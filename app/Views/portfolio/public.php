<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$name = trim((string) ($portfolio['display_name'] ?? 'Portfolio'));
$headline = trim((string) ($portfolio['headline'] ?? ''));
$bio = trim((string) ($portfolio['bio'] ?? ''));
$template = (string) ($portfolio['template_key'] ?? 'classic');
$availableTemplates = ['classic', 'editorial', '3d', 'minimal', 'noir', 'aurora', 'studio', 'orbit', 'prism', 'archive'];
$reactTemplates = ['aurora', 'studio', 'orbit', 'prism', 'archive'];
$customTemplate = is_array($customTemplate ?? null) ? $customTemplate : null;
$isCustomTemplate = $customTemplate !== null
    && (string) ($customTemplate['template_key'] ?? '') === $template;
$noirStylesheetPath = dirname(__DIR__, 3) . '/public/assets/portfolio-template/portfolio-noir.css';
$noirStylesheetVersion = is_file($noirStylesheetPath) ? (string) filemtime($noirStylesheetPath) : '1';
$reactStylesheetPath = dirname(__DIR__, 3) . '/public/assets/portfolio-template/react-portfolio.css';
$reactStylesheetVersion = is_file($reactStylesheetPath) ? (string) filemtime($reactStylesheetPath) : '1';
$reactScriptPath = dirname(__DIR__, 3) . '/public/assets/portfolio-template/react-portfolio.js';
$reactScriptVersion = is_file($reactScriptPath) ? (string) filemtime($reactScriptPath) : '1';
if (!$isCustomTemplate && !in_array($template, $availableTemplates, true)) {
    $template = 'classic';
}
$advancedSettings = is_array($advancedSettings ?? null) ? $advancedSettings : [];
$accentColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($advancedSettings['accent_color'] ?? '')) ? $advancedSettings['accent_color'] : '#f97316';
$backgroundColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($advancedSettings['background_color'] ?? '')) ? $advancedSettings['background_color'] : '#ffffff';
$textColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($advancedSettings['text_color'] ?? '')) ? $advancedSettings['text_color'] : '#111827';
$fontFamilies = ['system' => 'system-ui, -apple-system, sans-serif', 'sans-serif' => 'Arial, Helvetica, sans-serif', 'serif' => 'Georgia, serif', 'mono' => 'ui-monospace, monospace'];
$fontFamily = $fontFamilies[(string) ($advancedSettings['font_family'] ?? 'system')] ?? $fontFamilies['system'];
$heroStyle = (string) ($advancedSettings['hero_style'] ?? 'centered');
if (!in_array($heroStyle, ['centered', 'split', 'minimal'], true)) {
    $heroStyle = 'centered';
}
$showProjects = ($advancedSettings['show_projects'] ?? '1') !== '0';
$showContact = ($advancedSettings['show_contact'] ?? '1') !== '0';
// Keep this list aligned with AdvancedPortfolioController::SECTIONS.
$sectionLabels = [
    'goals' => 'Goals',
    'interests' => 'Interests',
    'challenges' => 'Challenges',
    'experience' => 'Work experience',
    'education' => 'Education',
    'skills' => 'Skills',
    'news' => 'News and updates',
    'partnerships' => 'Partnerships',
    'cv' => 'CV / Resume',
];
$sectionItems = [];
foreach (($advancedItems ?? []) as $item) {
    $sectionKey = (string) ($item['section_key'] ?? '');
    if (isset($sectionLabels[$sectionKey]) && !empty($item['is_enabled'])) {
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
$socialLinks = json_decode((string) ($portfolio['social_links'] ?? '{}'), true);
$socialLinks = is_array($socialLinks) ? $socialLinks : [];
$socialLabels = ['github' => 'GitHub', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'website' => 'Website'];
$safeSocialLinks = [];
foreach ($socialLabels as $platform => $label) {
    $url = trim((string) ($socialLinks[$platform] ?? ''));
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)) {
        $safeSocialLinks[$platform] = ['label' => $label, 'url' => $url];
    }
}

// Treat persisted project URLs as untrusted too. Older records may predate
// controller validation, so sanitize links at the public rendering boundary.
$safeProjects = [];
foreach (($projects ?? []) as $project) {
    $project = is_array($project) ? $project : [];
    $projectUrl = trim((string) ($project['project_url'] ?? ''));
    $projectScheme = strtolower((string) parse_url($projectUrl, PHP_URL_SCHEME));
    if ($projectUrl !== '' && (!filter_var($projectUrl, FILTER_VALIDATE_URL)
        || !in_array($projectScheme, ['http', 'https'], true))) {
        $project['project_url'] = '';
    }
    $projectImage = trim((string) ($project['image_path'] ?? ''));
    if (preg_match('#^assets/portfolio-template/demo/john-doe/[a-z0-9-]+\.jpg$#i', $projectImage)) {
        $project['image_url'] = $baseUrl . '/' . $projectImage;
    } else {
        $project['image_url'] = '';
    }
    $safeProjects[] = $project;
}

// Navigation is intentionally limited to the five most useful destinations.
// Missing content falls through to the next available priority instead of
// leaving an empty position in the menu.
$navigationPriority = ['about', 'experience', 'projects', 'skills', 'contact', 'education', 'goals', 'interests', 'challenges', 'partnerships', 'news', 'cv'];
$navigationItemsByKey = [
    'about' => ['href' => '#about', 'label' => 'About'],
];
$navigationLabels = ['experience' => 'Experience'];
foreach ($sectionLabels as $sectionKey => $sectionLabel) {
    if (!empty($sectionItems[$sectionKey])) {
        $navigationItemsByKey[$sectionKey] = [
            'href' => '#' . $sectionKey,
            'label' => $navigationLabels[$sectionKey] ?? $sectionLabel,
        ];
    }
}
if ($showProjects && $safeProjects !== []) {
    $navigationItemsByKey['projects'] = ['href' => '#projects', 'label' => 'Projects'];
}
if ($showContact && ($email !== '' || $safeSocialLinks !== [])) {
    $navigationItemsByKey['contact'] = ['href' => '#contact', 'label' => 'Contact'];
}
$navigationItems = [];
foreach ($navigationPriority as $navigationKey) {
    if (isset($navigationItemsByKey[$navigationKey])) {
        $navigationItems[] = $navigationItemsByKey[$navigationKey];
    }
}
$navigationItems = array_slice($navigationItems, 0, 5);

// Custom templates receive escaped profile values and pre-rendered content
// blocks. The HTML source itself is stored only after admin-side safety checks.
$customSectionsMarkup = '';
foreach ($sectionLabels as $sectionKey => $sectionLabel) {
    if (empty($sectionItems[$sectionKey])) {
        continue;
    }
    $cards = '';
    foreach ($sectionItems[$sectionKey] as $item) {
        $payload = json_decode((string) ($item['payload'] ?? '{}'), true);
        $payload = is_array($payload) ? $payload : [];
        $link = trim((string) ($payload['link'] ?? ''));
        if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($link, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            $link = '';
        }
        $meta = trim((string) ($payload['organisation'] ?? '') . (!empty($payload['organisation']) && !empty($payload['period']) ? ' · ' : '') . (string) ($payload['period'] ?? ''));
        $cards .= '<article class="custom-card"><h3>' . $escape($item['title'] ?? '') . '</h3>'
            . ($meta !== '' ? '<p>' . $escape($meta) . '</p>' : '')
            . (!empty($payload['details']) ? '<p>' . nl2br($escape($payload['details'])) . '</p>' : '')
            . ($link !== '' ? '<p><a href="' . $escape($link) . '" target="_blank" rel="noopener noreferrer">Learn more</a></p>' : '')
            . '</article>';
    }
    $customSectionsMarkup .= '<section id="' . $escape($sectionKey) . '" class="custom-section"><h2>' . $escape($sectionLabel) . '</h2><div class="custom-cards">' . $cards . '</div></section>';
}

$customProjectsMarkup = '';
if ($showProjects) {
    $projectCards = '';
    foreach ($safeProjects as $project) {
        $projectCards .= '<article class="custom-card">'
            . (!empty($project['image_url']) ? '<img class="custom-project-image" src="' . $escape($project['image_url']) . '" alt="" loading="lazy">' : '')
            . '<h3>' . $escape($project['title'] ?? '') . '</h3>'
            . (!empty($project['summary']) ? '<p>' . nl2br($escape($project['summary'])) . '</p>' : '')
            . (!empty($project['project_url']) ? '<p><a href="' . $escape($project['project_url']) . '" target="_blank" rel="noopener noreferrer">View project</a></p>' : '')
            . '</article>';
    }
    $customProjectsMarkup = '<section id="projects" class="custom-section"><h2>Projects</h2><div class="custom-cards">'
        . ($projectCards !== '' ? $projectCards : '<p>Projects will appear here as they are added.</p>')
        . '</div></section>';
}

$customContactMarkup = '';
if ($showContact && ($email !== '' || $safeSocialLinks !== [])) {
    $contactLinks = $email !== '' ? '<p><a class="custom-button" href="mailto:' . $escape($email) . '">Email ' . $escape($name) . '</a></p>' : '';
    foreach ($safeSocialLinks as $social) {
        $contactLinks .= '<a href="' . $escape($social['url']) . '" target="_blank" rel="noopener noreferrer">' . $escape($social['label']) . '</a> ';
    }
    $customContactMarkup = '<section id="contact" class="custom-section"><h2>Get in touch</h2><p>Contact ' . $escape($name) . ' to discuss a project or opportunity.</p>' . $contactLinks . '</section>';
}

$customAvatarMarkup = $avatarUrl !== ''
    ? '<img class="custom-avatar" src="' . $escape($avatarUrl) . '" alt="' . $escape($name) . '">'
    : '';
$customTemplateHtml = $isCustomTemplate ? (string) ($customTemplate['html_code'] ?? '') : '';
$customTemplateCss = $isCustomTemplate ? (string) ($customTemplate['css_code'] ?? '') : '';
$customTemplateOutput = $isCustomTemplate ? strtr($customTemplateHtml, [
    '{{name}}' => $escape($name),
    '{{headline}}' => $escape($headline),
    '{{bio}}' => nl2br($escape($bio)),
    '{{location}}' => $escape($location),
    '{{avatar}}' => $customAvatarMarkup,
    '{{sections}}' => $customSectionsMarkup,
    '{{projects}}' => $customProjectsMarkup,
    '{{contact}}' => $customContactMarkup,
    '{{year}}' => date('Y'),
]) : '';

// Noir uses local editorial visuals by default. A portfolio owner may safely
// replace a section image from the Advanced Editor; the override is accepted
// only from that website's dedicated Noir upload directory.
$noirImageAssets = [
    'goals' => 'ideas.svg', 'interests' => 'ideas.svg', 'challenges' => 'ideas.svg',
    'experience' => 'journey.svg', 'education' => 'journey.svg', 'skills' => 'practice.svg',
    'news' => 'work.svg', 'partnerships' => 'work.svg', 'cv' => 'practice.svg', 'projects' => 'work.svg',
];
$noirImageUrls = [];
foreach ($noirImageAssets as $sectionKey => $filename) {
    $noirImageUrls[$sectionKey] = $baseUrl . '/assets/portfolio-template/noir-images/' . $filename;
}
foreach (($noirSectionImages ?? []) as $upload) {
    $path = (string) ($upload['storage_key'] ?? '');
    if (preg_match('#^uploads/portfolios/' . (int) ($website['id'] ?? 0) . '/noir/section-([a-z]+)-[a-f0-9]{32}\.(jpg|png|webp)$#i', $path, $matches)
        && array_key_exists($matches[1], $noirImageUrls)) {
        $noirImageUrls[$matches[1]] = $baseUrl . '/' . $path;
    }
}

$reactSections = [];
foreach ($sectionLabels as $sectionKey => $sectionLabel) {
    if (empty($sectionItems[$sectionKey])) continue;
    $entries = [];
    foreach ($sectionItems[$sectionKey] as $item) {
        $payload = json_decode((string) ($item['payload'] ?? '{}'), true);
        $payload = is_array($payload) ? $payload : [];
        $link = trim((string) ($payload['link'] ?? ''));
        if (!filter_var($link, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($link, PHP_URL_SCHEME)), ['http', 'https'], true)) $link = '';
        $cvPath = (string) ($payload['file_path'] ?? '');
        if ($cvPath !== '' && !preg_match('#^uploads/portfolios/' . (int) ($website['id'] ?? 0) . '/cv/[a-f0-9]{32}\\.(pdf|docx?)$#i', $cvPath)) $cvPath = '';
        $entries[] = ['title' => (string) ($item['title'] ?? ''), 'details' => (string) ($payload['details'] ?? ''), 'organisation' => (string) ($payload['organisation'] ?? ''), 'period' => (string) ($payload['period'] ?? ''), 'link' => $link, 'fileUrl' => $cvPath !== '' ? '/' . $cvPath : '', 'fileName' => (string) ($payload['file_name'] ?? '')];
    }
    $reactSections[] = ['key' => $sectionKey, 'label' => $sectionLabel, 'items' => $entries];
}
$reactPortfolioData = [
    'template' => $template, 'name' => $name, 'headline' => $headline, 'bio' => $bio, 'initials' => $initials ?? strtoupper(substr($name, 0, 1)),
    'avatar' => $avatarUrl, 'email' => $email, 'location' => $location, 'accent' => $accentColor, 'background' => $backgroundColor,
    'textColor' => $textColor, 'fontFamily' => $fontFamily, 'heroStyle' => $heroStyle,
    'showProjects' => $showProjects, 'showContact' => $showContact, 'projects' => $safeProjects, 'sections' => $reactSections,
    'socials' => array_values($safeSocialLinks),
];
$initials = strtoupper(substr($name, 0, 1));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $escape($name) ?><?= $headline !== '' ? ' | ' . $escape($headline) : '' ?></title>
  <meta name="description" content="<?= $escape(substr($bio !== '' ? $bio : $headline, 0, 160)) ?>">
  <meta name="robots" content="<?= !empty($isPreview) ? 'noindex,nofollow' : 'index,follow' ?>">
  <meta name="theme-color" content="<?= in_array($template, ['3d', 'noir'], true) ? '#080b0e' : '#f4f0e7' ?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/styles.css') ?>">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio.css') ?>">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/navbar.css') ?>">
  <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/images.css') ?>">
  <?php if ($template === 'editorial'): ?>
    <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-editorial.css') ?>">
  <?php elseif ($template === '3d'): ?>
    <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-3d.css') ?>">
  <?php elseif ($template === 'minimal'): ?>
    <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-minimal.css') ?>">
  <?php endif; ?>
  <?php if (in_array($template, $reactTemplates, true)): ?>
    <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/react-portfolio.css?v=' . $reactStylesheetVersion) ?>">
  <?php endif; ?>
  <style>
    body{font-family:<?= $escape($fontFamily) ?>;background-color:<?= $escape($backgroundColor) ?>;color:<?= $escape($textColor) ?>;--portfolio-accent:<?= $escape($accentColor) ?>;min-height:100vh;overflow-x:clip}#portfolio-container{overflow:hidden}.portfolio-nav-logo{width:38px;height:38px;display:grid;place-items:center;border:1px solid currentColor;font-weight:800}
    .portfolio-avatar{display:grid;place-items:center;overflow:hidden;width:100%;aspect-ratio:4/5;background:transparent;color:var(--portfolio-accent);font-size:clamp(4rem,12vw,8rem);font-weight:800}
    .portfolio-avatar img{width:100%;height:100%;object-fit:cover;object-position:63% center;background:transparent}.hero-photo-frame{padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important}.portfolio-content-section{padding:5rem clamp(1.25rem,6vw,6rem)}
    .portfolio-content-inner{max-width:1120px;margin:0 auto}.portfolio-content-section h2{font-size:clamp(2rem,5vw,3.5rem);font-weight:800;letter-spacing:-.04em}
    .portfolio-project-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,270px),1fr));gap:1.2rem}
    .portfolio-project-card{height:100%;padding:1.6rem;border:1px solid rgba(100,116,139,.25);background:rgba(255,255,255,.78);box-shadow:0 14px 35px rgba(15,23,42,.06)}.portfolio-project-image{display:block;width:calc(100% + 3.2rem);height:11rem;object-fit:cover;margin:-1.6rem -1.6rem 1.3rem;border-bottom:1px solid rgba(100,116,139,.18)}
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
  <?php if ($isCustomTemplate && $customTemplateCss !== ''): ?>
    <style id="custom-portfolio-template-css"><?= $customTemplateCss ?></style>
  <?php endif; ?>
  <?php if ($template === 'noir'): ?>
    <link rel="stylesheet" href="<?= $escape($baseUrl . '/assets/portfolio-template/portfolio-noir.css?v=' . $noirStylesheetVersion) ?>">
  <?php endif; ?>
</head>
<body class="portfolio-template-<?= $escape($template) ?> portfolio-hero-style-<?= $escape($heroStyle) ?><?= in_array($template, $reactTemplates, true) ? ' react-portfolio-template' : '' ?>">
  <?php if (!empty($isPreview)): ?>
    <div style="position:relative;z-index:10000;padding:.65rem 1rem;background:#fef3c7;color:#78350f;text-align:center;font:600 13px/1.4 Arial,sans-serif">
      Draft preview · changes are shown from your saved portfolio data.
      <a href="<?= $escape($baseUrl . '/dashboard/websites/portfolio?id=' . (int) $website['id']) ?>" style="margin-left:.6rem;color:#78350f;text-decoration:underline">Back to Portfolio Studio</a>
    </div>
  <?php endif; ?>
  <?php if ($isCustomTemplate): ?>
    <?= $customTemplateOutput ?>
  <?php elseif ($template === 'noir'): ?>
    <header class="noir-site-header">
      <a class="noir-wordmark" href="#home" aria-label="<?= $escape($name) ?> home"><span><?= $escape($initials) ?></span><strong><?= $escape($name) ?></strong></a>
      <nav class="noir-nav" aria-label="Portfolio navigation">
        <?php foreach ($navigationItems as $navigationItem): ?><a href="<?= $escape($navigationItem['href']) ?>"><?= $escape($navigationItem['label']) ?></a><?php endforeach; ?>
      </nav>
    </header>

    <main class="noir-page">
      <section id="home" class="noir-hero<?= $avatarUrl === '' ? ' noir-hero--without-photo' : '' ?>" aria-labelledby="noir-hero-name">
        <div class="noir-hero-copy">
          <p class="noir-overline">Independent developer<?= $location !== '' ? ' / ' . $escape($location) : '' ?></p>
          <h1 id="noir-hero-name"><?= $escape($name) ?></h1>
          <?php if ($headline !== ''): ?><p class="noir-role"><?= $escape($headline) ?></p><?php endif; ?>
          <p class="noir-intro"><?= nl2br($escape($bio !== '' ? $bio : 'Practical digital systems, selected work, and ways to get in touch.')) ?></p>
          <div class="noir-actions">
            <?php if ($showProjects): ?><a class="noir-button noir-button--solid" href="#projects">View my work <span aria-hidden="true">&darr;</span></a><?php endif; ?>
            <a class="noir-button noir-button--quiet" href="#about">About me <span aria-hidden="true">&rarr;</span></a>
          </div>
          <dl class="noir-hero-facts">
            <div><dt>Based in</dt><dd><?= $escape($location !== '' ? $location : 'Available remotely') ?></dd></div>
            <div><dt>Selected work</dt><dd><?= count($safeProjects) ?> projects</dd></div>
          </dl>
        </div>
        <?php if ($avatarUrl !== ''): ?>
          <figure class="noir-portrait">
            <img src="<?= $escape($avatarUrl) ?>" alt="<?= $escape($name) ?>">
            <figcaption><span>Portfolio</span><span><?= date('Y') ?></span></figcaption>
          </figure>
        <?php endif; ?>
      </section>

      <section class="noir-impact" aria-labelledby="noir-impact-title">
        <p class="noir-overline">Selected impact</p>
        <h2 id="noir-impact-title">Evidence, not noise.</h2>
        <dl>
          <div><dt><?= count($safeProjects) ?></dt><dd>systems and platforms</dd></div>
          <div><dt><?= count($sectionItems['experience'] ?? []) ?></dt><dd>active professional roles</dd></div>
          <div><dt><?= count($sectionItems['skills'] ?? []) ?></dt><dd>technical practice areas</dd></div>
        </dl>
      </section>

      <section id="about" class="noir-about" aria-labelledby="noir-about-title">
        <p class="noir-overline">01 / About</p>
        <div><h2 id="noir-about-title">Useful things.<br>Built to last.</h2></div>
        <div class="noir-about-copy"><p><?= nl2br($escape($bio !== '' ? $bio : 'More information about ' . $name . ' will be added here.')) ?></p><?php if ($email !== ''): ?><a href="mailto:<?= $escape($email) ?>">Start a conversation <span aria-hidden="true">&nearr;</span></a><?php endif; ?></div>
      </section>

      <?php foreach ($sectionLabels as $sectionKey => $sectionLabel): ?>
        <?php if (empty($sectionItems[$sectionKey])) continue; ?>
        <section id="<?= $escape($sectionKey) ?>" class="noir-section noir-section--<?= $escape($sectionKey) ?>" aria-labelledby="noir-<?= $escape($sectionKey) ?>-title">
          <header class="noir-section-heading"><p class="noir-overline">Portfolio / <?= $escape(str_pad((string) (array_search($sectionKey, array_keys($sectionLabels), true) + 2), 2, '0', STR_PAD_LEFT)) ?></p><h2 id="noir-<?= $escape($sectionKey) ?>-title"><?= $escape($sectionLabel) ?></h2></header>
          <figure class="noir-section-image"><img src="<?= $escape($noirImageUrls[$sectionKey] ?? $noirImageUrls['projects']) ?>" alt="" loading="lazy" decoding="async"></figure>
          <?php if ($sectionKey === 'experience'): ?>
            <ol class="noir-timeline">
              <?php foreach ($sectionItems[$sectionKey] as $index => $item): $payload = json_decode((string) ($item['payload'] ?? '{}'), true); $payload = is_array($payload) ? $payload : []; ?>
                <li><span class="noir-item-number"><?= $escape(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><article><div class="noir-item-meta"><span><?= $escape($payload['organisation'] ?? '') ?></span><span><?= $escape($payload['period'] ?? '') ?></span></div><h3><?= $escape($item['title'] ?? '') ?></h3><?php if (!empty($payload['details'])): ?><p><?= nl2br($escape($payload['details'])) ?></p><?php endif; ?></article></li>
              <?php endforeach; ?>
            </ol>
          <?php elseif ($sectionKey === 'skills'): ?>
            <ul class="noir-skills" role="list">
              <?php foreach ($sectionItems[$sectionKey] as $index => $item): $payload = json_decode((string) ($item['payload'] ?? '{}'), true); $payload = is_array($payload) ? $payload : []; ?>
                <li><span><?= $escape(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><div><h3><?= $escape($item['title'] ?? '') ?></h3><?php if (!empty($payload['details'])): ?><p><?= nl2br($escape($payload['details'])) ?></p><?php endif; ?></div><small><?= $escape($payload['proficiency'] ?? $payload['category'] ?? '') ?></small></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="noir-editorial-list">
              <?php foreach ($sectionItems[$sectionKey] as $index => $item): $payload = json_decode((string) ($item['payload'] ?? '{}'), true); $payload = is_array($payload) ? $payload : []; $itemLink = trim((string) ($payload['link'] ?? '')); $itemScheme = strtolower((string) parse_url($itemLink, PHP_URL_SCHEME)); if (!filter_var($itemLink, FILTER_VALIDATE_URL) || !in_array($itemScheme, ['http', 'https'], true)) $itemLink = ''; ?>
                <article><span><?= $escape(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><div><h3><?= $escape($item['title'] ?? '') ?></h3><?php if (!empty($payload['organisation']) || !empty($payload['period'])): ?><p class="noir-item-meta"><?= $escape(trim((string) ($payload['organisation'] ?? '') . (!empty($payload['organisation']) && !empty($payload['period']) ? ' / ' : '') . (string) ($payload['period'] ?? ''))) ?></p><?php endif; ?><?php if (!empty($payload['details'])): ?><p><?= nl2br($escape($payload['details'])) ?></p><?php endif; ?></div><?php if ($itemLink !== ''): ?><a href="<?= $escape($itemLink) ?>" target="_blank" rel="noopener noreferrer">Open <span aria-hidden="true">&nearr;</span></a><?php endif; ?></article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>

      <?php if ($showProjects): ?><section id="projects" class="noir-projects" aria-labelledby="noir-projects-title">
        <header class="noir-section-heading"><p class="noir-overline">Selected work / 07</p><h2 id="noir-projects-title">Projects with a job to do.</h2><p>A selection of systems, platforms, and useful digital tools.</p></header>
        <figure class="noir-section-image noir-section-image--projects"><img src="<?= $escape($noirImageUrls['projects']) ?>" alt="" loading="lazy" decoding="async"></figure>
        <?php if ($safeProjects === []): ?>
          <p class="noir-empty">Projects will appear here as they are added to this portfolio.</p>
        <?php else: ?>
          <?php $featuredProjects = array_slice($safeProjects, 0, 4); $otherProjects = array_slice($safeProjects, 4); ?>
          <div class="noir-project-stack">
            <div class="noir-project-grid noir-project-grid--featured">
              <?php foreach ($featuredProjects as $index => $project): ?><article class="noir-project noir-project--featured"><span class="noir-project-index"><?= $escape(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span><div><h3><?= $escape($project['title'] ?? '') ?></h3><?php if (!empty($project['summary'])): ?><p><?= nl2br($escape($project['summary'])) ?></p><?php endif; ?></div><?php if (!empty($project['project_url'])): ?><a href="<?= $escape($project['project_url']) ?>" target="_blank" rel="noopener noreferrer">View project <span aria-hidden="true">&nearr;</span></a><?php endif; ?></article><?php endforeach; ?>
            </div>
            <?php if ($otherProjects !== []): ?><div class="noir-other-projects"><p class="noir-overline">Other projects</p><div class="noir-project-grid noir-project-grid--archive"><?php foreach ($otherProjects as $index => $project): ?><article class="noir-project noir-project--archive"><span class="noir-project-index"><?= $escape(str_pad((string) ($index + 5), 2, '0', STR_PAD_LEFT)) ?></span><div><h3><?= $escape($project['title'] ?? '') ?></h3><?php if (!empty($project['summary'])): ?><p><?= nl2br($escape($project['summary'])) ?></p><?php endif; ?></div><?php if (!empty($project['project_url'])): ?><a href="<?= $escape($project['project_url']) ?>" target="_blank" rel="noopener noreferrer">View project <span aria-hidden="true">&nearr;</span></a><?php endif; ?></article><?php endforeach; ?></div></div><?php endif; ?>
          </div>
        <?php endif; ?>
      </section><?php endif; ?>

      <?php if ($showContact && ($email !== '' || $safeSocialLinks !== [])): ?><section id="contact" class="noir-contact" aria-labelledby="noir-contact-title"><p class="noir-overline">Contact / 08</p><h2 id="noir-contact-title">Let’s build something useful together.</h2><div><p>Need a website, system, learning platform, automation solution, or technical support? Start with a conversation.</p><?php if ($email !== ''): ?><a class="noir-contact-email" href="mailto:<?= $escape($email) ?>">Email <?= $escape($name) ?> <span aria-hidden="true">&nearr;</span></a><?php endif; ?><nav aria-label="Social profiles"><?php foreach ($safeSocialLinks as $social): ?><a href="<?= $escape($social['url']) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($social['label']) ?> <span aria-hidden="true">&nearr;</span></a><?php endforeach; ?></nav></div></section><?php endif; ?>
    </main>
  <?php else: ?>
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
          <?php foreach ($navigationItems as $navigationItem): ?><li><a href="<?= $escape($navigationItem['href']) ?>"><?= $escape($navigationItem['label']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
    </nav>
  </header>

  <?php if (in_array($template, $reactTemplates, true)): ?>
    <main id="react-portfolio-root" data-portfolio="<?= $escape(json_encode($reactPortfolioData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?: '{}') ?>"></main>
  <?php endif; ?>

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
                if ($itemLink !== '' && (!filter_var($itemLink, FILTER_VALIDATE_URL)
                    || !in_array($itemLinkScheme, ['http', 'https'], true))) {
                    $itemLink = '';
                }
                ?>
                <article class="portfolio-project-card glass-card">
                  <h3><?= $escape($item['title'] ?? '') ?></h3>
                  <?php if (!empty($payload['organisation'])): ?><p class="mb-1"><?= $escape($payload['organisation']) ?></p><?php endif; ?>
                  <?php if (!empty($payload['period'])): ?><p class="mb-2"><?= $escape($payload['period']) ?></p><?php endif; ?>
                  <?php if (!empty($payload['details'])): ?><p><?= nl2br($escape($payload['details'])) ?></p><?php endif; ?>
                  <?php if ($itemLink !== ''): ?><a href="<?= $escape($itemLink) ?>" target="_blank" rel="noopener noreferrer">Learn more <span aria-hidden="true">↗</span></a><?php endif; ?>
                  <?php $cvPath = (string) ($payload['file_path'] ?? ''); if ($sectionKey === 'cv' && $cvPath !== '' && preg_match('#^uploads/portfolios/' . (int) ($website['id'] ?? 0) . '/cv/[a-f0-9]{32}\\.(pdf|docx?)$#i', $cvPath)): ?><p><a href="<?= $escape('/' . $cvPath) ?>" download><?= $escape($payload['file_name'] ?? 'Download CV') ?> · Download</a></p><?php endif; ?>
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
            <?php foreach ($safeProjects as $project): ?>
              <article class="portfolio-project-card glass-card">
                <?php if (!empty($project['image_url'])): ?><img class="portfolio-project-image" src="<?= $escape($project['image_url']) ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
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

    <?php if ($showContact && ($email !== '' || $safeSocialLinks !== [])): ?>
      <section id="contact" class="portfolio-content-section portfolio-contact">
        <div class="portfolio-content-inner">
          <p class="hero-eyebrow">Get in touch</p>
          <h2>Have a project in mind?</h2>
          <p class="lead mt-3">Contact <?= $escape($name) ?> to discuss a project or opportunity.</p>
          <?php if ($email !== ''): ?><a class="hero-button hero-button-primary mt-3" href="mailto:<?= $escape($email) ?>">Email <?= $escape($name) ?> <span aria-hidden="true">↗</span></a><?php endif; ?>
          <?php if ($safeSocialLinks !== []): ?>
            <nav class="portfolio-social-links mt-4 d-flex flex-wrap gap-3" aria-label="Social profiles">
              <?php foreach ($safeSocialLinks as $social): ?>
                <a href="<?= $escape($social['url']) ?>" target="_blank" rel="noopener noreferrer"><?= $escape($social['label']) ?> <span aria-hidden="true">↗</span></a>
              <?php endforeach; ?>
            </nav>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>
  <?php endif; ?>

  <?php if (!$isCustomTemplate): ?><footer class="portfolio-footer">© <?= date('Y') ?> <?= $escape($name) ?><?= $location !== '' ? ' · ' . $escape($location) : '' ?></footer><?php endif; ?>
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
  <?php if (in_array($template, $reactTemplates, true)): ?>
    <script type="module" src="<?= $escape($baseUrl . '/assets/portfolio-template/react-portfolio.js?v=' . $reactScriptVersion) ?>"></script>
  <?php endif; ?>
</body>
</html>
