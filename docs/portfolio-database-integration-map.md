# Portfolio Database Integration Map

**Status:** Database audit plus implementation tracking. Migration SQL and application changes are on the draft InstaWeb integration branches; local migration execution has not been verified.
**Target repository:** `lawreay/instaweb`
**Reference repository:** `lawreay/Custom-portfolios` (read-only; do not modify)
**Agreed runtime:** Dynamic PHP rendering on InfinityFree. Do not generate static HTML snapshots for portfolios.
**Scope:** Understand the two SQL schemas, explain their relationship, and define a safe migration path before writing SQL.

## 1. Source files inspected

### InstaWeb

- [`database/schema.sql`](https://github.com/lawreay/instaweb/blob/main/database/schema.sql) — source schema; blob SHA `42d34ab778b9b363aa2b0e193e2e3f5aab9b06cc`.
- [`database/schema_infinityfree.sql`](https://github.com/lawreay/instaweb/blob/main/database/schema_infinityfree.sql) — InfinityFree deployment schema; blob SHA `b23bb2e26e70103fd0edf40db1a86cb9703d8fd0`.
- [`app/Controllers/WebsiteController.php`](https://github.com/lawreay/instaweb/blob/main/app/Controllers/WebsiteController.php) — authenticated website creation and ownership checks.
- [`app/Models/Website.php`](https://github.com/lawreay/instaweb/blob/main/app/Models/Website.php) — website records and queries.
- [`app/Services/ProjectStorageService.php`](https://github.com/lawreay/instaweb/blob/main/app/Services/ProjectStorageService.php) — creates project folders and starter files.
- [`app/Services/PublishingService.php`](https://github.com/lawreay/instaweb/blob/main/app/Services/PublishingService.php) — current public publishing behavior.
- [`app/Services/PublicSiteUrlService.php`](https://github.com/lawreay/instaweb/blob/main/app/Services/PublicSiteUrlService.php) — public URL format.

Both InstaWeb schema files define the same core tables relevant to this integration: `users`, `websites`, and `files`, among the platform's other account and hosting tables. The `websites` table is the key existing link between an authenticated InstaWeb account and an individual website.

### Custom-portfolios

- [SQL dump](https://github.com/lawreay/Custom-portfolios/blob/main/if0_41898653_lawreay%20%283%29.sql) — blob SHA `b19fefce0782d1454e5f57ea60d60d01e0d48b7e`.
- [`config.php`](https://github.com/lawreay/Custom-portfolios/blob/main/config.php) — legacy DB connection, sessions, and portfolio helper functions.
- [`admin/dashboard.php`](https://github.com/lawreay/Custom-portfolios/blob/main/admin/dashboard.php) — existing editor and management experience.
- [`index.php`](https://github.com/lawreay/Custom-portfolios/blob/main/index.php) — current public portfolio renderer.

The SQL dump is an export from the legacy portfolio database. It includes table definitions, legacy user data, and example/personal records. It must not be imported wholesale into the InstaWeb production database.

## 2. What the current schemas mean

### InstaWeb ownership model

The current `websites` table includes:

- `id`: unique website/project ID.
- `user_id`: owner ID referencing InstaWeb's `users.id`.
- `website_name`, `slug`, `subdomain`, `custom_domain`.
- `status`: draft/published/suspended/expired.
- storage, bandwidth, expiry, and creation metadata.

The existing website creation flow authenticates the user, creates a `websites` record, and creates a separate draft folder under that user's storage. The selected starter key is currently used to create starter content, but **the schema does not persist a template/runtime type field**. The existing public URL is based on `/sites/{website_id}/{slug}/`.

### Custom-portfolios data model

The legacy SQL dump contains these portfolio tables:

| Legacy table | What it stores |
|---|---|
| `portfolio_users` | Standalone portfolio login accounts and password reset fields |
| `portfolio_settings` | Main profile, hero/about/contact fields, CV/profile/favicon paths, visual settings |
| `portfolio_projects` | Projects, descriptions, categories, icons/images, technology stack and links |
| `portfolio_skills` | Skill names, categories, proficiency, color, ordering |
| `portfolio_education` | Education history |
| `portfolio_experience` | Work/professional experience |
| `portfolio_sections` | Custom sections such as goals, interests, challenges, partnerships |
| `portfolio_section_backgrounds` | Section background images and overlay settings |
| `portfolio_popups` | Public popups, links, images, timing and trigger options |
| `portfolio_news` | Portfolio news/updates |
| `portfolio_files` | Uploaded file metadata |
| `portfolio_bookings` | Visitor booking requests |
| `portfolio_requests` | Visitor enquiries/service requests |
| `portfolio_messages` | Contact messages |
| `portfolio_comments` | Visitor comments |
| `portfolio_visits` | Portfolio visit records |

Most legacy content tables use a `user_id` column. In the old application, that ID belongs to **`portfolio_users.id`**, not InstaWeb's `users.id`. The two integer ID spaces are unrelated, even when both happen to contain the value `1`. Treating them as interchangeable would risk assigning one person's content to another account.

The current public `index.php` calls `get_public_user_id('admin')`, so it renders the portfolio associated with the fixed legacy username `admin`. That must be replaced with website-specific resolution. The current editor already passes a user ID to many helpers, but those helpers are built around the legacy authentication/session and database connection.

## 3. Recommended ownership model

**Use InstaWeb's `websites.id` as the identity of each portfolio site.** Continue using `websites.user_id` to identify the owning InstaWeb account.

This supports one user having multiple portfolios, each with different content, slug, publication status, and selected template. If content tables were keyed only by the InstaWeb account's `user_id`, two portfolios belonging to the same account would accidentally share the same projects and profile. Therefore, portfolio-specific data should be scoped to `website_id`.

Conceptual relationship:

```text
users (InstaWeb account)
  └── websites (one row per site/portfolio; websites.user_id -> users.id)
        ├── portfolio_settings (one row per portfolio; website_id unique)
        ├── portfolio_projects (many rows per website)
        ├── portfolio_skills (many rows per website)
        ├── portfolio_education / portfolio_experience
        ├── portfolio_sections / portfolio_section_backgrounds
        ├── portfolio_news / portfolio_popups
        └── portfolio_visits / portfolio_messages / requests / comments / bookings
```

All management requests must first resolve the selected website with both its ID and the authenticated owner's ID (equivalent to InstaWeb's existing `findForUser(websiteId, userId)` pattern). All writes, uploads, deletes, preview routes, and public actions must remain scoped to the selected website.

## 4. Implemented schema mapping and remaining extensions

### A. Existing `websites` table

The current implementation uses `websites.template_key` as the controlled website runtime/type selector. The value `portfolio` identifies a database-rendered portfolio; static template choices and `wordpress` keep their existing paths. The migration file `database/migrations/20261023_001_add_website_template_key.sql` adds the column when missing and backfills recognizable existing site types. The migration has not been verified as executed against the user's local database.

### B. Portfolio profile and projects

The current implementation adds:

- `portfolios`: one profile/settings row per website, including `website_id`, `user_id`, `template_key`, display name, headline, bio, email, location, avatar path, social-links JSON, and publication metadata.
- `portfolio_projects`: project cards attached to `portfolios.id`, with title, summary, optional URL/image path, and sort order.
- The editor and public renderer validate website/user ownership through the existing `websites` record. Public reads require the website status to be `published`.

Migration source: `database/migrations/20261022_001_create_portfolios.sql`. The tables use InnoDB and avoid foreign keys to existing MyISAM `websites`/`users` tables; project rows cascade from the InnoDB portfolio row.

### C. Future schema extensions

The legacy portfolio contains additional sections and engagement data (skills, education, experience, sections, visits, messages, requests, comments, and bookings). These are not all represented by the current InstaWeb schema/editor yet. Map and migrate only the fields required by the next implementation slices, keeping all content isolated by the selected `website_id`. Store upload paths as website-scoped relative paths rather than old-domain absolute URLs.

The old dump's `id` columns and `ALTER TABLE` statements are separated: run-time migrations must explicitly create primary keys and auto-increment behavior, rather than copying only the initial `CREATE TABLE` statements. The dump also contains seeded records. Do not bring those sample rows into a new client's portfolio by default.

### C. Do not migrate legacy authentication

Do not create or use `portfolio_users` in the integrated runtime. InstaWeb's existing `users` table and authentication service become the single source of account identity. Remove the standalone portfolio login/reset flow from the integrated entry path, while retaining the existing portfolio dashboard's editing capabilities inside InstaWeb's authenticated website-management flow.

### D. Template selection

The legacy `portfolio_settings` schema has extensive appearance settings, but the inspected dump does not provide a reliable portfolio-template registry/selection field. Add a controlled `template_key` at the portfolio/site level and map it to a server-owned template renderer. A template key must never be used to include arbitrary client-provided PHP or a path from a request parameter.

## 5. Runtime and publishing constraint that affects this schema

The agreed behavior is **dynamic PHP/MySQL rendering on InfinityFree**, with no static HTML snapshots for portfolios.

The current InstaWeb implementation is not yet wired for this:

- `ProjectStorageService::createStarterIndex()` creates an `index.html` for the `portfolio` starter, just as it does for other non-WordPress starters.
- `PublishingService` requires `index.html` for non-WordPress projects and copies only allowed static extensions for them.
- PHP execution and PHP-specific access rules are currently special-cased for WordPress.
- `PublicSiteUrlService` returns the existing `/sites/{id}/{slug}/` URL.

So merely importing the portfolio tables or copying the legacy PHP files would **not** make a portfolio render dynamically. The integration must add a deliberate portfolio runtime path. It should publish/serve a platform-owned PHP entry point and template assets, resolve the exact `website_id`, load that site's data, and apply its selected template on each request. PHP execution must be enabled only for this controlled portfolio runtime. Do not allow clients to upload or edit arbitrary executable PHP through the file manager.

Public rendering must verify that the website exists, is of type `portfolio`, is published and not suspended/expired, and uses an allowed template. The public visitor does not need to be logged in; the owner does need InstaWeb authentication to manage the site. Do not cache one portfolio's personalized HTML under another portfolio's URL.

## 6. Data migration and rollout plan

1. **Back up and inspect** the production InstaWeb database and the legacy portfolio database. Confirm the live schema rather than assuming repository SQL exactly matches hosting.
2. **Create additive migrations** in InstaWeb only. Do not overwrite existing `users`, `websites`, or `files` tables.
3. **Add persisted website type/template metadata** with safe defaults so current sites remain unchanged.
4. **Create website-scoped portfolio tables** with explicit keys, indexes, and foreign keys.
5. **Create a test portfolio** through InstaWeb's existing Create Website flow and ensure its `websites.user_id` and `website_id` relationship is correct.
6. **Port the dashboard behavior** into InstaWeb using its authenticated user and selected website context. Preserve profile, projects, skills, education, experience, CV/uploads, appearance, and existing engagement tools as appropriate.
7. **Port the renderer** to load settings/content by `website_id` and the chosen server-owned template. Replace the hardcoded `admin` lookup.
8. **Add the dynamic portfolio public path** while keeping generic static publishing and WordPress publishing regression-safe.
9. **Migrate existing personal content only deliberately**, with an explicit mapping from legacy portfolio user ID to the chosen InstaWeb website ID. Never equate legacy `user_id` with InstaWeb `users.id` by numeric coincidence.
10. **Test with at least two accounts and two portfolio websites**, including two sites owned by the same account if multi-site is enabled. Verify no cross-portfolio reads, writes, uploads, visitor messages, or cache leakage.
11. **Deploy behind a controlled feature path**, back up first, and verify create/manage/preview/publish/share/unpublish/delete on InfinityFree.

## 7. Minimum acceptance checks

- [ ] Creating a portfolio creates one InstaWeb `websites` row owned by the authenticated user.
- [ ] The selected runtime and template are persisted.
- [ ] Manage opens the existing portfolio dashboard experience for that website, without a second login.
- [ ] Each portfolio's profile, projects, skills, education, experience, settings, and uploads are isolated by `website_id`.
- [ ] Public URL dynamically renders the matching website's database content and selected template.
- [ ] Draft/private and unpublished portfolios are not publicly exposed.
- [ ] Publishing does not create a static HTML snapshot for portfolio sites.
- [ ] Only platform-owned PHP renderer files can execute; user uploads cannot introduce PHP execution.
- [ ] Visitor messages, bookings, requests, comments, and visit analytics attach to the correct portfolio.
- [ ] Existing static website and WordPress create/publish/edit/unpublish flows still work.
- [ ] Migration can be backed up, applied, verified, and rolled back safely.
- [ ] No changes are made to `lawreay/Custom-portfolios`.

## 8. Security note

The public Custom-portfolios repository's tracked `config.php` contains a database credential. Treat it as exposed: rotate the credential at the hosting provider, review whether it was reused, and remove secrets from tracked source in a separate security cleanup. Do not copy that file or any credentials into InstaWeb. The integrated app should use InstaWeb's existing configuration/environment mechanism.

## 9. Audit conclusion

The schemas can be integrated, but they should **not** be merged by blindly importing the legacy dump. InstaWeb already supplies the account identity and per-website ownership. The clean design is to make `websites.id` the portfolio identity, convert portfolio data to `website_id` scoping, remove standalone portfolio authentication from the integrated flow, persist the selected template/runtime, and add a controlled dynamic PHP publishing path.

This document began as a schema audit. As of 2026-10-23, InstaWeb contains portfolio tables/website-template migration files and a first dynamic-rendering implementation on draft PR #24. The migrations have not been verified as applied to the user's local database. The legacy reference repository remains untouched.


## 10. Current implementation status (2026-10-23)

- Portfolio website runtime is identified by `websites.template_key = 'portfolio'`.
- Portfolio profile and project content are stored in `portfolios` and `portfolio_projects`, scoped to the owning website/user.
- The editor routes validate the signed-in owner and CSRF token.
- Published portfolio requests resolve through `/p/{slug}` and render from database records; draft preview uses the owner-only preview route.
- Portfolio publishing removes any stale static copy and changes the website lifecycle status instead of generating static HTML.
- Migration execution, two-account isolation, browser behavior, and static/WordPress regression tests remain to be verified locally.
