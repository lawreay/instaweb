<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$e = static fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<style>
.business-media{max-width:1100px;margin:auto;color:#17231f}.business-media .media-head{display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:1.2rem}.business-media .media-card{background:#fff;border:1px solid #e5e9e5;border-radius:13px;padding:1.2rem;margin-bottom:1rem}.business-media .media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:1rem}.business-media .media-item{border:1px solid #e5e9e5;border-radius:11px;overflow:hidden;background:#fff}.business-media .media-item img{width:100%;height:145px;object-fit:cover;background:#f3f5f3}.business-media .media-item-body{padding:.8rem}.business-media .media-item small{display:block;color:#66736d;overflow-wrap:anywhere}.business-media .media-actions{display:flex;gap:.4rem;flex-wrap:wrap;margin-top:.7rem}.business-media .media-actions form{margin:0}.business-media .media-empty{padding:2rem;text-align:center;border:1px dashed #cbd5ce;border-radius:10px;color:#66736d}
@media(max-width:600px){.business-media .media-head{flex-direction:column}.business-media .media-head>*{width:100%}}
</style>
<section class="client-dashboard iw-client-page business-media">
    <?php $editorActive = 'media'; require __DIR__ . '/partials/editor-navigation.php'; ?>
    <header class="media-head">
        <div>
            <p class="text-uppercase fw-bold small text-muted mb-2">Business website / Assets</p>
            <h1 class="h2 fw-bold mb-1">Media library</h1>
            <p class="text-muted mb-0">Upload and reuse images in your Business website. Images are stored inside this website only.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= $e($baseUrl . '/dashboard/websites/business?id=' . (int) $website['id']) ?>">Back to content</a>
    </header>
    <section class="media-card">
        <h2 class="h5 fw-bold">Upload an image</h2>
        <form method="POST" enctype="multipart/form-data" action="<?= $e($baseUrl . '/dashboard/websites/business/media/upload') ?>" class="d-flex flex-wrap align-items-end gap-3">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
            <label class="flex-grow-1">Image file (JPEG, PNG or WebP, max 8 MB)
                <input class="form-control mt-1" type="file" name="media_file" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <button class="btn btn-primary" type="submit">Upload image</button>
        </form>
    </section>
    <section class="media-card">
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 fw-bold mb-0">Your images</h2>
            <span class="text-muted small"><?= count($items) ?> image<?= count($items) === 1 ? '' : 's' ?></span>
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
                            <small><?= number_format($item['size'] / 1024, 1) ?> KB</small>
                            <div class="media-actions">
                                <?php if ($selectTarget !== ''): ?>
                                    <button class="btn btn-sm btn-primary" type="button" data-select-image="<?= $e($item['path']) ?>">Use image</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-copy-path="<?= $e($item['path']) ?>">Copy path</button>
                                <form method="POST" action="<?= $e($baseUrl . '/dashboard/websites/business/media/delete') ?>" onsubmit="return confirm('Delete this image from the media library?')">
                                    <?= \App\Core\Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $website['id'] ?>">
                                    <input type="hidden" name="filename" value="<?= $e($item['filename']) ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
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
