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
require_once(PLUGIN_RPAUTO_DIR . "/fpdf/html2pdf.php");

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

   const CRON_TASK_NAME = 'RpautoMail';


   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return _n('Rpauto Mail', 'Rpauto Mail', $nb, 'rpauto');
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
            return ['description' => __('Envoyer automatiquement les rapports PDF par mail', 'rpauto')];   // Optional
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
   static function cronRpautoMail($task = NULL) {
      global $DB, $CFG_GLPI;
      
      // Clear html
         function ClearHtmlAuto($valuedes){
            $values = $valuedes;
            $values = stripcslashes($values);
            $values = htmlspecialchars_decode($values);
            $values = Glpi\RichText\RichText::getTextFromHtml($values);
            $values = strip_tags($values);
            $values = Toolbox::decodeFromUtf8($values);
            $values = Glpi\Toolbox\Sanitizer::unsanitize($values);
            $values = str_replace("’", "'", $values);
            $values = str_replace("?", "'", $values);
            return $values;
         }

      // Clear html space
         function ClearSpaceAuto($valuedes){
               $values = $valuedes;
               return preg_replace("# {2,}#"," \n",preg_replace("#(\r\n|\n\r|\n|\r)#"," ",$values));  // Suppression des saut de ligne superflu
         }

      Session::addMessageAfterRedirect(__('test GO','rpauto'), false, ERROR);
      
      // definition de la date et heure actuelle
      date_default_timezone_set('Europe/Paris');
         $CurrentDate = date("Y-m-d H:i:s");

         $query_surveyid = $DB->query("SELECT id FROM glpi_plugin_rpauto_surveys");
         //While 1 -------------------------------------------------------
         while ($data = $DB->fetchArray($query_surveyid)) {
            $surveyid = $data['id'];
            $query_surveyid_data = $DB->query("SELECT * FROM glpi_plugin_rpauto_surveys WHERE id = $surveyid")->fetch_object();

            // Récupértion du mail pour envoyé le PDF
            $query_sel_mail = $DB->query("SELECT alternative_email FROM glpi_plugin_rpauto_surveysuser WHERE survey_id = $surveyid")->fetch_object();

            // Récupération des dates et heures
            $query_rpauto_send = $DB->query("SELECT * FROM glpi_plugin_rpauto_send WHERE survey_id = $surveyid")->fetch_object();
            if(empty($query_rpauto_send->send_from)){
               $OldDate = date('Y-m-d H:i:s', strtotime('-1 month', strtotime($CurrentDate)));
            }else{
               $OldDate = $query_rpauto_send->send_from;
            }

            //requette pour recupéré les tickets cloturées ou solutionnées sur la periode donnée
                     // attention modifier si recursif ou pas////////////////////////////////////////////////////////////////////////////
            $query_ticket_close_and_answer = $DB->query("SELECT * FROM glpi_tickets WHERE entities_id = 0 AND (solvedate BETWEEN '$OldDate' AND '$CurrentDate' OR closedate BETWEEN '$OldDate' AND '$CurrentDate');");
               //While 2 -------------------------------------------------------
               while ($data2 = $DB->fetchArray($query_ticket_close_and_answer)) {
                  $ticketid = $data2['id']; // ID DU TICKET

                  // Instanciation de la classe dérivée --------------------------------------------------------------------
                     $pdf = new FPDF('P','mm','A4');
                     $pdf->AliasNbPages();
                     $pdf->AddPage();
                     $pdf->SetFont('Arial','',10); // police d'ecriture
                     $pdf->SetFillColor(77, 113, 166);

                  // Entête du PDF --------------------------------------------------------------------
                     $config     = PluginRpConfig::getInstance();
                     $doc        = new Document();
                     $img        = $doc->find(['id' => $config->fields['logo_id']]);
                     $img        = reset($img);
                     $pdf->SetFont('Arial','B',15);// police d'ecriture
         
                     // logo
                     if(isset($img['filepath'])){
                        $img = GLPI_DOC_DIR.'/'.$img['filepath'];
                        if(file_exists($img)){
                           $pdf->Image($img,$config->fields['margin_left'],$config->fields['margin_top'],$config->fields['cut']);  
                        }
                     }
         
                     $pdf->Cell(50,20,'',1,0,'C');
                     // titre du pdf
                     $pdf->Cell(90,20,"RAPPORT D'INTERVENTION",1,0,'C');

                     //date et heure de génération
                     $pdf->SetFont('Arial','',10); // police d'ecriture
                     $pdf->MultiCell(50,10,utf8_decode("Date d'édition :\n" .date("Y-m-d à H:i:s")),1,'C');
                     
             
                  /*// Pied de page du PDF --------------------------------------------------------------------
                     // Positionnement à 1,5 cm du bas
                     $pdf->SetY(-20);
                     // Police Arial italique 8
                     $pdf->SetFont('Arial','I',8);
         
                     // Numéro de page
                     $pdf->Cell(0,5,'Page '.$pdf->PageNo().'/{nb}',0,0,'C');
                     $pdf->Ln();
                     $pdf->Cell(0,5,utf8_decode($config->fields['line1']),0,0,'C');
                     $pdf->Ln();
                     $pdf->Cell(0,5,$config->fields['line2'],0,0,'C');  */

                  // --------- INFO CLIENT
                     //* VARIABLS */
                        $glpi_tickets_infos = $DB->query("SELECT * FROM glpi_tickets INNER JOIN glpi_entities ON glpi_tickets.entities_id = glpi_entities.id WHERE glpi_tickets.id = $ticketid")->fetch_object();
                        $glpi_plugin_rp_dataclient = $DB->query("SELECT * FROM `glpi_plugin_rp_dataclient` WHERE id_ticket = $ticketid")->fetch_object();

                        if(!empty($glpi_plugin_rp_dataclient->id_ticket)){
                           $SOCIETY = $glpi_plugin_rp_dataclient->society;
                           $TOWN = $glpi_plugin_rp_dataclient->town;
                           $ADDRESS = $glpi_plugin_rp_dataclient->address;
                           $POSTCODE = $glpi_plugin_rp_dataclient->postcode;
                           $PHONE = $glpi_plugin_rp_dataclient->phone;
                           $EMAIL = $glpi_plugin_rp_dataclient->email;
                        }else{
                           $SOCIETY = $glpi_tickets_infos->comment;
                           if(empty($SOCIETY)){$SOCIETY = $glpi_tickets_infos->completename;}
                           $TOWN = $glpi_tickets_infos->town;
                           $ADDRESS = $glpi_tickets_infos->address;
                           $POSTCODE = $glpi_tickets_infos->postcode;
                           $PHONE = $glpi_tickets_infos->phonenumber;
                           $EMAIL = $glpi_tickets_infos->email;
                        }

                        if (empty($SOCIETY)) $SOCIETY = "-";
                        if (empty($ADDRESS)) $ADDRESS = "-";
                        if (empty($TOWN)) $TOWN = "-";
                        if (empty($PHONE)) $PHONE = "-";
                        if (empty($EMAIL)) $EMAIL = "-";
                     //* VARIABLS */

                  $pdf->Cell(95,5,utf8_decode('N° du ticket'),1,0,'L',true);

                  $pdf->Cell(95,5,$ticketid,1,0,'L',false,$_SERVER['HTTP_REFERER']);
                  $pdf->Ln(10);
                  $pdf->Cell(50,5,utf8_decode('Nom de la société / Client'),1,0,'L',true);
                     if($glpi_tickets->requesttypes_id != 7 && $FORM == 'FormClient'){ 
                        $pdf->Cell(140,5,utf8_decode($SOCIETY." / ".$NAMERESPMAT),1,0,'L');
                     }else{
                        $pdf->Cell(140,5,utf8_decode($SOCIETY),1,0,'L');
                     }
                  $pdf->Ln();
                  $pdf->Cell(50,5,'Adresse',1,0,'L',true);
                  $pdf->Cell(140,5,utf8_decode($ADDRESS),1,0,'L');
                  $pdf->Ln();
                  $pdf->Cell(50,5,'Ville',1,0,'L',true);
                  $pdf->Cell(140,5,utf8_decode($TOWN),1,0,'L');
                  $pdf->Ln(10);
                  $pdf->Cell(50,5,utf8_decode('N° de Téléphone'),1,0,'L',true);
                  $pdf->Cell(140,5,utf8_decode($PHONE),1,0,'L');
                  $pdf->Ln();
                  $pdf->Cell(50,5,utf8_decode('Email'),1,0,'L',true);
                  $pdf->Cell(140,5,utf8_decode($EMAIL),1,0,'L');
                  $pdf->Ln(10);
                  // --------- INFO CLIENT

                  // --------- DEMANDE
                  $pdf->Cell(190,5,'Description de la demande',1,0,'C',true);
                  $pdf->Ln(5);
                  $pdf->MultiCell(0,5,ClearHtmlAuto($data2['name']),1,'C');
                  $pdf->Ln(0);
                  // --------- DEMANDE

                  // --------- DESCRIPTION
                  if($query_surveyid_data->ticket_desc == 1){
                     $pdf->Ln(5);
                     $pdf->Cell(190,5,utf8_decode('Description du problème'),1,0,'C',true);
                     $pdf->Ln();

                     $pdf->MultiCell(0,5,ClearSpaceAuto(ClearHtmlAuto($data2['content'])),1,'L');
                     $Y = $pdf->GetY();
                     $X = $pdf->GetX();

                        $query = $DB->query("SELECT documents_id FROM glpi_documents_items WHERE items_id = $ticketid AND itemtype = 'Ticket'");
                        while ($data3 = $DB->fetchArray($query)) {
                           if (isset($data3['documents_id'])){
                                 $iddoc = $data3['documents_id'];
                                 $ImgUrl = $DB->query("SELECT filepath FROM glpi_documents WHERE id = $iddoc")->fetch_object();
                           }
                        
                           $img = GLPI_DOC_DIR.'/'.$ImgUrl->filepath;

                           if (file_exists($img)){
                                 $imageSize = getimagesize($img);
                                 $width = $imageSize[0];
                                 $height = $imageSize[1];

                                 if($width != 0 && $height != 0){
                                    $taille = (100*$height)/$width;
                                    
                                    if($pdf->GetY() + $taille > 297-15) {
                                             $pdf->AddPage();
                                             $pdf->Image($img,$X,$pdf->GetY()+2,100,$taille);
                                       $pdf->Ln($taille + 5);
                                    }else{
                                             $pdf->Image($img,$X,$pdf->GetY()+2,100,$taille);
                                             $pdf->SetXY($X,$Y+($taille));
                                       $pdf->Ln();
                                    }  
                                 }
                                 $Y = $pdf->GetY();
                                 $X = $pdf->GetX();             
                           }
                        }
                     // Créé par + temps
                     $pdf->SetXY($X,$Y);
                  }
                  // --------- DESCRIPTION

                  if($query_surveyid_data->tasks_private == 1){
                     $is_private_tasks = "AND is_private = 0";
                  }else{
                     $is_private_tasks = "";
                  }

                  // --------- TACHES
                  $querytask = $DB->query("SELECT glpi_tickettasks.id FROM glpi_tickettasks INNER JOIN glpi_users ON glpi_tickettasks.users_id = glpi_users.id WHERE tickets_id = $ticketid");
                  $sumtask = 0;

                  while ($datasumtask = $DB->fetchArray($querytask)) {
                     if(!empty($datasumtask['id'])) $sumtask++;  
                  }

                  if ($sumtask > 0){
                     $querytask = $DB->query("SELECT glpi_tickettasks.id, content, date, name, actiontime FROM glpi_tickettasks INNER JOIN glpi_users ON glpi_tickettasks.users_id = glpi_users.id WHERE tickets_id = $ticketid $is_private_tasks");
                        $pdf->Ln(5);
                     $pdf->Cell(190,5,utf8_decode('Tâche(s) : '.$sumtask),1,0,'L',true);
                        $pdf->Ln(2);            

                     while ($datatask = $DB->fetchArray($querytask)) {
                        //verifications que la variable existe
                        if(!empty($datatask['id'])){

                              $pdf->Ln();
                              $pdf->MultiCell(0,5,ClearSpaceAuto(ClearHtmlAuto($datatask['content'])),1,'L');
                              $Y = $pdf->GetY();
                              $X = $pdf->GetX();

                              if ($query_surveyid_data->tasks_img == 1){
                                 //récupération de l'ID de l'image s'il y en a une.
                                 $IdImg = $datatask['id'];
                                 $querytaskdoc = $DB->query("SELECT documents_id FROM glpi_documents_items WHERE items_id = $IdImg AND itemtype = 'TicketTask'");
                                 while ($datataskdoc = $DB->fetchArray($querytaskdoc)) {
                                    if (isset($datataskdoc['documents_id'])){
                                    $iddoc = $datataskdoc['documents_id'];
                                    $ImgUrl = $DB->query("SELECT filepath FROM glpi_documents WHERE id = $iddoc")->fetch_object();
                                    }
                                 
                                    $img = GLPI_DOC_DIR.'/'.$ImgUrl->filepath;
                     
                                    if (file_exists($img)){
                                          $imageSize = getimagesize($img);
                                          $width = $imageSize[0];
                                          $height = $imageSize[1];
                        
                                          if($width != 0 && $height != 0){
                                             $taille = (100*$height)/$width;
                                             
                                                if($pdf->GetY() + $taille > 297-15) {
                                                      $pdf->AddPage();
                                                      $pdf->Image($img,$X,$pdf->GetY()+2,100,$taille);
                                                      $pdf->Ln($taille + 5);
                                                }else{
                                                      $pdf->Image($img,$X,$pdf->GetY()+2,100,$taille);
                                                      $pdf->SetXY($X,$Y+($taille));
                                                      $pdf->Ln();
                                                }  
                                          }
                                          $Y = $pdf->GetY();
                                          $X = $pdf->GetX();             
                                    }
                                 }
                              }
                     
                              // Créé par + temps
                              $pdf->SetXY($X,$Y);
                                 $pdf->Write(5,utf8_decode('Créé le : ' . $datatask['date'] . ' par ' . $datatask['name']));
                              $pdf->Ln();
                              // temps d'intervention si souhaité lors de la génération
                                 $pdf->Write(5,utf8_decode("Temps d'intervention : " . floor($datatask['actiontime'] / 3600) .  str_replace(":", "h",gmdate(":i", $datatask['actiontime'] % 3600))));
                              $pdf->Ln();
                              $sumtask += $datatask['actiontime'];
                        }
                     } 
                  }
                  // --------- TACHES

                  if($query_surveyid_data->suivis_private == 1){
                     $is_private_suivis = "AND is_private = 0";
                  }else{
                     $is_private_suivis = "";
                  }
                  // --------- SUIVI
                  $query = $DB->query("SELECT glpi_itilfollowups.id FROM glpi_itilfollowups INNER JOIN glpi_users ON glpi_itilfollowups.users_id = glpi_users.id WHERE items_id = $ticketid");
                  $sumsuivi = 0;

                  while ($datasumsuivi = $DB->fetchArray($query)) {
                     if(!empty($datasumsuivi['id'])) $sumsuivi++;  
                  } 
                  
                  if ($sumsuivi > 0){
                     $querysuivi = $DB->query("SELECT glpi_itilfollowups.id, content, date, name FROM glpi_itilfollowups INNER JOIN glpi_users ON glpi_itilfollowups.users_id = glpi_users.id WHERE items_id = $ticketid $is_private_suivis");
                        $pdf->Ln(5);
                     $pdf->Cell(190,5,utf8_decode('Suivi(s) : '.$sumsuivi),1,0,'L',true);
                        $pdf->Ln(2);

                        Session::addMessageAfterRedirect(__('test 3','rpauto'), false, ERROR);
                     while ($datasuivi = $DB->fetchArray($querysuivi)) {
                        //verifications que la variable existe
                        if(!empty($datasuivi['id'])){
                              
                              $pdf->Ln();
                              $pdf->MultiCell(0,5,ClearSpaceAuto(ClearHtmlAuto($datasuivi['content'])),1,'L');
                              $Y = $pdf->GetY();
                              $X = $pdf->GetX();
                              Session::addMessageAfterRedirect(__('test 4','rpauto'), false, ERROR);

                              if ($query_surveyid_data->suivis_img == 1){
                                 //récupération de l'ID de l'image s'il y en a une.
                                 $IdImg = $datasuivi['id'];
                        
                                 $querysuividoc = $DB->query("SELECT documents_id FROM glpi_documents_items WHERE items_id = $IdImg AND itemtype = 'ITILFollowup'");
                                 while ($datasuividoc = $DB->fetchArray($querysuividoc)) {
                                    if (isset($datasuividoc['documents_id'])){
                                          $iddoc = $datasuividoc['documents_id'];
                                          $ImgUrl = $DB->query("SELECT filepath FROM glpi_documents WHERE id = $iddoc")->fetch_object();
                                    }
                                 
                                    $img = GLPI_DOC_DIR.'/'.$ImgUrl->filepath;
                     
                                    if (file_exists($img)){
                                          $imageSize = getimagesize($img);
                                          $width = $imageSize[0];
                                          $height = $imageSize[1];
                     
                                          if($width != 0 && $height != 0){
                                          $taille = (100*$height)/$width;
                                          
                                             if($pdf->GetY() + $taille > 297-15) {
                                                      $pdf->AddPage();
                                                      $pdf->Image($img,$X,$pdf->GetY()+2,100,$taille);
                                                $pdf->Ln($taille + 5);
                                             }else{
                                                      $pdf->Image($img,$X,$pdf->GetY()+2,100,$taille);
                                                      $pdf->SetXY($X,$Y+($taille));
                                                $pdf->Ln();
                                             }  
                                          }
                                          $Y = $pdf->GetY();
                                          $X = $pdf->GetX();                
                                    }
                                 }
                              }
                     
                              // Créé par + temps
                              $pdf->SetXY($X,$Y);
                              $pdf->Write(5,utf8_decode('Créé le : ' . $datasuivi['date'] . ' par ' . $datasuivi['name']));
                              $pdf->Ln();
                        }         
                     } 
                  }
                  // --------- SUIVI

                  // --------- TEMPS D'INTERVENTION
                     $pdf->Ln(5);
                     $pdf->Cell(80,5,utf8_decode("Temps d'intervention total"),1,0,'L',true);
                     $pdf->Cell(110,5,utf8_decode(floor($sumtask / 3600) .  str_replace(":", "h",gmdate(":i", $sumtask % 3600))),1,0,'L');
                     $pdf->Ln(7);
                  // --------- TEMPS D'INTERVENTION

                  // --------- TEMPS DE TRAJET
                  if (Plugin::isPluginActive('rt') && $query_surveyid_data->route_time == 1) {
                        $sumroutetime = 0;
                        $timeroute = $DB->query("SELECT routetime FROM `glpi_plugin_rt_tickets` WHERE tickets_id = $ticketid");
                           while ($dataroutetime = $DB->fetchArray($timeroute)) {
                                 $sumroutetime += $dataroutetime['routetime'];
                           }
                           $pdf->Cell(80,5,utf8_decode('Temps de trajet total'),1,0,'L',true);
                           $pdf->Cell(110,5,utf8_decode(str_replace(":", "h", gmdate("H:i",$sumroutetime*60))),1,0,'L');
                           $pdf->Ln(7);
                  }
                  // --------- TEMPS DE TRAJET

                  $FileName           = date('Ymd-His')."_RA_Ticket_".$ticketid. ".pdf";
                  $Path               = 'C:\wamp64\www\glpi/files/_plugins/rp/rapports_auto/'.$FileName;
                  $pdf->Output($Path, 'F'); //enregistrement du pdf

               }//While 2 -------------------------------------------------------
         } //While 1 -------------------------------------------------------
      
      Session::addMessageAfterRedirect(__('Test END','rpauto'), false, ERROR);
      //self::sendMail();
   }

   static function sendMail() {


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



      /*

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
      }*/
   }
}
