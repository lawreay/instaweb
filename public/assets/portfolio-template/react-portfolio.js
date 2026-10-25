import React, { useEffect, useState } from 'https://esm.sh/react@18.3.1';
import { createRoot } from 'https://esm.sh/react-dom@18.3.1/client';
import { motion, useMotionValue, useSpring, useTransform, useReducedMotion } from 'https://esm.sh/framer-motion@11.11.17?external=react,react-dom';

const h = React.createElement;
const Motion = motion;

function useScrollProgress() {
  const [progress, setProgress] = useState(0);
  useEffect(() => {
    const update = () => {
      const height = document.documentElement.scrollHeight - window.innerHeight;
      setProgress(height > 0 ? Math.min(1, window.scrollY / height) : 0);
    };
    update(); window.addEventListener('scroll', update, { passive: true }); window.addEventListener('resize', update);
    return () => { window.removeEventListener('scroll', update); window.removeEventListener('resize', update); };
  }, []);
  return progress;
}

function TiltCard({ children, className = '' }) {
  const prefersReducedMotion = useReducedMotion() === true;
  const x = useMotionValue(0); const y = useMotionValue(0);
  const rotateX = useSpring(useTransform(y, [-.5, .5], [7, -7]), { stiffness: 150, damping: 18 });
  const rotateY = useSpring(useTransform(x, [-.5, .5], [-7, 7]), { stiffness: 150, damping: 18 });
  return h(Motion.article, { className: `rp-card ${className}`, style: prefersReducedMotion ? {} : { rotateX, rotateY }, onPointerMove: prefersReducedMotion ? undefined : e => { const r = e.currentTarget.getBoundingClientRect(); x.set((e.clientX - r.left) / r.width - .5); y.set((e.clientY - r.top) / r.height - .5); }, onPointerLeave: prefersReducedMotion ? undefined : () => { x.set(0); y.set(0); }, whileHover: prefersReducedMotion ? undefined : { y: -5 }, transition: { type: 'spring', stiffness: 220, damping: 20 } }, children);
}

function Navigation({ data }) {
  const links = [{ id: 'about', label: 'About' }, ...(data.sections || []).map(section => ({ id: section.key, label: section.label })), data.showProjects ? { id: 'projects', label: 'Work' } : null, data.showContact ? { id: 'contact', label: 'Contact' } : null].filter(Boolean);
  return h('nav', { className: 'rp-nav', 'aria-label': 'Portfolio navigation' }, h('a', { href: '#home', className: 'rp-wordmark' }, data.initials), h('div', { className: 'rp-nav-links' }, links.map(link => h('a', { href: `#${link.id}`, key: link.id }, link.label))));
}

function Section({ id, eyebrow, title, children }) {
  const prefersReducedMotion = useReducedMotion() === true;
  return h(Motion.section, { id, className: 'rp-section', initial: prefersReducedMotion ? false : { opacity: 0, y: 24 }, whileInView: prefersReducedMotion ? undefined : { opacity: 1, y: 0 }, viewport: { once: true, amount: .15 }, transition: { duration: prefersReducedMotion ? 0 : .55, ease: 'easeOut' } }, h('div', { className: 'rp-wrap' }, h('p', { className: 'rp-eyebrow' }, eyebrow), h('h2', null, title), children));
}

function App({ data }) {
  useEffect(() => {
    // Hide the PHP fallback only after React has mounted successfully.
    document.body.classList.add('react-portfolio-ready');
    return () => document.body.classList.remove('react-portfolio-ready');
  }, []);
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
    createRoot(mount).render(h(App, { data }));
  } catch (error) { console.warn('React portfolio could not start; showing the standard portfolio instead.', error); }
}
