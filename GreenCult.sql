CREATE TABLE `follows` (
  `following_user_id` integer,
  `followed_club_id` integer,
  `created_at` timestamp
);

CREATE TABLE `users` (
  `id` integer PRIMARY KEY,
  `username` varchar(255),
  `status` varchar(255),
  `language` varchar(255),
  `last_activity` timestamp,
  `role` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `clubs` (
  `id` integer PRIMARY KEY,
  `name` varchar(255),
  `status` varchar(255),
  `country` varchar(255),
  `city` varchar(255),
  `description` varchar(255),
  `created_at` timestamp
);

CREATE TABLE `comments` (
  `id` integer PRIMARY KEY,
  `title` varchar(255),
  `user_id` integer,
  `club_id` integer,
  `created_at` timestamp
);

CREATE TABLE `log` (
  `id` integer PRIMARY KEY,
  `created_at` timestamp,
  `entity` varchar(255),
  `source` integer,
  `context` varchar(255),
  `message` varchar(255)
);

ALTER TABLE `comments` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

ALTER TABLE `comments` ADD FOREIGN KEY (`club_id`) REFERENCES `clubs` (`id`);

ALTER TABLE `follows` ADD FOREIGN KEY (`following_user_id`) REFERENCES `users` (`id`);

ALTER TABLE `follows` ADD FOREIGN KEY (`followed_club_id`) REFERENCES `clubs` (`id`);
