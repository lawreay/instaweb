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
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/client-dashboard.css?v=20261017-sidebar-scroll', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/client-dashboard-home.css?v=20261018-client-pages', ENT_QUOTES, 'UTF-8') ?>">
  <?php $appIcon = trim((string) ($themeSettings['app_icon_url'] ?? '')); ?>
  <?php if ($appIcon !== ''): ?>
    <link rel="icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
</head>
<body class="dashboard-body">
  <?php $clientHeaderPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
  <header class="client-app-header">
    <a class="client-app-brand" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>" aria-label="InstaWeb client workspace home">
      <span class="client-app-brand-mark"><i data-lucide="layers"></i></span>
      <span>InstaWeb<small>Client workspace</small></span>
    </a>
    <button class="client-header-menu" type="button" aria-controls="clientHeaderNav" aria-expanded="false" aria-label="Open navigation" data-client-menu-toggle>
      <i data-lucide="menu"></i>
    </button>
    <nav id="clientHeaderNav" aria-label="Quick navigation">
      <a class="<?= $clientHeaderPath === '/dashboard' ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="layout-dashboard"></i> Dashboard</a>
      <a class="<?= str_starts_with($clientHeaderPath, '/dashboard/websites') ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="globe-2"></i> Manage my sites</a>
      <a class="<?= str_starts_with($clientHeaderPath, '/profile') ? 'active' : '' ?>" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="user-round"></i> Account</a>
    </nav>
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
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>">Manage sites</a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile', ENT_QUOTES, 'UTF-8') ?>">Account settings</a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/terms', ENT_QUOTES, 'UTF-8') ?>">Terms</a>
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/privacy', ENT_QUOTES, 'UTF-8') ?>">Privacy</a>
    </nav>
  </footer>
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
      var toggle = document.querySelector('[data-client-menu-toggle]');
      var navigation = document.getElementById('clientHeaderNav');
      if (!toggle || !navigation) return;

      function setMenuOpen(open) {
        navigation.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        toggle.innerHTML = '<i data-lucide="' + (open ? 'x' : 'menu') + '"></i>';
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
          window.lucide.createIcons();
        }
      }

      toggle.addEventListener('click', function () {
        setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true');
      });

      navigation.addEventListener('click', function (event) {
        if (event.target.closest('a') && window.matchMedia('(max-width: 700px)').matches) {
          setMenuOpen(false);
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setMenuOpen(false);
      });

      window.addEventListener('resize', function () {
        if (!window.matchMedia('(max-width: 700px)').matches) setMenuOpen(false);
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
