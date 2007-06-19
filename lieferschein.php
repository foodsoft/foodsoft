<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
include("code/zuordnen.php");
include("code/views.php");
// um die bestellungen nach produkten sortiert zu sehen ....


// Übergebene Variablen einlesen...
   if (isset($HTTP_GET_VARS['gruppen_id'])) $gruppen_id = $HTTP_GET_VARS['gruppen_id'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['gruppen_pwd'])) $gruppen_pwd = $HTTP_GET_VARS['gruppen_pwd'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['bestgr_pwd'])) $bestgr_pwd = $HTTP_GET_VARS['bestgr_pwd'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['bestellungs_id'])) $bestell_id = $HTTP_GET_VARS['bestellungs_id'];
    if (isset($HTTP_GET_VARS['allGroupsArray'])) $allGroupsArray = $HTTP_GET_VARS['allGroupsArray'];
    if (isset($HTTP_GET_VARS['sortierfolge'])) $sortierfolge = $HTTP_GET_VARS['sortierfolge'];
    if (isset($HTTP_GET_VARS['nichtGeliefert'])) $nichtGeliefert = $HTTP_GET_VARS['nichtGeliefert'];

    $pwd_ok = false;
    $bestgrup_view = false;

	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

	//nicht gelieferte Produkte auf 0 setzen
	if (isset($nichtGeliefert) && isset($bestell_id) && isset($bestgr_pwd) && $bestgr_pwd == $real_bestellt_pwd) {
	    //Hier tut's noch nicht mit der Mehrfachauswahl der checkboxen...
	    //Im internet suchen wie machen
	    //echo "nichtGeliefert: ".$nichtGeliefert."<br>";
	    //echo "HTTP_GET_VARS['nichtGeliefert']: ".$HTTP_GET_VARS['nichtGeliefert']."<br>";
	    nichtGeliefert($bestell_id, $nichtGeliefert);
	}


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
   <?
    	//zusätzlich gelieferte Produkte
	if (isset($HTTP_GET_VARS['liefermenge'])){
		$liefermenge = $HTTP_GET_VARS['liefermenge'];
		$produkt_id = $HTTP_GET_VARS['produkt_id'];
		if($liefermenge>0){
			zusaetzlicheBestellung($produkt_id,$bestell_id,  $liefermenge);
			$prod = getProdukt($produkt_id);
			$prod_name = $prod['name'];
		}
		
	}
  ?>
         <form action="index.php" method="post">
         <table style="width: 600px;" >
            <tr class="legende">
               <td>Produkt </td>
               <td> Gebindegrösse </td>
               <td>Einheit </td>
               <td> Liefermenge </td>
               <td> Netto/Einheit (Brutto,MWSt,Pfand)</td>
               <td> Bruttopreis </td>
            </tr>
                            
<?php                               
      //produkte und preise zur aktuellen bestellung auslesen
      $result1 = sql_bestellprodukte($bestell_id);
      $preis_summe = 0;

      //jetzt die namen und preis zu den produkten auslesen
      while  ($produkte_row = mysql_fetch_array($result1)) {
      	 $produkt_id =$produkte_row['produkt_id'];
	 if($produkte_row['liefermenge']!=0){	
		  ?>
	       <tr> <td><span style='font-size:1.2em; margin:5px;'><?echo( $produkte_row['produkt_name']);?></span></td>
               <td> <? echo($produkte_row['gebindegroesse'])?> </td>
               <td> <?echo($produkte_row['einheit'])?> </td>
	       <?
			$liefer = $produkte_row['liefermenge'];
			$fieldname = "verteilmenge".$produkt_id;
			$selectpreis = "preis".$produkt_id;
	       		if(isset($HTTP_GET_VARS[$fieldname])){
				$liefer_form =$HTTP_GET_VARS[$fieldname];
				if($liefer!=$liefer_form){
					changeLiefermengen_sql($liefer_form, $produkt_id, $bestell_id );
					$liefer=$liefer_form;
				}
			
			}
	       ?>
               <td> <input name="<? echo($fieldname) ?>" type="text" size="3" value="<? echo($liefer) ?>"/></td>
               <td> <select name="<? echo($selectpreis)?>"> 
	       		<?
			   $current_preis_id = $produkte_row['produktpreise_id'];
				if(isset($HTTP_GET_VARS[$selectpreis])){
					$preis_form =$HTTP_GET_VARS[$selectpreis];
					if($current_preis_id!=$preis_form){
						changeLieferpreis_sql($preis_form, $produkt_id, $bestell_id );
						$current_preis_id=$preis_form;
					}
				
				}
			   $preise=sql_produktpreise2($produkt_id);
			   while($pr = mysql_fetch_array($preise)){
				$sel = "";
			   	if($pr['id']==$current_preis_id ){
					$sel = " selected=\"selected\"";
					$preis =$pr['preis'];
				}
				echo "<option value='{$pr['id']}' $sel>";
        // bruttopreis berechnen und ausgeben (wie auf papierlieferschein!):
        printf( "%5.2lf", ( $pr['preis'] - $pr['pfand'] ) / ( 1.0 + $pr['mwst'] / 100.0 ) );
				echo "&nbsp;({$pr['preis']},{$pr['mwst']},{$pr['pfand']})";
        echo "</option>\n";
			   }
	       
	       		?>
	   	     </select>
		     </td>
		     <td>
	       		<?echo($preis*$liefer );
			$preis_summe+=$preis*$liefer
			?> 
			</td>
		  </tr>

     		<tr style='border:none'>
		 <td colspan='4' style='border:none'></td>
	      </tr>
   
   <?
   } //end if ... reichen die bestellten mengen? dann weiter im text
   
} //end while produkte array            
?>
   <tr>
   <td></td><td></td><td></td><td></td><td>Summe</td><td><?echo($preis_summe)?></td></tr>
   <tr style='border:none'>
	<td colspan='4' style='border:none'>
	   <input type="hidden" name="area" value="lieferschein">			
	   <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	   <input type="submit" value=" Lieferschein ändern ">
	   <input type="reset" value=" Änderungen zurücknehmen">
	</td>
   </tr>
   </table>                   
   </form>
      
   <h3> Zusätzlich geliefertes Produkt </h3>
   <form>
	   <input type="hidden" name="area" value="lieferschein">			
	   <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	     <?php
	         select_products_not_in_list($bestell_id);
	     ?>
	   Menge: <input type="text" name="liefermenge">
	   <input type="submit" value="Zusätzliche Lieferung eintragen">
   </form>


   <form action="index.php" method="post">
	   <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	   <input type="hidden" name="area" value="bestellt">			
	   <input type="submit" value="Zurück ">
   </form>
