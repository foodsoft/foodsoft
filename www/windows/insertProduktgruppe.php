<?PHP
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
   require_once("code/config.php");
   require_once("$foodsoftpath/code/err_functions.php");
   require_once("$foodsoftpath/code/login.php");
   fail_if_readonly();
   nur_fuer_dienst_IV();
	 
	 // ggf. die neues produkt hinzufügen
	 if (isset($HTTP_GET_VARS['newProdukt_name'])) {
	 
	    $newName        	                            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_name']));
			
			
			$errStr = "";
			if ($newName == "") $errStr = "Das neue Produktgruppe muß einen Name haben!<br>";

			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {
			
			   mysql_query("INSERT INTO produktgruppen (name) VALUES ('".mysql_escape_string($newName)."');") or error(__LINE__,__FILE__,"Konnte neues Produktgruppe nicht einfügen.",mysql_error());
					
				 $onload_str = "opener.focus();  if (opener.document.forms['reload_form'].action) opener.document.forms['reload_form'].action.value='reload'; opener.document.forms['reload_form'].submit(); window.close();";
			}
	 };
	 
?>

<html>
<head>
   <title>neues Produktgruppe einfügen</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">
   

<h3>neue Produktgruppe einfügen</h3>
	 <form action="insertProduktgruppe.php">
		<input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
		<table class="menu" style="width:240px;">
		   <tr>
			    <td><b>Name</b></td>
					<td><input type="input" size="20" name="newProdukt_name"></td>
			 </tr>
			 <tr>
			    <td colspan="2" align="center"><input type="submit" value="Einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
			 </tr>
		</table>
	 </form>
	 <h4>existierende Produktgruppen</h4>
	 <ol style="list-style-type:decimal">
		<?PHP	 //andere produktgruppen auslesen...
		$sql = "SELECT name FROM produktgruppen";
		$res = mysql_query($sql);
		while ($row = mysql_fetch_array($res)) 
			{ 
				echo "<li>".$row['name']."</li>";
				} //end while
		?>
	 </ol>
	 	
</body>
</html>
