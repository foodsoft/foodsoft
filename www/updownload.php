<?php

   // skript kann ueber index.php, aber auch direkt (fuer download!) aufgerufen werden:
   //
   require_once( dirname(__FILE__) . '/code/config.php' );
   require_once( "$foodsoftpath/code/err_functions.php" );
   require_once( "$foodsoftpath/code/zuordnen.php" );
   require_once( "$foodsoftpath/code/login.php" );
   // nur_fuer_dienst(1,3,4);

   $path = array_merge(
     split( ':', getenv("PATH") )
   , array( '/usr/local/bin', '/usr/local/sbin', '/usr/bin', '/usr/sbin', '/bin', '/sbin'
            , '/opt/lampp/bin', '/opt/lampp/mysql/bin' )
   );
   $mysqldump = false;
   foreach( $path as $d ) {
     if( is_executable( $d . '/mysqldump' ) ) {
       $mysqldump = $d . '/mysqldump';
       break;
     }
   }
   $mysql = false;
   foreach( $path as $d ) {
     if( is_executable( $d . '/mysql' ) ) {
       $mysql = $d . '/mysql';
       break;
     }
   }
   $gzip = false;
   foreach( $path as $d ) {
     if( is_executable( $d . '/gzip' ) ) {
       $mysqldump = $d . '/gzip';
       break;
     }
   }

//    if( ! $mysqldump ) {
//      echo "<div class='warn'>FEHLER: Programm mysqldump nicht gefunden!</div>";
//      exit();
//    }
//    if( ! $mysql ) {
//      echo "<div class='warn'>FEHLER: Programm mysql nicht gefunden!</div>";
//      exit();
//    }
//    if( ! $gzip ) {
//      echo "<div class='warn'>FEHLER: Programm gzip nicht gefunden!</div>";
//      exit();
//    }

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

   get_http_var( 'action' );

   function datenbank_sperren() {
     if( mysql_query( 'UPDATE leitvariable SET value="1" WHERE name="readonly"' ) ) {
       echo "<div class='ok'>Datenbank wurde gesperrt! <a href='index.php'>Weiter...</a></div>";
       return true;
     } else {
       echo "<div class='warn'>Sperrung der Datenbank fehlgeschlagen!</div>";
     }
     return false;
   }

   function datenbank_freigeben() {
     if( mysql_query( 'UPDATE leitvariable SET value="0" WHERE name="readonly"' ) ) {
       echo "<div class='ok'>Datenbank wurde freigegeben! <a href='index.php'>Weiter...</a></div>";
       return true;
     } else {
       echo "<div class='warn'>Freigabe fehlgeschlagen!</div>";
     }
     return false;
   }

   if( $action == 'release' ) {
     datenbank_freigeben();
     exit();
   }
   if( $action == 'lock' ) {
     datenbank_sperren();
     exit();
   }
   if( $action == 'upload' ) {
     $tmpfile = $_FILES['userfile']['tmp_name'];
     if(!$tmpfile) {
       echo "<div class='warn'>Keine Datei uebergeben!</div>";
       exit();
     }
     $command = "$gzip -dc $tmpfile | $mysql -h $db_server -u $db_user -p $db_pwd $db_name";
     system( $command, &$return );
     if( $return != 0 ) {
       echo "<div class='warn'>Hochladen fehlgeschlagen!</div>";
     } else {
       echo "<div class='warn'>Datenbank erfolgreich hochgeladen! <a href='index.php'>Weiter...</a></div>";
       datenbank_freigeben();
     }
     exit();
   }

   if( $action == 'download' ) {
     datenbank_sperren() or exit();

     $result = mysql_query( "SELECT * FROM leitvariable WHERE local=0" );
     if( ! $result ) {
       echo "<div class='warn'>Hochladen fehlgeschlagen!</div>";
       exit();
     }
     header("Content-Type: application/gzip");
     header("Content-Disposition: filename=$downloadname");
     $leit_sql="
        CREATE TABLE leitvariable (
          name varchar(20) NOT NULL,
          value text,
          local tinyint(1) NOT NULL default '0',
          comment text NOT NULL,
          PRIMARY KEY  ('name')
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
     ";
     $leit = mysql_query( "SELECT * FROM leitvariable WHERE local=0" );
     while( $leit && ( $row = mysql_fetch_array($leit) ) ) {
       $leit_sql = $leit_sql . "
         INSERT INTO leitvariable
            ( name, value, local, comment )
         VALUES
            ( {$row['name']}, `{$row['value']}`, 0, `{$row['comment']}` )
           WHERE name={$row['name']}
           ON DUPLICATE KEY UPDATE;
       ";
     }
     $leit_sql = $leit_sql . "
       INSERT INTO leitvariable
          ( name, value, local, comment )
       VALUES
          ( upload_stand, `$mysqljetzt`, 1, `Zeit der Erzeugung des letzten hochgeladenen Dumps` )
         WHERE name=upload_stand
         ON DUPLICATE KEY UPDATE;
     ";
     $leit_sql = $leit_sql . "
       INSERT INTO leitvariable
          ( name, value, local, comment )
       VALUES
          ( upload_von, `$foodsoftserver`, 1, `Server auf dem das letzte hochgeladene Dump erzeugt wurde` )
         WHERE name=upload_von
         ON DUPLICATE KEY UPDATE;
     ";
     $command = "
       {
         $mysqldump --opt $tables ;
         echo '
           $leit_sql
         ';
       } | $gzip
     ";
     system( $command, &$return );

     exit();
   }

   require_once( "$foodsoftpath/head.php" ); 
   echo "
     <h1>Up/Download der Datenbank...</h1>
   ";
   if( $readonly ) {
     echo "
       <table>
       <tr>
         <td>
           <label>Datenbank hochladen und anschliessend freigeben:</label>
         </td>
         <td>
           <form enctype='multipart/form-data' action='index.php?area=updownload&action=upload' method='post'>
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
           <form action='index.php?area=updownload&action=release' method='post'>
             <input type='submit' value='Freigeben'>
           </form>
         </td>
       </tr>
       </table>
     ";

   } else {
     echo "
       <table>
       <tr>
         <td>
           <label>Datenbank speichern als <kbd>$downloadname</kbd> und anschliessend sperren:</label>
         </td>
         <td>
           <form action='/updownload.php?action=download&nohead=1' method='post'>
             <input type='submit' value='Speichern'>
           </form>
         </td>
       </tr>
       <tr>
         <td>
           <label>Datenbank <em>ohne</em> Speichern sperren:</label>
         </td>
         <td>
           <form action='index.php?area=updownload&action=lock' method='post'>
             <input type='submit' value='Sperren'>
           </form>
         </td>
       </tr>
       </table>
     ";
   }
   echo "</table>";


?>
