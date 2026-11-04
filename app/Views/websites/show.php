<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$liveUrl = (string) ($liveUrl ?? '');
$websiteId = (int) $website['id'];
$templateKey = strtolower((string) ($website['template_key'] ?? 'blank'));
$managedContentType = !\App\Services\WebsiteEditingPolicy::canUseFileManager($website);
$isSystemTemplate = !empty($website['is_system_template']);
$status = (string) ($website['status'] ?? 'draft');
$e = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    return number_format($bytes / 1048576, 2) . ' MB';
};
$editUrl = match ($templateKey) {
    'portfolio' => $baseUrl . '/dashboard/websites/portfolio?id=' . $websiteId,
    'business' => $baseUrl . '/dashboard/websites/business?id=' . $websiteId,
    default => $baseUrl . '/dashboard/websites/edit?id=' . $websiteId,
};
$editLabel = match ($templateKey) {
    'portfolio' => 'Edit portfolio',
    'business' => 'Customize website',
    default => 'Edit details',
};
$previewUrl = $baseUrl . '/dashboard/websites/preview?id=' . $websiteId;
$filesUrl = $baseUrl . '/dashboard/websites/files?id=' . $websiteId;
$codeUrl = $baseUrl . '/dashboard/websites/editor?id=' . $websiteId . '&path=index.html';
$templateLabel = ucfirst(str_replace(['-', '_'], ' ', $templateKey));
$hasLiveUrl = $liveUrl !== '';
// Note: the draft folder path is a server detail, so it is intentionally not shown to the site owner.
?>
<style>
.iw-website-detail{--wd-ink:#14201A;--wd-muted:#5B675F;--wd-line:#E4EAE5;--wd-soft:#F6F9F6;--wd-green:#1F7A4D;--wd-green-dark:#14532D;--wd-deep:#12302A;--wd-lime:#BDE95F;--wd-ok:#14532D;--wd-ok-soft:#DCF3E4;--wd-warn:#6B4A00;--wd-warn-soft:#FDF0C9;--wd-danger:#A32A21;color:var(--wd-ink)}
.iw-website-detail *{box-sizing:border-box}
.iw-website-detail a{text-decoration:none}
.iw-website-detail .wd-header{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.25rem}
.iw-website-detail .wd-eyebrow{margin:0 0 .5rem;font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--wd-green)}
.iw-website-detail h1{margin:0;font-size:clamp(1.7rem,3vw,2.3rem);font-weight:800;letter-spacing:-.03em;overflow-wrap:anywhere;display:flex;flex-wrap:wrap;align-items:center;gap:.6rem}
.iw-website-detail .wd-url{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin:.6rem 0 0;color:var(--wd-muted);font-size:.92rem}
.iw-website-detail .wd-url a{color:var(--wd-green);font-weight:700;overflow-wrap:anywhere}
.iw-website-detail .wd-url a:hover{color:var(--wd-green-dark);text-decoration:underline}
.iw-website-detail .wd-url button{min-height:36px;padding:0 .8rem;border:1px solid #D0D9D2;border-radius:999px;background:#fff;color:#26382E;font:700 .78rem/1.2 inherit;font-family:inherit;cursor:pointer}
.iw-website-detail .wd-url button:hover{background:var(--wd-soft)}
.iw-website-detail .wd-pill{display:inline-flex;align-items:center;gap:.4rem;padding:.25rem .7rem;border-radius:999px;background:#EEF2EF;color:#3F4B44;font-size:.78rem;font-weight:700;text-transform:capitalize;vertical-align:middle}
.iw-website-detail .wd-pill:before{content:'';width:.5rem;height:.5rem;border-radius:50%;background:currentColor}
.iw-website-detail .wd-pill[data-status="published"]{background:var(--wd-ok-soft);color:var(--wd-ok)}
.iw-website-detail .wd-pill[data-status="draft"]{background:var(--wd-warn-soft);color:var(--wd-warn)}
.iw-website-detail .wd-pill[data-status="suspended"]{background:#FBE0DE;color:#8A1C14}
.iw-website-detail .wd-banner{display:flex;gap:.7rem;align-items:flex-start;margin-bottom:1.25rem;padding:.9rem 1.1rem;border:1px solid #CFE5D8;border-radius:14px;background:#F2F8F4;color:#26382E;font-size:.9rem}
.iw-website-detail .wd-banner svg{flex:none;width:1.2rem;height:1.2rem;color:var(--wd-green)}

.iw-website-detail .btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:12px;font-weight:600;font-size:.9rem}
.iw-website-detail .btn svg{width:1rem;height:1rem}
.iw-website-detail .btn-primary{background:var(--wd-green);border-color:var(--wd-green);color:#fff}
.iw-website-detail .btn-primary:hover{background:var(--wd-green-dark);border-color:var(--wd-green-dark);color:#fff}
.iw-website-detail .btn-outline-primary{border-color:var(--wd-green);color:var(--wd-green-dark);background:#fff}
.iw-website-detail .btn-outline-primary:hover{background:var(--wd-green);border-color:var(--wd-green);color:#fff}
.iw-website-detail .btn-outline-secondary{border-color:#D0D9D2;color:#26382E;background:#fff}
.iw-website-detail .btn-outline-secondary:hover{background:var(--wd-soft);color:var(--wd-ink);border-color:#D0D9D2}
.iw-website-detail .btn-outline-danger{border-color:#D9B4B0;color:var(--wd-danger);background:#fff}
.iw-website-detail .btn-outline-danger:hover{background:var(--wd-danger);border-color:var(--wd-danger);color:#fff}

.iw-website-detail .wd-panel{background:#fff;border:1px solid var(--wd-line);border-radius:20px;margin-bottom:1.25rem}
.iw-website-detail .wd-status{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1.25rem;padding:clamp(1.1rem,2.5vw,1.6rem);border-left:6px solid #C4D0C8}
.iw-website-detail .wd-status[data-status="published"]{border-left-color:var(--wd-green);background:linear-gradient(105deg,#fff 0%,#F2F8F4 100%)}
.iw-website-detail .wd-status[data-status="draft"]{border-left-color:#E0A21B;background:linear-gradient(105deg,#fff 0%,#FFFAEB 100%)}
.iw-website-detail .wd-status[data-status="suspended"]{border-left-color:var(--wd-danger);background:linear-gradient(105deg,#fff 0%,#FEF3F2 100%)}
.iw-website-detail .wd-status h2{margin:.5rem 0 .25rem;font-size:1.3rem;font-weight:800;letter-spacing:-.02em}
.iw-website-detail .wd-status p{margin:0;max-width:34rem;color:var(--wd-muted);font-size:.92rem;line-height:1.55}
.iw-website-detail .wd-actions{display:flex;flex-wrap:wrap;gap:.6rem}
.iw-website-detail .wd-actions form{margin:0}

.iw-website-detail .wd-section-title{margin:1.75rem 0 .75rem;font-size:1.05rem;font-weight:800}
.iw-website-detail .wd-tools{display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.9rem;margin-bottom:.5rem}
.iw-website-detail .wd-tool{display:flex;flex-direction:column;gap:.4rem;padding:1.15rem;border:1px solid var(--wd-line);border-radius:16px;background:#fff;color:inherit;transition:border-color .16s ease,box-shadow .16s ease,transform .16s ease}
.iw-website-detail .wd-tool:hover{transform:translateY(-2px);border-color:var(--wd-green);box-shadow:0 .7rem 1.4rem rgba(20,83,45,.1);color:inherit}
.iw-website-detail .wd-tool svg{width:1.4rem;height:1.4rem;color:var(--wd-green)}
.iw-website-detail .wd-tool strong{font-size:1rem}
.iw-website-detail .wd-tool span{color:var(--wd-muted);font-size:.86rem;line-height:1.5}
.iw-website-detail .wd-tool--primary{background:var(--wd-deep);border-color:var(--wd-deep);color:#fff}
.iw-website-detail .wd-tool--primary:hover{background:#0B1F1B;color:#fff;border-color:#0B1F1B}
.iw-website-detail .wd-tool--primary span{color:#D5E3DA}
.iw-website-detail .wd-tool--primary svg{color:var(--wd-lime)}
.iw-website-detail .wd-note{margin:.5rem 0 0;color:var(--wd-muted);font-size:.88rem}

.iw-website-detail .wd-details{margin:0;padding:.35rem 1.4rem;display:grid}
.iw-website-detail .wd-details>div{display:grid;grid-template-columns:11rem minmax(0,1fr);gap:1rem;padding:.95rem 0;border-top:1px solid var(--wd-line)}
.iw-website-detail .wd-details>div:first-child{border-top:0}
.iw-website-detail .wd-details dt{margin:0;color:var(--wd-muted);font-size:.86rem;font-weight:600}
.iw-website-detail .wd-details dd{margin:0;font-weight:600;overflow-wrap:anywhere}

.iw-website-detail .wd-danger{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding:1.15rem 1.4rem;border-color:#F0C9C5;background:#FFFBFA}
.iw-website-detail .wd-danger h3{margin:0 0 .25rem;font-size:1rem;font-weight:800;color:var(--wd-danger)}
.iw-website-detail .wd-danger p{margin:0;color:var(--wd-muted);font-size:.88rem;max-width:34rem}
.iw-website-detail .wd-danger form{margin:0}

.iw-website-detail a:focus-visible,.iw-website-detail button:focus-visible{outline:3px solid rgba(31,122,77,.4);outline-offset:2px}
@media(max-width:640px){
  .iw-website-detail .wd-details>div{grid-template-columns:minmax(0,1fr);gap:.25rem}
  .iw-website-detail .wd-header>.btn,.iw-website-detail .wd-actions,.iw-website-detail .wd-actions .btn,.iw-website-detail .wd-actions form{width:100%}
  .iw-website-detail .wd-danger .btn{width:100%}
}
@media(prefers-reduced-motion:reduce){.iw-website-detail *{transition:none}}
</style>

<section class="client-dashboard iw-client-page iw-website-detail">
  <div class="client-main">
    <header class="wd-header">
      <div>
        <p class="wd-eyebrow">Project summary</p>
        <h1><?= $e($website['website_name'] ?: 'Website') ?> <span class="wd-pill" data-status="<?= $e($status) ?>"><?= $e($status) ?></span></h1>
        <?php if ($hasLiveUrl && $status === 'published'): ?>
          <p class="wd-url"><a href="<?= $e($liveUrl) ?>" target="_blank" rel="noopener"><?= $e($liveUrl) ?></a><button type="button" id="wd-copy" data-copy-url="<?= $e($liveUrl) ?>" aria-live="polite">Copy link</button></p>
        <?php elseif ($status === 'draft'): ?>
          <p class="wd-url">Draft: only you can see it until you publish.</p>
        <?php else: ?>
          <p class="wd-url">This website is not currently available publicly.</p>
        <?php endif; ?>
      </div>
      <a class="btn btn-primary" href="<?= $e($editUrl) ?>"><i data-lucide="<?= $templateKey === 'portfolio' ? 'panels-top-left' : 'pencil' ?>"></i> <?= $e($editLabel) ?></a>
    </header>

    <?php if ($isSystemTemplate): ?>
      <div class="wd-banner" role="status"><i data-lucide="shield-check" aria-hidden="true"></i><span>This is the protected default portfolio template. It stays published and cannot be deleted.</span></div>
    <?php endif; ?>

    <!-- What to do next -->
    <section class="wd-panel wd-status" data-status="<?= $e($status) ?>" aria-labelledby="wd-status-title">
      <div>
        <?php if ($status === 'published'): ?>
          <span class="wd-pill" data-status="published">Live</span>
          <h2 id="wd-status-title">Your website is live</h2>
          <p>Visitors can open it right now. Changes you publish appear on the live site.</p>
        <?php elseif ($status === 'draft'): ?>
          <span class="wd-pill" data-status="draft">Draft</span>
          <h2 id="wd-status-title">Ready to go public?</h2>
          <p>Preview it first to check how it looks, then publish when you are happy.</p>
        <?php elseif ($status === 'suspended'): ?>
          <span class="wd-pill" data-status="suspended">Suspended</span>
          <h2 id="wd-status-title">This website is suspended</h2>
          <p role="status">An administrator suspended this website, so it cannot be published.</p>
        <?php else: ?>
          <span class="wd-pill" data-status="<?= $e($status) ?>"><?= $e($status) ?></span>
          <h2 id="wd-status-title">This website needs attention</h2>
          <p>It is not currently available publicly.</p>
        <?php endif; ?>
      </div>
      <div class="wd-actions">
        <?php if ($status === 'draft'): ?>
          <a class="btn btn-outline-primary" href="<?= $e($previewUrl) ?>"><i data-lucide="eye"></i> Preview</a>
          <form method="POST" action="<?= $e($baseUrl . '/dashboard/websites/publish') ?>">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="website_id" value="<?= $websiteId ?>">
            <button class="btn btn-primary" type="submit"><i data-lucide="rocket"></i> Publish website</button>
          </form>
        <?php elseif ($status === 'published'): ?>
          <?php if ($hasLiveUrl): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= $e($liveUrl) ?>"><i data-lucide="external-link"></i> Open live site</a>
            <button type="button" class="btn btn-outline-secondary btn-share-site" data-share-url="<?= $e($liveUrl) ?>"><i data-lucide="share-2"></i> Share</button>
          <?php endif; ?>
          <?php if (!$isSystemTemplate): ?>
            <form method="POST" action="<?= $e($baseUrl . '/dashboard/websites/unpublish') ?>" onsubmit="return confirm('Unpublish this website? Visitors will no longer be able to see it.');">
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="website_id" value="<?= $websiteId ?>">
              <button class="btn btn-outline-danger" type="submit"><i data-lucide="cloud-off"></i> Unpublish</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- Tools -->
    <h2 class="wd-section-title">Work on this website</h2>
    <div class="wd-tools">
      <a class="wd-tool wd-tool--primary" href="<?= $e($editUrl) ?>">
        <i data-lucide="<?= $templateKey === 'portfolio' ? 'panels-top-left' : 'pencil' ?>" aria-hidden="true"></i>
        <strong><?= $e($editLabel) ?></strong>
        <span><?= $templateKey === 'portfolio' ? 'Set up your profile, choose a design and add your work.' : ($templateKey === 'business' ? 'Edit your business information, services and contact details.' : 'Update the project name and settings.') ?></span>
      </a>
      <a class="wd-tool" href="<?= $e($previewUrl) ?>">
        <i data-lucide="eye" aria-hidden="true"></i>
        <strong>Preview</strong>
        <span>See the draft as visitors will.</span>
      </a>
      <?php if (!$managedContentType): ?>
        <a class="wd-tool" href="<?= $e($filesUrl) ?>">
          <i data-lucide="upload-cloud" aria-hidden="true"></i>
          <strong>File manager</strong>
          <span>Upload, organise and download your files.</span>
        </a>
        <a class="wd-tool" href="<?= $e($codeUrl) ?>">
          <i data-lucide="code-2" aria-hidden="true"></i>
          <strong>Code editor</strong>
          <span>Edit your HTML, CSS and JavaScript directly.</span>
        </a>
      <?php endif; ?>
    </div>
    <?php if ($managedContentType): ?>
      <p class="wd-note">This website uses a managed system template. Edit its content through the dedicated customization experience; raw files and source code are not available.</p>
    <?php endif; ?>

    <!-- Details -->
    <h2 class="wd-section-title">Project details</h2>
    <section class="wd-panel">
      <dl class="wd-details">
        <div><dt>Website address name</dt><dd><?= $e($website['slug'] ?? '') ?></dd></div>
        <div><dt>Type</dt><dd><?= $e($templateLabel) ?></dd></div>
        <div><dt>Storage used</dt><dd><?= $e($formatBytes((int) ($website['storage_used'] ?? 0))) ?> of draft files</dd></div>
        <div><dt>Expires</dt><dd><?= $e($website['expires_at'] ?: 'No expiry') ?></dd></div>
      </dl>
    </section>

    <?php if (!$isSystemTemplate): ?>
      <section class="wd-panel wd-danger" aria-labelledby="wd-danger-title">
        <div>
          <h3 id="wd-danger-title">Delete this website</h3>
          <p>Permanently removes the project and its draft files. This cannot be undone.</p>
        </div>
        <form method="POST" action="<?= $e($baseUrl . '/dashboard/websites/delete') ?>" onsubmit="return confirm('Delete this website project and draft files? This cannot be undone.');">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="id" value="<?= $websiteId ?>">
          <button class="btn btn-outline-danger" type="submit"><i data-lucide="trash-2"></i> Delete website</button>
        </form>
      </section>
    <?php endif; ?>
  </div>
</section>
<script>
(function () {
  var button = document.getElementById('wd-copy');
  if (!button || !navigator.clipboard) { if (button) button.hidden = true; return; }
  button.addEventListener('click', function () {
    navigator.clipboard.writeText(button.dataset.copyUrl).then(function () {
      button.textContent = 'Copied';
      setTimeout(function () { button.textContent = 'Copy link'; }, 1800);
    });
  });
})();
</script>