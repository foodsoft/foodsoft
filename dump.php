<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// dump.php --- dump database structure
//
// This script must _not_ be accessible over the net during normal
// operation - it is for developers only, to dump the database
// structure in easily PHP-readable format.
//

header("Content-Type: text/plain");

exit(1);  // keep disabled when not needed

require_once('code/config.php');
if (file_exists('structure.php'))
  require_once('structure.php');

error_reporting(E_ALL);

$db = mysqli_connect($db_server,$db_user,$db_pwd);
$db_selected = mysqli_select_db( $db, $db_name );

$result = mysqli_query( $db, "select value from leitvariable where name='database_version'");
$database_version = mysqli_fetch_row($result)[0];
echo "<?php

// db version $database_version

";

$previous_tables = $tables ?? null;

$tables = array();
$result = mysqli_query( $db, "SHOW TABLES; " );

while( $row = mysqli_fetch_array( $result ) ) {
  // var_export( $row );
  $tables[] = $row[0];
}

echo '$tables = [
';

$tkomma = ' ';
foreach( $tables as $table ) {
  echo "$tkomma '$table' => [\n";
  $tkomma = ',';
  $tdkomma = ' ';
  if ($previous_tables)
  {
    echo "  $tdkomma 'updownload' => ", $previous_tables[$table]['updownload'] ?? true ? 'true' : 'false', "\n";
    $tdkomma = ',';
  }
  $result = mysqli_query( $db, "SHOW COLUMNS FROM $table; " );
  echo "  $tdkomma 'cols' => [\n";
  $ckomma = ' ';
  while( $row = mysqli_fetch_array( $result ) ) {
    echo "    $ckomma '{$row['Field']}' => [\n";
    echo "        'type' =>  \"{$row['Type']}\"\n"; // Double-Quotes für Typen wie "enum('1/2','3','4','5','freigestellt')" notwendig!
    echo "      , 'null' => '{$row['Null']}'\n";
    echo "      , 'default' => ", is_null($row['Default']) ? 'null'
                    : "'" . preg_replace( "/^'(.*)'$/", '$1', $row['Default'] ) . "'", "\n";
    echo "      , 'extra' => '{$row['Extra']}'\n";
    echo "      ]\n";
    $ckomma = ',';
  }
  echo "    ]\n";
  echo "  , 'indices' => [\n";
  $result = mysqli_query( $db, "SHOW INDEX FROM $table; " );
  $ikomma = ' ';
  $i = 1;
  $iname = '';
  $icols = '';
  while( $row = mysqli_fetch_array( $result ) ) {
    // var_export( $row );
    if( $iname == $row['Key_name'] ) {
      $icols .= ", {$row['Column_name']}";
    } else {
      if( $iname ) {
        echo "    $ikomma '$iname' => [ 'unique' => $iunique, 'collist' => '$icols' ]\n";
        $ikomma = ',';
      }
      $iname = $row['Key_name'];
      $icols = $row['Column_name'];
      $iunique = ( $row['Non_unique'] == '0' ? 1 : 0 );
    }
  }
  if( $iname ) {
    echo "    $ikomma '$iname' => [ 'unique' => $iunique, 'collist' => '$icols' ]\n";
  }

  echo "    ]\n";
  echo "  ]\n";
}

echo "];\n";

echo '?' . ">\n";

?>
