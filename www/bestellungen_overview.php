<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
require_once("code/config.php");
require_once("$foodsoftpath/code/zuordnen.php");
require_once("$foodsoftpath/code/views.php");
require_once("$foodsoftpath/code/login.php");
$pwd_ok = $angemeldet;
require_once("$foodsoftpath/head.php");

	 	$result = sql_bestellungen( );
		select_bestellung_view($result, array("Gruppenansicht" => "konsument", "Bestellschein" => "bestellschein", "Bestellschein.pdf" => "bestellt_faxansicht", "Verteiltabelle" => "verteilung", "Lieferschein" => "lieferschein", "ins Archiv" => "check_balanced", "Archiv" => "archiv" ), "Überblick über alle Bestellungen", true);
?>
