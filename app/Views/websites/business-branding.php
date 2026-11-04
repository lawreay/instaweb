<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$siteId = (int) $website['id'];
$logoUrl = !empty($branding['logo_path']) ? $baseUrl . '/dashboard/websites/preview/file?id=' . $siteId . '&path=' . rawurlencode($branding['logo_path']) : '';
$faviconUrl = !empty($branding['favicon_path']) ? $baseUrl . '/dashboard/websites/preview/file?id=' . $siteId . '&path=' . rawurlencode($branding['favicon_path']) : '';
?>
<style>
.business-editor{--be-ink:#14201A;--be-muted:#5B675F;--be-line:#E4EAE5;--be-soft:#F6F9F6;--be-green:#1F7A4D;--be-green-dark:#14532D;--be-danger:#A32A21;max-width:1050px;margin:auto;color:var(--be-ink)}
.business-editor *{box-sizing:border-box}
.business-editor a{text-decoration:none}
.business-editor .be-head{display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1.4rem}
.business-editor .be-eyebrow{margin:0 0 .5rem;font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--be-green)}
.business-editor h1{margin:0;font-size:clamp(1.7rem,3vw,2.2rem);font-weight:800;letter-spacing:-.03em}
.business-editor .be-muted{color:var(--be-muted);font-size:.92rem;margin:.4rem 0 0}
.business-editor .btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:12px;font-weight:600;font-size:.9rem}
.business-editor .btn-primary{background:var(--be-green);border-color:var(--be-green);color:#fff}
.business-editor .btn-primary:hover{background:var(--be-green-dark);border-color:var(--be-green-dark);color:#fff}
.business-editor .btn-outline-secondary{border:1px solid #D0D9D2;color:#26382E;background:#fff}
.business-editor .btn-outline-secondary:hover{background:var(--be-soft);color:var(--be-ink)}
.business-editor .be-grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,22rem),1fr));gap:1.1rem;margin-bottom:1.1rem}
.business-editor .be-card{display:flex;flex-direction:column;gap:.9rem;background:#fff;border:1px solid var(--be-line);border-radius:20px;padding:clamp(1.1rem,3vw,1.6rem)}
.business-editor .be-card h2{margin:0;font-size:1.1rem;font-weight:800}
.business-editor .be-help{margin:0;font-size:.86rem;color:var(--be-muted);line-height:1.5}
.business-editor .be-preview{display:flex;align-items:center;justify-content:center;min-height:120px;padding:1rem;border:1.5px dashed #C4D0C8;border-radius:14px;background:var(--be-soft);color:var(--be-muted);font-size:.88rem;text-align:center}
.business-editor .be-preview.has-image{border-style:solid;border-color:var(--be-line);background:#fff}
.business-editor .be-preview img{display:block;object-fit:contain}
.business-editor .be-preview img.logo{max-width:220px;max-height:90px}
.business-editor .be-preview img.favicon{width:48px;height:48px}
.business-editor .be-file{display:grid;gap:.45rem;font-size:.86rem;font-weight:700;color:#26382E}
.business-editor .be-file input[type=file]{width:100%;min-height:46px;padding:.45rem;border:1px solid #D0D9D2;border-radius:10px;background:#fff;font:inherit;font-weight:400;font-size:.9rem;color:var(--be-ink)}
.business-editor .be-file input[type=file]::file-selector-button{min-height:36px;margin-right:.8rem;padding:0 .9rem;border:0;border-radius:8px;background:#E3F2E8;color:var(--be-green-dark);font:inherit;font-weight:700;cursor:pointer}
.business-editor .be-file input[type=file]:focus-visible{outline:3px solid rgba(31,122,77,.4);outline-offset:2px}
.business-editor .be-warn{margin:0;font-size:.86rem;font-weight:600;color:var(--be-danger)}
.business-editor .be-warn[hidden]{display:none}
.business-editor .be-actions{position:sticky;bottom:0;z-index:5;display:flex;flex-wrap:wrap;gap:.7rem;justify-content:flex-end;padding:1rem 0 1.2rem;background:linear-gradient(to top,#fff 55%,rgba(255,255,255,0))}
.business-editor a:focus-visible,.business-editor button:focus-visible{outline:3px solid rgba(31,122,77,.4);outline-offset:2px}
@media(max-width:650px){.business-editor .be-head>.btn{width:100%}.business-editor .be-actions>*{flex:1 1 100%}}
</style>
<section class="client-dashboard iw-client-page business-editor">
    <?php $editorActive = 'branding'; require __DIR__ . '/partials/editor-navigation.php'; ?>
    <header class="be-head">
        <div>
            <p class="be-eyebrow">Business website / Identity</p>
            <h1>Branding</h1>
            <p class="be-muted">Add your business logo and browser favicon.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= $e($baseUrl . '/dashboard/websites/business?id=' . $siteId) ?>">Back to content</a>
    </header>
    <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl . '/dashboard/websites/business/branding/save') ?>">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="id" value="<?= $siteId ?>">
        <div class="be-grid2">
            <section class="be-card">
                <h2>Business logo</h2>
                <p class="be-help">Shown on your website. JPEG, PNG or WebP, max 3 MB.</p>
                <div class="be-preview <?= $logoUrl !== '' ? 'has-image' : '' ?>" data-preview="logo">
                    <?php if ($logoUrl !== ''): ?>
                        <img class="logo" src="<?= $e($logoUrl) ?>" alt="Current business logo">
                    <?php else: ?>
                        <span>No logo uploaded yet</span>
                    <?php endif; ?>
                </div>
                <label class="be-file">Upload logo (JPEG, PNG or WebP, max 3 MB)
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" data-preview-target="logo" data-max-mb="3">
                </label>
                <p class="be-warn" data-warn="logo" role="alert" hidden>This file is larger than 3 MB, so it may be rejected.</p>
            </section>
            <section class="be-card">
                <h2>Browser favicon</h2>
                <p class="be-help">The small icon shown in the browser tab.</p>
                <div class="be-preview <?= $faviconUrl !== '' ? 'has-image' : '' ?>" data-preview="favicon">
                    <?php if ($faviconUrl !== ''): ?>
                        <img class="favicon" src="<?= $e($faviconUrl) ?>" alt="Current favicon">
                    <?php else: ?>
                        <span>No favicon uploaded yet</span>
                    <?php endif; ?>
                </div>
                <label class="be-file">Upload favicon (square image recommended, max 3 MB and 1024 × 1024 pixels)
                    <input type="file" name="favicon" accept="image/jpeg,image/png,image/webp" data-preview-target="favicon" data-max-mb="3">
                </label>
                <p class="be-warn" data-warn="favicon" role="alert" hidden>This file is larger than 3 MB, so it may be rejected.</p>
            </section>
        </div>
        <div class="be-actions">
            <button class="btn btn-primary" type="submit">Save branding</button>
        </div>
    </form>
    <p class="be-muted">Changes update your private draft. Publish the website to make them visible publicly.</p>
</section>
<script>
(function () {
    document.querySelectorAll('input[type=file][data-preview-target]').forEach(function (input) {
        var key = input.getAttribute('data-preview-target');
        var box = document.querySelector('[data-preview="' + key + '"]');
        var warn = document.querySelector('[data-warn="' + key + '"]');
        var maxBytes = (parseFloat(input.getAttribute('data-max-mb')) || 3) * 1048576;
        var objectUrl = null;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (warn) warn.hidden = !(file && file.size > maxBytes);
            if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }
            if (!file || !box || !/^image\//.test(file.type)) return;
            objectUrl = URL.createObjectURL(file);
            box.classList.add('has-image');
            box.innerHTML = '';
            var img = document.createElement('img');
            img.className = key === 'favicon' ? 'favicon' : 'logo';
            img.alt = 'Selected ' + (key === 'favicon' ? 'favicon' : 'logo') + ' preview (not saved yet)';
            img.src = objectUrl;
            box.appendChild(img);
        });
    });
})();
</script>