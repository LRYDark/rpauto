ALTER TABLE `glpi_plugin_rpauto_surveys` ADD `date_creation` date default NULL;
ALTER TABLE `glpi_plugin_rpauto_surveys` ADD `date_mod` datetime default NULL;

ALTER TABLE `glpi_plugin_rpauto_surveyquestions` ADD `type` varchar(255) collate utf8_unicode_ci default NULL;
ALTER TABLE `glpi_plugin_rpauto_surveyquestions` ADD `comment` text collate utf8_unicode_ci default NULL;
ALTER TABLE `glpi_plugin_rpauto_surveyquestions` ADD `number` int(11) NOT NULL DEFAULT 0;


ALTER TABLE `glpi_plugin_rpauto_surveyanswers` ADD `ticketrpautos` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `glpi_plugin_rpauto_surveyanswers` DROP `tickets_id`;
