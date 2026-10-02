#!/usr/bin/env bash
# Test de bout en bout sur le Docker local (http://localhost:8090).
# Modifie la base LOCALE : mot de passe « test » pour Etienne (id 1) et Mallory (id 141) pendant le test
# (les vrais mots de passe et photos sont restaurés à la fin), puis supprime les comptes et fichiers créés.
set -euo pipefail
cd "$(dirname "$0")"
COMPOSE="docker compose -f ../compose.yml"
sql() { $COMPOSE exec -T mysql mysql -uroot -proot datcharrye -e "$1" 2>/dev/null; }

# Sauvegarde des vrais mots de passe, restaurés à la fin même si le test échoue.
saved=$(sql "SELECT CONCAT('UPDATE liste_user SET password=', QUOTE(password), ', secret_question=', QUOTE(secret_question), ', secret_answer=', QUOTE(secret_answer), ', pictureFile=', QUOTE(pictureFile), ' WHERE id=', id, ';') FROM liste_user WHERE id IN (1, 141);" | tail -n +2)
trap 'sql "$saved"' EXIT
# Une question secrète factice, pour que la fenêtre d'invitation ne s'ouvre pas pendant les tests.
sql "UPDATE liste_user SET password = MD5('test'), secret_question = 'test', secret_answer = 'test' WHERE id IN (1, 141);"
mkdir -p out && touch out/.start

status=0
docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/w -w /w \
    -v "$PWD/../../extension-chrome":/ext:ro mcr.microsoft.com/playwright:v1.63.0-noble \
    sh -c 'npm install --silent --no-save playwright-core@1.63 >/dev/null 2>&1 && node e2e.js && node collections.js && node features.js && node secret.js && node admin.js && node private.js && node extension.js' || status=$?

# Nettoyage : comptes de test et images envoyées pendant le test.
for id in $(sql "SELECT id FROM liste_user WHERE nom LIKE 'Test1%';" | tail -n +2); do rm -rf "../../listeKdo/uploads/$id"; done
sql "DELETE FROM liste_user WHERE nom LIKE 'Test1%';"
# Liste privée (private.js) : remise en public même si le test s'arrête en cours de route.
sql "UPDATE liste_user SET is_private = 0 WHERE id = 1;" || true
sql "DELETE FROM liste_noel WHERE nom = 'Idée reçue test';"
sql "DELETE FROM notification WHERE product_id IN (SELECT id FROM liste_noel WHERE nom = 'Produit test extension'); DELETE FROM liste_noel WHERE nom = 'Produit test extension'; DELETE FROM liste_item WHERE product_id NOT IN (SELECT id FROM liste_noel);"
find ../../listeKdo/uploads/img -type f -newer out/.start -delete
# Photo de profil envoyée par features.js (l'ancienne est remise par « saved »).
find ../../listeKdo/uploads/1 -maxdepth 1 -type f -newer out/.start -delete 2>/dev/null || true
rm -rf node_modules package-lock.json package.json
exit $status
