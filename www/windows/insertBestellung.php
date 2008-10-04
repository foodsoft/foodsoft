<?PHP

assert( $angemeldet ) or exit();
nur_fuer_dienst(4);
fail_if_readonly();
$msg = '';
$problems = '';

setWindowSubtitle( 'Neue Bestellvorlage anlegen' );
setWikiHelpTopic( 'foodsoft:bestellvorlage_anlegen' );

need_http_var( 'lieferanten_id', 'u', true );
get_http_var( 'bestelliste[]','u' );
if( ! isset($bestelliste) or count($bestelliste) < 1 ) {
  $problems .= "Keine Produkte ausgewÃ¤hlt!";
}

$startzeit = date("Y-m-d H:i:s");
$endzeit   = date("Y-m-d 20:00:00");
$lieferung = date("Y-m-d H:i:s");
$bestellname = "";
$done = false;

if( $problems ) {
  div_msg( 'warn', "$problems <br> <a href='javascript:if(opener) opener.focus(); self.close();'>SchlieÃŸen...</a>";
  exit();
}

get_http_var('action','w','');

if( $action == 'insert' ) {
  need_http_var("startzeit_tag",'u');
  need_http_var("startzeit_monat",'u');
  need_http_var("startzeit_jahr",'u');
  need_http_var("startzeit_stunde",'u');
  need_http_var("startzeit_minute",'u');
  need_http_var("endzeit_tag",'u');
  need_http_var("endzeit_monat",'u');
  need_http_var("endzeit_jahr",'u');
  need_http_var("endzeit_stunde",'u');
  need_http_var("endzeit_minute",'u');
  need_http_var("lieferung_tag",'u');
  need_http_var("lieferung_monat",'u');
  need_http_var("lieferung_jahr",'u');
  need_http_var("bestellname",'H');

  $startzeit = "$startzeit_jahr-$startzeit_monat-$startzeit_tag $startzeit_stunde:$startzeit_minute:00";
  $endzeit = "$endzeit_jahr-$endzeit_monat-$endzeit_tag $endzeit_stunde:$endzeit_minute:00";
  $lieferung = "$lieferung_jahr-$lieferung_monat-$lieferung_tag";

  if( $bestellname == "" )
    $problems .= "Die Bestellung muÃŸ einen Namen bekommen!<br>";

  // Wenn keine Fehler, dann einfügen...
  if ($problems == "") {
    sql_insert_bestellung($bestellname, $startzeit, $endzeit, $lieferung, $lieferanten_id );
    $gesamtbestellung_id = mysql_insert_id();

    foreach( $bestelliste as $produkt_id ) {
      // preis, gebinde, und bestellnummer auslesen:
      $preis_row = sql_aktueller_produktpreis( $produkt_id );
      // jetzt die ganzen werte in die tabelle bestellvorschlaege schreiben:
      sql_insert_bestellvorschlaege( $produkt_id, $gesamtbestellung_id, $preis_row['id'] );
    } //end for - bestellvorschläge füllen
    $done = true;
  }
}

open_form( 'small_form', '', '', array( 'action' => 'insert' ) );
  foreach( $bestelliste as $p ) {
    echo "<input type='hidden' name='bestelliste[]' value='$p'>\n";
  }
  open_fieldset( 'small_form', "width:350px;", 'neue Bestellvorlage' );
    if( $problems )
      div_msg( 'warn', $problems );
    if( $done )
      div_msg( 'ok', 'Bestellvorlage wurde eingefÃ¼gt:' );

    open_table( '', "width='95%'" );
        open_td( 'label', '', 'Lieferant:' );
        open_td( 'kbd', '', lieferant_name( $lieferanten_id ) );
      open_tr();
        open_td( 'label', '', 'Name:' );
        open_td( 'kbd', '', string_view( $bestellname, 35, 'bestellname' ) );
      open_tr();
        open_td( 'label', '', 'Startzeit:' );
        open_td( 'kbd', '', date_time_view( $startzeit, 'startzeit' ) );
      open_tr();
        open_td( 'label', '', 'Ende:' );
        open_td( 'kbd', '', date_time_view( $endzeit, 'endzeit' ) );
      open_tr();
        open_td( 'label', '', 'Lieferdatum:' );
        open_td( 'kbd', '', date_view( $lieferung, 'lieferung' ) );
      open_tr();
        open_td( 'smallskip right', "colspan='2'" )
          if( ! $done ) {
            close_button( 'Abbrechen' );
            open_span('qquad');
            submissio n_button( MENATWORK 'Speichern' );
            close_span();
          } else {
            close_button( 'OK' );
          }
    close_table();
  close_fieldset();
close_form();

?>
