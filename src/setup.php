<?php
//
// setup.php --- setup tool for Foodsoft
//
// This script must _not_ be accessible over the net during normal
// operation - it is for installation and maintenance only!
//
?><!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
  <title>Foodsoft - Setup Tool</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='css/foodsoft.css'>
</head>
<body>
<h1>Foodsoft --- Setup Tool</h1>
<?php

require_once('code/config.php');

$remote_ip = getenv('REMOTE_ADDR');
if( $allow_setup_from and preg_match( '/^' . $allow_setup_from . '/', $remote_ip ) ) {
  true;
} else {
  ?>
    <div class='warn'>
      setup.php cannot be called from your IP, <?php echo $remote_ip; ?>.
      this can be configured in <code>code/config.php</code>!
    </div>
  <?php
  exit(1);
}

?>
<form name='setup_form' action='setup.php' method='post'>
<?php

$details = 'check_5'; // default: zeige leitvariable (wenn bis dahin alles OK)
if( isset( $_GET['details'] ) )
  $details = $_GET['details'];

$changes = array();
$js = '';
$problems = false;

function escape_val( $val ) {
  switch( $val ) {
    case 'current_timestamp()';
      return $val;
    default:
      return "'$val'";
  }
}

function check_1() {
  //
  // (1) check server runtime environment:
  //

  $foodsoft_path = realpath( dirname( __FILE__ ) );
  $ruid = posix_getuid();
  $euid = posix_geteuid();
  $rgid = posix_getgid();
  $egid = posix_getegid();

  ?>
    <table class='list'>
      <tr>
        <th>Name / Port:</th>
        <td><?php echo getenv( 'SERVER_NAME' ) . ' / ' . getenv( 'SERVER_PORT' );  ?></td>
      </tr>
      <tr>
        <th>Software:</th>
        <td><?php echo getenv( 'SERVER_SOFTWARE' ); ?></td>
      </tr>
      <tr>
        <th>Foodsoft Pfad:</th>
        <td><?php echo $foodsoft_path; ?></td>
      </tr>
      <tr>
        <th>ruid / euid:</th>
        <td><?php echo $ruid . ' / ' . $euid; ?></td>
      </tr>
      <tr>
        <th>rgid / egid:</th>
        <td><?php echo $rgid . ' / ' . $egid; ?></td>
      </tr>
    </table>
  <?php
  return 0;
}

function check_2() {
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
          <td><kbd><?php echo $path; ?></kbd></td>
        </tr>
        <tr>
          <td colspan='2' class='alert'>
            Suggestion:
                <?php echo $foodsoftdir; ?> and all subdirectories below should have read and execute permission,
                but no write permissions, for the apache server process.
          </td>
        </tr>
      <?php
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

  // tut noch nichts:
  // recurse_dir( $foodsoft_path );

  echo "(Baustelle! Hier werden bisher noch keine tests durchgefuehrt)";
  
  return 0;
}

function check_3() {
  //
  // (3) check MySQL server connection
  //
  global $db_handle, $db_server, $db_name, $db_user, $db_pwd;

  $problems = false;
  do {
    ?>
      <table class='list'>
        <tr>
          <th>Server:</th>
            <?php if( isset( $db_server ) ) { ?>
              <td class='ok'><?php echo $db_server; ?></td>
            <?php } else { $problems = true; ?>
              <td class='warn'>$db_server nicht gesetzt</td>
            <?php } ?>
          </td>
        </tr>
        <tr>
          <th>Datenbank:</th>
            <?php if( isset( $db_name ) ) { ?>
              <td class='ok'><?php echo $db_name; ?></td>
            <?php } else { $problems = true; ?>
              <td class='warn'>$db_ name nicht gesetzt</td>
            <?php } ?>
          </td>
        </tr>
        <tr>
          <th>Benutzer:</th>
            <?php if( isset( $db_user ) ) { ?>
              <td class='ok'><?php echo $db_user; ?></td>
            <?php } else { $problems = true; ?>
              <td class='warn'>$db_user nicht gesetzt</td>
            <?php } ?>
          </td>
        </tr>
        <tr>
          <th>Password:</th>
          <?php if( isset( $db_pwd ) ) { ?>
            <td class='ok'>(ein password ist gesetzt)</td>
          <?php } else { $problems = true; ?>
            <td class='warn'>$db_pwd nicht gesetzt</td>
          <?php } ?>
        </tr>
    <?php
    if( $problems )
      break;
    ?>
      <tr>
        <th>mysqli_connect():</th>
    <?php
    $db_handle = mysqli_connect($db_server,$db_user,$db_pwd);
    if( $db_handle ) {
      ?> <td class='ok'>Verbindung zum MySQL Server OK </td></tr> <?php
    } else {
      ?>
        <td class='warn'>
          Verbindung zum MySQL Server fehlgeschlagen:
          <div class='warn'><?php echo mysqli_error( $db_handle ); ?></div>
        </dt>
      <?php
      $problems = true;
    }
    ?> </tr> <?php
    if( $problems )
      break;

    ?>
      <tr>
        <th>mysqli_select_db():</th>
    <?php
    $db_selected = mysqli_select_db( $db_handle, $db_name );
    if( $db_selected ) {
      ?> <td class='ok'>Verbindung zur Datenbank OK </td></tr> <?php
    } else {
      ?>
        <td class='warn'>
          Verbindung zur Datenbank fehlgeschlagen:
          <div class='warn'><?php echo mysqli_error( $db_handle ); ?></div>
        </dt>
      <?php
      $problems = true;
    }
    ?> </tr> <?php
  } while( 0 );

  ?> </table> <?php

  if( $problems ) {
    ?>
      <div class='alert'>
        Zugriff auf die Datenbank funktioniert nicht richtig.
        Bitte überprüfe die Einstellungen in code/config.php!
      </div>
    <?php
  }

  return $problems;
}

function check_4() {
  global $db_handle, $tables, $changes;
  //
  // (4) database connection established: check tables, columns, indices:
  //

  $problems = false;
  require_once('structure.php');

  function add_table( $want_table ) {
    global $tables, $changes;
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
        $s .= 'default ' . escape_val( $props['default'] ) .' ';
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
        $s .= "KEY `$want_index` ( {$props['collist']} ) ";
      }
    }
    $s .= ') ENGINE=MyISAM  DEFAULT CHARSET=utf8;';
    $changes[] = $s;
  }

  function add_col( $want_table, $want_col, $op = 'ADD' ) {
    global $tables, $changes;
    $col = $tables[$want_table]['cols'][$want_col];
    $type = $col['type'];
    $null = ( $col['null'] == 'NO' ? 'NOT NULL' : 'NULL' );
    $default = ( ( isset( $col['default'] ) && ( $col['default'] !== '' ) ) ? "default " . escape_val( $col['default'] ) : '' );
    $extra = ( isset( $col['extra'] ) ? $col['extra'] : '' );
    $s = " ALTER TABLE $want_table $op COLUMN `$want_col` $type $null $default $extra;";
    $changes[] = $s;
  }

  function add_index( $want_table, $want_index ) {
    global $tables, $changes;
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
    $changes[] = $s;
  }

  function delete_table( $table ) {
    global $changes;
    $changes[] = "DROP TABLE $table; ";
  }

  function delete_col( $table, $col ) {
    global $changes;
    $changes[] = "ALTER TABLE $table DROP $col;";
  }
  function delete_index( $table, $index ) {
    global $changes;
    $changes[] = "ALTER TABLE $table DROP INDEX $index;";
  }

  function fix_col( $table, $col ) {
    add_col( $table, $col, 'MODIFY' );
  }
  function fix_index( $table, $index ) {
    delete_index( $table, $index );
    add_index( $table, $index );
  }

  if( $_POST['action'] == 'repair' ) {
    foreach( $_POST as $name => $value ) {
      $v = explode( '_', $name );
      switch( $v[0] ) {
        case 'add':
          switch( $v[1] ) {
            case 'table':
              add_table( $_POST['table_'.$v[2]] );
              break;
            case 'col':
              add_col( $_POST['table_'.$v[2] ], $_POST['col_'.$v[2]] );
              break;
            case 'index':
              add_index( $_POST['table_'.$v[2] ], $_POST['index_'.$v[2]] );
              break;
          }
          break;
        case 'delete':
          switch( $v[1] ) {
            case 'table':
              delete_table( $_POST['table_'.$v[2]] );
              break;
            case 'col':
              delete_col( $_POST['table_'.$v[2] ], $_POST['col_'.$v[2]] );
              break;
            case 'index':
              delete_index( $_POST['table_'.$v[2] ], $_POST['index_'.$v[2]] );
              break;
          }
          break;
        case 'fix':
          switch( $v[1] ) {
            case 'col':
              fix_col( $_POST['table_'.$v[2] ], $_POST['col_'.$v[2]] );
              break;
            case 'index':
              fix_index( $_POST['table_'.$v[2] ], $_POST['index_'.$v[2]] );
              break;
          }
          break;
      }
    }
  }
  if( count( $changes ) > 0 )
    return 0;

  ?> <table class='list'> <?php

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
    ?><tr><th colspan='6' style='padding-top:1em;text-align:center;'>table: <?php echo $table; ?></th></tr><?php

    $sql = "SHOW COLUMNS FROM $table; ";
    $result = mysqli_query( $db_handle, $sql );
    if( ! $result ) {
      ?>
        <tr>
          <td class='warn' colspan='5'>
            fehlgeschlagen: <code><?php echo $sql; ?></code>
          </td>
          <td class='warn' style='text-align:right;'>
            Tabelle anlegen? <input type='checkbox' name='add_table_<?php echo $id; ?>'>
            <input type='hidden' name='table_<?php echo $id; ?>' value='<?php echo $table; ?>'>
          </td>
        </tr>
      <?php
      $problems = true;
      $id++;
      continue;
    }
    echo $thead;
    $want_cols = $want['cols'];
    $want_indices = $want['indices'];
    while( $row = mysqli_fetch_array( $result ) ) {
      $field = $row['Field'];
      ?>
        <tr>
          <td><?php echo $field; ?></td>
          <td><?php echo $row['Type']; ?></td>
          <td><?php echo $row['Null']; ?></td>
          <td><?php echo $row['Default']; ?></td>
          <td><?php echo $row['Extra']; ?></td>
      <?php
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
              <?php echo $s; ?>
              <td class='alert' style='text-align:right;'>
                Spalte korrigieren? <input type='checkbox' name='fix_col_<?php echo $id; ?>'>
              <input type='hidden' name='table_<?php echo $id; ?>' value='<?php echo $table; ?>'>
              <input type='hidden' name='col_<?php echo $id; ?>' value='<?php echo $field; ?>'>
              </td>
            </tr>
          <?php
          $problems = true;
          $id++;
        } else {
          ?>
            <td class='ok'>OK</td>
            </tr>
          <?php
        }
        unset( $want_cols[$field] );
      } else {
        ?>
            <td class='alert' style='text-align:right;'>
              Spalte nicht benötigt; löschen? <input type='checkbox' name='delete_col_<?php echo $id; ?>'>
              <input type='hidden' name='table_<?php echo $id; ?>' value='<?php echo $table; ?>'>
              <input type='hidden' name='col_<?php echo $id; ?>' value='<?php echo $field; ?>'>
            </td>
          </tr>
        <?php
        $id++;
      }
    }
    foreach( $want_cols as $want_col => $want_props ) {
      ?>
        <tr>
          <td class='warn'><?php echo $want_col; ?></td>
          <td class='warn'><?php echo $want_props['type']; ?></td>
          <td class='warn'><?php echo $want_props['null']; ?></td>
          <td class='warn'><?php echo $want_props['default']; ?></td>
          <td class='warn'><?php echo $want_props['extra']; ?></td>
          <td class='alert' style='text-align:right;'>
            fehlende Spalte; hinzufügen? <input type='checkbox' name='add_col_<?php echo $id; ?>'>
            <input type='hidden' name='table_<?php echo $id; ?>' value='<?php echo $table; ?>'>
            <input type='hidden' name='col_<?php echo $id; ?>' value='<?php echo $want_col; ?>'>
          </td>
        </tr>
      <?php
      $problems = true;
      $id++;
    }

    ?><tr><th colspan='6' style='text-align:left;'>indices:</th></tr><?php
    echo $ihead;
    $result = mysqli_query( $db_handle, "SHOW INDEX FROM $table; " );
    $iname = '';
    $icols = '';
    while( ( $row = mysqli_fetch_array( $result ) ) or $iname ) {
      if( $row and ( $iname == $row['Key_name'] ) ) {
        $icols .= ", {$row['Column_name']}";
      } else {
        if( $iname ) {
          ?>
            <tr>
              <td><?php echo $iname; ?></td>
              <td colspan='3'><?php echo $icols; ?></td>
              <td><?php echo $iunique; ?></td>
          <?php
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
                  <?php echo $s; ?>
                  <td class='alert' style='text-align:right;'>
                    fix index?  <input type='checkbox' name='fix_index_<?php echo $id; ?>'>
                  <input type='hidden' name='table_<?php echo $id; ?>' value='<?php echo $table; ?>'>
                  <input type='hidden' name='index_<?php echo $id; ?>' value='<?php echo $iname; ?>'>
                  </td>
                </tr>
              <?php
              $problems = true;
              $id++;
            } else {
              ?>
                <td class='ok'>OK</td>
                </tr>
              <?php
            }
            unset( $want_indices[$iname] );
          } else {
            ?>
                <td class='alert' style='text-align:right;'>
                  Index nicht benötigt; löschen? <input type='checkbox' name='delete_index_<?php echo $id; ?>'>
                  <input type='hidden' name='table_<?php echo $id; ?>' value='<?php echo $table; ?>'>
                  <input type='hidden' name='index_<?php echo $id; ?>' value='<?php echo $iname; ?>'>
                </td>
              </tr>
            <?php
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
          <td class='warn'><?php echo $want_index; ?></td>
          <td class='warn' colspan='3'><?php echo $want_props['collist']; ?></td>
          <td class='warn'><?php echo $want_props['unique']; ?></td>
          <td class='alert' style='text-align:right;'>
            fehlender Index; hinzufügen? <input type='checkbox' name='add_index_<?php echo $id; ?>'>
            <input type='hidden' name='table_<?php echo $id; ?>' value='<?php echo $table; ?>'>
            <input type='hidden' name='index_<?php echo $id; ?>' value='<?php echo $want_index; ?>'>
          </td>
        </tr>
      <?php
      $problems = true;
      $id++;
    }
    ?><tr><td colspan='6' style='text-align:left;'>&nbsp;</td></tr><?php
  }

  ?> </table> <?php

  return $problems;
}

function check_5() {
  global $db_handle, $leitvariable, $changes;
  //
  // (5) setup leitvariable database:
  //

  $problems = false;
  require_once('leitvariable.php');
  $id = 1;

  if( $_POST['action'] == 'repair' ) {
    foreach( $_POST as $name => $value ) {
      $v = explode( '_', $name );
      if( $v[0] != 'leit' )
        continue;
      $action = $v[1];
      $id = $v[2];
      $name = $_POST['leit_name_'.$id];
      switch( $action ) {
        case 'set':
          $value = $_POST['leit_value_'.$id];
          $props = $leitvariable[$name];
          $local = $props['local'];
          $comment = $props['comment'];
          $runtime_editable = $props['runtime_editable'];
          $changes[] .= "
            INSERT INTO leitvariable ( name, value, comment )
            VALUES ( '$name', '$value', '$comment' )
            ON DUPLICATE KEY UPDATE value = '$value';
          ";
          break;
        case 'delete':
          $name = $_POST['leit_name_'.$id];
          $changes[] = "DELETE FROM leitvariable WHERE name='$name';";
      }
    }
  }
  if( count( $changes ) > 0 )
    return 0;

  ?>
  <table class='list'>
    <tr>
      <th>Variable</th>
      <th>Bedeutung</th>
      <th>Wert</th>
      <th>Aktion</th>
    </tr>
  <?php

  for( $runtime_editable = 0; $runtime_editable <= 1; ++$runtime_editable ) {
    if( $runtime_editable ) {
      ?>
        <th colspan='4'>Laufzeit-Konfiguration in der Datenbank:
          <div class='small'>Diese Variablen k&ouml;nnen jederzeit angepasst werden</div>
        </th>
      <?php
    } else {
      ?>
        <th colspan='4'>Installations-Konfiguration in der Datenbank:
          <div class='small'>Diese Variablen bitte nur bei Neuinstallation setzen, danach nicht mehr &auml;ndern!</div>
        </th>
      <?php
    }
    foreach( $leitvariable as $name => $props ) {
      if( $props['runtime_editable'] != $runtime_editable )
        continue;
      $rows = ( isset($props['rows']) ? $props['rows'] : 1 );
      $cols = ( isset($props['cols']) ? $props['cols'] : 20 );
      ?>
        <tr>
          <th><?php echo $name; ?></th>
          <td>
          <?php
            echo $props['meaning'];
            if( isset( $props['comment'] ) )
              echo "<div class='small'>".$props['comment']."</div>";
            $result = mysqli_query( $db_handle, "SELECT * FROM leitvariable WHERE name='$name'" );
            if( $result and ( $row = mysqli_fetch_array( $result ) ) ) {
              $value = $row['value'];
              $checked = '';
            } else {
              $value = $props['default'];
              ?><div class='warn'>Noch nicht in der Datenbank!</div><?php
              $checked = 'checked';
              $problems = true;
            }
          ?>
          </td><td>
            <?php if( $rows > 1 ) { ?>
              <textarea name='leit_value_<?php echo $id; ?>' rows='<?php echo $rows; ?>' cols='<?php echo $cols; ?>'
                onchange="document.getElementById('checkbox_<?php echo $id; ?>').checked = true;"
              ><?php echo $value; ?></textarea>
            <?php } else { ?>
              <input type='text' name='leit_value_<?php echo $id; ?>' size='<?php echo $cols; ?>' value='<?php echo $value; ?>'
                onchange="document.getElementById('checkbox_<?php echo $id; ?>').checked = true;"
              />
            <?php } ?>
            <input type='hidden' name='leit_name_<?php echo $id; ?>' value='<?php echo $name; ?>'>
          </td><td>
            <?php echo $checked ? "eintragen?" : "ändern?"; ?>
            <input id='checkbox_<?php echo $id; ?>' type='checkbox' name='leit_set_<?php echo $id; ?>' value='set' <?php echo $checked; ?>>
          </td>
        </tr>
      <?php
      $id++;
    }
  }

  $result = mysqli_query( $db_handle, "SELECT * FROM leitvariable" );
  $header_written = false;
  while( $row = mysqli_fetch_array( $result ) ) {
    if( isset( $leitvariable[$row['name']] ) )
      continue;
    if( ! $header_written ) {
      ?><th colspan='3' class='alert'>unerwartete Variable in der Datenbank:
          <div class='small'>(sollten gelöscht werden, um Nebeneffekte zu vermeiden)</div>
        </th><?php
      $header_written = true;
    }
    ?>
      <tr>
        <th><?php echo $row['name']; ?></th>
        <td class='alert'>undefinierte Variable</td>
        <td><?php echo $row['value']; ?></td>
        <td>
          löschen?
          <input type='checkbox' name='leit_delete_<?php echo $id; ?>' value='delete'>
          <input type='hidden' name='leit_name_<?php echo $id; ?>' value='<?php echo $row['name']; ?>'>
        </td>
      </tr>
    <?php
    $problems = true;
    $id++;
  }

  ?> </table> <?php

  return $problems;
}

function check_6() {
  global $db_handle, $changes;
  $problems = false;

  $result = mysqli_query( $db_handle, "SELECT * FROM leitvariable WHERE name = 'muell_id'; " );
  $row = mysqli_fetch_array( $result );
  if( $row ) {
    $muell_id = $row['value'];
  } else {
    $muell_id = false;
  }
  $result = mysqli_query( $db_handle, "SELECT * FROM leitvariable WHERE name = 'basar_id'; " );
  $row = mysqli_fetch_array( $result );
  if( $row ) {
    $basar_id = $row['value'];
  } else {
    $basar_id = false;
  }

  if( $muell_id and isset( $_POST['add_group_muell'] ) ) {
    $changes[] = "INSERT INTO bestellgruppen ( id, name, aktiv, passwort ) 
                  VALUES ( $muell_id, 'Bad Bank', 0, '*' )";
  }
  if( $basar_id and isset( $_POST['add_group_basar'] ) ) {
    $changes[] = "INSERT INTO bestellgruppen ( id, name, aktiv, passwort ) 
                  VALUES ( $basar_id, 'Basargruppe', 0, '*' )";
  }
  if( isset( $_POST['add_group_regular'] ) ) {
    $group_id = $_POST['group_id'];
    $group_name = $_POST['group_name'];
    $password = $_POST['group_password'];
    $urandom_handle = fopen( '/dev/urandom', 'r' );
    $bytes = 4;
    $salt = '';
    while( $bytes > 0 ) {
      $c = fgetc( $urandom_handle );
      $salt .= sprintf( '%02x', ord($c) );
      $bytes--;
    }
    $changes[] = "INSERT INTO bestellgruppen ( id, name, aktiv, passwort, salt ) 
                  VALUES ( $group_id, '$group_name', 1, '". crypt( $password, $salt) ."' , '$salt' )";
  }
  if( $changes )
    return false;

  ?>
    <table class='list'>
      <tr>
        <th>Gruppe</th>
        <th>Status</th>
        <th>Aktion</th>
      </tr>
      <tr>
        <td>Bad-Bank (Nr. <?php echo $muell_id; ?>)</td>
  <?php

  $result = mysqli_query( $db_handle, "SELECT * FROM bestellgruppen WHERE id=$muell_id; " );
  $row = mysqli_fetch_array( $result );
  if( $row ) {
    ?>
      <td class='ok'>eingetragen</td>
      <td>&nbsp;</td>
    <?php
  } else {
    $problems = true;
    ?>
      <td class='warn'>nicht vorhanden</td>
      <td class='alert'>
        eintragen? <input type='checkbox' name='add_group_muell' value='add_group_muell'>
      </td>
    <?php
  }
  ?>
      </tr>
      <tr>
        <td>'Basar'-Gruppe (Nr. <?php echo $basar_id; ?>)</td>
  <?php

  $result = mysqli_query( $db_handle, "SELECT * FROM bestellgruppen WHERE id=$basar_id; " );
  $row = mysqli_fetch_array( $result );
  if( $row ) {
    ?>
      <td class='ok'>eingetragen</td>
      <td>&nbsp;</td>
    <?php
  } else {
    $problems = true;
    ?>
      <td class='warn'>nicht vorhanden</td>
      <td class='alert'>
        eintragen? <input type='checkbox' name='add_group_basar' value='add_group_muell'>
      </td>
    <?php
  }
  ?>
      </tr>
      <tr>
        <td>Sonstige Gruppen</td>
  <?php
  $result = mysqli_query( $db_handle, "SELECT * FROM bestellgruppen " );
  $num = mysqli_num_rows( $result ) - 2;
  if( $num > 0 ) {
    ?>
      <td class='ok'><?php echo $num; ?> Gruppen eingetragen</td>
      <td>&nbsp;</td>
    <?php
  } else {
    $problems = true;
    ?>
      <td class='warn'>keine Gruppen vorhanden
        <div class='small'>
          Um dich in die Foodsoft einloggen zu k&ouml;nnen, sollte hier mindestens eine erste Gruppe
          eingetragen werden!
        </div>
      </td>
      <td>&nbsp;</td>
      </tr>
    <?php
  }
  ?>
  </table>
  <div style='padding:1em;'>
  <h4> Gruppe eintragen </h4>
  Hier kannst Du eine erste Gruppe eintragen, oder Gruppendaten &uuml;berschreiben:
  <ul>
    <li>
      Diese Funktion dient normalerweise nur zum Eintrag einer ersten Gruppe, als die
      du dich dann in die Foodsoft einloggen kannst - weitere Gruppen sind dann dort
      viel komfortabler anzulegen
    </li>
    <li>
      WARNUNG: schon vorhandene Daten einer Gruppe werden hier einfach kommentarlos &uuml;berschrieben
    </li>
    <li>
      Du kannst hier also im Notfall auch ein Gruppenpasswort neu setzen
    </li>
  </ul>
  ID: <input type='text' size='4' value='1' name='group_id'>
  &nbsp;
  Name: <input type='text' size='20' value='Die Pioniere' name='group_name'>
  &nbsp;
  Passwort: <input type='text' size='20' value='' name='group_password'>
  &nbsp;
  eintragen? <input type='checkbox' name='add_group_regular' value='add_group_regular'>
  </div>

  <?php
  return $problems;
}


$checks = array(
  'check_1' => 'HTTP Server / Laufzeitumgebung'
, 'check_2' => 'Installierte Dateien und Verzeichnisse / Zugriffsrechte'
, 'check_3' => 'Verbindung zum MySQL Server'
, 'check_4' => 'Datenbankstruktur'
, 'check_5' => 'Konfigurationsvariable'
, 'check_6' => 'Gruppenverwaltung'
);

foreach( $checks as $f => $title ) {
  ?>
    <h2 style='padding:1em 0em 0ex 0em;'><?php echo $title; ?>:</h2>
    <div id='details_<?php echo $f; ?>' style='display:none;'>
      <?php  $result = $f(); ?>
    </div>
    <div id='nodetails_<?php echo $f; ?>' style='display:block;'>
      <?php
        if( $result ) {
          ?> <div class='warn' style='padding:1ex;'> Fehler! <?php
        } elseif( $changes ) {
          ?> <div class='alert' style='padding:1ex;'> Korrekturen werden ausgef&uuml;hrt... <?php
        } else {
          ?> <div class='ok' style='padding:1ex;'> keine Fehler gefunden! <?php
        }
      ?>
      <a href='setup.php?details=<?php echo $f; ?>' style='margin:1ex;'>Details...</a></div>
    </div>
  <?php
  if( $result or ( $f == $details ) ) {
    $js .= "
      document.getElementById('details_$f').style.display = 'block';
      document.getElementById('nodetails_$f').style.display = 'none';
    ";
  } else {
    $js .= "
      document.getElementById('details_$f').style.display = 'none';
      document.getElementById('nodetails_$f').style.display = 'block';
    ";
  }
  if( $result ) {
    $problems = true;
    break;
  }
  if( count( $changes ) > 0 ) {
    break;
  }
}

if( count( $changes ) > 0 ) {
  ?>
    <h3 clas='alert' style='padding-top:2em;'>Korrekturen an der Datenbank:</h3>
    <table class='list'>
      <tr>
        <th>SQL Befehl:</th>
        <th>Ergebnis:</th>
      </tr>
  <?php
  foreach( $changes as $s ) {
    ?>
      <tr>
        <td><pre> <?php echo htmlspecialchars("$s\n"); ?></pre></td>
    <?php
    $result = false;
    $result = mysqli_query( $db_handle, $s );
    if( $result ) {
      ?>
        <td class='ok'>OK</td>
        </tr>
      <?php
    } else {
      ?>
        <td class='warn'>
          fehlgeschlagen:
          <div><?php echo mysqli_error($db_handle ); ?></div>
        </td>
        </tr>
      <?php
      $problems = true;
      break;
    }
  }
  ?> </table> <?php
}

if( count( $changes ) == 0 ) {
  ?>
  <input type='hidden' name='action' value='repair'>
  <div style='text-align:left;padding:1em 1em 2em 1em;'>
    <input type='submit' style='padding:1ex;' value='Abschicken' title='Abspeichern und/oder &Auml;nderungen vornehmen'>
  </div>
  <?php
} else {
  ?>
  <div style='text-align:left;padding:1em 1em 2em 1em;'>
    <input type='submit' style='padding:1ex;' value='Neu laden'>
  </div>
  <?php
}

?>

</form>
<script type='text/javascript'>
  <?php echo $js; ?>
</script>
</body>
</html>
