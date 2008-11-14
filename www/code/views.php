<?php
//This file defines views for foodsoft data

//////////////////
//
// views for "primitive" types:
//

function number_selector($name, $min, $max, $selected, $format, $to_stdout = true ){
  global $input_event_handlers;
  $s = "<select name='$name' $input_event_handlers>";
  for ($i=$min; $i <= $max; $i++) { 
	       if ($i == $selected) $select_str="selected";
     	       else $select_str = ""; 
	       $s .= "<option value='".$i."' ".$select_str.">".sprintf($format,$i)."</option>\n";
  }
  $s .= "</select>";
  if( $to_stdout )
    echo $s;
  return $s;
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
function date_time_selector($sql_date, $prefix, $show_time=true, $to_stdout = true ) {
  echo "<!-- sql_date :$sql_date -->";
	$datum = date_parse($sql_date);

  $s = "
    <table class='inner'>
                  <tr>
                     <td><label>Datum:</label></td>
                      <td style='white-space:nowrap;'>
    ". date_selector($prefix."_tag", $datum['day'],$prefix."_monat", $datum['month'], $prefix."_jahr", $datum['year'], false) ."
                   </td>
       </tr>
  ";
  if( $show_time ) {
    $s .= "
         <tr>
                   <td><label>Zeit:</label></td>
                           <td style='white-space:nowrap;'>
      ". time_selector($prefix."_stunde", $datum['hour'],$prefix."_minute", $datum['minute'], false ) ."
                           </td>
                       </tr>
    ";
  }
  $s .= "</table>";
  if( $to_stdout )
    echo $s;
  return $s;
}

function date_selector($tag_feld, $tag, $monat_feld, $monat, $jahr_feld, $jahr, $to_stdout = true ){
  $s = number_selector($tag_feld, 1, 31, $tag,"%02d",false);
  $s .= '.';
  $s .= number_selector($monat_feld,1, 12, $monat,"%02d",false);
  $s .= '.';
  $s .=  number_selector($jahr_feld, 2004, 2011, $jahr,"%04d",false);
  if( $to_stdout )
    echo $s;
  return $s;
}
function time_selector($stunde_feld, $stunde, $minute_feld, $minute, $to_stdout = true ){
  $s =  number_selector($stunde_feld, 0, 23, $stunde,"%02d",false);
  $s .= '.';
  $s .= number_selector($minute_feld,0, 59, $minute,"%02d",false);
  if( $to_stdout )
    echo $s;
  return $s;
}

function int_view( $num, $fieldname = false ) {
  global $input_event_handlers;
  $num = sprintf( "%d", $num );
  if( $fieldname )
    return "<input type='text' class='int number' size='6' name='$fieldname' value='$num' $input_event_handlers>";
  else
    return "<span class='int number'>$num</span>";
}

function price_view( $price, $fieldname = false ) {
  global $input_event_handlers;
  $price = sprintf( "%.2lf", $price );
  if( $fieldname )
    return "<input type='text' class='price number' size='8' name='$fieldname' value='$price' $input_event_handlers>";
  else
    return "<span class='price number'>$price</span>";
}

function string_view( $text, $length = 20, $fieldname = false, $attr = '' ) {
  global $input_event_handlers;
  if( $fieldname )
    return "<input type='text' class='string' size='$length' name='$fieldname' value='$text' $attr $input_event_handlers>";
  else
    return "<span class='string'>$text</span>";
}

function date_time_view( $datetime, $fieldname = '' ) {
  global $mysqljetzt;
  if( ! $datetime )
    $datetime = $mysqljetzt;
  if( $fieldname ) {
    sscanf( $datetime, '%u-%u-%u %u:%u', &$year, &$month, &$day, &$hour, &$minute );
    return date_selector( $fieldname.'_day', $day, $fieldname.'_month', $month, $fieldname.'_year', $year, false )
           .' '. time_selector( $fieldname.'_hour', $hour, $fieldname.'_minute', $minute, false );
  } else {
    return "<span class='datetime'>$datetime</span>";
  }
}
function date_view( $date, $fieldname = '' ) {
  global $mysqlheute;
  if( ! $date )
    $date = $mysqlheute;
  if( $fieldname ) {
    sscanf( $date, '%u-%u-%u', &$year, &$month, &$day );
    return date_selector( $fieldname.'_day', $day, $fieldname.'_month', $month, $fieldname.'_year', $year, false );
  } else {
    return "<span class='date'>$date</span>";
  }
}

function produktgruppe_view( $produktgruppen_id = 0, $fieldname = false ) {
  global $input_event_handlers, $window_id;
  if( $fieldname ) {
     return "<select name='$fieldname' $input_event_handlers>".optionen_produktgruppen( $produktgruppen_id )."</select>";
  } else {
    $text = ( $produktgruppen_id ? sql_produktgruppen_name( $produktgruppen_id ) : '-' );
    if( $produktgruppen_id and ( $window_id != 'produktgruppen' ) )
      return fc_link( 'produktgruppen', array( 'class' => 'href', 'text' => $text ) );
    else
      return $text;
  }
}

function gruppe_view( $gruppen_id = 0, $fieldname = '', $filter = 'aktiv', $option_0 = '' ) {
  global $input_event_handlers, $window_id;
  if( $fieldname ) {
     return "<select name='$fieldname' $input_event_handlers>".optionen_gruppen( $gruppen_id, $filter, $option_0 )."</select>";
  } else {
    $text = ( $gruppen_id ? sql_gruppenname( $gruppen_id )." (".sql_gruppennummer( $gruppen_id ).")" : $option_0 );
    if(  $gruppen_id and ( $window_id != 'gruppenmitglieder' ) )
      return fc_link( 'gruppenmitglieder', array( 'class' => 'href', 'gruppen_id' => $gruppen_id, 'text' => $text ) );
    else
      return $text;
  }
}

function konto_view( $konto_id = 0, $fieldname = '' ) {
  global $input_event_handlers, $window;
  if( $fieldname ) {
     return "<select name='$fieldname' $input_event_handlers>".optionen_konten( $konto_id )."</select>";
  } else {
    $text = ( $konto_id ? sql_kontoname( $konto_id ) : '-' );
    if( $window != 'konto' )
      return fc_link( 'konto', array( 'class' => 'href', 'konto_id' => $konto_id, 'text' => $text ) );
    else
      return $text;
  }
}

function kontoauszug_view( $konto_id = 0, $auszug_jahr = '', $auszug_nr = '', $fieldname = '' ) {
  global $input_event_handlers, $window;
  if( $fieldname ) {
    return "Jahr: ".int_view( $auszug_jahr, $fieldname.'_jahr' )
         . " / Nr.: " .int_view( $auszug_nr, $fieldname.'_nr' );
  } else {
    $text = "$auszug_jahr / $auszug_nr";
    if( $konto_id and ( $window != 'konto' ) )
      return fc_link( 'kontoauszug', array( 'class' => 'href', 'konto_id' => $konto_id, 'text' => $text
                                          , 'auszug_jahr' => $auszug_jahr, 'auszug_nr' => $auszug_nr ) );
    else
      return $text;
  }
}

function lieferant_view( $lieferant_id, $fieldname = '', $option_0 = '' ) {
  global $input_event_handlers, $window_id;
  if( $fieldname ) {
    return "<select name='$fieldname' $input_event_handlers>".optionen_lieferanten( $lieferant_id, $option_0 )."</select>";
  } else {
    $text = ( $lieferant_id ? sql_lieferant_name( $lieferant_id ) : $option_0 );
    if( $window_id != 'edit_lieferant' )
      return fc_link( 'edit_lieferant', array( 'class' => 'href', 'lieferanten_id' => $lieferant_id, 'text' => $text ) );
    else
      return $text;
  }
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

function show_dienst_gruppe($row, $color_use, $area="dienstplan"){
     global $hat_dienst_V;
     global $login_gruppen_id;
     $gruppen_members = sql_gruppen_members($login_gruppen_id);
     $gruppen_edit = $row['gruppen_id']%1000==$login_gruppen_id && count($gruppen_members) > 1;
     //Editiermöglichkeit für Dienst V und die Gruppen jeweils für 
     //sich selbst
     if(hat_dienst(5) || $gruppen_edit){

	   if($gruppen_edit){
		   $gruppen_auswahl = array("id" => $login_gruppen_id) ;
	   }else {
                 $gruppen_auswahl = sql_aktive_bestellgruppen();
	   }
?>
	   <form action="<? echo self_url(); ?>" method='post' name="personAendern_<?echo $row['ID']?>">
	       <?  echo self_post(); ?>
	       <input type="hidden" name="area" value=<?echo $area?>>
	       <input type="hidden" name="aktion" value="dienstPersonAendern_<?echo $row['ID']?>">

<?
          echo "                  <font color=".$color_use."><select name=\"person_neu\" onchange=\"document.personAendern_".$row['ID'].".submit()\">\n";
	  foreach($gruppen_auswahl as $gruppe){
		  foreach(sql_gruppen_members($gruppe['id']) as $member){
		          $selected="";
			  if($row['gruppenmitglieder_id']==$member['id']){
				  $selected=" selected ";
			  }
			  echo "                    <option value=".$member['id'].$selected.">G ".$member['gruppen_id']%1000 .": ".$member['vorname'][0].". ".$member['name']."</option>\n";
		  }
	  }

	  echo "             </select></font>";
	  echo "          </form>";
     } else {
          echo "<font color=".$color_use.">Gruppe ".($row['gruppen_id']%1000).": ".$row["name"]." ".$row["telefon"]."</font>";
     }

}

/**
 * Ausgabe der Links im Hauptmenue und im Foodsoft-Kopf
 */
function areas_in_menu($area){
  open_tr();
    open_td('', '', fc_link( $area['area'], array(
      'window_id' => 'main', 'text' => $area['title'], 'title' => $area['hint'] , 'class' => 'bigbutton'
    ) ) );
    // open_td( 'small middle', '', $area['hint'] );
}

function areas_in_head($area){
  open_li( '', '', fc_link( $area['area'], array(
    'window_id' => 'main', 'text' => $area['title'], 'title' => $area['hint'] , 'class' => 'href'
  ) ) );
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
  global $muell_id, $input_event_handlers;

  if( $editAmounts ) {
    open_form( '', '', 'action=basarzuteilung' );
    $cols=11;
  } else {
    $cols=9;
  }

  open_table('list');

  $legend = array(
    "<th>" . fc_link( 'self', "orderby=produktname,text=Produkt,title=Sortieren nach Produkten" ) ."</th>"
  , "<th>" . fc_link( 'self', "orderby=bestellung,text=Bestellung,title=Sortieren nach Bestellung" ) ."</th>"
  , "<th>" . fc_link( 'self', "orderby=datum,text=Lieferdatum,title=Sortieren nach Lieferdatum" ) ."</th>"
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

  $last_key = '';
  $row_index=0;
  $js = '';
  $fieldcount = 0;
  $gesamtwert = 0;
  foreach( sql_basar( $bestell_id, $order ) as $basar_row ) {
     kanonische_einheit( $basar_row['verteileinheit'], & $kan_verteileinheit, & $kan_verteilmult );
     $menge=$basar_row['basar'];
     // umrechnen, z.B. Brokkoli von: x * (500g) nach (x * 500) g:
     $menge *= $kan_verteilmult;
     $wert = $basar_row['basar'] * $basar_row['endpreis'];
     $gesamtwert += $wert;
     $rechnungsstatus = getState( $basar_row['gesamtbestellung_id'] );

     $row = array( 
       "<td>{$basar_row['produkt_name']}</td>"
     , "<td>" . fc_link( 'bestellschein', array(
          'bestell_id' => $basar_row['gesamtbestellung_id'], 'text' => $basar_row['bestellung_name'], 'class' => 'href'
        ) ) . "</td>"
     , "<td>{$basar_row['lieferung']}</td>"
     , "<td class='mult'>"
         . fc_link( 'produktdetails', array(
             'class' => 'href', 'produkt_id' => $basar_row['produkt_id']
           , 'text' => sprintf( "%.2lf", $basar_row['endpreis'] )
           ) )
         . "</td>
         <td class='unit'>/ $kan_verteilmult $kan_verteileinheit</td>"
     , "<td class='mult'><b>$menge</b></td>
        <td class='unit' style='border-right-style:none;'>$kan_verteileinheit</td>
        <td class='unit'>"
        . fc_link( 'produktverteilung', array( 'class' => 'question', 'text' => false
           , 'bestell_id' => $basar_row['gesamtbestellung_id'], 'produkt_id' => $basar_row['produkt_id']
        ) ) . "</td>"
     , "<td class='number' style='padding:0pt 1ex 0pt 1ex;'><b>" . sprintf( "%8.2lf", $wert ) . "</b></td>"
     , ( $editAmounts ? ( $rechnungsstatus < STATUS_ABGERECHNET ?
         "<td class='mult' style='padding:0pt 1ex 0pt 1ex;'>
          <input type='hidden' name='produkt$fieldcount' value='{$basar_row['produkt_id']}'>
          <input type='hidden' name='bestellung$fieldcount' value='{$basar_row['gesamtbestellung_id']}'>
          <input name='menge$fieldcount' type='text' size='5' $input_event_handlers></td>
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
    open_td( 'number', '', price_view( $gesamtwert ) );
    if( $editAmounts )
      open_td();

  echo $js;

  if( $editAmounts ) {
    open_tr();
      open_td( 'right medskip', "colspan='$cols'" );
        open_select( 'gruppen_id' );
          echo optionen_gruppen( false, "( aktiv or ( id = $muell_id ) )" );
        close_select();
        hidden_input( 'fieldcount', $fieldcount );
        qquad();
        submission_button('Zuteilen');
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
  global $input_event_handlers;

  $basar_id = sql_basar_id();
  $muell_id = sql_muell_id();

  $produkte = sql_bestellung_produkte( $bestell_id, $gruppen_id, 0 );
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
      if( $gruppen_id == $basar_id ) {
        $col[PR_COL_LIEFERMENGE] = array(
          'title' => "der Gruppe zugeteilte Menge", 'header' => "Basarbestand", 'cols' => 2
        );
      } else {
        $col[PR_COL_LIEFERMENGE] = array(
          'title' => "der Gruppe zugeteilte Menge", 'header' => "Zuteilung", 'cols' => 2
        );
      }
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
          $opts_drop .= "<option title='{$c['title']}' value='$n'>"
          . preg_replace( '/<br>/', ' ', $c['header'] ) . "</option>";
        } else {
          $opts_insert .= "<option title='{$c['title']}' value='$n'>"
            . preg_replace( '/<br>/', ' ', $c['header'] ) . "</option>";
        }
      }
    }
    if( $opts_insert ) {
      open_option_menu_row();
        open_td( '', '', 'Spalten einblenden:' );
        open_td( '', '', "<select id='select_insert_cols'
            onchange=\"insert_col('" . self_url('spalten') . "',$spalten);\"
            ><option selected>(bitte wählen)</option>$opts_insert</select></td>
        " );
      close_option_menu_row();
    }
    if( $opts_drop ) {
      open_option_menu_row();
        open_td( '', '', 'Spalten ausblenden:' );
        open_td( '', '', "<select id='select_drop_cols'
          onchange=\"drop_col('" . self_url('spalten') . "',$spalten);\"
           ><option selected>(bitte wählen)</option>$opts_drop</select></td>
        " );
      close_option_menu_row();
    }
  }

  if( $editAmounts ) {
    open_form( '', '', 'action=update' );
    floating_submission_button();
  }

  open_table( 'list hfill' );

    open_tr('legende');
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
    close_tr();

    if( $cols > $cols_vor_summe ) { // mindestens eine summenspalte ist aktiv
      $summenzeile = "
        open_tr( 'summe', \"id='row_total'\" );
          open_td( 'right', \"colspan='$cols_vor_summe'\", 'Summe:' );
          if( $spalten & PR_COL_NETTOSUMME )
            open_td( 'number', '', price_view( \$netto_summe ) );
          if( $spalten & PR_COL_BRUTTOSUMME )
            open_td( 'number', '', price_view( \$brutto_summe ) );
          if( $spalten & PR_COL_ENDSUMME )
            open_td( 'number', '', price_view( \$endpreis_summe ) );
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

    foreach( $produkte as $produkte_row ) {
      $produkt_id = $produkte_row['produkt_id'];

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
            } else if ( $gruppen_id == $basar_id ) {
              $liefermenge = sql_basarmenge( $bestell_id, $produkt_id );
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

      $nettogesamtpreis = sprintf( '%.2lf', $nettopreis * $liefermenge );
      $bruttogesamtpreis = sprintf( '%.2lf', $bruttopreis * $liefermenge );
      $endgesamtpreis = sprintf( '%.2lf', $endpreis * $liefermenge );

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

      open_tr();
        if( $spalten & PR_COL_NAME )
          open_td( 'left', '', $produkte_row['produkt_name'] );

        if( $spalten & PR_COL_ANUMMER )
          open_td( 'right', '', $produkte_row['artikelnummer'] );

        if( $spalten & PR_COL_BNUMMER )
          open_td( 'right', '', $produkte_row['bestellnummer'] );

        if( $spalten & PR_COL_LPREIS ) {
          open_td( 'mult', '', fc_link( 'produktdetails',
            "class=href,bestell_id=$bestell_id,produkt_id=$produkt_id,text=".sprintf( "%.2lf", $nettolieferpreis ) ) );
          open_td( 'unit', '', "/ {$produkte_row['preiseinheit']}" );
        }

        if( $spalten & PR_COL_MWST )
          open_td( 'number', '', $produkte_row['mwst'] );

        if( $spalten & PR_COL_PFAND )
          open_td( 'number', '', $produkte_row['pfand'] );

        if( $spalten & PR_COL_VPREIS ) {
          open_td( 'mult', '', price_view( $produkte_row['preis'] ) );
          open_td( 'unit', '', "/ {$produkte_row['kan_verteilmult']} {$produkte_row['kan_verteileinheit']}" );
        }

        if( $spalten & PR_COL_BESTELLMENGE ) {
          open_td( 'mult' );
            printf( '%u / %u', $festbestellmenge * $kan_verteilmult
                  , ( ( $gruppen_id == $basar_id ) ? $basarbestellmenge : $toleranzbestellmenge ) * $kan_verteilmult );
            if( ! $gruppen_id )
              printf( ' / %u', $basarbestellmenge * $kan_verteilmult );
          open_td( 'unit', '', $produkte_row['kan_verteileinheit'] );
        }

        if( $spalten & PR_COL_BESTELLGEBINDE ) {
          open_td( 'mult' );
            if( $state == STATUS_BESTELLEN and ! $gruppen_id ) {
              open_span( 'bold', '', $gebinde );
              echo ' / ';
            }
            printf( '%.2lf / %.2lf', $festbestellmenge / $gebindegroesse , $gesamtbestellmenge / $gebindegroesse );
          open_td( 'unit' );
            printf( ' * (%s %s)', $produkte_row['kan_verteilmult'] * $produkte_row['gebindegroesse'], $produkte_row['kan_verteileinheit'] );
        }

        if( $spalten & PR_COL_LIEFERMENGE ) {
          if( $gruppen_id ) {    // Gruppenansicht: 2 spalten, V-Einheit benutzen:
            open_td( 'mult', '', sprintf( '%d', $liefermenge * $kan_verteilmult ) );
            open_td( 'unit', '', $produkte_row['kan_verteileinheit'] );

          } else {               // Gesamtansicht: 4 spalten, Preis-Einheit benutzen:
            open_td( 'mult' );
              if( $editAmounts ) {
                printf( "
                  <input name='liefermenge$produkt_id' class='right' type='text' size='6' value='%.3lf'
                    title='tats&auml;chliche Liefermenge eingeben' $input_event_handlers >"
                , $liefermenge_scaled
                );
              } else {
                printf( "%.3lf", $liefermenge_scaled );
              }
            open_td( 'unit', "style='border-right-style:none;'", $produkte_row['preiseinheit'] );
            open_td( '', "style='border-left-style:none;border-right-style:none;'" );
              if( $editAmounts ) {
                //Checkbox für fehlende Lieferung. Löscht auch glei Einträge in der Verteiltabelle
                ?> <input  title='Wurde nicht geliefert' type='checkbox' name='nichtGeliefert[]' value='<? echo $produkt_id; ?>'
                     <? echo $input_event_handlers; ?> > <?
              }
            open_td( '', "style='border-left-style:none;'", fc_link( 'produktverteilung', "class=question,text=,bestell_id=$bestell_id,produkt_id=$produkt_id" ) );
          }
        }

        if( $spalten & PR_COL_LIEFERGEBINDE ) {
          open_td( 'mult', '', price_view( $gebinde ) );  //  <- sic: ggf. auch bruchteile anzeigen!
          open_td( 'unit', '', sprintf( ' * (%s %s)'
                                      , $produkte_row['kan_verteilmult'] * $produkte_row['gebindegroesse']
                                      , $produkte_row['kan_verteileinheit'] ) );
        }

        if( $spalten & PR_COL_NETTOSUMME )
          open_td( 'number', '', price_view( $nettogesamtpreis ) );

        if( $spalten & PR_COL_BRUTTOSUMME )
          open_td( 'number', '', price_view( $bruttogesamtpreis ) );

        if( $spalten & PR_COL_ENDSUMME )
          open_td( 'number', '', price_view( $endgesamtpreis ) );

    } //end while produkte array

    eval( $summenzeile );

  close_table();
  if($editAmounts){
    floating_submission_button();
    close_form();
  };

  if( $haben_nichtgeliefert && $select_nichtgeliefert ) {
    open_option_menu_row();
      open_td( '', "colspan='2'" );
        option_checkbox( 'spalten', PR_ROWS_NICHTGELIEFERT, "$nichtgeliefert_header zeigen"
                       , "$nichtgeliefert_header vorhanden; diese auch anzeigen?" );
    close_option_menu_row();
  }
  if( $option_nichtgefuellt && $haben_nichtgefuellt ) {
    open_option_menu_row();
      open_td( '', "colspan='2'" );
        option_checkbox( 'spalten', PR_ROWS_NICHTGEFUELLT, "nicht-volle Gebinde zeigen"
                       , 'nicht gefuellte Gebinde vorhanden; diese auch anzeigen?' );
    close_option_menu_row();
  }
}


/**
 * Liste zur Auswahl einer Bestellung via Link
 */
function select_bestellung_view() {
  global $self, $foodsoftdir, $dienst, $login_gruppen_id, $mysqljetzt;

  echo "<h1 class='bigskip'>Liste aller Bestellungen</h1>";

  open_table( 'list hfill' );
    open_th('','','Name');
    open_th('','','Status');
    open_th('','','Bestellzeitraum');
    open_th('','','Lieferung');
    open_th('','','Summe');
    open_th('','','Detailansichten');
    if( $dienst != 0 )
      open_th('','','Aktionen');

  foreach( sql_bestellungen() as $row ) {
    $bestell_id = $row['id'];
    $rechnungsstatus = getState( $bestell_id );
    $abrechnung_dienstkontrollblatt_id = $row['abrechnung_dienstkontrollblatt_id'];
    $views = array();
    $actions = array();

    switch( $rechnungsstatus ) {

      case STATUS_BESTELLEN:
        $views[] = fc_link( 'bestellschein', "class=href,bestell_id=$bestell_id,text=Bestellschein (vorl&auml;ufig)" );
        if( hat_dienst(4) ) {
          if ( $row['bestellende'] < $mysqljetzt ) {
            $actions[] = fc_action( array( 'text' => '>>> Bestellschein fertigmachen >>>'
                                         , 'title' => 'Jetzt Bestellschein für Lieferanten fertigmachen?'
                                         , 'confirm' => 'Jetzt Bestellschein für Lieferanten fertigmachen?' )
                                  , array( 'action' => 'changeState'
                                         , 'change_id' => $bestell_id, 'change_to' => STATUS_LIEFERANT ) );
          } else {
            $actions[] = "<div class='alert qquad'>Bestellung läuft noch!</div>";
          }
          $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
          if( sql_references_gesamtbestellung( $bestell_id ) == 0 ) {
            $actions[] = fc_action( "title=Bestellung löschen,class=drop,text=löschen", "action=delete,delete_id=$bestell_id" );
          }
        }
        break;

      case STATUS_LIEFERANT:
        $views[] = fc_link( 'bestellschein', "class=href,bestell_id=$bestell_id,text=Bestellschein" );
        if( hat_dienst(4) ) {
          $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
          $actions[] = fc_action( array( 'text' => '<<< Nachbestellen lassen <<<'
                                       , 'title' => 'Bestellung nochmal zum Bestellen freigeben?' )
                                , array( 'action' => 'changeState'
                                       , 'change_id' => $bestell_id, 'change_to' => STATUS_BESTELLEN ) );
        }
        if( $dienst > 0 )
          $actions[] = fc_action( array( 'text' => '>>> Lieferschein erstellen >>>'
                                       , 'title' => 'Bestellung wurde geliefert, Lieferschein abgleichen?'
                                       , 'confirm' => 'Bestellung wurde geliefert, Lieferschein abgleichen?' )
                                , array( 'action' => 'changeState'
                                       , 'change_id' => $bestell_id, 'change_to' => STATUS_VERTEILT ) );
        break;

      case STATUS_VERTEILT:
        $views[] = fc_link( 'lieferschein', "class=href,bestell_id=$bestell_id,text=Lieferschein" );
        if( $dienst > 0 )
          $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id" );
        if( hat_dienst(4) ) {
          $actions[] = fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=Stammdaten &auml;ndern..." );
          $actions[] = fc_link( 'abrechnung', "bestell_id=$bestell_id,text=Abrechnung beginnen..." );
        }
        break;

      case STATUS_ABGERECHNET:
        $views[] = fc_link( 'lieferschein', "class=href,bestell_id=$bestell_id,text=Lieferschein" );
        $views[] = fc_link( 'abrechnung', "class=href,bestell_id=$bestell_id" );
        if( $dienst > 0 )
          $views[] = fc_link( 'verteilliste', "class=href,bestell_id=$bestell_id" );
        break;

      case STATUS_ARCHIVIERT:
      default:
        break;
    }

    open_tr('',"id='row$bestell_id'" );
      open_td('','', $row['name'] );
      open_td('','', rechnung_status_string( $row['rechnungsstatus'] ) );
      open_td();
        open_div( 'left', '',  $row['bestellstart'] );
        open_div( 'right', '', "- ".$row['bestellende'] );
      open_td( '', '', $row['lieferung'] );
      open_td();
        if( $rechnungsstatus == STATUS_ABGERECHNET ) {
          open_div( '', '', price_view( sql_bestellung_rechnungssumme( $bestell_id ) ) );
          open_div( 'small', '', sql_dienstkontrollblatt_name( $abrechnung_dienstkontrollblatt_id ) );
        } else {
          echo '-';
        }
      open_td();
        if( $views ) {
          open_ul('plain');
            foreach( $views as $view )
              open_li( '', '', $view );
          close_ul();
        } else {
          echo '-';
        }
      if( $dienst != 0 ) {
        open_td();
          if( $actions ) {
            open_ul('plain');
              foreach( $actions as $action )
                open_li( '', '',  $action ); 
            close_ul();
          } else {
            echo '-';
          }
      }
  }
  close_table();
}

function select_products_not_in_list($bestell_id){
  ?> Produkt: <?
  open_select( 'produkt_id' );
    echo "<option value='0' selected>(Bitte Produkt wählen)</option>";
    foreach( getProdukteVonLieferant( sql_bestellung_lieferant_id( $bestell_id ), $bestell_id ) as $prod ) {
      echo "<option value='".$prod['produkt_id']."'>"
      . $prod['name'] . " (" . $prod['verteileinheit']. ") " ."</option>";
    }
  close_select();
}

function distribution_tabellenkopf() {
  open_tr('legende');
    open_th(''       ,''           ,'Gruppe');
    open_th('oneline','colspan="2"','bestellt (toleranz)');
    open_th(''       ,'colspan="2"','geliefert');
    open_th(''       ,''           ,'Gesamtpreis');
  close_tr();
}

function distribution_produktdaten( $bestell_id, $produkt_id ) {
  $produkt = sql_bestellvorschlag( $bestell_id, $produkt_id );
  open_tr();
    open_th( '', "colspan='6'" );
      open_div( '', "style='font-size:1.2em; margin:5px;'" );
        echo fc_link( 'produktpreise', array(
         'text' => $produkt['produkt_name'], 'class' => 'href', 'produkt_id' => $produkt_id ) );
      close_div();
      open_div('small');
        printf( "Preis: %.2lf / %s, Produktgruppe: %s"
          , $produkt['preis']
          , $produkt['verteileinheit']
          , $produkt['produktgruppen_name']
        );
      close_div();
  close_tr();
}

function distribution_view( $bestell_id, $produkt_id, $editable = false ) {
  $vorschlag = sql_bestellvorschlag($bestell_id,$produkt_id);
  preisdatenSetzen( & $vorschlag );
  $verteilmult = $vorschlag['kan_verteilmult'];
  $verteileinheit = $vorschlag['kan_verteileinheit'];
  $preis = $vorschlag['preis'];
  $liefermenge = $vorschlag['liefermenge'] * $verteilmult;

  open_tr('summe');
    open_th('', "colspan='3'", 'Liefermenge:' );
    open_td('mult','',int_view( $liefermenge, ( $editable ? "liefermenge_{$bestell_id}_{$produkt_id}" : false ) ) );
    open_td('unit','',$verteileinheit );
    open_td('number','', price_view( $preis * $liefermenge / $verteilmult ) );
  close_tr();

  $basar_id = sql_basar_id();
  $muell_id = sql_muell_id();
  $basar_festmenge = 0;
  $basar_toleranzmenge = 0;
  $basar_verteilmenge = sql_basarmenge( $bestell_id, $produkt_id ) * $verteilmult;
  $muellmenge = 0;

  foreach( sql_bestellung_gruppen( $bestell_id, $produkt_id ) as $gruppe ) {
    $gruppen_id = $gruppe['id'];
    $mengen = sql_select_single_row( select_bestellung_produkte( $bestell_id, $gruppen_id, $produkt_id ), true );
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
        $basar_toleranzmenge = $mengen['toleranzbestellmenge'] * $verteilmult;
        $basar_festmenge = $mengen['gesamtbestellmenge'] * $verteilmult - $basar_toleranzmenge;
        continue 2;
    }
    open_tr();
      open_td( '', '', "{$gruppe['gruppennummer']} {$gruppe['name']}" );
      open_td( 'mult', '', int_view($festmenge) . " (".int_view($toleranzmenge) .")" );
      open_td( 'unit', '', $verteileinheit );
      open_td( 'mult', '', int_view( $verteilmenge, ( $editable ? "menge_{$bestell_id}_{$produkt_id}_{$gruppen_id}" : false ) ) );
      open_td( 'unit', '', $verteileinheit );
      open_td( 'number', '', price_view( $preis * $verteilmenge / $verteilmult ) );
  }
  open_tr('summe');
    open_td('', "colspan='3'", "M&uuml;ll:" );
    open_td( 'mult', '', int_view( $muellmenge, ( $editable ? "menge_{$bestell_id}_{$produkt_id}_{$muell_id}" : false ) ) );
    open_td( 'unit', '', $verteileinheit );
    open_td( 'number', '', price_view( $preis * $muellmenge / $verteilmult ) );
  open_tr('summe');
    open_td('', '', "Basar:" );
    open_td( 'mult', '', int_view($basar_festmenge) . " (".int_view($basar_toleranzmenge).")" );
    open_td( 'unit', '', $verteileinheit );
    open_td( 'mult', '', int_view( $basar_verteilmenge ) );
    open_td( 'unit', '', $verteileinheit );
    open_td( 'number', '', price_view( $preis * $basar_verteilmenge / $verteilmult ) );
  close_tr();
}

function bestellung_overview($row, $showGroup=FALSE, $gruppen_id = NULL){
  global $login_gruppen_id, $window_id;
  $bestell_id = $row['id'];

  open_table('list');
      open_th('','','Bestellung:');
      open_td('bold large');
        echo fc_link( 'lieferschein', array(
          'class' => 'href', 'text' => $row['name'], 'bestell_id' => $row['id']
          , 'title' => 'zum Bestellschein/Lieferschein...'
        ) );
        if( hat_dienst(4) and getState( $bestell_id ) < STATUS_ABGERECHNET )
          echo fc_link( 'edit_bestellung', "bestell_id=$bestell_id,text=" );
        if(sql_dienste_nicht_bestaetigt($row['lieferung']))
          div_msg( 'bold warn', "Vorsicht:". fc_link( 'dienstplan', 'class=href,text=Dienstegruppen abwesend?' ) );
    open_tr();
      open_th('','','Lieferant:');
      open_td('','', fc_link( 'edit_lieferant', array( 'text' => sql_lieferant_name( $row['lieferanten_id'] )
                                                     , 'class' => 'href' , 'lieferanten_id' => $row['lieferanten_id'] ) ) );
    open_tr();
      open_th('','','Bestellzeitraum:');
      open_td('','', $row['bestellstart'] .' - '. $row['bestellende'] );
    open_tr();
      open_th('','','Lieferung:');
      open_td('','', $row['lieferung'] );
  if( $window_id != 'abrechnung' ) {
    open_tr();
      open_th('','','Status:');
      open_td();
        abrechnung_kurzinfo( $bestell_id );
  }
  if( $showGroup and $gruppen_id ){
    open_tr();
      open_th('','','Gruppe:');
        if( $gruppen_id == sql_basar_id() ) {
          open_td( 'warn', '', 'Basar' );
        } elseif( $gruppen_id == sql_muell_id() ) {
          open_td( 'warn', '', 'Müll' );
        } else {
          open_td( '', '', gruppe_view( $gruppen_id ) );
          if( hat_dienst(4) or ( $gruppen_id == $login_gruppen_id ) ) {
            $kontostand = kontostand($gruppen_id);
            open_tr();
              open_th('','','Kontostand:');
              open_td( $kontostand < 0 ? 'crit' : '' );
                echo fc_link( hat_dienst(4) ? 'gruppenkonto' : 'meinkonto'
                            , array( 'gruppen_id' => $gruppen_id, 'class' => 'href', 'text' => price_view( $kontostand ) ) );
          }
        }
  }
  close_table();
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
     <div class='quad smallskip'>
     <table class='layout hfill' style='color:#ed0000;'>
      <tr>
        <td class='small'>Rechnungsnummer:</td>
        <td class='small right'>". $row['rechnungsnummer'] ."</td>
      </tr>
      <tr>
        <td class='small'>Rechnungssumme:</td>
        <td class='small right'>". sprintf( '%.2lf', sql_bestellung_rechnungssumme($bestell_id) ) ."</td>
      </tr>
      <tr>
        <td class='small'>abgerechnet von:</td>
        <td class='small right'>". sql_dienstkontrollblatt_name( $row['abrechnung_dienstkontrollblatt_id'] ) ."</td>
      </tr>
    </table>
    </div>";
  } else {
    $text = rechnung_status_string( $status );
  }
  echo fc_link( 'abrechnung', array( 'bestell_id' => $bestell_id, 'class' => 'href' , 'text' => $text ) );
}

function buchung_kurzinfo( $id ) {
  global $muell_id, $login_gruppen_id;
  $row = sql_get_transaction( $id );
  $dir = ( ( $row['haben'] > 0 ) ? 'durch' : 'an' );
  if( $id > 0 ) { // bankueberweisung oder lastschrift
    $konto_id = $row['konto_id'];
    $auszug_nr = $row['kontoauszug_nr'];
    $auszug_jahr = $row['kontoauszug_jahr'];
    echo "Auszug: " . fc_link( 'kontoauszug', array(
                'konto_id' => $konto_id, 'auszug_jahr' => $auszug_jahr, 'auszug_nr' => $auszug_nr
              , 'text' => "$auszug_jahr / $auszug_nr ({$row['kontoname']})", 'class' => 'href' ) );
  } elseif( ( $gruppen_id = $row['gruppen_id'] ) > 0 ) {  // zahlung gruppe
    if( $gruppen_id == $muell_id ) {
      $typ = $row['transaktionstyp'];
      echo "interne Verrechnung: ";
      echo fc_link( 'verluste', array( 'class' => 'href', 'detail' => $typ, 'text' => transaktion_typ_string( $typ ) ) );
    } else {
       $gruppen_name = sql_gruppenname($gruppen_id);
       if( ($gruppen_id == $login_gruppen_id) or hat_dienst(4,5) )
         echo "Zahlung $dir Gruppe " . fc_link( 'gruppenkonto', array(
                'gruppen_id' => $gruppen_id, 'class' => 'href', 'text' => sql_gruppenname( $gruppen_id ) ) );
       else
         echo "Zahlung $dir Gruppe $gruppen_name";
    }
  } else { // zahlung lieferant:
     $lieferanten_id = $row['lieferanten_id'];
     echo "Zahlung $dir Lieferant: " . fc_link( 'lieferantenkonto', array(
          'class' => 'href', 'lieferanten_id' => $lieferanten_id, 'text' => sql_lieferant_name( $lieferanten_id ) ) );
  }
}


// preishistorie_view:
//  - kann preishistorie anzeigen
//  - kann preisauswahl fuer eine bestellung erlauben
//
function preishistorie_view( $produkt_id, $bestell_id = 0, $editable = false, $mod_id = false ) {
  global $mysqljetzt;
  need( $produkt_id );

  if( $bestell_id ) {
    $bestellvorschlag = sql_bestellvorschlag( $bestell_id, $produkt_id );
    $preisid_in_bestellvorschlag = $bestellvorschlag['preis_id'];
    $rechnungsstatus = getState( $bestell_id );
    $bestellung_name = bestellung_name( $bestell_id );
    $legend = "Preiseintrag wählen für Bestellung $bestellung_name";
  } else {
    $legend = "Preis-Historie";
  }

  open_fieldset( 'big_form', '', $legend, 'on' );
    open_table( 'list hfill' );
      open_th( '', "title='Interne eindeutige ID-Nummer des Preiseintrags'", 'id' );
      open_th( '', "title='Bestellnummer'", 'B-Nr' );
      open_th( '', "title='Preiseintrag gültig ab'", 'von' );
      open_th( '', "title='Preiseintrag gültig bis'", 'bis' );
      open_th( '', "title='Liefer-Einheit: fürs Bestellen beim Lieferanten' colspan='2'", 'L-Einheit' );
      open_th( '', "title='Nettopreis beim Lieferanten' colspan='2'", 'L-Preis' );
      open_th( '', "title='Verteil-Einheit: f&uuml;rs Bestellen und Verteilen bei uns' colspan='2'", 'V-Einheit' );
      open_th( '', "title='Gebindegröße in V-Einheiten'", 'Gebinde' );
      open_th( '', '', 'MWSt' );
      open_th( '', "title='Pfand je V-Einheit'", 'Pfand' );
      open_th( '', "title='Endpreis je V-Einheit' colspan='2'", 'V-Preis' );

  if( $bestell_id )
    open_th( '', "title='Preiseintrag für Bestellung $bestellung_name'", 'Aktiv' );

  foreach( sql_produktpreise( $produkt_id ) as $pr1 ) {
    preisdatenSetzen( &$pr1 );
    // var_export( $pr1 );
    $references = references_produktpreise( $pr1['id'] );
    open_tr();
      open_td( 'oneline' );
        echo $pr1['id'];
        if( $editable and ( $references == 0 ) and $pr1['zeitende'] ) {
          echo fc_action( array( 'class' => 'drop', 'text' => ''
                               , 'title' => 'Dieser Preiseintrag wird nicht verwendet; löschen?' )
                          , "action=delete_price,preis_id={$pr1['id']}" );
        }
      open_td( '', '', $pr1['bestellnummer'] );
      open_td( 'center', '', $pr1['datum_start'] );
      open_td( 'center' );
        if( $pr1['zeitende'] ) {
          echo "{$pr1['datum_ende']}";
        } else {
          if( $editable ) {
            echo fc_action( array( 'class' => 'button', 'text' => 'Abschließen'
                                 , 'title' => "Preisintervall abschließen (z.B. falls Artikel nicht lieferbar)" )
                          , array( 'action' => 'zeitende_setzen', 'preis_id' => $pr1['id']
                                 , 'day' => date('d'), 'month' => date('m'), 'year' => date('Y') ) );
          } else {
            echo " - ";
          }
        }
      open_td( 'mult', '', $pr1['kan_liefermult'] );
      open_td( 'unit', '', $pr1['kan_liefereinheit'] );
      open_td( 'mult', '', price_view( $pr1['nettolieferpreis'] ) );
      open_td( 'unit', '', $pr1['preiseinheit'] );
      open_td( 'mult', '', $pr1['kan_verteilmult'] );
      open_td( 'unit', '', $pr1['kan_verteileinheit'] );
      open_td( 'number', '', $pr1['gebindegroesse'] );
      open_td( 'number', '', $pr1['mwst'] );
      open_td( 'number', '', $pr1['pfand'] );
      open_td( 'mult', '', price_view( $pr1['preis'] ) );
      open_td( 'unit', '', "/ {$pr1['kan_verteilmult']} {$pr1['kan_verteileinheit']}" );

    if( $bestell_id ) {
      open_td( '', "style='padding:1ex 1em 1ex 1em;'" );
      if( $pr1['id'] == $preisid_in_bestellvorschlag ) {
        echo fc_link( '', array( 'class' => 'buttondown', 'text' => ' aktiv ', 'url' => ''
                               , 'title' => "gilt momentan f&uuml;r Bestellung $bestellung_name" ) );
      } else {
        if( $editable and ( $rechnungsstatus < STATUS_ABGERECHNET ) ) {
          echo fc_action( array( 'class' => 'buttonup', 'text' => ' setzen '
                               , 'title' => "Preiseintrag für Bestellung $bestellung_name auswählen" )
                        , array( 'action' => 'preiseintrag_waehlen', 'preis_id' => $pr1['id'] ) );
        } else {
          echo " - ";
        }
      }
    }
  }
  close_table();

  produktpreise_konsistenztest( $produkt_id, $editable, 0 );

  close_fieldset();
}


function auswahl_lieferant( $selected = 0 ) {
  $lieferanten = sql_lieferanten();
  if( ! $lieferanten ) {
    div_msg( 'warn left', "noch keine " . fc_link( 'lieferanten', 'text=Lieferanten,window_id=main,class=href' ). ' eingetragen!' );
    return;
  } else if( ! $selected ) {
    ?> <h4> Bitte Lieferant auswählen: </h4> <?
  } else {
    ?> <h4> Lieferanten der Foodcoop: </h4> <?
  }
  open_table('list',"width:600px;");
      open_th( '', '', 'Lieferanten' );
      open_th( '', '', 'Produkte' );
      open_th( '', '', 'Pfandverpackungen' );
    foreach( $lieferanten as $row ) {
      open_tr( ( $row['id'] == $selected ) ? 'active' : '' );
        open_td( '', '', fc_link( '', array( 'title' => 'Lieferant auswählen', 'lieferanten_id' => $row['id'], 'text' => $row['name'] ) ) );
        open_td( '', '', $row['anzahl_produkte'] );
        open_td( '', '', $row['anzahl_pfandverpackungen'] );
    }
  close_table();
}

function auswahl_konto( $selected = 0 ) {
  $konten = sql_konten();
  if( ! $konten ) {
    div_msg( 'warn left', 'noch keine Konten eingetragen!' );
    return;
  } else if( ! $selected ) {
    ?> <h4> Bitte Bankkonto auswählen: </h4> <?
  } else {
    ?> <h4> Bankkonten der Foodcoop: </h4> <?
  }
  open_table('list');
    open_th('','','Name');
    open_th('','','BLZ');
    open_th('','','Konto-Nr');
    open_th('','','Saldo');
    open_th('','','Online-Banking');
    open_th('','','Kommentar');
    if( hat_dienst(4) )
      open_th('','','Aktionen');
  foreach( sql_konten() as $row ) {
    open_tr( ( $row['id'] == $selected ) ? 'active' : '' );
      open_td( 'bold', '', fc_link( '', array( 'title' => 'Konto auswählen', 'konto_id' => $row['id'], 'text' => $row['name']
                                             , 'auszug_nr' => NULL, 'auszug_jahr' => NULL ) ) );
      open_td( '', '', $row['blz'] );
      open_td( '', '', $row['kontonr'] );
      open_td( 'number', '', price_view( sql_bankkonto_saldo( $row['id'] ) ) );
      if( ( $url = $row['url'] ) ) {
        open_td( '', '',"<a href=\"javascript:window.open('$url','onlinebanking').focus();\">$url</a></td>" );
      } else {
        open_td( '', '', '-' );
      }
      open_td( '', '', $row['kommentar'] );
      if( hat_dienst(4) )
        open_td( '', '', fc_link( 'edit_konto', "class=edit,text=,konto_id={$row['id']}" ) );
  }
  close_table();
}

function auswahl_bestellung( $bestell_id = 0 ) {
  global $mysqljetzt;
  $laufende_bestellungen = sql_bestellungen( STATUS_BESTELLEN );
  if( !  $laufende_bestellungen ) {
    div_msg( 'kommentar', 'Zur Zeit laufen leider keine Bestellungen!' );
    return;
  }
  open_table( 'list', "style='width:600px;'" );
      open_th( '', '', 'Name' );
      open_th( '', '', 'Lieferant' );
      open_th( '', '', 'Bestellschluss' );
      open_th( '', '', 'Lieferung' );
      open_th( '', '', 'Produkte' );

    foreach( $laufende_bestellungen as $row ) {
      $id = $row['id'];
      //jetzt die anzahl der produkte bestimmen ...
      $num = sql_select_single_field(
        "SELECT COUNT(*) as num FROM bestellvorschlaege WHERE gesamtbestellung_id=$id", 'num'
      );
      open_tr( $id == $bestell_id ? 'active' : '' );
      if( $id != $bestell_id )
        open_td( '', '', fc_link( 'bestellen', array( 'bestell_id' => $id, 'text' => $row['name'] ) ) );
      else
        open_td( 'bold', '', $row['name'] );
      open_td( '', '', lieferant_view($row['lieferanten_id']) );
      open_td( ( $row['bestellende'] < $mysqljetzt ? 'bold' : '' ), '', $row['bestellende'] );
      open_td( '', '', fc_link( 'bestellschein', array( 'title' => 'zum Bestellschein'
                       , 'class' => 'href', 'bestell_id' => $id, 'text' => $row['lieferung'] ) ) );
      open_td( '', '', $num );
    }
  close_table();
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
  if( $editable or $super_edit )
    open_form( 'big_form' , '', 'action=edit' );

  open_table('list');
  if( $head ) {
    open_th( '', '', 'Vorname' );
    open_th( '', '', 'Name' );
    open_th( '', '', 'Mail' );
    open_th( '', '', 'Telefon' );
    open_th( '', '', 'Diensteinteilung' );
    if($super_edit)
      open_th( '', '', 'Aktionen' );
  }

  foreach( sql_gruppen_members( $gruppen_id ) as $row ) {
    open_tr();
      $id = $row['id'];
      open_td( '', '', string_view( $row['vorname'], 16, $editable ? "vorname_$id" : false ) );
      open_td( '', '', string_view( $row['name'], 20, $editable ? "name_$id" : false ) );
      open_td( '', '', string_view( $row['email'], 16, $editable ? "email_$id" : false ) );
      open_td( '', '', string_view( $row['telefon'], 12, $editable ? "telefon_$id" : false ) );

      if($super_edit){
        open_td( '', '', dienst_selector( $row['diensteinteilung'], $id ) );
        open_td( '', '', fc_action( array( 'img' => 'img/b_drop.png'
                                         , 'confirm' => 'Soll das Gruppenmitglied wirklich GELÖSCHT werden?' )
                                  , array( 'action' => 'delete', 'person_id' => $id ) ) );
      } else {
        open_td( '', '',  $row['diensteinteilung'] );
      }
  }
  if($super_edit or $editable) {
    open_tr();
      open_td( '', "colspan='6'" );
        submission_button();
  }
  close_table();
  if( $editable or $super_edit )
    close_form();
}

?>
