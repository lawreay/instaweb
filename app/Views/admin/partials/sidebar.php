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


<script>
/* Admin navigation: use the existing controls, persist desktop collapse state, and
   keep the mobile drawer above the backdrop instead of underneath it. */
document.addEventListener('DOMContentLoaded', function () {
  var shell = document.querySelector('[data-admin-shell], .admin-pro-shell');
  if (!shell || shell.dataset.adminSidebarReady === 'true') return;
  shell.dataset.adminSidebarReady = 'true';

  var sidebar = shell.querySelector('#adminSidebar');
  var collapse = shell.querySelector('[data-sidebar-collapse]');
  var open = shell.querySelector('[data-sidebar-open], [data-admin-sidebar-toggle]');
  var backdrop = shell.querySelector('[data-sidebar-close], [data-admin-sidebar-overlay]');
  if (!sidebar) return;

  var storageKey = 'instaweb-admin-sidebar-expanded';
  var mobileQuery = window.matchMedia('(max-width: 900px)');
  var setExpanded = function (expanded, persist) {
    if (mobileQuery.matches) expanded = true;
    shell.classList.toggle('is-expanded', !!expanded);
    if (collapse) {
      collapse.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      collapse.setAttribute('aria-label', expanded ? 'Collapse sidebar' : 'Expand sidebar');
      collapse.title = expanded ? 'Collapse sidebar' : 'Expand sidebar';
      var icon = collapse.querySelector('[data-lucide]');
      if (icon) icon.setAttribute('data-lucide', expanded ? 'panel-left-close' : 'panel-left-open');
    }
    if (persist && !mobileQuery.matches) {
      try { localStorage.setItem(storageKey, expanded ? 'true' : 'false'); } catch (e) {}
    }
    if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
  };
  var closeMobile = function () {
    shell.classList.remove('sidebar-open', 'mobile-nav-open');
    if (open) open.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  };
  var openMobile = function () {
    shell.classList.add('sidebar-open');
    if (open) open.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  };

  var expanded = false;
  try { expanded = localStorage.getItem(storageKey) === 'true'; } catch (e) {}
  setExpanded(expanded, false);

  if (collapse) collapse.addEventListener('click', function () {
    if (!mobileQuery.matches) setExpanded(!shell.classList.contains('is-expanded'), true);
  });
  if (open) open.addEventListener('click', function () {
    if (mobileQuery.matches) {
      shell.classList.contains('sidebar-open') ? closeMobile() : openMobile();
    }
  });
  if (backdrop) backdrop.addEventListener('click', closeMobile);
  sidebar.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () { if (mobileQuery.matches) closeMobile(); });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeMobile();
  });
  var handleBreakpoint = function () {
    closeMobile();
    if (mobileQuery.matches) setExpanded(true, false);
    else {
      var saved = false;
      try { saved = localStorage.getItem(storageKey) === 'true'; } catch (e) {}
      setExpanded(saved, false);
    }
  };
  if (mobileQuery.addEventListener) mobileQuery.addEventListener('change', handleBreakpoint);
  else if (mobileQuery.addListener) mobileQuery.addListener(handleBreakpoint);
});
</script>
