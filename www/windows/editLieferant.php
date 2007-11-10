<?PHP
  assert( $angemeldet ) or exit();

  get_http_var( 'ro', 'u', 0, true );
  if( $readonly )
    $ro = 1;

  ( $dienst == 4 ) or ( $dienst == 5 ) or $ro = 1;

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

  $action = '';
  if( ! $ro )
    get_http_var( 'action', 'w', '' );

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

  ?>
   <form action='<? echo self_url(); ?>' method='post' class='small_form'>
   <? echo self_post(); ?>
   <input type='hidden' name='action' value='save'>
      <fieldset style='width:470px;' class='small_form'>
      <legend><? echo ( $lieferanten_id ? 'Stammdaten Lieferant' : 'Neuer Lieferant' ); ?></legend>
      <? echo $msg . $problems; ?>
			  <table>
			   <tr>
				    <td><label>Name:</label></td>
						<td><? echo $name; ?></td>
				 </tr>
			   <tr>
				    <td><label>Name:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $name; ?>" name='name'></td>
				 </tr>
			   <tr>
				    <td><label>Adresse:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $adresse; ?>" name='adresse'></td>
				 </tr>				 
			   <tr>
				    <td><label>AnsprechpartnerIn:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $ansprechpartner; ?>" name='ansprechpartner'></td>
				 </tr>				 
			   <tr>
				    <td><label>Telefonnummer:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $telefon; ?>" name='telefon'></td>
				 </tr>
			   <tr>
				    <td><label>Faxnummer:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $fax; ?>" name='fax'></td>
				 </tr>				 
			   <tr>
				    <td><label>Email-Adresse:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $mail; ?>" name='mail'></td>
				 </tr>				 
			   <tr>
				    <td><label>Liefertage:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $liefertage; ?>" name='liefertage'></td>
				 </tr>				
			   <tr>
				    <td><label>Bestellmodalitäten:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $bestellmodalitaeten; ?>" name='bestellmodalitaeten'></td>
				 </tr>				  
			   <tr>
				    <td><label>eigene Kundennummer:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $kundennummer; ?>" name='kundennummer'></td>
				 </tr>
			   <tr>
				    <td><label>Internetseiten:</label></td>
						<td><input <? echo $ro_tag; ?> type='text' size='50' value="<? echo $url; ?>" name='url'></td>
				 </tr>			 
				 <tr>
				    <td colspan='2' align='center'>
            <?  if( ! $ro ) { ?>
              <input type='submit' value='<? echo ( $lieferanten_id ? 'Ändern' : 'Einfügen'); ?>'>
              &nbsp;
            <? } ?>
            <?  if( $ro or $done ) { ?>
              <input value='Schließen' type='button' onClick='if(opener) opener.focus(); closeCurrentWindow();'>
            <? } ?>
				 </tr>
			</table>
    </fieldset>
	 </form>

</body>
</html>

