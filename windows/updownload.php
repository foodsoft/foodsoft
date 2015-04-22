<?php

assert( $angemeldet ) or exit();
nur_fuer_dienst(1,3,4);
need( $exportDB );

setWikiHelpTopic( 'foodsoft:updownload' );

$path = array_merge(
  split( ':', getenv("PATH") )
, array( '/usr/local/bin', '/usr/local/sbin', '/usr/bin', '/usr/sbin', '/bin', '/sbin'
         , '/opt/lampp/bin', '/opt/lampp/mysql/bin' )
);
$mysqldump = false;
foreach( $path as $d ) {
  // if( is_executable( $d . '/mysqldump' ) ) {
  system( "test -x $d/mysqldump", $rv );
  if( $rv == 0 ) {
    $mysqldump = $d . '/mysqldump';
    break;
  }
}
$mysql = false;
foreach( $path as $d ) {
  // if( is_executable( $d . '/mysql' ) ) {
  system( "test -x $d/mysql", $rv );
  if( $rv == 0 ) {
    $mysql = $d . '/mysql';
    break;
  }
}
$gzip = false;
foreach( $path as $d ) {
  // if( is_executable( $d . '/gzip' ) ) {
  system( "test -x $d/gzip", $rv );
  if( $rv == 0 ) {
    $gzip = $d . '/gzip';
    break;
  }
}
// echo "<pre>mysqldump: $mysqldump</pre>";
// echo "<pre>mysql: $mysql</pre>";
// echo "<pre>gzip: $gzip</pre>";

// echo "<div class='warn'>Up/download auf dem demo-server nicht möglich!</div>";
// exit();

need( $mysqldump, "FEHLER: Programm mysqldump nicht gefunden!" );
need( $mysql, "FEHLER: Programm mysql nicht gefunden!" );
need( $gzip, "FEHLER: Programm gzip nicht gefunden!" );

require_once( 'structure.php' );
$tablenames = '';
foreach( $tables as $key => $props ) {
  if( $props['updownload'] )
    $tablenames .= " $key ";
}

$downloadname = "foodsoft.$foodsoftserver." . date('Ymd.Hi') . ".sql" ;

get_http_var( 'action', 'w', '' );

function mount_usb() {
  global $usb_device;
  need( $usb_device );
  $cmd = "/bin/mount /usb";
  system( $cmd );
}
function umount_usb() {
  global $usb_device;
  need( $usb_device );
  system( "/bin/umount /usb" );
}

  
function datenbank_sperren() {
  if( mysql_query( 'UPDATE leitvariable SET value="1" WHERE name="readonly"' ) ) {
    return true;
  } else {
    echo "<div class='warn'>Sperrung der Datenbank fehlgeschlagen!</div>";
  }
  return false;
}

function datenbank_freigeben() {
  if( mysql_query( 'UPDATE leitvariable SET value="0" WHERE name="readonly"' ) ) {
    return true;
  } else {
    echo "<div class='warn'>Freigabe fehlgeschlagen!</div>";
  }
  return false;
}

if( $action == 'release' ) {
 if( datenbank_freigeben() ) {
   echo "<div class='ok'>Datenbank wurde freigegeben! <a href='index.php'>Weiter...</a></div>";
 }
 exit();
}
if( $action == 'lock' ) {
 if( datenbank_sperren() ) {
   echo "<div class='ok'>Datenbank wurde gesperrt! <a href='index.php'>Weiter...</a></div>";
 }
 exit();
}

// if( $action == 'upload' ) {
//   global $usb_device;
// 
//   if( $usb_device ) {
//     need_http_var( 'filename', 'R' );
//     mount_usb();
//     $input = file_get_contents( "/usb/$filename" );
//     umount_usb();
//   } else {
//     need( isset( $_FILES['userfile'] ), "keine Datei hochgeladen!" );
//     if($_FILES['userfile']['error']!=0){
//       error( "Fehler mit Code: ".$_FILES['userfile']['error']." (<a href=http://de.php.net/manual/en/features.file-upload.errors.php>Fehlercodes</a>)" );
//     }
//     $input = file_get_contents( $_FILES['userfile']['tmp_name'] );
//   }
//   $parts = preg_split( '/^-- :/m', $input );
//   need( isset( $parts[5] ) and ( $parts[5] == "end\n" ) , "Hochladen fehlgeschlagen (test 1)" );
//   $size = 0;
//   sscanf( $parts[2], "size: %u", $size );
//   need( $size > 1, "Hochladen fehlgeschlagen (test 2)" );
//   $md5 = false;
//   sscanf( $parts[3], "md5: %s", $md5 );
//   need( $md5, "Hochladen fehlgeschlagen (test 3)" );
//   $sql = $parts[4];
//   $s = strlen( $sql );
//   need( $s == $size, "Hochladen fehlgeschlagen: falsche Dateigroesse: $s statt $size" );
//   $m = hash( 'md5', $sql );
//   need( $m == $md5, "Hochladen fehlgeschlagen: falsche Pruefsumme: $m statt $md5" );
// 
//   file_put_contents( "/tmp/upload.sql", $input );
//   $command = "$mysql -h $db_server -u $db_user -p$db_pwd $db_name --default-character-set=utf8 < /tmp/upload.sql";
//   system( $command, $result );
//   logger( "upload: size: $size, md5: $md5" );
// 
//   div_msg( 'ok', "Datenbank hochgeladen! <a href='index.php?login=silentlogout'>Bitte neu anmelden...</a></div>" );
//   datenbank_freigeben();
// 
//   return;
// 
// }

if( $action == 'download' ) {
  global $leitvariable, $mysqljetzt, $foodsoftserver, $cookie;
  global $usb_device;

  // datenbank_sperren() or exit();

  $sql = "
     CREATE TABLE IF NOT EXISTS leitvariable (
       name varchar(20) NOT NULL,
       value text,
       comment text NOT NULL,
       PRIMARY KEY  (name)
     ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
  ";
  foreach( $leitvariable as $name => $props ) {
    if( $props['local'] )
      continue;
    $value = mysql_real_escape_string($$name);
    $comment = mysql_real_escape_string($props['comment']);
    $sql .= "
      INSERT INTO leitvariable
         ( `name`, `value`, `comment` )
        VALUES
         ( \"$name\", \"$value\", \"$comment\" )
        ON DUPLICATE KEY UPDATE value=\"$value\", comment=\"$comment\";
    ";
  }

  $command = "$mysqldump --opt -h $db_server -u $db_user -p$db_pwd $db_name --default-character-set=utf8 $tablenames 2>&1";
  // echo "command: <pre>$command</pre>";
  $sql .= shell_exec( $command ) . "\n";
  need( preg_match( "/$cookie/", $sql ), "mysql_dump fehlgeschlagen" ); // quick'n'dirty paranoia check...
  $size = strlen( $sql );
  $md5 = hash( 'md5', $sql );
  $data = "-- :foodsoft dump created at $mysqljetzt on $foodsoftserver\n-- :size: $size\n-- :md5: $md5\n-- :$sql-- :end\n";
  logger( "download: $downloadname, size: $size, md5: $md5" );
  if( $usb_device ) {
    mount_usb();
    file_put_contents( "/usb/$downloadname", $data );
    umount_usb();
    mount_usb();
    $paranoia_read = file_get_contents( "/usb/$downloadname" );
    umount_usb();
    if( $paranoia_read === $data )
      div_msg( 'ok', "Datenbank gespeichert!" );
    else
      div_msg( 'warn', "Speichern fehlgeschlagen!" );
    return;
  } else {
    header("Content-Type: application/data");
    header("Content-Disposition: filename=$downloadname");
    echo $data;
    exit();
  }
}


?> <h1>Download der Datenbank...</h1> <?php

open_table( 'layout' );
  global $usb_device;
  // if( $readonly ) {
  if( false ) { /* momentan ausser betrieb! */
      open_td();
        ?> Datenbank hochladen und anschliessend freigeben: <?php
          qquad(); wikiLink("foodsoft:daten_auf_den_server_hochladen", "Wiki...");
      open_td();
        if( $usb_device ) {
          mount_usb();
          $files = glob( "/usb/foodsoft.*" );
          // echo "files: " . var_export( $files );
          // return;
          umount_usb();
          if( $files ) {
            open_table( 'list' );
              open_th( '', '', 'Dateien:' );
              foreach( $files as $file ) {
                $name = basename( $file );
                open_tr();
                  open_td( '', '', fc_action( array( 'class' => 'hfref', 'text' => $name, 'confirm' => "Datei $name wirklich hochladen?" )
                                            , array( 'action' => 'upload', 'filename' => $name ) ) );
              }
            close_table();
          } else {
            div_msg( 'warn', 'keine Dateien gefunden (USB-Stick eingesteckt?)' );
          }
        } else {
          open_form( "enctype=multipart/form-data", 'action=upload' );
            ?> <input name='userfile' type='file'> <?php
            qquad(); submission_button( 'Hochladen' );
          close_form();
        }
    open_tr();
      open_td('medskip');
    open_tr();
      open_td( 'label', '', 'Datenbank <em>ohne</em> Upload wieder freigeben:' );
      open_td( '', '', fc_action( 'text=Freigeben,confirm=Datenbank wirklich freigeben?', 'action=release' ) );

  } else {
      open_td();
          ?> Datenbank <!-- sperren und --> runterladen: <?php
          wikiLink("foodsoft:daten_vom_server_runterladen", "Wiki...");
      open_td();
        if( $usb_device )
          $download = '';
        else
          $download = ',download=updownload';
        echo fc_action( "window=updownload,text=Runterladen,title=Download jetzt starten", "action=download".$download );
        echo " (wird gespeichert als <b>$downloadname</b>)";
    open_tr();
      open_td( 'kommentar', "colspan='2'", '(dient zur Zeit nur zur Datensicherung und zum Datenexport)' );
    open_tr();
      open_td('medskip');
//     open_tr();
//       open_td( 'label', '', 'Datenbank <em>ohne</em> Speichern sperren:' );
//       open_td( '', '', fc_action( 'text=Datenbank Sperren,confirm=Datenbank wirklich sperren?', 'action=lock' ) );
  }
close_table();

?>
