<?PHP

assert( $angemeldet ) or exit();

setWindowSubtitle( 'Bestellvorlage edieren' );
setWikiHelpTopic( 'foodsoft:bestellvorlage_edieren' );

$msg = '';
$problems = '';

need_http_var( 'bestell_id','u', true );
get_http_var( 'ro', 'u', 0, true );

$editable = ( ( $dienst == 4 ) and ( ! $readonly ) and ( ! $ro ) and ( getState( $bestell_id ) < STATUS_ABGERECHNET ) );
$ro_tag = ( $editable ? '' : 'readonly' );

$bestellung = sql_bestellung( $bestell_id );
$startzeit = $bestellung['bestellstart'];
$endzeit = $bestellung['bestellende'];
$lieferung = $bestellung['lieferung'];
$bestellname = $bestellung['name'];
$status = $bestellung['state'];

get_http_var('action','w','');
$editable or $action = '';

if( $action == 'update' ) {
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
    $problems  .= "<div class='warn'>Die Bestellung mu� einen Namen bekommen!</div>";

  if( $problems == '' ) {
    if( sql_update_bestellung( $bestellname, $startzeit, $endzeit, $lieferung, $bestell_id ) ) {
      $done = true;
      $msg .= "<div class='ok'>Änderungen gespeichert!</div>";
    } else {
      $problems .= "<div class='warn'>Änderung fehlgeschlagen!</div>";
    }
  }
}

open_form( 'small_form', '', '', array( 'action' => 'update' ) );
  open_fieldset( 'small_form', "style='width:360px;'", 'Bestellvorlage '. ( $editable ? 'edieren' : '(abgeschlossen)' ) );
    echo $msg; echo $problems;
    open_table( 'small_form',"style='width:420px;'" );
        open_td( 'label', '', 'Lieferant:' );
        open_td( 'kbd', '', lieferant_name( $bestellung['lieferanten_id'] ) );
      open_tr();
        open_td( 'label', '', 'Name:' );
        open_td( 'kbd', '', string_view( $bestellname, 35, ( $editable ? 'bestellname' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Startzeit:' );
        open_td( 'kbd', '', date_time_view( $startzeit, ( $editable ? 'startzeit' : false ) ) );
      open_tr();
        open_td( 'label', 'Ende:' );
        open_td( 'kbd', '', date_time_view( $endzeit, ( $editable ? 'endzeit' : false ) ) );
      open_tr();
        open_td( 'label', 'Lieferung:' );
        open_td( 'kbd', '', date_view( $lieferung, ( $editable ? 'lieferung' : false ) ) );
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
