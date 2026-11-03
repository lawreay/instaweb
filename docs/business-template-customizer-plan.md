# Business Template Customizer

## Status
Planning and implementation branch created from `main` on 2026-11-03:
`feature/business-template-customizer`

## Current implementation
- Business is currently a static starter generated as `index.html` by `ProjectStorageService::createStarterIndex()`.
- The page can be edited through the generic code editor and file manager.
- There is no structured Business content model or Business-specific editor.
- Portfolio editing is a separate database-backed workflow with owner-scoped controllers, structured fields, autosave, template selection, and preview.

## Product decision
Build a focused Business editor that reuses InstaWeb's existing dashboard layout, authentication, website ownership checks, CSRF protection, preview, and publishing lifecycle. Do not copy the Portfolio data model or add a second site-management system.

## Proposed MVP editor
1. Business identity: business name, tagline, logo, contact email, phone/WhatsApp, location.
2. Hero: eyebrow, heading, description, primary and secondary CTA labels/links.
3. Services: up to six editable service cards (title, description, optional icon label).
4. About: heading, description, three highlights.
5. Process: up to three steps.
6. Contact: heading, short message, email, phone/WhatsApp, location.
7. Appearance: one curated palette selector and typography preset.
8. Footer: tagline and copyright text.

## Persistence and rendering
- Store structured content in a website-scoped database record keyed by `website_id` and `user_id`.
- Generate the static `index.html` from validated content so existing static preview, publishing, direct visits, and hosting behavior remain unchanged.
- Keep the content record as the source of truth; do not make users edit HTML to customize the Business starter.
- Preserve unknown/legacy static files and do not rewrite unrelated website files on save.

## Security and validation
- Every read/write must verify the authenticated owner and `template_key = business`.
- Validate CSRF on writes; enforce field length limits, service/step count limits, and HTTP(S)-only external links.
- Escape all user-provided text for HTML output. Validate palette keys against a server-side allowlist.
- No arbitrary HTML, JavaScript, CSS, or raw SVG fields in the MVP.
- Save atomically where possible and report generation failures without corrupting the last working `index.html`.

## Acceptance criteria
- Existing Business sites can initialize editor data from safe defaults without losing their current page.
- Owner can edit content, save, preview, publish, unpublish, and revisit the editor with persisted values.
- Changes to a Business site do not affect Portfolio, Blank, Church, Blog, School, or WordPress sites.
- Free/premium template entitlements are not introduced in this milestone; one polished Business layout is enough.
- Desktop and mobile form usability, generated HTML, preview, published output, and direct URL are verified.

## Explicitly deferred
- E-commerce, product inventory, checkout, bookings, blog/CMS, multiple Business layouts, AI copy generation, arbitrary section builder, and custom CSS/JS.
