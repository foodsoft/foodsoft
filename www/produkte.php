<h1>Produktdatenbank ....</h1>

<?PHP
  require_once("$foodsoftpath/code/zuordnen.php");
  require_once("$foodsoftpath/code/login.php");
  $pwd_ok = $angemeldet;

/* wie das skript funktioniert:

   1. variablen einlesen, passwort prüfen
   2. über die action variable wird geprüft, welcher button gedrückt wurde
      2.1. action = delete -> produkt wird gelöscht
      2.2. action = edit_all ->  die alle bearbeiten seite wird angezeigt
      2.3. action = change_all -> die preise werden ggf. aktualisiert .. 

*/
    
          // ggf. Aktionen durchführen (z.B. Produkt löschen... oder neue preise einfügen)
  $edit_all = false;
  get_http_var('action');
  // loeschen ist keine gute idee (produkte werden fuer berechnung der kontostaende gebraucht!)
  // if ($action == "delete") 
  //  {
  //    mysql_query("DELETE FROM produkte WHERE id=".mysql_escape_string($HTTP_GET_VARS['produkt_id'])) or error(__LINE__,__FILE__,"Konnte Produkt nicht löschen.",mysql_error());
  //    mysql_query("DELETE FROM kategoriezuordnung WHERE produkt_id=".mysql_escape_string($HTTP_GET_VARS['produkt_id'])) or error(__LINE__,__FILE__,"Konnte Produkt-Kategorienzuordnung nicht löschen.",mysql_error());
  //  }
                      
  if ($action == "edit_all") { 
         $edit_all = true;
  }
                      
                      
                      // jetzt wurde er änderung speichern button gedrückt und alle produkte aktualisiert
                      else if ($action == "change_all" && isset($HTTP_GET_VARS['prodIds'])) 
                      {

                            $prodIds = $HTTP_GET_VARS['prodIds'];
                           
                              for ($i=0; $i < count($prodIds); $i++) 
                              {
                                    //produkte werden aktualisiert ...
                                 $sql = "UPDATE produkte 
                                                SET name='".mysql_escape_string($HTTP_GET_VARS['name_'.$prodIds[$i]])."', 
                                                lieferanten_id='".mysql_escape_string($HTTP_GET_VARS['lieferant_'.$prodIds[$i]])."', 
                                                produktgruppen_id='".mysql_escape_string($HTTP_GET_VARS['prodgroup_'.$prodIds[$i]])."', 
                                                einheit='".mysql_escape_string($HTTP_GET_VARS['einheit_'.$prodIds[$i]])."', 
                                                notiz='".mysql_escape_string($HTTP_GET_VARS['notiz_'.$prodIds[$i]])."' 
                                                WHERE id=".mysql_escape_string($prodIds[$i])."";
                                                
                                 mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produkt nicht ändern.",mysql_error());
                                  
                                     // ggf. die Preise updaten, welche dann neu in die db geschrieben werden
                                     
                                     //den eingegebenen preis auslesen und das komma entfernen !!
                                     $preis  = str_replace(",",".",$HTTP_GET_VARS['preis_'.$prodIds[$i]]);
                                     
                                    //aber erst wird geprüft, ob es aktuelle preise für das produkt gibt
              			    $result2 =  sql_produktpreise($prodIds[$i],0, "NOW()","NOW()");
                                    
                                       if (mysql_num_rows($result2) == 1) // wenn eine zeile mit gültigem preis existiert ...
                                       {         
                                             $preis_row = mysql_fetch_array($result2);
                                             
                                                if ($preis_row['preis'] != $HTTP_GET_VARS['preis_'.$prodIds[$i]] || $preis_row['bestellnummer'] != $HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]] || $preis_row['gebindegroesse'] != $HTTP_GET_VARS['gebindegroesse_'.$prodIds[$i]]) 
                                                {
                                                            //wenn der neue preis (oder bestellnummer oder gebindegröße)nicht mit dem alten übereinstimmt, dann wird der alte ungültig gemacht
                                                   $sql ="UPDATE produktpreise 
                                                                  SET zeitende=NOW() 
                                                                     WHERE id=".mysql_escape_string($preis_row['id'])."";
                                                   mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preis nicht ändern.",mysql_error());
                                                   
                                                         //jetzt wird der neue preis, bestellnummer, gebindegröße hinzugefügt
                                                   $sql = "INSERT INTO produktpreise (produkt_id, preis, zeitstart, zeitende, bestellnummer, gebindegroesse) 
                                                                  VALUES ('".mysql_escape_string($prodIds[$i])."', 
                                                                                 '".mysql_escape_string($preis)."', 
                                                                                 NOW(), 
                                                                                 NULL, 
                                                                                 '".mysql_escape_string($HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]])."', 
                                                                                 '".mysql_escape_string($HTTP_GET_VARS['gebindegroesse_'.$prodIds[$i]] )."')";
                                                    mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preis nicht einfügen.",mysql_error());
                                                
                                                } //end if 
                                             
                                       }          
                                          else if (mysql_num_rows($result2) == 0 && ($HTTP_GET_VARS['preis_'.$prodIds[$i]] != "" || $HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]] != ""))
                                       {   
                                                //wenn es keinen bis dato aktuellen preis gibt und preis, bestellnummer, gebinde nicht null sind, wird nur eine neue zeile eingefügt
                                                
                                                $sql = "INSERT INTO produktpreise (produkt_id, preis, zeitstart, zeitende, bestellnummer, gebindegroesse) 
                                                               VALUES ('".mysql_escape_string($prodIds[$i])."', 
                                                               '".mysql_escape_string($preis)."', 
                                                               NOW(), 
                                                               NULL, 
                                                               '".mysql_escape_string($HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]])."', 
                                                               '".mysql_escape_string($HTTP_GET_VARS['gebindegroesse_'.$prodIds[$i]] )."')";
                                                mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preis nicht einfügen.",mysql_error());
                                       
                                       } //end preise aktualisieren 
                                                                      
                              } // end for ($i=0; $i < count($prodIds); $i++) produkte aktualisieren 
                         
                      } // end else if ($action == "change_all" && isset($HTTP_GET_VARS['prodIds'])) 
                      
      
      // Lieferanten Array aufbauen (ordnet jeder ID einen Namen zu)
         $result = mysql_query("SELECT id,name FROM lieferanten") or error(__LINE__,__FILE__,"Konnte Lieferantennamen nich aus DB laden..",mysql_error());

    
  
            //überprüfen ob ein lieferant schon ausgewählt wurde, ansonsten asuwahlfenster anzeigen:
            
            //gewählte lieferanten_id auslesen          
      $lieferanten_id = $HTTP_GET_VARS['lieferanten_id'];
            
         if ($lieferanten_id !="") {
   ?>
        <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können -->
          <form action="index.php" name="reload_form">
             <input type="hidden" name="area" value="produkte">
               <input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
               <input type="hidden" name="lieferanten_id" value="<?PHP echo $lieferanten_id; ?>">
               <input type="hidden" name="action" value="normal">
               <input type="hidden" name="produkt_id">
          </form>
   
            <table class="menu">
               <tr>
                <td><input type="button" value="Neues Produkt" class="bigbutton" onClick="window.open('windows/insertProdukt.php?produkte_pwd=<?PHP echo $produkte_pwd; ?>','insertProdukt','width=420,height=500,left=100,top=100').focus()"></td>
                  <td valign="middle" class="smalfont">Einen neues Produkt hinzufügen...</td>
                </tr><tr>
                <td><input type="button" value="alle Bearbeiten" class="bigbutton" onClick="document.forms['reload_form'].action.value = 'edit_all'; document.forms['reload_form'].submit();"></td>
                  <td valign="middle" class="smalfont">die gesamte Produktliste bearbeiten...</td>
                </tr><tr>                
                <td><input type="button" value="Reload" class="bigbutton" onClick="document.forms['reload_form'].submit();"></td>
                  <td valign="middle" class="smalfont">diese Seite aktualisieren...</td>
                </tr><tr>
                <td><input type="button" value="Lieferant wechseln" class="bigbutton" onClick="document.forms['reload_form'].action.value = 'edit_all'; document.forms['reload_form'].lieferanten_id.value = ''; document.forms['reload_form'].submit();"></td>
                  <td valign="middle" class="smalfont">anderen Lieferanten auswählen</td>
                </tr><tr>
                <td><input type="button" value="Beenden" class="bigbutton" onClick="self.location.href='index.php'"></td>
                  <td valign="middle" class="smalfont">diesen Bereich verlassen...</td>
                </tr>
            </table>
            
            <br><br>
         
     <?PHP
		    if ($edit_all) {
		?>
		   <form action="index.php" name="editAllForm" method="POST">
			 <input type="hidden" name="area" value="produkte">
			 <input type="hidden" name="action" value="change_all">
		<?PHP
		   } else {
		?>
			<form action="windows/insertBestellung.php" target="insertBestellung" name="newBestellungForm">

	  <?PHP
		   }
		?>
			   <input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
				 <input type="hidden" name="lieferanten_id" value="<?PHP echo $lieferanten_id; ?>">
				 <input type="hidden" name="area" value="produkte">

			
				<?php	//lieferanten bestimmen für die überschrift ...
				
						if($lieferanten_id != "0")		//wenn alle Lieferanten ausgewählt wurde
						{			
								$sql = "SELECT name
													FROM lieferanten
													WHERE id = '$lieferanten_id'";
								$res = mysql_query($sql);
								$lieferant_name = mysql_result($res, 'name');
						} else
						{
								$lieferant_name = "allen Lieferanten";
						}
						
			 if (!$edit_all)
			 {  					// für die normalansicht
			 ?>
				<table class="liste">
				   <tr>
					    <th colspan="9"><h3>Produktübersicht von
              <?php
                echo $lieferant_name;
                if ( $lieferant_name == "Terra" ) {
                 echo '<a class="button" href="artikelsuche.php" target="_new">Katalogsuche</a>';
                }
                 // if( $hat_dienst_IV ) {
                   echo "<a class='button' href='terraabgleich.php?lieferanten_id=$lieferanten_id' target='_new'>Datenbankabgleich</a>";
                 // }
              ?>

              </h3></th>
					 </tr>
	        		<tr>
						 <th></th>
						 <th>Name</th>
						 <th>Produktgruppe</th>
						 <th>Lieferant</th>
						 <th>Einheit</th>
						 <th>Notiz</th>
						 <th>Kategorien</th>
						 <th>Preis</th>
						 <th>Optionen</th>
					</tr>
	<?php	
					} else			//für die alle überarbeiten ansicht
					{		?>
	 			<table>
				   <tr>		
					<th colspan="8"><h3>Produktübersicht von <?php echo $lieferant_name?></h3></th>
				</tr>
				<tr>
						<th>Name</th>
						 <th>Produktgruppe</th>
						 <th>Lieferant</th>
						 <th>Einheit</th>
						 <th>Notiz</th>
						 <th>Gebindegr</th>
						 <th>Preis</th>
						 <th>Bestellnr</th>
						</tr>	 
	<?PHP	}
					
		
//			$result = mysql_query("SELECT p.*, l.name as lname, pp.preis, pp.bestellnummer
//		       			       FROM produkte p, lieferanten l, produktpreise pp
//		       			       WHERE p.id = pp.produkt_id
//					       AND p.lieferanten_id = l.id
//					       AND pp.zeitende IS NULL
//					       AND pp.zeitstart <= NOW()
//					       ORDER BY p.name") or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());

      ?>
            <input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
             <input type="hidden" name="lieferanten_id" value="<?PHP echo $lieferanten_id; ?>">
             <input type="hidden" name="area" value="produkte">

         
            <?php   //lieferanten bestimmen für die überschrift ...
            
                  if($lieferanten_id != "0")      //wenn alle Lieferanten ausgewählt wurde
                  {         
                        $sql = "SELECT name
                                       FROM lieferanten
                                       WHERE id = '$lieferanten_id'";
                        $res = mysql_query($sql);
                        $lieferant_name = mysql_result($res, 'name');
                  } else
                  {
                        $lieferant_name = "allen Lieferanten";
                  }
                  
          if (!$edit_all)
          {                 // für die normalansicht
          ?>
            <table class="liste">
               <tr>
                   <th colspan="9"><h3>Produktübersicht von <?php echo $lieferant_name?></h3></th>
                </tr>
                 <tr>
                   <th></th>
                   <th>Name</th>
                   <th>Produktgruppe</th>
                   <th>Lieferant</th>
                   <th>Einheit</th>
                   <th>Notiz</th>
                   <th>Kategorien</th>
                   <th>Preis</th>
                   <th>Optionen</th>
               </tr>
   <?php   
               } else         //für die alle überarbeiten ansicht
               {      ?>
             <table>
               <tr>      
               <th colspan="8"><h3>Produktübersicht von <?php echo $lieferant_name?></h3></th>
            </tr>
            <tr>
                  <th>Name</th>
                   <th>Produktgruppe</th>
                   <th>Lieferant</th>
                   <th>Einheit</th>
                   <th>Notiz</th>
                   <th>Gebindegr</th>
                   <th>Preis</th>
                   <th>Bestellnr</th>
                  </tr>    
   <?PHP   }
               
      

               ///jetzt werden die produkte aus der datenbank gelesen ....
      
     
     if ($edit_all) {
                             // hier im falle der "alle überarbeiten" ansicht
                             
                             
          $result = mysql_query("SELECT * FROM produktgruppen ORDER BY name;") or error(__LINE__,__FILE__,"Konnte Produktgruppen nich aus DB laden..",mysql_error());   
            while ($row = mysql_fetch_array($result)) 
                $prodgroup_id2name[$row['id']] = $row['name'];
                  
          $result = mysql_query("SELECT * FROM lieferanten ORDER BY name;") or error(__LINE__,__FILE__,"Konnte Produktgruppen nich aus DB laden..",mysql_error());   
            while ($row = mysql_fetch_array($result)) 
                $lieferanten_id2name[$row['id']] = $row['name'];
                  
                  $sql = "SELECT produkte.*, produkte.id as prodId 
                                 FROM produkte
                                 WHERE produkte.lieferanten_id = '$lieferanten_id'
                                 ORDER BY produkte.lieferanten_id, produkte.produktgruppen_id, produkte.name";
                  
                 $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());
                  
       } else {         // hier für die standardansicht
       
                      // welcher lieferant soll angezeigt werden? bei "0" alle lieferanten ansonsten der spezielle
                      if ($lieferanten_id != "0")
                         {
                            $sql = "SELECT produkte.*, produkte.id as prodId, lieferanten.name as lname, produktgruppen.name as pname
                                    FROM produkte,lieferanten,produktgruppen
                                    WHERE lieferanten.id ='$lieferanten_id'
                                    AND produkte.produktgruppen_id = produktgruppen.id
                                    AND produkte.lieferanten_id = lieferanten.id
                                    ORDER BY produkte.lieferanten_id, produkte.produktgruppen_id, produkte.name";
                        $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());
                                    
                         } else {
                      
                   $result = mysql_query("SELECT produkte.*, produkte.id as prodId, lieferanten.name as lname, produktgruppen.name as pname
                                                                 FROM produkte,lieferanten,produktgruppen
                                                                                    WHERE produkte.lieferanten_id = lieferanten.id
                                                                                      AND produkte.produktgruppen_id = produktgruppen.id
                                                                                      ORDER BY produkte.lieferanten_id, produkte.produktgruppen_id, produkte.name") or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());
                        }//end if 
         }//end if
                         
                         
         while ($row = mysql_fetch_array($result))
        {
   ?>
    
           <?PHP if (!$edit_all) { 
              //die normalansicht
           ?>
           <tr>
                   <td valign="top"><input type="checkbox" name="bestelliste[]" value="<?PHP echo $row['prodId']; ?>"></td>
                   <td valign="top"><b><?PHP echo $row['name']; ?></b></td>
                   <td valign="top"><?PHP echo $row['pname']; ?></td>
                   <td valign="top"><?PHP echo $row['lname']; ?></td>
                   <td valign="top"><?PHP echo $row['einheit']; ?></td>
                   <td valign="top" align="middle"><?PHP echo $row['notiz']; ?></td>
                   <td valign="top" align="middle">
                      <?PHP 
                           $kat_result = mysql_query("SELECT produktkategorien.name FROM produktkategorien, kategoriezuordnung WHERE kategoriezuordnung.produkt_id = ".mysql_escape_string($row['prodId'])." AND kategoriezuordnung.kategorien_id = produktkategorien.id;") or error(__LINE__,__FILE__,"Konnte Kategorien nich aus DB laden..",mysql_error());
                            $kategorien_str = "";
                            while ($kat_row = mysql_fetch_array($kat_result)) 
                            {
                               $kategorien_str .= $kat_row['name'];
                            }
                            
                           if ($kategorien_str != "")
                           {
                              echo $kategorien_str;
                            } else 
                            { 
                               echo "-";
                            }
                        ?>
                   </td>
                   <td valign='top' align='middle'>
         <?php             //preise werden eingefügt
			//	    echo "line 362, prodID=".$row['prodId'];
              $result2 =  sql_produktpreise($row['prodId'],0, "NOW()","NOW()");
                                //wenn mehere preise aktuell sind dann die Meldung
                                // (TF: was soll das ^^^ eigentlich? das waere doch eine klare inkonsistenz!)
                if (mysql_num_rows($result2) > 1)
                {
                   echo "-multi-";
                } else {
                   $preis_row = mysql_fetch_array($result2);
                   echo $preis_row['preis'];
                  }
                  echo "
                    </td>
                    <td valign='top'>
                        <a class='png' href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produktid={$row['id']}','foodsoftdetail');\"><img src='img/euro.png' border='0' alt='Preise' titel='Preise'></a>
                        <a class='png' href=\"javascript:f=window.open('windows/editProdukt.php?produkt_id={$row['id']}','editProdukt','width=400,height=450,left=200,top=100'); f.focus();\"><img src='img/b_edit.png' border='0' alt='Produktdaten ändern'  titel='Produktdaten ändern'/></a>
                        <!-- Produkte nicht loeschen, da dynamische Abrechnung Daten benötigt
                        <a class='png' href=\"javascript:deleteProdukt({$row['id']})\"><img src='img/b_drop.png' border='0' alt='Gruppe löschen' titel='Gruppe löschen'/></a>
                        -->
                    </td>
                    </tr>
                  ";
                } else { 
                                          //  alle bearbeiten ansicht ...               
               ?>
           <tr>
                   <td valign="top"><input type="text" name="name_<?PHP echo $row['prodId']; ?>" value="<?PHP echo $row['name']; ?>"></td>
                   <td valign="top">
                      <?PHP 
                           echo "<input type='hidden' name='prodIds[]' value='".$row['prodId']."'>\n";
                           echo "<select name='prodgroup_".$row['prodId']."'>";
                           while (list($key, $value) = each($prodgroup_id2name)) {
                               if ($key == $row['produktgruppen_id']) $sel_str = "selected"; else $sel_str = "";
                               echo "<option value='".$key."' ".$sel_str.">".$value."</option>\n";
                            }
                            reset($prodgroup_id2name);
                            echo "</select>";
                        ?>
                   </td>
                   <td valign="top">
                      <?PHP 
                           echo "<select name='lieferant_".$row['prodId']."'>";
                           while (list($key, $value) = each($lieferanten_id2name)) {
                               if ($key == $row['lieferanten_id']) $sel_str = "selected"; else $sel_str = "";
                               echo "<option value='".$key."' ".$sel_str.">".$value."</option>\n";
                            }
                            reset($lieferanten_id2name);
                            echo "</select>";
                        ?>
                   </td>
                   <td valign="top"><input type="text" size="10" name="einheit_<?PHP echo $row['prodId']; ?>" value="<?PHP echo $row['einheit']; ?>"></td>
                   <td valign="top" align="middle"><input type="text" name="notiz_<?PHP echo $row['prodId']; ?>" value="<?PHP echo $row['notiz']; ?>"></td>
                      <?PHP
			    //echo "line 413, prodID=".$row['prodId'];
                           $result2 =  sql_produktpreise($row['prodId'],0, "NOW()","NOW()");
                            if (mysql_num_rows($result2) > 1)
                               echo "<td valign='top' align='middle'>-multi-</td><td valign='top' align='middle'>-multi-</td>";
                            else {
                               $preis_row = mysql_fetch_array($result2);
                               echo "<td valign='top' align='middle'><input type='text' size='10' name='gebindegroesse_".$row['prodId']."' value='".$preis_row['gebindegroesse']."'></td><td valign='top' align='middle'><input type='text' size='10' name='preis_".$row['prodId']."' value='".$preis_row['preis']."'></td><td valign='top' align='middle'><input type='text' size='10' name='bestellnummer_".$row['prodId']."' value='".$preis_row['bestellnummer']."'></td>";
                            }
                              
                        ?>
                  

                  </tr>
               <?PHP } ?>
    
   <?PHP
        }
   ?>
               <tr>
                   <?PHP 
                        if ($edit_all) { ?>
                           <th colspan="8">
                           <input type="submit" value="Änderungen speichern">
                           &nbsp;| <a href="#" class="tabelle">nach oben</a>
               <?PHP } else { 
                        
                                 //für die normalansicht   
                        ?>   
                        <th colspan="9">               
                        <input type="button" value="neue Bestellung" onClick="window.open('','insertBestellung','width=400,height=450,left=200,top=100').focus() ; document.forms['newBestellungForm'].submit();">
                        &nbsp;| <a href="javascript:checkAll('newBestellungForm','',true)" class="tabelle">alle Produkte Auswählen</a>
                        &nbsp;| <a href="#" class="tabelle">nach oben</a>
                        <?PHP } ?>
                   </th>
                </tr>
           </form>
            </table>            

  <?PHP
            
      } else { //wenn KEINE lieferanten id übergeben wurde, dann auswahlfenster für den lieferanten anzeigen...
      
      ?>
      <form action="index.php">
                                <input type="hidden" name="area" value="produkte">
                                <input type="hidden" name="produkte_pwd" value="<?PHP echo $produkte_pwd; ?>">
                <table class="menu">
                   <tr>
                      <th colspan="2">Anderen Lieferanten auswählen</th>
                   </tr>
                  <tr>
                     <td>Lieferanten auswählen</td>
                     <td>
                           <select name="lieferanten_id">
                              <option value="">[auswählen]</option>
                                  <?PHP
                                  //lieferanten ausspucken
                                    $sql = "SELECT id, name
                                                   FROM lieferanten
                                                   ORDER BY name";            
                                    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
                                    
                                    while ($row = mysql_fetch_array($result)) 
                                    {
                                       //jetzt die anzahl der produkte zählen
                                       $sql = "SELECT id
                                                   FROM produkte
                                                   WHERE lieferanten_id=".$row['id']."";
                                       $res = mysql_query($sql);
                                       $num = mysql_num_rows($res);
                                       
                                       echo "<option value='".$row['id']."'>".$row['name']." (".$num.")</option>\n";
                                    } //end while
                                    ?>
                                 <option value="0">- Alle Lieferanten -</option>
                           </select>
                     </td>                  
                  </tr>       
                   <tr>
                      <td colspan="2" align="right"><input type="submit" value="ok"></td>
                   </tr>
                </table>               
             </form>
            <?php
      } //end if

echo "$print_on_exit";

?>

<script type="text/javascript">
  function neuesfenster(url,name) {
    f=window.open(url,name);
    f.focus();
  }
</script>

