<?PHP

?> <h1>Produktdatenbank ....</h1> <?

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
          open_td( '', '', fc_link( 'katalog', "class=bigbutton,text=Katalogsuche,lieferanten_id=$lieferanten_id" ) );
      }
      open_tr();
        open_td( '', '', fc_link( 'self', "class=bigbutton,text=Seite aktualisieren" ) );
      open_tr();
        open_td( '', '', fc_link( 'index', "class=bigbutton" ) );
      open_tr();
        open_td();
          option_checkbox( 'options', OPTION_PREISKONSISTENZTEST, 'Preiskonsistenztest'
                         , 'Soll die Preishistorie aller Einträge auf Inkonsistenzen geprüft werden?' );
      open_tr();
        open_td();
          option_checkbox( 'options', OPTION_KATALOGABGLEICH, 'Abgleich mit Lieferantenkatalog'
                         , 'Sollen alle Einträge mit dem Lieferantenkatalog verglichen werden?' );
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
  open_form( '', 'window=insert_bestellung', "lieferanten_id=$lieferanten_id" );

open_table('list');
    open_th( '', "colspan='10'", "<h3>Produktübersicht von $lieferant_name </h3>" );
  open_tr();
    open_th();
    open_th( '', "title='generische Produktbezeichnung'", 'Bezeichnung' );
    open_th( '', '', 'Produktgruppe' );
    open_th( '', "title='aktuelle Details zum Produkt'", 'Notiz' );
    open_th( '', '', 'Gebindegroesse' );
    open_th( '', "colspan='2' title='Lieferanten-Preis (ohne Pfand, ohne MWSt)'", 'L-Nettopreis' );
    open_th( '', "colspan='2' title='Verbraucher-Preis mit Pfand und MWSt'", 'V-Endpreis' );
    open_th( '', '', 'Aktionen' );

  foreach( sql_lieferant_produkt_ids($lieferanten_id) as $id ) {
    $produkt = sql_produkt_details( $id, 0, $mysqljetzt );
    $references = references_produkt( $id );

    open_tr( 'groupofrows_top' );
      open_td( 'top', '', $produkt['zeitstart'] ? "<input type='checkbox' name='bestellliste[]' value='$id' $input_event_handlers>" : '-' );
      open_td( 'top bold', '', $produkt['name'] );
      open_td( 'top', '', $produkt['produktgruppen_name'] );
      if( $produkt['zeitstart'] ) {
        open_td( 'top', '', $produkt['notiz'] );
        open_td( 'number', '', sprintf( "%d * (%s %s)", $produkt['gebindegroesse'], $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ) );
        open_td( 'mult', '', price_view( $produkt['nettolieferpreis'] ) );
        open_td( 'unit', '', "/ {$produkt['preiseinheit']}" );
        open_td( 'mult', '', price_view( $produkt['endpreis'] ) );
        open_td( 'unit', '', "/ {$produkt['kan_verteilmult']} {$produkt['kan_verteileinheit']}" );
      } else {
        open_td( 'center', "colspan='6'", '(kein aktueller Preiseintrag)' );
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
      open_td();
      open_td( '', "colspan='9'" );
        if( $options & OPTION_PREISKONSISTENZTEST )
          produktpreise_konsistenztest( $id );
        if( $options & OPTION_KATALOGABGLEICH )
          katalogabgleich( $id );
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
