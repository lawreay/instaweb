<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Portfolio;
use App\Models\Website;
use App\Services\AuthService;

/**
 * Owner-only editing workflow for database-backed portfolio websites.
 */
class PortfolioController extends Controller
{
    private const TEMPLATES = [
        'classic' => 'Classic',
        'editorial' => 'Editorial',
        '3d' => '3D Motion',
    ];

    public function edit(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $model = new Portfolio();

        $this->view('portfolio/edit', [
            'title' => 'Edit Portfolio',
            'website' => $website,
            'portfolio' => $portfolio,
            'projects' => $model->projects((int) $portfolio['id']),
            'templates' => self::TEMPLATES,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    public function save(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
        }

        $displayName = trim((string) $this->input('display_name', ''));
        $headline = trim((string) $this->input('headline', ''));
        $bio = trim((string) $this->input('bio', ''));
        $email = trim((string) $this->input('email', ''));
        $location = trim((string) $this->input('location', ''));
        $templateKey = trim((string) $this->input('template_key', 'classic'));

        $socialLinks = [];
        foreach (['github', 'linkedin', 'instagram', 'facebook', 'website'] as $platform) {
            $url = trim((string) $this->input('social_' . $platform, ''));
            if ($url === '') {
                continue;
            }
            if (strlen($url) > 500 || filter_var($url, FILTER_VALIDATE_URL) === false
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                Session::flash('error', 'Social links must be valid HTTP or HTTPS URLs.');
                $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
            }
            $socialLinks[$platform] = $url;
        }

        if ($displayName === '' || strlen($displayName) > 120
            || strlen($headline) > 180
            || strlen($bio) > 10000
            || strlen($email) > 190
            || strlen($location) > 120
            || !array_key_exists($templateKey, self::TEMPLATES)
            || ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            Session::flash('error', 'Check the profile fields. The name is required, the email must be valid, and the selected template must be available.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
        }

        (new Portfolio())->updateProfile(
            (int) $portfolio['id'],
            (int) $website['id'],
            $userId,
            [
                'display_name' => $displayName,
                'headline' => $headline,
                'bio' => $bio,
                'email' => $email !== '' ? $email : null,
                'location' => $location !== '' ? $location : null,
                'template_key' => $templateKey,
                'social_links' => $socialLinks,
            ]
        );

        Session::flash('success', 'Portfolio profile saved.');
        $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
    }

    /**
     * Upload a profile image for the portfolio owned by the current user.
     * Only raster images are accepted; SVG and executable formats are rejected.
     */
    public function uploadAvatar(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        $file = $_FILES['avatar'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))
            || (int) ($file['size'] ?? 0) < 1 || (int) $file['size'] > 3 * 1024 * 1024) {
            Session::flash('error', 'Choose a profile image smaller than 3 MB.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        $tmpPath = (string) $file['tmp_name'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);
        $imageInfo = @getimagesize($tmpPath);
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!is_string($mime) || !isset($allowedTypes[$mime]) || $imageInfo === false
            || ($imageInfo['mime'] ?? '') !== $mime
            || (int) ($imageInfo[0] ?? 0) > 6000 || (int) ($imageInfo[1] ?? 0) > 6000) {
            Session::flash('error', 'Use a valid JPEG, PNG, or WebP image up to 6000 × 6000 pixels.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        $relativeDirectory = 'uploads/portfolios/' . $websiteId;
        $targetDirectory = dirname(__DIR__, 2) . '/public/' . $relativeDirectory;
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            Session::flash('error', 'The profile image directory could not be created.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            Session::flash('error', 'The profile image could not be saved.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        $relativePath = $relativeDirectory . '/' . $filename;
        $saved = (new Portfolio())->updateAvatarForOwner(
            (int) $portfolio['id'],
            $websiteId,
            $userId,
            $relativePath
        );

        if (!$saved) {
            @unlink($targetPath);
            Session::flash('error', 'The profile image could not be linked to this portfolio.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        Session::flash('success', 'Profile image uploaded.');
        $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
    }

    public function addProject(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
        }

        $title = trim((string) $this->input('project_title', ''));
        $summary = trim((string) $this->input('project_summary', ''));
        $url = trim((string) $this->input('project_url', ''));

        if ($title === '' || strlen($title) > 150 || strlen($summary) > 5000
            || strlen($url) > 500
            || ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)))) {
            Session::flash('error', 'Enter a project title and a valid HTTP or HTTPS project link.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
        }

        $added = (new Portfolio())->addProjectForOwner(
            (int) $portfolio['id'],
            (int) $website['id'],
            $userId,
            [
                'title' => $title,
                'summary' => $summary,
                'project_url' => $url !== '' ? $url : null,
            ]
        );

        Session::flash($added ? 'success' : 'error', $added ? 'Project added to your portfolio.' : 'The project could not be added to this portfolio.');
        $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
    }

    public function saveProject(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();
        $websiteId = (int) $website['id'];

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        $projectId = (int) $this->input('project_id', 0);
        $title = trim((string) $this->input('project_title', ''));
        $summary = trim((string) $this->input('project_summary', ''));
        $url = trim((string) $this->input('project_url', ''));
        $sortOrder = max(0, min(9999, (int) $this->input('sort_order', 0)));

        if ($projectId < 1 || $title === '' || strlen($title) > 150 || strlen($summary) > 5000
            || strlen($url) > 500
            || ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)))) {
            Session::flash('error', 'Check the project title, description, and HTTP or HTTPS link.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
        }

        $saved = (new Portfolio())->updateProjectForOwner(
            $projectId,
            (int) $portfolio['id'],
            $websiteId,
            $userId,
            [
                'title' => $title,
                'summary' => $summary,
                'project_url' => $url !== '' ? $url : null,
                'sort_order' => $sortOrder,
            ]
        );

        Session::flash($saved ? 'success' : 'error', $saved ? 'Project updated.' : 'Project not found or could not be updated.');
        $this->redirectTo('/dashboard/websites/portfolio?id=' . $websiteId);
    }

    public function deleteProject(): void
    {
        [$userId, $website, $portfolio] = $this->ownedPortfolio();

        if (!Csrf::validate($this->input('_csrf'))) {
            Session::flash('error', 'Your session expired. Please try again.');
            $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
        }

        $deleted = (new Portfolio())->deleteProjectForOwner(
            (int) $this->input('project_id', 0),
            (int) $portfolio['id'],
            (int) $website['id'],
            $userId
        );

        Session::flash($deleted ? 'success' : 'error', $deleted ? 'Project removed from your portfolio.' : 'Project not found or you do not have permission to remove it.');
        $this->redirectTo('/dashboard/websites/portfolio?id=' . (int) $website['id']);
    }

    /**
     * Resolve a portfolio only when the authenticated user owns its website.
     */
    private function ownedPortfolio(): array
    {
        if (!AuthService::check()) {
            $this->redirectTo('/login');
        }

        $userId = (int) AuthService::id();
        $websiteId = (int) $this->input('id', $this->input('website_id', 0));
        $website = (new Website())->findForUser($websiteId, $userId);

        if (!$website || ($website['template_key'] ?? '') !== 'portfolio') {
            Session::flash('error', 'Portfolio website not found.');
            $this->redirectTo('/dashboard/websites');
        }

        $portfolio = (new Portfolio())->findForWebsiteOwner($websiteId, $userId);
        if (!$portfolio) {
            Session::flash('error', 'Portfolio data is not initialized for this website. Recreate the portfolio project or contact support.');
            $this->redirectTo('/dashboard/websites/show?id=' . $websiteId);
        }

        return [$userId, $website, $portfolio];
    }
}
