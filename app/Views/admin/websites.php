<div class="admin-pro-shell">
  <?php $active = 'websites'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

  <section class="admin-pro-main iw-websites">
    <style>
      .iw-websites { --iw-ink:#14201A; --iw-muted:#5B675F; --iw-line:#E4EAE5; --iw-green:#1F7A4D; --iw-green-dark:#14532D; --iw-danger:#A32A21; }
      .iw-websites .iw-topbar { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
      .iw-websites .iw-kicker { margin:0 0 4px; font-size:12px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--iw-green); }
      .iw-websites .iw-topbar h1 { margin:0; font-size:32px; font-weight:800; letter-spacing:-.01em; color:var(--iw-ink); }
      .iw-websites .iw-total { display:flex; align-items:center; gap:12px; background:#fff; border:1px solid var(--iw-line); border-radius:14px; padding:10px 18px; }
      .iw-websites .iw-total span { font-size:13px; color:var(--iw-muted); }
      .iw-websites .iw-total strong { font-size:24px; font-weight:800; color:var(--iw-green-dark); }
      .iw-websites .iw-card { background:#fff; border:1px solid var(--iw-line); border-radius:20px; overflow:hidden; }
      .iw-websites .iw-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:20px 20px 16px; }
      .iw-websites .iw-card-head h2 { margin:0; font-size:18px; font-weight:700; color:var(--iw-ink); }
      .iw-websites .iw-card-head small { display:block; font-size:13px; color:var(--iw-muted); }
      .iw-websites .iw-search { display:flex; align-items:center; gap:10px; min-height:44px; min-width:260px; padding:0 14px; border:1px solid #D8E0DA; border-radius:12px; background:#F9FBF9; color:var(--iw-muted); }
      .iw-websites .iw-search input { border:0; outline:0; background:transparent; font-size:14px; color:var(--iw-ink); flex:1; min-width:0; }
      .iw-websites .iw-filters { display:flex; gap:8px; flex-wrap:wrap; padding:0 20px 16px; }
      .iw-websites .iw-filter { min-height:44px; padding:0 16px; border-radius:999px; border:1px solid #D8E0DA; background:#fff; color:#3F4B44; font-size:13px; font-weight:600; cursor:pointer; }
      .iw-websites .iw-filter.is-active { background:var(--iw-ink); border-color:var(--iw-ink); color:#fff; }
      .iw-websites .iw-table { width:100%; min-width:960px; border-collapse:collapse; margin:0; }
      .iw-websites .iw-table thead { background:#F9FBF9; }
      .iw-websites .iw-table th { text-align:left; font-size:12px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:var(--iw-muted); padding:12px 16px; border-bottom:1px solid var(--iw-line); white-space:nowrap; }
      .iw-websites .iw-table td { padding:16px; border-bottom:1px solid #EEF2EF; font-size:14px; color:var(--iw-ink); vertical-align:middle; }
      .iw-websites .iw-site { display:flex; align-items:center; gap:12px; }
      .iw-websites .iw-avatar { width:40px; height:40px; flex:none; border-radius:12px; background:#EAF3EC; color:var(--iw-green-dark); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; }
      .iw-websites .iw-site-name { display:flex; align-items:center; flex-wrap:wrap; gap:8px; font-weight:600; font-size:15px; }
      .iw-websites .iw-sub { display:block; font-size:13px; color:var(--iw-muted); margin-top:2px; font-weight:400; }
      .iw-websites .iw-badge { font-size:11px; font-weight:600; padding:3px 8px; border-radius:999px; background:#E6EEFB; color:#1A4DA8; white-space:nowrap; }
      .iw-websites .status-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 10px; border-radius:999px; font-size:13px; font-weight:600; text-transform:capitalize; background:#EEF2EF; color:#3F4B44; }
      .iw-websites .status-chip::before { content:""; width:7px; height:7px; border-radius:50%; background:currentColor; }
      .iw-websites .status-chip.published { background:#DCF3E4; color:#14532D; }
      .iw-websites .status-chip.draft { background:#FDF0C9; color:#6B4A00; }
      .iw-websites .status-chip.suspended { background:#FBE0DE; color:#8A1C14; }
      .iw-websites .iw-actions { display:flex; gap:8px; align-items:center; justify-content:flex-end; flex-wrap:wrap; }
      .iw-websites .iw-actions form { margin:0; }
      .iw-websites .iw-btn { min-height:44px; padding:0 16px; border-radius:10px; border:1px solid var(--iw-line); background:#fff; font-size:13px; font-weight:600; cursor:pointer; }
      .iw-websites .iw-btn-suspend { border-color:#D9B4B0; color:var(--iw-danger); }
      .iw-websites .iw-btn-unsuspend { border-color:#B9D8C5; color:var(--iw-green-dark); }
      .iw-websites .iw-btn-delete { min-width:44px; padding:0; color:var(--iw-danger); display:inline-flex; align-items:center; justify-content:center; }
      .iw-websites .iw-btn:hover { background:#F9FBF9; }
      .iw-websites .iw-protected { font-size:13px; color:var(--iw-muted); }
      .iw-websites .iw-empty td { text-align:center; color:var(--iw-muted); padding:40px 16px; }
      .iw-websites .iw-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:8px 20px; font-size:13px; color:var(--iw-muted); }
      .iw-websites .iw-footer a { font-weight:600; text-decoration:none; color:var(--iw-green); display:inline-flex; align-items:center; min-height:44px; }
      @media (max-width: 860px) { .iw-websites .iw-topbar h1 { font-size:26px; } .iw-websites .iw-search { min-width:0; width:100%; } }
    </style>

    <header class="iw-topbar">
      <div>
        <p class="iw-kicker">Hosting management</p>
        <h1>Websites</h1>
      </div>
      <div class="iw-total">
        <span>Total sites</span>
        <strong><?= count($websites) ?></strong>
      </div>
    </header>

    <div class="iw-card">
      <div class="iw-card-head">
        <div>
          <h2>Hosted Websites</h2>
          <small><?= count($websites) ?> records</small>
        </div>
        <label class="iw-search">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <span class="visually-hidden">Search websites</span>
          <input type="search" id="iwSearch" placeholder="Search name, domain or owner" autocomplete="off">
        </label>
      </div>

      <div class="iw-filters" id="iwFilters">
        <button type="button" class="iw-filter is-active" data-filter="all">All &middot; <?= count($websites) ?></button>
        <button type="button" class="iw-filter" data-filter="published">Published</button>
        <button type="button" class="iw-filter" data-filter="draft">Draft</button>
        <button type="button" class="iw-filter" data-filter="suspended">Suspended</button>
      </div>

      <div class="table-responsive">
        <table class="iw-table admin-pro-table">
          <thead>
            <tr>
              <th>Website</th>
              <th>Owner</th>
              <th>Status</th>
              <th>Storage</th>
              <th>Bandwidth</th>
              <th>Expires</th>
              <th style="text-align:right">Actions</th>
            </tr>
          </thead>
          <tbody id="iwRows">
            <?php foreach ($websites as $website): ?>
              <?php
                $isSystemTemplate = !empty($website['is_system_template']);
                $siteName = $website['website_name'] ?: 'Untitled website';
                $siteHost = $website['custom_domain'] ?: ($website['subdomain'] ?: $website['slug']);
                $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($siteName, 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($siteName, 0, 1));
                $searchText = strtolower($siteName . ' ' . $siteHost . ' ' . $website['username'] . ' ' . $website['email']);
              ?>
              <tr data-status="<?= htmlspecialchars(strtolower((string) $website['status']), ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                <td>
                  <div class="iw-site">
                    <div class="iw-avatar" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                    <div>
                      <div class="iw-site-name">
                        <span><?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($isSystemTemplate): ?><span class="iw-badge">Default template</span><?php endif; ?>
                      </div>
                      <small class="iw-sub"><?= htmlspecialchars($siteHost, ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                  </div>
                </td>
                <td>
                  <strong style="font-weight:500"><?= htmlspecialchars($website['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <small class="iw-sub"><?= htmlspecialchars($website['email'], ENT_QUOTES, 'UTF-8') ?></small>
                </td>
                <td><span class="status-chip <?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($website['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td style="white-space:nowrap"><?= number_format(((int) $website['storage_used']) / 1048576, 2) ?> MB</td>
                <td style="white-space:nowrap"><?= number_format(((int) $website['bandwidth_used']) / 1048576, 2) ?> MB</td>
                <td><?= htmlspecialchars($website['expires_at'] ?? 'No expiry', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <div class="iw-actions">
                    <?php if ($isSystemTemplate): ?>
                      <span class="iw-protected">Protected system portfolio</span>
                    <?php elseif ($website['status'] === 'suspended'): ?>
                      <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites/unsuspend', ENT_QUOTES, 'UTF-8') ?>">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                        <button class="iw-btn iw-btn-unsuspend" type="submit">Unsuspend</button>
                      </form>
                    <?php else: ?>
                      <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites/suspend', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Suspend this website and remove its public files?');">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                        <button class="iw-btn iw-btn-suspend" type="submit">Suspend</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/websites/delete', ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('Permanently delete this website, draft files, and public files?');">
                      <?= \App\Core\Csrf::field() ?>
                      <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                      <button class="iw-btn iw-btn-delete" type="submit" aria-label="Delete <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>" title="Delete">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/></svg>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($websites === []): ?>
              <tr class="iw-empty"><td colspan="7">No websites found.</td></tr>
            <?php endif; ?>
            <tr class="iw-empty" id="iwNoMatch" hidden><td colspan="7">No websites match your search.</td></tr>
          </tbody>
        </table>
      </div>

      <footer class="iw-footer admin-pro-footer">
        <span>Website list updated <?= htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8') ?></span>
        <span><a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>">Hosting settings</a></span>
      </footer>
    </div>
  </section>
</div>

<script>
(function () {
  var rows = Array.prototype.slice.call(document.querySelectorAll('#iwRows tr[data-status]'));
  var noMatch = document.getElementById('iwNoMatch');
  var search = document.getElementById('iwSearch');
  var filters = document.getElementById('iwFilters');
  var active = 'all';
  function apply() {
    var q = (search.value || '').toLowerCase().trim();
    var shown = 0;
    rows.forEach(function (row) {
      var ok = (active === 'all' || row.getAttribute('data-status') === active) &&
               (q === '' || row.getAttribute('data-search').indexOf(q) !== -1);
      row.hidden = !ok;
      if (ok) shown++;
    });
    if (noMatch) noMatch.hidden = !(rows.length > 0 && shown === 0);
  }
  search.addEventListener('input', apply);
  filters.addEventListener('click', function (e) {
    var btn = e.target.closest('.iw-filter');
    if (!btn) return;
    active = btn.getAttribute('data-filter');
    Array.prototype.forEach.call(filters.querySelectorAll('.iw-filter'), function (b) {
      b.classList.toggle('is-active', b === btn);
    });
    apply();
  });
})();
</script>