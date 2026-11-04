<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="client-dashboard iw-client-page business-editor">
    <?php $editorActive = 'branding'; require __DIR__ . '/partials/editor-navigation.php'; ?>
    <header class="be-head">
        <div>
            <p class="text-uppercase fw-bold small text-muted mb-2">Business website / Identity</p>
            <h1>Branding</h1>
            <p class="be-muted mb-0">Add your business logo and browser favicon.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= $e($baseUrl . '/dashboard/websites/business?id=' . (int) $website['id']) ?>">Back to content</a>
    </header>
    <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl . '/dashboard/websites/business/branding/save') ?>">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
        <section class="be-card">
            <h2 class="h5 fw-bold">Business logo</h2>
            <?php if (!empty($branding['logo_path'])): ?>
                <p><img src="<?= $e($baseUrl . '/dashboard/websites/preview/file?id=' . (int) $website['id'] . '&path=' . rawurlencode($branding['logo_path'])) ?>" alt="Current business logo" style="max-width:220px;max-height:90px;object-fit:contain"></p>
            <?php endif; ?>
            <label>Upload logo (JPEG, PNG or WebP, max 3 MB)
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
            </label>
        </section>
        <section class="be-card">
            <h2 class="h5 fw-bold">Browser favicon</h2>
            <?php if (!empty($branding['favicon_path'])): ?>
                <p><img src="<?= $e($baseUrl . '/dashboard/websites/preview/file?id=' . (int) $website['id'] . '&path=' . rawurlencode($branding['favicon_path'])) ?>" alt="Current favicon" style="width:48px;height:48px;object-fit:contain"></p>
            <?php endif; ?>
            <label>Upload favicon (square image recommended, max 3 MB and 1024 × 1024 pixels)
                <input type="file" name="favicon" accept="image/jpeg,image/png,image/webp">
            </label>
        </section>
        <div class="be-actions mb-4">
            <button class="btn btn-primary" type="submit">Save branding</button>
        </div>
    </form>
    <p class="be-muted">Changes update your private draft. Publish the website to make them visible publicly.</p>
</section>
