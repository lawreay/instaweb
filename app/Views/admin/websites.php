<div class="admin-pro-shell">
  <?php $active = 'websites'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

  <section class="admin-pro-main">
    <header class="admin-pro-topbar">
      <div class="admin-pro-topbar-left">
        <p class="admin-pro-kicker">Hosting management</p>
        <h1>Websites</h1>
      </div>
      <div class="admin-pro-topbar-right admin-pro-actions">
        <span class="text-secondary">Total sites: <?= count($websites) ?></span>
      </div>
    </header>

    <div class="admin-pro-card">
      <div class="admin-pro-card-head">
        <h2>Hosted Websites</h2>
        <span><?= count($websites) ?> records</span>
      </div>
      <div class="table-responsive">
        <table class="admin-pro-table">
          <thead>
            <tr>
              <th>Website</th>
              <th>Owner</th>
              <th>Status</th>
              <th>Storage</th>
              <th>Bandwidth</th>
              <th>Expires</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($websites as $website): ?>
              <?php $isSystemTemplate = !empty($website['is_system_template']); ?>
              <tr>
                <td><strong><?= htmlspecialchars($website['website_name'] ?: 'Untitled website', ENT_QUOTES, 'UTF-8') ?></strong><?php if ($isSystemTemplate): ?><span class="badge text-bg-primary ms-1">Default template</span><?php endif; ?><small><?= htmlspecialchars($website['custom_domain'] ?: ($website['subdomain'] ?: $website['slug']), ENT_QUOTES, 'UTF-8') ?></small></td>
                <td><?= htmlspecialchars($website['username'], ENT_QUOTES, 'UTF-8') ?><small><?= htmlspecialchars($website['email'], ENT_QUOTES, 'UTF-8') ?></small></td>
                <td><span class="status-chip <?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= number_format(((int) $website['storage_used']) / 1048576, 2) ?> MB</td>
                <td><?= number_format(((int) $website['bandwidth_used']) / 1048576, 2) ?> MB</td>
                <td><?= htmlspecialchars($website['expires_at'] ?? 'No expiry', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <div class="d-flex gap-2 flex-wrap">
                    <?php if ($isSystemTemplate): ?>
                      <span class="text-secondary small">Protected system portfolio</span>
                    <?php elseif ($website['status'] === 'suspended'): ?>
                      <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites/unsuspend', ENT_QUOTES, 'UTF-8') ?>">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                        <button class="btn btn-sm btn-outline-primary" type="submit">Unsuspend</button>
                      </form>
                    <?php else: ?>
                      <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites/suspend', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Suspend this website and remove its public files?');">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Suspend</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites/delete', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Permanently delete this website, draft files, and public files?');">
                      <?= \App\Core\Csrf::field() ?>
                      <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                      <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($websites === []): ?>
              <tr><td colspan="7">No websites found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <footer class="admin-pro-footer">
      <span>Website list updated <?= htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8') ?></span>
      <span><a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>">Hosting settings</a></span>
    </footer>
  </section>
</div>
