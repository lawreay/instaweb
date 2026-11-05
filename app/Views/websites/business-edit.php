<?php
$editorActive = 'content';
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$groups = [
    'Business identity' => [['business_name', 'Business name', 'text'], ['tagline', 'Short tagline', 'text']],
    'Hero section' => [['hero_title', 'Hero heading', 'text'], ['hero_description', 'Introduction', 'textarea']],
    'About section' => [['about_title', 'About heading', 'text'], ['about_text', 'About your business', 'textarea']],
    'Contact details' => [['email', 'Public email', 'email'], ['phone', 'Phone / WhatsApp', 'text'], ['address', 'Business address', 'textarea'], ['cta_label', 'Contact button label', 'text']],
];
$services = $content['services'] ?? null;
if (!is_array($services)) {
    $services = [];
    for ($i = 1; $i <= 3; $i++) {
        $services[] = [
            'title' => $content["service_{$i}_title"] ?? '',
            'text' => $content["service_{$i}_text"] ?? '',
        ];
    }
}
$services = array_values($services);
$siteId = (int) $website['id'];
?>
<style>
.business-editor{--be-ink:#14201A;--be-muted:#5B675F;--be-line:#E4EAE5;--be-soft:#F6F9F6;--be-green:#1F7A4D;--be-green-dark:#14532D;--be-danger:#A32A21;max-width:1050px;margin:auto;color:var(--be-ink)}
.business-editor *{box-sizing:border-box}
.business-editor .be-workspace-nav{display:flex;gap:.35rem;flex-wrap:wrap;padding:.35rem;margin:0 0 1.2rem;border:1px solid var(--be-line);border-radius:14px;background:#fff;position:sticky;top:0;z-index:8}.business-editor .be-workspace-nav button{border:0;border-radius:10px;background:transparent;color:var(--be-muted);padding:.75rem 1rem;font:inherit;font-size:.88rem;font-weight:750;cursor:pointer}.business-editor .be-workspace-nav button.is-active{background:var(--be-ink);color:#fff}.business-editor .be-workspace-nav button span{font-size:.75rem;opacity:.8}.business-editor .be-editor-panel[hidden]{display:none}.business-editor .be-theme-grid,.business-editor .be-brand-grid,.business-editor .be-media-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:1rem;margin-top:1rem}.business-editor .be-theme-card,.business-editor .be-media-item,.business-editor .be-brand-item{display:grid;gap:.7rem;border:1px solid var(--be-line);border-radius:14px;padding:1rem;min-width:0}.business-editor .be-theme-card iframe{width:100%;height:190px;border:1px solid var(--be-line);border-radius:10px;background:#fff}.business-editor .be-theme-card form,.business-editor .be-media-item form{margin:0}.business-editor .be-theme-card .btn,.business-editor .be-media-item .btn{width:100%}.business-editor .be-current{float:right;padding:.2rem .5rem;border-radius:99px;background:#e6f3eb;color:#14532d;font-size:.72rem;font-weight:800}.business-editor .be-brand-item img{max-width:100%;height:110px;object-fit:contain}.business-editor .be-brand-empty{display:grid;place-items:center;min-height:110px;background:var(--be-soft);border:1px dashed #c4d0c8;border-radius:10px;color:var(--be-muted)}.business-editor .be-upload-form{display:flex;align-items:end;flex-wrap:wrap;gap:1rem;margin-top:1rem}.business-editor .be-upload-form label{flex:1 1 220px}.business-editor .be-upload-form input[type=file]{width:100%;font:inherit;font-weight:400}.business-editor .be-storage{display:grid;grid-template-columns:1fr auto;gap:.5rem;padding:1rem;background:var(--be-soft);border-radius:12px;font-size:.85rem}.business-editor .be-storage-track{grid-column:1/-1;height:8px;border-radius:99px;background:#e3e9e4;overflow:hidden}.business-editor .be-storage-track span{display:block;height:100%;background:var(--be-green)}.business-editor .be-media-item img{width:100%;height:145px;object-fit:cover;border-radius:9px}.business-editor .be-media-item small{display:block;color:var(--be-muted)}.business-editor .be-media-item form button{margin-top:.4rem}@media(max-width:600px){.business-editor .be-workspace-nav{top:0}.business-editor .be-workspace-nav button{flex:1 1 40%;padding:.65rem .4rem}}
.business-editor a{text-decoration:none}
.business-editor .be-head{display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1.4rem}
.business-editor .be-eyebrow{margin:0 0 .5rem;font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--be-green)}
.business-editor h1{margin:0;font-size:clamp(1.7rem,3vw,2.2rem);font-weight:800;letter-spacing:-.03em}
.business-editor .be-muted{color:var(--be-muted);font-size:.92rem;margin:.4rem 0 0}
.business-editor .be-head-actions{display:flex;flex-wrap:wrap;gap:.5rem}
.business-editor .btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:12px;font-weight:600;font-size:.9rem}
.business-editor .btn-primary{background:var(--be-green);border-color:var(--be-green);color:#fff}
.business-editor .btn-primary:hover{background:var(--be-green-dark);border-color:var(--be-green-dark);color:#fff}
.business-editor .btn-outline-primary{border-color:var(--be-green);color:var(--be-green-dark);background:#fff}
.business-editor .btn-outline-primary:hover:not(:disabled){background:var(--be-green);border-color:var(--be-green);color:#fff}
.business-editor .btn-outline-secondary,.business-editor .btn-light{border:1px solid #D0D9D2;color:#26382E;background:#fff}
.business-editor .btn-outline-secondary:hover,.business-editor .btn-light:hover{background:var(--be-soft);color:var(--be-ink)}
.business-editor .btn-sm{min-height:44px}
.business-editor .be-card{background:#fff;border:1px solid var(--be-line);border-radius:20px;padding:clamp(1.1rem,3vw,1.6rem);margin-bottom:1.1rem}
.business-editor .be-card h2{margin:0 0 1rem;font-size:1.1rem;font-weight:800}
.business-editor .be-card h2.mb-1{margin-bottom:.25rem}
.business-editor .be-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
.business-editor .wide{grid-column:1/-1}
.business-editor label{display:grid;gap:.4rem;font-size:.86rem;font-weight:700;color:#26382E}
.business-editor input[type=text],.business-editor input[type=email],.business-editor textarea{width:100%;min-height:46px;padding:.7rem .9rem;border:1px solid #D0D9D2;border-radius:10px;font:inherit;font-weight:400;font-size:.95rem;color:var(--be-ink);background:#fff}
.business-editor textarea{min-height:120px;resize:vertical;line-height:1.5}
.business-editor input:focus,.business-editor textarea:focus{outline:0;border-color:var(--be-green);box-shadow:0 0 0 3px rgba(31,122,77,.18)}
.business-editor input[readonly]{background:var(--be-soft);color:#3F4B44}
.business-editor .be-help{font-size:.84rem;color:var(--be-muted);margin:0}
.business-editor .service-tools{display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap}
.business-editor .service-tools h2{margin-bottom:.25rem}
.business-editor .visibility-toggle{display:flex;align-items:center;gap:.7rem;min-height:48px;padding:.5rem .9rem;border:1px solid var(--be-line);border-radius:12px;background:#fff;cursor:pointer;font-weight:600}
.business-editor .visibility-toggle:hover{background:var(--be-soft)}
.business-editor .visibility-toggle input[type=checkbox]{width:20px;height:20px;flex:none;accent-color:var(--be-green)}
.business-editor .service-item{border:1px solid var(--be-line);border-radius:16px;padding:1rem 1.1rem;margin-top:.9rem;background:var(--be-soft)}
.business-editor .service-item-head{display:flex;justify-content:space-between;align-items:center;gap:.7rem;margin-bottom:.9rem}
.business-editor .service-item-head strong{font-size:.95rem}
.business-editor .service-item-actions{display:flex;gap:.4rem}
.business-editor .service-item-actions button{min-width:44px;min-height:44px;padding:0 .8rem;border:1px solid #D0D9D2;background:#fff;border-radius:10px;font-size:.85rem;font-weight:600;color:#26382E;cursor:pointer}
.business-editor .service-item-actions button:hover:not(:disabled){background:#EEF2EF}
.business-editor .service-item-actions button[data-remove-service]{color:var(--be-danger);border-color:#D9B4B0}
.business-editor .service-item-actions button:disabled{opacity:.4;cursor:not-allowed}
.business-editor .service-empty{margin:.9rem 0 0;padding:1.2rem;border:1.5px dashed #C4D0C8;border-radius:14px;color:var(--be-muted);text-align:center;font-size:.9rem}
.business-editor .service-empty[hidden]{display:none}
.business-editor .be-actions{position:sticky;bottom:0;z-index:5;display:flex;flex-wrap:wrap;gap:.7rem;justify-content:flex-end;padding:1rem 0 1.2rem;background:linear-gradient(to top,#fff 55%,rgba(255,255,255,0))}
.business-editor a:focus-visible,.business-editor button:focus-visible{outline:3px solid rgba(31,122,77,.4);outline-offset:2px}
@media(max-width:650px){.business-editor .be-grid{grid-template-columns:1fr}.business-editor .be-head-actions,.business-editor .be-head-actions>*{width:100%}.business-editor .be-actions>*{flex:1 1 100%}.business-editor .service-item-head{flex-wrap:wrap}}
</style>
<section class="client-dashboard iw-client-page business-editor">
<nav class="be-workspace-nav" aria-label="Business editor sections" role="tablist">
  <button type="button" class="is-active" data-editor-tab="content" role="tab" aria-selected="true">Content</button>
  <button type="button" data-editor-tab="design" role="tab" aria-selected="false">Design</button>
  <button type="button" data-editor-tab="branding" role="tab" aria-selected="false">Branding</button>
  <button type="button" data-editor-tab="media" role="tab" aria-selected="false">Media library <span><?= count($mediaItems ?? []) ?></span></button>
</nav>
<header class="be-head">
  <div>
    <p class="be-eyebrow">Business website / Customizer</p>
    <h1>Customize your website</h1>
    <p class="be-muted"><?= $e($website['website_name']) ?> · Changes update your private draft.</p>
  </div>
  <div class="be-head-actions">
    <a class="btn btn-outline-secondary" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.$siteId) ?>">Manage site</a>
    <a class="btn btn-outline-primary" href="<?= $e($baseUrl.'/dashboard/websites/preview?id='.$siteId) ?>" target="_blank" rel="noopener">Preview</a>
  </div>
</header>

<section class="be-editor-panel" data-editor-panel="content" role="tabpanel"><form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/save') ?>">
<?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>"><input type="hidden" name="services_submitted" value="1">

<?php foreach ($groups as $heading => $fields): ?>
<section class="be-card">
  <h2><?= $e($heading) ?></h2>
  <div class="be-grid">
    <?php foreach ($fields as [$key, $label, $type]): ?>
      <label class="<?= $type === 'textarea' ? 'wide' : '' ?>"><?= $e($label) ?>
        <?php if ($type === 'textarea'): ?>
          <textarea name="<?= $e($key) ?>" maxlength="<?= $key === 'about_text' ? 3000 : ($key === 'hero_description' ? 1200 : 240) ?>"><?= $e($content[$key] ?? '') ?></textarea>
        <?php else: ?>
          <input type="<?= $e($type) ?>" name="<?= $e($key) ?>" maxlength="<?= $key === 'business_name' ? 120 : ($key === 'email' ? 190 : 180) ?>" value="<?= $e($content[$key] ?? '') ?>" <?= in_array($key, ['business_name', 'hero_title'], true) ? 'required' : '' ?>>
        <?php endif; ?>
      </label>
    <?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>

<section class="be-card" id="hero-image-field">
  <div class="service-tools">
    <div><h2>Hero image</h2><p class="be-help">Optional image displayed above your main heading.</p></div>
    <button class="btn btn-outline-primary btn-sm" type="button" data-editor-tab="media">Choose from media library</button>
  </div>
  <label style="margin-top:1rem">Selected image path<input type="text" id="hero_image_path" name="hero_image_path" maxlength="100" value="<?= $e($content['hero_image_path'] ?? '') ?>" readonly placeholder="No hero image selected"></label>
  <?php if (!empty($content['hero_image_path'])): ?><p class="be-help" style="margin-top:.5rem">Current image: <?= $e(basename($content['hero_image_path'])) ?></p><?php endif; ?>
</section>

<section class="be-card" id="section-visibility">
  <h2 class="mb-1">Section visibility</h2>
  <p class="be-help" style="margin-bottom:1rem">Choose which sections appear on your published website. Hiding a section does not delete its content.</p>
  <div class="be-grid">
    <?php foreach (['hero' => 'Hero section', 'services' => 'Services section', 'about' => 'About section', 'contact' => 'Contact section'] as $sectionKey => $sectionLabel): ?>
      <label class="visibility-toggle"><input type="hidden" name="section_visibility[<?= $e($sectionKey) ?>]" value="0"><input type="checkbox" name="section_visibility[<?= $e($sectionKey) ?>]" value="1" <?= !isset($content['section_visibility'][$sectionKey]) || !empty($content['section_visibility'][$sectionKey]) ? 'checked' : '' ?>> <?= $e($sectionLabel) ?></label>
    <?php endforeach; ?>
  </div>
</section>

<section class="be-card" id="service-manager">
  <div class="service-tools">
    <div><h2>Services</h2><p class="be-help">Add up to 12 services. Use the arrows to change their order.</p></div>
    <button class="btn btn-outline-primary btn-sm" type="button" id="add-service">+ Add service</button>
  </div>
  <div id="service-list">
    <?php foreach ($services as $index => $service): ?>
    <article class="service-item" data-service-item>
      <div class="service-item-head"><strong data-service-number>Service <?= $index + 1 ?></strong><div class="service-item-actions"><button type="button" data-move-up aria-label="Move service up">↑</button><button type="button" data-move-down aria-label="Move service down">↓</button><button type="button" data-remove-service>Remove</button></div></div>
      <div class="be-grid">
        <label>Service title<input type="text" data-service-field="title" name="services[<?= $index ?>][title]" maxlength="100" value="<?= $e($service['title'] ?? '') ?>" required></label>
        <label class="wide">Description<textarea data-service-field="text" name="services[<?= $index ?>][text]" maxlength="700" required><?= $e($service['text'] ?? '') ?></textarea></label>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <p class="service-empty" id="service-empty" <?= $services !== [] ? 'hidden' : '' ?>>No services added yet. Add one when your business is ready to list its offerings.</p>
</section>

<div class="be-actions">
  <a class="btn btn-light" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.$siteId) ?>">Cancel</a>
  <button class="btn btn-primary" type="submit">Save website content</button>
</div>
</form></section>

<section class="be-editor-panel" data-editor-panel="design" role="tabpanel" hidden>
  <section class="be-card">
    <h2>Choose your website theme</h2>
    <p class="be-help">Themes change the visual presentation without replacing your content.</p>
    <div class="be-theme-grid">
      <?php foreach (($themes ?? []) as $themeKey => $theme): ?>
      <article class="be-theme-card">
        <div><strong><?= $e($theme['name']) ?></strong><?php if (($currentTheme ?? 'evergreen') === $themeKey): ?><span class="be-current">Current</span><?php endif; ?></div>
        <p class="be-help"><?= $e($theme['description']) ?></p>
        <iframe title="<?= $e($theme['name']) ?> preview" src="<?= $e($baseUrl.'/dashboard/websites/business/theme-preview?id='.$siteId.'&theme='.rawurlencode($themeKey)) ?>" loading="lazy" sandbox="allow-same-origin"></iframe>
        <form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/theme/apply') ?>">
          <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>"><input type="hidden" name="theme_key" value="<?= $e($themeKey) ?>">
          <button class="btn <?= ($currentTheme ?? 'evergreen') === $themeKey ? 'btn-outline-secondary' : 'btn-primary' ?>" type="submit" <?= ($currentTheme ?? 'evergreen') === $themeKey ? 'disabled' : '' ?>><?= ($currentTheme ?? 'evergreen') === $themeKey ? 'Currently applied' : 'Apply theme' ?></button>
        </form>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
</section>

<section class="be-editor-panel" data-editor-panel="branding" role="tabpanel" hidden>
  <section class="be-card">
    <h2>Business identity</h2>
    <p class="be-help">Add a logo and browser favicon. JPEG, PNG or WebP, maximum 3 MB each.</p>
    <div class="be-brand-grid">
      <?php foreach (['logo' => ['Business logo', 'logo_path'], 'favicon' => ['Browser favicon', 'favicon_path']] as $field => [$label, $pathKey]): ?>
      <div class="be-brand-item">
        <strong><?= $e($label) ?></strong>
        <?php if (!empty($branding[$pathKey])): ?><img src="<?= $e($baseUrl.'/dashboard/websites/preview/file?id='.$siteId.'&path='.rawurlencode($branding[$pathKey])) ?>" alt="<?= $e($label) ?>"><?php else: ?><div class="be-brand-empty">No image uploaded</div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl.'/dashboard/websites/business/branding/save') ?>" class="be-upload-form">
      <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>">
      <label>Business logo<input type="file" name="logo" accept="image/jpeg,image/png,image/webp"></label>
      <label>Browser favicon<input type="file" name="favicon" accept="image/jpeg,image/png,image/webp"></label>
      <button class="btn btn-primary" type="submit">Save branding</button>
    </form>
  </section>
</section>

<section class="be-editor-panel" data-editor-panel="media" role="tabpanel" hidden>
  <section class="be-card">
    <h2>Media library</h2>
    <p class="be-help">Upload images and select one as your hero image. Images are stored within this website.</p>
    <div class="be-storage">
      <strong>Account storage</strong>
      <span><?= number_format((int) ($storageSummary['used_bytes'] ?? 0) / 1048576, 1) ?> MB used<?= (int) ($storageSummary['limit_bytes'] ?? 0) > 0 ? ' of '.number_format((int) $storageSummary['limit_bytes'] / 1048576, 1).' MB' : ' · Unlimited' ?></span>
      <?php if ((int) ($storageSummary['limit_bytes'] ?? 0) > 0): ?><div class="be-storage-track"><span style="width:<?= (int) ($storageSummary['percent'] ?? 0) ?>%"></span></div><?php endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl.'/dashboard/websites/business/media/upload') ?>" class="be-upload-form">
      <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>">
      <label>Upload image (JPEG, PNG or WebP, max 8 MB)<input type="file" name="media_file" accept="image/jpeg,image/png,image/webp" required></label>
      <button class="btn btn-primary" type="submit">Upload image</button>
    </form>
    <?php if (($mediaItems ?? []) === []): ?><p class="service-empty">No images uploaded yet.</p><?php else: ?>
    <div class="be-media-grid">
      <?php foreach ($mediaItems as $media): ?>
      <article class="be-media-item">
        <img src="<?= $e($media['url']) ?>" alt="<?= $e($media['filename']) ?>" loading="lazy">
        <div><strong><?= $e($media['filename']) ?></strong><small><?= number_format((int) $media['size'] / 1024, 1) ?> KB</small></div>
        <button type="button" class="btn btn-outline-primary" data-use-hero="<?= $e($media['path']) ?>">Use as hero image</button>
        <form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/media/delete') ?>" onsubmit="return confirm('Delete this image?')">
          <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>"><input type="hidden" name="filename" value="<?= $e($media['filename']) ?>">
          <button class="btn btn-outline-secondary" type="submit">Delete</button>
        </form>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</section>
</section>
<script>
(function () {
    const tabButtons = Array.from(document.querySelectorAll('[data-editor-tab]'));
    const tabPanels = Array.from(document.querySelectorAll('[data-editor-panel]'));
    function activateTab(key) {
        tabButtons.forEach((button) => { const active = button.getAttribute('data-editor-tab') === key; button.classList.toggle('is-active', active); button.setAttribute('aria-selected', active ? 'true' : 'false'); });
        tabPanels.forEach((panel) => { panel.hidden = panel.getAttribute('data-editor-panel') !== key; });
    }
    tabButtons.forEach((button) => button.addEventListener('click', () => activateTab(button.getAttribute('data-editor-tab'))));
    document.querySelectorAll('[data-use-hero]').forEach((button) => button.addEventListener('click', () => { const field = document.getElementById('hero_image_path'); if (field) { field.value = button.getAttribute('data-use-hero') || ''; activateTab('content'); field.scrollIntoView({behavior:'smooth',block:'center'}); } }));
    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin || !event.data || event.data.type !== 'instaweb-media-selected' || event.data.target !== 'hero_image_path' || typeof event.data.path !== 'string') return;
        const field = document.getElementById('hero_image_path');
        if (field) field.value = event.data.path;
    });
    const list = document.getElementById('service-list');
    const add = document.getElementById('add-service');
    const empty = document.getElementById('service-empty');
    if (!list || !add || !empty) return;
    const max = 12;
    function refresh() {
        const items = Array.from(list.querySelectorAll('[data-service-item]'));
        items.forEach((item, index) => {
            item.querySelector('[data-service-number]').textContent = 'Service ' + (index + 1);
            item.querySelector('[data-move-up]').disabled = index === 0;
            item.querySelector('[data-move-down]').disabled = index === items.length - 1;
            item.querySelectorAll('[data-service-field]').forEach((field) => {
                field.name = 'services[' + index + '][' + field.getAttribute('data-service-field') + ']';
            });
        });
        empty.hidden = items.length > 0;
        add.disabled = items.length >= max;
        add.textContent = items.length >= max ? 'Maximum reached' : '+ Add service';
    }
    add.addEventListener('click', () => {
        const items = list.querySelectorAll('[data-service-item]');
        if (items.length >= max) return;
        const item = document.createElement('article');
        item.className = 'service-item';
        item.setAttribute('data-service-item', '');
        item.innerHTML = '<div class="service-item-head"><strong data-service-number></strong><div class="service-item-actions"><button type="button" data-move-up aria-label="Move service up">↑</button><button type="button" data-move-down aria-label="Move service down">↓</button><button type="button" data-remove-service>Remove</button></div></div><div class="be-grid"><label>Service title<input type="text" data-service-field="title" maxlength="100" required></label><label class="wide">Description<textarea data-service-field="text" maxlength="700" required></textarea></label></div>';
        list.appendChild(item);
        refresh();
        item.querySelector('input').focus();
    });
    list.addEventListener('click', (event) => {
        const item = event.target.closest('[data-service-item]');
        if (!item) return;
        if (event.target.closest('[data-remove-service]')) item.remove();
        if (event.target.closest('[data-move-up]') && item.previousElementSibling) list.insertBefore(item, item.previousElementSibling);
        if (event.target.closest('[data-move-down]') && item.nextElementSibling) list.insertBefore(item.nextElementSibling, item);
        refresh();
    });
    refresh();
})();
</script>