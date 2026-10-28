import { $, $$, html, post } from './core.js';
import { toast } from './ui.js';

/*
 * Réactions aux commentaires, comme dans WhatsApp :
 * le bouton smiley à côté d'une bulle (ou un appui long sur mobile) ouvre une barre de six emojis au-dessus du message ;
 * « + » déplie le sélecteur complet (onglets par catégorie, recherche en français, grille), chargé à la demande
 * depuis emoji-data.js. La barre est un popover : elle passe au-dessus de la fiche de l'idée sans être coupée.
 */

const QUICK_EMOJIS = ['👍', '❤️', '😂', '😮', '😢', '🙏'];
const GAP = 8;

let pop = null;
let current = null; // { id, anchor }
let lastClosed = { id: null, time: 0 };
let closingByCode = false; // fermeture après un choix, pas par un clic à l'extérieur
let groupsPromise = null;
let allEmojis = [];

const normalize = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

/** Premier emoji d'un texte (séquences, drapeaux et touches compris), ou ''. */
function firstEmoji(text) {
    const segments = typeof Intl.Segmenter === 'function'
        ? [...new Intl.Segmenter('fr', { granularity: 'grapheme' }).segment(text)].map((part) => part.segment)
        : [...text];
    return segments.find((part) => /\p{Extended_Pictographic}|\p{Regional_Indicator}|⃣/u.test(part)) || '';
}

function spriteIcon(name) {
    const sprite = $('svg.icon use')?.getAttribute('href').split('#')[0] || 'img/icons.svg';
    return `<svg class="icon" aria-hidden="true"><use href="${sprite}#i-${name}"></use></svg>`;
}

function emojiButton(emoji) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'emoji-grid__item';
    button.dataset.emoji = emoji;
    button.textContent = emoji;
    return button;
}

/* ---------- Barre rapide ---------- */

function createPop() {
    pop = document.createElement('div');
    pop.className = 'reaction-pop';
    pop.setAttribute('popover', 'auto');
    pop.setAttribute('role', 'dialog');
    pop.setAttribute('aria-label', 'Réagir au commentaire');
    pop.innerHTML = `
        <div class="reaction-pop__panel" data-emoji-panel hidden></div>
        <div class="reaction-pop__bar">
            ${QUICK_EMOJIS.map((emoji) => `<button type="button" class="reaction-pop__quick" data-emoji="${emoji}">${emoji}</button>`).join('')}
            <button type="button" class="reaction-pop__more" data-emoji-more aria-label="Plus d’emojis" aria-expanded="false">${spriteIcon('plus')}</button>
        </div>`;

    pop.addEventListener('toggle', (event) => {
        if (event.newState === 'closed') {
            lastClosed = closingByCode ? { id: null, time: 0 } : { id: current?.id, time: Date.now() };
            closingByCode = false;
            current?.anchor.setAttribute('aria-expanded', 'false');
            current = null;
        }
    });

    pop.addEventListener('click', (event) => {
        const choice = event.target.closest('[data-emoji]');
        if (choice && current) {
            sendReaction(current.id, choice.dataset.emoji);
            return;
        }
        if (event.target.closest('[data-emoji-more]')) togglePanel();
        const tab = event.target.closest('[data-emoji-tab]');
        if (tab) showGroup(tab.dataset.emojiTab);
    });

    pop.addEventListener('input', (event) => {
        if (event.target.matches('[data-emoji-search]')) search(event.target.value);
    });
}

function openPop(button) {
    const id = button.dataset.commentReact;
    // Un clic sur le bouton qui vient de fermer la barre (clic à l'extérieur) ne la rouvre pas.
    if (lastClosed.id === id && Date.now() - lastClosed.time < 300) return;
    if (!pop) createPop();
    if (pop.matches(':popover-open')) pop.hidePopover();

    // Dans la fenêtre de l'idée, sinon elle serait inerte derrière la fenêtre modale.
    (button.closest('dialog') || document.body).append(pop);
    const mine = button.closest('.comment__main')?.querySelector('[data-comment-reactions]')?.dataset.mine || '';
    $$('[data-emoji]', pop.querySelector('.reaction-pop__bar')).forEach((choice) => {
        choice.setAttribute('aria-pressed', choice.dataset.emoji === mine);
    });
    closePanel();

    current = { id, anchor: button };
    button.setAttribute('aria-expanded', 'true');
    pop.showPopover();
    place();
}

/** Au-dessus du message s'il y a la place, sinon en dessous ; toujours dans l'écran. */
function place() {
    if (!current) return;
    const row = current.anchor.closest('.comment__row') || current.anchor;
    const box = row.getBoundingClientRect();
    const width = pop.offsetWidth;
    const height = pop.offsetHeight;
    const viewW = document.documentElement.clientWidth;
    const viewH = window.innerHeight;

    const left = Math.min(Math.max(GAP, box.left), viewW - width - GAP);
    const above = box.top - height - GAP >= GAP;
    const below = box.bottom + height + GAP <= viewH - GAP;
    let top = above || !below ? box.top - height - GAP : box.bottom + GAP;
    top = Math.min(Math.max(GAP, top), viewH - height - GAP);

    pop.classList.toggle('reaction-pop--below', !above && below);
    pop.style.left = `${Math.max(GAP, left)}px`;
    pop.style.top = `${top}px`;
}

/* ---------- Sélecteur complet ---------- */

function closePanel() {
    const panel = $('[data-emoji-panel]', pop);
    panel.hidden = true;
    $('[data-emoji-more]', pop).setAttribute('aria-expanded', 'false');
    const field = $('[data-emoji-search]', panel);
    if (field && field.value) {
        field.value = '';
        search('');
    }
}

async function togglePanel() {
    const panel = $('[data-emoji-panel]', pop);
    if (!panel.hidden) {
        closePanel();
        place();
        return;
    }
    if (!panel.childElementCount) await buildPanel(panel);
    panel.hidden = false;
    $('[data-emoji-more]', pop).setAttribute('aria-expanded', 'true');
    place();
    // Sur ordinateur, on peut chercher tout de suite ; sur téléphone, pas de clavier qui surgit.
    if (window.matchMedia('(hover: hover)').matches) $('[data-emoji-search]', panel).focus({ preventScroll: true });
}

async function buildPanel(panel) {
    groupsPromise ||= import('./emoji-data.js').then((module) => module.EMOJI_GROUPS);
    let groups;
    try {
        groups = await groupsPromise;
    } catch {
        groupsPromise = null;
        toast('Les emojis n’ont pas pu être chargés, réessayez.', 'error');
        return;
    }

    panel.innerHTML = `
        <nav class="emoji-tabs" aria-label="Catégories">
            ${groups.map((group, index) => `<button type="button" class="emoji-tabs__tab${index ? '' : ' is-active'}" data-emoji-tab="${group.key}" aria-label="${group.name}" title="${group.name}">${spriteIcon(`emoji-${group.key}`)}</button>`).join('')}
        </nav>
        <label class="emoji-search">
            ${spriteIcon('magnifying-glass')}
            <input type="search" placeholder="Rechercher réaction" aria-label="Rechercher un emoji" autocomplete="off" data-emoji-search>
        </label>
        <div class="emoji-scroll" data-emoji-scroll>
            <section class="emoji-section" data-emoji-results hidden>
                <h4>Résultats</h4>
                <div class="emoji-grid"></div>
                <p class="emoji-empty" hidden>Aucun emoji trouvé.</p>
            </section>
        </div>`;

    const scroll = $('[data-emoji-scroll]', panel);
    allEmojis = [];
    groups.forEach((group) => {
        const section = document.createElement('section');
        section.className = 'emoji-section';
        section.dataset.emojiGroup = group.key;
        section.innerHTML = `<h4>${group.name}</h4>`;
        const grid = document.createElement('div');
        grid.className = 'emoji-grid';
        group.list.split('\n').forEach((line) => {
            const [emoji, words = ''] = line.split('\t');
            grid.append(emojiButton(emoji));
            allEmojis.push({ emoji, words: normalize(words) });
        });
        section.append(grid);
        scroll.append(section);
    });

    // L'onglet actif suit la catégorie visible.
    scroll.addEventListener('scroll', () => {
        let active = $$('[data-emoji-group]', scroll)[0];
        $$('[data-emoji-group]', scroll).forEach((section) => {
            if (section.offsetTop - scroll.offsetTop <= scroll.scrollTop + 12) active = section;
        });
        setActiveTab(active?.dataset.emojiGroup);
    }, { passive: true });
}

function setActiveTab(key) {
    $$('[data-emoji-tab]', pop).forEach((tab) => tab.classList.toggle('is-active', tab.dataset.emojiTab === key));
}

function showGroup(key) {
    const field = $('[data-emoji-search]', pop);
    if (field.value) {
        field.value = '';
        search('');
    }
    const scroll = $('[data-emoji-scroll]', pop);
    const section = $(`[data-emoji-group="${key}"]`, scroll);
    if (section) scroll.scrollTop = section.offsetTop - scroll.offsetTop;
    setActiveTab(key);
}

function search(value) {
    const scroll = $('[data-emoji-scroll]', pop);
    const results = $('[data-emoji-results]', scroll);
    const query = normalize(value.trim());

    // Un emoji tapé avec le clavier du téléphone ou de l'ordinateur est envoyé directement.
    const typed = firstEmoji(value);
    if (typed && current) {
        sendReaction(current.id, typed);
        return;
    }

    $$('[data-emoji-group]', scroll).forEach((section) => { section.hidden = Boolean(query); });
    results.hidden = !query;
    if (!query) return;

    const terms = query.split(/\s+/);
    const found = allEmojis.filter((item) => terms.every((term) => item.words.includes(term))).slice(0, 240);
    $('.emoji-grid', results).replaceChildren(...found.map((item) => emojiButton(item.emoji)));
    $('.emoji-empty', results).hidden = found.length > 0;
    scroll.scrollTop = 0;
}

/* ---------- Envoi ---------- */

async function sendReaction(id, emoji) {
    if (pop?.matches(':popover-open')) {
        closingByCode = true;
        pop.hidePopover();
    }
    const body = new FormData();
    body.set('id', id);
    body.set('emoji', emoji);
    try {
        const data = await post('actions/commentReaction.php', body);
        $$(`[data-comment-reactions="${id}"]`).forEach((slot) => slot.replaceWith(html(data.html)));
    } catch (error) {
        toast(error.message, 'error');
    }
}

export function initCommentReactions() {
    document.addEventListener('click', (event) => {
        const target = event.target;

        const react = target.closest?.('[data-comment-react]');
        if (react) {
            if (current?.id === react.dataset.commentReact) pop.hidePopover();
            else openPop(react);
            return;
        }

        // Pastille : qui a réagi (et « Retirer » pour sa propre réaction).
        const reactors = target.closest?.('[data-comment-reactors]');
        if (reactors) {
            const list = document.getElementById(reactors.getAttribute('aria-controls'));
            const open = reactors.getAttribute('aria-expanded') !== 'true';
            reactors.setAttribute('aria-expanded', open);
            if (list) list.hidden = !open;
        }

        const remove = target.closest?.('[data-comment-reaction-remove]');
        if (remove) sendReaction(remove.closest('[data-comment-reactions]').dataset.commentReactions, '');
    });

    // Appui long sur une bulle (écran tactile) : ouvre la barre, comme dans WhatsApp.
    let pressTimer = 0;
    let pressed = false;
    document.addEventListener('pointerdown', (event) => {
        if (event.pointerType !== 'touch') return;
        const button = event.target.closest?.('.comment__row')?.querySelector('[data-comment-react]');
        if (!button) return;
        pressed = false;
        pressTimer = setTimeout(() => {
            pressed = true;
            openPop(button);
            navigator.vibrate?.(10);
        }, 450);
    });
    ['pointerup', 'pointercancel', 'pointermove'].forEach((type) => {
        document.addEventListener(type, (event) => {
            if (type !== 'pointermove' || Math.abs(event.movementX) + Math.abs(event.movementY) > 6) clearTimeout(pressTimer);
        });
    });
    // Après un appui long : ni menu contextuel ni ouverture de l'image.
    document.addEventListener('contextmenu', (event) => {
        if (pressed && event.target.closest?.('.comment__row')) event.preventDefault();
    });
    document.addEventListener('click', (event) => {
        if (pressed && event.target.closest?.('.comment__row')) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
        pressed = false;
    }, true);

    // La barre suit le message quand la fiche défile.
    document.addEventListener('scroll', (event) => {
        if (current && !pop.contains(event.target)) place();
    }, { capture: true, passive: true });
    window.addEventListener('resize', () => current && place());
}
