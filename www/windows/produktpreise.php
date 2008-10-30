<?php
//
// produktpreise.php:
//  - zeigt preishistorie
//  - vergleich mit katalog (wenn vorhanden)
//  - erlaubt neuen preiseintrag
//

assert( $angemeldet ) or exit();

$editable = ( ! $readonly and ( $dienst == 4 ) );

need_http_var('produkt_id','u',true);
get_http_var('bestell_id','u',0,true);  // optional: waehle preiseintrag fuer diese bestellung!

$produkt = sql_produkt_details( $produkt_id );
$lieferanten_id = $produkt['lieferanten_id'];
$produkt_name = $produkt['name'];
$lieferanten_name = sql_lieferant_name( $lieferanten_id );

if( $bestell_id )
  $subtitle = "Produktdetails $produkt_name - Auswahl Preiseintrag";
else
  $subtitle = "Produktdetails: $produkt_name von $lieferanten_name";

setWindowSubtitle( $subtitle );
setWikiHelpTopic( "foodsoft:produktpreise" );

get_http_var( 'action','w','' );
$editable or $action = '';

switch( $action ) {
  case 'zeitende_setzen':
    need_http_var('preis_id','u');
    need_http_var('day','u');
    need_http_var('month','u');
    need_http_var('year','u');
    get_http_var('vortag','u',0);
    if( $vortag ) {
      $zeitende = "date_add( '$year-$month-$day', interval -1 second )";
    } else {
      $zeitende = "'$year-$month-$day 23:59:59'";
    }
    sql_update( 'produktpreise', $preis_id, array( 'zeitende' => "$zeitende" ), false );
    break;
  case 'artikelnummer_setzen':
    need_http_var( 'anummer', 'H' );
    sql_update( 'produkte', $produkt_id, array( 'artikelnummer' => $anummer ) );
    break;
  case 'neuer_preiseintrag':
    action_form_produktpreis();
    break;
  case 'delete_price':
    need_http_var('preis_id','u');
    sql_delete_produktpreis( $preis_id );
    break;
  case 'preiseintrag_waehlen':
    need_http_var( 'preis_id','u' );
    need( $bestell_id );
    need( getState( $bestell_id ) < STATUS_ABGERECHNET, "Änderung nicht möglich: Bestellung ist bereits abgerechnet!" );
    doSql ( "UPDATE bestellvorschlaege
       SET produktpreise_id='$preis_id'
       WHERE gesamtbestellung_id='$bestell_id' AND produkt_id='$produkt_id'
    ", LEVEL_IMPORTANT, "Auswahl Preiseintrag fehlgeschlagen: " );
    break;
}


// flag: neuen preiseintrag vorschlagen (falls gar keiner oder fehlerhaft):
//
$neednewprice = FALSE;

// flag: suche nach artikelnummer vorschlagen (falls kein Treffer bei Katalogsuche):
//
$neednewarticlenumber = FALSE;

// felder fuer neuen preiseintrag initialisieren:
//
$preiseintrag_neu = array();

// neu laden (falls durch $action geaendert):
//
$produkt = sql_produkt_details( $produkt_id );

$prgueltig = false;
if( $produkt['zeitstart'] )
  $prgueltig = true;
$lieferanten_id = $produkt['lieferanten_id'];
$produkt_name = $produkt['name'];

open_fieldset( 'big_form', ''
  , "Produkt: $produkt_name von $lieferanten_name "
    . ( $produkt['artikelnummer'] ? "(Artikelnummer: {$produkt['artikelnummer']})" : '(keine Artikelnummer)' ) );

////////////////////////
// Preishistorie: im Detail-Modus anzeigen, sonst nur Test auf Konsistenz:
//

preishistorie_view( $produkt_id, $bestell_id, $editable );


///////////////////////////
// Artikeldaten aus foodsoft-Datenbank anzeigen:
//


open_fieldset( 'big_form', '', "Foodsoft-Datenbank:" );
  open_table( 'list', "style='width:100%;'" );
      open_th( '', '', 'Name' );
      open_th( '', "title='Bestellnummer'", 'B-Nr.' );
      open_th( '', "title='Liefer-Einheit: fuers Bestellen beim Lieferanten'", 'L-Einheit' );
      open_th( '', "title='Nettopreis beim Lieferanten'", 'L-Preis' );
      open_th( '', "title='Verteil-Einheit: fuers Bestellen und Verteilen bei uns'", 'V-Einheit' );
      open_th( '', "title='V-Einheiten pro Gebinde'", 'Gebinde' );
      open_th( '', "title='MWSt in Prozent'", 'MWSt' );
      open_th( '', "title='Pfand je V-Einheit'", 'Pfand' );
      open_th( '', "title='Endpreis je V-Einheit'", 'V-Preis' );
    open_tr();
      open_td();
        open_table( 'layout', "style='width:100%'" );
            open_td( 'oneline left', '', $produkt['name'] );
            open_td( 'center', "rowspan='2' style='width:4em;'", fc_link( 'edit_produkt', "produkt_id=$produkt_id" ) );
          open_tr();
            open_td( 'oneline small', '', $produkt['notiz'] );
        close_table();
  if( $prgueltig ) {
    open_td( '', '', $produkt['bestellnummer'] );
    open_td( '', '',  "{$produkt['kan_liefermult']} {$produkt['kan_liefereinheit']}" );
    open_td( 'number', '', sprintf( "%.2lf / %s", $produkt['nettolieferpreis'], $produkt['preiseinheit'] ) );
    open_td( '', '', "{$produkt['kan_verteilmult']} {$produkt['kan_verteileinheit']}" );
    open_td( 'number', '', $produkt['gebindegroesse'] );
    open_td( 'number', '', price_view( $produkt['mwst'] ) );
    open_td( 'number', '', price_view( $produkt['pfand'] ) );
    open_td( 'number' );
      printf( "%8.2lf / %s %s", $produkt['endpreis'], $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] );
  } else {
    open_td( 'warn center', "colspan='9'", '- - -' );
  }
  close_table();

  if( $prgueltig ) {
    if( ! $produkt['kan_liefereinheit'] ) {
      div_msg( 'warn', 'FEHLER: keine gültige Liefereinheit' );
      $neednewprice = TRUE;
    }
    // FIXME: hier mehr tests machen!
  }

close_fieldset();

/////////////////////////////
// Artikeldaten im Katalog suchen und ggf. anzeigen:
//

$result = katalogabgleich( $produkt_id, $editable, true, & $preiseintrag_neu );
switch( $result ) {
  case 0:
    // alles ok!
    break;
  case 1:
    // Abweichung zum Katalog: schlage neuen Preiseintrag vor:
    $neednewprice = true;
    break;
  case 2:
    // Kein Treffer bei Katalogsuche: schlage Artikel(nummer)wahl vor:
    $neednewarticlenumber = true;
    break;
  default:
  case 3:
    // Katalogsuche fehlgeschlagen: das ist normal bei allen ausser Terra:
    break;
}
medskip();

if( $neednewprice ) {
  open_fieldset( 'small_form', '', 'Vorschlag neuer Preiseintrag' );
} else {
  open_fieldset( 'small_form', '', 'Neuer Preiseintrag', 'off' );
}
  formular_produktpreis( $produkt_id, $preiseintrag_neu );
close_fieldset();

?>

