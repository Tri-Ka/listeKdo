/*
 * Fenêtre de l'extension : lit le produit de l'onglet, puis l'ajoute à la liste
 * via le site (actions/addObject.php), avec la session déjà ouverte dans Chrome.
 */

const $ = (selector) => document.querySelector(selector);
const form = $('[data-view="form"]');
const fields = form.elements;

let site = 'http://datcharrye.free.fr/listeKdo/';
let account = null;

function show(view) {
    document.querySelectorAll('[data-view]').forEach((element) => {
        element.hidden = element.dataset.view !== view;
    });
}

function warn(message, isError = false) {
    const warning = $('[data-warning]');
    warning.hidden = !message;
    warning.textContent = message || '';
    warning.classList.toggle('is-error', isError);
}

/* ---------- Site et compte ---------- */

async function loadSettings() {
    const stored = await chrome.storage.local.get('site');
    if (stored.site) site = stored.site;
    $('[data-site]').value = site;
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
    const preview = $('[data-preview]');
    preview.hidden = !url;
    $('[data-no-image]').hidden = Boolean(url);
    if (url) preview.src = url;
    document.querySelectorAll('[data-thumbs] button').forEach((button) => {
        button.setAttribute('aria-pressed', button.dataset.url === url);
    });
}

function renderGallery(images) {
    const thumbs = $('[data-thumbs]');
    thumbs.replaceChildren();

    if (images.length > 1) {
        for (const url of images) {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.url = url;
            button.title = 'Choisir cette image';
            const image = document.createElement('img');
            image.src = url;
            image.alt = '';
            // Image cassée : on retire la vignette.
            image.addEventListener('error', () => button.remove());
            button.append(image);
            button.addEventListener('click', () => selectImage(url));
            thumbs.append(button);
        }
    }

    selectImage(images[0] || '');
}

/* ---------- Envoi ---------- */

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = $('[data-submit]');
    submit.disabled = true;
    submit.textContent = 'Ajout en cours…';
    warn('');

    try {
        const body = new FormData();
        body.append('nom', fields.nom.value.trim());
        body.append('description', fields.description.value.trim());
        body.append('link', fields.link.value.trim());
        body.append('image', fields.image.value);
        body.append('owner', fields.owner.value);
        body.append('price', fields.price.value.trim());

        const response = await fetch(`${site}actions/addObject.php`, {
            method: 'POST',
            body,
            credentials: 'include',
            headers: { Accept: 'application/json', 'X-CSRF-Token': account.token },
        });
        const data = await response.json().catch(() => null);

        if (!data?.ok) throw new Error(data?.message || "L'ajout a échoué, réessayez.");

        $('[data-open-list]').onclick = () => {
            chrome.tabs.create({ url: `${site}index.php?user=${encodeURIComponent(fields.owner.value)}#card-${data.id}` });
            window.close();
        };
        show('done');
    } catch (error) {
        warn(error.message, true);
    } finally {
        submit.disabled = false;
        submit.textContent = 'Ajouter à ma liste';
    }
});

$('[data-open-site]').addEventListener('click', () => {
    chrome.tabs.create({ url: site });
    window.close();
});

/* ---------- Démarrage ---------- */

(async () => {
    await loadSettings();

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

    // Choix de la liste : la sienne ou celle d'un enfant géré (mémorisé pour la prochaine fois).
    const lists = account.lists || [{ code: account.user.code, nom: 'Ma liste' }];
    const select = fields.owner;
    for (const list of lists) select.add(new Option(list.nom, list.code));
    const { lastList } = await chrome.storage.local.get('lastList');
    if (lists.some((list) => list.code === lastList)) select.value = lastList;
    select.addEventListener('change', () => chrome.storage.local.set({ lastList: select.value }));
    $('[data-list-field]').hidden = lists.length < 2;
    $('[data-price-field]').hidden = !account.prices;
    const avatar = $('[data-avatar]');
    avatar.src = /^https?:/.test(account.user.avatar) ? account.user.avatar : site + account.user.avatar;
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
