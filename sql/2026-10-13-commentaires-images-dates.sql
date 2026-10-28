-- Date et image (photo ou GIF) des commentaires.
-- À exécuter une seule fois dans phpMyAdmin, base datcharrye, onglet SQL.
ALTER TABLE `comment`
  ADD `created_at` datetime DEFAULT NULL,
  ADD `image` varchar(64) DEFAULT NULL;

-- Dates des anciens commentaires, retrouvées dans leurs notifications (type 1 = commentaire) :
-- le n-ième commentaire d'une personne sur une idée reçoit la date de sa n-ième notification.
-- Approximatif si des commentaires ont été supprimés ; sans notification, la date reste vide et n'est pas affichée.
UPDATE `comment` c
INNER JOIN (
  SELECT a.id, (SELECT COUNT(*) FROM `comment` b WHERE b.user_id = a.user_id AND b.product_id = a.product_id AND b.id < a.id) AS rk
  FROM `comment` a
) r ON r.id = c.id
INNER JOIN (
  SELECT n.author_id, n.product_id, n.created_at,
    (SELECT COUNT(*) FROM `notification` m WHERE m.type = 1 AND m.author_id = n.author_id AND m.product_id = n.product_id AND m.id < n.id) AS rk
  FROM `notification` n
  WHERE n.type = 1
) t ON t.author_id = c.user_id AND t.product_id = c.product_id AND t.rk = r.rk
SET c.created_at = t.created_at
WHERE c.created_at IS NULL;
