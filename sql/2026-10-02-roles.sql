-- Rôles : « user » (par défaut) ou « admin ». Les admins ont accès à la page admin.php
-- (gestion des utilisateurs et des listes).
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le site fonctionne : la partie admin est simplement inaccessible.

ALTER TABLE `liste_user` ADD `role` varchar(20) NOT NULL DEFAULT 'user';

-- Etienne (id 1) devient administrateur.
UPDATE `liste_user` SET `role` = 'admin' WHERE `id` = 1 AND `nom` = 'Etienne';
