/*
 * Page Demande de devis : récapitulatif en direct et envoi.
 *
 * Sans dépendance. Le formulaire poste sur les routes du module Devis
 * (/devis/token puis /devis/envoyer), exactement comme la modale : validation,
 * anti-spam et notifications restent côté noyau.
 *
 * No-op sur les autres pages.
 */

export function initDevisPage() {
    const form = document.getElementById('devis-page-form');
    if (!form) return;

    const done = document.getElementById('devis-page-done');
    const errorBox = form.querySelector('.qerrors');
    const submitBtn = form.querySelector('button[type="submit"]');
    const tokenUrl = form.dataset.tokenUrl;
    const submitUrl = form.dataset.submitUrl;

    let csrf = '';

    collectChecks(form);
    summary(form);
    fetchToken();

    /*
     * Le jeton est demandé au chargement : il pose aussi, côté session,
     * l'horodatage dont le noyau se sert pour écarter les envois trop rapides
     * pour être humains.
     */
    async function fetchToken() {
        try {
            const res = await fetch(tokenUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'fetch' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            if (data && data.token) csrf = data.token;
        } catch { /* réessayé à l'envoi */ }
    }

    const showErrors = (messages) => {
        if (!errorBox) return;
        errorBox.innerHTML = messages.length > 1
            ? '<ul>' + messages.map((m) => `<li>${escape(m)}</li>`).join('') + '</ul>'
            : escape(messages[0] || '');
        errorBox.hidden = messages.length === 0;
        if (messages.length) errorBox.scrollIntoView({ block: 'center', behavior: 'smooth' });
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Validation native d'abord : champs requis, format d'e-mail.
        if (!form.reportValidity()) return;
        if (!csrf) await fetchToken();

        submitBtn.disabled = true;
        showErrors([]);

        const body = new FormData(form);
        body.set('_csrf', csrf);

        try {
            const res = await fetch(submitUrl, { method: 'POST', body, credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.ok) {
                form.hidden = true;
                if (done) {
                    done.hidden = false;
                    done.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
                return;
            }
            showErrors((data && data.errors) || ["L'envoi a échoué. Merci de réessayer."]);
            // Le jeton est consommé : on en redemande un pour la tentative suivante.
            csrf = '';
            fetchToken();
        } catch {
            showErrors(['Connexion impossible. Vérifiez votre réseau et réessayez.']);
        } finally {
            submitBtn.disabled = false;
        }
    });
}

/*
 * Cases multiples : le module ne stocke que des valeurs simples, la liste
 * cochée est donc recopiée dans un champ caché, séparée par des virgules.
 */
function collectChecks(form) {
    form.querySelectorAll('[data-checks-value]').forEach((hidden) => {
        const key = hidden.dataset.checksValue;
        const boxes = [...form.querySelectorAll(`[data-checks="${key}"]`)];
        const sync = () => {
            hidden.value = boxes.filter((b) => b.checked).map((b) => b.value).join(', ');
        };
        boxes.forEach((b) => b.addEventListener('change', sync));
        sync();
    });
}

/* ---------- récapitulatif ---------- */
function summary(form) {
    const outs = [...document.querySelectorAll('[data-out]')];
    if (!outs.length) return;

    const update = () => {
        outs.forEach((out) => {
            const key = out.dataset.out;

            // Les modules s'affichent en pastilles, pas en ligne de texte.
            if (out.classList.contains('qsum-mods')) {
                const boxes = [...form.querySelectorAll(`[data-checks="${key}"]`)].filter((b) => b.checked);
                out.innerHTML = boxes.length
                    ? boxes.map((b) => `<span class="qsum-chip">${escape(b.value)}</span>`).join('')
                    : '<span class="v empty">Aucune sélectionnée</span>';
                return;
            }

            const value = readValue(form, key);
            out.textContent = value || '—';
            out.classList.toggle('empty', value === '');
        });
    };

    form.addEventListener('input', update);
    form.addEventListener('change', update);
    update();
}

/** Valeur courante d'une question, quelle que soit la forme de sa commande. */
function readValue(form, key) {
    const checked = form.querySelector(`input[name="${key}"]:checked`);
    if (checked) return checked.value;

    const field = form.querySelector(`[name="${key}"]`);
    if (!field) return '';
    // Rien n'est coché : un groupe de radios ou une case seule n'ont donc pas
    // de valeur. Sans ce retour, on lirait celle de la première commande du
    // groupe et le récapitulatif annoncerait un choix que personne n'a fait.
    if (field.type === 'radio' || field.type === 'checkbox') return '';
    return (field.value || '').trim();
}

/** Échappe ce qui part en innerHTML : valeurs saisies, messages du serveur. */
function escape(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}
