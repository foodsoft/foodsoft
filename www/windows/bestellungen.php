<?php

assert( $angemeldet ) or exit();


get_http_var( 'action', 'w', '' );
$readonly and $action = '';
switch( $action ) {

  case 'changeState':
    nur_fuer_dienst(1,3,4);
    need_http_var( 'change_id', 'u' );
    need_http_var( 'change_to', 'w' );
    if( sql_change_bestellung_status( $change_id, $change_to ) ) {
      if( ! $bestell_id ) {  // falls nicht bereits in detailanzeige:
        switch( $change_to ) {
          case STATUS_LIEFERANT:   // bestellschein oder ...
          case STATUS_VERTEILT:    // ... lieferschein anzeigen:
            echo fc_openwindow( 'bestellschein', "bestell_id=$change_id" );
          break;
        }
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
  default:
    break;
}


echo "<h1 class='bigskip'>Liste aller Bestellungen</h1>";

open_table( 'list hfill' );
  open_th('','','Name');
  open_th('','','Status');
  open_th('','','Bestellzeit');
  open_th('','','Lieferung');
  open_th('','','Summe');
  open_th('','','Detailansichten');
  if( $login_dienst != 0 ) {
    open_th('','','Aktionen');
    open_th('','','Zusammenfassen');
  }

$bestellungen = sql_bestellungen( 'true', 'rechnungsstatus, abrechnung_id' );
$abrechnung_id = -1;
foreach( $bestellungen as $row ) {
  $bestell_id = $row['id'];
  $rechnungsstatus = sql_bestellung_status( $bestell_id );
  $abrechnung_dienstkontrollblatt_id = $row['abrechnung_dienstkontrollblatt_id'];
  $views = array();
  $actions = array();

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
        } else {
          $actions[] = "
            <div class='alert qquad'>Bestellung läuft noch!</div>
            <div class='alert qquad'>".fc_link( 'bestellen', array( 'bestell_id' => $bestell_id
                                      , 'class' => 'href', 'text' => 'zum Bestellen...' ) )."</div>
          ";
        }
        $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
        $actions[] = fc_action( "title=Bestellung löschen,class=drop,text=löschen,confirm=Bestellung wirklich loeschen?"
                              , "action=delete,delete_id=$bestell_id" );
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
      if( $login_dienst > 0 )
        $actions[] = fc_action( array( 'text' => '>>> Lieferschein erstellen >>>'
                                     , 'title' => 'Bestellung wurde geliefert, Lieferschein abgleichen?'
                                     , 'confirm' => 'Bestellung wurde geliefert, Lieferschein abgleichen?' )
                              , array( 'action' => 'changeState'
                                     , 'change_id' => $bestell_id, 'change_to' => STATUS_VERTEILT ) );
        $actions[] = fc_action( "title=Bestellung löschen,class=drop,text=löschen,confirm=Bestellung wirklich loeschen?"
                              , "action=delete,delete_id=$bestell_id" );
      break;

    case STATUS_VERTEILT:
      $views[] = fc_link( 'lieferschein', "class=href,bestell_id=$bestell_id,text=Lieferschein" );
      if( $login_dienst > 0 )
        $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id" );
      if( hat_dienst(4) ) {
        $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
        $actions[] = fc_link( 'abrechnung', "bestell_id=$bestell_id,text=Abrechnung beginnen..." );
      }
      break;

    case STATUS_ABGERECHNET:
      $views[] = fc_link( 'lieferschein', "class=href,bestell_id=$bestell_id,text=Lieferschein" );
      $views[] = fc_link( 'abrechnung', "class=href,bestell_id=$bestell_id" );
      if( $login_dienst > 0 )
        $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id" );
      break;

    case STATUS_ARCHIVIERT:
    default:
      break;
  }

  open_tr('',"id='row$bestell_id'" );
    open_td('','', $row['name'] );
    open_td('','', rechnung_status_string( $row['rechnungsstatus'] ) );
    open_td();
      open_div( 'left small', '',  $row['bestellstart'] );
      open_div( 'right small', '', "- ".$row['bestellende'] );
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
      if( $row['abrechnung_id'] != $abrechnung_id ) {
        $abrechnung_id = $row['abrechnung_id'];
        $abrechnung_set = sql_abrechnung_set( $abrechnung_id );
        open_td( ( count( $abrechnung_set ) > 1 ) ? 'nobottom' : '' );
      } else {
        open_td( 'notop' );
      }
      if( count( $abrechnung_set ) > 1 ) {
        fc_action( 'update,text=Trennen', "action=split,message=$bestell_id" );
      }
      
    }
}
close_table();

?>
