<?php
//
// bestellschein.php: detailanzeige bestellschein / lieferschein, abhaengig vom status der bestellung
//

error_reporting(E_ALL);
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

assert( $angemeldet ) or exit();

need_http_var( 'abrechnung_id', 'U', true );
$bestell_ids = sql_abrechnung_set( $abrechnung_id );


echo "<h1>$Gesamtlieferschein</h1>";

$produkt_ids = doSql( "
  SELECT DISTINCT produkt_id
  FROM bestellvorschlaege
  WHERE gesamtbestellung.id IN $bestell_ids_list
" );

open_table();
  open_tr();
    open_th( 'Name' );
    open_th( 'A-Nummer' );
    open_th( 'B-Nummer' );
    open_th( 'L-Preis', "colspan='2'" );
    open_th( 'Menge' );
    open_th( 'Netto' );
    open_th( 'Brutto' );
close_table();

?>
