const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
(async () => {
    const browser = await chromium.launch();
    for (const [w, h] of [[1200, 800], [1440, 900], [390, 844]]) {
        const page = await (await browser.newContext({ viewport: { width: w, height: h } })).newPage();
        page.on('pageerror', (e) => console.log('pageerror', e.message));
        await page.goto(B);
        await page.click('.topbar [data-open="login-dialog"]');
        await page.fill('#login-dialog input[name=nom]', 'Etienne');
        await page.fill('#login-dialog input[name=password]', 'test');
        await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
        await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
        await page.locator('[data-open="shop-dialog"]').first().evaluate((b) => b.click());
        await page.waitForTimeout(500);
        if (process.env.FRAME) {
            await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
            await page.locator('.hero').screenshot({ path: `out/hero-${w}.jpg`, type: 'jpeg', quality: 75 });
            await page.locator('[data-open="shop-dialog"]').first().evaluate((b) => b.click());
            await page.waitForTimeout(300);
        }
        await page.screenshot({ path: `out/shop-${w}.jpg`, type: 'jpeg', quality: 70 });
        if (process.env.CAT) {
            await page.click(`[data-shop-cat="${process.env.CAT}"]`);
            await page.waitForTimeout(600);
            await page.screenshot({ path: `out/shop-${process.env.CAT}-${w}.jpg`, type: 'jpeg', quality: 70 });
            await page.locator('.shop__body').evaluate((b, y) => { b.scrollTop = y; document.querySelector('#shop-dialog').scrollTop = 700 + y; }, +(process.env.SCROLL || 700));
            await page.waitForTimeout(400);
            await page.screenshot({ path: `out/shop-${process.env.CAT}-b-${w}.jpg`, type: 'jpeg', quality: 70 });
        }
    }
    await browser.close();
})();
