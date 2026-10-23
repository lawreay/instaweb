# Customizable Portfolio Integration Audit and Plan

**Target repository:** `lawreay/instaweb`  
**Reference repository:** `lawreay/Custom-portfolios`  
**Documentation branch:** `docs/portfolio-integration-plan`  
**Decision updated:** 2026-10-23  
**Status:** Architecture and implementation tracking; code changes are underway on InstaWeb draft PR #24.  
**Scope constraint:** All implementation changes belong in InstaWeb. Do not modify the reference repository.

## 1. Agreed architectural decision

Integrate the existing customizable portfolio into InstaWeb's current website workflow and **render public portfolios dynamically with PHP and MySQL**.

The agreed approach is to keep the existing portfolio application and design, replace its public landing-page behavior with a database-driven public renderer, and adapt ownership/authentication to InstaWeb. Do not generate static HTML copies when publishing. The public URL must execute the PHP renderer and resolve the requested portfolio to the correct website record, content, and selected template.

This decision supersedes earlier suggestions in this document or related planning documents that proposed static HTML generation for portfolios.

- Keep InstaWeb's existing client dashboard, website list, Manage workflow, authentication, website records, and publishing/site lifecycle where compatible.
- Keep the existing portfolio's design and useful PHP rendering/content logic; refactor rather than rebuild wholesale.
- Use InstaWeb's authenticated identity for management actions. Do not introduce a second portfolio login or dashboard.
- Store portfolio content/settings against the corresponding InstaWeb website record, which is already associated with an owner.
- Resolve public requests by the published website's slug/route and its portfolio record, not by a fixed `admin` username.
- Reuse templates across clients. Each request loads the requested portfolio's data and chosen template, then renders the response dynamically.
- Do not create a separate application directory or separate PHP installation for every portfolio.
- Do not modify `lawreay/Custom-portfolios`; it is a reference only.

**Hosting dependency:** InstaWeb's normal publisher currently filters PHP files from ordinary static-site output, while WordPress has a separate PHP-enabled workflow. Before implementation, determine how portfolio public routes will execute PHP in the actual deployment. Do not assume WordPress provisioning automatically provides a reusable PHP runtime for ordinary sites. If the current hosting route cannot execute the renderer, design and document the smallest safe PHP-enabled route for portfolios. Do not silently fall back to static generation.

## 2. Findings verified in repository source

### InstaWeb

- The application is a PHP MVC application with MySQL.
- `app/Config/routes.php` registers the dashboard, website creation, website detail, file manager, code editor, preview, publish, and unpublish routes.
- `app/Controllers/WebsiteController.php` authenticates through InstaWeb's `AuthService`, creates a website record, creates its draft directory, and prepares starter content.
- `app/Models/Website.php` associates each website with `user_id`, has a globally unique `slug`, and provides owner-scoped lookups such as `findForUser(id, userId)`.
- `app/Views/websites/create.php` offers a `portfolio` starter choice, currently handled by the generic starter-file flow rather than the dedicated customizable portfolio application.
- `app/Views/websites/index.php` and `app/Views/websites/show.php` provide the shared website list, Manage/detail workflow, and existing dashboard shell.
- Draft files live under `storage/projects/{userId}/{slug}`; published output is placed under `public/sites/{projectId}/{slug}`.
- `app/Services/PublishingService.php` publishes a filtered set of static file extensions for ordinary sites. PHP files are excluded. WordPress has a separate explicit PHP-enabled path.
- Consequently, dynamic portfolio rendering requires a supported PHP execution route for the public portfolio URL. The current static publishing path alone does not provide it.

### Custom-portfolios

- `index.php` loads content from its own configuration/database helpers and currently resolves the public owner using a fixed username (`admin`). This is not suitable for a multi-client model as-is.
- `admin/dashboard.php` contains the existing portfolio editor and handles profile settings, projects, goals, interests, experience, skills, education, news, popups, bookings/requests/comments, visits, section backgrounds, and uploaded images.
- `admin/login.php` implements standalone login/recovery against a separate `portfolio_users` table. Do not integrate this as a second authentication system.
- `config.php` creates a separate database connection, starts a separate session, and performs schema changes at runtime. Do not copy these responsibilities into InstaWeb.
- The reference repository has a wider feature set than the initial integration requires. Assess secondary engagement features individually rather than automatically carrying them over.

## 3. Target request and data flow

### Public portfolio request

1. A visitor opens the published portfolio URL.
2. InstaWeb's supported public route resolves the requested website using a validated slug/domain/route.
3. The application finds the portfolio configuration/content associated with that website.
4. The renderer loads the selected template and that portfolio's published content/settings.
5. PHP renders the response dynamically.
6. Missing, unpublished, or invalid portfolios return an appropriate not-found/unavailable response without exposing private draft data.

### Owner management workflow

1. The client signs into InstaWeb.
2. The client selects **Create Website → Portfolio**.
3. InstaWeb creates its normal website record associated with the authenticated `user_id` and initializes the portfolio configuration/content.
4. The existing website list and dashboard remain the only management interface.
5. **Manage** opens the editor for that specific website record, with owner verification on every request.
6. The client edits structured content, chooses a template, saves a draft, and previews it privately.
7. The public renderer shows published data dynamically. Saved changes must not become public until the existing publish action or an explicitly agreed publish policy makes them public.
8. Share, unpublish, and delete operate on the selected website only.

## 4. Separation of responsibilities

- **InstaWeb authentication:** source of truth for the current management user; no parallel portfolio session.
- **Website record:** source of truth for owner, slug, status, plan limits, and public URL.
- **Portfolio content/settings:** structured records linked to the InstaWeb website record. Reuse existing tables only if their ownership and constraints fit; otherwise add versioned migrations in InstaWeb.
- **Template renderer:** receives a resolved portfolio record, content, and validated template/settings; it must not contain hardcoded client data.
- **Preview:** renders the owner's draft privately and does not publish it accidentally.
- **Publish:** changes which saved portfolio version/content is publicly served. It does not generate static HTML copies. Use InstaWeb's existing lifecycle for publish/unpublish/status where compatible.
- **Hosting/routing:** provides the PHP execution path required by the public renderer. Keep the runtime scoped and configured safely; never enable arbitrary uploaded PHP execution.

The likely MVP association is one portfolio configuration per InstaWeb website record. Avoid a separate portfolio account ID unless repository/schema inspection demonstrates a concrete need.

## 5. Reuse and migration map

| Existing portfolio capability | Integration treatment |
|---|---|
| Visual design and responsive layout | Preserve as the first reusable template; separate personal data from markup |
| PHP public renderer | Refactor to accept the resolved portfolio record and selected template dynamically |
| Profile, bio, contact and CV | MVP structured fields |
| Projects and technology tags | MVP structured records |
| Experience, education and skills | MVP structured records |
| Theme colors and typography | Reuse supported settings with validation and safe defaults |
| Section visibility/order/backgrounds | Begin with simple supported options; avoid an unrestricted page builder |
| Image and CV uploads | Use InstaWeb-managed storage with strict type, size, path, and visibility validation |
| Standalone login/password recovery | Do not migrate; use InstaWeb auth |
| Standalone DB connection/session/config | Do not migrate; use InstaWeb configuration and model/service patterns |
| News, popups, bookings, requests, comments, visitor logs | Defer until the core flow is reliable; assess privacy and abuse controls before adding |
| Fixed `admin` public owner lookup | Replace with published website/slug resolution and website-linked portfolio data |

## 6. Data and security requirements

- Every editor read/write/preview/publish operation must verify the authenticated user owns the website. Never trust a submitted `user_id` or website ID without server-side authorization.
- Public requests must resolve the portfolio from the requested published website record; never use a shared hardcoded username.
- Use prepared statements through InstaWeb's existing model/database conventions.
- Use InstaWeb CSRF protection for every state-changing editor action.
- Escape rendered output by context; validate template, URL, color, typography, and visibility settings against allowlists.
- Validate upload MIME type, extension, size, generated filenames, and storage paths. Do not allow uploaded PHP or arbitrary script execution.
- Never copy reference-repository credentials, session setup, database bootstrap, or runtime schema-alteration code.
- Use versioned, repeatable database migrations. Do not run opportunistic schema changes from a normal page request.
- Keep draft content private. Define explicitly which CVs and uploaded assets are public.
- Add tests proving Client A cannot read, edit, preview, publish, unpublish, or delete Client B's portfolio.
- Dynamic output must not allow one portfolio's data, cached response, or template settings to leak into another portfolio. If caching is introduced, cache keys and invalidation must be scoped by website and publication version.

## 7. Phased implementation plan

### Phase 0 — Confirm route, hosting, and schema

- Locate the exact reference schema and content write handlers.
- Map content fields to the MVP and proposed InstaWeb data model.
- Inspect InstaWeb route dispatch, website lookup, preview, publishing/unpublishing, file storage, WordPress provisioning, and production web-server configuration.
- Determine the smallest supported route that executes PHP for a portfolio's public URL without enabling PHP execution for arbitrary customer-uploaded files.
- Record the URL resolution strategy, draft/published visibility rules, and any hosting constraints before schema/controller changes.

**Exit criteria:** field-by-field mapping, route map, hosting execution plan, and reviewed migration plan.

### Phase 1 — Portfolio website foundation

- Add/confirm an explicit Portfolio website type without breaking static or WordPress flows.
- Add schema migrations only for genuinely missing fields/tables.
- Initialize portfolio configuration/content when the portfolio website is created.
- Preserve owner checks, slug uniqueness, plan limits, CSRF, notifications, and rollback-on-failure behavior.

**Exit criteria:** selecting Portfolio creates one owner-linked website and its portfolio configuration.

### Phase 2 — Integrate editor and ownership

- Add portfolio-specific controller/service/model/view code inside InstaWeb.
- Connect Manage from the existing website list/detail workflow to the selected website's editor.
- Implement profile, projects, skills, experience, education, contact, CV, and basic theme settings.
- Remove the standalone login dependency and hardcoded `admin` assumption.
- Keep SQL in models, business logic in services, HTTP handling in controllers, and UI in views/components consistent with InstaWeb conventions.

**Exit criteria:** data saves/reloads for the correct owner; no second login or dashboard shell is introduced.

### Phase 3 — Dynamic template rendering and preview

- Refactor the reference design into a reusable template with no hardcoded personal data.
- Implement a renderer that receives a resolved website/portfolio record and loads its selected template plus content.
- Ensure public requests show only published data and preview requests are owner-authorized and private.
- Ensure two users can share a template without sharing content.

**Exit criteria:** two test users see different correct portfolios through the same renderer, with draft privacy preserved.

### Phase 4 — Dynamic public routing and lifecycle

- Implement/verify the PHP-enabled public route and URL-to-website/portfolio resolution.
- Integrate publish, share, unpublish, and delete with the existing InstaWeb website lifecycle.
- Do not generate static HTML output for portfolio publishing.
- Verify a saved change appears publicly according to the agreed publish policy, without cross-client leakage.
- If PHP cannot execute on the current ordinary-site path, implement a specifically scoped and reviewed PHP route/hosting configuration. Do not enable arbitrary PHP execution in user-uploaded site directories.

**Exit criteria:** a published portfolio is dynamically rendered at its public URL; unpublishing removes public access while retaining the draft.

### Phase 5 — Regression, security, and deployment tests

- Run the existing test suite and add portfolio-specific unit/integration tests.
- Test two-user isolation, CSRF, invalid IDs/slugs, uploads, template switching, missing data, save/render errors, publish/unpublish/delete, and mobile layout.
- Test production URL routing, PHP runtime behavior, CSS/JS/image/CV assets, and correct error responses.
- Verify WordPress and generic static-site workflows are unchanged.
- Deploy only after reviewable changes and successful checks.

**Exit criteria:** create → manage → edit → preview → publish → share works in a test environment, and existing website types have no regressions.

## 8. Acceptance checklist

- [ ] Portfolio creation uses InstaWeb authentication and the existing website record.
- [ ] No portfolio-specific login or second dashboard is required.
- [ ] Manage opens the editor for the selected website and verifies ownership server-side.
- [ ] One shared editor/renderer serves all clients while keeping data isolated.
- [ ] Personal content is database-driven, not hardcoded in the template.
- [ ] Template switching does not delete or duplicate content.
- [ ] Draft save and preview do not accidentally publish changes.
- [ ] The public route dynamically executes the PHP renderer and resolves the correct published website.
- [ ] Publishing does not generate static HTML copies for portfolios.
- [ ] Share, unpublish, and delete operate on the selected website only.
- [ ] Client-to-client data leakage and cross-portfolio cache leakage are tested.
- [ ] Existing WordPress and generic static website workflows pass regression tests.
- [ ] No changes are made to `lawreay/Custom-portfolios`.
- [ ] No secrets or customer/runtime data are committed.

## 9. Security note

The reference repository's `config.php` was found to contain a database credential in source control. Treat it as exposed: rotate it at the hosting provider, verify the replacement is not committed, and review whether it was reused elsewhere. Do not copy or repeat the credential in InstaWeb.

## 10. Current status

- [x] Repositories identified and key files inspected.
- [x] InstaWeb website creation, ownership association, dashboard entry points, storage, and current publishing constraints reviewed.
- [x] Standalone portfolio authentication/database coupling and fixed `admin` lookup identified.
- [x] Architecture decision agreed: dynamic PHP rendering using the selected template and website-linked database content; no static portfolio generation.
- [ ] Exact reference SQL schema and complete field-by-field mapping verified.
- [ ] PHP-enabled public routing/hosting path confirmed for portfolio URLs.
- [x] First application-code slice implemented on draft PR #24 (PHP syntax checks pass); database/browser integration tests remain pending.
- [ ] No commits made to `lawreay/Custom-portfolios`.

**Next action:** complete local database and browser tests for the implemented slice, verify publishing and unpublishing, test two separate owners, then finish any remaining reference-template sections. Keep all implementation changes inside InstaWeb.


## 11. Implementation status (2026-10-23)

The first vertical slice now includes Portfolio Studio profile editing, project add/remove, three design choices (`classic`, `editorial`, `3d`), dynamic published rendering at `/p/{slug}`, owner-only database-backed preview, and publish/unpublish integration without static portfolio copies. Existing portfolio CSS and 3D interaction files were copied into InstaWeb; the reference repository was not modified.

GitHub Actions PHP lint passes on an earlier implementation commit and is running against the latest changes. The latest commit's check must pass before merge. Local database migration execution, cross-account access tests, full visual review, and generic static/WordPress regression tests remain outstanding.
