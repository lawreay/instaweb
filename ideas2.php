<?php
define('PORTFOLIO_PUBLIC_PAGE', true);
require_once __DIR__ . '/config.php';

$user_id = get_public_user_id('admin');
if (!$user_id) {
    http_response_code(500);
    exit('Portfolio owner not found. Run seed_portfolio.php first.');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['public_action'])) {
    $public_action = $_POST['public_action'];
    $redirect = strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?') . '?sent=1#engage';

    if ($public_action === 'booking') {
        $name = escape_input($_POST['visitor_name'] ?? '');
        $email_post = escape_input($_POST['visitor_email'] ?? '');
        $phone = escape_input($_POST['visitor_phone'] ?? '');
        $service = escape_input($_POST['service'] ?? '');
        $preferred_date = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;
        $message = escape_input($_POST['message'] ?? '');
        $stmt = $mysqli->prepare("INSERT INTO portfolio_bookings (user_id, visitor_name, visitor_email, visitor_phone, service, preferred_date, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssss", $user_id, $name, $email_post, $phone, $service, $preferred_date, $message);
        $stmt->execute();
        header("Location: " . $redirect);
        exit();
    }

    if ($public_action === 'request') {
        $name = escape_input($_POST['visitor_name'] ?? '');
        $email_post = escape_input($_POST['visitor_email'] ?? '');
        $request_type = escape_input($_POST['request_type'] ?? '');
        $message = escape_input($_POST['message'] ?? '');
        $stmt = $mysqli->prepare("INSERT INTO portfolio_requests (user_id, visitor_name, visitor_email, request_type, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $name, $email_post, $request_type, $message);
        $stmt->execute();
        header("Location: " . $redirect);
        exit();
    }

    if ($public_action === 'comment') {
        $name = escape_input($_POST['visitor_name'] ?? '');
        $email_post = escape_input($_POST['visitor_email'] ?? '');
        $comment = escape_input($_POST['comment'] ?? '');
        $stmt = $mysqli->prepare("INSERT INTO portfolio_comments (user_id, visitor_name, visitor_email, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $name, $email_post, $comment);
        $stmt->execute();
        header("Location: " . $redirect);
        exit();
    }
}

log_site_visit($user_id);

$settings = get_user_settings($user_id) ?: [];
$projects = get_user_projects($user_id);
$goals = get_goals($user_id);
$interests = get_interests($user_id);
$challenges = get_sections_by_type($user_id, 'Challenges');
$partnerships = get_sections_by_type($user_id, 'Partnerships');
$experience = get_experience($user_id);
$education = get_education($user_id);
$skills = get_skills($user_id);
$news_items = !empty($settings['enable_news']) ? get_news($user_id, true) : [];
$popups = get_popups($user_id, true);
$section_backgrounds = get_section_backgrounds($user_id);

$skill_groups = [];
foreach ($skills as $skill) {
    $skill_groups[$skill['category']][] = $skill;
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) && $settings[$key] !== '' ? $settings[$key] : $default;
}

function safe_color($value, $default) {
    return preg_match('/^#[0-9a-fA-F]{6}$/', (string)$value) ? $value : $default;
}

function icon_name($value, $default = 'circle') {
    $icon = strtolower((string)($value ?: $default));
    $icon = preg_replace('/[^a-z0-9-]/', '', $icon);
    return $icon ?: $default;
}

function is_image_icon($value) {
    $value = trim((string)$value);
    if ($value === '') return false;
    return (bool)preg_match('/^(https?:\/\/|\/|uploads\/)/i', $value)
        || (bool)preg_match('/\.(svg|png|jpe?g|gif|webp|ico)(\?.*)?$/i', $value);
}

function icon_markup($value, $default, $color, $size = 24) {
    $size = max(12, min(64, (int)$size));
    if (is_image_icon($value)) {
        return '<img src="' . e($value) . '" alt="" style="width:' . $size . 'px; height:' . $size . 'px; object-fit: contain;">';
    }

    return '<i data-lucide="' . e(icon_name($value, $default)) . '" style="color: ' . e($color) . '; width:' . $size . 'px; height:' . $size . 'px;"></i>';
}

function color_to_rgba($hex, $alpha = 0.15) {
    $hex = ltrim(safe_color($hex, '#6366f1'), '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r,$g,$b,$alpha)";
}

function section_style($key, $backgrounds, $fallback = '') {
    $bg = $backgrounds[$key] ?? null;
    if (!$bg || empty($bg['background_image'])) return $fallback;
    $opacity = isset($bg['overlay_opacity']) ? (float)$bg['overlay_opacity'] : 0.80;
    $opacity = max(0, min(0.95, $opacity));
    $image = htmlspecialchars($bg['background_image'], ENT_QUOTES, 'UTF-8');
    return "background-image: linear-gradient(rgba(10,10,15,$opacity), rgba(10,10,15,$opacity)), url('$image'); background-size: cover; background-position: center;";
}

function project_tags($tech_stack) {
    if (!$tech_stack) return [];
    $decoded = json_decode($tech_stack, true);
    if (is_array($decoded)) return $decoded;
    return array_filter(array_map('trim', explode(',', $tech_stack)));
}

function date_range_label($row) {
    $start = !empty($row['start_date']) ? date('M Y', strtotime($row['start_date'])) : 'N/A';
    $end = !empty($row['is_current']) ? 'Present' : (!empty($row['end_date']) ? date('M Y', strtotime($row['end_date'])) : 'N/A');
    return $start . ' - ' . $end;
}

function whatsapp_url($phone) {
    $digits = preg_replace('/\D+/', '', (string)$phone);
    return $digits ? 'https://wa.me/' . $digits : '#contact';
}

$background = safe_color(setting_value($settings, 'background_color', '#0a0a0f'), '#0a0a0f');
$surface = safe_color(setting_value($settings, 'surface_color', '#18181b'), '#18181b');
$text = safe_color(setting_value($settings, 'text_color', '#e4e4e7'), '#e4e4e7');
$primary = safe_color(setting_value($settings, 'primary_action_color', '#6366f1'), '#6366f1');
$secondary = safe_color(setting_value($settings, 'secondary_action_color', '#ec4899'), '#ec4899');
$accent = safe_color(setting_value($settings, 'accent_color', '#22c55e'), '#22c55e');
$muted = '#a1a1aa';
$soft = '#71717a';
$border = '#27272a';
$brand = setting_value($settings, 'brand_name', 'LAWREAY');
$hero_title = setting_value($settings, 'hero_title', 'Lawrence Phuka Chikapa');
$hero_kicker = setting_value($settings, 'hero_kicker', 'Creative Technologist | Developer | Builder');
$hero_subtitle = setting_value($settings, 'hero_subtitle', 'Empowering Malawi Through Digital Innovation');
$hero_cta = setting_value($settings, 'hero_cta', 'Learn More');
$about_title = setting_value($settings, 'about_title', 'About Me');
$about_text = setting_value($settings, 'about_text', setting_value($settings, 'bio'));
$about_secondary = setting_value($settings, 'about_secondary', 'As an ICT educator, I bridge the gap between technology and education, creating innovative solutions that make quality learning accessible to all Malawians, regardless of their economic background.');
$vision = setting_value($settings, 'vision', '');
$contact_intro = setting_value($settings, 'contact_intro', "Interested in collaboration, partnerships, or learning more about Learn360? I'd love to hear from you!");
$email = setting_value($settings, 'email', 'lawreay1@gmail.com');
$whatsapp = setting_value($settings, 'whatsapp', '+265 993 147 700');
$location = setting_value($settings, 'location', 'Ntcheu, Malawi');
$github_url = setting_value($settings, 'github_url', 'https://github.com/lawreay');
$profile_image = setting_value($settings, 'profile_image', '');
$favicon_path = setting_value($settings, 'favicon_path', '');
$cv_path = setting_value($settings, 'cv_path', '');
$cv_download_enabled = !isset($settings['enable_cv_download']) || (int)$settings['enable_cv_download'] === 1;
$cv_is_pdf = $cv_path !== '' && (bool)preg_match('/\.pdf(\?.*)?$/i', $cv_path);
$allowed_fonts = ['Syne', 'Inter', 'Poppins', 'Roboto', 'Playfair Display', 'DM Sans'];
$font_family = setting_value($settings, 'font_family', 'DM Sans');
if (!in_array($font_family, $allowed_fonts, true)) $font_family = 'DM Sans';
$font_size = max(12, min(24, (int)setting_value($settings, 'font_size', 16)));
$animations_enabled = !isset($settings['enable_animations']) || (int)$settings['enable_animations'] === 1;
$allowed_cursors = ['default', 'glow', 'trail', 'bounce'];
$cursor_animation = setting_value($settings, 'cursor_animation', 'default');
if (!in_array($cursor_animation, $allowed_cursors, true)) $cursor_animation = 'default';

$detail_cards = [
    'Full Name' => setting_value($settings, 'full_name', $hero_title),
    'Home of Origin' => setting_value($settings, 'home_origin', 'Ntcheu'),
    'Nationality' => setting_value($settings, 'nationality', 'Malawian'),
    'Religion' => setting_value($settings, 'religion', 'Christian'),
    'Status' => setting_value($settings, 'status', 'Single'),
    'Location' => $location,
];

$palette = [$primary, $secondary, $accent, '#fbbf24', '#a78bfa', '#f97316'];
?>
<!doctype html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($hero_title); ?></title>
  <?php if ($favicon_path): ?><link rel="icon" href="<?php echo e($favicon_path); ?>"><?php endif; ?>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Roboto:wght@300;400;500;700;900&family=Syne:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: <?php echo e($background); ?>;
      --surface: <?php echo e($surface); ?>;
      --panel: #0f0f14;
      --text: <?php echo e($text); ?>;
      --muted: <?php echo e($muted); ?>;
      --soft: <?php echo e($soft); ?>;
      --border: <?php echo e($border); ?>;
      --primary: <?php echo e($primary); ?>;
      --secondary: <?php echo e($secondary); ?>;
      --accent: <?php echo e($accent); ?>;
    }
    * { box-sizing: border-box; }
    html, body { min-height: 100%; margin: 0; scroll-behavior: smooth; overflow-x: hidden; }
    body { background: var(--bg); color: var(--text); font-family: '<?php echo e($font_family); ?>', 'DM Sans', sans-serif; font-size: <?php echo e($font_size); ?>px; }
    .font-syne { font-family: '<?php echo e($font_family); ?>', 'Syne', sans-serif; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-up { animation: fadeUp 0.8s ease forwards; }
    .delay-1 { animation-delay: 0.1s; opacity: 0; }
    .delay-2 { animation-delay: 0.2s; opacity: 0; }
    .delay-3 { animation-delay: 0.3s; opacity: 0; }
    .gradient-text { background: linear-gradient(135deg, var(--text) 0%, var(--primary) 50%, var(--secondary) 100%); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .nav-link { position: relative; }
    .nav-link::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: var(--primary); transition: width 0.3s ease; }
    .nav-link:hover::after { width: 100%; }
    .mobile-menu { max-height: 0; opacity: 0; overflow: hidden; pointer-events: none; transition: max-height 0.3s ease, opacity 0.25s ease; }
    .mobile-menu.is-open { max-height: 80vh; opacity: 1; pointer-events: auto; }
    .card { background: var(--surface); border: 1px solid var(--border); }
    .profile-photo { width: 8rem; height: 8rem; border-radius: 999px; object-fit: cover; border: 3px solid color-mix(in srgb, var(--primary) 72%, white); box-shadow: 0 20px 60px rgba(0,0,0,0.35); }
    .profile-fallback { width: 8rem; height: 8rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; font-size: 3rem; font-weight: 800; border: 3px solid rgba(255,255,255,0.12); }
    .project-card, .skill-pill, .contact-card { transition: transform 0.3s ease, border-color 0.3s ease; }
    .project-card:hover, .contact-card:hover { transform: translateY(-4px); border-color: var(--primary); }
    .skill-pill:hover { transform: scale(1.04); }
    .contact-value { overflow-wrap: anywhere; word-break: break-word; }
    .cv-viewer { width: 100%; height: min(78vh, 820px); border: 1px solid var(--border); border-radius: 0.75rem; background: var(--panel); }
    .popup-backdrop { position: fixed; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center; padding: 1rem; background: rgba(0,0,0,0.62); }
    .popup-backdrop.is-open { display: flex; }
    .cursor-effect { position: fixed; left: 0; top: 0; z-index: 100; pointer-events: none; transform: translate(-50%, -50%); display: none; }
    @media (pointer: fine) {
      body.cursor-glow, body.cursor-bounce { cursor: none; }
      body.cursor-glow a, body.cursor-glow button, body.cursor-glow input, body.cursor-glow textarea, body.cursor-glow select,
      body.cursor-bounce a, body.cursor-bounce button, body.cursor-bounce input, body.cursor-bounce textarea, body.cursor-bounce select { cursor: none; }
      .cursor-effect.is-visible { display: block; }
      .cursor-effect.glow { width: 34px; height: 34px; border-radius: 999px; border: 1px solid var(--primary); box-shadow: 0 0 26px color-mix(in srgb, var(--primary) 60%, transparent); }
      .cursor-effect.bounce { width: 18px; height: 18px; border-radius: 999px; background: var(--primary); animation: cursorBounce 0.7s ease-in-out infinite alternate; }
      .cursor-trail-dot { position: fixed; z-index: 99; pointer-events: none; width: 10px; height: 10px; border-radius: 999px; background: var(--secondary); transform: translate(-50%, -50%); opacity: 0.75; animation: trailFade 0.55s ease forwards; }
    }
    @keyframes cursorBounce { from { transform: translate(-50%, -50%) scale(0.75); } to { transform: translate(-50%, -50%) scale(1.25); } }
    @keyframes trailFade { to { opacity: 0; transform: translate(-50%, -50%) scale(0.2); } }
    <?php if (!$animations_enabled): ?>
    *, *::before, *::after { animation: none !important; transition: none !important; scroll-behavior: auto !important; }
    .animate-fade-up, .delay-1, .delay-2, .delay-3 { opacity: 1 !important; transform: none !important; }
    <?php endif; ?>
    @media (max-width: 767px) {
      section { padding-top: 4rem !important; padding-bottom: 4rem !important; }
      #hero-title { font-size: clamp(2.45rem, 14vw, 4.2rem) !important; line-height: 0.96; overflow-wrap: anywhere; }
      #hero-subtitle { font-size: 1rem !important; }
      .project-card:hover, .skill-pill:hover, .contact-card:hover, a:hover { transform: none !important; }
    }
  </style>
</head>
<body class="cursor-<?php echo e($cursor_animation); ?>">
  <?php if ($cursor_animation !== 'default'): ?><div id="cursor-effect" class="cursor-effect <?php echo e($cursor_animation); ?>"></div><?php endif; ?>
  <div id="app" class="w-full min-h-screen overflow-hidden">
    <nav class="fixed top-0 left-0 right-0 z-50 px-4 sm:px-6 py-4" style="background: rgba(10,10,15,0.92); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(39,39,42,0.5);">
      <div class="max-w-6xl mx-auto flex justify-between items-center gap-4">
        <a href="#top" class="flex items-center gap-3" style="text-decoration: none;">
          <span class="w-10 h-10 rounded-lg flex items-center justify-center font-syne font-bold text-lg" style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); color: #fff;">L</span>
          <span class="font-syne font-bold text-lg hidden sm:inline" style="color: var(--text);"><?php echo e($brand); ?></span>
        </a>
        <div class="hidden lg:flex gap-8">
          <a href="#about" class="nav-link text-sm" style="color: var(--muted);">About</a>
          <?php if ($goals): ?><a href="#goals" class="nav-link text-sm" style="color: var(--muted);">Goals</a><?php endif; ?>
          <?php if ($interests): ?><a href="#interests" class="nav-link text-sm" style="color: var(--muted);">Interests</a><?php endif; ?>
          <?php if ($projects): ?><a href="#projects" class="nav-link text-sm" style="color: var(--muted);">Projects</a><?php endif; ?>
          <?php if ($experience): ?><a href="#experience" class="nav-link text-sm" style="color: var(--muted);">Experience</a><?php endif; ?>
          <?php if ($education): ?><a href="#education" class="nav-link text-sm" style="color: var(--muted);">Education</a><?php endif; ?>
          <?php if ($skills): ?><a href="#skills" class="nav-link text-sm" style="color: var(--muted);">Skills</a><?php endif; ?>
          <?php if ($news_items): ?><a href="#news" class="nav-link text-sm" style="color: var(--muted);">News</a><?php endif; ?>
          <?php if ($cv_path): ?><a href="#cv" class="nav-link text-sm" style="color: var(--muted);">CV</a><?php endif; ?>
          <a href="#contact" class="nav-link text-sm" style="color: var(--muted);">Contact</a>
        </div>
        <div class="flex items-center gap-2">
          <?php if ($github_url): ?>
            <a href="<?php echo e($github_url); ?>" target="_blank" rel="noopener noreferrer" class="hidden sm:inline-flex p-2 rounded-lg transition-colors hover:bg-gray-800" title="GitHub">
              <i data-lucide="github" style="color: var(--muted); width: 20px; height: 20px;"></i>
            </a>
          <?php endif; ?>
          <button id="mobile-menu-button" type="button" class="lg:hidden p-2 rounded-lg transition-colors hover:bg-gray-800" aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu" style="color: var(--text);">
            <i data-lucide="menu" class="menu-icon" style="width: 24px; height: 24px;"></i>
            <i data-lucide="x" class="close-icon hidden" style="width: 24px; height: 24px;"></i>
          </button>
        </div>
      </div>
      <div id="mobile-menu" class="mobile-menu lg:hidden max-w-6xl mx-auto">
        <div class="grid gap-2 pt-4 pb-2">
          <a href="#about" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">About</a>
          <?php if ($goals): ?><a href="#goals" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">Goals</a><?php endif; ?>
          <?php if ($interests): ?><a href="#interests" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">Interests</a><?php endif; ?>
          <?php if ($projects): ?><a href="#projects" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">Projects</a><?php endif; ?>
          <?php if ($experience): ?><a href="#experience" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">Experience</a><?php endif; ?>
          <?php if ($education): ?><a href="#education" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">Education</a><?php endif; ?>
          <?php if ($skills): ?><a href="#skills" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">Skills</a><?php endif; ?>
          <?php if ($news_items): ?><a href="#news" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">News</a><?php endif; ?>
          <?php if ($cv_path): ?><a href="#cv" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">CV</a><?php endif; ?>
          <a href="#contact" class="mobile-menu-link rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);">Contact</a>
          <?php if ($github_url): ?><a href="<?php echo e($github_url); ?>" target="_blank" rel="noopener noreferrer" class="mobile-menu-link inline-flex items-center gap-2 rounded-lg px-4 py-3 text-sm hover:bg-gray-800" style="color: var(--text);"><i data-lucide="github" style="width: 18px; height: 18px;"></i> GitHub</a><?php endif; ?>
        </div>
      </div>
    </nav>

    <section id="top" class="min-h-screen flex items-center justify-center px-4 sm:px-6 pt-24 relative overflow-hidden" style="<?php echo section_style('hero', $section_backgrounds); ?>">
      <div class="absolute inset-0" style="background: radial-gradient(ellipse at 30% 50%, <?php echo e(color_to_rgba($primary, 0.09)); ?> 0%, transparent 60%), radial-gradient(ellipse at 70% 80%, <?php echo e(color_to_rgba($secondary, 0.07)); ?> 0%, transparent 50%);"></div>
      <div class="text-center relative z-10 max-w-3xl">
        <div class="mb-6 animate-fade-up">
          <?php if ($profile_image): ?>
            <img src="<?php echo e($profile_image); ?>" alt="<?php echo e($hero_title); ?>" class="profile-photo mx-auto">
          <?php else: ?>
            <span class="profile-fallback font-syne mx-auto">L</span>
          <?php endif; ?>
        </div>
        <p class="text-sm tracking-widest uppercase mb-4 animate-fade-up" style="color: var(--primary);"><?php echo e($hero_kicker); ?></p>
        <h1 id="hero-title" class="font-syne font-extrabold text-5xl sm:text-6xl md:text-7xl mb-6 animate-fade-up delay-1 gradient-text"><?php echo e($hero_title); ?></h1>
        <p id="hero-subtitle" class="text-lg md:text-xl mb-10 animate-fade-up delay-2" style="color: var(--muted);"><?php echo e($hero_subtitle); ?></p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-up delay-3">
          <a href="#projects" class="px-6 py-3 rounded-full font-medium text-sm transition-all hover:scale-105" style="background: var(--primary); color: #fff;"><?php echo e($hero_cta); ?></a>
          <a href="#contact" class="px-6 py-3 rounded-full font-medium text-sm border transition-all hover:scale-105" style="border-color: var(--border); color: var(--text);">Get in Touch</a>
          <?php if ($cv_path): ?><a href="#cv" class="px-6 py-3 rounded-full font-medium text-sm border transition-all hover:scale-105" style="border-color: var(--border); color: var(--text);">View CV</a><?php endif; ?>
        </div>
      </div>
    </section>

    <section id="about" class="py-24 px-6" style="<?php echo section_style('about', $section_backgrounds); ?>">
      <div class="max-w-4xl mx-auto">
        <h2 class="font-syne font-bold text-3xl mb-8"><?php echo e($about_title); ?></h2>
        <?php if ($about_text): ?><p class="text-lg leading-relaxed mb-6" style="color: var(--muted);"><?php echo e($about_text); ?></p><?php endif; ?>
        <?php if ($about_secondary): ?><p class="text-lg leading-relaxed mb-10" style="color: var(--muted);"><?php echo e($about_secondary); ?></p><?php endif; ?>
        <div class="grid md:grid-cols-2 gap-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($detail_cards as $label => $value): ?>
              <?php if ($value !== ''): ?>
                <div class="card p-5 rounded-xl">
                  <p class="text-xs font-medium mb-2" style="color: var(--soft);"><?php echo e($label); ?></p>
                  <p class="text-sm"><?php echo e($value); ?></p>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php if ($vision): ?>
            <div class="card p-6 rounded-xl">
              <h3 class="font-syne font-bold text-lg mb-4">Vision &amp; Impact</h3>
              <p class="text-sm leading-relaxed" style="color: var(--muted);"><?php echo e($vision); ?></p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <?php if ($goals): ?>
      <section id="goals" class="py-24 px-6" style="<?php echo section_style('goals', $section_backgrounds, 'background: var(--panel);'); ?>">
        <div class="max-w-4xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Goals &amp; Vision</h2>
          <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ($goals as $index => $goal): $color = $palette[$index % count($palette)]; ?>
              <div class="card p-6 rounded-xl">
                <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4" style="background: <?php echo e(color_to_rgba($color)); ?>;">
                  <?php echo icon_markup($goal['icon_type'] ?? 'target', 'target', $color, 24); ?>
                </div>
                <h3 class="font-syne font-bold text-lg mb-3"><?php echo e($goal['title']); ?></h3>
                <p class="text-sm" style="color: var(--muted);"><?php echo e($goal['description']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($interests): ?>
      <section id="interests" class="py-24 px-6" style="<?php echo section_style('interests', $section_backgrounds); ?>">
        <div class="max-w-4xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Interests &amp; Passions</h2>
          <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($interests as $index => $interest): ?>
              <?php $color = $palette[$index % count($palette)]; ?>
              <div class="card p-6 rounded-xl">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-4" style="background: <?php echo e(color_to_rgba($color)); ?>;">
                  <?php echo icon_markup($interest['icon_type'] ?? 'star', 'star', $color, 22); ?>
                </div>
                <h3 class="font-syne font-bold text-lg mb-3"><?php echo e($interest['title']); ?></h3>
                <p class="text-sm" style="color: var(--muted);"><?php echo e($interest['description']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($challenges): ?>
      <section class="py-24 px-6" style="<?php echo section_style('challenges', $section_backgrounds, 'background: var(--panel);'); ?>">
        <div class="max-w-4xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Challenges I've Tackled</h2>
          <div class="space-y-6">
            <?php foreach ($challenges as $index => $challenge): $color = $palette[$index % count($palette)]; ?>
              <div class="card p-6 rounded-xl" style="border-left: 4px solid <?php echo e($color); ?>;">
                <h3 class="font-syne font-bold text-lg mb-2"><?php echo e($challenge['title']); ?></h3>
                <p class="text-sm" style="color: var(--muted);"><?php echo e($challenge['description']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($experience): ?>
      <section id="experience" class="py-24 px-6" style="<?php echo section_style('experience', $section_backgrounds, 'background: var(--panel);'); ?>">
        <div class="max-w-4xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Experience</h2>
          <div class="space-y-8">
            <?php foreach ($experience as $index => $item): $color = safe_color($item['icon_color'] ?? '', $palette[$index % count($palette)]); ?>
              <div class="relative pl-8 border-l-2" style="border-color: var(--border);">
                <div class="absolute left-[-10px] top-0 w-5 h-5 rounded-full" style="background: <?php echo e($color); ?>;"></div>
                <h3 class="font-syne font-bold text-lg"><?php echo e($item['title']); ?></h3>
                <p class="text-sm mb-2" style="color: var(--muted);"><?php echo e($item['company']); ?></p>
                <p class="text-xs mb-3" style="color: var(--soft);"><?php echo e(date_range_label($item)); ?></p>
                <p class="text-sm" style="color: var(--muted);"><?php echo e($item['description']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($education): ?>
      <section id="education" class="py-24 px-6" style="<?php echo section_style('education', $section_backgrounds); ?>">
        <div class="max-w-4xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Education</h2>
          <div class="space-y-8">
            <?php foreach ($education as $index => $item): $color = safe_color($item['icon_color'] ?? '', $palette[$index % count($palette)]); ?>
              <div class="relative pl-8 border-l-2" style="border-color: var(--border);">
                <div class="absolute left-[-10px] top-0 w-5 h-5 rounded-full" style="background: <?php echo e($color); ?>;"></div>
                <h3 class="font-syne font-bold text-lg"><?php echo e($item['title']); ?></h3>
                <p class="text-sm mb-2" style="color: var(--muted);"><?php echo e($item['institution']); ?></p>
                <p class="text-xs mb-3" style="color: var(--soft);"><?php echo e(date_range_label($item)); ?></p>
                <p class="text-sm" style="color: var(--muted);"><?php echo e($item['description']); ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($projects): ?>
      <section id="projects" class="py-24 px-6" style="<?php echo section_style('projects', $section_backgrounds, 'background: var(--panel);'); ?>">
        <div class="max-w-6xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Featured Projects</h2>
          <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($projects as $index => $project): $color = safe_color($project['icon_color'] ?? '', $palette[$index % count($palette)]); $tags = project_tags($project['tech_stack']); ?>
              <article class="project-card card rounded-2xl p-8 flex flex-col">
                <?php if (!empty($project['image_path'])): ?>
                  <img src="<?php echo e($project['image_path']); ?>" alt="<?php echo e($project['title']); ?>" class="w-full aspect-video object-cover rounded-lg mb-5" loading="lazy">
                <?php endif; ?>
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: <?php echo e(color_to_rgba($color)); ?>;">
                    <?php echo icon_markup($project['icon_type'] ?? 'zap', 'zap', $color, 24); ?>
                  </div>
                  <div>
                    <h3 class="font-syne font-bold text-xl"><?php echo e($project['title']); ?></h3>
                    <?php if ($project['category']): ?><p class="text-xs" style="color: var(--soft);"><?php echo e($project['category']); ?></p><?php endif; ?>
                  </div>
                </div>
                <p class="text-sm mb-4 flex-grow" style="color: var(--muted);"><?php echo e($project['description'] ?: $project['short_description']); ?></p>
                <?php if ($tags): ?>
                  <div class="flex gap-2 flex-wrap mb-4">
                    <?php foreach ($tags as $tag): ?><span class="text-xs px-3 py-1 rounded-full" style="background: var(--border); color: var(--muted);"><?php echo e($tag); ?></span><?php endforeach; ?>
                  </div>
                <?php endif; ?>
                <?php if ($project['demo_url'] || $project['github_url']): ?>
                  <div class="flex flex-wrap gap-4">
                    <?php if ($project['demo_url']): ?><a href="<?php echo e($project['demo_url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium" style="color: <?php echo e($color); ?>;">View Demo <i data-lucide="external-link" style="width: 14px; height: 14px;"></i></a><?php endif; ?>
                    <?php if ($project['github_url']): ?><a href="<?php echo e($project['github_url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium" style="color: var(--muted);">Code <i data-lucide="github" style="width: 14px; height: 14px;"></i></a><?php endif; ?>
                  </div>
                <?php else: ?>
                  <a href="#contact" class="inline-flex items-center gap-2 text-sm font-medium" style="color: <?php echo e($color); ?>;">Learn More <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i></a>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($skill_groups): ?>
      <section id="skills" class="py-24 px-6" style="<?php echo section_style('skills', $section_backgrounds); ?>">
        <div class="max-w-4xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Core Competencies</h2>
          <div class="space-y-8">
            <?php foreach ($skill_groups as $category => $items): $color = safe_color($items[0]['color'] ?? '', $primary); ?>
              <div>
                <h3 class="font-syne font-bold text-lg mb-4" style="color: <?php echo e($color); ?>;"><?php echo e($category); ?></h3>
                <div class="flex flex-wrap gap-3">
                  <?php foreach ($items as $skill): $skill_color = safe_color($skill['color'] ?? '', $color); ?>
                    <span class="skill-pill px-4 py-2 rounded-full text-sm font-medium" style="background: <?php echo e(color_to_rgba($skill_color)); ?>; color: <?php echo e($skill_color); ?>;"><?php echo e($skill['skill_name']); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($news_items): ?>
      <section id="news" class="py-24 px-6" style="<?php echo section_style('news', $section_backgrounds, 'background: var(--panel);'); ?>">
        <div class="max-w-5xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">News</h2>
          <div class="grid md:grid-cols-2 gap-6">
            <?php foreach ($news_items as $item): ?>
              <article class="card rounded-xl p-6">
                <p class="text-xs mb-3" style="color: var(--soft);"><?php echo e(date('M j, Y', strtotime($item['published_at'] ?? $item['created_at']))); ?></p>
                <h3 class="font-syne font-bold text-xl mb-3"><?php echo e($item['title']); ?></h3>
                <p class="text-sm mb-4" style="color: var(--muted);"><?php echo e($item['body']); ?></p>
                <?php if (!empty($item['link_url'])): ?>
                  <a href="<?php echo e($item['link_url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium" style="color: var(--primary);">Read More <i data-lucide="external-link" style="width: 14px; height: 14px;"></i></a>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($partnerships): ?>
      <section class="py-24 px-6" style="<?php echo section_style('partnerships', $section_backgrounds); ?>">
        <div class="max-w-4xl mx-auto">
          <h2 class="font-syne font-bold text-3xl mb-12">Network &amp; Partnerships</h2>
          <div class="space-y-6">
            <?php foreach ($partnerships as $index => $partner): $color = $palette[$index % count($palette)]; ?>
              <div class="p-8 rounded-2xl" style="background: linear-gradient(135deg, <?php echo e(color_to_rgba($primary, 0.1)); ?> 0%, <?php echo e(color_to_rgba($secondary, 0.1)); ?> 100%); border: 1px solid var(--border);">
                <div class="flex flex-col sm:flex-row items-start gap-4">
                  <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: <?php echo e(color_to_rgba($color)); ?>;">
                    <?php echo icon_markup($partner['icon_type'] ?? 'users', 'users', $color, 24); ?>
                  </div>
                  <div>
                    <h3 class="font-syne font-bold text-lg mb-2"><?php echo e($partner['title']); ?></h3>
                    <p class="text-sm" style="color: var(--muted);"><?php echo e($partner['description']); ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($cv_path): ?>
      <section id="cv" class="py-24 px-6" style="<?php echo section_style('cv', $section_backgrounds, 'background: var(--panel);'); ?>">
        <div class="max-w-5xl mx-auto">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <h2 class="font-syne font-bold text-3xl">Curriculum Vitae</h2>
            <div class="flex flex-wrap gap-3">
              <?php if ($cv_download_enabled || !$cv_is_pdf): ?>
                <a href="<?php echo e($cv_path); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-5 py-3 rounded-lg font-semibold text-sm" style="background: var(--primary); color: #fff;">
                  <i data-lucide="external-link" style="width:16px;height:16px;"></i> View CV
                </a>
              <?php endif; ?>
              <?php if ($cv_download_enabled): ?>
                <a href="<?php echo e($cv_path); ?>" download class="inline-flex items-center gap-2 px-5 py-3 rounded-lg font-semibold text-sm border" style="border-color: var(--border); color: var(--text);">
                  <i data-lucide="download" style="width:16px;height:16px;"></i> Download CV
                </a>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($cv_is_pdf): ?>
            <iframe class="cv-viewer" src="<?php echo e($cv_path); ?><?php echo $cv_download_enabled ? '#toolbar=1' : '#toolbar=0&navpanes=0'; ?>" title="Curriculum Vitae"></iframe>
          <?php else: ?>
            <div class="card rounded-xl p-6">
              <a href="<?php echo e($cv_path); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium" style="color: var(--primary);">Open CV <i data-lucide="external-link" style="width:14px;height:14px;"></i></a>
            </div>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

    <section id="contact" class="py-24 px-6" style="<?php echo section_style('contact', $section_backgrounds, 'background: var(--panel);'); ?>">
      <div class="max-w-2xl mx-auto text-center">
        <h2 class="font-syne font-bold text-3xl mb-4">Let's Connect</h2>
        <p class="mb-12" style="color: var(--soft);"><?php echo e($contact_intro); ?></p>
        <div class="grid md:grid-cols-3 gap-6">
          <a href="mailto:<?php echo e($email); ?>" class="contact-card card p-6 rounded-xl" style="text-decoration: none;">
            <div class="flex items-center justify-center mb-3"><i data-lucide="mail" style="color: var(--primary); width:24px; height:24px;"></i></div>
            <p class="text-sm font-medium mb-1">Email</p>
            <p class="contact-value text-xs" style="color: var(--muted);"><?php echo e($email); ?></p>
          </a>
          <a href="<?php echo e(whatsapp_url($whatsapp)); ?>" target="_blank" rel="noopener noreferrer" class="contact-card card p-6 rounded-xl" style="text-decoration: none;">
            <div class="flex items-center justify-center mb-3"><i data-lucide="message-circle" style="color: var(--secondary); width:24px; height:24px;"></i></div>
            <p class="text-sm font-medium mb-1">WhatsApp</p>
            <p class="contact-value text-xs" style="color: var(--muted);"><?php echo e($whatsapp); ?></p>
          </a>
          <div class="card p-6 rounded-xl">
            <div class="flex items-center justify-center mb-3"><i data-lucide="map-pin" style="color: #fbbf24; width:24px; height:24px;"></i></div>
            <p class="text-sm font-medium mb-1">Location</p>
            <p class="contact-value text-xs" style="color: var(--muted);"><?php echo e($location); ?></p>
          </div>
        </div>
      </div>
    </section>

    <section id="engage" class="py-24 px-6" style="<?php echo section_style('engage', $section_backgrounds); ?>">
      <div class="max-w-6xl mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-12">
          <h2 class="font-syne font-bold text-3xl mb-4">Bookings, Requests &amp; Comments</h2>
          <p style="color: var(--muted);">Use the right form and it will appear in the admin dashboard for review.</p>
          <?php if (isset($_GET['sent'])): ?><p class="mt-4 text-sm" style="color: var(--accent);">Thank you. Your submission has been received.</p><?php endif; ?>
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
          <form method="POST" class="card rounded-xl p-6 space-y-4">
            <input type="hidden" name="public_action" value="booking">
            <h3 class="font-syne font-bold text-xl">Book a Session</h3>
            <input name="visitor_name" required placeholder="Your name" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <input type="email" name="visitor_email" required placeholder="Email" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <input name="visitor_phone" placeholder="Phone / WhatsApp" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <input name="service" placeholder="Service needed" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <input type="date" name="preferred_date" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <textarea name="message" rows="4" placeholder="Details" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);"></textarea>
            <button type="submit" class="w-full px-5 py-3 rounded-lg font-semibold" style="background: var(--primary); color: #fff;">Send Booking</button>
          </form>
          <form method="POST" class="card rounded-xl p-6 space-y-4">
            <input type="hidden" name="public_action" value="request">
            <h3 class="font-syne font-bold text-xl">Send a Request</h3>
            <input name="visitor_name" required placeholder="Your name" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <input type="email" name="visitor_email" required placeholder="Email" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <input name="request_type" placeholder="Request type" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <textarea name="message" rows="7" required placeholder="What do you need?" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);"></textarea>
            <button type="submit" class="w-full px-5 py-3 rounded-lg font-semibold" style="background: var(--secondary); color: #fff;">Send Request</button>
          </form>
          <form method="POST" class="card rounded-xl p-6 space-y-4">
            <input type="hidden" name="public_action" value="comment">
            <h3 class="font-syne font-bold text-xl">Leave a Comment</h3>
            <input name="visitor_name" required placeholder="Your name" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <input type="email" name="visitor_email" placeholder="Email" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);">
            <textarea name="comment" rows="9" required placeholder="Your comment" class="w-full px-4 py-3 rounded-lg" style="background: var(--border); color: var(--text); border: 1px solid var(--border);"></textarea>
            <button type="submit" class="w-full px-5 py-3 rounded-lg font-semibold" style="background: var(--accent); color: #06110b;">Send Comment</button>
          </form>
        </div>
      </div>
    </section>

    <footer class="py-8 px-6 text-center" style="border-top: 1px solid var(--surface);">
      <p class="text-xs" style="color: #52525b;">&copy; <?php echo date('Y'); ?> <?php echo e($hero_title); ?>. All rights reserved. <a href="admin/login.php" style="color: #52525b;">Admin</a></p>
    </footer>
  </div>

  <?php if ($popups): $popup = $popups[0]; ?>
    <div id="site-popup" class="popup-backdrop" role="dialog" aria-modal="true" aria-labelledby="site-popup-title"
      data-trigger-timer="<?php echo ($popup['trigger_timer'] ?? 1) ? 'true' : 'false'; ?>"
      data-timer-delay="<?php echo (int)($popup['timer_delay'] ?? 900); ?>"
      data-trigger-scroll="<?php echo ($popup['trigger_scroll'] ?? 0) ? 'true' : 'false'; ?>"
      data-scroll-percentage="<?php echo (int)($popup['scroll_percentage'] ?? 50); ?>"
      data-trigger-esc="<?php echo ($popup['trigger_esc'] ?? 1) ? 'true' : 'false'; ?>"
      data-trigger-back-button="<?php echo ($popup['trigger_back_button'] ?? 1) ? 'true' : 'false'; ?>">
      <div class="card rounded-2xl p-6 max-w-lg w-full relative">
        <button id="site-popup-close" type="button" class="absolute top-3 right-3 p-2 rounded-lg hover:bg-gray-800" aria-label="Close popup" style="color: var(--text);"><i data-lucide="x" style="width:20px;height:20px;"></i></button>
        <?php if (!empty($popup['image_url'])): ?><img src="<?php echo e($popup['image_url']); ?>" alt="" class="w-full aspect-video object-cover rounded-lg mb-5"><?php endif; ?>
        <h2 id="site-popup-title" class="font-syne font-bold text-2xl mb-3"><?php echo e($popup['title']); ?></h2>
        <p class="text-sm mb-6" style="color: var(--muted);"><?php echo e($popup['body']); ?></p>
        <?php if (!empty($popup['link_url'])): ?><a href="<?php echo e($popup['link_url']); ?>" class="inline-flex px-5 py-3 rounded-lg font-semibold" style="background: var(--primary); color: #fff;"><?php echo e($popup['button_text'] ?: 'Learn More'); ?></a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <script>
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    function setMobileMenu(open) {
      if (!mobileMenuButton || !mobileMenu) return;
      mobileMenu.classList.toggle('is-open', open);
      mobileMenuButton.setAttribute('aria-expanded', String(open));
      mobileMenuButton.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      mobileMenuButton.querySelector('.menu-icon')?.classList.toggle('hidden', open);
      mobileMenuButton.querySelector('.close-icon')?.classList.toggle('hidden', !open);
    }

    mobileMenuButton?.addEventListener('click', () => {
      setMobileMenu(!mobileMenu?.classList.contains('is-open'));
    });

    document.querySelectorAll('.mobile-menu-link').forEach(link => {
      link.addEventListener('click', () => setMobileMenu(false));
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') setMobileMenu(false);
    });

    const popup = document.getElementById('site-popup');
    const popupClose = document.getElementById('site-popup-close');
    
    if (popup && !sessionStorage.getItem('portfolioPopupClosed')) {
      // Parse popup trigger settings from data attributes
      const triggerTimer = popup.dataset.triggerTimer !== 'false';
      const timerDelay = parseInt(popup.dataset.timerDelay ?? 900);
      const triggerScroll = popup.dataset.triggerScroll === 'true';
      const scrollPercentage = parseInt(popup.dataset.scrollPercentage ?? 50);
      const triggerEsc = popup.dataset.triggerEsc !== 'false';
      const triggerBackButton = popup.dataset.triggerBackButton !== 'false';
      
      let popupShown = false;
      let scrollTriggered = false;
      
      // Timer trigger
      if (triggerTimer) {
        window.setTimeout(() => {
          if (!popupShown && !sessionStorage.getItem('portfolioPopupClosed')) {
            popup.classList.add('is-open');
            popupShown = true;
          }
        }, timerDelay);
      }
      
      // Scroll trigger
      if (triggerScroll) {
        document.addEventListener('scroll', () => {
          if (popupShown || scrollTriggered || sessionStorage.getItem('portfolioPopupClosed')) return;
          
          const scrollPercentageActual = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
          if (scrollPercentageActual >= scrollPercentage) {
            popup.classList.add('is-open');
            popupShown = true;
            scrollTriggered = true;
          }
        }, { once: true });
      }
      
      // ESC key trigger - allows closing with ESC
      if (triggerEsc) {
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && popup.classList.contains('is-open')) {
            popup.classList.remove('is-open');
            sessionStorage.setItem('portfolioPopupClosed', '1');
          }
        });
      }
      
      // Back button trigger - show popup on back/history
      if (triggerBackButton) {
        window.addEventListener('popstate', () => {
          if (!sessionStorage.getItem('portfolioPopupClosed') && !popupShown) {
            popup.classList.add('is-open');
            popupShown = true;
          }
        });
      }
    }
    
    // Close button click
    popupClose?.addEventListener('click', () => {
      popup.classList.remove('is-open');
      sessionStorage.setItem('portfolioPopupClosed', '1');
    });
    
    // Backdrop click to close
    popup?.addEventListener('click', (event) => {
      if (event.target === popup) {
        popup.classList.remove('is-open');
        sessionStorage.setItem('portfolioPopupClosed', '1');
      }
    });

    const cursorMode = <?php echo json_encode($cursor_animation); ?>;
    const cursorEffect = document.getElementById('cursor-effect');
    const finePointer = window.matchMedia('(pointer: fine)').matches;
    if (cursorMode !== 'default' && finePointer) {
      if (cursorEffect && (cursorMode === 'glow' || cursorMode === 'bounce')) {
        cursorEffect.classList.add('is-visible');
        document.addEventListener('mousemove', (event) => {
          cursorEffect.style.left = event.clientX + 'px';
          cursorEffect.style.top = event.clientY + 'px';
        });
      }

      if (cursorMode === 'trail') {
        let lastTrail = 0;
        document.addEventListener('mousemove', (event) => {
          const now = Date.now();
          if (now - lastTrail < 28) return;
          lastTrail = now;
          const dot = document.createElement('span');
          dot.className = 'cursor-trail-dot';
          dot.style.left = event.clientX + 'px';
          dot.style.top = event.clientY + 'px';
          document.body.appendChild(dot);
          window.setTimeout(() => dot.remove(), 600);
        });
      }
    }

    lucide.createIcons();
  </script>
</body>
</html>
