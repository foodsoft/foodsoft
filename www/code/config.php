<?php

  // DATEI: Diese Datei enthï¿½t allegmeine Einstellungen. Sie kann angepaï¿½ werden!


  // Verbindungseinstellungen fr den MySQL-Server und die MySQL-Datenbank
  // $db_server      MySQL-Server Hostname oder IP-Adresse (z.B. rdbms.strato.de)
  // $db_user        MySQL Benutzername 
  // $db_pwd        MySQL Passwort
  // $db_name      Name der MySQL-Datenbank
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
    $$row['name'] = "{$row['value']}";
  }

  // die generalpasswoerter (nur noch voruebergehend noetig):
  //
  $real_gruppen_pwd = "foodi";
  $real_lieferanten_pwd = "foodi";
  $real_produkte_pwd = "foodi";
  $real_bestellt_pwd = "foodi";
  $real_info_pwd = "foodi";

?>
