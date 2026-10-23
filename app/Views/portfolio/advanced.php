<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$websiteId = (int) $website['id'];
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
?>
<section class="client-dashboard iw-client-page iw-website-form">
  <div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Portfolio Studio / Advanced</p>
        <h1>Advanced Portfolio Editor</h1>
        <p>Control the visual presentation without touching source code. Your existing profile and projects remain intact.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio?id=' . $websiteId) ?>">Basic Editor</a>
        <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/preview?id=' . $websiteId) ?>"><i data-lucide="eye"></i> Preview</a>
      </div>
    </header>

    <section class="client-card mb-4">
      <div class="client-card-head">
        <h2>Visual design</h2>
        <span><?= $escape($website['website_name'] ?? 'Portfolio') ?></span>
      </div>
      <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/save') ?>" class="row g-4">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $websiteId ?>">
        <div class="col-12 col-md-4">
          <label class="form-label" for="accent_color">Accent colour</label>
          <input class="form-control form-control-color w-100" id="accent_color" name="accent_color" type="color" value="<?= $escape($settings['accent_color'] ?? '#f97316') ?>" required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label" for="background_color">Background colour</label>
          <input class="form-control form-control-color w-100" id="background_color" name="background_color" type="color" value="<?= $escape($settings['background_color'] ?? '#ffffff') ?>" required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label" for="text_color">Text colour</label>
          <input class="form-control form-control-color w-100" id="text_color" name="text_color" type="color" value="<?= $escape($settings['text_color'] ?? '#111827') ?>" required>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="font_family">Typography</label>
          <select class="form-select" id="font_family" name="font_family">
            <?php foreach (['system' => 'System default', 'sans-serif' => 'Sans serif', 'serif' => 'Serif', 'mono' => 'Monospace'] as $key => $label): ?>
              <option value="<?= $escape($key) ?>" <?= (($settings['font_family'] ?? 'system') === $key) ? 'selected' : '' ?>><?= $escape($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="hero_style">Hero layout</label>
          <select class="form-select" id="hero_style" name="hero_style">
            <?php foreach (['centered' => 'Centered introduction', 'split' => 'Split layout', 'minimal' => 'Minimal introduction'] as $key => $label): ?>
              <option value="<?= $escape($key) ?>" <?= (($settings['hero_style'] ?? 'centered') === $key) ? 'selected' : '' ?>><?= $escape($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="show_projects">Show projects section</label>
          <select class="form-select" id="show_projects" name="show_projects">
            <option value="1" <?= (($settings['show_projects'] ?? '1') === '1') ? 'selected' : '' ?>>Visible</option>
            <option value="0" <?= (($settings['show_projects'] ?? '1') === '0') ? 'selected' : '' ?>>Hidden</option>
          </select>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="show_contact">Show contact section</label>
          <select class="form-select" id="show_contact" name="show_contact">
            <option value="1" <?= (($settings['show_contact'] ?? '1') === '1') ? 'selected' : '' ?>>Visible</option>
            <option value="0" <?= (($settings['show_contact'] ?? '1') === '0') ? 'selected' : '' ?>>Hidden</option>
          </select>
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save Design</button>
        </div>
      </form>
    </section>

    <section class="client-card">
      <div class="client-card-head">
        <h2>Advanced content</h2>
        <span><?= count($items) ?> saved items</span>
      </div>
      <p class="text-secondary mb-0">The database foundation supports additional sections such as experience, education, skills, and interests. Those editing controls and their public rendering are not wired yet, so this page does not pretend otherwise.</p>
    </section>
  </div>
</section>
