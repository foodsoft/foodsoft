<?PHP

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:bestellvorlage_edieren' );
setWindowSubtitle( 'Stammdaten Bestellvorlage' );

$msg = '';
$problems = '';
$done = false;

get_http_var( 'bestell_id','u', 0, true );

if( $bestell_id ) {  // existierende bestellvorlage bearbeiten:
  $bestellung = sql_bestellung( $bestell_id );
  $startzeit = $bestellung['bestellstart'];
  $endzeit = $bestellung['bestellende'];
  $lieferung = $bestellung['lieferung'];
  $bestellname = $bestellung['name'];
  $status = $bestellung['state'];
  $lieferanten_id = $bestellung['lieferanten_id'];
} else {  // neue bestellvorlage erstellen:
  $startzeit = date("Y-m-d H:i:s");
  $endzeit   = date("Y-m-d 20:00:00");
  $lieferung = date("Y-m-d H:i:s");
  $bestellname = "";
  $status = STATUS_BESTELLEN;
  need_http_var( 'lieferanten_id', 'U', true );
  get_http_var( 'bestellliste[]','U' );
  if( ! isset($bestellliste) or count($bestellliste) < 1 ) {
    $problems .= "<div class='warn'>Keine Produkte ausgewählt!</div>";
  }
}
$editable = ( hat_dienst(4) and ( ! $readonly ) and ( $status < STATUS_ABGERECHNET ) );

get_http_var('action','w','');
$editable or $action = '';

if( $action == 'save' ) {
  need_http_var("startzeit_day",'u');
  need_http_var("startzeit_month",'u');
  need_http_var("startzeit_year",'u');
  need_http_var("startzeit_hour",'u');
  need_http_var("startzeit_minute",'u');
  need_http_var("endzeit_day",'u');
  need_http_var("endzeit_month",'u');
  need_http_var("endzeit_year",'u');
  need_http_var("endzeit_hour",'u');
  need_http_var("endzeit_minute",'u');
  need_http_var("lieferung_day",'u');
  need_http_var("lieferung_month",'u');
  need_http_var("lieferung_year",'u');
  need_http_var("bestellname",'H');

  $startzeit = "$startzeit_year-$startzeit_month-$startzeit_day $startzeit_hour:$startzeit_minute:00";
  $endzeit = "$endzeit_year-$endzeit_month-$endzeit_day $endzeit_hour:$endzeit_minute:00";
  $lieferung = "$lieferung_year-$lieferung_month-$lieferung_day";

  if( $bestellname == "" )
    $problems  .= "<div class='warn'>Die Bestellung mu� einen Namen bekommen!</div>";

  if( $problems == '' ) {
    if( $bestell_id ) {
      if( sql_update_bestellung( $bestellname, $startzeit, $endzeit, $lieferung, $bestell_id ) ) {
        $done = true;
        $msg .= "<div class='ok'>Änderungen gespeichert!</div>";
      } else {
        $problems .= "<div class='warn'>Änderung fehlgeschlagen!</div>";
      }
    } else {
      $bestell_id = sql_insert_bestellung($bestellname, $startzeit, $endzeit, $lieferung, $lieferanten_id );

      // jetzt die ganzen werte in die tabelle bestellvorschlaege schreiben:
      foreach( $bestellliste as $produkt_id ) {
        sql_insert_bestellvorschlag( $produkt_id, $bestell_id );
      }
      $done = true;
      $self_fields['bestell_id'] = $bestell_id;
    }
  }
}

open_form( '', 'action=save' );
  if( isset( $bestellliste ) and is_array( $bestellliste ) )
    foreach( $bestellliste as $produkt_id )
      hidden_input( 'bestellliste[]', $produkt_id );
  open_fieldset( 'small_form', '', 'Bestellvorlage' );
    echo $msg; echo $problems;
    if( $done )
      div_msg( 'ok', 'Bestellvorlage wurde eingefügt:' );
    open_table( 'layout hfill' );
      form_row_lieferant( 'Lieferant:', false, $lieferanten_id );
      form_row_text( 'Name:', ( $editable ? 'bestellname' : false ), 35, $bestellname );
      form_row_date_time( 'Startzeit:', ( $editable ? 'startzeit' : false ), $startzeit );
      form_row_date_time( 'Ende:', ( $editable ? 'endzeit' : false ), $endzeit );
      form_row_date( 'Lieferung:', ( $editable ? 'lieferung' : false ), $lieferung );
      open_tr();
        open_td('right', "colspan='2'");
          if( $editable )
            submission_button();
          else
            close_button();
    close_table();
  close_fieldset();
close_form();

?>
