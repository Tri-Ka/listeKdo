-- Badges et trophées (paramétrables dans l'administration, onglet « Badges »).
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Les badges par défaut ne sont PAS insérés ici : le site les installe lui-même (badge_fixtures() dans
-- lib/badges.php) à la première ouverture de l'onglet « Badges », pour que les accents et les emojis
-- passent par la même connexion que le reste des données.
-- Sans ces tables, le site fonctionne : les badges sont simplement masqués.

CREATE TABLE `badge` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(60) NOT NULL,
  `kind` varchar(10) NOT NULL DEFAULT 'badge',
  `name` varchar(80) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `emoji` varchar(32) NOT NULL DEFAULT '',
  `tier` varchar(12) NOT NULL DEFAULT 'bronze',
  `metric` varchar(40) NOT NULL,
  `threshold` int(11) NOT NULL DEFAULT 1,
  `secret` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `user_badge` (
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` datetime DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`, `badge_id`),
  KEY `badge_id` (`badge_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
