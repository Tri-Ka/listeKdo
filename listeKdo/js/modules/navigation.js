import { openDialog } from './dialogs.js';
import { burstConfetti } from './animations.js';

export function initNavigation() {
    function openFromHash() {
        const [, kind, id] = location.hash.match(/^#(idea|card|new)-(\d+)$/) ?? [];
        const card = id && document.getElementById(`idea-${id}`);
        if (!card) return;

        card.scrollIntoView({ block: 'center' });

        if (kind === 'idea') {
            openDialog(document.getElementById(`object-${id}`));
        } else if (kind === 'new') {
            // Idée tout juste ajoutée : elle tombe à sa place, avec des confettis.
            card.classList.add('is-new');
            // Les vignettes ont déjà une animation d'entrée (« rise ») : on attend la fin de la nôtre.
            card.addEventListener('animationend', function done(event) {
                if (event.target !== card || event.animationName !== 'highlight') return;
                // Sans cela, l'animation d'entrée « rise » repartirait de zéro (clignotement).
                card.style.animation = 'none';
                card.classList.remove('is-new');
                card.removeEventListener('animationend', done);
            });
            setTimeout(() => burstConfetti(card), 380);
            history.replaceState(null, '', location.pathname + location.search);
        } else {
            // Après un ajout ou une modification : on met la carte en évidence.
            card.classList.add('is-highlighted');
            card.addEventListener('animationend', function done(event) {
                if (event.target !== card || event.animationName !== 'highlight') return;
                card.style.animation = 'none';
                card.classList.remove('is-highlighted');
                card.removeEventListener('animationend', done);
            });
            history.replaceState(null, '', location.pathname + location.search);
        }
    }

    window.addEventListener('hashchange', openFromHash);

    openFromHash();
}
