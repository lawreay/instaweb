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
            'noirSectionImages' => $model->noirSectionImages((int) $website['id'], $userId),
            'projects' => (new Portfolio())->projects((int) $portfolio['id']),
            'templates' => PortfolioController::TEMPLATES,
            'sectionLabels' => self::SECTIONS,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function save(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
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

        $templateKey = trim((string) $this->input('template_key', (string) ($portfolio['template_key'] ?? 'classic')));
        if (!array_key_exists($templateKey, PortfolioController::TEMPLATES)) {
            Session::flash('error', 'Choose one of the available portfolio templates.');
            $this->backToEditor((int) $website['id']);
            return;
        }

        (new AdvancedPortfolio())->saveSettings((int) $website['id'], $userId, $settings);
        (new Portfolio())->updateTemplateForOwner((int) $website['id'], $userId, $templateKey);
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
        if ($section === 'cv') {
            $existingItem = null;
            if ($itemId > 0) {
                foreach ((new AdvancedPortfolio())->items($websiteId, $userId) as $candidate) {
                    if ((int) ($candidate['id'] ?? 0) === $itemId && ($candidate['section_key'] ?? '') === 'cv') {
                        $existingItem = $candidate;
                        break;
                    }
                }
                if ($existingItem === null) {
                    Session::flash('error', 'The CV entry could not be found for this website.');
                    $this->backToEditor($websiteId);
                    return;
                }
                $existingPayload = json_decode((string) ($existingItem['payload'] ?? '{}'), true);
                if (is_array($existingPayload) && isset($existingPayload['file_path'])) {
                    $payload['file_path'] = (string) $existingPayload['file_path'];
                    $payload['file_name'] = (string) ($existingPayload['file_name'] ?? '');
                }
            }
            $file = $_FILES['cv_file'] ?? null;
            if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                    || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
                    || (int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 5 * 1024 * 1024) {
                    Session::flash('error', 'Choose a PDF, DOC, or DOCX file smaller than 5 MB.');
                    $this->backToEditor($websiteId);
                    return;
                }
                $tmpPath = (string) $file['tmp_name'];
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpPath);
                $allowedTypes = [
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                ];
                $extension = is_string($mime) ? ($allowedTypes[$mime] ?? null) : null;
                $originalExtension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
                if ($extension === null && $mime === 'application/zip' && $originalExtension === 'docx'
                    && class_exists('\\ZipArchive')) {
                    $zip = new \ZipArchive();
                    if ($zip->open($tmpPath) === true) {
                        $isDocx = $zip->locateName('[Content_Types].xml') !== false
                            && $zip->locateName('word/document.xml') !== false;
                        $zip->close();
                        if ($isDocx) {
                            $extension = 'docx';
                        }
                    }
                }
                if ($extension === null || $originalExtension !== $extension) {
                    Session::flash('error', 'The CV file type is not supported. Upload a valid PDF, DOC, or DOCX file.');
                    $this->backToEditor($websiteId);
                    return;
                }
                $relativeDirectory = 'uploads/portfolios/' . $websiteId . '/cv';
                $targetDirectory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
                if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
                    Session::flash('error', 'The CV upload directory could not be created.');
                    $this->backToEditor($websiteId);
                    return;
                }
                $filename = bin2hex(random_bytes(16)) . '.' . $extension;
                $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($tmpPath, $targetPath)) {
                    Session::flash('error', 'The CV file could not be saved.');
                    $this->backToEditor($websiteId);
                    return;
                }
                $payload['file_path'] = $relativeDirectory . '/' . $filename;
                $originalName = basename((string) ($file['name'] ?? ('CV.' . $extension)));
                $payload['file_name'] = mb_substr($originalName, 0, 190);
                if (isset($existingPayload['file_path'])) {
                    $oldPath = (string) $existingPayload['file_path'];
                    $prefix = 'uploads/portfolios/' . $websiteId . '/cv/';
                    if (str_starts_with($oldPath, $prefix)) {
                        $oldAbsolute = realpath(dirname(__DIR__, 2) . '/public/' . str_replace('/', DIRECTORY_SEPARATOR, $oldPath));
                        $safeDirectory = realpath($targetDirectory);
                        if ($oldAbsolute !== false && $safeDirectory !== false
                            && str_starts_with($oldAbsolute, rtrim($safeDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                            && is_file($oldAbsolute)) {
                            @unlink($oldAbsolute);
                        }
                    }
                }
            } elseif (empty($payload['file_path']) && $itemId === 0) {
                Session::flash('error', 'Choose a CV file to upload.');
                $this->backToEditor($websiteId);
                return;
            }
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

    /** Upload a Noir-only override for a section's default editorial image. */
    public function uploadNoirSectionImage(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];
        if (!$this->validCsrf($websiteId)) {
            return;
        }
        if (($portfolio['template_key'] ?? '') !== 'noir') {
            Session::flash('error', 'Section images are available while the Noir template is selected.');
            $this->backToEditor($websiteId);
            return;
        }

        $sectionKey = trim((string) $this->input('section_key', ''));
        $allowedSections = array_merge(array_keys(self::SECTIONS), ['projects']);
        if (!in_array($sectionKey, $allowedSections, true)) {
            Session::flash('error', 'Choose a valid Noir portfolio section.');
            $this->backToEditor($websiteId);
            return;
        }

        $file = $_FILES['noir_image'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
            || (int) ($file['size'] ?? 0) < 1 || (int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            Session::flash('error', 'Choose a JPEG, PNG, or WebP image smaller than 5 MB.');
            $this->backToEditor($websiteId);
            return;
        }

        $tmpPath = (string) $file['tmp_name'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
        $dimensions = @getimagesize($tmpPath);
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!is_string($mime) || !isset($allowedTypes[$mime]) || $dimensions === false
            || ($dimensions['mime'] ?? '') !== $mime || (int) ($dimensions[0] ?? 0) > 6000 || (int) ($dimensions[1] ?? 0) > 6000) {
            Session::flash('error', 'Use a valid JPEG, PNG, or WebP image up to 6000 × 6000 pixels.');
            $this->backToEditor($websiteId);
            return;
        }

        $relativeDirectory = 'uploads/portfolios/' . $websiteId . '/noir';
        $directory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            Session::flash('error', 'The Noir image directory could not be created.');
            $this->backToEditor($websiteId);
            return;
        }
        $relativePath = $relativeDirectory . '/section-' . $sectionKey . '-' . bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        $targetPath = dirname(__DIR__, 2) . '/public/' . $relativePath;
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            Session::flash('error', 'The Noir section image could not be saved.');
            $this->backToEditor($websiteId);
            return;
        }

        $model = new AdvancedPortfolio();
        $previous = $model->noirSectionImage($websiteId, $userId, $sectionKey);
        try {
            $model->saveNoirSectionImage($websiteId, $userId, $sectionKey, $relativePath, mb_substr(basename((string) ($file['name'] ?? 'image.' . $allowedTypes[$mime])), 0, 255), $mime, (int) $file['size']);
            if (is_array($previous) && !empty($previous['storage_key']) && $previous['storage_key'] !== $relativePath) {
                $model->deleteUploadPath($websiteId, $userId, (string) $previous['storage_key']);
                $this->deleteNoirImageFile((string) $previous['storage_key'], $websiteId);
            }
        } catch (\Throwable) {
            @unlink($targetPath);
            Session::flash('error', 'The Noir section image could not be linked to this portfolio.');
            $this->backToEditor($websiteId);
            return;
        }

        Session::flash('success', 'Noir section image saved.');
        $this->backToEditor($websiteId);
    }

    public function removeNoirSectionImage(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];
        if (!$this->validCsrf($websiteId)) {
            return;
        }
        if (($portfolio['template_key'] ?? '') !== 'noir') {
            $this->backToEditor($websiteId);
            return;
        }
        $sectionKey = trim((string) $this->input('section_key', ''));
        if (!in_array($sectionKey, array_merge(array_keys(self::SECTIONS), ['projects']), true)) {
            Session::flash('error', 'Choose a valid Noir portfolio section.');
            $this->backToEditor($websiteId);
            return;
        }
        $upload = (new AdvancedPortfolio())->deleteNoirSectionImage($websiteId, $userId, $sectionKey);
        if ($upload !== null) {
            $this->deleteNoirImageFile((string) $upload['storage_key'], $websiteId);
            Session::flash('success', 'Custom Noir image removed. The default visual will be used again.');
        }
        $this->backToEditor($websiteId);
    }

    private function deleteNoirImageFile(string $relativePath, int $websiteId): void
    {
        $prefix = 'uploads/portfolios/' . $websiteId . '/noir/section-';
        if (!str_starts_with($relativePath, $prefix)) {
            return;
        }
        $publicRoot = realpath(dirname(__DIR__, 2) . '/public');
        $directory = $publicRoot === false ? false : realpath($publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolios' . DIRECTORY_SEPARATOR . $websiteId . DIRECTORY_SEPARATOR . 'noir');
        $path = $publicRoot === false ? false : realpath($publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if ($directory !== false && $path !== false && str_starts_with($path, rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) && is_file($path)) {
            @unlink($path);
        }
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
