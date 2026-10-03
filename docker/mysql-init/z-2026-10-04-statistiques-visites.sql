CREATE TABLE `user_visit` (
  `user_id` int(11) NOT NULL,
  `visited_on` date NOT NULL,
  PRIMARY KEY (`user_id`, `visited_on`),
  KEY `visited_on` (`visited_on`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
