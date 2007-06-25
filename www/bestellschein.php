<?php
include("code/zuordnen.php");
include("code/views.php");
// um die bestellungen nach produkten sortiert zu sehen ....

     if( ! $angemeldet ) {
       exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
     } 

     if(!nur_fuer_dienst(4)){exit();}

// Übergebene Variablen einlesen...
    if (isset($HTTP_GET_VARS['bestellungs_id'])) {
    		$bestell_id = $HTTP_GET_VARS['bestellungs_id'];
	} else {
	 	$result = sql_bestellungen(array(STATUS_BESTELLEN, STATUS_LIEFERANT));
		select_bestellung_view($result, array("zeigen" => "bestellschein", "pdf" => "bestellt_faxansicht" ));
		exit();
	 }
										
	 if(getState($bestell_id)==STATUS_BESTELLEN){
	 	verteilmengenZuweisen($bestell_id);
	 }
         //infos zur gesamtbestellung auslesen 
	 $result = sql_bestellungen(FALSE,FALSE,$bestell_id);
?>
<h1>Bestellungen an Lieferanten...</h1>

	 <?bestellung_overview(mysql_fetch_array($result));
	 next_view_fuer_Produkte_kombiniert_mit_lieferschein();
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
      

   <form action="index.php" method="post">
	   <input type="hidden" name="area" value="bestellschein">			
	   <input type="submit" value="Zurück zur Auswahl ">
   </form>
