# InstaWeb Client Dashboard UI Rebuild Guide

> **Status:** Planning baseline  
> **Repository:** `lawreay/instaweb`  
> **Target branch:** `main`  
> **Scope:** Authenticated client workspace and its connected pages  
> **Purpose:** Establish the UX, visual system, implementation rules, JSON examples, phased delivery plan, and acceptance criteria before rebuilding begins.

---

## 1. Product goal

The client dashboard should make InstaWeb feel like a dependable website-management service, not a generic admin template. A client should be able to answer these questions quickly:

1. What websites belong to my account?
2. Which websites are published, in draft, or need attention?
3. How do I manage a website or open its live version?
4. Where do I manage files and hosting?
5. Where do I find account, billing, and support information?

The dashboard must prioritize useful actions and truthful status information over decorative metrics. Do not add charts or counters unless they support a real client decision.

### Success criteria

- A client can identify a website and open its live site in one obvious action.
- Main navigation remains visible and usable at desktop and mobile sizes.
- Every redesigned page uses a consistent design system.
- Existing business logic, routes, forms, CSRF protection, authorization, publishing and deletion flows continue to work.
- Empty, loading, unavailable, and error states are designed, not left as accidental blank spaces.
- UI data comes from the existing backend. Sample values in this guide are examples only.

## 2. Design direction

### Visual identity

Use a calm, professional interface:

- **Primary:** deep forest green for sidebar, primary buttons, and active navigation.
- **Accent:** restrained soft lime for selected highlights and small status accents.
- **Canvas:** warm off-white rather than pure white across the entire application.
- **Surfaces:** white or near-white cards with subtle borders.
- **Typography:** a readable system sans-serif stack; clear hierarchy and restrained weights.
- **Icons:** one consistent icon set already available in the project. Do not mix emoji, unrelated icon packs, and text glyphs for navigation.
- **Motion:** short, purposeful transitions only. Respect reduced-motion preferences.
- **Shadows:** subtle and rare. Prefer spacing and borders over stacks of floating cards.
- **Avoid:** neon gradients, glassmorphism everywhere, oversized empty hero areas, excessive rounded pills, fake charts, and animation that delays routine tasks.

### Design tokens JSON

This JSON is the documented source of design decisions. It is not automatically consumed by PHP. Implement the equivalent values as CSS custom properties in the project's shared dashboard stylesheet.

```json
{
  "brand": {
    "name": "InstaWeb",
    "palette": {
      "primary": "#153E35",
      "primaryHover": "#205747",
      "accent": "#B6D96C",
      "pageBackground": "#F7F6F0",
      "surface": "#FFFFFF",
      "surfaceMuted": "#F0F2EC",
      "text": "#182B25",
      "textMuted": "#6B756F",
      "border": "#E3E6DF",
      "success": "#287548",
      "successBackground": "#EAF5EC",
      "warning": "#A76D18",
      "warningBackground": "#FBF2DE",
      "danger": "#B43C3C",
      "dangerBackground": "#FBECEC",
      "info": "#315F91",
      "infoBackground": "#EAF1F8"
    }
  },
  "layout": {
    "sidebarExpanded": "248px",
    "sidebarCollapsed": "76px",
    "headerHeight": "72px",
    "contentMaxWidth": "1440px",
    "pagePaddingDesktop": "32px",
    "pagePaddingTablet": "24px",
    "pagePaddingMobile": "16px",
    "cardRadius": "12px",
    "controlRadius": "8px",
    "cardGap": "20px",
    "sectionGap": "28px"
  },
  "typography": {
    "fontFamily": "system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif",
    "pageTitle": "28px",
    "sectionTitle": "18px",
    "body": "14px",
    "metadata": "12px",
    "lineHeight": 1.5
  },
  "interaction": {
    "transitionDuration": "160ms",
    "focusRing": "2px solid #315F91",
    "minimumControlHeight": "42px",
    "respectReducedMotion": true
  }
}
```

### CSS token implementation example

```css
:root {
  --iw-primary: #153e35;
  --iw-primary-hover: #205747;
  --iw-accent: #b6d96c;
  --iw-page-bg: #f7f6f0;
  --iw-surface: #fff;
  --iw-surface-muted: #f0f2ec;
  --iw-text: #182b25;
  --iw-text-muted: #6b756f;
  --iw-border: #e3e6df;
  --iw-success: #287548;
  --iw-warning: #a76d18;
  --iw-danger: #b43c3c;
  --iw-sidebar-expanded: 248px;
  --iw-sidebar-collapsed: 76px;
  --iw-header-height: 72px;
  --iw-card-radius: 12px;
  --iw-control-radius: 8px;
  --iw-card-gap: 20px;
}

.client-dashboard {
  min-height: 100vh;
  color: var(--iw-text);
  background: var(--iw-page-bg);
}

.client-card {
  background: var(--iw-surface);
  border: 1px solid var(--iw-border);
  border-radius: var(--iw-card-radius);
}

.client-primary-button {
  min-height: 42px;
  padding: 10px 16px;
  border: 1px solid transparent;
  border-radius: var(--iw-control-radius);
  background: var(--iw-primary);
  color: #fff;
  font-weight: 600;
}

.client-primary-button:hover {
  background: var(--iw-primary-hover);
}

.client-primary-button:focus-visible,
.client-dashboard a:focus-visible,
.client-dashboard button:focus-visible,
.client-dashboard input:focus-visible {
  outline: 2px solid #315f91;
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .client-dashboard *,
  .client-dashboard *::before,
  .client-dashboard *::after {
    scroll-behavior: auto !important;
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

Treat this as a token and style example. Integrate with existing selectors and CSS loading order rather than pasting blindly and creating a second competing design system.

## 3. Application shell and navigation

### Desktop

- Sidebar stays visible while the client navigates.
- Expanded width target: 248px. Collapsed width target: 76px.
- Collapsed mode displays icons with accessible labels/tooltips; expanded mode displays icon and text.
- The client can manually toggle collapse/expand.
- Active route has a clear background and text treatment; it must not depend on color alone.
- Main content scrolls independently where appropriate. Avoid making both the entire page and nested panels scroll unexpectedly.
- Header includes breadcrumb/page context, notifications only if implemented, and account menu.
- Keep account/profile controls out of the primary site-action area.

### Mobile and tablet

- At mobile widths, use a compact top bar with a menu button and an accessible navigation drawer.
- Drawer opens/closes with keyboard and touch, closes after navigation, and does not trap focus incorrectly.
- Main content uses 16px page padding by default.
- Website cards stack vertically; buttons wrap or stack without horizontal overflow.
- Tables, file listings and forms must have explicit mobile behavior rather than shrinking into unreadable columns.
- Never rely on hover as the only way to reveal an action.

### Proposed navigation

The actual menu must reflect real functionality in the repository. Do not add routes merely because they appear in this guide.

| Item | Purpose | Rule |
|---|---|---|
| Overview | Account and website summary | Default client landing page |
| My Websites | List, create, edit, manage and publish sites | Core navigation item |
| File Manager | Manage files within authorized website scope | Preserve security boundaries |
| Hosting | Hosting plan and resource information | Show only if backend data exists |
| Domains | Domain configuration | Show only if supported |
| Billing | Invoices and payment history | Show only if supported |
| Help & Support | Help articles or support workflow | Do not link to a dead page |
| Account Settings | Profile, password and preferences | Preserve current account flows |

### Navigation configuration JSON example

This describes intent for documentation. Do not introduce a JSON-driven navigation engine unless the existing app already uses one.

```json
{
  "navigation": [
    {
      "id": "overview",
      "label": "Overview",
      "icon": "layout-dashboard",
      "routeName": "client.dashboard",
      "requiredCapability": "dashboard.view"
    },
    {
      "id": "websites",
      "label": "My Websites",
      "icon": "globe",
      "routeName": "client.sites.index",
      "requiredCapability": "sites.view"
    },
    {
      "id": "file-manager",
      "label": "File Manager",
      "icon": "folder",
      "routeName": "client.files.index",
      "requiredCapability": "files.view"
    },
    {
      "id": "hosting",
      "label": "Hosting",
      "icon": "server",
      "routeName": "client.hosting",
      "requiredCapability": "hosting.view",
      "optionalFeature": true
    },
    {
      "id": "billing",
      "label": "Billing",
      "icon": "credit-card",
      "routeName": "client.billing",
      "requiredCapability": "billing.view",
      "optionalFeature": true
    },
    {
      "id": "support",
      "label": "Help & Support",
      "icon": "life-buoy",
      "routeName": "client.support",
      "requiredCapability": "support.view",
      "optionalFeature": true
    },
    {
      "id": "settings",
      "label": "Account Settings",
      "icon": "settings",
      "routeName": "client.profile",
      "requiredCapability": "account.manage"
    }
  ]
}
```

**Important:** Route names above are illustrative placeholders, not a claim that these exact route names exist. During Phase 1, map each menu item to the repository's real routes and authorization rules. Hiding a menu item is not authorization; every controller/action must enforce permissions server-side.

## 4. Dashboard overview page

### Page order

1. Header/breadcrumb and clear title.
2. Short description that explains the page.
3. Primary action: **Create website**, only if supported and authorized.
4. Compact summary of total websites, published sites, and drafts/attention-needed sites.
5. Website cards.
6. Hosting summary, only when accurate data exists and clients benefit from seeing it.
7. Help/next-step panel when useful.

Do not let metrics dominate the website list. The core purpose is to manage websites.

### Website card anatomy

Each website card should contain:

- Thumbnail or generated neutral preview; do not fabricate a screenshot.
- Website name.
- Domain or a clear “Domain not configured” state.
- Site type: WordPress, another supported dynamic CMS, or static website, based on backend metadata.
- Publication status.
- Last updated timestamp only if meaningful and available.
- One primary action based on state.
- A secondary **Visit site** action when a valid live URL exists.
- A restrained overflow menu for secondary actions such as edit or delete.
- Clear feedback after publish, save, or failure.

### State-to-action mapping JSON

```json
{
  "websiteStates": {
    "draft": {
      "label": "Draft",
      "tone": "warning",
      "primaryAction": "Continue editing",
      "showVisitAction": false
    },
    "published": {
      "label": "Published",
      "tone": "success",
      "primaryAction": "Manage website",
      "showVisitAction": true
    },
    "publishing": {
      "label": "Publishing",
      "tone": "info",
      "primaryAction": "View progress",
      "showVisitAction": false
    },
    "error": {
      "label": "Needs attention",
      "tone": "danger",
      "primaryAction": "Review issue",
      "showVisitAction": false
    }
  }
}
```

This mapping is a UX specification, not a replacement for the application's real publishing state machine. Use only states the backend can actually determine. Never label a site “Published” merely because a user clicked a button.

### Website record/view-model JSON example

```json
{
  "id": 42,
  "name": "Business Website",
  "domain": "business.example.com",
  "siteType": "wordpress",
  "status": "published",
  "lastUpdated": "2026-10-18T14:30:00+02:00",
  "previewImage": null,
  "actions": {
    "manage": "/replace-with-existing-route/site/42",
    "visit": "https://business.example.com"
  }
}
```

All values are illustrative. In production, render server-provided values from the authenticated user's authorized records. Use the existing URL generator/router for internal links rather than hardcoding paths. Validate that a live URL is safe and valid before displaying/opening it. Do not fetch user-provided URLs server-side as part of rendering a card.

## 5. My Websites page

This page is the complete inventory and should remain useful when a client has many sites.

### Requirements

- Page title and create action.
- Search by site name/domain if the dataset justifies it.
- Filters for supported status and site type.
- Consistent cards or a table with a deliberate mobile layout. Prefer cards for a small inventory; avoid forcing a dense table on mobile.
- Sort order that is understandable and stable.
- Empty state with a direct create action.
- Clear pagination if needed; do not load unbounded records.
- Site actions must be permission-checked and scoped to the current client.
- Destructive actions require a clear confirmation and must explain what will be deleted.
- Never expose another client's site ID, filesystem path, credentials or metadata.

### Empty-state JSON example

```json
{
  "state": "empty",
  "title": "Your first website starts here",
  "description": "Create a website to begin managing your online presence.",
  "primaryAction": {
    "label": "Create website",
    "capability": "sites.create"
  }
}
```

The action object describes UI intent. It must be connected to the existing route/controller and permission check, not interpreted as permission granted by the client.

## 6. Website management page

The management page should focus on one website and its real capabilities.

### Recommended information hierarchy

- Site name, domain and status.
- Main action: Visit site, when published and valid.
- Site-specific tabs or sections based on current functionality, such as Overview, Files, Settings and Publishing.
- Environment/technology label when known, such as WordPress or static.
- Important domain, SSL, storage and update information only when reliable data is available.
- Clear warning/error messages near the affected action.
- Confirmation before destructive operations.

Do not promise WordPress administration, one-click updates, backups, SSL, storage quotas or deployment health unless those functions exist and are connected to the backend.

## 7. File Manager

The File Manager is a security-sensitive feature, not merely a visual file browser.

### UI requirements

- Breadcrumb path.
- File/folder name, type, size and modified date where available.
- Upload action and progress/error feedback if upload exists.
- Clear empty folder state.
- Context actions appropriate to file type and permission.
- Confirm delete; prevent accidental bulk deletion.
- Display an understandable permission or file-size error.
- Mobile list layout with actions accessible without hover.
- Never show raw server filesystem paths to the client.

### Security requirements

- Resolve every requested file path against the authenticated client's authorized website root.
- Prevent path traversal, symlink escapes and access to application/configuration secrets.
- Enforce server-side permissions for list, upload, read, rename and delete.
- Validate uploads by allowed type, size and storage policy. Do not trust the supplied MIME type or extension alone.
- Apply CSRF protection to state-changing form submissions.
- Do not expose hosting credentials, environment files, database backups or private keys.
- Avoid destructive operations that silently overwrite existing files.
- Keep error messages useful without revealing internal paths or stack traces.

Do not broaden the file manager's capabilities during a visual-only redesign. Preserve the current security model and flag gaps separately before extending functionality.

## 8. Shared header, footer and account pages

### Header

- Breadcrumb/page title when useful.
- Notifications only if a real notification feature exists.
- Account menu with profile/settings and sign-out actions.
- Accessible keyboard behavior and clear focus styles.

### Footer

- Keep it compact and consistent across client pages.
- Include help links and service/legal links only when valid.
- Do not force a footer to the viewport bottom with fragile absolute positioning if page content can overflow.
- Avoid a marketing-style footer inside every authenticated page.

### Profile and settings

- Preserve existing form fields, validation, CSRF tokens, profile partials, password flows and error/success feedback.
- Use consistent labels, helper text and field spacing.
- Keep save/cancel actions easy to find.
- Never remove backend-supported fields as part of a visual-only change without explicit product approval.

## 9. Accessibility and responsive behavior

Minimum expectations:

- Semantic headings in logical order.
- Every input has an associated visible label.
- Buttons and links have meaningful accessible names.
- Icon-only controls have accessible labels and tooltips where appropriate.
- Keyboard navigation works across sidebar, menus, dialogs and forms.
- Focus indicator is visible.
- Status is conveyed by text and not color alone.
- Text contrast is checked against WCAG AA targets.
- Layout works at 320px, 375px, 768px, 1024px and wide desktop widths.
- No unintended horizontal overflow.
- Touch targets are comfortably usable; aim for approximately 44px where practical.
- Reduced-motion preference is respected.
- Error messages are associated with the relevant fields and announced appropriately.

## 10. Implementation rules for this repository

Before changing code, inspect the current default branch and existing implementation. Recent dashboard/page-polish work has been merged, so do not assume old files, route names or CSS selectors still apply.

### Required inspection

1. Read `docs/architecture.md` and `docs/features.md`.
2. Inspect the current dashboard home view and its controller/data source.
3. Inspect shared client sidebar, header and footer partials.
4. Inspect the current client dashboard and page-specific stylesheets, including stylesheet load order.
5. Map each visible navigation item and card action to its actual route/controller.
6. Identify how the current app represents WordPress, static websites, publication status and domains.
7. Inspect authorization, CSRF protection, validation and ownership checks on affected actions.
8. Check the repository's existing tests and CI workflow.

### Engineering constraints

- Keep the existing PHP application architecture. Do not introduce React/Vue or a frontend build pipeline solely for this redesign.
- Reuse existing views, partials, route names, controllers and models.
- Keep controllers and business logic unchanged unless a demonstrated defect requires a separately scoped fix.
- Do not duplicate shared layout markup across pages.
- Scope CSS to the client workspace. Avoid global selectors that change admin, public landing pages or unrelated forms.
- Use the current project's icon assets/library if available.
- Avoid new dependencies unless a clear requirement cannot be met with the current stack.
- Preserve authentication, authorization, CSRF, validation, upload protections and audit behavior.
- Do not invent backend data or fake status indicators.
- Do not perform unrelated cleanup or feature work in this branch.
- Do not edit production credentials, secrets, user uploads or runtime data.
- Keep changes small and reviewable; commit one coherent improvement at a time.

### Example CSS architecture

Adapt this example to actual files; do not create duplicate stylesheets if suitable existing files already serve these purposes.

```text
public/assets/css/
  client-dashboard.css         # Shared shell, tokens, sidebar, header, base components
  client-dashboard-pages.css   # Scoped styling for website/profile/notification pages
```

### Example component class naming

```text
.client-dashboard
.client-sidebar
.client-sidebar__toggle
.client-sidebar__link
.client-header
.client-page
.client-page__header
.client-card
.client-website-card
.client-website-card__preview
.client-website-card__actions
.client-status
.client-empty-state
.client-form
```

Use the established naming convention if the current codebase already has one. Consistency is more important than renaming every class.

## 11. Phased delivery plan

### Phase 0: Audit and baseline

- Read project docs and inspect current `main`.
- Record actual client routes, views, CSS files and functionality.
- Capture screenshots at desktop and mobile widths.
- Reproduce and document the missing/invisible sidebar issue if it still exists.
- Check for duplicate CSS rules, selector collisions and stylesheet order problems.
- Establish a list of existing flows that must not regress.

**Exit criteria:** audit notes identify exact files and actual routes; no implementation begins from assumed paths.

### Phase 1: Shared shell and responsive sidebar

- Standardize tokens and base layout.
- Fix expanded/collapsed sidebar behavior.
- Add accessible labels/tooltips for icon-only mode.
- Ensure active-route styling and keyboard navigation.
- Implement mobile drawer behavior.
- Align header and footer across client pages.
- Verify the public landing page and admin area have not been unintentionally restyled.

**Exit criteria:** sidebar works at desktop/tablet/mobile widths; no content overlap or hidden navigation.

### Phase 2: Dashboard overview

- Refine title, primary action and page spacing.
- Build clear, compact summary cards from real data.
- Improve website cards and status presentation.
- Implement accurate primary and Visit Site actions.
- Add empty/loading/error states.
- Add hosting summary only where backed by reliable data.

**Exit criteria:** a client can identify and open a published website quickly; drafts are clearly distinguished; counts match backend records.

### Phase 3: My Websites and management pages

- Align list/search/filter controls with the shared system.
- Polish create/edit/manage forms without changing their semantics.
- Preserve validation and CSRF.
- Improve confirmation dialogs and success/error feedback.
- Ensure WordPress/dynamic and static websites are represented accurately.
- Verify one-click Visit Site behavior.

**Exit criteria:** existing create/edit/publish/delete flows still work and are correctly authorized.

### Phase 4: File Manager and supporting pages

- Standardize file list, breadcrumbs, upload and action controls.
- Validate empty/error/loading states.
- Test authorization and path-scope protections.
- Align account profile and notification pages.
- Show only navigation for supported features.

**Exit criteria:** file operations remain scoped and secure; supporting pages share the visual language.

### Phase 5: Quality assurance and merge

- Run PHP syntax checks on changed PHP files.
- Run relevant automated tests and CI.
- Test navigation and actions with an authenticated client.
- Test desktop, tablet and mobile layouts.
- Check browser console and network errors.
- Verify a client cannot access another client's website by changing an ID or URL.
- Check keyboard/focus behavior and contrast.
- Review the diff for unrelated changes and accidental data/secret files.
- Commit each coherent change with a descriptive message.
- Merge only after acceptance criteria pass.

**Exit criteria:** tests pass, core flows work, responsive QA is complete, and no known critical security or navigation regressions remain.

## 12. Commit strategy

Prefer small commits that represent one reviewable change. Example sequence:

```text
docs: add client dashboard UI rebuild guide
style(client): define dashboard design tokens
fix(client): restore responsive sidebar visibility
style(client): standardize shared dashboard shell
style(client): improve dashboard overview layout
style(client): refine website cards and status states
style(client): polish website management pages
style(client): improve file manager responsive layout
test(client): cover dashboard navigation and ownership checks
```

These are proposed commit messages, not claims that those commits already exist. Do not create empty commits or split a trivial edit into meaningless fragments.

## 13. Quality checklist

Use this checklist before considering the redesign complete.

### Visual consistency
- [ ] Design tokens are centralized.
- [ ] Sidebar, header, cards, forms and buttons share consistent styling.
- [ ] Typography and spacing are consistent.
- [ ] No unrelated public/admin pages are affected.

### Navigation and behavior
- [ ] Expanded/collapsed sidebar works.
- [ ] Mobile drawer works with keyboard and touch.
- [ ] Active navigation matches the current route.
- [ ] Create, manage, edit and Visit Site actions lead to correct destinations.
- [ ] Invalid or unavailable actions are not presented as working.
- [ ] Empty, loading, error and success states are covered.

### Backend and security
- [ ] All displayed data comes from authorized records.
- [ ] Ownership and permission checks remain server-side.
- [ ] CSRF and validation are preserved.
- [ ] File manager cannot escape the authorized website root.
- [ ] No secrets, raw filesystem paths or other clients' data are exposed.
- [ ] Status and hosting information are truthful.

### Responsive and accessibility
- [ ] Tested at 320px, 375px, 768px, 1024px and desktop.
- [ ] No unintended horizontal scrolling.
- [ ] Keyboard and focus behavior work.
- [ ] Status does not rely on color alone.
- [ ] Contrast and reduced-motion behavior are checked.

### Delivery
- [ ] PHP syntax checks pass.
- [ ] Relevant tests and CI pass.
- [ ] Browser QA is complete.
- [ ] Diff contains no unrelated changes.
- [ ] Commits are small and descriptive.
- [ ] Known limitations are documented.

## 14. Out of scope unless separately approved

Do not expand this UI task into:

- Rewriting the app in React or another framework.
- Replacing the router or backend architecture.
- Building a new billing/payment system.
- Building a new notification system.
- Adding analytics charts with no defined client use case.
- Adding hosting controls that do not exist.
- Reworking WordPress deployment internals as part of a CSS task.
- Changing authentication or permissions merely to simplify UI code.
- Broad refactors unrelated to dashboard behavior.

If the audit reveals a security or correctness defect, document it and fix it in a focused change where necessary. Visual polish must never hide a broken or unsafe workflow.

## 15. Final implementation instruction

Treat this document as the UI/UX baseline, not permission to blindly overwrite the current dashboard. Begin with Phase 0. Inspect the live repository state and map the plan to the actual views, routes, and stylesheets. Make the smallest change that produces a measurable improvement, preserve existing behavior, and verify each phase before moving on.

**Product principle:** clients come to manage their websites, not admire the dashboard. The interface should make the correct action obvious and the dangerous action difficult.
