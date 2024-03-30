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
 *
 * Used to store reminders to send automatically
 */
class PluginRpautoReminder extends CommonDBTM {

   static $rightname = "plugin_rpauto";
   public $dohistory = true;

   public static $itemtype = TicketRpauto::class;
   public static $items_id = 'ticketrpautos_id';

   const CRON_TASK_NAME = 'RpautoReminder';


   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return _n('Rpauto reminder', 'Rpauto reminders', $nb, 'rpauto');
   }

   ////// CRON FUNCTIONS ///////

   /**
    * @param $name
    *
    * @return array
    */
   static function cronInfo($name) {

      switch ($name) {
         case self::CRON_TASK_NAME:
            return ['description' => __('Send automaticaly survey reminders', 'rpauto')];   // Optional
            break;
      }
      return [];
   }

   public static function deleteItem(Ticket $ticket) {
      $reminder = new Self;
      if ($reminder->getFromDBByCrit(['tickets_id' => $ticket->fields['id']])) {
         $reminder->delete(['id' => $reminder->fields["id"]]);
      }
   }

   /**
    * Cron action
    *
    * @param  $task for log
    *
    * @global $CFG_GLPI
    *
    * @global $DB
    */
   static function cronRpautoReminder($task = NULL) {

      // génération du mail 
      $mmail = new GLPIMailer();

      // For exchange
         $mmail->AddCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");

      if (empty($CFG_GLPI["from_email"])){
         // si mail expediteur non renseigné    
         $mmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"], false);
      }else{
         //si mail expediteur renseigné  
         $mmail->SetFrom($CFG_GLPI["from_email"], $CFG_GLPI["from_email_name"], false);
      }

      $mmail->AddAddress('lrydark93@gmail.com');
      $mmail->isHTML(true);

      // Objet et sujet du mail 
      $mmail->Subject = ('TEST');
      $mmail->Body = GLPIMailer::normalizeBreaks('Test mail auto RAPPORT PDF');


         // envoie du mail
         if(!$mmail->send()) {
               message("Erreur lors de l'envoi du mail : " . $mmail->ErrorInfo, ERROR);
         }else{
               message("<br>Mail envoyé à " . $EMAIL, INFO);
         }
         
      $mmail->ClearAddresses();


      /*$CronTask = new CronTask();
      if ($CronTask->getFromDBbyName(PluginRpautoReminder::class, PluginRpautoReminder::CRON_TASK_NAME)) {
         if ($CronTask->fields["state"] == CronTask::STATE_DISABLE) {
            return 0;
         }
      } else {
         return 0;
      }

      ?><script>
         // Code JavaScript pour écrire dans la console ***************************************************************************************************************************
         console.log("cronRpautoReminder");
      </script><?php*/
   

      self::sendReminders();
   }

   static function sendReminders() {

      $entityDBTM = new Entity();

      $pluginRpautoSurveyDBTM         = new PluginRpautoSurvey();
      $pluginRpautoSurveyReminderDBTM = new PluginRpautoSurveyReminder();
      $pluginRpautoReminderDBTM       = new PluginRpautoReminder();

      $surveys = $pluginRpautoSurveyDBTM->find(['is_active' => true]);

      foreach ($surveys as $survey) {

         // Entity
         $entityDBTM->getFromDB($survey['entities_id']);

         // Don't get tickets rpauto with date older than max_close_date
//                           $max_close_date = date('Y-m-d', strtotime($entityDBTM->getField('max_closedate')));
         $nb_days = $survey['reminders_days'];
         $dt             = date("Y-m-d");
         $max_close_date = date('Y-m-d', strtotime("$dt - ".$nb_days." day"));

         // Ticket Rpauto
         $ticketRpautos = self::getTicketRpauto($max_close_date, null, $survey['entities_id']);

         ?><script>
            // Code JavaScript pour écrire dans la console ***************************************************************************************************************************
            console.log("send reminders 1");
         </script><?php
         
 
         foreach ($ticketRpautos as $k => $ticketRpauto) {

            // Survey Reminders
            $surveyReminderCrit = [
               'plugin_rpauto_surveys_id' => $survey['id'],
               'is_active'                      => 1,
            ];
            $surveyReminders    = $pluginRpautoSurveyReminderDBTM->find($surveyReminderCrit);

            $potentialReminderToSendDates = [];

            ?><script>
               // Code JavaScript pour écrire dans la console ***************************************************************************************************************************
               console.log("send reminders 2");
            </script><?php

            // Calculate the next date of next reminders
            foreach ($surveyReminders as $surveyReminder) {

               $reminders = null;
               $reminders = $pluginRpautoReminderDBTM->find(['tickets_id' => $ticketRpauto['tickets_id'],
                                                                   'type'       => $surveyReminder['id']]);

               ?><script>
                  // Code JavaScript pour écrire dans la console ***************************************************************************************************************************
                  console.log("send reminders 3");
               </script><?php

               if (count($reminders)) {
                  continue;
               } else {
                  ?><script>
                     // Code JavaScript pour écrire dans la console ***************************************************************************************************************************
                     console.log("send reminders 4");
                  </script><?php

                  $lastSurveySendDate = date('Y-m-d', strtotime($ticketRpauto['date_begin']));

                  // Date when glpi rpauto was sended for the first time
                  $reminders_to_send = $pluginRpautoReminderDBTM->find(['tickets_id' => $ticketRpauto['tickets_id']]);
                  if (count($reminders_to_send)) {
                     $reminder           = array_pop($reminders_to_send);
                     $lastSurveySendDate = date('Y-m-d', strtotime($reminder['date']));
                  }

                  $date = null;

                  switch ($surveyReminder[PluginRpautoSurveyReminder::COLUMN_DURATION_TYPE]) {

                     case PluginRpautoSurveyReminder::DURATION_DAY:
                        $add  = " +" . $surveyReminder[PluginRpautoSurveyReminder::COLUMN_DURATION] . " day";
                        $date = strtotime(date("Y-m-d", strtotime($lastSurveySendDate)) . $add);
                        $date = date('Y-m-d', $date);
                        break;

                     case PluginRpautoSurveyReminder::DURATION_MONTH:
                        $add  = " +" . $surveyReminder[PluginRpautoSurveyReminder::COLUMN_DURATION] . " month";
                        $date = strtotime(date("Y-m-d", strtotime($lastSurveySendDate)) . $add);
                        $date = date('Y-m-d', $date);
                        break;
                     default:
                        $date = null;
                  }

                  if (!is_null($date)) {
                     $potentialReminderToSendDates[] = ["tickets_id" => $ticketRpauto['tickets_id'],
                                                        "type"       => $surveyReminder['id'],
                                                        "date"       => $date];
                  }
               }
            }
            // Order dates
            if (!function_exists("date_sort")) {
               function date_sort($a, $b) {
                  return strtotime($a["date"]) - strtotime($b["date"]);
               }
            }
            usort($potentialReminderToSendDates, "date_sort");
            $dateNow = date("Y-m-d");

            if (isset($potentialReminderToSendDates[0])) {

               $potentialTimestamp = strtotime($potentialReminderToSendDates[0]['date']);
               $nowTimestamp       = strtotime($dateNow);
               //
               if ($potentialTimestamp <= $nowTimestamp) {
                  // Send notification
                  PluginRpautoNotificationTargetTicket::sendReminder($ticketRpauto['tickets_id']);
                  $self = new self();
                  $self->add([
                                'type'       => $potentialReminderToSendDates[0]['type'],
                                'tickets_id' => $ticketRpauto['tickets_id'],
                                'date'       => $dateNow
                             ]);
               }
            }
         }
      }
   }
}
