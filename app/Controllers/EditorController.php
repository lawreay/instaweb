<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\ProjectStorageService;
use App\Services\PublicSiteUrlService;

/**
 * Handles browser-based editing for safe static text files.
 */
class EditorController extends Controller
{
    /**
     * Show the editor for one project file.
     */
    public function edit(): void
    {
        [$userId, $website] = $this->requireWebsite();

        try {
            $path = (string) $this->input('path', 'index.html');
            $file = (new ProjectStorageService())->readTextFile($userId, $website['slug'], $path);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirectTo('/dashboard/websites/files?id=' . (int) $website['id']);
        }

        $liveUrl = ($website['status'] ?? '') === 'published'
            ? (new PublicSiteUrlService())->absoluteUrl(
                (string) ($this->config['base_url'] ?? ''),
                (int) $website['id'],
                (string) $website['slug']
            )
            : '';

        $this->view('editor/edit', [
            'title' => 'Code Editor',
            'website' => $website,
            'file' => $file,
            'liveUrl' => $liveUrl,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Save text file changes.
     */
    public function save(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $fallback = '/dashboard/websites/files?id=' . (int) $website['id'];

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($fallback);
        }

        $path = (string) $this->input('path', '');

        try {
            $storage = new ProjectStorageService();
            $safePath = $storage->normalizeRelativePath($path);
            $size = $storage->writeTextFile($userId, $website['slug'], $safePath, (string) $this->input('content', ''));

            $websiteModel = new Website();
            $websiteModel->replaceFileRecord(
                (int) $website['id'],
                basename($safePath),
                $safePath,
                strtolower(pathinfo($safePath, PATHINFO_EXTENSION)),
                $size
            );
            $websiteModel->updateStorageUsed((int) $website['id'], $storage->directorySize($storage->projectPath($userId, $website['slug'])));
            Session::flash('success', 'File saved.');
            $this->redirectTo('/dashboard/websites/editor?id=' . (int) $website['id'] . '&path=' . rawurlencode($safePath));
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirectTo($fallback);
        }
    }

    /**
     * Require a project from a GET request.
     */
    private function requireWebsite(): array
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        $userId = (int) AuthService::id();
        $website = (new Website())->findForUser((int) $this->input('id', 0), $userId);

        if (!$website) {
            Session::flash('error', 'Website project not found.');
            $this->redirectTo('/dashboard/websites');
        }

        return [$userId, $website];
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
}
