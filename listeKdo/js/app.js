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

    // Clic sur le fond sombre autour de la fenêtre.
    if (event.target instanceof HTMLDialogElement) {
        const box = event.target.getBoundingClientRect();
        const inside = event.clientX >= box.left && event.clientX <= box.right
            && event.clientY >= box.top && event.clientY <= box.bottom;
        if (!inside) event.target.close();
    }
});

function openFromHash() {
    const [, kind, id] = location.hash.match(/^#(idea|card)-(\d+)$/) ?? [];
    const card = id && document.getElementById(`idea-${id}`);
    if (!card) return;

    card.scrollIntoView({ block: 'center' });

    if (kind === 'idea') {
        openDialog(document.getElementById(`object-${id}`));
    } else {
        // Après un ajout ou une modification : on met la carte en évidence.
        card.classList.add('is-highlighted');
        card.addEventListener('animationend', () => card.classList.remove('is-highlighted'), { once: true });
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

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm]');
    if (trigger && !confirm(trigger.dataset.confirm)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
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

    received(form, data) {
        const card = form.closest('.card');
        if (card) {
            card.dataset.received = data.received ? '1' : '0';
            card.classList.toggle('card--received', data.received);
        }
        const button = form.querySelector('.received-btn');
        button?.setAttribute('aria-pressed', data.received);
        const label = button?.querySelector('span');
        if (label) label.textContent = data.received ? 'Remettre dans la liste' : "Je l'ai reçu";
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

function layoutGrid() {
    if (!grid || layoutPending) return;
    layoutPending = true;
    requestAnimationFrame(() => {
        layoutPending = false;
        grid.classList.add('is-masonry');
        const gap = 24;
        for (const item of grid.children) {
            if (item.hidden) continue;
            item.style.gridRowEnd = `span ${Math.ceil((item.getBoundingClientRect().height + gap) / 4)}`;
        }
    });
}

if (grid) {
    // Recalcule quand une vignette change de taille (image chargée, réservation, filtre…).
    const observer = new ResizeObserver(layoutGrid);
    [...grid.children].forEach((item) => observer.observe(item));
    window.addEventListener('resize', layoutGrid);
    layoutGrid();
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

    $$('[data-filter]', tabs).forEach((tab) => tab.setAttribute('aria-pressed', tab.dataset.filter === currentFilter));

    // Menu de sélection (mobile) : mêmes filtres, mêmes compteurs.
    const select = $('[data-tabs-select]');
    if (select) {
        const labels = { all: 'Toutes les idées', available: 'À offrir', gifted: 'Déjà offertes', favorite: 'Coups de cœur', received: 'Reçus' };
        for (const option of select.options) {
            option.textContent = `${labels[option.value]} (${cards().filter(filters[option.value]).length})`;
            option.hidden = (['available', 'gifted'].includes(option.value) && !showGifted)
                || (option.value === 'received' && !cards().some(filters.received));
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
        const left = Math.max(0, Math.floor((target - Date.now()) / 1000));
        const today = left === 0 || new Date().toDateString() === target.toDateString();
        countdown.classList.toggle('is-today', today);
        if (today) return;

        const next = { d: String(Math.floor(left / 86400)), h: pad(Math.floor((left % 86400) / 3600)), m: pad(Math.floor((left % 3600) / 60)), s: pad(left % 60) };
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
        if (!userMenu.contains(event.target) || event.target.closest('[data-open]')) userMenu.open = false;
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') userMenu.open = false;
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

const nativeShare = $('[data-native-share]');
if (nativeShare && navigator.share) {
    nativeShare.hidden = false;
    nativeShare.addEventListener('click', () => {
        navigator.share({ title: document.title, url: $('[data-share-url]').value }).catch(() => {});
    });
}

// Lien court en HTTPS (TinyURL) : certaines applis, dont WhatsApp, ouvrent les liens en HTTPS,
// que Free ne gère pas. Le lien court redirige vers le site en HTTP.
const shareBox = $('[data-share]');
if (shareBox) {
    fetch(`actions/shortUrl.php?user=${encodeURIComponent(shareBox.dataset.shareCode)}`, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((data) => {
            if (!data.ok || data.url === data.long) return;
            $('[data-share-url]').value = data.url;
            $$('a[href]', shareBox).forEach((link) => {
                link.href = link.href.replaceAll(encodeURIComponent(data.long), encodeURIComponent(data.url));
            });
        })
        .catch(() => {});
}

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
    };

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
        $('[data-object-form-title]', form).textContent = object ? `Modifier : ${object.nom}` : 'Une nouvelle idée ?';
        $('[data-object-form-submit]', form).textContent = object ? 'Enregistrer' : 'Ajouter';
        showPreview(object?.image);
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
                const metadata = await response.json();

                // On n'écrase jamais ce que l'utilisateur a déjà saisi.
                if (metadata.success) {
                    if (metadata.title && !fields.nom.value) fields.nom.value = metadata.title;
                    if (metadata.description && !fields.description.value) fields.description.value = metadata.description;
                    if (metadata.image && !fields.image.value && !fields.file.files.length) {
                        fields.image.value = metadata.image;
                        showPreview(metadata.image);
                    }
                }
                status.textContent = metadata.message || (metadata.success ? 'Informations récupérées ✔' : '');
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = '';
            } finally {
                status.classList.remove('is-loading');
            }
        }, 600);
    });
}

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
