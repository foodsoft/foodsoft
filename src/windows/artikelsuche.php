<?php
// artikelsuche.php
//
// Timo, 2007, 2008
//
// verwaltet und sucht im lieferantenkatalog (soweit implementiert; aktuell: Terra, Bode, Rapunzel)

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and hat_dienst(4) );

setWindowSubtitle( "Artikelsuche im Lieferanten-Katalog" );
setWikiHelpTopic( "foodsoft:katalogsuche" );

need_http_var( 'lieferanten_id', 'U', true );
$lieferant = sql_lieferant( $lieferanten_id );
$lieferant_name = $lieferant['name'];
$katalogformat = $lieferant['katalogformat'];

?> <h1>Lieferantenkatalog </h1> <?php

open_div( 'oneline' );
  echo "Lieferant auswaehlen: ";
  open_select( 'lieferanten_id', 'autoreload' );
    echo optionen_lieferanten( $lieferanten_id );
  close_select();
close_div();
bigskip();

$have_mwst = false;
switch( $katalogformat ) {
  case 'terra_xls':
  case 'midgard':
  case 'bnn':
  case 'grell':
    $have_mwst = true;  // terra und BNN-kompatible (midgard, grell, ...) listen die MWSt im Katalog; andere nicht!
    break;
  case 'bode':
  case 'rapunzel':
    break;
  default:
  case 'keins':
    medskip();
    open_div( 'warn' );
      medskip();
      echo "Artikelsuche: fuer Lieferant ";
      echo fc_link( 'edit_lieferant', array( 'text' => $lieferant_name, 'class' => 'href', 'lieferanten_id' => $lieferanten_id ) );
      echo " ist das Katalogformat [$katalogformat] eingestellt; dieses wird leider (noch) nicht unterstuetzt!";
      medskip();
    close_div();
    return;
}

// $bestell_id: falls aufgerufen aus 'preiseintrag fuer bestellung waehlen', muessen wir die
// wieder zurueckgeben:
//
get_http_var( 'bestell_id', 'u', 0, true );

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

get_http_var( 'limit', 'u', 999, 'POST' ) or $limit = 999;

if( $action != 'search' )
  $filter = '';

// produkt_id: wenn gesetzt, erlaube update der artikelnummer!
get_http_var( 'produkt_id', 'u', 0, true );
if( $produkt_id ) {
  $produkt = sql_produkt( $produkt_id );
  $produktname = $produkt['name'];
}



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
    open_fieldset( 'small_form', '', "Kataloge von $lieferant_name" );

      ?><h4>erfasste Kataloge (insgesamt <?php echo sql_lieferant_katalogeintraege( $lieferanten_id ); ?> Einträge):</h4> <?php
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
      ?> <h3> Neuen Katalog einlesen: </h3> <?php
      open_table('layout');
        open_td( '', '', "Datei (Format: $katalogformat): <input type='file' name='katalog'>" );
        open_td( '', '', " &nbsp; gueltig ab (Format: JJJJkwWW): <input type='text' name='katalogkw' size='8'>" );
        open_td(); submission_button( 'Einlesen' );
      close_table();
    close_fieldset();
  close_form();
}

open_fieldset( 'small_form', '', $produkt_id ?  "Katalogsuche nach Artikelnummer fuer <i>$produktname</i>" : "Artikelsuche im Katalog" );

  if( $demoserver ) {
    open_div( 'warn', '', "
      Die Katalogsuche in den Lieferantenkatalogen ist auf diesem &ouml;ffentlichen Demo-Server leider
      nicht zul&auml;ssig!
    " );
    close_fieldset();
    return;
  }

  open_form( '', 'action=search' );
    open_table();
        open_td( '', '', '<label>Bestellnummer:</label>' );
        open_td();
          echo string_view( $bnummer, 10, 'bnummer' );
          open_span( 'qquad', '', "<label>Artikelnummer:</label>". string_view( $anummer, 10, 'anummer' ) );
          open_span( 'qquad' );
            ?> <label>Katalog:</label> <?php
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
          ?> &nbsp; von: <?php echo price_view( $minpreis, 'minpreis' );
          ?> &nbsp; bis: <?php echo price_view( $maxpreis, 'maxpreis' );
      open_tr();
        open_td( 'label', '', 'Limit:' );
        open_td();
          ?> maximal <?php echo int_view( $limit, 'limit' ); ?> Treffer anzeigen <?php
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

    ?> <h3> <?php echo mysql_num_rows($result); ?> Treffer (Limit: <?php echo $limit; ?>) </h3><?php
    open_table( 'list' );
      open_th( '', '', 'A-Nr.' );
      open_th( '', '', 'B-Nr.' );
      open_th();
        open_div('', '', 'Bezeichnung');
        open_div('small', '', 'Bemerkung');
      open_th( '', '', 'Gebinde' );
      open_th( '', '', 'Einheit' );
      open_th( '', '', 'Land' );
      open_th( '', '', 'Verband' );
      open_th( '', '', 'Hersteller' );
      open_th( '', '', 'Netto' );
      if( $have_mwst ) {
        open_th( '', '', 'MWSt' );
        open_th( '', '', 'Brutto' );
      }
      open_th( '', '', 'EAN einzeln');
      open_th( '', '', 'Katalog' );
      if( ! $produkt_id ) {
        open_th( '', '', 'Foodsoft-Datenbank' );
      }

      while( $row = mysql_fetch_array( $result ) ) {
        $netto = $row['preis'];
        open_tr();
          open_td( 'mult' );
            $anummer = $row['artikelnummer'];
            if ( $produkt_id > 0 ) {
              echo fc_action( "window=produktpreise,class=button,text=$anummer,produkt_id=$produkt_id,bestell_id=$bestell_id,title=Artikelnummer auswaehlen"
                            , "action=artikelnummer_setzen,anummer=$anummer" );
            } else {
              echo $anummer;
            }
          open_td( 'number', '', $row['bestellnummer'] );
          open_td();
            open_div('', '', $row['name']);
            if ($row['bemerkung'])
              open_div('small', '', $row['bemerkung']);
          open_td( '', '', mult_view( $row['gebinde'] ) );
          open_td( 'unit', '', $row['liefereinheit'] );
          open_td( '', '', $row['herkunft'] );
          open_td( '', '', $row['verband'] );
          open_td( '', '', $row['hersteller'] );
          open_td( '', '', price_view( $netto ) );
          if( $have_mwst ) {
            $mwst = $row['mwst'];
            $brutto = $netto * (1 + $mwst / 100.0 );
            open_td( 'mult', '', price_view( $mwst ) );
            open_td( 'mult', '', price_view( $brutto ) );
          }
          open_td( '', '', ean_view( $row['ean_einzeln']).ean_links($row['ean_einzeln']) );
          open_td( '', '',  "{$row['katalogtyp']} / {$row['katalogdatum']}" );
          if( ! $produkt_id ) {
            open_td( 'center' );
            $fc_produkte = sql_produkte( array( 'artikelnummer' => $row['artikelnummer'], 'lieferanten_id' => $lieferanten_id ) );
            if( $fc_produkte ) {
              foreach( $fc_produkte as $p ) {
                open_div( '', '', fc_link( 'produktpreise', "text=,produkt_id={$p['produkt_id']}" ) );
              }
            } else {
              if( hat_dienst(4) ) {
                echo fc_action(
                  array(
                    'window' => 'edit_produkt'
                  , 'class' => 'button'
                  , 'text' => 'Eintragen'
                  , 'title' => 'in Foodsoft Datenbank uebernehmen'
                  , 'confirm' => 'Artikel in Foodsoft Datenbank uebernehmen?'
                  )
                , array(
                    'lieferanten_id' => $lieferanten_id
                  , 'name' => $row['name']
                  , 'artikelnummer' => $row['artikelnummer']
                ) );
              } else {
                echo "-";
              }
            }
          }
      }
    close_table();
    if( $produkt_id )
      close_form();
  }

close_fieldset();

?>
