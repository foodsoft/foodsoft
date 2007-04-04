<?PHP
   $produkt_id       = $HTTP_GET_VARS['produkt_id'];
   $produkte_pwd = $HTTP_GET_VARS['produkte_pwd'];
   $preis_id = $HTTP_GET_VARS['preis_id'];
	$bestellnummer = $HTTP_GET_VARS['bestellnummer'];   
	$gebindegroesse = $HTTP_GET_VARS['gebindegroesse'];
	$preis = $HTTP_GET_VARS['preis'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 if ($produkte_pwd != $real_produkte_pwd) exit();
	 
	 //ggf,  preisinfos auslesen... wenn der edit button gedrückt wurde
		if (isset($HTTP_GET_VARS['zeitstart'])) 
	 {	 
		 $zeitstart	= $HTTP_GET_VARS['zeitstart'];
				
							//startzeit string aufteilen...	 	
		 	$zeitstart_array = explode(" ", $zeitstart);
			$datum = 	$zeitstart_array['0'];
			$uhrzeit = 	$zeitstart_array['1'];
			
				//$datum aufteilen
			$datum_array 			= explode("-", $datum);
			$startzeit_jahr 			= $datum_array['0'];
			$startzeit_monat		= $datum_array['1'];
			$startzeit_tag 			= $datum_array['2'];
	 		
	 			//$uhzeit aufteilen
			$uhrzeit_array 			= explode("-", $uhrzeit);
			$startzeit_std 			= $uhrzeit_array['0'];
			$startzeit_min		= $uhrzeit_array['1'];
			$startzeit_sek 			= $uhrzeit_array['2'];
	 		
	 		
	 		
	 						//jetzt den endzeitstring
	 		$endzeit	= $HTTP_GET_VARS['zeitende'];
				
				//endezeit string aufteilen...	 	
		 	$zeitend_array = explode(" ", $zeitende);
			$datum = 	$zeitend_array['0'];
			$uhrzeit = 	$zeitend_array['1'];
			
				//$datum aufteilen
			$datum_array 			= explode("-", $datum);
			$endzeit_jahr 			= $datum_array['0'];
			$endzeit_monat		= $datum_array['1'];
			$endzeit_tag 			= $datum_array['2'];
	 			
	 			//$uhzeit aufteilen
			$uhrzeit_array 			= explode("-", $uhrzeit);
			$endzeit_std 			= $uhrzeit_array['0'];
			$endzeit_min		= $uhrzeit_array['1'];
			$endzeit_sek 			= $uhrzeit_array['2'];
			
			 	
	} // end if (isset($HTTP_GET_VARS['zeitstart'])) 
	
		 // ggf. den preis ändern ... wenn der änderungen speichern button gedrückt wurde
		if (isset($HTTP_GET_VARS['startzeit_tag'])) 
	 {
			 $startzeit_tag        	                            =  $HTTP_GET_VARS['startzeit_tag'];
			$startzeit_monat                                  = $HTTP_GET_VARS['startzeit_monat'];
			$startzeit_jahr                                     = $HTTP_GET_VARS['startzeit_jahr'];
			
			$startzeit = $startzeit_jahr."-".$startzeit_monat."-".$startzeit_tag;
			
	    $endzeit_tag        	                            =  $HTTP_GET_VARS['endzeit_tag'];
			$endzeit_monat                                 = $HTTP_GET_VARS['endzeit_monat'];
			$endzeit_jahr                                     = $HTTP_GET_VARS['endzeit_jahr'];
			
			$endzeit = $endzeit_jahr."-".$endzeit_monat."-".$endzeit_tag;
			
			$gebindegroesse                                     = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['gebindegroesse']));
			$preis                                                     = str_replace(",",".",$HTTP_GET_VARS['preis']);
			$bestellnummer                                       = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['bestellnummer']));

			$errStr = "";
			if ($gebindegroesse == "") $errStr.= "Der neue Preis muß eine Gebindegröße haben!<br>";
			if ($preis == "") $errStr .= "Der neue Preis muß einen Preis haben!<br>";
			// if ($bestellnummer == "") $errStr .= "Der neue Preis muß eine Bestellnummer haben!<br>";
		

				// Wenn keine Fehler, dann einfügen...
				if ($errStr == "") 
						{
															
							$sql = "UPDATE produktpreise
												SET preis ='".mysql_escape_string($preis)."', 
												bestellnummer = '".mysql_escape_string($bestellnummer)."',
												gebindegroesse ='".mysql_escape_string($gebindegroesse)."'
												WHERE id = '".$preis_id."'" ;
												
							mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produkt nicht ändern.",mysql_error());
							
							$onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
							
						 } 
			} //end if (isset($HTTP_GET_VARS['startzeit_tag'])) 

?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-15">
   <title>Produktpreis ändern</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">

<small>(bisher kann mensch nur preis, bestellnummer <br />und gebindegröße ändern)</small>

<h3>Produktpreis ändern</h3>
	 <form name="reload_form" action="editProduktpreis.php">
		<input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
		<input type="hidden" name="produkt_id" value="<?PHP echo $produkt_id; ?>">
		<input type="hidden" name="preis_id" value="<?PHP echo $preis_id; ?>">		
		<input type="hidden" name="action" value="">
		
		<table class="menu" width="380px">
		   <tr>
			    <th>gültig von</th>
					<td>
					   <select name="startzeit_tag">
						  	 <?PHP for ($i=1; $i < 32; $i++) { if ($i == $startzeit_tag) $select_str="selected=\"selected\""; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="startzeit_monat">
							  <?PHP for ($i=1; $i < 13; $i++) { if ($i == $startzeit_monat) $select_str="selected=\"selected\""; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="startzeit_jahr">
							  <?PHP for ($i=2006; $i < 2011; $i++)  { if ($i == $startzeit_jahr) $select_str="selected=\"selected\""; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>	
					</td>
			 </tr>
			 <tr>
					<th>gültig bis</th>
					<td>
					   <select name="endzeit_tag">
						    <option value="-1">-</option>
						  	 <?PHP for ($i=1; $i < 32; $i++) { if ($i == $endzeit_tag) $select_str="selected=\"selected\""; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="endzeit_monat">
						    <option value="-1">-</option>
							  <?PHP for ($i=1; $i < 13; $i++) { if ($i == $endzeit_monat) $select_str="selected=\"selected\""; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>
					   .
					   <select name="endzeit_jahr">
						    <option value="-1">-</option>
							  <?PHP for ($i=2004; $i < 2011; $i++)  { if ($i == $endzeit_jahr) $select_str="selected=\"selected\""; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
					   </select>						
					</td>
			 </tr>
			 <tr>
					<th>Gebindegrösse</th>
					<td><input type="text" name="gebindegroesse" value="<?php echo $gebindegroesse ?>"></td>
			 </tr>
			 <tr>
					<th>Preis</th>
					<td><input type="text" name="preis" value="<?php echo $preis ?>"></td>
			 </tr>
			 <tr>
					<th>Bestellnummer</th>
					<td><input type="text" name="bestellnummer" value="<?php echo $bestellnummer ?>"></td>
			 </tr>
			 <tr>
			 <td></td>
			    <td>
			    	<input type="submit" value="Preis ändern">
			    	<input type="button" value="Abbrechen" onClick="opener.focus(); window.close();">
			    </td>
			 </tr>
		</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
