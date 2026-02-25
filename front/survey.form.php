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
global $DB, $CFG_GLPI;

function pluginRpautoSurveyCheckCSRF(array $data): void {
    if (!empty($data['plugin_rpauto_survey_csrf_token'])) {
        Session::checkCSRF(['_glpi_csrf_token' => (string)$data['plugin_rpauto_survey_csrf_token']], true);
        return;
    }
    Session::checkCSRF($data, true);
}

if (isset($_POST["add"])) {
   pluginRpautoSurveyCheckCSRF($_POST);
   $survey->check(-1, CREATE, $_POST);
   $id = $survey->add($_POST);

   $mail = trim((string)($_POST["mail"] ?? ''));
   $DB->insert('glpi_plugin_rpauto_surveysuser', [
      'survey_id'         => (int)$id,
      'users_id'          => 0,
      'type'              => 1,
      'use_notification'  => 0,
      'alternative_email' => $mail
   ]);

   Html::back();

} else if (isset($_POST["purge"])) {
   pluginRpautoSurveyCheckCSRF($_POST);
   $survey->check((int)$_POST['id'], PURGE);
   $survey->delete($_POST);
   $survey->redirectToList();

} else if (isset($_POST["update"])) {
   pluginRpautoSurveyCheckCSRF($_POST);
   $survey->check((int)$_POST['id'], UPDATE);
   $survey->update($_POST);
   Html::back();

} else {

   $survey->checkGlobal(READ);

   Html::header(PluginRpautoSurvey::getTypeName(2), '', "admin", "pluginrpautomenu", "survey");

   $survey->display(['id' => (int)($_GET['id'] ?? 0)]);

   Html::footer();
}
