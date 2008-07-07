<?php

// verteilung.php
//
// um die bestellungen nach produkten sortiert zu sehen ....

//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();
need_http_var('bestell_id','u',true);

$status = getState( $bestell_id );

$ro_tag = '';
if( $status != STATUS_VERTEILT ) {
  $ro_tag = 'readonly';
}

if(!nur_fuer_dienst(1,4)){exit();}

$basar= sql_basar_id();

	//Änderung der Gruppenverteilung wird unten, beim Aufbau der
	//Tabelle überprüft und eingetragen

$row_gesamtbestellung = sql_bestellung( $bestell_id );

setWikiHelpTopic( "foodsoft:verteilung" );

?>
<h1>Verteilliste</h1>

<? bestellung_overview($row_gesamtbestellung); ?>

<form action="<? echo self_url(); ?>" method="post"><? echo self_post(); ?>
<table style="width: 620px;" class='numbers'>

<?
distribution_tabellenkopf("Gruppe"); 

$result1 = sql_bestellprodukte($bestell_id);

while  ($produkte_row = mysql_fetch_array($result1)) {
  // nettopreis, Masseinheiten, ... ausrechnen:
  preisdatenSetzen( $produkte_row );
  $produkt_id = $produkte_row['produkt_id'];
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
       <span style='font-size:1.2em; margin:5px;'>
         <? echo fc_alink( 'produktpreise', array(
           'text' => $produkte_row['produkt_name'], 'img' => '', 'produkt_id' => $produkt_id ) ); ?>
       </span>
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
             $produkte_row['preis'], ( $ro_tag ? false : "verteil_".$produkt_id."_".$gruppenID ) );
    }

  } //end while gruppen array

  ?>
     <tr style='border:none'>
     <td colspan='6' style='border:none'></td>
     </tr>
   <?

   } //end if ... reichen die bestellten mengen?

} //end while produkte array

if( ! $ro_tag ) {
  ?>
  <tr style='border:none'>
    <td colspan='6' style='border:none;'>
      <input type='submit' value=' speichern '>
      <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen'>
    </td>
  </tr>
  <?
}

?>
</table>
</form>


