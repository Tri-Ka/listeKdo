/*
 * Cadeau à plusieurs : un participant dit « Je le prends » (c'est lui qui achète le cadeau).
 * Mallory participe à une idée d'Etienne, la prend puis ne la prend plus ; Etienne n'en voit rien.
 * Lancé par run-buyer.sh (mots de passe « test », idée supprimée à la fin).
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

const post = (page, url, data) => page.evaluate(async ([url, data]) => {
    const body = new URLSearchParams({ ...data, _token: document.querySelector('meta[name=csrf-token]').content });
    const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
    return { status: response.status, data: await response.json().catch(() => null) };
}, [`${B}actions/${url}`, data]);

(async () => {
    const browser = await chromium.launch();
    const owner = await session(browser, 'Etienne');
    const friend = await session(browser, 'Mallory');
    const name = 'Cagnotte acheteur test ' + Date.now();

    await owner.goto(`${B}?user=${ETIENNE}`);
    check((await post(owner, 'addObject.php', { owner: ETIENNE, nom: name, price: '80' })).status === 200, 'idée à 80 € ajoutée');
    await friend.goto(`${B}?user=${ETIENNE}`);
    const id = await friend.locator('.card', { hasText: name }).getAttribute('data-object');

    check((await post(friend, 'participate.php', { id, do: 'buy' })).status === 409, 'sans participer : « Je le prends » refusé');
    await friend.locator(`#idea-${id} [data-gift-choice]`).click();
    await friend.click('#gift-choice-dialog [data-gift-group]');
    await friend.fill(`#object-${id} input[name=amount]`, '30');
    await friend.click(`#object-${id} button[value=join]`);
    await friend.waitForSelector(`#object-${id} button[value=buy]`);
    check((await friend.locator(`#object-${id} .group`).innerText()).includes("Personne n'a encore dit"), 'indication : personne ne le prend encore');

    await friend.click(`#object-${id} button[value=buy]`);
    await friend.waitForSelector(`#object-${id} .group__buyer`);
    const group = await friend.locator(`#object-${id} .group`).innerText();
    check(group.includes('Vous le prenez') && group.includes('Vous achetez le cadeau'), 'après « Je le prends » : « Vous le prenez »');
    check(await friend.locator(`#object-${id} button[value=unbuy]`).count() === 1 && await friend.locator(`#object-${id} button[value=buy]`).count() === 0, 'bouton « Je ne le prends plus »');
    await friend.screenshot({ path: `${__dirname}/out/buyer-dialog.png` });
    await friend.keyboard.press('Escape');
    check((await friend.locator(`#idea-${id} .group-pill`).getAttribute('data-tip')).includes('vous le prenez'), 'info-bulle de la vignette : « vous le prenez »');

    await owner.reload();
    const ownerHtml = await owner.content();
    check(!ownerHtml.includes('group__buyer') && !ownerHtml.includes('le prenez'), 'propriétaire : aucune information');

    await friend.locator(`#idea-${id} .group-pill`).click();
    await friend.click(`#object-${id} button[value=unbuy]`);
    await friend.waitForSelector(`#object-${id} button[value=buy]`);
    check(await friend.locator(`#object-${id} .group__buyer`).count() === 0, 'après « Je ne le prends plus » : plus d\'acheteur');

    check((await post(owner, 'deleteObject.php', { id })).status === 200, 'nettoyage : idée supprimée');
    check(friend.errors.length === 0 && owner.errors.length === 0, 'aucune erreur JS ' + friend.errors.concat(owner.errors).join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nAcheteur OK');
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
