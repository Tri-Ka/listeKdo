-- Gemmes et boutique d'habillages (skins).
-- Les badges rapportent des gemmes (colonne badge.gems ; 0 = valeur automatique selon le type et le niveau),
-- dépensées dans la boutique pour débloquer des habillages de liste (lib/skins.php).
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL,
-- APRÈS sql/2026-10-03-badges.sql. Sans ces colonnes et cette table, la boutique est simplement masquée.

ALTER TABLE `badge` ADD `gems` int(11) NOT NULL DEFAULT 0;

ALTER TABLE `liste_user` ADD `skin` varchar(30) DEFAULT NULL;

CREATE TABLE `user_skin` (
  `user_id` int(11) NOT NULL,
  `skin` varchar(30) NOT NULL,
  `price` int(11) NOT NULL DEFAULT 0,
  `bought_at` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`, `skin`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
