<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
include("code/zuordnen.php");
// um die bestellungen nach produkten sortiert zu sehen ....


// Übergebene Variablen einlesen...
   if (isset($HTTP_GET_VARS['gruppen_id'])) $gruppen_id = $HTTP_GET_VARS['gruppen_id'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['gruppen_pwd'])) $gruppen_pwd = $HTTP_GET_VARS['gruppen_pwd'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['bestgr_pwd'])) $bestgr_pwd = $HTTP_GET_VARS['bestgr_pwd'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['bestellungs_id'])) $bestell_id = $HTTP_GET_VARS['bestellungs_id'];
    if (isset($HTTP_GET_VARS['allGroupsArray'])) $allGroupsArray = $HTTP_GET_VARS['allGroupsArray'];
    if (isset($HTTP_GET_VARS['sortierfolge'])) $sortierfolge = $HTTP_GET_VARS['sortierfolge'];

    $pwd_ok = false;
    $bestgrup_view = false;

	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

         //infos zur gesamtbestellung auslesen 
         $sql = "SELECT *
                  FROM gesamtbestellungen
                  WHERE id = ".$bestell_id."";
         $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
         $row_gesamtbestellung = mysql_fetch_array($result);               
?>
<h1>Bestellungen ansehen...</h1>
         <table class="info">
               <tr>
                   <th> Bestellung: </th>
                     <td style="font-size:1.2em;font-weight:bold"><?PHP echo $row_gesamtbestellung['name']; ?></td>
                </tr>
               <tr>
                   <th> Bestellbeginn: </th>
                     <td><?PHP echo $row_gesamtbestellung['bestellstart']; ?></td>
                </tr>
               <tr>
                   <th> Bestellende: </th>
                     <td><?PHP echo $row_gesamtbestellung['bestellende']; ?></td>
                </tr>                
            </table>
      <br>
      <br>
         <form action="index.php" method="post">
         <table style="width: 600px;" >
            <tr class="legende">
               <td colspan="5">Produkt (Einheit | Gebindegrösse | Preis | Produktgruppe)</td>
            </tr>
            <tr class="legende">
               <td>Gruppe</td>
               <td>bestellt (toleranz)</td>
               <td>geliefert</td>
               <td>Preis</td>
            </tr>
                            
<?php                               
      //produkte und preise zur aktuellen bestellung auslesen
      $result1 = sql_bestellprodukte($bestell_id);

      //jetzt die namen und preis zu den produkten auslesen
      while  ($produkte_row = mysql_fetch_array($result1)) {
      	 $produkt_id =$produkte_row['produkt_id'];
	 
	 //Wenn genügend bestellt wurde, gibt es mindestens einen
	 //Eintrag mit art=2
	 $result = sql_bestellmengen($bestell_id,
	 			     $produkt_id, 
				     2, //art
	 			     false, //gruppen_id
				     false); //sortByDate
	 if(mysql_num_rows($result)>0){
	 	$result = sql_bestellmengen($bestell_id,
	 			     $produkt_id, 
				     false, //art
	 			     false, //gruppen_id
				     false); //sortByDate
	 	
		  echo " <tr> <th colspan='3'><span
		  style='font-size:1.2em; margin:5px;'> ".$produkte_row['produkt_name']."</span>
					 <span style='font-size:0.8em'>(".$produkte_row['einheit']." | 
					 ".$produkte_row['gebindegroesse']." | 
					 ".$produkte_row['preis']." | 
					 ".$produkte_row['produktgruppen_name'].")";
		  echo "</span>";
		  echo "</th> </tr>";

	 
		  $entry_row = mysql_fetch_array($result);
		  while ($entry_row) {
			$gruppenID=$entry_row['bestellguppen_id'];
		  	$gruppenname=sql_gruppenname($gruppenID);
		  	if($entry_row['art']==0){
				//Festbestellmenge einlesen
				$festmenge = $entry_row['menge'];
				//Nächsten Datensatz
				$entry_row = mysql_fetch_array($result);
			} else {
				$festmenge = 0;
			}
		  	if($entry_row['art']==1){
				//Toleranzmenge einlesen
				$toleranz = $entry_row['menge'];
				//Nächsten Datensatz
				$entry_row = mysql_fetch_array($result);
			} else {
				$toleranz = 0;
			}
		  	if($entry_row['art']==2){
				//Verteilmenge einlesen
				$verteil = $entry_row['menge'];
				if(isset($HTTP_GET_VARS['verteil_'.$produkt_id."_".$gruppenID])){
					$verteil_form =$HTTP_GET_VARS['verteil_'.$produkt_id."_".$gruppenID];
					if($verteil!=$verteil_form){
						changeVerteilmengen_sql($verteil_form, $gruppenID, $produkt_id, $bestell_id );
						$verteil=$verteil_form;
					}
				}
				//Nächsten Datensatz nicht einlesen
				$entry_row = mysql_fetch_array($result);
			} else {
				$verteil = 0;
			}
			

		       echo "
	      <tr>
		 <td>".$gruppenname."</td>
		 <td><b>".$festmenge."</b> (".$toleranz.")</td>
		 <td><input name=\"verteil_".$produkt_id."_".$gruppenID."\" type=\"text\" size=\"3\" value=".$verteil." /></td>
		 <td>".$verteil."x".$produkte_row['preis']."=".($verteil* $produkte_row['preis'])."</td>
	      </tr>";

	       
		     
	 } //end while gruppen array
	 
     echo "<tr style='border:none'>
		 <td colspan='4' style='border:none'></td>
	      </tr>";
   
   } //end if ... reichen die bestellten mengen? dann weiter im text
   
} //end while produkte array            
?>
   <tr style='border:none'>
	<td colspan='4' style='border:none'>
	   <input type="hidden" name="area" value="bestellt_produkte">			
	   <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	   <input type="submit" value=" Verteilung ändern ">
	   <input type="reset" value=" Änderungen zurücknehmen">
	</td>
   </tr>
   </table>                   
   </form>
