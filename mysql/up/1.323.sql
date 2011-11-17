ALTER TABLE `gallery` ADD `description` text DEFAULT '';

ALTER TABLE `gallery_file` ADD `description` text DEFAULT '';

ALTER TABLE `gallery` ADD `file_path` varchar(255) DEFAULT '';