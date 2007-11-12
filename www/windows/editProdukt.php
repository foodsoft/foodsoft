<?PHP
  assert( $angemeldet ) or exit();


  get_http_var( 'ro', 'u', 0, true );
  if( $readonly )
    $ro = 1;

  $onload_str = "";       // befehlsstring der beim laden ausgef�hrt wird...

  ( $dienst == 4 ) or $ro = 1;
  nur_fuer_dienst_IV();

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
  $lieferant_name = lieferant_name( $lieferanten_id );

  $action = '';
  if( ! $ro )
    get_http_var( 'action', 'w', '' );

  if( $action == 'save' ) {
    $values = array(
      'name' => $name
    , 'produktgruppen_id' => $produktgruppen_id
    , 'lieferanten_id' => $lieferanten_id
    , 'notiz' => $notiz
    );
    // $newEinheit                                     = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_einheit']));

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

  $ro_tag = ( $ro ? 'readonly' : '' );

  ?>
   <form action='<? echo self_url(); ?>' method='post' class='small_form'>
   <? echo self_post(); ?>
   <input type='hidden' name='action' value='save'>
      <fieldset style='width:470px;' class='small_form'>
      <legend><? echo ( $produkt_id ? 'Stammdaten Produkt' : 'Neues Produkt' ); ?></legend>
      <? echo $msg . $problems; ?>
			  <table>
         <tr>
           <td><label>Lieferant:</label></td>
           <td><? echo $lieferant_name; ?></td>
			   <tr>
				    <td><label>Name:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $name; ?>" name='name'></td>
				 </tr>
		     <tr>
			     <td><label>Produktgruppe  <a style="font-size:10pt; text-decoration:none;" href="javascript:window.open('index.php?window=insertProduktgruppe','produkteKategorie','width=250,height=350,left=200,top=100').focus()"> - neu</a></label></td>
					<td>
						<select name="produktgruppen_id">
            <? echo optionen_produktgruppen( isset( $produktgruppen_id ) ? $produktgruppen_id : 0 ); ?>
	          </select>
					</td>
			   </tr>
         <!-- TODO: einheiten (wie preis) sind keine Stammdaten; trotzdem hier eingeben lassen?
		     <tr>
			    <td><label>Verteil-Einheit (z.B. 100 gr)</label></td>
					<td><input type="input" size="20" name="newProdukt_einheit"></td>
			 </tr>		 
			    <td><b>Liefer-Einheit (z.B. 200 gr)</b></td>
					<td><input type="input" size="20" name="newProdukt_liefereinheit"></td>
			 </tr>		 
       -->
       <!-- TODO: produktkategorien: benutzen wir die?
		   <tr>
			    <td valign="top"><b>Kategorie <a style="font-size:10pt; text-decoration:none;" href="javascript:window.open('index.php?insertProduktkategorie','produkteKategorie','width=250,height=350,left=200,top=100').focus()"> - neu</a></b></td>
					<td>
					
			    	<select name="newProduk_kategorien[]" size="5" multiple="multiple">
								   while ($row = mysql_fetch_array($kategorien)){ 
								
										   <option value="<?PHP echo $row['id']; ?>"><?PHP echo $row['name']; ?></option>
											 
						       } 
			    	</select>
							
					</td>
			 </tr>				 
       -->
		   <tr>
			    <td valign="top"><b>Notiz</b></td>
					<td>
						 <textarea name="notiz"><? echo $notiz; ?></textarea>
					</td>
			 </tr>	 
			 <tr>
			   <td colspan='2' align='center'>
         <? if( ! $ro ) { ?>
           <input type='submit' value='<? echo ( $produkt_id ? 'Ändern' : 'Einfügen'); ?>'>
           &nbsp;
            <? } ?>
            <?  if( $ro or $done ) { ?>
              <input value='Schließen' type='button' onClick='if(opener) opener.focus(); closeCurrentWindow();'>
            <? } ?>
         </td>
			 </tr>
		</table>
    </fieldset>
	 </form>
   
