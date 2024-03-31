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
 * Class PluginRpautoMenu
 */
class PluginRpautoMenu extends CommonGLPI
{
   static $rightname = 'plugin_rpauto';

   /**
    * @return translated
    */
   static function getMenuName() {
      return __('Rapport mail automatique', 'rpauto');
   }

   /**
    * @return array
    */
   static function getMenuContent() {

      $menu = [];

      if (Session::haveRight('plugin_rpauto', READ)) {
         $menu['title']           = self::getMenuName();
         $menu['page']            = PLUGIN_RPAUTO_NOTFULL_WEBDIR."/front/survey.php";
         $menu['links']['search'] = PluginRpautoSurvey::getSearchURL(false);
         if (PluginRpautoSurvey::canCreate()) {
            $menu['links']['add'] = PluginRpautoSurvey::getFormURL(false);
         }
      }

      $menu['icon'] = self::getIcon();

      return $menu;
   }

   static function getIcon() {
      return "fa-fw ti ti-report";
   }

   static function removeRightsFromSession() {
      if (isset($_SESSION['glpimenu']['admin']['types']['PluginRpautoMenu'])) {
         unset($_SESSION['glpimenu']['admin']['types']['PluginRpautoMenu']);
      }
      if (isset($_SESSION['glpimenu']['admin']['content']['pluginrpautomenu'])) {
         unset($_SESSION['glpimenu']['admin']['content']['pluginrpautomenu']);
      }
   }
}
