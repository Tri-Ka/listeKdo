/*
 * Test de l'extension Chrome (extension-chrome/) sur le site local.
 * Lancé par run.sh, qui fournit les mots de passe de test.
 */
const { chromium } = require('playwright-core');
const crypto = require('crypto');
const B = 'http://localhost:8090/listeKdo/';
const PRODUCT = 'http://localhost:8090/docker/tests/fixtures/product.html';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

// Identifiant d'une extension non empaquetée : dérivé de son chemin.
const extensionId = (path) => [...crypto.createHash('sha256').update(path).digest('hex').slice(0, 32)]
    .map((c) => String.fromCharCode(97 + parseInt(c, 16))).join('');

async function openPopup(context, id) {
    const popup = await context.newPage();
    await popup.goto(`chrome-extension://${id}/popup.html`);
    const tabId = await popup.evaluate(async (url) => (await chrome.tabs.query({})).find((t) => t.url?.startsWith(url))?.id, PRODUCT);
    await popup.evaluate(() => chrome.storage.local.set({ site: 'http://localhost:8090/listeKdo/' }));
    await popup.goto(`chrome-extension://${id}/popup.html?tabId=${tabId}`);
    return popup;
}

async function post(page, action, values) {
    return page.evaluate(async ({ action, values }) => (await fetch(`actions/${action}.php`, {
        method: 'POST', body: new URLSearchParams(values),
        headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content },
    })).json(), { action, values });
}

(async () => {
    const id = extensionId('/ext');
    const context = await chromium.launchPersistentContext('', {
        channel: 'chromium',
        args: ['--disable-extensions-except=/ext', '--load-extension=/ext'],
        viewport: { width: 1200, height: 800 },
    });
    const errors = [];

    const product = await context.newPage();
    await product.goto(PRODUCT);

    // Non connecté : l'extension propose d'ouvrir le site.
    let popup = await openPopup(context, id);
    popup.on('pageerror', (e) => errors.push(e.message));
    await popup.waitForSelector('[data-view="login"]:not([hidden])');
    check(true, 'non connecté : invitation à se connecter');
    check(await popup.locator('[data-update]').isHidden(), 'pas de bannière de mise à jour (même version que le site)');
    await popup.close();

    // Version plus récente sur le site : bannière de mise à jour.
    await context.route('**/download/extension-version.txt*', (route) => route.fulfill({ body: '9.9.9\n' }));
    popup = await openPopup(context, id);
    await popup.waitForSelector('[data-update]:not([hidden])');
    check(await popup.textContent('[data-update-version]') === '9.9.9', 'bannière : nouvelle version 9.9.9');
    await popup.screenshot({ path: `${__dirname}/out/extension-update.png` });
    await popup.close();
    await context.unroute('**/download/extension-version.txt*');

    // Connexion sur le site, dans le même navigateur.
    const site = await context.newPage();
    await site.goto(B);
    await site.click('.topbar [data-open="login-dialog"]');
    await site.fill('#login-dialog input[name=nom]', 'Etienne');
    await site.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([site.waitForNavigation(), site.click('#login-dialog button[type=submit]')]);

    popup = await openPopup(context, id);
    popup.on('pageerror', (e) => errors.push(e.message));
    await popup.waitForSelector('[data-view="form"]:not([hidden])');
    check((await popup.textContent('[data-user]')).includes('Etienne'), 'connecté : liste d\'Etienne');
    check(await popup.inputValue('input[name=nom]') === 'Produit test extension', 'nom lu depuis schema.org');
    const description = await popup.inputValue('textarea[name=description]');
    check(description === 'Une description venant de schema.org.', 'description : ' + JSON.stringify(description));
    check(await popup.inputValue('input[name=price]') === '29,90', 'prix dans son propre champ');
    const link = await popup.inputValue('input[name=link]');
    check(link.includes('id=42') && !link.includes('utm_source'), 'lien canonique sans utm : ' + link);
    check(await popup.locator('[data-thumbs] button').count() === 2, 'deux images proposées');
    await popup.locator('[data-thumbs] button').nth(1).click();
    check((await popup.inputValue('input[name=image]')).endsWith('product-2.jpg'), 'choix de la 2e image');
    await popup.screenshot({ path: `${__dirname}/out/extension-form.png` });

    await popup.click('[data-submit]');
    await popup.waitForSelector('[data-view="done"]:not([hidden])');
    check(true, 'produit ajouté');
    await popup.screenshot({ path: `${__dirname}/out/extension-done.png` });

    await site.goto(`${B}?user=e3370a0bb2c2ea49f93b68c0649d57b6`);
    const card = site.locator('.card', { hasText: 'Produit test extension' });
    check((await card.locator('.card__price').innerText()).replace(/\s/g, ' ') === '29,90 €', 'prix affiché sur la vignette');
    check(await card.count() === 1, 'idée visible sur la liste');
    check((await card.locator('.card__image img').getAttribute('src')).endsWith('product-2.jpg'), 'image choisie enregistrée');

    // Compléter une collection existante sans créer une autre idée.
    const collection = await site.evaluate(async () => {
        const body = new URLSearchParams({ nom: 'Collection test extension', collection: '1', 'items[]': 'Élément déjà présent', 'item_links[]': 'https://example.com/existant' });
        const response = await fetch('actions/addObject.php', {
            method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content },
        });
        return response.json();
    });
    check(collection.ok, 'collection de destination créée');
    await post(site, 'addChild', { nom: 'Test1collection-extension', theme: 'wishlist' });
    const managed = await site.evaluate(async () => (await (await fetch('actions/me.php')).json()).lists);
    const child = managed.find((list) => list.nom === 'Test1collection-extension');
    const childCollection = await post(site, 'addObject', { owner: child.code, nom: 'Collection secondaire test extension', collection: '1', 'items[]': 'Élément secondaire' });
    popup = await openPopup(context, id);
    await popup.waitForSelector('[data-view="form"]:not([hidden])');
    await popup.selectOption('[data-lists]', child.code);
    check(await popup.locator(`[data-collections] option[value="${collection.id}"]`).count() === 0, 'liste secondaire : collection personnelle exclue');
    await popup.selectOption('[data-collections]', String(childCollection.id));
    await popup.click('[data-submit]');
    await popup.waitForSelector('[data-view="done"]:not([hidden])');
    await site.goto(`${B}?user=${child.code}`);
    check(await site.locator(`#idea-${childCollection.id} .item`).count() === 2, 'extension : ajout dans une collection secondaire gérée');
    await site.goto(`${B}?user=e3370a0bb2c2ea49f93b68c0649d57b6`);
    popup = await openPopup(context, id);
    popup.on('pageerror', (e) => errors.push(e.message));
    await popup.waitForSelector('[data-view="form"]:not([hidden])');
    await popup.selectOption('[data-lists]', 'e3370a0bb2c2ea49f93b68c0649d57b6');
    await popup.selectOption('[data-collections]', String(collection.id));
    check(await popup.textContent('[data-submit-label]') === 'Ajouter à la collection', 'destination : ajouter à une collection');
    check(await popup.isHidden('.gallery') && await popup.isHidden('[data-price-field]') && await popup.isHidden('[data-description-field]'), 'élément : seuls le nom et le lien sont proposés');
    await popup.screenshot({ path: `${__dirname}/out/extension-collection.png` });
    await popup.click('[data-submit]');
    await popup.waitForSelector('[data-view="done"]:not([hidden])');
    check(await popup.textContent('[data-done-title]') === "C'est dans la collection !", 'élément ajouté depuis l’extension');
    await site.reload();
    const collectionCard = site.locator(`#idea-${collection.id}`);
    check(await collectionCard.locator('.item').count() === 2, 'la collection contient l’ancien élément et le produit');
    check(await collectionCard.locator('.item__link').first().getAttribute('href') === 'https://example.com/existant', 'ancien lien conservé');
    check(await collectionCard.locator('.item__link').nth(1).getAttribute('href') === link, 'lien canonique enregistré sur le nouvel élément');
    check(await site.locator('.card', { hasText: 'Produit test extension' }).count() === 2, 'ajout dans une collection sans idée supplémentaire');

    // Suggestion pour un ami (le premier du groupe « Suggérer à un ami ») : il ne la verra pas.
    popup = await openPopup(context, id);
    popup.on('pageerror', (e) => errors.push(e.message));
    await popup.waitForSelector('[data-view="form"]:not([hidden])');
    check(await popup.locator('[data-lists] optgroup').count() === 2, 'choix : « Mes listes » et « Suggérer à un ami »');
    check(await popup.isHidden('[data-suggest-note]'), 'sa propre liste : pas de message de suggestion');
    const target = await popup.locator('[data-lists] optgroup').nth(1).locator('option').first()
        .evaluate((option) => ({ code: option.value, nom: option.textContent }));
    await popup.selectOption('[data-lists]', target.code);
    check(await popup.isHidden('[data-collection-field]') && await popup.inputValue('[data-collections]') === '', 'suggestion : les collections personnelles ne sont pas proposées');
    check((await popup.textContent('[data-suggest-note]')).includes('ne verra jamais'), 'ami choisi : « … ne verra jamais cette idée »');
    check((await popup.textContent('[data-submit-label]')) === `Suggérer à ${target.nom}`, `bouton « Suggérer à ${target.nom} »`);
    await popup.screenshot({ path: `${__dirname}/out/extension-suggest.png` });
    await popup.click('[data-submit]');
    await popup.waitForSelector('[data-view="done"]:not([hidden])');
    check((await popup.textContent('[data-done-title]')) === "C'est suggéré !", 'suggestion envoyée');

    await site.goto(`${B}?user=${target.code}`);
    const suggested = site.locator('.card--suggestion', { hasText: 'Produit test extension' });
    check(await suggested.count() === 1, `suggestion visible sur la liste de ${target.nom}, avec son étiquette`);

    await site.evaluate(async (objectId) => fetch('actions/deleteObject.php', {
        method: 'POST', body: new URLSearchParams({ id: objectId }),
        headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content },
    }), String(collection.id));
    check((await post(site, 'deleteChild', { owner: child.code })).ok, 'liste secondaire de test supprimée');

    check(errors.length === 0, 'aucune erreur JS ' + errors.join(' | '));
    await context.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nExtension OK');
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
