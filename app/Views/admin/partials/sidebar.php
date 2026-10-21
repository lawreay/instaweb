<aside class="admin-pro-sidebar">
  <?php $sidebarSettings = (new \App\Models\Setting())->all(); ?>
  <?php $adminLogoUrl = trim((string) ($sidebarSettings['admin_logo'] ?? '')); ?>
  <div class="admin-pro-brand">
    <div class="admin-pro-logo">
      <?php if ($adminLogoUrl !== ''): ?>
        <img src="<?= htmlspecialchars($adminLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Admin logo">
      <?php else: ?>
        <i data-lucide="layers" aria-hidden="true"></i>
      <?php endif; ?>
    </div>
    <div>
      <strong>Instaweb</strong>
      <span>Admin workspace</span>
    </div>
  </div>

  <nav class="admin-pro-nav">
    <div class="admin-pro-sidebar-group">
      <p class="group-title">Workspace</p>
      <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a class="<?= ($active ?? '') === 'websites' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="globe-2"></i><span>Websites</span>
      </a>
      <a class="<?= ($active ?? '') === 'media' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/media', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="image"></i><span>Media</span>
      </a>
    </div>

    <div class="admin-pro-sidebar-group">
      <p class="group-title">Management</p>
      <a class="<?= ($active ?? '') === 'users' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/users', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="users"></i><span>Users</span>
      </a>
      <a class="<?= ($active ?? '') === 'payments' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/payments', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="credit-card"></i><span>Payments</span>
      </a>
      <a class="<?= ($active ?? '') === 'reports' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/reports', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="flag"></i><span>Reports</span>
      </a>
      <a class="<?= ($active ?? '') === 'notifications' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/notifications', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="bell"></i><span>Notifications</span>
      </a>
    </div>

    <div class="admin-pro-sidebar-group">
      <p class="group-title">Settings</p>
      <a class="<?= ($active ?? '') === 'profile' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="user"></i><span>Profile</span>
      </a>
      <a class="<?= ($active ?? '') === 'settings' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="settings"></i><span>Settings</span>
      </a>
      <a class="<?= ($active ?? '') === 'legal' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/legal', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="shield-check"></i><span>Legal</span>
      </a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="monitor"></i><span>Client View</span>
      </a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="globe"></i><span>Website</span>
      </a>
    </div>
  </nav>

  <div class="admin-pro-sidebar-card">
    <strong>Admin</strong>
    <small>Logged in as <?= htmlspecialchars(\App\Core\Session::get('user_name', 'Admin'), ENT_QUOTES, 'UTF-8') ?></small>
  </div>

  <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>" class="admin-pro-logout">
    <?= \App\Core\Csrf::field() ?>
    <button type="submit"><i data-lucide="log-out"></i><span>Log out</span></button>
  </form>
</aside>
