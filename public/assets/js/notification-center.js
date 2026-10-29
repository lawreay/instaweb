(function () {
  'use strict';
  function init() {
    var baseMeta = document.querySelector('meta[name="instaweb-base-url"]');
    var csrfMeta = document.querySelector('meta[name="instaweb-csrf"]');
    if (!baseMeta || !csrfMeta || document.querySelector('[data-iw-notification-center]')) return;
    var baseUrl = baseMeta.content.replace(/\/$/, '');
    var csrfToken = csrfMeta.content;
    var pollUrl = baseUrl + '/api/notifications/poll';
    var markUrl = baseUrl + '/api/notifications/mark-read';
    var markAllUrl = baseUrl + '/api/notifications/mark-all-read';
    var knownIds = new Set();
    var firstPoll = true;
    var unreadCount = 0;

    var dock = document.createElement('div');
    dock.className = 'iw-notification-dock';
    dock.setAttribute('data-iw-notification-center', '');
    dock.innerHTML = '<section class="iw-notification-panel" id="iwNotificationPanel" aria-label="Recent notifications" hidden>' +
      '<header class="iw-notification-panel-head"><div><strong>Notifications</strong><small id="iwNotificationSummary">Checking for updates…</small></div>' +
      '<div class="iw-notification-actions"><button type="button" id="iwMarkAllRead">Read all</button><button type="button" id="iwCloseNotifications" aria-label="Close notifications">✕</button></div></header>' +
      '<div class="iw-notification-list" id="iwNotificationList" aria-live="polite"></div>' +
      '<footer class="iw-notification-footer"><a href="' + escapeAttr(baseUrl + '/dashboard/notifications') + '">Open notification center</a></footer></section>' +
      '<button type="button" class="iw-notification-bell" id="iwNotificationBell" aria-label="Notifications" aria-controls="iwNotificationPanel" aria-expanded="false"><i data-lucide="bell"></i><span class="iw-notification-badge" id="iwNotificationBadge" hidden></span></button>';
    document.body.appendChild(dock);
    var toastHost = document.createElement('div');
    toastHost.className = 'iw-notification-toasts';
    toastHost.setAttribute('aria-live', 'polite');
    toastHost.setAttribute('aria-relevant', 'additions');
    document.body.appendChild(toastHost);

    var panel = dock.querySelector('#iwNotificationPanel');
    var bell = dock.querySelector('#iwNotificationBell');
    var badge = dock.querySelector('#iwNotificationBadge');
    var list = dock.querySelector('#iwNotificationList');
    var summary = dock.querySelector('#iwNotificationSummary');
    var markAll = dock.querySelector('#iwMarkAllRead');

    function escapeHtml(value) {
      return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function escapeAttr(value) { return escapeHtml(value); }
    function safeTarget(value) {
      var target = String(value || '');
      if (!target || target.charAt(0) !== '/' || target.indexOf('//') === 0 || /[\\\r\n]/.test(target)) return '';
      return baseUrl + target;
    }
    function setCount(value) {
      unreadCount = Math.max(0, Number(value) || 0);
      badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
      badge.hidden = unreadCount < 1;
      bell.setAttribute('aria-label', unreadCount ? 'Notifications, ' + unreadCount + ' unread' : 'Notifications');
      summary.textContent = unreadCount ? unreadCount + ' unread notification' + (unreadCount === 1 ? '' : 's') : 'You’re all caught up';
      markAll.disabled = unreadCount === 0;
    }
    function showToast(notification) {
      var toast = document.createElement('article');
      toast.className = 'iw-notification-toast';
      toast.setAttribute('role', 'status');
      toast.innerHTML = '<span class="iw-notification-toast-icon"><i data-lucide="bell-ring"></i></span><div class="iw-notification-toast-copy"><strong>' + escapeHtml(notification.title || 'New notification') + '</strong><p>' + escapeHtml(notification.message || '') + '</p></div><button type="button" aria-label="Dismiss notification">✕</button>';
      toastHost.appendChild(toast);
      if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
      var dismiss = function () { if (toast.parentNode) toast.parentNode.removeChild(toast); };
      toast.querySelector('button').addEventListener('click', dismiss);
      var timer = window.setTimeout(dismiss, 6500);
      toast.addEventListener('mouseenter', function () { window.clearTimeout(timer); });
      toast.addEventListener('mouseleave', function () { timer = window.setTimeout(dismiss, 2500); });
      if (notification.target_url) {
        toast.style.cursor = 'pointer';
        toast.addEventListener('click', function (event) {
          if (event.target.closest('button')) return;
          dismiss();
          markRead(notification.id).finally(function () { window.location.href = safeTarget(notification.target_url) || baseUrl + '/dashboard/notifications'; });
        });
      }
    }
    function render(notifications) {
      if (!notifications.length) {
        list.innerHTML = '<div class="iw-notification-empty">No notifications yet. Updates will appear here.</div>';
        return;
      }
      list.innerHTML = notifications.map(function (n) {
        var isUnread = Number(n.is_read) === 0;
        return '<div class="iw-notification-entry">' +
          '<button type="button" class="iw-notification-row ' + (isUnread ? 'is-unread' : '') + '" data-notification-id="' + escapeAttr(n.id) + '" data-target="' + escapeAttr(n.target_url || '') + '">' +
          '<span class="iw-notification-dot" aria-hidden="true"></span><span class="iw-notification-copy"><strong>' + escapeHtml(n.title || 'Notification') + '</strong><span>' + escapeHtml(n.message || '') + '</span><time>' + escapeHtml(n.created_at || '') + '</time></span></button>' +
          (isUnread ? '<div class="iw-notification-entry-actions"><button type="button" class="iw-mark-notification-read" data-mark-read-id="' + escapeAttr(n.id) + '">Mark as read</button></div>' : '') +
          '</div>';
      }).join('');
    }
    async function post(url, payload) {
      var response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify(Object.assign({_csrf: csrfToken}, payload || {}))
      });
      var data = await response.json().catch(function () { return {}; });
      if (!response.ok || data.success === false) throw new Error(data.error || 'Unable to update notification.');
      return data;
    }
    async function markRead(id) {
      try {
        var data = await post(markUrl, {id: Number(id)});
        setCount(data.unreadCount);
        var row = list.querySelector('[data-notification-id="' + CSS.escape(String(id)) + '"]');
        if (row) row.classList.remove('is-unread');
        await poll();
        return data;
      } catch (error) {
        return null;
      }
    }
    async function poll() {
      try {
        var response = await fetch(pollUrl, {method:'GET', credentials:'same-origin', cache:'no-store', headers:{'Accept':'application/json'}});
        if (!response.ok) return;
        var data = await response.json();
        var notifications = Array.isArray(data.latest) ? data.latest : [];
        setCount(data.unreadCount);
        notifications.forEach(function (n) {
          var id = String(n.id || '');
          if (!id) return;
          if (!firstPoll && !knownIds.has(id) && Number(n.is_read) === 0) showToast(n);
          knownIds.add(id);
        });
        firstPoll = false;
        render(notifications);
      } catch (error) {
        // Temporary connectivity failures should not break the current page.
      }
    }

    bell.addEventListener('click', function () {
      var open = panel.hidden;
      panel.hidden = !open;
      bell.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) poll();
    });
    dock.querySelector('#iwCloseNotifications').addEventListener('click', function () {
      panel.hidden = true;
      bell.setAttribute('aria-expanded', 'false');
      bell.focus();
    });
    markAll.addEventListener('click', async function () {
      if (markAll.disabled) return;
      markAll.disabled = true;
      try {
        var data = await post(markAllUrl, {});
        setCount(data.unreadCount);
        list.querySelectorAll('.iw-notification-row.is-unread').forEach(function (row) { row.classList.remove('is-unread'); });
        await poll();
      } catch (error) {
        summary.textContent = 'Could not mark all as read. Please retry.';
        markAll.disabled = false;
      }
    });
    list.addEventListener('click', function (event) {
      var markButton = event.target.closest('[data-mark-read-id]');
      if (markButton) {
        event.preventDefault();
        event.stopPropagation();
        var markId = markButton.getAttribute('data-mark-read-id');
        markButton.disabled = true;
        markButton.textContent = 'Marking…';
        markRead(markId).then(function (result) {
          if (!result) {
            markButton.disabled = false;
            markButton.textContent = 'Mark as read';
          }
        });
        return;
      }
      var row = event.target.closest('[data-notification-id]');
      if (!row) return;
      var id = row.getAttribute('data-notification-id');
      var target = safeTarget(row.getAttribute('data-target'));
      markRead(id).finally(function () {
        if (target) window.location.href = target;
        else poll();
      });
    });
    document.addEventListener('click', function (event) {
      if (!dock.contains(event.target) && !panel.hidden) {
        panel.hidden = true;
        bell.setAttribute('aria-expanded', 'false');
      }
    });
    poll();
    window.setInterval(poll, 20000);
    if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
