-- Dernière visite : mise à jour quand un utilisateur connecté charge le site (au plus toutes les 10 minutes).
-- Affichée et triable dans l'administration (onglet Utilisateurs).
-- À exécuter une seule fois dans phpMyAdmin (http://sql.free.fr/phpMyAdmin/), base datcharrye, onglet SQL.
-- Sans cette colonne, le site fonctionne : la colonne est simplement masquée dans l'admin.

ALTER TABLE `liste_user` ADD `last_seen_at` datetime DEFAULT NULL;

-- Point de départ approximatif pour les comptes existants : la dernière fois qu'ils ont ouvert
-- leurs notifications ou ajouté une idée (la vraie date s'affichera à leur prochaine visite).
UPDATE `liste_user` u SET u.`last_seen_at` = NULLIF(GREATEST(
    COALESCE(u.`last_seen_notif`, '1970-01-01'),
    COALESCE((SELECT MAX(n.`created_at`) FROM `liste_noel` n WHERE n.`user_id` = u.`id`), '1970-01-01')
), '1970-01-01');
