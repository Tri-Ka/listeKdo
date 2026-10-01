-- Prix, cadeaux à plusieurs, listes d'enfants, idées reçues, date de l'événement.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans ces colonnes et cette table, le site fonctionne : les fonctionnalités correspondantes sont masquées.

-- Prix indicatif d'une idée, et date à laquelle le propriétaire l'a marquée « reçue » (archivée).
ALTER TABLE `liste_noel`
  ADD `price` DECIMAL(10,2) DEFAULT NULL,
  ADD `received_at` DATETIME DEFAULT NULL;

-- Date de l'événement (compte à rebours).
ALTER TABLE `liste_user`
  ADD `event_date` DATE DEFAULT NULL;

-- Parents qui gèrent la liste d'un enfant (un enfant peut avoir plusieurs parents).
CREATE TABLE `liste_manager` (
  `child_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`child_id`, `user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Participants à un cadeau offert à plusieurs (montant facultatif).
CREATE TABLE `liste_participation` (
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`product_id`, `user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Listes d'enfants existantes : Olivia (id 123) et Mallory (id 141) sont gérées
-- par leurs parents Coline (id 13) et Etienne (id 1).
INSERT INTO `liste_manager` (`child_id`, `user_id`) VALUES
  (123, 13), (123, 1),
  (141, 13), (141, 1);
