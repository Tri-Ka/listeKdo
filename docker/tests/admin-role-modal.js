/* Changement de rôle en modale, sur un compte créé et supprimé par le lanceur. */
const { chromium } = require('playwright-core');
const assert = require('node:assert/strict');
const B = 'http://localhost:8090/listeKdo/';
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
        await page.goto(B + 'admin.php?tab=users&q=Test1gemtablerole');
        assert.equal(await page.locator('tbody [name=role]').count(), 0);
        const edit = page.locator('[data-admin-edit-role]');
        const modal = page.locator('#admin-role');
        await edit.click();
        assert.equal(await modal.locator('[name=role]').inputValue(), 'user');
        await modal.locator('[name=role]').selectOption('admin');
        const save = async () => {
            const [response] = await Promise.all([
                page.waitForResponse((r) => r.url().includes('actions/adminRole.php')),
                modal.locator('button[type=submit]').click(),
            ]);
            assert.equal((await response.json()).ok, true);
            await modal.waitFor({ state: 'hidden' });
            await page.waitForFunction(() => !document.querySelector('[data-dt][aria-busy=true]'));
        };
        await save();
        assert.equal(await edit.getAttribute('data-role'), 'admin');
        assert.equal(await page.locator('[data-admin-confirm=delete]').count(), 0);
        assert.equal(await page.locator('[data-dt-search] [name=q]').inputValue(), 'Test1gemtablerole');
        await edit.click();
        await modal.locator('[name=role]').selectOption('user');
        await page.route('**/actions/adminRole.php', (route) => route.fulfill({ status: 500, contentType: 'application/json', body: JSON.stringify({ ok: false, message: 'Erreur simulée' }) }), { times: 1 });
        await modal.locator('button[type=submit]').click();
        await modal.locator('.toast--error').waitFor();
        assert.equal(await edit.getAttribute('data-role'), 'admin');
        assert.equal(await modal.locator('[name=role]').inputValue(), 'user');
        await save();
        assert.equal(await edit.getAttribute('data-role'), 'user');
        assert.equal(await page.locator('[data-admin-confirm=delete]').count(), 1);
        await edit.click();
        await page.setViewportSize({ width: 390, height: 844 });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
        await modal.locator('button[data-close]').last().click();
        assert.equal(await edit.getAttribute('data-role'), 'user');
        await page.goto(B + 'admin.php?tab=users&q=Test1gemtableadmin');
        assert.equal(await page.locator('[data-admin-edit-role]').count(), 0);
        assert.deepEqual(errors, []);
        console.log('OK : rôle en modale, sauvegarde et annulation, erreur sans perte de saisie, recherche conservée, propre rôle protégé.');
    } finally { await browser.close(); }
})().catch((error) => { console.error(error); process.exit(1); });
