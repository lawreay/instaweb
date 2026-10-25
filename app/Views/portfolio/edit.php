<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$websiteId = (int) $website['id'];
$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$socialLinks = json_decode((string) ($portfolio['social_links'] ?? '{}'), true);
$socialLinks = is_array($socialLinks) ? $socialLinks : [];
$avatarPath = trim((string) ($portfolio['avatar_path'] ?? ''));
$avatarUrl = $avatarPath !== '' && !str_contains($avatarPath, '..')
    && preg_match('#^uploads/[A-Za-z0-9_./-]+$#', $avatarPath)
    ? $baseUrl . '/' . ltrim($avatarPath, '/')
    : '';
?>
<section class="client-dashboard iw-client-page iw-website-form">
  <div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Portfolio Studio</p>
        <h1>Edit Portfolio</h1>
        <p>Update the content shown on your portfolio without editing HTML or CSS.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/show?id=' . $websiteId) ?>">Back to Website</a>
        <a class="btn btn-primary" href="<?= $escape($baseUrl . '/dashboard/websites/portfolio/advanced?id=' . $websiteId) ?>"><i data-lucide="sliders-horizontal"></i> Advanced Editor</a>
        <a class="btn btn-outline-primary" href="<?= $escape($baseUrl . '/dashboard/websites/preview?id=' . $websiteId) ?>"><i data-lucide="eye"></i> Preview Draft</a>
      </div>
    </header>

    <section class="client-card mb-4">
      <div class="client-card-head">
        <h2>Profile and design</h2>
        <span><?= $escape($website['website_name'] ?? 'Portfolio') ?></span>
      </div>
      <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/save') ?>" class="row g-4">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $websiteId ?>">
        <div class="col-12 col-lg-6">
          <label class="form-label" for="display_name">Display name</label>
          <input class="form-control" id="display_name" name="display_name" maxlength="120" required value="<?= $escape($portfolio['display_name'] ?? '') ?>">
        </div>
        <div class="col-12 col-lg-6">
          <label class="form-label" for="headline">Headline / role</label>
          <input class="form-control" id="headline" name="headline" maxlength="180" value="<?= $escape($portfolio['headline'] ?? '') ?>" placeholder="Designer, developer, photographer...">
        </div>
        <div class="col-12">
          <label class="form-label" for="bio">About you</label>
          <textarea class="form-control" id="bio" name="bio" rows="5" maxlength="10000" placeholder="Describe your work, skills, and experience."><?= $escape($portfolio['bio'] ?? '') ?></textarea>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="email">Public contact email</label>
          <input class="form-control" id="email" name="email" type="email" maxlength="190" value="<?= $escape($portfolio['email'] ?? '') ?>" placeholder="you@example.com">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="location">Location</label>
          <input class="form-control" id="location" name="location" maxlength="120" value="<?= $escape($portfolio['location'] ?? '') ?>" placeholder="City, country">
        </div>
        <div class="col-12">
          <div class="border rounded-3 p-3">
            <h3 class="h5 mb-1">Social links</h3>
            <p class="text-secondary small">Add full public URLs. Empty fields are omitted from your portfolio.</p>
            <div class="row g-3">
              <?php foreach (['github' => 'GitHub', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'website' => 'Personal website'] as $platform => $label): ?>
                <div class="col-12 col-md-6">
                  <label class="form-label" for="social_<?= $escape($platform) ?>"><?= $escape($label) ?></label>
                  <input class="form-control" id="social_<?= $escape($platform) ?>" name="social_<?= $escape($platform) ?>" type="url" maxlength="500" inputmode="url" placeholder="https://..." value="<?= $escape($socialLinks[$platform] ?? '') ?>">
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-6">
          <label class="form-label" for="template_key">Portfolio design</label>
          <select class="form-select" id="template_key" name="template_key" required>
            <?php foreach ($templates as $key => $label): ?>
              <option value="<?= $escape($key) ?>" <?= (($portfolio['template_key'] ?? 'classic') === $key) ? 'selected' : '' ?>><?= $escape($label) ?></option>
            <?php endforeach; ?>
          </select>
          <small class="text-secondary d-block mt-2">Your content stays in the database when you switch designs.</small>
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit"><i data-lucide="save"></i> Save Profile</button>
        </div>
      </form>
    </section>

    <section class="client-card mb-4">
      <div class="client-card-head">
        <h2>Profile image</h2>
        <span>JPEG, PNG, or WebP · max 3 MB</span>
      </div>
      <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
        <?php if ($avatarUrl !== ''): ?>
          <img src="<?= $escape($avatarUrl) ?>" alt="Current portfolio profile image" width="96" height="120" style="object-fit:cover;border-radius:.75rem;border:1px solid var(--bs-border-color)">
        <?php else: ?>
          <div class="d-grid place-items-center rounded-3 border text-secondary" style="width:96px;height:120px">No image</div>
        <?php endif; ?>
        <p class="text-secondary mb-0">This image appears in your public portfolio hero. Upload a new image to replace it.</p>
      </div>
      <form method="POST" enctype="multipart/form-data" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/avatar') ?>" class="row g-3 align-items-end">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $websiteId ?>">
        <div class="col-12 col-md-8">
          <label class="form-label" for="avatar">Choose profile image</label>
          <input class="form-control" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required>
        </div>
        <div class="col-12 col-md-4">
          <button class="btn btn-primary" type="submit"><i data-lucide="image-up"></i> Upload image</button>
        </div>
      </form>
    </section>

    <section class="client-card">
      <div class="client-card-head">
        <h2>Projects</h2>
        <span><?= count($projects) ?> saved</span>
      </div>

      <?php if ($projects === []): ?>
        <div class="client-empty">
          <i data-lucide="briefcase-business"></i>
          <strong>No projects added yet</strong>
          <p>Add projects to show visitors examples of your work.</p>
        </div>
      <?php else: ?>
        <div class="client-mini-list mb-4">
          <?php foreach ($projects as $project): ?>
            <div class="portfolio-project-row">
              <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/projects/save') ?>" class="row g-2 flex-grow-1">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="id" value="<?= $websiteId ?>">
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <div class="col-12 col-lg-6">
                  <label class="form-label small" for="project_title_<?= (int) $project['id'] ?>">Project title</label>
                  <input class="form-control" id="project_title_<?= (int) $project['id'] ?>" name="project_title" maxlength="150" required value="<?= $escape($project['title'] ?? '') ?>">
                </div>
                <div class="col-12 col-lg-6">
                  <label class="form-label small" for="project_url_<?= (int) $project['id'] ?>">Project link</label>
                  <input class="form-control" id="project_url_<?= (int) $project['id'] ?>" type="url" name="project_url" maxlength="500" value="<?= $escape($project['project_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="col-12">
                  <label class="form-label small" for="project_summary_<?= (int) $project['id'] ?>">Description</label>
                  <textarea class="form-control" id="project_summary_<?= (int) $project['id'] ?>" name="project_summary" rows="2" maxlength="5000"><?= $escape($project['summary'] ?? '') ?></textarea>
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label small" for="project_order_<?= (int) $project['id'] ?>">Display order</label>
                  <input class="form-control" id="project_order_<?= (int) $project['id'] ?>" type="number" name="sort_order" min="0" max="9999" value="<?= (int) ($project['sort_order'] ?? 0) ?>">
                </div>
                <div class="col-6 d-flex align-items-end">
                  <button type="submit" class="btn btn-sm btn-primary"><i data-lucide="save"></i> Save project</button>
                </div>
              </form>
              <form class="project-delete-form" method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/projects/delete') ?>" onsubmit="return confirm('Remove this project from your portfolio?');">
                <?= \App\Core\Csrf::field() ?>
                <input type="hidden" name="id" value="<?= $websiteId ?>">
                <input type="hidden" name="project_id" value="<?= (int) $project['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i data-lucide="trash-2"></i> Remove</button>
              </form>
            </div>ch; ?>
        </div>
      <?php endif; ?>

      <h3 class="h5 mt-4">Add a project</h3>
      <form method="POST" action="<?= $escape($baseUrl . '/dashboard/websites/portfolio/projects/add') ?>" class="row g-3">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $websiteId ?>">
        <div class="col-12 col-lg-6">
          <label class="form-label" for="project_title">Project title</label>
          <input class="form-control" id="project_title" name="project_title" maxlength="150" required>
        </div>
        <div class="col-12 col-lg-6">
          <label class="form-label" for="project_url">Project link (optional)</label>
          <input class="form-control" id="project_url" name="project_url" type="url" maxlength="500" placeholder="https://example.com">
        </div>
        <div class="col-12">
          <label class="form-label" for="project_summary">Short description</label>
          <textarea class="form-control" id="project_summary" name="project_summary" rows="3" maxlength="5000"></textarea>
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit"><i data-lucide="plus"></i> Add Project</button>
        </div>
      </form>
    </section>
  </div>
</section>
<style>
.portfolio-project-row{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:1rem 0;border-bottom:1px solid var(--bs-border-color,#e5e7eb)}
.portfolio-project-row:last-child{border-bottom:0}
.portfolio-project-row>div{min-width:0;overflow-wrap:anywhere}
.portfolio-project-row>form:not(.project-delete-form){flex:1 1 100%;min-width:0}
.portfolio-project-row .project-delete-form{flex:0 0 auto}
@media(max-width:575px){.portfolio-project-row{flex-direction:column}.portfolio-project-row form{align-self:stretch}.portfolio-project-row .project-delete-form{align-self:flex-start}}
</style>
