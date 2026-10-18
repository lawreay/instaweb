<?php $baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/'); ?>
<section class="client-dashboard iw-client-page iw-website-form">
<div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">New Project</p>
        <h1>Create Website</h1>
        <p>Start with a safe draft folder and a starter `index.html` file.</p>
      </div>
    </header>

    <section class="client-card">
      <?php if (!$canCreate): ?>
        <div class="client-empty">
          <i data-lucide="lock"></i>
          <strong>Free plan limit reached</strong>
          <p>You already have one active website. Upgrade to Premium to create unlimited projects.</p>
          <a class="btn btn-primary" href="<?= htmlspecialchars($baseUrl . '/pricing', ENT_QUOTES, 'UTF-8') ?>">View Premium</a>
        </div>
      <?php else: ?>
        <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>" class="row g-4">
          <?= \App\Core\Csrf::field() ?>
          <div class="col-12 col-lg-7">
            <label class="form-label" for="website_name">Website name</label>
            <input class="form-control" id="website_name" name="website_name" maxlength="120" required value="<?= htmlspecialchars($old['website_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Portfolio">
          </div>
          <div class="col-12 col-lg-5">
            <label class="form-label" for="slug">Project slug</label>
            <input class="form-control" id="slug" name="slug" maxlength="150" value="<?= htmlspecialchars($old['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="portfolio">
            <small class="text-secondary">Lowercase letters, numbers, and hyphens are generated automatically.</small>
          </div>
          <div class="col-12">
            <label class="form-label" for="template">Starter template</label>
            <select class="form-select" id="template" name="template" required>
              <?php foreach ($templates as $key => $label): ?>
                <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= (($old['template'] ?? 'blank') === $key) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="text-secondary d-block mt-2">WordPress projects include the preserved WordPress package and require PHP/MySQL support on the server.</small>
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i data-lucide="folder-plus"></i> Create Website</button>
            <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
          </div>
        </form>
      <?php endif; ?>
    </section>
  </div>
</section>
