<?php
error_reporting(E_ALL);
// um die bestellungen nach produkten sortiert zu sehen ....

     if( ! $angemeldet ) {
       exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
     } 

     if(!nur_fuer_dienst(1,3,4)){exit();}

// Übergebene Variablen einlesen...
    $editable=FALSE;
	switch($area){
	case 'bestellschein':
	   $editable=FALSE;
	   $title="Bestellschein für den Lieferanten";
	   $selectButtons = array("zeigen" => "bestellschein", "pdf" => "bestellt_faxansicht" );
	   break;
	case 'lieferschein':
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
	 	$result = sql_bestellungen($_SESSION['ALLOWED_ORDER_STATES'][$area]);
		select_bestellung_view($result, $selectButtons, $title );
		exit();
	 }
										
	 if(getState($bestell_id)==STATUS_BESTELLEN){
	 	verteilmengenZuweisen($bestell_id);
	 }
         //infos zur gesamtbestellung auslesen 
	 $result = sql_bestellungen(FALSE,FALSE,$bestell_id);


  // liefermengen aktualisieren:
  //
  if( $editable ) {
    $produkte = sql_bestellprodukte($bestell_id);
    while  ($produkte_row = mysql_fetch_array($produkte)) {
      $produkt_id =$produkte_row['produkt_id'];
      if( get_http_var( 'liefermenge'.$produkt_id ) ) {
        preisdatenSetzen( & $produkte_row );
        $mengenfaktor = $produkte_row['mengenfaktor'];
        $liefermenge = $produkte_row['liefermenge'] / $mengenfaktor;
        if( abs( ${"liefermenge$produkt_id"} - $liefermenge ) > 0.001 ) {
          $liefermenge = ${"liefermenge$produkt_id"};
          changeLiefermengen_sql( $liefermenge * $mengenfaktor, $produkt_id, $bestell_id );
        }
      }
    }
  }
	
       //Formular ausgeben

	echo "<h1>".$title."</h1>";

	 bestellung_overview(mysql_fetch_array($result));
	 
	 products_overview($bestell_id, $editable, $editable);
         
?>

   <form action="index.php" method="get">
	   <input type="hidden" name="area" value="<?echo($area)?>">			
	   <input type="submit" value="Zurück zur Auswahl ">
   </form>
