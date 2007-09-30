<?php
//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();

$result = sql_bestellungen( );
select_bestellung_view($result, array("Gruppenansicht" => "konsument", "Bestellschein" => "bestellschein", "Bestellschein.pdf" => "bestellt_faxansicht", "Verteiltabelle" => "verteilung", "Lieferschein" => "lieferschein", "ins Archiv" => "check_balanced", "Archiv" => "archiv" ), "Überblick über alle Bestellungen", true);
 
?>

