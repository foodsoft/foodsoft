<?PHP
assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:lieferant_edieren' );
setWindowSubtitle( 'Stammdaten Lieferant' );

$editable = hat_dienst(4,5);
get_http_var( 'ro', 'u', 0, true );
if( $ro or $readonly )
  $editable = false;

$msg = '';
$problems = '';

get_http_var( 'lieferanten_id', 'u', 0, true );

$row = $lieferanten_id ? sql_lieferant( $lieferanten_id ) : false;
get_http_var('name','H',$row);
get_http_var('strasse','H',$row);
get_http_var('ort','H',$row);
get_http_var('ansprechpartner','H',$row);
get_http_var('telefon','H',$row);
get_http_var('fax','H',$row);
get_http_var('mail','H',$row);

get_http_var('liefertage','H',$row);
get_http_var('bestellmodalitaeten','H',$row);
get_http_var('kundennummer','H',$row);
get_http_var('url','H',$row);
get_http_var('katalogformat','w',$row);

get_http_var( 'action', 'w', '' );
$editable or $action = '';
if( $action == 'save' ) {
  $values = array(
    'name' => $name
  , 'strasse' => $strasse
  , 'ort' => $ort
  , 'ansprechpartner' => $ansprechpartner
  , 'telefon' => $telefon
  , 'fax' => $fax
  , 'mail' => $mail
  , 'liefertage' => $liefertage
  , 'bestellmodalitaeten' => $bestellmodalitaeten
  , 'kundennummer' => $kundennummer
  , 'url' => $url
  , 'katalogformat' => $katalogformat
  );
  if( ! $name ) {
    $problems = $problems . "<div class='warn'>Kein Name eingegeben!</div>";
  } else {
    if( $lieferanten_id ) {
      if( sql_update( 'lieferanten', $lieferanten_id, $values ) ) {
        $msg = $msg . "<div class='ok'>&Auml;nderungen gespeichert</div>";
      } else {
        $problems = $problems . "<div class='warn'>Änderung fehlgeschlagen: " . mysqli_error($db_handle) . '</div>';
      }
    } else {
      if( ( $lieferanten_id = sql_insert( 'lieferanten', $values ) ) ) {
        $self_fields['lieferanten_id'] = $lieferanten_id;
        $msg = $msg . "<div class='ok'>Lieferant erfolgreich angelegt:</div>";
      } else {
        $problems = $problems . "<div class='warn'>Eintrag fehlgeschlagen: " . mysqli_error($db_handle) . "</div>";
      }
    }
  }
}

open_form( '', 'action=save' );
  open_fieldset( 'small_form', '', ( $lieferanten_id ? 'Stammdaten Lieferant' : 'Neuer Lieferant' ) );
    echo $msg . $problems;
    open_table('small_form hfill');
      form_row_text( 'Name:', ( $editable ? 'name' : false ), 50, $name );
      form_row_text( 'Strasse:', ( $editable ? 'strasse' : false ), 50, $strasse );
      form_row_text( 'PLZ Ort:', ( $editable ? 'ort' : false ), 50, $ort );
      form_row_text( 'AnsprechpartnerIn:', ( $editable ? 'ansprechpartner' : false ), 50, $ansprechpartner );
      form_row_text( 'Telefonnummer:', ( $editable ? 'telefon' : false ), 50, $telefon );
      form_row_text( 'Faxnummer:', ( $editable ? 'fax' : false ), 50, $fax );
      form_row_text( 'Email:', ( $editable ? 'mail' : false ), 50, $mail );
      form_row_text( 'Liefertage:', ( $editable ? 'liefertage' : false ), 50, $liefertage );
      form_row_text( 'Bestellmodalit&auml;ten:', ( $editable ? 'bestellmodalitaeten' : false ), 50, $bestellmodalitaeten );
      form_row_text( 'Kundennummer:', ( $editable ? 'kundennummer' : false ), 50, $kundennummer );
      form_row_text( 'Webadresse:', ( $editable ? 'url' : false ), 50, $url );
      open_tr();
        open_td( '', '', 'Katalogformat:' );
        open_td();
        open_select( 'katalogformat' );
          $selected = false;
          $options = '';
          foreach( array( 'terra_xls', 'bode', 'rapunzel', 'midgard', 'grell', 'bnn' ) as $parser ) {
            if( $katalogformat == $parser ) {
              $checked = 'selected';
              $selected = true;
            } else {
              $checked = '';
            }
            $options .= "<option value='$parser' $checked>$parser</option>";
          }
          if( $selected ) {
            echo "<option value='keins'>(unbekannt oder nicht implementiert)</option>";
          } else {
            echo "<option value='keins' checked>(bitte Katalogformat waehlen)</option>";
          }
          echo "$options";
        close_select();
      open_tr();
        open_td( 'right', "colspan='2'" );
          if( $lieferanten_id > 0 )
            echo fc_link( 'lieferantenkonto', "lieferanten_id=$lieferanten_id,text=Lieferantenkonto..." );
          qquad();
          if( $editable )
            submission_button();
          else
            close_button();
    close_table();
  close_fieldset();
close_form();

?>
