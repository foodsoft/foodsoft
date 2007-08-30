

<?PHP
//   error_reporting(E_ALL); // alle Fehler anzeigen
   require_once("$foodsoftpath/code/zuordnen.php");
   require_once("$foodsoftpath/code/views.php");


   if( ! $angemeldet ) {
     echo "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>";
     return;
   } else	 {
	//$status und $useDate definieren, welche Bestellungen angezeigt werden
	$status = array(STATUS_BESTELLEN);
	$useDate = FALSE;
        if( $hat_dienst_IV ) {
	  //Alle Bestellungen mit Status Bestellen oder LIEFERANT
	  $status[] = STATUS_LIEFERANT;
          $gruppen_id = sql_basar_id();                 // dienst IV bestellt fuer basar...
          // echo "<div class='warn'>dienst IV: bestellt fuer $gruppen_id</div>";
          echo "<h1>Bestellen f&uuml;r den Basar</h1>";
        } else {
	  //Nur aktuell gültige Bestellungen
	  $useDate = TRUE;
          $gruppen_id = $login_gruppen_id;  // ...alle anderen fuer sich selbst!
          echo "<h1>Bestellen f&uuml;r Gruppe $login_gruppen_name</h1>";
    }

	wikiLink("foodsoft:bestellen", "Wiki...");

			
					   // Aktuelle Bestellung ermitteln...
						 if (isset($HTTP_GET_VARS['bestellungs_id'])) {
						    $bestell_id = $HTTP_GET_VARS['bestellungs_id'];
						    if($hat_dienst_IV){
						    	verteilmengenLoeschen($bestell_id);
						    }
						    $result = sql_bestellungen(FALSE,FALSE,$bestell_id);
						 } else {
						 	$result = sql_bestellungen($status, $useDate);
						 }
				
						if (mysql_num_rows($result) > 1) 
						{
					?>
					
					       Es laufen im Moment mehrere Bestellungen. Bitte eine wählen:<br />
								 <br />
					    			 <table style="width:600px;" class="liste">
										 	<tr>
												<th>Name</th>
												<th>Beginn</th>
												<th>Ende</th>
												<th>Produkte</th>
											</tr>
											<?php
											while ($row = mysql_fetch_array($result)) 
											{ echo "
											<tr>											
												<td><a class=\"tabelle\" href=\"index.php?area=bestellen&gruppen_id=".$gruppen_id."&bestellungs_id=".$row['id']."\">".$row['name']."</a></td>
												<td>".$row['bestellstart']."</td>
												<td>".$row['bestellende']."</td>";
												//jetzt die anzahl der produkte bestimmen ...
												$sql ="SELECT * 
																FROM bestellvorschlaege 
																WHERE gesamtbestellung_id=".$row['id']."";
												$res = mysql_query($sql);
												$num = mysql_num_rows($res);
												echo"
												<td>".$num."</td>
											</tr>	";
												}
							echo "</table> ";
							exit;  //hier ist dann zu ende ...
              ?>
											
				
							 		
		<?PHP
				 } else 	{		// jetzt wird die gewählte bestellung angezeigt
				 
				    $row_gesamtbestellung = mysql_fetch_array($result);
				    $bestell_id = $row_gesamtbestellung['id'];
						$gesamt_preis = 0;
						$max_gesamt_preis = 0;
						
						
						// Lieferantenname zu den Lieferanten-Nummern auslesen
						$result = mysql_query("SELECT name,id FROM lieferanten") or error(__LINE__,__FILE__,"Konnte Lieferantennamen nich aus DB laden..",mysql_error());
						while ($row = mysql_fetch_array($result)) $lieferanten_id2name[$row['id']] = $row['name'];
						
						
						// Produktgruppennamen zu den Produktgruppen-Nummern auslesen
						$result = mysql_query("SELECT name,id FROM produktgruppen ORDER BY produktgruppen.id") or error(__LINE__,__FILE__,"Konnte Produktgruppen nich aus DB laden..",mysql_error());
						while ($row = mysql_fetch_array($result)) $produktgruppen_id2name[$row['id']] = $row['name'];
						
				 		//von benni eingefügt: notiz auslesen und nachher als acronym ausgeben..
				 		
				 
		?>
		
		
				<script type="text/javascript">
        <!--
           var gebindegroessen  = new Array();
					 var gebindepreis        = new Array();
				   var bestellGrenzenUG = new Array();
					 var bestellGrenzenOG = new Array();
					 
					 
					 var gesamtBestellmengeAnfang = new Array();
					 
					 var gruppenToleranzen = new Array();
					 var gruppen_id = <?PHP echo $gruppen_id; ?>;
					 
					 var anzahl_produkte;
					 
					 
					 <?PHP 
					    if (isset($HTTP_GET_VARS['action']) && $HTTP_GET_VARS['action']=='reload') echo "var geandert = ".$HTTP_GET_VARS['isChanged'].";\n";
							else echo "var geandert = false;\n";
					?>
					 
					//TESTLAUF => anfang

					 var produktIds = new Array();
					 var prodNames = new Array();
					 
						function addProd(produktId, prodName) {		 
								    produktIds[produktIds.length] = produktId;
										prodNames[prodNames.length] = prodName;
						}
					//TESTLAUF => ende
					 
					 function addGebinde(produktId, groesse, preis) {
					 
					    if (!gebindegroessen[produktId]) {
							   gebindegroessen[produktId]     = new Array();
							   gebindepreis[produktId]           = new Array();
								 bestellGrenzenUG[produktId]  = new Array();
								 bestellGrenzenOG[produktId]  = new Array();
								 
							}
							
							var length = gebindegroessen[produktId].length;
							gebindegroessen[produktId][length]     = groesse;
							gebindepreis[produktId][length]           = preis;
							
					 }
					 
					 function addBestellgrenzen(produktId, UG, OG) {
					    bestellGrenzenUG[produktId][bestellGrenzenUG[produktId].length] = UG;
							bestellGrenzenOG[produktId][bestellGrenzenOG[produktId].length] = OG;
					 }
					 
					 
					 function addGesamtBestellmengeAnfang(produktId, menge) {
					    gesamtBestellmengeAnfang[produktId]=menge;
					 }
					 
					 
					 function mengeInGebinde(produktId, gebUG, gebOG, gesamtMenge, inGebindeMenge) {
					    var inGebinde = 0;
							var bestellUG, bestellOG;
							var aktuelleGesamtMenge;
							
							
							
					    for (var i = 0; i < bestellGrenzenOG[produktId].length; i++) {
							   if (gesamtMenge <= 0) return inGebinde;
							   if (!(bestellGrenzenOG[produktId][i] < gebUG || bestellGrenzenUG[produktId][i] > gebOG)) {
								   
									 bestellUG = bestellGrenzenUG[produktId][i];
									 bestellOG = bestellGrenzenOG[produktId][i];
									 aktuelleGesamtMenge = gesamtMenge - inGebinde - inGebindeMenge;
									 if (bestellUG < inGebindeMenge) bestellUG = inGebindeMenge + 1;
//alert('geb: ['+gebUG+','+gebOG+']     bestell: ['+bestellUG+','+bestellOG+'] (gesamt_menge: '+aktuelleGesamtMenge+')');
									  if (bestellOG - bestellUG + 1 > aktuelleGesamtMenge) { 
										   bestellOG = bestellUG + aktuelleGesamtMenge - 1; 
//alert('NEU: geb: ['+gebUG+','+gebOG+']     bestell: ['+bestellUG+','+bestellOG+'] (gesamt_menge: '+aktuelleGesamtMenge+')');
										}

										
								    if (bestellUG >= gebUG) {
										   if (bestellOG < gebOG)
											    inGebinde += 1 + bestellOG - bestellUG;
											else
											   inGebinde += 1 + gebOG - bestellUG;
										} else {
										   if (bestellOG > gebOG)
											    inGebinde += 1 + gebOG - gebUG;
											else
											   inGebinde += 1 +bestellOG - gebUG;
										}

								 }
						} //alert('ret'+inGebinde);
							return inGebinde;
					 }
					 
					 
					 function setColorPreisColum(farbcode) {
					 
							for (i = 0; i < anzahl_produkte; i++) document.getElementById("kosten_colum_"+i).bgColor = farbcode;
							
							document.getElementById("td_neuer_kontostand").bgColor = farbcode
							document.getElementById("td_gesamt_preis").bgColor         = farbcode
							document.getElementById("td_kontostand").bgColor            = farbcode			
							
					 }
					 
				   function changeMenge(produktId, schritt, art) {
					    geandert = true;
					 
					    // feste Bestellmengen auslesen
					    mengeInGeb     = document.getElementById("menge_geb_"+produktId).firstChild.nodeValue;
							mengeNichtGeb = document.getElementById("menge_nichtgeb_"+produktId).firstChild.nodeValue;
							mengeGesamt   = document.getElementById("menge_gesamt_"+produktId).firstChild.nodeValue;
							
							// toleranz Bestellmengen auslesen
							toleranzInGeb     = document.getElementById("toleranz_geb_"+produktId).firstChild.nodeValue;
							toleranzNichtGeb = document.getElementById("toleranz_nichtgeb_"+produktId).firstChild.nodeValue;
							toleranzGesamt   = document.getElementById("toleranz_gesamt_"+produktId).firstChild.nodeValue;
							
							if (art==0) {   // es wurden feste Bestellmengen geändert
							
							   newMengeInGeb     = parseInt(mengeInGeb);
							   newMengeNichtGeb = parseInt(mengeNichtGeb) + schritt;
							   newMengeGesamt   = parseInt(mengeGesamt) + schritt;

							   newToleranzNichtGeb = parseInt(toleranzNichtGeb);
								 newToleranzInGeb     = parseInt(toleranzInGeb);
							   newToleranzGesamt   = parseInt(toleranzGesamt) ;

							} else {         // es wurden Toleranzmengen geändert

                 newMengeInGeb        = parseInt(mengeInGeb);
							   newMengeNichtGeb    = parseInt(mengeNichtGeb);
							   newMengeGesamt      = parseInt(mengeGesamt);
								 
							   newToleranzNichtGeb = parseInt(toleranzNichtGeb) + schritt;
								 newToleranzInGeb     = parseInt(toleranzInGeb);
							   newToleranzGesamt   = parseInt(toleranzGesamt) + schritt;
								 
							}
							
							var eigeneMengeGesamt   = newMengeInGeb + newMengeNichtGeb;
							var eigeneToleranzGesamt = newToleranzInGeb + newToleranzNichtGeb;
							
							if (eigeneMengeGesamt < 0 || eigeneMengeGesamt > 999 || eigeneToleranzGesamt < 0 || eigeneToleranzGesamt > 999) return;
							
							
							// ggf. Gebinde neu aufteilen...
							//if ((newMengeNichtGeb < 0 && mengeInGeb > 0)|| newMengeNichtGeb >= gebindegroessen[produktId][gebindegroessen[produktId].length - 1]) {
							   var rest = newMengeGesamt;
								 var gebinde_anzahl = 0;
								 var inGebinden = 0;
								 var eigeneMengeInGebinde = 0;
								 var anz_gebgroessen = gebindegroessen[produktId].length;
								 
								 var gebindeAufteilung = new Array();
								 var eigeneMengeInGeb = new Array();
								 var gebindeUG, gebindeOG;
								 var eigeneMengeDiesesGebinde;

							   for (var i=0; i < anz_gebgroessen; i++) {
								    eigeneMengeDiesesGebinde = 0;
										
								    gebinde_anzahl = Math.floor(rest / gebindegroessen[produktId][i]);
										rest = rest % gebindegroessen[produktId][i];
										
										gebindeAufteilung[i] = gebinde_anzahl;
										
										if (gebinde_anzahl > 0) {
										   gebindeUG = inGebinden + 1;
										   inGebinden += gebinde_anzahl * gebindegroessen[produktId][i];
											 gebindeOG = inGebinden;
											 
											 eigeneMengeDiesesGebinde = mengeInGebinde(produktId, gebindeUG, gebindeOG, eigeneMengeGesamt, eigeneMengeInGebinde);
											
											 if (gebindeOG > gesamtBestellmengeAnfang[produktId]) {
											    if (gebindeUG <= gesamtBestellmengeAnfang[produktId])
													   eigeneMengeDiesesGebinde += gebindeOG - gesamtBestellmengeAnfang[produktId];
												  else
													   eigeneMengeDiesesGebinde += 1 + gebindeOG - gebindeUG;
											 }

											 eigeneMengeInGebinde += eigeneMengeDiesesGebinde;
										}
								  eigeneMengeInGeb[i] = eigeneMengeDiesesGebinde;
										
								 }
										
 
								 
								 newMengeNichtGeb = eigeneMengeGesamt - eigeneMengeInGebinde;
								 newMengeInGeb = eigeneMengeInGebinde;

								 
								 
								 // jetzt noch die toleranz Bestellmengen berücksichtigen, falls ein Rest geblieben ist...								 
								 var toleranz_geb_nr = -1;
								 gruppenToleranzen[produktId][gruppen_id] = newToleranzInGeb + newToleranzNichtGeb;     // aktuelle eigene Gruppentoleranzmenge setzen
								 newToleranzInGeb = 0;
								 document.getElementById("anz_prod("+produktId+")geb("+(anz_gebgroessen-1)+")").style.color = "";    // farbmarkierung vorerst zurücksetzen
								 var fuellmenge = gebindegroessen[produktId][anz_gebgroessen-1] - rest;                            // füllmenge berechnen
								 var toleranzGefuellt = (rest > 0 && newToleranzGesamt  >= fuellmenge);
						 
								 if (toleranzGefuellt) {                                                                        // wenn genug Gesamttoleranzen vorhanden, dann fuellen...
								    // neue Bestellmengen setzen
								    newMengeInGeb      += newMengeNichtGeb;
										var festInToleranzGeb = newMengeNichtGeb;
										newMengeNichtGeb    = 0;
										

										var tempGruppenToleranzen = gruppenToleranzen[produktId].slice(0,gruppenToleranzen[produktId].length);
										
										// eigene Toleranzmengen-Anteile berechnen

								    do {
//if (!confirm('schleife...')) break;
										   for (var var_gruppen_id in tempGruppenToleranzen) { 
											    if (tempGruppenToleranzen[var_gruppen_id]  && tempGruppenToleranzen[var_gruppen_id] > 0) {

													   tempGruppenToleranzen[var_gruppen_id]--;
														 fuellmenge--;
														 if (var_gruppen_id == gruppen_id) newToleranzInGeb++;
														 
														 if (fuellmenge == 0) break;
											    }
												}
										} while (fuellmenge > 0);
										

										// Gebindeaufteilung an Toleranzfüllung anpassen
										toleranz_geb_nr = anz_gebgroessen-1;										
										gebindeAufteilung[toleranz_geb_nr]++;
										var inAktGebinde = gebindeAufteilung[toleranz_geb_nr] * gebindegroessen[produktId][toleranz_geb_nr];
										eigeneMengeInGeb[toleranz_geb_nr] += festInToleranzGeb + newToleranzInGeb;
										for (var i=anz_gebgroessen-2; i >= 0 ; i--) {										   
										   if (inAktGebinde % gebindegroessen[produktId][i] == 0) {
												 gebindeAufteilung[i] += Math.floor(inAktGebinde / gebindegroessen[produktId][i]);
												 gebindeAufteilung[toleranz_geb_nr] = 0;
												 eigeneMengeInGeb[i] += eigeneMengeInGeb[toleranz_geb_nr];
												 eigeneMengeInGeb[toleranz_geb_nr] = 0;
												 toleranz_geb_nr = i;
												 inAktGebinde = gebindeAufteilung[toleranz_geb_nr] * gebindegroessen[produktId][toleranz_geb_nr];
											 }
										}

								 }
								 
								 newToleranzNichtGeb = eigeneToleranzGesamt - newToleranzInGeb;
								 
								 
						  // Gebindeaufteilung darstellen...
							var preis = 0;
							var max_prod_preis = 0;
							
							for (var i=0; i < anz_gebgroessen; i++) {
								 if (i == toleranz_geb_nr)
								    document.getElementById("anz_prod("+produktId+")geb("+i+")").style.color = "#999999";
								 else
								    document.getElementById("anz_prod("+produktId+")geb("+i+")").style.color = "";
										
								 document.getElementById("anz_prod("+produktId+")geb("+i+")").firstChild.nodeValue             = gebindeAufteilung[i];
								 document.getElementById("gruppenMengeInGeb("+produktId+")("+i+")").firstChild.nodeValue = eigeneMengeInGeb[i];
								 
								 // produktpreis berechnen
								 if (max_prod_preis < gebindepreis[produktId][i]) max_prod_preis = gebindepreis[produktId][i];
								 preis += eigeneMengeInGeb[i] * gebindepreis[produktId][i];
							}
							
							// alle nicht auf Gebinde aufgeteilte Mengen mit maximalpreis berechnen
							var max_preis=0;
							
							if (toleranzGefuellt) {
							   max_preis = preis - (newToleranzInGeb *  gebindepreis[produktId][toleranz_geb_nr]);
							   max_preis += (newMengeNichtGeb + newToleranzInGeb + newToleranzNichtGeb) * max_prod_preis;					
							}
							
							preis += (newMengeNichtGeb + newToleranzNichtGeb) * max_prod_preis;
							
							if (!toleranzGefuellt) max_preis = preis;

							if (newMengeNichtGeb >= 0 && newMengeNichtGeb <= 999) {
							   mengeNichtGeb = newMengeNichtGeb;
								 mengeInGeb     = newMengeInGeb;
							   mengeGesamt   = newMengeGesamt;
							};
							
							if (newToleranzNichtGeb >= 0 && newToleranzNichtGeb <= 999) {
							   toleranzNichtGeb = newToleranzNichtGeb;
								 toleranzInGeb     = newToleranzInGeb;
							   toleranzGesamt   = newToleranzGesamt;
							};

						  // feste Bestellmengen zurückschreiben...
							document.getElementById("menge_nichtgeb_"+produktId).firstChild.nodeValue = mengeNichtGeb;
							document.getElementById("menge_geb_"+produktId).firstChild.nodeValue        = mengeInGeb;
							document.getElementById("menge_gesamt_"+produktId).firstChild.nodeValue   = mengeGesamt;
							
              document.getElementsByName("menge_"+produktId)[0].value                             = mengeInGeb + mengeNichtGeb;
							document.getElementsByName("menge_ingeb_"+produktId)[0].value                    = mengeInGeb;
							document.getElementsByName("menge_nichtingeb_"+produktId)[0].value              = mengeNichtGeb;					


							// toleranzen Zurückschreiben
							document.getElementById("toleranz_geb_"+produktId).firstChild.nodeValue          = toleranzInGeb;
							document.getElementById("toleranz_nichtgeb_"+produktId).firstChild.nodeValue   = toleranzNichtGeb;
							document.getElementById("toleranz_gesamt_"+produktId).firstChild.nodeValue     = toleranzGesamt;							
							
							document.getElementsByName("toleranz_"+produktId)[0].value                               = toleranzInGeb + toleranzNichtGeb;
							document.getElementsByName("toleranz_ingeb_"+produktId)[0].value                    = toleranzInGeb;
							document.getElementsByName("toleranz_nichtingeb_"+produktId)[0].value              = toleranzNichtGeb;								


							// preise setzen
							var old_prod_preis = parseFloat(document.getElementById("kosten_"+produktId).firstChild.nodeValue);
							var old_prod_max_preis = parseFloat(document.getElementById("kosten_max_"+produktId).firstChild.nodeValue);
							var old_gruppen_konto = parseFloat(document.getElementById("alt_konto").firstChild.nodeValue);
							
							document.getElementById("kosten_"+produktId).firstChild.nodeValue                 = preis.toFixed(2);
							document.getElementById("kosten_max_"+produktId).firstChild.nodeValue          = max_preis.toFixed(2);
							
							var gesamt_preis        = parseFloat(document.getElementById("gesamt_preis").firstChild.nodeValue);
							var gesamt_preis_max =  parseFloat(document.getElementById("gesamt_preis_max").firstChild.nodeValue);
							
							var diff = (preis - old_prod_preis);
							var max_diff = max_preis - old_prod_max_preis;
							
							gesamt_preis += diff;
							gesamt_preis_max += max_diff;
							
							document.getElementById("gesamt_preis").firstChild.nodeValue   = gesamt_preis.toFixed(2);
							document.getElementById("gesamt_preis_max").firstChild.nodeValue   = gesamt_preis_max.toFixed(2);
							
							var neu_konto = (old_gruppen_konto -  gesamt_preis).toFixed(2);
							var neu_konto_min = (old_gruppen_konto -  gesamt_preis_max).toFixed(2);
							document.getElementById("neu_konto").firstChild.nodeValue = neu_konto;
							document.getElementById("neu_konto_min").firstChild.nodeValue = neu_konto_min;
							
             document.getElementsByName("produkt_preis_"+produktId)[0].value              = preis.toFixed(2);									
							
							if (neu_konto_min < 0)
							   setColorPreisColum("#FF4646");
							else 
							   setColorPreisColum("#FFFFFF");
								 
							
							
					 }
					 
					 function bestellungAktualisieren() {
					    document.forms['bestellForm'].gesamt_preis.value = document.getElementById("gesamt_preis").firstChild.nodeValue;
							
					    document.forms['bestellForm'].action.value = "bestellen";
					    document.forms['bestellForm'].submit();
					 }
					 
					 function bestellungBeenden() {
					    if (!geandert || confirm('Die Bestellung wurde geändert, aber noch nicht durchgeführt!\n Wirklich beenden OHNE ZU BESTELLEN?')) self.location.href="index.php";
					 }
					 
					 function bestellungReload() {
							document.forms['bestellForm'].action.value = "reload";
							document.forms['bestellForm'].isChanged.value=geandert;
							//document.forms['bestellForm'].dummy.value=(new Date()).getTime();
					    document.forms['bestellForm'].submit();
					 }

				-->
				</script>		
				
				
				<?bestellung_overview($row_gesamtbestellung, TRUE, $gruppen_id);
				   if (isset($HTTP_GET_VARS['produkt_id'])) {
						//Produkt in Liste aufnehmen
						 
							
						$newproduct = $HTTP_GET_VARS['produkt_id'];
						$errStr = "";
						//echo $newproduct;
						//echo $bestell_id;

						//echo $query;
						$result= sql_produktpreise($newproduct, $bestell_id, $bestellende, $bestellende);
						$row = mysql_fetch_array($result);
						$preis_id = $row['id'];

						
						if ($newproduct == "") $errStr = "Kein Produkt ausgesucht?!";
						if ($bestell_id == "") $errStr = "Bestellung nicht zugeordnet?!";
						if ($preis_id == "") $errStr = "Kein Preis zugeordnet!";
						
						// Wenn keine Fehler, dann einfügen...
						if ($errStr == "") {
							
							
							mysql_query("INSERT INTO bestellvorschlaege 
								     (produkt_id, gesamtbestellung_id, produktpreise_id, liefermenge)
								     VALUES ('".$newproduct."', '".$bestell_id."','".$preis_id."','NULL')")
								     or error(__LINE__,__FILE__,"Konnte neues Produkt nicht einfügen.",mysql_error());
							
						} else {
							echo "<p> Fehler beim Einfügen des zusätzlichen Produkts: ".$errStr." (Produkt_ID = $newproduct, Preis_ID = $preis_id)";
						}
					   }
					  // jetzt werden die anderen bestellungen angezeigt...
					 				// aber nur wenn es mehrere gibt ...
					 				
					 				// die aktuellen bestellungen werden ausgelesen ...
					$result = sql_bestellungen($status, $useDate);
					
		 ?>
					 <table style="width:auto; position:absolute; top:160px; right:10px; font-size:0.9em;" class="menu">
					 			<tr>
					 				<th colspan="2">andere Bestellungen...</th>
					 			</tr>
								<?php
								//nur bei anderen bestellugnen soll das angezeigt werden andernfalls Meldung ...
								//TODO: mit code/views:select_bestellung_view zusammenführen
						if (mysql_num_rows($result) > 1) 
						{										
								while ($row = mysql_fetch_array($result)) 
								{ 
										if ($row['id'] != $bestell_id)
										{
											echo "
											<tr>											
												<td><a class=\"tabelle\" href=\"index.php?area=bestellen&gruppen_id=".$gruppen_id."&bestellungs_id=".$row['id']."\">".$row['name']."</a></td>
												<td> | Ende: ".$row['bestellende']."</td>
											</tr>	";
										}
									}		
							} else 
							{
								echo "
									<tr>
										<td colspan=\"2\">zur Zeit gibt es keine weiteren Bestellungen ...</td>
									</tr>";
								}
				?>					
						</table>
	
								<!-- Bestelltabelle Anfang -->
								
	      <form name="bestellForm" action="index.php" method="POST">
				   <input type="hidden" name="area" value="bestellen">
					 <input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
					 <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
					 <input type="hidden" name="isChanged">
					 <input type="hidden" name="action">
				<table class='numbers' style="margin:40px 0 0 0;">
	        <tr>
						 <th>Bezeichnung</th>
						 <th>Produktgruppe</th>
						 <th>Gebinde</th>
             <th>Anzahl</th>
             <th colspan='2'>Preis</th>
						 <th class="menge">Menge</th>
						 <th class="toleranz">Toleranz</th>
						 <th>Kosten</th>
					</tr>			
		
		<?PHP
		

								
				 
				     // Produkte auslesen & Tabelle erstellen...
				     $sql = "SELECT * 
				     					FROM produkte, bestellvorschlaege 
				     					WHERE produkte.id=bestellvorschlaege.produkt_id 
				     					AND bestellvorschlaege.gesamtbestellung_id='".mysql_escape_string($bestell_id)."' 
				     					ORDER BY produktgruppen_id, name;";
				     					
	           		$result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produktdaten nich aus DB laden..",mysql_error());
	           
						 echo "<script type='text/javascript'>\n <!--\n anzahl_produkte = ".mysql_num_rows($result).";\n  \n--> \n</script>\n";
						 $produkt_counter = 0;
						 $bestellungDurchfuehren = true;   
						 
						 while ($produkt_row = mysql_fetch_array($result)) 
						 {


						   unset($gebindegroessen);
						   unset($gebindepreis);
						 
						    // Gebindegroessen und Preise des Produktes auslesen...
	
						    					
				    				$sql = "SELECT gebindegroesse,preis 
				    					FROM produktpreise 
				    					WHERE id=".mysql_escape_string($produkt_row['produktpreise_id'])." 
				    					ORDER BY gebindegroesse DESC;";
						    					
								    $result2 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Gebindegroessen nich aus DB laden..",mysql_error());
									  $i = 0;
									  
									while ($row = mysql_fetch_array($result2)) 	// das ganze war mal für ein rabattsystem gedacht.
									  {
										   $gebindegroessen[$i]=$row['gebindegroesse'];
											 $gebindepreis[$i]=$row['preis'];
											 $i++;
											 echo "<script type='text/javascript'>\n <!--\n addGebinde(".$produkt_row['id'].",".$row['gebindegroesse'].",".$row['preis'].");  \n--> \n</script>\n";
									  }			 
									  			

						 
						    // Bestellmengenzähler setzen
								$gesamtBestellmengeFest[$produkt_row['id']]                                   = 0;
								$gesamtBestellmengeToleranz[$produkt_row['id']]                             = 0;								
								$gruppenBestellmengeFest[$produkt_row['id']]                                  = 0;
								$gruppenBestellmengeToleranz[$produkt_row['id']]                            = 0;														 
								$gruppenBestellmengeFestInBerstellung[$produkt_row['id']]              = 0;
								$gruppenBestellmengeToleranzInBerstellung[$produkt_row['id']]        = 0;
								
								
								// Hier werden die aktuellen festen Bestellmengen ausgelesen...
						    $result2 = mysql_query("SELECT  *, gruppenbestellungen.id as gruppenbest_id, bestellzuordnung.id as bestellzuordnung_id FROM gruppenbestellungen, bestellzuordnung WHERE bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id AND gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)." AND bestellzuordnung.produkt_id = ".mysql_escape_string($produkt_row['id'])." AND bestellzuordnung.art=0 ORDER BY bestellzuordnung.zeitpunkt;") or error(__LINE__,__FILE__,"Konnte Bestellmengen nich aus DB laden..",mysql_error());
								$intervallgrenzen_counter = 0;
																
								while ($einzelbestellung_row = mysql_fetch_array($result2)) 
								{
										 if ($einzelbestellung_row['bestellguppen_id'] == $gruppen_id) 
										 {
										    $gruppenbestellung_id = $einzelbestellung_row['gruppenbest_id'];
										 
										    $ug = $gruppenBestellintervallUntereGrenze[$produkt_row['id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['id']] + 1;
												$og = $gruppenBestellintervallObereGrenze[$produkt_row['id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['id']] + $einzelbestellung_row['menge'];
												$bestellintervallId[$produkt_row['id']][$intervallgrenzen_counter] = $einzelbestellung_row['bestellzuordnung_id'];
												
												echo "<script type='text/javascript'>\n <!--\n addBestellgrenzen(".$produkt_row['id'].",".$ug.",".$og.");  \n--> \n</script>\n";
												
												$intervallgrenzen_counter++;
										    $gruppenBestellmengeFest[$produkt_row['id']] += $einzelbestellung_row['menge'];
										 }
										 
									 $gesamtBestellmengeFest[$produkt_row['id']] += $einzelbestellung_row['menge'];
								}
								
								echo "<script type='text/javascript'>\n <!--\n addGesamtBestellmengeAnfang(".$produkt_row['id'].",".$gesamtBestellmengeFest[$produkt_row['id']].");  \n--> \n</script>\n";
								$gesamteBestellmengeAnfang = $gesamtBestellmengeFest[$produkt_row['id']];
								
								// wenn die Bestellform neu geladen wird, danngeänderte Bestellmengen beachten
								if (isset($HTTP_GET_VARS['action'])) 
								{
								
								   $action = $HTTP_GET_VARS['action'];
								   $neueMenge = $HTTP_GET_VARS['menge_'.$produkt_row['id']];
									 
										 $diff = $neueMenge - $gruppenBestellmengeFest[$produkt_row['id']];
										   if ($diff > 0) 
										   {
											 
											    $gruppenBestellintervallUntereGrenze[$produkt_row['id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['id']] + 1;
													$gruppenBestellintervallObereGrenze[$produkt_row['id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['id']] + $diff;
													$intervallgrenzen_counter++;											
													
											    $gruppenBestellmengeFest[$produkt_row['id']] = $neueMenge;
													$gesamtBestellmengeFest[$produkt_row['id']]   += $diff;
													
														if ($action == "bestellen") 
														{
															$neueBestellungFest[$produkt_row['id']] = $diff;
														}
											 }
											 else if ($diff < 0) 
											 {
										 
													$gruppenBestellmengeFest[$produkt_row['id']] += $diff;
													$gesamtBestellmengeFest[$produkt_row['id']]   += $diff;
											 
												    for ($j= count($gruppenBestellintervallUntereGrenze[$produkt_row['id']]) - 1; j >= 0; $j--) 
												    {
														   $ug = $gruppenBestellintervallUntereGrenze[$produkt_row['id']][$j];
															 $og = $gruppenBestellintervallObereGrenze[$produkt_row['id']][$j];												 
															 $length = 1 + $og - $ug;
															 
																 if (abs($diff) >= $length) 
																 {
																    	$diff += $length;
																		$gruppenBestellintervallUntereGrenze[$produkt_row['id']][$j] = -2;
																		$gruppenBestellintervallObereGrenze[$produkt_row['id']][$j] = -3;
																		
																			if ($action == "bestellen") 
																			{
																				$neueBestellungDeleteFest[] = $bestellintervallId[$produkt_row['id']][$j];
																			}
																		
																			if ($diff == 0) break;
																		
																 } else {
																    $gruppenBestellintervallObereGrenze[$produkt_row['id']][$j] += $diff;
																		
																		if ($action == "bestellen") $neueBestellungChangeFest[$bestellintervallId[$produkt_row['id']][$j]] = $length + $diff;
																    break;
																 } //end if 
															 
															 
														}//end for ...
										    	
										 		} //end if 
								}

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

								
								// wenn die Bestellform neu geladen wird, danngeänderte Toleranzmengen beachten
								if (isset($HTTP_GET_VARS['action'])) {
								
								   $action = $HTTP_GET_VARS['action'];
								   $neueToleranz = $HTTP_GET_VARS['toleranz_'.$produkt_row['id']];
									 
									 $diff = $neueToleranz - $gruppenBestellmengeToleranz[$produkt_row['id']];
								   if ($diff != 0) {									
											
									    $gruppenBestellmengeToleranz[$produkt_row['id']] = $neueToleranz;
											$gesamtBestellmengeToleranz[$produkt_row['id']]   += $diff;
											
									  if (!isset($toleranzenNachGruppen[$gruppen_id])) $toleranzenNachGruppen[$gruppen_id] = 0;
									  $toleranzenNachGruppen[$gruppen_id] = $neueToleranz;											
											
											if ($action == "bestellen") $neueBestellungToleranz[$produkt_row['id']] = array($toleranzBestellungId, $neueToleranz);
									}
									// else if ($diff < 0) {
									// 
									//		$gruppenBestellmengeToleranz[$produkt_row['id']] += $diff;
									//		$gesamtBestellmengeToleranz[$produkt_row['id']]   += $diff;									 
									//    
									//		if ($action == "bestellen") $neueBestellungChangeToleranz[$toleranzBestellungId] = $gruppenBestellmengeToleranz[$produkt_row['id']];
									 //}
								}
								
								// alle Toleranzmengen der anderen Gruppen in Javascript-Array ablegen
								

								echo "<script type='text/javascript'>\n <!--\n";
								echo "gruppenToleranzen[".$produkt_row['id']."] = new Array();\n";
								if (isset($toleranzenNachGruppen)) {
								   reset($toleranzenNachGruppen);
								   while (list($key, $value) = each($toleranzenNachGruppen)) {
									    echo "gruppenToleranzen[".$produkt_row['id']."][".$key."]=".$value.";\n";
								   }
								} else echo "gruppenToleranzen[".$produkt_row['id']."][".$gruppen_id."]=0;\n";
								//echo "gruppenToleranzen[".$produkt_row['id']."].sort()\n";
								echo "\n-->\n</script>\n";
								
								//
								
								
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
								 
								 
								 if ($festeGebindeaufteilung[$i] > 0) {
								 // if ($gesamteBestellmengeAnfang < $gesamtMengeBestellt) {
								 
							  //   if ($gebindeAnfang <= $gesamteBestellmengeAnfang)
									   //   $gruppenMengeInGebinde[$i] += $gesamtMengeBestellt - $gesamteBestellmengeAnfang;
										  //else
										    //$gruppenMengeInGebinde[$i] += 1 + $gesamtMengeBestellt - $gebindeAnfang;
								     //}
										 
										 
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
                       reset($toleranzenNachGruppen);
										
										   do {
											    while (!(list($key, $value) = each($toleranzenNachGruppen))) reset($toleranzenNachGruppen);   // neue Wete auslesen und ggf. wieder am Anfang des Arrays starten

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
								 
							   $bestell_preis += $gruppenMengeInGebinde[$i] * $gebindepreis[$i];
							}
							$max_preis = $bestell_preis - ($gruppenToleranzInGebinde * $gebindepreis[$toleranzGebNr]);
							$max_preis += $max_prod_preis * ($gruppeGesamtMengeNichtInGebinden + $gruppenToleranzInGebinde + $gruppenToleranzNichtInGebinde);
							$bestell_preis += $max_prod_preis * ($gruppeGesamtMengeNichtInGebinden + $gruppenToleranzNichtInGebinde);
							
							$gesamt_preis += $bestell_preis;
							$max_gesamt_preis += $max_preis;
							
							
							// prüfe ob sich im Falle einer durchführung einer bestellung oder aktualisierung irgendetwas geändert hatt...
							$markiereMengenRow  = false;
							$markiereToleranzRow = false;
							$markierePreis              = false;
							if (isset($action) && $action=="bestellen") {
							
							   $gewuenschteMengeInGeb                 = $HTTP_GET_VARS['menge_ingeb_'.$produkt_row['id']];
								 $gewuenschteMengeNichtInGeb         = $HTTP_GET_VARS['menge_nichtingeb_'.$produkt_row['id']];
								 
								 $gewuenschteTolernanzInGeb              = $HTTP_GET_VARS['toleranz_ingeb_'.$produkt_row['id']];
								 $gewuenschteToleranzNichtInGeb      = $HTTP_GET_VARS['toleranz_nichtingeb_'.$produkt_row['id']];
								 
								 $gewuenschterPreis                            = $HTTP_GET_VARS['produkt_preis_'.$produkt_row['id']];
								 
								 $markerColor = "#96FF96";
								 $darkMarkerColor = "#46FF46";
							   if ($gewuenschteMengeInGeb != $gruppeGesamtMengeInGebinden || $gewuenschteMengeNichtInGeb != $gruppeGesamtMengeNichtInGebinden) {
								    $markiereMengenRow = true;
										$bestellungDurchfuehren = false;
										if ($gewuenschteMengeInGeb > $gruppeGesamtMengeInGebinden) { $markerColor = "#FF9696"; $darkMarkerColor = "#FF4646"; }
								 }
								 
								 if ($gewuenschteTolernanzInGeb != $gruppenToleranzInGebinde || $gewuenschteToleranzNichtInGeb != $gruppenToleranzNichtInGebinde) {
								    $markiereToleranzRow = true;
										$bestellungDurchfuehren = false;
										if ($gewuenschteTolernanzInGeb > $gruppenToleranzInGebinde) { $markerColor = "#FF9696"; $darkMarkerColor = "#FF4646"; }
								 }
								 
								 if (abs($gewuenschterPreis - $bestell_preis) > 0.01) {
								    $markierePreis = true;
										$bestellungDurchfuehren = false;
										
										if ($gewuenschterPreis < $bestell_preis) { $markerColor = "#FF9696"; $darkMarkerColor = "#FF4646"; }
								 }
								 
							}
						 
						 
		?>
		

		
					<tr <?PHP if ($markiereMengenRow || $markiereToleranzRow || $markierePreis) echo "bgcolor='".$markerColor."'"; ?>>
						 <td valign="top">
						 
						<?PHP 
						 echo " <b> ".$produkt_row['name']."</b>";
						 				 
						 				  //jetzt die produktnotizen anhängen falls welche da sind
						 if ( $produkt_row['notiz']!="")
						 {
						 		$notiz=$produkt_row['notiz'];
						 		echo "							
										<span id=\"notiz\">
											<a href=\"#\"><img src=\"img/gluehbirne_15x16.png\" width=\"15\" height=\"16\" border=\"0\" alt=\"\" />
											<span class=\"showcase\">".$notiz."</span></a>
										</span>";
						 		}  ?>
						 </td>
						 <td valign="top"><?PHP echo $produktgruppen_id2name[$produkt_row['produktgruppen_id']]; ?></td>
						 <!-- <td valign="top">
						     <table border="0" width="100%" class="inner"> -->
			<?PHP 
										
											  // Preise zum aktuellen Produkt auslesen..
                        // TF: warum nicht einfach die produktpreis_id aus der bestellvorlage nehmen?
                        // das ist doch der preis, der auch im lieferschein angezeigt, und vom konto abgebucht werden wird!
											
                        $result2 = mysql_query(
                          "SELECT id, gebindegroesse, bestellnummer, preis
                                , mwst, pfand, verteileinheit, liefereinheit, zeitende
                           FROM  produktpreise
                           WHERE id={$produkt_row['produktpreise_id']}"
                        ) or error(__LINE__,__FILE__,"Konnte Produktpreise nich aus DB laden..",mysql_error());												
                        
                        if( $result2 )
                          $preise_row = mysql_fetch_array($result2);
                        else
                          $preise_row = false;

                        if( $preise_row ) {
                          //
                          // bestellvorschlag hat preiseintrag: testen, ob er aktuell ist:
                          //
                          if( $preise_row['zeitende'] and ( $preise_row['zeitende'] < $row_gesamtbestellung['bestellende'] ) ) {
                            $preis_aktuell = false;
                            $result3 = mysql_query(
                              "SELECT * FROM produktpreise
                              WHERE produkt_id={$produkt_row['id']}
                                    AND (ISNULL(zeitende) OR zeitende>='{$row_gesamtbestellung['bestellende']}') "
                            );
                            if( $result3 && mysql_fetch_array($result3) ) {
                              $aktueller_preis_existiert = true;
                            } else {
                              $aktueller_preis_existiert = false;
                            }
                          } else {
                            $preis_aktuell = true;
                            $aktueller_preis_existiert = true;
                          }
                        } else {
                          //
                          // bestellvorschlag hat keinen preiseintrag: testen, ob es einen gibt:
                          //
                          $preis_aktuell = false;
                          $result2 = mysql_query(
                            "SELECT id, gebindegroesse, bestellnummer, preis
                                  , mwst, pfand, verteileinheit, liefereinheit, zeitende
                             FROM  produktpreise
                             WHERE produkt_id={$produkt_row['id']}
                                   AND ( ISNULL(zeitende) OR zeitende>='{$row_gesamtbestellung['bestellende']}') "
                          ) or error(__LINE__,__FILE__,"Konnte Produktpreise nich aus DB laden..",mysql_error());												
                          if( $result2 )
                            $preise_row = mysql_fetch_array($result2);
                          else
                            $preise_row = false;
                          if( $preise_row ) {
                            $aktueller_preis_existiert = true;
                          } else {
                            $aktueller_preis_existiert = false;
                          }
                        }

                        if( $aktueller_preis_existiert ) {
                          $i = 0;
                          preisdatenSetzen( $preise_row );

                          if ($toleranzGebNr == $i) { 
                            $toleranz_color_str = "style='color:#999999'";
                          } else {
                            $toleranz_color_str="";
                          }	

                          echo "
                             <!-- <tr>  -->
                             <td class='number'><b><span id='anz_prod(".$produkt_row['id'].")geb(".$i.")' ".$toleranz_color_str." >".$festeGebindeaufteilung[$i]."</span></b>
                              ({$preise_row['gebindegroesse']}*{$preise_row['kan_verteilmult']} {$preise_row['kan_verteileinheit']})</td>
                             <td class='number'><span id='gruppenMengeInGeb(".$produkt_row['id'].")(".$i.")'>".$gruppenMengeInGebinde[$i]."</span></td>
                             <td
                          ";
                          if( $preis_aktuell ) {
                            echo " class='mult'";
                          } else {
                            echo "
                              class='mult_outdated'
                              title='Preis nicht aktuell: Dienst 4 sollte aktualisieren!'
                            ";
                          }
                          echo "
                            ><a href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produktid={$produkt_row['id']}&bestell_id={$row_gesamtbestellung['id']}','produktdetails');\">
                             ".sprintf("%.02f",$preise_row['preis'])."
                             </a>
                            </td>
                            <td class='unit'> / {$preise_row['kan_verteilmult']} {$preise_row['kan_verteileinheit']}</td>
                            <!-- </tr> -->";
                        } else {
//                           if( $aktueller_preis_existiert ) {
//                             echo "
//                               <td class='mult_outdated' colspan='4'
//                               title='...bedeutet: Dienst 4 sollte den Preis aktualisieren!'
//                               >
//                               Preis 
//                               <a href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produktid={$produkt_row['id']}&bestell_id={$row_gesamtbestellung['id']}','produktdetails');\">
//                                  ".sprintf("%.02f",$preise_row['preis'])."</a>
//                               ist nicht aktuell
//                               </td>
//                             ";
//                           } else {
                            echo "
                              <td class='warn' colspan='4'
                              title='...kann bedeuten: Artikel nicht (mehr) lieferbar!'
                              >Kein aktueller
                                <a href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produktid={$produkt_row['id']}&bestell_id={$row_gesamtbestellung['id']}','produktdetails');\"
                                >Preiseintrag</a>
                              </td>
                            ";
//                          }
                        }
											 
						?>
						    <!--	</table>  -->
						 </td>
						 <td valign="top" <?PHP if ($markiereMengenRow) echo "bgcolor='".$darkMarkerColor."'"; ?>>
						 
								    <table border="0" width="100%" class="inner">
										   <tr>
													<td align="left" >
														<span style="color:#00FF00; font-weight:bold;"><span id="menge_geb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppeGesamtMengeInGebinden; ?></span></span>
														 +  <span style="color:#FF0000;font-weight:bold"><span id="menge_nichtgeb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppeGesamtMengeNichtInGebinden; ?></span></span>
														  / <span id="menge_gesamt_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gesamtBestellmengeFest[$produkt_row['id']]; ?></span>
													</td>
											</tr>
											 <?PHP
											    if ($markiereMengenRow) {
													   echo "
												<tr>
													<td align='left'><span style='color:#0000FF; font-weight:bold'>".$gewuenschteMengeInGeb." + ".$gewuenschteMengeNichtInGeb."</span></td>
												</tr>\n";
													}
											 ?>
											<tr>
													<td align="left">
													<input type="button" value="<" onClick="changeMenge('<?PHP echo $produkt_row['id']; ?>',-1,0)">
													<input type="button" value=">" onClick="changeMenge('<?PHP echo $produkt_row['id']; ?>',1,0)"></td>
											 </tr>
										</table>
																 
						   	 <input type="hidden" name="menge_<?PHP echo $produkt_row['id']; ?>" value="<?PHP echo ($gruppeGesamtMengeInGebinden + $gruppeGesamtMengeNichtInGebinden); ?>">
								<input type="hidden" name="menge_ingeb_<?PHP echo $produkt_row['id']; ?>" value="<?PHP echo $gruppeGesamtMengeInGebinden; ?>">
								<input type="hidden" name="menge_nichtingeb_<?PHP echo $produkt_row['id']; ?>" value="<?PHP echo $gruppeGesamtMengeNichtInGebinden; ?>">
						 </td>
						 
						 <td valign="top" <?PHP if ($markiereToleranzRow) echo "bgcolor='".$darkMarkerColor."'"; ?>>
						 
						    <table border="0" class="inner">
								   <tr>
											<td align="left">
												<span style='color:#00FF00;font-weight:bold;'><span id="toleranz_geb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppenToleranzInGebinde; ?></span></span>
												 +  <span style="color:#FF0000; font-weight:bold;"><span id="toleranz_nichtgeb_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gruppenToleranzNichtInGebinde; ?></span></span>
												  / <span id="toleranz_gesamt_<?PHP echo $produkt_row['id']; ?>"><?PHP echo $gesamtBestellmengeToleranz[$produkt_row['id']]; ?></span>
												</td>
									</tr>
									 <?PHP
									    if ($markiereToleranzRow) {
											   echo "
										<tr>
											<td align='left' ><span style='color:#0000FF; font-weight:bold'>".$gewuenschteTolernanzInGeb." + ".$gewuenschteToleranzNichtInGeb."</span></td></tr>\n";
											}
									 ?>
										<tr>
											<td align="left">
												<input type="button" value="<" onClick="changeMenge('<?PHP echo $produkt_row['id']; ?>',-1,1)">
												<input type="button" value=">" onClick="changeMenge('<?PHP echo $produkt_row['id']; ?>',1,1)">
											</td>
                    				</tr>
							 	</table>
							 							 
							 <input type="hidden" name="toleranz_<?PHP echo $produkt_row['id']; ?>"  value="<?PHP echo ($gruppenToleranzInGebinde + $gruppenToleranzNichtInGebinde); ?>">
								<input type="hidden" name="toleranz_ingeb_<?PHP echo $produkt_row['id']; ?>" value="<?PHP echo $gruppenToleranzInGebinde; ?>">
								<input type="hidden" name="toleranz_nichtingeb_<?PHP echo $produkt_row['id']; ?>" value="<?PHP echo $gruppenToleranzNichtInGebinde; ?>">							 
						 </td>
						 
             <td valign="bottom" align="right" id="kosten_colum_<?PHP echo $produkt_counter; ?>" <?PHP if ($markierePreis) echo "bgcolor='".$darkMarkerColor."'"; ?>>
             	<span style="font-weight:bold" id="kosten_<?PHP echo $produkt_row['id']; ?>"><?PHP echo sprintf("%.02f",$bestell_preis); ?></span><br />
						 <?PHP
						    if ($markierePreis) 
						    {
						    		$preis_style = "color:#0000FF;font-weight:bold;";
								}
						echo "
						 <span style='font-size:0.8em;".$preis_style."'>(<span id='kosten_max_".$produkt_row['id']."'>".sprintf("%.02f",$max_preis)."</span>)</span>";
	 ?>		 
	 				</td>
						 <input type="hidden" name="produkt_preis_<?PHP echo $produkt_row['id']; ?>" value="<?PHP echo sprintf("%.02f",$bestell_preis); ?>">
					</tr>
		
		<?PHP
		       $produkt_counter++;
						 }
		?>
		    <tr>
				   <td colspan="9" align="right"><b>Gesamtpreis:</b></td>
					 <td align="right" id="td_gesamt_preis">
					    <span id="gesamt_preis" style="font-weight:bold;"><?PHP echo sprintf("%.02f",$gesamt_preis); ?></span><br />
							<span style="font-size:0.8em;">(<span id="gesamt_preis_max"><?PHP echo  sprintf("%.02f",$max_gesamt_preis); ?></span>)</span>
					 </td>
					 <input type="hidden" name="gesamt_preis">
				</tr>
		    <tr>
				   <td colspan="9" align="right"><b>Gruppenkontostand:</b></td>
					 <td align="right" id="td_kontostand"><span style="font-weight:bold;" id="alt_konto"><?PHP echo sprintf("%.02f",$kontostand); ?></span</td>
				</tr>							
		    <tr>
				   <td colspan="9" align="right"><b>neuer Kontostand:</b></td>
					 <td align="right" id="td_neuer_kontostand">
					    <span style="font-weight:bold;" id="neu_konto"><?PHP echo sprintf("%.02f",($kontostand - $gesamt_preis)); ?></span><br />
							<span  style="font-size:0.8em;">(<span id="neu_konto_min"><?PHP echo  sprintf("%.02f",($kontostand - $max_gesamt_preis)); ?></span>)</span>
					 </td>
				</tr>				
	      <tr>
				   <th colspan="10">
					     <!-- <input type="button" class="bigbutton" value="aktualisieren" onClick="bestellungReload();"> -->
               <?php
                 if( ! $readonly ) {
                   echo "<input type='button' class='bigbutton' value='bestellen' onClick='bestellungAktualisieren();'>";
                 }
               ?>
				       <input type="button" class="bigbutton" value="Abbrechen" onClick="bestellungBeenden();">
				       
				   </th>
				</tr>
				</table>
		    </form>
 
<?php if( ! $readonly ) {  ?>
   <h3> Zusätzlich Produkt in Bestellvorlage aufnehmen </h3>
   <form method='post' action='index.php?area=bestellen'>
	 <input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
	 <input type="hidden" name="bestellungs_id" value="<?PHP echo $bestell_id; ?>">
	     <?php
	     	    select_products_not_in_list($bestell_id);
	     ?>
	   <input type="submit" value="Produkt hinzufügen">
   </form>
<?php } ?>

   <?PHP
		
						 // prüfe ob sich durch zwischenzeitliche Bestellungen der anderen Bestellgruppen etwas geändert hatt und bereite den Hinweistext vor...
						 if (isset($action) && $action == "bestellen") {
								
							 if (((double)$gesamt_preis - (double)$HTTP_GET_VARS['gesamt_preis']) >= 0.01) {
							    $bestellungDurchfuehren = false;
							    echo "<script type='text/javascript'>\n <!--\n alert('ACHTUNG\\n Andere Bestellgruppen haben in der Zwischenzeit bestellt, leider ist die Bestellung dadurch teurer geworden!\\n Bitte die aktualisierte Bestellung nochmal prüfen und NOCHMAL BESTELLEN!');\n  geandert=true; \n--> \n</script>\n";
							 } else if (! $bestellungDurchfuehren) {	
							    echo "<script type='text/javascript'>\n <!--\n alert('ACHTUNG\\n Andere Bestellgruppen haben in der Zwischenzeit bestellt, die aktuelle Bestellung wurde geändert!\\n Bitte die aktualisierte Bestellung nochmal prüfen und NOCHMAL BESTELLEN!');\n  geandert=true; \n--> \n</script>\n";
						   } else if ($kontostand < $max_gesamt_preis) {
							    $bestellungDurchfuehren = false;
							    echo "<script type='text/javascript'>\n <!--\n alert('ACHTUNG\\n Das Gruppenkonto weist kein ausreichendes Guthaben für diese Bestellung auf. Die Bestellungsdaten werden so NICHT AKTUALISIERT!!\\n Bitte die Bestellung ändern.');\n  geandert=true; \n--> \n</script>\n";
							} else if (((double)$HTTP_GET_VARS['gesamt_preis'] - (double)$gesamt_preis) >= 0.01) {
							   echo "<script type='text/javascript'>\n <!--\n alert('Andere Bestellgruppen haben in der Zwischenzeit bestellt. Der Preis der Bestellung hat sich verbessert!!\\n Die Bestellung wurde aufgenommen.');\n  geandert=false; \n--> \n</script>\n";
							}
								
					 }
		
		        // miese auf dem Konto farblich markieren...
						if ($kontostand  - $max_gesamt_preis < 0) echo "<script type='text/javascript'>\n <!--\n setColorPreisColum('#FF4646');  \n--> \n</script>\n";
		
		        // ggf. die aktuelle Bestellung durchführen....
						if (isset($HTTP_GET_VARS['action']) && $HTTP_GET_VARS['action'] == "bestellen" && $bestellungDurchfuehren) {

							 
							    // Bestellung in die Datenbank eintragen...
							//		echo "trage daten ein!<br>";
									
                  $gruppenbestellung_id = sql_create_gruppenbestellung( $gruppen_id, $bestell_id );
                  //                         ^ ^ ^ ist idempotent!
									
									// erhöte Bestellmengen eintragen
							    if (isset($neueBestellungFest))
									   while (list($key, $value) = each($neueBestellungFest)) {
										    //echo "-> INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art, zeitpunkt) VALUES (".$key.",".$gruppenbestellung_id.",".$value.", 0, NOW()); <br>";
										    mysql_query("INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art, zeitpunkt) VALUES (".mysql_escape_string($key).",".mysql_escape_string($gruppenbestellung_id).",".mysql_escape_string($value).", 0, NOW());")  or error(__LINE__,__FILE__,"Konnte Bestellung nicht in die Datenbank schreiben..",mysql_error());
										 }
									
									// andere Toleranzmengen eintagen
							    if (isset($neueBestellungToleranz))
									   while (list($key, $value) = each($neueBestellungToleranz)) {
										    if ($value[0] == -1)  { // es gibt noch keinen Datensatz mit Toleranzmenge zum produkt, dann einen anlegen... 
												   //echo "-> INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art, zeitpunkt) VALUES (".$key.",".$gruppenbestellung_id.",".$value[1].", 1, NOW());<br>";
										       mysql_query("INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art, zeitpunkt) VALUES (".$key.",".$gruppenbestellung_id.",".$value[1].", 1, NOW());")  or error(__LINE__,__FILE__,"Konnte Bestellung nicht in die Datenbank schreiben..",mysql_error());
												} else { // sonst, den bestehenden ändern
												   //echo "-> (1) UPDATE bestellzuordnung SET menge='".$value[1]."' WHERE id=".$value[0]."<br>";
												   mysql_query("UPDATE bestellzuordnung SET menge='".mysql_escape_string($value[1])."' WHERE id=".mysql_escape_string($value[0]))  or error(__LINE__,__FILE__,"Konnte Bestellung nicht in die Datenbank schreiben..",mysql_error());
											  }
										 }
										 
									// komplettes "Bestellintervall" (d.h. zuordnung das die gruppe das z.b. 5-16 stück des produktes bekommt) löschen
									if (isset($neueBestellungDeleteFest))
									   while (list($key, $value) = each($neueBestellungDeleteFest)) {
										    //echo "-> DELETE FROM bestellzuordnung WHERE id =".$value."<br>";
										    mysql_query("DELETE FROM bestellzuordnung WHERE id =".mysql_escape_string($value)) or error(__LINE__,__FILE__,"Konnte Bestellung nicht in die Datenbank schreiben..",mysql_error());;
										 }
										 
									// ändere "Bestellintervall"
									if (isset($neueBestellungChangeFest))
									   while (list($key, $value) = each($neueBestellungChangeFest)) {
										    //echo "-> (2) UPDATE bestellzuordnung SET menge=".$value." WHERE id =".$key."<br>";
										    mysql_query("UPDATE bestellzuordnung SET menge=".mysql_escape_string($value)." WHERE id =".mysql_escape_string($key)) or error(__LINE__,__FILE__,"Konnte Bestellung nicht in die Datenbank schreiben..",mysql_error());;
										 }
										 
							 

							    ?>
							    <script type="text/javascript">
							    <!-- 
							    alert("Bestellung eingetragen"); 
							    //-->
							    </script>
							    <?
						}
						
						
				
		
				 }
				 
		}
			
  ?>
	
