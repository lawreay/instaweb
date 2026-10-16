<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    return $bytes > 0 ? number_format($bytes / 1024, 2) . ' KB' : '0 KB';
};
?>
<section class="client-dashboard">
  <aside class="client-sidebar">
    <div class="client-profile">
      <div class="client-avatar"><i data-lucide="folder"></i></div>
      <strong>File Manager</strong>
      <span><?= htmlspecialchars($website['website_name'] ?: 'Website', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <nav class="client-nav">
      <a href="<?= htmlspecialchars($baseUrl . '/dashboard', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="layout-dashboard"></i> Dashboard</a>
      <a class="active" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="globe-2"></i> Websites</a>
      <a href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="arrow-left"></i> Project</a>
    </nav>
  </aside>

  <div class="client-main">
    <header class="client-header">
      <div>
        <p class="auth-eyebrow">Draft Storage</p>
        <h1><?= htmlspecialchars($website['website_name'] ?: 'Website Files', ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Upload, organise, edit, and export this site's files. Your live website remains separate from draft storage.</p>
      </div>
      <div class="client-header-actions">
        <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i> Manage site</a>
        <?php if (($website['status'] ?? '') === 'published' && !empty($website['public_url'])): ?>
          <a class="btn btn-primary" href="<?= htmlspecialchars($website['public_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i> Visit live site</a>
        <?php endif; ?>
      </div>
    </header>

    <div class="client-grid">
      <section class="client-card client-wide">
        <div class="client-card-head">
          <h2>Files</h2>
          <span><?= count($files) ?> items</span>
        </div>

        <?php if ($files === []): ?>
          <div class="client-empty">
            <i data-lucide="folder-open"></i>
            <strong>No files yet</strong>
            <p>Upload a static file or create `index.html` to start.</p>
          </div>
        <?php else: ?>
          <div class="client-site-list">
            <?php foreach ($files as $file): ?>
              <article class="client-site">
                <div class="site-icon"><i data-lucide="<?= $file['type'] === 'folder' ? 'folder' : 'file-code-2' ?>"></i></div>
                <div>
                  <strong><?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <span><?= htmlspecialchars($file['type'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($formatBytes((int) $file['size']), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($file['modified_at'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                  <?php if ($file['type'] === 'file'): ?>
                    <?php if (in_array($file['extension'], ['html', 'htm', 'css', 'js', 'json', 'txt', 'md'], true)): ?>
                      <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/editor?id=' . (int) $website['id'] . '&path=' . rawurlencode($file['path']), ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="pencil"></i></a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/download?id=' . (int) $website['id'] . '&path=' . rawurlencode($file['path']), ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="download"></i></a>
                  <?php endif; ?>
                  <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/delete', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Delete this draft item?');">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
                    <input type="hidden" name="path" value="<?= htmlspecialchars($file['path'], ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit"><i data-lucide="trash-2"></i></button>
                  </form>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="client-card">
        <div class="client-card-head">
          <h2>Upload</h2>
        </div>
        <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/upload', ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="d-grid gap-3">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
          <div>
            <label class="form-label" for="folder">Folder</label>
            <input class="form-control" id="folder" name="folder" placeholder="images">
          </div>
          <div>
            <label class="form-label" for="project_file">File</label>
            <input class="form-control" id="project_file" name="project_file" type="file" required>
          </div>
          <button class="btn btn-primary" type="submit"><i data-lucide="upload"></i> Upload</button>
        </form>
      </section>

      <section class="client-card">
        <div class="client-card-head">
          <h2>Import / Export</h2>
        </div>
        <div class="d-grid gap-3">
          <a class="btn btn-outline-primary" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/export?id=' . (int) $website['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="archive"></i> Export ZIP</a>
          <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/import', ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="d-grid gap-3">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
            <div>
              <label class="form-label" for="zip_file">Import ZIP</label>
              <input class="form-control" id="zip_file" name="zip_file" type="file" accept=".zip" required>
            </div>
            <button class="btn btn-outline-primary" type="submit"><i data-lucide="file-archive"></i> Import ZIP</button>
          </form>
        </div>
      </section>

      <section class="client-card">
        <div class="client-card-head">
          <h2>New File</h2>
        </div>
        <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/create-file', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
          <div>
            <label class="form-label" for="file_path">File path</label>
            <input class="form-control" id="file_path" name="file_path" placeholder="about.html" required>
          </div>
          <button class="btn btn-outline-primary" type="submit"><i data-lucide="file-plus-2"></i> Create File</button>
        </form>
      </section>

      <section class="client-card">
        <div class="client-card-head">
          <h2>Move / Rename</h2>
        </div>
        <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/move', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
          <div>
            <label class="form-label" for="from_path">Current path</label>
            <input class="form-control" id="from_path" name="from_path" placeholder="about.html" required>
          </div>
          <div>
            <label class="form-label" for="to_path">New path</label>
            <input class="form-control" id="to_path" name="to_path" placeholder="pages/about.html" required>
          </div>
          <button class="btn btn-outline-primary" type="submit"><i data-lucide="move-right"></i> Move Item</button>
        </form>
      </section>

      <section class="client-card">
        <div class="client-card-head">
          <h2>New Folder</h2>
        </div>
        <form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files/create-folder', ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-3">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="website_id" value="<?= (int) $website['id'] ?>">
          <div>
            <label class="form-label" for="folder_path">Folder path</label>
            <input class="form-control" id="folder_path" name="folder_path" placeholder="assets/images" required>
          </div>
          <button class="btn btn-outline-primary" type="submit"><i data-lucide="folder-plus"></i> Create Folder</button>
        </form>
      </section>
    </div>
  </div>
</section>
