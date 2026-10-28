#!/usr/bin/env bash
# Envoie le site sur la VM de la Freebox et (re)démarre les conteneurs.
#
#   docker/freebox/deploy.sh                    # envoyer le code et redémarrer
#   docker/freebox/deploy.sh --uploads          # + envoyer listeKdo/uploads/ (sans rien effacer là-bas)
#   docker/freebox/deploy.sh --db export.sql    # + base à importer au premier démarrage
#
# Réglages dans docker/freebox/deploy.env (jamais dans git), voir deploy.env.example.
set -euo pipefail

HERE=$(cd "$(dirname "$0")" && pwd)
ROOT=$(cd "$HERE/../.." && pwd)
# deploy.env est lu ligne à ligne (CLÉ=valeur), pas exécuté : une valeur peut contenir des espaces
# (mot de passe d'application Gmail « abcd efgh ijkl mnop ») ou des guillemets.
if [ -f "$HERE/deploy.env" ]; then
    while IFS= read -r line; do
        key=${line%%=*}
        value=${line#*=}
        value=${value%$'\r'}
        case "$value" in \"*\" | \'*\') value=${value:1:${#value}-2} ;; esac
        case "$key" in
            KDO_SSH | KDO_DOMAIN | KDO_REMOTE_DIR | SMTP_USER | SMTP_PASSWORD) printf -v "$key" '%s' "$value" ;;
        esac
    done < <(grep -E '^[A-Z_]+=' "$HERE/deploy.env")
fi
: "${KDO_SSH:?KDO_SSH manquant (ex. : freebox@192.168.1.50), voir deploy.env.example}"
: "${KDO_DOMAIN:?KDO_DOMAIN manquant (ex. : ab12cd34.fbxos.fr), voir deploy.env.example}"
KDO_SITE_URL="https://$KDO_DOMAIN/"
REMOTE=${KDO_REMOTE_DIR:-listekdo}

UPLOADS=0
DB=''
while [ $# -gt 0 ]; do
    case "$1" in
        --uploads) UPLOADS=1 ;;
        --db) DB=${2:?fichier .sql attendu après --db}; shift ;;
        *) echo "Option inconnue : $1" >&2; exit 1 ;;
    esac
    shift
done
case "$KDO_DOMAIN" in
    *[!a-z0-9.-]* | '') echo "KDO_DOMAIN doit être un nom de domaine seul (ex. : ab12cd34.fbxos.fr)" >&2; exit 1 ;;
esac

echo "→ Dossiers sur $KDO_SSH:~/$REMOTE"
ssh "$KDO_SSH" "mkdir -p $REMOTE/listeKdo/uploads $REMOTE/listeKdo/cache $REMOTE/docker/freebox/db-init"

echo "→ Code du site"
# Mêmes exclusions que le FTP (.vscode/sftp.json) ; uploads/, cache/ et config.php vivent sur le serveur.
rsync -az --delete \
    --exclude '/config.php' --exclude '/uploads/' --exclude '/cache/' \
    --exclude '.git' --exclude '.DS_Store' \
    --exclude '/img/elements.png' --exclude '/img/elements-mariage.png' --exclude '/img/elements-wishlist.png' \
    --exclude '/img/skins-sources/' \
    "$ROOT/listeKdo/" "$KDO_SSH:$REMOTE/listeKdo/"

echo "→ Fichiers Docker"
ssh "$KDO_SSH" "mkdir -p $REMOTE/docker/php8"
rsync -az --delete "$ROOT/docker/php8/" "$KDO_SSH:$REMOTE/docker/php8/"
rsync -az --exclude 'deploy.env' --exclude '.env' --exclude 'config.php' --exclude 'db-init/' \
    "$HERE/" "$KDO_SSH:$REMOTE/docker/freebox/"

if [ "$UPLOADS" = 1 ]; then
    echo "→ Photos (listeKdo/uploads/)"
    rsync -az "$ROOT/listeKdo/uploads/" "$KDO_SSH:$REMOTE/listeKdo/uploads/"
fi

if [ -n "$DB" ]; then
    echo "→ Base à importer : $(basename "$DB")"
    rsync -az "$DB" "$KDO_SSH:$REMOTE/docker/freebox/db-init/datcharrye.sql"
fi

# Mots de passe de la base : créés une seule fois sur la VM, puis relus à chaque envoi.
if ! ssh "$KDO_SSH" "test -f $REMOTE/docker/freebox/.env"; then
    echo "→ Création de .env (mots de passe de la base)"
    # id -u / id -g sur la VM : Apache tournera avec le propriétaire des fichiers envoyés.
    ssh "$KDO_SSH" "umask 077; printf 'KDO_UID=%s\nKDO_GID=%s\nDB_ROOT_PASSWORD=%s\nDB_PASSWORD=%s\n' \
        \"\$(id -u)\" \"\$(id -g)\" $(openssl rand -hex 16) $(openssl rand -hex 16) > $REMOTE/docker/freebox/.env"
fi
# Nom de domaine du certificat HTTPS (Caddyfile) et compte Gmail des e-mails, remplacés à chaque envoi.
# Passés par l'entrée standard : le mot de passe n'apparaît pas dans la ligne de commande.
SMTP_PASSWORD=$(printf '%s' "${SMTP_PASSWORD:-}" | tr -d ' ')
printf 'KDO_DOMAIN=%s\nSMTP_USER=%s\nSMTP_PASSWORD=%s\n' "$KDO_DOMAIN" "${SMTP_USER:-}" "$SMTP_PASSWORD" \
    | ssh "$KDO_SSH" "cd $REMOTE/docker/freebox && sed -i '/^KDO_DOMAIN=/d; /^HTTP_PORT=/d; /^SMTP_/d' .env && cat >> .env"
DB_PASSWORD=$(ssh "$KDO_SSH" "sed -n 's/^DB_PASSWORD=//p' $REMOTE/docker/freebox/.env")

# config.php : modèle + mot de passe + adresse publique + clés reprises du config.php local.
local_key() {
    [ -f "$ROOT/listeKdo/config.php" ] || return 0
    sed -n "s/^[[:space:]]*\\\$$1[[:space:]]*=[[:space:]]*'\\([^']*\\)';.*/\\1/p" "$ROOT/listeKdo/config.php" | head -n 1
}
escape() { printf '%s' "$1" | sed 's/[\/&|]/\\&/g'; }
echo "→ config.php"
sed -e "s|__DB_PASSWORD__|$(escape "$DB_PASSWORD")|" \
    -e "s|__SITE_URL__|$(escape "$KDO_SITE_URL")|" \
    -e "s|__MAIL_FROM__|$(escape "${SMTP_USER:-}")|" \
    -e "s|__SCRAPERAPI_KEY__|$(escape "$(local_key _scraperapi_key)")|" \
    -e "s|__WORKER_URL__|$(escape "$(local_key _metadata_worker_url)")|" \
    -e "s|__WORKER_TOKEN__|$(escape "$(local_key _metadata_worker_token)")|" \
    "$HERE/config.php.example" \
    | ssh "$KDO_SSH" "umask 077; cat > $REMOTE/docker/freebox/config.php"

echo "→ Démarrage (la première fois, la construction de l'image prend quelques minutes)"
# restart : les fichiers montés seuls (Caddyfile, apache-site.conf) remplacés par rsync ne sont relus qu'au redémarrage.
ssh "$KDO_SSH" "cd $REMOTE/docker/freebox && docker compose up -d --build && docker compose restart php caddy && docker compose ps"

echo "✓ En ligne : $KDO_SITE_URL"
