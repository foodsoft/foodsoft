<?
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
    'meaning' => '"message of the day": wird auf der Startseite angezeigt'
  , 'default' => 'Willkommen bei der Nahrungskette!'
  , 'local' => false
  , 'comment' => 'Hier kann beliebiger text, einschliessliche einfacher HTML-Formatierung, eingegeben werden'
  , 'runtime_editable' => 1
  , 'cols' => '60', 'rows' => 5
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
, 'foodsoftserver' => array(
    'meaning' => 'Spitzname des Servers'
  , 'default' => '(noch namenlos)'
  , 'local' => true
  , 'comment' => 'Dient nur zur Information und wird in der Fusszeile der Webseiten angezeigt'
  , 'runtime_editable' => 1
  , 'cols' => '40'
  )
, 'sockelbetrag' => array(
    'meaning' => 'Sockeleinlage in Euro pro Gruppenmitglied'
  , 'comment' => '"Geschäftsanteil", mit dem sich jedes Mitglied am Eigenkapital der FoodCoop beteiligt'
  , 'default' => '6.00'
  , 'local' => false
  , 'runtime_editable' => 0
  , 'cols' => '8'
  )
, 'muell_id' => array(
    'meaning' => 'Nummer der speziellen Müll-Gruppe (traditionell: 13)'
  , 'default' => '13'
  , 'comment' => 'Diese spezielle Gruppe bezahlt für Verluste der FoodCoop
                   (steht also für Gemeinschaft aller Mitglieder, die per Umlage für Verluste aufkommen mßssen)'
  , 'local' => false
  , 'runtime_editable' => 0
  , 'cols' => '2'
  )
, 'basar_id' => array(
    'meaning' => 'Nummer der speziellen Basar-Gruppe (traditionell: 99)'
  , 'default' => '99'
  , 'comment' => 'Diese spezielle Gruppe bestellt Waren für den Basar'
  , 'local' => false
  , 'runtime_editable' => 0
  , 'cols' => '2'
  )
, 'database_version' => array(
    'meaning' => 'Version der Datenbank'
  , 'default' => '8'
  , 'comment' => 'Bitte den vorgeschlagenen Wert übernehmen und nicht manuell ändern: diese Variable ird bei Upgrades automatisch hochgesetzt!'
  , 'local' => false
  , 'runtime_editable' => 0
  , 'cols' => '3'
  )
);
?>
