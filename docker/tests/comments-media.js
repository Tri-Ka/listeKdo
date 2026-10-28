/*
 * Photo et GIF en commentaire, date affichée : envoi avec aperçu, GIF gardé tel quel, suppression du fichier.
 * Compte « Test1comm… » supprimé par run-comments-media.sh, même si le test échoue.
 */
const { chromium } = require('playwright-core');
const fs = require('fs');
const B = 'http://localhost:8090/listeKdo/';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };
const GIF = Buffer.from('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==', 'base64');

(async () => {
    const browser = await chromium.launch();
    const page = await (await browser.newContext({ viewport: { width: 1366, height: 900 } })).newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));

    await page.goto(B);
    await page.click('[data-open="signup-dialog"] >> nth=0');
    await page.fill('#signup-dialog input[name=nom]', 'Test1comm' + Date.now());
    await page.fill('#signup-dialog input[name=password]', 'test');
    await page.fill('#signup-dialog input[name=re-password]', 'test');
    await Promise.all([page.waitForNavigation(), page.click('#signup-dialog button[type=submit]')]);
    await page.waitForSelector('#secret-invite-dialog[open]', { timeout: 3000 }).catch(() => {});
    await page.evaluate(() => { document.getElementById('secret-invite-dialog')?.close(); document.getElementById('onboarding-dialog')?.close(); });
    await page.click('.card--add');
    await page.fill('#object-form-dialog input[name=nom]', 'Idée commentaires images');
    await Promise.all([page.waitForNavigation(), page.click('#object-form-dialog [data-object-form-submit]')]);
    await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));

    const id = await page.locator('.grid .card[data-object]').first().getAttribute('data-object');
    const dialog = `#object-${id}`;
    await page.click(`[data-open="object-${id}"].round-btn`);
    await page.waitForSelector(`${dialog}[open]`);

    // Photo : grande image générée, réduite par le navigateur avant l'envoi
    const png = Buffer.from(await page.evaluate(() => {
        const c = document.createElement('canvas'); c.width = 2400; c.height = 1600;
        const x = c.getContext('2d'); const g = x.createLinearGradient(0, 0, 2400, 1600);
        g.addColorStop(0, '#f6a'); g.addColorStop(1, '#6af'); x.fillStyle = g; x.fillRect(0, 0, 2400, 1600);
        return c.toDataURL('image/png').split(',')[1];
    }), 'base64');
    await page.setInputFiles(`${dialog} [data-comment-image]`, { name: 'photo.png', mimeType: 'image/png', buffer: png });
    check(await page.waitForSelector(`${dialog} [data-comment-preview]:not([hidden])`, { timeout: 5000 }).then(() => true, () => false), 'aperçu de la photo');
    await page.click(`${dialog} [data-comment-image-remove]`);
    check(await page.locator(`${dialog} [data-comment-preview]`).isHidden(), 'photo retirée');
    await page.setInputFiles(`${dialog} [data-comment-image]`, { name: 'photo.png', mimeType: 'image/png', buffer: png });
    await page.click(`${dialog} [data-comment-editor]`);
    await page.keyboard.type('Regarde la couleur !');
    await page.click(`${dialog} .comment-form button[type=submit]`);
    await page.waitForSelector(`${dialog} .comment .comment__image img`);
    const photo = await page.locator(`${dialog} .comment__image img`).first().getAttribute('src');
    check(/^uploads\/comments\/[a-z0-9]+\.jpg$/.test(photo), 'photo envoyée et réduite en JPEG : ' + photo);
    check(await page.locator(`${dialog} [data-comment-preview]`).isHidden(), 'formulaire vidé après envoi');
    check(/^Aujourd'hui à \d\d:\d\d$/.test(await page.locator(`${dialog} .comment__date`).first().innerText()), 'date affichée');

    // Collage d'un texte mis en forme : arrive en texte brut
    await page.click(`${dialog} [data-comment-editor]`);
    await page.evaluate((d) => {
        const data = new DataTransfer();
        data.setData('text/html', '<b>gras</b>');
        data.setData('text/plain', 'gras');
        document.querySelector(`${d} [data-comment-editor]`).dispatchEvent(new ClipboardEvent('paste', { clipboardData: data, bubbles: true, cancelable: true }));
    }, dialog);
    check(await page.locator(`${dialog} [data-comment-editor] b`).count() === 0
        && await page.inputValue(`${dialog} textarea[name=content]`) === 'gras', 'texte collé sans mise en forme, recopié dans le champ envoyé');
    await page.evaluate((d) => { const e = document.querySelector(`${d} [data-comment-editor]`); e.textContent = ''; e.dispatchEvent(new InputEvent('input', { bubbles: true })); }, dialog);

    // GIF inséré par le clavier du téléphone (Gboard) dans le champ : devient la pièce jointe, envoyé seul
    await page.evaluate(([d, b64]) => {
        const img = document.createElement('img');
        img.src = 'data:image/gif;base64,' + b64;
        document.querySelector(`${d} [data-comment-editor]`).append(img);
    }, [dialog, GIF.toString('base64')]);
    await page.waitForSelector(`${dialog} [data-comment-preview]:not([hidden])`);
    check(await page.locator(`${dialog} [data-comment-editor] img`).count() === 0, 'GIF du clavier : retiré du texte, mis en pièce jointe');
    await page.press(`${dialog} [data-comment-editor]`, 'Enter');
    await page.waitForFunction((d) => document.querySelectorAll(`${d} .comment__image img`).length === 2, dialog);
    const gif = await page.locator(`${dialog} .comment__image img`).nth(1).getAttribute('src');
    check(/\.gif$/.test(gif), 'GIF envoyé sans texte');
    check(fs.readFileSync('/site/' + gif).equals(GIF), 'GIF gardé tel quel (animation conservée)');
    check(await page.locator(`${dialog} .comment`).nth(1).locator('.comment__text').count() === 0, 'pas de texte vide');

    // Commentaire vide refusé côté navigateur
    await page.click(`${dialog} .comment-form button[type=submit]`);
    await page.waitForTimeout(300);
    check(await page.locator(`${dialog} .comment`).count() === 2, 'commentaire vide ignoré');

    // Après rechargement, les images et dates restent
    await page.reload();
    await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
    await page.click(`[data-open="object-${id}"].round-btn`);
    check(await page.locator(`${dialog} .comment__image img`).count() === 2, 'images visibles après rechargement');
    check(await page.locator(`${dialog} .comment__date`).count() === 2, 'dates visibles après rechargement');
    await page.screenshot({ path: 'out/comments-media.png' });

    // Visionneuse : l'image s'ouvre par-dessus la fiche, Échap ne ferme qu'elle
    await page.click(`${dialog} .comment__image >> nth=0`);
    await page.waitForSelector('#image-viewer[open]');
    check(/uploads\/comments\/.+\.jpg$/.test(await page.locator('#image-viewer img').getAttribute('src')), 'visionneuse : image en grand');
    await page.screenshot({ path: 'out/comments-viewer.png' });
    await page.keyboard.press('Escape');
    await page.waitForTimeout(200);
    check(!(await page.locator('#image-viewer[open]').count()) && await page.locator(`${dialog}[open]`).count() === 1, 'visionneuse fermée, fiche toujours ouverte');

    // Réactions : barre rapide au-dessus du message, puis sélecteur complet (« + », recherche)
    const first = `${dialog} .comment >> nth=0`;
    await page.hover(first);
    await page.click(`${first} >> [data-comment-react]`);
    await page.waitForSelector('.reaction-pop:popover-open');
    const barBox = await page.locator('.reaction-pop__bar').boundingBox();
    const rowBox = await page.locator(`${first} >> .comment__row`).boundingBox();
    check(barBox.y + barBox.height <= rowBox.y + 1 || barBox.y >= rowBox.y + rowBox.height - 1, 'barre placée au-dessus ou en dessous du message');
    await page.click('.reaction-pop__bar [data-emoji="❤️"]');
    await page.waitForSelector(`${first} >> .comment-reactions__summary`);
    check((await page.locator(`${first} >> .comment-reactions__summary`).innerText()).includes('❤️'), 'réaction ❤️ posée');
    check(!(await page.locator('.reaction-pop:popover-open').count()), 'barre refermée après le choix');
    await page.hover(first);
    await page.click(`${first} >> [data-comment-react]`);
    await page.click('.reaction-pop [data-emoji-more]');
    await page.waitForSelector('.reaction-pop [data-emoji-group="flags"]');
    check(await page.locator('.reaction-pop .emoji-grid__item').count() > 1500, 'sélecteur complet chargé');
    await page.click('.reaction-pop [data-emoji-tab="food"]');
    check(await page.locator('.reaction-pop [data-emoji-tab="food"].is-active').count() === 1, 'onglet de catégorie');
    await page.screenshot({ path: 'out/comments-picker.png' });
    await page.fill('.reaction-pop [data-emoji-search]', 'licorne');
    await page.click('.reaction-pop [data-emoji-results] [data-emoji="🦄"]');
    await page.waitForFunction((d) => document.querySelector(`${d} .comment-reactions__summary`)?.textContent.includes('🦄'), dialog);
    check(!(await page.locator(`${first} >> .comment-reactions__summary`).innerText()).includes('❤️'), 'recherche « licorne » : 🦄 remplace la réaction précédente');
    check(await page.locator(`${dialog}[open]`).count() === 1, 'fiche toujours ouverte');

    // Réagir à l'idée depuis sa fiche : la vignette suit
    const compact = `${dialog} [data-reactions-compact]`;
    check(await page.locator(`${compact} .reactions__choices button`).first().isHidden(), 'fiche : réactions discrètes (fermées)');
    await page.click(`${compact} [data-reaction-toggle]`);
    await page.click(`${compact} button[value="1"]`);
    await page.waitForFunction((i) => document.querySelector(`.card [data-reactions-slot="${i}"] .reactions__total`)?.textContent.trim() === '1', id);
    check(await page.locator(`${compact} .reactions__summary--mine img[src$="/1.png"]`).count() === 1, 'réaction à l’idée depuis la fiche');
    await page.click(`${compact} [data-reaction-toggle]`);
    await page.screenshot({ path: 'out/idea-reaction-dialog.png' });
    await page.click(`${compact} button[value="1"]`);
    await page.waitForFunction((i) => !document.querySelector(`.card [data-reactions-slot="${i}"] .reactions__total`), id);
    check(true, 'réaction à l’idée retirée');

    const refused = await page.evaluate(async () => {
        const body = new FormData(); body.set('id', document.querySelector('[data-comment-react]').dataset.commentReact); body.set('emoji', '<b>x</b>');
        const r = await fetch('actions/commentReaction.php', { method: 'POST', body, headers: { Accept: 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content } });
        return r.status;
    });
    check(refused === 400, 'texte non emoji refusé');

    // Mobile
    await page.setViewportSize({ width: 390, height: 844 });
    await page.locator(`${dialog} .comment-form`).scrollIntoViewIfNeeded();
    await page.setInputFiles(`${dialog} [data-comment-image]`, { name: 'photo.png', mimeType: 'image/png', buffer: png });
    await page.screenshot({ path: 'out/comments-media-mobile.png' });
    const overflow = await page.evaluate((d) => document.querySelector(d).scrollWidth > document.querySelector(d).clientWidth, dialog);
    check(!overflow, 'mobile : pas de débordement horizontal');
    await page.click(`${dialog} [data-comment-image-remove]`);

    // Réaction conservée après rechargement (accents et emojis sur 4 octets), puis retirée
    await page.reload();
    await page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
    await page.click(`[data-open="object-${id}"].round-btn`);
    check((await page.locator(`${first} >> .comment-reactions__summary`).innerText()).trim() === '🦄', 'réaction 🦄 intacte après rechargement');
    await page.click(`${first} >> .comment-reactions__summary`);
    check(await page.locator(`${first} >> .comment-reactions__list`).isVisible(), 'liste de qui a réagi');
    await page.screenshot({ path: 'out/comments-reactions.png' });
    await page.click(`${first} >> [data-comment-reaction-remove]`);
    await page.waitForFunction((d) => !document.querySelector(`${d} .comment-reactions__summary`), dialog);
    check(true, 'réaction retirée');

    // Suppression : le fichier disparaît
    await page.click(`${dialog} .comment__delete button >> nth=1`);
    await page.click('#confirm-dialog [data-confirm-ok]');
    await page.waitForFunction((d) => document.querySelectorAll(`${d} .comment`).length === 1, dialog);
    check(!fs.existsSync('/site/' + gif), 'fichier du GIF supprimé avec le commentaire');

    // GIPHY (réponses simulées) : tendances, recherche, GIF joint puis envoyé
    const searches = [];
    await page.route('https://api.giphy.com/**', (route) => {
        const url = new URL(route.request().url());
        searches.push(url.pathname.split('/').pop() + ':' + (url.searchParams.get('q') || ''));
        const gif = (n) => ({ title: 'GIF ' + n, images: {
            fixed_width: { url: `https://media.giphy.com/test/${n}.gif`, size: String(GIF.length) },
            fixed_width_small: { url: `https://media.giphy.com/test/${n}s.gif`, webp: `https://media.giphy.com/test/${n}s.gif` } } });
        route.fulfill({ contentType: 'application/json', headers: { 'access-control-allow-origin': '*' },
            body: JSON.stringify({ data: [gif(1), gif(2), gif(3)], pagination: { total_count: 3 } }) });
    });
    await page.route('https://media.giphy.com/test/**', (route) => route.fulfill({ contentType: 'image/gif', headers: { 'access-control-allow-origin': '*' }, body: GIF }));
    await page.click(`${dialog} [data-gif-open]`);
    await page.waitForSelector('.gif-pop:popover-open .gif-pop__item');
    check(searches[0] === 'trending:', 'GIPHY : tendances à l’ouverture');
    await page.fill('.gif-pop [data-gif-search]', 'chat');
    await page.waitForTimeout(600);
    check(searches.includes('search:chat'), 'GIPHY : recherche « chat »');
    await page.screenshot({ path: 'out/comments-giphy.png' });
    await page.click('.gif-pop .gif-pop__item >> nth=1');
    await page.waitForSelector(`${dialog} [data-comment-preview]:not([hidden])`);
    check(!(await page.locator('.gif-pop:popover-open').count()), 'GIPHY : panneau refermé, GIF joint');
    await page.press(`${dialog} [data-comment-editor]`, 'Enter');
    await page.waitForFunction((d) => [...document.querySelectorAll(`${d} .comment__image img`)].some((img) => /\.gif$/.test(img.getAttribute('src'))), dialog);
    check(true, 'GIPHY : GIF envoyé en commentaire');

    check(errors.length === 0, 'aucune erreur JS' + (errors.length ? ' : ' + errors.join(' | ') : ''));
    await browser.close();
    process.exit(failures ? 1 : 0);
})();
