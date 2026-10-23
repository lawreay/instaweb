# Website-Type File Access Policy

## Purpose

InstaWeb does not offer arbitrary PHP hosting to clients. Static projects accept only the allow-listed static file types; PHP files are rejected during direct uploads, manual file creation, and ZIP imports. WordPress remains a managed product type rather than a general-purpose PHP project.

## Managed project types

- **WordPress:** Clients use the WordPress dashboard for site content and administration. InstaWeb File Manager and Code Editor routes are disabled for WordPress projects.
- **Portfolio:** Portfolio content is managed through the dedicated portfolio editing flow. InstaWeb File Manager and Code Editor routes are disabled for portfolio projects.
- **Static projects:** File Manager and Code Editor remain available subject to the existing upload and path-safety validation.

The restriction is enforced in the controllers, not just by hiding dashboard links. Direct GET, download/export, upload, ZIP import, create, move, copy, and delete requests all resolve the owned website and check its saved template type before proceeding.

## Persisted project type

The selected website template is stored in `websites.template_key`. New projects save the selected template when created. The website list and management page use that value to hide unsupported raw-file tools.

For an existing installation, back up the database and apply `database/migrations/20261023_001_add_website_template_key.sql` after `20261022_001_create_portfolios.sql`. The migration adds the column if missing and backfills WordPress projects from their `wp-config.php` file record and portfolios from the `portfolios.website_id` relationship. Existing legacy projects that have neither record cannot be reliably identified automatically and must be classified explicitly before deployment.

Fresh installations should use the updated `database/schema.sql` or `database/schema_infinityfree.sql`, which already include `template_key`.

## Verification checklist

- Confirm the migration ran successfully before deploying the code; the application queries `websites.template_key`.
- For a WordPress project, verify the File Manager link is hidden and direct visits to file-manager and code-editor URLs redirect to the project page.
- Repeat the same checks for a portfolio project.
- Verify static projects still show File Manager and Code Editor.
- Verify `.php`, `.phtml`, and `.phar` uploads/manual creation/import remain rejected.
- Verify public site URLs are unchanged; this policy concerns authenticated management tools, not visiting a published public website.
