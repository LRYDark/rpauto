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

         function exportZIP($SeePath, $pdfFiles, $i, $is_recursive, $entities_id){

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
                      'entities_id' => $entities_id,
                      'is_recursive'=> $is_recursive];
      
            if($NewDoc = $doc->add($input)){
               $zip = $zipFileName;
               Session::addMessageAfterRedirect(__("Documents enregistrés",'rpauto'), false, INFO);
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

         $query_surveyid = $DB->doQuery("SELECT id FROM glpi_plugin_rpauto_surveys WHERE is_active = 1;");
         //While 1 -------------------------------------------------------
         while ($data = $DB->fetchArray($query_surveyid)) {
            $surveyid = $data['id'];
            $query_surveyid_data = $DB->doQuery("SELECT * FROM glpi_plugin_rpauto_surveys WHERE id = $surveyid")->fetch_object();

            // Récupértion du mail pour envoyé le PDF
            $query_sel_mail = $DB->doQuery("SELECT alternative_email FROM glpi_plugin_rpauto_surveysuser WHERE survey_id = $surveyid")->fetch_object();
            
            // Récupération des dates et heures
            $query_rpauto_send = $DB->doQuery("SELECT * FROM glpi_plugin_rpauto_send WHERE survey_id = $surveyid")->fetch_object();
            if(empty($query_rpauto_send->send_from)){
               $OldDate = date('Y-m-d H:i:s', strtotime('-1 month', strtotime($CurrentDate)));
            }else{
               $OldDate = $query_rpauto_send->send_from;
            }

            //requette pour recupéré les tickets cloturées ou solutionnées sur la periode donnée
               // attention modifier si recursif ou pas////////////////////////////////////////////////////////////////////////////
               if($query_surveyid_data->is_recursive == 0){ // not recursive
                  $query_ticket_close_and_answer = $DB->doQuery("SELECT * FROM glpi_tickets WHERE (entities_id = $query_surveyid_data->entities_id) AND ((solvedate BETWEEN '$OldDate' AND '$CurrentDate') OR (closedate BETWEEN '$OldDate' AND '$CurrentDate'));");
               }else{ // recursive

                  $OtherEntities = "";
                  $OtherEntity = $DB->doQuery("SELECT * FROM glpi_entities WHERE entities_id = $query_surveyid_data->entities_id;");
                  while ($OtherEntityData = $DB->fetchArray($OtherEntity)) {
                     $OtherEntityID = $OtherEntityData['id'];
                     $OtherEntities .= " OR entities_id = ".$OtherEntityID;
                  }
                  $query_ticket_close_and_answer = $DB->doQuery("SELECT * FROM glpi_tickets WHERE entities_id = $query_surveyid_data->entities_id $OtherEntities AND (solvedate BETWEEN '$OldDate' AND '$CurrentDate' OR closedate BETWEEN '$OldDate' AND '$CurrentDate');");
               }

               // Collect generated PDF paths for the current survey run
               $pdfFiles = [];

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
                        $glpi_tickets_infos = $DB->doQuery("SELECT * FROM glpi_tickets INNER JOIN glpi_entities ON glpi_tickets.entities_id = glpi_entities.id WHERE glpi_tickets.id = $ticketid")->fetch_object();
                        $glpi_plugin_rp_dataclient = $DB->doQuery("SELECT * FROM `glpi_plugin_rp_dataclient` WHERE id_ticket = $ticketid")->fetch_object();

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

                        $query = $DB->doQuery("SELECT documents_id FROM glpi_documents_items WHERE items_id = $ticketid AND itemtype = 'Ticket'");
                        while ($data3 = $DB->fetchArray($query)) {
                           if (isset($data3['documents_id'])){
                                 $iddoc = $data3['documents_id'];
                                 $ImgUrl = $DB->doQuery("SELECT filepath FROM glpi_documents WHERE id = $iddoc")->fetch_object();
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

                  if($query_surveyid_data->tasks_private == 0){
                     $is_private_tasks = "AND is_private = 0";
                  }else{
                     $is_private_tasks = "";
                  }

                  // --------- TACHES
                  $querytask = $DB->doQuery("SELECT glpi_tickettasks.id FROM glpi_tickettasks INNER JOIN glpi_users ON glpi_tickettasks.users_id = glpi_users.id WHERE tickets_id = $ticketid $is_private_tasks");
                  $sumtask = 0;

                  while ($datasumtask = $DB->fetchArray($querytask)) {
                     if(!empty($datasumtask['id'])) $sumtask++;  
                  }

                  if ($sumtask > 0){
                     $querytask = $DB->doQuery("SELECT glpi_tickettasks.id, content, date, name, actiontime FROM glpi_tickettasks INNER JOIN glpi_users ON glpi_tickettasks.users_id = glpi_users.id WHERE tickets_id = $ticketid $is_private_tasks");
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
                                 $querytaskdoc = $DB->doQuery("SELECT documents_id FROM glpi_documents_items WHERE items_id = $IdImg AND itemtype = 'TicketTask'");
                                 while ($datataskdoc = $DB->fetchArray($querytaskdoc)) {
                                    if (isset($datataskdoc['documents_id'])){
                                    $iddoc = $datataskdoc['documents_id'];
                                    $ImgUrl = $DB->doQuery("SELECT filepath FROM glpi_documents WHERE id = $iddoc")->fetch_object();
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

                  if($query_surveyid_data->suivis_private == 0){
                     $is_private_suivis = "AND is_private = 0";
                  }else{
                     $is_private_suivis = "";
                  }

                  // --------- SUIVI
                  $query = $DB->doQuery("SELECT glpi_itilfollowups.id FROM glpi_itilfollowups INNER JOIN glpi_users ON glpi_itilfollowups.users_id = glpi_users.id WHERE items_id = $ticketid $is_private_suivis");
                  $sumsuivi = 0;

                  while ($datasumsuivi = $DB->fetchArray($query)) {
                     if(!empty($datasumsuivi['id'])) $sumsuivi++;  
                  } 
                  
                  if ($sumsuivi > 0){
                     $querysuivi = $DB->doQuery("SELECT glpi_itilfollowups.id, content, date, name FROM glpi_itilfollowups INNER JOIN glpi_users ON glpi_itilfollowups.users_id = glpi_users.id WHERE items_id = $ticketid $is_private_suivis");
                        $pdf->Ln(5);
                     $pdf->Cell(190,5,utf8_decode('Suivi(s) : '.$sumsuivi),1,0,'L',true);
                        $pdf->Ln(2);

                     while ($datasuivi = $DB->fetchArray($querysuivi)) {
                        //verifications que la variable existe
                        if(!empty($datasuivi['id'])){
                              
                              $pdf->Ln();
                              $pdf->MultiCell(0,5,ClearSpaceAuto(ClearHtmlAuto($datasuivi['content'])),1,'L');
                              $Y = $pdf->GetY();
                              $X = $pdf->GetX();

                              if ($query_surveyid_data->suivis_img == 1){

                                 //récupération de l'ID de l'image s'il y en a une.
                                 $IdImg = $datasuivi['id'];
                        
                                 $querysuividoc = $DB->doQuery("SELECT documents_id FROM glpi_documents_items WHERE items_id = $IdImg AND itemtype = 'ITILFollowup'");
                                 while ($datasuividoc = $DB->fetchArray($querysuividoc)) {
                                    if (isset($datasuividoc['documents_id'])){
                                          $iddoc = $datasuividoc['documents_id'];
                                          $ImgUrl = $DB->doQuery("SELECT filepath FROM glpi_documents WHERE id = $iddoc")->fetch_object();
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
                        $timeroute = $DB->doQuery("SELECT routetime FROM `glpi_plugin_rt_tickets` WHERE tickets_id = $ticketid");
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

               if (!empty($pdfFiles)) {
                  $SeePath = GLPI_PLUGIN_DOC_DIR."/rp/rapportsMass/";
                  $zipFileName = exportZIP($SeePath, $pdfFiles, $i++, $query_surveyid_data->is_recursive, $query_surveyid_data->entities_id);
      
                  if($zipFileName != 'no'){
                     self::sendMail($zipFileName, $query_sel_mail->alternative_email, $surveyid, $OldDate, $CurrentDate);
                  }
               }
            } //While 1 -------------------------------------------------------            
   }

   // Remplace ta méthode existante
   static function balise($corps, $Balises) {
      if ($corps === null) return '';
      if (!isset($Balises) || !is_iterable($Balises)) return (string)$corps;

      foreach ($Balises as $b) {
         $tag = isset($b['Balise']) ? (string)$b['Balise'] : '';
         if ($tag === '') continue;
         $val = array_key_exists('Value', $b) ? (string)$b['Value'] : '';
         $corps = str_replace($tag, $val, (string)$corps);
      }
      return $corps;
   }

   // Petit helper interne pour normaliser les fins de ligne
   private static function normalize_eols(string $s): string {
      $s = str_replace("\0", '', $s);
      return preg_replace("/\r\n|\r|\n/u", "\r\n", $s);
   }

   // Remplace ta méthode existante
   static function sendMail($doc, $email, $surveyid, $OldDate, $CurrentDate) {
      global $DB, $CFG_GLPI;

      // --- Validation email destinataire ---
      $email = trim((string)$email);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         Session::addMessageAfterRedirect(
               __("Adresse e-mail destinataire invalide : ", 'rpauto') . $email,
               false,
               ERROR
         );
         return false;
      }

      // --- Balises ---
      $Balises = [
         ['Balise' => '##date.old##',     'Value' => (string)$OldDate],
         ['Balise' => '##date.current##', 'Value' => (string)$CurrentDate],
      ];

      // --- Récupération du gabarit (id depuis la table surveys) ---
      $surveyid = (int)$surveyid;
      $gabaRow = $DB->request([
         'SELECT' => ['gabarit'],
         'FROM'   => 'glpi_plugin_rpauto_surveys',
         'WHERE'  => ['id' => $surveyid],
         'LIMIT'  => 1
      ])->current();

      if (!is_array($gabaRow) || empty($gabaRow['gabarit'])) {
         Session::addMessageAfterRedirect(
               __("Erreur : aucun gabarit rattaché, e-mail non envoyé.", 'rpauto'),
               false,
               ERROR
         );
         return false;
      }

      $notificationtemplates_id = (int)$gabaRow['gabarit'];

      // --- Lecture du gabarit avec fallback de langue ---
      $Subject = $BodyHtml = $BodyText = '';
      $curLang = $_SESSION['glpilanguage'] ?? ($CFG_GLPI['language'] ?? 'fr_FR');
      $langs   = array_values(array_unique([$curLang, substr($curLang, 0, 2), '']));

      $order = new \QueryExpression(
         "FIELD(language,'" . implode("','", array_map('addslashes', $langs)) . "')"
      );

      $tplRow = $DB->request([
         'SELECT' => ['subject', 'content_text', 'content_html', 'language'],
         'FROM'   => 'glpi_notificationtemplatetranslations',
         'WHERE'  => [
               'notificationtemplates_id' => $notificationtemplates_id,
               'language'                 => $langs // IN (...)
         ],
         'ORDER'  => [$order],
         'LIMIT'  => 1
      ])->current();

      if (!is_array($tplRow)) {
         // Ultime recours : sans filtre de langue
         $tplRow = $DB->request([
               'SELECT' => ['subject', 'content_text', 'content_html', 'language'],
               'FROM'   => 'glpi_notificationtemplatetranslations',
               'WHERE'  => ['notificationtemplates_id' => $notificationtemplates_id],
               'LIMIT'  => 1
         ])->current();
      }

      if (!is_array($tplRow)) {
         Session::addMessageAfterRedirect(
               __("Erreur : aucun gabarit valide trouvé, e-mail non envoyé.", 'rpauto'),
               false,
               ERROR
         );
         return false;
      }

      $Subject  = (string)($tplRow['subject'] ?? '');
      $BodyText = isset($tplRow['content_text'])
                  ? html_entity_decode((string)$tplRow['content_text'], ENT_QUOTES, 'UTF-8') : '';
      $BodyHtml = isset($tplRow['content_html'])
                  ? html_entity_decode((string)$tplRow['content_html'], ENT_QUOTES, 'UTF-8') : '';

      // --- Footer (signature) ---
      $footerVal = '';
      $cfgRow = $DB->request([
         'SELECT' => ['value'],
         'FROM'   => 'glpi_configs',
         'WHERE'  => ['name' => 'mailing_signature'],
         'LIMIT'  => 1
      ])->current();
      if (is_array($cfgRow) && !empty($cfgRow['value'])) {
         $footerVal = html_entity_decode((string)$cfgRow['value'], ENT_QUOTES, 'UTF-8');
      }

      // --- Construction du mail (GLPIMailer / Symfony Mailer) ---
      $mmail = new GLPIMailer();
      $mmail->addCustomHeader("X-Auto-Response-Suppress: OOF, DR, NDR, RN, NRN");

      // From sécurisé avec fallback
      $fromEmail = !empty($CFG_GLPI['from_email'])
         ? (string)$CFG_GLPI['from_email']
         : (!empty($CFG_GLPI['admin_email']) ? (string)$CFG_GLPI['admin_email'] : 'no-reply@localhost');

      $fromName = $CFG_GLPI['from_email_name'] ?? $CFG_GLPI['admin_email_name'] ?? null;
      $fromName = (is_string($fromName) && $fromName !== '') ? $fromName : 'GLPI';

      $emailObj = $mmail->getEmail();
      $emailObj->from(new \Symfony\Component\Mime\Address($fromEmail, $fromName));
      $emailObj->to($email); // pas de "name" → évite null

      // Pièce jointe : uniquement si fichier local existant et "raisonnable"
      if (is_string($doc) && $doc !== '' && file_exists($doc)) {
         $size = filesize($doc);
         if ($size !== false && $size > 15 * 1024 * 1024) {
               // Préfixe d’avertissement si >15MB
               $Subject = "⚠️ " . ($Subject ?: "Notification GLPI");
         } else {
               $emailObj->attachFromPath($doc);
         }
      }

      // Sujet / Corps avec balises + normalisation EOL + footer
      if ($Subject !== '') {
         $mmail->Subject = self::balise($Subject, $Balises);
      }

      $html = self::normalize_eols(self::balise($BodyHtml, $Balises));
      $txt  = self::normalize_eols(self::balise($BodyText, $Balises));

      if ($footerVal !== '') {
         $html .= "<br>" . $footerVal;
         $txt  .= "\r\n" . strip_tags($footerVal);
      }

      $mmail->Body    = $html;
      $mmail->AltBody = $txt;

      // --- Envoi ---
      $ok = $mmail->send();
      if (!$ok) {
         Session::addMessageAfterRedirect(
               __("Erreur lors de l'envoi du mail : ", 'rpauto') . $mmail->ErrorInfo,
               false,
               ERROR
         );
      } else {
         Session::addMessageAfterRedirect(
               __("Mail envoyé à ", 'rpauto') . $email,
               false,
               INFO
         );

         // --- Journalisation des envois (insert/update) ---
         // NB : la timezone est normalement gérée par PHP/GLPI ; on force si ton contexte l’exige.
         // date_default_timezone_set('Europe/Paris');
         $now = date("Y-m-d H:i:s");

         $sendRow = $DB->request([
               'FROM'  => 'glpi_plugin_rpauto_send',
               'WHERE' => ['survey_id' => $surveyid],
               'LIMIT' => 1
         ])->current();

         if (!is_array($sendRow)) {
               // Première trace
               $DB->insert('glpi_plugin_rpauto_send', [
                  'survey_id'     => $surveyid,
                  'send_from'     => (string)$OldDate,
                  'send_to'       => (string)$now,
                  'date_creation' => (string)$now
               ]);
         } else {
               // Mise à jour : on fait glisser send_from -> ancien send_to
               $DB->update('glpi_plugin_rpauto_send', [
                  'send_from' => (string)$sendRow['send_to'],
                  'send_to'   => (string)$now
               ], [
                  'survey_id' => $surveyid
               ]);
         }
      }

      // Nettoyage
      $mmail->ClearAddresses();

      return $ok ?? false;
   }
}
