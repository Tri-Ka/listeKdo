# Consignes du projet

Site perso hébergé chez **Free pages perso** (`http://datcharrye.free.fr/`).
L'application principale est `listeKdo/` : une liste de cadeaux (Noël, anniversaires) entre amis.

## Structure

| Chemin | Rôle | Envoyé sur le FTP ? |
|---|---|---|
| `listeKdo/` | Le site (PHP 4 + MySQL) | **Oui** |
| `docker/` | Environnement de dev local | **Non** |
| `.vscode/sftp.json` | Connexion FTP (extension SFTP de VS Code) | **Non** |
| `CLAUDE.md` | Ce fichier | **Non** |
| `sql/` | Scripts SQL à exécuter à la main dans le phpMyAdmin de Free | **Non** |
| `extension-chrome/` | Extension Chrome pour ajouter un produit à sa liste depuis n'importe quel site (voir son `README.md`). Après toute modification, augmenter `version` dans `manifest.json` et lancer `docker/build-extension.sh`, qui régénère `listeKdo/download/liste-kdo-extension.zip` (téléchargé depuis le site) et `listeKdo/download/extension-version.txt` (l'extension le compare à sa version pour proposer la mise à jour), puis envoyer ces deux fichiers sur le FTP | **Non** (seul le zip part en ligne) |

Le dossier local `/home/datch/projects/sftp` correspond à la **racine du FTP** (`remotePath: "/"`).
Tout nouveau fichier ou dossier qui ne doit pas partir en ligne doit être ajouté à `ignore` dans `.vscode/sftp.json`.

## Hébergement Free : contraintes

Ces informations ont été relevées le 01/10/2026 sur un `phpinfo()` du serveur. Ce fichier a été supprimé depuis, car il exposait la configuration publiquement. Pour revérifier, déposer temporairement un fichier `phpinfo()`, puis le retirer du FTP.

- **PHP 4.4.3** en CGI, et pas PHP 5 ni plus récent.
- **MySQL 5.7.44** sur `sql.free.fr`, avec l'extension `mysql_*` uniquement. Tables en MyISAM, majoritairement en utf8.
- `register_globals = On`, `magic_quotes_gpc = On`, `short_open_tag = On`.
- `safe_mode = On` : pas de `exec`/`system`/`shell_exec`, `set_time_limit` est ignoré, et les fichiers ouverts doivent appartenir au même propriétaire que le script.
- `upload_max_filesize` et `post_max_size` sont limités à **2 Mo**, `memory_limit` à **32 Mo**, et `max_execution_time` à **30 s**.
- Extensions disponibles : `mysql`, `gd`, `curl`, `pcre`, `session`, `xml`, `zlib`, `bcmath`, `calendar`, `ctype`, `exif`, `sockets`.
- Extensions **absentes** : `json`, `mbstring`, `mysqli`, `PDO`, `simplexml`, `filter`.
- Les appels sortants de Free sont filtrés. Par exemple, `api.scraperapi.com` est injoignable. C'est pour cette raison que la récupération des métadonnées produit passe par un Worker Cloudflare.

## Règles de code : PHP 4 strict

Tout code ajouté dans `listeKdo/` doit fonctionner en **PHP 4.4**. Sont donc interdits :

| Interdit (PHP 5+) | À utiliser à la place |
|---|---|
| `json_encode` / `json_decode` | `kdo_json()` (`lib/json.php`) |
| `mysqli_*`, `PDO`, SQL concaténé | `db_query`, `db_all`, `db_one`, `db_insert`, `db_update` (`lib/db.php`) avec des marqueurs `?` |
| `try` / `catch` / `throw` | Valeurs de retour + `mysql_error()` |
| `public` / `private` / `protected` / `static` / `__construct` | `var $x;` et un constructeur portant le nom de la classe |
| Tableaux courts `[]` | `array()` |
| Fonctions anonymes, `use`, `namespace`, `__DIR__` | Fonctions nommées, `dirname(__FILE__)` |
| `?:`, `??`, `instanceof` | Ternaire complet, `isset()`, `is_a()` |
| `foreach ($a as &$v)` | `foreach ($a as $k => $v) { $a[$k] = … }` |
| `file_put_contents`, `str_ireplace`, `stripos`, `array_combine`, `http_build_query`, `htmlspecialchars_decode` | `fopen`/`fwrite`, `eregi_replace`/`preg_replace` avec `/i`, `strpos(strtolower())`… |
| `DateTime`, `date_default_timezone_set` | `date()`, `mktime()`, `strtotime()` |
| `mb_*` | Fonctions `str*` classiques. Attention aux accents en UTF-8. |

Autres points d'attention :

- **Magic quotes et register_globals** : `lib/bootstrap.php` retire les antislashs ajoutés par les magic quotes et supprime les variables globales créées par `register_globals`. Ne pas refaire de `stripslashes()` dans le code. Toujours initialiser ses variables.
- **Encodage** : les fichiers sont en UTF-8. La connexion MySQL reste dans l'encodage par défaut (**pas de `SET NAMES`**) : les données existantes ont été écrites ainsi, et les relire autrement casserait les accents.
- **Anciennes données** : certains textes contiennent encore `\'` ou `\"`. `legacy_text()` les nettoie à l'affichage.
- **Vérification de syntaxe** : `php -l` avec le vrai PHP 4 (voir plus bas) refuse toute syntaxe PHP 5.

## Architecture de `listeKdo/`

| Chemin | Rôle |
|---|---|
| `index.php` | Seule page : charge les données, puis affiche `templates/page.php` |
| `lib/bootstrap.php` | Inclus partout en premier : nettoyage des entrées, `config.php`, puis les autres fichiers de `lib/` |
| `lib/db.php` | Requêtes avec marqueurs `?` échappés. Un tableau passé en paramètre devient une liste pour `IN (?)` |
| `lib/security.php` | `e()` (échappement HTML), CSRF, HMAC, mots de passe, `safe_url()` |
| `lib/auth.php` | `current_user()`, `require_login()`, `login()`, `logout()` |
| `lib/models.php` | Toutes les requêtes métier (utilisateurs, idées, réactions, notifications…) |
| `lib/http.php` | `input()`, `require_post()`, `succeed()`, `fail()`, messages flash |
| `lib/view.php` | `render()`, `icon()`, `avatar()`, `asset()`… |
| `lib/upload.php` | Envoi d'images : vérification, nom aléatoire, ré-encodage, réduction |
| `templates/page.php`, `templates/partials/` | Gabarits HTML |
| `actions/*.php` | Une action par fichier, en POST uniquement (sauf `me.php`, lecture seule, utilisé par l'extension Chrome) |
| `css/app.css` | Feuille de style unique. Les thèmes ne changent que des variables CSS (`[data-theme]`) |
| `js/app.js` | Module ES natif, sans dépendance ni build |
| `img/icons.svg` | Sprite d'icônes (Font Awesome Free, CC BY 4.0) : `icon('gift')`. Les icônes restent en SVG, jamais en images |
| `cache/` | Créé par le serveur : liens courts TinyURL des listes (`short_share_url()`). Protégé par un `.htaccess`, jamais envoyé depuis le local |
| `img/deco/<thème>/` | Titre et décorations de chaque thème, découpés dans `img/elements.png` (anniversaire, Noël, naissance) `img/elements-mariage.png` et `img/elements-wishlist.png` : planches sources à fond transparent, non envoyées sur le FTP |

Conventions :

- **Affichage** : toute donnée affichée passe par `e()`, ou par `multiline()` pour du texte avec retours à la ligne. Les liens passent par `safe_url()`.
- **Actions** : chaque action commence par `require_post()` (POST + jeton CSRF), puis `require_login()` si besoin. Elle se termine par `succeed($data, $redirection, $message)` ou `fail($message, $redirection)`. Elles répondent en JSON quand la requête envoie `Accept: application/json` (le cas de `fetch()` dans `js/app.js`), sinon par une redirection avec un message flash.
- **Formulaires** : toujours inclure `csrf_field()`. Un formulaire avec `data-ajax="<type>"` est envoyé en arrière-plan par `js/app.js`, qui appelle le gestionnaire du même nom dans `ajaxHandlers`.
- **Fenêtres** : éléments `<dialog>` natifs, ouverts avec `data-open="id"` et fermés avec `data-close`. Jamais d'icône ni d'emoji dans le titre d'une fenêtre (`.modal__header h2`).
- **Confirmations** : jamais de `alert()` / `confirm()` / `prompt()` du navigateur. Un bouton avec `data-confirm="Message"` ouvre la fenêtre `templates/partials/confirm.php` (par-dessus la fenêtre en cours), avec en option `data-confirm-title`, `data-confirm-ok` (libellé du bouton) et `data-confirm-icon` (icône du sprite). Une fois confirmé, le clic est rejoué. Dans les tests, cliquer ensuite sur `#confirm-dialog [data-confirm-ok]`.
- **Requêtes SQL** : pas de requête dans une boucle. Charger les données liées en une fois (`users_by_ids()`, `IN (?)`).
- **Thèmes** (`birthday`, `noel`, `naissance`, `mariage`, `wishlist` : neutre, sans date) : `themes()` dans `lib/models.php` est la seule liste. Elle définit les textes (`heading`, `subtitle`, `note`… avec `%s` pour le nom), les décorations de l'en-tête (`left`, `right`) et du pied de page (`footer`, aussi utilisée comme vignette dans le choix du type de liste), le nom de l'événement (`event`, `soon`), le type de date (`date` : `christmas`, `yearly`, `once` ou `none`), le rappel aux amis (`reminder`, `emoji`) et la couleur de la barre du navigateur (`color`). Pour ajouter un thème : compléter `themes()` ; dans `css/app.css`, ajouter un bloc `[data-theme="…"]` (couleurs), les positions `.deco--…`, `.theme-picker__option--…`, `.theme-field__option--…` et `.occasion--…` ; ajouter le même bloc de couleurs dans `extension-chrome/popup.css` ; créer `img/deco/<thème>/` (`title.png` et les décorations) et `img/<thème>/metaOg.jpg` (aperçu de partage, 1200 × 630). Le type de liste se choisit à l'inscription, dans « Mon profil » et dans les fiches des listes d'enfants (`templates/partials/theme_field.php`, validé par `valid_theme()`), ou avec le sélecteur de l'en-tête (`actions/changeTheme.php`).
- **Coups de cœur** : colonne `liste_noel.favorite`, ajoutée par `sql/2026-10-01-coups-de-coeur.sql`. Seul le propriétaire peut les modifier.
- **Petit mot du propriétaire** : colonne `liste_user.message`, ajoutée par `sql/2026-10-01-mot-du-proprietaire.sql`, affichée en carte dans le pied de page et modifiable dans « Mon profil ». Sans la colonne, le texte `note` du thème s'affiche et le champ est masqué (`array_key_exists('message', …)`).
- **Collections** : une idée peut contenir plusieurs éléments (table `liste_item`, ajoutée par `sql/2026-10-01-collections.sql`). Une idée est une collection dès qu'elle a des éléments. Les proches réservent les éléments un par un (`actions/itemGifted.php`) ; une collection compte comme « offerte » quand tout est réservé (`object_collection_state()`). Sans la table, l'option est masquée (`items_enabled()`).
- **Migration `sql/2026-10-01-prix-participations-enfants.sql`** (chaque partie est masquée tant qu'elle n'est pas faite, via `db_has_table()` / `db_has_column()`) :
  - **Prix** (`liste_noel.price`) : étiquette sur l'image, filtre budget et tri par prix (côté JS), champ prix dans l'extension.
  - **Reçu** (`liste_noel.received_at`) : menu « ⋯ » de la vignette ; les idées reçues ne sont visibles que de ceux qui gèrent la liste (onglet « Reçus »).
  - **Cadeau à plusieurs** (table `liste_participation`) : « Je l'offre » ouvre un choix « seul » / « à plusieurs » ; complet quand les montants atteignent le prix.
  - **Date de l'événement** (`liste_user.event_date`) : compte à rebours animé (`event_next()`, Noël = 25/12 par défaut, anniversaire chaque année), amis triés par prochain événement.
  - **Listes secondaires**, appelées « listes d'enfants » dans le code (table `liste_manager`, plusieurs gestionnaires possibles). À l'écran, on dit toujours « liste secondaire » et « gestionnaire », jamais « enfant » ni « parent » ; le code et la base gardent `child`/`manager` : `can_manage()` donne `$ctx['canEdit']`. Les gestionnaires modifient la liste et voient aussi les dons (`canGift`). Les actions visent une liste avec le champ `owner` (`target_owner()`, `managed_object()`). Une liste secondaire créée depuis le site n'a pas de mot de passe.
- **Question secrète** (`sql/2026-10-01-question-secrete.sql`) : liste dans `secret_questions()` (ou question libre). La réponse est hachée après `secret_normalize()` (sans majuscules, accents ni ponctuation). « Mot de passe oublié ? » dans la fenêtre de connexion (`actions/forgotQuestion.php` puis `actions/resetPassword.php`), blocage 15 min après 5 erreurs. À la connexion, une fenêtre invite ceux qui n'en ont pas à en choisir une (`$_SESSION['kdo_ask_secret']`).
- **Notifications de badges** : type `NOTIF_BADGE` (7), une par lot de badges obtenus (`badges_refresh()`), où `author_id` est la personne et `product_id` le badge mis en avant (pas une idée) ; le nombre de badges du lot se retrouve par la date d'obtention (`notifications_add_badges()`). Visible par la personne (« Bravo ! Vous avez obtenu… ») et par ses amis. Comme pour `NOTIF_EVENT`, les jointures sur `liste_noel` excluent ce type.
- **Rappels d'événements** : type de notification `NOTIF_EVENT` (4), où `author_id` est l'ami et `product_id` le palier en jours (30, 7 ou 1), pas une idée. Ils sont créés au chargement de la page par `notifications_create_event_reminders()`, partagés par tous les amis, et seul le palier en cours est créé. Les jointures sur `liste_noel` sont donc en `LEFT JOIN` dans les requêtes de notifications.
- **Les cadeaux que j'offre** : `my_gifts()`, fenêtre ouverte depuis le menu du compte.
- **Notifications** : 10 par page (`NOTIFICATIONS_PER_PAGE`), « Voir plus » et onglet « Non lues » via `actions/notifications.php`. État lu / non lu par utilisateur dans `notification_state` (`sql/2026-10-01-notifications-lues.sql`) : sans ligne, une notification est lue si elle est antérieure à `liste_user.last_seen_notif` (« Tout marquer comme lu »). Sans la table, ouvrir le panneau marque tout comme lu (ancien fonctionnement).
- **Amis** : 6 avatars au maximum dans la colonne ou la pile ; le bouton « +N » ouvre la fenêtre « Mes amis ».
- **Infos de don** : les éléments `.gift-slot` et `.gift-only` ne s'affichent que si « Voir les idées offertes » est activé (`body.show-gifted`).
- **Images envoyées** : le navigateur les réduit avant l'envoi (`data-resize-images` sur le formulaire, `data-max-size` sur le champ), puis le serveur les vérifie et les ré-encode avec GD.
- **Liens de partage** : Free n'a pas de HTTPS, or certaines applis (WhatsApp…) ouvrent les liens en HTTPS. Le partage utilise donc un lien court TinyURL en HTTPS, qui redirige vers le site en HTTP. Il est créé côté serveur via l'API HTTP de TinyURL (`actions/shortUrl.php`, appelé par le JS après l'affichage). Si TinyURL ne répond pas, le lien long reste.
- **Administration** (`admin.php`, `lib/admin.php`, `templates/admin.php`, `css/admin.css`, `js/admin.js`) : réservée aux admins (colonne `liste_user.role`, `sql/2026-10-02-roles.sql`, `is_admin()` / `require_admin()`), lien dans le menu du compte. Tableaux Utilisateurs et Listes rendus côté serveur (recherche, tri, filtres, pagination dans l'URL), rechargés par `admin.php?partial=1`. Actions : changer le rôle, parents d'une liste (`actions/adminManagers.php` : une liste avec au moins un parent devient une liste d'enfant, sans toucher à son mot de passe), mot de passe provisoire, lien de secours, supprimer un compte (`admin_delete_user()`, avec tout ce qui lui est lié). On ne peut ni changer son propre rôle ni supprimer un admin.
- **Dernière visite** (`liste_user.last_seen_at`, `sql/2026-10-02-derniere-visite.sql`) : mise à jour par `auth_touch()` dans `current_user()`, au plus toutes les 10 minutes. Affichée et triable dans l'admin. Les dates d'avant la migration sont approximatives (dernière ouverture des notifications ou dernière idée).
- **Lien de secours** (`sql/2026-10-02-lien-de-secours.sql`) : un admin génère un lien `reset.php?t=<id>-<jeton>` (usage unique, 48 h, raccourci par TinyURL). Seul `sha1(jeton)` est stocké dans `liste_user.reset_token`. La personne choisit un nouveau mot de passe et est connectée (`actions/resetByLink.php`).
- **Liste privée** (`liste_user.is_private`, `sql/2026-10-02-liste-privee.sql`) : case dans « Mon profil » et dans la fiche d'une liste secondaire. Seuls ceux qui la gèrent la voient (`can_view()`, `$ctx['canView']`) : les autres voient « Cette liste est privée », elle disparaît de leurs amis (`visible_lists()`) et de leurs notifications (`hidden_list_ids()` dans `notifications_where()`), et les actions sur ses idées passent par `visible_object()`. Le bloc de partage est masqué. Sans la colonne, l'option est masquée.
- **Badges et trophées** (`lib/badges.php`, tables `badge` et `user_badge`, `sql/2026-10-03-badges.sql`) : un badge = un indicateur (`badge_metrics()` : idées, cadeaux réservés, cagnottes, commentaires, réactions, amis, profil complet, ancienneté…) et un seuil. Les badges par défaut sont dans `badge_fixtures()` et installés **par le site** (`badges_install()`, à la première ouverture de l'onglet Badges de l'admin), jamais par un script SQL : les emojis et accents doivent passer par la même connexion que le reste. Les badges de la personne connectée sont recalculés à l'affichage de la page (`badges_refresh()`, au plus toutes les 30 s) ; les nouveaux sont fêtés une fois (`#badge-new-dialog`, pas d'ouverture automatique sous `navigator.webdriver`). Pastille « 🏅 N badges » sous le nom → vitrine (`templates/partials/badges.php`) : progression pour ceux qui gèrent la liste, badges obtenus seulement pour les autres, badges « secrets » cachés tant qu'ils ne sont pas obtenus. Administration › Badges : modifier, ajouter, désactiver (un badge obtenu reste acquis). Classes CSS `.trophy-tile` / `.medal--<niveau>` (`.badge` est la pastille des notifications).
- **Sécurité** : le cookie « rester connecté » (`listeKdoAuth`) est signé avec une clé dérivée de `config.php`. Les mots de passe sont salés et hachés (format `s1$…`) ; les anciens MD5 sont convertis à la connexion. Le **propriétaire d'une liste ne doit jamais voir qui offre quoi** (`$ctx['canGift']`).

## Git

Dépôt : `git@github.com:Tri-Ka/listeKdo.git` (**public**), branche `master`. La racine du dépôt est ce dossier : le site est dans `listeKdo/`. L'historique d'avant le 01/10/2026 avait le site à la racine.

Jamais dans git (voir `.gitignore`) : `listeKdo/config.php`, `docker/config.local.php` (modèle : `docker/config.local.php.example`), `.vscode/` (mot de passe FTP), `listeKdo/uploads/`, `listeKdo/cache/` et l'export `docker/mysql-init/datcharrye.sql`. Avant chaque commit, vérifier qu'aucun mot de passe ni clé n'est indexé.

## Secrets

`listeKdo/config.php` contient les identifiants MySQL de Free, une clé ScraperAPI et le jeton du Worker. Ce fichier est dans `listeKdo/.gitignore`.

- Ne jamais afficher ces valeurs dans une réponse ou un log.
- Ne jamais les exposer au navigateur, que ce soit en JS, en HTML ou dans une réponse AJAX.
- Le mot de passe FTP n'est pas stocké ici. S'il est ajouté dans `.vscode/sftp.json`, ce fichier ne doit jamais être publié.

## Environnement local (Docker)

Le dossier `docker/` sert **uniquement en local** : il n'est jamais envoyé sur le FTP. Il contient :

- `Dockerfile` : PHP **4.4.9** compilé depuis les sources, en CGI derrière Apache, sur une base Debian Jessie. Squeeze et Wheezy plantent sous WSL2 à cause de l'absence de `vsyscall`.
- `php.ini` : les mêmes réglages que Free.
- `compose.yml` : les services `php`, `mysql` (5.7, sans mode strict, latin1) et `phpmyadmin`.
- `config.local.php` : monté **à la place** de `listeKdo/config.php` dans le conteneur, pour pointer vers la base locale. Toute nouvelle clé ajoutée à `config.php` doit aussi y être reportée.
- `mysql-init/` : les fichiers `.sql` placés ici sont importés au premier démarrage, quand le volume est vide. `datcharrye.sql` est l'export de la base Free du 01/10/2026, avec les vraies données des utilisateurs : il ne doit jamais être publié. Pour rafraîchir les données, refaire un export depuis http://sql.free.fr/phpMyAdmin/ et remplacer ce fichier.

```bash
cd docker
docker compose up -d                       # démarrer
docker compose down                        # arrêter (données conservées)
docker compose down -v && docker compose up -d   # repartir d'une base vide et réimporter mysql-init/
docker compose logs -f php                 # logs Apache et erreurs PHP
```

| Service | Accès |
|---|---|
| Site | http://localhost:8090/listeKdo/ |
| phpMyAdmin | http://localhost:8091/ (root/root) |
| MySQL | `localhost:3307`, base et utilisateur `datcharrye`, mot de passe `datcharrye` |

Pour tester un fragment en PHP 4 sans passer par le navigateur (`php -r` n'existe pas en PHP 4, il faut passer le code par stdin) :

```bash
cd docker
printf '<?php echo phpversion(); ?>' | docker compose exec -T php /usr/local/bin/php -q
```

Vérifier la syntaxe PHP 4 de tout le site :

```bash
cd docker
for f in $(cd ../listeKdo && find . -name '*.php' -not -path './uploads/*'); do
  docker compose exec -T php /usr/local/bin/php -l "/var/www/site/listeKdo/$f" | grep -v 'No syntax errors'
done
```

Les scripts PHP lancés en ligne de commande doivent tourner avec l'utilisateur `site` (`docker compose exec -u site …`), sinon `safe_mode` bloque l'accès aux fichiers.

**Test de bout en bout** : `docker/tests/run.sh` lance Chromium (image Docker Playwright) sur le site local. Il couvre la connexion, l'inscription avec photo, les dons, les réactions, les commentaires, l'ajout, la modification et la suppression d'idées, les notifications, et des vérifications de sécurité (CSRF, XSS, injection SQL, ancien cookie). Il teste aussi les collections (`docker/tests/collections.js`) et l'extension Chrome (`docker/tests/extension.js`, sur la fausse page produit `docker/tests/fixtures/product.html`). Il modifie la base **locale** pendant le test (mot de passe `test` pour Etienne et Mallory, restaurés à la fin) et nettoie ce qu'il crée. Les captures d'écran sont dans `docker/tests/out/`.

MySQL est en 5.7 en local comme chez Free. Le `sql_mode` de Free n'est pas connu : en local, le mode strict est désactivé.

## Déroulement d'une modification

1. Modifier les fichiers dans `listeKdo/`.
2. Tester sur http://localhost:8090/listeKdo/ (Docker).
3. Vérifier la syntaxe PHP 4 (`php -l`) et lancer `docker/tests/run.sh`.
4. Envoyer sur Free avec VS Code : *SFTP: Upload Changed Files*, ou clic droit → *Upload*. `uploadOnSave` est désactivé, donc rien ne part automatiquement.
5. Vérifier en ligne sur http://datcharrye.free.fr/listeKdo/.

Ne **jamais** envoyer de fichier sur le FTP sans que l'utilisateur l'ait demandé.
