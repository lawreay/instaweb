<?php

declare(strict_types=1);

use App\Controllers\PortfolioController;

require dirname(__DIR__) . '/vendor/autoload.php';


function failCheck(string $message): void
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
$websiteModel = readRequiredFile($root . '/app/Models/Website.php');
$portfolioModel = readRequiredFile($root . '/app/Models/Portfolio.php');
$publicController = readRequiredFile($root . '/app/Controllers/PortfolioPublicController.php');
$previewController = readRequiredFile($root . '/app/Controllers/PreviewController.php');
$reactJs = readRequiredFile($root . '/public/assets/portfolio-template/react-portfolio.js');
$publishController = readRequiredFile($root . '/app/Controllers/PublishController.php');
$portfolioController = readRequiredFile($root . '/app/Controllers/PortfolioController.php');
$advancedPortfolioController = readRequiredFile($root . '/app/Controllers/AdvancedPortfolioController.php');

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

if (!str_contains($publicView, "if (\$template === 'noir')")
    || !str_contains($publicView, 'class="noir-hero')
    || !str_contains($publicView, 'class="noir-project-grid"')) {
    failCheck('Noir does not have its dedicated renderer path.');
}

foreach (['.noir-hero', '.noir-project-grid', '.noir-timeline', '.noir-skills', '@media (prefers-reduced-motion: reduce)'] as $noirSelector) {
    if (!str_contains($reactCss . readRequiredFile($root . '/public/assets/portfolio-template/portfolio-noir.css'), $noirSelector)) {
        failCheck("Noir redesign is missing '{$noirSelector}'.");
    }
}

if (!str_contains($editorView, 'foreach ($templates as $templateKey => $templateLabel)')) {
    failCheck('Advanced Editor selector is not generated from the server-side template registry.');
}

if (!str_contains($advancedPortfolioController, 'updateTemplateForOwner((int) $website[\'id\'], $userId, $templateKey)')
    || !str_contains($portfolioModel, 'function updateTemplateForOwner')) {
    failCheck('Advanced Editor template selection is not persisted through an owner-scoped portfolio update.');
}

if (!str_contains($publicView, '&& !empty($item[\'is_enabled\'])')) {
    failCheck('Public renderer does not filter disabled section entries.');
}

if (!str_contains($router, '#^/p/([a-z0-9-]{1,80})/?$#i')) {
    failCheck('Dynamic public portfolio route is missing.');
}

if (!str_contains($rootHtaccess, 'RewriteRule ^(?!index\\.php$)[^/]+\\.php$ - [F,L,NC]')) {
    failCheck('Root .htaccess does not block direct access to non-front-controller PHP files.');
}

foreach (['portfolio_advanced_items', 'portfolio_advanced_settings', 'portfolio_advanced_uploads'] as $table) {
    if (!str_contains($websiteModel, $table)) {
        failCheck("Website deletion does not clean advanced portfolio table '{$table}'.");
    }
}


if (!str_contains($websiteModel, "AND w.status = 'published'") || !str_contains($websiteModel, "AND w.template_key = 'portfolio'")) {
    failCheck('Public slug lookup does not require a published portfolio website.');
}

if (!str_contains($portfolioModel, "AND w.status = 'published'") || !str_contains($portfolioModel, "AND w.template_key = 'portfolio'")) {
    failCheck('Public portfolio content lookup does not independently enforce published status and portfolio template.');
}

if (!str_contains($publicController, 'findPublishedPortfolioBySlug($slug)')
    || !str_contains($publicController, 'findPublishedForWebsite((int) $website[\'id\'])')) {
    failCheck('Public portfolio controller is missing its published slug/content checks.');
}

if (!str_contains($previewController, 'AuthService::check()')
    || !str_contains($previewController, 'findForUser((int) $this->input(\'id\', 0), $userId)')) {
    failCheck('Draft preview does not enforce authenticated website ownership.');
}

if (!str_contains($previewController, "header('Cache-Control: private, no-store, no-cache, must-revalidate')")
    || !str_contains($previewController, "header('Pragma: no-cache')")) {
    failCheck('Owner-only portfolio draft preview does not send private no-cache response headers.');
}

if (!str_contains($publicView, "!empty(\$isPreview) ? 'noindex,nofollow' : 'index,follow'")) {
    failCheck('Private portfolio preview must be marked noindex,nofollow while published pages remain indexable.');
}

if (!str_contains($reactJs, "(data.showContact && (data.email || (data.socials || []).length)) ? { id: 'contact', label: 'Contact' } : null")) {
    failCheck('React Contact navigation must only appear when the Contact section can render.');
}

if (!str_contains($publishController, 'updateStatus((int) $website[\'id\'], $userId, \'published\')')
    || !str_contains($publishController, 'updateStatus((int) $website[\'id\'], $userId, \'draft\')')
    || !str_contains($publishController, 'publishPortfolio((int) $website[\'id\']')
    || !str_contains($publishController, '->unpublish((int) $website[\'id\']')) {
    failCheck('Publish/unpublish lifecycle does not update status and clean the public directory.');
}

$unpublishMethodStart = strpos($publishController, 'public function unpublish(): void');
$portfolioUnpublishStart = $unpublishMethodStart === false
    ? false
    : strpos($publishController, 'if (($website[\'template_key\'] ?? \'\') === \'portfolio\')', $unpublishMethodStart);
$portfolioDraftPosition = $portfolioUnpublishStart === false
    ? false
    : strpos($publishController, 'updateStatus((int) $website[\'id\'], $userId, \'draft\')', $portfolioUnpublishStart);
$portfolioCleanupPosition = $portfolioUnpublishStart === false
    ? false
    : strpos($publishController, '$publishingService->unpublish((int) $website[\'id\'], $website[\'slug\']);', $portfolioUnpublishStart);

if ($portfolioDraftPosition === false || $portfolioCleanupPosition === false || $portfolioDraftPosition > $portfolioCleanupPosition) {
    failCheck('Portfolio unpublish must disable the dynamic public route before filesystem cleanup.');
}

if (!str_contains($portfolioController, '$previousAvatar = (string) ($portfolio[\'avatar_path\'] ?? \'\');')
    || !str_contains($portfolioController, 'realpath($publicRoot . DIRECTORY_SEPARATOR . str_replace(\'/\', DIRECTORY_SEPARATOR, $previousAvatar))')
    || !str_contains($portfolioController, 'str_starts_with($previousAbsolutePath, rtrim($uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)')) {
    failCheck('Avatar replacement must safely remove the previous file only from the owning website upload directory.');
}

if (!str_contains($editorView, "'interests' => [['title','Interest',true],['details','Short explanation (optional)',false,'textarea']]")) {
    failCheck('Interests editor must not require organisation, period, or link fields.');
}
if (!str_contains($editorView, "'skills' => [['title','Skill name',true],['category','Category (optional)',false],['proficiency','Proficiency (optional)',false,'select'")) {
    failCheck('Skills editor must use skill-specific fields rather than the generic content form.');
}
if (!str_contains($advancedPortfolioController, "'interests' => ['details']") || !str_contains($advancedPortfolioController, "'skills' => ['category', 'proficiency']")) {
    failCheck('Portfolio controller must validate section-specific content fields.');
}
if (!str_contains($editorView, "'cv' => [['title','Document title',true]")) {
    failCheck('CV section must not present organisation, period, and link as generic required concepts.');
}
echo '[portfolio-check] PASS: template registry, React variants, editor selector, hidden items, public route, publication gating, owner-only preview, publish/unpublish wiring, root access rules, and advanced-data cleanup are consistent.' . PHP_EOL;
