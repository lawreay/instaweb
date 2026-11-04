<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$navigationPath = $root . '/app/Views/websites/partials/editor-navigation.php';
$businessViewPath = $root . '/app/Views/websites/business-edit.php';

if (!is_file($navigationPath) || !is_file($businessViewPath)) {
    fwrite(STDERR, "[editor-foundation] FAIL: shared editor navigation or Business editor view is missing.\n");
    exit(1);
}

$navigation = file_get_contents($navigationPath);
$businessView = file_get_contents($businessViewPath);
$requiredTabs = ["Overview", "Content", "Themes", "Branding", "Media", "SEO"];

foreach ($requiredTabs as $tab) {
    if (!str_contains($navigation, "'label' => '{$tab}'")) {
        fwrite(STDERR, "[editor-foundation] FAIL: {$tab} navigation item is missing.\n");
        exit(1);
    }
}

if (!str_contains($businessView, "require __DIR__ . '/partials/editor-navigation.php'")) {
    fwrite(STDERR, "[editor-foundation] FAIL: Business editor does not use the shared navigation partial.\n");
    exit(1);
}

if (!str_contains($navigation, "'available' => false")) {
    fwrite(STDERR, "[editor-foundation] FAIL: future editor areas must remain unavailable until implemented.\n");
    exit(1);
}

echo "[editor-foundation] PASS: shared navigation exists and is integrated without exposing unfinished routes.\n";
