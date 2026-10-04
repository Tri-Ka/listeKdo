/*
 * Visite guidée : lancement depuis le menu du compte, navigation (boutons et clavier), menu du compte
 * ouvert pour ses étapes, lancement depuis une autre page (#visite), fin sur « Ajouter une idée ».
 * Site local, lancé par run.sh (Etienne). Le démarrage automatique est coupé sous navigator.webdriver.
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

async function session(browser, name, viewport) {
    const page = await (await browser.newContext({ viewport })).newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
    return page;
}

const tour = (page) => page.locator('#onboarding-tour');
const title = (page) => page.locator('[data-tour-title]').innerText();

(async () => {
    const browser = await chromium.launch();

    for (const [label, viewport] of [['bureau', { width: 1366, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
        const page = await session(browser, 'Etienne', viewport);
        check(await tour(page).isHidden(), `${label} : pas de lancement automatique sous webdriver`);

        await page.click('.user-menu summary');
        await page.click('[data-start-onboarding]');
        check(await tour(page).isVisible() && (await title(page)).startsWith('Bienvenue'), `${label} : la visite s'ouvre sur l'accueil`);
        check(await page.locator('#onboarding-tour.is-centered').count() === 1, `${label} : accueil centré, sans projecteur`);

        await page.click('[data-tour-next]');
        check(await title(page) === 'Ajoutez vos idées', `${label} : étape « Ajouter une idée »`);
        check(await page.locator('[data-tour-count]').innerText() === `2 / ${(await page.locator('[data-tour-count]').innerText()).split(' / ')[1]}`, `${label} : compteur d'étapes`);
        await page.keyboard.press('ArrowLeft');
        check((await title(page)).startsWith('Bienvenue'), `${label} : flèche gauche = étape précédente`);
        await page.keyboard.press('ArrowRight');

        // Jusqu'à une étape du menu du compte : il doit s'ouvrir tout seul.
        const seen = [];
        let menuOpened = false;
        for (let i = 0; i < 30 && !(await page.locator('[data-tour-next]').innerText()).includes('Ajouter'); i++) {
            const current = await title(page);
            seen.push(current);
            if (current === 'Les cadeaux que vous offrez') menuOpened = await page.locator('[data-user-menu][open]').count() === 1;
            await page.click('[data-tour-next]');
        }
        check(menuOpened, `${label} : le menu du compte s'ouvre pour ses étapes`);
        check(seen.includes('Les paramètres de la liste') && seen.includes('Envoyez votre liste'), `${label} : paramètres et partage présentés (${seen.length} étapes)`);
        check(await page.locator('[data-user-menu][open]').count() === 0, `${label} : menu refermé à la dernière étape`);

        // Dernière étape : « Ajouter une idée » ferme la visite et ouvre le formulaire.
        await page.click('[data-tour-next]');
        check(await tour(page).isHidden() && await page.locator('#object-form-dialog[open]').count() === 1, `${label} : la fin ouvre le formulaire d'idée`);
        check(page.errors.length === 0, `${label} : aucune erreur JS ` + page.errors.join(' | '));
        await page.context().close();
    }

    /* ---- Échap ferme la visite ; lancée depuis une autre page, elle ramène sur sa liste ---- */
    const page = await session(browser, 'Etienne', { width: 1366, height: 900 });
    await page.goto(`${B}?page=comment-ca-marche`);
    await page.click('.user-menu summary');
    await Promise.all([page.waitForNavigation(), page.click('[data-start-onboarding]')]);
    await tour(page).waitFor({ state: 'visible' });
    check(!page.url().includes('#visite') && !page.url().includes('comment-ca-marche'), 'autre page : retour sur sa liste, visite lancée');
    await page.keyboard.press('Escape');
    check(await tour(page).isHidden(), 'Échap ferme la visite');
    check(page.errors.length === 0, 'aucune erreur JS ' + page.errors.join(' | '));

    await browser.close();
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
