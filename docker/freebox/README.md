# ListeKdo sur la Freebox

Le site tourne dans la **VM `listekdo` de la Freebox Delta**, avec Docker : Caddy (HTTPS), PHP 8.3 + Apache
(`docker/php8/`) et MariaDB 10.6. Le même code tourne encore en PHP 4 chez Free (voir « PHP 4 et PHP 8 » dans `CLAUDE.md`).

```
Internet ──► Freebox 82.67.74.202:80/443 ──redirection──► VM 192.168.1.29 ──► Caddy (HTTPS) ──► Apache + PHP 8 ──► MariaDB
```

| Élément | Valeur |
|---|---|
| Site | https://liste-kdo.fr/ (site à la racine ; `/listeKdo/…`, `www.`, l'IP, le HTTP et `listekdo.freeboxos.fr` redirigent ici) |
| Nom | `liste-kdo.fr`, acheté chez OVH (zone DNS : `A` et `www A` → 82.67.74.202, **pas d'AAAA**), `KDO_DOMAIN` dans `deploy.env` |
| Ancien nom | `listekdo.freeboxos.fr` (nom de la Freebox) : son IPv6 est celle de la Freebox, qui ne redirige aucun port en IPv6, donc injoignable en 4G. Gardé en redirection (`/download/` servi tel quel pour l'extension 1.5.0) |
| Certificat | Let's Encrypt, obtenu et renouvelé par Caddy (`Caddyfile`, volume `caddy-data`) |
| VM | `listekdo` : Debian 12 ARM64, 1 cœur, 512 Mo + 1 Go de swap, disque 42 Go (`Freebox/VMs/listekdo.qcow2`) |
| SSH | `ssh freebox@192.168.1.29` (clé `~/.ssh/id_ed25519` de WSL) |
| Freebox OS | bail DHCP fixe 192.168.1.29 ; redirections TCP 80 et 443 → 192.168.1.29 (« listekdo web », « listekdo https ») |
| Dossier sur la VM | `~/listekdo/` : `listeKdo/` (site, `uploads/`, `cache/`) et `docker/` |

La Freebox ne donne que **1 Go et 2 cœurs à l'ensemble des VM**, dont la moitié à la VM Plex : d'où les 512 Mo,
les réglages économes de MariaDB (`compose.yml`) et d'Apache (`docker/php8/apache-site.conf`).

## Mettre à jour le site

Depuis WSL, à la racine du projet (`~/projects/sftp`) :

```bash
docker/freebox/deploy.sh
```

Réglages dans `docker/freebox/deploy.env` (jamais dans git, modèle : `deploy.env.example`). Le script envoie
`listeKdo/` par rsync (sans `config.php`, `uploads/`, `cache/` ni les planches sources), régénère le `config.php`
de la VM (mot de passe de la base, adresse publique, clés reprises de `listeKdo/config.php`) et relance les
conteneurs. Les photos, le cache et la base restent sur la VM.

Options : `--uploads` envoie `listeKdo/uploads/` (sans rien effacer sur la VM), `--db fichier.sql` dépose une base
à importer au premier démarrage d'un volume vide.

## Administration sur la VM

```bash
ssh freebox@192.168.1.29
cd listekdo/docker/freebox
docker compose logs -f php                          # journaux Apache et erreurs PHP
docker compose restart                              # redémarrer
docker compose --profile admin up -d phpmyadmin     # phpMyAdmin sur http://192.168.1.29:8091/
docker compose --profile admin stop phpmyadmin      # …et l'arrêter après usage
grep DB_ROOT_PASSWORD .env                          # identifiant root de phpMyAdmin
```

Ne **jamais** rediriger le port 8091 sur la Freebox : phpMyAdmin doit rester sur le réseau local.
Les scripts de `sql/` se passent dans ce phpMyAdmin.

### Sauvegarder la base

```bash
cd ~/listekdo/docker/freebox
docker compose exec -T mysql sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" datcharrye' > ~/sauvegarde-$(date +%F).sql
```

### Repartir d'un export de Free

```bash
# Sur la VM : base vide, puis import de l'export au redémarrage
cd ~/listekdo/docker/freebox && docker compose down -v && rm -f db-init/*.sql
# Depuis WSL :
rsync -az export.sql freebox@192.168.1.29:listekdo/docker/freebox/db-init/datcharrye.sql
ssh freebox@192.168.1.29 'cd listekdo/docker/freebox && docker compose up -d'
```

## Recréer la VM de zéro

1. Freebox OS → VMs → *Ajouter une VM* : Debian 12, 1 cœur, 512 Mo, utilisateur `freebox`, clé SSH publique de WSL.
2. Bail DHCP fixe et redirection du port 80 (*Paramètres de la Freebox* → *DHCP* et *Gestion des ports*).
3. Dans la VM : 1 Go de swap (`/swapfile`), puis `curl -fsSL https://get.docker.com | sudo sh`,
   `sudo usermod -aG docker freebox` et `sudo apt-get install -y rsync`.
4. Depuis WSL : `docker/freebox/deploy.sh --uploads --db export.sql`.

## À savoir

- **Changer de nom de domaine** : faire pointer le nouveau nom vers 82.67.74.202, changer `KDO_DOMAIN` dans
  `deploy.env`, puis `docker/freebox/deploy.sh` : Caddy obtient le certificat seul, et les liens du site suivent.
- **Deux sites, deux bases** : tant que `datcharrye.free.fr` reste ouvert, ce qui s'y fait n'arrive pas sur la Freebox.
- **Extension Chrome** : elle ne connaît que `datcharrye.free.fr` (`manifest.json`, `popup.html`, `update.js`).
