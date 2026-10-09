#!/usr/bin/env bash
# Calculs des nouveaux bonus et migration du barème uniquement ; données locales restaurées.
set -euo pipefail
cd "$(dirname "$0")/../.."
reward_test() {
    docker compose -f docker/compose.yml exec -T -u site -e LISTEKDO_LOCAL_TEST=1 -e GEM_TEST_PHASE="$1" php /usr/local/bin/php -q /var/www/site/docker/tests/gem-rewards.php
}
reward_test setup
trap 'reward_test cleanup' EXIT
reward_test check
reward_test persist
