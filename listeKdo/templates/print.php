<?php
/*
 * Version imprimable d'une liste (index.php?user=…&page=imprimer) : une lettre « écrite à la main »,
 * au style du type de liste (lettre au Père Noël, anniversaire, naissance, mariage, wishlist).
 * Styles : css/print.css. Le navigateur imprime ou enregistre en PDF (window.print()).
 * Ni dons, ni réservations, ni suggestions ici : la lettre circule, elle ne doit gâcher aucune surprise.
 */
$key = $theme['key'];
$texts = print_texts($key);
$secondary = is_child_list($owner);
$heading = theme_text($theme, 'heading', $owner);
$host = link_domain(KDO_SITE_URL);
$ps = '' !== $host ? 'P.-S. : la liste à jour est sur ' . $host : '';
$hasOwnerPhoto = '' !== (string) $owner['pictureFile'] || '' !== (string) $owner['pictureFileUrl'];

// Textes par défaut de chaque ton (enfantin, normal, sérieux), relus par le script pour changer de ton.
// Le texte affiché sans JS est celui du ton « normal ».
$toneTexts = array();
foreach ($texts['tones'] as $name => $fields) {
    $fields['intro'] = $secondary ? $fields['intro_list'] : $fields['intro'];
    unset($fields['intro_list']);
    $toneTexts[$name] = $fields;
}
$tone = $toneTexts['normal'];
$defaults = array('tones' => $toneTexts, 'common' => array('title' => $heading, 'signame' => $owner['nom'], 'caption' => "C'est moi !", 'ps' => $ps));
$months = array('janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre');
$today = 'Le ' . date('j') . ' ' . $months[date('n') - 1] . ' ' . date('Y');
$next = event_next($owner);
$age = event_age($owner);

// Une police de titre par thème (Google Fonts), la même écriture manuscrite pour le texte.
$titleFonts = array(
    'noel' => 'Mountains+of+Christmas:wght@700',
    'birthday' => 'Pacifico',
    'naissance' => 'Dancing+Script:wght@600;700',
    'mariage' => 'Great+Vibes',
    'wishlist' => 'Gloria+Hallelujah',
);
$titleFont = isset($titleFonts[$key]) ? $titleFonts[$key] : $titleFonts['noel'];
// Habillage acheté dans la boutique (zombie, far west…) : sa police de titre en plus, ses couleurs (print_skin_css()),
// son illustration dans le timbre et son titre en image (décorations : déjà celles de l'habillage via deco()).
$skin = !empty($theme['skin']) ? $theme['skin'] : '';
$skinFonts = print_skin_fonts();
if ('' !== $skin && isset($skinFonts[$skin])) {
    $titleFont .= '&amp;family=' . $skinFonts[$skin][0];
}
$skinArt = '' !== $skin && !empty($theme['left']) ? $theme['left'][0] : '';
// Style enfantin : angles et décalages des idées collées, autocollants (décorations du thème) entre les idées.
$tilts = array(-5, 4, -2, 6, -7, 3, -4, 5);
$shifts = array(0, 6, 2, 9, 1, 5, 8, 3);
$stickers = array_values(array_unique(array_merge($theme['left'], $theme['right'])));
$hasDescriptions = false;
$hasPhotos = false;
foreach ($objects as $object) {
    if ('' !== $object['description']) {
        $hasDescriptions = true;
    }
    if ('' !== (string) $object['image']) {
        $hasPhotos = true;
    }
}
?><!DOCTYPE html>
<html lang="fr" data-theme="<?php echo e($key); ?>" data-tone="normal"<?php echo '' !== $skin ? ' data-skin="' . e($skin) . '"' : ''; ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php // Le titre devient le nom proposé pour le fichier PDF. ?>
    <title><?php echo e($theme['title'] . ' - ' . ('' !== list_title($owner) ? list_title($owner) : $owner['nom'])); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <?php echo render('partials/favicons'); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php // crossorigin : html-to-image peut lire ces polices pour les intégrer au PDF envoyé par e-mail. ?>
    <link rel="stylesheet" crossorigin="anonymous" href="https://fonts.googleapis.com/css2?family=Patrick+Hand&amp;family=Caveat:wght@500;700&amp;family=Schoolbell&amp;family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&amp;family=Lato:wght@400;700&amp;family=<?php echo $titleFont; ?>&amp;display=swap">
    <link rel="stylesheet" href="<?php echo e(asset('css/print.css')); ?>">
    <?php if ('' !== $skin) : ?>
        <style><?php echo print_skin_css($theme); ?></style>
    <?php endif; ?>
</head>
<body>
    <?php // Configurateur : colonne de gauche à l'écran (en haut sur mobile), jamais imprimé. ?>
    <aside class="toolbar" aria-label="Options d'impression">
        <a class="toolbar__back" href="index.php?user=<?php echo e(rawurlencode($owner['code'])); ?>">← Revenir à la liste</a>
        <h2 class="toolbar__title">Imprimer la liste</h2>

        <fieldset class="toolbar__group">
            <legend>Style de la lettre</legend>
            <?php
            $toneHints = array(
                'enfantin' => 'Collage sur cahier d’écolier, découpé et scotché de travers',
                'normal' => 'Lettre manuscrite sur papier ligné',
                'serieux' => 'Document net et carré, vignettes alignées',
            );
            ?>
            <?php foreach (print_tones() as $value => $label) : ?>
                <label class="tone-card tone-card--<?php echo e($value); ?>">
                    <input type="radio" name="tone" value="<?php echo e($value); ?>" data-tone-choice<?php echo 'normal' === $value ? ' checked' : ''; ?>>
                    <span class="tone-card__name"><?php echo e($label); ?></span>
                    <span class="tone-card__hint"><?php echo e($toneHints[$value]); ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <?php if ($hasPhotos || $hasDescriptions) : ?>
            <fieldset class="toolbar__group toolbar__options">
                <legend>Afficher</legend>
                <?php if ($hasPhotos) : ?>
                    <label><input type="checkbox" data-option="photos" checked> Photos des idées</label>
                <?php endif; ?>
                <?php if ($hasDescriptions) : ?>
                    <label><input type="checkbox" data-option="details" checked> Descriptions</label>
                <?php endif; ?>
            </fieldset>
        <?php endif; ?>

        <div class="toolbar__group">
            <p class="toolbar__edit"><strong>✎ Cliquez sur un texte de la lettre</strong> pour le modifier. Vos textes sont gardés dans ce navigateur.</p>
            <button type="button" class="toolbar__reset" data-reset>Rétablir les textes d'origine</button>
        </div>

        <button type="button" class="toolbar__print" data-print><?php echo icon('print'); ?> Imprimer / PDF</button>
        <?php // Envoi du PDF par e-mail (actions/sendLetter.php) : seulement si les e-mails partent (Freebox) et pour un compte connecté. ?>
        <?php if ($ctx['me'] && mail_enabled()) : ?>
            <button type="button" class="toolbar__mail" data-open-mail><?php echo icon('envelope'); ?> Envoyer le PDF par e-mail</button>
        <?php endif; ?>
        <p class="toolbar__tip">Pour un PDF, choisissez « Enregistrer au format PDF » comme imprimante. Si les couleurs n'apparaissent pas, cochez « Graphiques d'arrière-plan ».</p>
    </aside>

    <?php if ($ctx['me'] && mail_enabled()) : ?>
        <dialog class="mail-dialog" id="mail-dialog" aria-labelledby="mail-title">
            <form method="post" action="actions/sendLetter.php" data-mail-form>
                <input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="owner" value="<?php echo e($owner['code']); ?>">
                <h2 id="mail-title">Envoyer la lettre par e-mail</h2>
                <p class="mail-dialog__intro">La lettre part en PDF, telle que vous la voyez (style, textes et photos choisis)<?php echo can_share($ctx['me'], $owner) ? ', avec le lien de la liste en ligne' : ''; ?>.</p>
                <label class="mail-dialog__field">
                    <span>Destinataires <small>(<?php echo 10; ?> au plus, séparés par des virgules)</small></span>
                    <textarea name="recipients" rows="3" required placeholder="mamie@exemple.fr, tonton@exemple.fr" autocomplete="email" data-mail-recipients></textarea>
                </label>
                <label class="mail-dialog__field">
                    <span>Petit mot <small>(facultatif)</small></span>
                    <textarea name="message" rows="3" maxlength="1000" placeholder="Bisous à tous, à très vite !"></textarea>
                </label>
                <p class="mail-dialog__status" data-mail-status role="status" aria-live="polite"></p>
                <div class="mail-dialog__actions">
                    <button type="button" class="mail-dialog__cancel" data-mail-cancel>Annuler</button>
                    <button type="submit" class="toolbar__print" data-mail-send><?php echo icon('envelope'); ?> Envoyer</button>
                </div>
            </form>
        </dialog>
    <?php endif; ?>

    <div class="sheet" data-letter="<?php echo e($owner['code']); ?>" data-defaults="<?php echo e(kdo_json($defaults)); ?>">
        <?php // Cadre du thème : sur papier, position fixe = répété sur chaque page. ?>
        <div class="frame" aria-hidden="true">
            <p class="frame__foot"><span class="page-foot"><?php echo e($host); ?> · <?php echo e($heading); ?></span></p>
        </div>

        <?php // thead / tfoot vides : marges hautes et basses répétées sur chaque page imprimée. ?>
        <table class="layout">
            <thead><tr><td class="layout__spacer"></td></tr></thead>
            <tfoot><tr><td class="layout__spacer layout__spacer--foot"></td></tr></tfoot>
            <tbody><tr><td class="layout__body">

                <header class="letter-head">
                    <?php // Vrai timbre : pays, valeur faciale, illustration du thème. ?>
                    <div class="stamp" aria-hidden="true">
                        <span class="stamp__country"><?php echo e($texts['stamp']); ?></span>
                        <?php if ('' !== $skinArt) : ?>
                            <?php echo deco($theme, $skinArt, 'stamp__art'); ?>
                        <?php else : ?>
                            <span class="stamp__emoji"><?php echo e($theme['emoji']); ?></span>
                        <?php endif; ?>
                        <span class="stamp__value"><?php echo e($texts['value']); ?></span>
                        <?php // Tampon de la poste : nom du lieu et date du jour, à cheval sur le timbre. ?>
                        <span class="postmark"><span class="postmark__place"><?php echo e($texts['stamp']); ?></span><span class="postmark__date"><?php $short = array('JANV', 'FÉVR', 'MARS', 'AVR', 'MAI', 'JUIN', 'JUIL', 'AOÛT', 'SEPT', 'OCT', 'NOV', 'DÉC'); echo e(date('d') . ' ' . $short[date('n') - 1] . ' ' . date('Y')); ?></span></span>
                    </div>
                    <div class="letter-head__deco" aria-hidden="true">
                        <?php foreach (array_slice($theme['left'], 0, 2) as $name) : ?>
                            <?php echo deco($theme, $name, 'letter-deco'); ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ('' !== $skin) : ?>
                        <img class="title-art" src="<?php echo e(asset('img/' . $theme['dir'] . '/title.png')); ?>" alt="" decoding="async">
                    <?php endif; ?>
                    <p class="kicker" data-edit="kicker" data-line><?php echo e($tone['kicker']); ?></p>
                    <h1 class="title" data-edit="title" data-line><?php echo e($heading); ?></h1>
                    <p class="date">
                        <?php echo e($today); ?>
                        <?php if (null !== $next) : ?>
                            <span class="date__event">· <?php echo e($theme['event']); ?> le <?php echo e(date('j', $next) . ' ' . $months[date('n', $next) - 1] . ' ' . date('Y', $next)); ?><?php echo null !== $age ? ' (' . (int) $age . ' ans !)' : ''; ?></span>
                        <?php endif; ?>
                    </p>

                    <p class="opening" data-edit="opening" data-line><?php echo e($tone['opening']); ?></p>
                    <p class="intro" data-edit="intro"><?php echo e($tone['intro']); ?></p>
                </header>

                <?php if (0 === count($objects)) : ?>
                    <p class="empty">La liste est encore toute vide… mais ça ne saurait tarder !</p>
                <?php else : ?>
                    <ol class="wishes">
                        <?php foreach ($objects as $i => $object) : ?>
                            <?php
                            $domain = link_domain($object['link']);
                            // Style enfantin : chaque idée est collée « n'importe comment », mais toujours de la même façon
                            // (angle, décalage, forme du découpage tirés de sa position), pour que le PDF ressemble à l'écran.
                            $tilt = $tilts[$i % count($tilts)];
                            $shift = $shifts[$i % count($shifts)];
                            $cut = $i % 4 + 1;
                            ?>
                            <li class="wish wish--<?php echo 0 === $i % 2 ? 'even' : 'odd'; ?><?php echo $object['favorite'] ? ' wish--favorite' : ''; ?><?php echo '' !== (string) $object['image'] ? ' wish--photo' : ''; ?>" style="--tilt: <?php echo $tilt; ?>deg; --shift: <?php echo $shift; ?>mm;" data-cut="<?php echo $cut; ?>">
                                <?php if (0 < count($stickers) && 1 === $i % 3) : ?>
                                    <?php echo deco($theme, $stickers[(int) floor($i / 3) % count($stickers)], 'sticker sticker--' . (0 === (int) floor($i / 3) % 2 ? 'left' : 'right')); ?>
                                <?php endif; ?>
                                <div class="wish__body">
                                    <p class="wish__name">
                                        <?php // Numéro en compteur CSS : il se recalcule quand des idées sont masquées. ?>
                                        <span class="wish__num" aria-hidden="true"></span>
                                        <?php echo e($object['nom']); ?>
                                        <?php if ($object['favorite']) : ?>
                                            <span class="wish__heart" title="Coup de cœur">♥ coup de cœur</span>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (null !== $object['price'] || '' !== $domain) : ?>
                                        <p class="wish__meta">
                                            <?php if (null !== $object['price']) : ?>
                                                <span class="wish__price"><?php echo e(format_price($object['price'])); ?></span>
                                            <?php endif; ?>
                                            <?php if ('' !== $domain) : ?>
                                                <span class="wish__shop">→ chez <a href="<?php echo e($object['link']); ?>"><?php echo e($domain); ?></a></span>
                                            <?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ('' !== $object['description']) : ?>
                                        <p class="wish__details"><?php echo nl2br(e(excerpt(plain_text($object['description']), 280))); ?></p>
                                    <?php endif; ?>
                                    <?php if (0 < count($object['items'])) : ?>
                                        <ul class="wish__items">
                                            <?php foreach ($object['items'] as $item) : ?>
                                                <li><?php echo e(legacy_text($item['nom'])); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                                <?php if ('' !== (string) $object['image']) : ?>
                                    <?php // __cut : forme découpée aux ciseaux (style enfantin), l'ombre reste sur la figure. ?>
                                    <figure class="polaroid">
                                        <?php // data-proxy : photo hébergée ailleurs, recopiée par notre serveur pour le PDF (actions/printImage.php). ?>
                                        <span class="polaroid__cut"><img src="<?php echo e($object['image']); ?>"<?php echo '' === (string) $object['file'] ? ' data-proxy="actions/printImage.php?id=' . (int) $object['id'] . '"' : ''; ?> alt="" decoding="async" onerror="var w=this.parentNode.parentNode.parentNode;w.className=w.className.replace(' wish--photo','');this.parentNode.parentNode.style.display='none'"></span>
                                    </figure>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <footer class="letter-foot<?php echo $hasOwnerPhoto ? ' letter-foot--photo' : ''; ?>">
                    <p class="closing" data-edit="closing"><?php echo e($tone['closing']); ?></p>
                    <p class="signature" data-edit="signature" data-line><?php echo e($tone['signature']); ?></p>
                    <p class="signature__name" data-edit="signame" data-line><?php echo e($owner['nom']); ?></p>
                    <?php if ($hasOwnerPhoto) : ?>
                        <?php // Photo de profil du propriétaire, en polaroïd à côté de la signature. ?>
                        <figure class="polaroid polaroid--owner" data-owner-photo>
                            <span class="polaroid__cut"><img src="<?php echo e(avatar_url($owner)); ?>"<?php echo '' === (string) $owner['pictureFile'] ? ' data-proxy="actions/printImage.php?owner=' . e(rawurlencode($owner['code'])) . '"' : ''; ?> alt="" decoding="async" onerror="this.parentNode.parentNode.style.display='none'"></span>
                            <figcaption data-edit="caption" data-line>C'est moi !</figcaption>
                        </figure>
                    <?php endif; ?>
                    <?php echo deco($theme, $theme['footer'], 'letter-foot__deco'); ?>
                    <p class="ps" data-edit="ps"><?php echo e($ps); ?></p>
                </footer>

            </td></tr></tbody>
        </table>
    </div>

    <script>
    // Options d'affichage (classes de <body>) et impression. Sans dépendance : la page s'imprime aussi sans JS.
    (function () {
        var boxes = document.querySelectorAll('[data-option]');
        function apply() {
            for (var i = 0; i < boxes.length; i++) {
                var name = boxes[i].getAttribute('data-option');
                document.body.classList.toggle('no-' + name, !boxes[i].checked);
            }
        }
        for (var i = 0; i < boxes.length; i++) {
            boxes[i].addEventListener('change', apply);
        }
        apply();
        document.querySelector('[data-print]').addEventListener('click', function () {
            window.print();
        });

        // Style de la lettre et textes modifiables. Les choix sont gardés dans ce navigateur (localStorage),
        // par liste ; les textes des formules dépendent du style, le titre, la signature et le P.-S. non.
        var sheet = document.querySelector('[data-letter]');
        var defaults = JSON.parse(sheet.getAttribute('data-defaults'));
        var prefix = 'kdo-print:' + sheet.getAttribute('data-letter') + ':';
        var fields = document.querySelectorAll('[data-edit]');
        var choices = document.querySelectorAll('[data-tone-choice]');
        var tone = 'normal';

        function load(name) {
            try { return window.localStorage.getItem(prefix + name); } catch (e) { return null; }
        }
        function save(name, value) {
            try {
                if (null === value) { window.localStorage.removeItem(prefix + name); } else { window.localStorage.setItem(prefix + name, value); }
            } catch (e) {}
        }
        function storageName(field) {
            return defaults.common.hasOwnProperty(field) ? field : tone + ':' + field;
        }
        function original(field) {
            return defaults.common.hasOwnProperty(field) ? defaults.common[field] : defaults.tones[tone][field];
        }
        function fill() {
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i].getAttribute('data-edit');
                var saved = load(storageName(field));
                fields[i].textContent = null !== saved ? saved : original(field);
            }
        }
        function setTone(value) {
            tone = defaults.tones[value] ? value : 'normal';
            document.documentElement.setAttribute('data-tone', tone);
            for (var i = 0; i < choices.length; i++) {
                choices[i].checked = choices[i].value === tone;
            }
            fill();
        }

        for (var j = 0; j < fields.length; j++) {
            fields[j].setAttribute('contenteditable', 'true');
            fields[j].setAttribute('spellcheck', 'true');
            fields[j].addEventListener('input', function () {
                var field = this.getAttribute('data-edit');
                var text = this.innerText.replace(/\s+$/, '');
                // Texte identique à l'original (ou vide) : rien de gardé, on suivra les changements de style.
                save(storageName(field), text === original(field) ? null : text);
            });
            // Collage en texte brut ; Entrée ne crée pas de ligne dans les textes d'une ligne.
            fields[j].addEventListener('paste', function (event) {
                event.preventDefault();
                var text = (event.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, this.hasAttribute('data-line') ? text.replace(/\s*\n\s*/g, ' ') : text);
            });
            fields[j].addEventListener('keydown', function (event) {
                if ('Enter' === event.key && this.hasAttribute('data-line')) {
                    event.preventDefault();
                    this.blur();
                }
            });
        }
        for (var k = 0; k < choices.length; k++) {
            choices[k].addEventListener('change', function () {
                save('tone', this.value);
                setTone(this.value);
            });
        }
        document.querySelector('[data-reset]').addEventListener('click', function () {
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i].getAttribute('data-edit');
                save(field, null);
                for (var name in defaults.tones) {
                    if (defaults.tones.hasOwnProperty(name)) {
                        save(name + ':' + field, null);
                    }
                }
            }
            fill();
        });
        setTone(load('tone') || 'normal');

        initMail();
    })();

    /*
     * Envoi du PDF par e-mail. Le navigateur fabrique le PDF lui-même, page A4 par page A4 :
     *  1. les idées sont réparties sur des pages (jamais coupées), avec le cadre et le bas de page sur chacune ;
     *  2. chaque page devient une image (html-to-image, rendu du navigateur : polices, découpages, ombres) ;
     *  3. jsPDF assemble les images en PDF, envoyé à actions/sendLetter.php avec les adresses.
     * Les photos hébergées ailleurs passent par notre serveur (data-proxy), sinon le navigateur refuse de les copier.
     */
    function initMail() {
        var dialog = document.getElementById('mail-dialog');
        var opener = document.querySelector('[data-open-mail]');
        if (!dialog || !opener) {
            return;
        }
        var form = dialog.querySelector('[data-mail-form]');
        var status = dialog.querySelector('[data-mail-status]');
        var send = dialog.querySelector('[data-mail-send]');
        var libraries = null;
        var busy = false;

        function setStatus(message, kind) {
            status.textContent = message;
            status.className = 'mail-dialog__status' + (kind ? ' is-' + kind : '');
        }
        function loadScript(src) {
            return new Promise(function (resolve, reject) {
                var script = document.createElement('script');
                script.src = src;
                script.onload = resolve;
                script.onerror = function () { reject(new Error('script')); };
                document.head.appendChild(script);
            });
        }
        function loadLibraries() {
            if (!libraries) {
                libraries = Promise.all([
                    loadScript('https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.js'),
                    loadScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js'),
                ]);
            }
            return libraries;
        }
        function loaded(img) {
            return new Promise(function (resolve) {
                if (img.complete) {
                    resolve(img.naturalWidth > 0);
                    return;
                }
                img.addEventListener('load', function () { resolve(true); }, { once: true });
                img.addEventListener('error', function () { resolve(false); }, { once: true });
            });
        }

        // Photos venant d'autres sites : remplacées par leur copie sur notre serveur ; introuvables, elles sont retirées du PDF.
        function prepareImages() {
            var images = document.querySelectorAll('.sheet:not(.pdf-page) img[data-proxy]');
            return Promise.all(Array.prototype.map.call(images, function (img) {
                if (img.getAttribute('data-proxied')) {
                    return Promise.resolve();
                }
                var test = new Image();
                test.src = img.getAttribute('data-proxy');
                return loaded(test).then(function (ok) {
                    img.setAttribute('data-proxied', ok ? '1' : 'skip');
                    if (ok) {
                        img.removeAttribute('onerror');
                        img.src = test.src;
                        return loaded(img);
                    }
                });
            })).then(function () {
                var all = document.querySelectorAll('.sheet:not(.pdf-page) img');
                return Promise.all(Array.prototype.map.call(all, loaded));
            });
        }

        // Pages A4 hors de l'écran, remplies idée par idée.
        function buildPages() {
            var source = document.querySelector('.layout__body');
            var style = getComputedStyle(source);
            var top = document.querySelector('.layout__spacer').offsetHeight;
            var bottom = document.querySelector('.layout__spacer--foot').offsetHeight;
            var holder = document.createElement('div');
            holder.className = 'pdf-pages';
            document.body.appendChild(holder);
            var pages = [];
            var body;
            var list = null;
            var numbered = 0;

            function clean(node) {
                var copy = node.cloneNode(true);
                var editable = copy.querySelectorAll('[contenteditable]');
                for (var i = 0; i < editable.length; i++) {
                    editable[i].removeAttribute('contenteditable');
                }
                var skipped = copy.querySelectorAll('img[data-proxied="skip"]');
                for (var j = 0; j < skipped.length; j++) {
                    skipped[j].closest('figure').style.display = 'none';
                }
                if (copy.hasAttribute && copy.hasAttribute('contenteditable')) {
                    copy.removeAttribute('contenteditable');
                }
                return copy;
            }
            function newPage() {
                var page = document.createElement('div');
                page.className = 'sheet pdf-page';
                page.appendChild(document.querySelector('.sheet:not(.pdf-page) .frame').cloneNode(true));
                body = document.createElement('div');
                body.className = 'layout__body pdf-page__body';
                // !important : .layout__body fixe déjà ses marges en !important (css/print.css).
                body.style.setProperty('padding', top + 'px ' + style.paddingRight + ' ' + bottom + 'px ' + style.paddingLeft, 'important');
                page.appendChild(body);
                holder.appendChild(page);
                pages.push(page);
                list = null;
            }
            function overflows() {
                return body.scrollHeight > body.clientHeight + 1;
            }
            function startList() {
                list = document.querySelector('.sheet:not(.pdf-page) .wishes').cloneNode(false);
                list.style.counterReset = 'wish ' + numbered;
                body.appendChild(list);
            }

            newPage();
            body.appendChild(clean(document.querySelector('.letter-head')));
            var wishes = document.querySelectorAll('.sheet:not(.pdf-page) .wish');
            for (var i = 0; i < wishes.length; i++) {
                if (!list) {
                    startList();
                }
                var wish = clean(wishes[i]);
                list.appendChild(wish);
                if (overflows() && 1 < list.children.length) {
                    list.removeChild(wish);
                    newPage();
                    startList();
                    list.appendChild(wish);
                }
                numbered++;
            }
            var empty = document.querySelector('.sheet:not(.pdf-page) .empty');
            if (empty) {
                body.appendChild(clean(empty));
            }
            var foot = clean(document.querySelector('.letter-foot'));
            body.appendChild(foot);
            if (overflows()) {
                body.removeChild(foot);
                newPage();
                body.appendChild(foot);
            }

            return { holder: holder, pages: pages };
        }

        function makePdf(scale, quality) {
            var built = buildPages();
            var paper = getComputedStyle(document.documentElement).getPropertyValue('--paper').trim() || '#ffffff';
            var options = { pixelRatio: scale, quality: quality, backgroundColor: paper, cacheBust: false,
                imagePlaceholder: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' };
            var pdf = new window.jspdf.jsPDF({ unit: 'mm', format: 'a4', compress: true });

            return window.htmlToImage.getFontEmbedCSS(built.pages[0]).then(function (fonts) {
                options.fontEmbedCSS = fonts;
                var chain = Promise.resolve();
                built.pages.forEach(function (page, index) {
                    chain = chain.then(function () {
                        setStatus('Préparation du PDF… page ' + (index + 1) + ' sur ' + built.pages.length);
                        return window.htmlToImage.toJpeg(page, options);
                    }).then(function (image) {
                        if (0 < index) {
                            pdf.addPage();
                        }
                        pdf.addImage(image, 'JPEG', 0, 0, 210, 297, undefined, 'FAST');
                    });
                });
                return chain;
            }).then(function () {
                built.holder.parentNode.removeChild(built.holder);
                return pdf.output('blob');
            }, function (error) {
                built.holder.parentNode.removeChild(built.holder);
                throw error;
            });
        }

        opener.addEventListener('click', function () {
            setStatus('');
            dialog.showModal();
            loadLibraries().catch(function () {});
        });
        dialog.querySelector('[data-mail-cancel]').addEventListener('click', function () {
            if (!busy) {
                dialog.close();
            }
        });
        dialog.addEventListener('cancel', function (event) {
            if (busy) {
                event.preventDefault();
            }
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (busy) {
                return;
            }
            busy = true;
            send.disabled = true;
            setStatus('Préparation du PDF…');

            loadLibraries().then(prepareImages).then(function () {
                return document.fonts.ready;
            }).then(function () {
                return makePdf(2, 0.86);
            }).then(function (blob) {
                // Trop lourd pour l'envoi (7 Mo) : une version plus légère.
                return blob.size > 6800000 ? makePdf(1.4, 0.72) : blob;
            }).then(function (blob) {
                setStatus('Envoi en cours…');
                var data = new FormData(form);
                data.append('pdf', blob, 'lettre.pdf');
                return fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin', headers: { Accept: 'application/json' } });
            }).then(function (response) {
                return response.json().catch(function () {
                    return { ok: false, message: "L'envoi a échoué (" + response.status + '). Réessayez.' };
                });
            }).then(function (result) {
                if (!result.ok) {
                    throw new Error(result.message || "L'envoi a échoué. Réessayez.");
                }
                setStatus(result.message || 'Lettre envoyée !', 'success');
                form.querySelector('[data-mail-recipients]').value = '';
                setTimeout(function () {
                    if (!busy) {
                        dialog.close();
                    }
                }, 2500);
            }).catch(function (error) {
                setStatus(error && error.message && 'script' !== error.message
                    ? error.message
                    : 'Le PDF n\'a pas pu être préparé. Vérifiez votre connexion et réessayez.', 'error');
            }).then(function () {
                busy = false;
                send.disabled = false;
            });
        });
    }
    </script>
</body>
</html>
