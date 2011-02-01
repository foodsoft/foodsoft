<?PHP

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:pfandzettel' );
setWindowSubtitle( 'Pfandzettel Lieferant' );

$editable = ( hat_dienst(4) and ! $readonly );

get_http_var( 'abrechnung_id', 'u', 0, true );
if( $abrechnung_id ) {
  $bestell_id_set = sql_abrechnung_set( $abrechnung_id );
  $bestell_id_count = count( $bestell_id_set );
}

if( $abrechnung_id ) {
  $lieferanten_id = sql_bestellung_lieferant_id( $abrechnung_id );
  $lieferant_name = sql_lieferant_name( $lieferanten_id );
} else {
  get_http_var( 'lieferanten_id', 'u', 0, true );
}

get_http_var( 'optionen', 'u', 0, true );


//////////////////////////////////
//  menu und auswahl lieferanten:
//

open_table( 'layout hfill' );
  open_td();
    open_table( 'menu' );
        open_th('', '', 'Optionen');
      if( $lieferanten_id) {
        open_tr();
          open_td('', '',  fc_link( 'edit_verpackung',
                            "class=bigbutton,title=Neue Pfandverpackung erfassen,text=Neue Verpackung,lieferanten_id=$lieferanten_id" ) );
      }
    close_table();
  open_td('floatright');
    if( $abrechnung_id ) {
      if( $bestell_id_count > 1 ) {
        echo "<h3>Pfandabrechnung: $bestell_id_count zusammengefasste Bestellungen:</h3>";
        abrechnung_overview( $abrechnung_id );
      } else {
        echo "<h3>Pfandabrechnung: Bestellung" . fc_link( 'abrechnung', array(
                 'abrechnung_id' => $abrechnung_id, 'class' => 'href'
               , 'text' => sql_bestellung_name( $abrechnung_id )
             ) ) . "</h3>";
      }
    } else {
      ?> <h3>Pfandverpackungen</h3> <?php
      auswahl_lieferant( $lieferanten_id );
   }
close_table();
medskip();

// ab hier muss ein Lieferant ausgewaehlt sein, sonst Ende:
//
if( ! $lieferanten_id )
  return;

$lieferant_name = sql_lieferant_name( $lieferanten_id );


//////////////////////////
// aktionen verarbeiten:
//

get_http_var('action','w','');
$editable or $action = '';

if( $abrechnung_id and ( $action == 'save' ) ) {
  foreach( sql_lieferantenpfand( $lieferanten_id, $abrechnung_id ) as $row ) {
    $id = $row['verpackung_id'];
    if( get_http_var( "anzahl_voll_$id", 'u' ) and get_http_var( "anzahl_leer_$id", 'u' ) ) {
      sql_pfandzuordnung_lieferant( $abrechnung_id, $id, ${"anzahl_voll_$id"}, ${"anzahl_leer_$id"} );
    }
  }
}
if( $action == 'moveup' ) {
  need_http_var( 'verpackung_id', 'u' );
  $prev = false;
  foreach( sql_lieferantenpfand( $lieferanten_id ) as $row ) {
    if( $row['verpackung_id'] == $verpackung_id ) {
      if( ! $prev )
        break;
      $h = $prev['sort_id'];
      sql_update( 'pfandverpackungen', $prev['verpackung_id'], array( 'sort_id' => -1 ) );
      sql_update( 'pfandverpackungen', $row['verpackung_id'], array( 'sort_id' => $h ) );
      sql_update( 'pfandverpackungen', $prev['verpackung_id'], array( 'sort_id' => $row['sort_id'] ) );
      break;
    }
    $prev = $row;
  }
}
if( $action == 'movedown' ) {
  need_http_var( 'verpackung_id', 'u' );
  $verpackungen = sql_lieferantenpfand( $lieferanten_id );
  for( $row = current( $verpackungen ); $row; $row = next( $verpackungen ) ) {
    if( $row['verpackung_id'] == $verpackung_id ) {
      $next = next( $verpackungen );
      if( ! $next )
        break;
      $h = $row['sort_id'];
      sql_update( 'pfandverpackungen', $next['verpackung_id'], array( 'sort_id' => -1 ) );
      sql_update( 'pfandverpackungen', $row['verpackung_id'], array( 'sort_id' => $next['sort_id'] ) );
      sql_update( 'pfandverpackungen', $next['verpackung_id'], array( 'sort_id' => $h ) );
      break;
    }
  }
}

//   if( $action == 'delete' and $editable ) {
//     need_http_var('pfandverpackung_id','u');
//     sql_delete_pfandverpackung( $pfandverpackung_id );
//   }

/////////////////////////////
// Pfandzettel anzeigen:
//

if( $abrechnung_id && $editable )
  open_form( '', 'action=save' );

open_table('list greywhite');
  open_th( '', '', 'Bezeichnung' );
  open_th( '', '', 'Einzelwert' );
  open_th( '', '', 'MWSt' );
  open_th( '', '', 'geliefert' );
  open_th( '', '', 'Netto' );
  open_th( '', '', 'Brutto' );
  open_th( '', '', 'gutgeschrieben' );
  open_th( '', '', 'Netto' );
  open_th( '', '', 'Brutto' );
  open_th( '', '', 'Bestand' );
  open_th( '', '', 'Netto' );
  open_th( '', '', 'Brutto' );
  if( $editable )
    open_th( '', '', 'Aktionen' );

$summe_voll_anzahl = 0;
$summe_voll_netto = 0;
$summe_voll_brutto = 0;
$summe_leer_anzahl = 0;
$summe_leer_netto = 0;
$summe_leer_brutto = 0;

foreach( sql_lieferantenpfand( $lieferanten_id, $abrechnung_id ) as $row ) {
  $verpackung_id = $row['verpackung_id'];
  open_tr();
    open_td( '', '', $row['name'] );
    open_td( 'number', '', price_view( $row['wert'] ) );
    open_td( 'number', '', price_view( $row['mwst'] ) );

    open_td( 'number', '', int_view( $row['pfand_voll_anzahl'], ( ($editable and $abrechnung_id) ? "anzahl_voll_$verpackung_id" : false ) ) );
    open_td( 'number', '', price_view( $row['pfand_voll_netto_soll'] ) );
    open_td( 'number', '', price_view( $row['pfand_voll_brutto_soll'] ) );

    open_td( 'number', '', int_view( $row['pfand_leer_anzahl'], ( ($editable and $abrechnung_id) ? "anzahl_leer_$verpackung_id" : false ) ) );
    open_td( 'number', '', price_view( $row['pfand_leer_netto_soll'] ) );
    open_td( 'number', '', price_view( $row['pfand_leer_brutto_soll'] ) );

    open_td( 'number', '', int_view( $row['pfand_voll_anzahl'] - $row['pfand_leer_anzahl'] ) );
    open_td( 'number', '', price_view( $row['pfand_voll_netto_soll'] + $row['pfand_leer_netto_soll'] ) );
    open_td( 'number', '', price_view( $row['pfand_voll_brutto_soll'] + $row['pfand_leer_brutto_soll'] ) );

    if( $editable ) {
      open_td();
        echo fc_link( 'edit_verpackung', "verpackung_id=$verpackung_id" );
        echo fc_link( 'self', "action=moveup,verpackung_id=$verpackung_id,text=,img=img/arrow.up.blue.png,title=Eintrag nach oben schieben" );
        echo fc_link( 'self', "action=movedown,verpackung_id=$verpackung_id,text=,img=img/arrow.down.blue.png,title=Eintrag nach unten schieben" );
    }

  $summe_voll_anzahl += $row['pfand_voll_anzahl'];
  $summe_voll_netto += $row['pfand_voll_netto_soll'];
  $summe_voll_brutto += $row['pfand_voll_brutto_soll'];
  $summe_leer_anzahl += $row['pfand_leer_anzahl'];
  $summe_leer_netto += $row['pfand_leer_netto_soll'];
  $summe_leer_brutto += $row['pfand_leer_brutto_soll'];
}

// zwischensummen nach MWSt-Saetzen ausgeben (erleichtert Abgleich mit Terra-Rechnungen):
//
if( $abrechnung_id ) {
  foreach( sql_lieferantenpfand( $lieferanten_id, $abrechnung_id, 'mwst' ) as $row ) {
    open_tr('summe');
      open_td( '', "colspan='3'", "Teilsumme {$row['mwst']}%:" );

      open_td( 'number', '', int_view( $row['pfand_voll_anzahl'] ) );
      open_td( 'number', '', price_view( $row['pfand_voll_netto_soll'] ) );
      open_td( 'number', '', price_view( $row['pfand_voll_brutto_soll'] ) );

      open_td( 'number', '', int_view( $row['pfand_leer_anzahl'] ) );
      open_td( 'number', '', price_view( $row['pfand_leer_netto_soll'] ) );
      open_td( 'number', '', price_view( $row['pfand_leer_brutto_soll'] ) );

      open_td( 'number', '', int_view( $row['pfand_voll_anzahl'] - $row['pfand_leer_anzahl'] ) );
      open_td( 'number', '', price_view( $row['pfand_voll_netto_soll'] + $row['pfand_leer_netto_soll'] ) );
      open_td( 'number', '', price_view( $row['pfand_voll_brutto_soll'] + $row['pfand_leer_brutto_soll'] ) );

      if( $editable) open_td();
  }
}

open_tr('summe');
  open_td( '', "colspan='3'", "Summe:" );

    open_td( 'number', '', int_view( $summe_voll_anzahl ) );
    open_td( 'number', '', price_view( $summe_voll_netto ) );
    open_td( 'number', '', price_view( $summe_voll_brutto ) );

    open_td( 'number', '', int_view( $summe_leer_anzahl ) );
    open_td( 'number', '', price_view( $summe_leer_netto ) );
    open_td( 'number', '', price_view( $summe_leer_brutto ) );

    open_td( 'number', '', int_view( $summe_voll_anzahl - $summe_leer_anzahl ) );
    open_td( 'number', '', price_view( $summe_voll_netto + $summe_leer_netto ) );
    open_td( 'number', '', price_view( $summe_voll_brutto + $summe_leer_brutto ) );

    if( $editable) open_td();

close_table();

if( $abrechnung_id && $editable ) {
  floating_submission_button();
  close_form();
}

?>
