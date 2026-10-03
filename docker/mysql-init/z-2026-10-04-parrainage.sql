-- Parrainage : chaque compte a un code (calculé, pas stocké), à saisir à l'inscription.
-- Le filleul reçoit un bonus de bienvenue ; le parrain gagne ses gemmes quand le filleul ajoute sa première idée.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le parrainage est simplement masqué.

ALTER TABLE `liste_user` ADD `referred_by` int(11) DEFAULT NULL;
