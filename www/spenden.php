<?php
//
// verluste.php:
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
get_http_var( 'optionen', 'u', 0, true );

$muell_id = sql_muell_id();

$result = sql_spenden();

?>
<h2>Spenden</h2>

<table class='numbers'>
  <tr>
    <th>Id</th>
    <th>Datum</th>
    <th>Spender</th>
    <th>Notiz</th>
    <th>Betrag</th>
  </tr>
<?

$summe = 0.0;

while( $row = mysql_fetch_array( $result ) ) {
  $haben = $row['haben'];
  ?>
    <tr>
      <td><? echo fc_alink( 'edit_buchung', "transaktion_id={$row['id']},img=,text={$row['id']}" ); ?></td>
      <td><? echo $row['valuta']; ?></td>
      <td><? echo $row['name']; ?></td>
      <td><? echo $row['notiz']; ?></td>
      <td class='number'><? printf( "%.2lf", $haben ); ?></td>
    </tr>
  <?
  $summe += $haben;
}
?>
  <tr class='summe'>
    <td colspan='4' style='text-align:right;'>Summe:</td>
    <td class='number'><? printf( "%.2lf", $summe ); ?></td>
  </tr>
</table>


