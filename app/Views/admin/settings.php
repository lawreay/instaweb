<?php
$value = static fn (string $key, string $default = ''): string => htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
$checked = static fn (string $key, string $default = '0'): string => (($settings[$key] ?? $default) === '1') ? 'checked' : '';
?>
<div class="admin-pro-shell">
  <?php $active = 'settings'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

  <section class="admin-pro-main iw-settings">
    <style>
      .iw-settings { --iw-ink:#14201A; --iw-muted:#5B675F; --iw-line:#E4EAE5; --iw-soft:#F6F9F6; --iw-green:#1F7A4D; --iw-green-dark:#14532D; --iw-tint:#E3F2E8; }
      .iw-settings .admin-pro-topbar { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
      .iw-settings .admin-pro-kicker { margin:0 0 4px; font-size:12px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--iw-green); }
      .iw-settings .admin-pro-topbar h1 { margin:0; font-size:30px; font-weight:800; letter-spacing:-.01em; color:var(--iw-ink); }
      .iw-settings .admin-pro-actions { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
      .iw-settings .admin-pro-actions .btn { min-height:44px; display:inline-flex; align-items:center; gap:6px; border-radius:10px; font-weight:600; font-size:14px; }
      .iw-settings .admin-pro-actions .btn-primary { background:var(--iw-green); border-color:var(--iw-green); }
      .iw-settings .admin-pro-actions .btn-primary:hover { background:var(--iw-green-dark); border-color:var(--iw-green-dark); }
      .iw-settings .admin-settings-status { border-radius:12px; }

      .iw-settings .settings-pro-card { display:grid; grid-template-columns:248px minmax(0,1fr); gap:0; background:#fff; border:1px solid var(--iw-line); border-radius:20px; overflow:hidden; }
      .iw-settings .settings-pro-tabs { display:flex; flex-direction:column; gap:4px; padding:16px; background:var(--iw-soft); border-right:1px solid var(--iw-line); position:sticky; top:0; align-self:start; max-height:100vh; overflow-y:auto; }
      .iw-settings .settings-pro-tab { display:flex; align-items:center; gap:12px; width:100%; min-height:44px; padding:0 14px; border:0; border-radius:12px; background:transparent; color:#3F4B44; font-size:14px; font-weight:500; text-align:left; cursor:pointer; }
      .iw-settings .settings-pro-tab svg { width:18px; height:18px; flex:none; }
      .iw-settings .settings-pro-tab:hover { background:#EAF1EB; }
      .iw-settings .settings-pro-tab.active { background:var(--iw-tint); color:var(--iw-green-dark); font-weight:700; }
      .iw-settings .settings-pro-content { padding:28px 32px 36px; min-width:0; }
      .iw-settings .settings-pro-panel h2 { margin:0 0 20px; padding-bottom:14px; border-bottom:1px solid var(--iw-line); font-size:22px; font-weight:800; color:var(--iw-ink); }

      .iw-settings .form-label { font-size:13px; font-weight:600; color:#26382E; margin-bottom:6px; }
      .iw-settings .form-control, .iw-settings .form-select { min-height:44px; border-radius:10px; border:1px solid #D8E0DA; font-size:14px; color:var(--iw-ink); }
      .iw-settings textarea.form-control { min-height:96px; }
      .iw-settings .form-control:focus, .iw-settings .form-select:focus { border-color:var(--iw-green); box-shadow:0 0 0 3px rgba(31,122,77,.15); }
      .iw-settings .form-control-color { width:100%; min-height:44px; padding:4px; }
      .iw-settings .form-text { font-size:12px; color:var(--iw-muted); }
      .iw-settings .settings-check { display:flex; align-items:center; gap:12px; min-height:44px; padding:8px 14px; border:1px solid var(--iw-line); border-radius:12px; background:#fff; font-size:14px; font-weight:600; color:var(--iw-ink); cursor:pointer; margin:0; }
      .iw-settings .settings-check:hover { background:var(--iw-soft); }
      .iw-settings .settings-check input { width:18px; height:18px; flex:none; accent-color:var(--iw-green); }
      .iw-settings .settings-toggle-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:10px; }
      .iw-settings .settings-fieldset { border:1px solid var(--iw-line); border-radius:16px; padding:18px 20px 20px; margin-bottom:20px; background:#fff; }
      .iw-settings .settings-fieldset h3 { margin:0 0 14px; font-size:13px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; color:var(--iw-green-dark); }
      .iw-settings .settings-note { border-radius:14px; border:1px solid #D5E8DC; background:#F2F8F4; color:#26382E; font-size:13px; margin-bottom:20px; }
      .iw-settings .settings-note ul { margin:8px 0 0; padding-left:18px; }
      .iw-settings .settings-pro-panel .table { font-size:14px; }
      .iw-settings .settings-pro-panel .btn, .iw-settings .admin-pro-card .btn { min-height:44px; border-radius:10px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
      .iw-settings .settings-pro-panel .btn-sm { min-height:36px; }
      .iw-settings .settings-pro-panel .btn-primary, .iw-settings .admin-pro-card .btn-primary { background:var(--iw-green); border-color:var(--iw-green); }
      .iw-settings .appearance-preview { border:1px solid var(--iw-line); border-radius:16px; overflow:hidden; }
      .iw-settings .admin-pro-card { background:#fff; border:1px solid var(--iw-line); border-radius:20px; padding:24px; margin-top:24px; }
      .iw-settings .admin-pro-card h2 { font-weight:800; }
      .iw-settings .admin-pro-footer { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:16px 4px; font-size:13px; color:var(--iw-muted); }
      .iw-settings .admin-pro-footer a { color:var(--iw-green); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; min-height:44px; }

      @media (max-width: 900px) {
        .iw-settings .settings-pro-card { grid-template-columns:1fr; }
        .iw-settings .settings-pro-tabs { position:static; flex-direction:row; overflow-x:auto; max-height:none; border-right:0; border-bottom:1px solid var(--iw-line); padding:10px; }
        .iw-settings .settings-pro-tab { width:auto; white-space:nowrap; flex:none; }
        .iw-settings .settings-pro-content { padding:20px 16px 28px; }
        .iw-settings .admin-pro-topbar h1 { font-size:24px; }
      }
    </style>

    <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
      <?= \App\Core\Csrf::field() ?>
      <header class="admin-pro-topbar">
        <div class="admin-pro-topbar-left">
          <p class="admin-pro-kicker">Platform control center</p>
          <h1>Settings</h1>
        </div>
        <div class="admin-pro-topbar-right admin-pro-actions">
          <a class="btn btn-outline-primary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/app-update.php', ENT_QUOTES, 'UTF-8') ?>">
            <i data-lucide="upload-cloud"></i> Application Update
          </a>
          <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin', ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
          <button class="btn btn-primary" type="submit">Save Changes</button>
        </div>
      </header>

      <?php foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $type): ?>
        <?php $message = \App\Core\Session::pullFlash($key); ?>
        <?php if ($message): ?>
          <div class="admin-settings-status alert alert-<?= $type ?> shadow-sm" role="alert">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>

      <div class="settings-pro-card">
        <aside class="settings-pro-tabs" role="tablist">
          <?php foreach ($tabs as $id => [$label, $icon]): ?>
            <button class="settings-pro-tab <?= $id === 'general' ? 'active' : '' ?>" type="button" data-settings-tab="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
              <i data-lucide="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
              <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            </button>
          <?php endforeach; ?>
        </aside>

        <div class="settings-pro-content">
          <section class="settings-pro-panel active" data-settings-panel="general">
            <h2>General</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Site Name</label><input class="form-control" name="site_name" value="<?= $value('site_name', 'Instaweb') ?>"></div>
              <div class="col-md-6"><label class="form-label">Site Email</label><input class="form-control" name="site_email" value="<?= $value('site_email', 'admin@instaweb.free.dev') ?>"></div>
              <div class="col-12"><label class="settings-check"><input type="checkbox" name="allow_registration" value="1" <?= $checked('allow_registration', '1') ?>> Allow public registration</label></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="appearance">
            <h2>Appearance & Branding</h2>
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label">Primary Color</label><input type="color" class="form-control form-control-color" name="primary_color" value="<?= $value('primary_color', '#0D6EFD') ?>"></div>
              <div class="col-md-4"><label class="form-label">Secondary Color</label><input type="color" class="form-control form-control-color" name="secondary_color" value="<?= $value('secondary_color', '#00B4D8') ?>"></div>
              <div class="col-md-4"><label class="form-label">Text Color</label><input type="color" class="form-control form-control-color" name="text_color" value="<?= $value('text_color', '#e2dddd') ?>"></div>
              <div class="col-md-4"><label class="form-label">Sidebar Background</label><input type="color" class="form-control form-control-color" name="sidebar_background_color" value="<?= $value('sidebar_background_color', '#FFFFFF') ?>"></div>
              <div class="col-md-4"><label class="form-label">Sidebar Text</label><input type="color" class="form-control form-control-color" name="sidebar_text_color" value="<?= $value('sidebar_text_color', '#475569') ?>"></div>
              <div class="col-md-4"><label class="form-label">App Icon URL</label><input class="form-control" name="app_icon_url" value="<?= $value('app_icon_url') ?>"></div>
              <div class="col-md-4"><label class="form-label">Upload App Icon</label><input type="file" accept="image/*" class="form-control" name="app_icon_file"></div>
              <div class="col-md-4"><label class="form-label">App Logo URL</label><input class="form-control" name="dashboard_logo" value="<?= $value('dashboard_logo') ?>"></div>
              <div class="col-md-4"><label class="form-label">Upload App Logo</label><input type="file" accept="image/*" class="form-control" name="dashboard_logo_file"></div>
              <div class="col-md-4"><label class="form-label">Admin Logo URL</label><input class="form-control" name="admin_logo" value="<?= $value('admin_logo') ?>"></div>
              <div class="col-md-4"><label class="form-label">Upload Admin Logo</label><input type="file" accept="image/*" class="form-control" name="admin_logo_file"></div>
              <div class="col-md-4"><label class="form-label">Muted Text Color</label><input type="color" class="form-control form-control-color" name="muted_text_color" value="<?= $value('muted_text_color', '#64748b') ?>"></div>
              <div class="col-md-4"><label class="form-label">Page Background</label><input class="form-control" name="page_background_color" value="<?= $value('page_background_color', 'linear-gradient(180deg, #f8fafc 0%, #eef7ff 100%)') ?>" placeholder="color or gradient"></div>
              <div class="col-md-4"><label class="form-label">Surface Background</label><input type="color" class="form-control form-control-color" name="surface_background_color" value="<?= $value('surface_background_color', '#ffffff') ?>"></div>
              <div class="col-md-4"><label class="form-label">Surface Border Color</label><input type="color" class="form-control form-control-color" name="surface_border_color" value="<?= $value('surface_border_color', 'rgba(15, 23, 42, 0.08)') ?>"></div>
              <div class="col-md-6"><label class="form-label">Open Graph Image</label><input class="form-control" name="open_graph_image" value="<?= $value('open_graph_image') ?>"></div>
              <div class="col-md-6"><label class="form-label">Browser Title</label><input class="form-control" name="browser_title" value="<?= $value('browser_title') ?>"></div>
              <div class="col-12"><label class="form-label">Meta Description</label><textarea class="form-control" name="meta_description" rows="3"><?= $value('meta_description') ?></textarea></div>
              <div class="col-12"><label class="form-label">SEO Keywords</label><input class="form-control" name="seo_keywords" value="<?= $value('seo_keywords', 'website hosting, web hosting, site builder, file uploads') ?>"></div>
              <div class="col-12"><label class="settings-check"><input type="checkbox" name="dark_mode_enabled" value="1" <?= $checked('dark_mode_enabled') ?>> Enable dark mode support</label></div>
            </div>

            <div class="appearance-preview mt-4">
              <div class="preview-sidebar">
                <div class="preview-brand">
                  <div class="preview-brand-icon" id="preview-brand-icon">
                    <?php if ($value('app_icon_url')): ?>
                      <img id="preview-icon-image" src="<?= htmlspecialchars($value('app_icon_url'), ENT_QUOTES, 'UTF-8') ?>" alt="App icon">
                    <?php else: ?>
                      <span id="preview-icon-letter">T</span>
                    <?php endif; ?>
                  </div>
                  <div>
                    <strong id="preview-brand-name"><?= htmlspecialchars($value('site_name', 'Instaweb'), ENT_QUOTES, 'UTF-8') ?></strong>
                    <span>Admin Preview</span>
                  </div>
                </div>
                <nav class="preview-nav">
                  <a class="active">Dashboard</a>
                  <a>Users</a>
                  <a>Settings</a>
                </nav>
              </div>
              <div class="preview-main">
                <div class="preview-header">
                  <img id="preview-logo-image" class="preview-logo" src="<?= htmlspecialchars($value('dashboard_logo') ?: '', ENT_QUOTES, 'UTF-8') ?>" alt="App logo" <?= $value('dashboard_logo') ? '' : 'style="display:none;"' ?> >
                  <div id="preview-logo-placeholder" class="preview-logo preview-logo-placeholder" <?= $value('dashboard_logo') ? 'style="display:none;"' : '' ?>>T</div>
                  <div>
                    <h3>Live appearance preview</h3>
                    <p>Changes update the sidebar, logo, icon, and text colors instantly.</p>
                  </div>
                </div>
                <div class="preview-card">
                  <p>Use the color controls above to tint the app shell and sidebar.</p>
                  <p>Enter logo and icon URLs to update the preview and deploy the branding across the admin interface.</p>
                </div>
              </div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="branding">
            <h2>Branding & Site Identity</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Platform Name</label><input class="form-control" name="footer_brand_name" value="<?= $value('footer_brand_name', $settings['site_name'] ?? 'Instaweb') ?>"></div>
              <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" name="footer_tagline" value="<?= $value('footer_tagline', 'Build • Host • Grow') ?>"></div>
              <div class="col-12"><label class="form-label">Brand Description</label><textarea class="form-control" name="footer_description" rows="3"><?= $value('footer_description', 'Affordable hosting for students, churches, portfolios and businesses.') ?></textarea></div>
              <div class="col-md-6"><label class="form-label">Footer Logo URL</label><input class="form-control" name="footer_logo_url" value="<?= $value('footer_logo_url') ?>"></div>
              <div class="col-md-6"><label class="form-label">Footer Favicon URL</label><input class="form-control" name="footer_favicon_url" value="<?= $value('footer_favicon_url') ?>"></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="homepage">
            <h2>Hero & Homepage</h2>
            <div class="settings-note alert alert-info">
              <strong>Hero setup:</strong> edit the first screen from here. Use image for a static hero, video for motion, and leave the secondary URL blank to hide the second button.
            </div>
            <div class="settings-fieldset">
              <h3>Hero Copy</h3>
              <div class="row g-3">
                <div class="col-md-5"><label class="form-label">Kicker</label><input class="form-control" name="homepage_hero_kicker" value="<?= $value('homepage_hero_kicker', 'BUILD AND HOST WEBSITES IN MINUTES') ?>"></div>
                <div class="col-md-7"><label class="form-label">Title</label><input class="form-control" name="homepage_hero_title" value="<?= $value('homepage_hero_title', 'Build, host, and launch your website without the usual stress.') ?>"></div>
                <div class="col-12"><label class="form-label">Subtitle</label><textarea class="form-control" name="homepage_hero_subtitle" rows="3"><?= $value('homepage_hero_subtitle', 'Instaweb helps students, churches, small businesses, freelancers, and NGOs create a strong online presence with simple tools and dependable hosting.') ?></textarea></div>
                <div class="col-12"><label class="form-label">Small note below buttons</label><input class="form-control" name="homepage_hero_note" value="<?= $value('homepage_hero_note', 'Perfect for anyone who wants a website that looks professional and is easy to manage.') ?>"></div>
              </div>
            </div>
            <div class="settings-fieldset">
              <h3>Buttons</h3>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Primary button text</label><input class="form-control" name="homepage_cta_text" value="<?= $value('homepage_cta_text', 'Start Your Free Site') ?>"></div>
                <div class="col-md-6"><label class="form-label">Primary button URL</label><input class="form-control" name="homepage_cta_url" value="<?= $value('homepage_cta_url', '/register') ?>"></div>
                <div class="col-md-6"><label class="form-label">Secondary button text</label><input class="form-control" name="homepage_secondary_cta_text" value="<?= $value('homepage_secondary_cta_text', 'Sign In') ?>"></div>
                <div class="col-md-6"><label class="form-label">Secondary button URL</label><input class="form-control" name="homepage_secondary_cta_url" value="<?= $value('homepage_secondary_cta_url', '/login') ?>"></div>
              </div>
            </div>
            <div class="settings-fieldset">
              <h3>Media & Layout</h3>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Hero image URL</label><input class="form-control" name="homepage_hero_image" value="<?= $value('homepage_hero_image', 'https://images.pexels.com/photos/30530403/pexels-photo-30530403.jpeg?auto=compress&cs=tinysrgb&w=1280') ?>"></div>
                <div class="col-md-6"><label class="form-label">Hero video URL</label><input class="form-control" name="homepage_hero_video" value="<?= $value('homepage_hero_video') ?>"></div>
                <div class="col-md-6"><label class="form-label">Media alt text</label><input class="form-control" name="homepage_hero_media_alt" value="<?= $value('homepage_hero_media_alt', 'Instaweb dashboard showcase') ?>"></div>
                <div class="col-md-3"><label class="form-label">Alignment</label><select class="form-control" name="homepage_hero_alignment"><option value="split" <?= $value('homepage_hero_alignment', 'split') === 'split' ? 'selected' : '' ?>>Split</option><option value="center" <?= $value('homepage_hero_alignment') === 'center' ? 'selected' : '' ?>>Centered</option></select></div>
                <div class="col-md-3"><label class="form-label">Overlay strength</label><select class="form-control" name="homepage_hero_overlay"><option value="soft" <?= $value('homepage_hero_overlay', 'soft') === 'soft' ? 'selected' : '' ?>>Soft</option><option value="strong" <?= $value('homepage_hero_overlay') === 'strong' ? 'selected' : '' ?>>Strong</option><option value="none" <?= $value('homepage_hero_overlay') === 'none' ? 'selected' : '' ?>>None</option></select></div>
              </div>
            </div>
            <div class="settings-fieldset">
              <h3>Homepage Sections</h3>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Features kicker</label><input class="form-control" name="homepage_section_heading" value="<?= $value('homepage_section_heading', 'Features') ?>"></div>
                <div class="col-md-6"><label class="form-label">Features heading</label><input class="form-control" name="homepage_section_subheading" value="<?= $value('homepage_section_subheading', 'Everything You Need') ?>"></div>
              </div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="footer">
            <h2>Footer Builder</h2>
            <div class="settings-note alert alert-info">
              <strong>Footer setup:</strong> choose visible blocks, set brand/contact details, then add links one per line as <code>Label | URL</code>. Existing label-only links still work for common pages.
            </div>
            <div class="settings-fieldset">
              <h3>Display</h3>
              <div class="settings-toggle-grid">
                <label class="settings-check"><input type="checkbox" name="footer_enabled" value="1" <?= $checked('footer_enabled', '1') ?>> Enable footer</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_brand_card" value="1" <?= $checked('footer_component_brand_card', '1') ?>> Brand block</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_platform_links" value="1" <?= $checked('footer_component_platform_links', '1') ?>> Platform links</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_resources" value="1" <?= $checked('footer_component_resources', '1') ?>> Resources links</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_company" value="1" <?= $checked('footer_component_company', '1') ?>> Company links</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_legal" value="1" <?= $checked('footer_component_legal', '1') ?>> Legal links</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_social_icons" value="1" <?= $checked('footer_component_social_icons', '1') ?>> Social links</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_newsletter" value="1" <?= $checked('footer_component_newsletter', '1') ?>> Newsletter</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_system_status" value="1" <?= $checked('footer_component_system_status', '1') ?>> System status</label>
                <label class="settings-check"><input type="checkbox" name="footer_component_copyright_bar" value="1" <?= $checked('footer_component_copyright_bar', '1') ?>> Bottom bar</label>
              </div>
              <div class="row g-3 mt-1">
                <div class="col-md-6"><label class="form-label">Layout</label><select class="form-control" name="footer_layout"><option value="2_columns" <?= $value('footer_layout', '4_columns') === '2_columns' ? 'selected' : '' ?>>2 columns</option><option value="3_columns" <?= $value('footer_layout', '4_columns') === '3_columns' ? 'selected' : '' ?>>3 columns</option><option value="4_columns" <?= $value('footer_layout', '4_columns') === '4_columns' ? 'selected' : '' ?>>4 columns</option><option value="minimal" <?= $value('footer_layout', '4_columns') === 'minimal' ? 'selected' : '' ?>>Minimal</option></select></div>
                <div class="col-md-6"><label class="form-label">Copyright template</label><input class="form-control" name="footer_copyright_template" value="<?= $value('footer_copyright_template', '© {year} {company}') ?>"></div>
              </div>
            </div>
            <div class="settings-fieldset">
              <h3>Brand & Contact</h3>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Brand name</label><input class="form-control" name="footer_brand_name" value="<?= $value('footer_brand_name', $settings['site_name'] ?? 'Instaweb') ?>"></div>
                <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" name="footer_tagline" value="<?= $value('footer_tagline', 'Build • Host • Grow') ?>"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="footer_description" rows="3"><?= $value('footer_description', 'Affordable hosting for students, churches, portfolios and businesses.') ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" name="footer_email" value="<?= $value('footer_email', 'phukal@mau.adventist.org') ?>"></div>
                <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="footer_phone" value="<?= $value('footer_phone', '+265 XXX XXX XXX') ?>"></div>
                <div class="col-md-6"><label class="form-label">Website</label><input class="form-control" name="footer_website" value="<?= $value('footer_website', 'instaweb.free.dev') ?>"></div>
                <div class="col-md-6"><label class="form-label">Address</label><input class="form-control" name="footer_address" value="<?= $value('footer_address', 'Ntcheu, Malawi') ?>"></div>
                <div class="col-md-6"><label class="form-label">Google Maps URL</label><input class="form-control" name="footer_google_maps_url" value="<?= $value('footer_google_maps_url') ?>"></div>
                <div class="col-md-6"><label class="form-label">Business hours</label><input class="form-control" name="footer_business_hours" value="<?= $value('footer_business_hours', '08:00 - 17:00') ?>"></div>
              </div>
            </div>
            <div class="settings-fieldset">
              <h3>Link Columns</h3>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Platform title</label><input class="form-control" name="footer_platform_title" value="<?= $value('footer_platform_title', 'Platform') ?>"></div>
                <div class="col-md-6"><label class="form-label">Platform links</label><textarea class="form-control" name="footer_platform_links" rows="4"><?= $value('footer_platform_links', "Features | /features\nPricing | /pricing\nTemplates | /templates\nDashboard | /dashboard") ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Resources title</label><input class="form-control" name="footer_resources_title" value="<?= $value('footer_resources_title', 'Resources') ?>"></div>
                <div class="col-md-6"><label class="form-label">Resources links</label><textarea class="form-control" name="footer_resources_links" rows="4"><?= $value('footer_resources_links', "FAQ | /faq\nContact | /contact\nLogin | /login\nRegister | /register") ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Company title</label><input class="form-control" name="footer_company_title" value="<?= $value('footer_company_title', 'Company') ?>"></div>
                <div class="col-md-6"><label class="form-label">Company links</label><textarea class="form-control" name="footer_company_links" rows="4"><?= $value('footer_company_links', "About | /about\nContact | /contact\nPricing | /pricing") ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Legal title</label><input class="form-control" name="footer_legal_title" value="<?= $value('footer_legal_title', 'Legal') ?>"></div>
                <div class="col-md-6"><label class="form-label">Legal links</label><textarea class="form-control" name="footer_legal_links" rows="4"><?= $value('footer_legal_links', "Privacy Policy | /privacy\nTerms | /terms") ?></textarea></div>
              </div>
            </div>
            <div class="settings-fieldset">
              <h3>Style & Extras</h3>
              <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Background</label><input type="color" class="form-control form-control-color" name="footer_background_color" value="<?= $value('footer_background_color', '#0F172A') ?>"></div>
                <div class="col-md-3"><label class="form-label">Text</label><input type="color" class="form-control form-control-color" name="footer_text_color" value="<?= $value('footer_text_color', '#FFFFFF') ?>"></div>
                <div class="col-md-3"><label class="form-label">Link</label><input type="color" class="form-control form-control-color" name="footer_link_color" value="<?= $value('footer_link_color', '#CBD5E1') ?>"></div>
                <div class="col-md-3"><label class="form-label">Hover</label><input type="color" class="form-control form-control-color" name="footer_link_hover_color" value="<?= $value('footer_link_hover_color', '#2563EB') ?>"></div>
                <div class="col-md-6"><label class="form-label">Newsletter title</label><input class="form-control" name="footer_newsletter_title" value="<?= $value('footer_newsletter_title', 'Stay Updated') ?>"></div>
                <div class="col-md-6"><label class="form-label">Newsletter button URL</label><input class="form-control" name="footer_newsletter_url" value="<?= $value('footer_newsletter_url', '/register') ?>"></div>
                <div class="col-md-6"><label class="form-label">Newsletter button text</label><input class="form-control" name="footer_newsletter_button_text" value="<?= $value('footer_newsletter_button_text', 'Subscribe') ?>"></div>
                <div class="col-md-6"><label class="form-label">Newsletter provider</label><input class="form-control" name="footer_newsletter_provider" value="<?= $value('footer_newsletter_provider', 'Internal') ?>"></div>
                <div class="col-12"><label class="form-label">Newsletter description</label><textarea class="form-control" name="footer_newsletter_description" rows="2"><?= $value('footer_newsletter_description', 'Receive updates and announcements.') ?></textarea></div>
                <div class="col-12"><label class="settings-check"><input type="checkbox" name="footer_newsletter_enabled" value="1" <?= $checked('footer_newsletter_enabled') ?>> Enable newsletter/signup callout</label></div>
                <div class="col-md-6"><label class="form-label">System status text</label><input class="form-control" name="footer_system_status_text" value="<?= $value('footer_system_status_text', 'All Systems Operational') ?>"></div>
                <div class="col-md-3"><label class="form-label">Status color</label><input type="color" class="form-control form-control-color" name="footer_system_status_color" value="<?= $value('footer_system_status_color', '#22C55E') ?>"></div>
                <div class="col-md-3"><label class="form-label">Status link</label><input class="form-control" name="footer_system_status_link" value="<?= $value('footer_system_status_link', '/status') ?>"></div>
              </div>
              <div class="settings-toggle-grid mt-3">
                <label class="settings-check"><input type="checkbox" name="footer_show_system_status" value="1" <?= $checked('footer_show_system_status') ?>> Show system status</label>
                <label class="settings-check"><input type="checkbox" name="footer_show_version" value="1" <?= $checked('footer_show_version') ?>> Show version</label>
                <label class="settings-check"><input type="checkbox" name="footer_show_build_number" value="1" <?= $checked('footer_show_build_number') ?>> Show build number</label>
                <label class="settings-check"><input type="checkbox" name="footer_show_copyright" value="1" <?= $checked('footer_show_copyright', '1') ?>> Show copyright</label>
                <label class="settings-check"><input type="checkbox" name="footer_made_in_malawi" value="1" <?= $checked('footer_made_in_malawi', '1') ?>> Show made in Malawi</label>
                <label class="settings-check"><input type="checkbox" name="footer_powered_by" value="1" <?= $checked('footer_powered_by', '1') ?>> Show powered by</label>
              </div>
            </div>
            <div class="settings-fieldset">
              <h3>Social Links</h3>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Facebook URL</label><input class="form-control" name="footer_social_facebook_url" value="<?= $value('footer_social_facebook_url') ?>"></div>
                <div class="col-md-6"><label class="form-label">Instagram URL</label><input class="form-control" name="footer_social_instagram_url" value="<?= $value('footer_social_instagram_url') ?>"></div>
                <div class="col-md-6"><label class="form-label">LinkedIn URL</label><input class="form-control" name="footer_social_linkedin_url" value="<?= $value('footer_social_linkedin_url') ?>"></div>
                <div class="col-md-6"><label class="form-label">GitHub URL</label><input class="form-control" name="footer_social_github_url" value="<?= $value('footer_social_github_url') ?>"></div>
                <div class="col-md-6"><label class="form-label">YouTube URL</label><input class="form-control" name="footer_social_youtube_url" value="<?= $value('footer_social_youtube_url') ?>"></div>
                <div class="col-md-6"><label class="form-label">TikTok URL</label><input class="form-control" name="footer_social_tiktok_url" value="<?= $value('footer_social_tiktok_url') ?>"></div>
                <div class="col-md-6"><label class="form-label">WhatsApp URL</label><input class="form-control" name="footer_social_whatsapp_url" value="<?= $value('footer_social_whatsapp_url') ?>"></div>
              </div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="legal">
            <h2>Legal Policies</h2>
            <div class="settings-note alert alert-info">
              <strong>Manage legal content</strong> from the app settings page. Use the links below to edit Privacy, Terms, and other policies in the legal manager.
            </div>
            <div class="row g-3">
              <div class="col-12">
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($policies)): ?>
                        <tr><td colspan="3">No legal policies found.</td></tr>
                      <?php else: ?>
                        <?php foreach ($policies as $policy): ?>
                          <tr>
                            <td><?= htmlspecialchars($policy['policy_type'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($policy['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                              <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/legal/edit?id=' . $policy['id'], ENT_QUOTES, 'UTF-8') ?>">Edit</a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="col-12">
                <a class="btn btn-primary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/legal/edit', ENT_QUOTES, 'UTF-8') ?>">Create new policy</a>
              </div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="pricing">
            <h2>Pricing</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Free Plan Name</label><input class="form-control" name="free_plan_name" value="<?= $value('free_plan_name', 'Free Plan') ?>"></div>
              <div class="col-md-6"><label class="form-label">Free Plan Price</label><input class="form-control" name="free_plan_price" value="<?= $value('free_plan_price', 'MWK 0') ?>"></div>
              <div class="col-md-4"><label class="form-label">Free Website Limit</label><input class="form-control" name="free_plan_website_limit" value="<?= $value('free_plan_website_limit', '1') ?>"></div>
              <div class="col-md-4"><label class="form-label">Free Storage MB</label><input class="form-control" name="free_storage_limit_mb" value="<?= $value('free_storage_limit_mb', '100') ?>"></div>
              <div class="col-md-4"><label class="form-label">Free Hosting Days</label><input class="form-control" name="free_plan_hosting_days" value="<?= $value('free_plan_hosting_days', '30') ?>"></div>
              <div class="col-12"><label class="form-label">Free Plan Benefits</label><textarea class="form-control" name="free_plan_benefits" rows="4"><?= $value('free_plan_benefits', "1 website\n100MB storage\n30 days hosting\nInstaweb subdomain") ?></textarea></div>
              <div class="col-md-6"><label class="form-label">Premium Plan Name</label><input class="form-control" name="premium_plan_name" value="<?= $value('premium_plan_name', 'Premium Plan') ?>"></div>
              <div class="col-md-6"><label class="form-label">Premium Plan Price</label><input class="form-control" name="premium_plan_price" value="<?= $value('premium_plan_price', 'MWK 5,000/mo') ?>"></div>
              <div class="col-12"><label class="form-label">Premium Plan Benefits</label><textarea class="form-control" name="premium_plan_benefits" rows="4"><?= $value('premium_plan_benefits', "Unlimited websites\nCustom domains\nNo expiry\nAnalytics and priority support") ?></textarea></div>
              <div class="col-md-6"><label class="form-label">Premium PayChangu Amount</label><input class="form-control" name="premium_plan_amount" value="<?= $value('premium_plan_amount', '5000') ?>"></div>
              <div class="col-md-6"><label class="form-label">PayChangu Currency</label>
                <select class="form-control" name="paychangu_currency">
                  <option value="MWK" <?= $value('paychangu_currency', 'MWK') === 'MWK' ? 'selected' : '' ?>>MWK</option>
                  <option value="USD" <?= $value('paychangu_currency') === 'USD' ? 'selected' : '' ?>>USD</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">PayChangu Public Key</label><input class="form-control" name="paychangu_public_key" value="<?= $value('paychangu_public_key') ?>"></div>
              <div class="col-md-6"><label class="form-label">PayChangu Secret Key</label><input class="form-control" type="password" name="paychangu_secret_key" value="<?= $value('paychangu_secret_key') ?>" placeholder="Enter secret key"></div>
              <div class="col-md-6">
                <label class="form-label" for="payment_checkout_mode">PayChangu Checkout Mode</label>
                <select class="form-control" id="payment_checkout_mode" name="payment_checkout_mode">
                  <option value="standard" <?= $value('payment_checkout_mode', 'standard') === 'standard' ? 'selected' : '' ?>>Standard Checkout (hosted page)</option>
                  <option value="inline" <?= $value('payment_checkout_mode') === 'inline' ? 'selected' : '' ?>>Inline Checkout (popup)</option>
                  <option value="dropin" <?= $value('payment_checkout_mode') === 'dropin' ? 'selected' : '' ?>>Drop-in Checkout (embedded form)</option>
                </select>
                <small class="text-muted d-block mt-2">Standard is the fallback. Inline and Drop-in require a PayChangu public key. Test each mode before enabling it for customers.</small>
              </div>
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="free_plan_enabled" value="1" <?= $checked('free_plan_enabled', '1') ?>> Free plan available</label></div>
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="premium_plan_enabled" value="1" <?= $checked('premium_plan_enabled', '1') ?>> Premium plan available</label></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="smtp">
            <h2>Email / SMTP</h2>
            <div class="row g-3">
              <div class="col-12"><label class="settings-check"><input type="checkbox" name="smtp_enabled" value="1" <?= $checked('smtp_enabled') ?>> Enable SMTP email sending</label></div>
              <div class="col-md-6"><label class="form-label">Mail Host</label><input class="form-control" name="mail_host" value="<?= $value('mail_host', 'smtp.gmail.com') ?>"></div>
              <div class="col-md-6"><label class="form-label">Mail Port</label><input class="form-control" name="mail_port" value="<?= $value('mail_port', '587') ?>"></div>
              <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" name="mail_username" value="<?= $value('mail_username') ?>"></div>
              <div class="col-md-6"><label class="form-label">Password</label><input class="form-control" type="password" name="mail_password" value="<?= $value('mail_password') ?>" placeholder="••••••••••••••••"></div>
              <div class="col-md-6"><label class="form-label">Encryption</label>
                <select class="form-control" name="mail_encryption">
                  <option value="tls" <?= $value('mail_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                  <option value="ssl" <?= $value('mail_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                  <option value="" <?= $value('mail_encryption') === '' ? 'selected' : '' ?>>None</option>
                </select>
              </div>
              <div class="col-md-6"><label class="form-label">From Address</label><input class="form-control" name="mail_from_address" value="<?= $value('mail_from_address') ?>"></div>
              <div class="col-md-6"><label class="form-label">From Name</label><input class="form-control" name="mail_from_name" value="<?= $value('mail_from_name', 'Instaweb') ?>"></div>
              <div class="col-md-6"><label class="form-label">Admin Login Notification Address</label><input class="form-control" name="admin_login_notification_email" value="<?= $value('admin_login_notification_email') ?>"></div>
              <div class="col-12"><label class="form-label">Send test email to</label><input class="form-control" type="email" name="send_test_email_to" placeholder="Enter an address to receive a test message using the settings above."></div>
              <div class="col-12"><button class="btn btn-outline-primary" type="submit" formaction="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings/test-email', ENT_QUOTES, 'UTF-8') ?>">Send test email</button></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="maintenance">
            <h2>Maintenance</h2>
            <div class="row g-3">
              <div class="col-12"><label class="settings-check"><input type="checkbox" name="maintenance_mode" value="1" <?= $checked('maintenance_mode') ?>> Put public site into maintenance mode</label></div>
              <div class="col-md-6"><label class="form-label">Maintenance page title</label><input class="form-control" name="maintenance_page_title" value="<?= $value('maintenance_page_title', 'We’ll be back soon') ?>"></div>
              <div class="col-md-6"><label class="form-label">Status label</label><input class="form-control" name="maintenance_status_label" value="<?= $value('maintenance_status_label', 'Maintenance in progress') ?>"></div>
              <div class="col-12"><label class="form-label">Maintenance message</label><textarea class="form-control" name="maintenance_message" rows="4"><?= $value('maintenance_message', 'Our website is currently undergoing scheduled maintenance. We appreciate your patience and expect to be back online shortly.') ?></textarea></div>
              <div class="col-md-6"><label class="form-label">Expected return date/time</label><input class="form-control" type="datetime-local" name="maintenance_return_at" value="<?= $value('maintenance_return_at') ?>"></div>
              <div class="col-md-6"><label class="form-label">Maintenance bypass token</label><input class="form-control" name="maintenance_bypass_token" value="<?= $value('maintenance_bypass_token') ?>" placeholder="Enter a secret bypass token"></div>
              <div class="col-12 d-flex gap-2 align-items-center">
                <button class="btn btn-outline-primary" type="submit">Save settings</button>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings/maintenance-preview', ENT_QUOTES, 'UTF-8') ?>" target="_blank">Preview maintenance page</a>
              </div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="security">
            <h2>Security</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="recaptcha_enabled" value="1" <?= $checked('recaptcha_enabled') ?>> Google reCAPTCHA</label></div>
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="turnstile_enabled" value="1" <?= $checked('turnstile_enabled') ?>> Cloudflare Turnstile</label></div>
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="two_factor_enabled" value="1" <?= $checked('two_factor_enabled') ?>> Two-Factor Authentication</label></div>
              <div class="col-md-6"><label class="form-label">Password Minimum Length</label><input class="form-control" name="password_min_length" value="<?= $value('password_min_length', '8') ?>"></div>
              <div class="col-md-6"><label class="form-label">Login Attempt Limit</label><input class="form-control" name="login_attempt_limit" value="<?= $value('login_attempt_limit', '5') ?>"></div>
              <div class="col-md-6"><label class="form-label">Session Lifetime Minutes</label><input class="form-control" name="session_lifetime_minutes" value="<?= $value('session_lifetime_minutes', '120') ?>"></div>
              <div class="col-md-6"><label class="form-label">reCAPTCHA Site Key</label><input class="form-control" name="recaptcha_site_key" value="<?= $value('recaptcha_site_key') ?>"></div>
              <div class="col-md-6"><label class="form-label">Turnstile Site Key</label><input class="form-control" name="turnstile_site_key" value="<?= $value('turnstile_site_key') ?>"></div>
              <div class="col-md-6"><label class="form-label">IP Blacklist</label><textarea class="form-control" name="ip_blacklist" rows="4"><?= $value('ip_blacklist') ?></textarea></div>
              <div class="col-md-6"><label class="form-label">IP Whitelist</label><textarea class="form-control" name="ip_whitelist" rows="4"><?= $value('ip_whitelist') ?></textarea></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="uploads">
            <h2>Uploads</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Max Upload Size MB</label><input class="form-control" name="max_upload_size_mb" value="<?= $value('max_upload_size_mb', '10') ?>"></div>
              <div class="col-md-6"><label class="form-label">Allowed File Extensions</label><input class="form-control" name="allowed_file_extensions" value="<?= $value('allowed_file_extensions') ?>"></div>
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="zip_upload_enabled" value="1" <?= $checked('zip_upload_enabled') ?>> ZIP upload support</label></div>
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="image_compression_enabled" value="1" <?= $checked('image_compression_enabled') ?>> Image compression</label></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="storage">
            <h2>Storage</h2>
            <div class="settings-note alert alert-info">
              <strong>How to use storage settings:</strong>
              <ul>
                <li><strong>Free Storage Limit MB</strong> sets the maximum draft upload storage available to Free plan users.</li>
                <li><strong>Premium Storage Limit MB</strong> sets the maximum draft upload storage available to Premium users. Use <strong>0</strong> for unlimited Premium storage.</li>
                <li>These limits are enforced across uploads, file creation, and ZIP imports in the dashboard.</li>
                <li>Save settings after changing values, then verify limits by testing file uploads as a Free or Premium user.</li>
              </ul>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Free Storage Limit MB</label>
                <input class="form-control" name="free_storage_limit_mb" value="<?= $value('free_storage_limit_mb', '100') ?>">
                <small class="form-text text-muted">Maximum draft project storage for Free plan users.</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Premium Storage Limit MB</label>
                <input class="form-control" name="premium_storage_limit_mb" value="<?= $value('premium_storage_limit_mb', '0') ?>">
                <small class="form-text text-muted">Enter 0 for unlimited Premium storage, or a positive MB limit to cap Premium usage.</small>
              </div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="notifications">
            <h2>Notifications</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="email_notifications_enabled" value="1" <?= $checked('email_notifications_enabled') ?>> Email notifications</label></div>
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="admin_login_alerts_enabled" value="1" <?= $checked('admin_login_alerts_enabled') ?>> Admin login alerts</label></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="backups">
            <h2>Backups</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="automatic_backups_enabled" value="1" <?= $checked('automatic_backups_enabled') ?>> Automatic backups</label></div>
              <div class="col-md-6"><label class="form-label">Backup Frequency</label><input class="form-control" name="backup_frequency" value="<?= $value('backup_frequency', 'daily') ?>"></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="api">
            <h2>API</h2>
            <div class="row g-3">
              <div class="col-md-6"><label class="settings-check"><input type="checkbox" name="api_enabled" value="1" <?= $checked('api_enabled') ?>> Enable API access</label></div>
              <div class="col-md-6"><label class="form-label">API Rate Limit</label><input class="form-control" name="api_rate_limit" value="<?= $value('api_rate_limit', '60') ?>"></div>
            </div>
          </section>

          <section class="settings-pro-panel" data-settings-panel="advanced">
            <h2>Advanced</h2>
            <div class="row g-3">
              <div class="col-12"><label class="form-label">Custom Head Code</label><textarea class="form-control" name="custom_head_code" rows="5"><?= $value('custom_head_code') ?></textarea></div>
            </div>
          </section>
        </div>
      </div>
    </form>
    <section class="admin-pro-card mt-4">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
          <p class="admin-pro-kicker mb-1">System maintenance</p>
          <h2 class="h4 mb-2">Application Update</h2>
          <p class="text-muted mb-0">Upload a ZIP package to update the application. A backup is created before the update is applied.</p>
        </div>
        <i data-lucide="upload-cloud" aria-hidden="true"></i>
      </div>
      <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/app-update.php', ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="mt-3">
        <?= \App\Core\Csrf::field() ?>
        <div class="row g-3 align-items-end">
          <div class="col-md-8">
            <label class="form-label" for="settings_update_package">ZIP package</label>
            <input class="form-control" id="settings_update_package" name="update_package" type="file" accept=".zip,application/zip" required>
            <small class="form-text text-muted">Maximum size: 200 MB.</small>
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary w-100" type="submit"><i data-lucide="upload-cloud"></i> Upload and apply</button>
          </div>
        </div>
      </form>
    </section>
    <footer class="admin-pro-footer">
      <span>Settings saved <?= htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8') ?></span>
      <span><a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin', ENT_QUOTES, 'UTF-8') ?>">Return to dashboard</a></span>
    </footer>
  </section>
</div>