<?php $errors = $errors ?? []; ?>
<?php $old = $old ?? []; ?>
<?php $registrationEnabled = $registrationEnabled ?? true; ?>
<?php $baseUrl = (string) ($app['base_url'] ?? ''); ?>
<section class="auth-shell rg">
  <style>
    .rg { --rg-ink:#14201A; --rg-muted:#5B675F; --rg-line:#E4EAE5; --rg-green:#1F7A4D; --rg-green-dark:#14532D; --rg-deep:#12302A; --rg-lime:#BDE95F; padding:32px 16px; }
    .rg .rg-card { max-width:1040px; margin:0 auto; display:grid; grid-template-columns:minmax(0,5fr) minmax(0,7fr); background:#fff; border:1px solid var(--rg-line); border-radius:24px; overflow:hidden; box-shadow:0 18px 50px rgba(18,48,42,.08); }
    .rg .rg-side { background:var(--rg-deep); color:#fff; padding:44px 36px; display:flex; flex-direction:column; justify-content:center; gap:20px; }
    .rg .rg-eyebrow { margin:0; display:inline-flex; align-items:center; gap:8px; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--rg-lime); }
    .rg .rg-eyebrow::before { content:""; width:8px; height:8px; border-radius:50%; background:var(--rg-lime); }
    .rg .rg-side h1 { margin:0; font-size:clamp(28px,3.4vw,38px); font-weight:800; letter-spacing:-.02em; line-height:1.15; color:#fff; }
    .rg .rg-side p.rg-lede { margin:0; font-size:15px; line-height:1.6; color:#D5E3DA; }
    .rg .rg-steps { list-style:none; margin:4px 0 0; padding:0; display:flex; flex-direction:column; gap:12px; counter-reset:step; }
    .rg .rg-steps li { counter-increment:step; display:flex; align-items:center; gap:12px; font-size:14px; color:#E4EEE8; }
    .rg .rg-steps li::before { content:counter(step); width:28px; height:28px; flex:none; border-radius:50%; background:rgba(189,233,95,.18); color:var(--rg-lime); font-weight:700; font-size:13px; display:inline-flex; align-items:center; justify-content:center; }
    .rg .rg-side-links { display:flex; align-items:center; gap:16px; flex-wrap:wrap; margin-top:8px; }
    .rg .rg-side-links .btn { min-height:44px; border-radius:12px; font-weight:600; background:var(--rg-lime); border-color:var(--rg-lime); color:#10230F; display:inline-flex; align-items:center; }
    .rg .rg-side-links .btn:hover { background:#A9DB45; border-color:#A9DB45; color:#10230F; }
    .rg .rg-side-links a.rg-home { min-height:44px; display:inline-flex; align-items:center; color:#D5E3DA; font-size:14px; text-decoration:underline; text-underline-offset:3px; }
    .rg .rg-side-links a.rg-home:hover { color:#fff; }
    .rg .rg-form { padding:44px 40px; }
    .rg .rg-brand { margin-bottom:24px; }
    .rg .rg-brand h2 { margin:0; font-size:28px; font-weight:800; letter-spacing:-.02em; color:var(--rg-ink); }
    .rg .rg-brand p { margin:4px 0 0; font-size:14px; color:var(--rg-muted); }
    .rg .form-label { font-size:13px; font-weight:600; color:#26382E; margin-bottom:6px; }
    .rg .form-control { min-height:46px; border-radius:10px; border:1px solid #D0D9D2; font-size:15px; color:var(--rg-ink); }
    .rg .form-control:focus { border-color:var(--rg-green); box-shadow:0 0 0 3px rgba(31,122,77,.18); }
    .rg .form-control.is-invalid { border-color:#C0352B; }
    .rg .invalid-feedback { font-size:13px; color:#A32A21; margin-top:6px; }
    .rg .rg-submit { min-height:48px; border-radius:12px; font-weight:700; font-size:15px; background:var(--rg-green); border-color:var(--rg-green); }
    .rg .rg-submit:hover:not(:disabled) { background:var(--rg-green-dark); border-color:var(--rg-green-dark); }
    .rg .rg-alt { text-align:center; margin:20px 0 0; font-size:14px; color:var(--rg-muted); }
    .rg .rg-alt a { color:var(--rg-green); font-weight:600; }
    @media (max-width: 860px) {
      .rg .rg-card { grid-template-columns:1fr; }
      .rg .rg-side { padding:28px 24px; }
      .rg .rg-steps { display:none; }
      .rg .rg-form { padding:28px 20px; }
    }
  </style>

  <div class="rg-card auth-split auth-split-wide">
    <div class="rg-side auth-side">
      <p class="rg-eyebrow">Start hosting</p>
      <h1>Create your account</h1>
      <p class="rg-lede">Register in minutes, verify your email, and launch your first website with Instaweb.</p>
      <ol class="rg-steps">
        <li>Register in minutes</li>
        <li>Verify your email</li>
        <li>Launch your first website</li>
      </ol>
      <div class="rg-side-links">
        <a class="btn" href="<?= htmlspecialchars($baseUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Back to login</a>
        <a class="rg-home" href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">Back to home</a>
      </div>
    </div>

    <div class="rg-form auth-form-panel">
      <div class="rg-brand">
        <h2>Instaweb</h2>
        <p>Begin with a free account</p>
      </div>

      <?php if (!$registrationEnabled): ?>
        <div class="alert alert-warning" role="alert">Public registration is currently disabled. Please check back later or contact support.</div>
      <?php endif; ?>

      <form method="POST" action="<?= htmlspecialchars($baseUrl . '/register', ENT_QUOTES, 'UTF-8') ?>" novalidate>
        <?= \App\Core\Csrf::field() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="fullname">Full name</label>
            <input class="form-control <?= !empty($errors['fullname']) ? 'is-invalid' : '' ?>" id="fullname" name="fullname" autocomplete="name" value="<?= htmlspecialchars($old['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['fullname'] ?? [] as $error): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="username">Username</label>
            <input class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" autocomplete="username" value="<?= htmlspecialchars($old['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['username'] ?? [] as $error): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="email">Email</label>
            <input class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" autocomplete="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['email'] ?? [] as $error): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="phone">Phone number</label>
            <input class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" type="tel" autocomplete="tel" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['phone'] ?? [] as $error): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>

          <div class="col-12">
            <label class="form-label" for="country">Country</label>
            <input class="form-control <?= !empty($errors['country']) ? 'is-invalid' : '' ?>" id="country" name="country" list="country-options" autocomplete="country-name" value="<?= htmlspecialchars($old['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <datalist id="country-options">
              <option value="Malawi">
              <option value="South Africa">
              <option value="Zambia">
              <option value="Mozambique">
              <option value="Tanzania">
              <option value="Kenya">
              <option value="Nigeria">
              <option value="United States">
              <option value="United Kingdom">
            </datalist>
            <?php foreach ($errors['country'] ?? [] as $error): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="password">Password</label>
            <input class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" type="password" autocomplete="new-password" required>
            <?php foreach ($errors['password'] ?? [] as $error): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label" for="confirm_password">Confirm password</label>
            <input class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
            <?php foreach ($errors['confirm_password'] ?? [] as $error): ?>
              <div class="invalid-feedback d-block"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="submit" class="btn btn-primary rg-submit w-100 mt-4" <?= !$registrationEnabled ? 'disabled' : '' ?>>Create account</button>
      </form>

      <p class="rg-alt">
        Already registered?
        <a href="<?= htmlspecialchars($baseUrl . '/login', ENT_QUOTES, 'UTF-8') ?>">Login</a>
      </p>
    </div>
  </div>
</section>