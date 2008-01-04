<?php
  //
  // book.php
  //
  // debugger fuer tabelle gruppen_transaktion
  //

error_reporting(E_ALL);

assert($angemeldet) or exit();
need( $dienstkontrollblatt_id, "Dienstkontrollblatt-ID nicht gesetzt" );


$gtr = doSql( "
  SELECT * FROM gruppen_transaktion
  WHERE 1
  ORDER BY id
" );


?>
  <table class='list' width='100%'>
  <tr>
    <th>id</th>
    <th>Buchung</th>
    <th>Valuta</th>
    <th>Gruppe</th>
    <th>Kommentar</th>
    <th>Betrag</th>
    <th>K-id</th>
    <th>K-Kommentar</th>
    <th>K-Valuta</th>
    <th>issues</th>
  </tr>
<?

while( $trow = mysql_fetch_array( $gtr ) ) {
  $issues = '';

  $betrag = $trow['summe'];
  $id = $trow['id'];

  $k_id = $trow['konterbuchung_id'];
  $k_kommentar = '(empty)';
  $k_valuta = '(empty)';
  if( $k_id > 0 ) {
    $sql = "SELECT * FROM bankkonto WHERE id = $k_id";
    $result = doSql( $sql );
    $k_rows = mysql_num_rows( $result );
    
    if( $k_rows == 1 ) {
      $k_row = mysql_fetch_array( $result );
      $k_kommentar = $k_row['kommentar'];
      $k_valuta = $k_row['valuta'];
      $k_betrag = $k_row['betrag'];
      if( $k_betrag != $betrag ) {
        $issues .= " [k_betrag: $k_betrag] ";
      }
    } else {
      $issues .= " [$k_rows hits] ";
    }
  } else if ( $k_id < 0 ) {
    $result = doSql( "SELECT * FROM gruppen_transaktion WHERE id = " . (-$k_id) );
    $k_rows = mysql_num_rows( $result );
    if( $k_rows == 1 ) {
      $k_row = mysql_fetch_array( $result );
      $k_kommentar = $k_row['notiz'];
      $k_valuta = $k_row['kontobewegungs_datum'];
      $k_betrag = $k_row['summe'];
      if( $k_betrag != -$betrag ) {
        $issues .= " [k_betrag: $k_betrag] ";
      }
    } else {
      $issues .= " [$k_rows hits] ";
    }
  } else {
    $issues .= " [orphan] ";
  }

  ?>
    <tr>
      <td class='number'><? echo $trow['id']; ?></td>
      <td class='number'><? echo $trow['eingabe_zeit']; ?></td>
      <td class='number'><? echo $trow['kontobewegungs_datum']; ?></td>
      <td class='number'><? echo $trow['gruppen_id']; ?></td>
      <td><? echo $trow['notiz']; ?></td>
      <td class='number'><? printf( "%8.2lf", $trow['summe'] ); ?></td>
      <td class='number'><? echo $trow['konterbuchung_id']; ?></td>
      <td><? echo $k_kommentar; ?></td>
      <td><? echo $k_valuta; ?></td>
      <td>
  <?
  if( $issues ) {
    echo "<div class='warn'>$issues</div>";
  }
  ?> </td> <?

  ?> </tr> <?
}

?> </table> <?


?>

