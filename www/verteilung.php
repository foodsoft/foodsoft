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
<h1>Verteilliste</h1>

<? bestellung_overview($row_gesamtbestellung); ?>

<br>
<br>
<form action="<? echo self_url(); ?>" method="post"><? echo self_post(); ?>
<table style="width: 600px;" class='numbers'>

<?
distribution_tabellenkopf("Gruppe"); 

$result1 = sql_bestellprodukte($bestell_id);

while  ($produkte_row = mysql_fetch_array($result1)) {
  // nettopreis, Masseinheiten, ... ausrechnen:
  preisdatenSetzen( $produkte_row );
  $produkt_id =$produkte_row['produkt_id'];
  // echo "produkt_id: $produkt_id<br>";

  // Wenn genügend bestellt wurde, gibt es mindestens einen  Eintrag mit art=2
  $result = sql_bestellmengen($bestell_id,
     $produkt_id, 
     2, //art
     false, //gruppen_id
     false); //sortByDate

  if( mysql_num_rows($result) > 0 ) {
    $result = sql_bestellmengen($bestell_id,
      $produkt_id, 
      false, //art
      false, //gruppen_id
      false); // false heisst: ORDER BY gruppenbestellung_id, art

   ?>
     <tr><th colspan='6'>
       <span style='font-size:1.2em; margin:5px;'> <? echo $produkte_row['produkt_name']; ?> </span>
       <span style='font-size:0.8em'>
       <? printf( "Preis: %.2lf / %s, Produktgruppe: %s"
         , $produkte_row['preis']
         , $produkte_row['verteileinheit']
         , $produkte_row['produktgruppen_name']
         );
       ?>
       </span>
     </th></tr>
   <?

   $entry_row = mysql_fetch_array($result);
   while ($entry_row) {
     $gruppenID=$entry_row['bestellguppen_id'];
     $festmenge = 0;
     while( $entry_row['art']==0 and $entry_row['bestellguppen_id']==$gruppenID ) {
       $festmenge += $entry_row['menge'];
       $entry_row = mysql_fetch_array($result);
     }
     $toleranz = 0;
     while( $entry_row['art']==1 and $entry_row['bestellguppen_id']==$gruppenID){
      $toleranz += $entry_row['menge'];
      $entry_row = mysql_fetch_array($result);
    }
    $verteil = 0;
    while($entry_row['art']==2 and $entry_row['bestellguppen_id']==$gruppenID){
      $verteil += $entry_row['menge'];
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

    if( $gruppenID != $basar ) {
      distribution_view( $gruppenID, $festmenge, $toleranz, $verteil,
             $produkte_row['kan_verteilmult'], $produkte_row['kan_verteileinheit'],
             $produkte_row['preis'],"verteil_".$produkt_id."_".$gruppenID );
    }

  } //end while gruppen array

  ?>
     <tr style='border:none'>
     <td colspan='6' style='border:none'></td>
     </tr>
   <?

   } //end if ... reichen die bestellten mengen?

} //end while produkte array

?>
  <tr style='border:none'>
    <td colspan='6' style='border:none;'>
      <input type='submit' value=' speichern '>
      <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen'>
    </td>
  </tr>
</table>
</form>
<form action='index.php' method='get'>
  <input type='submit' value='Zur&uuml;ck '>
</form>


