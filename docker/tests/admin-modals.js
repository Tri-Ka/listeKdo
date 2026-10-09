/* Modales et colonne Actions uniquement ; nettoyage des fixtures par le lanceur. */
const { chromium } = require('playwright-core');
const assert = require('node:assert/strict');
const B = 'http://localhost:8090/listeKdo/';
const save = async (page, dialog, action) => {
    const [response] = await Promise.all([
        page.waitForResponse((r) => r.url().includes('actions/' + action + '.php') && r.request().method() === 'POST'),
        dialog.locator('button[type=submit]').click(),
    ]);
    assert.equal((await response.json()).ok, true);
    await dialog.waitFor({ state: 'hidden' });
    return response.request().postData();
};
const sticky = async (table) => {
    const scroll = table.locator('.dt__scroll');
    const row = table.locator('tbody tr:visible').first();
    for (const end of [false, true]) {
        await scroll.evaluate((el, end) => { el.scrollLeft = end ? el.scrollWidth : 0; }, end);
        assert.equal(await row.locator('.dt__actions').evaluate((cell) => {
            const container = cell.closest('.dt__scroll').getBoundingClientRect();
            const rect = cell.getBoundingClientRect();
            return getComputedStyle(cell).position === 'sticky' && rect.left >= container.left - 1 && rect.right <= container.right + 1;
        }), true, 'Actions visibles aux deux extrémités du tableau');
    }
};
(async () => {
    const browser = await chromium.launch();
    try {
        const page = await (await browser.newContext({ viewport: { width: 1100, height: 900 } })).newPage();
        page.setDefaultTimeout(7000);
        const errors = [];
        page.on('pageerror', (e) => errors.push(e.message));
        await page.goto(B);
        await page.click('.topbar [data-open="login-dialog"]');
        await page.fill('#login-dialog [name=nom]', 'Test1gemtableadmin');
        await page.fill('#login-dialog [name=password]', 'test');
        await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
        if (process.env.ADMIN_MODAL_SECTION !== 'gems') {
        for (const tab of ['users', 'lists', 'badges']) {
            await page.goto(B + 'admin.php?tab=' + tab);
            await sticky(page.locator('.panel').filter({ has: page.locator('.dt__scroll') }).first());
        }
        const table = page.locator('[data-badge-table]');
        assert.equal(await table.locator('tbody form').count(), 0);
        await table.locator('[data-badge-per]').selectOption('25');
        await table.locator('[data-badge-search]').fill('Test1gemtablebadge');
        const row = table.locator('[data-badge-row][data-name="Test1gemtablebadge137"]');
        const dialog = page.locator('#badge-edit-dialog');
        await row.locator('[data-badge-edit]').click();
        assert.equal(await dialog.locator('[name=gems]').inputValue(), '137');
        await dialog.locator('[name=description]').fill('Modale <b>texte</b>');
        await dialog.locator('[name=gems]').fill('151');
        await dialog.locator('[name=kind]').selectOption('trophy');
        await dialog.locator('[name=tier]').selectOption('gold');
        await dialog.locator('[name=secret]').check();
        await save(page, dialog, 'adminBadge');
        assert.equal(await row.locator('[data-badge-amount]').innerText(), '151');
        assert.equal(await row.locator('[data-badge-description]').innerText(), 'Modale <b>texte</b>');
        assert.equal(await row.locator('[data-badge-description] b').count(), 0);
        assert.equal(await table.locator('[data-badge-per]').inputValue(), '25');
        assert.equal(await table.locator('[data-badge-search]').inputValue(), 'Test1gemtablebadge');
        await row.locator('[data-badge-edit]').click();
        assert.equal(await dialog.locator('[name=gems]').inputValue(), '151');
        await dialog.locator('[name=gems]').fill('0');
        await save(page, dialog, 'adminBadge');
        assert.equal(await row.locator('[data-badge-amount]').innerText(), '800');
        await row.locator('[data-badge-edit]').click();
        await dialog.locator('[name=gems]').fill('999');
        await page.route('**/actions/adminBadge.php', (route) => route.fulfill({ status: 500, contentType: 'application/json', body: JSON.stringify({ ok: false, message: 'Erreur simulée' }) }), { times: 1 });
        await dialog.locator('button[type=submit]').click();
        await dialog.locator('.toast--error').waitFor();
        assert.equal(await dialog.locator('[name=gems]').inputValue(), '999');
        assert.equal(await row.locator('[data-badge-amount]').innerText(), '800');
        await dialog.locator('.modal__close').click();
        await row.locator('[data-badge-edit]').click();
        assert.equal(await dialog.locator('[name=gems]').inputValue(), '0');
        await page.setViewportSize({ width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
        await dialog.locator('button[type=submit]').scrollIntoViewIfNeeded();
        await page.screenshot({ path: __dirname + '/out/admin-badge-modal-mobile.png' });
        await dialog.locator('button[data-close]').last().click();
        await page.locator('[data-badge-create]').click();
        await dialog.locator('[name=name]').fill('Test1gemtablebadge-new');
        await dialog.locator('[name=threshold]').fill('100000');
        await dialog.locator('[name=active]').uncheck();
        await Promise.all([page.waitForNavigation(), dialog.locator('button[type=submit]').click()]);
        await table.locator('[data-badge-search]').fill('Test1gemtablebadge-new');
        assert.equal(await table.locator('[data-badge-row]:visible').count(), 1);
        console.log('OK : édition et création des badges en modale, sauvegarde, annulation, erreur, mobile.');

        } else {
            await page.goto(B + 'admin.php?tab=badges');
            await page.setViewportSize({ width: 390, height: 844 });
            await page.locator('[data-badge-row]:visible').first().locator('[data-badge-edit]').click();
            const mobile = page.locator('#badge-edit-dialog');
            assert.equal(await mobile.locator('form').evaluate((el) => getComputedStyle(el).display), 'grid');
            await mobile.locator('button[type=submit]').scrollIntoViewIfNeeded();
            await page.screenshot({ path: __dirname + '/out/admin-badge-modal-mobile.png' });
            await mobile.locator('.modal__close').click();
        }
        await page.setViewportSize({ width: 1100, height: 900 });
        await page.goto(B + 'admin.php?tab=gems');
        const gemDialog = page.locator('#gem-edit-dialog');
        for (const [section, name, value] of [['actions', 'rate[ideas]', '17'], ['badges', 'badge_gems', '0'], ['shop', 'price[pastel]', '207']]) {
            await page.click(`[data-gem-section="${section}"]`);
            const gems = page.locator(`[data-gem-table="${section}"]`);
            assert.equal(await gems.locator('tbody input, tbody form').count(), 0);
            // Force un débordement pour vérifier aussi les petits tableaux.
            await gems.locator('table').evaluate((el) => { el.style.minWidth = '1200px'; });
            await sticky(gems);
            const reward = section === 'badges'
                ? gems.locator('[data-gem-row]', { hasText: 'Test1gemtablebadge137' })
                : gems.locator('[data-gem-row]').filter({ has: page.locator('[data-gem-edit]') });
            let target = reward;
            if (section !== 'badges') {
                target = gems.locator('[data-gem-definition*=' + JSON.stringify('\"name\":\"' + name + '\"') + ']');
                if (await target.isHidden()) await gems.locator('[data-gem-search]').fill(await target.getAttribute('data-name'));
            } else await gems.locator('[data-gem-search]').fill('Test1gemtablebadge137');
            await target.locator('[data-gem-edit]').click();
            await gemDialog.locator('[name=amount]').fill(value);
            const payload = await save(page, gemDialog, 'adminGems');
            assert.equal(payload.includes(`name="${name}"`), true);
            assert.equal(payload.includes('name="amount"'), false);
            assert.equal(payload.includes('name="rate[comments]"'), false);
            assert.equal(await target.locator('[data-gem-amount]').innerText(), section === 'badges' ? String(JSON.parse(await target.getAttribute('data-gem-definition')).default) : value);
            await target.locator('[data-gem-edit]').click();
            assert.equal(await gemDialog.locator('[name=amount]').inputValue(), value);
            await gemDialog.locator('button[data-close]').last().click();
        }
        const shop = page.locator('[data-gem-table=shop]');
        await shop.locator('[data-gem-search]').fill('Pastel');
        const pastel = shop.locator('[data-gem-row]:visible').first();
        await pastel.locator('[data-gem-edit]').click();
        await page.route('**/actions/adminGems.php', (route) => route.fulfill({ status: 500, contentType: 'application/json', body: JSON.stringify({ ok: false, message: 'Erreur simulée' }) }), { times: 1 });
        await gemDialog.locator('[name=amount]').fill('999');
        await gemDialog.locator('button[type=submit]').click();
        await gemDialog.locator('.toast--error').waitFor();
        assert.equal(await gemDialog.locator('[name=amount]').inputValue(), '999');
        assert.equal(await pastel.locator('[data-gem-amount]').innerText(), '207');
        await page.setViewportSize({ width: 390, height: 844 });
        await shop.locator('table').evaluate((el) => { el.style.minWidth = ''; });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
        await page.screenshot({ path: __dirname + '/out/admin-gem-modal-mobile.png' });
        assert.deepEqual(errors, []);
        console.log('OK : colonne Actions fixe, modales de gemmes, sauvegarde isolée, montant automatique, erreur sans perte de saisie.');
    } finally { await browser.close(); }
})().catch((error) => { console.error(error); process.exit(1); });
