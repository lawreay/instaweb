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

/**
 * Advanced presentation and content editor. All content is website-scoped.
 */
class AdvancedPortfolioController extends Controller
{
    private const SETTINGS = [
        'accent_color' => ['#f97316', 7],
        'background_color' => ['#ffffff', 7],
        'text_color' => ['#111827', 7],
        'font_family' => ['system', 20],
        'hero_style' => ['centered', 20],
        'show_projects' => ['1', 1],
        'show_contact' => ['1', 1],
    ];

    /**
     * Structured content areas supported by the InstaWeb portfolio editor.
     * Hero/About and Projects keep their dedicated editors.
     */
    private const SECTIONS = [
        'goals' => 'Goals',
        'interests' => 'Interests',
        'challenges' => 'Challenges',
        'experience' => 'Work experience',
        'education' => 'Education',
        'skills' => 'Skills',
        'news' => 'News and updates',
        'partnerships' => 'Partnerships',
        'cv' => 'CV / Resume',
    ];

    public function edit(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $model = new AdvancedPortfolio();
        $settings = array_merge(
            array_map(static fn (array $definition): string => $definition[0], self::SETTINGS),
            $model->settings((int) $website['id'], $userId)
        );

        $this->view('portfolio/advanced', [
            'title' => 'Advanced Portfolio Editor',
            'website' => $website,
            'portfolio' => $portfolio,
            'settings' => $settings,
            'items' => $model->items((int) $website['id'], $userId),
            'projects' => (new Portfolio())->projects((int) $portfolio['id']),
            'templates' => PortfolioController::TEMPLATES,
            'sectionLabels' => self::SECTIONS,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function save(): void
    {
        [$userId, $website] = $this->ownedPortfolio();
        if (!$this->validCsrf((int) $website['id'])) {
            return;
        }

        $settings = [];
        foreach (self::SETTINGS as $key => [$default, $maxLength]) {
            $value = trim((string) $this->input($key, $default));
            if (strlen($value) > $maxLength) {
                Session::flash('error', 'One or more design settings are too long.');
                $this->backToEditor((int) $website['id']);
                return;
            }
            $settings[$key] = $value;
        }

        foreach (['accent_color', 'background_color', 'text_color'] as $key) {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $settings[$key])) {
                Session::flash('error', 'Choose valid six-digit hex colours.');
                $this->backToEditor((int) $website['id']);
                return;
            }
        }

        if (!in_array($settings['font_family'], ['system', 'serif', 'sans-serif', 'mono'], true)
            || !in_array($settings['hero_style'], ['centered', 'split', 'minimal'], true)
            || !in_array($settings['show_projects'], ['0', '1'], true)
            || !in_array($settings['show_contact'], ['0', '1'], true)) {
            Session::flash('error', 'Choose one of the available design options.');
            $this->backToEditor((int) $website['id']);
            return;
        }

        (new AdvancedPortfolio())->saveSettings((int) $website['id'], $userId, $settings);
        Session::flash('success', 'Advanced portfolio design settings saved.');
        $this->backToEditor((int) $website['id']);
    }

    public function saveItem(): void
    {
        [$userId, $website] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];
        if (!$this->validCsrf($websiteId)) {
            return;
        }

        $section = trim((string) $this->input('section_key', ''));
        $title = trim((string) $this->input('title', ''));
        $sortOrder = max(0, min(9999, (int) $this->input('sort_order', 0)));
        $itemId = (int) $this->input('item_id', 0);
        $fieldRules = [
            'goals' => ['details'],
            'interests' => ['details'],
            'challenges' => ['details', 'outcome'],
            'experience' => ['organisation', 'details', 'start_date', 'end_date', 'current'],
            'education' => ['organisation', 'field_of_study', 'details', 'start_date', 'end_date', 'current'],
            'skills' => ['category', 'proficiency'],
            'news' => ['details', 'link', 'published_date'],
            'partnerships' => ['details', 'link', 'start_date', 'end_date'],
            'cv' => ['details', 'year'],
        ];
        $requiredFields = ['education' => ['organisation'], 'experience' => ['organisation'], 'news' => ['details']];
        if (!isset(self::SECTIONS[$section]) || $title === '' || mb_strlen($title) > 190) {
            Session::flash('error', 'Enter a valid title for this portfolio entry.');
            $this->backToEditor($websiteId);
            return;
        }
        $payload = [];
        foreach ($fieldRules[$section] as $field) {
            $value = $field === 'current' ? ((string) $this->input($field, '0') === '1' ? '1' : '0') : trim((string) $this->input($field, ''));
            $maxLength = in_array($field, ['details'], true) ? 5000 : (in_array($field, ['link'], true) ? 500 : 190);
            if (mb_strlen($value) > $maxLength) {
                Session::flash('error', 'One or more fields are too long. Please shorten the content and try again.');
                $this->backToEditor($websiteId);
                return;
            }
            if (in_array($field, $requiredFields[$section] ?? [], true) && $value === '') {
                Session::flash('error', 'Complete the required fields for ' . self::SECTIONS[$section] . '.');
                $this->backToEditor($websiteId);
                return;
            }
            if ($field === 'link' && $value !== '' && (!filter_var($value, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true))) {
                Session::flash('error', 'Links must be complete HTTP or HTTPS URLs, including https://.');
                $this->backToEditor($websiteId);
                return;
            }
            $payload[$field] = $value;
        }
        if (in_array($section, ['education', 'experience', 'partnerships'], true)) {
            $start = trim((string) ($payload['start_date'] ?? ''));
            $end = trim((string) ($payload['end_date'] ?? ''));
            $payload['period'] = trim($start . ($end !== '' ? ' – ' . $end : ($start !== '' && !empty($payload['current']) ? ' – Present' : '')));
        }
        if (isset($payload['organisation']) && $payload['organisation'] !== '') {
            // Keep the existing public renderer compatible while preserving section-specific fields.
            $payload['organisation'] = $payload['organisation'];
        }
        if (isset($payload['field_of_study']) && $payload['field_of_study'] !== '') {
            $existingDetails = trim((string) ($payload['details'] ?? ''));
            $payload['details'] = trim($payload['field_of_study'] . ($existingDetails !== '' ? "\n" . $existingDetails : ''));
        }
        if (isset($payload['category']) && $payload['category'] !== '') {
            $existingDetails = trim((string) ($payload['details'] ?? ''));
            $categoryLine = 'Category: ' . $payload['category'];
            $payload['details'] = $existingDetails === '' ? $categoryLine : $existingDetails . "\n" . $categoryLine;
        }
        if (isset($payload['proficiency']) && $payload['proficiency'] !== '') {
            $existingDetails = trim((string) ($payload['details'] ?? ''));
            $payload['details'] = $existingDetails === '' ? 'Proficiency: ' . $payload['proficiency'] : $existingDetails . ' · Proficiency: ' . $payload['proficiency'];
        }
        if (isset($payload['outcome']) && $payload['outcome'] !== '') {
            $payload['details'] = trim(($payload['details'] ?? '') . "\nOutcome: " . $payload['outcome']);
        }
        if (isset($payload['year']) && $payload['year'] !== '') {
            $payload['period'] = $payload['year'];
        }
        if (isset($payload['published_date']) && $payload['published_date'] !== '') {
            // The shared public renderer displays period; map the news date there until templates support a dedicated date field.
            $payload['period'] = $payload['published_date'];
        }
        if (!empty($payload['current']) && in_array($section, ['education', 'experience'], true)) {
            $payload['period'] = trim((string) ($payload['start_date'] ?? '') . (!empty($payload['start_date']) ? ' – ' : '') . 'Present');
        }
        (new AdvancedPortfolio())->saveItem(
            $websiteId,
            $userId,
            $section,
            $title,
            $payload,
            $sortOrder,
            $itemId > 0 ? $itemId : null
        );
        Session::flash('success', $itemId > 0 ? 'Portfolio section item updated.' : 'Portfolio section item added.');
        $this->backToEditor($websiteId);
    }

    public function toggleItem(): void
    {
        [$userId, $website] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];
        if (!$this->validCsrf($websiteId)) {
            return;
        }

        $itemId = (int) $this->input('item_id', 0);
        $enabled = (string) $this->input('enabled', '0') === '1';
        if ($itemId > 0) {
            (new AdvancedPortfolio())->setItemEnabled($websiteId, $userId, $itemId, $enabled);
            Session::flash('success', $enabled ? 'Portfolio item enabled.' : 'Portfolio item hidden.');
        }
        $this->backToEditor($websiteId);
    }

    public function deleteItem(): void
    {
        [$userId, $website] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];
        if (!$this->validCsrf($websiteId)) {
            return;
        }

        $itemId = (int) $this->input('item_id', 0);
        if ($itemId > 0) {
            (new AdvancedPortfolio())->deleteItem($websiteId, $userId, $itemId);
            Session::flash('success', 'Portfolio item deleted.');
        }
        $this->backToEditor($websiteId);
    }

    private function validCsrf(int $websiteId): bool
    {
        if (Csrf::validate($this->input('_csrf'))) {
            return true;
        }
        Session::flash('error', 'Your session expired. Please try again.');
        $this->backToEditor($websiteId);
        return false;
    }

    private function backToEditor(int $websiteId): void
    {
        $tab = strtolower(trim((string) $this->input('tab', 'overview')));
        if (!preg_match('/^[a-z-]{1,30}$/', $tab)) {
            $tab = 'overview';
        }

        $this->redirectTo('/dashboard/websites/portfolio/advanced?id=' . $websiteId . '&tab=' . rawurlencode($tab));
    }

    private function ownedPortfolio(): array
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        $userId = (int) AuthService::id();
        $websiteId = (int) $this->input('id', $this->input('website_id', 0));
        $website = (new Website())->findForUser($websiteId, $userId);

        if (!$website || ($website['template_key'] ?? '') !== 'portfolio') {
            Session::flash('error', 'Portfolio website not found.');
            $this->redirectTo('/dashboard/websites');
        }

        $portfolio = (new Portfolio())->findForWebsiteOwner($websiteId, $userId);
        if (!$portfolio) {
            Session::flash('error', 'Portfolio data is not initialized for this website.');
            $this->redirectTo('/dashboard/websites/show?id=' . $websiteId);
        }

        return [$userId, $website, $portfolio];
    }
}
