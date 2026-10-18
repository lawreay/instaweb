<?php
$joinedAt = isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A';
$isVerified = ((int) ($user['email_verified'] ?? 0)) === 1;
$status = (string) ($user['account_status'] ?? 'pending');
?>
<section class="client-dashboard iw-client-page iw-notifications-page">
  <aside class="client-sidebar">
    <div class="client-profile">
      <?php if (!empty($user['avatar']) && $user['avatar'] !== 'default.png'): ?>
        <img class="client-sidebar-avatar" src="<?= htmlspecialchars(($app['base_url'] ?? '') . '/' . $user['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="Profile avatar">
      <?php else: ?>
        <div class="client-avatar"><?= htmlspecialchars(strtoupper(substr((string) $user['fullname'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <strong><?= htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8') ?></strong>
      <span><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <nav class="client-nav">
      <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="layout-dashboard"></i> Dashboard</a>
      <a class="<?= ($active ?? '') === 'profile' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="user"></i> Profile</a>
      <a class="<?= ($active ?? '') === 'websites' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="globe-2"></i> Websites</a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="hard-drive"></i> Storage</a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="badge-dollar-sign"></i> Subscription</a>
      <a class="<?= ($active ?? '') === 'notifications' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/notifications', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="bell"></i> Notifications</a>
      <?php if ($isAdmin): ?>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="shield-check"></i> Admin Console</a>
      <?php endif; ?>
    </nav>

    <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>" class="client-logout"><i data-lucide="log-out"></i> Logout</a>
  </aside>

  <div class="client-main">
    <header class="client-header profile-header">
      <div>
        <p class="admin-pro-kicker">Notification center</p>
        <h1>My Notifications</h1>
        <p>Browse all recent alerts, updates, and account messages in one place.</p>
      </div>
      <div class="client-header-actions">
        <a class="client-header-action" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="user"></i><span>Profile</span></a>
        <a class="client-header-action" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/notifications', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="bell"></i><span>Alerts</span></a>
        <a class="client-header-action logout-action" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="log-out"></i><span>Logout</span></a>
      </div>
    </header>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $flashKey => $type): ?>
      <?php if ($message = \App\Core\Session::pullFlash($flashKey)): ?>
        <div class="alert alert-<?= $type ?> shadow-sm" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    <?php endforeach; ?>

    <section class="client-card notifications-panel">
      <div class="client-card-head">
        <div>
          <h2>Recent notifications</h2>
          <span><?= count($notifications) ?> items</span>
        </div>
      </div>

      <?php if ($notifications === []): ?>
        <div class="client-empty">
          <i data-lucide="bell-off"></i>
          <strong>No notifications yet</strong>
          <p>When your account receives an update, it will appear here.</p>
        </div>
      <?php else: ?>
        <div class="notification-list">
          <?php foreach ($notifications as $notification): ?>
            <article class="notification-card <?= (int) ($notification['is_read'] ?? 0) === 0 ? 'unread' : 'read' ?>">
              <div class="notification-card-icon">
                <i data-lucide="<?= htmlspecialchars($notification['icon'] ?? 'bell', ENT_QUOTES, 'UTF-8') ?>"></i>
              </div>
              <div class="notification-card-body">
                <div class="notification-card-title">
                  <strong><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <span><?= htmlspecialchars(date('M j, Y H:i', strtotime($notification['created_at'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p><?= htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php if (!empty($notification['target_url'])): ?>
                  <a class="link-secondary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . $notification['target_url'], ENT_QUOTES, 'UTF-8') ?>">View details</a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</section>
