-- Liste privée : amis choisis par le propriétaire (ou un gestionnaire) qui peuvent quand même la voir.
-- Une ligne = un ami autorisé à voir une liste privée. Sans effet tant que la liste est publique.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette table, le site fonctionne : une liste privée reste visible seulement par ceux qui la gèrent.

CREATE TABLE `liste_viewer` (
  `list_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`list_id`, `user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
