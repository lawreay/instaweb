<?php
$sidebarBase = (string) ($app['base_url'] ?? '');
$sidebarEsc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$sidebarSettings = (new \App\Models\Setting())->all();
$adminLogoUrl = trim((string) ($sidebarSettings['admin_logo'] ?? ''));

// Optional counts. Pass $navBadges = ['reports' => 3] from any controller; the overview page falls back to $stats.
$navBadges = is_array($navBadges ?? null) ? $navBadges : [];
if (!isset($navBadges['reports']) && isset($stats['pending_reports'])) {
    $navBadges['reports'] = (int) $stats['pending_reports'];
}

// group label => [key, path, icon, label]
$navGroups = [
    '' => [
        ['dashboard', '/admin', 'layout-dashboard', 'Overview'],
    ],
    'Hosting' => [
        ['websites', '/admin/websites', 'globe-2', 'Websites'],
        ['portfolio-templates', '/admin/portfolio-templates', 'gallery-vertical-end', 'Portfolio templates'],
    ],
    'Customers' => [
        ['users', '/admin/users', 'users', 'Users'],
        ['payments', '/admin/payments', 'credit-card', 'Payments'],
    ],
    'Administration' => [
        ['reports', '/admin/reports', 'flag', 'Reports'],
        ['settings', '/admin/settings', 'settings', 'Settings'],
    ],
];
?>
<style>
/* Additions only: the existing sidebar stylesheet still styles everything else. */
#adminSidebar .iw-nav-group+.iw-nav-group{margin-top:.6rem}
#adminSidebar .iw-nav-label{margin:.6rem .85rem .3rem;font-size:.66rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;opacity:.6}
#adminSidebar .iw-nav-link{position:relative}
#adminSidebar .iw-nav-badge{margin-left:auto;min-width:1.35rem;padding:.1rem .4rem;border-radius:999px;background:#f97316;color:#fff;font-size:.68rem;font-weight:800;line-height:1.3;text-align:center}
#adminSidebar a:focus-visible,#adminSidebar button:focus-visible{outline:3px solid #f9731699;outline-offset:2px}
#adminSidebar .iw-sidebar-site{display:flex;align-items:center;gap:.65rem;padding:.6rem .85rem;border-radius:.6rem;color:inherit;font-size:.85rem;font-weight:600;text-decoration:none;opacity:.85}
#adminSidebar .iw-sidebar-site:hover{opacity:1;background:rgba(255,255,255,.08)}
#adminSidebar .iw-sidebar-site svg{width:1.1rem;height:1.1rem;flex:0 0 auto}
/* Collapsed (icon-only) state: hide group labels, show a divider, turn badges into dots. */
.admin-pro-shell:not(.is-expanded) #adminSidebar .iw-nav-label{display:none}
.admin-pro-shell:not(.is-expanded) #adminSidebar .iw-nav-group+.iw-nav-group{padding-top:.5rem;border-top:1px solid rgba(148,163,184,.25)}
.admin-pro-shell:not(.is-expanded) #adminSidebar .iw-nav-badge{position:absolute;top:.3rem;right:.3rem;min-width:0;width:.6rem;height:.6rem;padding:0;font-size:0}
.admin-pro-shell:not(.is-expanded) #adminSidebar .iw-sidebar-site span{display:none}
</style>

<aside class="admin-pro-sidebar iw-client-sidebar" id="adminSidebar" aria-label="Administrator navigation">
  <a class="admin-pro-brand iw-client-brand" href="<?= $sidebarEsc($sidebarBase . '/admin') ?>" aria-label="InstaWeb admin overview">
    <span class="admin-pro-logo iw-client-brand-mark">
      <?php if ($adminLogoUrl !== ''): ?>
        <img src="<?= $sidebarEsc($adminLogoUrl) ?>" alt="">
      <?php else: ?>
        <i data-lucide="layers" aria-hidden="true"></i>
      <?php endif; ?>
    </span>
    <span class="iw-client-brand-copy">
      <strong>Instaweb</strong>
      <small>Admin workspace</small>
    </span>
  </a>
  <button class="iw-sidebar-collapse" type="button" data-sidebar-collapse aria-label="Expand sidebar" aria-expanded="false" aria-controls="adminSidebar" title="Expand sidebar">
    <i data-lucide="panel-left-open" aria-hidden="true"></i>
  </button>

  <nav class="admin-pro-nav iw-client-nav" aria-label="Main navigation">
    <?php foreach ($navGroups as $groupLabel => $links): ?>
      <div class="admin-pro-sidebar-group iw-nav-group"<?= $groupLabel !== '' ? ' role="group" aria-label="' . $sidebarEsc($groupLabel) . '"' : '' ?>>
        <?php if ($groupLabel !== ''): ?><p class="iw-nav-label" aria-hidden="true"><?= $sidebarEsc($groupLabel) ?></p><?php endif; ?>
        <?php foreach ($links as [$key, $path, $icon, $text]):
            $isActive = ($active ?? '') === $key;
            $badge = (int) ($navBadges[$key] ?? 0); ?>
          <a class="iw-nav-link <?= $isActive ? 'active' : '' ?>" href="<?= $sidebarEsc($sidebarBase . $path) ?>" title="<?= $sidebarEsc($text) ?>" aria-label="<?= $sidebarEsc($text . ($badge > 0 ? ', ' . $badge . ' waiting' : '')) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
            <i data-lucide="<?= $sidebarEsc($icon) ?>" aria-hidden="true"></i><span><?= $sidebarEsc($text) ?></span>
            <?php if ($badge > 0): ?><span class="iw-nav-badge" aria-hidden="true"><?= $badge > 99 ? '99+' : $badge ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </nav>

  <div class="iw-sidebar-bottom">
    <a class="iw-sidebar-site" href="<?= $sidebarEsc($sidebarBase . '/') ?>" target="_blank" rel="noopener" title="View public site"><i data-lucide="external-link" aria-hidden="true"></i><span>View public site</span></a>
    <form method="POST" action="<?= $sidebarEsc($sidebarBase . '/logout') ?>" class="admin-pro-logout">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit" class="iw-client-logout" title="Sign out"><i data-lucide="log-out" aria-hidden="true"></i><span>Sign out</span></button>
    </form>
  </div>
</aside>

<script>
/* Admin navigation: persisted desktop collapse, mobile drawer with focus handling. */
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

  var readExpanded = function () {
    try {
      var saved = localStorage.getItem(storageKey);
      if (saved === '1' || saved === '0') {
        var migrated = saved === '1';
        localStorage.setItem(storageKey, migrated ? 'true' : 'false');
        return migrated;
      }
      return saved === 'true';
    } catch (e) {
      return false;
    }
  };

  var isDrawerOpen = function () { return shell.classList.contains('sidebar-open'); };

  /* On phones the closed drawer is off-screen, so keep it out of the tab order. */
  var syncInert = function () {
    var hidden = mobileQuery.matches && !isDrawerOpen();
    if (hidden) sidebar.setAttribute('inert', '');
    else sidebar.removeAttribute('inert');
  };

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

  var closeMobile = function (returnFocus) {
    var wasOpen = isDrawerOpen();
    shell.classList.remove('sidebar-open', 'mobile-nav-open');
    if (open) open.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    syncInert();
    if (wasOpen && returnFocus && open) open.focus();
  };

  var openMobile = function () {
    shell.classList.add('sidebar-open');
    if (open) open.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    syncInert();
    var first = sidebar.querySelector('a[href]');
    if (first) first.focus();
  };

  setExpanded(readExpanded(), false);
  syncInert();

  if (collapse) collapse.addEventListener('click', function () {
    if (!mobileQuery.matches) setExpanded(!shell.classList.contains('is-expanded'), true);
  });
  if (open) open.addEventListener('click', function () {
    if (mobileQuery.matches) { isDrawerOpen() ? closeMobile(true) : openMobile(); }
  });
  if (backdrop) backdrop.addEventListener('click', function () { closeMobile(true); });
  sidebar.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () { if (mobileQuery.matches) closeMobile(false); });
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && isDrawerOpen()) closeMobile(true);
  });

  var handleBreakpoint = function () {
    closeMobile(false);
    setExpanded(mobileQuery.matches ? true : readExpanded(), false);
    syncInert();
  };
  if (mobileQuery.addEventListener) mobileQuery.addEventListener('change', handleBreakpoint);
  else if (mobileQuery.addListener) mobileQuery.addListener(handleBreakpoint);
});
</script>