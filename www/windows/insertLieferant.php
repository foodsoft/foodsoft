<?PHP
   $lieferanten_pwd = $HTTP_GET_VARS['lieferanten_pwd'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 if ($lieferanten_pwd != $real_lieferanten_pwd) exit();
	 
	 // ggf. die neuen Lieferanten hinzufügen
	 if (isset($HTTP_GET_VARS['newLieferant_name'])) {
	    	$newName    		= str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_name']));
		$newAdresse		= str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_adresse']));
		$newAnsprechpartner 	= str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_ansprechpartner']));
		$newTelefon         	= str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_telefon']));
		$newFax                 = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_fax']));			
		$newMail                = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_mail']));
		$newLiefertage          = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_liefertage']));
		$newMods                = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_mods']));
		$newKundennummer  	= str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_kundennummer']));			
		$newURL                 = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newLieferant_url']));
			
		$errStr = "";
		if ($newName == "") $errStr = "Der neue Lieferant muß einen Name haben!";
			
		// Wenn keine Fehler, dann einfügen...
		if ($errStr == "") {
			   mysql_query("INSERT INTO lieferanten (name, adresse, ansprechpartner, telefon, fax, mail, liefertage, bestellmodalitaeten, url, kundennummer) VALUES ('".mysql_escape_string($newName)."', '".mysql_escape_string($newAdresse)."', '".mysql_escape_string($newAnsprechpartner)."', '".mysql_escape_string($newTelefon)."', '".mysql_escape_string($newFax)."', '".mysql_escape_string($newMail)."', '".mysql_escape_string($newLiefertage)."', '".mysql_escape_string($newMods)."', '".mysql_escape_string($newURL)."', '".mysql_escape_string($newKundennummer)."')") or error(__LINE__,__FILE__,"Konnte neuen Lieferanten nicht einfügen.",mysql_error());
				 $onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
			}
	 }
	 
?>

<html>
<head>
   <title>neuen Lieferanten einfügen</title>
<link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">
   <h3>neuer Lieferant</h3>
	 <form action="insertLieferant.php">
			<input type="hidden" name="lieferanten_pwd" value="<?PHP echo $lieferanten_pwd; ?>">
			<table border="2">
			   <tr>
				    <td><b>Lieferantenname</b></td>
						<td><input type="input" size="20" name="newLieferant_name"></td>
				 </tr>
			   <tr>
				    <td><b>Adresse</b></td>
						<td><input type="input" size="20" name="newLieferant_adresse"></td>
				 </tr>				 
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td><input type="input" size="20" name="newLieferant_ansprechpartner"></td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td><input type="input" size="20" name="newLieferant_telefon"></td>
				 </tr>
			   <tr>
				    <td><b>Faxnummer</b></td>
						<td><input type="input" size="20" name="newLieferant_fax"></td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td><input type="input" size="20" name="newLieferant_mail"></td>
				 </tr>				 
			   <tr>
				    <td><b>Liefertage</b></td>
						<td><input type="input" size="20" name="newLieferant_liefertage"></td>
				 </tr>				
			   <tr>
				    <td><b>Bestellmodalitäten</b></td>
						<td><input type="input" size="20" name="newLieferant_mods"></td>
				 </tr>				  
			   <tr>
				    <td><b>eigene Kundennummer</b></td>
						<td><input type="input" size="20" value="" name="newLieferant_kundennummer"></td>
				 </tr>
			   <tr>
				    <td><b>Internetseiten</b></td>
						<td><input type="input" size="20" value="" name="newLieferant_url"></td>
				 </tr>			 
				 <tr>
				    <td colspan="2" align="center"><input type="submit" value="Einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
				 </tr>
			</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
