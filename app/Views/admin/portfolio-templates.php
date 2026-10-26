<?php
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$baseUrl = rtrim((string) (($app ?? [])['base_url'] ?? ''), '/');
?>
<section class="container-fluid py-3">
  <header class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
      <p class="text-uppercase small fw-bold text-secondary mb-1">Portfolio Studio</p>
      <h1 class="h3 mb-2">Default template previews</h1>
      <p class="text-secondary mb-0">Choose one of your own portfolios as the example content for each template. Edit its profile, projects, and sections through your normal Portfolio Studio. Only published samples are shown to clients.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/admin') ?>">Back to Admin</a>
  </header>

  <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/save') ?>">
    <?= \App\Core\Csrf::field() ?>
    <div class="row g-3">
      <?php foreach ($templates as $templateKey => $templateLabel): ?>
        <?php
          $matching = array_values(array_filter($portfolios, static fn (array $p): bool => ($p['template_key'] ?? '') === $templateKey));
          $selectedId = (int) ($defaults[$templateKey] ?? 0);
          $selected = null;
          foreach ($matching as $candidate) { if ((int) $candidate['id'] === $selectedId) { $selected = $candidate; break; } }
        ?>
        <div class="col-12 col-xl-6">
          <section class="card h-100 shadow-sm">
            <div class="card-body">
              <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                <div><span class="badge text-bg-light mb-2"><?= $escape(strtoupper($templateKey)) ?></span><h2 class="h5 mb-1"><?= $escape($templateLabel) ?></h2></div>
                <?php if ($selected && ($selected['status'] ?? '') === 'published'): ?>
                  <span class="badge text-bg-success">Preview active</span>
                <?php elseif ($selected): ?>
                  <span class="badge text-bg-warning">Not published</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">No sample selected</span>
                <?php endif; ?>
              </div>
              <label class="form-label fw-semibold" for="default_<?= $escape($templateKey) ?>">Admin-owned sample portfolio</label>
              <select class="form-select" id="default_<?= $escape($templateKey) ?>" name="default_<?= $escape($templateKey) ?>">
                <option value="0">Use the visitor's own draft until a sample is set</option>
                <?php foreach ($matching as $option): ?>
                  <option value="<?= (int) $option['id'] ?>" <?= $selectedId === (int) $option['id'] ? 'selected' : '' ?>>
                    <?= $escape($option['display_name']) ?> · <?= $escape($option['website_name']) ?><?= ($option['status'] ?? '') === 'published' ? '' : ' (publish to use as preview)' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if ($selected): ?>
                <p class="small text-secondary mt-2 mb-2">Selected sample: <?= $escape($selected['display_name']) ?> / <?= $escape($selected['website_name']) ?>.</p>
                <div class="d-flex flex-wrap gap-2">
                  <a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio?id=' . (int) $selected['website_id']) ?>">Edit setup</a>
                  <a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced?id=' . (int) $selected['website_id']) ?>">Edit sample content</a>
                  <?php if (($selected['status'] ?? '') === 'published' && !empty($selected['slug'])): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= $escape($baseUrl . '/p/' . rawurlencode((string) $selected['slug'])) ?>" target="_blank" rel="noopener">View sample</a>
                  <?php endif; ?>
                </div>
              <?php elseif ($matching === []): ?>
                <p class="small text-secondary mt-2 mb-0">Create a portfolio website under your admin account, select this template, and publish it to make it available here.</p>
              <?php endif; ?>
            </div>
          </section>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-end mt-4">
      <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save template previews</button>
    </div>
  </form>
</section>
