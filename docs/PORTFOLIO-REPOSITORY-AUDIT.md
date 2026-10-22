# Portfolio Integration: Repository Audit

**Branch:** `feature/portfolio-integration`  
**Audit status:** Initial source inspection; no application code or database has been changed.  
**Reference project:** `lawreay/Custom-portfolios`

## 1. Confirmed InstaWeb architecture

Inspected `app/Controllers/WebsiteController.php`, `app/Models/Website.php`, `app/Services/ProjectStorageService.php`, `database/schema.sql`, and `composer.json` on `main`.

- InstaWeb is a PHP application with an existing controller/model/service structure and PHP >= 8.0.
- Authentication is already centralized through `AuthService`; the website controller enforces signed-in access and uses CSRF validation on writes.
- A website is stored in the existing `websites` table, with `user_id`, `website_name`, globally unique `slug`, status, storage/bandwidth fields, and expiry information.
- Draft project files are stored under a path derived from user ID and website slug by `ProjectStorageService`.
- `WebsiteController::create()` exposes starter choices including `portfolio`; `store()` currently treats every non-WordPress starter as a generated `index.html` project.
- Website creation already includes plan limits, slug generation, file records, storage accounting, notifications, and rollback of the project record/files if starter creation fails.
- Public URL generation and publishing have existing services. The precise public request dispatch and how published files are served still need to be traced before choosing the portfolio runtime.

## 2. Confirmed Custom-portfolios architecture

Inspected `config.php`, `index.php`, and the beginning of `admin/dashboard.php`.

The reference application contains a substantial PHP-based portfolio renderer and admin editor. Its data includes profile/settings, projects, goals, interests, experience, skills, education, news, popups, engagement forms, visits, section backgrounds, uploaded images, and CV files. The public renderer uses a standalone configuration/helper layer and currently resolves a default public user (username `admin`), rather than an InstaWeb website owner.

### Incompatibilities to avoid copying wholesale

- Separate database connection, session name, login/session checks, and admin routes.
- Standalone public renderer and URL assumptions.
- Single-default-user public lookup instead of linking content to an InstaWeb website and its owner.
- Schema-altering code invoked from ordinary configuration initialization. InstaWeb integration should use an explicit, versioned migration.
- Existing database credentials are embedded in the reference repository's source. They are intentionally not reproduced in this document. Treat any real credential committed there as exposed: rotate it with the provider, review repository history, and move secrets to environment configuration. Do not reuse those credentials in InstaWeb.
- A dedicated check is still required to locate and inspect any debug/password utilities, seed/reset scripts, upload validation, and all relevant table definitions before reuse.

## 3. Integration decision

Keep the existing InstaWeb website lifecycle, user ownership, authentication, draft storage, and publishing flow. Build the portfolio editor/gallery and reusable templates as a React experience, with PHP endpoints/services and MySQL persistence.

The old project is a functional/visual reference, not a second application to embed.

## 4. Proposed first vertical slice

1. Trace the exact route and published-site serving behavior, and inspect the create-website view and template selector.
2. Define a portfolio record linked to an existing `websites.id` and `user_id`; decide whether fields belong in normalized child tables or a documented JSON payload based on the existing data and query needs.
3. Add a versioned migration, not schema changes at request time.
4. Implement ownership-protected PHP read/write endpoints using existing CSRF/auth conventions.
5. Build the Classic template as a React component receiving normalized portfolio data.
6. Connect creation, save, preview, and publish to InstaWeb without introducing a separate login or publishing system.
7. Add automated tests for owner isolation, saving, draft privacy, slug collision, template switching, and regression of existing website types.

## 5. Still to verify before application implementation

- The exact route definitions and public URL/rewrite dispatch.
- The complete create-website view and starter template rendering.
- Published-site storage path and how PHP/dynamic projects are served.
- InstaWeb test conventions, migration conventions, and current CI baseline.
- Full reference schema and the security/behavior of image and CV uploads, public forms, seed scripts, and debug scripts.
- Whether any existing portfolio data must be migrated.

## 6. Current status

- Created branch `feature/portfolio-integration` from `main`.
- This audit document is the first deliverable on that branch.
- No production data, database schema, credentials, or `main` branch have been changed.
- Do not merge this branch until the audit is complete, the vertical slice is implemented, and tests are run.
