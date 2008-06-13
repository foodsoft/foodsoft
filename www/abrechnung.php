<?php
//
// abrechnung.php:
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
need_http_var( 'bestell_id', 'u', true );

$state = getState( $bestell_id );

need( $state >= STATUS_VERTEILT, "Bestellung ist noch nicht verteilt!" );
need( $state < STATUS_ARCHIVIERT, "Bestellung ist bereits archiviert!" );

$bestellung = sql_bestellung( $bestell_id );
$bestellung_name = $bestellung['name'];
$lieferant_id = $bestellung['lieferanten_id'];
$lieferant_name = lieferant_name( $lieferant_id );

/////////////////////////////
//
// aktionen verarbeiten:
//
/////////////////////////////

get_http_var( 'action', 'w', '' );
$editable or $action = '';

$status = getState( $bestell_id );

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
$bestellung_name = $bestellung['name'];
$lieferant_id = $bestellung['lieferanten_id'];
$lieferant_name = lieferant_name( $lieferant_id );
$status = getState( $bestell_id );
$ro_tag = '';
if( $status >= STATUS_ABGERECHNET ) {
  $ro_tag = 'readonly';
}


$result = sql_gruppenpfand( $lieferant_id, $bestell_id, "gesamtbestellungen.id" );
$gruppenpfand = mysql_fetch_array( $result );

// $result = sql_pfandverpackungen( $lieferant_id, $bestell_id, "lieferantenpfand.bestell_id" );
// $lieferantenpfand = mysql_fetch_array( $result );

$lieferanten_soll = sql_bestellung_soll_lieferant( $bestell_id );


$warenwert_verteilt_brutto = verteilung_wert_brutto( $bestell_id ); 
$warenwert_muell_brutto = muell_wert_brutto( $bestell_id ); 
$warenwert_basar_brutto = basar_wert_brutto( $bestell_id ); 


?>
<h2>Abrechnung: Bestellung <? echo "$bestellung_name ($lieferant_name)"; ?></h2>

<form method='post' action='<? echo self_url(); ?>'>
<?echo self_post(); ?>
  <input type='hidden' name='action' value='save'>

  <table class='numbers'>
    <tr>
      <th>Abrechnungsschritt</th>
      <th>Details</th>
      <th style='text-align:right;'>Netto</th>
      <th style='text-align:right;'>Brutto</th>
      <th>Aktionen</th>
    </tr>

<tr>
  <th colspan='6' style='padding-top:2em;'>Bestellgruppen: </th>
</tr>
    <tr>
      <td>
        Basarkäufe eintragen:
      </td>
      <td style='text-align:right;'>
        Reste im Basar:
      </td>
      <td>&nbsp;</td>
      <td class='number'><b><? printf( "%.2lf", $warenwert_basar_brutto ); ?></b></td>
      <td style='vertical-align:bottom;'>
        <a href="javascript:neuesfenster('index.php?window=basar','basar');"
        >zum Basar...</a>
      </td>
    </tr>
    <tr>
      <td rowspan='2'>
        Verteilmengen abgleichen:
      </td>
      <td style='text-align:right;'>
        Warenwert Gruppen:
      </td>
      <td class='number'>&nbsp;</td>
      <td class='number'><b><? printf( "%.2lf", $warenwert_verteilt_brutto ); ?></b></td>
      <td rowspan='2' style='vertical-align:middle;'>
        <a href="javascript:neuesfenster('index.php?window=verteilung&bestell_id=<? echo $bestell_id; ?>','verteilliste');"
        >zur Verteilliste...</a>
      </td>
    </tr>
    <tr>
      <td style='text-align:right;'>
        auf den Müll gewandert:
      </td>
      <td class='number'>&nbsp;</td>
      <td class='number'><b><? printf( "%.2lf", $warenwert_muell_brutto ); ?></b></td>
    </tr>
    <tr class='summe'>
      <td colspan='3'>Summe:</td>
      <td class='number'>
        <? printf( "%.2lf", $warenwert_verteilt_brutto + $warenwert_muell_brutto + $warenwert_basar_brutto ); ?>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td rowspan='2'>
        Pfandabrechnung Bestellgruppen:
        <div class='small'>(nur bei Terra!)</div>
      </td>
      <td style='text-align:right;'>berechnet (Kauf):</td>
      <td>&nbsp;</td>
      <td class='number'><b><? printf( "%.2lf", -$gruppenpfand['pfand_voll_brutto_soll'] ); ?></b></td>
      <td rowspan='2' style='vertical-align:middle;'>
        <a href="javascript:neuesfenster('index.php?window=gruppenpfand&bestell_id=<? echo $bestell_id; ?>','gruppenpfand');"
        >zur Pfandabrechnung...</a>
      </td>
    </tr>
    <tr>
      <td style='text-align:right;'>gutgeschrieben (Rückgabe):</td>
      <td>&nbsp;</td>
      <td class='number'><b><? printf( "%.2lf", -$gruppenpfand['pfand_leer_brutto_soll'] ); ?></b></td>
    </tr>

<tr>
  <th colspan='6' style='padding-top:2em;'>
    <div style='text-align:center;'>Lieferant <? echo $lieferant_name; ?>: </div>
    <div style='text-align:left;'>
      Rechnungsnummer des Lieferanten:
      <input <? echo $ro_tag; ?> type='text' size='40' name='rechnungsnummer' value='<? echo $bestellung['rechnungsnummer']; ?>'>
    </div>
  </th>
</tr>
    <tr>
      <td>
        Liefermengen und -preise abgleichen:
      </td>
      <td style='text-align:right;'>Warenwert:</td>
      <td class='number'><b><? printf( "%.2lf", $lieferanten_soll['waren_netto_soll'] ); ?></b></td>
      <td class='number'><b><? printf( "%.2lf", $lieferanten_soll['waren_brutto_soll'] ); ?></b></td>
      <td style='vertical-align:bottom;'>
        <a href="javascript:neuesfenster('index.php?window=bestellschein&bestell_id=<? echo $bestell_id; ?>','bestellschein');"
        >zum Lieferschein...</a>
      </td>
    </tr>
    <tr>
      <td rowspan='2'>
        Pfandabrechnung Lieferant:
        <div class='small'>(falls zutreffend, etwa bei Terra!)</div>
      </td>
      <td style='text-align:right;'>berechnet (Kauf):</td>
      <td class='number'><b><? printf( "%.2lf", $lieferanten_soll['pfand_voll_netto_soll'] ); ?></b></td>
      <td class='number'><b><? printf( "%.2lf", $lieferanten_soll['pfand_voll_brutto_soll'] ); ?></b></td>
      </td>
      <td rowspan='2' style='vertical-align:middle;'>
        <a href="javascript:neuesfenster('index.php?window=pfandverpackungen&bestell_id=<? echo $bestell_id; ?>','pfandzettel');"
        >zum Pfandzettel...</a>
      </td>
    </tr>
    <tr>
      <td style='text-align:right;'>gutgeschrieben (Rückgabe):</td>
      <td class='number'><b><? printf( "%.2lf", $lieferanten_soll['pfand_leer_netto_soll'] ); ?></b></td>
      <td class='number'><b><? printf( "%.2lf", $lieferanten_soll['pfand_leer_brutto_soll'] ); ?></b></td>
    </tr>
    <tr class='summe'>
      <td colspan='2'>Zwischensumme:</td>
      <td class='number'><? printf( "%.2lf", $lieferanten_soll['waren_netto_soll']
                              + $lieferanten_soll['pfand_leer_netto_soll']
                              + $lieferanten_soll['pfand_voll_netto_soll']  ); ?>
      </td>
      <td class='number'><? printf( "%.2lf", $lieferanten_soll['waren_brutto_soll']
                              + $lieferanten_soll['pfand_leer_brutto_soll']
                              + $lieferanten_soll['pfand_voll_brutto_soll']  ); ?>

      </td>
      <td colspan='2'>&nbsp;</td>
    </tr>
    <tr>
      <td colspan='3'>
        Sonstiges:
        <br>
        <input <? echo $ro_tag; ?> type='text' name='extra_text' size='40' value='<? echo $bestellung['extra_text']; ?>'>
      </td>
      <td class='number' style='text-align:right;vertical-align:bottom;'>
        <input <? echo $ro_tag; ?> style='text-align:right;' type='text' name='extra_soll' size='10' value='<? printf( "%.2lf", $bestellung['extra_soll'] ); ?>'>
      </td>
    </tr>
    <tr class='summe'>
      <td colspan='3'>Summe:</td>
      <td class='number'>
        <? printf( "%.2lf", sql_bestellung_rechnungssumme( $bestell_id ) ); ?>
      </td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td colspan='5' style='padding-top:1em;text-align:right;'>
        <? if( $status >= STATUS_ABGERECHNET ) { ?>
          Abrechnung durchgeführt: <? echo dienstkontrollblatt_name( $bestellung['abrechnung_dienstkontrollblatt_id'] ); ?>,
          <? echo $bestellung['abrechnung_datum']; ?>
          <? if( $hat_dienst_IV ) { ?>
            <span style='padding-left:3em;'>Nochmal öffnen: <input type='checkbox' name='rechnung_abschluss' value='reopen' style='padding-right:4em'>
            <input type='submit' value='Abschicken'>
            </span>
          <? } ?>
        <? } else { ?>
          <? if( $hat_dienst_IV ) { ?>
            <? if( abs( $warenwert_basar_brutto ) < 0.05 ) { ?>
              Rechnung abschliessen: <input type='checkbox' name='rechnung_abschluss' value='yes' style='padding-right:4em'>
            <? } ?>
            <input type='submit' value='Speichern'>
          <? } ?>
        <? } ?>
      </td>
    </tr>
  </table>

</form>



