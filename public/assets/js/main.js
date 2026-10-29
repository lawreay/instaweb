/* ============================================
   MODERN PUBLIC HEADER
   ============================================ */
function initializePublicHeader() {
  const header = document.getElementById('publicHeader');
  const menuToggle = document.getElementById('headerMenuToggle');
  const menuOverlay = document.getElementById('headerMenuOverlay');
  const mobileMenu = document.getElementById('headerMobileMenu');
  const menuClose = document.getElementById('headerMenuClose');

  if (!header) return;

  // Handle scroll effect
  let lastScrollY = 0;
  const handleScroll = () => {
    const scrollY = window.scrollY;
    
    if (scrollY > 20) {
      if (!header.classList.contains('scrolled')) {
        header.classList.add('scrolled');
      }
    } else {
      header.classList.remove('scrolled');
    }
    
    lastScrollY = scrollY;
  };

  window.addEventListener('scroll', handleScroll, { passive: true });

  // Handle mobile menu toggle
  const toggleMenu = (show) => {
    if (show) {
      mobileMenu.style.display = 'flex';
      menuOverlay.style.display = 'block';
      menuToggle.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    } else {
      mobileMenu.style.display = 'none';
      menuOverlay.style.display = 'none';
      menuToggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }
  };

  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
      toggleMenu(!isOpen);
    });
  }

  if (menuOverlay) {
    menuOverlay.addEventListener('click', () => toggleMenu(false));
  }

  if (menuClose) {
    menuClose.addEventListener('click', () => toggleMenu(false));
  }

  // Close menu when clicking on a link
  if (mobileMenu) {
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => toggleMenu(false));
    });
  }

  // Close menu on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menuToggle?.getAttribute('aria-expanded') === 'true') {
      toggleMenu(false);
    }
  });
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializePublicHeader);
} else {
  initializePublicHeader();
}

function initializeResponsiveSidebars() {
  const createToggleButton = (type, container, label) => {
    const existing = container.querySelector(`[data-${type}-sidebar-toggle]`);
    if (existing) {
      return existing;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = type === 'client' ? 'client-sidebar-toggle' : 'admin-sidebar-toggle';
    button.setAttribute('aria-label', label);
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute(`data-${type}-sidebar-toggle`, '');
    button.innerHTML = '<i data-lucide="menu"></i><span class="sidebar-toggle-label">Menu</span>';

    const headerTarget = type === 'client'
      ? container.querySelector('.client-header .client-header-actions, .client-header')
      : container.querySelector('.admin-pro-topbar .admin-pro-topbar-right, .admin-pro-topbar');

    if (headerTarget) {
      headerTarget.prepend(button);
    } else {
      container.prepend(button);
    }

    return button;
  };

  const createOverlay = (type, container) => {
    const existing = container.querySelector(`[data-${type}-sidebar-overlay]`);
    if (existing) {
      return existing;
    }

    const overlay = document.createElement('div');
    overlay.className = type === 'client' ? 'client-sidebar-overlay' : 'admin-sidebar-overlay';
    overlay.setAttribute(`data-${type}-sidebar-overlay`, '');
    container.prepend(overlay);
    return overlay;
  };

  document.querySelectorAll('.client-dashboard').forEach((dashboard) => {
    const sidebar = dashboard.querySelector('.client-sidebar');
    if (!sidebar) {
      return;
    }

    const toggle = createToggleButton('client', dashboard, 'Open navigation menu');
    const overlay = createOverlay('client', dashboard);

    const closeSidebar = () => {
      dashboard.classList.remove('sidebar-open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    };

    const openSidebar = () => {
      dashboard.classList.add('sidebar-open');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    };

    toggle.addEventListener('click', () => {
      if (dashboard.classList.contains('sidebar-open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    overlay.addEventListener('click', closeSidebar);
    sidebar.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeSidebar);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && dashboard.classList.contains('sidebar-open')) {
        closeSidebar();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 1024) {
        closeSidebar();
      }
    });
  });

  // The redesigned admin dashboard provides its own controller for its compact
  // sidebar. Keep this legacy controller for the remaining admin screens only.
  document.querySelectorAll('.admin-pro-shell:not([data-admin-shell])').forEach((shell) => {
    const sidebar = shell.querySelector('.admin-pro-sidebar');
    if (!sidebar) {
      return;
    }

    const toggle = createToggleButton('admin', shell, 'Open admin menu');
    const overlay = createOverlay('admin', shell);

    const closeSidebar = () => {
      shell.classList.remove('sidebar-open');
      toggle.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    };

    const openSidebar = () => {
      shell.classList.add('sidebar-open');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    };

    toggle.addEventListener('click', () => {
      if (shell.classList.contains('sidebar-open')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    overlay.addEventListener('click', closeSidebar);
    sidebar.querySelectorAll('a, button').forEach((link) => {
      link.addEventListener('click', closeSidebar);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && shell.classList.contains('sidebar-open')) {
        closeSidebar();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 1024) {
        closeSidebar();
      }
    });
  });
}

function initializeCompactDashboardHeader() {
  document.querySelectorAll('.client-dashboard').forEach((dashboard) => {
    const header = dashboard.querySelector('.client-header');
    const scrollArea = dashboard.querySelector('.client-main');

    if (!header || !scrollArea) {
      return;
    }

    const syncCompactState = () => {
      header.classList.toggle('is-compact', scrollArea.scrollTop > 16 || window.scrollY > 16);
    };

    scrollArea.addEventListener('scroll', syncCompactState, { passive: true });
    window.addEventListener('scroll', syncCompactState, { passive: true });
    syncCompactState();
  });
}

function initializeMarketingHero() {
  const revealEls = document.querySelectorAll('.reveal, .reveal-stagger');
  if (revealEls.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }
        entry.target.classList.add('in');
        io.unobserve(entry.target);
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -60px 0px',
    });

    revealEls.forEach((el) => io.observe(el));
  }

  const target = document.getElementById('typed-code');
  const badge = document.getElementById('publishBadge');

  if (!target || !badge) {
    return;
  }

  const codeLines = [
    { text: '<', cls: 'tag' },
    { text: 'section', cls: 'tag' },
    { text: ' class=', cls: '' },
    { text: '"hero"', cls: 'str' },
    { text: '>', cls: 'tag' },
    { text: '\n  ', cls: '' },
    { text: '<h1>', cls: 'tag' },
    { text: 'My New Site', cls: '' },
    { text: '</h1>', cls: 'tag' },
    { text: '\n  ', cls: '' },
    { text: '<p>', cls: 'tag' },
    { text: 'Published by TurboHostMw', cls: '' },
    { text: '</p>', cls: 'tag' },
    { text: '\n', cls: '' },
    { text: '</section>', cls: 'tag' },
     { text: 'DEVELOPED BY LAWRENCE PHUKA ', cls: '' },
  ];

  let lineIndex = 0;
  let charIndex = 0;

  function typeStep() {
    if (lineIndex >= codeLines.length) {
      badge.classList.add('show');
      setTimeout(() => {
        badge.classList.remove('show');
        target.innerHTML = '';
        lineIndex = 0;
        charIndex = 0;
        setTimeout(typeStep, 600);
      }, 2200);
      return;
    }

    const line = codeLines[lineIndex];
    if (charIndex === 0) {
      const span = document.createElement('span');
      if (line.cls) {
        span.className = line.cls;
      }
      span.dataset.full = line.text;
      target.appendChild(span);
    }

    const currentSpan = target.lastElementChild;
    charIndex += 1;
    currentSpan.textContent = line.text.slice(0, charIndex);

    if (charIndex >= line.text.length) {
      lineIndex += 1;
      charIndex = 0;
    }

    setTimeout(typeStep, 22 + Math.random() * 20);
  }

  typeStep();
}

function initializeTurboHostFrontend() {
  initializeResponsiveSidebars();
  initializeCompactDashboardHeader();
  initializeMarketingHero();

  if (window.lucide) {
    window.lucide.createIcons();
  }

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-settings-tab]');

    if (!button) {
      return;
    }

    event.preventDefault();

    const target = button.dataset.settingsTab;

    document.querySelectorAll('[data-settings-tab]').forEach((tab) => {
      tab.classList.toggle('active', tab === button);
    });

    document.querySelectorAll('[data-settings-panel]').forEach((panel) => {
      panel.classList.toggle('active', panel.dataset.settingsPanel === target);
    });
  });

  const appearanceInputs = document.querySelectorAll('[name="primary_color"], [name="secondary_color"], [name="text_color"], [name="muted_text_color"], [name="page_background_color"], [name="surface_background_color"], [name="surface_border_color"], [name="sidebar_background_color"], [name="sidebar_text_color"], [name="dashboard_logo"], [name="admin_logo"], [name="app_icon_url"]');
  const previewLogoImage = document.getElementById('preview-logo-image');
  const previewLogoPlaceholder = document.getElementById('preview-logo-placeholder');
  const previewIconImage = document.getElementById('preview-icon-image');
  const previewIconLetter = document.getElementById('preview-icon-letter');

  const updateAppearancePreview = () => {
    const primary = document.querySelector('[name="primary_color"]')?.value || '#0F766E';
    const secondary = document.querySelector('[name="secondary_color"]')?.value || '#0F766E';
    const text = document.querySelector('[name="text_color"]')?.value || '#00922e';
    const muted = document.querySelector('[name="muted_text_color"]')?.value || '#5B6472';
    const pageBackground = document.querySelector('[name="page_background_color"]')?.value || '#FFFFFF';
    const surfaceBackground = document.querySelector('[name="surface_background_color"]')?.value || '#ffffff';
    const surfaceBorder = document.querySelector('[name="surface_border_color"]')?.value || 'rgba(15, 23, 42, 0.08)';
    const sidebarBg = document.querySelector('[name="sidebar_background_color"]')?.value || '#FFFFFF';
    const sidebarText = document.querySelector('[name="sidebar_text_color"]')?.value || '#475569';
    const logoUrl = document.querySelector('[name="dashboard_logo"]')?.value || '';
    const iconUrl = document.querySelector('[name="app_icon_url"]')?.value || '';

    document.documentElement.style.setProperty('--app-primary', primary);
    document.documentElement.style.setProperty('--app-secondary', secondary);
    document.documentElement.style.setProperty('--app-text', text);
    document.documentElement.style.setProperty('--app-muted', muted);
    document.documentElement.style.setProperty('--app-background', pageBackground);
    document.documentElement.style.setProperty('--surface-bg', surfaceBackground);
    document.documentElement.style.setProperty('--surface-border', surfaceBorder);
    document.documentElement.style.setProperty('--sidebar-bg', sidebarBg);
    document.documentElement.style.setProperty('--sidebar-text', sidebarText);
    document.documentElement.style.setProperty('--brand-bg', primary);

    if (previewLogoImage && previewLogoPlaceholder) {
      if (logoUrl.trim()) {
        previewLogoImage.src = logoUrl.trim();
        previewLogoImage.style.display = 'block';
        previewLogoPlaceholder.style.display = 'none';
      } else {
        previewLogoImage.style.display = 'none';
        previewLogoPlaceholder.style.display = 'grid';
      }
    }

    if (previewIconImage && previewIconLetter) {
      if (iconUrl.trim()) {
        previewIconImage.src = iconUrl.trim();
        previewIconImage.style.display = 'block';
        previewIconLetter.style.display = 'none';
      } else {
        previewIconImage.style.display = 'none';
        previewIconLetter.style.display = 'block';
      }
    }
  };

  appearanceInputs.forEach((input) => {
    input.addEventListener('input', updateAppearancePreview);
  });

  updateAppearancePreview();
  initializeNotificationPolling();
}

function initializeNotificationPolling() {
  const panel = document.querySelector('[data-notification-panel]');
  const baseUrl = document.querySelector('meta[name="turbohost-base-url"]')?.content;
  const csrfToken = document.querySelector('meta[name="instaweb-csrf"]')?.content || '';
  if (!panel || !baseUrl) {
    return;
  }

  const items = panel.querySelector('#notificationItems');
  const countNode = panel.querySelector('#notificationCount');
  const apiUrl = `${baseUrl}/api/notifications/poll`;

  async function fetchNotifications() {
    try {
      const response = await fetch(apiUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
        },
      });

      if (!response.ok) {
        return;
      }

      const payload = await response.json();
      const notifications = Array.isArray(payload.latest) ? payload.latest : [];
      const countText = payload.unreadCount > 0 ? `${payload.unreadCount} unread` : `${notifications.length} latest`;

      if (countNode) {
        countNode.textContent = countText;
      }

      if (!items) {
        return;
      }

      if (notifications.length === 0) {
        items.innerHTML = '<p class="text-secondary mb-0">No notifications yet.</p>';
        return;
      }

      items.innerHTML = notifications.map((notification) => `
        <div class="notification-item ${notification.is_read == 0 ? 'unread' : 'read'}" data-id="${notification.id || ''}" data-target="${notification.target_url || ''}">
          <div>
            <strong>${notification.title ? escapeHtml(notification.title) : 'Notification'}</strong>
            <span>${notification.message ? escapeHtml(notification.message) : ''}</span>
          </div>
          <small>${escapeHtml(notification.created_at || '')}</small>
        </div>
      `).join('');

      // Attach click handler to mark as read and navigate if target exists
      items.querySelectorAll('.notification-item').forEach(function (node) {
        node.addEventListener('click', async function () {
          const id = node.getAttribute('data-id');
          const target = node.getAttribute('data-target');
          if (id) {
            try {
              await fetch(`${baseUrl}/api/notifications/mark-read`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ id: parseInt(id, 10), _csrf: csrfToken })
              }).then(res => res.json()).then((payload) => {
                if (payload && typeof payload.unreadCount !== 'undefined' && countNode) {
                  countNode.textContent = payload.unreadCount > 0 ? `${payload.unreadCount} unread` : `${notifications.length} latest`;
                }
              });
            } catch (e) {
              // ignore
            }
            node.classList.remove('unread');
            node.classList.add('read');
          }

          if (target) {
            window.location.href = target;
          }
        });
      });
    } catch (error) {
      // Silent fail; polling is progressive enhancement.
    }
  }

  fetchNotifications();
  setInterval(fetchNotifications, 30000);
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeTurboHostFrontend);
} else {
  initializeTurboHostFrontend();
}
