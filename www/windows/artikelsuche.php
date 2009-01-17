<?php
// artikelsuche.php
//
// Timo, 2007, 2008
//
// verwaltet und sucht im lieferantenkatalog (funktioniert bislang nur mit Terra und Bode)

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and hat_dienst(4) );

setWindowSubtitle( "Artikelsuche im Lieferanten-Katalog" );
setWikiHelpTopic( "foodsoft:katalogsuche" );

need_http_var( 'lieferanten_id', 'U', true );
$lieferant_name = sql_lieferant_name( $lieferanten_id );

need( preg_match( '&^Terra&', $lieferant_name ) or preg_match( '&^Bode&', $lieferant_name ) 
    , "Lieferanten-Katalog: bislang nur fuer Terra und Bode!" );

get_http_var('action','w','');

$filter = '';

get_http_var( 'bnummer', 'w', '', 'POST' ) or $bnummer = '';
$bnummer and $filter .= " AND bestellnummer='$bnummer'";

get_http_var( 'anummer', 'w', '', 'POST' ) or $anummer = '';
$anummer and $filter .= " AND artikelnummer='$anummer'";

get_http_var( 'name', 'H', '', 'POST' ) or $name = '';
$name and $filter .= " AND name like '%".mysql_real_escape_string($name)."%' ";

get_http_var( 'minpreis', 'f', 0, 'POST' ) or $minpreis = 0;
( $minpreis > 0 ) and $filter .= " AND preis >= $minpreis";

get_http_var( 'maxpreis', 'f', 0, 'POST' ) or $maxpreis = 0;
( $maxpreis > 0 ) and $filter .= " AND preis <= $maxpreis";

get_http_var( 'katalogtyp', 'w', '', 'POST' ) or $katalogtyp = '';
$katalogtyp and $filter .= " AND katalogtyp = '$katalogtyp'";

get_http_var( 'limit', 'u', 99, 'POST' ) or $limit = 99;

if( $action != 'search' )
  $filter = '';

// produkt_id: wenn gesetzt, erlaube update der artikelnummer!
get_http_var( 'produkt_id', 'u', 0, true );
if( $produkt_id ) {
  $produkt = sql_produkt_details( $produkt_id );
  $produktname = $produkt['name'];
}

?>
<h1>Lieferantenkatalog - bislang nur fuer Terra und Bode! </h1>

<h3>Lieferant: <? echo sql_lieferant_name( $lieferanten_id ); ?> --- Katalogeintraege: <? echo sql_anzahl_katalogeintraege( $lieferanten_id ); ?></h3>
<?

$editable or $action = '';
if( $action == 'delete' ) {
  need_http_var( 'katalogdatum', 'w' );
  need_http_var( 'katalogtyp', 'w' );
  doSql( "DELETE FROM lieferantenkatalog WHERE katalogdatum = '$katalogdatum' and katalogtyp='$katalogtyp'" );
}

if( $editable and ( ! $produkt_id ) ) {
  $kataloge = doSql( "
    SELECT katalogdatum, katalogtyp
    FROM lieferantenkatalog
    WHERE lieferanten_id=$lieferanten_id
    GROUP BY katalogdatum, katalogtyp
    ORDER BY katalogtyp, katalogdatum
  " );
  open_form( array( 'window' => 'katalog_upload', 'attr' => "enctype='multipart/form-data'", 'action' => 'upload', 'lieferanten_id' => $lieferanten_id ) );
    open_fieldset( 'small_form', '', 'Kataloge' );
      open_table( 'list' );
        open_th( '', '', 'Katalog' );
        open_th( '', '', 'Typ' );
        open_th( '', '', 'Aktionen' );

        while( $row = mysql_fetch_array( $kataloge ) ) {
          open_tr();
            open_td( '', '', $row['katalogdatum'] );
            open_td( '', '', $row['katalogtyp'] );
            open_td( '', '', fc_action( array( 'class' => 'drop', 'title' => 'Katalog l&ouml;schen'
                                             , 'confirm' => 'Soll der Katalog wirklich GEL&Ouml;SCHT werden?' )
                                      , array( 'action' => 'delete', 'katalogdatum' => $row['katalogdatum']
                                                                   , 'katalogtyp' => $row['katalogtyp'] ) ) );
        }
      close_table();

      medskip();
      ?> <h3> Neuen Katalog einlesen: </h3> <?
      open_table('layout');
        open_td( '', '', "Datei (Format: .xls): <input type='file' name='katalog'>" );
        open_td( '', '', " &nbsp; gueltig ab (Format: JJJJkwWW): <input type='text' name='katalogkw' size='8'>" );
        open_td(); submission_button( 'Einlesen' );
      close_table();
    close_fieldset();
  close_form();
}

open_fieldset( 'small_form', '', $produkt_id ?  "Katalogsuche nach Artikelnummer fuer <i>$produktname</i>" : "Artikelsuche im Katalog" );

  open_form( '', 'action=search' );
    open_table();
        open_td( '', '', '<label>Bestellnummer:</label>' );
        open_td();
          string_view( $bnummer, 10, 'bnummer' );
          open_span( 'qquad', '', "<label>Artikelnummer:</label>". string_view( $anummer, 10, 'anummer' ) );
          open_span( 'qquad' );
            ?> <label>Katalog:</label> <?
            open_select( 'katalogtyp' );
              echo optionen( array( '', 'OG', 'Fr', 'Tr', 'drog' ), $katalogtyp );
            close_select();
          close_span();
      open_tr();
        open_td( '', '', '<label>Bezeichnung:</label>' );
        open_td( '', '', string_view( $name, 60, 'name' ) ." &nbsp; (Jokerzeichen ist % (Prozent))" );

      open_tr();
        open_td( '', '', 'Preis (netto):' );
        open_td();
          ?> &nbsp; von: <? echo price_view( $minpreis, 'minpreis' );
          ?> &nbsp; bis: <? echo price_view( $maxpreis, 'maxpreis' );
      open_tr();
        open_td( 'label', '', 'Limit:' );
        open_td();
          ?> maximal <? echo int_view( $limit, 'limit' ); ?> Treffer anzeigen <?
          submission_button( 'Suche starten', true );
    close_table();
  close_form();

  if( $filter != '' ) {

    $result = doSql( "
      SELECT * FROM lieferantenkatalog WHERE lieferanten_id = $lieferanten_id
      $filter
      LIMIT $limit
    " );

    if( $produkt_id ) {
      open_form( '', 'action=artikelnummer_setzen,button_id=' );
      div_msg( 'bold', 'Zur Übernahme in die Produktdatenbank bitte auf Artikelnummer klicken!' );
    }

    ?> <h3> <? echo mysql_num_rows($result); ?> Treffer (Limit: <? echo $limit; ?>) </h3><?
    open_table( 'list' );
      open_th( '', '', 'A-Nr.' );
      open_th( '', '', 'B-Nr.' );
      open_th( '', '', 'Bezeichnung' );
      open_th( '', '', 'Gebinde' );
      open_th( '', '', 'Einheit' );
      open_th( '', '', 'Land' );
      open_th( '', '', 'Verband' );
      open_th( '', '', 'Netto' );
      open_th( '', '', 'MWSt' );
      open_th( '', '', 'Brutto' );
      open_th( '', '', 'Katalog' );

      while( $row = mysql_fetch_array( $result ) ) {
        $netto = $row['preis'];
        $mwst = $row['mwst'];
        $brutto = $netto * (1 + $mwst / 100.0 );
        open_tr();
          open_td( 'mult' );
            $anummer = $row['artikelnummer'];
            if ( $produkt_id > 0 ) {
              echo fc_action( "window=produktpreise,class=button,text=$anummer,produkt_id=$produkt_id,title=Artikelnummer auswählen"
                            , "action=artikelnummer_setzen,anummer=$anummer" );
            } else {
              echo $anummer;
            }
          open_td( 'number', '', $row['bestellnummer'] );
          open_td( '', '', $row['name'] );
          open_td( '', '', $row['gebinde'] );
          open_td( 'unit', '', $row['liefereinheit'] );
          open_td( '', '', $row['herkunft'] );
          open_td( '', '', $row['verband'] );
          open_td( '', '', price_view( $netto ) );
          open_td( 'mult', '', price_view( $mwst ) );
          open_td( 'mult', '', price_view( $brutto ) );
          open_td( '', '',  "{$row['katalogtyp']} / {$row['katalogdatum']}" );
      }
    close_table();
    if( $produkt_id )
      close_form();
  }

close_fieldset();

?>
