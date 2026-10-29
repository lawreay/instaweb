<?php
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$baseUrl = rtrim((string) (($app ?? [])['base_url'] ?? ''), '/');
$isEditing = ($mode ?? '') === 'edit';
$action = $isEditing ? '/admin/portfolio-templates/update' : '/admin/portfolio-templates/create';
$engine = (string) ($template['render_engine'] ?? 'html');
if (!in_array($engine, ['html', 'react'], true)) { $engine = 'html'; }
?>
<div class="admin-pro-shell iw-client-shell admin-dashboard-refresh" data-admin-shell>
  <?php $active = 'portfolio-templates'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>
  <button class="iw-sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>
  <section class="admin-pro-main iw-client-main">
    <header class="ad-topbar">
      <button class="ad-topbar-menu" type="button" data-sidebar-open aria-controls="adminSidebar" aria-expanded="false" aria-label="Open navigation"><i data-lucide="menu"></i></button>
      <div class="ad-topbar-context">
        <span class="ad-topbar-eyebrow">INSTAWEB WORKSPACE</span>
        <span class="ad-topbar-page"><?= $isEditing ? 'Edit portfolio template' : 'Add portfolio template' ?></span>
      </div>
      <nav class="ad-topbar-actions" aria-label="Account actions">
        <a class="ad-topbar-link" href="<?= $escape($baseUrl . '/admin/notifications') ?>"><i data-lucide="bell"></i><span>Notifications</span></a>
        <a class="ad-topbar-link" href="<?= $escape($baseUrl . '/profile') ?>"><i data-lucide="user-round"></i><span>Account</span></a>
      </nav>
    </header>
    <section class="container-fluid py-3">
  <header class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
      <p class="text-uppercase small fw-bold text-secondary mb-1">Portfolio Studio</p>
      <h1 class="h3 mb-2"><?= $isEditing ? 'Edit custom template' : 'Add custom template' ?></h1>
      <p class="text-secondary mb-0">Build an HTML/CSS layout or a React component using the saved portfolio data.</p>
    </div>
    <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/admin/portfolio-templates') ?>">Back to templates</a>
  </header>

  <div class="alert alert-info border" data-template-help="html">
    <strong>HTML placeholders:</strong>
    <code>{{name}}</code>, <code>{{headline}}</code>, <code>{{bio}}</code>, <code>{{location}}</code>, <code>{{avatar}}</code>, <code>{{navigation}}</code>, <code>{{sections}}</code>, <code>{{projects}}</code>, <code>{{contact}}</code>, and <code>{{year}}</code>.
  </div>
  <div class="alert alert-info border d-none" data-template-help="react">
    <strong>React component contract:</strong> define <code>function Portfolio({ data })</code> and return JSX. Use <code>React.useState</code> and <code>React.useEffect</code> when needed. Do not use imports, exports, network requests, browser storage, or direct dynamic-code APIs. The component runs inside an isolated frame.
  </div>

  <form method="POST" action="<?= $escape($baseUrl . $action) ?>" data-template-form>
    <?= \App\Core\Csrf::field() ?>
    <?php if ($isEditing): ?><input type="hidden" name="id" value="<?= (int) ($template['id'] ?? 0) ?>"><?php endif; ?>
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-5">
            <label class="form-label fw-semibold" for="display_name">Template name</label>
            <input class="form-control" id="display_name" name="display_name" maxlength="120" required value="<?= $escape($template['display_name'] ?? '') ?>" placeholder="e.g. Ledger">
            <?php if ($isEditing): ?><div class="form-text">Key: <code><?= $escape($template['template_key'] ?? '') ?></code></div><?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold" for="description">Short description</label>
            <input class="form-control" id="description" name="description" maxlength="500" value="<?= $escape($template['description'] ?? '') ?>" placeholder="What makes this layout distinct?">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-semibold" for="render_engine">Renderer</label>
            <select class="form-select" id="render_engine" name="render_engine" data-template-engine>
              <option value="html" <?= $engine === 'html' ? 'selected' : '' ?>>HTML + CSS</option>
              <option value="react" <?= $engine === 'react' ? 'selected' : '' ?>>React + JSX</option>
            </select>
          </div>
        </div>

        <div class="mb-3" data-template-source="html">
          <label class="form-label fw-semibold" for="html_code">Template HTML</label>
          <textarea class="form-control font-monospace" id="html_code" name="html_code" rows="19" spellcheck="false" <?= $engine === 'html' ? 'required' : '' ?>><?= $escape($template['html_code'] ?? '') ?></textarea>
          <div class="form-text">Scripts, forms, embedded pages, event handlers, and executable URLs are blocked.</div>
        </div>

        <div class="mb-3 <?= $engine === 'react' ? '' : 'd-none' ?>" data-template-source="react">
          <label class="form-label fw-semibold" for="react_code">React component (JSX)</label>
          <textarea class="form-control font-monospace" id="react_code" name="react_code" rows="24" spellcheck="false" <?= $engine === 'react' ? 'required' : '' ?>><?= $escape($template['react_code'] ?? '') ?></textarea>
          <div class="form-text">Define a component named <code>Portfolio</code>. React and portfolio <code>data</code> are supplied automatically; do not add imports or exports.</div>
        </div>

        <div class="mb-0">
          <label class="form-label fw-semibold" for="css_code">Template CSS</label>
          <textarea class="form-control font-monospace" id="css_code" name="css_code" rows="16" spellcheck="false"><?= $escape($template['css_code'] ?? '') ?></textarea>
          <div class="form-text">CSS applies only to this custom template. Remote imports and executable URLs are blocked.</div>
        </div>
      </div>
      <div class="card-footer bg-white d-flex flex-wrap justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="<?= $escape($baseUrl . '/admin/portfolio-templates') ?>">Cancel</a>
        <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> <?= $isEditing ? 'Save template code' : 'Add template' ?></button>
      </div>
    </div>
  </form>
</section>
    <footer class="ad-footer">
      <span>© <?= $escape(date('Y')) ?> InstaWeb · Administration</span>
      <nav aria-label="Administrative and legal links">
        <a href="<?= $escape($baseUrl . '/admin/settings') ?>">System settings</a>
        <a href="<?= $escape($baseUrl . '/admin/legal') ?>">Policies</a>
        <a href="<?= $escape($baseUrl . '/terms') ?>">Terms</a>
        <a href="<?= $escape($baseUrl . '/privacy') ?>">Privacy</a>
      </nav>
    </footer>
  </section>
</div>
<script>
  (() => {
    const selector = document.querySelector('[data-template-engine]');
    const htmlSource = document.querySelector('[data-template-source="html"]');
    const reactSource = document.querySelector('[data-template-source="react"]');
    const htmlHelp = document.querySelector('[data-template-help="html"]');
    const reactHelp = document.querySelector('[data-template-help="react"]');
    const htmlCode = document.getElementById('html_code');
    const reactCode = document.getElementById('react_code');
    if (!selector || !htmlSource || !reactSource || !htmlCode || !reactCode) return;
    const update = () => {
      const react = selector.value === 'react';
      htmlSource.classList.toggle('d-none', react);
      reactSource.classList.toggle('d-none', !react);
      htmlHelp.classList.toggle('d-none', react);
      reactHelp.classList.toggle('d-none', !react);
      htmlCode.required = !react;
      reactCode.required = react;
    };
    selector.addEventListener('change', update);
    update();
  })();
</script>
