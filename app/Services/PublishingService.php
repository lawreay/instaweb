<?php

namespace App\Services;

/**
 * Publishes validated draft project files into the public static sites folder.
 */
class PublishingService
{
    private array $publishableExtensions = [
        'html', 'htm', 'css', 'js', 'json', 'txt', 'md',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg',
        'pdf', 'mp4',
    ];

    /**
     * Publish draft files to the public site path.
     */
    public function publish(int $userId, int $projectId, string $slug): string
    {
        $storage = new ProjectStorageService();
        $draftPath = $storage->projectPath($userId, $slug);

        $isWordPress = is_file($draftPath . DIRECTORY_SEPARATOR . '.turbohost-wordpress');
        if (!$isWordPress && !is_file($draftPath . DIRECTORY_SEPARATOR . 'index.html')) {
            throw new \RuntimeException('Publishing requires an index.html file.');
        }

        $publicPath = $this->publicSitePath($projectId, $slug);
        $this->replacePublicDirectory($publicPath);
        $this->copyPublishableFiles($draftPath, $publicPath, $isWordPress);

        if ($isWordPress) {
            $this->writeWordPressAccessRules($publicPath);
        }

        return (new PublicSiteUrlService())->relativePath($projectId, $slug);
    }

    /**
     * Publish a database-backed portfolio without generating static HTML copies.
     */
    public function publishPortfolio(int $projectId, string $slug): string
    {
        // Remove any older static publication so Apache cannot serve stale HTML.
        $this->unpublish($projectId, $slug);

        return 'p/' . rawurlencode($slug);
    }

    /**
     * Remove a published site directory.
     */
    public function unpublish(int $projectId, string $slug): void
    {
        $publicPath = $this->publicSitePath($projectId, $slug);

        if (is_dir($publicPath) && $this->isInsidePublicSites($publicPath)) {
            $this->deleteDirectory($publicPath);
        }
    }

    /**
     * Return the public site path for a project ID and slug.
     */
    public function publicSitePath(int $projectId, string $slug): string
    {
        return PUBLIC_PATH . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . $projectId . DIRECTORY_SEPARATOR . $slug;
    }

    /**
     * Replace the target public directory with a clean folder.
     */
    private function replacePublicDirectory(string $publicPath): void
    {
        if (is_dir($publicPath)) {
            if (!$this->isInsidePublicSites($publicPath)) {
                throw new \RuntimeException('Invalid public site path.');
            }

            $this->deleteDirectory($publicPath);
        }

        if (!mkdir($publicPath, 0755, true) && !is_dir($publicPath)) {
            throw new \RuntimeException('Unable to create public site folder.');
        }
    }

    /**
     * Copy only allowed static files from draft storage to public storage.
     */
    private function copyPublishableFiles(string $draftPath, string $publicPath, bool $isWordPress = false): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($draftPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($draftPath) + 1);
            $target = $publicPath . DIRECTORY_SEPARATOR . $relative;

            if ($relative === '.turbohost-wordpress') {
                continue;
            }

            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                    throw new \RuntimeException('Unable to create public subfolder.');
                }
                continue;
            }

            $extension = strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION));
            if (!$isWordPress && !in_array($extension, $this->publishableExtensions, true)) {
                continue;
            }

            $parent = dirname($target);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new \RuntimeException('Unable to create public subfolder.');
            }

            if (!copy($item->getPathname(), $target)) {
                throw new \RuntimeException('Unable to publish file.');
            }
        }
    }

    /**
     * Allow the WordPress project to run PHP while static projects remain PHP-disabled.
     */
    private function writeWordPressAccessRules(string $publicPath): void
    {
        $rules = <<<'HTACCESS'
Options -Indexes
DirectoryIndex index.php index.html index.htm

<FilesMatch "\.(php|phtml|phar)$">
  Require all granted
</FilesMatch>

<FilesMatch "^(wp-config\.php|\.env|\.git.*|.*\.(sql|sqlite|log|bak|ini))$">
    Require all denied
</FilesMatch>
HTACCESS;

        if (file_put_contents($publicPath . DIRECTORY_SEPARATOR . '.htaccess', $rules . PHP_EOL) === false) {
            throw new \RuntimeException('Unable to configure the WordPress site.');
        }
    }

    /**
     * Confirm a directory is inside public/sites.
     */
    private function isInsidePublicSites(string $path): bool
    {
        $rootPath = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'sites';
        if (!is_dir($rootPath)) {
            mkdir($rootPath, 0755, true);
        }

        $root = realpath($rootPath);
        $target = realpath($path);

        if (!$root || !$target) {
            return false;
        }

        return $target === $root || str_starts_with(strtolower($target), strtolower($root . DIRECTORY_SEPARATOR));
    }

    /**
     * Recursively remove a directory tree.
     */
    private function deleteDirectory(string $path): void
    {
        $items = array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
                continue;
            }

            unlink($itemPath);
        }

        rmdir($path);
    }
}
