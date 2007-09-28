<?php

  global $foodsoftpath, $foodsoftdir;

  // Verbindungseinstellungen fr den MySQL-Server und die MySQL-Datenbank
  // $db_server      MySQL-Server Hostname oder IP-Adresse (z.B. rdbms.strato.de)
  // $db_user        MySQL Benutzername 
  // $db_name      Name der MySQL-Datenbank
  // $db_pwd        MySQL Passwort
  $db_server =  "127.0.0.1";
  $db_name   = "nahrungskette";
  $db_user   = "nahrungskette";
  $db_pwd    = "leckerpotsdam"; 

  // verbindung gleich aufbauen:
  if (!($db = mysql_connect($db_server,$db_user,$db_pwd)) || !@MYSQL_SELECT_DB($db_name)) {
    echo "<html><body><h1>Datenbankfehler!</h1>Konnte keine Verbindung zur Datenbank herstellen... Bitte später nochmal versuchen.</body></html>";
    exit();
  }
  
  // die restliche konfiguration koennen wir aus der leitvariablen-tabelle lesen
  // (skripte koennen dann persistente variable einfach speichern, aendern, und
  //  an slave (im keller) uebertragen)
  //
  $leitvariable = mysql_query( "SELECT * FROM leitvariable" );
  if( ! $leitvariable ) { 
    echo "<html><body><h1>Datenbankfehler!</h1>Leitvariabeln nicht gefunden</body></html>";
    exit();
  }
  while( $row = mysql_fetch_array( $leitvariable ) ) {
    global $$row['name'];
    $$row['name'] = "{$row['value']}";
  }
  $foodsoftserver or $foodsoftserver = "unbekannt";

  // foodsoftpath: absoluter pfad im Server-Filesystem:
  //
  isset( $foodsoftpath ) or $foodsoftpath = realpath( dirname( __FILE__ ) . '/../' );

  // foodsoftdir: pfad relativ zur DOCUMENT_ROOT:
  // (hier benutzt: alle skripte werden per index.php?... aufgerufen!)
  //
  if( ! isset( $foodsoftdir ) ) {
    $foodsoftdir = ereg_replace( '/[^/]+$', '', $_SERVER['SCRIPT_NAME'] );
    //
    // (ausnahme: einige in ../windows/ werden direkt aufgerufen; ggf. abschneiden:)
    // (todo: aufrufe aller skripte in ../windows/ auf index.php?window=... umstellen!)
    //
    $foodsoftdir = ereg_replace( '/windows$', '', $foodsoftdir );
    //
    // (noch'ne ausnahme: aufruf aus dem wiki heraus...)
    //
    $foodsoftdir = ereg_replace( '/wiki$', '/foodsoft', $foodsoftdir );
  }

  $mysqlheute = date('Y') . '-' . date('m') . '-' . date('d');
  $mysqljetzt = $mysqlheute . ' ' . date('H') . ':' . date('i') . ':' . date('s');

?>
