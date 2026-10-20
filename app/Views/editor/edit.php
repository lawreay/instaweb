<?php
$baseUrl = rtrim((string) ($app['base_url'] ?? ''), '/');
$websiteId = (int) $website['id'];
$editorFiles = array_values(array_filter($files ?? [], static fn (array $item): bool => in_array(($item['type'] ?? ''), ['file', 'folder'], true)));
$editorConfig = [
    'baseUrl' => $baseUrl,
    'websiteId' => $websiteId,
    'currentPath' => $file['relative_path'],
    'currentContent' => $file['content'],
    'extension' => $file['extension'],
    'csrf' => \App\Core\Csrf::field(),
    'saveUrl' => $baseUrl . '/dashboard/websites/editor/save',
    'editorUrl' => $baseUrl . '/dashboard/websites/editor',
    'previewUrl' => $baseUrl . '/dashboard/websites/preview/file?id=' . $websiteId . '&path=index.html',
    'previewPageUrl' => $baseUrl . '/dashboard/websites/preview?id=' . $websiteId,
    'manageUrl' => $baseUrl . '/dashboard/websites/show?id=' . $websiteId,
    'filesUrl' => $baseUrl . '/dashboard/websites/files?id=' . $websiteId,
    'fileActions' => [
        'createFile' => $baseUrl . '/dashboard/websites/files/create-file',
        'createFolder' => $baseUrl . '/dashboard/websites/files/create-folder',
        'upload' => $baseUrl . '/dashboard/websites/files/upload',
        'move' => $baseUrl . '/dashboard/websites/files/move',
        'copy' => $baseUrl . '/dashboard/websites/files/copy',
        'delete' => $baseUrl . '/dashboard/websites/files/delete',
        'download' => $baseUrl . '/dashboard/websites/files/download?id=' . $websiteId,
    ],
    'liveUrl' => $liveUrl ?? '',
    'files' => $editorFiles,
];
?>
<link rel="stylesheet" href="<?= htmlspecialchars($baseUrl . '/assets/css/instaweb-studio.css?v=20261020-3', ENT_QUOTES, 'UTF-8') ?>">
<section class="iw-studio" data-iw-studio data-config="<?= htmlspecialchars(json_encode($editorConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>">
  <header class="iw-studio-topbar">
    <a class="iw-studio-brand" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . $websiteId, ENT_QUOTES, 'UTF-8') ?>" aria-label="Back to site management">
      <span class="iw-studio-brand-mark"><i data-lucide="code-2"></i></span>
      <span><strong>InstaWeb Studio</strong><small><?= htmlspecialchars($website['website_name'] ?: 'Website project', ENT_QUOTES, 'UTF-8') ?></small></span>
    </a>
    <div class="iw-studio-actions">
      <span class="iw-save-status" data-save-status role="status" aria-live="polite"><span class="iw-status-dot"></span> Loading editor…</span>
      <button class="iw-studio-button" type="button" data-action="save"><i data-lucide="save"></i><span>Save</span></button>
      <button class="iw-studio-button" type="button" data-action="save-refresh-preview" title="Save changes and refresh preview"><i data-lucide="refresh-cw"></i><span>Save &amp; Refresh</span></button>
      <button class="iw-studio-button" type="button" data-action="shortcuts" title="Keyboard shortcuts (Ctrl/Cmd+Shift+P)"><i data-lucide="keyboard"></i><span>Shortcuts</span></button>
      <a class="iw-studio-button" href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/show?id=' . $websiteId, ENT_QUOTES, 'UTF-8') ?>"><i data-lucide="settings-2"></i><span>Manage</span></a>
      <?php if (($liveUrl ?? '') !== ''): ?>
        <a class="iw-studio-button iw-studio-button-primary" href="<?= htmlspecialchars($liveUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link"></i><span>Live site</span></a>
      <?php endif; ?>
    </div>
  </header>

  <div class="iw-studio-workspace">
    <aside class="iw-studio-explorer" aria-label="Project file explorer">
      <div class="iw-studio-section-heading"><span>EXPLORER</span><div class="iw-explorer-actions"><button type="button" data-file-action="new-file" title="New file" aria-label="New file"><i data-lucide="file-plus-2"></i></button><button type="button" data-file-action="new-folder" title="New folder" aria-label="New folder"><i data-lucide="folder-plus"></i></button><button type="button" data-file-action="upload" title="Upload file" aria-label="Upload file"><i data-lucide="upload"></i></button><button type="button" data-file-action="paste" title="Paste cut item into selected folder" aria-label="Paste"><i data-lucide="clipboard-paste"></i></button><a href="<?= htmlspecialchars($baseUrl . '/dashboard/websites/files?id=' . $websiteId, ENT_QUOTES, 'UTF-8') ?>" title="Open full file manager" aria-label="Open full file manager"><i data-lucide="folder-cog"></i></a></div></div>
      <label class="iw-file-search"><i data-lucide="search"></i><input type="search" placeholder="Find a file…" data-file-search aria-label="Search project files"></label>
      <input type="file" data-file-upload hidden accept=".html,.htm,.css,.js,.json,.txt,.md,.png,.jpg,.jpeg,.gif,.webp,.svg,.pdf,.mp4,.zip"><div class="iw-file-tree" data-file-tree></div>
      <div class="iw-explorer-footer"><i data-lucide="folder"></i><span><?= count($editorFiles) ?> project items</span></div>
    </aside>

    <main class="iw-studio-main">
      <div class="iw-studio-tabs" role="tablist" aria-label="Open files">
        <button class="iw-studio-tab is-active" type="button" role="tab" aria-selected="true" data-current-tab>
          <span data-language-icon>◇</span><span data-current-path><?= htmlspecialchars($file['relative_path'], ENT_QUOTES, 'UTF-8') ?></span><span class="iw-tab-dirty" data-dirty-indicator aria-label="Unsaved changes"></span>
        </button>
      </div>
      <div class="iw-studio-toolbar">
        <div class="iw-breadcrumb"><span><?= htmlspecialchars($website['website_name'] ?: 'Website', ENT_QUOTES, 'UTF-8') ?></span><i data-lucide="chevron-right"></i><strong data-breadcrumb><?= htmlspecialchars($file['relative_path'], ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="iw-editor-tools"><button type="button" data-action="toggle-preview" aria-pressed="true"><i data-lucide="panel-right"></i><span>Toggle preview</span></button><button type="button" data-action="refresh-preview" title="Save changes and refresh preview (F5)"><i data-lucide="refresh-cw"></i><span>Refresh</span></button></div>
      </div>
      <div class="iw-studio-panes" data-panes>
        <section class="iw-code-pane" aria-label="Code editor">
          <div class="iw-monaco-host" data-monaco-host></div>
          <textarea class="iw-editor-fallback is-visible" data-editor-fallback spellcheck="false" aria-label="Code editor fallback"><?= htmlspecialchars($file['content'], ENT_QUOTES, 'UTF-8') ?></textarea>
          <div class="iw-editor-loading is-hidden" data-editor-loading><span class="iw-loading-spinner"></span><span>Starting editor…</span></div>
        </section>
        <section class="iw-preview-pane" data-preview-pane aria-label="Live draft preview">
          <div class="iw-preview-heading"><span><i data-lucide="monitor"></i> Draft preview</span><span class="iw-preview-badge">PRIVATE</span></div>
          <iframe data-preview-frame title="Private draft website preview" data-preview-src="<?= htmlspecialchars($baseUrl . '/dashboard/websites/preview/file?id=' . $websiteId . '&path=index.html', ENT_QUOTES, 'UTF-8') ?>" sandbox="allow-scripts allow-forms allow-modals allow-popups" referrerpolicy="no-referrer"></iframe>
        </section>
      </div>
      <footer class="iw-studio-statusbar">
        <span data-language-status><?= strtoupper(htmlspecialchars($file['extension'], ENT_QUOTES, 'UTF-8')) ?></span><span>UTF-8</span><span>Spaces: 2</span><span class="iw-status-spacer"></span><span data-cursor-status>Ln 1, Col 1</span><span data-status-message>Autosave enabled</span>
      </footer>
    </main>
  </div>

  <dialog class="iw-shortcuts-dialog" data-shortcuts-dialog aria-labelledby="iw-shortcuts-title">
    <div class="iw-shortcuts-header">
      <h2 id="iw-shortcuts-title">Keyboard shortcuts</h2>
      <button type="button" class="iw-shortcuts-close" data-shortcuts-close aria-label="Close shortcuts">×</button>
    </div>
    <div class="iw-shortcuts-list">
      <div><span>Save file</span><kbd>Ctrl / ⌘ + S</kbd></div>
      <div><span>Save and refresh preview</span><kbd>Ctrl / ⌘ + Shift + S</kbd></div>
      <div><span>Refresh preview</span><kbd>F5</kbd></div>
      <div><span>Find a file</span><kbd>Ctrl / ⌘ + P</kbd></div>
      <div><span>Show shortcuts</span><kbd>Ctrl / ⌘ + Shift + P</kbd></div>
      <div><span>Insert indentation</span><kbd>Tab</kbd></div>
    </div>
    <p>Changes autosave after you stop typing. Preview refreshes after a successful save.</p>
    <div class="iw-shortcuts-footer"><button type="button" class="iw-studio-button iw-studio-button-primary" data-shortcuts-close>Done</button></div>
  </dialog>

  <form class="iw-studio-save-form" data-save-form method="POST" action="<?= htmlspecialchars($baseUrl . '/dashboard/websites/editor/save', ENT_QUOTES, 'UTF-8') ?>">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" name="website_id" value="<?= $websiteId ?>">
    <input type="hidden" name="path" value="<?= htmlspecialchars($file['relative_path'], ENT_QUOTES, 'UTF-8') ?>">
    <textarea name="content" data-save-content aria-hidden="true" tabindex="-1"></textarea>
  </form>
</section>
<script src="<?= htmlspecialchars($baseUrl . '/assets/js/instaweb-studio.js?v=20261020-5', ENT_QUOTES, 'UTF-8') ?>" defer></script>
