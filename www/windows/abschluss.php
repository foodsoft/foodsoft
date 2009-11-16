<?php
//
// abschluss.php:
//

error( 'men at work --- funktioniert noch nicht!' );

assert( $angemeldet ) or exit();
$editable = ( hat_dienst(4) and ! $readonly );

setWikiHelpTopic( 'foodsoft:Abschluss' );

get_http_var( 'action', 'w', '' );
$editable or $action = '';

switch( $action ) {
  case 'garbage_collection':
    logger( 'garbage collection: start' );
    $n = 0;
    foreach( sql_produkte() as $p ) {
      foreach( sql_produktpreise( $p['produkt_id'] as $preis ) {
        if( $preis['datum_ende'] and ! references_produktpreis( $preis['id'] ) ) {
          sql_delete_produktpreis( $preis['id'] );
          $n++;
        }
      }
    }
    logger( "garbage collection: $n produktpreise geloescht" );

    doSql( "DELETE FROM transactions WHERE used OR ( session_id != $session_id )" );

    logger( "garbage collection: ende" );
    break;

  case 'abschluss':
    need_http_var( 'year', 'd' );
    need_http_var( 'month', 'd' );
    need_http_var( 'day', 'd' );

    $date = "$year-$month-$day";
    $bestellungen = sql_bestellungen( "lieferung <= '$date'", 'lieferung' );
    $bestellungen_archiviert = array();
    $gruppen = sql_gruppen();
    $lieferanten = sql_lieferanten();

    foreach( $gruppen as & $g ) {
      $g['saldo'] = 0.0;
    }
    foreach( $lieferanten as & $l ) {
      $l['saldo'] = 0.0;
    }
    foreach( $bestellungen as $b ) {
      if( $b['lieferung] > $date )
        continue;
      need( $b['rechnungsstatus'] >= STATUS_ABGERECHNET, "bestellung {$b['name']} noch nicht abgerechnet" );
      $bestell_id = $b['id'];
      foreach( $gruppen as & $g ) {
        $soll = sql_bestellungen_soll_gruppe( $g['id'], $bestell_id )
        $g['saldo] += ( $soll['waren_brutto_soll'] + $soll['aufschlag_soll']
                        + $soll['pfand_leer_brutto_soll'] + $soll['pfand_voll_brutto_soll'] );
      }
      foreach( $lieferanten as & $l ) {
        $soll = sql_bestellungen_soll_lieferant( $l['id'], $bestell_id );
        $l['saldo'] += (
                        
        

      $bestellungen_abgeschlossen[] = $b['id'];
    }

    foreach( $bestellungen_abgeschlossen as $key => $bestell_id ) {
      sql_delete_bestellzuordnungen( 'bestell_id' => $bestell_id );
      doSql( "DELETE FROM gruppenbestellungen WHERE gesamtbestellung_id = $bestell_id" );
      doSql( "DELETE FROM bestellvorschlaege WHERE gesamtbestellung_id = $bestell_id" );
      doSql( "DELETE FROM lieferantenpfand WHERE bestell_id = $bestell_id" );
      doSql( "DELETE FROM gesamtbestellungen WHERE id = $bestell_id" );
    }

    doSql( "DELETE FROM dienste WHERE lieferdatum <= '$date' " );

    break;
}



echo "<h1>Jahresabschluss</h1>";

open_table( 'layout' );
close_table();

?>
