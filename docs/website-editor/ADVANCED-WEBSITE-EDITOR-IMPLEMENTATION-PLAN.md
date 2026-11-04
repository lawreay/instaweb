# Advanced Website Editor and Theme Selector
## Implementation Plan

**Project:** InstaWeb  
**Status:** Proposed implementation baseline  
**Document type:** Product scope, technical design, and phased delivery plan  
**Target branch:** `docs/advanced-website-editor-plan`  
**Primary objective:** Establish one reusable editing workflow for all managed website templates, beginning with the existing Business template.

---

## 1. Executive summary

InstaWeb already has working foundations for managed websites: template-based site creation, a Business content editor, private draft preview, and publishing. The current Business editor is intentionally limited to a fixed set of fields. It is not yet a reusable website editing experience.

The next milestone is a shared **Advanced Website Editor**. It will let a website owner manage branding, media, page sections, layout options, and publishing through one consistent interface. Template-specific configuration will define which sections and fields are available.

A **Theme Selector** is part of this milestone. It lets a user choose from compatible visual themes for their selected template. A theme controls presentation (design tokens, typography, spacing, component styles, and section layouts); a template controls the website's purpose and available content model. These concepts must remain separate.

The first delivery target is the Business template. Do not begin School, Church, or Blog editor implementations until the shared editor has been validated with Business.

## 2. Product goals

### Goals

1. Give owners a clear, safe way to customize a website without editing HTML, CSS, PHP, or files.
2. Provide a consistent editor shell across all managed templates.
3. Support branding, logo/favicon, image assets, structured sections, and theme selection.
4. Keep draft preview and published output on the same rendering implementation.
5. Preserve the existing Blank-template raw Code Editor and File Manager workflow.
6. Make it possible to add future templates by registering their content schema, section definitions, and compatible themes rather than building a new editor from scratch.

### Non-goals for the first release

- A free-form drag-and-drop page builder.
- Arbitrary HTML, CSS, or JavaScript editing for managed templates.
- A general-purpose CMS with plugins and third-party extensions.
- E-commerce, shopping carts, stock management, or online payments.
- Per-element pixel positioning or unrestricted layout manipulation.
- AI-generated full websites as a dependency of the editor.
- Building all template categories in the first release.

These are deliberately excluded to keep the first version shippable and maintainable.

## 3. Core product concepts

| Concept | Responsibility | Example |
|---|---|---|
| Template | Defines the website purpose, content schema, available sections, and default structure | Business, Portfolio, School, Church, Blog |
| Theme | Defines the visual presentation and compatible layout variants | Modern Green, Minimal Light, Corporate Blue |
| Website content | Owner's actual text, links, contact details, and section data | Business name, services, about text |
| Brand settings | Reusable identity and visual tokens | Logo, favicon, colors, fonts |
| Media library | Website-owned uploaded assets | Hero image, team photo, service image |
| Editor | Shared interface to edit the above | Branding, Sections, Theme, Preview |
| Renderer | Converts validated content + template + theme into a website | Draft and published HTML/CSS |
| Publisher | Publishes the same rendered output used by preview | Public website directory |

**Important distinction:** Selecting a different theme must not change or delete the user's content. Changing a template is a separate, potentially destructive operation and should not be offered as a casual theme-switch action.

## 4. Proposed editor experience

Use the existing InstaWeb client dashboard shell and design system. Do not create a second dashboard or an unrelated editor application.

### Main navigation

The editor should use a clear left-side navigation or tab structure:

1. **Overview** — website status, last saved time, publish status, and shortcuts.
2. **Branding** — business/site name, logo, favicon, colors, and typography.
3. **Theme** — compatible theme gallery, preview, and apply action.
4. **Sections** — add, edit, reorder, enable/disable, and remove supported sections.
5. **Media** — website-owned image and asset library.
6. **Navigation** — menu labels and links, within template-defined limits.
7. **SEO & Sharing** — page title, meta description, social sharing image, and favicon where supported.
8. **Preview** — desktop, tablet, and mobile preview modes.
9. **Publish** — draft/live status, publish action, and last published timestamp.

The first implementation may combine Overview, Preview, and Publish into the current website-management screens if a separate navigation item adds no value. The information architecture matters more than adding more pages.

### Editor interaction rules

- All editor actions must be owner-authorized on the server, not merely hidden in the UI.
- Saving updates the private draft only.
- Publishing is an explicit action and updates the live website.
- Show an unsaved-changes indicator and last-saved timestamp.
- Warn before leaving with unsaved changes.
- Show clear success and validation messages.
- Do not silently discard section content when changing theme.
- Do not auto-publish on every save.
- The editor must be usable on desktop and mobile; complex section editing can use a mobile-friendly stacked layout.

## 5. Theme Selector

### 5.1 Purpose

The Theme Selector allows a user to change the appearance of their existing website without rebuilding the website or losing content.

### 5.2 Theme gallery

Each theme card should include:

- Theme name and short description.
- Thumbnail or rendered preview image.
- Visual style labels (for example: Minimal, Corporate, Editorial).
- Compatibility with the active template.
- Free or Premium status, if InstaWeb's entitlement model is active.
- Current-theme indicator.
- Preview action.
- Apply theme action.

Theme previews should use representative sample content or the current website's content where practical. A thumbnail alone is not sufficient to judge a theme. Provide a preview route or modal that renders the current site's content with the candidate theme.

### 5.3 Theme behavior

- Only show themes compatible with the active template.
- Applying a theme changes presentation settings, not the content model.
- Keep the currently selected theme until the user explicitly applies another.
- Previewing a theme must not change the saved or live theme.
- Applying a theme updates the draft and marks it as unpublished if the site is already live.
- Publish remains a separate action.
- If a theme is unavailable due to plan/entitlement, explain the restriction and offer the existing upgrade path; do not silently apply a different theme.
- Existing websites must receive a stable default theme during migration or through a safe runtime fallback.

### 5.4 Theme registry

Use a code-defined registry initially. Avoid a database-driven theme marketplace until there is a real need to let administrators upload or manage theme packages.

Each registered theme should define metadata similar to:

```php
[
    'key' => 'business-modern',
    'name' => 'Modern',
    'description' => 'Clean business layout with strong typography.',
    'template_types' => ['business'],
    'preview_image' => '/assets/themes/business-modern/preview.webp',
    'tier' => 'free',
    'renderer' => 'business-modern',
    'tokens' => [
        'color_primary' => '#176B52',
        'color_text' => '#17231F',
        'color_surface' => '#FAF9F6',
        'font_heading' => 'system',
        'font_body' => 'system',
    ],
]
```

This is an illustrative contract, not a requirement to copy the exact array or file structure. Validate registry entries and never accept a renderer path, PHP class name, or arbitrary asset path directly from user input.

### 5.5 Theme data model

Store a stable theme key on the website or in a dedicated settings record, depending on the existing website schema. Do not duplicate the complete theme definition in every website row.

Suggested logical fields:

- `theme_key`: selected registered theme.
- `theme_settings_json`: owner-customizable design-token overrides, if supported.
- `theme_updated_at`: last selection/update time.

For MVP, support a small, curated set of theme tokens. Do not expose every CSS property as a setting.

### 5.6 Theme compatibility and versioning

- A theme declares supported template types.
- A theme has a version or compatibility contract.
- Removing a theme from the registry must not break existing websites; provide a documented fallback and migration path.
- Theme keys are validated against the registry on every save and render.
- Do not allow a theme to introduce arbitrary server-side code.
- Keep content migrations separate from theme migrations.

## 6. Branding and media

### Branding fields

Initial Business scope:

- Business/site display name.
- Logo.
- Favicon.
- Primary, secondary, and accent colors.
- Heading and body font selections from an approved list.
- Contact email, phone, address, and social links.

Logo and favicon behavior:

- Accept a limited set of safe raster formats initially (JPEG, PNG, WebP); SVG support should be disabled until sanitization is implemented and reviewed.
- Enforce configurable file-size and dimension limits.
- Verify actual file content/MIME, not only the extension.
- Generate safe, randomized filenames.
- Store uploads outside executable PHP paths where possible.
- Scope every asset to its owning website and verify ownership on upload, list, replace, and delete.
- Preserve an existing logo if a new upload fails.
- Provide alt text for meaningful images.
- Never overwrite an existing user's asset merely because filenames match.

### Media library

The first media release should support:

- Upload.
- List/grid view.
- Select an image for a supported field.
- Replace an image.
- Delete an unused image.
- Basic file metadata (type, dimensions, size).

Defer image editing, cropping UI, video hosting, and external stock-image integrations.

## 7. Section Manager

### Initial Business sections

| Section | Initial fields / behavior |
|---|---|
| Header | Logo, navigation links, primary CTA |
| Hero | Eyebrow, heading, description, image/visual, primary and secondary CTA |
| Trust/features strip | Up to a small number of short highlights |
| About | Heading, body, image, optional highlights |
| Services | Repeatable service cards with title, description, optional image/icon and link |
| Why choose us | Repeatable benefit items |
| Portfolio/projects | Repeatable project cards; optional for businesses that need it |
| Testimonials | Repeatable quote, person, role/company |
| Team | Repeatable profile cards |
| Process | Ordered steps |
| FAQ | Repeatable question/answer pairs |
| Contact | Email, phone, address, contact CTA/form only if a working form backend exists |
| Footer | Copyright, social links, footer navigation |

Do not force every section into every Business website. Owners can enable only the sections they need.

### Section operations

- Add a section from the template's approved section catalogue.
- Edit section content through typed forms.
- Reorder sections with explicit controls; drag-and-drop can be added later if it materially improves usability.
- Enable/disable a section without deleting its data.
- Delete a section only after confirmation.
- Enforce per-section item limits and required fields.
- Preserve section data when a section is disabled.
- Prevent duplicate singleton sections (for example, one Hero and one About) where the template requires uniqueness.

### Section schema

Each template provides a server-defined section schema. The browser may render forms based on schema metadata, but the server remains authoritative for validation and allowed section types.

Example conceptual section data:

```json
{
  "sections": [
    {
      "id": "hero-main",
      "type": "hero",
      "enabled": true,
      "position": 10,
      "data": {
        "eyebrow": "Welcome",
        "heading": "A better way to work",
        "description": "A short introduction.",
        "primary_cta_label": "Contact us",
        "primary_cta_url": "#contact"
      }
    },
    {
      "id": "services-main",
      "type": "services",
      "enabled": true,
      "position": 20,
      "data": {
        "items": []
      }
    }
  ]
}
```

The example illustrates the shape only. Final fields must be defined by the Business template schema. IDs must be generated and validated by the application, not trusted from arbitrary client payloads.

## 8. Technical architecture

### Recommended flow

```text
Client Dashboard
      |
      v
Shared Website Editor
      |
      +---- Branding settings
      +---- Theme selector
      +---- Section manager
      +---- Media library
      +---- SEO settings
      |
      v
Template Schema + Validation
      |
      v
Owner-scoped Website Content
      |
      v
Shared Website Renderer
      |
      +---- Private draft preview
      |
      +---- Publish service
                 |
                 v
          Public website output
```

### Separation of responsibilities

- **Editor UI:** Forms, navigation, preview controls, and user feedback.
- **Template definition:** Allowed sections, field schemas, validation rules, defaults, and compatible theme types.
- **Theme registry:** Theme metadata, tokens, preview assets, and template compatibility.
- **Content repository/model:** Owner-scoped reads and writes.
- **Media service:** Upload validation, storage, and ownership enforcement.
- **Renderer:** Deterministic output from validated content + template + theme.
- **Publishing service:** Publishes output generated by the same renderer used for preview.

### Rendering rule

The draft preview and public site must use the same renderer and the same normalized input:

`render(template, theme, brand_settings, sections, media, seo)`

The preview and publish paths may differ in access control and output destination, but must not have separate HTML-generation implementations. This prevents the preview/live mismatch already encountered during the Business template work.

### Storage approach

Prefer structured database records for content and settings. Do not make generated HTML the source of truth.

Logical data areas:

- Website-level settings (selected theme and brand settings).
- Template-specific structured content and ordered sections.
- Website media metadata.
- Existing website record and publish metadata.

Reuse the existing Business content table where practical, but avoid turning it into a permanent catch-all JSON dump without validation. Decide whether to evolve it or introduce normalized section/settings tables after inspecting the current schema and migration conventions.

Any schema change must include:
- Forward migration.
- Rollback strategy where practical.
- Existing-site backfill/defaults.
- Backup and deployment notes.
- Tests for old records with missing optional fields.

## 9. Security and reliability requirements

- Every read/write is scoped to the authenticated website owner.
- Enforce authorization in controllers/services, not only navigation visibility.
- CSRF protection on all state-changing requests.
- Validate template, theme, section types, field lengths, URLs, and item counts server-side.
- Escape user content at render time according to output context.
- Validate URLs and prevent unsafe schemes such as `javascript:`.
- Validate uploads by MIME/content, size, dimensions, and allowed extension.
- Randomize upload filenames and prevent path traversal.
- Do not execute uploaded content.
- Do not allow a user to change a website's template key through editor payloads.
- Save content atomically where multiple records must remain consistent.
- Use transactions for section reorder or multi-record updates where supported.
- Keep generated output deterministic and avoid unsafe string replacement/regex HTML mutation.
- Log publishing failures without exposing filesystem paths or secrets to users.
- Do not change `.env`, production configuration, dependency manifests, or vendor files as part of this feature unless a separately reviewed requirement demands it.

## 10. Phased implementation plan

### Phase 0 — Audit and design lock

**Purpose:** Understand current Business editor, portfolio editor, website storage, migrations, preview, and publish flow before changing architecture.

Tasks:
- Audit current routes, controllers, models, services, views, and tests.
- Map Business content fields to proposed section schema.
- Identify reusable portfolio editor components and existing dashboard UI patterns.
- Confirm where theme selection belongs in website persistence.
- Document migration/backfill behavior for existing Business sites.
- Record current preview and publish behavior as regression tests.

**Exit criteria:**
- Approved data model and route map.
- No duplicate editor shell planned.
- Existing Business site and publish behavior covered by tests.

### Phase 1 — Shared editor foundation

**Purpose:** Build the reusable shell before expanding features.

Tasks:
- Shared editor layout within the existing client dashboard.
- Template-aware editor navigation.
- Website context and owner authorization.
- Consistent form components, validation display, save status, and unsaved-change handling.
- Shared preview entry point.
- Template capability/section registry contract.
- Keep current Business editor available until the new shell reaches parity.

**Exit criteria:**
- Business can open the new editor shell.
- Unsupported tools are not shown.
- Server rejects unauthorized website access.
- Existing Blank raw tools remain unaffected.

### Phase 2 — Theme registry and Theme Selector

**Purpose:** Let Business owners change visual presentation without losing content.

Tasks:
- Implement code-defined theme registry.
- Register at least two distinct Business themes for meaningful comparison.
- Theme gallery with compatibility and current-theme state.
- Preview candidate theme using current saved content.
- Apply/cancel behavior.
- Persist theme key and safe token overrides.
- Add default-theme migration/backfill.
- Ensure draft preview and publish use selected theme.

**Exit criteria:**
- Switching themes preserves all section content and media references.
- Previewing a theme does not persist it.
- Applying a theme marks a live site as having unpublished changes.
- Published output reflects the selected theme after explicit publish.
- Invalid/incompatible theme keys are rejected.

### Phase 3 — Branding and media library

**Purpose:** Allow users to make the site recognizably theirs.

Tasks:
- Logo and favicon fields.
- Secure website-scoped media upload and library.
- Replace/remove asset actions.
- Brand colors and approved font choices.
- Renderer consumes branding values consistently in header, footer, and supported sections.
- Fallback logo/name behavior.

**Exit criteria:**
- Owner can upload, select, replace, and remove a logo.
- Another account cannot access or replace the asset.
- Invalid/oversized uploads are rejected safely.
- Existing sites render correctly when no logo is set.

### Phase 4 — Section Manager

**Purpose:** Move beyond fixed fields to configurable page composition.

Tasks:
- Business section catalogue and schema.
- Add/edit/enable/disable/delete operations.
- Reordering with stable positions.
- Repeatable item forms for services, testimonials, FAQ, team, process, and project cards.
- Section-level validation and item limits.
- Accessible forms and confirmation for destructive actions.
- Renderer handles enabled sections in saved order.

**Exit criteria:**
- Owner can add and reorder supported sections.
- Disabled sections are omitted from output but retain content.
- Deleting one section does not corrupt other sections.
- Preview and published output match.

### Phase 5 — SEO, navigation, and publishing polish

Tasks:
- SEO title and description.
- Social sharing image and favicon integration.
- Menu link management with URL validation.
- Clear draft/live/unpublished status.
- Publish confirmation and error feedback.
- Last-published timestamp and recovery guidance.
- Responsive preview modes.

**Exit criteria:**
- SEO metadata is reflected in rendered output.
- Navigation links validate and render safely.
- Save, preview, and publish states are unambiguous.

### Phase 6 — Business migration and release hardening

Tasks:
- Migrate existing fixed Business fields into the new section model.
- Preserve current content, logo/media references where present, and theme defaults.
- Test upgrade path on a copy of an existing database.
- Run authorization, validation, rendering, and publishing tests.
- Manual browser tests at desktop and mobile sizes.
- Document deployment, backup, rollback, and support procedures.
- Remove old Business editor only after parity and migration verification.

**Exit criteria:**
- Existing Business websites retain their content.
- New and existing Business websites use the shared editor.
- No regression in public URLs or publishing.
- All release checklist items pass.

### Phase 7 — Reuse for other templates

Only after Business passes release criteria:

1. Portfolio: map existing advanced portfolio editor into shared shell where it provides a clear benefit; do not rewrite its working functionality just for uniformity.
2. School: define school-specific sections and compatible themes.
3. Church: define ministry, service times, events, sermons, and contact sections as needed.
4. Blog: define post listing, categories, author, article, and archive behavior.

Each template should reuse the shell, theme registry, media service, renderer contract, and publishing lifecycle. Template-specific business logic remains in its own definition/module.

## 11. Testing strategy

### Automated tests

- Theme registry returns only valid theme definitions.
- Incompatible theme cannot be applied.
- Theme change leaves content unchanged.
- Theme preview does not persist.
- Section create/update/delete/reorder is owner-scoped.
- Disabled sections are omitted from rendering.
- Section order is stable.
- Invalid fields, URLs, and oversized item arrays are rejected.
- HTML-special characters are escaped.
- Unsafe URL schemes are rejected.
- Upload MIME/size/dimension checks work.
- Cross-owner media access is denied.
- Draft preview and published output use the same renderer and normalized data.
- Existing Business content migration preserves values.
- Blank template raw editor/file manager policy remains unchanged.

### Manual acceptance checklist

- [ ] Open editor as website owner.
- [ ] Confirm another account cannot open the editor.
- [ ] Select another compatible theme and preview it.
- [ ] Cancel theme preview and confirm current theme remains selected.
- [ ] Apply theme and verify content remains unchanged.
- [ ] Upload a logo and favicon.
- [ ] Replace and remove a logo.
- [ ] Add, edit, disable, enable, reorder, and delete a section.
- [ ] Save and reload; confirm changes persist.
- [ ] Preview desktop, tablet, and mobile layouts.
- [ ] Publish and compare public website with preview.
- [ ] Save another change and confirm it is marked unpublished.
- [ ] Confirm live website does not change until explicit publish.
- [ ] Confirm existing websites without optional assets still render.
- [ ] Confirm Blank sites retain raw Code Editor and File Manager access.

## 12. Delivery strategy and scope control

Recommended delivery order:

1. Shared editor shell.
2. Theme Selector.
3. Branding/logo/media.
4. Section Manager.
5. SEO and navigation.
6. Migration and release hardening.
7. Additional template integrations.

Do not implement all phases in one large pull request. Deliver small, reviewable vertical slices with tests and a usable result after each phase.

Do not begin drag-and-drop until users demonstrate that explicit reorder controls are insufficient. Do not build a theme marketplace or arbitrary theme upload system in the first release. Do not add a feature merely because a visual page builder has it.

## 13. Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Rewriting the portfolio editor unnecessarily | High effort and regression risk | Reuse shell selectively; preserve working portfolio behavior |
| Preview and live renderer diverge | Users see different content after publishing | One renderer for preview and publish |
| Theme change alters content | Data loss and support burden | Separate theme configuration from content schema |
| Unsafe media uploads | Code execution or stored malicious content | Strict MIME checks, randomized names, non-executable storage |
| Generic JSON becomes unmaintainable | Weak validation and difficult migrations | Server-defined schemas and versioned content contracts |
| Too many themes too early | Design and QA overhead | Start with two Business themes |
| Scope grows into a full page builder | Delayed release | Fixed section catalogue and controlled layout options |
| Existing websites break during migration | Production incidents | Backfill, backup, staged rollout, regression tests |

## 14. Definition of done

The Advanced Website Editor milestone is complete when:

- Business sites use the shared editor shell.
- Theme Selector offers compatible themes and preserves content.
- Owners can manage logo, favicon, brand tokens, and website media securely.
- Owners can add, edit, reorder, enable/disable, and remove supported sections.
- Save, preview, and publish behavior is clear and reliable.
- Draft preview and live output are generated through the same renderer.
- Existing Business sites migrate without losing content.
- Automated tests and manual acceptance checks pass.
- Blank website editing policy remains intact.
- Deployment and rollback instructions are documented.

## 15. Recommended first implementation slice

Start with **Phase 0 + Phase 1**, then deliver **Phase 2 Theme Selector** as the first user-visible feature after the shared shell is in place.

Do not start logo uploads and section management in parallel with the editor foundation. Establish the data and rendering contracts first, then build on them.

**Technical lead recommendation:** Keep the editor schema-driven, the theme registry code-defined, content structured and owner-scoped, and rendering shared between preview and publishing. This is the smallest architecture that supports more template categories without turning InstaWeb into a fragile page-builder project.
