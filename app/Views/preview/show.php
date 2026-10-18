<?php $baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/'); ?>
<section class="client-dashboard">
<div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Owner-Only Draft</p>
        <h1><?= htmlspecialchars($website['website_name'] ?: 'Website Preview', ENT_QUOTES, 'UTF-8') ?></h1>
        <p>This preview loads from private draft storage and is visible only to the project owner.</p>
      </div>
    </header>

    <section class="client-card">
      <iframe
        title="Website preview"
        src="<?= htmlspecialchars($baseUrl . '/dashboard/websites/preview/file?id=' . (int) $website['id'] . '&path=index.html', ENT_QUOTES, 'UTF-8') ?>"
        style="width: 100%; min-height: 70vh; border: 1px solid rgba(13, 110, 253, 0.18); border-radius: 16px; background: #ffffff;"
      ></iframe>
    </section>
  </div>
</section>
