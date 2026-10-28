#!/usr/bin/env bash
# Test ciblé : photos, GIF, dates, réactions et visionneuse des commentaires (comments-media.js) sur le Docker local.
# Supprime ensuite les comptes « Test1comm… », leurs idées, commentaires et images, même en cas d'échec.
set -uo pipefail
cd "$(dirname "$0")"
COMPOSE="docker compose -f ../compose.yml"
sql() { $COMPOSE exec -T mysql mysql -uroot -proot datcharrye -e "$1" 2>/dev/null; }
mkdir -p out && touch out/.start-comments
# Clé GIPHY factice pendant le test (les réponses de GIPHY sont simulées), réglage d'origine restauré à la fin.
saved_giphy=$(sql "SELECT CONCAT('REPLACE INTO kdo_setting (name,value) VALUES (', QUOTE(name), ',', QUOTE(value), ');') FROM kdo_setting WHERE name='giphy_key';" | tail -n +2)
sql "REPLACE INTO kdo_setting (name, value) VALUES ('giphy_key', 'testkey1234567890');"

cleanup() {
    sql "DELETE FROM kdo_setting WHERE name = 'giphy_key'; $saved_giphy"
    for id in $(sql "SELECT id FROM liste_user WHERE nom LIKE 'Test1comm%';" | tail -n +2); do
        sql "DELETE FROM comment_reaction WHERE user_id = $id OR comment_id IN (SELECT id FROM comment WHERE user_id = $id);
             DELETE FROM comment WHERE user_id = $id OR product_id IN (SELECT id FROM liste_noel WHERE user_id = $id);
             DELETE FROM notification WHERE author_id = $id; DELETE FROM reaction WHERE user_id = $id; DELETE FROM user_badge WHERE user_id = $id;
             DELETE FROM liste_noel WHERE user_id = $id; DELETE FROM user_friend WHERE user_id = $id; DELETE FROM liste_user WHERE id = $id;"
        rm -rf "../../listeKdo/uploads/$id"
    done
    find ../../listeKdo/uploads/comments -type f -newer out/.start-comments -delete 2>/dev/null || true
    rm -rf node_modules package-lock.json package.json
}
trap cleanup EXIT

docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/w -w /w \
    -v "$PWD/../../listeKdo":/site:ro mcr.microsoft.com/playwright:v1.63.0-noble \
    sh -c 'npm install --silent --no-save playwright-core@1.63 >/dev/null 2>&1 && node comments-media.js'
