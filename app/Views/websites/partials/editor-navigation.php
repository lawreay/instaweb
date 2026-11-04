<?php
/**
 * Shared navigation for managed website editors.
 *
 * Keep links limited to implemented destinations. Future editor areas are
 * intentionally displayed as unavailable until their workflows are shipped.
 */
$editorBaseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$editorWebsiteId = (int) ($website['id'] ?? 0);
$editorActive = (string) ($editorActive ?? 'content');
$editorTabs = [
    ['key' => 'overview', 'label' => 'Overview', 'url' => $editorBaseUrl . '/dashboard/websites/show?id=' . $editorWebsiteId, 'available' => true],
    ['key' => 'content', 'label' => 'Content', 'url' => $editorBaseUrl . '/dashboard/websites/business?id=' . $editorWebsiteId, 'available' => true],
    ['key' => 'themes', 'label' => 'Themes', 'url' => $editorBaseUrl . '/dashboard/websites/business/themes?id=' . $editorWebsiteId, 'available' => true],
    ['key' => 'branding', 'label' => 'Branding', 'url' => '', 'available' => false],
    ['key' => 'media', 'label' => 'Media', 'url' => '', 'available' => false],
    ['key' => 'seo', 'label' => 'SEO', 'url' => '', 'available' => false],
];
?>
<nav class="iw-editor-nav" aria-label="Website editor navigation">
    <?php foreach ($editorTabs as $tab): ?>
        <?php if ($tab['available']): ?>
            <a class="iw-editor-nav__item<?= $editorActive === $tab['key'] ? ' is-active' : '' ?>"
               href="<?= htmlspecialchars($tab['url'], ENT_QUOTES, 'UTF-8') ?>"
               <?= $editorActive === $tab['key'] ? 'aria-current="page"' : '' ?>>
                <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php else: ?>
            <span class="iw-editor-nav__item is-upcoming" aria-disabled="true"
                  title="<?= htmlspecialchars($tab['label'] . ' editor is not available yet', ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
                <small>Coming soon</small>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<style>
.iw-editor-nav{display:flex;flex-wrap:wrap;gap:.35rem;padding:.35rem;margin:0 0 1.25rem;border:1px solid #e5e9e5;border-radius:12px;background:#fff}
.iw-editor-nav__item{display:inline-flex;align-items:center;gap:.45rem;padding:.65rem .9rem;border-radius:8px;color:#59665f;text-decoration:none;font-size:.86rem;font-weight:700}
.iw-editor-nav__item:hover{background:#f5f7f5;color:#17231f}
.iw-editor-nav__item.is-active{background:#17231f;color:#fff}
.iw-editor-nav__item.is-upcoming{color:#98a19b;cursor:not-allowed}
.iw-editor-nav__item small{font-size:.62rem;font-weight:600;letter-spacing:.02em}
@media(max-width:600px){.iw-editor-nav{gap:.2rem}.iw-editor-nav__item{padding:.55rem .65rem;font-size:.78rem}}
</style>
