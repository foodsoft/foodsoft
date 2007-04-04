<h1>Gruppenverwaltung...</h1>

<?PHP
  include("code/zuordnen.php");
   // Übergebene Variablen einlesen...
   if (isset($HTTP_GET_VARS['gruppen_pwd'])) $gruppen_pwd = $HTTP_GET_VARS['gruppen_pwd'];       // Passwort für den Bereich
	 
	 // Passwort prüfen...
	 $pwd_ok = ($gruppen_pwd == $real_gruppen_pwd);
	 
	 
	 // ggf. Aktionen durchführen (z.B. Gruppe löschen...)
	  if ($pwd_ok && isset($HTTP_GET_VARS['action'])) {
		   $action = $HTTP_GET_VARS['action'];
			 
			 // Gruppe löschen..
			 if ($action == "delete") mysql_query("DELETE FROM bestellgruppen WHERE id=".mysql_escape_string($HTTP_GET_VARS['gruppen_id'])) or error(__LINE__,__FILE__,"Konnte Bestellgruppe nicht löschen.",mysql_error());
		}

	    // Wenn kein Passwort für die Bestellgruppen-Admin angegeben wurde, dann abfragen...
			if (!isset($gruppen_pwd) || !$pwd_ok) {
	?>
				 <form action="index.php">
				    <input type="hidden" name="area" value="gruppen">
				    <b>Bitte Finanzpasswort angeben:</b><br /><br />
						<input type="password" size="12" name="gruppen_pwd"><input type="submit" value="ok">						
				 </form>
	<?PHP
			} else	{
  ?>
	
	     <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können -->
			 <form action="index.php" name="reload_form">
			    <input type="hidden" name="area" value="gruppen">
					<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
					<input type="hidden" name="action" value="normal">
					<input type="hidden" name="gruppen_id">
			 </form>
	
				<table class="menu">
					<tr>
		          <td><input type="button" value="Neue Gruppe" class="bigbutton" onClick="window.open('windows/insertGroup.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>','insertGroup','width=350,height=320,left=200,top=100').focus()"></td>
				      <td valign="middle" class="smalfont">Eine neue Bestellgruppe hinzufügen...</td>
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
						 <th>Gruppenname</th>
						 <th>AnsprechpartnerIn</th>
						 <th>Mail</th>
						 <th>Telefon</th>
						 <th>Kontostand</th>
						 <th>Mitgliederzahl</th>
						 <th>Optionen</th>
					</tr>					 
	<?PHP
			$result = mysql_query("SELECT * FROM bestellgruppen ORDER BY name") or error(__LINE__,__FILE__,"Konnte Blockinhalt nicht prüfen.",mysql_error());
			while ($row = mysql_fetch_array($result))
		  {
	?>
	 
	        <tr>
						 <td><?PHP echo $row['name']; ?></td>
						 <td><?PHP echo $row['ansprechpartner']; ?></td>
						 <td><?PHP echo $row['email']; ?></td>
						 <td><?PHP echo $row['telefon']; ?></td>
						 <td align="right"><?PHP echo sprintf("%.02f",kontostand($row['id'])); ?></td>
						 <td><?PHP echo $row['mitgliederzahl']; ?></td>
						 <td>				 	
						 		<a class="png" href="javascript:window.open('windows/groupTransaktionMenu.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>&gruppen_id=<?PHP echo $row['id']; ?>&gruppen_name=<?PHP echo $row['name']; ?>','groupTransaktion','width=500,height=300,left=200,top=100').focus()"><img src="img/b_browse.png" border="0" titel="Kontotransaktionen" alt="Kontotransaktionen"/></a>
						 		<a class="png" href="javascript:window.open('windows/editGroup.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>&gruppen_id=<?PHP echo $row['id']; ?>','insertGroup','width=350,height=340,left=200,top=100').focus()"><img src="img/b_edit.png" border="0" alt="Gruppendaten ändern"  titel="Gruppendaten ändern"/></a>
								<a class="png" href="javascript:deleteGroup(<?PHP echo $row['id']; ?>);"><img src="img/b_drop.png" border="0" alt="Gruppe löschen" titel="Gruppe löschen"/></a>
						</td>
					</tr>
	 
	<?PHP
	     }
	?>
	
				</table>				

  <?PHP
			}    
	 ?>
