<?php

////////////////////////////////////
//
// debugging und fehlerbehandlung:
//
////////////////////////////////////

global $from_dokuwiki;
$from_dokuwiki or   // dokuwiki hat viele, viele "undefined variable"s !!!
  error_reporting(E_ALL); // alle Fehler anzeigen

//Debug LEVEL_
 define('LEVEL_NEVER',  5);
 define('LEVEL_ALL',  4);
 define('LEVEL_MOST',  3);
 define('LEVEL_IMPORTANT',  2);  //All UPDATE and INSERT statments should have level important
 define('LEVEL_KEY',  1);
 define('LEVEL_NONE',  0);
 $_SESSION['LEVEL_CURRENT'] = LEVEL_NONE;

function doSql($sql, $debug_level = LEVEL_IMPORTANT, $error_text = "Datenbankfehler: " ){
	if($debug_level <= $_SESSION['LEVEL_CURRENT']) echo "<p>".$sql."</p>";
	$result = mysql_query($sql) or
	error(__LINE__,__FILE__,$error_text."(".$sql.")",mysql_error(), debug_backtrace());
	return $result;

}
function sql_select_single_row( $sql ) {
  $result = doSql( $sql );
  need( mysql_num_rows($result) == 1 );
  return mysql_fetch_array($result);
}

function need( $exp, $comment = "Fataler Fehler" ) {
  global $print_on_exit;
  if( ! $exp ) {
    echo "<div class='warn'>$comment</div>$print_on_exit";
    exit();
  }
  return true;
}


function fail_if_readonly() {
  global $readonly;
  if( $readonly ) {
    echo "
      <div class='warn'>Datenbank ist schreibgesch&uuml;tzt - Operation nicht m&ouml;glich!</div>
      $print_on_exit
    ";
    exit();
  }
}

function adefault( $array, $index, $default ) {
  if( isset( $array[$index] ) )
    return $array[$index];
  else
    return $default;
}

function mysql2array( $result, $key, $val ) {
  $r = array();
  while( $row = mysql_fetch_array( $result ) ) {
    need( isset( $row[$key] ) );
    need( isset( $row[$val] ) );
    $r[$row[$key]] = $row[$val];
  }
  return $r;
}



/*
ALTER TABLE `gesamtbestellungen` ADD `state` ENUM( 'bestellen', 'beimLieferanten', 'Verteilt', 'archiviert' ) NOT NULL DEFAULT 'bestellen';

ALTER TABLE `gesamtbestellungen` ADD INDEX ( `state` ) ;
*/
 define('STATUS_BESTELLEN', "bestellen");
 define('STATUS_LIEFERANT', "beimLieferanten");
 define('STATUS_VERTEILT', "Verteilt");
 define('STATUS_ARCHIVIERT', "archiviert");

 define('SQL_FILTER_SCHULDVERHAELTNIS'
   ,"(gesamtbestellungen.state in ('".STATUS_LIEFERANT."','".STATUS_VERTEILT."'))"
 );

////////////////////////////////////
//
// dienstplan-funktionen:
//
////////////////////////////////////

 $_SESSION['DIENSTEINTEILUNG'] =  array('1/2', '3', '4', '5', 'freigestellt');
 $_SESSION['ALLOWED_ORDER_STATES'] = array(
	     'lieferschein' => array(STATUS_VERTEILT, STATUS_LIEFERANT),
	     'bestellschein' => array(STATUS_BESTELLEN, STATUS_LIEFERANT),
	     'bestellt_faxansicht' => array(STATUS_BESTELLEN, STATUS_LIEFERANT),
	     'verteilung' => array(STATUS_LIEFERANT,STATUS_VERTEILT),
	     'bestellungen_overview' => array(STATUS_BESTELLEN, STATUS_LIEFERANT,STATUS_VERTEILT, STATUS_ARCHIVIERT),
	     'konsument' => array(STATUS_BESTELLEN, STATUS_LIEFERANT,STATUS_VERTEILT, STATUS_ARCHIVIERT),
	     'check_balanced' => array(STATUS_VERTEILT),
	     'archiv' => array(STATUS_ARCHIVIERT)
	 );


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
/**
 *
 */
function in_two_weeks(){
     //Now
     $date = date_sql2intern(strftime("%Y-%m-%d %H:%M:%s"));
     //Correct format
    $toreturn = sql_add_days_to_date($date, 19);
    return $toreturn;
}

if(!function_exists("date_parse")){
function date_parse($date_in){
  echo "<!-- date_parse: $date_in -->";
	$temp = explode(" ", $date_in);
	
   $date = explode("-", $temp[0]);
   if( count($date) == 3 ) {
     $toReturn = array( "year" => $date[0],
				   "month" => $date[1],
				   "day" => $date[2]);
   } else {
	      $toReturn["year"] =  date('Y');
	      $toReturn["month"] =  date('m');
	      $toReturn["day"] =  date('d');
   }

	 if(isset($temp[1])){
           $time = explode(":", $temp[1]);
	 }
   if( isset($time) && count($time) == 3 ) {
	      $toReturn["hour"] =  $time[0];
	      $toReturn["minute"] =  $time[1];
	      $toReturn["second"] =  $time[2];
	 } else {
	      $toReturn["hour"] =  "00";
	      $toReturn["minute"] =  "00";
	      $toReturn["second"] =  "00";
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
$areas[] = array("area" => "index.php?area=bestellungen_overview",
	"hint" => "Auflistung aller Bestellungen mit Status und Links",
	"title" => "Alle Bestellungen");
$areas[] = array("area" => "index.php?area=bestellen",
	"hint" => "Hier können die einzelnen Gruppen an den aktuellen Bestellung Teilnehmen....",
	"title" => "Bestellen");

$areas[] = array("area" => "index.php?area=bilanz",
	"hint" => "Finanzen der FC: Überblick und Verwaltung",
	"title" => "Bilanz");

if($hat_dienst_IV){
	$areas[] = array("area" => "index.php?area=produkte",
	"hint" => "Neue Produkte eingeben ... Preise verwalten ... Bestellung online stellen","title" => "Produktdatenbank");	 
} 
	$areas[] = array("area" => "index.php?area=gruppen",
	"hint" => "Hier kann man die Bestellgruppen und deren Konten verwalten...",
	"title" => "Gruppen");		
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
//
function check_password( $gruppen_id, $gruppen_pwd ) {
  global $crypt_salt;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    $sql="SELECT * FROM bestellgruppen WHERE id='$gruppen_id' AND aktiv=1";
    //do not show because this happens before header
    $result = doSql($sql, LEVEL_NEVER, "Suche nach Bestellgruppe fehlgeschlagen..");
    $row = mysql_fetch_array($result);
    if( $row['passwort'] == crypt($gruppen_pwd,$crypt_salt) )
      return $row;
  }
  return false;
}

function set_password( $gruppen_id, $gruppen_pwd ) {
  global $crypt_salt;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    fail_if_readonly();
    ( $gruppen_id == $login_gruppen_id ) or nur_fuer_dienst_V();
    $query= "UPDATE bestellgruppen SET passwort='"
       . mysql_real_escape_string(crypt($gruppen_pwd,$crypt_salt))
       . "' WHERE id='$gruppen_id'";
    doSql($query, LEVEL_IMPORTANT, "Setzen des Gruppenpassworts fehlgeschlagen...");

  }
}

////////////////////////////////////
//
// dienstkontrollblatt-Funktionen:
//
////////////////////////////////////

function dienstkontrollblatt_eintrag( $dienstkontrollblatt_id, $gruppen_id, $dienst, $name, $telefon, $notiz, $datum = '', $zeit = '' ) {
  if( $dienstkontrollblatt_id ) {
    mysql_query( "
      UPDATE dienstkontrollblatt SET
        name = " . ( $name ? "'$name'" : "name" ) . "
      , telefon = " . ( $telefon ? "'$telefon'" : "telefon" ) . "
      , notiz = IF( notiz = '$notiz', notiz, CONCAT( notiz, ' --- $notiz' ) )
      WHERE id='$dienstkontrollblatt_id'
    " ) or error( __LINE__,__FILE__,"Eintrag im Dienstkontrollblatt fehlgeschlagen: ", mysql_error() );
    return $dienstkontrollblatt_id;
  } else {
    mysql_query( "
      INSERT INTO dienstkontrollblatt (
          gruppen_id
        , dienst
        , telefon
        , name
        , notiz
        , datum
        , zeit
      ) VALUES (
          '$gruppen_id'
        , '$dienst'
        , '$telefon'
        , '$name'
        , '$notiz'
        , " . ( $datum ? "'$datum'" :  "CURDATE()" ) . "
        , " . ( $zeit ? "'$zeit'" :  "CURTIME()" ) . "
      )
      ON DUPLICATE KEY UPDATE
          name = " . ( $name ? "'$name'" : "name" ) . "
        , telefon = " . ( $telefon ? "'$telefon'" : "telefon" ) . "
        , notiz = CONCAT( notiz, ' --- ', '$notiz' )
        , zeit = CURTIME()
        , id = LAST_INSERT_ID(id)
    " ) or error( __LINE__,__FILE__,"Eintrag im Dienstkontrollblatt fehlgeschlagen: ", mysql_error() );
    return mysql_insert_id();
    //  WARNING: ^ does not always work (see http://bugs.mysql.com/bug.php?id=27033)
    //  (fixed in mysql-5.0.45)
  }
}

function dienstkontrollblatt_select( $from_id = 0, $to_id = 0 ) {
  $to_id or $to_id = $from_id;
  $where = '';
  if( $from_id ) {
    $where = "WHERE (dienstkontrollblatt.id >= $from_id) and (dienstkontrollblatt.id <= $to_id)";
  }
  $result = mysql_query( "
    SELECT *
     , bestellgruppen.id as gruppen_id
     , bestellgruppen.name as gruppen_name
     , dienstkontrollblatt.id as id
     , dienstkontrollblatt.name as name
     , dienstkontrollblatt.telefon as telefon
    FROM dienstkontrollblatt
    INNER JOIN bestellgruppen ON ( bestellgruppen.id = dienstkontrollblatt.gruppen_id )
    $where
    ORDER BY dienstkontrollblatt.id
  " ) or error( __LINE__, __FILE__, "Suche in dienstkontrollblatt fehlgeschlagen: ", mysql_error() );
  return $result;
}


////////////////////////////////////
//
// bestellgruppen-funktionen:
//
////////////////////////////////////

function sql_basar_id(){
  global $basar_id;
  need( $basar_id );
  return $basar_id;
}

function sql_muell_id(){
  global $muell_id;
  need( $muell_id );
  return $muell_id;
}


function sql_gruppendaten( $gruppen_id ) {
  return sql_select_single_row( "SELECT * FROM bestellgruppen WHERE id='$gruppen_id'" );
}
function sql_gruppenname($gruppen_id){
  $row = sql_gruppendaten( $gruppen_id );
  return $row['name'];
}

function subquery_aktive_bestellgruppen() {
  return " (
    SELECT *
    FROM bestellgruppen
    WHERE (bestellgruppen.aktiv = 1)
          AND NOT (bestellgruppen.id IN ( ".sql_basar_id().", ".sql_muell_id()." ) )
  ) ";
}
function sql_aktive_bestellgruppen() {
  return doSql( subquery_aktive_bestellgruppen() );
}

/*
 * sql_gruppen: SELECT
 * - alle aktiven gruppen, oder
 * - alle an einer gesamtbestellung beteiligten gruppen, oder
 * - alle an bestellung/zuteilung eines produktes einer gesamtbestellung beteligten gruppen
 */
function sql_gruppen($bestell_id=FALSE, $produkt_id=FALSE){
        if($bestell_id===FALSE && $produkt_id===FALSE){
                $query="SELECT * FROM bestellgruppen WHERE aktiv=1 ORDER by (id%1000)";
        } else if($produkt_id===FALSE) {
            $query="SELECT bestellgruppen.id, bestellgruppen.name, gruppenbestellungen.id as gruppenbestellungen_id
                FROM bestellgruppen
                INNER JOIN gruppenbestellungen
                ON (gruppenbestellungen.bestellguppen_id = bestellgruppen.id)
                WHERE gruppenbestellungen.gesamtbestellung_id = $bestell_id
                GROUP BY bestellgruppen.id
                ORDER BY ( bestellgruppen.id % 1000 )
                "; 
        } else {
                $query=
    " SELECT gruppenbestellungen.bestellguppen_id as id
           , bestellgruppen.name as name
      FROM bestellzuordnung
      INNER JOIN gruppenbestellungen
              ON gruppenbestellungen.id=bestellzuordnung.gruppenbestellung_id
      INNER JOIN bestellgruppen
              ON bestellgruppen.id=gruppenbestellungen.bestellguppen_id
      WHERE     gruppenbestellungen.gesamtbestellung_id='$bestell_id'
            AND bestellzuordnung.produkt_id='$produkt_id'
      GROUP BY bestellgruppen.id
      ORDER BY ( bestellgruppen.id % 1000 )
    ";
	}
        $result = doSql($query, LEVEL_ALL, "Konnte Bestellgruppendaten nicht aus DB laden..");
	return $result;
}

function optionen_gruppen(
  $bestell_id = false
, $produkt_id = false
, $selected = false
, $option_0 = false       /* erzeuge option value='0' mit diesem titel (z.b. 'Alle') */
, $allowedgroups = false  /* array erlaubter gruppen_ids */
, $additionalgroups = array() /* zusaetzlich in jedem fall anzubietende gruppen (z.b. basar) */
) {
  global $specialgroups;
  if( $allowedgroups )
    if( ! is_array( $allowedgroups ) )
      $allowedgroups = array( $allowedgroups );
  if( ! is_array( $additionalgroups ) )
    $additionalgroups = array( $additionalgroups );
  $gruppen = sql_gruppen($bestell_id,$produkt_id);
  $output='';
  if( $option_0 ) {
    $output = "<option value='0'";
    if( $selected == 0 ) {
      $output = $output . " selected";
      $selected = -1;
    }
    $output = $output . ">$option_0</option>";
  }
  while($gruppe = mysql_fetch_array($gruppen)){
    $id = $gruppe['id'];
    if( ! in_array( $id, $additionalgroups ) ) {
      if( in_array( $id, $specialgroups ) )
        continue;
      if( $allowedgroups and ! in_array( $id, $allowedgroups ) )
        continue;
    }
    $output = "$output
      <option value='$id'";
    if( $selected == $id ) {
      $output = $output . " selected";
      $selected = -1;
    }
    $output = $output . ">{$gruppe['name']}</option>";
  }
  if( $selected >=0 ) {
    // $selected stand nicht zur Auswahl; vermeide zufaellige Anzeige:
    $output = "<option value='0' selected>(bitte Gruppe wählen)</option>" . $output;
  }
  return $output;
}

////////////////////////////////////
//
// lieferanten-funktionen:
//
////////////////////////////////////

function sql_lieferanten( $id = false ) {
  $where = ( $id ? "WHERE id=$id" : "" );
  return doSql( "SELECT * FROM lieferanten $where", LEVEL_ALL, "Suche nach Lieferanten fehlgeschlagen: " );
}

/**
 *   Infos zu Lieferant abfragen
 */
function sql_getLieferant($lieferant_id){
  $result = sql_lieferanten( $lieferant_id );
  need( mysql_num_rows( $result ) == 1 );
  return mysql_fetch_array($result);
}

function lieferant_name($id){
  $infos = sql_getLieferant($id);
  return $infos["name"];
}

function optionen_lieferanten( $selected = false, $option_0 = false ) {
  $lieferanten = sql_lieferanten();
  $output = "";
  if( $option_0 ) {
    $output = "<option value='0'";
    if( $selected == 0 ) {
      $output .= " selected";
      $selected = -1;
    }
    $output .= ">$option_0</option>";
  }
  while( $lieferant = mysql_fetch_array($lieferanten) ) {
    $id = $lieferant['id'];
    $output .= "<option value='$id'";
    if( $selected == $id ) {
      $output .= " selected";
      $selected = -1;
    }
    $output .= ">{$lieferant['name']}</option>";
  }
  if( $selected >=0 ) {
    // $selected stand nicht zur Auswahl; vermeide zufaellige Anzeige:
    $output = "<option value='0' selected>(bitte Lieferant wählen)</option>" . $output;
  }
  return $output;
}



////////////////////////////////////
//
// funktionen fuer gesamtbestellung, bestellvorschlaege und gruppenbestellungen:
//
////////////////////////////////////

function getState($bestell_id){
  $row = mysql_select_single_row( "SELECT state FROM gesamtbestellungen WHERE id=$bestell_id" );
  return $row['state'];
}

/**
 *
 */
function getProduzentBestellID($bestell_id){
    if($bestell_id==0) {error(__LINE__,__FILE__,"Do not call getProduzentBestellID with bestell_id null)", "bla");}
    $sql="SELECT DISTINCT lieferanten_id FROM bestellvorschlaege 
		INNER JOIN produkte ON (produkt_id = produkte.id)
		WHERE gesamtbestellung_id = ".$bestell_id;
    $result = doSql($sql, LEVEL_ALL, "Konnte Preise nicht aus DB laden..");
    if (mysql_num_rows($result) > 1)
	    echo error(__LINE__,__FILE__,"Mehr als ein Lieferant fuer Bestellung ".$bestell_id);
	 else {
	    $row = mysql_fetch_array($result);
	    return $row['lieferanten_id'];

	 }
}

/**
 *  changeState: 
 *   - fuehrt erlaubte Statusaenderungen einer Bestellung aus
 *   - ggf. werden Nebenwirkungen, wie verteilmengenZuweisen, ausgeloest
 */
function changeState($bestell_id, $state){
  global $mysqljetzt;

  $bestellung = sql_bestellung( $bestell_id );

  $current = $bestellung['state'];
  if( $current == $state )
    return true;

  fail_if_readonly();
  nur_fuer_dienst(1,3,4);

  $do_verteilmengen_zuweisen = false;
  $changes = "state = '$state'";
  switch( "$current,$state" ){
    case STATUS_BESTELLEN . "," . STATUS_LIEFERANT:
      $do_verteilmengen_zuweisen = true;  // erst nach statuswechsel ausfuehren!
      if( $bestellung['bestellende'] > $mysqljetzt )
        $changes .= ", bestellende=NOW()";
      break;
    case STATUS_LIEFERANT . "," . STATUS_BESTELLEN:
      verteilmengenLoeschen( $bestell_id );
      break;
    case STATUS_LIEFERANT . "," . STATUS_VERTEILT:
      $changes .= ", lieferung=NOW()";   // TODO: eingabe erlauben?
      break;
    case STATUS_VERTEILT . "," . STATUS_ARCHIVIERT:
      // TODO: tests:
      //   - bezahlt?
      //   - basarreste?
      break;
    default:
      error(__LINE__,__FILE__, "Ungültiger Statuswechsel");
      return false;
  }
  $sql = "UPDATE gesamtbestellungen SET $changes WHERE id = $bestell_id";
  $result = doSql($sql, LEVEL_KEY, "Konnte status der Bestellung ändern..");
  if( $result ) {
    if( $do_verteilmengen_zuweisen )
      verteilmengenZuweisen( $bestell_id );
  }
  return $result;
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
	 $query .= " ORDER BY bestellende DESC,name";
	$result = doSql(  $query, LEVEL_ALL,"Konnte Gesamtbestellungen nich aus DB laden.. ");
	return $result;
}

function sql_bestellung( $id ) {
  $result = sql_bestellungen( false, false, $id );
  if( ! $result or mysql_num_rows( $result ) != 1 ) {
    error( __LINE__, __FILE__, "Lesen der Gesamtbestellung fehlgeschlagen" );
    exit();
  }
  return mysql_fetch_array( $result );
}

/**
 *  Gesamtbestellung einfügen
 */
function sql_insert_bestellung($name, $startzeit, $endzeit, $lieferung){
  fail_if_readonly();
  nur_fuer_dienst_IV();
   $sql = "INSERT INTO gesamtbestellungen (name, bestellstart, bestellende, lieferung) 
           VALUES ('".mysql_escape_string($name)."', '".
	              mysql_escape_string($startzeit)."', '".
	              mysql_escape_string($endzeit)."', '".
		      mysql_escape_string($lieferung)."')";
  doSql($sql, LEVEL_IMPORTANT, "Konnte Gesamtbestellung nicht aufnehmen.");
}

function sql_update_bestellung($name, $startzeit, $endzeit, $lieferung, $bestell_id ){
  fail_if_readonly();
  nur_fuer_dienst_IV();
  $sql = "
    UPDATE gesamtbestellungen
    SET name = '" . mysql_escape_string($name) . "'
      , bestellstart='$startzeit'
      , bestellende='$endzeit'
      , lieferung='$lieferung'
    WHERE id=$bestell_id
  ";
  return doSql($sql, LEVEL_IMPORTANT, "Update Gesamtbestellung fehlgeschlagen");
}

/**
 *  Bestellvorschläge einfügen
 */
function sql_insert_bestellvorschlaege(
  $produkt_id
, $gesamtbestellung_id
, $preis_id = 0
, $bestellmenge = 0, $liefermenge = 0
) {
  global $hat_dienst_IV;

  fail_if_readonly();

  // finde NOW() aktuellen preis:
  if( ! $preis_id )
    $preis_id = sql_aktueller_produktpreis_id( $produkt_id );

  // kludge alert: finde erstmal irgendeinen preis...
  if( ! $preis_id )
    if( $hat_dienst_IV )
      $preis_id = sql_aktueller_produktpreis_id( $produkt_id, false );

  if( ! $preis_id ) {
    error( "Eintrag Bestellvorschlag fehlgeschlagen: kein Preiseintrag gefunden!" );
    return false;
  }
  $sql = "
    INSERT INTO bestellvorschlaege
      (produkt_id, gesamtbestellung_id, produktpreise_id, bestellmenge, liefermenge )
    VALUES ($produkt_id, $gesamtbestellung_id, $preis_id, $bestellmenge, $liefermenge )
    ON DUPLICATE KEY UPDATE produktpreise_id = $preis_id
                          , bestellmenge = bestellmenge + $bestellmenge
                          , liefermenge = liefermenge + $liefermenge
  ";
  return doSql($sql, LEVEL_IMPORTANT, "Konnte Bestellvorschlag nicht aufnehmen.");
}

function sql_bestellvorschlag_daten($bestell_id, $produkt_id){
	  $query=
    " SELECT * , produktpreise.id as preis_id
               , produkte.name as produkt_name
               , gesamtbestellungen.name as name
      FROM gesamtbestellungen
      INNER JOIN bestellvorschlaege
              ON bestellvorschlaege.gesamtbestellung_id=gesamtbestellungen.id
      INNER JOIN produkte
              ON produkte.id=bestellvorschlaege.produkt_id
      INNER JOIN produktpreise
              ON produktpreise.id=bestellvorschlaege.produktpreise_id
      WHERE     gesamtbestellungen.id='$bestell_id'
            AND bestellvorschlaege.produkt_id='$produkt_id'
	    ";
    $result= doSql($query, LEVEL_ALL, "Suche in gesamtbestellungen,bestellvorschlaege fehlgeschlagen");
    return mysql_fetch_array($result)  ;
}

function sql_bestellpreis($bestell_id, $produkt_id){
	$row = sql_bestellvorschlag_daten($bestell_id, $produkt_id);
	return $row['preis_id'];
}

function sql_create_gruppenbestellung($gruppe, $bestell_id){
  fail_if_readonly();
  $sql = "
    INSERT INTO gruppenbestellungen (bestellguppen_id, gesamtbestellung_id)
    VALUES ($gruppe, $bestell_id)
    ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
  ";
  doSql($sql, LEVEL_IMPORTANT, "Konnte Gruppenbestellung nicht in DB ändern...");
  return mysql_insert_id();
}


////////////////////////////////////
//
// funktionen fuer bestellmengen und verteil/liefermengen
//
////////////////////////////////////

function sql_liefermenge($bestell_id,$produkt_id){
  $row = sql_select_single_row( "
    SELECT liefermenge FROM bestellvorschlaege
    WHERE (gesamtbestellung_id='$bestell_id') and (produkt_id='$produkt_id')
  " );
  return $row['liefermenge'];
}

function select_verteilmengen(){
  return "
    SELECT IFNULL(sum(menge),0.0) as verteilmenge
         , gesamtbestellung_id
         , produkt_id
    FROM bestellzuordnung
    INNER JOIN gruppenbestellungen
       ON bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id
    WHERE art = 2
    GROUP BY gesamtbestellung_id , produkt_id
  ";
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
        $result = doSql($query, LEVEL_ALL, "Konnte Bestellmengen nich aus DB laden..");
	return $result;
}

function sql_bestellprodukte( $bestell_id, $gruppen_id=false, $produkt_id=false ){
  $basar_id = sql_basar_id();
  $state = getState( $bestell_id );

  // zur information, vor allem im "vorlaeufigen Bestellschein", auch Bestellmengen berechnen:
  $gesamtbestellmenge_expr = "
    ifnull( sum(bestellzuordnung.menge * IF(bestellzuordnung.art<2,1,0) ), 0.0 )
  ";
  // basarbestellmenge: _eigentliche_ basarbestellungen sind art=1,
  // basar mit art=0 zaehlt wie gewoehnliche festmenge!
  $basarbestellmenge_expr = "
    ifnull( sum(bestellzuordnung.menge * IF(gruppenbestellungen.bestellguppen_id=$basar_id,1,0)
                               * IF(bestellzuordnung.art=1,1,0) ), 0.0 )
  ";
  $toleranzbestellmenge_expr = "
    ifnull( sum(bestellzuordnung.menge * IF(gruppenbestellungen.bestellguppen_id=$basar_id,0,1)
                               * IF(bestellzuordnung.art=1,1,0) ), 0.0 )
  ";
  $verteilmenge_expr = "
    ifnull( sum(bestellzuordnung.menge * IF(bestellzuordnung.art=2,1,0) ), 0.0 )
  ";

  // tatsaechlich bestellte oder gelieferte produkte werden vor solchen mit
  // menge 0 angezeigt; dafuer einen sortierbaren ausdruck definieren:
  switch($state) {
    case STATUS_BESTELLEN:
    case STATUS_LIEFERANT:
      // eigentlich wollen wir "ORDER BY if(gesamtbestellmenge>0,0,1),... "
      // das geht aber so nicht ("reference to group function ... not supported"),
      // deshalb ein extra feld:
      $firstorder_expr = $gesamtbestellmenge_expr;
      break;
    default:
      if( $gruppen_id )
        $firstorder_expr = $verteilmenge_expr;
      else
        $firstorder_expr = "liefermenge";
      break;
  }
  $query = "SELECT *
    , produkte.name as produkt_name, produktgruppen.name as produktgruppen_name
    , produktpreise.liefereinheit as liefereinheit
    , produktpreise.verteileinheit as verteileinheit
    , produktpreise.gebindegroesse as gebindegroesse
    , $gesamtbestellmenge_expr as gesamtbestellmenge
    , $basarbestellmenge_expr  as basarbestellmenge
    , $toleranzbestellmenge_expr as toleranzbestellmenge
    , $verteilmenge_expr as verteilmenge
    , IF( $firstorder_expr > 0, 0, 1 ) as menge_ist_null
  FROM bestellvorschlaege
  INNER JOIN produkte
    ON (produkte.id=bestellvorschlaege.produkt_id)
  INNER JOIN produktpreise 
    ON (produktpreise.id=bestellvorschlaege.produktpreise_id)
  INNER JOIN produktgruppen
    ON (produktgruppen.id=produkte.produktgruppen_id)
  INNER JOIN gruppenbestellungen
    ON (gruppenbestellungen.gesamtbestellung_id=$bestell_id)
  INNER JOIN bestellzuordnung
    ON (bestellzuordnung.produkt_id=bestellvorschlaege.produkt_id
        and bestellzuordnung.gruppenbestellung_id=gruppenbestellungen.id)
  WHERE bestellvorschlaege.gesamtbestellung_id=$bestell_id
  "
   . ( $gruppen_id ? " and gruppenbestellungen.bestellguppen_id=$gruppen_id " : "" )
   . ( $produkt_id ? " and produkte.id=$produkt_id " : "" )
  . "
  GROUP BY bestellvorschlaege.produkt_id
  "
   . ( $gruppen_id ? " HAVING gesamtbestellmenge<>0 or verteilmenge<>0" : "" ) .
  " ORDER BY menge_ist_null, produktgruppen_id, produkte.name ";

  $result = doSql($query, LEVEL_ALL, "Konnte Produktdaten nich aus DB laden..");
  return $result;
}

/**
 * verteilmengenLoeschen:
 *  - Verteilmengen nochmal löschen bei statuswechsel LIEFERANT -> BESTELLEN
 *    ( $nur_basar == false ), oder
 *  - nur die Verteilmengen fuer basar loeschen (nach verteilmengenZuweisen),
 *    da basar nie Verteilmengen erhalten sollte
 */
function verteilmengenLoeschen($bestell_id, $nur_basar=FALSE){
  $state = getState( $bestell_id );
  switch( $state ) {
    case STATUS_BESTELLEN:
    case STATUS_LIEFERANT:
      break;
    default:
      error( __LINE__, __FILE__, "Bestellung in Status $state: verteilmengen_loeschen() nicht mehr moeglich!" );
      return false;
  }
  fail_if_readonly();
  nur_fuer_dienst(1,3,4);

  $sql = "
    DELETE bestellzuordnung.*
    FROM bestellzuordnung
    INNER JOIN gruppenbestellungen
      ON (gruppenbestellungen.id = gruppenbestellung_id)
    WHERE art = 2 AND gesamtbestellung_id = $bestell_id ";
    if($nur_basar) {
      $sql .= " AND bestellguppen_id = ".sql_basar_id();
  }
  doSql($sql, LEVEL_ALL, "Konnte Verteilmengen nicht aus bestellzuordnung löschen..");

	if(! $nur_basar){
		$sql = "UPDATE bestellvorschlaege set bestellmenge = NULL where gesamtbestellung_id = ".$bestell_id;
		doSql($sql, LEVEL_ALL, "Konnte Bestellmengen nicht aus bestellvorschlaege löschen..");
	}

	return true;
}

/**
 *  setzt bestellmenge und liefermenge in bestellvorschlaegen aus zuteilungen in bestellzuordnung
 */
function writeLiefermenge_sql($bestell_id){
	$query = "SELECT produkt_id, sum(menge) as s FROM gruppenbestellungen  
		  INNER JOIN bestellzuordnung ON
		  	(gruppenbestellungen.id = gruppenbestellung_id)
		  WHERE art = 2 
		  AND gesamtbestellung_id = ".$bestell_id." 
		  GROUP BY produkt_id";
        $result = doSql($query, LEVEL_ALL, "Konnte bestellte Mengen nicht aus DB laden..");
  	while ($produkt_row = mysql_fetch_array($result)){
		$sql2 = "UPDATE bestellvorschlaege SET bestellmenge = "
		        .$produkt_row['s'].", liefermenge = ".
		        $produkt_row['s']." WHERE gesamtbestellung_id = ".
			$bestell_id." AND produkt_id = ".$produkt_row['produkt_id'];
                doSql($sql2, LEVEL_IMPORTANT, "Konnte Liefermengen nicht in DB schreiben...");
	}

}

/**
 *
 */
function writeVerteilmengen_sql($gruppenMengeInGebinde, $gruppenbestellung_id, $produkt_id){
	if($gruppenMengeInGebinde > 0){
		$query = "INSERT INTO  bestellzuordnung (menge, produkt_id, gruppenbestellung_id, art) 
			  VALUES (".$gruppenMengeInGebinde.
			 ", ".$produkt_id.
			 ", ".$gruppenbestellung_id.", 2);";
                doSql($query, LEVEL_IMPORTANT, "Konnte Verteilmengen nicht in DB schreiben...");
	}
}

/**
 *
 */
function verteilmengenZuweisen($bestell_id){
  // nichts tun, wenn keine Bestellung ausgewählt
  need($bestell_id);

  if(getState($bestell_id)!=STATUS_LIEFERANT) return;

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
	
	// changeState($bestell_id, STATUS_LIEFERANT);
}

/**
 *
 */
function changeLieferpreis_sql($preis_id, $produkt_id, $bestellung_id){
	$query = "UPDATE bestellvorschlaege 
		  SET produktpreise_id = ".mysql_escape_string($preis_id)."
		  WHERE produkt_id = ".mysql_escape_string($produkt_id)."
		  AND gesamtbestellung_id = ".mysql_escape_string($bestellung_id).";";
	//echo $query."<br>";
	doSql($query, LEVEL_IMPORTANT,"Konnte Lieferpreis nicht in DB ändern...");
}
/**
 *
 */
function changeLiefermengen_sql($menge, $produkt_id, $bestellung_id){
  fail_if_readonly();
  nur_fuer_dienst(1,3,4);
	$query = "UPDATE bestellvorschlaege 
		  SET liefermenge = ".mysql_escape_string($menge)."
		  WHERE produkt_id = ".mysql_escape_string($produkt_id)."
		  AND gesamtbestellung_id = ".mysql_escape_string($bestellung_id).";";
        doSql($query, LEVEL_IMPORTANT, "Konnte Liefermengen nicht in DB ändern...");
}
/**
 *
 */
function changeVerteilmengen_sql($menge, $gruppen_id, $produkt_id, $bestellung_id){
	$where_clause = " WHERE art = 2 AND produkt_id = ".mysql_escape_string($produkt_id)."
			 AND gruppenbestellung_id IN
		  	(SELECT id FROM gruppenbestellungen
				 WHERE bestellguppen_id = ".mysql_escape_string($gruppen_id)."
				 AND gesamtbestellung_id =
				 ".mysql_escape_string($bestellung_id).") ";

	$query = "SELECT * FROM bestellzuordnung ".$where_clause;
        $result = doSql($query, LEVEL_ALL, "Konnte Verteilmengen nicht von DB laden...");
	$toDelete = mysql_num_rows($result) - 1 ;
	if($toDelete > 0){
		$query = "DELETE FROM bestellzuordnung
			".$where_clause." LIMIT ".$toDelete;
                doSql($query, LEVEL_IMPORTANT, "Konnte Verteilmengen nicht in DB ändern...");
	}

	$query = "UPDATE bestellzuordnung 
		  SET menge = ".mysql_escape_string($menge).$where_clause;
        doSql($query, LEVEL_IMPORTANT, "Konnte Verteilmengen nicht in DB ändern...");
}

function sql_basar2group($gruppe, $produkt, $bestell_id, $menge){

      $id = sql_create_gruppenbestellung( $gruppe, $bestell_id );
      //                   ^ ist idempotent!

	    $sql = " INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art)
        VALUES ('$produkt','$id','$menge', 2)
        ON DUPLICATE KEY UPDATE menge = menge + $menge
      ";
            doSql($sql, LEVEL_IMPORTANT, "Konnte Basarkauf nicht eintragen..");
}


/**
 *  sql_basar:
 *  produkte im basar (differenz aus liefer- und verteilmengen) berechnen:
 */
function sql_basar($bestell_id=0,$order='produktname'){
   $sql = "SELECT * FROM (".select_basar($bestell_id,$order).") as basar";
   $result = doSql($sql, LEVEL_ALL, "Konnte Basardaten nicht aus DB laden..");
   return $result;

}
/**
 *
 */
function select_basar($bestell_id=0, $order='produktname') {
  switch( $order ) {
    case 'datum':
      $order_by = 'gesamtbestellungen.lieferung';
      break;
    case 'bestellung':
      $order_by = 'gesamtbestellungen.name';
      break;
    default:
    case 'produktname':
      $order_by = 'produkte.name';
      break;
  }
   return "
SELECT produkte.name, bestellvorschlaege.produkt_id,
bestellvorschlaege.gesamtbestellung_id,
bestellvorschlaege.produktpreise_id, bestellvorschlaege.liefermenge,
bz.verteilmenge, (bestellvorschlaege.liefermenge -
	ifnull(bz.verteilmenge,0)) as basar, produktpreise.verteileinheit,
     produktpreise.preis,
     gesamtbestellungen.name as bestellung_name,
     gesamtbestellungen.lieferung
FROM bestellvorschlaege 
LEFT JOIN (". select_verteilmengen() .")as bz
ON (bz.produkt_id =
	bestellvorschlaege.produkt_id and bz.gesamtbestellung_id =
	bestellvorschlaege.gesamtbestellung_id) 
JOIN produktpreise ON ( bestellvorschlaege.produktpreise_id = produktpreise.id ) 
JOIN gesamtbestellungen ON ( gesamtbestellungen.id = bestellvorschlaege.gesamtbestellung_id ) 
JOIN produkte ON ( bestellvorschlaege.produkt_id = produkte.id ) 
where (not isnull(liefermenge) or not isnull(bestellmenge))
      and gesamtbestellungen.state>='Verteilt'
      " . ( $bestell_id ? " and gesamtbestellungen.id=$bestell_id " : "" ) . "
HAVING ( `basar` <>0 )
ORDER BY $order_by
" ;
}

/**
 *  zusaetzlicheBestellung:
 *    um nachtraeglich (insbesondere nach Lieferung) ein Produkt zu einer Bestellung hinzuzufuegen.
 *    - eine entsprechende Basarbestellung wird erzeugt
 *    - liefermenge wird noch _nicht_ gesetzt
 */
function zusaetzlicheBestellung($produkt_id, $bestell_id, $bestellmenge ) {
   sql_insert_bestellvorschlaege( $produkt_id, $bestell_id, 0, $bestellmenge, 0 );
   $gruppenbestellung_id = sql_create_gruppenbestellung( sql_basar_id(), $bestell_id );
   $sql = "
     INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art)
     VALUES ( $produkt_id, $gruppenbestellung_id, $bestellmenge, 1)
   ";
   return doSql( $sql, LEVEL_IMPORTANT, "zusaetzlicheBestellung fehlgeschlagen: ");
}






////////////////////////////////////
//
// funktionen fuer gruppen-, lieferanten-, und bankkonto
//
////////////////////////////////////


/**
 * transaktionsart: 0 : gruppen_transaktion / bankkonto
 *                  1 : gruppen_transaktion / gruppen_transaktion
 *                  2 : gruppen_transaktion / (FIXME)
 */
function sql_gruppen_transaktion(
  $transaktionsart, $gruppen_id, $summe,
  $auszug_nr = "NULL", $auszug_jahr = "NULL", $notiz ="", 
  $kontobewegungs_datum ="NOW()", $lieferanten_id = 0, $konterbuchung_id = 0
) {
  global $dienstkontrollblatt_id, $hat_dienst_IV;
  fail_if_readonly();
  need( $hat_dienst_IV or ( $transaktionsart == 2 ) );
  need( $gruppen_id or $lieferanten_id );
  // art=0 ohne konto wird fuer vorlaeufige buchungen benutzt:
  // need( $transaktionsart or $bankkonto_id );

  $sql="
    INSERT INTO gruppen_transaktion (
      type, gruppen_id, lieferanten_id, 
    , eingabe_zeit, summe
    , kontoauszugs_jahr, kontoauszugs_nr
    , kontobewegungs_datum
    , dienstkontrollblatt_id, notiz
    , konterbuchung_id
    ) VALUES (
	    '$transaktionsart', '$gruppen_id', '$lieferanten_id'
    , 'NOW()', '$summe'
    , '$auszug_jahr', '$auszugs_nr'
    , '$kontobewegungs_datum'
    , '$dienstkontrollblatt_id', '$notiz'
    , '$konterbuchung_id
    );
  ";
  doSql( $sql, LEVEL_IMPORTANT, "Konnte Gruppentransaktion nicht in DB speichern.. ");
  return mysql_insert_id();
}

function sql_bank_transaktion(
  $konto_id, $auszug_jahr, $auszug_nr
, $haben, $datum, $gruppen_id, $lieferanten_id
, $dienstkontrollblatt_id, $notiz
, $konterbuchung_id = 0
) {
  need( $konto_id and $auzug_jahr and $auzug_nr );
  need( $dienstkontrollblatt_id and $notiz );
  fail_if_readonly();
  doSql( "
    INSERT INTO bankkonto (
      konto_id, kontoauszug_jahr, kontoauszug_nr
    , betrag, eingabedatum
    , gruppen_id, lieferanten_id
    , dienstkontrollblatt_id, kommentar
    , konterbuchung_id
    ) VALUES (
      '$konto_id', '$auszug_jahr', '$auszug_nr'
    , '$haben', '$datum'
    , '$gruppen_id', '$lieferanten_id'
    , '$dienstkontrollblatt_id', '$notiz'
    , '$konterbuchung_id'
    ); "
  , LEVEL_IMPORTANT, "Buchung fehlgeschlagen"
  );
  return mysql_insert_id();
}

function sql_link_transaktion( $soll_id, $haben_id ) {
  if( $soll_id > 0 )
    doSql( "UPDATE bankkonto SET konterbuchung_id=$haben_id WHERE id=$soll_id" );
  else
    doSql( "UPDATE gruppen_transaktion SET konterbuchung_id=$haben_id WHERE id=".(-$soll_id) );

  if( $haben_id > 0 )
    doSql( "UPDATE bankkonto SET konterbuchung_id=$soll_id WHERE id=$haben_id" );
  else
    doSql( "UPDATE gruppen_transaktion SET konterbuchung_id=$soll_id WHERE id=".(-$haben_id) );
}

/*
 * konto_id == -1 bedeutet gruppen_transaktion, sonst bankkonto
 */
function sql_doppelte_transaktion( $soll, $haben, $betrag, $datum, $notiz ) {
  global $dienstkontrollblatt_id;
  nur_fuer_dienst_IV();
  need( $dienstkontrollblatt_id and $notiz );
  need( isset( $soll['konto_id'] ) and isset( $haben['konto_id'] ) );
  if( $soll['konto_id'] == -1 and $haben['konto_id'] == -1 )
    $typ = 1;
  else
    $typ = 0;

  if( $soll['konto_id'] == -1 ) {
    $soll_id = -1 * sql_gruppen_transaktion(
      $typ, adefault( $soll, 'gruppen_id', 0 ), $betrag
    , adefault( $soll, 'auszug_nr', '' ), adefault( $soll, 'auszug_jahr', '' ), $notiz
    , $datum, adefault( $soll, 'lieferanten_id', 0 )
    );
  } else {
    $soll_id = sql_bank_transaktion(
      $soll['konto_id'], adefault( $soll, 'auszug_jahr', '' ), adefault( $soll, 'auszug_nr', '' )
    , -$betrag, $datum, adefault( $soll, 'gruppen_id', 0 ), adefault( $soll, 'lieferanten_id', 0 )
    , $dienstkontrollblatt_id, $notiz, 0
    );
  }

  if( $haben['konto_id'] == -1 ) {
    $haben_id = -1 * sql_gruppen_transaktion(
      $typ, adefault( $haben, 'gruppen_id', 0 ), -$betrag
    , adefault( $haben, 'auszug_nr', '' ), adefault( $haben, 'auszug_jahr', '' ), $notiz
    , $datum, adefault( $haben, 'lieferanten_id', 0 )
    );
  } else {
    $haben_id = sql_bank_transaktion(
      $haben['konto_id'], adefault( $haben, 'auszug_jahr', '' ), adefault( $haben, 'auszug_nr', '' )
    , $betrag, $datum, adefault( $haben, 'gruppen_id', 0 ), adefault( $haben, 'lieferanten_id', 0 )
    , $dienstkontrollblatt_id, $notiz, 0
    );
  }

  sql_link_transaktion( $soll_id, $haben_id );

  return;
}

function sql_groupGlass($gruppe, $menge){
	$pfand_preis = 0.16; // TODO: aus leitvariablen oder variable nach produkten machen?
	sql_gruppen_transaktion(2, $gruppe, ($pfand_preis*$menge),"NULL" , "NULL", 'Glasrueckgabe');
}

/**
 *
 */
function sql_finish_transaction( $soll_id , $konto_id , $receipt_nr , $receipt_year, $notiz ){
  global $dienstkontrollblatt_id;
  fail_if_readonly();
  nur_fuer_dienst_IV();

  $row = sql_select_single_row( "SELECT * FROM gruppen_transaktion WHERE id=$soll_id" );

  $haben_id = bankkonto_transaktion(
    $konto_id, $receipt_year, $receipt_nr
  , $row['summe'], 'NOW()', $row['gruppen_id'], $row['lieferanten_id']
  , $dienstkontrollblatt_id, $notiz
  );

  sql_link_transaktion( $soll_id, $haben_id );

  $sql="
    UPDATE gruppen_transaktion
    SET kontoauszugs_nr='$receipt_nr', kontoauszugs_jahr='$receipt_year',
        dienstkontrollblatt_id='$dienstkontrollblatt_id'
    WHERE id = '$soll_id'
  ";
  doSql($sql, LEVEL_IMPORTANT, "Konnte Transaktion in DB nicht aktualisieren..");
}


function sql_get_group_transactions( $gruppen_id, $from_date = NULL, $to_date = NULL ) {
  $sql = "
    SELECT id, type, summe, kontobewegungs_datum
         , konterbuchung_id, notiz
         , DATE_FORMAT(gruppen_transaktion.eingabe_zeit,'%d.%m.%Y  <br> <font size=1>(%T)</font>') AS date
         , DATE_FORMAT(gruppen_transaktion.kontobewegungs_datum,'%d.%m.%Y') AS valuta_trad
         , DATE_FORMAT(gruppen_transaktion.kontobewegungs_datum,'%Y%m%d') AS valuta_kan
    FROM gruppen_transaktion
    WHERE ( gruppen_id = $gruppen_id )
        " . ( $from_date ? " AND ( kontobewegungs_datum >= '$from_date' ) " : "" ) . "
        " . ( $to_date ? " AND ( kontobewegungs_datum <= '$to_date' ) " : "" ) . "
    ORDER BY valuta_kan DESC
  ";
  // LIMIT ".mysql_escape_string($start_pos).", ".mysql_escape_string($size).";") or error(__LINE__,__FILE__,"Konnte Gruppentransaktionsdaten nicht lesen.",mysql_error());
  return doSql( $sql, LEVEL_IMPORTANT, "Konnte Gruppentransaktionen nicht lesen ");
}

function sql_get_transaction( $id ) {
  if( $id > 0 ) {
    $sql = "
      SELECT kontoauszug_jahr, kontoauszug_nr
           , betrag as haben
           , kommentar
           , bankkonto.konterbuchung_id as konterbuchung_id
           , bankkonten.name as kontoname
           , gruppen_id, lieferanten_id
      FROM bankkonto
      JOIN bankkonten ON bankkonten.id = bankkonto.konto_id
      WHERE bankkonto.id = $id
    ";
  } else {
    $sql = "
      SELECT bankkonto.kontoauszug_jahr, bankkonto.kontoauszug_nr
           , -summe as haben
           , gruppen_transaktion.notiz as kommentar
           , gruppen_transaktion.konterbuchung_id as konterbuchung_id
           , bankkonten.name as kontoname
           , gruppen_transaktion.gruppen_id as gruppen_id,
           , gruppen_transaktion.lieferanten_id as lieferanten_id
      FROM gruppen_transaktion
      LEFT JOIN bankkonto
             ON bankkonto.id = gruppen_transaktion.konterbuchung_id
      LEFT JOIN bankkonten
             ON bankkonten.id = bankkonto.konto_id
      WHERE bankkonto.id = ".(-$id)."
    ";
  }
  return sql_select_single_row( $sql );
}

function sql_bankkonto_salden() {
  return doSql( "
    SELECT konto_id,
           IFNULL(sum( betrag ),0.0) as saldo,
           bankkonten.name as kontoname
    FROM bankkonto
    JOIN bankkonten ON bankkonten.id=konto_id
    GROUP BY konto_id
  " );
}

function sql_bankkonto_saldo( $konto_id, $auszug_jahr = 0, $auszug_nr = 0 ) {
  $where = "WHERE (konto_id=$konto_id)";
  if( $auszug_jahr ) {
    if( $auszug_nr ) {
      $where .= (
        ( $where ? " AND " : " WHERE " )
          . "( (kontoauszug_jahr<$auszug_jahr) or ((kontoauszug_jahr=$auszug_jahr) and (kontoauszug_nr<=$auszug_nr)) )"
      );
    } else {
      $where .= (
        ( $where ? " AND " : " WHERE " ) . "(kontoauszug_jahr<=$auszug_jahr)"
      );
    }
  }
  $row = sql_select_single_row( "
    SELECT konto_id,
           IFNULL(sum( betrag ),0.0) as saldo,
           bankkonten.name as name
    FROM bankkonto
    JOIN bankkonten ON bankkonten.id=konto_id
    $where
  " );
  return $row['saldo'];
}

function sql_konten() {
  return doSql( "SELECT * FROM bankkonten ORDER BY name" );
}

function sql_kontodaten( $konto_id ) {
  return sql_select_single_row( "SELECT * FROM bankkonten WHERE id='$konto_id'" );
}
function sql_kontoname($konto_id){
  $row = sql_kontodaten( $konto_id );
  return $row['name'];
}

function optionen_konten( $selected = 0 ) {
  $konten = sql_konten();
  $output = "";
  while( $konto = mysql_fetch_array($konten) ) {
    $id = $konto['id'];
    $output .= "<option value='$id'";
    if( $selected == $id ) {
      $output .= " selected";
      $selected = -1;
    }
    $output .= ">{$konto['name']}</option>";
  }
  if( $selected >=0 ) {
    $output = "<option value='0' selected>(bitte Konto wählen)</option>" . $output;
  }
  return $output;
}

function sql_kontoauszug( $konto_id = 0, $auszug_jahr = 0, $auszug_nr = 0 ) {
  $where = "";
  $groupby = "GROUP BY konto_id, kontoauszug_jahr, kontoauszug_nr";
  if( $konto_id ) {
    $where .= (
      ( $where ? " AND " : " WHERE " ) . "(konto_id=$konto_id)"
    );
  }
  if( $auszug_jahr ) {
    $where .= (
      ( $where ? " AND " : " WHERE " ) . "(kontoauszug_jahr=$auszug_jahr)"
    );
    if( $auszug_nr ) {
      $where .= (
        ( $where ? " AND " : " WHERE " ) . "(kontoauszug_nr=$auszug_nr)"
      );
      $groupby = "";
    }
  }
  return doSql( "
    SELECT *
    , bankkonto.id as id
    , bankkonto.kommentar as kommentar
    FROM bankkonto
    JOIN bankkonten ON bankkonten.id=konto_id
    $where
    $groupby
    ORDER BY konto_id, kontoauszug_jahr, kontoauszug_nr
  " );
}

/* subquery_bestellungen_soll_gruppen:
 *   liefert schuld von gruppen aus bestellungen
 *   $using ist array von tabellen, die aus dem uebergeordneten query benutzt werden sollen;
 *   erlaubte werte: 'gesamtbestellungen', 'bestellgruppen'
*/
function subquery_bestellungen_soll_gruppen( $using ) {
  is_array( $using ) or $using = array( $using );
  $morejoins = "";
  in_array( "gesamtbestellungen", $using ) or $morejoins .= "
    JOIN gesamtbestellungen ON gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id
  ";
  in_array( "bestellgruppen", $using ) or $morejoins .= "
    JOIN bestellgruppen ON bestellgruppen.id = gruppenbestellungen.bestellguppen_id
  ";
  return " (
    SELECT IFNULL( sum( bestellzuordnung.menge * produktpreise.preis ), 0.0 )
      FROM gruppenbestellungen
      $morejoins
      JOIN bestellzuordnung
        ON gruppenbestellungen.id = bestellzuordnung.gruppenbestellung_id
      JOIN bestellvorschlaege
        ON (bestellvorschlaege.produkt_id = bestellzuordnung.produkt_id)
           AND ( bestellvorschlaege.gesamtbestellung_id = gruppenbestellungen.gesamtbestellung_id )
      JOIN produktpreise
        ON produktpreise.id = bestellvorschlaege.produktpreise_id
     WHERE (bestellzuordnung.art=2)
           AND (gruppenbestellungen.bestellguppen_id=bestellgruppen.id)
           AND (gruppenbestellungen.gesamtbestellung_id=gesamtbestellungen.id)
           AND ".SQL_FILTER_SCHULDVERHAELTNIS."
  ) ";
}

/* subquery_bestellungen_haben_lieferanten:
 *   liefert forderung von lieferanten aus bestellungen
 *   $using ist array von tabellen, die aus dem uebergeordneten query benutzt werden sollen;
 *   erlaubte werte: 'gesamtbestellungen', 'lieferanten'
*/
function subquery_bestellungen_haben_lieferanten( $using ) {
  is_array( $using ) or $using = array( $using );
  $morejoins = "";
  in_array( "gesamtbestellungen", $using ) or $morejoins .= "
    JOIN gesamtbestellungen ON gesamtbestellungen.id = bestellvorschlaege.gesamtbestellung_id
  ";
  in_array( "lieferanten", $using ) or $morejoins .= "
    JOIN lieferanten ON lieferanten.id = produkte.lieferanten_id
  ";
  return " (
    SELECT IFNULL( sum( bestellvorschlaege.liefermenge * produktpreise.preis ), 0.0 )
      FROM bestellvorschlaege
      JOIN produktpreise
        ON produktpreise.id = bestellvorschlaege.produktpreise_id
      JOIN produkte
        ON produkte.id = bestellvorschlaege.produkt_id
      $morejoins
     WHERE (produkte.lieferanten_id = lieferanten.id)
           AND (bestellvorschlaege.gesamtbestellung_id=gesamtbestellungen.id)
           AND ".SQL_FILTER_SCHULDVERHAELTNIS."
  ) ";
}

function subquery_transaktionen_haben_gruppen( $using ) {
  is_array( $using ) or $using = array( $using );
  $morejoins = "";
  in_array( "bestellgruppen", $using ) or $morejoins .= "
    JOIN bestellgruppen ON bestellgruppen.id = gruppen_transaktion.gruppen_id
  ";
  return " (
    SELECT IFNULL( sum( summe ), 0.0 )
      FROM gruppen_transaktion
     WHERE gruppen_transaktion.gruppen_id = bestellgruppen.id
  ) ";
}

function subquery_transaktionen_soll_lieferanten( $using ) {
  is_array( $using ) or $using = array( $using );
  $morejoins = "";
  in_array( "lieferanten", $using ) or $morejoins .= "
    JOIN lieferanten ON lieferanten.id = gruppen_transaktion.lieferanten_id
  ";
  return " (
    SELECT IFNULL( sum( -summe ), 0.0 )
      FROM gruppen_transaktion
     WHERE gruppen_transaktion.lieferanten_id = lieferanten.id
  ) ";
}


function subquery_haben_lieferanten( $using ) {
  return " (
    SELECT (" .subquery_bestellungen_haben_lieferanten($using). "
            - " .subquery_transaktionen_soll_lieferanten($using). ") as haben
  ) ";
}

function subquery_kontostand_gruppen( $using ) {
  return " (
    SELECT (".subquery_transaktionen_haben_gruppen('bestellgruppen')."
           - ".subquery_bestellungen_soll_gruppen('bestellgruppen')." ) as haben
  ) ";
}

function sql_verbindlichkeiten_lieferanten() {
  return doSql( "
    SELECT lieferanten.id as lieferanten_id
         , lieferanten.name as name
         , ( ".subquery_haben_lieferanten('lieferanten')." ) as soll
    FROM lieferanten
    HAVING (soll <> 0)
  " );
}

function forderungen_gruppen_summe() {
  $row = sql_select_single_row( "
    SELECT ifnull( sum( table_soll_gruppe.soll_gruppe ), 0.0 ) as soll
    FROM (
      SELECT ( -" .subquery_kontostand_gruppen('bestellgruppen'). ") as soll_gruppe
      FROM " .subquery_aktive_bestellgruppen(). " as bestellgruppen
      HAVING (soll_gruppe > 0)
    ) AS table_soll_gruppe
  " );
  return $row['soll'];
}

function guthaben_gruppen_summe() {
  $row = sql_select_single_row( "
    SELECT ifnull( sum( table_haben_gruppe.haben_gruppe ), 0.0 ) as haben
    FROM (
      SELECT (" .subquery_kontostand_gruppen('bestellgruppen'). ") as haben_gruppe
      FROM " .subquery_aktive_bestellgruppen(). " as bestellgruppen
      HAVING (haben_gruppe > 0)
    ) AS table_haben_gruppe
  " );
  return $row['haben'];
}

function sql_bestellungen_soll_gruppe( $gruppen_id ) {
  $query = "
    SELECT gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.name
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%d.%m.%Y') as valuta_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%Y%m%d') as valuta_kan
         , " .subquery_bestellungen_soll_gruppen( array('bestellgruppen','gesamtbestellungen') ). " as soll
    FROM gesamtbestellungen
    INNER JOIN gruppenbestellungen
      ON ( gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id )
    INNER JOIN bestellgruppen
      ON bestellgruppen.id = gruppenbestellungen.bestellguppen_id
    WHERE ( gruppenbestellungen.bestellguppen_id = $gruppen_id ) AND ".SQL_FILTER_SCHULDVERHAELTNIS."
    ORDER BY valuta_kan DESC;
  ";
  $result = doSql($query, LEVEL_ALL, "Konnte Gesamtpreise nicht aus DB laden..");
  return $result;
}

function kontostand($gruppen_id){
  $row = sql_select_single_row( "
    SELECT (".subquery_kontostand_gruppen('bestellgruppen').") as haben
    FROM bestellgruppen
    WHERE bestellgruppen.id = $gruppen_id
  " );
  return $row['haben'];
}

function sockel_gruppen_summe() {
  global $sockelbetrag;
  $row = sql_select_single_row( "
    SELECT sum( $sockelbetrag * bestellgruppen.mitgliederzahl ) as soll
    FROM ".subquery_aktive_bestellgruppen()." as bestellgruppen
  " );
  return $row['soll'];
}


/////////////////////////////////////////////
//
// produkte und produktpreise
//
/////////////////////////////////////////////

function sql_aktuelle_produktpreise( $produkt_id, $zeitpunkt = "NOW()" ){
  if( $zeitpunkt ) {
    $zeitfilter = " AND (zeitende >= $zeitpunkt OR ISNULL(zeitende))
                    AND (zeitstart <= $zeitpunkt OR ISNULL(zeitstart))";
  } else {
    $zeitfilter = "";
  }
  $sql = "SELECT id
          FROM produktpreise 
          WHERE produkt_id = $produkt_id $zeitfilter
          ORDER BY zeitende DESC";
  // aktuellster preis ist immer vorn (auch NULL!)

  return doSql($sql, LEVEL_ALL, "Konnte Produktpreise nich aus DB laden..");
}

/* sql_aktueller_produktpreis_id:
 *  liefert id des aktuellsten preises zu $produkt_id,
 *  oder 0 falls es NOW() keinen gueltigen preis gibt:
 */
function sql_aktueller_produktpreis_id( $produkt_id, $zeitpunkt = "NOW()" ) {
  $result = sql_aktuelle_produktpreise( $produkt_id, $zeitpunkt );
  $n = mysql_num_rows($result);
  echo "<!-- aktueller_produktpreis: $n -->";
  if( mysql_num_rows( $result ) < 1 )
    return 0;
  $row = mysql_fetch_array( $result );
  return $row['id'];
}

/**
 *  Erzeugt einen Produktpreiseintrag
 *  Achtung, $start und $ende selbst escapen, damit
 *  now() und null verwendet werden können.
 */
function sql_insert_produktpreis ($id, $preis, $start,$ende, $bestellnummer, $gebindegroesse){
	$sql = "INSERT INTO produktpreise 
		(produkt_id, preis, zeitstart, zeitende, bestellnummer, gebindegroesse) 
		  VALUES ('".mysql_escape_string($id)."', 
				 '".mysql_escape_string($preis)."', 
				 $start, 
				 $ende, 
				 '".mysql_escape_string($bestellnummer)."', 
				 '".mysql_escape_string($gebindgroesse)."')";
        doSql($sql, LEVEL_IMPORTANT, "Konnte Preis nicht einfügen...");
}
/**
 *  Setzt einen Preis auf abgelaufen
 */
function sql_expire_produktpreis ($id){
	$query="UPDATE produktpreise SET zeitende=NOW() WHERE id=".$id;
        doSql($query, LEVEL_IMPORTANT, "Konnte Preis nicht in löschen...");
}

/**
 * Prüft, ob ein Preis noch gültig ist
 */
function is_expired_produktpreis($id){

   $sql ="SELECT id FROM produktpreise WHERE id=".$id." AND (ISNULL(zeitende) OR zeitende >= NOW());";
   $result = doSql($sql, LEVEL_ALL, "Konnte Preisdaten nicht aus DB laden..");
   return (mysql_num_rows($result) == 0);
}
/**
 *
 */
function sql_produktpreise2($produkt_id){
	$query = "SELECT * FROM produktpreise 
		WHERE produkt_id=".mysql_escape_string($produkt_id).
		" ORDER BY zeitstart, zeitende, gebindegroesse";
	return doSql($query, LEVEL_ALL, "Konnte Gebindegroessen nich aus DB laden..");
}
/**
 *
 */
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

global $masseinheiten;
$masseinheiten = array( 'g', 'ml', 'ST', 'KI', 'PA', 'GL', 'BE', 'DO', 'BD', 'BT', 'KT', 'FL' );

// kanonische_einheit: zerlegt $einheit in kanonische einheit und masszahl:
// 
/**
 *
 */
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

/**
 *
 */
function optionen_einheiten( $selected ) {
  global $masseinheiten;
  $output = '';
  foreach( $masseinheiten as $e ) {
    $output = $output . "<option value='$e'";
    if( $e == $selected )
      $output = $output . " selected";
    $output = $output . ">$e</option>";
  }
  return $output;
}

/*  preisdaten setzen:
 *  berechnet und setzt einige weitere nuetzliche eintraege einer 'produktpreise'-Zeile:
 */
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

  // Preise je V-Einheit:
  $pr['endpreis'] = $pr['preis'];
  $pr['bruttopreis'] = $pr['preis'] - $pr['pfand'];
  $pr['nettopreis'] = $pr['bruttopreis'] / ( 1.0 + $pr['mwst'] / 100.0 );

  // brutto/nettopreis je preiseinheit:
  // $pr['endlieferpreis'] = $pr['endpreis'] * $pr['mengenfaktor'];
  $pr['nettolieferpreis'] = $pr['nettopreis'] * $pr['mengenfaktor'];
  $pr['bruttolieferpreis'] = $pr['bruttopreis'] * $pr['mengenfaktor'];

  // deprecated:
  $pr['lieferpreis'] = $pr['nettolieferpreis'];
  $pr['preis_rund'] = sprintf( "%8.2lf", $pr['preis'] );
}

/**
 *  Produktgruppen abfragen
 */
function sql_produktgruppen(){
     $sql = "SELECT * FROM produktgruppen ORDER BY name"; 
    $result = doSql($sql, LEVEL_ALL, "Konnte Produktgruppen nicht aus DB laden..");
    return $result;
	
}
/**
 *  Produktinformationen abfragen
 */
function getProdukt($produkt_id){
   $sql = "SELECT * FROM produkte WHERE id = ".$produkt_id;
    $result = doSql($sql, LEVEL_ALL, "Konnte Produkte nich aus DB laden..");
    return mysql_fetch_array($result);
}

/**
 *  Produktinformationen updaten
 */
function sql_update_produkt ($id, $name, $lieferant_id, $produktgruppen_id, $einheit, $notiz){
	 $sql = "UPDATE produkte 
			SET name='".mysql_escape_string($name)."', 
			lieferanten_id='".mysql_escape_string($lieferanten_id)."', 
			produktgruppen_id='".mysql_escape_string($produktgruppen_id)."', 
			einheit='".mysql_escape_string($einheit)."', 
			notiz='".mysql_escape_string($notiz)."' 
			WHERE id=".mysql_escape_string($id);

         doSql($sql, LEVEL_IMPORTANT, "Konnte Produkt nicht in DB ändern...");
}

/**
 * Alle Produkte von einem Lieferanten, auch mit ungültigem Preis
 */
function getAlleProdukteVonLieferant ($lieferant_id){
	  $sql = "SELECT produkte.*, produkte.id as prodId 
			 FROM produkte
			 WHERE produkte.lieferanten_id = '$lieferanten_id'
			 ORDER BY produkte.lieferanten_id, produkte.produktgruppen_id, produkte.name";
    $result = doSql($sql, LEVEL_ALL, "Konnte Produkte nicht aus DB laden..");
    return $result;
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
    $sql = "
      SELECT *
      , produkte.name as name
      , produktgruppen.name as produktgruppen_name
      , produkte.id as produkt_id
      , produktgruppen.id as produktgruppen_id
      FROM produkte
      INNER JOIN produktgruppen
        ON produktgruppen.id = produkte.produktgruppen_id
      INNER JOIN produktpreise
        ON (produkte.id = produktpreise.produkt_id)
      WHERE lieferanten_id = $lieferant_id
            AND zeitstart <= NOW() AND ( ISNULL(zeitende) OR zeitende >= NOW() )
    ";
  } else {
    $state = getState( $bestell_id );
    switch( $state ) {
      case STATUS_BESTELLEN:
        // produkte in bestellvorlage aufnehmen:
        //  - wenn noch kein vorschlag vorhanden und
        //  - aktuell gueltiger preis existiert
        $zeitpunkt = " (SELECT bestellende FROM gesamtbestellungen WHERE id = ".$bestell_id.") ";
        $sql = "
          SELECT *
          , produkte.name as name
          , produktgruppen.name as produktgruppen_name
          , produkte.id as produkt_id
          , produktgruppen.id as produktgruppen_id
          FROM produkte
          INNER JOIN produktgruppen
            ON produktgruppen.id = produkte.produktgruppen_id
          INNER JOIN produktpreise ON
            (produkte.id = produktpreise.produkt_id)
          LEFT JOIN (SELECT * FROM bestellvorschlaege WHERE gesamtbestellung_id = $bestell_id ) as vorschlaege
            ON (produkte.id = vorschlaege.produkt_id)
          WHERE lieferanten_id = $lieferant_id AND isnull(gesamtbestellung_id)
          AND zeitstart <= $zeitpunkt AND ( ISNULL(zeitende) OR zeitende >= $zeitpunkt )
        ";
        break;
      default:
        // zusaetzlich geliefertes produkt aufnehmen:
        //  - wenn keine bestellung vorliegt (aber vorschlag evtl. schon!)
        //  - zeiten seien hier fast egal (lieferschein wird sowieso abgeglichen;
        //    (bei nachtraeglichem einfuegen wird die startzeit fast nie passen :-) )
        $sql = "
          SELECT *
          , produkte.name as name
          , produktgruppen.name as produktgruppen_name
          , produkte.id as produkt_id
          , produktgruppen.id as produktgruppen_id
          FROM produkte
          INNER JOIN produktgruppen
            ON produktgruppen.id = produkte.produktgruppen_id
          INNER JOIN produktpreise ON
            (produkte.id = produktpreise.produkt_id)
          LEFT JOIN
            ( SELECT bestellvorschlaege.produkt_id, bestellvorschlaege.gesamtbestellung_id
              FROM bestellvorschlaege
              INNER JOIN gruppenbestellungen
                ON gruppenbestellungen.gesamtbestellung_id = bestellvorschlaege.gesamtbestellung_id
              INNER JOIN bestellzuordnung
                ON bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id
                   AND bestellzuordnung.produkt_id = bestellvorschlaege.produkt_id
              WHERE bestellvorschlaege.gesamtbestellung_id = $bestell_id
              GROUP BY produkt_id
            ) as bestellungen
            ON (produkte.id = bestellungen.produkt_id)
          WHERE lieferanten_id = $lieferant_id AND isnull(gesamtbestellung_id)
          GROUP BY produkte.id
        ";
      break;
    }
  }
  $sql .= " ORDER BY produktgruppen.id, produkte.name ";
  $result = doSql($sql, LEVEL_ALL, "Konnte Produkte nich aus DB laden..");
  return $result;
}




////////////////////////////////////
//
// HTML-funktionen:
//
////////////////////////////////////


// get_http_var: bisher definierte $typ argumente:
//   u (default wenn name auf _id endet): positive ganze Zahl
//   M (sonst default): Wert beliebig, wird aber durch mysql_real_escape_string fuer MySQL verdaulich gemacht
//   A : automatisch (default; momentan: trick um ..._id-Variablen zu testen)
//   f : Festkommazahl
//   w : bezeichner: alphanumerisch und _
//   /.../: regex pattern. Wert wird ausserdem ge-trim()-t
//
/**
 *
 */
function get_http_var( $name, $typ = 'A', $default = NULL, $is_self_field = false ) {
  global $$name, $HTTP_GET_VARS, $HTTP_POST_VARS, $self_fields;
  if( isset( $HTTP_GET_VARS[$name] ) ) {
    $val = $HTTP_GET_VARS[$name];
  } elseif( isset( $HTTP_POST_VARS[$name] ) ) {
    $val = $HTTP_POST_VARS[$name];
  } else {
    if( isset( $default ) ) {
      $$name = $default;
      if( $is_self_field ) {
        $self_fields[$name] = $default;
      }
      return TRUE;
    } else {
      unset( $$name );
      return FALSE;
    }
  }
  if( $typ == 'A' ) {
    if( substr( $name, -3 ) == '_id' ) {
      $typ = 'u';
    } else {
      $typ = 'M';
    }
  }
  $pattern = '';
  switch( substr( $typ, 0, 1 ) ) {
    case 'M':
      $val = mysql_real_escape_string( $val );
      break;
    case 'u':
      $val = trim($val);
      $pattern = '/^\d+$/';
      break;
    case 'f':
      $val = trim($val);
      $pattern = '/^[-\d.]+$/';
      break;
    case 'w':
      $val = trim($val);
      $pattern = '/^[a-zA-Z0-9_]+$/';
      break;
    case '/':
      $val = trim($val);
      $pattern = $typ;
       break;
    default:
  }
  if( $pattern ) {
    if( ! preg_match( $pattern, $val ) ) {
      unset( $$name );
      return FALSE;
    }
  }
  $$name = $val;
  if( $is_self_field ) {
    $self_fields[$name] = $val;
  }
  return TRUE;
}

/**
 *
 */
function need_http_var( $name, $typ = 'A', $is_self_field = false ) {
  if( ! get_http_var( $name, $typ, NULL, $is_self_field ) ) {
    error( __FILE__, __LINE__, "variable $name nicht uebergeben" );
    exit();
  }
  return TRUE;
}
/**
 *
 */
function reload_immediately( $url ) {
  echo "
    <form action='$url' name='reload_now_form' method='post'></form>
    <script type='text/javascript'>document.forms['reload_now_form'].submit();</script>
    $print_on_exit;
  ";
  exit();
}
function wikiLink( $topic, $text, $head = false ) {
  global $foodsoftdir;
  echo "
    <a class='wikilink' " . ( $head ? "id='wikilink_head' " : "" ) . "
    title='zur Wiki-Seite $topic'
    href=\"javascript:neuesfenster('$foodsoftdir/../wiki/doku.php?id=$topic','wiki');\">$text</a>
  ";
}

function setWikiHelpTopic( $topic ) {
  global $foodsoftdir;
  ?>
    <script type='text/javascript'>
      document.getElementById('wikilink_head').href
        = "javascript:neuesfenster('<? echo $foodsoftdir; ?>/../wiki/doku.php?id=<? echo $topic; ?>','wiki');";
      document.getElementById('wikilink_head').title
        = "zur Wiki-Seite <? echo $topic; ?>";
    </script>
  <?
}

// auf <title> (fensterrahmen) kann offenbar nicht mehr zugegriffen werden(?), wir
// koennen daher nur noch den subtitle (im fenster) setzen:
//
function setWindowSubtitle( $subtitle ) {
  echo "
    <script type='text/javascript'>
  " . replace_html( 'subtitle', "Foodsoft: $subtitle" ) . "
    </script>
  ";
}

// self_url:
// liefert url zum neuladen derselben seite, mit QUERY_STRING aus allen variablen
// in global $self_fields, mit ausnahme der variablen in $exclude:
// 
function self_url( $exclude = array() ) {
  global $self_fields;

  $output = 'index.php?';
  if( ! $exclude ) {
    $exclude = array();
  } elseif( is_string( $exclude ) ) {
    $exclude = array( $exclude );
  }
  foreach( $self_fields as $key => $value ) {
    if( ! in_array( $key, $exclude ) )
      $output = $output . "&$key=$value";
  }
  return $output;
}

// self_post:
// liefert 'hidden' input elemente, zum neuladen derselben seite per post, zu allen
// variablen in global $self_fields, mit ausnahme der variablen in $exclude:
// 
function self_post( $exclude = array() ) {
  global $self_fields;

  $output = '';
  if( ! $exclude ) {
    $exclude = array();
  } elseif( is_string( $exclude ) ) {
    $exclude = array( $exclude );
  }
  foreach( $self_fields as $key => $value ) {
    if( ! in_array( $key, $exclude ) )
      $output = $output . "<input type='hidden' name='$key' value='$value'>";
  }
  return $output;
}

function optionen( $fieldname, $values ) {
  global $$fieldname;
  $output = '';
  foreach( $values as $v ) {
    if( is_array( $v ) ) {
      $value = $v[0];
      $text = $v[1];
      $title = ( $v[2] ? $v[2] : '' );
    } else {
      $value = $v;
      $text = $v;
      $title = '';
    }
    $output = $output . "<option value='$value'";
    if( $value == $$fieldname )
      $output = $output . " selected";
    if( $title )
      $output = $output . " title='$title'";
    $output = $output . ">$text</option>";
  }
  return $output;
}

// insert_html:
// erzeugt javascript-code, der $element als Child vom element $id ins HTML einfuegt.
// $element is entweder ein string (erzeugt textelement), oder ein
// array( tag, attrs, childs ):
//   - tag ist der tag-name (z.b. 'table')
//   - attrs ist false, oder Liste von Paaren ( name, wert) gewuenschter Attribute
//   - childs ist entweder false, ein Textstring, oder ein Array von $element-Objekten
function insert_html( $id, $element ) {
  global $autoid;
  if( ! $autoid ) $autoid = 0;

  $output = '
  ';
  if( ! $element )
    return $output;

  if( is_string( $element ) ) {
    $autoid++;
    $output = "$output
      var tnode_$autoid;
      tnode_$autoid = document.createTextNode('$element');
      document.getElementById('$id').appendChild(tnode_$autoid);
    ";
  } else {
    assert( is_array( $element ) );
    $tag = $element[0];
    $attrs = $element[1];
    $childs = $element[2];

    // element mit eindeutiger id erzeugen:
    $autoid++;
    $newid = "autoid_$autoid";
    $output = "$output
      var enode_$newid;
      var attr_$autoid;
      enode_$newid = document.createElement('$tag');
      attr_$autoid = document.createAttribute('id');
      attr_$autoid.nodeValue = '$newid';
      enode_$newid.setAttributeNode( attr_$autoid );
    ";
    // sonstige gewuenschte attribute erzeugen:
    if( $attrs ) {
      foreach( $attrs as $a ) {
        $autoid++;
        $output = "$output
          var attr_$autoid;
          attr_$autoid = document.createAttribute('{$a[0]}');
          attr_$autoid.nodeValue = '{$a[1]}';
          enode_$newid.setAttributeNode( attr_$autoid );
        ";
      }
    }
    // element einhaengen:
    $output = "$output
      document.getElementById( '$id' ).appendChild( enode_$newid );
    ";

    // rekursiv unterelemente erzeugen:
    if( is_array( $childs ) ) {
      foreach( $childs as $c )
        $output = $output . insert_html( $newid, $c );
    } else {
      // abkuerzung fuer reinen textnode:
      $output = $output . insert_html( $newid, $childs );
    }
  }
  return $output;
}

// replace_html: wie insert_html, loescht aber vorher alle Child-Elemente von $id
function replace_html( $id, $element ) {
  global $autoid;
  $autoid++;
  $output = "
    var enode_$autoid;
    var child_$autoid;
    enode_$autoid = document.getElementById('$id');
    while( child_$autoid = enode_$autoid.firstChild )
      enode_$autoid.removeChild(child_$autoid);
  ";
  return $output . insert_html( $id, $element );
}

function move_html( $id, $into_id ) {
  global $autoid;
  $autoid++;
  return "
    var child_$autoid;
    child_$autoid = document.getElementById('$id');
    document.getElementById('$into_id').appendChild(child_$autoid);
  ";
  // appendChild erzeugt _keine_ Kopie!
  // das urspruengliche element verschwindet, also ist das explizite loeschen unnoetig:
  //   document.getElementById('$id').removeChild(child_$autoid);
}



////////////////////////////////////
//
// momentan unbenutzte funktionen:
//
////////////////////////////////////

// function sql_delete_bestellzuordnung ($id){
//     $query= "DELETE FROM bestellzuordnung WHERE id='$id'"; 
//     doSql($query, LEVEL_IMPORTANT, "Löschen fehlgeschlagen...");
// }
//
//
// function sql_verteilmengen($bestell_id, $produkt_id, $gruppen_id){
// 	$result = sql_bestellmengen($bestell_id, $produkt_id,2, $gruppen_id);
// 	if(mysql_num_rows($result)==0) $return = 0;
// 	else if(mysql_num_rows($result)>1) 
// 		error(__LINE__,__FILE__,"Nicht genau ein Eintrag (".mysql_num_rows($result).") für Verteilmenge: bestell_id = $bestell_id, produkt_id = $produkt_id, gruppen_id = $gruppen_id" );
// 	else{
// 		$row = mysql_fetch_array($result);
// 		$return = $row['menge'];
// 	}
// 	return $return;
// }
// /**
//  *
//  */
// function sql_bestellvorschlag($bestell_id, $produkt_id){
// 	$query="SELECT produktpreis_id FROM bestellvorschlaege 
// 	  	WHERE gesamtbestellung_id = ".$bestell_id.
// 		"AND produkt_Id = ".$produkt_id;
// 	$result = doSql($query, LEVEL_ALL, "Konnte Bestellpreis nicht laden");
// 	$row = mysql_fetch_array($result);
// 	return $row;
// }
// 
// function nichtGeliefert($bestell_id, $produkt_id){
//     $sql = "UPDATE bestellzuordnung INNER JOIN gruppenbestellungen 
// 	    ON gruppenbestellung_id = gruppenbestellungen.id 
// 	    SET menge =0 
// 	    WHERE art=2 
// 	    AND produkt_id = ".$produkt_id." 
// 	    AND gesamtbestellung_id = ".$bestell_id.";";
//     doSql($sql, LEVEL_IMPORTANT, "Konnte Verteilmengen nicht in DB ändern...");
//     $sql = "UPDATE bestellvorschlaege
//     	    SET liefermenge = 0 
// 	    WHERE produkt_id = ".$produkt_id."
// 	    AND gesamtbestellung_id = ".$bestell_id;
//     doSql($sql, LEVEL_IMPORTANT, "Konnte Liefermengen nicht in DB ändern...");
// }
// /**
//  *
//  */
// function from_basar(){
//    return "((`verteilmengen` join `bestellvorschlaege` on(((`verteilmengen`.`bestell_id` = `bestellvorschlaege`.`gesamtbestellung_id`) and (`bestellvorschlaege`.`produkt_id` = `verteilmengen`.`produkt_id`)))) join `produkte` on((`verteilmengen`.`produkt_id` = `produkte`.`id`)))";
// }


?>
