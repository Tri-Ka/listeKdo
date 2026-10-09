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
    const rows = admin.locator('[data-badge-row]');
    check(await rows.count() >= 40, 'admin : badges par défaut installés (' + await rows.count() + ')');
    check(await admin.locator('[data-badge-row][data-name="Première idée"]').count() === 1, 'admin : accents conservés (« Première idée »)');

    const name = 'Test1badge' + Date.now();
    const blank = admin.locator('#badge-edit-dialog');
    await admin.locator('[data-badge-create]').click();
    await blank.locator('input[name=emoji]').fill('🧪');
    await blank.locator('input[name=name]').fill(name);
    await blank.locator('input[name=description]').fill('Badge créé par le test.');
    await blank.locator('select[name=metric]').selectOption('ideas');
    await blank.locator('input[name=threshold]').fill('1');
    await blank.locator('select[name=kind]').selectOption('trophy');
    await blank.locator('input[name=gems]').fill('123');
    await Promise.all([admin.waitForNavigation(), blank.locator('button[type=submit]').click()]);
    check(await admin.locator('.toast--success').count() === 1, 'admin : badge ajouté');
    check(await admin.locator('[data-badge-row]', { hasText: name }).locator('[data-badge-amount]').innerText() === '123', 'Badges : récompense enregistrée à la création');

    /* ---- Récompenses accessibles dans le tableau Gemmes et dans l’édition du badge ---- */
    await admin.goto(B + 'admin.php?tab=gems#gem-badges');
    await admin.locator('[data-gem-table=badges] [data-gem-search]').fill(name);
    const reward = admin.locator('[data-gem-row]', { hasText: name });
    const badgeId = JSON.parse(await reward.getAttribute('data-gem-definition')).hidden.badge_id;
    await reward.locator('[data-gem-edit]').click();
    const gemForm = admin.locator('#gem-edit-dialog');
    await gemForm.locator('[name=amount]').fill('137');
    await Promise.all([admin.waitForResponse((r) => r.url().includes('actions/adminGems.php') && r.request().method() === 'POST'), gemForm.locator('button[type=submit]').click()]);
    check(admin.url().includes('tab=gems') && await admin.locator('[data-gem-row]', { hasText: name }).locator('[data-gem-amount]').innerText() === '137', 'Gemmes : récompense enregistrée et retour au bon onglet');
    const rewardPost = (page, csrf) => page.evaluate(async ([id, csrf]) => {
        const body = new URLSearchParams({ op: 'badge', badge_id: id, badge_gems: '500' });
        if (csrf) body.set('_token', document.querySelector('meta[name=csrf-token]').content);
        const response = await fetch('actions/adminGems.php', { method: 'POST', body, headers: { Accept: 'application/json' } });
        return response.status;
    }, [badgeId, csrf]);
    check(await rewardPost(friend, true) === 403, 'récompense refusée à un non-admin');
    check(await rewardPost(admin, false) === 419, 'récompense refusée sans CSRF');
    await admin.goto(B + 'admin.php?tab=badges');
    await admin.locator('[data-badge-search]').fill(name);
    await admin.locator('[data-badge-row]', { hasText: name }).locator('[data-badge-edit]').click();
    const conditions = admin.locator('#badge-edit-dialog');
    check(await conditions.locator('[name=gems]').inputValue() === '137', 'Badges : récompense actuelle affichée dans le formulaire');
    await conditions.locator('[name=description]').fill('Conditions modifiées sans changer les gemmes.');
    await Promise.all([admin.waitForResponse((r) => r.url().includes('actions/adminBadge.php') && r.request().method() === 'POST'), conditions.locator('button[type=submit]').click()]);
    await admin.waitForFunction(() => !document.querySelector('.badge-admin__form[aria-busy=true]'));
    await admin.locator('[data-badge-row]', { hasText: name }).locator('[data-badge-edit]').click();
    const edited = admin.locator('#badge-edit-dialog');
    await edited.locator('[name=gems]').fill('151');
    await Promise.all([admin.waitForResponse((r) => r.url().includes('actions/adminBadge.php') && r.request().method() === 'POST'), edited.locator('button[type=submit]').click()]);
    await admin.waitForFunction(() => !document.querySelector('.badge-admin__form[aria-busy=true]'));
    check(await admin.locator('[data-badge-row]', { hasText: name }).locator('[data-badge-amount]').innerText() === '151', 'Badges : récompense modifiée depuis l’édition du badge');
    await admin.goto(B + 'admin.php?tab=gems#gem-badges');
    check(await admin.locator('[data-gem-row]', { hasText: name }).locator('[data-gem-amount]').innerText() === '151', 'Gemmes : même récompense que dans l’édition du badge');

    /* ---- Obtention : fête « Nouveau trophée ! » à la visite suivante ---- */
    // (La fenêtre ne s'ouvre pas seule dans un navigateur de test : on vérifie son contenu.)
    await admin.goto(`${B}?user=${ETIENNE}`);
    const party = await admin.locator('#badge-new-dialog').evaluate((d) => d.textContent).catch(() => '');
    check(party.includes(name) || party.includes('nouveaux badges'), 'obtention : fenêtre « Nouveau badge » avec le badge créé');
    await admin.reload();
    check(await admin.locator('#badge-new-dialog').count() === 0, 'la fête ne s\'affiche qu\'une fois');

    /* ---- Vitrine de sa liste ---- */
    await admin.locator('[data-user-menu] > summary').click();
    await admin.click('[data-my-badges]');
    await admin.waitForSelector('#badges-dialog[open]');
    const mine = admin.locator('#badges-dialog');
    check((await mine.locator('#badges-title').innerText()) === 'Mes badges', 'menu du compte : accès direct à ses badges');
    check(await mine.locator('[data-badge-section="earned"] .trophy-tile.is-earned', { hasText: name }).count() === 1, 'vitrine : le trophée de test est obtenu');
    check(await mine.locator('.trophy-tile__medal.has-progress').count() > 0, 'vitrine (propriétaire) : progression des badges à débloquer');

    /* ---- Vue d'un ami : seulement les badges obtenus ---- */
    await friend.goto(`${B}?user=${ETIENNE}`);
    await friend.locator('[data-user-menu] > summary').click();
    await friend.click('[data-my-badges]');
    await friend.waitForSelector('#badges-dialog[open]');
    check((await friend.locator('#badges-title').innerText()) === 'Mes badges' && !friend.url().includes(ETIENNE), 'menu depuis la liste d’un ami : ouvre ses propres badges');
    await friend.goto(`${B}?user=${ETIENNE}`);
    await friend.click('.profile__badges');
    const theirs = friend.locator('#badges-dialog');
    check(await theirs.locator('[data-badge-section="earned"] .trophy-tile.is-earned', { hasText: name }).count() === 1, 'ami : voit le trophée obtenu');
    check(await theirs.locator('.trophy-tile.is-locked').count() === 0 && await theirs.locator('.trophy-tile__medal.has-progress').count() === 0, 'ami : ni badges verrouillés ni progression');

    /* ---- Désactivation ---- */
    await admin.goto(B + 'admin.php?tab=badges');
    await admin.locator('[data-badge-search]').fill(name);
    await admin.locator('[data-badge-row]', { hasText: name }).locator('[data-badge-edit]').click();
    const row = admin.locator('#badge-edit-dialog');
    await row.locator('input[name=active]').uncheck();
    await Promise.all([admin.waitForResponse((r) => r.url().includes('actions/adminBadge.php') && r.request().method() === 'POST'), row.locator('button[type=submit]').click()]);
    await admin.goto(`${B}?user=${ETIENNE}`);
    await admin.click('.profile__badges');
    check(await admin.locator('#badges-dialog .trophy-tile', { hasText: name }).count() === 0, 'badge désactivé : retiré des vitrines');

    check(admin.errors.length + friend.errors.length === 0, 'aucune erreur JS ' + [...admin.errors, ...friend.errors].join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nBadges OK');
    process.exit(failures ? 1 : 0);
})();
