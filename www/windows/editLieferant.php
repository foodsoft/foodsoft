<?PHP
  assert( $angemeldet ) or exit();
  nur_fuer_dienst(4,5);

  $msg = '';
  $problems = '';
  $done = false;

  get_http_var( 'lieferanten_id', 'u', 0, true );
  if( $lieferanten_id ) {
    $row = sql_getLieferant( $lieferanten_id );
  } else {
    $row = false;
  }
  get_http_var('name','F',$row);
  get_http_var('adresse','F',$row);
  get_http_var('ansprechpartner','F',$row);
  get_http_var('telefon','F',$row);
  get_http_var('fax','F',$row);
  get_http_var('mail','F',$row);
  get_http_var('liefertage','F',$row);
  get_http_var('bestellmodalitaeten','F',$row);
  get_http_var('kundennummer','F',$row);
  get_http_var('url','F',$row);

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
  
  ?>
   <form action='<? echo self_url(); ?>' method='post' class='small_form'>
   <? echo self_post(); ?>
   <input type='hidden' name='action' value='save'>
      <fieldset style='width:470px;' class='small_form'>
      <legend><? echo ( $lieferanten_id ? 'Stammdaten Lieferant' : 'Neuer Lieferant' ); ?></legend>
      <? echo $msg . $problems; ?>
			  <table>
			   <tr>
				    <td><b>Lieferantenname</b></td>
						<td><input type='input' size='50' value="<? echo $name; ?>" name='name'></td>
				 </tr>
			   <tr>
				    <td><b>Adresse</b></td>
						<td><input type='input' size='50' value="<? echo $adresse; ?>" name='adresse'></td>
				 </tr>				 
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td><input type='input' size='50' value="<? echo $ansprechpartner; ?>" name='ansprechpartner'></td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td><input type='input' size='50' value="<? echo $telefon; ?>" name='telefon'></td>
				 </tr>
			   <tr>
				    <td><b>Faxnummer</b></td>
						<td><input type='input' size='50' value="<? echo $fax; ?>" name='fax'></td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td><input type='input' size='50' value="<? echo $mail; ?>" name='mail'></td>
				 </tr>				 
			   <tr>
				    <td><b>Liefertage</b></td>
						<td><input type='input' size='50' value="<? echo $liefertage; ?>" name='liefertage'></td>
				 </tr>				
			   <tr>
				    <td><b>Bestellmodalitäten</b></td>
						<td><input type='input' size='50' value="<? echo $bestellmodalitaeten; ?>" name='bestellmodalitaeten'></td>
				 </tr>				  
			   <tr>
				    <td><b>eigene Kundennummer</b></td>
						<td><input type='input' size='50' value="<? echo $kundennummer; ?>" name='kundennummer'></td>
				 </tr>
			   <tr>
				    <td><b>Internetseiten</b></td>
						<td><input type='input' size='50' value="<? echo $url; ?>" name='url'></td>
				 </tr>			 
				 <tr>
				    <td colspan='2' align='center'>
            <input type='submit' value='<? echo ( $lieferanten_id ? 'Ändern' : 'Einfügen'); ?>'>
            <?  if( $done ) { ?>
              &nbsp; <input value='Schließen' type='button' onClick='if(opener) opener.focus(); closeCurrentWindow();'>
            <? } ?>
				 </tr>
			</table>
    </fieldset>
	 </form>

</body>
</html>

