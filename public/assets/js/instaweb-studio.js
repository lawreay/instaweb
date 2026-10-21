(function () {
  'use strict';

  var root = document.querySelector('[data-iw-studio]');
  if (!root) return;

  var config;
  try { config = JSON.parse(root.getAttribute('data-config') || '{}'); }
  catch (error) { return; }

  var treeHost = root.querySelector('[data-file-tree]');
  var searchInput = root.querySelector('[data-file-search]');
  var saveStatus = root.querySelector('[data-save-status]');
  var statusMessage = root.querySelector('[data-status-message]');
  var loading = root.querySelector('[data-editor-loading]');
  var fallback = root.querySelector('[data-editor-fallback]');
  var host = root.querySelector('[data-monaco-host]');
  var form = root.querySelector('[data-save-form]');
  var contentInput = root.querySelector('[data-save-content]');
  var pathInput = form ? form.querySelector('[name="path"]') : null;
  var previewFrame = root.querySelector('[data-preview-frame]');
  var panes = root.querySelector('[data-panes]');
  var dirtyIndicator = root.querySelector('[data-dirty-indicator]');
  var currentPathLabel = root.querySelector('[data-current-path]');
  var breadcrumb = root.querySelector('[data-breadcrumb]');
  var languageStatus = root.querySelector('[data-language-status]');
  var cursorStatus = root.querySelector('[data-cursor-status]');
  var tabsHost = root.querySelector('.iw-studio-tabs');
  var currentTab = root.querySelector('[data-current-tab]');
  var currentPath = String(config.currentPath || 'index.html');
  var openPaths = [currentPath];
  var editor = null;
  var models = {};
  var currentContent = String(config.currentContent || '');
  var currentExtension = String(config.extension || 'html').toLowerCase();
  var editVersion = 0;
  var savedVersion = 0;
  var saveTimer = null;
  var saving = false;
  var saveAgain = false;
  var lastSaveError = '';
  var fallbackMode = true;
  var previewVisible = !window.matchMedia('(max-width: 760px)').matches;
  var previousMobileLayout = window.matchMedia('(max-width: 760px)').matches;
  var latestRequest = 0;
  var selectedFolder = '';
  var clipboardPath = '';
  var clipboardMode = 'copy';
  var uploadInput = root.querySelector('[data-file-upload]');

  function languageFor(extension) {
    return ({ html: 'html', htm: 'html', css: 'css', js: 'javascript', json: 'json', md: 'markdown', txt: 'plaintext' })[extension] || 'plaintext';
  }

  function iconFor(item) {
    if (item.type === 'folder') return 'folder';
    return ({ html: 'file-code-2', htm: 'file-code-2', css: 'file-code-2', js: 'file-code-2', json: 'file-json', md: 'file-text', txt: 'file-text' })[item.extension] || 'file';
  }

  function refreshIcons() {
    if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons();
  }

  function setStatus(state, message) {
    if (!saveStatus) return;
    saveStatus.classList.remove('is-saving', 'is-error');
    if (state === 'saving') saveStatus.classList.add('is-saving');
    if (state === 'error') saveStatus.classList.add('is-error');
    var label = state === 'saving' ? 'Saving…' : state === 'error' ? 'Save failed' : state === 'dirty' ? 'Unsaved changes' : 'All changes saved';
    saveStatus.lastChild.textContent = ' ' + label;
    if (statusMessage) statusMessage.textContent = message || label;
  }

  function isDirty() { return editVersion !== savedVersion; }

  function renderTabs() {
    if (!tabsHost) return;
    tabsHost.replaceChildren();
    openPaths.forEach(function (path) {
      var tab = document.createElement('button');
      tab.type = 'button';
      tab.className = 'iw-studio-tab' + (path === currentPath ? ' is-active' : '');
      tab.setAttribute('role', 'tab');
      tab.setAttribute('aria-selected', path === currentPath ? 'true' : 'false');
      tab.setAttribute('aria-label', path);
      tab.title = path;
      var ext = path.split('.').pop().toLowerCase();
      var lang = document.createElement('span');
      lang.textContent = ext === 'html' || ext === 'htm' ? '◇' : ext === 'css' ? '#' : ext === 'js' ? 'JS' : '{}';
      var label = document.createElement('span');
      label.textContent = path.split('/').pop();
      label.setAttribute('data-tab-label', '');
      var dirty = document.createElement('span');
      dirty.className = 'iw-tab-dirty' + (path === currentPath && isDirty() ? ' is-dirty' : '');
      dirty.setAttribute('aria-label', 'Unsaved changes');
      tab.appendChild(lang);
      tab.appendChild(label);
      tab.appendChild(dirty);
      tab.addEventListener('click', function () { openFile(path); });
      tabsHost.appendChild(tab);
      if (path === currentPath) currentTab = tab;
    });
  }

  function updateDirtyUi() {
    if (dirtyIndicator) dirtyIndicator.classList.toggle('is-dirty', isDirty());
    if (currentTab) {
      currentTab.setAttribute('aria-label', currentPath + (isDirty() ? ', unsaved changes' : ''));
      var tabDirty = currentTab.querySelector('.iw-tab-dirty');
      if (tabDirty) tabDirty.classList.toggle('is-dirty', isDirty());
    }
    if (isDirty()) setStatus('dirty', 'Changes will autosave');
    else if (!lastSaveError) setStatus('saved', 'Autosave enabled');
  }

  function currentValue() {
    return editor && !fallbackMode ? editor.getValue() : (fallback ? fallback.value : currentContent);
  }

  function setCurrentValue(value) {
    currentContent = String(value);
    if (editor && !fallbackMode) editor.setValue(currentContent);
    if (fallback) fallback.value = currentContent;
  }

  function refreshPreview() {
    if (!previewFrame) return;
    var url = config.previewUrl || '';
    if (!url) return;
    previewFrame.dataset.loaded = 'true';
    previewFrame.src = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_preview=' + Date.now();
  }

  function scheduleSave(delay) {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(function () { saveBuffer(); }, typeof delay === 'number' ? delay : 850);
  }

  async function saveBuffer() {
    if (!isDirty()) return true;
    if (saving) { saveAgain = true; return false; }
    if (!form || !pathInput || !contentInput) return false;

    saving = true;
    saveAgain = false;
    lastSaveError = '';
    var snapshotVersion = editVersion;
    var snapshotContent = currentValue();
    contentInput.value = snapshotContent;
    setStatus('saving', 'Saving draft to server…');

    try {
      var body = new FormData(form);
      var response = await fetch(config.saveUrl, {
        method: 'POST',
        body: body,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      var payload;
      try { payload = await response.json(); }
      catch (error) { throw new Error('The server returned an unexpected response. Refresh and sign in again if this continues.'); }
      if (!response.ok || !payload || payload.ok !== true) {
        throw new Error(payload && payload.message ? payload.message : 'Unable to save this file.');
      }

      savedVersion = snapshotVersion;
      currentContent = snapshotContent;
      if (snapshotVersion === editVersion) {
        setStatus('saved', 'Saved to draft storage');
        refreshPreview();
      }
    } catch (error) {
      lastSaveError = error && error.message ? error.message : 'Unable to save this file.';
      setStatus('error', lastSaveError);
      if (statusMessage) statusMessage.textContent = lastSaveError;
    } finally {
      saving = false;
      updateDirtyUi();
      if (lastSaveError) setStatus('error', lastSaveError);
      if (saveAgain || editVersion !== savedVersion) scheduleSave(lastSaveError ? 2500 : 150);
    }

    return !isDirty() && !lastSaveError;
  }

  function onEdit() {
    editVersion += 1;
    lastSaveError = '';
    updateDirtyUi();
    scheduleSave();
  }

  function submitFileAction(action, fields, fileInput) {
    var routes = config.fileActions || {};
    var endpoint = routes[action];
    if (!endpoint) { setStatus('error', 'This file action is not configured.'); return; }
    var actionForm = document.createElement('form');
    actionForm.method = 'post';
    actionForm.action = endpoint;
    actionForm.enctype = fileInput ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
    actionForm.hidden = true;
    var csrf = form ? form.querySelector('[name="_csrf"]') : null;
    var values = Object.assign({ website_id: String(config.websiteId || ''), _csrf: csrf ? csrf.value : '', return_to: config.editorUrl + '?id=' + encodeURIComponent(config.websiteId) + '&path=' + encodeURIComponent(currentPath) }, fields || {});
    Object.keys(values).forEach(function (name) {
      var input = document.createElement('input');
      input.type = 'hidden'; input.name = name; input.value = String(values[name] == null ? '' : values[name]);
      actionForm.appendChild(input);
    });
    if (fileInput) {
      fileInput.name = 'project_file';
      actionForm.appendChild(fileInput);
    }
    document.body.appendChild(actionForm);
    actionForm.submit();
  }

  function folderForPath(path) {
    var normalized = String(path || '').replace(/\\\\/g, '/');
    var index = normalized.lastIndexOf('/');
    return index >= 0 ? normalized.slice(0, index) : '';
  }

  function showInlineNameInput(action, item, wrap) {
    if (!treeHost) return;
    treeHost.querySelectorAll('.iw-inline-name-row').forEach(function (row) { row.remove(); });
    var row = document.createElement('form');
    row.className = 'iw-inline-name-row';
    row.addEventListener('submit', function (event) {
      event.preventDefault();
      var value = input.value.trim();
      if (!value) { input.focus(); return; }
      if (action === 'rename') {
        var parentPath = folderForPath(item.path);
        var destination = (parentPath ? parentPath + '/' : '') + value;
        if (destination === item.path) { row.remove(); return; }
        submitFileAction('move', { from_path: item.path, to_path: destination });
      } else if (action === 'new-folder') {
        var folderPath = (selectedFolder ? selectedFolder + '/' : '') + value.split('/').filter(Boolean).join('/');
        submitFileAction('createFolder', { folder_path: folderPath });
      } else if (action === 'new-file') {
        var filePath = (selectedFolder ? selectedFolder + '/' : '') + value.split('/').filter(Boolean).join('/');
        submitFileAction('createFile', { file_path: filePath });
      }
    });
    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'iw-inline-name-input';
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.setAttribute('aria-label', action === 'rename' ? 'Rename ' + item.name : action === 'new-folder' ? 'New folder name' : 'New file name');
    input.placeholder = action === 'rename' ? 'New name' : action === 'new-folder' ? 'Folder name' : 'File name';
    input.value = action === 'rename' && item ? item.name : '';
    var submit = document.createElement('button');
    submit.type = 'submit';
    submit.title = 'Confirm';
    submit.setAttribute('aria-label', 'Confirm name');
    submit.innerHTML = '<i data-lucide="check"></i>';
    var cancel = document.createElement('button');
    cancel.type = 'button';
    cancel.title = 'Cancel';
    cancel.setAttribute('aria-label', 'Cancel');
    cancel.innerHTML = '<i data-lucide="x"></i>';
    cancel.addEventListener('click', function () { row.remove(); });
    row.appendChild(input);
    row.appendChild(submit);
    row.appendChild(cancel);
    if (wrap && wrap.parentNode) wrap.parentNode.insertBefore(row, wrap.nextSibling);
    else treeHost.insertBefore(row, treeHost.firstChild);
    refreshIcons();
    input.focus();
    input.select();
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') { event.preventDefault(); row.remove(); }
    });
  }

  function runItemAction(action, item, wrap) {
    if (action === 'rename') {
      showInlineNameInput('rename', item, wrap);
    } else if (action === 'delete') {
      if (!window.confirm('Delete "' + item.path + '"? This cannot be undone.')) return;
      submitFileAction('delete', { path: item.path });
    } else if (action === 'download') {
      window.location.href = (config.fileActions.download || '') + '&path=' + encodeURIComponent(item.path);
    } else if (action === 'copy') {
      clipboardPath = item.path;
      clipboardMode = 'copy';
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(item.path).catch(function () {});
      setStatus('saved', 'Ready to copy: ' + item.path + ' · select a destination folder and press Paste');
    } else if (action === 'cut') {
      clipboardPath = item.path;
      clipboardMode = 'cut';
      setStatus('saved', 'Ready to move: ' + item.path + ' · select a destination folder and press Paste');
    }
  }

  function renderTree(query) {
    if (!treeHost) return;
    treeHost.replaceChildren();
    var normalizedQuery = String(query || '').trim().toLowerCase();
    var files = Array.isArray(config.files) ? config.files : [];
    var rootNode = { name: '', path: '', type: 'folder', children: {} };

    files.forEach(function (file) {
      var path = String(file.path || '').replace(/\\/g, '/');
      if (normalizedQuery && path.toLowerCase().indexOf(normalizedQuery) === -1) return;
      var segments = path.split('/').filter(Boolean);
      var cursor = rootNode;
      segments.forEach(function (segment, index) {
        if (!cursor.children[segment]) {
          cursor.children[segment] = { name: segment, path: segments.slice(0, index + 1).join('/'), type: index === segments.length - 1 ? 'file' : 'folder', children: {} };
        }
        cursor = cursor.children[segment];
        if (index === segments.length - 1) {
          cursor.type = file.type === 'folder' ? 'folder' : 'file';
          cursor.extension = cursor.type === 'file' ? String(file.extension || '').toLowerCase() : '';
          cursor.path = path;
        }
      });
    });

    function appendChildren(parent, node, depth) {
      var children = Object.keys(node.children).map(function (key) { return node.children[key]; });
      children.sort(function (a, b) {
        if (a.type !== b.type) return a.type === 'folder' ? -1 : 1;
        return a.name.localeCompare(b.name, undefined, { sensitivity: 'base' });
      });
      children.forEach(function (item) {
        var wrap = document.createElement('div');
        wrap.className = 'iw-tree-node';
        var button = document.createElement('button');
        button.type = 'button';
        button.className = (item.type === 'folder' ? 'iw-tree-folder' : 'iw-tree-file') + (item.path === selectedFolder && item.type === 'folder' ? ' is-selected-folder' : '');
        button.style.paddingLeft = '8px';
        if (item.type === 'folder') {
          var containsCurrentFile = currentPath === item.path || currentPath.indexOf(item.path + '/') === 0;
          var expandedByDefault = containsCurrentFile || normalizedQuery !== '';
          button.setAttribute('aria-expanded', expandedByDefault ? 'true' : 'false');
          var caret = document.createElement('i');
          caret.setAttribute('data-lucide', 'chevron-down');
          caret.className = 'iw-tree-caret';
          button.appendChild(caret);
        } else {
          if (item.path === currentPath) button.classList.add('is-active');
          var imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
          if (imageExtensions.indexOf(item.extension) !== -1) {
            var thumbnail = document.createElement('img');
            thumbnail.className = 'iw-tree-thumbnail';
            thumbnail.alt = '';
            thumbnail.loading = 'lazy';
            thumbnail.src = config.baseUrl + '/dashboard/websites/preview/file?id=' + encodeURIComponent(config.websiteId) + '&path=' + encodeURIComponent(item.path);
            thumbnail.onerror = function () { thumbnail.hidden = true; };
            button.appendChild(thumbnail);
          } else {
            var icon = document.createElement('i');
            icon.setAttribute('data-lucide', iconFor(item));
            button.appendChild(icon);
          }
        }
        var label = document.createElement('span');
        label.className = 'iw-tree-label';
        label.textContent = item.name;
        button.appendChild(label);
        var row = document.createElement('div');
        row.className = 'iw-tree-row';
        row.appendChild(button);
        wrap.appendChild(row);

        if (item.type === 'folder') {
          var childrenHost = document.createElement('div');
          childrenHost.className = 'iw-tree-children';
          childrenHost.hidden = !expandedByDefault;
          appendChildren(childrenHost, item, depth + 1);
          if (!expandedByDefault) {
            var initialCaret = button.querySelector('.iw-tree-caret');
            if (initialCaret) initialCaret.setAttribute('data-lucide', 'chevron-right');
          }
          button.addEventListener('click', function () {
            selectedFolder = item.path;
            treeHost.querySelectorAll('.is-selected-folder').forEach(function (selected) { selected.classList.remove('is-selected-folder'); });
            button.classList.add('is-selected-folder');
            var expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            childrenHost.hidden = expanded;
            var caretIcon = button.querySelector('.iw-tree-caret');
            if (caretIcon) caretIcon.setAttribute('data-lucide', expanded ? 'chevron-right' : 'chevron-down');
            refreshIcons();
          });
          wrap.appendChild(childrenHost);
        } else {
          button.title = item.path;
          button.addEventListener('click', function () {
            if (['html', 'htm', 'css', 'js', 'json', 'txt', 'md'].indexOf(item.extension) !== -1) {
              openFile(item.path);
            } else if (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'].indexOf(item.extension) !== -1) {
              if (previewFrame) previewFrame.src = config.baseUrl + '/dashboard/websites/preview/file?id=' + encodeURIComponent(config.websiteId) + '&path=' + encodeURIComponent(item.path);
              setPreviewVisible(true);
            } else {
              window.location.href = (config.fileActions.download || '') + '&path=' + encodeURIComponent(item.path);
            }
          });
        }
        var menuButton = document.createElement('button');
        menuButton.type = 'button';
        menuButton.className = 'iw-tree-options';
        menuButton.setAttribute('aria-label', 'Options for ' + item.name);
        menuButton.title = 'File options';
        var menuIcon = document.createElement('i');
        menuIcon.setAttribute('data-lucide', 'ellipsis');
        menuButton.appendChild(menuIcon);
        menuButton.setAttribute('aria-haspopup', 'menu');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.addEventListener('click', function (event) {
          event.stopPropagation();
          var existing = wrap.querySelector('.iw-tree-menu');
          if (existing) { existing.remove(); menuButton.setAttribute('aria-expanded', 'false'); return; }
          treeHost.querySelectorAll('.iw-tree-menu').forEach(function (menu) { menu.remove(); });
          treeHost.querySelectorAll('.iw-tree-options[aria-expanded="true"]').forEach(function (openButton) { openButton.setAttribute('aria-expanded', 'false'); });
          var menu = document.createElement('div');
          menu.className = 'iw-tree-menu';
          menu.setAttribute('role', 'menu');
          var choices = item.type === 'folder'
            ? [{ id: 'rename', label: 'Rename', icon: 'pencil' }, { id: 'cut', label: 'Cut', icon: 'scissors' }, { id: 'copy', label: 'Copy', icon: 'copy' }, { id: 'delete', label: 'Delete', icon: 'trash-2' }]
            : [{ id: 'rename', label: 'Rename', icon: 'pencil' }, { id: 'cut', label: 'Cut', icon: 'scissors' }, { id: 'copy', label: 'Copy', icon: 'copy' }, { id: 'download', label: 'Download', icon: 'download' }, { id: 'delete', label: 'Delete', icon: 'trash-2' }];
          choices.forEach(function (choice) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'iw-tree-menu-item' + (choice.id === 'delete' ? ' is-danger' : '');
            option.setAttribute('role', 'menuitem');
            var optionIcon = document.createElement('i');
            optionIcon.setAttribute('data-lucide', choice.icon);
            var optionLabel = document.createElement('span');
            optionLabel.textContent = choice.label;
            option.appendChild(optionIcon);
            option.appendChild(optionLabel);
            option.addEventListener('click', function () {
              menu.remove();
              menuButton.setAttribute('aria-expanded', 'false');
              runItemAction(choice.id, item, wrap);
            });
            menu.appendChild(option);
          });
          wrap.appendChild(menu);
          menuButton.setAttribute('aria-expanded', 'true');
          refreshIcons();
        });
        row.appendChild(menuButton);
        parent.appendChild(wrap);
      });
    }

    appendChildren(treeHost, rootNode, 0);
    if (!treeHost.children.length) {
      var empty = document.createElement('div');
      empty.className = 'iw-tree-empty';
      empty.textContent = normalizedQuery ? 'No editable files match your search.' : 'No editable text files found. Use File Manager to upload or create files.';
      treeHost.appendChild(empty);
    }
    refreshIcons();
  }

  async function openFile(path) {
    if (path === currentPath) return;
    if (isDirty() && !(await saveBuffer())) return;

    var requestId = ++latestRequest;
    setStatus('saving', 'Opening ' + path + '…');
    try {
      var url = config.editorUrl + '/file?id=' + encodeURIComponent(config.websiteId) + '&path=' + encodeURIComponent(path);
      var response = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      var payload = await response.json();
      if (requestId !== latestRequest) return;
      if (!response.ok || !payload || payload.ok !== true) throw new Error(payload && payload.message ? payload.message : 'Unable to open file.');

      currentPath = payload.path;
      currentExtension = String(payload.extension || '').toLowerCase();
      currentContent = String(payload.content || '');
      editVersion = 0;
      savedVersion = 0;
      lastSaveError = '';
      if (pathInput) pathInput.value = currentPath;
      if (editor && !fallbackMode && window.monaco) {
        var modelKey = currentPath;
        if (!models[modelKey]) models[modelKey] = window.monaco.editor.createModel(currentContent, languageFor(currentExtension), window.monaco.Uri.parse('inmemory://instaweb/' + encodeURIComponent(modelKey)));
        editor.setModel(models[modelKey]);
        editor.focus();
      } else if (fallback) {
        fallback.value = currentContent;
      }

      if (currentPathLabel) currentPathLabel.textContent = currentPath;
      if (breadcrumb) breadcrumb.textContent = currentPath;
      if (languageStatus) languageStatus.textContent = currentExtension.toUpperCase();
      var langIcon = root.querySelector('[data-language-icon]');
      if (langIcon) langIcon.textContent = currentExtension === 'html' || currentExtension === 'htm' ? '◇' : currentExtension === 'css' ? '#' : currentExtension === 'js' ? 'JS' : '{}';
      if (openPaths.indexOf(currentPath) === -1) openPaths.push(currentPath);
      renderTabs();
      renderTree(searchInput ? searchInput.value : '');
      updateDirtyUi();
      if (window.history && window.history.replaceState) {
        window.history.replaceState({}, '', config.editorUrl + '?id=' + encodeURIComponent(config.websiteId) + '&path=' + encodeURIComponent(currentPath));
      }
      setStatus('saved', 'File ready');
    } catch (error) {
      setStatus('error', error && error.message ? error.message : 'Unable to open file.');
    }
  }

  function setPreviewVisible(visible) {
    previewVisible = visible;
    if (panes) {
      panes.classList.toggle('is-preview-hidden', !visible);
      panes.classList.toggle('is-preview-visible', visible && window.matchMedia('(max-width: 760px)').matches);
    }
    var toggle = root.querySelector('[data-action="toggle-preview"]');
    if (toggle) toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
  }

  function updateCursor() {
    if (!cursorStatus) return;
    if (editor && !fallbackMode) {
      var pos = editor.getPosition();
      if (pos) cursorStatus.textContent = 'Ln ' + pos.lineNumber + ', Col ' + pos.column;
    } else if (fallback) {
      var before = fallback.value.slice(0, fallback.selectionStart);
      var lines = before.split('\n');
      cursorStatus.textContent = 'Ln ' + lines.length + ', Col ' + (lines[lines.length - 1].length + 1);
    }
  }

  function startFallback() {
    fallbackMode = true;
    if (fallback) {
      fallback.classList.add('is-visible');
      fallback.value = currentContent;
      fallback.addEventListener('input', onEdit);
      fallback.addEventListener('keyup', updateCursor);
      fallback.addEventListener('click', updateCursor);
    }
    if (loading) loading.classList.add('is-hidden');
    setStatus('saved', 'Basic editor mode · autosave enabled');
  }

  function startMonaco() {
    if (!window.require || !window.require.config) { startFallback(); return; }
    window.require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs' } });
    window.require(['vs/editor/editor.main'], function () {
      try {
        window.monaco.editor.setTheme('vs-dark');
        // Preserve anything typed into the immediately available fallback while Monaco downloads.
        if (fallback && fallback.classList.contains('is-visible')) currentContent = fallback.value;
        var initialUri = window.monaco.Uri.parse('inmemory://instaweb/' + encodeURIComponent(currentPath));
        models[currentPath] = window.monaco.editor.createModel(currentContent, languageFor(currentExtension), initialUri);
        editor = window.monaco.editor.create(host, {
          model: models[currentPath],
          theme: 'vs-dark',
          automaticLayout: true,
          minimap: { enabled: false },
          fontSize: 13,
          lineHeight: 21,
          tabSize: 2,
          insertSpaces: true,
          wordWrap: 'off',
          scrollBeyondLastLine: false,
          smoothScrolling: true,
          cursorBlinking: 'smooth',
          renderLineHighlight: 'all',
          bracketPairColorization: { enabled: true },
          guides: { indentation: true, bracketPairs: true },
          padding: { top: 12, bottom: 12 },
          ariaLabel: 'InstaWeb code editor'
        });
        fallbackMode = false;
        if (fallback) fallback.classList.remove('is-visible');
        if (loading) loading.classList.add('is-hidden');
        editor.onDidChangeModelContent(onEdit);
        editor.onDidChangeCursorPosition(updateCursor);
        editor.addCommand(window.monaco.KeyMod.CtrlCmd | window.monaco.KeyCode.KeyS, function () { saveBuffer(); });
        editor.focus();
        setStatus('saved', 'Autosave enabled · Ctrl/Cmd+S to save');
      } catch (error) {
        startFallback();
      }
    }, startFallback);
  }

  root.querySelectorAll('[data-action]').forEach(function (button) {
    button.addEventListener('click', function () {
      var action = button.getAttribute('data-action');
      if (action === 'save') saveBuffer().then(function (ok) { if (ok) refreshPreview(); });
      if (action === 'save-refresh-preview' || action === 'preview' || action === 'refresh-preview') {
        saveBuffer().then(function (ok) {
          if (!ok) return;
          if (action === 'preview') setPreviewVisible(true);
          refreshPreview();
        });
      }
      if (action === 'toggle-preview') setPreviewVisible(!previewVisible);
      if (action === 'shortcuts') showShortcuts();
    });
  });

  root.querySelectorAll('[data-file-action]').forEach(function (button) {
    button.addEventListener('click', function () {
      var action = button.getAttribute('data-file-action');
      if (action === 'paste') {
        if (!clipboardPath) { setStatus('error', 'Copy or cut a file or folder first.'); return; }
        var name = clipboardPath.split('/').pop();
        var destination = (selectedFolder ? selectedFolder + '/' : '') + name;
        if (destination === clipboardPath) { setStatus('error', 'Choose a different destination folder first.'); return; }
        if (clipboardMode === 'cut') submitFileAction('move', { from_path: clipboardPath, to_path: destination });
        else submitFileAction('copy', { from_path: clipboardPath, to_path: destination });
      } else if (action === 'new-file') {
        showInlineNameInput('new-file');
      } else if (action === 'new-folder') {
        showInlineNameInput('new-folder');
      } else if (action === 'upload' && uploadInput) {
        uploadInput.click();
      }
    });
  });
  if (uploadInput) uploadInput.addEventListener('change', function () {
    if (!uploadInput.files || !uploadInput.files.length) return;
    var chosen = uploadInput.files[0];
    var transfer = uploadInput;
    // Upload endpoint currently accepts one file per request.
    var hiddenFolder = selectedFolder;
    submitFileAction('upload', { folder: hiddenFolder }, transfer);
    setStatus('saving', 'Uploading ' + chosen.name + '…');
  });

  if (searchInput) searchInput.addEventListener('input', function () { renderTree(searchInput.value); });

  var shortcutDialog = root.querySelector('[data-shortcuts-dialog]');
  function showShortcuts() {
    if (!shortcutDialog) return;
    if (typeof shortcutDialog.showModal === 'function') shortcutDialog.showModal();
    else shortcutDialog.hidden = false;
  }
  function hideShortcuts() {
    if (!shortcutDialog) return;
    if (typeof shortcutDialog.close === 'function' && shortcutDialog.open) shortcutDialog.close();
    else shortcutDialog.hidden = true;
  }
  if (shortcutDialog) {
    shortcutDialog.addEventListener('click', function (event) {
      if (event.target === shortcutDialog) hideShortcuts();
    });
    shortcutDialog.querySelectorAll('[data-shortcuts-close]').forEach(function (button) {
      button.addEventListener('click', hideShortcuts);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (!root.isConnected) return;
    if (event.key === 'Escape' && shortcutDialog && shortcutDialog.open) {
      hideShortcuts();
      return;
    }
    var command = event.ctrlKey || event.metaKey;
    var key = String(event.key || '').toLowerCase();
    if (command && key === 's') {
      event.preventDefault();
      if (event.shiftKey) {
        saveBuffer().then(function (ok) { if (ok) refreshPreview(); });
      } else {
        saveBuffer();
      }
      return;
    }
    if (event.key === 'F5') {
      event.preventDefault();
      saveBuffer().then(function (ok) { if (ok) refreshPreview(); });
      return;
    }
    if (command && event.shiftKey && key === 'p') {
      event.preventDefault();
      showShortcuts();
      return;
    }
    if (command && key === 'p' && searchInput) {
      event.preventDefault();
      searchInput.focus();
      searchInput.select();
      return;
    }
  });

  if (fallback) {
    fallback.addEventListener('keydown', function (event) {
      if (event.key === 'Tab') {
        event.preventDefault();
        var start = fallback.selectionStart;
        var end = fallback.selectionEnd;
        fallback.setRangeText('  ', start, end, 'end');
        onEdit();
      }
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        saveBuffer();
      }
    });
  }

  setPreviewVisible(previewVisible);
  renderTabs();
  renderTree('');

  // The server-rendered textarea is the first editor. Load heavyweight Monaco only
  // after the browser has had a chance to paint the source and interactive controls.
  startFallback();

  function scheduleMonacoStartup() {
    var loadMonaco = function () {
      // Do not download Monaco twice if this function is ever called again.
      if (window.require && window.require.config) {
        startMonaco();
        return;
      }
      var script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/monaco-editor@0.52.2/min/vs/loader.js';
      script.async = true;
      script.onload = startMonaco;
      script.onerror = startFallback;
      document.head.appendChild(script);
    };

    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(loadMonaco, { timeout: 1800 });
    } else {
      window.setTimeout(loadMonaco, 1000);
    }
  }

  function schedulePreviewStartup() {
    if (!previewFrame || !previewFrame.dataset.previewSrc) return;
    var loadPreview = function () {
      if (previewFrame.dataset.loaded === 'true') return;
      previewFrame.dataset.loaded = 'true';
      previewFrame.src = previewFrame.dataset.previewSrc;
    };
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(loadPreview, { timeout: 1200 });
    } else {
      window.setTimeout(loadPreview, 700);
    }
  }

  scheduleMonacoStartup();
  schedulePreviewStartup();
  window.addEventListener('beforeunload', function (event) {
    if (isDirty()) {
      event.preventDefault();
      event.returnValue = '';
    }
  });
  window.addEventListener('resize', function () {
    var isMobileLayout = window.matchMedia('(max-width: 760px)').matches;
    if (isMobileLayout !== previousMobileLayout) {
      previewVisible = !isMobileLayout;
      previousMobileLayout = isMobileLayout;
    }
    setPreviewVisible(previewVisible);
  });
}());
