/*
 * These portfolio variants deliberately use a tiny local DOM renderer instead
 * of a remote React bundle. Public portfolios must still render when a viewer
 * is offline or their network blocks esm.sh, which previously left the generic
 * PHP fallback visible.
 */
const ignoredProps = new Set(['animate', 'initial', 'transition', 'viewport', 'whileHover', 'whileInView']);
const h = (type, props, ...children) => {
  if (typeof type === 'function') return type({ ...(props || {}), children });
  const element = document.createElement(type);
  Object.entries(props || {}).forEach(([name, value]) => {
    if (value == null || value === false || ignoredProps.has(name)) return;
    if (name === 'className') { element.className = value; return; }
    if (name === 'style' && typeof value === 'object') {
      Object.entries(value).forEach(([property, styleValue]) => {
        if (styleValue != null && property.startsWith('--')) element.style.setProperty(property, styleValue);
        else if (styleValue != null && property in element.style) element.style[property] = styleValue;
      });
      return;
    }
    if (name.startsWith('on') && typeof value === 'function') {
      element.addEventListener(name.slice(2).toLowerCase(), value);
      return;
    }
    element.setAttribute(name, value === true ? 'true' : String(value));
  });
  const append = child => {
    if (child == null || child === false || child === true) return;
    if (Array.isArray(child)) { child.forEach(append); return; }
    element.append(child instanceof Node ? child : document.createTextNode(String(child)));
  };
  children.forEach(append);
  return element;
};
const Motion = new Proxy({}, { get: (_target, tag) => tag });
const useEffect = callback => callback();
const useState = initial => [initial, () => {}];
const useMotionValue = value => value;
const useSpring = value => value;
const useTransform = () => 0;
const useReducedMotion = () => true;

function useScrollProgress() {
  return 0;
}

function TiltCard({ children, className = '' }) {
  const prefersReducedMotion = useReducedMotion() === true;
  const x = useMotionValue(0); const y = useMotionValue(0);
  const rotateX = useSpring(useTransform(y, [-.5, .5], [7, -7]), { stiffness: 150, damping: 18 });
  const rotateY = useSpring(useTransform(x, [-.5, .5], [-7, 7]), { stiffness: 150, damping: 18 });
  return h(Motion.article, { className: `rp-card ${className}`, style: prefersReducedMotion ? {} : { rotateX, rotateY }, onPointerMove: prefersReducedMotion ? undefined : e => { const r = e.currentTarget.getBoundingClientRect(); x.set((e.clientX - r.left) / r.width - .5); y.set((e.clientY - r.top) / r.height - .5); }, onPointerLeave: prefersReducedMotion ? undefined : () => { x.set(0); y.set(0); }, whileHover: prefersReducedMotion ? undefined : { y: -5 }, transition: { type: 'spring', stiffness: 220, damping: 20 } }, children);
}

const navigationPriority = ['about', 'experience', 'projects', 'skills', 'contact', 'education', 'goals', 'interests', 'challenges', 'partnerships', 'news', 'cv'];

function rankedNavigation(data) {
  const sections = new Map((data.sections || [])
    .filter(section => (section.items || []).length > 0)
    .map(section => [section.key, { id: section.key, label: section.key === 'experience' ? 'Experience' : section.label }]));
  const available = new Map([
    ['about', { id: 'about', label: 'About' }],
    ...sections,
    ...(data.showProjects && (data.projects || []).length > 0 ? [['projects', { id: 'projects', label: 'Work' }]] : []),
    ...(data.showContact && (data.email || (data.socials || []).length) ? [['contact', { id: 'contact', label: 'Contact' }]] : []),
  ]);
  const remaining = [...sections.keys()].filter(key => !navigationPriority.includes(key));

  return [...navigationPriority, ...remaining]
    .map(key => available.get(key))
    .filter(Boolean)
    .slice(0, 5);
}

function Navigation({ data }) {
  const links = rankedNavigation(data);
  return h('nav', { className: 'rp-nav', 'aria-label': 'Portfolio navigation' }, h('a', { href: '#home', className: 'rp-wordmark' }, data.initials), h('div', { className: 'rp-nav-links' }, links.map(link => h('a', { href: `#${link.id}`, key: link.id }, link.label))));
}

function Section({ id, eyebrow, title, children }) {
  const prefersReducedMotion = useReducedMotion() === true;
  return h(Motion.section, { id, className: 'rp-section', initial: prefersReducedMotion ? false : { opacity: 0, y: 24 }, whileInView: prefersReducedMotion ? undefined : { opacity: 1, y: 0 }, viewport: { once: true, amount: .15 }, transition: { duration: prefersReducedMotion ? 0 : .55, ease: 'easeOut' } }, h('div', { className: 'rp-wrap' }, h('p', { className: 'rp-eyebrow' }, eyebrow), h('h2', null, title), children));
}

function AuroraArt({ index, label }) {
  const gradientId = `aurora-gradient-${index}`;
  const paths = [
    'M-15 135C80 20 135 235 240 122s137-59 236-73',
    'M-25 176C70 220 105 38 212 110s132 145 280 22',
    'M-20 92C80 175 135 7 229 75s153 182 271 44'
  ];
  return h('div', { className: 'am-art', 'aria-hidden': true },
    h('span', { className: 'am-art-label' }, label),
    h('svg', { viewBox: '0 0 480 260', fill: 'none', preserveAspectRatio: 'none' },
      h('defs', null, h('linearGradient', { id: gradientId, x1: '0', y1: '0', x2: '1', y2: '0' }, h('stop', { stopColor: '#65f6ed' }), h('stop', { offset: '.52', stopColor: '#8d7dff' }), h('stop', { offset: '1', stopColor: '#fb88d7' }))),
      h('path', { className: 'am-art-grid', d: 'M0 52H480M0 130H480M0 208H480M80 0V260M240 0V260M400 0V260' }),
      h('path', { className: 'am-art-path', d: paths[index % paths.length], stroke: `url(#${gradientId})` }),
      h('circle', { className: 'am-art-orb am-art-orb--one', cx: 125 + (index % 3) * 48, cy: 92, r: 11, fill: `url(#${gradientId})` }),
      h('circle', { className: 'am-art-orb am-art-orb--two', cx: 332, cy: 170 - (index % 2) * 48, r: 6, fill: '#e8efff' })
    )
  );
}

function AuroraEntry({ item, index, sectionKey, prefersReducedMotion }) {
  return h(Motion.article, { className: `am-entry am-entry--${sectionKey}`, initial: prefersReducedMotion ? false : { opacity: 0, y: 18 }, whileInView: prefersReducedMotion ? undefined : { opacity: 1, y: 0 }, viewport: { once: true, amount: .2 }, transition: { duration: prefersReducedMotion ? 0 : .42, delay: prefersReducedMotion ? 0 : Math.min(index * .06, .24), ease: 'easeOut' } },
    h('span', { className: 'am-entry-index' }, String(index + 1).padStart(2, '0')),
    h('div', { className: 'am-entry-main' }, h('h3', null, item.title), item.details ? h('p', null, item.details) : null),
    h('div', { className: 'am-entry-meta' }, item.organisation ? h('span', null, item.organisation) : null, item.period ? h('span', null, item.period) : null, item.link ? h('a', { href: item.link, target: '_blank', rel: 'noreferrer' }, 'Open ↗') : null)
  );
}

function AuroraSection({ section, index, prefersReducedMotion }) {
  return h(Motion.section, { id: section.key, className: `am-section am-section--${section.key}`, initial: prefersReducedMotion ? false : { opacity: 0, y: 28 }, whileInView: prefersReducedMotion ? undefined : { opacity: 1, y: 0 }, viewport: { once: true, amount: .12 }, transition: { duration: .6, ease: 'easeOut' } },
    h('div', { className: 'rp-wrap' },
      h('header', { className: 'am-section-heading' }, h('span', null, `0${index + 2}`), h('p', null, section.label), h('h2', null, section.key === 'experience' ? 'The work behind the glow.' : section.label)),
      h('div', { className: 'am-section-body' }, h(AuroraArt, { index, label: section.label }), h('div', { className: 'am-entry-list' }, section.items.map((item, itemIndex) => h(AuroraEntry, { key: `${section.key}-${itemIndex}`, item, index: itemIndex, sectionKey: section.key, prefersReducedMotion }))))
    )
  );
}

function AuroraApp({ data }) {
  const progress = useScrollProgress();
  const prefersReducedMotion = useReducedMotion() === true;
  const links = rankedNavigation(data);
  const projects = data.projects || [];
  const navigation = h('nav', { className: 'am-nav', 'aria-label': 'Portfolio navigation' },
    h('a', { href: '#home', className: 'am-wordmark' }, h('span', null, data.initials), h('strong', null, 'Aurora Motion')),
    h('div', { className: 'am-nav-links' }, links.map(link => h('a', { href: `#${link.id}`, key: link.id }, link.label)))
  );
  const heroCopy = h('div', { className: 'am-hero-copy' },
    h('p', { className: 'am-kicker' }, data.location || 'Independent digital practice'),
    h(Motion.h1, { initial: prefersReducedMotion ? false : { opacity: 0, y: 32 }, animate: prefersReducedMotion ? undefined : { opacity: 1, y: 0 }, transition: { duration: .7, ease: 'easeOut' } }, data.name),
    h('p', { className: 'am-headline' }, data.headline || 'Building systems that move people forward.'),
    h('p', { className: 'am-intro' }, data.bio || 'Selected work, experience, and practical ideas brought into motion.'),
    h('div', { className: 'am-actions' },
      data.showProjects ? h('a', { href: '#projects', className: 'am-button am-button--primary' }, 'Explore selected work') : null,
      data.email ? h('a', { href: `mailto:${data.email}`, className: 'am-button' }, 'Start a conversation') : null
    )
  );
  const heroVisual = h('div', { className: 'am-hero-visual', 'aria-hidden': data.avatar ? undefined : true },
    h('div', { className: 'am-hero-rings' }, h('i'), h('i'), h('i')),
    data.avatar ? h(Motion.img, { src: data.avatar, alt: '' }) : h(AuroraArt, { index: 1, label: 'Live signal' }),
    h('span', { className: 'am-visual-caption' }, 'Live / in motion')
  );
  const hero = h('header', { id: 'home', className: 'am-hero' }, h('div', { className: 'rp-wrap am-hero-layout' }, heroCopy, heroVisual));
  const about = h('section', { id: 'about', className: 'am-about' }, h('div', { className: 'rp-wrap am-about-layout' },
    h('div', null, h('p', { className: 'am-kicker' }, '01 / Profile'), h('h2', null, 'Ideas with momentum.')),
    h('p', null, data.bio || `More information about ${data.name} will be added here.`),
    h(AuroraArt, { index: 0, label: 'Direction' })
  ));
  const projectCards = projects.map((project, index) => h(TiltCard, { key: project.id || index, className: `am-project am-project--${index < 2 ? 'featured' : 'standard'}` },
    h('span', { className: 'am-entry-index' }, String(index + 1).padStart(2, '0')),
    h('div', { className: 'am-project-glow', 'aria-hidden': true }),
    h('h3', null, project.title),
    project.summary ? h('p', null, project.summary) : null,
    project.project_url ? h('a', { href: project.project_url, target: '_blank', rel: 'noreferrer' }, 'Visit project ↗') : null
  ));
  const projectSection = data.showProjects ? h('section', { id: 'projects', className: 'am-projects' }, h('div', { className: 'rp-wrap' },
    h('header', { className: 'am-project-heading' }, h('p', { className: 'am-kicker' }, 'Selected work'), h('h2', null, 'Systems in motion.'), h('p', null, 'A focused selection of digital products and practical platforms.')),
    h('div', { className: 'am-project-grid' }, projectCards)
  )) : null;
  const contactSection = data.showContact && (data.email || (data.socials || []).length) ? h('section', { id: 'contact', className: 'am-contact' }, h('div', { className: 'rp-wrap' },
    h('p', { className: 'am-kicker' }, 'Let’s connect'),
    h('h2', null, 'Make the next useful thing.'),
    h('div', null, h('p', null, 'Have a system, platform, or idea that needs momentum?'), data.email ? h('a', { href: `mailto:${data.email}`, className: 'am-contact-link' }, `Email ${data.name} ↗`) : null, h('nav', { 'aria-label': 'Social profiles' }, (data.socials || []).map(social => h('a', { key: social.label, href: social.url, target: '_blank', rel: 'noreferrer' }, social.label))))
  )) : null;
  return h('div', { className: 'rp rp--aurora am', style: { '--rp-accent': '#74f8ff', '--am-violet': '#8d7dff', '--am-pink': '#fb88d7' } },
    h('div', { className: 'rp-progress am-progress', style: { transform: `scaleX(${progress})` } }),
    navigation,
    hero,
    about,
    (data.sections || []).map((section, index) => h(AuroraSection, { key: section.key, section, index, prefersReducedMotion })),
    projectSection,
    contactSection,
    h('footer', { className: 'am-footer' }, `© ${new Date().getFullYear()} ${data.name} / Aurora Motion`)
  );
}

function PortfolioLinks({ data, className = '', limit }) {
  const links = rankedNavigation(data);
  return h('nav', { className, 'aria-label': 'Portfolio navigation' }, links.slice(0, Math.min(limit || 5, 5)).map(link => h('a', { href: `#${link.id}`, key: link.id }, link.label)));
}

function StudioApp({ data }) {
  const sections = data.sections || [];
  const projects = data.projects || [];
  const contentSections = sections.map((section, sectionIndex) => {
    const entries = (section.items || []).map((item, itemIndex) => h('article', { key: `${section.key}-${itemIndex}` },
      h('span', null, String(itemIndex + 1).padStart(2, '0')),
      h('div', null, h('h3', null, item.title), item.details ? h('p', null, item.details) : null),
      h('div', { className: 'rs-meta' }, item.organisation ? h('span', null, item.organisation) : null, item.period ? h('span', null, item.period) : null, item.link ? h('a', { href: item.link, target: '_blank', rel: 'noreferrer' }, 'Open ↗') : null)
    ));
    return h('section', { id: section.key, className: 'rs-section', key: section.key }, h('header', null, h('span', null, String(sectionIndex + 2).padStart(2, '0')), h('h2', null, section.label)), h('div', { className: 'rs-entries' }, entries));
  });
  const projectSection = data.showProjects ? h('section', { id: 'projects', className: 'rs-projects' }, h('p', { className: 'rs-label' }, 'Selected work'), h('h2', null, 'Recent collaborations.'), h('div', null, projects.map((project, index) => h('article', { key: project.id || index }, h('span', null, String(index + 1).padStart(2, '0')), h('h3', null, project.title), project.summary ? h('p', null, project.summary) : null, project.project_url ? h('a', { href: project.project_url, target: '_blank', rel: 'noreferrer' }, 'View case study ↗') : null)))) : null;
  const contactSection = data.showContact && (data.email || (data.socials || []).length) ? h('section', { id: 'contact', className: 'rs-contact' }, h('p', { className: 'rs-label' }, 'Available for considered work'), h('h2', null, 'Start with a good question.'), data.email ? h('a', { href: `mailto:${data.email}` }, data.email) : null, h('div', null, (data.socials || []).map(social => h('a', { href: social.url, target: '_blank', rel: 'noreferrer', key: social.label }, social.label)))) : null;
  const rail = h('aside', { className: 'rs-rail' }, h('a', { href: '#home', className: 'rs-mark' }, h('span', null, data.initials), h('strong', null, data.name)), h('p', { className: 'rs-rail-note' }, data.location || 'Independent studio'), h(PortfolioLinks, { data, className: 'rs-links', limit: 5 }), h('p', { className: 'rs-rail-footer' }, `© ${new Date().getFullYear()} / Selected practice`));
  const hero = h('header', { id: 'home', className: 'rs-hero' }, h('p', { className: 'rs-label' }, 'Creative practice / 01'), h('h1', null, data.name), data.avatar ? h('figure', { className: 'rs-portrait' }, h('img', { src: data.avatar, alt: '' })) : null, h('div', { className: 'rs-hero-bottom' }, h('p', null, data.headline || 'Thoughtful work for people and organisations.'), h('p', null, data.bio || 'A small, independent practice for clear digital experiences and useful systems.'), data.showProjects ? h('a', { href: '#projects', className: 'rs-button' }, 'Browse selected work ↘') : null));
  return h('div', { className: 'rp rs', style: { '--rs-accent': data.accent || '#df5d3e' } }, rail, h('main', { className: 'rs-main' }, hero, h('section', { id: 'about', className: 'rs-intro' }, h('p', { className: 'rs-label' }, 'A note on the work'), h('h2', null, 'Ideas made tangible.'), h('p', null, data.bio || `More information about ${data.name} will be added here.`)), contentSections, projectSection, contactSection));
}

function OrbitApp({ data }) {
  const sections = data.sections || [];
  const projects = data.projects || [];
  const modules = sections.map((section, sectionIndex) => {
    const items = (section.items || []).map((item, itemIndex) => h('article', { key: `${section.key}-${itemIndex}` }, h('span', null, '●'), h('div', null, h('h3', null, item.title), item.details ? h('p', null, item.details) : null), h('footer', null, item.organisation ? h('span', null, item.organisation) : null, item.period ? h('span', null, item.period) : null, item.link ? h('a', { href: item.link, target: '_blank', rel: 'noreferrer' }, 'Inspect ↗') : null)));
    return h('section', { id: section.key, className: 'ro-module', key: section.key }, h('div', { className: 'ro-module-title' }, h('span', null, String(sectionIndex + 2).padStart(2, '0')), h('h2', null, section.label)), h('div', { className: 'ro-module-items' }, items));
  });
  const consolePanel = h('div', { className: 'ro-console', 'aria-hidden': data.avatar ? undefined : true }, h('div', { className: 'ro-orbit ro-orbit--one' }), h('div', { className: 'ro-orbit ro-orbit--two' }), h('div', { className: 'ro-core' }, data.avatar ? h('img', { src: data.avatar, alt: '' }) : h('span', null, data.initials)), h('dl', null, h('div', null, h('dt', null, 'Work logs'), h('dd', null, String(projects.length).padStart(2, '0'))), h('div', null, h('dt', null, 'Signals'), h('dd', null, String(sections.length).padStart(2, '0')))));
  const hero = h('section', { id: 'home', className: 'ro-hero' }, h('div', { className: 'ro-copy' }, h('p', null, 'Mission control / active profile'), h('h1', null, data.name), h('h2', null, data.headline || 'A focused independent practice.'), h('p', null, data.bio || 'Selected projects, working history and ways to connect.'), h('div', { className: 'ro-actions' }, data.showProjects ? h('a', { href: '#projects' }, 'Enter work log') : null, data.email ? h('a', { href: `mailto:${data.email}` }, 'Open channel') : null)), consolePanel);
  const projectSection = data.showProjects ? h('section', { id: 'projects', className: 'ro-projects' }, h('header', null, h('span', null, 'Work log'), h('h2', null, 'Selected missions.')), h('div', { className: 'ro-project-grid' }, projects.map((project, index) => h('article', { key: project.id || index }, h('span', null, String(index + 1).padStart(2, '0')), h('h3', null, project.title), project.summary ? h('p', null, project.summary) : null, project.project_url ? h('a', { href: project.project_url, target: '_blank', rel: 'noreferrer' }, 'Open record ↗') : null)))) : null;
  const contactSection = data.showContact && (data.email || (data.socials || []).length) ? h('section', { id: 'contact', className: 'ro-contact' }, h('p', null, 'Transmission open'), h('h2', null, 'Plot the next move.'), data.email ? h('a', { href: `mailto:${data.email}` }, `Contact ${data.name} ↗`) : null, h('div', null, (data.socials || []).map(social => h('a', { href: social.url, target: '_blank', rel: 'noreferrer', key: social.label }, social.label)))) : null;
  return h('div', { className: 'rp ro', style: { '--ro-accent': data.accent || '#62ead7' } }, h('div', { className: 'ro-scanline', 'aria-hidden': true }), h('header', { className: 'ro-header' }, h('a', { href: '#home', className: 'ro-brand' }, h('span', null, data.initials), 'Orbit / Portfolio'), h(PortfolioLinks, { data, className: 'ro-links', limit: 5 })), h('main', null, hero, h('section', { id: 'about', className: 'ro-brief' }, h('span', null, '01'), h('div', null, h('p', null, 'Profile transmission'), h('h2', null, 'Built for a clear trajectory.'), h('p', null, data.bio || `More information about ${data.name} will be added here.`))), h('div', { className: 'ro-route' }, modules), projectSection, contactSection));
}

function PrismApp({ data }) {
  const sections = data.sections || [];
  const projects = data.projects || [];
  const contentSections = sections.map((section, sectionIndex) => {
    const items = (section.items || []).map((item, itemIndex) => h('article', { key: `${section.key}-${itemIndex}` }, h('span', null, String(itemIndex + 1).padStart(2, '0')), h('h3', null, item.title), item.details ? h('p', null, item.details) : null, h('footer', null, item.organisation ? h('span', null, item.organisation) : null, item.period ? h('span', null, item.period) : null, item.link ? h('a', { href: item.link, target: '_blank', rel: 'noreferrer' }, 'Explore ↗') : null)));
    return h('section', { id: section.key, className: `rpz-section rpz-section--${sectionIndex % 3}`, key: section.key }, h('header', null, h('span', null, String(sectionIndex + 2).padStart(2, '0')), h('h2', null, section.label)), h('div', { className: 'rpz-items' }, items));
  });
  const projectCards = projects.map((project, index) => h('article', { key: project.id || index }, h('span', null, String(index + 1).padStart(2, '0')), h('h3', null, project.title), project.summary ? h('p', null, project.summary) : null, project.project_url ? h('a', { href: project.project_url, target: '_blank', rel: 'noreferrer' }, 'Visit project ↗') : null));
  const hero = h('section', { id: 'home', className: 'rpz-hero' }, h('p', null, data.location || 'Independent creative direction'), h('div', { className: 'rpz-title' }, h('h1', null, data.name), h('h1', { 'aria-hidden': true }, data.name)), data.avatar ? h('figure', { className: 'rpz-portrait' }, h('img', { src: data.avatar, alt: '' })) : null, h('div', { className: 'rpz-summary' }, h('p', null, data.headline || 'Turning useful ideas into memorable experiences.'), h('p', null, data.bio || 'A vibrant mix of projects, skills and open directions.'), data.showProjects ? h('a', { href: '#projects' }, 'See the spectrum ↘') : null), h('div', { className: 'rpz-shapes', 'aria-hidden': true }, h('i'), h('i'), h('i')));
  const projectSection = data.showProjects ? h('section', { id: 'projects', className: 'rpz-projects' }, h('header', null, h('p', null, 'Practical colour studies'), h('h2', null, 'Work with a pulse.')), h('div', null, projectCards)) : null;
  const contactSection = data.showContact && (data.email || (data.socials || []).length) ? h('section', { id: 'contact', className: 'rpz-contact' }, h('h2', null, 'Make something bright.'), data.email ? h('a', { href: `mailto:${data.email}` }, data.email) : null, h('nav', null, (data.socials || []).map(social => h('a', { href: social.url, target: '_blank', rel: 'noreferrer', key: social.label }, social.label)))) : null;
  return h('div', { className: 'rp rpz', style: { '--rpz-accent': data.accent || '#f9d849' } }, h('header', { className: 'rpz-header' }, h('a', { href: '#home' }, h('span', null, data.initials), 'PRISM'), h(PortfolioLinks, { data, className: 'rpz-links', limit: 5 })), h('main', null, hero, h('section', { id: 'about', className: 'rpz-manifesto' }, h('span', null, '01'), h('h2', null, 'A point of view is part of the work.'), h('p', null, data.bio || `More information about ${data.name} will be added here.`)), contentSections, projectSection, contactSection));
}

function ArchiveApp({ data }) {
  const sections = data.sections || [];
  const projects = data.projects || [];
  const catalogueSections = sections.map((section, sectionIndex) => {
    const records = (section.items || []).map((item, itemIndex) => h('li', { key: `${section.key}-${itemIndex}` }, h('span', null, String(itemIndex + 1).padStart(2, '0')), h('article', null, h('h3', null, item.title), item.details ? h('p', null, item.details) : null), h('footer', null, item.organisation ? h('span', null, item.organisation) : null, item.period ? h('span', null, item.period) : null, item.link ? h('a', { href: item.link, target: '_blank', rel: 'noreferrer' }, 'Reference ↗') : null)));
    return h('section', { id: section.key, className: 'ra-catalogue', key: section.key }, h('header', null, h('span', null, `File ${String(sectionIndex + 2).padStart(2, '0')}`), h('h2', null, section.label)), h('ol', null, records));
  });
  const projectRecords = projects.map((project, index) => h('li', { key: project.id || index }, h('span', null, String(index + 1).padStart(2, '0')), h('div', null, h('h3', null, project.title), project.summary ? h('p', null, project.summary) : null), project.project_url ? h('a', { href: project.project_url, target: '_blank', rel: 'noreferrer' }, 'Open record →') : null));
  const lead = h('section', { id: 'home', className: 'ra-lead' }, h('div', null, h('p', null, 'A collection by'), h('h1', null, data.name), h('h2', null, data.headline || 'Notes on useful work.'), data.showProjects ? h('a', { href: '#projects' }, 'Read selected work →') : null), h('aside', null, data.avatar ? h('img', { src: data.avatar, alt: '' }) : h('span', null, data.initials), h('p', null, data.location || 'Open to connections')));
  const projectSection = data.showProjects ? h('section', { id: 'projects', className: 'ra-projects' }, h('header', null, h('span', null, 'Selected index'), h('h2', null, 'Projects and records.')), h('ol', null, projectRecords)) : null;
  const contactSection = data.showContact && (data.email || (data.socials || []).length) ? h('section', { id: 'contact', className: 'ra-contact' }, h('p', null, 'Correspondence'), h('h2', null, 'Leave a note.'), data.email ? h('a', { href: `mailto:${data.email}` }, data.email) : null, h('nav', null, (data.socials || []).map(social => h('a', { href: social.url, target: '_blank', rel: 'noreferrer', key: social.label }, social.label)))) : null;
  return h('div', { className: 'rp ra', style: { '--ra-accent': data.accent || '#b6462f' } }, h('header', { className: 'ra-masthead' }, h('a', { href: '#home' }, 'The ', h('strong', null, 'Working Archive')), h('span', null, 'Volume 01 / Portfolio'), h(PortfolioLinks, { data, className: 'ra-links', limit: 5 })), h('main', null, lead, h('section', { id: 'about', className: 'ra-editorial' }, h('span', null, 'Editor’s note'), h('p', null, data.bio || `More information about ${data.name} will be added here.`)), catalogueSections, projectSection, contactSection, h('footer', { className: 'ra-footer' }, `© ${new Date().getFullYear()} ${data.name} / The Working Archive`)));
}

function App({ data }) {
  if (data.template === 'aurora') return h(AuroraApp, { data });
  if (data.template === 'studio') return h(StudioApp, { data });
  if (data.template === 'orbit') return h(OrbitApp, { data });
  if (data.template === 'prism') return h(PrismApp, { data });
  if (data.template === 'archive') return h(ArchiveApp, { data });
  const progress = useScrollProgress();
  const prefersReducedMotion = useReducedMotion() === true;
  const heroChildren = [
    h('p', { className: 'rp-eyebrow', key: 'eyebrow' }, data.location || 'Independent portfolio'),
    h(Motion.h1, { key: 'title', initial: prefersReducedMotion ? false : { opacity: 0, y: 35 }, animate: prefersReducedMotion ? undefined : { opacity: 1, y: 0 }, transition: { duration: prefersReducedMotion ? 0 : .7, ease: 'easeOut' } }, data.name),
    data.headline ? h('p', { className: 'rp-headline', key: 'headline' }, data.headline) : null,
    h('p', { className: 'rp-intro', key: 'bio' }, data.bio || 'Selected work, experience, and ways to get in touch.'),
    h('div', { className: 'rp-actions', key: 'actions' }, data.showProjects ? h('a', { className: 'rp-action rp-action-primary', href: '#projects' }, 'View work') : null, data.email ? h('a', { className: 'rp-action', href: `mailto:${data.email}` }, 'Contact') : null)
  ];
  if (data.avatar) heroChildren.push(h(Motion.img, { className: 'rp-avatar', src: data.avatar, alt: '', key: 'avatar', initial: prefersReducedMotion ? false : { opacity: 0, scale: .92 }, animate: prefersReducedMotion ? undefined : { opacity: 1, scale: 1 }, transition: { delay: .2, duration: prefersReducedMotion ? 0 : .6 } }));
  const sections = (data.sections || []).map(section => h(Section, { key: section.key, id: section.key, eyebrow: 'Portfolio', title: section.label }, h('div', { className: 'rp-grid' }, section.items.map((item, index) => h(TiltCard, { key: `${section.key}-${index}` }, h('span', { className: 'rp-index' }, String(index + 1).padStart(2, '0')), h('h3', null, item.title), item.organisation ? h('p', { className: 'rp-meta' }, item.organisation) : null, item.period ? h('p', { className: 'rp-meta' }, item.period) : null, item.details ? h('p', null, item.details) : null, item.link ? h('a', { href: item.link, target: '_blank', rel: 'noreferrer' }, 'Open link ↗') : null)))));
  return h('div', { className: `rp rp--${data.template} rp-hero-layout--${data.heroStyle || 'centered'}`, style: { '--rp-accent': data.accent || '#8b5cf6', ...(data.background && data.background.toLowerCase() !== '#ffffff' ? { '--rp-bg': data.background } : {}), ...(data.textColor && data.textColor.toLowerCase() !== '#111827' ? { '--rp-ink': data.textColor } : {}), ...(data.fontFamily ? { '--rp-font': data.fontFamily } : {}) } }, h('div', { className: 'rp-progress', style: { transform: `scaleX(${progress})` } }), h(Navigation, { data }), h('div', { className: 'rp-orbit', 'aria-hidden': true }, !prefersReducedMotion ? [h(Motion.span, { key: 'a', animate: { rotate: 360 }, transition: { duration: 22, ease: 'linear', repeat: Infinity } }), h(Motion.span, { key: 'b', animate: { rotate: -360 }, transition: { duration: 32, ease: 'linear', repeat: Infinity } })] : null), h('header', { id: 'home', className: 'rp-hero' }, h('div', { className: 'rp-wrap rp-hero-copy' }, heroChildren)), h(Section, { id: 'about', eyebrow: 'About', title: 'A little about me' }, h('p', { className: 'rp-about' }, data.bio || `More information about ${data.name} will be added here.`)), sections, data.showProjects ? h(Section, { id: 'projects', eyebrow: 'Selected work', title: 'Projects' }, h('div', { className: 'rp-grid rp-projects' }, (data.projects || []).map((project, index) => h(TiltCard, { key: project.id || index }, h('span', { className: 'rp-index' }, String(index + 1).padStart(2, '0')), h('h3', null, project.title), project.summary ? h('p', null, project.summary) : null, project.project_url ? h('a', { href: project.project_url, target: '_blank', rel: 'noreferrer' }, 'View project ↗') : null)))) : null, data.showContact && (data.email || (data.socials || []).length) ? h(Section, { id: 'contact', eyebrow: 'Contact', title: 'Let’s work together' }, h('div', { className: 'rp-contact' }, data.email ? h('a', { className: 'rp-action rp-action-primary', href: `mailto:${data.email}` }, `Email ${data.name}`) : null, (data.socials || []).map(social => h('a', { key: social.label, href: social.url, target: '_blank', rel: 'noreferrer' }, social.label)))) : null, h('footer', { className: 'rp-footer' }, `© ${new Date().getFullYear()} ${data.name}`));
}

const mount = document.getElementById('react-portfolio-root');
if (mount) {
  try {
    const data = JSON.parse(mount.dataset.portfolio || '{}');
    mount.replaceChildren(h(App, { data }));
    document.body.classList.add('react-portfolio-ready');
  } catch (error) {
    document.body.classList.remove('react-portfolio-ready');
    console.warn('Portfolio renderer could not start; showing the standard portfolio instead.', error);
  }
}
