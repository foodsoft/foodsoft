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



// $db_pwd = 

?>
<h1>Database settings</h1>
  <table>
    <tr>
      <th>Server:</th>
      <td><? echo $db_server; ?></td>
    </tr>
    <tr>
      <th>Database:</th>
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
    <td class='warn'>Failed!</td>
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
    <td class='warn'>Failed!</td>
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
<?


//
// database connection established; we can now save settings, fix tables, etc....
//

function add_table( $want_table ) {
  global $tables;
  echo "<pre> add_table: $want_table\n";
  $s = "CREATE TABLE $want_table ( \n";
  $komma = ' ';
  foreach( $tables[$want_table]['cols'] as $col => $props ) {
    $s .= "$komma $col {$props['type']} ";
    if( $props['null'] == 'NO' ) {
      $s .= 'NOT NULL ';
    } else {
      $s .= 'NULL ';
    }
    if( $props['default'] ) {
      $s .= "default '{$props['default']}' ";
    }
    $s .= $props['extra'] . "\n";
    $komma = ',';
  }
  $s .= ') ENGINE=MyISAM  DEFAULT CHARSET=utf8';
  echo $s;
  echo "</pre>";
}

function add_col( $want_table, $want_col ) {
  echo "add_col: $want_table, $want_col";
}
function add_index( $want_table, $want_index ) {
  echo "add_index: $want_table, $want_index";
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
            
          
    



?>
  <h1>Tables:</h1>
  <table>
<?

$thead = "
  <tr>
    <th>Column</th>
    <th>Type</th>
    <th>Null</th>
    <th>Default</th>
    <th>Extra</th>
    <th>Status</th>
  </tr>
";
$ihead = "
  <tr>
    <th>Name</th>
    <th colspan='3'>Column(s)</th>
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
  $result = mysql_query( "SHOW COLUMNS FROM $table; " );
  if( ! $result ) {
    ?>
      <tr>
        <td class='warn' colspan='5'>
          failed: <code>SHOW COLUMNS FROM <? echo $table; ?></code>
        </td>
        <td class='warn' style='text-align:right;'>
          create table? <input type='checkbox' name='add_table_<? echo $id; ?>'>
          create table? <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
        </td>
      </tr>
    <?
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
      unset( $want_cols[$field] );
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
  foreach( $want_cols as $want_col => $want_props ) {
    ?>
      <tr>
        <td class='warn'><? echo $want_col; ?></td>
        <td class='warn'><? echo $want_props['type']; ?></td>
        <td class='warn'><? echo $want_props['null']; ?></td>
        <td class='warn'><? echo $want_props['default']; ?></td>
        <td class='warn'><? echo $want_props['extra']; ?></td>
        <td class='alert' style='text-align:right;'>
          missing column; add? <input type='checkbox' name='add_col_<? echo $id; ?>'>
          <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
          <input type='hidden' name='col_<? echo $id; ?>' value='<? echo $want_col; ?>'>
        </td>
      </tr>
    <?
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
                <td class='warn'>mismatch</td>
              </tr>
              <tr>
                <td class='alert' style='text-align:right;'>should be:</td>
                <? echo $s; ?>
                <td class='alert' style='text-align:right;'>
                  fix index?  <input type='checkbox' name='fix_index_<? echo $id; ?>'>
                <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
                <input type='hidden' name='index_<? echo $id; ?>' value='<? echo $field; ?>'>
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
          unset( $want_indices[$iname] );
        } else {
          ?>
              <td class='alert' style='text-align:right;'>
                obsolete index; delete? <input type='checkbox' name='delete_index_<? echo $id; ?>'>
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
          missing index; create? <input type='checkbox' name='add_index_<? echo $id; ?>'>
          <input type='hidden' name='table_<? echo $id; ?>' value='<? echo $table; ?>'>
          <input type='hidden' name='index_<? echo $id; ?>' value='<? echo $want_index; ?>'>
        </td>
      </tr>
    <?
    $id++;
  }



  ?>
    <tr>
      <td colspan='6' style='text-align:left;'>&nbsp;</td>
    </tr>
  <?
}


?>
</table>

<div style='text-align:left;padding:1em 1em 2em 1em;'>
  <input type='submit' style='padding:1ex;' value='Submit'>
</div>

</form>
</body>
</html>

