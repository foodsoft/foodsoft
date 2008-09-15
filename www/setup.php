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
<input type='hidden' name='action' value='setup'>

<?php

require_once('code/config.php');
require_once('structure.php');

$problems = false;

//
// (1) check server runtime environment:
//

$foodsoft_path = realpath( dirname( __FILE__ ) );
$ruid = posix_getuid();
$euid = posix_geteuid();
$rgid = posix_getgid();
$egid = posix_getegid();

?>
<h2 style='padding:1em 0em 0ex 0em;'>HTTP Server:</h2>
  <table>
    <tr>
      <th>Name / Port:</th>
      <td><? echo getenv( 'SERVER_NAME' ) . ' / ' . getenv( 'SERVER_PORT' );  ?></td>
    </tr>
    <tr>
      <th>Software:</th>
      <td><? echo getenv( 'SERVER_SOFTWARE' ); ?></td>
    </tr>
    <tr>
      <th>Foodsoft Pfad:</th>
      <td><? echo $foodsoft_path; ?></td>
    </tr>
    <tr>
      <th>ruid / euid:</th>
      <td><? echo $ruid . ' / ' . $euid; ?></td>
    </tr>
    <tr>
      <th>rgid / egid:</th>
      <td><? echo $rgid . ' / ' . $egid; ?></td>
    </tr>
  </table>
 <?

//
// (2) check file system layout, permissions, ... (TODO: this is incomplete!)
//

function check_dir( $path ) {
  echo "check_dir: $path<br>";
  if( $path == 'CVS' or $path == 'attic' ) {

  }
  return true;
}

function check_file( $path ) {

  echo "check_file: $path<br>";

  return true;
}

function recurse_dir( $path ) {
  global $foodsoftdir, $ruid, $rgid, $euid, $egid;
  $dir = opendir( $path );
  if( $dir === FALSE ) {
    ?>
      <tr>
        <th class='warn'>
          Problem: cannot access directory
        </th>
        <td><kbd><? echo $path; ?></kbd></td>
      </tr>
      <tr>
        <td colspan='2' class='alert'>
          Suggestion:
              <? echo $foodsoftdir; ?> and all subdirectories below should have read and execute permission,
              but no write permissions, for the apache server process.
        </td>
      </tr>
    <?
    return false;
  }
  echo "hello";
  $ok = check_dir( $path );
  while( $path = readdir( $dir ) ) {
    echo "readdir: $path<br>";
    if( $path == '.' or $path == '..' )
      continue;
    if( is_dir( $path ) ) {
      $ok &= recurse_dir( $path );
    } else {
      $ok &= check_file( $path );
    }
  }
  return $ok;
}

if( ! $problems ) {
// funktioniert noch nicht:
// recurse_dir( $foodsoft_path );

}



//
// (3) check MySQL server connection
//

if( ! $problems ) {
do {
  ?>
    <h2 style='padding:1em 0em 0ex 0em;'>MySQL db Einstellungensettings:</h2>
      <table>
        <tr>
          <th>Server:</th>
            <? if( isset( $db_server ) ) { ?>
              <td class='ok'><? echo $db_server; ?></td>
            <? } else { $problems = true; ?>
              <td class='warn'>$db_server nicht gesetzt</td>
            <? } ?>
          </td>
        </tr>
        <tr>
          <th>Datenbank:</th>
            <? if( isset( $db_name ) ) { ?>
              <td class='ok'><? echo $db_name; ?></td>
            <? } else { $problems = true; ?>
              <td class='warn'>$db_ name nicht gesetzt</td>
            <? } ?>
          </td>
        </tr>
        <tr>
          <th>Benutzer:</th>
            <? if( isset( $db_user ) ) { ?>
              <td class='ok'><? echo $db_user; ?></td>
            <? } else { $problems = true; ?>
              <td class='warn'>$db_user nicht gesetzt</td>
            <? } ?>
          </td>
        </tr>
        <tr>
          <th>Password:</th>
          <? if( isset( $db_pwd ) ) { ?>
            <td class='ok'>(ein password ist gesetzt)</td>
          <? } else { $problems = true; ?>
            <td class='warn'>$db_pwd nicht gesetzt</td>
          <? } ?>
        </tr>
  <?
  if( $problems )
    break;
  ?>
    <tr>
      <th>mysql_connect():</th>
  <?
  $db = mysql_connect($db_server,$db_user,$db_pwd);
  if( $db ) {
    ?> <td class='ok'>Verbindung zum MySQL Server OK </td></tr> <?
  } else {
    ?>
      <td class='warn'>
        Verbindung zum MySQL Server fehlgeschlagen:
        <div class='warn'><? echo mysql_error(); ?></div>
      </dt>
    <?
    $problems = true;
  }
  ?> </tr> <?
  if( $problems )
    break;

  ?>
    <tr>
      <th>mysql_select_db():</th>
  <?
  $db_selected = mysql_select_db( $db_name, $db );
  if( $db_selected ) {
    ?> <td class='ok'>Verbindung zur Datenbank OK </td></tr> <?
  } else {
    ?>
      <td class='warn'>
        Verbindung zur Datenbank fehlgeschlagen:
        <div class='warn'><? echo mysql_error(); ?></div>
      </dt>
    <?
    $problems = true;
  }
  ?> </tr> <?
} while( 0 );

?> </table> <?

if( $problems ) {
  ?>
    <div class='alert'>
      Zugriff auf die Datenbank funktioniert nicht richtig.
      Bitte überprüfe die Einstellungen in code/config.php!
    </div>
  <?
}

}


//
// (4) database connection established: check tables, columns, indices:
//

if( ! $problems ) {

$sql = array();

function add_table( $want_table ) {
  global $tables, $sql;
  $s = "CREATE TABLE `$want_table` ( \n";
  $komma = ' ';
  foreach( $tables[$want_table]['cols'] as $col => $props ) {
    $s .= "$komma `$col` {$props['type']} ";
    if( $props['null'] == 'NO' ) {
      $s .= 'NOT NULL ';
    } else {
      $s .= 'NULL ';
    }
    if( isset( $props['default'] ) && ( $props['default'] !== '' ) ) {
      $s .= "default {$props['default']} ";
    }
    if( isset( $props['extra'] ) ) {
      $s .= $props['extra'];
    }
    $s .= "\n";
    $komma = ',';
  }
  foreach( $tables[$want_table]['indices'] as $want_index => $props ) {
    if( $want_index == 'PRIMARY' ) {
      $s .= ", PRIMARY KEY ( {$props['collist']} ) ";
    } else {
      $s .= ', ';
      if( $props['unique'] ) {
        $s .= "UNIQUE ";
      }
      $s .= "KEY `$want_index` ( {$props['collist']} );";
    }
  }
  $s .= ') ENGINE=MyISAM  DEFAULT CHARSET=utf8;';
  $sql[] = $s;
}

function add_col( $want_table, $want_col, $op = 'ADD' ) {
  global $tables, $sql;
  $col = $tables[$want_table]['cols'][$want_col];
  $type = $col['type'];
  $null = ( $col['null'] == 'NO' ? 'NOT NULL' : 'NULL' );
  $default = ( ( isset( $col['default'] ) && ( $col['default'] !== '' ) ) ? "default " . $col['default'] : '' );
  $extra = ( isset( $col['extra'] ) ? $col['extra'] : '' );
  $s = " ALTER TABLE $want_table $op COLUMN `$want_col` $type $null $default $extra;";
  $sql[] = $s;
}

function add_index( $want_table, $want_index ) {
  global $tables, $sql;
  $index = $tables[$want_table]['indices'][$want_index];
  $s = " ALTER TABLE $want_table ADD ";
  if( $want_index == 'PRIMARY' ) {
    $s .= "PRIMARY KEY ( {$index['collist']} )";
  } else {
    if( $index['unique'] ) {
      $s .= "UNIQUE ";
    }
    $s .= "KEY `$want_index` ( {$index['collist']} );";
  }
  $sql[] = $s;
}

function delete_table( $table ) {
  global $sql;
  $sql[] = "DROP TABLE $table; ";
}

function delete_col( $table, $col ) {
  global $sql;
  $sql[] = "ALTER TABLE $table DROP $col;";
}
function delete_index( $table, $index ) {
  global $sql;
  $sql[] = "ALTER TABLE $table DROP INDEX $index;";
}

function fix_col( $table, $col ) {
  add_col( $table, $col, 'MODIFY' );
}
function fix_index( $table, $index ) {
  delete_index( $table, $index );
  add_index( $table, $index );
}


if( $HTTP_POST_VARS['action'] == 'setup' ) {
  foreach( $HTTP_POST_VARS as $name => $value ) {
    $v = explode( '_', $name );
    switch( $v[0] ) {
      case 'add':
        switch( $v[1] ) {
          case 'table':
            add_table( $HTTP_POST_VARS['table_'.$v[2]] );
            break;
          case 'col':
            add_col( $HTTP_POST_VARS['table_'.$v[2] ], $HTTP_POST_VARS['col_'.$v[2]] );
            break;
          case 'index':
            add_index( $HTTP_POST_VARS['table_'.$v[2] ], $HTTP_POST_VARS['index_'.$v[2]] );
            break;
        }
        break;
      case 'delete':
        switch( $v[1] ) {
          case 'table':
            delete_table( $HTTP_POST_VARS['table_'.$v[2]] );
            break;
          case 'col':
            delete_col( $HTTP_POST_VARS['table_'.$v[2] ], $HTTP_POST_VARS['col_'.$v[2]] );
            break;
          case 'index':
            delete_index( $HTTP_POST_VARS['table_'.$v[2] ], $HTTP_POST_VARS['index_'.$v[2]] );
            break;
        }
        break;
      case 'fix':
        switch( $v[1] ) {
          case 'col':
            fix_col( $HTTP_POST_VARS['table_'.$v[2] ], $HTTP_POST_VARS['col_'.$v[2]] );
            break;
          case 'index':
            fix_index( $HTTP_POST_VARS['table_'.$v[2] ], $HTTP_POST_VARS['index_'.$v[2]] );
            break;
        }
        break;
    }
  }
}

if( count( $sql ) > 0 ) {
  ?>
    <h3 clas='alert' style='padding-top:2em;'>Korrekturen an der Datenbank:</h3>
    <table>
      <tr>
        <th>SQL Befehl:</th>
        <th>Ergebnis:</th>
      </tr>
  <?
  foreach( $sql as $s ) {
    ?>
      <tr>
        <td><pre> <? echo "$s\n"; ?></pre></td>
    <?
    $result = mysql_query( $s );
    if( $result ) {
      ?>
        <td class='ok'>OK</td>
        </tr>
      <?
    } else {
      ?>
        <td class='warn'>
          fehlgeschlagen:
          <? echo mysql_error(); ?>
        </td>
        </tr>
      <?
      $problems = true;
      break;
    }
  }
  ?> </table> <?
}


?>
  <h2 style='padding:1em 0em 0ex 0em;'>MySQL Tabellen:</h2>
  <table>
<?

$thead = "
  <tr>
    <th>Spalte</th>
    <th>Typ</th>
    <th>Null</th>
    <th>Default</th>
    <th>Extra</th>
    <th>Status</th>
  </tr>
";
$ihead = "
  <tr>
    <th>Name</th>
    <th colspan='3'>Spalte(n)</th>
    <th>Unique</th>
    <th>Status</th>
  </tr>
";


$id = 0;

foreach( $tables as $table => $want ) {
  ?>
    <tr>
      <th colspan='6' style='padding-top:1em;text-align:center;'>table: <? echo $table; ?></th>
    </tr>
  <?
  
  $sql = "SHOW COLUMNS FROM $table; ";
  $result = mysql_query( $sql );
  if( ! $result ) {
    ?>
      <tr>
        <td class='warn' colspan='5'>
          fehlgeschlagen: <code><? echo $sql; ?></code>
        </td>
        <td class='warn' style='text-align:right;'>
          Tabelle anlegen? <input type='checkbox' name='add_table_<? echo $id; ?>'>
          <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
        </td>
      </tr>
    <?
    $problems = true;
    $id++;
    continue;
  }
  echo $thead;
  $want_cols = $want['cols'];
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
    if( isset( $want_cols[$field] ) ) {
      $want_col = $want_cols[$field];
      $s = '';
      $mismatch = false;
      if( $want_col['type'] != $row['Type'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_col['type']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $want_col['null'] != $row['Null'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_cold['null']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $want_col['default'] != $row['Default'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_col['default']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $want_col['extra'] != $row['Extra'] ) {
        $mismatch = true;
        $s .= "<td class='warn'>{$want_col['extra']}</td>";
      } else {
        $s .= "<td>&nbsp;</td>";
      }
      if( $mismatch ) {
        ?>
            <td class='warn'>Fehler</td>
          </tr>
          <tr>
            <td class='alert' style='text-align:right;'>Sollwert:</td>
            <? echo $s; ?>
            <td class='alert' style='text-align:right;'>
              Spalte korrigieren? <input type='checkbox' name='fix_col_<? echo $id; ?>'>
            <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
            <input type='hidden' name='col_<? echo $id; ?>' value='<? echo $field; ?>'>
            </td>
          </tr>
        <?
        $problems = true;
        $id++;
      } else {
        ?>
          <td class='ok'>OK</td>
          </tr>
        <?
      }
      unset( $want_cols[$field] );
    } else {
      ?>
          <td class='alert' style='text-align:right;'>
            Spalte nicht benötigt; löschen? <input type='checkbox' name='delete_col_<? echo $id; ?>'>
            <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
            <input type='hidden' name='col_<? echo $id; ?>' value='<? echo $field; ?>'>
          </td>
        </tr>
      <?
      $id++;
    }
  }
  foreach( $want_cols as $want_col => $want_props ) {
    ?>
      <tr>
        <td class='warn'><? echo $want_col; ?></td>
        <td class='warn'><? echo $want_props['type']; ?></td>
        <td class='warn'><? echo $want_props['null']; ?></td>
        <td class='warn'><? echo $want_props['default']; ?></td>
        <td class='warn'><? echo $want_props['extra']; ?></td>
        <td class='alert' style='text-align:right;'>
          fehlende Spalte; hinzufügen? <input type='checkbox' name='add_col_<? echo $id; ?>'>
          <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
          <input type='hidden' name='col_<? echo $id; ?>' value='<? echo $want_col; ?>'>
        </td>
      </tr>
    <?
    $problems = true;
    $id++;
  }

  ?>
    <tr>
      <th colspan='6' style='text-align:left;'>indices:</th>
    </tr>
  <?
  echo $ihead;
  $result = mysql_query( "SHOW INDEX FROM $table; " );
  $iname = '';
  $icols = '';
  while( ( $row = mysql_fetch_array( $result ) ) or $iname ) {
    if( $row and ( $iname == $row['Key_name'] ) ) {
      $icols .= ", {$row['Column_name']}";
    } else {
      if( $iname ) {
        ?>
          <tr>
            <td><? echo $iname; ?></td>
            <td colspan='3'><? echo $icols; ?></td>
            <td><? echo $iunique; ?></td>
        <?
        if( isset( $want_indices[$iname] ) ) {
          $want_index = $want_indices[$iname];
          $s = '';
          $mismatch = false;
          if( $want_index['collist'] != $icols ) {
            $mismatch = true;
            $s .= "<td class='warn' colspan='3'>{$want_index['collist']}</td>";
          } else {
            $s .= "<td colspan='3'>&nbsp;</td>";
          }
          if( $want_index['unique'] != $iunique ) {
            $mismatch = true;
            $s .= "<td class='warn'>{$want_index['unique']}</td>";
          } else {
            $s .= "<td>&nbsp;</td>";
          }
          if( $mismatch ) {
            ?>
                <td class='warn'>Fehler</td>
              </tr>
              <tr>
                <td class='alert' style='text-align:right;'>Sollwert:</td>
                <? echo $s; ?>
                <td class='alert' style='text-align:right;'>
                  fix index?  <input type='checkbox' name='fix_index_<? echo $id; ?>'>
                <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
                <input type='hidden' name='index_<? echo $id; ?>' value='<? echo $iname; ?>'>
                </td>
              </tr>
            <?
            $problems = true;
            $id++;
          } else {
            ?>
              <td class='ok'>OK</td>
              </tr>
            <?
          }
          unset( $want_indices[$iname] );
        } else {
          ?>
              <td class='alert' style='text-align:right;'>
                Index nicht benötigt; löschen? <input type='checkbox' name='delete_index_<? echo $id; ?>'>
                <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
                <input type='hidden' name='index_<? echo $id; ?>' value='<? echo $iname; ?>'>
              </td>
            </tr>
          <?
          $id++;
        }
      }
      if( $row ) {
        $iname = $row['Key_name'];
        $icols = $row['Column_name'];
        $iunique = ( $row['Non_unique'] == '0' ? 1 : 0 );
      } else {
        $iname = '';
      }
    }
  }
  foreach( $want_indices as $want_index => $want_props ) {
    ?>
      <tr>
        <td class='warn'><? echo $want_index; ?></td>
        <td class='warn' colspan='3'><? echo $want_props['collist']; ?></td>
        <td class='warn'><? echo $want_props['unique']; ?></td>
        <td class='alert' style='text-align:right;'>
          fehlender Index; hinzufügen? <input type='checkbox' name='add_index_<? echo $id; ?>'>
          <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
          <input type='hidden' name='index_<? echo $id; ?>' value='<? echo $want_index; ?>'>
        </td>
      </tr>
    <?
    $problems = true;
    $id++;
  }

  ?>
    <tr>
      <td colspan='6' style='text-align:left;'>&nbsp;</td>
    </tr>
  <?
}


?> </table> <?

}


//
// (5) setup leitvariable database:
//

if( ! $problems ) {

$leitvariable = array(
  'fc_name' => array(
    'meaning' => 'Name der Foodcoop'
  , 'default' => 'Nahrungskette'
  )
, 'motd' => array(
    'meaning' => 'message of the day: wird auf der Startseite angezeigt'
  , 'default' => 'Willkommen bei der Nahrungskette!'
  )
,
  'readonly' => array(
    'meaning' => 'Datenbank schreibgeschuetzt setzen (sehr eingeschränkte bestimmte sind dennoch moeglich)'
  , 'default' => '0'
  )
, 'foodsoftserver' => array(
    'meaning' => 'Spitzname des Servers (default: $SERVER_NAME, siehe oben)'
  , 'default' => ''
  )
, 'sockelbetrag' => array(
    'meaning' => 'Sockeleinlage pro Gruppenmitglied'
  , 'default' => '6.00'
  )
, 'basar_id' => array(
    'meaning' => 'Nummer der speziellen Müll-Gruppe (traditionell: 13)'
  , 'default' => '13'
  )
, 'basar_id' => array(
    'meaning' => 'Nummer der speziellen Basar-Gruppe (traditionell: 99)'
  , 'default' => '99'
  )
);

  ?>
  <h2>Konfigurationsvariable</h2>
  <table>
    <tr>
      <th>Variable</th>
      <th>Bedeutung</th>
      <th>Wert</th>
    </tr>
  <?
  $result = mysql_query( "SELECT * FROM leitvariable" );

  $header_written = false;
  while( $row = mysql_fetch_array( $result ) ) {
    $name = $row['name'];
    if( ! isset( $leitvariable[$name] ) )
      continue;
    $props = $leitvariable[$name];
    if( ! $header_written ) {
      ?><th colspan='3'>Variable in der Datenbank:</th><?
      $header_written = true;
    }
    ?>
      <tr>
        <th><? echo $name; ?></th>
        <td><? echo $props['meaning']; ?></td>
        <td><input type='text' name='leitvariable_<?echo $name; ?> size='20' value='<? echo $row['value']; ?>'></td>
      </tr>
    <?
    $leitvariable[$name] = false;
  }

  $header_written = false;
  foreach( $leitvariable as $name => $props ) {
    if( ! $props )
      continue;    // schon oben ausgegeben
    if( ! $header_written ) {
      ?><th colspan='3' class='alert'>Variable NOCH NICHT in der Datenbank:
          <div class='small'>(werden beim Abspeichern neu eingetragen)</div>
        </th><?
      $header_written = true;
    }
    ?>
      <tr>
        <th><? echo $var; ?></th>
        <td><? echo $props['meaning']; ?></td>
        <td><input type='text' name='leitvariable_<?echo $name; ?> size='20' value='<? echo $props['default']; ?>'></td>
      </tr>
    <?
  }

  $result = mysql_query( "SELECT * FROM leitvariable" );
  $header_written = false;
  while( $row = mysql_fetch_array( $result ) ) {
    if( isset( $leitvariable[$row['name']] ) )
      continue;
    if( ! $header_written ) {
      ?><th colspan='3' class='alert'>unerwartete Variable in der Datenbank:
          <div class='small'>(sollte gelöscht werden, um Nebeneffekte zu vermeiden)</div>
        </th><?
      $header_written = true;
    }
    ?>
      <tr>
        <th><? echo $row['name']; ?></th>
        <td class='alert'>undefinierte Variable - löschen?
        </td>
        <td><? echo $row['value']; ?></td>
      </tr>
    <?
  }


  ?> </table> <?
}

?>

<div style='text-align:left;padding:1em 1em 2em 1em;'>
  <input type='submit' style='padding:1ex;' value='Speichern / Ausführen' title='Abspeichern und/oder &Auml;nderungen vornehmen'>
</div>

</form>
</body>
</html>

