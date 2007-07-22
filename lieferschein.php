<?php
//error_reporting(E_ALL); // alle Fehler anzeigen

  require_once("$foodsoftpath/code/zuordnen.php");
  require_once("$foodsoftpath/code/views.php");
  require_once("$foodsoftpath/head.php");
  require_once("$foodsoftpath/code/login.php");
  if( ! $angemeldet ) {
    exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
  } 
  
  // um die bestellungen nach produkten sortiert zu sehen ....


     // if(!nur_fuer_dienst(1,4)){exit();}

// Übergebene Variablen einlesen...
    if (isset($HTTP_GET_VARS['bestellungs_id'])) {
    		$bestell_id = $HTTP_GET_VARS['bestellungs_id'];
	} else {
	 	$result = sql_bestellungen( array(STATUS_LIEFERANT,STATUS_VERTEILT) );
		select_bestellung_view($result, "lieferschein");
		exit();
	 }
										
				
							 		
    if (isset($HTTP_GET_VARS['nichtGeliefert'])) $nichtGeliefert = $HTTP_GET_VARS['nichtGeliefert'];


	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

	//nicht gelieferte Produkte auf 0 setzen
	if (isset($nichtGeliefert) && isset($bestell_id) ) {
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

  echo "
    <h1>Lieferschein</h1>
         <table class='info'>
               <tr>
                   <th> Bestellung: </th>
                     <td style='font-size:1.2em;font-weight:bold'>{$row_gesamtbestellung['name']}</td>
                </tr>
               <tr>
                   <th> Bestellbeginn: </th>
                     <td>{$row_gesamtbestellung['bestellstart']}</td>
                </tr>
               <tr>
                   <th> Bestellende: </th>
                     <td>{$row_gesamtbestellung['bestellende']}</td>
                </tr>                
            </table>
      <br>
      <br>
  ";
  
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
  echo "
         <form action='index.php' method='post'>
           <input type='hidden' name='area' value='lieferschein'>			
           <input type='hidden' name='bestellungs_id' value='$bestell_id'>
           <input type='hidden' name='action' value='aktualisiere_lieferschein'>
         <table>
           <tr class='legende'>
             <th>Produkt</th>
             <th>Bruttopreis</th>
             <th>MWSt</th>
             <th>Pfand</th>
             <th>Nettopreis</th>
             <th>Liefermenge</th>
             <th>Gesamtpreis</th>
           </tr>
  ";

  //produkte und preise zur aktuellen bestellung auslesen
  $produkte = sql_bestellprodukte($bestell_id);
  $preis_summe = 0;

  //jetzt die namen und preis zu den produkten auslesen
  while  ($produkte_row = mysql_fetch_array($produkte)) {
    $produkt_id =$produkte_row['produkt_id'];

    echo "
      <tr id='row$produkt_id'>
      <td>$produkt_id
    ";

    kanonische_einheit( $produkte_row['liefereinheit'], &$kan_liefereinheit, &$kan_liefermult );
    kanonische_einheit( $produkte_row['verteileinheit'], &$kan_verteileinheit, &$kan_verteilmult );
    $gebindegroesse = $produkte_row['gebindegroesse'];
    $mwst = $produkte_row['mwst'];
    $pfand = $produkte_row['pfand'];
    $preis = $produkte_row['preis'];

    if( $kan_liefereinheit != $kan_verteileinheit ) {
      // typischer fall: liefereinheit=KIste, verteileinheit: STueck
      // hier sollte die liefereinheit einem gebinde entsprechen, und der preis pro gebinde
      // in der lieferantenrechnung stehen!
      $kan_liefereinheit = $kan_verteileinheit;
      $kan_liefermult = $kan_verteilmult * $gebindegroesse;
    }

    // nettopreis pro verteileinheit berechnen:
    $nettopreis = ( $preis - $pfand ) / ( 1.0 + $mwst / 100.0 );

    // einheit waehlen, die vermutlich in der lieferantenrechnung aufgefuehrt ist:
    switch( $kan_liefereinheit ) {
      case 'g':
        // ausgewogene waren: lieferanten sollten preis pro 1 kg berechnen:
        $preiseeinheit = 'kg';
        $mengenfaktor = 1000.0 / $kan_verteilmult;
        break;
      case 'ml':
        // pro volumen: lieferanten sollten preis pro 1 liter berechnen:
        $preiseinheit = 'L';
        $mengenfaktor = 1000.0 / $kan_verteilmult;
        break;
      default:
        // sachen sollten stueckweise verteilt werden:
        assert( $kan_verteilmult == 1 );
        $preiseinheit = $kan_liefereinheit;
        $mengenfaktor = 1.0;
    }
    $lieferpreis = $nettopreis * $mengenfaktor;

    $liefermenge = $produkte_row['liefermenge'] / $mengenfaktor;
    if( get_http_var( 'liefermenge'.$produkt_id ) ) {
      if( abs( ${"liefermenge$produkt_id"} - $liefermenge ) > 0.001 ) {
        $liefermenge = ${"liefermenge$produkt_id"};
        // echo "<div class='ok'>neue liefermenge fuer $produkt_id: $liefermenge</div>";
        changeLiefermengen_sql( $liefermenge * $mengenfaktor, $produkt_id, $bestell_id );
      }
    }

    $gesamtpreis = $lieferpreis * $liefermenge;

    // if($produkte_row['liefermenge']!=0){	
    echo "
      {$produkte_row['produkt_name']}</td>
      <td class='number'>$preis / $kan_verteilmult $kan_verteileinheit</td>
      <td class='number'>$mwst</td>
      <td class='number'>$pfand</td>
      <td class='number'><a
        href='terraabgleich.php?produktid=$produkt_id&bestell_id=$bestell_id'
        target='foodsoftdetail'
        onclick=\"
          document.getElementById('row$produkt_id').className='modified';
          document.getElementById('row_total').className='modified';\"
        >
        $lieferpreis / $preiseinheit</a></td>
      <td class='number' style='text-align:left;'><input name='liefermenge$produkt_id' type='text' size='5' value='$liefermenge'></innput> $preiseinheit</td>
      <td class='number'>$gesamtpreis</td>
    </tr>";

    $preis_summe += $gesamtpreis;

//         $fieldname = "liefermenge".$produkt_id;
// 			$selectpreis = "preis".$produkt_id;
// 	       		if(isset($HTTP_GET_VARS[$fieldname])){
// 				$liefer_form =$HTTP_GET_VARS[$fieldname];
// 				if($liefer!=$liefer_form){
// 					changeLiefermengen_sql($liefer_form, $produkt_id, $bestell_id );
// 					$liefer=$liefer_form;
// 				}
// 			
// 			}
// 	       
// 	      
// 			   $current_preis_id = $produkte_row['produktpreise_id'];
// 				if(isset($HTTP_GET_VARS[$selectpreis])){
// 					$preis_form =$HTTP_GET_VARS[$selectpreis];
// 					if($current_preis_id!=$preis_form){
// 						changeLieferpreis_sql($preis_form, $produkt_id, $bestell_id );
// 						$current_preis_id=$preis_form;
// 					}
// 				
// 				}
// 			   $preise=sql_produktpreise2($produkt_id);
// 			   while($pr = mysql_fetch_array($preise)){
// 				$sel = "";
// 			   	if($pr['id']==$current_preis_id ){
// 					$sel = " selected=\"selected\"";
// 					$preis =$pr['preis'];
// 				}
// 				echo "<option value='{$pr['id']}' $sel>";
//         // bruttopreis berechnen und ausgeben (wie auf papierlieferschein!):
//         printf( "%5.2lf", ( $pr['preis'] - $pr['pfand'] ) / ( 1.0 + $pr['mwst'] / 100.0 ) );
// 				echo "&nbsp;({$pr['preis']},{$pr['mwst']},{$pr['pfand']})";
//         echo "</option>\n";
// 			   }
// 	       
// 	     
// 	   	     </select>
// 		     </td>
// 		     <td>
// 	       		<?echo($preis*$liefer );
//			$preis_summe+=$preis*$liefer
//			
   
   //// } //end if ... reichen die bestellten mengen? dann weiter im text
   
      } //end while produkte array            

      echo "
        <tr id='row_total'>
          <td colspan='6'>&nbsp;</td>
          <td>$preis_summe</td>
        </tr>
        <tr>
          <td colspan='7'>
            <input type='submit' value=' Lieferschein Aktualisieren '>
            <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen '>
          </td>
        </tr>
        </table>
        </form>
      ";
?>

   <h3> Zusätzlich geliefertes Produkt </h3>
   <form>
	   <input type="hidden" name="area" value="lieferschein">			
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	     <?php
	         select_products_not_in_list($bestell_id);
	     ?>
	   Menge: <input type="text" name="liefermenge">
	   <input type="submit" value="Zusätzliche Lieferung eintragen">
   </form>


   <form action="index.php" method="post">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	   <input type="hidden" name="area" value="bestellt">			
	   <input type="submit" value="Zur&uuml;ck ">
   </form>


<?php
  echo "$print_on_exit";
?>
