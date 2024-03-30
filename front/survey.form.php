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


include('../../../inc/includes.php');

Session::checkLoginUser();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$survey = new PluginRpautoSurvey();

if (isset($_POST["add"])) {
   $survey->check(-1, CREATE, $_POST);
   $id = $survey->add($_POST);

   $mail = $_POST["mail"];
   $query= "INSERT INTO `glpi_plugin_rpauto_surveysuser` (`survey_id`, `users_id`, `type`, `use_notification`, `alternative_email`) VALUES ($id, 0, 1, 0, '$mail');";
   $survey_id = $DB->query($query);

   Html::back();

} else if (isset($_POST["purge"])) {
   $survey->check($_POST['id'], PURGE);
   $survey->delete($_POST);
   $survey->redirectToList();

} else if (isset($_POST["update"])) {
   $survey->check($_POST['id'], UPDATE);
   $survey->update($_POST);
   Html::back();

} else {

   $survey->checkGlobal(READ);

   Html::header(PluginRpautoSurvey::getTypeName(2), '', "admin", "pluginrpautomenu", "survey");

   $survey->display(['id' => $_GET['id']]);

   Html::footer();
}
