<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php $themeSettings = (new \App\Models\Setting())->all(); ?>
  <?php $pageTitle = htmlspecialchars($title ?? ($app['name'] ?? 'Instaweb'), ENT_QUOTES, 'UTF-8'); ?>
  <?php $pageDescription = htmlspecialchars($themeSettings['meta_description'] ?? 'Instaweb offers fast website hosting, a browser-based website builder, secure file uploads, and reliable publishing tools for individuals, developers, and small businesses.', ENT_QUOTES, 'UTF-8'); ?>
  <?php $pageKeywords = htmlspecialchars($themeSettings['seo_keywords'] ?? 'website hosting, web hosting, site builder, file uploads', ENT_QUOTES, 'UTF-8'); ?>
  <?php $siteUrl = rtrim(($app['base_url'] ?? ''), '/'); ?>
  <?php $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
  <?php $basePath = parse_url($siteUrl, PHP_URL_PATH) ?: ''; ?>
  <?php $relativePath = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $requestPath); ?>
  <?php $relativePath = $relativePath === '' ? '/' : $relativePath; ?>
  <?php $relativePath = rtrim($relativePath, '/') ?: '/'; ?>
  <?php $pageUrl = htmlspecialchars($siteUrl . '/' . ltrim($relativePath, '/'), ENT_QUOTES, 'UTF-8'); ?>
  <?php $ogImage = htmlspecialchars(trim((string) ($themeSettings['open_graph_image'] ?? $themeSettings['homepage_hero_image'] ?? 'https://images.pexels.com/photos/30530403/pexels-photo-30530403.jpeg?auto=compress&cs=tinysrgb&w=1280')), ENT_QUOTES, 'UTF-8'); ?>
  <title><?= $pageTitle ?></title>
  <meta name="description" content="<?= $pageDescription ?>">
  <meta name="keywords" content="<?= $pageKeywords ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="<?= $pageUrl ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?= htmlspecialchars($app['name'] ?? 'Instaweb', ENT_QUOTES, 'UTF-8') ?>">
  <meta property="og:title" content="<?= $pageTitle ?>">
  <meta property="og:description" content="<?= $pageDescription ?>">
  <meta property="og:image" content="<?= $ogImage ?>">
  <meta property="og:url" content="<?= $pageUrl ?>">
  <meta name="instaweb-base-url" content="<?= htmlspecialchars($app['base_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= $pageTitle ?>">
  <meta name="twitter:description" content="<?= $pageDescription ?>">
  <meta name="twitter:image" content="<?= $ogImage ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/css/styles.css', ENT_QUOTES, 'UTF-8') ?>">
  <?php $themePrimary = $themeSettings['primary_color'] ?? '#0F766E'; ?>
  <?php $themeSecondary = $themeSettings['secondary_color'] ?? '#0F766E'; ?>
  <?php $themeText = $themeSettings['text_color'] ?? '#111827'; ?>
  <?php $themeMutedText = $themeSettings['muted_text_color'] ?? '#5B6472'; ?>
  <?php $themeBackground = $themeSettings['page_background_color'] ?? '#FFFFFF'; ?>
  <?php $themeSurfaceBg = $themeSettings['surface_background_color'] ?? '#ffffff'; ?>
  <?php $themeSurfaceBorder = $themeSettings['surface_border_color'] ?? 'rgba(15, 23, 42, 0.08)'; ?>
  <?php $themeSidebarBg = $themeSettings['sidebar_background_color'] ?? '#FFFFFF'; ?>
  <?php $themeSidebarText = $themeSettings['sidebar_text_color'] ?? '#475569'; ?>
  <?php $themeBrandBg = $themeSettings['primary_color'] ?? '#0F766E'; ?>
  <?php $themeBrandText = '#ffffff'; ?>
  <?php $appIcon = trim((string) ($themeSettings['app_icon_url'] ?? '')); ?>
  <?php if ($appIcon !== ''): ?>
    <link rel="icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>">
  <?php endif; ?>
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
</head>
<body>
  <?php
    $publicNav = [
        ['label' => 'Home', 'path' => '/'],
        ['label' => 'Features', 'path' => '/features'],
        ['label' => 'Pricing', 'path' => '/pricing'],
        ['label' => 'About', 'path' => '/about'],
        ['label' => 'Contact', 'path' => '/contact'],
    ];
  ?>
  <!-- Modern Public Header -->
  <header class="public-header" id="publicHeader">
    <div class="header-container">
      <?php $appIcon = trim((string) ($themeSettings['app_icon_url'] ?? '')); ?>
      <!-- Logo -->
      <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/', ENT_QUOTES, 'UTF-8') ?>" class="header-logo">
        <?php if ($appIcon !== ''): ?>
          <img src="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>" alt="App icon" class="header-logo-icon">
        <?php else: ?>
          <svg width="32" height="32" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="28" height="28" rx="6" fill="var(--app-primary)"/>
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="DM Sans, sans-serif" font-size="16" font-weight="700" fill="white">T</text>
          </svg>
        <?php endif; ?>
        <span class="header-logo-text"><?= htmlspecialchars($app['name'] ?? 'Instaweb', ENT_QUOTES, 'UTF-8') ?></span>
      </a>

      <!-- Desktop Navigation -->
      <nav class="header-nav">
        <?php foreach ($publicNav as $navItem): ?>
          <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . $navItem['path'], ENT_QUOTES, 'UTF-8') ?>" class="nav-link<?= $relativePath === $navItem['path'] ? ' active' : '' ?>"<?= $relativePath === $navItem['path'] ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($navItem['label'], ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
      </nav>

      <!-- Right Section -->
      <div class="header-actions">
        <?php if (\App\Services\AuthService::check()): ?>
          <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>" class="btn-text">Dashboard</a>
          <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>" style="display: inline;">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn-primary">Logout</button>
          </form>
        <?php else: ?>
          <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/login', ENT_QUOTES, 'UTF-8') ?>" class="btn-text">Login</a>
          <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/register', ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Get Started</a>
        <?php endif; ?>
      </div>

      <!-- Mobile Hamburger -->
      <button class="header-menu-toggle" id="headerMenuToggle" aria-label="Open navigation menu" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>

    <!-- Mobile Menu -->
    <div class="header-menu-overlay" id="headerMenuOverlay"></div>
    <div class="header-mobile-menu" id="headerMobileMenu">
      <div class="mobile-menu-header">
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/', ENT_QUOTES, 'UTF-8') ?>" class="header-logo-mobile">
          <?php if ($appIcon !== ''): ?>
            <img src="<?= htmlspecialchars($appIcon, ENT_QUOTES, 'UTF-8') ?>" alt="App icon" class="header-logo-icon-mobile">
          <?php else: ?>
            <svg width="24" height="24" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="28" height="28" rx="6" fill="var(--app-primary)"/>
              <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="DM Sans, sans-serif" font-size="16" font-weight="700" fill="white">T</text>
            </svg>
          <?php endif; ?>
          <span><?= htmlspecialchars($app['name'] ?? 'Instaweb', ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <button class="mobile-menu-close" id="headerMenuClose" aria-label="Close menu">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <nav class="mobile-menu-nav">
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/', ENT_QUOTES, 'UTF-8') ?>" class="mobile-nav-link">
          <i data-lucide="home"></i> Home
        </a>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/features', ENT_QUOTES, 'UTF-8') ?>" class="mobile-nav-link">
          <i data-lucide="sparkles"></i> Features
        </a>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/pricing', ENT_QUOTES, 'UTF-8') ?>" class="mobile-nav-link">
          <i data-lucide="dollar-sign"></i> Pricing
        </a>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/templates', ENT_QUOTES, 'UTF-8') ?>" class="mobile-nav-link">
          <i data-lucide="palette"></i> Templates
        </a>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/about', ENT_QUOTES, 'UTF-8') ?>" class="mobile-nav-link">
          <i data-lucide="info"></i> About
        </a>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/contact', ENT_QUOTES, 'UTF-8') ?>" class="mobile-nav-link">
          <i data-lucide="mail"></i> Contact
        </a>
      </nav>

      <div class="mobile-menu-divider"></div>

      <div class="mobile-menu-actions">
        <?php if (\App\Services\AuthService::check()): ?>
          <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/dashboard', ENT_QUOTES, 'UTF-8') ?>" class="mobile-btn-text">Dashboard</a>
          <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/logout', ENT_QUOTES, 'UTF-8') ?>">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="mobile-btn-primary">Logout</button>
          </form>
        <?php else: ?>
          <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/login', ENT_QUOTES, 'UTF-8') ?>" class="mobile-btn-text">Login</a>
          <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/register', ENT_QUOTES, 'UTF-8') ?>" class="mobile-btn-primary">Get Started</a>
        <?php endif; ?>
      </div>

      <div class="mobile-menu-divider"></div>

      <div class="mobile-menu-footer">
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/', ENT_QUOTES, 'UTF-8') ?>" class="footer-link">Home</a>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/privacy', ENT_QUOTES, 'UTF-8') ?>" class="footer-link">Privacy</a>
        <a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/terms', ENT_QUOTES, 'UTF-8') ?>" class="footer-link">Terms</a>
        <small>&copy; <?= date('Y') ?> <?= htmlspecialchars($app['name'] ?? 'Instaweb', ENT_QUOTES, 'UTF-8') ?> v1.0</small>
      </div>
    </div>
  </header>

  <main>
    <?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $type): ?>
      <?php $message = \App\Core\Session::pullFlash($key); ?>
      <?php if ($message): ?>
        <div class="container pt-4">
          <div class="alert alert-<?= $type ?> shadow-sm" role="alert">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?= $content ?? '' ?>
  </main>
  <?php
  $footerEnabled = (($themeSettings['footer_enabled'] ?? '1') === '1');
  $footerLayout = $themeSettings['footer_layout'] ?? '4_columns';
  $footerUrl = static function (string $target) use ($siteUrl): string {
      $target = trim($target);
      if ($target === '') {
          return '#';
      }
      if (preg_match('#^(https?:)?//#i', $target) || str_starts_with($target, 'mailto:') || str_starts_with($target, 'tel:')) {
          return $target;
      }
      if (str_starts_with($target, '#')) {
          return $target;
      }
      return $siteUrl . '/' . ltrim($target, '/');
  };
  $footerDefaultUrl = static function (string $label): string {
      $slug = strtolower(trim($label));
      $slug = preg_replace('/\s+/', ' ', $slug);
      return match ($slug) {
          'home' => '/',
          'features' => '/features',
          'pricing' => '/pricing',
          'templates' => '/templates',
          'about', 'about us' => '/about',
          'contact', 'support' => '/contact',
          'faq' => '/faq',
          'privacy', 'privacy policy' => '/privacy',
          'terms', 'terms of service' => '/terms',
          'login', 'sign in' => '/login',
          'register', 'get started' => '/register',
          'dashboard' => '/dashboard',
          default => '#',
      };
  };
  $footerLinks = static function (string $value) use ($footerDefaultUrl, $footerUrl): array {
      $items = preg_split('/\r\n|\r|\n/', trim($value));
      $links = [];
      foreach ($items as $item) {
          $item = trim($item);
          if ($item !== '') {
              $parts = array_map('trim', explode('|', $item, 2));
              $label = $parts[0] ?? '';
              $target = $parts[1] ?? $footerDefaultUrl($label);
              if ($label !== '') {
                  $links[] = [
                      'label' => $label,
                      'url' => $footerUrl($target),
                  ];
              }
          }
      }
      return $links;
  };
  $platformLinks = $footerLinks((string) ($themeSettings['footer_platform_links'] ?? "Features | /features\nPricing | /pricing\nTemplates | /templates\nDashboard | /dashboard"));
  $resourcesLinks = $footerLinks((string) ($themeSettings['footer_resources_links'] ?? "FAQ | /faq\nContact | /contact\nLogin | /login\nRegister | /register"));
  $companyLinks = $footerLinks((string) ($themeSettings['footer_company_links'] ?? "About | /about\nContact | /contact\nPricing | /pricing"));
  $legalLinks = $footerLinks((string) ($themeSettings['footer_legal_links'] ?? "Privacy Policy | /privacy\nTerms | /terms"));
  $socialLinks = [
      ['label' => 'Facebook', 'url' => trim((string) ($themeSettings['footer_social_facebook_url'] ?? ''))],
      ['label' => 'Instagram', 'url' => trim((string) ($themeSettings['footer_social_instagram_url'] ?? ''))],
      ['label' => 'LinkedIn', 'url' => trim((string) ($themeSettings['footer_social_linkedin_url'] ?? ''))],
      ['label' => 'GitHub', 'url' => trim((string) ($themeSettings['footer_social_github_url'] ?? ''))],
      ['label' => 'YouTube', 'url' => trim((string) ($themeSettings['footer_social_youtube_url'] ?? ''))],
      ['label' => 'TikTok', 'url' => trim((string) ($themeSettings['footer_social_tiktok_url'] ?? ''))],
      ['label' => 'WhatsApp', 'url' => trim((string) ($themeSettings['footer_social_whatsapp_url'] ?? ''))],
  ];
  $footerBrandName = trim((string) ($themeSettings['footer_brand_name'] ?? $app['name'] ?? 'Instaweb')) ?: 'Instaweb';
  $footerLogo = trim((string) ($themeSettings['footer_logo_url'] ?? $themeSettings['app_icon_url'] ?? ''));
  $footerTagline = trim((string) ($themeSettings['footer_tagline'] ?? 'Build • Host • Grow')) ?: 'Build • Host • Grow';
  $footerDescription = trim((string) ($themeSettings['footer_description'] ?? 'Affordable hosting for students, churches, portfolios and businesses.')) ?: 'Affordable hosting for students, churches, portfolios and businesses.';
  $footerCompany = trim((string) ($themeSettings['footer_company_name'] ?? 'Lawreay Technologies')) ?: 'Lawreay Technologies';
  $footerEmail = trim((string) ($themeSettings['footer_email'] ?? 'phukal@mau.adventist.org')) ?: 'phukal@mau.adventist.org';
  $footerPhone = trim((string) ($themeSettings['footer_phone'] ?? '+265 XXX XXX XXX')) ?: '+265 XXX XXX XXX';
  $footerWebsite = trim((string) ($themeSettings['footer_website'] ?? 'instaweb.free.dev')) ?: 'instaweb.free.dev';
  $footerWebsiteUrl = preg_match('#^https?://#i', $footerWebsite) ? $footerWebsite : 'https://' . ltrim($footerWebsite, '/');
  $footerAddress = trim((string) ($themeSettings['footer_address'] ?? 'Ntcheu, Malawi')) ?: 'Ntcheu, Malawi';
  $footerMapsUrl = trim((string) ($themeSettings['footer_google_maps_url'] ?? ''));
  $footerBusinessHours = trim((string) ($themeSettings['footer_business_hours'] ?? ''));
  $footerNewsletterUrl = $footerUrl((string) ($themeSettings['footer_newsletter_url'] ?? '/register'));
  $footerCopyright = trim((string) ($themeSettings['footer_copyright_template'] ?? '© {year} {company}')) ?: '© {year} {company}';
  $footerCopyright = str_replace(['{year}', '{company}', '{website}', '{version}'], [date('Y'), $footerCompany, $footerWebsite, ($app['version'] ?? '1.0')], $footerCopyright);
  ?>
  <?php if ($footerEnabled): ?>
  <footer class="site-footer" style="--footer-bg: <?= htmlspecialchars($themeSettings['footer_background_color'] ?? '#0F172A', ENT_QUOTES, 'UTF-8') ?>; --footer-text: <?= htmlspecialchars($themeSettings['footer_text_color'] ?? '#FFFFFF', ENT_QUOTES, 'UTF-8') ?>; --footer-link: <?= htmlspecialchars($themeSettings['footer_link_color'] ?? '#CBD5E1', ENT_QUOTES, 'UTF-8') ?>; --footer-link-hover: <?= htmlspecialchars($themeSettings['footer_link_hover_color'] ?? '#2DD4BF', ENT_QUOTES, 'UTF-8') ?>;">
    <div class="container">
      <div class="site-footer-grid site-footer-grid-<?= htmlspecialchars($footerLayout, ENT_QUOTES, 'UTF-8') ?>">
        <?php if (($themeSettings['footer_component_brand_card'] ?? '1') === '1'): ?>
        <div class="site-footer-card">
          <a class="site-footer-brand" href="<?= htmlspecialchars($siteUrl . '/', ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($footerLogo !== ''): ?><img src="<?= htmlspecialchars($footerLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($footerBrandName, ENT_QUOTES, 'UTF-8') ?> logo"><?php endif; ?>
            <span><?= htmlspecialchars($footerBrandName, ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <p class="site-footer-tagline"><?= htmlspecialchars($footerTagline, ENT_QUOTES, 'UTF-8') ?></p>
          <p><?= htmlspecialchars($footerDescription, ENT_QUOTES, 'UTF-8') ?></p>
          <ul class="site-footer-contact">
            <?php if ($footerEmail !== ''): ?><li><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($footerEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($footerEmail, ENT_QUOTES, 'UTF-8') ?></a></li><?php endif; ?>
            <?php if ($footerPhone !== ''): ?><li><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($footerPhone, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($footerPhone, ENT_QUOTES, 'UTF-8') ?></a></li><?php endif; ?>
            <?php if ($footerWebsite !== ''): ?><li><strong>Website:</strong> <a href="<?= htmlspecialchars($footerWebsiteUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer"><?= htmlspecialchars(preg_replace('#^https?://#i', '', $footerWebsite), ENT_QUOTES, 'UTF-8') ?></a></li><?php endif; ?>
            <?php if ($footerAddress !== ''): ?><li><strong>Address:</strong> <?php if ($footerMapsUrl !== ''): ?><a href="<?= htmlspecialchars($footerMapsUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer"><?= htmlspecialchars($footerAddress, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><?= htmlspecialchars($footerAddress, ENT_QUOTES, 'UTF-8') ?><?php endif; ?></li><?php endif; ?>
            <?php if ($footerBusinessHours !== ''): ?><li><strong>Hours:</strong> <?= htmlspecialchars($footerBusinessHours, ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if (($themeSettings['footer_component_platform_links'] ?? '1') === '1' && (($themeSettings['footer_platform_show'] ?? '1') === '1')): ?>
        <div class="site-footer-card">
          <h5><?= htmlspecialchars($themeSettings['footer_platform_title'] ?? 'Platform', ENT_QUOTES, 'UTF-8') ?></h5>
          <ul class="site-footer-links">
            <?php foreach ($platformLinks as $link): ?><li><a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if (($themeSettings['footer_component_resources'] ?? '1') === '1' && (($themeSettings['footer_resources_show'] ?? '1') === '1')): ?>
        <div class="site-footer-card">
          <h5><?= htmlspecialchars($themeSettings['footer_resources_title'] ?? 'Resources', ENT_QUOTES, 'UTF-8') ?></h5>
          <ul class="site-footer-links">
            <?php foreach ($resourcesLinks as $link): ?><li><a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if (($themeSettings['footer_component_company'] ?? '1') === '1' && (($themeSettings['footer_company_show'] ?? '1') === '1')): ?>
        <div class="site-footer-card">
          <h5><?= htmlspecialchars($themeSettings['footer_company_title'] ?? 'Company', ENT_QUOTES, 'UTF-8') ?></h5>
          <ul class="site-footer-links">
            <?php foreach ($companyLinks as $link): ?><li><a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if (($themeSettings['footer_component_legal'] ?? '1') === '1' && (($themeSettings['footer_legal_show'] ?? '1') === '1')): ?>
        <div class="site-footer-card">
          <h5><?= htmlspecialchars($themeSettings['footer_legal_title'] ?? 'Legal', ENT_QUOTES, 'UTF-8') ?></h5>
          <ul class="site-footer-links">
            <?php foreach ($legalLinks as $link): ?><li><a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </div>
      <div class="site-footer-bottom">
        <?php if (($themeSettings['footer_component_social_icons'] ?? '1') === '1'): ?>
        <div class="site-footer-socials">
          <?php foreach ($socialLinks as $social): ?><?php if ($social['url'] !== ''): ?><a href="<?= htmlspecialchars($social['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noreferrer"><?= htmlspecialchars($social['label'], ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (($themeSettings['footer_component_newsletter'] ?? '1') === '1' && (($themeSettings['footer_newsletter_enabled'] ?? '0') === '1')): ?>
        <div class="site-footer-newsletter">
          <h6><?= htmlspecialchars($themeSettings['footer_newsletter_title'] ?? 'Stay Updated', ENT_QUOTES, 'UTF-8') ?></h6>
          <p><?= htmlspecialchars($themeSettings['footer_newsletter_description'] ?? 'Receive updates and announcements.', ENT_QUOTES, 'UTF-8') ?></p>
          <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($footerNewsletterUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($themeSettings['footer_newsletter_button_text'] ?? 'Subscribe', ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <?php endif; ?>
        <?php if (($themeSettings['footer_component_system_status'] ?? '1') === '1' && (($themeSettings['footer_show_system_status'] ?? '0') === '1')): ?>
        <div class="site-footer-status">
          <span class="site-footer-status-dot" style="background: <?= htmlspecialchars($themeSettings['footer_system_status_color'] ?? '#22C55E', ENT_QUOTES, 'UTF-8') ?>"></span>
          <a href="<?= htmlspecialchars($themeSettings['footer_system_status_link'] ?? '/status', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($themeSettings['footer_system_status_text'] ?? 'All Systems Operational', ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <?php endif; ?>
      </div>
      <?php if (($themeSettings['footer_component_copyright_bar'] ?? '1') === '1'): ?>
      <div class="site-footer-copyright">
        <?php if (($themeSettings['footer_show_version'] ?? '0') === '1'): ?><span>Version <?= htmlspecialchars($app['version'] ?? '1.0', ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        <?php if (($themeSettings['footer_show_build_number'] ?? '0') === '1'): ?><span>Build <?= htmlspecialchars($app['build'] ?? '001', ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        <?php if (($themeSettings['footer_show_copyright'] ?? '1') === '1'): ?><span><?= htmlspecialchars($footerCopyright, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
        <?php if (($themeSettings['footer_made_in_malawi'] ?? '1') === '1'): ?><span>Made in Malawi 🇲🇼</span><?php endif; ?>
        <?php if (($themeSettings['footer_powered_by'] ?? '1') === '1'): ?><span>Powered by Instaweb</span><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </footer>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>
  <script src="<?= htmlspecialchars(($app['base_url'] ?? '') . '/assets/js/main.js?v=20260629-tabs', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
