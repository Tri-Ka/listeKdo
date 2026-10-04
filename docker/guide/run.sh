#!/usr/bin/env bash
# Captures d'écran de la page « Comment ça marche ? » (listeKdo/img/guide/*.jpg), sur le Docker local.
# Crée des comptes de démonstration (Léa, Hugo, Inès, Paul, Chloé et la liste secondaire Jules, codes « demo-… »),
# fait les captures, puis supprime tout ce qu'il a créé, même en cas d'erreur. Aucune vraie donnée n'apparaît.
# Thèmes standard uniquement (pas d'habillage). Images des idées : emojis sur fond pastel (products.js).
set -euo pipefail
cd "$(dirname "$0")"
COMPOSE="docker compose -f ../compose.yml"
sql() { $COMPOSE exec -T mysql mysql -uroot -proot datcharrye -e "$1" 2>/dev/null; }
UPLOADS=../../listeKdo/uploads/img
IMAGE=mcr.microsoft.com/playwright:v1.63.0-noble

cleanup() {
    ids=$(sql "SELECT GROUP_CONCAT(id) FROM liste_user WHERE code LIKE 'demo-%';" | tail -n +2)
    if [ -n "$ids" ] && [ "$ids" != "NULL" ]; then
        sql "DELETE FROM liste_item WHERE product_id IN (SELECT id FROM liste_noel WHERE user_id IN ($ids));
             DELETE FROM liste_participation WHERE user_id IN ($ids) OR product_id IN (SELECT id FROM liste_noel WHERE user_id IN ($ids));
             DELETE FROM comment WHERE user_id IN ($ids) OR product_id IN (SELECT id FROM liste_noel WHERE user_id IN ($ids));
             DELETE FROM reaction WHERE user_id IN ($ids) OR product_id IN (SELECT id FROM liste_noel WHERE user_id IN ($ids));
             DELETE FROM notification_state WHERE user_id IN ($ids);
             DELETE FROM notification WHERE author_id IN ($ids) OR product_id IN (SELECT id FROM liste_noel WHERE user_id IN ($ids));
             DELETE FROM liste_noel WHERE user_id IN ($ids);
             DELETE FROM liste_manager WHERE child_id IN ($ids) OR user_id IN ($ids);
             DELETE FROM user_friend WHERE user_id IN ($ids) OR friend_code LIKE 'demo-%';
             DELETE FROM user_badge WHERE user_id IN ($ids);
             DELETE FROM user_skin WHERE user_id IN ($ids);
             DELETE FROM user_visit WHERE user_id IN ($ids);
             DELETE FROM liste_user WHERE id IN ($ids);" || true
    fi
    rm -f "$UPLOADS"/demo-*.png
    rm -rf out node_modules package.json package-lock.json
}
trap cleanup EXIT
cleanup

mkdir -p out ../../listeKdo/img/guide
docker run --rm --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/w -w /w $IMAGE \
    sh -c 'npm install --silent --no-save playwright-core@1.63 >/dev/null 2>&1 && node products.js'
cp out/demo-*.png "$UPLOADS"/ && rm "$UPLOADS"/demo-plante.png
$COMPOSE exec -T mysql mysql -uroot -proot datcharrye < seed.sql 2>/dev/null

# Chromium complet (channel « chromium ») en français : sinon « Choose File » et dates au format américain.
docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -e LANG=fr_FR.UTF-8 -e LANGUAGE=fr \
    -v "$PWD":/w -w /w -v "$PWD/../../listeKdo/img/guide":/out -v "$PWD/../../extension-chrome":/ext:ro $IMAGE \
    sh -c 'node capture.js'
