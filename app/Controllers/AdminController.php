<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Models\AdminRepository;
use App\Models\LegalPolicy;
use App\Models\Portfolio;
use App\Models\PortfolioTemplate;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\MailerService;
use App\Services\NotificationManager;
use App\Services\PublishingService;
use App\Services\ProjectStorageService;
use App\Services\SitemapGenerator;

/**
 * Handles admin dashboard and platform settings management.
 */
class AdminController extends Controller
{
    /**
     * Show the database-backed admin overview.
     */
    public function dashboard(): void
    {
        $this->adminOnly();

        $repository = new AdminRepository();

        $this->view('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $repository->stats(),
            'recentUsers' => $repository->recentUsers(),
            'recentWebsites' => $repository->recentWebsites(),
            'paymentSummary' => $repository->paymentSummary(),
        ], 'layouts/admin');
    }

    /**
     * Show settings tabs.
     */
    public function settings(): void
    {
        $this->adminOnly();

        $this->view('admin/settings', [
            'title' => 'Admin Settings',
            'settings' => (new Setting())->all(),
            'tabs' => $this->settingsTabs(),
            'policies' => (new LegalPolicy())->all(),
        ], 'layouts/admin');
    }

    /**
     * Show user management records.
     */
    public function users(): void
    {
        $this->adminOnly();

        $this->view('admin/users', [
            'title' => 'Users',
            'users' => (new AdminRepository())->users(),
        ], 'layouts/admin');
    }

    /**
     * Show the administrator user profile editor.
     */
    public function editUser(): void
    {
        $this->adminOnly();

        $userId = (int) $this->input('id', 0);
        $user = (new User())->find($userId);

        if (!$user) {
            Session::flash('error', 'User account not found.');
            $this->redirectTo('/admin/users');
        }

        $this->view('admin/user-edit', [
            'title' => 'Edit User',
            'user' => $user,
            'currentPlan' => (new Website())->userPlan($userId),
            'roles' => $this->editableRoles($user['role'] ?? null),
            'statuses' => $this->accountStatuses(),
            'isEditingSelf' => AuthService::id() === $userId,
            'active' => 'users',
        ], 'layouts/admin');
    }

    /**
     * Save administrator edits to a user account.
     */
    public function updateUser(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/users');
        }

        $userId = (int) $this->input('id', 0);
        $userModel = new User();
        $user = $userModel->find($userId);

        if (!$user) {
            Session::flash('error', 'User account not found.');
            $this->redirectTo('/admin/users');
        }

        $validator = new Validator($this->request());
        $validator->validate([
            'fullname' => 'required|min:3|max:150',
            'username' => 'required|min:3|max:50',
            'email' => 'required|email|max:150',
            'phone' => 'max:30',
            'country' => 'max:100',
            'bio' => 'max:500',
        ]);

        $role = trim((string) $this->input('role', $user['role']));
        $status = trim((string) $this->input('account_status', $user['account_status']));
        $username = strtolower(trim((string) $this->input('username')));
        $email = strtolower(trim((string) $this->input('email')));

        if (!preg_match('/^[a-z0-9_][a-z0-9_\-]{2,49}$/', $username)) {
            $validator->add('username', 'Username may contain lowercase letters, numbers, underscores, and hyphens.');
        }

        if ($this->input('reset_role') === '1') {
            $role = 'user';
        }

        if (!array_key_exists($role, $this->editableRoles($user['role'] ?? null))) {
            $validator->add('role', 'Select a valid role.');
        }

        if (!array_key_exists($status, $this->accountStatuses())) {
            $validator->add('account_status', 'Select a valid account status.');
        }

        if ($userModel->findByEmailExcept($email, $userId)) {
            $validator->add('email', 'That email address is already assigned to another account.');
        }

        if ($userModel->findByUsernameExcept($username, $userId)) {
            $validator->add('username', 'That username is already assigned to another account.');
        }

        if ($validator->errors() !== []) {
            Session::flash('error', implode(' ', $validator->messages()));
            $this->redirectTo('/admin/users/edit?id=' . $userId);
        }

        $isEditingSelf = AuthService::id() === $userId;
        $payload = [
            'fullname' => trim((string) $this->input('fullname')),
            'username' => $username,
            'email' => $email,
            'phone' => trim((string) $this->input('phone')),
            'country' => trim((string) $this->input('country')),
            'bio' => trim((string) $this->input('bio')),
            'email_verified' => $this->input('email_verified') === '1' ? 1 : 0,
        ];

        if (!$isEditingSelf) {
            $payload['role'] = $role;
            $payload['account_status'] = $status;
        }

        $userModel->updateByAdmin($userId, $payload);

        if ($isEditingSelf) {
            Session::put('user_name', $payload['fullname']);
            Session::put('user_role', $user['role']);
        }

        Session::flash('success', 'User profile updated successfully.');
        $this->redirectTo('/admin/users/edit?id=' . $userId);
    }

    /**
     * Manually move a user between Free and Premium plans.
     */
    public function changeUserPlan(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/users');
        }

        $userId = (int) $this->input('id', 0);
        $targetPlan = trim((string) $this->input('plan', ''));
        if (!in_array($targetPlan, ['free', 'premium'], true)) {
            Session::flash('error', 'Select a valid plan.');
            $this->redirectTo('/admin/users');
        }

        if ($userId === AuthService::id()) {
            Session::flash('error', 'You cannot change your own plan from here.');
            $this->redirectTo('/admin/users');
        }

        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            Session::flash('error', 'User account not found.');
            $this->redirectTo('/admin/users');
        }

        if (in_array($user['role'], ['admin', 'super_admin', 'moderator'], true)) {
            Session::flash('warning', 'Admin and moderator plans are managed through roles.');
            $this->redirectTo('/admin/users');
        }

        $database = Database::connection();
        $database->beginTransaction();

        try {
            $expire = $database->prepare("UPDATE subscriptions SET status = 'expired' WHERE user_id = :user_id AND status = 'active'");
            $expire->execute(['user_id' => $userId]);

            if ($targetPlan === 'premium') {
                $insert = $database->prepare(
                    "INSERT INTO subscriptions (user_id, plan, amount, start_date, end_date, status)
                     VALUES (:user_id, 'premium', 0, NOW(), NULL, 'active')"
                );
                $insert->execute(['user_id' => $userId]);
                $userModel->updateByAdmin($userId, ['role' => 'premium']);
            } else {
                $insert = $database->prepare(
                    "INSERT INTO subscriptions (user_id, plan, amount, start_date, end_date, status)
                     VALUES (:user_id, 'free', 0, NOW(), NULL, 'active')"
                );
                $insert->execute(['user_id' => $userId]);
                $userModel->updateByAdmin($userId, ['role' => 'user']);
            }

            $database->commit();
        } catch (\Throwable $exception) {
            $database->rollBack();
            Session::flash('error', 'Unable to update plan: ' . $exception->getMessage());
            $this->redirectTo('/admin/users');
        }

        $message = $targetPlan === 'premium'
            ? 'An administrator upgraded your account to Premium. Your Premium hosting benefits are now active.'
            : 'An administrator moved your account to the Free plan. Free plan hosting limits now apply.';

        (new NotificationManager($this->config))->sendUserNotificationWithEmail(
            $userId,
            $targetPlan === 'premium' ? 'Premium plan activated' : 'Plan changed to Free',
            $message,
            $targetPlan === 'premium' ? 'Your Instaweb Premium plan is active' : 'Your Instaweb plan changed',
            'subscription-update',
            ['message' => $message],
            [
                'category' => 'subscription',
                'icon' => $targetPlan === 'premium' ? 'crown' : 'badge-dollar-sign',
                'target_url' => '/dashboard',
            ]
        );

        Session::flash('success', 'User plan updated and notification sent.');
        $this->redirectTo('/admin/users');
    }

    /**
     * Delete a user account from the admin console.
     */
    public function deleteUser(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/users');
        }

        $userId = (int) $this->input('id', 0);
        if ($userId === AuthService::id()) {
            Session::flash('error', 'You cannot delete your own account from here.');
            $this->redirectTo('/admin/users');
        }

        $userModel = new User();
        $user = $userModel->find($userId);

        if (!$user) {
            Session::flash('error', 'User account not found.');
            $this->redirectTo('/admin/users');
        }

        if ((new AdminRepository())->userOwnsSystemWebsite($userId)) {
            Session::flash('error', 'This account owns a protected default portfolio and cannot be deleted.');
            $this->redirectTo('/admin/users');
        }

        $userModel->query('DELETE FROM users WHERE id = :id', ['id' => $userId]);
        Session::flash('success', 'User account deleted successfully.');
        $this->redirectTo('/admin/users');
    }

    /**
     * Set a user's role to the default user role.
     */
    public function resetRole(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/users');
        }

        $userId = (int) $this->input('id', 0);
        if ($userId === AuthService::id()) {
            Session::flash('error', 'You cannot reset your own role from here.');
            $this->redirectTo('/admin/users');
        }

        $userModel = new User();
        $user = $userModel->find($userId);

        if (!$user) {
            Session::flash('error', 'User account not found.');
            $this->redirectTo('/admin/users');
        }

        $userModel->updateByAdmin($userId, ['role' => 'user']);
        Session::flash('success', 'User role reset to User successfully.');
        $this->redirectTo('/admin/users');
    }

    /**
     * Show media manager.
     */
    public function media(): void
    {
        $this->adminOnly();
        $uploadsPath = $this->mediaUploadDir();

        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        $files = array_values(array_filter(scandir($uploadsPath), static fn ($item): bool => is_file($uploadsPath . DIRECTORY_SEPARATOR . $item) && $item !== '.' && $item !== '..'));
        $files = array_map(function (string $filename) use ($uploadsPath): array {
            return [
                'name' => $filename,
                'url' => rtrim(($this->config['base_url'] ?? ''), '/') . '/uploads/' . rawurlencode($filename),
                'size' => filesize($uploadsPath . DIRECTORY_SEPARATOR . $filename),
                'modified_at' => date('F j, Y g:i A', filemtime($uploadsPath . DIRECTORY_SEPARATOR . $filename)),
                'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
            ];
        }, $files);

        $this->view('admin/media', [
            'title' => 'Media Manager',
            'files' => $files,
            'uploads_url' => rtrim(($this->config['base_url'] ?? ''), '/') . '/uploads',
            'allowed_extensions' => $this->allowedUploadExtensions(),
            'max_upload_size_mb' => (int) ($this->config['storage']['default_upload_file_bytes'] / (1024 * 1024)),
            'active' => 'media',
        ], 'layouts/admin');
    }

    /**
     * Handle media uploads.
     */
    public function uploadMedia(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/media');
        }

        if (empty($_FILES['media_file']['name'])) {
            Session::flash('error', 'Select a file to upload.');
            $this->redirectTo('/admin/media');
        }

        $file = $_FILES['media_file'];
        $filename = basename($file['name']);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $uploadsPath = $this->mediaUploadDir();

        if (!in_array($extension, $this->allowedUploadExtensions(), true)) {
            Session::flash('error', 'This file type is not allowed.');
            $this->redirectTo('/admin/media');
        }

        if ($file['size'] > ($this->config['storage']['default_upload_file_bytes'] ?? (10 * 1024 * 1024))) {
            Session::flash('error', 'The file is too large.');
            $this->redirectTo('/admin/media');
        }

        $safeName = pathinfo($filename, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $safeName);
        $outputExtension = $this->isImageForWebpConversion($extension) ? 'webp' : $extension;
        $finalName = $safeName . '.' . $outputExtension;
        $targetPath = $uploadsPath . DIRECTORY_SEPARATOR . $finalName;

        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        if (is_file($targetPath)) {
            Session::flash('error', 'A file with that name already exists.');
            $this->redirectTo('/admin/media');
        }

        if ($this->isImageForWebpConversion($extension)) {
            if (!$this->convertImageToWebp($file['tmp_name'], $targetPath, $extension)) {
                Session::flash('error', 'Unable to convert image to WebP.');
                $this->redirectTo('/admin/media');
            }
        } else {
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                Session::flash('error', 'Unable to save the uploaded file.');
                $this->redirectTo('/admin/media');
            }
        }

        Session::flash('success', 'File uploaded successfully.');
        $this->redirectTo('/admin/media');
    }

    private function isImageForWebpConversion(string $extension): bool
    {
        return in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'bmp'], true);
    }

    private function convertImageToWebp(string $sourcePath, string $targetPath, string $extension): bool
    {
        if (extension_loaded('imagick')) {
            try {
                $image = new \Imagick($sourcePath);
                $image->setImageFormat('webp');
                $image->setImageCompressionQuality(100);
                $image->setOption('webp:lossless', 'true');
                $image->writeImage($targetPath);
                $image->clear();
                $image->destroy();
                return is_file($targetPath);
            } catch (\Throwable $e) {
                return false;
            }
        }

        if (!function_exists('imagewebp')) {
            return false;
        }

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $resource = @imagecreatefromjpeg($sourcePath);
                break;
            case 'png':
                $resource = @imagecreatefrompng($sourcePath);
                if ($resource) {
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                }
                break;
            case 'gif':
                $resource = @imagecreatefromgif($sourcePath);
                if ($resource) {
                    imagealphablending($resource, false);
                    imagesavealpha($resource, true);
                }
                break;
            case 'bmp':
                $resource = @imagecreatefrombmp($sourcePath);
                break;
            default:
                $resource = null;
        }

        if (!$resource) {
            return false;
        }

        $success = imagewebp($resource, $targetPath, 100);
        imagedestroy($resource);
        return $success && is_file($targetPath);
    }

    /**
     * Delete a media file.
     */
    public function deleteMedia(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/media');
        }

        $filename = basename(trim((string) $this->input('filename', '')));
        $filePath = $this->mediaUploadDir() . DIRECTORY_SEPARATOR . $filename;

        if ($filename === '' || !is_file($filePath)) {
            Session::flash('error', 'File not found.');
            $this->redirectTo('/admin/media');
        }

        unlink($filePath);
        Session::flash('success', 'File deleted successfully.');
        $this->redirectTo('/admin/media');
    }

    /**
     * Rename a media file.
     */
    public function renameMedia(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/media');
        }

        $currentName = basename(trim((string) $this->input('current_name', '')));
        $newName = basename(trim((string) $this->input('new_name', '')));
        $uploadsPath = $this->mediaUploadDir();
        $currentPath = $uploadsPath . DIRECTORY_SEPARATOR . $currentName;
        $newPath = $uploadsPath . DIRECTORY_SEPARATOR . $newName;

        if ($currentName === '' || $newName === '' || !is_file($currentPath)) {
            Session::flash('error', 'Invalid file name.');
            $this->redirectTo('/admin/media');
        }

        $extension = strtolower(pathinfo($newName, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedUploadExtensions(), true)) {
            Session::flash('error', 'This file extension is not allowed.');
            $this->redirectTo('/admin/media');
        }

        if (is_file($newPath)) {
            Session::flash('error', 'A file with this name already exists.');
            $this->redirectTo('/admin/media');
        }

        rename($currentPath, $newPath);
        Session::flash('success', 'File renamed successfully.');
        $this->redirectTo('/admin/media');
    }

    private function mediaUploadDir(): string
    {
        return PUBLIC_PATH . DIRECTORY_SEPARATOR . 'uploads';
    }

    private function allowedUploadExtensions(): array
    {
        return ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp', 'txt', 'pdf', 'zip', 'css', 'js'];
    }

    /**
     * Account roles an administrator may assign.
     */
    private function editableRoles(string $currentRole = null): array
    {
        $roles = [
            'user' => 'User',
            'moderator' => 'Moderator',
            'admin' => 'Admin',
            'super_admin' => 'Super Admin',
        ];

        if ($currentRole === 'premium') {
            $roles = array_merge(['premium' => 'Premium (legacy)'], $roles);
        }

        return $roles;
    }

    /**
     * Account statuses an administrator may assign.
     */
    private function accountStatuses(): array
    {
        return [
            'active' => 'Active',
            'pending' => 'Pending',
            'suspended' => 'Suspended',
            'banned' => 'Banned',
        ];
    }

    /**
     * Show hosted website records.
     */
    public function websites(): void
    {
        $this->adminOnly();

        $this->view('admin/websites', [
            'title' => 'Websites',
            'websites' => (new AdminRepository())->websites(),
        ], 'layouts/admin');
    }

    /**
     * Suspend a hosted website and remove its public copy.
     */
    public function suspendWebsite(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/websites');
        }

        $repository = new AdminRepository();
        $website = $repository->website((int) $this->input('id', 0));

        if (!$website) {
            Session::flash('error', 'Website not found.');
            $this->redirectTo('/admin/websites');
        }

        if (!empty($website['is_system_template'])) {
            Session::flash('error', 'This protected default portfolio must remain published.');
            $this->redirectTo('/admin/websites');
        }

        (new PublishingService())->unpublish((int) $website['id'], $website['slug']);
        $repository->updateWebsiteStatus((int) $website['id'], 'suspended');
        try {
            (new SitemapGenerator($this->config))->writeToFile();
        } catch (\Throwable $exception) {
            // Sitemap regeneration should not block suspension.
        }

        Session::flash('success', 'Website suspended and public files removed.');
        $this->redirectTo('/admin/websites');
    }

    /**
     * Return a suspended website to draft status.
     */
    public function unsuspendWebsite(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/websites');
        }

        $repository = new AdminRepository();
        $website = $repository->website((int) $this->input('id', 0));

        if (!$website) {
            Session::flash('error', 'Website not found.');
            $this->redirectTo('/admin/websites');
        }

        $repository->updateWebsiteStatus((int) $website['id'], 'draft');

        Session::flash('success', 'Website unsuspended. The owner can publish it again after review.');
        $this->redirectTo('/admin/websites');
    }

    /**
     * Delete a website project and its draft/public files from the admin console.
     */
    public function deleteWebsite(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/websites');
        }

        $repository = new AdminRepository();
        $website = $repository->website((int) $this->input('id', 0));

        if (!$website) {
            Session::flash('error', 'Website not found.');
            $this->redirectTo('/admin/websites');
        }

        if (!empty($website['is_system_template'])) {
            Session::flash('error', 'This protected default portfolio cannot be deleted.');
            $this->redirectTo('/admin/websites');
        }

        (new PublishingService())->unpublish((int) $website['id'], $website['slug']);
        (new ProjectStorageService())->deleteProjectDirectory((int) $website['user_id'], $website['slug']);
        $repository->deleteWebsite((int) $website['id']);
        try {
            (new SitemapGenerator($this->config))->writeToFile();
        } catch (\Throwable $exception) {
            // Sitemap regeneration should not block admin deletion.
        }

        Session::flash('success', 'Website deleted.');
        $this->redirectTo('/admin/websites');
    }

    /**
     * Show payment records.
     */
    public function payments(): void
    {
        $this->adminOnly();

        $this->view('admin/payments', [
            'title' => 'Payments',
            'payments' => (new AdminRepository())->payments(),
        ], 'layouts/admin');
    }

    /**
     * Show abuse reports.
     */
    public function reports(): void
    {
        $this->adminOnly();

        $this->view('admin/reports', [
            'title' => 'Reports',
            'reports' => (new AdminRepository())->reports(),
        ], 'layouts/admin');
    }

    /**
     * Show platform notifications.
     */
    public function feedback(): void
    {
        $this->adminOnly();
        $model = new \App\Models\FeedbackSubmission();
        $this->view('admin/feedback', [
            'title' => 'Problems & Feature Requests',
            'submissions' => $model->all((string) $this->input('status', ''), (string) $this->input('type', '')),
            'newCount' => $model->countNew(),
            'navBadges' => ['feedback' => $model->countNew()],
        ], 'layouts/admin');
    }

    public function updateFeedbackStatus(): void
    {
        $this->adminOnly();
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/feedback');
        }

        $id = (int) $this->input('id', 0);
        $status = (string) $this->input('status', 'new');
        $note = trim((string) $this->input('admin_note', ''));
        if ($id < 1 || !in_array($status, ['new', 'in_progress', 'resolved', 'closed'], true) || mb_strlen($note) > 2000) {
            Session::flash('error', 'Invalid feedback update.');
            $this->redirectTo('/admin/feedback');
        }

        (new \App\Models\FeedbackSubmission())->updateStatus($id, $status, $note);
        Session::flash('success', 'Feedback status updated.');
        $this->redirectTo('/admin/feedback');
    }

    public function notifications(): void
    {
        $this->adminOnly();

        $this->view('admin/notifications', [
            'title' => 'Notifications',
            'notifications' => (new AdminRepository())->notifications(),
        ], 'layouts/admin');
    }

    /**
     * Broadcast a notification to all users and administrators.
     */
    public function broadcastNotifications(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/notifications');
        }

        $title = trim((string) $this->input('broadcast_title'));
        $message = trim((string) $this->input('broadcast_message'));

        if ($title === '' || $message === '') {
            Session::flash('error', 'Both title and message are required to broadcast a notification.');
            $this->redirectTo('/admin/notifications');
        }

        $sendEmail = $this->input('broadcast_send_email') === '1';

        if ($sendEmail) {
            // Send email + create notification per user
            $notificationService = new \App\Services\NotificationService($this->config);
            $userModel = new \App\Models\User();
            $users = $userModel->allIds();
            foreach ($users as $u) {
                $userId = (int) ($u['id'] ?? 0);
                if ($userId === 0) {
                    continue;
                }
                try {
                    $notificationService->notifyUserWithEmail($userId, $title, $message, $title, 'broadcast', ['message' => $message], 'broadcast', 'send', '/dashboard');
                } catch (\Throwable $e) {
                    // continue on error for individual users
                }
            }

            Session::flash('success', 'Broadcast notification created and emails queued for all users.');
        } else {
            $notificationManager = new NotificationManager($this->config);
            $notificationManager->broadcastAnnouncement($title, $message, 'broadcast', 'send', '/dashboard');

            Session::flash('success', 'Broadcast notification created successfully for all users.');
        }
        $this->redirectTo('/admin/notifications');
    }

    /**
     * Save settings from the tabbed settings form.
     */
    public function saveSettings(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/settings');
        }

        $brandingUploads = $this->processBrandingUploads();
        $allowed = $this->allowedSettings();
        $payload = [];

        foreach ($allowed as $key => $group) {
            $value = trim((string) ($brandingUploads[$key] ?? $this->input($key, '')));
            $payload[$key] = [
                'group' => $group,
                'value' => $value,
            ];
        }

        foreach ($this->checkboxSettings() as $key => $group) {
            $payload[$key] = [
                'group' => $group,
                'value' => $this->input($key) === '1' ? '1' : '0',
            ];
        }

        (new Setting())->saveMany($payload);
        Session::flash('success', 'Settings saved successfully.');
        $this->redirectTo('/admin/settings');
    }

    /**
     * Preview the maintenance page using current maintenance settings.
     */
    public function previewMaintenance(): void
    {
        $this->adminOnly();

        $settings = (new Setting())->all();

        $this->view('maintenance', [
            'title' => $settings['maintenance_page_title'] ?? 'We’ll be back soon',
            'maintenance_page_title' => $settings['maintenance_page_title'] ?? 'We’ll be back soon',
            'maintenance_status_label' => $settings['maintenance_status_label'] ?? 'Maintenance in progress',
            'maintenance_message' => $settings['maintenance_message'] ?? 'Our website is currently undergoing scheduled maintenance. We appreciate your patience and expect to be back online shortly.',
            'maintenance_return_at' => $settings['maintenance_return_at'] ?? '',
            'maintenance_bypass_token' => $settings['maintenance_bypass_token'] ?? '',
            'maintenance_preview' => true,
            'app' => $this->config,
        ], 'layouts/maintenance');
    }

    /**
     * Send a SMTP test email using the current platform settings.
     */
    public function sendTestEmail(): void
    {
        $this->adminOnly();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/settings');
        }

        $recipient = trim((string) $this->input('send_test_email_to', ''));

        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Enter a valid email address to receive the test message.');
            $this->redirectTo('/admin/settings');
        }

        $settings = (new Setting())->all();
        $mailConfig = [
            'host' => $settings['mail_host'] ?? 'smtp.gmail.com',
            'port' => (int) ($settings['mail_port'] ?? 587),
            'username' => $settings['mail_username'] ?? '',
            'password' => $settings['mail_password'] ?? '',
            'encryption' => $settings['mail_encryption'] ?? 'tls',
            'from_address' => $settings['mail_from_address'] ?? 'no-reply@instaweb.free.dev',
            'from_name' => $settings['mail_from_name'] ?? ($this->config['name'] ?? 'Instaweb'),
            'admin_login_notification_address' => $settings['admin_login_notification_email'] ?? '',
            'admin_login_notification_message' => $settings['admin_login_notification_message'] ?? 'An administrator has signed in to the Instaweb site.',
        ];

        try {
            $mailer = new MailerService($mailConfig, $this->config);
            $sent = $mailer->send(
                $recipient,
                'SMTP test recipient',
                'Instaweb SMTP test message',
                '<p>This is a test email sent from Instaweb using your SMTP configuration.</p>',
                'This is a test email sent from Instaweb using your SMTP configuration.'
            );

            if ($sent) {
                Session::flash('success', 'Test email sent successfully.');
            } else {
                Session::flash('error', 'Test email failed to send. Check your SMTP settings.');
            }
        } catch (\Exception $exception) {
            Session::flash('error', 'Test email failed: ' . $exception->getMessage());
        }

        $this->redirectTo('/admin/settings');
    }

    /**
     * Configure the administrator-owned published portfolio used as the
     * sample preview for each portfolio template.
     */
    public function portfolioTemplates(): void
    {
        $this->adminOnly();
        $settings = (new Setting())->all();
        $portfolios = (new Portfolio())->ownedPortfolioOptions((int) AuthService::id());
        $templateCatalog = new PortfolioTemplate();
        $templates = $templateCatalog->all();
        $defaults = [];
        foreach ($templates as $template) {
            $templateKey = (string) $template['template_key'];
            $defaults[$templateKey] = (int) ($settings['portfolio_template_default_' . $templateKey] ?? 0);
        }

        $this->view('admin/portfolio-templates', [
            'title' => 'Portfolio Templates',
            'active' => 'portfolio-templates',
            'templates' => $templates,
            'portfolios' => $portfolios,
            'defaults' => $defaults,
        ], 'layouts/admin');
    }

    public function savePortfolioTemplates(): void
    {
        $this->adminOnly();
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/portfolio-templates');
        }

        $userId = (int) AuthService::id();
        $portfolioModel = new Portfolio();
        $owned = $portfolioModel->ownedPortfolioOptions($userId);
        $ownedById = [];
        foreach ($owned as $portfolio) {
            $ownedById[(int) $portfolio['id']] = (string) ($portfolio['template_key'] ?? '');
        }

        $settingsToSave = [];
        foreach ((new PortfolioTemplate())->all() as $template) {
            $templateKey = (string) $template['template_key'];
            $portfolioId = (int) $this->input('default_' . $templateKey, 0);
            if ($portfolioId !== 0 && (($ownedById[$portfolioId] ?? null) !== $templateKey)) {
                Session::flash('error', 'Choose a portfolio you own that uses the matching template. Each sample must match its template.');
                $this->redirectTo('/admin/portfolio-templates');
            }
            $settingsToSave['portfolio_template_default_' . $templateKey] = [
                'value' => (string) $portfolioId,
                'group' => 'portfolio_templates',
            ];
        }

        (new Setting())->saveMany($settingsToSave);
        Session::flash('success', 'Default portfolio template previews saved.');
        $this->redirectTo('/admin/portfolio-templates');
    }

    /** Show the safe HTML/CSS editor for a new custom portfolio template. */
    public function createPortfolioTemplate(): void
    {
        $this->adminOnly();
        $this->view('admin/portfolio-template-form', [
            'title' => 'Add Portfolio Template',
            'active' => 'portfolio-templates',
            'mode' => 'create',
            'template' => [
                'display_name' => '',
                'description' => '',
                'render_engine' => 'html',
                'html_code' => $this->defaultTemplateHtml(),
                'css_code' => $this->defaultTemplateCss(),
                'react_code' => $this->defaultTemplateReact(),
            ],
        ], 'layouts/admin');
    }

    /** Show the code editor for a custom template. Built-ins are code-owned. */
    public function editPortfolioTemplate(): void
    {
        $this->adminOnly();
        $template = (new PortfolioTemplate())->findCustom((int) $this->input('id', 0));
        if (!$template) {
            Session::flash('error', 'That custom template was not found. Built-in template code is managed by the application.');
            $this->redirectTo('/admin/portfolio-templates');
        }

        $this->view('admin/portfolio-template-form', [
            'title' => 'Edit Portfolio Template',
            'active' => 'portfolio-templates',
            'mode' => 'edit',
            'template' => $template,
        ], 'layouts/admin');
    }

    public function storePortfolioTemplate(): void
    {
        $this->adminOnly();
        $this->validateTemplateCsrf();
        $data = $this->templateInput();
        $catalog = new PortfolioTemplate();
        $data['template_key'] = $this->nextCustomTemplateKey($data['display_name'], $catalog->all());
        $data['status'] = 'active';
        $catalog->create($data, (int) AuthService::id());

        Session::flash('success', 'Custom template added. It is now available in Portfolio Studio.');
        $this->redirectTo('/admin/portfolio-templates');
    }

    public function updatePortfolioTemplate(): void
    {
        $this->adminOnly();
        $this->validateTemplateCsrf();
        $catalog = new PortfolioTemplate();
        $templateId = (int) $this->input('id', 0);
        if (!$catalog->findCustom($templateId)) {
            Session::flash('error', 'That custom template no longer exists.');
            $this->redirectTo('/admin/portfolio-templates');
        }

        $catalog->update($templateId, $this->templateInput());
        Session::flash('success', 'Template code saved.');
        $this->redirectTo('/admin/portfolio-templates');
    }

    /** Suspend or activate either a built-in or custom template. */
    public function setPortfolioTemplateStatus(): void
    {
        $this->adminOnly();
        $this->validateTemplateCsrf();
        $status = (string) $this->input('status', '');
        if (!in_array($status, ['active', 'suspended'], true)) {
            Session::flash('error', 'Choose a valid template status.');
            $this->redirectTo('/admin/portfolio-templates');
        }

        $catalog = new PortfolioTemplate();
        $templateKey = trim((string) $this->input('template_key', ''));
        if ($catalog->isBuiltIn($templateKey)) {
            $catalog->setBuiltInStatus($templateKey, $status);
        } else {
            $templateId = (int) $this->input('id', 0);
            if (!$catalog->findCustom($templateId)) {
                Session::flash('error', 'That custom template was not found.');
                $this->redirectTo('/admin/portfolio-templates');
            }
            $catalog->setCustomStatus($templateId, $status);
        }

        Session::flash('success', $status === 'suspended'
            ? 'Template suspended. It is hidden from new Portfolio Studio selections; portfolios already using it still render.'
            : 'Template activated and available in Portfolio Studio.');
        $this->redirectTo('/admin/portfolio-templates');
    }

    /** Set a built-in or custom portfolio template to Free or Premium. */
    public function setPortfolioTemplateTier(): void
    {
        $this->adminOnly();
        $this->validateTemplateCsrf();

        $templateKey = trim((string) $this->input('template_key', ''));
        $tier = strtolower(trim((string) $this->input('plan_tier', '')));
        if (!in_array($tier, ['free', 'premium'], true)
            || !(new PortfolioTemplate())->setTemplateTier($templateKey, $tier)) {
            Session::flash('error', 'Choose a valid template and plan tier.');
            $this->redirectTo('/admin/portfolio-templates');
        }

        Session::flash('success', ucfirst($templateKey) . ' is now classified as ' . ucfirst($tier) . '.');
        $this->redirectTo('/admin/portfolio-templates');
    }

    /** Delete an unused custom template. Built-in renderer files stay protected. */
    public function deletePortfolioTemplate(): void
    {
        $this->adminOnly();
        $this->validateTemplateCsrf();
        $catalog = new PortfolioTemplate();
        $template = $catalog->findCustom((int) $this->input('id', 0));
        if (!$template) {
            Session::flash('error', 'Only custom templates can be deleted.');
            $this->redirectTo('/admin/portfolio-templates');
        }
        if ($catalog->isInUse((string) $template['template_key'])) {
            Session::flash('error', 'This template is currently used by a portfolio and cannot be deleted. Switch those portfolios to another template first.');
            $this->redirectTo('/admin/portfolio-templates');
        }

        $catalog->deleteCustom((int) $template['id']);
        Session::flash('success', 'Custom template deleted.');
        $this->redirectTo('/admin/portfolio-templates');
    }

    private function validateTemplateCsrf(): void
    {
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/admin/portfolio-templates');
        }
    }

    /** Validate the source that is stored for a public-facing custom template. */
    private function templateInput(): array
    {
        $displayName = trim((string) $this->input('display_name', ''));
        $description = trim((string) $this->input('description', ''));
        $renderEngine = (string) $this->input('render_engine', 'html');
        $html = trim((string) $this->input('html_code', ''));
        $css = trim((string) $this->input('css_code', ''));
        $react = trim((string) $this->input('react_code', ''));

        $unsafeHtml = '/<\/?(?:script|iframe|object|embed|base|form|meta|link)\b|<\?|\bon[a-z]+\s*=|(?:javascript|vbscript|data)\s*:/i';
        $unsafeCss = '/<\/?(?:style|script|iframe|object|embed|base|form|meta|link)\b|@import|expression\s*\(|(?:javascript|vbscript|data)\s*:|behavior\s*:/i';
        // Keep `function Portfolio()` valid. The dynamic Function constructor is
        // deliberately checked separately and case-sensitively, because this
        // expression otherwise considers the `function` keyword unsafe too.
        $unsafeReact = '/\b(?:import|export|require|fetch|XMLHttpRequest|WebSocket|EventSource|Worker|navigator|localStorage|sessionStorage|indexedDB|eval)\b|<\/?(?:script|iframe|object|embed|base|form|meta|link)\b|(?:javascript|vbscript|data)\s*:/i';
        $unsafeReactFunctionConstructor = '/\bFunction\s*\(/';
        $invalidBasics = $displayName === '' || mb_strlen($displayName) > 120 || mb_strlen($description) > 500
            || !in_array($renderEngine, ['html', 'react'], true) || strlen($css) > 90000 || preg_match($unsafeCss, $css);
        $invalidHtml = $renderEngine === 'html' && ($html === '' || strlen($html) > 180000 || preg_match($unsafeHtml, $html));
        $invalidReact = $renderEngine === 'react' && ($react === '' || strlen($react) > 180000
            || !preg_match('/\b(?:function|const|let|var)\s+Portfolio\b/', $react)
            || preg_match($unsafeReact, $react) || preg_match($unsafeReactFunctionConstructor, $react));
        if ($invalidBasics || $invalidHtml || $invalidReact) {
            Session::flash('error', $renderEngine === 'react'
                ? 'Use a valid React component named Portfolio. Imports, network APIs, browser storage, frames, and dynamic code execution are not allowed.'
                : 'Use a name, safe HTML, and safe CSS. Scripts, embedded pages, event handlers, imports, and executable URLs are not allowed.');
            $this->redirectTo('/admin/portfolio-templates');
        }

        return [
            'display_name' => $displayName,
            'description' => $description,
            'render_engine' => $renderEngine,
            'html_code' => $renderEngine === 'html' ? $html : '',
            'css_code' => $css,
            'react_code' => $renderEngine === 'react' ? $react : '',
        ];
    }

    private function nextCustomTemplateKey(string $displayName, array $templates): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $displayName), '-'));
        $slug = $slug !== '' ? $slug : 'portfolio';
        $base = substr('custom-' . $slug, 0, 36);
        $keys = [];
        foreach ($templates as $template) {
            $keys[(string) ($template['template_key'] ?? '')] = true;
        }
        $key = $base;
        $suffix = 2;
        while (isset($keys[$key])) {
            $suffixText = '-' . $suffix++;
            $key = substr($base, 0, 40 - strlen($suffixText)) . $suffixText;
        }

        return $key;
    }

    private function defaultTemplateHtml(): string
    {
        return <<<'HTML'
<main class="custom-portfolio">
  <header class="custom-site-header">
    <a class="custom-wordmark" href="#home">{{name}}</a>
    {{navigation}}
  </header>
  <header class="custom-hero" id="home">
    <div>
      <p class="custom-eyebrow">{{location}}</p>
      <h1>{{name}}</h1>
      <p class="custom-headline">{{headline}}</p>
      <p>{{bio}}</p>
      <a class="custom-button" href="#contact">Get in touch</a>
    </div>
    {{avatar}}
  </header>
  <section class="custom-section custom-about" id="about">
    <p class="custom-eyebrow">About</p>
    <h2>Useful work, clearly presented.</h2>
    <p>{{bio}}</p>
  </section>
  {{sections}}
  {{projects}}
  {{contact}}
  <footer>© {{year}} {{name}}</footer>
</main>
HTML;
    }

    private function defaultTemplateCss(): string
    {
        return <<<'CSS'
.custom-portfolio { max-width: 1120px; margin: 0 auto; padding: 108px 28px 28px; }
.custom-site-header { position: fixed; z-index: 20; top: 0; right: 0; left: 0; display: flex; align-items: center; justify-content: space-between; gap: 20px; min-height: 72px; padding: 14px max(28px, calc((100vw - 1120px) / 2 + 28px)); border-bottom: 1px solid rgba(15,23,42,.12); background: rgba(255,255,255,.9); backdrop-filter: blur(14px); }
.custom-wordmark { min-width: 0; color: #172b3a; font-weight: 900; overflow: hidden; text-decoration: none; text-overflow: ellipsis; white-space: nowrap; }.custom-site-nav { display: flex; align-items: center; gap: 18px; overflow-x: auto; scrollbar-width: none; }.custom-site-nav::-webkit-scrollbar { display: none; }.custom-site-nav a { color: #475569; font-size: .9rem; font-weight: 750; text-decoration: none; white-space: nowrap; }.custom-site-nav a:hover,.custom-site-nav a:focus-visible { color: var(--portfolio-accent); }
.custom-hero { min-height: 72vh; display: grid; grid-template-columns: 1fr minmax(240px, .72fr); align-items: center; gap: 56px; }
.custom-hero h1 { margin: 0; font-size: clamp(3rem, 10vw, 7rem); letter-spacing: -.07em; line-height: .92; }
.custom-eyebrow { color: var(--portfolio-accent); font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.custom-headline { font-size: 1.3rem; font-weight: 700; }
.custom-avatar { display: block; width: 100%; aspect-ratio: 4 / 5; object-fit: cover; }
.custom-section { padding: 78px 0; border-top: 1px solid rgba(15, 23, 42, .15); scroll-margin-top: 92px; }
.custom-section h2 { font-size: clamp(2rem, 5vw, 3.5rem); margin: 0 0 28px; }
.custom-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; }
.custom-card { padding: 22px; background: rgba(255,255,255,.72); border: 1px solid rgba(15,23,42,.12); border-radius: 14px; }
.custom-card h3 { margin-top: 0; }.custom-button { display:inline-block; padding: 12px 18px; background: #172b3a; color: #fff; text-decoration:none; }
.custom-portfolio footer { padding: 32px 0; color: #64748b; }
@media (max-width: 720px) { .custom-portfolio { padding: 96px 20px 20px; } .custom-site-header { min-height: 64px; padding: 10px 20px; } .custom-site-nav { gap: 14px; } .custom-hero { grid-template-columns: 1fr; min-height: auto; padding: 60px 0; gap: 28px; } }
.react-custom-template { max-width: 1120px; margin: 0 auto; padding: 108px 28px 28px; color: #172b3a; }
.react-custom-site-header { position: fixed; z-index: 20; top: 0; right: 0; left: 0; display: flex; align-items: center; justify-content: space-between; gap: 20px; min-height: 72px; padding: 14px max(28px, calc((100vw - 1120px) / 2 + 28px)); border-bottom: 1px solid rgba(15,23,42,.12); background: rgba(255,255,255,.9); backdrop-filter: blur(14px); }.react-custom-wordmark { min-width: 0; color: #172b3a; font-weight: 900; overflow: hidden; text-decoration: none; text-overflow: ellipsis; white-space: nowrap; }.react-custom-nav { display: flex; align-items: center; gap: 18px; overflow-x: auto; scrollbar-width: none; }.react-custom-nav::-webkit-scrollbar { display: none; }.react-custom-nav a { color: #475569; font-size: .9rem; font-weight: 750; text-decoration: none; white-space: nowrap; }.react-custom-nav a:hover,.react-custom-nav a:focus-visible { color: #b45309; }
.react-custom-hero { min-height: 72vh; display: grid; grid-template-columns: 1fr minmax(240px, .72fr); align-items: center; gap: 56px; }
.react-custom-hero h1 { margin: 0; font-size: clamp(3rem, 10vw, 7rem); letter-spacing: -.07em; line-height: .92; }
.react-custom-kicker { color: #b45309; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.react-custom-headline { font-size: 1.3rem; font-weight: 700; }.react-custom-avatar { display: block; width: 100%; aspect-ratio: 4 / 5; object-fit: cover; }
.react-custom-section { padding: 78px 0; border-top: 1px solid rgba(15, 23, 42, .15); scroll-margin-top: 92px; }.react-custom-section h2 { font-size: clamp(2rem, 5vw, 3.5rem); margin: 0 0 28px; }
.react-custom-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; }.react-custom-card { padding: 22px; background: rgba(255,255,255,.8); border: 1px solid rgba(15,23,42,.12); border-radius: 14px; }
.react-custom-card h3 { margin-top: 0; }.react-custom-card a { color: #b45309; font-weight: 800; }.react-custom-button,.react-custom-toggle { display:inline-block; padding: 12px 18px; border: 0; background: #172b3a; color: #fff; text-decoration:none; font-weight:800; cursor:pointer; }.react-custom-toggle { margin-top: 20px; }
@media (max-width: 720px) { .react-custom-template { padding: 96px 20px 20px; } .react-custom-site-header { min-height: 64px; padding: 10px 20px; } .react-custom-nav { gap: 14px; } .react-custom-hero { grid-template-columns: 1fr; min-height: auto; padding: 60px 0; gap: 28px; } }
CSS;
    }

    private function defaultTemplateReact(): string
    {
        return <<<'JSX'
function Portfolio({ data }) {
  const [showAllProjects, setShowAllProjects] = React.useState(false);
  const allProjects = Array.isArray(data.projects) ? data.projects : [];
  const sections = Array.isArray(data.sections) ? data.sections : [];
  const projects = showAllProjects ? allProjects : allProjects.slice(0, 3);
  const sectionLabels = {};
  sections.forEach(section => { sectionLabels[section.key] = section.label; });
  const navigation = [
    { key: 'about', label: 'About', available: true },
    { key: 'experience', label: 'Experience', available: Boolean(sectionLabels.experience) },
    { key: 'projects', label: 'Projects', available: data.showProjects && allProjects.length > 0 },
    { key: 'skills', label: 'Skills', available: Boolean(sectionLabels.skills) },
    { key: 'contact', label: 'Contact', available: data.showContact && Boolean(data.email || (data.socials || []).length) },
    { key: 'education', label: sectionLabels.education || 'Education', available: Boolean(sectionLabels.education) },
    { key: 'goals', label: sectionLabels.goals || 'Goals', available: Boolean(sectionLabels.goals) },
    { key: 'interests', label: sectionLabels.interests || 'Interests', available: Boolean(sectionLabels.interests) },
    { key: 'challenges', label: sectionLabels.challenges || 'Challenges', available: Boolean(sectionLabels.challenges) },
    { key: 'partnerships', label: sectionLabels.partnerships || 'Partnerships', available: Boolean(sectionLabels.partnerships) },
    { key: 'news', label: sectionLabels.news || 'News and updates', available: Boolean(sectionLabels.news) },
    { key: 'cv', label: sectionLabels.cv || 'CV / Resume', available: Boolean(sectionLabels.cv) }
  ].filter(item => item.available).slice(0, 5);

  return (
    <main className="react-custom-template">
      <header className="react-custom-site-header">
        <a className="react-custom-wordmark" href="#home">{data.name || 'Portfolio'}</a>
        <nav className="react-custom-nav" aria-label="Portfolio navigation">
          {navigation.map(item => <a href={`#${item.key}`} key={item.key}>{item.label}</a>)}
        </nav>
      </header>
      <header className="react-custom-hero" id="home">
        <div>
          <p className="react-custom-kicker">{data.location || 'Portfolio'}</p>
          <h1>{data.name}</h1>
          <p className="react-custom-headline">{data.headline}</p>
          <p>{data.bio}</p>
          {data.contactAvailable && <a className="react-custom-button" href="#portfolio-contact-dialog" data-portfolio-contact>Start a conversation</a>}
        </div>
        {data.avatar && <img className="react-custom-avatar" src={data.avatar} alt={data.name} />}
      </header>

      <section className="react-custom-section" id="about">
        <p className="react-custom-kicker">About</p>
        <h2>Useful work, clearly presented.</h2>
        <p>{data.bio}</p>
      </section>

      {sections.map(section => (
        <section className="react-custom-section" id={section.key} key={section.key}>
          <p className="react-custom-kicker">Portfolio</p>
          <h2>{section.label}</h2>
          <div className="react-custom-grid">
            {section.items.map((item, index) => <article className="react-custom-card" key={`${section.key}-${index}`}><h3>{item.title}</h3><p>{item.organisation}</p><p>{item.details}</p></article>)}
          </div>
        </section>
      ))}

      {data.showProjects && <section className="react-custom-section" id="projects"><p className="react-custom-kicker">Selected work</p><h2>Projects</h2><div className="react-custom-grid">{projects.map((project, index) => <article className="react-custom-card" key={project.id || index}><h3>{project.title}</h3><p>{project.summary}</p>{project.project_url && <a href={project.project_url} target="_blank" rel="noreferrer">View project ↗</a>}</article>)}</div>{allProjects.length > 3 && <button type="button" className="react-custom-toggle" onClick={() => setShowAllProjects(!showAllProjects)}>{showAllProjects ? 'Show fewer projects' : 'Show all projects'}</button>}</section>}

      {data.showContact && data.contactAvailable && <section className="react-custom-section react-custom-contact" id="contact"><p className="react-custom-kicker">Contact</p><h2>Let’s work together.</h2><a className="react-custom-button" href="#portfolio-contact-dialog" data-portfolio-contact>Email {data.name}</a></section>}
    </main>
  );
}
JSX;
    }

    /**
     * Require an authenticated admin account.
     */
    private function adminOnly(): void
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        if (!AuthService::isAdmin()) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    /**
     * Settings tab definitions.
     */
    private function settingsTabs(): array
    {
        return [
            'general' => ['General', 'settings'],
            'appearance' => ['Appearance', 'palette'],
            'branding' => ['Branding', 'badge-check'],
            'homepage' => ['Homepage', 'home'],
            'footer' => ['Footer', 'layout-template'],
            'legal' => ['Legal', 'shield-check'],
            'pricing' => ['Pricing', 'badge-dollar-sign'],
            'smtp' => ['SMTP', 'mail'],
            'maintenance' => ['Maintenance', 'wrench'],
            'security' => ['Security', 'shield-check'],
            'uploads' => ['Uploads', 'upload-cloud'],
            'storage' => ['Storage', 'hard-drive'],
            'notifications' => ['Notifications', 'bell'],
            'backups' => ['Backups', 'database-backup'],
            'api' => ['API', 'key-round'],
            'advanced' => ['Advanced', 'sliders-horizontal'],
        ];
    }

    /**
     * Plain input settings that may be saved.
     */
    private function allowedSettings(): array
    {
        return [
            'site_name' => 'general',
            'site_email' => 'general',
            'browser_title' => 'branding',
            'meta_description' => 'branding',
            'dashboard_logo' => 'branding',
            'admin_logo' => 'branding',
            'app_icon_url' => 'branding',
            'open_graph_image' => 'branding',
            'seo_keywords' => 'branding',
            'footer_brand_name' => 'branding',
            'footer_tagline' => 'branding',
            'footer_description' => 'branding',
            'footer_logo_url' => 'branding',
            'footer_favicon_url' => 'branding',
            'footer_enabled' => 'footer',
            'footer_company_name' => 'footer',
            'footer_email' => 'footer',
            'footer_phone' => 'footer',
            'footer_website' => 'footer',
            'footer_address' => 'footer',
            'footer_google_maps_url' => 'footer',
            'footer_business_hours' => 'footer',
            'footer_platform_show' => 'footer',
            'footer_platform_title' => 'footer',
            'footer_platform_links' => 'footer',
            'footer_resources_show' => 'footer',
            'footer_resources_title' => 'footer',
            'footer_resources_links' => 'footer',
            'footer_company_show' => 'footer',
            'footer_company_title' => 'footer',
            'footer_company_links' => 'footer',
            'footer_legal_show' => 'footer',
            'footer_legal_title' => 'footer',
            'footer_legal_links' => 'footer',
            'footer_social_facebook_url' => 'footer',
            'footer_social_instagram_url' => 'footer',
            'footer_social_linkedin_url' => 'footer',
            'footer_social_github_url' => 'footer',
            'footer_social_youtube_url' => 'footer',
            'footer_social_tiktok_url' => 'footer',
            'footer_social_whatsapp_url' => 'footer',
            'footer_newsletter_title' => 'footer',
            'footer_newsletter_description' => 'footer',
            'footer_newsletter_button_text' => 'footer',
            'footer_newsletter_provider' => 'footer',
            'footer_newsletter_url' => 'footer',
            'footer_copyright_template' => 'footer',
            'footer_show_system_status' => 'footer',
            'footer_system_status_text' => 'footer',
            'footer_system_status_color' => 'footer',
            'footer_system_status_link' => 'footer',
            'footer_show_language_switcher' => 'footer',
            'footer_languages' => 'footer',
            'footer_allow_theme_switching' => 'footer',
            'footer_layout' => 'footer',
            'footer_background_color' => 'footer',
            'footer_text_color' => 'footer',
            'footer_link_color' => 'footer',
            'footer_link_hover_color' => 'footer',
            'footer_show_version' => 'footer',
            'footer_show_build_number' => 'footer',
            'footer_show_copyright' => 'footer',
            'footer_made_in_malawi' => 'footer',
            'footer_powered_by' => 'footer',
            'homepage_hero_kicker' => 'homepage',
            'homepage_hero_title' => 'homepage',
            'homepage_hero_subtitle' => 'homepage',
            'homepage_hero_note' => 'homepage',
            'homepage_cta_text' => 'homepage',
            'homepage_cta_url' => 'homepage',
            'homepage_secondary_cta_text' => 'homepage',
            'homepage_secondary_cta_url' => 'homepage',
            'homepage_hero_image' => 'homepage',
            'homepage_hero_video' => 'homepage',
            'homepage_hero_media_alt' => 'homepage',
            'homepage_hero_alignment' => 'homepage',
            'homepage_hero_overlay' => 'homepage',
            'homepage_section_heading' => 'homepage',
            'homepage_section_subheading' => 'homepage',
            'primary_color' => 'appearance',
            'secondary_color' => 'appearance',
            'text_color' => 'appearance',
            'muted_text_color' => 'appearance',
            'page_background_color' => 'appearance',
            'surface_background_color' => 'appearance',
            'surface_border_color' => 'appearance',
            'sidebar_background_color' => 'appearance',
            'sidebar_text_color' => 'appearance',
            'free_plan_price' => 'pricing',
            'free_plan_name' => 'pricing',
            'free_plan_website_limit' => 'pricing',
            'free_plan_hosting_days' => 'pricing',
            'free_plan_benefits' => 'pricing',
            'premium_plan_name' => 'pricing',
            'premium_plan_price' => 'pricing',
            'premium_plan_benefits' => 'pricing',
            'premium_plan_amount' => 'pricing',
            'paychangu_currency' => 'pricing',
            'paychangu_public_key' => 'pricing',
            'paychangu_secret_key' => 'pricing',
            'mail_host' => 'smtp',
            'mail_port' => 'smtp',
            'mail_username' => 'smtp',
            'mail_password' => 'smtp',
            'mail_encryption' => 'smtp',
            'mail_from_address' => 'smtp',
            'mail_from_name' => 'smtp',
            'admin_login_notification_email' => 'notifications',
            'recaptcha_site_key' => 'security',
            'turnstile_site_key' => 'security',
            'password_min_length' => 'security',
            'login_attempt_limit' => 'security',
            'session_lifetime_minutes' => 'security',
            'ip_blacklist' => 'security',
            'ip_whitelist' => 'security',
            'free_storage_limit_mb' => 'storage',
            'premium_storage_limit_mb' => 'storage',
            'max_upload_size_mb' => 'uploads',
            'allowed_file_extensions' => 'uploads',
            'backup_frequency' => 'backups',
            'api_rate_limit' => 'api',
            'maintenance_page_title' => 'maintenance',
            'maintenance_status_label' => 'maintenance',
            'maintenance_message' => 'maintenance',
            'maintenance_return_at' => 'maintenance',
            'maintenance_bypass_token' => 'maintenance',
            'custom_head_code' => 'advanced',
        ];
    }

    /**
     * Checkbox settings that may be saved.
     */
    private function checkboxSettings(): array
    {
        return [
            'allow_registration' => 'general',
            'maintenance_mode' => 'maintenance',
            'dark_mode_enabled' => 'appearance',
            'footer_enabled' => 'footer',
            'footer_platform_show' => 'footer',
            'footer_resources_show' => 'footer',
            'footer_company_show' => 'footer',
            'footer_legal_show' => 'footer',
            'footer_newsletter_enabled' => 'footer',
            'footer_show_system_status' => 'footer',
            'footer_show_language_switcher' => 'footer',
            'footer_allow_theme_switching' => 'footer',
            'footer_show_version' => 'footer',
            'footer_show_build_number' => 'footer',
            'footer_show_copyright' => 'footer',
            'footer_made_in_malawi' => 'footer',
            'footer_powered_by' => 'footer',
            'footer_component_brand_card' => 'footer',
            'footer_component_platform_links' => 'footer',
            'footer_component_resources' => 'footer',
            'footer_component_company' => 'footer',
            'footer_component_legal' => 'footer',
            'footer_component_social_icons' => 'footer',
            'footer_component_newsletter' => 'footer',
            'footer_component_system_status' => 'footer',
            'footer_component_copyright_bar' => 'footer',
            'smtp_enabled' => 'smtp',
            'free_plan_enabled' => 'pricing',
            'premium_plan_enabled' => 'pricing',
            'recaptcha_enabled' => 'security',
            'turnstile_enabled' => 'security',
            'two_factor_enabled' => 'security',
            'zip_upload_enabled' => 'uploads',
            'image_compression_enabled' => 'uploads',
            'email_notifications_enabled' => 'notifications',
            'admin_login_alerts_enabled' => 'notifications',
            'automatic_backups_enabled' => 'backups',
            'api_enabled' => 'api',
        ];
    }

    private function processBrandingUploads(): array
    {
        $uploads = [];
        $fields = [
            'dashboard_logo_file' => 'dashboard_logo',
            'admin_logo_file' => 'admin_logo',
            'app_icon_file' => 'app_icon_url',
        ];

        foreach ($fields as $fieldName => $settingKey) {
            if (empty($_FILES[$fieldName]['name'])) {
                continue;
            }

            $file = $_FILES[$fieldName];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                continue;
            }

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'ico'];

            if (!in_array($extension, $allowed, true)) {
                Session::flash('warning', 'The uploaded file must be an image file (PNG, JPG, GIF, WEBP, BMP, SVG, ICO).');
                continue;
            }

            if ($file['size'] > (($this->config['storage']['default_upload_file_bytes'] ?? 10 * 1024 * 1024))) {
                Session::flash('warning', 'The uploaded file is too large. Please choose a smaller image.');
                continue;
            }

            $safeName = pathinfo($file['name'], PATHINFO_FILENAME);
            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $safeName);
            $targetDir = $this->mediaUploadDir() . DIRECTORY_SEPARATOR . 'branding';

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetName = $safeName . '.' . $extension;
            $counter = 1;
            while (is_file($targetDir . DIRECTORY_SEPARATOR . $targetName)) {
                $targetName = sprintf('%s-%d.%s', $safeName, $counter++, $extension);
            }

            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $targetName;
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                Session::flash('warning', 'Unable to save uploaded file.');
                continue;
            }

            $uploads[$settingKey] = rtrim(($this->config['base_url'] ?? ''), '/') . '/uploads/branding/' . rawurlencode($targetName);
        }

        return $uploads;
    }
}
