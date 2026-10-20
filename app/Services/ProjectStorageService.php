<?php

namespace App\Services;

/**
 * Builds and manages safe draft storage paths for website projects.
 */
class ProjectStorageService
{
    /**
     * Return the root folder for all draft projects.
     */
    public function projectsRoot(): string
    {
        return STORAGE_PATH . DIRECTORY_SEPARATOR . 'projects';
    }

    /**
     * Return a draft project directory from trusted user and slug values.
     */
    public function projectPath(int $userId, string $slug): string
    {
        return $this->projectsRoot() . DIRECTORY_SEPARATOR . $userId . DIRECTORY_SEPARATOR . $slug;
    }

    /**
     * Resolve a user-supplied relative path safely inside a project.
     */
    public function resolveProjectPath(int $userId, string $slug, string $relativePath = ''): string
    {
        $projectPath = $this->ensureProjectDirectory($userId, $slug);
        $safeRelativePath = $this->normalizeRelativePath($relativePath);

        return $safeRelativePath === ''
            ? $projectPath
            : $projectPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safeRelativePath);
    }

    /**
     * Create the draft project directory if needed.
     */
    public function ensureProjectDirectory(int $userId, string $slug): string
    {
        $path = $this->projectPath($userId, $slug);

        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException('Unable to create project folder.');
        }

        return $path;
    }

    /**
     * Create the starter index file for a new project.
     */
    public function createStarterIndex(string $projectPath, string $websiteName, string $template): int
    {
        $templateLabel = $this->templateLabel($template);
        $safeTitle = htmlspecialchars($websiteName, ENT_QUOTES, 'UTF-8');
        $content = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle}</title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      font-family: Arial, sans-serif;
      background: #F8FAFC;
      color: #e2dddd;
    }
    main {
      max-width: 720px;
      padding: 40px;
      text-align: center;
    }
    h1 {
      color: #0D6EFD;
    }
  </style>
</head>
<body>
  <main>
    <p>{$templateLabel} starter project</p>
    <h1>{$safeTitle}</h1>
    <p>Your Instaweb website is ready. Edit this file, preview your changes, and publish when you are done.</p>
  </main>
</body>
</html>
HTML;

        $target = $projectPath . DIRECTORY_SEPARATOR . 'index.html';
        if (file_put_contents($target, $content) === false) {
            throw new \RuntimeException('Unable to create starter index.html.');
        }

        return filesize($target) ?: strlen($content);
    }

    /**
     * Install the preserved WordPress package into a project directory.
     */
    public function installWordPress(string $projectPath): int
    {
        $configuredPackage = (string) env('WORDPRESS_PACKAGE_PATH', 'storage/wordpress/wordpress-6.9.3.zip');
        $packagePath = str_starts_with($configuredPackage, DIRECTORY_SEPARATOR)
            ? $configuredPackage
            : ROOT_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configuredPackage);
        if (!is_file($packagePath)) {
            throw new \RuntimeException('The preserved WordPress package is missing.');
        }

        $expectedHash = strtolower(trim((string) env('WORDPRESS_PACKAGE_SHA256', '')));
        if ($expectedHash === '' || !preg_match('/^[a-f0-9]{64}$/', $expectedHash)) {
            throw new \RuntimeException('The WordPress package checksum is not configured.');
        }
        $actualHash = hash_file('sha256', $packagePath);
        if (!is_string($actualHash) || !hash_equals($expectedHash, strtolower($actualHash))) {
            throw new \RuntimeException('The WordPress package checksum failed.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new \RuntimeException('Unable to open the WordPress package.');
        }

        $temporaryPath = $projectPath . '.install-' . bin2hex(random_bytes(8));
        if (!mkdir($temporaryPath, 0755, true) && !is_dir($temporaryPath)) {
            throw new \RuntimeException('Unable to prepare the temporary WordPress folder.');
        }

        // Project backups keep WordPress files at the archive root, while the
        // preserved installer package keeps them in a `wordpress/` directory.
        // Support both trusted layouts so a preserved project backup can be
        // restored as the package when necessary.
        $archivePrefix = $zip->locateName('wordpress/') !== false ? 'wordpress/' : '';
        $totalBytes = 0;
        $fileCount = 0;
        $maxBytes = max(1, (int) env('WORDPRESS_MAX_EXTRACTED_BYTES', 512 * BYTES_PER_MB));
        $maxFiles = max(1, (int) env('WORDPRESS_MAX_FILES', 20000));
        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = str_replace('\\', '/', (string) $zip->getNameIndex($index));
                if (!str_starts_with($name, $archivePrefix)) {
                    continue;
                }

                $relative = substr($name, strlen($archivePrefix));
                if ($relative === '') {
                    continue;
                }
                if (str_contains($relative, "\0") || preg_match('#(^|/)\.\.?(/|$)#', $relative)) {
                    throw new \RuntimeException('The WordPress package contains an unsafe path.');
                }

                if ($fileCount >= $maxFiles) {
                    throw new \RuntimeException('The WordPress package contains too many files.');
                }
                $entry = $zip->statIndex($index);
                $entryBytes = (int) ($entry['size'] ?? 0);
                if ($entryBytes < 0 || $totalBytes > $maxBytes - $entryBytes) {
                    throw new \RuntimeException('The WordPress package is too large to extract.');
                }
                $externalAttributes = 0;
                $attributeType = 0;
                if ($zip->getExternalAttributesIndex($index, $attributeType, $externalAttributes)
                    && $attributeType === \ZipArchive::OPSYS_UNIX
                    && (($externalAttributes >> 16) & 0120000) === 0120000) {
                    throw new \RuntimeException('The WordPress package contains a symbolic link.');
                }

                $target = $temporaryPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (str_ends_with($relative, '/')) {
                    if (!is_dir($target) && !mkdir($target, 0755, true) && !is_dir($target)) {
                        throw new \RuntimeException('Unable to create the WordPress folder.');
                    }
                    continue;
                }

                $parent = dirname($target);
                if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                    throw new \RuntimeException('Unable to create the WordPress folder.');
                }

                $stream = $zip->getStream($name);
                $file = $stream ? fopen($target, 'wb') : false;
                if (!$stream || !$file) {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                    throw new \RuntimeException('Unable to extract the WordPress package.');
                }
                $totalBytes += stream_copy_to_stream($stream, $file);
                $fileCount++;
                fclose($file);
                fclose($stream);
            }
        } catch (\Throwable $exception) {
            $this->deleteProjectTree($temporaryPath);
            throw $exception;
        } finally {
            $zip->close();
        }

        foreach (['index.php', 'wp-admin', 'wp-includes', 'wp-content', 'wp-settings.php', 'wp-login.php'] as $required) {
            if (!file_exists($temporaryPath . DIRECTORY_SEPARATOR . $required)) {
                $this->deleteProjectTree($temporaryPath);
                throw new \RuntimeException('The WordPress package is incomplete.');
            }
        }

        $prefix = 'wp_' . bin2hex(random_bytes(8)) . '_';
        $config = "<?php\n";
        $config .= "define('DB_NAME', " . var_export((string) env('DB_NAME', 'turbohostmw'), true) . ");\n";
        $config .= "define('DB_USER', " . var_export((string) env('DB_USER', ''), true) . ");\n";
        $config .= "define('DB_PASSWORD', " . var_export((string) env('DB_PASS', ''), true) . ");\n";
        $config .= "define('DB_HOST', " . var_export((string) env('DB_HOST', 'localhost'), true) . ");\n";
        $config .= "define('DB_CHARSET', 'utf8mb4');\ndefine('DB_COLLATE', '');\n";
        $config .= "\$table_prefix = " . var_export($prefix, true) . ";\n";
        $config .= "define('WP_DEBUG', false);\n\n";
        $config .= "if (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }\nrequire_once ABSPATH . 'wp-settings.php';\n";

        if (file_put_contents($temporaryPath . DIRECTORY_SEPARATOR . 'wp-config.php', $config) === false
            || file_put_contents($temporaryPath . DIRECTORY_SEPARATOR . '.turbohost-wordpress', $prefix) === false) {
            $this->deleteProjectTree($temporaryPath);
            throw new \RuntimeException('Unable to create the WordPress configuration.');
        }

        if (is_dir($projectPath)) {
            $this->deleteProjectTree($projectPath);
        }
        if (!rename($temporaryPath, $projectPath)) {
            $this->deleteProjectTree($temporaryPath);
            throw new \RuntimeException('Unable to finalize the WordPress project.');
        }

        return $totalBytes + strlen($config) + strlen($prefix);
    }

    private function deleteProjectTree(string $path): bool
    {
        if (!is_dir($path)) {
            return true;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath) && !is_link($itemPath)) {
                if (!$this->deleteProjectTree($itemPath)) {
                    return false;
                }
            } elseif (!unlink($itemPath)) {
                return false;
            }
        }

        return rmdir($path);
    }

    /**
     * Normalize a browser-submitted project-relative path.
     */
    public function normalizeRelativePath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = trim($path, '/');
        $segments = array_values(array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== ''));
        $clean = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..' || str_contains($segment, "\0")) {
                throw new \InvalidArgumentException('Invalid file path.');
            }

            $safe = preg_replace('/[^A-Za-z0-9_\-\. ]/', '_', $segment);
            $safe = trim((string) $safe);

            if ($safe === '' || str_starts_with($safe, '.')) {
                throw new \InvalidArgumentException('Invalid file path.');
            }

            $clean[] = $safe;
        }

        return implode('/', $clean);
    }

    /**
     * Return a recursive file/folder listing for display.
     */
    public function listProjectFiles(int $userId, string $slug): array
    {
        $projectPath = $this->ensureProjectDirectory($userId, $slug);
        $items = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($projectPath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($projectPath) + 1));
            $items[] = [
                'name' => $item->getFilename(),
                'path' => $relative,
                'type' => $item->isDir() ? 'folder' : 'file',
                'size' => $item->isFile() ? $item->getSize() : 0,
                'modified_at' => date('Y-m-d H:i:s', $item->getMTime()),
                'extension' => $item->isFile() ? strtolower(pathinfo($item->getFilename(), PATHINFO_EXTENSION)) : '',
            ];
        }

        usort($items, static function (array $a, array $b): int {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'folder' ? -1 : 1;
            }

            return strcasecmp($a['path'], $b['path']);
        });

        return $items;
    }

    /**
     * Calculate a project folder's total file size.
     */
    public function directorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $bytes = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $bytes += $item->getSize();
            }
        }

        return $bytes;
    }

    /**
     * Write a starter text file inside a project.
     */
    public function createTextFile(int $userId, string $slug, string $relativePath): int
    {
        $safeRelativePath = $this->normalizeRelativePath($relativePath);
        $targetPath = $this->resolveProjectPath($userId, $slug, $safeRelativePath);
        $parent = dirname($targetPath);

        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new \RuntimeException('Unable to create folder.');
        }

        if (is_file($targetPath) || is_dir($targetPath)) {
            throw new \RuntimeException('A file or folder with that name already exists.');
        }

        $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        $content = $extension === 'html'
            ? "<!doctype html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <title>New page</title>\n</head>\n<body>\n  <h1>New page</h1>\n</body>\n</html>\n"
            : '';

        if (file_put_contents($targetPath, $content) === false) {
            throw new \RuntimeException('Unable to create file.');
        }

        return filesize($targetPath) ?: strlen($content);
    }

    /**
     * Create a folder inside a project.
     */
    public function createFolder(int $userId, string $slug, string $relativePath): void
    {
        $targetPath = $this->resolveProjectPath($userId, $slug, $relativePath);

        if (is_file($targetPath) || is_dir($targetPath)) {
            throw new \RuntimeException('A file or folder with that name already exists.');
        }

        if (!mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
            throw new \RuntimeException('Unable to create folder.');
        }
    }

    /**
     * Copy a file or directory within the same draft project.
     */
    public function copyProjectItem(int $userId, string $slug, string $fromRelativePath, string $toRelativePath): array
    {
        $from = $this->normalizeRelativePath($fromRelativePath);
        $to = $this->normalizeRelativePath($toRelativePath);
        if ($from === '' || $to === '' || $from === $to || str_starts_with($to, $from . '/')) {
            throw new \InvalidArgumentException('Choose a valid destination outside the source folder.');
        }

        $projectPath = $this->projectPath($userId, $slug);
        $sourcePath = $this->resolveProjectPath($userId, $slug, $from);
        $targetPath = $this->resolveProjectPath($userId, $slug, $to);
        if (!$this->isInsidePath($sourcePath, $projectPath) || (!is_file($sourcePath) && !is_dir($sourcePath))) {
            throw new \RuntimeException('Source file or folder was not found.');
        }
        if (file_exists($targetPath)) {
            throw new \RuntimeException('A file or folder already exists at the destination.');
        }

        $copyTree = function (string $source, string $target) use (&$copyTree): void {
            if (is_link($source)) {
                throw new \RuntimeException('Symbolic links cannot be copied.');
            }
            if (is_file($source)) {
                $parent = dirname($target);
                if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                    throw new \RuntimeException('Unable to create destination folder.');
                }
                if (!copy($source, $target)) {
                    throw new \RuntimeException('Unable to copy file.');
                }
                return;
            }
            if (!mkdir($target, 0755, true) && !is_dir($target)) {
                throw new \RuntimeException('Unable to create destination folder.');
            }
            foreach (array_diff(scandir($source) ?: [], ['.', '..']) as $entry) {
                $copyTree($source . DIRECTORY_SEPARATOR . $entry, $target . DIRECTORY_SEPARATOR . $entry);
            }
        };

        try {
            $copyTree($sourcePath, $targetPath);
        } catch (\Throwable $exception) {
            if (is_dir($targetPath)) {
                $this->deleteDirectory($targetPath);
            } elseif (is_file($targetPath)) {
                unlink($targetPath);
            }
            throw $exception;
        }

        return [
            'from' => $from,
            'to' => $to,
            'type' => is_dir($targetPath) ? 'folder' : 'file',
            'size' => is_file($targetPath) ? (filesize($targetPath) ?: 0) : $this->directorySize($targetPath),
        ];
    }

    /**
     * Delete a safe project-relative file or folder.
     */
    public function deleteProjectItem(int $userId, string $slug, string $relativePath): void
    {
        $safeRelativePath = $this->normalizeRelativePath($relativePath);

        if ($safeRelativePath === '') {
            throw new \InvalidArgumentException('Select a file or folder to delete.');
        }

        $targetPath = $this->resolveProjectPath($userId, $slug, $safeRelativePath);
        $projectPath = $this->projectPath($userId, $slug);

        if (!$this->isInsidePath($targetPath, $projectPath)) {
            throw new \InvalidArgumentException('Invalid file path.');
        }

        if (is_dir($targetPath)) {
            $this->deleteDirectory($targetPath);
            return;
        }

        if (is_file($targetPath)) {
            unlink($targetPath);
        }
    }

    /**
     * Move or rename a draft file/folder inside the same project.
     */
    public function moveProjectItem(int $userId, string $slug, string $fromRelativePath, string $toRelativePath): array
    {
        $from = $this->normalizeRelativePath($fromRelativePath);
        $to = $this->normalizeRelativePath($toRelativePath);

        if ($from === '' || $to === '') {
            throw new \InvalidArgumentException('Choose both source and destination paths.');
        }

        $projectPath = $this->projectPath($userId, $slug);
        $sourcePath = $this->resolveProjectPath($userId, $slug, $from);
        $targetPath = $this->resolveProjectPath($userId, $slug, $to);

        if (!$this->isInsidePath($sourcePath, $projectPath)) {
            throw new \InvalidArgumentException('Invalid source path.');
        }

        if (is_file($targetPath) || is_dir($targetPath)) {
            throw new \RuntimeException('A file or folder already exists at the destination.');
        }

        if (!is_file($sourcePath) && !is_dir($sourcePath)) {
            throw new \RuntimeException('Source file or folder was not found.');
        }

        $parent = dirname($targetPath);
        if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new \RuntimeException('Unable to create destination folder.');
        }

        if (!rename($sourcePath, $targetPath)) {
            throw new \RuntimeException('Unable to move the item.');
        }

        return [
            'from' => $from,
            'to' => $to,
            'type' => is_dir($targetPath) ? 'folder' : 'file',
            'size' => is_file($targetPath) ? (filesize($targetPath) ?: 0) : 0,
        ];
    }

    /**
     * Resolve a downloadable file path and return metadata.
     */
    public function downloadableFile(int $userId, string $slug, string $relativePath): array
    {
        $safeRelativePath = $this->normalizeRelativePath($relativePath);
        $projectPath = $this->projectPath($userId, $slug);
        $targetPath = $this->resolveProjectPath($userId, $slug, $safeRelativePath);

        if (!$this->isInsidePath($targetPath, $projectPath) || !is_file($targetPath)) {
            throw new \RuntimeException('File was not found.');
        }

        return [
            'absolute_path' => $targetPath,
            'relative_path' => $safeRelativePath,
            'filename' => basename($targetPath),
            'size' => filesize($targetPath) ?: 0,
            'mime' => $this->detectMimeType($targetPath),
        ];
    }

    /**
     * Read a project text file after safe path checks.
     */
    public function readTextFile(int $userId, string $slug, string $relativePath): array
    {
        $file = $this->downloadableFile($userId, $slug, $relativePath);
        $extension = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['html', 'htm', 'css', 'js', 'json', 'txt', 'md'], true)) {
            throw new \InvalidArgumentException('This file type cannot be edited.');
        }

        $content = file_get_contents($file['absolute_path']);
        if ($content === false) {
            throw new \RuntimeException('Unable to read file.');
        }

        $file['content'] = $content;
        $file['extension'] = $extension;

        return $file;
    }

    /**
     * Save a project text file after safe path checks.
     */
    public function writeTextFile(int $userId, string $slug, string $relativePath, string $content): int
    {
        $file = $this->readTextFile($userId, $slug, $relativePath);

        if (file_put_contents($file['absolute_path'], $content) === false) {
            throw new \RuntimeException('Unable to save file.');
        }

        return filesize($file['absolute_path']) ?: strlen($content);
    }

    /**
     * Delete a project folder only when it is inside the projects storage root.
     */
    public function deleteProjectDirectory(int $userId, string $slug): void
    {
        $path = $this->projectPath($userId, $slug);

        if (!is_dir($path) || !$this->isInsideProjectsRoot($path)) {
            return;
        }

        $this->deleteDirectory($path);
    }

    /**
     * Normalize a template key into a display label.
     */
    private function templateLabel(string $template): string
    {
        $labels = [
            'blank' => 'Blank',
            'portfolio' => 'Portfolio',
            'business' => 'Business',
            'church' => 'Church',
            'blog' => 'Blog',
            'school' => 'School project',
        ];

        return $labels[$template] ?? 'Blank';
    }

    /**
     * Confirm a directory resolves under the projects root.
     */
    private function isInsideProjectsRoot(string $path): bool
    {
        $root = realpath($this->projectsRoot());
        $target = realpath($path);

        if (!$root || !$target) {
            return false;
        }

        return str_starts_with(strtolower($target), strtolower($root . DIRECTORY_SEPARATOR));
    }

    /**
     * Confirm a target path stays inside the given base path.
     */
    private function isInsidePath(string $targetPath, string $basePath): bool
    {
        $base = realpath($basePath);
        $target = realpath($targetPath);

        if (!$base || !$target) {
            return false;
        }

        return $target === $base || str_starts_with(strtolower($target), strtolower($base . DIRECTORY_SEPARATOR));
    }

    /**
     * Detect a safe response MIME type for downloads.
     */
    private function detectMimeType(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);

                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }

        return 'application/octet-stream';
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
