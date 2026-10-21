<aside class="admin-pro-sidebar iw-client-sidebar" id="adminSidebar" aria-label="Administrator navigation">
  <?php $sidebarSettings = (new \App\Models\Setting())->all(); ?>
  <?php $adminLogoUrl = trim((string) ($sidebarSettings['admin_logo'] ?? '')); ?>
  <a class="admin-pro-brand iw-client-brand" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin', ENT_QUOTES, 'UTF-8') ?>" aria-label="InstaWeb admin overview">
    <span class="admin-pro-logo iw-client-brand-mark">
      <?php if ($adminLogoUrl !== ''): ?>
        <img src="<?= htmlspecialchars($adminLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Admin logo">
      <?php else: ?>
        <i data-lucide="layers"></i>
      <?php endif; ?>
    </span>
    <span class="iw-client-brand-copy">
      <strong>Instaweb</strong>
      <small>Admin workspace</small>
    </span>
  </a>
  <button class="iw-sidebar-collapse" type="button" data-sidebar-collapse aria-label="Expand sidebar" aria-expanded="false" title="Expand sidebar">
    <i data-lucide="panel-left-open"></i>
  </button>

  <nav class="admin-pro-nav iw-client-nav" aria-label="Main navigation">
    <div class="admin-pro-sidebar-group">
      <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="layout-dashboard"></i><span>Overview</span>
      </a>
      <a class="<?= ($active ?? '') === 'websites' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="globe-2"></i><span>Websites</span>
      </a>
      <a class="<?= ($active ?? '') === 'users' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/users', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="users"></i><span>Users</span>
      </a>
      <a class="<?= ($active ?? '') === 'payments' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/payments', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="credit-card"></i><span>Payments</span>
      </a>
      <a class="<?= ($active ?? '') === 'reports' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/reports', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="flag"></i><span>Reports</span>
      </a>
      <a class="<?= ($active ?? '') === 'settings' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>">
        <i data-lucide="settings"></i><span>Settings</span>
      </a>
    </div>
  </nav>

  <div class="iw-sidebar-bottom">
    <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>" class="admin-pro-logout">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit" class="iw-client-logout"><i data-lucide="log-out"></i><span>Sign out</span></button>
    </form>
  </div>
</aside>
