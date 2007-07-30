<?php
//This file defines views for foodsoft data

function select_products_not_in_list($bestell_id){
	   echo "Produkt: <select name=\"produkt_id\"> ";
	 if($bestell_id!=0){
	   $produkte=getProdukteVonLieferant(getProduzentBestellID($bestell_id), $bestell_id);
	   while($prod = mysql_fetch_array($produkte)){
		echo "<option value=\"".$prod['p_id']."\">".
			$prod['name']." (".$prod['einheit'].") "."</option>\n";
	   }
	 }
	 echo "  </select>\n";

}
function distribution_tabellenkopf($name){
  ?>
            <tr class="legende">
               <th><?echo $name?></th>
               <th colspan='2'>bestellt (toleranz)</th>
               <th colspan='2'>geliefert</th>
               <th colspan='2'>Einzelpreis</th>
               <th>Gesamtpreis</th>
            </tr>
 
  <?
}
function distribution_view($name, $festmenge, $toleranz, $verteilmenge, $verteilmult, $verteileinheit, $preis, $inputbox_name = false){
  echo "
    <tr>
      <td>$name</td>
      <td class='mult'><b>" . $festmenge * $verteilmult . " </b> (" . $toleranz * $verteilmult . ")</td>
      <td class='unit'>$verteileinheit</td>
      <td class='mult'>
  ";
  if($inputbox_name===false){
      echo $verteilmenge * $verteilmult;
  }else{
      echo "<input name='$inputbox_name' type='text' size='5'
            value='" . $verteilmenge * $verteilmult . "' />";
  }
  echo "
      </td>
      <td class='unit'>$verteileinheit</td> 
      <td class='mult'>$preis</td>
      <td class='unit'>/ $verteilmult $verteileinheit</td>
      <td class='number'>" . sprintf( "%8.2lf", $verteilmenge * $preis ) . " </td>
    </tr>
  ";
}

function sum_row($sum){
?>
<tr style='border:none'>
		 <td colspan='7' style='border:none' align=right><b>Summe:</b></td>
     <td class='number'><b><?echo
     sprintf( "%8.2lf", $sum); ?></b></td>
	      </tr>
<?
}
function bestellung_overview($row){
	 ?>
         <table class="info">
               <tr>
                   <th> Bestellung: </th>
                     <td style="font-size:1.2em;font-weight:bold"><?PHP echo $row['name']; ?></td>
                </tr>
               <tr>
                   <th> Bestellbeginn: </th>
                     <td><?PHP echo $row['bestellstart']; ?></td>
                </tr>
               <tr>
                   <th> Bestellende: </th>
                     <td><?PHP echo $row['bestellende']; ?></td>
                </tr>                
            </table>
	    <?
}

?>
