<?php
$editorActive = in_array(($activeEditorPanel ?? 'content'), ['content', 'design', 'branding', 'media'], true) ? $activeEditorPanel : 'content';
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$groups = [
    'Business identity' => ['Your business name and a short line that describes it.', [['business_name', 'Business name', 'text'], ['tagline', 'Short tagline', 'text']]],
    'Hero section' => ['The first thing visitors see on your site.', [['hero_title', 'Hero heading', 'text'], ['hero_description', 'Introduction', 'textarea']]],
    'About section' => ['Tell visitors who you are and what you do.', [['about_title', 'About heading', 'text'], ['about_text', 'About your business', 'textarea']]],
    'Contact details' => ['How customers can reach you.', [['email', 'Public email', 'email'], ['phone', 'Phone / WhatsApp', 'text'], ['address', 'Business address', 'textarea'], ['cta_label', 'Contact button label', 'text']]],
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
$tabs = ['content' => 'Content', 'design' => 'Design', 'branding' => 'Branding', 'media' => 'Media library'];
?>
<section class="bx-studio business-editor">
  <header class="bx-header">
    <div>
      <p class="bx-crumbs"><a href="<?= $e($baseUrl.'/dashboard/websites/show?id='.$siteId) ?>"><?= $e($website['website_name']) ?></a><span aria-hidden="true">/</span>Business website / Customizer</p>
      <h1>Customize your website</h1>
      <p class="bx-sub">Changes update your private draft.</p>
    </div>
    <div class="bx-header-actions">
      <a class="bx-btn bx-btn-quiet" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.$siteId) ?>">Manage site</a>
      <a class="bx-btn bx-btn-primary" href="<?= $e($baseUrl.'/dashboard/websites/preview?id='.$siteId) ?>" target="_blank" rel="noopener">Preview site ↗</a>
    </div>
  </header>

  <nav class="bx-tabs" aria-label="Business editor sections" role="tablist">
    <?php foreach ($tabs as $tabKey => $tabLabel): ?>
      <button type="button" class="bx-tab <?= $editorActive === $tabKey ? 'is-active' : '' ?>" data-editor-tab="<?= $e($tabKey) ?>" id="be-tab-<?= $e($tabKey) ?>" aria-controls="be-panel-<?= $e($tabKey) ?>" role="tab" aria-selected="<?= $editorActive === $tabKey ? 'true' : 'false' ?>" tabindex="<?= $editorActive === $tabKey ? '0' : '-1' ?>"><?= $e($tabLabel) ?><?php if ($tabKey === 'media'): ?> <span class="bx-count"><?= count($mediaItems ?? []) ?></span><?php endif; ?></button>
    <?php endforeach; ?>
  </nav>

  <!-- Content -->
  <section class="bx-panel" data-editor-panel="content" id="be-panel-content" aria-labelledby="be-tab-content" role="tabpanel"<?= $editorActive === 'content' ? '' : ' hidden' ?>>
    <form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/save') ?>">
      <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>"><input type="hidden" name="services_submitted" value="1">

      <?php foreach ($groups as $heading => [$groupHint, $fields]): ?>
      <section class="bx-row">
        <div class="bx-row-label"><h2><?= $e($heading) ?></h2><p><?= $e($groupHint) ?></p></div>
        <div class="bx-row-fields bx-grid">
          <?php foreach ($fields as [$key, $label, $type]): $req = in_array($key, ['business_name', 'hero_title'], true); ?>
            <label class="bx-field<?= $type === 'textarea' ? ' bx-wide' : '' ?>"><span class="bx-label"><?= $e($label) ?><?= $req ? ' <b aria-hidden="true">*</b>' : '' ?></span>
              <?php if ($type === 'textarea'): ?>
                <textarea name="<?= $e($key) ?>" rows="4" maxlength="<?= $key === 'about_text' ? 3000 : ($key === 'hero_description' ? 1200 : 240) ?>"><?= $e($content[$key] ?? '') ?></textarea>
              <?php else: ?>
                <input type="<?= $e($type) ?>" name="<?= $e($key) ?>" maxlength="<?= $key === 'business_name' ? 120 : ($key === 'email' ? 190 : 180) ?>" value="<?= $e($content[$key] ?? '') ?>" <?= $req ? 'required' : '' ?>>
              <?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>

      <section class="bx-row" id="hero-image-field">
        <div class="bx-row-label"><h2>Hero image</h2><p>Optional image displayed above your main heading.</p></div>
        <div class="bx-row-fields">
          <label class="bx-field"><span class="bx-label">Selected image path</span><input type="text" id="hero_image_path" name="hero_image_path" maxlength="100" value="<?= $e($content['hero_image_path'] ?? '') ?>" readonly placeholder="No hero image selected"></label>
          <?php if (!empty($content['hero_image_path'])): ?><p class="bx-note">Current image: <?= $e(basename($content['hero_image_path'])) ?></p><?php endif; ?>
          <div><button class="bx-btn bx-btn-quiet" type="button" data-open-editor-tab="media">Choose from media library</button></div>
        </div>
      </section>

      <section class="bx-row" id="section-visibility">
        <div class="bx-row-label"><h2>Section visibility</h2><p>Choose which sections appear on your published website. Hiding a section does not delete its content.</p></div>
        <div class="bx-row-fields bx-toggles">
          <?php foreach (['hero' => ['Hero section', 'Heading and introduction'], 'services' => ['Services section', 'Your list of services'], 'about' => ['About section', 'Your story'], 'contact' => ['Contact section', 'Email, phone and address']] as $sectionKey => [$sectionLabel, $sectionHint]): ?>
            <label class="bx-toggle"><input type="hidden" name="section_visibility[<?= $e($sectionKey) ?>]" value="0"><input type="checkbox" name="section_visibility[<?= $e($sectionKey) ?>]" value="1" <?= !isset($content['section_visibility'][$sectionKey]) || !empty($content['section_visibility'][$sectionKey]) ? 'checked' : '' ?>><span class="bx-toggle-text"><strong><?= $e($sectionLabel) ?></strong><small><?= $e($sectionHint) ?></small></span><span class="bx-switch" aria-hidden="true"></span></label>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="bx-row" id="service-manager">
        <div class="bx-row-label"><h2>Services</h2><p>Add up to 12 services. Use the arrows to change their order.</p></div>
        <div class="bx-row-fields">
          <div class="bx-services" id="service-list">
            <?php foreach ($services as $index => $service): ?>
            <article class="bx-service" data-service-item>
              <div class="bx-service-head"><strong data-service-number>Service <?= $index + 1 ?></strong><div class="bx-service-tools"><button type="button" class="bx-tool" data-move-up aria-label="Move service up">↑</button><button type="button" class="bx-tool" data-move-down aria-label="Move service down">↓</button><button type="button" class="bx-tool bx-tool-danger" data-remove-service>Remove</button></div></div>
              <label class="bx-field"><span class="bx-label">Service title <b aria-hidden="true">*</b></span><input type="text" data-service-field="title" name="services[<?= $index ?>][title]" maxlength="100" value="<?= $e($service['title'] ?? '') ?>" required></label>
              <label class="bx-field"><span class="bx-label">Description <b aria-hidden="true">*</b></span><textarea data-service-field="text" name="services[<?= $index ?>][text]" rows="3" maxlength="700" required><?= $e($service['text'] ?? '') ?></textarea></label>
            </article>
            <?php endforeach; ?>
          </div>
          <p class="bx-empty" id="service-empty" <?= $services !== [] ? 'hidden' : '' ?>>No services added yet. Add one when your business is ready to list its offerings.</p>
          <button class="bx-add" type="button" id="add-service">+ Add service</button>
        </div>
      </section>

      <div class="bx-savebar">
        <span class="bx-savebar-note">Changes update your private draft.</span>
        <a class="bx-btn bx-btn-quiet" href="<?= $e($baseUrl.'/dashboard/websites/show?id='.$siteId) ?>">Cancel</a>
        <button class="bx-btn bx-btn-primary" type="submit">Save website content</button>
      </div>
    </form>
  </section>

  <!-- Design -->
  <section class="bx-panel" data-editor-panel="design" id="be-panel-design" aria-labelledby="be-tab-design" role="tabpanel"<?= $editorActive === 'design' ? '' : ' hidden' ?>>
    <div class="bx-intro"><h2>Choose your website theme</h2><p>Themes change the visual presentation without replacing your content.</p></div>
    <?php if (($themes ?? []) === []): ?>
      <p class="bx-empty">No themes are available yet.</p>
    <?php else: ?>
    <div class="bx-themes">
      <?php foreach (($themes ?? []) as $themeKey => $theme): $isCurrent = ($currentTheme ?? 'evergreen') === $themeKey; ?>
      <article class="bx-theme<?= $isCurrent ? ' is-current' : '' ?>">
        <div class="bx-theme-frame">
          <iframe title="<?= $e($theme['name']) ?> preview" src="<?= $e($baseUrl.'/dashboard/websites/business/theme-preview?id='.$siteId.'&theme='.rawurlencode($themeKey)) ?>" loading="lazy" sandbox="allow-same-origin"></iframe>
          <?php if ($isCurrent): ?><span class="bx-tag be-current">In use</span><?php endif; ?>
        </div>
        <div class="bx-theme-info">
          <div><h3><?= $e($theme['name']) ?></h3><p><?= $e($theme['description']) ?></p></div>
          <form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/theme/apply') ?>">
            <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>"><input type="hidden" name="theme_key" value="<?= $e($themeKey) ?>">
            <button class="bx-btn <?= $isCurrent ? 'bx-btn-quiet' : 'bx-btn-primary' ?>" type="submit" <?= $isCurrent ? 'disabled' : '' ?>><?= $isCurrent ? 'Currently applied' : 'Apply theme' ?></button>
          </form>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- Branding -->
  <section class="bx-panel" data-editor-panel="branding" id="be-panel-branding" aria-labelledby="be-tab-branding" role="tabpanel"<?= $editorActive === 'branding' ? '' : ' hidden' ?>>
    <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl.'/dashboard/websites/business/branding/save') ?>">
      <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>">
      <?php foreach (['logo' => ['Business logo', 'logo_path', 'Shown in your site header.'], 'favicon' => ['Browser favicon', 'favicon_path', 'The small icon in the browser tab.']] as $field => [$label, $pathKey, $hint]): ?>
      <section class="bx-row">
        <div class="bx-row-label"><h2><?= $e($label) ?></h2><p><?= $e($hint) ?> JPEG, PNG or WebP, maximum 3 MB.</p></div>
        <div class="bx-row-fields bx-asset">
          <div class="bx-asset-preview">
            <?php if (!empty($branding[$pathKey])): ?><img src="<?= $e($baseUrl.'/dashboard/websites/preview/file?id='.$siteId.'&path='.rawurlencode($branding[$pathKey])) ?>" alt="<?= $e($label) ?>"><?php else: ?><span>No image uploaded</span><?php endif; ?>
          </div>
          <label class="bx-field"><span class="bx-label"><?= $e($label) ?></span><input type="file" name="<?= $e($field) ?>" accept="image/jpeg,image/png,image/webp"></label>
        </div>
      </section>
      <?php endforeach; ?>
      <div class="bx-savebar"><button class="bx-btn bx-btn-primary" type="submit">Save branding</button></div>
    </form>
  </section>

  <!-- Media library -->
  <section class="bx-panel" data-editor-panel="media" id="be-panel-media" aria-labelledby="be-tab-media" role="tabpanel"<?= $editorActive === 'media' ? '' : ' hidden' ?>>
    <div class="bx-intro"><h2>Media library</h2><p>Upload images and select one as your hero image. Images are stored within this website.</p></div>
    <div class="bx-storage">
      <div><strong>Account storage</strong><span><?= number_format((int) ($storageSummary['used_bytes'] ?? 0) / 1048576, 1) ?> MB used<?= (int) ($storageSummary['limit_bytes'] ?? 0) > 0 ? ' of '.number_format((int) $storageSummary['limit_bytes'] / 1048576, 1).' MB' : ' · Unlimited' ?></span></div>
      <?php if ((int) ($storageSummary['limit_bytes'] ?? 0) > 0): ?><div class="bx-meter" aria-hidden="true"><i style="width:<?= (int) ($storageSummary['percent'] ?? 0) ?>%"></i></div><?php endif; ?>
    </div>
    <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl.'/dashboard/websites/business/media/upload') ?>" class="bx-drop">
      <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>">
      <label class="bx-field"><span class="bx-label">Upload image <small>JPEG, PNG or WebP, max 8 MB</small></span><input type="file" name="media_file" accept="image/jpeg,image/png,image/webp" required></label>
      <button class="bx-btn bx-btn-primary" type="submit">Upload image</button>
    </form>
    <?php if (($mediaItems ?? []) === []): ?>
      <p class="bx-empty">No images uploaded yet.</p>
    <?php else: ?>
    <div class="bx-media-grid">
      <?php foreach ($mediaItems as $media): ?>
      <article class="bx-media">
        <img src="<?= $e($media['url']) ?>" alt="<?= $e($media['filename']) ?>" loading="lazy">
        <div class="bx-media-meta"><strong><?= $e($media['filename']) ?></strong><small><?= number_format((int) $media['size'] / 1024, 1) ?> KB</small></div>
        <div class="bx-media-actions">
          <button type="button" class="bx-btn bx-btn-quiet bx-btn-sm" data-use-hero="<?= $e($media['path']) ?>">Use as hero</button>
          <form method="POST" action="<?= $e($baseUrl.'/dashboard/websites/business/media/delete') ?>" onsubmit="return confirm('Delete this image?')">
            <?= \App\Core\Csrf::field() ?><input type="hidden" name="id" value="<?= $siteId ?>"><input type="hidden" name="filename" value="<?= $e($media['filename']) ?>">
            <button class="bx-btn bx-btn-ghost-danger bx-btn-sm" type="submit">Delete</button>
          </form>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</section>

<style>
/* Business editor — its own design: focused single column, underline tabs, label-left settings rows. Visual only. */
.bx-studio{--bx-ink:#1a2420;--bx-muted:#66746b;--bx-line:#dde4df;--bx-soft:#f5f8f6;--bx-green:#1f7a4d;--bx-green-dk:#17603c;--bx-tint:#eaf4ee;--bx-danger:#b42318;max-width:62rem;margin:0 auto;padding:.5rem 0 3rem;color:var(--bx-ink)}
.bx-studio *{box-sizing:border-box}
.bx-studio a{text-decoration:none}

/* header */
.bx-header{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-end;gap:1rem;padding-bottom:1.25rem}
.bx-crumbs{display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;margin:0 0 .6rem;font-size:.82rem;color:var(--bx-muted)}
.bx-crumbs a{color:var(--bx-green-dk);font-weight:600}
.bx-crumbs a:hover{text-decoration:underline}
.bx-header h1{margin:0;font-size:clamp(1.6rem,2.6vw,2.1rem);font-weight:700;letter-spacing:-.025em;line-height:1.15}
.bx-sub{margin:.4rem 0 0;color:var(--bx-muted);font-size:.92rem}
.bx-header-actions{display:flex;flex-wrap:wrap;gap:.5rem}

/* buttons */
.bx-btn{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;min-height:2.5rem;padding:.5rem 1rem;border:1px solid transparent;border-radius:.5rem;font:600 .88rem/1 inherit;font-family:inherit;cursor:pointer;transition:background-color .15s,border-color .15s,color .15s}
.bx-btn-sm{min-height:2.1rem;padding:.35rem .7rem;font-size:.8rem}
.bx-btn-primary{background:var(--bx-green);color:#fff}
.bx-btn-primary:hover{background:var(--bx-green-dk);color:#fff}
.bx-btn-quiet{background:#fff;color:var(--bx-ink);border-color:#cfd8d2}
.bx-btn-quiet:hover:not(:disabled){border-color:var(--bx-green);color:var(--bx-green-dk)}
.bx-btn-ghost-danger{background:transparent;color:var(--bx-danger)}
.bx-btn-ghost-danger:hover{background:#fef3f2}
.bx-btn:disabled{opacity:.55;cursor:not-allowed}
.bx-studio a:focus-visible,.bx-studio button:focus-visible{outline:2px solid var(--bx-green);outline-offset:2px}

/* tabs */
.bx-tabs{display:flex;gap:1.75rem;margin-bottom:.5rem;border-bottom:1px solid var(--bx-line);overflow-x:auto;position:sticky;top:0;z-index:8;background:var(--bs-body-bg,#fff)}
.bx-tab{position:relative;flex:0 0 auto;padding:.85rem 0;border:0;background:transparent;color:var(--bx-muted);font:600 .92rem/1 inherit;font-family:inherit;cursor:pointer;white-space:nowrap}
.bx-tab:hover{color:var(--bx-ink)}
.bx-tab.is-active{color:var(--bx-ink)}
.bx-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:var(--bx-green)}
.bx-count{display:inline-block;min-width:1.25rem;margin-left:.25rem;padding:.1rem .4rem;border-radius:999px;background:var(--bx-soft);color:var(--bx-muted);font-size:.72rem;text-align:center}
.bx-panel[hidden]{display:none}

/* settings rows */
.bx-row{display:grid;grid-template-columns:minmax(0,15rem) minmax(0,1fr);gap:1.5rem 3rem;padding:1.75rem 0;border-bottom:1px solid var(--bx-line)}
.bx-row-label h2{margin:0 0 .35rem;font-size:1rem;font-weight:700;letter-spacing:-.01em}
.bx-row-label p{margin:0;color:var(--bx-muted);font-size:.86rem;line-height:1.55}
.bx-row-fields{display:grid;gap:1rem;align-content:start;min-width:0}
.bx-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.bx-wide{grid-column:1/-1}
.bx-note{margin:0;color:var(--bx-muted);font-size:.82rem}
.bx-intro{padding:1.5rem 0 1.25rem}
.bx-intro h2{margin:0 0 .3rem;font-size:1.15rem;font-weight:700;letter-spacing:-.015em}
.bx-intro p{margin:0;color:var(--bx-muted);font-size:.9rem}

/* fields */
.bx-field{display:grid;gap:.4rem;min-width:0}
.bx-label{font-size:.84rem;font-weight:600;color:#33433a}
.bx-label b{color:var(--bx-danger);font-weight:600}
.bx-label small{margin-left:.35rem;color:var(--bx-muted);font-weight:400;font-size:.78rem}
.bx-studio input:not([type=checkbox]):not([type=file]):not([type=hidden]),.bx-studio textarea{display:block;width:100%;padding:.65rem .8rem;border:1px solid #cfd8d2;border-radius:.5rem;background:var(--bs-body-bg,#fff);color:inherit;font:400 .93rem/1.45 inherit;font-family:inherit}
.bx-studio textarea{min-height:6rem;resize:vertical}
.bx-studio input:focus,.bx-studio textarea:focus{outline:0;border-color:var(--bx-green);box-shadow:0 0 0 3px rgba(31,122,77,.16)}
.bx-studio input[readonly]{background:var(--bx-soft);color:#4b5a51}
.bx-studio input[type=file]{width:100%;padding:.55rem;border:1px dashed #b7c4bb;border-radius:.5rem;background:var(--bx-soft);font:400 .85rem/1.3 inherit;color:var(--bx-muted)}

/* toggles */
.bx-toggles{gap:0}
.bx-toggle{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--bx-line);cursor:pointer}
.bx-toggle:first-child{padding-top:0}
.bx-toggle:last-child{border-bottom:0;padding-bottom:0}
.bx-toggle input[type=checkbox]{position:absolute;opacity:0;width:1px;height:1px}
.bx-toggle-text strong{display:block;font-size:.92rem}
.bx-toggle-text small{display:block;color:var(--bx-muted);font-size:.8rem}
.bx-switch{flex:0 0 auto;position:relative;width:2.6rem;height:1.5rem;border-radius:.4rem;background:#c8d2cb;transition:background-color .15s}
.bx-switch::after{content:"";position:absolute;top:.2rem;left:.2rem;width:1.1rem;height:1.1rem;border-radius:.25rem;background:#fff;transition:transform .15s}
.bx-toggle input:checked ~ .bx-switch{background:var(--bx-green)}
.bx-toggle input:checked ~ .bx-switch::after{transform:translateX(1.1rem)}
.bx-toggle input:focus-visible ~ .bx-switch{outline:2px solid var(--bx-green);outline-offset:2px}

/* services */
.bx-services{display:grid;gap:1.1rem}
.bx-service{display:grid;gap:.85rem;padding:1rem 0 0 1.1rem;border-left:3px solid var(--bx-tint)}
.bx-service:focus-within{border-left-color:var(--bx-green)}
.bx-service-head{display:flex;justify-content:space-between;align-items:center;gap:.75rem}
.bx-service-head strong{font-size:.82rem;font-weight:700;color:var(--bx-green-dk)}
.bx-service-tools{display:flex;gap:.3rem}
.bx-tool{min-width:2.1rem;min-height:2.1rem;padding:0 .6rem;border:1px solid transparent;border-radius:.4rem;background:var(--bx-soft);color:#33433a;font:600 .8rem/1 inherit;font-family:inherit;cursor:pointer}
.bx-tool:hover:not(:disabled){background:var(--bx-tint);color:var(--bx-green-dk)}
.bx-tool:disabled{opacity:.35;cursor:not-allowed}
.bx-tool-danger{color:var(--bx-danger);background:transparent}
.bx-tool-danger:hover:not(:disabled){background:#fef3f2;color:var(--bx-danger)}
.bx-add{width:100%;min-height:2.8rem;border:1.5px dashed #b7c4bb;border-radius:.5rem;background:transparent;color:var(--bx-green-dk);font:600 .9rem/1 inherit;font-family:inherit;cursor:pointer}
.bx-add:hover:not(:disabled){background:var(--bx-tint);border-color:var(--bx-green)}
.bx-add:disabled{opacity:.5;cursor:not-allowed}
.bx-empty{margin:0;padding:1.5rem 0;color:var(--bx-muted);font-size:.9rem}
.bx-empty[hidden]{display:none}

/* themes */
.bx-themes{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,18rem),1fr));gap:1.5rem}
.bx-theme{display:grid;gap:.9rem;align-content:start}
.bx-theme-frame{position:relative;border:1px solid var(--bx-line);border-radius:.6rem;overflow:hidden;background:var(--bx-soft);transition:border-color .15s,box-shadow .15s}
.bx-theme:hover .bx-theme-frame{border-color:#b7c4bb}
.bx-theme.is-current .bx-theme-frame{border-color:var(--bx-green);box-shadow:0 0 0 2px var(--bx-green)}
.bx-theme iframe{display:block;width:100%;height:210px;border:0;background:#fff}
.bx-tag{position:absolute;top:.6rem;left:.6rem;padding:.25rem .55rem;border-radius:.35rem;background:var(--bx-green);color:#fff;font-size:.72rem;font-weight:700}
.bx-theme-info{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem}
.bx-theme-info h3{margin:0;font-size:.98rem;font-weight:700}
.bx-theme-info p{margin:.2rem 0 0;color:var(--bx-muted);font-size:.84rem;line-height:1.5}
.bx-theme-info form{margin:0;flex:0 0 auto}

/* branding */
.bx-asset{grid-template-columns:11rem minmax(0,1fr);align-items:center}
.bx-asset-preview{display:grid;place-items:center;height:7.5rem;border:1px solid var(--bx-line);border-radius:.6rem;background:repeating-conic-gradient(#f3f6f4 0% 25%,#fff 0% 50%) 50%/14px 14px;color:var(--bx-muted);font-size:.78rem;text-align:center;overflow:hidden}
.bx-asset-preview img{max-width:85%;max-height:85%;object-fit:contain}

/* media */
.bx-storage{display:grid;gap:.5rem;padding:1rem 0;border-bottom:1px solid var(--bx-line);font-size:.86rem}
.bx-storage>div:first-child{display:flex;justify-content:space-between;gap:1rem}
.bx-storage span{color:var(--bx-muted)}
.bx-meter{height:.35rem;border-radius:99px;background:#e6ece8;overflow:hidden}
.bx-meter i{display:block;height:100%;background:var(--bx-green)}
.bx-drop{display:flex;flex-wrap:wrap;align-items:flex-end;gap:.75rem;margin:1.25rem 0;padding:1.1rem;border:1.5px dashed #b7c4bb;border-radius:.7rem;background:var(--bx-soft)}
.bx-drop .bx-field{flex:1 1 16rem}
.bx-media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,12.5rem),1fr));gap:1.1rem}
.bx-media{display:grid;gap:.5rem;align-content:start;min-width:0}
.bx-media img{display:block;width:100%;aspect-ratio:1;object-fit:cover;border-radius:.6rem;border:1px solid var(--bx-line);background:var(--bx-soft)}
.bx-media-meta{display:grid}
.bx-media-meta strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.84rem}
.bx-media-meta small{color:var(--bx-muted);font-size:.76rem}
.bx-media-actions{display:flex;gap:.4rem;align-items:center}
.bx-media-actions form{margin:0}

/* save bar */
.bx-savebar{position:sticky;bottom:0;z-index:5;display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;gap:.6rem;margin-top:.5rem;padding:.9rem 0;border-top:1px solid var(--bx-line);background:var(--bs-body-bg,#fff)}
.bx-savebar-note{margin-right:auto;color:var(--bx-muted);font-size:.82rem}

@media(max-width:760px){
  .bx-row{grid-template-columns:minmax(0,1fr);gap:1rem;padding:1.4rem 0}
  .bx-asset{grid-template-columns:minmax(0,1fr)}
}
@media(max-width:560px){
  .bx-grid{grid-template-columns:minmax(0,1fr)}
  .bx-header-actions,.bx-header-actions>*{width:100%}
  .bx-savebar>*{flex:1 1 100%}
  .bx-savebar-note{display:none}
  .bx-theme-info{flex-direction:column}
}
@media(prefers-reduced-motion:reduce){.bx-studio *{transition:none}}
</style>

<script>
(function () {
    const tabButtons = Array.from(document.querySelectorAll('.bx-tabs [data-editor-tab]'));
    const tabPanels = Array.from(document.querySelectorAll('[data-editor-panel]'));
    function activateTab(key, moveFocus = false) {
        tabButtons.forEach((button) => {
            const active = button.getAttribute('data-editor-tab') === key;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.tabIndex = active ? 0 : -1;
            if (active && moveFocus) button.focus();
        });
        tabPanels.forEach((panel) => { panel.hidden = panel.getAttribute('data-editor-panel') !== key; });
    }
    document.querySelectorAll('[data-open-editor-tab]').forEach((button) => button.addEventListener('click', () => activateTab(button.getAttribute('data-open-editor-tab'))));
    tabButtons.forEach((button, index) => {
        button.addEventListener('click', () => activateTab(button.getAttribute('data-editor-tab')));
        button.addEventListener('keydown', (event) => {
            let nextIndex = index;
            if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabButtons.length;
            else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabButtons.length) % tabButtons.length;
            else if (event.key === 'Home') nextIndex = 0;
            else if (event.key === 'End') nextIndex = tabButtons.length - 1;
            else return;
            event.preventDefault();
            activateTab(tabButtons[nextIndex].getAttribute('data-editor-tab'), true);
        });
    });
    document.querySelectorAll('[data-use-hero]').forEach((button) => button.addEventListener('click', () => { const field = document.getElementById('hero_image_path'); if (field) { field.value = button.getAttribute('data-use-hero') || ''; activateTab('content'); field.scrollIntoView({behavior:'smooth',block:'center'}); } }));
    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin || !event.data || event.data.type !== 'instaweb-media-selected' || event.data.target !== 'hero_image_path' || typeof event.data.path !== 'string') return;
        const field = document.getElementById('hero_image_path');
        if (field) field.value = event.data.path;
    });
    activateTab('<?= $e($editorActive) ?>');
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
        item.className = 'bx-service';
        item.setAttribute('data-service-item', '');
        item.innerHTML = '<div class="bx-service-head"><strong data-service-number></strong><div class="bx-service-tools"><button type="button" class="bx-tool" data-move-up aria-label="Move service up">↑</button><button type="button" class="bx-tool" data-move-down aria-label="Move service down">↓</button><button type="button" class="bx-tool bx-tool-danger" data-remove-service>Remove</button></div></div><label class="bx-field"><span class="bx-label">Service title <b aria-hidden="true">*</b></span><input type="text" data-service-field="title" maxlength="100" required></label><label class="bx-field"><span class="bx-label">Description <b aria-hidden="true">*</b></span><textarea data-service-field="text" rows="3" maxlength="700" required></textarea></label>';
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