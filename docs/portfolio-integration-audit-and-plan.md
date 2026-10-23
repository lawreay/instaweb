# Customizable Portfolio Integration Audit and Plan

**Target repository:** `lawreay/instaweb`  
**Reference repository:** `lawreay/Custom-portfolios`  
**Documentation branch:** `docs/portfolio-integration-plan`  
**Status:** Audit and plan only. No application code or database schema has been changed by this document.  
**Scope constraint:** All implementation changes must be made in InstaWeb. Do not commit changes to the Custom-portfolios repository.

## 1. Executive decision

Integrate the existing customizable portfolio into InstaWeb's current website workflow. Do not build a second client dashboard and do not embed a second authentication system.

Reuse the current portfolio's visual design, content model, and suitable editor interactions, but adapt them to InstaWeb's PHP MVC conventions, authenticated user identity, database connection, storage, preview, and publishing lifecycle. Treat the existing portfolio as a reference implementation, not as an application to copy wholesale.

## 2. Findings verified in repository source

### InstaWeb

- The application is a PHP MVC application with MySQL.
- `app/Config/routes.php` registers the current dashboard, website creation, website detail, file manager, code editor, preview, publish, and unpublish routes.
- `app/Controllers/WebsiteController.php` authenticates through InstaWeb's `AuthService`, creates a website record, creates its draft directory, and prepares starter content.
- `app/Models/Website.php` already associates each website with `user_id`, has a globally unique `slug`, and provides owner-scoped lookups such as `findForUser(id, userId)`.
- `app/Views/websites/create.php` offers a `portfolio` starter choice today, but it is handled by the generic starter-file flow rather than the dedicated customizable portfolio application.
- `app/Views/websites/index.php` and `app/Views/websites/show.php` already provide the shared website list, Manage/detail workflow, and existing dashboard shell.
- Draft files live under `storage/projects/{userId}/{slug}`; published output is placed under `public/sites/{projectId}/{slug}`.
- `app/Services/PublishingService.php` publishes a filtered set of static file extensions for ordinary sites. PHP files are excluded. WordPress has a separate explicit PHP-enabled path.
- Therefore, copying the current database-driven `index.php` into a normal InstaWeb static project will not produce a working portfolio. The portfolio must be rendered to static output during preview/publish, or a separate supported dynamic hosting architecture must be designed. For the MVP, generated static output best fits the existing publisher and avoids enabling PHP execution for ordinary customer sites.

### Custom-portfolios

- `index.php` loads content from its own configuration and database helpers and currently resolves the public owner using a fixed username (`admin`). This is not a suitable multi-client ownership model as-is.
- `admin/dashboard.php` is a substantial portfolio editor. It loads profile settings, projects, goals, interests, experience, skills, education, news, popups, bookings/requests/comments, visits, section backgrounds, and uploaded images.
- `admin/login.php` implements its own login and recovery flow against a separate `portfolio_users` table. This must not be integrated as a second authentication system.
- `config.php` creates a separate database connection, starts a separate session for the standalone admin, and performs schema changes at runtime. These responsibilities must not be copied into InstaWeb.
- The repository's README documents a portfolio-specific schema with tables for users, settings, projects, experience, skills, sections, messages, and files. The exact checked-in schema file path still needs to be located and verified before mapping every column.
- The current portfolio has a wider feature set than the first InstaWeb integration needs. Its engagement, popup, news, and visitor analytics features should be individually assessed rather than copied automatically.

## 3. Architecture to implement

### One dashboard workflow

1. The client signs into InstaWeb.
2. The client creates a website and selects **Portfolio**.
3. InstaWeb creates its normal `websites` record, associated with the signed-in `user_id`, and prepares portfolio data/settings for that website.
4. The website list displays the portfolio using the existing shared dashboard.
5. **Manage** opens the portfolio editor for that specific website ID, with owner verification on every request.
6. The client edits structured fields, chooses a supported template, saves a draft, and previews it.
7. **Publish** renders the saved portfolio into static HTML/CSS/JS/assets in the project's draft output and uses the existing safe publishing service to publish the generated files.
8. **Share** provides the existing public URL. Unpublish and delete continue through the existing lifecycle.

### Separation of responsibilities

- **InstaWeb authentication:** source of truth for the current user. No portfolio-specific login or parallel session.
- **Website record:** source of truth for owner, slug, status, plan limits, and public URL.
- **Portfolio content:** structured data linked to the InstaWeb website record. Reuse existing tables only if their ownership and constraints fit; otherwise add versioned schema migrations under InstaWeb.
- **Template renderer:** transforms saved content plus selected template into a static site. Templates contain presentation, not a particular client's personal data.
- **Preview:** render from the owner's saved draft without making it public.
- **Publish:** generate and validate output, then invoke the existing publisher. Do not allow arbitrary PHP files in normal portfolio output.

The likely association is one portfolio configuration per InstaWeb website record for the MVP. Avoid a separate portfolio account ID. If the existing database proves a better mapping is already present, document that before creating new tables.

## 4. Reuse and migration map

| Current portfolio capability | Integration treatment |
|---|---|
| Visual design and responsive layout | Preserve as the first template; separate hardcoded personal data from markup |
| Profile, bio, contact and CV | MVP structured fields |
| Projects and technology tags | MVP structured records |
| Experience, education and skills | MVP structured records |
| Theme colors and typography | Reuse supported settings with validation and safe defaults |
| Section visibility/order/backgrounds | Start with simple supported options; avoid an unrestricted page builder |
| Image and CV uploads | Move to InstaWeb-managed storage with strict type, size, and path validation |
| Standalone login and password recovery | Do not migrate; use InstaWeb auth |
| Standalone DB connection and config | Do not migrate; use InstaWeb configuration and model/service patterns |
| News, popups, bookings, requests, comments, visitor logs | Defer until core portfolio creation/edit/publish is reliable; assess privacy and abuse controls before adding |
| Fixed `admin` public owner lookup | Replace with website/slug resolution and published data only |

## 5. Data and security requirements

- Every portfolio-content query must be scoped to the authenticated user's owned website. Never trust a submitted `user_id` or website ID without checking ownership server-side.
- Use prepared statements through InstaWeb's existing model/database conventions.
- Use InstaWeb CSRF protection for every state-changing editor action.
- Escape output for the context where it is rendered; validate color, URL, template, and typography settings against allowlists.
- Store uploads outside executable/public draft paths where practical; validate actual MIME type, extension, size, and generated filenames. Do not allow uploaded PHP or script execution.
- Never copy the reference repository's credentials, session setup, database bootstrap, or runtime schema-alteration code.
- Use versioned, repeatable database migrations. Do not run opportunistic `ALTER TABLE` operations from a normal page request.
- Preserve current static publishing protections. PHP must remain excluded from ordinary portfolio output.
- Do not expose private drafts or CV files until the product explicitly defines whether each file is public.
- Add tests proving Client A cannot read, edit, preview, publish, or delete Client B's portfolio.

## 6. Phased implementation plan

### Phase 0 — Finish audit and map schema
- Locate the exact portfolio SQL schema and all content write handlers in the reference repository.
- Map each existing content type to the MVP scope and proposed InstaWeb data model.
- Inspect InstaWeb tests, route dispatch behavior, preview response handling, publishing error handling, and file upload conventions.
- Confirm the production web server supports the static output paths and current public URL scheme.
- Record any unknowns before changing schema or controllers.

**Exit criteria:** a field-by-field mapping, a route map, and a reviewed data migration plan.

### Phase 1 — Foundation and website type
- Add an explicit portfolio project type to the InstaWeb website lifecycle without breaking existing static, WordPress, or other starter types.
- Add schema migrations only for fields/tables that are genuinely missing.
- Initialize a portfolio draft/config when a portfolio website is created.
- Keep existing owner checks, plan limits, slug uniqueness, CSRF, notifications, and rollback-on-creation-failure behavior.

**Exit criteria:** selecting Portfolio creates one owner-linked website and its portfolio draft without breaking other project types.

### Phase 2 — Portfolio editor
- Add portfolio-specific controller/service/model/view code inside InstaWeb.
- Link Manage from the existing website detail/list views to the editor for the selected website.
- Implement profile, projects, skills, experience, education, contact, CV, and basic theme settings.
- Keep business logic in services, SQL in models, HTTP handling in controllers, and UI in views.

**Exit criteria:** content saves and reloads for the correct owner; no second login or dashboard shell is introduced.

### Phase 3 — First template and preview
- Refactor the reference design into a template renderer with no hardcoded personal profile.
- Generate a preview from saved portfolio data using safe output escaping and allowlisted settings.
- Keep template-specific code separate from the content persistence model.

**Exit criteria:** two test users can use the same template with different data and cannot see each other's draft content.

### Phase 4 — Static publishing and sharing
- Generate a complete static site into the website's draft directory.
- Validate generated files and assets before publishing.
- Use the existing publishing service and public URL logic.
- Verify re-publish replaces only the selected website's public output; unpublish removes public access while preserving the draft.
- Make Share point to the actual published URL, not to the editor or preview.

**Exit criteria:** public portfolios work without requiring PHP execution inside the static output folder.

### Phase 5 — Regression, security, and deployment tests
- Run existing test suite and add portfolio-specific unit/integration tests.
- Test two-user isolation, CSRF, invalid IDs, invalid slugs, uploads, template switching, missing data, save failures, publish failures, unpublish, delete, and mobile layout.
- Test static output on the target hosting environment, including CSS, JavaScript, images, CV links, URL rewriting, and asset paths.
- Verify WordPress and generic static site flows are unchanged.
- Deploy only after a reviewable pull request and successful checks.

**Exit criteria:** creation-to-share flow passes in a test environment and existing website workflows have no regression.

## 7. Acceptance checklist

- [ ] Portfolio creation uses InstaWeb authentication and its existing website record.
- [ ] No portfolio-specific login is required.
- [ ] Existing Manage action opens the portfolio editor for the selected website.
- [ ] One shared editor serves all clients; saved data remains isolated.
- [ ] Personal content is data-driven, not hardcoded in the template.
- [ ] Template switching does not delete content.
- [ ] Draft save and preview do not accidentally publish changes.
- [ ] Publish generates only safe static output and returns the existing public URL.
- [ ] Share, unpublish, and delete operate on the selected website only.
- [ ] Existing WordPress and static website workflows pass regression tests.
- [ ] No changes are made to `lawreay/Custom-portfolios`.
- [ ] No secrets or customer/runtime data are committed.

## 8. Immediate security action

The reference repository's `config.php` contains a database credential in source control. Treat that credential as exposed: rotate it at the hosting provider, verify the replacement is not committed, and review whether the same credential was reused elsewhere. Do not copy any credential into InstaWeb. This plan intentionally does not repeat the value.

## 9. Current implementation status

- [x] Repositories identified and key files inspected.
- [x] InstaWeb website creation, owner association, dashboard entry points, storage, and publishing constraints reviewed.
- [x] Standalone portfolio authentication and database coupling identified.
- [x] Main architectural blocker identified: the current publisher excludes PHP for normal sites.
- [ ] Exact reference SQL schema path and complete field-by-field mapping verified.
- [ ] No application code changed as part of this audit.
- [ ] No schema changes made.
- [ ] No commits made to the Custom-portfolios repository.

**Recommended next action:** complete Phase 0, then implement the smallest vertical slice: create portfolio → manage structured profile → preview → publish a generated static portfolio → share. Defer secondary engagement features until this flow is reliable.
