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
        ['feedback', '/admin/feedback', 'message-square-warning', 'Feedback inbox'],
        ['settings', '/admin/settings', 'settings', 'Settings'],
    ],
];
?>
<style>
/* Self-contained sidebar styling, scoped by id so it works on every admin page. */
#adminSidebar { --sb-ink:#14201A; --sb-muted:#5B675F; --sb-line:#E4EAE5; --sb-hover:#F2F6F3; --sb-green:#1F7A4D; --sb-green-dark:#14532D; --sb-tint:#E3F2E8; --sb-deep:#12302A;
  position:sticky; top:0; z-index:30; display:flex; flex-direction:column; gap:12px; width:100%; height:100vh; height:100dvh; min-height:0; padding:16px 12px; overflow-x:hidden; overflow-y:auto; background:#fff; color:var(--sb-ink); border-right:1px solid var(--sb-line); box-shadow:none; scrollbar-width:thin; }
#adminSidebar *, #adminSidebar *::before, #adminSidebar *::after { box-sizing:border-box; }

/* Brand */
#adminSidebar .admin-pro-brand { display:flex; align-items:center; justify-content:center; gap:12px; min-height:48px; padding:0 4px 14px; border-bottom:1px solid var(--sb-line); text-decoration:none; }
#adminSidebar .admin-pro-logo { position:relative; display:grid; place-items:center; flex:0 0 42px; width:42px; height:42px; overflow:hidden; border-radius:13px; background:var(--sb-deep); color:#fff; }
#adminSidebar .admin-pro-logo img { display:block; max-width:100%; max-height:100%; object-fit:contain; background:#fff; }
#adminSidebar .admin-pro-logo svg { width:21px; height:21px; }
#adminSidebar .iw-client-brand-copy { display:none; min-width:0; }
#adminSidebar .iw-client-brand-copy strong { display:block; font-size:17px; font-weight:800; letter-spacing:-.02em; color:var(--sb-green-dark); }
#adminSidebar .iw-client-brand-copy small { display:block; margin-top:1px; font-size:12px; color:var(--sb-muted); }
.admin-pro-shell.is-expanded #adminSidebar .admin-pro-brand { justify-content:flex-start; padding-inline:6px; }
.admin-pro-shell.is-expanded #adminSidebar .iw-client-brand-copy { display:block; }

/* Navigation */
#adminSidebar .admin-pro-nav { display:flex; flex-direction:column; gap:4px; margin-top:4px; }
#adminSidebar .iw-nav-group { display:flex; flex-direction:column; gap:4px; min-width:0; }
#adminSidebar .iw-nav-group + .iw-nav-group { margin-top:8px; }
#adminSidebar .iw-nav-label { display:none; margin:8px 12px 2px; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--sb-muted); }
.admin-pro-shell.is-expanded #adminSidebar .iw-nav-label { display:block; }
.admin-pro-shell:not(.is-expanded) #adminSidebar .iw-nav-group + .iw-nav-group { padding-top:8px; border-top:1px solid var(--sb-line); }
#adminSidebar .iw-nav-link { position:relative; display:flex; align-items:center; justify-content:center; gap:12px; min-height:44px; padding:0 12px; border:1px solid transparent; border-radius:12px; color:#3F4B44; font-size:14px; font-weight:600; text-decoration:none; white-space:nowrap; overflow:hidden; transition:background .15s, color .15s; }
#adminSidebar .iw-nav-link > svg, #adminSidebar .iw-nav-link > i { width:20px; height:20px; flex:0 0 20px; }
#adminSidebar .iw-nav-link > span:not(.iw-nav-badge) { display:none; }
.admin-pro-shell.is-expanded #adminSidebar .iw-nav-link { justify-content:flex-start; }
.admin-pro-shell.is-expanded #adminSidebar .iw-nav-link > span:not(.iw-nav-badge) { display:inline; }
#adminSidebar .iw-nav-link:hover { background:var(--sb-hover); color:var(--sb-ink); }
#adminSidebar .iw-nav-link.active { background:var(--sb-tint); border-color:#CFE5D8; color:var(--sb-green-dark); font-weight:700; }
#adminSidebar .iw-nav-link.active::before { content:""; position:absolute; left:0; top:10px; bottom:10px; width:3px; border-radius:0 3px 3px 0; background:var(--sb-green); }
#adminSidebar .iw-nav-badge { display:inline-block; margin-left:auto; min-width:22px; padding:2px 7px; border-radius:999px; background:#D9480F; color:#fff; font-size:11px; font-weight:800; line-height:1.4; text-align:center; }
.admin-pro-shell:not(.is-expanded) #adminSidebar .iw-nav-badge { position:absolute; top:8px; right:8px; min-width:0; width:9px; height:9px; padding:0; font-size:0; border:2px solid #fff; box-sizing:content-box; }

/* Bottom area */
#adminSidebar .iw-sidebar-bottom { margin-top:auto; display:flex; flex-direction:column; gap:4px; padding-top:12px; border-top:1px solid var(--sb-line); }
#adminSidebar .iw-sidebar-collapse, #adminSidebar .iw-sidebar-site, #adminSidebar .iw-client-logout { position:static; display:flex; align-items:center; justify-content:center; gap:12px; width:100%; min-height:44px; padding:0 12px; border:0; border-radius:12px; background:transparent; color:#3F4B44; font:inherit; font-size:14px; font-weight:600; text-align:left; text-decoration:none; cursor:pointer; opacity:1; }
#adminSidebar .iw-sidebar-collapse svg, #adminSidebar .iw-sidebar-collapse i, #adminSidebar .iw-sidebar-site svg, #adminSidebar .iw-client-logout svg { width:20px; height:20px; flex:0 0 20px; }
#adminSidebar .iw-sidebar-collapse:hover, #adminSidebar .iw-sidebar-site:hover { background:var(--sb-hover); color:var(--sb-ink); }
#adminSidebar .iw-client-logout { color:#A32A21; }
#adminSidebar .iw-client-logout:hover { background:#FFF1F0; color:#8A1C14; }
#adminSidebar .iw-sidebar-collapse > span, #adminSidebar .iw-sidebar-site > span, #adminSidebar .iw-client-logout > span { display:none; }
.admin-pro-shell.is-expanded #adminSidebar .iw-sidebar-collapse, .admin-pro-shell.is-expanded #adminSidebar .iw-sidebar-site, .admin-pro-shell.is-expanded #adminSidebar .iw-client-logout { justify-content:flex-start; }
.admin-pro-shell.is-expanded #adminSidebar .iw-sidebar-collapse > span, .admin-pro-shell.is-expanded #adminSidebar .iw-sidebar-site > span, .admin-pro-shell.is-expanded #adminSidebar .iw-client-logout > span { display:inline; }
#adminSidebar .admin-pro-logout { margin:0; padding:0; border:0; }
#adminSidebar a:focus-visible, #adminSidebar button:focus-visible { outline:3px solid rgba(31,122,77,.4); outline-offset:2px; }

/* Mobile menu button and backdrop (created by the script when the page has none) */
.iw-mobile-menu { display:none; align-items:center; gap:8px; min-height:44px; margin:0 0 16px; padding:0 14px; border:1px solid #D8E0DA; border-radius:12px; background:#fff; color:#14201A; font-size:14px; font-weight:600; cursor:pointer; }
.iw-mobile-menu svg { width:20px; height:20px; }
.iw-drawer-backdrop { display:none; position:fixed; inset:0; z-index:1040; width:100%; height:100%; border:0; background:rgba(17,24,39,.42); cursor:pointer; }

@media (max-width: 900px) {
  #adminSidebar { position:fixed; top:0; bottom:0; left:0; z-index:1050; width:min(86vw,300px); height:100vh; height:100dvh; box-shadow:16px 0 42px rgba(23,32,51,.16); transform:translateX(-105%); transition:transform .2s ease; }
  .admin-pro-shell.sidebar-open #adminSidebar, .admin-pro-shell.mobile-nav-open #adminSidebar { transform:translateX(0); }
  #adminSidebar .admin-pro-brand { justify-content:flex-start; padding-inline:6px; }
  #adminSidebar .iw-client-brand-copy { display:block; }
  #adminSidebar .iw-nav-label { display:block; }
  #adminSidebar .iw-nav-link, #adminSidebar .iw-sidebar-site, #adminSidebar .iw-client-logout { justify-content:flex-start; }
  #adminSidebar .iw-nav-link > span:not(.iw-nav-badge), #adminSidebar .iw-sidebar-site > span, #adminSidebar .iw-client-logout > span { display:inline; }
  #adminSidebar .iw-sidebar-collapse { display:none; }
  .admin-pro-shell:not(.is-expanded) #adminSidebar .iw-nav-badge { position:static; width:auto; height:auto; min-width:22px; padding:2px 7px; font-size:11px; border:0; }
  .iw-mobile-menu { display:inline-flex; }
  .admin-pro-shell.sidebar-open .iw-drawer-backdrop, .admin-pro-shell.mobile-nav-open .iw-drawer-backdrop { display:block; }
}
@media (prefers-reduced-motion: reduce) { #adminSidebar { transition:none; } #adminSidebar .iw-nav-link { transition:none; } }
</style>

<aside class="admin-pro-sidebar iw-client-sidebar" id="adminSidebar" aria-label="Administrator navigation">
  <a class="admin-pro-brand iw-client-brand" href="<?= $sidebarEsc($sidebarBase . '/admin') ?>" aria-label="InstaWeb admin overview">
    <span class="admin-pro-logo iw-client-brand-mark">
      <?php if ($adminLogoUrl !== ''): ?>
        <img src="<?= $sidebarEsc($adminLogoUrl) ?>" alt="" onerror="this.hidden=true;var f=this.nextElementSibling;if(f){f.hidden=false;}">
        <i data-lucide="layers" aria-hidden="true" hidden></i>
      <?php else: ?>
        <i data-lucide="layers" aria-hidden="true"></i>
      <?php endif; ?>
    </span>
    <span class="iw-client-brand-copy">
      <strong>Instaweb</strong>
      <small>Admin workspace</small>
    </span>
  </a>

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
    <button class="iw-sidebar-collapse" type="button" data-sidebar-collapse aria-label="Expand sidebar" aria-expanded="false" aria-controls="adminSidebar" title="Expand sidebar">
      <i data-lucide="panel-left-open" aria-hidden="true"></i><span data-collapse-label>Expand</span>
    </button>
    <a class="iw-sidebar-site" href="<?= $sidebarEsc($sidebarBase . '/') ?>" target="_blank" rel="noopener" title="View public site" aria-label="View public site (opens in a new tab)"><i data-lucide="external-link" aria-hidden="true"></i><span>View public site</span></a>
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

  /* Pages without their own top bar (websites, users, settings...) get a mobile Menu button and backdrop. */
  if (!open) {
    var main = shell.querySelector('.admin-pro-main');
    if (main) {
      open = document.createElement('button');
      open.type = 'button';
      open.className = 'iw-mobile-menu';
      open.setAttribute('data-sidebar-open', '');
      open.setAttribute('aria-label', 'Open navigation');
      open.setAttribute('aria-expanded', 'false');
      open.setAttribute('aria-controls', 'adminSidebar');
      open.innerHTML = '<i data-lucide="menu" aria-hidden="true"></i><span>Menu</span>';
      main.insertBefore(open, main.firstChild);
    }
  }
  if (!backdrop) {
    backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'iw-drawer-backdrop';
    backdrop.setAttribute('data-sidebar-close', '');
    backdrop.setAttribute('aria-label', 'Close navigation');
    backdrop.tabIndex = -1;
    shell.appendChild(backdrop);
  }

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
      var label = collapse.querySelector('[data-collapse-label]');
      if (label) label.textContent = expanded ? 'Collapse' : 'Expand';
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