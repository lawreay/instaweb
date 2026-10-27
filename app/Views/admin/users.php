<?php
$planOf = static fn (array $u): string => $u['current_plan'] ?? ((in_array($u['role'], ['admin', 'moderator', 'premium'], true)) ? 'premium' : 'free');
$premiumCount = 0;
$unverifiedCount = 0;
foreach ($users as $countUser) {
    if ($planOf($countUser) === 'premium') { $premiumCount++; }
    if (((int) $countUser['email_verified']) !== 1) { $unverifiedCount++; }
}
$freeCount = count($users) - $premiumCount;
?>
<div class="admin-pro-shell">
  <?php $active = 'users'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

  <section class="admin-pro-main iw-users">
    <style>
      .iw-users { --iw-ink:#14201A; --iw-muted:#5B675F; --iw-line:#E4EAE5; --iw-soft:#F9FBF9; --iw-green:#1F7A4D; --iw-green-dark:#14532D; --iw-danger:#A32A21; }
      .iw-users .iw-topbar { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
      .iw-users .iw-kicker { margin:0 0 4px; font-size:12px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--iw-green); }
      .iw-users .iw-topbar h1 { margin:0; font-size:30px; font-weight:800; letter-spacing:-.01em; color:var(--iw-ink); }
      .iw-users .iw-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:20px; }
      .iw-users .iw-stat { background:#fff; border:1px solid var(--iw-line); border-radius:14px; padding:12px 16px; }
      .iw-users .iw-stat span { display:block; font-size:12px; color:var(--iw-muted); }
      .iw-users .iw-stat strong { font-size:24px; font-weight:800; color:var(--iw-green-dark); }
      .iw-users .iw-card { background:#fff; border:1px solid var(--iw-line); border-radius:20px; overflow:hidden; }
      .iw-users .iw-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; padding:20px 20px 16px; }
      .iw-users .iw-card-head h2 { margin:0; font-size:18px; font-weight:700; color:var(--iw-ink); }
      .iw-users .iw-card-head small { display:block; font-size:13px; color:var(--iw-muted); }
      .iw-users .iw-search { display:flex; align-items:center; gap:10px; min-height:44px; min-width:260px; padding:0 14px; border:1px solid #D8E0DA; border-radius:12px; background:var(--iw-soft); color:var(--iw-muted); }
      .iw-users .iw-search input { border:0; outline:0; background:transparent; font-size:14px; color:var(--iw-ink); flex:1; min-width:0; }
      .iw-users .iw-filters { display:flex; gap:8px; flex-wrap:wrap; padding:0 20px 16px; }
      .iw-users .iw-filter { min-height:44px; padding:0 16px; border-radius:999px; border:1px solid #D8E0DA; background:#fff; color:#3F4B44; font-size:13px; font-weight:600; cursor:pointer; }
      .iw-users .iw-filter.is-active { background:var(--iw-ink); border-color:var(--iw-ink); color:#fff; }
      .iw-users .iw-table { width:100%; min-width:1040px; border-collapse:collapse; margin:0; }
      .iw-users .iw-table thead { background:var(--iw-soft); }
      .iw-users .iw-table th { text-align:left; font-size:12px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:var(--iw-muted); padding:12px 16px; border-bottom:1px solid var(--iw-line); white-space:nowrap; }
      .iw-users .iw-table td { padding:14px 16px; border-bottom:1px solid #EEF2EF; font-size:14px; color:var(--iw-ink); vertical-align:middle; }
      .iw-users .iw-user { display:flex; align-items:center; gap:12px; }
      .iw-users .iw-avatar { width:40px; height:40px; flex:none; border-radius:50%; background:#EAF3EC; color:var(--iw-green-dark); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; }
      .iw-users .iw-name { font-weight:600; font-size:15px; display:block; }
      .iw-users .iw-sub { display:block; font-size:13px; color:var(--iw-muted); margin-top:2px; font-weight:400; }
      .iw-users .iw-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; text-transform:capitalize; background:#EEF2EF; color:#3F4B44; white-space:nowrap; }
      .iw-users .iw-chip.premium { background:#14201A; color:#fff; }
      .iw-users .iw-chip.free { background:#DCF3E4; color:#14532D; }
      .iw-users .iw-chip.pending { background:#FDF0C9; color:#6B4A00; }
      .iw-users .iw-chip.active, .iw-users .iw-chip.verified { background:#DCF3E4; color:#14532D; }
      .iw-users .iw-chip.suspended, .iw-users .iw-chip.banned, .iw-users .iw-chip.unverified { background:#FBE0DE; color:#8A1C14; }
      .iw-users .iw-actions { display:flex; gap:6px; align-items:center; flex-wrap:nowrap; }
      .iw-users .iw-actions form { margin:0; }
      .iw-users .iw-btn { min-height:44px; padding:0 12px; border-radius:10px; border:1px solid var(--iw-line); background:#fff; color:var(--iw-ink); font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:6px; white-space:nowrap; }
      .iw-users .iw-btn:hover { background:var(--iw-soft); color:var(--iw-ink); }
      .iw-users .iw-btn svg { width:16px; height:16px; }
      .iw-users .iw-btn.icon { min-width:44px; padding:0; }
      .iw-users .iw-btn.primary { background:var(--iw-green); border-color:var(--iw-green); color:#fff; }
      .iw-users .iw-btn.primary:hover { background:var(--iw-green-dark); color:#fff; }
      .iw-users .iw-btn.ok { border-color:#B9D8C5; color:var(--iw-green-dark); }
      .iw-users .iw-btn.danger { border-color:#D9B4B0; color:var(--iw-danger); }
      .iw-users .iw-empty td { text-align:center; color:var(--iw-muted); padding:40px 16px; }
      .iw-users .iw-footer { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding:8px 20px; font-size:13px; color:var(--iw-muted); }
      .iw-users .iw-footer a { font-weight:600; text-decoration:none; color:var(--iw-green); display:inline-flex; align-items:center; min-height:44px; }
      @media (max-width: 720px) { .iw-users .iw-topbar h1 { font-size:24px; } .iw-users .iw-search { min-width:0; width:100%; } }
    </style>

    <header class="iw-topbar">
      <div>
        <p class="iw-kicker">Account management</p>
        <h1>Users</h1>
      </div>
      <a class="iw-btn primary" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/register', ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="user-plus"></i> Add user</a>
    </header>

    <div class="iw-stats">
      <div class="iw-stat"><span>Total users</span><strong><?= count($users) ?></strong></div>
      <div class="iw-stat"><span>Premium</span><strong><?= (int) $premiumCount ?></strong></div>
      <div class="iw-stat"><span>Free</span><strong><?= (int) $freeCount ?></strong></div>
      <div class="iw-stat"><span>Unverified</span><strong><?= (int) $unverifiedCount ?></strong></div>
    </div>

    <div class="iw-card">
      <div class="iw-card-head">
        <div>
          <h2>All Users</h2>
          <small><?= count($users) ?> records</small>
        </div>
        <label class="iw-search">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          <span class="visually-hidden">Search users</span>
          <input type="search" id="iwUserSearch" placeholder="Search name, username, email or phone" autocomplete="off">
        </label>
      </div>
      <div class="iw-filters" id="iwUserFilters">
        <button type="button" class="iw-filter is-active" data-filter="all">All &middot; <?= count($users) ?></button>
        <button type="button" class="iw-filter" data-filter="premium">Premium</button>
        <button type="button" class="iw-filter" data-filter="free">Free</button>
        <button type="button" class="iw-filter" data-filter="unverified">Unverified</button>
      </div>

      <div class="table-responsive">
        <table class="iw-table admin-pro-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Contact</th>
              <th>Country</th>
              <th>Role</th>
              <th>Plan</th>
              <th>Status</th>
              <th>Verified</th>
              <th>Joined</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="iwUserRows">
            <?php foreach ($users as $user): ?>
              <?php
                $currentPlan = $planOf($user);
                $isVerified = ((int) $user['email_verified']) === 1;
                $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr((string) $user['fullname'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr((string) $user['fullname'], 0, 1));
                $searchText = strtolower($user['fullname'] . ' ' . $user['username'] . ' ' . $user['email'] . ' ' . ($user['phone'] ?? ''));
              ?>
              <tr data-plan="<?= htmlspecialchars($currentPlan, ENT_QUOTES, 'UTF-8') ?>" data-verified="<?= $isVerified ? '1' : '0' ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                <td>
                  <div class="iw-user">
                    <div class="iw-avatar" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
                    <div>
                      <strong class="iw-name"><?= htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8') ?></strong>
                      <small class="iw-sub">@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?><small class="iw-sub"><?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                <td><?= htmlspecialchars($user['country'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="iw-chip status-chip"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="iw-chip plan-chip <?= htmlspecialchars($currentPlan, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($currentPlan), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="iw-chip status-chip <?= htmlspecialchars($user['account_status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($user['account_status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="iw-chip <?= $isVerified ? 'verified' : 'unverified' ?>"><?= $isVerified ? 'Yes' : 'No' ?></span></td>
                <td style="white-space:nowrap"><?= htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <div class="iw-actions">
                    <a class="iw-btn" href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/users/edit?id=' . (int) $user['id'], ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="square-pen"></i> Edit</a>
                    <?php if ((int) $user['id'] !== \App\Services\AuthService::id()): ?>
                      <?php if (!in_array($user['role'], ['admin', 'moderator'], true)): ?>
                        <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/users/change-plan', ENT_QUOTES, 'UTF-8') ?>">
                          <?= \App\Core\Csrf::field() ?>
                          <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                          <input type="hidden" name="plan" value="<?= $currentPlan === 'premium' ? 'free' : 'premium' ?>">
                          <button class="iw-btn <?= $currentPlan === 'premium' ? '' : 'ok' ?>" type="submit" onclick="return confirm('Are you sure you want to <?= $currentPlan === 'premium' ? 'move this user to Free plan' : 'upgrade this user to Premium' ?>?')">
                            <i data-lucide="<?= $currentPlan === 'premium' ? 'chevrons-down' : 'award' ?>"></i>
                            <?= $currentPlan === 'premium' ? 'Free' : 'Premium' ?>
                          </button>
                        </form>
                      <?php endif; ?>
                      <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/users/reset-role', ENT_QUOTES, 'UTF-8') ?>">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                        <button class="iw-btn icon" type="submit" aria-label="Reset role to User" title="Reset role to User" onclick="return confirm('Reset this user role to User?')"><i data-lucide="user-check"></i></button>
                      </form>
                      <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/users/delete', ENT_QUOTES, 'UTF-8') ?>">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                        <button class="iw-btn icon danger" type="submit" aria-label="Delete user" title="Delete user" onclick="return confirm('Delete this user account?')"><i data-lucide="trash-2"></i></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($users === []): ?>
              <tr class="iw-empty"><td colspan="9">No users found.</td></tr>
            <?php endif; ?>
            <tr class="iw-empty" id="iwUserNoMatch" hidden><td colspan="9">No users match your search.</td></tr>
          </tbody>
        </table>
      </div>

      <footer class="iw-footer admin-pro-footer">
        <span><?= htmlspecialchars('User directory refreshed ' . date('d M Y'), ENT_QUOTES, 'UTF-8') ?></span>
        <span><a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>">Manage user settings</a></span>
      </footer>
    </div>
  </section>
</div>

<script>
(function () {
  var rows = Array.prototype.slice.call(document.querySelectorAll('#iwUserRows tr[data-plan]'));
  var noMatch = document.getElementById('iwUserNoMatch');
  var search = document.getElementById('iwUserSearch');
  var filters = document.getElementById('iwUserFilters');
  var active = 'all';
  function apply() {
    var q = (search.value || '').toLowerCase().trim();
    var shown = 0;
    rows.forEach(function (row) {
      var okFilter = active === 'all' ||
        row.getAttribute('data-plan') === active ||
        (active === 'unverified' && row.getAttribute('data-verified') === '0');
      var okSearch = q === '' || row.getAttribute('data-search').indexOf(q) !== -1;
      row.hidden = !(okFilter && okSearch);
      if (!row.hidden) shown++;
    });
    noMatch.hidden = !(rows.length > 0 && shown === 0);
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