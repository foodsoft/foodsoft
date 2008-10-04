<?PHP
assert( $angemeldet ) or exit();

$editable = hat_dienst(4,5);
get_http_var( 'ro', 'u', 0, true );
if( $ro or $readonly )
  $editable = false;

$msg = '';
$problems = '';
$done = false;

get_http_var( 'lieferanten_id', 'u', 0, true );
if( $lieferanten_id ) {
  $row = sql_getLieferant( $lieferanten_id );
} else {
  $row = false;
}
get_http_var('name','H',$row);
get_http_var('adresse','H',$row);
get_http_var('ansprechpartner','H',$row);
get_http_var('telefon','H',$row);
get_http_var('fax','H',$row);
get_http_var('mail','H',$row);
get_http_var('liefertage','H',$row);
get_http_var('bestellmodalitaeten','H',$row);
get_http_var('kundennummer','H',$row);
get_http_var('url','H',$row);

get_http_var( 'action', 'w', '' );
$editable or $action = '';
if( $action == 'save' ) {
  $values = array(
    'name' => $name
  , 'adresse' => $adresse
  , 'ansprechpartner' => $ansprechpartner
  , 'telefon' => $telefon
  , 'fax' => $fax
  , 'mail' => $mail
  , 'liefertage' => $liefertage
  , 'bestellmodalitaeten' => $bestellmodalitaeten
  , 'kundennummer' => $kundennummer
  , 'url' => $url
  );
  if( ! $name ) {
    $problems = $problems . "<div class='warn'>Kein Name eingegeben!</div>";
  } else {
    if( $lieferanten_id ) {
      if( sql_update( 'lieferanten', $lieferanten_id, $values ) ) {
        $msg = $msg . "<div class='ok'>&Auml;nderungen gespeichert</div>";
        $done = true;
      } else {
        $problems = $problems . "<div class='warn'>Änderung fehlgeschlagen: " . mysql_error() . '</div>';
      }
    } else {
      if( ( $lieferanten_id = sql_insert( 'lieferanten', $values ) ) ) {
        $self_fields['lieferanten_id'] = $lieferanten_id;
        $msg = $msg . "<div class='ok'>Lieferant erfolgreich angelegt:</div>";
        $done = true;
      } else {
        $problems = $problems . "<div class='warn'>Eintrag fehlgeschlagen: " .  mysql_error() . "</div>";
      }
    }
  }
}

$ro_tag = ( $ro ? 'readonly' : '' );

open_form( 'small_form', '', '', array( 'action' => 'save' ) );
  open_fieldset( 'small_form', "style='width:470px;'", ( $lieferanten_id ? 'Stammdaten Lieferant' : 'Neuer Lieferant' ) );
    echo $msg . $problems;
    open_table('small_form');
        open_td( 'label', '', 'Name:' );
        open_td( 'kbd', '', string_view( $name, 50, ( $editable ? 'name' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Adresse:' );
        open_td( 'kbd', '', string_view( $adresse, 50, ( $editable ? 'adresse' : false ) ) );
      open_tr();
        open_td( 'label', '', 'AnsprechpartnerIn:' );
        open_td( 'kbd', '', string_view( $ansprechpartner, 50, ( $editable ? 'ansprechpartner' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Telefonnummer:' );
        open_td( 'kbd', '', string_view( $telefon, 50, ( $editable ? 'telefon' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Faxnummer:' );
        open_td( 'kbd', '', string_view( $fax, 50, ( $editable ? 'fax' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Email:' );
        open_td( 'kbd', '', string_view( $mail, 50, ( $editable ? 'mail' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Liefertage:' );
        open_td( 'kbd', '', string_view( $liefertage, 50, ( $editable ? 'liefertage' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Bestellmodalit&auml;ten:' );
        open_td( 'kbd', '', string_view( $bestellmodalitaeten, 50, ( $editable ? 'bestellmodalitaeten' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Kundennummer:' );
        open_td( 'kbd', '', string_view( $kundennummer, 50, ( $editable ? 'kundennummer' : false ) ) );
      open_tr();
        open_td( 'label', '', 'Webadresse:' );
        open_td( 'kbd', '', string_view( $url, 50, ( $editable ? 'url' : false ) ) );
      open_tr();
        open_td( 'right', "colspan='2'" );
          if( $lieferanten_id > 0 ) {
            echo fc_alink( 'lieferantenkonto', "lieferanten_id=$lieferanten_id,text=Lieferantenkonto..." );
          }
          open_span( 'qquad' );
          if( $editable )
            submission_button();
          else
            close_button();
          close_span();
    close_table();
  close_fieldset();
close_form();

?>
