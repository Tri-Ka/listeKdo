/*
 * Administration : accès réservé, tableau (recherche, tri, filtres, pagination), rôles,
 * mot de passe provisoire et suppression d'un compte. Lancé par run.sh (Etienne = admin).
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

async function session(browser, name, password = 'test', viewport = { width: 1366, height: 900 }) {
    const page = await (await browser.newContext({ viewport })).newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', password);
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    return page;
}

const rows = (page) => page.locator('[data-dt] tbody tr:not(.dt__empty)');
const waitTable = (page) => page.waitForFunction(() => !document.querySelector('[data-dt][aria-busy]'));

(async () => {
    const browser = await chromium.launch();

    /* ---- Compte de test, créé par l'inscription ---- */
    const name = 'Test1admin' + Date.now();
    const visitor = await (await browser.newContext()).newPage();
    await visitor.goto(B);
    await visitor.click('.home-hero [data-open="signup-dialog"]');
    await visitor.fill('#signup-dialog input[name=nom]', name);
    await visitor.fill('#signup-dialog input[name=password]', 'ancien1');
    await visitor.fill('#signup-dialog input[name="re-password"]', 'ancien1');
    await Promise.all([visitor.waitForNavigation(), visitor.click('#signup-dialog button[type=submit]')]);

    /* ---- Accès refusé aux non-admins ---- */
    const mallory = await session(browser, 'Mallory');
    await mallory.click('.user-menu summary');
    check(await mallory.locator('.user-menu__panel a[href="admin.php"]').count() === 0, 'pas de lien Administration pour Mallory');
    await mallory.goto(B + 'admin.php');
    check(!mallory.url().includes('admin.php'), 'admin.php redirige Mallory');
    const forbidden = await mallory.evaluate(async () => {
        const body = new FormData();
        body.append('id', '141');
        body.append('role', 'admin');
        const token = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch('actions/adminRole.php', { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-Token': token } });
        return response.status;
    });
    check(forbidden === 403, 'adminRole.php refusé à Mallory : ' + forbidden);

    /* ---- Page d'admin ---- */
    const admin = await session(browser, 'Etienne');
    await admin.click('.user-menu summary');
    await Promise.all([admin.waitForNavigation(), admin.click('.user-menu__panel a[href="admin.php"]')]);
    check(admin.url().endsWith('admin.php'), 'lien Administration dans le menu');
    check(await admin.locator('.stat').count() === 4, 'chiffres clés');
    check(await rows(admin).count() === 25, '25 lignes par page');
    await admin.screenshot({ path: `${__dirname}/out/admin-users.png`, fullPage: true });

    // Recherche instantanée.
    await admin.fill('[data-dt-search] input[name=q]', 'Mallory');
    await admin.waitForFunction(() => document.querySelectorAll('[data-dt] tbody tr').length === 1);
    check((await rows(admin).first().innerText()).includes('Mallory'), 'recherche « Mallory »');
    check(admin.url().includes('q=Mallory'), 'recherche dans l\'URL');
    await admin.fill('[data-dt-search] input[name=q]', '');
    await admin.waitForFunction(() => document.querySelectorAll('[data-dt] tbody tr').length === 25);

    // Tri par nombre d'idées, décroissant (deux clics).
    await admin.click('th a:has-text("Idées")');
    await waitTable(admin);
    await admin.click('th a:has-text("Idées")');
    await waitTable(admin);
    await admin.waitForFunction(() => location.search.includes('dir=desc'));
    const ideas = await admin.locator('[data-dt] tbody td[data-label="Idées"]').allInnerTexts();
    const numbers = ideas.map((text) => parseInt(text, 10));
    check(numbers.every((n, i) => i === 0 || numbers[i - 1] >= n), 'tri par idées décroissant');

    // Pagination.
    await admin.selectOption('[data-dt-per]', { label: '10' });
    await admin.waitForFunction(() => document.querySelectorAll('[data-dt] tbody tr').length === 10);
    const firstPage = await rows(admin).first().innerText();
    await admin.click('.dt__pages a[aria-label="Page suivante"]');
    await admin.waitForFunction(() => document.querySelector('.dt__page.is-current')?.textContent.trim() === '2');
    check(firstPage !== await rows(admin).first().innerText(), 'page 2');
    check(admin.url().includes('page=2') && admin.url().includes('per=10'), 'page et taille dans l\'URL');

    // Filtre « Admins ».
    await admin.click('.dt__chip:has-text("Admins")');
    await admin.waitForFunction(() => document.querySelector('.dt__chip[aria-current]')?.textContent.trim() === 'Admins');
    check((await rows(admin).allInnerTexts()).every((text) => text.includes('Administrateur')), 'filtre Admins');

    // Dernière visite : Etienne est connecté, il est « à l'instant ». Tri décroissant cohérent.
    await admin.goto(B + 'admin.php?q=Etienne');
    check((await admin.locator('[data-dt] tbody tr', { hasText: 'Vous' }).innerText()).includes('instant'), 'dernière visite d\'Etienne : à l\'instant');
    await admin.goto(B + 'admin.php?sort=last_seen&dir=desc');
    const seen = await admin.locator('[data-dt] .dt__seen').evaluateAll((spans) => spans.map((span) => span.title));
    const times = seen.map((t) => { const [d, m, y, h, i] = t.match(/\d+/g).map(Number); return new Date(y, m - 1, d, h, i).getTime(); });
    check(times.length > 0 && times.every((t, i) => i === 0 || times[i - 1] >= t), 'tri par dernière visite');

    // Onglet Listes.
    await admin.goto(B + 'admin.php?tab=lists');
    check(await admin.locator('th:has-text("Abonnés")').count() === 1, 'onglet Listes');
    await admin.screenshot({ path: `${__dirname}/out/admin-lists.png`, fullPage: true });

    /* ---- Rôle, mot de passe et suppression du compte de test ---- */
    await admin.goto(B + 'admin.php?q=' + name);
    const row = rows(admin).first();
    check((await row.innerText()).includes(name), 'compte de test trouvé');

    await row.locator('select[name=role]').selectOption('admin');
    await admin.waitForSelector('.toast--success');
    check((await admin.locator('.toast--success').last().innerText()).includes('administrateur'), 'rôle admin donné');
    await admin.reload();
    check(await rows(admin).first().locator('select[name=role]').inputValue() === 'admin', 'rôle enregistré');
    check(await rows(admin).first().locator('[data-admin-confirm="delete"]').count() === 0, 'un admin ne peut pas être supprimé');
    await rows(admin).first().locator('select[name=role]').selectOption('user');
    await admin.waitForFunction(() => [...document.querySelectorAll('.toast--success')].some((t) => t.textContent.includes('utilisateur')));

    await rows(admin).first().locator('[data-admin-confirm="password"] button').click();
    await admin.click('#admin-confirm [data-confirm-ok]');
    await admin.waitForSelector('#admin-password[open]');
    const password = (await admin.locator('[data-password-value]').innerText()).trim();
    check(/^[a-z2-9]{10}$/.test(password), 'mot de passe provisoire : ' + password.length + ' caractères');
    await admin.screenshot({ path: `${__dirname}/out/admin-password.png` });
    await admin.click('#admin-password .modal__footer [data-close]');

    // L'ancien cookie du compte de test ne marche plus, le nouveau mot de passe oui.
    await visitor.context().clearCookies({ name: 'PHPSESSID' });
    await visitor.goto(B);
    check(await visitor.locator('.user-menu').count() === 0, 'ancien cookie révoqué');
    const tester = await session(browser, name, password);
    check(await tester.locator('.user-menu').count() === 1, 'connexion avec le mot de passe provisoire');

    // Lien de secours : usage unique, connecte la personne.
    await admin.reload();
    await rows(admin).first().locator('[data-admin-link] button').click();
    await admin.waitForSelector('#admin-link[open]');
    const link = await admin.locator('[data-link-value]').inputValue();
    check(/reset\.php\?t=\d+-[0-9a-f]{40}$/.test(link), 'lien de secours créé');
    check((await admin.locator('[data-link-whatsapp]').getAttribute('href')).startsWith('https://wa.me/?text='), 'partage WhatsApp');
    await admin.screenshot({ path: `${__dirname}/out/admin-link.png` });
    await admin.click('#admin-link .modal__footer [data-close]');

    const lost = await (await browser.newContext({ viewport: { width: 390, height: 844 } })).newPage();
    await lost.goto(link);
    check((await lost.locator('h1').innerText()).includes(name), 'page du lien : bonjour ' + name);
    await lost.screenshot({ path: `${__dirname}/out/reset-link.png` });
    await lost.fill('input[name=password]', 'nouveau1');
    await lost.fill('input[name="re-password"]', 'autre');
    await Promise.all([lost.waitForNavigation(), lost.click('button[type=submit]')]);
    check((await lost.locator('.reset-card__flash').innerText()).includes('différents'), 'mots de passe différents refusés');
    await lost.fill('input[name=password]', 'nouveau1');
    await lost.fill('input[name="re-password"]', 'nouveau1');
    await Promise.all([lost.waitForNavigation(), lost.click('button[type=submit]')]);
    check(await lost.locator('.user-menu').count() === 1, 'connecté après le nouveau mot de passe');
    await lost.goto(link);
    check((await lost.locator('h1').innerText()).includes('expiré'), 'le lien ne sert qu\'une fois');
    const relogged = await session(browser, name, 'nouveau1');
    check(await relogged.locator('.user-menu').count() === 1, 'connexion avec le mot de passe choisi');

    // Parents : le compte de test devient une liste d'enfant d'Etienne, puis redevient normal.
    // (Mallory est déjà une liste d'enfant dans la base locale : elle n'est pas proposée.)
    check(await admin.locator('#admin-parent-options option[value^="Mallory"]').count() === 0, 'parents : une liste d\'enfant n\'est pas proposée');
    await admin.reload();
    await rows(admin).first().locator('[data-admin-managers]').click();
    await admin.waitForFunction(() => document.querySelector('[data-managers-state]').textContent.includes('pas une liste'));
    check(true, 'parents : pas encore une liste d\'enfant');
    await admin.fill('#admin-parent-input', name);
    await admin.click('[data-managers-add] button');
    await admin.waitForSelector('.toast--error');
    check((await admin.locator('.toast--error').last().innerText()).includes('propre gestionnaire'), 'gestionnaires : pas son propre gestionnaire');
    await admin.fill('#admin-parent-input', 'Etienne');
    await admin.click('[data-managers-add] button');
    await admin.waitForSelector('[data-managers-list] li');
    check((await admin.locator('[data-managers-list]').innerText()).includes('Etienne'), 'parents : Etienne ajouté');
    await admin.screenshot({ path: `${__dirname}/out/admin-managers.png` });
    await admin.click('#admin-managers .modal__footer [data-close]');
    await admin.waitForSelector('[data-dt] tbody tr .dt__tag--child');
    check(true, 'tableau : étiquette « Enfant »');

    const parentView = await admin.context().newPage();
    await parentView.goto(B);
    await parentView.click('.user-menu summary');
    check(await parentView.locator('.user-menu__panel a', { hasText: name }).count() === 1, 'Etienne : liste de l\'enfant dans son menu');
    await parentView.goto(B + 'index.php?q=&user=' + await rows(admin).first().locator('.dt__person').getAttribute('href').then((h) => h.split('user=')[1]));
    check(await parentView.locator('.card--add').count() === 1, 'Etienne peut modifier la liste');
    await parentView.close();

    await rows(admin).first().locator('[data-admin-managers]').click();
    await admin.waitForSelector('[data-managers-list] li');
    await admin.click('[data-managers-list] [data-remove-manager]');
    await admin.waitForFunction(() => document.querySelector('[data-managers-state]').textContent.includes('pas une liste'));
    check(true, 'parents : dernier parent retiré, liste normale');
    await admin.click('#admin-managers .modal__footer [data-close]');
    await admin.waitForFunction(() => !document.querySelector('[data-dt] tbody tr .dt__tag--child'));

    await admin.reload();
    await rows(admin).first().locator('[data-admin-confirm="delete"] button').click();
    check((await admin.locator('#admin-confirm [data-confirm-title]').innerText()).includes(name), 'confirmation de suppression');
    await admin.screenshot({ path: `${__dirname}/out/admin-delete.png` });
    await admin.click('#admin-confirm [data-confirm-ok]');
    await admin.waitForFunction(() => document.querySelector('[data-dt] .dt__empty'));
    check(true, 'compte supprimé');

    // Pas de suppression ni de changement de rôle sur soi-même.
    await admin.goto(B + 'admin.php?q=Etienne');
    const me = admin.locator('[data-dt] tbody tr', { hasText: 'Vous' });
    check(await me.locator('select').count() === 0 && await me.locator('[data-admin-confirm="delete"]').count() === 0, 'pas d\'action sur son propre compte');

    /* ---- Mobile ---- */
    const mobile = await session(browser, 'Etienne', 'test', { width: 390, height: 844 });
    await mobile.goto(B + 'admin.php?per=10');
    check(await mobile.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'pas de défilement horizontal sur mobile');
    await mobile.screenshot({ path: `${__dirname}/out/admin-mobile.png`, fullPage: true });

    const errors = [...admin.errors, ...mallory.errors, ...mobile.errors];
    check(errors.length === 0, 'aucune erreur JS ' + errors.join(' | '));

    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nAdmin OK');
    process.exit(failures ? 1 : 0);
})();
