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

$status = sql_bestellung_status( $bestell_id );

$editable = ( $status == STATUS_VERTEILT and hat_dienst(1,3,4,5) );

nur_fuer_dienst(1,3,4,5);

get_http_var( 'druck', 'u', 0, true );
if( $druck or $readonly )
  $editable = false;
if( !$editable && $status < STATUS_ABGERECHNET )
  $druck = 1;

setWikiHelpTopic( "foodsoft:verteilung" );

if( $status < STATUS_LIEFERANT ) {
  ?> <h1>Bestellmengen</h1> <?php
} else {
  if( $produkt_id ) {
    ?> <h1>Produktverteilung</h1> <?php
  } else {
    ?> <h1>Verteilliste</h1> <?php
  }
}
bestellung_overview( $bestell_id );


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
  foreach( sql_bestellung_produkte( [
      'bestell_id' => $bestell_id
    , 'produkt_id' => $produkt_id] )
    as $produkt ) {
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
        sql_change_liefermenge( $bestell_id, $produkt_id, $liefermenge_form / $verteilmult );
      }
    }

    $gruppen = sql_gruppen( array( 'bestell_id' => $bestell_id, 'produkt_id' => $produkt_id ) );
    $gruppen[] = array( 'id' => sql_muell_id() );
    foreach( $gruppen as $gruppe ) {
      $gruppen_id = $gruppe['id'];
      $mengen = sql_select_single_row( select_bestellung_produkte( [
            'bestell_id' => $bestell_id
          , 'produkt_id' => $produkt_id
          , 'gruppen_id' => $gruppen_id
          ] )
        , true );
      if( $mengen ) {
        $toleranzmenge = $mengen['toleranzbestellmenge'] * $verteilmult;
        $festmenge = $mengen['gesamtbestellmenge'] * $verteilmult - $toleranzmenge;
        if( $gruppen_id == sql_muell_id() ) {
          $verteilmenge = $mengen['muellmenge'] * $verteilmult;
        } else {
          $verteilmenge = $mengen['verteilmenge'] * $verteilmult;
        }
      } else {
        $toleranzmenge = 0;
        $festmenge = 0;
        $verteilmenge = 0;
      }
      $feldname = "menge_{$bestell_id}_{$produkt_id}_{$gruppen_id}";
      global $$feldname;
      if( get_http_var( $feldname, 'f' ) ) {
        $menge_form = $$feldname;
        // echo "[$feldname, $menge_form, $verteilmenge]<br>";
        if( $verteilmenge != $menge_form ) {
          sql_change_verteilmenge( $bestell_id, $produkt_id, $gruppen_id, $menge_form / $verteilmult );
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

if( $druck ) {
  $lieferant_id = sql_bestellung_lieferant_id( $bestell_id );
  $lieferant = sql_lieferant( $lieferant_id );

  $druck = $lieferant['distribution_druck_preisspalte'] ? Distribution_Druck::Menge_Preis : Distribution_Druck::Menge;
} else {
  $druck = Distribution_Druck::Nein;
}

open_table('list');
  distribution_tabellenkopf($status, $druck);

  foreach( sql_bestellung_produkte( [
      'bestell_id' => $bestell_id
    , 'produkt_id' => $produkt_id] )
    as $produkt ) {
    if( $status < STATUS_LIEFERANT) {
      if ( $produkt['gesamtbestellmenge'] < 0.001 )
        continue;
    } else {
      if( ( $produkt['liefermenge'] < 0.001 ) and ( $produkt['verteilmenge'] < 0.001 ) )
        continue;
    }

    $produkt_id = $produkt['produkt_id'];

    open_tag( 'tbody' );
      distribution_produktdaten( $status, $bestell_id, $produkt_id, $druck );
      distribution_view( $status, $bestell_id, $produkt_id, $editable, $druck );
      open_tr();
        open_td( 'medskip noleft noright notop nobottom', "colspan='".(6 + ($druck !== Distribution_Druck::Nein ? $druck->value + 2 : 0) )."'", '' );
      close_tr();
    close_tag( 'tbody' );
  }
close_table();

if( $editable )
  close_form();

?>
