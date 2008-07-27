<?PHP
  assert( $angemeldet ) or exit();
	 
   fail_if_readonly();
   nur_fuer_dienst_IV();
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	
   // ggf. die neues produkt hinzufügen
	 if (isset($HTTP_GET_VARS['newProdukt_name'])) {
	 
	    $newName        	                            = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['newProdukt_name']));
			
			
			$errStr = "";
			if ($newName == "") $errStr = "Das neue Produktgruppe muß einen Name haben!<br>";

			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {
         sql_insert( 'produktgruppen', array( 'name' => $newName ) );
				 $onload_str = "opener.focus();  if (opener.document.forms['reload_form'].action) opener.document.forms['reload_form'].action.value='reload'; opener.document.forms['reload_form'].submit(); closeCurrentWindow();";
			}
	 };
	 
?>

<h3>neue Produktgruppe einf&uuml;gen</h3>
	 <form action="<? echo self_url(); ?>" method='post'>
		<table class="menu" style="width:240px;">
		   <tr>
			    <td><b>Name</b></td>
					<td><input type="input" size="20" name="newProdukt_name"></td>
			 </tr>
			 <tr>
			    <td colspan="2" align="center"><input type="submit" value="Einf&uuml;gen"><input type="button" value="Abbrechen" onClick="if(opener) opener.focus(); closeCurrentWindow();"></td>
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
