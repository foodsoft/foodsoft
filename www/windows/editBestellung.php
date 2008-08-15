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

?>
<form action='<? echo self_url(); ?>' method='post' class='small_form'>
  <? echo self_post(); ?>
  <input type='hidden' name='action' value='update'>
  <fieldset style='width:360px;' class='small_form'>
    <legend>Bestellvorlage <? echo ( $editable ? 'edieren' : '(abgeschlossen)' ); ?></legend>
    <? echo $msg; echo $problems; ?>
    <table style="width:420px;">
      <tr>
        <td><label>Lieferant:</label></td>
        <td>
          <kbd><? echo lieferant_name( $bestellung['lieferanten_id'] ); ?></kbd>
        </td>
      </tr>
      <tr>
        <td><label>Name:</label></td>
        <td>
          <? if( $editable ) { ?>
            <input type="text" name="bestellname" size="35" value="<? echo "$bestellname"; ?>">
          <? } else { ?>
            <kbd><? echo $bestellname; ?></kbd>
          <? } ?>
        </td>
      </tr>
      <tr>
        <td valign="top"><label>Startzeit:</label></td>
        <td>
          <? if( $editable ) { ?>
            <?date_time_selector($startzeit,"startzeit");?>
          <? } else { ?>
            <kbd><? echo $startzeit; ?></kbd>
          <? } ?>
        </td>
      </tr>
      <tr>
        <td valign="top"><label>Ende:</label></td>
        <td>
          <? if( $editable ) { ?>
            <?date_time_selector($endzeit,"endzeit");?>
          <? } else { ?>
            <kbd><? echo $endzeit; ?></kbd>
          <? } ?>
        </td>
      </tr>
      <tr>
        <td valign="top"><label>Lieferung:</label></td>
        <td>
          <? if( $editable ) { ?>
            <?date_time_selector($lieferung,"lieferung",false);?>
          <? } else { ?>
            <kbd><? echo $lieferung; ?></kbd>
          <? } ?>
        </td>
      </tr>
      <tr>
        <td colspan='2' style='text-align:right;'>
          <? if( $editable ) { ?>
            <input type='submit' value='&Auml;ndern'>
          <? } ?>
          <input value='Schließen' type='button' onClick='if(opener) opener.focus(); closeCurrentWindow();'>
        </td>
      </tr>
    </table>
  </fieldset>
</form>


