<?PHP

?> <h1>Produktdatenbank ....</h1> <?php

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:produkte' );

get_http_var( 'lieferanten_id', 'u', 0, true );
define( 'OPTION_KATALOGABGLEICH', 1 );
define( 'OPTION_PREISKONSISTENZTEST', 2 );
get_http_var( 'options', 'u', OPTION_PREISKONSISTENZTEST, true );

$editable = ( hat_dienst(4) and ! $readonly );

//
// tabelle fuer hauptmenue und auswahl lieferanten:
//
open_table('layout hfill' );
  open_td('left');
    open_table('menu');
      if( $lieferanten_id ) {
        if( $editable ) {
           open_tr();
             open_td( '', '', fc_link( 'edit_produkt'
                     , "class=bigbutton,lieferanten_id=$lieferanten_id,title=Neues Produkt eintragen,text=Neues Produkt" ) );
        }
        open_tr();
          open_td( '', '', fc_link( 'katalog', "class=bigbutton,text=Lieferantenkatalog,lieferanten_id=$lieferanten_id" ) );
      }
      open_tr();
        open_td( '', '', fc_link( 'catalogue_acronyms', "class=bigbutton,text=Katalog-Akronyme") );
      open_tr();
        open_td( '', '', fc_link( 'self', "class=bigbutton,text=Seite aktualisieren" ) );
      open_tr();
        open_td( '', '', fc_link( 'index', "class=bigbutton" ) );
      // braucht nicht mehr optional zu sein - preise sollten immer konsistent sein!
      // open_tr();
      //   open_td();
      //     option_checkbox( 'options', OPTION_PREISKONSISTENZTEST, 'Preiskonsistenztest'
      //                   , 'Soll die Preishistorie aller Einträge auf Inkonsistenzen geprüft werden?' );
      if( $lieferanten_id && sql_lieferant_katalogeintraege( $lieferanten_id ) ) {
        open_tr();
          open_td();
            option_checkbox( 'options', OPTION_KATALOGABGLEICH, 'Abgleich mit Lieferantenkatalog'
                           , 'Sollen alle Einträge mit dem Lieferantenkatalog verglichen werden?' );
      } else {
        $options &= ~OPTION_KATALOGABGLEICH;
      }
    close_table();

  open_td('floatright');
    auswahl_lieferant( $lieferanten_id );
close_table();

// ab hier muss ein Lieferant ausgewaehlt sein, sonst Ende:
//
if( ! $lieferanten_id )
  return;

bigskip();

/////////////////////////////
//
// aktionen verarbeiten:
//
/////////////////////////////

get_http_var('action','w','');
$editable or $action = '';
if( $action == 'delete' ) {
  need_http_var('produkt_id','u');
  sql_delete_produkt( $produkt_id );
}


/////////////////////////////
//
// Produkttabelle anzeigen:
//
/////////////////////////////

$lieferant_name = sql_lieferant_name($lieferanten_id);

if( $editable )
  open_form( 'window=insert_bestellung', "lieferanten_id=$lieferanten_id" );

$produkte = sql_produkte( array( 'lieferanten_id' => $lieferanten_id ) );

$produktgruppen_zahl = array();
foreach( $produkte as $produkt ) {
  $id = $produkt['produktgruppen_id'];
  $produktgruppen_zahl[$id] = adefault( $produktgruppen_zahl, $id, 0 ) + 1;
}
$produktgruppen_id_alt = -1;

$cols = ( $editable ? 9 : 8 );
open_table('list hfill');
  open_tr();
    open_th( '', "colspan='$cols'", "<h3>Produktübersicht von $lieferant_name </h3>" );
  open_tr();
    open_th( '', '', 'Produktgruppe' );
    open_th( '', "title='generische Produktbezeichnung'", 'Bezeichnung' );
    if( $editable )
      open_th( '', "title='in Bestellvorlage aufnehmen?'", 'aufnehmen?' );
    // open_th( '', "title='aktuelle Details zum Produkt'", 'Notiz' );
    open_th( '', '', 'Gebindegroesse' );
    open_th( '', "colspan='2' title='Lieferanten-Preis (ohne Pfand, ohne MWSt)'", 'L-Nettopreis' );
    open_th( '', "colspan='2' title='Verbraucher-Preis mit Pfand und MWSt'", 'V-Preis' );
    open_th( '', '', 'Aktionen' );

  foreach( $produkte as $p ) {
    $id = $p['produkt_id'];
    $preis_id = sql_aktueller_produktpreis_id( $id );
    $produkt = sql_produkt( array( 'produkt_id' => $id, 'preis_id' => $preis_id ) );
    $references = references_produkt( $id );
    $vormerkungen_menge = sql_bestellzuordnung_menge( array( 'art' => BESTELLZUORDNUNG_ART_VORMERKUNGEN, 'produkt_id' => $id ) );
    
    $katalogeintrag = katalogsuche( $p );

    open_tr( 'groupofrows_top' );
      $produktgruppen_id = $produkt['produktgruppen_id'];
      if( $produktgruppen_id != $produktgruppen_id_alt ) {
        $rows = $produktgruppen_zahl[$produktgruppen_id] * 2;
        open_td( 'top', "rowspan='$rows'", $produkt['produktgruppen_name'] );
        $produktgruppen_id_alt = $produktgruppen_id;
      }
      open_td( 'top' );
        open_span( 'bold', '', $produkt['name'] );
        open_span( 'small floatright', '', catalogue_product_details( $katalogeintrag ));
        open_div( 'small', '', $produkt['notiz'] );
      if( $editable ) {
        if( $preis_id ) {
          if( $vormerkungen_menge > 0 ) {
            $title = sprintf(
              "Vormerkungen fuer %s %s vorhanden"
            , $vormerkungen_menge * $produkt['kan_verteilmult']
            , $produkt['kan_verteileinheit']
            );
            open_td( 'top center alert', "title='$title'" );
            $checked = 'checked';
          } else {
            if( $produkt['dauerbrenner'] ) {
              open_td( 'top center highlight', "title='Produkt ist als Dauerbrenner markiert (siehe Stammdaten)'" );
              $checked = 'checked';
            } else {
              open_td( 'top center' );
              $checked = '';
            }
          }
          echo "<input type='checkbox' name='bestellliste[]' value='$id' $input_event_handlers $checked>";
        } else {
          open_td( 'top center', '', '-' );
        }
      }
      if( $preis_id ) {
        open_td( 'center oneline', '', gebindegroesse_view( $produkt ) );
        open_td( 'mult', '', price_view( $produkt['nettolieferpreis'] ) );
        open_td( 'unit', '', "/ {$produkt['liefereinheit']}" );
        open_td( 'mult', '', price_view( $produkt['vpreis'] ) );
        open_td( 'unit', '', "/ {$produkt['kan_verteilmult']} {$produkt['kan_verteileinheit']}" );
      } else {
        open_td( 'center', "colspan='5'", '(kein aktueller Preiseintrag)' );
      }
      open_td( 'top oneline', '' );
        if( $editable )
          echo fc_link( 'edit_produkt', "produkt_id=$id" );
        echo fc_link( 'produktpreise', "produkt_id=$id,text=" );
        if( $editable and ( $references == 0 ) ) {
          echo fc_action( array( 'class' => 'drop', 'title' => 'Produkt L&ouml;schen', 'confirm' => 'Soll das Produkt wirklich GEL&Ouml;SCHT werden?' )
                        , array( 'action' => 'delete', 'produkt_id' => $id ) );
        }
    open_tr( 'groupofrows_bottom' );
      // open_td();
      open_td( '', "colspan='$cols'" );
        if( $options & OPTION_PREISKONSISTENZTEST )
          produktpreise_konsistenztest( $id );
        if( $options & OPTION_KATALOGABGLEICH )
          katalogabgleich( $id, 1 );
  }

  if( $editable ) {
    open_tr();
      open_th( '', "colspan='10'" );
        check_all_button();
        submission_button( 'Neue Bestellung' );
  }

close_table();

if( $editable )
  close_form();

?>
