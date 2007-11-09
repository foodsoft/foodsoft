<?PHP

   // detailsProdukt.php
   //
   // ... skript is momentan unbenutzt und nicht gepflegt:
   //
   exit();
   
	 $produkt_id       = $HTTP_GET_VARS['produkt_id'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 
	// Produktdaten aus DB lesen...
	$result = mysql_query("SELECT * FROM produkte WHERE id=".mysql_escape_string($produkt_id)) or error(__LINE__,__FILE__,"Konnte Produkt nich aus DB laden..",mysql_error());
	$row = mysql_fetch_array($result);

?>

<html>
<head>
   <title>Details</title>
</head>
<body>
   <h3>Details</h3>
			<table border="2">
			   <tr>
				    <td><b>Name</b></td>
						<td><?PHP echo $row['name']; ?></td>
				 </tr>
			   <tr>
				    <td><b>Bestellnummer</b></td>
						<td><?PHP echo $row['bestellnummer']; ?></td>
				 </tr>				 
			   <tr>
				    <td><b>Gebinde</b></td>
						<td><?PHP echo $row['gebinde']; ?></td>
				 </tr>		 
			   <tr>
				    <td><b>Einheit</b></td>
						<td><?PHP echo $row['einheit']; ?></td>
				 </tr>				 
			   <tr>
				    <td><b>Gebindepreis (incl. MwSt)</b></td>
						<td><?PHP echo $row['preis']; ?></td>
				 </tr>
			   <tr>
				    <td valign="top"><b>Kategorie</b></td>
						<td>
						
						   <table border="0">
							    <tr>
						         <td><input type="checkbox" name="newProdukt_kategorie0" value="1" <?PHP if ($row['kategorie'] & 1) echo "checked"; ?>>wöchentlich</td>
										 <td><input type="checkbox" name="newProdukt_kategorie1" value="1" <?PHP if ($row['kategorie'] & 2) echo "checked"; ?>>monatlich</td>
									</tr><tr>
							       <td><input type="checkbox" name="newProdukt_kategorie2" value="1" <?PHP if ($row['kategorie'] & 4) echo "checked"; ?>>pfand</td>
										 <td></td>
									</tr>
								</table>
								
						</td>
				 </tr>				 
			   <tr>
				    <td><b>Lieferant</b></td>
						<td>
							    <?PHP
									   $result = mysql_query("SELECT name,id FROM lieferanten WHERE id=".mysql_escape_string($row['lieferant'])) or error(__LINE__,__FILE__,"Konnte LieferantInnen nich aus DB laden..",mysql_error());
                     $row_lieferant = mysql_fetch_array($result);
										 
										 echo $row_lieferant['name'];
									?>
						</td>
				 </tr>				 
			   <tr>
				    <td><b>Status</b></td>
						<td>
							 <?PHP 
							    if ($row['status'] == 0) echo "bestellbar";
									else if ($row['status'] == 1) echo "nicht bestellbar";
								?>
						</td>
				 </tr>	 
				 <tr>
				    <td><b>Notiz</b></td>
						<td>
							 <textarea name="newProdukt_notiz"><?PHP echo $row['notiz']; ?></textarea>
						</td>
				 </tr>	 				 
				 <tr>
				    <td colspan="2" align="center"><input type="button" value="schließen" onClick="opener.focus(); window.close();"></td>
				 </tr>
			</table>
</body>
</html>
