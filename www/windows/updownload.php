<?php

assert( $angemeldet ) or exit();
nur_fuer_dienst(1,3,4);

setWikiHelpTopic( 'foodsoft:updownload' );

$path = array_merge(
  split( ':', getenv("PATH") )
, array( '/usr/local/bin', '/usr/local/sbin', '/usr/bin', '/usr/sbin', '/bin', '/sbin'
         , '/opt/lampp/bin', '/opt/lampp/mysql/bin' )
);
$mysqldump = false;
foreach( $path as $d ) {
  // if( is_executable( $d . '/mysqldump' ) ) {
  system( "test -x $d/mysqldump", &$rv );
  if( $rv == 0 ) {
    $mysqldump = $d . '/mysqldump';
    break;
  }
}
$mysql = false;
foreach( $path as $d ) {
  // if( is_executable( $d . '/mysql' ) ) {
  system( "test -x $d/mysql", &$rv );
  if( $rv == 0 ) {
    $mysql = $d . '/mysql';
    break;
  }
}
$gzip = false;
foreach( $path as $d ) {
  // if( is_executable( $d . '/gzip' ) ) {
  system( "test -x $d/gzip", &$rv );
  if( $rv == 0 ) {
    $gzip = $d . '/gzip';
    break;
  }
}
// echo "<pre>mysqldump: $mysqldump</pre>";
// echo "<pre>mysql: $mysql</pre>";
// echo "<pre>gzip: $gzip</pre>";

echo "<div class='warn'>Das Hochladen im Keller funktioniert zur Zeit leider nicht (wir arbeiten dran...): bitte druckt Euch die Verteillisten aus!</div>";
exit();

need( $mysqldump, "FEHLER: Programm mysqldump nicht gefunden!" );
need( $mysql, "FEHLER: Programm mysql nicht gefunden!" );
need( $gzip, "FEHLER: Programm gzip nicht gefunden!" );

need( $result = mysql_query( 'show tables' ), "FEHLER: 'show tables' fehlgeschlagen!";

$tables = '';
while( $row = mysql_fetch_array( $result ) ) {
  switch( $row[0] ) {
    // spezielle tabellen nicht uebertragen und sperren:
    case 'leitvariable':
    case 'log':
    case 'transactions':
      break;
    default:
      $tables = "$tables {$row[0]}";
      break;
  }
}

$downloadname = "foodsoft.$foodsoftserver." . date('Ymd.Hi') . ".sql" ;

get_http_var( 'action', 'w', '' );

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

if( $action == 'upload' ) {
  if($_FILES['userfile']['error']!=0){

    echo "<div class='warn'>Fehler mit Code: ".$_FILES['userfile']['error']." (<a href=http://de.php.net/manual/en/features.file-upload.errors.php>Fehlercodes</a>)</div>";
    //var_dump($_FILES);
    exit();
  }
  $tmpfile = $_FILES['userfile']['tmp_name'];
  if(!$tmpfile) {
    echo "<div class='warn'>Keine Datei uebergeben!</div>";
    //var_dump($_FILES);
    exit();
  }
//    exitcode 2 ist bei gzip auch erfolgreich!
//     $command = "
//       $gzip -dc $tmpfile | $mysql -h $db_server -u $db_user -p$db_pwd $db_name ;
//     " . 'a="${PIPESTATUS[*]}"; [ "$a" == "0 0" -o "$a" == "2 0" ]';
  $command = "$mysql -h $db_server -u $db_user -p$db_pwd $db_name -T 2>&1 < $tmpfile";
  system( $command, &$return );
  if( $return != 0 ) {
    echo "<div class='warn'>Hochladen fehlgeschlagen: $return $mysql $gzip</div>";
  } else {
    echo "<div class='ok'>Datenbank erfolgreich hochgeladen! <a href='index.php'>Weiter...</a></div>";
    datenbank_freigeben();
  }
  exit();
}

if( $action == 'download' ) {
  datenbank_sperren() or exit();

  $result = mysql_query( "SELECT * FROM leitvariable WHERE local=0" );
  if( ! $result ) {
    echo "<div class='warn'>Runterladen fehlgeschlagen!</div>";
    exit();
  }
  $leit_sql="
     CREATE TABLE IF NOT EXISTS leitvariable (
       name varchar(20) NOT NULL,
       value text,
       local tinyint(1) NOT NULL default 0,
       comment text NOT NULL,
       PRIMARY KEY  (name)
     ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
  ";
  $leit = mysql_query( "SELECT * FROM leitvariable WHERE local=0" );
  while( $leit && ( $row = mysql_fetch_array($leit) ) ) {
    $leit_sql = $leit_sql . "
      INSERT INTO leitvariable
         ( `name`, `value`, `local`, `comment` )
        VALUES
         ( \"{$row['name']}\", \"{$row['value']}\", 0, \"{$row['comment']}\" )
        ON DUPLICATE KEY UPDATE name=VALUES(name), value=VALUES(value), local=VALUES(local), comment=VALUES(comment);
    ";
  }
  $leit_sql = $leit_sql . "
    INSERT INTO leitvariable
       ( `name`, `value`, `local`, `comment` )
      VALUES
       ( \"upload_stand\", \"$mysqljetzt\", 1, \"Zeit der Erzeugung des zuletzt hochgeladenen Dumps\" )
      ON DUPLICATE KEY UPDATE name=VALUES(name), value=VALUES(value), local=VALUES(local), comment=VALUES(comment);
  ";
  $leit_sql = $leit_sql . "
    INSERT INTO leitvariable
       ( `name`, `value`, `local`, `comment` )
      VALUES
       ( \"upload_ursprung\", \"$foodsoftserver\", 1, \"Server auf dem das zuletzt hochgeladene Dump erzeugt wurde\" )
      ON DUPLICATE KEY UPDATE name=VALUES(name), value=VALUES(value), local=VALUES(local), comment=VALUES(comment);
  ";


     // FIXME: only for testing:
//     $tables = 'lieferanten pfandverpackungen';
//     $command = "
//       tar c $foodsoftdir \
//       && echo -n "---- cut me" && echo " here ----" \
//       && $mysqldump --opt -h $db_server -u $db_user -p$db_pwd $db_name $tables 2>&1 && echo ' $leit_sql'
//     ";
  $command = "
    $mysqldump --opt -h $db_server -u $db_user -p$db_pwd $db_name $tables 2>&1 && echo ' $leit_sql'
  ";
  // echo "command: <pre>$command</pre>";
  header("Content-Type: application/data");
  header("Content-Disposition: filename=$downloadname");
  system( $command, &$return );
  if( $return != 0 ) {
    echo "<div class='warn'>Abspeichern fehlgeschlagen!</div>";
    // FIXME: ^ ^ ^ ist jetzt vermutlich sinnlos; wie koennen wir das besser machen?
  }

  exit();
}


?> <h1>Up/Download der Datenbank...</h1> <?

open_table( 'layout' );
  if( $readonly ) {
      open_td();
        ?> Datenbank hochladen und anschliessend freigeben: <?
          qquad(); wikiLink("foodsoft:daten_auf_den_server_hochladen", "Wiki...");
      open_td();
        open_form( '', "enctype='multipart/form-data'", 'action=upload' );
          ?> <input name='userfile' type='file'> <?
          qquad(); submission_button( 'Hochladen' );
        close_form();
    open_tr();
      open_td( 'label', '', 'Datenbank <em>ohne</em> Upload wieder freigeben:' );
      open_td( '', '', fc_action( 'text=Freigeben', 'action=release' ) );

  } else {
      open_td();
          ?> Datenbank sperren und anschliessend speichern als <kbd><? echo $downloadname; ?>:<?
          wikiLink("foodsoft:daten_vom_server_runterladen", "Wiki...");
      open_td( '', '', fc_action( 'window=updownload', 'download=updownload,action=download' ) );
    open_tr();
      open_td( 'label', '', 'Datenbank <em>ohne</em> Speichern sperren:' );
      open_td( '', '', 'fc_action( 'text=Sperren', 'action=lock' );
  }
close_table();

?>
