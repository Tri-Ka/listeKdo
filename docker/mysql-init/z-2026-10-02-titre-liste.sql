-- Titre de la liste (facultatif), modifiable dans « Paramètres de la liste » (roue dentée en haut à droite).
-- Exemple : « Les 40 ans d'Etienne ». Vide : titre par défaut du thème (« Anniversaire de Etienne »…).
-- Il remplace ce titre dans l'onglet du navigateur, la barre du haut et les messages de partage.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le site fonctionne : le champ est simplement masqué.

ALTER TABLE `liste_user` ADD `list_title` varchar(120) DEFAULT NULL;
