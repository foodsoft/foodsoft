<?php

// verteilung.php
//
// um die bestellungen nach produkten sortiert zu sehen ....

//error_reporting(E_ALL); // alle Fehler anzeigen

  assert( $angemeldet ) or exit();

  if( get_http_var( 'bestellungs_id', 'u' ) ) {
    $bestell_id = $bestellungs_id;
    $self_fields['bestell_id'] = $bestell_id;
  } else {
    get_http_var('bestell_id','u',0,true);
  }
  if( ! $bestell_id ) {
    $result = sql_bestellungen( $_SESSION['ALLOWED_ORDER_STATES'][$area] );
    select_bestellung_view($result, array("Verteiltabelle" => "verteilung"));
    return;
	}

  if(!nur_fuer_dienst(1,4)){exit();}

    if (!isset($bestell_id)) {
	 	$result = sql_bestellungen(array(STATUS_LIEFERANT, STATUS_VERTEILT));
		select_bestellung_view($result, array("zeigen" => "verteilung") , "Verteilung der Bestellung");
		exit();
	 }

	 $basar= sql_basar_id();

	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

  $row_gesamtbestellung = sql_bestellung( $bestell_id );


setWikiHelpTopic( "foodsoft:verteilung" );

?>
<h1>Bestellungen ansehen...</h1>

	 <?bestellung_overview($row_gesamtbestellung);?>
	 <?changeState($bestell_id, "Verteilt")?>
      <br>
      <br>
         <form action="<? echo self_url(); ?>" method="post">
         <? echo self_post(); ?>
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
        $fieldname = 'verteil_'.$produkt_id.'_'.$gruppenID;
				if( get_http_var( $fieldname, 'f' ) ) {
					$verteil_form = $$fieldname / $produkte_row['kan_verteilmult'];
					if($verteil!=$verteil_form){
						changeVerteilmengen_sql($verteil_form, $gruppenID, $produkt_id, $bestell_id );
						$verteil=$verteil_form;
					}
				}
			} else {
				$verteil = 0;
			}
			

       if($gruppenID != $basar){
		       distribution_view($gruppenID, $festmenge, $toleranz, $verteil,
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
  	   <input type='submit' value=' speichern '>
  	   <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen'>
  	</td>
     </tr>
     </table>                   
     </form>
     <form action='index.php' method='get'>
  	   <input type='submit' value='Zur&uuml;ck '>
     </form>
  ";
?>
