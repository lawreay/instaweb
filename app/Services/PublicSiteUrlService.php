<?php

namespace App\Services;

/**
 * Builds canonical public URLs for static websites and database-rendered portfolios.
 */
class PublicSiteUrlService
{
    /**
     * Return the app-relative public site path.
     */
    public function relativePath(int $projectId, string $slug, string $templateKey = 'blank'): string
    {
        if ($templateKey === 'portfolio') {
            return 'p/' . rawurlencode($slug);
        }

        return 'sites/' . $projectId . '/' . rawurlencode($slug) . '/';
    }

    /**
     * Return the absolute public site URL using the configured app base URL.
     */
    public function absoluteUrl(string $baseUrl, int $projectId, string $slug, string $templateKey = 'blank'): string
    {
        return rtrim($baseUrl, '/') . '/' . $this->relativePath($projectId, $slug, $templateKey);
    }

    /**
     * Add a canonical public_url value to a website row.
     */
    public function withPublicUrl(array $website, string $baseUrl): array
    {
        $website['public_url'] = $this->absoluteUrl(
            $baseUrl,
            (int) ($website['id'] ?? 0),
            (string) ($website['slug'] ?? ''),
            (string) ($website['template_key'] ?? 'blank')
        );

        return $website;
    }
}
