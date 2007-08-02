<?PHP
   
		/* wichtige variablen:
		array: $liste[ ] enthält die produkt_id s für die bestellung
		
		*/   
   
   $produkte_pwd = $HTTP_GET_VARS['produkte_pwd'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 require_once('../code/config.php');
	 require_once('../code/err_functions.php');
	 require_once('../code/login.php');
   nur_fuer_dienst_IV();
	 
	 // zur Sicherheit das Passwort prüfen..
	 
	 
	 $startzeit_tag       = date("j");
	 $startzeit_monat  = date("n");
	 $startzeit_jahr      = date("Y");	
   $startzeit_stunde  = date("G");
	 $startzeit_minute  = date("i");
	 
	 $endzeit_tag       = date("j");
	 $endzeit_monat  = date("n");
	 $endzeit_jahr      = date("Y");
	 $endzeit_stunde  = "22";
	 
	 
	 // ggf. die neue bestellung einfügen 
	 if (isset($HTTP_GET_VARS['startzeit_tag'])) 
	 {
	 
	    	$startzeit_tag        	                            =  $HTTP_GET_VARS['startzeit_tag'];
			$startzeit_monat                                  = $HTTP_GET_VARS['startzeit_monat'];
			$startzeit_jahr                                     = $HTTP_GET_VARS['startzeit_jahr'];
			$startzeit_stunde                                 = $HTTP_GET_VARS['startzeit_stunde'];
			$startzeit_minute                                 = $HTTP_GET_VARS['startzeit_minute'];
			
			$startzeit = $startzeit_jahr."-".$startzeit_monat."-".$startzeit_tag." ".$startzeit_stunde.":".$startzeit_minute.":00";
			
	    	$endzeit_tag        	                            =  $HTTP_GET_VARS['endzeit_tag'];
			$endzeit_monat                                 = $HTTP_GET_VARS['endzeit_monat'];
			$endzeit_jahr                                     = $HTTP_GET_VARS['endzeit_jahr'];
			$endzeit_stunde                                 = $HTTP_GET_VARS['endzeit_stunde'];
			$endzeit_minute                                 = $HTTP_GET_VARS['endzeit_minute'];			
			
			$endzeit = $endzeit_jahr."-".$endzeit_monat."-".$endzeit_tag." ".$endzeit_stunde.":".$endzeit_minute.":00";
			
			
			$name                                     = str_replace("'", "", str_replace('"',"'",$HTTP_GET_VARS['name']));
			
			if (isset($HTTP_GET_VARS['bestelliste'])) $liste= $HTTP_GET_VARS['bestelliste'];

			
			
			$errStr = "";
			if ($name == "") $errStr.= "Die Bestellung muß einen Namen bekommen!<br>";
			if (!isset($liste)) $errStr.= "Die Bestellung enthält keine Produkte!<br>";

			
			// Wenn keine Fehler, dann einfügen...
			if ($errStr == "") 
			{

						$sql = "INSERT INTO gesamtbestellungen (name, bestellstart, bestellende) 
											VALUES ('".mysql_escape_string($name)."', 
																'".mysql_escape_string($startzeit)."', 
																'".mysql_escape_string($endzeit)."')";
						 mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Gesamtbestellung nicht aufnehmen.",mysql_error());
		
		          		$gesamtbestellung_id = mysql_insert_id();
		          		
		          	
												
							for ($i = 0; $i < count($liste); $i++) 
							{
								// preis, gebinde, und bestellnummer auslesen
								$sql = "SELECT id
												FROM produktpreise 
												WHERE produkt_id = ".mysql_escape_string($liste[$i])." 
												AND (zeitende >= NOW() OR ISNULL(zeitende))";							
								$result =  mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preise nich aus DB laden..",mysql_error());
								
								$produkt_row = mysql_fetch_array($result);  // alles in ein array schreiben
			
								  // jetzt die ganzen werte in die tabelle bestellvorschlaege schreiben
								$sql = "INSERT INTO bestellvorschlaege (produkt_id, gesamtbestellung_id, produktpreise_id)
													VALUES ('".mysql_escape_string($liste[$i])."', 
																		'".mysql_escape_string($gesamtbestellung_id)."',
																		'".mysql_escape_string($produkt_row['id'])."')";
								mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Gesamtbestellungs-Produktliste nicht aufnehmen.",mysql_error());
							
							} //end for - bestellvorschläge füllen
		
							$onload_str = "opener.focus(); opener.document.forms['reload_form'].submit(); window.close();";
							
				} //end if -wenn keine fehler ....
				
	 } //end if - ggf. neue bestellung einfügen ...
	 
	  
	 
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="ISO-8859-15">
   <title>neue Bestellung</title>
    <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">


<h3>neue Bestellung</h3>
	 <form name="reload_form" action="insertBestellung.php">
		<input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
		<input type="hidden" name="action" value="">
		
		<?PHP
		   if (isset($HTTP_GET_VARS['bestelliste'])) {
			    $liste = $HTTP_GET_VARS['bestelliste'];
					
					while (list($key, $value) = each($liste)) {
					   echo "<input type='hidden' name='bestelliste[]' value='".$value."'>\n";
					}
			 }
		?>
		
		<table class="menu" style="width:370px;">
			 <tr>
					<td><b>Name</b></td>
					<td><input type="text" name="name" size="35"></td>
			 </tr>		
		   <tr>
			    <td valign="top"><b>Startzeit</b></td>
					<td>
					
					 <table border=0>
					   <tr>
						   <td>Datum</td>
							 <td>
									 <select name="startzeit_tag">
											 <?PHP for ($i=1; $i < 32; $i++) { if ($i == $startzeit_tag) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
									 </select>
									 .
									 <select name="startzeit_monat">
											<?PHP for ($i=1; $i < 13; $i++) { if ($i == $startzeit_monat) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
									 </select>
									 .
									 <select name="startzeit_jahr">
											<?PHP for ($i=2004; $i < 2011; $i++)  { if ($i == $startzeit_jahr) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
									 </select>
								</td>
						 
						 </tr><tr>

                 <td>Zeit</td>
								 <td>
										 <select name="startzeit_stunde">
												 <?PHP for ($i=0; $i < 24; $i++) { if ($i == $startzeit_stunde) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
										 </select>
										 :
										 <select name="startzeit_minute">
												<?PHP for ($i=1; $i < 60; $i++) { if ($i == $startzeit_minute) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
										 </select>
							    </td>
							</tr>
						</table>	
						 
					</td>
			 </tr>
		   <tr>
			    <td valign="top"><b>Ende</b></td>
					<td>
					
					 <table border=0>
					   <tr>
						   <td>Datum</td>
							 <td>
									 <select name="endzeit_tag">
											 <?PHP for ($i=1; $i < 32; $i++) { if ($i == $endzeit_tag) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
									 </select>
									 .
									 <select name="endzeit_monat">
											<?PHP for ($i=1; $i < 13; $i++) { if ($i == $endzeit_monat) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
									 </select>
									 .
									 <select name="endzeit_jahr">
											<?PHP for ($i=2004; $i < 2011; $i++)  { if ($i == $endzeit_jahr) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
									 </select>
								</td>
						 
						 </tr><tr>

                 <td>Zeit</td>
								 <td>
										 <select name="endzeit_stunde">
												 <?PHP for ($i=0; $i < 24; $i++) { if ($i == $endzeit_stunde) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
										 </select>
										 :
										 <select name="endzeit_minute">
												<?PHP for ($i=0; $i < 60; $i++) { if ($i == $endzeit_minute) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".sprintf("%02d",$i)."</option>\n"; } ?>
										 </select>
							    </td>
							</tr>
						</table>	
						 
					</td>
			 </tr>
			 <tr>
			    <td colspan="2" align="middle"><input type="submit" value="Bestellung einfügen"><input type="button" value="Abbrechen" onClick="opener.focus(); window.close();"></td>
			 </tr>
		</table>
	 </form>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
