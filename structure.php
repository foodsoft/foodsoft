<?php

// db version 28

$tables = array(
  'bankkonten' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'kontonr' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'blz' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'url' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'kommentar' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'letzter_auszug_jahr' => array(
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'letzter_auszug_nr' => array(
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'bankkonto' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'valuta' => array(
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => '0000-00-00'
      , 'extra' => ''
      )
    , 'kontoauszug_jahr' => array(
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'kontoauszug_nr' => array(
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'buchungsdatum' => array(
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => '0000-00-00'
      , 'extra' => ''
      )
    , 'dienstkontrollblatt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'betrag' => array(
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      )
    , 'kommentar' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'konto_id' => array(
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'konterbuchung_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 0, 'collist' => 'konto_id, kontoauszug_jahr, kontoauszug_nr' )
    )
  )
, 'bestellgruppen' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
    )
    , 'passwort' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
    )
    , 'salt' => array(
        'type' =>  "char(8)"
      , 'null' => 'NO'
      , 'default' => '35464'
      , 'extra' => ''
    )
    , 'sockeleinlage' => array(
        'type' => "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
    )
    , 'aktiv' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'notiz_gruppe' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'bestellvorschlaege' => array(
    'updownload' => true
  , 'cols' => array(
      'produkt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'gesamtbestellung_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'produktpreise_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'liefermenge' => array(
        'type' =>  "decimal(10,3)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'gesamtbestellung_id, produkt_id' )
    )
  )
, 'bestellzuordnung' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'produkt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'gruppenbestellung_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'menge' => array(
        'type' =>  "decimal(10,3)"
      , 'null' => 'NO'
      , 'default' => '0.000'
      , 'extra' => ''
      )
    , 'art' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'zeitpunkt' => array(
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'CURRENT_TIMESTAMP'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 0, 'collist' => 'art, produkt_id, gruppenbestellung_id' )
      , 'nochnindex' => array( 'unique' => 0, 'collist' => 'produkt_id, gruppenbestellung_id' )
      , 'undnocheiner' => array( 'unique' => 0, 'collist' => 'art, gruppenbestellung_id' )
    )
  )
, 'dienste' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'dienstkontrollblatt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'dienst' => array(
        'type' =>  "enum('1/2','3','4','5','freigestellt')"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'lieferdatum' => array(
        'type' =>  "date"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'status' => array(
        'type' =>  "enum('Vorgeschlagen','Akzeptiert','Bestaetigt','Offen')"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'geleistet' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'bemerkung' => array(
        'type' =>  "text"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'gruppenmitglieder_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'gruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'mitglied' => array( 'unique' => 0, 'collist' => 'gruppenmitglieder_id' )
    )
  )
, 'dienstkontrollblatt' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'gruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'dienst' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'datum' => array(
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => '0000-00-00'
      , 'extra' => ''
      )
    , 'zeit' => array(
        'type' =>  "time"
      , 'null' => 'NO'
      , 'default' => '00:00:00'
      , 'extra' => ''
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'telefon' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'notiz' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 1, 'collist' => 'dienst, gruppen_id, datum' )
    )
  )
, 'gesamtbestellungen' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestellstart' => array(
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestellende' => array(
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'ausgang' => array(
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'lieferung' => array(
        'type' =>  "date"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bezahlung' => array(
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'rechnungssumme' => array(
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      )
    , 'abrechnung_dienstkontrollblatt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'rechnungsnummer' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'lieferanten_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'rechnungsstatus' => array(
        'type' =>  "smallint(6)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'extra_soll' => array(
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'extra_text' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'aufschlag_prozent' => array(
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'abrechnung_datum' => array(
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'abrechnung_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'rechnungsstatus' => array( 'unique' => 0, 'collist' => 'rechnungsstatus' )
    )
  )
, 'gruppenbestellungen' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'bestellgruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'gesamtbestellung_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 1, 'collist' => 'gesamtbestellung_id, bestellgruppen_id' )
      , 'gruppe' => array( 'unique' => 0, 'collist' => 'bestellgruppen_id' )
    )
  )
, 'gruppenmitglieder' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'gruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'vorname' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'telefon' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'email' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'diensteinteilung' => array(
        'type' =>  "enum('1/2','3','4','5','freigestellt')"
      , 'null' => 'NO'
      , 'default' => 'freigestellt'
      , 'extra' => ''
      )
    , 'rotationsplanposition' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'sockeleinlage' => array(
        'type' => "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      )
    , 'aktiv' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'slogan' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'url' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'photo_url' => array(
        'type' => 'mediumtext'
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'notiz' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'gruppe' => array( 'unique' => 0, 'collist' => 'gruppen_id' )
      , 'rotationsplan' => array( 'unique' => 1, 'collist' => 'rotationsplanposition' )
    )
  )
, 'gruppenpfand' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'bestell_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'gruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'pfand_wert' => array(
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'anzahl_leer' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 1, 'collist' => 'bestell_id, gruppen_id' )
      , 'gruppe' => array( 'unique' => 0, 'collist' => 'gruppen_id' )
    )
  )
, 'gruppen_transaktion' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'dienstkontrollblatt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'type' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'gruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'eingabe_zeit' => array(
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'CURRENT_TIMESTAMP'
      , 'extra' => ''
      )
    , 'summe' => array(
        'type' =>  "decimal(10,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      )
    , 'notiz' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'valuta' => array(
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => '0000-00-00'
      , 'extra' => ''
      )
    , 'konterbuchung_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'lieferanten_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 0, 'collist' => 'gruppen_id, valuta' )
      , 'tertiary' => array( 'unique' => 0, 'collist' => 'lieferanten_id, valuta' )
    )
  )
, 'leitvariable' => array(
    'updownload' => false  // leitvariable werden gesondert behandelt!
  , 'cols' => array(
      'name' => array(
        'type' =>  'varchar(30)'
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'value' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'comment' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'name' )
    )
  )
, 'lieferanten' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'strasse' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'ort' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'ansprechpartner' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'telefon' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'fax' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'mail' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'grussformel' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'anrede' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'fc_name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'fc_strasse' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'fc_ort' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'liefertage' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestellmodalitaeten' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'url' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'kundennummer' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'sonstiges' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'katalogformat' => array(
        'type' => 'varchar(20)'
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestellfaxspalten' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '534541'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'lieferantenkatalog' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'lieferanten_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'artikelnummer' => array(
        'type' =>  "bigint(20)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestellnummer' => array(
        'type' =>  "bigint(20)"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'liefereinheit' => array(
        'type' =>  'varchar(20)'
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'gebinde' => array(
        'type' =>  "decimal(8,3)"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'mwst' => array(
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'pfand' => array(
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'verband' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'herkunft' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'preis' => array(
        'type' =>  "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'katalogdatum' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'katalogtyp' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'katalogformat' => array(
        'type' => 'varchar(20)'
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'gueltig' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '1'
      , 'extra' => ''
      )
    , 'hersteller' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
    )
    , 'bemerkung' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
    )
    , 'ean_einzeln' => array(
        'type' => "varchar(15)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => '' 
    )
  )
  , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 1, 'collist' => 'lieferanten_id, artikelnummer' )
  )
)
, 'lieferantenpfand' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'verpackung_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'bestell_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'anzahl_voll' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'anzahl_leer' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 1, 'collist' => 'bestell_id, verpackung_id' )
    )
  )
, 'logbook' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'session_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'time_stamp' => array(
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'CURRENT_TIMESTAMP'
      , 'extra' => ''
      )
    , 'notiz' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'pfandverpackungen' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'lieferanten_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'wert' => array(
        'type' =>  "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'mwst' => array(
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'sort_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'sort_id' => array( 'unique' => 0, 'collist' => 'lieferanten_id, sort_id' )
    )
  )
, 'produkte' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'artikelnummer' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'lieferanten_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'produktgruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'notiz' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'dauerbrenner' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'produktgruppen' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'name' => array(
        'type' =>  "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'produktpreise' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'produkt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'lieferpreis' => array(
        'type' =>  "decimal(12,4)"
      , 'null' => 'NO'
      , 'default' => '0.0'
      , 'extra' => ''
      )
    , 'zeitstart' => array(
        'type' =>  "datetime"
      , 'null' => 'NO'
      , 'default' => '0000-00-00 00:00:00'
      , 'extra' => ''
      )
    , 'zeitende' => array(
        'type' =>  "datetime"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestellnummer' => array(
        'type' =>  'varchar(20)'
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'pfand' => array(
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      )
    , 'mwst' => array(
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => '0.00'
      , 'extra' => ''
      )
    , 'verteileinheit' => array(
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => '1 ST'
      , 'extra' => ''
      )
    , 'gebindegroesse' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '1'
      , 'extra' => ''
      )
    , 'liefereinheit' => array(
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => '1 ST'
      , 'extra' => ''
      )
    , 'lv_faktor' => array(
        'type' =>  "decimal(12,6)"
      , 'null' => 'NO'
      , 'default' => '1.0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 0, 'collist' => 'produkt_id, zeitende' )
    )
  )
, 'sessions' => array(
    'updownload' => true
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'cookie' => array(
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'login_gruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'dienst' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'dienstkontrollblatt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'session_timestamp' => array(
        'type' =>  "timestamp"
      , 'null' => 'NO'
      , 'default' => 'CURRENT_TIMESTAMP'
      , 'extra' => ''
      )
    , 'muteReconfirmation_timestamp' => array(
        'type' => "timestamp"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'transactions' => array(
    'updownload' => false
  , 'cols' => array(
      'id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'used' => array(
        'type' =>  "tinyint(1)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    , 'itan' => array(
        'type' =>  "varchar(10)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'session_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => '0'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    )
  )
, 'catalogue_acronyms' => array(
    'updownload' => true
  , 'cols'=> array(
      'id' => array(
        'type' => "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'context' => array(
        'type' => "varchar(10)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'acronym' => array(
        'type' => "varchar(10)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'definition' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'comment' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'url' => array(
        'type' => "text"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
  , 'indices' => array(
      'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
    , 'secondary' => array( 'unique' => 1, 'collist' => 'context, acronym')
  )
)
);

?>
