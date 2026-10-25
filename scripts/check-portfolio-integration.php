<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controllers\PortfolioController;

function failCheck(string $message): never
{
    fwrite(STDERR, "[portfolio-check] FAIL: {$message}\n");
    exit(1);
}

function readRequiredFile(string $path): string
{
    $content = file_get_contents($path);
    if ($content === false) {
        failCheck("Could not read {$path}");
    }

    return $content;
}

$root = dirname(__DIR__);
$registry = array_keys(PortfolioController::TEMPLATES);
$publicView = readRequiredFile($root . '/app/Views/portfolio/public.php');
$editorView = readRequiredFile($root . '/app/Views/portfolio/advanced.php');
$reactCss = readRequiredFile($root . '/public/assets/portfolio-template/react-portfolio.css');
$router = readRequiredFile($root . '/app/Core/Router.php');
$rootHtaccess = readRequiredFile($root . '/.htaccess');

if (!preg_match('/\$availableTemplates\s*=\s*\[(.*?)\];/s', $publicView, $templateMatch)) {
    failCheck('Public renderer does not declare its supported template list.');
}
preg_match_all("/'([a-z0-9]+)'/", $templateMatch[1], $publicKeys);

if ($registry !== $publicKeys[1]) {
    failCheck('Controller template registry and public renderer template list differ.');
}

if (!preg_match('/\$reactTemplates\s*=\s*\[(.*?)\];/s', $publicView, $reactMatch)) {
    failCheck('Public renderer does not declare its React template list.');
}
preg_match_all("/'([a-z0-9]+)'/", $reactMatch[1], $reactKeys);
$expectedReact = ['aurora', 'studio', 'orbit', 'prism', 'archive'];
if ($expectedReact !== $reactKeys[1]) {
    failCheck('React template allowlist differs from the expected five template keys.');
}

foreach ($expectedReact as $key) {
    if (!str_contains($reactCss, '.rp--' . $key)) {
        failCheck("Missing CSS variant for React template '{$key}'.");
    }
}

if (!str_contains($editorView, 'foreach ($templates as $templateKey => $templateLabel)')) {
    failCheck('Advanced Editor selector is not generated from the server-side template registry.');
}

if (!str_contains($publicView, "&& !empty($item['is_enabled'])")) {
    failCheck('Public renderer does not filter disabled section entries.');
}

if (!str_contains($router, '#^/p/([a-z0-9-]{1,80})/?$#i')) {
    failCheck('Dynamic public portfolio route is missing.');
}

if (!str_contains($rootHtaccess, 'RewriteRule ^(?!index\\.php$)[^/]+\\.php$ - [F,L,NC]')) {
    failCheck('Root .htaccess does not block direct access to non-front-controller PHP files.');
}

echo '[portfolio-check] PASS: template registry, React variants, editor selector, hidden items, public route, and root access rules are consistent.' . PHP_EOL;
