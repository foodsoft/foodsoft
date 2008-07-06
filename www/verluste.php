<?php
//
// verluste.php:
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
// need_http_var( 'bestell_id', 'u', true );

$muell_id = sql_muell_id();



?>
<h2>Verlustaufstellung: Achtung, noch unvollstaendig!</h2>



<h3>Internes Verrechnungskonto:</h3>
<table class='numbers'>
  <tr>
    <th>Id</th>
    <th>Art</th>
    <th>Valuta</th>
    <th>Notiz</th>
    <th>Betrag</th>
    <th>Verlust</th>
  </tr>

<?

$result = doSql( "
  SELECT gruppen_transaktion.type as type
  FROM gruppen_transaktion
  GROUP BY type
" );

$types = array();
while( $row = mysql_fetch_array( $result ) ) {
  $types[] = $row['type'];
}


$result = doSql( "
  SELECT gruppen_transaktion.*
  FROM gruppen_transaktion
  WHERE gruppen_transaktion.gruppen_id = $muell_id
  ORDER BY type, kontobewegungs_datum
" );


$soll_summe = array();
$soll_total = 0.0;
$type = -1;

$row = mysql_fetch_array( $result );
while( $row ) {
  $soll = $row['summe'];
  if( $type != $row['type'] ) {
    ?>
      <tr>
        <th colspan='6' style='padding:1ex;'>
          <? echo transaktion_typ_string( $row['type'] ); ?>
        </th>
      </tr>
    <?
  }
  $type = $row['type'];
  if( ! isset( $soll_summe[$type] ) )
    $soll_summe[$type] = 0.0;

  ?>
    <tr>
      <td><a href="javascript:window.open('index.php?window=editBuchung&transaktion_id=<? echo $row['id']; ?>','buchung','width=490,height=620,left=200,top=100').focus();"><? echo $row['id']; ?></a></td>
      <td><? echo transaktion_typ_string( $type ); ?></td>
      <td><? echo $row['kontobewegungs_datum']; ?></td>
      <td><? echo $row['notiz']; ?></td>
      <td class='number'><? printf( "%.2lf", $soll ); ?></td>
      <td class='number'><? printf( "%.2lf", $soll_total ); ?></td>
    </tr>
  <?
  $soll_summe[$type] += $soll;
  $soll_total += $soll;

  $row = mysql_fetch_array( $result );
  if( ! $row or ( $row['type'] != $type ) ) {
    ?>
      <tr class='summe'>
        <td colspan='5'>Zwischensumme <? echo transaktion_typ_string( $type ); ?>:</td>
        <td class='number'><? printf( "%.2lf", $soll_summe[$type] ); ?></td>
      </tr>
    <?
  }
}
?>
  <tr class='summe'>
    <td colspan='5'>Summe:</td>
    <td class='number'><? printf( "%.2lf", $soll_total ); ?></td>
  </tr>
</table>

<h3>Differenzen aus Bestellungen:</h3>
<table class='numbers'>
  <tr>
      <th>Bestellung</th>
      <th>Schwund/Müll</th>
      <th colspan='2'>Sonstiges</th>
      <th>Verlust</th>
    </tr>

<?
$result = doSql( "
  SELECT gesamtbestellungen.*
  , (" .select_bestellungen_soll_gruppen( OPTION_ENDPREIS_SOLL, array( 'gesamtbestellungen', 'bestellgruppen' ) ). ") as muell_soll
  FROM gesamtbestellungen
  JOIN bestellgruppen ON bestellgruppen.id = $muell_id
  HAVING ( extra_soll <> 0 ) OR ( muell_soll <> 0)
  ORDER BY gesamtbestellungen.lieferung
" );

$muell_soll_summe = 0;
$extra_soll_summe = 0;
$soll_summe = 0;

while( $row = mysql_fetch_array( $result ) ) {
  $muell_soll = - $row['muell_soll'];
  $extra_soll = $row['extra_soll'];
  $soll = $muell_soll + $extra_soll;

  ?>
    <tr>
      <td><a href="<? echo abrechnung_url( $row['id'] ); ?>"><? echo $row['name']; ?></a></td>
      <td class='number'><? printf( "%.2lf", $muell_soll ); ?></td>
      <td><? echo $row['extra_text']; ?></td>
      <td class='number'><? printf( "%.2lf", $extra_soll ); ?></td>
      <td class='number'><? printf( "%.2lf", $soll ); ?></td>
    </tr>
  <?
  $muell_soll_summe += $muell_soll;
  $extra_soll_summe += $extra_soll;
  $soll_summe += $soll;
}
?>
  <tr class='summe'>
    <td>Summe:</td>
    <td class='number'><? printf( "%.2lf", $muell_soll_summe ); ?></td>
    <td>&nbsp;</td>
    <td class='number'><? printf( "%.2lf", $extra_soll_summe ); ?></td>
    <td class='number'><? printf( "%.2lf", $soll_summe ); ?></td>
  </tr>
</table>
<?


