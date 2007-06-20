<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
include("code/zuordnen.php");
include("code/views.php");

// um die bestellung für eine bestimmte gruppe zu sehen ....


// Übergebene Variablen einlesen...
    if (isset($_REQUEST['bestell_id'])) $bestell_id = $_REQUEST['bestell_id'];

    
    if($angemeldet){


	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

         //infos zur gesamtbestellung auslesen 
         $sql = "SELECT *
                  FROM gesamtbestellungen
                  WHERE id = ".$bestell_id;
         $result = mysql_query($sql) or
	 error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus
	 DB laden.. ($sql)",mysql_error());
         $row_gesamtbestellung = mysql_fetch_array($result);               
?>
<h1>Bestellungen ansehen für Gruppe <? echo sql_gruppenname($login_gruppen_id)?></h1>
	 <?bestellung_overview($row_gesamtbestellung);?>
      <br>
      <br>
         <table style="width: 600px;" >
	    <?distribution_tabellenkopf("Produkt");?>
                           
<?php                               
      //produkte und preise zur aktuellen bestellung auslesen
      $sum=0;
      $result1 = sql_bestellprodukte($bestell_id);

      //jetzt die namen und preis zu den produkten auslesen
      while  ($produkte_row = mysql_fetch_array($result1)) {
      	 $produkt_id =$produkte_row['produkt_id'];
	 
	 $result = sql_bestellmengen($bestell_id,
	 			     $produkt_id, 
				     false, //art
	 			     $login_gruppen_id, //gruppen_id
				     false); //sortByDate
	 if(mysql_num_rows($result)>0){
	 	 
		  $entry_row = mysql_fetch_array($result);
		  while ($entry_row) {
			$gruppenID=$entry_row['bestellguppen_id'];
		  	$gruppenname=sql_gruppenname($gruppenID);
		  	if($entry_row['art']==0){
				$festmenge = 0;
				while($entry_row['art']==0 and $entry_row['bestellguppen_id']==$gruppenID){
					//Festbestellmenge einlesen
					$festmenge += $entry_row['menge'];
					//Nächsten Datensatz
					$entry_row = mysql_fetch_array($result);
				}
			} else {
				$festmenge = 0;
			}
		  	if($entry_row['art']==1 and $entry_row['bestellguppen_id']==$gruppenID ){
				$toleranz = 0;
				while($entry_row['art']==1 and $entry_row['bestellguppen_id']==$gruppenID){
					//Toleranzmenge einlesen
					$toleranz += $entry_row['menge'];
					//Nächsten Datensatz
					$entry_row = mysql_fetch_array($result);
				}
			} else {
				$toleranz = 0;
			}
		  	if($entry_row['art']==2 and $entry_row['bestellguppen_id']==$gruppenID ){
				$verteil = 0;
				while($entry_row['art']==2 and $entry_row['bestellguppen_id']==$gruppenID){
					//Verteilmenge einlesen
					$verteil += $entry_row['menge'];
					//Nächsten Datensatz 
					$entry_row = mysql_fetch_array($result);
				}
				if(isset($HTTP_GET_VARS['verteil_'.$produkt_id."_".$gruppenID])){
					$verteil_form =$HTTP_GET_VARS['verteil_'.$produkt_id."_".$gruppenID];
					if($verteil!=$verteil_form){
						changeVerteilmengen_sql($verteil_form, $gruppenID, $produkt_id, $bestell_id );
						$verteil=$verteil_form;
					}
				}
			} else {
				$verteil = 0;
			}
			

		       distribution_view($produkte_row['produkt_name'], $festmenge, $toleranz, $verteil, $produkte_row['preis']);
		       $sum += $verteil*$produkte_row['preis'];
		     
	 } //end while gruppen array
	 
   
   } //end if ... reichen die bestellten mengen? dann weiter im text
   
} //end while produkte array            
   sum_row($sum);
   } else {
   ?><h2>Falsches Passwort?</h2><?

  }
?>
