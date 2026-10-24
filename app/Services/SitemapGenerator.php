<?php

namespace App\Services;

use App\Models\Website;

/**
 * Generates a sitemap XML file for the public marketing pages and published websites.
 */
class SitemapGenerator
{
    private array $config;
    private ?Website $websiteModel = null;
    private PublicSiteUrlService $urlService;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->urlService = new PublicSiteUrlService();
    }

    /**
     * Return the sitemap XML content.
     */
    public function generate(): string
    {
        $baseUrl = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        $urls = $this->publicPageUrls($baseUrl);

        try {
            $publishedSites = $this->websiteModel()->publishedSites();
        } catch (\Throwable $exception) {
            $publishedSites = [];
        }

        foreach ($publishedSites as $website) {
            $urls[] = [
                'loc' => $this->urlService->absoluteUrl($baseUrl, (int) $website['id'], (string) $website['slug'], (string) ($website['template_key'] ?? 'blank')),
                'lastmod' => $this->formatLastModified($website['created_at'] ?? ''),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '    <url>' . PHP_EOL;
            $xml .= '        <loc>' . $this->escape($url['loc']) . '</loc>' . PHP_EOL;
            if (!empty($url['lastmod'])) {
                $xml .= '        <lastmod>' . $this->escape($url['lastmod']) . '</lastmod>' . PHP_EOL;
            }
            if (!empty($url['changefreq'])) {
                $xml .= '        <changefreq>' . $this->escape($url['changefreq']) . '</changefreq>' . PHP_EOL;
            }
            if (!empty($url['priority'])) {
                $xml .= '        <priority>' . $this->escape($url['priority']) . '</priority>' . PHP_EOL;
            }
            $xml .= '    </url>' . PHP_EOL;
        }

        $xml .= '</urlset>' . PHP_EOL;

        return $xml;
    }

    /**
     * Write the sitemap file to the public directory.
     */
    public function writeToFile(): void
    {
        $filePath = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'sitemap.xml';
        $xml = $this->generate();

        if (file_put_contents($filePath, $xml) === false) {
            throw new \RuntimeException('Unable to write sitemap.xml to public directory.');
        }
    }

    private function websiteModel(): Website
    {
        return $this->websiteModel ??= new Website();
    }

    /**
     * Ensure the sitemap file exists and can be served statically.
     */
    public function ensureFileExists(): void
    {
        $filePath = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'sitemap.xml';

        if (!is_file($filePath)) {
            $this->writeToFile();
        }
    }

    private function publicPageUrls(string $baseUrl): array
    {
        $paths = [
            '/',
            '/features',
            '/pricing',
            '/about',
            '/contact',
            '/faq',
            '/privacy',
            '/terms',
        ];

        return array_map(fn(string $path) => [
            'loc' => $baseUrl . ($path === '/' ? '' : $path),
            'lastmod' => '',
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ], $paths);
    }

    private function formatLastModified(string $datetime): string
    {
        if ($datetime === '') {
            return '';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d', $timestamp);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
