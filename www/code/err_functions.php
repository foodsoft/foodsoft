<?PHP

   // DATEI: Diese Datei enthält Funktionen zur Fehlerbehandlung und zum Erstellen von Log-Files


	 function log_error($line,$file,$string,$error="",$stack) {
	 
	    global $logfile_errs, $log_win_format;
			
	                if($logfile_errs == NULL){
				echo "<p> <b> Warning</b> \$logfile_errs not set, not writing to logfile </p>";
			} else {
				$new_line = $log_win_format ? "\r\n" : "\n";
				$filename = $logfile_errs;
				$exists     = file_exists($filename);
				
				$fp = fopen($filename,"a");
				
				// Wenn Log-Datei neu erstellt wurde, dann erstmal "Kopzeile" schreiben
				if (!$exists) fputs($fp, "line file err_msg mysql_err_msg".$new_line);
				
				// Fehler rausschreiben und dabei Leerzeichen maskieren (" " => %20)
				fputs($fp, $line." ".str_replace(" ", "_", $file)." ".str_replace(" ", "_", $string)." ".str_replace(" ", "_", $error)." ".var_export($stack, TRUE).$new_line);
				
				fclose($fp);
			}
	 
	 }
	 
	 

   function error($line,$file,$string,$error="",$stack=""){
	   global $error_report_adress, $test_title;
		 
		 log_error($line,$file,$string,$error,$stack);
	 
      $fehler = "<div class='warn'><b>Fehler in Zeile ".$line." in ".$file."</b> ";
      $fehler .= "<br>" . $string . "<br>";
      if($error) $fehler .= "<b>MySQL-Error:</b> ". $error;
      if($stack) $fehler .= "<br><b>Stack:</b><br><code>".var_export($stack, TRUE)."</code>";
      $fehler .= "</div>";
			
			if ($error_report_adress != "") mail($error_report_adress,$test_title." - Error mail!!",$fehler);
			
      die($fehler);
   }
	 

?>
