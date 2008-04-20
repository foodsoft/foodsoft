<?PHP
  assert( $angemeldet ) or exit();

  $onload_str = "";       // befehlsstring der beim laden ausgef�hrt wird...

  $editable = ( ! $readonly and ( $dienst == 4 ) );
  $editable = true;

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
  $lieferant_name = lieferant_name( $lieferanten_id );

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

    // Wenn keine Fehler, dann einf�gen...
    if( ! $problems ) {
      if( $verpackung_id ) {
        if( sql_update( 'pfandverpackungen', $verpackung_id, $values ) ) {
          $msg = $msg . "<div class='ok'>&Auml;nderungen gespeichert</div>";
          $done = true;
        } else {
          $problems = $problems . "<div class='warn'>Änderung fehlgeschlagen: " . mysql_error() . '</div>';
        }
      } else {
        if( ( $verpackung_id = sql_insert( 'pfandverpackungen', $values ) ) ) {
          $self_fields['verpackung_id'] = $verpackung_id;
          $msg = $msg . "<div class='ok'>Verpackung erfolgreich eingetragen:</div>";
          $done = true;
        } else {
          $problems = $problems . "<div class='warn'>Eintrag fehlgeschlagen: " .  mysql_error() . "</div>";
        }
      }
    }
  }

  $ro_tag = ( $editable ? '' : 'readonly' );

  ?>
  <form action='<? echo self_url(); ?>' method='post' class='small_form'>
    <? echo self_post(); ?>
    <input type='hidden' name='action' value='save'>
    <fieldset style='width:460px;' class='small_form'>
      <legend><? echo ( $verpackung_id ? 'Stammdaten Verpackung' : 'Neue Verpackung' ); ?></legend>
      <? echo $msg . $problems; ?>
      <table>
        <tr>
          <td><label>Lieferant:</label></td>
          <td><? echo $lieferant_name; ?></td>
        </tr>
        <tr>
          <td><label>Bezeichnung:</label></td>
          <td><input <? echo $ro_tag; ?> type='text' size='30' value="<? echo $name; ?>" name='name'></td>
        </tr>
        <tr>
          <td valign="top"><label>Wert: </label></td>
          <td>
            <input <? echo $ro_tag; ?> name="wert" type='text' size='10' value='<? printf( "%.2lf", $wert); ?>'>
          </td>
        </tr>	 
        <tr>
          <td valign="top"><label>Mehrwertsteuer: </label></td>
          <td>
            <input <? echo $ro_tag; ?> name="mwst" type='text' size='10' value='<? printf( "%.2lf", $mwst); ?>'>
          </td>
        </tr>	 
      </table>
      <table width='100%'>
        <tr>
          <td style='text-align:left;white-space:nowrap;'>
            <? if( $editable ) { ?>
              <input type='submit' value='<? echo ( $verpackung_id ? 'Ändern' : 'Einfügen'); ?>'>
              &nbsp;
            <? } ?>
            <?  if( $done or ! $editable ) { ?>
              <input value='Schließen' type='button' onClick='if(opener) opener.focus(); closeCurrentWindow();'>
            <? } ?>
         </td>
        </tr>
      </table>

      <? if( $verpackung_id and $editable and ! $done ) { ?>
        <div class='warn' id='name_change_warning' style='margin-top:2em;display:block;'>
        <p>
          Warnung: Änderungen (Preis, MWSt) wirken sich auch rückwirkend auf alte Abrechnungen aus!
        </p>
        </div>
      <? } ?>
    </fieldset>
  </form>

