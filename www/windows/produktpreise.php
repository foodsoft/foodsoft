<?php
//
// produktpreise.php:
//  - zeigt preishistorie
//  - vergleich mit katalog (wenn vorhanden)
//  - erlaubt neuen preiseintrag
//

assert( $angemeldet ) or exit();

$editable = ( hat_dienst(4) and ! $readonly );

need_http_var('produkt_id','u',true);
get_http_var('bestell_id','u',0,true);  // optional: waehle preiseintrag fuer diese bestellung!

$produkt = sql_produkt( $produkt_id );
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
  case 'katalog_gueltig':
    need_http_var( 'message','U' );
    sql_update( 'lieferantenkatalog', $message, array( 'gueltig' => 1 ) );
    break;
  case 'katalog_ungueltig':
    need_http_var( 'message','U' );
    sql_update( 'lieferantenkatalog', $message, array( 'gueltig' => 0 ) );
    break;
  case 'preiseintrag_waehlen':
    need_http_var( 'preis_id','u' );
    need( $bestell_id );
    need( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET, "Änderung nicht möglich: Bestellung ist bereits abgerechnet!" );
    sql_update( 'bestellvorschlaege'
    , array( 'gesamtbestellung_id' => $bestell_id, 'produkt_id' => $produkt_id )
    , array( 'produktpreise_id' => $preis_id )
    );
    break;
}


$neednewprice = FALSE;         // flag: neuen preiseintrag vorschlagen (falls gar keiner oder fehlerhaft):
$neednewarticlenumber = FALSE; // flag: suche nach artikelnummer vorschlagen (falls kein Treffer bei Katalogsuche):
$preiseintrag_neu = array();   // array fuer vorschlag neuer preiseintrag

// neu laden (falls durch $action geaendert), mit aktuellen preisdaten (soweit vorhanden):
//
$preis_id = sql_aktueller_produktpreis_id( $produkt_id );
$produkt = sql_produkt( array( 'produkt_id' => $produkt_id, 'preis_id' => $preis_id ) );

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
  open_table( 'list hfill' );
      open_th( '', '', 'Name' );
      open_th( '', "title='Bestellnummer'", 'B-Nr.' );
      open_th( 'mult', "title='L-Preis: Netto-Einzelpreis beim Lieferanten'", 'L-Preis' );
      open_th( 'unit', "title='Liefer-Einheit: fuer Abgleich mit Rechnungen und Katalog'", '/ L-Einheit' );
      open_th( '', "title='MWSt in Prozent'", 'MWSt' );
      open_th( '', "title='Pfand je V-Einheit'", 'Pfand' );
      open_th( '', "title='Wieviel wir beim Lieferanten auf einmal bestellen muessen'", 'Gebindegroesse' );
      open_th( 'mult', "title='V-Preis: mit Pfand und Mehrwertsteuer'", 'V-Preis' );
      open_th( 'unit', "title='Verteil-Einheit: fuers Bestellen und Verteilen bei uns'", '/ V-Einheit' );
    open_tr();
      open_td();
        open_table( 'layout hfill' );
            open_td( 'oneline left', '', $produkt['name'] );
            open_td( 'center', "rowspan='2' style='width:4em;'", fc_link( 'edit_produkt', "produkt_id=$produkt_id" ) );
          open_tr();
            open_td( 'oneline small', '', $produkt['notiz'] );
        close_table();
  if( $preis_id ) {
    open_td( '', '', $produkt['bestellnummer'] );
    open_td( 'mult', '', price_view( $produkt['nettolieferpreis'] ) );
    open_td( 'unit', '', "/ {$produkt['liefereinheit']}" );
    open_td( 'number', '', price_view( $produkt['mwst'] ) );
    open_td( 'number', '', price_view( $produkt['pfand'] ) );
    open_td( 'center oneline', '', gebindegroesse_view( $produkt ) );
    open_td( 'mult', '', price_view( $produkt['vpreis'] ) );
    open_td( 'unit', '', "/ ${produkt['verteileinheit']}" );
  } else {
    open_td( 'warn center', "colspan='8'", '- - -' );
  }
  close_table();

  if( $preis_id ) {
    if( ! $produkt['kan_liefereinheit'] ) {
      div_msg( 'warn', 'FEHLER: keine gültige Liefereinheit' );
      $neednewprice = TRUE;
    }
    // FIXME: hier mehr tests machen! aber: waere nur fuer lieferanten ohne erfassten katalog nuetzlich!
  }

close_fieldset();

/////////////////////////////
// Artikeldaten im Katalog suchen und ggf. anzeigen:
//

$result = katalogabgleich( $produkt_id, 2, $editable, $preiseintrag_neu );
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
    // Katalogsuche fehlgeschlagen: das ist normal bei allen Lieferanten ohne erfassten Katalog:
    break;
}
medskip();

if( $editable ) {
  formular_artikelnummer( $produkt_id, $neednewarticlenumber, $bestell_id );
  if( $neednewprice ) {
    open_fieldset( 'small_form', '', 'Vorschlag neuer Preiseintrag' );
  } else {
    open_fieldset( 'small_form', '', 'Neuer Preiseintrag', 'off' );
  }
    formular_produktpreis( $produkt_id, $preiseintrag_neu );
  close_fieldset();
}

?>

