ALTER TABLE `gallery` ADD `description` text;

ALTER TABLE `gallery_file` ADD `description` text;

ALTER TABLE `gallery` ADD `file_path` varchar(255) DEFAULT '';
