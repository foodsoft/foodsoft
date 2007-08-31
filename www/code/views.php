<?php
//This file defines views for foodsoft data


function number_selector($name, $min, $max, $selected, $format){
 ?>
    <select name="<?echo $name?>">
          <?PHP 
	  for ($i=$min; $i <= $max; $i++) { 
	       if ($i == $selected) $select_str="selected";
     	       else $select_str = ""; 
	       echo "<option value='".$i."' ".$select_str.">".sprintf($format,$i)."</option>\n"; } ?>
    </select>
  <?
}
/**
 * Stellt eine komplette Editiermöglichkeit für
 * Datum und Uhrzeit zur Verfügung.
 * Muss in ein Formluar eingebaut werden
 * Die Elemente des Datums stehen dann zur Verfügung als
 *   <prefix>_minute
 *   <prefix>_stunde
 *   <prefix>_tag
 *   <prefix>_monat
 *   <prefix>_jahr
 */
function date_time_selector($sql_date, $prefix, $show_time=true){
	$datum = date_parse($sql_date);

?>     <table class='inner'>
                  <tr>
                     <td>Datum</td>
                      <td style='white-space:nowrap;'>
         <?date_selector($prefix."_tag", $datum['day'],$prefix."_monat", $datum['month'], $prefix."_jahr", $datum['year'])?>
                     </td>
                   
                   </tr><tr>

                 <td>Zeit</td>
                         <td style='white-space:nowrap;'>
         <?time_selector($prefix."_stunde", $datum['hour'],$prefix."_minute", $datum['minute'])?>
                         </td>
                     </tr>
                  </table>   
            
<?
}
function date_selector($tag_feld, $tag, $monat_feld, $monat, $jahr_feld, $jahr){
    number_selector($tag_feld, 1, 31, $tag,"%02d");
    echo ".";
    number_selector($monat_feld,1, 12, $monat,"%02d");
    echo ".";
    number_selector($jahr_feld, 2004, 2011, $jahr,"%04d");
}
function time_selector($stunde_feld, $stunde, $minute_feld, $minute){
    number_selector($stunde_feld, 0, 24, $stunde,"%02d");
    echo ":";
    number_selector($minute_feld,0, 59, $minute,"%02d");
}


/**
 *  Zeigt einen Dienst und die möglichen Aktionen
 */
function dienst_view($row, $gruppe, $show_buttons = TRUE, $area="dienstplan"){
       $critical_date = in_two_weeks();
       if(compare_date2($row["Lieferdatum"], $critical_date)){
	  //soon
	  $color_norm="#00FF00";
	  $color_not_confirmed="#FFC800";
	  $color_not_accepted="#FF0000";
	  $soon=TRUE;
       } else {
	  $color_norm="#00FF00";
	  $color_not_confirmed="#00FF00";
	  $color_not_accepted="#000000";
	  $soon=FALSE;
       }
       switch($row["Status"]){
       case "Vorgeschlagen":
	    if($gruppe == $row["GruppenID"]){
	    ?>
	       <font color="<?echo $color_not_accepted?>"> Dieser Dienst ist euch zugeteilt <br>
	       <?if($show_buttons){?>
	       <form action="index.php">
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="akzeptieren_<?echo $row["ID"]?>">
	       <input type="submit" value="akzeptieren">  
	       </form>
	       <?}?>
	       <?if($show_buttons){?>
	       <form action="index.php" >
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="abtauschen_<?echo $row["ID"]?>">
	       <input type="submit" value="geht nicht">  
	       </form>
	       <?}?>
	       </font>
	    <?
	    } else {
		    ?>
		    <font color="<?echo $color_not_accepted?>">
		    Noch nicht akzeptiert
		    
		    <?
                    echo "(".$row["name"].")</font>";
	            if( $soon){

		       ?>
	               <?if($show_buttons){?>
		       <form action="index.php">
		       <input type="hidden" name="area" value=<?echo $area?>>
		       <input type="hidden" name="aktion" value="uebernehmen_<?echo $row["ID"]?>">
		       <input  type="submit" value="übernehmen">  
		       </form>
		       <?
		       }
	           }

	    }
       	    break;
       case "Nicht geleistet":
       	    break;
       case "Offen":
	    ?>
	       <font color=<?echo $color_not_accepted?>>Offener Dienst </font>
	       <?if($show_buttons){?>
	       <form action="index.php">
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="uebernehmen_<?echo $row["ID"]?>">
	       <input  type="submit" value="übernehmen">  
	       </form>
	    <?
	       }
       	    break;
       case "Geleistet":
            echo "<font color=".$color_norm.">".$row["name"]." ".$row["telefon"]."</font>";
       	    break;
       case "Akzeptiert":
            $color_use = $color_not_confirmed;

       case "Bestaetigt":
            if(!isset($color_use)){
	    	$color_use = $color_norm;
	    }
            echo "<font color=".$color_use.">".$row["name"]." ".$row["telefon"]."</font>";
       	    if($gruppe == $row["GruppenID"]){
	    ?>
	       <?if($show_buttons){?>
	       <form action="index.php" >
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="wirdoffen_<?echo $row["ID"]?>">
	       <input type="submit" value="kann doch nicht">  
	       </form>
	    <?
	       }
	    } else if($row["Status"]=="Akzeptiert" & $soon){

	       ?>
	       <?if($show_buttons){?>
	       <form action="index.php">
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="uebernehmen_<?echo $row["ID"]?>">
	       <input  type="submit" value="übernehmen">  
	       </form>
	       <?
	       }
	    }
	    
	    break;
       }
}
/**
 *  Zeigt ein Produkt als Bestellungsübersicht
 */
function areas_in_menu($area){
 ?>
   <tr>
       <td><input type="button" value="<? echo $area['title']?>" class="bigbutton" onClick="self.location.href='<? echo $area['area']?>'"></td>
	<td valign="middle" class="smalfont"><? echo $area['hint']?></td>
    </tr> 		 
  <?
}

function rotationsplanView($row){
 ?>
   <tr>
       <td>
       <b>
       <?echo $row['rotationsplanposition']?>
       </b>
       </td><td>
       <?echo $row['name']?>
       </td><td>
       <?if($row['rotationsplanposition']>1){?>
	<input type="submit" width="80" value="UP" name="up_<? echo $row['id']?>" > 
	<?}?>
       </td><td>
       <?if($row['rotationsplanposition']<sql_rotationsplan_extrem($row['diensteinteilung'])){?>
	<input type="submit" width="80" value="DOWN" name="down_<? echo $row['id']?>"  onClick="self.location.href='<? echo $_SERVER['PHP_SELF']?>'">
	<?}?>
	</td>
    </tr> 		 
  <?
    
}

// moegliche spalten:
//   1: produktname
//   2: V-Preis
//   4: MWst
//   8: Pfand
//  10: Netto-L-Preis
//  20: bestellmenge (*)
//  40: gebinde bestellt (*)
//  80: liefermenge (*)
// 100: gebinde geliefert (*)
// 200: gesamtpreis netto (*)
// 400: gesamtpreis brutto (*)
//
// mit $gruppen_id: (*) nur fuer diese gruppe
//
function products_overview($bestell_id, $editAmounts = FALSE, $editPrice = FALSE, $spalten = FALSE, $gruppen_id = false ) {
  global $area, $print_on_exit;

  $result1 = sql_bestellprodukte($bestell_id,$gruppen_id);
  $state = getState($bestell_id);
  switch($state) {
    case STATUS_BESTELLEN:
      if( $gruppen_id ) {
        $default_spalten = 0x63f;
        $max_spalten = 0x67f;
      } else {
        $default_spalten = 0x67f;
        $max_spalten = 0x67f;
      }
      break;
    case STATUS_LIEFERANT:
    case STATUS_VERTEILT:
    default:
      if( $gruppen_id ) {
        $default_spalten = 0x6bf;
        $max_spalten = 0x7ff;
      } else {
        $default_spalten = 0x79f;
        $max_spalten = 0x7ff;
      }
      break;
  }
  if( ! $spalten ) {
    $spalten = $spalten & $max_spalten;
  } else {
    $spalten = $default_spalten;
  }

  if( $editAmounts ) {
    echo "<form action='index.php' method='post'>";
  }
  echo "<table class='numbers' width='100%'><tr class='legende'>";
  $cols = 0;
  if( $spalten & 0x1 ) {
    echo "<th>Produkt<br>&nbsp;<!-- doppelzeile erzwingen --></th>";
    $cols += 1;
  }
  if( $spalten & 0x2 ) {
    echo "<th title='Endpreis pro V-Einheit' colspan='2'>V-Preis</th>";
    $cols += 2;
  }
  if( $spalten & 0x4 ) {
    echo "<th>MWSt</th>";
    $cols += 1;
  }
  if( $spalten & 0x8 ) {
    echo "<th>Pfand</th>";
    $cols += 1;
  }
  if( $spalten & 0x10 ) {
    echo "<th title='Nettopreis beim Lieferanten' colspan='2'>L-Preis</th>";
    $cols += 2;
  }
  if( $spalten & 0x20 ) {
    if( $gruppen_id) {
      echo "<th colspan='2'>bestellt<br>fest(Toleranz)</th>";
    } else {
      echo "<th title='von Konsumenten bestellte Mengen: fest (Toleranz,Basar)' colspan='2'
       >bestellt<br>fest (Toleranz,Basar)</th>";
     }
    $cols += 2;
  }
  if( $spalten & 0x40 ) {
    echo "<th title='von Konsumenten bestellte Gebinde: fest / maximal' colspan='2'
     >bestellt Gebinde<br>fest / maximal</th>";
    $cols += 2;
  }
  if( $spalten & 0x80 ) {
    if( $gruppen_id) {
      echo "<th colspan='2'>Zuteilung</th>";
      $cols += 2;
    } else {
      echo "<th colspan='3'>Liefermenge</th>";
      $cols += 3;
    }
  }
  if( $spalten & 0x100 ) {
    echo "<th colspan='2'>Liefer-Gebinde</th>";
    $cols += 2;
  }
  $cols_vor_summe = $cols;
  if( $spalten & 0x200 ) {
    echo "<th title='Netto-Gesamtpreis (ohne MWSt, ohne Pfand)'>Gesamt<br>Netto</th>";
    $cols += 1;
  }
  if( $spalten & 0x400 ) {
    echo "<th title='Brutto-Gesamtpreis (mit MWSt, ohne Pfand)'>Gesamt<br>Brutto</th>";
    $cols += 1;
  }

  $netto_summe = 0;
  $brutto_summe = 0;
  $nichtgeliefert_header_ausgeben = true;

  while  ($produkte_row = mysql_fetch_array($result1)) {
    $produkt_id =$produkte_row['produkt_id'];
    if( $produkte_row['liefermenge'] == 0 ) {
      if( ! $editAmounts && ! $gruppen_id )
        break;  // nicht gelieferte werden nicht mehr angezeigt
      if( $nichtgeliefert_header_ausgeben ) {
        if( $cols > $cols_vor_summe ) {
          ?>
            <tr id='row_total' class='summe'>
              <td colspan='<? echo $cols_vor_summe; ?>' style='text-align:right;'>Summe:</td>
          <?
          if( $spalten & 0x200 )
            echo "<td class='number'>".sprintf( "%8.2lf", $netto_summe )."</td>";
          if( $spalten & 0x400 )
            echo "<td class='number'>".sprintf( "%8.2lf", $brutto_summe )."</td>";
          ?>
            </tr>
          <?
        }
        ?>
          <tr>
            <th colspan='<? echo $cols ?>'>
              <img id='nichtgeliefert_knopf' class='button' src='img/close_black_trans.gif'
                onclick='nichtgeliefert_toggle();' title='Ausblenden'>
              </img>
              Nicht bestellte oder nicht gelieferte Produkte:
            </th>
          </tr>
        <?
        $nichtgeliefert_header_ausgeben = false;
      }
      echo "<tr name='trnichtgeliefert'";
    } else {
      echo "<tr name='geliefert'";
    }
    echo " id='row$produkt_id'>";

    preisdatenSetzen( & $produkte_row );

    $nettolieferpreis = $produkte_row['nettolieferpreis'];
    $bruttolieferpreis = $produkte_row['bruttolieferpreis'];
    $mengenfaktor = $produkte_row['mengenfaktor'];

    $gesamtbestellmenge = $produkte_row['gesamtbestellmenge'];
    $basarbestellmenge = $produkte_row['basarbestellmenge'];
    $toleranzbestellmenge = $produkte_row['toleranzbestellmenge'];
    $festbestellmenge = $gesamtbestellmenge - $toleranzbestellmenge - $basarbestellmenge;

    if( $state == STATUS_BESTELLEN ) {
      $liefermenge = $gesamtbestellmenge;
    } else {
      if( $gruppen_id ) {
        $liefermenge = $produkte_row['verteilmenge'] / $mengenfaktor;
      } else {
        $liefermenge = $produkte_row['liefermenge'] / $mengenfaktor;
      }
    }

    $nettogesamtpreis = sprintf( "%8.2lf", $nettolieferpreis * $liefermenge );
    $bruttogesamtpreis = sprintf( "%8.2lf", $bruttolieferpreis * $liefermenge );

    $gebindegroesse = $produkte_row['gebindegroesse'];

    echo "<td>{$produkte_row['produkt_name']}</td>";
    if( $spalten & 0x2 ) {
      echo "<td class='mult'>" . sprintf( "%8.2lf", $produkte_row['preis'] ) . "</td>";
      echo "<td class='unit'>/ {$produkte_row['kan_verteilmult']} {$produkte_row['kan_verteileinheit']}</td>";
    }
    if( $spalten & 0x4 ) {
      echo "<td class='number'>{$produkte_row['mwst']}</td>";
    }
    if( $spalten & 0x8 ) {
      echo "<td class='number'>{$produkte_row['pfand']}</td>";
    }
    if( $spalten & 0x10 ) {
      echo "<td class='mult'>";
      if($editPrice){
        echo "<a
          href=\"javascript:neuesfenster('index.php?window=terraabgleich?produktid=$produkt_id&bestell_id=$bestell_id','produktdetails');\"
          onclick=\"
            document.getElementById('row$produkt_id').className='modified';
            document.getElementById('row_total').className='modified';\"
            title='Preis oder Produktdaten &auml;ndern'
          >" . sprintf( "%8.2lf", $nettolieferpreis ) . "</a>
        ";
      } else {
        printf( "%8.2lf", $nettolieferpreis );
      }
      echo "</td><td class='unit'>/ {$produkte_row['preiseinheit']}</a></td>";
    }
    if( $spalten & 0x20 ) {
      if( $gruppen_id ) {
        echo "
          <td class='mult'>"
          . sprintf("%.0lf(%.0lf)", $festbestellmenge, $toleranzbestellmenge )
          . "</td>
          <td class='unit'>* {$produkte_row['kan_verteilmult']} {$produkte_row['kan_verteileinheit']}</td>
        ";
      } else {
        echo "
          <td class='mult'>"
          . sprintf("%.0lf (%.0lf,%.0lf)", $festbestellmenge, $toleranzbestellmenge,$basarbestellmenge )
          . "</td>
          <td class='unit'>* {$produkte_row['kan_verteilmult']} {$produkte_row['kan_verteileinheit']}</td>
        ";
      }
    }
    if( $spalten & 0x40 ) {
      echo "
        <td class='mult'>" . sprintf( "%.2lf", $festbestellmenge / $gebindegroesse ) . "</td>
        <td class='unit'>/ " . sprintf( "%.2lf", $gesamtbestellmenge / $gebindegroesse ) . "</td>
      ";
    }
    if( $spalten & 0x80 ) {
      echo "<td class='mult'>";
      if( $editAmounts && ! $gruppen_id ) {
        echo "
          <input name='liefermenge$produkt_id' type='text' size='5' value='$liefermenge'
            onchange=\"
              document.getElementById('row$produkt_id').className='modified';
              document.getElementById('row_total').className='modified';\"
            title='tats&auml;chliche Liefermenge eingeben'
          >
        ";
      } else {
        echo $liefermenge;
      }
      echo "
        </td>
        <td class='unit' style='border-right-style:none;'>{$produkte_row['preiseinheit']}</td>
      ";
      if( ! $gruppen_id ) {
        echo "
          <td style='border-left-style:none;'><a class='png' style='padding:0pt 1pt 0pt 1pt;'
            href=\"javascript:neuesfenster('index.php?window=showBestelltProd&bestell_id=$bestell_id&produkt_id=$produkt_id','produktverteilung')\"
            title='Details zur Verteilung'
            ><img src='img/b_browse.png' style='border-style:none;padding:1px 0.5ex 1px 0.5ex;'
               title='Details zur Verteilung' alt='Details zur Verteilung'
            ></a></td>
        ";
      }
    }
    if( $spalten & 0x100 ) {
      echo "
        <td class='mult'>"
        . sprintf( "%.2lf", $produkte_row['liefermenge'] / $gebindegroesse ) . " * </td>
        <td class='unit'>(" . $produkte_row['kan_verteilmult'] * $produkte_row['gebindegroesse']
                         . " {$produkte_row['kan_verteileinheit']})</td>
      ";
    }
    if( $spalten & 0x200 ) {
      echo "<td class='number'>$nettogesamtpreis</td>";
    }
    if( $spalten & 0x400 ) {
      echo "<td class='number'>$bruttogesamtpreis</td>";
    }
    echo "</tr>";

    $netto_summe += $nettogesamtpreis;
    $brutto_summe += $bruttogesamtpreis;

  } //end while produkte array            

  if( $nichtgeliefert_header_ausgeben ) {
        // summe muss noch angezeigt werden:
    if( $cols > $cols_vor_summe ) {
      ?>
        <tr id='row_total' class='summe'>
          <td colspan='<? echo $cols_vor_summe ?>' style='text-align:right;'>Summe:</td>
      <?
      if( $spalten & 0x200 ) {
        printf( "<td class='number'>%8.2lf</td>", $netto_summe );
      }
      if( $spalten & 0x400 ) {
        printf( "<td class='number'>%8.2lf</td>", $brutto_summe );
      }
      echo "</tr>";
    }
  }

  if($editAmounts){
    ?>
        <tr style='border:none'>
          <td colspan='<? echo $cols ?>'>
            <input type='hidden' name='area' value='$area'>
            <input type='hidden' name='bestellungs_id' value='$bestell_id'>
            <input type='submit' value='Liefermengen ändern'>
            <input type='reset' value='Änderungen zurücknehmen'>
          </td>
        </tr>
      </table>
      </form>
    <?
  } else {
    echo "</table>";
  };

  $print_on_exit = "$print_on_exit
    <script type='text/javascript'>
      nichtgeliefert_zeigen = 1;
      function nichtgeliefert_toggle() {
        nichtgeliefert_zeigen = !  nichtgeliefert_zeigen;
        if( nichtgeliefert_zeigen ) {
          rows = document.getElementsByName('trnichtgeliefert');
          i=0;
          while( rows[i] ) {
            rows[i].style.display = '';
            i++;
          }
          document.getElementById('nichtgeliefert_knopf').src = 'img/close_black_trans.gif';
          document.getElementById('nichtgeliefert_knopf').title = 'Ausblenden';
        } else {
          rows = document.getElementsByName('trnichtgeliefert');
          i=0;
          while( rows[i] ) {
            rows[i].style.display = 'none';
            i++;
          }
          document.getElementById('nichtgeliefert_knopf').src = 'img/open_black_trans.gif';
          document.getElementById('nichtgeliefert_knopf').title = 'Einblenden';
        }
      }
    </script>
  ";
}


/**
 * Gibt einen einzelnen Preis mit Pfand und Mehrwertsteuer aus
 * Argument: mysql_fetch_array(sql_produktpreise2())
 */

function preis_view($pr){
        printf( "%5.2lf", ( $pr['preis'] - $pr['pfand'] ) / ( 1.0 + $pr['mwst'] / 100.0 ) );
				echo "&nbsp;({$pr['preis']},{$pr['mwst']},{$pr['pfand']})";

}

/**
 * Erzeugt eine Auswahl für alle Preise eines Produktes
 */
function preis_selection($produkt_id, $current_preis_id){
	$selectpreis = "preis".$produkt_id;

		?>
                <select name="<? echo($selectpreis)?>"> 
	       		<?
			   $preise=sql_produktpreise2($produkt_id);
			   while($pr = mysql_fetch_array($preise)){
				$sel = "";
			   	if($pr['id']==$current_preis_id ){
					$sel = " selected=\"selected\"";
					$preis =$pr['preis'];
				}
				echo "<option value='{$pr['id']}' $sel>";
				preis_view($pr);
        			echo "</option>\n";

			   }
	       
	       		?>
	   	     </select>
		     <?
		     return $preis;
}


/**
 * Ausgabe der Links im Foodsoft-Kopf
 */
function areas_in_head($area){

?>
  <li>
  <a href="<? echo $area['area']?>" class="first" title="<? echo $area['hint']?>"><? echo $area['title']?></a> </li>
<?
}
/**
 * Liste zur Auswahl einer Bestellung via Link
 */
function select_bestellung_view( $result, $head="Bitte eine Bestellung wählen:", $editDates = false, $changeState = false ) {
  global $self, $self_fields, $self_form, $foodsoftdir;

      echo "<h1>".$head."</h1>";
      // $span =  count($area);
      ?>
      <br /> <br />
	     <table style="width:100%" class="liste">
		  <tr>
		    <th>Name</th>
                    <th>Status</th>
		    <th>Beginn</th>
		    <th>Ende</th>
                    <th>Lieferung</th>
        <!-- <th>Ausgang</th>
        <th>Bezahlung</th> -->
		    <th> Detailansicht </th>
      <?
      if( $changeState )
        echo "<th> Aktionen </th>";
      echo "</tr>";

		 while ($row = mysql_fetch_array($result)) {
       $id = $row['id'];
       $detail_url = "javascript:neuesfenster('$foodsoftdir/index.php?window=bestellschein&bestell_id=$id','bestellschein');";
       $fax_url = "javascript:neuesfenster('$foodsoftdir/index.php?download=bestellt_faxansicht&bestell_id=$id','bestellfax');";
       $self_form = "<form action='$self' method='post'>$self_fields";
		 ?>
		 <tr>                                 
		    <td><?echo $row['name']?></td>
		    <td><? echo $row['state']; ?></td>
		    <td><? echo $row['bestellstart']; ?></td>
		    <td><?
			if($editDates && ( $row['state'] == 'bestellen' ) ) {
				date_time_selector($row['bestellende'], "ende");
			} else {
				echo $row['bestellende']; 
			}
		  ?>
                    </td>
		    <td><? echo $row['lieferung']; ?></td>
<!--
		    <td><? echo $row['ausgang']; ?></td>
		    <td><? echo $row['bezahlung']; ?></td>
-->
      <?
      switch( $row['state'] ) {
        case 'bestellen':
          ?>
            <td><a href="<? echo "$detail_url"; ?>">Bestellschein (vorl&auml;ufig)</a></td>
          <?
          if( $changeState ) {
            ?> <td> <?
            if( $hat_dienst_IV ) {
              echo "$self_form";
              ?>
                  <input type='hidden' name='action' value='changeState'>
                  <input type='hidden' name='change_id' value='<? echo "$id"; ?>'>
                  <input type='hidden' name='change_to' value='beimLieferanten'>
                  <input type='submit' class='button' name='submit' value='>>> Bestellschein erstellen >>>'>
                </form>
              <?
            }
            ?> </td> <?
          }
          break;
        case 'beimLieferanten':
          ?>
            <td><a href="<? echo "$detail_url"; ?>">Bestellschein</a>
            ---
            <a href="<? echo "$fax_url"; ?>">Bestell-Fax (.pdf)</a></td>
          <?
          if( $changeState ) {
            ?> <td> <?
            if( $hat_dienst_IV ) {
              echo "$self_form";
              ?>
                  <input type='hidden' name='action' value='changeState'>
                  <input type='hidden' name='change_id' value='<? echo "$id"; ?>'>
                  <input type='hidden' name='change_to' value='bestellen'>
                  <input type='submit' class='button' name='submit'
                    title='Bestellung nochmal zum Bestellen freigeben'
                    value='<<< Nachbestellen <<<'>
                </form>
              <?
            }
            if( $dienst > 0 ) {
              echo "$self_form";
              ?>
                  <input type='hidden' name='action' value='changeState'>
                  <input type='hidden' name='change_id' value='<? echo "$id"; ?>'>
                  <input type='hidden' name='change_to' value='Verteilt'>
                  <input type='submit' class='button' name='submit'
                    title='Bestellung wurde geliefert: Lieferschein erstellen'
                    value='>>> Lieferschein erstellen >>>'>
                </form>
              <?
            }
            ?> </td> <?
          }
          break;
        case 'Verteilt':
          ?>
            <td><a href="<? echo "$detail_url"; ?>">Lieferschein</a>
            ---
            <a href="<? echo "$verteil_url"; ?>">Verteil-Liste</a></td>
          <?
          if( $changeState ) {
            ?>
              <td> - </td>
            <?
          }
          break;
        case 'archiviert':
        default:
          ?>
            <td>(keine Details verf&uuml;gbar)</td>
          <?
          if( $changeState ) {
            ?>
              <td> - </td>
            <?
          }
          break;
      }
			
      // reset($area);
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
               <th><?echo $name?></th>
               <th colspan='2'>bestellt (toleranz)</th>
               <th colspan='2'>geliefert</th>
               <th colspan='2'>Einzelpreis</th>
               <th>Gesamtpreis</th>
            </tr>
 
  <?
}
function distribution_view($name, $festmenge, $toleranz, $verteilmenge, $verteilmult, $verteileinheit, $preis
  , $inputbox_name = false, $summenzeile = false ){
  if( $summenzeile )
    echo "<tr class='summe'>";
  else
    echo "<tr>";
  echo "
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
function bestellung_overview($row, $showGroup=FALSE, $gruppen_id = NULL){
	 ?>
         <table class="info">
               <tr>
                   <th> Bestellung: </th>
                     <td style="font-size:1.2em;font-weight:bold"><?PHP echo $row['name']; 
		     if(sql_dienste_nicht_bestaetigt($row['lieferung'])){
		     	  ?><br> <b>Vorsicht:</b> <a href=index.php?area=dienstplan>Dienstegruppen abwesend?</a>
		     <? } ?>

		     </td>
                </tr>
               <tr>
                   <th> Bestellbeginn: </th>
                     <td><?PHP echo $row['bestellstart']; ?></td>
                </tr>
               <tr>
                   <th> Bestellende: </th>
                     <td><?PHP echo $row['bestellende']; ?></td>
                </tr>                
		<?
		if($showGroup){
		?>
		<tr>
		    <th> Gruppe: </th>
		  <td>
                <?PHP
                  if( $gruppen_id == 99 )
                    echo "<span class='warn'> BASAR </span>";
                  else
                    echo sql_gruppenname($gruppen_id);
                ?>
                </td>
	      </tr>	
	      <tr>
	          <th> Kontostand: </th>
	          <td><?PHP 
			// überprüfen ob negeativer kontostand. wenn ja, dann rot und fett !!
			$kontostand = kontostand($gruppen_id);
			if ($kontostand < "0") { 
				echo "<span style=\"color:red; font-weight:bold\">".sprintf("%.02f",$kontostand)."</span>"; 
			} else {
				echo "<span style=\"color:green; font-weight:normal\">".sprintf("%.02f",$kontostand)."</span>"; 
			}	
	      	      ?>
		 </td>
	     </tr>	

		<?
		}
		?>
            </table>
	    <br/>
	    <?
}

?>
