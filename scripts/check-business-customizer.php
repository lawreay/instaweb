<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

function businessCheck(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "[business-check] FAIL: {$message}\n");
        exit(1);
    }
}

$root = dirname(__DIR__);
$read = static function (string $path) use ($root): string {
    $value = file_get_contents($root . '/' . $path);
    if ($value === false) {
        throw new RuntimeException("Unable to read {$path}");
    }
    return $value;
};

$routes = $read('app/Config/routes.php');
$controller = $read('app/Controllers/BusinessWebsiteController.php');
$model = $read('app/Models/BusinessWebsiteContent.php');
$view = $read('app/Views/websites/business-edit.php');
$detail = $read('app/Views/websites/show.php');
$migration = $read('database/migrations/20261103_001_create_business_website_content.sql');

businessCheck(str_contains($routes, "BusinessWebsiteController::class, 'edit'"), 'Business GET route is missing.');
businessCheck(str_contains($routes, "BusinessWebsiteController::class, 'save'"), 'Business POST route is missing.');
businessCheck(str_contains($controller, 'Csrf::validate'), 'Business save must validate CSRF.');
businessCheck(str_contains($controller, 'findForUser'), 'Business editor must load an owner-scoped website.');
businessCheck(str_contains($model, "w.template_key = 'business'"), 'Business content queries must enforce the template type.');
businessCheck(str_contains($detail, "\$baseUrl . '/dashboard/websites/business?id='"), 'Website management page does not link to the Business customizer.');
businessCheck(str_contains($view, 'service_3_text'), 'Business customizer is missing service fields.');
businessCheck(str_contains($migration, 'business_website_content'), 'Business content migration is missing its table.');

$service = new App\Services\BusinessWebsiteService();
$sample = $service->defaults('A & B');
$sample['business_name'] = 'A & B <script>alert(1)</script>';
$sample['hero_title'] = 'Reliable service';
$html = $service->render($sample);
businessCheck(str_contains($html, 'A &amp; B &lt;script&gt;'), 'Rendered business content must be HTML escaped.');
businessCheck(!str_contains($html, '<script>alert(1)</script>'), 'User content must not inject executable HTML.');
businessCheck(str_contains($html, 'Service Three'), 'Renderer must include all three service cards.');
businessCheck(str_contains($html, 'Reliable service'), 'Renderer must include saved hero content.');

echo "[business-check] PASS: routes, ownership guards, migration, form and renderer checks passed.\n";
