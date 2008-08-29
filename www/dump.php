<?php

header("Content-Type: text/plain");

// <!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
// <html>
// <head>
//   <title>Foodsoft - Setup Tool</title>
//   <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
//   <link rel='stylesheet' type='text/css' href='css/foodsoft.css'>
// </head>
// <body>
// <pre>


require_once('code/config.php');

$db = mysql_connect($db_server,$db_user,$db_pwd);
$db_selected = mysql_select_db( $db_name, $db );

$tables = array();
$result = mysql_query( "SHOW TABLES; " );

while( $row = mysql_fetch_array( $result ) ) {
  // var_export( $row );
  $tables[] = $row[0];
}

echo '$tables = array(
';

$tkomma = ' ';
foreach( $tables as $table ) {
  echo "$tkomma '$table' => array(\n";
  $tkomma = ',';

  $result = mysql_query( "SHOW COLUMNS FROM $table; " );
  echo "    'cols' => array(\n";
  $ckomma = ' ';
  while( $row = mysql_fetch_array( $result ) ) {
    echo "    $ckomma '{$row['Field']}' => array(\n";
    echo "        'type' =>  \"{$row['Type']}\"\n";
    echo "      , 'null' => '{$row['Null']}'\n";
    echo "      , 'default' => '{$row['Default']}'\n";
    echo "      , 'extra' => '{$row['Extra']}'\n";
    echo "      )\n";
    $ckomma = ',';
  }
  echo "    )\n";
  echo "    , 'indices' => array(\n";
  $result = mysql_query( "SHOW INDEX FROM $table; " );
  $ikomma = ' ';
  $i = 1;
  $iname = '';
  $icols = '';
  while( $row = mysql_fetch_array( $result ) ) {
    // var_export( $row );
    if( $iname == $row['Key_name'] ) {
      $icols .= ", {$row['Column_name']}";
    } else {
      if( $iname ) {
        echo "      $ikomma '$iname' => array( 'unique' => $iunique, 'collist' => '$icols' )\n";
        $ikomma = ',';
      }
      $iname = $row['Key_name'];
      $icols = $row['Column_name'];
      $iunique = ( $row['Non_unique'] == '0' ? 1 : 0 );
    }
  }
  if( $iname ) {
    echo "      $ikomma '$iname' => array( 'unique' => $iunique, 'collist' => '$icols' )\n";
  }

  echo "    )\n";
  echo "  )\n";
}

echo ");";

?>
