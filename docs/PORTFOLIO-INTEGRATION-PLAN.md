# Portfolio Integration Plan: Custom Portfolios into InstaWeb

**Status:** Agreed architecture; implementation in progress on `feature/portfolio-editor-workflow`  
**Decision date:** 2026-10-23  
**Target repository:** `lawreay/instaweb`  
**Reference project:** `lawreay/Custom-portfolios`  
**Stack:** Existing InstaWeb PHP backend and MySQL; existing dashboard and website lifecycle  
**Scope:** Portfolio website type, shared editor, reusable templates, private preview, dynamic public rendering, and publishing lifecycle integration

## 1. Agreed decision

**Keep the existing portfolio application/design and render public portfolios dynamically with PHP and MySQL. Do not generate static HTML copies.**

Replace the existing public landing-page behavior with a database-driven renderer that resolves the requested published InstaWeb website, loads its portfolio data and selected template, and renders the response on each request. The standalone portfolio is a reference implementation, not a second application to deploy for every client.

This supersedes any earlier static-generation proposal in this plan.

Core rules:

1. All implementation changes happen in InstaWeb. Do not modify `lawreay/Custom-portfolios`.
2. Keep InstaWeb authentication, account ownership, dashboard shell, website records, Manage flow, and lifecycle.
3. Do not create a second login, dashboard, or per-client portfolio application.
4. Link each portfolio's data/settings to its InstaWeb website record and owner.
5. Replace the fixed `admin` public-owner lookup with website/slug resolution.
6. Reuse the existing PHP renderer/design, refactored to render the resolved portfolio and selected template.
7. Public rendering is dynamic. Publish/unpublish controls whether the saved portfolio is publicly available; publishing does not generate a static copy.
8. Verify the production PHP execution route before implementation. The ordinary static publisher currently excludes PHP, and WordPress's PHP-enabled workflow does not automatically prove ordinary website routes can execute PHP.
9. Never enable arbitrary PHP execution for customer-uploaded files as a shortcut.

## 2. Product experience

1. A signed-in user opens **Create Website** in InstaWeb.
2. They choose **Portfolio**.
3. InstaWeb creates a normal website record associated with the authenticated owner and initializes portfolio settings/content.
4. The portfolio appears in the existing website list.
5. **Manage** opens the editor for that specific website without a second login or dashboard.
6. The client edits profile, biography, skills, projects, experience, education, contact information, CV, images, and supported appearance settings.
7. The client chooses a template, saves a draft, and previews it privately.
8. Publish makes the saved version available through the portfolio's public URL.
9. A visitor opens that URL; PHP resolves the website record, loads its published content and selected template, and renders the portfolio dynamically.
10. Share, unpublish, and delete use InstaWeb's existing website lifecycle and affect only the selected website.

A template change must not require the client to re-enter content. Template and appearance settings remain separate from portfolio content.

## 3. Target request architecture

```text
Public portfolio URL
        |
        v
InstaWeb public route / PHP execution path
        |
        v
Resolve published website by validated slug/domain
        |
        v
Load website-linked portfolio and published data
        |
        +------ Selected template
        |
        v
Render HTML response dynamically with PHP
        |
        v
Visitor sees the correct portfolio
```

Management uses the same website record but a different authorization path: authenticated InstaWeb user → owner-scoped website lookup → editor → save/preview/publish. Public routes must never expose drafts or use the logged-in session as the identity of a public portfolio.

## 4. Template and content model

The existing Custom-portfolios design is the proposed first template, subject to inspection and refactoring. Preserve its visual identity where practical and remove hardcoded personal content from markup.

Initial candidates after the first end-to-end flow is reliable:

- **Classic:** Existing portfolio design adapted into a reusable PHP template.
- **Modern Developer:** Developer-focused layout.
- **Minimal:** Content-first layout.
- **Creative:** Visual layout for designers and creative professionals.

Do not build all templates before one shared template/data contract works. Templates contain presentation only; they must not own independent content copies, authentication, persistence, or publish logic.

Candidate structured content:

- Profile, biography, contact details and social links.
- Projects and technology tags.
- Work experience, education, and skills.
- CV/resume and approved public images.
- Validated theme colors, typography, section visibility/order, and supported backgrounds.

Assess news, popups, bookings, requests, comments, and visitor analytics separately. Defer them from the first release until the core portfolio lifecycle is safe and reliable.

## 5. What to reuse and what to replace

### Reuse

- Existing portfolio design, responsive layout, useful content fields, and suitable PHP rendering logic.
- Existing InstaWeb user/session and authorization system.
- Existing website record, owner association, slug, dashboard list, Manage entry point, and website lifecycle.
- InstaWeb database configuration, models/services/controllers conventions, storage controls, CSRF protection, and validation patterns.

### Replace or exclude from the integrated flow

- Standalone portfolio login and recovery.
- Separate portfolio session and database bootstrap.
- Fixed `admin` username as the public portfolio identity.
- Hardcoded personal content in the template.
- Runtime schema changes from ordinary page requests.
- Any assumption that each client needs a separate copied application.
- Static HTML generation for portfolio publishing.
- Unsafe debug pages, destructive production seed/reset scripts, and committed credentials.

Do not delete or modify the reference repository. Port or refactor only reviewed components inside InstaWeb.

## 6. Data ownership, public resolution, and security

- Associate each portfolio configuration/content set with an InstaWeb website record; the website record identifies its owner.
- For editor actions, verify the authenticated user owns the requested website on every read/write/preview/publish/unpublish/delete request.
- For public requests, resolve the published website by its validated slug/domain/route and load only its published portfolio content. Never trust a submitted owner ID or use a fixed username.
- Use prepared statements, CSRF protection for state-changing editor actions, context-aware output escaping, and server-side validation.
- Allowlist template identifiers and appearance options; validate URLs and upload MIME type, extension, size, filename, and storage path.
- Do not permit uploaded PHP or arbitrary scripts to execute.
- Keep drafts private and define whether each CV/image is public.
- Use explicit, versioned migrations; do not mutate the schema on normal requests.
- If caching is used, scope cache keys and invalidation by website and publication version to prevent cross-portfolio leakage.
- Return suitable not-found/unpublished responses without exposing private content.
- Rotate the reference repository's exposed database credential; never copy or repeat it.

## 7. Hosting and routing requirement

InstaWeb's current ordinary-site publishing path filters PHP files, while WordPress uses a separate PHP-enabled workflow. Dynamic portfolio rendering therefore needs a deliberate public PHP execution path.

During Phase 0, inspect the actual production web-server configuration, routes, WordPress provisioning/runtime, public URL scheme, and security boundaries. Decide the smallest safe way to route a portfolio URL to the PHP renderer. Do not assume WordPress's installation path is reusable, and do not enable PHP execution across arbitrary uploaded website directories. If a scoped PHP route/configuration is required, implement and test it before claiming dynamic public rendering works.

## 8. Implementation phases

### Phase 0 — Audit and mapping

- Locate the exact reference SQL schema and content handlers.
- Map the MVP content fields to the InstaWeb website record and proposed tables.
- Trace route dispatch, website ownership, preview, publish/unpublish, WordPress provisioning, public URL handling, and server PHP execution.
- Record draft versus published visibility rules and the hosting approach.
- Establish the existing test baseline.

**Exit condition:** reviewed data mapping, route map, and confirmed PHP-enabled public route design.

### Phase 1 — Portfolio creation

- Add/confirm Portfolio as a first-class website type.
- Initialize portfolio configuration/content linked to the newly created website record.
- Preserve current owner checks, slug uniqueness, plan limits, and failure rollback behavior.

**Exit condition:** selecting Portfolio creates one correctly owned website and portfolio configuration.

### Phase 2 — Shared editor integration

- Connect Manage to the selected portfolio in InstaWeb's existing dashboard.
- Implement MVP structured fields and basic appearance controls.
- Use InstaWeb authentication, CSRF, services, models, and views/components.
- Remove dependency on standalone authentication and the hardcoded `admin` lookup.

**Exit condition:** owner can save and reload their portfolio without a second login or dashboard.

### Phase 3 — Dynamic renderer and preview

- Refactor the existing design into a reusable template driven by saved data.
- Load the selected template and content for the resolved website record.
- Implement owner-authorized private preview and public rendering of published content.
- Verify two users can use the same template while seeing only their own content.

**Exit condition:** same renderer produces distinct, correctly isolated portfolios for two test users.

### Phase 4 — Public routing and lifecycle

- Implement/verify the PHP-enabled public route.
- Connect publish, Share, unpublish, and delete to InstaWeb's existing website lifecycle.
- Do not generate static portfolio copies.
- Verify unpublishing blocks public access but preserves the draft; republishing exposes the current saved/published version according to the agreed policy.

**Exit condition:** published portfolio URL dynamically renders the correct website data and template.

### Phase 5 — Security, regression, and release

- Test cross-user access, CSRF, invalid IDs/slugs, uploads, template switching, missing data, render failures, publish/unpublish/delete, and responsive layout.
- Verify CSS/JS/images/CV links and correct public URL behavior.
- Run existing tests and confirm generic static sites and WordPress still work.
- Release only after review and passing checks.

**Exit condition:** create → manage → edit → preview → publish → share passes in a test environment without regressions.

## 9. Acceptance checklist

- [ ] Portfolio is created through InstaWeb and linked to the correct owner.
- [ ] No second login or dashboard is introduced.
- [ ] Manage opens the editor for the selected website and checks ownership server-side.
- [ ] Existing design is preserved as a reusable template.
- [ ] Content is stored once and rendered from database records.
- [ ] Public renderer resolves a website by slug/domain and renders its published data dynamically with PHP.
- [ ] Portfolio publishing does not generate static HTML copies.
- [ ] Preview is private; draft data is not publicly exposed.
- [ ] Template switching preserves content.
- [ ] Share, unpublish, and delete affect only the selected website.
- [ ] PHP hosting/routing is confirmed and safely scoped.
- [ ] Cross-client isolation and cache behavior are tested.
- [ ] WordPress and generic static website workflows remain functional.
- [ ] All changes are inside InstaWeb; the reference repository is untouched.
- [ ] No credentials, customer data, or unsafe debug utilities are introduced.

## 10. Working rules

- Keep this feature on a dedicated branch, separate from unrelated dashboard work.
- Make small, reviewable commits.
- Read `AGENTS.md` and current project/phase documentation before changing code.
- Inspect existing conventions before adding routes, tables, folders, or dependencies.
- Never claim tests, migrations, hosting changes, or deployments succeeded until verified.
- Record changes to the schema, API contract, or public URL behavior in the docs.
- Keep all implementation changes in `lawreay/instaweb`; do not modify `lawreay/Custom-portfolios`.

## Final recommendation

Implement the agreed dynamic approach inside InstaWeb: preserve the existing PHP portfolio design/renderer, replace the hardcoded public landing behavior with website-record-driven rendering, reuse InstaWeb authentication and management, and load each client's selected template and data dynamically. Confirm the production PHP route first, then build one secure end-to-end portfolio flow before expanding the template gallery.


## 11. Implementation status (2026-10-23)

The first implementation slice is on the draft InstaWeb PR [#24](https://github.com/lawreay/instaweb/pull/24): owner-scoped profile/project editing, template selection, database-backed public rendering at `/p/{slug}`, private portfolio preview, dynamic publishing without static HTML copies, and the initial design CSS/3D assets. GitHub PHP syntax checks pass for the current code branch.

Still pending: local database migrations and two-account ownership tests, browser verification of all templates and lifecycle actions, static-site/WordPress regression checks, and review of reference-portfolio sections not yet represented in the current profile/projects schema. Production hosting behavior has not been verified from this repository review.
