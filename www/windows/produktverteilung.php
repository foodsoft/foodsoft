<?php
//
// verteilung.php
//
// zeigt verteilung eines oder aller produkte einer bestellung auf die gruppen an
// und erlaubt aenderung der verteilmengen.

//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();
need_http_var('bestell_id','u',true);
get_http_var('produkt_id','u',0, true);

$status = getState( $bestell_id );

$editable = ( $status == STATUS_VERTEILT and hat_dienst(1,3,4,5) and ! $readonly );

nur_fuer_dienst(1,3,4,5);

setWikiHelpTopic( "foodsoft:verteilung" );

if( $produkt_id ) {
  ?> <h1>Produktverteilung</h1> <?
} else {
  ?> <h1>Verteilliste</h1> <?
}
bestellung_overview( sql_bestellung( $bestell_id ) );


// aktionen verarbeiten; hier: liefer/verteilmengen aendern:
//
get_http_var( 'action', 'w', '' );
$editable or $action = '';
switch( $action ) {
  case 'update_distribution':
    update_distribution( $bestell_id, $produkt_id );
    break;
}

function update_distribution( $bestell_id, $produkt_id ) {
  foreach( sql_bestellung_produkte( $bestell_id, 0, $produkt_id ) as $produkt ) {
    $produkt_id = $produkt['produkt_id'];
    $verteilmult = $produkt['kan_verteilmult'];
    $verteileinheit = $produkt['kan_verteileinheit'];
    $preis = $produkt['endpreis'];
    $liefermenge = $produkt['liefermenge'] * $verteilmult;

    $feldname = "liefermenge_{$bestell_id}_{$produkt_id}";
    global $$feldname;
    if( get_http_var( $feldname, 'f' ) ) {
      $liefermenge_form = $$feldname;
      if( $liefermenge != $liefermenge_form ) {
        changeLiefermengen_sql( $liefermenge_form / $verteilmult, $produkt_id, $bestell_id );
      }
    }

    $gruppen = sql_bestellung_gruppen( $bestell_id, $produkt_id );
    $gruppen[] = array( 'id' => sql_muell_id() );
    foreach( $gruppen as $gruppe ) {
      $gruppen_id = $gruppe['id'];
      $mengen = sql_select_single_row( select_bestellung_produkte( $bestell_id, $gruppen_id, $produkt_id ), true );
      if( $mengen ) {
        $toleranzmenge = $mengen['toleranzbestellmenge'] * $verteilmult;
        $festmenge = $mengen['gesamtbestellmenge'] * $verteilmult - $toleranzmenge;
        $verteilmenge = $mengen['verteilmenge'] * $verteilmult;
      } else {
        $toleranzmenge = 0;
        $festmenge = 0;
        $verteilmenge = 0;
      }
      $feldname = "menge_{$bestell_id}_{$produkt_id}_{$gruppen_id}";
      global $$feldname;
      if( get_http_var( $feldname, 'f' ) ) {
        $menge_form = $$feldname;
        if( $verteilmenge != $menge_form ) {
          changeVerteilmengen_sql( $menge_form / $verteilmult, $gruppen_id, $produkt_id, $bestell_id );
        }
      }
    }
  }
}

medskip();

if( $editable ) {
  open_form( '', 'action=update_distribution' );
  floating_submission_button();
}

open_table('list');
  distribution_tabellenkopf(); 

  foreach( sql_bestellung_produkte( $bestell_id, 0, $produkt_id ) as $produkt ) {
    if( ( $produkt['liefermenge'] < 0.5 ) and ( $produkt['verteilmenge'] < 0.5 ) )
      continue;
    $produkt_id = $produkt['produkt_id'];

    distribution_produktdaten( $bestell_id, $produkt_id );
    distribution_view( $bestell_id, $produkt_id, $editable );
    open_tr();
      open_td( 'medskip', "colspan='6'", '' );
  }
close_table();

if( $editable )
  close_form();

?>
