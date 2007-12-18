<?php
//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();  // aufruf nur per index.php?area=basar...

// self-variable:
//
get_http_var( 'orderby', 'w' , 'artikelname', true );
get_http_var( 'bestell_id', 'u' , 0, true );

$editable = ( ! $readonly and ( $dienst == 4 ) );

if( $editable ) {

  // aktionen:
  //
  get_http_var('gruppe','u', false, false );
  if( $gruppe > 0 ) {

    // glassrueckgabe bearbeiten:
    //
    if( get_http_var('menge_glas','u', 0, false) ) {
      fail_if_readonly();
      nur_fuer_dienst(4);
      sql_groupGlass( $gruppe, $menge_glas );
      // echo "glasrueckgabe: $gruppe, $menge_glas";
    }

    // basarzuteilungen bearbeiten:
    //
    for( $i = 0; get_http_var( "produkt$i", 'u' ) ; $i++ ) {
      need_http_var( "bestellung$i", 'u' );
      if( get_http_var( "menge$i", "f" ) ) {
  //      if( ${"menge$i"} > 0 ) {
          fail_if_readonly();
          nur_fuer_dienst(4);
          $pr = sql_bestellvorschlag_daten( ${"bestellung$i"}, ${"produkt$i"} );
          kanonische_einheit( $pr['verteileinheit'], &$kan_verteileinheit, &$kan_verteilmult );
          $gruppen_menge = ${"menge$i"} / $kan_verteilmult;
          sql_basar2group($gruppe, ${"produkt$i"}, ${"bestellung$i"}, $gruppen_menge);
  //      }
      }
    }
  }
  get_http_var( 'action','w','' );
  if( $action == 'schwund' ) {
    need_http_var( 'produkt_id', 'u' );
    need_http_var( 'bestellung', 'u' );
    need_http_var( 'menge', 'f' );
    need( $muell_id );
    // echo "Schwundbuchung: $produkt_id, $bestellung, $menge<br>";
    sql_basar2group( $muell_id, $produkt_id, $bestellung, $menge );
  }
}


?> <h1>Basar</h1> <?

basar_overview( $bestell_id, $orderby, $editable );

?>
   <form action='index.php' method='post'>
     <input type='submit' value='Zur&uuml;ck '>
   </form>

