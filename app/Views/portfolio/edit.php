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
@media(max-width:1000px){.portfolio-setup .ps-layout{grid-template-columns:1fr}}
@media(max-width:560px){.portfolio-setup .ps-heading{display:block}.portfolio-setup .ps-heading .ps-actions{margin-top:1rem}.portfolio-setup .ps-template-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.portfolio-setup .ps-profile-row{grid-template-columns:76px minmax(0,1fr)}.portfolio-setup .ps-avatar{width:76px;height:96px}}
</style>
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
          <div class="ps-panel-head"><h2>Choose a template</h2><span class="text-secondary small">10 designs</span></div>
          <div class="ps-template-grid" role="radiogroup" aria-label="Portfolio templates">
            <?php foreach ($templates as $key => $label): ?>
              <label class="ps-template">
                <input type="radio" name="template_choice" value="<?= $escape($key) ?>" form="portfolio-setup-form" <?= $currentTemplate === $key ? 'checked' : '' ?>>
                <span class="ps-mini ps-mini--<?= $escape($key) ?>" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="ps-template-name"><?= $escape(preg_replace('/\s+[—-].*$/u', '', $label)) ?></span>
                <span class="ps-template-desc"><?= $escape($templateDescriptions[$key] ?? 'A distinctive portfolio design.') ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="ps-actions">
            <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced?id=' . $websiteId) ?>">Edit projects and content</a>
            <a class="btn btn-primary" href="<?= $escape($baseUrl . '/dashboard/websites/preview?id=' . $websiteId) ?>" target="_blank" rel="noopener">Open full preview</a>
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
  const status = document.getElementById('portfolio-save-status');
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
    const selected = form.querySelector('input[name="template_choice"]:checked');
    return (selected && templatePreviewUrls[selected.value]) || ownPreviewUrl;
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
      const selected = form.querySelector('input[name="template_choice"]:checked');
      if (selected) body.set('template_key', selected.value);
      const response = await fetch(form.action, { method: 'POST', body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      const result = await response.json();
      if (currentRequest !== requestNumber) return;
      if (!response.ok || !result.ok) throw new Error(result.message || 'Could not save changes.');
      setStatus('All changes saved', 'saved');
      if (selected && preview) {
        const target = selectedPreviewUrl();
        const separator = target.includes('?') ? '&' : '?';
        preview.src = target + separator + '_preview=' + Date.now();
        if (previewOpen) previewOpen.href = target;
      }
    } catch (error) {
      if (currentRequest === requestNumber) setStatus(error.message || 'Save failed. Check your connection.', 'error');
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
  form.querySelectorAll('input[name="template_choice"]').forEach(input => input.addEventListener('change', saveSetup));
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
  if (avatarForm) avatarForm.addEventListener('submit', event => {
    const file = avatarInput && avatarInput.files && avatarInput.files[0];
    if (!file) { event.preventDefault(); return; }
    if (file.size > 3 * 1024 * 1024) {
      event.preventDefault();
      setStatus('Photo must be smaller than 3 MB.', 'error');
      return;
    }
    setStatus('Uploading photo…', 'saving');
  });
})();
</script>
