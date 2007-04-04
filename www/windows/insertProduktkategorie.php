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
	 if (isset($HTTP_GET_VARS['newProduktkategorie_name'])) {
	 
	    $newName        	                            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProduktkategorie_name']));
			
			
			$errStr = "";
			if ($newName == "") $errStr = "Die neue Produktkategorie muß einen Name haben!<br>";

			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {
			
			   mysql_query("INSERT INTO produktkategorien (name) VALUES ('".mysql_escape_string($newName)."');") or error(__LINE__,__FILE__,"Konnte neues produktkategorien nicht einfügen.",mysql_error());
					
				 $onload_str = "opener.focus(); if (opener.document.forms['reload_form'].action) opener.document.forms['reload_form'].action.value='reload'; opener.document.forms['reload_form'].submit(); window.close();";
			}
	 };
	 
?>

<html>
<head>
   <title>neues Produktkategorie einfügen</title>
    <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">
   

<h3>neue Produktkategorie</h3>
	 <form action="insertProduktkategorie.php">
		<input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
		<table class="menu" style="width:240px;">
		   <tr>
			    <td><b>Name</b></td>
					<td><input type="input" size="20" name="newProduktkategorie_name"></td>
			 </tr>
			 <tr>
			    <td colspan="2" align="center"><input type="submit" value="Einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
			 </tr>
		</table>
	 </form>
	 <hr / style="border-style:dotted">
	 <h4>existierende Produktkategorien:</h4>
	 <ol style="list-style-type:decimal">
		<?PHP	 //andere produktgruppen auslesen...
		$sql = "SELECT name FROM produktkategorien";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res)) 
			{ 
				echo "<li>".$row['name']."</li>";
				} //end while
		?>
	 </ol>
</body>
</html>
