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
        <h2>Portfolio sections</h2>
        <span><?= count($items) ?> saved items</span>
      </div>
      <p class="text-secondary">Add and manage extra content here. Changes are saved to this portfolio only.</p>
      <?php foreach (($sectionLabels ?? []) as $sectionKey => $sectionLabel): ?>
        <?php $sectionItems = array_values(array_filter($items, static fn (array $item): bool => ($item['section_key'] ?? '') === $sectionKey)); ?>
        <div class="border rounded-3 p-3 mb-4">
          <h3 class="h5 mb-3"><?= $escape($sectionLabel) ?></h3>
          <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/save') ?>" class="row g-3 mb-3">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $websiteId ?>">
            <input type="hidden" name="section_key" value="<?= $escape($sectionKey) ?>">
            <div class="col-12 col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" maxlength="190" required placeholder="<?= $escape($sectionKey === 'skills' ? 'e.g. PHP and MySQL' : ($sectionKey === 'interests' ? 'e.g. Photography' : ($sectionKey === 'education' ? 'e.g. Diploma in ICT' : 'e.g. Software Developer'))) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label">Organisation / institution</label><input class="form-control" name="organisation" maxlength="190" placeholder="Company, school, or category"></div>
            <div class="col-12 col-md-6"><label class="form-label">Period</label><input class="form-control" name="period" maxlength="100" placeholder="e.g. 2023–2025"></div>
            <div class="col-12 col-md-6"><label class="form-label">Link (optional)</label><input class="form-control" type="url" name="link" maxlength="500" placeholder="https://..."></div>
            <div class="col-12"><label class="form-label">Description / details</label><textarea class="form-control" name="details" rows="2" maxlength="5000" placeholder="Add a short description"></textarea></div>
            <div class="col-12 col-md-3"><label class="form-label">Display order</label><input class="form-control" type="number" name="sort_order" min="0" max="9999" value="0"></div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Add <?= $escape(strtolower($sectionLabel)) ?></button></div>
          </form>
          <?php if ($sectionItems === []): ?>
            <p class="text-secondary small mb-0">No items in this section yet.</p>
          <?php else: ?>
            <?php foreach ($sectionItems as $item): ?>
              <?php $payload = json_decode((string) ($item['payload'] ?? '{}'), true); $payload = is_array($payload) ? $payload : []; ?>
              <div class="border-top pt-3 mt-3 d-flex flex-column gap-3">
                <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/save') ?>" class="row g-2">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $websiteId ?>">
                  <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                  <input type="hidden" name="section_key" value="<?= $escape($item['section_key']) ?>">
                  <div class="col-12 col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" maxlength="190" required value="<?= $escape($item['title'] ?? '') ?>"></div>
                  <div class="col-12 col-md-6"><label class="form-label">Organisation / institution</label><input class="form-control" name="organisation" maxlength="190" value="<?= $escape($payload['organisation'] ?? '') ?>"></div>
                  <div class="col-12 col-md-6"><label class="form-label">Period</label><input class="form-control" name="period" maxlength="100" value="<?= $escape($payload['period'] ?? '') ?>"></div>
                  <div class="col-12 col-md-6"><label class="form-label">Link</label><input class="form-control" type="url" name="link" maxlength="500" value="<?= $escape($payload['link'] ?? '') ?>"></div>
                  <div class="col-12"><label class="form-label">Description / details</label><textarea class="form-control" name="details" rows="2" maxlength="5000"><?= $escape($payload['details'] ?? '') ?></textarea></div>
                  <div class="col-12 col-md-3"><label class="form-label">Display order</label><input class="form-control" type="number" name="sort_order" min="0" max="9999" value="<?= (int) ($item['sort_order'] ?? 0) ?>"></div>
                  <div class="col-12">
                    <button class="btn btn-sm btn-primary" type="submit">Save changes</button>
                  </div>
                </form>
                <div class="d-flex gap-2 flex-wrap">
                  <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/toggle') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $websiteId ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="enabled" value="<?= empty($item['is_enabled']) ? '1' : '0' ?>">
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><?= empty($item['is_enabled']) ? 'Enable' : 'Hide' ?></button>
                  </form>
                  <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced/items/delete') ?>" onsubmit="return confirm('Delete this portfolio item?')">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="id" value="<?= $websiteId ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </section>
  </div>
</section>
