-- Boutique : cadres de photo et effets de compte à rebours (lib/skins.php : frames(), countdown_effects()).
-- Les achats vont dans user_skin (clés « frame/… » et « countdown/… ») ; ces colonnes gardent l'article porté.
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL,
-- APRÈS sql/2026-10-03-gemmes-boutique.sql. Sans ces colonnes, les deux onglets de la boutique sont masqués.

ALTER TABLE `liste_user` ADD `frame` varchar(30) DEFAULT NULL, ADD `countdown_fx` varchar(30) DEFAULT NULL;
