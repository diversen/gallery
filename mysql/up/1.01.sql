CREATE TABLE `gallery` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

CREATE TABLE `gallery_file` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) DEFAULT '',
  `gallery_id` int(10) DEFAULT NULL,
  `file_name` varchar(256) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;