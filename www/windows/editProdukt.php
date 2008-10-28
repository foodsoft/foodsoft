<?PHP
assert( $angemeldet ) or exit();

$editable = hat_dienst(4);
get_http_var( 'ro', 'u', 0, true );
if( $ro or $readonly )
  $editable = false;

$msg = "";
$problems = "";
$done = "";

get_http_var( 'produkt_id', 'u', 0, true );
if( $produkt_id ) {
  $row = sql_produkt_details( $produkt_id );
  setWindowSubtitle( 'Artikeldaten' );
  setWikiHelpTopic( 'foodsoft:artikeldaten' );
  $lieferanten_id = $row['lieferanten_id'];
} else {
  need_http_var( 'lieferanten_id', 'u', true );
  setWindowSubtitle( 'Neuen Artikel eintragen' );
  setWikiHelpTopic( 'foodsoft:artikeldaten' );
  $row = false;
}
get_http_var('name','H',$row);
get_http_var('produktgruppen_id','u',$row);
get_http_var('notiz','H',$row);
get_http_var('artikelnummer','H',$row);
$lieferant_name = sql_lieferant_name( $lieferanten_id );

$action = '';
get_http_var( 'action', 'w', '' );
$editable or $action = '';

if( $action == 'save' ) {
  $values = array(
    'name' => $name
  , 'produktgruppen_id' => $produktgruppen_id
  , 'lieferanten_id' => $lieferanten_id
  , 'artikelnummer' => $artikelnummer
  , 'notiz' => $notiz
  );

  if( ! $name ) $problems .= "<div class='warn'>Das neue Produkt mu� einen Name haben!</div>";
  if( ! $produktgruppen_id ) $problems .= "<div class='warn'>Das neue Produkt muß zu einer Produktgruppe gehören!</div>";

  // Wenn keine Fehler, dann einf�gen...
  if( ! $problems ) {
    if( $produkt_id ) {
      if( sql_update( 'produkte', $produkt_id, $values ) ) {
        $msg = $msg . "<div class='ok'>&Auml;nderungen gespeichert</div>";
        $done = true;
      } else {
        $problems = $problems . "<div class='warn'>Änderung fehlgeschlagen: " . mysql_error() . '</div>';
      }
    } else {
      if( ( $produkt_id = sql_insert( 'produkte', $values ) ) ) {
        $self_fields['produkt_id'] = $produkt_id;
        $msg = $msg . "<div class='ok'>Produkt erfolgreich eingetragen:</div>";
        $done = true;
      } else {
        $problems = $problems . "<div class='warn'>Eintrag fehlgeschlagen: " .  mysql_error() . "</div>";
      }
    }
  }
}

open_form( 'small_form', '', 'action=save' );
  open_fieldset( 'small_form', "style='width:400px;'", ( $produkt_id ? 'Stammdaten Produkt' : 'Neues Produkt' ) );
    echo $msg . $problems;
    open_table('small_form');
        open_td('label', '', 'Lieferant:' );
        open_td('kbd', '', $lieferant_name );
      open_tr();
        open_td('label', '', 'Bezeichnung:' );
        open_td('kbd');
          $attr = '';
          if( $produkt_id )
            $attr = "onFocus=\"document.getElementById('name_change_warning').style.display='inline';\"
                     onBlur=\"document.getElementById('name_change_warning').style.display='none';\"";
          echo string_view( $name, 40, ( $editable ? 'name' : false ), $attr );
      open_tr();
        open_td('label', '', 'Artikelnummer:' );
        open_td( 'kbd', '', string_view( $artikelnummer, 10, ( $editable ? 'artikelnummer' : false ) ) );
      open_tr();
        open_td('label', '', fc_link( 'produktgruppen', 'class=href,text=Produktgruppe:' ) );
        open_td('kbd', '', produktgruppe_view( $produktgruppen_id, 'produktgruppen_id' ) );
      open_tr();
        open_td('label', '', 'Notiz:' );
        open_td( 'kbd', '', string_view( $notiz, 40, ( $editable ? 'notiz' : false ) ) );
      open_tr();
        open_td('right smallskip', "colspan='2'");
          if( $produkt_id > 0 )
            echo fc_link( 'produktpreise', "produkt_id=$produkt_id,text=Details / Preise..." );
          open_span('qquad');
          if( $editable and ! $done )
            submission_button();
          else
            close_button();
          close_span();
    close_table();

    if( $produkt_id ) {
      open_div( 'kommentar', "id='name_change_warning' style='display:none;'" );
        open_div( 'smallskip', '', "Hinweis: die Produktbezeichnung sollte möglichst nicht geändert werden,
                                    da sich Änderungen auch rückwirkend auf alte Abrechnungen auswirken! " );
        open_div( 'smallskip', '', "Aktuelle und veränderliche Angaben bitte als 'Notiz' speichern!" );
      close_div();
    }
  close_fieldset();
close_form();

?>
