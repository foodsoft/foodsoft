<?php

assert( $angemeldet ) or exit();

// 'since' sets the lower boundary of the time period displayed (upper boundary is NOW) as Unix epoch timestamp
get_http_var('since', 'U', -1);
if ( $since === -1 ) {
  // show last 2 years by default, or the datetime of the last unfinished order (if older)
  $oldest_unfinished = sql_bestellung_oldest_unfinished_timestamp();
  $two_years_ago = (new DateTime())->modify('-2 years')->getTimestamp(); 
  $since = min($oldest_unfinished, $two_years_ago);
}
$sinceDate = (new DateTime())->setTimestamp($since)->format("Y-m-d");

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
      need(
        $lieferanten_id == sql_bestellung_lieferant_id( $bestell_id ),
        'Nur Bestellungen bei demselben Lieferanten können zusammengefasst werden!'
      );
      sql_update(
        'gesamtbestellungen',
        $bestell_id,
        [ 'abrechnung_id' => $abrechnung_id ]
      );
    }
    $set = sql_abrechnung_set( $abrechnung_id );
    $extra_soll = 0;
    $extra_text = '';
    $anzahl_voll = array();
    $anzahl_leer = array();
    foreach( $set as $b_id ) {
      $extra_soll += sql_select_single_field(
        "SELECT extra_soll FROM gesamtbestellungen WHERE id = $b_id",
        'extra_soll'
      );
      $extra_text .= ( sql_select_single_field(
        "SELECT extra_text FROM gesamtbestellungen WHERE id = $b_id",
        'extra_text'
        ) . ' '
      );
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
      sql_update(
        'gesamtbestellungen',
        $bestell_id,
        [ 'abrechnung_id' => $bestell_id ]
      );
    } else {
      $set = sql_abrechnung_set( $bestellung['abrechnung_id'] );
      $abrechnung_id = 0;
      foreach( $set as $b_id ) {
        if( $b_id == $bestell_id )
          continue;
        if( ! $abrechnung_id )
          $abrechnung_id = $b_id;
        sql_update(
          'gesamtbestellungen',
          $b_id,
          [ 'abrechnung_id' => $abrechnung_id ]
        );
      }
    }
    break;

  default:
    break;
}


echo "<h1 class='bigskip'>Liste aller Bestellungen (seit {$sinceDate})</h1>";

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

$selected_since_condition = "`lieferung` > FROM_UNIXTIME({$since})";
$bestellungen = sql_bestellungen( $selected_since_condition, $order );
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
    $row_css_class = [
      STATUS_BESTELLEN =>   'orderstatus_ordering',
      STATUS_LIEFERANT =>   'orderstatus_ordered',
      STATUS_VERTEILT =>    'orderstatus_distributed',
      STATUS_ABGERECHNET => '',
      STATUS_ARCHIVIERT =>  '',
    ][$rechnungsstatus];
    $abrechnung_dienstkontrollblatt_id = $row['abrechnung_dienstkontrollblatt_id'];

    switch( $rechnungsstatus ) {

      case STATUS_BESTELLEN:
        $views[] = fc_link(
          'bestellschein',
          [
            'class'      => 'href',
            'bestell_id' => $bestell_id,
            'text'       => 'Bestellschein (vorläufig)',
          ]
        );
        if( hat_dienst(4) ) {
          if ( $row['bestellende'] < $mysqljetzt ) {
            $actions[] = fc_action(
              [
                'text'      => '>>> Bestellschein fertigmachen >>>',
                'title'     => 'Bestellschein für Lieferanten fertigmachen',
                'confirm'   => 'Jetzt Bestellschein für Lieferanten fertigmachen?',
              ],
              [
                'action'    => 'changeState',
                'change_id' => $bestell_id,
                'change_to' => STATUS_LIEFERANT,
              ]
            );
            $actions[] = fc_action(
              [
                'text'      => 'löschen',
                'title'     => 'Bestellung löschen',
                'confirm'   => 'Bestellung wirklich löschen?',
                'class'     => 'drop',
              ],
              [
                'action'    => 'delete',
                'delete_id' => $bestell_id,
              ]
            );
          } else {
            $actions[] = "
              <div class='alert qquad'>Bestellzeit läuft noch!</div>
            ";
          }
          $actions[] = fc_link(
            'bestellen',
            [
              'text'       => 'zum Bestellen...',
              'bestell_id' => $bestell_id,
              'class'      => 'browse',
            ]
          );
          $actions[] = fc_link(
            'edit_bestellung',
            [
              'text'       => 'Stammdaten ändern...',
              'bestell_id' => $bestell_id,
            ]
          );
        }
        break;
  
      case STATUS_LIEFERANT:
        $views[] = fc_link(
          'bestellschein',
          [
            'text'         => 'Bestellschein',
            'bestell_id'   => $bestell_id,
            'class'        => 'href',
          ]
        );
        if( $login_dienst > 0 )
          $views[] = fc_link( 
            'verteilliste',
            [
              'bestell_id' => $bestell_id,
              'class'      => 'href',
            ]
          );
        if( hat_dienst(4) ) {
          $actions[] = fc_link( 
            'edit_bestellung',
            [
              'text'       => 'Stammdaten ändern...',
              'bestell_id' => $bestell_id,
            ]
          );
          $actions[] = fc_action(
            [
              'text'      => '<<< Nachbestellen lassen <<<',
              'title'     => 'Bestellung nochmal zum Bestellen freigeben?',
            ],
            [
              'action'    => 'changeState',
              'change_id' => $bestell_id,
              'change_to' => STATUS_BESTELLEN,
            ]
          );
        }
        if( hat_dienst(1,3,4) )
          $actions[] = fc_action(
            [
              'text'      => '>>> Lieferschein erstellen >>>',
              'title'     => 'Nach Lieferung Lieferschein abgleichen',
              'confirm'   => 'Bestellung wurde geliefert, Lieferschein abgleichen?',
            ],
            [
              'action'    => 'changeState',
              'change_id' => $bestell_id,
              'change_to' => STATUS_VERTEILT,
            ]
          );
        if( hat_dienst(4) )
          $actions[] = fc_action(
            [
              'text'      => 'löschen',
              'title'     => 'Bestellung löschen',
              'confirm'   => 'Bestellung wirklich löschen?',
              'class'     => 'drop',
            ],
            [
              'action'    => 'delete',
              'delete_id' => $bestell_id,
            ]
          );
        break;
  
      case STATUS_VERTEILT:
        $views[] = fc_link(
          'lieferschein', 
          [
            'text'       => 'Lieferschein',
            'bestell_id' => $bestell_id,
            'class'      => 'href',
          ]
        );
        if( $login_dienst > 0 ) {
          $views[] = fc_link(
            'verteilliste',
            [
              'bestell_id'=> $bestell_id,
              'class'     => 'href',
            ]
          );
          $views[] = fc_link(
            'verteilliste',
            [
              'text'      => 'Produktverteilung (Druck)',
              'bestell_id'=> $bestell_id,
              'ro'        => 1,
              'class'     => 'href',
            ]
          );
        }
        if( hat_dienst(4) ) {
          $actions[] = fc_link(
            'edit_bestellung',
            [
              'text'       => 'Stammdaten ändern...',
              'bestell_id' => $bestell_id,
            ]
          );
          if( $abrechnung_set_count > 1 ) {
            $combs[] = fc_action(
              [
                'text'    => 'Trennen',
                'confirm' => 'Bestellung von Gesamtabrechnung abtrennen?',
                'update'  => 1,

              ],
              [
                'action'  => 'split',
                'message' => $bestell_id,
              ]
            );
          }
          if( $n == $abrechnung_set_count ) {
            $combs[] = "<div class='bigskip'>&nbsp;</div>";
            if( $abrechnung_set_count > 1 ) {
              $combs[] = fc_link(
                'gesamtlieferschein',
                [
                  'text'          => 'Gesamt-Lieferschein',
                  'abrechnung_id' => $abrechnung_id,
                  'class'         => 'href',
                ]
              );
            }
            $combs[] = fc_link(
              'abrechnung',
              [
                'text'          => 'Abrechnung beginnen...',
                'abrechnung_id' => $abrechnung_id,
                'class'         => 'href',
              ]
            );
            $combs[] = "<input type='checkbox' onclick='kombinieren($abrechnung_id);'> Kombinieren";
          }
        }
        break;
  
      case STATUS_ABGERECHNET:
        $views[] = fc_link(
          'lieferschein',
          [
            'text'       => 'Lieferschein',
            'bestell_id' => $bestell_id,
            'class'      => 'href',
          ]
        );
        if( $login_dienst > 0 )
          $views[] = fc_link(
            'verteilliste',
            [
              'class'      => 'href',
              'bestell_id' => $bestell_id,
            ]
          );
  
        $views[] = fc_link(
          'abrechnung',
          [
            'text'          => 'Abrechnung',
            'abrechnung_id' => $abrechnung_id,
            'bestell_id'    => $bestell_id,
            'class'         => 'href',
           ]
        );
  
        if( $n == $abrechnung_set_count ) {
          if( $abrechnung_set_count > 1 ) {
            $combs[] = fc_link(
              'gesamtlieferschein',
              [
                'text'          => 'Gesamt-Lieferschein',
                'abrechnung_id' => $abrechnung_id,
                'class'         => 'href',
              ]
            );
            $combs[] = fc_link(
              'abrechnung',
              [
                'text'          => 'Gesamt-Abrechnung',
                'abrechnung_id' => $abrechnung_id,
                'class'         => 'href',
              ]
            );
          }
        }
  
        break;
  
      case STATUS_ARCHIVIERT:
      default:
        break;
    }
  
    open_tr($row_css_class, "id='row$bestell_id'" );
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
        $tdcls = 
          ( ( $n == 1 ) ? '' : 'notop ' ) .
          ( ( $n == $abrechnung_set_count ) ? '' : ' nobottom' );
        open_td( $tdcls );
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

medskip();

open_div( 'center' );
  open_tag("button", "button", "id='loadMoreButton'", "⇩⇩⇩ Mehr laden... ⇩⇩⇩");
close_div();

?>

  <script>
    document.getElementById('loadMoreButton').addEventListener(
      'click',
      // move the limit roughly 1y back in time (-31536000s)
      (event) => { loadMore('since', <?php echo $since ?>, -31536000); }
    );

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
  </script>
