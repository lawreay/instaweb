<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\AdvancedPortfolio;
use App\Models\Portfolio;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\PortfolioAiService;
use InvalidArgumentException;
use RuntimeException;

/**
 * Owner-scoped JSON API for review-first portfolio AI suggestions.
 * Provider keys are used for one request only and are never stored or returned.
 */
final class PortfolioAiController extends Controller
{
    private const SECTIONS = [
        'goals' => 'Goals',
        'interests' => 'Interests',
        'challenges' => 'Challenges',
        'experience' => 'Work experience',
        'education' => 'Education',
        'skills' => 'Skills',
        'news' => 'News and updates',
        'partnerships' => 'Partnerships',
    ];

    private const REWRITABLE_SECTIONS = [
        'goals', 'interests', 'challenges', 'experience', 'education', 'news', 'partnerships',
    ];

    private const FIELD_RULES = [
        'goals' => ['details'],
        'interests' => ['details'],
        'challenges' => ['details', 'outcome'],
        'experience' => ['organisation', 'details', 'start_date', 'end_date', 'current'],
        'education' => ['organisation', 'field_of_study', 'details', 'start_date', 'end_date', 'current'],
        'skills' => ['category', 'proficiency'],
        'news' => ['details', 'link', 'published_date'],
        'partnerships' => ['details', 'link', 'start_date', 'end_date'],
    ];

    private const REQUIRED_FIELDS = [
        'education' => ['organisation'],
        'experience' => ['organisation'],
        'news' => ['details'],
    ];

    public function status(): void
    {
        if ($this->ownedPortfolio() === null) {
            return;
        }

        $this->json(200, ['ok' => true, 'ai' => PortfolioAiService::publicStatus()]);
    }

    public function propose(): void
    {
        $owned = $this->ownedPortfolio();
        if ($owned === null || !$this->validCsrf()) {
            return;
        }
        [$userId, $website, $portfolio] = $owned;

        if (!$this->allowProposal($userId)) {
            $this->json(429, ['ok' => false, 'message' => 'AI suggestions are limited to 12 per hour. Please try again later.']);
            return;
        }

        try {
            $context = $this->proposalContext((int) $website['id'], $userId, $portfolio);
            $provider = trim((string) $this->input('provider', ''));
            // The request key is intentionally never written to Session, database, flash, or logs.
            $requestKey = trim((string) $this->input('api_key', ''));
            $result = (new PortfolioAiService())->propose($provider, $requestKey, $context);
            $proposal = array_merge($result, [
                'operation' => $context['operation'],
                'section_key' => $context['section_key'] ?? '',
                'item_id' => $context['item_id'] ?? 0,
                'field' => $context['field'] ?? '',
            ]);
            $this->json(200, ['ok' => true, 'proposal' => $proposal]);
        } catch (InvalidArgumentException $exception) {
            $this->json(422, ['ok' => false, 'message' => $exception->getMessage()]);
        } catch (RuntimeException $exception) {
            // Provider/client exceptions contain safe, deliberately generic diagnostics; never expose raw provider bodies.
            $this->json(502, ['ok' => false, 'message' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            $this->json(500, ['ok' => false, 'message' => 'Unable to create an AI suggestion right now.']);
        }
    }

    public function apply(): void
    {
        $owned = $this->ownedPortfolio();
        if ($owned === null || !$this->validCsrf()) {
            return;
        }
        [$userId, $website, $portfolio] = $owned;

        $encodedProposal = (string) $this->input('proposal', '');
        if ($encodedProposal === '' || strlen($encodedProposal) > 60000) {
            $this->json(422, ['ok' => false, 'message' => 'The AI proposal is missing or too large. Generate it again.']);
            return;
        }
        $proposal = json_decode($encodedProposal, true);
        if (!is_array($proposal)) {
            $this->json(422, ['ok' => false, 'message' => 'The AI proposal could not be read. Generate it again.']);
            return;
        }

        try {
            $result = $this->applyProposal($proposal, (int) $website['id'], $userId, $portfolio);
            $this->json(200, ['ok' => true] + $result);
        } catch (InvalidArgumentException $exception) {
            $this->json(422, ['ok' => false, 'message' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            $this->json(500, ['ok' => false, 'message' => 'The suggestion could not be applied. Please review it and try again.']);
        }
    }

    /**
     * @param array<string, mixed> $portfolio
     * @return array<string, mixed>
     */
    private function proposalContext(int $websiteId, int $userId, array $portfolio): array
    {
        $operation = trim((string) $this->input('operation', ''));
        if (!in_array($operation, ['rewrite_profile', 'rewrite_item', 'generate_items'], true)) {
            throw new InvalidArgumentException('Choose an AI enhancement type.');
        }

        $instructions = $this->text($this->input('instructions', ''), 2000);
        $tone = strtolower($this->text($this->input('tone', 'professional'), 30));
        if (!in_array($tone, ['professional', 'warm', 'concise'], true)) {
            $tone = 'professional';
        }

        $context = [
            'operation' => $operation,
            'tone' => $tone,
            'instructions' => $instructions !== '' ? $instructions : 'Improve clarity while keeping the supplied facts accurate.',
            'profile' => [
                'display_name' => $this->text($portfolio['display_name'] ?? '', 120),
                'headline' => $this->text($portfolio['headline'] ?? '', 180),
                'bio' => $this->text($portfolio['bio'] ?? '', 5000),
                'location' => $this->text($portfolio['location'] ?? '', 120),
            ],
        ];

        if ($operation === 'rewrite_profile') {
            $field = trim((string) $this->input('field', 'bio'));
            if (!in_array($field, ['headline', 'bio'], true)) {
                throw new InvalidArgumentException('Choose a profile field to rewrite.');
            }
            $source = $this->text($portfolio[$field] ?? '', $field === 'bio' ? 10000 : 180);
            if ($source === '') {
                throw new InvalidArgumentException('Add some profile text before asking AI to rewrite it.');
            }
            $context['field'] = $field;
            $context['section_key'] = 'profile';
            $context['source'] = ['text' => $source];

            return $context;
        }

        $section = trim((string) $this->input('section_key', ''));
        if (!isset(self::SECTIONS[$section])) {
            throw new InvalidArgumentException('Choose a valid portfolio section.');
        }
        $context['section_key'] = $section;
        $context['section_label'] = self::SECTIONS[$section];
        $items = (new AdvancedPortfolio())->items($websiteId, $userId);

        if ($operation === 'rewrite_item') {
            if (!in_array($section, self::REWRITABLE_SECTIONS, true)) {
                throw new InvalidArgumentException('Choose a written portfolio entry to rewrite.');
            }
            $itemId = (int) $this->input('item_id', 0);
            $item = $this->findItem($items, $itemId, $section);
            if ($item === null) {
                throw new InvalidArgumentException('Choose one of your existing entries to rewrite.');
            }
            $payload = json_decode((string) ($item['payload'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            $context['item_id'] = $itemId;
            $context['source'] = [
                'title' => $this->text($item['title'] ?? '', 190),
                'details' => $this->text($payload['details'] ?? '', 5000),
                'organisation' => $this->text($payload['organisation'] ?? '', 190),
                'period' => $this->text($payload['period'] ?? '', 190),
            ];

            return $context;
        }

        $count = max(1, min(3, (int) $this->input('count', 2)));
        $context['requested_count'] = $count;
        $existing = [];
        foreach ($items as $item) {
            if (($item['section_key'] ?? '') !== $section || count($existing) >= 10) {
                continue;
            }
            $existing[] = $this->text($item['title'] ?? '', 190);
        }
        $context['existing_titles'] = $existing;

        return $context;
    }

    /**
     * @param array<string, mixed> $proposal
     * @param array<string, mixed> $portfolio
     * @return array<string, mixed>
     */
    private function applyProposal(array $proposal, int $websiteId, int $userId, array $portfolio): array
    {
        $operation = (string) ($proposal['operation'] ?? '');
        if ($operation === 'rewrite_profile') {
            $field = (string) ($proposal['field'] ?? '');
            $limit = $field === 'bio' ? 10000 : 180;
            $rewrite = $this->text($proposal['rewrite'] ?? '', $limit);
            if (!in_array($field, ['headline', 'bio'], true) || $rewrite === '') {
                throw new InvalidArgumentException('This profile rewrite is incomplete. Generate it again.');
            }
            $socialLinks = json_decode((string) ($portfolio['social_links'] ?? '{}'), true);
            (new Portfolio())->updateProfile((int) $portfolio['id'], $websiteId, $userId, [
                'display_name' => (string) ($portfolio['display_name'] ?? ''),
                'headline' => $field === 'headline' ? $rewrite : (string) ($portfolio['headline'] ?? ''),
                'bio' => $field === 'bio' ? $rewrite : (string) ($portfolio['bio'] ?? ''),
                'email' => ($portfolio['email'] ?? '') !== '' ? $portfolio['email'] : null,
                'location' => ($portfolio['location'] ?? '') !== '' ? $portfolio['location'] : null,
                'template_key' => (string) ($portfolio['template_key'] ?? 'classic'),
                'social_links' => is_array($socialLinks) ? $socialLinks : [],
            ]);

            return ['message' => 'AI profile rewrite applied.', 'tab' => 'profile'];
        }

        if ($operation === 'rewrite_item') {
            $section = (string) ($proposal['section_key'] ?? '');
            $itemId = (int) ($proposal['item_id'] ?? 0);
            $rewrite = $this->text($proposal['rewrite'] ?? '', 5000);
            if (!in_array($section, self::REWRITABLE_SECTIONS, true) || $itemId < 1 || $rewrite === '') {
                throw new InvalidArgumentException('This entry rewrite is incomplete. Generate it again.');
            }
            $model = new AdvancedPortfolio();
            $item = $this->findItem($model->items($websiteId, $userId), $itemId, $section);
            if ($item === null) {
                throw new InvalidArgumentException('That portfolio entry is no longer available.');
            }
            $payload = json_decode((string) ($item['payload'] ?? '{}'), true);
            $payload = is_array($payload) ? $payload : [];
            $payload['details'] = $rewrite;
            $model->saveItem($websiteId, $userId, $section, (string) ($item['title'] ?? ''), $payload, (int) ($item['sort_order'] ?? 0), $itemId);

            return ['message' => 'AI entry rewrite applied.', 'tab' => $section];
        }

        if ($operation !== 'generate_items') {
            throw new InvalidArgumentException('This AI proposal is not supported. Generate it again.');
        }
        $section = (string) ($proposal['section_key'] ?? '');
        if (!isset(self::SECTIONS[$section]) || !is_array($proposal['items'] ?? null)) {
            throw new InvalidArgumentException('This entry proposal is incomplete. Generate it again.');
        }

        $model = new AdvancedPortfolio();
        $nextSort = 0;
        foreach ($model->items($websiteId, $userId) as $item) {
            if (($item['section_key'] ?? '') === $section) {
                $nextSort = max($nextSort, (int) ($item['sort_order'] ?? 0) + 1);
            }
        }
        $applied = 0;
        foreach (array_slice($proposal['items'], 0, 3) as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $item = $this->normaliseItem($section, $candidate);
            if ($item === null) {
                continue;
            }
            $model->saveItem($websiteId, $userId, $section, $item['title'], $item['payload'], $nextSort++);
            $applied++;
        }
        if ($applied === 0) {
            throw new InvalidArgumentException('None of these suggestions met the required fields. Refine your instructions and generate again.');
        }

        return ['message' => $applied . ' AI suggestion' . ($applied === 1 ? '' : 's') . ' added.', 'tab' => $section, 'applied' => $applied];
    }

    /**
     * @param array<string, mixed> $candidate
     * @return array{title: string, payload: array<string, string>}|null
     */
    private function normaliseItem(string $section, array $candidate): ?array
    {
        $title = $this->text($candidate['title'] ?? '', 190);
        if ($title === '' || !isset(self::FIELD_RULES[$section])) {
            return null;
        }
        $payload = [];
        foreach (self::FIELD_RULES[$section] as $field) {
            $value = $field === 'current'
                ? (in_array(strtolower((string) ($candidate[$field] ?? '')), ['1', 'true', 'yes'], true) ? '1' : '0')
                : $this->text($candidate[$field] ?? '', $field === 'details' ? 5000 : ($field === 'link' ? 500 : 190));
            if (in_array($field, self::REQUIRED_FIELDS[$section] ?? [], true) && $value === '') {
                return null;
            }
            if ($field === 'link' && $value !== '' && (!$this->isHttpUrl($value))) {
                return null;
            }
            if (in_array($field, ['start_date', 'end_date', 'published_date'], true)
                && $value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $value = '';
            }
            if ($field === 'proficiency' && $value !== '' && !in_array(strtolower($value), ['beginner', 'intermediate', 'advanced', 'expert'], true)) {
                $value = '';
            }
            $payload[$field] = $value;
        }

        if (in_array($section, ['education', 'experience', 'partnerships'], true)) {
            $start = $payload['start_date'] ?? '';
            $end = $payload['end_date'] ?? '';
            $payload['period'] = trim($start . ($end !== '' ? ' – ' . $end : ($start !== '' && !empty($payload['current']) ? ' – Present' : '')));
        }
        if (($payload['field_of_study'] ?? '') !== '') {
            $payload['details'] = trim($payload['field_of_study'] . (($payload['details'] ?? '') !== '' ? "\n" . $payload['details'] : ''));
        }
        if (($payload['category'] ?? '') !== '') {
            $payload['details'] = trim(($payload['details'] ?? '') . (($payload['details'] ?? '') !== '' ? "\n" : '') . 'Category: ' . $payload['category']);
        }
        if (($payload['proficiency'] ?? '') !== '') {
            $payload['details'] = trim(($payload['details'] ?? '') . (($payload['details'] ?? '') !== '' ? ' · ' : '') . 'Proficiency: ' . $payload['proficiency']);
        }
        if (($payload['outcome'] ?? '') !== '') {
            $payload['details'] = trim(($payload['details'] ?? '') . "\nOutcome: " . $payload['outcome']);
        }
        if (($payload['published_date'] ?? '') !== '') {
            $payload['period'] = $payload['published_date'];
        }
        if (!empty($payload['current']) && in_array($section, ['education', 'experience'], true)) {
            $payload['period'] = trim(($payload['start_date'] ?? '') . (($payload['start_date'] ?? '') !== '' ? ' – ' : '') . 'Present');
        }

        return ['title' => $title, 'payload' => $payload];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function findItem(array $items, int $itemId, string $section): ?array
    {
        foreach ($items as $item) {
            if ((int) ($item['id'] ?? 0) === $itemId && ($item['section_key'] ?? '') === $section) {
                return $item;
            }
        }

        return null;
    }

    /** @return array{0: int, 1: array<string, mixed>, 2: array<string, mixed>}|null */
    private function ownedPortfolio(): ?array
    {
        if (!AuthService::check()) {
            $this->json(401, ['ok' => false, 'message' => 'Sign in to use AI enhancement.']);
            return null;
        }
        $userId = (int) AuthService::id();
        $websiteId = (int) $this->input('id', $this->input('website_id', 0));
        $website = (new Website())->findForUser($websiteId, $userId);
        if (!$website || ($website['template_key'] ?? '') !== 'portfolio') {
            $this->json(404, ['ok' => false, 'message' => 'Portfolio website not found.']);
            return null;
        }
        $portfolio = (new Portfolio())->findForWebsiteOwner($websiteId, $userId);
        if (!$portfolio) {
            $this->json(404, ['ok' => false, 'message' => 'Portfolio data is not initialized for this website.']);
            return null;
        }

        return [$userId, $website, $portfolio];
    }

    private function validCsrf(): bool
    {
        if (Csrf::validate((string) $this->input('_csrf', ''))) {
            return true;
        }
        $this->json(419, ['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.']);

        return false;
    }

    private function allowProposal(int $userId): bool
    {
        $now = time();
        $key = 'portfolio_ai_rate_' . $userId;
        $attempts = Session::get($key, []);
        $attempts = is_array($attempts) ? $attempts : [];
        $attempts = array_values(array_filter($attempts, static fn (mixed $at): bool => is_int($at) && $at > $now - 3600));
        if (count($attempts) >= 12) {
            return false;
        }
        $attempts[] = $now;
        Session::put($key, $attempts);

        return true;
    }

    private function text(mixed $value, int $limit): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return mb_substr($value, 0, $limit);
    }

    private function isHttpUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    /** @param array<string, mixed> $payload */
    private function json(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
