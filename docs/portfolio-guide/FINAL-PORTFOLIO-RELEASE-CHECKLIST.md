# Final Portfolio Editor & Template Release Plan

**Repository:** `lawreay/instaweb`  
**Implementation branch reviewed:** `feature/portfolio-editor-full-dashboard`  
**Reference only:** `lawreay/Custom-portfolios` (kept unchanged); the root-level `idea.php` and `ideas2.php` files have been removed from InstaWeb  
**Document purpose:** final implementation checklist, acceptance contract, and honest record of verified work  
**Review date:** 2026-10-25

> **Release rule:** This document is the final planned pass, not permission to label untested work complete. A feature is marked **Complete** only when implementation and the relevant test are evidenced. Code being present or passing syntax checks does not prove the full user journey works. Do not merge or call this production-ready until all release-blocking checks in Phase 8 pass.

## Status legend

- **Complete**: implementation is present and the stated verification has evidence.
- **Partial**: implementation exists, but important behavior, coverage, or verification remains.
- **Not started / Missing**: no verified implementation found in the reviewed branch.
- **Blocked**: cannot be signed off until an external prerequisite is confirmed.
- **Not applicable**: intentionally outside this release.

## Current evidence-based summary

| Area | Status | Evidence / qualification |
|---|---|---|
| InstaWeb-owned portfolio editor and owner checks | **Partial** | Routes/controllers and website-owner lookups exist. Cross-account runtime tests have not been evidenced. |
| Tabbed Advanced Editor | **Implemented; browser QA pending** | `app/Views/portfolio/advanced.php` has Overview, Profile, Design, section tabs, Projects, URL tab retention, and now generates its template selector from the server-side ten-template allowlist. Mobile/keyboard/browser checks are not evidenced. |
| Profile, social links, avatar, projects | **Implemented; runtime QA pending** | Controller/model/view paths exist. Avatar validation and owner-scoped project updates are present; database and upload behavior still need local verification. |
| Nine structured content sections | **Implemented; end-to-end QA pending** | Goals, Interests, Challenges, Experience, Education, Skills, News, Partnerships, and CV/Resume have CRUD/visibility UI and controller/model paths. Some source-reference features are not ported. |
| Ten template identifiers | **Wired in code; visual parity partial** | `PortfolioController::TEMPLATES` and `portfolio/public.php` list Classic, Editorial, 3D Motion, Minimal, Noir, Aurora Motion, Studio Canvas, Orbit 3D, Prism, and Archive. Selection and renderer paths exist, but browser proof of all ten is outstanding. |
| React-powered template family | **Implemented; production verification pending** | `react-portfolio.js` uses React and Framer Motion and has distinct CSS variants. The PHP fallback now remains visible until the React component mounts. It is one shared React renderer with template-specific styling, not ten independent React applications. External CDN loading and each template's visual identity need browser testing. |
| Draft preview and public publishing lifecycle | **Partial** | Preview/public controller and renderer code exist. Confirm private draft access, published-only public access, unpublish behavior, slug resolution, and the real hosting route in a running environment. |
| Security controls | **Partially implemented; security sign-off pending** | CSRF, owner-scoped lookups, HTTP/HTTPS URL validation, output escaping, image checks, randomized filenames, and upload script blocking are present in reviewed paths. Public rendering now revalidates stored project URLs before exposing them to PHP or React templates. Adversarial and cross-user tests are not evidenced. |
| Reference-file cleanup | **Complete on working branch** | Removed root-level `idea.php` and `ideas2.php` from `feature/portfolio-editor-full-dashboard`; verified both paths return GitHub 404 on that branch. They are not yet removed from `main` until the PR is merged. |
| Syntax / automated checks | **Latest code change CI passed** | GitHub Actions run 40215882457 completed successfully for code commit `391b77ae64dcbb77555a46423056ad8fafd450fc`; PHP lint, React module syntax, and the static integration guard passed. The checklist-only documentation commit follows that code commit. This is not browser/database runtime verification. |
| Production readiness | **Not signed off** | Runtime database, browser, hosting, accessibility, performance, and cross-user checks remain release gates. |

## Non-negotiable product rules

1. All implementation changes stay in **InstaWeb**. Do not edit the separate `Custom-portfolios` repository.
2. Keep InstaWeb as the only authentication, account, website ownership, editor, preview, publish, and unpublish system. No second login or copied app per customer.
3. Each portfolio's data and settings must be scoped to its InstaWeb website and authenticated owner.
4. Store content once. A template switch changes presentation, not the user's saved content.
5. Use a controlled, server-side template allowlist. Never execute a template path, PHP snippet, or script supplied by a customer.
6. Drafts must remain private. Public requests must resolve the correct published website and must never expose another user's data.
7. Keep dynamic PHP rendering. Do not generate static HTML copies as a shortcut.
8. Do not change the sidebar or unrelated dashboard surfaces as part of this final portfolio pass.
9. Do not mark a phase complete from screenshots, code inspection, or syntax lint alone when its acceptance tests require a running application.
10. Any remaining blocker must be recorded plainly. A checklist is not a substitute for the work it describes.

---

## Phase 1 - Architecture and integration audit

**Status: PARTIAL**

### Implemented / found

- [x] Portfolio editor, advanced editor, public renderer, preview, and publishing controllers exist in InstaWeb.
- [x] Portfolio template keys are controlled in server-side code.
- [x] Portfolio data uses InstaWeb website records and owner-scoped model/controller paths.
- [x] Added `scripts/check-portfolio-integration.php`, a static guard that compares the controller registry, public renderer allowlist, React template variants, editor selector, hidden-item filtering, public route, and root access rules.
- [x] Removed root-level `idea.php` and `ideas2.php` from InstaWeb; they are no longer standalone dashboard entry points in the working branch.
- [x] The separate `Custom-portfolios` repository remains unchanged and is reference-only rather than a second application to deploy.
- [x] Portfolio guide and integration documentation exist under `docs/`.
- [x] The separate reference repository is not a target for edits.

### Required before sign-off

- [ ] Trace one complete request from website creation through editor, preview, publish, public slug resolution, unpublish, and republish in the running app.
- [ ] Confirm the production/local web-server route really executes the intended PHP renderer for public portfolios. Do not infer this from PHP files being present.
- [ ] Confirm migrations run on the supported MySQL/MariaDB versions and are safe to re-run according to project conventions.
- [ ] Confirm template keys in the editor, controller allowlist, database values, renderer, CSS, and JavaScript are identical.
- [ ] Confirm public route behavior for unknown slug, unpublished site, deleted site, and missing portfolio data.
- [ ] Confirm no fixed username, hardcoded personal data, standalone authentication, or separate per-customer database is used in the integrated flow.

**Acceptance gate:** architecture and hosting route are demonstrated in a local/staging environment, not merely described in documentation.

## Phase 2 - Advanced Editor dashboard

**Status: IMPLEMENTED; ACCESSIBILITY AND BROWSER QA PENDING**

### Implemented / found

- [x] Tabbed dashboard includes Overview, Profile, Design, structured sections, and Projects.
- [x] URL query parameter selects/retains the active tab after navigation.
- [x] Overview shows counts and quick links.
- [x] Profile fields include name, headline, biography, email, location, social links, and avatar upload.
- [x] Design controls include accent/background/text colours, typography, hero style, and Projects/Contact visibility.
- [x] Structured content forms support adding, editing, sorting, hiding/showing, and deleting entries.
- [x] Project editing and ordering controls are present.
- [x] UI styling has been simplified from the previous glossy treatment.

### Required before sign-off

- [ ] Test every tab at desktop, tablet, and mobile widths; no clipped controls, horizontal overflow, or inaccessible actions.
- [ ] Verify keyboard-only navigation, visible focus, logical tab order, form labels, and screen-reader names for icon buttons.
- [ ] Verify the selected tab remains selected after successful save, validation failure, expired CSRF token, upload error, and delete/toggle action.
- [ ] Add/verify clear success and error feedback; do not silently discard invalid input.
- [ ] Verify unsaved user edits are not unexpectedly lost on navigation or tab changes. If forms submit independently, document that behavior clearly.
- [ ] Verify forms use the correct website ID and active tab without trusting client-provided ownership.
- [ ] Ensure the design selector lists all supported templates without relying on fragile client-side option injection as the only source of truth.
- [ ] Check the dashboard in a browser with JavaScript disabled or failing where practical; essential saved content controls should fail gracefully.

**Acceptance gate:** a user can navigate, edit, save, receive feedback, and return to the same section reliably on supported screen sizes.

## Phase 3 - Standard PHP/CSS templates

**Status: PARTIAL; DISTINCTNESS AND RENDERING QA PENDING**

Templates in the current allowlist:

- [ ] **Classic** - complete baseline layout, profile, projects, sections, and contact.
- [ ] **Editorial** - typography-led layout with a distinct hierarchy and spacing.
- [ ] **3D Motion** - interactive presentation, loaded only for this template.
- [ ] **Minimal** - reduced decoration and content-first layout.
- [ ] **Noir** - intentional dark visual system with readable contrast.

### Existing evidence

- [x] The renderer recognizes these template keys and has conditional CSS/script loading for Editorial, 3D Motion, Minimal, and Noir.
- [x] The Advanced Editor template selector now renders all ten options from `PortfolioController::TEMPLATES`, avoiding a hard-coded three-template selector.
- [x] A shared database-backed content model is used rather than requiring a separate portfolio copy per template.
- [x] The renderer validates unknown template keys and falls back to Classic.
- [x] Public project URLs are revalidated at render time and only valid HTTP/HTTPS URLs are emitted as links, including for legacy or directly modified database records.
- [x] The 3D script is conditionally loaded for the 3D template rather than every portfolio.

### Required before sign-off

- [ ] Open every standard template in a browser with a complete profile, no avatar, no projects, many projects, all content sections, and long text.
- [ ] Confirm each has a visibly intentional layout rather than merely a different name or colour.
- [ ] Confirm design settings are applied consistently and do not make text unreadable.
- [ ] Test navigation anchors, mobile menu, links, image sizing, empty states, and footer.
- [ ] Check console/network errors and confirm the 3D script is absent from non-3D templates.
- [ ] Test reduced-motion preference and keyboard interaction for motion controls.

**Acceptance gate:** five distinct, usable, responsive templates render the same saved content correctly.

## Phase 4 - React-powered templates

**Status: IMPLEMENTED FOUNDATION; RUNTIME AND QUALITY QA PENDING**

Template identifiers:

- [ ] **Aurora Motion** (`aurora`)
- [ ] **Studio Canvas** (`studio`)
- [ ] **Orbit 3D** (`orbit`)
- [ ] **Prism** (`prism`)
- [ ] **Archive** (`archive`)

### Existing evidence

- [x] The public renderer passes portfolio data to a React mount point for the five React keys.
- [x] `public/assets/portfolio-template/react-portfolio.js` contains a shared React renderer and Framer Motion effects.
- [x] `react-portfolio.css` contains template-specific visual variants.
- [x] GitHub Actions now checks the React portfolio module's JavaScript syntax with Node.js; the workflow passed on the implementation branch.
- [x] Implemented interaction foundations include scroll progress, entrance animation, hover tilt cards, orbit decoration, and reduced-motion handling.
- [x] React templates now disable nonessential hero, section, and pointer-tilt motion when the operating system requests reduced motion.
- [x] A standard PHP-rendered version remains in the page as a fallback if React does not initialize.

### Required before sign-off

- [ ] Verify all five keys mount correctly in a real browser and each variant has a distinct visual identity.
- [ ] Confirm external dependency/CDN failures leave readable content and do not hide both renderers.
- [ ] Check React mount timing and ensure the fallback is hidden only after successful initialization. A JavaScript error must not leave a blank portfolio.
- [ ] Check console errors, duplicate IDs, navigation anchors, responsive overflow, and data escaping.
- [ ] Verify reduced-motion behavior for all motion effects in a browser with the OS preference both enabled and disabled.
- [ ] Measure script loading and ensure React/Framer Motion are not loaded for standard PHP/CSS templates.
- [ ] Decide and document whether the release intentionally depends on external CDNs; test the policy under blocked network conditions.
- [ ] Ensure the React templates render all content and visibility settings consistently with the standard renderer.

**Acceptance gate:** all five React variants work with and without external animation dependencies, degrade gracefully, and respect accessibility preferences.

## Phase 5 - Template selection, preview, and publishing

**Status: PARTIAL; FULL LIFECYCLE NOT VERIFIED**

### Implemented / found

- [x] The template allowlist contains ten keys.
- [x] The profile editor submits a template key and the controller validates it against the allowlist.
- [x] Public renderer maps unknown keys to Classic.
- [x] Preview/public rendering code exists in the InstaWeb application.
- [x] Portfolio content remains database-backed and is shared across template variants.
- [x] Hidden section entries are filtered before both the standard renderer and React template payload are built.
- [x] The React fallback is hidden only after the React app component mounts, so a failed module import or initial render does not intentionally hide the PHP fallback.

### Required before sign-off

- [ ] Select each of the ten templates, save, reload, and confirm the selection remains selected.
- [ ] Preview each selected template and verify the preview uses the exact same renderer/template as the public published version.
- [ ] Verify a private draft URL cannot be viewed by a logged-out visitor or another account.
- [ ] Publish a portfolio and verify its public URL renders only the correct published content.
- [ ] Edit after publishing and confirm the product's intended draft/published behavior is respected.
- [ ] Unpublish and confirm public access is blocked while the owner's saved data remains intact.
- [ ] Republish and confirm the current intended version is public.
- [ ] Test invalid/missing slug, unknown template key, deleted website, and missing portfolio record.
- [ ] Confirm switching templates does not delete projects, section entries, social links, avatar, or design settings.
- [ ] Verify all ten keys map consistently across controller, database, preview, public renderer, styles, and scripts.

**Acceptance gate:** create/edit/save/preview/publish/unpublish/republish works without data loss or draft leakage.

## Phase 6 - Content, media, and visibility

**Status: PARTIAL**

### Implemented / found

- [x] Profile fields and social links.
- [x] Avatar upload accepts JPEG, PNG, and WebP with size/dimension checks, randomized filenames, MIME/image validation, and website-specific storage.
- [x] Project add/edit/delete, project URL validation, and display ordering.
- [x] Structured sections: Goals, Interests, Challenges, Experience, Education, Skills, News and Updates, Partnerships, and CV/Resume.
- [x] Section item payloads support title, organisation/category, period, details, optional link, sort order, and enabled/hidden state.
- [x] Project and Contact visibility settings exist.
- [x] Upload directory contains a rule intended to block execution of script extensions.

### Not yet evidenced / not in current parity scope

- [ ] Favicon upload and correct public favicon rendering.
- [ ] Section background image/media upload.
- [ ] Icon selection.
- [ ] Project image/gallery, tags/technologies, featured state, and richer project metadata.
- [ ] Direct CV/resume file upload with explicit public/private handling.
- [ ] Popups, bookings/requests/comments, and visitor analytics. These are not release blockers for the core portfolio unless explicitly added to scope, but must not be represented as already integrated.
- [ ] Independent visibility/order controls for all sections if required by the final UX; current evidence shows item visibility and Projects/Contact controls, not a complete section-layout manager.
- [ ] Replace/delete avatar behavior and cleanup of orphaned uploaded files.
- [ ] Upload storage quotas, safe image serving headers, and backup/retention policy.

### Required before sign-off

- [ ] Verify upload MIME, extension, byte size, dimensions, random path, database save, failure cleanup, and cross-website access.
- [ ] Test invalid, oversized, malformed, and non-image files.
- [x] Advanced section-item links now require a valid HTTP or HTTPS URL at controller validation; profile social links and project URLs also enforce HTTP/HTTPS. Output escaping and runtime payload tests still require verification.
- [ ] Confirm hidden items never appear in public HTML, navigation, structured data, or React payloads.
- [ ] Confirm missing content produces deliberate empty states instead of broken layout.
- [ ] Test long names, Unicode, emoji, long URLs, empty fields, and multi-line content.
- [ ] Confirm media paths cannot traverse directories or execute uploaded code.

**Acceptance gate:** content and media save to the correct portfolio, display as expected, respect visibility, and resist malformed uploads and cross-user access.

## Phase 7 - Security, performance, and accessibility

**Status: PARTIAL; SECURITY SIGN-OFF PENDING**

### Existing controls found

- [x] CSRF validation on reviewed portfolio mutation routes.
- [x] Owner-scoped website lookup through the authenticated user.
- [x] Owner-scoped portfolio/avatar/project operations in reviewed controller/model paths.
- [x] Server-side template allowlist and unknown-template fallback.
- [x] Output escaping in reviewed PHP templates.
- [x] HTTP/HTTPS checks for social/project links and structured section-item links in reviewed controller paths.
- [x] Avatar MIME/image validation, size/dimension limits, randomized filenames, and upload-directory script blocking.
- [x] Root `.htaccess` now blocks direct execution of root-level PHP reference files other than the front controller, plus repository metadata, internal docs, and archive/database artifacts.
- [x] React CSS includes a reduced-motion media query and JavaScript checks the reduced-motion preference for orbit decoration.

### Required before sign-off

- [ ] Attempt to access another user's website, portfolio, section item, project, and uploaded avatar by changing IDs. All must be denied without leaking data.
- [ ] Verify CSRF failures do not mutate data and return a clear message.
- [ ] Check stored/reflected XSS payloads in every text, title, URL, section, project, and social field.
- [ ] Check URL schemes, malformed URLs, external link targets, and safe `rel` attributes.
- [ ] Check upload path traversal, double extensions, spoofed MIME, oversized files, and executable content.
- [ ] Verify the deployed Apache configuration blocks direct access to `/idea.php`, `/ideas2.php`, `/api.zip`, repository metadata, and internal documentation paths (deleted PHP files may return 404; blocked paths may return 403) while `/index.php`, `/p/{slug}`, and public assets still work.
- [ ] Confirm public routes never reveal drafts or private account metadata.
- [ ] Check SQL operations use prepared statements and mutation queries are constrained by website and owner.
- [ ] Test contrast, keyboard focus, semantic headings, landmarks, labels, alt text, touch targets, and mobile layout.
- [ ] Confirm reduced-motion preference disables or substantially reduces all nonessential animation.
- [ ] Avoid loading Bootstrap, React, Framer Motion, or Three.js when the selected template does not need them where practical.
- [ ] Check caching and response headers for public and private preview routes.
- [ ] Review logs/errors so they do not reveal database details, file paths, or private content.

**Acceptance gate:** security checks and accessibility review are recorded with outcomes; no unresolved high-severity issue remains.

## Phase 8 - End-to-end verification and release

**Status: NOT SIGNED OFF**

This is the final release gate. Syntax checks alone are not sufficient.

### Required test matrix

| Test | Expected result | Status |
|---|---|---|
| Create a portfolio website as User A | One website and initialized portfolio data belong to User A | **Not verified** |
| Open editor for User A's website | Correct website data loads | **Not verified** |
| User B requests User A's editor URL | Access denied; no data disclosed | **Not verified** |
| Save profile/social links | Data persists after reload | **Not verified** |
| Upload valid avatar | Image saves and renders for the correct website | **Not verified** |
| Upload invalid/oversized avatar | Rejected safely; no executable file saved | **Not verified** |
| Add/edit/hide/show/delete an item in every section | Correct mutation persists; hidden item is absent publicly | **Not verified** |
| Add/edit/reorder/delete projects | Correct project data and order persist | **Not verified** |
| Save each design setting | Setting persists and affects intended rendering | **Not verified** |
| Select all ten templates | Selection persists; correct renderer is used | **Not verified** |
| Preview while logged out / as another user | Draft remains private | **Not verified** |
| Publish and open public slug | Correct published website renders dynamically | **Not verified** |
| Unpublish / republish | Public availability changes correctly without data loss | **Not verified** |
| Unknown slug/template/missing record | Safe not-found/fallback behavior | **Not verified** |
| Run on mobile and desktop | No layout breakage or inaccessible controls | **Not verified** |
| Block external CDNs | Useful fallback remains visible; no blank page | **Not verified** |
| PHP/JavaScript syntax and static integration guard | PHP files lint; React module parses; template/route/security wiring is consistent | **Passed in GitHub Actions; browser/database/security runtime tests remain unverified** |
| Existing static and WordPress websites | No regression to unrelated website types | **Not verified** |
| Production hosting/PHP route | Public portfolio route executes safely in the actual hosting configuration | **Blocked until environment verified** |

### Release procedure

- [ ] Run all required database migrations against a clean test database and an existing database containing relevant data.
- [x] Run PHP syntax checks over application PHP files and the front controller in GitHub Actions.
- [x] Run JavaScript syntax check for the React portfolio module in GitHub Actions.
- [x] Run the new static portfolio integration guard in CI; it verifies template-registry parity, React CSS variants, dynamic route presence, disabled-item filtering, editor selection wiring, and root access rules.
- [ ] Add runtime tests for ownership, template allowlisting, publication visibility, and upload validation; no PHP unit-test suite is currently configured in this repository.
- [ ] Execute every row in the test matrix and record the environment, date, result, and evidence.
- [ ] Inspect browser console and network logs for every template.
- [ ] Test generic static-site and WordPress flows for regressions.
- [ ] Review the final diff for accidental unrelated changes, debug code, secrets, credentials, and generated artifacts.
- [ ] Confirm migrations and deployment instructions are documented.
- [ ] Merge only after all release-blocking tests pass and the production PHP route is confirmed.
- [ ] After merge, run a smoke test on the deployed app and verify create, edit, preview, publish, and unpublish.
- [ ] Keep `Custom-portfolios` unchanged.

**Release decision:** **NOT APPROVED YET** based on the evidence available in this repository review. This is a test-status statement, not a claim that the implementation is unusable. The remaining step is to prove the complete user journey and security boundaries in a running environment.

---

## Final scope boundary: what “done” means

The core release is done only when a customer can:

1. Create a portfolio in InstaWeb without a second account or dashboard.
2. Open the editor for their own website and never another user's content.
3. Edit profile, social links, avatar, projects, design settings, and supported structured sections.
4. Select any of the ten named templates without losing content.
5. Preview the correct draft privately.
6. Publish a dynamic portfolio at the intended public URL.
7. Unpublish and republish without data loss.
8. Use the site on mobile and desktop, with accessible controls and safe failure behavior.

Features not listed as implemented above must not be advertised as complete. In particular, favicon upload, section backgrounds, icon selection, project imagery/metadata, CV file upload, popups, booking/request/comment flows, and visitor analytics remain outside verified core parity.

## Change log

- **2026-10-25:** Audited the current `feature/portfolio-editor-full-dashboard` branch files and recorded implementation evidence versus unverified runtime behavior. The document intentionally separates code found from features that still need local database, browser, security, and hosting verification.
- **2026-10-25:** Fixed verified integration gaps: the Advanced Editor now exposes all ten allowlisted template choices; disabled section entries are excluded from public rendering and the React payload; the PHP fallback stays visible until React mounts; reduced-motion preferences disable nonessential React motion; and saved colour/font/hero-layout settings are passed to React templates. Added a static integration guard and extended GitHub Actions to check PHP, JavaScript, and template wiring. The PHP syntax checks, React module syntax check, and static integration guard passed in GitHub Actions on the implementation commit. Root `.htaccess` blocks standalone root-level PHP entry points and sensitive project artifacts. Verify access rules in the actual Apache hosting environment. Full browser, database, and security acceptance remains outstanding.
- **2026-10-25:** Deleted `idea.php` and `ideas2.php` from the InstaWeb working branch in separate commits. Verified both file paths now return 404 on `feature/portfolio-editor-full-dashboard`. The separate `lawreay/Custom-portfolios` repository was not changed. The deletions are in PR #26 and are not present on `main` until that PR is merged.
- **2026-10-25:** Tightened `AdvancedPortfolioController::saveItem()` so structured section links require a valid HTTP or HTTPS scheme, matching the existing social-link and project-link policy. CI must confirm syntax after this change; browser/security tests for malformed and unsafe URLs remain outstanding.
