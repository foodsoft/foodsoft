<?PHP
   $lieferanten_pwd = $HTTP_GET_VARS['lieferanten_pwd'];
	 $lieferanten_id     = $HTTP_GET_VARS['lieferanten_id'];
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
	 
	 // zur Sicherheit das Passwort prüfen..
	 if ($lieferanten_pwd != $real_lieferanten_pwd) exit();
	 
	 $result = mysql_query("SELECT * FROM lieferanten WHERE id=".mysql_escape_string($lieferanten_id)) or error(__LINE__,__FILE__,"Konnte LieferantInnen nich aus DB laden..",mysql_error());
	 $row = mysql_fetch_array($result);
	 
?>

<html>
<head>
   <title>Details</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body>
   <h3>Details</h3>
			<table border="2" style="width:580px;">
			   <tr>
				    <td><b>Lieferantenname</b></td>
						<td><?PHP echo $row['name']; ?></td>
				 </tr>
			   <tr>
				    <td><b>Adresse</b></td>
						<td><?PHP echo $row['adresse']; ?></td>
				 </tr>				 
			   <tr>
				    <td><b>AnsprechpartnerIn</b></td>
						<td><?PHP echo $row['ansprechpartner']; ?></td>
				 </tr>				 
			   <tr>
				    <td><b>Telefonnummer</b></td>
						<td><?PHP echo $row['telefon']; ?></td>
				 </tr>
			   <tr>
				    <td><b>Faxnummer</b></td>
						<td><?PHP echo $row['fax']; ?></td>
				 </tr>				 
			   <tr>
				    <td><b>Email-Adresse</b></td>
						<td><?PHP echo $row['mail']; ?></td>
				 </tr>				 
			   <tr>
				    <td><b>Liefertage</b></td>
						<td><?PHP echo $row['liefertage']; ?></td>
				 </tr>				 
			   <tr>
				    <td><b>Bestellmodalitäten</b></td>
						<td><?PHP echo $row['bestellmodalitaeten']; ?></td>
				 </tr>
			   
			   <tr>
				    <td><b>eigene Kundennummer</b></td>
						<td><?PHP echo $row['kundennummer']; ?></td>
				 </tr>
			   <tr>
				    <td><b>Internetseiten</b></td>
						<td><?PHP echo $row['url']; ?></td>
			   	 </tr>	
			   <tr>
				    <td colspan="2" align="center"><input type="button" value="schließen" onClick="opener.focus(); window.close();"></td>
				 </tr>
			</table>
			<br>
			<br>
	 <?PHP if($row['sonstiges'] != "")echo "Sonstige Infos: <br>". $row['sonstiges']; ?>

	 </form>
</body>
</html>
