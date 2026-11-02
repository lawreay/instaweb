<?php $baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$previewPrefix = !empty($adminPreview) ? '/admin/websites/preview' : '/dashboard/websites/preview';
$previewFileUrl = !empty($adminPreview) && ($website['template_key'] ?? '') === 'portfolio'
    ? $baseUrl . '/admin/websites/preview/portfolio?id=' . (int) $website['id']
    : $baseUrl . $previewPrefix . '/file?id=' . (int) $website['id'] . '&path=index.html'; ?>
<section class="iw-client-page iw-preview-page">

    <header class="client-header">
      <div>
        <p class="auth-eyebrow"><?= !empty($adminPreview) ? 'Administrator Draft Preview' : 'Owner-Only Draft' ?></p>
        <h1><?= htmlspecialchars($website['website_name'] ?: 'Website Preview', ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= !empty($adminPreview) ? 'Private draft preview. This site has not been published.' : 'This preview loads from private draft storage and is visible only to the project owner.' ?></p>
      </div>
      <div class="client-header-actions">
        <?php if (!empty($adminPreview)): ?><a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/admin/websites', ENT_QUOTES, 'UTF-8') ?>">Back to websites</a><?php else: ?>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i> Manage site</a>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="folder-open"></i> File Manager</a>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/editor?id=' . (int) $website['id'] . '&path=index.html', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="code-2"></i> Edit index.html</a>
        <?php endif; ?>
        <?php if ($liveUrl !== ''): ?>
          <a class="btn btn-primary" href="<?= htmlspecialchars($liveUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Visit live site</a>
        <?php endif; ?>
      </div>
    </header>

    <section class="client-card">
      <iframe
        title="Website preview"
        sandbox="allow-scripts allow-forms"
        referrerpolicy="no-referrer"
        src="<?= htmlspecialchars($baseUrl . $previewPrefix . '/file?id=' . (int) $website['id'] . '&path=index.html', ENT_QUOTES, 'UTF-8') ?>"
        style="width: 100%; min-height: 70vh; border: 1px solid rgba(13, 110, 253, 0.18); border-radius: 16px; background: #ffffff;"
      ></iframe>
    </section>
</section>
