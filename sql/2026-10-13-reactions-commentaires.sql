-- Réactions aux commentaires : un emoji au choix par personne et par commentaire, comme dans une messagerie.
-- À exécuter une seule fois dans phpMyAdmin, base datcharrye, onglet SQL.
CREATE TABLE `comment_reaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `emoji` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_user` (`comment_id`, `user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
