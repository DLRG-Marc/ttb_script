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


// Daten der Mysql-Datenbank
$GLOBALS['DB_SETTINGS']['HOST'] = 'mysql.dlrg.de';    // muss normalerweise nicht geändert werden
$GLOBALS['DB_SETTINGS']['PORT'] = '3306';             // muss normalerweise nicht geändert werden
$GLOBALS['DB_SETTINGS']['USER'] = '';                 // normalerweise Gliederungsnummer
$GLOBALS['DB_SETTINGS']['DATABASE'] = '';             // normalerweise Gliederungsnummer
$GLOBALS['DB_SETTINGS']['PASSWORD'] = '';             // Passwort der Datenbank

// Daten der Gliederung
$GLOBALS['HOME']['NAME'] = 'DLRG Musterhausen e.V.';      // auf eigenen DLRG Namen ändern
$GLOBALS['HOME']['TOKEN'] = '';                           // auf eigenen Token von Telgram ändern