<?PHP

   // DATEI: Diese Datei enthält Funktionen zur Fehlerbehandlung und zum Erstellen von Log-Files


	 function log_error($line,$file,$string,$error="") {
	 
	    global $logfile_errs, $log_win_format;
			
			$new_line = $log_win_format ? "\r\n" : "\n";
			$filename = $logfile_errs;
			$exists     = file_exists($filename);
			
			$fp = fopen($filename,"a");
			
			// Wenn Log-Datei neu erstellt wurde, dann erstmal "Kopzeile" schreiben
			if (!$exists) fputs($fp, "line file err_msg mysql_err_msg".$new_line);
			
			// Fehler rausschreiben und dabei Leerzeichen maskieren (" " => %20)
			fputs($fp, $line." ".str_replace(" ", "_", $file)." ".str_replace(" ", "_", $string)." ".str_replace(" ", "_", $error).$new_line);
			
			fclose($fp);
	 
	 }
	 
	 

   function error($line,$file,$string,$error=""){
	   global $error_report_adress, $test_title;
		 
		 log_error($line,$file,$string,$error);
	 
      $fehler = "<b>Fehler in Zeile ".$line." in ".$file."</b> ";
      $fehler .= "<br>" . $string . "<br>";
      if($error) $fehler .= "<b>MySQL-Error:</b> ". $error;
			
			if ($error_report_adress != "") mail($error_report_adress,$test_title." - Error mail!!",$fehler);
			
      die($fehler);
   }
	 

?>