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
    await first.press('Enter');
    await owner.keyboard.type('Tome 2');
    await owner.click('[data-collection-add]');
    await owner.keyboard.type('Tome 3');
    await owner.screenshot({ path: `${__dirname}/out/collection-form.png` });
    await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-object-form-submit]')]);

    let card = owner.locator('.card', { hasText: 'Collection test BD' });
    const id = await card.getAttribute('data-object');
    check(await card.locator('.item').count() === 3, 'collection créée avec 3 éléments');
    check((await card.locator('.card__badge--idea').innerText()).includes('Collection · 3'), 'badge « Collection · 3 »');
    check(await card.locator('.item__form, .item__by').count() === 0, 'propriétaire : aucune case ni réservation visible');

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
    await rows.nth(0).locator('input[type=text]').fill('Tome 1 (édition collector)');
    await rows.nth(2).locator('[data-collection-remove]').click();
    await owner.click('[data-collection-add]');
    await owner.keyboard.type('Tome 4');
    await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-object-form-submit]')]);
    const names = await owner.locator(`#idea-${id} .item__name`).allInnerTexts();
    check(JSON.stringify(names) === JSON.stringify(['Tome 1 (édition collector)', 'Tome 2', 'Tome 4']), 'éléments modifiés : ' + names.join(', '));

    // La réservation de l'ami est conservée.
    await friend.reload();
    card = friend.locator(`#idea-${id}`);
    check(await card.locator('.item--mine .item__name').innerText() === 'Tome 2', 'réservation conservée après modification');

    // L'ami libère son élément.
    await card.locator('.item--mine .item__check').click();
    await friend.waitForFunction((i) => !document.querySelector(`#idea-${i} .item--mine`), id);
    check((await card.locator('.collection-pill').innerText()).includes('0 / 3'), 'ami : libère Tome 2');

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
