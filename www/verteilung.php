<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
require_once("code/config.php");
require_once("$foodsoftpath/code/zuordnen.php");
require_once("$foodsoftpath/code/views.php");
require_once("$foodsoftpath/code/login.php");
$pwd_ok = $angemeldet;
require_once("$foodsoftpath/head.php");

// um die bestellungen nach produkten sortiert zu sehen ....


// Übergebene Variablen einlesen...
    if (isset($HTTP_GET_VARS['bestellungs_id'])) {
    		$bestell_id = $HTTP_GET_VARS['bestellungs_id'];
	} else {
	 	$result = sql_bestellungen( $_SESSION['ALLOWED_ORDER_STATES'][$area] );
		select_bestellung_view($result, array("Verteiltabelle" => "verteilung"));
		exit();
	 }
  get_http_var('gruppen_id');
  get_http_var('allGroupsArray');
  get_http_var('sortierfolge');


     if( ! $angemeldet ) {
       exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
     } 

     if(!nur_fuer_dienst(1,4)){exit();}

    if (!isset($bestell_id)) {
	 	$result = sql_bestellungen(array(STATUS_LIEFERANT, STATUS_VERTEILT));
		select_bestellung_view($result, array("zeigen" => "verteilung") , "Veteilung der Bestellung");
		exit();
	 }

	 $basar= sql_basar_id();
	


	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

         //infos zur gesamtbestellung auslesen 
         $sql = "SELECT *
                  FROM gesamtbestellungen
                  WHERE id = ".$bestell_id."";
         $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Gesamtbestellungsdaten nich aus DB laden..",mysql_error());
         $row_gesamtbestellung = mysql_fetch_array($result);               
?>
<h1>Bestellungen ansehen...</h1>

	<p>
<?	wikiLink("foodsoft:verteilung", "Wiki...");
?>
        </p>


	 <?bestellung_overview($row_gesamtbestellung);?>
	 <?changeState($bestell_id, "Verteilt")?>
      <br>
      <br>
         <form action="index.php" method="post">
         <table style="width: 600px;" class='numbers'>
	    <?distribution_tabellenkopf("Gruppe");?>
<?php                               
      //produkte und preise zur aktuellen bestellung auslesen
      $result1 = sql_bestellprodukte($bestell_id);

      //jetzt die namen und preis zu den produkten auslesen
      while  ($produkte_row = mysql_fetch_array($result1)) {
         // nettopreis, Masseinheiten, ... ausrechnen:
         preisdatenSetzen( $produkte_row );
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
	 	
		  echo " <tr> <th colspan='8'><span
		  style='font-size:1.2em; margin:5px;'> ".$produkte_row['produkt_name']."</span>
					 <span style='font-size:0.8em'>(".$produkte_row['verteileinheit']." | 
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
					$verteil_form =$HTTP_GET_VARS['verteil_'.$produkt_id."_".$gruppenID] / $produkte_row['kan_verteilmult'];
					if($verteil!=$verteil_form){
						changeVerteilmengen_sql($verteil_form, $gruppenID, $produkt_id, $bestell_id );
						$verteil=$verteil_form;
					}
				}
			} else {
				$verteil = 0;
			}
			

       if($gruppenID != $basar){
		       distribution_view($gruppenname, $festmenge, $toleranz, $verteil,
             $produkte_row['kan_verteilmult'], $produkte_row['kan_verteileinheit'],
             $produkte_row['preis'],"verteil_".$produkt_id."_".$gruppenID );
       }
		     
	 } //end while gruppen array
	 
     echo "<tr style='border:none'>
		 <td colspan='4' style='border:none'></td>
	      </tr>";
   
   } //end if ... reichen die bestellten mengen? dann weiter im text
   
} //end while produkte array            

  echo "
     <tr style='border:none'>
  	<td colspan='4' style='border:none;'>
  	   <input type='hidden' name='bestellungs_id' value='$bestell_id'>
  	   <input type='hidden' name='area' value='verteilung'>			
  	   <input type='submit' value=' speichern '>
  	   <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen'>
  	</td>
     </tr>
     </table>                   
     </form>
     <form action='index.php' method='get'>
  	   <input type='hidden' name='bestellungs_id' value='$bestell_id'>
  	   <input type='hidden' name='area' value='bestellt'>			
  	   <input type='submit' value='Zur&uuml;ck '>
     </form>
  ";
?>
