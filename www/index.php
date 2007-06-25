<?php
  // Konfigurationsdatei einlesen
	require_once('code/config.php');
	
	// Funktionen zur Fehlerbehandlung laden
	require_once('code/err_functions.php');
	
	// Verbindung zur MySQL-Datenbank herstellen
	require_once('code/connect_MySQL.php');

  require_once('code/login.php');
	
	// egal ob get oder post verwendet wird...
	$HTTP_GET_VARS = array_merge($HTTP_GET_VARS, $HTTP_POST_VARS);

  get_http_var( 'area' );

		//head einfügen
		include ('head.php');

  if( ! $angemeldet ) {
    echo "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div></body></html>";
    exit();
  }
	 
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
			   
			else if ($area == 'bestellschein')
			   include('bestellschein.php');
			   //abgeschlossene bestellungen nach produkte sortiert				
				else if ($area == 'bestellt_produkte')
			   include('bestellt_produkte.php');
				else if ($area == 'bestellt_gruppe')
			   include('bestellt_gruppe.php');
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
			
      else if ($area == 'dienstkontrollblatt')
			   include('dienstkontrollblatt.php');				 
	?>

</body>
</html>
