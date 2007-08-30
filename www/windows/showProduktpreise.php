<?PHP
	//error_reporting(E_ALL);
	 include('../code/config.php');
	 include('../code/err_functions.php');
  include('../code/connect_MySQL.php');
  require_once('../code/login.php');
   $produkt_id       = $HTTP_GET_VARS['produkt_id'];
      if( ! $angemeldet ) {
       exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
     } 

if(!nur_fuer_dienst(4)){exit();}
	 
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 // Verbindung zur Datenbank herstellen
	 
	 // zur Sicherheit das Passwort prüfen..
	 
	 // ggf. die neues produkt hinzufügen
	 if (isset($HTTP_GET_VARS['action'])) {
	    $action = $HTTP_GET_VARS['action'];
			
	    if ($action == "delete") {
			   sql_expire_produktpreis($HTTP_GET_VARS['id']);
			}
	    
	 }
	 
	 $produkt_row = getProdukt($produkt_id);	
	 
?>

<html>
<head>
   <title>Produktpreise</title>
   <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
   <script type="text/javascript">
   <!--	 
	 
	    function deletePreis(preisID)
			{
 	       if (confirm('Soll der Preis wirklich ab jetzt nicht mehr gültig sein?')) { 
				     document.forms['reload_form'].action.value="delete";
						document.forms['reload_form'].id.value=preisID;
						document.forms['reload_form'].submit();
				 }
			}	    
	 
	 -->
	 </script>
</head>
<body onload="<?PHP echo $onload_str; ?>">

 <form name="reload_form" action="showProduktpreise.php">
     <input type="hidden" name="produkt_id" value="<?PHP echo $produkt_id; ?>">
		 <input type="hidden" name="action">
		 <input type="hidden" name="id">
 </form>


<h3>Produktpreise</h3>

		<table width="640px" class="liste">
		   <tr>
			    <th>gültig von</th>
					<th>gültig bis</th>
					<th>gebinde</th>
					<th>preis(total)</th>
					<th>mwst</th>
					<th>pfand</th>
					<th>bestellnr.</th>
					<th>optionen</th>
			 </tr>
			 
			 <?PHP
          $result = sql_produktpreise2($produkt_id);
	        while ($row = mysql_fetch_array($result)) 
	        {
			?>
			      <tr>
			         <td><?PHP echo $row['zeitstart']; ?></td>
					     <td><?PHP echo $row['zeitende']; ?></td>
					     <td><?PHP echo $row['gebindegroesse']; ?></td>
					     <td><?PHP echo $row['preis']; ?></td>
					     <td><?PHP echo $row['mwst']; ?></td>
					     <td><?PHP echo $row['pfand']; ?></td>
					     <td><?PHP echo $row['bestellnummer']; ?></td>		
							 <td>
							    <input type="button" value="ändern" onClick="window.open('editProduktpreis.php?produkt_id=<?PHP echo $produkt_id; ?>&preis_id=<?PHP echo $row['id']; ?>&zeitstart=<?PHP echo $row['zeitstart']; ?>&zeitende=<?PHP echo $row['zeitende']; ?>&bestellnummer=<?PHP echo $row['bestellnummer']; ?>&gebindegroesse=<?PHP echo $row['gebindegroesse']; ?>&pfand=<?PHP echo $row['pfand']; ?>&mwst=<?PHP echo $row['mwst']; ?>&preis=<?PHP echo $row['preis']; ?>','editProduktpreis','width=400,height=350,left=100,top=100').focus()">
						<?PHP 
				   // Prüfe ob der Preis noch gültig ist
				   if (!is_expired_produktpreis($row['id'])) 
				   { echo "
					   		<br /><input type='button' value='abgelaufen' onClick='deletePreis(".$row['id'].");'>";
						?>
							 </td>
						</tr>
			
			<?PHP
					} //end if
			} //end while
			 ?>

			 <tr>
			    <td colspan="6" align="middle"><input type="button" value="Preis einfügen" onClick="window.open('insertProduktpreis.php?produkt_id=<?PHP echo $produkt_id; ?>','insertProduktpreis','width=400,height=350,left=100,top=100').focus()">
					<input type="button" value="Schließen" onClick="window.close();"></td>
			 </tr>
		</table>
</body>
</html>
