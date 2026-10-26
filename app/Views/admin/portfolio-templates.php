<?php
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$baseUrl = rtrim((string) (($app ?? [])['base_url'] ?? ''), '/');
?>
<section class="container-fluid py-3">
  <header class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
      <p class="text-uppercase small fw-bold text-secondary mb-1">Portfolio Studio</p>
      <h1 class="h3 mb-2">Template manager</h1>
      <p class="text-secondary mb-0">Control which layouts are available, create custom HTML/CSS or React/JSX layouts, and choose sample portfolios for template previews.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-primary" href="<?= $escape($baseUrl . '/admin/portfolio-templates/new') ?>"><i data-lucide="plus"></i> Add custom template</a>
      <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/admin') ?>">Back to Admin</a>
    </div>
  </header>

  <div class="alert alert-light border d-flex gap-3 mb-4" role="note">
    <i data-lucide="shield-check" class="flex-shrink-0 mt-1"></i>
    <div><strong>Built-in layouts are protected.</strong> You can activate or suspend them, but their application code cannot be deleted. Custom templates can use safe HTML/CSS or React/JSX. React templates run in an isolated frame. Suspended templates are hidden from new portfolio selections; existing portfolios continue to render normally.</div>
  </div>

  <div class="row g-3">
    <?php foreach ($templates as $template): ?>
      <?php
        $templateKey = (string) ($template['template_key'] ?? '');
        $isBuiltIn = !empty($template['is_builtin']);
        $isActive = ($template['status'] ?? '') === 'active';
        $renderEngine = (string) ($template['render_engine'] ?? 'html');
        $matching = array_values(array_filter($portfolios, static fn (array $p): bool => ($p['template_key'] ?? '') === $templateKey));
        $selectedId = (int) ($defaults[$templateKey] ?? 0);
        $selected = null;
        foreach ($matching as $candidate) {
            if ((int) $candidate['id'] === $selectedId) {
                $selected = $candidate;
                break;
            }
        }
      ?>
      <div class="col-12 col-xl-6">
        <section class="card h-100 shadow-sm <?= $isActive ? '' : 'border-warning' ?>">
          <div class="card-body d-flex flex-column">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
              <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                  <span class="badge text-bg-light"><?= $escape($isBuiltIn ? 'BUILT-IN' : 'CUSTOM') ?></span>
                  <?php if (!$isBuiltIn): ?><span class="badge text-bg-info"><?= $escape($renderEngine === 'react' ? 'REACT / JSX' : 'HTML / CSS') ?></span><?php endif; ?>
                  <span class="badge text-bg-<?= $isActive ? 'success' : 'warning' ?>"><?= $escape(ucfirst((string) ($template['status'] ?? 'suspended'))) ?></span>
                </div>
                <h2 class="h5 mb-1"><?= $escape($template['display_name'] ?? '') ?></h2>
                <p class="small text-secondary mb-0"><code><?= $escape($templateKey) ?></code><?= !empty($template['description']) ? ' · ' . $escape($template['description']) : '' ?></p>
              </div>
              <?php if (!$isBuiltIn): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= $escape($baseUrl . '/admin/portfolio-templates/edit?id=' . (int) $template['id']) ?>"><i data-lucide="code-2"></i> Edit code</a>
              <?php endif; ?>
            </div>

            <p class="small text-secondary mb-3"><?= $isBuiltIn ? 'Renderer files are maintained by the application.' : ($renderEngine === 'react' ? 'Editable React component and CSS, rendered in an isolated frame.' : 'Editable, script-free HTML and CSS.') ?></p>

            <div class="mt-auto d-flex flex-wrap gap-2">
              <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/status') ?>">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="template_key" value="<?= $escape($templateKey) ?>">
                <?php if (!$isBuiltIn): ?><input type="hidden" name="id" value="<?= (int) $template['id'] ?>"><?php endif; ?>
                <input type="hidden" name="status" value="<?= $isActive ? 'suspended' : 'active' ?>">
                <button class="btn btn-sm btn-<?= $isActive ? 'outline-warning' : 'outline-success' ?>" type="submit"><i data-lucide="<?= $isActive ? 'pause-circle' : 'play-circle' ?>"></i> <?= $isActive ? 'Suspend' : 'Activate' ?></button>
              </form>
              <?php if (!$isBuiltIn): ?>
                <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/delete') ?>" onsubmit="return confirm('Delete this unused custom template? This cannot be undone.');">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $template['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit"><i data-lucide="trash-2"></i> Delete</button>
                </form>
              <?php endif; ?>
              <?php if ($selected && ($selected['status'] ?? '') === 'published' && !empty($selected['slug'])): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= $escape($baseUrl . '/p/' . rawurlencode((string) $selected['slug'])) ?>" target="_blank" rel="noopener">View sample</a>
              <?php endif; ?>
            </div>
          </div>
        </section>
      </div>
    <?php endforeach; ?>
  </div>

  <hr class="my-5">

  <header class="mb-3">
    <p class="text-uppercase small fw-bold text-secondary mb-1">Preview content</p>
    <h2 class="h4 mb-1">Default template previews</h2>
    <p class="text-secondary mb-0">Choose one of your own portfolios as the example content for each template. Only published samples are shown to visitors.</p>
  </header>

  <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/save') ?>">
    <?= \App\Core\Csrf::field() ?>
    <div class="row g-3">
      <?php foreach ($templates as $template): ?>
        <?php
          $templateKey = (string) ($template['template_key'] ?? '');
          $matching = array_values(array_filter($portfolios, static fn (array $p): bool => ($p['template_key'] ?? '') === $templateKey));
          $selectedId = (int) ($defaults[$templateKey] ?? 0);
        ?>
        <div class="col-12 col-lg-6 col-xxl-4">
          <section class="card h-100 border-0 bg-light">
            <div class="card-body">
              <label class="form-label fw-semibold" for="default_<?= $escape($templateKey) ?>"><?= $escape($template['display_name'] ?? '') ?></label>
              <select class="form-select" id="default_<?= $escape($templateKey) ?>" name="default_<?= $escape($templateKey) ?>">
                <option value="0">Use the visitor's own draft until a sample is set</option>
                <?php foreach ($matching as $option): ?>
                  <option value="<?= (int) $option['id'] ?>" <?= $selectedId === (int) $option['id'] ? 'selected' : '' ?>>
                    <?= $escape($option['display_name']) ?> · <?= $escape($option['website_name']) ?><?= ($option['status'] ?? '') === 'published' ? '' : ' (publish to use as preview)' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <?php if ($matching === []): ?><p class="small text-secondary mt-2 mb-0">No admin-owned portfolio currently uses this template.</p><?php endif; ?>
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
