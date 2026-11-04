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
