/* Réglage admin et raccourcissement depuis le navigateur ; --live teste aussi l'origine Free. */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
const OWNER = 'e3370a0bb2c2ea49f93b68c0649d57b6';
let failures = 0;
const check = (ok, label) => {
    console.log((ok ? 'OK   ' : 'FAIL ') + label);
    if (!ok) failures++;
};

async function login(browser, name) {
    const page = await (await browser.newContext()).newPage();
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    return page;
}

async function action(page, fields, token = true) {
    return page.evaluate(async ({ fields, token }) => {
        const body = new FormData();
        Object.entries(fields).forEach(([name, value]) => body.set(name, value));
        const headers = { Accept: 'application/json' };
        if (token) headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch('actions/adminShortLinks.php', { method: 'POST', headers, body });
        return { status: response.status, data: await response.json() };
    }, { fields, token });
}

(async () => {
    const browser = await chromium.launch();
    let admin;
    let previous;
    try {
        admin = await login(browser, 'Etienne');
        await admin.goto(B + 'admin.php?tab=settings');
        previous = await admin.locator('[name=provider]').inputValue();
        check(await admin.locator('.admin-nav [aria-current=page]').innerText() === 'Réglages', 'onglet Réglages actif');
        check(await admin.locator('[name=provider] option').count() === 6, 'choix de quatre services, automatique et désactivation');
        const errors = [];
        admin.on('pageerror', (error) => errors.push(error.message));

        const friend = await login(browser, 'Mallory');
        const forbidden = await action(friend, { op: 'save', provider: 'dagd' });
        check(forbidden.status === 403, 'réglage refusé à un non-admin');
        const csrf = await action(admin, { op: 'save', provider: 'dagd' }, false);
        check(csrf.status === 419, 'réglage refusé sans CSRF');
        const invalid = await action(admin, { op: 'save', provider: 'https://evil.test' });
        check(!invalid.data.ok, 'endpoint arbitraire refusé');

        let responseBody = 'https://da.gd/test-123';
        let responseStatus = 200;
        let requests = [];
        await admin.route('https://da.gd/s?*', async (route) => {
            requests.push(route.request().url());
            await route.fulfill({ status: responseStatus, body: responseBody, headers: { 'Access-Control-Allow-Origin': '*' } });
        });
        await admin.selectOption('[name=provider]', 'dagd');
        await admin.click('[data-short-link-test]');
        await admin.waitForFunction(() => document.querySelector('[data-short-link-status]').textContent.includes('https://da.gd/test-123'));
        await admin.reload();
        check(await admin.locator('[name=provider]').inputValue() === previous, 'Tester ne change pas le service enregistré');

        await admin.selectOption('[name=provider]', 'dagd');
        await Promise.all([admin.waitForNavigation(), admin.click('button[name=op][value=save]')]);
        check(await admin.locator('[name=provider]').inputValue() === 'dagd', 'choix da.gd enregistré');
        await admin.screenshot({ path: __dirname + '/out/admin-short-links.png', fullPage: true });
        await admin.setViewportSize({ width: 390, height: 844 });
        check(await admin.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'réglages lisibles sur mobile sans débordement');
        await admin.screenshot({ path: __dirname + '/out/admin-short-links-mobile.png', fullPage: true });
        await admin.setViewportSize({ width: 1280, height: 900 });
        requests = [];
        await admin.goto(B + '?user=' + OWNER);
        await admin.waitForFunction(() => document.querySelector('[data-share-url]')?.value === 'https://da.gd/test-123');
        check(await admin.locator('.share--whatsapp').getAttribute('href').then((url) => url.includes(encodeURIComponent('https://da.gd/test-123'))), 'partage WhatsApp mis à jour');
        const referral = admin.locator('#share-dialog [data-referral-link]');
        if (await referral.count()) {
            check((await referral.inputValue()).startsWith(B), 'parrainage local conservé sans envoi au service tiers');
        }
        check(requests.length === new Set(requests).size, 'un seul appel tiers par lien, même dans plusieurs blocs');
        requests = [];
        const duplicates = await admin.evaluate(async () => {
            const { resolveShortLink } = await import('./js/modules/short-links.js');
            const long = 'http://datcharrye.free.fr/listeKdo/?parrain=test';
            const data = { url: long, long, browser: { provider: 'dagd', endpoint: 'https://da.gd/s?url=', site: 'http://datcharrye.free.fr/listeKdo/' } };
            return Promise.all([resolveShortLink(data), resolveShortLink(data)]);
        });
        check(requests.length === 1 && duplicates.every((url) => url === 'https://da.gd/test-123'), 'deux demandes simultanées partagent le même appel');

        responseBody = 'https://evil.test/forged';
        await admin.reload();
        await admin.waitForTimeout(500);
        check((await admin.locator('[data-share-url]').inputValue()).startsWith('http://datcharrye.free.fr/'), 'réponse tiers invalide : lien complet conservé');
        responseStatus = 503;
        await admin.reload();
        await admin.waitForTimeout(500);
        check((await admin.locator('[data-share-url]').inputValue()).startsWith('http://datcharrye.free.fr/'), 'service indisponible : lien complet conservé');

        await action(admin, { op: 'save', provider: 'none' });
        requests = [];
        await admin.reload();
        await admin.waitForTimeout(500);
        check(requests.length === 0, 'désactivation : aucun appel tiers');
        check(errors.length === 0, 'aucune erreur JavaScript');

        if (process.argv.includes('--live')) {
            const live = await (await browser.newContext()).newPage();
            await live.goto('http://datcharrye.free.fr/listeKdo/');
            const result = await live.evaluate(async () => {
                const long = 'http://datcharrye.free.fr/listeKdo/';
                const response = await fetch('https://da.gd/s?url=' + encodeURIComponent(long), {
                    credentials: 'omit', referrerPolicy: 'no-referrer', signal: AbortSignal.timeout(6000),
                });
                return { ok: response.ok, short: (await response.text()).trim() };
            });
            check(result.ok && /^https:\/\/da\.gd\/[A-Za-z0-9_-]+$/.test(result.short), 'appel réel depuis une page Free : ' + result.short);
        }
    } finally {
        if (admin && previous) await action(admin, { op: 'save', provider: previous });
        await browser.close();
    }
    process.exitCode = failures ? 1 : 0;
})().catch((error) => { console.error(error); process.exitCode = 1; });
