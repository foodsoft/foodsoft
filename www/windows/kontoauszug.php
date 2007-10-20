<?PHP

assert($angemeldet) or exit();
 
setWindowSubtitle( 'Kontoauszug' );
setWikiHelpTopic( 'foodsoft:kontoauszug' );

need_http_var( 'konto_id', 'u', true );
need_http_var( 'auszug_jahr', 'u', true );
need_http_var( 'auszug_nr', 'u', true );

$auszug = sql_kontoauszug( $konto_id, $auszug_jahr, $auszug_nr );
need( mysql_num_rows( $auszug ) > 0, "Keine Posten vorhanden" );

$result = sql_saldo( $konto_id, $auszug_jahr, $auszug_nr-1 );
if( mysql_num_rows( $result ) < 1 ) {
  $startsaldo = 0.0;
} else {
  need( mysql_num_rows( $result ) == 1 );
  $row = mysql_fetch_array( $result );
  $startsaldo = $row['saldo'];
}

$result = sql_saldo( $konto_id, $auszug_jahr, $auszug_nr );
need( mysql_num_rows( $result ) == 1 );
$row = mysql_fetch_array( $result );
$saldo = $row['saldo'];
$bankname = $row['name'];

echo "
  <h1>Kontoauszug: $bankname - $auszug_jahr / $auszug_nr</h1>
  <table class='liste'>
    <tr class='legende'>
      <th>Nr</th>
      <th>Text</th>
      <th>Betrag</th>
    </tr>
";

printf( "
    <tr class='summe'>
      <td colspan='2' style='text-align:right;'>Startsaldo:</td>
      <td class='number'>%.2lf</td>
    </tr>
  "
, $startsaldo
);

$n=0;
while( $row = mysql_fetch_array( $auszug ) ) {
  $n++;
  echo "
    <tr>
      <td class='number'>$n</td>
      <td>
  ";
  $gid = $row['gruppen_id'];
  $lid = $row['lieferanten_id'];
  $kommentar = $row['kommentar'];
  if( $gid ) {
    printf( "<p>Überweisung Gruppe %d (%s)<p>" , $gid % 1000, sql_gruppenname( $gid ) );
  }
  if( $lid ) {
    printf( "<p>Überweisung Lieferant %s<p>" , lieferant_name( $gid ) );
  }
  if( $kommentar ) {
    echo "<p>$kommentar</p>";
  }
  printf( "<td class='number' style='vertical-align:bottom;'>%.2lf</td>", $row['betrag'] );
  echo "</tr>";
}

printf( "
    <tr class='summe'>
      <td colspan='2' style='text-align:right;'>Saldo:</td>
      <td class='number'>%.2lf</td>
    </tr>
  "
, $saldo
);

?> </table> <?


?>


