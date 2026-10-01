/*
 * Question secrète : invitation après l'inscription, puis « Mot de passe oublié ». Lancé par run.sh.
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

(async () => {
    const browser = await chromium.launch();
    const page = await (await browser.newContext({ viewport: { width: 1200, height: 900 } })).newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    const name = 'Test1secret' + Date.now();

    // Inscription sans question secrète : la fenêtre d'invitation s'ouvre.
    await page.goto(B);
    await page.click('.home-hero [data-open="signup-dialog"]');
    await page.fill('#signup-dialog input[name=nom]', name);
    await page.fill('#signup-dialog input[name=password]', 'ancien1');
    await page.fill('#signup-dialog input[name="re-password"]', 'ancien1');
    await Promise.all([page.waitForNavigation(), page.click('#signup-dialog button[type=submit]')]);
    await page.waitForSelector('#secret-invite-dialog[open]');
    check(true, 'invitation à choisir une question secrète');
    check(await page.locator('#secret-invite-dialog optgroup option').count() >= 40, 'longue liste de questions : ' + await page.locator('#secret-invite-dialog optgroup option').count());

    // Question personnalisée.
    await page.selectOption('#secret-invite-dialog [data-secret-select]', '__custom');
    await page.fill('#secret-invite-dialog input[name=secret_question_custom]', 'Comment s\'appelait le chien de mamie ?');
    await page.fill('#secret-invite-dialog input[name=secret_answer]', 'Médor');
    await page.screenshot({ path: `${__dirname}/out/secret-invite.png` });
    await Promise.all([page.waitForNavigation(), page.click('#secret-invite-dialog button[type=submit]')]);
    check(await page.locator('#secret-invite-dialog').count() === 0, 'question enregistrée, plus d\'invitation');

    // Déconnexion, puis mot de passe oublié.
    await page.click('.user-menu summary');
    await Promise.all([page.waitForNavigation(), page.click('.user-menu__panel form button')]);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.click('#login-dialog [data-open="forgot-dialog"]');
    await page.fill('#forgot-dialog input[name=nom]', name);
    await page.click('#forgot-dialog [data-forgot-submit]');
    await page.waitForSelector('#forgot-dialog [data-forgot-step]:not([hidden])');
    check(await page.locator('[data-forgot-question]').innerText() === 'Comment s\'appelait le chien de mamie ?', 'la question s\'affiche');

    await page.fill('#forgot-dialog input[name=secret_answer]', 'mauvais');
    await page.fill('#forgot-dialog input[name=password]', 'nouveau1');
    await page.fill('#forgot-dialog input[name="re-password"]', 'nouveau1');
    await page.click('#forgot-dialog [data-forgot-submit]');
    await page.waitForSelector('.toast--error');
    check((await page.locator('.toast--error').last().innerText()).includes('pas la bonne réponse'), 'mauvaise réponse refusée');

    // Réponse avec majuscules, accents et ponctuation différents.
    await page.fill('#forgot-dialog input[name=secret_answer]', '  MEDOR ! ');
    await Promise.all([page.waitForNavigation(), page.click('#forgot-dialog [data-forgot-submit]')]);
    check((await page.locator('.user-menu__avatar').getAttribute('data-tip')) === name, 'bonne réponse (« MEDOR ! » = « Médor ») : connecté');

    // Le nouveau mot de passe fonctionne.
    await page.click('.user-menu summary');
    await Promise.all([page.waitForNavigation(), page.click('.user-menu__panel form button')]);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'nouveau1');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    check(await page.locator('.user-menu').count() === 1, 'connexion avec le nouveau mot de passe');

    check(errors.length === 0, 'aucune erreur JS ' + errors.join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nQuestion secrète OK');
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
