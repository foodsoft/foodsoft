<?php
//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();  // aufruf nur per index.php?window=basar...

get_http_var( 'orderby', 'w' , 'artikelname', true );
get_http_var( 'bestell_id', 'u' , 0, true );

$editable = ( hat_dienst(4) and ! $readonly );

get_http_var( 'action','w','' );
$editable or $action = '';
if( $action == 'basarzuteilung' ) {
  need_http_var('fieldcount','u' );
  need_http_var('gruppen_id','U', false );
  if( $gruppen_id != sql_muell_id() ) {
    need( sql_gruppe_aktiv( $gruppen_id ) , "Keine aktive Bestellgruppe ausgewaehlt!" );
  }

  for( $i = 0; $i < $fieldcount; $i++ ) {
    if( ! get_http_var( "produkt$i", 'U' ) )
      continue;
    need_http_var( "bestellung$i", 'U' );
    $b_id = ${"bestellung$i"};
    if( sql_bestellung_status( $b_id ) >= STATUS_ABGERECHNET )
      continue;
    if( get_http_var( "menge$i", "f" ) ) {
      $pr = sql_produkt( array( 'bestell_id' => $b_id, 'produkt_id' => ${"produkt$i"} ) );
      $gruppen_menge = ${"menge$i"} / $pr['kan_verteilmult'];
      if( $gruppen_menge > 0 or ( $gruppen_id == $muell_id ) )
        sql_basar2group( $gruppen_id, ${"produkt$i"}, ${"bestellung$i"}, $gruppen_menge );
    }
  }
}

?> <h1>Basar</h1> <?php

basar_view( $bestell_id, $orderby, $editable );

?>
