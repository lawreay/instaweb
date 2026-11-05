<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$siteId = (int) $website['id'];
$formatSize = static function ($bytes): string {
    $bytes = (int) $bytes;
    return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
};
?>
<style>
.business-media{--bm-ink:#14201A;--bm-muted:#5B675F;--bm-line:#E4EAE5;--bm-soft:#F6F9F6;--bm-green:#1F7A4D;--bm-green-dark:#14532D;--bm-danger:#A32A21;max-width:1100px;margin:auto;color:var(--bm-ink)}
.business-media *{box-sizing:border-box}
.business-media .media-head{display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1rem;margin-bottom:1.4rem}
.business-media .media-eyebrow{margin:0 0 .5rem;font-size:.75rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--bm-green)}
.business-media h1{margin:0 0 .4rem;font-size:clamp(1.7rem,3vw,2.2rem);font-weight:800;letter-spacing:-.03em}
.business-media .media-lede{margin:0;max-width:40rem;color:var(--bm-muted);font-size:.92rem;line-height:1.55}
.business-media .btn{min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:12px;font-weight:600;font-size:.9rem}
.business-media .btn-sm{min-height:44px;padding:0 .9rem}
.business-media .btn-primary{background:var(--bm-green);border-color:var(--bm-green);color:#fff}
.business-media .btn-primary:hover{background:var(--bm-green-dark);border-color:var(--bm-green-dark);color:#fff}
.business-media .btn-outline-secondary{border:1px solid #D0D9D2;color:#26382E;background:#fff}
.business-media .btn-outline-secondary:hover{background:var(--bm-soft);color:var(--bm-ink)}
.business-media .btn-outline-danger{border:1px solid #D9B4B0;color:var(--bm-danger);background:#fff}
.business-media .btn-outline-danger:hover{background:var(--bm-danger);border-color:var(--bm-danger);color:#fff}
.business-media .storage-summary{display:grid;gap:.55rem;margin-bottom:1.1rem;padding:1rem 1.1rem;border:1px solid var(--bm-line);border-radius:14px;background:#fff}
.business-media .storage-meta{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;color:var(--bm-muted);font-size:.84rem}
.business-media .storage-track{height:9px;overflow:hidden;border-radius:99px;background:#E9EEE9}
.business-media .storage-track span{display:block;height:100%;border-radius:inherit;background:var(--bm-green)}
.business-media .media-banner{display:flex;gap:.7rem;align-items:flex-start;margin-bottom:1.1rem;padding:.9rem 1.1rem;border:1px solid #CFE5D8;border-radius:14px;background:#F2F8F4;color:#26382E;font-size:.9rem}
.business-media .media-card{background:#fff;border:1px solid var(--bm-line);border-radius:20px;padding:clamp(1.1rem,3vw,1.5rem);margin-bottom:1.1rem}
.business-media .media-card h2{margin:0;font-size:1.1rem;font-weight:800}
.business-media .media-upload{display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;margin-top:1rem}
.business-media .media-upload label{flex:1 1 18rem;display:grid;gap:.45rem;font-size:.86rem;font-weight:700;color:#26382E}
.business-media .media-upload input[type=file]{width:100%;min-height:46px;padding:.45rem;border:1px solid #D0D9D2;border-radius:10px;background:#fff;font:inherit;font-weight:400;font-size:.9rem;color:var(--bm-ink)}
.business-media .media-upload input[type=file]::file-selector-button{min-height:36px;margin-right:.8rem;padding:0 .9rem;border:0;border-radius:8px;background:#E3F2E8;color:var(--bm-green-dark);font:inherit;font-weight:700;cursor:pointer}
.business-media .media-warn{flex:1 1 100%;margin:0;font-size:.86rem;font-weight:600;color:var(--bm-danger)}
.business-media .media-warn[hidden]{display:none}
.business-media .media-list-head{display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:1rem}
.business-media .media-count{font-size:.86rem;color:var(--bm-muted)}
.business-media .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:1rem}
.business-media .media-item{display:flex;flex-direction:column;border:1px solid var(--bm-line);border-radius:16px;overflow:hidden;background:#fff}
.business-media .media-item img{display:block;width:100%;height:150px;object-fit:cover;background:#F3F5F3}
.business-media .media-item-body{display:flex;flex-direction:column;gap:.2rem;flex:1;padding:.9rem}
.business-media .media-item strong{font-size:.9rem;overflow-wrap:anywhere}
.business-media .media-item small{display:block;color:var(--bm-muted);font-size:.8rem}
.business-media .media-actions{display:flex;gap:.45rem;flex-wrap:wrap;margin-top:auto;padding-top:.7rem}
.business-media .media-actions form{margin:0}
.business-media .media-empty{padding:2.2rem 1rem;text-align:center;border:1.5px dashed #C4D0C8;border-radius:16px;color:var(--bm-muted);background:var(--bm-soft)}
.business-media a:focus-visible,.business-media button:focus-visible{outline:3px solid rgba(31,122,77,.4);outline-offset:2px}
@media(max-width:600px){.business-media .media-head>.btn{width:100%}.business-media .media-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}.business-media .media-upload .btn{width:100%}}
</style>
<section class="client-dashboard iw-client-page business-media">
    <?php $editorActive = 'media'; require __DIR__ . '/partials/editor-navigation.php'; ?>
    <header class="media-head">
        <div>
            <p class="media-eyebrow">Business website / Assets</p>
            <h1>Media library</h1>
            <p class="media-lede">Upload and reuse images in your Business website. Images are stored inside this website only.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= $e($baseUrl . '/dashboard/websites/business?id=' . $siteId) ?>">Back to content</a>
    </header>

    <section class="storage-summary" aria-label="Account storage usage">
        <div class="storage-meta">
            <strong>Account storage</strong>
            <span><?= $e($formatSize($storageSummary['used_bytes'])) ?> used<?= (int) $storageSummary['limit_bytes'] > 0 ? ' of ' . $e($formatSize($storageSummary['limit_bytes'])) : ' · Unlimited' ?></span>
        </div>
        <?php if ((int) $storageSummary['limit_bytes'] > 0): ?>
            <div class="storage-track" role="progressbar" aria-label="Storage used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $storageSummary['percent'] ?>"><span style="width:<?= (int) $storageSummary['percent'] ?>%"></span></div>
        <?php endif; ?>
        <small class="media-count">Usage is shared across all your websites on the <?= $e(ucfirst($storageSummary['plan'])) ?> plan.</small>
    </section>

    <?php if ($selectTarget !== ''): ?>
        <div class="media-banner" role="status">Choose an image below and press <strong>&nbsp;Use image&nbsp;</strong> to add it to your website.</div>
    <?php endif; ?>

    <section class="media-card">
        <h2>Upload an image</h2>
        <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl . '/dashboard/websites/business/media/upload') ?>" class="media-upload">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $siteId ?>">
            <label>Image file (JPEG, PNG or WebP, max 8 MB)
                <input type="file" name="media_file" id="media_file" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <button class="btn btn-primary" type="submit">Upload image</button>
            <p class="media-warn" id="media-warn" role="alert" hidden>This file is larger than 8 MB, so it may be rejected.</p>
        </form>
    </section>

    <section class="media-card">
        <div class="media-list-head">
            <h2>Your images</h2>
            <span class="media-count"><?= count($items) ?> image<?= count($items) === 1 ? '' : 's' ?></span>
        </div>
        <?php if ($items === []): ?>
            <div class="media-empty">No images yet. Upload a JPEG, PNG or WebP image to get started.</div>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($items as $item): ?>
                    <article class="media-item">
                        <img src="<?= $e($item['url']) ?>" alt="<?= $e($item['filename']) ?>" loading="lazy">
                        <div class="media-item-body">
                            <strong><?= $e($item['filename']) ?></strong>
                            <small><?= $e($formatSize($item['size'])) ?></small>
                            <div class="media-actions">
                                <?php if ($selectTarget !== ''): ?>
                                    <button class="btn btn-sm btn-primary" type="button" data-select-image="<?= $e($item['path']) ?>">Use image</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-path="<?= $e($item['path']) ?>">Copy path</button>
                                <form method="POST" action="<?= $e($baseUrl . '/dashboard/websites/business/media/delete') ?>" onsubmit="return confirm('Delete this image from the media library?')">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= $siteId ?>">
                                    <input type="hidden" name="filename" value="<?= $e($item['filename']) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete <?= $e($item['filename']) ?>">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
<script>
(function () {
    var upload = document.getElementById('media_file');
    var warn = document.getElementById('media-warn');
    if (upload && warn) {
        upload.addEventListener('change', function () {
            var file = upload.files && upload.files[0];
            warn.hidden = !(file && file.size > 8 * 1048576);
        });
    }
    document.querySelectorAll('[data-select-image]').forEach((button) => {
        button.addEventListener('click', () => {
            const path = button.getAttribute('data-select-image') || '';
            if (!window.opener || window.opener.closed) {
                alert('Open the media library from the Business editor to select an image.');
                return;
            }
            window.opener.postMessage({ type: 'instaweb-media-selected', target: <?= json_encode($selectTarget) ?>, path }, window.location.origin);
            window.close();
        });
    });
    document.querySelectorAll('[data-copy-path]').forEach((button) => {
        button.addEventListener('click', async () => {
            const path = button.getAttribute('data-copy-path') || '';
            try {
                await navigator.clipboard.writeText(path);
                const old = button.textContent;
                button.textContent = 'Copied';
                window.setTimeout(() => button.textContent = old, 1400);
            } catch (error) {
                button.textContent = path;
            }
        });
    });
})();
</script>