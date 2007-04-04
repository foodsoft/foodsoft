<?PHP
   $lieferanten_pwd = $HTTP_GET_VARS['lieferanten_pwd'];
	 $lieferanten_id     = $HTTP_GET_VARS['lieferanten_id'];	 
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 if ($lieferanten_pwd != $real_lieferanten_pwd) exit();
	 
	 // ggf. die neuen Lieferanten hinzufügen
	 if (isset($HTTP_GET_VARS['newLieferant_name'])) {
	 
	    $newName                   = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_name']));
			$newAdresse               = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_adresse']));
			$newAnsprechpartner = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_ansprechpartner']));
			$newTelefon                = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_telefon']));
			$newFax                      = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_fax']));			
			$newMail                     = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_mail']));
			$newKundennummer  = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_kundennummer']));	
			$newLiefertage                    = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLiefertage']));
			$newMods                    = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newMods']));
			$newURL                    = str_replace("'", "", str_replace('"',"",$HTTP_GET_VARS['newLieferant_url']));
			
			$errStr = "";
			if ($newName == "") $errStr = "Der neue Lieferant muß einen Name haben!";
			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {
			
			   mysql_query("UPDATE lieferanten SET name='".mysql_escape_string($newName)."', adresse='".mysql_escape_string($newAdresse)."', ansprechpartner='".mysql_escape_string($newAnsprechpartner)."', telefon='".mysql_escape_string($newTelefon)."', fax='".mysql_escape_string($newFax)."', mail='".mysql_escape_string($newMail)."', url='".mysql_escape_string($newURL)."', kundennummer='".mysql_escape_string($newKundennummer)."', liefertage='".mysql_escape_string($newLiefertage)."', bestellmodalitaeten='".mysql_escape_string($newMods)."' WHERE id=".mysql_escape_string($lieferanten_id)) or error(__LINE__,__FILE__,"Konnte neuen Lieferanten nicht einfügen.",mysql_error());
				 $onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
			}
	 }
	 
	 // Lieferantendaten laden..
	 $result = mysql_query("SELECT * FROM lieferanten WHERE id=".mysql_escape_string($lieferanten_id)) or error(__LINE__,__FILE__,"Konnte LieferantInnen nich aus DB laden..",mysql_error());
	 $row = mysql_fetch_array($result);	 
	 
?>

<html>
<head>
   <title>Lieferanten bearbeiten</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">
   <h3>Lieferanten bearbeiten</h3>
	 <form action="editLieferant.php">
			<input type="hidden" name="lieferanten_pwd" value="<?PHP echo $lieferanten_pwd; ?>">
			<input type="hidden" name="lieferanten_id" value="<?PHP echo $lieferanten_id; ?>">
			<table border="2" style="width:500px;">
			   <tr>
				    <td><b>Lieferantenname</b></td>
						<td><input type="input" size="50" name="newLieferant_name" value="<?PHP echo $row['name']; ?>"></td>
				 </tr>
			   <tr>
				    <td><b>Adresse</b></td>
						<td><input type="input" size="50" name="newLieferant_adresse" value="<?PHP echo $row['adresse']; ?>"></td>
				 </tr>				 
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td><input type="input" size="50" name="newLieferant_ansprechpartner" value="<?PHP echo $row['ansprechpartner']; ?>"></td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td><input type="input" size="50" name="newLieferant_telefon"  value="<?PHP echo $row['telefon']; ?>"></td>
				 </tr>
			   <tr>
				    <td><b>Faxnummer</b></td>
						<td><input type="input" size="50" name="newLieferant_fax" value="<?PHP echo $row['fax']; ?>"></td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td><input type="input" size="50" name="newLieferant_mail" value="<?PHP echo $row['mail']; ?>"></td>
				 </tr>				 
			   <tr>
				    <td><b>Liefertage</b></td>
						<td><input type="input" size="50" name="newLiefertage" value="<?PHP echo $row['liefertage']; ?>"></td>
				 </tr>
			   <tr>
				    <td><b>Bestellmodalitäten</b></td>
						<td><input type="input" size="50" name="newMods" value="<?PHP echo $row['bestellmodalitaeten']; ?>"></td>
				 </tr>				 
			   <tr>
				    <td><b>eigene Kundennummer</b></td>
						<td><input type="input" size="50" name="newLieferant_kundennummer" value="<?PHP echo $row['kundennummer']; ?>"></td>
				 </tr>
			   <tr>
				    <td><b>Internetseiten</b></td>
						<td><input type="input" size="50" name="newLieferant_url"  value="<?PHP echo $row['url']; ?>"></td>
				 </tr>			 
			  <tr>
				    <td colspan="2" align="center"><input type="submit" value="Ändern"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
				 </tr>
			</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
