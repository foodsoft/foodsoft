-- phpMyAdmin SQL Dump
-- version 2.10.1
-- http://www.phpmyadmin.net
-- 
-- Host: 127.0.0.1
-- Generation Time: Aug 27, 2008 at 01:00 PM
-- Server version: 5.0.45
-- PHP Version: 5.2.2

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Database: `nahrungskette`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `bankkonten`
-- 

CREATE TABLE `bankkonten` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `kontonr` text NOT NULL,
  `blz` text NOT NULL,
  `url` text NOT NULL COMMENT 'Link zum online-Banking',
  `kommentar` text NOT NULL,
  `letzter_auszug_jahr` smallint(6) NOT NULL default '0',
  `letzter_auszug_nr` smallint(6) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `bankkonto`
-- 

CREATE TABLE `bankkonto` (
  `id` int(11) NOT NULL auto_increment,
  `valuta` date NOT NULL default '0000-00-00',
  `kontoauszug_jahr` smallint(6) NOT NULL default '0',
  `kontoauszug_nr` smallint(6) NOT NULL default '0',
  `buchungsdatum` date NOT NULL default '0000-00-00',
  `dienstkontrollblatt_id` int(11) NOT NULL default '0',
  `betrag` decimal(10,2) NOT NULL default '0.00',
  `kommentar` text NOT NULL,
  `konto_id` smallint(6) NOT NULL default '0',
  `konterbuchung_id` int(11) NOT NULL default '0' COMMENT '>0:bank <0: gruppe',
  PRIMARY KEY  (`id`),
  KEY `secondary` (`konto_id`,`kontoauszug_jahr`,`kontoauszug_nr`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Bankkontotransaktionen';

-- --------------------------------------------------------

-- 
-- Table structure for table `bestellgruppen`
-- 

CREATE TABLE `bestellgruppen` (
  `id` int(11) NOT NULL default '0',
  `name` text NOT NULL,
  `passwort` text NOT NULL,
  `aktiv` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `bestellvorschlaege`
-- 

CREATE TABLE `bestellvorschlaege` (
  `produkt_id` int(11) NOT NULL default '0',
  `gesamtbestellung_id` int(11) NOT NULL default '0',
  `produktpreise_id` int(11) NOT NULL default '0',
  `liefermenge` decimal(10,3) default NULL,
  `bestellmenge` decimal(10,3) default NULL,
  PRIMARY KEY  (`gesamtbestellung_id`,`produkt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `bestellzuordnung`
-- 

CREATE TABLE `bestellzuordnung` (
  `id` int(11) NOT NULL auto_increment,
  `produkt_id` int(11) NOT NULL default '0',
  `gruppenbestellung_id` int(11) NOT NULL default '0',
  `menge` decimal(10,3) NOT NULL default '0.000',
  `art` tinyint(1) NOT NULL default '0',
  `zeitpunkt` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `secondary` (`art`,`produkt_id`,`gruppenbestellung_id`),
  KEY `nochnindex` (`produkt_id`,`gruppenbestellung_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='art = toleranz / fest / zugeteilt';

-- --------------------------------------------------------

-- 
-- Table structure for table `Dienste`
-- 

CREATE TABLE `Dienste` (
  `ID` int(11) NOT NULL auto_increment,
  `Dienst` enum('1/2','3','4','5','freigestellt') NOT NULL default '1/2',
  `Lieferdatum` date NOT NULL default '0000-00-00',
  `Status` enum('Vorgeschlagen','Akzeptiert','Bestaetigt','Geleistet','Nicht geleistet','Offen') NOT NULL default 'Vorgeschlagen',
  `Bemerkung` text,
  `gruppenmitglieder_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `GruppenID` (`Dienst`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='EnthÃƒÂ¤lt Dienste fÃƒÂ¼r jedes einzelne Lieferdatum';

-- --------------------------------------------------------

-- 
-- Table structure for table `dienstkontrollblatt`
-- 

CREATE TABLE `dienstkontrollblatt` (
  `id` int(11) NOT NULL auto_increment,
  `gruppen_id` int(11) NOT NULL default '0',
  `dienst` tinyint(1) NOT NULL default '0',
  `datum` date NOT NULL default '0000-00-00',
  `zeit` time NOT NULL default '00:00:00',
  `name` text NOT NULL,
  `telefon` text NOT NULL,
  `notiz` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secondary` (`dienst`,`gruppen_id`,`datum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `gesamtbestellungen`
-- 

CREATE TABLE `gesamtbestellungen` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `bestellstart` datetime default NULL,
  `bestellende` datetime default NULL,
  `ausgang` datetime default NULL,
  `lieferung` date default NULL,
  `bezahlung` datetime default NULL,
  `state` enum('bestellen','beimLieferanten','Verteilt','archiviert') NOT NULL default 'bestellen',
  `rechnungssumme` decimal(10,2) NOT NULL default '0.00' COMMENT 'wahre Rechnungssumme (kann wegen Pfand von berechneter abweichen!)',
  `abrechnung_dienstkontrollblatt_id` int(11) NOT NULL default '0',
  `rechnungsnummer` text NOT NULL COMMENT 'Rechnungsnummer des Lieferanten',
  `lieferanten_id` int(11) NOT NULL,
  `rechnungsstatus` smallint(6) NOT NULL,
  `extra_soll` decimal(10,2) NOT NULL,
  `extra_text` text NOT NULL,
  `abrechnung_datum` date NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `state` (`state`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='ausgang = Zeitpunkt der\nBestellung beim Lieferanten';

-- --------------------------------------------------------

-- 
-- Table structure for table `gruppenbestellungen`
-- 

CREATE TABLE `gruppenbestellungen` (
  `id` int(11) NOT NULL auto_increment,
  `bestellguppen_id` int(11) NOT NULL default '0',
  `gesamtbestellung_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secondary` (`gesamtbestellung_id`,`bestellguppen_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `gruppenmitglieder`
-- 

CREATE TABLE `gruppenmitglieder` (
  `id` int(11) NOT NULL auto_increment,
  `gruppen_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `vorname` text NOT NULL,
  `telefon` text NOT NULL,
  `email` text NOT NULL,
  `diensteinteilung` enum('1/2','3','4','5','freigestellt') NOT NULL default 'freigestellt',
  `rotationsplanposition` int(11) NOT NULL,
  `status` enum('aktiv','geloescht') NOT NULL default 'aktiv',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Mitglieder einer Foodcoopgruppe';

-- --------------------------------------------------------

-- 
-- Table structure for table `gruppenpfand`
-- 

CREATE TABLE `gruppenpfand` (
  `id` int(11) NOT NULL auto_increment,
  `bestell_id` int(11) NOT NULL,
  `gruppen_id` int(11) NOT NULL,
  `pfand_wert` decimal(6,2) NOT NULL,
  `anzahl_leer` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secondary` (`bestell_id`,`gruppen_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `gruppen_transaktion`
-- 

CREATE TABLE `gruppen_transaktion` (
  `id` int(11) NOT NULL auto_increment,
  `dienstkontrollblatt_id` int(11) NOT NULL default '0',
  `type` tinyint(1) NOT NULL default '0',
  `gruppen_id` int(11) NOT NULL default '0',
  `eingabe_zeit` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `summe` decimal(10,2) NOT NULL default '0.00',
  `notiz` text NOT NULL,
  `kontobewegungs_datum` date NOT NULL default '0000-00-00',
  `konterbuchung_id` int(11) NOT NULL default '0' COMMENT '>0:bank <0: gruppe',
  `lieferanten_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `secondary` (`gruppen_id`,`kontobewegungs_datum`),
  KEY `tertiary` (`lieferanten_id`,`kontobewegungs_datum`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `kategoriezuordnung`
-- 

CREATE TABLE `kategoriezuordnung` (
  `produkt_id` int(11) NOT NULL default '0',
  `kategorien_id` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `leitvariable`
-- 

CREATE TABLE `leitvariable` (
  `name` varchar(20) NOT NULL default '',
  `value` text NOT NULL,
  `local` tinyint(1) NOT NULL default '0',
  `comment` text NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `lieferanten`
-- 

CREATE TABLE `lieferanten` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `adresse` text NOT NULL,
  `ansprechpartner` text NOT NULL,
  `telefon` text NOT NULL,
  `fax` text NOT NULL,
  `mail` text NOT NULL,
  `liefertage` text NOT NULL,
  `bestellmodalitaeten` text NOT NULL,
  `url` text NOT NULL,
  `kundennummer` text NOT NULL,
  `sonstiges` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `lieferantenkatalog`
-- 

CREATE TABLE `lieferantenkatalog` (
  `id` int(11) NOT NULL auto_increment,
  `lieferanten_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `artikelnummer` bigint(20) NOT NULL,
  `bestellnummer` text NOT NULL,
  `liefereinheit` text NOT NULL,
  `gebinde` text NOT NULL,
  `mwst` decimal(4,2) NOT NULL,
  `pfand` decimal(6,2) NOT NULL,
  `verband` text NOT NULL,
  `herkunft` text NOT NULL,
  `preis` decimal(8,2) NOT NULL,
  `katalogdatum` text NOT NULL,
  `katalogtyp` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secondary` (`lieferanten_id`,`artikelnummer`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `lieferantenpfand`
-- 

CREATE TABLE `lieferantenpfand` (
  `id` int(11) NOT NULL auto_increment,
  `verpackung_id` int(11) NOT NULL,
  `bestell_id` int(11) NOT NULL,
  `anzahl_voll` int(11) NOT NULL default '0',
  `anzahl_leer` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secondary` (`bestell_id`,`verpackung_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `pfandverpackungen`
-- 

CREATE TABLE `pfandverpackungen` (
  `id` int(11) NOT NULL auto_increment,
  `lieferanten_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `wert` decimal(8,2) NOT NULL,
  `mwst` decimal(6,2) NOT NULL,
  `sort_id` int(11) NOT NULL COMMENT 'um Sortierung synchron mit den Papierzetteln zu halten',
  PRIMARY KEY  (`id`),
  KEY `sort_id` (`sort_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produkte`
-- 

CREATE TABLE `produkte` (
  `id` int(11) NOT NULL auto_increment,
  `artikelnummer` int(11) NOT NULL default '0',
  `name` text NOT NULL,
  `lieferanten_id` int(11) NOT NULL default '0',
  `produktgruppen_id` int(11) NOT NULL default '0',
  `einheit` text NOT NULL,
  `notiz` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produktgruppen`
-- 

CREATE TABLE `produktgruppen` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produktkategorien`
-- 

CREATE TABLE `produktkategorien` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produktpreise`
-- 

CREATE TABLE `produktpreise` (
  `id` int(11) NOT NULL auto_increment,
  `produkt_id` int(11) NOT NULL default '0',
  `preis` decimal(10,4) NOT NULL default '0.0000',
  `zeitstart` datetime NOT NULL default '0000-00-00 00:00:00',
  `zeitende` datetime default NULL,
  `bestellnummer` text NOT NULL,
  `liefereinheit` text NOT NULL,
  `gebindegroesse` int(11) NOT NULL default '0',
  `pfand` decimal(6,2) NOT NULL default '0.00',
  `mwst` decimal(4,2) NOT NULL default '0.00',
  `verteileinheit` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `secondary` (`produkt_id`,`zeitende`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='bestellnummer =\nlieferantenbestellnummer sagt zottel!';

-- --------------------------------------------------------

-- 
-- Table structure for table `transactions`
-- 

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL auto_increment,
  `used` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
