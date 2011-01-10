<?php
//
// bestellschein.php: detailanzeige gesamtlieferschein, abhaengig vom status der bestellung
//

error_reporting(E_ALL);
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

assert( $angemeldet ) or exit();

need_http_var( 'abrechnung_id', 'U', true );
$bestell_id_set = sql_abrechnung_set( $abrechnung_id );

$bestellungen = array();
$bestell_id_list = '';
$komma = '(';
foreach( $bestell_id_set as $b ) {
  $bestell_id_list .= "$komma $b";
  $komma = ',';
  $bestellungen[] = sql_bestellung( $b );
}
$bestell_id_list .= ')';


abrechnung_overview( $abrechnung_id );
medskip();

echo "<h1>Gesamtlieferschein</h1>";


$p = mysql2array( doSql( "
  SELECT DISTINCT produkt_id
  FROM bestellvorschlaege
  WHERE gesamtbestellung_id IN $bestell_id_list
" ) );
$produkt_ids = array();
foreach( $p as $q ) {
  $produkt_ids[] = $q['produkt_id'];
}

open_table('list');
  open_tr();
    open_th( '', '', 'Produkt' );
    open_th( '', '', 'Bestellung' );
    open_th( '', '', 'Lieferung' );
    open_th( '', '', 'A-Nummer' );
    open_th( '', '', 'B-Nummer' );
    open_th( '', "colspan='2'", 'L-Preis' );
    open_th( '', "colspan='2'", 'Menge' );
    open_th( '', '', 'Netto' );
    open_th( '', '', 'Brutto' );


  $netto_total = 0;
  $brutto_total = 0;
  foreach( $produkt_ids as $produkt_id ) {
    $stammdaten = sql_produkt( array( 'produkt_id' => $produkt_id ) );
    $produktbestellungen = array();
    foreach( $bestellungen as $b ) {
      $bestell_id = $b['id'];
      $r = sql_bestellung_produkte( $bestell_id, $produkt_id );
      switch( count($r) ) {
        case 0:
          continue 2;
        case 1:
          $p = $r[0];
          if( $p['liefermenge'] < 0.001 )
            continue 2;
          $p['name'] = $b['name'];
          $p['lieferung'] = $b['lieferung'];
          $p['bestell_id'] = $bestell_id;
          $produktbestellungen[] = $p;
          break;
        default: 
          error( "internal error: unexpected count: " . count($r) );
      }
    }
    $rowcount = count( $produktbestellungen );
    if( $rowcount < 1 )
      continue;
    open_tr();
    $rowcount++;
    open_td( 'bold top', "rowspan='$rowcount'", $stammdaten['name'] );
    $netto_summe = 0;
    $brutto_summe = 0;
    $liefermenge_summe = 0;
    $first = true;
    foreach( $produktbestellungen as $p ) {
      if( ! $first )
        open_tr();
      $first = false;
      $liefermenge = $p['liefermenge'];
      $netto = $liefermenge * $p['nettopreis'];
      $brutto = $liefermenge * $p['bruttopreis'];
      $liefermenge_summe += $liefermenge;
      $netto_summe += $netto;
      $brutto_summe += $brutto;
      open_td( 'left', '', $p['name'] );
      open_td( 'center', '', $p['lieferung'] );
      open_td( 'center', '', $p['artikelnummer'] );
      open_td( 'right', '', $p['bestellnummer'] );
      open_td( 'mult', '', fc_link( 'produktdetails', array( 'class' => 'href'
      , 'produkt_id' => $produkt_id, 'bestell_id' => $p['bestell_id'], 'text' => price_view( $p['nettolieferpreis'] ) ) ) );
      open_td( 'unit', '', " / " . $p['liefereinheit_anzeige'] );
      open_td( 'mult', '', sprintf( '%.3lf', $p['liefermenge'] * $p['kan_liefermult_anzeige'] / $p['lv_faktor'] ) );
      open_td( 'unit', '', $p['kan_liefereinheit_anzeige'] );
      open_td( 'number', '', price_view( $netto ) );
      open_td( 'number', '', price_view( $brutto ) );
    }
    open_tr( 'summe' );
    open_td( '', "colspan='8'", 'Summe:' );
    open_td( 'number', '', price_view( $netto_summe ) );
    open_td( 'number', '', price_view( $brutto_summe ) );
    $netto_total += $netto_summe;
    $brutto_total += $brutto_summe;
  }
  open_tr( 'summe' );
    open_td( '', "colspan='9'", 'Gesamtsumme:' );
    open_td( 'number', '', price_view( $netto_total ) );
    open_td( 'number', '', price_view( $brutto_total ) );


close_table();

?>
