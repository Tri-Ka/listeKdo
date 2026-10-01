/*
 * Prix, idées reçues, cadeaux à plusieurs, date de l'événement, listes d'enfants (plusieurs parents).
 * Site local, lancé par run.sh (Etienne = propriétaire, Mallory = amie).
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
const ETIENNE = 'e3370a0bb2c2ea49f93b68c0649d57b6';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };
const text = async (locator) => (await locator.innerText()).replace(/\s+/g, ' ').trim();

async function session(browser, name) {
    const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    page.on('dialog', (d) => d.accept());
    await page.goto(B);
    await page.click('.topbar [data-open="login-dialog"]');
    await page.fill('#login-dialog input[name=nom]', name);
    await page.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
    return page;
}

async function addIdea(page, name, price) {
    await page.click('.card--add');
    await page.fill('#object-form-dialog input[name=nom]', name);
    if (price) await page.fill('#object-form-dialog input[name=price]', price);
    await Promise.all([page.waitForNavigation(), page.click('#object-form-dialog [data-object-form-submit]')]);
    const card = page.locator('.card', { hasText: name });
    return [card, await card.getAttribute('data-object')];
}

(async () => {
    const browser = await chromium.launch();
    const owner = await session(browser, 'Etienne');
    const friend = await session(browser, 'Mallory');

    /* ---- Date de l'événement ---- */
    const inTwelveDays = new Date(Date.now() + 12 * 86400000).toISOString().slice(0, 10);
    await owner.goto(`${B}?user=${ETIENNE}`);
    await owner.click('.profile__action');
    await owner.fill('#profile-dialog input[name=event_date]', inTwelveDays);
    await Promise.all([owner.waitForNavigation(), owner.click('#profile-dialog button[type=submit]')]);
    const days = await owner.locator('[data-unit="d"]').innerText();
    const seconds = await owner.locator('[data-unit="s"]').innerText();
    await owner.waitForTimeout(1200);
    check(days === '11' && seconds !== await owner.locator('[data-unit="s"]').innerText(), 'compte à rebours : 11 jours + heures, secondes qui défilent');

    /* ---- Prix ---- */
    const [cheap, cheapId] = await addIdea(owner, 'Test prix petit', '12');
    const [big, bigId] = await addIdea(owner, 'Test prix gros', '45,50');
    check(await text(big.locator('.card__price')) === '45,50 €', 'prix affiché : 45,50 €');
    await owner.click('[data-budget="30"]');
    check(await owner.locator(`#idea-${cheapId}`).isVisible() && await owner.locator(`#idea-${bigId}`).isHidden(), 'budget ≤ 30 € : seule l\'idée à 12 € reste');
    await owner.click('[data-budget=""]');
    await owner.click('[data-sort="desc"]');
    const order = await owner.locator('.card[data-object]:not([hidden])').evaluateAll((cards) => cards.map((c) => c.dataset.price).filter(Boolean).map(Number));
    check(order.every((v, i) => i === 0 || order[i - 1] >= v), 'tri prix ↓ : ' + order.slice(0, 5).join(', '));
    await owner.click('[data-sort=""]');

    /* ---- Cadeau à plusieurs ---- */
    await friend.goto(`${B}?user=${ETIENNE}`);
    await friend.locator(`#idea-${bigId} [data-gift-choice]`).click();
    await friend.click('#gift-choice-dialog [data-gift-group]');
    await friend.fill(`#object-${bigId} input[name=amount]`, '20');
    await friend.click(`#object-${bigId} button[value=join]`);
    await friend.waitForSelector(`#object-${bigId} .group__list li`);
    check((await text(friend.locator(`#object-${bigId} .group__total`))).includes('20 € sur 45,50 €'), 'participation : 20 € sur 45,50 €');
    await friend.keyboard.press('Escape');
    check((await text(friend.locator(`#idea-${bigId} .collection-pill`))).includes('1 participant · 20 € / 45,50 €'), 'vignette : 1 participant · 20 € / 45,50 €');
    await owner.reload();
    check(await owner.locator(`#idea-${bigId} .collection-pill, #object-${bigId} .group`).count() === 0, 'propriétaire : ne voit pas le cadeau à plusieurs');
    await friend.locator(`#idea-${bigId} .collection-pill`).click();
    await friend.click(`#object-${bigId} button[value=leave]`);
    await friend.waitForFunction((id) => !document.querySelector(`#object-${id} .group__list`), bigId);
    check(true, 'se retirer du cadeau à plusieurs');
    await friend.keyboard.press('Escape');

    /* ---- Reçu ---- */
    await owner.locator(`#idea-${cheapId} [data-card-menu] summary`).click();
    await owner.locator(`#idea-${cheapId} .received-btn`).click();
    await owner.waitForSelector(`#idea-${cheapId}[data-received="1"]`, { state: 'attached' });
    check(await owner.locator(`#idea-${cheapId}`).isHidden(), 'reçu : l\'idée quitte « Toutes »');
    await owner.click('[data-filter="received"]');
    check(await owner.locator(`#idea-${cheapId}`).isVisible(), 'onglet « Reçus »');
    await friend.reload();
    check(await friend.locator(`#idea-${cheapId}`).count() === 0, 'ami : idée reçue invisible');
    await owner.locator(`#idea-${cheapId} [data-card-menu] summary`).click();
    await owner.locator(`#idea-${cheapId} .received-btn`).click();
    await owner.waitForSelector(`#idea-${cheapId}[data-received="0"]`, { state: 'attached' });
    check(true, 'remettre dans la liste');

    /* ---- Liste d'enfant, plusieurs parents ---- */
    await owner.click('.user-menu summary');
    await owner.click('[data-open="child-new-dialog"]');
    await owner.fill('#child-new-dialog input[name=nom]', 'Léo test');
    await owner.selectOption('#child-new-dialog select[name=theme]', 'birthday');
    await Promise.all([owner.waitForNavigation(), owner.click('#child-new-dialog button[type=submit]')]);
    check(await text(owner.locator('.profile__name')) === 'Léo test', 'liste d\'enfant créée');
    const childUrl = owner.url().split('#')[0];
    const [childIdea, childIdeaId] = await addIdea(owner, 'Test idée enfant', '');
    check(await childIdea.count() === 1, 'le parent ajoute une idée à la liste de l\'enfant');
    check(await owner.locator('.gift-slot').count() > 0, 'le parent voit aussi les dons (pour coordonner)');
    const actions = await owner.locator(`#idea-${childIdeaId} .card__actions`).boundingBox();
    const cardBox = await owner.locator(`#idea-${childIdeaId}`).boundingBox();
    check(actions.x + actions.width <= cardBox.x + cardBox.width, 'parent : les boutons ne débordent pas de la vignette');

    await friend.goto(childUrl);
    check(await friend.locator(`#idea-${childIdeaId} [data-open-object-form]`).count() === 0, 'amie : pas encore gestionnaire');
    await owner.click('.profile__action');
    await owner.selectOption('#child-dialog select[name=manager_id]', { label: 'Mallory' });
    await Promise.all([owner.waitForNavigation(), owner.click('#child-dialog button[name=manager_add]')]);
    check((await text(owner.locator('#child-dialog .managers__list'))).includes('Mallory'), 'deuxième parent ajouté');
    await friend.reload();
    check(await friend.locator(`#idea-${childIdeaId} [data-card-menu] [data-open-object-form]`).count() === 1, 'le deuxième parent peut modifier la liste (menu « ⋯ »)');
    await friend.click('.user-menu summary');
    check(await friend.locator('.user-menu__panel a', { hasText: 'Liste de Léo test' }).count() === 1, 'menu du 2e parent : « Liste de Léo test »');

    // Suppression de la liste de test.
    await owner.click('.profile__action');
    await Promise.all([owner.waitForNavigation(), owner.click('#child-dialog [form="delete-child-form"]')]);
    check(!owner.url().includes(childUrl.split('user=')[1]), 'liste d\'enfant supprimée');

    // Nettoyage des idées de test.
    await owner.goto(`${B}?user=${ETIENNE}`);
    for (const id of [cheapId, bigId]) {
        await owner.locator(`#idea-${id} [data-open-object-form]`).click();
        await Promise.all([owner.waitForNavigation(), owner.click('#object-form-dialog [data-delete-object]')]);
    }
    await owner.click('.profile__action');
    await owner.fill('#profile-dialog input[name=event_date]', '');
    await Promise.all([owner.waitForNavigation(), owner.click('#profile-dialog button[type=submit]')]);

    check(owner.errors.length + friend.errors.length === 0, 'aucune erreur JS ' + [...owner.errors, ...friend.errors].join(' | '));
    await browser.close();
    console.log(failures ? `\n${failures} échec(s)` : '\nFonctionnalités OK');
    process.exit(failures ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(2); });
