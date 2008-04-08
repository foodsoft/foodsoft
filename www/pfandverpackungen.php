<h1>Pfandverpackungen</h1>

<?PHP
  assert( $angemeldet ) or exit();
  $editable = ( ! $readonly and ( $dienst == 4 ) );

  get_http_var( 'bestell_id', 'u', false, true );
  if( $bestell_id ) {
    $bestellung_name = bestellung_name( $bestell_id );
    $lieferanten_id = getProduzentBestellID( $bestell_id );
  } else {
    $bestellung_name = '';
    get_http_var( 'lieferanten_id', 'u', false, true );
  }


  /////////////////////////////
  //
  //  auswahl lieferanten:
  //
  /////////////////////////////
  ?> <table width='100%' class='layout'><tr> <?


  if( $bestell_id ) {
    ?>
    <h2> 

  } else {
    ?>
      <td style='text-align:left;padding:1ex 1em 2em 3em;'>
      <table style="width:600px;" class="liste">
        <tr>
          <th>Lieferanten</th>
          <th>Produkte</th>
        </tr>
    <?
    $lieferanten = sql_lieferanten();
    while( $row = mysql_fetch_array($lieferanten) ) {
      if( $row['id'] != $lieferanten_id ) {
        echo "<tr><td><a class='tabelle' href='" . self_url('lieferanten_id') . "&lieferanten_id={$row['id']}'>{$row['name']}</a>";
      } else {
        echo "<tr class='active'><td>{$row['name']}";
      }
      ?>  </td><td> <? echo $row['anzahl_pfandverpackungen']; ?> </td>
        </tr>
      <?
    }
    ?>
          </table>
        </td>
      </tr>
      </table>
    <?
  }

  // ab hier muss ein Lieferant ausgewaehlt sein, sonst Ende:
  //
  if( ! $lieferanten_id )
    return;

  $lieferant_name = lieferant_name( $lieferanten_id );

  /////////////////////////////
  //
  // aktionen verarbeiten:
  //
  /////////////////////////////

  get_http_var('action','w','');

  if( $action == 'delete' and $editable ) {
    need_http_var('pfandverpackung_id','u');
    sql_delete_pfandverpackung( $pfandverpackung_id );
  }



//   /////////////////////////////
//   //
//   // Produkttabelle anzeigen:
//   //
//   /////////////////////////////
// 
//   $sql = "SELECT * FROM pfandverpackungen ";
//   $where = "WHERE lieferanten_id=$lieferanten_id";
//   if( $bestell_id ) {
//     $sql .= " LEFT JOIN pfandzuordnung ON pfandzuordnung.pfandverpackung_id = pfandverpackungen_id";
//     $where .= " AND pfandzuordnung.bestell_id = $bestell_id";
//   }
// 
//   $verpackungen = doSql(
//     "SELECT * FROM 
// 
//   ?>
//     <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu k�nnen -->
//     <form action='<? echo self_url(); ?>' name='reload_form' method='post'>
//       <? echo self_post(); ?>
//       <input type='hidden' name='action' value='nop'>
//       <input type='hidden' name='produkt_id' value='0'>
//     </form>
//   <?
// 
//   if ($edit_all) {
//     ?>
//       <form action="<? echo self_url(); ?>" name="editAllForm" method="POST">
//       <? echo self_post(); ?>
//       <input type="hidden" name="action" value="change_all">
//     <?
//   } else {
//     ?>
//       <form action="index.php?window=insertBestellung" method="post" target="insertBestellung" name="newBestellungForm">
//       <input type="hidden" name="lieferanten_id" value="<? echo $lieferanten_id; ?>">
//     <?
//   }
// 
//   if (!$edit_all) {   // f�r die normalansicht
//     ?>
//       <table class='numbers'>
//         <tr>
//           <th colspan="10"><h3>Produktübersicht von
//               <?php
//                 echo $lieferant_name;
//                 if ( $lieferant_name == "Terra" ) {
//                  ?> <a class="button" href="javascript:neuesfenster('index.php?window=artikelsuche','artikelsuche');">Katalogsuche</a> <?
//                 }
//                 if( 0 ) {
//                    ?> <a class="button" href="javascript:neuesfenster('index.php?window=terraabgleich&lieferanten_id=<? echo $lieferanten_id; ?>','terraabgleich;');">Datenbankabgleich</a> <?
//                 }
//               ?>
// 
//           </h3></th>
//         </tr><tr>
//           <th> </th>
//           <th title='generische Produktbezeichnung'>Bezeichnung</th>
//           <th>Produktgruppe</th>
//           <th title='aktuelle Details zum Produkt'>Notiz</th>
//           <th>Gebindegroesse</th>
//           <!-- <th>Kategorien</th> -->
//           <th colspan='2' title='Lieferanten-Preis (ohne Pfand, ohne MWSt)'>L-Nettopreis</th>
//           <th colspan='2' title='Verbraucher-Preis mit Pfand und MWSt'>V-Endpreis</th>
//           <th>Optionen</th>
//         </tr>
//     <?
//   } else {  //f�r die alle �berarbeiten ansicht
//     ?>
//       <table class='numbers'>
//         <tr>
//           <th colspan="7"><h3>Produktübersicht von <?php echo $lieferant_name?></h3></th>
//         </tr><tr>
//           <th>Bezeichnung</th>
//           <th>Produktgruppe</th>
//           <th>Einheit</th>
//           <th>Notiz</th>
//           <th>Gebindegr</th>
//           <th>Preis</th>
//           <th>Bestellnr</th>
//         </tr>	 
//     <?
//   }
// 
//   while( $row = mysql_fetch_array($produkte) ) {
//     $id = $row['id'];
//     $produkt = sql_produkt_details( $id, 0, $mysqljetzt );
//     $references = references_produkt( $id );
// 
//     if (!$edit_all) { 
//       ?>
//         <tr class='groupofrows_top'>
//       <? if( $produkt['zeitstart'] ) { ?>
//           <td valign="top"><input type="checkbox" name="bestelliste[]" value="<? echo $id; ?>"></td>
//       <?  } else { ?>
//           <td valign='top'> - </td>
//       <?  } ?>
//           <td valign="top"><b><? echo $produkt['name']; ?></b></td>
//           <td valign="top"><? echo $produkt['produktgruppen_name']; ?></td>
//       <? if( $produkt['zeitstart'] ) { ?>
//           <td valign="top"><? echo $produkt['notiz']; ?></td>
//           <td class='number'><?
//             printf(
//               "%d * (%s %s)"
//             , $produkt['gebindegroesse'], $produkt['kan_verteilmult'], $produkt['kan_verteileinheit']
//             );
//           ?></td>
//           <td class='mult'><?  printf( "%.2lf", $produkt['nettolieferpreis'] ); ?></td>
//           <td class='unit'><?  printf( "/ %s", $produkt['preiseinheit'] ); ?></td>
//           <td class='mult'><?  printf( "%.2lf", $produkt['endpreis'] ); ?></td>
//           <td class='unit'><?
//             printf( "/ %s %s"
//             , $produkt['kan_verteilmult'], $produkt['kan_verteileinheit']
//             );
//           ?></td>
//       <?  } else { ?>
//         <td colspan='6' style='text-align:center'>(kein aktueller Preiseintrag)</td>
//       <? } ?>
//           <td valign='top' style='white-space:nowrap;'>
//           <? if( $editable ) { ?>
//             <a class='png' href="javascript:f=window.open('index.php?window=editProdukt&produkt_id=<? echo $id; ?>','editProdukt','width=500,height=450,left=200,top=100');f.focus();"><img src='img/b_edit.png'
//              border='0' alt='Produktdaten ändern' title='Produktdaten ändern'/></a>
//             &nbsp;
//             <a class='png' href="javascript:neuesfenster('index.php?window=terraabgleich&produkt_id=<? echo $id; ?>','produktdetails');"><img src='img/b_browse.png'
//              border='0' alt='Details und Preise' title='Details und Preise'></a>
//             <? if( $references == 0 ) { ?>
//               &nbsp; <a class='png' href="javascript:deleteProdukt(<? echo $id; ?>);"><img src='img/b_drop.png' border='0'
//                       alt='Produkt löschen' title='Produkt löschen'/></a>
//             <? } ?>
//           <? } ?>
//           </td>
//         </tr>
//         <tr class='groupofrows_bottom'>
//           <td colspan='1'></td>
//           <td colspan='9'>
//             <?
//               if( $optionen & OPTION_PREISKONSISTENZTEST )
//                 produktpreise_konsistenztest( $id );
//               if( $optionen & OPTION_KATALOGABGLEICH )
//                 katalogabgleich( $id );
//             ?>
//           </td>
//         </tr>
//       <?
//     } else { //  alle bearbeiten ansicht ...
//       ?>
//         <tr>
//           <td valign="top"><input type="text" name="name_<? echo $row['id']; ?>" value="<? echo $row['name']; ?>"></td>
//           <td valign="top">
//             <input type='hidden' name='prodIds[]' value='<? echo $row['id']; ?>'>
//             <select name='prodgroup_<? echo $row['id']; ?>'>
//             <?
//               foreach( $prodgroup_id2name as $key => $value ) {
//                 if ($key == $row['produktgruppen_id']) $sel_str = "selected"; else $sel_str = "";
//                 echo "<option value='".$key."' ".$sel_str.">".$value."</option>\n";
//               }
//             ?>
//             </select>
//           </td>
//           <td valign="top">
//             <select name='lieferant_<? echo $row['id']; ?>'>
//             <?
//               foreach( $lieferanten_id2name as $key => $value ) {
//                 if ($key == $row['lieferanten_id']) $sel_str = "selected"; else $sel_str = "";
//                 echo "<option value='".$key."' ".$sel_str.">".$value."</option>\n";
//               }
//             ?>
//             </select>
//           </td>
//           <td valign="top"><input type="text" size="10" name="einheit_<? echo $row['id']; ?>" value="<? echo $row['einheit']; ?>"></td>
//           <td valign="top" align="middle"><input type="text" name="notiz_<? echo $row['id']; ?>" value="<? echo $row['notiz']; ?>"></td>
//           <?
//             $preis_row = sql_aktueller_produktpreis($row['id']);
//             if( $preis_row ) {
//               echo "<td valign='top' align='middle'><input type='text' size='10' name='gebindegroesse_".$row['id']."' value='".$preis_row['gebindegroesse']."'></td><td valign='top' align='middle'><input type='text' size='10' name='preis_".$row['id']."' value='".$preis_row['preis']."'></td><td valign='top' align='middle'><input type='text' size='10' name='bestellnummer_".$row['id']."' value='".$preis_row['bestellnummer']."'></td>";
//             } else {
//               echo "<td>(kein aktueller Preiseintrag)</td>";
//             }
//           ?>
//         </tr>
//       <?
//     }
//   }
// 
//   if( $editable ) {
//     ?> <tr> <?
//     if ($edit_all) {
//       ?>
//         <th colspan="9">
//         <input type="submit" value="Änderungen speichern"> &nbsp;| <a href="#" class="tabelle">nach oben</a>
//       <?
//     } else {
//       ?>
//         <th colspan="10">
//           <input type="button" value="neue Bestellung" onClick="window.open('','insertBestellung','width=400,height=450,left=200,top=100').focus() ; document.forms['newBestellungForm'].submit();">
//           &nbsp;| <a href="javascript:checkAll('newBestellungForm','',true)" class="tabelle">alle Produkte auswählen</a>
//           &nbsp;| <a href="#" class="tabelle">nach oben</a>
//       <?
//     }
//     ?> </th> </tr> <?
//   }
//   ?>
// 
//     </form>
//  </table>

