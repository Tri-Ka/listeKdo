-- Collections : une idée peut contenir plusieurs éléments (ex. les tomes d'une BD),
-- que les proches réservent un par un.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette table, le site fonctionne : l'option « collection » est simplement masquée.

CREATE TABLE `liste_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `gifted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
