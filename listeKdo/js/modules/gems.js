import { $, $$ } from './core.js';

/* ---------- Gains et dépenses de gemmes : la pastille s'anime ---------- */

const gemPill = $('.gem-pill');
let gemsAnimation = null;

function showGems(from, to) {
    if (!gemPill) return;
    cancelAnimationFrame(gemsAnimation);
    const value = $('.gem-pill__value', gemPill);
    const counters = [value, ...$$('.user-menu__count--gems, [data-gems-balance]')];
    $$('[data-gems-unit]').forEach((el) => { el.textContent = to > 1 ? 'gemmes' : 'gemme'; });
    gemPill.dataset.gems = to;
    gemPill.setAttribute('aria-label', `Boutique : ${to} gemmes`);
    if (to === from || matchMedia('(prefers-reduced-motion: reduce)').matches) {
        counters.forEach((el) => { el.textContent = to; });
        return;
    }

    // Le solde défile jusqu'à sa nouvelle valeur.
    const start = performance.now();
    const tick = (now) => {
        const t = Math.min(1, (now - start) / 900);
        const current = Math.round(from + (to - from) * (1 - (1 - t) ** 3));
        counters.forEach((el) => { el.textContent = current; });
        if (t < 1) gemsAnimation = requestAnimationFrame(tick);
    };
    gemsAnimation = requestAnimationFrame(tick);

    const losing = to < from;
    const change = to - from;
    const animationClass = losing ? 'is-losing' : 'is-gaining';
    gemPill.classList.remove('is-gaining', 'is-losing');
    void gemPill.offsetWidth;
    gemPill.classList.add(animationClass);
    if (!losing) playGemSound();

    // L'animation se joue dans la couche supérieure du navigateur (popover), au-dessus d'une fenêtre ouverte
    // et de son fond flouté. Si une fenêtre est ouverte, une copie nette de la pastille s'affiche aussi.
    const layer = document.createElement('div');
    layer.className = losing ? 'gem-layer gem-layer--loss' : 'gem-layer';
    layer.setAttribute('popover', 'manual');
    layer.setAttribute('aria-hidden', 'true');
    document.body.append(layer);
    try {
        layer.showPopover();
    } catch {
        // Navigateur sans popover : l'animation reste sous la fenêtre éventuelle.
    }

    const box = gemPill.getBoundingClientRect();
    const gemSrc = $('img.gem', gemPill)?.src;
    if ($('dialog[open]')) {
        const copy = gemPill.cloneNode(true);
        copy.removeAttribute('data-open');
        copy.classList.add('gem-pill--copy', animationClass);
        Object.assign(copy.style, { left: `${box.left}px`, top: `${box.top}px`, width: `${box.width}px`, height: `${box.height}px` });
        layer.append(copy);
        counters.push($('.gem-pill__value', copy));
        copy.animate([{ opacity: 0 }, { opacity: 1, offset: .1 }, { opacity: 1, offset: .85 }, { opacity: 0 }], { duration: 2000, fill: 'forwards' });
    }

    // Le montant s'envole ; en cas de baisse, les gemmes quittent la pastille et s'effacent.
    const label = document.createElement('span');
    label.className = losing ? 'gem-gain gem-loss' : 'gem-gain';
    label.innerHTML = `${losing ? '−' : '+'}${Math.abs(change)} <img src="${gemSrc}" alt="">`;
    label.style.left = `${box.left + box.width / 2}px`;
    label.style.top = `${box.bottom + 6}px`;
    layer.append(label);
    label.animate(
        [{ transform: 'translate(-50%, 0) scale(.6)', opacity: 0 }, { transform: 'translate(-50%, 8px) scale(1.1)', opacity: 1, offset: .25 }, { transform: 'translate(-50%, 40px) scale(1)', opacity: 0 }],
        { duration: 1600, easing: 'ease-out', fill: 'forwards' },
    );

    for (let i = 0; i < 8; i++) {
        const gem = document.createElement('img');
        gem.src = gemSrc;
        gem.alt = '';
        gem.className = losing ? 'gem-spark gem-spark--leaving' : 'gem-spark';
        gem.style.left = `${box.left + box.width / 2}px`;
        gem.style.top = `${box.top + box.height / 2}px`;
        layer.append(gem);
        if (losing) {
            const drift = (i - 3.5) * 12 + (Math.random() - .5) * 14;
            gem.animate(
                [
                    { transform: 'translate(-50%, -50%) scale(.8)', opacity: 1 },
                    { transform: `translate(calc(-50% + ${drift}px), calc(-50% + 25px)) scale(.65) rotate(${drift}deg)`, opacity: 1, offset: .22 },
                    { transform: `translate(calc(-50% + ${drift * 1.6}px), calc(-50% + 130px)) scale(.12) rotate(${drift * 3}deg)`, opacity: 0 },
                ],
                { duration: 1150 + Math.random() * 300, delay: i * 55, easing: 'ease-in', fill: 'forwards' },
            );
            continue;
        }
        const angle = (Math.PI * 2 * i) / 8 + Math.random() * .5;
        const distance = 34 + Math.random() * 30;
        gem.animate(
            [
                { transform: 'translate(-50%, -50%) scale(.4) rotate(0deg)', opacity: 1 },
                { transform: `translate(calc(-50% + ${Math.cos(angle) * distance}px), calc(-50% + ${Math.sin(angle) * distance}px)) scale(1) rotate(${Math.random() * 180}deg)`, opacity: 1, offset: .6 },
                { transform: `translate(calc(-50% + ${Math.cos(angle) * distance * 1.3}px), calc(-50% + ${Math.sin(angle) * distance * 1.3 + 20}px)) scale(.5)`, opacity: 0 },
            ],
            { duration: 900 + Math.random() * 300, easing: 'cubic-bezier(.2, .7, .4, 1)', fill: 'forwards' },
        );
    }
    setTimeout(() => layer.remove(), 2200);
}

// Petit bruit de pièce, une seule fois par gain, à faible volume.
// Le navigateur peut le refuser tant que la page n'a reçu aucun clic : on l'ignore alors.
function playGemSound() {
    const src = gemPill?.dataset.gemsSound;
    if (!src) return;
    const sound = new Audio(src);
    sound.volume = 0.125;
    sound.play().catch(() => {});
}

// Après une action en arrière-plan : on redemande le solde.
let gemsCheck = null;
async function refreshGems() {
    if (!gemPill) return;
    clearTimeout(gemsCheck);
    gemsCheck = setTimeout(async () => {
        try {
            const response = await fetch('actions/gems.php', { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
            const data = await response.json();
            if (data.ok) showGems(Number(gemPill.dataset.gems), data.balance);
        } catch {
            // Sans réponse, la pastille sera à jour au prochain chargement.
        }
    }, 400);
}

export { refreshGems };

export function initGems() {
    // Au chargement : variation depuis la page précédente (pas sous les tests automatisés).
    if (gemPill && !navigator.webdriver) {
        const from = Number(gemPill.dataset.gemsFrom);
        const to = Number(gemPill.dataset.gems);
        if (to !== from) {
            $('.gem-pill__value', gemPill).textContent = from;
            setTimeout(() => {
                if (Number(gemPill.dataset.gems) === to) showGems(from, to);
            }, 700);
        }
    }

    // Un gain ou achat depuis un autre onglet est repris en revenant à la boutique.
    window.addEventListener('focus', () => {
        if ($('#shop-dialog')?.open) refreshGems();
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && $('#shop-dialog')?.open) refreshGems();
    });
}
