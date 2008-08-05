<?php

// um die bestellungen nach produkten sortiert zu sehen ....

  // Konfigurationsdatei einlesen
	include('code/config.php');
	
	// Funktionen zur Fehlerbehandlung laden
	include('code/err_functions.php');
	
	// Verbindung zur MySQL-Datenbank herstellen
	include('code/connect_MySQL.php');

//legt den seitenumbruch nach bestimmter anzahl von produkten fest
$MAX_PROD_PER_PAGE = 17;

//----------------------------------------------------beginn der pdf funktion

require('inc/fpdf.php'); //pdf extension wird geladen ..

class PDF extends FPDF //die klassen und funtionen für die pdf erzeugung
{
		//Page header
		function Header()
		{
		    //Logo
		    //$this->Image('logo_pb.png',10,8,33);
		    //Arial bold 15
		    $this->SetFont('Arial','B',15);
		    //Move to the right
		    $this->Cell(80);
		    //Title
		    global $title;
		    $this->Cell(30,10,$title,0,0,'C');
		    //Line break
		    $this->Ln(20);
		}
		
		//Page footer
		function Footer()
		{
		    //Position at 1.5 cm from bottom
		    $this->SetY(-15);
		    //Arial italic 8
		    $this->SetFont('Arial','I',8);
		    //Page number
		    $this->Cell(0,10,'Seite '.$this->PageNo().'/{nb}',0,0,'C');
		}
		//Load data
	/*	function LoadData($file)
		{
		    //Read file lines
		    $lines=file($file);
		    $data=array();
		    foreach($lines as $line)
		        $data[]=explode(';',chop($line));
		    return $data;
		} */
		
		//Simple table
		function BasicTable($header,$data)
		{
		    //Header
		    foreach($header as $col)
		        $this->Cell(40,7,$col,1);
		    $this->Ln();
		    //Data
		    foreach($data as $row)
		    {
		        foreach($row as $col)
		            $this->Cell(40,6,$col,1);
		        $this->Ln();
		    }
		}
		
		//Better table
		function ImprovedTable($header,$data)
		{
		    //Column widths
		    $w=array(40,35,40,45);
		    //Header
		    for($i=0;$i<count($header);$i++)
		        $this->Cell($w[$i],7,$header[$i],1,0,'C');
		    $this->Ln();
		    //Data
		    foreach($data as $row)
		    {
		        $this->Cell($w[0],6,$row[0],'LR');
		        $this->Cell($w[1],6,$row[1],'LR');
		        $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R');
		        $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R');
		        $this->Ln();
		    }
		    //Closure line
		    $this->Cell(array_sum($w),0,'','T');
		}
		
		//Colored table
		function FancyTable($header,$data)
		{
		    //Colors, line width and bold font
		    $this->SetFillColor(255,0,0);
		    $this->SetTextColor(255);
		    $this->SetDrawColor(128,0,0);
		    $this->SetLineWidth(.3);
		    $this->SetFont('','B');
		    //Header
		    $w=array(40,35,40,45);
		    for($i=0;$i<count($header);$i++)
		        $this->Cell($w[$i],7,$header[$i],1,0,'C',1);
		    $this->Ln();
		    //Color and font restoration
		    $this->SetFillColor(224,235,255);
		    $this->SetTextColor(0);
		    $this->SetFont('');
		    //Data
		    $fill=0;
		    foreach($data as $row)
		    {
		        $this->Cell($w[0],6,$row[0],'LR',0,'L',$fill);
		        $this->Cell($w[1],6,$row[1],'LR',0,'L',$fill);
		        $this->Cell($w[2],6,number_format($row[2]),'LR',0,'R',$fill);
		        $this->Cell($w[3],6,number_format($row[3]),'LR',0,'R',$fill);
		        $this->Ln();
		        $fill=!$fill;
		    }
		    $this->Cell(array_sum($w),0,'','T');
		}
} //end class
// ----------------------------------------------php klassen und funktionen enden ...


// Übergebene Variablen einlesen...
if (isset($_POST['bestgr_pwd'])) $bestgr_pwd = $_POST['bestgr_pwd'];       // Passwort für den Bereich
if (isset($_POST['bestellungs_id'])) $bestell_id = $_POST['bestellungs_id'];
	
	
$pwd_ok = false;
$bestgrup_view = false;

//infos zur gesamtbestellung auslesen 
$sql = "SELECT * FROM gesamtbestellungen WHERE id = ".$bestell_id."";

$result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
$row_gesamtbestellung = mysql_fetch_array($result);					


$pdf=new PDF();  //pdf-objekt erzeugen
$pdf->SetAuthor('FC Schinke09'); 
$title = $row_gesamtbestellung['name']; //titel für die seiten setzen ...
$pdf->SetTitle($title); 
$pdf->SetFont('Arial','B',10);
$pdf->AliasNbPages();
$pdf->AddPage();
//bestellungsinformationen anzeigen....
$pdf->Cell(25,7,'Bestellstart: ',1);
$pdf->Cell(40,7,$row_gesamtbestellung['bestellstart'],1);
$pdf->Ln();
$pdf->Cell(25,7,'Bestellende: ',1);
$pdf->Cell(40,7,$row_gesamtbestellung['bestellende'],1);
$pdf->Ln();
$pdf->Ln();




 //erstmal alle Produkte auflisten:

$total_num_produkte = 0;
		 						
//produkte und preise zur aktuellen bestellung auslesen
$sql = "SELECT bestellvorschlaege.produkt_id as produkt_id, bestellvorschlaege.produktpreise_id as preis_id,produkte.name, produkte.einheit,produktpreise.preis, produktpreise.gebindegroesse, produktgruppen.name as produktgruppe
FROM bestellvorschlaege, produkte, produktpreise, produktgruppen
WHERE gesamtbestellung_id = '".mysql_escape_string($bestell_id)."'
AND produkte.id = bestellvorschlaege.produkt_id
AND produktpreise.id = bestellvorschlaege.produktpreise_id
AND produktgruppen.id = produkte.produktgruppen_id
ORDER BY produkte.produktgruppen_id, produkte.name ASC";

$result1 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
		 					
$pdf->SetFont('Arial','B',9);	
$pdf->Cell(10,6,'Index',1);				
$pdf->Cell(15,6,'Kürzel',1);	
$pdf->Cell(70,6,'Name',1);	
$pdf->Cell(20,6,'Einheit',1);	
$pdf->Cell(15,6,'Gebinde',1);	
$pdf->Cell(10,6,'Preis',1);		
$pdf->Cell(30,6,'Produktgruppe',1);
$pdf->Cell(12,6,'Menge',1);
$pdf->Ln();
$index=1;
$pdf->SetFont('Arial','',9);	

//jetzt die namen und preis zu den produkten auslesen
while  ($produkte_row = mysql_fetch_array($result1))
{
	  //variablen für bertschs algorithmus setzen										
	  unset($gebindegroessen);
	  unset($gebindepreis);
							   			
	  $i = 0;
	  $gebindegroessen[$i]=$produkte_row['gebindegroesse'];
	  $gebindepreis[$i]=$produkte_row['preis'];
					
					
	  //--------------------- jetzt überprüfen, ob das produkt bestellt wurde
	  $sql = "SELECT bestellzuordnung.id, bestellzuordnung.menge, bestellzuordnung.art
														FROM bestellzuordnung, gruppenbestellungen
														WHERE produkt_id = ".$produkte_row['produkt_id']."
														AND bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id
	
	      AND gruppenbestellungen.gesamtbestellung_id = ".$bestell_id.";";
	
	  $result3 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
			 								
	  //produktmenge pro produkt werden ausgelesen...
	  $produktmenge = 0;
	  while ($produktmenge_row = mysql_fetch_array($result3))
	    {
		      $produktmenge += $produktmenge_row['menge'];
	    }
			 								
	  //reichen die bestellten mengen? dann weiter im text
	  if ($produktmenge >= $produkte_row['gebindegroesse'])
	    {
	    
	      $total_num_produkte++;
			      
			 // zur berechnung der bestellten menge
			$menge = $produktmenge/$produkte_row['gebindegroesse'];
			$menge = (int)$menge; 
			
			//das produktkürzel ...
			$produkt_kuerzel[$index] = substr($produkte_row['name'], 0, 6);
			     			      
			$pdf->Cell(10,5,$index,1);  
			$pdf->Cell(15,5,$produkt_kuerzel[$index],1);  // kürzel
			$pdf->Cell(70,5,substr($produkte_row['name'],0,45),1);
			$pdf->Cell(20,5,$produkte_row['einheit'],1);
			$pdf->Cell(15,5,$produkte_row['gebindegroesse'],1);
			$pdf->Cell(10,5,$produkte_row['preis'],1);
			$pdf->Cell(30,5,$produkte_row['produktgruppe'],1);	
			$pdf->Cell(12,5,$menge,1);
	    	$pdf->Ln();

			
			      $index++;
			
	    } //end if
	    
} //end while (namen und preis zu den produkten auslesen)


// ----------------- beginne die 2x2 matrix zu bilden  

//--------wenn aber mehr als XX produkte bestellt wurden, müssen diese auf neuen seiten angezeigt werden. andernfalls würde diese einfach abgeschnitten


$produktNum=0;

//jetzt die Tabellen:
$num_site = -1;



while (($num_site+1)*$MAX_PROD_PER_PAGE < $total_num_produkte)
{

  $num_site++;
	
$pdf->AddPage(L); // "L" steht für landscape
$pdf->SetFont('Arial','B',9);	//header schön fett machen
$pdf->Cell(40,5,'Gruppen',1);

	//----------------abkürzungen anzeigen ...


  $produktNum = 0;
for ($j=1;$j<$index;$j++) {
  $produktNum++;
  //jetzt gucken ob das Produkt auf diese Seite soll
  if ($produktNum > ($MAX_PROD_PER_PAGE*$num_site) && $produktNum <= ($MAX_PROD_PER_PAGE*($num_site+1))) {
    $pdf->Cell(14,5,$produkt_kuerzel[$j],1);
  }
}
$pdf->Ln(); //nächste Zeile ...





//-------------------------------------------------Gruppen--------------------------------
// jetzt die gruppen zur bestellung auslesen ...
$sql = "SELECT  gruppenbestellungen.bestellguppen_id, bestellgruppen.name, gruppenbestellungen.id as gruppenbestellung_id
																FROM gruppenbestellungen, bestellgruppen 
																WHERE gruppenbestellungen.gesamtbestellung_id = ".$bestell_id." 
																AND bestellgruppen.id = gruppenbestellungen.bestellguppen_id
																ORDER BY bestellgruppen.name ASC ";
$result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());



while ($gruppen_row = mysql_fetch_array($result))
{
  $gruppen_id = $gruppen_row['bestellguppen_id'];
  $gruppen_name = $gruppen_row['name'];
  
  
  
  //------------------------------------gucken ob die Gruppe überhaupt bestellt hat----------
  
  $sql = "SELECT gruppenbestellungen.id	FROM gruppenbestellungen WHERE gruppenbestellungen.bestellguppen_id = ".$gruppen_id."";																		
  $result2 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
  
  if (mysql_num_rows($result2)!= 0 )
    {
      $pdf->SetFont('Arial','B',9); //fett machen
      $pdf->Cell(40,5,substr($gruppen_name,0,21),1);	//echo "<tr><td>$gruppen_name</td>\n";
		$pdf->SetFont('Arial','',9); //und schnell wieder dünn machen

      //--------------------------------------------------Produkte------------------------------
		 						
      //produkte und preise zur aktuellen bestellung auslesen
      $sql = "SELECT bestellvorschlaege.produkt_id as produkt_id, bestellvorschlaege.produktpreise_id as preis_id,produkte.name, produkte.einheit,produktpreise.preis, produktpreise.gebindegroesse, produktgruppen.name as produktgruppe
							FROM bestellvorschlaege, produkte, produktpreise, produktgruppen
							WHERE gesamtbestellung_id = '".mysql_escape_string($bestell_id)."'
							AND produkte.id = bestellvorschlaege.produkt_id
							AND produktpreise.id = bestellvorschlaege.produktpreise_id
							AND produktgruppen.id = produkte.produktgruppen_id
							ORDER BY produkte.produktgruppen_id, produkte.name ASC";

      $result1 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
		 					
      $produktNum = 0;
       
      //jetzt die namen und preis zu den produkten auslesen
      while  ($produkte_row = mysql_fetch_array($result1))
	{
	  //variablen für bertschs algorithmus setzen										
	  unset($gebindegroessen);
	  unset($gebindepreis);
						   			
	  $i = 0;
	  $gebindegroessen[$i]=$produkte_row['gebindegroesse'];
	  $gebindepreis[$i]=$produkte_row['preis'];
				
				
	  //--------------------- jetzt überprüfen, ob das produkt bestellt wurde
	  $sql = "SELECT bestellzuordnung.id, bestellzuordnung.menge, bestellzuordnung.art
													FROM bestellzuordnung, gruppenbestellungen
													WHERE produkt_id = ".$produkte_row['produkt_id']."
													AND bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id

      AND gruppenbestellungen.gesamtbestellung_id = ".$bestell_id.";";

	  $result3 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
		 								
	  //produktmenge pro produkt werden ausgelesen...
	  $produktmenge = 0;
	  while ($produktmenge_row = mysql_fetch_array($result3))
	    {
	      $produktmenge += $produktmenge_row['menge'];
	    }
		 								
	  //reichen die bestellten mengen? dann weiter im text
	  if ($produktmenge >= $produkte_row['gebindegroesse'])
	    {

	      //--------------------- jetzt überprüfen, ob das produkt von dieser Gruppe bestellt wurde
	      $produktNum++;
	      //jetzt gucken ob das Produkt auf diese Seite Soll
	      if ($produktNum > ($MAX_PROD_PER_PAGE*$num_site) && $produktNum <= ($MAX_PROD_PER_PAGE*($num_site+1))) {
	      $sql = "SELECT bestellzuordnung.id, bestellzuordnung.menge, bestellzuordnung.art
													FROM bestellzuordnung, gruppenbestellungen
													WHERE produkt_id = ".$produkte_row['produkt_id']."
													AND bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id

      AND gruppenbestellungen.bestellguppen_id = ".$gruppen_id."
													AND gruppenbestellungen.gesamtbestellung_id = ".$bestell_id.";";
	      $result4 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());

	      if (mysql_num_rows($result4)!= 0 )
		{

		  //-------------------Produkt wurde bestellt dann-----------------------------------------------



							 					
		  //beginn bertsch version
							 				
		  // Bestellmengenzähler setzen
		  $gesamtBestellmengeFest[$produkte_row['produkt_id']]                                   = 0;
		  $gesamtBestellmengeToleranz[$produkte_row['produkt_id']]                             = 0;								
		  $gruppenBestellmengeFest[$produkte_row['produkt_id']]                                  = 0;
		  $gruppenBestellmengeToleranz[$produkte_row['produkt_id']]                            = 0;														 
		  $gruppenBestellmengeFestInBerstellung[$produkte_row['produkt_id']]              = 0;
		  $gruppenBestellmengeToleranzInBerstellung[$produkte_row['produkt_id']]        = 0;
														
		  unset($gruppenBestellintervallUntereGrenze);
		  unset($gruppenBestellintervallObereGrenze);
		  unset($bestellintervallId);
														
														
		  // Hier werden die aktuellen FESTEN Bestellmengen ausgelesen...
														
		  $sql = "SELECT  *, gruppenbestellungen.id as gruppenbest_id, bestellzuordnung.id as bestellzuordnung_id 
																			FROM gruppenbestellungen, bestellzuordnung 
																			WHERE bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id 
																			AND gruppenbestellungen.gesamtbestellung_id = ".$bestell_id." 
																			AND bestellzuordnung.produkt_id = ".$produkte_row['produkt_id']." 
																			AND bestellzuordnung.art=0 
																			ORDER BY bestellzuordnung.zeitpunkt;";													
		  $result2 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellmengen nich aus DB laden..",mysql_error());
												    
		  $intervallgrenzen_counter = 0;	
																					
		  while ($einzelbestellung_row = mysql_fetch_array($result2)) 
		    {
		      if ($einzelbestellung_row['bestellguppen_id'] == $gruppen_id) 
			{
			  $gruppenbestellung_id = $einzelbestellung_row['gruppenbest_id'];
																	 
			  $ug = $gruppenBestellintervallUntereGrenze[$produkte_row['produkt_id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkte_row['produkt_id']] + 1;
			  $og = $gruppenBestellintervallObereGrenze[$produkte_row['produkt_id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkte_row['produkt_id']] + $einzelbestellung_row['menge'];
			  $bestellintervallId[$produkte_row['produkt_id']][$intervallgrenzen_counter] = $einzelbestellung_row['bestellzuordnung_id'];
																						
			  $intervallgrenzen_counter++;
			  $gruppenBestellmengeFest[$produkte_row['produkt_id']] += $einzelbestellung_row['menge'];
							
																	    				
			}
		      $gesamtBestellmengeFest[$produkte_row['produkt_id']] += $einzelbestellung_row['menge'];
		    }
														
		  $gesamteBestellmengeAnfang = $gesamtBestellmengeFest[$produkte_row['produkt_id']];

						
		  unset($toleranzenNachGruppen);
						                			
						                			
		  // Hier werden die aktuellen toleranz Bestellmengen ausgelesen...
														
		  $result2 = mysql_query("SELECT *, bestellzuordnung.id as bestellzuordnung_id 
												   	 															FROM gruppenbestellungen, bestellzuordnung 
												   	 															WHERE bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id 
												   	 															AND gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)." 
												   	 															AND bestellzuordnung.produkt_id = ".mysql_escape_string($produkte_row['produkt_id'])." 
												   	 															AND bestellzuordnung.art=1 
												   	 															ORDER BY bestellzuordnung.zeitpunkt;") or error(__LINE__,__FILE__,"Konnte Bestellmengen nich aus DB laden..",mysql_error());
		  $toleranzBestellungId = -1;
		  while ($einzelbestellung_row = mysql_fetch_array($result2)) 
		    {						
		      if ($einzelbestellung_row['bestellguppen_id'] == $gruppen_id) 
			{
			  $gruppenBestellmengeToleranz[$produkte_row['produkt_id']] += $einzelbestellung_row['menge'];
			  $toleranzBestellungId =  $einzelbestellung_row['bestellzuordnung_id'];

			}
		      $gesamtBestellmengeToleranz[$produkte_row['produkt_id']] += $einzelbestellung_row['menge'];
																 
		      // für jede Gruppe getrennt die Toleranzmengen ablegen
		      $bestellgruppen_id = $einzelbestellung_row['bestellguppen_id'];
		      if (!isset($toleranzenNachGruppen[$bestellgruppen_id])) $toleranzenNachGruppen[$bestellgruppen_id] = 0;
		      $toleranzenNachGruppen[$bestellgruppen_id] += $einzelbestellung_row['menge'];
																 
		    }
														
		  if (isset($toleranzenNachGruppen)) ksort($toleranzenNachGruppen);
													
														
		  // jetzt die Gebindeaufteilung berechnen
		  unset($gruppenMengeInGebinde);
		  unset($festeGebindeaufteilung);
													
		  $rest_menge = $gesamtBestellmengeFest[$produkte_row['produkt_id']]; 
		  $gesamtMengeBestellt = 0;
		  $gruppeGesamtMengeInGebinden = 0;
														
		  for ($i=0; $i < count($gebindegroessen); $i++) 
		    {
		      $festeGebindeaufteilung[$i] = floor($rest_menge / $gebindegroessen[$i]);
		      $rest_menge = $rest_menge % $gebindegroessen[$i];
														 
		      // berechne: wieviel  hat die aktuelle Gruppe in diesem Gebinde
		      $gebindeAnfang = $gesamtMengeBestellt + 1;
		      $gesamtMengeBestellt += $festeGebindeaufteilung[$i] * $gebindegroessen[$i];
														 
		      $gruppenMengeInGebinde[$i]       = 0;
														 
														 
		      if ($festeGebindeaufteilung[$i] > 0) 
			{
																 
																 
			  for ($j=0; $j < count($gruppenBestellintervallUntereGrenze[$produkte_row['produkt_id']]); $j++) 
			    {
																 
			      $ug = $gruppenBestellintervallUntereGrenze[$produkte_row['produkt_id']][$j];
			      $og = $gruppenBestellintervallObereGrenze[$produkte_row['produkt_id']][$j];
			      $gebindeEnde = $gesamtMengeBestellt;
						
			      if ($ug >= $gebindeAnfang && $ug <= $gebindeEnde) 
				{  
				  // untere Grenze des Bestellintervalls im aktuellen Gebinde...
				  if ($og >= $gebindeAnfang && $og <= $gebindeEnde)   
				    { 
				      // und die obere Grenze auch dann...
				      $gruppenMengeInGebinde[$i] += 1 + $og - $ug;
				    }
				  else    // und die obere Grenze nicht, dann ...
				    {
				      $gruppenMengeInGebinde[$i] += 1 + $gebindeEnde - $ug;    // alles bis zum Intervallende
				    } //end if
				} 
			      else if ($og >= $gebindeAnfang && $og <= $gebindeEnde) // die obere Grenze des Bestellintervalls im aktuellen Gebinde, und die untere nicht, dann...
				{  
				  $gruppenMengeInGebinde[$i] += 1 + $og - $gebindeAnfang;    // alles ab Intervallanfang bis obere Grenze
				}
			      else if ($ug < $gebindeAnfang && $og > $gebindeEnde) { //die untere Grenze des Bestellintervalls unterhalb und die obere oberhalb des aktuellen Gebindes, dann..
				$gruppenMengeInGebinde[$i] += 1 + $gebindeEnde - $gebindeAnfang;    // das gesamte Gebinde
			      } //end if
																				
			    } // end for
																 	
			} //end if
						
		      $gruppeGesamtMengeInGebinden += $gruppenMengeInGebinde[$i];
														 	
		    } //end for ($i=0; $i < count($gebindegroessen); $i++)
													
		  // versuche offenes Gebinde mit Toleranzmengen zu füllen							
		  $gruppenToleranzInGebinde     = 0;
		  $toleranzGebNr                      = -1;
											
		  if ($rest_menge != 0) 
		    {
		      $fuellmenge = $gebindegroessen[count($gebindegroessen)-1] - $rest_menge;
		      $gruppen_anzahl = count($toleranzenNachGruppen);
														 	
		      if ($fuellmenge <= $gesamtBestellmengeToleranz[$produkte_row['produkt_id']]) 
			{
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
			  $gruppenMengeInGebinde[$toleranzGebNr] += $gruppenBestellmengeFest[$produkte_row['produkt_id']]  - $gruppeGesamtMengeInGebinden;
			  $gruppenMengeInGebinde[$toleranzGebNr] += $gruppenToleranzInGebinde;
			  $gruppeGesamtMengeInGebinden = $gruppenBestellmengeFest[$produkte_row['produkt_id']];
			  $toleranzFuellung = count($gebindegroessen) -1;
																	 
			  // Gebindeaufteillung an Toleranzfüllung anpassen...
			  $anzInAktGeb = $festeGebindeaufteilung[$toleranzGebNr] * $gebindegroessen[$toleranzGebNr];											 
						
			  for ($i = count($gebindegroessen)-2; $i >= 0 ; $i--)
																		 
			    if (($anzInAktGeb % $gebindegroessen[$i]) == 0) 
			      {
																				
																				
				$gruppenMengeInGebinde[$i] += $gruppenMengeInGebinde[$toleranzGebNr];
				$gruppenMengeInGebinde[$toleranzGebNr] = 0;
																					 
				$festeGebindeaufteilung[$i] += floor($anzInAktGeb / $gebindegroessen[$i]);
				$festeGebindeaufteilung[$toleranzGebNr] = 0;
				$toleranzGebNr = $i;
				$anzInAktGeb = $festeGebindeaufteilung[$toleranzGebNr] * $gebindegroessen[$toleranzGebNr];														 
							
			      } //end if
																		 
			}// end if ($fuellmenge <= $gesamtBestellmengeToleranz[$produkte_row['produkt_id']]) 
														 				
		    }// end if ($rest_menge != 0) 
						
		  $gruppenToleranzNichtInGebinde = $gruppenBestellmengeToleranz[$produkte_row['produkt_id']] - $gruppenToleranzInGebinde;
		  $gruppeGesamtMengeNichtInGebinden = $gruppenBestellmengeFest[$produkte_row['produkt_id']]  - $gruppeGesamtMengeInGebinden;
						
		  // Preis berechnen
		  $bestell_preis = 0;
		  $max_prod_preis     = 0;
		  for ($i = 0; $i < count($gebindegroessen); $i++) 
		    {
		      if ($gebindepreis[$i] > $max_prod_preis) $max_prod_preis = $gebindepreis[$i];
														 
		      if (!$gesamt_liste)
			$bestell_preis += $gruppenMengeInGebinde[$i] * $gebindepreis[$i];
		      else
			$bestell_preis += $festeGebindeaufteilung[$i] * $gebindepreis[$i] * $gebindegroessen[$i]; // benni: variable $gebindegroessen[$i] hinzugefügt
		    }
		  //$max_preis = $bestell_preis - ($gruppenToleranzInGebinde * $gebindepreis[$toleranzGebNr]);
		  //$max_preis += $max_prod_preis * ($gruppeGesamtMengeNichtInGebinden + $gruppenToleranzInGebinde + $gruppenToleranzNichtInGebinde);
		  //$bestell_preis += $max_prod_preis * ($gruppeGesamtMengeNichtInGebinden + $gruppenToleranzNichtInGebinde);
													
		  $gesamt_preis += $bestell_preis;
		  //$max_gesamt_preis += $max_preis;
													
		  $anzGeb = 0;
		  for ($i=0; $i < count($festeGebindeaufteilung); $i++) $anzGeb += $festeGebindeaufteilung[$i];


				
		  //ende bertsch version ....		
		  // echo "<td><b>".($gruppeGesamtMengeInGebinden + $gruppenToleranzInGebinde)."</b> (".$gruppenToleranzInGebinde.")</td>\n";				
		$mengenanzeige = $gruppeGesamtMengeInGebinden + $gruppenToleranzInGebinde;
		$pdf->Cell(14,5,$mengenanzeige,1);

		}
	      else // felder für die nicht bestellten produkte einer gruppe
		$pdf->Cell(14,5,'--',1);	//echo "  <td>--</td>";
	    }
	    }
	}
       
     $pdf->Ln();	// echo "</tr>\n";
    }
}
}
				
$pdf->Output($title . '.pdf',I);
exit();
//echo "</table>\n";		 		
?>