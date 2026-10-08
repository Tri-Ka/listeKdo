/*
 * Test des collections (plusieurs éléments dans une idée), sur le site local. Lancé par run.sh.
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
const OWNER = 'e3370a0bb2c2ea49f93b68c0649d57b6';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

async function session(browser, name) {
    const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    page.on('dialog', (d) => d.accept());
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    await page.goto(`${B}?user=${OWNER}`);
    return page;
}

(async () => {
    const browser = await chromium.launch();

    // Le propriétaire crée une collection.
    const owner = await session(browser, 'Etienne');
    await owner.click('.card--add');
    await owner.fill('#object-form-dialog input[name=nom]', 'Collection test BD');
    await owner.check('[data-collection-toggle]');
    const first = owner.locator('[data-collection-list] input[name="items[]"]').first();
    await first.fill('Tome 1');
    await owner.locator('[data-collection-list] input[name="item_links[]"]').first().fill('example.com/tome-1?edition=collector&lang=fr');
    await first.press('Enter');
    await owner.keyboard.type('Tome 2');
    await owner.locator('[data-collection-list] input[name="item_links[]"]').nth(1).fill('google.fr');
    await owner.click('[data-collection-add]');
    await owner.keyboard.type('Tome 3');
    await owner.screenshot({ path: `${__dirname}/out/collection-form.png` });
    await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-object-form-submit]')]);

    let card = owner.locator('.card', { hasText: 'Collection test BD' });
    const id = await card.getAttribute('data-object');
    check(await card.locator('.item').count() === 3, 'collection créée avec 3 éléments');
    check((await card.locator('.card__badge--idea').innerText()).includes('Collection · 3'), 'badge « Collection · 3 »');
    check(await card.locator('.item__form, .item__by').count() === 0, 'propriétaire : aucune case ni réservation visible');
    check(await card.locator('.item__link').first().getAttribute('href') === 'https://example.com/tome-1?edition=collector&lang=fr', 'lien sans protocole normalisé en https avec chemin et paramètres');
    check(await card.locator('.item__link').nth(1).getAttribute('href') === 'https://google.fr', 'google.fr accepté et enregistré en https://google.fr');
    await card.screenshot({ path: `${__dirname}/out/collection-owner.png` });
    await owner.setViewportSize({ width: 390, height: 844 });
    await card.screenshot({ path: `${__dirname}/out/collection-owner-mobile.png` });
    await owner.setViewportSize({ width: 1366, height: 900 });

    // Un ami réserve « Tome 2 ».
    const friend = await session(browser, 'Mallory');
    card = friend.locator(`#idea-${id}`);
    check((await card.locator('.collection-pill').innerText()).includes('0 / 3'), 'ami : compteur 0 / 3');
    await card.locator('.item', { hasText: 'Tome 2' }).locator('.item__check').click();
    await friend.waitForSelector(`#idea-${id} .item--mine`);
    check((await card.locator('.collection-pill').innerText()).includes('1 / 3'), 'ami : réserve Tome 2, compteur 1 / 3');
    await friend.screenshot({ path: `${__dirname}/out/collection-friend.png`, clip: await card.boundingBox() });

    // Fiche détaillée : même état.
    await card.locator('.card__image').click();
    check(await friend.locator(`#object-${id} .item--mine`).count() === 1, 'fiche : Tome 2 coché');
    await friend.keyboard.press('Escape');

    // Le propriétaire modifie : renomme Tome 1, retire Tome 3, ajoute Tome 4.
    await owner.reload();
    await owner.locator(`#idea-${id} [data-card-menu] summary`).click();
    await owner.locator(`#idea-${id} [data-open-object-form]`).click();
    const rows = owner.locator('[data-collection-list] li');
    check(await rows.count() === 3, 'modification : 3 éléments pré-remplis');
    check(await rows.nth(0).locator('input[name="item_links[]"]').inputValue() === 'https://example.com/tome-1?edition=collector&lang=fr', 'lien pré-rempli à la modification');
    await rows.nth(0).locator('input[name="items[]"]').fill('Tome 1 (édition collector)');
    await rows.nth(2).locator('[data-collection-remove]').click();
    await owner.click('[data-collection-add]');
    await owner.keyboard.type('Tome 4');
    await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-object-form-submit]')]);
    const names = (await owner.locator(`#idea-${id} .item__name`).allInnerTexts()).map((name) => name.trim());
    check(JSON.stringify(names) === JSON.stringify(['Tome 1 (édition collector)', 'Tome 2', 'Tome 4']), 'éléments modifiés : ' + names.join(', '));

    // La réservation de l'ami est conservée.
    await friend.reload();
    card = friend.locator(`#idea-${id}`);
    check((await card.locator('.item--mine .item__name').innerText()).trim() === 'Tome 2', 'réservation conservée après modification');

    // L'ami libère son élément.
    await card.locator('.item--mine .item__check').click();
    await friend.waitForFunction((i) => !document.querySelector(`#idea-${i} .item--mine`), id);
    check((await card.locator('.collection-pill').innerText()).includes('0 / 3'), 'ami : libère Tome 2');

    // Un élément peut être reçu sans dévoiler qui l'avait réservé.
    const itemId = await owner.locator(`#idea-${id} .item`, { hasText: 'Tome 2' }).locator('input[name=id]').inputValue();
    const post = async (page, action, values, csrf = true) => page.evaluate(async ({ action, values, csrf }) => {
        const response = await fetch(`actions/${action}.php`, {
            method: 'POST', headers: { Accept: 'application/json', ...(csrf ? { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content } : {}) },
            body: new URLSearchParams(values),
        });
        return { status: response.status, data: await response.json() };
    }, { action, values, csrf });
    check((await post(friend, 'itemReceived', { id: itemId })).status === 404, 'un ami ne peut pas marquer un élément reçu');
    check((await post(owner, 'itemReceived', { id: itemId }, false)).status === 419, 'réception : jeton CSRF obligatoire');
    check((await post(friend, 'addCollectionItem', { object_id: id, nom: 'Intrusion' })).status === 404, 'un ami ne peut pas ajouter à la collection');
    await post(friend, 'itemGifted', { id: itemId, gift: '1' });

    await owner.locator(`#idea-${id} .card__image`).click();
    await owner.locator(`#object-${id} .item`, { hasText: 'Tome 2' }).locator('.item__received').click();
    await owner.waitForSelector(`#object-${id} .item--received`);
    check(await owner.locator(`#object-${id}[open]`).count() === 1, 'réception dans la fiche : fenêtre conservée ouverte');
    check(await owner.locator(`#idea-${id} .item--received`).count() === 1, 'réception synchronisée sur la vignette');
    await owner.mouse.move(0, 0);
    await owner.locator(`#object-${id}`).screenshot({ path: `${__dirname}/out/collection-detail-received.png` });
    await owner.keyboard.press('Escape');
    await friend.reload();
    card = friend.locator(`#idea-${id}`);
    check(await card.locator('.item', { hasText: 'Tome 2' }).count() === 0, 'élément reçu masqué aux proches');
    check((await card.locator('.collection-pill').innerText()).includes('0 / 2'), 'compteur exclut les éléments reçus');
    check(await friend.locator('#my-gifts-dialog .my-gifts__item', { hasText: 'Tome 2' }).count() === 0, 'élément reçu retiré du récapitulatif des cadeaux à offrir');
    check((await post(friend, 'itemGifted', { id: itemId, gift: '1' })).status === 409, 'impossible de réserver un élément déjà reçu');

    await owner.reload();
    check(await owner.locator(`#idea-${id} .item--received`).count() === 1, 'statut reçu conservé au rechargement');
    await owner.locator(`#idea-${id} [data-card-menu] summary`).click();
    await owner.locator(`#idea-${id} [data-open-object-form]`).click();
    await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-object-form-submit]')]);
    check(await owner.locator(`#idea-${id} .item--received`).count() === 1, 'modification de la collection conserve le statut reçu');
    await owner.locator(`#idea-${id} .item--received .item__received`).click();
    await owner.waitForFunction((i) => !document.querySelector(`#idea-${i} .item--received`), id);

    // Les gemmes de chaque élément utilisent exactement le taux des idées.
    await owner.goto(B + 'admin.php?tab=badges');
    const rate = Number(await owner.inputValue('input[name="rate[ideas]"]'));
    await owner.goto(`${B}?user=${OWNER}`);
    const balance = async () => (await owner.evaluate(async () => (await (await fetch('actions/gems.php')).json()).balance));
    const before = await balance();
    const added = await post(owner, 'addCollectionItem', { object_id: id, nom: 'Tome 5', link: 'javascript:alert(1)' });
    check(added.data.ok && await balance() === before + rate, 'un nouvel élément rapporte exactement les gemmes d’une idée');
    await owner.reload();
    check(await owner.locator(`#idea-${id} .item`, { hasText: 'Tome 5' }).locator('a').count() === 0, 'lien javascript refusé');

    // Tous reçus : collection archivée ; chaque élément reste récupérable.
    const totalItems = await owner.locator(`#idea-${id} .item__received`).count();
    for (let receivedCount = 1; receivedCount <= totalItems; receivedCount++) {
        // Les réponses remplacent la carte ; on prend à chaque fois le premier élément non reçu.
        await owner.locator(`#idea-${id} .item:not(.item--received) .item__received`).first().click();
        await owner.waitForFunction(({ id, receivedCount }) => document.querySelectorAll(`#idea-${id} .item--received`).length === receivedCount, { id, receivedCount });
    }
    await owner.waitForFunction((i) => document.querySelector(`#idea-${i}`).dataset.received === '1', id);
    check(await owner.locator(`#idea-${id}`).getAttribute('data-received') === '1', 'tous reçus : collection archivée');
    await friend.reload();
    check(await friend.locator(`#idea-${id}`).count() === 0, 'collection entièrement reçue masquée aux proches');
    await owner.click('[data-filter="received"]');
    await owner.locator(`#idea-${id} .item--received .item__received`).first().click();
    await owner.waitForFunction((i) => document.querySelector(`#idea-${i}`).dataset.received === '0', id);
    await owner.click('[data-filter="all"]');
    check(await owner.locator(`#idea-${id} .item--received`).count() === 3, 'remettre un seul élément réactive la collection');

    // L'archivage de la collection entière et celui des éléments restent cohérents.
    await post(owner, 'toggleReceived', { id });
    const restored = await post(owner, 'itemReceived', { id: itemId });
    check(restored.data.ok && !restored.data.received, 'remettre un élément réactive aussi une collection archivée par son menu');
    await post(owner, 'toggleReceived', { id });
    await post(owner, 'toggleReceived', { id });
    await owner.reload();
    check(await owner.locator(`#idea-${id} .item--received`).count() === 0, 'remettre toute la collection restaure tous ses éléments');

    // Nettoyage : suppression par le propriétaire.
    await owner.locator(`#idea-${id} [data-card-menu] summary`).click();
    await owner.locator(`#idea-${id} [data-open-object-form]`).click();
    await owner.click('#object-form-dialog [data-delete-object]');
    await owner.click('#confirm-dialog [data-confirm-ok]');
    await owner.waitForSelector('.toast--success >> text=Idée supprimée');
    check(await owner.locator(`#idea-${id}`).count() === 0, 'collection supprimée');

    check(owner.errors.length + friend.errors.length === 0, 'aucune erreur JS ' + [...owner.errors, ...friend.errors].join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nCollections OK');
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
