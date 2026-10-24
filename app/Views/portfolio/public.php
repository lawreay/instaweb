<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$name = trim((string) ($portfolio['display_name'] ?? 'Portfolio'));
$headline = trim((string) ($portfolio['headline'] ?? ''));
$bio = trim((string) ($portfolio['bio'] ?? ''));
$template = (string) ($portfolio['template_key'] ?? 'classic');
$advancedSettings = is_array($advancedSettings ?? null) ? $advancedSettings : [];
$accentColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($advancedSettings['accent_color'] ?? '')) ? $advancedSettings['accent_color'] : '#f97316';
$backgroundColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($advancedSettings['background_color'] ?? '')) ? $advancedSettings['background_color'] : '#ffffff';
$textColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($advancedSettings['text_color'] ?? '')) ? $advancedSettings['text_color'] : '#111827';
$fontFamilies = ['system' => 'system-ui, -apple-system, sans-serif', 'sans-serif' => 'Arial, Helvetica, sans-serif', 'serif' => 'Georgia, serif', 'mono' => 'ui-monospace, monospace'];
$fontFamily = $fontFamilies[(string) ($advancedSettings['font_family'] ?? 'system')] ?? $fontFamilies['system'];
$heroStyle = in_array(($advancedSettings['hero_style'] ?? 'centered'), ['centered', 'split', 'minimal'], true) ? $advancedSettings['hero_style'] : 'centered';
$showProjects = ($advancedSettings['show_projects'] ?? '1') !== '0';
$showContact = ($advancedSettings['show_contact'] ?? '1') !== '0';
$sectionLabels = [
    'experience' => 'Work experience',
    'education' => 'Education',
    'skills' => 'Skills',
    'interests' => 'Interests',
];
$sectionItems = [];
foreach (($advancedItems ?? []) as $item) {
    $sectionKey = (string) ($item['section_key'] ?? '');
    if (isset($sectionLabels[$sectionKey])) {
        $sectionItems[$sectionKey][] = $item;
    }
}
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
  <meta name="description" content="<?= $escape(substr($bio !== '' ? $bio : $headline, 0, 160)) ?>">
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
    body{font-family:<?= $escape($fontFamily) ?>;background-color:<?= $escape($backgroundColor) ?>;color:<?= $escape($textColor) ?>;--portfolio-accent:<?= $escape($accentColor) ?>;min-height:100vh;overflow-x:clip}#portfolio-container{overflow:hidden}.portfolio-nav-logo{width:38px;height:38px;display:grid;place-items:center;border:1px solid currentColor;font-weight:800}
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
    body.portfolio-template-classic .portfolio-hero{min-height:88vh;padding:125px clamp(22px,7vw,100px) 72px;display:flex;align-items:center;background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 55%,#fff7ed 100%);color:#172b3a}
    body.portfolio-template-classic .hero-shell{width:min(100%,1120px);margin:0 auto;position:relative}
    body.portfolio-template-classic .hero-copy{max-width:760px;padding:2rem 0}
    body.portfolio-template-classic .hero-kicker{font-size:.75rem;font-weight:800;letter-spacing:.14em;color:#64748b}
    body.portfolio-template-classic .hero-eyebrow{color:#c2410c;text-transform:uppercase;letter-spacing:.12em;font-size:.8rem;font-weight:800}
    body.portfolio-template-classic .portfolio-hero h1{font-size:clamp(3rem,8vw,6.5rem);line-height:.98;letter-spacing:-.06em;font-weight:850;color:#172b3a;overflow-wrap:anywhere}
    body.portfolio-template-classic .hero-role{font-size:1.1rem;font-weight:700;color:#334155;margin-top:1.5rem}
    body.portfolio-template-classic .hero-summary{max-width:680px;color:#475569;font-size:1.05rem;line-height:1.8}
    body.portfolio-template-classic .hero-actions{display:flex;flex-wrap:wrap;align-items:center;gap:1rem;margin-top:1.8rem}
    body.portfolio-template-classic .hero-button{display:inline-flex;align-items:center;gap:.7rem;padding:.9rem 1.2rem;border:1px solid #172b3a;text-decoration:none;font-weight:750}
    body.portfolio-template-classic .hero-button-primary{background:#172b3a;color:#fff}
    body.portfolio-template-classic .hero-button-secondary{color:#172b3a;background:transparent}
    body.portfolio-template-classic .hero-footnote{margin-top:2rem;color:#64748b;font-size:.8rem;font-weight:700;letter-spacing:.06em}
    body.portfolio-template-classic .hero-footnote-line{display:inline-block;width:2rem;height:2px;margin-right:.6rem;background:#c2410c;vertical-align:middle}
    body.portfolio-hero-style-centered .hero-copy{text-align:center;margin-inline:auto}
    body.portfolio-hero-style-centered .hero-actions,body.portfolio-hero-style-centered .hero-footnote{justify-content:center}
    body.portfolio-template-classic.portfolio-hero-style-split .hero-shell{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(220px,.75fr);align-items:center;gap:clamp(2rem,6vw,5rem)}
    body.portfolio-template-classic.portfolio-hero-style-split .hero-copy{max-width:none;text-align:left;margin:0}
    body.portfolio-template-classic.portfolio-hero-style-split .hero-portrait-wrap{width:min(100%,380px);justify-self:end}
    body.portfolio-template-classic.portfolio-hero-style-split .hero-actions,body.portfolio-template-classic.portfolio-hero-style-split .hero-footnote{justify-content:flex-start}
    body.portfolio-hero-style-minimal .portfolio-hero{min-height:65vh}
    body.portfolio-hero-style-minimal .hero-footnote,body.portfolio-hero-style-minimal .hero-kicker{display:none}
    @media(max-width:760px){body.portfolio-template-classic.portfolio-hero-style-split .hero-shell{grid-template-columns:1fr}body.portfolio-template-classic.portfolio-hero-style-split .hero-portrait-wrap{width:min(100%,300px);justify-self:start;grid-row:1}}
    .hero-eyebrow,.portfolio-project-card a{color:var(--portfolio-accent)!important}
    body.portfolio-template-classic .portfolio-hero{background:linear-gradient(135deg,<?= $escape($backgroundColor) ?> 0%,#e2e8f0 55%,<?= $escape($backgroundColor) ?> 100%)}
    @media(max-width:650px){.portfolio-content-section{padding:3.5rem 1.25rem}.portfolio-hero{min-height:auto}}
  </style>
</head>
<body class="portfolio-template-<?= $escape($template) ?> portfolio-hero-style-<?= $escape($heroStyle) ?>">
  <?php if (!empty($isPreview)): ?>
    <div style="position:relative;z-index:10000;padding:.65rem 1rem;background:#fef3c7;color:#78350f;text-align:center;font:600 13px/1.4 Arial,sans-serif">
      Draft preview · changes are shown from your saved portfolio data.
      <a href="<?= $escape($baseUrl . '/dashboard/websites/portfolio?id=' . (int) $website['id']) ?>" style="margin-left:.6rem;color:#78350f;text-decoration:underline">Back to Portfolio Studio</a>
    </div>
  <?php endif; ?>
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
          <?php foreach ($sectionLabels as $sectionKey => $sectionLabel): ?>
            <?php if (!empty($sectionItems[$sectionKey])): ?><li><a href="#<?= $escape($sectionKey) ?>"><?= $escape($sectionLabel) ?></a></li><?php endif; ?>
          <?php endforeach; ?>
          <?php if ($showProjects): ?><li><a href="#projects">Projects</a></li><?php endif; ?>
          <?php if ($showContact && $email !== ''): ?><li><a href="#contact">Contact</a></li><?php endif; ?>
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
            <?php if ($showProjects): ?><a class="hero-button hero-button-primary" href="#projects">Explore my work <span aria-hidden="true">↘</span></a><?php endif; ?>
            <?php if ($showContact && $email !== ''): ?><a class="hero-text-link hero-button-secondary" href="mailto:<?= $escape($email) ?>">Contact me <span aria-hidden="true">↗</span></a><?php endif; ?>
          </div>
          <?php if ($template === '3d'): ?>
            <div class="hero-proof"><div><strong>Selected work</strong><span><?= count($projects) ?> portfolio projects</span></div><div><strong><?= $escape($location !== '' ? $location : 'Open to opportunities') ?></strong><span>Profile and work</span></div></div>
          <?php else: ?>
            <div class="hero-footnote"><span class="hero-footnote-line"></span><span><?= $escape($location !== '' ? $location : 'Selected work and experience') ?></span></div>
          <?php endif; ?>
        </div>
        <?php if ($template === 'editorial' || $avatarUrl !== '' || $heroStyle === 'split'): ?>
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

    <?php foreach ($sectionLabels as $sectionKey => $sectionLabel): ?>
      <?php if (!empty($sectionItems[$sectionKey])): ?>
        <section id="<?= $escape($sectionKey) ?>" class="portfolio-content-section">
          <div class="portfolio-content-inner">
            <p class="hero-eyebrow">Portfolio</p>
            <h2><?= $escape($sectionLabel) ?></h2>
            <div class="portfolio-project-grid mt-4">
              <?php foreach ($sectionItems[$sectionKey] as $item): ?>
                <?php
                $payload = json_decode((string) ($item['payload'] ?? '{}'), true);
                $payload = is_array($payload) ? $payload : [];
                $itemLink = trim((string) ($payload['link'] ?? ''));
                $itemLinkScheme = strtolower((string) parse_url($itemLink, PHP_URL_SCHEME));
                if (!in_array($itemLinkScheme, ['http', 'https'], true)) {
                    $itemLink = '';
                }
                ?>
                <article class="portfolio-project-card glass-card">
                  <h3><?= $escape($item['title'] ?? '') ?></h3>
                  <?php if (!empty($payload['organisation'])): ?><p class="mb-1"><?= $escape($payload['organisation']) ?></p><?php endif; ?>
                  <?php if (!empty($payload['period'])): ?><p class="mb-2"><?= $escape($payload['period']) ?></p><?php endif; ?>
                  <?php if (!empty($payload['details'])): ?><p><?= nl2br($escape($payload['details'])) ?></p><?php endif; ?>
                  <?php if ($itemLink !== ''): ?><a href="<?= $escape($itemLink) ?>" target="_blank" rel="noopener noreferrer">Learn more <span aria-hidden="true">↗</span></a><?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($showProjects): ?><section id="projects" class="portfolio-content-section">
      <div class="portfolio-content-inner">
        <p class="hero-eyebrow">Selected work</p>
        <h2>Projects</h2>
        <p class="text-secondary mb-4">A selection of projects and work.</p>
        <?php if ($projects === []): ?>
          <div class="portfolio-empty">Projects will appear here as they are added to this portfolio.</div>
        <?php else: ?>
          <div class="portfolio-project-grid mt-4">
            <?php foreach ($projects as $project): ?>
              <article class="portfolio-project-card glass-card">
                <p class="hero-eyebrow mb-2">PROJECT</p>
                <h3><?= $escape($project['title'] ?? '') ?></h3>
                <?php if (!empty($project['summary'])): ?><p><?= nl2br($escape($project['summary'])) ?></p><?php endif; ?>
                <?php if (!empty($project['project_url'])): ?><a href="<?= $escape($project['project_url']) ?>" target="_blank" rel="noopener noreferrer">View project <span aria-hidden="true">↗</span></a><?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section><?php endif; ?>

    <?php if ($showContact && $email !== ''): ?>
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
  <?php if ($template === '3d'): ?>
    <script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.169.0/build/three.module.js"}}</script>
    <script type="module" src="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-3d.js') ?>"></script>
  <?php endif; ?>
</body>
</html>
