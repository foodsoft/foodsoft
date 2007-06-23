<?PHP
	
   $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
  require_once('code/config.php');
  require_once('code/err_functions.php');
  require_once('code/connect_MySQL.php');
  require_once('code/login.php');
  nur_fuer_dienst(4,5);

  $msg = '';
  $problems = '';
  $done = FALSE;
	 
	 // ggf. die neuen Lieferanten hinzufügen
  if (isset($HTTP_POST_VARS['newLieferant_name'])) {
	    	$newName    		= str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_name']));
		$newAdresse		= str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_adresse']));
		$newAnsprechpartner 	= str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_ansprechpartner']));
		$newTelefon         	= str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_telefon']));
		$newFax                 = str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_fax']));			
		$newMail                = str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_mail']));
		$newLiefertage          = str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_liefertage']));
		$newMods                = str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_mods']));
		$newKundennummer  	= str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_kundennummer']));			
		$newURL                 = str_replace("'", "", str_replace('"',"'",$HTTP_POST_VARS['newLieferant_url']));
			
		if ($newName == "")
      $problems = $problems . "<div class='warn'>Der neue Lieferant muß einen Name haben!</div>";
			
		// Wenn keine Fehler, dann einfügen...
		if ($problems == "") {
      if( mysql_query(
        "INSERT INTO lieferanten
        ( name
        , adresse
        , ansprechpartner
        , telefon
        , fax
        , mail
        , liefertage
        , bestellmodalitaeten
        , url
        , kundennummer)
        VALUES (
          '".mysql_escape_string($newName)."'
          , '".mysql_escape_string($newAdresse)."'
          , '".mysql_escape_string($newAnsprechpartner)."'
          , '".mysql_escape_string($newTelefon)."'
          , '".mysql_escape_string($newFax)."'
          , '".mysql_escape_string($newMail)."'
          , '".mysql_escape_string($newLiefertage)."'
          , '".mysql_escape_string($newMods)."'
          , '".mysql_escape_string($newURL)."'
          , '".mysql_escape_string($newKundennummer)."')"
      ) ) {
        $msg = $msg . "<div class='ok'>Lieferant erfolgreich angelegt:</div>";
        $done = TRUE;
      } else {
        $problems = $problems . "<div class='warn'>Eintragen des Lieferanten fehlgeschlagen: "
                                 .  mysql_error() . "</div>";
      }
    }
  }
  
  $title = "Neuen Lieferanten eintragen";
  $subtitle = "Neuen Lieferanten eintragen";
  require_once('head.php');

  echo "
	 <form action='insertLieferant.php' method='post' class='small_form'>
      <fieldset style='width:470px;' class='small_form'>
      <legend>neuer Lieferant</legend>
        $msg
        $problems
			  <table>
			   <tr>
				    <td><b>Lieferantenname</b></td>
						<td><input type='input' size='50' value='$newName' name='newLieferant_name'></td>
				 </tr>
			   <tr>
				    <td><b>Adresse</b></td>
						<td><input type='input' size='50' value='$newAdresse' name='newLieferant_adresse'></td>
				 </tr>				 
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td><input type='input' size='50' value='$newAnsprechpartner' name='newLieferant_ansprechpartner'></td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td><input type='input' size='50' value='$newTelefon' name='newLieferant_telefon'></td>
				 </tr>
			   <tr>
				    <td><b>Faxnummer</b></td>
						<td><input type='input' size='50' value='$newFax' name='newLieferant_fax'></td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td><input type='input' size='50' value='$newMail' name='newLieferant_mail'></td>
				 </tr>				 
			   <tr>
				    <td><b>Liefertage</b></td>
						<td><input type='input' size='50' value='$newLiefertage' name='newLieferant_liefertage'></td>
				 </tr>				
			   <tr>
				    <td><b>Bestellmodalitäten</b></td>
						<td><input type='input' size='50' value='$newMods' name='newLieferant_mods'></td>
				 </tr>				  
			   <tr>
				    <td><b>eigene Kundennummer</b></td>
						<td><input type='input' size='50' value='$newKundennummer' name='newLieferant_kundennummer'></td>
				 </tr>
			   <tr>
				    <td><b>Internetseiten</b></td>
						<td><input type='input' size='50' value='$newURL' name='newLieferant_url'></td>
				 </tr>			 
				 <tr>
				    <td colspan='2' align='center'>
  ";
  if( ! $done ) {
    echo "<input type='submit' value='Einf&uuml;gen'></input>";
  } else {
    echo "<input value='OK' type='button' onClick='opener.focus(); window.close();'></td>";
  }
  echo "
				 </tr>
			</table>
    </fieldset>
	 </form>
  ";
?>

</body>
</html>
