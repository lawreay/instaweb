# Client Dashboard UI Rebuild: Phase 0 Audit

**Repository:** `lawreay/instaweb`  
**Base audited:** `main` at `27321e4cd879d5177f6c2317b09cd6bbacc2a1a3`  
**Audit branch:** `feature/client-dashboard-ui-rebuild`  
**Date:** 2026-10-19  
**Scope:** Current client dashboard shell, dashboard home, website index, route map, and relevant CSS/JS.  
**Status:** Static source audit completed; browser QA not yet performed.

## Executive finding

The reported missing sidebar is explained by the current markup, not just by a styling defect: the shared authenticated client layout currently renders a **top navigation header** (`client-app-header`) and does not render the `client-sidebar` element. The existing sidebar JavaScript only initializes when it finds `.client-sidebar` inside a `.client-dashboard` workspace. The dashboard home view uses `.iw-home` and does not include that workspace/sidebar markup. Therefore, sidebar CSS and JavaScript cannot make the sidebar appear on the dashboard home.

**Recommended fix:** implement one shared client shell in `app/Views/layouts/dashboard.php` with a real sidebar and a header/content layout. Do not add separate sidebar markup to each page. Keep the current PHP MVC architecture and existing route/controller behavior.

## Files inspected

- `docs/architecture.md`
- `docs/features.md`
- `app/Views/layouts/dashboard.php`
- `app/Views/dashboard/index.php`
- `app/Views/websites/index.php`
- `app/Config/routes.php`
- `app/Controllers/DashboardController.php`
- `public/assets/css/client-dashboard.css`
- `public/assets/css/client-dashboard-home.css`
- `public/assets/css/client-dashboard-pages.css`

## Findings

### F0-1 — Sidebar markup is missing from the shared client layout

**Evidence:** `app/Views/layouts/dashboard.php` renders `<header class="client-app-header">` with brand, mobile menu toggle, and a horizontal `#clientHeaderNav`. The layout then renders `$content` and the footer. It does not render `.client-sidebar`.

The layout's sidebar script later searches for `.client-sidebar` within each `.client-dashboard`, but the sidebar element is not present in the shared layout. The home view begins with `<main class="iw-home">`, so it does not even match the `.client-dashboard` selector used by that script.

**Impact:** sidebar visibility cannot be corrected reliably through CSS alone. The shared layout must own the sidebar markup and page shell.

**Priority:** P0.

### F0-2 — Multiple client layout patterns coexist

The dashboard home uses `.iw-home` and its own page design. Website/account pages use `.iw-client-page` / `.client-dashboard` patterns. Three separate dashboard-related stylesheets are loaded by the shared layout:

- `public/assets/css/client-dashboard.css`
- `public/assets/css/client-dashboard-home.css`
- `public/assets/css/client-dashboard-pages.css`

**Impact:** design tokens and layout behavior are distributed across overlapping selectors. This increases the chance that fixes apply on one page but not another.

**Recommendation:** do not rewrite all CSS at once. First establish the shared shell and tokens, then migrate only the necessary page styles in small changes. Preserve page-specific styles where they are still needed.

### F0-3 — Existing palette values conflict

The layout emits dynamic platform CSS variables with defaults such as teal `#0F766E`, while the dashboard home/pages independently define forest/olive colors such as `#182522`, `#f4f7f3`, and `#b9e769`. Older dashboard rules also reference blue/teal variables.

**Impact:** controls, links, focus rings and shared shell can appear to belong to different products.

**Recommendation:** define the client workspace tokens in one scoped place and migrate relevant rules incrementally. Do not break administrator theme settings or the public marketing site.

### F0-4 — Navigation route mapping must follow actual routes

The current route map includes:

- `/dashboard`
- `/dashboard/notifications`
- `/dashboard/websites`
- `/dashboard/websites/create`
- `/dashboard/websites/show`
- `/dashboard/websites/edit`
- `/dashboard/websites/files`
- `/dashboard/websites/editor`
- `/dashboard/websites/preview`
- `/profile`

There are working route definitions for website and file workflows. Proposed guide labels such as Hosting, Domains, Billing, and Support must not be added to the sidebar unless the current application exposes the corresponding client-facing functionality and routes.

**Recommendation:** first navigation should include only real, useful destinations, for example Overview, My Websites, Notifications (if useful in the existing app), and Account. File Manager should be reached through a selected website or a real global view that correctly scopes the user's access. Sign-out must preserve its existing POST/CSRF behavior.

### F0-5 — Website actions already exist and should be preserved

The home and website index views already display website names/status and conditionally expose actions including Visit Site, Manage, Continue Editing, and File Manager. The redesign should preserve these workflows and improve their layout rather than inventing new actions.

**Caution:** the public URL is rendered from website data. Do not add server-side fetching of client-provided URLs. Preserve safe external-link attributes and validate the expected URL scheme at the relevant boundary if that behavior is changed.

### F0-6 — Dashboard home contains a large promotional hero and four metrics

The home view currently renders a large marketing-style hero followed by four summary metrics (websites, published, storage, bandwidth), then website/file panels.

**Recommendation:** after the shell is fixed, reduce unnecessary vertical competition: keep a concise greeting, the primary Create Website action, compact useful metrics, and make the website list visually dominant. Keep bandwidth only if the displayed value is accurate and useful. This is a layout refinement, not a backend feature request.

### F0-7 — Automated PHP syntax workflow passes, but this is not browser QA

The workflow `PHP syntax checks` ran successfully for the guide commit on `main` (run 115). This verifies the documented commit passed that workflow, not that the dashboard renders correctly or that navigation, mobile interactions, authorization, and file operations have been browser-tested.

**Remaining validation:** browser-level desktop/mobile QA, console/network inspection, authenticated user workflow tests, and ownership/security regression tests.

### F0-8 — Repository has several historical feature branches

The repository contains multiple older client dashboard/sidebar redesign branches, while the open-pull-request list was empty during this audit. Avoid blindly merging old branches. They may contain stale versions of the UI or duplicate attempts to solve the same issue.

**Recommendation:** implement from current `main` on `feature/client-dashboard-ui-rebuild`, one focused commit at a time. Cherry-pick or reuse historical work only after comparing it to current `main` and confirming it is still needed.

## Phase 1 implementation plan

1. Add a single shared client sidebar to `app/Views/layouts/dashboard.php`; remove redundant horizontal navigation only after the replacement works.
2. Sidebar navigation uses verified existing routes. Add an accessible collapse/expand control for desktop, icon tooltips/labels in collapsed mode, and a mobile drawer.
3. Make the shell layout account for sidebar width and the main content; prevent fixed-height and overflow rules from hiding navigation or page content.
4. Keep the dashboard home and secondary pages inside the same shared shell without duplicating sidebar markup.
5. Scope all new rules to the client workspace. Avoid affecting `layouts/main.php` (public marketing) or admin layout/views.
6. Preserve existing route names, forms, CSRF, permissions, ownership checks, website actions, and footer links.
7. Commit each coherent change separately and run the PHP syntax workflow.
8. Browser-test at 320px, 375px, 768px, 1024px, and desktop widths before claiming the UI is fixed.

## Acceptance criteria for Phase 1

- [ ] Sidebar markup is present exactly once through the shared client layout.
- [ ] Dashboard home, My Websites, website details, file manager, and profile pages all display the same shell.
- [ ] Desktop collapse/expand works without hiding content.
- [ ] Mobile navigation opens/closes and does not cause horizontal overflow.
- [ ] Active navigation reflects the current route.
- [ ] Every visible destination maps to a real route and preserves authorization.
- [ ] Sign-out and existing account workflows remain functional.
- [ ] Public marketing and admin layouts are unaffected.
- [ ] PHP syntax workflow passes.
- [ ] Browser QA has been completed and recorded.

## Explicit non-goals

- No React/Vue migration.
- No new billing, domain, support, hosting-control, or analytics features.
- No backend rewrite.
- No broad CSS rewrite before the shared shell is stable.
- No claim of browser validation until a real browser run has been completed.

**Technical recommendation:** fix the missing shared markup and layout ownership first. More CSS applied to an element that is not in the DOM is not a redesign strategy.
