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
  echo "<!-- sql_date :$sql_date -->";
	$datum = date_parse($sql_date);

?>     <table class='inner'>
                  <tr>
                     <td><label>Datum:</label></td>
                      <td style='white-space:nowrap;'>
         <?date_selector($prefix."_tag", $datum['day'],$prefix."_monat", $datum['month'], $prefix."_jahr", $datum['year'])?>
                     </td>
       </tr>
<? if( $show_time ) { ?>
       <tr>
                 <td><label>Zeit:</label></td>
                         <td style='white-space:nowrap;'>
         <?time_selector($prefix."_stunde", $datum['hour'],$prefix."_minute", $datum['minute'])?>
                         </td>
                     </tr>
<? } ?>
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


function basar_overview( $bestell_id = 0, $order = 'produktname', $editAmounts = false ) {
  global $self_fields, $gruppe, $pfand, $specialgroups;

  if( $editAmounts ) {
    echo "<form action='" . self_url() . "' method='post'>" . self_post();
    $cols=9;
  } else {
    $cols=8;
  }

  ?> <table style='width: 600px;' class='numbers'> <?

  if( $editAmounts ) {
    ?>
      <tr>
        <td colspan='2'> Gruppe: 
          <select name='gruppe'>
          <option value='' selected>(Gruppe w&auml;hlen)</option>
          <? echo optionen_gruppen( false, false, false, false, false, $specialgroups ); ?>
          </select>
        </td>
        <td colspan='<? echo $cols-2; ?>' style='text-align:right;padding-bottom:1ex;'>
          Glasr&uuml;ckgabe zu je <? echo $pfand; ?> Euro (Anzahl eintragen):	<input name='menge_glas' type='text' size='3' />
        </td>
      </tr>
    <?
  }

  $legend = array(
    "<th><a href='" . self_url('orderby') . "&orderby=produktname'
      title='Sortieren nach Produkten'>Produkt</a></th>"
  , "<th><a href='" . self_url('orderby') . "&orderby=bestellung'
      title='Sortieren nach Bestellung'>Bestellung</a></th>"
  , "<th><a href='" . self_url('orderby') . "&orderby=datum'
      title='Sortieren nach Lieferdatum'>Lieferdatum</a></th>"
  , "<th colspan='2'>Preis</th>"
  , "<th colspan='3'>Menge im Basar</th>"
  , ( $editAmounts ? "<th>Zuteilung</th>" : "" )
  );
  switch( $order ) {
    case 'bestellung':
      $rowformat='%2$s%1$s%3$s%4$s%5$s%6$s';
      $keyfield=1;
      break;
    case 'datum':
      $rowformat='%3$s%1$s%2$s%4$s%5$s%6$s';
      $keyfield=2;
      break;
    default:
    case 'produktname':
      $rowformat='%1$s%2$s%3$s%4$s%5$s%6$s';
      $keyfield=0;
      break;
  }
  vprintf( "<tr class='legende'>$rowformat</tr>", $legend );

  $result = sql_basar( $bestell_id, $order );

  $last_key = '';
  $row_index=0;
  $js = '';
  $fieldcount=0;
  while  ($basar_row = mysql_fetch_array($result)) {
     kanonische_einheit( $basar_row['verteileinheit'], & $kan_verteileinheit, & $kan_verteilmult );
     $menge=$basar_row['basar'];
     // umrechnen, z.B. Brokkoli von: x * (500g) nach (x * 500) g:
     $menge *= $kan_verteilmult;

     $row = array( 
       "<td>{$basar_row['produkt_name']}</td>"
     , "<td><a
           href=\"javascript:neuesfenster('index.php?window=bestellschein&bestell_id={$basar_row['gesamtbestellung_id']}','bestellschein')\"
             title='zum Lieferschein...'>{$basar_row['bestellung_name']}</a></td>"
     , "<td>{$basar_row['lieferung']}</td>"
     , "<td class='mult'>" . sprintf( "%8.2lf", $basar_row['preis'] ) . "</td>
         <td class='unit'>/ $kan_verteilmult $kan_verteileinheit</td>"
     , "<td class='mult'><b>$menge</b></td>
        <td class='unit' style='border-right-style:none;'>$kan_verteileinheit</td>"
     , "<td style='border-left-style:none;'><a 
            href=\"javascript:neuesfenster('index.php?window=showBestelltProd&bestell_id={$basar_row['gesamtbestellung_id']}&produkt_id={$basar_row['produkt_id']}','produktverteilung');\"
            ><img src='img/b_browse.png' border='0' title='Details zur Verteilung' alt='Details zur Verteilung'
            ></a></td>
         "
         . ( $editAmounts ?
             "<td class='unit'>
              <input type='hidden' name='produkt$fieldcount' value='{$basar_row['produkt_id']}'>
              <input type='hidden' name='bestellung$fieldcount' value='{$basar_row['gesamtbestellung_id']}'>
              <input name='menge$fieldcount' type='text' size='5' /> $kan_verteileinheit</td>"
           : ""
           )
     );
     $fieldcount++;

     // sortierschluessel nur einmal ausgeben:
     //
     if( $last_key == $row[$keyfield] ) {
       $rowspan++;
       $js = "
         <script type='text/javascript'>
         document.getElementById('row$row_index').rowSpan=$rowspan;
         </script>
       ";
       $row[$keyfield] = "";
     } else {
       echo $js;
       $js = '';
       $last_key = $row[$keyfield];
       $row_index++;
       $rowspan=1;
       $row[$keyfield] = preg_replace( "/^<td/", "<td id='row$row_index'", $row[$keyfield], 1 );
     }
     vprintf( "<tr>$rowformat</tr>\n", $row );
  }
  echo $js;

  if( $editAmounts ) {
    ?>
      <tr style='border:none'>
        <td colspan='<? echo $cols; ?>' style='border:none;padding-top:1ex;'>
          <input type='submit' value=' Neu laden / Basareintrag &uuml;bertragen '>
          <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen'>
        </td>
      </tr>
      </table>                   
      </form>
    <?
  } else {
    ?> </table> <?
  }
}

// products_overview:
// uebersicht ueber bestellte und gelieferte mengen einer Bestellung anzeigen
// moegliche Tabellenspalten:
define( 'PR_COL_NAME' , 0x1 );           // produktname
define( 'PR_COL_ANUMMER', 0x2 );      // Artikelnummer
define( 'PR_COL_BNUMMER', 0x4 );      // Bestellnummer
define( 'PR_COL_LPREIS', 0x8 );          // Netto-L-Preis
define( 'PR_COL_MWST', 0x10 );            // Mehrwertsteuersatz
define( 'PR_COL_PFAND', 0x20 );           // Pfand
define( 'PR_COL_VPREIS', 0x40 );         // V-Preis
define( 'PR_COL_BESTELLMENGE', 0x80 );   // bestellte menge (1)
define( 'PR_COL_BESTELLGEBINDE', 0x100 ); // bestellte Gebinde (1)
define( 'PR_COL_LIEFERMENGE', 0x200 );    // gelieferte Menge (1,2)
define( 'PR_COL_LIEFERGEBINDE', 0x400 ); // gelieferte Gebinde(1,2)
define( 'PR_COL_NETTOSUMME', 0x800 );    // Gesamtpreis Netto (1,3)
define( 'PR_COL_BRUTTOSUMME', 0x1000 );   // Gesamtpreis Brutto (1,3)
define( 'PR_COL_ENDSUMME', 0x2000 );      // Endpreis (mit Pfand) (1,3)
//
// (1) mit $gruppen_id: Anzeige nur fuer diese gruppe
// (2) nur moeglich ab STATUS_LIEFERANT
// (3) bei STATUS_BESTELLEN: berechnet aus Bestellmenge, sonst aus Liefermenge
//
define( 'PR_ROWS_NICHTGELIEFERT', 0x4000 ); // nicht gelieferte Produkte auch anzeigen
define( 'PR_ROWS_NICHTGEFUELLT', 0x8000 ); // nicht gefuellte gebinde auch anzeigen?
//
// $select_columns: menue zur auswahl der (moeglichen) Tabellenspalten generieren.
// $select_nichtgeliefert: option anzeigen, ob auch nichtgelieferte angezeigt werden
//
function products_overview(
    $bestell_id, $editAmounts = FALSE, $editPrice = FALSE, $spalten = 0xfff, $gruppen_id = false,
    $select_columns = false, $select_nichtgeliefert = false
  ) {
  global $self_fields;

  $result = sql_bestellprodukte($bestell_id,$gruppen_id);
  $state = getState($bestell_id);

  $warnung_vorlaeufig = "";
  if( $gruppen_id and ( $state == STATUS_BESTELLEN ) ) {
    $warnung_vorlaeufig = " (vorläufige obere Abschätzung!)";
  }
  $col[PR_COL_NAME] = array(
    'header' => "Produkt", 'title' => "Produktname", 'cols' => 1
  );
  $col[PR_COL_ANUMMER] = array(
    'header' => "A-Nr.", 'title' => "Artikelnummer", 'cols' => 1
  );
  $col[PR_COL_BNUMMER] = array(
    'header' => "B-Nr.", 'title' => "Bestellnummer", 'cols' => 1
  );
  $col[PR_COL_LPREIS] = array(
    'header' => "L-Preis", 'title' => "Nettopreis (ohne MWSt und Pfand) beim Lieferanten", 'cols' => 2
  );
  $col[PR_COL_MWST] = array(
    'header' => "MWSt", 'title' => "Mehrwertsteuersatz in Prozent", 'cols' => 1
  );
  $col[PR_COL_PFAND] = array(
    'header' => "Pfand", 'title' => "Pfand pro V-Einheit", 'cols' => 1
  );
  $col[PR_COL_VPREIS] = array(
    'header' => "V-Preis", 'title' => "Endpreis (mit MWSt und Pfand) pro V-Einheit", 'cols' => 2
  );
  $col[PR_COL_NETTOSUMME] = array(
    'header' => "Gesamt<br>Netto", 'cols' => 1,
    'title' => "Netto-Gesamtpreis (ohne MWSt, ohne Pfand)$warnung_vorlaeufig"
  );
  $col[PR_COL_BRUTTOSUMME] = array(
    'header' => "Gesamt<br>Brutto", 'cols' => 1,
    'title' => "Brutto-Gesamtpreis (mit MWSt, ohne Pfand)$warnung_vorlaeufig"
  );
  $col[PR_COL_ENDSUMME] = array(
    'header' => "Gesamt<br>Endpreis", 'cols' => 1,
    'title' => "Konsumenten-Gesamtpreis (mit MWSt, mit Pfand)$warnung_vorlaeufig"
  );

  if( $gruppen_id ) {
    $col[PR_COL_BESTELLMENGE] = array(
     'title' => "von der Gruppe bestellte Mengen: fest/Toleranz",
     'header' => "bestellt<br>fest/Toleranz", 'cols' => 2
    );
    $col[PR_COL_BESTELLGEBINDE] = array(
     'title' => "von der Gruppe bestellte Gebinde: fest / maximal",
     'header' => "bestellt Gebinde<br>fest/maximal</th>", 'cols' => 2
    );
    if( $state != STATUS_BESTELLEN ) {
      $col[PR_COL_LIEFERMENGE] = array(
        'title' => "der Gruppe zugeteilte Menge", 'header' => "Zuteilung", 'cols' => 2
      );
    }
    $option_nichtgefuellt = false;
  } else {
    $col[PR_COL_BESTELLMENGE] = array(
     'title' => "von Konsumenten bestellte Mengen: fest/Toleranz/Basar",
     'header' => "bestellt<br>fest/Toleranz/Basar", 'cols' => 2
    );
    if( $state == STATUS_BESTELLEN ) {
      $col[PR_COL_BESTELLGEBINDE] = array(
        'title' => "von Konsumenten und Basar bestellte Gebinde: aufgefüllt / fest /maximal",
        'header' => "bestellt Gebinde<br>voll / fest / max", 'cols' => 2
      );
      $option_nichtgefuellt = true;
    } else {
      $col[PR_COL_BESTELLGEBINDE] = array(
        'title' => "von Konsumenten und Basar bestellte Gebinde: fest /maximal",
        'header' => "bestellt Gebinde<br>fest / max", 'cols' => 2
      );
      if( $state == STATUS_LIEFERANT ) {
        $col[PR_COL_LIEFERMENGE] = array(
          'title' => "beim Lieferanten bestellte Menge", 'header' => "L-Menge", 'cols' => 4
        );
        $col[PR_COL_LIEFERGEBINDE] = array(
          'title' => "beim Lieferanten bestellte Gebinde", 'header' => "L-Gebinde", 'cols' => 2
        );
        $option_nichtgefuellt = true;
      } else {
        $col[PR_COL_LIEFERMENGE] = array(
          'title' => "vom Lieferanten gelieferte Menge", 'header' => "L-Menge", 'cols' => 4
        );
        $col[PR_COL_LIEFERGEBINDE] = array(
          'title' => "vom Lieferanten gelieferte Gebinde", 'header' => "L-Gebinde", 'cols' => 2
        );
        $option_nichtgefuellt = false;
      }
    }
  }

  if( $select_columns ) {
    $opts_insert="";
    $opts_drop="";
    for( $n=1 ; $n <= PR_COL_ENDSUMME; $n *= 2 ) {
      if( array_key_exists( $n, $col ) ) {
        $c = $col[$n];
        if( $spalten & $n ) {
          $opts_drop = "$opts_drop <option title='{$c['title']}' value='$n'>"
          . preg_replace( '/<br>/', ' ', $c['header'] ) . "</option>";
        } else {
          $opts_insert = "$opts_insert
            <option title='{$c['title']}' value='$n'>"
            . preg_replace( '/<br>/', ' ', $c['header'] ) . "</option>";
        }
      }
    }
    if( $opts_insert ) {
      option_menu_row( "
        <td>Spalten einblenden:</td><td>
        <select id='select_insert_cols'
          onchange=\"insert_col('" . self_url('spalten') . "',$spalten);\"
          ><option selected>(bitte wählen)</option>$opts_insert</select></td>
      " );
    }
    if( $opts_drop ) {
      option_menu_row( "
        <td>Spalten ausblenden:</td><td>
          <select id='select_drop_cols'
          onchange=\"drop_col('" . self_url('spalten') . "',$spalten);\"
           ><option selected>(bitte wählen)</option>$opts_drop</select></td>
      " );
    }
  }

  if( $editAmounts ) {
    echo "<form action='" . self_url() . "' method='post'>" . self_post();
  }
  ?> <table class='numbers' width='100%'><tr class='legende'> <?

  $cols = 0;
  $cols_vor_summe = 0;
  for( $n=1 ; $n <= PR_COL_ENDSUMME; $n *= 2 ) {
    if( $spalten & $n ) {
      if( array_key_exists( $n, $col ) ) {
        $c = $col[$n];
        echo "<th colspan='{$c['cols']}' title='{$c['title']}'>{$c['header']}</th>";
        $cols += $c['cols'];
        if( $n < PR_COL_NETTOSUMME )
          $cols_vor_summe += $c['cols'];
      } else {
        $spalten = ($spalten & ~$n);  // nicht definiert: bit loeschen!
      }
    }
  }

  if( $cols > $cols_vor_summe ) { // mindestens eine summenspalte ist aktiv
    $summenzeile = "
      echo \"<tr id='row_total' class='summe'>
              <td colspan='$cols_vor_summe' style='text-align:right;'>Summe:</td>\";
      if( \$spalten & PR_COL_NETTOSUMME )
        printf( \"<td class='number'>%8.2lf</td>\", \$netto_summe );
      if( \$spalten & PR_COL_BRUTTOSUMME )
        printf( \"<td class='number'>%8.2lf</td>\", \$brutto_summe );
      if( \$spalten & PR_COL_ENDSUMME )
        printf( \"<td class='number'>%8.2lf</td>\", \$endpreis_summe );
      echo '</tr>';
    ";
  } else {
    $summenzeile = '';
  }
  switch( $state ) {
    case STATUS_BESTELLEN:
    case STATUS_LIEFERANT:
      $nichtgeliefert_header = 'Nicht bestellte Produkte';
    break;
    case STATUS_VERTEILT:
    default:
      $nichtgeliefert_header = ( $gruppen_id ?
        'Nicht gelieferte oder zugeteilte Produkte' : 'Nicht gelieferte Produkte' );
    break;
  }

  $netto_summe = 0;
  $brutto_summe = 0;
  $endpreis_summe = 0;
  $haben_nichtgeliefert = false;
  $haben_nichtgefuellt = false;

  while  ($produkte_row = mysql_fetch_array($result)) {
    $produkt_id =$produkte_row['produkt_id'];

    if( $produkte_row['menge_ist_null'] && ! $haben_nichtgeliefert ) {
      $haben_nichtgeliefert = true;
      eval( $summenzeile );
      $summenzeile = '';
      if( $spalten & PR_ROWS_NICHTGELIEFERT ) {
        echo "<tr><th colspan='$cols'>$nichtgeliefert_header:</th></tr>";
      } else {
        break;
      }
    }

    preisdatenSetzen( & $produkte_row );

    // preise je V-einheit:
    $nettopreis = $produkte_row['nettopreis'];
    $bruttopreis = $produkte_row['bruttopreis'];
    $endpreis = $produkte_row['endpreis'];

    // preise je preiseinheit:
    $nettolieferpreis = $produkte_row['nettolieferpreis'];
    $bruttolieferpreis = $produkte_row['bruttolieferpreis'];
    $mengenfaktor = $produkte_row['mengenfaktor'];

    $gesamtbestellmenge = $produkte_row['gesamtbestellmenge'];
    $basarbestellmenge = $produkte_row['basarbestellmenge'];
    $toleranzbestellmenge = $produkte_row['toleranzbestellmenge'];

    // festbestellmenge enthaelt auch die "festen" basarbestellungen!
    $festbestellmenge = $gesamtbestellmenge - $toleranzbestellmenge - $basarbestellmenge;

    $gebindegroesse = $produkte_row['gebindegroesse'];
    $kan_verteilmult = $produkte_row['kan_verteilmult'];

    switch($state) {
      case STATUS_BESTELLEN:
        if( $gruppen_id ) {
          $liefermenge = $gesamtbestellmenge;  // obere abschaetzung...
          $gebinde = "ERROR";  // nicht sinnvoll
        } else {
          // voraussichtliche liefermenge berechnen:
          $gebinde = (int)($festbestellmenge / $gebindegroesse);
          $festmengenrest = $festbestellmenge - $gebinde * $gebindegroesse;
          if( $festmengenrest > 0 ) { // zu wenig: versuche aufzurunden...
            if( $festmengenrest + $basarbestellmenge + $toleranzbestellmenge >= $gebindegroesse ) {
              $gebinde += 1;
            }
          }
          $liefermenge = $gebinde * $gebindegroesse;
        }
        break;
      case STATUS_LIEFERANT:  // verteilmengen sollten jetzt zugewiesen sein:
      default:  // rien ne va plus...
        if( $gruppen_id ) {
          $liefermenge = $produkte_row['verteilmenge'];
        } else {
          $liefermenge = $produkte_row['liefermenge'];
        }
        $gebinde = $liefermenge / $gebindegroesse;  // nicht unbedingt integer!
        break;
    }
    $liefermenge_scaled = $liefermenge / $mengenfaktor;

    $nettogesamtpreis = sprintf( "%8.2lf", $nettopreis * $liefermenge );
    $bruttogesamtpreis = sprintf( "%8.2lf", $bruttopreis * $liefermenge );
    $endgesamtpreis = sprintf( "%8.2lf", $endpreis * $liefermenge );

    $netto_summe += $nettogesamtpreis;
    $brutto_summe += $bruttogesamtpreis;
    $endpreis_summe += $endgesamtpreis;

    if( $option_nichtgefuellt ) {
      if( $gebinde < 1 ) {
        $haben_nichtgefuellt = true;
        if( ! ( $spalten & PR_ROWS_NICHTGEFUELLT ) ) {
          continue;
        }
      }
    }

    ?> <tr> <?
    if( $spalten & PR_COL_NAME ) {
      echo "<td>{$produkte_row['produkt_name']}</td>";
    }
    if( $spalten & PR_COL_ANUMMER ) {
      echo "<td>{$produkte_row['artikelnummer']}</td>";
    }
    if( $spalten & PR_COL_BNUMMER ) {
      echo "<td>{$produkte_row['bestellnummer']}</td>";
    }
    if( $spalten & PR_COL_LPREIS ) {
      echo "<td class='mult'>";
      if($editPrice){
        echo "<a
          href=\"javascript:neuesfenster('index.php?window=terraabgleich&produkt_id=$produkt_id&bestell_id=$bestell_id','produktdetails');\"
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
    if( $spalten & PR_COL_MWST ) {
      echo "<td class='number'>{$produkte_row['mwst']}</td>";
    }
    if( $spalten & PR_COL_PFAND ) {
      echo "<td class='number'>{$produkte_row['pfand']}</td>";
    }
    if( $spalten & PR_COL_VPREIS ) {
      printf( "<td class='mult'>%8.2lf</td><td class='unit'>/ %s %s</td>"
        , $produkte_row['preis'], $produkte_row['kan_verteilmult'], $produkte_row['kan_verteileinheit']
      );
    }
    if( $spalten & PR_COL_BESTELLMENGE ) {
      printf(
        '<td class="mult">%1$.0lf / %2$.0lf' . ( $gruppen_id ? '' : ' / %3$.0lf' ) . '</td>
         <td class="unit">%4$s</td>'
      , $festbestellmenge * $kan_verteilmult
      , $toleranzbestellmenge * $kan_verteilmult
      , $basarbestellmenge * $kan_verteilmult
      , $produkte_row['kan_verteileinheit']
      );
    }
    if( $spalten & PR_COL_BESTELLGEBINDE ) {
      printf(
        "<td class='mult'>"
        . ( ($state == STATUS_BESTELLEN and ! $gruppen_id) ? '<b>%1$u</b> / ' : '' )
        . '%2$.2lf / %3$.2lf</td><td class="unit"> * (%4$s %5$s)</td>'
      , $gebinde
      , $festbestellmenge / $gebindegroesse
      , $gesamtbestellmenge / $gebindegroesse
      , $produkte_row['kan_verteilmult'] * $produkte_row['gebindegroesse']
      , $produkte_row['kan_verteileinheit']
      );
    }
    if( $spalten & PR_COL_LIEFERMENGE ) {
      echo "<td class='mult'>";
      if( $gruppen_id ) {    // Gruppenansicht: V-Einheit benutzen
        printf( "%d", $liefermenge * $kan_verteilmult );
        echo "
          </td>
          <td class='unit' style='border-right-style:none;'>{$produkte_row['kan_verteileinheit']}</td>
        ";
      } else {               // Gesamtansicht: Preis-Einheit benutzen:
        if( $editAmounts ) {
          printf( "
            <input name='liefermenge$produkt_id' style='text-align:right;' type='text' size='6' value='%.3lf'
              onchange=\"
                document.getElementById('row$produkt_id').className='modified';
                document.getElementById('row_total').className='modified';\"
              title='tats&auml;chliche Liefermenge eingeben'
            >"
          , $liefermenge_scaled
          );
	
        } else {
          printf( "%.3lf", $liefermenge_scaled );
        }
        echo "
          </td>
          <td class='unit' style='border-right-style:none;'>{$produkte_row['preiseinheit']}</td>
        ";
        if( $editAmounts ) {
	  //Checkbox für fehlende Lieferung. Löscht auch gleich 
		//die Einträge in der Verteiltabelle
        echo "
              <td style='border-left-style:none;border-right-style:none;'>
		<input  title='Wurde nicht geliefert' type='checkbox' name='nichtGeliefert[]' value='$produkt_id'>
	      </td>
        ";
	} else {
		echo " <td></td>";
	}

      }
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
    if( $spalten & PR_COL_LIEFERGEBINDE ) {
      printf(
        "<td class='mult'>%.2lf</td><td class='unit'> * (%s %s)</td>"
      , $gebinde
      , $produkte_row['kan_verteilmult'] * $produkte_row['gebindegroesse']
      , $produkte_row['kan_verteileinheit']
      );
    }
    if( $spalten & PR_COL_NETTOSUMME ) {
      echo "<td class='number'>$nettogesamtpreis</td>";
    }
    if( $spalten & PR_COL_BRUTTOSUMME ) {
      echo "<td class='number'>$bruttogesamtpreis</td>";
    }
    if( $spalten & PR_COL_ENDSUMME ) {
      echo "<td class='number'>$endgesamtpreis</td>";
    }
    ?> </tr> <?

  } //end while produkte array

  eval( $summenzeile );

  if($editAmounts){
    ?>
        <tr style='border:none'>
          <td colspan='<? echo $cols ?>'>
            <input type='submit' value='Speichern'>
            <input type='reset' value='Änderungen zurücknehmen'>
          </td>
        </tr>
      </table>
      </form>
    <?
  } else {
    ?> </table> <?
  };

  if( $haben_nichtgeliefert && $select_nichtgeliefert ) {
    option_menu_row( "
      <td colspan='2'>$nichtgeliefert_header zeigen:
        <input type='checkbox'
         " . ( ( $spalten & PR_ROWS_NICHTGELIEFERT ) ? ' checked' : '' ) . "'
         onclick=\"window.location.href='" . self_url('spalten') . "&spalten="
                 . ($spalten ^ PR_ROWS_NICHTGELIEFERT) . "';\"
         title='$nichtgeliefert_header vorhanden; diese auch anzeigen?'></td>
    " );
  }
  if( $option_nichtgefuellt && $haben_nichtgefuellt ) {
    option_menu_row( "
      <td colspan='2'>nicht-volle Gebinde zeigen:
        <input type='checkbox'
         " . ( ( $spalten & PR_ROWS_NICHTGEFUELLT ) ? ' checked' : '' ) . "'
         onclick=\"window.location.href='" . self_url('spalten') . "&spalten="
                 . ($spalten ^ PR_ROWS_NICHTGEFUELLT) . "';\"
         title='nicht gefuellte Gebinde vorhanden; diese auch anzeigen?'></td>
    " );
  }
}

// option_menu_row:
// fuegt eine zeile in die <table id="option_menu_table"> ein.
// die tabelle wird beim ersten aufruf erzeugt, und nach ausgabe des dokuments
// in ein beliebiges elternelement mit id="option_menu" verschoben:
//
function option_menu_row( $option = false ) {
  global $option_menu_counter, $print_on_exit;
  if( ! $option_menu_counter ) {
    // menu erstmal erzeugen (so dass wir einfuegen koennen):
    echo "<table class='info' id='option_menu_table'></table>";
    $option_menu_counter = 0;
    // positionieren erst ganz am schluss (wenn parent sicher vorhanden ist):
    $print_on_exit = $print_on_exit
    . "
      <script type='text/javascript'>
      var option_menu_parent, option_menu_table;
      option_menu_table = document.getElementById('option_menu_table');
      if( option_menu_table ) {
        option_menu_parent = document.getElementById('option_menu');
        if( option_menu_parent ) {
          option_menu_parent.appendChild(option_menu_table);
        }
      }
      </script>
    ";
  }
  if( $option ) {
    $option_menu_counter++;
    echo "
      <table>
        <tr id='option_entry_$option_menu_counter'>$option</tr>
      </table>
      <script type='text/javascript'>
    " .  move_html( 'option_entry_' . $option_menu_counter, 'option_menu_table' ) . "
      </script>
    ";
  }
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
  global $self, $self_fields, $foodsoftdir, $dienst, $login_gruppen_id, $hat_dienst_IV;

  if( $head )
    echo "<h1 style='margin-bottom:2em;'>$head</h1>";
?>
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
  if( $changeState || $editDates )
    echo "<th> Aktionen </th>";
  echo "</tr>";

  while ($row = mysql_fetch_array($result)) {
    $id = $row['id'];
    $detail_url = "javascript:neuesfenster('"
           . "$foodsoftdir/index.php?window=bestellschein"
           . "&bestell_id=$id"
           . "&gruppen_id=" . ( $dienst > 0 ? "0" : "$login_gruppen_id" )
           . "','bestellschein');";
    $fax_url = "javascript:neuesfenster('$foodsoftdir/index.php?download=bestellt_faxansicht&bestell_id=$id','bestellfax');";
    $verteil_url = "javascript:neuesfenster('$foodsoftdir/index.php?window=verteilung&bestellungs_id=$id','Verteil-Liste');";
    $self_form = "<form action='" . self_url() . "' name='self_form' method='post'>" . self_post();
    $edit_link = "<a class='png' style='padding:0pt 1ex 0pt 1ex;'
      href=\"javascript:window.open('index.php?window=editBestellung&bestell_id=$id','editBestellung','width=400,height=420,left=100,top=100').focus();\">
      <img src='img/b_edit.png' border='0' alt='Daten der Bestellung ändern' title='Daten der Bestellung ändern'>
      edieren...</a>
    ";
    $aktionen = "";

    ?>
      <tr id='row<?echo $id; ?>'>
      <td><?echo $row['name']?></td>
      <td><? echo $row['state']; ?></td>
      <td><? echo $row['bestellstart']; ?></td>
      <td><? echo $row['bestellende']; ?></td>
      <td><? echo $row['lieferung']; ?></td>
  <!--
      <td><? echo $row['ausgang']; ?></td>
      <td><? echo $row['bezahlung']; ?></td>
  -->
    <?
  
    switch( $row['state'] ) {
  
      case 'bestellen':
        ?>
          <td>
            <a href="<? echo "$detail_url"; ?>">Bestellschein (vorl&auml;ufig)</a>
          </td>
        <?
        if( $editDates )
          $aktionen .= "<li>$edit_link</li>";
        if( $changeState ) {
          if( $dienst == 4 ) {
            $aktionen .= "<li>$self_form
              <input type='hidden' name='action' value='changeState'>
              <input type='hidden' name='change_id' value='$id'>
              <input type='hidden' name='change_to' value='beimLieferanten'>
              <input type='submit' class='button' name='submit'
                title='Jetzt Bestellschein für Lieferanten fertigmachen?'
                value='> Bestellschein fertigmachen >'>
              </form></li>
            ";
          }
        }
        break;
  
      case 'beimLieferanten':
        ?>
          <td>
            <a href="<? echo "$detail_url"; ?>">Bestellschein</a>
            <? if( $hat_dienst_IV ) { ?>
              <br><a href="<? echo "$fax_url"; ?>">Bestell-Fax (.pdf)</a>
            <? } ?>
          </td>
        <?
        if( $editDates )
          $aktionen .= "<li>$edit_link</li>";
        if( $changeState ) {
          if( $hat_dienst_IV ) {
            $aktionen .= "<li>$self_form
              <input type='hidden' name='action' value='changeState'>
              <input type='hidden' name='change_id' value='$id'>
              <input type='hidden' name='change_to' value='bestellen'>
              <input type='submit' class='button' name='submit'
                title='Bestellung nochmal zum Bestellen freigeben?'
                value='< Nachbestellen lassen <'>
              </form></li>
            ";
          }
          if( $dienst > 0 ) {
            $aktionen .= "<li>$self_form
              <input type='hidden' name='action' value='changeState'>
              <input type='hidden' name='change_id' value='$id'>
              <input type='hidden' name='change_to' value='Verteilt'>
              <input type='submit' class='button' name='submit'
                title='Bestellung wurde geliefert, Lieferschein abgleichen?'
                value='> Lieferschein erstellen >'>
              </form></li>
            ";
          }
        }
        break;
  
      case 'Verteilt':
        ?>
          <td>
            <a href="<? echo "$detail_url"; ?>">Lieferschein</a>
            <?  if( $dienst > 0 ) { ?>
              <br><a href="<? echo "$verteil_url"; ?>">Verteil-Liste</a>
            <? } ?>
          </td>
        <?
        break;
  
      case 'archiviert':
      default:
        ?>
          <td>(keine Details verf&uuml;gbar)</td>
        <?
        break;
    }
    if( $changeState || $editDates ) {
      if( $aktionen )
        echo "<td><ul class='inner'>$aktionen</ul></td>";
      else
        echo "<td> - </td>";
    }
    echo "</tr>";
  }
  ?> </table> <?
}

function select_products_not_in_list($bestell_id){
	   echo "Produkt: <select name=\"produkt_id\"> ";
	 if($bestell_id!=0){
	   $produkte=getProdukteVonLieferant(getProduzentBestellID($bestell_id), $bestell_id);
	   while($prod = mysql_fetch_array($produkte)){
		echo "<option value=\"".$prod['produkt_id']."\">".
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


function formular_buchung_gruppe_bank(
  $gruppen_id = 0, $konto_id = 0, $auszug_jahr='', $auszug_nr = '', $notiz = 'Einzahlung'
) {
  ?>
    <form method='post' class='small_form'
          action='<? echo self_url(array('konto_id','auszug_jahr','auszug_nr','gruppen_id')); ?>'>
      <? echo self_post(array('konto_id','auszug_jahr','auszug_nr','gruppen_id')); ?>
      <input type='hidden' name='action' value='zahlung_gruppe'>
      <fieldset>
        <legend>
          Einzahlung / Auszahlung Gruppe
        </legend>
        <table>
          <tr>
            <td><label>Gruppe:</label></td>
            <td>
            <? if ( $gruppen_id ) { ?>
              <kbd>
                <? echo sql_gruppenname( $gruppen_id ); ?>
                <input type='hidden' name='gruppen_id' value='<? echo $gruppen_id; ?>'>
              </kbd>
            <? } else { ?>
              <select name='gruppen_id'><? echo optionen_gruppen( false, false, $gruppen_id ); ?></select>
            <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>Konto:</label></td>
            <td>
              <? if( $konto_id ) { ?>
                <kbd><? echo sql_kontoname( $konto_id ); ?>
                <input type='hidden' name='konto_id' value='<? echo $konto_id; ?>'>
                </kbd>
              <? } else { ?>
                <select name='konto_id'><? echo optionen_konten( $konto_id ); ?></select>
              <? } ?>
               &nbsp; <label>Auszug:</label>
              <? if( $auszug_nr ) { ?>
                <kbd><? echo "$auszug_jahr / $auszug_nr"; ?></kbd>
                <input type='hidden' name='auszug_jahr' value='<? echo $auszug_jahr; ?>'>
                <input type='hidden' name='auszug_nr' value='<? echo $auszug_nr; ?>'>
              <? } else { ?>
                  <input type='text' size='4' name='auszug_jahr' value='<? echo $auszug_jahr; ?>'> /
                  <input ty[e='text' size='2' name='auszug_nr' value='<? echo $auszug_nr; ?>'>
              <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label title'positiv: Einzahlung / negativ: Auszahlung!'>Betrag:</label></td>
            <td>
              <input type="text" name="betrag" value=""
              title='von der Gruppe eingezahlt: postiv / an die Gruppe ausgezahlt: negativ'>
            </td>
          </tr>
          <tr>
            <td>Notiz:</td>
            <td>
              <input type="text" size="60" name="notiz" value='<? echo $notiz; ?>'>
              &nbsp;
              <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
            </td>
          </tr>
        </table>
      </fieldset>
    </form>
  <?
  return true;
}

function formular_buchung_lieferant_bank(
  $lieferanten_id = 0, $konto_id = 0, $auszug_jahr='', $auszug_nr = ''
, $notiz = 'Abbuchung Lieferant'
) {
  ?>
    <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <input type='hidden' name='action' value='zahlung_lieferant'>
      <fieldset>
        <legend>
          Überweisung / Lastschrift Lieferant
        </legend>
        <table>
          <tr>
            <td><label>Lieferant:</label></td>
            <td>
              <? if( $lieferanten_id ) { ?>
                <kbd>
                  <? echo lieferant_name( $lieferanten_id ); ?>
                  <input type='hidden' name='lieferanten_id' value='<? echo $lieferanten_id; ?>'>
                </kbd><
              <? } else { ?>
                <select name='lieferanten_id'><? echo optionen_lieferanten(); ?></select>
              <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>Konto:</label></td>
            <td>
              <? if( $konto_id ) { ?>
                <kbd><? echo sql_kontoname( $konto_id ); ?>
                <input type='hidden' name='konto_id' value='<? echo $konto_id; ?>'>
                </kbd>
              <? } else { ?>
                <select name='konto_id'><? echo optionen_konten( $konto_id ); ?></select>
              <? } ?>
               &nbsp; <label>Auszug:</label>
              <? if( $auszug_nr ) { ?>
                <kbd><? echo "$auszug_jahr / $auszug_nr"; ?></kbd>
                <input type='hidden' name='auszug_jahr' value='<? echo $auszug_jahr; ?>'>
                <input type='hidden' name='auszug_nr' value='<? echo $auszug_nr; ?>'>
              <? } else { ?>
                  <input type='text' size='4' name='auszug_jahr' value='<? echo $auszug_jahr; ?>'> /
                  <input ty[e='text' size='2' name='auszug_nr' value='<? echo $auszug_nr; ?>'>
              <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label title'positiv: Einzahlung / negativ: Auszahlung!'>Betrag:</label></td>
            <td>
              <input type="text" name="betrag" value="">
              <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
            </td>
          </tr>
          <tr>
            <td>Notiz:</td>
            <td><input type="text" size="60" name="notiz" value='<? echo $notiz; ?>'>
              &nbsp;
              <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
            </td>
          </tr>
        </table>
      </fieldset>
    </form>
  <?
  return true;
}

function formular_buchung_gruppe_lieferant(
  $gruppen_id = 0, $lieferanten_id = 0, $notiz = 'Direktzahlung von Gruppe an Lieferant'
) {
  ?>
    <form method='post' class='small_form' action='<? echo self_url(array('gruppen_id','lieferanten_id')); ?>'>
      <? echo self_post(array('gruppen_id','lieferanten_id')); ?>
      <input type='hidden' name='action' value='zahlung_gruppe_lieferant'>
      <fieldset>
        <legend>
          Zahlung von Gruppe an Lieferant
        </legend>
        <table>
          <tr>
            <td><label>von Gruppe:</label></td>
            <td>
            <? if ( $gruppen_id ) { ?>
              <kbd>
                <? echo sql_gruppenname( $gruppen_id ); ?>
                <input type='hidden' name='gruppen_id' value='<? echo $gruppen_id; ?>'>
              </kbd>
            <? } else { ?>
              <select name='gruppen_id'><? echo optionen_gruppen( false, false, $gruppen_id ); ?></select>
            <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>an Lieferant:</label></td>
            <td>
              <? if( $lieferanten_id ) { ?>
                <kbd>
                  <? echo lieferant_name( $lieferanten_id ); ?>
                  <input type='hidden' name='lieferanten_id' value='<? echo $lieferanten_id; ?>'>
                </kbd><
              <? } else { ?>
                <select name='lieferanten_id'><? echo optionen_lieferanten(); ?></select>
              <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label>Betrag:</label></td>
            <td>
              <input type="text" name="betrag" value="">
            </td>
          </tr>
          <tr>
            <td>Notiz:</td>
            <td><input type="text" size="60" name="notiz" value='<? echo $notiz; ?>'>
              &nbsp;
              <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
            </td>
          </tr>
        </table>
      </fieldset>
    </form>
  <?
  return true;
}

function formular_buchung_gruppe_gruppe(
  $gruppen_id = 0, $nach_gruppen_id = 0, $notiz = 'Umbuchung von Gruppe an Gruppe'
) {
  ?>
    <form method='post' class='small_form' action='<? echo self_url('gruppen_id'); ?>'>
      <? echo self_post('gruppen_id'); ?>
      <input type='hidden' name='action' value='umbuchung_gruppe_gruppe'>
      <fieldset>
        <legend>
          Umbuchung von Gruppe an Gruppe
        </legend>
        <table>
          <tr>
            <td><label>von Gruppe:</label></td>
            <td>
            <? if ( $gruppen_id ) { ?>
              <kbd>
                <? echo sql_gruppenname( $gruppen_id ); ?>
                <input type='hidden' name='gruppen_id' value='<? echo $gruppen_id; ?>'>
              </kbd>
            <? } else { ?>
              <select name='gruppen_id'><? echo optionen_gruppen( false, false, $gruppen_id ); ?></select>
            <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>an Gruppe:</label></td>
            <td><select name='nach_gruppen_id'><? echo optionen_gruppen( false, false, $nach_gruppen_id ); ?></select></td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label>Betrag:</label></td>
            <td>
              <input type="text" name="betrag" value="">
            </td>
          </tr>
          <tr>
            <td>Notiz:</td>
            <td><input type="text" size="60" name="notiz" value='<? echo $notiz; ?>'>
              &nbsp;
              <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
            </td>
          </tr>
        </table>
      </fieldset>
    </form>
  <?
  return true;
}

function mod_onclick( $id ) {
  return $id ? " onclick=\"document.getElementById('$id').className='modified';\" " : '';
}

function formular_artikelnummer( $produkt_id, $can_toggle = false, $default_on = false, $mod_id = false ) {
  $produkt = sql_produkt_details( $produkt_id );
  $anummer = $produkt['artikelnummer'];
  $can_toggle or $default_on = true;
  if( $can_toggle ) {
    if( $default_on ) {
      $form_display = ''; $button_display = 'none';
    } else {
      $form_display = 'none'; $button_display = '';
    }
    ?>
      <span class='button' id='anummer_button' style='display:<? echo $button_display; ?>;'
        onclick="document.getElementById('anummer_button').style.display = 'none';
                 document.getElementById('anummer_form').style.display = 'block';"
      >Artikelnummer (<? echo $anummer; ?>) &auml;ndern...</span>
    <?
  } else {
    $form_display = '';
  }
  ?>
    <div style='display:<? echo $form_display; ?>;' id='anummer_form' class='small_form'>
      <!-- <form> -->
      <fieldset class='small_form'>
        <legend>
          <?
            if( $can_toggle ) {
              ?>
                <img class='button' src='img/close_black_trans.gif' title='Ausblenden'
                  onclick="document.getElementById('anummer_button').style.display='inline';
                  document.getElementById('anummer_form').style.display = 'none';"
                >
              <?
            }
          ?>
          Artikelnummer (<? echo $anummer; ?>) &auml;ndern:
        </legend>
        <table>
          <tr>
            <td> neue Artikel-Nr. setzen: </td>
            <td>
              <form method='post' action='<? echo self_url(); ?>'> 
                <? echo self_post(); ?>
                <input type='hidden' name='action' value='artikelnummer_setzen'>
                <input type='text' size='20' name='anummer' value='<? echo $anummer; ?>'>&nbsp;
                <input type='submit' name='Submit' value='OK' <? echo mod_onclick( $mod_id ); ?>>
              </form>
            </td>
          </tr>
          <tr>
            <td>
              ...oder: Katalogsuche nach:
            </td>
            <td>
              <form action='index.php?window=artikelsuche' method='post'>
                <input name='terracn' value='<? echo $produkt['name']; ?>' size='40'>&nbsp;<input type='submit' name='submit' value='Los!'
                  <? echo mod_onclick( $mod_id ); ?> >
                <input type='hidden' name='produkt_id' value='<? echo $produkt_id; ?>'>
              </form>
            </td>
          </tr>
        </table>
      </fieldset>
      <!-- </form> -->
    </div>
  <?
}

function action_button( $label, $title, $fields, $mod_id = false, $class = '' ) {
  ?><div class='<? echo $class; ?>' style='white-space:nowrap;padding:0.1ex 1ex 0.1ex 1ex;'>
      <form style='margin:0ex;padding:0ex;' method='post' action='<? echo self_url(); ?>'>
        <?
          echo self_post();
          foreach( $fields as $name => $value )
            echo "<input type='hidden' name='$name' value='$value'>";
          echo "<input style='padding:0ex;margin:0ex;' type='submit' name='submit' value='$label'";
          if( $mod_id )
            echo " onclick=\"document.getElementById('$mod_id').className='modified';\"";
          if( $title )
            echo " title='$title'";
        ?>
        ></form></div><?
}

// preishistorie_view:
//  - kann preishistorie anzeigen
//  - kann preisauswahl fuer eine bestellung erlauben
//
function preishistorie_view( $produkt_id, $bestell_id = 0, $editable = false, $mod_id = false ) {
  global $mysqljetzt;
  need( $produkt_id );
  if( $bestell_id ) {
    $bestellvorschlag = sql_bestellvorschlag_daten( $bestell_id, $produkt_id );
    $preisid_in_bestellvorschlag = $bestellvorschlag['preis_id'];
  }

  ?>
    <script type="text/javascript">
      preishistorie_status = 1;
      function preishistorie_toggle() {
        preishistorie_status = ! preishistorie_status;
        if( preishistorie_status ) {
          document.getElementById("preishistorie").style.display = "block";
          document.getElementById("preishistorie_knopf").src = "img/close_black_trans.gif";
          document.getElementById("preishistorie_knopf").title = "Ausblenden";
        } else {
          document.getElementById("preishistorie").style.display = "none";
          document.getElementById("preishistorie_knopf").src = "img/open_black_trans.gif";
          document.getElementById("preishistorie_knopf").title = "Einblenden";
        }
      }
    </script>
    <fieldset class='big_form'>
    <legend>
      <img id='preishistorie_knopf' class='button' src='img/close_black_trans.gif'
        onclick='preishistorie_toggle();' title='Ausblenden'>
  <?
  if( $bestell_id ) {
    ?> Preiseintrag wählen für Bestellung <?
    echo "$bestellung_name:";
  } else {
    ?> Preis-Historie: <?
  }
  ?>
    </legend>
    <div id='preishistorie'>
      <table width='100%' class='numbers'>
        <tr>
          <th title='Interne eindeutige ID-Nummer des Preiseintrags'>id</th>
          <th title='Bestellnummer'>B-Nr</th>
          <th title='Preiseintrag gültig ab'>von</th>
          <th title='Preiseintrag gültig bis'>bis</th>
          <th title='Liefer-Einheit: fürs Bestellen beim Lieferanten' colspan='2'>L-Einheit</th>
          <th title='Nettopreis beim Lieferanten' colspan='2'>L-Preis</th>
          <th title='Verteil-Einheit: f&uuml;rs Bestellen und Verteilen bei uns' colspan='2'>V-Einheit</th>
          <th title='Gebindegröße in V-Einheiten'>Gebinde</th>
          <th>MWSt</th>
          <th title='Pfand je V-Einheit'>Pfand</th>
          <th title='Endpreis je V-Einheit' colspan='2'>V-Preis</th>
  <?
  if( $bestell_id )
    echo "<th title='Preiseintrag für Bestellung $bestellung_name'>Aktiv</th>";
  ?> </tr> <?

  $produktpreise = sql_produktpreise2( $produkt_id );
  while( $pr1 = mysql_fetch_array($produktpreise) ) {
    preisdatenSetzen( &$pr1 );
    $references = references_produktpreise( $pr1['id'] );
    ?>
      <tr>
        <td style='white-space:nowrap;'><?
          echo $pr1['id'];
          if( $editable and ( $references == 0 ) and $pr1['zeitende'] ) {
            ?>
              &nbsp; <a class='png'
                 href="javascript:deleteProduktpreis(<? echo $pr1['id']; ?>);"
              ><img src='img/b_drop.png' border='0'
                    alt='Preiseintrag löschen'
                    title='Dieser Preiseintrag wird nicht verwendet; löschen?'/></a>
            <?
          }
        ?></td>
        <td><? echo $pr1['bestellnummer']; ?></td>
        <td style='text-align:center;'><? echo $pr1['datum_start']; ?></td>
        <td style='text-align:center;'>
    <?
    if( $pr1['zeitende'] ) {
      echo "{$pr1['datum_ende']}";
    } else {
      if( $editable )
        action_button( "Abschließen"
        , "Preisintervall abschließen (z.B. falls Artikel nicht lieferbar)"
        , array( 'action' => 'zeitende_setzen'
          , 'preis_id' => $pr1['id']
          , 'day' => date('d'), 'month' => date('m'), 'year' => date('Y')
          )
        , $mod_id
        );
      else
        echo " - ";
    }
    ?>
        </td>
        <td class='mult'><? echo $pr1['kan_liefermult']; ?></td>
        <td class='unit'><? echo $pr1['kan_liefereinheit']; ?></td>
        <td class='mult'><? printf( "%8.2lf", $pr1['lieferpreis'] ); ?></td>
        <td class='unit'>/ <? echo $pr1['preiseinheit']; ?></td>
        <td class='mult'><? echo $pr1['kan_verteilmult']; ?></td>
        <td class='unit'><? echo $pr1['kan_verteileinheit']; ?></td>
        <td class='number'><? echo $pr1['gebindegroesse']; ?></td>
        <td class='number'><? echo $pr1['mwst']; ?></td>
        <td class='number'><? echo $pr1['pfand']; ?></td>
        <td class='mult'><? printf( "%8.2lf", $pr1['preis'] ); ?></td>
        <td class='unit'> / <? echo "{$pr1['kan_verteilmult']} {$pr1['kan_verteileinheit']}"; ?></td>
    <?
    if( $bestell_id ) {
      ?> <td> <?
      if( $pr1['id'] == $preisid_in_bestellvorschlag ) {
        ?>
          <input type='submit' name='aktiv' value='aktiv' class='buttondown'
          style='width:5em;'
          title='gilt momentan f&uuml;r Abrechnung der Bestellung <? echo $bestellung_name; ?>'>
        <?
      } else {
        if( $editable ) {
          action_button( "setzen"
          , "diesen Preiseintrag für Bestellung $bestellung_name auswählen"
          , array( 'action' => 'preiseintrag_waehlen', 'preis_id' => $pr1['id'] )
          );
        } else {
          echo " - ";
        }
      }
      ?> </td> <?
    }
    ?> </tr> <?
  }
  ?></table></div><?

  produktpreise_konsistenztest( $produkt_id, $editable, 0 );

  ?></fieldset><?
}

/**
 * Produziert ein neues select-Feld mit den möglichen
 * Diensten.
 */
function dienst_selector($pre_select, $id=""){
	echo"
              <select name='newDienst$id'>
	";
	    
	  //var_dump($_SESSION['DIENSTEINTEILUNG']);
	  foreach ($_SESSION['DIENSTEINTEILUNG'] as $key => $i) { 
	       if ($i == $pre_select) $select_str="selected";
     	       else $select_str = ""; 
	       echo "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } 
	echo"
               </select>
	";

}
/**
 * Zeigt die Gruppenmitglieder einer Gruppe als Tabellenansicht an.
 * Argument: sql_members($group_id)
 */
function membertable_view($rows, $editable=FALSE, $super_edit=FALSE){
?>
   
    <table class='liste'>
      <tr>
         <th>Vorname</th>
         <th>Name</th>
         <th>Mail</th>
         <th>Telefon</th>
         <th>Diensteinteilung</th>
<?
	if($super_edit){
		echo"
         <th>Optionen</th> ";
	}
?>
      </tr>
<?     
  while ($row = mysql_fetch_array($rows)) {
   if($editable){
	echo"
      <tr>
	 <td><input type='input' size='12' name='newVorname{$row['id']}' value='{$row['vorname']}'></td>
	 <td><input type='input' size='12' name='newName{$row['id']}' value='{$row['name']}'></td>
	 <td><input type='input' size='12' name='newEmail{$row['id']}' value='{$row['email']}'></td>
	 <td><input type='input' size='12' name='newTelefon{$row['id']}' value='{$row['telefon']}'></td>
	";
   }else {
	echo"
      <tr>
        <td>{$row['vorname']}</td>
        <td>{$row['name']}</td>
        <td>{$row['email']}</td>
        <td>{$row['telefon']}</td>
	";
   }
   if($super_edit){
	echo"
	   <td>
	";
	    
	dienst_selector($row['diensteinteilung'], $row['id']);
	echo"
	   </td>
	   <td>
             <a class='png' href=\"javascript:if(confirm('Soll die Person wirklich GELÖSCHT werden?')){
            document.forms['reload_form'].action.value='delete';
            document.forms['reload_form'].person_id.value='{$row['id']}';
            document.forms['reload_form'].submit();}\">
          <img src='img/b_drop.png' border='0' alt='Person löschen' title='Person löschen'/></a>

	   </td>
	";

   }else{
	echo"
        <td>{$row['diensteinteilung']}</td>
	";
   }
	echo"
      <tr/>
	";
  }
?>
          
      </tr>
    </table>
<?
}
