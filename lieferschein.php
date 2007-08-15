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


  //if(!nur_fuer_dienst(1,3,4)){exit();}

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
		// if($liefermenge>0){
			zusaetzlicheBestellung($produkt_id,$bestell_id,  $liefermenge);
			// $prod = getProdukt($produkt_id);
			// $prod_name = $prod['name'];
		// }
		
	}
  echo "
         <form action='index.php' method='post'>
           <input type='hidden' name='area' value='lieferschein'>			
           <input type='hidden' name='bestellungs_id' value='$bestell_id'>
           <input type='hidden' name='action' value='aktualisiere_lieferschein'>
         <table class='numbers' width='100%'>
           <tr class='legende'>
             <th>Produkt</th>
             <th title='Endpreis pro V-Einheit' colspan='2'>V-Preis</th>
             <th>MWSt</th>
             <th>Pfand</th>
             <th title='Nettopreis beim Lieferanten' colspan='2'>L-Preis</th>
             <th colspan='3'>Liefermenge</th>
             <th colspan='2'>Gebinde</th>
             <th>Gesamtpreis</th>
           </tr>
  ";

  // liefermengen aktualisieren:
  //
  $produkte = sql_bestellprodukte($bestell_id);
  while  ($produkte_row = mysql_fetch_array($produkte)) {
    $produkt_id =$produkte_row['produkt_id'];
    if( get_http_var( 'liefermenge'.$produkt_id ) ) {
      preisdatenSetzen( & $produkte_row );
      $mengenfaktor = $produkte_row['mengenfaktor'];
      $liefermenge = $produkte_row['liefermenge'] / $mengenfaktor;
      if( abs( ${"liefermenge$produkt_id"} - $liefermenge ) > 0.001 ) {
        $liefermenge = ${"liefermenge$produkt_id"};
        // echo "<div class='ok'>neue liefermenge fuer $produkt_id: $liefermenge</div>";
        changeLiefermengen_sql( $liefermenge * $mengenfaktor, $produkt_id, $bestell_id );
      }
    }
  }

  // produkte und preise zur aktuellen bestellung nochmal auslesen
  // (reihenfolge kann nun anders sein!)
  $produkte = sql_bestellprodukte($bestell_id);
  $preis_summe = 0;

  $nichtgeliefert_header_ausgeben = true;

  //jetzt die namen und preis zu den produkten auslesen
  while  ($produkte_row = mysql_fetch_array($produkte)) {
    $produkt_id =$produkte_row['produkt_id'];
    if( $produkte_row['liefermenge'] == 0 ) {
      if( $nichtgeliefert_header_ausgeben ) {
        echo "
          <tr id='row_total' class='summe'>
            <td colspan='12' style='text-align:right;'>Summe:</td>
            <td class='number'>" .  sprintf( "%8.2lf", $preis_summe ) . "</td>
          </tr>
          <tr>
            <th colspan='13'>
              <img id='nichtgeliefert_knopf' class='button' src='img/close_black_trans.gif'
                onclick='nichtgeliefert_toggle();' title='Ausblenden'>
              </img>
              Nicht bestellte oder nicht gelieferte Produkte:
            </th>
          </tr>
        ";
        $nichtgeliefert_header_ausgeben = false;
      }
      echo "<tr name='trnichtgeliefert'";
    } else {
      echo "<tr name='geliefert'";
    }

    echo " id='row$produkt_id'><td>";

    preisdatenSetzen( & $produkte_row );

    $lieferpreis = $produkte_row['lieferpreis'];
    $mengenfaktor = $produkte_row['mengenfaktor'];
    $liefermenge = $produkte_row['liefermenge'] / $mengenfaktor;

    $gesamtpreis = sprintf( "%8.2lf", $lieferpreis * $liefermenge );

    echo "
        {$produkte_row['produkt_name']}
      </td>
      <td class='mult'>{$produkte_row['preis_rund']}</td>
      <td class='unit'>/ {$produkte_row['kan_verteilmult']} {$produkte_row['kan_verteileinheit']}</td>
      <td class='number'>{$produkte_row['mwst']}</td>
      <td class='number'>{$produkte_row['pfand']}</td>
      <td class='mult'><a
        href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produktid=$produkt_id&bestell_id=$bestell_id','foodsoftdetail');\"
          onclick=\"
            document.getElementById('row$produkt_id').className='modified';
            document.getElementById('row_total').className='modified';\"
          title='Preis oder Produktdaten &auml;ndern'
        >
        $lieferpreis</a></td>
      <td class='unit'>/ {$produkte_row['preiseinheit']}</a></td>
      <td class='mult'>
        <input name='liefermenge$produkt_id' type='text' size='5' value='$liefermenge'
          onchange=\"
            document.getElementById('row$produkt_id').className='modified';
            document.getElementById('row_total').className='modified';\"
          title='tats&auml;chliche Liefermenge eingeben'
        ></input>
      </td>
      <td class='unit' style='border-right-style:none;'>{$produkte_row['preiseinheit']}</td>
      <td style='border-left-style:none;'><a class='png' style='padding:0pt 1ex 0pt 1ex;'
        href=\"javascript:neuesfenster('/foodsoft/windows/showBestelltProd.php?bestell_id=$bestell_id&produkt_id=$produkt_id','produktVerteilung')\"
        title='Details zur Verteilung'
        ><img src='img/b_browse.png' border='0' title='Details zur Verteilung' alt='Details zur Verteilung'
        ></a></td>
      <td class='mult'>"
      . sprintf( "%.2lf", $produkte_row['liefermenge'] / $produkte_row['gebindegroesse'] ). " * </td>
      <td class='unit'>(" . $produkte_row['kan_verteilmult'] * $produkte_row['gebindegroesse']
                         . " {$produkte_row['kan_verteileinheit']})</td>
      <td class='number'>$gesamtpreis</td>
    </tr>";

    $preis_summe += $gesamtpreis;

    // } //end if ... reichen die bestellten mengen? dann weiter im text
   
      } //end while produkte array            

      if( $nichtgeliefert_header_ausgeben ) {
        // summe muss noch angezeigt werden:
        echo "
          <tr id='row_total' class='summe'>
            <td colspan='12' style='text-align:right;'>Summe:</td>
            <td class='number'>" .  sprintf( "%8.2lf", $preis_summe ) . "</td>
          </tr>
        ";
      }
      echo "
        <tr>
          <td colspan='13'>
            <input type='submit' value=' Lieferschein Aktualisieren '>
            <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen '>
          </td>
        </tr>
      ";
      echo "
        </table>
        </form>
      ";
?>

   <h3> Zus&auml;tzlich geliefertes Produkt </h3>
   <form>
	   <input type="hidden" name="area" value="lieferschein">			
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	     <?php
	         select_products_not_in_list($bestell_id);
	     ?>
     <!-- hier muesste erst noch die richtige einheit berechnet werden,
       deshalb erstmal mit menge 0 eintragen, dann spaeter setzen!
	   Menge: --> <input type="hidden" name="liefermenge" value="0">
	   <input type="submit" value="Zus&auml;tzliche Lieferung eintragen">
   </form>


   <form action="index.php" method="post">
	   <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	   <input type="hidden" name="area" value="bestellt">			
	   <input type="submit" value="Zur&uuml;ck ">
   </form>


<?php
  echo "$print_on_exit";
?>

<script type="text/javascript">
  nichtgeliefert_zeigen = 1;
  function nichtgeliefert_toggle() {
    nichtgeliefert_zeigen = !  nichtgeliefert_zeigen;
    if( nichtgeliefert_zeigen ) {
      // document.getElementById("table_nichtgeliefert").style.display = "run-in";
        rows = document.getElementsByName('trnichtgeliefert');
        i=0;
        while( rows[i] ) {
          rows[i].style.display = "";
          i++;
        }
      document.getElementById("nichtgeliefert_knopf").src = "img/close_black_trans.gif";
      document.getElementById("nichtgeliefert_knopf").title = "Ausblenden";
    } else {
       // document.getElementById("table_nichtgeliefert").style.display = "none";
        rows = document.getElementsByName('trnichtgeliefert');
        i=0;
        while( rows[i] ) {
          rows[i].style.display = "none";
          i++;
        }
      document.getElementById("nichtgeliefert_knopf").src = "img/open_black_trans.gif";
      document.getElementById("nichtgeliefert_knopf").title = "Einblenden";
    }
  }
  function neuesfenster(url,name) {
    f=window.open(url,name);
    f.focus();
  }
</script>

