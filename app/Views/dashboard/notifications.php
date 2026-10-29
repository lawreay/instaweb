<?php
$joinedAt = isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A';
$isVerified = ((int) ($user['email_verified'] ?? 0)) === 1;
$status = (string) ($user['account_status'] ?? 'pending');
$baseUrl = (string) ($app['base_url'] ?? '');
$unreadCount = 0;
foreach ($notifications as $countNotification) {
    if ((int) ($countNotification['is_read'] ?? 0) === 0) { $unreadCount++; }
}
?>
<section class="client-dashboard iw-client-page iw-notifications-page">
<div class="client-main">
  <div class="nt">
    <style>
      .nt { --nt-ink:#14201A; --nt-muted:#5B675F; --nt-line:#E4EAE5; --nt-soft:#F6F9F6; --nt-green:#1F7A4D; --nt-green-dark:#14532D; color:var(--nt-ink); display:flex; flex-direction:column; gap:20px; }
      .nt a { text-decoration:none; }
      .nt .nt-head { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; }
      .nt .nt-kicker { margin:0 0 6px; font-size:12px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--nt-green); }
      .nt .nt-head h1 { margin:0 0 6px; font-size:clamp(26px,4vw,34px); font-weight:800; letter-spacing:-.02em; }
      .nt .nt-head p.nt-lede { margin:0; font-size:15px; color:var(--nt-muted); max-width:560px; }
      .nt .nt-head-actions { display:flex; gap:8px; flex-wrap:wrap; }
      .nt .nt-btn { min-height:44px; padding:0 16px; border-radius:12px; border:1px solid #D8E0DA; background:#fff; color:var(--nt-ink); font-size:14px; font-weight:600; display:inline-flex; align-items:center; gap:8px; white-space:nowrap; }
      .nt .nt-btn:hover { background:var(--nt-soft); color:var(--nt-ink); }
      .nt .nt-btn svg { width:16px; height:16px; }
      .nt .nt-btn.danger { color:#A32A21; border-color:#D9B4B0; }
      .nt .nt-card { background:#fff; border:1px solid var(--nt-line); border-radius:20px; padding:22px 24px; }
      .nt .nt-card-head { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
      .nt .nt-card-head h2 { margin:0; font-size:18px; font-weight:700; }
      .nt .nt-card-head small { display:block; font-size:13px; color:var(--nt-muted); margin-top:2px; }
      .nt .nt-filters { display:flex; gap:8px; flex-wrap:wrap; }
      .nt .nt-filter { min-height:44px; padding:0 16px; border-radius:999px; border:1px solid #D8E0DA; background:#fff; color:#3F4B44; font-size:13px; font-weight:600; cursor:pointer; }
      .nt .nt-filter.is-active { background:var(--nt-ink); border-color:var(--nt-ink); color:#fff; }
      .nt .nt-list { display:flex; flex-direction:column; gap:12px; }
      .nt .nt-item { display:flex; gap:14px; align-items:flex-start; border:1px solid var(--nt-line); border-radius:16px; padding:16px; background:#fff; }
      .nt .nt-item[hidden] { display:none; }
      .nt .nt-item.unread { background:#F2F8F4; border-color:#CFE5D8; }
      .nt .nt-icon { width:44px; height:44px; flex:none; border-radius:12px; background:#E3F2E8; color:var(--nt-green-dark); display:inline-flex; align-items:center; justify-content:center; }
      .nt .nt-item.read .nt-icon { background:#EEF2EF; color:var(--nt-muted); }
      .nt .nt-icon svg { width:20px; height:20px; }
      .nt .nt-body { flex:1; min-width:0; }
      .nt .nt-title { display:flex; align-items:baseline; justify-content:space-between; gap:4px 12px; flex-wrap:wrap; }
      .nt .nt-title strong { font-size:15px; font-weight:700; display:inline-flex; align-items:center; gap:8px; }
      .nt .nt-title time, .nt .nt-title span.nt-time { font-size:12px; color:var(--nt-muted); white-space:nowrap; }
      .nt .nt-new { font-size:11px; font-weight:700; padding:2px 8px; border-radius:999px; background:var(--nt-green); color:#fff; letter-spacing:.03em; }
      .nt .nt-body p { margin:4px 0 8px; font-size:14px; color:#3F4B44; line-height:1.55; }
      .nt .nt-link { display:inline-flex; align-items:center; min-height:44px; font-size:13px; font-weight:600; color:var(--nt-green); }
      .nt .nt-link:hover { color:var(--nt-green-dark); }
      .nt .nt-empty { text-align:center; padding:36px 12px; }
      .nt .nt-empty[hidden] { display:none; }
      .nt .nt-empty svg { width:32px; height:32px; color:var(--nt-muted); }
      .nt .nt-empty strong { display:block; margin:10px 0 4px; font-size:17px; }
      .nt .nt-empty p { margin:0; font-size:14px; color:var(--nt-muted); }
      @media (max-width: 640px) { .nt .nt-card { padding:18px 16px; } .nt .nt-item { padding:14px; } }
    </style>

    <header class="nt-head">
      <div>
        <p class="nt-kicker">Notification center</p>
        <h1>My Notifications</h1>
        <p class="nt-lede">Browse all recent alerts, updates, and account messages in one place.</p>
      </div>
      <div class="nt-head-actions">
        <a class="nt-btn" href="<?= htmlspecialchars($baseUrl . '/profile', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="user"></i><span>Profile</span></a>
        <a class="nt-btn" href="<?= htmlspecialchars($baseUrl . '/dashboard/notifications', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="bell"></i><span>Alerts</span></a>
        <a class="nt-btn danger" href="<?= htmlspecialchars($baseUrl . '/logout', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="log-out"></i><span>Logout</span></a>
      </div>
    </header>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $flashKey => $type): ?>
      <?php if ($message = \App\Core\Session::pullFlash($flashKey)): ?>
        <div class="alert alert-<?= $type ?> shadow-sm" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    <?php endforeach; ?>

    <section class="nt-card" aria-labelledby="ntTitle">
      <div class="nt-card-head">
        <div>
          <h2 id="ntTitle">Recent notifications</h2>
          <small><?= count($notifications) ?> items · <?= (int) $unreadCount ?> unread</small>
        </div>
        <?php if ($notifications !== []): ?>
          <div class="nt-filters" id="ntFilters">
            <button type="button" class="nt-filter is-active" data-filter="all">All</button>
            <button type="button" class="nt-filter" data-filter="unread">Unread</button>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($notifications === []): ?>
        <div class="nt-empty">
          <i data-lucide="bell-off"></i>
          <strong>No notifications yet</strong>
          <p>When your account receives an update, it will appear here.</p>
        </div>
      <?php else: ?>
        <div class="nt-list" id="ntList">
          <?php foreach ($notifications as $notification): ?>
            <?php
              $isUnread = (int) ($notification['is_read'] ?? 0) === 0;
              $createdTs = strtotime((string) ($notification['created_at'] ?? ''));
            ?>
            <article class="nt-item <?= $isUnread ? 'unread' : 'read' ?>" data-state="<?= $isUnread ? 'unread' : 'read' ?>">
              <div class="nt-icon">
                <i data-lucide="<?= htmlspecialchars($notification['icon'] ?? 'bell', ENT_QUOTES, 'UTF-8') ?>"></i>
              </div>
              <div class="nt-body">
                <div class="nt-title">
                  <strong><?= htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8') ?><?php if ($isUnread): ?><span class="nt-new">New</span><?php endif; ?></strong>
                  <?php if ($createdTs !== false): ?><time datetime="<?= htmlspecialchars(date(DATE_ATOM, $createdTs), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('M j, Y H:i', $createdTs), ENT_QUOTES, 'UTF-8') ?></time><?php endif; ?>
                </div>
                <p><?= htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php if (!empty($notification['target_url'])): ?>
                  <a class="nt-link" href="<?= htmlspecialchars($baseUrl . $notification['target_url'], ENT_QUOTES, 'UTF-8') ?>">View details</a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
          <div class="nt-empty" id="ntNoMatch" hidden>
            <i data-lucide="check-circle-2"></i>
            <strong>You’re all caught up</strong>
            <p>No unread notifications right now.</p>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
</section>

<?php if ($notifications !== []): ?>
<script>
(function () {
  var filters = document.getElementById('ntFilters');
  var items = Array.prototype.slice.call(document.querySelectorAll('#ntList .nt-item'));
  var empty = document.getElementById('ntNoMatch');
  filters.addEventListener('click', function (e) {
    var btn = e.target.closest('.nt-filter');
    if (!btn) return;
    var mode = btn.getAttribute('data-filter');
    Array.prototype.forEach.call(filters.querySelectorAll('.nt-filter'), function (b) {
      b.classList.toggle('is-active', b === btn);
    });
    var shown = 0;
    items.forEach(function (item) {
      var ok = mode === 'all' || item.getAttribute('data-state') === 'unread';
      item.hidden = !ok;
      if (ok) shown++;
    });
    empty.hidden = shown !== 0;
  });
})();
</script>
<?php endif; ?>