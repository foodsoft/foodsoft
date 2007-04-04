<h1>Up/Download der Datenbank...</h1>


<?PHP
   // Übergebene Variablen einlesen...
   if (isset($HTTP_GET_VARS['lieferanten_pwd'])) $lieferanten_pwd = $HTTP_GET_VARS['lieferanten_pwd'];       // Passwort für den Bereich
	 
	 // Passwort prüfen...
	 $pwd_ok = ($lieferanten_pwd == $real_lieferanten_pwd);
	 
	 
	 // ggf. Aktionen durchführen (z.B. Lieferant löschen...)
	  if ($pwd_ok && isset($HTTP_GET_VARS['action'])) {
		   $action = $HTTP_GET_VARS['action'];
			 
			 // Lieferant löschen..
			 if ($action == "delete") mysql_query("DELETE FROM lieferanten WHERE id=".mysql_escape_string($HTTP_GET_VARS['lieferanten_id'])) or error(__LINE__,__FILE__,"Konnte Lieferanten nicht löschen.",mysql_error());
		}

	 
	    // Wenn kein Passwort für die Bestellgruppen-Admin angegeben wurde, dann abfragen...
			if (!isset($lieferanten_pwd) || !$pwd_ok) {
	?>
				 <form action="index.php">
				    <input type="hidden" name="area" value="updownload">
				    <b>Bitte Schinkepasswort angeben:</b><br /><br />
						<input type="password" size="12" name="lieferanten_pwd"><input type="submit" value="ok">						
				 </form>
	<?PHP
			} else	{
  ?>
	
	     <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können -->
			 <form action="index.php" name="reload_form">
			    <input type="hidden" name="area" value="lieferanten">
					<input type="hidden" name="lieferanten_pwd" value="<?PHP echo $lieferanten_pwd; ?>">
					<input type="hidden" name="action" value="normal">
					<input type="hidden" name="lieferanten_id">
			 </form>
			 
	<?PHP		 function upload() {
					    
					 }
	?>
				
				<table class="menu">
					<tr>
		          <td><form action="code/download.php"><input type="submit" value="Download" class="bigbutton"></form></td>
				      <td valign="middle" class="smalfont">Die Datenbank abspeichern...</td>
					 </tr> <tr>
		          <td><input type="button" value="Upload" class="bigbutton" onClick="window.open('windows/upload.php?produkte_pwd=<?PHP echo $produkte_pwd; ?>','upload','width=420,height=500,left=100,top=100').focus()"></td>
				      <td valign="middle" class="smalfont">Die Datenbank übertragen...</td>
					 </tr>
				</table>
				

  <?PHP
		//action=download;
		//dumping the file >> action: download
		//$backupfile = 'foodsoft.gz';
		//$command = "mysqldump --opt -h $dbhost -u $dbuser -p $dbpass $dbname | gzip > $backupfile";
		//mysql_query($command);
		
		//$command2 = "UPDATE gesamtbestellungen
		//			SET bestellende = now()
		//			WHERE bestellende > now() AND bestellende < DATE_ADD(now(), INTERVAL 7 DAY)";
		//mysql_query($command2);
		}    
	 ?>
