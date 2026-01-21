-- Script d'initialisation de la base de données listeKdo
-- Ce script sera exécuté automatiquement lors de la création du conteneur MySQL

CREATE DATABASE IF NOT EXISTS listekdo CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Ensure the application user can connect from any container/IP on the Docker network
DROP USER IF EXISTS 'listekdo_user'@'localhost';
CREATE USER IF NOT EXISTS 'listekdo_user'@'%' IDENTIFIED BY 'listekdo_pass';
ALTER USER 'listekdo_user'@'%' IDENTIFIED BY 'listekdo_pass';
GRANT ALL PRIVILEGES ON listekdo.* TO 'listekdo_user'@'%';
FLUSH PRIVILEGES;

USE listekdo;

-- Table des utilisateurs (correspond à liste_user)
CREATE TABLE IF NOT EXISTS liste_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    code VARCHAR(32) NOT NULL,
    password VARCHAR(255) DEFAULT NULL,
    theme VARCHAR(50) DEFAULT 'noel',
    pictureFile VARCHAR(255) DEFAULT NULL,
    pictureFileUrl VARCHAR(500) DEFAULT NULL,
    googleId VARCHAR(255) DEFAULT NULL,
    last_seen_notif DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY ux_liste_user_code (code),
    UNIQUE KEY ux_liste_user_nom (nom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table des idées/cadeaux (correspond à liste_noel)
CREATE TABLE IF NOT EXISTS liste_noel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500) DEFAULT NULL,
    link VARCHAR(500) DEFAULT NULL,
    file VARCHAR(255) DEFAULT NULL,
    gifted_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_liste_noel_user FOREIGN KEY (user_id) REFERENCES liste_user(id) ON DELETE CASCADE,
    CONSTRAINT fk_liste_noel_gifted FOREIGN KEY (gifted_by) REFERENCES liste_user(id) ON DELETE SET NULL,
    INDEX idx_liste_noel_user_id (user_id),
    INDEX idx_liste_noel_gifted_by (gifted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table des commentaires (correspond à comment)
CREATE TABLE IF NOT EXISTS comment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comment_product FOREIGN KEY (product_id) REFERENCES liste_noel(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES liste_user(id) ON DELETE CASCADE,
    INDEX idx_comment_product_id (product_id),
    INDEX idx_comment_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table des réactions (correspond à reaction)
CREATE TABLE IF NOT EXISTS reaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    type TINYINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reaction_product FOREIGN KEY (product_id) REFERENCES liste_noel(id) ON DELETE CASCADE,
    CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES liste_user(id) ON DELETE CASCADE,
    UNIQUE KEY ux_reaction_product_user (product_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table des notifications (correspond à notification)
CREATE TABLE IF NOT EXISTS notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    product_id INT NOT NULL,
    type TINYINT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_author FOREIGN KEY (author_id) REFERENCES liste_user(id) ON DELETE CASCADE,
    CONSTRAINT fk_notification_product FOREIGN KEY (product_id) REFERENCES liste_noel(id) ON DELETE CASCADE,
    INDEX idx_notification_author (author_id),
    INDEX idx_notification_product (product_id),
    INDEX idx_notification_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table des relations d'amitié (correspond à user_friend)
CREATE TABLE IF NOT EXISTS user_friend (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_code VARCHAR(32) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_friend_user FOREIGN KEY (user_id) REFERENCES liste_user(id) ON DELETE CASCADE,
    UNIQUE KEY ux_user_friend (user_id, friend_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
