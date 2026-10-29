<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php $themeSettings = (new \App\Models\Setting())->all(); ?>
  <?php $themePrimary = $themeSettings['primary_color'] ?? '#153E35'; ?>
  <?php $themeSecondary = $themeSettings['secondary_color'] ?? '#B6D96C'; ?>
  <?php $themeText = $themeSettings['text_color'] ?? '#182B25'; ?>
  <?php $themeMutedText = $themeSettings['muted_text_color'] ?? '#6B756F'; ?>
  <?php $themeBackground = $themeSettings['page_background_color'] ?? '#F7F6F0'; ?>
  <?php $themeSurfaceBg = $themeSettings['surface_background_color'] ?? '#ffffff'; ?>
  <?php $themeSurfaceBorder = $themeSettings['surface_border_color'] ?? 'rgba(15, 23, 42, 0.08)'; ?>
  <?php $themeSidebarBg = $themeSettings['sidebar_background_color'] ?? '#FFFFFF'; ?>
  <?php $themeSidebarText = $themeSettings['sidebar_text_color'] ?? '#475569'; ?>
  <?php $themeBrandBg = $themeSettings['primary_color'] ?? '#0D6EFD'; ?>
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
      --app-primary-soft: color-mix(in srgb, var(--app-primary) 12%, white);
      --app-secondary-soft: color-mix(in srgb, var(--app-secondary) 12%, white);
      --app-primary-strong: color-mix(in srgb, var(--app-primary) 18%, black);
    }
  </style>
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/styles.css', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/client-dashboard.css?v=20261021-topbar-plan', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/admin-dashboard-refresh.css?v=20261022-sidebar-state-fix', ENT_QUOTES, 'UTF-8') ?>">
  <?php $appIcon = trim((string) ($themeSettings['app_icon_url'] ?? '')); ?>
  <?php if ($appIcon !== ''): ?>
    <link rel="icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
  <style id="platform-theme-vars-2">
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
      --app-primary-soft: color-mix(in srgb, var(--app-primary) 12%, white);
      --app-secondary-soft: color-mix(in srgb, var(--app-secondary) 12%, white);
      --app-primary-strong: color-mix(in srgb, var(--app-primary) 18%, black);
    }
  </style>

  <?php if (\App\Services\AuthService::check()): ?>
    <meta name="instaweb-base-url" content="<?= htmlspecialchars((string) ($app['base_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="instaweb-csrf" content="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(rtrim((string) ($app['base_url'] ?? ''), '/') . '/assets/css/notification-center.css?v=20261029-global-notifications', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(rtrim((string) ($app['base_url'] ?? ''), '/') . '/assets/css/feedback-widget.css?v=20261029-feedback', ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>

</head>
<body class="admin-body">
  <?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $type): ?>
    <?php $message = \App\Core\Session::pullFlash($key); ?>
    <?php if ($message): ?>
      <div class="admin-toast alert alert-<?= $type ?> shadow-sm" role="alert">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
  <?= $content ?? '' ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>
  <script src="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/js/main.js?v=20261022-sidebar-state-fix', ENT_QUOTES, 'UTF-8') ?>"></script>

  <?php if (\App\Services\AuthService::check()): ?>
    <script src="<?= htmlspecialchars(rtrim((string) ($app['base_url'] ?? ''), '/') . '/assets/js/notification-center.js?v=20261029-global-notifications', ENT_QUOTES, 'UTF-8') ?>"></script>
  <?php endif; ?>

</body>
</html>
