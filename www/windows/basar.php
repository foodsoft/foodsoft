<?php
//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();  // aufruf nur per index.php?window=basar...

get_http_var( 'orderby', 'w' , 'artikelname', true );
get_http_var( 'bestell_id', 'u' , 0, true );

$editable = ( ! $readonly and ( $dienst == 4 ) );

get_http_var( 'action','w','' );
$editable or $action = '';
if( $action == 'basarzuteilung' ) {
  need_http_var('fieldcount','u' );
  need_http_var('gruppe','U', false );
  if( $gruppe != sql_muell_id() ) {
    $gruppendaten = sql_gruppendaten( $gruppe );
    need( $gruppendaten['aktiv'] , "Keine aktive Bestellgruppe ausgewaehlt!" );
  }

  for( $i = 0; $i < $fieldcount; $i++ ) {
    if( ! get_http_var( "produkt$i", 'u' ) )
      continue;
    need_http_var( "bestellung$i", 'u' );
    $id = ${"bestellung$i"};
    if( getState( $id ) >= STATUS_ABGERECHNET )
      continue;
    if( get_http_var( "menge$i", "f" ) ) {
        $pr = sql_bestellvorschlag_daten( $id, ${"produkt$i"} );
        preisdatenSetzen( & $pr );
        $gruppen_menge = ${"menge$i"} / $pr['kan_verteilmult'];
        if( $gruppen_menge > 0 or ( $gruppe == $muell_id ) )
          sql_basar2group( $gruppe, ${"produkt$i"}, ${"bestellung$i"}, $gruppen_menge );
    }
  }
}


?> <h1>Basar</h1> <?

basar_overview( $bestell_id, $orderby, $editable );

?>

