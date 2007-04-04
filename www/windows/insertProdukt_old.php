<?PHP
   $produkte_pwd = $HTTP_GET_VARS['produkte_pwd'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 if ($produkte_pwd != $real_produkte_pwd) exit();
	 
	 // ggf. die neues produkt hinzufügen
	 if (isset($HTTP_GET_VARS['newProdukt_name'])) {
	 
	    $newName                   	         = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_name']));
			$newBestellnummer               = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_bestellnummer']));
			$newGebinde                         = (int) $HTTP_GET_VARS['newProdukt_gebinde'];
			$newPreis                               = (double) str_replace(',','.',$HTTP_GET_VARS['newProdukt_preis']);
			$newLieferant                        = $HTTP_GET_VARS['newProdukt_lieferant'];
			$newStatus                            = $HTTP_GET_VARS['newProdukt_status'];
			$newNotiz                               = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_notiz']));
			$newEinheit                            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_einheit']));
			
			// die Kategorie kodieren
			$newKategorie = 0;
			if (isset($HTTP_GET_VARS['newProdukt_kategorie0'])) $newKategorie += 1;
			if (isset($HTTP_GET_VARS['newProdukt_kategorie1'])) $newKategorie += 2;
			if (isset($HTTP_GET_VARS['newProdukt_kategorie2'])) $newKategorie += 4;
			
			
			$errStr = "";
			if ($newName == "") $errStr = "Das neue Produkt muß einen Name haben!<br>";
			if (!(isset($newGebinde) || $newGebinde < 0)) $errStr = "Die Gebindegröße ist ungültig!<br>";
			if (!(isset($newPreis) || $newPreis < 0)) $errStr = "Der Preis ist ungültig!<br>";
			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {
			
			   mysql_query("INSERT INTO produkte (name, bestellnummer, gebinde, preis, kategorie, lieferant, status, notiz, einheit) VALUES ('".mysql_escape_string($newName)."', '".mysql_escape_string($newBestellnummer)."', '".mysql_escape_string($newGebinde)."', '".mysql_escape_string($newPreis)."', '".mysql_escape_string($newKategorie)."', '".mysql_escape_string($newLieferant)."', '".mysql_escape_string($newStatus)."', '".mysql_escape_string($newNotiz)."', '".mysql_escape_string($newEinheit)."')") or error(__LINE__,__FILE__,"Konnte neuensn Produkt nicht einfügen.",mysql_error());
				 $onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
			}
	 }
	 
?>

<html>
<head>
   <title>neues Produkt einfügen</title>
</head>
<body onload="<?PHP echo $onload_str; ?>">
   <h3>neues Produkt einfügen</h3>
	 <form action="insertProdukt.php">
			<input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
			<table border="2">
			   <tr>
				    <td><b>Name</b></td>
						<td><input type="input" size="20" name="newProdukt_name"></td>
				 </tr>
			   <tr>
				    <td><b>Bestellnummer</b></td>
						<td><input type="input" size="20" name="newProdukt_bestellnummer"></td>
				 </tr>				 
			   <tr>
				    <td><b>Gebinde</b></td>
						<td><input type="input" size="20" name="newProdukt_gebinde"></td>
				 </tr>				
			   <tr>
				    <td><b>Einheit (z.B. 200 gr)</b></td>
						<td><input type="input" size="20" name="newProdukt_einheit"></td>
				 </tr>					 
			   <tr>
				    <td><b>Gebindepreis (incl. MwSt)</b></td>
						<td><input type="input" size="20" name="newProdukt_preis"></td>
				 </tr>
			   <tr>
				    <td valign="top"><b>Kategorie</b></td>
						<td>
						
						   <table border="0">
							    <tr>
						         <td><input type="checkbox" name="newProdukt_kategorie0" value="1">wöchentlich</td>
										 <td><input type="checkbox" name="newProdukt_kategorie1" value="1">monatlich</td>
									</tr><tr>
							       <td><input type="checkbox" name="newProdukt_kategorie2" value="1">pfand</td>
										 <td></td>
									</tr>
								</table>
								
						</td>
				 </tr>				 
			   <tr>
				    <td><b>Lieferant</b></td>
						<td>
						   <select name="newProdukt_lieferant">
							    <?PHP
									   $result = mysql_query("SELECT name,id FROM lieferanten ORDER BY name") or error(__LINE__,__FILE__,"Konnte LieferantInnen nich aus DB laden..",mysql_error());
										 
			               while ($row = mysql_fetch_array($result)) 
										    echo "<option value='".$row['id']."'>".$row['name']."</option>";
									?>
							 </select>
						</td>
				 </tr>				 
			   <tr>
				    <td><b>Status</b></td>
						<td>
							 <select name="newProdukt_status">
							    <option value="0">bestellbar</option>
									<option value="1">nicht bestellbar</option>
							 </select>
						</td>
				 </tr>
				 <tr>
				    <td><b>Notiz</b></td>
						<td>
							 <textarea name="newProdukt_notiz"></textarea>
						</td>
				 </tr>	 
				 <tr>
				    <td colspan="2" align="center"><input type="submit" value="Einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
				 </tr>
			</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
