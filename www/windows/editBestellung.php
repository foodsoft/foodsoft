<?PHP

  assert( $angemeldet ) or exit();
  assert( isset( $sockelbetrag ) );  // sollte in leitvariablen definiert sein!

  setWindowSubtitle( 'Bestellvorlage edieren' );
  setWikiHelpTopic( 'foodsoft:bestellvorlage_edieren' );

  nur_fuer_dienst_IV();
  fail_if_readonly();

  need_http_var( 'bestell_id','u', true );

  $msg = '';
  $pwmsg = '';
  $problems = '';

  $result = sql_bestellungen( false, false, $bestell_id );
  echo "<!-- bestell_id: $bestell_id, rows: " .mysql_num_rows($result) . " -->";
  if( mysql_num_rows( $result ) != 1 ) {
    $problems .= "<div class='warn'>Konnte Bestellung nicht aus Gesamtbestellungen laden</div>";
  }

  if( $problems ) {
    echo "
      $problems
      <div class='warn'>
        <a href='javascript:if(opener) opener.focus(); self.close();'>Fenster schließen...</a>
      </div>
    ";
    return;
  }

  $bestellung = mysql_fetch_array( $result );
  $startzeit = $bestellung['bestellstart'];
  $endzeit = $bestellung['bestellende'];
  $lieferung = $bestellung['lieferung'];
  $bestellname = $bestellung['name'];
  $status = $bestellung['state'];

  get_http_var('action','w','');

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
    need_http_var("bestellname",'M');

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
    <legend>Bestellvorlage edieren</legend>
    <? echo $msg; echo $problems; ?>
    <table style="width:350px;">
      <tr>
        <td><label>Name:</label></td>
        <td><input type="text" name="bestellname" size="35" value="<? echo "$bestellname"; ?>"></td>
      </tr>
      <tr>
        <td valign="top"><label>Startzeit:</label></td>
        <td>
          <?date_time_selector($startzeit,"startzeit");?>
        </td>
      </tr>
      <tr>
        <td valign="top"><label>Ende:</label></td>
        <td>
          <?date_time_selector($endzeit,"endzeit");?>
        </td>
      </tr>
      <tr>
        <td valign="top"><label>Lieferung:</label></td>
        <td>
          <?date_time_selector($lieferung,"lieferung",false);?>
        </td>
      </tr>
      <tr>
        <td colspan='2' align='center'><input type='submit' value='&Auml;ndern'></td>
      </tr>
    </table>
  </fieldset>
</form>


