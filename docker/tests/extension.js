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
    await popup.close();

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

    check(errors.length === 0, 'aucune erreur JS ' + errors.join(' | '));
    await context.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nExtension OK');
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
