-- Lien de secours : un admin génère un lien à usage unique (valable 48 h) pour qu'une personne
-- qui a oublié son mot de passe en choisisse un nouveau. Seule l'empreinte du jeton est stockée.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans ces colonnes, le site fonctionne : le bouton « Lien de secours » est simplement masqué.

ALTER TABLE `liste_user`
  ADD `reset_token` varchar(40) DEFAULT NULL,
  ADD `reset_expires` datetime DEFAULT NULL;
