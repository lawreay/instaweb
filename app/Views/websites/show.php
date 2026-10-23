<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$liveUrl = (string) ($liveUrl ?? '');
$templateKey = strtolower((string) ($website['template_key'] ?? 'blank'));
$managedContentType = in_array($templateKey, ['wordpress', 'portfolio'], true);
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    return number_format($bytes / 1048576, 2) . ' MB';
};
?>
<section class="client-dashboard iw-client-page iw-website-detail">
<div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Project Summary</p>
        <h1><?= htmlspecialchars($website['website_name'] ?: 'Website', ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($liveUrl !== ''): ?>
          <p><?= htmlspecialchars($liveUrl, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (($website['status'] ?? '') === 'draft'): ?>
          <p>This is a draft. Publish it when you are ready to make it public.</p>
        <?php else: ?>
          <p>This website is not currently available publicly.</p>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/edit?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="pencil"></i> Edit</a>
        <?php if (!$managedContentType): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="upload-cloud"></i> File Manager</a>
        <?php endif; ?>
        <?php if (!$managedContentType): ?>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/editor?id=' . (int) $website['id'] . '&path=index.html', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="code-2"></i> Code Editor</a>
        <?php endif; ?>
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/preview?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="eye"></i> Preview</a>
      </div>
      <?php if ($templateKey === 'wordpress'): ?>
        <p class="text-secondary mt-3 mb-0">WordPress files are managed by WordPress. File Manager and the code editor are disabled for this project.</p>
      <?php elseif ($templateKey === 'portfolio'): ?>
        <p class="text-secondary mt-3 mb-0">Portfolio content is managed through the portfolio editor. File Manager and the code editor are disabled for this project.</p>
      <?php endif; ?>
    </header>

    <div class="client-stat-grid">
      <div class="client-card client-stat">
        <i data-lucide="activity"></i>
        <span>Status</span>
        <strong><?= htmlspecialchars(ucfirst($website['status']), ENT_QUOTES, 'UTF-8') ?></strong>
        <small>
          <?php if (($website['status'] ?? '') === 'published'): ?>
            Marked as published
          <?php elseif (($website['status'] ?? '') === 'draft'): ?>
            Not published yet
          <?php else: ?>
            This website requires attention
          <?php endif; ?>
        </small>
      </div>
      <div class="client-card client-stat">
        <i data-lucide="hard-drive"></i>
        <span>Storage</span>
        <strong><?= htmlspecialchars($formatBytes((int) $website['storage_used']), ENT_QUOTES, 'UTF-8') ?></strong>
        <small>Draft files</small>
      </div>
      <div class="client-card client-stat">
        <i data-lucide="folder"></i>
        <span>Draft Folder</span>
        <strong>Ready</strong>
        <small>Stored outside public web root</small>
      </div>
    </div>

    <section class="client-card mt-4">
      <div class="client-card-head">
        <h2>Project Details</h2>
        <span class="client-status <?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <div class="client-mini-list">
        <div>
          <strong>Slug</strong>
          <span><?= htmlspecialchars($website['slug'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div>
          <strong>Draft path</strong>
          <span><?= htmlspecialchars($draftPath, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div>
          <strong>Expires</strong>
          <span><?= htmlspecialchars($website['expires_at'] ?: 'No expiry', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap mt-4">
        <?php if (($website['status'] ?? '') === 'draft'): ?>
          <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/publish', ENT_QUOTES, 'UTF-8') ?>">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
            <button class="btn btn-primary" type="submit"><i data-lucide="rocket"></i> Publish Website</button>
          </form>
        <?php elseif (($website['status'] ?? '') === 'suspended'): ?>
          <p class="alert alert-warning mb-0" role="status">This website is suspended by an administrator and cannot be published.</p>
        <?php endif; ?>
        <?php if (($website['status'] ?? '') === 'published'): ?>

          <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="<?= htmlspecialchars($liveUrl, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="external-link"></i> Open Live Site</a>
          <button type="button" class="btn btn-outline-secondary btn-share-site" data-share-url="<?= htmlspecialchars($liveUrl, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="share-2"></i> Share</button>
          <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/unpublish', ENT_QUOTES, 'UTF-8') ?>">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
            <button class="btn btn-outline-danger" type="submit"><i data-lucide="cloud-off"></i> Unpublish</button>
          </form>
        <?php endif; ?>
      </div>

      <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/delete', ENT_QUOTES, 'UTF-8') ?>" class="mt-4" onsubmit="return confirm('Delete this website project and draft files?');">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
        <button class="btn btn-outline-danger" type="submit"><i data-lucide="trash-2"></i> Delete Website</button>
      </form>
    </section>
  </div>
</section>
