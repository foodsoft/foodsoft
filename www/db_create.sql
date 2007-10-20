-- phpMyAdmin SQL Dump
-- version 2.9.2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Sep 26, 2007 at 01:53 PM
-- Server version: 4.1.11
-- PHP Version: 5.1.4-Debian-0.1~sarge1
-- 
-- Database: `nahrungskette`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `Dienste`
-- 

DROP TABLE IF EXISTS `Dienste`;
CREATE TABLE `Dienste` (
  `ID` int(11) NOT NULL auto_increment,
  `GruppenID` int(11) NOT NULL default '0',
  `Dienst` enum('1/2','3','4','5','freigestellt') NOT NULL default '1/2',
  `Lieferdatum` date NOT NULL default '0000-00-00',
  `Status` enum('Vorgeschlagen','Akzeptiert','Bestaetigt','Geleistet','Nicht geleistet','Offen') NOT NULL default 'Vorgeschlagen',
  `Bemerkung` text,
  PRIMARY KEY  (`ID`),
  KEY `GruppenID` (`GruppenID`,`Dienst`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Enthält Dienste für jedes einzelne Lieferdatum';

-- --------------------------------------------------------

-- 
-- Table structure for table `bestellgruppen`
-- 

DROP TABLE IF EXISTS `bestellgruppen`;
CREATE TABLE `bestellgruppen` (
  `id` int(11) NOT NULL default '0',
  `name` text NOT NULL,
  `ansprechpartner` text NOT NULL,
  `telefon` text NOT NULL,
  `email` text NOT NULL,
  `mitgliederzahl` smallint(4) NOT NULL,
  `passwort` text NOT NULL,
  `aktiv` tinyint(1) NOT NULL default '0',
  `diensteinteilung` enum('1/2','3','4','5','freigestellt') NOT NULL default 'freigestellt',
  `rotationsplanposition` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `rotationsplanposition` (`rotationsplanposition`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `bestellvorschlaege`
-- 

DROP TABLE IF EXISTS `bestellvorschlaege`;
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

DROP TABLE IF EXISTS `bestellzuordnung`;
CREATE TABLE `bestellzuordnung` (
  `id` int(11) NOT NULL auto_increment,
  `produkt_id` int(11) NOT NULL default '0',
  `gruppenbestellung_id` int(11) NOT NULL default '0',
  `menge` decimal(10,3) NOT NULL default '0.000',
  `art` tinyint(1) NOT NULL default '0',
  `zeitpunkt` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `secondary` (`art`,`produkt_id`,`gruppenbestellung_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
  COMMENT='art = toleranz / fest / zugeteilt';

-- --------------------------------------------------------

-- 
-- Table structure for table `dienstkontrollblatt`
-- 

DROP TABLE IF EXISTS `dienstkontrollblatt`;
CREATE TABLE `dienstkontrollblatt` (
  `id` int(11) NOT NULL auto_increment,
  `gruppen_id` int(11) NOT NULL,
  `dienst` tinyint(1) NOT NULL default '0',
  `datum` date NOT NULL default '0000-00-00',
  `zeit` time NOT NULL default '00:00:00',
  `name` text NOT NULL,
  `telefon` text NOT NULL,
  `notiz` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secondary` (`dienst`,`gruppen_id`,`datum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `dienstquiz`
-- 

DROP TABLE IF EXISTS `dienstquiz`;

-- 
-- Table structure for table `gesamtbestellungen`
-- 

DROP TABLE IF EXISTS `gesamtbestellungen`;
CREATE TABLE `gesamtbestellungen` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `bestellstart` datetime default NULL,
  `bestellende` datetime default NULL,
  `ausgang` datetime default NULL,
  `lieferung` date default NULL,
  `bezahlung` datetime default NULL,
  `state` enum('bestellen','beimLieferanten','Verteilt','archiviert') NOT NULL default 'bestellen',
  PRIMARY KEY  (`id`),
  KEY `state` (`state`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='ausgang = Zeitpunkt der\nBestellung beim Lieferanten';

-- --------------------------------------------------------

-- 
-- Table structure for table `gruppen_transaktion`
-- 

DROP TABLE IF EXISTS `gruppen_transaktion`;
CREATE TABLE `gruppen_transaktion` (
  `id` int(11) NOT NULL auto_increment,
  `kontoauszugs_jahr` smallint(4) unsigned NOT NULL,
  `dienstkontrollblatt_id` int(11) NOT NULL default '0',
  `type` tinyint(1) NOT NULL default '0',
  `gruppen_id` int(11) NOT NULL default '0',
  `eingabe_zeit` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `summe` decimal(10,2) NOT NULL default '0.00',
  `kontoauszugs_nr` int(11) NOT NULL default '0',
  `notiz` text NOT NULL,
  `kontobewegungs_datum` date NOT NULL default '0000-00-00',
  `bankkonto_id` INT NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `secondary` (`gruppen_id`,`kontobewegungs_datum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `gruppenbestellungen`
-- 

DROP TABLE IF EXISTS `gruppenbestellungen`;
CREATE TABLE `gruppenbestellungen` (
  `id` int(11) NOT NULL auto_increment,
  `bestellguppen_id` int(11) NOT NULL default '0',
  `gesamtbestellung_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secondary` (`gesamtbestellung_id`,`bestellguppen_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `kategoriezuordnung`
-- 

DROP TABLE IF EXISTS `kategoriezuordnung`;
CREATE TABLE `kategoriezuordnung` (
  `produkt_id` int(11) NOT NULL default '0',
  `kategorien_id` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `leitvariable`
-- 

DROP TABLE IF EXISTS `leitvariable`;
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

DROP TABLE IF EXISTS `lieferanten`;
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produkte`
-- 

DROP TABLE IF EXISTS `produkte`;
CREATE TABLE `produkte` (
  `id` int(11) NOT NULL auto_increment,
  `artikelnummer` int(11) NOT NULL default '0',
  `name` text NOT NULL,
  `lieferanten_id` int(11) NOT NULL default '0',
  `produktgruppen_id` int(11) NOT NULL default '0',
  `einheit` text NOT NULL,
  `notiz` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produktgruppen`
-- 

DROP TABLE IF EXISTS `produktgruppen`;
CREATE TABLE `produktgruppen` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produktkategorien`
-- 

DROP TABLE IF EXISTS `produktkategorien`;
CREATE TABLE `produktkategorien` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

-- 
-- Table structure for table `produktpreise`
-- 

--
-- preis braucht 4 nachkommestellen, um netto * (1.07) (MWSt) exakt darzustellen!
--
DROP TABLE IF EXISTS `produktpreise`;
CREATE TABLE `produktpreise` (
  `id` int(11) NOT NULL auto_increment,
  `produkt_id` int(11) NOT NULL default '0',
  `preis` decimal(10,4) NOT NULL default '0',
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='bestellnummer =\nlieferantenbestellnummer sagt zottel!';


CREATE TABLE `bankkonto` (
 `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
 `kontoauszug_jahr` SMALLINT NOT NULL, 
 `kontoauszug_nr` SMALLINT NOT NULL, 
 `eingabedatum` DATE NOT NULL, 
 `gruppen_id` INT NOT NULL,
 `lieferanten_id` INT NOT NULL,
 `dienstkontrollblatt_id` INT NOT NULL,
 `betrag` DECIMAL(10,2) NOT NULL,
 `konto_id` smallint(4) NOT NULL,
 `kommentar` TEXT NOT NULL
  PRIMARY KEY  (`id`),
  KEY `secondary` ( `konto_id`, `kontoauszug_jahr`,`kontoauszug_nr`)
 )
 ENGINE = myisam DEFAULT CHARACTER SET utf8 COMMENT = 'Bankkontotransaktionen';

CREATE TABLE `bankkonten` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` TEXT NOT NULL ,
`kontonr` TEXT NOT NULL ,
`blz` TEXT NOT NULL
) ENGINE = MYISAM ;

