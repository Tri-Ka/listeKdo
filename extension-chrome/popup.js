/*
 * Fenêtre de l'extension : lit le produit de l'onglet, puis l'ajoute à la liste
 * via le site (actions/addObject.php), avec la session déjà ouverte dans Chrome.
 */

const $ = (selector) => document.querySelector(selector);
const form = $('[data-view="form"]');
const fields = form.elements;

let site = DEFAULT_SITE;
let account = null;
let images = [];

function show(view) {
    document.querySelectorAll('[data-view]').forEach((element) => {
        element.hidden = element.dataset.view !== view;
    });
}

function warn(message, isError = false) {
    const warning = $('[data-warning]');
    warning.hidden = !message;
    warning.querySelector('span').textContent = message || '';
    warning.classList.toggle('is-error', isError);
}

/* ---------- Site et compte ---------- */

async function loadSettings() {
    site = await currentSite();
    $('[data-site]').value = site;
    $('[data-version]').textContent = `v${chrome.runtime.getManifest().version}`;
}

$('[data-site]').addEventListener('change', async (event) => {
    await chrome.storage.local.set({ site: event.target.value });
    location.reload();
});

async function fetchAccount() {
    const response = await fetch(`${site}actions/me.php`, {
        credentials: 'include',
        headers: { Accept: 'application/json' },
        cache: 'no-store',
    });
    const data = await response.json().catch(() => null);
    return data?.ok ? data : null;
}

/* ---------- Mise à jour ---------- */

async function showUpdate() {
    const latest = await checkUpdate(site);
    showUpdateBadge(latest);
    if (!latest) return;

    $('[data-update-version]').textContent = latest;
    $('[data-current-version]').textContent = chrome.runtime.getManifest().version;
    $('[data-update]').hidden = false;
}

$('[data-update-download]').addEventListener('click', async () => {
    const id = await chrome.downloads.download({ url: `${site}download/liste-kdo-extension.zip?t=${Date.now()}`, filename: 'liste-kdo-extension.zip' });
    $('[data-update-steps]').hidden = false;
    // Ouvre le dossier de téléchargement sur le fichier, une fois celui-ci terminé.
    chrome.downloads.onChanged.addListener(function opened(delta) {
        if (delta.id === id && delta.state?.current === 'complete') {
            chrome.downloads.show(id);
            chrome.downloads.onChanged.removeListener(opened);
        }
    });
});

// Une extension non empaquetée se recharge depuis son dossier : les nouveaux fichiers sont pris en compte.
$('[data-update-reload]').addEventListener('click', () => chrome.runtime.reload());

/* ---------- Page en cours ---------- */

async function currentTab() {
    // ?tabId=… sert aux tests automatisés ; sinon, l'onglet actif.
    const tabId = Number(new URLSearchParams(location.search).get('tabId'));
    if (tabId) return chrome.tabs.get(tabId);

    const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
    return tab;
}

async function readProduct(tab) {
    try {
        const [result] = await chrome.scripting.executeScript({ target: { tabId: tab.id }, func: extractProduct });
        return result?.result || null;
    } catch {
        // Pages protégées (chrome://, Chrome Web Store, PDF…).
        return null;
    }
}

function formatPrice(price, currency) {
    const amount = Number(String(price).replace(',', '.'));
    if (!amount) return '';
    try {
        return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: currency || 'EUR' }).format(amount);
    } catch {
        return `${amount} ${currency || ''}`.trim();
    }
}

/* ---------- Galerie ---------- */

function selectImage(url) {
    fields.image.value = url || '';
    const index = images.indexOf(url);
    const count = $('[data-count]');
    count.hidden = images.length < 2;
    count.textContent = `${index + 1} / ${images.length}`;
    const preview = $('[data-preview]');
    preview.hidden = !url;
    $('[data-no-image]').hidden = Boolean(url);
    if (url) preview.src = url;
    document.querySelectorAll('[data-thumbs] button').forEach((button) => {
        const selected = button.dataset.url === url;
        button.setAttribute('aria-pressed', selected);
        if (selected) button.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    });
}

function renderGallery(list) {
    images = list;
    const thumbs = $('[data-thumbs]');
    thumbs.replaceChildren();
    document.querySelectorAll('.gallery__nav').forEach((button) => { button.hidden = images.length < 2; });

    if (images.length > 1) {
        for (const url of images) {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.url = url;
            button.title = 'Choisir cette image';
            const image = document.createElement('img');
            image.src = url;
            image.alt = '';
            // Image cassée : on retire la vignette et l'image de la liste.
            image.addEventListener('error', () => {
                button.remove();
                const wasSelected = fields.image.value === url;
                images = images.filter((other) => other !== url);
                selectImage(wasSelected ? images[0] || '' : fields.image.value);
            });
            button.append(image);
            button.addEventListener('click', () => selectImage(url));
            thumbs.append(button);
        }
    }

    selectImage(images[0] || '');
}

// Flèches sur l'image principale : image précédente / suivante, en boucle.
document.querySelectorAll('.gallery__nav').forEach((button) => {
    button.addEventListener('click', () => {
        const index = images.indexOf(fields.image.value);
        selectImage(images[(index + Number(button.dataset.step) + images.length) % images.length]);
    });
});

/* ---------- Liste choisie : la sienne, ou une suggestion pour un ami ---------- */

const selectedList = () => fields.owner.selectedOptions[0];
const isSuggestion = () => selectedList()?.dataset.suggest === '1';
const submitLabel = () => (isSuggestion() ? `Suggérer à ${selectedList().textContent}` : 'Ajouter à la liste');

function refreshTarget() {
    const suggest = isSuggestion();
    const note = $('[data-suggest-note]');
    note.hidden = !suggest;
    if (suggest) {
        note.querySelector('span').textContent = `${selectedList().textContent} ne verra jamais cette idée. Ses autres amis la verront avec votre nom, et pourront la réserver.`;
    }
    $('[data-submit-label]').textContent = submitLabel();
    $('[data-submit-icon]').setAttribute('href', suggest ? '#i-lightbulb' : '#i-gift');
}

/* ---------- Envoi ---------- */

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = $('[data-submit]');
    submit.disabled = true;
    $('[data-submit-label]').textContent = 'Ajout en cours…';
    warn('');

    try {
        const body = new FormData();
        body.append('nom', fields.nom.value.trim());
        body.append('description', fields.description.value.trim());
        body.append('link', fields.link.value.trim());
        body.append('image', fields.image.value);
        body.append('owner', fields.owner.value);
        body.append('price', fields.price.value.trim());
        const suggest = isSuggestion();
        if (suggest) body.append('suggest', '1');

        const response = await fetch(`${site}actions/addObject.php`, {
            method: 'POST',
            body,
            credentials: 'include',
            headers: { Accept: 'application/json', 'X-CSRF-Token': account.token },
        });
        const data = await response.json().catch(() => null);

        if (!data?.ok) throw new Error(data?.message || "L'ajout a échoué, réessayez.");

        $('[data-open-list]').onclick = () => {
            chrome.tabs.create({ url: `${site}index.php?user=${encodeURIComponent(fields.owner.value)}#new-${data.id}` });
            window.close();
        };
        const doneImage = $('[data-done-image]');
        doneImage.hidden = !fields.image.value;
        if (fields.image.value) doneImage.src = fields.image.value;
        $('[data-done-name]').textContent = suggest
            ? `${fields.nom.value.trim()} : ${selectedList().textContent} ne la verra pas.`
            : fields.nom.value.trim();
        $('[data-done-title]').textContent = suggest ? "C'est suggéré !" : "C'est sur la liste !";
        show('done');
    } catch (error) {
        warn(error.message, true);
    } finally {
        submit.disabled = false;
        $('[data-submit-label]').textContent = submitLabel();
    }
});

$('[data-close-popup]').addEventListener('click', () => window.close());

$('[data-open-site]').addEventListener('click', () => {
    chrome.tabs.create({ url: site });
    window.close();
});

/* ---------- Démarrage ---------- */

(async () => {
    await loadSettings();
    showUpdate();

    const tab = await currentTab();
    const [accountData, product] = await Promise.all([
        fetchAccount().catch(() => null),
        tab ? readProduct(tab) : null,
    ]);

    if (!accountData) {
        $('[data-user]').textContent = 'Non connecté';
        show('login');
        return;
    }

    account = accountData;
    $('[data-user]').textContent = `Connecté : ${account.user.nom}`;
    // Mêmes couleurs que la liste de l'utilisateur sur le site.
    if (account.user.theme) document.body.dataset.theme = account.user.theme;

    // Choix de la liste : la sienne ou celle d'un enfant géré, ou une suggestion pour un ami
    // (mémorisé pour la prochaine fois).
    const lists = account.lists || [{ code: account.user.code, nom: 'Ma liste' }];
    const friends = account.friends || [];
    const select = fields.owner;
    const mine = friends.length ? document.createElement('optgroup') : select;
    if (friends.length) {
        mine.label = 'Mes listes';
        select.append(mine);
    }
    for (const list of lists) mine.append(new Option(list.nom, list.code));
    if (friends.length) {
        const group = document.createElement('optgroup');
        group.label = 'Suggérer à un ami (il ne la verra pas)';
        for (const friend of friends) {
            const option = new Option(friend.nom, friend.code);
            option.dataset.suggest = '1';
            group.append(option);
        }
        select.append(group);
    }
    const codes = lists.concat(friends).map((list) => list.code);
    const { lastList } = await chrome.storage.local.get('lastList');
    if (codes.includes(lastList)) select.value = lastList;
    select.addEventListener('change', () => {
        chrome.storage.local.set({ lastList: select.value });
        refreshTarget();
    });
    $('[data-list-field]').hidden = codes.length < 2;
    refreshTarget();
    $('[data-price-field]').hidden = !account.prices;
    $('[data-name-row]').classList.toggle('has-price', Boolean(account.prices));
    const avatar = $('[data-avatar]');
    avatar.src = /^(https?|data):/.test(account.user.avatar) ? account.user.avatar : site + account.user.avatar;
    avatar.hidden = false;

    if (product) {
        const price = formatPrice(product.price, product.currency);
        fields.nom.value = product.title;
        // Prix : champ dédié si le site le gère, sinon ajouté à la description.
        if (account.prices) {
            fields.price.value = price.replace(/[^\d,]/g, '');
            fields.description.value = product.description;
        } else {
            fields.description.value = [product.description, price ? `Prix indicatif : ${price}` : ''].filter(Boolean).join('\n\n');
        }
        fields.link.value = product.link;
        renderGallery(product.images);
    } else {
        fields.link.value = /^https?:/.test(tab?.url || '') ? tab.url : '';
        renderGallery([]);
        warn("Impossible de lire cette page : remplissez les informations à la main.");
    }

    show('form');
    fields.nom.focus();
    fields.nom.select();
})();
