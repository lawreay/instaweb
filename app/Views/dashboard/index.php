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
?>
<section class="client-dashboard">
  <aside class="client-sidebar">
    <div class="client-profile">
      <div class="client-avatar"><?= htmlspecialchars(strtoupper(substr((string) $displayName, 0, 1)), ENT_QUOTES, 'UTF-8') ?></div>
      <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
      <span class="plan-chip <?= htmlspecialchars($plan, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($currentPlan['name'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <nav class="client-nav">
      <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="layout-dashboard"></i> Dashboard</a>
      <a class="<?= ($active ?? '') === 'profile' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="user"></i> Profile</a>
      <a class="<?= ($active ?? '') === 'websites' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="globe-2"></i> Websites</a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard#storage', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="hard-drive"></i> Storage</a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard#subscription', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="badge-dollar-sign"></i> Subscription</a>
      <a class="<?= ($active ?? '') === 'notifications' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/notifications', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="bell"></i> Notifications</a>
      <?php if ($isAdmin): ?>

        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="shield-check"></i> Admin Console</a>
      <?php endif; ?>
    </nav>

    <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>" class="client-logout"><i data-lucide="log-out"></i> Logout</a>
  </aside>

  <div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Client Dashboard</p>
        <h1>Welcome back, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Manage your hosted websites, storage, subscription, and account activity.</p>
      </div>
      <div class="client-header-actions">
        <a class="client-header-action" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="user"></i><span>Profile</span></a>
        <a class="client-header-action" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/notifications', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="bell"></i><span>Alerts</span></a>
        <a class="client-header-action logout-action" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="log-out"></i><span>Logout</span></a>
        <a class="btn btn-primary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/websites/create', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="plus"></i> Create Website</a>
      </div>
    </header>

    <div class="client-stat-grid">
      <div class="client-card client-stat">
        <i data-lucide="globe-2"></i>
        <span>Websites</span>
        <strong><?= number_format((int) ($stats['websites'] ?? 0)) ?></strong>
        <small><?= number_format((int) ($stats['published_websites'] ?? 0)) ?> published</small>
      </div>
      <div class="client-card client-stat">
        <i data-lucide="hard-drive"></i>
        <span>Storage Used</span>
        <strong><?= htmlspecialchars($formatBytes($storageUsed), ENT_QUOTES, 'UTF-8') ?></strong>
        <small><?= $storageLimit > 0 ? $storagePercent . '% of free limit' : 'Premium unlimited' ?></small>
      </div>
      <div class="client-card client-stat">
        <i data-lucide="activity"></i>
        <span>Bandwidth</span>
        <strong><?= htmlspecialchars($formatBytes((int) ($stats['bandwidth_used'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong>
        <small>Current usage</small>
      </div>
      <div class="client-card client-stat">
        <i data-lucide="badge-check"></i>
        <span>Subscription</span>
        <strong class="<?= $plan === 'premium' ? 'text-gold' : '' ?>"><?= htmlspecialchars($currentPlan['name'], ENT_QUOTES, 'UTF-8') ?></strong>
        <small><?= htmlspecialchars($subscription['status'] ?? 'active', ENT_QUOTES, 'UTF-8') ?></small>
      </div>
    </div>

    <div class="client-grid">
      <section class="client-card client-wide" id="websites">
        <div class="client-card-head">
          <h2>Recent Websites</h2>
          <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/websites/create', ENT_QUOTES, 'UTF-8') ?>">New Website</a>
        </div>

        <?php if ($websites === []): ?>
          <div class="client-empty">
            <i data-lucide="folder-plus"></i>
            <strong>No websites yet</strong>
            <p>Create your first project and publish it on an Instaweb subdomain.</p>
          </div>
        <?php else: ?>
          <div class="client-site-list">
            <?php foreach ($websites as $website): ?>
              <article class="client-site">
                <div class="site-icon"><i data-lucide="globe-2"></i></div>
                <div>
                  <strong><a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($website['website_name'] ?: 'Untitled website', ENT_QUOTES, 'UTF-8') ?></a></strong>
                  <span><?= htmlspecialchars($website['custom_domain'] ?: ($website['subdomain'] ?: ($website['public_url'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <span class="client-status <?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?></span>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="client-card" id="subscription">
        <div class="client-card-head">
          <h2>Current Plan</h2>
        </div>
        <div class="plan-panel">
          <i data-lucide="<?= $plan === 'premium' ? 'crown' : 'zap' ?>"></i>
          <strong><?= htmlspecialchars($currentPlan['name'], ENT_QUOTES, 'UTF-8') ?></strong>
          <ul>
            <?php foreach ($currentPlan['benefits'] as $benefit): ?>
              <li><?= htmlspecialchars($benefit, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($plan === 'premium'): ?>
            <a class="btn btn-outline-primary w-100" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/pricing', ENT_QUOTES, 'UTF-8') ?>">View plans</a>
          <?php else: ?>
            <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/payments/paychangu/premium', ENT_QUOTES, 'UTF-8') ?>">
              <?= \App\Core\Csrf::field() ?>
              <button class="btn btn-primary w-100" type="submit">Upgrade with PayChangu</button>
            </form>
          <?php endif; ?>
        </div>
      </section>

      <section class="client-card" id="storage">
        <div class="client-card-head">
          <h2>Storage</h2>
        </div>
        <div class="storage-meter">
          <div class="d-flex justify-content-between">
            <span><?= htmlspecialchars($formatBytes($storageUsed), ENT_QUOTES, 'UTF-8') ?> used</span>
            <span><?= $storageLimit > 0 ? htmlspecialchars($formatBytes($storageLimit), ENT_QUOTES, 'UTF-8') : 'Unlimited' ?></span>
          </div>
          <div class="progress">
            <div class="progress-bar" style="width: <?= $storageLimit > 0 ? $storagePercent : 8 ?>%"></div>
          </div>
        </div>
      </section>

      <section class="client-card" id="notifications" data-notification-panel>
        <div class="client-card-head">
          <div>
            <h2>Notifications</h2>
            <small id="notificationCount" class="notification-summary"><?= count($notifications) ?> latest</small>
          </div>
        </div>
        <div id="notificationItems">
          <?php if ($notifications === []): ?>
            <p class="text-secondary mb-0">No notifications yet.</p>
          <?php else: ?>
            <div class="client-mini-list">
              <?php foreach ($notifications as $notification): ?>
                <div class="notification-item">
                  <div>
                    <strong><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <small><?= htmlspecialchars(date('M j, Y H:i', strtotime($notification['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="client-card">
        <div class="client-card-head">
          <h2>Recent Files</h2>
        </div>
        <?php if ($recentFiles === []): ?>
          <p class="text-secondary mb-0">No uploaded files yet.</p>
        <?php else: ?>
          <div class="client-mini-list">
            <?php foreach ($recentFiles as $file): ?>
              <div>
                <strong><?= htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8') ?></strong>
                <span><?= htmlspecialchars($file['website_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($formatBytes((int) $file['filesize']), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</section>
