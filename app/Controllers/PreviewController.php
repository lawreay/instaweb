<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\Portfolio;
use App\Models\AdvancedPortfolio;
use App\Core\Session;
use App\Models\Website;
use App\Services\AuthService;
use App\Services\ProjectStorageService;
use App\Services\PublicSiteUrlService;

/**
 * Serves owner-only draft previews without exposing storage paths.
 */
class PreviewController extends Controller
{
    /**
     * Show the preview wrapper for a project.
     */
    public function show(): void
    {
        [$userId, $website] = $this->requireWebsite();
        if (($website['template_key'] ?? '') === 'portfolio') {
            $portfolioModel = new Portfolio();
            $advancedModel = new AdvancedPortfolio();
            $portfolio = $portfolioModel->findForWebsiteOwner((int) $website['id'], $userId);
            if (!$portfolio) {
                Session::flash('error', 'Portfolio data is not initialized for this website.');
                $this->redirectTo('/dashboard/websites/show?id=' . (int) $website['id']);
                return;
            }

            header('Content-Type: text/html; charset=UTF-8');
            echo View::render('portfolio/public', [
                'app' => $this->config,
                'website' => $website,
                'portfolio' => $portfolio,
                'projects' => $portfolioModel->projects((int) $portfolio['id']),
                'advancedSettings' => $advancedModel->settings((int) $website['id'], $userId),
                'advancedItems' => $advancedModel->visibleItems((int) $website['id'], $userId),
                'isPreview' => true,
            ], '');
            return;
        }

        $liveUrl = ($website['status'] ?? '') === 'published'
            ? (new PublicSiteUrlService())->absoluteUrl(
                (string) ($this->config['base_url'] ?? ''),
                (int) $website['id'],
                (string) $website['slug']
            )
            : '';

        $this->view('preview/show', [
            'title' => 'Preview Website',
            'website' => $website,
            'liveUrl' => $liveUrl,
            'active' => 'websites',
        ], 'layouts/dashboard');
    }

    /**
     * Serve one preview file after ownership and path validation.
     */
    public function file(): void
    {
        [$userId, $website] = $this->requireWebsite();
        $path = (string) $this->input('path', 'index.html');

        try {
            $storage = new ProjectStorageService();
            $file = $storage->downloadableFile($userId, $website['slug'], $path);
        } catch (\Throwable) {
            http_response_code(404);
            echo 'Preview file not found.';
            return;
        }

        $extension = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
        $contentTypes = [
            'html' => 'text/html; charset=UTF-8',
            'htm' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            'json' => 'application/json; charset=UTF-8',
            'txt' => 'text/plain; charset=UTF-8',
            'md' => 'text/plain; charset=UTF-8',
            'svg' => 'image/svg+xml',
        ];

        header('Content-Type: ' . ($contentTypes[$extension] ?? $file['mime']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (in_array($extension, ['html', 'htm'], true)) {
            $html = file_get_contents($file['absolute_path']);
            echo $this->rewriteHtmlAssets($html === false ? '' : $html, (int) $website['id'], dirname($file['relative_path']));
            return;
        }

        readfile($file['absolute_path']);
    }

    /**
     * Rewrite relative HTML assets to pass through the owner-only preview file route.
     */
    private function rewriteHtmlAssets(string $html, int $websiteId, string $baseDir): string
    {
        $baseDir = $baseDir === '.' ? '' : trim($baseDir, '/');
        $route = rtrim((string) ($this->config['base_url'] ?? ''), '/') . '/dashboard/websites/preview/file?id=' . $websiteId . '&path=';

        return preg_replace_callback(
            '/\b(src|href)=["\']([^"\']+)["\']/i',
            static function (array $match) use ($route, $baseDir): string {
                $attribute = $match[1];
                $value = $match[2];

                if ($value === '' || preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:') || str_starts_with($value, '#') || str_starts_with($value, 'mailto:')) {
                    return $match[0];
                }

                $path = ltrim($value, '/');
                if ($baseDir !== '' && !str_starts_with($value, '/')) {
                    $path = $baseDir . '/' . $path;
                }

                return $attribute . '="' . htmlspecialchars($route . rawurlencode($path), ENT_QUOTES, 'UTF-8') . '"';
            },
            $html
        ) ?? $html;
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
}
