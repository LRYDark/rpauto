# noinspection SqlNoDataSourceInspectionForFile

CREATE TABLE `glpi_plugin_rpauto_surveys` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL default '0',
  `is_active` tinyint NOT NULL default '0',
  `name` varchar(255) collate utf8mb4_unicode_ci default NULL,
  `comment` text collate utf8mb4_unicode_ci default NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
