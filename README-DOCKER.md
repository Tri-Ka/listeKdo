# ListeKdo - Configuration Docker

Ce projet utilise Docker pour créer un environnement de développement avec Apache, PHP 4.4 et MySQL 5.7.

## ⚠️ Avertissement

**PHP 4.4 est une version très ancienne et n'est plus supportée depuis 2008.** Cette configuration Docker est destinée uniquement à maintenir un site legacy existant. Il est fortement recommandé de migrer vers une version moderne de PHP (7.4, 8.0+).

## Prérequis

- Docker et Docker Compose installés
- WSL2 (si vous êtes sous Windows)

## Structure Docker

```
listeKdo/
├── Dockerfile                   # Image PHP 4.4 avec Apache
├── docker-compose.yml           # Orchestration des services
├── .env                         # Variables d'environnement
├── docker/
│   ├── apache-site.conf        # Configuration Apache
│   ├── init.sql                # Script d'initialisation MySQL
│   └── sessions/               # Dossier pour les sessions PHP
└── ...
```

## Services

- **web** : Apache 2.x avec PHP 4.4.9 (port 8080)
- **db** : MySQL 5.7 (port 3306)
- **phpmyadmin** : Interface web pour MySQL (port 8081)

## Installation

### 1. Créer le dossier sessions

```bash
mkdir -p docker/sessions
```

### 2. Configurer les variables d'environnement

Le fichier `.env` contient déjà des valeurs par défaut. Vous pouvez les modifier selon vos besoins :

```env
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=listekdo
MYSQL_USER=listekdo_user
MYSQL_PASSWORD=listekdo_pass
```

### 3. Construire et démarrer les conteneurs

```bash
docker-compose up -d --build
```

La première construction peut prendre 10-15 minutes car PHP 4.4.9 doit être compilé depuis les sources.

### 4. Vérifier que les services sont démarrés

```bash
docker-compose ps
```

## Accès aux services

- **Site web** : http://localhost:8080
- **PhpMyAdmin** : http://localhost:8081
  - Serveur : `db`
  - Utilisateur : `listekdo_user`
  - Mot de passe : `listekdo_pass`

## Commandes utiles

### Démarrer les conteneurs
```bash
docker-compose up -d
```

### Arrêter les conteneurs
```bash
docker-compose down
```

### Voir les logs
```bash
docker-compose logs -f web
docker-compose logs -f db
```

### Accéder au conteneur web
```bash
docker exec -it listekdo_web bash
```

### Accéder au conteneur MySQL
```bash
docker exec -it listekdo_db mysql -ulistekdo_user -plistekdo_pass listekdo
```

### Redémarrer un service
```bash
docker-compose restart web
```

### Reconstruire les images
```bash
docker-compose up -d --build --force-recreate
```

## Configuration de la base de données

Le fichier `config.php` doit être mis à jour pour utiliser les credentials Docker :

```php
<?php
$db_host = 'db';  // Nom du service dans docker-compose.yml
$db_name = 'listekdo';
$db_user = 'listekdo_user';
$db_pass = 'listekdo_pass';
?>
```

## Base de données

Le script `docker/init.sql` crée automatiquement :
- La base de données `listekdo`
- Les tables nécessaires (users, objects, friends, comments, reactions, notifications)
- Un utilisateur de test :
  - Email : `test@example.com`
  - Mot de passe : `password`

## Résolution de problèmes

### Le site ne s'affiche pas

1. Vérifiez que les conteneurs sont démarrés :
   ```bash
   docker-compose ps
   ```

2. Consultez les logs :
   ```bash
   docker-compose logs web
   ```

### Erreurs de connexion MySQL

1. Vérifiez que le conteneur MySQL est démarré
2. Assurez-vous que `config.php` utilise `db` comme nom d'hôte
3. Vérifiez les credentials dans `.env`

### Erreurs de permissions

```bash
# Ajuster les permissions du dossier sessions
chmod -R 777 docker/sessions
```

### Réinitialiser la base de données

```bash
docker-compose down -v  # Supprime les volumes
docker-compose up -d    # Recrée tout
```

## Migration vers une version moderne de PHP

Il est **fortement recommandé** de migrer ce projet vers PHP 7.4+ ou PHP 8.x. Voici les principales incompatibilités à gérer :

1. **register_globals** : Remplacer par `$_GET`, `$_POST`, `$_SESSION`
2. **magic_quotes** : Retirer, utiliser des requêtes préparées
3. **mysql_*** : Migrer vers MySQLi ou PDO
4. **Sécurité** : Implémenter le hashing moderne des mots de passe (password_hash)

## Support

Ce setup Docker est fourni tel quel pour maintenir un site legacy. Pour toute question, consultez :
- [Documentation Docker](https://docs.docker.com/)
- [Documentation PHP](https://www.php.net/manual/fr/)
- [Documentation MySQL](https://dev.mysql.com/doc/)

## Licence

Ce projet hérite de la licence du code source original de listeKdo.
