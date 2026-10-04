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
# Rappels d'événement d'Etienne et Mallory recréés à neuf : sinon un ancien rappel peut être repoussé
# au-delà des 10 premières notifications par celles des tests précédents.
sql "DELETE FROM notification WHERE type = 4 AND author_id IN (1, 141);"
mkdir -p out && touch out/.start

status=0
docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/w -w /w \
    -v "$PWD/../../extension-chrome":/ext:ro mcr.microsoft.com/playwright:v1.63.0-noble \
    sh -c 'npm install --silent --no-save playwright-core@1.63 >/dev/null 2>&1 && node e2e.js && node collections.js && node features.js && node secret.js && node admin.js && node badges.js && node shop.js && node referral.js && node account.js && node private.js && node suggestions.js && node tour.js && node extension.js' || status=$?

# Nettoyage : comptes de test et images envoyées pendant le test.
for id in $(sql "SELECT id FROM liste_user WHERE nom LIKE 'Test1%';" | tail -n +2); do rm -rf "../../listeKdo/uploads/$id"; done
# Amitiés avec les comptes de test (le parrainage en crée dans les deux sens).
sql "DELETE FROM user_friend WHERE user_id IN (SELECT id FROM liste_user WHERE nom LIKE 'Test1%') OR friend_code IN (SELECT code FROM liste_user WHERE nom LIKE 'Test1%');"
sql "DELETE FROM liste_user WHERE nom LIKE 'Test1%';"
# Liens de gestion et badges des comptes supprimés.
sql "DELETE FROM liste_manager WHERE child_id NOT IN (SELECT id FROM liste_user) OR user_id NOT IN (SELECT id FROM liste_user);"
sql "DELETE FROM user_badge WHERE user_id NOT IN (SELECT id FROM liste_user);" 2>/dev/null || true
sql "DELETE FROM user_badge WHERE badge_id IN (SELECT id FROM badge WHERE name LIKE 'Test1%'); DELETE FROM badge WHERE name LIKE 'Test1%';" 2>/dev/null || true
# Achats de la boutique faits par les tests (shop.js).
sql "DELETE FROM user_skin WHERE user_id IN (1, 141); UPDATE liste_user SET skin = NULL WHERE id IN (1, 141);" 2>/dev/null || true
sql "UPDATE liste_user SET frame = NULL, countdown_fx = NULL WHERE id IN (1, 141);" 2>/dev/null || true
sql "DELETE FROM notification WHERE type = 8 AND author_id NOT IN (SELECT id FROM liste_user);" 2>/dev/null || true
# Liste privée (private.js) : remise en public même si le test s'arrête en cours de route.
sql "UPDATE liste_user SET is_private = 0 WHERE id = 1;" || true
sql "DELETE FROM liste_viewer WHERE list_id = 1;" 2>/dev/null || true
sql "DELETE FROM liste_noel WHERE nom = 'Idée reçue test';"
# Suggestions (suggestions.js), si le test s'est arrêté avant de les supprimer.
sql "DELETE FROM comment WHERE product_id IN (SELECT id FROM liste_noel WHERE nom LIKE 'Suggestion test%'); DELETE FROM notification WHERE product_id IN (SELECT id FROM liste_noel WHERE nom LIKE 'Suggestion test%'); DELETE FROM liste_noel WHERE nom LIKE 'Suggestion test%';"
# Badges de suggestion obtenus par Mallory pendant le test, et leur notification.
sql "DELETE FROM notification WHERE type = 7 AND author_id = 141 AND product_id IN (SELECT id FROM badge WHERE code LIKE '%suggest%'); DELETE FROM user_badge WHERE user_id = 141 AND badge_id IN (SELECT id FROM badge WHERE code LIKE '%suggest%');" 2>/dev/null || true
sql "DELETE FROM notification WHERE product_id IN (SELECT id FROM liste_noel WHERE nom = 'Produit test extension'); DELETE FROM liste_noel WHERE nom = 'Produit test extension'; DELETE FROM liste_item WHERE product_id NOT IN (SELECT id FROM liste_noel);"
find ../../listeKdo/uploads/img -type f -newer out/.start -delete
# Photo de profil envoyée par features.js (l'ancienne est remise par « saved »).
find ../../listeKdo/uploads/1 -maxdepth 1 -type f -newer out/.start -delete 2>/dev/null || true
rm -rf node_modules package-lock.json package.json
exit $status
