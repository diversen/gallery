ALTER TABLE `gallery` ADD `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `gallery` ADD `user_id` int(10) NOT NULL;