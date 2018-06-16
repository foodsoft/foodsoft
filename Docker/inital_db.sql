-- MySQL dump 10.16  Distrib 10.1.26-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: foodcoop_foodsoft
-- ------------------------------------------------------
-- Server version	10.1.26-MariaDB-0+deb9u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bankkonten`
--

DROP TABLE IF EXISTS `bankkonten`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bankkonten` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `kontonr` text NOT NULL,
  `blz` text NOT NULL,
  `url` text NOT NULL,
  `kommentar` text NOT NULL,
  `letzter_auszug_jahr` smallint(6) NOT NULL DEFAULT '0',
  `letzter_auszug_nr` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bankkonten`
--

LOCK TABLES `bankkonten` WRITE;
/*!40000 ALTER TABLE `bankkonten` DISABLE KEYS */;
/*!40000 ALTER TABLE `bankkonten` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bankkonto`
--

DROP TABLE IF EXISTS `bankkonto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bankkonto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `valuta` date NOT NULL DEFAULT '0000-00-00',
  `kontoauszug_jahr` smallint(6) NOT NULL DEFAULT '0',
  `kontoauszug_nr` smallint(6) NOT NULL DEFAULT '0',
  `buchungsdatum` date NOT NULL DEFAULT '0000-00-00',
  `dienstkontrollblatt_id` int(11) NOT NULL DEFAULT '0',
  `betrag` decimal(10,2) NOT NULL DEFAULT '0.00',
  `kommentar` text NOT NULL,
  `konto_id` smallint(6) NOT NULL DEFAULT '0',
  `konterbuchung_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `secondary` (`konto_id`,`kontoauszug_jahr`,`kontoauszug_nr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bankkonto`
--

LOCK TABLES `bankkonto` WRITE;
/*!40000 ALTER TABLE `bankkonto` DISABLE KEYS */;
/*!40000 ALTER TABLE `bankkonto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bestellgruppen`
--

DROP TABLE IF EXISTS `bestellgruppen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bestellgruppen` (
  `id` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `passwort` text NOT NULL,
  `salt` char(8) NOT NULL DEFAULT '35464',
  `sockeleinlage` decimal(8,2) NOT NULL DEFAULT '0.00',
  `aktiv` tinyint(1) NOT NULL DEFAULT '0',
  `notiz_gruppe` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bestellgruppen`
--

LOCK TABLES `bestellgruppen` WRITE;
/*!40000 ALTER TABLE `bestellgruppen` DISABLE KEYS */;
INSERT INTO `bestellgruppen` VALUES (1,'Die Pioniere','50UzSCcBEW9kI','501475df',0.00,1,'');
/*!40000 ALTER TABLE `bestellgruppen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bestellvorschlaege`
--

DROP TABLE IF EXISTS `bestellvorschlaege`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bestellvorschlaege` (
  `produkt_id` int(11) NOT NULL DEFAULT '0',
  `gesamtbestellung_id` int(11) NOT NULL DEFAULT '0',
  `produktpreise_id` int(11) NOT NULL DEFAULT '0',
  `liefermenge` decimal(10,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`gesamtbestellung_id`,`produkt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bestellvorschlaege`
--

LOCK TABLES `bestellvorschlaege` WRITE;
/*!40000 ALTER TABLE `bestellvorschlaege` DISABLE KEYS */;
/*!40000 ALTER TABLE `bestellvorschlaege` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bestellzuordnung`
--

DROP TABLE IF EXISTS `bestellzuordnung`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bestellzuordnung` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produkt_id` int(11) NOT NULL DEFAULT '0',
  `gruppenbestellung_id` int(11) NOT NULL DEFAULT '0',
  `menge` decimal(10,3) NOT NULL DEFAULT '0.000',
  `art` tinyint(1) NOT NULL DEFAULT '0',
  `zeitpunkt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `secondary` (`art`,`produkt_id`,`gruppenbestellung_id`),
  KEY `nochnindex` (`produkt_id`,`gruppenbestellung_id`),
  KEY `undnocheiner` (`art`,`gruppenbestellung_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bestellzuordnung`
--

LOCK TABLES `bestellzuordnung` WRITE;
/*!40000 ALTER TABLE `bestellzuordnung` DISABLE KEYS */;
/*!40000 ALTER TABLE `bestellzuordnung` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catalogue_acronyms`
--

DROP TABLE IF EXISTS `catalogue_acronyms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catalogue_acronyms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(10) NOT NULL,
  `acronym` varchar(10) NOT NULL,
  `definition` text NOT NULL,
  `comment` text NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secondary` (`context`,`acronym`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalogue_acronyms`
--

LOCK TABLES `catalogue_acronyms` WRITE;
/*!40000 ALTER TABLE `catalogue_acronyms` DISABLE KEYS */;
/*!40000 ALTER TABLE `catalogue_acronyms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dienste`
--

DROP TABLE IF EXISTS `dienste`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dienste` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dienstkontrollblatt_id` int(11) NOT NULL DEFAULT '0',
  `dienst` enum('1/2','3','4','5','6','freigestellt') DEFAULT NULL,
  `lieferdatum` date DEFAULT NULL,
  `status` enum('Vorgeschlagen','Akzeptiert','Bestaetigt','Offen') DEFAULT NULL,
  `geleistet` tinyint(1) NOT NULL DEFAULT '0',
  `bemerkung` text,
  `gruppenmitglieder_id` int(11) NOT NULL DEFAULT '0',
  `gruppen_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `mitglied` (`gruppenmitglieder_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dienste`
--

LOCK TABLES `dienste` WRITE;
/*!40000 ALTER TABLE `dienste` DISABLE KEYS */;
/*!40000 ALTER TABLE `dienste` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dienstkontrollblatt`
--

DROP TABLE IF EXISTS `dienstkontrollblatt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dienstkontrollblatt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gruppen_id` int(11) NOT NULL DEFAULT '0',
  `dienst` tinyint(1) NOT NULL DEFAULT '0',
  `datum` date NOT NULL DEFAULT '0000-00-00',
  `zeit` time NOT NULL DEFAULT '00:00:00',
  `name` text NOT NULL,
  `telefon` text NOT NULL,
  `notiz` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secondary` (`dienst`,`gruppen_id`,`datum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dienstkontrollblatt`
--

LOCK TABLES `dienstkontrollblatt` WRITE;
/*!40000 ALTER TABLE `dienstkontrollblatt` DISABLE KEYS */;
/*!40000 ALTER TABLE `dienstkontrollblatt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gesamtbestellungen`
--

DROP TABLE IF EXISTS `gesamtbestellungen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gesamtbestellungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `bestellstart` datetime DEFAULT NULL,
  `bestellende` datetime DEFAULT NULL,
  `ausgang` datetime DEFAULT NULL,
  `lieferung` date DEFAULT NULL,
  `bezahlung` datetime DEFAULT NULL,
  `rechnungssumme` decimal(10,2) NOT NULL DEFAULT '0.00',
  `abrechnung_dienstkontrollblatt_id` int(11) NOT NULL DEFAULT '0',
  `rechnungsnummer` text NOT NULL,
  `lieferanten_id` int(11) NOT NULL,
  `rechnungsstatus` smallint(6) NOT NULL,
  `extra_soll` decimal(10,2) NOT NULL,
  `extra_text` text NOT NULL,
  `aufschlag_prozent` decimal(4,2) NOT NULL DEFAULT '0.00',
  `abrechnung_datum` date NOT NULL,
  `abrechnung_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `rechnungsstatus` (`rechnungsstatus`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gesamtbestellungen`
--

LOCK TABLES `gesamtbestellungen` WRITE;
/*!40000 ALTER TABLE `gesamtbestellungen` DISABLE KEYS */;
/*!40000 ALTER TABLE `gesamtbestellungen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gruppen_transaktion`
--

DROP TABLE IF EXISTS `gruppen_transaktion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gruppen_transaktion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dienstkontrollblatt_id` int(11) NOT NULL DEFAULT '0',
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `gruppen_id` int(11) NOT NULL DEFAULT '0',
  `eingabe_zeit` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `summe` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notiz` text NOT NULL,
  `valuta` date NOT NULL DEFAULT '0000-00-00',
  `konterbuchung_id` int(11) NOT NULL DEFAULT '0',
  `lieferanten_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `secondary` (`gruppen_id`,`valuta`),
  KEY `tertiary` (`lieferanten_id`,`valuta`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gruppen_transaktion`
--

LOCK TABLES `gruppen_transaktion` WRITE;
/*!40000 ALTER TABLE `gruppen_transaktion` DISABLE KEYS */;
/*!40000 ALTER TABLE `gruppen_transaktion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gruppenbestellungen`
--

DROP TABLE IF EXISTS `gruppenbestellungen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gruppenbestellungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bestellgruppen_id` int(11) NOT NULL DEFAULT '0',
  `gesamtbestellung_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `secondary` (`gesamtbestellung_id`,`bestellgruppen_id`),
  KEY `gruppe` (`bestellgruppen_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gruppenbestellungen`
--

LOCK TABLES `gruppenbestellungen` WRITE;
/*!40000 ALTER TABLE `gruppenbestellungen` DISABLE KEYS */;
/*!40000 ALTER TABLE `gruppenbestellungen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gruppenmitglieder`
--

DROP TABLE IF EXISTS `gruppenmitglieder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gruppenmitglieder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gruppen_id` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `vorname` text NOT NULL,
  `telefon` text NOT NULL,
  `email` text NOT NULL,
  `diensteinteilung` enum('1/2','3','4','5','freigestellt') NOT NULL DEFAULT 'freigestellt',
  `rotationsplanposition` int(11) NOT NULL,
  `sockeleinlage` decimal(8,2) NOT NULL DEFAULT '0.00',
  `aktiv` tinyint(1) NOT NULL DEFAULT '0',
  `slogan` text NOT NULL,
  `url` text NOT NULL,
  `photo_url` mediumtext NOT NULL,
  `notiz` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rotationsplan` (`rotationsplanposition`),
  KEY `gruppe` (`gruppen_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gruppenmitglieder`
--

LOCK TABLES `gruppenmitglieder` WRITE;
/*!40000 ALTER TABLE `gruppenmitglieder` DISABLE KEYS */;
/*!40000 ALTER TABLE `gruppenmitglieder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gruppenpfand`
--

DROP TABLE IF EXISTS `gruppenpfand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gruppenpfand` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bestell_id` int(11) NOT NULL DEFAULT '0',
  `gruppen_id` int(11) NOT NULL DEFAULT '0',
  `pfand_wert` decimal(6,2) NOT NULL DEFAULT '0.00',
  `anzahl_leer` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `secondary` (`bestell_id`,`gruppen_id`),
  KEY `gruppe` (`gruppen_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gruppenpfand`
--

LOCK TABLES `gruppenpfand` WRITE;
/*!40000 ALTER TABLE `gruppenpfand` DISABLE KEYS */;
/*!40000 ALTER TABLE `gruppenpfand` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leitvariable`
--

DROP TABLE IF EXISTS `leitvariable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leitvariable` (
  `name` varchar(30) NOT NULL,
  `value` text NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leitvariable`
--

LOCK TABLES `leitvariable` WRITE;
/*!40000 ALTER TABLE `leitvariable` DISABLE KEYS */;
INSERT INTO `leitvariable` VALUES ('muell_id','13','Diese spezielle Gruppe &uuml;bernimmt Verluste der FoodCoop\n                   (steht also f&uuml;r die Gemeinschaft aller Mitglieder, die irgendwann per Umlage f&uuml;r die Verluste aufkommen m&uuml;ssen)'),('basar_id','99','Diese spezielle Gruppe bestellt Waren f&uuml;r den Basar'),('database_version','31','Bitte den vorgeschlagenen Wert &uuml;bernehmen und nicht manuell &auml;ndern: diese Variable wird bei Upgrades automatisch hochgesetzt!'),('foodcoop_name','Nahrungskette','Dient nur zur Information, wird im Seitenkopf der Webseiten angezeigt'),('motd','Willkommen bei der Nahrungskette!','Hier kann beliebiger text, einschliessliche einfacher HTML-Formatierung, eingegeben werden'),('bulletinboard','Aktuelle Neuigkeiten','Hier kann beliebiger text (kein html) eingegeben werden'),('member_showcase_count','3','0, um ganz abzuschalten'),('member_showcase_title','<b>Ein paar von uns</b>','Beliebiger Text mit einfachem HTML'),('exportDB','0','Flag (1 oder 0), um Dienst 4 den Export der Datenbank zu erlauben'),('readonly','0','Flag (1 oder 0), um &Auml;nderungen an der Datenbank, etwa w&auml;hrend offline-Betrieb auf\n                  einem anderen Rechner, zu verhindern'),('demoserver','0','Flag (1 oder 0): unterbindet auf oeffentlichen Servern die Unterstuetzung fuer Lieferanten-Kataloge (aus rechtlichen Gruenden)'),('foodsoftserver','(noch namenlos)','Dient nur zur Information und wird in der Fusszeile der Webseiten angezeigt'),('sockelbetrag_gruppe','0.0','\"Gesch&auml;ftsanteil\", mit dem sich jede Gruppe am Eigenkapital der FoodCoop beteiligt'),('sockelbetrag_mitglied','6.00','\"Gesch&auml;ftsanteil\", mit dem sich jedes Mitglied am Eigenkapital der FoodCoop beteiligt'),('aufschlag_default','0.00','Prozentualer Aufschlag auf alle Preise zur Deckung der Selbstkosten der Foodcoop'),('mwst_default','7.00','H&auml;ufigster Mehrwertsteuer-Satz in Prozent'),('toleranz_default','0.00','automatischer Toleranzzuschlag in Prozent bei Bestellungen (kann im Einzelfall manuell runtergesetzt werden)');
/*!40000 ALTER TABLE `leitvariable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lieferanten`
--

DROP TABLE IF EXISTS `lieferanten`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lieferanten` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `strasse` text NOT NULL,
  `ort` text NOT NULL,
  `ansprechpartner` text NOT NULL,
  `telefon` text NOT NULL,
  `fax` text NOT NULL,
  `mail` text NOT NULL,
  `grussformel` text NOT NULL,
  `anrede` text NOT NULL,
  `fc_name` text NOT NULL,
  `fc_strasse` text NOT NULL,
  `fc_ort` text NOT NULL,
  `liefertage` text NOT NULL,
  `bestellmodalitaeten` text NOT NULL,
  `url` text NOT NULL,
  `kundennummer` text NOT NULL,
  `sonstiges` text NOT NULL,
  `katalogformat` varchar(20) NOT NULL,
  `bestellfaxspalten` int(11) NOT NULL DEFAULT '534541',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lieferanten`
--

LOCK TABLES `lieferanten` WRITE;
/*!40000 ALTER TABLE `lieferanten` DISABLE KEYS */;
/*!40000 ALTER TABLE `lieferanten` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lieferantenkatalog`
--

DROP TABLE IF EXISTS `lieferantenkatalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lieferantenkatalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lieferanten_id` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `artikelnummer` bigint(20) NOT NULL,
  `bestellnummer` bigint(20) DEFAULT NULL,
  `liefereinheit` varchar(20) DEFAULT NULL,
  `gebinde` decimal(8,3) DEFAULT NULL,
  `mwst` decimal(4,2) NOT NULL DEFAULT '0.00',
  `pfand` decimal(6,2) NOT NULL DEFAULT '0.00',
  `verband` text NOT NULL,
  `herkunft` text NOT NULL,
  `preis` decimal(8,2) NOT NULL DEFAULT '0.00',
  `katalogdatum` text NOT NULL,
  `katalogtyp` text NOT NULL,
  `katalogformat` varchar(20) NOT NULL,
  `gueltig` tinyint(1) NOT NULL DEFAULT '1',
  `hersteller` text NOT NULL,
  `bemerkung` text NOT NULL,
  `ean_einzeln` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secondary` (`lieferanten_id`,`artikelnummer`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lieferantenkatalog`
--

LOCK TABLES `lieferantenkatalog` WRITE;
/*!40000 ALTER TABLE `lieferantenkatalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `lieferantenkatalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lieferantenpfand`
--

DROP TABLE IF EXISTS `lieferantenpfand`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lieferantenpfand` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `verpackung_id` int(11) NOT NULL DEFAULT '0',
  `bestell_id` int(11) NOT NULL DEFAULT '0',
  `anzahl_voll` int(11) NOT NULL DEFAULT '0',
  `anzahl_leer` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `secondary` (`bestell_id`,`verpackung_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lieferantenpfand`
--

LOCK TABLES `lieferantenpfand` WRITE;
/*!40000 ALTER TABLE `lieferantenpfand` DISABLE KEYS */;
/*!40000 ALTER TABLE `lieferantenpfand` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logbook`
--

DROP TABLE IF EXISTS `logbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL DEFAULT '0',
  `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notiz` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logbook`
--

LOCK TABLES `logbook` WRITE;
/*!40000 ALTER TABLE `logbook` DISABLE KEYS */;
INSERT INTO `logbook` VALUES (1,1,'2018-06-16 11:48:05','successful login. client: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) QtWebEngine/5.11.0 Chrome/65.0.3325.151 Safari/537.36 0 1 0');
/*!40000 ALTER TABLE `logbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pfandverpackungen`
--

DROP TABLE IF EXISTS `pfandverpackungen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pfandverpackungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lieferanten_id` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `wert` decimal(8,2) NOT NULL DEFAULT '0.00',
  `mwst` decimal(6,2) NOT NULL DEFAULT '0.00',
  `sort_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sort_id` (`lieferanten_id`,`sort_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pfandverpackungen`
--

LOCK TABLES `pfandverpackungen` WRITE;
/*!40000 ALTER TABLE `pfandverpackungen` DISABLE KEYS */;
/*!40000 ALTER TABLE `pfandverpackungen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produkte`
--

DROP TABLE IF EXISTS `produkte`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produkte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `artikelnummer` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `lieferanten_id` int(11) NOT NULL DEFAULT '0',
  `produktgruppen_id` int(11) NOT NULL DEFAULT '0',
  `notiz` text NOT NULL,
  `dauerbrenner` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produkte`
--

LOCK TABLES `produkte` WRITE;
/*!40000 ALTER TABLE `produkte` DISABLE KEYS */;
/*!40000 ALTER TABLE `produkte` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produktgruppen`
--

DROP TABLE IF EXISTS `produktgruppen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produktgruppen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produktgruppen`
--

LOCK TABLES `produktgruppen` WRITE;
/*!40000 ALTER TABLE `produktgruppen` DISABLE KEYS */;
/*!40000 ALTER TABLE `produktgruppen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produktpreise`
--

DROP TABLE IF EXISTS `produktpreise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produktpreise` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produkt_id` int(11) NOT NULL DEFAULT '0',
  `lieferpreis` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `zeitstart` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `zeitende` datetime DEFAULT NULL,
  `bestellnummer` varchar(20) NOT NULL,
  `pfand` decimal(6,2) NOT NULL DEFAULT '0.00',
  `mwst` decimal(4,2) NOT NULL DEFAULT '0.00',
  `verteileinheit` varchar(10) NOT NULL DEFAULT '1 ST',
  `gebindegroesse` int(11) NOT NULL DEFAULT '1',
  `liefereinheit` varchar(10) NOT NULL DEFAULT '1 ST',
  `lv_faktor` decimal(12,6) NOT NULL DEFAULT '1.000000',
  PRIMARY KEY (`id`),
  KEY `secondary` (`produkt_id`,`zeitende`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produktpreise`
--

LOCK TABLES `produktpreise` WRITE;
/*!40000 ALTER TABLE `produktpreise` DISABLE KEYS */;
/*!40000 ALTER TABLE `produktpreise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cookie` varchar(10) NOT NULL,
  `login_gruppen_id` int(11) NOT NULL DEFAULT '0',
  `dienst` tinyint(1) NOT NULL DEFAULT '0',
  `dienstkontrollblatt_id` int(11) NOT NULL DEFAULT '0',
  `session_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `muteReconfirmation_timestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES (1,'05b3de9f55',1,0,0,'2018-06-16 11:48:05',NULL);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `itan` varchar(10) NOT NULL,
  `session_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
INSERT INTO `transactions` VALUES (1,1,'734501e133',0),(2,1,'f51e580ffd',0),(3,0,'abb68f88c0',0),(4,1,'bc45f8764e',0),(5,0,'f0c908bb0a',1),(6,0,'952a4fa8d1',1);
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-06-16 11:49:17
