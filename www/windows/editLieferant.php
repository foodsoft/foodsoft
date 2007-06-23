<?PHP
  require_once('code/config.php');
  require_once('code/err_functions.php');
  require_once('code/connect_MySQL.php');
  require_once('code/login.php');
  nur_fuer_dienst(4,5);

  need_http_var('lieferanten_id');
  $problems = '';

  $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...

	 // ggf. die neuen Lieferanten hinzufügen
	 if( get_http_var('newLieferant_name') ) {
	 
	    $newName                   = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_name']));
			$newAdresse               = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_adresse']));
			$newAnsprechpartner = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_ansprechpartner']));
			$newTelefon                = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_telefon']));
			$newFax                      = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_fax']));			
			$newMail                     = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_mail']));
			$newKundennummer  = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_kundennummer']));	
			$newLiefertage                    = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLiefertage']));
			$newMods                    = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newMods']));
			$newURL                    = str_replace("'", "", str_replace('"',"",$HTTP_POST_VARS['newLieferant_url']));
			
			if ($newName == "")
        $problems = $problems . "<div class='warn'>Der neue Lieferant $newName/$newLieferant_name mu&szlig; einen Namen haben!</div>";
			
			// Wenn keine Fehler, dann aendern...
			if( ! $problems ) {
			  if( mysql_query(
          "UPDATE lieferanten
          SET name='".mysql_escape_string($newName)."'
            , adresse='".mysql_escape_string($newAdresse)."'
            , ansprechpartner='".mysql_escape_string($newAnsprechpartner)."'
            , telefon='".mysql_escape_string($newTelefon)."'
            , fax='".mysql_escape_string($newFax)."'
            , mail='".mysql_escape_string($newMail)."'
            , url='".mysql_escape_string($newURL)."'
            , kundennummer='".mysql_escape_string($newKundennummer)."'
            , liefertage='".mysql_escape_string($newLiefertage)."'
            , bestellmodalitaeten='".mysql_escape_string($newMods)."'
          WHERE id=".mysql_escape_string($lieferanten_id)
        ) ) {
          $msg = $msg . "<div class='ok'>&Auml;nderungen gespeichert</div>";
        } else {
          $problems = $problems . "<div class='warn'>Aenderung fehlgeschlagen: "
                         . mysql_error() . '</div>';
        }
			}
	 }
	 
	 // Lieferantendaten laden..
	 $result = mysql_query("SELECT * FROM lieferanten WHERE id=".mysql_escape_string($lieferanten_id))
      or $problems = $problems . "<div class='warn'>Konnte Lieferantendaten nicht laden</div>"
                     . mysql_error() . '</div>';
	 $row = mysql_fetch_array($result)
      or $problems = $problems . "<div class='warn'>Konnte Lieferantendaten nicht laden</div>"
                     . mysql_error() . '</div>';
	 
  $title = "Lieferantendaten edieren";
  $subtitle = "Lieferantendaten edieren";
  require_once('head.php');

  echo "
	  <form action='editLieferant.php' method='post' class='small_form'>
		  <input type='hidden' name='lieferanten_id' value='$lieferanten_id'>
      <fieldset style='width:510px;' class='small_form'>
      <legend>Stammdaten Lieferant</legend>
      $msg
      $problems
		  <table style='width:500px;'>
			   <tr>
				    <td><label>Lieferantenname:</label></td>
						<td><input type='input' size='50' name='newLieferant_name' value='{$row['name']}'></td>
				 </tr>
			   <tr>
				    <td><label>Adresse:</label></td>
						<td><input type='input' size='50' name='newLieferant_adresse' value='{$row['adresse']}'></td>
				 </tr>				 
			   <tr>
				    <td><label>AnsprechpartnerIn:</label></td>
						<td><input type='input' size='50' name='newLieferant_ansprechpartner' value='{$row['ansprechpartner']}'></td>
				 </tr>				 
			   <tr>
				    <td><label>Telefonnummer</label></td>
						<td><input type='input' size='50' name='newLieferant_telefon'  value='{$row['telefon']}'></td>
				 </tr>
			   <tr>
				    <td><label>Faxnummer</label></td>
						<td><input type='input' size='50' name='newLieferant_fax' value='{$row['fax']}'></td>
				 </tr>				 
			   <tr>
				    <td><label>Email-Adresse</label></td>
						<td><input type='input' size='50' name='newLieferant_mail' value='{$row['mail']}'></td>
				 </tr>				 
			   <tr>
				    <td><label>Liefertage</label></td>
						<td><input type='input' size='50' name='newLiefertage' value='{$row['liefertage']}'></td>
				 </tr>
			   <tr>
				    <td><label>Bestellmodalitäten</label></td>
						<td><input type='input' size='50' name='newMods' value='{$row['bestellmodalitaeten']}'></td>
				 </tr>				 
			   <tr>
				    <td><label>eigene Kundennummer</label></td>
						<td><input type='input' size='50' name='newLieferant_kundennummer' value='{$row['kundennummer']}'></td>
				 </tr>
			   <tr>
				    <td><label>Internetseiten</label></td>
						<td><input type='input' size='50' name='newLieferant_url'  value='{$row['url']}'></td>
				 </tr>			 
			  <tr>
				    <td colspan='2' align='center'><input type='submit' value='&Auml;ndern'></input></td>
				 </tr>
			</table>
      </fieldset>
	 </form>
  ";
?>
</body>
</html>
