ALTER TABLE `bestellgruppen` ADD `diensteinteilung` ENUM( "1/2", "3", "4", "5", "freigestellt" ) NOT NULL DEFAULT 'freigestellt',
ADD `rotationsplanposition` INT NOT NULL DEFAULT '0';

ALTER TABLE `bestellgruppen` ADD INDEX ( `rotationsplanposition` ) ;

CREATE TABLE `Dienste` (
`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`GruppenID` INT NOT NULL ,
`Dienst` ENUM( "1/2", "3", "4", "5", "freigestellt" ) NOT NULL ,
`Lieferdatum` DATE NOT NULL ,
`Status` ENUM( "Vorgeschlagen", "Akzeptiert", "Bestätigt", "Geleistet", "Nicht geleistet", "Offen" ) NOT NULL ,
`Bemerkung` TEXT NULL ,
INDEX ( `GruppenID` , `Dienst` )
) ENGINE = MYISAM COMMENT = 'Enthält Dienste für jedes einzelne Lieferdatum Enthält Dienste für j. Lieferdat';

ALTER TABLE `gesamtbestellungen` CHANGE `lieferung` `lieferung` DATE NULL DEFAULT NULL  
