<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$websiteId = (int) $website['id'];
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$avatarPath = trim((string) ($portfolio['avatar_path'] ?? ''));
$avatarUrl = $avatarPath !== '' && !str_contains($avatarPath, '..')
    && preg_match('#^uploads/[A-Za-z0-9_./-]+$#', $avatarPath)
    ? $baseUrl . '/' . ltrim($avatarPath, '/')
    : '';
$templateDescriptions = [
    'classic' => 'Clear, adaptable and familiar.',
    'editorial' => 'Typography-led, like a printed feature.',
    '3d' => 'Immersive and technical.',
    'minimal' => 'Quiet, focused and content-first.',
    'noir' => 'Bold, dark and editorial.',
    'aurora' => 'Luminous motion and layered colour.',
    'studio' => 'Warm, creative and tactile.',
    'orbit' => 'A technical mission-control aesthetic.',
    'prism' => 'Expressive colour and confident shapes.',
    'archive' => 'A paper-led creative catalogue.',
];
$currentTemplate = (string) ($portfolio['template_key'] ?? 'classic');
$isPremiumUser = (bool) ($isPremiumUser ?? false);
$premiumTemplateKeys = is_array($premiumTemplateKeys ?? null) ? $premiumTemplateKeys : [];
$premiumPlanPrice = trim((string) ($premiumPlanPrice ?? '')) ?: 'See plan pricing';
$premiumUpgradeEnabled = (bool) ($premiumUpgradeEnabled ?? false);
$ownPreviewUrl = $baseUrl . '/dashboard/websites/preview?id=' . $websiteId;
$templatePreviewUrls = is_array($templatePreviewUrls ?? null) ? $templatePreviewUrls : [];
$initialPreviewUrl = $templatePreviewUrls[$currentTemplate] ?? $ownPreviewUrl;
?>
<style>
.portfolio-setup{--ps-ink:#17191d;--ps-muted:#6d7480;--ps-line:#e5e7eb;--ps-accent:#f97316;color:var(--ps-ink)}
.portfolio-setup .ps-heading{display:flex;justify-content:space-between;gap:1.5rem;align-items:flex-start;margin-bottom:1.5rem}
.portfolio-setup .ps-eyebrow{font-size:.72rem;letter-spacing:.15em;font-weight:800;text-transform:uppercase;color:var(--ps-muted);margin:0 0 .55rem}
.portfolio-setup .ps-heading h1{font-size:clamp(1.8rem,3vw,2.5rem);font-weight:800;letter-spacing:-.04em;margin:0}
.portfolio-setup .ps-heading p{color:var(--ps-muted);margin:.55rem 0 0;max-width:42rem}
.portfolio-setup .ps-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,.85fr);gap:1.25rem;align-items:start}
.portfolio-setup .ps-panel{background:var(--bs-body-bg,#fff);border:1px solid var(--ps-line);border-radius:1rem;padding:clamp(1rem,2vw,1.5rem);margin-bottom:1.25rem;min-width:0}
.portfolio-setup .ps-panel h2{font-size:1.05rem;font-weight:800;margin:0}
.portfolio-setup .ps-panel-head{display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-bottom:1.2rem}
.portfolio-setup .ps-field{display:block;margin-bottom:1rem;font-weight:700;font-size:.9rem}
.portfolio-setup .ps-field input{display:block;width:100%;margin-top:.45rem;border:1px solid #d5d9e0;border-radius:.65rem;padding:.8rem .9rem;font:inherit;font-weight:400;background:var(--bs-body-bg,#fff);color:inherit}
.portfolio-setup .ps-field input:focus{outline:3px solid #f9731626;border-color:var(--ps-accent)}
.portfolio-setup .ps-profile-row{display:grid;grid-template-columns:96px minmax(0,1fr);gap:1rem;align-items:center}
.portfolio-setup .ps-avatar{width:96px;height:112px;object-fit:cover;border-radius:.8rem;background:#f1f3f5;border:1px solid var(--ps-line)}
.portfolio-setup .ps-avatar-empty{display:grid;place-items:center;color:var(--ps-muted);font-size:.75rem;text-align:center;padding:.5rem}
.portfolio-setup .ps-upload{display:flex;flex-wrap:wrap;gap:.6rem;align-items:center}
.portfolio-setup .ps-upload input{max-width:100%;font-size:.85rem}
.portfolio-setup .ps-status{font-size:.8rem;color:var(--ps-muted);min-height:1.2rem}
.portfolio-setup .ps-status[data-state="saved"]{color:#16804a}
.portfolio-setup .ps-status[data-state="error"]{color:#b42318}
.portfolio-setup .ps-toast{position:fixed;right:1.25rem;bottom:1.25rem;z-index:2000;display:flex;align-items:flex-start;gap:.7rem;max-width:min(26rem,calc(100vw - 2rem));padding:.9rem 1rem;border-radius:.8rem;background:#17211b;color:#fff;box-shadow:0 12px 35px #0003;font-size:.9rem;font-weight:650;opacity:0;transform:translateY(10px);pointer-events:none;transition:opacity .18s ease,transform .18s ease}
.portfolio-setup .ps-toast.is-visible{opacity:1;transform:translateY(0)}
.portfolio-setup .ps-toast[data-state="error"]{background:#8f1d18}
.portfolio-setup .ps-template-active{display:inline-flex;align-items:center;gap:.25rem;margin-top:.55rem;padding:.25rem .5rem;border-radius:999px;background:#fff1e8;color:#9a3412;font-size:.7rem;font-weight:800}
.portfolio-setup .ps-template-active[hidden]{display:none}
.portfolio-setup .ps-active-control{border:1px solid var(--ps-accent);background:#fff7ed;color:#9a3412;font-weight:800;border-radius:.65rem;padding:.55rem .8rem;font-size:.82rem}
.portfolio-setup .ps-active-control:disabled{opacity:1;cursor:default}
.portfolio-setup .ps-template-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
.portfolio-setup .ps-template{position:relative;display:block;border:1px solid var(--ps-line);border-radius:.85rem;padding:.6rem;text-align:left;cursor:pointer;background:transparent;color:inherit;transition:border-color .15s,box-shadow .15s}
.portfolio-setup .ps-template:has(input:checked){border:2px solid var(--ps-accent);padding:calc(.6rem - 1px);box-shadow:0 0 0 3px #f9731617}
.portfolio-setup .ps-template input{position:absolute;opacity:0;pointer-events:none}
.portfolio-setup .ps-template:focus-within{outline:3px solid #f9731640}
.portfolio-setup .ps-mini{height:82px;border-radius:.5rem;overflow:hidden;padding:.65rem;display:flex;gap:.35rem;flex-direction:column;justify-content:center}
.portfolio-setup .ps-mini span{display:block;height:5px;border-radius:9px;background:currentColor;opacity:.8;width:64%}
.portfolio-setup .ps-mini span:nth-child(2){width:90%;opacity:.35}
.portfolio-setup .ps-mini span:nth-child(3){width:44%;opacity:.55}
.portfolio-setup .ps-mini--classic{background:#f4f5f7;color:#343a40}
.portfolio-setup .ps-mini--editorial{background:#f1e9dc;color:#4b3325}
.portfolio-setup .ps-mini--3d{background:#111827;color:#b9c8ff}
.portfolio-setup .ps-mini--minimal{background:#fff;color:#151515;border:1px solid #eee}
.portfolio-setup .ps-mini--noir{background:#111;color:#f3eee7}
.portfolio-setup .ps-mini--aurora{background:linear-gradient(120deg,#101c37,#4b347a);color:#b8ffe8}
.portfolio-setup .ps-mini--studio{background:#f2e5d4;color:#744b2f}
.portfolio-setup .ps-mini--orbit{background:#071e2b;color:#71e4f1}
.portfolio-setup .ps-mini--prism{background:linear-gradient(120deg,#ffe7b2,#f5a3cc);color:#39224f}
.portfolio-setup .ps-mini--archive{background:#eee7d9;color:#473d2e;border:1px solid #d5c9b4}
.portfolio-setup .ps-template-name{display:block;font-weight:800;font-size:.88rem;margin:.6rem 0 .2rem}
.portfolio-setup .ps-template-desc{display:block;color:var(--ps-muted);font-size:.75rem;line-height:1.4}
.portfolio-setup .ps-preview-stage{width:100%;overflow:hidden;border:1px solid var(--ps-line);border-radius:.8rem;background:#e9edf2;position:relative;}
.portfolio-setup .ps-preview-frame{display:block;width:1440px;height:900px;max-width:none;border:0;border-radius:0;background:#fff;transform:scale(var(--ps-preview-scale, .3));transform-origin:top left;}
.portfolio-setup .ps-preview-stage-label{display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.5rem .7rem;background:#f5f6f8;border-bottom:1px solid var(--ps-line);font-size:.72rem;color:var(--ps-muted);}
.portfolio-setup .ps-preview-note{font-size:.8rem;color:var(--ps-muted);margin:.65rem 0 0}
.portfolio-setup .ps-actions{display:flex;flex-wrap:wrap;gap:.65rem;margin-top:1rem}
.portfolio-setup .ps-upgrade-trigger{display:inline-flex;align-items:center;justify-content:center;gap:.45rem;border:1px solid #f97316;background:#fff7ed;color:#9a3412;border-radius:.65rem;padding:.65rem .9rem;font-weight:800;text-decoration:none}
.portfolio-setup .ps-upgrade-trigger:hover{background:#ffedd5;color:#7c2d12}
.portfolio-setup .ps-modal[hidden]{display:none}
.portfolio-setup .ps-modal{position:fixed;inset:0;z-index:3000;display:grid;place-items:center;padding:1rem}
.portfolio-setup .ps-modal-backdrop{position:absolute;inset:0;background:#111827b8;backdrop-filter:blur(3px)}
.portfolio-setup .ps-modal-card{position:relative;width:min(100%,31rem);max-height:min(90vh,48rem);overflow:auto;background:#fff;color:#17191d;border-radius:1.15rem;padding:1.5rem;box-shadow:0 24px 80px #0004}
.portfolio-setup .ps-modal-close{border:0;background:#f3f4f6;border-radius:999px;width:2.25rem;height:2.25rem;font-size:1.2rem;line-height:1;cursor:pointer}
.portfolio-setup .ps-modal-kicker{display:inline-flex;align-items:center;gap:.4rem;font-size:.75rem;font-weight:850;text-transform:uppercase;letter-spacing:.1em;color:#9a3412;background:#fff1e8;border-radius:999px;padding:.35rem .65rem}
.portfolio-setup .ps-modal-price{font-size:1.8rem;font-weight:900;letter-spacing:-.04em;margin:.6rem 0}
.portfolio-setup .ps-modal-benefits{display:grid;gap:.7rem;list-style:none;padding:0;margin:1.25rem 0}
.portfolio-setup .ps-modal-benefits li{display:flex;gap:.65rem;align-items:flex-start}
.portfolio-setup .ps-modal-benefits li:before{content:'✓';font-weight:900;color:#15803d}
.portfolio-setup .ps-modal-footnote{font-size:.78rem;color:#6b7280;line-height:1.5}
.portfolio-setup .ps-modal-primary{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;border:0;border-radius:.75rem;background:#f97316;color:#fff;font-weight:850;padding:.9rem 1rem}
.portfolio-setup .ps-modal-primary:disabled{opacity:.55;cursor:not-allowed}
@media(max-width:1000px){.portfolio-setup .ps-layout{grid-template-columns:1fr}}
@media(max-width:560px){.portfolio-setup .ps-heading{display:block}.portfolio-setup .ps-heading .ps-actions{margin-top:1rem}.portfolio-setup .ps-template-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.portfolio-setup .ps-profile-row{grid-template-columns:76px minmax(0,1fr)}.portfolio-setup .ps-avatar{width:76px;height:96px}}
</style>
<div class="ps-toast" id="portfolio-toast" role="status" aria-live="polite" data-state="success"><span id="portfolio-toast-icon" aria-hidden="true">✓</span><span id="portfolio-toast-message">Changes saved successfully.</span></div>
<?php if (!$isPremiumUser): ?>
<div class="ps-modal" id="portfolio-upgrade-modal" hidden aria-hidden="true">
  <div class="ps-modal-backdrop" data-upgrade-close></div>
  <section class="ps-modal-card" role="dialog" aria-modal="true" aria-labelledby="portfolio-upgrade-title" aria-describedby="portfolio-upgrade-description">
    <div class="d-flex align-items-start justify-content-between gap-3">
      <span class="ps-modal-kicker"><i data-lucide="crown" aria-hidden="true"></i> InstaWeb Premium</span>
      <button type="button" class="ps-modal-close" data-upgrade-close aria-label="Close upgrade dialog">×</button>
    </div>
    <h2 id="portfolio-upgrade-title" class="mt-3 mb-2 fw-bold">Make your portfolio stand out.</h2>
    <p id="portfolio-upgrade-description" class="text-secondary mb-2">Unlock every design and publish a polished portfolio without the Free-plan watermark.</p>
    <div class="ps-modal-price"><?= $escape($premiumPlanPrice) ?></div>
    <ul class="ps-modal-benefits">
      <li>Access all Premium and administrator-created templates</li>
      <li>Remove the Powered by InstaWeb watermark</li>
      <li>Export your portfolio to PDF</li>
      <li>Unlock the other benefits included in your Premium plan</li>
    </ul>
    <?php if ($premiumUpgradeEnabled): ?>
      <form method="post" action="<?= $escape($baseUrl . '/payments/paychangu/premium') ?>">
        <?= \App\Core\Csrf::field() ?>
        <button class="ps-modal-primary" type="submit"><i data-lucide="lock-keyhole" aria-hidden="true"></i> Upgrade securely with PayChangu</button>
      </form>
      <p class="ps-modal-footnote mt-3 mb-0">You'll continue to PayChangu to complete payment. Premium access is activated only after the payment is verified.</p>
    <?php else: ?>
      <button class="ps-modal-primary" type="button" disabled>Upgrades temporarily unavailable</button>
      <p class="ps-modal-footnote mt-3 mb-0">Premium checkout is not currently configured or enabled. You can review plan details on the <a href="<?= $escape($baseUrl . '/pricing') ?>">pricing page</a>.</p>
    <?php endif; ?>
    <p class="text-center small mt-3 mb-0"><a href="<?= $escape($baseUrl . '/pricing') ?>">Compare all plan features</a></p>
  </section>
</div>
<?php endif; ?>
<section class="client-dashboard iw-client-page portfolio-setup">
  <div class="client-main">
    <header class="ps-heading">
      <div>
        <p class="ps-eyebrow">Portfolio Studio / Setup</p>
        <h1>Set up your portfolio</h1>
        <p>Add the essentials, choose a visual style, and preview it. Projects and detailed sections belong in the Advanced Editor.</p>
      </div>
      <div class="ps-actions">
        <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/show?id=' . $websiteId) ?>">Back to Website</a>
        <a class="btn btn-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced?id=' . $websiteId) ?>"><i data-lucide="sliders-horizontal"></i> Advanced Editor</a>
      </div>
    </header>

    <div class="ps-layout">
      <div>
        <section class="ps-panel">
          <div class="ps-panel-head"><h2>Basic information</h2><span class="ps-status" id="portfolio-save-status" role="status" aria-live="polite">Changes save automatically</span></div>
          <form id="portfolio-setup-form" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/autosave') ?>" method="post">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $websiteId ?>">
            <label class="ps-field" for="display_name">Full name
              <input id="display_name" name="display_name" maxlength="120" required autocomplete="name" value="<?= $escape($portfolio['display_name'] ?? '') ?>" placeholder="Your full name">
            </label>
            <label class="ps-field" for="headline">Role / headline
              <input id="headline" name="headline" maxlength="180" value="<?= $escape($portfolio['headline'] ?? '') ?>" placeholder="e.g. UI Designer and Front-end Developer">
            </label>
          </form>
            <div class="ps-profile-row">
              <?php if ($avatarUrl !== ''): ?>
                <img class="ps-avatar" id="portfolio-avatar-preview" src="<?= $escape($avatarUrl) ?>" alt="Portfolio photo preview">
              <?php else: ?>
                <div class="ps-avatar ps-avatar-empty" id="portfolio-avatar-empty">Photo preview</div>
                <img class="ps-avatar" id="portfolio-avatar-preview" src="" alt="Portfolio photo preview" hidden>
              <?php endif; ?>
              <div>
                <strong>Profile photo</strong>
                <p class="text-secondary small mb-2">JPEG, PNG or WebP, up to 3 MB.</p>
                <form id="portfolio-avatar-form" method="POST" enctype="multipart/form-data" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/avatar') ?>">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= $websiteId ?>">
                  <div class="ps-upload"><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required><button class="btn btn-outline-primary btn-sm" type="submit">Upload photo</button></div>
                </form>
              </div>
            </div>
        </section>

        <section class="ps-panel">
          <div class="ps-panel-head"><h2>Choose a template</h2><div class="d-flex flex-wrap align-items-center gap-2"><button type="button" class="ps-active-control" id="portfolio-active-template" disabled>✓ Active: <?= $escape(preg_replace('/\s+[—-].*$/u', '', $templates[$currentTemplate] ?? ucfirst($currentTemplate))) ?></button><?php if (!$isPremiumUser): ?><button type="button" class="ps-upgrade-trigger" data-upgrade-open><i data-lucide="crown"></i> Upgrade to Premium</button><?php endif; ?></div></div>
          <div class="ps-template-grid" role="radiogroup" aria-label="Portfolio templates">
            <?php foreach ($templates as $key => $label): ?>
              <label class="ps-template">
                <input type="radio" name="template_choice" value="<?= $escape($key) ?>" data-premium="<?= in_array($key, $premiumTemplateKeys, true) || !in_array($key, ['classic', 'minimal', 'graduate'], true) ? '1' : '0' ?>" form="portfolio-setup-form" <?= $currentTemplate === $key ? 'checked' : '' ?>>
                <span class="ps-mini ps-mini--<?= $escape($key) ?>" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="ps-template-name"><?= $escape(preg_replace('/\s+[—-].*$/u', '', $label)) ?></span>
                <span class="ps-template-desc"><?= $escape($templateDescriptions[$key] ?? 'A distinctive portfolio design.') ?></span>
                <?php if (in_array($key, $premiumTemplateKeys, true) || !in_array($key, ['classic', 'minimal', 'graduate'], true)): ?>
                  <span class="badge <?= $isPremiumUser ? 'text-bg-warning' : 'text-bg-secondary' ?> mt-2"><?= $isPremiumUser ? 'Premium included' : 'Premium' ?></span>
                <?php else: ?>
                  <span class="badge text-bg-success mt-2">Free</span>
                <?php endif; ?>
                <span class="ps-template-active" data-active-template="<?= $escape($key) ?>" <?= $currentTemplate === $key ? '' : 'hidden' ?>>✓ Active Template</span>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="ps-actions">
            <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced?id=' . $websiteId) ?>">Edit projects and content</a>
            <a class="btn btn-primary" href="<?= $escape($baseUrl . '/dashboard/websites/preview?id=' . $websiteId) ?>" target="_blank" rel="noopener">Open full preview</a>
            <?php if ($isPremiumUser): ?><a class="btn btn-outline-dark" href="<?= $escape($baseUrl . '/dashboard/websites/preview?id=' . $websiteId . '&export=pdf') ?>" target="_blank" rel="noopener">Export portfolio to PDF</a><?php else: ?><button type="button" class="btn btn-outline-dark" data-upgrade-open><i data-lucide="file-down"></i> Unlock PDF export</button><?php endif; ?>
          </div>
        </section>
      </div>

      <aside class="ps-panel">
        <div class="ps-panel-head"><h2>Template preview</h2><a class="small" id="portfolio-preview-open" href="<?= $escape($initialPreviewUrl) ?>" target="_blank" rel="noopener">Open separately ↗</a></div>
        <div class="ps-preview-stage" id="portfolio-preview-stage">
          <div class="ps-preview-stage-label"><span>Desktop view</span><span>1440 px viewport · scaled to fit</span></div>
          <iframe class="ps-preview-frame" id="portfolio-template-preview" title="Desktop preview of your selected portfolio template" src="<?= $escape($initialPreviewUrl) ?>" loading="lazy"></iframe>
        </div>
        <p class="ps-preview-note">When an admin has configured a published sample, this panel shows that template example. Otherwise it falls back to your draft. Use Open full preview to inspect your own portfolio.</p>
      </aside>
    </div>
  </div>
</section>
<script>
(function () {
  const form = document.getElementById('portfolio-setup-form');
  if (!form) return;
  const isPremiumUser = <?= $isPremiumUser ? 'true' : 'false' ?>;
  const status = document.getElementById('portfolio-save-status');
  const toast = document.getElementById('portfolio-toast');
  const toastMessage = document.getElementById('portfolio-toast-message');
  const toastIcon = document.getElementById('portfolio-toast-icon');
  const activeTemplateButton = document.getElementById('portfolio-active-template');
  const upgradeModal = document.getElementById('portfolio-upgrade-modal');
  let upgradeReturnFocus = null;
  function openUpgradeModal() {
    if (!upgradeModal) return;
    upgradeReturnFocus = document.activeElement;
    upgradeModal.hidden = false;
    upgradeModal.setAttribute('aria-hidden', 'false');
    const closeButton = upgradeModal.querySelector('[data-upgrade-close]');
    if (closeButton) closeButton.focus();
  }
  function closeUpgradeModal() {
    if (!upgradeModal) return;
    upgradeModal.hidden = true;
    upgradeModal.setAttribute('aria-hidden', 'true');
    if (upgradeReturnFocus && typeof upgradeReturnFocus.focus === 'function' && document.contains(upgradeReturnFocus)) upgradeReturnFocus.focus();
  }
  document.querySelectorAll('[data-upgrade-open]').forEach(button => button.addEventListener('click', openUpgradeModal));
  if (upgradeModal) {
    upgradeModal.querySelectorAll('[data-upgrade-close]').forEach(button => button.addEventListener('click', closeUpgradeModal));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !upgradeModal.hidden) closeUpgradeModal(); });
  }
  let activeTemplateKey = <?= json_encode($currentTemplate) ?>;
  let toastTimer = null;
  function showToast(message, state) {
    if (!toast || !toastMessage) return;
    toastMessage.textContent = message;
    toast.dataset.state = state || 'success';
    if (toastIcon) toastIcon.textContent = state === 'error' ? '!' : '✓';
    toast.classList.add('is-visible');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('is-visible'), state === 'error' ? 5500 : 3200);
  }
  function updateActiveTemplate() {
    document.querySelectorAll('[data-active-template]').forEach(badge => { badge.hidden = badge.dataset.activeTemplate !== activeTemplateKey; });
    const activeRadio = document.querySelector('input[name="template_choice"][value="' + CSS.escape(activeTemplateKey) + '"]');
    if (activeTemplateButton) {
      const label = activeRadio?.closest('.ps-template')?.querySelector('.ps-template-name')?.textContent?.trim() || activeTemplateKey;
      activeTemplateButton.textContent = '✓ Active: ' + label;
    }
  }
  updateActiveTemplate();
  const preview = document.getElementById('portfolio-template-preview');
  const previewOpen = document.getElementById('portfolio-preview-open');
  const previewStage = document.getElementById('portfolio-preview-stage');
  const previewFrame = document.getElementById('portfolio-template-preview');
  const desktopViewportWidth = 1440;
  const desktopViewportHeight = 900;
  function fitDesktopPreview() {
    if (!previewStage || !previewFrame) return;
    const availableWidth = previewStage.clientWidth;
    const scale = availableWidth / desktopViewportWidth;
    previewFrame.style.setProperty('--ps-preview-scale', String(scale));
    previewFrame.style.width = desktopViewportWidth + 'px';
    previewFrame.style.height = desktopViewportHeight + 'px';
    previewStage.style.height = (desktopViewportHeight * scale + 34) + 'px';
  }
  fitDesktopPreview();
  if (previewStage && 'ResizeObserver' in window) {
    new ResizeObserver(fitDesktopPreview).observe(previewStage);
  } else {
    window.addEventListener('resize', fitDesktopPreview);
  }
  const avatarInput = document.getElementById('avatar');
  const avatarPreview = document.getElementById('portfolio-avatar-preview');
  const avatarEmpty = document.getElementById('portfolio-avatar-empty');
  let timer = null;
  let requestNumber = 0;
  let activeObjectUrl = null;
  const ownPreviewUrl = <?= json_encode($ownPreviewUrl) ?>;
  const templatePreviewUrls = <?= json_encode($templatePreviewUrls, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  function selectedPreviewUrl() {
    const selected = document.querySelector('input[name="template_choice"]:checked');
    if (!selected) return ownPreviewUrl;
    if (templatePreviewUrls[selected.value]) return templatePreviewUrls[selected.value];
    return ownPreviewUrl + '&template=' + encodeURIComponent(selected.value);
  }
  function setStatus(message, state) {
    status.textContent = message;
    status.dataset.state = state || '';
  }
  async function saveSetup() {
    if (!form.reportValidity()) return;
    const currentRequest = ++requestNumber;
    setStatus('Saving…', 'saving');
    try {
      const body = new FormData(form);
      const selected = document.querySelector('input[name="template_choice"]:checked');
      if (selected) body.set('template_key', (!isPremiumUser && selected.dataset.premium === '1' && selected.value !== activeTemplateKey) ? activeTemplateKey : selected.value);
      const response = await fetch(form.action, { method: 'POST', body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      const result = await response.json();
      if (currentRequest !== requestNumber) return;
      if (!response.ok || !result.ok) throw new Error(result.message || 'Could not save changes.');
      setStatus('All changes saved', 'saved');
      const templateChanged = !!selected && selected.value !== activeTemplateKey && (isPremiumUser || selected.dataset.premium !== '1');
      if (selected && (isPremiumUser || selected.dataset.premium !== '1' || selected.value === activeTemplateKey)) activeTemplateKey = selected.value;
      updateActiveTemplate();
      showToast(templateChanged ? (selected.closest('.ps-template')?.querySelector('.ps-template-name')?.textContent?.trim() || selected.value) + ' is now your active template.' : 'Your portfolio changes were saved successfully.', 'success');
      if (selected && preview) {
        const target = selectedPreviewUrl();
        const separator = target.includes('?') ? '&' : '?';
        preview.src = target + separator + '_preview=' + Date.now();
        if (previewOpen) previewOpen.href = target;
      }
    } catch (error) {
      if (currentRequest === requestNumber) {
        setStatus(error.message || 'Save failed. Check your connection.', 'error');
        showToast(error.message || 'Save failed. Check your connection and try again.', 'error');
      }
    }
  }
  form.querySelectorAll('#display_name, #headline').forEach(input => {
    input.addEventListener('input', () => {
      setStatus('Unsaved changes…', '');
      clearTimeout(timer);
      timer = setTimeout(saveSetup, 650);
    });
    input.addEventListener('blur', () => { clearTimeout(timer); saveSetup(); });
  });
  document.querySelectorAll('input[name="template_choice"][form="portfolio-setup-form"]').forEach(input => input.addEventListener('change', () => {
    const target = selectedPreviewUrl();
    if (preview) {
      const separator = target.includes('?') ? '&' : '?';
      preview.src = target + separator + '_preview=' + Date.now();
    }
    if (previewOpen) previewOpen.href = target;
    if (!isPremiumUser && input.dataset.premium === '1' && input.value !== activeTemplateKey) {
      setStatus('Previewing a Premium template. Upgrade to activate it.', '');
      showToast('Premium template preview only. Upgrade to activate and publish this design.', 'error');
      updateActiveTemplate();
      openUpgradeModal();
      return;
    }
    if (activeTemplateButton) activeTemplateButton.textContent = 'Saving template…';
    saveSetup();
  }));
  if (avatarInput) avatarInput.addEventListener('change', () => {
    const file = avatarInput.files && avatarInput.files[0];
    if (!file) return;
    if (activeObjectUrl) URL.revokeObjectURL(activeObjectUrl);
    activeObjectUrl = URL.createObjectURL(file);
    avatarPreview.src = activeObjectUrl;
    avatarPreview.hidden = false;
    if (avatarEmpty) avatarEmpty.hidden = true;
  });
  const avatarForm = document.getElementById('portfolio-avatar-form');
  if (avatarForm) avatarForm.addEventListener('submit', async event => {
    event.preventDefault();
    const file = avatarInput && avatarInput.files && avatarInput.files[0];
    if (!file) { showToast('Choose a profile photo first.', 'error'); return; }
    if (file.size > 3 * 1024 * 1024) {
      setStatus('Photo must be smaller than 3 MB.', 'error');
      showToast('Photo must be smaller than 3 MB.', 'error');
      return;
    }
    setStatus('Uploading photo…', 'saving');
    const submitButton = avatarForm.querySelector('button[type="submit"]');
    if (submitButton) submitButton.disabled = true;
    try {
      const response = await fetch(avatarForm.action, { method: 'POST', body: new FormData(avatarForm), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      const result = await response.json().catch(() => ({ ok: false, message: 'Photo upload failed. Check the file and try again.' }));
      if (!response.ok || !result.ok) throw new Error(result.message || 'Photo upload failed.');
      if (result.avatar_url && avatarPreview) {
        avatarPreview.src = result.avatar_url + '?_=' + Date.now();
        avatarPreview.hidden = false;
        if (avatarEmpty) avatarEmpty.hidden = true;
      }
      avatarInput.value = '';
      setStatus('Profile photo saved', 'saved');
      showToast('Profile photo saved successfully.', 'success');
      if (preview) { const target = selectedPreviewUrl(); preview.src = target + (target.includes('?') ? '&' : '?') + '_preview=' + Date.now(); }
    } catch (error) {
      const message = error.message || 'Photo upload failed. Check your connection.';
      setStatus(message, 'error');
      showToast(message, 'error');
    } finally { if (submitButton) submitButton.disabled = false; }
  });
})();
</script>
