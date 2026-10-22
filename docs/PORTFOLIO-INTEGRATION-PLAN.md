# Portfolio Integration Plan: Custom Portfolios into InstaWeb

**Status:** Planning  
**Target repository:** `lawreay/instaweb`  
**Reference project:** `lawreay/Custom-portfolios`  
**Proposed stack:** React UI, existing InstaWeb PHP backend, MySQL  
**Scope:** Portfolio website type, template gallery, editor, preview, and publishing integration

## 1. Decision: Build the InstaWeb feature cleanly, using Custom-portfolios as the reference

**Recommendation: do not copy the entire Custom-portfolios application into InstaWeb, and do not throw away its working functionality and start blindly from zero.**

Use a selective-rebuild approach:

1. Inspect and document the existing Custom-portfolios editor, portfolio fields, content flows, and current template.
2. Reuse its proven requirements, content model, validation rules, useful assets, and design ideas where they fit.
3. Build the portfolio feature inside InstaWeb's existing website lifecycle and account system.
4. Build the new template gallery, editor interface, and template components with React.
5. Keep InstaWeb's existing PHP backend and MySQL as the source of truth. Add or adapt PHP endpoints and schema through explicit, reviewable migrations.
6. Port content only through a deliberate migration or import process if existing portfolio data needs to be retained. Do not copy the old app's authentication, routing, publishing system, or configuration wholesale.

This avoids inheriting unrelated legacy code while preserving useful work. It also avoids a risky, unnecessary rewrite of InstaWeb itself.

## 2. Product experience

The intended flow is:

1. A signed-in user opens **Create Website** in InstaWeb.
2. They choose **Portfolio** as the website type.
3. They browse a gallery of templates with real, working sample portfolios using fictional sample content such as “John Doe”.
4. They preview a template before selecting it.
5. InstaWeb creates a portfolio associated with that user's website record.
6. The user edits their own profile, projects, skills, experience, education, contact details, images, and other supported content.
7. The user previews the result, selects a unique public slug, and publishes.
8. The published portfolio is served through InstaWeb's existing hosting and publishing lifecycle.

A user should be able to switch templates without re-entering portfolio content. Template selection and appearance settings must remain separate from the portfolio's actual content.

## 3. Planned template system

The current Custom-portfolios design is the proposed **Classic** default, subject to a review to confirm it is suitable for the first integrated release.

Initial template candidates:

- **Classic:** Existing portfolio design, adapted into a reusable template.
- **Modern Developer:** A contemporary developer-focused layout.
- **Minimal:** A restrained, content-first layout.
- **Creative:** A more visual layout for designers and creative professionals.

Build and verify one end-to-end template first. Add further templates after the shared data model, editor, preview, and publishing path work reliably.

Each template should be a React component that receives normalized portfolio data and supported appearance settings. Templates must not contain their own copies of the user's content or implement separate save/publish logic.

The gallery should show working previews with sample data, not screenshots pretending to be interactive pages. Preview mode must not accidentally modify or publish a user's real portfolio.

## 4. What to bring forward from Custom-portfolios

Treat these as candidates to inspect and reuse, not as permission to copy files without review.

- **Portfolio fields and content structure:** profile, biography, skills, projects, work experience, education, contact information, CV/resume, and supported social links.
- **Existing editor workflows:** useful field groups, validation rules, image/CV handling, and established content-management behavior.
- **Existing Classic design:** preserve its visual identity where useful, then extract it into a reusable React template.
- **Existing assets:** logos, icons, fonts, and other assets only where ownership/licensing and quality are clear.
- **Theme ideas:** useful colors, typography, section visibility, and layout options, normalized into a documented settings format.
- **Existing data:** preserve or import real content if needed. Inspect the schema and data first; do not assume the old schema is the final InstaWeb schema.

Before porting a behavior, confirm that it works, is secure, and fits InstaWeb's account and website model.

## 5. What must be replaced or excluded from the old project

The following old-project responsibilities should not be carried over as independent systems:

- **Separate login and authentication:** use InstaWeb's authenticated session and authorization rules.
- **Hard-coded single portfolio or administrator ownership:** support portfolio records associated with the correct InstaWeb website and owner.
- **Standalone publishing and URL routing:** integrate with InstaWeb's website lifecycle and routing after verifying how its hosting works.
- **Direct assumptions about a single public user:** resolve public content by the published website/portfolio slug and its associated record.
- **Database initialization that changes schema during normal requests:** use explicit, versioned migrations and deployment steps.
- **Development/debug utilities in production:** remove or disable password-debug pages and similar diagnostics before deployment.
- **Destructive seeding or reset scripts in production:** isolate development fixtures and ensure they cannot erase real portfolio content.
- **Credentials committed in source code:** move secrets to environment/configuration outside version control, rotate any exposed database credentials, and review Git history where necessary.
- **Duplicated application shell and unrelated dashboard code:** use InstaWeb's existing dashboard, navigation, website management, and access controls.
- **Duplicated portfolio content per template:** store content once and render it through the selected template.

Do not delete the reference project or its data as part of this work. “Remove” means exclude or replace these responsibilities in the integrated InstaWeb implementation. Keep the reference repository intact until the new feature is verified and any required data has been migrated safely.

## 6. Target architecture

```text
InstaWeb
├── Existing account and authorization system
├── Existing website lifecycle
│   └── Website type: Portfolio
├── React portfolio experience
│   ├── Template gallery and live sample previews
│   ├── Shared portfolio editor
│   ├── Portfolio preview
│   └── Template components
│       ├── Classic
│       ├── Modern Developer
│       ├── Minimal
│       └── Creative
├── PHP backend/API
│   ├── Authorization and ownership checks
│   ├── Portfolio content operations
│   ├── Template/settings operations
│   └── Preview and publishing integration
└── MySQL
    ├── Existing InstaWeb users and websites
    └── Portfolio-specific content/settings linked to a website
```

This is a logical architecture, not a claim that the exact folders, API routes, or database tables already exist. Inspect the repositories before choosing implementation paths.

### Ownership and data rules

- Every portfolio must be linked to an existing InstaWeb website record and its authorized owner.
- Enforce ownership and permissions on the server for every read/write operation; hiding UI controls is not security.
- Use a unique, validated public slug and reserve system routes.
- Keep draft/preview content private unless the owner deliberately publishes it.
- Keep portfolio content independent of the selected template.
- Define supported template settings explicitly and validate them on the server.
- Use parameterized database queries, CSRF protection where applicable, output escaping, upload validation, and sensible upload limits.
- Do not expose database credentials or private draft data in public responses.

## 7. What to add to InstaWeb

1. **Portfolio** as a first-class website type in the existing website-creation flow.
2. A template gallery with sample data and working preview pages.
3. React template components and a shared rendering contract.
4. A React editing experience backed by authorized PHP endpoints.
5. Portfolio-specific persistence linked to the InstaWeb website record.
6. Template selection and validated appearance settings.
7. A preview/draft workflow integrated with InstaWeb.
8. Slug selection/uniqueness validation and public rendering through the existing hosting/routing model.
9. Tests for ownership, saving, template switching, preview privacy, slug collisions, publishing, and regression behavior.
10. Documentation for setup, API contracts, migrations, template development, and deployment.

Avoid adding billing, subscription limits, a separate authentication service, or a second hosting system as part of the initial slice unless an existing InstaWeb requirement makes one essential.

## 8. What not to rebuild

- Do not rewrite InstaWeb's account system.
- Do not create a separate portfolio application for every user.
- Do not duplicate the portfolio content for every template.
- Do not copy the old dashboard wholesale.
- Do not migrate the entire InstaWeb backend to another framework for this feature.
- Do not add all templates before proving that one can be created, edited, previewed, and published end to end.
- Do not merge this work into unrelated dashboard/sidebar work.

## 9. Implementation phases

### Phase 0: Repository audit

- Inspect InstaWeb's website types, account ownership, route handling, hosting/publishing lifecycle, upload handling, and database conventions.
- Inspect Custom-portfolios' editor, schema, current template, assets, authentication, public renderer, and debug/seed scripts.
- Identify reusable behavior and document incompatibilities.
- Check whether any real portfolio data must be migrated.
- Record the current tests and establish a baseline.

**Exit condition:** an agreed data mapping and integration design, based on inspected code rather than assumptions.

### Phase 1: One vertical slice

- Add Portfolio to the existing website creation flow.
- Create/link a portfolio record to the new website and its owner.
- Build the Classic React template using sample and real saved data.
- Connect the editor to PHP endpoints and MySQL.
- Support save, preview, slug validation, and publish through InstaWeb.
- Test access control and the existing website lifecycle.

**Exit condition:** a user can create, edit, preview, and publish one portfolio without separate authentication or a separate publishing system.

### Phase 2: Template gallery and switching

- Add working sample previews.
- Add template selection and persistence.
- Verify switching templates does not change or delete content.
- Add Modern Developer after the shared contract is stable.

### Phase 3: More templates and polish

- Add Minimal and Creative templates.
- Add only well-defined customization options.
- Verify responsive behavior, accessibility, performance, and public-page metadata/SEO.
- Test image/CV uploads, empty states, malformed input, and failure recovery.

### Phase 4: Security, migration, and release

- Remove or isolate unsafe legacy utilities from the integrated application.
- Rotate exposed credentials and keep secrets outside source control.
- Add/verify explicit migrations and rollback/recovery instructions.
- Test draft privacy, ownership, slug collisions, published routes, and backups.
- Update documentation and release notes.

## 10. Acceptance checklist

- [ ] Portfolio appears in InstaWeb's existing Create Website flow.
- [ ] A portfolio is linked to the correct website and owner.
- [ ] The gallery presents working sample previews.
- [ ] The Classic template renders the shared portfolio data.
- [ ] Editing and saving persist data through PHP and MySQL.
- [ ] Unauthorized users cannot read or modify another user's private portfolio.
- [ ] Template switching preserves portfolio content.
- [ ] Preview does not publish a draft accidentally.
- [ ] Slugs are validated, unique, and do not shadow reserved routes.
- [ ] Publishing uses InstaWeb's existing lifecycle and hosting model.
- [ ] No second authentication or publishing system is introduced.
- [ ] Legacy debug, destructive seed, and embedded-secret risks are addressed.
- [ ] Relevant automated tests pass and existing website types still work.

## 11. Working rules

- Keep this feature on a dedicated branch, separate from unrelated dashboard work.
- Make small, reviewable commits.
- Read the repository's `AGENTS.md` and current phase/project documentation before changing code.
- Inspect existing conventions before adding new folders, routes, tables, or dependencies.
- Never claim tests, migrations, deployments, or data migrations succeeded until verified.
- Do not delete or modify the Custom-portfolios reference repository as a shortcut.
- Document decisions that change the data model, API contract, or public URL behavior.

## Final recommendation

**Build the portfolio feature inside InstaWeb with React, PHP, and MySQL. Use Custom-portfolios as the functional and visual reference, selectively reuse proven work, and replace its standalone application boundaries with InstaWeb integration.**

The first milestone is one complete Classic portfolio flow. Once that works safely, add the gallery and further React templates without duplicating the backend or portfolio data.
