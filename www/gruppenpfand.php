<?PHP

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );

need_http_var( 'bestell_id', 'u', true );

$bestellung_name = bestellung_name( $bestell_id );
$lieferanten_id = getProduzentBestellID( $bestell_id );
$lieferant_name = lieferant_name( $lieferanten_id );

get_http_var( 'optionen', 'u', 0, true );
define( 'OPTION_GRUPPEN_INAKTIV', 1 );

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
            <? if( $optionen & OPTION_GRUPPEN_INAKTIV ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ OPTION_GRUPPEN_INAKTIV );
            ?>';"
            title='Auch inaktive Gruppen in Pfandübersicht aufnehmen?'
          > auch inaktive Gruppen anzeigen?
        </td>
      </tr>
    </table>
  </td>
  <td>
    <h3>Gruppenpfand: Bestellung <? echo "$bestellung_name ({$lieferant_name})"; ?></h3>
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

// if( $bestell_id and ( $action == 'save' ) ) {
//   $verpackungen = sql_pfandverpackungen( $lieferanten_id, $bestell_id );
//   while( $row = mysql_fetch_array($verpackungen)) {
//     $id = $row['verpackung_id'];
//     if( get_http_var( "anzahl_kauf$id", 'u' ) and get_http_var( "anzahl_rueckgabe$id", 'u' ) ) {
//       sql_pfandzuordnung_lieferant( $bestell_id, $id, ${"anzahl_kauf$id"}, ${"anzahl_rueckgabe$id"} );
//     }
//   }
// }
// 

/////////////////////////////
//
// Pfandzettel anzeigen:
//
/////////////////////////////


$gruppen = sql_gruppenpfand( $lieferanten_id, $bestell_id );

?>
<table class='numbers'>
  <tr>
    <th>Gruppe</th>
    <th>Nr (Id)</th>
    <th>aktiv</th>
    <th title='Pfand für Bestellungen in Rechnung gestellt'>Wert berechnet</th>
    <th title='Anzahl zurückgegebene Pfandverpackungen'>Anzahl gutgeschrieben</th>
    <th title='Gutschrift für zurürckgegebene Pfandverpackungen'>Wert gutgeschrieben</th>
    <th>Differenz</th>
  </tr>
<?
$summe_pfand_haben = 0;
$summe_pfand_soll = 0;
$muell_row = false;
$basar_row = false;
while( $row = mysql_fetch_array( $gruppen ) ) {
  if( $row['gruppen_id'] == $muell_id ) {
    $muell_row = $row;
    continue;
  }
  if( $row['gruppen_id'] == $basar_id ) {
    $basar_row = $row;
    continue;
  }
  if( ! ( $row['aktiv'] or ( $optionen & OPTION_GRUPPEN_INAKTIV ) ) )
    continue;
  ?>
    <tr>
      <td><? echo $row['gruppen_name']; ?></td>
      <td><? echo "{$row['gruppen_nummer']} ({$row['gruppen_id']})"; ?></td> 
      <td><? echo $row['aktiv']; ?></td> 
      <td class='number'><? printf( "%.2lf", $row['pfand_haben'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['pfand_soll'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['pfand_haben'] - $row['pfand_soll'] ); ?></td>
    </tr>
  <?
  $summe_pfand_haben += $row['pfand_haben'];
  $summe_pfand_soll += $row['pfand_soll'];
}
?>
  <tr class='summe'>
    <td colspan='3'>Summe:</td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_haben ); ?></td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_soll ); ?></td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_haben - $summe_pfand_soll ); ?></td>
  </tr>
<?
if( $basar_row ) {
  ?>
  <tr class='summe'>
    <td colspan='3'>Basar:</td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_haben'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_soll'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_haben'] - $basar_row['pfand_soll'] ); ?></td>
  </tr>
  <?
}
if( $muell_row ) {
  ?>
  <tr class='summe'>
    <td colspan='3'>internes Verrechnungskonto:</td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_haben'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_soll'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_haben'] - $muell_row['pfand_soll'] ); ?></td>
  </tr>
  <?
}
?>
</table>

