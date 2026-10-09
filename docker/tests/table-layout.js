/* Mise en page des six tableaux d’administration et commandes locales concernées. */
const { chromium } = require('playwright-core');
const assert = require('node:assert/strict');
const B = 'http://localhost:8090/listeKdo/';
const cases = [
    ['users', 'admin.php?tab=users', '[data-dt]'],
    ['lists', 'admin.php?tab=lists', '[data-dt]'],
    ['badges', 'admin.php?tab=badges', '[data-badge-table]'],
    ['actions', 'admin.php?tab=gems#gem-actions', '[data-gem-table=actions]'],
    ['rewards', 'admin.php?tab=gems#gem-badges', '[data-gem-table=badges]'],
    ['shop', 'admin.php?tab=gems#gem-shop', '[data-gem-table=shop]'],
];

(async () => {
    const browser = await chromium.launch();
    try {
        const page = await (await browser.newContext()).newPage();
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.goto(B);
        await page.click('.topbar [data-open="login-dialog"]');
        await page.fill('#login-dialog [name=nom]', 'Test1gemtableadmin');
        await page.fill('#login-dialog [name=password]', 'test');
        await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
        for (const width of [1440, 390]) {
            await page.setViewportSize({ width, height: 1050 });
            let reference;
            for (const [name, url, selector] of cases) {
                await page.goto(B + url);
                const root = page.locator(selector);
                await root.waitFor({ state: 'visible' });
                const panel = root.locator('xpath=ancestor-or-self::*[contains(concat(" ", normalize-space(@class), " "), " panel ")]').last();
                assert.equal(await panel.locator('.panel').count(), 0, name + ': pas de cadre imbriqué');
                const layout = await panel.evaluate((element) => {
                    const head = element.querySelector('.panel__head');
                    const filters = element.querySelector('.dt__bar');
                    const table = element.querySelector('.dt');
                    const search = head.querySelector('.search');
                    const style = getComputedStyle(head);
                    const font = getComputedStyle(table.querySelector('th'));
                    const footer = getComputedStyle(element.querySelector('.dt__footer'));
                    return {
                        padding: style.padding, font: font.fontSize, background: font.backgroundColor,
                        footerPadding: footer.padding, footerBackground: footer.backgroundColor,
                        ordered: head.getBoundingClientRect().bottom <= filters.getBoundingClientRect().top + 1
                            && filters.getBoundingClientRect().bottom <= table.getBoundingClientRect().top + 1,
                        right: element.getBoundingClientRect().right - search.getBoundingClientRect().right,
                        noOverflow: document.documentElement.scrollWidth <= innerWidth,
                    };
                });
                assert.equal(layout.ordered, true, name + ': recherche, filtres, colonnes');
                assert.equal(layout.noOverflow, true, name + ': pas de débordement');
                if (width === 1440) assert.ok(Math.abs(layout.right - 21) < 2, name + ': recherche à droite');
                if (!reference) reference = layout;
                assert.deepEqual([layout.padding, layout.font, layout.background, layout.footerPadding, layout.footerBackground],
                    [reference.padding, reference.font, reference.background, reference.footerPadding, reference.footerBackground], name + ': styles identiques aux utilisateurs');
                await page.screenshot({ path: __dirname + '/out/table-layout-' + name + '-' + width + '.png' });
            }
            console.log('OK : les six tableaux partagent la même disposition et les mêmes styles à ' + width + ' px.');
        }
        await page.setViewportSize({ width: 1440, height: 1050 });
        await page.goto(B + 'admin.php?tab=badges');
        const badges = page.locator('[data-badge-table]');
        await badges.locator('[data-badge-page-number="2"]').click();
        assert.equal(await badges.locator('.dt__page[aria-current=page]').innerText(), '2');
        assert.equal(await badges.locator('[data-badge-count] strong').innerText(), '11–20');
        await badges.locator('[data-badge-search]').fill('Test1gemtablebadge');
        assert.equal(await badges.locator('[data-badge-pagination]').isVisible(), false);
        await badges.locator('[data-badge-sort=amount]').click();
        assert.deepEqual(await badges.locator('[data-badge-row]:visible').evaluateAll((rows) => rows.map((row) => Number(row.dataset.amount))), [9, 137]);
        assert.equal(await badges.locator('[data-badge-sort=amount] use').getAttribute('href').then((href) => href.endsWith('#i-sort-up')), true);
        await page.goto(B + 'admin.php?tab=gems#gem-shop');
        const shop = page.locator('[data-gem-table=shop]');
        await shop.locator('[data-gem-page-number="2"]').click();
        assert.equal(await shop.locator('.dt__page[aria-current=page]').innerText(), '2');
        await shop.locator('[data-gem-per]').selectOption('100');
        assert.equal(await shop.locator('[data-gem-pagination]').isVisible(), false);
        assert.deepEqual(errors, []);
        console.log('OK : pagination numérotée, compteurs, icônes de tri et choix du nombre de lignes.');
    } finally { await browser.close(); }
})().catch((error) => { console.error(error); process.exit(1); });
