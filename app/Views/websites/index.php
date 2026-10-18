<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    return number_format($bytes / 1048576, 2) . ' MB';
};
?>
<section class="client-dashboard iw-client-page iw-websites-list">
<div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Website Projects</p>
        <h1>My Websites</h1>
        <p>Create and manage website projects, edit draft files, preview changes, and publish when ready.</p>
      </div>
      <a class="btn btn-primary <?= $canCreate ? '' : 'disabled' ?>" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/create', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="plus"></i> Create Website
      </a>
    </header>

    <section class="client-card">
      <div class="client-card-head">
        <h2>Projects</h2>
        <span><?= count($websites) ?> total</span>
      </div>

      <?php if ($websites === []): ?>
        <div class="client-empty">
          <i data-lucide="folder-plus"></i>
          <strong>No websites yet</strong>
          <p>Create your first project to prepare draft files outside the public web root.</p>
        </div>
      <?php else: ?>
        <div class="client-site-list">
          <?php foreach ($websites as $website): ?>
            <article class="client-site">
              <div class="site-icon"><i data-lucide="globe-2"></i></div>
              <div>
                <strong>
                  <a href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($website['website_name'] ?: 'Untitled website', ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </strong>
                <span>
                  <?php if (($website['status'] ?? '') === 'published' && !empty($website['public_url'])): ?>
                    <?= htmlspecialchars((string) $website['public_url'], ENT_QUOTES, 'UTF-8') ?>
                  <?php elseif (($website['status'] ?? '') === 'draft'): ?>
                    Not published yet
                  <?php else: ?>
                    Public URL unavailable
                  <?php endif; ?>
                  · <?= htmlspecialchars($formatBytes((int) $website['storage_used']), ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>
              <div class="client-site-actions">
                <span class="client-status <?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (($website['status'] ?? '') === 'published' && !empty($website['public_url'])): ?>
                  <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($website['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Visit site</a>
                  <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i> Manage</a>
                <?php elseif (($website['status'] ?? '') === 'draft'): ?>
                  <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="pencil"></i> Continue editing</a>
                <?php else: ?>
                  <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i> Manage</a>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="folder-open"></i> File manager</a>
                <?php if (($website['status'] ?? '') === 'published' && !empty($website['public_url'])): ?>
                  <button type="button" class="btn btn-sm btn-outline-secondary btn-share-site" data-share-url="<?= htmlspecialchars($website['public_url'], ENT_QUOTES, 'UTF-8') ?>" title="Share site"><i data-lucide="share-2"></i> Share</button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!$canCreate): ?>
        <p class="text-secondary mt-4 mb-0">Free plan allows one active website. Upgrade to Premium when you are ready to create more projects.</p>
      <?php endif; ?>
    </section>
  </div>
</section>
