<?php
  // Konfigurationsdatei einlesen
	include('code/config.php');
	
	// Funktionen zur Fehlerbehandlung laden
	include('code/err_functions.php');
	
	// Verbindung zur MySQL-Datenbank herstellen
	include('code/connect_MySQL.php');
	
	// egal ob get oder post verwendet wird...
	$HTTP_GET_VARS = array_merge($HTTP_GET_VARS, $HTTP_POST_VARS);

   // ggf. die area Variable einlesen, die festlegt in welchem Bereich man sich befindet
   if (isset($HTTP_GET_VARS['area'])) $area = $HTTP_GET_VARS['area'];


		//head einfügen
		include ('head.php');
	 
	    // Wenn kein Bereich gewählt wurde, dann Auswahlmenü präsentieren
	    if (!isset($area))
			   include('menu.php');
				 
			// zur Bestellgruppen-Administration verzweigen	 
			else if ($area == 'gruppen')
			   include('gruppen.php');
				 
			// zur LieferantInnen-Administration verzweigen	 
			else if ($area == 'lieferanten')
			   include('lieferanten.php');				 
				 
			// zum Datenbankmanagment verzweigen	 
			else if ($area == 'updownload')
			   include('updownload.php');				 
				 
			// zur Produkte-Administration verzweigen	 
			else if ($area == 'produkte')
			   include('produkte.php');					
				 
			// zur Produkte-Administration verzweigen	 
			else if ($area == 'bestellen')
			   include('bestellen.php');		
				 
			// zur den abgeschlossenen Bestellungen verzweigen	 
			else if ($area == 'bestellt')
			   include('bestellt.php');
			   
					   //abgeschlossene bestellungen nach produkte sortiert				
						else if ($area == 'bestellt_produkte')
					   include('bestellt_produkte.php');
						else if ($area == 'lieferschein')
					   include('lieferschein.php');

					   //2 x2 matrix				
						else if ($area == 'bestellt_matrix')
					   include('bestellt_matrix.php');
			   
			// interna  
			else if ($area == 'info')
			   include('info.php');				
			   
			// die Kontoverwaltung für die Gruppen ...  
			else if ($area == 'meinkonto')
			   include('meinkonto.php');				 
	?>
<!-- 	<br /><br />
	<hr style="border:1px dotted grey;"/>
	<div style="font-size: 0.9em; color: grey; ">Achtung Testumgebung. ALLE Passwörter sind leer....
	<br />Fragen an admin ätt fcschinke09.de</div>
	</div> -->
</body>
</html>
