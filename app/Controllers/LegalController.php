<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Models\LegalPolicy;
use App\Models\LegalPolicyVersion;
use App\Models\PolicyAuditLog;
use App\Models\Setting;
use App\Models\UserPolicyAcceptance;
use App\Services\AuthService;

/**
 * Handles legal policy pages, admin publishing, and acceptance flow.
 */
class LegalController extends Controller
{
    public function privacy(): void
    {
        $this->showPublicPolicy('privacy', 'Privacy Policy');
    }

    public function terms(): void
    {
        $this->showPublicPolicy('terms', 'Terms and Conditions');
    }

    public function cookie(): void
    {
        $this->showPublicPolicy('cookie', 'Cookie Policy');
    }

    public function acceptableUse(): void
    {
        $this->showPublicPolicy('acceptable_use', 'Acceptable Use Policy');
    }

    public function copyright(): void
    {
        $this->showPublicPolicy('copyright', 'Copyright and Takedown Policy');
    }

    private function showPublicPolicy(string $policyType, string $fallbackTitle): void
    {
        $policy = $this->loadPolicy($policyType);

        if (!$policy) {
            http_response_code(404);
            echo htmlspecialchars($fallbackTitle, ENT_QUOTES, 'UTF-8') . ' not found.';
            return;
        }

        $this->view('legal/privacy', [
            'title' => $policy['title'] ?: $fallbackTitle,
            'policy' => $policy,
            'settings' => (new Setting())->all(),
        ]);
    }

    public function showAcceptancePage(): void
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        $publishedPolicies = $this->loadPublishedPolicies();

        if ($publishedPolicies === []) {
            $this->redirectTo('/dashboard');
            return;
        }

        $this->view('legal/accept', [
            'title' => 'Accept Updated Policies',
            'policies' => $publishedPolicies,
        ]);
    }

    public function accept(): void
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/legal/accept');
            return;
        }

        $policyTypes = $this->request()['accepted_policy_types'] ?? [];
        if (!is_array($policyTypes)) {
            $policyTypes = [$policyTypes];
        }

        $publishedPolicies = $this->loadPublishedPolicies();
        $policyTypes = array_map('trim', $policyTypes);
        $acceptedCount = 0;

        foreach ($publishedPolicies as $publishedPolicy) {
            if (!in_array($publishedPolicy['policy_type'], $policyTypes, true)) {
                continue;
            }

            $acceptance = new UserPolicyAcceptance();
            $acceptance->recordAcceptance(
                (int) AuthService::id(),
                (int) $publishedPolicy['policy_id'],
                (int) $publishedPolicy['version_id']
            );
            (new PolicyAuditLog())->logAcceptance(
                (int) $publishedPolicy['policy_id'],
                (int) $publishedPolicy['version_id'],
                (int) AuthService::id()
            );
            $acceptedCount++;
        }

        if ($acceptedCount === 0) {
            Session::flash('error', 'You must accept at least one current policy to continue.');
            $this->redirectTo('/legal/accept');
            return;
        }

        Session::flash('success', 'Thank you. Your acceptance has been recorded.');
        $this->redirectTo('/dashboard');
    }

    private function loadPublishedPolicies(): array
    {
        $policyModel = new LegalPolicy();
        $versionModel = new LegalPolicyVersion();

        $policies = $policyModel->all();
        $publishedPolicies = [];

        foreach ($policies as $policy) {
            $published = $versionModel->publishedVersion((int) $policy['id']);
            if (!$published) {
                continue;
            }

            $publishedPolicies[] = [
                'policy_id' => (int) $policy['id'],
                'policy_type' => $policy['policy_type'],
                'title' => $policy['title'],
                'version_id' => (int) $published['id'],
                'version_label' => $published['version_label'],
                'content' => $published['content'],
                'published_at' => $published['created_at'],
            ];
        }

        return $publishedPolicies;
    }

    public function adminIndex(): void
    {
        $this->adminOnly();

        $policyModel = new LegalPolicy();
        $policies = $policyModel->all();
        $this->view('admin/legal/index', [
            'title' => 'Legal Policies',
            'policies' => $policies,
            'active' => 'legal',
        ], 'layouts/admin');
    }

    public function editPolicy(): void
    {
        $this->adminOnly();

        $policyId = (int) $this->input('id', 0);

        if ($policyId === 0) {
            $this->view('admin/legal/edit', [
                'title' => 'Create Policy',
                'policy' => [],
                'versions' => [],
                'active' => 'legal',
            ], 'layouts/admin');
            return;
        }

        $policy = (new LegalPolicy())->find($policyId);
        if (!$policy) {
            Session::flash('error', 'Policy not found.');
            $this->redirectTo('/admin/legal');
            return;
        }

        $versions = (new LegalPolicyVersion())->versionsForPolicy($policyId);
        $this->view('admin/legal/edit', [
            'title' => 'Edit Policy',
            'policy' => $policy,
            'versions' => $versions,
            'active' => 'legal',
        ], 'layouts/admin');
    }

    public function savePolicy(): void
    {
        $this->adminOnly();
        $this->validateCsrf('/admin/legal');

        $policyId = (int) $this->input('policy_id', 0);
        $policyType = trim((string) $this->input('policy_type'));
        $title = trim((string) $this->input('title'));
        $content = trim((string) $this->input('content'));
        $versionLabel = trim((string) $this->input('version_label')) ?: 'Draft';
        $publish = $this->input('publish') === '1';

        if ($title === '' || $content === '' || $policyType === '') {
            Session::flash('error', 'Title, type, and content are required.');
            $this->redirectTo('/admin/legal');
            return;
        }

        $policyModel = new LegalPolicy();
        $versionModel = new LegalPolicyVersion();
        $auditLog = new PolicyAuditLog();

        if ($policyId === 0) {
            $policyId = $policyModel->create($policyType, $title);
        } else {
            $policyModel->updateTitle($policyId, $title);
        }

        $versionId = $versionModel->create($policyId, $versionLabel, $content, $publish);

        if ($publish) {
            $versionModel->publish($versionId);
            $auditLog->logPublish($policyId, $versionId, (int) AuthService::id());
            // Notify all users that policies were updated and require acceptance
            try {
                $publishedPolicies = $this->loadPublishedPolicies();
                $this->notifyAllUsersOnPublish($publishedPolicies);
            } catch (\Throwable) {
                // preserve normal flow even if notifications fail
            }
        }

        Session::flash('success', 'Policy version saved successfully.');
        $this->redirectTo('/admin/legal');
    }

    public function publishVersion(): void
    {
        $this->adminOnly();
        $this->validateCsrf('/admin/legal');

        $versionId = (int) $this->input('version_id', 0);
        $versionModel = new LegalPolicyVersion();
        $version = $versionModel->find($versionId);

        if (!$version) {
            Session::flash('error', 'Policy version not found.');
            $this->redirectTo('/admin/legal');
            return;
        }

        $versionModel->publish($versionId);
        (new PolicyAuditLog())->logPublish((int) $version['policy_id'], $versionId, (int) AuthService::id());

        // Notify all users after publishing a version
        try {
            $publishedPolicies = $this->loadPublishedPolicies();
            $this->notifyAllUsersOnPublish($publishedPolicies);
        } catch (\Throwable) {
            // ignore notification errors
        }

        Session::flash('success', 'Policy version published successfully.');
        $this->redirectTo('/admin/legal');
    }

    /**
     * Notify all users by in-app notification and email about updated published policies.
     * This also prompts users to accept the new policies when they next visit the dashboard.
     */
    private function notifyAllUsersOnPublish(array $publishedPolicies): void
    {
        $notificationService = new \App\Services\NotificationService($this->config);
        $users = (new \App\Models\User())->allIds();

        $subject = 'Updated platform policies';
        $title = 'Platform policies updated';
        $message = 'We have updated our platform policies. Please review and accept the changes to continue using Instaweb.';

        foreach ($users as $user) {
            $userId = (int) ($user['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            try {
                $notificationService->notifyUserWithEmail(
                    $userId,
                    $title,
                    $message,
                    $subject,
                    'policy-update',
                    ['policies' => $publishedPolicies, 'message' => $message],
                    'legal',
                    'file-text',
                    '/legal/accept'
                );
            } catch (\Throwable) {
                // continue notifying other users even if one fails
            }
        }
    }

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

    private function validateCsrf(string $redirectPath): void
    {
        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo($redirectPath);
        }
    }

    private function loadPolicy(string $policyType): ?array
    {
        $policy = (new LegalPolicy())->findByType($policyType);
        if (!$policy) {
            return null;
        }

        $versionModel = new LegalPolicyVersion();
        $published = $versionModel->publishedVersion((int) $policy['id']);
        if (!$published) {
            return null;
        }

        return [
            'policy_id' => (int) $policy['id'],
            'policy_type' => $policy['policy_type'],
            'title' => $policy['title'],
            'version_id' => (int) $published['id'],
            'version_label' => $published['version_label'],
            'content' => $published['content'],
            'published_at' => $published['created_at'],
        ];
    }
}
