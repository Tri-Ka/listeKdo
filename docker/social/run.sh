#!/usr/bin/env bash
# Génère les visuels et icônes uniquement en local, sans modifier la base.
set -euo pipefail
social_workspace=$(cd "$(dirname "$0")/../.." && pwd)
docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp \
    -v "$social_workspace":/site -w /tmp mcr.microsoft.com/playwright:v1.63.0-noble \
    sh -c 'npm install --silent --no-save playwright-core@1.63 >/dev/null 2>&1 && NODE_PATH=/tmp/node_modules node /site/docker/social/build.js'
