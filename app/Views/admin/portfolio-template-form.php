<?php
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$baseUrl = rtrim((string) (($app ?? [])['base_url'] ?? ''), '/');
$isEditing = ($mode ?? '') === 'edit';
$action = $isEditing ? '/admin/portfolio-templates/update' : '/admin/portfolio-templates/create';
?>
<section class="container-fluid py-3">
  <header class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
      <p class="text-uppercase small fw-bold text-secondary mb-1">Portfolio Studio</p>
      <h1 class="h3 mb-2"><?= $isEditing ? 'Edit custom template' : 'Add custom template' ?></h1>
      <p class="text-secondary mb-0">Use the provided placeholders to bring each portfolio’s content into your layout. HTML and CSS are stored safely; scripts and embedded content are not supported.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/admin/portfolio-templates') ?>">Back to templates</a>
  </header>

  <div class="alert alert-info border">
    <strong>Available placeholders:</strong>
    <code>{{name}}</code>, <code>{{headline}}</code>, <code>{{bio}}</code>, <code>{{location}}</code>, <code>{{avatar}}</code>, <code>{{sections}}</code>, <code>{{projects}}</code>, <code>{{contact}}</code>, and <code>{{year}}</code>.
    Content placeholders are already escaped before they are inserted.
  </div>

  <form method="POST" action="<?= $escape($baseUrl . $action) ?>">
    <?= \App\Core\Csrf::field() ?>
    <?php if ($isEditing): ?><input type="hidden" name="id" value="<?= (int) ($template['id'] ?? 0) ?>"><?php endif; ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="display_name">Template name</label>
            <input class="form-control" id="display_name" name="display_name" maxlength="120" required value="<?= $escape($template['display_name'] ?? '') ?>" placeholder="e.g. Ledger">
            <?php if ($isEditing): ?><div class="form-text">Key: <code><?= $escape($template['template_key'] ?? '') ?></code></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="description">Short description</label>
            <input class="form-control" id="description" name="description" maxlength="500" value="<?= $escape($template['description'] ?? '') ?>" placeholder="What makes this layout distinct?">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" for="html_code">Template HTML</label>
          <textarea class="form-control font-monospace" id="html_code" name="html_code" rows="19" spellcheck="false" required><?= $escape($template['html_code'] ?? '') ?></textarea>
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold" for="css_code">Template CSS</label>
          <textarea class="form-control font-monospace" id="css_code" name="css_code" rows="16" spellcheck="false"><?= $escape($template['css_code'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="card-footer bg-white d-flex flex-wrap justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl . '/admin/portfolio-templates') ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> <?= $isEditing ? 'Save template code' : 'Add template' ?></button>
      </div>
    </div>
  </form>
</section>
