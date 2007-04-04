<?PHP
   $produkt_id       = $HTTP_GET_VARS['produkt_id'];
   $bestellt_pwd    = $HTTP_GET_VARS['bestellt_pwd'];
	 $bestellung_id   = $HTTP_GET_VARS['bestellung_id'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 if ($produkte_pwd != $real_bestellt_pwd) exit();
	 
   $result = mysql_query("SELECT * FROM produkte WHERE id=".$produkt_id) or error(__LINE__,__FILE__,"Konnte Produkt nich aus DB laden..",mysql_error());
	 $produkt_row = mysql_fetch_array($result);	
	 
?>

<html>
<head>
   <title>Produktbestellungen</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">

 <!-- 
 <form name="reload_form" action="showProduktpreise.php">
     <input type="hidden" name="produkt_id" value="<?PHP echo $produkt_id; ?>">
		 <input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
		 <input type="hidden" name="action">
		 <input type="hidden" name="id">
 </form> 
-->

<h3>Produktbestellungen</h3>
ACHTUNG: Momentan werden nur die Gesamtbestellwünsche angezeigt! <br />Nicht was angenommen wurde!<br /><br />
		<table  width="580px">
		   <tr>
			    <th colspan="5" >Produkt: <?PHP echo $produkt_row['name']; ?></th>
			 </tr>
		   <tr>
			    <th>Bestellgruppe</th>
					<th>Bestellmenge</th>
					<th>Toleranzmenge</th>
					<th>preis</th>
					<th>gesamt bestellt</th>
			 </tr>
			 
			 <?PHP 
          $result = mysql_query("SELECT *, gruppenbestellungen.id as grupbestid, bestellgruppen.name as bname  FROM gruppenbestellungen, bestellgruppen WHERE gesamtbestellung_id=".$bestellung_id." AND bestellgruppen.id=gruppenbestellungen.bestellguppen_id ORDER BY bestellguppen_id;") or error(__LINE__,__FILE__,"Konnte Gruppenbestellungen nich aus DB laden..",mysql_error());
	        while ($row = mysql_fetch_array($result)) {
					
					   $gesamt_menge = 0;
						 $gesamt_toleranz = 0;
						 
					   $result2 = mysql_query("SELECT * FROM bestellzuordnung WHERE gruppenbestellung_id=".$row['grupbestid']." AND produkt_id ='".$produkt_id."';") or error(__LINE__,__FILE__,"Konnte Produkt nich aus DB laden..",mysql_error());
						  while ($row2 = mysql_fetch_array($result2)) {
							   if ($row2['art'] == 0)
							      $gesamt_menge += $row2['menge'];
								 else
									 $gesamt_toleranz += $row2['menge'];
							}
			?>
			      <tr>
			         <td><?PHP echo $row['bname']; ?></td>
					     <td><?PHP echo $gesamt_menge; ?></td>
					     <td><?PHP echo $gesamt_toleranz; ?></td>
					     <td><?PHP //echo $row['preis']; ?></td>
					     <td><?PHP //echo $row['bestellnummer']; ?></td>		
						</tr>
			
			<?PHP
					}
			 ?>
		</table>
	 <b><font color="#FF0000"><?PHP echo $errStr ?></font></b>
</body>
</html>
