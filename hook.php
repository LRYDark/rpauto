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

   $rep_files_rp = GLPI_PLUGIN_DOC_DIR . "/rp/rapports_auto";
   if (!is_dir($rep_files_rp))
      mkdir($rep_files_rp);

   if (!$DB->tableExists("glpi_plugin_rpauto_surveys")) { //version 1.0.0
      $DB->runFile(Plugin::getPhpDir('rpauto')."/install/sql/empty-1.0.0.sql");
   } /*else {
      //version beta 0.1.0
      if (!$DB->fieldExists("glpi_plugin_rpauto_surveys", "reminders_days")) {
         $DB->runFile(Plugin::getPhpDir('rpauto')."/install/sql/update-1.4.5.sql");
      }
   }*/

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
   include_once(Plugin::getPhpDir('rpauto')."/inc/menu.class.php");

   $DB->query("DROP TABLE IF EXISTS glpi_plugin_rpauto_surveys, glpi_plugin_rpauto_surveysuser, glpi_plugin_rpauto_send;");

   $tables_glpi = ["glpi_logs"];
   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE FROM `$table_glpi`
               WHERE `itemtype` = 'PluginRpautoSurvey';");
   }


   $notifications_templates = $DB->query("SELECT * FROM glpi_notificationtemplates WHERE comment = 'Created by the plugin RPAUTO';");
   while ($notification_template = $DB->fetchArray($notifications_templates)) {
      $id_notificationtemplates = $notification_template['id'];

      $DB->query("DELETE FROM `glpi_notificationtemplatetranslations` WHERE `notificationtemplates_id` = $id_notificationtemplates;");
   }
   $tables_glpi = ["glpi_notificationtemplates"];
   foreach ($tables_glpi as $table_glpi) {
      $DB->query("DELETE FROM `$table_glpi` WHERE `comment` = 'Created by the plugin RPAUTO';");
   }

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginRpautoProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   PluginRpautoProfile::removeRightsFromSession();
   PluginRpautoMenu::removeRightsFromSession();

   CronTask::Register(PluginRpautoReminder::class, PluginRpautoReminder::CRON_TASK_NAME, DAY_TIMESTAMP);

   return true;
}
