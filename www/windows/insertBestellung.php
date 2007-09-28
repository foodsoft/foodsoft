<?PHP

  assert( $angemeldet ) or exit();

  setWindowSubtitle( 'Neue Bestellvorlage anlegen' );
  setWikiHelpTopic( 'foodsoft:bestellvorlage_anlegen' );

  nur_fuer_dienst_IV();
  fail_if_readonly();

  $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
  $errStr = "";

  get_http_var('action','w');

  if( $action == 'insert' ) {
    need_http_var("startzeit_tag",'u');
    need_http_var("startzeit_monat",'u');
    need_http_var("startzeit_jahr",'u');
    need_http_var("startzeit_stunde",'u');
    need_http_var("startzeit_minute",'u');
    need_http_var("endzeit_tag"),'u';
    need_http_var("endzeit_monat",'u');
    need_http_var("endzeit_jahr",'u');
    need_http_var("endzeit_stunde",'u');
    need_http_var("endzeit_minute",'u');
    need_http_var("lieferung_tag",'u');
    need_http_var("lieferung_monat",'u');
    need_http_var("lieferung_jahr",'u');
    need_http_var("bestellname",'M');

    bestelliste = $HTTP_POST_VARS['bestelliste'];
    if( ! is_array( $bestelliste ) ) {
      $errStr = "Keine Produktliste nicht uebergeben!";
    }
    foreach( $bestelliste as $p )
      assert( preg_match( '/^\d+$/', $p ) or exit();

    $startzeit = "$startzeit_jahr-$startzeit_monat-$startzeit_tag $startzeit_stunde:$startzeit_minute:00";
    $endzeit = "$endzeit_jahr-$endzeit_monat-$endzeit_tag $endzeit_stunde:$endzeit_minute:00";
    $lieferung = "$lieferung_jahr-$lieferung_monat-$lieferung_tag";

    if( $bestellname == "" )
      $errStr.= "Die Bestellung muß einen Namen bekommen!<br>";

    // Wenn keine Fehler, dann einfügen...
    if ($errStr == "") {
      sql_insert_bestellung($bestellname, $startzeit, $endzeit, $lieferung);
      $gesamtbestellung_id = mysql_insert_id();

      for ($i = 0; $i < count($bestelliste); $i++) {
                        // preis, gebinde, und bestellnummer auslesen
        $result = sql_aktuelle_produktpreise($bestelliste[$i]);
        $produkt_row = mysql_fetch_array($result);  // alles in ein array schreiben
                          // jetzt die ganzen werte in die tabelle bestellvorschlaege schreiben
        sql_insert_bestellvorschlaege($bestelliste[$i],$gesamtbestellung_id,$produkt_row['id']);

      } //end for - bestellvorschläge füllen

    } //end if -wenn keine fehler ....
  
  } else {
     $startzeit   = date("Y-m-d  H:i:s");
     $endzeit  = date("Y-m-d  22:00:00");  

     $lieferung   = date("Y-m-d  H:i:s");

   }    

?>


<h3>neue Bestellung</h3>
  <form action='<? echo self_url(); ?>' method='post' class='small_form'>
    <input type="hidden" name="action" value="insert">
      
      <?PHP
         if (isset($HTTP_GET_VARS['bestelliste'])) {
             $bestelliste = $HTTP_GET_VARS['bestelliste'];
               
               while (list($key, $value) = each($bestelliste)) {
                  echo "<input type='hidden' name='bestelliste[]' value='".$value."'>\n";
               }
          }
      ?>
      
      <table class="menu" style="width:370px;">
          <tr>
               <td><b>Name</b></td>
               <td><input type="text" name="bestellname" size="35"></td>
          </tr>      
         <tr>
             <td valign="top"><b>Startzeit</b></td>
               <td>
               
		<?date_time_selector($startzeit,"startzeit");?>
                   
               </td>
          </tr>
         <tr>
             <td valign="top"><b>Ende</b></td>
               <td>
               
		<?date_time_selector($endzeit,"endzeit");?>
                   
               </td>
          </tr>
             <td valign="top"><b>Lieferung</b></td>
               <td>
               
		<?date_time_selector($lieferung,"lieferung",false);?>
               </td>
          </tr>
          <tr>
             <td colspan="2" align="middle"><input type="submit" value="Bestellung einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
          </tr>
      </table>
    </form>
    <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
