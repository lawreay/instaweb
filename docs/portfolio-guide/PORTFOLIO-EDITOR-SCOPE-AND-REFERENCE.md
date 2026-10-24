# InstaWeb Portfolio Editor: Scope and Reference Guide

**Document type:** Product scope, implementation guide, and acceptance reference  
**Project:** InstaWeb  
**Status:** Active implementation specification  
**Applies to:** Portfolio creation, editing, preview, and published rendering

## 1. Purpose

This document is the shared reference for implementing and reviewing the InstaWeb portfolio editor. The objective is to let a website owner customize portfolio content and appearance from the dashboard without editing source code.

This document defines the target scope. A feature is not complete merely because a control appears in the UI. It must save correctly, survive reload, appear in preview, and render correctly on the published portfolio.

## 2. Product principles

1. **InstaWeb remains the host platform.** Reuse InstaWeb authentication, website ownership, website lifecycle, routing, preview, and publishing.
2. **One integrated application.** Do not add a second login system, duplicate account model, or separate database copied from the standalone Custom-portfolios project.
3. **Website-scoped data.** Every portfolio setting and content record must be associated with the relevant InstaWeb website and authenticated owner.
4. **Persist, then render.** Preview and published rendering must use saved values, with safe defaults for older records.
5. **Secure by default.** Validate inputs and URLs, use prepared SQL, enforce CSRF and ownership checks, escape output, and validate uploads.
6. **Accessible and responsive.** Controls need labels, validation feedback, keyboard usability, and layouts that work on desktop and mobile.
7. **No false completion.** Mark a feature complete only after its applicable CRUD, persistence, rendering, and security checks pass.

## 3. Owner workflow

1. Sign in to InstaWeb and open a website owned by the account.
2. Open the Portfolio editor and select a section.
3. Edit content, appearance, media, or engagement settings.
4. Save and receive clear success or validation feedback.
5. Reload the editor to confirm persistence.
6. Preview the draft and confirm the saved state.
7. Publish or update through InstaWeb's existing publishing workflow.
8. Open the public portfolio and confirm the published state matches the preview.

## 4. Functional scope and acceptance checklist

Check an item only after the feature has been implemented and tested.

### 4.1 Hero

- [ ] Hero title.
- [ ] Subtitle or tagline.
- [ ] CTA button text.
- [ ] CTA destination/behaviour, if configurable, is validated.
- [ ] Empty optional values use a safe fallback or hide the relevant element.
- [ ] Saved values appear in preview and published output.

### 4.2 About and personal details

- [ ] About section title and body text.
- [ ] Full name.
- [ ] Home origin.
- [ ] Nationality.
- [ ] Religion.
- [ ] Status.
- [ ] Location.
- [ ] Vision or intended impact.
- [ ] Bio maps consistently to the relevant public content.
- [ ] Optional details can be blank without showing empty labels.
- [ ] Personal details are shown publicly only when the owner chooses to publish them.

### 4.3 Goals

- [ ] Add, edit, and delete goals.
- [ ] Goal title and description.
- [ ] Optional icon or image.
- [ ] Stable ordering and public display.
- [ ] Every operation is scoped to the authenticated owner and selected website.

### 4.4 Interests and passions

- [ ] Add, edit, and delete interests.
- [ ] Interest title, description, and optional icon.
- [ ] Stable ordering and public display.
- [ ] Every operation is owner- and website-scoped.

### 4.5 Experience

- [ ] Add, edit, and delete work/role entries.
- [ ] Role/title, company, start date, end date, and description.
- [ ] Mark an entry as current; current entries need no end date.
- [ ] Configurable timeline dot colour.
- [ ] Validate dates and use a defined display order.
- [ ] Preview and published output use the saved data.

### 4.6 Education

- [ ] Add, edit, and delete qualifications.
- [ ] Qualification, institution, dates, and description.
- [ ] Configurable colour styling.
- [ ] Validate dates and use a defined display order.
- [ ] Preview and published output use the saved data.

### 4.7 Skills

- [ ] Add, edit, and delete skill categories.
- [ ] Add, edit, and delete skills within categories.
- [ ] Skill names and proficiency level.
- [ ] Configurable skill colour.
- [ ] Proficiency uses a defined scale.
- [ ] Category and skill ordering persists.

### 4.8 Projects

- [ ] Add, edit, and delete projects.
- [ ] Title and category.
- [ ] Short summary and full description.
- [ ] Technology stack.
- [ ] Demo URL and GitHub/repository URL.
- [ ] Project image.
- [ ] Optional icon and display colour.
- [ ] Featured flag and featured ordering.
- [ ] Validate URLs and images.
- [ ] Ordering and visibility match in preview and public output.

### 4.9 News

- [ ] Enable or disable the News section.
- [ ] Add, edit, and delete news items.
- [ ] Title, body text, and optional validated link.
- [ ] Published/unpublished state.
- [ ] Unpublished items never appear publicly.
- [ ] A disabled section stays hidden even if items exist.

### 4.10 Media and branding

- [ ] Upload and replace profile image.
- [ ] Upload and replace favicon.
- [ ] Configure background images for supported homepage sections.
- [ ] Configure overlay opacity per section.
- [ ] Validate MIME type, extension, size, and image decoding where applicable.
- [ ] Store files safely and scope upload metadata to the correct website and owner.
- [ ] Handle missing assets gracefully.
- [ ] Preview and public output use saved assets.

### 4.11 CV management

- [ ] Upload a CV.
- [ ] Add or update an external CV URL.
- [ ] Enable or disable public download.
- [ ] Preview/open the current CV where supported.
- [ ] Remove or replace the CV.
- [ ] Validate file types and URLs.
- [ ] Do not expose the CV publicly when downloads are disabled.
- [ ] Do not expose local filesystem paths or private upload metadata.

### 4.12 Engagement and visitor content

- [ ] View bookings for the selected portfolio website.
- [ ] Review booking details/status where supported.
- [ ] Delete bookings with confirmation and authorization.
- [ ] View and review visitor requests.
- [ ] Delete requests with confirmation and authorization.
- [ ] View and review comments.
- [ ] Delete comments with confirmation and authorization.
- [ ] View recent site visits with useful timestamps and available summary details.
- [ ] Scope every query and action to the selected website and owner.
- [ ] Avoid exposing unnecessary visitor personal data.

### 4.13 Popups

- [ ] Add, edit, activate/deactivate, and delete popup items.
- [ ] Title, message, button text, destination link, and optional image.
- [ ] Timer trigger.
- [ ] Scroll trigger.
- [ ] ESC-key close behaviour.
- [ ] Documented back-button behaviour that does not trap visitors or break navigation.
- [ ] Configurable timer delay and scroll percentage.
- [ ] Validate trigger settings, delay, percentage, URL, and uploads.
- [ ] Inactive popups never display.
- [ ] Respect accessibility, mobile usability, and reduced-motion preferences.

### 4.14 Theme

- [ ] Background colour.
- [ ] Surface/card colour.
- [ ] Text colour.
- [ ] Primary action colour.
- [ ] Secondary action colour.
- [ ] Accent colour.
- [ ] Validate colour values.
- [ ] Maintain readable contrast and visible focus states.
- [ ] Saved colours apply in preview and published output.

### 4.15 Typography

- [ ] Font family selection.
- [ ] Font size control with validated supported values.
- [ ] Consistent application without breaking responsive layouts.
- [ ] Persistence and public rendering.

### 4.16 Animations and cursor

- [ ] Enable/disable animations.
- [ ] Cursor style: default.
- [ ] Cursor style: glow.
- [ ] Cursor style: trail.
- [ ] Cursor style: bounce.
- [ ] Respect reduced-motion preferences.
- [ ] Custom cursor effects must not interfere with ordinary pointer or touch interaction.
- [ ] Provide safe fallbacks where effects are unsupported.

### 4.17 Profile contact information

- [ ] Bio.
- [ ] Location.
- [ ] Public contact email.
- [ ] WhatsApp contact.
- [ ] Validate email and normalize WhatsApp destinations safely.
- [ ] Let the owner choose which details are publicly displayed.

### 4.18 Account settings

- [ ] Define which settings belong to the InstaWeb account and which belong only to a portfolio website.
- [ ] Reuse existing InstaWeb account/profile settings where appropriate.
- [ ] Do not duplicate passwords, authentication, or account security inside the portfolio editor.
- [ ] Protect account-level changes with existing authentication and authorization.
- [ ] Clearly state whether each setting affects all websites or only the selected portfolio.

## 5. Data and architecture guidance

- Use the authenticated user ID and ownership-checked website ID for every read and write.
- Store settings with stable, documented keys and validate values against allowlists.
- Store repeatable section records in a structured representation with documented fields, ordering, and enabled/published state where needed.
- Use explicit fields or documented JSON payload schemas for complex items. Avoid undocumented arbitrary payloads.
- Add schema changes through the project's migration process. Do not destroy existing data.
- Use prepared SQL statements and transactions for multi-record operations that must succeed or fail together.
- Keep public rendering separate from owner-only management pages.
- Public queries must return only enabled/published content intended for public display.
- Reuse InstaWeb's public renderer and template selection. Do not create a separate publishing pipeline.
- Keep Classic, Editorial, and 3D Motion templates consistent. If a template does not support a setting, define a graceful fallback rather than silently losing saved data.
- Do not copy the standalone Custom-portfolios authentication/session system or import its database schema unchanged.

## 6. Security and privacy requirements

- Validate CSRF tokens on state-changing requests.
- Verify ownership server-side on every read/write operation, including edits, deletes, uploads, previews, and engagement management.
- Never trust a submitted user ID, website ID, item ID, or upload key without checking its relationship to the authenticated owner.
- Validate and normalize URLs; reject dangerous schemes such as javascript.
- Escape untrusted content at output time. Do not treat arbitrary user HTML as trusted markup.
- Validate uploads by allowed type, extension, size, storage path, and safe file name. Prevent executable uploads where files are publicly served.
- Prevent path traversal and unauthorized file access.
- Keep CV files private when public download is disabled.
- Avoid logging secrets or unnecessary visitor data.
- Confirm destructive actions and provide useful errors without exposing sensitive internals.

## 7. Definition of done

A checklist item is complete only when all applicable criteria pass:

1. UI exposes the required fields/actions with labels and validation.
2. Server validates input and checks ownership/authorization.
3. Data persists and survives reload.
4. Add/edit/delete works where applicable.
5. Preview displays the saved state.
6. Published output displays the intended state.
7. Disabled or unpublished content remains hidden publicly.
8. Missing, invalid, or legacy values fail safely with sensible defaults.
9. Responsive behaviour and success/error feedback are usable.
10. Relevant automated tests pass; manual checks are recorded for behaviour not covered by automation.

## 8. Recommended implementation sequence

1. Foundation audit: map each field to its form, route/controller, persistence field, preview renderer, public renderer, and tests.
2. Hero and About: implement editable content and personal details, persistence, and public rendering.
3. Repeatable content: complete Goals, Interests, Experience, Education, Skills, and Projects CRUD and ordering.
4. News and visibility: implement section toggles and published/unpublished handling.
5. Profile media and CV: secure uploads, asset lifecycle, and visibility controls.
6. Theme, typography, and animations: complete all appearance settings and template fallbacks.
7. Engagement: implement owner-scoped booking/request/comment review, deletion, and recent visits.
8. Popups: implement management and test public trigger behaviour.
9. Account settings: define boundaries with existing InstaWeb account settings.
10. Integration review: test all three templates, security boundaries, regressions, and preview/publish parity.
11. Merge review: compare the feature branch with current main, inspect the full diff, run tests, and merge only after scope and regression review.

## 9. Test record template

Record evidence for each feature.

| Field | Record |
|---|---|
| Feature/checklist item | |
| Branch and commit SHA | |
| UI implemented | Pass / Fail / Not tested |
| Server validation and ownership | Pass / Fail / Not tested |
| Persistence after reload | Pass / Fail / Not tested |
| Preview result | Pass / Fail / Not tested |
| Published result | Pass / Fail / Not tested |
| Security checks | Pass / Fail / Not applicable / Not tested |
| Automated tests and result | |
| Manual test notes | |
| Known gaps | |

## 10. Related project references

- docs/portfolio-advanced-editor-integration.md
- docs/portfolio-database-integration-map.md
- docs/portfolio-integration-audit-and-plan.md
- docs/PORTFOLIO-INTEGRATION-PLAN.md
- docs/PORTFOLIO-REPOSITORY-AUDIT.md

Review these documents alongside the implementation. If implementation conflicts with this scope, document the discrepancy and deliberately update the reference rather than silently reducing acceptance criteria.

---

**Maintenance rule:** update this guide when scope changes and keep its checklist aligned with verified code and tests. This document defines the target; it does not claim every listed feature has already been implemented.
