<?php
$base = rtrim((string) ($app['base_url'] ?? ''), '/');
$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="admin-pro-shell">
  <?php $active = 'feedback'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>
  <section class="admin-pro-main">
    <header class="admin-pro-topbar">
      <div class="admin-pro-topbar-left">
        <p class="admin-pro-kicker">Product feedback</p>
        <h1>Problems & Feature Requests</h1>
        <p class="text-secondary mb-0">Reports submitted from the floating help button.</p>
      </div>
      <div class="admin-pro-topbar-right"><span class="status-chip pending"><?= (int) ($newCount ?? 0) ?> new</span></div>
    </header>

    <div class="admin-pro-card">
      <div class="admin-pro-card-head"><h2>Feedback inbox</h2><span><?= count($submissions ?? []) ?> shown</span></div>
      <div class="table-responsive">
        <table class="admin-pro-table">
          <thead><tr><th>Type / Subject</th><th>Submitted by</th><th>Description</th><th>Page</th><th>Received</th><th>Manage</th></tr></thead>
          <tbody>
          <?php foreach (($submissions ?? []) as $item): ?>
            <tr>
              <td><strong><?= $esc($item['type'] === 'feature' ? 'Feature request' : 'Problem report') ?></strong><small>#<?= (int) $item['id'] ?> · <?= $esc($item['subject']) ?></small><small class="status-chip <?= $esc($item['status']) ?>"><?= $esc(str_replace('_', ' ', $item['status'])) ?></small></td>
              <td><?= $esc($item['submitter_name']) ?><small><?= $esc($item['submitter_email'] ?: 'No contact email') ?></small><small><?= $esc($item['username'] ?: 'Guest account') ?></small></td>
              <td style="min-width:240px;max-width:420px;white-space:pre-wrap;overflow-wrap:anywhere"><?= $esc($item['message']) ?></td>
              <td style="max-width:180px;overflow-wrap:anywhere"><?= $esc($item['page_url'] ?: 'Not provided') ?></td>
              <td><?= $esc($item['created_at']) ?></td>
              <td style="min-width:220px">
                <form method="post" action="<?= $esc($base . '/admin/feedback/status') ?>" class="d-grid gap-2">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                  <select name="status" class="form-select form-select-sm" aria-label="Feedback status">
                    <?php foreach (['new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label): ?>
                      <option value="<?= $esc($value) ?>" <?= $item['status'] === $value ? 'selected' : '' ?>><?= $esc($label) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <textarea name="admin_note" class="form-control form-control-sm" rows="2" maxlength="2000" placeholder="Internal note (optional)"><?= $esc($item['admin_note'] ?? '') ?></textarea>
                  <button class="btn btn-sm btn-dark" type="submit">Save update</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($submissions)): ?><tr><td colspan="6" class="text-center py-5">No feedback has been submitted yet.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>
