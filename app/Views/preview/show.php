<?php $baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/'); ?>
<section class="client-dashboard iw-client-page iw-preview-page">
  <aside class="client-sidebar">
    <div class="client-profile">
      <div class="client-avatar"><i data-lucide="eye"></i></div>
      <strong>Preview</strong>
      <span><?= htmlspecialchars($website['website_name'] ?: 'Website', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <nav class="client-nav">
      <a href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="arrow-left"></i> Project</a>
      <a href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="folder"></i> File Manager</a>
      <a href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/editor?id=' . (int) $website['id'] . '&path=index.html', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="code-2"></i> Editor</a>
    </nav>
  </aside>

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
