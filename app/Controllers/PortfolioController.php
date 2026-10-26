<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Portfolio;
use App\Models\PortfolioTemplate;
use App\Models\Setting;
use App\Models\Website;
use App\Services\AuthService;

/**
 * Owner-only editing workflow for database-backed portfolio websites.
 */
class PortfolioController extends Controller
{
    public const TEMPLATES = [
        'classic' => 'Classic — clear and adaptable',
        'editorial' => 'Editorial — print-inspired showcase',
        '3d' => '3D Motion — immersive and technical',
        'minimal' => 'Minimal — calm, content-first',
        'noir' => 'Noir — bold dark portfolio',
        'aurora' => 'Aurora Motion — luminous and animated',
        'studio' => 'Studio Canvas — warm creative practice',
        'orbit' => 'Orbit 3D — mission-control interface',
        'prism' => 'Prism — colourful and expressive',
        'archive' => 'Archive — paper-led catalogue',
    ];

    public function edit(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $settings = (new Setting())->all();
        $templates = $this->availableTemplates((string) ($portfolio['template_key'] ?? 'classic'));
        $templatePreviewUrls = [];
        $portfolioModel = new Portfolio();
        foreach (array_keys($templates) as $templateKey) {
            $defaultPortfolioId = (int) ($settings['portfolio_template_default_' . $templateKey] ?? 0);
            if ($defaultPortfolioId < 1) {
                continue;
            }
            $sample = $portfolioModel->findPublishedTemplatePreview($defaultPortfolioId, $templateKey);
            if ($sample && !empty($sample['slug'])) {
                $templatePreviewUrls[$templateKey] = rtrim((string) ($this->config['base_url'] ?? ''), '/') . '/p/' . rawurlencode((string) $sample['slug']);
            }
        }

        $this->view('portfolio/edit', [
            'title' => 'Edit Portfolio',
            'website' => $website,
            'portfolio' => $portfolio,
            'templates' => $templates,
            'templatePreviewUrls' => $templatePreviewUrls,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Lightweight autosave for the portfolio setup page. Projects and detailed
     * portfolio sections remain exclusive to the Advanced Editor.
     */
    public function autosave(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (!Csrf::validate($this->input('_csrf'))) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'message' => 'Your session expired. Refresh and try again.']);
            return;
        }

        $displayName = trim((string) $this->input('display_name', (string) ($portfolio['display_name'] ?? '')));
        $headline = trim((string) $this->input('headline', (string) ($portfolio['headline'] ?? '')));
        $templateKey = trim((string) $this->input('template_key', (string) ($portfolio['template_key'] ?? 'classic')));

        if ($displayName === '' || mb_strlen($displayName) > 120 || mb_strlen($headline) > 180
            || !$this->canUseTemplate($templateKey, (string) ($portfolio['template_key'] ?? 'classic'))) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => 'Enter a full name and choose an available template.']);
            return;
        }

        $socialLinks = json_decode((string) ($portfolio['social_links'] ?? '{}'), true);
        (new Portfolio())->updateProfile(
            (int) $portfolio['id'],
            (int) $website['id'],
            $userId,
            [
                'display_name' => $displayName,
                'headline' => $headline,
                'bio' => (string) ($portfolio['bio'] ?? ''),
                'email' => ($portfolio['email'] ?? '') !== '' ? $portfolio['email'] : null,
                'location' => ($portfolio['location'] ?? '') !== '' ? $portfolio['location'] : null,
                'template_key' => $templateKey,
                'social_links' => is_array($socialLinks) ? $socialLinks : [],
            ]
        );

        echo json_encode(['ok' => true, 'message' => 'All changes saved', 'template_key' => $templateKey]);
    }

    public function save(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($this->editorPath((int) $website['id']));
        }

        $displayName = trim((string) $this->input('display_name', ''));
        $headline = trim((string) $this->input('headline', ''));
        $bio = trim((string) $this->input('bio', ''));
        $email = trim((string) $this->input('email', ''));
        $location = trim((string) $this->input('location', ''));
        $templateKey = trim((string) $this->input('template_key', (string) ($portfolio['template_key'] ?? 'classic')));

        $socialLinks = [];
        foreach (['github', 'linkedin', 'instagram', 'facebook', 'website'] as $platform) {
            $url = trim((string) $this->input('social_' . $platform, ''));
            if ($url === '') {
                continue;
            }
            if (strlen($url) > 500 || filter_var($url, FILTER_VALIDATE_URL) === false
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                Session::flash('error', 'Social links must be valid HTTP or HTTPS URLs.');
                $this->redirectTo($this->editorPath((int) $website['id']));
            }
            $socialLinks[$platform] = $url;
        }

        if ($displayName === '' || strlen($displayName) > 120
            || strlen($headline) > 180
            || strlen($bio) > 10000
            || strlen($email) > 190
            || strlen($location) > 120
            || !$this->canUseTemplate($templateKey, (string) ($portfolio['template_key'] ?? 'classic'))
            || ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            Session::flash('error', 'Check the profile fields. The name is required, the email must be valid, and the selected template must be available.');
            $this->redirectTo($this->editorPath((int) $website['id']));
        }

        (new Portfolio())->updateProfile(
            (int) $portfolio['id'],
            (int) $website['id'],
            $userId,
            [
                'display_name' => $displayName,
                'headline' => $headline,
                'bio' => $bio,
                'email' => $email !== '' ? $email : null,
                'location' => $location !== '' ? $location : null,
                'template_key' => $templateKey,
                'social_links' => $socialLinks,
            ]
        );

        Session::flash('success', 'Portfolio profile saved.');
        $this->redirectTo($this->editorPath((int) $website['id']));
    }

    /**
     * Upload a profile image for the portfolio owned by the current user.
     * Only raster images are accepted; SVG and executable formats are rejected.
     */
    public function uploadAvatar(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        $file = $_FILES['avatar'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
            || (int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 3 * 1024 * 1024) {
            Session::flash('error', 'Choose a profile image smaller than 3 MB.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        $tmpPath = (string) $file['tmp_name'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        $imageInfo = @getimagesize($tmpPath);
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!is_string($mime) || !isset($allowedTypes[$mime]) || $imageInfo === false
            || ($imageInfo['mime'] ?? '') !== $mime
            || (int) ($imageInfo[0] ?? 0) > 6000 || (int) ($imageInfo[1] ?? 0) > 6000) {
            Session::flash('error', 'Use a valid JPEG, PNG, or WebP image up to 6000 × 6000 pixels.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        $relativeDirectory = 'uploads/portfolios/' . $websiteId;
        $targetDirectory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            Session::flash('error', 'The profile image directory could not be created.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            Session::flash('error', 'The profile image could not be saved.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        $relativePath = $relativeDirectory . '/' . $filename;
        $saved = (new Portfolio())->updateAvatarForOwner(
            (int) $portfolio['id'],
            $websiteId,
            $userId,
            $relativePath
        );

        if (!$saved) {
            @unlink($targetPath);
            Session::flash('error', 'The profile image could not be linked to this portfolio.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        // Avoid accumulating replaced avatars. Resolve both paths and only
        // remove the previous file when it belongs to this website's upload folder.
        $previousAvatar = (string) ($portfolio['avatar_path'] ?? '');
        if ($previousAvatar !== '' && $previousAvatar !== $relativePath
            && str_starts_with($previousAvatar, 'uploads/portfolios/' . $websiteId . '/')) {
            $publicRoot = realpath(dirname(__DIR__, 2) . '/public');
            $uploadDirectory = $publicRoot === false
                ? false
                : realpath($publicRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'portfolios' . DIRECTORY_SEPARATOR . $websiteId);
            $previousAbsolutePath = $publicRoot === false
                ? false
                : realpath($publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $previousAvatar));

            if ($uploadDirectory !== false && $previousAbsolutePath !== false
                && str_starts_with($previousAbsolutePath, rtrim($uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
                && is_file($previousAbsolutePath)) {
                @unlink($previousAbsolutePath);
            }
        }

        Session::flash('success', 'Profile image uploaded.');
        $this->redirectTo($this->editorPath($websiteId));
    }

    public function addProject(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($this->editorPath((int) $website['id']));
        }

        $title = trim((string) $this->input('project_title', ''));
        $summary = trim((string) $this->input('project_summary', ''));
        $url = trim((string) $this->input('project_url', ''));

        if ($title === '' || strlen($title) > 150 || strlen($summary) > 5000
            || strlen($url) > 500
            || ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)))) {
            Session::flash('error', 'Enter a project title and a valid HTTP or HTTPS project link.');
            $this->redirectTo($this->editorPath((int) $website['id']));
        }

        $added = (new Portfolio())->addProjectForOwner(
            (int) $portfolio['id'],
            (int) $website['id'],
            $userId,
            [
                'title' => $title,
                'summary' => $summary,
                'project_url' => $url !== '' ? $url : null,
            ]
        );

        Session::flash($added ? 'success' : 'error', $added ? 'Project added to your portfolio.' : 'The project could not be added to this portfolio.');
        $this->redirectTo($this->editorPath((int) $website['id']));
    }

    public function saveProject(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        $projectId = (int) $this->input('project_id', 0);
        $title = trim((string) $this->input('project_title', ''));
        $summary = trim((string) $this->input('project_summary', ''));
        $url = trim((string) $this->input('project_url', ''));
        $sortOrder = max(0, min(9999, (int) $this->input('sort_order', 0)));

        if ($projectId < 1 || $title === '' || strlen($title) > 150 || strlen($summary) > 5000
            || strlen($url) > 500
            || ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)))) {
            Session::flash('error', 'Check the project title, description, and HTTP or HTTPS link.');
            $this->redirectTo($this->editorPath($websiteId));
        }

        $saved = (new Portfolio())->updateProjectForOwner(
            $projectId,
            (int) $portfolio['id'],
            $websiteId,
            $userId,
            [
                'title' => $title,
                'summary' => $summary,
                'project_url' => $url !== '' ? $url : null,
                'sort_order' => $sortOrder,
            ]
        );

        Session::flash($saved ? 'success' : 'error', $saved ? 'Project updated.' : 'Project not found or could not be updated.');
        $this->redirectTo($this->editorPath($websiteId));
    }

    public function deleteProject(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($this->editorPath((int) $website['id']));
        }

        $deleted = (new Portfolio())->deleteProjectForOwner(
            (int) $this->input('project_id', 0),
            (int) $portfolio['id'],
            (int) $website['id'],
            $userId
        );

        Session::flash($deleted ? 'success' : 'error', $deleted ? 'Project removed from your portfolio.' : 'Project not found or you do not have permission to remove it.');
        $this->redirectTo($this->editorPath((int) $website['id']));
    }

    /** Active templates are selectable; a current suspended template stays visible for its owner. */
    private function availableTemplates(string $currentTemplate = ''): array
    {
        $catalog = new PortfolioTemplate();
        $templates = $catalog->selectable();
        if ($currentTemplate !== '' && !isset($templates[$currentTemplate])) {
            foreach ($catalog->all() as $template) {
                if ((string) ($template['template_key'] ?? '') === $currentTemplate) {
                    $templates[$currentTemplate] = (string) ($template['display_name'] ?? $currentTemplate) . ' (currently suspended)';
                    break;
                }
            }
        }

        return $templates;
    }

    private function canUseTemplate(string $templateKey, string $currentTemplate): bool
    {
        return array_key_exists($templateKey, $this->availableTemplates($currentTemplate));
    }

    /**
     * Resolve a portfolio only when the authenticated user owns its website.
     */
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
            Session::flash('error', 'Portfolio data is not initialized for this website. Recreate the portfolio project or contact support.');
            $this->redirectTo('/dashboard/websites/show?id=' . $websiteId);
        }

        return [$userId, $website, $portfolio];
    }

    /** Return submitted Advanced Studio forms to the tab they came from. */
    private function editorPath(int $websiteId): string
    {
        if ((string) $this->input('return_to', '') !== 'advanced') {
            return '/dashboard/websites/portfolio?id=' . $websiteId;
        }

        $tab = strtolower(trim((string) $this->input('return_tab', 'profile')));
        if (!preg_match('/^[a-z-]{1,30}$/', $tab)) {
            $tab = 'profile';
        }

        return '/dashboard/websites/portfolio/advanced?id=' . $websiteId . '&tab=' . rawurlencode($tab);
    }
}
