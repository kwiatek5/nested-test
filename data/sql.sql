CREATE TABLE `tree` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `title` varchar(100) COLLATE utf8_polish_ci NOT NULL,
 `lft` int(11) NOT NULL,
 `rgt` int(11) NOT NULL,
 PRIMARY KEY (`id`),
 KEY `lft` (`lft`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;
