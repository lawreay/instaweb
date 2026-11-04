<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\BusinessWebsiteContent;
use App\Models\BusinessWebsiteBranding;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\BusinessWebsiteService;
use App\Services\BusinessWebsiteThemeService;
use App\Services\ProjectStorageService;

class BusinessWebsiteController extends Controller
{
    public function edit(): void
    {
        [$userId, $website] = $this->owned();
        $service = new BusinessWebsiteService();
        $themes = new BusinessWebsiteThemeService();
        $content = (new BusinessWebsiteContent())->findForOwner((int) $website['id'], $userId)
            ?? $service->defaults((string) $website['website_name']);
        $this->view('websites/business-edit', [
            'title' => 'Customize Business Website',
            'website' => $website,
            'content' => $content,
            'currentTheme' => $themes->current((int) $website['id'], $userId),
            'branding' => (new BusinessWebsiteBranding())->findForOwner((int) $website['id'], $userId),
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function save(): void
    {
        [$userId, $website] = $this->owned();
        $id = (int) $website['id'];
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/business?id=' . $id);
        }
        try {
            $service = new BusinessWebsiteService();
            $content = $service->validate($this->request(), (string) $website['website_name']);
            if ($content['hero_image_path'] !== '') {
                try {
                    (new ProjectStorageService())->downloadableFile($userId, (string) $website['slug'], $content['hero_image_path']);
                } catch (\Throwable) {
                    throw new \InvalidArgumentException('The selected hero image is no longer available. Choose it again from the media library.');
                }
            }
            (new BusinessWebsiteContent())->saveForOwner($id, $userId, $content);
            (new Website())->updateProject($id, $userId, $content['business_name']);
            $themeService = new BusinessWebsiteThemeService();
            $themeService->writeDraft($userId, (string) $website['slug'], $content, $themeService->current($id, $userId), (new BusinessWebsiteBranding())->findForOwner($id, $userId));
            $storage = new ProjectStorageService();
            (new Website())->updateStorageUsed($id, $storage->directorySize($storage->projectPath($userId, (string) $website['slug'])));
            Session::flash('success', 'Business website content saved. Preview your changes before publishing.');
        } catch (\InvalidArgumentException $exception) {
            Session::flash('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            Session::flash('error', 'The business website could not be updated. Confirm that the required website migrations have been applied.');
        }
        $this->redirectTo('/dashboard/websites/business?id=' . $id);
    }

    public function themes(): void
    {
        [$userId, $website] = $this->owned();
        $themeService = new BusinessWebsiteThemeService();
        $this->view('websites/business-themes', [
            'title' => 'Choose a Website Theme',
            'website' => $website,
            'themes' => $themeService->all(),
            'currentTheme' => $themeService->current((int) $website['id'], $userId),
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function themePreview(): void
    {
        [$userId, $website] = $this->owned();
        $content = (new BusinessWebsiteContent())->findForOwner((int) $website['id'], $userId)
            ?? (new BusinessWebsiteService())->defaults((string) $website['website_name']);
        $theme = (new BusinessWebsiteThemeService())->normalize((string) $this->input('theme', 'evergreen'));
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        $branding = (new BusinessWebsiteBranding())->findForOwner((int) $website['id'], $userId);
        $assetRoute = rtrim((string) ($this->config['base_url'] ?? ''), '/') . '/dashboard/websites/preview/file?id=' . (int) $website['id'] . '&path=';
        $heroImagePath = (string) ($content['hero_image_path'] ?? '');
        if ($heroImagePath !== '') {
            $content['hero_image_url'] = $assetRoute . rawurlencode($heroImagePath);
        }
        if (!empty($branding['logo_path'])) {
            $branding['logo_url'] = $assetRoute . rawurlencode((string) $branding['logo_path']);
        }
        if (!empty($branding['favicon_path'])) {
            $branding['favicon_url'] = $assetRoute . rawurlencode((string) $branding['favicon_path']);
        }
        echo (new BusinessWebsiteThemeService())->render($content, $theme, $branding);
    }

    public function applyTheme(): void
    {
        [$userId, $website] = $this->owned();
        $id = (int) $website['id'];
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/business/themes?id=' . $id);
        }
        try {
            $themeService = new BusinessWebsiteThemeService();
            $theme = $themeService->normalize((string) $this->input('theme_key', ''));
            $themeService->save($id, $userId, $theme);
            $content = (new BusinessWebsiteContent())->findForOwner($id, $userId)
                ?? (new BusinessWebsiteService())->defaults((string) $website['website_name']);
            $themeService->writeDraft($userId, (string) $website['slug'], $content, $theme, (new BusinessWebsiteBranding())->findForOwner($id, $userId));
            Session::flash('success', 'Theme applied to your draft. Publish when you are ready to make it live.');
        } catch (\Throwable $exception) {
            Session::flash('error', 'The theme could not be applied. Confirm that the Business theme migration has been applied.');
        }
        $this->redirectTo('/dashboard/websites/business/themes?id=' . $id);
    }

    public function branding(): void
    {
        [$userId, $website] = $this->owned();
        $this->view('websites/business-branding', [
            'title' => 'Business Website Branding',
            'website' => $website,
            'branding' => (new BusinessWebsiteBranding())->findForOwner((int) $website['id'], $userId),
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function saveBranding(): void
    {
        [$userId, $website] = $this->owned();
        $id = (int) $website['id'];
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/business/branding?id=' . $id);
        }

        $model = new BusinessWebsiteBranding();
        $current = $model->findForOwner($id, $userId);
        $storage = new ProjectStorageService();
        $directory = $storage->ensureProjectDirectory($userId, (string) $website['slug'])
            . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'branding';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            Session::flash('error', 'The branding upload directory could not be created.');
            $this->redirectTo('/dashboard/websites/business/branding?id=' . $id);
        }

        $saved = $current;
        foreach (['logo' => 'logo_path', 'favicon' => 'favicon_path'] as $field => $column) {
            $file = $_FILES[$field] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
                || (int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 3 * 1024 * 1024) {
                Session::flash('error', 'Each branding image must be a valid upload no larger than 3 MB.');
                $this->redirectTo('/dashboard/websites/business/branding?id=' . $id);
            }
            $tmp = (string) $file['tmp_name'];
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            $info = @getimagesize($tmp);
            $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!is_string($mime) || !isset($types[$mime]) || $info === false
                || ($info['mime'] ?? '') !== $mime || (int) $info[0] < 1 || (int) $info[1] < 1
                || (int) $info[0] > 6000 || (int) $info[1] > 6000) {
                Session::flash('error', 'Use a valid JPEG, PNG, or WebP image up to 6000 × 6000 pixels.');
                $this->redirectTo('/dashboard/websites/business/branding?id=' . $id);
            }
            if ($field === 'favicon' && max((int) $info[0], (int) $info[1]) > 1024) {
                Session::flash('error', 'Favicon images must be no larger than 1024 × 1024 pixels.');
                $this->redirectTo('/dashboard/websites/business/branding?id=' . $id);
            }
            $filename = $field . '-' . bin2hex(random_bytes(12)) . '.' . $types[$mime];
            $target = $directory . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($tmp, $target)) {
                Session::flash('error', 'The branding image could not be saved.');
                $this->redirectTo('/dashboard/websites/business/branding?id=' . $id);
            }
            $saved[$column] = 'assets/branding/' . $filename;
            $old = (string) ($current[$column] ?? '');
            if ($old !== '' && str_starts_with($old, 'assets/branding/')) {
                $oldPath = $storage->resolveProjectPath($userId, (string) $website['slug'], $old);
                if (is_file($oldPath)) { @unlink($oldPath); }
            }
        }

        $model->saveForOwner($id, $userId, $saved);
        (new BusinessWebsiteThemeService())->writeDraft(
            $userId,
            (string) $website['slug'],
            (new BusinessWebsiteContent())->findForOwner($id, $userId)
                ?? (new BusinessWebsiteService())->defaults((string) $website['website_name']),
            (new BusinessWebsiteThemeService())->current($id, $userId),
            $saved
        );
        (new Website())->updateStorageUsed($id, $storage->directorySize($storage->projectPath($userId, (string) $website['slug'])));
        Session::flash('success', 'Branding saved to your private draft. Publish to update the live website.');
        $this->redirectTo('/dashboard/websites/business/branding?id=' . $id);
    }

    public function media(): void
    {
        [$userId, $website] = $this->owned();
        $storage = new ProjectStorageService();
        $directory = $storage->resolveProjectPath($userId, (string) $website['slug'], 'assets/media');
        $items = [];
        if (is_dir($directory)) {
            foreach (scandir($directory) ?: [] as $filename) {
                if (!preg_match('/^media-[a-f0-9]{24}\\.(?:jpg|png|webp)$/', $filename)) continue;
                $path = $directory . DIRECTORY_SEPARATOR . $filename;
                if (!is_file($path)) continue;
                $items[] = [
                    'filename' => $filename,
                    'path' => 'assets/media/' . $filename,
                    'size' => (int) filesize($path),
                    'url' => rtrim((string) ($this->config['base_url'] ?? ''), '/') . '/dashboard/websites/preview/file?id=' . (int) $website['id'] . '&path=' . rawurlencode('assets/media/' . $filename),
                ];
            }
        }
        usort($items, static fn (array $a, array $b): int => strcmp($b['filename'], $a['filename']));
        $this->view('websites/business-media', [
            'title' => 'Business Media Library',
            'website' => $website,
            'items' => $items,
            'selectTarget' => preg_match('/^[a-z_]{1,40}$/', (string) $this->input('select', '')) ? (string) $this->input('select', '') : '',
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function uploadMedia(): void
    {
        [$userId, $website] = $this->owned();
        $id = (int) $website['id'];
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $file = $_FILES['media_file'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
            || (int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 8 * 1024 * 1024) {
            Session::flash('error', 'Choose an image no larger than 8 MB.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $tmp = (string) $file['tmp_name'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $info = @getimagesize($tmp);
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!is_string($mime) || !isset($types[$mime]) || $info === false
            || ($info['mime'] ?? '') !== $mime || (int) $info[0] < 1 || (int) $info[1] < 1
            || (int) $info[0] > 8000 || (int) $info[1] > 8000) {
            Session::flash('error', 'Use a valid JPEG, PNG or WebP image up to 8000 × 8000 pixels.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $storage = new ProjectStorageService();
        $directory = $storage->ensureProjectDirectory($userId, (string) $website['slug'])
            . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'media';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            Session::flash('error', 'The media directory could not be created.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $filename = 'media-' . bin2hex(random_bytes(12)) . '.' . $types[$mime];
        if (!move_uploaded_file($tmp, $directory . DIRECTORY_SEPARATOR . $filename)) {
            Session::flash('error', 'The image could not be saved.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        (new Website())->updateStorageUsed($id, $storage->directorySize($storage->projectPath($userId, (string) $website['slug'])));
        Session::flash('success', 'Image added to your media library.');
        $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
    }

    public function deleteMedia(): void
    {
        [$userId, $website] = $this->owned();
        $id = (int) $website['id'];
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $filename = (string) $this->input('filename', '');
        if (!preg_match('/^media-[a-f0-9]{24}\\.(?:jpg|png|webp)$/', $filename)) {
            Session::flash('error', 'Invalid media item.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $content = (new BusinessWebsiteContent())->findForOwner($id, $userId) ?? [];
        if (($content['hero_image_path'] ?? '') === 'assets/media/' . $filename) {
            Session::flash('error', 'This image is currently used by your hero section. Select another image before deleting it.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $storage = new ProjectStorageService();
        $relativePath = 'assets/media/' . $filename;
        $draftIndex = $storage->projectPath($userId, (string) $website['slug']) . DIRECTORY_SEPARATOR . 'index.html';
        $publishedIndex = (new \App\Services\PublishingService())->publicSitePath($id, (string) $website['slug']) . DIRECTORY_SEPARATOR . 'index.html';
        if ($this->htmlReferencesMediaFile($draftIndex, $relativePath)
            || $this->htmlReferencesMediaFile($publishedIndex, $relativePath)) {
            Session::flash('error', 'This image is still referenced by your draft or published website. Publish the updated website before deleting it.');
            $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
        }
        $path = $storage->resolveProjectPath($userId, (string) $website['slug'], $relativePath);
        if (!is_file($path) || !unlink($path)) {
            Session::flash('error', 'The media item could not be deleted.');
        } else {
            (new Website())->updateStorageUsed($id, $storage->directorySize($storage->projectPath($userId, (string) $website['slug'])));
            Session::flash('success', 'Media item deleted.');
        }
        $this->redirectTo('/dashboard/websites/business/media?id=' . $id);
    }

    private function htmlReferencesMediaFile(string $htmlPath, string $relativePath): bool
    {
        if (!is_file($htmlPath)) {
            return false;
        }

        $html = file_get_contents($htmlPath);
        return is_string($html) && str_contains($html, $relativePath);
    }

    private function owned(): array
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }
        $userId = (int) AuthService::id();
        $website = (new Website())->findForUser((int) $this->input('id', 0), $userId);
        if (!$website || strtolower((string) ($website['template_key'] ?? '')) !== 'business') {
            Session::flash('error', 'Business website not found.');
            $this->redirectTo('/dashboard/websites');
        }
        return [$userId, $website];
    }
}
