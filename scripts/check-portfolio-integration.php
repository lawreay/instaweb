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

/**
 * Remove formatting whitespace while preserving whitespace inside PHP string
 * literals. This keeps source-integrity assertions independent of formatting.
 */
function compactPhpSource(string $source): string
{
    $compact = '';
    foreach (token_get_all($source) as $token) {
        $compact .= is_array($token) && $token[0] === T_WHITESPACE
            ? ''
            : (is_array($token) ? $token[1] : $token);
    }

    return $compact;
}

$root = dirname(__DIR__);
$registry = array_keys(PortfolioController::TEMPLATES);
$publicView = readRequiredFile($root . '/app/Views/portfolio/public.php');
$editorView = readRequiredFile($root . '/app/Views/portfolio/advanced.php');
$editorViewCompact = compactPhpSource($editorView);
$reactCss = readRequiredFile($root . '/public/assets/portfolio-template/react-portfolio.css');
$router = readRequiredFile($root . '/app/Core/Router.php');
$rootHtaccess = readRequiredFile($root . '/.htaccess');
$websiteModel = readRequiredFile($root . '/app/Models/Website.php');
$websiteController = readRequiredFile($root . '/app/Controllers/WebsiteController.php');
$adminRepository = readRequiredFile($root . '/app/Models/AdminRepository.php');
$adminWebsitesView = readRequiredFile($root . '/app/Views/admin/websites.php');
$websiteListView = readRequiredFile($root . '/app/Views/websites/index.php');
$seedScript = readRequiredFile($root . '/scripts/seed-default-admin-portfolio.php');
$portfolioModel = readRequiredFile($root . '/app/Models/Portfolio.php');
$publicController = readRequiredFile($root . '/app/Controllers/PortfolioPublicController.php');
$previewController = readRequiredFile($root . '/app/Controllers/PreviewController.php');
$reactJs = readRequiredFile($root . '/public/assets/portfolio-template/react-portfolio.js');
$publishController = readRequiredFile($root . '/app/Controllers/PublishController.php');
$portfolioController = readRequiredFile($root . '/app/Controllers/PortfolioController.php');
$advancedPortfolioController = readRequiredFile($root . '/app/Controllers/AdvancedPortfolioController.php');
$routes = readRequiredFile($root . '/app/Config/routes.php');
$portfolioContactController = readRequiredFile($root . '/app/Controllers/PortfolioContactController.php');
$portfolioContactMigration = readRequiredFile($root . '/database/migrations/20261026_004_create_portfolio_contact_rate_limits.php');

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
$expectedReact = ['aurora', 'studio', 'orbit', 'prism', 'archive', 'ledger', 'graduate'];
if ($expectedReact !== $reactKeys[1]) {
    failCheck('React template allowlist differs from the expected five template keys.');
}

foreach ($expectedReact as $key) {
    if (!str_contains($reactCss, '.rp--' . $key)) {
        failCheck("Missing CSS variant for React template '{$key}'.");
    }
}

if (str_contains($reactJs, 'https://esm.sh') || preg_match('/^import\s/m', $reactJs)) {
    failCheck('Interactive portfolio templates must not rely on remote JavaScript modules.');
}

if (!str_contains($reactJs, 'function AuroraApp')
    || !str_contains($reactJs, 'function AuroraArt')
    || !str_contains($reactCss, '.am-hero')
    || !str_contains($reactCss, '.am-section-body')) {
    failCheck('Aurora Motion does not have its dedicated React renderer and graphics system.');
}

$dedicatedReactTemplates = [
    'studio' => ['StudioApp', '.rs-rail', '.rs-projects'],
    'orbit' => ['OrbitApp', '.ro-console', '.ro-route'],
    'prism' => ['PrismApp', '.rpz-hero', '.rpz-items'],
    'archive' => ['ArchiveApp', '.ra-masthead', '.ra-catalogue'],
    'ledger' => ['LedgerApp', '.ld-header', '.ld-ledger'],
    'graduate' => ['GraduateApp', '.gp-header', '.gp-path'],
];
foreach ($dedicatedReactTemplates as $template => [$renderer, $firstSelector, $secondSelector]) {
    if (!str_contains($reactJs, 'function ' . $renderer)
        || !str_contains($reactCss, $firstSelector)
        || !str_contains($reactCss, $secondSelector)) {
        failCheck(ucfirst($template) . ' does not have its dedicated renderer and layout system.');
    }
}

if (!str_contains($publicView, 'if ($template === \'noir\')')
    || !str_contains($publicView, 'class="noir-hero')
    || !str_contains($publicView, 'class="noir-project-grid')) {
    failCheck('Noir does not have its dedicated renderer path.');
}

foreach (['.noir-hero', '.noir-project-grid', '.noir-timeline', '.noir-skills', '@media (prefers-reduced-motion: reduce)'] as $noirSelector) {
    if (!str_contains($reactCss . readRequiredFile($root . '/public/assets/portfolio-template/portfolio-noir.css'), $noirSelector)) {
        failCheck("Noir redesign is missing '{$noirSelector}'.");
    }
}

foreach (['uploadNoirSectionImage', 'removeNoirSectionImage', 'noirSectionImages', 'noirSectionImage'] as $noirImageWiring) {
    if (!str_contains($advancedPortfolioController . $portfolioModel, $noirImageWiring)) {
        failCheck("Noir section-image support is missing '{$noirImageWiring}'.");
    }
}
if (!str_contains($routes, 'noir-image/upload') || !str_contains($routes, 'noir-image/remove')
    || !str_contains($editorView, 'data-ap-tab-panel="noir-images"')
    || !str_contains($publicView, '$noirImageUrls')) {
    failCheck('Noir image dashboard routes or public rendering are incomplete.');
}

$setupView = readRequiredFile($root . '/app/Views/portfolio/edit.php');
if (!str_contains($setupView, 'name="display_name"')
    || !str_contains($setupView, 'name="headline"')
    || !str_contains($setupView, 'portfolio-avatar-preview')
    || !str_contains($setupView, 'portfolio-template-preview')
    || !str_contains($setupView, 'Changes save automatically')
    || !str_contains($setupView, 'template_choice')
    || !str_contains($routes, "'/dashboard/websites/portfolio/autosave' => [PortfolioController::class, 'autosave']")) {
    failCheck('Portfolio setup page is missing its basic profile, photo preview, template preview, or autosave wiring.');
}
if (str_contains($setupView, 'projects/add') || str_contains($setupView, 'project_title')) {
    failCheck('Project management must remain in the Advanced Editor, not the setup page.');
}
if (!str_contains($portfolioController, 'public function autosave(): void')
    || !str_contains($portfolioController, 'Csrf::validate($this->input(\'_csrf\'))')) {
    failCheck('Portfolio setup autosave endpoint must validate the session token.');
}

$adminTemplateView = readRequiredFile($root . '/app/Views/admin/portfolio-templates.php');
$adminController = readRequiredFile($root . '/app/Controllers/AdminController.php');
$customTemplateModel = readRequiredFile($root . '/app/Models/PortfolioTemplate.php');
$customTemplateForm = readRequiredFile($root . '/app/Views/admin/portfolio-template-form.php');
$customReactFrame = readRequiredFile($root . '/public/assets/portfolio-template/custom-react-template-frame.html');
$customReactRuntime = readRequiredFile($root . '/public/assets/portfolio-template/custom-react-template-runtime.js');
if (!str_contains($routes, "'/admin/portfolio-templates' => [AdminController::class, 'portfolioTemplates']")
    || !str_contains($routes, "'/admin/portfolio-templates/save' => [AdminController::class, 'savePortfolioTemplates']")
    || !str_contains($adminController, 'public function savePortfolioTemplates(): void')
    || !str_contains($adminController, 'ownedPortfolioOptions((int) AuthService::id())')
    || !str_contains($adminTemplateView, 'Save template previews')
    || !str_contains($portfolioModel, 'function findPublishedTemplatePreview')
    || !str_contains($portfolioModel, 'function ownedPortfolioOptions')
    || !str_contains($portfolioController, 'portfolio_template_default_')) {
    failCheck('Admin-managed, owner-scoped default template preview workflow is incomplete.');
}

if (!str_contains($editorView, 'foreach ($templates as $templateKey => $templateLabel)')) {
    failCheck('Advanced Editor selector is not generated from the server-side template registry.');
}

if (!str_contains($customTemplateModel, 'render_engine')
    || !str_contains($customTemplateModel, 'react_code')
    || !str_contains($customTemplateForm, 'React + JSX')
    || !str_contains($customTemplateForm, 'function Portfolio({ data })')
    || !str_contains($adminController, 'defaultTemplateReact')
    || !str_contains($adminController, '$unsafeReact')
    || !str_contains($publicView, '$isCustomReactTemplate')
    || !str_contains($publicView, 'custom-react-template-frame')
    || !str_contains($publicView, 'sandbox="allow-scripts"')
    || !str_contains($customReactFrame, 'react.production.min.js')
    || !str_contains($customReactFrame, 'babel.min.js')
    || !str_contains($customReactRuntime, "Babel.transform")
    || !str_contains($customReactRuntime, "instaweb-react-template-height")) {
    failCheck('Admin-authored React/JSX template support is incomplete.');
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

if (!str_contains($websiteModel, 'is_system_template')
    || !str_contains($websiteModel, 'function isSystemTemplate')
    || !str_contains($websiteController, 'This protected default portfolio cannot be deleted.')
    || !str_contains($adminRepository, 'DELETE FROM websites WHERE id = :id AND is_system_template = 0')
    || !str_contains($adminWebsitesView, 'Protected system portfolio')
    || !str_contains($websiteListView, 'Default template')
    || !str_contains($seedScript, "'john-doe-bba-accounting'")) {
    failCheck('Protected John Doe default portfolio wiring is incomplete.');
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

if (!str_contains($reactJs, 'function rankedNavigation(data)')
    || !str_contains($reactJs, "const navigationPriority = ['about', 'experience', 'projects', 'skills', 'contact'")
    || !str_contains($reactJs, 'data.showContact && (data.contactAvailable || (data.socials || []).length)')
    || !str_contains($reactJs, '.slice(0, 5)')
    || !str_contains($publicView, '$navigationPriority =')
    || !str_contains($publicView, 'array_slice($navigationItems, 0, 5)')) {
    failCheck('Portfolio navigation must rank available destinations and keep only five links.');
}

if (!str_contains($routes, "'/portfolio/contact' => [PortfolioContactController::class, 'send']")
    || !str_contains($publicView, 'data-portfolio-contact-form')
    || !str_contains($publicView, 'portfolio-contact-dialog')
    || !str_contains($publicView, "'contactAvailable' => \$portfolioContactAvailable")
    || !str_contains($portfolioContactController, 'Csrf::validate')
    || !str_contains($portfolioContactController, 'findPublishedPortfolioBySlug')
    || !str_contains($portfolioContactController, 'withinRateLimit')
    || !str_contains($portfolioContactMigration, 'portfolio_contact_rate_limits')) {
    failCheck('Portfolio contact modal, SMTP endpoint, publication checks, or rate limiting are incomplete.');
}
if (str_contains($reactJs, 'mailto:')) {
    failCheck('Built-in React portfolio contacts must use the SMTP contact modal, not mailto links.');
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

if (!str_contains($editorViewCompact, "'interests'=>[['title','Interest',true],['details','Short explanation (optional)',false,'textarea']]")) {
    failCheck('Interests editor must not require organisation, period, or link fields.');
}
if (!str_contains($editorViewCompact, "'skills'=>[['title','Skill name',true],['category','Category (optional)',false],['proficiency','Proficiency (optional)',false,'select'")) {
    failCheck('Skills editor must use skill-specific fields rather than the generic content form.');
}
if (!str_contains($advancedPortfolioController, "'interests' => ['details']") || !str_contains($advancedPortfolioController, "'skills' => ['category', 'proficiency']")) {
    failCheck('Portfolio controller must validate section-specific content fields.');
}
if (!str_contains($editorViewCompact, "'cv'=>[['title','Document title',true]")) {
    failCheck('CV section must not present organisation, period, and link as generic required concepts.');
}
$aiConfig = readRequiredFile($root . '/app/Config/ai.php');
$aiService = readRequiredFile($root . '/app/Services/PortfolioAiService.php');
$openRouterProvider = readRequiredFile($root . '/app/Services/OpenRouterPortfolioProvider.php');
$aiController = readRequiredFile($root . '/app/Controllers/PortfolioAiController.php');
$aiUsageModel = readRequiredFile($root . '/app/Models/PortfolioAiDailyUsage.php');
$aiUsageMigration = readRequiredFile($root . '/database/migrations/20261030_001_create_portfolio_ai_daily_usage.php');
$aiWorkflowDoc = readRequiredFile($root . '/docs/portfolio-guide/AI-ENHANCE-WORKFLOW.md');

if (!str_contains($aiConfig, "'openrouter'")
    || !str_contains($aiConfig, "env('OPENROUTER_API_KEY'")
    || !str_contains($aiConfig, "env('OPENROUTER_MODEL', 'openrouter/free')")
    || !str_contains($aiService, "'openrouter' => new OpenRouterPortfolioProvider")
    || !str_contains($aiService, "foreach (['openrouter', 'openai', 'gemini'] as $provider)")
    || !str_contains($openRouterProvider, 'https://openrouter.ai/api/v1/chat/completions')
    || !str_contains($openRouterProvider, "'response_format' => [")
    || !str_contains($aiController, 'consumeOpenRouter($dailyLimit)')
    || !str_contains($aiUsageModel, 'ON DUPLICATE KEY UPDATE')
    || !str_contains($aiUsageMigration, 'portfolio_ai_daily_usage')
    || !str_contains($editorView, '<option value="openrouter">OpenRouter (Free)</option>')
    || !str_contains($aiWorkflowDoc, 'PORTFOLIO_AI_OPENROUTER_DAILY_LIMIT=40')) {
    failCheck('OpenRouter free default, structured output adapter, BYOK provider routing, or shared daily quota wiring is incomplete.');
}

echo '[portfolio-check] PASS: template registry, React variants, editor selector, hidden items, public route, publication gating, owner-only preview, publish/unpublish wiring, root access rules, and advanced-data cleanup are consistent.' . PHP_EOL;
