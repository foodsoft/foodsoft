<?

// db version 11

$tables = array(
  'Dienste' => array(
    'updownload' => true
  , 'cols' => array(
      'ID' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => 'auto_increment'
      )
    , 'dienstkontrollblatt_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'Dienst' => array(
        'type' =>  "enum('1/2','3','4','5','freigestellt')"
      , 'null' => 'NO'
      , 'default' => '1/2'
      , 'extra' => ''
      )
    , 'Lieferdatum' => array(
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => '0000-00-00'
      , 'extra' => ''
      )
    , 'Status' => array(
        'type' =>  "enum('Vorgeschlagen','Akzeptiert','Bestaetigt','Geleistet','Nicht geleistet','Offen')"
      , 'null' => 'NO'
      , 'default' => 'Vorgeschlagen'
      , 'extra' => ''
      )
    , 'Bemerkung' => array(
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
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'ID' )
      , 'GruppenID' => array( 'unique' => 0, 'collist' => 'Dienst' )
    )
  )
, 'bankkonten' => array(
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
    , 'aktiv' => array(
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
      , 'null' => 'YES'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestellmenge' => array(
        'type' =>  "decimal(10,3)"
      , 'null' => 'YES'
      , 'default' => ''
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
    , 'state' => array(
        'type' =>  "enum('bestellen','beimLieferanten','Verteilt','archiviert')"
      , 'null' => 'NO'
      , 'default' => 'bestellen'
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
    , 'abrechnung_datum' => array(
        'type' =>  "date"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'state' => array( 'unique' => 0, 'collist' => 'state' )
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
    , 'kontobewegungs_datum' => array(
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
      , 'secondary' => array( 'unique' => 0, 'collist' => 'gruppen_id, kontobewegungs_datum' )
      , 'tertiary' => array( 'unique' => 0, 'collist' => 'lieferanten_id, kontobewegungs_datum' )
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
    , 'bestellguppen_id' => array(
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
      , 'secondary' => array( 'unique' => 1, 'collist' => 'gesamtbestellung_id, bestellguppen_id' )
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
      , 'default' => ''
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
    , 'status' => array(
        'type' =>  "enum('aktiv','geloescht')"
      , 'null' => 'NO'
      , 'default' => 'aktiv'
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
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
      , 'default' => ''
      , 'extra' => ''
      )
    , 'gruppen_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'pfand_wert' => array(
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'anzahl_leer' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'secondary' => array( 'unique' => 1, 'collist' => 'bestell_id, gruppen_id' )
    )
  )
, 'leitvariable' => array(
    'updownload' => false  // leitvariable werden gesondert behandelt!
  , 'cols' => array(
      'name' => array(
        'type' =>  "varchar(20)"
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
    , 'adresse' => array(
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
      , 'default' => ''
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
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'liefereinheit' => array(
        'type' =>  "varchar(20)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'gebinde' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'mwst' => array(
        'type' =>  "decimal(4,2)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'pfand' => array(
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => ''
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
    , 'lieferpreis' => array(
        'type' =>  "decimal(8,2)"
      , 'null' => 'NO'
      , 'default' => ''
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
      , 'default' => ''
      , 'extra' => ''
      )
    , 'bestell_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
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
      , 'default' => ''
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
      , 'default' => ''
      , 'extra' => ''
      )
    , 'mwst' => array(
        'type' =>  "decimal(6,2)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    , 'sort_id' => array(
        'type' =>  "int(11)"
      , 'null' => 'NO'
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
      , 'sort_id' => array( 'unique' => 1, 'collist' => 'sort_id' )
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
    , 'einheit' => array(
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
    , 'preis' => array(
        'type' =>  "decimal(10,4)"
      , 'null' => 'NO'
      , 'default' => '0.0000'
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
        'type' =>  "varchar(20)"
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
        'type' =>  "decimal(9,3)"
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
      , 'default' => ''
      , 'extra' => ''
      )
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
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
    )
    , 'indices' => array(
        'PRIMARY' => array( 'unique' => 1, 'collist' => 'id' )
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
      , 'default' => ''
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
);

?>
