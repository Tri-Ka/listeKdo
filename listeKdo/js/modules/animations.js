import { $ } from './core.js';

/* Pastille ronde (corbeille, carton d'archives) qui apparaît en bas à droite le temps d'une animation. */
async function showDropZone(icon, modifier) {
    const sprite = $('svg use')?.getAttribute('href')?.split('#')[0] || 'img/icons.svg';
    const zone = document.createElement('div');
    zone.className = `trash-fly ${modifier}`;
    zone.innerHTML = `<svg class="icon" aria-hidden="true"><use href="${sprite}#i-${icon}"></use></svg>`;
    document.body.append(zone);
    await zone.animate(
        [{ transform: 'translateY(140%) scale(.4)', opacity: 0 }, { transform: 'none', opacity: 1 }],
        { duration: 280, easing: 'cubic-bezier(.2, .9, .3, 1.3)', fill: 'forwards' },
    ).finished;
    return zone;
}

async function hideDropZone(zone) {
    await zone.animate(
        [{ transform: 'none', opacity: 1 }, { transform: 'translateY(140%) scale(.4)', opacity: 0 }],
        { duration: 300, delay: 100, easing: 'ease-in', fill: 'forwards' },
    ).finished;
    zone.remove();
}

/* Copie de la vignette, posée par-dessus, que l'on peut animer librement (la vraie est cachée). */
function cardGhost(card) {
    const from = card.getBoundingClientRect();
    const ghost = card.cloneNode(true);
    ghost.removeAttribute('id');
    ghost.classList.add('trash-fly__ghost');
    Object.assign(ghost.style, {
        visibility: 'visible', left: `${from.left}px`, top: `${from.top}px`, width: `${from.width}px`, height: `${from.height}px`,
    });
    document.body.append(ghost);
    return { ghost, from };
}

/*
 * « J'ai reçu » : la vignette se réduit en fiche, glisse jusqu'au carton d'archives et y est rangée,
 * le carton se tasse puis s'en va.
 */
async function fileInArchive(card) {
    card.style.visibility = 'hidden';
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const box = await showDropZone('box-archive', 'trash-fly--archive');
    const { ghost, from } = cardGhost(card);
    const to = box.getBoundingClientRect();
    const dx = to.left + to.width / 2 - (from.left + from.width / 2);
    const dy = to.top + to.height / 2 - (from.top + from.height / 2);
    const size = Math.min(1, 64 / from.width);

    // On soulève la fiche, elle file au-dessus du carton, puis descend dedans (en passant derrière lui).
    await ghost.animate(
        [
            { transform: 'none', opacity: 1 },
            { transform: 'translateY(-18px) scale(1.03) rotate(-2deg)', opacity: 1, offset: .15 },
            { transform: `translate(${dx}px, ${dy - to.height * 1.1}px) scale(${size}) rotate(0deg)`, opacity: 1, offset: .75 },
            { transform: `translate(${dx}px, ${dy}px) scale(${size * .8})`, opacity: 0 },
        ],
        { duration: 900, easing: 'cubic-bezier(.45, 0, .25, 1)', fill: 'forwards' },
    ).finished;
    ghost.remove();

    // Le carton se tasse (couvercle refermé), puis repart.
    await box.animate(
        [{ transform: 'none' }, { transform: 'scale(1.12, .82)' }, { transform: 'scale(.96, 1.06)' }, { transform: 'none' }],
        { duration: 420, easing: 'ease-out' },
    ).finished;
    await hideDropZone(box);
}

/* Confettis aux couleurs du thème, lancés depuis le centre d'une vignette. */
function burstConfetti(element) {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const box = element.getBoundingClientRect();
    const style = getComputedStyle(document.body);
    const colors = ['--brand', '--confetti-1', '--confetti-2', '--confetti-3'].map((name) => style.getPropertyValue(name).trim() || '#f5a524');

    for (let i = 0; i < 28; i++) {
        const piece = document.createElement('span');
        piece.className = 'confetti-piece';
        piece.style.background = colors[i % colors.length];
        piece.style.left = `${box.left + box.width / 2}px`;
        piece.style.top = `${box.top + box.height / 3}px`;
        if (i % 3 === 0) piece.style.borderRadius = '50%';
        document.body.append(piece);

        const angle = (Math.PI * 2 * i) / 28 + Math.random() * .4;
        const distance = 90 + Math.random() * 110;
        const x = Math.cos(angle) * distance;
        const y = Math.sin(angle) * distance - 60;
        piece.animate(
            [
                { transform: 'translate(-50%, -50%) rotate(0deg) scale(1)', opacity: 1 },
                { transform: `translate(calc(-50% + ${x}px), calc(-50% + ${y}px)) rotate(${Math.random() * 540}deg) scale(1)`, opacity: 1, offset: .55 },
                { transform: `translate(calc(-50% + ${x * 1.15}px), calc(-50% + ${y + 140}px)) rotate(${Math.random() * 720}deg) scale(.6)`, opacity: 0 },
            ],
            { duration: 1100 + Math.random() * 400, easing: 'cubic-bezier(.2, .7, .4, 1)', fill: 'forwards' },
        ).finished.then(() => piece.remove());
    }
}

/* Mot de bande dessinée (« Cling ! ») qui jaillit à un endroit de l'écran puis s'efface. */
function onomatopoeia(text, x, y) {
    const word = document.createElement('span');
    word.className = 'onomatopoeia';
    word.textContent = text;
    word.style.left = `${x}px`;
    word.style.top = `${y}px`;
    document.body.append(word);
    word.animate(
        [
            { transform: 'translate(-50%, -50%) scale(.2) rotate(-25deg)', opacity: 0 },
            { transform: 'translate(-50%, -50%) scale(1.25) rotate(-8deg)', opacity: 1, offset: .25 },
            { transform: 'translate(-50%, -50%) scale(1) rotate(-12deg)', opacity: 1, offset: .7 },
            { transform: 'translate(-50%, -80%) scale(.9) rotate(-12deg)', opacity: 0 },
        ],
        { duration: 900, easing: 'ease-out', fill: 'forwards' },
    ).finished.then(() => word.remove());
}

/* Contour bosselé d'une boule de papier. */
const PAPER_BALL = '46% 54% 42% 58% / 55% 45% 57% 43%';

/*
 * Effet « chiffonné » : un filtre SVG (bruit + déplacement) déforme la vignette de plus en plus fort,
 * des plis apparaissent (.is-crumpled), et elle se ramasse en boule irrégulière de taille `size`.
 */
async function crumple(element, size) {
    let filter = document.getElementById('crumple-filter');
    if (!filter) {
        document.body.insertAdjacentHTML('beforeend', `<svg width="0" height="0" style="position:absolute" aria-hidden="true">
            <filter id="crumple-filter" x="-20%" y="-20%" width="140%" height="140%">
                <feTurbulence type="fractalNoise" baseFrequency="0.03" numOctaves="2" seed="7"/>
                <feDisplacementMap in="SourceGraphic" scale="0" xChannelSelector="R" yChannelSelector="G"/>
            </filter></svg>`);
        filter = document.getElementById('crumple-filter');
    }
    const displacement = filter.querySelector('feDisplacementMap');
    element.style.filter = 'url(#crumple-filter)';
    element.classList.add('is-crumpled');

    const duration = 520;
    const start = performance.now();
    const distort = (now) => {
        const t = Math.min(1, (now - start) / duration);
        displacement.setAttribute('scale', String(70 * t * t));
        if (t < 1) requestAnimationFrame(distort);
    };
    requestAnimationFrame(distort);

    await element.animate(
        [
            { transform: 'none', borderRadius: '22px' },
            { transform: `rotate(6deg) skew(6deg, -4deg) scale(${.55 + size * .3}, ${.5 + size * .25})`, borderRadius: '30% 40% 35% 45%', offset: .35 },
            { transform: `rotate(-10deg) skew(-8deg, 5deg) scale(${size * 1.6}, ${size * 1.3})`, borderRadius: '40% 50% 45% 55%', offset: .7 },
            { transform: `translateY(14px) rotate(-4deg) scale(${size})`, borderRadius: PAPER_BALL },
        ],
        { duration, easing: 'cubic-bezier(.3, 0, .3, 1)', fill: 'forwards' },
    ).finished;
}

/*
 * Animation de suppression : une corbeille apparaît en bas à droite, une copie de la vignette s'y
 * envole comme un lancer de basket (balle, parabole, rebond sur le cercle), la corbeille tremblote puis s'en va. La vignette d'origine reste
 * cachée (visibility) jusqu'à la réponse du serveur, pour pouvoir la réafficher en cas d'erreur.
 */
async function throwInTrash(card) {
    card.style.visibility = 'hidden';
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const trash = await showDropZone('trash-can', 'trash-fly--trash');
    const { ghost, from } = cardGhost(card);

    // Lancer de basket : la vignette se ramasse en balle, part en cloche (parabole) en tournant, puis touche le cercle et rentre.
    const to = trash.getBoundingClientRect();
    const dx = to.left + to.width / 2 - (from.left + from.width / 2);
    const dy = to.top + to.height / 2 - (from.top + from.height / 2);
    const ball = Math.min(1, 56 / Math.min(from.width, from.height));

    // Préparation du tir : la feuille est chiffonnée (déformation SVG de plus en plus forte, plis), puis roulée en boule.
    await crumple(ghost, ball);

    // Parabole : x avance régulièrement, y suit une cloche bien plus haute que le départ et l'arrivée.
    const rimY = dy - to.height * .55;
    const height = Math.max(220, Math.abs(rimY) * .6 + 160);
    const arc = [];
    for (let i = 0; i <= 24; i++) {
        const t = i / 24;
        const x = dx * t;
        const y = 14 + (rimY - 14) * t - 4 * height * t * (1 - t);
        arc.push({ transform: `translate(${x}px, ${y}px) rotate(${-720 * t}deg) scale(${ball * (1 - .35 * t)})`, borderRadius: PAPER_BALL });
    }
    await ghost.animate(arc, { duration: 800, easing: 'linear', fill: 'forwards' }).finished;

    // Le cercle : « Cling ! », petit rebond, puis la balle tombe dans la corbeille.
    onomatopoeia('Cling !', to.left + to.width / 2 - 30, to.top - 34);
    const end = ball * .65;
    await ghost.animate(
        [
            { transform: `translate(${dx}px, ${rimY}px) rotate(-720deg) scale(${end})`, borderRadius: PAPER_BALL, opacity: 1 },
            { transform: `translate(${dx + 10}px, ${rimY - 22}px) rotate(-780deg) scale(${end})`, borderRadius: PAPER_BALL, opacity: 1, offset: .4 },
            { transform: `translate(${dx}px, ${dy}px) rotate(-840deg) scale(${end * .3})`, borderRadius: PAPER_BALL, opacity: 0 },
        ],
        { duration: 380, easing: 'ease-in', fill: 'forwards' },
    ).finished;
    ghost.remove();

    // La corbeille avale la vignette puis tremblote, avant de redescendre.
    await trash.animate(
        [
            { transform: 'none' },
            { transform: 'scale(1.25, .8)' },
            { transform: 'rotate(-14deg) scale(1.05)' },
            { transform: 'rotate(12deg)' },
            { transform: 'rotate(-9deg)' },
            { transform: 'rotate(7deg)' },
            { transform: 'rotate(-4deg)' },
            { transform: 'rotate(2deg)' },
            { transform: 'none' },
        ],
        { duration: 650, easing: 'ease-out' },
    ).finished;
    await hideDropZone(trash);
}

export { fileInArchive, burstConfetti, throwInTrash };
