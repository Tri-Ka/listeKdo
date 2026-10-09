import { $, $$ } from './core.js';
import { openDialog } from './dialogs.js';

/* ---------- Messages ---------- */

const toasts = $('[data-toasts]');

function toast(message, type = 'info') {
    const element = document.createElement('div');
    element.className = `toast toast--${type}`;
    element.setAttribute('role', type === 'error' ? 'alert' : 'status');
    element.textContent = message;
    toasts.append(element);
    dismissLater(element);
}

function dismissLater(element, delay = 4500) {
    setTimeout(() => {
        element.classList.add('is-leaving');
        element.addEventListener('transitionend', () => element.remove(), { once: true });
    }, delay);
}

export { toast };

export function initUI() {
    $$('.toast', toasts).forEach((element) => dismissLater(element, 6000));

    /* ---------- Infobulles ---------- */

    /*
     * Une seule infobulle pour tous les éléments [data-tip], placée au-dessus de tout :
     * elle n'est jamais coupée par un conteneur qui défile (colonne d'amis, fenêtres…).
     */
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.setAttribute('role', 'tooltip');
    let tooltipTarget = null;

    function showTooltip(target) {
        const text = target.dataset.tip;
        if (!text) return;

        tooltipTarget = target;
        // Dans une fenêtre ouverte, l'infobulle doit être dans la fenêtre pour passer au premier plan.
        (target.closest('dialog[open]') || document.body).append(tooltip);
        tooltip.textContent = text;

        const box = target.getBoundingClientRect();
        const tip = tooltip.getBoundingClientRect();
        const beside = target.closest('.friends') && innerWidth > 700;
        let left = beside ? box.right + 10 : box.left + box.width / 2 - tip.width / 2;
        let top = beside ? box.top + box.height / 2 - tip.height / 2 : box.top - tip.height - 8;

        // En dessous : pour les réactions, dont le sélecteur s'ouvre au-dessus.
        if (target.dataset.tipPosition === 'bottom') top = Math.min(box.bottom + 8, innerHeight - tip.height - 4);
        if (top < 4) top = box.bottom + 8;
        left = Math.min(Math.max(4, left), innerWidth - tip.width - 4);

        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
        tooltip.classList.add('is-visible');
    }

    function hideTooltip() {
        tooltipTarget = null;
        tooltip.classList.remove('is-visible');
    }

    document.addEventListener('pointerover', (event) => {
        if (event.pointerType !== 'mouse') return;
        const target = event.target.closest('[data-tip]');
        if (target && target !== tooltipTarget) showTooltip(target);
    });

    document.addEventListener('pointerout', (event) => {
        if (tooltipTarget && !tooltipTarget.contains(event.relatedTarget)) hideTooltip();
    });

    document.addEventListener('focusin', (event) => {
        const target = event.target.closest?.('[data-tip]');
        if (target && event.target.matches(':focus-visible')) showTooltip(target);
    });

    document.addEventListener('focusout', hideTooltip);
    document.addEventListener('scroll', hideTooltip, true);
    document.addEventListener('click', hideTooltip);

    /* ---------- Compte à rebours ---------- */

    const countdown = $('[data-countdown]');

    if (countdown) {
        const [year, month, day] = countdown.dataset.countdown.split('-').map(Number);
        const target = new Date(year, month - 1, day); // minuit, heure du visiteur
        const values = Object.fromEntries($$('[data-unit]', countdown).map((element) => [element.dataset.unit, element]));
        const pad = (value) => String(value).padStart(2, '0');

        const tick = () => {
            // Décompte « calendaire » : jours entre demain et la date, plus le temps jusqu'à minuit ce soir.
            // (Une simple différence de millisecondes compte une heure de trop ou de moins quand
            // le passage à l'heure d'hiver ou d'été tombe entre aujourd'hui et la date.)
            const now = new Date();
            const midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
            const days = Math.round((Date.UTC(year, month - 1, day) - Date.UTC(midnight.getFullYear(), midnight.getMonth(), midnight.getDate())) / 86400000);
            const today = days < 0 || now.toDateString() === target.toDateString();
            countdown.classList.toggle('is-today', today);
            if (today) return;

            const left = Math.max(0, Math.min(86399, Math.floor((midnight - now) / 1000)));
            const next = { d: String(days), h: pad(Math.floor(left / 3600)), m: pad(Math.floor((left % 3600) / 60)), s: pad(left % 60) };
            for (const [unit, value] of Object.entries(next)) {
                const element = values[unit];
                if (element.textContent === value) continue;
                element.textContent = value;
                // Relance l'animation de bascule à chaque changement.
                element.classList.remove('is-ticking');
                void element.offsetWidth;
                element.classList.add('is-ticking');
            }
        };

        tick();
        setInterval(tick, 1000);
    }

    /* ---------- Menu « ⋯ » des vignettes ---------- */

    document.addEventListener('click', (event) => {
        $$('[data-card-menu][open]').forEach((menu) => {
            // Fermé au clic à l'extérieur, ou après avoir choisi une action.
            if (!menu.contains(event.target) || event.target.closest('.card-menu__panel button')) menu.open = false;
        });
    });

    /* ---------- Menu du compte ---------- */

    const userMenu = $('[data-user-menu]');

    if (userMenu) {
        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-onboarding]')) return; // la visite guidée ouvre et ferme le menu elle-même
            if (!userMenu.contains(event.target) || event.target.closest('[data-open]')) userMenu.open = false;
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !document.body.classList.contains('is-touring')) userMenu.open = false;
        });
    }

    // Image introuvable : on affiche l'image par défaut.
    function useFallback(image) {
        if (image.dataset.fallback && !image.src.endsWith(image.dataset.fallback)) image.src = image.dataset.fallback;
    }

    document.addEventListener('error', (event) => {
        if (event.target instanceof HTMLImageElement) useFallback(event.target);
    }, true);

    // Images déjà en erreur avant le chargement de ce script.
    $$('img[data-fallback]').forEach((image) => {
        if (image.complete && image.naturalWidth === 0 && image.getAttribute('loading') !== 'lazy') useFallback(image);
    });

    // Mobile : la carte de la liste (en haut à gauche) ouvre « Mes amis » au lieu de recharger la liste.
    document.addEventListener('click', (event) => {
        const brand = event.target.closest('[data-mobile-friends]');
        if (!brand || !matchMedia('(max-width: 700px)').matches || !$('#friends-dialog')) return;
        event.preventDefault();
        openDialog($('#friends-dialog'));
    });

    // Onglets de la vitrine des badges.

    /* ---------- Zones de texte ---------- */

    if (!CSS.supports('field-sizing', 'content')) {
        document.addEventListener('input', (event) => {
            const field = event.target;
            if (field.matches?.('textarea[data-autosize]')) {
                field.style.height = 'auto';
                field.style.height = `${field.scrollHeight + 4}px`;
            }
        });
    }
}
