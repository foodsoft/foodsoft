<?php

// um die bestellungen nach produkten sortiert zu sehen ....

  // Konfigurationsdatei einlesen
	include('code/config.php');
	include('code/zuordnen.php');
	
	// Funktionen zur Fehlerbehandlung laden
	include('code/err_functions.php');
	
	// Verbindung zur MySQL-Datenbank herstellen
	include('code/connect_MySQL.php');
	
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
		    $this->SetY(-20);
		    $this->SetTextColor(128,128,128);
		    $this->SetFont('Arial','I',8);
		    $this->MultiCell(0,4,"Bankverbindung: Anton Pieper, Nr. 882299806, BLZ 70010080, Postbank München \r\nSeite ".$this->PageNo()."/{nb}",0,'C');
		}
	
} //end class
// ----------------------------------------------php klassen und funktionen enden ...

// Übergebene Variablen einlesen...
if (isset($_POST['bestgr_pwd'])) $bestgr_pwd = $_POST['bestgr_pwd'];       // Passwort für den Bereich
if (isset($_POST['bestellungs_id'])) $bestell_id = $_POST['bestellungs_id'];
	
verteilmengenZuweisen($bestell_id);
	
$pwd_ok = false;
$bestgrup_view = false;

//infos zur gesamtbestellung auslesen 
$sql = "SELECT * FROM gesamtbestellungen WHERE id = ".$bestell_id."";

$result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden.. ($sql)",mysql_error());
$row_gesamtbestellung = mysql_fetch_array($result);					

//produkte und preise zur aktuellen bestellung auslesen
$sql = "SELECT bestellvorschlaege.produkt_id as produkt_id,
bestellvorschlaege.produktpreise_id as preis_id,
bestellvorschlaege.bestellmenge,produkte.name, produkte.einheit,produktpreise.preis, produktpreise.gebindegroesse, produktpreise.bestellnummer, produktgruppen.name as produktgruppe
FROM bestellvorschlaege 
inner join produkte on (produkte.id = bestellvorschlaege.produkt_id)
inner join produktpreise on (produktpreise.produkt_id = produkte.id)
inner join produktgruppen on (produktgruppen_id = produktgruppen.id)
WHERE gesamtbestellung_id = '".mysql_escape_string($bestell_id)."'
AND produktpreise.id = bestellvorschlaege.produktpreise_id
AND produktgruppen.id = produkte.produktgruppen_id
AND bestellvorschlaege.bestellmenge > 0
ORDER BY produkte.produktgruppen_id, produkte.name ASC";
$result1 = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
//$produkte_row = mysql_fetch_array($result1);

//Lieferant bestimmen
$sql = "SELECT lieferanten.name as name, lieferanten.adresse as adresse, lieferanten.fax as fax
					FROM lieferanten, produkte
					WHERE produkte.id = '".$produkte_row['produkt_id']."'
					AND lieferanten.id = produkte.lieferanten_id";
$result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
$lieferant_row = mysql_fetch_array($result);

//********************  wir starten mit der pdf ausgabe ***********************

$pdf=new PDF();  //pdf-objekt erzeugen
$pdf->SetAuthor('FC Nahrungskette'); 
//$title = "Faxbestellung der FC Schinke09"; //titel für die seiten setzen ...
//$pdf->SetTitle($title); 
$pdf->AliasNbPages();
$pdf->AddPage();

// hier die allgemeinen Infos anzeigen
$pdf->SetY(15);
$pdf->SetFont('Arial','',10);
$pdf->MultiCell(0,5,"FC Nahrungskette\r\nBreitestrasse \r\n14471 Potsdam \r\n \r\nnahrungskette.fcschinke09.de\r\n \r\nPotsdam, den ".date("d.m.Y"),0,'R');
$pdf->Ln();
$pdf->SetFont('Arial','B',10);
$pdf->SetXY(10,30);
$pdf->MultiCell(0,6,$lieferant_row['name'] ."\r\n" . $lieferant_row['adresse'] . "\r\nFAX: " . $lieferant_row['fax']);
$pdf->Ln();
$pdf->SetFont('Arial','B',10);
$pdf->Cell(0,9,"Lieferdatum: ",0);
$pdf->Ln();
$pdf->Cell(0,5,"Ansprechpartner:",0);
$pdf->SetY(80);



 //alle Produkte auflisten:

$total_num_produkte = 0;
		 					
$pdf->SetFont('Arial','B',9);
$pdf->Cell(16,6,'BestellNr.',1);
$pdf->Cell(12,6,'Menge',1);
$pdf->Cell(70,6,'Name',1);
$pdf->Cell(30,6,'Produktgruppe',1);
$pdf->Cell(15,6,'Gebinde',1);	
$pdf->Cell(20,6,'Einheit',1);		
$pdf->Cell(22,6,'Preis/Einheit',1);		
$pdf->Ln();
$index=1;
$pdf->SetFont('Arial','',9);	

//jetzt die namen und preis zu den produkten auslesen
while  ($produkte_row = mysql_fetch_array($result1))
{
			$pdf->Cell(16,5,$produkte_row['bestellnummer'],1);
			$pdf->Cell(12,5,$produkte_row['bestellmenge']/$produkte_row['gebindegroesse'],1);
			$pdf->Cell(70,5,substr($produkte_row['name'],0,45),1);
			$pdf->Cell(30,5,$produkte_row['produktgruppe'],1);	
			$pdf->Cell(15,5,$produkte_row['gebindegroesse'],1);
			$pdf->Cell(20,5,$produkte_row['einheit'],1);
			$pdf->Cell(22,5,$produkte_row['preis'],1);
	    	$pdf->Ln();

			
			
	    
} //end while (namen und preis zu den produkten auslesen)

$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial','I',8);
$pdf->MultiCell(0,4,"Hinweise:
Gebinde und Einheit in der Tabelle beziehen sich auf interne Größen der Foodcoop und können von Ihren Gebinden bzw. Einheiten abweichen.
Die Preis sind inklusive Mehrwertsteuer und ggf. Pfand."); 
$pdf->Output('Faxansicht_' . $lieferant_row['name'] . '_' . date("d.m.Y") . '.pdf',I);



