<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\NotificationManager;
use App\Services\NotificationService;
use App\Services\PlanService;
use App\Services\PublicSiteUrlService;
use App\Services\ProjectArchiveService;
use App\Services\ProjectStorageService;
use App\Services\UploadValidationService;

/**
 * Handles draft website file management for authenticated users.
 */
class FileManagerController extends Controller
{
    /**
     * Show draft files for one website project.
     */
    public function index(): void
    {
        [$userId, $website] = $this->requireWebsite();
        $storage = new ProjectStorageService();
        $website = (new PublicSiteUrlService())->withPublicUrl($website, (string) ($this->config['base_url'] ?? ''));

        $this->view('filemanager/index', [
            'title' => 'File Manager',
            'website' => $website,
            'files' => $storage->listProjectFiles($userId, $website['slug']),
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Upload a file into draft storage.
     */
    public function upload(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/files?id=' . (int) $website['id']);

        try {
            if (empty($_FILES['project_file'])) {
                throw new \InvalidArgumentException('Choose a file to upload.');
            }

            $validator = new UploadValidationService();
            $planService = new PlanService();
            $safeName = $validator->validate($_FILES['project_file']);
            $folder = (new ProjectStorageService())->normalizeRelativePath((string) $this->input('folder', ''));
            $relativePath = trim($folder . '/' . $safeName, '/');

            $websiteModel = new Website();
            $storage = new ProjectStorageService();
            $targetPath = $storage->resolveProjectPath($userId, $website['slug'], $relativePath);
            $projectPath = $storage->projectPath($userId, $website['slug']);

            if (is_file($targetPath) || is_dir($targetPath)) {
                throw new \InvalidArgumentException('A file with that name already exists.');
            }

            $plan = $websiteModel->userPlan($userId);
            $storageLimit = $plan === 'premium' ? $planService->premiumStorageBytes() : $planService->freeStorageBytes();

            if ($storageLimit > 0 && ($websiteModel->totalStorageForUser($userId) + (int) $_FILES['project_file']['size']) > $storageLimit) {
                throw new \InvalidArgumentException(sprintf('This upload exceeds the plan storage limit of %dMB.', (int) ($storageLimit / BYTES_PER_MB)));
            }

            $parent = dirname($targetPath);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new \RuntimeException('Unable to create upload folder.');
            }

            if (!move_uploaded_file($_FILES['project_file']['tmp_name'], $targetPath)) {
                throw new \RuntimeException('Unable to save uploaded file.');
            }

            $size = filesize($targetPath) ?: (int) $_FILES['project_file']['size'];
            $websiteModel->replaceFileRecord((int) $website['id'], $safeName, $relativePath, strtolower(pathinfo($safeName, PATHINFO_EXTENSION)), $size);
            $newUsage = $storage->directorySize($projectPath);
            $websiteModel->updateStorageUsed((int) $website['id'], $newUsage);
            $notificationManager = new NotificationManager($this->config);
            $notificationManager->sendUserNotification(
                $userId,
                'File uploaded',
                sprintf('Your file "%s" was added to "%s".', $safeName, $website['website_name']),
                [
                    'category' => 'uploads',
                    'icon' => 'upload-cloud',
                    'target_url' => '/dashboard/websites/files?id=' . (int) $website['id'],
                ]
            );
            $limitBytes = $plan === 'premium' ? $planService->premiumStorageBytes() : $planService->freeStorageBytes();
            (new NotificationService($this->config))->sendStorageThresholdNotifications(
                $userId,
                (int) $website['storage_used'],
                $newUsage,
                $limitBytes > 0 ? $limitBytes : null
            );
            Session::flash('success', 'File uploaded.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectAfterFileAction($website);
    }

    /**
     * Create a text file in draft storage.
     */
    public function createFile(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/files?id=' . (int) $website['id']);

        try {
            $relativePath = (string) $this->input('file_path', '');
            (new UploadValidationService())->validateCreatedFileName(basename($relativePath));

            $storage = new ProjectStorageService();
            $safeRelativePath = $storage->normalizeRelativePath($relativePath);
            $size = $storage->createTextFile($userId, $website['slug'], $safeRelativePath);
            $projectPath = $storage->projectPath($userId, $website['slug']);

            $websiteModel = new Website();
            $planService = new PlanService();
            $websiteModel->replaceFileRecord((int) $website['id'], basename($safeRelativePath), $safeRelativePath, strtolower(pathinfo($safeRelativePath, PATHINFO_EXTENSION)), $size);
            $newUsage = $storage->directorySize($projectPath);
            $websiteModel->updateStorageUsed((int) $website['id'], $newUsage);
            $notificationManager = new NotificationManager($this->config);
            $notificationManager->sendUserNotification(
                $userId,
                'File created',
                sprintf('A new file "%s" was created in "%s".', basename($safeRelativePath), $website['website_name']),
                [
                    'category' => 'uploads',
                    'icon' => 'file-text',
                    'target_url' => '/dashboard/websites/files?id=' . (int) $website['id'],
                ]
            );
            $limitBytes = $websiteModel->userPlan($userId) === 'premium' ? $planService->premiumStorageBytes() : $planService->freeStorageBytes();
            (new NotificationService($this->config))->sendStorageThresholdNotifications(
                $userId,
                (int) $website['storage_used'],
                $newUsage,
                $limitBytes > 0 ? $limitBytes : null
            );
            Session::flash('success', 'File created.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectAfterFileAction($website);
    }

    /**
     * Create a folder in draft storage.
     */
    public function createFolder(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/files?id=' . (int) $website['id']);

        try {
            (new ProjectStorageService())->createFolder($userId, $website['slug'], (string) $this->input('folder_path', ''));
            Session::flash('success', 'Folder created.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectAfterFileAction($website);
    }

    /**
     * Copy a draft file or folder and update storage accounting.
     */
    public function copy(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/files?id=' . (int) $website['id']);

        try {
            $storage = new ProjectStorageService();
            $from = $storage->normalizeRelativePath((string) $this->input('from_path', ''));
            $to = $storage->normalizeRelativePath((string) $this->input('to_path', ''));
            $items = $storage->listProjectFiles($userId, $website['slug']);
            $copyBytes = 0;
            foreach ($items as $item) {
                if (($item['type'] ?? '') !== 'file') {
                    continue;
                }
                $itemPath = (string) $item['path'];
                if ($itemPath === $from || str_starts_with($itemPath, rtrim($from, '/') . '/')) {
                    $copyBytes += (int) ($item['size'] ?? 0);
                }
            }

            $websiteModel = new Website();
            $planService = new PlanService();
            $limit = $websiteModel->userPlan($userId) === 'premium' ? $planService->premiumStorageBytes() : $planService->freeStorageBytes();
            if ($limit > 0 && $websiteModel->totalStorageForUser($userId) + $copyBytes > $limit) {
                throw new \InvalidArgumentException('Copying this item would exceed your plan storage limit.');
            }

            $storage->copyProjectItem($userId, $website['slug'], $from, $to);
            $websiteModel->syncFileRecords((int) $website['id'], $storage->listProjectFiles($userId, $website['slug']));
            $newUsage = $storage->directorySize($storage->projectPath($userId, $website['slug']));
            $websiteModel->updateStorageUsed((int) $website['id'], $newUsage);
            Session::flash('success', 'Item copied.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectAfterFileAction($website);
    }

    /**
     * Delete one draft file or folder.
     */
    public function delete(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/files?id=' . (int) $website['id']);

        try {
            $storage = new ProjectStorageService();
            $relativePath = $storage->normalizeRelativePath((string) $this->input('path', ''));
            $storage->deleteProjectItem($userId, $website['slug'], $relativePath);

            $websiteModel = new Website();
            $planService = new PlanService();
            $websiteModel->deleteFileRecordsUnderPath((int) $website['id'], $relativePath);
            $newUsage = $storage->directorySize($storage->projectPath($userId, $website['slug']));
            $websiteModel->updateStorageUsed((int) $website['id'], $newUsage);
            $notificationManager = new NotificationManager($this->config);
            $notificationManager->sendUserNotification(
                $userId,
                'Draft item deleted',
                sprintf('A draft item was removed from "%s".', $website['website_name']),
                [
                    'category' => 'uploads',
                    'icon' => 'trash-2',
                    'target_url' => '/dashboard/websites/files?id=' . (int) $website['id'],
                ]
            );
            $limitBytes = $websiteModel->userPlan($userId) === 'premium' ? $planService->premiumStorageBytes() : $planService->freeStorageBytes();
            (new NotificationService($this->config))->sendStorageThresholdNotifications(
                $userId,
                (int) $website['storage_used'],
                $newUsage,
                $limitBytes > 0 ? $limitBytes : null
            );
            Session::flash('success', 'Item deleted.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectAfterFileAction($website);
    }

    /**
     * Move or rename one draft file or folder.
     */
    public function move(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/files?id=' . (int) $website['id']);

        try {
            $storage = new ProjectStorageService();
            $result = $storage->moveProjectItem(
                $userId,
                $website['slug'],
                (string) $this->input('from_path', ''),
                (string) $this->input('to_path', '')
            );

            $websiteModel = new Website();
            $planService = new PlanService();
            $websiteModel->moveFileRecords(
                (int) $website['id'],
                $result['from'],
                $result['to'],
                $result['type'],
                (int) $result['size']
            );
            $newUsage = $storage->directorySize($storage->projectPath($userId, $website['slug']));
            $websiteModel->updateStorageUsed((int) $website['id'], $newUsage);
            $notificationManager = new NotificationManager($this->config);
            $notificationManager->sendUserNotification(
                $userId,
                'Draft item moved',
                sprintf('A draft item was moved in "%s".', $website['website_name']),
                [
                    'category' => 'uploads',
                    'icon' => 'arrows-right-left',
                    'target_url' => '/dashboard/websites/files?id=' . (int) $website['id'],
                ]
            );
            $limitBytes = $websiteModel->userPlan($userId) === 'premium' ? $planService->premiumStorageBytes() : $planService->freeStorageBytes();
            (new NotificationService($this->config))->sendStorageThresholdNotifications(
                $userId,
                (int) $website['storage_used'],
                $newUsage,
                $limitBytes > 0 ? $limitBytes : null
            );
            Session::flash('success', 'Item moved.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectAfterFileAction($website);
    }

    /**
     * Download one draft file after ownership and path checks.
     */
    public function download(): void
    {
        [$userId, $website] = $this->requireWebsite();

        try {
            $file = (new ProjectStorageService())->downloadableFile($userId, $website['slug'], (string) $this->input('path', ''));
        } catch (\Throwable) {
            Session::flash('error', 'File was not found.');
            $this->redirectAfterFileAction($website);
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . $file['size']);
        header('Content-Disposition: attachment; filename="' . addcslashes($file['filename'], '"\\') . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($file['absolute_path']);
        exit;
    }

    /**
     * Export the draft project as a ZIP archive.
     */
    public function exportZip(): void
    {
        [$userId, $website] = $this->requireWebsite();

        try {
            $archive = (new ProjectArchiveService())->exportDraft($userId, $website['slug']);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirectAfterFileAction($website);
        }

        header('Content-Type: application/zip');
        header('Content-Length: ' . $archive['size']);
        header('Content-Disposition: attachment; filename="' . addcslashes($archive['filename'], '"\\') . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($archive['path']);
        unlink($archive['path']);
        exit;
    }

    /**
     * Import safe ZIP contents into the draft project.
     */
    public function importZip(): void
    {
        [$userId, $website] = $this->requireWebsiteFromPost();
        $this->requireCsrf('/dashboard/websites/files?id=' . (int) $website['id']);

        try {
            if (empty($_FILES['zip_file'])) {
                throw new \InvalidArgumentException('Choose a ZIP file to import.');
            }

            $websiteModel = new Website();
            $planService = new PlanService();
            $storage = new ProjectStorageService();
            $storageLimit = $websiteModel->userPlan($userId) === 'premium' ? $planService->premiumStorageBytes() : $planService->freeStorageBytes();
            $maxProjectBytes = $storageLimit > 0 ? $storageLimit : null;
            $result = (new ProjectArchiveService())->importDraft($userId, $website['slug'], $_FILES['zip_file'], $maxProjectBytes);

            $websiteModel->syncFileRecords((int) $website['id'], $storage->listProjectFiles($userId, $website['slug']));
            $newUsage = $result['size'];
            $websiteModel->updateStorageUsed((int) $website['id'], $newUsage);
            $notificationManager = new NotificationManager($this->config);
            $notificationManager->sendUserNotification(
                $userId,
                'ZIP imported',
                sprintf('%d files were imported into "%s" from ZIP.', (int) $result['imported'], $website['website_name']),
                [
                    'category' => 'uploads',
                    'icon' => 'folder-zip',
                    'target_url' => '/dashboard/websites/files?id=' . (int) $website['id'],
                ]
            );
            (new NotificationService($this->config))->sendStorageThresholdNotifications(
                $userId,
                (int) $website['storage_used'],
                $newUsage,
                $websiteModel->userPlan($userId) === 'premium' ? null : $planService->freeStorageBytes()
            );
            Session::flash('success', 'ZIP imported: ' . (int) $result['imported'] . ' files added, ' . (int) $result['skipped'] . ' skipped.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        $this->redirectAfterFileAction($website);
    }

    /**
     * Return file operations to the editor without allowing external redirects.
     */
    private function redirectAfterFileAction(array $website): void
    {
        $websiteId = (int) $website['id'];
        $fallback = '/dashboard/websites/files?id=' . $websiteId;
        $returnTo = trim((string) $this->input('return_to', $fallback));
        $path = parse_url($returnTo, PHP_URL_PATH);
        $query = parse_url($returnTo, PHP_URL_QUERY);
        $queryValues = [];
        if (is_string($query)) {
            parse_str($query, $queryValues);
        }

        // The editor may run under a subdirectory locally. Validate the path and
        // project ID, then rebuild a relative URL so external hosts are never trusted.
        if (is_string($path)
            && str_ends_with(rtrim($path, '/'), '/dashboard/websites/editor')
            && (int) ($queryValues['id'] ?? 0) === $websiteId
            && !str_contains($path, "\r")
            && !str_contains($path, "\n")) {
            // redirectTo() prepends the configured base URL. Strip that prefix
            // first because return_to already contains it (for example /instaweb1).
            $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');
            $relativePath = $path;
            if ($baseUrl !== '' && ($path === $baseUrl || str_starts_with($path, $baseUrl . '/'))) {
                $relativePath = substr($path, strlen($baseUrl));
            }
            $safeReturnTo = ltrim($relativePath, '/') . ($query !== null ? '?' . $query : '');
            $this->redirectTo($safeReturnTo);
        }

        $this->redirectTo($fallback);
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

    /**
     * Require CSRF for mutating file actions.
     */
    private function requireCsrf(string $fallback): void
    {
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($fallback);
        }
    }
}
