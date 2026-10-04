const { chromium } = require('playwright-core');
const crypto = require('crypto');
const B = 'http://localhost:8090/listeKdo/';
const OUT = '/out/';
const PRODUCT = 'http://localhost:8090/docker/guide/product.html';
const shot = async (target, name, opts = {}) => {
  try { await target.screenshot({ path: OUT + name + '.jpg', type: 'jpeg', quality: 78, animations: 'disabled', ...opts }); console.log('ok', name); }
  catch (e) { console.log('FAIL', name, e.message.split('\n')[0]); }
};
async function session(browser, name, w = 1280, h = 860) {
  const ctx = await browser.newContext({ viewport: { width: w, height: h }, deviceScaleFactor: 2, locale: 'fr-FR' });
  await ctx.addInitScript(() => document.addEventListener('DOMContentLoaded', () => { const st = document.createElement('style'); st.textContent = 'dialog::backdrop{background:#fff!important;backdrop-filter:none!important}'; document.head.appendChild(st); }));
  const page = await ctx.newPage();
  page.on('pageerror', (e) => console.log('pageerror', name, e.message));
  await page.goto(B);
  await page.click('.topbar [data-open="login-dialog"]');
  await page.fill('#login-dialog input[name=nom]', name);
  await page.fill('#login-dialog input[name=password]', 'test');
  await Promise.all([page.waitForNavigation(), page.click('#login-dialog button[type=submit]')]);
  return page;
}
const closeAll = (p) => p.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
const dlg = (p, id) => p.locator(`#${id} .modal__panel, #${id} > *`).first();
const step = async (label, fn) => { try { await fn(); } catch (e) { console.log('FAIL', label, e.message.split('\n')[0]); } };

(async () => {
  const browser = await chromium.launch({ channel: 'chromium', args: ['--lang=fr-FR'] });
  /* ---- Léa, propriétaire ---- */
  const lea = await session(browser, 'Léa', 1280, 1250);
  const still = (p) => p.addStyleTag({ content: '*, *::before, *::after { animation: none !important; transition: none !important; }' });
  const top = { clip: { x: 0, y: 0, width: 1280, height: 860 } };
  await lea.goto(B + '?user=demo-lea');
  await lea.waitForTimeout(800);
  await closeAll(lea);
  await still(lea); await shot(lea, 'liste', top);
  await step('ajout', async () => {
    await lea.route('**/actions/fetch_metadata.php', (r) => r.fulfill({ contentType: 'application/json', body: JSON.stringify({ success: true, title: 'Plante monstera en pot', description: "Une grande plante d'intérieur, facile à entretenir.", image: 'https://boutique.example/monstera.png', images: ['https://boutique.example/monstera.png'], price: '39.90' }) }));
    await lea.route('https://boutique.example/monstera.png', (r) => r.fulfill({ path: '/w/out/demo-plante.png', contentType: 'image/png' }));
    await lea.click('.card--add');
    await lea.fill('#object-form-dialog input[name=link]', 'https://boutique.example/monstera');
    await lea.waitForFunction(() => document.querySelector('#object-form-dialog input[name=nom]').value !== '', null, { timeout: 8000 });
    await lea.waitForTimeout(800);
    await lea.evaluate(() => document.activeElement.blur());
    await shot(lea.locator('#object-form-dialog'), 'ajout');
    await closeAll(lea);
  });
  await step('notifications', async () => {
    await lea.click('.topbar [data-toggle-notifications]');
    await lea.waitForTimeout(1200);
    const nb = await lea.locator('#notifications').boundingBox(); const last = await lea.locator('#notifications li.notification').last().boundingBox(); await shot(lea, 'notifications', { clip: { x: nb.x, y: nb.y, width: nb.width, height: Math.min(nb.height, last ? last.y + last.height - nb.y + 16 : nb.height) } });
    await lea.click('#notifications [data-toggle-notifications]');
  });
  await step('partage', async () => { await lea.click('.topbar [data-open="share-dialog"]'); await lea.waitForTimeout(800); await shot(lea.locator('#share-dialog'), 'partage'); await closeAll(lea); });
  await step('parametres', async () => { await lea.click('.topbar [data-open="list-settings-dialog"]'); await lea.waitForTimeout(400); await shot(lea.locator('#list-settings-dialog'), 'parametres'); await closeAll(lea); });
  await step('amis', async () => { await lea.locator('[data-open="friends-dialog"]').first().click(); await lea.waitForTimeout(400); await shot(lea.locator('#friends-dialog'), 'amis'); await closeAll(lea); });
  await step('badges', async () => { await lea.locator('[data-open="badges-dialog"]').first().click(); await lea.waitForTimeout(400); await shot(lea.locator('#badges-dialog'), 'badges'); await closeAll(lea); });
  await step('boutique', async () => { await lea.locator('[data-open="shop-dialog"]').first().click(); await lea.waitForTimeout(500); await shot(lea.locator('#shop-dialog'), 'boutique'); await closeAll(lea); });
  await step('secondaire', async () => { await lea.goto(B + '?user=demo-jules'); await lea.waitForTimeout(800); await closeAll(lea); await still(lea); await shot(lea, 'secondaire', top); });

  /* ---- Hugo, ami ---- */
  const hugo = await session(browser, 'Hugo');
  await hugo.goto(B + '?user=demo-lea');
  await hugo.waitForTimeout(800);
  await closeAll(hugo);
  await step('cartes', async () => {
    await hugo.addStyleTag({ content: '.topbar, .friends { visibility: hidden !important; }' });
    await hugo.evaluate(() => { const g = document.querySelector('[data-grid]'); window.scrollTo(0, g.getBoundingClientRect().top + window.scrollY - 24); });
    await hugo.waitForTimeout(500);
    await shot(hugo, 'cartes');
    await hugo.addStyleTag({ content: '.topbar, .friends { visibility: visible !important; }' });
  });
  await step('offrir', async () => {
    await hugo.locator('.card', { hasText: 'Plaid' }).locator('[data-gift-choice]').click();
    await hugo.waitForTimeout(400);
    await shot(hugo.locator('#gift-choice-dialog'), 'offrir');
    await closeAll(hugo);
  });
  await step('cagnotte', async () => {
    const id = await hugo.locator('.card', { hasText: 'Vélo' }).getAttribute('data-object');
    await hugo.locator('.card', { hasText: 'Vélo' }).locator('.card__image').click();
    await hugo.waitForTimeout(500);
    await hugo.locator('#object-' + id + ' .group').scrollIntoViewIfNeeded(); await hugo.waitForTimeout(300); await shot(hugo.locator('#object-' + id + ' .group'), 'cagnotte');
    await closeAll(hugo);
  });
  await step('collection', async () => { await hugo.addStyleTag({ content: '.friends { visibility: hidden !important; }' }); await shot(hugo.locator('.card', { hasText: 'Livres de cuisine' }), 'collection'); });
  await step('suggestion', async () => { await shot(hugo.locator('.card', { hasText: 'Appareil photo' }), 'suggestion'); });
  await step('mes-cadeaux', async () => { await hugo.locator('[data-open="my-gifts-dialog"]').first().evaluate((b) => b.click()); await hugo.waitForTimeout(400); await shot(hugo.locator('#my-gifts-dialog'), 'mes-cadeaux'); await closeAll(hugo); });

  /* ---- Mobile ---- */
  await step('mobile', async () => {
    const m = await session(browser, 'Inès', 390, 844);
    await m.goto(B + '?user=demo-lea'); await m.waitForTimeout(800); await closeAll(m);
    await m.waitForTimeout(300);
    await shot(m, 'mobile');
  });
  await browser.close();

  /* ---- Extension ---- */
  await step('extension', async () => {
    const extId = [...crypto.createHash('sha256').update('/ext').digest('hex').slice(0, 32)].map((c) => String.fromCharCode(97 + parseInt(c, 16))).join('');
    const context = await chromium.launchPersistentContext('', { channel: 'chromium', args: ['--disable-extensions-except=/ext', '--load-extension=/ext'], viewport: { width: 380, height: 760 }, deviceScaleFactor: 2 });
    const site = await context.newPage();
    await site.goto(B);
    await site.click('.topbar [data-open="login-dialog"]');
    await site.fill('#login-dialog input[name=nom]', 'Léa');
    await site.fill('#login-dialog input[name=password]', 'test');
    await Promise.all([site.waitForNavigation(), site.click('#login-dialog button[type=submit]')]);
    const product = await context.newPage(); await product.goto(PRODUCT);
    const popup = await context.newPage();
    await popup.setViewportSize({ width: 380, height: 760 });
    await popup.goto(`chrome-extension://${extId}/popup.html`);
    const tabId = await popup.evaluate(async (url) => (await chrome.tabs.query({})).find((t) => t.url?.startsWith(url))?.id, PRODUCT);
    await popup.evaluate(() => chrome.storage.local.set({ site: 'http://localhost:8090/listeKdo/' }));
    await popup.goto(`chrome-extension://${extId}/popup.html?tabId=${tabId}`);
    await popup.waitForSelector('[data-view="form"]:not([hidden])');
    await popup.fill('input[name=link]', 'https://boutique.example/monstera');
    const box = await popup.locator('[data-submit]').boundingBox();
    await shot(popup, 'extension', { clip: { x: 0, y: 0, width: 380, height: Math.ceil(box.y + box.height + 16) } });
    await context.close();
  });
})();
