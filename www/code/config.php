<?PHP

   // DATEI: Diese Datei enth�t allegmeine Einstellungen. Sie kann angepa� werden!


   // Verbindungseinstellungen fr den MySQL-Server und die MySQL-Datenbank
	 // $db_server      MySQL-Server Hostname oder IP-Adresse (z.B. rdbms.strato.de)
	 // $db_user        MySQL Benutzername 
	 // $db_pwd        MySQL Passwort
	 // $db_name      Name der MySQL-Datenbank
   $db_server  =  "localhost";
   $db_name   = "foodsoft";
   $db_user     = "root";
   $db_pwd     = ""; 
   
//    $db_server  =  "localhost";
//    $db_name   = "nahrungskette";
//    $db_user     = "nahrungskette";
//    $db_pwd     = "leckerpotsdam"; 
// 	 
	 
	 // Mailadresse an die Auftretende Fehler gemeldet werden. Wenn leer (="") dann keine Warnmails.
	 $error_report_adress = "";
	 
	 // Passwort fr die Gruppenadministration
	 $real_gruppen_pwd = "";
	 
	 // Passwort fr die LieferantInnenadministartion
	 $real_lieferanten_pwd = "";	 
	 
	 // Passwort fr die Produktadministration
	 $real_produkte_pwd = "";

	 
	 // Passwort fr das ansehen der abgeschlossenen Gesamtbestellungen (Bestellgruppe)
	 $real_bestellt_pwd = "";

	 // Passwort fr den Internen Bereich
	 $real_info_pwd = "";		 	 
	 
	 // Legt fest, Zeilenumbrche in Logfiles fr Windows genutzt werden.
	 $log_win_format = true;	 
	 
	 // Dateiname und Pfad der Fehler-Log-Datei
	 $logfile_errs            = "/tmp/foodsoft_err.txt";
	 
	 
	 
	 //____________________________________ //
	 // Ab hier programminterne Definitionen					 //
	 //____________________________________ //
	 
	 // Klartext des Statusfeldes 'status' der Bestellgruppentabelle 'bestellgruppen'
	 $gruppenstatusString[0] = 'aktiv';
	 $gruppenstatusString[1] = 'gesperrt';
	 
	 $produktstatus[0] = 'bestellbar';
	 $produktstatus[1] = 'nicht bestellbar';
	 
	 
?>
