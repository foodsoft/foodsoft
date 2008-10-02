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
    number_selector($stunde_feld, 0, 23, $stunde,"%02d");
    echo ":";
    number_selector($minute_feld,0, 59, $minute,"%02d");
}

function floating_submission_button( $id ) {
  ?>
  <span id='<? echo $id; ?>' style='display:none;position:fixed;top:20px;left:20px;padding:1ex;z-index:999;' class='alert'>
    <div style='margin:0.5ex;'>
      <table class='alert'>
        <tr>
          <td class='alert'>
            <img class='button' src='img/close_black_trans.gif' onClick='document.getElementById("<? echo $id; ?>").style.display = "none";'>
          </td>
          <td style='text-align:center' class='alert'> &Auml;nderungen sind noch nicht gespeichert! </td>
        </tr>
        <tr>
          <td colspan='2' style='text-align:center;' class='alert'>
            <input type='submit' class='bigbutton' value='Speichern'>
            <input type='reset' class="bigbutton" value='Zur&uuml;cksetzen'
              onClick='document.getElementById("<? echo $id; ?>").style.display = "none";'>
          </td>
        </tr>
      </table>
    </div>
  </span>
  <?
}

/**
 *  Zeigt einen Dienst und die möglichen Aktionen
 */
function dienst_view($row, $gruppe, $show_buttons = TRUE, $area="dienstplan"){
       $critical_date = in_two_weeks();
       if(compare_date2($row["Lieferdatum"], $critical_date)){
	  //soon
	  $color_norm="#00FF00";   //grün
	  $color_not_confirmed="#FFC800";   //yellow
	  $color_not_accepted="#FF0000";    //red
	  $soon=TRUE;
       } else {
	  $color_norm="#00FF00";   //grün
	  $color_not_confirmed="#00FF00";   //grün
	  $color_not_accepted="#000000";    //black
	  $soon=FALSE;
       }
       switch($row["Status"]){
       case "Vorgeschlagen":
	    if($gruppe == $row["gruppen_id"]){
	    ?>
              <div class=alert>
	       <b>
                <?    show_dienst_gruppe($row, $color_not_accepted); ?>
                Dieser Dienst ist euch zugeteilt</b> <br>
	       <?if($show_buttons){?>
	       <form action="<? echo self_url(); ?>" method='post'>
	       <? echo self_post(); ?>
	       <input type="hidden" name="aktion" value="akzeptieren_<?echo $row["ID"]?>">
	       <input type="submit" value="akzeptieren">  
	       </form>
	       <?}?>
	       <?if($show_buttons){?>
	       <form action="<? echo self_url(); ?>" method='post'>
	       <? echo self_post(); ?>
	       <input type="hidden" name="aktion" value="abtauschen_<?echo $row["ID"]?>">
	       <input type="submit" value="geht nicht">  
	       </form>
           </div>
	       <?}?>
	       </font>
	    <?
	    } else {
		    ?>
		    Noch nicht akzeptiert (
		    
		    <?
	            show_dienst_gruppe($row, $color_not_accepted);
		    echo ")";
	            if( $soon){

		       ?>
	               <?if($show_buttons){?>
  	       <form action="<? echo self_url(); ?>" method='post'>
  	       <? echo self_post(); ?>
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
	       <form action="<? echo self_url(); ?>" method='post'>
	       <? echo self_post(); ?>
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="uebernehmen_<?echo $row["ID"]?>">
	       <input  type="submit" value="übernehmen">  
	       </form>
	    <?
	       }
       	    break;
       case "Geleistet":
	       show_dienst_gruppe($row, $color_norm);
       	    break;
       case "Akzeptiert":
            $color_use = $color_not_confirmed;

       case "Bestaetigt":
            if(!isset($color_use)){
	    	$color_use = $color_norm;
	    }
	    show_dienst_gruppe($row, $color_use);
       	    if($gruppe == $row["gruppen_id"]){
	    ?>
	       <?if($show_buttons){?>
	       <form action="<? echo self_url(); ?>" method='post'>
	       <? echo self_post(); ?>
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="wirdoffen_<?echo $row["ID"]?>">
	       <input type="submit" value="kann doch nicht">  
	       </form>
	    <?
	       }
	    } else if($row["Status"]=="Akzeptiert" & $soon){

	       ?>
	       <?if($show_buttons){?>
	       <form action="<? echo self_url(); ?>" method='post'>
	       <? echo self_post(); ?>
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

function show_dienst_gruppe($row, $color_use){
     echo "<font color=".$color_use.">Gruppe ".($row['gruppen_id']%1000).": ".$row["name"]." ".$row["telefon"]."</font>";

}

/**
 * Ausgabe der Links im Hauptmenue und im Foodsoft-Kopf
 */
function areas_in_menu($area){
  ?> <tr> <td> <?
    echo fc_button( $area['area'], array(
       'window_id' => 'main', 'text' => $area['title'], 'title' => $area['hint'] 
     ) );
  ?> </td><td class='small' style='vertical-align:middle;'><? echo $area['hint']; ?></td></tr><?
}

function areas_in_head($area){
  ?> <li> <?
  echo fc_alink( $area['area'], array(
    'window_id' => 'main', 'img' => '', 'text' => $area['title'], 'title' => $area['hint'] 
  ) );
  ?> </li> <?
}

function rotationsplanView($row){
 ?>
   <tr>
       <td>
       <b>
       <?echo $row['rotationsplanposition']?>
       </b>
       </td><td>
       <?echo "Gruppe ".($row['gruppen_id'] % 1000).":".$row['name']?>
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
  $muell_id = sql_muell_id();

  if( $editAmounts ) {
    open_form( '', '', '', array( 'action' => 'basarzuteilung' ) );
    $cols=11;
  } else {
    $cols=9;
  }

  open_table('list');

  $legend = array(
    "<th>" . fc_alink( 'self', "orderby=produktname,text=Produkt,title=Sortieren nach Produkten" ) ."</th>"
  , "<th>" . fc_alink( 'self', "orderby=bestellung,text=Bestellung,title=Sortieren nach Bestellung" ) ."</th>"
  , "<th>" . fc_alink( 'self', "orderby=datum,text=Lieferdatum,title=Sortieren nach Lieferdatum" ) ."</th>"
  , "<th colspan='2'>Preis</th>"
  , "<th colspan='3'>Menge im Basar</th>"
  , "<th title='Wert incl. MWSt. und Pfand'>Wert</th>"
  , ( $editAmounts ? "<th colspan='2'>Zuteilung</th>" : "" )
  );
  switch( $order ) {
    case 'bestellung':
      $rowformat='%2$s%1$s%3$s%4$s%5$s%6$s%7$s';
      $keyfield=1;
      break;
    case 'datum':
      $rowformat='%3$s%1$s%2$s%4$s%5$s%6$s%7$s';
      $keyfield=2;
      break;
    default:
    case 'produktname':
      $rowformat='%1$s%2$s%3$s%4$s%5$s%6$s%7$s';
      $keyfield=0;
      break;
  }
  vprintf( "<tr class='legende'>$rowformat</tr>", $legend );

  $result = sql_basar( $bestell_id, $order );

  $last_key = '';
  $row_index=0;
  $js = '';
  $fieldcount = 0;
  $gesamtwert = 0;
  while( $basar_row = mysql_fetch_array($result) ) {
     kanonische_einheit( $basar_row['verteileinheit'], & $kan_verteileinheit, & $kan_verteilmult );
     $menge=$basar_row['basar'];
     // umrechnen, z.B. Brokkoli von: x * (500g) nach (x * 500) g:
     $menge *= $kan_verteilmult;
     $wert = $basar_row['basar'] * $basar_row['endpreis'];
     $gesamtwert += $wert;
     $rechnungsstatus = getState( $basar_row['gesamtbestellung_id'] );

     $row = array( 
       "<td>{$basar_row['produkt_name']}</td>"
     , "<td>" . fc_alink( 'bestellschein', array(
          'bestell_id' => $basar_row['gesamtbestellung_id'], 'text' => $basar_row['bestellung_name'], 'img' => false
        ) ) . "</td>"
     , "<td>{$basar_row['lieferung']}</td>"
     , "<td class='mult'>"
         . fc_alink( 'produktdetails', array(
             'img' => '', 'produkt_id' => $basar_row['produkt_id']
           , 'text' => sprintf( "%.2lf", $basar_row['endpreis'] )
           ) )
         . "</td>
         <td class='unit'>/ $kan_verteilmult $kan_verteileinheit</td>"
     , "<td class='mult'><b>$menge</b></td>
        <td class='unit' style='border-right-style:none;'>$kan_verteileinheit</td>
        <td style='border-left-style:none;'>"
        . fc_alink( 'produktverteilung', array(
           'bestell_id' => $basar_row['gesamtbestellung_id'], 'produkt_id' => $basar_row['produkt_id']
        ) ) . "</td>"
     , "<td class='number' style='padding:0pt 1ex 0pt 1ex;'><b>" . sprintf( "%8.2lf", $wert ) . "</b></td>"
     , ( $editAmounts ? ( $rechnungsstatus < STATUS_ABGERECHNET ?
         "<td class='mult' style='padding:0pt 1ex 0pt 1ex;'>
          <input type='hidden' name='produkt$fieldcount' value='{$basar_row['produkt_id']}'>
          <input type='hidden' name='bestellung$fieldcount' value='{$basar_row['gesamtbestellung_id']}'>
          <input name='menge$fieldcount' type='text' size='5'></td>
          <td class='unit'>$kan_verteileinheit</td>"
         : "<td> (Bestellung abgeschlossen) </td>"
       ) : ""
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
  open_tr('summe');
    open_td( 'right', "colspan='8'", 'Summe:' );
    open_td( 'number', sprintf( "%8.2lf", $gesamtwert ) );
    open_td();

  echo $js;

  if( $editAmounts ) {
    open_tr();
      open_td( 'right', "colspan='$cols' style='padding-top:1ex;'" );
      ?>
        <select name='gruppe'>
          <? echo optionen_gruppen( false, false, false, false, false, array($muell_id => 'Müll' ) ); ?>
        </select>
        <input type='submit' value='Zuteilen' style='margin-left:2em;'>
        <input type='hidden' name='fieldcount' value='<? echo $fieldcount; ?>'>
      <?
    close_table();
    close_form();
  } else {
    close_table();
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
  $basar_id = sql_basar_id();
  $muell_id = sql_muell_id();

  $result = sql_bestellprodukte( $bestell_id, $gruppen_id, 0 );
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
    'header' => "L-Preis", 'title' => "Nettopreis (ohne MWSt, ohne Pfand) beim Lieferanten", 'cols' => 2
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
  ?> <table class='list' width='100%'><tr class='legende'> <?

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
          if( $gruppen_id == $muell_id ) {
            $liefermenge = $produkte_row['muellmenge'];
          } else {
            $liefermenge = $produkte_row['verteilmenge'];
          }
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
      // if($editPrice){
        echo fc_alink( 'produktdetails', "img=,bestell_id=$bestell_id,produkt_id=$produkt_id,text=".sprintf( "%.2lf", $nettolieferpreis ) );
      // } else {
      //  printf( "%8.2lf", $nettolieferpreis );
      // }
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
      , ( ( $gruppen_id == $basar_id ) ? $basarbestellmenge : $toleranzbestellmenge ) * $kan_verteilmult
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
                document.getElementById('reminder').style.display='inline';
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
          ?> <td style='border-left-style:none;border-right-style:none;'>
                <input  title='Wurde nicht geliefert' type='checkbox' name='nichtGeliefert[]' value='<? echo $produkt_id; ?>'
                  onchange="document.getElementById('reminder').style.display='inline';">
             </td>
          <?
	} else {
		echo " <td></td>";
	}

      }
      if( ! $gruppen_id ) {
        ?> <td style='border-left-style:none;'> <?
        echo fc_alink( 'produktverteilung', "bestell_id=$bestell_id,produkt_id=$produkt_id" );
        ?> </td> <?
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

  ?> </table> <?
  if($editAmounts){
    floating_submission_button( 'reminder' );
    ?> </form> <?
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
    echo "<table class='menu' id='option_menu_table'></table>";
    $option_menu_counter = 0;
    // positionieren erst ganz am schluss (wenn parent sicher vorhanden ist):
    $print_on_exit[] = "
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
 * Liste zur Auswahl einer Bestellung via Link
 */
function select_bestellung_view( $result, $head="Bitte eine Bestellung wählen:", $editDates = false, $changeState = false ) {
  global $self, $foodsoftdir, $dienst, $login_gruppen_id, $hat_dienst_IV, $mysqljetzt;

  if( $head )
    echo "<h1 style='margin-bottom:2em;'>$head</h1>";
?>
  <table style="width:100%" class="list">
    <tr>
      <th>Name</th>
      <th>Status</th>
      <th>Bestellzeitraum</th>
      <th>Lieferung</th>
      <th>Summe</th>
      <th>Detailansichten</th>
<?
  if( $changeState || $editDates )
    echo "<th>Aktionen</th>";
  echo "</tr>";

  while ($row = mysql_fetch_array($result)) {
    $bestell_id = $row['id'];
    $rechnungsstatus = getState( $bestell_id );
    // $fax_url = ?
    $self_form = "<form action='" . self_url() . "' name='self_form' method='post'>" . self_post();
    $edit_link = fc_alink( 'edit_bestellung', "bestell_id=$bestell_id" );
    $aktionen = "";

    ?>
      <tr id='row<?echo $bestell_id; ?>'>
      <td><?echo $row['name']?></td>
      <td><? echo rechnung_status_string( $row['rechnungsstatus'] ); ?></td>
      <td>
        <div><? echo $row['bestellstart']; ?></div>
        <div> - <? echo $row['bestellende']; ?></div>
      </td>
      <td><? echo $row['lieferung']; ?></td>
      <td><?
        $abrechnung_dienstkontrollblatt_id = $row['abrechnung_dienstkontrollblatt_id'];
        if( $rechnungsstatus == STATUS_ABGERECHNET ) {
          printf( "<div>%.2lf</div><div style='font-size:smaller;'>%s</div"
          , sql_bestellung_rechnungssumme( $bestell_id )
          , dienstkontrollblatt_name( $abrechnung_dienstkontrollblatt_id )
          );
        } else {
          echo "-";
        }
      ?></td>
    <?
  
    switch( $rechnungsstatus ) {
  
      case STATUS_BESTELLEN:
        ?> <td> <?
          echo  fc_alink( 'bestellschein', "bestell_id=$bestell_id,img=,text=Bestellschein (vorl&auml;ufig)" );
        ?> </td> <?
        if( $editDates )
          $aktionen .= "<li>$edit_link</li>";
        if( $changeState ) {
          if( $dienst == 4 )  {
            if ( $row['bestellende'] < $mysqljetzt ) {
              $aktionen .= ( "<li>" . fc_action( array( 'action' => 'changeState', 'class' => 'action'
                             , 'change_id' => $bestell_id, 'change_to' => STATUS_LIEFERANT
                             , 'title' => 'Jetzt Bestellschein für Lieferanten fertigmachen?'
                             , 'text' => '>>> Bestellschein fertigmachen >>>' ) ) . "</li>" );
            } else {
              $aktionen .= "<li style='font-weight:bold;'>Bestellung läuft noch!</li>";
            }
            if( references_gesamtbestellung( $bestell_id ) == 0 ) {
              $aktionen .= ( "<li>" . fc_action( "action=delete,title=Bestellung löschen,delete_id=$bestell_id,img=img/b_drop.png" ) . "</li>" );
            }
          }
        }
        break;
  
      case STATUS_LIEFERANT:
        ?> <td> <?
          echo  fc_alink( 'bestellschein', "bestell_id=$bestell_id,img=,text=Bestellschein" );
          // if( $hat_dienst_IV ) {
          //    fax-download: im Moment ausser Betrieb!
          // }
        ?> </td> <?
        if( $editDates )
          $aktionen .= "<li>$edit_link</li>";
        if( $changeState ) {
          if( $hat_dienst_IV ) {
            $aktionen .= ( "<li>" . fc_action( array( 'action' => 'changeState', 'class' => 'action'
                   , 'change_id' => $bestell_id, 'change_to' => STATUS_BESTELLEN
                   , 'title' => 'Bestellung nochmal zum Bestellen freigeben?'
                   , 'text' => '<<< Nachbestellen lassen <<<' ) ) . "</li>" );
          }
          if( $dienst > 0 ) {
            $aktionen .= ( "<li>" . fc_action( array( 'action' => 'changeState', 'class' => 'action'
                  , 'change_id' => $bestell_id, 'change_to' => STATUS_VERTEILT
                  , 'title' => 'Bestellung wurde geliefert, Lieferschein abgleichen?'
                  , 'text' => '>>> Lieferschein erstellen >>>' ) ) . "</li>" );
          }
        }
        break;

      case STATUS_VERTEILT:
        ?> <td> <?
          echo  fc_alink( 'lieferschein', "bestell_id=$bestell_id,img=,text=Lieferschein" );
          if( $dienst > 0 ) {
           ?> <br> <?
            echo fc_alink( 'verteilliste', "bestell_id=$bestell_id,img=" );
          }
        ?> </td> <?
        if( $editDates )
          $aktionen .= "<li>$edit_link</li>";
        if( $dienst == 4 )
           $aktionen .= ( "<li>" . fc_alink( 'abrechnung', "bestell_id=$bestell_id,text=Abrechnung beginnen..." ) . "</li>" );
        break;

      case STATUS_ABGERECHNET:
        ?> <td> <?
          echo  fc_alink( 'lieferschein', "bestell_id=$bestell_id,img=,text=Lieferschein" );
          ?> <br> <?
          echo  fc_alink( 'abrechnung', "bestell_id=$bestell_id,img=" );
          if( $dienst > 0 ) {
           ?> <br> <?
            echo fc_alink( 'verteilliste', "bestell_id=$bestell_id,img=" );
          }
        ?> </td> <?
        break;

      case STATUS_ARCHIVIERT:
      default:
        ?> <td>(keine Details verf&uuml;gbar)</td> <?
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
  ?> Produkt: <select name='produkt_id'> <?
  if( $bestell_id ) {
    $produkte = getProdukteVonLieferant( getProduzentBestellID( $bestell_id ), $bestell_id );
    while( $prod = mysql_fetch_array( $produkte ) ) {
      echo "<option value='".$prod['produkt_id']."'>"
      . $prod['name'] . " (" . $prod['verteileinheit']. ") " ."</option>";
    }
  }
  ?> </select> <?
}

function distribution_tabellenkopf() {
  ?>
    <table class='list' width='800'>
    <tr class="legende">
       <th>Gruppe</th>
       <th colspan='2'>bestellt (toleranz)</th>
       <th colspan='2'>geliefert</th>
       <th>Gesamtpreis</th>
    </tr>
  <?
}

function distribution_produktdaten( $bestell_id, $produkt_id ) {
  $produkt = sql_bestellvorschlag_daten( $bestell_id, $produkt_id );
  ?>
  <tr><th colspan='6'>
    <div style='font-size:1.2em; margin:5px;'>
       <? echo fc_alink( 'produktpreise', array(
         'text' => $produkt['produkt_name'], 'img' => '', 'produkt_id' => $produkt_id ) ); ?>
    </div>
     <div style='font-size:0.8em'>
       <? printf( "Preis: %.2lf / %s, Produktgruppe: %s"
         , $produkt['preis']
         , $produkt['verteileinheit']
         , $produkt['produktgruppen_name']
         );
       ?>
    </div>
  </th></tr>
  <?
}

function distribution_view( $bestell_id, $produkt_id, $editable = false ) {
  $vorschlag = sql_bestellvorschlag_daten($bestell_id,$produkt_id);
  preisdatenSetzen( & $vorschlag );
  $verteilmult = $vorschlag['kan_verteilmult'];
  $verteileinheit = $vorschlag['kan_verteileinheit'];
  $preis = $vorschlag['preis'];

  ?>
    <tr class='summe'>
      <th colspan='3' style='text-align:left;'>Liefermenge:</th>
      <th class='mult'>
        <?
          $liefermenge = $vorschlag['liefermenge'] * $verteilmult;
          if( $editable ) {
            $feldname = "liefermenge_{$bestell_id}_{$produkt_id}";
            global $$feldname;
            if( get_http_var( $feldname, 'f' ) ) {
              $liefermenge_form = $$feldname;
              if( $liefermenge != $liefermenge_form ) {
                changeLiefermengen_sql( $liefermenge_form / $verteilmult, $produkt_id, $bestell_id );
                $liefermenge = $liefermenge_form;
              }
            }
            printf(
              "<input name='$feldname' type='text' size='5' style='text-align:right;' value='%d'
               onchange=\"document.getElementById('reminder').style.display = 'inline';\">"
            , $liefermenge
            );
          } else {
            printf( "%d", $liefermenge );
          }
        ?>
      </th>
      <th class='unit'>
        <? echo $verteileinheit; ?>
      </th>
      <th class='number'><? printf( "%.2lf", $preis * $liefermenge / $verteilmult ); ?></td>
    </tr>
  <?

  $basar_id = sql_basar_id();
  $muell_id = sql_muell_id();
  $basar_festmenge = 0;
  $basar_toleranzmenge = 0;
  $muellmenge = 0;

  $gruppen = sql_beteiligte_bestellgruppen( $bestell_id, $produkt_id );
  while( $gruppe = mysql_fetch_array( $gruppen ) ) {
    $gruppen_id = $gruppe['id'];
    $mengen = sql_select_single_row( select_bestellprodukte( $bestell_id, $gruppen_id, $produkt_id ), true );
    if( $mengen ) {
      $toleranzmenge = $mengen['toleranzbestellmenge'] * $verteilmult;
      $festmenge = $mengen['gesamtbestellmenge'] * $verteilmult - $toleranzmenge;
      $verteilmenge = $mengen['verteilmenge'] * $verteilmult;
    } else {
      $toleranzmenge = 0;
      $festmenge = 0;
      $verteilmenge = 0;
    }
    switch( $gruppen_id ) {
      case $muell_id:
        $muellmenge = $mengen['muellmenge'] * $verteilmult;
        continue 2;
      case $basar_id;
        $basar_toleranzmenge = $toleranzmenge;
        $basar_festmenge = $festmenge;
        continue 2;
    }
    ?>
      <tr>
        <td><? echo "{$gruppe['gruppennummer']} {$gruppe['name']}"; ?></td>
        <td class='mult'><? printf( "%d (%d)", $festmenge, $toleranzmenge ); ?></td>
        <td class='unit'><? echo $verteileinheit ?></td>
        <td class='mult'>
    <?
    if( $editable ) {
      $feldname = "menge_{$bestell_id}_{$produkt_id}_{$gruppen_id}";
      global $$feldname;
      if( get_http_var( $feldname, 'f' ) ) {
        $menge_form = $$feldname;
        if( $verteilmenge != $menge_form ) {
          changeVerteilmengen_sql( $menge_form / $verteilmult, $gruppen_id, $produkt_id, $bestell_id );
          $verteilmenge = $menge_form;
        }
      }
      printf(
        "<input name='$feldname' type='text' size='5' style='text-align:right;' value='%d'
         onchange=\"document.getElementById('reminder').style.display = 'inline';\">"
      , $verteilmenge
      );
    } else {
      printf( "%d", $verteilmenge );
    }
    ?>
      </td>
      <td class='unit'><? echo $verteileinheit ?></td>
      <td class='number'><? printf( "%.2lf", $preis * $verteilmenge / $verteilmult ); ?></td>
      </tr>
    <?
  }
  ?>
    <tr class='summe'>
      <td colspan='3'>M&uuml;ll:</td>
      <td class='mult'>
      <?
        if( $editable ) {
          $feldname = "menge_{$bestell_id}_{$produkt_id}_{$muell_id}";
          global $$feldname;
          if( get_http_var( $feldname, 'f' ) ) {
            $menge_form = $$feldname;
            if( $muellmenge != $menge_form ) {
              changeVerteilmengen_sql( $menge_form / $verteilmult, $muell_id, $produkt_id, $bestell_id );
              $muellmenge = $menge_form;
            }
          }
          printf(
            "<input name='$feldname' type='text' size='5' style='text-align:right;' value='%d'
             onchange=\"document.getElementById('reminder').style.display = 'inline';\">"
          , $muellmenge
          );
        } else {
          printf( "%d", $muellmenge );
        }
      ?>
      </td>
      <td class='unit'><? echo $verteileinheit ?></td>
      <td class='number'><? printf( "%.2lf", $preis * $muellmenge / $verteilmult ); ?></td>
    </tr>
    <tr class='summe'>
      <td colspan='1'>Basar:</td>
      <td class='mult'>
        <? printf( "%d (%d)", $basar_festmenge, $basar_toleranzmenge ); ?>
      </td>
      <td class='unit'><? echo $verteileinheit ?></td>
      <td class='mult'><? echo $basarmenge = sql_basarmenge( $bestell_id, $produkt_id ) * $verteilmult; ?></td>
      <td class='unit'><? echo $verteileinheit ?></td>
      <td class='number'><? printf( "%.2lf", $preis * $basarmenge / $verteilmult ); ?></td>
    </tr>
  <?
}


function sum_row($sum){
  ?>
  <tr style='border:none' class='summe'>
    <td colspan='7' style='border:none;text-align:right;'>Summe:</td>
    <td class='number'><? printf( "%8.2lf", $sum); ?></td>
  </tr>
  <?
}

function bestellung_overview($row, $showGroup=FALSE, $gruppen_id = NULL){
  global $hat_dienst_IV, $window_id;
  $bestell_id = $row['id'];
  ?>
    <table class="list">
      <tr>
        <th> Bestellung: </th>
          <td style="font-size:1.2em;font-weight:bold">
            <?
              echo fc_alink( 'lieferschein', array(
                'img' => false, 'text' => $row['name'], 'bestell_id' => $row['id']
                , 'title' => 'zum Bestellschein/Lieferschein...'
              ) );
              if( $hat_dienst_IV and getState( $bestell_id ) < STATUS_ABGERECHNET ) {
                echo fc_alink( 'edit_bestellung', "bestell_id=$bestell_id" );
              }
              if(sql_dienste_nicht_bestaetigt($row['lieferung'])){
                ?> <br> <b>Vorsicht:</b> <?
                echo fc_alink( 'dienstplan', 'text=Dienstegruppen abwesend?' );
              }
            ?>
          </td>
        </tr>
        <tr>
          <th>Lieferant:</th>
          <td><?
            echo fc_alink( 'edit_lieferant', array( 'text' => lieferant_name( $row['lieferanten_id'] )
                                                     , 'img' => '' , 'lieferanten_id' => $row['lieferanten_id'] ) );
          ?></td>
        </tr>
        <tr>
          <th> Bestellzeitraum: </th>
          <td><?PHP echo $row['bestellstart'] .' - '. $row['bestellende']; ?></td>
        </tr>
        <tr>
          <th> Lieferung: </th>
          <td><?PHP echo $row['lieferung']; ?></td>
        </tr>
    <?
    if( $showGroup and $gruppen_id ){
      $gruppendaten = sql_gruppendaten( $gruppen_id );
      ?>
        <tr>
          <th> Gruppe: </th>
          <td>
            <?PHP
              if( $gruppen_id == sql_basar_id() )
                echo "<span class='warn'> BASAR </span>";
              else
                echo "{$gruppendaten['name']} ({$gruppendaten['gruppennummer']})";
            ?>
          </td>
        </tr>	
        <tr>
          <th> Kontostand: </th>
          <td>
            <?
              // überprüfen ob negeativer kontostand. wenn ja, dann rot und fett !!
              $kontostand = kontostand($gruppen_id);
              if( $kontostand < 0 ) { 
                ?><span style'color:red;font-weight:bold;'><? printf( "%.2lf", $kontostand ); ?></span><?
              } else {
                ?><span style='color:green;font-weight:normal;'><? printf( "%.2lf", $kontostand ); ?></span><?
              }	
            ?>
          </td>
        </tr>	
      <?
    }
    if( $window_id != 'abrechnung' ) {
      ?>
        <tr>
          <th>Status:</th>
          <td> <? abrechnung_kurzinfo( $bestell_id ); ?> </td>
        </tr>
      <?
    }
  ?> </table> <?
}

function abrechnung_kurzinfo( $bestell_id ) {
  $row = sql_bestellung( $bestell_id );
  $status = $row['rechnungsstatus'];
  if( $status < STATUS_VERTEILT ) {
    echo rechnung_status_string( $status );
    return;
  }
  if( $status == STATUS_ABGERECHNET ) {
    $text = "abgerechnet
     <div style='padding-top:1ex;padding-left:1ex;'>
     <table class='inner' width='100%' style='color:#ed0000;'>
      <tr>
        <td class='small'>Rechnungsnummer:</td>
        <td style='text-align:right;' class='small'>". $row['rechnungsnummer'] ."</td>
      </tr>
      <tr>
        <td class='small'>Rechnungssumme:</td>
        <td style='text-align:right;' class='small'>". sprintf( '%.2lf', sql_bestellung_rechnungssumme($bestell_id) ) ."</td>
      </tr>
      <tr>
        <td class='small'>abgerechnet von:</td>
        <td style='text-align:right;' class='small'>". dienstkontrollblatt_name( $row['abrechnung_dienstkontrollblatt_id'] ) ."</td>
      </tr>
    </table>
    </div>";
  } else {
    $text = rechnung_status_string( $status );
  }
  echo fc_alink( 'abrechnung', array( 'bestell_id' => $bestell_id, 'img' => false , 'text' => $text ) );
}

function formular_buchung_gruppe_bank(
  $gruppen_id = 0, $konto_id = 0, $auszug_jahr = '', $auszug_nr = '', $notiz = 'Einzahlung'
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
              <select name='gruppen_id'><? echo optionen_gruppen(); ?></select>
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
            <td><label title'positiv: Einzahlung / negativ: Auszahlung!'>Haben Konto:</label></td>
            <td>
              <input type="text" name="betrag" size='6' value="" title='positiv: Einzahlung / negativ: Auszahlung!'>
              <kbd>EUR</kbd>
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
    <form method='post' class='small_form' action='<? echo self_url('lieferanten_id'); ?>'>
      <? echo self_post('lieferanten_id'); ?>
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
                </kbd>
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
            <td><label title='positiv: Einzahlung / negativ: Auszahlung!'>Haben Konto:</label></td>
            <td>
              <input type="text" name="betrag" value="" size='6'
                   title='positiv: Einzahlung / negativ: Auszahlung!'>
              <kbd>EUR</kbd>
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
              <select name='gruppen_id'><? echo optionen_gruppen(); ?></select>
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
                </kbd>
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
            <td><label title='positiv: Zahlung an / negativ: Zahlung von Lieferant'>Haben Lieferant:</label></td>
            <td>
              <input type="text" name="betrag" value="" size='6'
                title='positiv: Zahlung an Lieferant/ negativ: Zahlung von Lieferant'>
              <kbd>EUR</kbd>
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
              <select name='gruppen_id'><?
                echo optionen_gruppen();
              ?></select>
            <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>an Gruppe:</label></td>
            <td><select name='nach_gruppen_id'><?
              echo optionen_gruppen( false, false, $nach_gruppen_id );
            ?></select></td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label>Betrag:</label></td>
            <td>
              <input type="text" name="betrag" value="" size='6'>
              <kbd>EUR</kbd>
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

function formular_buchung_bank_bank(
  $konto_id, $auszug_jahr, $auszug_nr, $notiz = 'Überweisung von Konto zu Konto'
) {
  ?>
    <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <input type='hidden' name='action' value='ueberweisung_konto_konto'>
      <fieldset>
        <legend>
          Überweisung von Konto zu Konto
        </legend>
        <table>
          <tr>
            <td><label>von Konto:</label></td>
            <td>
              <kbd>
                <? echo sql_kontoname( $konto_id ); ?>
                <input type='hidden' name='konto_id' value='<? echo $konto_id; ?>'>
              </kbd>
              &nbsp; Auszug:
              <kbd>
                <? echo "$auszug_jahr / $auszug_nr"; ?>
                <input type='hidden' name='auszug_jahr' value='<? echo $auszug_jahr; ?>'>
                <input type='hidden' name='auszug_nr' value='<? echo $auszug_nr; ?>'>
              </kbd>
            </td>
          </tr>
          <tr>
            <td><label>an Konto:</label></td>
            <td><select name='nach_konto_id'><? echo optionen_konten( $nach_konto_id ); ?></select>
               &nbsp; <label>Auszug:</label>
                  <input type='text' size='4' name='nach_auszug_jahr' value=''> /
                  <input ty[e='text' size='2' name='nach_auszug_nr' value=''>
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label>Betrag:</label></td>
            <td>
              <input type="text" name="betrag" value="" size='6'>
              <kbd>EUR</kbd>
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

function formular_buchung_bank_sonderausgabe(
  $konto_id, $auszug_jahr, $auszug_nr, $notiz = ''
) {
  ?>
    <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <input type='hidden' name='action' value='ueberweisung_sonderausgabe'>
      <fieldset>
        <legend>
          &Uuml;berweisung Sonderausgabe
        </legend>
        <table>
          <tr>
            <td><label>von Konto:</label></td>
            <td>
              <kbd>
                <? echo sql_kontoname( $konto_id ); ?>
                <input type='hidden' name='konto_id' value='<? echo $konto_id; ?>'>
              </kbd>
              &nbsp; Auszug:
              <kbd>
                <? echo "$auszug_jahr / $auszug_nr"; ?>
                <input type='hidden' name='auszug_jahr' value='<? echo $auszug_jahr; ?>'>
                <input type='hidden' name='auszug_nr' value='<? echo $auszug_nr; ?>'>
              </kbd>
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label title='positiv: Gewinn der FC / negativ: Verlust der FC'>Haben FC:</label></td>
            <td>
              <input type="text" name="betrag" value="" size='6'
                title='positiv: Gewinn der FC / negativ: Verlust der FC'>
              <kbd>EUR</kbd>
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

function formular_buchung_gruppe_sonderausgabe( $gruppen_id = 0, $notiz = '' ) {
  ?>
    <form method='post' class='small_form' action='<? echo self_url('gruppen_id'); ?>'>
      <? echo self_post('gruppen_id'); ?>
      <input type='hidden' name='action' value='sonderausgabe_gruppe'>
      <fieldset>
        <legend>
          Sonderausgabe durch eine Gruppe
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
              <select name='gruppen_id'><?
                echo optionen_gruppen();
              ?></select>
            <? } ?>
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label title='positiv: Gewinn der FC / negativ: Verlust der FC'>Betrag:</label></td>
            <td>
              <input type="text" name="betrag" value="" size='6'
                title='positiv: Gewinn der FC / negativ: Verlust der FC'>
              <kbd>EUR</kbd>
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

function formular_umbuchung_verlust( $typ = 0 ) {
  ?>
    <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <input type='hidden' name='action' value='umbuchung_verlust'>
      <input type='hidden' name='typ' value='<? echo $typ; ?>'>
        <table>
          <tr>
            <td><label>von:</label></td>
            <td>
              <?  if( $typ ) { ?>
                <? need( in_array( $typ, array( TRANSAKTION_TYP_SPENDE, TRANSAKTION_TYP_UMLAGE ) ) ); ?>
                <kbd><? echo transaktion_typ_string( $typ ); ?></kbd>
                <input type='hidden' name='von_typ' value='<? echo $typ; ?>'>
              <? } else { ?>
                <select name='von_typ'>
                  <option value=''>(bitte Quelle w&auml;hlen)</option>
                  <?
                    foreach( array( TRANSAKTION_TYP_SPENDE , TRANSAKTION_TYP_UMLAGE ) as $t ) {
                      ?> <option value='<? echo $t; ?>'><? echo transaktion_typ_string($t); ?></option> <?
                    }
                  ?>
                </select>
              <?  } ?>
            </td>
          </tr>
          <tr>
            <td><label>nach:</label></td>
            <td>
              <select name='nach_typ'>
                <option value=''>(bitte Ziel w&auml;hlen)</option>
                <?
                  foreach( array( TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN
                                , TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN
                                , TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE ) as $t ) {
                    ?> <option value='<? echo $t; ?>'><? echo transaktion_typ_string($t); ?></option> <?
                  }
                ?>
              </select>
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td><label>Betrag:</label></td>
            <td>
              <input type="text" size='6' name="betrag" value="" size='6'>
              <kbd>EUR</kbd>
            </td>
          </tr>
          <tr>
            <td>Notiz:</td>
            <td><input type="text" size="60" name="notiz" value=''>
              &nbsp;
              <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
            </td>
          </tr>
        </table>
    </form>
  <?
  return true;
}

function formular_gruppen_umlage() {
  ?>
    <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <input type='hidden' name='action' value='umlage'>
        <table>
          <tr>
            <td colspan='2'>
              Von <span style='font-weight:bold;font-style:italic'>allen aktiven Bestellgruppen</span> eine Umlage
            </td>
          </tr>
          <tr>
            <td class='oneline'>in Höhe von</td>
            <td><input type="text" size='6' name="betrag" value="" size='6'>
              EUR je Gruppenmitglied erheben
            </td>
          </tr>
          <tr>
            <td><label>Valuta:</label></td>
            <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
          </tr>
          <tr>
            <td>Notiz:</td>
            <td><input type="text" size="60" name="notiz" value=''>
              &nbsp;
              <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
            </td>
          </tr>
        </table>
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
  $lieferanten_id = $produkt['lieferanten_id'];
  $can_toggle or $default_on = true;
  if( $can_toggle ) {
    if( $default_on ) {
      $form_display = 'inline'; $button_display = 'none';
    } else {
      $form_display = 'none'; $button_display = 'inline';
    }
    ?>
      <span class='button' id='anummer_button' style='display:<? echo $button_display; ?>;'
        onclick="document.getElementById('anummer_button').style.display = 'none';
                 document.getElementById('anummer_form').style.display = 'block';"
      >Artikelnummer (<? echo $anummer; ?>) &auml;ndern...</span>
    <?
  } else {
    $form_display = 'inline';
  }
  ?>
    <div style='display:<? echo $form_display; ?>;' id='anummer_form' class='small_form'>
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
              <form name='artikelsuche' action="<? echo fc_url( 'artikelsuche', "lieferanten_id=$lieferanten_id", '', 'action' ); ?>" method='post'>
                <input type='hidden' name='produkt_id' value='<? echo $produkt_id; ?>'>
                <input name='name' value='<? echo $produkt['name']; ?>' size='40'>&nbsp;
                <? echo fc_button( 'artikelsuche', 'text=Los!,form=artikelsuche,class=submit' ); ?>
              </form>
            </td>
          </tr>
        </table>
      </fieldset>
    </div>
  <?
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
    $rechnungsstatus = getState( $bestell_id );
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
    $bestellung_name = bestellung_name( $bestell_id );
    ?> Preiseintrag wählen für Bestellung <?
    echo "$bestellung_name:";
  } else {
    ?> Preis-Historie: <?
  }
  ?>
    </legend>
    <div id='preishistorie'>
      <table width='100%' class='list'>
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
      if( $editable ) {
        echo action_button( "Abschließen"
        , "Preisintervall abschließen (z.B. falls Artikel nicht lieferbar)"
        , array( 'action' => 'zeitende_setzen'
          , 'preis_id' => $pr1['id']
          , 'day' => date('d'), 'month' => date('m'), 'year' => date('Y')
          )
        , $mod_id
        );
      } else {
        echo " - ";
      }
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
        if( $editable and ( $rechnungsstatus < STATUS_ABGERECHNET ) ) {
          echo action_button( "setzen"
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


function auswahl_lieferant( $selected = 0 ) {
  ?>
  <table style="width:600px;" class="list">
    <tr>
      <th>Lieferanten</th>
      <th>Produkte</th>
      <th>Pfandverpackungen</th>
    </tr>
  <?
  $lieferanten = sql_lieferanten();
  while( $row = mysql_fetch_array($lieferanten) ) {
    if( $row['id'] == $selected ) {
      echo "<tr class='active'>";
    } else {
      echo "<tr>";
    }
    ?> <td> <?
      echo fc_alink( 'self', array( 'lieferanten_id' => $row['id'], 'text' => $row['name'] ) );
    ?>
      </td>
      <td><? echo $row['anzahl_produkte']; ?></td>
      <td><? echo $row['anzahl_pfandverpackungen']; ?></td>
      </tr>
    <?
  }
  ?> </table> <?
}

function switchable_form( $tag, $legend, $initially_on, $formfields ) {
  if( $initially_on ) {
    $buttondisplay = 'none';
    $formdisplay = 'block';
  } else {
    $buttondisplay = 'block';
    $formdisplay = 'none';
  }
  return "
    <div id='{$tag}_button' style='padding-bottom:1em;display:$buttondisplay;'>
      <span class='button'
        onclick=\"document.getElementById('{$tag}_form').style.display='block';
                 document.getElementById('{$tag}_button').style.display='none';\"
        >$legend...</span>
    </div>

    <div id='{$tag}_form' style='display:$formdisplay;padding-bottom:1em;'>
      <form method='post' class='small_form' action='".self_url()."'>".self_post()."
        <fieldset>
          <legend>
            <img src='img/close_black_trans.gif' class='button'
            onclick=\"document.getElementById('{$tag}_button').style.display='block';
                     document.getElementById('{$tag}_form').style.display='none';\">
            $legend
          </legend>
          $formfields
        </fieldset>
      </form>
    </div>
  ";
}

/**
 * Produziert ein neues select-Feld mit den möglichen
 * Diensten.
 */
function dienst_selector($pre_select, $id=""){
  $s = "<select name='dienst_$id'>";
	    
	  //var_dump($_SESSION['DIENSTEINTEILUNG']);
	  foreach ($_SESSION['DIENSTEINTEILUNG'] as $key => $i) { 
	       if ($i == $pre_select) $select_str="selected";
     	       else $select_str = ""; 
	       $s .= "<option value='".$i."' ".$select_str.">".$i."</option>\n"; } 
  $s .= "</select>";
  return $s;
}
/**
 * Zeigt die Gruppenmitglieder einer Gruppe als Tabellenansicht an.
 * Argument: sql_members($group_id)
 */
function membertable_view( $gruppen_id, $editable=FALSE, $super_edit=FALSE, $head=TRUE){
?>
  <form method='post' class='big_form' action='<? echo self_url(); ?>'>
  <? echo self_post(); ?>
  <input type='hidden' name='action' value='edit'>

    <table class='list'>
<?
  if($head){
    ?>
      <tr>
         <th>Vorname</th>
         <th>Name</th>
         <th>Mail</th>
         <th>Telefon</th>
         <th>Diensteinteilung</th>
         <? if($super_edit){ ?> <th>Optionen</th> <? } ?>
      </tr>
    <?
  }
  $rows = sql_gruppen_members( $gruppen_id );
  while ($row = mysql_fetch_array($rows)) {
    ?> <tr> <?
    if($editable){
      $id = $row['id'];
      ?>
         <td><input type='input' size='16' name='vorname_<? echo $id; ?>' value='<? echo $row['vorname']; ?>'></td>
         <td><input type='input' size='16' name='name_<? echo $id; ?>' value='<? echo $row['name']; ?>'></td>
         <td><input type='input' size='16' name='email_<? echo $id; ?>' value='<? echo $row['email']; ?>'></td>
         <td><input type='input' size='12' name='telefon_<? echo $id; ?>' value='<? echo $row['telefon']; ?>'></td>
      <?
    } else {
      ?>
         <td><? echo $row['vorname']; ?></td>
         <td><? echo $row['name']; ?></td>
         <td><? echo $row['email']; ?></td>
         <td><? echo $row['telefon']; ?></td>
      <?
    }
    if($super_edit){
      ?> <td> <?
      echo dienst_selector($row['diensteinteilung'], $id );
      ?> </td><td> <?
      echo fc_action( array( 'action' => 'delete', 'person_id' => $id, 'img' => 'img/b_drop.png'
                           , 'confirm' => 'Soll das Gruppenmitglied wirklich GELÖSCHT werden?' ) );
      ?> </td> <?
    }else{
      echo"<td>{$row['diensteinteilung']}</td> ";
    }
    ?> </tr> <?
  }
  ?> </table> <?
  if($super_edit or $editable) {
    ?> <input type="submit" value="Speichern"/> <?
  }
  ?> </form> <?
}
