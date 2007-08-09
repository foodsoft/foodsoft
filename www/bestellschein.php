<?php
error_reporting(E_ALL);
// um die bestellungen nach produkten sortiert zu sehen ....

     if( ! $angemeldet ) {
       exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
     } 

     if(!nur_fuer_dienst(4)){exit();}

// Übergebene Variablen einlesen...
    $editable=FALSE;
    $possibleState=array(STATUS_LIEFERANT);
	switch($area){
	case 'bestellschein':
	   $possibleState[]=STATUS_BESTELLEN;
	   $editable=FALSE;
	   $title="Bestellschein für den Lieferanten";
	   $selectButtons = array("zeigen" => "bestellschein", "pdf" => "bestellt_faxansicht" );
	   break;
	case 'lieferschein':
	   $possibleState[]=STATUS_VERTEILT;
	   $editable=TRUE;
	   $title="Lieferschein";
	   $selectButtons = array("zeigen" => "lieferschein");
	   break;
	default: 
	   ?>
	   <p> Fehlerhafte Auswahl für area: <?echo $area?> </p>
	   <?
	   exit();
	}
    if (isset($HTTP_GET_VARS['bestellungs_id'])) {
    		$bestell_id = $HTTP_GET_VARS['bestellungs_id'];
	} else {
	 	$result = sql_bestellungen($possibleState);
		select_bestellung_view($result, $selectButtons, $title );
		exit();
	 }
										
	 if(getState($bestell_id)==STATUS_BESTELLEN){
	 	verteilmengenZuweisen($bestell_id);
	 }
         //infos zur gesamtbestellung auslesen 
	 $result = sql_bestellungen(FALSE,FALSE,$bestell_id);

	 //Liefermengen oder Preise ändern (Nur beim LIEFERSCHEIN)
	 foreach (array_keys($_REQUEST) as $submitted){
	 	if(strstr($submitted, "preis")!==FALSE){
			$preis_form =$HTTP_GET_VARS[$submitted];
			$podukt_id = str_replace("preis", "", $submitted);
			$current_preis_id=sql_Lieferpreis($produkt_id, $bestell_id);
			if($current_preis_id!=$preis_form){
				changeLieferpreis_sql($preis_form, $produkt_id, $bestell_id );
				 if(getState($bestell_id)==STATUS_LIEFERANT){
					changeState($bestell_id, STATUS_VERTEILT);
				 }
			}
		} elseif(strstr($submitted, "verteilmenge")!==FALSE) {
			$menge_form =$HTTP_GET_VARS[$submitted];
			$podukt_id = str_replace("verteilmenge", "", $submitted);
			$current_menge=sql_liefermenge($produkt_id, $bestell_id);
			if($current_menge_id!=$menge_form){
				changeLiefermengen_sql($liefer_form, $produkt_id, $bestell_id );
				 if(getState($bestell_id)==STATUS_LIEFERANT){
					changeState($bestell_id, STATUS_VERTEILT);
				 }
			}

		}
	 }
	
       //Formular ausgeben

	echo "<h1>".$title."</h1>";

	 bestellung_overview(mysql_fetch_array($result));
	 
	 products_overview($bestell_id, $editable, $editable);
         
?>

   <form action="index.php" method="post">
	   <input type="hidden" name="area" value="<?echo($area)?>">			
	   <input type="submit" value="Zurück zur Auswahl ">
   </form>
