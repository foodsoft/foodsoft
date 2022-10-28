<?php
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
