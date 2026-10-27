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
        'ledger' => 'Ledger — precise professional portfolio',
        'graduate' => 'Graduate Path — confident early-career portfolio',
    ];

    public function edit(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $settings = (new Setting())->all();
        $isPremiumUser = (new Website())->userPlan($userId) === 'premium';
        $templates = $this->availableTemplates((string) ($portfolio['template_key'] ?? 'classic'), $userId);
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
            'isPremiumUser' => $isPremiumUser,
            'premiumTemplateKeys' => PortfolioTemplate::PREMIUM_BUILT_INS,
            'premiumPlanPrice' => (string) ($settings['premium_plan_price'] ?? 'MWK 5,000/mo'),
            'premiumUpgradeEnabled' => ($settings['premium_plan_enabled'] ?? '1') === '1'
                && trim((string) ($settings['paychangu_secret_key'] ?? '')) !== '',
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
            || !$this->canUseTemplate($templateKey, (string) ($portfolio['template_key'] ?? 'classic'), $userId)) {
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
            || !$this->canUseTemplate($templateKey, (string) ($portfolio['template_key'] ?? 'classic'), $userId)
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
            || (int) ($file['size'] ?? 0) < 1) {
            $this->avatarUploadFailure($websiteId, 'Choose a valid image to upload.');
            return;
        }

        $tmpPath = (string) $file['tmp_name'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        $imageInfo = @getimagesize($tmpPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        if (!is_string($mime) || !in_array($mime, $allowedTypes, true) || $imageInfo === false
            || ($imageInfo['mime'] ?? '') !== $mime
            || (int) ($imageInfo[0] ?? 0) < 1 || (int) ($imageInfo[1] ?? 0) < 1
            || (int) ($imageInfo[0] ?? 0) > 6000 || (int) ($imageInfo[1] ?? 0) > 6000) {
            $this->avatarUploadFailure($websiteId, 'Use a valid JPEG, PNG, or WebP image up to 6000 × 6000 pixels.');
            return;
        }

        $relativeDirectory = 'uploads/portfolios/' . $websiteId;
        $targetDirectory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            $this->avatarUploadFailure($websiteId, 'The profile image directory could not be created.');
            return;
        }

        $filename = bin2hex(random_bytes(16)) . '.webp';
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
        if (!$this->convertAvatarToWebp($tmpPath, $mime, $targetPath)) {
            $this->avatarUploadFailure($websiteId, 'This server could not convert the image to WebP. Please try another image.');
            return;
        }

        if (!is_file($targetPath) || filesize($targetPath) === 0) {
            @unlink($targetPath);
            $this->avatarUploadFailure($websiteId, 'The converted profile image could not be saved.');
            return;
        }

        // Generate square PNG favicon variants from the uploaded source when GD supports it.
        // The portfolio remains usable on hosts without GD; the public page then falls back to its WebP avatar.
        $this->generateAvatarFaviconPngs($tmpPath, $mime, $targetDirectory, pathinfo($filename, PATHINFO_FILENAME));

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
                $previousStem = pathinfo($previousAbsolutePath, PATHINFO_FILENAME);
                foreach ([32, 180, 192] as $iconSize) {
                    $previousIcon = $uploadDirectory . DIRECTORY_SEPARATOR . $previousStem . '-favicon-' . $iconSize . '.png';
                    if (is_file($previousIcon)) {
                        @unlink($previousIcon);
                    }
                }
            }
        }

        if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            echo json_encode(['ok' => true, 'message' => 'Profile photo saved.', 'avatar_url' => rtrim((string) ($this->config['base_url'] ?? ''), '/') . '/' . $relativePath]);
            return;
        }

        Session::flash('success', 'Profile image uploaded.');
        $this->redirectTo($this->editorPath($websiteId));
    }

    /**
     * Convert uploaded raster images to WebP. Imagick uses lossless WebP when
     * available; GD fallback uses quality 92 (high visual quality, not lossless).
     */
    private function convertAvatarToWebp(string $sourcePath, string $mime, string $targetPath): bool
    {
        if (class_exists(\Imagick::class)) {
            try {
                $image = new \Imagick($sourcePath);
                if (method_exists($image, 'autoOrient')) {
                    $image->autoOrient();
                } elseif (method_exists($image, 'autoOrientImage')) {
                    $image->autoOrientImage();
                }
                $image->setImageFormat('webp');
                $image->setOption('webp:lossless', 'true');
                $image->setImageCompressionQuality(100);
                $image->stripImage();
                $saved = $image->writeImage($targetPath);
                $image->clear();
                $image->destroy();
                if ($saved && is_file($targetPath) && filesize($targetPath) > 0) {
                    return true;
                }
                @unlink($targetPath);
            } catch (\Throwable $exception) {
                @unlink($targetPath);
            }
        }

        if (!function_exists('imagewebp')) {
            return false;
        }

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };
        if ($image === false) {
            return false;
        }

        if ($mime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $saved = @imagewebp($image, $targetPath, 92);
        imagedestroy($image);

        if (!$saved || !is_file($targetPath) || filesize($targetPath) === 0) {
            @unlink($targetPath);
            return false;
        }

        return true;
    }

    /**
     * Create centered square PNG icons for browser tabs and mobile home-screen shortcuts.
     * Existing files are replaced only after a new upload has been validated and converted.
     */
    private function generateAvatarFaviconPngs(string $sourcePath, string $mime, string $targetDirectory, string $avatarStem): void
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')
            || !function_exists('imagepng')) {
            return;
        }

        $source = match ($mime) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($sourcePath) : false,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };
        if ($source === false) {
            return;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $sourceX = (int) floor(($width - $side) / 2);
        $sourceY = (int) floor(($height - $side) / 2);

        foreach ([32, 180, 192] as $size) {
            $icon = imagecreatetruecolor($size, $size);
            if ($icon === false) {
                continue;
            }

            imagealphablending($icon, false);
            imagesavealpha($icon, true);
            $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
            imagefill($icon, 0, 0, $transparent);

            if (imagecopyresampled($icon, $source, 0, 0, $sourceX, $sourceY, $size, $size, $side, $side)) {
                $path = $targetDirectory . DIRECTORY_SEPARATOR . $avatarStem . '-favicon-' . $size . '.png';
                @imagepng($icon, $path, 9);
            }
            imagedestroy($icon);
        }

        imagedestroy($source);
    }

    /** Return upload errors as JSON for the setup page's asynchronous uploader. */
    private function avatarUploadFailure(int $websiteId, string $message): void
    {
        if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $message]);
            return;
        }

        Session::flash('error', $message);
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

    /** Active templates are filtered by plan; the current layout remains available so existing work is not broken. */
    private function availableTemplates(string $currentTemplate = '', int $userId = 0): array
    {
        $catalog = new PortfolioTemplate();
        $isPremiumUser = $userId > 0 && (new Website())->userPlan($userId) === 'premium';
        $templates = [];
        foreach ($catalog->all() as $template) {
            $key = (string) ($template['template_key'] ?? '');
            if ($key === '' || (($template['status'] ?? '') !== 'active' && $key !== $currentTemplate)) {
                continue;
            }
            $label = (string) ($template['display_name'] ?? $key);
            if (($template['status'] ?? '') !== 'active') {
                $label .= ' (currently suspended)';
            }
            $templates[$key] = $label;
        }
        return $templates;
    }

    private function canUseTemplate(string $templateKey, string $currentTemplate, int $userId): bool
    {
        if ($templateKey === $currentTemplate) {
            return array_key_exists($templateKey, $this->availableTemplates($currentTemplate, $userId));
        }
        $isPremiumUser = (new Website())->userPlan($userId) === 'premium';
        return array_key_exists($templateKey, $this->availableTemplates($currentTemplate, $userId))
            && ($isPremiumUser || !(new PortfolioTemplate())->isPremiumTemplate($templateKey));
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
