<?php
//
// bestellschein.php:
// - wenn bestell_id (oder bestellungs_id...) uebergeben:
//   detailanzeige, abhaengig vom status der bestellung
// - wenn keine bestell_id uebergeben:
//   auswahlliste aller bestellungen zeigen
//   (ggf. mit filter "status")
//

error_reporting(E_ALL);

     if( ! $angemeldet ) {
       exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
     } 

if( get_http_var( 'bestellungs_id', 'u' ) )
  $bestell_id = $bestellungs_id;
else
  get_http_var( 'bestell_id', 'u' );

$self = "$foodsoftdir/index.php?area=bestellschein";
$self_fields = "<input type='hidden' name='area' value='bestellschein'>";
$self_form = "<form action='$self' method='post'>$self_fields";

get_http_var( 'action', 'w' );
switch( $action ) {
  case 'changeState':
    nur_fuer_dienst(1,3,4);
    need_http_var( 'change_id', 'u' );
    need_http_var( 'change_to', 'w' );
    changeState( $change_id, $change_to );
    break;
  case 'changeEndDate':
    nur_fuer_dienst(4);
    // yet to be implemented...
  default:
    break;
}

if( $bestell_id ) {
  $self = "$self&bestell_id=$bestell_id";
  $self_fields = "$self_fields<input type='hidden' name='bestell_id' value='$bestell_id'>";
  $state = getState( $bestell_id );
} else {
  if( ! get_http_var( 'state', 'w' ) ) {
    switch( $area ) {
      case 'lieferschein':
        $state = 'Verteilt';
        break;
      case 'bestellschein':
        $state = 'beimLieferanten';
        break;
      default:
    }
  $result = sql_bestellungen( $state );
  select_bestellung_view($result, /* $selectButtons, */ 'Liste der Bestellungen', $hat_dienst_IV, $dienst > 0 );
  echo "$print_on_exit";
  exit();
}

	switch($state){
	case STATUS_BESTELLEN:
     $editable = FALSE;
	   $title="Bestellschein (vorl√§ufig)";
	   // $selectButtons = array("zeigen" => "bestellschein", "pdf" => "bestellt_faxansicht" );
	   break;
	case STATUS_LIEFERANT:
     $editable= FALSE;
	   $title="Bestellschein f¸r den Lieferanten";
	   // $selectButtons = array("zeigen" => "bestellschein", "pdf" => "bestellt_faxansicht" );
	   break;
	case STATUS_VERTEILT:
	   $editable = ( $hat_dienst_I or $hat_dienst_III or $hat_dienst_IV );
	   $title="Lieferschein";
	   // $selectButtons = array("zeigen" => "lieferschein");
	   break;
	default: 
	   ?>
	   <div class='warn'>Keine Detailanzeige verf√ºgbar</div>
	   <?
     echo "$print_on_exit";
	   exit();
	}


										
	 if(getState($bestell_id)==STATUS_LIEFERANT){
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
	   <input type="submit" value="Zur¸ck zur Auswahl ">
   </form>
