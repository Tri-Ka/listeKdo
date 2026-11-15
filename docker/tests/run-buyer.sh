#!/usr/bin/env bash
# Test ciblé « Je le prends » (cadeau à plusieurs) ; mots de passe, e-mails et idée de test restaurés même après un échec.
set -uo pipefail
cd "$(dirname "$0")"
COMPOSE="docker compose -f ../compose.yml"
sql() { $COMPOSE exec -T mysql mysql -uroot -proot datcharrye -e "$1" 2>/dev/null; }
saved=$(sql "SELECT CONCAT('UPDATE liste_user SET password=', QUOTE(password), ', secret_question=', QUOTE(secret_question), ', secret_answer=', QUOTE(secret_answer), ' WHERE id=', id, ';') FROM liste_user WHERE id IN (1, 141);" | tail -n +2)
saved_email=""
if sql "SHOW COLUMNS FROM liste_user LIKE 'email_verified_at';" | grep -q email; then
    saved_email=$(sql "SELECT CONCAT('UPDATE liste_user SET email=', QUOTE(email), ', email_verified_at=', QUOTE(email_verified_at), ' WHERE id=', id, ';') FROM liste_user WHERE id IN (1, 141);" | tail -n +2)
fi
cleanup() {
    sql "$saved"; [ -n "$saved_email" ] && sql "$saved_email"
    sql "DELETE FROM liste_participation WHERE product_id IN (SELECT id FROM liste_noel WHERE nom LIKE 'Cagnotte acheteur test%'); DELETE FROM notification WHERE product_id IN (SELECT id FROM liste_noel WHERE nom LIKE 'Cagnotte acheteur test%'); DELETE FROM liste_noel WHERE nom LIKE 'Cagnotte acheteur test%';"
}
trap cleanup EXIT
sql "UPDATE liste_user SET password = MD5('test'), secret_question = 'test', secret_answer = 'test' WHERE id IN (1, 141);"
[ -n "$saved_email" ] && sql "UPDATE liste_user SET email = CONCAT('test-', id, '@exemple.test'), email_verified_at = NOW() WHERE id IN (1, 141);"
mkdir -p out
docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD":/w -w /w mcr.microsoft.com/playwright:v1.63.0-noble \
    sh -c 'npm install --silent --no-save playwright-core@1.63 >/dev/null 2>&1 && node buyer.js'
