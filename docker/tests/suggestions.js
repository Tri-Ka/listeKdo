/*
 * Idées suggérées par les amis : Mallory suggère une idée sur la liste d'Etienne,
 * qui ne doit jamais la voir (liste, notifications, actions directes).
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
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    return page;
}

// POST en JSON depuis la page, avec son jeton CSRF : renvoie le code HTTP et la réponse.
const post = (page, url, data) => page.evaluate(async ([url, data]) => {
    const body = new URLSearchParams({ ...data, _token: document.querySelector('meta[name=csrf-token]').content });
    const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' } });
    return { status: response.status, data: await response.json().catch(() => null) };
}, [`${B}actions/${url}`, data]);

(async () => {
    const browser = await chromium.launch();
    const owner = await session(browser, 'Etienne');
    const friend = await session(browser, 'Mallory');
    const name = 'Suggestion test ' + Date.now();

    /* ---- Mallory suggère une idée ---- */
    await friend.goto(`${B}?user=${ETIENNE}`);
    check(await friend.locator('.card--suggest').count() === 1, 'amie : vignette « Suggérer une idée »');
    check(await friend.locator('.card--add:not(.card--suggest)').count() === 0, 'amie : pas de vignette « Ajouter une idée »');
    await friend.click('.card--suggest');
    check((await friend.textContent('#object-form-title')).trim() === 'Suggérer une idée', 'fenêtre « Suggérer une idée »');
    check((await friend.locator('#object-form-dialog .form__notice').innerText()).includes('ne verra jamais'), 'fenêtre : « Etienne ne verra jamais cette idée »');
    await friend.fill('#object-form-dialog input[name=nom]', name);
    await friend.fill('#object-form-dialog input[name=price]', '42');
    await Promise.all([friend.waitForNavigation(), friend.click('#object-form-dialog [data-object-form-submit]')]);

    const card = friend.locator('.card', { hasText: name });
    check(await card.count() === 1, 'amie : la suggestion apparaît sur la liste');
    check((await card.locator('.card__badge--suggestion').innerText()).includes('Suggestion de Mallory'), 'étiquette « Suggestion de Mallory »');
    check(await card.locator('.suggester__avatar').count() === 1, "étiquette : avatar de l'auteur");
    check(await card.locator('.heart-btn').count() === 0, 'pas de coup de cœur sur une suggestion');
    const id = await card.getAttribute('data-object');
    await friend.waitForTimeout(400);
    await card.screenshot({ path: `${__dirname}/out/suggestion-card.png` });

    // Onglet « Suggestions » : seulement les suggestions.
    await friend.click('[data-filter="suggestion"]');
    const shown = await friend.locator('.card[data-object]:visible').evaluateAll((cards) => cards.map((c) => c.dataset.suggestion));
    check(shown.length > 0 && shown.every((s) => s === '1'), 'onglet « Suggestions » : ' + shown.length + ' suggestion(s)');
    await friend.click('[data-filter="all"]');

    // Menu de l'auteur : modifier et supprimer, pas « reçu ».
    await card.locator('[data-card-menu] summary').click();
    const menu = await card.locator('.card-menu__panel').innerText();
    check(menu.includes('Modifier') && menu.includes('Supprimer') && !menu.includes('reçu'), 'auteur : modifier, supprimer, pas « reçu »');
    await card.locator('button[data-open-object-form]').click();
    await friend.fill('#object-form-dialog input[name=nom]', name + ' modifiée');
    await Promise.all([friend.waitForNavigation(), friend.click('#object-form-dialog [data-object-form-submit]')]);
    check(await friend.locator('.card', { hasText: name + ' modifiée' }).count() === 1, 'auteur : suggestion modifiée');

    // Fiche de l'idée : « Suggérée par Mallory ».
    await friend.locator(`#idea-${id} .card__image`).click();
    check((await friend.locator(`#object-${id} .detail__suggestion`).innerText()).includes('Suggérée par Mallory'), 'fiche : « Suggérée par Mallory »');
    await friend.screenshot({ path: `${__dirname}/out/suggestion-detail.png` });
    await friend.keyboard.press('Escape');

    // Les amis peuvent la commenter et la réserver comme une autre idée.
    check((await post(friend, 'addComment.php', { productId: id, content: 'Bonne idée !' })).status === 200, 'amie : commentaire sur la suggestion');

    /* ---- Etienne ne voit rien ---- */
    await owner.goto(`${B}?user=${ETIENNE}`);
    check(await owner.locator(`#idea-${id}`).count() === 0 && !(await owner.locator('[data-grid]').innerText()).includes(name), 'propriétaire : suggestion absente de sa liste');
    check(await owner.locator('[data-filter="suggestion"]').count() === 0, 'propriétaire : pas d\'onglet « Suggestions »');
    check(await owner.locator('.card--suggest').count() === 0, 'propriétaire : pas de bouton « Suggérer »');
    check(!(await owner.locator('[data-notifications]').innerText()).includes(name), 'propriétaire : aucune notification de la suggestion ni du commentaire');
    const more = await owner.evaluate(async (url) => (await fetch(url, { headers: { Accept: 'application/json' } })).text(), `${B}actions/notifications.php?offset=0`);
    check(!more.includes(name), 'propriétaire : rien dans les notifications paginées');

    check((await post(owner, 'addComment.php', { productId: id, content: 'je triche' })).status === 404, 'propriétaire : commentaire refusé');
    check((await post(owner, 'addReaction.php', { object: id, value: 1 })).status !== 200, 'propriétaire : réaction refusée');
    check((await post(owner, 'toggleFavorite.php', { id })).status === 404, 'propriétaire : coup de cœur refusé');
    check((await post(owner, 'toggleReceived.php', { id })).status === 404, 'propriétaire : « reçu » refusé');
    check((await post(owner, 'editObject.php', { object_id: id, nom: 'piraté' })).status === 404, 'propriétaire : modification refusée');
    check((await post(owner, 'deleteObject.php', { id })).status === 404, 'propriétaire : suppression refusée');

    // On ne suggère pas sur sa propre liste.
    check((await post(owner, 'addObject.php', { owner: ETIENNE, suggest: '1', nom: name + ' soi' })).status === 403, 'propriétaire : suggestion sur sa propre liste refusée');

    /* ---- Extension Chrome : amis proposés pour une suggestion ---- */
    const me = await friend.evaluate(async (url) => (await fetch(url, { headers: { Accept: 'application/json' } })).json(), `${B}actions/me.php`);
    check(Array.isArray(me.friends) && me.friends.some((f) => f.code === ETIENNE), 'me.php : Etienne parmi les amis à qui suggérer');
    const meOwner = await owner.evaluate(async (url) => (await fetch(url, { headers: { Accept: 'application/json' } })).json(), `${B}actions/me.php`);
    check(!meOwner.friends.some((f) => f.code === ETIENNE), 'me.php : jamais sa propre liste');

    /* ---- Badge « Souffleur » : recalculé à la connexion suivante (nouvelle session) ---- */
    const friend2 = await session(browser, 'Mallory');
    await friend2.click('.profile__badges');
    check(await friend2.locator('#badges-dialog [data-badge-section="earned"] .trophy-tile.is-earned', { hasText: 'Souffleur' }).count() === 1, 'badge « Souffleur » obtenu par l\'auteur');
    await friend2.context().close();

    /* ---- Nettoyage : Mallory supprime sa suggestion ---- */
    check((await post(friend, 'deleteObject.php', { id })).status === 200, 'auteur : suggestion supprimée');

    check(friend.errors.length === 0 && owner.errors.length === 0, 'aucune erreur JS ' + friend.errors.concat(owner.errors).join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nSuggestions OK');
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
