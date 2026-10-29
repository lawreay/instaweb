(function () {
  'use strict';
  function init() {
    if (document.querySelector('[data-iw-feedback-widget]')) return;
    var baseMeta = document.querySelector('meta[name="instaweb-base-url"]');
    var csrfMeta = document.querySelector('meta[name="instaweb-csrf"]');
    if (!baseMeta || !csrfMeta) return;

    var baseUrl = baseMeta.content.replace(/\/$/, '');
    var csrfToken = csrfMeta.content;
    var launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'iw-feedback-launcher';
    launcher.setAttribute('data-iw-feedback-widget', '');
    launcher.setAttribute('aria-haspopup', 'dialog');
    launcher.setAttribute('aria-expanded', 'false');
    launcher.innerHTML = '<i data-lucide="message-square-warning" aria-hidden="true"></i><span>Report problem / Request feature</span>';

    var overlay = document.createElement('div');
    overlay.className = 'iw-feedback-overlay';
    overlay.hidden = true;
    overlay.innerHTML =
      '<section class="iw-feedback-dialog" role="dialog" aria-modal="true" aria-labelledby="iwFeedbackTitle">' +
        '<header class="iw-feedback-head"><div><p class="iw-feedback-eyebrow">Help us improve</p><h2 id="iwFeedbackTitle">Send feedback</h2><p>Report something broken or suggest what we should build next.</p></div><button type="button" class="iw-feedback-close" aria-label="Close feedback form"><i data-lucide="x"></i></button></header>' +
        '<form class="iw-feedback-form" id="iwFeedbackForm">' +
          '<div class="iw-feedback-types" role="radiogroup" aria-label="Feedback type">' +
            '<label class="iw-feedback-type"><input type="radio" name="type" value="problem" checked><span><strong>Report a problem</strong><small>Something is broken</small></span></label>' +
            '<label class="iw-feedback-type"><input type="radio" name="type" value="feature"><span><strong>Request a feature</strong><small>Suggest an improvement</small></span></label>' +
          '</div>' +
          '<div class="iw-feedback-field"><label for="iwFeedbackName">Your name</label><input id="iwFeedbackName" name="name" maxlength="150" autocomplete="name" placeholder="Name (optional)"></div>' +
          '<div class="iw-feedback-field"><label for="iwFeedbackEmail">Email for follow-up</label><input id="iwFeedbackEmail" name="email" type="email" maxlength="150" autocomplete="email" placeholder="you@example.com (optional)"></div>' +
          '<div class="iw-feedback-field"><label for="iwFeedbackSubject">Short title</label><input id="iwFeedbackSubject" name="subject" required minlength="4" maxlength="150" placeholder="What is the issue or idea?"></div>' +
          '<div class="iw-feedback-field"><label for="iwFeedbackMessage">Details</label><textarea id="iwFeedbackMessage" name="message" required minlength="10" maxlength="5000" placeholder="Describe what happened, what you expected, or how the feature would help." rows="5"></textarea><span class="iw-feedback-hint">Please do not include passwords or other sensitive information.</span></div>' +
          '<div class="iw-feedback-trap" aria-hidden="true"><label>Leave this field empty<input name="website" tabindex="-1" autocomplete="off"></label></div>' +
          '<div class="iw-feedback-actions"><div class="iw-feedback-status" id="iwFeedbackStatus" role="status" aria-live="polite"></div><button class="iw-feedback-submit" type="submit">Send feedback <i data-lucide="arrow-up-right"></i></button></div>' +
        '</form>' +
      '</section>';

    document.body.appendChild(launcher);
    document.body.appendChild(overlay);
    var form = overlay.querySelector('#iwFeedbackForm');
    var status = overlay.querySelector('#iwFeedbackStatus');
    var submit = overlay.querySelector('.iw-feedback-submit');
    var close = overlay.querySelector('.iw-feedback-close');

    function closeDialog() {
      overlay.hidden = true;
      launcher.setAttribute('aria-expanded', 'false');
      launcher.focus();
    }
    launcher.addEventListener('click', function () {
      overlay.hidden = false;
      launcher.setAttribute('aria-expanded', 'true');
      overlay.querySelector('#iwFeedbackSubject').focus();
      if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
    });
    close.addEventListener('click', closeDialog);
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) closeDialog();
    });
    document.addEventListener('keydown', function (event) {
      if (overlay.hidden) return;
      if (event.key === 'Escape') closeDialog();
    });

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (!form.reportValidity()) return;
      submit.disabled = true;
      status.dataset.kind = '';
      status.textContent = 'Sending…';
      var data = Object.fromEntries(new FormData(form).entries());
      data._csrf = csrfToken;
      data.page_url = window.location.pathname;
      try {
        var response = await fetch(baseUrl + '/feedback/submit', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-Token':csrfToken},
          body: JSON.stringify(data)
        });
        var result = await response.json().catch(function () { return {}; });
        if (!response.ok || !result.success) throw new Error(result.error || 'Could not send feedback. Please try again.');
        status.dataset.kind = 'success';
        status.textContent = result.message || 'Feedback submitted successfully.';
        form.reset();
        window.setTimeout(function () {
          if (!overlay.hidden && status.dataset.kind === 'success') closeDialog();
        }, 3000);
      } catch (error) {
        status.dataset.kind = 'error';
        status.textContent = error.message || 'Could not send feedback. Please try again.';
      } finally {
        submit.disabled = false;
      }
    });
    if (window.lucide && window.lucide.createIcons) window.lucide.createIcons();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
}());
