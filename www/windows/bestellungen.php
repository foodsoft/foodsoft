<?php

assert( $angemeldet ) or exit();

get_http_var( 'orderby', 'w', 'status', true );
switch( $orderby ) {
  case 'name':
    $order = 'gesamtbestellungen.name';
    break;
  case 'lieferant':
    $order = 'lieferanten.name, rechnungsstatus, lieferung DESC';
    break;
  case 'lieferdatum':
    $order = 'lieferung DESC';
    break;
  case 'status':
  default:
    $order = 'rechnungsstatus, lieferung DESC';
    break;
}

get_http_var( 'action', 'w', '' );
$readonly and $action = '';
switch( $action ) {

  case 'changeState':
    nur_fuer_dienst(1,3,4);
    need_http_var( 'change_id', 'u' );
    need_http_var( 'change_to', 'w' );
    if( sql_change_bestellung_status( $change_id, $change_to ) ) {
      switch( $change_to ) {
        case STATUS_LIEFERANT:   // detailanzeige bestellschein oder ...
        case STATUS_VERTEILT:    // ... lieferschein aufrufen:
          echo fc_openwindow( 'bestellschein', "bestell_id=$change_id" );
        break;
      }
    }
    break;

  case 'delete':
    nur_fuer_dienst(4);
    need_http_var( 'delete_id', 'U' );
    need( sql_bestellung_status( $delete_id ) <= STATUS_LIEFERANT );
    sql_delete_bestellzuordnungen( array( 'bestell_id' => $delete_id ) );
    doSql( "DELETE FROM gruppenbestellungen WHERE gesamtbestellung_id = $delete_id " );
    doSql( "DELETE FROM bestellvorschlaege WHERE gesamtbestellung_id = $delete_id " );
    doSql( "DELETE FROM gesamtbestellungen WHERE id = $delete_id " );
    $bestell_id = 0;
    unset( $self_fields['bestell_id'] );
    break;

  case 'combine':
    nur_fuer_dienst(4);
    need_http_var( 'message', '/\d+,\d+/' );
    $sets = explode( ',', $message );
    $abrechnung_id = $sets[0];
    $set2 = sql_abrechnung_set( $sets[1] );
    $lieferanten_id = sql_bestellung_lieferant_id( $abrechnung_id );
    foreach( $set2 as $bestell_id ) {
      need( $lieferanten_id == sql_bestellung_lieferant_id( $bestell_id ), "Nur Bestellungen bei demselben Lieferanten koennen zusammengefasst werden!" );
      sql_update( 'gesamtbestellungen', $bestell_id, array( 'abrechnung_id' => $abrechnung_id ) );
    }
    $set = sql_abrechnung_set( $abrechnung_id );
    $extra_soll = 0;
    $extra_text = '';
    $anzahl_voll = array();
    $anzahl_leer = array();
    foreach( $set as $b_id ) {
      $extra_soll += sql_select_single_field( "SELECT extra_soll FROM gesamtbestellungen WHERE id = $b_id", 'extra_soll' );
      $extra_text .= ( sql_select_single_field( "SELECT extra_text FROM gesamtbestellungen WHERE id = $b_id", 'extra_text' ) . ' ' );
      foreach( sql_lieferantenpfand( $lieferanten_id, $b_id ) as $pfandrow ) {
        $verpackung_id = $pfandrow['verpackung_id'];
        $anzahl_voll[$verpackung_id] = adefault( $anzahl_voll, $verpackung_id, 0 ) + $pfandrow['pfand_voll_anzahl'];
        $anzahl_leer[$verpackung_id] = adefault( $anzahl_leer, $verpackung_id, 0 ) + $pfandrow['pfand_leer_anzahl'];
      }
      if( $b_id == $abrechnung_id )
        continue;
      doSql( "DELETE FROM lieferantenpfand WHERE bestell_id = $b_id" );
      sql_update( 'gesamtbestellungen', $b_id, array( 'extra_soll' => 0, 'extra_text' => '' ) );
    }
    sql_update( 'gesamtbestellungen', $abrechnung_id, array( 'extra_soll' => $extra_soll, 'extra_text' => $extra_text ) );
    foreach( $anzahl_voll as $verpackung_id => $voll ) {
      sql_pfandzuordnung_lieferant( $abrechnung_id, $verpackung_id, $voll, $anzahl_leer[$verpackung_id] );
    }

    break;

  case 'split':
    nur_fuer_dienst(4);
    need_http_var( 'message', 'U' );
    $bestell_id = $message;
    $bestellung = sql_bestellung( $bestell_id );
    if( $bestell_id != $bestellung['abrechnung_id'] ) {
      sql_update( 'gesamtbestellungen', $bestell_id, array( 'abrechnung_id' => $bestell_id ) );
    } else {
      $set = sql_abrechnung_set( $bestellung['abrechnung_id'] );
      $abrechnung_id = 0;
      foreach( $set as $b_id ) {
        if( $b_id == $bestell_id )
          continue;
        if( ! $abrechnung_id )
          $abrechnung_id = $b_id;
        sql_update( 'gesamtbestellungen', $b_id, array( 'abrechnung_id' => $abrechnung_id ) );
      }
    }
    break;

  default:
    break;
}


echo "<h1 class='bigskip'>Liste aller Bestellungen</h1>";

open_table( 'list hfill' );
  open_th();
    echo fc_link( '', 'text=Name,orderby=name,class=href' );
    echo ' / ';
    echo fc_link( '', 'text=Lieferant,orderby=lieferant,class=href' );
  open_th();
    echo fc_link( '', 'text=Status,orderby=status,class=href' );
    echo ' / RNr.';
  open_th('','','Bestellzeit');
  open_th('','', fc_link( '', 'text=Lieferdatum,orderby=lieferdatum,class=href' ) );
  open_th('','','Summe');
  open_th('','','Detailansichten');
  if( $login_dienst != 0 )
    open_th('','','Aktionen');
  if( hat_dienst(4) )
    open_th('','','Abrechnung');

// $bestellungen = sql_bestellungen( 'true', 'rechnungsstatus, abrechnung_id DESC' );
$bestellungen = sql_bestellungen( 'true', $order );
$abrechnung_id = -1;
foreach( $bestellungen as $bestellung ) {
  $abrechnung_id = $bestellung['abrechnung_id'];
  if( $bestellung['abrechnung_id'] ) {
    if( $bestellung['abrechnung_id'] != $bestellung['id'] )
      continue;
    $abrechnung_set = sql_abrechnung_set( $bestellung['abrechnung_id'] );
  } else {
    $abrechnung_set = array( $bestellung['id'] );
  }
  $abrechnung_set_count = count( $abrechnung_set );
  $n = 0;
  foreach( $abrechnung_set as $bestell_id ) {
    $n++;
    $row = sql_bestellung( $bestell_id );

    $views = array();
    $actions = array();
    $combs = array();

    $rechnungsstatus = $row['rechnungsstatus'];
    $abrechnung_dienstkontrollblatt_id = $row['abrechnung_dienstkontrollblatt_id'];

    switch( $rechnungsstatus ) {

      case STATUS_BESTELLEN:
        $views[] = fc_link( 'bestellschein', "class=href,bestell_id=$bestell_id,text=Bestellschein (vorl&auml;ufig)" );
        if( hat_dienst(4) ) {
          if ( $row['bestellende'] < $mysqljetzt ) {
            $actions[] = fc_action( array( 'text' => '>>> Bestellschein fertigmachen >>>'
                                         , 'title' => 'Jetzt Bestellschein für Lieferanten fertigmachen?'
                                         , 'confirm' => 'Jetzt Bestellschein für Lieferanten fertigmachen?' )
                                  , array( 'action' => 'changeState'
                                         , 'change_id' => $bestell_id, 'change_to' => STATUS_LIEFERANT ) );
            $actions[] = fc_action( "title=Bestellung löschen,class=drop,text=löschen,confirm=Bestellung wirklich loeschen?"
                                  , "action=delete,delete_id=$bestell_id" );
          } else {
            $actions[] = "
              <div class='alert qquad'>Bestellzeit läuft noch!</div>
            ";
          }
          $actions[] = fc_link( 'bestellen', array( 'bestell_id' => $bestell_id
                                        , 'class' => 'browse', 'text' => 'zum Bestellen...' ) );
          $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
        }
        break;
  
      case STATUS_LIEFERANT:
        $views[] = fc_link( 'bestellschein', "class=href,bestell_id=$bestell_id,text=Bestellschein" );
        if( $login_dienst > 0 )
          $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id" );
        if( hat_dienst(4) ) {
          $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
          $actions[] = fc_action( array( 'text' => '<<< Nachbestellen lassen <<<'
                                       , 'title' => 'Bestellung nochmal zum Bestellen freigeben?' )
                                , array( 'action' => 'changeState'
                                       , 'change_id' => $bestell_id, 'change_to' => STATUS_BESTELLEN ) );
        }
        if( hat_dienst(1,3,4) )
          $actions[] = fc_action( array( 'text' => '>>> Lieferschein erstellen >>>'
                                       , 'title' => 'Bestellung wurde geliefert, Lieferschein abgleichen?'
                                       , 'confirm' => 'Bestellung wurde geliefert, Lieferschein abgleichen?' )
                                , array( 'action' => 'changeState'
                                       , 'change_id' => $bestell_id, 'change_to' => STATUS_VERTEILT ) );
        if( hat_dienst(4) )
          $actions[] = fc_action( "title=Bestellung löschen,class=drop,text=löschen,confirm=Bestellung wirklich loeschen?"
                                , "action=delete,delete_id=$bestell_id" );
        break;
  
      case STATUS_VERTEILT:
        $views[] = fc_link( 'lieferschein', "class=href,bestell_id=$bestell_id,text=Lieferschein" );
        if( $login_dienst > 0 ) {
          $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id" );
          $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id,ro=1,text=Produktverteilung (Druck)" );
        }
        if( hat_dienst(4) ) {
          $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
          if( $abrechnung_set_count > 1 ) {
            $combs[] = fc_action( 'update,text=Trennen,confirm=Bestellung von Gesamtabrechnung abtrennen?', "action=split,message=$bestell_id" );
          }
          if( $n == $abrechnung_set_count ) {
            $combs[] = "<div class='bigskip'>&nbsp;</div>";
            if( $abrechnung_set_count > 1 ) {
              $combs[] = fc_link( 'gesamtlieferschein', "class=href,abrechnung_id=$abrechnung_id,text=Gesamt-Lieferschein" );
            }
            $combs[] = fc_link( 'abrechnung', "class=href,abrechnung_id=$abrechnung_id,text=Abrechnung beginnen..." );
            $combs[] = "<input type='checkbox' onclick='kombinieren($abrechnung_id);'> Kombinieren";
          }
        }
        break;
  
      case STATUS_ABGERECHNET:
        $views[] = fc_link( 'lieferschein', "class=href,bestell_id=$bestell_id,text=Lieferschein" );
        if( $login_dienst > 0 )
          $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id" );
  
        $views[] = fc_link( 'abrechnung', "class=href,abrechnung_id=$abrechnung_id,bestell_id=$bestell_id,text=Abrechnung" );
  
        if( $n == $abrechnung_set_count ) {
          if( $abrechnung_set_count > 1 ) {
            $combs[] = fc_link( 'gesamtlieferschein', "class=href,abrechnung_id=$abrechnung_id,text=Gesamt-Lieferschein" );
            $combs[] = fc_link( 'abrechnung', "class=href,abrechnung_id=$abrechnung_id,text=Gesamt-Abrechnung" );
          }
        }
  
        break;
  
      case STATUS_ARCHIVIERT:
      default:
        break;
    }
  
    open_tr('',"id='row$bestell_id'" );
      open_td();
        open_div( '','', $row['name'] );
        open_div( 'small','', $row['lieferantenname'] );
        // echo "[$abrechnung_id,$bestell_id,$abrechnung_set_count,$n]";
      open_td();
        open_div( '','', rechnung_status_string( $row['rechnungsstatus'] ) );
        if( $row['rechnungsnummer'] )
          open_div( '','', $row['rechnungsnummer'] );
      open_td();
        open_div( 'left small oneline', '',  $row['bestellstart'] );
        open_div( 'right small oneline', '', "- ".$row['bestellende'] );
      open_td( '', '', $row['lieferung'] );
      open_td();
        if( $rechnungsstatus == STATUS_ABGERECHNET ) {
          open_div( '', '', price_view( sql_bestellung_rechnungssumme( $bestell_id ) ) );
          open_div( 'small', '', sql_dienstkontrollblatt_name( $abrechnung_dienstkontrollblatt_id ) );
        } else {
          echo '-';
        }
      open_td();
        if( $views ) {
          open_ul('plain');
            foreach( $views as $view )
              open_li( '', '', $view );
          close_ul();
        } else {
          echo '-';
        }
      if( $login_dienst != 0 ) {
        open_td();
          if( $actions ) {
            open_ul('plain');
              foreach( $actions as $action )
                open_li( '', '',  $action ); 
            close_ul();
          } else {
            echo '-';
          }
      }
  
      if( hat_dienst(4) ) {
        open_td( ( ( $n == 1 ) ? '' : 'notop ' ) . ( ( $n == $abrechnung_set_count ) ? '' : ' nobottom' ) );
        if( $combs ) {
          open_ul('plain');
            foreach( $combs as $comb )
              open_li( '', '', $comb );
          close_ul();
        }
      }
  }
}
close_table();


open_javascript();
  ?>
    var abrechnung_id = 0;

    function kombinieren( id2 ) {
      if( ! abrechnung_id ) {
        abrechnung_id = id2;
        return;
      }
      if( id2 == abrechnung_id ) {
        abrechnung_id = 0;
        return;
      }
      if( confirm( "Bestellungen zu einer Gesamtabrechnung zusammenfassen?" ) ) {
        post_action( 'combine', abrechnung_id + ',' + id2 );
      } else {
        post_action( 'nop', 0 );
      }
    }
  <?php
close_javascript();

?>
