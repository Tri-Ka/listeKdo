/*
 * Liste de Kdo : page d'administration (admin.php).
 * Le tableau est rendu par le serveur ; ici on le recharge en arrière-plan (recherche,
 * tri, filtres, pagination) et on envoie les actions sans quitter la page.
 */

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const csrfToken = $('meta[name="csrf-token"]')?.content ?? '';

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

/* ---------- Messages ---------- */

const toasts = $('[data-toasts]');

function toast(message, type = 'info') {
    const element = document.createElement('div');
    element.className = `toast toast--${type}`;
    element.setAttribute('role', type === 'error' ? 'alert' : 'status');
    element.textContent = message;
    // Une fenêtre ouverte passe au premier plan : le message doit être dedans pour rester visible.
    const dialog = document.querySelector('dialog[open]');
    let host = toasts;
    if (dialog) {
        host = $(':scope > .toasts', dialog) || dialog.appendChild(Object.assign(document.createElement('div'), { className: 'toasts' }));
    }
    host.append(element);
    dismissLater(element);
}

function dismissLater(element, delay = 4500) {
    setTimeout(() => {
        element.classList.add('is-leaving');
        element.addEventListener('transitionend', () => element.remove(), { once: true });
    }, delay);
}

$$('.toast', toasts).forEach((element) => dismissLater(element, 8000));

/* ---------- Fenêtres ---------- */

document.addEventListener('click', (event) => {
    const dialog = event.target.closest('dialog');
    if (dialog && event.target.closest('[data-close]')) dialog.close();
    // Clic sur le fond : ferme la fenêtre.
    if (event.target instanceof HTMLDialogElement) event.target.close();
});

const confirmDialog = $('#admin-confirm');

function confirmAction({ title, text, ok, danger = false }) {
    $('[data-confirm-title]', confirmDialog).textContent = title;
    $('[data-confirm-text]', confirmDialog).textContent = text;
    const button = $('[data-confirm-ok]', confirmDialog);
    button.textContent = ok;
    button.classList.toggle('btn--danger-solid', danger);
    confirmDialog.returnValue = '';
    confirmDialog.showModal();

    return new Promise((resolve) => {
        confirmDialog.addEventListener('close', () => resolve(confirmDialog.returnValue === 'ok'), { once: true });
    });
}

/* ---------- Infobulles ---------- */

const tooltip = document.createElement('div');
tooltip.className = 'tooltip';
tooltip.setAttribute('role', 'tooltip');
document.body.append(tooltip);

document.addEventListener('pointerover', (event) => {
    const target = event.target.closest('[data-tip]');
    if (!target || event.pointerType !== 'mouse') return;
    tooltip.textContent = target.dataset.tip;
    const box = target.getBoundingClientRect();
    const tip = tooltip.getBoundingClientRect();
    tooltip.style.left = `${Math.min(Math.max(4, box.left + box.width / 2 - tip.width / 2), innerWidth - tip.width - 4)}px`;
    tooltip.style.top = `${box.top - tip.height - 8 < 4 ? box.bottom + 8 : box.top - tip.height - 8}px`;
    tooltip.classList.add('is-visible');
});

document.addEventListener('pointerout', (event) => {
    if (event.target.closest('[data-tip]')) tooltip.classList.remove('is-visible');
});
document.addEventListener('scroll', () => tooltip.classList.remove('is-visible'), true);

/* ---------- Images de secours ---------- */

document.addEventListener('error', (event) => {
    const image = event.target;
    if (image instanceof HTMLImageElement && image.dataset.fallback && image.getAttribute('src') !== image.dataset.fallback) {
        image.src = image.dataset.fallback;
    }
}, true);

// Images déjà en erreur avant le chargement de ce script.
$$('img[data-fallback]').forEach((image) => {
    if (image.complete && image.naturalWidth === 0) image.src = image.dataset.fallback;
});

/* ---------- Tableau ---------- */

const table = $('[data-dt]');
const search = $('[data-dt-search]');
let controller = null;

async function load(url, { push = true } = {}) {
    controller?.abort();
    controller = new AbortController();
    table.setAttribute('aria-busy', 'true');

    const target = new URL(url, location.href);
    const partial = new URL(target);
    partial.searchParams.set('partial', '1');

    try {
        const response = await fetch(partial, { credentials: 'same-origin', signal: controller.signal });
        if (!response.ok) throw new Error();
        table.innerHTML = await response.text();
        if (push) history.replaceState(null, '', target);
        syncSearchForm(target);
    } catch (error) {
        if (error.name !== 'AbortError') toast('Impossible de charger le tableau.', 'error');
    } finally {
        table.removeAttribute('aria-busy');
    }
}

// Les champs cachés de la recherche suivent le tri et le filtre affichés.
function syncSearchForm(url) {
    $$('input[type="hidden"]', search).forEach((input) => {
        const value = url.searchParams.get(input.name);
        if (value !== null) input.value = value;
        else if (input.name === 'sort') input.value = 'nom';
        else if (input.name === 'dir') input.value = 'asc';
        else if (input.name === 'filter') input.value = 'all';
        else if (input.name === 'per') input.value = '25';
    });
}

function searchUrl() {
    const url = new URL(search.action, location.href);
    new FormData(search).forEach((value, key) => {
        if (value !== '') url.searchParams.set(key, value);
    });
    return url;
}

let searchTimer = null;
search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => load(searchUrl()), 250);
});

search.addEventListener('submit', (event) => {
    event.preventDefault();
    clearTimeout(searchTimer);
    load(searchUrl());
});

table.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-dt-link]');
    if (!link || event.metaKey || event.ctrlKey || event.shiftKey) return;
    event.preventDefault();
    load(link.href);
});

table.addEventListener('change', async (event) => {
    if (event.target.matches('[data-dt-per]')) {
        load(event.target.value);
        return;
    }

    // Changement de rôle.
    const form = event.target.closest('[data-admin-role]');
    if (!form) return;

    const select = event.target;
    const previous = [...select.options].find((option) => option.defaultSelected)?.value;
    // Lu avant de désactiver la liste : un champ désactivé n'est pas envoyé.
    const body = new FormData(form);
    select.disabled = true;

    try {
        const data = await post(form.action, body);
        [...select.options].forEach((option) => { option.defaultSelected = option.value === data.role; });
        select.className = `dt__select dt__select--${data.role}`;
        toast(data.message, 'success');
    } catch (error) {
        select.value = previous;
        toast(error.message, 'error');
    } finally {
        select.disabled = false;
    }
});

/* ---------- Mot de passe et suppression ---------- */

const passwordDialog = $('#admin-password');

$('[data-password-copy]', passwordDialog).addEventListener('click', async () => {
    const value = $('[data-password-value]', passwordDialog).textContent;
    try {
        await navigator.clipboard.writeText(value);
        toast('Mot de passe copié !', 'success');
    } catch {
        getSelection().selectAllChildren($('[data-password-value]', passwordDialog));
    }
});

table.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-admin-confirm]');
    if (!form) return;
    event.preventDefault();

    const name = form.dataset.name;
    const isDelete = form.dataset.adminConfirm === 'delete';
    const ideas = Number(form.dataset.ideas || 0);

    const confirmed = await confirmAction(isDelete
        ? {
            title: `Supprimer ${name} ?`,
            text: `Son compte, sa liste${ideas ? ` et ses ${ideas} idée${ideas > 1 ? 's' : ''}` : ''}, ses commentaires et ses réactions seront supprimés définitivement. Ses dons sur les listes des autres seront libérés.`,
            ok: 'Supprimer définitivement',
            danger: true,
        }
        : {
            title: `Nouveau mot de passe pour ${name} ?`,
            text: 'Un mot de passe provisoire va remplacer l\'actuel. Vous devrez le lui transmettre.',
            ok: 'Générer',
        });
    if (!confirmed) return;

    const button = $('button[type="submit"]', form);
    button.setAttribute('aria-busy', 'true');

    try {
        const data = await post(form.action, new FormData(form));
        if (isDelete) {
            toast(data.message, 'success');
            await load(location.href, { push: false });
        } else {
            $('[data-password-name]', passwordDialog).textContent = data.name;
            $('[data-password-value]', passwordDialog).textContent = data.password;
            passwordDialog.showModal();
        }
    } catch (error) {
        toast(error.message, 'error');
    } finally {
        button.removeAttribute('aria-busy');
    }
});

/* ---------- Lien de secours ---------- */

const linkDialog = $('#admin-link');
const linkValue = $('[data-link-value]', linkDialog);
const linkShare = $('[data-link-share]', linkDialog);
let linkText = '';

linkShare.hidden = !navigator.share;

$('[data-link-copy]', linkDialog).addEventListener('click', async () => {
    try {
        await navigator.clipboard.writeText(linkValue.value);
        toast('Lien copié !', 'success');
    } catch {
        linkValue.select();
        document.execCommand('copy');
    }
});

linkShare.addEventListener('click', () => {
    navigator.share({ title: 'Liste de Kdo', text: linkText }).catch(() => {});
});

table.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-admin-link]');
    if (!form) return;
    event.preventDefault();

    const button = $('button[type="submit"]', form);
    button.setAttribute('aria-busy', 'true');

    try {
        const data = await post(form.action, new FormData(form));
        linkText = `Bonjour ${data.name} ! Voici un lien pour choisir un nouveau mot de passe sur Liste de Kdo : ${data.url}`;
        $('[data-link-name]', linkDialog).textContent = data.name;
        $('[data-link-expires]', linkDialog).textContent = data.expires;
        $('[data-link-whatsapp]', linkDialog).href = `https://wa.me/?text=${encodeURIComponent(linkText)}`;
        linkValue.value = data.url;
        linkDialog.showModal();
        linkValue.select();
    } catch (error) {
        toast(error.message, 'error');
    } finally {
        button.removeAttribute('aria-busy');
    }
});

/* ---------- Parents d'une liste d'enfant ---------- */

const managersDialog = $('#admin-managers');
const managersList = $('[data-managers-list]', managersDialog);
const managersAdd = $('[data-managers-add]', managersDialog);
const parentInput = $('input', managersAdd);
let managersChild = null;
let managersChanged = false;

function managersBody(op, parent = '') {
    const body = new FormData();
    body.append('id', managersChild);
    body.append('op', op);
    if (parent) body.append('parent', parent);
    return body;
}

function renderManagers(data) {
    $('[data-managers-name]', managersDialog).textContent = data.name;
    $('[data-managers-state]', managersDialog).textContent = data.managers.length
        ? `C'est une liste d'enfant, gérée par ${data.managers.length > 1 ? 'ces parents' : 'ce parent'} :`
        : 'Ce n\'est pas une liste d\'enfant. Ajoutez un parent pour en faire une.';

    managersList.replaceChildren(...data.managers.map((manager) => {
        const item = document.createElement('li');
        const avatar = document.createElement('img');
        avatar.src = manager.avatar;
        avatar.alt = '';
        const name = document.createElement('strong');
        name.textContent = manager.nom;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'round-btn round-btn--sm';
        remove.dataset.removeManager = manager.id;
        remove.setAttribute('aria-label', `Retirer ${manager.nom}`);
        remove.dataset.tip = 'Retirer';
        // Même icône que le bouton de fermeture (sprite versionné).
        remove.append($('.modal__close svg', managersDialog).cloneNode(true));
        item.append(avatar, name, remove);
        return item;
    }));
}

async function managersCall(op, parent = '') {
    const data = await post('actions/adminManagers.php', managersBody(op, parent));
    renderManagers(data);
    if (op !== 'list') {
        managersChanged = true;
        toast(data.message, 'success');
    }
}

document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-admin-managers]');
    if (!trigger) return;

    managersChild = trigger.dataset.adminManagers;
    managersChanged = false;
    managersList.replaceChildren();
    $('[data-managers-name]', managersDialog).textContent = trigger.dataset.name;
    $('[data-managers-state]', managersDialog).textContent = 'Chargement…';
    parentInput.value = '';
    managersDialog.showModal();

    try {
        await managersCall('list');
    } catch (error) {
        toast(error.message, 'error');
    }
});

managersAdd.addEventListener('submit', async (event) => {
    event.preventDefault();
    const name = parentInput.value.trim().toLowerCase();
    const option = [...$$('#admin-parent-options option')].find((o) => o.value.trim().toLowerCase() === name);
    if (!option) {
        toast('Choisissez un parent dans la liste proposée.', 'error');
        return;
    }

    try {
        await managersCall('add', option.dataset.id);
        parentInput.value = '';
    } catch (error) {
        toast(error.message, 'error');
    }
});

managersList.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-remove-manager]');
    if (!button) return;
    button.setAttribute('aria-busy', 'true');

    try {
        await managersCall('remove', button.dataset.removeManager);
    } catch (error) {
        toast(error.message, 'error');
        button.removeAttribute('aria-busy');
    }
});

// Le tableau (colonne « Gérée par », étiquette « Enfant ») est rechargé après une modification.
managersDialog.addEventListener('close', () => {
    if (managersChanged) load(location.href, { push: false });
});
