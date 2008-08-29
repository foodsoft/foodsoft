<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
  <title>Foodsoft - Setup Tool</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='css/foodsoft.css'>
</head>
<body>
<h1>Foodsoft --- Setup Tool</h1>
<form name='setup_form' action='setup.php' method='post'>

<?php

require_once('code/config.php');

// $db_pwd = 

?>
<h1>Datenbankeinstellungen</h1>
  <table>
    <tr>
      <th>Server:</th>
      <td><? echo $db_server; ?></td>
    </tr>
    <tr>
      <th>Datenbank:</th>
      <td><? echo $db_name; ?></td>
    </tr>
    <tr>
      <th>User:</th>
      <td><? echo $db_user; ?></td>
    </tr>
    <tr>
      <th>Password:</th>
      <td><input type='password' size='20' value='12345' name='db_pwd'></td>
    </tr>
    <tr>
      <th>mysql_connect():</th>
<?

$db = mysql_connect($db_server,$db_user,$db_pwd);
if( ! $db ) {
  ?>
    <td class='warn'>Fehlgeschlagen!</td>
    </tr>
    </table>
    <div class='alert'>
      mysql_connect() failed: could not connect to MySQL server.
      Please check and correct your settings in code/config.php and try again!
    </div>
    </body>
    </html>
  <?
  exit(1);
}

?>
  <td class='ok'>
    OK
  </td>
  </tr>
  <tr>
    <th>mysql_select_db():</th>
<?

$db_selected = mysql_select_db( $db_name, $db );
if( ! $db_selected ) {
  ?>
    <td class='warn'>Fehlgeschlagen!</td>
    </tr>
    </table>
    <div class='alert'>
      mysql_select_db() failed: could not select database <? echo $db_name; ?>.
      Please check and correct your settings in code/config.php and try again!
    </div>
    </body>
    </html>
  <?
  exit(1);
}
?>
  <td class='ok'>
    OK
  </td>
  </tr>
  </table>

  <h1>Tables:</h1>
  <table>
<?

$thead = "
  <tr>
    <th>Field</th>
    <th>Type</th>
    <th>Null</th>
    <th>Default</th>
    <th>Extra</th>
    <th>Status</th>
  </tr>
";


$tables = array(
  'leitvariable' => array(
     'fields' => array(
       'name' => array( 'type' => 'varchar(21)', 'null' => 'NO', 'default' => '', 'extra' => '' )
     , 'value' => array( 'type' => 'text', 'null' => 'NO', 'default' => '', 'extra' => '' )
     , 'local' => array( 'type' => 'tinyint(1)', 'null' => 'NO', 'default' => '0', 'extra' => '' )
     , 'comment' => array( 'type' => 'text', 'null' => 'NO', 'default' => '', 'extra' => '' )
     )
   , 'indices' => array()
   )
, 'giebsnich' => array(
     'fields' => array()
   , 'indices' => array()
   )
, 'gesamtbestellungen' => array(
     'fields' => array()
   , 'indices' => array()
   )
);

$id = 0;

foreach( $tables as $table => $want ) {
  ?>
    <tr>
      <th colspan='6' style='padding-top:1em;text-align:center;'>table: <? echo $table; ?></th>
    </tr>
  <?
  $result = mysql_query( "SHOW COLUMNS FROM $table; " );
  if( ! $result ) {
    ?>
      <tr>
        <td class='warn' colspan='5'>
          failed: <code>SHOW COLUMNS FROM <? echo $table; ?></code>
        </td>
        <td class='warn' style='text-align:right;'>
          create table? <input type='checkbox' name='create_<? echo $table; ?>'>
        </td>
      </tr>
    <?
    continue;
  }
  echo $thead;
  $want_fields = $want['fields'];
  $want_indices = $want['indices'];
  while( $row = mysql_fetch_array( $result ) ) {
    $field = $row['Field'];
    ?>
      <tr>
        <td><? echo $field; ?></td>
        <td><? echo $row['Type']; ?></td>
        <td><? echo $row['Null']; ?></td>
        <td><? echo $row['Default']; ?></td>
        <td><? echo $row['Extra']; ?></td>
    <?
    if( isset( $want_fields[$field] ) ) {
      $want_field = $want_fields[$field];
      $s = '';
      $mismatch = false;
      if( $want_field['type'] != $row['Type'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_field['type']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $want_field['null'] != $row['Null'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_field['null']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $want_field['default'] != $row['Default'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_field['default']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $want_field['extra'] != $row['Extra'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_field['extra']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $mismatch ) {
        ?>
            <td class='warn'>mismatch</td>
          </tr>
          <tr>
            <td class='alert' style='text-align:right;'>should be:</td>
            <? echo $s; ?>
            <td class='alert' style='text-align:right;'>
              fix column?  <input type='checkbox' name='fix_col_<? echo $id; ?>'>
            <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
            <input type='hidden' name='col_<? echo $id; ?>' value='<? echo $field; ?>'>
            </td>
          </tr>
        <?
        $id++;
      } else {
        ?>
          <td class='ok'>OK</td>
          </tr>
        <?
      }
      unset( $want_fields[$field] );
    } else {
      ?>
          <td class='alert' style='text-align:right;'>
            obsolete column; delete? <input type='checkbox' name='delete_col_<? echo $id; ?>'>
            <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
            <input type='hidden' name='col_<? echo $id; ?>' value='<? echo $field; ?>'>
          </td>
        </tr>
      <?
      $id++;
    }
  }


}
    
























?>

</form>
</body>
</html>

