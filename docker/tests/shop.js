/*
 * Gemmes et boutique : solde, achat d'un habillage (posé sur sa liste), vu par une amie,
 * retour au classique, et choix de l'habillage dans « Paramètres de la liste ».
 * Lancé par run.sh, qui efface ensuite les achats d'Etienne et Mallory.
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

const balance = async (page) => Number((await page.locator('.user-menu__count--gems').textContent()).trim());

(async () => {
    const browser = await chromium.launch();
    const owner = await session(browser, 'Etienne');
    const friend = await session(browser, 'Mallory');

    /* ---- Gemmes par action, réglées dans l'administration (Etienne est admin) ---- */
    await owner.goto(`${B}?user=${ETIENNE}`);
    const start = await balance(owner);
    await owner.goto(B + 'admin.php?tab=badges');
    const ideasRate = Number(await owner.inputValue('input[name="rate[ideas]"]'));
    await owner.fill('input[name="rate[ideas]"]', String(ideasRate + 10));
    await Promise.all([owner.waitForNavigation(), owner.click('.gem-rates button[type=submit]')]);
    await owner.goto(`${B}?user=${ETIENNE}`);
    check(await balance(owner) > start, 'admin : gemmes par idée augmentées, le solde augmente (' + start + ' -> ' + await balance(owner) + ')');
    await owner.goto(B + 'admin.php?tab=badges');
    await owner.fill('input[name="rate[ideas]"]', String(ideasRate));
    await Promise.all([owner.waitForNavigation(), owner.click('.gem-rates button[type=submit]')]);

    await owner.goto(`${B}?user=${ETIENNE}`);
    const before = await balance(owner);
    check(before === start, 'admin : réglage remis, solde d\'origine');
    check(before >= 200, 'gemmes gagnées avec les badges : ' + before);

    /* ---- Achat ---- */
    // Pastille de gemmes dans la barre du haut : même solde, ouvre la boutique.
    check(Number((await owner.locator('.gem-pill__value').textContent()).trim()) === before, 'barre du haut : pastille avec le solde de gemmes');
    await owner.click('.gem-pill');
    check(await owner.locator('[data-shop-section="birthday"]').isVisible() && await owner.locator('[data-shop-section="noel"]').isHidden(), 'boutique : onglet du type de sa liste ouvert');
    // Première section = le type de la liste d'Etienne (anniversaire) : un article par habillage et par type.
    const mine = owner.locator('[data-shop-section="birthday"]');
    check(await owner.locator('#shop-dialog [data-shop-panel="skins"] .shop-item', { hasText: 'Pastel' }).count() === 5, 'boutique : Pastel vendu séparément pour chaque type de liste (5 articles)');
    // Aperçu : fausse liste aux couleurs de l'habillage, par-dessus la boutique.
    await mine.locator('.shop-item', { hasText: 'Néon' }).locator('[data-skin-preview]').click();
    check(await owner.locator('#skin-preview-dialog[open] [data-preview-title]').getAttribute('src').then((src) => src.includes('img/skins/neon/birthday/title.png')), 'aperçu : titre de l\'habillage');
    await owner.click('#skin-preview-dialog [data-close]');
    const pastel = mine.locator('.shop-item', { hasText: 'Pastel' });
    await pastel.locator('button[type=submit]').click();
    await Promise.all([owner.waitForNavigation(), owner.click('#confirm-dialog [data-confirm-ok]')]);
    check(await owner.locator('body').getAttribute('data-skin') === 'pastel', 'achat : la liste porte l\'habillage Pastel');
    check(await balance(owner) === before - 200, 'achat : 200 gemmes dépensées');
    check((await owner.locator('.hero__title img').getAttribute('src')).includes('img/skins/pastel/'), 'achat : titre de l\'habillage');

    /* ---- Vu par une amie ---- */
    await friend.goto(`${B}?user=${ETIENNE}`);
    check(await friend.locator('body').getAttribute('data-skin') === 'pastel', 'amie : voit la liste avec l\'habillage');

    /* ---- Trop cher ---- */
    await owner.click('.user-menu summary');
    await owner.click('.user-menu [data-open="shop-dialog"]');
    const zombie = owner.locator('[data-shop-section="birthday"] .shop-item', { hasText: 'Zombie' });
    if (await balance(owner) < 2000) {
        check(await zombie.locator('button[type=submit]').count() === 0 && (await zombie.innerText()).includes('encore'), 'habillage trop cher : pas de bouton, « encore N »');
    }

    /* ---- Retour au classique, puis paramètres de la liste ---- */
    await Promise.all([owner.waitForNavigation(), owner.locator('[data-shop-section="birthday"] .shop-item', { hasText: 'Classique' }).locator('button[type=submit]').click()]);
    check(await owner.locator('body').getAttribute('data-skin') === null, 'retour à l\'habillage classique');
    await owner.click('.topbar__settings');
    check(await owner.locator('#list-settings-dialog [data-skin-field] .skin-field__option:visible').count() === 2, 'paramètres : seuls les habillages de ce type de liste sont proposés (classique + Pastel)');
    await owner.locator('#list-settings-dialog .skin-field__option', { has: owner.locator('input[value="pastel/birthday"]') }).click();
    await Promise.all([owner.waitForNavigation(), owner.click('#list-settings-dialog .modal__footer button[type=submit]')]);
    check(await owner.locator('body').getAttribute('data-skin') === 'pastel', 'paramètres de la liste : habillage choisi');

    /* ---- Habillage non acheté refusé ---- */
    const refused = await owner.evaluate(async () => {
        const body = new FormData();
        body.append('skin', 'neon/birthday');
        const r = await fetch('actions/shopEquip.php', { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content } });
        return r.status;
    });
    check(refused === 403, 'habillage non acheté refusé (' + refused + ')');

    /* ---- Cadres et effets de compte à rebours ---- */
    await owner.goto(`${B}?user=${ETIENNE}`);
    const gems = await balance(owner);
    await owner.click('.gem-pill');
    await owner.click('[data-shop-cat="frame"]');
    check(await owner.locator('[data-shop-panel="frame"]').isVisible() && await owner.locator('[data-shop-panel="skins"]').isHidden(), 'catégorie « Cadres » : seuls les cadres sont affichés');
    check(await owner.locator('[data-shop-subnav]').isHidden(), 'catégorie « Cadres » : pas de choix du type de liste');
    check(await owner.locator('[data-shop-panel="frame"] .shop__rarity').count() === 6, 'cadres groupés par rareté (6 groupes)');
    const frameItems = await owner.locator('[data-shop-panel="frame"] .shop-item').count();
    check(frameItems > 20 && await owner.locator('[data-shop-panel="frame"] .shop-item .frame-host').count() === frameItems, 'aperçu de sa photo dans chaque cadre (et sans cadre)');
    check(await owner.locator('[data-shop-panel="frame"] .frame--img .frame__img').count() === 14, 'cadres illustrés : 14 images');
    if (gems >= 100) {
        await owner.locator('[data-shop-item="frame/trait"] button[type=submit]').click();
        await Promise.all([owner.waitForNavigation(), owner.click('#confirm-dialog [data-confirm-ok]')]);
        check(await owner.locator('.profile__avatar .frame--trait').count() === 1, 'cadre acheté : il entoure la photo de la liste');
        check(await balance(owner) === gems - 50, 'cadre : 50 gemmes dépensées');
        await friend.goto(`${B}?user=${ETIENNE}`);
        check(await friend.locator('.profile__avatar .frame--trait').count() === 1, 'amie : voit le cadre sur la liste');
        check(await friend.locator('.mini-frame[data-frame="trait"] .frame--trait').count() > 0, 'amie : même cadre sur les petites photos (amis…)');

        await owner.click('.gem-pill');
        await owner.click('[data-shop-cat="countdown"]');
        await owner.locator('[data-shop-item="countdown/pastel"] button[type=submit]').click();
        await Promise.all([owner.waitForNavigation(), owner.click('#confirm-dialog [data-confirm-ok]')]);
        await owner.click('.gem-pill');
        await owner.click('[data-shop-cat="countdown"]');
        check((await owner.locator('[data-shop-item="countdown/pastel"]').innerText()).includes('Sur ma liste'), 'effet acheté : porté sur sa liste');
        if (await owner.locator('[data-countdown]').count()) {
            check(await owner.locator('[data-countdown]').getAttribute('data-fx') === 'pastel', 'effet : compte à rebours animé');
        }

        // Compte à rebours : se change dans « Paramètres de la liste ».
        await owner.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
        await owner.locator('[data-open="list-settings-dialog"]').first().evaluate((b) => b.click());
        const fx = owner.locator('#list-settings-dialog .accessory-field--countdown');
        check(await fx.locator('input[value="pastel"]').isChecked(), 'paramètres de la liste : effet porté coché');
        await fx.locator('.skin-field__option', { hasText: 'Classique' }).click();
        await Promise.all([owner.waitForNavigation(), owner.click('#list-settings-dialog .modal__footer button[type=submit]')]);
        check(await owner.locator('[data-countdown][data-fx]').count() === 0 && await owner.locator('#list-settings-dialog .accessory-field--countdown input[value=""]').isChecked(), 'paramètres de la liste : retour au compte à rebours classique');

        // Cadre : se change dans « Mon profil ».
        await owner.locator('[data-open="profile-dialog"]').first().evaluate((b) => b.click());
        const frameField = owner.locator('#profile-dialog .accessory-field--frame');
        check(await frameField.locator('input[value="trait"]').isChecked(), 'mon profil : cadre porté coché');
        await frameField.locator('.skin-field__option', { hasText: 'Sans cadre' }).click();
        await Promise.all([owner.waitForNavigation(), owner.click('#profile-dialog .modal__footer button[type=submit]')]);
        check(await owner.locator('.profile__avatar .frame').count() === 0, 'mon profil : sans cadre, la photo retrouve son allure');
    }
    const wearRefused = await owner.evaluate(async () => {
        const body = new FormData();
        body.append('kind', 'frame');
        body.append('item', 'prisme');
        const r = await fetch('actions/shopWear.php', { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content } });
        return r.status;
    });
    check(wearRefused === 403, 'cadre non acheté refusé (' + wearRefused + ')');

    check(owner.errors.length + friend.errors.length === 0, 'aucune erreur JS ' + [...owner.errors, ...friend.errors].join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nBoutique OK');
    process.exit(failures ? 1 : 0);
})();
