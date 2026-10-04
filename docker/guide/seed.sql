SET @pw = MD5('test');
INSERT INTO liste_user (nom, code, password, theme, event_date, message, secret_question, secret_answer, onboarding_seen_at, last_seen_notif) VALUES
('Léa', 'demo-lea', @pw, 'birthday', DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Merci à tous ! Pas besoin de tout offrir, un petit mot me fera déjà très plaisir 💛', 'test', 'test', NOW(), '2000-01-01');
SET @lea = LAST_INSERT_ID();
INSERT INTO liste_user (nom, code, password, theme, event_date, secret_question, secret_answer, onboarding_seen_at) VALUES ('Hugo', 'demo-hugo', @pw, 'noel', NULL, 'test','test', NOW());
SET @hugo = LAST_INSERT_ID();
INSERT INTO liste_user (nom, code, password, theme, event_date, secret_question, secret_answer, onboarding_seen_at) VALUES ('Inès', 'demo-ines', @pw, 'birthday', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'test','test', NOW());
SET @ines = LAST_INSERT_ID();
INSERT INTO liste_user (nom, code, password, theme, event_date, secret_question, secret_answer, onboarding_seen_at) VALUES ('Paul', 'demo-paul', @pw, 'mariage', DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'test','test', NOW());
SET @paul = LAST_INSERT_ID();
INSERT INTO liste_user (nom, code, password, theme, event_date, secret_question, secret_answer, onboarding_seen_at) VALUES ('Chloé', 'demo-chloe', @pw, 'wishlist', NULL, 'test','test', NOW());
SET @chloe = LAST_INSERT_ID();
INSERT INTO liste_user (nom, code, password, theme, event_date, onboarding_seen_at) VALUES ('Jules', 'demo-jules', '', 'naissance', DATE_ADD(CURDATE(), INTERVAL 60 DAY), NOW());
SET @jules = LAST_INSERT_ID();
INSERT INTO liste_manager (child_id, user_id) VALUES (@jules, @lea);
INSERT INTO user_friend (user_id, friend_code) VALUES
(@lea,'demo-hugo'),(@lea,'demo-ines'),(@lea,'demo-paul'),(@lea,'demo-chloe'),
(@hugo,'demo-lea'),(@ines,'demo-lea'),(@paul,'demo-lea'),(@chloe,'demo-lea'),
(@hugo,'demo-ines'),(@ines,'demo-hugo'),(@hugo,'demo-jules'),(@ines,'demo-jules');

INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price) VALUES
('Casque audio sans fil', '', 'demo-casque.png', 'https://example.com/casque', 'Plutôt en blanc ou en beige, avec réduction de bruit.', @lea, @hugo, DATE_SUB(NOW(), INTERVAL 9 DAY), 1, 89.00);
SET @casque = LAST_INSERT_ID();
INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price) VALUES
('Vélo de ville', '', 'demo-velo.png', 'https://example.com/velo', 'Taille M, avec un panier à l''avant si possible !', @lea, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY), 1, 320.00);
SET @velo = LAST_INSERT_ID();
INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price) VALUES
('Livres de cuisine du monde', '', 'demo-livre.png', '', 'Je complète ma collection petit à petit.', @lea, NULL, DATE_SUB(NOW(), INTERVAL 7 DAY), 0, NULL);
SET @livres = LAST_INSERT_ID();
INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price) VALUES
('Théière en fonte', '', 'demo-the.png', 'https://example.com/theiere', '', @lea, NULL, DATE_SUB(NOW(), INTERVAL 6 DAY), 0, 45.00);
SET @the = LAST_INSERT_ID();
INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price) VALUES
('Plaid tout doux', '', 'demo-plaid.png', 'https://example.com/plaid', 'Couleur terracotta ou vert sauge.', @lea, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY), 0, 35.00);
SET @plaid = LAST_INSERT_ID();
INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price) VALUES
('Château en briques', '', 'demo-lego.png', 'https://example.com/chateau', '', @lea, NULL, DATE_SUB(NOW(), INTERVAL 4 DAY), 0, 60.00);
SET @lego = LAST_INSERT_ID();
INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price, received_at) VALUES
('Jeu de société', '', 'demo-jeu.png', 'https://example.com/jeu', '', @lea, @ines, DATE_SUB(NOW(), INTERVAL 30 DAY), 0, 30.00, DATE_SUB(NOW(), INTERVAL 2 DAY));
INSERT INTO liste_noel (nom, image_url, file, link, description, user_id, gifted_by, created_at, favorite, price) VALUES
('Doudou lapin', '', 'demo-doudou.png', '', 'Tout doux, lavable en machine.', @jules, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY), 1, 25.00);

INSERT INTO liste_item (product_id, nom, position, gifted_by) VALUES
(@livres, 'Cuisine japonaise', 1, @ines), (@livres, 'Cuisine libanaise', 2, NULL), (@livres, 'Cuisine mexicaine', 3, @paul), (@livres, 'Cuisine indienne', 4, NULL);
INSERT INTO liste_participation (product_id, user_id, amount, created_at) VALUES
(@velo, @hugo, 100.00, DATE_SUB(NOW(), INTERVAL 3 DAY)), (@velo, @ines, 80.00, DATE_SUB(NOW(), INTERVAL 2 DAY)), (@velo, @chloe, 50.00, DATE_SUB(NOW(), INTERVAL 1 DAY));
INSERT INTO comment (user_id, product_id, content) VALUES
(@ines, @velo, 'Tu préfères quelle couleur ?'), (@lea, @velo, 'Vert ou bleu, j''adore les deux 😍'), (@hugo, @velo, 'Super idée, je participe !'),
(@paul, @the, 'Elle est magnifique !');
INSERT INTO reaction (product_id, type, user_id) VALUES
(@velo, 1, @hugo), (@velo, 2, @ines), (@velo, 1, @paul), (@casque, 1, @chloe), (@the, 3, @paul), (@plaid, 1, @ines), (@lego, 2, @hugo);
INSERT INTO notification (author_id, product_id, type, created_at) VALUES
(@lea, @lego, 2, DATE_SUB(NOW(), INTERVAL 4 DAY)), (@paul, @the, 1, DATE_SUB(NOW(), INTERVAL 3 HOUR)), (@hugo, @velo, 1, DATE_SUB(NOW(), INTERVAL 50 MINUTE)),
(@ines, @velo, 3, DATE_SUB(NOW(), INTERVAL 20 MINUTE)), (@chloe, @casque, 3, DATE_SUB(NOW(), INTERVAL 10 MINUTE));
