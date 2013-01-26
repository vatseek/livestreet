CREATE TABLE `category` (
  `category_id` INT(11) UNSIGNED AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `thumb` VARCHAR(255),
  `url` VARCHAR(100),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY (`url`),
  CONSTRAINT `category_fk1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `film` (
  `film_id` INT(11) UNSIGNED AUTO_INCREMENT ,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `category_id` INT(11) UNSIGNED NOT NULL,
  `create_at` DATETIME,
  `name` VARCHAR(255) NOT NULL,
  `origin_name` VARCHAR(255),
  `slogan` VARCHAR(255),
  `description` TEXT,
  `url` VARCHAR(100),
  `thumb` VARCHAR(255),
  `premiere` datetime,
  `duration` SMALLINT(4),
  `hash` VARCHAR (32),
  PRIMARY KEY (`film_id`),
  UNIQUE KEY (`url`),
  UNIQUE KEY (`hash`),
  CONSTRAINT `film_fk1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `film_fk2` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `screenshot`;

CREATE TABLE `screenshot` (
  `screenshot_id` INT(11) UNSIGNED AUTO_INCREMENT ,
  `film_id` INT(11) UNSIGNED NOT NULL,
  `path_filename` VARCHAR(255),
  `description` VARCHAR(255),
  PRIMARY KEY (`screenshot_id`),
  CONSTRAINT `screenshot_fk1` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `link` (
  `link_id` INT(11) UNSIGNED AUTO_INCREMENT,
  `film_id` INT(11) UNSIGNED NOT NULL,
  `source` TEXT,
  `title` VARCHAR(255),
  PRIMARY KEY (`link_id`),
  CONSTRAINT `link_fk1` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;