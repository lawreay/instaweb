<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$controller = file_get_contents($root . '/app/Controllers/BusinessWebsiteController.php');
$businessView = file_get_contents($root . '/app/Views/websites/business-edit.php');

if (!is_string($controller) || !is_string($businessView)) {
    fwrite(STDERR, "[editor-foundation] FAIL: Business editor sources are missing.\\n");
    exit(1);
}

$requiredPanels = ['content', 'design', 'branding', 'media'];
foreach ($requiredPanels as $panel) {
    if (!str_contains($businessView, 'data-editor-tab="' . $panel . '"')
        || !str_contains($businessView, 'data-editor-panel="' . $panel . '"')) {
        fwrite(STDERR, "[editor-foundation] FAIL: {$panel} is not available in the single-page editor.\\n");
        exit(1);
    }
}

if (str_contains($businessView, "partials/editor-navigation.php")) {
    fwrite(STDERR, "[editor-foundation] FAIL: Business editor must not navigate to separate editor pages.\\n");
    exit(1);
}

foreach (['themes->all()', 'mediaItems', 'storageSummary($userId, $website)'] as $dataSource) {
    if (!str_contains($controller, $dataSource)) {
        fwrite(STDERR, "[editor-foundation] FAIL: editor workspace data source {$dataSource} is missing.\\n");
        exit(1);
    }
}

echo "[editor-foundation] PASS: Business content, design, branding and media share one editor workspace.\\n";
