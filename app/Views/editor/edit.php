<?php $baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/'); ?>
<section class="iw-client-page iw-editor-page">

    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Draft Editor · <?= htmlspecialchars($website['website_name'] ?: 'Website', ENT_QUOTES, 'UTF-8') ?></p>
        <h1><?= htmlspecialchars($file['relative_path'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Save updates to private draft storage before previewing or publishing.</p>
      </div>
      <div class="client-header-actions">
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i> Manage site</a>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="folder-open"></i> File Manager</a>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/preview?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="eye"></i> Preview</a>
        <?php if ($liveUrl !== ''): ?>
          <a class="btn btn-primary" href="<?= htmlspecialchars($liveUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Visit live site</a>
        <?php endif; ?>
      </div>
    </header>

    <section class="client-card">
      <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/editor/save', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
        <input type="hidden" name="path" value="<?= htmlspecialchars($file['relative_path'], ENT_QUOTES, 'UTF-8') ?>">
        <textarea class="form-control font-monospace" name="content" rows="24" spellcheck="false"><?= htmlspecialchars($file['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save</button>
          <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/preview?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="eye"></i> Preview</a>
        </div>
      </form>
    </section>
</section>
