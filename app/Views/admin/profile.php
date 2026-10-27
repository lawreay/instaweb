<?php
$joinedAt = isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A';
$isVerified = ((int) ($user['email_verified'] ?? 0)) === 1;
$status = (string) ($user['account_status'] ?? 'pending');
$initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr((string) ($user['fullname'] ?? ''), 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr((string) ($user['fullname'] ?? ''), 0, 1));
?>
<div class="admin-pro-shell">
  <?php $active = $active ?? 'profile'; include APP_PATH . '/Views/admin/partials/sidebar.php'; ?>

  <section class="admin-pro-main iw-profile">
    <style>
      .iw-profile { --iw-ink:#14201A; --iw-muted:#5B675F; --iw-line:#E4EAE5; --iw-soft:#F6F9F6; --iw-green:#1F7A4D; --iw-green-dark:#14532D; }
      .iw-profile .iw-topbar { margin-bottom:20px; }
      .iw-profile .iw-kicker { margin:0 0 4px; font-size:12px; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:var(--iw-green); }
      .iw-profile .iw-topbar h1 { margin:0; font-size:30px; font-weight:800; letter-spacing:-.01em; color:var(--iw-ink); }
      .iw-profile .iw-hero { display:flex; align-items:center; gap:20px; flex-wrap:wrap; background:#fff; border:1px solid var(--iw-line); border-radius:20px; padding:22px 24px; margin-bottom:20px; }
      .iw-profile .iw-hero-avatar { width:72px; height:72px; flex:none; border-radius:50%; background:var(--iw-green); color:#fff; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:800; }
      .iw-profile .iw-hero-main { flex:1; min-width:220px; }
      .iw-profile .iw-hero-main h2 { margin:0; font-size:22px; font-weight:800; color:var(--iw-ink); }
      .iw-profile .iw-hero-main p { margin:2px 0 0; font-size:14px; color:var(--iw-muted); word-break:break-word; }
      .iw-profile .iw-chips { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
      .iw-profile .iw-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; text-transform:capitalize; background:#EEF2EF; color:#3F4B44; white-space:nowrap; }
      .iw-profile .iw-chip.role { background:#14201A; color:#fff; }
      .iw-profile .iw-chip.active, .iw-profile .iw-chip.verified { background:#DCF3E4; color:#14532D; }
      .iw-profile .iw-chip.pending, .iw-profile .iw-chip.unverified { background:#FDF0C9; color:#6B4A00; }
      .iw-profile .iw-chip.suspended, .iw-profile .iw-chip.banned { background:#FBE0DE; color:#8A1C14; }
      .iw-profile .iw-facts { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; flex:1 1 100%; }
      .iw-profile .iw-fact { background:var(--iw-soft); border:1px solid var(--iw-line); border-radius:12px; padding:10px 14px; min-width:0; }
      .iw-profile .iw-fact span { display:block; font-size:11px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; color:var(--iw-muted); }
      .iw-profile .iw-fact strong { display:block; font-size:14px; color:var(--iw-ink); word-break:break-word; margin-top:2px; }
      .iw-profile .iw-alert { border-radius:12px; }
      .iw-profile .iw-grid { display:grid; grid-template-columns:minmax(0,1fr) 380px; gap:20px; align-items:start; }
      .iw-profile .iw-card { background:#fff; border:1px solid var(--iw-line); border-radius:20px; padding:22px 24px; }
      .iw-profile .iw-side { display:flex; flex-direction:column; gap:20px; min-width:0; }
      .iw-profile .iw-card-head { margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid var(--iw-line); }
      .iw-profile .iw-card-head h2 { margin:0; font-size:18px; font-weight:700; color:var(--iw-ink); }
      .iw-profile .iw-card-head p { margin:4px 0 0; font-size:13px; color:var(--iw-muted); }
      .iw-profile .form-label, .iw-profile label { font-size:13px; font-weight:600; color:#26382E; }
      .iw-profile .form-control, .iw-profile .form-select, .iw-profile textarea, .iw-profile input[type="text"], .iw-profile input[type="email"], .iw-profile input[type="password"], .iw-profile input[type="tel"] { min-height:44px; border-radius:10px; border:1px solid #D8E0DA; font-size:14px; color:var(--iw-ink); }
      .iw-profile .form-control:focus, .iw-profile .form-select:focus { border-color:var(--iw-green); box-shadow:0 0 0 3px rgba(31,122,77,.15); }
      .iw-profile .btn { min-height:44px; border-radius:10px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:6px; }
      .iw-profile .btn-primary { background:var(--iw-green); border-color:var(--iw-green); }
      .iw-profile .btn-primary:hover { background:var(--iw-green-dark); border-color:var(--iw-green-dark); }
      .iw-profile .iw-footer { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; padding:16px 4px; font-size:13px; color:var(--iw-muted); }
      .iw-profile .iw-footer a { color:var(--iw-green); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; min-height:44px; }
      @media (max-width: 1100px) { .iw-profile .iw-grid { grid-template-columns:1fr; } }
      @media (max-width: 720px) { .iw-profile .iw-topbar h1 { font-size:24px; } .iw-profile .iw-card { padding:18px 16px; } }
    </style>

    <header class="iw-topbar">
      <p class="iw-kicker">User profile</p>
      <h1>My Profile</h1>
    </header>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $flashKey => $type): ?>
      <?php if ($message = \App\Core\Session::pullFlash($flashKey)): ?>
        <div class="iw-alert alert alert-<?= $type ?> shadow-sm" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
    <?php endforeach; ?>

    <div class="iw-hero">
      <div class="iw-hero-avatar" aria-hidden="true"><?= htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') ?></div>
      <div class="iw-hero-main">
        <h2><?= htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></p>
        <div class="iw-chips">
          <span class="iw-chip role"><?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="iw-chip <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="iw-chip <?= $isVerified ? 'verified' : 'unverified' ?>"><?= $isVerified ? 'Verified' : 'Unverified' ?></span>
        </div>
      </div>
      <div class="iw-facts">
        <div class="iw-fact"><span>Joined</span><strong><?= htmlspecialchars($joinedAt, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="iw-fact"><span>Username</span><strong>@<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="iw-fact"><span>Email</span><strong><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></strong></div>
      </div>
    </div>

    <div class="iw-grid">
      <section class="iw-card">
        <div class="iw-card-head">
          <h2>Account information</h2>
          <p>Keep your administrator identity and support contact details current.</p>
        </div>

        <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile/update', ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
          <?= \App\Core\Csrf::field() ?>
          <div class="row g-4">
            <div class="col-lg-4">
              <?php include APP_PATH . '/Views/partials/profile/avatar.php'; ?>
            </div>
            <div class="col-lg-8">
              <?php include APP_PATH . '/Views/partials/profile/details.php'; ?>
            </div>
          </div>
        </form>
      </section>

      <aside class="iw-side">
        <section class="iw-card">
          <div class="iw-card-head"><h2>Security</h2></div>
          <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile/change-password', ENT_QUOTES, 'UTF-8') ?>">
            <?= \App\Core\Csrf::field() ?>
            <?php include APP_PATH . '/Views/partials/profile/security.php'; ?>
          </form>
        </section>

        <section class="iw-card">
          <div class="iw-card-head"><h2>Email &amp; account</h2></div>
          <form method="POST" action="<?= htmlspecialchars(($app['base_url'] ?? '') . '/profile/change-email', ENT_QUOTES, 'UTF-8') ?>">
            <?= \App\Core\Csrf::field() ?>
            <?php include APP_PATH . '/Views/partials/profile/email.php'; ?>
          </form>
          <?php include APP_PATH . '/Views/partials/profile/deactivate.php'; ?>
        </section>
      </aside>
    </div>

    <footer class="iw-footer admin-pro-footer">
      <span>Profile details updated <?= htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8') ?></span>
      <span><a href="<?= htmlspecialchars(($app['base_url'] ?? '') . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>">Edit platform settings</a></span>
    </footer>
  </section>
</div>