# Complete guide: creating portfolio templates

This guide explains how to plan, build, test, publish, maintain, and retire portfolio templates in InstaWeb.

Templates control presentation only. The portfolio owner still enters their profile, photo, projects, experience, education, skills, and contact details in Portfolio Studio. A well-made template uses that content clearly and remains useful when a portfolio has very little content, very long content, or many entries.

## 1. Before you start: decide what the template is for

Do not start with code. Start with the job the template must do.

| Template direction | Best for | Visual characteristics |
| --- | --- | --- |
| Professional / finance | Graduates, accountants, consultants, administrators | Calm colours, clear facts, ordered experience, restrained typography |
| Creative / editorial | Designers, photographers, writers, agencies | Larger type, visual rhythm, project-led layouts, stronger colour |
| Technical / product | Developers, analysts, engineers | Clear systems, project cards, skills, outcome-focused case studies |
| Personal / graduate | Job seekers and students | Friendly introduction, education first, concise experience, easy contact |
| Minimal | Any portfolio with limited content | Generous space, few visual effects, hierarchy through type and spacing |

Write down the following before opening the editor:

1. The primary audience: recruiter, potential client, employer, collaborator, or school.
2. The primary action: email the owner, view projects, download a CV, or learn about experience.
3. The three content areas that must stand out first.
4. The template mood in three words, such as `reliable, precise, calm`.
5. The smallest screen to support: always plan for a phone first, then expand to desktop.

For example, an accounting graduate template might lead with the name, degree, location, a short professional summary, education, practical experience, skills, projects, then contact. A designer template may lead with work samples and use the biography later.

## 2. How the template system works

Go to **Admin → Portfolio Templates**.

The catalog has three types of template:

- **Built-in templates** are shipped with the application. You can activate or suspend them, but cannot delete or edit their renderer code in the catalog.
- **Custom HTML/CSS templates** use safe markup with portfolio placeholders. They are best for mostly static designs.
- **Custom React/JSX templates** use an interactive React component. They are best when the layout needs state, filters, expandable content, tabs, or other controlled interactions.

Each custom template has:

| Field | Purpose |
| --- | --- |
| Name | The label seen in Portfolio Studio |
| Description | A concise explanation of the template’s intended use |
| Renderer | `HTML + CSS` or `React + JSX` |
| Source | HTML placeholders or a React component |
| CSS | Styles for this template only |
| Status | `Active` or `Suspended` |

When you save a new custom template, InstaWeb gives it a stable internal key such as `custom-ledger`. The key is used by portfolios and should be treated as permanent. You can change the display name without breaking existing portfolios.

## 3. Create a template: recommended workflow

1. In **Admin → Portfolio Templates**, choose **Add custom template**.
2. Give the layout a specific name. Good: `Ledger — professional finance portfolio`. Weak: `Template 1`.
3. Add a short description explaining who it suits and its style.
4. Choose the renderer:
   - Choose **HTML + CSS** for a content-led layout with no application state.
   - Choose **React + JSX** for interactive elements or data transformations.
5. Start from the provided default code rather than a blank page.
6. Keep the first version intentionally small: hero, about, sections, projects, contact, footer.
7. Save the template. It becomes available in Portfolio Studio immediately because it starts as active.
8. Create or choose an admin-owned portfolio that uses the template, fill it with realistic content, and preview it on desktop and mobile.
9. If the template is ready for users, keep it active. If it is unfinished, suspend it instead of deleting it.

## 4. Design rules that make templates reliable

### Create a clear hierarchy

Every template should make the following easy to scan:

1. Who is this person?
2. What do they do or study?
3. What proof of work or experience do they have?
4. How can someone contact them?

Use one large page heading (`h1`) for the person’s name. Use `h2` headings for major sections such as About, Experience, Education, Skills, Projects, and Contact. Use `h3` headings for cards and individual entries.

### Design for missing content

Not every portfolio has a photo, projects, social links, or every advanced section. Do not make the design depend on all of them.

- If there is no photo, the hero should still look intentional.
- If there are no projects, do not leave a large empty coloured area.
- If there is no email, do not show an empty contact button.
- If there are no experience records, education or skills should still carry the page.

### Design for long content

A user may have a 150-character name, a long biography, or ten projects. Avoid rigid fixed heights and `overflow: hidden` on text.

- Use `max-width` for reading text.
- Use responsive grids such as `repeat(auto-fit, minmax(240px, 1fr))`.
- Let cards grow vertically.
- Keep long text readable with a line-height around `1.5` to `1.8`.
- Use `overflow-wrap: anywhere` on large names or URLs when necessary.

### Make contrast and interaction obvious

Text must stand out from its background. Links and buttons must be visibly interactive without relying only on colour. Buttons should have a comfortable tap target on phones—roughly 44px high is a good target.

### Avoid visual noise

Choose one primary visual idea: a muted professional palette, oversized type, an editorial grid, an image-led hero, or a technical interface. Do not put every effect into one template. Large shadows, gradients, animated backgrounds, glass cards, and many accent colours compete with the content.

## 5. HTML + CSS templates

HTML templates are the easiest and safest route for a layout that mostly presents content. Choose **HTML + CSS** in the renderer selector.

### Available placeholders

The following values can be written directly into the HTML source.

| Placeholder | Output |
| --- | --- |
| `{{name}}` | Portfolio owner’s display name |
| `{{headline}}` | Professional headline or role |
| `{{bio}}` | Biography, safely escaped with line breaks preserved |
| `{{location}}` | Location text |
| `{{avatar}}` | A complete safe `<img>` element when a profile image exists; otherwise blank |
| `{{sections}}` | All enabled advanced portfolio sections and their items |
| `{{projects}}` | The projects section when enabled |
| `{{contact}}` | Email and validated social links when contact is enabled |
| `{{year}}` | Current year |

Content placeholders are already escaped before insertion. Do not add user content into an attribute or script context yourself.

### A good starter HTML template

```html
<main class="ledger">
  <header class="ledger-hero" id="home">
    <div>
      <p class="ledger-kicker">{{location}}</p>
      <h1>{{name}}</h1>
      <p class="ledger-role">{{headline}}</p>
      <p class="ledger-intro">{{bio}}</p>
      <a class="ledger-button" href="#contact">Get in touch</a>
    </div>
    <aside class="ledger-photo">{{avatar}}</aside>
  </header>

  {{sections}}
  {{projects}}
  {{contact}}

  <footer>© {{year}} {{name}}</footer>
</main>
```

### A matching CSS foundation

```css
.ledger {
  max-width: 1180px;
  margin: 0 auto;
  padding: clamp(20px, 4vw, 56px);
  color: #172b3a;
}

.ledger-hero {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(240px, .65fr);
  align-items: center;
  gap: clamp(32px, 7vw, 96px);
  min-height: 72vh;
}

.ledger h1 {
  margin: 0;
  font: 800 clamp(3.4rem, 9vw, 7.5rem)/.9 Georgia, serif;
  letter-spacing: -.065em;
  overflow-wrap: anywhere;
}

.ledger-kicker {
  color: #a9512c;
  font-size: .75rem;
  font-weight: 800;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.ledger-photo .custom-avatar {
  display: block;
  width: 100%;
  aspect-ratio: 4 / 5;
  object-fit: cover;
}

.ledger .custom-section {
  padding: 72px 0;
  border-top: 1px solid #d6dce0;
}

.ledger .custom-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
}

.ledger .custom-card {
  padding: 22px;
  border: 1px solid #d8dfe3;
  background: #fff;
}

@media (max-width: 720px) {
  .ledger-hero { grid-template-columns: 1fr; min-height: auto; padding: 56px 0; }
  .ledger { padding: 20px; }
}
```

### HTML template safety limits

HTML templates are intentionally not arbitrary web pages. The save process rejects scripts, PHP tags, forms, frames, embedded documents, metadata tags, external style imports, inline event handlers such as `onclick`, and executable URLs. This protects visitors and prevents a template from taking control of the application.

Use semantic HTML, normal links, classes, and CSS. If you need interaction, use the React renderer instead.

## 6. React + JSX templates

Choose **React + JSX** when the presentation needs controlled interactivity. Common valid reasons include:

- Expand and collapse a long projects list.
- Filter projects by category.
- Switch between experience and education views.
- Build accessible tabs, an accordion, or a compact mobile menu.
- Calculate a small display value from portfolio data.

### React component contract

The editor supplies React globally. Define exactly one component named `Portfolio`; do not import or export anything.

```jsx
function Portfolio({ data }) {
  return <main>{data.name}</main>;
}
```

Use hooks through the global React object:

```jsx
const [open, setOpen] = React.useState(false);
React.useEffect(() => {
  // Run a small, local visual effect if needed.
}, []);
```

Do not write this:

```jsx
import React from 'react';
export default function Portfolio() {}
```

Imports and exports are rejected because the template runs from the application’s local isolated renderer rather than from a bundler such as Vite or Webpack.

### The `data` object

The component receives a `data` object with this shape:

```js
{
  name: 'John Doe',
  initials: 'J',
  headline: 'BBA Accounting Graduate',
  bio: '...',
  email: 'john@example.com',
  contactAvailable: true,
  location: 'Blantyre, Malawi',
  avatar: '/uploads/portfolios/55/avatar.jpg',
  accent: '#f97316',
  background: '#ffffff',
  textColor: '#111827',
  fontFamily: 'system-ui, -apple-system, sans-serif',
  heroStyle: 'centered',
  showProjects: true,
  showContact: true,
  projects: [
    {
      id: 1,
      title: 'Expense tracker',
      summary: '...',
      project_url: 'https://example.com',
      image_url: ''
    }
  ],
  sections: [
    {
      key: 'experience',
      label: 'Work experience',
      items: [
        {
          title: 'Finance Intern',
          details: '...',
          organisation: 'Example Ltd',
          period: '2025',
          link: ''
        }
      ]
    }
  ],
  socials: [{ label: 'LinkedIn', url: 'https://linkedin.com/in/example' }]
}
```

Some values may be blank. Always make optional values conditional.

```jsx
{data.avatar && <img src={data.avatar} alt={data.name} />}
{data.contactAvailable && <a href="#portfolio-contact-dialog" data-portfolio-contact>Email me</a>}
{data.projects.length > 0 && <Projects projects={data.projects} />}
```

Use `data.contactAvailable` for a contact action. Add `href="#portfolio-contact-dialog"` and `data-portfolio-contact` exactly as shown: the public portfolio page opens the built-in accessible message form and sends it through the application's configured SMTP service. Do not build a separate form or use `mailto:` in a custom React template.

### Complete React example

This example creates a profile-led portfolio with a “show all projects” action.

```jsx
function Portfolio({ data }) {
  const [showAllProjects, setShowAllProjects] = React.useState(false);
  const visibleProjects = showAllProjects ? data.projects : data.projects.slice(0, 3);

  return (
    <main className="cobalt-portfolio">
      <header className="cobalt-hero" id="home">
        <div>
          <p className="cobalt-label">{data.location || 'Portfolio'}</p>
          <h1>{data.name}</h1>
          {data.headline && <p className="cobalt-role">{data.headline}</p>}
          {data.bio && <p className="cobalt-intro">{data.bio}</p>}
          {data.contactAvailable && <a className="cobalt-button" href="#portfolio-contact-dialog" data-portfolio-contact>Start a conversation</a>}
        </div>
        {data.avatar && <img className="cobalt-avatar" src={data.avatar} alt={data.name} />}
      </header>

      <section className="cobalt-section" id="about">
        <p className="cobalt-label">About</p>
        <h2>Useful work, clearly presented.</h2>
        <p>{data.bio || `More information about ${data.name} will be added here.`}</p>
      </section>

      {data.sections.map(section => (
        <section className="cobalt-section" id={section.key} key={section.key}>
          <p className="cobalt-label">Portfolio</p>
          <h2>{section.label}</h2>
          <div className="cobalt-grid">
            {section.items.map((item, index) => (
              <article className="cobalt-card" key={`${section.key}-${index}`}>
                <h3>{item.title}</h3>
                {item.organisation && <p>{item.organisation}</p>}
                {item.period && <p>{item.period}</p>}
                {item.details && <p>{item.details}</p>}
                {item.link && <a href={item.link} target="_blank" rel="noreferrer">Learn more ↗</a>}
              </article>
            ))}
          </div>
        </section>
      ))}

      {data.showProjects && (
        <section className="cobalt-section" id="projects">
          <p className="cobalt-label">Selected work</p>
          <h2>Projects</h2>
          <div className="cobalt-grid">
            {visibleProjects.map((project, index) => (
              <article className="cobalt-card" key={project.id || index}>
                {project.image_url && <img src={project.image_url} alt="" />}
                <h3>{project.title}</h3>
                {project.summary && <p>{project.summary}</p>}
                {project.project_url && <a href={project.project_url} target="_blank" rel="noreferrer">View project ↗</a>}
              </article>
            ))}
          </div>
          {data.projects.length > 3 && (
            <button type="button" onClick={() => setShowAllProjects(value => !value)}>
              {showAllProjects ? 'Show fewer projects' : 'Show all projects'}
            </button>
          )}
        </section>
      )}

      {data.showContact && (data.contactAvailable || data.socials.length > 0) && (
        <section className="cobalt-section cobalt-contact" id="contact">
          <p className="cobalt-label">Contact</p>
          <h2>Let’s work together.</h2>
          {data.contactAvailable && <a className="cobalt-button" href="#portfolio-contact-dialog" data-portfolio-contact>Email {data.name}</a>}
          <nav aria-label="Social profiles">
            {data.socials.map(social => <a key={social.label} href={social.url} target="_blank" rel="noreferrer">{social.label}</a>)}
          </nav>
        </section>
      )}
    </main>
  );
}
```

Suggested CSS for the example:

```css
.cobalt-portfolio { max-width: 1120px; margin: 0 auto; padding: clamp(20px, 5vw, 64px); color: #14213d; }
.cobalt-hero { min-height: 74vh; display: grid; grid-template-columns: 1.15fr .65fr; align-items: center; gap: clamp(30px, 7vw, 90px); }
.cobalt-label { margin: 0 0 12px; color: #275daf; font-weight: 800; font-size: .75rem; letter-spacing: .12em; text-transform: uppercase; }
.cobalt-hero h1 { margin: 0; font-size: clamp(3.4rem, 10vw, 8rem); line-height: .88; letter-spacing: -.07em; overflow-wrap: anywhere; }
.cobalt-role { font-size: 1.25rem; font-weight: 750; }.cobalt-intro { max-width: 620px; line-height: 1.75; color: #475569; }
.cobalt-avatar { width: 100%; aspect-ratio: 4 / 5; object-fit: cover; border-radius: 20px; }
.cobalt-section { padding: clamp(52px, 8vw, 100px) 0; border-top: 1px solid #dbe3ef; }.cobalt-section h2 { font-size: clamp(2.1rem, 5vw, 4rem); letter-spacing: -.05em; }
.cobalt-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; }.cobalt-card { padding: 22px; border: 1px solid #dbe3ef; border-radius: 16px; background: #fff; }
.cobalt-card img { display: block; width: calc(100% + 44px); height: 180px; margin: -22px -22px 18px; border-radius: 16px 16px 0 0; object-fit: cover; }.cobalt-card a { color: #275daf; font-weight: 800; }
.cobalt-button, .cobalt-section button { display: inline-block; margin-top: 18px; padding: 13px 18px; border: 0; border-radius: 8px; background: #14213d; color: #fff; font-weight: 800; text-decoration: none; cursor: pointer; }
.cobalt-contact { background: #eef5ff; padding-inline: clamp(20px, 5vw, 64px); margin-inline: calc(clamp(20px, 5vw, 64px) * -1); }
@media (max-width: 720px) { .cobalt-hero { grid-template-columns: 1fr; min-height: auto; padding: 50px 0; }.cobalt-avatar { max-width: 360px; } }
```

### React safety and runtime rules

React templates run in a sandboxed frame. The frame receives the portfolio data and cannot access the dashboard or parent page. The source is compiled locally, with local React and local JSX tooling; it does not depend on an external runtime CDN.

For safety and predictable behavior, the save process rejects:

- `import`, `export`, and `require`.
- Network APIs such as `fetch`, `XMLHttpRequest`, `WebSocket`, and `EventSource`.
- Browser storage and device APIs such as `localStorage`, `sessionStorage`, `indexedDB`, and `navigator`.
- Web workers, frames, embedded documents, forms, scripts, and dynamic code APIs.
- External CSS imports and executable URLs.

Keep interactions local to the rendered component. State, filtering, sorting, small animations, and toggles are good uses of React. Calling services, storing credentials, integrating payment systems, or loading remote code is not.

## 7. Navigation requirements

Public portfolio navigation is ranked automatically and displays at most five links. The priority is:

1. About
2. Experience
3. Projects
4. Skills
5. Contact
6. Education
7. Goals
8. Interests
9. Challenges
10. Partnerships
11. News and updates
12. CV / Resume

If a higher-ranked section is unavailable, the next available section fills the place. In a custom React template, follow this same principle: do not hard-code links to sections that may be empty. For example, only show a Projects link when `data.showProjects` is true and `data.projects.length > 0`.

## 8. Responsive design checklist

Test every custom template at these approximate widths:

| Viewport | What to check |
| --- | --- |
| 320–390px | No horizontal scrolling, readable type, usable buttons, one-column cards |
| 768px | Two-column layouts transition cleanly and images do not dominate content |
| 1024px | Navigation, hero, and grids have comfortable whitespace |
| 1440px+ | Text does not become too wide and the design does not look stretched |

Use these defaults unless there is a strong reason not to:

```css
/* Flexible cards */
grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));

/* Scalable spacing */
padding: clamp(20px, 5vw, 72px);

/* Scalable headings */
font-size: clamp(2.5rem, 8vw, 7rem);

/* A safe image crop */
aspect-ratio: 4 / 5;
object-fit: cover;
```

Avoid fixed pixel widths for the whole page and fixed heights for text cards. Avoid animations that are essential to reading the content. If you add motion, make sure the page is still understandable when it does not run.

## 9. Quality assurance before activating a template

Use a realistic test portfolio before allowing users to select a template.

### Content checks

- A profile with an avatar, and another without one.
- A short name, a very long name, a short biography, and a long biography.
- Zero projects, three projects, and more than six projects.
- At least two experience items, two skills, and one education item.
- A valid email, social links, and a version with no contact information.
- Long project titles and long section-item details.

### Visual checks

- Heading hierarchy is logical: one `h1`, then section `h2` headings.
- Text remains legible against every background.
- All buttons and links are visible, reachable, and have sensible labels.
- Image alt text is meaningful where the image conveys information; decorative images may use an empty `alt`.
- Cards align even with uneven text lengths.
- There is no unwanted horizontal scrollbar.
- The footer does not cover content.

### Interaction checks for React templates

- Buttons work using a mouse and keyboard.
- Any expand/collapse action remains understandable with JavaScript state at its default value.
- Every mapped list item has a stable `key`.
- External project and social links use `target="_blank"` together with `rel="noreferrer"` when they open a new tab.
- No error message appears inside the template frame.

## 10. Template lifecycle: activate, suspend, delete

### Activate

An active template appears in Portfolio Studio and can be selected by portfolio owners.

### Suspend

Suspension hides a template from new selections but preserves existing portfolios that already use it. Use this when:

- You found a layout issue and need time to fix it.
- The template is being redesigned.
- You want to retire it gradually without breaking public portfolios.

### Delete

Only unused custom templates can be deleted. A template currently used by any portfolio cannot be deleted until those portfolios move to another template. Built-in templates cannot be deleted.

Recommended retirement process:

1. Create and test a replacement.
2. Suspend the old template.
3. Move affected portfolios to the replacement.
4. Confirm there are no remaining users of the old template.
5. Delete the old custom template only when it is no longer needed.

## 11. Default preview portfolios

The Admin Template Manager also lets you select an admin-owned portfolio as a default preview for each template. This preview portfolio must use the same template and must be published before visitors can see it.

Use a complete, realistic sample profile. It should include a good photo, meaningful biography, projects, experience, education, skills, and working contact details. A weak or empty sample makes even a good template look unfinished.

The protected John Doe portfolio is suitable as a baseline example. Do not delete or repurpose the protected system portfolio.

## 12. Common mistakes and fixes

| Problem | Likely cause | Fix |
| --- | --- | --- |
| Template looks empty | It only displays projects or a photo | Make the hero and about section work with text alone |
| Content overlaps on mobile | Fixed widths, absolute positioning, or fixed heights | Use grid/flex layouts and a mobile media query |
| React template fails to render | Missing `Portfolio` component, JSX syntax error, import/export used | Define `function Portfolio({ data })`, remove imports/exports, then inspect the code for syntax errors |
| Image is stretched | Image lacks `object-fit: cover` or an aspect ratio | Add `aspect-ratio` and `object-fit: cover` |
| Project link is unsafe or missing | Stored link is invalid | Use a valid `http` or `https` URL in the project editor |
| A suspended template disappeared from the chooser | This is expected | Reactivate it to allow new portfolios to select it |
| Template cannot be deleted | A portfolio still uses it | Move that portfolio to another template first |
| Template is too similar to another one | Only colours changed | Change hierarchy, grid, typography, emphasis, and section treatment—not just colours |

## 13. A practical template brief

Before building a new template, fill in this short brief:

```text
Name:
Audience:
Primary action:
Template mood (three words):
Primary content to highlight:
Secondary content:
Photo required? Yes / No / Optional
Renderer: HTML + CSS / React + JSX
Mobile layout plan:
Colour palette:
Typography direction:
Interaction needed (if React):
Sample portfolio to test with:
```

If you cannot explain the template in this brief, the design direction is probably not ready yet.

## 14. Final release checklist

Before activating a custom template for users:

- [ ] Name and description explain the template’s intended use.
- [ ] The template works with and without a profile photo.
- [ ] The template works with zero, few, and many projects.
- [ ] Every major content area has a clear heading.
- [ ] The mobile layout has no horizontal scrolling.
- [ ] Buttons and links have visible focus and hover states.
- [ ] Text and controls have adequate contrast.
- [ ] The portfolio navigation remains limited to the most useful five destinations.
- [ ] React templates define `Portfolio`, use no imports/exports, and keep all interaction local.
- [ ] The template has been tested with an admin-owned published preview portfolio.
- [ ] The template is active only when it is ready for users.

Following this process will give you templates that look distinct, stay readable, and remain dependable as portfolio content grows.
