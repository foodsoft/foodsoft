<?PHP
assert( $angemeldet ) or exit();

setWindowSubtitle( 'Konto edieren' );
setWikiHelpTopic( 'foodsoft:konto_edieren' );
$editable = hat_dienst(4);
get_http_var( 'ro', 'u', 0, true );
if( $ro or $readonly )
  $editable = false;

$msg = '';
$problems = '';

get_http_var( 'konto_id', 'u', 0, true );

$row = $konto_id ? sql_kontodaten( $konto_id ) : false;
get_http_var('name','H',$row);
get_http_var('blz','H',$row);
get_http_var('kontonr','H',$row);
get_http_var('url','H',$row);
get_http_var('kommentar','H',$row);

get_http_var( 'action', 'w', '' );
$editable or $action = '';
if( $action == 'save' ) {
  $values = array(
    'name' => $name
  , 'blz' => $blz
  , 'kontonr' => $kontonr
  , 'url' => $url
  , 'kommentar' => $kommentar
  );
  if( ! $name ) {
    $problems .= "<div class='warn'>Kein Name eingegeben!</div>";
  } else {
    if( $konto_id ) {
      if( sql_update( 'bankkonten', $konto_id, $values ) ) {
        $msg .= "<div class='ok'>&Auml;nderungen gespeichert</div>";
      } else {
        $problems .= "<div class='warn'>Änderung fehlgeschlagen: " . mysqli_error($db_handle) . '</div>';
      }
    } else {
      if( ( $konto_id = sql_insert( 'bankkonten', $values ) ) ) {
        $self_fields['konto_id'] = $konto_id;
        $msg .= "<div class='ok'>Bankkonto erfolgreich eingetragen:</div>";
      } else {
        $problems .= "<div class='warn'>Eintrag fehlgeschlagen: " .  mysqli_error($db_handle) . "</div>";
      }
    }
  }
}

open_form( '', 'action=save' );
  open_fieldset( 'small_form', '', ( $konto_id ? 'Stammdaten Bankkonto' : 'Neues Bankkonto' ) );
    echo $msg . $problems;
    open_table('small_form hfill');
      form_row_text( 'Name:', ( $editable ? 'name' : false ), 50, $name );
      form_row_text( 'BLZ:', ( $editable ? 'blz' : false ), 50, $blz );
      form_row_text( 'Kontonummer:', ( $editable ? 'kontonr' : false ), 50, $kontonr );
      form_row_text( 'Webadresse:', ( $editable ? 'url' : false ), 50, $url );
      form_row_text( 'Kommentar:', ( $editable ? 'kommentar' : false ), 50, $kommentar );
      open_tr();
        open_td( 'right', "colspan='2'" );
          if( $konto_id > 0 )
            echo fc_link( 'konto', "konto_id=$konto_id,text=Kontoübersicht..." );
          qquad();
          if( $editable )
            submission_button();
          else
            close_button();
    close_table();
  close_fieldset();
close_form();

?>
