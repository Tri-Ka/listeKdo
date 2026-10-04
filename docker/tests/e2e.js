/*
 * Test de bout en bout de listeKdo sur l'environnement Docker local.
 * Lancer avec ./run.sh (ne jamais viser le site en production).
 */
const { chromium } = require('playwright-core');
const B = 'http://localhost:8090/listeKdo/';
const OWNER = 'e3370a0bb2c2ea49f93b68c0649d57b6';
let failures = 0;
const check = (ok, label) => { console.log((ok ? 'OK   ' : 'FAIL ') + label); if (!ok) failures++; };

async function newPage(b, w = 1366, h = 900) {
  const ctx = await b.newContext({ viewport: { width: w, height: h } });
  const p = await ctx.newPage();
  p.errors = [];
  p.on('pageerror', e => p.errors.push(e.message));
  p.on('dialog', d => d.accept());
  return p;
}
async function login(p, name) {
  await p.goto(B + `?user=${OWNER}`);
  await p.click('.topbar [data-open="login-dialog"]');
  await p.fill('#login-dialog input[name=nom]', name);
  await p.fill('#login-dialog input[name=password]', 'test');
  await Promise.all([p.waitForNavigation(), p.click('#login-dialog button[type=submit]')]);
}

(async () => {
  const b = await chromium.launch();

  // --- Ami : offrir, réagir, commenter
  const f = await newPage(b);
  await login(f, 'Mallory');
  check(f.url().includes(OWNER), 'connexion : retour sur la liste consultée');
  await f.goto(B + `?user=${OWNER}`);
  check(await f.locator('.gift-slot').first().isVisible(), 'boutons « Je l\'offre » visibles par défaut');
  await f.click('.topbar .pill-switch');
  check(await f.locator('.gift-slot').first().isHidden(), 'interrupteur : infos de don masquées');
  await f.click('.topbar .pill-switch');
  check(await f.locator('.gift-slot').first().isVisible(), 'interrupteur : infos de don réaffichées');
  const card = f.locator('.card:not(.card--add)').filter({ has: f.locator('[data-gift-choice]') }).first();
  const cardId = await card.getAttribute('data-object');
  await card.locator('[data-gift-choice]').click();
  await f.waitForSelector('#gift-choice-dialog[open]');
  check(await f.locator('#gift-choice-dialog [data-gift-group]').isVisible(), '« Je l\'offre » : choix seul / à plusieurs');
  await f.screenshot({ path: `${__dirname}/out/e2e-gift-choice.png` });
  await f.click('#gift-choice-dialog [data-gift-alone]');
  await f.waitForSelector(`#idea-${cardId}.card--gifted`);
  check(await f.locator(`#idea-${cardId} .gifted-pill--mine`).count() === 1, 'offrir un Kdo (AJAX)');
  await f.screenshot({ path: `${__dirname}/out/e2e-friend-gifted.png` });
  await f.click('[data-filter="gifted"]');
  const shown = await f.locator('.card[data-object]:visible').evaluateAll(cs => cs.map(c => c.dataset.gifted));
  check(shown.length > 0 && shown.every(g => g === '1'), 'onglet « Déjà offertes » : ' + shown.length + ' idée(s)');
  await f.click('[data-filter="all"]');
  // Confirmation dans une fenêtre du site (plus de confirm() du navigateur) : « Annuler » ne change rien.
  await f.locator(`#idea-${cardId} .gifted-pill--mine`).click();
  check(await f.locator('#confirm-dialog[open] [data-confirm-title]').innerText() === "Vous ne l'offrez plus ?", 'confirmation dans une fenêtre du site');
  await f.click('#confirm-dialog [data-close]');
  await f.waitForTimeout(300);
  check(await f.locator(`#idea-${cardId}.card--gifted`).count() === 1, 'confirmation annulée : toujours offert');
  await f.locator(`#idea-${cardId} .gifted-pill--mine`).click();
  await f.click('#confirm-dialog [data-confirm-ok]');
  await f.waitForSelector(`#idea-${cardId}:not(.card--gifted)`);
  check(true, 'annuler le don (AJAX)');

  for (const value of ['4', '3']) {
    await f.locator(`#idea-${cardId} [data-reaction-toggle]`).click();
    await f.locator(`#idea-${cardId} .reactions__choices button[value="${value}"]`).click();
    await f.waitForSelector(`#idea-${cardId} .reactions__choices button[value="${value}"][aria-pressed="true"]`, { state: "attached" });
  }
  check(await f.locator(`#idea-${cardId} .reactions .reactions__summary`).count() === 1, 'un seul résumé de réactions (pas de doublon)');
  check(true, 'réagir (HaHa)');
  await f.screenshot({ path: `${__dirname}/out/e2e-friend-reaction.png` });

  await f.locator(`#idea-${cardId} .card__image`).click();
  const dlg = f.locator(`#object-${cardId}`);
  check(await dlg.isVisible(), 'ouvrir la fiche (dialog)');
  check(await dlg.locator(`[data-reactors-slot] img[src$="3.png"]`).count() >= 1, 'réaction visible dans la fiche');
  const before = await dlg.locator('.comment').count();
  await dlg.locator('textarea[name=content]').fill("Test d'apostrophe <b>pas gras</b> & \"guillemets\"");
  await dlg.locator('.comment-form button[type=submit]').click();
  await f.waitForFunction(([id, n]) => document.querySelectorAll(`#object-${id} .comment`).length === n + 1, [cardId, before]);
  const txt = await dlg.locator('.comment').last().locator('.comment__text').innerText();
  check(txt === "Test d'apostrophe <b>pas gras</b> & \"guillemets\"", 'commentaire ajouté et échappé : ' + txt);
  await f.screenshot({ path: `${__dirname}/out/e2e-friend-dialog.png` });
  await dlg.locator('.comment').last().locator('.comment__delete button').click();
  await f.click('#confirm-dialog [data-confirm-ok]');
  await f.waitForFunction(([id, n]) => document.querySelectorAll(`#object-${id} .comment`).length === n, [cardId, before]);
  check(true, 'commentaire supprimé');
  await f.keyboard.press('Escape');

  // infobulles au survol des avatars
  await f.keyboard.press('Escape');
  await f.hover('.friends li:first-child a');
  const tipFriend = await f.locator('.tooltip.is-visible').textContent();
  const friendName = await f.locator('.friends li:first-child img').getAttribute('data-tip');
  check(tipFriend === friendName, 'infobulle sur un ami : ' + tipFriend);
  const tipBox = await f.locator('.tooltip').boundingBox();
  check(tipBox && tipBox.x > 60, 'infobulle à droite de la colonne, non coupée');

  // persistance après rechargement
  await f.reload();
  check(await f.locator('.topbar .pill-switch input').isChecked(), 'interrupteur mémorisé');

  check(await f.locator(`#idea-${cardId} .reactions__summary img[src$="3.png"]`).count() === 1, 'réaction enregistrée en base');
  check(f.errors.length === 0, 'aucune erreur JS (ami) ' + f.errors.join(' | '));

  // --- Propriétaire : ajouter, modifier, supprimer, notifications
  const o = await newPage(b);
  await login(o, 'Etienne');
  await o.goto(B + `?user=${OWNER}`);
  check(await o.locator('.gift-slot').count() === 0, 'propriétaire : ne voit pas qui offre quoi');
  check(await o.locator('[data-notification-badge]').count() === 1, 'badge de notifications');
  await o.click('[data-toggle-notifications][aria-controls]');
  await o.waitForSelector('.drawer.is-open');
  await o.waitForTimeout(400);
  await o.screenshot({ path: `${__dirname}/out/e2e-owner-notifs.png` });
  check(await o.locator('[data-notification-badge]').count() === 1, 'ouvrir le panneau ne marque plus tout comme lu');

  // Basculer une notification lue / non lue.
  const unreadBefore = Number(await o.locator('[data-unread-count]').innerText());
  const firstNew = o.locator('.notification--new').first();
  const notifId = await firstNew.getAttribute('data-notification');
  await firstNew.hover();
  await firstNew.locator('[data-notification-toggle]').click();
  await o.waitForSelector(`[data-notification="${notifId}"]:not(.notification--new)`);
  check(Number(await o.locator('[data-unread-count]').innerText()) === unreadBefore - 1, 'marquer une notification comme lue');
  await o.locator(`[data-notification="${notifId}"] [data-notification-toggle]`).click();
  await o.waitForSelector(`[data-notification="${notifId}"].notification--new`);
  check(Number(await o.locator('[data-unread-count]').innerText()) === unreadBefore, 'la remarquer comme non lue');

  // Onglet « Non lues ».
  await o.click('[data-notification-filter="unread"]');
  await o.waitForTimeout(500);
  const unreadShown = await o.locator('.notification').count();
  check(unreadShown > 0 && unreadShown === await o.locator('.notification--new').count(), 'onglet « Non lues » : ' + unreadShown);
  await o.click('[data-notification-filter="all"]');
  await o.waitForTimeout(500);

  // « Tout marquer comme lu ».
  await o.click('[data-mark-all-read]');
  await o.waitForSelector('[data-notification-badge]', { state: 'detached' });
  check(await o.locator('.notification--new').count() === 0, 'tout marquer comme lu');
  const firstPage = await o.locator('.notification').count();
  check(firstPage <= 10, 'notifications : ' + firstPage + ' au départ (10 max)');
  if (await o.locator('[data-more-notifications]').count()) {
    await o.click('[data-more-notifications]');
    await o.waitForFunction((n) => document.querySelectorAll('.notification').length > n, firstPage);
    check(true, '« Voir plus » : ' + (await o.locator('.notification').count()) + ' notifications');
  }
  await o.keyboard.press('Escape');

  const favCard = o.locator('.card[data-object]:not([hidden])').first();
  const favBefore = await favCard.getAttribute('data-favorite');
  await favCard.locator('.heart-btn').click();
  await o.waitForFunction(([s, v]) => document.querySelector(s).dataset.favorite !== v, [`#${await favCard.getAttribute('id')}`, favBefore]);
  await o.reload();
  check(await o.locator(`#${await favCard.getAttribute('id')}`).getAttribute('data-favorite') !== favBefore, 'coup de cœur enregistré');
  await o.locator(`#${await favCard.getAttribute('id')} .heart-btn`).click();
  await o.waitForTimeout(500);

  await o.click('.card--add');
  await o.fill('#object-form-dialog input[name=nom]', 'Idée de test e2e');
  await o.fill('#object-form-dialog textarea[name=description]', 'Ligne 1\nLigne 2 avec <script>alert(1)</script>');
  await o.fill('#object-form-dialog input[name=link]', 'javascript:alert(1)');
  await o.setInputFiles('#object-form-dialog input[name=file]', `${__dirname}/fixtures/photo.jpg`);
  await o.screenshot({ path: `${__dirname}/out/e2e-owner-form.png` });
  await Promise.all([o.waitForNavigation(), o.click('#object-form-dialog [data-object-form-submit]')]);
  const newCard = o.locator('.card', { hasText: 'Idée de test e2e' });
  check(await newCard.count() === 1, 'idée ajoutée avec photo');
  const newId = await newCard.getAttribute('data-object');
  const imgSrc = await newCard.locator('.card__image img').getAttribute('src');
  check(/^uploads\/img\/[0-9a-f]{24}\.jpg$/.test(imgSrc), 'photo renommée et convertie : ' + imgSrc);
  await newCard.scrollIntoViewIfNeeded();
  const dims = await newCard.locator('.card__image img').evaluate(async i => { await i.decode(); return [i.naturalWidth, i.naturalHeight]; });
  check(dims && Math.max(...dims) <= 1600, 'photo redimensionnée : ' + dims);
  check(await newCard.locator('a[href^="javascript"]').count() === 0, 'lien javascript: refusé');
  check(await o.locator('.toast--success').count() === 1, 'message de succès affiché');

  await newCard.locator('[data-card-menu] summary').click();
  await newCard.locator('[data-open-object-form]').click();
  check(await o.inputValue('#object-form-dialog input[name=nom]') === 'Idée de test e2e', 'formulaire pré-rempli');
  await o.fill('#object-form-dialog input[name=nom]', 'Idée modifiée e2e');
  await Promise.all([o.waitForNavigation(), o.click('#object-form-dialog [data-object-form-submit]')]);
  const edited = o.locator(`#idea-${newId}`);
  check((await edited.locator('.card__title').innerText()).toLowerCase() === 'idée modifiée e2e', 'idée modifiée');
  check(await edited.locator('.card__image img').getAttribute('src') === imgSrc, 'image conservée après modification');

  await edited.locator('[data-card-menu] summary').click();
  await edited.locator('[data-open-object-form]').click();
  await o.click('#object-form-dialog [data-delete-object]');
  const totalBefore = Number(await o.locator('[data-count-total]').innerText());
  await o.click('#confirm-dialog [data-confirm-ok]');
  await o.waitForSelector('.trash-fly');
  check(true, 'suppression : la corbeille apparaît');
  await o.waitForSelector(`#idea-${newId}`, { state: 'detached' });
  await o.waitForSelector('.trash-fly', { state: 'detached' });
  check(Number(await o.locator('[data-count-total]').innerText()) === totalBefore - 1, 'suppression sans rechargement, compteur mis à jour');
  await o.reload();
  check(await o.locator(`#idea-${newId}`).count() === 0, 'idée supprimée');

  await o.click('.topbar__settings');
  await o.click('#list-settings-dialog .theme-field__option--noel');
  await Promise.all([o.waitForNavigation(), o.click('#list-settings-dialog .modal__footer button[type=submit]')]);
  await o.waitForLoadState('networkidle');
  check(await o.locator('body').getAttribute('data-theme') === 'noel', 'changement de thème');
  await o.click('.topbar__settings');
  await o.click('#list-settings-dialog .theme-field__option--birthday');
  await Promise.all([o.waitForNavigation(), o.click('#list-settings-dialog .modal__footer button[type=submit]')]);
  await o.waitForLoadState('networkidle');
  check(o.errors.length === 0, 'aucune erreur JS (propriétaire) ' + o.errors.join(' | '));

  // --- Sécurité
  const v = await newPage(b);
  const r1 = await v.request.post(B + 'actions/objectGifted.php', { form: { id: cardId }, headers: { Accept: 'application/json' } });
  check(r1.status() === 419, 'action sans jeton CSRF refusée (' + r1.status() + ')');
  await v.context().addCookies([{ name: 'listeKdoUserCode', value: OWNER, url: B }]);
  await v.goto(B + `?user=${OWNER}`);
  check(await v.locator('.user-menu').count() === 0 && await v.locator('.topbar [data-open="login-dialog"]').count() === 1, 'ancien cookie (code public) ne connecte plus');
  const r2 = await v.request.get(B + `?user=${OWNER}'%20OR%201=1--%20`);
  check((await r2.text()).includes("n'existe pas"), 'injection SQL dans ?user= sans effet');

  // --- Inscription
  const s = await newPage(b, 390, 844);
  await s.goto(B);
  await s.screenshot({ path: `${__dirname}/out/e2e-landing-mob.png` });
  await s.click('.home-hero [data-open="signup-dialog"]');
  const uname = 'Test' + Date.now();
  await s.fill('#signup-dialog input[name=nom]', uname);
  await s.setInputFiles('#signup-dialog input[name=pictureFile]', `${__dirname}/fixtures/photo.jpg`);
  await s.fill('#signup-dialog input[name=password]', 'secret1');
  await s.fill('#signup-dialog input[name="re-password"]', 'secret1');
  await s.click('#signup-dialog .theme-field__option--naissance');
  await s.screenshot({ path: `${__dirname}/out/e2e-signup-mob.png` });
  await Promise.all([s.waitForNavigation(), s.click('#signup-dialog button[type=submit]')]);
  check(await s.locator('.user-menu__avatar').getAttribute('data-tip') === uname, 'inscription + connexion');
  check(await s.locator('body').getAttribute('data-theme') === 'naissance', 'inscription : type de liste choisi');
  const av = await s.locator('.profile__avatar > img').evaluate(i => [i.getAttribute('src'), i.naturalWidth]);
  check(/^uploads\/\d+\/[0-9a-f]{24}\.jpg$/.test(av[0]) && av[1] <= 600, 'avatar envoyé et réduit : ' + av);
  await s.screenshot({ path: `${__dirname}/out/e2e-newuser-mob.png` });
  // Nouveau compte sans question secrète : la fenêtre d'invitation s'ouvre, on la ferme (« Plus tard »).
  await s.waitForSelector('#secret-invite-dialog[open]');
  await s.click('#secret-invite-dialog .btn--ghost[data-close]');
  await s.click('.user-menu summary');
  await Promise.all([s.waitForNavigation(), s.click('.user-menu__panel form button')]);
  check(await s.locator('.topbar [data-open="login-dialog"]').count() === 1, 'déconnexion');
  await s.click('.topbar [data-open="login-dialog"]');
  await s.fill('#login-dialog input[name=nom]', uname);
  await s.fill('#login-dialog input[name=password]', 'mauvais');
  await Promise.all([s.waitForNavigation(), s.click('#login-dialog button[type=submit]')]);
  check(await s.locator('.toast--error').count() === 1, 'mauvais mot de passe refusé');
  check(s.errors.length === 0, 'aucune erreur JS (inscription) ' + s.errors.join(' | '));

  await b.close();
  console.log(failures ? `\n${failures} échec(s)` : '\nTout est OK');
  process.exit(failures ? 1 : 0);
})().catch(e => { console.error(e); process.exit(2); });
