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
  `tasks_img` tinyint NOT NULL default '1',
  `suivis_private` tinyint NOT NULL default '0',
  `suivis_img` tinyint NOT NULL default '1',
  `ticket_desc` tinyint NOT NULL default '1',
  `route_time` tinyint NOT NULL default '1',
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

ALTER TABLE glpi_plugin_rpauto_send
ADD CONSTRAINT fk_surveysend_id
FOREIGN KEY (survey_id)
REFERENCES glpi_plugin_rpauto_surveys(id)
ON DELETE CASCADE;

INSERT INTO `glpi_notificationtemplates` (`name`, `itemtype`, `date_mod`, `comment`, `css`, `date_creation`) VALUES ('Rapport automatique PDF', 'Ticket', NULL, 'Created by the plugin RPAUTO', '', NULL);
INSERT INTO `glpi_notificationtemplatetranslations` (`notificationtemplates_id`, `language`, `subject`, `content_text`, `content_html`) VALUES (LAST_INSERT_ID(), '', '[GLPI] | Rapports d\'intervention du ##date.old## au ##date.current##', ' 		 \n\nRAPPORTS D\'INTERVENTION\n\n 		 \n\nChère cliente, cher client,\n\n \n\nVeuillez trouver ci-joint un fichier ZIP contenant tous les rapports d\'intervention datant du ##date.old## au ##date.current##.\n\n \n\nCordialement,\n\nL\'équipe JCD\n\n_Ce courrier électronique est envoyé automatiquement par le centre de service Easi Support._\n\nGénéré automatiquement par GLPI.', '&#60;table cellspacing="0" cellpadding="0" align="center"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center"&#62;\r\n&#60;table width="600" cellspacing="0" cellpadding="0" align="center" bgcolor="transparent"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="left"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left" width="270"&#62;\r\n&#60;table width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center"&#62;&#60;a&#62;&#60;img class="CToWUd" src="https://ci3.googleusercontent.com/meips/ADKq_NbMZUvFUDxtwMPhuNczY-aOMR16hRkHxEmquZBcZpKGBl9BC3YIrYH-z17yfdhPcPp09La0Nog_G4pdSYn2d-3ursWMp_Pw-Z9kmQWueHhG3wShJskYop-sxZyuZYniSHfXagm3suTdfg3Ll5YlcMOGD8GjB-o1oVpTK2c03gDvjVKIYeASbGwd=s0-d-e1-ft#https://fvjwbn.stripocdn.email/content/guids/CABINET_44164322675628a7251e1d7d361331e9/images/logoeasisupportnew.png" alt="" width="270" data-bit="iit"&#62;&#60;/a&#62;&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="right"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left" width="270"&#62; &#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="center"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center"&#62;\r\n&#60;table width="600" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left" bgcolor="transparent"&#62;\r\n&#60;table style="height: 271px;" width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr style="height: 271px;"&#62;\r\n&#60;td style="height: 271px;" align="center" valign="top" width="560"&#62;&#60;br&#62;\r\n&#60;table style="height: 331px;" width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr style="height: 37px;"&#62;\r\n&#60;td style="height: 37px;" align="center"&#62;\r\n&#60;div&#62;\r\n&#60;h2&#62;&#60;strong&#62;RAPPORTS D\'INTERVENTION&#60;/strong&#62;&#60;/h2&#62;\r\n&#60;/div&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;tr style="height: 21px;"&#62;\r\n&#60;td style="height: 21px;" align="center"&#62; &#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;tr style="height: 273px;"&#62;\r\n&#60;td style="height: 273px;" align="left"&#62;\r\n&#60;h2&#62;Chère cliente, cher client,&#60;/h2&#62;\r\n&#60;p&#62; &#60;/p&#62;\r\n&#60;p&#62;&#60;br&#62;Veuillez trouver ci-joint un fichier ZIP contenant tous les rapports d\'intervention datant du ##date.old## au ##date.current##.&#60;/p&#62;\r\n&#60;p&#62; &#60;/p&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="center"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center"&#62;\r\n&#60;table width="600" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;\r\n&#60;table width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center" valign="top" width="600"&#62;\r\n&#60;table width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;\r\n&#60;p&#62;Cordialement,&#60;/p&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="center"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center"&#62;\r\n&#60;table width="600" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="left"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left" width="180"&#62;\r\n&#60;table width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;&#60;a&#62;&#60;img class="CToWUd" src="https://ci3.googleusercontent.com/meips/ADKq_NaEqy6VWWDm6oXDTghtitNBpsEAJW4Y7U_gB_qLkXpa-YxlCy-x_y_C8PpoMO04E1b6HRXqdP0He4DkNFt5P7beMeR85v0eio-YPyDCYhE-SruwGX8SnV-urmM72buDbwYgKbDmkEjvtBrNEo1C5tMXbtKATt3d5rA4b5P7Jvvfl4Uf=s0-d-e1-ft#https://fvjwbn.stripocdn.email/content/guids/CABINET_44164322675628a7251e1d7d361331e9/images/logo_jcd_54G.png" alt="" width="80" data-bit="iit"&#62;&#60;/a&#62;&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="right"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left" width="360"&#62;\r\n&#60;table width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;\r\n&#60;p&#62;&#60;br&#62;&#60;br&#62;&#60;strong&#62;L\'équipe JCD&#60;/strong&#62;&#60;/p&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;table cellspacing="0" cellpadding="0" align="center"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center"&#62;\r\n&#60;table width="600" cellspacing="0" cellpadding="0" align="center" bgcolor="#ffffff"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;\r\n&#60;table width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="center" valign="top" width="560"&#62;\r\n&#60;table width="100%" cellspacing="0" cellpadding="0"&#62;\r\n&#60;tbody&#62;\r\n&#60;tr&#62;\r\n&#60;td align="left"&#62;\r\n&#60;p&#62;&#60;br&#62;&#60;em&#62;Ce courrier électronique est envoyé automatiquement par le centre de service Easi Support.&#60;/em&#62;&#60;br&#62;&#60;br&#62;&#60;br&#62;Généré automatiquement par GLPI.&#60;/p&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;\r\n&#60;/td&#62;\r\n&#60;/tr&#62;\r\n&#60;/tbody&#62;\r\n&#60;/table&#62;');
