<?
$leitvariable = array(
  'foodcoop_name' => array(
    'meaning' => 'Name der Foodcoop'
  , 'default' => 'Nahrungskette'
  , 'local' => false
  , 'comment' => 'Dient nur zur Information, wird im Seitenkopf der Webseiten angezeigt'
  , 'runtime_editable' => 1
  )
, 'motd' => array(
    'meaning' => 'message of the day: wird auf der Startseite angezeigt'
  , 'default' => 'Willkommen bei der Nahrungskette!'
  , 'local' => false
  , 'comment' => 'Hier kann beliebiger text, einschliessliche einfacher HTML-Formatierung, eingegeben werden'
  , 'runtime_editable' => 1
  )
, 'readonly' => array(
    'meaning' => 'Datenbank schreibgeschuetzt setzen (sehr eingeschränkte Schreibzugriffe sind dennoch moeglich)'
  , 'default' => '0'
  , 'local' => true
  , 'comment' => 'Flag, um &Auml;nderungen an der Datenbank, etwa w&auml;hrend offline-Betrieb auf
                  einem anderen Rechner, zu verhindern'
  , 'runtime_editable' => 1
  )
, 'foodsoftserver' => array(
    'meaning' => 'Spitzname des Servers (default: $SERVER_NAME, siehe oben)'
  , 'default' => ''
  , 'local' => true
  , 'comment' => 'Dient nur zur Information, wird in der Fusszeile der Webseiten angezeigt'
  , 'runtime_editable' => 1
  )
, 'sockelbetrag' => array(
    'meaning' => 'Sockeleinlage pro Gruppenmitglied'
  , 'default' => '6.00'
  , 'local' => false
  , 'runtime_editable' => 0
  )
, 'basar_id' => array(
    'meaning' => 'Nummer der speziellen Müll-Gruppe (traditionell: 13)'
  , 'default' => '13'
  , 'local' => false
  , 'runtime_editable' => 0
  )
, 'basar_id' => array(
    'meaning' => 'Nummer der speziellen Basar-Gruppe (traditionell: 99)'
  , 'default' => '99'
  , 'local' => false
  , 'runtime_editable' => 0
  )
);
?>

