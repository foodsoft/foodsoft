<?php
//This file defines views for foodsoft data
/**
 * Liste zur Auswahl einer Bestellung via Link
 */
function select_bestellung_view($result, $area, $label=array("zeigen"), $head="Bitte eine Bestellung wählen:" ){

      echo $head;
      ?>
      <br /> <br />
	     <table style="width:600px;" class="liste">
		  <tr>
		    <th>Name</th>
		    <th>Beginn</th>
		    <th>Ende</th>
		 </tr>
		 <?php
		 while ($row = mysql_fetch_array($result)) {
		 ?>
		 <tr>                                 
		    <td><a class="tabelle" href="index.php?area=<?echo $area;?>&bestellungs_id=<?echo $row['id'];?>"><?echo $row['name']?></a></td>
		    <td><? echo $row['bestellstart']; ?></td>
		    <td><? echo $row['bestellende']; ?></td>
		    <?
			while($area_name = current($area)){
			    $label=key($area);
				   ?>
				   <td>
				      <form action="index.php" method="POST">         
				      <input type="hidden" name="bestellungs_id" value=<? echo($row['id'])?> >
				      <input type="hidden" name="area" value=<? echo($area_name)?> >
					  <input type="submit" value="<?echo($label)?>">
				       </form>
				   </td>
		      <?
		            next($area);

			}
			reset($area);
		    ?>
		 </tr>   
		  <?  }?>

            </table> 

<?
  
}

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
               <td><?echo $name?></td>
               <td>bestellt (toleranz)</td>
               <td>geliefert</td>
               <td>Preis</td>
            </tr>
 
  <?
}
function distribution_view($name, $festmenge, $toleranz, $verteilmenge, $preis, $inputbox_name = false){
  ?>
      <tr>
	 <td> <?echo $name?></td>
	 <td><b><?echo $festmenge?></b> (<?echo $toleranz?>)</td>
	 <td>
	     <?if($inputbox_name===false){
	     	   echo $verteilmenge ;
	     }else{?>
	            <input name="<?echo $inputbox_name?>" type="text" size="3" value="<?echo $verteilmenge ?>" />
	     <?}?>
	 </td>
	 <td><?echo $verteilmenge."x".$preis."=".($verteilmenge* $preis)?></td>
      </tr>
   <?
}

function sum_row($sum){
?>
<tr style='border:none'>
		 <td colspan='5' style='border:none' align=right><b>Summe = <?echo $sum?></b></td>
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
