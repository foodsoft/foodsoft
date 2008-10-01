<?php
//
// abrechnung.php:
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
need_http_var( 'bestell_id', 'u', true );

setWikiHelpTopic( 'foodsoft:Abrechnung' );

$status = getState( $bestell_id );

need( $status >= STATUS_VERTEILT, "Bestellung ist noch nicht verteilt!" );
need( $status < STATUS_ARCHIVIERT, "Bestellung ist bereits archiviert!" );

$bestellung = sql_bestellung( $bestell_id );
$bestellung_name = $bestellung['name'];
$lieferant_id = $bestellung['lieferanten_id'];
$lieferant = sql_getLieferant( $lieferant_id );
$lieferant_name = $lieferant['name'];

//
// aktionen verarbeiten:
//
/////////////////////////////

get_http_var( 'action', 'w', '' );
$editable or $action = '';

if( $action == 'save' ) {
  if( $status == STATUS_ABGERECHNET ) {
    get_http_var( 'rechnung_abschluss', 'w', '' );
    if( $rechnung_abschluss == 'reopen' ) {
      changeState( $bestell_id, STATUS_VERTEILT );
    }
  } else {
    get_http_var( 'rechnungsnummer', 'H', '' ) or $rechnungsnummer = '';
    get_http_var( 'extra_text', 'H', '' ) or $extra_text = '';
    need_http_var( 'extra_soll', 'f', 0.0 );
    sql_update( 'gesamtbestellungen', $bestell_id, array(
      'rechnungsnummer' => $rechnungsnummer
    , 'extra_text' => $extra_text
    , 'extra_soll' => $extra_soll
    ) );
    get_http_var( 'rechnung_abschluss', 'w', '' );
    if( $rechnung_abschluss == 'yes' ) {
      need( abs( basar_wert_brutto( $bestell_id ) ) < 0.01 , "Abschluss noch nicht möglich: da sind noch Reste im Basar!" );
      changeState( $bestell_id, STATUS_ABGERECHNET );
    }
  }
}

$bestellung = sql_bestellung( $bestell_id );
$status = getState( $bestell_id );
$ro_tag = '';
if( ! $editable or ( $status >= STATUS_ABGERECHNET ) ) {
  $ro_tag = 'readonly';
}

$result = sql_gruppenpfand( $lieferant_id, $bestell_id, "gesamtbestellungen.id" );
$gruppenpfand = mysql_fetch_array( $result );

$lieferanten_soll = sql_bestellung_soll_lieferant( $bestell_id );

$warenwert_verteilt_brutto = verteilung_wert_brutto( $bestell_id ); 
$warenwert_muell_brutto = muell_wert_brutto( $bestell_id ); 
$warenwert_basar_brutto = basar_wert_brutto( $bestell_id ); 


?><h2>Abrechnung: Bestellung <?
  echo "$bestellung_name ($lieferant_name) ". fc_alink( 'edit_bestellung', "bestell_id=$bestell_id" );
?></h2><?

open_form();
  ?><input type='hidden' name='action' value='save'><?

  open_table( 'list' );
      open_th( '', '', 'Abrechnungsschritt' );
      open_th( '', '', 'Details' );
      open_th( '', '', 'Netto' );
      open_th( '', '', 'Brutto' );
      open_th( '', '', 'Aktionen' );

    //
    // gruppennteil:
    //
    open_tr();
      open_th( '', "colspan='5' style='padding-top:2em;'", 'Bestellgruppen:' );

    open_tr();
      open_td( '', '', 'Basarkäufe eintragen:' );
      open_td( '', '', 'Reste im Basar:' );
      open_td();
      open_td( 'boldnumber', sprintf( "%.2lf", $warenwert_basar_brutto ) );
      open_td( '', "style='vertical-align:bottom;'", fc_alink( 'basar', "text=zum Basar...,img=" ) );
    open_tr();
      open_td( '', "rowspan='2'", "Verteilmengen abgleichen:" );
      open_td( 'right', '', 'Warenwert Gruppen:' );
      open_td();
      open_td( 'boldnumber', '', sprintf( "%.2lf", $warenwert_verteilt_brutto ) );
      open_td( '', " rowspan='2' style='vertical-align:middle;'",
               fc_alink( 'verteilliste', "bestell_id=$bestell_id,text=zur Verteilliste...,img=" ) );
    open_tr();
      open_td( 'right', '', 'auf den Müll gewandert:' );
      open_td();
      open_td( 'boldnumber', '', sprintf( "%.2lf", $warenwert_muell_brutto ) );

    open_tr( 'summe' );
      open_td( '', "colspan='3'", 'Summe' );
      open_td( 'number', '', sprintf( "%.2lf", $warenwert_verteilt_brutto + $warenwert_muell_brutto + $warenwert_basar_brutto ) );
      open_td();

if( $lieferant['anzahl_pfandverpackungen'] > 0 ) {
    open_tr();
      open_td( '', "rowspan='2'", 'Pfandabrechnung Bestellgruppen:' );
      open_td( 'right', '', 'berechnet (Kauf):' );
      open_td();
      open_td( 'boldnumber', '', sprintf( "%.2lf", -$gruppenpfand['pfand_voll_brutto_soll'] ) );
      open_td( '', "rowspan='2' style='vertical-align:middle;'",
               fc_alink( 'gruppenpfand', "bestell_id=$bestell_id,img=,text=zur Pfandabrechnung..." ) );
    open_tr();
      open_td( 'right', '', 'gutgeschrieben (Rückgabe):' );
      open_td();
      open_td( 'boldnumber', '', sprintf( "%.2lf", -$gruppenpfand['pfand_leer_brutto_soll'] ) );
}

    //
    // lieferantenteil:
    //
    open_tr();
      open_th( '', "colspan='5' style='padding-top:2em;'" );
        echo "Lieferant: $lieferant_name";
        ?> <div> Rechnungsnummer des Lieferanten: <?
           if( $readonly ) { 
             echo $bestellung['rechnungsnummer'];
           } else {
             ?> <input type='text' size='40' name='rechnungsnummer' value='<? echo $bestellung['rechnungsnummer']; ?>'> <?
           }
        ?> </div> <?

    open_tr();
      open_td( '', '', 'Liefermengen und -preise abgleichen:' );
      open_td( 'right', '', 'Warenwert:' );
      open_td( 'boldnumber', '', sprintf( "%.2lf", $lieferanten_soll['waren_netto_soll'] ) );
      open_td( 'boldnumber', '', sprintf( "%.2lf", $lieferanten_soll['waren_brutto_soll'] ) );
      open_td( '', "style='vertical-align:bottom;'",
                fc_alink( 'lieferschein', "bestell_id=$bestell_id,img=,text=zum Lieferschein..." ) );

if( $lieferant['anzahl_pfandverpackungen'] > 0 ) {
    open_tr();
      open_td( '', "rowspan='2'", "Pfandabrechnung Lieferant: <div class='small'>(falls zutreffend, etwa bei Terra!)</div>" );
      open_td( 'right', '', 'berechnet (Kauf):' );
      open_td( 'boldnumber', '', sprintf( "%.2lf", $lieferanten_soll['pfand_voll_netto_soll'] ) );
      open_td( 'boldnumber', '', sprintf( "%.2lf", $lieferanten_soll['pfand_voll_brutto_soll'] ) );
      open_td( '', "rowspan='2' style='vertical-align:middle;'",
               fc_alink( 'pfandzettel', "bestell_id=$bestell_id,lieferanten_id=$lieferant_id,img=,text=zum Pfandzettel..." ) );

    open_tr();
      open_td( 'right', '', 'gutgeschrieben (Rückgabe):' );
      open_td( 'boldnumber', '', sprintf( "%.2lf", $lieferanten_soll['pfand_leer_netto_soll'] ) );
      open_td( 'boldnumber', '', sprintf( "%.2lf", $lieferanten_soll['pfand_leer_brutto_soll'] ) );
}
    open_tr( 'summe' );
      open_td( '', "colspan='2'", 'Zwischensumme:' );
      open_td( 'number', '', sprintf( "%.2lf", $lieferanten_soll['waren_netto_soll']
                                             + $lieferanten_soll['pfand_leer_netto_soll']
                                             + $lieferanten_soll['pfand_voll_netto_soll'] ) );
      open_td( 'number', '', sprintf( "%.2lf", $lieferanten_soll['waren_brutto_soll']
                                             + $lieferanten_soll['pfand_leer_brutto_soll']
                                             + $lieferanten_soll['pfand_voll_brutto_soll'] ) );
      open_td( '', "colspan='2'" );

    open_tr();
      open_td( '', "colspan='3'" );
        ?> Sonstiges: <br><?
        if( $readonly ) {
          echo $bestellung['extra_text'];
        } else {
          ?> <input type='text' name='extra_text' size='40' value='<? echo $bestellung['extra_text']; ?>'> <?
        }
      open_td( 'number', "style='text-align:right;vertical-align:bottom;'" );
        if( $readonly ) {
          printf( "%.2lf", $bestellung['extra_soll'] );
        } else {
          ?> <input style='text-align:right;' type='text' name='extra_soll' size='10' value='<? printf( "%.2lf", $bestellung['extra_soll'] ); ?>'> <?
        }

    open_tr( 'summe' );
      open_td( '', "colspan='3'", 'Summe:' );
      open_td( 'number', '', sprintf( "%.2lf", sql_bestellung_rechnungssumme( $bestell_id ) ) );
      open_td();

    open_tr();
      if( $status >= STATUS_ABGERECHNET ) {
        open_td( 'right', "colspan='5' style='padding-top:1em;text-align:right;'" );
          ?> Abrechnung durchgeführt: <?
           echo dienstkontrollblatt_name( $bestellung['abrechnung_dienstkontrollblatt_id'] ) .", "
                . $bestellung['abrechnung_datum'];
          if( $hat_dienst_IV ) {
            ?> <span style='padding-left:3em;'>Nochmal öffnen: <input type='checkbox' name='rechnung_abschluss' value='reopen' style='padding-right:4em'>
                <input type='submit' value='Abschicken'></span> <?
          }
      } else {
        if( $hat_dienst_IV ) {
          if( abs( $warenwert_basar_brutto ) < 0.05 ) {
            open_td( '', "colspan='4' style='padding-top:0.8em;text-align:right;border-right:none;'"
                     , "Rechnung abschliessen: <input type='checkbox' name='rechnung_abschluss' value='yes' style='padding-right:4em'>" );
          } else {
            open_td( '', "colspan='4' style='padding-top:1ex;text-align:left;font-size:smaller;'"
                     , " Reste im Basar --- bitte vor Abschluss leermachen!" );
          }
          open_td( 'right', "style='padding-top:1em;text-align:right;border-left:none;'", "<input type='submit' value='Speichern'>" );
        }
      }

  close_table();
close_form();

?>
