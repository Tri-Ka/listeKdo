#!/usr/bin/env bash
# Test ciblé : adresse e-mail obligatoire, connexion par e-mail et mot de passe oublié par e-mail (email.js).
# Nécessite le site local en PHP 8 avec MailDev : docker compose -f compose.yml -f compose.php8.yml up -d --build php maildev
# Crée un ancien compte sans adresse, puis supprime les comptes « Test1mail… », même en cas d'échec.
set -uo pipefail
cd "$(dirname "$0")"
COMPOSE="docker compose -f ../compose.yml"
sql() { $COMPOSE exec -T mysql mysql -uroot -proot datcharrye -e "$1" 2>/dev/null; }
mkdir -p out

cleanup() {
    for id in $(sql "SELECT id FROM liste_user WHERE nom LIKE 'Test1mail%';" | tail -n +2); do
        sql "DELETE FROM notification WHERE author_id = $id; DELETE FROM user_badge WHERE user_id = $id;
             DELETE FROM user_friend WHERE user_id = $id; DELETE FROM liste_user WHERE id = $id;"
        rm -rf "../../listeKdo/uploads/$id"
    done
    curl -s -X DELETE http://localhost:1088/email/all >/dev/null || true
    rm -rf node_modules package-lock.json package.json
}
trap cleanup EXIT

# Un ancien compte, sans adresse e-mail (mot de passe « test » à l'ancien format MD5, converti à la connexion).
sql "INSERT INTO liste_user (nom, code, password, theme) VALUES ('Test1mailancien', MD5(UUID()), MD5('test'), 'noel');"

docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/w -w /w \
    mcr.microsoft.com/playwright:v1.63.0-noble \
    sh -c 'npm install --silent --no-save playwright-core@1.63 >/dev/null 2>&1 && node email.js'
