/* Solde synchronisé et animation des gemmes après une action ou un changement dans un autre onglet. */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

async function post(page, action, values) {
    return page.evaluate(async ({ action, values }) => (await fetch(`actions/${action}.php`, {
        method: 'POST', body: new URLSearchParams(values),
        headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]').content },
    })).json(), { action, values });
}

const balance = (page) => page.evaluate(async () => (await (await fetch('actions/gems.php')).json()).balance);
const waitBalance = (page, value) => page.waitForFunction((expected) =>
    [...document.querySelectorAll('.gem-pill__value, .user-menu__count--gems, [data-gems-balance]')]
        .every((counter) => Number(counter.textContent) === expected), value);

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext({ viewport: { width: 1366, height: 900 } });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    const fixtures = [];
    try {
        await page.goto(B);
        await page.click('.topbar [data-open="login-dialog"]');
        await page.fill('#login-dialog input[name=nom]', 'Etienne');
        await page.fill('#login-dialog input[name=password]', 'test');
        await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);

        const idea = await post(page, 'addObject', { nom: 'Test solde boutique ' + Date.now() });
        if (!idea.ok) throw new Error(idea.message);
        fixtures.push(idea.id);
        await page.reload();
        const initial = await balance(page);
        const card = page.locator(`#idea-${idea.id}`);

        await card.locator('[data-reaction-toggle]').click();
        await card.locator('.reactions__choices button[value="3"]').click();
        await page.waitForSelector(`#idea-${idea.id} .reactions__choices button[value="3"][aria-pressed=true]`, { state: 'attached' });
        const gained = await balance(page);
        check(gained > initial, 'une réaction rapporte des gemmes');
        await page.click('.gem-pill');
        await waitBalance(page, gained);
        check(true, 'gain sans rechargement : boutique, pastille et menu affichent le même solde');
        await page.screenshot({ path: `${__dirname}/out/gems-wallet.png` });

        await page.click('#shop-dialog [data-close]');
        await card.locator('[data-reaction-toggle]').click();
        await card.locator('.reactions__choices button[value="3"]').click();
        await page.waitForSelector(`#idea-${idea.id} .reactions__choices button[value="3"][aria-pressed=true]`, { state: 'detached' });
        const removed = await balance(page);
        check(removed < gained, 'retirer la réaction diminue le solde');
        await page.waitForSelector('.gem-loss');
        check((await page.locator('.gem-loss').last().textContent()).trim() === `−${gained - removed}`, 'annulation : le montant des gemmes perdues apparaît');
        await waitBalance(page, removed);
        check(true, 'diminution : tous les compteurs sont synchronisés');

        // Même solde sur le serveur, mais un compteur de boutique obsolète.
        await page.evaluate(() => { document.querySelector('[data-gems-balance]').textContent = '0'; });
        await page.click('.gem-pill');
        await waitBalance(page, removed);
        check(true, 'ouvrir la boutique corrige un compteur obsolète même si la pastille est déjà à jour');
        await page.click('#shop-dialog [data-close]');

        const other = await page.context().newPage();
        await other.goto(B);
        const extra = await post(other, 'addObject', { nom: 'Test solde autre onglet ' + Date.now() });
        if (!extra.ok) throw new Error(extra.message);
        fixtures.push(extra.id);
        const external = await balance(other);
        check(external > removed, 'une idée ajoutée dans un autre onglet augmente le solde');
        await page.bringToFront();
        await page.click('.gem-pill');
        await waitBalance(page, external);
        check(true, 'gain dans un autre onglet : solde repris à l’ouverture de la boutique');

        await other.bringToFront();
        const deleted = await post(other, 'deleteObject', { id: extra.id });
        if (!deleted.ok) throw new Error(deleted.message);
        fixtures.pop();
        const afterDelete = await balance(other);
        check(afterDelete < external, 'supprimer cette idée diminue le solde');
        await page.bringToFront();
        // Simule le retour du focus sur une boutique restée ouverte.
        await page.evaluate(() => window.dispatchEvent(new Event('focus')));
        await waitBalance(page, afterDelete);
        check(true, 'boutique ouverte : solde actualisé au retour depuis un autre onglet');

        await page.click('#shop-dialog [data-close]');
        await page.waitForSelector('.gem-layer', { state: 'detached' });
        await card.locator('[data-card-menu] summary').click();
        await card.locator('[data-open-object-form]').click();
        await page.click('#object-form-dialog [data-delete-object]');
        await page.click('#confirm-dialog [data-confirm-ok]');
        await card.waitFor({ state: 'detached' });
        fixtures.pop();
        const afterOwnDelete = await balance(page);
        check(afterOwnDelete < afterDelete, 'supprimer une idée depuis sa carte diminue le solde');
        await page.waitForSelector('.gem-loss');
        check((await page.locator('.gem-loss').textContent()).trim() === `−${afterDelete - afterOwnDelete}`, 'suppression : le montant des gemmes perdues apparaît');
        await page.screenshot({ path: `${__dirname}/out/gems-loss.png` });
        await waitBalance(page, afterOwnDelete);
        await page.waitForSelector('.gem-layer', { state: 'detached' });
        check(true, 'après la suppression : compteurs actualisés et animation terminée');
        check(errors.length === 0, 'aucune erreur JS ' + errors.join(' | '));
    } finally {
        for (const id of fixtures) await post(page, 'deleteObject', { id }).catch(() => {});
        await browser.close();
    }
    console.log(failures ? `\n${failures} échec(s)` : '\nSolde de gemmes OK');
    process.exit(failures ? 1 : 0);
})().catch((error) => { console.error(error); process.exit(2); });
