<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$name = trim((string) ($portfolio['display_name'] ?? 'Portfolio'));
$headline = trim((string) ($portfolio['headline'] ?? ''));
$bio = trim((string) ($portfolio['bio'] ?? ''));
$template = (string) ($portfolio['template_key'] ?? 'classic');
$avatar = trim((string) ($portfolio['avatar_path'] ?? ''));
$avatarUrl = '';
if ($avatar !== '' && !str_contains($avatar, '..') && preg_match('#^uploads/[A-Za-z0-9_./-]+$#', $avatar)) {
    $avatarUrl = $baseUrl . '/' . ltrim($avatar, '/');
}
$email = trim((string) ($portfolio['email'] ?? ''));
$location = trim((string) ($portfolio['location'] ?? ''));
$initials = strtoupper(substr($name, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $escape($name) ?><?= $headline !== '' ? ' | ' . $escape($headline) : '' ?></title>
  <meta name="description" content="<?= $escape(mb_substr($bio !== '' ? $bio : $headline, 0, 160)) ?>">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="<?= $template === '3d' ? '#080b0e' : '#f4f0e7' ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/styles.css') ?>">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio.css') ?>">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/navbar.css') ?>">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/images.css') ?>">
  <?php if ($template === 'editorial'): ?>
    <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-editorial.css') ?>">
  <?php elseif ($template === '3d'): ?>
    <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-3d.css') ?>">
  <?php endif; ?>
  <style>
    body{min-height:100vh}#portfolio-container{overflow:hidden}.portfolio-nav-logo{width:38px;height:38px;display:grid;place-items:center;border:1px solid currentColor;font-weight:800}
    .portfolio-avatar{display:grid;place-items:center;overflow:hidden;width:100%;aspect-ratio:4/5;background:linear-gradient(135deg,#d97706,#172b3a);color:#fff;font-size:clamp(4rem,12vw,8rem);font-weight:800}
    .portfolio-avatar img{width:100%;height:100%;object-fit:cover}.portfolio-content-section{padding:5rem clamp(1.25rem,6vw,6rem)}
    .portfolio-content-inner{max-width:1120px;margin:0 auto}.portfolio-content-section h2{font-size:clamp(2rem,5vw,3.5rem);font-weight:800;letter-spacing:-.04em}
    .portfolio-project-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,270px),1fr));gap:1.2rem}
    .portfolio-project-card{height:100%;padding:1.6rem;border:1px solid rgba(100,116,139,.25);background:rgba(255,255,255,.78);box-shadow:0 14px 35px rgba(15,23,42,.06)}
    .portfolio-project-card h3{font-size:1.25rem;font-weight:750}.portfolio-project-card p{white-space:pre-line;color:#475569}
    .portfolio-project-card a{display:inline-flex;margin-top:1rem;color:#b45309;font-weight:700;text-decoration:none}
    .portfolio-about{background:#f8fafc;color:#172b3a}.portfolio-contact{background:#172b3a;color:#f8fafc}
    .portfolio-contact a{color:#fbbf24}.portfolio-footer{padding:1.5rem;text-align:center;font-size:.85rem;color:#64748b}
    .portfolio-empty{padding:2rem;border:1px dashed #94a3b8;color:#64748b}
    @media(max-width:650px){.portfolio-content-section{padding:3.5rem 1.25rem}.portfolio-hero{min-height:auto}}
  </style>
</head>
<body class="portfolio-template-<?= $escape($template) ?>">
  <header class="navbar">
    <nav>
      <div class="nav-container">
        <a href="#home" class="logo-container" aria-label="<?= $escape($name) ?> home">
          <span class="portfolio-nav-logo"><?= $escape($initials) ?></span>
          <span class="logo-text"><?= $escape($name) ?></span>
        </a>
        <button class="hamburger" id="hamburger-menu" type="button" aria-label="Toggle navigation" aria-controls="nav-menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
        <ul id="nav-menu">
          <li><a href="#about">About</a></li>
          <li><a href="#projects">Projects</a></li>
          <?php if ($email !== ''): ?><li><a href="#contact">Contact</a></li><?php endif; ?>
        </ul>
      </div>
    </nav>
  </header>

  <main id="portfolio-container">
    <section id="home" class="portfolio-hero hero-gradient" aria-labelledby="hero-name">
      <?php if ($template === '3d'): ?><canvas id="portfolio-scene" aria-hidden="true"></canvas><div class="hero-grain" aria-hidden="true"></div><?php endif; ?>
      <?php if ($template === 'editorial'): ?><div class="hero-paper-grid" aria-hidden="true"></div><?php endif; ?>
      <div class="hero-shell">
        <div class="hero-copy">
          <p class="hero-kicker"><?= $template === '3d' ? '<span class="status-dot"></span>' : '<span class="hero-kicker-mark">' . $escape($initials) . '</span>' ?> PORTFOLIO<?= $location !== '' ? ' · ' . $escape($location) : '' ?></p>
          <p class="hero-eyebrow"><?= $escape($headline !== '' ? $headline : 'Independent professional') ?></p>
          <h1 id="hero-name"><?= $escape($name) ?></h1>
          <p id="hero-title" class="hero-role"><?= $escape($headline) ?></p>
          <p id="hero-tagline" class="hero-summary"><?= nl2br($escape($bio !== '' ? $bio : 'Welcome to my portfolio. Explore selected work and get in touch.')) ?></p>
          <div class="hero-actions">
            <a class="hero-button hero-button-primary" href="#projects">Explore my work <span aria-hidden="true">↘</span></a>
            <?php if ($email !== ''): ?><a class="hero-text-link hero-button-secondary" href="mailto:<?= $escape($email) ?>">Contact me <span aria-hidden="true">↗</span></a><?php endif; ?>
          </div>
          <?php if ($template === '3d'): ?>
            <div class="hero-proof"><div><strong>Selected work</strong><span><?= count($projects) ?> portfolio projects</span></div><div><strong><?= $escape($location !== '' ? $location : 'Open to opportunities') ?></strong><span>Profile and work</span></div></div>
          <?php else: ?>
            <div class="hero-footnote"><span class="hero-footnote-line"></span><span><?= $escape($location !== '' ? $location : 'Selected work and experience') ?></span></div>
          <?php endif; ?>
        </div>
        <?php if ($template === 'editorial' || $avatarUrl !== ''): ?>
          <div class="hero-portrait-wrap">
            <div class="hero-photo-frame">
              <div class="portfolio-avatar"><?php if ($avatarUrl !== ''): ?><img src="<?= $escape($avatarUrl) ?>" alt="<?= $escape($name) ?>"><?php else: ?><?= $escape($initials) ?><?php endif; ?></div>
              <span class="hero-photo-corner hero-photo-corner-tl" aria-hidden="true"></span><span class="hero-photo-corner hero-photo-corner-br" aria-hidden="true"></span>
            </div>
            <div class="hero-photo-caption"><span>PORTFOLIO</span><span><?= $escape($location) ?></span></div>
          </div>
        <?php endif; ?>
        <?php if ($template === 'editorial' || $template === '3d'): ?>
          <a href="#about" class="hero-scroll-cue"><span class="scroll-line"></span><span>Explore the work</span></a><div class="hero-index">PORTFOLIO <span>— <?= date('Y') ?></span></div>
        <?php endif; ?>
      </div>
    </section>

    <section id="about" class="portfolio-content-section portfolio-about section-gradient">
      <div class="portfolio-content-inner">
        <p class="hero-eyebrow">A little about me</p>
        <h2>About</h2>
        <div class="glass-card rounded-3xl p-4 p-md-5 mt-4">
          <?php if ($bio !== ''): ?><p class="lead mb-0"><?= nl2br($escape($bio)) ?></p><?php else: ?><p class="lead mb-0">More information about <?= $escape($name) ?> will be added here.</p><?php endif; ?>
        </div>
      </div>
    </section>

    <section id="projects" class="portfolio-content-section">
      <div class="portfolio-content-inner">
        <p class="hero-eyebrow">Selected work</p>
        <h2>Projects</h2>
        <p class="text-secondary mb-4">A selection of projects and work.</p>
        <?php if ($projects === []): ?>
          <div class="portfolio-empty">Projects will appear here as they are added to this portfolio.</div>
        <?php else: ?>
          <div class="portfolio-project-grid mt-4">
            <?php foreach ($projects as $project): ?>
              <article class="portfolio-project-card glass-card reveal-on-scroll">
                <p class="hero-eyebrow mb-2">PROJECT</p>
                <h3><?= $escape($project['title'] ?? '') ?></h3>
                <?php if (!empty($project['summary'])): ?><p><?= nl2br($escape($project['summary'])) ?></p><?php endif; ?>
                <?php if (!empty($project['project_url'])): ?><a href="<?= $escape($project['project_url']) ?>" target="_blank" rel="noopener noreferrer">View project <span aria-hidden="true">↗</span></a><?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($email !== ''): ?>
      <section id="contact" class="portfolio-content-section portfolio-contact">
        <div class="portfolio-content-inner">
          <p class="hero-eyebrow">Get in touch</p>
          <h2>Have a project in mind?</h2>
          <p class="lead mt-3">Contact <?= $escape($name) ?> to discuss a project or opportunity.</p>
          <a class="hero-button hero-button-primary mt-3" href="mailto:<?= $escape($email) ?>">Email <?= $escape($name) ?> <span aria-hidden="true">↗</span></a>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer class="portfolio-footer">© <?= date('Y') ?> <?= $escape($name) ?><?= $location !== '' ? ' · ' . $escape($location) : '' ?></footer>
  <script>
    (function () {
      var button = document.getElementById('hamburger-menu');
      var menu = document.getElementById('nav-menu');
      if (!button || !menu) return;
      button.addEventListener('click', function () {
        var open = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', open ? 'false' : 'true');
        menu.classList.toggle('is-open', !open);
      });
    }());
  </script>
  <?php if ($template === '3d'): ?><script type="module" src="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-3d.js') ?>"></script><?php endif; ?>
</body>
</html>
