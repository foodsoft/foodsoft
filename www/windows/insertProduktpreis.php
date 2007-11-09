<?PHP
  assert( $angemeldet ) or exit();
  nur_fuer_dienst_IV();
  get_http_var('produkt_id','u');
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 
	 // ggf. die neues produkt hinzufügen
	 if (isset($HTTP_GET_VARS['startzeit_tag'])) {
	 
	    $startzeit_tag        	                            =  $HTTP_GET_VARS['startzeit_tag'];
			$startzeit_monat                                  = $HTTP_GET_VARS['startzeit_monat'];
			$startzeit_jahr                                     = $HTTP_GET_VARS['startzeit_jahr'];
			
			$startzeit = $startzeit_jahr."-".$startzeit_monat."-".$startzeit_tag;
			
	    $endzeit_tag        	                            =  $HTTP_GET_VARS['endzeit_tag'];
			$endzeit_monat                                 = $HTTP_GET_VARS['endzeit_monat'];
			$endzeit_jahr                                     = $HTTP_GET_VARS['endzeit_jahr'];
			
			if ($endzeit_tag == -1 || $endzeit_monat == -1 || $endzeit_jahr == -1)
			   $endzeit="";
			else
			   $endzeit = $endzeit_jahr."-".$endzeit_monat."-".$endzeit_tag;
			
			
			$gebindegroesse                                     = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['gebindegroesse']));
			$preis                                                     = str_replace(",",".",$HTTP_GET_VARS['preis']);
			$bestellnummer                                       = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['bestellnummer']));
			
			
			$errStr = "";
			if ($gebindegroesse == "") $errStr.= "Der neue Preis muß eine Gebindegröße haben!<br>";
			if ($preis == "") $errStr .= "Der neue Preis muß einen Preis haben!<br>";
			// if ($bestellnummer == "") $errStr .= "Der neue Preis muß eine Bestellnummer haben!<br>";
			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") {

			    if ($endzeit == "") 
					   mysql_query("INSERT INTO produktpreise (produkt_id, preis, zeitstart, zeitende, bestellnummer, gebindegroesse) VALUES ('".mysql_escape_string($produkt_id)."', '".mysql_escape_string($preis)."', '".mysql_escape_string($startzeit)."', NULL, '".mysql_escape_string($bestellnummer)."', '".mysql_escape_string($gebindegroesse)."');") or error(__LINE__,__FILE__,"Konnte Produkt nicht ändern.",mysql_error());
					else
					   mysql_query("INSERT INTO produktpreise (produkt_id, preis, zeitstart, zeitende, bestellnummer, gebindegroesse) VALUES ('".mysql_escape_string($produkt_id)."', '".mysql_escape_string($preis)."', '".mysql_escape_string($startzeit)."', '".mysql_escape_string($endzeit)."', '".mysql_escape_string($bestellnummer)."', '".mysql_escape_string($gebindegroesse)."');") or error(__LINE__,__FILE__,"Konnte Produkt nicht ändern.",mysql_error());

					$onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
			}
	 }
	 
   $result = mysql_query("SELECT * FROM produkte WHERE id=".mysql_escape_string($produkt_id)) or error(__LINE__,__FILE__,"Konnte Produkt nich aus DB laden..",mysql_error());
	 $produkt_row = mysql_fetch_array($result);	
	 
	 $startzeit_tag       = date("j");
	 $startzeit_monat  = date("n");
	 $startzeit_jahr      = date("Y");	 
	 
?>

<html>
<head>
   <title>neuer Produktpreis</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">


<h3>neuer Produktpreis</h3>
	 <form name="reload_form" action="<? echo self_url(); ?>">
		<? echo self_post(); ?>
		<input type="hidden" name="produkt_id" value="<?PHP echo $produkt_id; ?>">
		<input type="hidden" name="action" value="">
		
		<table class="menu" width="380px">
		   <tr>
			    <th>gültig von</th>
					<td>
					   <select name="startzeit_tag">
						  	 <?PHP for ($i=1; $i < 32; $i++) { if ($i == $startzeit_tag) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="startzeit_monat">
							  <?PHP for ($i=1; $i < 13; $i++) { if ($i == $startzeit_monat) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="startzeit_jahr">
							  <?PHP for ($i=2004; $i < 2011; $i++)  { if ($i == $startzeit_jahr) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>	
					</td>
			 </tr>
			 <tr>
					<th>gültig bis</th>
					<td>
					   <select name="endzeit_tag">
						    <option value="-1">-</option>
						  	 <?PHP for ($i=1; $i < 32; $i++) { if ($i == $endzeit_tag) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="endzeit_monat">
						    <option value="-1">-</option>
							  <?PHP for ($i=1; $i < 13; $i++) { if ($i == $endzeit_monat) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="endzeit_jahr">
						    <option value="-1">-</option>
							  <?PHP for ($i=2004; $i < 2011; $i++)  { if ($i == $endzeit_jahr) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>						
					</td>
			 </tr>
			 <tr>
					<th>gebindegrösse</th>
					<td><input type="text" name="gebindegroesse"></td>
			 </tr>
			 <tr>
					<th>preis</th>
					<td><input type="text" name="preis"></td>
			 </tr>
			 <tr>
					<th>bestellnummer</th>
					<td><input type="text" name="bestellnummer"></td>
			 </tr>
			 <tr>
			 <td></td>
			    <td><input type="submit" value="Preis einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
			 </tr>
		</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
