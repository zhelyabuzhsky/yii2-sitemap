DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `slug` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

INSERT INTO `category` (`name`, `slug`)
VALUES
    ('Category 1', 'category_1'),
    ('Category 2', 'category_2'),
    ('Category 3', 'category_3');