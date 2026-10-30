<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$websiteId = (int) $website['id'];
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

$socialLinks = json_decode((string) ($portfolio['social_links'] ?? '{}'), true);
$socialLinks = is_array($socialLinks) ? $socialLinks : [];
$avatarPath = trim((string) ($portfolio['avatar_path'] ?? ''));
$avatarUrl = $avatarPath !== '' && !str_contains($avatarPath, '..') && preg_match('#^uploads/[A-Za-z0-9_./-]+$#', $avatarPath)
    ? $baseUrl . '/' . ltrim($avatarPath, '/')
    : '';

$itemCountBySection = array_fill_keys(array_keys($sectionLabels ?? []), 0);
foreach ($items as $item) {
    if (isset($itemCountBySection[$item['section_key'] ?? ''])) {
        $itemCountBySection[$item['section_key']]++;
    }
}

// [singular noun, details placeholder, meta hint]
$sectionHints = [
    'goals' => ['Goal', 'What you want to achieve', 'Target date or timeframe'],
    'interests' => ['Interest', 'Why it matters to you', 'Category or timeframe'],
    'challenges' => ['Challenge', 'The problem and your approach', 'Date or outcome'],
    'experience' => ['Role or position', 'What you did and achieved', 'Company and dates'],
    'education' => ['Qualification', 'Programme, achievements, or highlights', 'Institution and dates'],
    'skills' => ['Skill', 'How you use this skill', 'Level or category'],
    'news' => ['Update headline', 'Share an announcement or update', 'Published date'],
    'partnerships' => ['Partner name', 'How you work together', 'Organisation or period'],
    'cv' => ['CV or credential title', 'Add a short description', 'Year or issuer'],
];
$sectionBlurbs = [
    'interests' => 'Add interests that help visitors understand what you care about.',
    'skills' => 'Add skills and optionally indicate your proficiency.',
];
$sectionIcons = [
    'goals' => 'target', 'interests' => 'heart', 'challenges' => 'mountain', 'experience' => 'briefcase-business',
    'education' => 'graduation-cap', 'skills' => 'award', 'news' => 'newspaper', 'partnerships' => 'handshake', 'cv' => 'file-text',
];

$noirImageBySection = [];
foreach (($noirSectionImages ?? []) as $image) {
    $path = (string) ($image['storage_key'] ?? '');
    if (preg_match('#^uploads/portfolios/' . $websiteId . '/noir/section-([a-z]+)-[a-f0-9]{32}\.(jpg|png|webp)$#i', $path, $matches)) {
        $noirImageBySection[$matches[1]] = $path;
    }
}
$noirDefaults = ['goals' => 'ideas.svg', 'interests' => 'ideas.svg', 'challenges' => 'ideas.svg', 'experience' => 'journey.svg', 'education' => 'journey.svg', 'skills' => 'practice.svg', 'news' => 'work.svg', 'partnerships' => 'work.svg', 'cv' => 'practice.svg', 'projects' => 'work.svg'];

$sectionFields = [
    'goals' => [['title', 'Goal title', true], ['details', 'What you want to achieve', false, 'textarea']],
    'interests' => [['title', 'Interest', true], ['details', 'Short explanation (optional)', false, 'textarea']],
    'challenges' => [['title', 'Challenge', true], ['details', 'What happened and how you responded', false, 'textarea'], ['outcome', 'Outcome (optional)', false]],
    'experience' => [['title', 'Job title', true], ['organisation', 'Employer', true], ['details', 'Responsibilities and achievements', false, 'textarea'], ['start_date', 'Start date', false, 'date'], ['end_date', 'End date', false, 'date'], ['current', 'I currently work here', false, 'checkbox']],
    'education' => [['title', 'Qualification', true], ['organisation', 'Institution', true], ['field_of_study', 'Field of study (optional)', false], ['details', 'Highlights or description (optional)', false, 'textarea'], ['start_date', 'Start date', false, 'date'], ['end_date', 'End date', false, 'date'], ['current', 'I am currently studying here', false, 'checkbox']],
    'skills' => [['title', 'Skill name', true], ['category', 'Category (optional)', false], ['proficiency', 'Proficiency (optional)', false, 'select', ['Beginner', 'Intermediate', 'Advanced', 'Expert']]],
    'news' => [['title', 'Headline', true], ['details', 'Article or update', true, 'textarea'], ['link', 'External link (optional)', false, 'url'], ['published_date', 'Publication date (optional)', false, 'date']],
    'partnerships' => [['title', 'Partner or organisation', true], ['details', 'Partnership details (optional)', false, 'textarea'], ['link', 'Website (optional)', false, 'url'], ['start_date', 'Start date (optional)', false, 'date'], ['end_date', 'End date (optional)', false, 'date']],
    'cv' => [['title', 'Document title', true], ['details', 'Short note (optional)', false, 'textarea'], ['year', 'Year or issuer (optional)', false], ['cv_file', 'Upload CV (PDF, DOC, DOCX; max 5 MB)', false, 'file']],
];
$aiStatus = is_array($aiStatus ?? null) ? $aiStatus : ['enabled' => false, 'defaultProvider' => 'openrouter', 'allowRequestKey' => false, 'providers' => []];
$aiStatusJson = json_encode($aiStatus, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$aiStatusJson = $aiStatusJson === false ? '{}' : $aiStatusJson;
$aiRewritableSections = ['goals', 'interests', 'challenges', 'experience', 'education', 'news', 'partnerships'];

/* One field renderer shared by the "add" and "edit" forms. */
$renderField = static function (array $field, $value, array $payload, bool $edit, string $placeholder) use ($escape): string {
    [$key, $label, $isRequired] = $field;
    $type = $field[3] ?? 'text';
    $options = $field[4] ?? [];
    $name = $escape($key);
    $required = $isRequired ? ' required' : '';

    if ($type === 'checkbox') {
        $checked = $edit && !empty($value) ? ' checked' : '';
        return '<label class="ap-check"><input type="checkbox" name="' . $name . '" value="1"' . $checked . '><span>' . $escape($label) . '</span></label>';
    }

    $wide = in_array($type, ['textarea', 'file'], true) ? ' ap-wide' : '';
    $out = '<label class="ap-field' . $wide . '"><span class="ap-label">' . $escape($label) . ($isRequired ? ' <em aria-hidden="true">*</em>' : '') . '</span>';
    $current = $edit ? (string) $value : '';

    if ($type === 'textarea') {
        $out .= '<textarea name="' . $name . '" rows="3" maxlength="5000" placeholder="' . $escape($placeholder) . '"' . $required . '>' . $escape($current) . '</textarea>';
    } elseif ($type === 'select') {
        $out .= '<select name="' . $name . '"><option value="">Choose if applicable</option>';
        foreach ($options as $option) {
            $optionValue = strtolower($option);
            $selected = $edit && $current === $optionValue ? ' selected' : '';
            $out .= '<option value="' . $escape($optionValue) . '"' . $selected . '>' . $escape($option) . '</option>';
        }
        $out .= '</select>';
    } elseif ($type === 'file') {
        if ($edit && !empty($payload['file_path'])) {
            $out .= '<small class="ap-note">Current file: <a href="' . $escape('/' . ltrim((string) $payload['file_path'], '/')) . '" target="_blank" rel="noopener noreferrer">' . $escape($payload['file_name'] ?? 'Download CV') . '</a>. Choose a new file to replace it.</small>';
        }
        $out .= '<input type="file" name="' . $name . '" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"' . $required . '>';
    } else {
        $out .= '<input type="' . $escape($type) . '" name="' . $name . '" maxlength="' . ($type === 'url' ? '500' : '190') . '"' . ($edit ? ' value="' . $escape($current) . '"' : '') . ($type === 'url' ? ' placeholder="https://..."' : '') . $required . '>';
    }
    return $out . '</label>';
};

/* Overview checklist */
$profileName = trim((string) ($portfolio['display_name'] ?? ''));
$profileHeadline = trim((string) ($portfolio['headline'] ?? ''));
$profileBio = trim((string) ($portfolio['bio'] ?? ''));
$profileEmail = trim((string) ($portfolio['email'] ?? ''));
$coreKeys = array_values(array_filter(['experience', 'education', 'skills'], static fn (string $k): bool => isset($sectionLabels[$k])));
$hasCoreItems = false;
foreach ($coreKeys as $k) {
    if (($itemCountBySection[$k] ?? 0) > 0) {
        $hasCoreItems = true;
    }
}
$checklist = [
    ['Add your name and headline', $profileName !== '' && $profileHeadline !== '', 'profile'],
    ['Write your about section', $profileBio !== '', 'profile'],
    ['Upload a profile photo', $avatarUrl !== '', 'profile'],
    ['Add a public contact email', $profileEmail !== '', 'profile'],
    ['Add at least one project', count($projects) > 0, 'projects'],
];
if ($coreKeys !== []) {
    $checklist[] = ['Add experience, education or skills', $hasCoreItems, $coreKeys[0]];
}
$checkDone = count(array_filter($checklist, static fn (array $c): bool => $c[1]));
$checkTotal = count($checklist);
$visibleItems = count(array_filter($items, static fn (array $item): bool => !empty($item['is_enabled'])));
$isNoir = ($portfolio['template_key'] ?? '') === 'noir';
$settingsShowProjects = ($settings['show_projects'] ?? '1') === '1';
$settingsShowContact = ($settings['show_contact'] ?? '1') === '1';
?>
<section class="ap-studio" data-advanced-studio>
  <header class="ap-header">
    <div>
      <p class="ap-eyebrow">Portfolio Studio / Advanced editor</p>
      <h1>Edit your portfolio</h1>
      <p class="ap-sub"><?= $escape($website['website_name'] ?? 'Portfolio') ?></p>
    </div>
    <div class="ap-header-actions">
      <button class="ap-button ap-button-ai" type="button" data-ai-open><i data-lucide="sparkles"></i>AI Enhance</button>
      <a class="ap-button ap-button-quiet" href="<?= $escape($baseUrl . '/dashboard/websites/show?id=' . $websiteId) ?>">Manage site</a>
      <a class="ap-button ap-button-primary" href="<?= $escape($baseUrl . '/dashboard/websites/preview?id=' . $websiteId) ?>" target="_blank" rel="noopener"><i data-lucide="eye"></i>Preview</a>
    </div>
  </header>

  <div class="ap-shell">
    <nav class="ap-nav" aria-label="Portfolio editor sections" role="tablist" aria-orientation="vertical">
      <p class="ap-nav-group">Start</p>
      <button type="button" class="ap-tab" role="tab" data-ap-tab-button="overview"><i data-lucide="layout-dashboard"></i><span class="ap-tab-text">Overview</span></button>
      <p class="ap-nav-group">Basics</p>
      <button type="button" class="ap-tab" role="tab" data-ap-tab-button="profile"><i data-lucide="contact-round"></i><span class="ap-tab-text">Profile</span></button>
      <button type="button" class="ap-tab" role="tab" data-ap-tab-button="design"><i data-lucide="palette"></i><span class="ap-tab-text">Design</span></button>
      <?php if ($isNoir): ?><button type="button" class="ap-tab" role="tab" data-ap-tab-button="noir-images"><i data-lucide="images"></i><span class="ap-tab-text">Noir images</span></button><?php endif; ?>
      <?php if (!empty($sectionLabels)): ?><p class="ap-nav-group">Content</p><?php endif; ?>
      <?php foreach ($sectionLabels as $sectionKey => $sectionLabel): ?>
        <button type="button" class="ap-tab" role="tab" data-ap-tab-button="<?= $escape($sectionKey) ?>"><i data-lucide="<?= $escape($sectionIcons[$sectionKey] ?? 'list-plus') ?>"></i><span class="ap-tab-text"><?= $escape($sectionLabel) ?></span><span class="ap-tab-count"><?= (int) $itemCountBySection[$sectionKey] ?></span></button>
      <?php endforeach; ?>
      <p class="ap-nav-group">Work</p>
      <button type="button" class="ap-tab" role="tab" data-ap-tab-button="projects"><i data-lucide="panels-top-left"></i><span class="ap-tab-text">Projects</span><span class="ap-tab-count"><?= count($projects) ?></span></button>
    </nav>

    <div class="ap-panels">
      <!-- Overview -->
      <section class="ap-panel" role="tabpanel" data-ap-tab-panel="overview">
        <div class="ap-panel-heading">
          <div><p class="ap-kicker">Overview</p><h2>Manage your portfolio</h2><p>Complete the checklist, then fine-tune each area from the menu.</p></div>
        </div>
        <div class="ap-card ap-checklist">
          <div class="ap-check-head">
            <div><h3>Getting your portfolio ready</h3><p><?= $checkDone ?> of <?= $checkTotal ?> steps complete</p></div>
            <span class="ap-meter" aria-hidden="true"><i style="width:<?= (int) round($checkDone / max(1, $checkTotal) * 100) ?>%"></i></span>
          </div>
          <ul>
            <?php foreach ($checklist as [$text, $done, $tab]): ?>
              <li class="<?= $done ? 'is-done' : '' ?>">
                <span class="ap-tick" aria-hidden="true"><?= $done ? '&#10003;' : '' ?></span>
                <span class="ap-check-text"><?= $escape($text) ?><span class="ap-visually-hidden"><?= $done ? ' (done)' : ' (to do)' ?></span></span>
                <?php if (!$done): ?><button type="button" class="ap-link" data-ap-tab-button="<?= $escape($tab) ?>">Add</button><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="ap-stats">
          <article><i data-lucide="panels-top-left"></i><span>Projects</span><strong><?= count($projects) ?></strong></article>
          <article><i data-lucide="layers-3"></i><span>Portfolio items</span><strong><?= count($items) ?></strong></article>
          <article><i data-lucide="badge-check"></i><span>Visible items</span><strong><?= $visibleItems ?></strong></article>
          <article><i data-lucide="paintbrush"></i><span>Hero layout</span><strong><?= $escape(ucfirst((string) ($settings['hero_style'] ?? 'centered'))) ?></strong></article>
        </div>
      </section>

      <!-- Profile -->
      <section class="ap-panel" role="tabpanel" data-ap-tab-panel="profile">
        <div class="ap-panel-heading">
          <div><p class="ap-kicker">Profile</p><h2>Your introduction</h2><p>Powers your hero, about section, contact area and social links.</p></div>
        </div>
        <div class="ap-grid ap-grid-profile">
          <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/save') ?>" class="ap-card ap-form">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $websiteId ?>">
            <input type="hidden" name="return_to" value="advanced">
            <input type="hidden" name="return_tab" value="profile">
            <div class="ap-form-heading"><h3>Hero &amp; about</h3><span>Public content</span></div>
            <div class="ap-field-grid">
              <label class="ap-field"><span class="ap-label">Display name <em aria-hidden="true">*</em></span><input name="display_name" maxlength="120" required value="<?= $escape($portfolio['display_name'] ?? '') ?>"></label>
              <label class="ap-field"><span class="ap-label">Headline / role</span><input name="headline" maxlength="180" value="<?= $escape($portfolio['headline'] ?? '') ?>" placeholder="Designer, developer, photographer..."></label>
            </div>
            <label class="ap-field"><span class="ap-label">About you</span><textarea name="bio" rows="6" maxlength="10000" placeholder="Tell visitors about your work, skills, and experience."><?= $escape($portfolio['bio'] ?? '') ?></textarea><small class="ap-note" data-counter-for="bio"></small></label>
            <div class="ap-field-grid">
              <label class="ap-field"><span class="ap-label">Public email</span><input type="email" name="email" maxlength="190" value="<?= $escape($portfolio['email'] ?? '') ?>" placeholder="you@example.com"></label>
              <label class="ap-field"><span class="ap-label">Location</span><input name="location" maxlength="120" value="<?= $escape($portfolio['location'] ?? '') ?>" placeholder="City, country"></label>
            </div>
            <fieldset class="ap-fieldset">
              <legend>Social links</legend>
              <div class="ap-field-grid">
                <?php foreach (['github' => 'GitHub', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'website' => 'Personal website'] as $platform => $label): ?>
                  <label class="ap-field"><span class="ap-label"><?= $escape($label) ?></span><input type="url" name="social_<?= $escape($platform) ?>" maxlength="500" placeholder="https://..." value="<?= $escape($socialLinks[$platform] ?? '') ?>"></label>
                <?php endforeach; ?>
              </div>
            </fieldset>
            <div class="ap-form-foot"><button class="ap-button ap-button-primary" type="submit"><i data-lucide="save"></i>Save profile</button></div>
          </form>

          <aside class="ap-card ap-avatar-card">
            <div class="ap-form-heading"><h3>Profile image</h3><span>JPEG, PNG or WebP</span></div>
            <div class="ap-avatar-preview" id="ap-avatar-box">
              <?php if ($avatarUrl !== ''): ?><img src="<?= $escape($avatarUrl) ?>" alt="Current profile image"><?php else: ?><i data-lucide="image"></i><span>No image yet</span><?php endif; ?>
            </div>
            <p class="ap-note">Appears in your public portfolio hero. Up to 3 MB.</p>
            <form method="POST" enctype="multipart/form-data" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/avatar') ?>" data-no-dirty>
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="id" value="<?= $websiteId ?>">
              <input type="hidden" name="return_to" value="advanced">
              <input type="hidden" name="return_tab" value="profile">
              <label class="ap-upload" for="ap-avatar-input"><?= $avatarUrl !== '' ? 'Choose a new image' : 'Choose image' ?></label>
              <input class="ap-visually-hidden" id="ap-avatar-input" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
              <button class="ap-button ap-button-primary" id="ap-avatar-submit" type="submit"><i data-lucide="upload"></i>Upload image</button>
            </form>
          </aside>
        </div>
      </section>

      <!-- Design -->
      <section class="ap-panel" role="tabpanel" data-ap-tab-panel="design">
        <div class="ap-panel-heading">
          <div><p class="ap-kicker">Design</p><h2>Make the portfolio yours</h2><p>Changes apply to the live portfolio and the draft preview.</p></div>
        </div>
        <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/save') ?>" class="ap-card ap-form">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= $websiteId ?>">
          <input type="hidden" name="tab" value="design">

          <div class="ap-form-heading"><h3>Colours</h3><span id="ap-contrast" class="ap-contrast" role="status"></span></div>
          <div class="ap-colours">
            <label class="ap-colour"><span class="ap-label">Accent colour</span><span class="ap-colour-row"><input name="accent_color" type="color" value="<?= $escape($settings['accent_color'] ?? '#f97316') ?>"><output></output></span></label>
            <label class="ap-colour"><span class="ap-label">Background colour</span><span class="ap-colour-row"><input name="background_color" type="color" value="<?= $escape($settings['background_color'] ?? '#ffffff') ?>" data-contrast-bg><output></output></span></label>
            <label class="ap-colour"><span class="ap-label">Text colour</span><span class="ap-colour-row"><input name="text_color" type="color" value="<?= $escape($settings['text_color'] ?? '#111827') ?>" data-contrast-text><output></output></span></label>
          </div>

          <div class="ap-form-heading"><h3>Template &amp; layout</h3><span>Presentation only</span></div>
          <label class="ap-field"><span class="ap-label">Portfolio template</span>
            <select name="template_key" required>
              <?php foreach ($templates as $templateKey => $templateLabel): ?>
                <option value="<?= $escape($templateKey) ?>" <?= ($portfolio['template_key'] ?? 'classic') === $templateKey ? 'selected' : '' ?>><?= $escape($templateLabel) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="ap-note">Switching templates changes presentation, not your saved content.</small>
          </label>
          <div class="ap-field-grid">
            <label class="ap-field"><span class="ap-label">Typography</span>
              <select name="font_family"><?php foreach (['system' => 'System default', 'sans-serif' => 'Sans serif', 'serif' => 'Serif', 'mono' => 'Monospace'] as $key => $label): ?><option value="<?= $escape($key) ?>" <?= ($settings['font_family'] ?? 'system') === $key ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?></select>
            </label>
            <label class="ap-field"><span class="ap-label">Hero layout</span>
              <select name="hero_style"><?php foreach (['centered' => 'Centered introduction', 'split' => 'Split layout', 'minimal' => 'Minimal introduction'] as $key => $label): ?><option value="<?= $escape($key) ?>" <?= ($settings['hero_style'] ?? 'centered') === $key ? 'selected' : '' ?>><?= $escape($label) ?></option><?php endforeach; ?></select>
            </label>
          </div>

          <div class="ap-form-heading"><h3>Sections shown</h3><span>Toggle what visitors see</span></div>
          <div class="ap-switches">
            <label class="ap-switch"><input type="hidden" name="show_projects" value="0"><input type="checkbox" name="show_projects" value="1" <?= $settingsShowProjects ? 'checked' : '' ?>><span class="ap-switch-ui" aria-hidden="true"></span><span><strong>Projects section</strong><small>Your selected work</small></span></label>
            <label class="ap-switch"><input type="hidden" name="show_contact" value="0"><input type="checkbox" name="show_contact" value="1" <?= $settingsShowContact ? 'checked' : '' ?>><span class="ap-switch-ui" aria-hidden="true"></span><span><strong>Contact section</strong><small>Email and social links</small></span></label>
          </div>
          <div class="ap-form-foot"><button class="ap-button ap-button-primary" type="submit"><i data-lucide="save"></i>Save design</button></div>
        </form>
      </section>

      <?php if ($isNoir): ?>
      <!-- Noir images -->
      <section class="ap-panel" role="tabpanel" data-ap-tab-panel="noir-images">
        <div class="ap-panel-heading">
          <div><p class="ap-kicker">Noir visuals</p><h2>Section images</h2><p>Each Noir section starts with an original editorial visual. Upload an image to replace it, or restore the default at any time.</p></div>
        </div>
        <div class="ap-noir-image-grid">
          <?php foreach (array_merge($sectionLabels, ['projects' => 'Projects']) as $sectionKey => $sectionLabel):
              $customPath = $noirImageBySection[$sectionKey] ?? '';
              $imageUrl = $customPath !== '' ? $baseUrl . '/' . $customPath : $baseUrl . '/assets/portfolio-template/noir-images/' . ($noirDefaults[$sectionKey] ?? 'work.svg'); ?>
            <article class="ap-noir-image-card">
              <img src="<?= $escape($imageUrl) ?>" alt="<?= $escape($sectionLabel) ?> visual preview">
              <div>
                <div class="ap-form-heading"><h3><?= $escape($sectionLabel) ?></h3><span class="ap-badge <?= $customPath !== '' ? 'ap-badge--on' : '' ?>"><?= $customPath !== '' ? 'Custom image' : 'Noir default' ?></span></div>
                <form method="POST" enctype="multipart/form-data" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/noir-image/upload') ?>" class="ap-form" data-no-dirty>
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $websiteId ?>">
                  <input type="hidden" name="tab" value="noir-images">
                  <input type="hidden" name="section_key" value="<?= $escape($sectionKey) ?>">
                  <label class="ap-field"><span class="ap-label">Replace image</span><input type="file" name="noir_image" accept="image/jpeg,image/png,image/webp" required></label>
                  <button class="ap-button ap-button-primary" type="submit"><i data-lucide="upload"></i><?= $customPath !== '' ? 'Replace' : 'Upload' ?></button>
                </form>
                <?php if ($customPath !== ''): ?>
                  <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/noir-image/remove') ?>" class="ap-noir-remove">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $websiteId ?>">
                    <input type="hidden" name="tab" value="noir-images">
                    <input type="hidden" name="section_key" value="<?= $escape($sectionKey) ?>">
                    <button class="ap-button ap-button-quiet" type="submit"><i data-lucide="rotate-ccw"></i>Use default</button>
                  </form>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- Content sections -->
      <?php foreach ($sectionLabels as $sectionKey => $sectionLabel):
          $sectionItems = array_values(array_filter($items, static fn (array $item): bool => ($item['section_key'] ?? '') === $sectionKey));
          $fields = $sectionFields[$sectionKey] ?? [['title', 'Title', true], ['details', 'Details (optional)', false, 'textarea']];
          $hints = $sectionHints[$sectionKey] ?? [$sectionLabel, 'Add details (optional)', ''];
          $noun = strtolower($hints[0]);
          $multipart = $sectionKey === 'cv' ? 'enctype="multipart/form-data"' : '';
      ?>
      <section class="ap-panel" role="tabpanel" data-ap-tab-panel="<?= $escape($sectionKey) ?>">
        <div class="ap-panel-heading">
          <div><p class="ap-kicker"><?= $escape($sectionLabel) ?></p><h2><?= $escape($sectionLabel) ?></h2><p><?= $escape($sectionBlurbs[$sectionKey] ?? 'Add entries using fields relevant to this section.') ?></p></div>
          <span class="ap-count"><?= count($sectionItems) ?> saved</span>
        </div>

        <details class="ap-add" <?= $sectionItems === [] ? 'open' : '' ?>>
          <summary class="ap-button ap-button-primary"><i data-lucide="plus"></i><span class="ap-when-closed">Add <?= $escape($noun) ?></span><span class="ap-when-open">Close form</span></summary>
          <form method="POST" <?= $multipart ?> action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/save') ?>" class="ap-card ap-form">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $websiteId ?>">
            <input type="hidden" name="tab" value="<?= $escape($sectionKey) ?>">
            <input type="hidden" name="section_key" value="<?= $escape($sectionKey) ?>">
            <div class="ap-form-heading"><h3>New <?= $escape($noun) ?></h3><span><?= $escape($hints[2]) ?></span></div>
            <div class="ap-field-grid"><?php foreach ($fields as $field) { echo $renderField($field, '', [], false, $hints[1]); } ?></div>
            <div class="ap-form-foot"><button class="ap-button ap-button-primary" type="submit"><i data-lucide="plus"></i>Add entry</button></div>
          </form>
        </details>

        <div class="ap-item-list">
          <?php if ($sectionItems === []): ?>
            <div class="ap-empty"><i data-lucide="inbox"></i><strong>No <?= $escape(strtolower($sectionLabel)) ?> yet</strong><p><?= $escape($hints[1]) ?>. Use the form above to add your first entry.</p></div>
          <?php endif; ?>
          <?php foreach ($sectionItems as $item):
              $payload = json_decode((string) ($item['payload'] ?? '{}'), true);
              $payload = is_array($payload) ? $payload : [];
              $itemId = (int) $item['id'];
              $enabled = !empty($item['is_enabled']); ?>
            <details class="ap-item">
              <summary>
                <span class="ap-item-title"><strong><?= $escape($item['title'] ?? '') ?></strong><small><?= $escape($payload['organisation'] ?? $payload['category'] ?? '') ?></small></span>
                <span class="ap-item-meta"><span class="ap-badge <?= $enabled ? 'ap-badge--on' : '' ?>"><?= $enabled ? 'Visible' : 'Hidden' ?></span><span class="ap-edit">Edit <i data-lucide="chevron-down"></i></span></span>
              </summary>
              <div class="ap-item-body">
                <form method="POST" <?= $multipart ?> action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/save') ?>" class="ap-form">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $websiteId ?>">
                  <input type="hidden" name="tab" value="<?= $escape($sectionKey) ?>">
                  <input type="hidden" name="item_id" value="<?= $itemId ?>">
                  <input type="hidden" name="section_key" value="<?= $escape($sectionKey) ?>">
                  <div class="ap-field-grid">
                    <?php foreach ($fields as $field) {
                        $value = $field[0] === 'title' ? ($item['title'] ?? '') : ($payload[$field[0]] ?? '');
                        echo $renderField($field, $value, $payload, true, $hints[1]);
                    } ?>
                  </div>
                  <label class="ap-field ap-order"><span class="ap-label">Display order</span><input type="number" name="sort_order" min="0" max="9999" value="<?= (int) ($item['sort_order'] ?? 0) ?>"></label>
                  <div class="ap-form-foot"><button class="ap-button ap-button-primary" type="submit"><i data-lucide="save"></i>Save entry</button></div>
                </form>
                <div class="ap-item-actions">
                  <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/toggle') ?>" data-no-dirty>
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $websiteId ?>">
                    <input type="hidden" name="tab" value="<?= $escape($sectionKey) ?>">
                    <input type="hidden" name="item_id" value="<?= $itemId ?>">
                    <input type="hidden" name="enabled" value="<?= $enabled ? '0' : '1' ?>">
                    <button class="ap-button ap-button-quiet" type="submit"><i data-lucide="<?= $enabled ? 'eye-off' : 'eye' ?>"></i><?= $enabled ? 'Hide entry' : 'Show entry' ?></button>
                  </form>
                  <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/delete') ?>" onsubmit="return confirm('Delete this portfolio entry?')" data-no-dirty>
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $websiteId ?>">
                    <input type="hidden" name="tab" value="<?= $escape($sectionKey) ?>">
                    <input type="hidden" name="item_id" value="<?= $itemId ?>">
                    <button class="ap-button ap-button-danger" type="submit"><i data-lucide="trash-2"></i>Delete</button>
                  </form>
                </div>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>

      <!-- Projects -->
      <section class="ap-panel" role="tabpanel" data-ap-tab-panel="projects">
        <div class="ap-panel-heading">
          <div><p class="ap-kicker">Selected work</p><h2>Projects</h2><p>Show visitors the work you are proud of.</p></div>
          <span class="ap-count"><?= count($projects) ?> saved</span>
        </div>
        <details class="ap-add" <?= $projects === [] ? 'open' : '' ?>>
          <summary class="ap-button ap-button-primary"><i data-lucide="plus"></i><span class="ap-when-closed">Add project</span><span class="ap-when-open">Close form</span></summary>
          <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/projects/add') ?>" class="ap-card ap-form">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $websiteId ?>">
            <input type="hidden" name="return_to" value="advanced">
            <input type="hidden" name="return_tab" value="projects">
            <div class="ap-form-heading"><h3>New project</h3><span>Selected work</span></div>
            <div class="ap-field-grid">
              <label class="ap-field"><span class="ap-label">Project title <em aria-hidden="true">*</em></span><input name="project_title" maxlength="150" required></label>
              <label class="ap-field"><span class="ap-label">Project link (optional)</span><input type="url" name="project_url" maxlength="500" placeholder="https://example.com"></label>
            </div>
            <label class="ap-field"><span class="ap-label">Short description</span><textarea name="project_summary" rows="3" maxlength="5000" placeholder="What it is, what you did, and the result."></textarea></label>
            <div class="ap-form-foot"><button class="ap-button ap-button-primary" type="submit"><i data-lucide="plus"></i>Add project</button></div>
          </form>
        </details>

        <div class="ap-item-list">
          <?php if ($projects === []): ?>
            <div class="ap-empty"><i data-lucide="briefcase-business"></i><strong>No projects yet</strong><p>Add a project above to populate your public work section.</p></div>
          <?php endif; ?>
          <?php foreach ($projects as $project): $projectId = (int) $project['id']; ?>
            <details class="ap-item">
              <summary>
                <span class="ap-item-title"><strong><?= $escape($project['title'] ?? '') ?></strong><small><?= $escape($project['project_url'] ?? '') ?></small></span>
                <span class="ap-item-meta"><span class="ap-edit">Edit <i data-lucide="chevron-down"></i></span></span>
              </summary>
              <div class="ap-item-body">
                <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/projects/save') ?>" class="ap-form">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $websiteId ?>">
                  <input type="hidden" name="project_id" value="<?= $projectId ?>">
                  <input type="hidden" name="return_to" value="advanced">
                  <input type="hidden" name="return_tab" value="projects">
                  <div class="ap-field-grid">
                    <label class="ap-field"><span class="ap-label">Project title <em aria-hidden="true">*</em></span><input name="project_title" maxlength="150" required value="<?= $escape($project['title'] ?? '') ?>"></label>
                    <label class="ap-field"><span class="ap-label">Project link</span><input type="url" name="project_url" maxlength="500" value="<?= $escape($project['project_url'] ?? '') ?>"></label>
                  </div>
                  <label class="ap-field"><span class="ap-label">Description</span><textarea name="project_summary" rows="3" maxlength="5000"><?= $escape($project['summary'] ?? '') ?></textarea></label>
                  <label class="ap-field ap-order"><span class="ap-label">Display order</span><input type="number" name="sort_order" min="0" max="9999" value="<?= (int) ($project['sort_order'] ?? 0) ?>"></label>
                  <div class="ap-form-foot"><button class="ap-button ap-button-primary" type="submit"><i data-lucide="save"></i>Save project</button></div>
                </form>
                <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/projects/delete') ?>" class="ap-item-actions" onsubmit="return confirm('Remove this project?')" data-no-dirty>
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $websiteId ?>">
                  <input type="hidden" name="project_id" value="<?= $projectId ?>">
                  <input type="hidden" name="return_to" value="advanced">
                  <input type="hidden" name="return_tab" value="projects">
                  <button class="ap-button ap-button-danger" type="submit"><i data-lucide="trash-2"></i>Remove project</button>
                </form>
              </div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </div>
</section>

<div class="ap-ai-modal" data-ai-modal hidden aria-hidden="true">
  <section class="ap-ai-dialog" role="dialog" aria-modal="true" aria-labelledby="ap-ai-title">
    <header class="ap-ai-head">
      <div><p class="ap-kicker">Portfolio assistant</p><h2 id="ap-ai-title">Enhance with AI</h2><p>Review every suggestion before it changes your portfolio.</p></div>
      <button class="ap-ai-close" type="button" aria-label="Close AI Enhance" data-ai-close><i data-lucide="x"></i></button>
    </header>
    <form class="ap-ai-form" data-ai-form>
      <?= \App\Core\Csrf::field() ?>
      <input type="hidden" name="id" value="<?= $websiteId ?>">
      <label class="ap-field"><span class="ap-label">What would you like to do?</span>
        <select name="operation" data-ai-operation>
          <option value="enhance_portfolio">Enhance my whole portfolio</option>
          <option value="generate_items">Add new entries to a section</option>
          <option value="rewrite_profile">Rewrite my profile text</option>
          <option value="rewrite_item">Rewrite an existing entry</option>
        </select>
      </label>
      <div class="ap-field-grid" data-ai-generate-fields>
        <label class="ap-field"><span class="ap-label">Portfolio section</span>
          <select name="section_key" data-ai-section>
            <?php foreach ($sectionLabels as $sectionKey => $sectionLabel): if ($sectionKey === 'cv') continue; ?>
              <option value="<?= $escape($sectionKey) ?>"><?= $escape($sectionLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="ap-field"><span class="ap-label">How many?</span>
          <select name="count"><option value="1">1 suggestion</option><option value="2" selected>2 suggestions</option><option value="3">3 suggestions</option></select>
        </label>
      </div>
      <label class="ap-field" data-ai-profile-field><span class="ap-label">Profile text</span>
        <select name="field"><option value="bio">About you</option><option value="headline">Headline / role</option></select>
      </label>
      <label class="ap-field" data-ai-item-field><span class="ap-label">Existing entry</span>
        <select name="item_id" data-ai-item>
          <?php foreach ($items as $item): $itemSection = (string) ($item['section_key'] ?? ''); if (!in_array($itemSection, $aiRewritableSections, true)) continue; ?>
            <option value="<?= (int) ($item['id'] ?? 0) ?>" data-ai-section="<?= $escape($itemSection) ?>"><?= $escape(($sectionLabels[$itemSection] ?? ucfirst($itemSection)) . ' — ' . ($item['title'] ?? 'Untitled entry')) ?></option>
          <?php endforeach; ?>
        </select>
        <small class="ap-note" data-ai-item-empty hidden>Add a written entry first, then AI can help refine it.</small>
      </label>
      <label class="ap-field"><span class="ap-label">Tone</span>
        <select name="tone"><option value="professional">Professional</option><option value="warm">Warm and approachable</option><option value="concise">Concise</option></select>
      </label>
      <label class="ap-field"><span class="ap-label">Context or instructions <span class="ap-optional">(optional)</span></span>
        <textarea name="instructions" rows="4" maxlength="2000" placeholder="For example: emphasise my problem-solving work, use a confident but friendly tone, and do not add facts not already in my portfolio."></textarea>
      </label>
      <div class="ap-ai-provider">
        <label class="ap-field"><span class="ap-label">AI provider</span>
          <select name="provider" data-ai-provider><option value="openrouter">OpenRouter (Free)</option><option value="openai">OpenAI (use your API key)</option><option value="gemini">Google Gemini (use your API key)</option></select>
        </label>
        <label class="ap-field"><span class="ap-label">API key <span class="ap-optional">(only if needed)</span></span>
          <input type="password" name="api_key" data-ai-key maxlength="500" autocomplete="off" spellcheck="false" placeholder="Paste your own provider key for this request only">
          <small class="ap-note" data-ai-key-note></small>
        </label>
      </div>
      <p class="ap-ai-status" data-ai-status role="status" aria-live="polite"></p>
      <div class="ap-ai-actions"><button class="ap-button ap-button-primary" type="submit" data-ai-generate><i data-lucide="sparkles"></i>Generate suggestions</button></div>
    </form>
    <section class="ap-ai-result" data-ai-result hidden aria-live="polite">
      <div class="ap-ai-result-head"><div><p class="ap-kicker">Suggestion</p><h3>Review before applying</h3></div><span class="ap-ai-ready">Nothing is saved yet</span></div>
      <div class="ap-ai-output" data-ai-output></div>
      <div class="ap-ai-actions"><button class="ap-button ap-button-primary" type="button" data-ai-apply><i data-lucide="check"></i>Apply suggestion</button></div>
    </section>
  </section>
</div>

<style>
.ap-studio{--ap-ink:#17191d;--ap-muted:#667085;--ap-line:#e4e7ec;--ap-soft:#f8fafc;--ap-accent:#f97316;--ap-accent-ink:#9a3412;--ap-accent-soft:#fff4ea;--ap-ok:#166534;--ap-ok-soft:#e8f7ee;--ap-danger:#b42318;color:var(--ap-ink)}
.ap-studio *{box-sizing:border-box}
.ap-studio .ap-visually-hidden{position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0}

/* header */
.ap-header{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.5rem}
.ap-eyebrow,.ap-kicker{margin:0 0 .5rem;font-size:.72rem;font-weight:800;letter-spacing:.15em;text-transform:uppercase;color:var(--ap-muted)}
.ap-header h1{margin:0;font-size:clamp(1.8rem,3vw,2.5rem);font-weight:800;letter-spacing:-.04em}
.ap-sub{margin:.4rem 0 0;color:var(--ap-muted)}
.ap-header-actions{display:flex;flex-wrap:wrap;gap:.65rem}

/* buttons */
.ap-button{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;min-height:2.6rem;padding:.55rem 1rem;border:1px solid transparent;border-radius:.65rem;font:700 .85rem/1 inherit;font-family:inherit;text-decoration:none;cursor:pointer;transition:background-color .15s,border-color .15s,box-shadow .15s}
.ap-button svg{width:1rem;height:1rem}
.ap-button-primary{background:var(--ap-ink);color:#fff}
.ap-button-primary:hover{background:#2b3038;color:#fff}
.ap-button-quiet{background:#fff;color:var(--ap-ink);border-color:#d0d5dd}
.ap-button-quiet:hover{background:var(--ap-soft);border-color:#98a2b3;color:var(--ap-ink)}
.ap-button-danger{background:#fff;color:var(--ap-danger);border-color:#fecdca}
.ap-button-danger:hover{background:#fef3f2;border-color:#fda29b}
.ap-button-ai{background:#fff7ed;color:#9a3412;border-color:#fdba74}
.ap-button-ai:hover{background:#ffedd5;color:#7c2d12;border-color:#fb923c}
.ap-studio a:focus-visible,.ap-studio button:focus-visible,.ap-studio summary:focus-visible{outline:3px solid #f9731666;outline-offset:2px}

/* shell */
.ap-shell{display:grid;grid-template-columns:15rem minmax(0,1fr);gap:1.5rem;align-items:start}
.ap-nav{position:sticky;top:1rem;display:flex;flex-direction:column;gap:.15rem;padding:.75rem;background:var(--bs-body-bg,#fff);border:1px solid var(--ap-line);border-radius:1rem;max-height:calc(100vh - 2rem);overflow:auto}
.ap-nav-group{margin:.8rem .6rem .3rem;font-size:.66rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--ap-muted)}
.ap-nav-group:first-child{margin-top:.2rem}
.ap-tab{position:relative;display:flex;align-items:center;gap:.6rem;width:100%;padding:.6rem .7rem;border:0;border-radius:.6rem;background:transparent;color:#475467;font:700 .84rem/1.2 inherit;font-family:inherit;text-align:left;cursor:pointer}
.ap-tab svg{flex:0 0 auto;width:1rem;height:1rem}
.ap-tab-text{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ap-tab-count{flex:0 0 auto;min-width:1.35rem;padding:.1rem .35rem;border-radius:999px;background:#f2f4f7;color:#475467;font-size:.68rem;text-align:center}
.ap-tab:hover{background:var(--ap-soft);color:var(--ap-ink)}
.ap-tab.is-active{background:var(--ap-accent-soft);color:var(--ap-accent-ink)}
.ap-tab.is-active .ap-tab-count{background:#fff;color:var(--ap-accent-ink)}
.ap-tab.is-dirty:after{content:'';position:absolute;top:.55rem;right:.5rem;width:.5rem;height:.5rem;border-radius:50%;background:var(--ap-accent)}

/* panels */
.ap-panels{min-width:0}
.ap-panel{display:none}
.ap-panel.is-active{display:block}
.ap-panel-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.1rem}
.ap-panel-heading h2{margin:0 0 .35rem;font-size:1.5rem;font-weight:800;letter-spacing:-.03em}
.ap-panel-heading p:not(.ap-kicker){margin:0;max-width:42rem;color:var(--ap-muted);line-height:1.6}
.ap-count{flex:0 0 auto;padding:.35rem .65rem;border:1px solid var(--ap-line);border-radius:999px;color:var(--ap-muted);font-size:.75rem;font-weight:700;background:#fff}
.ap-card,.ap-empty,.ap-item,.ap-stats article{background:var(--bs-body-bg,#fff);border:1px solid var(--ap-line);border-radius:1rem}
.ap-card{padding:clamp(1rem,2.5vw,1.5rem);box-shadow:0 .6rem 1.8rem rgba(15,23,42,.04)}
.ap-grid{display:grid;gap:1.25rem}
.ap-grid-profile{grid-template-columns:minmax(0,1.8fr) minmax(15rem,.8fr);align-items:start}

/* overview */
.ap-check-head{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem}
.ap-check-head h3,.ap-form-heading h3{margin:0;font-size:1rem;font-weight:800}
.ap-check-head p{margin:.2rem 0 0;color:var(--ap-muted);font-size:.84rem}
.ap-meter{flex:0 1 12rem;width:12rem;height:.45rem;border-radius:9px;background:#eceff3;overflow:hidden}
.ap-meter i{display:block;height:100%;border-radius:9px;background:var(--ap-accent)}
.ap-checklist ul{margin:0;padding:0;list-style:none;display:grid;gap:.15rem}
.ap-checklist li{display:flex;align-items:center;gap:.75rem;padding:.65rem .25rem;border-top:1px solid var(--ap-line)}
.ap-checklist li:first-child{border-top:0}
.ap-tick{flex:0 0 auto;display:grid;place-items:center;width:1.4rem;height:1.4rem;border:1.5px solid #d0d5dd;border-radius:50%;font-size:.75rem;font-weight:900;color:#fff}
.is-done .ap-tick{background:#16a34a;border-color:#16a34a}
.ap-check-text{flex:1 1 auto;font-size:.9rem;font-weight:600}
.is-done .ap-check-text{color:var(--ap-muted);text-decoration:line-through}
.ap-link{border:0;background:none;padding:.3rem .5rem;color:var(--ap-accent-ink);font:800 .82rem/1 inherit;font-family:inherit;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
.ap-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.85rem;margin-top:1rem}
.ap-stats article{padding:1rem}
.ap-stats article>svg{width:1.2rem;height:1.2rem;margin-bottom:.7rem;color:var(--ap-accent)}
.ap-stats span{display:block;color:var(--ap-muted);font-size:.78rem}
.ap-stats strong{display:block;margin-top:.3rem;font-size:1.35rem;letter-spacing:-.02em}

/* forms */
.ap-form{display:grid;gap:1rem}
.ap-form-heading{display:flex;justify-content:space-between;align-items:center;gap:.75rem}
.ap-form-heading span{color:var(--ap-muted);font-size:.74rem}
.ap-field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
.ap-field{display:grid;gap:.4rem;align-content:start;min-width:0}
.ap-wide{grid-column:1/-1}
.ap-label{font-size:.82rem;font-weight:700;color:#344054}
.ap-label em{color:var(--ap-danger);font-style:normal}
.ap-studio input:not([type=checkbox]):not([type=color]):not([type=file]),.ap-studio select,.ap-studio textarea{display:block;width:100%;padding:.75rem .85rem;border:1px solid #d0d5dd;border-radius:.65rem;background:var(--bs-body-bg,#fff);color:inherit;font:400 .92rem/1.4 inherit;font-family:inherit}
.ap-studio input[type=file]{width:100%;padding:.5rem;border:1px dashed #98a2b3;border-radius:.65rem;font-size:.82rem;background:var(--ap-soft)}
.ap-studio textarea{resize:vertical;min-height:5rem}
.ap-studio input:focus,.ap-studio select:focus,.ap-studio textarea:focus{outline:3px solid #f9731626;border-color:var(--ap-accent)}
.ap-note{margin:0;color:var(--ap-muted);font-size:.76rem;line-height:1.45}
.ap-check{grid-column:1/-1;display:flex;align-items:center;gap:.6rem;font-size:.88rem;font-weight:600;cursor:pointer}
.ap-check input{width:1.1rem;height:1.1rem;accent-color:var(--ap-accent)}
.ap-fieldset{margin:0;padding:1rem;border:1px solid var(--ap-line);border-radius:.8rem;min-width:0}
.ap-fieldset legend{float:none;width:auto;margin:0;padding:0 .35rem;font-size:.8rem;font-weight:800;color:#344054}
.ap-form-foot{display:flex;justify-content:flex-end}
.ap-order{max-width:10rem}

/* design */
.ap-colours{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.85rem}
.ap-colour{display:grid;gap:.5rem;padding:.85rem;border:1px solid var(--ap-line);border-radius:.8rem;background:var(--ap-soft);cursor:pointer}
.ap-colour-row{display:flex;align-items:center;gap:.65rem}
.ap-colour input[type=color]{width:2.6rem;height:2.6rem;padding:.15rem;border:1px solid #d0d5dd;border-radius:.5rem;background:#fff;cursor:pointer}
.ap-colour output{font:600 .82rem/1 ui-monospace,SFMono-Regular,Menlo,monospace;color:#475467;text-transform:uppercase}
.ap-contrast{padding:.25rem .55rem;border-radius:999px;font-size:.72rem;font-weight:800;background:#f2f4f7;color:#475467}
.ap-contrast[data-tone="good"]{background:var(--ap-ok-soft);color:var(--ap-ok)}
.ap-contrast[data-tone="low"]{background:#fef3f2;color:var(--ap-danger)}
.ap-switches{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem}
.ap-switch{position:relative;display:flex;align-items:center;gap:.8rem;padding:.9rem;border:1px solid var(--ap-line);border-radius:.8rem;cursor:pointer}
.ap-switch input[type=checkbox]{position:absolute;opacity:0;width:1px;height:1px}
.ap-switch-ui{flex:0 0 auto;position:relative;width:2.5rem;height:1.45rem;border-radius:999px;background:#d0d5dd;transition:background-color .15s}
.ap-switch-ui:after{content:'';position:absolute;top:.17rem;left:.17rem;width:1.11rem;height:1.11rem;border-radius:50%;background:#fff;box-shadow:0 .1rem .3rem #0003;transition:transform .15s}
.ap-switch input:checked ~ .ap-switch-ui{background:var(--ap-accent)}
.ap-switch input:checked ~ .ap-switch-ui:after{transform:translateX(1.05rem)}
.ap-switch input:focus-visible ~ .ap-switch-ui{outline:3px solid #f9731666;outline-offset:2px}
.ap-switch strong{display:block;font-size:.88rem}
.ap-switch small{display:block;color:var(--ap-muted);font-size:.76rem}

/* avatar */
.ap-avatar-card{position:sticky;top:1rem;display:grid;gap:.85rem}
.ap-avatar-preview{display:grid;place-items:center;gap:.5rem;aspect-ratio:4/5;background:var(--ap-soft);border:1px dashed #98a2b3;border-radius:999px 999px .9rem .9rem;color:var(--ap-muted);font-size:.82rem;overflow:hidden}
.ap-avatar-preview svg{width:2rem;height:2rem}
.ap-avatar-preview img{width:100%;height:100%;object-fit:cover}
.ap-avatar-card form{display:grid;gap:.65rem}
.ap-upload{display:block;padding:.65rem;text-align:center;border:1px dashed #98a2b3;border-radius:.65rem;font-size:.85rem;font-weight:700;color:#344054;cursor:pointer}
.ap-upload:hover{background:var(--ap-soft)}
.ap-avatar-card form:focus-within .ap-upload{outline:3px solid #f9731640;outline-offset:2px}

/* lists */
.ap-add{margin-bottom:1rem}
.ap-add>summary{list-style:none;width:fit-content;margin-bottom:.9rem}
.ap-add>summary::-webkit-details-marker{display:none}
.ap-add .ap-when-open{display:none}
.ap-add[open] .ap-when-closed{display:none}
.ap-add[open] .ap-when-open{display:inline}
.ap-add[open]>summary{background:#fff;color:var(--ap-ink);border-color:#d0d5dd}
.ap-item-list{display:grid;gap:.7rem}
.ap-empty{display:grid;place-items:center;padding:2.5rem 1rem;color:var(--ap-muted);text-align:center;border-style:dashed}
.ap-empty svg{width:2rem;height:2rem}
.ap-empty strong{margin-top:.6rem;color:var(--ap-ink)}
.ap-empty p{margin:.3rem 0 0;font-size:.85rem}
.ap-item{overflow:hidden}
.ap-item summary{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.9rem 1.1rem;cursor:pointer;list-style:none}
.ap-item summary::-webkit-details-marker{display:none}
.ap-item summary:hover{background:var(--ap-soft)}
.ap-item[open] summary{border-bottom:1px solid var(--ap-line)}
.ap-item-title{display:grid;min-width:0}
.ap-item-title strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ap-item-title small{color:var(--ap-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ap-item-meta{display:flex;align-items:center;gap:.75rem;flex:0 0 auto}
.ap-edit{display:inline-flex;align-items:center;gap:.3rem;color:var(--ap-muted);font-size:.78rem;font-weight:700}
.ap-edit svg{width:1rem;height:1rem;transition:transform .15s}
.ap-item[open] .ap-edit svg{transform:rotate(180deg)}
.ap-badge{padding:.2rem .5rem;border-radius:999px;background:#f2f4f7;color:#475467;font-size:.68rem;font-weight:800}
.ap-badge--on{background:var(--ap-ok-soft);color:var(--ap-ok)}
.ap-item-body{display:grid;gap:1rem;padding:1.1rem}
.ap-item-actions{display:flex;flex-wrap:wrap;gap:.6rem;padding-top:1rem;border-top:1px solid var(--ap-line)}

/* noir images */
.ap-noir-image-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
.ap-noir-image-card{display:grid;grid-template-columns:8rem minmax(0,1fr);gap:1rem;padding:1rem;background:var(--bs-body-bg,#fff);border:1px solid var(--ap-line);border-radius:1rem}
.ap-noir-image-card>img{width:100%;height:100%;min-height:8rem;object-fit:cover;background:var(--ap-soft);border:1px solid var(--ap-line);border-radius:.6rem}
.ap-noir-image-card .ap-form{gap:.65rem;margin-top:.75rem}
.ap-noir-remove{margin-top:.65rem}

/* AI review dialog */
.ap-ai-modal[hidden],.ap-ai-result[hidden]{display:none}
.ap-ai-modal{--ap-ink:#17191d;--ap-muted:#667085;--ap-line:#e4e7ec;--ap-soft:#f8fafc;--ap-accent:#f97316;--ap-danger:#b42318;position:fixed;z-index:1060;inset:0;display:grid;place-items:center;padding:1rem;background:rgba(15,23,42,.54);overflow:auto}
.ap-ai-dialog{width:min(100%,44rem);max-height:calc(100vh - 2rem);overflow:auto;padding:clamp(1rem,3vw,1.6rem);border:1px solid #e4e7ec;border-radius:1rem;background:var(--bs-body-bg,#fff);box-shadow:0 1.5rem 4rem rgba(15,23,42,.3);color:var(--ap-ink)}
.ap-ai-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding-bottom:1rem;border-bottom:1px solid var(--ap-line)}
.ap-ai-head h2{margin:0;font-size:1.35rem;font-weight:800;letter-spacing:-.025em}.ap-ai-head p:not(.ap-kicker){margin:.3rem 0 0;color:var(--ap-muted);font-size:.88rem}
.ap-ai-close{display:grid;place-items:center;flex:0 0 auto;width:2.25rem;height:2.25rem;border:1px solid var(--ap-line);border-radius:.6rem;background:#fff;color:#475467;cursor:pointer}.ap-ai-close:hover{background:var(--ap-soft)}.ap-ai-close svg{width:1.1rem;height:1.1rem}
.ap-ai-form{display:grid;gap:1rem;padding-top:1rem}.ap-ai-provider{display:grid;grid-template-columns:9rem minmax(0,1fr);gap:1rem;align-items:start}.ap-optional{color:var(--ap-muted);font-weight:500}.ap-ai-status{min-height:1.25rem;margin:0;color:var(--ap-muted);font-size:.84rem;line-height:1.45}.ap-ai-status.is-error{color:var(--ap-danger)}.ap-ai-status.is-loading{color:#9a3412}
.ap-ai-actions{display:flex;justify-content:flex-end;gap:.65rem}.ap-ai-actions .ap-button:disabled{cursor:wait;opacity:.65}.ap-ai-result{margin-top:1rem;padding:1rem;border:1px solid #fed7aa;border-radius:.85rem;background:#fffaf5}.ap-ai-result-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start}.ap-ai-result-head h3{margin:0;font-size:1rem;font-weight:800}.ap-ai-ready{flex:0 0 auto;padding:.25rem .5rem;border-radius:999px;background:#fff0df;color:#9a3412;font-size:.68rem;font-weight:800}.ap-ai-output{display:grid;gap:.75rem;margin:1rem 0}.ap-ai-summary{margin:0;color:var(--ap-muted);font-size:.86rem}.ap-ai-rewrite{margin:0;padding:.85rem;border-left:3px solid var(--ap-accent);border-radius:.35rem;background:#fff;white-space:pre-wrap;line-height:1.6}.ap-ai-suggestion-select{display:flex;align-items:center;gap:.65rem;margin-bottom:.8rem;cursor:pointer}.ap-ai-suggestion-select input{width:1rem;height:1rem;accent-color:#f97316}.ap-ai-edit-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}.ap-ai-edit-field{display:grid;gap:.35rem;min-width:0}.ap-ai-edit-field>span{font-size:.78rem;font-weight:750;color:#475467}.ap-ai-edit-field input,.ap-ai-edit-field textarea{width:100%;min-width:0;padding:.65rem .75rem;border:1px solid #d0d5dd;border-radius:.55rem;background:#fff;color:#17191d;font:inherit;font-size:.88rem}.ap-ai-edit-field textarea{resize:vertical}.ap-ai-edit-field input[type=checkbox]{width:auto;min-height:1rem;padding:0;accent-color:#f97316}.ap-ai-edit-wide{grid-column:1/-1}.ap-ai-edit-field input:focus,.ap-ai-edit-field textarea:focus{outline:2px solid #f9731666;border-color:#f97316}.ap-ai-result [data-ai-apply]:disabled{opacity:.6;cursor:not-allowed}.ap-ai-suggestion{padding:.85rem;border:1px solid #fed7aa;border-radius:.65rem;background:#fff}.ap-ai-suggestion strong{display:block}.ap-ai-suggestion p{margin:.35rem 0 0;color:#475467;white-space:pre-wrap;line-height:1.5}

@media(max-width:1000px){
  .ap-shell{grid-template-columns:minmax(0,1fr)}
  .ap-nav{position:sticky;top:0;z-index:5;flex-direction:row;gap:.3rem;padding:.5rem;border-radius:.8rem;overflow-x:auto;max-height:none}
  .ap-nav-group{display:none}
  .ap-tab{width:auto;flex:0 0 auto}
  .ap-grid-profile{grid-template-columns:minmax(0,1fr)}
  .ap-avatar-card{position:static}
  .ap-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
  .ap-noir-image-grid{grid-template-columns:minmax(0,1fr)}
  .ap-ai-provider{grid-template-columns:minmax(0,1fr)}
}
@media(max-width:640px){
  .ap-ai-edit-grid{grid-template-columns:minmax(0,1fr)}
  .ap-field-grid,.ap-colours,.ap-switches{grid-template-columns:minmax(0,1fr)}
  .ap-panel-heading{flex-direction:column}
  .ap-form-foot .ap-button,.ap-item-actions .ap-button,.ap-item-actions form{width:100%}
  .ap-item summary{padding:.8rem}
  .ap-item-body{padding:.85rem}
  .ap-badge{display:none}
  .ap-noir-image-card{grid-template-columns:minmax(0,1fr)}
  .ap-noir-image-card>img{max-height:12rem}
}
@media(prefers-reduced-motion:reduce){
  .ap-studio *{transition:none}
}
</style>

<script>
(() => {
  const studio = document.querySelector('[data-advanced-studio]');
  if (!studio) return;

  /* ---------- tabs ---------- */
  const tabs = Array.from(studio.querySelectorAll('.ap-nav [data-ap-tab-button]'));
  const valid = new Set(Array.from(studio.querySelectorAll('[data-ap-tab-panel]')).map(el => el.dataset.apTabPanel));
  const url = new URL(window.location.href);

  function activate(tab, moveFocus) {
    tab = valid.has(tab) ? tab : 'overview';
    studio.querySelectorAll('[data-ap-tab-panel]').forEach(el => el.classList.toggle('is-active', el.dataset.apTabPanel === tab));
    studio.querySelectorAll('[data-ap-tab-button]').forEach(el => {
      const on = el.dataset.apTabButton === tab;
      el.classList.toggle('is-active', on);
      if (el.getAttribute('role') === 'tab') {
        el.setAttribute('aria-selected', on ? 'true' : 'false');
        el.tabIndex = on ? 0 : -1;
      }
    });
    url.searchParams.set('tab', tab);
    history.replaceState({}, '', url);
    if (moveFocus) { const active = tabs.find(t => t.dataset.apTabButton === tab); if (active) active.focus(); }
    if (window.lucide) window.lucide.createIcons();
  }
  studio.querySelectorAll('[data-ap-tab-button]').forEach(button => button.addEventListener('click', () => {
    activate(button.dataset.apTabButton);
    const nav = studio.querySelector('.ap-nav');
    if (window.innerWidth <= 1000 && nav) nav.scrollIntoView({ block: 'start', behavior: 'smooth' });
  }));
  studio.querySelector('.ap-nav')?.addEventListener('keydown', event => {
    const index = tabs.findIndex(t => t === document.activeElement);
    if (index === -1) return;
    const forward = ['ArrowDown', 'ArrowRight'].includes(event.key);
    const backward = ['ArrowUp', 'ArrowLeft'].includes(event.key);
    if (!forward && !backward) return;
    event.preventDefault();
    const next = tabs[(index + (forward ? 1 : -1) + tabs.length) % tabs.length];
    activate(next.dataset.apTabButton, true);
  });
  activate(url.searchParams.get('tab') || 'overview');

  /* ---------- unsaved-changes tracking ---------- */
  let submitting = false;
  function refreshDirty() {
    tabs.forEach(t => t.classList.remove('is-dirty'));
    studio.querySelectorAll('form[data-dirty="1"]').forEach(form => {
      const panel = form.closest('[data-ap-tab-panel]');
      const tab = tabs.find(t => panel && t.dataset.apTabButton === panel.dataset.apTabPanel);
      if (tab) tab.classList.add('is-dirty');
    });
  }
  const markDirty = event => {
    const form = event.target.closest && event.target.closest('form');
    if (!form || form.hasAttribute('data-no-dirty')) return;
    form.dataset.dirty = '1';
    refreshDirty();
  };
  studio.addEventListener('input', markDirty);
  studio.addEventListener('change', markDirty);
  studio.addEventListener('submit', event => { if (!event.defaultPrevented) submitting = true; });
  window.addEventListener('beforeunload', event => {
    if (submitting || !studio.querySelector('form[data-dirty="1"]')) return;
    event.preventDefault();
    event.returnValue = '';
  });

  /* ---------- bio counter ---------- */
  const bio = studio.querySelector('textarea[name="bio"]');
  const counter = studio.querySelector('[data-counter-for="bio"]');
  if (bio && counter) {
    const update = () => { counter.textContent = bio.value.length.toLocaleString() + ' / ' + bio.maxLength.toLocaleString() + ' characters'; };
    bio.addEventListener('input', update);
    update();
  }

  /* ---------- avatar preview ---------- */
  const avatarInput = document.getElementById('ap-avatar-input');
  const avatarBox = document.getElementById('ap-avatar-box');
  const avatarSubmit = document.getElementById('ap-avatar-submit');
  if (avatarSubmit) avatarSubmit.disabled = true;
  if (avatarInput && avatarBox) avatarInput.addEventListener('change', () => {
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) { if (avatarSubmit) avatarSubmit.disabled = true; return; }
    if (file.size > 3 * 1024 * 1024) {
      alert('Photo must be smaller than 3 MB.');
      avatarInput.value = '';
      if (avatarSubmit) avatarSubmit.disabled = true;
      return;
    }
    avatarBox.innerHTML = '';
    const img = document.createElement('img');
    img.src = URL.createObjectURL(file);
    img.alt = 'Selected profile image preview';
    avatarBox.appendChild(img);
    if (avatarSubmit) avatarSubmit.disabled = false;
  });

  /* ---------- colour readouts and contrast check ---------- */
  studio.querySelectorAll('.ap-colour').forEach(label => {
    const input = label.querySelector('input[type="color"]');
    const out = label.querySelector('output');
    if (!input || !out) return;
    const show = () => { out.textContent = input.value; };
    input.addEventListener('input', show);
    show();
  });
  const bgInput = studio.querySelector('[data-contrast-bg]');
  const textInput = studio.querySelector('[data-contrast-text]');
  const badge = document.getElementById('ap-contrast');
  const luminance = hex => {
    const n = parseInt(hex.slice(1), 16);
    return [n >> 16 & 255, n >> 8 & 255, n & 255]
      .map(v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); })
      .reduce((sum, v, i) => sum + v * [0.2126, 0.7152, 0.0722][i], 0);
  };
  function updateContrast() {
    if (!bgInput || !textInput || !badge) return;
    const a = luminance(bgInput.value);
    const b = luminance(textInput.value);
    const ratio = (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
    badge.textContent = 'Text contrast ' + ratio.toFixed(1) + ':1 \u00b7 ' + (ratio >= 4.5 ? 'Good' : 'Too low');
    badge.dataset.tone = ratio >= 4.5 ? 'good' : 'low';
  }
  [bgInput, textInput].forEach(input => input && input.addEventListener('input', updateContrast));
  updateContrast();

  /* ---------- AI enhancement (review before apply; no key persistence) ---------- */
  const aiModal = document.querySelector('[data-ai-modal]');
  if (aiModal) {
    const aiStatus = <?= $aiStatusJson ?>;
    const aiOpen = studio.querySelector('[data-ai-open]');
    const aiClose = aiModal.querySelector('[data-ai-close]');
    const aiForm = aiModal.querySelector('[data-ai-form]');
    const aiOperation = aiModal.querySelector('[data-ai-operation]');
    const aiSection = aiModal.querySelector('[data-ai-section]');
    const aiItem = aiModal.querySelector('[data-ai-item]');
    const aiItemEmpty = aiModal.querySelector('[data-ai-item-empty]');
    const aiProvider = aiModal.querySelector('[data-ai-provider]');
    const aiKey = aiModal.querySelector('[data-ai-key]');
    const aiKeyNote = aiModal.querySelector('[data-ai-key-note]');
    const aiStatusMessage = aiModal.querySelector('[data-ai-status]');
    const aiGenerate = aiModal.querySelector('[data-ai-generate]');
    const aiResult = aiModal.querySelector('[data-ai-result]');
    const aiOutput = aiModal.querySelector('[data-ai-output]');
    const aiApply = aiModal.querySelector('[data-ai-apply]');
    const aiGenerateFields = aiModal.querySelector('[data-ai-generate-fields]');
    const aiProfileField = aiModal.querySelector('[data-ai-profile-field]');
    const aiItemField = aiModal.querySelector('[data-ai-item-field]');
    const proposeUrl = <?= json_encode($baseUrl . '/dashboard/websites/portfolio/ai/propose', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const applyUrl = <?= json_encode($baseUrl . '/dashboard/websites/portfolio/ai/apply', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const editorUrl = <?= json_encode($baseUrl . '/dashboard/websites/portfolio/advanced', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const editorId = <?= (int) $websiteId ?>;
    let proposal = null;
    let priorFocus = null;

    const show = element => { if (element) element.hidden = false; };
    const hide = element => { if (element) element.hidden = true; };
    const setMessage = (message, state = '') => {
      aiStatusMessage.textContent = message || '';
      aiStatusMessage.className = 'ap-ai-status' + (state ? ' is-' + state : '');
    };
    const optionForSection = () => {
      if (!aiItem) return false;
      const requested = aiSection ? aiSection.value : '';
      let first = null;
      Array.from(aiItem.options).forEach(option => {
        const usable = option.dataset.aiSection === requested;
        option.hidden = !usable;
        option.disabled = !usable;
        if (usable && !first) first = option;
      });
      if (first && aiItem.selectedOptions[0]?.disabled) aiItem.value = first.value;
      if (aiItemEmpty) aiItemEmpty.hidden = Boolean(first);
      if (aiGenerate) aiGenerate.disabled = !aiStatus.enabled || (!first && aiOperation?.value === 'rewrite_item');
      return Boolean(first);
    };
    const syncAiForm = () => {
      const operation = aiOperation?.value || 'generate_items';
      const rewritingItem = operation === 'rewrite_item';
      const generating = operation === 'generate_items';
      const enhancingWhole = operation === 'enhance_portfolio';
      aiGenerateFields.hidden = enhancingWhole || (!generating && !rewritingItem);
      if (aiGenerate) aiGenerate.lastChild.textContent = enhancingWhole ? 'Enhance whole portfolio' : 'Generate suggestions';
      if (aiApply) aiApply.lastChild.textContent = enhancingWhole ? 'Apply all improvements' : 'Apply suggestion';
      aiProfileField.hidden = operation !== 'rewrite_profile';
      aiItemField.hidden = !rewritingItem;
      if (rewritingItem) optionForSection();
      else if (aiGenerate) aiGenerate.disabled = !aiStatus.enabled;

      updateKeyNote();
    };
    const updateKeyNote = () => {
      const provider = aiProvider?.value || 'openrouter';
      const configured = Boolean(aiStatus.providers?.[provider]?.configured);
      if (!aiStatus.enabled) {
        aiKeyNote.textContent = 'AI enhancement is disabled for this installation.';
      } else if (configured) {
        aiKeyNote.textContent = 'A platform key is available. Leave this blank to use it; a pasted key is used for this request only.';
      } else if (aiStatus.allowRequestKey) {
        aiKeyNote.textContent = 'No platform key is configured. Paste your own key for this request only; it is never saved.';
      } else {
        aiKeyNote.textContent = 'No key is configured for this provider. Ask an administrator to configure one.';
      }
    };
    const closeAi = () => {
      aiModal.hidden = true;
      aiModal.setAttribute('aria-hidden', 'true');
      if (aiKey) aiKey.value = '';
      if (priorFocus && typeof priorFocus.focus === 'function') priorFocus.focus();
    };
    const openAi = () => {
      priorFocus = document.activeElement;
      aiModal.hidden = false;
      aiModal.setAttribute('aria-hidden', 'false');
      proposal = null;
      hide(aiResult);
      setMessage(aiStatus.enabled ? '' : 'AI enhancement is disabled for this installation.', aiStatus.enabled ? '' : 'error');
      if (aiProvider) aiProvider.value = ['openrouter', 'openai', 'gemini'].includes(aiStatus.defaultProvider) ? aiStatus.defaultProvider : 'openrouter';
      syncAiForm();
      window.setTimeout(() => aiOperation?.focus(), 0);
      if (window.lucide) window.lucide.createIcons();
    };
    const editableItemFields = [
      ['title', 'Title'], ['organisation', 'Organisation'], ['details', 'Details'],
      ['field_of_study', 'Field of study'], ['category', 'Category'], ['proficiency', 'Proficiency'],
      ['outcome', 'Outcome'], ['link', 'Link'], ['start_date', 'Start date'],
      ['end_date', 'End date'], ['current', 'Current'], ['published_date', 'Published date'], ['year', 'Year']
    ];
    const renderProposal = value => {
      aiOutput.replaceChildren();
      if (value.summary) {
        const summary = document.createElement('p');
        summary.className = 'ap-ai-summary';
        summary.textContent = value.summary;
        aiOutput.appendChild(summary);
      }
      if (value.operation === 'enhance_portfolio') {
        if (value.rewrite) { const card=document.createElement('article'); card.className='ap-ai-suggestion'; const title=document.createElement('strong'); title.textContent='Profile biography'; card.appendChild(title); const copy=document.createElement('p'); copy.textContent=value.rewrite; card.appendChild(copy); aiOutput.appendChild(card); }
        (value.items||[]).forEach((item,index)=>{ const card=document.createElement('article'); card.className='ap-ai-suggestion'; const title=document.createElement('strong'); title.textContent=(index+1)+'. '+(item.title||('Portfolio entry #'+item.item_id)); card.appendChild(title); const copy=document.createElement('p'); copy.textContent=item.details||''; card.appendChild(copy); const section=document.createElement('small'); section.textContent='Section: '+(item.section_key||'Portfolio'); card.appendChild(section); aiOutput.appendChild(card); });
      } else if (value.operation === 'rewrite_profile' || value.operation === 'rewrite_item') {
        const label = document.createElement('label');
        label.className = 'ap-ai-edit-field';
        const caption = document.createElement('span');
        caption.textContent = 'Proposed text (you can edit before applying)';
        const rewrite = document.createElement('textarea');
        rewrite.rows = 5;
        rewrite.maxLength = value.field === 'bio' ? 10000 : 5000;
        rewrite.dataset.aiRewrite = '1';
        rewrite.value = value.rewrite || '';
        label.append(caption, rewrite);
        aiOutput.appendChild(label);
      } else {
        (value.items || []).forEach((item, index) => {
          const card = document.createElement('article');
          card.className = 'ap-ai-suggestion';
          const head = document.createElement('label');
          head.className = 'ap-ai-suggestion-select';
          const checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.checked = true;
          checkbox.dataset.aiSelect = String(index);
          const title = document.createElement('strong');
          title.textContent = 'Include suggestion ' + (index + 1);
          head.append(checkbox, title);
          card.appendChild(head);
          const fields = document.createElement('div');
          fields.className = 'ap-ai-edit-grid';
          editableItemFields.forEach(([key, labelText]) => {
            const label = document.createElement('label');
            label.className = 'ap-ai-edit-field' + (key === 'details' || key === 'outcome' ? ' ap-ai-edit-wide' : '');
            const caption = document.createElement('span');
            caption.textContent = labelText;
            const input = key === 'details' || key === 'outcome' ? document.createElement('textarea') : document.createElement('input');
            if (input.tagName === 'INPUT') input.type = key.endsWith('_date') || key === 'published_date' ? 'date' : 'text';
            if (key === 'current') {
              input.type = 'checkbox';
              input.checked = ['1', 'true', 'yes'].includes(String(item[key] || '').toLowerCase());
              input.dataset.aiField = key;
            } else {
              input.value = item[key] || '';
              input.dataset.aiField = key;
              input.maxLength = key === 'details' || key === 'outcome' ? 5000 : key === 'link' ? 500 : 190;
              if (input.tagName === 'TEXTAREA') input.rows = 3;
              if (key === 'link') input.placeholder = 'https://...';
            }
            label.append(caption, input);
            fields.appendChild(label);
          });
          card.appendChild(fields);
          aiOutput.appendChild(card);
        });
      }
      if (aiApply) aiApply.disabled = false;
    };
    const requestJson = async (url, data) => {
      const controller = new AbortController();
      const timeoutId = window.setTimeout(() => controller.abort(), 45000);
      try {
        const response = await fetch(url, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
          body: data,
          signal: controller.signal
        });
        let body;
        try { body = await response.json(); } catch (error) { body = { ok: false, message: 'The server returned an unexpected response.' }; }
        if (!response.ok || !body.ok) throw new Error(body.message || 'The request could not be completed.');
        return body;
      } catch (error) {
        if (error && error.name === 'AbortError') {
          throw new Error('This request took more than 45 seconds. Check your connection, provider availability, API key, model access, and free-tier quota.');
        }
        throw error;
      } finally {
        window.clearTimeout(timeoutId);
      }
    };

    aiOpen?.addEventListener('click', openAi);
    aiClose?.addEventListener('click', closeAi);
    aiModal.addEventListener('click', event => { if (event.target === aiModal) closeAi(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !aiModal.hidden) closeAi(); });
    aiOperation?.addEventListener('change', () => {
      syncAiForm();
      if (aiGenerate) aiGenerate.lastChild.textContent = aiOperation.value === 'enhance_portfolio' ? 'Enhance whole portfolio' : 'Generate suggestions';
      if (aiApply) aiApply.lastChild.textContent = aiOperation.value === 'enhance_portfolio' ? 'Apply all improvements' : 'Apply suggestion';
    });
    aiSection?.addEventListener('change', optionForSection);
    aiProvider?.addEventListener('change', updateKeyNote);
    aiForm?.addEventListener('submit', async event => {
      event.preventDefault();
      if (!aiStatus.enabled) { setMessage('AI enhancement is disabled for this installation.', 'error'); return; }
      if (aiOperation?.value === 'rewrite_item' && !optionForSection()) return;
      proposal = null;
      hide(aiResult);
      aiGenerate.disabled = true;
      aiGenerate.setAttribute('aria-busy', 'true');
      setMessage('Creating your suggestion… This can take up to 45 seconds.', 'loading');
      try {
        const result = await requestJson(proposeUrl, new FormData(aiForm));
        proposal = result.proposal;
        renderProposal(proposal);
        show(aiResult);
        setMessage('Suggestion ready. Review it, then choose Apply suggestion.');
      } catch (error) {
        setMessage(error instanceof Error ? error.message : 'Unable to create a suggestion.', 'error');
      } finally {
        if (aiKey) aiKey.value = '';
        aiGenerate.removeAttribute('aria-busy');
        aiGenerate.disabled = !aiStatus.enabled || (aiOperation?.value === 'rewrite_item' && !optionForSection());
      }
    });
    aiApply?.addEventListener('click', async () => {
      if (!proposal) return;
      aiApply.disabled = true;
      setMessage('Applying your approved suggestion…', 'loading');
      try {
        const approved = JSON.parse(JSON.stringify(proposal));
        const rewriteInput = aiOutput.querySelector('[data-ai-rewrite]');
        if (rewriteInput) approved.rewrite = rewriteInput.value.trim();
        if (approved.operation === 'generate_items') {
          approved.items = (approved.items || []).filter((item, index) => {
            const selected = aiOutput.querySelector('[data-ai-select="' + index + '"]');
            if (!selected || !selected.checked) return false;
            const card = selected.closest('.ap-ai-suggestion');
            card?.querySelectorAll('[data-ai-field]').forEach(input => {
              const key = input.dataset.aiField;
              item[key] = input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value.trim();
            });
            return true;
          });
          if (!approved.items.length) {
            setMessage('Select at least one suggestion to apply.', 'error');
            aiApply.disabled = false;
            return;
          }
        }
        if ((approved.operation === 'rewrite_profile' || approved.operation === 'rewrite_item') && !approved.rewrite) {
          setMessage('The proposed text cannot be empty.', 'error');
          aiApply.disabled = false;
          return;
        }
        const data = new FormData();
        data.append('_csrf', aiForm.querySelector('input[name="_csrf"]')?.value || '');
        data.append('id', String(editorId));
        data.append('proposal', JSON.stringify(approved));
        const result = await requestJson(applyUrl, data);
        const tab = result.tab || 'overview';
        window.location.assign(editorUrl + '?id=' + encodeURIComponent(String(editorId)) + '&tab=' + encodeURIComponent(tab));
      } catch (error) {
        setMessage(error instanceof Error ? error.message : 'Unable to apply the suggestion.', 'error');
        aiApply.disabled = false;
      }
    });
  }
})();
</script>
