<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

function themeCheck(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "[business-themes] FAIL: {$message}\n");
        exit(1);
    }
}

$root = dirname(__DIR__);
$read = static fn (string $path): string => (string) file_get_contents($root . '/' . $path);
$routes = $read('app/Config/routes.php');
$controller = $read('app/Controllers/BusinessWebsiteController.php');
$publisher = $read('app/Services/PublishingService.php');
$view = $read('app/Views/websites/business-themes.php');
$migration = $read('database/migrations/20261104_001_create_business_website_themes.sql');
$cleanup = $read('app/Models/Website.php');

themeCheck(str_contains($routes, "'/dashboard/websites/business/themes'"), 'Theme gallery route is missing.');
themeCheck(str_contains($routes, "'/dashboard/websites/business/theme-preview'"), 'Non-persistent theme preview route is missing.');
themeCheck(str_contains($routes, "'/dashboard/websites/business/theme/apply'"), 'Theme apply route is missing.');
themeCheck(str_contains($controller, 'Csrf::validate'), 'Theme changes must be CSRF protected.');
themeCheck(str_contains($controller, 'findForUser'), 'Theme actions must verify website ownership.');
themeCheck(str_contains($publisher, 'BusinessWebsiteThemeService'), 'Publishing must use the selected Business theme.');
themeCheck(str_contains($view, 'theme-preview?id='), 'Theme gallery must preview actual website content.');
themeCheck(str_contains($migration, 'business_website_themes'), 'Theme preference migration is missing.');
themeCheck(str_contains($cleanup, "'business_website_themes'"), 'Website deletion must clean theme preferences.');

$themes = new App\Services\BusinessWebsiteThemeService();
$content = (new App\Services\BusinessWebsiteService())->defaults('Theme Test');
$content['hero_title'] = 'Content must survive theme changes';
$evergreen = $themes->render($content, 'evergreen');
$midnight = $themes->render($content, 'midnight');
themeCheck($evergreen !== $midnight, 'Theme render output must differ between available themes.');
themeCheck(str_contains($evergreen, 'Content must survive theme changes'), 'Theme rendering must preserve website content.');
themeCheck(str_contains($midnight, 'Content must survive theme changes'), 'Theme switching must not remove website content.');
themeCheck($themes->normalize('not-a-theme') === 'evergreen', 'Unknown themes must safely fall back to the default.');

echo "[business-themes] PASS: routes, ownership, CSRF, previews, publishing and content preservation checks passed.\n";
