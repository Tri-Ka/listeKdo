-- Réglages du site modifiables dans l'administration (pour l'instant : gemmes gagnées par action).
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette table, les gemmes par action utilisent les valeurs par défaut (gem_actions() dans lib/skins.php).

CREATE TABLE `kdo_setting` (
  `name` varchar(60) NOT NULL,
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
