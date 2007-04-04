<?PHP

   $gruppen_pwd = $HTTP_GET_VARS['gruppen_pwd'];
	$gruppen_id     = $HTTP_GET_VARS['gruppen_id'];
 	$gruppen_name     = $HTTP_GET_VARS['gruppen_name'];

?>

<html>
<head>
   <title>Kontotransaktion</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
   <style type="text/css">
   <!--
	    .bigbutton                         {width:200px; height:30px;}
			.bigbutton:hover               {width:200px; height:30px; font-weight:bold;}
   -->
   </style>		 
</head>
<body>
	 <h3>Kontoverwaltung für Gruppe: <?PHP echo $gruppen_name; ?></h3>
   <table class="menu" style="width:430px">
	   <tr>
		    <td><input type="button" value="Transaktion" class="bigbutton" onClick="window.resizeTo(450,600); self.location.href='makeGroupTransaktion.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>&gruppen_id=<?PHP echo $gruppen_id; ?>';"></td>
				<td valign="middle" style="font-size:0.8em">eine Kontotransaktion durchführen (Einzahlen, Bestellun bezahlen, ...)</td>
		 </tr>
			<tr>
		    <td><input type="button" value="Kontoauszüge" class="bigbutton" onClick="window.resizeTo(450,600); self.location.href='showGroupTransaktions.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>&gruppen_id=<?PHP echo $gruppen_id; ?>';"></td>
				<td valign="middle" style="font-size:0.8em">Kontotransaktionen anzeigen...</td>
		 </tr>
			<tr>
		    <td><input type="button" value="Schließen" class="bigbutton" onClick="window.close();"></td>
				<td valign="middle" style="font-size:0.8em">dieses Fenster schließen...</td>
		 </tr>		 
	 </table>
</body>
</html>