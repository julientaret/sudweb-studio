/*
 * Pop-up de demande de devis (module Devis).
 *
 * Modale multi-étapes, sans dépendance. Dialogue en AJAX avec le CMS :
 *   - POST data-token-url  → jeton CSRF (à l'ouverture) ;
 *   - POST data-submit-url → envoi de la demande (JSON).
 *
 * Le markup est rendu côté serveur dans frontend/includes/footer.php. Ce module
 * ne fait rien si la modale est absente de la page.
 */

export function initDevis() {
  const modal = document.getElementById('devis-modal');
  if (!modal) return;

  const dialog = modal.querySelector('.devis-dialog');
  const form = modal.querySelector('.devis-form');
  const steps = Array.from(form.querySelectorAll('.devis-step'));
  const dots = Array.from(modal.querySelectorAll('.devis-dot'));
  const errorBox = form.querySelector('.devis-error');
  const successBox = modal.querySelector('.devis-success');
  const btnPrev = form.querySelector('.devis-prev');
  const btnNext = form.querySelector('.devis-next');
  const btnSubmit = form.querySelector('.devis-submit');

  const tokenUrl = modal.dataset.tokenUrl;
  const submitUrl = modal.dataset.submitUrl;

  let current = 0;
  let csrf = '';
  let tokenLoaded = false;
  let lastFocus = null;

  // --- Ouverture / fermeture ---------------------------------------------
  function open() {
    lastFocus = document.activeElement;
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    goTo(0);
    fetchToken();
    const first = steps[0].querySelector('select, input, textarea');
    if (first) first.focus();
  }

  function close() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
  }

  async function fetchToken() {
    if (tokenLoaded) return;
    try {
      const res = await fetch(tokenUrl, { method: 'POST', headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.token) {
        csrf = data.token;
        tokenLoaded = true;
      }
    } catch (e) {
      /* le jeton sera retenté au prochain envoi en échec */
    }
  }

  // --- Navigation entre étapes -------------------------------------------
  function goTo(index) {
    current = Math.max(0, Math.min(index, steps.length - 1));
    steps.forEach((s, i) => s.classList.toggle('is-active', i === current));
    dots.forEach((d, i) => d.classList.toggle('is-active', i <= current));
    btnPrev.hidden = current === 0;
    btnNext.hidden = current === steps.length - 1;
    btnSubmit.hidden = current !== steps.length - 1;
    hideError();
    const focusable = steps[current].querySelector('select, input, textarea');
    if (focusable) focusable.focus();
  }

  function validateStep(index) {
    const errors = [];
    const fields = steps[index].querySelectorAll('input, select, textarea');
    fields.forEach((f) => {
      if (f.type === 'checkbox') {
        if (f.required && !f.checked) errors.push('Merci d’accepter l’utilisation de vos données.');
        return;
      }
      const val = (f.value || '').trim();
      const min = parseInt(f.getAttribute('minlength') || '0', 10);
      if (f.required && val === '') {
        errors.push(labelFor(f) + ' : champ obligatoire.');
      } else if (f.type === 'email' && val !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
        errors.push('Adresse e-mail invalide.');
      } else if (min > 0 && val !== '' && val.length < min) {
        errors.push(labelFor(f) + ' : au moins ' + min + ' caractères.');
      }
    });
    return errors;
  }

  function labelFor(field) {
    const lbl = field.closest('.devis-field')?.querySelector('label');
    if (!lbl) return 'Ce champ';
    return lbl.textContent.replace(/\*|\(facultatif\)/g, '').trim();
  }

  // --- Erreurs ------------------------------------------------------------
  function showErrors(list) {
    errorBox.innerHTML = list.length === 1
      ? escapeHtml(list[0])
      : '<ul>' + list.map((e) => '<li>' + escapeHtml(e) + '</li>').join('') + '</ul>';
    errorBox.hidden = false;
  }
  function hideError() { errorBox.hidden = true; errorBox.innerHTML = ''; }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => (
      { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
  }

  // --- Envoi --------------------------------------------------------------
  async function submit() {
    const errors = validateStep(current);
    if (errors.length) { showErrors(errors); return; }

    if (!tokenLoaded) await fetchToken();

    btnSubmit.disabled = true;
    btnSubmit.textContent = 'Envoi…';

    const fd = new FormData(form);
    fd.set('_csrf', csrf);

    try {
      const res = await fetch(submitUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.ok) {
        form.hidden = true;
        modal.querySelector('.devis-steps').hidden = true;
        successBox.hidden = false;
      } else {
        showErrors((data && data.errors) || ['Une erreur est survenue. Merci de réessayer.']);
        btnSubmit.disabled = false;
        btnSubmit.textContent = 'Envoyer la demande';
      }
    } catch (e) {
      showErrors(['Connexion impossible. Merci de réessayer dans un instant.']);
      btnSubmit.disabled = false;
      btnSubmit.textContent = 'Envoyer la demande';
    }
  }

  // --- Liaisons -----------------------------------------------------------
  document.querySelectorAll('[data-devis-open]').forEach((el) => {
    el.addEventListener('click', (e) => { e.preventDefault(); open(); });
  });
  modal.querySelectorAll('[data-devis-close]').forEach((el) => {
    el.addEventListener('click', (e) => { e.preventDefault(); close(); });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) close();
  });

  btnPrev.addEventListener('click', () => goTo(current - 1));
  btnNext.addEventListener('click', () => {
    const errors = validateStep(current);
    if (errors.length) { showErrors(errors); return; }
    goTo(current + 1);
  });
  form.addEventListener('submit', (e) => { e.preventDefault(); submit(); });
}
