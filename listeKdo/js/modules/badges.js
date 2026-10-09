import { $, $$ } from './core.js';
import { openDialog } from './dialogs.js';
import { burstConfetti } from './animations.js';

export function initBadges() {
    document.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-badge-tab]');
        if (!tab) return;
        $$('[data-badge-tab]').forEach((other) => other.setAttribute('aria-pressed', String(other === tab)));
        $$('[data-badge-section]').forEach((section) => { section.hidden = section.dataset.badgeSection !== tab.dataset.badgeTab; });
    });

    /* ---------- Badges (templates/partials/badges.php) ---------- */

    // Nouveau badge : la fenêtre s'ouvre au chargement, avec des confettis.
    // (Pas dans les navigateurs pilotés par les tests, où elle masquerait les clics.)
    const badgeNew = $('[data-badge-new]');
    if (badgeNew && !navigator.webdriver) {
        const celebrate = () => {
            openDialog(badgeNew);
            burstConfetti($('.badge-new__medal', badgeNew) || badgeNew);
        };
        // Première visite : la visite guidée passe d'abord, les badges ensuite.
        if ($('#onboarding-tour[data-auto-start]')) document.addEventListener('kdo:tour-end', () => setTimeout(celebrate, 400), { once: true });
        else setTimeout(celebrate, 600);
    }

    // Lien « …#badges » (notification, liste d'un ami) : ouvre la vitrine, au chargement ou sans recharger la page.
    function openBadgesFromHash() {
        if (location.hash !== '#badges' || !$('#badges-dialog')) return;
        openDialog($('#badges-dialog'));
        history.replaceState(null, '', location.pathname + location.search);
    }
    openBadgesFromHash();
    window.addEventListener('hashchange', openBadgesFromHash);
}
