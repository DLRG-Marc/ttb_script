<?php

/**
 * telegram termin Bot TTB
 * Copyright (C) 2017-2030 Marc Busse
 *
 * This script is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details
 * at <http://www.gnu.org/licenses/>. 
 *
 * @TTB version  0.0.1
 * @date - time  07.08.2017 - 19:00
 * @copyright    Marc Busse 2017-2030
 * @author       Marc Busse <http://www.eutin.dlrg.de>
 * @license      GPL
 */


  // Info
  // max 4096 char per messagetext
  //

  // Includes
  if( file_exists('ttb_globals_local.php') ) {
    require_once('ttb_globals_local.php');
  }
  else {
    require_once('ttb_globals.php');
  }
  require_once('classes_global.php');

  // Settings
  define('BOT_TOKEN', $GLOBALS['HOME']['TOKEN']);
  define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
  define('GLIEDERUNG', $GLOBALS['HOME']['NAME']);


  // Functionen:

  function parse_termine($result_rd) {
    // termindetails parsen
    $termin_ary = array();
    $search = array('ä','ü','ö','ß','Mon','Tue','Wed','Thu','Fri','Sat','Sun','Erste Hilfe Kurs');
    $replace = array('ae','ue','oe','ss','Mo','Di','Mi','Do','Fr','Sa','So','Erste-Hilfe-Kurs'); 
    while($row = $result_rd->fetch_assoc()) {
      $title = $row['title'];
      if((!is_null($row['endDate'])) && ($row['endDate'] > $row['startDate'])) {
        $date = date("D. d.m.Y",$row['startDate'])." bis ".date("D. d.m.Y",$row['endDate']);
      }
      else {
        $date = date("D. d.m.Y",$row['startDate']);
      }
      if($row['addTime']=="1") {
        if($row['endTime'] > $row['startTime']) {
          $date .= " von ".date("H:i",$row['startTime'])." bis ".date("H:i",$row['endTime']);
        }
        else {
          $date .= " um ".date("H:i",$row['startTime']);
        }
      }
      $location = $row['location'];
      if($row['alias']=='') {
        $row['alias'] = $row['id'];
      }
      $link = $_SERVER[ 'SERVER_NAME']."/index.php/termin/".$row['alias'].".html";
      $termin_ary[] = $title."%0A".$date."%0A".$location."%0A".$link;
      $termin_ary = str_replace($search,$replace,$termin_ary); 
      #echo $title."%0A".$date."%0A".$location."%0A".$link."<br>";
    }
    return $termin_ary;
  }

  function termin_list($remind_days) {
    // alle Termine raussuchen die im Erinnerungszeitraum liegen
    global $mysqli;
    if(($result_rd = $mysqli->query("SELECT ce.id, ce.title, ce.alias, ce.startDate, ce.endDate, ce.addTime, ce.startTime, ce.endTime, ce.location
      FROM tl_calendar cl INNER JOIN tl_calendar_events ce ON (cl.id = ce.pid) WHERE cl.protected != '1' AND ce.published = '1'
      AND ce.startDate = ".strtotime("today +".$remind_days."day")." ORDER BY ce.startDate ASC")) !== FALSE) {
      $termine = parse_termine($result_rd);
      $result_rd->free();
    }
    else {
      // lesen aus Tabelle nicht erfolgreich
      $termine = array("Es ist leider ein Fehler aufgetreten, leider konnte ich dir die Terminerinnerungen nicht senden.");
    }
    return $termine;
  }

  // Functionen Ende


  if(($result_rd = $mysqli->query("SELECT chat_id, remind_days FROM tg_user WHERE remind_days != '0'")) !== FALSE) {
    while($row = $result_rd->fetch_assoc()) {
      $chat_id = $row['chat_id'];
      $remind_days = $row['remind_days'];
      $termine = termin_list($remind_days);
      if(!empty($termine)) {
        if($remind_days == "1") {
          $s_text = "Ich erinnere dich an die folgenden Termine die in 1 Tag stattfinden:";
        }
        else {
          $s_text = "Ich erinnere dich an die folgenden Termine die in ".$remind_days." Tagen stattfinden:";
        }
        file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?text='.$s_text.'&parse_mode=HTML&chat_id='.$chat_id);
        foreach($termine as $termin) {
          #echo $termin;
          file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?text='.$termin.'&parse_mode=HTML&chat_id='.$chat_id);
          sleep(1);
        }
      }
    }
    $result_rd->free();
  }
