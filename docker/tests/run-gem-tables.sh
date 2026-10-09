#!/usr/bin/env bash
# Test ciblé des tableaux Gemmes ; fixtures et réglages locaux restaurés même après un échec.
set -euo pipefail
cd "$(dirname "$0")/../.."
cleanup_gem_tables() {
    docker compose -f docker/compose.yml exec -T -u site -e LISTEKDO_LOCAL_TEST=1 -e GEM_FIXTURE_OP=cleanup php /usr/local/bin/php -q /var/www/site/docker/tests/gem-tables-fixture.php
}
docker compose -f docker/compose.yml exec -T -u site -e LISTEKDO_LOCAL_TEST=1 php /usr/local/bin/php -q /var/www/site/docker/tests/gem-tables-fixture.php
trap cleanup_gem_tables EXIT
docker run --rm --network host -e ADMIN_MODAL_SECTION="${ADMIN_MODAL_SECTION:-}" -e GEM_TABLE_NATIVE_ONLY="${GEM_TABLE_NATIVE_ONLY:-}" -e BADGE_TABLE_SCREENSHOT_ONLY="${BADGE_TABLE_SCREENSHOT_ONLY:-}" -e ADMIN_TABLE_TEST="${ADMIN_TABLE_TEST:-gem-tables.js}" --user "$(id -u):$(id -g)" -v "$PWD":/site -w /tmp mcr.microsoft.com/playwright:v1.63.0-noble \
    sh -c 'npm install --silent --no-save --prefix /tmp/pw --cache /tmp/npm-cache playwright-core@1.63 >/dev/null 2>&1 && NODE_PATH=/tmp/pw/node_modules node "/site/docker/tests/$ADMIN_TABLE_TEST"'
