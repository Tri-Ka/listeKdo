# Extension Chrome « Liste de Kdo »

Ajoute le produit de la page en cours à votre liste de cadeaux.

## Installation

1. Ouvrir `chrome://extensions` dans Chrome.
2. Activer le **Mode développeur** (en haut à droite).
3. Cliquer sur **Charger l'extension non empaquetée** et choisir ce dossier (`extension-chrome`).
   Sous WSL, le chemin Windows est `\\wsl$\<distribution>\home\datch\projects\sftp\extension-chrome`.
4. Épingler l'extension (icône puzzle, puis l'épingle) pour l'avoir dans la barre.

## Utilisation

1. Se connecter une fois sur http://datcharrye.free.fr/listeKdo/ dans Chrome.
2. Sur la page d'un produit, cliquer sur l'icône cadeau.
3. Vérifier le nom, la description, l'image et le lien, puis « Ajouter à ma liste ».

En bas de la fenêtre, « Site » permet de basculer sur le Docker local (`localhost:8090`) pour tester.

## Fonctionnement

- `extract.js` lit la page : données produit schema.org en priorité, puis Open Graph, puis les grandes images visibles.
  Les paramètres de suivi (`utm_…`, `gclid`…) sont retirés du lien.
- `popup.js` récupère le compte et le jeton CSRF via `actions/me.php`, puis envoie l'idée à `actions/addObject.php`,
  avec la session déjà ouverte dans Chrome (aucun mot de passe stocké dans l'extension).
- Mises à jour : `update.js` compare la version du manifest à `download/extension-version.txt` sur le site.
  `background.js` vérifie au démarrage de Chrome puis toutes les 6 h et met une flèche sur l'icône ;
  la fenêtre affiche alors une bannière qui télécharge le zip. Une fois le dossier remplacé,
  « Recharger l'extension » (`chrome.runtime.reload()`) charge les nouveaux fichiers.
  Pour publier une version : augmenter `version` dans `manifest.json`, lancer `docker/build-extension.sh`,
  puis envoyer le zip et `extension-version.txt` sur le FTP.
- Permissions : `activeTab` + `scripting` (lire la page uniquement quand on clique sur l'icône),
  `storage` (site choisi), `alarms` (vérification périodique), `downloads` (télécharger la mise à jour),
  et l'accès aux deux adresses du site.

Testé par `docker/tests/run.sh` (`docker/tests/extension.js`).
