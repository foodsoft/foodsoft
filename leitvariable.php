<?php
$leitvariable = array(
  'foodcoop_name' => array(
    'meaning' => 'Name der Foodcoop'
  , 'default' => 'Nahrungskette'
  , 'local' => false
  , 'comment' => 'Dient nur zur Information, wird im Seitenkopf der Webseiten angezeigt'
  , 'runtime_editable' => 1
  , 'cols' => '40'
  )
, 'motd' => array(
    'meaning' => '"message of the day": wird auf der Login-Seite (vor dem login, also oeffentilich!) angezeigt'
  , 'default' => 'Willkommen bei der Nahrungskette!'
  , 'local' => false
  , 'comment' => 'Hier kann beliebiger text, einschliessliche einfacher HTML-Formatierung, eingegeben werden'
  , 'runtime_editable' => 1
  , 'cols' => '60', 'rows' => 5
  )
, 'bulletinboard' => array(
    'meaning' => '"Schwarzes Brett": wird auf der Startseite angezeigt (neben Hauptmenue, nach dem Login!)'
  , 'default' => 'Aktuelle Neuigkeiten'
  , 'local' => false
  , 'comment' => 'Hier kann beliebiger text (kein html) eingegeben werden'
  , 'runtime_editable' => 1
  , 'cols' => '30', 'rows' => 5
  )
, 'member_showcase_count' => array(
    'meaning' => 'Anzahl an Mitgliedern, die auf der Startseite angezeigt werden (neben Schwarzem Brett)'  
  , 'default' => '3'
  , 'local' => false
  , 'comment' => '0, um ganz abzuschalten'
  , 'runtime_editable' => 1
  , 'cols' => '2'
  )
, 'member_showcase_title' => array(
    'meaning' => 'Titel über Mitgliedern, die auf der Startseite angezeigt werden (neben Schwarzem Brett)'  
  , 'default' => '<b>Ein paar von uns</b>'
  , 'local' => false
  , 'comment' => 'Beliebiger Text mit einfachem HTML'
  , 'runtime_editable' => 1
  , 'cols' => '60', 'rows' => 1
  )
, 'exportDB' => array(
    'meaning' => 'Export der datenbank erlauben'
  , 'default' => '0'
  , 'local' => true
  , 'comment' => 'Flag (1 oder 0), um Dienst 4 den Export der Datenbank zu erlauben'
  , 'runtime_editable' => 1
  , 'cols' => '1'
  )
, 'readonly' => array(
    'meaning' => 'Datenbank schreibgeschuetzt setzen (einige sehr eingeschränkte Schreibzugriffe sind dennoch moeglich)'
  , 'default' => '0'
  , 'local' => true
  , 'comment' => 'Flag (1 oder 0), um &Auml;nderungen an der Datenbank, etwa w&auml;hrend offline-Betrieb auf
                  einem anderen Rechner, zu verhindern'
  , 'runtime_editable' => 1
  , 'cols' => '1'
  )
, 'demoserver' => array(
    'meaning' => 'oeffentlicher Demo-Server (mit eingeschraenkten Funktionen)'
  , 'default' => '0'
  , 'local' => false
  , 'comment' => 'Flag (1 oder 0): unterbindet auf oeffentlichen Servern die Unterstuetzung fuer Lieferanten-Kataloge (aus rechtlichen Gruenden)'
  , 'runtime_editable' => 1
  , 'cols' => '1'
  )
, 'foodsoftserver' => array(
    'meaning' => 'Spitzname des Servers'
  , 'default' => '(noch namenlos)'
  , 'local' => true
  , 'comment' => 'Dient nur zur Information und wird in der Fusszeile der Webseiten angezeigt'
  , 'runtime_editable' => 1
  , 'cols' => '40'
  )
, 'sockelbetrag_gruppe' => array(
    'meaning' => 'Sockeleinlage in Euro pro Gruppe'
  , 'comment' => '"Gesch&auml;ftsanteil", mit dem sich jede Gruppe am Eigenkapital der FoodCoop beteiligt'
  , 'default' => '0.0'
  , 'local' => false
  , 'runtime_editable' => 1
  , 'cols' => '8'
  )
, 'sockelbetrag_mitglied' => array(
    'meaning' => 'Sockeleinlage in Euro pro Gruppenmitglied'
  , 'comment' => '"Gesch&auml;ftsanteil", mit dem sich jedes Mitglied am Eigenkapital der FoodCoop beteiligt'
  , 'default' => '6.00'
  , 'local' => false
  , 'runtime_editable' => 1
  , 'cols' => '8'
  )
, 'aufschlag_default' => array(
    'meaning' => 'Preisaufschlag zur Kostendeckung'
  , 'comment' => 'Prozentualer Aufschlag auf alle Preise zur Deckung der Selbstkosten der Foodcoop'
  , 'default' => '0.00'
  , 'local' => false
  , 'runtime_editable' => 1
  , 'cols' => '8'
  )
, 'mwst_default' => array(
    'meaning' => 'Default-MWSt-Satz'
  , 'comment' => 'H&auml;ufigster Mehrwertsteuer-Satz in Prozent'
  , 'default' => '7.00'
  , 'local' => false
  , 'runtime_editable' => 1
  , 'cols' => '8'
  )
, 'toleranz_default' => array(
    'meaning' => 'Default-Toleranz-Satz'
  , 'comment' => 'automatischer Toleranzzuschlag in Prozent bei Bestellungen (kann im Einzelfall manuell runtergesetzt werden)'
  , 'default' => '0.00'
  , 'local' => false
  , 'runtime_editable' => 1
  , 'cols' => '8'
  )
, 'muell_id' => array(
    'meaning' => 'Nummer der speziellen Bad-Bank-Gruppe (traditionell: 13)'
  , 'default' => '13'
  , 'comment' => 'Diese spezielle Gruppe &uuml;bernimmt Verluste der FoodCoop
                   (steht also f&uuml;r die Gemeinschaft aller Mitglieder, die irgendwann per Umlage f&uuml;r die Verluste aufkommen m&uuml;ssen)'
  , 'local' => false
  , 'runtime_editable' => 0
  , 'cols' => '2'
  )
, 'basar_id' => array(
    'meaning' => 'Nummer der speziellen Basar-Gruppe (traditionell: 99)'
  , 'default' => '99'
  , 'comment' => 'Diese spezielle Gruppe bestellt Waren f&uuml;r den Basar'
  , 'local' => false
  , 'runtime_editable' => 0
  , 'cols' => '2'
  )
, 'database_version' => array(
    'meaning' => 'Version der Datenbank'
  , 'default' => '31'
  , 'comment' => 'Bitte den vorgeschlagenen Wert &uuml;bernehmen und nicht manuell &auml;ndern: diese Variable wird bei Upgrades automatisch hochgesetzt!'
  , 'local' => false
  , 'runtime_editable' => 0
  , 'cols' => '3'
  )
);
//
// currently unused:
//
// , 'usb_device' => array(
//     'meaning' => 'device in /dev des USB-sticks (fuer lokalen up/download ohne Netz)'
//   , 'default' => false
//   , 'comment' => 'zur Zeit unbenutzt: vorgesehen fuer offline-Betrieb auf lokalem PC: das device des USB-Sticks; auf Server: false'
//   , 'runtime_editable' => 1
//   , 'local' => true
//   , 'cols' => '20'
//   )
?>
