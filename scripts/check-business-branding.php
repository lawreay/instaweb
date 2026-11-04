<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
function brandingCheck(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "[business-branding] FAIL: {$message}\n"); exit(1); }
}
$root = dirname(__DIR__);
$read = static fn (string $path): string => (string) file_get_contents($root . '/' . $path);
$routes = $read('app/Config/routes.php');
$controller = $read('app/Controllers/BusinessWebsiteController.php');
$renderer = $read('app/Services/BusinessWebsiteService.php');
$theme = $read('app/Services/BusinessWebsiteThemeService.php');
$view = $read('app/Views/websites/business-branding.php');
$migration = $read('database/migrations/20261104_002_create_business_website_branding.sql');
brandingCheck(str_contains($routes, "'/dashboard/websites/business/branding'"), 'Branding page route missing.');
brandingCheck(str_contains($routes, "'/dashboard/websites/business/branding/save'"), 'Branding save route missing.');
brandingCheck(str_contains($controller, 'Csrf::validate'), 'Branding upload must validate CSRF.');
brandingCheck(str_contains($controller, 'is_uploaded_file'), 'Branding upload must validate uploaded files.');
brandingCheck(str_contains($controller, 'incomingBytes')
    && str_contains($controller, 'replacementBytes')
    && str_contains($controller, 'hasStorageCapacity($userId, $website, $brandingIncomingBytes, $brandingReplacementBytes)'),
    'Branding uploads must preflight combined incoming bytes against the aggregate plan quota.');
brandingCheck(str_contains($controller, 'getimagesize'), 'Branding upload must validate image contents.');
$validationEnd = strpos($controller, '$uploads[$field] = [');
$moveStart = strpos($controller, 'foreach ($uploads as $field => $upload)');
brandingCheck($validationEnd !== false && $moveStart !== false && $validationEnd < $moveStart,
    'All branding images must be validated before any replacement file is moved.');
brandingCheck(str_contains($renderer, 'logo_path') && str_contains($renderer, 'favicon_path'), 'Renderer must support logo and favicon.');
brandingCheck(str_contains($controller, 'new BusinessWebsiteBranding()') && str_contains($theme, 'array $branding = []'), 'Theme-aware draft renderer must receive owner-scoped branding data.');
brandingCheck(str_contains($view, 'enctype="multipart/form-data"'), 'Branding form must support file uploads.');
brandingCheck(str_contains($view, 'storageSummary[\'used_bytes\']') && str_contains($controller, 'storageSummary($userId, $website)'), 'Branding page must show account-wide storage usage.');
brandingCheck(str_contains($migration, 'business_website_branding'), 'Branding migration missing.');
echo "[business-branding] PASS: routes, upload validation, renderer and migration checks passed.\n";
