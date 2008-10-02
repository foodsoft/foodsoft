<?php
// artikelsuche.php
//
// Timo, 2007, 2008
//
// verwaltet und sucht im lieferantenkatalog (funktioniert bislang nur mit Terra)

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );

setWindowSubtitle( "Artikelsuche im Lieferanten-Katalog" );
setWikiHelpTopic( "foodsoft:katalogsuche" );

need_http_var( 'lieferanten_id', 'U', true );

$filter = '';

get_http_var( 'bnummer', 'w', '' ) or $bnummer = '';
$bnummer and $filter .= " AND bestellnummer='$bnummer'";

get_http_var( 'anummer', 'w', '' ) or $anummer = '';
$anummer and $filter .= " AND artikelnummer='$anummer'";

get_http_var( 'name', 'H', '' ) or $name = '';
$name and $filter .= " AND name like '%".mysql_real_escape_string($name)."%' ";

get_http_var( 'minpreis', 'f', 0 ) or $minpreis = 0;
( $minpreis > 0 ) and $filter .= " AND preis >= $minpreis";

get_http_var( 'maxpreis', 'f', 0 ) or $maxpreis = 0;
( $maxpreis > 0 ) and $filter .= " AND preis <= $maxpreis";

get_http_var( 'katalogtyp', 'w', '' ) or $katalogtyp = '';
$katalogtyp and $filter .= " AND katalogtyp = '$katalogtyp'";

get_http_var( 'limit', 'u', 99 ) or $limit = 99;

// produkt_id: wenn gesetzt, erlaube update der artikelnummer!
get_http_var( 'produkt_id', 'u', 0, true );
if( $produkt_id ) {
  $produkt = sql_produkt_details( $produkt_id );
  $produktname = $produkt['name'];
}

?>

<h1>Lieferantenkatalog - bisher nur fuer Terra! </h1>

<h3>Lieferant: <? echo lieferant_name( $lieferanten_id ); ?> --- Katalogeintraege: <? echo sql_anzahl_katalogeintraege( $lieferanten_id ); ?></h3>

<?

get_http_var('action','w','');
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
  open_form( 'small_form', "enctype='multipart/form-data'", 'index.php?window=terrakatalog_upload' );
    open_fieldset( 'small_form', '', 'Kataloge' );
      open_table( 'list' );
        open_th( '', '', 'Katalog' );
        open_th( '', '', 'Typ' );
        open_th( '', '', 'Aktionen' );

        while( $row = mysql_fetch_array( $kataloge ) ) {
          open_tr();
            open_td( '', '', $row['katalogdatum'] );
            open_td( '', '', $row['katalogtyp'] );
            open_td( '', '', fc_action( array(
                  'img' => 'img/b_drop.png', 'title' => 'Katalog l&ouml;schen'
                , 'action' => 'delete', 'confirm' => 'Soll der Katalog wirklich GEL&Ouml;SCHT werden?'
                , 'katalogdatum' => $row['katalogdatum'], 'katalogtyp' => $row['katalogtyp'] ) )
            );
        }
      close_table();

      ?> <h3> Neuen Katalog einlesen: </h3> <?
      open_table();
        open_td( '', '', "Datei (Format: .xls): <input type='file' name='terrakatalog'>" );
        open_td( '', '', " &nbsp; gueltig ab (Format: JJJJkwWW): <input type='text' name='terrakw' size='8'>" );
        open_td( '', '', " <input type='submit' value='start'>" );
      close_table();
    close_fieldset();
  close_form();
}

open_fieldset( 'small_form', '', $produkt_id ?  "Katalogsuche nach Artikelnummer fuer <i>$produktname</i>" : "Artikelsuche im Katalog" );

  open_form( 'small_form' );
    open_table();
        open_td( '', '', '<label>Bestellnummer:</label>' );
        open_td();
          ?>
            <input type='text' name='bnummer' value='<? echo $bnummer; ?>' size='10'>
            &nbsp;
            <label>Artikelnummer:</label>
            <input type='text' name='anummer' value='<? echo $anummer; ?>' size='10'>
            &nbsp;
            <label>Katalog:</label>
            <select name='katalogtyp' size='1'>
              <? echo optionen( array( '', 'OG', 'Fr', 'Tr', 'drog' ), $katalogtyp ); ?>
            </select>
          <?
      open_tr();
        open_td( '', '', '<label>Bezeichnung:</label>' );
        open_td( '', '', " &nbsp; <input type='text' name='name' value='$name' size='60'> &nbsp; (Jokerzeichen ist % (Prozent))" );

      open_tr();
        open_td( '', '', 'Preis (netto):' );
        open_td();
          ?> &nbsp; von: <input type='text' name='minpreis' value='<? printf( "%.2lf", $minpreis ); ?>' size='10'>
              &nbsp; bis: <input type='text' name='maxpreis' value='<? printf( "%.2lf", $maxpreis ); ?>' size='10'> <?
      open_tr();
        open_td( '', '', '<label>Limit:</label>' );
        open_td();
          ?> maximal <input type='text' name='limit' value='<? printf( "%u", $limit ); ?>' size='8'> Treffer anzeigen
            <input style='margin-left:4em;' type='submit' value='Suche starten'> <?
    close_table();
  close_form();

  if( $filter != '' ) {
  
    $result = doSql( "
      SELECT * FROM lieferantenkatalog WHERE lieferanten_id = $lieferanten_id
      $filter
      limit $limit
    " );

    if( $produkt_id ) {
      open_form( '', "name='anummer_setzen'", fc_url( 'produktpreise', "produkt_id=$produkt_id", '', 'action' ) );
      ?>
        <input type='hidden' name='action' value='artikelnummer_setzen'>
        <input type='hidden' name='button_id' value=''>
        <div style='font-weight:bold;'>Zur Übernahme in die Produktdatenbank bitte auf Artikelnummer klicken!</div>
      <?
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
              echo fc_button( 'produktdetails', "text=$anummer,class=submit,form=anummer_setzen,button_id=$anummer" );
            } else {
              echo $anummer;
            }
          open_td( 'number', '', $row['bestellnummer'] );
          open_td( '', '', $row['name'] );
          open_td( '', '', $row['gebinde'] );
          open_td( 'unit', '', $row['liefereinheit'] );
          open_td( '', '', $row['herkunft'] );
          open_td( '', '', $row['verband'] );
          open_td( '', '', sprintf( "%.2lf", $netto ) );
          open_td( 'mult', '', sprintf( "%.2lf", $mwst ) );
          open_td( 'mult', '', sprintf( "%.2lf", $brutto ) );
          open_td( '', '',  "{$row['katalogtyp']} / {$row['katalogdatum']}" );
      }
    close_table();
    if( $produkt_id )
      close_form();
  }

close_fieldset();

?>
