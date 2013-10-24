-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.5.16 - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Version:             8.0.0.4396
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table test_nestedtree.tree
CREATE TABLE IF NOT EXISTS `tree` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_polish_ci NOT NULL DEFAULT '',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci;

-- Dumping data for table test_nestedtree.tree: ~10 rows (approximately)
/*!40000 ALTER TABLE `tree` DISABLE KEYS */;
INSERT INTO `tree` (`id`, `lft`, `rgt`, `name`, `parent_id`) VALUES
	(1, 1, 22, 'root', 0),
	(2, 4, 15, 'Windows', 1),
	(3, 16, 19, 'Linux', 1),
	(4, 20, 21, 'MacOS', 1),
	(5, 5, 12, 'Office', 2),
	(6, 13, 14, 'InternetExplorer', 2),
	(7, 17, 18, 'Iceweasel', 3),
	(8, 6, 7, 'Word', 5),
	(9, 8, 9, 'Excel', 5),
	(10, 10, 11, 'PowerPoint', 5);
/*!40000 ALTER TABLE `tree` ENABLE KEYS */;


-- Dumping structure for table test_nestedtree.tree_copy
CREATE TABLE IF NOT EXISTS `tree_copy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lft` int(10) unsigned NOT NULL,
  `rgt` int(10) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_polish_ci NOT NULL DEFAULT '',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COLLATE=utf8_polish_ci ROW_FORMAT=COMPACT;

-- Dumping data for table test_nestedtree.tree_copy: ~10 rows (approximately)
/*!40000 ALTER TABLE `tree_copy` DISABLE KEYS */;
INSERT INTO `tree_copy` (`id`, `lft`, `rgt`, `name`, `parent_id`) VALUES
	(1, 1, 22, 'root', 0),
	(2, 4, 15, 'Windows', 1),
	(3, 16, 19, 'Linux', 1),
	(4, 20, 21, 'MacOS', 1),
	(5, 5, 12, 'Office', 2),
	(6, 13, 14, 'InternetExplorer', 2),
	(7, 17, 18, 'Iceweasel', 3),
	(8, 6, 7, 'Word', 5),
	(9, 8, 9, 'Excel', 5),
	(10, 10, 11, 'PowerPoint', 5);
/*!40000 ALTER TABLE `tree_copy` ENABLE KEYS */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
