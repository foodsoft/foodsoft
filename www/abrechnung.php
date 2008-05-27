<?php
//
// abrechnung.php:
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
need_http_var( 'bestell_id', 'u', true );

$state = getState($bestell_id);
$bestellung_name = bestellung_name( $bestell_id );
$lieferant_id = getProduzentBestellID( $bestell_id );
$lieferant_name = lieferant_name( $lieferant_id );

$bestellung = sql_bestellung( $bestell_id );

need( $state >= STATUS_VERTEILT, "Bestellung ist noch nicht verteilt!" );
need( $state < STATUS_ARCHIVIERT, "Bestellung ist bereits archiviert!" );


get_http_var( 'action', 'w', '' );
$editable or $action = '';

$result = sql_gruppenpfand( $lieferant_id, $bestell_id, "gesamtbestellungen.id" );
$gruppenpfand = mysql_fetch_array( $result );

// $result = sql_pfandverpackungen( $lieferant_id, $bestell_id, "lieferantenpfand.bestell_id" );
// $lieferantenpfand = mysql_fetch_array( $result );

$lieferanten_soll = sql_bestellung_soll_lieferant( $bestell_id );



/////////////////////////////
//
// aktionen verarbeiten:
//
/////////////////////////////


$warenwert_verteilt_brutto = verteilung_wert_brutto( $bestell_id ); 
$warenwert_muell_brutto = muell_wert_brutto( $bestell_id ); 
$warenwert_basar_brutto = basar_wert_brutto( $bestell_id ); 


?>
<h2>Abrechung: Bestellung <? echo "$bestellung_name ($lieferant_name)"; ?></h2>

<form method='post' action='<? echo self_url(); ?>'>
<?echo self_post(); ?>
  <input type='hidden' name='action' value='abschluss'>

  <table class='liste'>
    <tr>
      <th>Abrechnungsschritt</th>
      <th>Details</th>
      <th>Netto</th>
      <th>Brutto</th>
      <th>Aktionen</th>
      <th>erledigt?</th>
    </tr>
    <tr>
      <th colspan='6' style='padding-top:2em;'>Lieferant <? echo $lieferant_name; ?>: </th>
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
      <td style='vertical-align:bottom;'>
        ok: <input type='checkbox' name='lieferschein_ok' value='yes'>
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
      <td rowspan='2' style='vertical-align:bottom;'>
        ok: <input type='checkbox' name='pfandzettel_ok' value='yes'>
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
        <input type='text' name='extra_text' size='40' value='<? echo $bestellung['extra_text']; ?>'>
      </td>
      <td class='number' style='text-align:right;vertical-align:bottom;'>
        <input style='text-align:right;' type='text' name='extra_soll' size='10' value='<? printf( "%.2lf", $bestellung['extra_soll'] ); ?>'>
      </td>
    </tr>
    <tr class='summe'>
      <td colspan='3'>Summe:</td>
      <td class='number'>
        <? printf( "%.2lf", sql_bestellung_rechnungssumme( $bestell_id ) ); ?>
      </td>
      <td>&nbsp;</td>
      <td>
        ok: <input type='checkbox' name='rechnungssumme_ok' value='yes'>
      </td>
    </tr>
    <tr>
      <td colspan='5'>
        
      </td>
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
      <td style='vertical-align:bottom;'>
        ok: <input type='checkbox' name='basar_ok' value='yes'>
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
      <td rowspan='2' style='vertical-align:bottom;'>
        ok: <input type='checkbox' name='veteilung_ok' value='yes'>
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
      <td>
        ok: <input type='checkbox' name='rechnungssumme_ok' value='yes'>
      </td>
    </tr>
    <tr>
      <td rowspan='2'>
        Pfandabrechnung Bestellgruppen:
        <div class='small'>(nur bei Terra!)</div>
      </td>
      <td style='text-align:right;'>berechnet (Kauf):</td>
      <td>&nbsp;</td>
      <td class='number'><b><? printf( "%.2lf", $gruppenpfand['pfand_voll_brutto_soll'] ); ?></b></td>
      <td rowspan='2' style='vertical-align:middle;'>
        <a href="javascript:neuesfenster('index.php?window=gruppenpfand&bestell_id=<? echo $bestell_id; ?>','gruppenpfand');"
        >zur Pfandabrechnung...</a>
      </td>
      <td rowspan='2' style='vertical-align:bottom;'>
        ok: <input type='checkbox' name='gruppenpfand_ok' value='yes'>
      </td>
    </tr>
    <tr>
      <td style='text-align:right;'>gutgeschrieben (Rückgabe):</td>
      <td>&nbsp;</td>
      <td class='number'><b><? printf( "%.2lf", $gruppenpfand['pfand_leer_brutto_soll'] ); ?></b></td>
    </tr>
  </table>

</form>



