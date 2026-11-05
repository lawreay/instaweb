<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<section class="client-dashboard iw-client-page business-editor business-themes">
    <?php $editorActive = 'themes'; require __DIR__ . '/partials/editor-navigation.php'; ?>
    <header class="be-head">
        <div>
            <p class="text-uppercase fw-bold small text-muted mb-2">Business website / Appearance</p>
            <h1>Choose a theme</h1>
            <p class="be-muted mb-0">Change the visual style without changing your website content.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= $e($baseUrl . '/dashboard/websites/business?id=' . (int) $website['id']) ?>">Back to content</a>
    </header>
    <div class="be-grid">
        <?php foreach ($themes as $key => $theme): ?>
            <article class="be-card">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h2 class="h5 fw-bold mb-1"><?= $e($theme['name']) ?></h2>
                        <p class="be-muted mb-0"><?= $e($theme['description']) ?></p>
                    </div>
                    <?php if ($currentTheme === $key): ?><span class="badge text-bg-success">Current theme</span><?php endif; ?>
                </div>
                <div class="ratio ratio-16x9 border rounded overflow-hidden bg-light my-3">
                    <iframe title="<?= $e($theme['name']) ?> live preview"
                        src="<?= $e($baseUrl . '/dashboard/websites/business/theme-preview?id=' . (int) $website['id'] . '&theme=' . rawurlencode($key)) ?>"
                        loading="lazy" sandbox="allow-same-origin"></iframe>
                </div>
                <form method="POST" action="<?= $e($baseUrl . '/dashboard/websites/business/theme/apply') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                    <input type="hidden" name="theme_key" value="<?= $e($key) ?>">
                    <button class="btn <?= $currentTheme === $key ? 'btn-outline-secondary' : 'btn-primary' ?> w-100"
                        type="submit" <?= $currentTheme === $key ? 'disabled' : '' ?>>
                        <?= $currentTheme === $key ? 'Currently applied' : 'Apply theme' ?>
                    </button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="be-muted mt-2">Applying a theme updates your private draft only. Your published website changes after you publish again.</p>
</section>
