<?php $baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/'); ?>
<section class="client-dashboard iw-client-page iw-website-form">
<div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Project Settings</p>
        <h1>Edit Website</h1>
        <p>The slug stays fixed so storage paths and future public URLs remain stable.</p>
      </div>
    </header>

    <section class="client-card">
      <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/update', ENT_QUOTES, 'UTF-8') ?>" class="row g-4">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
        <div class="col-12 col-lg-8">
          <label class="form-label" for="website_name">Website name</label>
          <input class="form-control" id="website_name" name="website_name" maxlength="120" required value="<?= htmlspecialchars($website['website_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label">Project slug</label>
          <input class="form-control" value="<?= htmlspecialchars($website['slug'], ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save Changes</button>
          <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
        </div>
      </form>
    </section>
  </div>
</section>
