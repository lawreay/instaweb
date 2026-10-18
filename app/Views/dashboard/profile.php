<?php
$joinedAt = isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A';
$isVerified = ((int) ($user['email_verified'] ?? 0)) === 1;
$status = (string) ($user['account_status'] ?? 'pending');
?>
<section class="client-dashboard iw-client-page iw-profile-page">
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
        <p class="admin-pro-kicker">Profile workspace</p>
        <h1>Account Profile</h1>
        <p>Manage your public identity, contact details, email, and account security.</p>
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

    <div class="profile-layout">
      <section class="client-card profile-panel">
        <div class="client-card-head">
          <div>
            <h2>Personal details</h2>
            <span>These details help support and billing identify your account.</span>
          </div>
          <span class="profile-meta"><i data-lucide="calendar-days"></i> Joined <?= htmlspecialchars($joinedAt, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile/update', ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
          <?= \App\Core\Csrf::field() ?>
          <div class="row g-4">
            <div class="col-lg-4">
              <?php include APP_PATH . '/Views/partials/profile/avatar.php'; ?>
            </div>
            <div class="col-lg-8">
              <?php include APP_PATH . '/Views/partials/profile/details.php'; ?>
            </div>
          </div>
        </form>
      </section>

      <aside class="profile-side">
        <section class="client-card">
          <div class="client-card-head"><h2>Security</h2></div>
          <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile/change-password', ENT_QUOTES, 'UTF-8') ?>">
            <?= \App\Core\Csrf::field() ?>
            <?php include APP_PATH . '/Views/partials/profile/security.php'; ?>
          </form>
        </section>

        <section class="client-card">
          <div class="client-card-head"><h2>Email & account</h2></div>
          <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile/change-email', ENT_QUOTES, 'UTF-8') ?>">
            <?= \App\Core\Csrf::field() ?>
            <?php include APP_PATH . '/Views/partials/profile/email.php'; ?>
          </form>
          <?php include APP_PATH . '/Views/partials/profile/deactivate.php'; ?>
        </section>
      </aside>
    </div>
  </div>
</section>
