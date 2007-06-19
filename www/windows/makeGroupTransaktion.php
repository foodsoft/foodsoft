<?PHP
   include('../code/zuordnen.php');
   // wichtige Variablen einlesen...
   // $gruppen_pwd    = $HTTP_GET_VARS['gruppen_pwd'];
   $gruppen_pwd = 'obsolet';
	 $gruppen_id 	     = $HTTP_GET_VARS['gruppen_id'];
	 
	 // Variablen initialisieren
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 $transaktionsart       = "0";
	 $eingang_tag            = "1";
   $eingang_monat       =  "1";
	 $eingang_jahr           = "2006";
	 $auszug_nr               = "";
	 $summe                     = "";
	 $notiz                        = "";
	 
	 // Verbindung zur Datenbank herstellen
	 require_once('../code/config.php');
	 require_once('../code/err_functions.php');
	 require_once('../code/connect_MySQL.php');

   require_once('code/login.php');
   nur_fuer_dienst_IV();
	 
	 // zur Sicherheit das Passwort prüfen..
	 // if ($gruppen_pwd != $real_gruppen_pwd) exit();
	 
	 $eingang_tag       = date("j");
	 $eingang_monat  = date("n");
	 $eingang_jahr      = date("Y");
	 
	 // ggf. die Gruppendaten ändern
	 if (isset($HTTP_GET_VARS['transaktionsart'])) {
	 
	    $errStr = "";
	 
	    $transaktionsart                   = $HTTP_GET_VARS['transaktionsart'];
			
			
			if ($transaktionsart == "0") {                     // Einzahlung..
			
			   // Eingaben in Variablen einlesen
			   $eingang_tag            =                $HTTP_GET_VARS['eingang_tag'];
			   $eingang_monat       =                $HTTP_GET_VARS['eingang_monat'];
				 $eingang_jahr           =                $HTTP_GET_VARS['eingang_jahr'];				 
				 $auszug_nr               = (int)        $HTTP_GET_VARS['auszug_nr'];
				 $summe                     = (double) str_replace(",", ".", $HTTP_GET_VARS['summe_einzahlung']);
				 
				 $kontobewegungs_datum = $eingang_jahr."-".$eingang_monat."-".$eingang_tag;
				 
				 // Eingabefehler abfangen...
				 if (!(isset($auszug_nr) && $auszug_nr > 0)) { $errStr .= "Die Kontoauszugsnr ist ungültig!<br>"; $auszug_nr="";}
				 if (!(isset($summe) && $summe > 0)) { $errStr .= "Die Summe ist ungültig!<br>"; $summe=""; }
				 
			} else if ($transaktionsart == "1") {
			
			   $errStr .= "Funktion noch nicht vorhanden...";
			
			
			} else if ($transaktionsart == "2") {     // sonstiges...
			
			
			   // Eingaben in Variablen einlesen
			   $notiz                        =                str_replace("'", "", str_replace('"', "", $HTTP_GET_VARS['notiz']));
				 $summe                     = (double) str_replace(",", ".", $HTTP_GET_VARS['summe_sonstiges']);
				 
				 // Eingabefehler abfangen...
				 if (!(isset($notiz) && $notiz != "")) $errStr .= "Bitte eine Notiz angeben, mit der dieser Vorgang nachvollzogen werde kann!<br>";
				 if (!(isset($summe) && $summe != 0)) $errStr .= "Die Summe ist ungültig!<br>";			
			
			}
    
			
			// Wenn keine Fehler, dann ändern...
			if ($errStr == "") {
			
			  // Gruppenkontostand auslesen...
				$alter_kontostand = kontostand($gruppen_id);
				
				$neuer_kontostand = $alter_kontostand + $summe;
				if ($neuer_kontostand < 0) $onload_str .= "alert('ACHTUNG: Das Gruppenkonto weist einen negativen Kontostand auf. Dies sollte NICHT VORKOMMEN!! Bitte prüfen!'); ";
			
			   // Transaktion speichern...
			   mysql_query( "INSERT INTO gruppen_transaktion
                  (type
                  , gruppen_id
                  , eingabe_zeit
                  , summe
                  , kontoauszugs_nr
                  , notiz
                  , kontobewegungs_datum
                  , dienstkontrollblatt_id)
           VALUES
                ('".mysql_escape_string($transaktionsart)
              . "', '".mysql_escape_string($gruppen_id)
              . "', NOW(), '"
              . mysql_escape_string($summe)
              . "', '".mysql_escape_string($auszug_nr)
              . "', '".mysql_escape_string($notiz)
              . "', '".mysql_escape_string($kontobewegungs_datum)
              . "',$dienstkontrollblatt_id) "
         ) or error(__LINE__,__FILE__,"Konnte Transaktion nicht speichern.",mysql_error());
				 
				 // Gruppenkontostand anpassen...
				 $onload_str .= "opener.document.forms['reload_form'].submit();";
				 
				 // Wenn Transaktion durchgeführt wurde, dann Eingabemaske wieder zurücksetzen...
				 $transaktionsart       = "0";
				 $auszug_nr               = "";
				 $summe                     = "";
				 $notiz                        = "";
				 
			}
	 }
	 
	 


  // aktuelle Gruppendaten laden
	$result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Gruppendaten nicht lesen.",mysql_error());
	$row = mysql_fetch_array($result);
	 
?>

<html>
<head>
   <title>Kontotransaktion</title>
     <link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">
   <h3>Kontotransaktion durchführen</h3>
	 <form action="makeGroupTransaktion.php">
			<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
			<input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
			<table class="">
			   <tr>
				    <td><b>Gruppenname</b></td>
						<td align="right"><?PHP echo $row['name']; ?></td>
				 </tr>		 
			   <tr>
				    <td><b>aktueller Kontostand</b></td>
						<td
						align="right"><?PHP
						echo
						sprintf("%.02f",kontostand($gruppen_id)); ?></td>
				 </tr>				 
			   <tr>
				    <td colspan="2">
						
						   <table class="inner">
							 <tr>
						      <td><input type="radio" name="transaktionsart" value="0" <?PHP if ($transaktionsart == 0) echo "checked"; ?>> <b>Einzahlung</b></td>
							 </tr><tr>
							    <td></td>
									<td>Kontoeingang:</td>
									<td>
									   <select name="eingang_tag">
										    <?PHP for ($i=1; $i < 32; $i++) { if ($i == $eingang_tag) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
										 </select>
										 .
									   <select name="eingang_monat">
										    <?PHP for ($i=1; $i < 13; $i++) { if ($i == $eingang_monat) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
										 </select>
                     .
									   <select name="eingang_jahr">
										    <?PHP for ($i=2004; $i < 2011; $i++)  { if ($i == $eingang_jahr) $select_str="selected"; else $select_str = ""; echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } ?>
										 </select>										 
									</td>
							 </tr><tr>
							    <td></td>
									<td>Kontoauszug Nr:</td>
									<td><input type="text" size="4" name="auszug_nr" value="<?PHP echo $auszug_nr; ?>"></td>
							 </tr><tr>
							    <td></td>
									<td>Summe:</td>
									<td><input type="text" name="summe_einzahlung" value="<?PHP if ($transaktionsart == 0) echo $summe; ?>"></td>
							 </tr>
							 </table>
							 
						</td>
				 </tr><tr>
				    <td colspan="2">
						
						   <table class="inner">
							 <tr>
						      <td><input type="radio" name="transaktionsart" value="2" <?PHP if ($transaktionsart == 2) echo "checked"; ?>> <b>Sonstiges</b></td>
							 </tr><tr>
							    <td></td>
									<td>Notiz:</td>
									<td><textarea name="notiz"><?PHP echo $notiz; ?></textarea></td>
							 </tr><tr>
							    <td></td>
									<td>Summe:</td>
									<td><input type="text" name="summe_sonstiges" value="<?PHP if ($transaktionsart == 2) echo $summe; ?>"></td>
							 </tr>
							 </table>						
						
						</td>
				 </tr>
				 </tr>
				 <tr>
				    <td colspan="2" align="center">
				    		<input type="submit" value="Durchführen">
				    		<input type="button" value="Schließen" onClick="opener.focus(); window.close();">
				    	</td>
				 </tr>
			</table>
	 </form>
	 <a href="groupTransaktionMenu.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>&gruppen_id=<?PHP echo $gruppen_id; ?>&gruppen_name=<?PHP echo $row['name']; ?>">Zurück</a>
	 <b><font color="#FF0000"><?PHP echo $errStr; ?></font></b>
	 
</body>
</html>
