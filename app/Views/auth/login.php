<?php $errors = $errors ?? []; ?>
<?php $old = $old ?? []; ?>
<?php $baseUrl = (string) ($app['base_url'] ?? ''); ?>
<section class="auth-shell lg">
  <style>
    .lg { --lg-ink:#14201A; --lg-muted:#5B675F; --lg-line:#E4EAE5; --lg-green:#1F7A4D; --lg-green-dark:#14532D; --lg-deep:#12302A; --lg-lime:#BDE95F; padding:32px 16px; }
    .lg .lg-card { max-width:960px; margin:0 auto; display:grid; grid-template-columns:minmax(0,5fr) minmax(0,5fr); background:#fff; border:1px solid var(--lg-line); border-radius:24px; overflow:hidden; box-shadow:0 18px 50px rgba(18,48,42,.08); }
    .lg .lg-side { background:var(--lg-deep); color:#fff; padding:44px 36px; display:flex; flex-direction:column; justify-content:center; gap:20px; }
    .lg .lg-eyebrow { margin:0; display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--lg-lime); }
    .lg .lg-eyebrow::before { content:""; width:8px; height:8px; border-radius:50%; background:var(--lg-lime); }
    .lg .lg-side h1 { margin:0; font-size:clamp(28px,3.4vw,38px); font-weight:800; letter-spacing:-.02em; line-height:1.15; color:#fff; }
    .lg .lg-side p.lg-lede { margin:0; font-size:15px; line-height:1.6; color:#D5E3DA; }
    .lg .lg-points { list-style:none; margin:4px 0 0; padding:0; display:flex; flex-direction:column; gap:10px; }
    .lg .lg-points li { display:flex; align-items:center; gap:10px; font-size:14px; color:#E4EEE8; }
    .lg .lg-points li::before { content:"✓"; color:var(--lg-lime); font-weight:700; }
    .lg .lg-side-links { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-top:8px; }
    .lg .lg-side-links .btn { min-height:44px; border-radius:12px; font-weight:600; background:var(--lg-lime); border-color:var(--lg-lime); color:#10230F; display:inline-flex; align-items:center; }
    .lg .lg-side-links .btn:hover { background:#A9DB45; border-color:#A9DB45; color:#10230F; }
    .lg .lg-side-links a.lg-home { min-height:44px; display:inline-flex; align-items:center; color:#D5E3DA; font-size:14px; text-decoration:underline; text-underline-offset:3px; }
    .lg .lg-side-links a.lg-home:hover { color:#fff; }
    .lg .lg-form { padding:44px 40px; display:flex; flex-direction:column; justify-content:center; }
    .lg .lg-brand { margin-bottom:24px; }
    .lg .lg-brand h2 { margin:0; font-size:28px; font-weight:800; letter-spacing:-.02em; color:var(--lg-ink); }
    .lg .lg-brand p { margin:4px 0 0; font-size:14px; color:var(--lg-muted); }
    .lg .form-label { font-size:13px; font-weight:600; color:#26382E; margin-bottom:6px; }
    .lg .form-control { min-height:46px; border-radius:10px; border:1px solid #D0D9D2; font-size:15px; color:var(--lg-ink); }
    .lg .form-control:focus { border-color:var(--lg-green); box-shadow:0 0 0 3px rgba(31,122,77,.18); }
    .lg .form-control.is-invalid { border-color:#C0352B; }
    .lg .invalid-feedback { font-size:13px; color:#A32A21; margin-top:6px; }
    .lg .form-check-input { width:18px; height:18px; margin-top:0; accent-color:var(--lg-green); }
    .lg .form-check { display:flex; align-items:center; gap:10px; padding-left:0; min-height:44px; }
    .lg .form-check-label { font-size:14px; color:#26382E; }
    .lg .lg-row a { color:var(--lg-green); font-weight:600; font-size:14px; min-height:44px; display:inline-flex; align-items:center; }
    .lg .lg-submit { min-height:48px; border-radius:12px; font-weight:700; font-size:15px; background:var(--lg-green); border-color:var(--lg-green); }
    .lg .lg-submit:hover { background:var(--lg-green-dark); border-color:var(--lg-green-dark); }
    .lg .lg-alt { text-align:center; margin:20px 0 0; font-size:14px; color:var(--lg-muted); }
    .lg .lg-alt a { color:var(--lg-green); font-weight:600; }
    @media (max-width: 860px) {
      .lg .lg-card { grid-template-columns:1fr; }
      .lg .lg-side { padding:28px 24px; }
      .lg .lg-points { display:none; }
      .lg .lg-form { padding:28px 20px; }
    }
  </style>

  <div class="lg-card auth-split">
    <div class="lg-side auth-side">
      <p class="lg-eyebrow">Secure access</p>
      <h1>Welcome back</h1>
      <p class="lg-lede">Manage your websites, files, subscriptions, and analytics from one secure dashboard.</p>
      <ul class="lg-points">
        <li>Websites and files</li>
        <li>Subscriptions</li>
        <li>Analytics</li>
      </ul>
      <div class="lg-side-links">
        <a class="btn" href="<?= htmlspecialchars($baseUrl . '/register', ENT_QUOTES, 'UTF-8') ?>">Create account</a>
        <a class="lg-home" href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">Back to home</a>
      </div>
    </div>

    <div class="lg-form auth-form-panel">
      <div class="lg-brand">
        <h2>Instaweb</h2>
        <p>Sign in to continue</p>
      </div>

      <form method="POST" action="<?= htmlspecialchars($baseUrl . '/login', ENT_QUOTES, 'UTF-8') ?>" novalidate>
        <?= \App\Core\Csrf::field() ?>
        <div class="mb-3">
          <label class="form-label" for="login">Email or username</label>
          <input class="form-control <?= !empty($errors['login']) ? 'is-invalid' : '' ?>" id="login" name="login" autocomplete="username" value="<?= htmlspecialchars($old['login'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          <?php foreach ($errors['login'] ?? [] as $error): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endforeach; ?>
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" type="password" autocomplete="current-password" required>
          <?php foreach ($errors['password'] ?? [] as $error): ?>
            <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
          <?php endforeach; ?>
        </div>

        <div class="lg-row d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
          <div class="form-check">
            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
            <label class="form-check-label" for="remember">Remember me</label>
          </div>
          <a href="<?= htmlspecialchars($baseUrl . '/forgot-password', ENT_QUOTES, 'UTF-8') ?>">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary lg-submit w-100">Login</button>
      </form>

      <p class="lg-alt">
        Need an account?
        <a href="<?= htmlspecialchars($baseUrl . '/register', ENT_QUOTES, 'UTF-8') ?>">Create one</a>
      </p>
    </div>
  </div>
</section>