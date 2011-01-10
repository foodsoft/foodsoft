<?PHP

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:gruppenpfand' );
setWindowSubtitle( 'Pfandabrechnung Bestellgruppen' );

$editable = ( hat_dienst(3,4) and ! $readonly );

get_http_var( 'bestell_id', 'u', 0, true );

// TODO: aufschluesselung nach lieferanten? (macht im moment keinen sinn, pfand gibt's nur bei terra!)
//
// get_http_var( 'lieferanten_id', 'u', 0, true );

get_http_var( 'options', 'u', 0, true );

if( $bestell_id ) {
  $bestellung_name = sql_bestellung_name( $bestell_id );
  $lieferanten_id = sql_bestellung_lieferant_id( $bestell_id );
  $lieferant_name = sql_lieferant_name( $lieferanten_id );
  $editable = ( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET );
} else {
  $options |= PFAND_OPT_ALLE_BESTELLUNGEN;
  $bestellung_name = '';
  $lieferanten_id = 0;
  $editable = false;
}

if( $options & PFAND_OPT_ALLE_BESTELLUNGEN ) {
  $bestell_id = 0;
  $editable = false;
}

open_table( 'layout hfill' );
  open_td();
    open_table( 'menu' );
        open_th('', '', 'Optionen');
      open_tr();
        open_td();
          option_checkbox( 'options', PFAND_OPT_GRUPPEN_INAKTIV, 'auch inaktive Gruppen zeigen?'
                           , 'Auch inaktive Gruppen in Pfandübersicht aufnehmen?' );
      if( $bestellung_name ) {
        open_tr();
          open_td();
           option_checkbox( 'options', PFAND_OPT_ALLE_BESTELLUNGEN, 'Summe aller Bestellungen anzeigen?'
                           , "Pfandsumme ueber alle Bestellungen bei $lieferant_name anzeigen?" );
      }
    close_table();
  open_td();
    if( $bestell_id ) {
      ?> <h3>Gruppenpfand: Bestellung <?php echo "$bestellung_name ({$lieferant_name})"; ?></h3> <?php
    } else if( $lieferanten_id ) {
      ?> <h3>Gruppenpfand: alle Bestellungen bei <?php echo "$lieferant_name"; ?></h3> <?php
    } else {
      ?> <h3>Gruppenpfand: alle Bestellungen </h3> <?php
    }
close_table();

medskip();

/////////////////////////////
//
// aktionen verarbeiten:
//
/////////////////////////////

get_http_var('action','w','');
$editable or $action = '';
if( $bestell_id and ( $action == 'save' ) ) {
  foreach( sql_gruppen() as $row ) {
    $id = $row['id'];
    if( get_http_var( "anzahl_leer_$id", 'u' ) ) {
      sql_pfandzuordnung_gruppe( $bestell_id, $id, ${"anzahl_leer_$id"} );
    }
  }
}


/////////////////////////////
//
// Pfandzettel anzeigen:
//
/////////////////////////////

if( $bestell_id )
  open_form( '', 'action=save' );

open_table('list');
  open_th( '', '', 'Gruppe' );
  open_th( '', '', 'Nr (Id)' );
  open_th( '', '', 'aktiv' );
  open_th( '', "title='Pfand für Bestellungen in Rechnung gestellt'", 'Wert berechnet' );
  open_th( '', "title='Anzahl zurückgegebene Pfandverpackungen'", 'Anzahl gutgeschrieben' );
  open_th( '', "title='Gutschrift für zurürckgegebene Pfandverpackungen'", 'Wert gutgeschrieben' );
  open_th( '', '', 'Summe' );

$summe_pfand_leer_brutto = 0;
$summe_pfand_voll_brutto = 0;
$summe_pfand_leer_anzahl = 0;
$muell_row = false;
$basar_row = false;

foreach( sql_gruppenpfand( $lieferanten_id, $bestell_id ) as $row ) {
  $gruppen_id = $row['gruppen_id'];
  switch( $gruppen_id ) {
    case $muell_id:
      $muell_row = $row;
      continue 2;
    case $basar_id:
      $basar_row = $row;
      continue 2;
    default:
      if( ! ( $row['aktiv'] or ( $options & PFAND_OPT_GRUPPEN_INAKTIV ) ) )
        continue 2;
  }
  open_tr();
    open_td( '', '', $row['gruppen_name'] );
    open_td( '', '', "{$row['gruppennummer']} ($gruppen_id)" );
    open_td( '', '', $row['aktiv'] );
    open_td( 'number', '', price_view( $row['pfand_voll_brutto_soll'] ) );
    open_td( 'number', '', int_view( $row['pfand_leer_anzahl'], ( $editable ? "anzahl_leer_$gruppen_id" : false ) ) );
    open_td( 'number', '', price_view( $row['pfand_leer_brutto_soll'] ) );
    open_td( 'number', '', price_view( $row['pfand_leer_brutto_soll'] + $row['pfand_voll_brutto_soll'] ) );

  $summe_pfand_voll_brutto += $row['pfand_voll_brutto_soll'];
  $summe_pfand_leer_brutto += $row['pfand_leer_brutto_soll'];
  $summe_pfand_leer_anzahl += $row['pfand_leer_anzahl'];
}

  open_tr('summe');
    open_td( '', "colspan='3'", 'Summe:' );
    open_td( 'number', '', price_view( $summe_pfand_voll_brutto ) );
    open_td( 'number', '', int_view( $summe_pfand_leer_anzahl ) );
    open_td( 'number', '', price_view( $summe_pfand_leer_brutto ) );
    open_td( 'number', '', price_view( $summe_pfand_leer_brutto + $summe_pfand_voll_brutto ) );

if( $basar_row ) {
  open_tr('summe');
    open_td( '', "colspan='3'", 'Basar:' );
    open_td( 'number', '', price_view( $basar_row['pfand_voll_brutto_soll'] ) );
    open_td( 'number', '', int_view( $basar_row['pfand_leer_anzahl'] ) );
    open_td( 'number', '', price_view( $basar_row['pfand_leer_brutto_soll'] ) );
    open_td( 'number', '', price_view( $basar_row['pfand_voll_brutto_soll'] + $basar_row['pfand_leer_brutto_soll'] ) );
}
if( $muell_row ) {
  open_tr('summe');
    open_td( '', "colspan='3'", 'M&uuml;ll:' );
    open_td( 'number', '', price_view( $muell_row['pfand_voll_brutto_soll'] ) );
    open_td( 'number', '', int_view( $muell_row['pfand_leer_anzahl'] ) );
    open_td( 'number', '', price_view( $muell_row['pfand_leer_brutto_soll'] ) );
    open_td( 'number', '', price_view( $muell_row['pfand_voll_brutto_soll'] + $muell_row['pfand_leer_brutto_soll'] ) );
}

if( $editable ) {
  open_tr();
    open_td('right', "colspan='6'" );
      floating_submission_button();
}
close_table();

if( $editable )
  close_form();

?>
