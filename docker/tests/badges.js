/*
 * Badges : onglet d'administration (badges par défaut installés, ajout d'un badge), obtention et fête
 * « Nouveau badge ! », vitrine de sa liste (progression) et vue d'un ami (badges obtenus seulement).
 * Lancé par run.sh (Etienne = admin, Mallory = amie). Le badge de test (« Test1… ») est supprimé par run.sh.
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
const ETIENNE = 'e3370a0bb2c2ea49f93b68c0649d57b6';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

async function session(browser, name) {
    const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    return page;
}

(async () => {
    const browser = await chromium.launch();
    const admin = await session(browser, 'Etienne');
    const friend = await session(browser, 'Mallory');

    /* ---- Administration ---- */
    await admin.goto(B + 'admin.php?tab=badges');
    const rows = admin.locator('.badge-admin__row:not(.badge-admin__row--new)');
    check(await rows.count() >= 40, 'admin : badges par défaut installés (' + await rows.count() + ')');
    check((await rows.first().locator('input[name=name]').inputValue()) === 'Première idée', 'admin : accents conservés (« Première idée »)');

    const name = 'Test1badge' + Date.now();
    const blank = admin.locator('.badge-admin__row--new');
    await blank.locator('input[name=emoji]').fill('🧪');
    await blank.locator('input[name=name]').fill(name);
    await blank.locator('input[name=description]').fill('Badge créé par le test.');
    await blank.locator('select[name=metric]').selectOption('ideas');
    await blank.locator('input[name=threshold]').fill('1');
    await blank.locator('select[name=kind]').selectOption('trophy');
    await Promise.all([admin.waitForNavigation(), blank.locator('button[type=submit]').click()]);
    check(await admin.locator('.toast--success').count() === 1, 'admin : badge ajouté');

    /* ---- Obtention : fête « Nouveau trophée ! » à la visite suivante ---- */
    // (La fenêtre ne s'ouvre pas seule dans un navigateur de test : on vérifie son contenu.)
    await admin.goto(`${B}?user=${ETIENNE}`);
    const party = await admin.locator('#badge-new-dialog').evaluate((d) => d.textContent).catch(() => '');
    check(party.includes(name) || party.includes('nouveaux badges'), 'obtention : fenêtre « Nouveau badge » avec le badge créé');
    await admin.reload();
    check(await admin.locator('#badge-new-dialog').count() === 0, 'la fête ne s\'affiche qu\'une fois');

    /* ---- Vitrine de sa liste ---- */
    await admin.click('.profile__badges');
    const mine = admin.locator('#badges-dialog');
    check(await mine.locator('.trophy-tile.is-earned', { hasText: name }).count() === 1, 'vitrine : le trophée de test est obtenu');
    check(await mine.locator('.trophy-tile__medal.has-progress').count() > 0, 'vitrine (propriétaire) : progression des badges à débloquer');

    /* ---- Vue d'un ami : seulement les badges obtenus ---- */
    await friend.goto(`${B}?user=${ETIENNE}`);
    await friend.click('.profile__badges');
    const theirs = friend.locator('#badges-dialog');
    check(await theirs.locator('.trophy-tile.is-earned', { hasText: name }).count() === 1, 'ami : voit le trophée obtenu');
    check(await theirs.locator('.trophy-tile.is-locked').count() === 0 && await theirs.locator('.trophy-tile__medal.has-progress').count() === 0, 'ami : ni badges verrouillés ni progression');

    /* ---- Désactivation ---- */
    await admin.goto(B + 'admin.php?tab=badges');
    const row = admin.locator('.badge-admin__row', { has: admin.locator(`input[name=name][value="${name}"]`) });
    await row.locator('input[name=active]').uncheck();
    await Promise.all([admin.waitForNavigation(), row.locator('button[type=submit]').click()]);
    await admin.goto(`${B}?user=${ETIENNE}`);
    await admin.click('.profile__badges');
    check(await admin.locator('#badges-dialog .trophy-tile', { hasText: name }).count() === 0, 'badge désactivé : retiré des vitrines');

    check(admin.errors.length + friend.errors.length === 0, 'aucune erreur JS ' + [...admin.errors, ...friend.errors].join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nBadges OK');
    process.exit(failures ? 1 : 0);
})();
