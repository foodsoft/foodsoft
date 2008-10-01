<?php

global $foodsoftpath, $foodsoftdir;   // noetig wenn aufruf aus wiki
if( ! isset( $foodsoftpath ) ) {
  $foodsoftpath = realpath( dirname( __FILE__ ) . '/../' );
}
if( ! isset( $foodsoftdir ) ) {
  $foodsoftdir = ereg_replace( '/[^/]+$', '', $_SERVER['SCRIPT_NAME'] );
  // ausnahme: aufruf aus dem wiki heraus:
  $foodsoftdir = ereg_replace( '/wiki$', '/foodsoft', $foodsoftdir );
}

require_once('code/config.php');
if( 0 or $allow_setup ) { // TODO: warnen, wenn setup.php zugreifbar ist?
  ?><html><body> Fehler: bitte <code>setup.php</code> deaktivieren in <code>code/config.php</code>!</body></html><?
  exit(1);
}

global $print_on_exit;
$print_on_exit = '';
require_once('code/err_functions.php');

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
global $db_handle;
if( ! ( $db_handle = mysql_connect($db_server,$db_user,$db_pwd ) ) || !mysql_select_db( $db_name ) ) {
  echo "<html><body><h1>Datenbankfehler!</h1>Konnte keine Verbindung zur Datenbank herstellen... Bitte später nochmal versuchen.</body></html>";
  exit();
}

// die restliche konfiguration koennen wir aus der leitvariablen-tabelle lesen
// (skripte koennen dann persistente variable einfach speichern, aendern, und
//  an slave (im keller) uebertragen)
//
global $leitvariable;
require_once('leitvariable.php');
foreach( $leitvariable as $name => $props ) {
  global $$name;
  $result = mysql_query( "SELECT * FROM leitvariable WHERE name='$name'" );
  if( $result and ( $row = mysql_fetch_array( $result ) ) ) {
    $$name = $row['value'];
  } else {
    $$name = $props['default'];
  }
}

global $mysqlheute, $mysqljetzt;
// $mysqljetzt: Alternative zu NOW(), Vorteile:
//  - kann quotiert werden
//  - in einem Skriptlauf wird garantiert immer dieselbe Zeit verwendet
$mysqlheute = date('Y') . '-' . date('m') . '-' . date('d');
$mysqljetzt = $mysqlheute . ' ' . date('H') . ':' . date('i') . ':' . date('s');

// gruppen mit sonderbedeutung merken:
global $specialgroups;
$specialgroups = array();
$specialgroups[] = $basar_id;
$specialgroups[] = $muell_id;

// $self_fields: variable, die in der url uebergeben werden, werden hier gesammelt:
global $self_fields;
$self_fields = array();

// Benutzerdaten:
global $angemeldet, $login_gruppen_id, $login_gruppen_name, $dienst, $coopie_name, $dienstkontrollblatt_id, $session_id;
$angemeldet = false;

require_once('code/views.php');
require_once('code/html.php');
require_once('code/inlinks.php');
require_once('code/zuordnen.php');
require_once('code/katalogsuche.php');

update_database($database_version);

?>
