<?PHP
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 require_once('code/config.php');
	 require_once('code/err_functions.php');
	 require_once('code/connect_MySQL.php');
	 require_once('code/login.php');
   nur_fuer_dienst(5);
	 
	 // ggf. die neue Gruppe hinzufügen
	 if (isset($HTTP_GET_VARS['newGroup_name'])) {

		$newName            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_name']));
		$newAnsprechpartner = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_ansprechpartner']));
		$newMail            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_mail']));
		$newTelefon         = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newGroup_telefon']));
		$newMitgliederzahl  = $HTTP_GET_VARS['newGroup_mitgliederzahl'];
			
		$errStr = "";
		if ($newName == "") $errStr = "Die neue Bestellgruppe muß einen Name haben!";
		
		// Wenn keine Fehler, dann einfügen...
		if ($errStr == "") {
			
			// vorläufiges Passwort für die Bestellgruppe erzeugen...
			$pwd = strval(rand(1000,9999));
			
			mysql_query("INSERT INTO bestellgruppen 
				     (name, ansprechpartner, email, telefon, mitgliederzahl, passwort)
				     VALUES ('".mysql_escape_string($newName)."', '".mysql_escape_string($newAnsprechpartner)."', '".mysql_escape_string($newMail)."', '".mysql_escape_string($newTelefon)."', '".mysql_escape_string($newMitgliederzahl)."', '".crypt($pwd,35464)."')")
				     or error(__LINE__,__FILE__,"Konnte neue Benutzergruppe nicht einfügen.",mysql_error());
			
			$onload_str = "alert('Bitte das vorläufige Passwort für die Gruppe notieren! Passwort: ".$pwd."');
		       		       opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
		}
	 }
	 
?>

<html>
<head>
   <title>neue Bestellgruppe einfügen</title>
</head>
<body onload="<?PHP echo $onload_str; ?>">
   <h3>neue Bestelgruppe</h3>
	 <form action="insertGroup.php">
			<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
			<table border="2">
			   <tr>
				    <td><b>Gruppenname</b></td>
						<td><input type="input" size="20" name="newGroup_name"></td>
				 </tr>
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td><input type="input" size="20" name="newGroup_ansprechpartner"></td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td><input type="input" size="20" name="newGroup_mail"></td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td><input type="input" size="20" name="newGroup_telefon"></td>
				 </tr>
			   <tr>
				    <td><b>Mitgliederzahl</b></td>
						<td><input type="input" size="20" value="0" name="newGroup_mitgliederzahl"></td>
				 </tr>				 
			   <tr>
				    <td colspan="2" align="center"><input type="submit" value="Einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
				 </tr>
			</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
