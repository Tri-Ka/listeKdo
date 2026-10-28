/*
 * Adresse e-mail (sql/2026-10-28-email.sql, lib/mail.php) sur le Docker local en PHP 8 avec MailDev
 * (docker compose -f compose.yml -f compose.php8.yml) : inscription avec e-mail obligatoire et code à 6 chiffres,
 * connexion par e-mail, fenêtre bloquante des anciens comptes, adresse en double refusée, mot de passe oublié
 * par e-mail (lien lu dans MailDev). Lancé par run-email.sh, qui crée et supprime les comptes « Test1mail… ».
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
const MAILDEV = 'http://localhost:1088/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Derniers e-mails reçus par une adresse dans MailDev (le plus récent en premier).
async function mailsTo(address) {
    await sleep(1200);
    const mails = await (await fetch(MAILDEV + 'email')).json();
    return mails.filter((m) => m.to.some((t) => t.address === address)).reverse();
}
async function lastCode(address) {
    const mail = (await mailsTo(address)).find((m) => /^Votre code : \d{6}$/.test(m.subject));
    return mail ? mail.subject.slice(-6) : null;
}
const toastText = (page) => page.locator('.toast').first().innerText().catch(() => '');

(async () => {
    const browser = await chromium.launch();
    const errors = [];
    const newPage = async () => {
        const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
        page.on('pageerror', (e) => errors.push(e.message));
        return page;
    };
    const login = async (page, id, password) => {
        await page.goto(B);
        await page.click('.topbar [data-open="login-dialog"]');
        await page.fill('#login-dialog input[name=nom]', id);
        await page.fill('#login-dialog input[name=password]', password);
        await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    };
    const stamp = Date.now();
    const name = 'Test1mail' + stamp;
    const email = 'test1mail' + stamp + '@exemple.test';
    const oldEmail = 'ancien' + stamp + '@exemple.test';
    await fetch(MAILDEV + 'email/all', { method: 'DELETE' });

    // Inscription : e-mail obligatoire, puis code à 6 chiffres
    let page = await newPage();
    await page.goto(B);
    await page.click('[data-open="signup-dialog"] >> nth=0');
    check(await page.locator('#signup-dialog input[name=email][required]').count() === 1, 'inscription : champ e-mail obligatoire');
    await page.fill('#signup-dialog input[name=nom]', name);
    await page.fill('#signup-dialog input[name=email]', email.toUpperCase());
    await page.fill('#signup-dialog input[name=password]', 'test');
    await page.fill('#signup-dialog input[name=re-password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#signup-dialog button[type=submit]')]);
    let codeDialog = page.locator('#email-code-dialog');
    await codeDialog.waitFor({ state: 'visible', timeout: 4000 }).catch(() => {});
    check(await codeDialog.isVisible() && (await codeDialog.innerText()).includes(email), 'inscription : fenêtre du code, adresse en minuscules');
    await page.keyboard.press('Escape');
    check(await codeDialog.isVisible(), 'fenêtre du code : Échap ne la ferme pas');
    const code = await lastCode(email);
    check(!!code, 'code à 6 chiffres reçu dans MailDev');
    await page.screenshot({ path: 'out/email-code.png' });

    await codeDialog.locator('input[name=code]').fill(code === '000000' ? '111111' : '000000');
    await Promise.all([page.waitForNavigation(), codeDialog.locator('button[value=verify]').click()]);
    check(/ne correspond pas/.test(await toastText(page)), 'mauvais code refusé');

    await page.click('#email-code-dialog [data-email-resend]');
    await page.waitForLoadState();
    check(/attendez une minute/.test(await toastText(page)), 'renvoi immédiat limité');

    await codeDialog.locator('input[name=code]').fill(code.slice(0, 3) + ' ' + code.slice(3));
    await codeDialog.locator('input[name=code]').press('Enter');
    await page.waitForLoadState();
    await sleep(500);
    check(await page.locator('#email-code-dialog').count() === 0 && /confirmée/.test(await toastText(page)), 'bon code (avec espace, touche Entrée) : adresse confirmée');
    await page.context().close();

    // Connexion avec l'adresse e-mail (casse différente)
    page = await newPage();
    await login(page, email.replace('test1mail', 'Test1Mail'), 'test');
    check(await page.locator('.topbar .user-menu').count() === 1, 'connexion par e-mail');
    await page.context().close();

    // Ancien compte sans adresse (créé par run-email.sh) : fenêtre bloquante
    page = await newPage();
    await login(page, 'Test1mailancien', 'test');
    const required = page.locator('#email-required-dialog');
    codeDialog = page.locator('#email-code-dialog');
    await required.waitFor({ state: 'visible', timeout: 4000 }).catch(() => {});
    check(await required.isVisible(), 'ancien compte sans e-mail : fenêtre affichée');
    check(await required.locator('[data-close], .modal__close').count() === 0, 'fenêtre sans bouton de fermeture');
    await page.keyboard.press('Escape');
    await page.mouse.click(5, 5);
    check(await required.isVisible(), 'Échap et clic sur le fond ne la ferment pas');
    await page.screenshot({ path: 'out/email-required.png' });

    await required.locator('input[name=email]').fill(email);
    await Promise.all([page.waitForNavigation(), required.locator('button[type=submit]').click()]);
    check(/déjà utilisée/.test(await toastText(page)), 'adresse d\'un autre compte refusée');
    await required.waitFor({ state: 'visible', timeout: 4000 }).catch(() => {});
    check(await required.isVisible(), 'la fenêtre revient tant que l\'adresse manque');

    // Mauvaise adresse saisie, corrigée depuis la fenêtre du code
    await required.locator('input[name=email]').fill('faute' + stamp + '@exemple.test');
    await Promise.all([page.waitForNavigation(), required.locator('button[type=submit]').click()]);
    await codeDialog.waitFor({ state: 'visible', timeout: 4000 }).catch(() => {});
    check(await codeDialog.isVisible(), 'adresse saisie : fenêtre du code');
    await codeDialog.locator('summary').click();
    await codeDialog.locator('.email-code__change input[name=email]').fill(oldEmail);
    await Promise.all([page.waitForNavigation(), codeDialog.locator('.email-code__change button[type=submit]').click()]);
    await codeDialog.waitFor({ state: 'visible', timeout: 4000 }).catch(() => {});
    check((await codeDialog.innerText()).includes(oldEmail), 'adresse corrigée : code envoyé à la nouvelle');
    const oldCode = await lastCode(oldEmail);
    await codeDialog.locator('input[name=code]').fill(oldCode || '');
    await Promise.all([page.waitForNavigation(), codeDialog.locator('button[value=verify]').click()]);
    check(await page.locator('#email-code-dialog, #email-required-dialog').count() === 0, 'ancien compte : adresse confirmée, plus de fenêtre');

    // Profil : adresse obligatoire et préremplie
    await page.click('.topbar .user-menu summary');
    await page.click('.user-menu [data-open="profile-dialog"]');
    check(await page.inputValue('#profile-dialog input[name=email]') === oldEmail
        && await page.locator('#profile-dialog input[name=email][required]').count() === 1, 'profil : e-mail prérempli et obligatoire');
    await page.context().close();

    // Mot de passe oublié : lien par e-mail
    page = await newPage();
    await page.goto(B);
    const askLink = async (login) => {
        await page.click('.topbar [data-open="login-dialog"]');
        await page.click('#login-dialog [data-open="forgot-dialog"]');
        await page.fill('#forgot-dialog input[name=nom]', login);
        await page.click('#forgot-dialog [data-forgot-mail]');
        await page.waitForSelector('.toast--success', { timeout: 5000 }).catch(() => {});
    };
    await askLink(name);
    check(/lien vient d'y être envoyé/.test(await toastText(page)), 'mot de passe oublié : message neutre');
    const mail = (await mailsTo(email)).find((m) => m.subject === 'Votre nouveau mot de passe');
    check(!!mail && mail.from[0].name === 'Liste de Kdo', 'e-mail du lien reçu (sujet et expéditeur accentués)');
    const link = mail && (mail.text.match(/https?:\/\/\S+reset\.php\?t=[0-9]+-[0-9a-f]{40}/) || [])[0];
    check(!!link, 'lien de nouveau mot de passe dans l\'e-mail');

    const before = (await (await fetch(MAILDEV + 'email')).json()).length;
    await askLink('inconnu' + stamp + '@exemple.test');
    await sleep(1200);
    check((await (await fetch(MAILDEV + 'email')).json()).length === before, 'compte inconnu : même message, aucun e-mail');

    if (link) {
        await page.goto(link.replace(/^https?:\/\/[^/]+\/listeKdo\//, B));
        await page.fill('input[name=password]', 'nouveau');
        await page.fill('input[name=re-password]', 'nouveau');
        await Promise.all([page.waitForNavigation(), page.click('.reset-card__form button[type=submit]')]);
        check(await page.locator('.topbar .user-menu').count() === 1, 'nouveau mot de passe enregistré et connecté');
    }
    await page.context().close();

    page = await newPage();
    await login(page, email, 'nouveau');
    check(await page.locator('.topbar .user-menu').count() === 1, 'connexion par e-mail avec le nouveau mot de passe');

    check(errors.length === 0, 'aucune erreur JS' + (errors.length ? ' : ' + errors.join(' | ') : ''));
    await browser.close();
    process.exit(failures ? 1 : 0);
})();
