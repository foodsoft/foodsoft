<h1>Lieferantenübersicht...</h1>

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
				    <input type="hidden" name="area" value="lieferanten">
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
	
				<table class="menu">
				   <tr>
		          <td><input type="button" value="Neuen Lieferanten" class="bigbutton" onClick="window.open('windows/insertLieferant.php?lieferanten_pwd=<?PHP echo $lieferanten_pwd; ?>','insertLieferant','width=350,height=400,left=200,top=100').focus()"></td>
				      <td valign="middle" class="smalfont">Einen neuen Lieferanten hinzufügen...</td>
					 </tr><tr>
		          <td><input type="button" value="Reload" class="bigbutton" onClick="document.forms['reload_form'].submit();"></td>
				      <td valign="middle" class="smalfont">diese Seite aktualisieren...</td>
					 </tr><tr>
		          <td><input type="button" value="Beenden" class="bigbutton" onClick="self.location.href='index.php'"></td>
				      <td valign="middle" class="smalfont">diesen Bereich verlassen...</td>
					 </tr>
				</table>
				
				<br><br>
				
				<table class="liste">
	        <tr>
						 <th>Name</th>
						 <th>Telefon</th
						 <th>Fax</th
						 <th>Mail</th
						 <th>Webadresse</th
						 <th>Optionen</th
					</tr>					 
	<?PHP
			$result = mysql_query("SELECT * FROM lieferanten ORDER BY name") or error(__LINE__,__FILE__,"Konnte LieferantInnen nich aus DB laden..",mysql_error());
			while ($row = mysql_fetch_array($result))
		  {
	?>
	 
	        <tr>
						 <td><b><?PHP echo $row['name']; ?></b></td>
						 <td><?PHP echo $row['telefon']; ?></td>
						 <td><?PHP echo $row['fax']; ?></td>
						 <td><?PHP echo $row['mail']; ?></td>
						 <td><?PHP echo $row['url']; ?></td>
						 <td>
						 	<a class="png" href="javascript:window.open('windows/detailsLieferant.php?lieferanten_pwd=<?PHP echo $lieferanten_pwd; ?>&lieferanten_id=<?PHP echo $row['id']; ?>','lieferantenDetails','width=600,height=450,left=200,top=100').focus()"><img src="img/birne_rot.png" border="0" alt="Details zum Lieferanten" titel="Details zum Lieferanten" /></a>
						 	<a class="png" href="javascript:window.open('windows/editLieferant.php?lieferanten_pwd=<?PHP echo $lieferanten_pwd; ?>&lieferanten_id=<?PHP echo $row['id']; ?>','editDetails','width=560,height=450,left=200,top=100').focus()"><img src="img/b_edit.png" border="0" alt="Lieferanten editieren" titel="Lieferanten editieren" /></a>
						 	<a class="png" href="javascript:deleteLieferant(<?PHP echo $row['id']; ?>);"><img src="img/b_drop.png" border="0" alt="Lieferanten löschen" titel="Lieferanten löschen" /></a>
						    <!-- <input type="button" value="Details" onClick="">
								<input type="button" value="Editieren" onClick="">
								<input type="button" value="Löschen" onClick="deleteLieferant(<?PHP echo $row['id']; ?>);"> -->
						 </td>
					</tr>
	 
	<?PHP
	     }
	?>
	
				</table>				

  <?PHP
			}    
	 ?>
