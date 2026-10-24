# Portfolio Integration: Repository Audit

**Branch:** `feature/portfolio-integration`  
**Audit status:** Initial audit retained for history; the dynamic-rendering implementation is now in progress on `feature/portfolio-editor-workflow`.  
**Reference project:** `lawreay/Custom-portfolios`

## 1. Confirmed InstaWeb architecture

Inspected `app/Config/routes.php`, `app/Controllers/WebsiteController.php`, `app/Views/websites/create.php`, `app/Controllers/PreviewController.php`, `app/Controllers/PublishController.php`, `app/Services/PublishingService.php`, `app/Services/PublicSiteUrlService.php`, `app/Services/ProjectStorageService.php`, `app/Models/Website.php`, `database/schema.sql`, and `composer.json` on `main`.

- InstaWeb is a PHP application (PHP >= 8.0) with existing controller/model/service patterns. The inspected root has no `package.json`, so React/Vite build tooling is not yet established at the repository root.
- Authentication and ownership checks already exist. Website-specific actions use the existing user identity, website model, and CSRF conventions.
- A website is stored in the existing `websites` table, with `user_id`, `website_name`, globally unique `slug`, status, storage/bandwidth fields, and expiry information.
- Draft project files are stored beneath a path derived from user ID and website slug.
- `WebsiteController::create()` offers a `portfolio` starter choice, but `store()` currently creates a generic `index.html` for every non-WordPress template. The selector label is not a working portfolio builder yet.
- Preview is an authenticated, owner-only route that serves draft files through a controlled endpoint.
- Publishing copies allowed static file types from the draft to `public/sites/{projectId}/{slug}`. Ordinary static projects require `index.html`; PHP execution is specially enabled for the WordPress case. The public URL is a static path, `sites/{id}/{slug}/`.
- Existing website creation also applies plan limits, slug generation, file records, storage accounting, notifications, and cleanup if starter creation fails.

## 2. Agreed architecture

The agreed implementation is **dynamic PHP/MySQL rendering**, not static export. A published portfolio is resolved by its unique website slug at `/p/{slug}`; the renderer loads the published InstaWeb website record, its owner-linked portfolio row, saved projects, and selected design from MySQL on each request.

The portfolio editor and public renderer live inside InstaWeb. Publish/unpublish changes whether the database-backed portfolio is publicly available. Portfolio publishing must not copy a generated HTML snapshot into `public/sites`. Existing generic static websites and WordPress keep their separate publishing paths.

Design source repositories remain read-only references. The current implementation imports portfolio styles and 3D interaction into `public/assets/portfolio-template/`; it does not modify the reference repositories.

We must decide how React is built and deployed before adding frontend dependencies. Options are to bundle the editor assets with a documented build step or use a carefully versioned prebuilt asset bundle. Do not silently introduce a frontend toolchain that contributors or deployment cannot reproduce.

The portfolio content must be stored separately from generated output. Changing a template should regenerate the output from the same content model, not scrape data back out of HTML.

## 3. Confirmed Custom-portfolios architecture

Inspected `config.php`, `index.php`, and the beginning of `admin/dashboard.php`.

The reference application contains a substantial PHP-based portfolio renderer and admin editor. Its data includes profile/settings, projects, goals, interests, experience, skills, education, news, popups, engagement forms, visits, section backgrounds, uploaded images, and CV files. The public renderer uses a standalone configuration/helper layer and currently resolves a default public user (username `admin`), rather than an InstaWeb website owner.

### Incompatibilities to avoid copying wholesale

- Separate database connection, session name, login/session checks, and admin routes.
- Standalone public renderer and URL assumptions.
- Single-default-user public lookup instead of linking content to an InstaWeb website and its owner.
- Schema-altering code invoked from ordinary configuration initialization. InstaWeb integration should use an explicit, versioned migration.
- Existing database credentials are embedded in the reference repository's source. They are intentionally not reproduced in this document. Treat any real credential committed there as exposed: rotate it with the provider, review repository history, and move secrets to environment configuration. Do not reuse those credentials in InstaWeb.
- A dedicated check is still required to locate and inspect any debug/password utilities, seed/reset scripts, upload validation, and all relevant table definitions before reuse.

## 4. Integration decision

Keep InstaWeb's existing website lifecycle, user ownership, authentication, draft storage, preview, and publishing flow. Build the portfolio gallery/editor and reusable templates as a React experience, with PHP endpoints/services and MySQL persistence.

The old project is a functional/visual reference, not a second application to embed.

## 5. Proposed first vertical slice

1. Finish tracing routes, preview, and publishing behavior, including existing tests and migration conventions.
2. Define a portfolio record linked to an existing `websites.id` and `user_id`; use normalized child tables for repeatable sections such as projects, skills, and experience if they need independent editing and ordering.
3. Add a versioned migration, not schema changes at request time.
4. Implement ownership-protected PHP read/write endpoints using existing CSRF/auth conventions.
5. Build the Classic template as a reusable React component receiving normalized portfolio data.
6. Generate static files into the correct draft directory and track generated files/storage with existing services.
7. Connect creation, save, preview, and publish to InstaWeb without introducing a separate login or publishing system.
8. Add automated tests for owner isolation, saving, draft privacy, slug collision, template switching, generated asset paths, and regression of existing website types.

## 6. Still to verify before application implementation

- Existing tests, CI commands, and migration conventions.
- Full reference schema and the security/behavior of image and CV uploads, public forms, seed scripts, and debug scripts.
- Whether any existing portfolio data must be migrated.
- The most maintainable way to bundle and deploy the React editor with this PHP-only repository.
- Whether the Classic template requires any reference assets whose licenses or paths constrain reuse.

## 7. Current status

- Created branch `feature/portfolio-integration` from `main`.
- This audit document is committed on that branch.
- No production data, database schema, credentials, or `main` branch have been changed.
- No app code has been changed yet. Do not merge this branch until the first vertical slice is implemented and tested.


## 7. Implementation status (2026-10-23)

Implemented on the draft branch `feature/portfolio-editor-workflow`:

- Portfolio profile editor with name, headline, bio, public email, location, and design selection.
- Owner-scoped project add/remove actions with CSRF validation.
- Dynamic public renderer at `/p/{slug}`, gated on website status `published`.
- Portfolio publishing skips static HTML copies; owner-only preview renders saved database content.
- Public URLs and sitemap entries distinguish portfolio routes from static website paths.
- Existing portfolio CSS and 3D interaction assets copied into InstaWeb.

Still required before merge:

- Local database test using the migrations and at least two different user accounts.
- Browser verification of profile saves, project CRUD, all three designs, private preview, publish/unpublish, and public URL.
- Regression checks for generic static sites and WordPress.
- Review and integration of any remaining sections/data from the reference portfolio that are not represented by the current profile/projects schema.

PHP syntax checks are run by GitHub Actions. They do not replace the local database and browser checks above.
