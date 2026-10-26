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
            <?php
              $websiteId = (int) $website['id'];
              $websiteStatus = (string) ($website['status'] ?? 'draft');
              $publicUrl = (string) ($website['public_url'] ?? '');
              $isPublished = $websiteStatus === 'published' && $publicUrl !== '';
              $isSystemTemplate = !empty($website['is_system_template']);
              $manageUrl = $baseUrl . '/dashboard/websites/show?id=' . $websiteId;
              $previewUrl = $baseUrl . '/dashboard/websites/preview?id=' . $websiteId;
            ?>
            <article class="client-site">
              <div class="site-icon"><i data-lucide="globe-2"></i></div>
              <div class="client-site-info">
                <strong>
                  <a href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($website['website_name'] ?: 'Untitled website', ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </strong>
                <span>
                  <?php if ($isPublished): ?>
                    <?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>
                  <?php elseif ($websiteStatus === 'draft'): ?>
                    Draft website · not published yet
                  <?php else: ?>
                    Public URL unavailable
                  <?php endif; ?>
                  · <?= htmlspecialchars($formatBytes((int) ($website['storage_used'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>
                </span>
              </div>
              <div class="client-site-actions">
                <span class="client-status <?= htmlspecialchars($websiteStatus, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($websiteStatus), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($isSystemTemplate): ?><span class="badge text-bg-primary">Default template</span><?php endif; ?>

                <?php if ($isPublished): ?>
                  <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Visit</a>
                  <button type="button" class="btn btn-sm btn-outline-secondary btn-share-site" data-share-url="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" title="Copy public website link"><i data-lucide="copy"></i> Copy Link</button>
                <?php else: ?>
                  <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="file-text"></i> Draft</a>
                <?php endif; ?>

                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i> Manage</a>

                <?php if (!$isSystemTemplate): ?>
                  <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/delete', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Delete this website project and its files? This cannot be undone.');">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $websiteId ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2"></i> Delete</button>
                  </form>
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
