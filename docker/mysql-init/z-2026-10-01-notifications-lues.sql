-- État lu / non lu de chaque notification, par utilisateur.
-- Sans ligne ici, une notification est lue si elle est antérieure à liste_user.last_seen_notif
-- (« Tout marquer comme lu »), non lue sinon.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette table, le site garde l'ancien fonctionnement (ouvrir les notifications marque tout comme lu).

CREATE TABLE `notification_state` (
  `user_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`, `notification_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
