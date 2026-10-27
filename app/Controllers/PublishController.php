<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\BackupService;
use App\Services\NotificationManager;
use App\Services\PublishingService;
use App\Services\SitemapGenerator;

/**
 * Handles publishing and unpublishing static websites.
 */
class PublishController extends Controller
{
    /**
     * Publish a website project.
     */
    public function publish(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/show?id=' . (int) $website['id']);

        if (($website['status'] ?? '') === 'suspended') {
            Session::flash('error', 'This website is suspended by an administrator and cannot be published. Contact support if you believe this is a mistake.');
            $this->redirectTo('/dashboard/websites/show?id=' . (int) $website['id']);
            return;
        }

        try {
            $websiteModel = new Website();

            (new BackupService())->backupDraft($userId, $website['slug']);
            $publishingService = new PublishingService();
            if (($website['template_key'] ?? '') === 'portfolio') {
                $publishingService->publishPortfolio((int) $website['id'], (string) $website['slug']);
            } else {
                $publishingService->publish($userId, (int) $website['id'], $website['slug']);
            }
            $websiteModel->updateStatus((int) $website['id'], $userId, 'published');
            try {
                (new SitemapGenerator($this->config))->writeToFile();
            } catch (\Throwable $exception) {
                // Sitemap regeneration should not block publish success.
            }
            (new NotificationManager($this->config))->sendUserNotification(
                $userId,
                'Website published',
                sprintf('Your website "%s" is now live.', $website['website_name']),
                [
                    'category' => 'website',
                    'icon' => 'globe',
                    'target_url' => '/dashboard/websites/show?id=' . (int) $website['id'],
                ]
            );

            Session::flash('success', 'Website published.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectTo('/dashboard/websites/show?id=' . (int) $website['id']);
    }

    /**
     * Remove a website from the public site folder.
     */
    public function unpublish(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/show?id=' . (int) $website['id']);

        if (($website['status'] ?? '') === 'suspended') {
            Session::flash('error', 'This website is suspended by an administrator. Its status cannot be changed by the site owner.');
            $this->redirectTo('/dashboard/websites/show?id=' . (int) $website['id']);
            return;
        }

        if ((new Website())->isSystemTemplate($website)) {
            Session::flash('error', 'This protected default portfolio stays published for template previews.');
            $this->redirectTo('/dashboard/websites/show?id=' . (int) $website['id']);
            return;
        }

        try {
            $websiteModel = new Website();
            $publishingService = new PublishingService();

            if (($website['template_key'] ?? '') === 'portfolio') {
                // Disable the dynamic /p/{slug} route first. If filesystem cleanup
                // fails, the public controller still rejects the now-draft site.
                $websiteModel->updateStatus((int) $website['id'], $userId, 'draft');
                $publishingService->unpublish((int) $website['id'], $website['slug']);
            } else {
                // For static sites, remove public files before changing status so
                // a cleanup failure does not leave a directly served public copy.
                $publishingService->unpublish((int) $website['id'], $website['slug']);
                $websiteModel->updateStatus((int) $website['id'], $userId, 'draft');
            }
            try {
                (new SitemapGenerator($this->config))->writeToFile();
            } catch (\Throwable $exception) {
                // Sitemap regeneration should not block unpublish success.
            }
            (new NotificationManager($this->config))->sendUserNotification(
                $userId,
                'Website unpublished',
                sprintf('Your website "%s" is no longer live.', $website['website_name']),
                [
                    'category' => 'website',
                    'icon' => 'globe-2',
                    'target_url' => '/dashboard/websites/show?id=' . (int) $website['id'],
                ]
            );

            Session::flash('success', 'Website unpublished.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectTo('/dashboard/websites/show?id=' . (int) $website['id']);
    }

    /**
     * Require a project from a POST request.
     */
    private function requireWebsiteFromPost(): array
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        $userId = (int) AuthService::id();
        $website = (new Website())->findForUser((int) $this->input('website_id', 0), $userId);

        if (!$website) {
            Session::flash('error', 'Website project not found.');
            $this->redirectTo('/dashboard/websites');
        }

        return [$userId, $website];
    }

    /**
     * Require CSRF for publish actions.
     */
    private function requireCsrf(string $fallback): void
    {
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($fallback);
        }
    }
}
