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

         function exportZIP($SeePath, $pdfFiles, $i){

            $doc        = new Document();
            $zip        = new ZipArchive();
      
            // Créez un nouveau fichier zip
            $FileName = '/RapportPDF_'.$i.'_Export-'.date('Ymd-His').'.zip';
            $zipFileName = $SeePath . $FileName;
            if ($zip->open($zipFileName, ZipArchive::CREATE)!==TRUE) {
               exit("Impossible d'ouvrir le fichier <$zipFileName>\n");
            }
      
            // Ajoutez les fichiers PDF au fichier zip
            foreach($pdfFiles as $pdfFile) {
               $zip->addFile($pdfFile, basename($pdfFile));
            }
      
            // Fermez le fichier zip
            $zip->close();
      
            $input = ['name'        => addslashes('Rapport PDF : Export massif du - ' . date("Y-m-d à H:i:s")),
                      'filename'    => addslashes($FileName),
                      'filepath'    => addslashes('_plugins/rp/rapportsMass' . $FileName),
                      'mime'        => 'application/zip',
                      'users_id'    => Session::getLoginUserID(),
                      //'entities_id' => $ticket_entities->entities_id,
                      //'tickets_id'  => $Ticket_id,
                      'is_recursive'=> 1];
      
            if($NewDoc = $doc->add($input)){
               $zip = $zipFileName;
               Session::addMessageAfterRedirect(__("<br>Documents enregistrés avec succès : $zipFileName'>Télécharger les rapports en ZIP</a>",'rpauto'), false, INFO);
            }else{
               $zip = 'no';
               Session::addMessageAfterRedirect(__("Erreur lors de la création des rapports",'rpauto'), false, ERROR);
            }
            return $zip;
         }
      
      // definition de la date et heure actuelle
      date_default_timezone_set('Europe/Paris');
         $CurrentDate = date("Y-m-d H:i:s");
         $i = 0;

         $query_surveyid = $DB->query("SELECT id FROM glpi_plugin_rpauto_surveys WHERE is_active = 1;");
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
               if($query_surveyid_data->is_recursive == 0){ // not recursive
                  $query_ticket_close_and_answer = $DB->query("SELECT * FROM glpi_tickets WHERE (entities_id = $query_surveyid_data->entities_id) AND ((solvedate BETWEEN '$OldDate' AND '$CurrentDate') OR (closedate BETWEEN '$OldDate' AND '$CurrentDate'));");
               }else{ // recursive

                  $OtherEntities = "";
                  $OtherEntity = $DB->query("SELECT * FROM glpi_entities WHERE entities_id = $query_surveyid_data->entities_id;");
                  while ($OtherEntityData = $DB->fetchArray($OtherEntity)) {
                     $OtherEntityID = $OtherEntityData['id'];
                     $OtherEntities .= " OR entities_id = ".$OtherEntityID;
                  }
                  $query_ticket_close_and_answer = $DB->query("SELECT * FROM glpi_tickets WHERE entities_id = $query_surveyid_data->entities_id $OtherEntities AND (solvedate BETWEEN '$OldDate' AND '$CurrentDate' OR closedate BETWEEN '$OldDate' AND '$CurrentDate');");
               }
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
                  $pdf->Cell(140,5,utf8_decode($SOCIETY),1,0,'L');
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

                 
                  $FileName           = date('Ymd-His')."_RA_Ticket_".$ticketid.".pdf";
                  $Path               = GLPI_PLUGIN_DOC_DIR.'/rp/rapports_auto/'.$FileName;
                  $pdf->Output($Path, 'F'); //enregistrement du pdf

                  // Ajoutez le chemin du fichier PDF au tableau
                  $pdfFiles[] = $Path;

               }//While 2 -------------------------------------------------------
         
               $SeePath = GLPI_PLUGIN_DOC_DIR."/rp/rapportsMass/";
               $zipFileName = exportZIP($SeePath, $pdfFiles, $i++);
      
               if($zipFileName != 'no'){
                  self::sendMail($zipFileName, $query_sel_mail->alternative_email, $surveyid, $OldDate, $CurrentDate);
               }
            } //While 1 -------------------------------------------------------            
   }

   static function balise($corps, $Balises){
      foreach($Balises as $balise) {
          $corps = str_replace($balise['Balise'], $balise['Value'], $corps);
      }
      return $corps;
   } 

   static function sendMail($doc, $email, $surveyid, $OldDate, $CurrentDate) {
      global $DB, $CFG_GLPI;

      // génération et gestion des balises
         //BALISES
         $Balises = array(
            array('Balise' => '##date.old##'        , 'Value' => $OldDate),
            array('Balise' => '##date.current##'    , 'Value' => $CurrentDate),
         );
      // génération et gestion des balises
      
      // génération du mail 
      $mmail = new GLPIMailer();

      $gabarit = $DB->query("SELECT gabarit FROM glpi_plugin_rpauto_surveys WHERE id = $surveyid")->fetch_object();
      $notificationtemplates_id = $gabarit->gabarit;
      $NotifMailTemplate = $DB->query("SELECT * FROM glpi_notificationtemplatetranslations WHERE notificationtemplates_id=$notificationtemplates_id")->fetch_object();
         $BodyHtml = html_entity_decode($NotifMailTemplate->content_html, ENT_QUOTES, 'UTF-8');
         $BodyText = html_entity_decode($NotifMailTemplate->content_text, ENT_QUOTES, 'UTF-8');

      $footer = $DB->query("SELECT value FROM glpi_configs WHERE name = 'mailing_signature'")->fetch_object();
      if(!empty($footer->value)){$footer = html_entity_decode($footer->value, ENT_QUOTES, 'UTF-8');}else{$footer='';}

      // For exchange
         $mmail->AddCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");

      if (empty($CFG_GLPI["from_email"])){
         // si mail expediteur non renseigné    
         $mmail->SetFrom($CFG_GLPI["admin_email"], $CFG_GLPI["admin_email_name"], false);
      }else{
         //si mail expediteur renseigné  
         $mmail->SetFrom($CFG_GLPI["from_email"], $CFG_GLPI["from_email_name"], false);
      }

      $mmail->AddAddress($email);
      $mmail->addAttachment($doc); // Ajouter un attachement (documents)
      $mmail->isHTML(true);

    // Objet et sujet du mail 
    $mmail->Subject = self::balise($NotifMailTemplate->subject, $Balises);
        $mmail->Body = GLPIMailer::normalizeBreaks(self::balise($BodyHtml, $Balises)).$footer;
        $mmail->AltBody = GLPIMailer::normalizeBreaks(self::balise($BodyText, $Balises)).$footer;

        // envoie du mail
         if(!$mmail->send()) {
               Session::addMessageAfterRedirect(__("Erreur lors de l'envoi du mail : " . $mmail->ErrorInfo,'rpauto'), false, ERROR);
         }else{
               Session::addMessageAfterRedirect(__("<br>Mail envoyé à " . $email,'rpauto'), false, INFO);
               date_default_timezone_set('Europe/Paris');
               $CurrentDate = date("Y-m-d H:i:s");

               $query_rpauto_send = $DB->query("SELECT * FROM glpi_plugin_rpauto_send WHERE survey_id = $surveyid")->fetch_object();
               if(empty($query_rpauto_send->id)){
                  $query= "INSERT INTO `glpi_plugin_rpauto_send` (`survey_id`, `send_from`, `send_to`, `date_creation`) 
                           VALUES ($surveyid ,'$OldDate' ,'$CurrentDate' ,'$CurrentDate' );";
                  $DB->query($query);
               }else{
                  $query= "UPDATE glpi_plugin_rpauto_send SET send_from = '$query_rpauto_send->send_to', send_to = '$CurrentDate' WHERE survey_id = $surveyid";
                  $DB->query($query);
               }
         }

      $mmail->ClearAddresses();
   }
}
