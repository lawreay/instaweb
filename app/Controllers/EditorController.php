<?php

namespace App\\Controllers;

use App\\Core\\Controller;
use App\\Core\\Csrf;
use App\\Core\\Session;
use App\\Models\\Website;
use App\\Services\\AuthService;
use App\\Services\\ProjectStorageService;
use App\\Services\\PublicSiteUrlService;

/**
 * Handles browser-based editing for safe static text files.
 */
class EditorController extends Controller
{
    public function edit(): void
    {
        [$userId, $website] = $this->requireWebsite();
        $storage = new ProjectStorageService();

        try {
            $path = (string) $this->input('path', 'index.html');
            $file = $storage->readTextFile($userId, $website['slug'], $path);
        } catch (\\Throwable $exception) {
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
            'title' => 'InstaWeb Studio',
            'website' => $website,
            'file' => $file,
            'files' => $storage->listProjectFiles($userId, $website['slug']),
            'liveUrl' => $liveUrl,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Fetch a project text file as JSON so the explorer can switch files
     * without a full dashboard reload.
     */
    public function openFile(): void
    {
        [$userId, $website] = $this->requireWebsite();
        try {
            $path = (string) $this->input('path', 'index.html');
            $file = (new ProjectStorageService())->readTextFile($userId, $website['slug'], $path);
            $this->respondJson([
                'ok' => true,
                'path' => $file['relative_path'],
                'content' => $file['content'],
                'extension' => $file['extension'],
            ]);
        } catch (\\Throwable $exception) {
            $this->respondJson(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    /**
     * Save an editor buffer. AJAX requests receive JSON; regular form posts
     * retain the existing redirect-based fallback.
     */
    public function save(): void
    {
        $isAjax = str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json')
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        [$userId, $website] = $this->requireWebsiteFromPost();
        $fallback = '/dashboard/websites/files?id=' . (int) $website['id'];

        if (!Csrf::validate($this->input('_csrf'))) {
            $this->respondSaveError($isAjax, 'Your session expired. Refresh the page and try again.', 419, $fallback);
        }

        $path = (string) $this->input('path', '');
        $content = (string) $this->input('content', '');

        // Keep accidental giant uploads from turning autosave into a denial of service.
        if (strlen($content) > 2 * 1024 * 1024) {
            $this->respondSaveError($isAjax, 'This editor currently supports files up to 2 MB.', 413, $fallback);
        }

        try {
            $storage = new ProjectStorageService();
            $safePath = $storage->normalizeRelativePath($path);
            $size = $storage->writeTextFile($userId, $website['slug'], $safePath, $content);

            $websiteModel = new Website();
            $websiteModel->replaceFileRecord(
                (int) $website['id'],
                basename($safePath),
                $safePath,
                strtolower(pathinfo($safePath, PATHINFO_EXTENSION)),
                $size
            );
            $usage = $storage->directorySize($storage->projectPath($userId, $website['slug']));
            $websiteModel->updateStorageUsed((int) $website['id'], $usage);

            if ($isAjax) {
                $this->respondJson(['ok' => true, 'message' => 'Saved', 'path' => $safePath, 'size' => $size]);
            }

            Session::flash('success', 'File saved.');
            $this->redirectTo('/dashboard/websites/editor?id=' . (int) $website['id'] . '&path=' . rawurlencode($safePath));
        } catch (\\Throwable $exception) {
            $this->respondSaveError($isAjax, $exception->getMessage(), 422, $fallback);
        }
    }

    private function respondSaveError(bool $isAjax, string $message, int $status, string $fallback): void
    {
        if ($isAjax) {
            $this->respondJson(['ok' => false, 'message' => $message], $status);
        }

        Session::flash('error', $message);
        $this->redirectTo($fallback);
    }

    private function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

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
