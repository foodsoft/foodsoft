<?PHP

   // DATEI: Hierwird die Verbindeung zur MySQL-Datenbank aufgebaut und geprüft


   if (!($db        = mysql_connect($db_server,$db_user,$db_pwd)) || !@MYSQL_SELECT_DB($db_name))
   {
	    echo "<html><body><h1>Datenbankfehler!</h1>Konnte keine Verbindung zur Datenbank herstellen... Bitte später nochmal versuchen.</body></html>";
			exit();
   }

?>
