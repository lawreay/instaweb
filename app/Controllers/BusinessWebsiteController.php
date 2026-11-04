<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\BusinessWebsiteContent;
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
            $themeService->writeDraft($userId, (string) $website['slug'], $content, $themeService->current($id, $userId));
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
        echo (new BusinessWebsiteThemeService())->render($content, $theme);
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
            $themeService->writeDraft($userId, (string) $website['slug'], $content, $theme);
            Session::flash('success', 'Theme applied to your draft. Publish when you are ready to make it live.');
        } catch (\Throwable $exception) {
            Session::flash('error', 'The theme could not be applied. Confirm that the Business theme migration has been applied.');
        }
        $this->redirectTo('/dashboard/websites/business/themes?id=' . $id);
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
