<h1>Produktdatenbank ....</h1>

<?PHP
  assert( $angemeldet ) or exit();

/* wie das skript funktioniert:

   1. variablen einlesen, passwort prüfen
   2. über die action variable wird geprüft, welcher button gedrückt wurde
      2.1. action = delete -> produkt wird gelöscht
      2.2. action = edit_all ->  die alle bearbeiten seite wird angezeigt
      2.3. action = change_all -> die preise werden ggf. aktualisiert .. 

*/
  get_http_var( 'lieferanten_id', 'u', false, true );

  get_http_var('action','w','');

  $edit_all = false;
  if ($action == "edit_all" and !$readonly ) { 
    nur_fuer_dienst(4);
         $edit_all = true;
  }


  /////////////////////////////
  //
  // tabelle fuer hauptmenue und auswahl lieferanten:
  //
  /////////////////////////////
  ?> <table width='100%' class='layout'><tr> <?

  if( $lieferanten_id ) {
    ?> <td> <table class='menu'> <?
    if( !$readonly ) {
      ?>
        <tr>
          <td><input type='button' value='Neues Produkt eintragen' class='bigbutton' onClick="window.open('index.php?window=insertProdukt','insertProdukt','width=450,height=500,left=100,top=100').focus()"></td>
        </tr><tr>
          <td><input type='button' value='Alle Produkte bearbeiten' class='bigbutton' onClick="document.forms['reload_form'].action.value = 'edit_all'; document.forms['reload_form'].submit();"></td>
        </tr>
      <?
    }
    ?>
      <tr>
        <td><input type='button' value='Seite aktualisieren' class='bigbutton' onClick="document.forms['reload_form'].submit();"></td>
      </tr><tr>
        <td><input type='button' value='Beenden' class='bigbutton' onClick="self.location.href='index.php';"></td>
      </tr>
      </table>
    <?
  }

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
      echo "
        <tr>
          <td><a class='tabelle' href='" . self_url('lieferanten_id') . "&lieferanten_id={$row['id']}'>{$row['name']}</a></td>
          <td>{$row['anzahl_produkte']}</td>
        </tr>
      ";
    } else {
      echo "
        <tr class='active'>
          <td style='font-weight:bold;'>{$row['name']}</td>
          <td>{$row['anzahl_produkte']}</td>
        </tr>
      ";
    }
  }
  ?>
        </table>
      </td>
    </tr>
    </table>
  <?

  if( ! $lieferanten_id )
    return;

  /////////////////////////////
  //
  // aktionen verarbeiten:
  //
  /////////////////////////////

  if ($action == "change_all" && isset($HTTP_GET_VARS['prodIds'])) {
    nur_fuer_dienst(4);
    fail_if_readonly();
    get_http_vars( 'prodIds[]', 'u', array() );
    for ($i=0; $i < count($prodIds); $i++) { //produkte werden aktualisiert ...
      $pid = $prodIds[$i];
      sql_update_produkt( $prodIds[$i],
				    $HTTP_GET_VARS['name_'.$pid],
						$HTTP_GET_VARS['lieferant_'.$pid],
						$HTTP_GET_VARS['prodgroup_'.$pid],
						$HTTP_GET_VARS['einheit_'.$pid],
						$HTTP_GET_VARS['notiz_'.$pid]
					      );
      // ggf. neuen Preiseintrag schreiben:
      $fname = 'preis_'.$pid;
      get_http_var( $fname, 'f' );
      $preis = $$fname;
      //aber erst wird geprüft, ob es aktuelle preise für das produkt gibt
      $result2 =  sql_produktpreise($prodIds[$i],0, "NOW()","NOW()");
                                    
                                       if (mysql_num_rows($result2) == 1) // wenn eine zeile mit gültigem preis existiert ...
                                       {         
                                             $preis_row = mysql_fetch_array($result2);
                                             
                                                if ($preis_row['preis'] != $HTTP_GET_VARS['preis_'.$prodIds[$i]] || $preis_row['bestellnummer'] != $HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]] || $preis_row['gebindegroesse'] != $HTTP_GET_VARS['gebindegroesse_'.$prodIds[$i]]) 
                                                {
                                                            //wenn der neue preis (oder bestellnummer oder gebindegröße)nicht mit dem alten übereinstimmt, dann wird der alte ungültig gemacht
						   sql_expire_produktpreis($preis_row['id']);
                                                         //jetzt wird der neue preis, bestellnummer, gebindegröße hinzugefügt
						   sql_insert_produktpreis($prodIds[$i], 
							   $preis, "NOW()", "NULL", 
							   $HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]],
							   $HTTP_GET_VARS['gebindegroesse_'.$prodIds[$i]] 
						   );
                                                
                                                } //end if 
                                             
                                       }          
				       else if (mysql_num_rows($result2) == 0 
					       && ($HTTP_GET_VARS['preis_'.$prodIds[$i]] != "" 
					       || $HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]] != ""
				       )) {   
                                                //wenn es keinen bis dato aktuellen preis gibt und preis, bestellnummer, gebinde nicht null sind, wird nur eine neue zeile eingefügt
                                                
					       sql_insert_produktpreis($prodIds[$i],
							   $preis, "NOW()", "NULL", 
							   $HTTP_GET_VARS['bestellnummer_'.$prodIds[$i]],
							   $HTTP_GET_VARS['gebindegroesse_'.$prodIds[$i]] 
						       );
                                       
                                       } //end preise aktualisieren 
                                                                      
    } // end for ($i=0; $i < count($prodIds); $i++) produkte aktualisieren 
                         
  } // end else if ($action == "change_all" && isset($HTTP_GET_VARS['prodIds'])) 
                      
      
  
      //überprüfen ob ein lieferant schon ausgewählt wurde, ansonsten asuwahlfenster anzeigen:
           
            
  /////////////////////////////
  //
  // Produkttabelle anzeigen:
  //
  /////////////////////////////
  
        echo "
          <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können -->
          <form action='index.php' name='reload_form'>
             <input type='hidden' name='area' value='produkte'>
               <input type='hidden' name='lieferanten_id' value='$lieferanten_id'>
               <input type='hidden' name='action' value='normal'>
               <input type='hidden' name='produkt_id'>
          </form>
        ";
   
		    if ($edit_all) {
		?>
		   <form action="index.php" name="editAllForm" method="POST">
			 <input type="hidden" name="area" value="produkte">
			 <input type="hidden" name="action" value="change_all">
		<?PHP
		   } else {
		?>
			<form action="index.php?window=insertBestellung" method="post" target="insertBestellung" name="newBestellungForm">

	  <?PHP
		   }
		?>
				 <input type="hidden" name="lieferanten_id" value="<?PHP echo $lieferanten_id; ?>">
				 <input type="hidden" name="area" value="produkte">

			
				<?php	//lieferanten bestimmen für die überschrift ...
				
	                 $lieferant_name = lieferant_name($lieferanten_id);
						
			 if (!$edit_all)
			 {  					// für die normalansicht
			 ?>
				<table class="liste">
				   <tr>
					    <th colspan="9"><h3>Produktübersicht von
              <?php
                echo $lieferant_name;
                if ( $lieferant_name == "Terra" ) {
                 ?> <a class="button" href="javascript:neuesfenster('index.php?window=artikelsuche','artikelsuche');">Katalogsuche</a> <?
                }
                 // if( $hat_dienst_IV ) {
                   ?> <a class="button" href="javascript:neuesfenster('index.php?window=terraabgleich&lieferanten_id=<? echo $lieferanten_id; ?>','terraabgleich;');">Datenbankabgleich</a> <?
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
					
		
      ?>
             <input type="hidden" name="lieferanten_id" value="<?PHP echo $lieferanten_id; ?>">
             <input type="hidden" name="area" value="produkte">

         
            <?php   //lieferanten bestimmen für die überschrift ...
            
                  $lieferant_name = lieferant_name($lieferanten_id);
                  
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
                             
                             
	    $result = sql_produktgruppen();
            while ($row = mysql_fetch_array($result)) 
                $prodgroup_id2name[$row['id']] = $row['name'];
                  
          $result = sql_lieferanten(); 
            while ($row = mysql_fetch_array($result)) 
                $lieferanten_id2name[$row['id']] = $row['name'];
                  
                  
	    $result = getProdukteVonLieferant($lieferanten_id);
                  
       } else {         // hier für die standardansicht
       
                      // welcher lieferant soll angezeigt werden? bei "0" alle lieferanten ansonsten der spezielle
                      if ($lieferanten_id != "0")
                         {
				 //TODO mit 
				 //getAlleProdukteVonLieferant 
				 //zusammen
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
                   echo "<span class='warn'>Inkonsistenz!</a>";
                } else {
                   $preis_row = mysql_fetch_array($result2);
                   echo $preis_row['preis'];
                  }
                  echo "
                    </td>
                    <td valign='top'>
                  ";
                  if( !$readonly ) {
                    echo "
                      <a class='png' href=\"javascript:neuesfenster('index.php?window=terraabgleich&produktid={$row['id']}','produktdetails');\"><img src='img/euro.png' border='0' alt='Preise' titel='Preise'></a>
                      <a class='png' href=\"javascript:f=window.open('index.php?window=editProdukt&produkt_id={$row['id']}','editProdukt','width=400,height=450,left=200,top=100'); f.focus();\"><img src='img/b_edit.png' border='0' alt='Produktdaten ändern'  titel='Produktdaten ändern'/></a>
                      <!-- Produkte nicht loeschen, da dynamische Abrechnung Daten benötigt
                      <a class='png' href=\"javascript:deleteProdukt({$row['id']})\"><img src='img/b_drop.png' border='0' alt='Gruppe löschen' titel='Gruppe löschen'/></a>
                      -->
                    ";
                  }
                  echo "
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
                        if( !$readonly ) {
                          ?>   
                          <th colspan="9">               
                          <input type="button" value="neue Bestellung" onClick="window.open('','insertBestellung','width=400,height=450,left=200,top=100').focus() ; document.forms['newBestellungForm'].submit();">
                          &nbsp;| <a href="javascript:checkAll('newBestellungForm','',true)" class="tabelle">alle Produkte Auswählen</a>
                          &nbsp;| <a href="#" class="tabelle">nach oben</a>
                          <?PHP
                        }
                     } ?>
                   </th>
                </tr>
           </form>
            </table>            


