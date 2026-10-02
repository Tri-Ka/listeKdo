#!/usr/bin/env bash
# Génère listeKdo/download/liste-kdo-extension.zip à partir de extension-chrome/,
# et listeKdo/download/extension-version.txt (version du manifest), que l'extension
# compare à la sienne pour proposer la mise à jour.
# À relancer après chaque modification de l'extension, en augmentant « version » dans
# extension-chrome/manifest.json, puis envoyer les deux fichiers sur le FTP.
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p listeKdo/download
python3 - <<'PY'
import json, os, zipfile
src = 'extension-chrome'
out = 'listeKdo/download/liste-kdo-extension.zip'
version = json.load(open(os.path.join(src, 'manifest.json')))['version']
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for root, _, files in os.walk(src):
        for name in sorted(files):
            path = os.path.join(root, name)
            # Les fichiers sont rangés dans un dossier « liste-kdo-extension » une fois décompressés.
            z.write(path, os.path.join('liste-kdo-extension', os.path.relpath(path, src)))
with open('listeKdo/download/extension-version.txt', 'w') as f:
    f.write(version + '\n')
print(f'{out} (version {version}, {os.path.getsize(out) // 1024} Ko)')
PY
