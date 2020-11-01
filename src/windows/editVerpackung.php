<?PHP
assert( $angemeldet ) or exit();

$editable = ( hat_dienst(4) and ! $readonly );

$msg = "";
$problems = "";
$done = "";

setWikiHelpTopic( 'foodsoft:pfandverpackungen' );

get_http_var( 'verpackung_id', 'u', 0, true );
if( $verpackung_id ) {
  $row = sql_select_single_row( "SELECT * FROM pfandverpackungen WHERE id=$verpackung_id" );
  setWindowSubtitle( 'Pfandverpackung - Daten' );
  $lieferanten_id = $row['lieferanten_id'];
} else {
  need_http_var( 'lieferanten_id', 'u', true );
  setWindowSubtitle( 'Neue Pfandverpackung eintragen' );
  $row = array( 'name' => '', 'wert' => 0.00, 'mwst' => 7.00 );
}
$lieferant_name = sql_lieferant_name( $lieferanten_id );

get_http_var('name','H',$row);
get_http_var('wert','f',$row);
get_http_var('mwst','f',$row);

get_http_var( 'action', 'w', '' );
$editable or $action  = '';
if( $action == 'save' ) {
  $values = array(
    'name' => $name
  , 'wert' => $wert
  , 'mwst' => $mwst
  , 'lieferanten_id' => $lieferanten_id
  );

  if( ! $name ) $problems .= "<div class='warn'>Die neue Verpackung muss eine Bezeichnung haben!</div>";

  // Wenn keine Fehler, dann einfügen...
  if( ! $problems ) {
    if( $verpackung_id ) {
      if( sql_update( 'pfandverpackungen', $verpackung_id, $values ) ) {
        $msg .= "<div class='ok'>&Auml;nderungen gespeichert</div>";
        $done = true;
      } else {
        $problems .= "<div class='warn'>Änderung fehlgeschlagen: " . mysqli_error($db_handle) . '</div>';
      }
    } else {
      if( ( $verpackung_id = sql_insert( 'pfandverpackungen', $values ) ) ) {
        $self_fields['verpackung_id'] = $verpackung_id;
        sql_update( 'pfandverpackungen', $verpackung_id, array( 'sort_id' => $verpackung_id ) );
        $msg .= "<div class='ok'>Verpackung erfolgreich eingetragen:</div>";
        $done = true;
      } else {
        $problems .= "<div class='warn'>Eintrag fehlgeschlagen: " . mysqli_error($db_handle) . "</div>";
      }
    }
  }
}

open_form( '', 'action=save' );
  open_fieldset( 'small_form', '', ( $verpackung_id ? 'Stammdaten Verpackung' : 'Neue Verpackung' ) );
    echo $msg . $problems;
    open_table('small_form hfill');
      form_row_lieferant( 'Lieferant:', false, $lieferanten_id );
      form_row_text( 'Bezeichnung:', 'name', 30, $name );
      form_row_betrag( 'Wert:', 'wert', $wert );
      form_row_betrag( 'MWSt:', 'mwst', $mwst );
      open_tr();
        open_td('right smallskip', "colspan='2'");
          if( $editable ) {
            reset_button(); submission_button();
          } else {
            close_button();
          }
    close_table();

    if( $verpackung_id and $editable and ! $done )
      open_div( 'kommentar', '', "Hinweis: Änderungen (Preis, MWSt) wirken sich auch rückwirkend auch auf alte Abrechnungen aus!" );

  close_fieldset();
close_form();

?>
