<?php
$active = 'dashboard';
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="admin-pro-shell admin-dashboard-refresh">
  <?php include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

  <section class="admin-pro-main">
    <header class="ad-topbar">
      <div class="ad-topbar-context">
        <span class="ad-topbar-eyebrow">INSTAWEB CONTROL CENTRE</span>
        <span class="ad-topbar-page">Administrator workspace</span>
      </div>
      <div class="ad-topbar-actions">
        <a class="ad-topbar-link" href="<?= $e($baseUrl . '/admin/notifications') ?>"><i data-lucide="bell"></i><span>Notifications</span></a>
        <a class="ad-topbar-link" href="<?= $e($baseUrl . '/profile') ?>"><i data-lucide="user-round"></i><span>Account</span></a>
      </div>
    </header>
    <main class="admin-dashboard-page">
    <header class="ad-page-heading">
      <div>
        <p class="ad-eyebrow">InstaWeb / Control centre</p>
        <h1>Platform overview</h1>
        <p class="ad-subtitle">A clear view of your users, hosted websites, and platform activity.</p>
      </div>
      <div class="ad-actions">
        <a class="ad-button" href="<?= $e($baseUrl . '/admin/websites') ?>"><i data-lucide="globe-2"></i> Manage websites</a>
        <a class="ad-button ad-button-primary" href="<?= $e($baseUrl . '/admin/users') ?>"><i data-lucide="user-plus"></i> Manage users</a>
      </div>
    </header>

    <section class="ad-metrics" aria-label="Platform statistics">
      <article class="ad-metric">
        <div class="ad-metric-top"><span>Total users</span><span class="ad-metric-icon"><i data-lucide="users"></i></span></div>
        <strong class="ad-metric-value"><?= number_format((int) ($stats['users'] ?? 0)) ?></strong>
        <p class="ad-metric-note"><?= number_format((int) ($stats['active_users'] ?? 0)) ?> active accounts</p>
      </article>
      <article class="ad-metric">
        <div class="ad-metric-top"><span>Hosted websites</span><span class="ad-metric-icon"><i data-lucide="globe-2"></i></span></div>
        <strong class="ad-metric-value"><?= number_format((int) ($stats['websites'] ?? 0)) ?></strong>
        <p class="ad-metric-note"><?= number_format((int) ($stats['published_websites'] ?? 0)) ?> published websites</p>
      </article>
      <article class="ad-metric">
        <div class="ad-metric-top"><span>Paid revenue</span><span class="ad-metric-icon"><i data-lucide="wallet"></i></span></div>
        <strong class="ad-metric-value">MWK <?= number_format((float) ($stats['paid_revenue'] ?? 0), 2) ?></strong>
        <p class="ad-metric-note"><?= number_format((int) ($stats['payments'] ?? 0)) ?> payment records</p>
      </article>
      <article class="ad-metric">
        <div class="ad-metric-top"><span>Open reports</span><span class="ad-metric-icon"><i data-lucide="flag"></i></span></div>
        <strong class="ad-metric-value"><?= number_format((int) ($stats['pending_reports'] ?? 0)) ?></strong>
        <p class="ad-metric-note"><?= number_format((int) ($stats['reports'] ?? 0)) ?> reports submitted in total</p>
      </article>
    </section>

    <div class="ad-main-grid">
      <div class="ad-stack">
        <section class="ad-panel">
          <div class="ad-panel-heading">
            <div><h2>Recently registered users</h2><p>The latest accounts created on InstaWeb.</p></div>
            <a class="ad-panel-link" href="<?= $e($baseUrl . '/admin/users') ?>">All users <i data-lucide="arrow-up-right"></i></a>
          </div>
          <div class="ad-table-wrap">
            <table class="ad-table">
              <thead><tr><th>User</th><th>Role</th><th>Account status</th><th>Email</th></tr></thead>
              <tbody>
              <?php foreach ($recentUsers as $user): ?>
                <tr>
                  <td><span class="ad-primary-cell"><?= $e($user['fullname'] ?? 'Unnamed user') ?></span><span class="ad-secondary-cell">@<?= $e($user['username'] ?? '') ?></span></td>
                  <td><?= $e(ucwords(str_replace('_', ' ', (string) ($user['role'] ?? 'user')))) ?></td>
                  <td><span class="ad-pill <?= $e(strtolower((string) ($user['account_status'] ?? 'unknown'))) ?>"><?= $e(ucwords(str_replace('_', ' ', (string) ($user['account_status'] ?? 'unknown')))) ?></span></td>
                  <td><?= ((int) ($user['email_verified'] ?? 0)) === 1 ? '<span class="ad-pill active">Verified</span>' : '<span class="ad-pill pending">Unverified</span>' ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if ($recentUsers === []): ?><tr><td colspan="4" class="ad-empty">No user accounts have been registered yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="ad-panel">
          <div class="ad-panel-heading">
            <div><h2>Recently added websites</h2><p>Hosting records and their current status.</p></div>
            <a class="ad-panel-link" href="<?= $e($baseUrl . '/admin/websites') ?>">All websites <i data-lucide="arrow-up-right"></i></a>
          </div>
          <div class="ad-table-wrap">
            <table class="ad-table">
              <thead><tr><th>Website</th><th>Owner</th><th>Status</th><th>Storage used</th></tr></thead>
              <tbody>
              <?php foreach ($recentWebsites as $website): ?>
                <tr>
                  <td><span class="ad-primary-cell"><?= $e(($website['website_name'] ?? '') ?: 'Untitled website') ?></span><span class="ad-secondary-cell"><?= $e(($website['custom_domain'] ?? '') ?: (($website['subdomain'] ?? '') ?: ($website['slug'] ?? ''))) ?></span></td>
                  <td><?= $e($website['username'] ?? 'Unknown') ?><span class="ad-secondary-cell"><?= $e($website['email'] ?? '') ?></span></td>
                  <td><span class="ad-pill <?= $e(strtolower((string) ($website['status'] ?? 'unknown'))) ?>"><?= $e(ucwords(str_replace('_', ' ', (string) ($website['status'] ?? 'unknown')))) ?></span></td>
                  <td><?= number_format(((int) ($website['storage_used'] ?? 0)) / 1048576, 2) ?> MB</td>
                </tr>
              <?php endforeach; ?>
              <?php if ($recentWebsites === []): ?><tr><td colspan="4" class="ad-empty">No websites have been created yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <aside class="ad-stack ad-side-grid">
        <section class="ad-panel">
          <div class="ad-panel-heading"><div><h2>Payment summary</h2><p>Records grouped by payment status.</p></div><a class="ad-panel-link" href="<?= $e($baseUrl . '/admin/payments') ?>">View all <i data-lucide="arrow-up-right"></i></a></div>
          <div class="ad-payment-list">
            <?php foreach ($paymentSummary as $payment): ?>
              <div class="ad-payment-row">
                <div class="ad-payment-label"><span class="ad-payment-icon"><i data-lucide="credit-card"></i></span><div><strong><?= $e(ucwords(str_replace('_', ' ', (string) ($payment['payment_status'] ?? 'unknown')))) ?></strong><small><?= number_format((int) ($payment['total'] ?? 0)) ?> transactions</small></div></div>
                <span class="ad-payment-amount">MWK <?= number_format((float) ($payment['amount'] ?? 0), 2) ?></span>
              </div>
            <?php endforeach; ?>
            <?php if ($paymentSummary === []): ?><p class="ad-empty">No payment records yet.</p><?php endif; ?>
          </div>
        </section>

        <section class="ad-panel">
          <div class="ad-panel-heading"><div><h2>Quick access</h2><p>Common platform administration tasks.</p></div></div>
          <div class="ad-shortcuts">
            <a class="ad-shortcut" href="<?= $e($baseUrl . '/admin/users') ?>"><span class="ad-shortcut-icon"><i data-lucide="users"></i></span><span><strong>User management</strong><small>Accounts, roles, and plans</small></span><i data-lucide="chevron-right"></i></a>
            <a class="ad-shortcut" href="<?= $e($baseUrl . '/admin/websites') ?>"><span class="ad-shortcut-icon"><i data-lucide="globe-2"></i></span><span><strong>Website management</strong><small>Hosting records and status</small></span><i data-lucide="chevron-right"></i></a>
            <a class="ad-shortcut" href="<?= $e($baseUrl . '/admin/reports') ?>"><span class="ad-shortcut-icon"><i data-lucide="shield-alert"></i></span><span><strong>Review reports</strong><small>Investigate reported websites</small></span><i data-lucide="chevron-right"></i></a>
            <a class="ad-shortcut" href="<?= $e($baseUrl . '/admin/settings') ?>"><span class="ad-shortcut-icon"><i data-lucide="settings"></i></span><span><strong>Platform settings</strong><small>Configure InstaWeb</small></span><i data-lucide="chevron-right"></i></a>
          </div>
        </section>
      </aside>
    </div>

    </main>
    <footer class="ad-footer">
      <span>© <?= date('Y') ?> InstaWeb. Administrator workspace.</span>
      <nav aria-label="Footer navigation">
        <a href="<?= $e($baseUrl . '/admin/settings') ?>">System settings</a>
        <a href="<?= $e($baseUrl . '/admin/legal') ?>">Policies</a>
        <a href="<?= $e($baseUrl . '/terms') ?>">Terms</a>
        <a href="<?= $e($baseUrl . '/privacy') ?>">Privacy</a>
      </nav>
    </footer>
  </section>
</div>
