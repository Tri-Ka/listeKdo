import { $, $$ } from './core.js';
import { refreshGems } from './gems.js';

export function openDialog(dialog) {
    if (!dialog || dialog.open) return;
    // Une fenêtre obligatoire (data-locked) reste au premier plan tant qu'elle n'est pas remplie.
    if ($('dialog[data-locked][open]')) return;
    $$('dialog[open]').forEach((other) => other.close());
    dialog.showModal();
    document.body.classList.add('is-locked');
    if (dialog.id === 'shop-dialog') refreshGems();
}

export function initDialogs() {
    document.addEventListener('close', (event) => {
        if (event.target instanceof HTMLDialogElement && !$('dialog[open]')) {
            document.body.classList.remove('is-locked');

            if (location.hash.startsWith('#idea-')) {
                history.replaceState(null, '', location.pathname + location.search);
            }
        }
    }, true);

    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-open]');
        if (opener) {
            event.preventDefault();
            const dialog = document.getElementById(opener.dataset.open);
            openDialog(dialog);
            // data-focus="nom" : place le curseur dans ce champ de la fenêtre.
            if (opener.dataset.focus) dialog?.querySelector(`[name="${opener.dataset.focus}"]`)?.focus();
            return;
        }

        if (event.target.closest('[data-close]')) {
            event.target.closest('dialog')?.close();
            return;
        }

        // Clic sur le fond sombre autour de la fenêtre : il faut aussi que l'appui ait commencé sur le fond.
        // Sinon, sélectionner du texte et relâcher la souris hors de la fenêtre la fermerait.
        if (event.target instanceof HTMLDialogElement && pressedOnBackdrop === event.target
            && outsideDialog(event.target, event) && !('locked' in event.target.dataset)) {
            event.target.close();
        }
    });

    // Fenêtre obligatoire (data-locked, ex. adresse e-mail manquante) : Échap ne la ferme pas.
    document.addEventListener('cancel', (event) => {
        if (event.target instanceof HTMLDialogElement && 'locked' in event.target.dataset) event.preventDefault();
    }, true);

    // Fenêtre dont le fond sombre a reçu l'appui (souris ou doigt), ou null.
    let pressedOnBackdrop = null;

    function outsideDialog(dialog, event) {
        const box = dialog.getBoundingClientRect();
        return event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom;
    }

    document.addEventListener('pointerdown', (event) => {
        pressedOnBackdrop = event.target instanceof HTMLDialogElement && outsideDialog(event.target, event) ? event.target : null;
    });

    /* ---------- Confirmations ---------- */

    /*
     * data-confirm="Message" : demande confirmation dans une fenêtre (templates/partials/confirm.php)
     * au lieu de confirm() du navigateur. Une fois confirmé, le clic est rejoué sur le même bouton.
     */
    const confirmDialog = $('#confirm-dialog');
    let confirmedTrigger = null;

    function askConfirm(trigger) {
        const data = trigger.dataset;
        $('[data-confirm-title]', confirmDialog).textContent = data.confirmTitle || 'Vous êtes sûr ?';
        $('[data-confirm-text]', confirmDialog).textContent = data.confirm;
        $('[data-confirm-ok]', confirmDialog).textContent = data.confirmOk || 'Confirmer';
        const icon = $('[data-confirm-icon]', confirmDialog);
        icon.setAttribute('href', icon.getAttribute('href').replace(/#.*$/, `#i-${data.confirmIcon || 'triangle-exclamation'}`));

        // Ouverte par-dessus la fenêtre en cours (sans la fermer, contrairement à openDialog()).
        confirmDialog.returnValue = '';
        confirmDialog.showModal();
        document.body.classList.add('is-locked');

        return new Promise((resolve) => {
            confirmDialog.addEventListener('close', () => resolve(confirmDialog.returnValue === 'ok'), { once: true });
        });
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-confirm]');
        if (!trigger || !confirmDialog) return;
        if (confirmedTrigger === trigger) {
            confirmedTrigger = null;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        askConfirm(trigger).then((ok) => {
            if (!ok) return;
            confirmedTrigger = trigger;
            trigger.click();
        });
    }, true);
}
