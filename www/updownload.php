<?php

   assert( $angemeldet ) or exit();
   nur_fuer_dienst(1,3,4);

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

   if( ! $mysqldump ) {
     echo "<div class='warn'>FEHLER: Programm mysqldump nicht gefunden!</div>";
     exit();
   }
   if( ! $mysql ) {
     echo "<div class='warn'>FEHLER: Programm mysql nicht gefunden!</div>";
     exit();
   }
   if( ! $gzip ) {
     echo "<div class='warn'>FEHLER: Programm gzip nicht gefunden!</div>";
     exit();
   }

   $result = mysql_query( 'show tables' );
   if( ! $result ) {
     echo "<div class='warn'>FEHLER: 'show tables' fehlgeschlagen!</div>";
     exit();
   }
   $tables = '';
   while( $row = mysql_fetch_array( $result ) ) {
     switch( $row[0] ) {
       // spezielle tabellen nicht uebertragen und sperren:
       case 'leitvariable':
       case 'log':
         break;
       default:
         $tables = "$tables {$row[0]}";
         break;
     }
   }

   $downloadname = "foodsoft.$foodsoftserver." . date('Ymd.Hi') . ".sql.gz" ;

   get_http_var( 'action', 'w' );

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
     $tmpfile = $_FILES['userfile']['tmp_name'];
     if(!$tmpfile) {
       echo "<div class='warn'>Keine Datei uebergeben!</div>";
       exit();
     }
     // exitcode 2 ist bei gzip auch erfolgreich!
     $command = "
       $gzip -dc $tmpfile | $mysql -h $db_server -u $db_user -p$db_pwd $db_name ;
     " . 'a="${PIPESTATUS[*]}"; [ "$a" == "0 0" -o "$a" == "2 0" ]';
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
     $command = "
       {
         $mysqldump --opt -h $db_server -u $db_user -p$db_pwd $db_name $tables 2>&1 &&
         echo '
           $leit_sql
         ';
       } | $gzip ;
     " . '[ "${PIPESTATUS[*]}" == "0 0" ]';
     // echo "command: <pre>$command</pre>";
     header("Content-Type: application/gzip");
     header("Content-Disposition: filename=$downloadname");
     system( $command, &$return );
     if( $return != 0 ) {
       echo "<div class='warn'>Abspeichern fehlgeschlagen!</div>";
       // FIXME: ^ ^ ^ ist jetzt vermutlich sinnlos; wie koennen wir das besser machen?
     }

     exit();
   }

   ?>
     <h1>Up/Download der Datenbank...</h1>
   <?


   if( $readonly ) {
	wikiLink("foodsoft:daten_auf_den_server_hochladen", "Wiki...");
     ?>
       <table>
       <tr>
         <td>
           <label>Datenbank hochladen und anschliessend freigeben:</label>
         </td>
         <td>
           <form enctype='multipart/form-data' action='<? echo self_url; ?>' method='post'>
             <input type='hidden' name='action' value='upload'>
             <input name='userfile' type='file'>
             <input type='submit' value='Hochladen'>
           </form>
         </td>
       </tr>
       <tr>
         <td>
           <label>Datenbank <em>ohne</em> Upload wieder freigeben:</label>
         </td>
         <td>
           <form action='<? echo self_url; ?>' method='post'>
             <input type='hidden' name='action' value='release'>
             <input type='submit' value='Freigeben'>
           </form>
         </td>
       </tr>
       </table>
     <?

   } else {
	wikiLink("foodsoft:daten_vom_server_runterladen", "Wiki...");
     ?>
       <table>
       <tr>
         <td>
           <label>Datenbank sperren und anschliessend speichern als <kbd><? echo $downloadname; ?></kbd>:</label>
         </td>
         <td>
           <form action='index.php?download=updownload' method='post'>
             <input type='hidden' name='action' value='download'>
             <input type='submit' value='Speichern'>
           </form>
         </td>
       </tr>
       <tr>
         <td>
           <label>Datenbank <em>ohne</em> Speichern sperren:</label>
         </td>
         <td>
           <form action='<? echo self_url; ?>' method='post'>
             <input type='hidden' name='action' value='lock'>
             <input type='submit' value='Sperren'>
           </form>
         </td>
       </tr>
       </table>
     <?
   }
   ?> </table> <?


?>
