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
$publishController = $read('app/Controllers/PublishController.php');
$publishingService = $read('app/Services/PublishingService.php');
$model = $read('app/Models/BusinessWebsiteContent.php');
$view = $read('app/Views/websites/business-edit.php');
$mediaView = $read('app/Views/websites/business-media.php');
$detail = $read('app/Views/websites/show.php');
$migration = $read('database/migrations/20261103_001_create_business_website_content.sql');

businessCheck(str_contains($publishController, '$publishingService->publishBusiness('), 'Business publish must use the structured-content publishing path.');
businessCheck(str_contains($publishingService, 'function publishBusiness('), 'Business-specific publish renderer is missing.');
businessCheck(str_contains($publishingService, 'findForOwner($projectId, $userId)'), 'Business publishing must load owner-scoped saved content.');
businessCheck(str_contains($publishingService, 'writeDraft($userId, $slug, $content,'), 'Business publishing must regenerate index.html before copying it.');
businessCheck(str_contains($routes, "BusinessWebsiteController::class, 'edit'"), 'Business GET route is missing.');
businessCheck(str_contains($routes, "BusinessWebsiteController::class, 'save'"), 'Business POST route is missing.');
businessCheck(str_contains($controller, 'Csrf::validate'), 'Business save must validate CSRF.');
businessCheck(str_contains($controller, 'findForUser'), 'Business editor must load an owner-scoped website.');
businessCheck(str_contains($model, "w.template_key = 'business'"), 'Business content queries must enforce the template type.');
businessCheck(str_contains($detail, "\$baseUrl . '/dashboard/websites/business?id='"), 'Website management page does not link to the Business customizer.');
businessCheck(str_contains($view, 'data-service-item') && str_contains($view, 'add-service') && str_contains($view, 'data-move-down'), 'Business customizer must support adding, removing and reordering services.');
businessCheck(str_contains($view, 'service_{$i}_text'), 'Business customizer must retain legacy service field compatibility.');
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
$dynamic = $service->validate([
    'business_name' => 'Example Business',
    'hero_title' => 'Welcome',
    'hero_description' => 'Introduction',
    'services' => [
        ['title' => 'Consulting', 'text' => 'Practical advice.'],
        ['title' => 'Training', 'text' => 'Skills development.'],
    ],
], 'Example Business');
$dynamicHtml = $service->render($dynamic);
businessCheck(($sample['section_visibility']['hero'] ?? false) === true, 'New Business sites must show sections by default.');
$hidden = $service->validate([
    'business_name' => 'Example Business',
    'hero_title' => 'Welcome',
    'hero_description' => 'Introduction',
    'services' => [['title' => 'Consulting', 'text' => 'Practical advice.']],
    'services_submitted' => '1',
    'section_visibility' => ['hero' => '0', 'services' => '1', 'about' => '0', 'contact' => '0'],
], 'Example Business');
$hiddenHtml = $service->render($hidden);
businessCheck(!str_contains($hiddenHtml, 'class="hero"'), 'Hidden hero section must not render.');
businessCheck(str_contains($hiddenHtml, 'id="services"'), 'Visible services section must render.');
businessCheck(!str_contains($hiddenHtml, 'id="about"') && !str_contains($hiddenHtml, 'id="contact"'), 'Hidden About and Contact sections must not render.');
businessCheck(!str_contains($hiddenHtml, 'href="#contact"') && !str_contains($hiddenHtml, 'href="#about"'), 'Navigation must not link to hidden sections.');
$withImage = $service->validate(['business_name'=>'Image Business','hero_title'=>'Welcome','hero_description'=>'Intro','hero_image_path'=>'assets/media/media-0123456789abcdef01234567.webp'], 'Image Business');
$imageHtml = $service->render($withImage);
businessCheck(str_contains($imageHtml, 'assets/media/media-0123456789abcdef01234567.webp'), 'Valid selected hero image must render.');
$legacyHtml = $service->render(['business_name' => 'Legacy Business', 'hero_title' => 'Welcome', 'hero_description' => 'Hello']);
businessCheck(str_contains($legacyHtml, 'class="hero"') && str_contains($legacyHtml, 'id="contact"'), 'Legacy Business content without visibility settings must keep all sections visible.');
businessCheck(str_contains($view, 'hero_image_path') && str_contains($view, 'instaweb-media-selected'), 'Business editor must allow selecting a hero image from the media library.');
businessCheck(str_contains($view, 'rel="opener"') && str_contains($mediaView, 'window.opener.postMessage'), 'Media picker must preserve its opener connection so selected images return to the editor.');
businessCheck(str_contains($view, 'section_visibility[') && str_contains($view, 'id="section-visibility"'), 'Business editor must expose section visibility controls.');
businessCheck(count($dynamic['services']) === 2, 'Service validation must preserve a variable number of services.');
businessCheck(str_contains($dynamicHtml, 'Consulting') && str_contains($dynamicHtml, 'Training'), 'Renderer must output all submitted services.');
businessCheck(!str_contains($dynamicHtml, 'Service Three'), 'Renderer must not inject removed default services into a customized list.');

businessCheck(str_contains($controller, 'function media(): void') && str_contains($controller, 'function uploadMedia(): void') && str_contains($controller, 'function deleteMedia(): void'), 'Business media library actions must be present.');
businessCheck(str_contains($controller, 'downloadableFile($userId') && str_contains($controller, 'selected hero image is no longer available'), 'Business content save must reject a missing or unavailable selected hero image.');
businessCheck(str_contains($routes, "BusinessWebsiteController::class, 'media'"), 'Business media route is missing.');
echo "[business-check] PASS: routes, ownership guards, migration, form and renderer checks passed.\n";
