<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
//all pwd empty: update `bestellgruppen` set passwort = '352DeJsgtxG.6'
//foodi als pwd: 35q3Za9.ZxrxYd


/*
ALTER TABLE `gesamtbestellungen` ADD `state` ENUM( 'bestellen', 'beimLieferanten', 'Verteilt', 'archiviert' ) NOT NULL DEFAULT 'bestellen';

ALTER TABLE `gesamtbestellungen` ADD INDEX ( `state` ) ;
*/
//Debug LEVEL_
 define('LEVEL_ALL',  4);
 define('LEVEL_MOST',  3);
 define('LEVEL_IMPORTANT',  2);  //All UPDATE and INSERT statments should have level important
 define('LEVEL_KEY',  1);
 define('LEVEL_NONE',  0);
 $_SESSION['LEVEL_CURRENT'] = LEVEL_NONE;
 define('STATUS_BESTELLEN', "bestellen");
 define('STATUS_LIEFERANT', "beimLieferanten");
 define('STATUS_VERTEILT', "Verteilt");
 define('STATUS_ARCHIVIERT', "archiviert");

 $_SESSION['DIENSTEINTEILUNG'] =  array('1/2', '3', '4', '5', 'freigestellt');

/**
 *  Bestellvorschläge einfügen
 */
function sql_insert_bestellvorschlaege($produkt_id,$gesamtbestellung_id, $preis_id ){
  $sql = "INSERT INTO bestellvorschlaege (produkt_id, gesamtbestellung_id, produktpreise_id)
          VALUES ('".mysql_escape_string($produkt_id)."', 
                  '".mysql_escape_string($gesamtbestellung_id)."',
                  '".mysql_escape_string($preis_id)."')";
  doSql($sql, LEVEL_IMPORTANT, "Konnte Gesamtbestellungs-Produktliste nicht aufnehmen.");
}
/**
 *  Gesamtbestellung einfügen
 */
function sql_insert_bestellung($name, $startzeit, $endzeit, $lieferung){
   $sql = "INSERT INTO gesamtbestellungen (name, bestellstart, bestellende, lieferung) 
           VALUES ('".mysql_escape_string($name)."', '".
	              mysql_escape_string($startzeit)."', '".
	              mysql_escape_string($endzeit)."', '".
		      mysql_escape_string($lieferung)."')";
  doSql($sql, LEVEL_IMPORTANT, "Konnte Gesamtbestellung nicht aufnehmen.");

}

/**
 *  Dienst bestaetigen 
 */ 
function sql_dienst_bestaetigen($datum){
  global $login_gruppen_id;
  $sql = "UPDATE Dienste SET Status = 'Bestaetigt'
          WHERE GruppenID = ".$login_gruppen_id."
	  AND Lieferdatum = '".$datum."'";
  doSql($sql, LEVEL_IMPORTANT, "Error while confirming Dienstplan");

}

/**
 *  Dienst Akzeptieren 
 */ 
function sql_dienst_akzeptieren($dienst){
  global $login_gruppen_id;
  $row = sql_get_dienst_by_id($dienst);
  if($row["GruppenID"]!=$login_gruppen_id || $row["Status"]!="Vorgeschlagen" ){
       error(__LINE__,__FILE__,"Falsche GruppenID (angemeldet als $login_gruppen_id, dienst gehört ".$row["GruppenID"].") oder falscher Status ".$row["Status"]);
  }
  //OK, wir dürfen den Dienst ändern
  $sql = "UPDATE Dienste SET Status = 'Akzeptiert' WHERE ID = ".$dienst;
  doSql($sql, LEVEL_IMPORTANT, "Error while changing Dienstplan");

}

/**
 *  Dienst ablehnen, nachdem die Gruppe ihn schon akzeptiert hat (offen)
 */
function sql_dienst_wird_offen($dienst){
  global $login_gruppen_id;
  $row = sql_get_dienst_by_id($dienst);
  if($row["GruppenID"]!=$login_gruppen_id || 
         ($row["Status"]!="Vorgeschlagen" && $row["Status"]!="Bestaetigt" && $row["Status"]!="Akzeptiert")){
       error(__LINE__,__FILE__,"Falsche GruppenID (angemeldet als $login_gruppen_id, dienst gehört ".$row["GruppenID"].") oder falscher Status ".$row["Status"]);
  }
  //OK, wir dürfen den Dienst ändern
  $sql = "UPDATE Dienste SET Status = 'Offen' WHERE ID = ".$dienst;
  doSql($sql, LEVEL_IMPORTANT, "Error while reading Rotationsplan");

}
/**
 *  Dienst ablehnen und alternative suchen
 */
function sql_dienst_abtauschen($dienst, $bevorzugt){
  global $login_gruppen_id;
  $row = sql_get_dienst_by_id($dienst);
  if($row["GruppenID"]!=$login_gruppen_id || $row["Status"]!="Vorgeschlagen" ){
       error(__LINE__,__FILE__,"Falsche GruppenID (angemeldet als $login_gruppen_id, dienst gehört ".$row["GruppenID"].") oder falscher Status ".$row["Status"]);
  }
  $sql = "SELECT * from Dienste 
          WHERE Lieferdatum = '".$bevorzugt.
	  "' AND Status = 'Vorgeschlagen'
	  AND Dienst = '".$row["Dienst"]."'";
  $result = doSql($sql, LEVEL_ALL, "Error while reading Dienste");
  if(mysql_num_rows($result)==0){
       error(__LINE__,__FILE__,"Kein ausweichsdienst an diesem Datum ".$bevorzugt." für Dienst ".$row["Dienst"]);
  }
  
  //OK, wir dürfen den Dienst ändern
  $sql = "UPDATE Dienste SET Lieferdatum = '".$bevorzugt."', Status = 'Akzeptiert' WHERE ID = ".$dienst;
  doSql($sql, LEVEL_IMPORTANT, "Error while changing Dienste");
  $sql = "UPDATE Dienste SET Lieferdatum = '".$row["Lieferdatum"]."' WHERE Lieferdatum = '".$bevorzugt.
	  "' AND Status = 'Vorgeschlagen'
	  AND Dienst = '".$row["Dienst"]."' LIMIT 1";
  doSql($sql, LEVEL_IMPORTANT, "Error while changing Dienste");
}
/**
 *  Offenen oder nicht angenommnen Dienst übernehmen
 */
function sql_dienst_uebernehmen($dienst){
  global $login_gruppen_id;
  echo "uebernehmen $dienst";
  $row = sql_get_dienst_by_id($dienst);
  if( ($row["Status"]!="Offen" && $row["Status"]!="Akzeptiert"&& $row["Status"]!="Vorgeschlagen")){
       error(__LINE__,__FILE__,"Falscher Status ".$row["Status"]);
  }
  //OK, wir dürfen den Dienst ändern
  $sql = "UPDATE Dienste SET Status = 'Nicht geleistet' WHERE ID = ".$dienst;
  doSql($sql, LEVEL_IMPORTANT, "Error while reading Rotationsplan");

  if(compare_date2($row["Lieferdatum"], in_two_weeks())){
       $status = "Bestaetigt";
  } else {
       $status = "Akzeptiert";
  }

  sql_create_dienst2($login_gruppen_id,$row["Dienst"], "'".$row["Lieferdatum"]."'", $status);

}

/**
 *  Fragt einen einzelnen Dienst basierend
 *  auf der ID ab
 */
function sql_get_dienst_by_id($dienst){
  $sql = "SELECT * FROM Dienste WHERE ID = ".$dienst;
  $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
  return mysql_fetch_array($result);
}
/**
 * Gibt es an einem Datum Dienste,
 * Die noch offen, vorgeschlagen oder akzeptiert sind
 * (so dass nicht sicher ist, ob der Dienst geleistet wird)
 */

function sql_dienste_nicht_bestaetigt($datum){
   $sql = "SELECT * FROM Dienste 
           WHERE Lieferdatum = '".$datum."' 
	   AND (Status != 'Bestaetigt' OR
	        Status != 'Nicht geleistet')";
   $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
   return mysql_num_rows($result) > 0;
}

/**
 *  Macht eine Abfrage für den Dienstplan 
 *  Zurückgegeben wird ein mysql-set
 */

function sql_get_dienste($datum = FALSE){
   $sql = "SELECT * FROM Dienste 
              INNER JOIN bestellgruppen 
	         ON (Dienste.gruppenID = bestellgruppen.id)";
   if($datum !==FALSE){
   $sql .= " WHERE Lieferdatum = '".$datum."'";
   }
   $sql .= " ORDER BY Lieferdatum DESC, Dienst ASC";
   $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
   return $result;
}

/**
 *  Gibt die nächste Gruppe für einen Dienst aus
 *  dem Rotationsplan zurück
 */

function sql_rotationsplan_next($dienst, $current){
     $sql = "SELECT min(rotationsplanposition) as mynext
		    FROM bestellgruppen  
		    WHERE rotationsplanposition > ".$current."
		    AND diensteinteilung = '".$dienst."'";
     $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
     $row = mysql_fetch_array($result);
     $next = $row["mynext"];
     if($next==NULL){
         $next = sql_rotationsplan_extrem($dienst, FALSE);
     }
     return $next;
}
/** Fügt einen Dienst mit beliebigem Status in die Diensttabelle 
 *  Achtung, das Datum muss in Anführungszeichen sein.
 */

function sql_create_dienst2($gruppe, $dienst, $sql_datum, $status){
    $sql = "INSERT INTO Dienste (GruppenID, Dienst, Lieferdatum, Status)
            VALUES (".$gruppe.", '".$dienst."', ".$sql_datum.", '".$status."')";
    doSql($sql, LEVEL_IMPORTANT, "Error while adding Dienst");
}

/** Fügt einen neuen Dienst als Vorschlag in die Diensttabelle
 */
function sql_create_dienst($datum, $dienst, $rotationsposition){
    sql_create_dienst2(sql_rotationsplangruppe($dienst, $rotationsposition),
                         $dienst,
			 date_intern2sql($datum),
			 "Vorgeschlagen");
}

/** 
 *  Erzeugt Dienste für einen Zeitraum
 */

function create_dienste($start, $end, $spacing) {
   $dates = sql_date_list($start, $end, $spacing);
   //mit negativer Position in Reihenfolge intialisieren
   // wird beim ersten Durchlauf auf erste Position gesetzt
   $dienste = array("1/2" => array("position" => -999, "anzahl" => 2), 
                      "3" => array("position" => -999, "anzahl" => 1), 
		      "4" => array("position" => -999, "anzahl" => 2)
		   );
   foreach($dates as $current){
       if(compare_date2(get_latest_dienst(), $current )){
	       foreach(array_keys($dienste) as $dienst){
	          for($i=1; $i<=$dienste[$dienst]["anzahl"]; $i++){
		   $plan_position = sql_rotationsplan_next($dienst, $dienste[$dienst]["position"]);
		   sql_create_dienst($current, $dienst, $plan_position);
		   $dienste[$dienst]["position"]=$plan_position;
		  }
	       }
       }
   }
   //Wenn ein Dienst erzeugt wurde, rotationsplan umstellen
   if($dienste["1/2"]["position"]!=-999){
   foreach(array_keys($dienste) as $dienst){
        sql_rotate_rotationsplan($dienste[$dienst]["position"],$dienst);
   }
   }
}

/** 
 *  Erzeugt Array mit Daten in einem Zeitraum
 */
function sql_date_list($start, $end, $spacing) {
   if(compare_date2($end,$start)){
   	error(__LINE__,__FILE__,"Enddatum muss später sein als Anfangsdatum", "");
   }
	$list = array();
	$newer=$start;
	do{
	     $list[]=$newer;
	     $sql = "SELECT ADDDATE(".date_intern2sql($newer).", INTERVAL ".$spacing." DAY) as datum";
	     $result = doSql($sql, LEVEL_ALL, "Error while making datelist");
	     $row = mysql_fetch_array($result);
	     $newer = date_sql2intern($row["datum"]);

	} while(compare_date2($newer,$end));
	return($list);
}

/**
 * Vergleicht zwei Datumswerte bezüglich Reihenfolge
 * True, wenn das erste Datum früher ist
 */ 
function compare_date2($first, $second){
   return strtotime($first) < strtotime($second);
}
function in_two_weeks(){
     //Now
     $date = date_sql2intern(strftime("%Y-%m-%d %H:%M:%s"));
     //Correct format
    $toreturn = sql_add_days_to_date($date, 19);
    return $toreturn;
}

if(!function_exists("date_parse")){
function date_parse($date_in){
	$temp = explode(" ", $date_in);
	 $date = explode("-", $temp[0]);
	 if(isset($temp[1])){
           $time = explode(":", $temp[1]);
	 }
	 $toReturn = array( "year" => $date[0],
				   "month" => $date[1],
				   "day" => $date[2]);
	 if(isset($time[0])){
	      $toRetrun["hour"] =  $time[0];
	 }
	 if(isset($time[1])){
	      $toRetrun["minute"] =  $time[1];
	 }
	 if(isset($time[2])){
	      $toRetrun["second"] =  $time[2];
	 }
	return $toReturn;
}
}
/** Converts a date string from mysql
 *  to a date of the form
 *   $date["day"].".".$date["month"].".".$date["year"]
 */
function date_sql2intern($date_in){
     $date = date_parse($date_in);
     return $date["day"].".".$date["month"].".".$date["year"];
}

/** Adds convertion commands in mysql to
 *  converts a date string 
 *  from  a date of the form
 *   $date["day"].".".$date["month"].".".$date["year"]
 */
function date_intern2sql($date){
   return "STR_TO_DATE('".$date."', '%e.%c.%Y')";
}
/**
 *  Tage zu Datum hinzufügen, da
 *  php-Funktionen nicht gut
 *  Date Format: $date["day"].".".$date["month"].".".$date["year"]
 */
function sql_add_days_to_date($date, $add_days=0){
     $sql = "SELECT ADDDATE(".date_intern2sql($date).", INTERVAL ".$add_days." DAY) as datum";
     $result = doSql($sql, LEVEL_ALL, "Error while doing date function");
     $row = mysql_fetch_array($result);
     $toreturn=  date_sql2intern($row["datum"]);
     return $toreturn;
}

/** Gibt das Datum für den letzten
 *  Dienst im Dienstplan zurück.
 *  Add days wird auf das Datum draufgeschlagen
 *
 *  Heute (ohne aufschlag), wenn kein Eintrag.
 */
function get_latest_dienst($add_days=0){
     $sql = "SELECT ADDDATE(max(Lieferdatum), INTERVAL ".$add_days." DAY) as datum
		    FROM Dienste  ";
     $result = doSql($sql, LEVEL_ALL, "Error while reading Dienstplan");
     $row = mysql_fetch_array($result);
     $date = date_parse($row["datum"]);
     if($date["year"]==false){
        $date = date_parse(strftime("%Y-%m-%d"));
     }
     $date_formated = $date["day"].".".$date["month"].".".$date["year"];
     return $date_formated;

}


/**
 *  Wählt alle Dienste einer Gruppe mit bestimmtem Status
 */
function sql_get_dienst_group($group, $status){
    $sql = "SELECT *
            FROM Dienste
	    WHERE Status = '".$status.
	    "' AND GruppenID = ".$group."
	    ORDER BY Lieferdatum ASC";
    return doSql($sql, LEVEL_ALL, "Error while reading Dienstplan");
}
/**
 *  Wählt Datum aus, mit bestimmtem Dienst und Status
 *  verwendet für Dienstabtausch
 */
function sql_get_dienst_date($dienst, $status){
    $sql = "SELECT DISTINCT Lieferdatum as datum 
            FROM Dienste
	    WHERE Dienst = '".$dienst.
	    "' AND Status = '".$status."'";
    return doSql($sql, LEVEL_ALL, "Error while reading Dienstplan");
}
/**
 *  This function allows to rotate the
 *  rotation system. This is used after
 *  assigning new tasks. The rotation is
 *  performed in a way that the group with
 *  the latest assignment will the the last
 *  in the rotation system.
 */
function sql_rotate_rotationsplan($latest_position, $dienst){
    /*move all before and including the latest assigned
     *group to the back of the rotation system.
     *Mark the changed entries with negative numbers.
     */
    var_dump(sql_rotationsplan_extrem($dienst));
    var_dump($latest_position);
    $shift =sql_rotationsplan_extrem($dienst) - $latest_position ;
    $sql = "UPDATE bestellgruppen 
            SET rotationsplanposition = -1 * (rotationsplanposition +".$shift.") 
	    WHERE rotationsplanposition <= ".$latest_position." AND diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
    /* Move all remaining groups (the ones not assigned a
     * task during the last round) to the front.
     * They haven't been moved in the previous round,
     * so they remain positive
     */
    $sql = "UPDATE bestellgruppen 
    	    SET rotationsplanposition 
	        = (rotationsplanposition -".$latest_position.
	   ") WHERE rotationsplanposition > 0 
	    AND diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
    // Remove mark (negative numbers)
    $sql = "UPDATE bestellgruppen 
    	    SET rotationsplanposition = -1*rotationsplanposition 
	    WHERE rotationsplanposition < 0 
	    AND diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
   
}
/**
 *  This function allows to move a group up or down
 *  within the rotation system
 */
function sql_change_rotationsplan($gruppe, $dienst, $move_down){
    $position = sql_rotationsplanposition($gruppe);
    if($move_down){
    	$position_new = $position+1;
    } else {
    	$position_new = $position-1;
    }
    $sql = "UPDATE bestellgruppen 
    	    SET rotationsplanposition = ".$position.
	   " WHERE rotationsplanposition = ".$position_new.
	   " AND diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
    $sql = "UPDATE bestellgruppen 
    	    SET rotationsplanposition = ".$position_new.
	   " WHERE id = ".$gruppe;
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");

}

/**
 *  This function returns the highest
 *  position number in the rotation system.
 *  Usally, this corresponds to the number of
 *  groups
 */

function sql_rotationsplan_extrem($dienst, $getMax=TRUE){
     $max="min";
     if($getMax){
         $max="max";
     }
     $sql = "SELECT ".$max."(rotationsplanposition) as theMax
		    FROM bestellgruppen  
		    WHERE diensteinteilung = '". $dienst.
		  "' AND aktiv = 1 ";
     $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
     $row = mysql_fetch_array($result);
     return $row["theMax"];
}
/**
 *  Queries the group id for a
 *  given position in the
 *  rotation plan 
 */
function sql_rotationsplangruppe($dienst, $position){
     $sql = "SELECT id
		    FROM bestellgruppen  
		    WHERE rotationsplanposition = ".$position."
		    AND diensteinteilung = '".$dienst."'";
     $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
     $row = mysql_fetch_array($result);
     return $row["id"];

}
/**
 *  Queries the position in the
 *  rotation plan for a group
 */
function sql_rotationsplanposition($gruppe){
     $sql = "SELECT rotationsplanposition
		    FROM bestellgruppen  
		    WHERE id = ".$gruppe;
     $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
     $row = mysql_fetch_array($result);
     return $row["rotationsplanposition"];

}

/**
 *  Checks the validity of a rotation system.
 *  If there are any groups with position 0
 *  or negative, they will be pushed to the
 *  end of the plan.
 *  One of multiple groups with duplicate positions will be
 *  moved to the end.
 */
function sql_check_rotationsplan($dienst){
     $theMax  = sql_rotationsplan_extrem($dienst);
     while(sql_rotationsplan_has0($dienst)){
	$theMax +=1;
	$sql = "UPDATE bestellgruppen  
	        SET rotationsplanposition = ".$theMax.
		" WHERE diensteinteilung = '". $dienst.
		"' AND aktiv = 1 and rotationsplanposition <= 0 
		LIMIT 1";
	doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");

    }
    $position = sql_rotationsplan_hasDuplicates($dienst);
     while($position !=0){
          
	$theMax +=1;
	$sql = "UPDATE bestellgruppen  
	        SET rotationsplanposition = ".$theMax.
		" WHERE diensteinteilung = '". $dienst.
		"' AND aktiv = 1 and rotationsplanposition = ".$position." 
		LIMIT 1";
	doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
        $position = sql_rotationsplan_hasDuplicates($dienst);
     }

}
/** 
 *  Checks whether there are groups 
 *  which share position in the rotation system,
 */
function sql_rotationsplan_hasDuplicates($dienst){

        $sql = "SELECT rotationsplanposition FROM
	            (SELECT rotationsplanposition, count(id) as anzahl
			FROM bestellgruppen  
			WHERE diensteinteilung = '". $dienst.
			"' AND aktiv = 1  
			GROUP BY rotationsplanposition) as c
		      WHERE anzahl > 1";
	$result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
	$answer = 0;
        if(mysql_num_rows($result)!=0){
	   $row = mysql_fetch_array($result);
	   $answer = $row["rotationsplanposition"];
	}
        return($answer);
}
/** 
 *  Checks whether there are groups 
 *  which are not in the rotation system,
 *  i.e. their position is 0
 */
function sql_rotationsplan_has0($dienst){

        $sql = "SELECT id 
		FROM bestellgruppen  
		WHERE diensteinteilung = '". $dienst.
		"' AND aktiv = 1 and rotationsplanposition <= 0 ";
	$result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
        return(mysql_num_rows($result)!=0);

}
/** Queries the rotation plan for a
 *  given task. Before querying it, a
 *  check is performed to fix problems.
 */
function sql_rotationsplan($dienst){
        sql_check_rotationsplan($dienst);
	$sql = "SELECT id, name, rotationsplanposition, diensteinteilung
		FROM bestellgruppen 
		WHERE diensteinteilung = '". $dienst.
		"' AND aktiv = 1
		ORDER BY rotationsplanposition ASC";
	return doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
}
/**
 * Returns an array of functions (i.e. forms) a
 * group is allowed to access based on the task
 * they are performing
 */
function possible_areas(){
  global $hat_dienst_I, $hat_dienst_III, $hat_dienst_IV, $hat_dienst_V;
   $areas = array(
           array("area" => "index.php?area=meinkonto", 
	        "hint"  => "Hier können die einzelnen Gruppen ihre Kontoauszüge einsehen....", 
		"title" => "Mein Konto"
	   )
   );
$areas[] = array("area" => "index.php?area=bestellen",
	"hint" => "Hier können die einzelnen Gruppen an den aktuellen Bestellung Teilnehmen....",
	"title" => "Bestellen");
if($hat_dienst_I or $hat_dienst_IV){
	$areas[] = array("area" => "index.php?area=lieferschein",
	"hint" => "Hier kann der Lieferschein abgeglichen werden...",
	"title" => "Lieferschein");
	$areas[] = array("area" => "index.php?area=verteilung",
	"hint" => "Hier kann die Verteilung eingesehen und angepasst werden...",
	"title" => "Verteilung");
 }
if($hat_dienst_IV){
	$areas[] = array("area" => "index.php?area=bestellschein",
	"hint" => "FAX mit Bestellung, Gesamtbestellung ansehen",
	"title" => "Bestellschein");
	$areas[] = array("area" => "index.php?area=produkte",
	"hint" => "Neue Produkte eingeben ... Preise verwalten ... Bestellung online stellen","title" => "Produktdatenbank");	 
} 
	$areas[] = array("area" => "index.php?area=gruppen",
	"hint" => "Hier kann man die Bestellgruppen und deren Konten verwalten...",
	"title" => "Gruppenverwaltung");		
if($hat_dienst_IV or $hat_dienst_III){

	$areas[] = array("area" => "index.php?area=basar",
	"hint" => "Produkte im Basar an Gruppen verteilen",
	"title" => "Basar");
}
if($hat_dienst_IV){
	$areas[] = array("area" => "index.php?area=lieferanten",
	"hint" => "Hier kann man die LieferantInnen verwalten...",
	"title" => "LieferantInnen");
} 
	$areas[] = array("area" => "index.php?area=dienstkontrollblatt",
	"hint" => "Hier kann man das Dientkontrollblatt einsehen...",
	"title" => "Dienstkontrollblatt");		
if($hat_dienst_IV or $hat_dienst_III or $hat_dienst_I){
	$areas[] = array("area" => "index.php?area=updownload",
	"hint" => "Hier kann die Datenbank hoch und runter geladen werden...",
	"title" => "Up/Download");
} 

   $areas[] = array("area" => "index.php?area=dienstplan", 
	        "hint"  => "Eigene Dienste anschauen, Dienste übernehmen, ...", 
		"title" => "Dienstplan"
	   );
   return $areas;
}

//
// Passwort-Funktionen:
//
function check_password( $gruppen_id, $gruppen_pwd ) {
  global $crypt_salt;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    $result = mysql_query( "SELECT password FROM bestellgruppen WHERE id='$gruppen_id' AND aktiv=1" )
      or error(__LINE__,__FILE__,"Suche nach Bestellgruppe fehlgeschlagen: ",mysql_error());
    $row = mysql_fetch_array($result);
      or error(__LINE__,__FILE__,"Bestellgruppe nicht gefunden: ",mysql_error());
    if( $row['passwort'] == crypt($gruppen_pwd,$crypt_salt) )
      return $row;
  }
  return false;
}
function set_password( $gruppen_id, $gruppen_pwd ) {
  global $crypt_salt;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    ( $gruppen_id == $login_gruppen_id ) or nur_fuer_dienst_V();
    mysql_query( 
      "UPDATE bestellgruppen SET passwort='"
       . mysql_real_escape_string(crypt($gruppen_pwd,$crypt_salt))
       . "' WHERE id='$gruppen_id'"
    ) or error(__LINE__,__FILE__,"Setzen des Gruppenpassworts fehlgeschlagen: ",mysql_error());
  }
}

function doSql($sql, $debug_level, $error_text){
	if($debug_level <= $_SESSION['LEVEL_CURRENT']) echo "<p>".$sql."</p>";
	$result = mysql_query($sql) or
	error(__LINE__,__FILE__,$error_text."(".$sql.")",mysql_error(), debug_backtrace());
	return $result;

}
function getState($bestell_id){
     $sql = "SELECT state FROM gesamtbestellungen WHERE id = $bestell_id";
     $result = doSql($sql, LEVEL_ALL, "Konnte status  nicht von DB laden..");
     $row = mysql_fetch_array($result);
     return $row['state'];
}
function changeState($bestell_id, $state){

     $current = getState($bestell_id);

     switch($state){
     case "bestellen":
     break;
     case "beimLieferanten":
     break;
     case "Verteilt":
     break;
     case "archiviert":
     break;
     default: error(__LINE__,__FILE__, "Ungültiger zu setzender Status");
     }
     $sql = "UPDATE gesamtbestellungen SET state = '$state' WHERE id = $bestell_id";
    doSql($sql, LEVEL_KEY, "Konnte status  in DB nicht ändern..");
}

/**
 *  Dient dazu, die Verteilmengen nochmal zu
 *  löschen, wenn erneut als Basar angemeldet wird
 *  oder sonst ein Fehler besteht
 */
function verteilmengenLoeschen($bestell_id, $nur_basar=FALSE){
    $query = "SELECT * FROM gesamtbestellungen WHERE (state =
    '".STATUS_BESTELLEN."' or state = '".STATUS_LIEFERANT."' ) AND id = ".mysql_escape_string($bestell_id);
	$result = doSql($query, LEVEL_ALL, "Konnte Bestellmengen nich aus DB laden.. ");
	if(mysql_num_rows($result)==0) return false;

	$sql = "DELETE bestellzuordnung.* FROM bestellzuordnung inner
	join gruppenbestellungen on (gruppenbestellungen.id =
	gruppenbestellung_id) WHERE art = 2 AND gesamtbestellung_id = ".$bestell_id;
	if($nur_basar) {
	    $sql.=" AND bestellguppen_id = ".mysql_escape_string(sql_basar_id());
	}

	doSql($sql, LEVEL_ALL, "Konnte bestellungen nicht aus DB löschen..");


	if(! $nur_basar){
		$sql = "UPDATE bestellvorschlaege set bestellmenge = NULL where gesamtbestellung_id = ".$bestell_id;
		doSql($sql, LEVEL_ALL, "Konnte bestellungen nicht aus DB löschen..");
	}

	changeState($bestell_id, STATUS_BESTELLEN);

	return true;
}
function sql_basar_id(){
	    $sql = "SELECT id FROM bestellgruppen
	    		WHERE name = \"_basar\"";
	    //echo $sql."<br>";
	    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Basar-ID nich aus DB laden..",mysql_error());
	    if(mysql_num_rows($result)!=1) 
		error(__LINE__,__FILE__,"Kein Eintrag für Glasrueckgabe" );
	    $row = mysql_fetch_array($result);
	    return $row['id'];


}
function sqlUpdateTransaction($transaction, $receipt){
	    $sql="UPDATE gruppen_transaktion SET kontoauszugs_nr = ".$receipt." WHERE id = ".$transaction;
	    //echo $sql."<br>";
	    $result = mysql_query($sql) or
	    error(__LINE__,__FILE__,"Konnte Transaktion in DB nicht aktualisieren.. ($sql)",mysql_error());
}
function sql_groupGlass($gruppe, $menge){
	//include_once("config.php");  tut bisher nicht
	$pfand_preis = 0.16; 
	sqlGroupTransaction(2, $gruppe, ($pfand_preis*$menge),"NULL" ,'Glasrueckgabe');
}

function sqlGroupTransaction($transaktionsart,
			         $gruppen_id,
				 $summe, $auszug_nr = NULL,
				 $notiz ="", 
				 $kontobewegungs_datum ="NOW()"){

	   $sql="INSERT INTO gruppen_transaktion 
	                    (type, gruppen_id, eingabe_zeit,
			      summe, kontoauszugs_nr, notiz, 
			      kontobewegungs_datum) 
	         VALUES ('".mysql_escape_string($transaktionsart).
		          "', '".mysql_escape_string($gruppen_id).
			  "', NOW(), '".mysql_escape_string($summe).
			  "', '".mysql_escape_string($auszug_nr).
			  "', '".mysql_escape_string($notiz).
			  "', '".mysql_escape_string($kontobewegungs_datum).
			  "')" ;
	    //echo $sql."<br>";
	    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Glas-Rückgabe nicht in DB speichern.. ($sql)",mysql_error());
}
function getGlassID(){
	    $sql = "SELECT id FROM produkte
	    		WHERE name = \"glasrueckgabe\"";
	    //echo $sql."<br>";
	    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Glas-Produkt-ID nich aus DB laden..",mysql_error());
	    if(mysql_num_rows($result)!=1) 
		error(__LINE__,__FILE__,"Kein Eintrag für Glasrueckgabe" );
	    $row = mysql_fetch_array($result);
	    return $row['id'];


}
function sql_create_gruppenbestellung($gruppe, $bestell_id){
	    $sql = "
        INSERT INTO gruppenbestellungen (bestellguppen_id, gesamtbestellung_id)
        VALUES ($gruppe, $bestell_id)
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
      ";
	    //echo $sql."<br>";
	    mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Gruppenbestellung nicht eintragen: ",mysql_error());
	    //Id Auslesen und zurückgeben
	    $sql = "SELECT last_insert_id() as id;";
	    $result = mysql_query($sql) or error(__LINE__,__FILE__,"SELECT last_insert_id() fehlgeschlagen: ",mysql_error());
	    $id = mysql_fetch_array($result)
        or error(__LINE__,__FILE__,"last_insert_id() nicht gefunden: ",mysql_error());
	    return($id['id']);
	
}
function sql_basar2group($gruppe, $produkt, $bestell_id, $menge){

	    //Gruppenbestellung ID raussuchen:
      $id = sql_create_gruppenbestellung( $gruppe, $bestell_id );
      //                   ^ ist idempotent!

	    $sql = "
        INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art)
        VALUES ('$produkt','$id','$menge', 2)
        ON DUPLICATE KEY UPDATE menge = menge + $menge
      ";
	    //echo $sql2."<br>";
	    mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Basarkauf nicht eintragen",mysql_error());
}
function kontostand($gruppen_id){
	    //Bestellt
	    $query = "SELECT summe FROM (".select_bestellsumme().")as bestellsumme WHERE bestellguppen_id = ".mysql_escape_string($gruppen_id);
	    //echo "<p>".$query."</p>";
	    $result = doSql($query, LEVEL_ALL, "Konnte Produktdaten nicht aus DB laden..");
	    $row = mysql_fetch_array($result);
	    $summe = -$row['summe'];
	    //Sonstige Transaktionen
	    $query = "SELECT sum( summe ) as summe
			FROM `gruppen_transaktion`
			WHERE gruppen_id =".mysql_escape_string($gruppen_id);
	    //echo "<p>".$query."</p>";
	    $result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Produktdaten nich aus DB laden..",mysql_error());
	    $row = mysql_fetch_array($result);
	    $summe += $row['summe'];

	    return $summe;

}
function select_verteilmengen_preise(){
	return "select `gruppenbestellungen`.`bestellguppen_id` AS `bestellguppen_id`,`gesamtbestellungen`.`id` AS `bestell_id`,`gesamtbestellungen`.`name` AS `name`,`bestellzuordnung`.`produkt_id` AS `produkt_id`,`bestellzuordnung`.`menge` AS `menge`,`produktpreise`.`preis` AS `preis`,`gesamtbestellungen`.`bestellende` AS `bestellende` from ((((`bestellzuordnung` join `gruppenbestellungen` on((`bestellzuordnung`.`gruppenbestellung_id` = `gruppenbestellungen`.`id`))) join `bestellvorschlaege` on(((`bestellzuordnung`.`produkt_id` = `bestellvorschlaege`.`produkt_id`) and (`gruppenbestellungen`.`gesamtbestellung_id` = `bestellvorschlaege`.`gesamtbestellung_id`)))) join `produktpreise` on((`bestellvorschlaege`.`produktpreise_id` = `produktpreise`.`id`))) join `gesamtbestellungen` on((`gesamtbestellungen`.`id` = `gruppenbestellungen`.`gesamtbestellung_id`))) where (`bestellzuordnung`.`art` = 2) order by `gesamtbestellungen`.`bestellende`
";
}
function select_verteilmengen(){
	return " SELECT sum(menge) as verteilmenge, gesamtbestellung_id,
	produkt_id FROM bestellzuordnung inner join
	gruppenbestellungen on (bestellzuordnung.gruppenbestellung_id
		= gruppenbestellungen.id) WHERE art = 2
	GROUP BY gesamtbestellung_id , produkt_id";
}
function select_bestellkosten(){
	return "select `verteilmengen_preise`.`bestellguppen_id` AS
	`bestellguppen_id`,`verteilmengen_preise`.`bestell_id` AS
	`bestell_id`,`verteilmengen_preise`.`name` AS
	`name`,sum((`verteilmengen_preise`.`menge` *
	`verteilmengen_preise`.`preis`)) AS
	`gesamtpreis`,`verteilmengen_preise`.`bestellende` AS
	`bestellende` from (".select_verteilmengen_preise().") as `verteilmengen_preise` group by `verteilmengen_preise`.`bestellguppen_id`,`verteilmengen_preise`.`bestell_id`,`verteilmengen_preise`.`name`,`verteilmengen_preise`.`bestellende`";
}
function select_bestellsumme(){
	return "select bestellkosten.bestellguppen_id
	,sum(bestellkosten.gesamtpreis) AS summe from
	(".select_bestellkosten().") as`bestellkosten` group by `bestellkosten`.`bestellguppen_id`
";
	/*
	`bestellsumme` AS select `bestellkosten`.`bestellguppen_id` AS `bestellguppen_id`,sum(`bestellkosten`.`gesamtpreis`) AS `summe` from `bestellkosten` group by `bestellkosten`.`bestellguppen_id`
	`bestellkosten` AS select `verteilmengen_preise`.`bestellguppen_id` AS `bestellguppen_id`,`verteilmengen_preise`.`bestell_id` AS `bestell_id`,`verteilmengen_preise`.`name` AS `name`,sum((`verteilmengen_preise`.`menge` * `verteilmengen_preise`.`preis`)) AS `gesamtpreis`,`verteilmengen_preise`.`bestellende` AS `bestellende` from `verteilmengen_preise` group by `verteilmengen_preise`.`bestellguppen_id`,`verteilmengen_preise`.`bestell_id`,`verteilmengen_preise`.`name`,`verteilmengen_preise`.`bestellende`
	`verteilmengen_preise` AS select `gruppenbestellungen`.`bestellguppen_id` AS `bestellguppen_id`,`gesamtbestellungen`.`id` AS `bestell_id`,`gesamtbestellungen`.`name` AS `name`,`bestellzuordnung`.`produkt_id` AS `produkt_id`,`bestellzuordnung`.`menge` AS `menge`,`produktpreise`.`preis` AS `preis`,`gesamtbestellungen`.`bestellende` AS `bestellende` from ((((`bestellzuordnung` join `gruppenbestellungen` on((`bestellzuordnung`.`gruppenbestellung_id` = `gruppenbestellungen`.`id`))) join `bestellvorschlaege` on(((`bestellzuordnung`.`produkt_id` = `bestellvorschlaege`.`produkt_id`) and (`gruppenbestellungen`.`gesamtbestellung_id` = `bestellvorschlaege`.`gesamtbestellung_id`)))) join `produktpreise` on((`bestellvorschlaege`.`produktpreise_id` = `produktpreise`.`id`))) join `gesamtbestellungen` on((`gesamtbestellungen`.`id` = `gruppenbestellungen`.`gesamtbestellung_id`))) where (`bestellzuordnung`.`art` = 2) order by `gesamtbestellungen`.`bestellende`
	`verteilmengen` AS select `verteilmengen_preise`.`bestell_id` AS `bestell_id`,`verteilmengen_preise`.`produkt_id` AS `produkt_id`,sum(`verteilmengen_preise`.`menge`) AS `menge` from `verteilmengen_preise` group by `verteilmengen_preise`.`bestell_id`,`verteilmengen_preise`.`produkt_id`
	*/
}
function sql_gesamtpreise($gruppe_id){
            $query = "SELECT gesamtbestellungen.id as gesamtbestellung_id, gesamtbestellungen.name, sum(menge * preis) AS gesamtpreis, 
	    				DATE_FORMAT(bestellende,'%d.%m.%Y  <br> <font size=1>(%T)</font>') as datum
				FROM  bestellzuordnung 
				INNER JOIN gruppenbestellungen ON ( gruppenbestellung_id = gruppenbestellungen.id )
				INNER JOIN bestellvorschlaege
				on (
				bestellvorschlaege.gesamtbestellung_id
				=
				gruppenbestellungen.gesamtbestellung_id
				and bestellvorschlaege.produkt_id =
				bestellzuordnung.produkt_id  )
				INNER JOIN produktpreise ON ( produktpreise_id = produktpreise.id ) 
				INNER JOIN gesamtbestellungen ON (gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id)
				WHERE art =2 and bestellguppen_id = '".mysql_escape_string($gruppe_id)."'
				GROUP BY gesamtbestellungen.name
				    ORDER BY bestellende DESC;";

//	    echo "<p>".$query."</p>";
	    $result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Produktdaten nich aus DB laden..",mysql_error());

	    return $result;

}


function sql_bestellprodukte($bestell_id){
            $query = "SELECT *, produkte.name as produkt_name, produktgruppen.name as produktgruppen_name
                              , produktpreise.liefereinheit as liefereinheit
                              , produktpreise.verteileinheit as verteileinheit
                              , produktpreise.gebindegroesse as gebindegroesse
            FROM produkte INNER JOIN
	                            bestellvorschlaege ON (produkte.id=bestellvorschlaege.produkt_id)
				    INNER JOIN produktpreise 
				    ON (bestellvorschlaege.produktpreise_id=produktpreise.id)
				    INNER JOIN produktgruppen
				    ON (produktgruppen.id=produkte.produktgruppen_id)
				    WHERE bestellvorschlaege.gesamtbestellung_id='".mysql_escape_string($bestell_id)."'
				    ORDER BY IF(liefermenge>0,0,1), produktgruppen_id, produkte.name;";

	    //echo "<p>".$query."</p>";
	    $result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Produktdaten nich aus DB laden..",mysql_error());

	    return $result;
}
function sql_aktuelle_produktpreise($produkt_id){
   $sql = "SELECT id
           FROM produktpreise 
           WHERE produkt_id = ".mysql_escape_string($produkt_id)." 
           AND (zeitende >= NOW() OR ISNULL(zeitende))";                     

	return doSql($sql, LEVEL_ALL, "Konnte Produktpreise nich aus DB laden..");
}

function sql_produktpreise2($produkt_id){
	$query = "SELECT * FROM produktpreise 
		  WHERE produkt_id=".mysql_escape_string($produkt_id);
	return doSql($query, LEVEL_ALL, "Konnte Gebindegroessen nich aus DB laden..");
}
function sql_produktpreise($produkt_id, $bestell_id, $bestellstart=NULL, $bestellende=NULL){
	
	if($produkt_id=="") error(__LINE__,__FILE__, "Produkt_ID must not be empty");
	//Read start and ende from Database
	if($bestellende===NULL){
		$query = "SELECT bestellende FROM gesamtbestellungen WHERE id = ".$bestell_id;
		//echo "<p>".$query."</p>";
		$result = doSql($query, LEVEL_ALL,"Konnte Bestellung nicht aus DB laden ..");
		$row = mysql_fetch_array($result);
		$bestellende=$row["bestellende"];
	}
	if($bestellstart===NULL){
		$bestellstart = $bestellende;
	}
	$query = "SELECT gebindegroesse,preis,bestellnummer, id FROM produktpreise 
		  WHERE zeitstart <= '".mysql_escape_string($bestellstart)."' 
		        AND (ISNULL(zeitende) OR zeitende >= '".mysql_escape_string($bestellende)."')
			AND produkt_id= ".mysql_escape_string($produkt_id)."
			ORDER BY gebindegroesse DESC;";
	//echo "<p>".$query."</p>";
	$result = doSql($query, LEVEL_ALL, "Konnte Gebindegroessen nich aus DB laden..");
       if(mysql_num_rows($result)==0) {
		$query = "SELECT gebindegroesse, preis FROM produktpreise 
		          WHERE id IN 
			  	(SELECT produktpreise_id 
				 FROM bestellvorschlaege WHERE 
				 produkt_id = ".mysql_escape_string($produkt_id)."  
				 AND gesamtbestellung_id = ".mysql_escape_string($bestell_id)." 
				)";
		$result = doSql($query, LEVEL_ALL,"Konnte Gebindegroessen nich aus DB laden.. ");
       }

	return $result;
}
function sql_verteilmengen($bestell_id, $produkt_id, $gruppen_id){
	$result = sql_bestellmengen($bestell_id, $produkt_id,2, $gruppen_id);
	if(mysql_num_rows($result)==0) $return = 0;
	else if(mysql_num_rows($result)>1) 
		error(__LINE__,__FILE__,"Nicht genau ein Eintrag (".mysql_num_rows($result).") für Verteilmenge: bestell_id = $bestell_id, produkt_id = $produkt_id, gruppen_id = $gruppen_id" );
	else{
		$row = mysql_fetch_array($result);
		$return = $row['menge'];
	}
	return $return;
	
}
function sql_bestellmengen($bestell_id, $produkt_id, $art, $gruppen_id=false,$sortByDate=true){
	$query = "SELECT  *, gruppenbestellungen.id as gruppenbest_id,
	bestellzuordnung.id as bestellzuordnung_id
	FROM gruppenbestellungen INNER JOIN bestellzuordnung 
	ON (bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id)
	WHERE gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)." 
	AND bestellzuordnung.produkt_id = ".mysql_escape_string($produkt_id);
	if($gruppen_id!==false){
		$query = $query." AND gruppenbestellungen.bestellguppen_id = ".mysql_escape_string($gruppen_id);
	}
	if($art!==false){
		$query = $query." AND bestellzuordnung.art=".$art;
	}
	if($sortByDate){
		$query = $query." ORDER BY bestellzuordnung.zeitpunkt;";
	}else{
		$query = $query." ORDER BY gruppenbestellung_id, art;";
	}
	//echo "<p>".$query."</p>";
	$result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Bestellmengen nich aus DB laden..",mysql_error());
	return $result;
}
function sql_gruppenname($gruppen_id){
	$query="SELECT name 
		FROM bestellgruppen 
		WHERE id = ".mysql_escape_string($gruppen_id); 
	//echo "<p>".$query."</p>";
	$result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
	$row=mysql_fetch_array($result);
	return $row['name'];
}
function sql_gruppen($bestell_id=FALSE){
        if($bestell_id==FALSE){
		$query="SELECT * FROM bestellgruppen WHERE aktiv=1 ORDER by (id%1000)";
	} else {
	    $query="SELECT distinct bestellgruppen.id, bestellgruppen.name, max(gruppenbestellungen.id) as gruppenbestellungen_id
		FROM bestellgruppen INNER JOIN gruppenbestellungen 
		ON (gruppenbestellungen.bestellguppen_id = bestellgruppen.id)
		WHERE gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id).
		" GROUP BY bestellgruppen.id, bestellgruppen.name"; 
	}
	//echo "<p>".$query."</p>";
	$result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
	return $result;
	
}
function optionen_gruppen() {
  $gruppen = sql_gruppen();
  while($gruppe = mysql_fetch_array($gruppen)){
    echo "<option value='{$gruppe['id']}'>{$gruppe['name']}</option>\n";
  }
}

function sql_bestellungen($state = FALSE, $use_Date = FALSE, $id = FALSE){
	 $query = "SELECT * FROM gesamtbestellungen ";
	 $where = "";
	 $add_and = FALSE;
	 if($use_Date!==FALSE){
	    $where .= "(NOW() BETWEEN bestellstart AND bestellende)";
	    $add_and = TRUE;
	 }
	 if($state!==FALSE){
	    $addOR = FALSE;
	    if($add_and){
	    	$where .= " AND (";
	    }
	    if(!is_array($state)){
	    	$where .= " state = '".$state."'";
	    } else {
		    foreach($state as $st){
			if($addOR) $where .= " OR ";
			$where .= " state = '".$st."'";
			$addOR = TRUE;
		    }
	    }
	    if($add_and){
	    	$where .= ")";
	    }
	    $add_and = TRUE;
	 }
	 if($id!==FALSE){
	    if($add_and){
	    	$where .= " AND (";
	    }
	    $where.= " id =".$id;
	    if($add_and){
	    	$where .= ")";
	    }
	 }
	 if($where != "") {
	 	$query .= " WHERE ".$where;
	}
	$result = doSql(  $query, LEVEL_ALL,"Konnte Gesamtbestellungen nich aus DB laden.. ");
	return $result;
}

function nichtGeliefert($bestell_id, $produkt_id){
    $sql = "UPDATE bestellzuordnung INNER JOIN gruppenbestellungen 
	    ON gruppenbestellung_id = gruppenbestellungen.id 
	    SET menge =0 
	    WHERE art=2 
	    AND produkt_id = ".$produkt_id." 
	    AND gesamtbestellung_id = ".$bestell_id.";";
    mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Verteilmengen nicht ändern..",mysql_error());
    //echo $sql;
    $sql = "UPDATE bestellvorschlaege
    	    SET liefermenge = 0 
	    WHERE produkt_id = ".$produkt_id."
	    AND gesamtbestellung_id = ".$bestell_id;
    //mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Verteilmengen nicht ändern..",mysql_error());
    //echo $sql;
}
function writeLiefermenge_sql($bestell_id){
	$query = "SELECT produkt_id, sum(menge) as s FROM gruppenbestellungen  
		  INNER JOIN bestellzuordnung ON
		  	(gruppenbestellungen.id = gruppenbestellung_id)
		  WHERE art = 2 
		  AND gesamtbestellung_id = ".$bestell_id." 
		  GROUP BY produkt_id";
	//echo $query."<br>";
	$result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Liefermengen nicht in DB schreiben...",mysql_error());
  	while ($produkt_row = mysql_fetch_array($result)){
		$sql2 = "UPDATE bestellvorschlaege SET bestellmenge = "
		        .$produkt_row['s'].", liefermenge = ".
		        $produkt_row['s']." WHERE gesamtbestellung_id = ".
			$bestell_id." AND produkt_id = ".$produkt_row['produkt_id'];
		//echo $sql2."<br>";
		mysql_query($sql2) or error(__LINE__,__FILE__,"Konnte Liefermengen nicht in DB schreiben...",mysql_error());
	}

}
function sql_basar(){
   $sql = "SELECT * FROM (".select_basar().") as basar";
   //echo $sql."<br>";
   $result =  mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Basardaten nich aus DB laden..",mysql_error());
   return $result;

}
function select_basar(){
   return "
SELECT produkte.name, bestellvorschlaege.produkt_id,
bestellvorschlaege.gesamtbestellung_id,
bestellvorschlaege.produktpreise_id, bestellvorschlaege.liefermenge,
bz.verteilmenge, (bestellvorschlaege.liefermenge -
	ifnull(bz.verteilmenge,0)) as basar, produktpreise.verteileinheit,
     produktpreise.preis,
     gesamtbestellungen.name as bestellung_name

FROM bestellvorschlaege 
LEFT JOIN (". select_verteilmengen() .")as bz
ON (bz.produkt_id =
	bestellvorschlaege.produkt_id and bz.gesamtbestellung_id =
	bestellvorschlaege.gesamtbestellung_id) 
JOIN produktpreise ON ( bestellvorschlaege.produktpreise_id = produktpreise.id ) 
JOIN gesamtbestellungen ON ( gesamtbestellungen.id = bestellvorschlaege.gesamtbestellung_id ) 
JOIN produkte ON ( bestellvorschlaege.produkt_id = produkte.id ) 
where (not isnull(liefermenge) or not isnull(bestellmenge)) 
HAVING ( `basar` <>0 )
ORDER BY produkte.name
" ;


 /*  
   "select
   produkte.name,bestellvorschlaege.produkt_id,bestellvorschlaege.gesamtbestellung_id,(bestellvorschlaege.liefermenge
   - verteilmengen.menge) AS basar,bestellvorschlaege.produktpreise_id
   from (((".select_verteilmengen().") as `verteilmengen` join `bestellvorschlaege` on(((`verteilmengen`.`bestell_id` = `bestellvorschlaege`.`gesamtbestellung_id`) and (`bestellvorschlaege`.`produkt_id` = `verteilmengen`.`produkt_id`)))) join `produkte` on((`verteilmengen`.`produkt_id` = `produkte`.`id`))) having (`basar` <> 0) ";
   */
}

function from_basar(){
   return "((`verteilmengen` join `bestellvorschlaege` on(((`verteilmengen`.`bestell_id` = `bestellvorschlaege`.`gesamtbestellung_id`) and (`bestellvorschlaege`.`produkt_id` = `verteilmengen`.`produkt_id`)))) join `produkte` on((`verteilmengen`.`produkt_id` = `produkte`.`id`)))";
   /*
   VIEW `basar` AS select `produkte`.`name` AS `name`,`bestellvorschlaege`.`produkt_id` AS `produkt_id`,`bestellvorschlaege`.`gesamtbestellung_id` AS `gesamtbestellung_id`,(`bestellvorschlaege`.`liefermenge` - `verteilmengen`.`menge`) AS `basar`,`bestellvorschlaege`.`produktpreise_id` AS `produktpreise_id` from ((`verteilmengen` join `bestellvorschlaege` on(((`verteilmengen`.`bestell_id` = `bestellvorschlaege`.`gesamtbestellung_id`) and (`bestellvorschlaege`.`produkt_id` = `verteilmengen`.`produkt_id`)))) join `produkte` on((`verteilmengen`.`produkt_id` = `produkte`.`id`))) having (`basar` <> 0)
   */
}
function zusaetzlicheBestellung($produkt_id, $bestell_id, $menge ){
   $sql ="SELECT * FROM bestellvorschlaege 
   		WHERE produkt_id = ".mysql_escape_string($produkt_id)." 
   		AND gesamtbestellung_id = ".mysql_escape_string($bestell_id) ;
   //echo $sql."<br>";
   $result2 =  mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preise nich aus DB laden..",mysql_error());
   if (mysql_num_rows($result2) == 1){
   	$sql = "UPDATE 	bestellvorschlaege set liefermenge = liefermenge + ".$menge." 
		WHERE produkt_id = ".mysql_escape_string($produkt_id)." 
   		AND gesamtbestellung_id = ".mysql_escape_string($bestell_id) ;
   //echo $sql."<br>";
   mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preise nich aus DB laden..",mysql_error());

   }else {

   $result2 =  sql_produktpreise($produkt_id, $bestell_id);
   if (mysql_num_rows($result2) > 1){
	    error(__LINE__,__FILE__,"Mehr als ein Preis");
   } else if (mysql_num_rows($result2) ==0){
   	    error(__LINE__,__FILE__,"Kein gültiger Preis zum Zeitpunkt
	    des Bestellendes? Produkt_ID $produkt_id, BestellID = $bestell_id");
	 } else {
	    $preis_row = mysql_fetch_array($result2);
	    //var_dump($preis_row);
	    $sql = "INSERT INTO bestellvorschlaege 
	              (produkt_id, gesamtbestellung_id, produktpreise_id, liefermenge)
	            VALUES (".$produkt_id.",".
		    $bestell_id.",".
		    $preis_row['id'].",".
		    $menge.")";
	    //echo $sql."<br>";
	    mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preise nich aus DB laden.. ($sql)",mysql_error());
	}
	}
	    //Dummy Eintrag in bestellzuordnung
	    $sql = "SELECT id FROM gruppenbestellungen
	    		WHERE gesamtbestellung_id = ".$bestell_id;
	    //echo $sql."<br>";
	    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte nicht aus DB laden.. ($sql)",mysql_error());
	    $row = mysql_fetch_array($result);
	    $sql2 = "INSERT INTO bestellzuordnung
	    		(produkt_id, gruppenbestellung_id, menge, art)
			VALUES (".$produkt_id.", ".$row['id'].", 0, 2)";
	    //echo $sql2."<br>";
	    mysql_query($sql2) or error(__LINE__,__FILE__,"Konnte nicht in DB schreiben.. ($sql2)",mysql_error());

}
function sql_getLieferant($lieferant_id){
    $sql="SELECT * FROM lieferanten WHERE id = ".$lieferant_id;
    $result=doSql($sql, LEVEL_ALL, "Error while retrieving Lieferanteninfos");
    return mysql_fetch_array($result);
}
function getProduzentBestellID($bestell_id){
    if($bestell_id==0) {error(__LINE__,__FILE__,"Do not call getProduzentBestellID with bestell_id null)", "bla");}
    $sql="SELECT DISTINCT lieferanten_id FROM bestellvorschlaege 
		INNER JOIN produkte ON (produkt_id = produkte.id)
		WHERE gesamtbestellung_id = ".$bestell_id;
    //echo $sql."<br>";
    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Preise nicht aus DB laden.. ($sql)",mysql_error());
    if (mysql_num_rows($result) > 1)
	    echo error(__LINE__,__FILE__,"Mehr als ein Lieferant fuer Bestellung ".$bestell_id);
	 else {
	    $row = mysql_fetch_array($result);
	    return $row['lieferanten_id'];

	 }
}
function getProdukt($produkt_id){
   $sql = "SELECT * FROM produkte WHERE id = ".$produkt_id;
    //echo $sql."<br>";
    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());
    return mysql_fetch_array($result);
}
/**
 *   Produkte von einem Bestimmten Lieferanten abfragen
 *
 *   Es werden nur Proukte mit gültigen Preis zurückgegeben.
 *   
 *   Wenn eine Bestellung angegeben wird, werden nur
 *   die Produkte zurückgegeben, die noch nicht in der
 *   Bestellung drin sind.
 */
function getProdukteVonLieferant($lieferant_id,   $bestell_id = Null){
   if($bestell_id === Null){
	$zeitpunkt="NOW()";
   	$sql = "SELECT *, produkte.id as p_id FROM produkte inner join produktpreise ON
	(produkte.id = produktpreise.produkt_id) WHERE lieferanten_id =
	".$lieferant_id;
   } else {
   	$zeitpunkt = " (SELECT bestellende FROM gesamtbestellungen WHERE id = ".$bestell_id.") ";
   	$sql = "SELECT *, produkte.id as p_id FROM produkte inner join produktpreise ON
	(produkte.id = produktpreise.produkt_id) left join (SELECT * FROM
	bestellvorschlaege WHERE gesamtbestellung_id = ". $bestell_id.
	") as vorschlaege ON
	(produkte.id = vorschlaege.produkt_id) WHERE
	lieferanten_id =  ".$lieferant_id."  and
	isnull(gesamtbestellung_id)";
	
   }
   $sql .= " AND zeitstart <= ".
	$zeitpunkt." AND (ISNULL(zeitende) OR
	zeitende >= ".$zeitpunkt.") ";
    //echo $sql."<br>";
    $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());
    return $result;
}
function writeVerteilmengen_sql($gruppenMengeInGebinde, $gruppenbestellung_id, $produkt_id){
	if($gruppenMengeInGebinde > 0){
		$query = "INSERT INTO  bestellzuordnung (menge, produkt_id, gruppenbestellung_id, art) 
			  VALUES (".$gruppenMengeInGebinde.
			 ", ".$produkt_id.
			 ", ".$gruppenbestellung_id.", 2);";
		//echo $query."<br>";
		mysql_query($query) or error(__LINE__,__FILE__,"Konnte Verteilmengen nicht in DB schreiben...",mysql_error());
	}
}
function sql_bestellvorschlag($bestell_id, $produkt_id){
	$query="SELECT produktpreis_id FROM bestellvorschlaege 
	  	WHERE gesamtbestellung_id = ".$bestell_id.
		"AND produkt_Id = ".$produkt_id;
	$result = doSql($query, LEVEL_ALL, "Konnte Bestellpreis nicht laden");
	$row = mysql_fetch_array($result);
	return $row;
}
function sql_bestellpreis($bestell_id, $produkt_id){
	$row = sql_bestellvorschlag($bestell_id, $produkt_id);
	return $row['produktpreis_id'];
}
function sql_liefermenge($bestell_id,$produkt_id){
	$row = sql_bestellvorschlag($bestell_id, $produkt_id);
	return $row['liefermenge'];
}

function changeLieferpreis_sql($preis_id, $produkt_id, $bestellung_id){
	$query = "UPDATE bestellvorschlaege 
		  SET produktpreise_id = ".mysql_escape_string($preis_id)."
		  WHERE produkt_id = ".mysql_escape_string($produkt_id)."
		  AND gesamtbestellung_id = ".mysql_escape_string($bestellung_id).";";
	//echo $query."<br>";
	doSql($query, LEVEL_IMPORTANT,"Konnte Lieferpreis nicht in DB ändern...");
}
function changeLiefermengen_sql($menge, $produkt_id, $bestellung_id){
	$query = "UPDATE bestellvorschlaege 
		  SET liefermenge = ".mysql_escape_string($menge)."
		  WHERE produkt_id = ".mysql_escape_string($produkt_id)."
		  AND gesamtbestellung_id = ".mysql_escape_string($bestellung_id).";";
	//echo $query."<br>";
	mysql_query($query) or error(__LINE__,__FILE__,"Konnte Liefermengen nicht in DB ändern...",mysql_error());
}
function changeVerteilmengen_sql($menge, $gruppen_id, $produkt_id, $bestellung_id){
	$where_clause = " WHERE art = 2 AND produkt_id = ".mysql_escape_string($produkt_id)."
			 AND gruppenbestellung_id IN
		  	(SELECT id FROM gruppenbestellungen
				 WHERE bestellguppen_id = ".mysql_escape_string($gruppen_id)."
				 AND gesamtbestellung_id =
				 ".mysql_escape_string($bestellung_id).") ";

	$query = "SELECT * FROM bestellzuordnung ".$where_clause;
	//echo $query."<br>";
	$result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Verteilmengen nicht von DB landen... ($sql)",mysql_error());
	$toDelete = mysql_num_rows($result) - 1 ;
	if($toDelete > 0){
		$query = "DELETE FROM bestellzuordnung
			".$where_clause." LIMIT ".$toDelete;
		echo $query."<br>";
		$result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Verteilmengen nicht in DB ändern...",mysql_error());
	}

	$query = "UPDATE bestellzuordnung 
		  SET menge = ".mysql_escape_string($menge).$where_clause;
	//echo $query."<br>";
	mysql_query($query) or error(__LINE__,__FILE__,"Konnte Verteilmengen nicht in DB ändern...",mysql_error());
}
/*
function check_bereitsVerteilt($bestell_id){
	$query = "SELECT  *, gruppenbestellungen.id as gruppenbest_id,
	bestellzuordnung.id as bestellzuordnung_id 
	FROM gruppenbestellungen INNER JOIN bestellzuordnung 
	ON (bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id)
	WHERE gruppenbestellungen.gesamtbestellung_id = ".mysql_escape_string($bestell_id)." 
	AND bestellzuordnung.art=2 ;";
	//echo "<p>".$query."</p>";
	$result = mysql_query($query) or error(__LINE__,__FILE__,"Konnte Bestellmengen nich aus DB laden..",mysql_error());
	if(mysql_num_rows($result)==0) return false;
	return true;
}
*/
function verteilmengenZuweisen($bestell_id){
  // nichts tun, wenn keine Bestellung ausgewählt
  if($bestell_id==""){
  	echo "<h2>Bitte Bestellung auswählen!!</h2>";
	return;
  }
  // Gleich aussteigen, wenn zuweisung bereits erfolgt
  if(getState($bestell_id)!=STATUS_BESTELLEN) return;

  //row_gesamtbestellung einlesen aus Datenbank
  //benötigt für Bestellstart und Ende
  $sql_best = sql_bestellungen(FALSE, FALSE, $bestell_id);
  $row_gesamtbestellung = mysql_fetch_array($sql_best);

  $gruppen = sql_gruppen($bestell_id);
  while ($gruppe_row = mysql_fetch_array($gruppen)){
     //Diese loops sind noch nicht sauber verschachtelt.
     //Eigentlich könnte man sich die Gruppenmengen in Array merken
     //und damit weiterrechnen. Dazu sind aber im Moment zuviele
     //Variablen da, die ich nicht verstehe.
     $gruppen_id = $gruppe_row['id'];
     $gruppenbestellung_id = $gruppe_row['gruppenbestellungen_id'];
     //echo "Bearbeite Gruppe (".$gruppen_id.") ".$gruppe_row['name'];
     // Produkte auslesen & Tabelle erstellen...
     $result = sql_bestellprodukte($bestell_id);
				    

	$produkt_counter = 0;
	$bestellungDurchfuehren = true;   
			 
	while ($produkt_row = mysql_fetch_array($result)) {

	   unset($gebindegroessen);
	   unset($gebindepreis);
			 
	    // Gebindegroessen und Preise des Produktes auslesen...
	    $preise = sql_produktpreise($produkt_row['produkt_id'],$bestell_id,
	    				$row_gesamtbestellung['bestellstart'],
	    				$row_gesamtbestellung['bestellende']);
	    $i = 0;
	    while ($row = mysql_fetch_array($preise)) {
		   $gebindegroessen[$i]=$row['gebindegroesse'];
	 	   $gebindepreis[$i]=$row['preis'];
	 	   $i++;

	    }			 

	    if($i == 0) error(__FILE__,__LINE__,"Kein Preis für Produkt ".$produkt_row['produkt_name']." (".$produkt_row['produkt_id'].") gefunden! Überprüfe gültigkeit");

	    // Bestellmengenzähler setzen
	    $gesamtBestellmengeFest[$produkt_row['produkt_id']] = 0;
   	    $gesamtBestellmengeToleranz[$produkt_row['produkt_id']] = 0;
	    $gruppenBestellmengeFest[$produkt_row['produkt_id']] = 0;
	    $gruppenBestellmengeToleranz[$produkt_row['produkt_id']] = 0;
	    $gruppenBestellmengeFestInBerstellung[$produkt_row['produkt_id']] = 0;
	    $gruppenBestellmengeToleranzInBerstellung[$produkt_row['produkt_id']] = 0;
	    unset($gruppenBestellintervallUntereGrenze);
	    unset($gruppenBestellintervallObereGrenze);
	    unset($bestellintervallId);
					
					
	    // Hier werden die aktuellen festen Bestellmengen ausgelesen...
	    $bestellmengen = sql_bestellmengen($bestell_id, $produkt_row['produkt_id'],0);
	    $intervallgrenzen_counter = 0;								
	    while ($einzelbestellung_row = mysql_fetch_array($bestellmengen)) {
		if ($einzelbestellung_row['bestellguppen_id'] == $gruppen_id) {
		    //$gruppenbestellung_id = $einzelbestellung_row['gruppenbest_id'];
		    $ug = $gruppenBestellintervallUntereGrenze[$produkt_row['produkt_id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['produkt_id']] + 1;
		    $og = $gruppenBestellintervallObereGrenze[$produkt_row['produkt_id']][$intervallgrenzen_counter] = $gesamtBestellmengeFest[$produkt_row['produkt_id']] + $einzelbestellung_row['menge'];
		    $bestellintervallId[$produkt_row['produkt_id']][$intervallgrenzen_counter] = $einzelbestellung_row['bestellzuordnung_id'];
								
		    $intervallgrenzen_counter++;
		    $gruppenBestellmengeFest[$produkt_row['produkt_id']] += $einzelbestellung_row['menge'];
		}
		$gesamtBestellmengeFest[$produkt_row['produkt_id']] += $einzelbestellung_row['menge'];
	   }
					
	   $gesamteBestellmengeAnfang = $gesamtBestellmengeFest[$produkt_row['produkt_id']];

	   unset($toleranzenNachGruppen);
	   // Hier werden die aktuellen toleranz Bestellmengen ausgelesen...
	   $bestellmengen = sql_bestellmengen($bestell_id, $produkt_row['produkt_id'],1);
	   $toleranzBestellungId = -1;
	   while ($einzelbestellung_row = mysql_fetch_array($bestellmengen)) {						
	 	if ($einzelbestellung_row['bestellguppen_id'] == $gruppen_id) {
		    $gruppenBestellmengeToleranz[$produkt_row['produkt_id']] += $einzelbestellung_row['menge'];
		    $toleranzBestellungId =  $einzelbestellung_row['bestellzuordnung_id'];
		}
		$gesamtBestellmengeToleranz[$produkt_row['produkt_id']] += $einzelbestellung_row['menge'];
						 
		// für jede Gruppe getrennt die Toleranzmengen ablegen
	 	$bestellgruppen_id = $einzelbestellung_row['bestellguppen_id'];
		if (!isset($toleranzenNachGruppen[$bestellgruppen_id])) $toleranzenNachGruppen[$bestellgruppen_id] = 0;
		$toleranzenNachGruppen[$bestellgruppen_id] += $einzelbestellung_row['menge'];
						 
	  }
					
	  if (isset($toleranzenNachGruppen)) ksort($toleranzenNachGruppen);
					
	  // jetzt die Gebindeaufteilung berechnen
	  unset($gruppenMengeInGebinde);
	  unset($festeGebindeaufteilung);
				
	  $rest_menge = $gesamtBestellmengeFest[$produkt_row['produkt_id']]; 
	  $gesamtMengeBestellt = 0;
	  $gruppeGesamtMengeInGebinden = 0;
 	  for ($i=0; $i < count($gebindegroessen); $i++) {
	      $festeGebindeaufteilung[$i] = floor($rest_menge / $gebindegroessen[$i]);
	      $rest_menge = $rest_menge % $gebindegroessen[$i];
					 
	      // berechne: wieviel  hat die aktuelle Gruppe in diesem Gebinde
	      $gebindeAnfang = $gesamtMengeBestellt + 1;
	      $gesamtMengeBestellt += $festeGebindeaufteilung[$i] * $gebindegroessen[$i];
					 
	      $gruppenMengeInGebinde[$i] = 0;
					 
	      if ($festeGebindeaufteilung[$i] > 0 && isset($gruppenBestellintervallUntereGrenze[$produkt_row['produkt_id']])) {
		   for ($j=0; $j < count($gruppenBestellintervallUntereGrenze[$produkt_row['produkt_id']]); $j++) {
			$ug = $gruppenBestellintervallUntereGrenze[$produkt_row['produkt_id']][$j];
			$og = $gruppenBestellintervallObereGrenze[$produkt_row['produkt_id']][$j];
			$gebindeEnde = $gesamtMengeBestellt;

			if ($ug >= $gebindeAnfang && $ug <= $gebindeEnde) {  // untere Grenze des Bestellintervalls im aktuellen Gebinde...
			     if ($og >= $gebindeAnfang && $og <= $gebindeEnde)   { // und die obere Grenze auch dann...
				$gruppenMengeInGebinde[$i] += 1 + $og - $ug;
			     } else {   // und die obere Grenze nicht, dann ...
				$gruppenMengeInGebinde[$i] += 1 + $gebindeEnde - $ug;    // alles bis zum Intervallende
			     }
			} else if ($og >= $gebindeAnfang && $og <= $gebindeEnde) {  // die obere Grenze des Bestellintervalls im aktuellen Gebinde, und die untere nicht, dann...
				$gruppenMengeInGebinde[$i] += 1 + $og - $gebindeAnfang;    // alles ab Intervallanfang bis obere Grenze
			} else if ($ug < $gebindeAnfang && $og > $gebindeEnde) { //die untere Grenze des Bestellintervalls unterhalb und die obere oberhalb des aktuellen Gebindes, dann..
			 	$gruppenMengeInGebinde[$i] += 1 + $gebindeEnde - $gebindeAnfang;    // das gesamte Gebinde
			}
		   }
	      }
	      $gruppeGesamtMengeInGebinden += $gruppenMengeInGebinde[$i];
	  }
				
	  // versuche offenes Gebinde mit Toleranzmengen zu füllen							
	  $gruppenToleranzInGebinde     = 0;
	  $toleranzGebNr = -1;
		
	  if ($rest_menge != 0) {
		$fuellmenge = $gebindegroessen[count($gebindegroessen)-1] - $rest_menge;
		if (isset($toleranzenNachGruppen) && $fuellmenge <= $gesamtBestellmengeToleranz[$produkt_row['produkt_id']]) {
			//echo "<p>toleranzenNachGruppen: ".$toleranzenNachGruppen."</p>";
			//echo "<p>isset(toleranzenNachGruppen): ";
			//if(isset($toleranzenNachGruppen)) echo "true";
			//else echo "false";
			//echo "</p>";
			reset($toleranzenNachGruppen);
			do {
			    while (!(list($key, $value) = each($toleranzenNachGruppen))) reset($toleranzenNachGruppen);   // neue Wete auslesen und ggf. wieder am Anfang des Arrays starten

			    if ($value > 0) { 
				$toleranzenNachGruppen[$key] --;
				$fuellmenge--;
				if ($key == $gruppen_id) $gruppenToleranzInGebinde++;
			    }
										
										
			} while($fuellmenge > 0);
								 
			// das "toleranzgefüllte" Gebinde anzeigen
			$toleranzGebNr = count($festeGebindeaufteilung)-1;
								 
			$festeGebindeaufteilung[count($festeGebindeaufteilung)-1]++;
			$gruppenMengeInGebinde[$toleranzGebNr] += $gruppenBestellmengeFest[$produkt_row['produkt_id']]  - $gruppeGesamtMengeInGebinden;
			$gruppenMengeInGebinde[$toleranzGebNr] += $gruppenToleranzInGebinde;
			$gruppeGesamtMengeInGebinden = $gruppenBestellmengeFest[$produkt_row['produkt_id']];
			$toleranzFuellung = count($gebindegroessen) -1;
								 
			// Gebindeaufteillung an Toleranzfüllung anpassen...
			$anzInAktGeb = $festeGebindeaufteilung[$toleranzGebNr] * $gebindegroessen[$toleranzGebNr];											 

			for ($i = count($gebindegroessen)-2; $i >= 0 ; $i--)
			if (($anzInAktGeb % $gebindegroessen[$i]) == 0) {
			   	$gruppenMengeInGebinde[$i] += $gruppenMengeInGebinde[$toleranzGebNr];
				$gruppenMengeInGebinde[$toleranzGebNr] = 0;
				$festeGebindeaufteilung[$i] += floor($anzInAktGeb / $gebindegroessen[$i]);
				$festeGebindeaufteilung[$toleranzGebNr] = 0;
				$toleranzGebNr = $i;
				$anzInAktGeb = $festeGebindeaufteilung[$toleranzGebNr] * $gebindegroessen[$toleranzGebNr];
			}
								 
		}
	  }

	$gruppenToleranzNichtInGebinde = $gruppenBestellmengeToleranz[$produkt_row['produkt_id']] - $gruppenToleranzInGebinde;
	$gruppeGesamtMengeNichtInGebinden = $gruppenBestellmengeFest[$produkt_row['produkt_id']]  - $gruppeGesamtMengeInGebinden;
	$dr_bestellen = $gruppeGesamtMengeInGebinden +$gruppenToleranzInGebinde;
	
	//Hier können Verteilmengen geschrieben werden
	writeVerteilmengen_sql($dr_bestellen, $gruppenbestellung_id, $produkt_row['produkt_id']);
     }
  }
  	writeLiefermenge_sql($bestell_id);
	if(!verteilmengenLoeschen($bestell_id, TRUE))
		error(__LINE__,__FILE__,"Konnte basareinträge  nicht löschen..","")	;
	
	changeState($bestell_id, STATUS_LIEFERANT);
}

global $masseinheiten;
$masseinheiten = array( 'g', 'ml', 'ST', 'KI', 'PA', 'GL', 'BE', 'DO', 'BD', 'BT', 'KT', 'FL' );

// kanonische_einheit: zerlegt $einheit in kanonische einheit und masszahl:
// 
function kanonische_einheit( $einheit, &$kan_einheit, &$kan_mult ) {
  global $masseinheiten;
  $kan_einheit = NULL;
  $kan_mult = NULL;
  sscanf( $einheit, "%f", &$kan_mult );
  if( $kan_mult ) {
    // masszahl vorhanden, also abspalten:
    sscanf( $einheit, "%f%s", &$kan_mult, &$einheit );
  } else {
    // keine masszahl, also eine einheit:
    $kan_mult = 1;
  }
  $einheit = substr( str_replace( ' ', '', strtolower($einheit) ), 0, 2);
  switch( $einheit ) {
    //
    // gewicht immer in gramm:
    //
    case 'kg':
      $kan_einheit = 'g';
      $kan_mult *= 1000;
      break;
    case 'g':
    case 'gr':
      $kan_einheit = 'g';
      break;
    //
    // volumen immer in ml:
    //
    case 'l':
    case 'lt':
    case 'li':
      $kan_einheit = 'ml';
      $kan_mult *= 1000;
      break;
    case 'ml':
      $kan_einheit = 'ml';
      break;
    //
    // PAckung und KIste: wenn liefer-einheit:
    // - die verteileinheit darf dann STueck sein; dann bedeutet die
    //    gebindegroesse STueck pro KIste oder PAckung
    //    (annahme: wir koennen einzelne KI oder PA bestellen)
    // - andernfalls muss die verteileinheit ebenfalls KI oder PA sein
    //
    case 'pa':
      $kan_einheit = 'PA';
      break;
    case 'ki':
      $kan_einheit = 'KI';
      break;
    default:
      //
      // der rest sind zaehleinheiten (STueck und aequivalent):
      //
      foreach( $masseinheiten as $e ) {
        if( strtolower( $e ) == $einheit ) {
          $kan_einheit = $e;
          break 2;
        }
      }
      $kan_einheit = $einheit;
      //  echo "<div class='warn'>Einheit unbekannt: '$kan_einheit'</div>";
      $kan_einheit = false;
      return false;
  }
  return true;
}

function optionen_einheiten( $selected ) {
  global $masseinheiten;
  foreach( $masseinheiten as $e ) {
    echo "<option value='$e'";
    if( $e == $selected )
      echo " selected";
    echo ">$e</option>";
  }
}

// preisdaten setzen:
// berechnet und setzt einige weitere nuetzliche eintraege einer 'produktpreise'-Zeile:
//
function preisdatenSetzen( &$pr /* a row from produktpreise */ ) {
  kanonische_einheit( $pr['verteileinheit'], &$pr['kan_verteileinheit'], &$pr['kan_verteilmult'] );
  kanonische_einheit( $pr['liefereinheit'], &$pr['kan_liefereinheit'], &$pr['kan_liefermult'] );

  if( $pr['kan_liefereinheit'] and $pr['kan_verteileinheit'] ) {
    if( $pr['kan_liefereinheit'] != $pr['kan_verteileinheit'] ) {
      $pr['preiseinheit'] = "{$pr['kan_liefereinheit']} (". $pr['gebindegroesse'] * $pr['kan_verteilmult'] . " {$pr['kan_verteileinheit']})";
      if( $pr['kan_liefermult'] != 1 ) {
        $pr['preiseinheit'] = $pr['kan_liefermult'] . " " . $pr['preiseinheit'];
      }
      $pr['mengenfaktor'] = $pr['gebindegroesse'];
    } else {
      switch( $pr['kan_liefereinheit'] ) {
        case 'g':
          $pr['preiseinheit'] = 'kg';
          $pr['mengenfaktor'] = 1000.0 / $pr['kan_verteilmult'];
          break;
        case 'ml':
          $pr['preiseinheit'] = 'L';
          $pr['mengenfaktor'] = 1000 / $pr['kan_verteilmult'];
          break;
        default:
          $pr['preiseinheit'] = $pr['kan_liefereinheit'];
          $pr['mengenfaktor'] = 1.0 / $pr['kan_verteilmult'];
          break;
      }
    }
  } else {
    $pr['preiseinheit'] = false;
    $pr['mengenfaktor'] = 1.0;
  }
  $pr['preis_rund'] = sprintf( "%8.2lf", $pr['preis'] );
  $pr['nettopreis'] = ( $pr['preis'] - $pr['pfand'] ) / ( 1.0 + $pr['mwst'] / 100.0 );
  $pr['lieferpreis'] = sprintf( "%8.2lf", $pr['nettopreis'] * $pr['mengenfaktor'] );
}

// get_http_var: bisher definierte $typ argumente:
//   M : Wert beliebig, wird aber durch mysql_real_escape_string fuer MySQL verdaulich gemacht
//
function get_http_var( $name, $typ = '' ) {
  global $$name, $HTTP_GET_VARS, $HTTP_POST_VARS;
  if( isset( $HTTP_GET_VARS[$name] ) ) {
    $$name = $HTTP_GET_VARS[$name];
  } elseif( isset( $HTTP_POST_VARS[$name] ) ) {
    $$name = $HTTP_POST_VARS[$name];
  } else {
    unset( $$name );
    return FALSE;
  }
  switch( $typ ) {
    case 'M':
      $$name = mysql_real_esacape_string( $$name );
      break;
    default:
      break;
  }
  return TRUE;
}
function need_http_var( $name, $typ = '' ) {
  if( ! get_http_var( $name, $typ ) ) {
    error( __FILE__, __LINE__, "variable $name nicht uebergeben" );
    exit();
  }
  return TRUE;
}

function fail_if_readonly() {
  global $readonly;
  if( $readonly ) {
    echo "<div class='warn'>Datenbank ist schreibgesch&uuml;tzt - Operation nicht m&ouml;glich!</div></body></html>";
    exit();
  }
}

function wikiLink( $topic, $text ) {
  global $foodsoftpath;
  echo "<a class='wikilink' target='wiki' href='/wiki/doku.php?id=$topic'>$text</a>";
}

?>
