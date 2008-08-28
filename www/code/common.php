<?php

global $foodsoftpath, $foodsoftdir;   // noetig wenn aufruf aus wiki

require_once('code/config.php');

// schinke-server fuer (Terra-)kataloge    *** EXPERIMENTELL ***
// (bisher nicht sinnvoll, da keine bestellnummern geliefert werden!)
//
// $katalog_db_server = 'nahrungskette.fcschinke09.de';
// $katalog_db_name = 'sharedLists';
// $katalog_db_user = 'sharedLists_read';
// $katalog_db_pwd = 'XXXXXXXX';
//
// verbindung zum katalog-server zuerst aufbauen (die _zuletzt_ geoeffnete verbindung ist default!):
//
// $mysql_katalog_handle = mysql_connect( $katalog_db_server, $katalog_db_user, $katalog_db_pwd );
//
// if( $mysql_katalog_handle ) {
//   mysql_select_db( $katalog_db_name, $mysql_katalog_handle ) or $mysql_katalog_handle = false;
// }

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
    // ausnahme: einige in ../windows/ wurden direkt aufgerufen, aber das
    // sollte jetzt nicht mehr vorkommen:
    /// $foodsoftdir = ereg_replace( '/windows$', '', $foodsoftdir );
    //
    // (noch'ne ausnahme: aufruf aus dem wiki heraus...)
    //
    $foodsoftdir = ereg_replace( '/wiki$', '/foodsoft', $foodsoftdir );
  }

  // $mysqljetzt: Alternative zu NOW(), Vorteile:
  //  - kann quotiert werden
  //  - in einem Skriptlauf wird garantiert immer dieselbe Zeit verwendet
  $mysqlheute = date('Y') . '-' . date('m') . '-' . date('d');
  $mysqljetzt = $mysqlheute . ' ' . date('H') . ':' . date('i') . ':' . date('s');

  // gruppen mit sonderbedeutung merken:
  global $specialgroups, $basar_id, $muell_id;
  $specialgroups = array();
  if( isset( $basar_id ) )
    $specialgroups[] = $basar_id;
  if( isset( $muell_id ) )
    $specialgroups[] = $muell_id;

  global $print_on_exit;
  $print_on_exit = '';

  // databaseversion
  $database_version or $database_version = 0;
  require_once('code/err_functions.php');
  require_once('code/views.php');
  require_once('code/inlinks.php');
  require_once('code/zuordnen.php');
  require_once('code/katalogsuche.php');
  update_database($database_version);

?>
