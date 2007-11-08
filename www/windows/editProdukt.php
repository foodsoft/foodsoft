<?PHP
   assert( $angemeldet ) or exit();

if(!nur_fuer_dienst(4)){exit();}

   need_http_var('produkt_id','u');
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 
	 
	 // ggf. die neues produkt hinzufügen
	 if (isset($HTTP_GET_VARS['newProdukt_name'])) {
	 
	    $newName        	                            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_name']));
			$newLieferant                                 = $HTTP_GET_VARS['newProdukt_lieferant'];
			$newProduktgruppe                        = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_produktgruppe']));
			$newEinheit                                     = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_einheit']));
			$newNotiz                                       = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_notiz']));
			
			
			$newKategorien                            = $HTTP_GET_VARS['newProduk_kategorien'];
			
			
			$errStr = "";
			if ($newName == "") $errStr = "Das neue Produkt muß einen Name haben!<br>";
			if ($newEinheit == "") $errStr = "Das neue Produkt muß einen Einheit haben!<br>";
			if ($newProduktgruppe == "") $errStr = "Das neue Produkt muß zu einer Produktgruppe gehören!<br>";
			if ($newLieferant == "")         $errStr = "Ein Lieferant muß angegeben werden!<br>";
			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {

			   mysql_query("UPDATE produkte SET name='".mysql_escape_string($newName)."', lieferanten_id='".mysql_escape_string($newLieferant)."', produktgruppen_id='".mysql_escape_string($newProduktgruppe)."', einheit='".mysql_escape_string($newEinheit)."', notiz='".mysql_escape_string($newNotiz)."' WHERE id=".mysql_escape_string($produkt_id)) or error(__LINE__,__FILE__,"Konnte Produkt nicht ändern.",mysql_error());

				
				mysql_query("DELETE FROM kategoriezuordnung WHERE produkt_id=".mysql_escape_string($produkt_id))  or error(__LINE__,__FILE__,"Konnte Kategorie nicht einfügen.",mysql_error());
        for ($i=0; $i < count($newKategorien); $i++)
				    mysql_query("INSERT INTO kategoriezuordnung (kategorien_id, produkt_id) VALUES ('".mysql_escape_string($newKategorien[$i])."', '".mysql_escape_string($produkt_id)."')") or error(__LINE__,__FILE__,"Konnte Kategorie nicht einfügen.",mysql_error());
			

					
				 if ($HTTP_GET_VARS['action'] == "reload") 
				    $onload_str = "opener.document.forms['reload_form'].submit();";
				 else
				    $onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
			}
	 }
	 
	$result = mysql_query("SELECT * FROM produkte WHERE id=".mysql_escape_string($produkt_id)) or error(__LINE__,__FILE__,"Konnte Produkt nich aus DB laden..",mysql_error());
	$produkt_row = mysql_fetch_array($result);	 
	 

$title = "Produkt bearbeiten";
$subtitle = "Produkt bearbeiten";
$wikitopic = "foodsoft:datenbankabgleich";
require_once('head.php');

$kategorien= mysql_query("SELECT name,id FROM produktkategorien ORDER BY name") 
		      or error(__LINE__,__FILE__,"Konnte Kategorien nich aus DB laden..",mysql_error());
										 
?>


	 <form name="reload_form" action="editProdukt.php">
		<input type="hidden" name="produkt_id" value="<?PHP echo $produkt_id; ?>">
		<input type="hidden" name="action" value="">
		<table class="menu" width="390px">
		   <tr>
			    <td><b>Name</b></td>
					<td><input type="input" size="30" name="newProdukt_name" value="<?PHP echo $produkt_row['name']; ?>"></td>
			 </tr>
		   <tr>
			    <td><b>Produktgruppe  <a style="font-size:10pt; text-decoration:none;" href="javascript:window.open('insertProduktgruppe.php?produkteKategorie','width=250,height=350,left=200,top=100').focus()"> - neu</a></b></td>
					<td>
						<select name="newProdukt_produktgruppe">
               <?PHP
		              $result = mysql_query("SELECT name,id FROM produktgruppen ORDER BY name") or error(__LINE__,__FILE__,"Konnte Produktgruppen nich aus DB laden..",mysql_error());
										 
		              while ($row = mysql_fetch_array($result))  {
									   if ($produkt_row['produktgruppen_id'] == $row['id']) $selectStr=" selected='selected' "; else $selectStr="";
									   echo "<option value='".$row['id']."' ".$selectStr.">".$row['name']."</option>";
									}
										 
								?>
	           </select>
					</td>
			 </tr>
		   <tr>
			    <td><b>Einheit (z.B. 200 gr)</b></td>
					<td><input type="input" size="20" name="newProdukt_einheit" value="<?PHP echo $produkt_row['einheit']; ?>"></td>
			 </tr>		 
		   <tr>
			    <td><b>Lieferant</b></td>
					<td>
						<select name="newProdukt_lieferant">
               <?PHP
		              $result = mysql_query("SELECT name,id FROM lieferanten ORDER BY name") or error(__LINE__,__FILE__,"Konnte LieferantInnen nich aus DB laden..",mysql_error());
										 
		              while ($row = mysql_fetch_array($result)) {
										 if ($produkt_row['lieferanten_id'] == $row['id']) $selectStr=" selected='selected' "; else $selectStr="";
									   echo "<option value='".$row['id']."' ".$selectStr.">".$row['name']."</option>";
									}
										 
								?>
	           </select>
					</td>
			 </tr>				
		   <tr>
			    <td valign="top"><b>Kategorie <a style="font-size:10pt; text-decoration:none;" href="javascript:window.open('insertProduktkategorie.php?produkteKategorie','width=250,height=350,left=200,top=100').focus()"> - neu</a></b></td>
					<td>
					
			    	<select name="newProduk_kategorien[]" size="5" multiple="multiple">
                <?PHP
								   while ($row = mysql_fetch_array($kategorien)) {
									    if (mysql_num_rows(mysql_query("SELECT * FROM kategoriezuordnung WHERE produkt_id = '".mysql_escape_string($produkt_id)."' AND kategorien_id = '".mysql_escape_string($row['id'])."';")) > 0)  $selectStr=" selected='selected' "; else $selectStr="";
								?>
								
										   <option value="<?PHP echo $row['id']; ?>" <?PHP echo $selectStr; ?> ><?PHP echo $row['name']; ?></option>
											 
						    <?PHP
						       } 
						    ?>
			    	</select>
							
					</td>
			 </tr>				 
		   <tr>
							</td>
			 </tr>				 
		   <tr>
			    <td valign="top"><b>Notiz</b></td>
					<td>
						 <textarea name="newProdukt_notiz"><?PHP echo $produkt_row['notiz']; ?></textarea>
					</td>
			 </tr>	 
			 <tr>
			 		<td></td>
			    <td><input type="submit" value="Einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
			 </tr>
		</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
