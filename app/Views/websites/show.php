<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$liveUrl = (string) ($liveUrl ?? '');
$websiteId = (int) $website['id'];
$templateKey = strtolower((string) ($website['template_key'] ?? 'blank'));
$managedContentType = in_array($templateKey, ['wordpress', 'portfolio'], true);
$isSystemTemplate = !empty($website['is_system_template']);
$status = (string) ($website['status'] ?? 'draft');
$e = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    }

    return number_format($bytes / 1048576, 2) . ' MB';
};
$editUrl = $templateKey === 'portfolio'
    ? $baseUrl . '/dashboard/websites/portfolio?id=' . $websiteId
    : $baseUrl . '/dashboard/websites/edit?id=' . $websiteId;
$previewUrl = $baseUrl . '/dashboard/websites/preview?id=' . $websiteId;
$filesUrl = $baseUrl . '/dashboard/websites/files?id=' . $websiteId;
$codeUrl = $baseUrl . '/dashboard/websites/editor?id=' . $websiteId . '&path=index.html';
$templateLabel = ucfirst(str_replace(['-', '_'], ' ', $templateKey));
// Note: the draft folder path is a server detail, so it is intentionally not shown to the site owner.
?>
<style>
.iw-website-detail{--wd-ink:#17191d;--wd-muted:#667085;--wd-line:#e4e7ec;--wd-soft:#f8fafc;--wd-accent:#f97316;--wd-accent-ink:#9a3412;--wd-accent-soft:#fff4ea;--wd-ok:#166534;--wd-ok-soft:#e8f7ee;--wd-warn:#92400e;--wd-warn-soft:#fef3c7;--wd-danger:#b42318;color:var(--wd-ink)}
.iw-website-detail *{box-sizing:border-box}
.iw-website-detail .wd-header{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.25rem}
.iw-website-detail .wd-eyebrow{margin:0 0 .5rem;font-size:.72rem;font-weight:800;letter-spacing:.15em;text-transform:uppercase;color:var(--wd-muted)}
.iw-website-detail h1{margin:0;font-size:clamp(1.8rem,3vw,2.5rem);font-weight:800;letter-spacing:-.04em;overflow-wrap:anywhere}
.iw-website-detail .wd-url{display:flex;flex-wrap:wrap;align-items:center;gap:.5rem;margin:.6rem 0 0;color:var(--wd-muted);font-size:.92rem}
.iw-website-detail .wd-url a{color:var(--wd-accent-ink);font-weight:700;overflow-wrap:anywhere}
.iw-website-detail .wd-url button{padding:.2rem .55rem;border:1px solid #d0d5dd;border-radius:999px;background:#fff;color:#344054;font:700 .74rem/1.2 inherit;font-family:inherit;cursor:pointer}
.iw-website-detail .wd-url button:hover{background:var(--wd-soft)}
.iw-website-detail .wd-pill{display:inline-flex;align-items:center;gap:.4rem;padding:.25rem .65rem;border-radius:999px;background:#f2f4f7;color:#475467;font-size:.74rem;font-weight:800;text-transform:capitalize;vertical-align:middle}
.iw-website-detail .wd-pill:before{content:'';width:.5rem;height:.5rem;border-radius:50%;background:currentColor}
.iw-website-detail .wd-pill[data-status="published"]{background:var(--wd-ok-soft);color:var(--wd-ok)}
.iw-website-detail .wd-pill[data-status="draft"]{background:var(--wd-warn-soft);color:var(--wd-warn)}
.iw-website-detail .wd-pill[data-status="suspended"]{background:#fef3f2;color:var(--wd-danger)}
.iw-website-detail .wd-banner{display:flex;gap:.7rem;align-items:flex-start;margin-bottom:1.25rem;padding:.85rem 1rem;border:1px solid #b2ddff;border-radius:.8rem;background:#eff8ff;color:#175cd3;font-size:.88rem}

.iw-website-detail .wd-panel{background:var(--bs-body-bg,#fff);border:1px solid var(--wd-line);border-radius:1.1rem;box-shadow:0 .6rem 1.8rem rgba(15,23,42,.04);margin-bottom:1.25rem}
.iw-website-detail .wd-status{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1.25rem;padding:clamp(1.1rem,2.5vw,1.6rem)}
.iw-website-detail .wd-status[data-status="published"]{background:linear-gradient(105deg,#fff 0%,#f1fbf5 100%)}
.iw-website-detail .wd-status[data-status="draft"]{background:linear-gradient(105deg,#fff 0%,#fff8f2 100%)}
.iw-website-detail .wd-status h2{margin:.5rem 0 .25rem;font-size:1.3rem;font-weight:800;letter-spacing:-.02em}
.iw-website-detail .wd-status p{margin:0;max-width:34rem;color:var(--wd-muted);font-size:.9rem;line-height:1.55}
.iw-website-detail .wd-actions{display:flex;flex-wrap:wrap;gap:.6rem}
.iw-website-detail .wd-actions form{margin:0}

.iw-website-detail .wd-section-title{margin:1.75rem 0 .75rem;font-size:1rem;font-weight:800}
.iw-website-detail .wd-tools{display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.85rem;margin-bottom:.5rem}
.iw-website-detail .wd-tool{display:flex;flex-direction:column;gap:.35rem;padding:1.05rem;border:1px solid var(--wd-line);border-radius:1rem;background:var(--bs-body-bg,#fff);color:inherit;text-decoration:none;transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease}
.iw-website-detail .wd-tool:hover{transform:translateY(-2px);border-color:#fdba74;box-shadow:0 .7rem 1.4rem rgba(124,45,18,.1);color:inherit}
.iw-website-detail .wd-tool svg{width:1.3rem;height:1.3rem;color:var(--wd-accent)}
.iw-website-detail .wd-tool strong{font-size:.95rem}
.iw-website-detail .wd-tool span{color:var(--wd-muted);font-size:.82rem;line-height:1.45}
.iw-website-detail .wd-tool--primary{background:var(--wd-ink);border-color:var(--wd-ink);color:#fff}
.iw-website-detail .wd-tool--primary:hover{background:#2b3038;color:#fff;border-color:#2b3038}
.iw-website-detail .wd-tool--primary span{color:#d0d5dd}
.iw-website-detail .wd-tool--primary svg{color:#fdba74}
.iw-website-detail .wd-note{margin:.5rem 0 0;color:var(--wd-muted);font-size:.84rem}

.iw-website-detail .wd-details{margin:0;padding:.35rem 1.25rem;display:grid}
.iw-website-detail .wd-details>div{display:grid;grid-template-columns:10rem minmax(0,1fr);gap:1rem;padding:.9rem 0;border-top:1px solid var(--wd-line)}
.iw-website-detail .wd-details>div:first-child{border-top:0}
.iw-website-detail .wd-details dt{margin:0;color:var(--wd-muted);font-size:.84rem;font-weight:700}
.iw-website-detail .wd-details dd{margin:0;font-weight:600;overflow-wrap:anywhere}

.iw-website-detail .wd-danger{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;padding:1.1rem 1.25rem;border-color:#fecdca}
.iw-website-detail .wd-danger h3{margin:0 0 .25rem;font-size:.98rem;font-weight:800;color:var(--wd-danger)}
.iw-website-detail .wd-danger p{margin:0;color:var(--wd-muted);font-size:.84rem;max-width:34rem}
.iw-website-detail .wd-danger form{margin:0}

.iw-website-detail a:focus-visible,.iw-website-detail button:focus-visible{outline:3px solid #f9731666;outline-offset:2px}
@media(max-width:640px){
  .iw-website-detail .wd-details>div{grid-template-columns:minmax(0,1fr);gap:.25rem}
  .iw-website-detail .wd-actions,.iw-website-detail .wd-actions .btn,.iw-website-detail .wd-actions form{width:100%}
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
        <?php if ($liveUrl !== '' && $status === 'published'): ?>
          <p class="wd-url"><a href="<?= $e($liveUrl) ?>" target="_blank" rel="noopener"><?= $e($liveUrl) ?></a><button type="button" id="wd-copy" data-copy-url="<?= $e($liveUrl) ?>">Copy link</button></p>
        <?php elseif ($status === 'draft'): ?>
          <p class="wd-url">Draft: only you can see it until you publish.</p>
        <?php else: ?>
          <p class="wd-url">This website is not currently available publicly.</p>
        <?php endif; ?>
      </div>
      <a class="btn btn-primary" href="<?= $e($editUrl) ?>"><i data-lucide="<?= $templateKey === 'portfolio' ? 'panels-top-left' : 'pencil' ?>"></i> <?= $templateKey === 'portfolio' ? 'Edit portfolio' : 'Edit details' ?></a>
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
          <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= $e($liveUrl) ?>"><i data-lucide="external-link"></i> Open live site</a>
          <button type="button" class="btn btn-outline-secondary btn-share-site" data-share-url="<?= $e($liveUrl) ?>"><i data-lucide="share-2"></i> Share</button>
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
        <strong><?= $templateKey === 'portfolio' ? 'Edit portfolio' : 'Edit details' ?></strong>
        <span><?= $templateKey === 'portfolio' ? 'Set up your profile, choose a design and add your work.' : 'Update the project name and settings.' ?></span>
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
    <?php if ($templateKey === 'wordpress'): ?>
      <p class="wd-note">WordPress files are managed by WordPress. File Manager and the code editor are disabled for this project.</p>
    <?php elseif ($templateKey === 'portfolio'): ?>
      <p class="wd-note">Portfolio content is managed through the portfolio editor. File Manager and the code editor are disabled for this project.</p>
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