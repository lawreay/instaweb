# TurboHostMw

TurboHostMw is a custom PHP web hosting application for creating, managing, editing, publishing, and monitoring customer websites. It is built as a small MVC framework on top of native PHP and MySQL, with project storage, public publishing, file management, admin tools, and WordPress provisioning support.

## What this app does

The platform allows a signed-in user to:

- create a website project
- choose a starter template or WordPress project
- edit files in a draft workspace
- preview files before publishing
- publish to a public site folder
- manage uploaded files and folders
- track usage and site status
- receive notifications and manage billing/admin tasks

The public-facing website includes landing pages, pricing, FAQs, legal pages, login/register flows, and support pages.

## Technology stack

- PHP 8+ with a custom MVC structure
- MySQL database
- Sessions and cookie-based remember me flows
- PHPMailer for email delivery
- Apache/public folder hosting pattern
- Flat file storage under `storage/projects` and published sites under `public/sites`

## Application architecture

The app is organized into a lightweight MVC structure:

- `app/Controllers` contains HTTP handlers
- `app/Models` contains database-access logic for entities like users and websites
- `app/Services` contains business logic such as auth, publishing, notifications, storage, payments, and WordPress provisioning
- `app/Core` contains the framework pieces such as routing, sessions, validation, database access, and view rendering
- `app/Views` holds the template files for dashboard, marketing pages, email content, and administrative screens
- `app/Config` contains route registration, environment config, and startup settings

The app bootstraps in `index.php`, which loads the app and runs the router.

## Request lifecycle

1. The browser requests a route.
2. `index.php` starts the application.
3. `App::run()` loads config and routes.
4. Session handling and remember-me login logic run.
5. The router matches the request path and method.
6. A controller method executes the action.
7. Services perform business logic and database access.
8. A view renders HTML, or a response redirect is issued.

## Routing

Routes are defined in `app/Config/routes.php`.

Examples include:

- marketing pages: `/`, `/pricing`, `/contact`
- auth: `/login`, `/register`, `/forgot-password`
- dashboard: `/dashboard`, `/dashboard/websites`
- file management: `/dashboard/websites/files`
- publishing: `/dashboard/websites/publish`
- admin: `/admin`, `/admin/users`, `/admin/settings`

## Configuration

The main runtime config lives in:

- `app/Config/app.php`
- `app/Config/constants.php`
- `app/Config/routes.php`
- `.env` for environment-specific values such as database and email settings

The app reads environment values via the `env()` helper defined in `app/Config/constants.php`.

Important: production secrets should never be committed to Git. The project includes `.env.example` as the safe template, and `.env` should be kept only on the server or hosting panel.

## Database

The schema is defined in `database/schema.sql` and related SQL files under `database/`.

Core tables include:

- `users`
- `websites`
- `files`
- `subscriptions`
- `payments`
- `analytics`

`app/Models` wraps the SQL logic for each domain object and keeps database work out of the controllers.

## Storage model

Runtime files are deliberately excluded from Git. The application creates working directories as needed, and the web-server account must have write access to the runtime locations:

- `storage/projects/{userId}/{slug}` for active draft content
- `storage/backups/{userId}/{slug}` for generated project backups
- `storage/exports` for generated ZIP exports
- `public/sites/{projectId}/{slug}` for published static content or WordPress output
- `uploads/` for application-managed uploads, where used

These folders may be absent in a fresh checkout. Do not commit customer projects, published output, uploads, backups, exports, or other runtime data. Ensure the deployment environment creates the required folders and grants the PHP process the minimum write permissions it needs.

The storage service manages safe path creation, path sanitization, file creation, folder creation, and directory size checks.

## Feature overview

### 1. Authentication and account management

Handled by controllers in `app/Controllers/AuthController.php` and services such as:

- `AuthService`
- `EmailVerificationService`
- `PasswordResetService`
- `TwoFactorService`

The app supports:

- registration
- login and logout
- remember-me cookies
- password reset
- email verification
- two-factor authentication

### 2. Website creation and draft management

The `WebsiteController` creates a project record, creates a safe storage folder, and then prepares the requested starter content.

For static sites, it creates a new `index.html` draft.
For WordPress sites, it installs a WordPress archive into the project folder and writes a generated `wp-config.php` file.

### 3. File manager

`FileManagerController` manages:

- uploading files
- creating folders and files
- moving items
- deleting items
- downloading/exporting project content
- ZIP import/export

The file management logic keeps operations inside the project directory and uses sanitization so invalid paths do not escape the project root.

### 4. Editor

The editor lets a user open text-based project files and save them back.

Th### 6. WordPress support

WordPress installs are handled by `ProjectStorageService::installWordPress()`.

**Deployment requirement:** the WordPress ZIP package is not stored in Git. Upload the approved WordPress package to the server at the configured `WORDPRESS_PACKAGE_PATH` (defaults to `storage/wordpress/wordpress-6.9.3.zip`) and set `WORDPRESS_PACKAGE_SHA256` in the server-side `.env` to the package's actual SHA-256 checksum. Keep the package outside source control and make sure the PHP process can read it.

The installer:

- resolves the package path
- verifies the package checksum
- checks the archive for unsafe paths and symbolic links
- extracts into a temporary directory
- validates required WordPress files exist
- writes the generated configuration
- moves the final installation into the project directory

This reduces the risk of partially installed or maliciously crafted WordPress packages.

public files separate from editable draft storage.

### 6. WordPress support

WordPress installs are handled by `ProjectStorageService::installWordPress()`.

The installer:

- resolves the package path
- verifies the package checksum
- checks the archive for unsafe paths and symbolic links
- extracts into a temporary directory
- validates required WordPress files exist
- writes the generated configuration
- moves the final installation into the project directory

This reduces the risk of partially installed or maliciously crafted WordPress packages.

### 7. Notifications

The notifier system sends user alerts for events such as:

- account actions
- website creation
- payment changes
- admin broadcasts

The relevant logic lives in `app/Services/NotificationManager.php` and related notification controllers.

### 8. Admin tools

Admin routes allow an admin user to:

- view dashboard metrics
- manage users
- moderate websites
- review payments
- manage notifications and settings
- publish legal policies

### 9. Payments and plans

Payments are handled through the `PaymentController` and related services. The app tracks user plans and website limits via the `Website` model and `PlanService`.

### 10. SEO and legal pages

The app includes:

- sitemap generation
- legal pages such as privacy and terms
- policy acceptance flow

## Project structure

```text
app/
  Config/
  Controllers/
  Core/
  Models/
  Services/
  Views/
config/
  (if present in deployment, varies by host)
database/
public/
  assets/
  sites/
storage/
  backups/
  exports/
  projects/
  wordpress/
vendor/
composer.json
index.php
```

## Local development setup

### Requirements

- PHP 8.0+
- MySQL
- Apache or Nginx with URL rewriting enabled
- Composer

### Steps

1. Clone the repository.
2. Copy `.env.example` to `.env` and fill in your local values.
3. Create the database and import `database/schema.sql`.
4. Install PHP dependencies if needed:

```bash
composer install
```

5. Configure your local web server to point to the project root or `public` directory based on your hosting pattern.
6. Start the app and visit the local URL.

## Security notes

This project is a hosting app and handles customer content, so remember to:

- never commit real production secrets
- keep `.env` out of version control
- validate paths before writing files
- restrict project publishing and output folders
- use strong server-side security for uploaded content
- keep WordPress packages verified and isolated

## Recommended next improvements

The app already has a strong foundation, and the next improvements that make it production-safe are:

- separate WordPress database credentials per site
- stricter user/site isolation
- more explicit WordPress lifecycle states
- stronger backup and restore strategy
- more granular deployment and environment validation

## Maintainer guidance

When working on this project:

- keep business logic in `app/Services`
- keep HTTP handling in `app/Controllers`
- keep SQL in `app/Models`
- keep presentations in `app/Views`
- keep route registration centralized in `app/Config/routes.php`

This keeps the codebase easier to extend and safer to maintain.
