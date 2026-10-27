<?php
$active = 'dashboard';
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$label = static fn ($value): string => ucwords(str_replace('_', ' ', (string) $value));
$tone = static function ($status): string {
    $status = strtolower((string) $status);
    if (in_array($status, ['active', 'published', 'paid', 'completed', 'success', 'successful', 'verified', 'approved'], true)) {
        return 'ok';
    }
    if (in_array($status, ['pending', 'draft', 'unverified', 'processing', 'initiated'], true)) {
        return 'warn';
    }
    if (in_array($status, ['suspended', 'failed', 'banned', 'blocked', 'rejected', 'cancelled', 'canceled', 'expired', 'refunded'], true)) {
        return 'bad';
    }
    return 'muted';
};
$initials = static function (string $name): string {
    $name = trim($name) !== '' ? trim($name) : '?';
    $parts = preg_split('/\s+/u', $name) ?: [$name];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($letters) : strtoupper($letters);
};
$percent = static fn (int $part, int $whole): int => $whole > 0 ? (int) min(100, round($part / $whole * 100)) : 0;

$totalUsers = (int) ($stats['users'] ?? 0);
$activeUsers = (int) ($stats['active_users'] ?? 0);
$totalWebsites = (int) ($stats['websites'] ?? 0);
$publishedWebsites = (int) ($stats['published_websites'] ?? 0);
$pendingReports = (int) ($stats['pending_reports'] ?? 0);
$totalReports = (int) ($stats['reports'] ?? 0);
$paymentTotal = 0.0;
foreach ($paymentSummary as $row) {
    $paymentTotal += (float) ($row['amount'] ?? 0);
}
?>
<style>
.admin-dashboard-page .ox{--ox-ink:#17191d;--ox-muted:#667085;--ox-line:#e4e7ec;--ox-soft:#f8fafc;--ox-accent:#f97316;--ox-accent-ink:#9a3412;--ox-accent-soft:#fff4ea;color:var(--ox-ink)}
.admin-dashboard-page .ox *{box-sizing:border-box}
.admin-dashboard-page .ox a:focus-visible,.admin-dashboard-page .ox button:focus-visible{outline:3px solid #f9731666;outline-offset:2px}

/* heading */
.ox-heading{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.25rem}
.ox-eyebrow{margin:0 0 .5rem;font-size:.72rem;font-weight:800;letter-spacing:.15em;text-transform:uppercase;color:var(--ox-muted)}
.ox-heading h1{margin:0;font-size:clamp(1.8rem,3vw,2.5rem);font-weight:800;letter-spacing:-.04em}
.ox-sub{margin:.5rem 0 0;color:var(--ox-muted);max-width:40rem}
.ox-actions{display:flex;flex-wrap:wrap;gap:.65rem}
.ox-button{display:inline-flex;align-items:center;gap:.45rem;min-height:2.6rem;padding:.55rem 1rem;border:1px solid #d0d5dd;border-radius:.65rem;background:#fff;color:var(--ox-ink);font-size:.85rem;font-weight:700;text-decoration:none}
.ox-button:hover{background:var(--ox-soft);border-color:#98a2b3;color:var(--ox-ink)}
.ox-button svg{width:1rem;height:1rem}
.ox-button--primary{background:var(--ox-ink);border-color:var(--ox-ink);color:#fff}
.ox-button--primary:hover{background:#2b3038;border-color:#2b3038;color:#fff}

/* attention banner */
.ox-attention{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;padding:1rem 1.25rem;border:1px solid #fed7aa;border-radius:1rem;background:linear-gradient(105deg,#fff 0%,#fff4ea 100%)}
.ox-attention--clear{border-color:var(--ox-line);background:#fff}
.ox-attention-text{display:flex;align-items:center;gap:.85rem}
.ox-attention-icon{display:grid;place-items:center;flex:0 0 auto;width:2.4rem;height:2.4rem;border-radius:.7rem;background:#ffedd5;color:var(--ox-accent-ink)}
.ox-attention--clear .ox-attention-icon{background:#e8f7ee;color:#166534}
.ox-attention-icon svg{width:1.15rem;height:1.15rem}
.ox-attention strong{display:block;font-size:.98rem}
.ox-attention span.ox-attention-note{display:block;color:var(--ox-muted);font-size:.84rem}

/* metrics */
.ox-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.9rem;margin-bottom:1.25rem}
.ox-metric{display:flex;flex-direction:column;gap:.35rem;padding:1.15rem;border:1px solid var(--ox-line);border-radius:1rem;background:var(--bs-body-bg,#fff);color:inherit;text-decoration:none;transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease}
.ox-metric:hover{transform:translateY(-2px);border-color:#fdba74;box-shadow:0 .7rem 1.4rem rgba(124,45,18,.1);color:inherit}
.ox-metric-top{display:flex;align-items:center;justify-content:space-between;gap:.5rem;color:var(--ox-muted);font-size:.8rem;font-weight:700}
.ox-metric-icon{display:grid;place-items:center;width:2rem;height:2rem;border-radius:.6rem;background:var(--ox-accent-soft);color:var(--ox-accent-ink)}
.ox-metric-icon svg{width:1rem;height:1rem}
.ox-metric-value{font-size:clamp(1.4rem,2.2vw,1.9rem);font-weight:800;letter-spacing:-.03em;overflow-wrap:anywhere}
.ox-metric-note{margin:0;color:var(--ox-muted);font-size:.8rem}
.ox-bar{display:block;height:.35rem;margin-top:.35rem;border-radius:9px;background:#eceff3;overflow:hidden}
.ox-bar i{display:block;height:100%;border-radius:9px;background:var(--ox-accent)}
.ox-metric--alert{border-color:#fed7aa;background:linear-gradient(160deg,#fff 0%,#fff4ea 100%)}

/* layout */
.ox-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:1.25rem;align-items:start}
.ox-stack{display:grid;gap:1.25rem;min-width:0}
.ox-panel{min-width:0;background:var(--bs-body-bg,#fff);border:1px solid var(--ox-line);border-radius:1.1rem;box-shadow:0 .6rem 1.8rem rgba(15,23,42,.04);overflow:hidden}
.ox-panel-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;padding:1.1rem 1.25rem;border-bottom:1px solid var(--ox-line)}
.ox-panel-head h2{margin:0;font-size:1.05rem;font-weight:800;letter-spacing:-.02em}
.ox-panel-head p{margin:.25rem 0 0;color:var(--ox-muted);font-size:.82rem}
.ox-link{flex:0 0 auto;display:inline-flex;align-items:center;gap:.3rem;color:var(--ox-accent-ink);font-size:.82rem;font-weight:800;text-decoration:none;white-space:nowrap}
.ox-link:hover{text-decoration:underline;color:var(--ox-accent-ink)}
.ox-link svg{width:.95rem;height:.95rem}

/* tables */
.ox-table-wrap{overflow-x:auto}
.ox-table{width:100%;border-collapse:collapse;font-size:.88rem}
.ox-table th{padding:.7rem 1.25rem;text-align:left;font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--ox-muted);background:var(--ox-soft);white-space:nowrap}
.ox-table td{padding:.85rem 1.25rem;border-top:1px solid var(--ox-line);vertical-align:middle}
.ox-table tbody tr:hover td{background:#fcfcfd}
.ox-cell{display:flex;align-items:center;gap:.75rem;min-width:0}
.ox-avatar{flex:0 0 auto;display:grid;place-items:center;width:2.25rem;height:2.25rem;border-radius:50%;background:#eef2ff;color:#3730a3;font-size:.78rem;font-weight:800}
.ox-avatar--site{border-radius:.6rem;background:var(--ox-accent-soft);color:var(--ox-accent-ink)}
.ox-primary{display:block;font-weight:700;overflow-wrap:anywhere}
.ox-secondary{display:block;color:var(--ox-muted);font-size:.78rem;overflow-wrap:anywhere}
.ox-empty{padding:2rem 1.25rem;text-align:center;color:var(--ox-muted);font-size:.88rem}
.ox-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;border-radius:999px;font-size:.72rem;font-weight:800;white-space:nowrap}
.ox-pill:before{content:'';width:.45rem;height:.45rem;border-radius:50%;background:currentColor}
.ox-pill--ok{background:#e8f7ee;color:#166534}
.ox-pill--warn{background:#fef3c7;color:#92400e}
.ox-pill--bad{background:#fef3f2;color:#b42318}
.ox-pill--muted{background:#f2f4f7;color:#475467}

/* payments */
.ox-payments{display:grid;gap:0}
.ox-payment{padding:.95rem 1.25rem;border-top:1px solid var(--ox-line)}
.ox-payment:first-child{border-top:0}
.ox-payment-row{display:flex;justify-content:space-between;align-items:center;gap:.75rem}
.ox-payment strong{display:flex;align-items:center;gap:.5rem;font-size:.9rem}
.ox-dot{width:.55rem;height:.55rem;border-radius:50%;background:#98a2b3}
.ox-dot--ok{background:#16a34a}.ox-dot--warn{background:#f59e0b}.ox-dot--bad{background:#dc2626}
.ox-payment small{display:block;margin-top:.15rem;color:var(--ox-muted);font-size:.76rem}
.ox-amount{font-weight:800;font-size:.9rem;white-space:nowrap}
.ox-payment .ox-bar{margin-top:.6rem}

/* quick access */
.ox-shortcuts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem;padding:1.1rem 1.25rem 1.25rem}
.ox-shortcut{display:flex;flex-direction:column;gap:.3rem;padding:.95rem;border:1px solid var(--ox-line);border-radius:.9rem;color:inherit;text-decoration:none;transition:border-color .16s,box-shadow .16s,transform .16s}
.ox-shortcut:hover{transform:translateY(-2px);border-color:#fdba74;box-shadow:0 .6rem 1.2rem rgba(124,45,18,.09);color:inherit}
.ox-shortcut-icon{display:grid;place-items:center;width:2rem;height:2rem;margin-bottom:.2rem;border-radius:.6rem;background:var(--ox-accent-soft);color:var(--ox-accent-ink)}
.ox-shortcut-icon svg{width:1rem;height:1rem}
.ox-shortcut strong{font-size:.88rem}
.ox-shortcut small{color:var(--ox-muted);font-size:.76rem;line-height:1.4}

@media(max-width:1100px){.ox-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.ox-grid{grid-template-columns:minmax(0,1fr)}}
@media(max-width:700px){
  .ox-metrics{grid-template-columns:minmax(0,1fr)}
  .ox-actions,.ox-actions .ox-button{width:100%}
  .ox-actions .ox-button{justify-content:center}
  .ox-attention .ox-button{width:100%;justify-content:center}
  .ox-table thead{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)}
  .ox-table,.ox-table tbody,.ox-table tr,.ox-table td{display:block;width:100%}
  .ox-table tr{padding:.85rem 1.25rem;border-top:1px solid var(--ox-line)}
  .ox-table tbody tr:first-child{border-top:0}
  .ox-table td{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.3rem 0;border:0}
  .ox-table td[data-label]:before{content:attr(data-label);flex:0 0 auto;color:var(--ox-muted);font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
  .ox-table td.ox-empty{display:block}
  .ox-shortcuts{grid-template-columns:minmax(0,1fr)}
}
@media(prefers-reduced-motion:reduce){.admin-dashboard-page .ox *{transition:none}}
</style>

<div class="admin-pro-shell iw-client-shell admin-dashboard-refresh" data-admin-shell>
  <?php include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>
  <button class="iw-sidebar-backdrop" type="button" data-sidebar-close aria-label="Close navigation" tabindex="-1"></button>

  <section class="admin-pro-main iw-client-main">
    <header class="ad-topbar">
      <button class="ad-topbar-menu" type="button" data-sidebar-open aria-controls="adminSidebar" aria-expanded="false" aria-label="Open navigation"><i data-lucide="menu"></i></button>
      <div class="ad-topbar-context">
        <span class="ad-topbar-eyebrow">INSTAWEB WORKSPACE</span>
        <span class="ad-topbar-page">Admin overview</span>
      </div>
      <nav class="ad-topbar-actions" aria-label="Account actions">
        <a class="ad-topbar-link" href="<?= $e($baseUrl . '/admin/notifications') ?>"><i data-lucide="bell"></i><span>Notifications</span></a>
        <a class="ad-topbar-link" href="<?= $e($baseUrl . '/profile') ?>"><i data-lucide="user-round"></i><span>Account</span></a>
      </nav>
    </header>

    <div class="admin-dashboard-page">
    <div class="ox">
      <header class="ox-heading">
        <div>
          <p class="ox-eyebrow">InstaWeb / Control centre</p>
          <h1>Platform overview</h1>
          <p class="ox-sub">Your users, hosted websites, revenue and anything that needs a decision.</p>
        </div>
        <div class="ox-actions">
          <a class="ox-button" href="<?= $e($baseUrl . '/admin/websites') ?>"><i data-lucide="globe-2"></i> Manage websites</a>
          <a class="ox-button ox-button--primary" href="<?= $e($baseUrl . '/admin/users') ?>"><i data-lucide="user-plus"></i> Manage users</a>
        </div>
      </header>

      <?php if ($pendingReports > 0): ?>
        <section class="ox-attention" aria-label="Needs attention">
          <div class="ox-attention-text">
            <span class="ox-attention-icon"><i data-lucide="shield-alert"></i></span>
            <div>
              <strong><?= number_format($pendingReports) ?> report<?= $pendingReports === 1 ? '' : 's' ?> waiting for review</strong>
              <span class="ox-attention-note">Reported websites stay public until you review them.</span>
            </div>
          </div>
          <a class="ox-button ox-button--primary" href="<?= $e($baseUrl . '/admin/reports') ?>">Review reports</a>
        </section>
      <?php else: ?>
        <section class="ox-attention ox-attention--clear" aria-label="Needs attention">
          <div class="ox-attention-text">
            <span class="ox-attention-icon"><i data-lucide="check-circle-2"></i></span>
            <div>
              <strong>Nothing needs your attention</strong>
              <span class="ox-attention-note">No reports are waiting for review.</span>
            </div>
          </div>
        </section>
      <?php endif; ?>

      <section class="ox-metrics" aria-label="Platform statistics">
        <a class="ox-metric" href="<?= $e($baseUrl . '/admin/users') ?>">
          <div class="ox-metric-top"><span>Total users</span><span class="ox-metric-icon"><i data-lucide="users"></i></span></div>
          <strong class="ox-metric-value"><?= number_format($totalUsers) ?></strong>
          <p class="ox-metric-note"><?= number_format($activeUsers) ?> active accounts (<?= $percent($activeUsers, $totalUsers) ?>%)</p>
          <span class="ox-bar" aria-hidden="true"><i style="width:<?= $percent($activeUsers, $totalUsers) ?>%"></i></span>
        </a>
        <a class="ox-metric" href="<?= $e($baseUrl . '/admin/websites') ?>">
          <div class="ox-metric-top"><span>Hosted websites</span><span class="ox-metric-icon"><i data-lucide="globe-2"></i></span></div>
          <strong class="ox-metric-value"><?= number_format($totalWebsites) ?></strong>
          <p class="ox-metric-note"><?= number_format($publishedWebsites) ?> published (<?= $percent($publishedWebsites, $totalWebsites) ?>%)</p>
          <span class="ox-bar" aria-hidden="true"><i style="width:<?= $percent($publishedWebsites, $totalWebsites) ?>%"></i></span>
        </a>
        <a class="ox-metric" href="<?= $e($baseUrl . '/admin/payments') ?>">
          <div class="ox-metric-top"><span>Paid revenue</span><span class="ox-metric-icon"><i data-lucide="wallet"></i></span></div>
          <strong class="ox-metric-value">MWK <?= number_format((float) ($stats['paid_revenue'] ?? 0), 2) ?></strong>
          <p class="ox-metric-note"><?= number_format((int) ($stats['payments'] ?? 0)) ?> payment records</p>
        </a>
        <a class="ox-metric <?= $pendingReports > 0 ? 'ox-metric--alert' : '' ?>" href="<?= $e($baseUrl . '/admin/reports') ?>">
          <div class="ox-metric-top"><span>Open reports</span><span class="ox-metric-icon"><i data-lucide="flag"></i></span></div>
          <strong class="ox-metric-value"><?= number_format($pendingReports) ?></strong>
          <p class="ox-metric-note"><?= number_format($totalReports) ?> submitted in total</p>
        </a>
      </section>

      <div class="ox-grid">
        <div class="ox-stack">
          <section class="ox-panel" aria-labelledby="ox-users-title">
            <div class="ox-panel-head">
              <div><h2 id="ox-users-title">Recently registered users</h2><p>The latest accounts created on InstaWeb.</p></div>
              <a class="ox-link" href="<?= $e($baseUrl . '/admin/users') ?>">All users <i data-lucide="arrow-up-right"></i></a>
            </div>
            <div class="ox-table-wrap">
              <table class="ox-table">
                <thead><tr><th scope="col">User</th><th scope="col">Role</th><th scope="col">Account</th><th scope="col">Email</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsers as $user): ?>
                  <tr>
                    <td>
                      <span class="ox-cell"><span class="ox-avatar" aria-hidden="true"><?= $e($initials((string) ($user['fullname'] ?? ''))) ?></span><span><span class="ox-primary"><?= $e($user['fullname'] ?? 'Unnamed user') ?></span><span class="ox-secondary">@<?= $e($user['username'] ?? '') ?></span></span></span>
                    </td>
                    <td data-label="Role"><?= $e($label($user['role'] ?? 'user')) ?></td>
                    <td data-label="Account"><span class="ox-pill ox-pill--<?= $e($tone($user['account_status'] ?? 'unknown')) ?>"><?= $e($label($user['account_status'] ?? 'unknown')) ?></span></td>
                    <td data-label="Email"><?= ((int) ($user['email_verified'] ?? 0)) === 1 ? '<span class="ox-pill ox-pill--ok">Verified</span>' : '<span class="ox-pill ox-pill--warn">Unverified</span>' ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if ($recentUsers === []): ?><tr><td colspan="4" class="ox-empty">No user accounts have been registered yet.</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <section class="ox-panel" aria-labelledby="ox-sites-title">
            <div class="ox-panel-head">
              <div><h2 id="ox-sites-title">Recently added websites</h2><p>Hosting records and their current status.</p></div>
              <a class="ox-link" href="<?= $e($baseUrl . '/admin/websites') ?>">All websites <i data-lucide="arrow-up-right"></i></a>
            </div>
            <div class="ox-table-wrap">
              <table class="ox-table">
                <thead><tr><th scope="col">Website</th><th scope="col">Owner</th><th scope="col">Status</th><th scope="col">Storage</th></tr></thead>
                <tbody>
                <?php foreach ($recentWebsites as $website):
                    $siteName = ($website['website_name'] ?? '') ?: 'Untitled website';
                    $siteAddress = ($website['custom_domain'] ?? '') ?: (($website['subdomain'] ?? '') ?: ($website['slug'] ?? '')); ?>
                  <tr>
                    <td>
                      <span class="ox-cell"><span class="ox-avatar ox-avatar--site" aria-hidden="true"><?= $e($initials($siteName)) ?></span><span><span class="ox-primary"><?= $e($siteName) ?></span><span class="ox-secondary"><?= $e($siteAddress) ?></span></span></span>
                    </td>
                    <td data-label="Owner"><span><?= $e($website['username'] ?? 'Unknown') ?><span class="ox-secondary"><?= $e($website['email'] ?? '') ?></span></span></td>
                    <td data-label="Status"><span class="ox-pill ox-pill--<?= $e($tone($website['status'] ?? 'unknown')) ?>"><?= $e($label($website['status'] ?? 'unknown')) ?></span></td>
                    <td data-label="Storage"><?= number_format(((int) ($website['storage_used'] ?? 0)) / 1048576, 2) ?> MB</td>
                  </tr>
                <?php endforeach; ?>
                <?php if ($recentWebsites === []): ?><tr><td colspan="4" class="ox-empty">No websites have been created yet.</td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <aside class="ox-stack">
          <section class="ox-panel" aria-labelledby="ox-pay-title">
            <div class="ox-panel-head">
              <div><h2 id="ox-pay-title">Payment summary</h2><p>Records grouped by payment status.</p></div>
              <a class="ox-link" href="<?= $e($baseUrl . '/admin/payments') ?>">View all <i data-lucide="arrow-up-right"></i></a>
            </div>
            <div class="ox-payments">
              <?php foreach ($paymentSummary as $payment):
                  $paymentAmount = (float) ($payment['amount'] ?? 0);
                  $paymentShare = $paymentTotal > 0 ? (int) round($paymentAmount / $paymentTotal * 100) : 0; ?>
                <div class="ox-payment">
                  <div class="ox-payment-row">
                    <div>
                      <strong><span class="ox-dot ox-dot--<?= $e($tone($payment['payment_status'] ?? 'unknown')) ?>" aria-hidden="true"></span><?= $e($label($payment['payment_status'] ?? 'unknown')) ?></strong>
                      <small><?= number_format((int) ($payment['total'] ?? 0)) ?> transactions</small>
                    </div>
                    <span class="ox-amount">MWK <?= number_format($paymentAmount, 2) ?></span>
                  </div>
                  <span class="ox-bar" aria-hidden="true"><i style="width:<?= $paymentShare ?>%"></i></span>
                </div>
              <?php endforeach; ?>
              <?php if ($paymentSummary === []): ?><p class="ox-empty">No payment records yet.</p><?php endif; ?>
            </div>
          </section>

          <section class="ox-panel" aria-labelledby="ox-quick-title">
            <div class="ox-panel-head"><div><h2 id="ox-quick-title">Quick access</h2><p>Common administration tasks.</p></div></div>
            <div class="ox-shortcuts">
              <a class="ox-shortcut" href="<?= $e($baseUrl . '/admin/users') ?>"><span class="ox-shortcut-icon"><i data-lucide="users"></i></span><strong>User management</strong><small>Accounts, roles and plans</small></a>
              <a class="ox-shortcut" href="<?= $e($baseUrl . '/admin/websites') ?>"><span class="ox-shortcut-icon"><i data-lucide="globe-2"></i></span><strong>Website management</strong><small>Hosting records and status</small></a>
              <a class="ox-shortcut" href="<?= $e($baseUrl . '/admin/reports') ?>"><span class="ox-shortcut-icon"><i data-lucide="shield-alert"></i></span><strong>Review reports</strong><small>Investigate reported websites</small></a>
              <a class="ox-shortcut" href="<?= $e($baseUrl . '/admin/settings') ?>"><span class="ox-shortcut-icon"><i data-lucide="settings"></i></span><strong>Platform settings</strong><small>Configure InstaWeb</small></a>
            </div>
          </section>
        </aside>
      </div>
    </div>

    <footer class="ad-footer">
      <span>© <?= $e(date('Y')) ?> InstaWeb · Administration</span>
      <nav aria-label="Administrative and legal links">
        <a href="<?= $e($baseUrl . '/admin/settings') ?>">System settings</a>
        <a href="<?= $e($baseUrl . '/admin/legal') ?>">Policies</a>
        <a href="<?= $e($baseUrl . '/terms') ?>">Terms</a>
        <a href="<?= $e($baseUrl . '/privacy') ?>">Privacy</a>
      </nav>
    </footer>
    </div>
  </section>
</div>