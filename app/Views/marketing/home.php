<?php
$value = static fn (string $key, string $default = ''): string => htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$url = static function (string $target) use ($baseUrl): string {
    $target = trim($target);

    if ($target === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $target) || str_starts_with($target, 'mailto:') || str_starts_with($target, 'tel:')) {
        return $target;
    }

    return $baseUrl . '/' . ltrim($target, '/');
};

$heroKicker = $value('homepage_hero_kicker', 'Build · Host · Grow');
$heroTitle = $value('homepage_hero_title', 'Build and host websites in minutes.');
$heroSubtitle = $value('homepage_hero_subtitle', 'Create, host, and manage websites in one place. Publish static websites or start a WordPress project for a dynamic, database-driven website, with straightforward tools built for individuals and growing organisations across Malawi.');
$heroNote = trim((string) ($settings['homepage_hero_note'] ?? 'Create your account and publish your first site with Instaweb in minutes.'));
$primaryUrl = $url((string) ($settings['homepage_cta_url'] ?? '/register'));
$secondaryUrl = $url((string) ($settings['homepage_secondary_cta_url'] ?? '/login'));
$heroImage = $value('homepage_hero_image', 'https://images.pexels.com/photos/30530403/pexels-photo-30530403.jpeg?auto=compress&cs=tinysrgb&w=1280');
$heroAlt = $value('homepage_hero_media_alt', 'Instaweb dashboard showcase');
?>

<section class="homepage-hero">
  <div class="wrap hero-grid">
    <div>
      <div class="eyebrow-row"><span class="rule"></span><span class="eyebrow"><?= $heroKicker ?></span></div>
      <h1 class="homepage-hero-title"><?= htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="lede"><?= $heroSubtitle ?></p>
      <div class="hero-actions">
        <?php if ($primaryUrl !== ''): ?><a href="<?= htmlspecialchars($primaryUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-primary">Start Building Free</a><?php endif; ?>
        <?php if ($secondaryUrl !== ''): ?><a href="#templates" class="btn-ghost">See Templates</a><?php endif; ?>
      </div>
      <?php if ($heroNote !== ''): ?><p class="hero-note"><?= $heroNote ?></p><?php endif; ?>
      <div class="hero-capabilities" aria-label="Supported website types">
        <span>Static websites</span>
        <span>WordPress projects</span>
        <span>File management</span>
      </div>
    </div>
    <div class="editor-shell">
      <div class="editor-bar">
        <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
        <span class="editor-tab">index.html — lawreeay.lovestoblog.com</span>
      </div>
      <div class="editor-body"><span id="typed-code"></span><span class="cursor"></span></div>
      <div class="publish-badge" id="publishBadge"><span class="dotpulse"></span> Published to https://instaweb.free.dev/</div>
    </div>
  </div>
</section>

<section id="malawi" class="page-section section-muted">
  <div class="container">
    <div class="row g-5 align-items-start">
      <div class="col-lg-6 reveal">
        <span class="section-kicker">Built for Malawi</span>
        <h2>Simple website tools for schools, shops, churches, and growing brands.</h2>
        <div class="audience-tags mt-4">
          <span>Schools</span><span>Shops</span><span>Churches</span><span>Portfolios</span><span>Small Business</span>
        </div>
      </div>
      <div class="col-lg-6 reveal">
        <p>Whether you need a lightweight static website or a WordPress-powered site with dynamic pages and database-backed content, Instaweb brings core website creation and management tools into one workspace.</p>
        <p class="text-secondary">Start with a static project for simple pages, or choose WordPress when you need its content management features. WordPress setup still depends on the hosting server's PHP and database configuration.</p>
        <ul class="feature-list mt-4">
          <li><i data-lucide="arrow-right"></i> Quick setup with beginner-friendly controls</li>
          <li><i data-lucide="arrow-right"></i> Upload files and publish your site in minutes</li>
          <li><i data-lucide="arrow-right"></i> Keep your online presence professional and easy to maintain</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section id="how" class="page-section">
  <div class="container">
    <div class="section-heading section-heading-left reveal">
      <p class="section-kicker">How It Works</p>
      <h2>Launch your website in 3 steps.</h2>
    </div>
    <div class="row g-4 reveal-stagger">
      <div class="col-md-4">
        <div class="feature-card h-100">
          <i data-lucide="plus-circle"></i>
          <h3>Create Project</h3>
          <p>Start a static website or create a WordPress project from the available options.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100">
          <i data-lucide="upload"></i>
          <h3>Upload Files</h3>
          <p>Manage project files from your dashboard. WordPress projects include the files needed to complete WordPress setup.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-card h-100">
          <i data-lucide="globe-2"></i>
          <h3>Publish</h3>
          <p>Publish your project to its Instaweb address. WordPress sites require a working PHP and database environment to complete setup.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="features" class="page-section section-muted">
  <div class="container">
    <div class="section-heading section-heading-left reveal">
      <p class="section-kicker">Features</p>
      <h2>Everything you need.</h2>
    </div>
    <div class="row g-4 reveal-stagger">
      <?php foreach ([
        ['code-2', 'Built-in Code Editor', 'Syntax highlighting, project files, and live preview.'],
        ['folder-open', 'File Manager', 'Upload and organize files easily.'],
        ['server', 'Static Website Hosting', 'Publish HTML, CSS, JavaScript, images, and other static assets.'],
        ['layers', 'WordPress Projects', 'Prepare a WordPress website for setup on a PHP-enabled host with database access.'],
        ['bar-chart-3', 'Analytics', 'Track visitors and traffic as your project grows.'],
        ['link', 'Custom Domains', 'Connect a custom domain on Premium.'],
        ['shield-check', 'Secure Platform', 'Protected with modern security and validation.'],
      ] as $item): ?>
        <div class="col-md-6 col-lg-4">
          <div class="feature-card h-100">
            <i data-lucide="<?= htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') ?>"></i>
            <h3><?= htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($item[2], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="page-section">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <div class="dashboard-preview">
          <img src="<?= htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($heroAlt, ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded-3">
        </div>
      </div>
      <div class="col-lg-6">
        <span class="section-kicker">Powerful Dashboard</span>
        <h2>Manage websites, files, and billing in one place.</h2>
        <ul class="feature-list mt-4">
          <li><i data-lucide="check-circle"></i> Website management</li>
          <li><i data-lucide="check-circle"></i> File uploads and storage insights</li>
          <li><i data-lucide="check-circle"></i> Traffic analytics and reports</li>
          <li><i data-lucide="check-circle"></i> Billing controls and plan upgrades</li>
          <li><i data-lucide="check-circle"></i> Project settings and publishing tools</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section id="pricing" class="page-section section-muted">
  <div class="container">
    <div class="section-heading reveal">
      <p class="section-kicker">Pricing</p>
      <h2>Simple pricing.</h2>
    </div>
    <?php include APP_PATH . '/Views/marketing/partials/pricing-cards.php'; ?>
  </div>
</section>

<section id="templates" class="page-section">
  <div class="container">
    <div class="text-center section-heading reveal">
      <p class="section-kicker">Templates</p>
      <h2>Start with a template.</h2>
    </div>
    <div class="row g-4 reveal-stagger">
      <?php foreach ([
        ['Business Website', 'https://images.pexels.com/photos/461073/pexels-photo-461073.jpeg?auto=compress&cs=tinysrgb&w=800'],
        ['Portfolio', 'https://images.pexels.com/photos/326514/pexels-photo-326514.jpeg?auto=compress&cs=tinysrgb&w=800'],
        ['Church Website', 'https://images.pexels.com/photos/69432/pexels-photo-69432.jpeg?auto=compress&cs=tinysrgb&w=800'],
        ['School Website', 'https://images.pexels.com/photos/5621952/pexels-photo-5621952.jpeg?auto=compress&cs=tinysrgb&w=800'],
        ['Blog', 'https://images.pexels.com/photos/7394719/pexels-photo-7394719.jpeg?auto=compress&cs=tinysrgb&w=800'],
        ['Landing Page', 'https://images.pexels.com/photos/3584973/pexels-photo-3584973.jpeg?auto=compress&cs=tinysrgb&w=800'],
      ] as $template): ?>
        <div class="col-sm-6 col-lg-4">
          <div class="template-card h-100">
            <img src="<?= htmlspecialchars($template[1], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($template[0], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded-3">
            <p class="mt-3 fw-semibold mb-0"><?= htmlspecialchars($template[0], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="container text-center">
    <span class="section-kicker">Get started</span>
    <h2>Ready to launch your website?</h2>
    <p>Create your account and start building today.</p>
    <div class="d-flex justify-content-center flex-wrap gap-3 mt-4">
      <a class="btn btn-light btn-lg" href="<?= htmlspecialchars($primaryUrl, ENT_QUOTES, 'UTF-8') ?>">Create Account</a>
      <a class="btn btn-outline-light btn-lg" href="<?= htmlspecialchars($secondaryUrl, ENT_QUOTES, 'UTF-8') ?>">Log In</a>
    </div>
  </div>
</section>
