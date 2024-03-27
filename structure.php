<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.


// db version 20000

$tables = [
  'bankkonten' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'kontonr' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'blz' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'url' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'kommentar' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'letzter_auszug_jahr' => [
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'letzter_auszug_nr' => [
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'buchungsregeln' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    ]
  ]
, 'bankkonto' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'valuta' => [
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'kontoauszug_jahr' => [
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'kontoauszug_nr' => [
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'buchungsdatum' => [
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'dienstkontrollblatt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'betrag' => [
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'kommentar' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'konto_id' => [
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'konterbuchung_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 0, 'collist' => 'konto_id, kontoauszug_jahr, kontoauszug_nr' ]
    ]
  ]
, 'bestellgruppen' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'passwort' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'salt' => [
        'type' =>  "char(8)"
      , 'null' => 'NO'
      , 'default' => '35464'
      , 'extra' => ''
      ]
    , 'sockeleinlage' => [
        'type' =>  "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'aktiv' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'notiz_gruppe' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'buchungsregeln' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    ]
  ]
, 'bestellvorschlaege' => [
    'updownload' => true
  , 'cols' => [
      'produkt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'gesamtbestellung_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'produktpreise_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'liefermenge' => [
        'type' =>  "decimal(10,3)"
      , 'null' => 'NO'
      , 'default' => '0.000'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'gesamtbestellung_id, produkt_id' ]
    , 'by_produkt_id' => [ 'unique' => 0, 'collist' => 'produkt_id' ]
    ]
  ]
, 'bestellzuordnung' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'produkt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'gruppenbestellung_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'menge' => [
        'type' =>  "decimal(10,3)"
      , 'null' => 'NO'
      , 'default' => '0.000'
      , 'extra' => ''
      ]
    , 'art' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'zeitpunkt' => [
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'current_timestamp()'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 0, 'collist' => 'art, produkt_id, gruppenbestellung_id' ]
    , 'nochnindex' => [ 'unique' => 0, 'collist' => 'produkt_id, gruppenbestellung_id' ]
    , 'undnocheiner' => [ 'unique' => 0, 'collist' => 'art, gruppenbestellung_id' ]
    ]
  ]
, 'catalogue_acronyms' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'context' => [
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'acronym' => [
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'definition' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'comment' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'url' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 1, 'collist' => 'context, acronym' ]
    ]
  ]
, 'dienste' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'dienstkontrollblatt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'dienst' => [
        'type' =>  "enum('1/2','3','4','5','6','freigestellt')"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'lieferdatum' => [
        'type' =>  "date"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'status' => [
        'type' =>  "enum('Vorgeschlagen','Akzeptiert','Bestaetigt','Offen')"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'geleistet' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'bemerkung' => [
        'type' =>  "text"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'gruppenmitglieder_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'gruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'mitglied' => [ 'unique' => 0, 'collist' => 'gruppenmitglieder_id' ]
    ]
  ]
, 'dienstkontrollblatt' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'gruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'dienst' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'datum' => [
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'zeit' => [
        'type' =>  "time"
      , 'null' => 'NO'
      , 'default' => '00:00:00'
      , 'extra' => ''
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'telefon' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'notiz' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 1, 'collist' => 'dienst, gruppen_id, datum' ]
    ]
  ]
, 'gesamtbestellungen' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bestellstart' => [
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bestellende' => [
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'ausgang' => [
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'lieferung' => [
        'type' =>  "date"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bezahlung' => [
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'rechnungssumme' => [
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'abrechnung_dienstkontrollblatt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'rechnungsnummer' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'lieferanten_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'rechnungsstatus' => [
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'extra_soll' => [
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'extra_text' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'aufschlag_prozent' => [
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'abrechnung_datum' => [
        'type' =>  "date"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'abrechnung_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'rechnungsstatus' => [ 'unique' => 0, 'collist' => 'rechnungsstatus' ]
    , 'abrechnung_id' => [ 'unique' => 0, 'collist' => 'abrechnung_id' ]
    ]
  ]
, 'gruppen_transaktion' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'dienstkontrollblatt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'type' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'gruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'eingabe_zeit' => [
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'current_timestamp()'
      , 'extra' => ''
      ]
    , 'summe' => [
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'notiz' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'valuta' => [
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'konterbuchung_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'lieferanten_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 0, 'collist' => 'gruppen_id, valuta' ]
    , 'tertiary' => [ 'unique' => 0, 'collist' => 'lieferanten_id, valuta' ]
    ]
  ]
, 'gruppenbestellungen' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'bestellgruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'gesamtbestellung_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 1, 'collist' => 'gesamtbestellung_id, bestellgruppen_id' ]
    , 'gruppe' => [ 'unique' => 0, 'collist' => 'bestellgruppen_id' ]
    ]
  ]
, 'gruppenmitglieder' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'gruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'vorname' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'telefon' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'email' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'diensteinteilung' => [
        'type' =>  "enum('1/2','3','4','5','6','freigestellt')"
      , 'null' => 'NO'
      , 'default' => 'freigestellt'
      , 'extra' => ''
      ]
    , 'rotationsplanposition' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'sockeleinlage' => [
        'type' =>  "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'aktiv' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'slogan' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'url' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'photo_url' => [
        'type' =>  "mediumtext"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'notiz' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'rotationsplan' => [ 'unique' => 1, 'collist' => 'rotationsplanposition' ]
    , 'gruppe' => [ 'unique' => 0, 'collist' => 'gruppen_id' ]
    ]
  ]
, 'gruppenpfand' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'bestell_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'gruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'pfand_wert' => [
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'anzahl_leer' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 1, 'collist' => 'bestell_id, gruppen_id' ]
    , 'gruppe' => [ 'unique' => 0, 'collist' => 'gruppen_id' ]
    ]
  ]
, 'leitvariable' => [
    'updownload' => false
  , 'cols' => [
      'name' => [
        'type' =>  "varchar(30)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'value' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'comment' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'name' ]
    ]
  ]
, 'lieferanten' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'strasse' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'ort' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'ansprechpartner' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'telefon' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'fax' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'mail' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'grussformel' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'anrede' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'fc_name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'fc_strasse' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'fc_ort' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'liefertage' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bestellmodalitaeten' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'url' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'kundennummer' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'sonstiges' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    , 'katalogformat' => [
        'type' =>  "varchar(20)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bestellfaxspalten' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '534541'
      , 'extra' => ''
      ]
    , 'katalogaufschlag' => [
        'type' =>  "decimal(5,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'gruppenpfand' => [
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => '0.16'
      , 'extra' => ''
      ]
    , 'katalogaufschlagrunden' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '1'
      , 'extra' => ''
      ]
    , 'distribution_druck_preisspalte' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'buchungsregeln' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    ]
  ]
, 'lieferantenkatalog' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'lieferanten_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'artikelnummer' => [
        'type' =>  "bigint(20)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bestellnummer' => [
        'type' =>  "bigint(20)"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'liefereinheit' => [
        'type' =>  "varchar(20)"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'gebinde' => [
        'type' =>  "decimal(8,3)"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'mwst' => [
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'pfand' => [
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'verband' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'herkunft' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'preis' => [
        'type' =>  "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'katalogdatum' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'katalogtyp' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'katalogformat' => [
        'type' =>  "varchar(20)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'gueltig' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '1'
      , 'extra' => ''
      ]
    , 'hersteller' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bemerkung' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'ean_einzeln' => [
        'type' =>  "varchar(15)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 1, 'collist' => 'lieferanten_id, artikelnummer' ]
    ]
  ]
, 'lieferantenpfand' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'verpackung_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'bestell_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'anzahl_voll' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'anzahl_leer' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 1, 'collist' => 'bestell_id, verpackung_id' ]
    ]
  ]
, 'logbook' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'session_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'time_stamp' => [
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'current_timestamp()'
      , 'extra' => ''
      ]
    , 'notiz' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    ]
  ]
, 'pfandverpackungen' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'lieferanten_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'wert' => [
        'type' =>  "decimal(8,4)"
      , 'null' => 'NO'
      , 'default' => '0.0000'
      , 'extra' => ''
      ]
    , 'mwst' => [
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'sort_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'sort_id' => [ 'unique' => 0, 'collist' => 'lieferanten_id, sort_id' ]
    ]
  ]
, 'produkte' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'artikelnummer' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'lieferanten_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'produktgruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'notiz' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'dauerbrenner' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'by_lieferanten_id' => [ 'unique' => 0, 'collist' => 'lieferanten_id' ]
    ]
  ]
, 'produktgruppen' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'name' => [
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    ]
  ]
, 'produktpreise' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'produkt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'lieferpreis' => [
        'type' =>  "decimal(12,4)"
      , 'null' => 'NO'
      , 'default' => '0.0000'
      , 'extra' => ''
      ]
    , 'zeitstart' => [
        'type' =>  "datetime"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'zeitende' => [
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'bestellnummer' => [
        'type' =>  "varchar(20)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'pfand' => [
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'mwst' => [
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      ]
    , 'verteileinheit' => [
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => '1 ST'
      , 'extra' => ''
      ]
    , 'gebindegroesse' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '1'
      , 'extra' => ''
      ]
    , 'liefereinheit' => [
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => '1 ST'
      , 'extra' => ''
      ]
    , 'lv_faktor' => [
        'type' =>  "decimal(12,6)"
      , 'null' => 'NO'
      , 'default' => '1.000000'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    , 'secondary' => [ 'unique' => 0, 'collist' => 'produkt_id, zeitende' ]
    , 'by_zeitstart' => [ 'unique' => 0, 'collist' => 'produkt_id, zeitstart' ]
    ]
  ]
, 'sessions' => [
    'updownload' => true
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'cookie' => [
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'login_gruppen_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'dienst' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'dienstkontrollblatt_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'session_timestamp' => [
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'current_timestamp()'
      , 'extra' => ''
      ]
    , 'muteReconfirmation_timestamp' => [
        'type' =>  "timestamp"
      , 'null' => 'YES'
      , 'default' => null
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    ]
  ]
, 'transactions' => [
    'updownload' => false
  , 'cols' => [
      'id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => 'auto_increment'
      ]
    , 'used' => [
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    , 'itan' => [
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => null
      , 'extra' => ''
      ]
    , 'session_id' => [
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      ]
    ]
  , 'indices' => [
      'PRIMARY' => [ 'unique' => 1, 'collist' => 'id' ]
    ]
  ]
];
?>
