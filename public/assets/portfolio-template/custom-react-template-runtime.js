/*
 * Runtime for admin-authored React templates. It only accepts data from the
 * embedding portfolio page and is served inside a sandboxed, opaque-origin
 * iframe. The frame's CSP blocks network connections and external scripts.
 */
(() => {
  const rootElement = document.getElementById('react-custom-template-root');
  const styleElement = document.getElementById('react-custom-template-styles');
  const root = window.ReactDOM && typeof window.ReactDOM.createRoot === 'function'
    ? window.ReactDOM.createRoot(rootElement)
    : null;
  let acceptsMessages = false;

  const reportHeight = () => {
    const height = Math.max(
      document.documentElement.scrollHeight,
      document.body ? document.body.scrollHeight : 0,
      200
    );
    window.parent.postMessage({ type: 'instaweb-react-template-height', height }, '*');
  };

  const showError = message => {
    rootElement.dataset.state = 'error';
    rootElement.textContent = `React template could not render: ${message}`;
    reportHeight();
  };

  const renderTemplate = payload => {
    if (!window.React || !window.ReactDOM || !window.Babel || !root) {
      showError('the local React runtime is unavailable.');
      return;
    }
    if (!payload || typeof payload.code !== 'string' || typeof payload.css !== 'string' || !payload.data) {
      showError('template data is incomplete.');
      return;
    }
    acceptsMessages = payload.data.contactAvailable === true;

    try {
      const compiled = window.Babel.transform(payload.code, {
        filename: 'portfolio-template.jsx',
        presets: [['react', { runtime: 'classic' }]],
        sourceType: 'script',
      }).code;
      const componentFactory = new Function('React', 'data', `"use strict";\n${compiled}\nreturn typeof Portfolio === "function" ? Portfolio : null;`);
      const Portfolio = componentFactory(window.React, payload.data);
      if (typeof Portfolio !== 'function') {
        throw new Error('define a function or variable named Portfolio.');
      }

      styleElement.textContent = payload.css;
      rootElement.removeAttribute('data-state');
      root.render(window.React.createElement(Portfolio, { data: payload.data }));
      requestAnimationFrame(() => requestAnimationFrame(reportHeight));
    } catch (error) {
      showError(error instanceof Error ? error.message : 'unknown compilation error.');
    }
  };

  window.addEventListener('message', event => {
    if (event.source !== window.parent || !event.data || event.data.type !== 'instaweb-react-template') return;
    renderTemplate(event.data.payload);
  });

  document.addEventListener('click', event => {
    const target = event.target instanceof Element
      ? event.target.closest('[data-portfolio-contact], a[href^="mailto:"]')
      : null;
    if (!acceptsMessages || !target) return;
    event.preventDefault();
    window.parent.postMessage({ type: 'instaweb-react-template-contact' }, '*');
  }, true);

  if ('ResizeObserver' in window) {
    new ResizeObserver(reportHeight).observe(document.documentElement);
  }
  window.parent.postMessage({ type: 'instaweb-react-template-ready' }, '*');
  reportHeight();
})();
