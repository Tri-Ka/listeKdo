-- Statistiques : visiteurs uniques par jour pour l'administration.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Une visite est enregistrée au premier chargement connecté de chaque journée ; aucune IP, URL ou donnée
-- d'appareil n'est conservée. Les graphiques de connexions démarrent après cette installation.

CREATE TABLE `user_visit` (
  `user_id` int(11) NOT NULL,
  `visited_on` date NOT NULL,
  PRIMARY KEY (`user_id`, `visited_on`),
  KEY `visited_on` (`visited_on`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
