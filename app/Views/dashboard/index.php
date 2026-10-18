<?php
$plan = strtolower((string) ($plan ?? $subscription['plan'] ?? 'free'));
$plans = $planDetails ?? (new \App\Services\PlanService())->plans();
$currentPlan = $plans[$plan] ?? $plans['free'];
$storageLimit = $storageLimitBytes ?? ($plan === 'premium' ? (int) ($currentPlan['storage_mb'] ?? 0) * BYTES_PER_MB : (int) ($freeStorageBytes ?? (new \App\Services\PlanService())->freeStorageBytes()));
$storageUsed = (int) ($stats['storage_used'] ?? 0);
$storagePercent = $storageLimit > 0 ? min(100, (int) round(($storageUsed / $storageLimit) * 100)) : 0;
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    return number_format($bytes / 1048576, 2) . ' MB';
};
$websiteCount = (int) ($stats['websites'] ?? count($websites ?? []));
$publishedCount = (int) ($stats['published_websites'] ?? 0);
$notificationCount = count($notifications ?? []);
$baseUrl = (string) ($app['base_url'] ?? '');
?>
<main class="iw-home">
  <div class="iw-page-heading">
    <div>
      <p class="iw-eyebrow">Your workspace / Overview</p>
      <h1>Welcome back, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>.</h1>
      <p>Your websites, files and hosting details, all in one place. Pick up where you left off.</p>
    </div>
    <a class="iw-button" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/create', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="plus"></i> Create a website</a>
  </div>

  <section class="iw-hero" aria-labelledby="iwHeroTitle">
    <div class="iw-hero-copy">
      <span class="iw-hero-kicker">Make your next move</span>
      <h2 id="iwHeroTitle">Your online presence, under control.</h2>
      <p>Publish a new idea, update an existing site, or check how your hosting resources are doing. No digging through menus required.</p>
    </div>
    <div class="iw-hero-action">
      <a class="iw-button iw-button-light" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>">Manage websites <i data-lucide="arrow-up-right"></i></a>
    </div>
  </section>

  <section class="iw-stats" aria-label="Account overview">
    <article class="iw-stat">
      <div class="iw-stat-top"><span>All websites</span><span class="iw-stat-icon"><i data-lucide="panels-top-left"></i></span></div>
      <strong class="iw-stat-value"><?= number_format($websiteCount) ?></strong>
      <span class="iw-stat-note">Websites in your workspace</span>
    </article>
    <article class="iw-stat">
      <div class="iw-stat-top"><span>Published</span><span class="iw-stat-icon"><i data-lucide="globe-2"></i></span></div>
      <strong class="iw-stat-value"><?= number_format($publishedCount) ?></strong>
      <span class="iw-stat-note">Live and ready to visit</span>
    </article>
    <article class="iw-stat">
      <div class="iw-stat-top"><span>Storage used</span><span class="iw-stat-icon"><i data-lucide="database"></i></span></div>
      <strong class="iw-stat-value"><?= htmlspecialchars($formatBytes($storageUsed), ENT_QUOTES, 'UTF-8') ?></strong>
      <span class="iw-stat-note"><?= $storageLimit > 0 ? $storagePercent . '% of your storage limit' : 'Unlimited storage' ?></span>
    </article>
    <article class="iw-stat">
      <div class="iw-stat-top"><span>Bandwidth</span><span class="iw-stat-icon"><i data-lucide="activity"></i></span></div>
      <strong class="iw-stat-value"><?= htmlspecialchars($formatBytes((int) ($stats['bandwidth_used'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong>
      <span class="iw-stat-note">Recorded usage</span>
    </article>
  </section>

  <div class="iw-content-grid">
    <div class="iw-column">
      <section class="iw-panel" aria-labelledby="iwSitesTitle">
        <div class="iw-panel-heading">
          <div><h2 id="iwSitesTitle">Your websites</h2><p>Quick access to the sites you own.</p></div>
          <a class="iw-text-link" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>">View all <i data-lucide="arrow-right"></i></a>
        </div>
        <?php if (empty($websites)): ?>
          <div class="iw-empty">
            <span class="iw-empty-icon"><i data-lucide="panels-top-left"></i></span>
            <h3>Your first website starts here.</h3>
            <p>You haven’t created a website yet. Start with a blank site and publish it when you’re ready.</p>
            <a class="iw-button" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/create', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="plus"></i> Create your first website</a>
          </div>
        <?php else: ?>
          <div class="iw-site-list">
            <?php foreach ($websites as $website): ?>
              <?php
                $websiteId = (int) ($website['id'] ?? 0);
                $websiteName = trim((string) ($website['website_name'] ?? '')) ?: 'Untitled website';
                $websiteStatus = strtolower((string) ($website['status'] ?? 'unknown'));
                $manageUrl = $baseUrl . '/dashboard/websites/show?id=' . $websiteId;
                $filesUrl = $baseUrl . '/dashboard/websites/files?id=' . $websiteId;
                $publicUrl = trim((string) ($website['public_url'] ?? ''));
              ?>
              <article class="iw-site">
                <span class="iw-site-icon"><i data-lucide="globe-2"></i></span>
                <div class="iw-site-info">
                  <a class="iw-site-name" href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($websiteName, ENT_QUOTES, 'UTF-8') ?></a>
                  <span class="iw-site-url"><?php if ($websiteStatus === 'published' && $publicUrl !== ''): ?><?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?><?php elseif ($websiteStatus === 'draft'): ?>Not published yet<?php else: ?>Website details and settings<?php endif; ?></span>
                  <div class="iw-site-meta"><span class="iw-status <?= htmlspecialchars($websiteStatus, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($websiteStatus, ENT_QUOTES, 'UTF-8') ?></span></div>
                </div>
                <div class="iw-site-actions">
                  <?php if ($websiteStatus === 'published' && $publicUrl !== ''): ?>
                    <a class="iw-button iw-button-light" href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Visit site</a>
                    <a class="iw-button iw-button-outline" href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>">Manage</a>
                  <?php elseif ($websiteStatus === 'draft'): ?>
                    <a class="iw-button" href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="pencil"></i> Continue editing</a>
                  <?php else: ?>
                    <a class="iw-button iw-button-outline" href="<?= htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i> Manage</a>
                  <?php endif; ?>
                  <a class="iw-button iw-button-outline" href="<?= htmlspecialchars($filesUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Manage files for <?= htmlspecialchars($websiteName, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="folder"></i></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="iw-panel" aria-labelledby="iwFilesTitle">
        <div class="iw-panel-heading"><div><h2 id="iwFilesTitle">Recent files</h2><p>Files you’ve uploaded to your websites.</p></div></div>
        <?php if (empty($recentFiles)): ?>
          <p class="iw-muted-empty">No uploaded files yet. Files added through a website’s file manager will appear here.</p>
        <?php else: ?>
          <div class="iw-files">
            <?php foreach ($recentFiles as $file): ?>
              <div class="iw-file">
                <span class="iw-file-icon"><i data-lucide="file-text"></i></span>
                <div class="iw-file-info">
                  <strong><?= htmlspecialchars((string) ($file['filename'] ?? 'Unnamed file'), ENT_QUOTES, 'UTF-8') ?></strong>
                  <span><?= htmlspecialchars((string) ($file['website_name'] ?? 'Website'), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($formatBytes((int) ($file['filesize'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>

    <div class="iw-column iw-side-column">
      <section class="iw-panel" id="iwSubscription" aria-labelledby="iwPlanTitle">
        <div class="iw-panel-heading"><div><h2 id="iwPlanTitle">Your plan</h2><p>Know what’s included in your workspace.</p></div></div>
        <div class="iw-plan-banner">
          <div class="iw-plan-top">
            <div class="iw-plan-name"><i data-lucide="<?= $plan === 'premium' ? 'crown' : 'zap' ?>"></i><?= htmlspecialchars((string) ($currentPlan['name'] ?? 'Free'), ENT_QUOTES, 'UTF-8') ?></div>
            <span class="iw-plan-label"><?= htmlspecialchars((string) ($subscription['status'] ?? 'active'), ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <ul class="iw-benefits">
            <?php foreach (($currentPlan['benefits'] ?? []) as $benefit): ?><li><?= htmlspecialchars((string) $benefit, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
          </ul>
          <?php if ($plan === 'premium'): ?>
            <a class="iw-button iw-button-outline iw-full-button" href="<?= htmlspecialchars($baseUrl . '/pricing', ENT_QUOTES, 'UTF-8') ?>">View plans</a>
          <?php else: ?>
            <form method="POST" action="<?= htmlspecialchars($baseUrl . '/payments/paychangu/premium', ENT_QUOTES, 'UTF-8') ?>">
              <?= \App\Core\Csrf::field() ?>
              <button class="iw-button iw-button-light iw-full-button" type="submit">Upgrade plan <i data-lucide="arrow-up-right"></i></button>
            </form>
          <?php endif; ?>
        </div>
      </section>

      <section class="iw-panel" id="iwStorage" aria-labelledby="iwStorageTitle">
        <div class="iw-panel-heading"><div><h2 id="iwStorageTitle">Storage</h2><p>Space used by your website files.</p></div></div>
        <div class="iw-storage-head"><span>Used</span><strong><?= htmlspecialchars($formatBytes($storageUsed), ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="iw-progress" role="progressbar" aria-label="Storage used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $storageLimit > 0 ? $storagePercent : 0 ?>"><span style="width: <?= $storageLimit > 0 ? $storagePercent : 0 ?>%"></span></div>
        <p class="iw-storage-note"><?= $storageLimit > 0 ? htmlspecialchars($formatBytes($storageLimit), ENT_QUOTES, 'UTF-8') . ' total available · ' . max(0, 100 - $storagePercent) . '% remaining' : 'Your current plan has no configured storage limit.' ?></p>
      </section>

      <section class="iw-panel" aria-labelledby="iwNotificationsTitle">
        <div class="iw-panel-heading">
          <div><h2 id="iwNotificationsTitle">Latest updates</h2><p><?= number_format($notificationCount) ?> recent notification<?= $notificationCount === 1 ? '' : 's' ?></p></div>
          <a class="iw-text-link" href="<?= htmlspecialchars($baseUrl . '/dashboard/notifications', ENT_QUOTES, 'UTF-8') ?>">All <i data-lucide="arrow-right"></i></a>
        </div>
        <?php if (empty($notifications)): ?>
          <p class="iw-muted-empty">You’re all caught up. New account and website updates will appear here.</p>
        <?php else: ?>
          <div class="iw-notifications">
            <?php foreach (array_slice($notifications, 0, 4) as $notification): ?>
              <article class="iw-notification">
                <span class="iw-notification-dot" aria-hidden="true"></span>
                <div>
                  <strong><?= htmlspecialchars((string) ($notification['title'] ?? 'Update'), ENT_QUOTES, 'UTF-8') ?></strong>
                  <p><?= htmlspecialchars((string) ($notification['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                  <?php $notificationTime = strtotime((string) ($notification['created_at'] ?? '')); ?>
                  <?php if ($notificationTime !== false): ?><time datetime="<?= htmlspecialchars(date(DATE_ATOM, $notificationTime), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('M j, Y · H:i', $notificationTime), ENT_QUOTES, 'UTF-8') ?></time><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
      <?php if (!empty($isAdmin)): ?><a class="iw-button iw-button-outline iw-full-button" href="<?= htmlspecialchars($baseUrl . '/admin', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="shield-check"></i> Open admin console</a><?php endif; ?>
    </div>
  </div>
</main>
