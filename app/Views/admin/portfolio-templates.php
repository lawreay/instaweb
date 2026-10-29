<?php
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$baseUrl = rtrim((string) (($app ?? [])['base_url'] ?? ''), '/');

$totalCount = count($templates);
$activeCount = 0;
$customCount = 0;
$freeCount = 0;
foreach ($templates as $countTemplate) {
    if (($countTemplate['status'] ?? '') === 'active') { $activeCount++; }
    if (empty($countTemplate['is_builtin'])) { $customCount++; }
    if (($countTemplate['plan_tier'] ?? 'premium') === 'free') { $freeCount++; }
}
$suspendedCount = $totalCount - $activeCount;
?>
<div class="admin-pro-shell iw-client-shell admin-dashboard-refresh" data-admin-shell>
  <?php $active = 'portfolio-templates'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>
  <button class="iw-sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>
  <section class="admin-pro-main iw-client-main">
    <header class="ad-topbar">
      <button class="ad-topbar-menu" type="button" data-sidebar-open aria-controls="adminSidebar" aria-expanded="false" aria-label="Open navigation"><i data-lucide="menu"></i></button>
      <div class="ad-topbar-context">
        <span class="ad-topbar-eyebrow">INSTAWEB WORKSPACE</span>
        <span class="ad-topbar-page">Portfolio templates</span>
      </div>
      <nav class="ad-topbar-actions" aria-label="Account actions">
        <a class="ad-topbar-link" href="<?= $escape($baseUrl . '/admin/notifications') ?>"><i data-lucide="bell"></i><span>Notifications</span></a>
        <a class="ad-topbar-link" href="<?= $escape($baseUrl . '/profile') ?>"><i data-lucide="user-round"></i><span>Account</span></a>
      </nav>
    </header>
    <section class="container-fluid py-3 iw-tpl">
  <style>
    .iw-tpl { --iw-ink:#14201A; --iw-muted:#5B675F; --iw-line:#E4EAE5; --iw-green:#1F7A4D; --iw-green-dark:#14532D; --iw-danger:#A32A21; --iw-warn:#8A5A00; color:var(--iw-ink); }
    .iw-tpl .iw-head { display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:20px; }
    .iw-tpl .iw-kicker { margin:0 0 4px; font-size:12px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--iw-green); }
    .iw-tpl .iw-head h1 { margin:0 0 6px; font-size:30px; font-weight:800; letter-spacing:-.01em; }
    .iw-tpl .iw-head p.iw-lede { margin:0; max-width:680px; font-size:14px; color:var(--iw-muted); }
    .iw-tpl .iw-head-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .iw-tpl .iw-note { display:flex; gap:12px; align-items:flex-start; background:#F2F8F4; border:1px solid #D5E8DC; border-radius:14px; padding:14px 16px; margin-bottom:20px; font-size:13px; color:#26382E; }
    .iw-tpl .iw-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:20px; }
    .iw-tpl .iw-stat { background:#fff; border:1px solid var(--iw-line); border-radius:14px; padding:12px 16px; }
    .iw-tpl .iw-stat span { display:block; font-size:12px; color:var(--iw-muted); }
    .iw-tpl .iw-stat strong { font-size:24px; font-weight:800; color:var(--iw-green-dark); }
    .iw-tpl .iw-toolbar { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; }
    .iw-tpl .iw-filters { display:flex; flex-wrap:wrap; gap:8px; }
    .iw-tpl .iw-filter { min-height:44px; padding:0 16px; border-radius:999px; border:1px solid #D8E0DA; background:#fff; color:#3F4B44; font-size:13px; font-weight:600; cursor:pointer; }
    .iw-tpl .iw-filter.is-active { background:var(--iw-ink); border-color:var(--iw-ink); color:#fff; }
    .iw-tpl .iw-search { display:flex; align-items:center; gap:10px; min-height:44px; min-width:260px; padding:0 14px; border:1px solid #D8E0DA; border-radius:12px; background:#fff; color:var(--iw-muted); }
    .iw-tpl .iw-search input { border:0; outline:0; background:transparent; font-size:14px; color:var(--iw-ink); flex:1; min-width:0; }
    .iw-tpl .iw-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:16px; }
    .iw-tpl .iw-card { display:flex; flex-direction:column; gap:14px; background:#fff; border:1px solid var(--iw-line); border-radius:18px; padding:18px; }
    .iw-tpl .iw-card.is-suspended { border-color:#E8C98A; background:#FFFCF5; }
    .iw-tpl .iw-card[hidden] { display:none; }
    .iw-tpl .iw-badges { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
    .iw-tpl .iw-pill { font-size:11px; font-weight:700; letter-spacing:.03em; padding:3px 9px; border-radius:999px; background:#EEF2EF; color:#3F4B44; white-space:nowrap; text-transform:uppercase; }
    .iw-tpl .iw-pill.engine { background:#E3F1F7; color:#0B5873; }
    .iw-tpl .iw-pill.active { background:#DCF3E4; color:#14532D; }
    .iw-tpl .iw-pill.suspended { background:#FDF0C9; color:#6B4A00; }
    .iw-tpl .iw-pill.free { background:#DCF3E4; color:#14532D; }
    .iw-tpl .iw-pill.premium { background:#14201A; color:#fff; }
    .iw-tpl .iw-card h2 { margin:0 0 4px; font-size:17px; font-weight:700; line-height:1.3; }
    .iw-tpl .iw-key { font-size:12px; color:var(--iw-muted); word-break:break-all; }
    .iw-tpl .iw-key code { color:#7A2A5A; }
    .iw-tpl .iw-help { margin:0; font-size:13px; color:var(--iw-muted); }
    .iw-tpl .iw-tier { display:flex; align-items:flex-end; gap:8px; background:#F6F9F6; border:1px solid var(--iw-line); border-radius:12px; padding:10px; }
    .iw-tpl .iw-tier > div { flex:1; min-width:0; }
    .iw-tpl .iw-tier label { display:block; font-size:12px; font-weight:600; color:var(--iw-muted); margin-bottom:4px; }
    .iw-tpl .iw-tier select { width:100%; min-height:44px; }
    .iw-tpl .iw-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:auto; }
    .iw-tpl .iw-actions form { margin:0; }
    .iw-tpl .iw-btn { min-height:44px; padding:0 14px; border-radius:10px; border:1px solid var(--iw-line); background:#fff; color:var(--iw-ink); font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; white-space:nowrap; }
    .iw-tpl .iw-btn:hover { background:#F6F9F6; color:var(--iw-ink); }
    .iw-tpl .iw-btn.primary { background:var(--iw-green); border-color:var(--iw-green); color:#fff; }
    .iw-tpl .iw-btn.primary:hover { background:var(--iw-green-dark); color:#fff; }
    .iw-tpl .iw-btn.warn { border-color:#E3C27A; color:var(--iw-warn); }
    .iw-tpl .iw-btn.ok { border-color:#B9D8C5; color:var(--iw-green-dark); }
    .iw-tpl .iw-btn.danger { border-color:#D9B4B0; color:var(--iw-danger); }
    .iw-tpl .iw-btn svg { width:16px; height:16px; }
    .iw-tpl .iw-empty { grid-column:1 / -1; text-align:center; padding:40px 16px; color:var(--iw-muted); background:#fff; border:1px dashed #D8E0DA; border-radius:18px; }
    .iw-tpl .iw-empty[hidden] { display:none; }
    .iw-tpl .iw-section { margin-top:40px; padding-top:32px; border-top:1px solid var(--iw-line); }
    .iw-tpl .iw-section h2 { margin:0 0 4px; font-size:22px; font-weight:800; }
    .iw-tpl .iw-section > header p { margin:0 0 16px; font-size:14px; color:var(--iw-muted); }
    .iw-tpl .iw-preview-list { background:#fff; border:1px solid var(--iw-line); border-radius:18px; overflow:hidden; }
    .iw-tpl .iw-preview-row { display:grid; grid-template-columns:minmax(180px,1fr) minmax(260px,1.4fr); gap:16px; align-items:center; padding:12px 18px; border-bottom:1px solid #EEF2EF; }
    .iw-tpl .iw-preview-row:last-child { border-bottom:0; }
    .iw-tpl .iw-preview-row label { font-size:14px; font-weight:600; margin:0; }
    .iw-tpl .iw-preview-row select { width:100%; min-height:44px; }
    .iw-tpl .iw-preview-row .iw-none { grid-column:2; margin:-6px 0 0; font-size:12px; color:var(--iw-muted); }
    .iw-tpl .iw-savebar { position:sticky; bottom:0; display:flex; justify-content:flex-end; padding:14px 0; margin-top:16px; background:linear-gradient(to top, #fff 60%, rgba(255,255,255,0)); }
    @media (max-width: 720px) {
      .iw-tpl .iw-head h1 { font-size:24px; }
      .iw-tpl .iw-grid { grid-template-columns:1fr; }
      .iw-tpl .iw-search { min-width:0; width:100%; }
      .iw-tpl .iw-preview-row { grid-template-columns:1fr; gap:8px; }
      .iw-tpl .iw-preview-row .iw-none { grid-column:1; margin:0; }
    }
  </style>

  <header class="iw-head">
    <div>
      <p class="iw-kicker">Portfolio Studio</p>
      <h1>Template manager</h1>
      <p class="iw-lede">Control template availability and Free/Premium access, create custom HTML/CSS or React/JSX layouts, and choose sample portfolios for template previews.</p>
    </div>
    <div class="iw-head-actions">
      <a class="iw-btn primary" href="<?= $escape($baseUrl . '/admin/portfolio-templates/new') ?>"><i data-lucide="plus"></i> Add custom template</a>
      <a class="iw-btn" href="<?= $escape($baseUrl . '/admin') ?>">Back to Admin</a>
    </div>
  </header>

  <div class="iw-note" role="note">
    <i data-lucide="shield-check" class="flex-shrink-0"></i>
    <div><strong>Built-in layouts are protected.</strong> You can activate or suspend them, but their application code cannot be deleted. Custom templates can use safe HTML/CSS or React/JSX. React templates run in an isolated frame. Suspended templates are hidden from new portfolio selections; existing portfolios continue to render normally.</div>
  </div>

  <div class="iw-stats">
    <div class="iw-stat"><span>Templates</span><strong><?= (int) $totalCount ?></strong></div>
    <div class="iw-stat"><span>Active</span><strong><?= (int) $activeCount ?></strong></div>
    <div class="iw-stat"><span>Suspended</span><strong><?= (int) $suspendedCount ?></strong></div>
    <div class="iw-stat"><span>Custom</span><strong><?= (int) $customCount ?></strong></div>
    <div class="iw-stat"><span>Free plan</span><strong><?= (int) $freeCount ?></strong></div>
  </div>

  <div class="iw-toolbar">
    <div class="iw-filters" id="iwTplFilters">
      <button type="button" class="iw-filter is-active" data-filter="all">All</button>
      <button type="button" class="iw-filter" data-filter="builtin">Built-in</button>
      <button type="button" class="iw-filter" data-filter="custom">Custom</button>
      <button type="button" class="iw-filter" data-filter="active">Active</button>
      <button type="button" class="iw-filter" data-filter="suspended">Suspended</button>
    </div>
    <label class="iw-search">
      <i data-lucide="search"></i>
      <span class="visually-hidden">Search templates</span>
      <input type="search" id="iwTplSearch" placeholder="Search name or key" autocomplete="off">
    </label>
  </div>

  <div class="iw-grid" id="iwTplGrid">
    <?php foreach ($templates as $template): ?>
      <?php
        $templateKey = (string) ($template['template_key'] ?? '');
        $isBuiltIn = !empty($template['is_builtin']);
        $isActive = ($template['status'] ?? '') === 'active';
        $renderEngine = (string) ($template['render_engine'] ?? 'html');
        $planTier = (string) ($template['plan_tier'] ?? 'premium');
        $matching = array_values(array_filter($portfolios, static fn (array $p): bool => ($p['template_key'] ?? '') === $templateKey));
        $selectedId = (int) ($defaults[$templateKey] ?? 0);
        $selected = null;
        foreach ($matching as $candidate) {
            if ((int) $candidate['id'] === $selectedId) {
                $selected = $candidate;
                break;
            }
        }
        $searchText = strtolower((string) ($template['display_name'] ?? '') . ' ' . $templateKey);
      ?>
      <section class="iw-card <?= $isActive ? '' : 'is-suspended' ?>"
               data-kind="<?= $isBuiltIn ? 'builtin' : 'custom' ?>"
               data-status="<?= $isActive ? 'active' : 'suspended' ?>"
               data-search="<?= $escape($searchText) ?>">
        <div>
          <div class="iw-badges">
            <span class="iw-pill"><?= $escape($isBuiltIn ? 'BUILT-IN' : 'CUSTOM') ?></span>
            <?php if (!$isBuiltIn): ?><span class="iw-pill engine"><?= $escape($renderEngine === 'react' ? 'REACT / JSX' : 'HTML / CSS') ?></span><?php endif; ?>
            <span class="iw-pill <?= $isActive ? 'active' : 'suspended' ?>"><?= $escape(ucfirst((string) ($template['status'] ?? 'suspended'))) ?></span>
            <span class="iw-pill <?= $planTier === 'premium' ? 'premium' : 'free' ?>"><?= $escape(ucfirst($planTier)) ?> plan</span>
          </div>
          <h2><?= $escape($template['display_name'] ?? '') ?></h2>
          <p class="iw-key mb-0"><code><?= $escape($templateKey) ?></code><?= !empty($template['description']) ? ' · ' . $escape($template['description']) : '' ?></p>
        </div>

        <p class="iw-help"><?= $isBuiltIn ? 'Renderer files are maintained by the application.' : ($renderEngine === 'react' ? 'Editable React component and CSS, rendered in an isolated frame.' : 'Editable, script-free HTML and CSS.') ?></p>

        <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/tier') ?>" class="iw-tier">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="template_key" value="<?= $escape($templateKey) ?>">
          <div>
            <label for="tier_<?= $escape($templateKey) ?>">Availability plan</label>
            <select class="form-select form-select-sm" id="tier_<?= $escape($templateKey) ?>" name="plan_tier">
              <option value="free" <?= $planTier === 'free' ? 'selected' : '' ?>>Free</option>
              <option value="premium" <?= $planTier === 'premium' ? 'selected' : '' ?>>Premium</option>
            </select>
          </div>
          <button class="iw-btn" type="submit">Save tier</button>
        </form>

        <div class="iw-actions">
          <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/status') ?>">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="template_key" value="<?= $escape($templateKey) ?>">
            <?php if (!$isBuiltIn): ?><input type="hidden" name="id" value="<?= (int) $template['id'] ?>"><?php endif; ?>
            <input type="hidden" name="status" value="<?= $isActive ? 'suspended' : 'active' ?>">
            <button class="iw-btn <?= $isActive ? 'warn' : 'ok' ?>" type="submit"><i data-lucide="<?= $isActive ? 'pause-circle' : 'play-circle' ?>"></i> <?= $isActive ? 'Suspend' : 'Activate' ?></button>
          </form>
          <?php if (!$isBuiltIn): ?>
            <a class="iw-btn" href="<?= $escape($baseUrl . '/admin/portfolio-templates/edit?id=' . (int) $template['id']) ?>"><i data-lucide="code-2"></i> Edit code</a>
            <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/delete') ?>" onsubmit="return confirm('Delete this unused custom template? This cannot be undone.');">
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="id" value="<?= (int) $template['id'] ?>">
              <button class="iw-btn danger" type="submit"><i data-lucide="trash-2"></i> Delete</button>
            </form>
          <?php endif; ?>
          <?php if ($selected && ($selected['status'] ?? '') === 'published' && !empty($selected['slug'])): ?>
            <a class="iw-btn" href="<?= $escape($baseUrl . '/p/' . rawurlencode((string) $selected['slug'])) ?>" target="_blank" rel="noopener">View sample</a>
          <?php endif; ?>
        </div>
      </section>
    <?php endforeach; ?>
    <div class="iw-empty" id="iwTplNoMatch" hidden>No templates match your filters.</div>
  </div>

  <div class="iw-section">
    <header>
      <p class="iw-kicker">Preview content</p>
      <h2>Default template previews</h2>
      <p>Choose one of your own portfolios as the example content for each template. Only published samples are shown to visitors.</p>
    </header>

    <form method="POST" action="<?= $escape($baseUrl . '/admin/portfolio-templates/save') ?>">
      <?= \App\Core\Csrf::field() ?>
      <div class="iw-preview-list">
        <?php foreach ($templates as $template): ?>
          <?php
            $templateKey = (string) ($template['template_key'] ?? '');
            $matching = array_values(array_filter($portfolios, static fn (array $p): bool => ($p['template_key'] ?? '') === $templateKey));
            $selectedId = (int) ($defaults[$templateKey] ?? 0);
          ?>
          <div class="iw-preview-row">
            <label for="default_<?= $escape($templateKey) ?>"><?= $escape($template['display_name'] ?? '') ?></label>
            <select class="form-select" id="default_<?= $escape($templateKey) ?>" name="default_<?= $escape($templateKey) ?>">
              <option value="0">Use the visitor's own draft until a sample is set</option>
              <?php foreach ($matching as $option): ?>
                <option value="<?= (int) $option['id'] ?>" <?= $selectedId === (int) $option['id'] ? 'selected' : '' ?>>
                  <?= $escape($option['display_name']) ?> · <?= $escape($option['website_name']) ?><?= ($option['status'] ?? '') === 'published' ? '' : ' (publish to use as preview)' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if ($matching === []): ?><p class="iw-none">No admin-owned portfolio currently uses this template.</p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="iw-savebar">
        <button class="iw-btn primary" type="submit"><i data-lucide="save"></i> Save template previews</button>
      </div>
    </form>
  </div>
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
(function () {
  var cards = Array.prototype.slice.call(document.querySelectorAll('#iwTplGrid .iw-card'));
  var noMatch = document.getElementById('iwTplNoMatch');
  var search = document.getElementById('iwTplSearch');
  var filters = document.getElementById('iwTplFilters');
  var active = 'all';
  function apply() {
    var q = (search.value || '').toLowerCase().trim();
    var shown = 0;
    cards.forEach(function (card) {
      var okFilter = active === 'all' || card.getAttribute('data-kind') === active || card.getAttribute('data-status') === active;
      var okSearch = q === '' || card.getAttribute('data-search').indexOf(q) !== -1;
      card.hidden = !(okFilter && okSearch);
      if (!card.hidden) shown++;
    });
    noMatch.hidden = !(cards.length > 0 && shown === 0);
  }
  search.addEventListener('input', apply);
  filters.addEventListener('click', function (e) {
    var btn = e.target.closest('.iw-filter');
    if (!btn) return;
    active = btn.getAttribute('data-filter');
    Array.prototype.forEach.call(filters.querySelectorAll('.iw-filter'), function (b) {
      b.classList.toggle('is-active', b === btn);
    });
    apply();
  });
})();
</script>