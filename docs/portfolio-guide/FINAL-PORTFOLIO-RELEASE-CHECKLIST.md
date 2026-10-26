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
| Nine structured content sections | **Section-specific editor in progress; runtime QA pending** | The Advanced Editor now defines distinct fields for Goals, Interests, Challenges, Experience, Education, Skills, News, Partnerships, and CV/Resume. The controller validates section-specific payload fields. CV file upload/replace/download is still outstanding; the current CV form stores document metadata, not a file. Browser, persistence, and public-rendering checks remain pending. |
| Ten template identifiers | **Wired in code; visual parity partial** | `PortfolioController::TEMPLATES` and `portfolio/public.php` list Classic, Editorial, 3D Motion, Minimal, Noir, Aurora Motion, Studio Canvas, Orbit 3D, Prism, and Archive. Selection and renderer paths exist, but browser proof of all ten is outstanding. |
| React-powered template family | **Implemented; production verification pending** | `react-portfolio.js` uses React and Framer Motion and has distinct CSS variants. The PHP fallback now remains visible until the React component mounts. It is one shared React renderer with template-specific styling, not ten independent React applications. External CDN loading and each template's visual identity need browser testing. |
| Draft preview and public publishing lifecycle | **Partial** | Preview/public controller and renderer code exist. Confirm private draft access, published-only public access, unpublish behavior, slug resolution, and the real hosting route in a running environment. |
| Security controls | **Partially implemented; security sign-off pending** | CSRF, owner-scoped lookups, HTTP/HTTPS URL validation, output escaping, image checks, randomized filenames, and upload script blocking are present in reviewed paths. Public rendering revalidates stored project and structured-section URLs. Website deletion now explicitly removes advanced portfolio items, settings, and upload records because the legacy MyISAM website table cannot cascade to those InnoDB tables. Adversarial and cross-user tests remain pending. |
| Reference-file cleanup | **Complete on working branch** | Removed root-level `idea.php` and `ideas2.php` from `feature/portfolio-editor-full-dashboard`; verified both paths return GitHub 404 on that branch. They are not yet removed from `main` until the PR is merged. |
| Syntax / automated checks | **Passed on latest verified code commit** | GitHub Actions runs [#550](https://github.com/lawreay/instaweb/actions/runs/40226157766) and [#551](https://github.com/lawreay/instaweb/actions/runs/40226161039) passed for commit `a767bcf25151588973819f4a78365cfe32c5cc55`, including PHP lint, React module syntax, and the expanded static integration guard. This is not browser/database runtime verification. |
| Production readiness | **Not signed off** | Runtime database, browser, hosting, accessibility, performance, and cross-user checks remain release gates. |

## Portfolio editor field-contract correction (2026-10-26)

- [x] Replace the universal title/organisation/period/link/details form with section-specific field definitions in `app/Views/portfolio/advanced.php`.
- [x] Update `AdvancedPortfolioController::saveItem()` to validate the field allowlist and required fields by section.
- [x] Preserve compatibility with the current JSON payload/public renderer for the existing generic fields; education/experience date ranges are also mapped to the legacy `period` value.
- [x] Add integration-guard assertions for the Interests, Skills, CV metadata forms, and section-specific controller field rules.
- [ ] Run PHP lint, the integration guard, and React syntax checks against this commit in CI.
- [ ] Test add/edit/hide/delete for each section in WAMP and verify values render publicly.
- [ ] Implement actual CV file upload, validation, replacement/removal, and download visibility as a separate capability. Do not describe the current CV metadata form as file upload.
- [ ] Review semantic rendering of skill proficiency/category, news publication status, and current-role/current-study dates in all ten templates.

**Current limitation:** These edits have been committed to the feature branch, but the latest CI result and live WAMP behavior have not yet been verified. The editor field contracts are a first implementation pass, not release approval.
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
- [x] Public project and structured-section URLs are revalidated at render time; only valid HTTP/HTTPS URLs are emitted as links, including for legacy or directly modified database records.
- [x] Website deletion explicitly removes advanced portfolio item, settings, and upload metadata rows because those tables cannot rely on foreign-key cascades from the legacy MyISAM `websites` table.
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
- [x] Authenticated preview HTML now emits `noindex,nofollow`, while published portfolio pages retain `index,follow`; the integration guard checks this distinction.
- [x] React Contact navigation now appears only when the Contact section can render (email or at least one social link), preventing a dead `#contact` anchor.

### Required before sign-off

- [x] Select each of the ten templates, save, reload, and confirm the selection remains selected (local WAMP HTTP test, 2026-10-26).
- [x] Preview each selected template and verify the expected renderer asset is selected; publish verification used the same Aurora renderer (local WAMP HTTP test, 2026-10-26).
- [x] Verify a private draft URL cannot be viewed by a logged-out visitor or another account (local WAMP HTTP test, 2026-10-26).
- [x] Publish a portfolio and verify its public URL renders only the correct published content (local WAMP HTTP test, 2026-10-26).
- [x] Unpublish and confirm public access is blocked while the owner's saved data remains intact (local WAMP HTTP test, 2026-10-26).
- [x] Republish and confirm the current intended version is public (local WAMP HTTP test, 2026-10-26).
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
- [x] Safe cleanup of the previous avatar after a successful replacement, restricted to the current website's dedicated upload directory.
- [ ] Explicit avatar removal and broader orphaned-upload cleanup behavior; runtime filesystem tests remain pending.
- [ ] Upload storage quotas, safe image serving headers, and backup/retention policy.

### Required before sign-off

- [ ] Verify upload MIME, extension, byte size, dimensions, random path, database save, failure cleanup, and cross-website access.
- [ ] Test invalid, oversized, malformed, and non-image files.
- [x] Advanced section-item links now require a valid HTTP or HTTPS URL at controller validation; profile social links and project URLs also enforce HTTP/HTTPS. Output escaping and runtime payload tests still require verification.
- [x] Confirm a disabled Goal item is absent from the published React portfolio HTML after a real owner mutation (local WAMP HTTP test, 2026-10-26). Broader section-by-section coverage remains pending.
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

- [x] A second authenticated account was redirected away from User A's basic editor, Advanced Editor, and preview URLs by changing the website ID (local WAMP HTTP test, 2026-10-26). Direct project/item/upload endpoint coverage remains pending.
- [x] Verify a missing-CSRF profile save does not mutate data (local WAMP HTTP test, 2026-10-26). Error-message presentation still needs browser coverage.
- [ ] Check stored/reflected XSS payloads in every text, title, URL, section, project, and social field.
- [ ] Check URL schemes, malformed URLs, external link targets, and safe `rel` attributes.
- [ ] Check upload path traversal, double extensions, spoofed MIME, oversized files, and executable content.
- [x] Verify the local WAMP Apache configuration blocks direct access to `/idea.php`, `/ideas2.php`, `/api.zip`, and internal checklist paths while public portfolio routes remain available (local HTTP test, 2026-10-26). Production hosting still needs separate verification.
- [ ] Confirm public routes never reveal drafts or private account metadata.
- [ ] Check SQL operations use prepared statements and mutation queries are constrained by website and owner.
- [ ] Test contrast, keyboard focus, semantic headings, landmarks, labels, alt text, touch targets, and mobile layout.
- [ ] Confirm reduced-motion preference disables or substantially reduces all nonessential animation.
- [ ] Avoid loading Bootstrap, React, Framer Motion, or Three.js when the selected template does not need them where practical.
- [x] Private draft preview controller sends `private, no-store, no-cache, must-revalidate`, `Pragma: no-cache`, and `Expires: 0`; the shared portfolio HTML emits `noindex,nofollow` for previews. Confirmed over local WAMP HTTP on 2026-10-26 after preventing Apache/PHP session defaults from replacing the route-specific cache policy.
- [x] React Contact navigation is omitted when the Contact section cannot render because both email and social links are absent. Static regression guard passes; browser interaction remains to be tested.
- [ ] Review logs/errors so they do not reveal database details, file paths, or private content.

**Acceptance gate:** security checks and accessibility review are recorded with outcomes; no unresolved high-severity issue remains.

## Phase 8 - End-to-end verification and release

**Status: NOT SIGNED OFF**

This is the final release gate. Syntax checks alone are not sufficient.

### Local WAMP runtime evidence (2026-10-26)

The following was exercised against `http://localhost/instaweb1` with MariaDB 8.4.7 and two disposable accounts. The test site and both accounts were deleted through the application/database cleanup immediately after verification; no QA portfolio data remains.

- Created a portfolio as User A, saved profile and design data, added a Goal item and a project, and confirmed database persistence.
- Confirmed User B was redirected away from User A's basic editor, Advanced Editor, and preview URLs.
- Confirmed missing-CSRF and `javascript:` URL submissions did not mutate data; the valid HTTP/HTTPS path did.
- Selected, saved, and previewed all ten template keys. The selected renderer asset matched each template; Aurora was also verified on a published public URL.
- Confirmed an owner preview was private (`noindex,nofollow` and private no-store/no-cache headers), a draft public slug returned 404, and the published slug was indexable.
- Confirmed publish, disabled-item filtering, unpublish (404), and republish (200) through the real HTTP routes.
- Confirmed local Apache blocked direct root reference files and internal documentation while public portfolio routes still served.

This materially reduces the outstanding runtime work, but it is not a production release approval: browser visual/accessibility, uploads, CDN-failure fallback, direct endpoint coverage, unrelated site types, clean-migration coverage, and production-host verification remain open.

### Required test matrix

| Test | Expected result | Status |
|---|---|---|
| Create a portfolio website as User A | One website and initialized portfolio data belong to User A | **Passed locally (2026-10-26)** |
| Open editor for User A's website | Correct website data loads | **Passed locally (2026-10-26)** |
| User B requests User A's editor URL | Access denied; no data disclosed | **Passed locally for basic editor, Advanced Editor, and preview (2026-10-26)** |
| Save profile/social links | Data persists after reload | **Passed locally for profile/design fields; social-link browser coverage remains pending (2026-10-26)** |
| Upload valid avatar | Image saves and renders for the correct website | **Not verified** |
| Upload invalid/oversized avatar | Rejected safely; no executable file saved | **Not verified** |
| Add/edit/hide/show/delete an item in every section | Correct mutation persists; hidden item is absent publicly | **Partially passed: add/hide a Goal item verified; full section coverage pending** |
| Add/edit/reorder/delete projects | Correct project data and order persist | **Partially passed: add/persist verified; edit/reorder/delete pending** |
| Save each design setting | Setting persists and affects intended rendering | **Partially passed: design save/persistence verified; visual coverage pending** |
| Select all ten templates | Selection persists; correct renderer is used | **Passed locally (2026-10-26)** |
| Preview while logged out / as another user | Draft remains private | **Passed locally for another authenticated user and public draft slug (2026-10-26)** |
| Draft preview HTML metadata and cache headers | Preview is `noindex,nofollow` and sends private no-store/no-cache headers; public pages remain indexable | **Passed locally (2026-10-26)** |
| React Contact navigation with no email/social links | No Contact nav link is rendered when the Contact section is absent | **Code/guard implemented; browser test not verified** |
| Publish and open public slug | Correct published website renders dynamically | **Passed locally (2026-10-26)** |
| Unpublish / republish | Public availability changes correctly without data loss | **Passed locally (2026-10-26)** |
| Unknown slug/template/missing record | Safe not-found/fallback behavior | **Partially passed: unknown slug returned 404; unknown-template/missing-record runtime coverage pending** |
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
- [x] Re-run CI after the private-preview robots metadata and React Contact-navigation guard changes; GitHub Actions runs #550 and #551 passed on commit `a767bcf25151588973819f4a78365cfe32c5cc55`.
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

- **2026-10-26:** Ran the first local WAMP/MariaDB runtime acceptance pass using two disposable accounts and an application-created portfolio. Ownership redirects, missing-CSRF non-mutation, unsafe URL rejection, profile/design/project/section persistence, all ten template selections, private draft access, preview robots metadata, publication lifecycle, disabled-item filtering, and local Apache path protection were exercised over real HTTP. The test portfolio was deleted through the product and both disposable accounts were removed; no QA data remains. During this pass, Apache/PHP session defaults were found to replace the preview route's private cache policy. `PreviewController`, `Session`, and `.htaccess` were updated so the live preview response now retains `Cache-Control: private, no-store, no-cache, must-revalidate`, `Pragma: no-cache`, and `Expires: 0`. Browser visual/accessibility, media uploads, CDN-failure fallback, direct endpoint coverage, unrelated site types, clean-migration coverage, and production-host verification remain release blockers.
- **2026-10-25:** Audited the current `feature/portfolio-editor-full-dashboard` branch files and recorded implementation evidence versus unverified runtime behavior. The document intentionally separates code found from features that still need local database, browser, security, and hosting verification.
- **2026-10-25:** Fixed verified integration gaps: the Advanced Editor now exposes all ten allowlisted template choices; disabled section entries are excluded from public rendering and the React payload; the PHP fallback stays visible until React mounts; reduced-motion preferences disable nonessential React motion; and saved colour/font/hero-layout settings are passed to React templates. Added a static integration guard and extended GitHub Actions to check PHP, JavaScript, and template wiring. The PHP syntax checks, React module syntax check, and static integration guard passed in GitHub Actions on the implementation commit. Root `.htaccess` blocks standalone root-level PHP entry points and sensitive project artifacts. Verify access rules in the actual Apache hosting environment. Full browser, database, and security acceptance remains outstanding.
- **2026-10-25:** Deleted `idea.php` and `ideas2.php` from the InstaWeb working branch in separate commits. Verified both file paths now return 404 on `feature/portfolio-editor-full-dashboard`. The separate `lawreay/Custom-portfolios` repository was not changed. The deletions are in PR #26 and are not present on `main` until that PR is merged.
- **2026-10-25:** Tightened `AdvancedPortfolioController::saveItem()` so structured section links require a valid HTTP or HTTPS scheme, matching the existing social-link and project-link policy. CI passed on the earlier security-fix commit; browser/security tests for malformed and unsafe URLs remain outstanding.
- **2026-10-25:** Audited the publish lifecycle. Code review confirms the dynamic public slug lookup and portfolio content lookup both require `status = 'published'` and the portfolio template; draft preview requires authentication and owner-scoped website lookup; publish/unpublish updates website status and removes stale static publication files. These are source-code findings only, not live HTTP/database test results. Reordered website deletion cleanup so advanced rows and portfolio rows are removed before the legacy MyISAM website row, after an owner check. Extended the static integration guard to check publication gating, owner-only preview, publish/unpublish wiring, and cleanup. GitHub Actions passed for code commit `4c8fe26316ad14091764ae3091dcf7cb621b924d`, including PHP syntax, the static portfolio integration guard, and React module syntax. The latest branch commit after that is a documentation-only checklist update. See [workflow run #525](https://github.com/lawreay/instaweb/actions/runs/40217827715).

- **2026-10-25:** Hardened deletion cleanup further: advanced editor rows and portfolio rows are removed before the legacy MyISAM website row, after an owner check, and the generated avatar file is removed only when its resolved path is inside that website's dedicated upload directory. Updated portfolio unpublish ordering so the dynamic public route is switched to draft before filesystem cleanup; static sites retain file-first cleanup. Added a static regression assertion for the portfolio-specific ordering. GitHub Actions passed for the latest code commit, including PHP lint, the expanded static lifecycle guard, and React module syntax. Database and HTTP runtime tests remain outstanding.

- **2026-10-25:** Found and fixed an upload lifecycle issue: replacing a profile avatar previously left the prior image file on disk. `PortfolioController::uploadAvatar()` now resolves and removes the old file only when it is inside the current website's dedicated upload directory. Extended the static integration guard for this cleanup. GitHub Actions run #532 passed PHP syntax, the portfolio integration guard, and React module syntax. Live upload/replacement and filesystem-permission tests remain pending.

- **2026-10-25:** Added private, no-store/no-cache response headers to the owner-only database-backed portfolio draft preview. Static website preview files already used these headers; the portfolio HTML preview now follows the same policy so draft content is not retained by browsers or shared caches. Extended `scripts/check-portfolio-integration.php` to guard this behavior. At the time of this change, CI was still pending; later runs #550 and #551 passed after the preview metadata and Contact-navigation guards were added. Live HTTP/cache behavior still needs runtime verification.

- **2026-10-25:** Closed two additional audit findings in the InstaWeb branch: React Contact navigation is now conditional on the same email/social-link criteria as the rendered Contact section, and authenticated preview HTML now uses `noindex,nofollow` while published pages stay `index,follow`. Extended the static integration guard for both regressions. At the time of the initial guard commit, a fresh CI run was still required. The first guard attempt exposed a string-interpolation issue in the test itself; that assertion was corrected, and the full CI workflow then passed in runs [#550](https://github.com/lawreay/instaweb/actions/runs/40226157766) and [#551](https://github.com/lawreay/instaweb/actions/runs/40226161039). Live browser/HTTP verification remains outstanding.
