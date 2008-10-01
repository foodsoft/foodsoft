<?PHP

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );

get_http_var( 'bestell_id', 'u', 0, true );

// TODO: aufschluesselung nach lieferanten? (macht im moment keinen sinn, pfand gibt's nur bei terra!)
//
// get_http_var( 'lieferanten_id', 'u', 0, true );

get_http_var( 'optionen', 'u', 0, true );

if( $bestell_id ) {
  $bestellung_name = bestellung_name( $bestell_id );
  $lieferanten_id = getProduzentBestellID( $bestell_id );
  $lieferant_name = lieferant_name( $lieferanten_id );
  $editable = ( getState( $bestell_id ) < STATUS_ABGERECHNET );
} else {
  $optionen |= PFAND_OPT_ALLE_BESTELLUNGEN;
  $bestellung_name = '';
  $lieferanten_id = 0;
  $editable = false;
}

if( $optionen & PFAND_OPT_ALLE_BESTELLUNGEN ) {
  $bestell_id = 0;
  $editable = false;
}

?>
<table width='100%' class='layout'>
<tr>
  <td>
    <table class='menu'>
      <tr>
        <td><h4>Optionen</h4></td>
      </tr>
      <tr>
        <td>
          <input style='margin-left:2em;' type='checkbox'
            <? if( $optionen & PFAND_OPT_GRUPPEN_INAKTIV ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ PFAND_OPT_GRUPPEN_INAKTIV );
            ?>';"
            title='Auch inaktive Gruppen in Pfandübersicht aufnehmen?'
          > auch inaktive Gruppen anzeigen?
        </td>
      </tr>
      <? if( $bestellung_name ) { ?>
      <tr>
        <td>
          <input style='margin-left:2em;' type='checkbox'
            <? if( $optionen & PFAND_OPT_ALLE_BESTELLUNGEN ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ PFAND_OPT_ALLE_BESTELLUNGEN );
            ?>';"
            title='Pfandsumme ueber alle Bestellungen bei <? echo $lieferant_name; ?> anzeigen'
          > Summe aller Bestellungen anzeigen?
        </td>
      </tr>
      <? } ?>
    </table>
  </td>
  <td>
    <? if( $bestell_id ) { ?>
      <h3>Gruppenpfand: Bestellung <? echo "$bestellung_name ({$lieferant_name})"; ?></h3>
    <? } else if( $lieferanten_id ) { ?>
      <h3>Gruppenpfand: alle Bestellungen bei <? echo "$lieferant_name"; ?></h3>
    <? } else { ?>
      <h3>Gruppenpfand: alle Bestellungen </h3>
    <? } ?>
  </td>
</tr>
</table>
<?


/////////////////////////////
//
// aktionen verarbeiten:
//
/////////////////////////////

get_http_var('action','w','');
$editable or $action = '';

if( $bestell_id and ( $action == 'save' ) ) {
  $gruppen = sql_bestellgruppen();
  while( $row = mysql_fetch_array( $gruppen ) ) {
    $id = $row['id'];
    if( get_http_var( "anzahl_leer$id", 'u' ) ) {
      sql_pfandzuordnung_gruppe( $bestell_id, $id, ${"anzahl_leer$id"} );
    }
  }
}


/////////////////////////////
//
// Pfandzettel anzeigen:
//
/////////////////////////////

$gruppen = sql_gruppenpfand( $lieferanten_id, $bestell_id );

if( $bestell_id ) {
  ?>
  <form method='post' action='<? echo self_url(); ?>'>
  <? echo self_post(); ?>
  <input type='hidden' name='action' value='save'>
  <?
}

?>
<table class='list'>
  <tr>
    <th>Gruppe</th>
    <th>Nr (Id)</th>
    <th>aktiv</th>
    <th title='Pfand für Bestellungen in Rechnung gestellt'>Wert berechnet</th>
    <th title='Anzahl zurückgegebene Pfandverpackungen'>Anzahl gutgeschrieben</th>
    <th title='Gutschrift für zurürckgegebene Pfandverpackungen'>Wert gutgeschrieben</th>
    <th>Summe</th>
  </tr>
<?
$summe_pfand_leer_brutto = 0;
$summe_pfand_voll_brutto = 0;
$summe_pfand_leer_anzahl = 0;
$muell_row = false;
$basar_row = false;
while( $row = mysql_fetch_array( $gruppen ) ) {
  $gruppen_id = $row['gruppen_id'];
  if( $gruppen_id == $muell_id ) {
    $muell_row = $row;
    continue;
  }
  if( $gruppen_id == $basar_id ) {
    $basar_row = $row;
    continue;
  }
  if( ! ( $row['aktiv'] or ( $optionen & PFAND_OPT_GRUPPEN_INAKTIV ) ) )
    continue;
  ?>
    <tr>
      <td><? echo $row['gruppen_name']; ?></td>
      <td><? echo "{$row['gruppen_nummer']} ($gruppen_id)"; ?></td> 
      <td><? echo $row['aktiv']; ?></td> 
      <td class='number'><? printf( "%.2lf", $row['pfand_voll_brutto_soll'] ); ?></td>
      <td class='number'>
        <? if( $editable and $bestell_id ) { ?>
          <input type=text' size='6' name='anzahl_leer<? echo $gruppen_id; ?>'
                 value='<? printf( "%u", $row['pfand_leer_anzahl'] ); ?>'>
        <? } else { ?>
          <? printf( "%u", $row['pfand_leer_anzahl'] ); ?>
        <? } ?>
      </td>
      <td class='number'><? printf( "%.2lf", $row['pfand_leer_brutto_soll'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['pfand_leer_brutto_soll'] + $row['pfand_voll_brutto_soll'] ); ?></td>
    </tr>
  <?
  $summe_pfand_voll_brutto += $row['pfand_voll_brutto_soll'];
  $summe_pfand_leer_brutto += $row['pfand_leer_brutto_soll'];
  $summe_pfand_leer_anzahl += $row['pfand_leer_anzahl'];
}
?>
  <tr class='summe'>
    <td colspan='3'>Summe:</td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_voll_brutto ); ?></td>
    <td class='number'><? printf( "%u", $summe_pfand_leer_anzahl ); ?></td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_leer_brutto ); ?></td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_voll_brutto + $summe_pfand_leer_brutto ); ?></td>
  </tr>
<?
if( $basar_row ) {
  ?>
  <tr class='summe'>
    <td colspan='3'>Basar:</td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_voll_brutto_soll'] ); ?></td>
    <td class='number'><? printf( "%u", $row['pfand_leer_anzahl'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_leer_brutto_soll'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_voll_brutto_soll'] - $basar_row['pfand_leer_brutto_soll'] ); ?></td>
  </tr>
  <?
}
if( $muell_row ) {
  ?>
  <tr class='summe'>
    <td colspan='3'>internes Verrechnungskonto:</td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_voll_brutto_soll'] ); ?></td>
    <td class='number'><? printf( "%u", $row['pfand_leer_anzahl'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_leer_brutto_soll'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_voll_brutto_soll'] - $muell_row['pfand_leer_brutto_soll'] ); ?></td>
  </tr>
  <?
}

if( $bestell_id ) {
  ?>
    <tr>
      <td colspan='6'>
        <input type='submit' class='button' value='Speichern'>
      </td>
    </tr>
  </table>
  </form>
  <?
} else {
  ?> </table> <?
}

