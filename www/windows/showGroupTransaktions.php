<?PHP
 
 //Vergleicht das Datum der beiden mysql-records
 //gibt +1 zurück, wenn Datum in $konto älter ist
 //gibt 0 zurück, wenn Daten gleich sind
 //gibt -1 zurück, wen Datum in $veteil älter ist
 function compare_date($konto, $verteil){
 	//Kein weiterer Eintrag in Konto
 	if(!$konto) return 1;
	if(!$verteil) return -1;
 	$konto_date = $konto['date'];
	$verteil_date = $verteil['datum'];
	// Zeit abschneiden
	$temp = explode("<", $konto_date);
	//echo "konto-datum ".$temp[0];
	$k = explode(".", $temp[0]);
	$temp = explode("<", $verteil_date);
	//echo "verteil-datum ".$temp[0];
	$v = explode(".", $temp[0]);
	//Jahr vegleichen
	if($k[2]<$v[2]){
		return 1;
	} else if($k[2]>$v[2]){
		return -1;
	} else {
		//Monat vergleichen
		if($k[1]<$v[1]){
			return 1;
		} else if($k[1]>$v[1]){
			return -1;
		} else {
			//Tag vergleichen
			if($k[0]<$v[0]){
				return 1;
			} else if($k[0]>$v[0]){
				return -1;
			} else {
				return 0;
			}
		}
	}

 }
/*   // wichtige Variablen einlesen...
 *	 if($meinKonto){  
 *  		$gruppen_pwd    = crypt($HTTP_GET_VARS['gruppen_pwd'],35464);
 *	 } else {
 *  		$gruppen_pwd    = $HTTP_GET_VARS['gruppen_pwd'];
 *	 }
 *	 $gruppen_id 	     = $HTTP_GET_VARS['gruppen_id'];
 */
  if($meinKonto) {
    $gruppen_id = $login_gruppen_id;
  } else {
 	 require_once('code/config.php');
 	 require_once('code/err_functions.php');
 	 require_once('code/connect_MySQL.php');
 	 require_once('code/zuordnen.php');
   require_once('code/login.php');
   nur_fuer_dienst_IV();
   get_http_var( 'gruppen_id' ) or die();
  }
 
	 // Variablen initialisieren
	 $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...
	 
	 
// 	 // zur Sicherheit das Passwort prüfen..
// 	 if ($gruppen_pwd != $real_gruppen_pwd){
// 		echo "<h2>Passwortfehler</h2>";
// 	 	exit();
// 	}
   if( ! $angemeldet ) {
     echo "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div></body></html>";
     exit();
   }
	 

  // aktuelle Gruppendaten laden
	$result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Gruppendaten nicht lesen.",mysql_error());
	$bestellgruppen_row = mysql_fetch_array($result);
	
	// wieviele Kontenbewegungen werden ab wo angezeigt...
	if (isset($HTTP_GET_VARS['start_pos'])) $start_pos = $HTTP_GET_VARS['start_pos']; else $start_pos = 0;
	//Funktioniert erstmal mit der Mischung aus Automatischer Berechung und manuellen Einträgen nicht
	$size          = 2000;
	 
	$type2str[0] = "Einzahlung";
	$type2str[1] = "Bestellung";
	$type2str[2] = "Sonstiges";
	
?>

<html>
<head>
   <title>Kontotransaktionen - Kontoauszüge</title>
<link rel="stylesheet" type="text/css" media="screen" href="../css/foodsoft.css" />
</head>
<body onload="<?PHP echo $onload_str; ?>">
   <h3>Kontoauszüge von: <?PHP echo $bestellgruppen_row['name']; ?></h3>

   <?php
   if( ( $gesamtbestellung_id = $HTTP_GET_VARS['gesamtbestellung_id'] ) ) {
     echo "details fuer bestellung: $gesamtbestellung_id";
     echo "<div class='warn'>noch in arbeit!</div>";
     exit(12);
   }
   ?>
   
	 <table style="width:430px;" class="liste">
	    <tr>
			   <th>type</th>
				 <th>eingabezeit</th>
				 <th>informationen</th>
				 <th>summe</th>
			</tr>
			<?PHP
			   if(isset($_POST['trans_nr']) && isset($_POST['auszug'])){
			          if($_POST['auszug'] > 0){
				  	sqlUpdateTransaction($_POST['trans_nr'], $_POST['auszug']);
				  }
			   }

			   $result = mysql_query("SELECT id, type, summe, kontobewegungs_datum, kontoauszugs_nr, notiz, DATE_FORMAT(eingabe_zeit,'%d.%m.%Y  <br> <font size=1>(%T)</font>') as date FROM gruppen_transaktion WHERE gruppen_id=".mysql_escape_string($gruppen_id)." ORDER BY  eingabe_zeit DESC LIMIT ".mysql_escape_string($start_pos).", ".mysql_escape_string($size).";") or error(__LINE__,__FILE__,"Konnte Gruppentransaktionsdaten nicht lesen.",mysql_error());
				 $num_rows = mysql_num_rows($result);
				 $vert_result = sql_gesamtpreise($gruppen_id);
				 $no_more_vert = false;
				 $no_more_konto=false;
				 $konto_row = mysql_fetch_array($result);
				 $vert_row = mysql_fetch_array($vert_result);
				 //Gehe zum ersten Eintrag in Bestellzuordnung, der nach dem Eintrag in Konto liegt
				 //while(compare_date($konto_row, $vert_row)==+1){
				 	//$vert_row = mysql_fetch_array($vert_result);
				 //}
				 while (!($no_more_vert && $no_more_konto)) {
				    //Mische Einträge aus Kontobewegungen und Verteilzuordnung zusammen
				    if(compare_date($konto_row, $vert_row)==1 && !$no_more_vert){
				    		//Eintrag in Konto ist Älter -> Verteil ausgeben
					    echo "<tr>\n";
					    echo "   <td valign='top'><b>Bestell Abrechnung</b></td>\n";
					    echo "   <td>".$vert_row['datum']."</td>\n";
					    echo "   <td>Bestellung: ".$vert_row['name']." </td>";
					    echo "   <td align='right' valign='bottom'> <b> ".$vert_row['gesamtpreis']."</b> <br>";
					    ?>
						<form action=<?if($meinKonto) echo "index.php"; else echo "../index.php";?> method="post">
							   <input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
							   <input type="hidden" name="gruppen_pwd" value="<?PHP echo $HTTP_GET_VARS['gruppen_pwd']; ?>">
							   <input
							   type="hidden"
							   name="bestell_id"
							   value="<?PHP
							   echo
							   $vert_row['gesamtbestellung_id'] ?>">
							   <input type="hidden" name="area" value="bestellt_gruppe">			
							   <input type="submit" value="Details ">
						   </form>
					    <?
					    echo "   </td>";
				 	    $vert_row = mysql_fetch_array($vert_result);
					    if(!$vert_row){
					    	$no_more_vert = true;
					    }

			            } else {

					    echo "<tr>\n";
							echo "   <td valign='top'><b>".$type2str[$konto_row['type']]."</b></td>\n";
							echo "   <td>".$konto_row['date']."</td>\n";
							
							if ($konto_row['type'] == 0) {
                     ?>
                          <td> <table style='font-size:10pt' class='inner'>
                           <tr><td>Einzahldatum:</td>
                           <td><?echo $konto_row['kontobewegungs_datum']?></td>
                           </tr>
                           <tr><td>AuszugsNr:</td><td>
                             <?
                        if($meinKonto or $konto_row['kontoauszugs_nr']>0){
			   echo $konto_row['kontoauszugs_nr'] ;
                        } else {
			   ?>
						<form
						action=showGroupTransaktions.php method="post">
							   <input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
							   <input type="hidden" name="gruppen_pwd" value="<?PHP echo $_REQUEST['gruppen_pwd']; ?>">
							   <input type="hidden" name="trans_nr" value="<?PHP echo $konto_row['id'] ?>">
							   <input type="text" size=12 name=auszug />
							   <input type="submit" value="Bestätigen ">
						   </form>
			   <?
                        }
                        ?></td></tr>
                           </table> </td>
                     <?
							} else if ($konto_row['type'] == 1) {
							   echo "<td>[noch nicht unterstützt]</td>";
		    } else {
							   echo "<td>".$konto_row['notiz']."</td>";
							}
							
							echo "   <td align='right' valign='bottom'><b>".$konto_row['summe']."</b></td>\n";
							echo "</tr>\n";
				 	    $konto_row = mysql_fetch_array($result);
					    if(!$konto_row){
					    	$no_more_konto = true;
					    }

				 	}
				 }
			?>
	 </table>
	 <form name="skip" action="showGroupTransaktions.php">
	    <input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
			<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
			<input type="hidden" name="start_pos" value="<?PHP echo $start_pos; ?>">
			<?PHP 
			   $downButtonScript = "";
			   if ($start_pos > 0 && $start_pos > $size)
				    $downButtonScript="document.forms['skip'].start_pos.value=".($start_pos-$size).";";
				 else if ($start_pos > 0)
				    $downButtonScript="document.forms['skip'].start_pos.value=0;";
						
				 if ($downButtonScript != "")
				    echo "<input type=button value='<' onClick=\"".$downButtonScript." ;document.forms['skip'].submit();\">";
						

			   $upButtonScript = "";
			   if ($num_rows == $size)
				    $upButtonScript="document.forms['skip'].start_pos.value=".($start_pos+$size).";";

				 if ($upButtonScript != "") echo "<input type=button value='>' onClick=\"".$upButtonScript.";document.forms['skip'].submit()\"";
			?>
	 </form>
	 <? if(!$meinKonto){  ?>
	 	 <a href="groupTransaktionMenu.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>&gruppen_id=<?PHP echo $gruppen_id; ?>&gruppen_name=<?PHP echo $bestellgruppen_row['name']; ?>">Zurück</a>
	 <? } ?>

</body>
</html>
