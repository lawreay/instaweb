<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Csrf;
use App\Core\Session;
use App\Services\AuthService;

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

$config = require __DIR__ . '/../app/Config/app.php';
Session::start();

if (!AuthService::check()) {
    header('Location: ' . rtrim((string) ($config['base_url'] ?? ''), '/') . '/login');
    exit;
}

if (!AuthService::isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$rootPath = realpath(__DIR__ . '/..');
if ($rootPath === false) {
    http_response_code(500);
    exit('Application root could not be resolved.');
}

$updatePath = $rootPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'updates';
$message = null;
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate(isset($_POST['_csrf']) ? (string) $_POST['_csrf'] : null)) {
        $message = 'Your session expired. Please try again.';
        $messageType = 'danger';
    } else {
        try {
            $result = applySafeUpdate($rootPath, $updatePath, $_FILES['update_package'] ?? null);
            $message = sprintf(
                'Update completed. %d files updated, %d protected files skipped. Backup: %s',
                $result['updated'],
                $result['skipped'],
                $result['backup']
            );
        } catch (Throwable $exception) {
            error_log('[InstaWeb updater] ' . $exception->getMessage());
            $message = $exception->getMessage();
            $messageType = 'danger';
        }
    }
}

/**
 * Apply a ZIP as a non-destructive file overlay.
 *
 * Existing files are backed up before replacement. If any copy fails, files
 * already replaced by this run are restored and newly-created files removed.
 * The updater never deletes files from the live installation.
 */
function applySafeUpdate(string $rootPath, string $updatePath, $upload): array
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException('PHP ZIP extension is required to install updates.');
    }
    if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Choose a valid ZIP update package.');
    }
    if ((int) ($upload['size'] ?? 0) < 1 || (int) $upload['size'] > 200 * 1024 * 1024) {
        throw new RuntimeException('The update package must be between 1 byte and 200 MB.');
    }
    if (strtolower(pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION)) !== 'zip'
        || !is_uploaded_file((string) ($upload['tmp_name'] ?? ''))) {
        throw new RuntimeException('The update must be uploaded as a ZIP file.');
    }

    $incoming = $updatePath . DIRECTORY_SEPARATOR . 'incoming';
    $backups = $updatePath . DIRECTORY_SEPARATOR . 'backups';
    foreach ([$incoming, $backups] as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to prepare private update storage.');
        }
    }

    $token = bin2hex(random_bytes(8));
    $archivePath = $incoming . DIRECTORY_SEPARATOR . 'package-' . $token . '.zip';
    $stagePath = $incoming . DIRECTORY_SEPARATOR . 'stage-' . $token;
    $backupPath = $backups . DIRECTORY_SEPARATOR . 'release-' . date('Ymd-His') . '-' . $token;
    $backupFiles = $backupPath . DIRECTORY_SEPARATOR . 'files';

    if (!move_uploaded_file((string) $upload['tmp_name'], $archivePath)) {
        throw new RuntimeException('Unable to store the update package.');
    }

    $zip = new ZipArchive();
    if ($zip->open($archivePath) !== true) {
        @unlink($archivePath);
        throw new RuntimeException('The uploaded file is not a readable ZIP archive.');
    }

    $extractedBytes = 0;
    $fileCount = 0;
    try {
        validateUpdateArchive($zip, $extractedBytes, $fileCount);
        if ($fileCount === 0) {
            throw new RuntimeException('The update package contains no files.');
        }
        if (!mkdir($stagePath, 0750, true)) {
            throw new RuntimeException('Unable to create update staging directory.');
        }
        if (!$zip->extractTo($stagePath)) {
            throw new RuntimeException('Unable to extract the update package.');
        }
    } finally {
        $zip->close();
        @unlink($archivePath);
    }

    $stageRoot = resolvePackageRoot($stagePath);
    $files = collectUpdateFiles($stageRoot);
    if ($files === []) {
        removeUpdateTree($stagePath);
        throw new RuntimeException('No usable application files were found in the package.');
    }

    if (!mkdir($backupFiles, 0750, true)) {
        removeUpdateTree($stagePath);
        throw new RuntimeException('Unable to create the update backup.');
    }

    $manifest = [
        'created_at' => date(DATE_ATOM),
        'package_file_count' => $fileCount,
        'updated_files' => [],
        'skipped_protected_files' => [],
    ];
    $changed = [];
    $updated = 0;
    $skipped = 0;

    try {
        foreach ($files as $relativePath => $sourcePath) {
            $relativePath = normalizeRelativePath($relativePath);
            if (isProtectedUpdatePath($relativePath)) {
                $manifest['skipped_protected_files'][] = $relativePath;
                $skipped++;
                continue;
            }

            $destination = $rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $parent = dirname($destination);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new RuntimeException('Unable to create destination directory for ' . $relativePath . '.');
            }

            if (file_exists($destination) && (!is_file($destination) || is_link($destination))) {
                throw new RuntimeException('Refusing to replace a non-regular file: ' . $relativePath . '.');
            }

            $backupFile = $backupFiles . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $hadOriginal = is_file($destination);
            if ($hadOriginal) {
                $backupParent = dirname($backupFile);
                if (!is_dir($backupParent) && !mkdir($backupParent, 0750, true) && !is_dir($backupParent)) {
                    throw new RuntimeException('Unable to prepare backup for ' . $relativePath . '.');
                }
                if (!copy($destination, $backupFile)) {
                    throw new RuntimeException('Unable to back up ' . $relativePath . '.');
                }
            }

            $changed[] = ['destination' => $destination, 'backup' => $backupFile, 'had_original' => $hadOriginal, 'relative' => $relativePath];
            $temporary = $destination . '.instaweb-update-' . $token;
            if (!copy($sourcePath, $temporary)) {
                throw new RuntimeException('Unable to stage replacement for ' . $relativePath . '.');
            }
            if (!@rename($temporary, $destination)) {
                @unlink($temporary);
                if (DIRECTORY_SEPARATOR === '\\' && !@copy($sourcePath, $destination)) {
                    throw new RuntimeException('Unable to replace ' . $relativePath . '.');
                }
                if (DIRECTORY_SEPARATOR !== '\\') {
                    throw new RuntimeException('Unable to atomically replace ' . $relativePath . '.');
                }
            }

            $manifest['updated_files'][] = $relativePath;
            $updated++;
        }

        file_put_contents(
            $backupPath . DIRECTORY_SEPARATOR . 'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            LOCK_EX
        );
    } catch (Throwable $exception) {
        foreach (array_reverse($changed) as $entry) {
            if ($entry['had_original'] && is_file($entry['backup'])) {
                @copy($entry['backup'], $entry['destination']);
            } elseif (!$entry['had_original'] && is_file($entry['destination'])) {
                @unlink($entry['destination']);
            }
        }
        removeUpdateTree($stagePath);
        throw new RuntimeException('Update failed. The updater attempted to restore every changed file. Reason: ' . $exception->getMessage(), 0, $exception);
    }

    removeUpdateTree($stagePath);
    return ['updated' => $updated, 'skipped' => $skipped, 'backup' => basename($backupPath)];
}

function validateUpdateArchive(ZipArchive $zip, int &$totalBytes, int &$fileCount): void
{
    $totalBytes = 0;
    $fileCount = 0;
    if ($zip->numFiles > 10000) {
        throw new RuntimeException('The update package contains too many entries.');
    }

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $stat = $zip->statIndex($index);
        if (!is_array($stat) || !isset($stat['name'])) {
            throw new RuntimeException('The update package contains an invalid entry.');
        }
        $name = str_replace('\\', '/', (string) $stat['name']);
        if ($name === '' || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name)) {
            throw new RuntimeException('The update package contains an unsafe path.');
        }
        foreach (explode('/', trim($name, '/')) as $segment) {
            if ($segment === '..' || $segment === '.') {
                throw new RuntimeException('The update package contains a path traversal entry.');
            }
        }

        $attributes = 0;
        $opsys = 0;
        if ($zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            $mode = ($attributes >> 16) & 0170000;
            if ($mode === 0120000) {
                throw new RuntimeException('Symbolic links are not allowed in update packages.');
            }
        }

        if (str_ends_with($name, '/')) {
            continue;
        }
        $fileCount++;
        $totalBytes += (int) ($stat['size'] ?? 0);
        if ($totalBytes > 500 * 1024 * 1024) {
            throw new RuntimeException('The uncompressed update package exceeds 500 MB.');
        }
    }
}

function resolvePackageRoot(string $stagePath): string
{
    $entries = array_values(array_filter(scandir($stagePath) ?: [], static fn ($entry) => $entry !== '.' && $entry !== '..'));
    if (count($entries) === 1 && is_dir($stagePath . DIRECTORY_SEPARATOR . $entries[0])) {
        $candidate = $stagePath . DIRECTORY_SEPARATOR . $entries[0];
        $hasProjectMarker = file_exists($candidate . DIRECTORY_SEPARATOR . 'index.php')
            || is_dir($candidate . DIRECTORY_SEPARATOR . 'app')
            || is_dir($candidate . DIRECTORY_SEPARATOR . 'public')
            || file_exists($candidate . DIRECTORY_SEPARATOR . 'composer.json');
        if ($hasProjectMarker) {
            return $candidate;
        }
    }
    return $stagePath;
}

function collectUpdateFiles(string $stageRoot): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($stageRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->isLink()) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($stageRoot) + 1));
        $relative = normalizeRelativePath($relative);
        if ($relative !== '') {
            $files[$relative] = $file->getPathname();
        }
    }
    return $files;
}

function normalizeRelativePath(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    $segments = explode('/', $path);
    if ($path === '' || in_array('..', $segments, true) || in_array('.', $segments, true)) {
        throw new RuntimeException('An unsafe update path was detected.');
    }
    return implode('/', $segments);
}

/**
 * Protected paths are intentionally conservative. New releases must not alter
 * deployment configuration, credentials, dependencies, customer data or runtime state.
 */
function isProtectedUpdatePath(string $path): bool
{
    $normalized = strtolower(normalizeRelativePath($path));
    $segments = explode('/', $normalized);
    $base = end($segments);

    if ($base === '.env' || str_starts_with($base, '.env.')) {
        return true;
    }
    if (in_array($base, ['config.php', 'wp-config.php', '.htaccess', '.user.ini', 'php.ini'], true)) {
        return true;
    }
    if (in_array($segments[0], [
        '.git', '.github', 'config', 'database', 'storage', 'uploads',
        'vendor', 'node_modules', 'templates', 'logs', 'backups',
    ], true)) {
        return true;
    }
    if ($normalized === 'app/config' || str_starts_with($normalized, 'app/config/')) {
        return true;
    }
    if ($normalized === 'public/app-update.php') {
        return true;
    }
    if (in_array($normalized, ['composer.json', 'composer.lock', 'package.json', 'package-lock.json'], true)) {
        return true;
    }
    if (str_starts_with($normalized, 'public/sites/') || str_starts_with($normalized, 'public/uploads/')) {
        return true;
    }
    return false;
}

function removeUpdateTree(string $path): void
{
    if (!file_exists($path) && !is_link($path)) {
        return;
    }
    if (is_dir($path) && !is_link($path)) {
        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                removeUpdateTree($path . DIRECTORY_SEPARATOR . $entry);
            }
        }
        @rmdir($path);
        return;
    }
    @unlink($path);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Application Update</title>
  <style>
    :root { color-scheme: light; --ink: #132238; --muted: #66758a; --line: #dbe5ef; --blue: #1769e0; --blue-dark: #0f4fae; --wash: #eef6ff; }
    * { box-sizing: border-box; }
    body { min-height: 100vh; margin: 0; padding: 32px 18px; font-family: "DM Sans", "Segoe UI", sans-serif; color: var(--ink); background: radial-gradient(circle at 15% 0%, #dff0ff 0, transparent 36%), linear-gradient(145deg, #f7fbff, #edf3f8); }
    .shell { width: min(760px, 100%); margin: auto; }
    .brand { display: flex; align-items: center; gap: 12px; margin: 0 0 24px 4px; }
    .brand-mark { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 12px; background: linear-gradient(145deg, var(--blue), #34a0ff); color: white; font-size: 21px; font-weight: 800; }
    .brand strong { display: block; font-size: 15px; }
    .brand span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }
    main { overflow: hidden; background: rgba(255,255,255,.96); border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 22px 55px rgba(28,54,84,.12); }
    .hero { padding: 34px 36px 28px; background: linear-gradient(135deg,#fafdff,var(--wash)); border-bottom: 1px solid var(--line); }
    .eyebrow { margin: 0 0 9px; color: var(--blue); font-size: 11px; font-weight: 800; letter-spacing: .13em; text-transform: uppercase; }
    h1 { margin: 0; font-size: clamp(27px,5vw,40px); }
    .hero p { max-width: 590px; margin: 12px 0 0; color: var(--muted); line-height: 1.6; }
    .content { padding: 28px 36px 34px; }
    .notice { padding: 13px 15px; margin-bottom: 22px; border: 1px solid #bde6cc; border-radius: 10px; background: #effaf3; color: #17633a; line-height: 1.45; }
    .notice.danger { border-color: #f1c2c2; background: #fff1f1; color: #9b2020; }
    .upload-box { padding: 22px; border: 1px dashed #a8c4e2; border-radius: 13px; background: #fbfdff; }
    label { display: block; margin-bottom: 9px; font-weight: 800; }
    input[type=file] { display: block; width: 100%; padding: 11px; border: 1px solid var(--line); border-radius: 8px; background: white; color: var(--muted); }
    .help { display: block; margin-top: 10px; color: var(--muted); font-size: 12px; line-height: 1.5; }
    button { display: inline-flex; margin-top: 20px; border: 0; border-radius: 8px; padding: 12px 18px; background: var(--blue); color: #fff; font: inherit; font-weight: 800; cursor: pointer; }
    button:hover { background: var(--blue-dark); }
    .footnote { margin: 20px 2px 0; color: var(--muted); font-size: 12px; line-height: 1.5; }
    @media (max-width:560px) { body { padding: 20px 12px; } .hero,.content { padding-left: 22px; padding-right: 22px; } }
  </style>
</head>
<body>
<div class="shell">
  <div class="brand"><div class="brand-mark">I</div><div><strong>Instaweb</strong><span>Administrator workspace</span></div></div>
  <main>
    <div class="hero"><p class="eyebrow">System maintenance</p><h1>Application Update</h1><p>Install a release package using a protected, non-destructive update process. Existing server configuration and customer data are preserved.</p></div>
    <div class="content">
      <?php if ($message !== null): ?><div class="notice <?= $messageType === 'danger' ? 'danger' : '' ?>" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="upload-box">
          <label for="update_package">Choose release package</label>
          <input id="update_package" name="update_package" type="file" accept=".zip,application/zip" required>
          <small class="help">ZIP files up to 200 MB. The updater skips environment files, PHP configuration, app configuration, database files, dependencies, uploads, customer sites, templates, logs and runtime storage. It never deletes live files.</small>
          <button type="submit">Upload and apply update</button>
        </div>
      </form>
      <p class="footnote"><strong>Administrator only.</strong> Existing files are backed up before replacement. If an update fails, the updater attempts to restore the files changed in that run. Database migrations and dependency installation are deliberately not run automatically.</p>
    </div>
  </main>
</div>
</body>
</html>
