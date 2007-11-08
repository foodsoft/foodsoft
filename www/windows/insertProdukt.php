<?PHP
   assert( $angemeldet ) or exit();

	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
   fail_if_readonly();
   nur_fuer_dienst_IV();
	 
   $errStr = "";

   // ggf. die neues produkt hinzufügen
	 if (isset($HTTP_GET_VARS['newProdukt_name'])) {
	 
	    $newName        	                            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_name']));
			$newLieferant                                 = $HTTP_GET_VARS['newProdukt_lieferant'];
			$newProduktgruppe                        = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_produktgruppe']));
			$newEinheit                                     = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_einheit']));
			$newNotiz                                       = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_notiz']));
			
			
			$newKategorien                            = $HTTP_GET_VARS['newProduk_kategorien'];
			
			
			if ($newName == "") $errStr = "Das neue Produkt muß einen Name haben!<br>";
			if ($newEinheit == "") $errStr = "Das neue Produkt muß einen Einheit haben!<br>";
			if ($newProduktgruppe == "") $errStr = "Das neue Produkt muß zu einer Produktgruppe gehören!<br>";
			if ($newLieferant == "")         $errStr = "Ein Lieferant muß angegeben werden!<br>";
			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {
			
			   mysql_query("INSERT INTO produkte (name, lieferanten_id, produktgruppen_id, einheit, notiz)
			   		VALUES ('".mysql_escape_string($newName)."', '"
						  .mysql_escape_string($newLieferant)."', '"
						  .mysql_escape_string($newProduktgruppe)."', '"
						  .mysql_escape_string($newEinheit)."', '"
						  .mysql_escape_string($newNotiz)."');")

					or error(__LINE__,__FILE__,"Konnte neues Produkt nicht einfügen.",mysql_error());

				$newProdukt_id = mysql_insert_id(); 

				
        for ($i=0; $i < count($newKategorien); $i++)
			      mysql_query("INSERT INTO kategoriezuordnung (kategorien_id, produkt_id) VALUES ('".mysql_escape_string($newKategorien[$i])."', '".mysql_escape_string($newProdukt_id)."')") or error(__LINE__,__FILE__,"Konnte Kategorie nicht einfügen.",mysql_error());


					
				 $onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close(); window.open('showProduktpreise.php?produkte_pwd=".$produkte_pwd."&produkt_id=".$newProdukt_id."','produkteDetails','width=650,height=600,left=200,top=100').focus();";
			}
	 };
	 

$title = "Neues Produkt eintragen";
$subtitle = "Neues Produkt eintragen";
$wikitopic = "foodsoft:datenbankabgleich";
require_once('head.php');

$kategorien= mysql_query("SELECT name,id FROM produktkategorien ORDER BY name") 
		      or error(__LINE__,__FILE__,"Konnte Kategorien nich aus DB laden..",mysql_error());
										 
?>

<form name="reload_form" action="insertProdukt.php">
    <input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
</form>


	 <form action="insertProdukt.php" class='small_form'>
    <fieldset style='width:390px;' class='small_form'>
    <legend>Neues Produkt</legend>
		<table class="menu" width="390px">
		   <tr>
			    <td><label>Name</label></td>
					<td><input type="input" size="40" name="newProdukt_name"></td>
			 </tr>
		   <tr>
			    <td><label>Produktgruppe  <a style="font-size:10pt; text-decoration:none;" href="javascript:window.open('insertProduktgruppe.php','produkteKategorie','width=250,height=350,left=200,top=100').focus()"> - neu</a></label></td>
					<td>
						<select name="newProdukt_produktgruppe">
               <?PHP
		              $result = mysql_query("SELECT name,id FROM produktgruppen ORDER BY name") or error(__LINE__,__FILE__,"Konnte Produktgruppen nich aus DB laden..",mysql_error());
										 
		              while ($row = mysql_fetch_array($result)) 
									   echo "<option value='".$row['id']."'>".$row['name']."</option>";
										 
								?>
	           </select>
					</td>
			 </tr>
		   <tr>
			    <td><label>Verteil-Einheit (z.B. 100 gr)</label></td>
					<td><input type="input" size="20" name="newProdukt_einheit"></td>
			 </tr>		 
		   <!-- TODO: liefereinheit ebenfalls setzen lassen! <tr>
			    <td><b>Liefer-Einheit (z.B. 200 gr)</b></td>
					<td><input type="input" size="20" name="newProdukt_liefereinheit"></td>
			 </tr>		 
       -->
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
			    <td valign="top"><b>Kategorie <a style="font-size:10pt; text-decoration:none;" href="javascript:window.open('insertProduktkategorie.php','produkteKategorie','width=250,height=350,left=200,top=100').focus()"> - neu</a></b></td>
					<td>
					
			    	<select name="newProduk_kategorien[]" size="5" multiple="multiple">
                <?PHP
								   while ($row = mysql_fetch_array($kategorien)){ 
								?>
								
										   <option value="<?PHP echo $row['id']; ?>"><?PHP echo $row['name']; ?></option>
											 
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
						 <textarea name="newProdukt_notiz"></textarea>
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
