<?PHP

  assert( $angemeldet ) or exit();

  setWindowSubtitle( 'Neue Bestellvorlage anlegen' );
  setWikiHelpTopic( 'foodsoft:bestellvorlage_anlegen' );

  nur_fuer_dienst_IV();
  fail_if_readonly();

  $errStr = "";

  $startzeit = date("Y-m-d H:i:s");
  $endzeit   = date("Y-m-d 20:00:00");  
  $lieferung = date("Y-m-d H:i:s");
  $done = false;

  $bestelliste = array();
  if( isset( $HTTP_POST_VARS['bestelliste'] ) )
    $bestelliste = $HTTP_POST_VARS['bestelliste'];
  if( ! is_array( $bestelliste ) ) {
    $bestelliste = array();
  }
  foreach( $bestelliste as $p ) {
    if( ! preg_match( '/^\d+$/', $p ) ) {
      $errStr .= "Fehler in uebergebener Produktliste! ";
      $bestelliste = array();
    }
  }

  if( count($bestelliste) < 1 )
    $errStr .= "Keine Produkte ausgewÃ¤hlt! ";

  if( $errStr ) {
    echo "
      <div class='warn'>$errStr
        <a href='javascript:if(opener) opener.focus(); self.close();'>SchlieÃŸen...</a>
      </div>
    ";
    return;
  }

  get_http_var('action','w','');

  if( $action == 'insert' ) {
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
      $errStr .= "Die Bestellung muß einen Namen bekommen!<br>";

    // Wenn keine Fehler, dann einfügen...
    if ($errStr == "") {
      sql_insert_bestellung($bestellname, $startzeit, $endzeit, $lieferung);
      $gesamtbestellung_id = mysql_insert_id();

      for ($i = 0; $i < count($bestelliste); $i++) {
        // preis, gebinde, und bestellnummer auslesen:
        $result = sql_aktuelle_produktpreise($bestelliste[$i]);
        $produkt_row = mysql_fetch_array($result);  // alles in ein array schreiben
        // jetzt die ganzen werte in die tabelle bestellvorschlaege schreiben:
        sql_insert_bestellvorschlaege($bestelliste[$i],$gesamtbestellung_id,$produkt_row['id']);
      } //end for - bestellvorschläge füllen
      $done = true;
    }
   }

  if( $errStr ) 
    echo "<div class='warn'>$errStr</div>";

?>

<form action='<? echo self_url(); ?>' method='post' class='small_form'>
  <fieldset style='width:390px;' class='small_form'>
    <legend>neue Bestellvorlage</legend>
    <input type="hidden" name="action" value="insert">

    <?PHP
      foreach( $bestelliste as $p ) {
        echo "<input type='hidden' name='bestelliste[]' value='$p'>\n";
      }
    ?>

    <table style="width:370px;">
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
        <td colspan="2">
          <?
            if( ! $done ) {
              ?>
                <input type='submit' value='Einf&uuml;gen'>
                <input type='button' value='Abbrechen' onclick='if(opener) opener.focus(); window.close();'>
              <?
            } else {
              ?>
                <input value='OK' type='button' onClick='if(opener) opener.focus();window.close();'>
              <?
            }
          ?>
        </td>
      </tr>
    </table>
  </fieldset>
</form>

