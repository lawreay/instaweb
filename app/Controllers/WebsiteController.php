<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\NotificationManager;
use App\Services\PlanService;
use App\Services\PublicSiteUrlService;
use App\Services\ProjectStorageService;
use App\Services\PublishingService;
use App\Services\SitemapGenerator;

/**
 * Handles authenticated website project management.
 */
class WebsiteController extends Controller
{
    /**
     * Show all website projects for the signed-in user.
     */
    public function index(): void
    {
        $userId = $this->requireUser();
        $websiteModel = new Website();
        $planService = new PlanService();
        $plan = $websiteModel->userPlan($userId);
        $urlService = new PublicSiteUrlService();
        $websites = array_map(
            fn (array $website): array => $urlService->withPublicUrl($website, (string) ($this->config['base_url'] ?? '')),
            $websiteModel->forUser($userId)
        );

        $this->view('websites/index', [
            'title' => 'My Websites',
            'websites' => $websites,
            'plan' => $plan,
            'planDetails' => $planService->plans(),
            'canCreate' => $plan === 'premium' || $websiteModel->activeCountForUser($userId) < $planService->freeWebsiteLimit(),
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Show the create project form.
     */
    public function create(): void
    {
        $userId = $this->requireUser();
        $websiteModel = new Website();
        $planService = new PlanService();
        $plan = $websiteModel->userPlan($userId);

        $this->view('websites/create', [
            'title' => 'Create Website',
            'plan' => $plan,
            'planDetails' => $planService->plans(),
            'canCreate' => $plan === 'premium' || $websiteModel->activeCountForUser($userId) < $planService->freeWebsiteLimit(),
            'templates' => $this->templates(),
            'old' => $this->request(),
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Store a new website project and starter draft files.
     */
    public function store(): void
    {
        $userId = $this->requireUser();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/create');
        }

        $validator = new Validator($this->request());
        $validator->validate([
            'website_name' => 'required|min:3|max:120',
            'slug' => 'max:150',
            'template' => 'required|max:40',
        ]);

        $template = trim((string) $this->input('template', 'blank'));
        if (!array_key_exists($template, $this->templates())) {
            $validator->add('template', 'Select a valid template.');
        }

        $websiteModel = new Website();
        $planService = new PlanService();
        $plan = $websiteModel->userPlan($userId);
        $premium = $plan === 'premium';

        if (!$premium && $websiteModel->activeCountForUser($userId) >= $planService->freeWebsiteLimit()) {
            $validator->add('plan', sprintf('Free plan allows %d active website(s). Upgrade to Premium for more projects.', $planService->freeWebsiteLimit()));
        }

        $name = trim((string) $this->input('website_name'));
        $slugSource = trim((string) $this->input('slug')) ?: $name;
        $slug = $this->uniqueSlug($websiteModel, $slugSource);

        if ($slug === '') {
            $validator->add('slug', 'Enter a valid website slug.');
        }

        if ($validator->errors() !== []) {
            Session::flash('error', implode(' ', $validator->messages()));
            $this->redirectTo('/dashboard/websites/create');
        }

        $storage = new ProjectStorageService();
        $websiteId = $websiteModel->createProject($userId, $name, $slug, $premium, $planService->freeHostingDays());

        try {
            $projectPath = $storage->ensureProjectDirectory($userId, $slug);
            $starterSize = $template === 'wordpress'
                ? $storage->installWordPress($projectPath)
                : $storage->createStarterIndex($projectPath, $name, $template);
            $projectSize = $storage->directorySize($storage->projectPath($userId, $slug));
            $websiteModel->addFileRecord($websiteId, $template === 'wordpress' ? 'wp-config.php' : 'index.html', $template === 'wordpress' ? 'wp-config.php' : 'index.html', $template === 'wordpress' ? 'php' : 'html', $starterSize);
            $websiteModel->updateStorageUsed($websiteId, $projectSize);
        } catch (\Throwable $exception) {
            $storage->deleteProjectDirectory($userId, $slug);
            $websiteModel->deleteForUser($websiteId, $userId);
            Session::flash('error', 'Website record was created, but the draft folder could not be prepared.');
            $this->redirectTo('/dashboard/websites/create');
        }

        $notificationManager = new NotificationManager($this->config);
        $notificationManager->sendUserNotification(
            $userId,
            'Website created',
            sprintf('Your website project "%s" has been created and is ready to edit.', $name),
            [
                'category' => 'website',
                'icon' => 'file-plus',
                'target_url' => '/dashboard/websites/show?id=' . $websiteId,
            ]
        );

        Session::flash('success', $template === 'wordpress'
            ? 'WordPress project created. Publish it, then complete the WordPress setup in your site URL.'
            : 'Website project created. Your draft index.html is ready.');
        $this->redirectTo('/dashboard/websites/show?id=' . $websiteId);
    }

    /**
     * Show one website project summary.
     */
    public function show(): void
    {
        $userId = $this->requireUser();
        $website = (new Website())->findForUser((int) $this->input('id', 0), $userId);

        if (!$website) {
            Session::flash('error', 'Website project not found.');
            $this->redirectTo('/dashboard/websites');
        }

        $this->view('websites/show', [
            'title' => $website['website_name'] ?: 'Website Project',
            'website' => $website,
            'draftPath' => (new ProjectStorageService())->projectPath($userId, $website['slug']),
            'liveUrl' => ($website['status'] ?? '') === 'published'
                ? (new PublicSiteUrlService())->absoluteUrl(
                    (string) ($this->config['base_url'] ?? ''),
                    (int) $website['id'],
                    (string) $website['slug']
                )
                : '',
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Show the edit metadata form.
     */
    public function edit(): void
    {
        $userId = $this->requireUser();
        $website = (new Website())->findForUser((int) $this->input('id', 0), $userId);

        if (!$website) {
            Session::flash('error', 'Website project not found.');
            $this->redirectTo('/dashboard/websites');
        }

        $this->view('websites/edit', [
            'title' => 'Edit Website',
            'website' => $website,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Save editable project metadata.
     */
    public function update(): void
    {
        $userId = $this->requireUser();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites');
        }

        $websiteId = (int) $this->input('id', 0);
        $websiteModel = new Website();
        $website = $websiteModel->findForUser($websiteId, $userId);

        if (!$website) {
            Session::flash('error', 'Website project not found.');
            $this->redirectTo('/dashboard/websites');
        }

        $validator = new Validator($this->request());
        $validator->validate(['website_name' => 'required|min:3|max:120']);

        if ($validator->errors() !== []) {
            Session::flash('error', implode(' ', $validator->messages()));
            $this->redirectTo('/dashboard/websites/edit?id=' . $websiteId);
        }

        $websiteModel->updateProject($websiteId, $userId, trim((string) $this->input('website_name')));
        (new NotificationManager($this->config))->sendUserNotification(
            $userId,
            'Website updated',
            'Your website project details were updated successfully.',
            [
                'category' => 'website',
                'icon' => 'edit-3',
                'target_url' => '/dashboard/websites/show?id=' . $websiteId,
            ]
        );

        Session::flash('success', 'Website project updated.');
        $this->redirectTo('/dashboard/websites/show?id=' . $websiteId);
    }

    /**
     * Delete a project record and its draft files.
     */
    public function delete(): void
    {
        $userId = $this->requireUser();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites');
        }

        $websiteId = (int) $this->input('id', 0);
        $websiteModel = new Website();
        $website = $websiteModel->findForUser($websiteId, $userId);

        if (!$website) {
            Session::flash('error', 'Website project not found.');
            $this->redirectTo('/dashboard/websites');
        }

        if (($website['status'] ?? '') === 'published') {
            (new PublishingService())->unpublish((int) $website['id'], $website['slug']);
        }

        (new ProjectStorageService())->deleteProjectDirectory($userId, $website['slug']);
        $websiteModel->deleteForUser($websiteId, $userId);
        try {
            (new SitemapGenerator($this->config))->writeToFile();
        } catch (\Throwable $exception) {
            // Sitemap regeneration should not block project deletion.
        }
        (new NotificationManager($this->config))->sendUserNotification(
            $userId,
            'Website deleted',
            sprintf('Your website project "%s" was deleted.', $website['website_name'] ?? 'Untitled project'),
            [
                'category' => 'website',
                'icon' => 'trash-2',
                'target_url' => '/dashboard/websites',
            ]
        );

        Session::flash('success', 'Website project deleted.');
        $this->redirectTo('/dashboard/websites');
    }

    /**
     * Require an authenticated user and return their ID.
     */
    private function requireUser(): int
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        return (int) AuthService::id();
    }

    /**
     * Return supported starter templates.
     */
    private function templates(): array
    {
        return [
            'blank' => 'Blank',
            'portfolio' => 'Portfolio',
            'business' => 'Business',
            'church' => 'Church',
            'blog' => 'Blog',
            'school' => 'School project',
            'wordpress' => 'WordPress',
        ];
    }

    /**
     * Generate a URL-safe slug and avoid existing global slug collisions.
     */
    private function uniqueSlug(Website $websiteModel, string $source): string
    {
        $base = strtolower(trim($source));
        $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
        $base = trim($base, '-');
        $base = substr($base, 0, 80);

        if ($base === '') {
            return '';
        }

        $slug = $base;
        $counter = 2;

        while ($websiteModel->slugExists($slug)) {
            $suffix = '-' . $counter++;
            $slug = substr($base, 0, 80 - strlen($suffix)) . $suffix;
        }

        return $slug;
    }
}
