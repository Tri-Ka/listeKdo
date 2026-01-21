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

-- Jeu de données de démonstration enrichi
INSERT INTO liste_user (id, nom, code, password, theme, pictureFile, pictureFileUrl, last_seen_notif, created_at, updated_at)
VALUES
    (1, 'demo', 'c514c91e4ed341f263e458d44b3bb0a7', 'fe01ce2a7fbac8fafaed7c982a04e229', 'noel', NULL, 'https://i.pravatar.cc/150?img=5', '2025-11-15 09:00:00', '2023-12-01 10:00:00', '2025-11-15 09:00:00'),
    (2, 'Alice', 'f7b0919058594559bf0bb9b59dc5ea79', '10cf1fdf6ad958eeffa9853f6885cec9', 'birthday', NULL, 'https://i.pravatar.cc/150?img=10', '2025-11-18 07:35:00', '2024-05-12 14:30:00', '2025-11-18 07:35:00'),
    (3, 'Bob', '95dde775af7229816acd67124bb98f01', 'c87a8ca60f0891b79d192fa86f019916', 'naissance', NULL, 'https://i.pravatar.cc/150?img=12', '2025-11-16 18:10:00', '2024-06-22 09:10:00', '2025-11-16 18:10:00'),
    (4, 'Carol', '9b1682f53c60aa5bdbb1caaef65766b0', '276405502bd6a2c0a45c17b8745ecae0', 'noel', NULL, 'https://i.pravatar.cc/150?img=32', '2025-11-17 21:00:00', '2024-07-05 16:45:00', '2025-11-17 21:00:00'),
    (5, 'Dave', 'd42d792029931be007f9957d63476cef', 'a7a2ea629a07abd2e4f56dab12f915f2', 'birthday', NULL, 'https://i.pravatar.cc/150?img=41', '2025-11-14 08:50:00', '2024-08-18 11:05:00', '2025-11-14 08:50:00')
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom),
    password = VALUES(password),
    theme = VALUES(theme),
    pictureFile = VALUES(pictureFile),
    pictureFileUrl = VALUES(pictureFileUrl),
    last_seen_notif = VALUES(last_seen_notif),
    updated_at = VALUES(updated_at);

INSERT INTO user_friend (user_id, friend_code, created_at)
VALUES
    (1, 'f7b0919058594559bf0bb9b59dc5ea79', '2025-11-10 09:00:00'),
    (2, '95dde775af7229816acd67124bb98f01', '2025-11-12 10:15:00'),
    (2, '9b1682f53c60aa5bdbb1caaef65766b0', '2025-11-12 10:16:00'),
    (3, 'f7b0919058594559bf0bb9b59dc5ea79', '2025-11-12 11:05:00'),
    (4, 'f7b0919058594559bf0bb9b59dc5ea79', '2025-11-13 08:20:00'),
    (4, 'd42d792029931be007f9957d63476cef', '2025-11-13 08:25:00'),
    (5, 'f7b0919058594559bf0bb9b59dc5ea79', '2025-11-13 09:15:00')
ON DUPLICATE KEY UPDATE
    created_at = VALUES(created_at);

INSERT INTO liste_noel (id, user_id, nom, description, image_url, link, file, gifted_by, created_at, updated_at)
VALUES
    (1, 2, 'Portable turntable', 'Je veux relancer les soirées vinyle à la maison.', 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=640', 'https://www.amazon.fr/slp/platine-vinyle', NULL, NULL, '2025-10-30 18:00:00', '2025-11-15 08:00:00'),
    (2, 2, 'Vintage city bike', 'Un vélo confortable pour les trajets quotidiens, avec panier avant.', 'https://images.unsplash.com/photo-1498685297329-42e44ab4446f?w=640', 'https://www.decathlon.fr/browse/c0-tous-les-sports/c1-velo/c4-velo-de-ville/_/N-1tdg1v2', NULL, 3, '2025-10-28 07:45:00', '2025-11-16 19:10:00'),
    (3, 3, 'Smart baby monitor', 'On dort mieux avec un babyphone connecté et fiable.', 'https://images.unsplash.com/photo-1519681393784-d120267933ba?w=640', 'https://www.amazon.fr/s?k=babyphone+connecte', NULL, 4, '2025-10-27 21:00:00', '2025-11-16 18:30:00'),
    (4, 4, 'Ski weekend voucher', 'Deux jours dans les Alpes pour profiter de la poudreuse.', 'https://images.unsplash.com/photo-1453743327117-664e2bf4e951?w=640', 'https://www.voyageski.com', NULL, NULL, '2025-10-25 12:00:00', '2025-11-14 20:10:00'),
    (5, 5, 'Kamado barbecue set', 'Pour tester de nouvelles recettes low and slow tout l''été.', 'https://images.unsplash.com/photo-1504753793650-d4a2b783c15e?w=640', 'https://www.weber.com/fr/fr/barbecues/', NULL, NULL, '2025-10-24 19:30:00', '2025-11-14 08:30:00'),
    (6, 1, 'Noise-canceling headphones', 'Pour mieux me concentrer au bureau et pendant les voyages.', 'https://images.unsplash.com/photo-1517142089942-ba376ce32a0b?w=640', 'https://www.boulanger.com/c/casque-audio/b/bose', NULL, 2, '2025-10-20 08:00:00', '2025-11-13 17:45:00')
ON DUPLICATE KEY UPDATE
    nom = VALUES(nom),
    description = VALUES(description),
    image_url = VALUES(image_url),
    link = VALUES(link),
    file = VALUES(file),
    gifted_by = VALUES(gifted_by),
    updated_at = VALUES(updated_at);

INSERT INTO comment (id, content, product_id, user_id, created_at)
VALUES
    (1, 'Parfait pour les soirées chez toi, j''apporte les vinyles !', 1, 3, '2025-11-15 08:15:00'),
    (2, 'Je peux t''offrir un casque et le panier assorti.', 2, 4, '2025-11-16 19:20:00'),
    (3, 'On prend celui avec caméra grand angle, tu vas aimer.', 3, 2, '2025-11-16 18:35:00'),
    (4, 'Prenons le même week-end que d''habitude, je m''occupe du chalet.', 4, 5, '2025-11-14 20:20:00')
ON DUPLICATE KEY UPDATE
    content = VALUES(content),
    user_id = VALUES(user_id),
    created_at = VALUES(created_at);

INSERT INTO reaction (id, product_id, user_id, type, created_at)
VALUES
    (1, 1, 3, 2, '2025-11-15 09:10:00'),
    (2, 1, 4, 1, '2025-11-15 09:12:00'),
    (3, 2, 3, 5, '2025-11-16 19:25:00'),
    (4, 3, 2, 1, '2025-11-16 18:40:00'),
    (5, 4, 5, 3, '2025-11-14 20:25:00')
ON DUPLICATE KEY UPDATE
    type = VALUES(type),
    created_at = VALUES(created_at);

INSERT INTO notification (id, author_id, product_id, type, created_at)
VALUES
    (1, 3, 2, 1, '2025-11-16 19:15:00'),
    (2, 4, 3, 2, '2025-11-16 18:45:00'),
    (3, 2, 6, 1, '2025-11-15 09:30:00'),
    (4, 5, 4, 2, '2025-11-14 20:30:00')
ON DUPLICATE KEY UPDATE
    author_id = VALUES(author_id),
    product_id = VALUES(product_id),
    type = VALUES(type),
    created_at = VALUES(created_at);
