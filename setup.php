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
 * Init the hooks of the plugins -Needed
 */

define ("PLUGIN_RPAUTO_VERSION", "0.1.0");

// Minimal GLPI version, inclusive
define('PLUGIN_RPAUTO_MIN_GLPI', '10.0');
// Maximum GLPI version, exclusive
define('PLUGIN_RPAUTO_MAX_GLPI', '11.0');

function plugin_init_rpauto() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['rpauto'] = true;
   $PLUGIN_HOOKS['change_profile']['rpauto'] = [PluginRpautoProfile::class, 'initProfile'];

   if (Plugin::isPluginActive('rpauto')) {

      //if glpi is loaded
      if (Session::getLoginUserID()) {

         /*Plugin::registerClass(PluginRpautoProfile::class,
                               ['addtabon' => Profile::class]);*/

         /*$PLUGIN_HOOKS['pre_item_form']['rpauto'] = [PluginRpautoSurveyAnswer::class, 'displayRpauto'];

         $PLUGIN_HOOKS['pre_item_update']['rpauto'][TicketRpauto::class] = [PluginRpautoSurveyAnswer::class,
                                                                                        'preUpdateRpauto'];

         $PLUGIN_HOOKS['item_get_events']['rpauto'] =
            ['NotificationTargetTicket' => ['PluginRpautoNotificationTargetTicket', 'addEvents']];

         $PLUGIN_HOOKS['item_delete']['rpauto'] = ['Ticket' => ['PluginRpautoReminder', 'deleteItem']];*/

         //current user must have config rights
         if (Session::haveRight('plugin_rpauto', READ)) {
            $config_page = 'front/survey.php';
            $PLUGIN_HOOKS['config_page']['rpauto'] = $config_page;

            $PLUGIN_HOOKS["menu_toadd"]['rpauto'] = ['admin' => PluginRpautoMenu::class];
         }

         /*if (isset($_SESSION['glpiactiveprofile']['interface'])
             && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $PLUGIN_HOOKS['add_javascript']['rpauto'] = ["rpauto.js"];
         }
         if (class_exists('PluginMydashboardMenu')) {
            $PLUGIN_HOOKS['mydashboard']['rpauto'] = [PluginRpautoDashboard::class];
         }*/
      }

        /* $PLUGIN_HOOKS['item_get_datas']['rpauto'] = [NotificationTargetTicket::class => [PluginRpautoSurveyAnswer::class,
         'addNotificationDatas']];*/
   }
}

/**
 * Get the name and the version of the plugin - Needed
 *
 * @return array
 */
function plugin_version_rpauto() {

   return [
      'name'           => __("Rp Auto", 'rpauto'),
      'version'        => PLUGIN_RPAUTO_VERSION,
      'author'         => "REINERT Joris",
      'homepage'       => 'https://github.com/LRYDark/rpauto/releases',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_RPAUTO_MIN_GLPI,
            'max' => PLUGIN_RPAUTO_MAX_GLPI,
         ]
      ]
   ];
}
