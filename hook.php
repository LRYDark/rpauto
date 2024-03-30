<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 rpauto plugin for GLPI
 Copyright (C) 2016-2022 by the rpauto Development Team.

 https://github.com/pluginsglpi/rpauto
 -------------------------------------------------------------------------

 LICENSE

 This file is part of rpauto.

 rpauto is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 rpauto is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with rpauto. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * @return bool
 */
function plugin_rpauto_install() {
   global $DB;

   include_once(Plugin::getPhpDir('rpauto')."/inc/profile.class.php");
   //include_once(Plugin::getPhpDir('rpauto')."/inc/notificationtargetticket.class.php");

   if (!$DB->tableExists("glpi_plugin_rpauto_surveys")) {
      $DB->runFile(Plugin::getPhpDir('rpauto')."/install/sql/empty-1.6.0.sql");

   } else {
      //version beta 0.1.0
      if (!$DB->fieldExists("glpi_plugin_rpauto_surveys", "reminders_days")) {
         $DB->runFile(Plugin::getPhpDir('rpauto')."/install/sql/update-1.4.5.sql");
      }
   }

   //PluginRpautoNotificationTargetTicket::install();
   PluginRpautoProfile::initProfile();
   PluginRpautoProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   CronTask::Register(PluginRpautoReminder::class, PluginRpautoReminder::CRON_TASK_NAME, DAY_TIMESTAMP);
   return true;
}

/**
 * @return bool
 */
function plugin_rpauto_uninstall() {
   global $DB;

   include_once(Plugin::getPhpDir('rpauto')."/inc/profile.class.php");
   //include_once(Plugin::getPhpDir('rpauto')."/inc/menu.class.php");
   //include_once(Plugin::getPhpDir('rpauto')."/inc/notificationtargetticket.class.php");

   $tables = [
      "glpi_plugin_rpauto_surveys",
      "glpi_plugin_rpauto_surveyquestions",
      "glpi_plugin_rpauto_surveyanswers",
      "glpi_plugin_rpauto_surveyreminders",
      "glpi_plugin_rpauto_surveytranslations",
      "glpi_plugin_rpauto_reminders"
   ];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   $tables_glpi = ["glpi_logs"];

   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE FROM `$table_glpi`
               WHERE `itemtype` = 'PluginRpautoSurvey';");
   }

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginRpautoProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   PluginRpautoProfile::removeRightsFromSession();

   PluginRpautoMenu::removeRightsFromSession();

   //PluginRpautoNotificationTargetTicket::uninstall();

   CronTask::Register(PluginRpautoReminder::class, PluginRpautoReminder::CRON_TASK_NAME, DAY_TIMESTAMP);

   return true;
}
