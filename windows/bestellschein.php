<?php
//
// bestellschein.php: detailanzeige bestellschein / lieferschein, abhaengig vom status der bestellung
//

 


error_reporting(E_ALL);
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

assert( $angemeldet ) or exit();

need_http_var( 'bestell_id', 'U', true );

$bestellung = sql_bestellung( $bestell_id );
$status = $bestellung['rechnungsstatus'];
$lieferdatum_trad = "{$wochentage[ $bestellung['lieferdatum_dayofweek'] ]}, {$bestellung['lieferdatum_trad']}";


$lieferant = sql_lieferant( $bestellung['lieferanten_id'] );

get_http_var( 'lieferant_name', 'H', $lieferant['name'] );
get_http_var( 'lieferant_strasse', 'H', $lieferant['strasse'] );
get_http_var( 'lieferant_ort', 'H', $lieferant['ort'] );
get_http_var( 'lieferant_fax', 'H', $lieferant['fax'] );
get_http_var( 'lieferant_email', 'H', $lieferant['mail'] );

get_http_var( 'lieferant_anrede', 'H', $lieferant['anrede'] );
if( ! ( $lieferant_anrede = trim( $lieferant_anrede ) ) )
  $lieferant_anrede = 'Sehr geehrte Damen und Herren,';
get_http_var( 'lieferant_grussformel', 'H', $lieferant['grussformel'] );
if( ! ( $lieferant_grussformel = trim( $lieferant_grussformel ) ) )
  $lieferant_grussformel = 'Mit freundlichen Grüßen,';

get_http_var( 'fc_name', 'H', $lieferant['fc_name'] );
if( ! ( $fc_name = trim( $fc_name ) ) )
  $fc_name = $foodcoop_name;
get_http_var( 'fc_strasse', 'H', $lieferant['fc_strasse'] );
get_http_var( 'fc_ort', 'H', $lieferant['fc_ort'] );
get_http_var( 'fc_kundennummer', 'H', $lieferant['kundennummer'] );

get_http_var( 'besteller_name', 'H', $coopie_name );

get_http_var( 'action', 'w', '' );
$readonly and $action = '';

switch( $action ) {

  case 'insert':
    nur_fuer_dienst(1,3,4);
    need( $status < STATUS_ABGERECHNET, "Änderung nicht möglich: Bestellung ist bereits abgerechnet!" );
    need_http_var( 'produkt_id', 'u' );
    // need_http_var( 'menge', 'f' );
    if( $bestell_id ) {
      if( sql_insert_bestellvorschlag( $produkt_id, $bestell_id ) ) {
        if( $status < STATUS_VERTEILT ) {
          $msg = 'Produkt wurde aufgenommen, steht aber noch unter \"nicht bestellt\" (da Menge 0); bitte nach Lieferung die Menge nachtragen!'
                 . ' (das geht leider erst nach \"Lieferschein fertigmachen\"!)';
        } else {
          $msg = 'Produkt wurde aufgenommen, steht aber noch unter \"nicht geliefert\" (da Liefermenge 0): bitte Menge nachtragen!';
        }
        $js_on_exit[] = "alert( '$msg' );";
      }
    }
    break;

  case 'update':
    nur_fuer_dienst(4);
    need( $status == STATUS_VERTEILT );
    foreach( sql_bestellung_produkte($bestell_id ) as $produkt ) {
      $produkt_id = $produkt['produkt_id'];
      if( get_http_var( 'liefermenge'.$produkt_id, 'f' ) ) {
        $lv_faktor = $produkt['lv_faktor'];
        $liefermenge = $produkt['liefermenge'] / $lv_faktor;
        if( abs( ${"liefermenge$produkt_id"} - $liefermenge ) > 0.001 ) {
          $liefermenge = ${"liefermenge$produkt_id"};
          sql_change_liefermenge( $bestell_id, $produkt_id, $liefermenge * $lv_faktor );
        }
      }
    }
    // Als nicht geliefert markierte Produkte löschen
    if( get_http_var( 'nichtGeliefert[]','u') ) {
      foreach( $nichtGeliefert as $p_id ) {
        nichtGeliefert( $bestell_id, $p_id );
      }
    }
    break;

  default:
    break;
}

get_http_var( 'gruppen_id', 'u', 0, true );

if( $gruppen_id and ! in_array( $gruppen_id, $specialgroups ) ) {
  if( $gruppen_id != $login_gruppen_id )
    nur_fuer_dienst(4);
  $gruppen_name = sql_gruppenname($gruppen_id);
}


$default_spalten = PR_COL_NAME | PR_COL_LPREIS | PR_COL_ENDPREIS | PR_COL_MWST | PR_COL_PFAND | PR_COL_AUFSCHLAG;
switch( $status ){    // anzeigedetails abhaengig vom Status auswaehlen
  case STATUS_BESTELLEN:
    $editable = FALSE;
    if( $gruppen_id ) {
      $default_spalten |= ( PR_COL_BESTELLMENGE | PR_COL_VSUMME );
    } else {
      $default_spalten
        |= ( PR_COL_BESTELLMENGE | PR_COL_BESTELLGEBINDE | PR_COL_NETTOSUMME | PR_ROWS_NICHTGEFUELLT );
    }
    $title="Bestellschein (vorläufig)";
    break;
  case STATUS_LIEFERANT:
    $editable = FALSE;
    if( $gruppen_id ) {
      $default_spalten |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_VSUMME );
    } else {
      $default_spalten
        |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_LIEFERGEBINDE | PR_COL_NETTOSUMME | PR_ROWS_NICHTGEFUELLT );
    }
    $title="Bestellschein";
    // $selectButtons = array("zeigen" => "bestellschein", "pdf" => "bestellt_faxansicht" );
    break;
  case STATUS_VERTEILT:
  case STATUS_ABGERECHNET:
    if( $gruppen_id ) {
      $editable = FALSE;
      $default_spalten |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME );
    } else {
      // ggf. liefermengen aendern lassen:
      $editable = (!$readonly) && ( hat_dienst(1,3,4) && ( $status == STATUS_VERTEILT ) );
      $default_spalten
        |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_LIEFERGEBINDE | PR_COL_NETTOSUMME | PR_ROWS_NICHTGEFUELLT );
    }
    $title="Lieferschein";
    break;
  default: 
    div_msg( 'warn', 'Keine Detailanzeige verfügbar' );
    return;
}
if( hat_dienst(0) ) {
  $default_spalten |= PR_COL_ENDSUMME;
}

get_http_var( 'spalten', 'w', $default_spalten, true );



$abrechnung_id = $bestellung['abrechnung_id'];
$bestell_id_set = sql_abrechnung_set( $abrechnung_id );
if( count( $bestell_id_set ) > 1 ) {
  abrechnung_overview( $abrechnung_id, $bestell_id );
}


open_table( 'layout hfill' );
  open_td( 'left' );
    bestellung_overview( $bestell_id, $gruppen_id );
  open_td( 'right qquad floatright' );
    open_table( 'menu', "id='option_menu_table'" );
      open_th( '', "colspan='2'", 'Anzeigeoptionen' );
    close_table();
close_table();



echo "<h1>$title</h1>";

open_option_menu_row();
  open_td( '', '', 'Gruppenansicht:' );
  open_td();
    open_select( 'gruppen_id', 'autoreload' );
      $keys = array( 'bestell_id' => $bestell_id );
      if( ! hat_dienst(4) )
        $keys['where'] = "bestellgruppen.id in ( $login_gruppen_id, ".sql_muell_id().", ".sql_basar_id()." )";
      echo optionen_gruppen( $gruppen_id, $keys, "Alle (Gesamtbestellung)" );
    close_select();
close_option_menu_row();

medskip();
bestellschein_view(
  $bestell_id,
  $editable,   // Liefermengen edieren zulassen?
  $editable,   // Preise edieren zulassen?
  $spalten,    // welche Tabellenspalten anzeigen
  $gruppen_id, // Gruppenansicht (0: alle)
  true,        // angezeigte Spalten auswaehlen lassen
  true         // Option: Anzeige nichtgelieferte zulassen
);

medskip();
switch( $status ) {
  case STATUS_LIEFERANT:
  case STATUS_VERTEILT:
    if( ! $readonly and ! $gruppen_id and hat_dienst(1,3,4) ) {
      open_fieldset( 'small_form', '', 'Zusätzliches Produkt eintragen', 'off' );
        open_form( '', 'action=insert' );
          open_div( 'kommentar' )
            ?> Hier koennt ihr ein weiteres geliefertes Produkt in den Lieferschein eintragen: <?php
            open_ul();
              open_li( '', '', 'das Produkt muss vorher in der Produkt-Datenbank erfasst sein' );
              open_li( '', '', 'die <em>Liefermenge</em> ist danach noch 0 und muss hinterher gesetzt werden!' );
            close_ul();
          close_div();
          select_products_not_in_list($bestell_id);
          // mengeneingabe ist hier sinnlos, da wir keine Masseinheit anbieten koennen
          // (die haengt von der auswahl in obiger Produktliste ab!)
          // ?> <label>Menge:</label> <?php
          // echo int_view( 1, 'menge' );
          submission_button();
        close_form();
      close_fieldset();
    }
    break;
  default:
    break;
}

if( hat_dienst( 4 ) && ( $status > STATUS_BESTELLEN ) && ( $status < STATUS_ABGERECHNET ) ) {
  open_option_menu_row();
    open_td( '', "colspan='2'", fc_link( 'bestellfax', "class=qquad href,bestell_id=$bestell_id,text=zur Faxansicht..." ) );
  close_option_menu_row();
}

?>
