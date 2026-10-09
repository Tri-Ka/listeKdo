/* Tableau Badges uniquement ; même compte local et même nettoyage que les tableaux Gemmes. */
const { chromium } = require('playwright-core');
const assert = require('node:assert/strict');
const B = 'http://localhost:8090/listeKdo/';

(async () => {
    const browser = await chromium.launch();
    try {
        const page = await (await browser.newContext({ viewport: { width: 1440, height: 1050 } })).newPage();
        const errors = [];
        page.on('pageerror', (e) => errors.push(e.message));
        await page.goto(B);
        await page.click('.topbar [data-open="login-dialog"]');
        await page.fill('#login-dialog [name=nom]', 'Test1gemtableadmin');
        await page.fill('#login-dialog [name=password]', 'test');
        await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
        await page.goto(B + 'admin.php?tab=badges');
        const table = page.locator('[data-badge-table]');
        const visible = () => table.locator('[data-badge-row]:visible');
        if (process.env.BADGE_TABLE_SCREENSHOT_ONLY) {
            await page.screenshot({ path: __dirname + '/out/badge-table-desktop.png', fullPage: true });
            assert.equal(await table.locator('.dt__scroll').evaluate((scroll) => scroll.scrollWidth <= scroll.clientWidth), true);
            await page.setViewportSize({ width: 390, height: 844 });
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
            await table.locator('[data-badge-search]').fill('Test1gemtablebadge137');
            await visible().locator('[data-badge-edit]').click();
            await page.screenshot({ path: __dirname + '/out/badge-table-mobile.png', fullPage: true });
            console.log('OK : toutes les colonnes visibles sur ordinateur et formulaire accessible sur mobile.');
            return;
        }
        assert.equal(await visible().count(), 10);
        const first = await visible().first().getAttribute('data-id');
        await table.locator('[data-badge-next]').click();
        assert.notEqual(await visible().first().getAttribute('data-id'), first);
        await table.locator('[data-badge-prev]').click();
        await table.locator('[data-badge-per]').selectOption('25');
        assert.equal(await visible().count(), 25);
        await table.locator('[data-badge-search]').fill('premiere idee');
        assert.equal(await visible().evaluateAll((rows) => rows.some((row) => row.dataset.name === 'Première idée')), true);
        await table.locator('[data-badge-search]').fill('introuvable-zzzz');
        assert.equal(await visible().count(), 0);
        assert.equal(await table.locator('[data-badge-empty]').isVisible(), true);
        await table.locator('[data-badge-search]').fill('');
        await table.locator('[data-badge-filter=category]').selectOption('Trophée');
        await table.locator('[data-badge-filter=tier]').selectOption('gold');
        await table.locator('[data-badge-filter=state]').selectOption('Actif');
        assert.equal(await visible().evaluateAll((rows) => rows.length > 0 && rows.every((r) => r.dataset.category === 'Trophée' && r.dataset.tier === 'gold' && r.dataset.state === 'Actif')), true);
        for (const filter of await table.locator('[data-badge-filter]').all()) await filter.selectOption('');
        await table.locator('[data-badge-search]').fill('Test1gemtablebadge');
        await table.locator('[data-badge-sort=amount]').click();
        assert.deepEqual(await visible().evaluateAll((rows) => rows.map((r) => Number(r.dataset.amount))), [9, 137]);
        console.log('OK : recherche, filtres combinés, pagination et tri numérique des badges.');

        const row = table.locator('[data-badge-row][data-name="Test1gemtablebadge137"]');
        const editor = page.locator('#badge-edit-dialog');
        await row.locator('[data-badge-edit]').click();
        await editor.locator('[name=description]').fill('Description modifiée <b>sans HTML</b>');
        await editor.locator('[name=gems]').fill('151');
        await editor.locator('[name=kind]').selectOption('trophy');
        await editor.locator('[name=tier]').selectOption('gold');
        await editor.locator('[name=secret]').check();
        assert.equal(await editor.locator('[name=gems]').inputValue(), '151');
        const [saved] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('actions/adminBadge.php') && r.request().method() === 'POST'),
            editor.locator('button[type=submit]').click(),
        ]);
        assert.equal((await saved.json()).ok, true);
        await page.waitForFunction(() => !document.querySelector('.badge-admin__form[aria-busy=true]'));
        assert.equal(await row.locator('[data-badge-amount]').innerText(), '151');
        assert.equal(await row.locator('[data-badge-kind]').innerText(), 'Trophée');
        assert.equal(await row.locator('[data-badge-tier]').innerText(), 'Or');
        assert.equal(await row.locator('[data-badge-secret]').innerText(), 'Secret');
        assert.equal(await row.locator('[data-badge-description]').innerText(), 'Description modifiée <b>sans HTML</b>');
        assert.equal(await row.locator('[data-badge-description] b').count(), 0);
        await row.locator('[data-badge-edit]').click();
        await editor.locator('[name=gems]').fill('0');
        const [automatic] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('actions/adminBadge.php') && r.request().method() === 'POST'),
            editor.locator('button[type=submit]').click(),
        ]);
        assert.equal((await automatic.json()).ok, true);
        await page.waitForFunction(() => !document.querySelector('.badge-admin__form[aria-busy=true]'));
        assert.equal(await row.locator('[data-badge-amount]').innerText(), '800');
        assert.equal(await row.locator('[data-badge-auto]').innerText(), 'Automatique');
        await row.locator('[data-badge-edit]').click();
        assert.equal(await editor.isVisible(), true);
        await editor.locator('.modal__close').click();
        await page.reload();
        await table.locator('[data-badge-search]').fill('Test1gemtablebadge137');
        assert.equal(await row.locator('[data-badge-amount]').innerText(), '800');
        await row.locator('[data-badge-edit]').click();
        assert.equal(await editor.locator('[name=gems]').inputValue(), '0');
        await page.screenshot({ path: __dirname + '/out/badge-table-edit.png', fullPage: true });
        await editor.locator('.modal__close').click();
        console.log('OK : édition en modale, enregistrement sans navigation, affichage mis à jour, gemmes automatiques et texte échappé.');

        await table.locator('[data-badge-search]').fill('');
        await table.locator('[data-badge-per]').selectOption('10');
        await page.screenshot({ path: __dirname + '/out/badge-table-desktop.png', fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
        await table.locator('[data-badge-search]').fill('Test1gemtablebadge137');
        await page.screenshot({ path: __dirname + '/out/badge-table-mobile.png', fullPage: true });
        const blank = page.locator('#badge-edit-dialog');
        await page.locator('[data-badge-create]').click();
        await blank.locator('[name=name]').fill('Test1gemtablebadge-new');
        await blank.locator('[name=threshold]').fill('100000');
        await blank.locator('[name=gems]').fill('79');
        await blank.locator('[name=active]').uncheck();
        await Promise.all([page.waitForNavigation(), blank.locator('button[type=submit]').click()]);
        await table.locator('[data-badge-search]').fill('Test1gemtablebadge-new');
        assert.equal(await visible().count(), 1);
        assert.equal(await visible().locator('[data-badge-amount]').innerText(), '79');
        assert.deepEqual(errors, []);
        console.log('OK : ordinateur et mobile sans débordement, création accessible hors pagination, aucune erreur JavaScript.');
    } finally {
        await browser.close();
    }
})().catch((error) => { console.error(error); process.exit(1); });
