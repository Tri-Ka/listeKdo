/*
 * Parrainage : lien ?parrain=CODE, inscription avec le code (bonus de bienvenue), code inconnu refusé,
 * gemmes du parrain à la première idée du filleul, notification. Le compte « Test1parrain… » est supprimé par run.sh.
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };
const balance = async (page) => Number((await page.locator('.gem-pill__value').textContent()).trim());

async function session(browser, name, password = 'test') {
    const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', password);
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    return page;
}

(async () => {
    const browser = await chromium.launch();
    const sponsor = await session(browser, 'Etienne');
    const before = await balance(sponsor);

    // Code et lien dans la boutique
    await sponsor.click('.gem-pill');
    const code = (await sponsor.locator('.referral__value').innerText()).trim();
    const link = await sponsor.locator('[data-referral-link]').inputValue();
    check(/^[A-Z2-9]{6}$/.test(code) && link.endsWith('?parrain=' + code), 'boutique : code de parrainage ' + code + ' et lien');

    // Visiteur : le lien ouvre l'inscription avec le code prérempli
    const visitor = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    visitor.errors = [];
    visitor.on('pageerror', (e) => visitor.errors.push(e.message));
    await visitor.goto(B + '?parrain=' + code);
    check(await visitor.locator('#signup-dialog[open]').count() === 1 && await visitor.inputValue('#signup-dialog input[name=referral]') === code, 'lien : inscription ouverte, code prérempli');

    // Code inconnu refusé
    const name = 'Test1parrain' + Date.now();
    await visitor.fill('#signup-dialog input[name=nom]', name);
    await visitor.fill('#signup-dialog input[name=password]', 'test');
    await visitor.fill('#signup-dialog input[name=re-password]', 'test');
    await visitor.fill('#signup-dialog input[name=referral]', 'ZZZZZZ');
    await Promise.all([visitor.waitForNavigation(), visitor.click('#signup-dialog button[type=submit]')]);
    check(await visitor.locator('.toast--error').count() === 1, 'code de parrainage inconnu refusé');

    // Inscription avec le bon code : bonus de bienvenue
    await visitor.goto(B + '?parrain=' + code);
    await visitor.fill('#signup-dialog input[name=nom]', name);
    await visitor.fill('#signup-dialog input[name=password]', 'test');
    await visitor.fill('#signup-dialog input[name=re-password]', 'test');
    await Promise.all([visitor.waitForNavigation(), visitor.click('#signup-dialog button[type=submit]')]);
    await visitor.evaluate(() => document.getElementById('secret-invite-dialog')?.close());
    check(await balance(visitor) >= 50, 'filleul : bonus de bienvenue (' + await balance(visitor) + ' gemmes)');

    // Pas encore d'idée : le parrain ne gagne rien
    await sponsor.reload();
    check(await balance(sponsor) === before, 'parrain : rien tant que le filleul n\'a pas d\'idée');

    // Première idée du filleul
    // La fenêtre « Protégez votre compte » s'ouvre toute seule après l'inscription : on attend qu'elle soit là pour la fermer.
    await visitor.waitForSelector('#secret-invite-dialog[open]', { timeout: 3000 }).catch(() => {});
    await visitor.evaluate(() => document.getElementById('secret-invite-dialog')?.close());
    await visitor.click('.card--add');
    await visitor.waitForSelector('#object-form-dialog[open]');
    await visitor.fill('#object-form-dialog input[name=nom]', 'Première idée de test parrainage');
    await Promise.all([visitor.waitForNavigation(), visitor.click('#object-form-dialog [data-object-form-submit]')]);
    await sponsor.reload();
    check(await balance(sponsor) - before === 200 + 0, 'parrain : +200 gemmes à la première idée (' + before + ' -> ' + await balance(sponsor) + ')');
    await sponsor.click('[data-toggle-notifications][aria-controls]');
    await sponsor.waitForTimeout(400);
    check(await sponsor.locator('.notification', { hasText: 'a ajouté sa première idée' }).count() === 1, 'parrain : notification « a ajouté sa première idée »');

    check(sponsor.errors.length + visitor.errors.length === 0, 'aucune erreur JS ' + [...sponsor.errors, ...visitor.errors].join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nParrainage OK');
    process.exit(failures ? 1 : 0);
})();
