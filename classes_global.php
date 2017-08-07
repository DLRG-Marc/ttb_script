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


class filter
{
  public function var_var($input)
  {
    if(is_array($input)===TRUE) {
      foreach( $input as $index => $val ) {
        $input[$index] = trim(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
      }
    }
    else {
      $input = trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    return $input;
  }
  

  public function var_mysql($input)
  {
    global $mysqli;
    if(is_array($input)===TRUE) {
      foreach( $input as $index => $val ) {
        $input[$index] = trim(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
        $input[$index] = $mysqli->real_escape_string(stripslashes($input[$index]));
      }
    }
    else {
      $input = trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
      $input = $mysqli->real_escape_string(stripslashes($input));
    }
    return $input;
  }
}


  $mysqli = new mysqli($GLOBALS['DB_SETTINGS']['HOST'], $GLOBALS['DB_SETTINGS']['USER'], $GLOBALS['DB_SETTINGS']['PASSWORD'], $GLOBALS['DB_SETTINGS']['DATABASE']);
  $mysqli->query("SET NAMES 'utf8'");
