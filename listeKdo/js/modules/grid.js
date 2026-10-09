import { $ } from './core.js';

/* ---------- Grille « masonry » ---------- */

/*
 * Chaque vignette occupe un nombre de rangées de 4 px proportionnel à sa hauteur réelle :
 * pas d'espace vide sous les vignettes courtes, et l'ordre reste de gauche à droite.
 */
const grid = $('[data-grid]');
let layoutPending = false;

// Dernière position connue de chaque vignette (dans la grille), pour animer les déplacements.
const gridPositions = new WeakMap();
let gridAnimate = true;

/*
 * animate = false : pas d'animation (premier affichage, redimensionnement de la fenêtre).
 * Sinon, chaque vignette déplacée glisse depuis son ancienne place (technique FLIP),
 * et celles qui réapparaissent (filtre) arrivent en fondu.
 */
function layoutGrid(animate = true) {
    if (!grid) return;
    if (animate !== true) gridAnimate = false;
    if (layoutPending) return;
    layoutPending = true;
    requestAnimationFrame(() => {
        layoutPending = false;
        const smooth = gridAnimate && grid.classList.contains('is-masonry') && !matchMedia('(prefers-reduced-motion: reduce)').matches;
        gridAnimate = true;
        grid.classList.add('is-masonry');
        const gap = 24;
        const items = [...grid.children].filter((item) => !item.hidden);
        // offsetHeight / offsetTop ignorent les transformations : une animation en cours ne fausse pas la mise en page.
        for (const item of items) {
            item.style.gridRowEnd = `span ${Math.ceil((item.offsetHeight + gap) / 4)}`;
        }
        for (const item of items) {
            const before = gridPositions.get(item);
            const after = { x: item.offsetLeft, y: item.offsetTop };
            gridPositions.set(item, after);
            if (!smooth || item.classList.contains('is-new') || item.style.visibility === 'hidden') continue;
            if (!before) {
                item.animate([{ opacity: 0, transform: 'scale(.92)' }, { opacity: 1, transform: 'none' }], { duration: 350, easing: 'ease-out' });
            } else if (Math.abs(before.x - after.x) > 1 || Math.abs(before.y - after.y) > 1) {
                item.animate(
                    [{ transform: `translate(${before.x - after.x}px, ${before.y - after.y}px)` }, { transform: 'none' }],
                    { duration: 450, easing: 'cubic-bezier(.2, .8, .2, 1)' },
                );
            }
        }
        // Vignettes masquées : on oublie leur place, elles réapparaîtront en fondu.
        [...grid.children].filter((item) => item.hidden).forEach((item) => gridPositions.delete(item));
    });
}

// Recalcule quand une vignette change de taille (image chargée, réservation, filtre…).
const gridObserver = grid ? new ResizeObserver(() => layoutGrid()) : null;

export { grid, gridObserver, layoutGrid };

export function initGrid() {
    if (grid) {
        [...grid.children].forEach((item) => gridObserver.observe(item));
        window.addEventListener('resize', () => layoutGrid(false));
        layoutGrid(false);
    }
}
