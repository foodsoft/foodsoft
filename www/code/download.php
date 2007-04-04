<? 
error_reporting(E_ALL);
	 include('../code/config.php');
	 include('../code/err_functions.php');
	 include('../code/connect_MySQL.php');
		// send the necessary headers.
		// i found that these work well.
		header("Content-Type: application/gzip");
		header("Content-Disposition: filename=databaseDump.sql.gz");


		$command = "mysqldump --opt 
		    -h ".$db_server.
		    " -u ".$db_user.
		    " -p".$db_pwd." ".$db_name;
		$command = escapeshellcmd($command);
		$command = $command." | gzip ";
		passthru($command, $return);
		if($return!=0){
			echo "Error, return value of: ".$return."<br>";
		} else {
	
			$command2 = "UPDATE gesamtbestellungen
					SET bestellende = now()
					WHERE bestellende > now() AND bestellende < DATE_ADD(now(), INTERVAL 7 DAY)";
			mysql_query($command2) or error(__LINE__,__FILE__,"Problem beim Verändern des Datums",mysql_error());
			//echo $command2."<br>\n";
			echo "<h3> Daten unter ".$value." gespeichert</h3>";

			echo $code_comment3;
		}
	 
	?>
