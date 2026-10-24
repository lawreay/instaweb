# Custom Portfolio Advanced Editor Integration

This branch will integrate the Custom Portfolios dashboard into InstaWeb without copying its separate login/session or database configuration.

## Non-negotiable boundaries
- Use InstaWeb authentication and ownership checks for every request.
- Scope all data to the authenticated user's portfolio website ID.
- Use new namespaced tables; never import upstream tables over existing InstaWeb tables.
- Reuse the existing database-backed public renderer as the source of truth.
- Validate uploads, use CSRF protection and prepared SQL queries, and escape output.
- Do not copy upstream database credentials or default login credentials.

## Work sequence
1. Add namespaced schema migrations and an owner-scoped persistence service.
2. Add an Advanced Editor route protected by the existing ownership guard.
3. Port dashboard navigation and profile/theme controls.
4. Port content managers and uploads incrementally.
5. Connect fields to the public renderer and test cross-account isolation, CSRF, uploads, preview, and publishing.

The upstream dashboard is a standalone PHP/MySQL application and cannot safely be copied unchanged: its session/authentication model and table names conflict with InstaWeb. Keep the existing Portfolio Studio working throughout the migration.
