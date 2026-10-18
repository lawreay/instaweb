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
$baseUrl = (string) ($app['base_url'] ?? '');
$h = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$websiteCount = (int) ($stats['websites'] ?? 0);
$publishedCount = (int) ($stats['published_websites'] ?? 0);
$bandwidthUsed = (int) ($stats['bandwidth_used'] ?? 0);
$notificationCount = count($notifications ?? []);
?>
<section class="client-dashboard">
  <aside class="client-sidebar" aria-label="Client workspace navigation">
    <div class="client-sidebar-top">
      <div class="client-profile">
        <div class="client-avatar"><?= $h(strtoupper(substr((string) $displayName, 0, 1))) ?></div>
        <div class="client-profile-copy">
          <strong><?= $h($displayName) ?></strong>
          <span>Workspace owner</span>
        </div>
        <span class="plan-chip <?= $h($plan) ?>"><?= $h($currentPlan['name'] ?? 'Free') ?></span>
      </div>

      <p class="client-nav-label">WORKSPACE</p>
      <nav class="client-nav" aria-label="Main navigation">
        <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= $h($baseUrl . '/dashboard') ?>"><i data-lucide="layout-dashboard"></i><span>Overview</span></a>
        <a class="<?= ($active ?? '') === 'websites' ? 'active' : '' ?>" href="<?= $h($baseUrl . '/dashboard/websites') ?>"><i data-lucide="panels-top-left"></i><span>My websites</span><span class="nav-count"><?= $websiteCount ?></span></a>
        <a href="<?= $h($baseUrl . '/dashboard#storage') ?>"><i data-lucide="database"></i><span>Storage</span></a>
        <a href="<?= $h($baseUrl . '/dashboard#subscription') ?>"><i data-lucide="credit-card"></i><span>Plan & billing</span></a>
        <a class="<?= ($active ?? '') === 'notifications' ? 'active' : '' ?>" href="<?= $h($baseUrl . '/dashboard/notifications') ?>"><i data-lucide="bell"></i><span>Notifications</span><?php if ($notificationCount > 0): ?><span class="nav-dot" aria-label="<?= $notificationCount ?> notifications"></span><?php endif; ?></a>
      </nav>

      <p class="client-nav-label client-nav-label-spaced">ACCOUNT</p>
      <nav class="client-nav" aria-label="Account navigation">
        <a class="<?= ($active ?? '') === 'profile' ? 'active' : '' ?>" href="<?= $h($baseUrl . '/profile') ?>"><i data-lucide="user-round"></i><span>Profile settings</span></a>
        <?php if ($isAdmin): ?>
          <a href="<?= $h($baseUrl . '/admin') ?>"><i data-lucide="shield-check"></i><span>Admin console</span></a>
        <?php endif; ?>
      </nav>
    </div>

    <div class="client-sidebar-bottom">
      <div class="sidebar-help">
        <span class="sidebar-help-icon"><i data-lucide="life-buoy"></i></span>
        <div><strong>Need a hand?</strong><span>Manage your site files or settings.</span></div>
        <a href="<?= $h($baseUrl . '/dashboard/websites') ?>" aria-label="Open website management"><i data-lucide="arrow-up-right"></i></a>
      </div>
      <a href="<?= $h($baseUrl . '/logout') ?>" class="client-logout"><i data-lucide="log-out"></i><span>Sign out</span></a>
    </div>
  </aside>

  <main class="client-main">
    <header class="workspace-topline">
      <div class="workspace-breadcrumb"><span>Workspace</span><i data-lucide="chevron-right"></i><strong>Overview</strong></div>
      <div class="workspace-top-actions">
        <a class="workspace-icon-button" href="<?= $h($baseUrl . '/dashboard/notifications') ?>" aria-label="Notifications"><i data-lucide="bell"></i><?php if ($notificationCount > 0): ?><span></span><?php endif; ?></a>
        <a class="workspace-user" href="<?= $h($baseUrl . '/profile') ?>"><span class="workspace-user-avatar"><?= $h(strtoupper(substr((string) $displayName, 0, 1))) ?></span><span><?= $h($displayName) ?></span><i data-lucide="chevron-down"></i></a>
      </div>
    </header>

    <section class="dashboard-welcome">
      <div class="welcome-copy">
        <p class="eyebrow"><span class="eyebrow-mark"></span> YOUR DIGITAL WORKSPACE</p>
        <h1>Make the web <span>yours.</span></h1>
        <p class="welcome-subtitle">Everything you need to keep your websites moving, in one clear view.</p>
        <div class="welcome-actions">
          <a class="btn btn-create-site" href="<?= $h($baseUrl . '/dashboard/websites/create') ?>"><i data-lucide="plus"></i>Create a website</a>
          <a class="btn btn-quiet" href="<?= $h($baseUrl . '/dashboard/websites') ?>">Manage websites<i data-lucide="arrow-up-right"></i></a>
        </div>
      </div>
      <div class="welcome-art" aria-hidden="true">
        <div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div>
        <div class="art-window">
          <div class="art-window-top"><span></span><span></span><span></span><i data-lucide="panel-top"></i></div>
          <div class="art-window-content"><div class="art-window-line"></div><div class="art-window-line short"></div><div class="art-window-block"></div><div class="art-window-footer"><span></span><span></span><span></span></div></div>
        </div>
        <span class="art-spark spark-one">✳</span><span class="art-spark spark-two">✦</span><span class="art-pill">YOUR SPACE, ONLINE</span>
      </div>
      <div class="welcome-index"><span>01</span><span class="welcome-index-line"></span><span>CONTROL CENTER</span></div>
    </section>

    <div class="section-heading">
      <div><p class="eyebrow">AT A GLANCE</p><h2>Your workspace, summarized</h2></div>
      <span class="last-updated"><i data-lucide="activity"></i> Live account data</span>
    </div>

    <div class="client-stat-grid">
      <article class="client-card client-stat stat-sites">
        <div class="stat-top"><span class="stat-icon"><i data-lucide="panels-top-left"></i></span><span class="stat-kicker">SITES</span></div>
        <strong class="stat-number"><?= number_format($websiteCount) ?></strong>
        <span class="stat-label">Total websites</span>
        <div class="stat-foot"><span class="stat-foot-mark"></span><?= number_format($publishedCount) ?> published <span class="stat-foot-divider">/</span> <?= number_format(max(0, $websiteCount - $publishedCount)) ?> in progress</div>
      </article>
      <article class="client-card client-stat stat-storage">
        <div class="stat-top"><span class="stat-icon"><i data-lucide="database"></i></span><span class="stat-kicker">STORAGE</span></div>
        <strong class="stat-number"><?= $h($formatBytes($storageUsed)) ?></strong>
        <span class="stat-label">Space in use</span>
        <div class="stat-progress"><span style="width: <?= $storageLimit > 0 ? $storagePercent : 8 ?>%"></span></div>
        <div class="stat-foot"><?= $storageLimit > 0 ? $storagePercent . '% of ' . $h($formatBytes($storageLimit)) . ' used' : 'Unlimited storage on your plan' ?></div>
      </article>
      <article class="client-card client-stat stat-bandwidth">
        <div class="stat-top"><span class="stat-icon"><i data-lucide="activity"></i></span><span class="stat-kicker">BANDWIDTH</span></div>
        <strong class="stat-number"><?= $h($formatBytes($bandwidthUsed)) ?></strong>
        <span class="stat-label">Traffic consumed</span>
        <div class="stat-foot"><span class="stat-neutral-dot"></span>Current usage recorded</div>
      </article>
      <article class="client-card client-stat stat-plan">
        <div class="stat-top"><span class="stat-icon"><i data-lucide="<?= $plan === 'premium' ? 'crown' : 'zap' ?>"></i></span><span class="stat-kicker">MEMBERSHIP</span></div>
        <strong class="stat-number stat-plan-name"><?= $h($currentPlan['name'] ?? 'Free') ?></strong>
        <span class="stat-label">Current subscription</span>
        <div class="stat-foot"><span class="plan-status-dot"></span><?= $h(ucfirst((string) ($subscription['status'] ?? 'active'))) ?><a href="#subscription">View plan <i data-lucide="arrow-up-right"></i></a></div>
      </article>
    </div>

    <div class="client-grid">
      <section class="client-card websites-card client-wide" id="websites">
        <div class="client-card-head">
          <div><p class="eyebrow">YOUR PROJECTS</p><h2>Websites <span class="heading-count"><?= $websiteCount ?></span></h2><p class="card-subtitle">The places you’re building on the web.</p></div>
          <a class="btn btn-outline-primary btn-new-website" href="<?= $h($baseUrl . '/dashboard/websites/create') ?>"><i data-lucide="plus"></i> New site</a>
        </div>
        <?php if ($websites === []): ?>
          <div class="client-empty">
            <div class="empty-illustration"><i data-lucide="panels-top-left"></i><span>+</span></div>
            <strong>Your first website starts here.</strong>
            <p>Create a project, add your files, and publish when you’re ready.</p>
            <a class="btn btn-create-site" href="<?= $h($baseUrl . '/dashboard/websites/create') ?>"><i data-lucide="plus"></i>Create your first site</a>
          </div>
        <?php else: ?>
          <div class="client-site-list">
            <?php foreach ($websites as $website): ?>
              <?php $siteStatus = (string) ($website['status'] ?? 'unknown'); ?>
              <article class="client-site">
                <div class="site-icon"><i data-lucide="globe-2"></i></div>
                <div class="site-copy">
                  <strong><a href="<?= $h($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id']) ?>"><?= $h($website['website_name'] ?: 'Untitled website') ?></a></strong>
                  <span class="site-url">
                    <?php if ($siteStatus === 'published' && !empty($website['public_url'])): ?>
                      <i data-lucide="link-2"></i><?= $h($website['public_url']) ?>
                    <?php elseif ($siteStatus === 'draft'): ?>
                      Not published yet
                    <?php else: ?>
                      Public URL unavailable
                    <?php endif; ?>
                  </span>
                </div>
                <span class="client-status <?= $h($siteStatus) ?>"><span></span><?= $h(ucfirst($siteStatus)) ?></span>
                <div class="client-site-actions">
                  <?php if ($siteStatus === 'published' && !empty($website['public_url'])): ?>
                    <a class="btn btn-site-visit" href="<?= $h($website['public_url']) ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Visit</a>
                    <a class="btn btn-site-manage" href="<?= $h($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id']) ?>"><i data-lucide="settings-2"></i> Manage</a>
                  <?php elseif ($siteStatus === 'draft'): ?>
                    <a class="btn btn-site-visit" href="<?= $h($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id']) ?>"><i data-lucide="pencil"></i> Continue</a>
                  <?php else: ?>
                    <a class="btn btn-site-manage" href="<?= $h($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id']) ?>"><i data-lucide="settings-2"></i> Manage</a>
                  <?php endif; ?>
                  <a class="btn btn-site-files" href="<?= $h($baseUrl . '/dashboard/websites/files?id=' . (int) $website['id']) ?>" aria-label="Manage files for <?= $h($website['website_name'] ?: 'website') ?>"><i data-lucide="folder"></i></a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
          <div class="websites-card-footer"><span>Showing your <?= count($websites) === 1 ? 'latest website' : 'websites' ?></span><a href="<?= $h($baseUrl . '/dashboard/websites') ?>">View all websites <i data-lucide="arrow-right"></i></a></div>
        <?php endif; ?>
      </section>

      <section class="client-card plan-card" id="subscription">
        <div class="client-card-head"><div><p class="eyebrow">YOUR ACCESS</p><h2>Current plan</h2></div><span class="plan-card-mark"><i data-lucide="<?= $plan === 'premium' ? 'crown' : 'zap' ?>"></i></span></div>
        <div class="plan-panel">
          <span class="plan-overline"><?= $plan === 'premium' ? 'MORE ROOM TO GROW' : 'A GOOD PLACE TO START' ?></span>
          <strong><?= $h($currentPlan['name'] ?? 'Free') ?></strong>
          <p><?= $plan === 'premium' ? 'Your workspace has premium access enabled.' : 'Build and publish your online presence at your own pace.' ?></p>
          <ul>
            <?php foreach (($currentPlan['benefits'] ?? []) as $benefit): ?>
              <li><i data-lucide="check"></i><span><?= $h($benefit) ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($plan === 'premium'): ?>
            <a class="btn btn-plan-action" href="<?= $h($baseUrl . '/pricing') ?>">Explore plans <i data-lucide="arrow-up-right"></i></a>
          <?php else: ?>
            <form method="POST" action="<?= $h($baseUrl . '/payments/paychangu/premium') ?>">
              <?= \App\Core\Csrf::field() ?>
              <button class="btn btn-plan-action" type="submit">Upgrade with PayChangu <i data-lucide="arrow-up-right"></i></button>
            </form>
          <?php endif; ?>
        </div>
      </section>

      <section class="client-card storage-card" id="storage">
        <div class="client-card-head"><div><p class="eyebrow">SPACE CHECK</p><h2>Storage usage</h2></div><span class="storage-card-icon"><i data-lucide="hard-drive"></i></span></div>
        <div class="storage-large-number"><?= $h($formatBytes($storageUsed)) ?><span> used</span></div>
        <div class="storage-meter"><div class="storage-meter-track"><span style="width: <?= $storageLimit > 0 ? $storagePercent : 8 ?>%"></span></div><div class="storage-meter-labels"><span>Used</span><span><?= $storageLimit > 0 ? $h($formatBytes($storageLimit)) . ' available limit' : 'Unlimited plan' ?></span></div></div>
        <?php if ($plan !== 'premium' && $storagePercent >= 75): ?><p class="storage-notice"><i data-lucide="alert-triangle"></i>You’re getting close to your storage limit.</p><?php endif; ?>
      </section>

      <section class="client-card notification-card" id="notifications" data-notification-panel>
        <div class="client-card-head"><div><p class="eyebrow">WHAT’S HAPPENING</p><h2>Recent notifications</h2></div><a class="text-link" href="<?= $h($baseUrl . '/dashboard/notifications') ?>">View all <i data-lucide="arrow-up-right"></i></a></div>
        <div id="notificationItems">
          <?php if ($notifications === []): ?>
            <div class="compact-empty"><span><i data-lucide="bell"></i></span><p>You’re all caught up. No new notifications.</p></div>
          <?php else: ?>
            <div class="client-mini-list">
              <?php foreach ($notifications as $notification): ?>
                <article class="notification-item"><span class="notification-marker"></span><div><strong><?= $h($notification['title'] ?? 'Notification') ?></strong><p><?= $h($notification['message'] ?? '') ?></p><small><?= $h(date('M j, Y · H:i', strtotime($notification['created_at'] ?? 'now'))) ?></small></div></article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="client-card files-card">
        <div class="client-card-head"><div><p class="eyebrow">RECENT ACTIVITY</p><h2>Recently uploaded</h2></div><span class="files-card-icon"><i data-lucide="files"></i></span></div>
        <?php if ($recentFiles === []): ?>
          <div class="compact-empty"><span><i data-lucide="file-plus-2"></i></span><p>No uploaded files yet. Files you add to your websites will appear here.</p></div>
        <?php else: ?>
          <div class="client-mini-list recent-file-list">
            <?php foreach ($recentFiles as $file): ?>
              <div class="recent-file-item"><span class="file-type-icon"><i data-lucide="file"></i></span><div><strong><?= $h($file['filename'] ?? 'Unnamed file') ?></strong><span><?= $h($file['website_name'] ?? 'Website') ?> · <?= $h($formatBytes((int) ($file['filesize'] ?? 0))) ?></span></div></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>

    <footer class="dashboard-bottom-note"><span class="bottom-note-mark"><i data-lucide="shield-check"></i></span><span><strong>Your workspace, your control.</strong> Keep your account details current and only share access with people you trust.</span><a href="<?= $h($baseUrl . '/profile') ?>">Account settings <i data-lucide="arrow-up-right"></i></a></footer>
  </main>
</section>