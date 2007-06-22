<h1>Bestellungen ansehen...</h1>

<?PHP

//Funktionen von Dominik für Verteilmengen
include("code/zuordnen.php");

//error_reporting(E_ALL); // alle Fehler anzeigen
   // Übergebene Variablen einlesen...
   if (isset($HTTP_GET_VARS['gruppen_id'])) $gruppen_id = $HTTP_GET_VARS['gruppen_id'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['gruppen_pwd'])) $gruppen_pwd = $HTTP_GET_VARS['gruppen_pwd'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['bestgr_pwd'])) $bestgr_pwd = $HTTP_GET_VARS['bestgr_pwd'];       // Passwort für den Bereich
    if (isset($HTTP_GET_VARS['bestellungs_id']) and $HTTP_GET_VARS['bestellungs_id'] != "" ) $bestell_id = $HTTP_GET_VARS['bestellungs_id'];
    if (isset($HTTP_GET_VARS['allGroupsArray'])) $allGroupsArray = $HTTP_GET_VARS['allGroupsArray'];

    $pwd_ok = false;
    $bestgroup_view = false;
    
    // Passwort prüfen, Bestellgrupendaten einlesen...
    
             //für die Ansicht: Bestellergebnisse ansehen (nur bestellte Produkte)
    if (isset($bestell_id) && isset($bestgr_pwd) && $bestgr_pwd == $real_bestellt_pwd) {
        $pwd_ok = true;
          $bestgroup_view = true;
          
                      
             if (isset($gruppen_id)) 
             {
		   $query = "SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id);
                   $result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden.. ($query)",mysql_error());
                    $bestellgruppen_row = mysql_fetch_array($result);
             }
       
    }          
                //für die Ansicht: Bestellergebnisse ansehen (für einzelne Bestellgruppen, alle Produkte)
       else if (isset($gruppen_id) && isset($gruppen_pwd) && $gruppen_id != "" && $gruppen_id != "bestgroup") 
    {
         $result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
          $bestellgruppen_row = mysql_fetch_array($result);
         
         $pwd_ok = true; // ($bestellgruppen_row['passwort'] == crypt($gruppen_pwd,35464));   
    }
    

    
       $gesamt_liste               = false;   // die gesamtbestellung anzeigen (oder nur ergebnisse einer bestellguppe)
         $ask_bestgroup_pwd   = false;  // wir gerade das Zugangspwd abgefragt?
         
         
    // wenn die Bestellgruppe die Ergebnisse sehen will...
    if (isset($gruppen_id) && $gruppen_id == "bestgroup") 
    {
      
          $pwd_ok         = true;   // sorgt dafür, dass man nicht in die Bestellgruppen-Passwortabfrage verzweigt
             
            //ggf. das pwd abfragen
            if (!isset($gruppen_pwd) || $gruppen_pwd != $real_bestellt_pwd) 
            {
             $ask_bestgroup_pwd = true;
               
             ?>
               
             <form action="index.php">
                <input type="hidden" name="area" value="bestellt">
                   <input type="hidden" name="gruppen_id" value="bestgroup">
                   <b>Bitte das Schinkepasswort eingeben:</b>
                   <input type="password" size="12" name="gruppen_pwd"><input type="submit" value="ok">      
               </form>
                   
               <?PHP
               exit(); //wir brechen das script ab
             }
             else 
             {
                $gesamt_liste = true;
             }
      }
         
       // Wenn kein Passwort für die Bestellgruppen-Admin angegeben wurde, dann abfragen...
         if ((!isset($bestgr_pwd) && !isset($gruppen_pwd)) || !$pwd_ok) {
   ?>
   
             <form action="index.php">
                <input type="hidden" name="area" value="bestellt">
                  <h3>Konsumentengruppen:</h3>
                  <table class="menu" style="width:450px">
                     <tr>
                         <th colspan="2">Bestellergebnisse ansehen <span style="font-size:0.8em">(alle Produkte)</span></th>
                      </tr>
                     <tr>
                        <td>Bestellgruppenname:</td>
                        <td>
                              <select name="gruppen_id">
                                  <option value="">[auswählen]</option>
<?PHP
                                 $result = mysql_query("SELECT id,name FROM bestellgruppen WHERE aktiv=1 ORDER BY (id%1000)") or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
                                  while ($row = mysql_fetch_array($result))
                                  { 
                                   echo "
                                   <option value='".$row['id']."'>".$row['name']."</option>\n";
                                   }
?>
                           </select>
                           </td>
                      </tr>
                      <tr>
                         <td>Bitte Gruppenpasswort angeben:</td>
                           <td><input type="password" size="12" name="gruppen_pwd"></td>
                      </tr>
                      <tr>
                         <td></td>
                         <td><input type="submit" value="einloggen"><input type="button" value="abbrechen" onClick="self.location.href='index.php'"></td>
                      </tr>
                  </table>
                  </form>
                  
                  
                <form action="index.php">
                <input type="hidden" name="area" value="bestellt">               
                  <h3>Sortiergruppe:</h3>
                  <table class="menu" style="width:450px">
                     <tr>
                         <th colspan="2">Bestellergebnisse <span style="font-size:0.8em">(nur bestellte Produkte, Druckversion)</span></th>
                      </tr>
                     <tr>
                        <td>Bestellung wählen:</td>
                        <td>
                           <select name="bestellungs_id">
                                  <option value="">[auswählen]</option>
<?PHP
                                 $result = mysql_query("SELECT *, gesamtbestellungen.id as gesamtbest_id FROM gesamtbestellungen WHERE NOW() > bestellende ORDER BY bestellende DESC") or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
                                  while ($row = mysql_fetch_array($result)) 
                                  {   
                          echo "<option value='".$row['gesamtbest_id']."'>".$row['name']."</option>\n";
                                   }
?>
                           </select>
                           </td>
                      </tr>
                      <tr>
                         <td>Bitte Schinkepasswort angeben:</td>
                           <td><input type="password" size="12" name="bestgr_pwd"></td>
                      </tr>                      
                      <tr>
                         <td></td>
                         <td><input type="submit" value="einloggen"><input type="button" value="abbrechen" onClick="self.location.href='index.php'"></td>
                      </tr>
                  </table>
                  </form>                  
                  
                  <h3>Bestellgruppe:</h3>
                  <form action="index.php">
                  <input type="hidden" name="area" value="bestellt">
                  <input type="hidden" name="gruppen_id" value="bestgroup">   
                   <table class="menu" style="width:450px">
                      <tr>
                            <th colspan="2">Gesamtbestellungen ansehen <span style="font-size:0.8em">(alle Produkte in der Übersicht)</span></th>
                         </tr>
                        <tr>
                         <td>Bitte Schinkepasswort angeben:</td>
                           <td><input type="password" size="12" name="gruppen_pwd"></td>
                      </tr>
                      <tr>
                         <td></td>
                         <td><input type="submit" value="einloggen"><input type="button" value="abbrechen" onClick="self.location.href='index.php'"></td>
                      </tr>
                 </table>       
                </form>         
  <?PHP                
         } 
         else if (!isset($allGroupsArray) && $bestgroup_view && $gruppen_id == "") 
         {
            verteilmengenZuweisen($bestell_id);
   ?>
      

                
                <form action="index.php">
                <input type="hidden" name="area" value="bestellt">         
                  <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
                  <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
                  <table class="menu">
                     <tr>
                         <th colspan="2">Bitte eine der beteiligten Bestellgruppen wählen:</th>
                      </tr>
                     <tr>
                        <td>Bestellgruppenname:</td>
                        <td>
                              <select name="gruppen_id">
                                  <option value="">[auswählen]</option>
                                  <?PHP
			   $sql = "SELECT bestellgruppen.id, bestellgruppen.name FROM bestellgruppen inner join gruppenbestellungen ON (gruppenbestellungen.bestellguppen_id = bestellgruppen.id) WHERE gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)."  ORDER  BY (bestellgruppen.id % 1000)  ASC ";
                           $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden.. ($sql) ",mysql_error());
                            while ($row = mysql_fetch_array($result)) echo "<option value='".$row['id']."'>".$row['name']."</option>\n";
                                    ?>
                           </select>
                           </td>
                      </tr>
                         <td></td>
                         <td><input type="submit" value="wählen"></td>
                      </tr>
                  </table>
                  </form>
                  
                <table class="menu">
                     <tr>
                         <th colspan="4">oder Druckversion erstellen:</th>
                      </tr>
                      <tr>
                         <td>
                           
<!--
Werte für die Druckversion "2x2 Matrix"  ..  
-->

                     <form action="bestellt_matrix_pdf.php" method="POST">         
                        <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
                        <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
                            <input type="submit" value="Matrix (PDF)">
                            </form>
                           </td>
                      <td>
                                 <?PHP   //Werte für die Druckversion sortiert nach GRUPPEN werden gesetzt .. 
                                 ?>
                        <form action="index.php" method="POST">
                      <input type="hidden" name="area" value="bestellt">         
                        <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
                        <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
            <?PHP
            $result = mysql_query("SELECT bestellgruppen.id, bestellgruppen.name 
                                                      FROM bestellgruppen, gruppenbestellungen 
                                                      WHERE gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)." 
                                                      AND gruppenbestellungen.bestellguppen_id = bestellgruppen.id 
                                                      ORDER  BY  (bestellgruppen.id % 1000)  ASC ") 
                                                      or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
                                                      
            while ($row = mysql_fetch_array($result)) 
               {   
                  echo "<input type='hidden' name='allGroupsArray[]' value='".$row['id']."'>\n";
               }
            ?>
                            <input type="submit" value="nach Gruppen sortiert"></td>
                            </form>
                            <td>
                     <form action="index.php" method="POST">
                      <input type="hidden" name="area" value="lieferschein">         
                        <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
                        <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
                            <input type="submit" value="Lieferschein"></form>
                           </td>
                            <td>
                     <form action="index.php" method="POST">
                      <input type="hidden" name="area" value="bestellt_produkte">         
                        <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
                        <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
                            <input type="submit" value="nach Produkten sortiert"></form>
                           </td>

                            </tr>
                        </table>
                <table class="menu">
                     <tr>
                         <th
                         colspan="1">oder zum Basar:</th>
                      </tr>
                      <tr>
                         <td>
                           

                     <form action="basar.php" method="GET">         
                        <input type="hidden" name="bestgr_pwd" value="<?PHP echo $bestgr_pwd; ?>">
                        <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
                            <input type="submit" value="Basar">
                            </form>
                           </td>
                            </tr>
                        </table>
               <table class="menu">
                     <tr>
                         <th
                         colspan="1">oder zur Auswahl:</th>
                      </tr>
                            
                            <tr>
                <td><input type="button" value="Auswahl" class="button" onClick="self.location.href='index.php?area=bestellt'"></td>
                  
                </tr>
                            
                        </table>

                        
   <?PHP
         } else   if (!$ask_bestgroup_pwd) {
         
            // Aktuelle Bestellung ermitteln...
             if (isset($HTTP_GET_VARS['bestellungs_id'])) {
                $result = mysql_query("SELECT * , gesamtbestellungen.id as gesamtbest_id FROM gesamtbestellungen WHERE id=".mysql_escape_string($bestell_id)) or error(__LINE__,__FILE__,"Konnte Gesamtbestellungen nich aus DB laden..",mysql_error());
             } else {
                if ($gesamt_liste)
                     $result = mysql_query("SELECT *, gesamtbestellungen.id as gesamtbest_id FROM gesamtbestellungen WHERE NOW() > bestellende ORDER BY bestellende DESC") or error(__LINE__,__FILE__,"Konnte Gesamtbestellungen nich aus DB laden..",mysql_error());
                  else
                   $result = mysql_query("SELECT *, gesamtbestellungen.id as gesamtbest_id FROM gesamtbestellungen, gruppenbestellungen WHERE NOW() > bestellende AND gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id AND gruppenbestellungen.bestellguppen_id= ".$gruppen_id." ORDER BY bestellende DESC") or error(__LINE__,__FILE__,"Konnte Gesamtbestellungen nich aus DB laden..",mysql_error());
             }
            
         if (mysql_num_rows($result) > 1 || mysql_num_rows($result) == 0) {
      ?>
      
            <b>Bitte eine abgeschlossene Bestellung wählen:</b><br>
                <br>
                   
                      <table class="liste">
                      <tr>
                         <th>Startzeit</th>
                           <th>Ende</th>
                           <th>Name</th>
                           <th>optionen</th>
                           <th>FAX-Ansicht</th>
                      </tr>

      <?PHP
                      while ($row = mysql_fetch_array($result)) {
                           
                            echo "
                        <tr>
                           <td>".$row['bestellstart']."</td>
                           <td>".$row['bestellende']."</td>
                           <td>".$row['name']."</td>
                           <td>
                              <form action=\"index.php\" name=\"bestellung_form\">
                                 <input type=\"hidden\" name=\"area\" value=\"bestellt\">
                                  <input type=\"hidden\" name=\"gruppen_id\" value=\"".$gruppen_id."\">
                                  <input type=\"hidden\" name=\"gruppen_pwd\" value=\"".$gruppen_pwd."\">
                                  <input type=\"hidden\" name=\"bestellungs_id\" value=\"".$gruppen_pwd."\">
                                 <input type='button' value='ansehen' onClick=\"document.forms['bestellung_form'].bestellungs_id.value='".$row['gesamtbest_id']."'; document.forms['bestellung_form'].submit();\">
                              </form>
                           </td>";
			   if(TRUE){ //TODO richtige Bedinung finden, wann FAX-Ansicht ausgeblendet werden soll
				   echo"
				   <td>
				      <form action=\"bestellt_faxansicht.php\" method=\"POST\">         
					 <input type=\"hidden\" name=\"bestgr_pwd\" value=\"".$bestgr_pwd."\">
					 <input type=\"hidden\" name=\"bestellungs_id\" value=\"".$row['gesamtbest_id']."\">
					  <input type=\"submit\" value=\"PDF\">
				       </form>
				   </td>";
			   } else {
			   	echo "<td></td>";
			   }
                           echo "</tr>";
                            
                        }
      ?>
      
              <tr>
                     <td colspan="4"><input type="button" value="beenden" onClick="self.location.href='index.php'"></td>
                  </tr>
                  </form>
               </table>
      
      <?PHP
             } else {
            
              
             
                $row_gesamtbestellung = mysql_fetch_array($result);
                $bestell_id = $row_gesamtbestellung['id'];
                  $gesamt_preis = 0;
                  $max_gesamt_preis = 0;
                  
                   $bestell_id = $row_gesamtbestellung['gesamtbest_id'];
                  
                  
                  // Lieferantenname zu den Lieferanten-Nummern auslesen
                  $result = mysql_query("SELECT name,id FROM lieferanten") or error(__LINE__,__FILE__,"Konnte Lieferantennamen nich aus DB laden..",mysql_error());
                  while ($row = mysql_fetch_array($result)) $lieferanten_id2name[$row['id']] = $row['name'];
                  
                  // Produktgruppennamen zu den Produktgruppen-Nummern auslesen
                  $result = mysql_query("SELECT name,id FROM produktgruppen") or error(__LINE__,__FILE__,"Konnte Produktgruppen nich aus DB laden..",mysql_error());
                  while ($row = mysql_fetch_array($result)) $produktgruppen_id2name[$row['id']] = $row['name'];
                  
             
             
      ?>
      
      
            <table class="info">
               <tr>
                   <th> Bestellung: </th>
                     <td><?PHP echo $row_gesamtbestellung['name']; ?></td>
                </tr>
               <tr>
                   <th> Bestellbeginn: </th>
                     <td><?PHP echo $row_gesamtbestellung['bestellstart']; ?></td>
                </tr>
               <tr>
                   <th> Bestellende: </th>
                     <td><?PHP echo $row_gesamtbestellung['bestellende']; ?></td>
                </tr>                
            </table>
      <br>
	 Achtung: Preise und Mengen stimmen nicht. Siehe andere Tabellen.
         <table style="width: 700px; border:none">
            <tr class="legende">
               <td colspan="7">Konsumentengruppe</td>
            </tr>
            <tr class="legende">
               <td style="width: 300px;">Bezeichnung</td>
               <td>bestellt</td>
               <td>einzelpreis</td>
               <td>gebinde</td>
               <td>preis</td>
               <td>geliefert</td>
               <td>abgeholt</td>
            </tr>
         </table>
      <br />
      
<?PHP

           $mutli_group = true;
                $counter = -1;
                
                while ($mutli_group) {
                
                   $counter++;
                     $gesamt_preis = 0;
                
                      if (isset($allGroupsArray)) {      // wenn der druckversionGRUPPENübersichtbutton gedrückt wurde, 
                         if ($counter < count($allGroupsArray)) { 
                              $gruppen_id = $allGroupsArray[$counter];
                              $result44 = mysql_query("SELECT * 
                                                                           FROM bestellgruppen 
                                                                           WHERE id=".mysql_escape_string($gruppen_id)) 
                                                                           or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
                               $bestellgruppen_row = mysql_fetch_array($result44);                     
                          } else break;
                     } 
                     else if (isset($allProdArray)) {      // wenn der druckversionPRODUKTEübersichtbutton gedrückt wurde 
                         if ($counter < count($allProdArray)) { 
                              $gruppen_id = $allProdArray[$counter];
                              $result44 = mysql_query("SELECT * 
                                                                           FROM bestellgruppen 
                                                                           WHERE id=".mysql_escape_string($gruppen_id)) 
                                                                           or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
                               $bestellgruppen_row = mysql_fetch_array($result44);                     
                          } else break;
                     } 
                     else $mutli_group = false;

           if (!$mutli_group) {
?>
       
            <table>
               <tr>
                   <th colspan="7"><h3><?PHP
                      if(isset($bestellgruppen_row)){
                     echo "Gruppenbestellung - ".$bestellgruppen_row['name']." Achtung - Preisberechnungen zur Zeit falsch. Es fehlen der Basar und die Änderungen der Verteilgruppe. Siehe <a href=index.php?area=meinkonto>Kontoauszüge</a> für korrekten Preis"; 
                  } else {
                     echo "Bestellung Übersicht";
                  }
                   ?></h3></th>
                </tr>
           <tr>
                   <th>Bezeichnung</th>
                   <th>Produktgruppe</th>
                   <th>Lieferant</th>
                   <th>Gebinde und Preis/Einheit</th>
                   <th class="menge">Menge</th>
                   <th class="toleranz">Toleranz</th>
                   <th>Kosten</th>
               </tr>         
<?PHP
   } else {
?>

            <table style="width: 700px;">
               <tr>
                   <th colspan="7"><span style='font-size:1.2em; margin:5px;'>&nbsp;&nbsp;&nbsp;<?PHP echo $bestellgruppen_row['name']; ?></span></th>
                </tr>

      <?PHP
   }   

                        
             
                 // Produkte auslesen & Tabelle erstellen...
              $result = mysql_query("SELECT * FROM produkte,bestellvorschlaege WHERE produkte.id=bestellvorschlaege.produkt_id AND bestellvorschlaege.gesamtbestellung_id='".mysql_escape_string($bestell_id)."' ORDER BY produktgruppen_id, name;") or error(__LINE__,__FILE__,"Konnte Produktdaten nich aus DB laden..",mysql_error());

                   $produkt_counter = 0;
                   $bestellungDurchfuehren = true;   
                   
                   while ($produkt_row = mysql_fetch_array($result)) {

                     unset($gebindegroessen);
                     unset($gebindepreis);
                   
                      // Gebindegroessen und Preise des Produktes auslesen...
                      $result2 = mysql_query("SELECT gebindegroesse,preis FROM produktpreise WHERE zeitstart <= '".mysql_escape_string($row_gesamtbestellung['bestellstart'])."' AND (ISNULL(zeitende) OR zeitende >= '".mysql_escape_string($row_gesamtbestellung['bestellende'])."') AND produkt_id=".mysql_escape_string($produkt_row['id'])." ORDER BY gebindegroesse DESC;") or error(__LINE__,__FILE__,"Konnte Gebindegroessen nich aus DB laden..",mysql_error());
		       if(mysql_num_rows($result2)==0) {
		       		$query = "SELECT gebindegroesse, preis FROM produktpreise WHERE id IN (SELECT produktpreise_id FROM bestellvorschlaege WHERE produkt_id = ".mysql_escape_string($produkt_row['id'])."  AND gesamtbestellung_id = ".mysql_escape_string($row_gesamtbestellung['id'])." )";
				$result2 = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Gebindegroessen nich aus DB laden.. ($query)",mysql_error());
		       }
                       $i = 0;
                       while ($row = mysql_fetch_array($result2)) {
                           $gebindegroessen[$i]=$row['gebindegroesse'];
                            $gebindepreis[$i]=$row['preis'];
                            $i++;

                       }          

	                if($i == 0) error(__FILE__,__LINE__,"Kein Preis für Produkt ".$produkt_row['produkt_name']." (".$produkt_row['produkt_id'].") gefunden! Überprüfe gültigkeit");

                      // Bestellmengenzähler setzen
                        $gesamtBestellmengeFest[$produkt_row['id']]                                   = 0;
                        $gesamtBestellmengeToleranz[$produkt_row['id']]                             = 0;                        
                        $gruppenBestellmengeFest[$produkt_row['id']]                                  = 0;
                        $gruppenBestellmengeToleranz[$produkt_row['id']]                            = 0;                                           
                        $gruppenBestellmengeFestInBerstellung[$produkt_row['id']]              = 0;
                        $gruppenBestellmengeToleranzInBerstellung[$produkt_row['id']]        = 0;
                        
                        unset($gruppenBestellintervallUntereGrenze);
                        unset($gruppenBestellintervallObereGrenze);
                        unset($bestellintervallId);
                        
                        
                        // Hier werden die aktuellen festen Bestellmengen ausgelesen...
                      $result2 = mysql_query("SELECT  *, gruppenbestellungen.id as gruppenbest_id, bestellzuordnung.id as bestellzuordnung_id FROM gruppenbestellungen, bestellzuordnung WHERE bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id AND gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)." AND bestellzuordnung.produkt_id = ".mysql_escape_string($produkt_row['id'])." AND bestellzuordnung.art=0 ORDER BY bestellzuordnung.zeitpunkt;") or error(__LINE__,__FILE__,"Konnte Bestellmengen nich aus DB laden..",mysql_error());
                        $intervallgrenzen_counter = 0;                        
                        while ($einzelbestellung_row = mysql_fetch_array($result2)) {
                            if ($einzelbestellung_row['bestellguppen_id'] == $gruppen_id) {
                               $gruppenbestellung_id = $einzelbestellung_row['gruppenbest_id'];
                            
                               $ug = $gruppenBestellintervallUntereGrenze[$produkt_row['id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['id']] + 1;
                                 $og = $gruppenBestellintervallObereGrenze[$produkt_row['id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['id']] + $einzelbestellung_row['menge'];
                                 $bestellintervallId[$produkt_row['id']][$intervallgrenzen_counter] = $einzelbestellung_row['bestellzuordnung_id'];
                                 
                                 
                                 $intervallgrenzen_counter++;
                               $gruppenBestellmengeFest[$produkt_row['id']] += $einzelbestellung_row['menge'];
                            }
                            $gesamtBestellmengeFest[$produkt_row['id']] += $einzelbestellung_row['menge'];
                        }
                        
                        $gesamteBestellmengeAnfang = $gesamtBestellmengeFest[$produkt_row['id']];
                        

                unset($toleranzenNachGruppen);
                        // Hier werden die aktuellen toleranz Bestellmengen ausgelesen...
                      $result2 = mysql_query("SELECT *, bestellzuordnung.id as bestellzuordnung_id FROM gruppenbestellungen, bestellzuordnung WHERE bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id AND gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)." AND bestellzuordnung.produkt_id = ".mysql_escape_string($produkt_row['id'])." AND bestellzuordnung.art=1 ORDER BY bestellzuordnung.zeitpunkt;") or error(__LINE__,__FILE__,"Konnte Bestellmengen nich aus DB laden..",mysql_error());
                        $toleranzBestellungId = -1;
                        while ($einzelbestellung_row = mysql_fetch_array($result2)) {                  
                            if ($einzelbestellung_row['bestellguppen_id'] == $gruppen_id) {
                               $gruppenBestellmengeToleranz[$produkt_row['id']] += $einzelbestellung_row['menge'];
                                 $toleranzBestellungId =  $einzelbestellung_row['bestellzuordnung_id'];
                            }
                            $gesamtBestellmengeToleranz[$produkt_row['id']] += $einzelbestellung_row['menge'];
                            
                            // für jede Gruppe getrennt die Toleranzmengen ablegen
                            $bestellgruppen_id = $einzelbestellung_row['bestellguppen_id'];
                            if (!isset($toleranzenNachGruppen[$bestellgruppen_id])) $toleranzenNachGruppen[$bestellgruppen_id] = 0;
                            $toleranzenNachGruppen[$bestellgruppen_id] += $einzelbestellung_row['menge'];
                            
                        }
                        
                        if (isset($toleranzenNachGruppen)) ksort($toleranzenNachGruppen);
                        
                        
                     // jetzt die Gebindeaufteilung berechnen
                     unset($gruppenMengeInGebinde);
                     unset($festeGebindeaufteilung);
                     
                     $rest_menge = $gesamtBestellmengeFest[$produkt_row['id']]; 
                     $gesamtMengeBestellt = 0;
                     $gruppeGesamtMengeInGebinden = 0;
                     for ($i=0; $i < count($gebindegroessen); $i++) {
                        $festeGebindeaufteilung[$i] = floor($rest_menge / $gebindegroessen[$i]);
                         $rest_menge = $rest_menge % $gebindegroessen[$i];
                         
                         // berechne: wieviel  hat die aktuelle Gruppe in diesem Gebinde
                         $gebindeAnfang = $gesamtMengeBestellt + 1;
                         $gesamtMengeBestellt += $festeGebindeaufteilung[$i] * $gebindegroessen[$i];
                         
                         $gruppenMengeInGebinde[$i]       = 0;
                         
                         
                         if ($festeGebindeaufteilung[$i] > 0 && isset($gruppenBestellintervallUntereGrenze[$produkt_row['id']])) {
                               
                               
                               for ($j=0; $j < count($gruppenBestellintervallUntereGrenze[$produkt_row['id']]); $j++) {
                               
                                  $ug = $gruppenBestellintervallUntereGrenze[$produkt_row['id']][$j];
                                    $og = $gruppenBestellintervallObereGrenze[$produkt_row['id']][$j];
                                    $gebindeEnde = $gesamtMengeBestellt;

                                    if ($ug >= $gebindeAnfang && $ug <= $gebindeEnde) {  // untere Grenze des Bestellintervalls im aktuellen Gebinde...
                                        if ($og >= $gebindeAnfang && $og <= $gebindeEnde)   { // und die obere Grenze auch dann...
                                             $gruppenMengeInGebinde[$i] += 1 + $og - $ug;
                                        }
                                        else    // und die obere Grenze nicht, dann ...
                                        {
                                             $gruppenMengeInGebinde[$i] += 1 + $gebindeEnde - $ug;    // alles bis zum Intervallende
                                        }
                                    }
                                    else if ($og >= $gebindeAnfang && $og <= $gebindeEnde) {  // die obere Grenze des Bestellintervalls im aktuellen Gebinde, und die untere nicht, dann...
                                        $gruppenMengeInGebinde[$i] += 1 + $og - $gebindeAnfang;    // alles ab Intervallanfang bis obere Grenze
                                    }
                                    else if ($ug < $gebindeAnfang && $og > $gebindeEnde) { //die untere Grenze des Bestellintervalls unterhalb und die obere oberhalb des aktuellen Gebindes, dann..
                                       $gruppenMengeInGebinde[$i] += 1 + $gebindeEnde - $gebindeAnfang;    // das gesamte Gebinde
                                    }
                               }
                          }

                         $gruppeGesamtMengeInGebinden += $gruppenMengeInGebinde[$i];
                     }
                     
                     // versuche offenes Gebinde mit Toleranzmengen zu füllen                     
                     $gruppenToleranzInGebinde     = 0;
                     $toleranzGebNr                      = -1;
               
                     if ($rest_menge != 0) {
                        $fuellmenge = $gebindegroessen[count($gebindegroessen)-1] - $rest_menge;
                         $gruppen_anzahl = count($toleranzenNachGruppen);
                         if ($fuellmenge <= $gesamtBestellmengeToleranz[$produkt_row['id']]) {
                             //echo "<p>toleranzenNachGruppen: ".$toleranzenNachGruppen."</p>";
                           
                       reset($toleranzenNachGruppen);
                              
                                 do {
				     if($toleranzenNachGruppen==NULL){
				     	echo "<p> Error while processing Product ".$produkt_row['id']." ".$produkt_row['name']." </p>";
					break;
				     }
                                     while (!(list($key, $value) = each($toleranzenNachGruppen))){ 
                                        reset($toleranzenNachGruppen);   // neue Wete auslesen und ggf. wieder am Anfang des Arrays starten

                                     }

                                       if ($value > 0) { 
                                       
                                          $toleranzenNachGruppen[$key] --;
                                           $fuellmenge--;
                                           if ($key == $gruppen_id) $gruppenToleranzInGebinde++;
                                       }
                                       
                                       
                                 } while($fuellmenge > 0);
                                  
                                  // das "toleranzgefüllte" Gebinde anzeigen
                                  $toleranzGebNr = count($festeGebindeaufteilung)-1;
                                  
                                  $festeGebindeaufteilung[count($festeGebindeaufteilung)-1]++;
                                  $gruppenMengeInGebinde[$toleranzGebNr] += $gruppenBestellmengeFest[$produkt_row['id']]  - $gruppeGesamtMengeInGebinden;
                                  $gruppenMengeInGebinde[$toleranzGebNr] += $gruppenToleranzInGebinde;
                                  $gruppeGesamtMengeInGebinden = $gruppenBestellmengeFest[$produkt_row['id']];
                                  $toleranzFuellung = count($gebindegroessen) -1;
                                  
                                  // Gebindeaufteillung an Toleranzfüllung anpassen...
                                  $anzInAktGeb = $festeGebindeaufteilung[$toleranzGebNr] * $gebindegroessen[$toleranzGebNr];                                  

                                  for ($i = count($gebindegroessen)-2; $i >= 0 ; $i--)
                                     if (($anzInAktGeb % $gebindegroessen[$i]) == 0) {
                                       
                                       
                                          $gruppenMengeInGebinde[$i] += $gruppenMengeInGebinde[$toleranzGebNr];
                                           $gruppenMengeInGebinde[$toleranzGebNr] = 0;
                                           
                                          $festeGebindeaufteilung[$i] += floor($anzInAktGeb / $gebindegroessen[$i]);
                                          $festeGebindeaufteilung[$toleranzGebNr] = 0;
                                           $toleranzGebNr = $i;
                                           $anzInAktGeb = $festeGebindeaufteilung[$toleranzGebNr] * $gebindegroessen[$toleranzGebNr];                                           

                                       }
                                  
                         }
                     }

                     $gruppenToleranzNichtInGebinde = $gruppenBestellmengeToleranz[$produkt_row['id']] - $gruppenToleranzInGebinde;
                     $gruppeGesamtMengeNichtInGebinden = $gruppenBestellmengeFest[$produkt_row['id']]  - $gruppeGesamtMengeInGebinden;

                     // Preis berechnen
                     $bestell_preis = 0;
                     $max_prod_preis     = 0;
                     for ($i = 0; $i < count($gebindegroessen); $i++) {
                        if ($gebindepreis[$i] > $max_prod_preis) $max_prod_preis = $gebindepreis[$i];
                         
                        if (!$gesamt_liste){
			    $verteilmenge = sql_verteilmengen($bestell_id,$produkt_row['id'],$gruppen_id);
                            //$bestell_preis += $gruppenMengeInGebinde[$i] * $gebindepreis[$i];
                            $bestell_preis += $verteilmenge * $gebindepreis[$i];
                         }else
                            $bestell_preis += $festeGebindeaufteilung[$i] * $gebindepreis[$i] * $gebindegroessen[$i]; // benni: variable $gebindegroessen[$i] hinzugefügt
                     }
                     //$max_preis = $bestell_preis - ($gruppenToleranzInGebinde * $gebindepreis[$toleranzGebNr]);
                     //$max_preis += $max_prod_preis * ($gruppeGesamtMengeNichtInGebinden + $gruppenToleranzInGebinde + $gruppenToleranzNichtInGebinde);
                     //$bestell_preis += $max_prod_preis * ($gruppeGesamtMengeNichtInGebinden + $gruppenToleranzNichtInGebinde);
                     
                     $gesamt_preis += $bestell_preis;
                     //$max_gesamt_preis += $max_preis;
                     
                     $anzGeb = 0;
                     for ($i=0; $i < count($festeGebindeaufteilung); $i++) $anzGeb += $festeGebindeaufteilung[$i];
                     
                     if ((!$gesamt_liste && !$bestgroup_view) || ($gesamt_liste && $anzGeb > 0) || ($bestgroup_view && ($gruppeGesamtMengeInGebinden + $gruppenToleranzInGebinde > 0)) ) {
                   
                   if ($mutli_group) {
      ?>
            <tr>
               <td valign="top"><b><?PHP echo $produkt_row['name'];  ?></b></td>
                <!-- <td valign="top"><?PHP echo $lieferanten_id2name[$produkt_row['lieferanten_id']]; ?></td> -->
                <td valign="top"><?PHP echo "<b>".($gruppeGesamtMengeInGebinden + $gruppenToleranzInGebinde)."</b> (".$gruppenToleranzInGebinde.")"; ?></td>
                <td>
                   <table class="inner">
                   <?PHP
                                   // Preise zum aktuelle Produkt auslesen..
                                    $prod_ges_preis = 0;
                                   $result2 = mysql_query("SELECT  id, gebindegroesse, bestellnummer, preis FROM  produktpreise WHERE zeitstart <= '".mysql_escape_string($row_gesamtbestellung['bestellstart'])."' AND (ISNULL(zeitende) OR zeitende >= '".mysql_escape_string($row_gesamtbestellung['bestellende'])."') AND produkt_id=".mysql_escape_string($produkt_row['id'])." ORDER BY gebindegroesse;") or error(__LINE__,__FILE__,"Konnte Produktpreise nich aus DB laden..",mysql_error());                                    
                                    for ($i = count($gebindegroessen)-1; $i >= 0; $i--) {
                                       $preise_row = mysql_fetch_array($result2);
                                        
                                        echo
                                        "<tr><td>".$gruppenMengeInGebinde[$i]."</td><td>x</td><td>".$preise_row['preis']."</td></tr>\n";
                                        $prod_ges_preis += $gruppenMengeInGebinde[$i] * $preise_row['preis'];
                                        
                                    }
                  ?>
                        </table>
                </td>
                <td>
                  <table class="inner">
                   <?PHP
                                   $result2 = mysql_query("SELECT  id, gebindegroesse, bestellnummer, preis FROM  produktpreise WHERE zeitstart <= '".mysql_escape_string($row_gesamtbestellung['bestellstart'])."' AND (ISNULL(zeitende) OR zeitende >= '".mysql_escape_string($row_gesamtbestellung['bestellende'])."') AND produkt_id=".mysql_escape_string($produkt_row['id'])." ORDER BY gebindegroesse;") or error(__LINE__,__FILE__,"Konnte Produktpreise nich aus DB laden..",mysql_error());                                    
                                    for ($i = count($gebindegroessen)-1; $i >= 0; $i--) {
                                       $preise_row = mysql_fetch_array($result2);
                                        
                                        echo "<tr><td>".$preise_row['gebindegroesse']."</td><td>x</td><td>".$produkt_row['einheit']."</td></tr>\n";
                                        
                                    }
                  ?>
                     </table>
                </td>
                <td><?PHP echo $prod_ges_preis; ?></td>
                <td width="50px"></td>
                <td width="50px"><?php echo sql_verteilmengen($bestell_id,$produkt_row['id'],$gruppen_id)?></td>
            </tr>
      
     <?PHP
                  } else {
      ?>
      
               <tr>
                   <td valign="top"><b><?PHP if ($gesamt_liste) echo "<a href=\"javascript: window.open('windows/showBestelltProd.php?bestellt_pwd=".$gruppen_pwd."&produkt_id=".$produkt_row['id']."&bestellung_id=".$bestell_id."','insertProdukt','width=600,height=500,left=50,top=50').focus()\" style='text-decoration:none;'>"; echo $produkt_row['name']; if ($gesamt_liste) echo "</a>"; ?></b>
                   
                   </td>
                   <td valign="top"><?PHP echo $produktgruppen_id2name[$produkt_row['produktgruppen_id']]; ?></td>
                   <td valign="top"><?PHP echo $lieferanten_id2name[$produkt_row['lieferanten_id']]; ?></td>
                   <td valign="top">
                      <table border="0" width="100%"  class="inner">
                               <?PHP 
                              
                                   // Preise zum aktuelle Produkt auslesen..
                                   $result2 = mysql_query("SELECT  id, gebindegroesse, bestellnummer, preis FROM  produktpreise WHERE zeitstart <= '".mysql_escape_string($row_gesamtbestellung['bestellstart'])."' AND (ISNULL(zeitende) OR zeitende >= '".mysql_escape_string($row_gesamtbestellung['bestellende'])."') AND produkt_id=".mysql_escape_string($produkt_row['id'])." ORDER BY gebindegroesse;") or error(__LINE__,__FILE__,"Konnte Produktpreise nich aus DB laden..",mysql_error());                                    
                                    for ($i = count($gebindegroessen)-1; $i >= 0; $i--) {
                                       $preise_row = mysql_fetch_array($result2);
                                        
                                        if ($toleranzGebNr == $i) { 
                                           $toleranz_color_str = "style='color:#999999'";
                                        } else {
                                           $toleranz_color_str="";
                                        }   
                                        
                                        if ($gesamt_liste)
                                           echo "<tr><td width='50px'><b><span id='anz_prod(".$produkt_row['id'].")geb(".$i.")' ".$toleranz_color_str." >".$festeGebindeaufteilung[$i]."</span> - </b></td><td>".$preise_row['gebindegroesse']." * ".$produkt_row['einheit']."</td><td align='right'></td><td align='right'>".sprintf("%.02f",$preise_row['preis'])."</td></tr>";
                                        else
                                           echo "<tr><td width='50px'><b><span id='anz_prod(".$produkt_row['id'].")geb(".$i.")' ".$toleranz_color_str." >".$festeGebindeaufteilung[$i]."</span> - </b></td><td>".$preise_row['gebindegroesse']." * ".$produkt_row['einheit']."</td><td align='right'></td><td align='right'>".sprintf("%.02f",$preise_row['preis'])."</td></tr>";
                             echo "<tr><td><span style='font-size:0.8em'>best_nr:".$preise_row['bestellnummer']."</span></td></tr>";
                                    }
                                  
                               ?>
                      </table>
                   </td>
                   <td valign="top">
                   
                      <table border="0" width="100%"  class="inner">
                           <tr>
                               <?PHP
                                    if ($gesamt_liste) {
                                 ?>
                                       <td align="right" ><b><span id="menge_gesamt_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gesamtBestellmengeFest[$produkt_row['id']]; ?></span></b></td>
                                 <?PHP 
                                   } else {
                                 ?>
                                    <td align="left" ><span style="color:#00FF00"><b><span id="menge_geb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppeGesamtMengeInGebinden; ?></span></span> +  <span style="color:#FF0000"><spam id="menge_nichtgeb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppeGesamtMengeNichtInGebinden; ?></span></span></b> / <span id="menge_gesamt_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gesamtBestellmengeFest[$produkt_row['id']]; ?></span></td>
                                 <?PHP
                                   }
                                 ?>
                           </tr>
                        </table>                   

                   </td>
                   <td valign="top">
                   
                      <table border="0" width="100%" class="inner">
                           <tr>
                               <?PHP
                                    if ($gesamt_liste) {
                                 ?>
                                       <td align="right" ><b><?PHP echo $gesamtBestellmengeToleranz[$produkt_row['id']]; ?></b></td>
                                 <?PHP 
                                   } else {
                                 ?>
                                    <td align="left"><span color="#00FF00"><b><span id="toleranz_geb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppenToleranzInGebinde; ?></span></span> +  <span style="color#FF0000"><span id="toleranz_nichtgeb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppenToleranzNichtInGebinde; ?></span></span></b> / <span id="toleranz_gesamt_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gesamtBestellmengeToleranz[$produkt_row['id']]; ?></span></td>
                                 <?PHP
                                   }
                                 ?>
                           </tr>
                      </table>                   

                   </td>
             <td valign="bottom" align="right" id="kosten_colum_<?PHP echo $produkt_counter; ?>"><b><span id="kosten_<?PHP echo $produkt_row['id']; ?>"><?PHP echo sprintf("%.02f",$bestell_preis); ?></span></b>
                   </td>
               </tr>
      
      <?PHP
              }
            }
             
             $produkt_counter++;
                   }
      ?>
          <tr>
               <td colspan="6" align="right"><b>Gesamtpreis:</b></td>
                <td align="right" id="td_gesamt_preis">
                   <b><span id="gesamt_preis"><?PHP echo sprintf("%.02f",$gesamt_preis); ?></span></b>
                </td>
            </tr>   <tr>   <td colspan="6" align="right"><b>Alle Preise inclusive Pfand und Mehrwertsteuer!</b></td></tr>
      <?PHP 
         if (!$mutli_group) {
      ?>
         <tr>
               <td colspan="7">
                   <input type="button" class="bigbutton" value="zur Auswahl" onClick="self.location.href='index.php?area=bestellt&gruppen_id=<?PHP echo $gruppen_id; ?>&gruppen_pwd=<?PHP echo $gruppen_pwd; ?>'">
                      <input type="button" class="bigbutton" value="beenden" onClick="self.location.href='index.php'">
               </td>
            </tr>
      <?PHP
          }
      ?>
            </table>
          </form>
          <br />   

            
      <?PHP
            
      
             }
             
         }
         
      }
         
  ?>
   
