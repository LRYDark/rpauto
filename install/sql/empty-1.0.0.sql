CREATE TABLE `glpi_plugin_rpauto_surveys` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `gabarit` int NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL default '0',
  `is_active` tinyint NOT NULL default '0',
  `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
  `comment` text collate utf8mb4_unicode_ci default NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  `tasks_private` tinyint NOT NULL default '0',
  `tasks_img` tinyint NOT NULL default '0',
  `suivis_private` tinyint NOT NULL default '0',
  `suivis_img` tinyint NOT NULL default '0',
  `ticket_desc` tinyint NOT NULL default '0',
  `route_time` tinyint NOT NULL default '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `glpi_plugin_rpauto_surveysuser` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` int unsigned NOT NULL DEFAULT 0,
  `users_id` int unsigned NOT NULL DEFAULT 0,
  `type` int NOT NULL DEFAULT 1,
  `use_notification` tinyint NOT NULL default '1',
  `alternative_email` varchar(255) collate utf8mb4_unicode_ci default NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

ALTER TABLE glpi_plugin_rpauto_surveysuser
ADD CONSTRAINT fk_survey_id
FOREIGN KEY (survey_id)
REFERENCES glpi_plugin_rpauto_surveys(id)
ON DELETE CASCADE;

CREATE TABLE `glpi_plugin_rpauto_send` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `survey_id` int unsigned NOT NULL DEFAULT 0,
  `send_from` timestamp NULL DEFAULT NULL,
  `send_to` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;