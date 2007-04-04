<?PHP

   // wichtige Variablen einlesen...
   $gruppen_pwd    = $HTTP_GET_VARS['gruppen_pwd'];
	 $gruppen_id 	     = $HTTP_GET_VARS['gruppen_id'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 if ($gruppen_pwd != $real_gruppen_pwd) exit();
	 
	 // ggf. die Gruppendaten ändern
	 if (isset($HTTP_GET_VARS['newGroup_name'])) {
	

		$newName            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_name']));
		$newAnsprechpartner = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_ansprechpartner']));
		$newMail            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_mail']));
		$newTelefon         = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_telefon']));
		$newMitgliederzahl  = $HTTP_GET_VARS['newGroup_mitgliederzahl'];
			
		$errStr = "";
		if ($newName == "") $errStr = "Die Bestellgruppe muß einen Name haben!";
			
		// Wenn keine Fehler, dann ändern...
		if ($errStr == "") {
			   mysql_query("UPDATE bestellgruppen SET name='".mysql_escape_string($newName)."', ansprechpartner='".mysql_escape_string($newAnsprechpartner)."', email='".mysql_escape_string($newMail)."', telefon='".mysql_escape_string($newTelefon)."', mitgliederzahl='".mysql_escape_string($newMitgliederzahl)."' WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Benutzergruppe nicht ändern.",mysql_error());
				 $onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
			}
	 }
	 //ggf. Aktionen durchführen
	 else if (isset($HTTP_GET_VARS['action'])) {
	    $action = $HTTP_GET_VARS['action'];
			
			// neues Passwort anlegen...
			if ($action == "new_pwd") {
			   $pwd = strval(rand(1000,9999));
				 mysql_query("UPDATE bestellgruppen SET passwort='".mysql_escape_string(crypt($pwd,35464))."' WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte das Gruppenpasswort nicht zurücksetzen.",mysql_error());
				 $onload_str = "alert('Das neu angelegte Gruppenpasswort: ".$pwd."');";
			}
	 }


	 $result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Gruppendaten nicht lesen.",mysql_error());
	 $row = mysql_fetch_array($result);
	 
	 
?>

<html>
<head>
   <title>Bestellgruppe editieren</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">
   <h3>Bestellgruppe editieren</h3>
	 <form action="editGroup.php">
			<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
			<input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
			<table style="width:340px;" class="menu">
			   <tr>
				    <td><b>Gruppenname</b></td>
						<td><input type="input" size="20" name="newGroup_name" value="<?PHP echo $row['name']; ?>"></td>
				 </tr>
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td><input type="input" size="20" name="newGroup_ansprechpartner" value="<?PHP echo $row['ansprechpartner']; ?>"></td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td><input type="input" size="20" name="newGroup_mail" value="<?PHP echo $row['email']; ?>"></td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td><input type="input" size="20" name="newGroup_telefon" value="<?PHP echo $row['telefon']; ?>"></td>
				 </tr>
			   <tr>
				    <td><b>Mitgliederzahl</b></td>
						<td><input type="input" size="20" name="newGroup_mitgliederzahl" value="<?PHP echo $row['mitgliederzahl']; ?>"></td>
				 </tr>				 
				 <tr>
				    <td colspan="2" align="center"><input type="submit" value="Ändern"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
				 </tr>
			</table>
	 </form>
	 
	 <form action="editGroup.php" name="optionen">
			<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
			<input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">	 
			<input type="hidden" name="action">
			
    <table style="width:340px;" class="menu">
		   <tr>
			    <td colspan="2"><b>Optionen</b></td>
			 </tr>
			 <tr>
			    <td><input type="button" value="neues Passwort" onClick="document.forms['optionen'].action.value='new_pwd'; document.forms['optionen'].submit();"></td>
					<td class="smalfont">Gruppenpasswort zurücksetzen...</td>
			 </tr>
	  </table>
		
	</form>
</body>
</html>
