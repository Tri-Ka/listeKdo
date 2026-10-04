/*
 * Suppression de son compte depuis « Mon profil » : mauvais mot de passe refusé, puis compte supprimé,
 * déconnexion et connexion impossible. Le compte « Test1suppr… » est supprimé par le test lui-même (sinon par run.sh).
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

(async () => {
    const browser = await chromium.launch();
    const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));

    // Inscription et une idée
    const name = 'Test1suppr' + Date.now();
    await page.goto(B);
    await page.click('[data-open="signup-dialog"] >> nth=0');
    await page.fill('#signup-dialog input[name=nom]', name);
    await page.fill('#signup-dialog input[name=password]', 'test');
    await page.fill('#signup-dialog input[name=re-password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#signup-dialog button[type=submit]')]);
    await page.waitForSelector('#secret-invite-dialog[open]', { timeout: 3000 }).catch(() => {});
    await page.evaluate(() => document.getElementById('secret-invite-dialog')?.close());
    await page.click('.card--add');
    await page.fill('#object-form-dialog input[name=nom]', 'Idée du compte supprimé');
    await Promise.all([page.waitForNavigation(), page.click('#object-form-dialog [data-object-form-submit]')]);

    // Zone repliée dans « Mon profil »
    const openZone = async () => {
        await page.click('.topbar .user-menu summary');
        await page.click('.user-menu [data-open="profile-dialog"]');
        await page.click('#profile-dialog .danger-zone summary');
    };
    await openZone();
    check(await page.locator('#profile-dialog .danger-zone input[name=current_password]').isVisible(), 'profil : zone « Supprimer mon compte »');
    await page.locator('#profile-dialog .danger-zone .btn--danger').scrollIntoViewIfNeeded();
    await page.screenshot({ path: 'out/account-delete.png' });

    // Mauvais mot de passe : refusé
    await page.fill('#profile-dialog input[name=current_password]', 'mauvais');
    await page.click('#profile-dialog .danger-zone .btn--danger');
    await Promise.all([page.waitForNavigation(), page.click('#confirm-dialog [data-confirm-ok]')]);
    check(await page.locator('.toast--error').count() === 1, 'mauvais mot de passe refusé');

    // Bon mot de passe : compte supprimé et déconnecté
    await openZone();
    await page.fill('#profile-dialog input[name=current_password]', 'test');
    await page.click('#profile-dialog .danger-zone .btn--danger');
    await Promise.all([page.waitForNavigation(), page.click('#confirm-dialog [data-confirm-ok]')]);
    check(await page.locator('.topbar [data-open="login-dialog"]').count() === 1, 'compte supprimé : déconnecté');
    check(/supprimé/.test(await page.locator('.toast').first().innerText().catch(() => '')), 'message de confirmation');

    // Connexion impossible
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    check(await page.locator('.topbar [data-open="login-dialog"]').count() === 1, 'connexion impossible après suppression');

    check(errors.length === 0, 'aucune erreur JS' + (errors.length ? ' : ' + errors.join(' | ') : ''));
    await browser.close();
    process.exit(failures ? 1 : 0);
})();
