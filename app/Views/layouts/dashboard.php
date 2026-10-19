<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php $themeSettings = (new \App\Models\Setting())->all(); ?>
  <?php $themePrimary = $themeSettings['primary_color'] ?? '#0F766E'; ?>
  <?php $themeSecondary = $themeSettings['secondary_color'] ?? '#0F766E'; ?>
  <?php $themeText = $themeSettings['text_color'] ?? '#111827'; ?>
  <?php $themeMutedText = $themeSettings['muted_text_color'] ?? '#5B6472'; ?>
  <?php $themeBackground = $themeSettings['page_background_color'] ?? '#F8FAFC'; ?>
  <?php $themeSurfaceBg = $themeSettings['surface_background_color'] ?? '#ffffff'; ?>
  <?php $themeSurfaceBorder = $themeSettings['surface_border_color'] ?? 'rgba(15, 23, 42, 0.08)'; ?>
  <?php $themeSidebarBg = $themeSettings['sidebar_background_color'] ?? '#FFFFFF'; ?>
  <?php $themeSidebarText = $themeSettings['sidebar_text_color'] ?? '#475569'; ?>
  <?php $themeBrandBg = $themeSettings['primary_color'] ?? '#0F766E'; ?>
  <?php $themeBrandText = '#ffffff'; ?>
  <style id="platform-theme-vars">
    :root {
      --app-primary: <?= htmlspecialchars($themePrimary, ENT_QUOTES, 'UTF-8') ?>;
      --app-secondary: <?= htmlspecialchars($themeSecondary, ENT_QUOTES, 'UTF-8') ?>;
      --app-text: <?= htmlspecialchars($themeText, ENT_QUOTES, 'UTF-8') ?>;
      --app-muted: <?= htmlspecialchars($themeMutedText, ENT_QUOTES, 'UTF-8') ?>;
      --app-background: <?= htmlspecialchars($themeBackground, ENT_QUOTES, 'UTF-8') ?>;
      --surface-bg: <?= htmlspecialchars($themeSurfaceBg, ENT_QUOTES, 'UTF-8') ?>;
      --surface-border: <?= htmlspecialchars($themeSurfaceBorder, ENT_QUOTES, 'UTF-8') ?>;
      --sidebar-bg: <?= htmlspecialchars($themeSidebarBg, ENT_QUOTES, 'UTF-8') ?>;
      --sidebar-text: <?= htmlspecialchars($themeSidebarText, ENT_QUOTES, 'UTF-8') ?>;
      --brand-bg: <?= htmlspecialchars($themeBrandBg, ENT_QUOTES, 'UTF-8') ?>;
      --brand-text: <?= htmlspecialchars($themeBrandText, ENT_QUOTES, 'UTF-8') ?>;
    }
  </style>
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/styles.css', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/client-dashboard.css?v=20261019-shared-sidebar', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/client-dashboard-home.css?v=20261018-client-pages', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/client-dashboard-pages.css?v=20261018-pages-v1', ENT_QUOTES, 'UTF-8') ?>">
  <?php $appIcon = trim((string) ($themeSettings['app_icon_url'] ?? '')); ?>
  <?php if ($appIcon !== ''): ?>
    <link rel="icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
</head>
<body class="dashboard-body">
  <?php
  $clientHeaderPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $clientBasePath = rtrim((string) (parse_url($app['base_url'] ?? '', PHP_URL_PATH) ?: ''), '/');
  if ($clientBasePath !== '' && str_starts_with($clientHeaderPath, $clientBasePath)) {
      $clientHeaderPath = substr($clientHeaderPath, strlen($clientBasePath)) ?: '/';
  }
  $clientHeaderPath = '/' . ltrim($clientHeaderPath, '/');
  $clientHeaderPath = rtrim($clientHeaderPath, '/') ?: '/';
  $clientBaseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
  ?>
  <div class="iw-client-shell" data-client-shell>
    <aside class="iw-client-sidebar" id="clientSidebar" aria-label="Client workspace navigation">
      <a class="iw-client-brand" href="<?= htmlspecialchars($clientBaseUrl . '/dashboard', ENT_QUOTES, 'UTF-8') ?>" aria-label="InstaWeb overview">
        <span class="iw-client-brand-mark"><i data-lucide="layers"></i></span>
        <span class="iw-client-brand-copy">InstaWeb<small>Client workspace</small></span>
      </a>
      <button class="iw-sidebar-collapse" type="button" data-sidebar-collapse aria-label="Expand sidebar" aria-expanded="false" title="Expand sidebar">
        <i data-lucide="panel-left-open"></i>
      </button>
      <nav class="iw-client-nav" id="clientSidebarNavigation" aria-label="Main navigation">
        <a class="<?= $clientHeaderPath === '/dashboard' ? 'active' : '' ?>" href="<?= htmlspecialchars($clientBaseUrl . '/dashboard', ENT_QUOTES, 'UTF-8') ?>" <?= $clientHeaderPath === '/dashboard' ? 'aria-current="page"' : '' ?>>
          <i data-lucide="layout-dashboard"></i><span>Overview</span>
        </a>
        <a class="<?= str_starts_with($clientHeaderPath, '/dashboard/websites') ? 'active' : '' ?>" href="<?= htmlspecialchars($clientBaseUrl . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>" <?= str_starts_with($clientHeaderPath, '/dashboard/websites') ? 'aria-current="page"' : '' ?>>
          <i data-lucide="globe-2"></i><span>My Websites</span>
        </a>
        <a class="<?= $clientHeaderPath === '/dashboard/notifications' ? 'active' : '' ?>" href="<?= htmlspecialchars($clientBaseUrl . '/dashboard/notifications', ENT_QUOTES, 'UTF-8') ?>" <?= $clientHeaderPath === '/dashboard/notifications' ? 'aria-current="page"' : '' ?>>
          <i data-lucide="bell"></i><span>Notifications</span>
        </a>
        <a class="<?= str_starts_with($clientHeaderPath, '/profile') ? 'active' : '' ?>" href="<?= htmlspecialchars($clientBaseUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>" <?= str_starts_with($clientHeaderPath, '/profile') ? 'aria-current="page"' : '' ?>>
          <i data-lucide="user-round"></i><span>Account Settings</span>
        </a>
      </nav>
      <div class="iw-sidebar-bottom">
        <form method="POST" action="<?= htmlspecialchars($clientBaseUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>">
          <?= \App\Core\Csrf::field() ?>
          <button type="submit" class="iw-client-logout"><i data-lucide="log-out"></i><span>Sign out</span></button>
        </form>
      </div>
    </aside>
    <button class="iw-sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>
    <div class="iw-client-main">
      <header class="iw-client-topbar">
        <button class="iw-mobile-menu" type="button" data-sidebar-open aria-controls="clientSidebar" aria-expanded="false" aria-label="Open navigation">
          <i data-lucide="menu"></i>
        </button>
        <div class="iw-topbar-context">
          <span class="iw-topbar-eyebrow">INSTAWEB WORKSPACE</span>
          <span class="iw-topbar-page"><?= htmlspecialchars($clientHeaderPath === '/dashboard' ? 'Overview' : (str_starts_with($clientHeaderPath, '/dashboard/websites') ? 'My Websites' : ($clientHeaderPath === '/dashboard/notifications' ? 'Notifications' : (str_starts_with($clientHeaderPath, '/profile') ? 'Account Settings' : 'Workspace'))), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <a class="iw-topbar-account" href="<?= htmlspecialchars($clientBaseUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>" aria-label="Open account settings">
          <i data-lucide="user-round"></i><span>Account</span>
        </a>
      </header>
  <?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $type): ?>
    <?php $message = \App\Core\Session::pullFlash($key); ?>
    <?php if ($message): ?>
      <div class="admin-toast alert alert-<?= $type ?> shadow-sm" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
  <?php
  // If an authenticated user has not accepted the latest published policies,
  // show a non-dismissible modal forcing acceptance or logout.
  $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
  $showPolicyModal = false;
  $pendingPolicies = [];
  try {
      if (function_exists('\App\Services\AuthService::check') ? \App\Services\AuthService::check() : (\App\Services\AuthService::class)) {
          // Use AuthService through namespaced class to avoid import issues
      }
  } catch (\Throwable) {
      // ignore
  }

  if (\App\Services\AuthService::check()) {
      $userId = (int) \App\Services\AuthService::id();
      $policyModel = new \App\Models\LegalPolicy();
      $versionModel = new \App\Models\LegalPolicyVersion();
      $acceptModel = new \App\Models\UserPolicyAcceptance();
      $policies = $policyModel->all();

      foreach ($policies as $policy) {
          $published = $versionModel->publishedVersion((int) $policy['id']);
          if (!$published) {
              continue;
          }

          $last = $acceptModel->lastAcceptedVersion($userId, (int) $policy['id']);
          if (!$last || (int) ($last['version_id'] ?? 0) !== (int) $published['id']) {
              $showPolicyModal = true;
              $pendingPolicies[] = [
                  'policy' => $policy,
                  'published' => $published,
              ];
          }
      }
  }
  ?>
  <?php if (!empty($pendingPolicies) && (!isset($requestPath) || ($requestPath ?? '') !== '/legal/accept')): ?>
    <div class="modal fade" id="policyModal" tabindex="-1" aria-labelledby="policyModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="policyModalLabel">Updated platform policies</h5>
          </div>
          <div class="modal-body">
            <p>The platform administrators have updated the following policies. You must accept them to continue using the dashboard.</p>
            <ul>
              <?php foreach ($pendingPolicies as $pp): ?>
                <li><strong><?= htmlspecialchars($pp['policy']['title'] ?? $pp['policy']['policy_type'], ENT_QUOTES, 'UTF-8') ?></strong> — Version <?= htmlspecialchars($pp['published']['version_label'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="modal-footer">
            <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/legal/accept', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" id="policyAcceptForm">
              <?= \App\Core\Csrf::field() ?>
              <?php foreach ($pendingPolicies as $pp): ?>
                <input type="hidden" name="accepted_policy_types[]" value="<?= htmlspecialchars($pp['policy']['policy_type'], ENT_QUOTES, 'UTF-8') ?>">
              <?php endforeach; ?>
              <button type="submit" class="btn btn-primary">Accept</button>
            </form>
            <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>" class="d-inline" id="policyDeclineForm">
              <?= \App\Core\Csrf::field() ?>
              <button type="submit" class="btn btn-outline-danger">Decline and logout</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script>
      window.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('policyModal');
        if (modalEl) {
          var modal = new bootstrap.Modal(modalEl);
          modal.show();
        }
      });
    </script>
  <?php endif; ?>
  <?= $content ?? '' ?>
  <footer class="client-app-footer">
    <span>© <?= date('Y') ?> InstaWeb. Your websites, in one place.</span>
    <nav aria-label="Footer navigation">
      <a href="<?= htmlspecialchars($clientBaseUrl . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>">Manage sites</a>
      <a href="<?= htmlspecialchars($clientBaseUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>">Account settings</a>
      <a href="<?= htmlspecialchars($clientBaseUrl . '/terms', ENT_QUOTES, 'UTF-8') ?>">Terms</a>
      <a href="<?= htmlspecialchars($clientBaseUrl . '/privacy', ENT_QUOTES, 'UTF-8') ?>">Privacy</a>
    </nav>
  </footer>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>
  <script src="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/js/main.js?v=20260629-tabs', ENT_QUOTES, 'UTF-8') ?>"></script>
  <script>
    // Share button handler: copy the public URL when the browser permits it.
    document.addEventListener('click', function (e) {
      var el = e.target.closest && e.target.closest('.btn-share-site');
      if (!el) return;

      var url = el.getAttribute('data-share-url') || '';
      if (!url) return;

      var showCopyFallback = function () {
        window.prompt('Copy this site URL:', url);
      };

      var showCopiedToast = function () {
        var toast = document.createElement('div');
        toast.className = 'admin-toast alert alert-success shadow-sm';
        toast.setAttribute('role', 'status');
        toast.style.position = 'fixed';
        toast.style.right = '20px';
        toast.style.bottom = '20px';
        toast.style.zIndex = 2000;
        toast.textContent = 'Link copied to clipboard';
        document.body.appendChild(toast);
        window.setTimeout(function () {
          if (toast.isConnected) toast.remove();
        }, 2500);
      };

      // Clipboard access is usually unavailable on insecure HTTP origins.
      if (!window.isSecureContext || !navigator.clipboard || !navigator.clipboard.writeText) {
        showCopyFallback();
        return;
      }

      navigator.clipboard.writeText(url).then(showCopiedToast).catch(showCopyFallback);
    });
  </script>
  <script>
    (function () {
      var shell = document.querySelector('[data-client-shell]');
      var sidebar = document.getElementById('clientSidebar');
      var openButton = document.querySelector('[data-sidebar-open]');
      var closeButton = document.querySelector('[data-sidebar-close]');
      var collapseButton = document.querySelector('[data-sidebar-collapse]');
      if (!shell || !sidebar || !openButton || !closeButton || !collapseButton) return;

      var storageKey = 'instaweb-client-sidebar-expanded';
      var desktopQuery = window.matchMedia('(min-width: 900px)');

      function setExpanded(expanded) {
        shell.classList.toggle('is-expanded', expanded);
        collapseButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        collapseButton.setAttribute('aria-label', expanded ? 'Collapse sidebar' : 'Expand sidebar');
        collapseButton.setAttribute('title', expanded ? 'Collapse sidebar' : 'Expand sidebar');
        collapseButton.innerHTML = '<i data-lucide="' + (expanded ? 'panel-left-close' : 'panel-left-open') + '"></i>';
        try { window.localStorage.setItem(storageKey, expanded ? 'true' : 'false'); } catch (error) { /* Storage may be disabled. */ }
        if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
      }

      function setMobileOpen(open) {
        shell.classList.toggle('mobile-nav-open', open);
        openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        openButton.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        openButton.innerHTML = '<i data-lucide="' + (open ? 'x' : 'menu') + '"></i>';
        if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
        if (open) {
          var firstLink = sidebar.querySelector('.iw-client-nav a');
          if (firstLink) firstLink.focus();
        } else {
          openButton.focus({ preventScroll: true });
        }
      }

      sidebar.querySelectorAll('.iw-client-nav a').forEach(function (link) {
        var label = link.querySelector('span');
        if (label && label.textContent.trim()) {
          var text = label.textContent.trim();
          link.setAttribute('aria-label', text);
          link.setAttribute('title', text);
        }
      });

      var expanded = false;
      try { expanded = window.localStorage.getItem(storageKey) === 'true'; } catch (error) { /* Storage may be disabled. */ }
      setExpanded(expanded);

      collapseButton.addEventListener('click', function () {
        setExpanded(!shell.classList.contains('is-expanded'));
      });
      openButton.addEventListener('click', function () {
        setMobileOpen(!shell.classList.contains('mobile-nav-open'));
      });
      closeButton.addEventListener('click', function () { setMobileOpen(false); });
      sidebar.querySelectorAll('.iw-client-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
          if (!desktopQuery.matches) setMobileOpen(false);
        });
      });
      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && shell.classList.contains('mobile-nav-open')) setMobileOpen(false);
      });
      window.addEventListener('resize', function () {
        if (desktopQuery.matches) shell.classList.remove('mobile-nav-open');
      });
    }());
  </script>
  <script>
    (function () {
      var workspaces = document.querySelectorAll('.client-dashboard');
      if (!workspaces.length) return;

      workspaces.forEach(function (workspace) {
        var sidebar = workspace.querySelector('.client-sidebar');
        if (!sidebar) return;

        var navLinks = sidebar.querySelectorAll('.client-nav a');
        navLinks.forEach(function (link) {
          var label = (link.textContent || '').replace(/\\s+/g, ' ').trim();
          if (label) {
            link.setAttribute('data-sidebar-label', label);
            link.setAttribute('title', label);
            link.setAttribute('aria-label', label);
          }
        });

        var logout = sidebar.querySelector('.client-logout');
        if (logout) {
          logout.setAttribute('data-sidebar-label', 'Logout');
          logout.setAttribute('title', 'Logout');
          logout.setAttribute('aria-label', 'Logout');
        }

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'client-sidebar-toggle';
        toggle.setAttribute('aria-controls', 'client-sidebar-navigation');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<i data-lucide="chevrons-right"></i>';
        toggle.setAttribute('aria-label', 'Expand sidebar');
        toggle.setAttribute('title', 'Expand sidebar');
        sidebar.insertBefore(toggle, sidebar.firstChild);

        var nav = sidebar.querySelector('.client-nav');
        if (nav && !nav.id) nav.id = 'client-sidebar-navigation';

        var storageKey = 'instaweb-client-sidebar-expanded';
        var expanded = false;
        try { expanded = window.localStorage.getItem(storageKey) === 'true'; } catch (error) { /* Storage may be disabled. */ }

        function applyState(isExpanded) {
          workspace.classList.toggle('sidebar-expanded', isExpanded);
          toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
          toggle.setAttribute('aria-label', isExpanded ? 'Collapse sidebar' : 'Expand sidebar');
          toggle.setAttribute('title', isExpanded ? 'Collapse sidebar' : 'Expand sidebar');
          toggle.innerHTML = '<i data-lucide="' + (isExpanded ? 'chevrons-left' : 'chevrons-right') + '"></i>';
          if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
        }

        applyState(expanded);
        toggle.addEventListener('click', function () {
          expanded = !workspace.classList.contains('sidebar-expanded');
          applyState(expanded);
          try { window.localStorage.setItem(storageKey, expanded ? 'true' : 'false'); } catch (error) { /* Keep working without storage. */ }
        });
      });
    }());
  </script>
</body>
</html>
