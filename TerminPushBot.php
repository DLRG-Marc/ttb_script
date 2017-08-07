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

  // Classes
  $myFilter = new filter;

  // Settings
  define('BOT_TOKEN', $GLOBALS['HOME']['TOKEN']);
  define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');
  define('GLIEDERUNG', $GLOBALS['HOME']['NAME']);


  $content = file_get_contents("php://input");
  $result = json_decode($content, true);
  $message = $result["message"];
  $chat_id = $message["chat"]["id"];
  $r_text = $message["text"];
  #checkJSON($chat_id,$result);


  // Functionen:

  function checkJSON($chat_id,$result) {
    $myFile = "log.txt";
    $updateArray = print_r($result,TRUE);
    $fh = fopen($myFile, 'a') or die("can't open file");
    fwrite($fh, $chat_id ."\n\n");
    fwrite($fh, $updateArray."\n\n");
    fclose($fh);
  }

  function set_db_user($message) {
    global $mysqli;
    $chat_id = $message["chat"]["id"];
    $user_id = $message["from"]["id"];
    $user_name = $message["from"]["first_name"];
    if(($mysqli->query("INSERT INTO tg_user SET chat_id='".$chat_id."', user_id='".$user_id."', user_name='".$user_name."', starts='1'
      ON DUPLICATE KEY UPDATE starts=starts+1")) !== TRUE) {
      echo "FEHLER";
    }
    else {
      echo "<br>Betroffen:".$mysqli->affected_rows;
    }
  }

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

  function termin_list() {
    // alle Termine ab heute raussuchen
    global $mysqli;
    if(($result_rd = $mysqli->query("SELECT ce.id, ce.title, ce.alias, ce.startDate, ce.endDate, ce.addTime, ce.startTime, ce.endTime, ce.location
      FROM tl_calendar cl INNER JOIN tl_calendar_events ce ON (cl.id = ce.pid) WHERE cl.protected != '1' AND ce.published = '1'
      AND ce.startDate > ".strtotime("now")." ORDER BY ce.startDate ASC")) !== FALSE) {
      $termine = parse_termine($result_rd);
      $result_rd->free();
    }
    else {
      // lesen aus Tabelle nicht erfolgreich
      $termine = array("Es ist leider ein Fehler aufgetreten, versuche es noch einmal.");
    }
    return $termine;
  }

  function termin_next() {
    // nächsten Termin des aktuellen Jahres rausuchen
    global $mysqli;
    if(($result_rd = $mysqli->query("SELECT ce.id, ce.title, ce.alias, ce.startDate, ce.endDate, ce.addTime, ce.startTime, ce.endTime, ce.location
      FROM tl_calendar cl INNER JOIN tl_calendar_events ce ON (cl.id = ce.pid) WHERE cl.protected != '1' AND ce.published = '1'
      AND ce.startDate > ".strtotime("now")." ORDER BY ce.startDate ASC LIMIT 1")) !== FALSE) {
      $termine = parse_termine($result_rd);
      $result_rd->free();
    }
    else {
      // lesen aus Tabelle nicht erfolgreich
      $termine = array("Es ist leider ein Fehler aufgetreten, versuche es noch einmal.");
    }
    return $termine;
  }

  function parse_send_message($messageArray) {
    // array der zu sendende Nachricht parsen
    foreach($messageArray as $key=>$value) {
      $s_message .= '&'.$key.'='.$value;
    }
    $s_message = '/sendMessage?'.ltrim($s_message,'&');
    return $s_message;
  }

  function termin_reminder_setting($message) {
    // Nachricht und Tastatur für die Einstellung der Terminerinnerung senden
    global $mysqli;
    $chat_id = $message["chat"]["id"];
    $user_id = $message["from"]["id"];
    $message_id = $message["message_id"];
    if(($mysqli->query("UPDATE tg_user SET message_id='".$message_id."' WHERE chat_id='".$chat_id."' AND user_id='".$user_id."'")) !== TRUE) {
      echo "FEHLER";
    }
    $text = "Wie viele Tage im Voraus soll ich dich erinnern?";
    $keyboard = [
      ['1','2','3'],
      ['5','7','keine']
    ];
    $reply_markup = [
      'keyboard' => $keyboard,
      'resize_keyboard' => true,
      'one_time_keyboard' => true
    ];
    $reply_markup = json_encode($reply_markup);
    $s_message = [
      'chat_id' => $chat_id,
      'text' => $text,
      'parse_mode' => 'HTML',
      'reply_markup' => $reply_markup
    ];
    return $s_message;
  }

  function termin_reminder_setting_answer($message,$r_text) {
    // 
    global $mysqli;
    global $myFilter;
    $s_text = FALSE;
    $mysql_text = $myFilter->var_mysql($r_text);
    $chat_id = $message["chat"]["id"];
    $user_id = $message["from"]["id"];
    $message_id = $message["message_id"]-2;
    if($r_text == "1" OR $r_text == "2" OR $r_text == "3" OR $r_text == "5" OR $r_text == "7" OR $r_text == "keine") {
      if($r_text == "keine") {
        $mysql_text = "0";
      }
      if(($mysqli->query("UPDATE tg_user SET remind_days='".$mysql_text."' WHERE chat_id='".$chat_id."' AND user_id='".$user_id."'
        AND message_id='".$message_id."'")) === TRUE) {
        if($r_text == "keine") {
          $s_text = "Ich werde dich von nun an nicht mehr an Termine der " . GLIEDERUNG . "erinnern.";
        }
        else if($r_text == "1") {
          $s_text = "Ich erinnere dich ab nun 1 Tag vorher an alle Termine der " . GLIEDERUNG;
        }
        else {
          $s_text = "Ich erinnere dich ab nun ".$r_text." Tage vorher an alle Termine der " . GLIEDERUNG;
        }
      }
    }
    return $s_text;
  }

  // Functionen Ende


  if($r_text == "/start") {
    $s_text = "<b>Was kann dieser Bot?</b>%0A%0ADieser Bot kann dir die Termine der " . GLIEDERUNG . " zusenden und dich an Termine erinnern.%0A%0A";
    $s_text .= "/termin_list - Liste aller Termine ab heute%0A";
    $s_text .= "/termin_next - Den naechsten faellige Termin anzeigen%0A";
    $s_text .= "/termin_reminder - Terminerinnerungen einstellen";
    file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?text='.$s_text.'&parse_mode=HTML&chat_id='.$chat_id);
    set_db_user($message);
  }
  else if($r_text == "/termin_list") {
    $termine = termin_list();
    foreach($termine as $termin) {
      #echo $termin."<br>";
      file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?text='.$termin.'&parse_mode=HTML&chat_id='.$chat_id);
      sleep(1);
    }
  }
  else if($r_text == "/termin_next") {
    $termine = termin_next();
    foreach($termine as $termin) {
      #echo $termin."<br>";
      file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?text='.$termin.'&parse_mode=HTML&chat_id='.$chat_id);
    }
  }
  else if($r_text == "/termin_reminder") {
    $s_message = parse_send_message(termin_reminder_setting($message));
    #echo $s_message;
    file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . $s_message);
  }
  else {
    $s_text = termin_reminder_setting_answer($message,$r_text);
    if($s_text !== FALSE) {
      file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?text='.$s_text.'&parse_mode=HTML&chat_id='.$chat_id);
    }
    else {
      $s_text = "Ich habe dich nicht verstanden, bzw. dies ist keine gueltige Anweisung.";
      file_get_contents('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage?text='.$s_text.'&parse_mode=HTML&chat_id='.$chat_id);
    }
  }
