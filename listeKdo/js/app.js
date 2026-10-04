/*
 * Liste de Kdo : interactions de la page.
 * JavaScript natif (module ES), sans dépendance ni étape de build.
 */

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const csrfToken = $('meta[name="csrf-token"]')?.content ?? '';

/* ---------- Appels serveur ---------- */

async function post(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        body,
        headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
        credentials: 'same-origin',
    });

    let data = null;
    try {
        data = await response.json();
    } catch {
        // Réponse non JSON (erreur PHP, coupure réseau…).
    }

    if (!response.ok || !data?.ok) {
        throw new Error(data?.message || 'Une erreur est survenue, réessayez.');
    }

    return data;
}

function html(markup) {
    const template = document.createElement('template');
    template.innerHTML = markup.trim();
    return template.content;
}

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

$$('.toast', toasts).forEach((element) => dismissLater(element, 6000));

/* ---------- Fenêtres (dialog) ---------- */

function openDialog(dialog) {
    if (!dialog || dialog.open) return;
    $$('dialog[open]').forEach((other) => other.close());
    dialog.showModal();
    document.body.classList.add('is-locked');
}

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
        && outsideDialog(event.target, event)) {
        event.target.close();
    }
});

// Fenêtre dont le fond sombre a reçu l'appui (souris ou doigt), ou null.
let pressedOnBackdrop = null;

function outsideDialog(dialog, event) {
    const box = dialog.getBoundingClientRect();
    return event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom;
}

document.addEventListener('pointerdown', (event) => {
    pressedOnBackdrop = event.target instanceof HTMLDialogElement && outsideDialog(event.target, event) ? event.target : null;
});

/* ---------- Visite guidée ---------- */
// Une fenêtre d'accueil, puis un projecteur sur chaque fonctionnalité (Précédent / Suivant, flèches du clavier).
// Chaque étape vise le premier élément visible de `targets` (sinon elle est sautée) ; `menu: true` ouvre
// d'abord le menu du compte (éléments rangés dedans, ou déplacés dedans sur mobile). Sans cible : fenêtre centrée.

const onboarding = $('#onboarding-tour');

if (onboarding) {
    const firstName = onboarding.dataset.tourName.split(' ')[0];
    const tourSteps = [
        { icon: 'gift', eyebrow: 'Bienvenue', title: `Bienvenue ${firstName} !`, text: 'Votre liste de cadeaux est prête. En une minute, faisons le tour de tout ce que vous pouvez y faire.', next: "C'est parti", prev: 'Plus tard' },
        { targets: ['.card--add'], icon: 'plus', eyebrow: 'Votre liste', title: 'Ajoutez vos idées', text: "Collez le lien d'un produit, de n'importe quelle boutique : le nom, l'image et le prix se remplissent tout seuls. Une idée peut aussi regrouper plusieurs éléments (une collection)." },
        { targets: ['.grid .card[data-object]'], icon: 'image', eyebrow: 'Vos idées', title: 'Chaque idée a sa vignette', text: 'Vos proches y voient la photo et le prix, réagissent et laissent des commentaires pour se mettre d’accord.' },
        { targets: ['.grid .card[data-object] .heart-btn'], icon: 'heart', eyebrow: 'Vos envies', title: 'Vos coups de cœur', text: 'Touchez le cœur sur les idées qui vous font le plus envie : vos proches les repèrent tout de suite.' },
        { targets: ['.grid .card[data-object] [data-card-menu] > summary'], icon: 'ellipsis', eyebrow: 'Vos idées', title: 'Modifier, marquer comme reçu', text: 'Ce menu sert à modifier une idée, à la supprimer, ou à indiquer que vous l’avez reçue : elle passe alors dans l’onglet « Reçus ».' },
        { targets: ['[data-tabs]', '.tabs-select'], icon: 'list', eyebrow: 'Vos idées', title: 'Filtrer et trier', text: 'Retrouvez vos coups de cœur ou vos cadeaux reçus. Dès qu’un prix est indiqué, on peut aussi filtrer par budget et trier par prix.' },
        { icon: 'eye', eyebrow: 'La surprise', title: 'Vous ne saurez pas qui offre quoi', text: 'Vos proches réservent les idées, seuls ou en se cotisant à plusieurs, sans que vous le voyiez. Eux voient ce qui est déjà pris : pas de doublon, et la surprise reste entière.' },
        { targets: ['.profile__avatar'], icon: 'user', eyebrow: 'Votre profil', title: 'Votre photo et votre profil', text: 'Changez votre photo, votre nom et votre mot de passe ici. Ils sont aussi dans le menu du compte.' },
        { targets: ['[data-countdown]', '.topbar__days'], icon: 'calendar-days', eyebrow: 'Le grand jour', title: 'Le compte à rebours', text: 'La date de votre événement s’affiche ici. Vos amis reçoivent un rappel à l’approche du jour J.' },
        { targets: ['.topbar__settings', { sel: '.user-menu__mobile [data-open="list-settings-dialog"]', menu: true }], icon: 'gear', eyebrow: 'À votre image', title: 'Les paramètres de la liste', text: 'Choisissez le type de liste (anniversaire, Noël, naissance, mariage…), son titre, la date de l’événement, et rendez-la privée si besoin, visible seulement par les proches que vous invitez.' },
        { targets: ['.topbar__share', { sel: '.user-menu__mobile [data-open="share-dialog"]', menu: true }], icon: 'share-nodes', eyebrow: 'À partager', title: 'Envoyez votre liste', text: 'Copiez le lien ou partagez-le sur WhatsApp : vos proches voient votre liste sans avoir besoin de compte.' },
        { targets: ['.friends', '.friends-shortcut', '[data-mobile-friends]'], icon: 'users', eyebrow: 'Vos proches', title: 'Les listes de vos amis', text: 'Retrouvez ici les listes de vos amis, triées par prochain événement. Pour en ajouter un, ouvrez sa liste et touchez « Ajouter à mes amis ».' },
        { targets: ['.friends', '.friends-shortcut', '[data-mobile-friends]'], icon: 'lightbulb', eyebrow: 'Vos proches', title: 'Suggérez-leur des idées', text: 'Sur la liste d’un ami, « Suggérer une idée » ajoute une idée qu’il ne verra jamais : seuls ses autres amis la voient, pour la lui offrir.' },
        { targets: ['.bell'], icon: 'bell', eyebrow: 'Ne rien rater', title: 'Les notifications', text: 'Nouvelles idées de vos amis, commentaires, réactions, rappels d’anniversaire : tout arrive ici.' },
        { targets: ['.profile__badges'], icon: 'crown', eyebrow: 'Récompenses', title: 'Vos badges', text: 'Ajouter des idées, offrir, commenter… chaque étape débloque des badges. Touchez la pastille pour voir les prochains.' },
        { targets: ['.gem-pill'], icon: 'palette', eyebrow: 'Récompenses', title: 'Gemmes et boutique', text: 'Badges et actions rapportent des gemmes. Dépensez-les dans la boutique : habillages pour votre liste, cadres pour votre photo, effets pour votre compte à rebours. Parrainez vos proches pour en gagner plus.' },
        { targets: [{ sel: '[data-user-menu] [data-open="child-new-dialog"]', menu: true }], icon: 'layer-group', eyebrow: 'Pour toute la famille', title: 'Les listes secondaires', text: 'Créez une liste pour un enfant ou un proche sans compte, et gérez-la à plusieurs. Ici, vous voyez ce qui est déjà offert pour éviter les doublons.' },
        { targets: [{ sel: '[data-user-menu] [data-open="my-gifts-dialog"]', menu: true }], icon: 'gift', eyebrow: 'Vos cadeaux', title: 'Les cadeaux que vous offrez', text: 'Tous les cadeaux que vous avez réservés pour les autres, rassemblés au même endroit.' },
        { targets: [{ sel: '[data-user-menu] [data-open="extension-dialog"]', menu: true }], icon: 'puzzle-piece', eyebrow: 'Encore plus rapide', title: 'L’extension Chrome', text: 'Ajoutez un produit à votre liste en un clic, depuis la page de la boutique.' },
        { targets: ['.note'], icon: 'pen', eyebrow: 'Un petit mot', title: 'Votre message', text: 'Ce mot s’affiche en bas de votre liste : remerciements, tailles, préférences… Modifiez-le avec le crayon.' },
        { icon: 'circle-check', eyebrow: 'C’est tout !', title: 'À vous de jouer', text: 'Commencez par ajouter une première idée. Vous retrouverez cette visite dans le menu du compte.', next: 'Ajouter une idée', prev: 'Terminer', final: true },
    ];
    const bubble = $('[data-tour-bubble]', onboarding);
    const spotlight = $('[data-tour-spotlight]', onboarding);
    const arrow = $('[data-tour-arrow]', onboarding);
    const prevButton = $('[data-tour-prev]', onboarding);
    const nextButton = $('[data-tour-next]', onboarding);
    const accountMenu = $('[data-user-menu]');
    const iconHref = $('svg use')?.getAttribute('href')?.replace(/#.*$/, '') ?? '';
    let steps = [];
    let index = 0;
    let target = null;
    let finished = true;
    let lastFocus = null;

    const visible = (element) => element && element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden';

    function setMenu(open) {
        if (accountMenu) accountMenu.open = open;
    }

    // Premier élément visible de l'étape (le menu reste ouvert s'il le faut). null : étape sans cible.
    function resolve(step) {
        for (const item of step.targets) {
            const { sel, menu } = typeof item === 'string' ? { sel: item, menu: false } : item;
            setMenu(menu);
            const element = $$(sel).find(visible);
            if (element) return element;
        }
        setMenu(false);
        return undefined;
    }

    function place() {
        if (finished) return;
        const gap = 14;
        const margin = 12;
        bubble.style.left = bubble.style.top = '';

        if (!target) {
            onboarding.classList.add('is-centered');
            return;
        }
        onboarding.classList.remove('is-centered');

        const box = target.getBoundingClientRect();
        const pad = 6;
        const radius = Math.min(parseFloat(getComputedStyle(target).borderTopLeftRadius) || 0, box.height / 2) + pad;
        Object.assign(spotlight.style, {
            left: `${box.left - pad}px`,
            top: `${box.top - pad}px`,
            width: `${box.width + pad * 2}px`,
            height: `${box.height + pad * 2}px`,
            borderRadius: `${radius}px`,
        });

        // Sous la cible s'il y a la place, sinon au-dessus, sinon par-dessus en bas de l'écran.
        const size = bubble.getBoundingClientRect();
        const below = box.bottom + pad + gap + size.height <= innerHeight - margin;
        const above = box.top - pad - gap - size.height >= margin;
        const left = Math.min(Math.max(margin, box.left + box.width / 2 - size.width / 2), innerWidth - size.width - margin);
        let top;
        if (below) top = box.bottom + pad + gap;
        else if (above) top = box.top - pad - gap - size.height;
        else top = innerHeight - size.height - margin;
        bubble.style.left = `${left}px`;
        bubble.style.top = `${top}px`;
        arrow.hidden = !below && !above;
        bubble.dataset.side = below ? 'below' : 'above';
        arrow.style.left = `${Math.min(Math.max(22, box.left + box.width / 2 - left), size.width - 22)}px`;
    }

    // Fait défiler pour que la cible et la bulle tiennent ensemble à l'écran : la cible juste sous la barre
    // du haut (collante), la bulle en dessous. Sur petit écran, une grande cible est montrée par son haut.
    function scrollToTarget() {
        if (target.closest('.topbar, .friends, [data-user-menu]')) return; // toujours à l'écran
        const box = target.getBoundingClientRect();
        const bubbleHeight = bubble.offsetHeight;
        const top = ($('.topbar')?.getBoundingClientRect().bottom ?? 0) + 16;
        const bottom = innerHeight - 12;
        const fits = (box.top >= top && box.bottom + 20 + bubbleHeight <= bottom) || (box.top - 20 - bubbleHeight >= top && box.bottom <= bottom);
        if (fits) return;
        const delta = box.top - top;
        // Long trajet (pied de page…) : direct, sinon la bulle arriverait avant la cible.
        const smooth = Math.abs(delta) < innerHeight && !matchMedia('(prefers-reduced-motion: reduce)').matches;
        scrollBy({ top: delta, behavior: smooth ? 'smooth' : 'instant' }); // « auto » suivrait le scroll-behavior: smooth du CSS
    }

    function render() {
        const step = steps[index];
        target = step.targets ? resolve(step) : null;
        if (!step.targets) setMenu(false);
        // Élément disparu depuis le début (idée supprimée, fenêtre de taille différente…) : étape suivante.
        if (undefined === target) return go(index + 1);

        $('[data-tour-icon]', onboarding).innerHTML = `<svg class="icon" aria-hidden="true"><use href="${iconHref}#i-${step.icon}"></use></svg>`;
        $('[data-tour-eyebrow]', onboarding).textContent = step.eyebrow;
        $('[data-tour-title]', onboarding).textContent = step.title;
        $('[data-tour-text]', onboarding).textContent = step.text;
        $('[data-tour-count]', onboarding).textContent = `${index + 1} / ${steps.length}`;
        $('[data-tour-progress]', onboarding).style.width = `${((index + 1) / steps.length) * 100}%`;
        nextButton.textContent = step.next ?? 'Suivant';
        prevButton.textContent = step.prev ?? 'Précédent';
        bubble.classList.toggle('tour__bubble--intro', !step.targets);

        // Relance l'animation d'apparition de la bulle.
        bubble.classList.remove('is-entering');
        void bubble.offsetWidth;
        bubble.classList.add('is-entering');

        if (target) scrollToTarget();
        place();
        bubble.focus({ preventScroll: true });
    }

    function go(next) {
        if (next < 0) return;
        if (next >= steps.length) return finish();
        index = next;
        render();
    }

    function finish(addIdea = false) {
        if (finished) return;
        finished = true;
        onboarding.hidden = true;
        document.body.classList.remove('is-touring');
        setMenu(false);
        document.dispatchEvent(new Event('kdo:tour-end')); // la fenêtre « nouveau badge » attendait la fin
        // L'écriture est volontairement discrète : un échec n'empêche pas de continuer.
        post('actions/onboardingSeen.php', new FormData()).catch(() => {});
        if (addIdea) $('.card--add')?.click();
        else lastFocus?.focus?.({ preventScroll: true });
    }

    function start() {
        if (!onboarding.hasAttribute('data-tour-here')) {
            // La visite présente sa propre liste : on y va d'abord.
            location.href = `${onboarding.dataset.tourHome}#visite`;
            return;
        }
        // Les étapes dont rien n'est à l'écran sont retirées dès le départ, pour un compteur juste.
        steps = tourSteps.filter((step) => !step.targets || resolve(step));
        setMenu(false);
        lastFocus = document.activeElement;
        finished = false;
        onboarding.hidden = false;
        document.body.classList.add('is-touring');
        go(0);
    }

    nextButton.addEventListener('click', () => {
        const step = steps[index];
        if (step.final) finish(true);
        else go(index + 1);
    });
    prevButton.addEventListener('click', () => {
        if (0 === index || steps[index].final) finish();
        else go(index - 1);
    });
    $$('[data-tour-skip]', onboarding).forEach((button) => button.addEventListener('click', () => finish()));

    document.addEventListener('keydown', (event) => {
        if (finished) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            finish();
        } else if (event.key === 'ArrowRight') {
            go(index + 1);
        } else if (event.key === 'ArrowLeft') {
            go(index - 1);
        } else if (event.key === 'Tab') {
            // Le focus reste dans la bulle.
            const focusable = $$('button', bubble).filter(visible);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === bubble)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }, true);

    $$('[data-start-onboarding]').forEach((button) => button.addEventListener('click', start));
    addEventListener('resize', place);
    addEventListener('scroll', place, true);

    // Démarrage automatique (première fois) ou demandé depuis une autre page (#visite) :
    // on attend qu'aucune fenêtre ne soit ouverte (badge obtenu, question secrète…).
    const startWhenIdle = () => {
        const open = $('dialog[open]');
        if (open) open.addEventListener('close', () => setTimeout(startWhenIdle, 300), { once: true });
        else start();
    };
    if ('#visite' === location.hash) {
        history.replaceState(null, '', location.pathname + location.search);
        setTimeout(startWhenIdle, 300);
    } else if (onboarding.hasAttribute('data-auto-start') && !navigator.webdriver) {
        setTimeout(startWhenIdle, 700);
    }
}

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

/* ---------- Formulaires envoyés en arrière-plan ---------- */

const ajaxHandlers = {
    gift(form, data) {
        const id = form.elements.id.value;
        $$(`[data-gift-slot="${id}"]`).forEach((slot) => slot.replaceChildren(html(data.html)));
        const card = document.getElementById(`idea-${id}`);
        if (card) {
            card.classList.toggle('card--gifted', data.gifted);
            card.dataset.gifted = data.gifted ? '1' : '0';
        }
        refreshFilters();
        form.closest('#gift-choice-dialog')?.close();
        toast(data.gifted ? 'Noté ! Ce Kdo est réservé pour vous.' : 'Ce Kdo est de nouveau disponible.', 'success');
    },

    favorite(form, data) {
        form.querySelector('.heart-btn')?.setAttribute('aria-pressed', data.favorite);
        const card = form.closest('.card');
        if (card) card.dataset.favorite = data.favorite ? '1' : '0';
        refreshFilters();
    },

    async received(form, data) {
        const card = form.closest('.card');
        form.closest('details')?.removeAttribute('open');
        // Rangée dans le carton d'archives quand elle quitte l'onglet affiché.
        if (card && data.received && currentFilter !== 'received' && !card.hidden) await fileInArchive(card);
        // Vignette et fiche refaites par le serveur : « Je l'offre », étiquettes et menu suivent l'état.
        if (card && data.card) {
            const fresh = html(data.card).firstElementChild;
            // Pas d'animation d'apparition : la vignette change sur place.
            fresh.style.animation = 'none';
            card.replaceWith(fresh);
            gridObserver?.observe(fresh);
        }
        const dialog = data.dialog && card ? document.getElementById(`object-${card.dataset.object}`) : null;
        if (dialog && !dialog.open) dialog.replaceWith(html(data.dialog));
        toast(data.message, 'success');
        refreshFilters();
    },

    participation(form, data) {
        $$(`[data-gift-slot="${data.id}"]`).forEach((slot) => slot.replaceChildren(html(data.gift)));
        $(`[data-group-slot="${data.id}"]`)?.replaceWith(html(data.group));
        const card = document.getElementById(`idea-${data.id}`);
        if (card) {
            card.classList.toggle('card--gifted', data.complete);
            card.dataset.gifted = data.complete ? '1' : '0';
        }
        toast(data.message, 'success');
        refreshFilters();
    },

    item(form, data) {
        $$(`[data-items-slot="${data.id}"]`).forEach((slot) => {
            slot.replaceWith(html(slot.dataset.itemsMode === 'card' ? data.card : data.detail));
        });
        $$(`[data-gift-slot="${data.id}"]`).forEach((slot) => slot.replaceChildren(html(data.gift)));
        const card = document.getElementById(`idea-${data.id}`);
        if (card) {
            card.classList.toggle('card--gifted', data.complete);
            card.dataset.gifted = data.complete ? '1' : '0';
        }
        refreshFilters();
    },

    reaction(form, data) {
        const id = form.elements.object.value;
        $(`[data-reactions-slot="${id}"]`)?.replaceChildren(html(data.summary));
        $(`[data-reactors-slot="${id}"]`)?.replaceChildren(html(data.detail));
    },

    comment(form, data) {
        const id = form.elements.productId.value;
        $(`[data-comments="${id}"]`)?.append(html(data.html));
        form.reset();
        updateCommentCount(id, data.count);
    },

    'delete-comment'(form, data) {
        const list = form.closest('[data-comments]');
        form.closest('.comment')?.remove();
        if (list) updateCommentCount(list.dataset.comments, data.count);
    },
};

function updateCommentCount(id, count) {
    $$(`[data-comment-count="${id}"]`).forEach((badge) => {
        badge.textContent = count;
        badge.hidden = count === 0;
    });
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    const handler = ajaxHandlers[form.dataset.ajax];
    if (!handler) return;

    event.preventDefault();
    if (form.dataset.busy) return;

    const button = event.submitter;
    form.dataset.busy = '1';
    button?.setAttribute('aria-busy', 'true');

    try {
        handler(form, await post(form.getAttribute('action'), new FormData(form, button)));
        refreshGems();
    } catch (error) {
        toast(error.message, 'error');
    } finally {
        delete form.dataset.busy;
        button?.removeAttribute('aria-busy');
    }
});

// Envoi par Entrée dans un commentaire (Maj+Entrée pour aller à la ligne).
document.addEventListener('keydown', (event) => {
    const field = event.target;
    if (event.key === 'Enter' && !event.shiftKey && field.matches?.('.comment-form textarea')) {
        event.preventDefault();
        if (field.value.trim()) field.form.requestSubmit();
    }
});

$$('form[data-autosubmit]').forEach((form) => {
    form.addEventListener('change', () => form.requestSubmit());
});

/* ---------- Réactions ---------- */

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-reaction-toggle]');
    $$('.reactions__picker.is-open').forEach((picker) => {
        if (!toggle || picker !== toggle.parentElement) picker.classList.remove('is-open');
    });
    toggle?.parentElement.classList.toggle('is-open');
});

/* ---------- Notifications ---------- */

const drawer = $('[data-notifications]');

function setUnreadCount(count) {
    const bell = $('.bell');
    let badge = $('[data-notification-badge]');
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'badge';
            badge.dataset.notificationBadge = '';
            bell?.append(badge);
        }
        badge.textContent = count > 9 ? '9+' : count;
    } else {
        badge?.remove();
    }
    $$('[data-unread-count]').forEach((element) => { element.textContent = count; });
    const markAll = $('[data-mark-all-read]');
    if (markAll) markAll.hidden = count === 0;
}

function setRead(item, read) {
    item.classList.toggle('notification--new', !read);
    const toggle = item.querySelector('[data-notification-toggle]');
    if (toggle) {
        const label = read ? 'Marquer comme non lue' : 'Marquer comme lue';
        toggle.setAttribute('aria-label', label);
        toggle.dataset.tip = label;
    }
}

if (drawer) {
    const states = drawer.dataset.states === '1';
    const list = $('[data-notification-list]', drawer);
    let filter = 'all';
    let seen = false;

    const toggleNotifications = (force) => {
        const open = force ?? !drawer.classList.contains('is-open');
        drawer.hidden = false;
        requestAnimationFrame(() => drawer.classList.toggle('is-open', open));
        $$('[data-toggle-notifications][aria-controls]').forEach((button) => button.setAttribute('aria-expanded', open));

        // Sans suivi lu / non lu (migration non faite) : ouvrir le panneau marque tout comme lu.
        if (open && !states && !seen && $('[data-notification-badge]')) {
            seen = true;
            post('actions/updateSeenNotif.php', new FormData()).then(() => setUnreadCount(0)).catch(() => { seen = false; });
        }
    };

    const loadNotifications = async (offset) => {
        const response = await fetch(`actions/notifications.php?filter=${filter}&offset=${offset}`, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!data.ok) throw new Error();
        return data;
    };

    const moreButton = (offset) => {
        const item = document.createElement('li');
        item.className = 'notifications__more';
        item.innerHTML = `<button type="button" class="btn btn--soft" data-more-notifications data-offset="${offset}">Voir plus</button>`;
        return item;
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-toggle-notifications]')) {
            toggleNotifications();
        } else if (drawer.classList.contains('is-open') && !drawer.contains(event.target)) {
            toggleNotifications(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && drawer.classList.contains('is-open')) toggleNotifications(false);
    });

    drawer.addEventListener('click', async (event) => {
        const target = event.target;

        // Onglets « Toutes » / « Non lues »
        const tab = target.closest('[data-notification-filter]');
        if (tab) {
            filter = tab.dataset.notificationFilter;
            $$('[data-notification-filter]', drawer).forEach((button) => button.setAttribute('aria-selected', button === tab));
            try {
                const data = await loadNotifications(0);
                list.replaceChildren(html(data.html));
                if (data.hasMore) list.append(moreButton(data.offset));
                $('[data-notifications-empty]', drawer).hidden = Boolean(data.html.trim());
                setUnreadCount(data.unread);
            } catch {
                toast('Impossible de charger les notifications.', 'error');
            }
            return;
        }

        // « Voir plus » : 10 notifications de plus.
        const more = target.closest('[data-more-notifications]');
        if (more && !more.getAttribute('aria-busy')) {
            more.setAttribute('aria-busy', 'true');
            try {
                const data = await loadNotifications(more.dataset.offset);
                more.parentElement.before(html(data.html));
                more.dataset.offset = data.offset;
                if (!data.hasMore) more.parentElement.remove();
            } catch {
                toast('Impossible de charger les notifications.', 'error');
            } finally {
                more.removeAttribute('aria-busy');
            }
            return;
        }

        // Basculer lue / non lue
        const toggle = target.closest('[data-notification-toggle]');
        if (toggle) {
            const item = toggle.closest('[data-notification]');
            const read = item.classList.contains('notification--new');
            const body = new FormData();
            body.append('id', item.dataset.notification);
            body.append('read', read ? '1' : '0');
            try {
                const data = await post('actions/notificationRead.php', body);
                setRead(item, read);
                setUnreadCount(data.unread);
                if (filter === 'unread' && read) item.remove();
            } catch (error) {
                toast(error.message, 'error');
            }
            return;
        }

        // « Tout marquer comme lu »
        if (target.closest('[data-mark-all-read]')) {
            try {
                await post('actions/updateSeenNotif.php', new FormData());
                $$('[data-notification]', drawer).forEach((item) => setRead(item, true));
                if (filter === 'unread') list.replaceChildren();
                setUnreadCount(0);
            } catch (error) {
                toast(error.message, 'error');
            }
            return;
        }

        // Ouvrir une notification la marque comme lue (envoyé même si la page change).
        const link = target.closest('[data-notification-link]');
        if (link) {
            const item = link.closest('[data-notification]');
            if (states && item.classList.contains('notification--new')) {
                const body = new FormData();
                body.append('_token', csrfToken);
                body.append('id', item.dataset.notification);
                body.append('read', '1');
                navigator.sendBeacon('actions/notificationRead.php', body);
                setRead(item, true);
                const badge = $('[data-notification-badge]');
                const count = Number($('[data-unread-count]')?.textContent || 0);
                if (badge || count) setUnreadCount(Math.max(0, count - 1));
            }
            // Lien vers une idée de la page courante : on ferme le panneau, la fiche s'ouvre.
            if (new URL(link.href).search === location.search) toggleNotifications(false);
        }
    });
}

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

if (grid) {
    [...grid.children].forEach((item) => gridObserver.observe(item));
    window.addEventListener('resize', () => layoutGrid(false));
    layoutGrid(false);
}

/* ---------- Idées offertes et filtres ---------- */

function storage(key, value) {
    try {
        if (value === undefined) return localStorage.getItem(key);
        localStorage.setItem(key, value);
    } catch {
        return null;
    }
}

const tabs = $('[data-tabs]');
const priceTools = $('[data-price-tools]');
const cards = () => $$('.card[data-object]');
const active = (card) => card.dataset.received !== '1';
const filters = {
    all: active,
    available: (card) => active(card) && card.dataset.gifted === '0',
    gifted: (card) => active(card) && card.dataset.gifted === '1',
    favorite: (card) => active(card) && card.dataset.favorite === '1',
    suggestion: (card) => active(card) && card.dataset.suggestion === '1',
    received: (card) => !active(card),
};
let currentFilter = 'all';
let budget = 0;
let sort = '';

// Ordre d'origine (les plus récentes d'abord), pour revenir au tri « Récentes ».
cards().forEach((card, index) => { card.dataset.index = index; });

function sortCards() {
    if (!grid) return;
    const price = (card) => (card.dataset.price === '' ? null : Number(card.dataset.price));
    const sorted = cards().sort((a, b) => {
        if (sort) {
            const pa = price(a);
            const pb = price(b);
            // Les idées sans prix vont à la fin.
            if (pa === null || pb === null) return (pa === null) - (pb === null);
            if (pa !== pb) return sort === 'asc' ? pa - pb : pb - pa;
        }
        return a.dataset.index - b.dataset.index;
    });
    grid.append(...sorted);
}

function refreshFilters() {
    if (!tabs) return;

    const showGifted = document.body.classList.contains('show-gifted');
    if (!showGifted && (currentFilter === 'available' || currentFilter === 'gifted')) currentFilter = 'all';

    for (const [name, test] of Object.entries(filters)) {
        const count = $(`[data-count="${name}"]`, tabs);
        if (count) count.textContent = `(${cards().filter(test).length})`;
    }
    const receivedTab = $('[data-filter="received"]', tabs);
    if (receivedTab) receivedTab.hidden = !cards().some(filters.received) && currentFilter !== 'received';
    const suggestionTab = $('[data-filter="suggestion"]', tabs);
    if (suggestionTab) suggestionTab.hidden = !cards().some(filters.suggestion) && currentFilter !== 'suggestion';

    $$('[data-filter]', tabs).forEach((tab) => tab.setAttribute('aria-pressed', tab.dataset.filter === currentFilter));

    // Menu de sélection (mobile) : mêmes filtres, mêmes compteurs.
    const select = $('[data-tabs-select]');
    if (select) {
        const labels = { all: 'Toutes les idées', available: 'À offrir', gifted: 'Déjà offertes', favorite: 'Coups de cœur', suggestion: 'Suggestions des amis', received: 'Reçus' };
        for (const option of select.options) {
            option.textContent = `${labels[option.value]} (${cards().filter(filters[option.value]).length})`;
            option.hidden = (['available', 'gifted'].includes(option.value) && !showGifted)
                || (['received', 'suggestion'].includes(option.value) && !cards().some(filters[option.value]));
        }
        select.value = currentFilter;
    }

    if (priceTools) {
        priceTools.hidden = !cards().some((card) => active(card) && card.dataset.price !== '');
        $$('[data-budget]', priceTools).forEach((button) => button.setAttribute('aria-pressed', Number(button.dataset.budget || 0) === budget));
        $$('[data-sort]', priceTools).forEach((button) => button.setAttribute('aria-pressed', button.dataset.sort === sort));
    }

    let visible = 0;
    cards().forEach((card) => {
        const price = card.dataset.price === '' ? null : Number(card.dataset.price);
        const show = filters[currentFilter](card) && (!budget || (price !== null && price <= budget));
        card.hidden = !show;
        if (show) visible++;
    });

    const addCard = $('.card--add');
    if (addCard) addCard.hidden = currentFilter !== 'all' || Boolean(budget);
    layoutGrid();

    const empty = $('[data-empty]');
    if (empty) empty.hidden = visible > 0 || Boolean(addCard && !addCard.hidden);
}

$('[data-tabs-select]')?.addEventListener('change', (event) => {
    currentFilter = event.target.value;
    refreshFilters();
});

tabs?.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-filter]');
    if (!tab) return;
    currentFilter = tab.dataset.filter;
    refreshFilters();
});

priceTools?.addEventListener('click', (event) => {
    const budgetButton = event.target.closest('[data-budget]');
    const sortButton = event.target.closest('[data-sort]');
    if (budgetButton) budget = Number(budgetButton.dataset.budget || 0);
    if (sortButton) {
        sort = sortButton.dataset.sort;
        sortCards();
    }
    refreshFilters();
});

// Deux interrupteurs possibles (barre du haut, et près des onglets sur mobile) : synchronisés.
const giftSwitches = $$('[data-show-gifted]');

if (giftSwitches.length) {
    const apply = (checked) => {
        giftSwitches.forEach((input) => { input.checked = checked; });
        document.body.classList.toggle('show-gifted', checked);
        storage('kdo-show-gifted', checked ? '1' : '0');
        refreshFilters();
    };
    giftSwitches.forEach((input) => input.addEventListener('change', () => apply(input.checked)));
    apply(storage('kdo-show-gifted') !== '0');
} else {
    refreshFilters();
}

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

/* ---------- « Je l'offre » : seul ou à plusieurs ---------- */

const giftChoice = $('#gift-choice-dialog');

if (giftChoice) {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-gift-choice]');
        if (trigger) {
            $('[data-gift-choice-id]', giftChoice).value = trigger.dataset.giftChoice;
            $('[data-gift-choice-name]', giftChoice).textContent = trigger.dataset.giftName;
            openDialog(giftChoice);
            return;
        }

        if (event.target.closest('[data-gift-group]')) {
            const id = $('[data-gift-choice-id]', giftChoice).value;
            const dialog = document.getElementById(`object-${id}`);
            openDialog(dialog);
            const amount = dialog?.querySelector('[name="amount"]');
            amount?.scrollIntoView({ block: 'center' });
            amount?.focus();
        }
    });
}

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

/* ---------- Partage ---------- */

$('[data-copy]')?.addEventListener('click', async () => {
    const field = $('[data-share-url]');
    try {
        await navigator.clipboard.writeText(field.value);
    } catch {
        field.select();
        document.execCommand('copy');
    }
    toast('Lien copié !', 'success');
});

// Lien d'invitation d'une liste privée (paramètres de la liste).
$('[data-copy-invite]')?.addEventListener('click', async () => {
    const field = $('[data-invite-url]');
    try {
        await navigator.clipboard.writeText(field.value);
    } catch {
        field.select();
        document.execCommand('copy');
    }
    toast("Lien d'invitation copié !", 'success');
});

const nativeShare = $('[data-native-share]');
if (nativeShare && navigator.share) {
    nativeShare.hidden = false;
    nativeShare.addEventListener('click', () => {
        navigator.share({ title: document.title, url: $('[data-share-url]').value }).catch(() => {});
    });
}

// Liens courts en HTTPS (TinyURL) : certaines applis, dont WhatsApp, ouvrent les liens en HTTPS,
// que Free ne gère pas. Le lien court redirige vers le site en HTTP. Chaque bloc [data-short]
// (partage de la liste, parrainage, invitation) demande le sien après l'affichage (actions/shortUrl.php),
// puis remplace le lien long dans ses champs et ses liens (pas dans un bloc [data-short] imbriqué).
$$('[data-short]').forEach((box) => {
    const params = new URLSearchParams({ type: box.dataset.short, user: box.dataset.shortUser || '' });
    fetch(`actions/shortUrl.php?${params}`, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((data) => {
            if (!data.ok || data.url === data.long) return;
            const own = (el) => el.closest('[data-short]') === box;
            $$('input', box).filter(own).forEach((field) => {
                if (field.value === data.long) field.value = data.url;
            });
            $$('a[href]', box).filter(own).forEach((link) => {
                link.href = link.href.replaceAll(encodeURIComponent(data.long), encodeURIComponent(data.url));
            });
        })
        .catch(() => {});
});

/* ---------- Formulaire d'idée (ajout / modification) ---------- */

const objectDialog = $('#object-form-dialog');

if (objectDialog) {
    const form = $('[data-object-form]', objectDialog);
    const deleteForm = $('#delete-object-form');
    const preview = $('[data-image-preview]', form);
    const status = $('[data-metadata-status]', form);
    const fields = form.elements;

    const showPreview = (src) => {
        preview.hidden = !src;
        if (src) preview.src = src;
        $$('[data-image-choice]', form).forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.imageChoice === src)));
    };

    // Plusieurs images trouvées : vignettes cliquables pour choisir celle de l'idée.
    const choices = $('[data-image-choices]', form);
    const showChoices = (images) => {
        const list = $('[data-image-choices-list]', choices);
        list.replaceChildren();
        images.forEach((src) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'image-choices__item';
            button.dataset.imageChoice = src;
            button.setAttribute('aria-label', 'Choisir cette image');
            button.setAttribute('aria-pressed', String(src === fields.image.value));
            const img = document.createElement('img');
            img.src = src;
            img.alt = '';
            img.loading = 'lazy';
            img.referrerPolicy = 'no-referrer';
            // Image introuvable ou interdite d'affichage hors du site : on la retire.
            img.addEventListener('error', () => {
                button.remove();
                if (list.children.length < 2) choices.hidden = true;
            });
            button.append(img);
            list.append(button);
        });
        choices.hidden = images.length < 2;
    };

    choices.addEventListener('click', (event) => {
        const button = event.target.closest('[data-image-choice]');
        if (!button) return;
        fields.file.value = '';
        fields.image.value = button.dataset.imageChoice;
        showPreview(button.dataset.imageChoice);
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-open-object-form]');
        if (!trigger) return;

        const object = trigger.dataset.openObjectForm ? JSON.parse(trigger.dataset.openObjectForm) : null;
        form.reset();
        status.textContent = '';
        form.action = object ? form.dataset.editAction : form.dataset.addAction;
        fields.object_id.value = object?.id ?? '';
        fields.nom.value = object?.nom ?? '';
        fields.description.value = object?.description ?? '';
        fields.image.value = object?.image ?? '';
        fields.link.value = object?.link ?? '';
        if (fields.price) fields.price.value = object?.price ?? '';
        deleteForm.elements.id.value = object?.id ?? '';
        $('[data-delete-object]', form).hidden = !object;
        $('[data-object-form-title]', form).textContent = object ? `Modifier : ${object.nom}` : form.dataset.addTitle;
        $('[data-object-form-submit]', form).textContent = object ? 'Enregistrer' : form.dataset.addLabel;
        showPreview(object?.image);
        showChoices([]);
        setCollection(object?.items || []);
        openDialog(objectDialog);
    });

    // Collection : liste d'éléments (juste un nom), ajoutés avec « + ».
    const collection = $('[data-collection]', form);

    function addCollectionRow(item = null, focus = false) {
        const row = $('[data-collection-row]', collection).content.firstElementChild.cloneNode(true);
        row.querySelector('[name="item_ids[]"]').value = item?.id ?? '';
        const input = row.querySelector('[name="items[]"]');
        input.value = item?.nom ?? '';
        $('[data-collection-list]', collection).append(row);
        if (focus) input.focus();
        return input;
    }

    function toggleCollection(enabled) {
        $('[data-collection-editor]', collection).hidden = !enabled;
        $$('[name="items[]"]', collection).forEach((input) => { input.required = false; });
        if (enabled && !$('[data-collection-list] li', collection)) addCollectionRow(null, true);
    }

    function setCollection(items) {
        if (!collection) return;
        $('[data-collection-list]', collection).replaceChildren();
        const toggle = $('[data-collection-toggle]', collection);
        toggle.checked = items.length > 0;
        items.forEach((item) => addCollectionRow(item));
        toggleCollection(toggle.checked);
    }

    if (collection) {
        $('[data-collection-toggle]', collection).addEventListener('change', (event) => toggleCollection(event.target.checked));
        $('[data-collection-add]', collection).addEventListener('click', () => addCollectionRow(null, true));
        collection.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-collection-remove]');
            if (!remove) return;
            const row = remove.closest('li');
            const next = row.nextElementSibling || row.previousElementSibling;
            row.remove();
            next?.querySelector('[name="items[]"]')?.focus();
        });
        // Entrée dans un élément : nouvelle ligne au lieu d'envoyer le formulaire.
        collection.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && event.target.matches('[name="items[]"]')) {
                event.preventDefault();
                if (event.target.value.trim()) addCollectionRow(null, true);
            }
        });
    }

    fields.image.addEventListener('input', () => {
        if (!fields.file.files.length) showPreview(fields.image.value.trim());
    });

    fields.file.addEventListener('change', () => {
        const [file] = fields.file.files;
        showPreview(file ? URL.createObjectURL(file) : fields.image.value.trim());
    });

    // Remplissage automatique depuis le lien du produit.
    let timer;
    let controller;

    fields.link.addEventListener('input', () => {
        clearTimeout(timer);
        const url = fields.link.value.trim();
        if (url.length < 8) return;

        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();
            status.textContent = 'Recherche des informations du produit…';
            status.classList.add('is-loading');

            try {
                const body = new FormData();
                body.append('pageUrl', url);
                const response = await fetch('actions/fetch_metadata.php', {
                    method: 'POST',
                    body,
                    signal: controller.signal,
                    headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
                });
                let metadata = await response.json();

                // Le serveur (Free) est souvent bloqué : on demande alors à Microlink, directement depuis le navigateur.
                if (!metadata.success || metadata.partial || !metadata.image) {
                    const extra = await microlinkMetadata(url, controller.signal);
                    if (extra) {
                        metadata = {
                            success: true,
                            // Un nom deviné depuis le lien (« partial ») vaut moins que le vrai titre de la page.
                            title: metadata.success && !metadata.partial && metadata.title ? metadata.title : (extra.title || metadata.title),
                            partial: !extra.title && (metadata.partial || !metadata.success),
                            description: metadata.description || extra.description,
                            image: metadata.image || extra.image,
                            images: [...(metadata.images || []), ...(extra.image ? [extra.image] : [])],
                            price: metadata.price || '',
                        };
                    }
                }

                // On n'écrase jamais ce que l'utilisateur a déjà saisi.
                if (metadata.success) {
                    if (metadata.title && !fields.nom.value) fields.nom.value = productTitle(metadata.title);
                    if (metadata.description && !fields.description.value) fields.description.value = metadata.description;
                    if (metadata.image && !fields.image.value && !fields.file.files.length) {
                        fields.image.value = metadata.image;
                        showPreview(metadata.image);
                    }
                    if (metadata.price && fields.price && !fields.price.value) fields.price.value = String(metadata.price).replace('.', ',');
                    if (!fields.file.files.length) showChoices([...new Set(metadata.images || [])]);
                }
                status.textContent = metadata.partial || !metadata.success
                    ? 'Ce site bloque la lecture automatique : complétez à la main (ou utilisez l’extension Chrome).'
                    : (metadata.image ? 'Informations récupérées ✔' : 'Informations récupérées ✔ (pas d’image trouvée)');
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = '';
            } finally {
                status.classList.remove('is-loading');
            }
        }, 600);
    });
}

/* « L’orchidée 10311 | Creator Expert | Boutique LEGO® » -> « L’orchidée 10311 » (nom du site retiré). */
function productTitle(title) {
    const [first] = title.split(' | ');
    return first.trim().length >= 4 ? first.trim() : title;
}

/*
 * Microlink (offre gratuite, sans clé, ~25 liens par jour et par visiteur) lit la page à notre place.
 * Appelé depuis le navigateur : Free ne peut pas le joindre, et aucun secret n'est nécessaire.
 */
async function microlinkMetadata(url, signal) {
    try {
        const response = await fetch(`https://api.microlink.io/?url=${encodeURIComponent(url)}`, { signal });
        const result = await response.json();
        const data = result.status === 'success' ? result.data : null;
        if (!data) return null;
        // Microlink renvoie parfois le lien lui-même comme titre, ou un logo (SVG) comme image : on les ignore.
        const title = data.title && data.title.includes(' ') && !/introuvable|not found|access denied|captcha|robot/i.test(data.title) ? data.title : '';
        const image = data.image?.url && !/\.svg(\?|$)/i.test(data.image.url) ? data.image.url : '';
        if (!title && !image) return null;
        return { title, description: title ? data.description || '' : '', image };
    } catch (error) {
        if (error.name === 'AbortError') throw error;
        return null;
    }
}

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

/*
 * Suppression d'une idée (form[data-delete-idea] : menu « ⋯ » de la vignette ou fenêtre de modification),
 * en arrière-plan : la vignette part dans la corbeille (throwInTrash), puis disparaît de la liste.
 */
document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-delete-idea]');
    if (!form) return;
    event.preventDefault();
    const card = document.getElementById(`idea-${form.elements.id.value}`);
    form.closest('dialog')?.close();
    card?.querySelector('details[open]')?.removeAttribute('open');

    const request = post(form.getAttribute('action'), new FormData(form));
    try {
        const [result] = await Promise.allSettled([request, card ? throwInTrash(card) : null]);
        if (result.status === 'rejected') throw result.reason;
        card?.remove();
        const total = $('[data-count-total]');
        if (total) {
            const count = Math.max(0, Number(total.textContent) - 1);
            total.textContent = count;
            if (total.nextSibling) total.nextSibling.textContent = count > 1 ? ' idées' : ' idée';
        }
        refreshFilters();
        layoutGrid();
        toast('Idée supprimée.', 'success');
        refreshGems();
    } catch (error) {
        if (card) card.style.visibility = '';
        toast(error.message, 'error');
    }
});

/* ---------- Boutique : un onglet par type de liste ---------- */

// Paramètres de la liste : seuls les habillages du type de liste coché sont proposés (et envoyés).
function syncSkinField(form) {
    const field = form && $('[data-skin-field]', form);
    const theme = form && $('input[name="theme"]:checked', form)?.value;
    if (!field || !theme) return;
    let checked = false;
    $$('[data-skin-theme]', field).forEach((option) => {
        const visible = option.dataset.skinTheme === theme;
        const input = $('input', option);
        option.hidden = !visible;
        input.disabled = !visible;
        if (!visible) input.checked = false;
        if (visible && input.checked) checked = true;
    });
    if (!checked) $(`[data-skin-theme="${theme}"].skin-field__option--classic input`, field).checked = true;
}
document.addEventListener('change', (event) => {
    if (event.target.matches('input[name="theme"]')) syncSkinField(event.target.form);
});

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

// Mobile : la carte de la liste (en haut à gauche) ouvre « Mes amis » au lieu de recharger la liste.
document.addEventListener('click', (event) => {
    const brand = event.target.closest('[data-mobile-friends]');
    if (!brand || !matchMedia('(max-width: 700px)').matches || !$('#friends-dialog')) return;
    event.preventDefault();
    openDialog($('#friends-dialog'));
});

// Onglets de la vitrine des badges.
document.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-badge-tab]');
    if (!tab) return;
    $$('[data-badge-tab]').forEach((other) => other.setAttribute('aria-pressed', String(other === tab)));
    $$('[data-badge-section]').forEach((section) => { section.hidden = section.dataset.badgeSection !== tab.dataset.badgeTab; });
});

// Boutique : catégorie (habillages, cadres, compte à rebours) ; les types de liste ne concernent que les habillages.
document.addEventListener('click', (event) => {
    const cat = event.target.closest('[data-shop-cat]');
    if (!cat) return;
    $$('[data-shop-cat]').forEach((other) => other.setAttribute('aria-pressed', String(other === cat)));
    $$('[data-shop-panel]').forEach((panel) => { panel.hidden = panel.dataset.shopPanel !== cat.dataset.shopCat; });
    const subnav = $('[data-shop-subnav]');
    if (subnav) subnav.hidden = cat.dataset.shopCat !== 'skins';
});

// « Plus de cadres » / « Plus d'effets » (paramètres, profil) : la boutique s'ouvre sur la bonne catégorie.
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-shop-open]');
    const cat = link && $(`[data-shop-cat="${link.dataset.shopOpen}"]`);
    if (cat) cat.click();
});

document.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-shop-tab]');
    if (!tab) return;
    $$('[data-shop-tab]').forEach((other) => other.setAttribute('aria-pressed', String(other === tab)));
    $$('[data-shop-section]').forEach((section) => { section.hidden = section.dataset.shopSection !== tab.dataset.shopTab; });
});

// Aperçu d'un habillage : fausse liste à ses couleurs, ouverte par-dessus la boutique.
const skinPreview = $('#skin-preview-dialog');
let previewSource = null;
document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-skin-preview]');
    if (!trigger || !skinPreview) return;
    const data = JSON.parse(trigger.dataset.skinPreview);
    const stage = $('[data-preview-stage]', skinPreview);
    for (const [name, value] of Object.entries(data.colors)) stage.style.setProperty(`--${name}`, value);
    $('[data-preview-title]', skinPreview).src = data.title;
    $('[data-preview-subtitle]', skinPreview).textContent = data.subtitle;
    for (const side of ['left', 'right']) {
        $(`[data-preview-${side}]`, skinPreview).replaceChildren(...data[side].map((src) => {
            const img = document.createElement('img');
            img.src = src;
            img.alt = '';
            return img;
        }));
    }
    $('[data-preview-name]', skinPreview).textContent = data.name;
    $('[data-preview-meta]', skinPreview).textContent = `${data.rarity} · listes « ${data.type} »`;

    // Bouton d'achat (ou « Utiliser ») de la carte, repris tel quel.
    previewSource = trigger.closest('.shop-item').querySelector('.shop-item__action button[type=submit]');
    const buy = $('[data-preview-buy]', skinPreview);
    buy.hidden = !previewSource;
    if (previewSource) buy.innerHTML = previewSource.innerHTML;
    // Pas de bouton (trop cher, déjà porté…) : on recopie l'état affiché sur la carte.
    const price = $('[data-preview-price]', skinPreview);
    const action = trigger.closest('.shop-item').querySelector('.shop-item__action');
    price.hidden = Boolean(previewSource);
    price.innerHTML = previewSource ? '' : [...action.children].filter((el) => el.tagName !== 'INPUT').map((el) => el.outerHTML).join(' ');

    skinPreview.showModal();
});
$('[data-preview-buy]', skinPreview || document)?.addEventListener('click', () => {
    skinPreview.close();
    previewSource?.click();
});

/* ---------- Gemmes gagnées : la pastille s'anime ---------- */

const gemPill = $('.gem-pill');

function showGems(from, to) {
    if (!gemPill || to === from) return;
    const value = $('.gem-pill__value', gemPill);
    const counters = [value, ...$$('.user-menu__count--gems')];
    gemPill.dataset.gems = to;
    gemPill.setAttribute('aria-label', `Boutique : ${to} gemmes`);
    if (to < from || matchMedia('(prefers-reduced-motion: reduce)').matches) {
        counters.forEach((el) => { el.textContent = to; });
        return;
    }

    // Le solde défile jusqu'à sa nouvelle valeur.
    const start = performance.now();
    const tick = (now) => {
        const t = Math.min(1, (now - start) / 900);
        const current = Math.round(from + (to - from) * (1 - (1 - t) ** 3));
        counters.forEach((el) => { el.textContent = current; });
        if (t < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);

    gemPill.classList.remove('is-gaining');
    void gemPill.offsetWidth;
    gemPill.classList.add('is-gaining');
    playGemSound(to - from);

    // L'animation se joue dans la couche supérieure du navigateur (popover), au-dessus d'une fenêtre ouverte
    // et de son fond flouté. Si une fenêtre est ouverte, une copie nette de la pastille s'affiche aussi.
    const layer = document.createElement('div');
    layer.className = 'gem-layer';
    layer.setAttribute('popover', 'manual');
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
        copy.classList.add('gem-pill--copy', 'is-gaining');
        Object.assign(copy.style, { left: `${box.left}px`, top: `${box.top}px`, width: `${box.width}px`, height: `${box.height}px` });
        layer.append(copy);
        counters.push($('.gem-pill__value', copy));
        copy.animate([{ opacity: 0 }, { opacity: 1, offset: .1 }, { opacity: 1, offset: .85 }, { opacity: 0 }], { duration: 2000, fill: 'forwards' });
    }

    // « +N » qui s'envole, et quelques gemmes qui jaillissent de la pastille.
    const label = document.createElement('span');
    label.className = 'gem-gain';
    label.innerHTML = `+${to - from} <img src="${gemSrc}" alt="">`;
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
        gem.className = 'gem-spark';
        gem.style.left = `${box.left + box.width / 2}px`;
        gem.style.top = `${box.top + box.height / 2}px`;
        layer.append(gem);
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

// Petit bruit de pièce, une fois par gemme gagnée (12 au plus), à faible volume.
// Le navigateur peut le refuser tant que la page n'a reçu aucun clic : on l'ignore alors.
function playGemSound(count) {
    const src = gemPill?.dataset.gemsSound;
    if (!src) return;
    for (let i = 0; i < Math.min(count, 12); i++) {
        setTimeout(() => {
            const sound = new Audio(src);
            sound.volume = 0.25;
            sound.play().catch(() => {});
        }, i * 90);
    }
}

// Au chargement : gemmes gagnées depuis la page précédente (pas sous les tests automatisés).
if (gemPill && !navigator.webdriver) {
    const from = Number(gemPill.dataset.gemsFrom);
    const to = Number(gemPill.dataset.gems);
    if (to > from) {
        $('.gem-pill__value', gemPill).textContent = from;
        setTimeout(() => showGems(from, to), 700);
    }
}

// Après une action en arrière-plan : on redemande le solde.
let gemsCheck = null;
async function refreshGems() {
    if (!gemPill) return;
    clearTimeout(gemsCheck);
    gemsCheck = setTimeout(async () => {
        try {
            const response = await fetch('actions/gems.php', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await response.json();
            if (data.ok) showGems(Number(gemPill.dataset.gems), data.balance);
        } catch {
            // Sans réponse, la pastille sera à jour au prochain chargement.
        }
    }, 400);
}

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

/* ---------- Images : réduction avant envoi ---------- */

/*
 * Les photos de téléphone dépassent souvent la limite de 2 Mo de Free.
 * On les redimensionne dans le navigateur (orientation EXIF comprise) avant l'envoi.
 */
async function shrinkImage(file, maxSide) {
    if (!file.type.startsWith('image/') || file.type === 'image/gif') return file;

    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));

    if (scale === 1 && file.size < 1.5 * 1024 * 1024 && /jpe?g|png/.test(file.type)) {
        bitmap.close();
        return file;
    }

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    const context = canvas.getContext('2d');
    context.fillStyle = '#fff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';

    return new File([blob], name, { type: 'image/jpeg' });
}

$$('form[data-resize-images]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.ready) {
            delete form.dataset.ready;
            return;
        }

        const repeat = form.elements['re-password'];
        if (repeat && repeat.value !== form.elements.password.value) {
            event.preventDefault();
            toast('Les mots de passe sont différents.', 'error');
            repeat.focus();
            return;
        }

        const inputs = $$('input[type="file"][data-max-size]', form).filter((input) => input.files.length);
        if (!inputs.length) return;

        event.preventDefault();
        const submit = $('[type="submit"]:not([form])', form);
        submit?.setAttribute('aria-busy', 'true');

        try {
            for (const input of inputs) {
                const resized = await shrinkImage(input.files[0], Number(input.dataset.maxSize));
                const transfer = new DataTransfer();
                transfer.items.add(resized);
                input.files = transfer.files;
            }
        } catch {
            // Si la réduction échoue, le fichier d'origine est envoyé tel quel.
        }

        form.dataset.ready = '1';
        form.requestSubmit(event.submitter ?? undefined);
    });
});

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

openFromHash();
