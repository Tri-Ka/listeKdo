import { $, $$, post } from './core.js';
import { openDialog } from './dialogs.js';
import { toast } from './ui.js';

export function initAccount() {
    /* ---------- Question secrète et mot de passe oublié ---------- */

    // Fenêtres à ouvrir dès l'arrivée (ex. invitation à choisir sa question secrète).
    $$('dialog[data-autoopen]').forEach((dialog) => setTimeout(() => openDialog(dialog), 600));

    // « Écrire ma propre question » : affiche le champ libre.
    document.addEventListener('change', (event) => {
        const select = event.target.closest?.('[data-secret-select]');
        if (!select) return;
        const custom = select.closest('[data-secret-fields]').querySelector('[data-secret-custom]');
        custom.hidden = select.value !== '__custom';
        custom.querySelector('input').required = !custom.hidden && select.required;
        if (!custom.hidden) custom.querySelector('input').focus();
    });

    const forgot = $('[data-forgot]');

    if (forgot) {
        const step = $('[data-forgot-step]', forgot);
        const submit = $('[data-forgot-submit]', forgot);

        forgot.addEventListener('submit', async (event) => {
            event.preventDefault();
            submit.setAttribute('aria-busy', 'true');

            try {
                if (step.hidden) {
                    // Étape 1 : la question secrète du compte.
                    const body = new FormData();
                    body.append('nom', forgot.elements.nom.value);
                    const data = await post('actions/forgotQuestion.php', body);
                    $('[data-forgot-question]', forgot).textContent = data.question;
                    step.hidden = false;
                    $$('input', step).forEach((input) => { input.required = true; });
                    forgot.elements.nom.readOnly = true;
                    submit.textContent = 'Changer mon mot de passe';
                    forgot.elements.secret_answer.focus();
                } else {
                    // Étape 2 : bonne réponse -> nouveau mot de passe, connexion.
                    const data = await post(forgot.getAttribute('action'), new FormData(forgot));
                    location.href = data.redirect;
                }
            } catch (error) {
                toast(error.message, 'error');
            } finally {
                submit.removeAttribute('aria-busy');
            }
        });

        // Rouvrir la fenêtre repart de la première étape.
        forgot.closest('dialog').addEventListener('close', () => {
            step.hidden = true;
            $$('input', step).forEach((input) => { input.required = false; input.value = ''; });
            forgot.elements.nom.readOnly = false;
            submit.textContent = 'Continuer';
        });
    }

    // Parrainage (boutique) : copier le lien d'invitation.
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-referral-copy]');
        if (!button) return;
        const link = $('[data-referral-link]', button.closest('[data-referral]')).value;
        try {
            await navigator.clipboard.writeText(link);
        } catch {
            window.prompt('Copiez ce lien :', link);
        }
        toast('Lien de parrainage copié !', 'success');
    });

    // Inscription depuis un lien de parrainage (?parrain=CODE) : la fenêtre s'ouvre toute seule.
    if (new URLSearchParams(location.search).get('parrain') && $('#signup-dialog') && !$('.user-menu')) {
        openDialog($('#signup-dialog'));
    }
}
