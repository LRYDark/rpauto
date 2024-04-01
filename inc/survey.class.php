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


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Class PluginRpautoSurvey
 */
class PluginRpautoSurvey extends CommonDBTM {

   static $rightname = "plugin_rpauto";
  /* public $dohistory = true;

   public $can_be_translated = true;*/

   static function getTypeName($nb = 0) {
      return _n('Rpauto survey', 'Rpauto surveys', $nb, 'rpauto');
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(Log::class, $ong, $options);
      return $ong;
   }

   function canCreateItem() {

      if (!$this->checkEntity()) {
         return false;
      }
      return true;
   }

   /**
    * @return array
    */
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => self::getTypeName(2)
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'itemlink_type'      => $this->getType(),
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'is_active',
         'name'               => __('Active'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'massiveaction'      => false,
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   /**
    * Print survey
    *
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {
      global $DB;
      
      if (!$this->canView()) {
         return false;
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input('name', ['value' => $this->fields['name'], 'size' => 40]);
      echo "</td>";
      echo "<td>" . __('Comments') . "</td>";
      echo "<td>";
      echo Html::textarea([
                             'name'    => 'comment',
                             'value'    => $this->fields["comment"],
                             'cols'    => '60',
                             'rows'    => '6',
                             'display' => false,
                          ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Entity') . "</td>";
      echo "<td>";
      Dropdown::show('Entity', [
         'name' => 'entities_id',
         'value' => $this->fields["entities_id"],
         'display_emptychoice' => 1,
         'specific_tags' => [],
         'itemtype' => 'Entity',
         'displaywith' => [],
         'emptylabel' => "-----",
         'used' => [],
         'toadd' => [],
         'entity_restrict' => 0,
      ]); 
      echo "</td><td colspan='2'></td></tr>";


      $ID_notificationtemplates = $DB->query("SELECT id FROM glpi_notificationtemplates WHERE NAME = 'Rapport automatique PDF'")->fetch_object();
      if(empty($this->fields["gabarit"])){
         $this->fields["gabarit"] = $ID_notificationtemplates->id;
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Model de notification') . "</td>";
      echo "<td>";
      //notificationtemplates_id
      Dropdown::show('NotificationTemplate', [
         'name' => 'gabarit',
         'value' => $this->fields["gabarit"],
         'display_emptychoice' => 1,
         'specific_tags' => [],
         'itemtype' => 'NotificationTemplate',
         'displaywith' => [],
         'emptylabel' => "-----",
         'used' => [],
         'toadd' => [],
         'entity_restrict' => 0,
      ]); 
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Active') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Affichage des tâches privés') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("tasks_private", $this->fields["tasks_private"]);
      echo "</td>";
      echo "<td>" . __('Affichager les images des tâches') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("tasks_img", $this->fields["tasks_img"]);
      echo "</td>";
      echo "<td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Affichage des suivis privés') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("suivis_private", $this->fields["suivis_private"]);
      echo "</td>";
      echo "<td>" . __('Affichager les images des suivis') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("suivis_img", $this->fields["suivis_img"]);
      echo "</td>";
      echo "<td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Affichage de la déscription du ticket') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("ticket_desc", $this->fields["ticket_desc"]);
      echo "</td>";
      if (Plugin::isPluginActive('rt')) {
         echo "<td>" . __('Affichager du temps de trajet') . "</td>";
         echo "<td>";
         Dropdown::showYesNo("route_time", $this->fields["route_time"]);
         echo "</td>";
      }
      echo "<td colspan='2'></td></tr>";

      echo "<td>" . __('Email') . "</td>";
      echo "<td>";
      $mail = $DB->query("SELECT alternative_email FROM glpi_plugin_rpauto_surveysuser WHERE survey_id = $ID")->fetch_object();
      if(empty($mail->alternative_email)){$mail = ' ';}else{$mail = $mail->alternative_email;}
      echo Html::input('mail', ['value' => $mail, 'size' => 40]);
      echo "</td>";  

      $this->showFormButtons($options);
      Html::closeForm();

      return true;
   }

   /**
    * Prepare input datas for adding the item
    **/
   function prepareInputForAdd($input) {

      if ($input['is_active'] == 1) {
         $dbu = new DbUtils();
         //we must store only one survey by entity
         $condition  = ['is_active' => 1]
                        + $dbu->getEntitiesRestrictCriteria($this->getTable(), 'entities_id', $input['entities_id'], true);
         $found = $this->find($condition);
         if (count($found) > 0) {
            Session::addMessageAfterRedirect(__('Error : only one survey is allowed by entity', 'rpauto'), false, ERROR);
            return false;
         }
      }

      return $input;
   }

   /**
    * Prepare input datas for updating the item
    **/
   function prepareInputForUpdate($input){
      global $DB;

      $id = $input['id'];
      $mail = $input['mail'];      
      $query= "UPDATE glpi_plugin_rpauto_surveysuser SET alternative_email = '$mail' WHERE survey_id = $id";
      $DB->query($query);

      //active external survey for entity
      if ($input['is_active'] == 1) {
         $dbu = new DbUtils();
         //we must store only one survey by entity (other this one)
         $condition  = ['is_active' => 1,
                        ['NOT' => ['id' => $this->getID()]]]
                       + $dbu->getEntitiesRestrictCriteria($this->getTable(), 'entities_id', $input['entities_id'], true);
         $found = $this->find($condition);
         if (count($found) > 0) {
            Session::addMessageAfterRedirect(__('Error : only one survey is allowed by entity',
                                                'rpauto'), false, ERROR);
            return false;
         }
      }

      return $input;
   }
}
