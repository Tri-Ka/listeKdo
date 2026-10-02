/*
 * Liste privée : visible seulement par son propriétaire (et ses gestionnaires).
 * Site local, lancé par run.sh (Etienne = propriétaire, Mallory = amie).
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
    page.on('dialog', (d) => d.accept());
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    return page;
}

async function setPrivate(page, on) {
    await page.goto(`${B}?user=${ETIENNE}`);
    await page.click('.profile__action');
    await page.locator('#profile-dialog input[name=is_private][type=checkbox]').setChecked(on);
    await Promise.all([page.waitForNavigation(), page.click('#profile-dialog button[type=submit]')]);
}

(async () => {
    const browser = await chromium.launch();
    const owner = await session(browser, 'Etienne');
    const friend = await session(browser, 'Mallory');

    await owner.goto(`${B}?user=${ETIENNE}`);
    const ideaId = await owner.locator('.card[data-object]').first().getAttribute('data-object');

    /* ---- Rendre la liste privée ---- */
    await setPrivate(owner, true);
    check(await owner.locator('.profile__private').count() === 1, 'propriétaire : pastille « Privée »');
    check(await owner.locator('[data-share]').count() === 0, 'propriétaire : bloc de partage masqué');
    check(await owner.locator('.card[data-object]').count() > 0, 'propriétaire : voit toujours ses idées');
    await owner.screenshot({ path: `${__dirname}/out/private-owner.png` });

    // Nouvelle idée pendant que la liste est privée : pas de notification pour les amis.
    const secret = 'Idée privée test ' + Date.now();
    await owner.click('.card--add');
    await owner.fill('#object-form-dialog input[name=nom]', secret);
    await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-object-form-submit]')]);
    const secretId = await owner.locator('.card', { hasText: secret }).getAttribute('data-object');

    /* ---- Côté amie ---- */
    await friend.goto(`${B}?user=${ETIENNE}`);
    check(await friend.locator('.private-notice').count() === 1, 'amie : « Cette liste est privée »');
    check(await friend.locator('.card[data-object]').count() === 0 && await friend.locator('.footer').count() === 0, 'amie : aucune idée ni pied de page');
    check(await friend.locator(`.friends a[href*="${ETIENNE}"]`).count() === 0, 'amie : la liste disparaît de ses amis');
    check(!(await friend.locator('[data-notifications]').innerText()).includes(secret), 'amie : pas de notification de la nouvelle idée');
    await friend.screenshot({ path: `${__dirname}/out/private-friend.png` });

    const post = (url, data) => friend.evaluate(async ([url, data]) => {
        const body = new URLSearchParams({ ...data, _token: document.querySelector('meta[name=csrf-token]').content });
        const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
        return response.status;
    }, [url, data]);
    check(await post(`${B}actions/addComment.php`, { productId: ideaId, content: 'intrus' }) === 404, 'amie : commentaire refusé');
    check(await post(`${B}actions/objectGifted.php`, { id: ideaId }) === 404, 'amie : réservation refusée');
    check(await post(`${B}actions/addReaction.php`, { object: ideaId, value: 1 }) === 400, 'amie : réaction refusée');

    /* ---- Liste de nouveau publique ---- */
    await setPrivate(owner, false);
    check(await owner.locator('.profile__private').count() === 0 && await owner.locator('[data-share]').count() === 1, 'propriétaire : liste de nouveau publique');
    await friend.goto(`${B}?user=${ETIENNE}`);
    check(await friend.locator('.private-notice').count() === 0 && await friend.locator(`#idea-${secretId}`).count() === 1, 'amie : voit de nouveau la liste');
    check(await friend.locator(`.friends a[href*="${ETIENNE}"]`).count() === 1, 'amie : la liste revient dans ses amis');

    /* ---- Liste secondaire privée dès sa création (nom Test1… : supprimée par run.sh) ---- */
    await owner.click('.user-menu summary');
    await owner.click('[data-open="child-new-dialog"]');
    await owner.fill('#child-new-dialog input[name=nom]', 'Test1prive');
    await owner.check('#child-new-dialog input[name=is_private]');
    await Promise.all([owner.waitForNavigation(), owner.click('#child-new-dialog button[type=submit]')]);
    const childUrl = owner.url().split('#')[0];
    check(await owner.locator('.profile__private').count() === 1 && await owner.locator('.card--add').count() === 1, 'liste secondaire privée : le gestionnaire la voit et la modifie');
    check(await owner.locator('#child-dialog input[name=is_private][type=checkbox]').isChecked(), 'liste secondaire : case cochée dans sa fiche');
    await friend.goto(childUrl);
    check(await friend.locator('.private-notice').count() === 1, 'liste secondaire privée : l\'amie voit « Cette liste est privée »');
    check(await friend.locator('.friends a[href*="index.php?user="]', { hasText: 'Test1prive' }).count() === 0
        && !(await friend.content()).includes('Test1prive'), 'liste secondaire privée : absente des amis de l\'amie');

    // Nettoyage.
    await owner.goto(`${B}?user=${ETIENNE}`);
    await owner.locator(`#idea-${secretId} [data-open-object-form]`).click();
    await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-delete-object]')]);

    check(owner.errors.length === 0 && friend.errors.length === 0, 'aucune erreur JS ' + owner.errors.concat(friend.errors).join(' | '));
    await browser.close();
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(1); });
