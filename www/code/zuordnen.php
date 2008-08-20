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

function debug_args( $args, $tag = '' ) {
  echo "<br><pre>";
  $i = 1;
  foreach( $args as $k => $a ) {
    echo "$tag: $i: $k => ";
    var_export( $a );
    echo '';
    echo "</pre>";
    $i++;
  }
}


function doSql($sql, $debug_level = LEVEL_IMPORTANT, $error_text = "Datenbankfehler: " ){
	if($debug_level <= $_SESSION['LEVEL_CURRENT']) echo "<p>".htmlspecialchars($sql)."</p>";
	$result = mysql_query($sql) or
	error(__LINE__,__FILE__,$error_text."(".htmlspecialchars($sql).")",mysql_error(), debug_backtrace());
	return $result;
}

function sql_select_single_row( $sql, $allownull = false ) {
  $result = doSql( $sql );
  $rows = mysql_num_rows($result);
  // echo "<br>$sql<br>rows: $rows<br>";
  if( $rows == 0 ) {
    if( is_array( $allownull ) )
      return $allownull;
    if( $allownull )
      return NULL;
  }
  need( $rows > 0, "Kein Treffer bei Datenbanksuche: $sql" );
  need( $rows == 1, "Ergebnis der Datenbanksuche $sql nicht eindeutig ($rows)" );
  return mysql_fetch_array($result);
}

function sql_select_single_field( $sql, $field, $allownull = false ) {
  $row = sql_select_single_row( $sql, $allownull );
  if( ! $row ) {
    if( ! is_array( $allownull ) )
      return NULL;
  }
  need( isset( $row[$field] ), "Feld $field nicht gesetzt" );
  return $row[$field];
}

function sql_update( $table, $where, $values, $escape_and_quote = true ) {
  $table == 'leitvariable' or $table == 'transactions' or fail_if_readonly();
  $sql = "UPDATE $table SET";
  $komma='';
  foreach( $values as $key => $val ) {
    if( $escape_and_quote )
      $val = "'" . mysql_real_escape_string($val) . "'";
    $sql .= "$komma $key=$val";
    $komma=',';
  }
  if( is_array( $where ) ) {
    $and = 'WHERE';
    foreach( $where as $field => $val ) {
      if( $escape_and_quote )
        $val = "'" . mysql_real_escape_string($val) . "'";
      $sql .= " $and ($field=$val) ";
      $and = 'AND';
    }
  } else {
    $sql .= " WHERE id=$where";
  }
  if( doSql( $sql, LEVEL_IMPORTANT, "Update von Tabelle $table fehlgeschlagen: " ) )
    return $where;
  else
    return FALSE;
}

function sql_insert( $table, $values, $update_cols = false, $escape_and_quote = true ) {
  $table == 'leitvariable' or $table == 'transactions' or fail_if_readonly();
  $komma='';
  $update_komma='';
  $cols = '';
  $vals = '';
  $update = '';
  foreach( $values as $key => $val ) {
    $cols .= "$komma $key";
    if( $escape_and_quote )
      $val = "'" . mysql_real_escape_string($val) . "'";
    $vals .= "$komma $val";
    if( is_array( $update_cols ) ) {
      if( isset( $update_cols[$key] ) ) {
        if( $update_cols[$key] ) {
          $val = $update_cols[$key];
          if( $escape_and_quote )
            $val = "'" . mysql_real_escape_string($val) . "'";
        }
        $update .= "$update_komma $key=$val";
        $update_komma=',';
      }
    } elseif( $update_cols ) {
      $update .= "$update_komma $key=$val";
      $update_komma=',';
    }
    $komma=',';
  }
  $sql = "INSERT INTO $table ( $cols ) VALUES ( $vals )";
  if( $update_cols or is_array( $update_cols ) ) {
    $sql .= " ON DUPLICATE KEY UPDATE $update $update_komma id = LAST_INSERT_ID(id) ";
  }
  if( doSql( $sql, LEVEL_IMPORTANT, "Einfügen in Tabelle $table fehlgeschlagen: " ) )
    return mysql_insert_id();
  else
    return FALSE;
}


function need( $exp, $comment = "Fataler Fehler" ) {
  global $print_on_exit;
  if( ! $exp ) {
    ?>
      <div class='warn'>
        <? echo htmlspecialchars( "$comment $exp" ); ?>
        <a href='<? echo self_url(); ?>'>weiter...</a>
      </div>
    <?
    echo "$print_on_exit";
    exit();
  }
  return true;
}


function fail_if_readonly() {
  global $readonly, $print_on_exit;
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
 * need_joins: fuer skalare subqueries wie in "SELECT x , ( SELECT ... ) as y, z":
 *  erzeugt aus $rules JOIN-anweisungen fuer benoetigte tabellen; in $using koennen
 *  tabellen uebergeben werden, die bereits verfuegbar sind
 */
function need_joins( $using, $rules ) {
  $joins = '';
  is_array( $using ) or $using = array( $using );
  $keys = array_keys( $rules );
  foreach( $keys as $table )
    if( ! in_array( $table, $using ) )
      $joins .= " JOIN " . $rules[$table];
  return $joins;
}

/*
 * use_filters: fuer skalare subqueries wie in "SELECT x , ( SELECT ... ) as y, z":
 *  erzeugt optionale filterausdruecke, die bereits verfuegbare tabellen benutzen
 */
function use_filters( $using, $rules ) {
  $filters = '';
  is_array( $using ) or $using = array( $using );
  $keys = array_keys( $rules );
  foreach( $keys as $table )
    if( in_array( $table, $using ) )
      $filters .= " AND ({$rules[$table]}) ";
  return $filters;
}


define('STATUS_BESTELLEN', 10 );
define('STATUS_LIEFERANT', 20 );
define('STATUS_VERTEILT', 30 );
define('STATUS_ABGERECHNET', 40 );
define('STATUS_ARCHIVIERT', 50 );

function rechnung_status_string( $state ) {
  switch( $state ) {
    case STATUS_BESTELLEN:
      return 'Bestellen';
    case STATUS_LIEFERANT:
      return 'beim Lieferanten';
    case STATUS_VERTEILT:
      return 'geliefert und verteilt';
    case STATUS_ABGERECHNET:
      return 'abgerechnet';
    case STATUS_ARCHIVIERT:
      return 'archiviert';
  }
  return "FEHLER: undefinierter Status: $state";
}


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
  $sql = "UPDATE Dienste inner join gruppenmitglieder on (gruppenmitglieder_id = gruppenmitglieder.id) SET Dienste.Status = 'Bestaetigt'
          WHERE gruppen_id = ".$login_gruppen_id."
	  AND Lieferdatum = '".$datum."'";
  doSql($sql, LEVEL_IMPORTANT, "Error while confirming Dienstplan");

}

/**
 *  Dienst Akzeptieren 
 */ 
function sql_dienst_akzeptieren($dienst){
  global $login_gruppen_id;
  $row = sql_get_dienst_by_id($dienst);
  if($row["gruppen_id"]!=$login_gruppen_id || $row["Status"]!="Vorgeschlagen" ){
       error(__LINE__,__FILE__,"Falsche gruppen_id (angemeldet als $login_gruppen_id, dienst gehört ".$row["gruppen_id"].") oder falscher Status ".$row["Status"]);
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
  if($row["gruppen_id"]!=$login_gruppen_id || 
         ($row["Status"]!="Vorgeschlagen" && $row["Status"]!="Bestaetigt" && $row["Status"]!="Akzeptiert")){
       error(__LINE__,__FILE__,"Falsche GruppenID (angemeldet als $login_gruppen_id, dienst gehört ".$row["gruppen_id"].") oder falscher Status ".$row["Status"]);
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
  if($row["gruppen_id"]!=$login_gruppen_id || $row["Status"]!="Vorgeschlagen" ){
       error(__LINE__,__FILE__,"Falsche GruppenID (angemeldet als $login_gruppen_id, dienst gehört ".$row["gruppen_id"].") oder falscher Status ".$row["Status"]);
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

  $personen = sql_gruppen_members($login_gruppen_id);
  $person = mysql_fetch_array($personen);
  sql_create_dienst2($person["id"],$row["Dienst"], "'".$row["Lieferdatum"]."'", $status);

}

/**
 *  Fragt einen einzelnen Dienst basierend
 *  auf der ID ab
 */
function sql_get_dienst_by_id($dienst){
  $sql = "SELECT * FROM Dienste INNER JOIN gruppenmitglieder
	         ON (Dienste.gruppenmitglieder_id = gruppenmitglieder.id) WHERE Dienste.ID = ".$dienst;
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
              INNER JOIN gruppenmitglieder
	         ON (Dienste.gruppenmitglieder_id = gruppenmitglieder.id)";
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
		    FROM gruppenmitglieder
		    WHERE rotationsplanposition > ".$current."
		    AND status = 'aktiv' AND diensteinteilung = '".$dienst."'";
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

function sql_create_dienst2($mitglied, $dienst, $sql_datum, $status){
    $sql = "INSERT INTO Dienste (gruppenmitglieder_id, Dienst, Lieferdatum, Status)
            VALUES (".$mitglied.", '".$dienst."', ".$sql_datum.", '".$status."')";
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
	        inner join gruppenmitglieder on (gruppenmitglieder_id = gruppenmitglieder.id)
	    WHERE Dienste.Status = '".$status.
	    "' AND gruppen_id = ".$group."
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
    // var_dump(sql_rotationsplan_extrem($dienst));
    // var_dump($latest_position);
    $shift =sql_rotationsplan_extrem($dienst) - $latest_position ;
    $sql = "UPDATE gruppenmitglieder
            SET rotationsplanposition = -1 * (rotationsplanposition +".$shift.") 
	    WHERE rotationsplanposition <= ".$latest_position." AND status = 'aktiv' and diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
    /* Move all remaining groups (the ones not assigned a
     * task during the last round) to the front.
     * They haven't been moved in the previous round,
     * so they remain positive
     */
    $sql = "UPDATE gruppenmitglieder
    	    SET rotationsplanposition 
	        = (rotationsplanposition -".$latest_position.
	   ") WHERE rotationsplanposition > 0 
	    AND status = 'aktiv' AND diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
    // Remove mark (negative numbers)
    $sql = "UPDATE gruppenmitglieder
    	    SET rotationsplanposition = -1*rotationsplanposition 
	    WHERE rotationsplanposition < 0 
	    AND status = 'aktiv' and diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
   
}
/**
 *  This function allows to move a group up or down
 *  within the rotation system
 */
function sql_change_rotationsplan($mitglied, $dienst, $move_down){
    $position = sql_rotationsplanposition($mitglied);
    if($move_down){
    	$position_new = $position+1;
    } else {
    	$position_new = $position-1;
    }
    $sql = "UPDATE gruppenmitglieder
    	    SET rotationsplanposition = ".$position.
	   " WHERE rotationsplanposition = ".$position_new.
	   " AND status = 'aktiv' and diensteinteilung = '".$dienst."'";
    doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");
    $sql = "UPDATE gruppenmitglieder
    	    SET rotationsplanposition = ".$position_new.
	   " WHERE id = ".$mitglied;
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
		    FROM gruppenmitglieder
		    WHERE status = 'aktiv' AND diensteinteilung = '". $dienst."'";
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
		    FROM gruppenmitglieder
		    WHERE rotationsplanposition = ".$position."
		    AND status = 'aktiv' and diensteinteilung = '".$dienst."'";
     $result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
     $row = mysql_fetch_array($result);
     return $row["id"];

}
/**
 *  Queries the position in the
 *  rotation plan for a group
 */
function sql_rotationsplanposition($mitglied_id){
     $sql = "SELECT rotationsplanposition
		    FROM gruppenmitglieder
		    WHERE id = ".$mitglied_id;
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
	$sql = "UPDATE gruppenmitglieder
	        SET rotationsplanposition = ".$theMax.
		" WHERE diensteinteilung = '". $dienst.
		"' AND status = 'aktiv'  and rotationsplanposition <= 0 
		LIMIT 1";
	doSql($sql, LEVEL_IMPORTANT, "Error while changing Rotationsplan");

    }
    $position = sql_rotationsplan_hasDuplicates($dienst);
     while($position !=0){
          
	$theMax +=1;
	$sql = "UPDATE gruppenmitglieder
	        SET rotationsplanposition = ".$theMax.
		" WHERE diensteinteilung = '". $dienst.
		"' AND status = 'aktiv' and rotationsplanposition = ".$position." 
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
			FROM gruppenmitglieder
			WHERE status = 'aktiv' and diensteinteilung = '". $dienst.
			"' GROUP BY rotationsplanposition) as c
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
		FROM gruppenmitglieder
		WHERE status = 'aktiv' and diensteinteilung = '". $dienst.
		"' AND  rotationsplanposition <= 0 ";
	$result = doSql($sql, LEVEL_ALL, "Error while reading Rotationsplan");
        return(mysql_num_rows($result)!=0);

}
/** Queries the rotation plan for a
 *  given task. Before querying it, a
 *  check is performed to fix problems.
 */
function sql_rotationsplan($dienst){
        sql_check_rotationsplan($dienst);
	$sql = "SELECT * 
		FROM gruppenmitglieder
		WHERE status = 'aktiv' and diensteinteilung = '". $dienst.
		"' ORDER BY rotationsplanposition ASC";
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
           array("area" => "meinkonto", 
	        "hint"  => "Hier können die einzelnen Gruppen ihre Kontoauszüge einsehen....", 
		"title" => "Mein Konto"
	   )
   );
$areas[] = array("area" => "bestellungen_overview",
	"hint" => "Auflistung aller Bestellungen mit Status und Links",
	"title" => "Alle Bestellungen");
$areas[] = array("area" => "bestellen",
	"hint" => "Hier können die einzelnen Gruppen an den aktuellen Bestellung Teilnehmen....",
	"title" => "Bestellen");

$areas[] = array("area" => "bilanz",
	"hint" => "Finanzen der FC: Überblick und Verwaltung",
	"title" => "Bilanz");

if($hat_dienst_IV){
	$areas[] = array("area" => "produkte",
	"hint" => "Neue Produkte eingeben ... Preise verwalten ... Bestellung online stellen","title" => "Produktdatenbank");	 
} else {
	$areas[] = array("area" => "produkte",
	"hint" => "Produktdatenbank und Kataloge einsehen","title" => "Produktdatenbank");	 
}
	$areas[] = array("area" => "gruppen",
	"hint" => "Hier kann man die Bestellgruppen und deren Konten verwalten...",
	"title" => "Gruppen");		
if($hat_dienst_IV or $hat_dienst_III){
	$areas[] = array("area" => "basar",
	"hint" => "Produkte im Basar an Gruppen verteilen",
	"title" => "Basar");
}
if($hat_dienst_IV){
	$areas[] = array("area" => "lieferanten",
	"hint" => "Hier kann man die LieferantInnen verwalten...",
	"title" => "LieferantInnen");
} 
	$areas[] = array("area" => "dienstkontrollblatt",
	"hint" => "Hier kann man das Dientkontrollblatt einsehen...",
	"title" => "Dienstkontrollblatt");		
if($hat_dienst_IV or $hat_dienst_III or $hat_dienst_I){
	$areas[] = array("area" => "updownload",
	"hint" => "Hier kann die Datenbank hoch und runter geladen werden...",
	"title" => "Up/Download");
} 

   $areas[] = array("area" => "dienstplan", 
	        "hint"  => "Eigene Dienste anschauen, Dienste übernehmen, ...", 
		"title" => "Dienstplan"
	   );
   return $areas;
}


//////////////////////////////
//
// Passwort-Funktionen:
//
//////////////////////////////

function check_password( $gruppen_id, $gruppen_pwd ) {
  global $crypt_salt, $specialgroups;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    if( in_array( $gruppen_id, $specialgroups ) )
      return false;
    $row = sql_gruppendaten( $gruppen_id );
    if( ! $row['aktiv'] )
      return false;
    if( $row['passwort'] == crypt($gruppen_pwd,$crypt_salt) )
      return $row;
  }
  return false;
}

function set_password( $gruppen_id, $gruppen_pwd ) {
  global $crypt_salt, $login_gruppen_id;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    ( $gruppen_id == $login_gruppen_id ) or nur_fuer_dienst_V();
    return sql_update( 'bestellgruppen', $gruppen_id, array(
      'passwort' => crypt( $gruppen_pwd, $crypt_salt )
    ) );
  }
}

////////////////////////////////////
//
// dienstkontrollblatt-Funktionen:
//
////////////////////////////////////

function dienstkontrollblatt_eintrag( $dienstkontrollblatt_id, $gruppen_id, $dienst, $name, $telefon, $notiz, $datum = '', $zeit = '' ) {
  $notiz = mysql_real_escape_string($notiz);
  $telefon = mysql_real_escape_string($telefon);
  $name = mysql_real_escape_string($name);
  if( $dienstkontrollblatt_id ) {
    mysql_query( "
      UPDATE dienstkontrollblatt SET
        name = " . ( $name ? "'$name'" : "name" ) . "
      , telefon = " . ( $telefon ? "'$telefon'" : "telefon" ) . "
      , notiz = " . ( $notiz ? "IF( notiz = '$notiz', notiz, CONCAT( notiz, ' --- $notiz' ) )" : "notiz" ) . "
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
    SELECT
      bestellgruppen.id as gruppen_id
    , bestellgruppen.name as gruppen_name
    , dienstkontrollblatt.id as id
    , dienstkontrollblatt.name as name
    , dienstkontrollblatt.telefon as telefon
    , dienstkontrollblatt.notiz as notiz
    , dienstkontrollblatt.zeit as zeit
    , dienstkontrollblatt.datum as datum
    , dienstkontrollblatt.dienst as dienst
    FROM dienstkontrollblatt
    INNER JOIN bestellgruppen ON ( bestellgruppen.id = dienstkontrollblatt.gruppen_id )
    $where
    ORDER BY dienstkontrollblatt.id
  " ) or error( __LINE__, __FILE__, "Suche in dienstkontrollblatt fehlgeschlagen: ", mysql_error() );
  return $result;
}

function dienstkontrollblatt_name( $id ) {
  return sql_select_single_field( "SELECT name FROM dienstkontrollblatt WHERE id=$id", 'name' );
}


////////////////////////////////////
//
// bestellgruppen-funktionen:
//
////////////////////////////////////

function sql_basar_id(){
  global $basar_id;
  need( $basar_id, "Spezielle Basar-Gruppe nicht gesetzt (in tabelle leitvariablen!)" );
  return $basar_id;
}

function sql_muell_id(){
  global $muell_id;
  need( $muell_id, "Spezielle Muell-Gruppe nicht gesetzt (in tabelle leitvariablen!)" );
  return $muell_id;
}

function sql_gruppen_members( $gruppen_id, $member_id = FALSE){ 
  $sql = "SELECT * FROM gruppenmitglieder WHERE status = 'aktiv' and gruppen_id = ".mysql_escape_string($gruppen_id);
  if($member_id!==FALSE){
	  $sql.=" AND id = ".mysql_escape_string($member_id);
  }
  $result = doSql($sql, LEVEL_ALL);
  if($member_id!==FALSE){
	  $result = mysql_fetch_array($result);
  }
  return $result;
}

function sql_update_gruppen_member($id, $name, $vorname, $email, $telefon, $dienst){
  return sql_update( 'gruppenmitglieder', $id, array(
    'name' => $name
  , 'vorname' => $vorname
  , 'email' => $email
  , 'telefon' => $telefon
  , 'diensteinteilung' => $dienst
  ) );
}

function select_bestellgruppen( $filter = '', $more_select = '' ) {
  return "
    SELECT
      bestellgruppen.name as name
    , bestellgruppen.id as id
    , bestellgruppen.aktiv as aktiv
    , bestellgruppen.passwort as passwort
    , ( SELECT count(*) FROM gruppenmitglieder
        WHERE gruppenmitglieder.gruppen_id = bestellgruppen.id 
              AND gruppenmitglieder.status='aktiv' ) as mitgliederzahl
    , bestellgruppen.id % 1000 as gruppennummer
  " . ( $more_select ? ", $more_select" : '' ) . "
    FROM bestellgruppen
  " . ( $filter ? "WHERE ($filter) " : '' );
}

function select_aktive_bestellgruppen() {
  return select_bestellgruppen( 'bestellgruppen.aktiv' );
}

function sql_bestellgruppen( $filter = '' ) {
  return doSql( select_bestellgruppen( $filter ) . " ORDER BY gruppennummer" );
}

function sql_aktive_bestellgruppen() {
  return doSql( select_aktive_bestellgruppen() . " ORDER BY gruppennummer" );
}

function sql_gruppendaten( $gruppen_id ) {
  return sql_select_single_row( select_bestellgruppen( "bestellgruppen.id = $gruppen_id" ) ); 
}

function sql_gruppenname($gruppen_id){
  return sql_select_single_field( select_bestellgruppen( "bestellgruppen.id = $gruppen_id" ) , 'name' );
}

function sql_gruppennummer($gruppen_id){
  return $gruppen_id % 1000;
}

/*
 * sql_beteiligte_gruppen: SELECT
 * - alle an einer gesamtbestellung beteiligten (durch bestellung oder zuordnung!) gruppen,
 * - optional eingeschraenkt auf einen bestimmten artikel dieser bestellung
 * auch pfandrueckgabe zaehlt als teilnahme an einer bestellung
 */
function sql_beteiligte_bestellgruppen( $bestell_id, $produkt_id = FALSE ){
  $query = select_bestellgruppen( '', 'gruppenbestellungen.id as gruppenbestellungen_id' )
  . " INNER JOIN gruppenbestellungen
      ON ( gruppenbestellungen.bestellguppen_id = bestellgruppen.id )";
  if( $produkt_id ) {
    $query .= "
      INNER JOIN bestellzuordnung
      ON bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id
    ";
  }
  $query .= " WHERE gruppenbestellungen.gesamtbestellung_id = $bestell_id";
  if( $produkt_id ) {
    $query .= " AND bestellzuordnung.produkt_id = $produkt_id";
  }
  $query .= " GROUP BY bestellgruppen.id ORDER BY gruppennummer";
  return doSql( $query );
}

function optionen_gruppen(
  $bestell_id = false
, $produkt_id = false
, $selected = 0
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
  if( $bestell_id ) {
    $gruppen = sql_beteiligte_bestellgruppen($bestell_id,$produkt_id);
  } else {
    $gruppen = sql_aktive_bestellgruppen();
  }
  $output='';
  if( $option_0 ) {
    $output = "<option value='0'";
    if( $selected == 0 ) {
      $output = $output . " selected";
      $selected = -1;
    }
    $output = $output . ">$option_0</option>";
  }
  foreach( $additionalgroups as $id => $name ) {
    $output = "$output
      <option value='$id'";
        if( $selected == $id ) {
          $output = $output . " selected";
          $selected = -1;
        }
        $output = $output . ">" . ( $name ? $name : sql_gruppenname( $id ) ) . "</option>";
  }
  while($gruppe = mysql_fetch_array($gruppen)){
    $id = $gruppe['id'];
    if( in_array( $id, $additionalgroups ) )
      continue;
    if( in_array( $id, $specialgroups ) )
      continue;
    if( $allowedgroups and ! in_array( $id, $allowedgroups ) )
      continue;

    $output = "$output
      <option value='$id'";
    if( $selected == $id ) {
      $output = $output . " selected";
      $selected = -1;
    }
    $output = $output . ">{$gruppe['name']} ({$gruppe['gruppennummer']})</option>";
  }
  if( $selected >=0 ) {
    // $selected stand nicht zur Auswahl; vermeide zufaellige Anzeige:
    $output = "<option value='0' selected>(bitte Gruppe wählen)</option>" . $output;
  }
  return $output;
}

/**
 * Überprüft, ob die gewählte Gruppennummer verfügbar ist.
 * suche $id = $newNummer + n * 1000
 * dabei pruefen, ob noch aktive gruppe derselben nummer existiert
 *
 * Rückgabe= $newNummer + n * 1000
 *
 * Wenn etwas nicht klappt, enthält Problems eine entsprechende
 * html-Warnung
 */
function check_new_group_nr($newNummer){
    global $problems, $specialgroups;
    if( ( ! ( $newNummer > 0 ) ) || ( $newNummer > 98 ) ) {
      $problems = $problems . "<div class='warn'>Ung&uuml;ltige Gruppennummer!</div>";
      return FALSE;
    }
    if( in_array( $newNummer, $specialgroups ) ) {
      $problems = $problems . "<div class='warn'>Ung&uuml;ltige Gruppennummer (reserviert fuer Basar oder Muell)</div>";
      return FALSE;
    }
    $id = $newNummer;
    while( true ) {
	    $sql="SELECT * FROM bestellgruppen WHERE id='$id'" ;
	    $result=doSql($sql, LEVEL_ALL, "Suche in Bestellgruppen fehlgeschlagen");
      if( ! $result ) {
        $problems = $problems . "<div class='warn'>Suche in bestellgruppen fehlgeschlagen: </div>";
        break;
      }
      $row = mysql_fetch_array( $result );
      if( ! $row )
        break;
      if( $row['aktiv'] > 0 )
        $problems = $problems . "<div class='warn'>Aktive Gruppe der Nummer $newNummer existiert bereits!</div>";
      $id = $id + 1000;
    }
    if($problems!=""){
	    return FALSE;
    } else {
	    return $id;
    }

}
/**
 * Entfernt Gruppenmitglied und verringert den
 * Sockelbetrag entsprechend
 * Argument: personen_id
 */
function sql_delete_group_member($person_id, $gruppen_id){
	global $problems, $msg, $sockelbetrag, $mysqlheute;
  need( isset( $sockelbetrag ), "leitvariable sockelbetrag nicht gesetzt!" );
  $muell_id = sql_muell_id();
  sql_update( 'gruppenmitglieder', $person_id, array(
    'status' => 'geloescht'
  , 'diensteinteilung' => 'freigestellt'
  , 'rotationsplanposition' => 0
  ) );

          //Den Sockelbetrag ändern
  if( sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
  , array( 'konto_id' => -1, 'gruppen_id' => $muell_id, 'transaktionsart' => TRANSAKTION_TYP_SOCKEL )
  , $sockelbetrag
  , $mysqlheute
  , "Korrektur Sockelbetrag für ausgetretenes Mitglied"
  ) ) {
    $msg = $msg . "<div class='ok'>Aenderung Sockelbetrag: $sockelbetrag Euro wurden verbucht.</div>";
  } else {
    $problems = $problems . "<div class='warn'>Verbuchen Aenderung Sockelbetrag fehlgeschlagen: "
                               . mysql_error() . "</div>";
  }
}


/**
 * Legt neues Gruppenmitglied an und erhöht den
 * Sockelbetrag entsprechend
 * Argumente:
 * Vorname, Name, Mail, Telefon und Diensteinteilung des Neumitgliedes
 */
function sql_insert_group_member($gruppen_id, $newVorname, $newName, $newMail, $newTelefon, $newDiensteinteilung){
	global $problems, $msg, $sockelbetrag, $muell_id, $mysqlheute;
  need( isset( $sockelbetrag ), "leitvariable sockelbetrag nicht gesetzt!" );
  $muell_id = sql_muell_id();
  sql_insert( 'gruppenmitglieder', array(
    'vorname' => $newVorname
  , 'name' => $newName
  , 'gruppen_id' => $gruppen_id
  , 'email' => $newMail
  , 'telefon' => $newTelefon
  , 'diensteinteilung' => $newDiensteinteilung
  ) );

  //Den Sockelbetrag ändern
  if( sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => $muell_id, 'transaktionsart' => TRANSAKTION_TYP_SOCKEL )
  , array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
  , $sockelbetrag
  , $mysqlheute
  , "Korrektur Sockelbetrag für zusätzliches Mitglied"
  ) ) {
    $msg = $msg . "<div class='ok'>Aenderung Sockelbetrag: $sockelbetrag Euro wurden verbucht.</div>";
  } else {
    $problems = $problems . "<div class='warn'>Verbuchen Aenderung Sockelbetrag fehlgeschlagen: "
                                       . mysql_error() . "</div>";
  }
}

/**
 * Legt neue Gruppe an
 * macht aber erst ein paar checks
 *
 * Bitte zuvor $problems und $msg initialisieren
 *
 * Wenn etwas nicht stimmt, ist $problems gesetzt und
 * die Funktion gibt false zurück.
 *
 * $msg könnte auch Hinweise enthalten
 */

function sql_insert_group($newNumber, $newName, $pwd){
	global $problems, $msg, $crypt_salt;

	  $new_id = check_new_group_nr($newNumber) ;

	  if( $new_id > 0 ) {

	    if ($newName == "")
	      $problems = $problems . "<div class='warn'>Die neue Bestellgruppe mu&szlig; einen Name haben!</div>";

	    if( ! $problems ) {
		  return sql_insert( 'bestellgruppen', array(
          'id' => $new_id
        , 'aktiv' => 1
        , 'name' => $newName
        , 'passwort' => crypt( $pwd, $crypt_salt )
        ) );
	   } else {
		   return FALSE;
	   }
	  }
}

// optionsflags fuer anzeige in gruppen.php
// (hier definiert, um bei aufruf aus anderem fenster optionen setzen zu koennen):
//
define( 'GRUPPEN_OPT_INAKTIV', 1 );
define( 'GRUPPEN_OPT_SCHULDEN', 2 );
define( 'GRUPPEN_OPT_GUTHABEN', 4 );
define( 'GRUPPEN_OPT_UNGEBUCHT', 8 );
define( 'GRUPPEN_OPT_DETAIL', 16 );


////////////////////////////////////
//
// lieferanten-funktionen:
//
////////////////////////////////////

function select_lieferanten( $id = false, $orderby = 'name' ) {
  $where = ( $id ? "WHERE id=$id" : "" );
  return  "
    SELECT *
    , ( SELECT count(*) FROM produkte WHERE produkte.lieferanten_id = lieferanten.id ) as anzahl_produkte
    , ( SELECT count(*) FROM pfandverpackungen WHERE pfandverpackungen.lieferanten_id = lieferanten.id ) as anzahl_pfandverpackungen
    FROM lieferanten $where
    ORDER BY $orderby
  ";
}

function sql_lieferanten( $id = false ) {
  return doSql( select_lieferanten( $id ) );
}

function sql_getLieferant( $id ) {
  return sql_select_single_row( select_lieferanten( $id ) );
}

function lieferant_name($id){
  return sql_select_single_field( select_lieferanten( $id ) , 'name' );
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

function references_lieferant( $lieferanten_id ) {
  return sql_select_single_field(
    "SELECT count(*) as count FROM gesamtbestellungen WHERE lieferanten_id=$lieferanten_id"
  , 'count'
  );
}

function delete_lieferant( $lieferanten_id ) {
  $name = lieferant_name( $lieferanten_id );
  need( references_lieferant( $lieferanten_id ) == 0, 'Bestellungen vorhanden: Lieferant $name kann nicht gelöpscht werden!' );
  need( abs( lieferantenkontostand( $lieferanten_id )) < 0.01
    , 'Lieferantenkonto nicht ausgeglichen: Lieferant $name kann nicht gelöpscht werden!' );
  doSql(
    "DELETE FROM lieferanten WHERE id=$lieferanten_id"
  , LEVEL_IMPORTANT, "Loeschen des Lieferanten fehlgeschlagen"
  );
}

////////////////////////////////////
//
// funktionen fuer gesamtbestellung, bestellvorschlaege und gruppenbestellungen:
//
////////////////////////////////////

function sql_bestellung( $bestell_id ) {
  return sql_select_single_row( "SELECT * FROM gesamtbestellungen WHERE id=$bestell_id" );
}

function getState($bestell_id){
  return sql_select_single_field( "SELECT rechnungsstatus FROM gesamtbestellungen WHERE id=$bestell_id", 'rechnungsstatus' );
}

function bestellung_name($bestell_id){
  return sql_select_single_field( "SELECT name FROM gesamtbestellungen WHERE id=$bestell_id", 'name' );
}

function getProduzentBestellID($bestell_id){
  return sql_select_single_field( "SELECT lieferanten_id FROM gesamtbestellungen WHERE id=$bestell_id", 'lieferanten_id' );
}

/**
 *  changeState: 
 *   - fuehrt erlaubte Statusaenderungen einer Bestellung aus
 *   - ggf. werden Nebenwirkungen, wie verteilmengenZuweisen, ausgeloest
 */
function changeState($bestell_id, $state){
  global $mysqljetzt, $dienstkontrollblatt_id;

  $bestellung = sql_bestellung( $bestell_id );

  $current = $bestellung['rechnungsstatus'];
  if( $current == $state )
    return true;

  fail_if_readonly();
  nur_fuer_dienst(1,3,4);

  $do_verteilmengen_zuweisen = false;
  $changes = "rechnungsstatus = '$state'";
  switch( "$current,$state" ){
    case STATUS_BESTELLEN . "," . STATUS_LIEFERANT:
      need( $bestellung['bestellende'] < $mysqljetzt , "Fehler: Bestellung läuft noch!" );
      $do_verteilmengen_zuweisen = true;  // erst nach statuswechsel ausfuehren!
      // if( $bestellung['bestellende'] > $mysqljetzt )
      //   $changes .= ", bestellende=NOW()";
      break;
    case STATUS_LIEFERANT . "," . STATUS_BESTELLEN:
      verteilmengenLoeschen( $bestell_id );
      break;
    case STATUS_LIEFERANT . "," . STATUS_VERTEILT:
      $changes .= ", lieferung=NOW()";   // TODO: eingabe erlauben?
      break;
    case STATUS_VERTEILT . "," . STATUS_ABGERECHNET:
      nur_fuer_dienst(4);
      need( $dienstkontrollblatt_id > 0, "Kein Dienstkontrollblatt Eintrag" );
      $changes .= ", abrechnung_dienstkontrollblatt_id = '$dienstkontrollblatt_id'
                   , abrechnung_datum = '$mysqljetzt' ";
      break;
    case STATUS_ABGERECHNET . "," . STATUS_VERTEILT:
      nur_fuer_dienst(4);
      need( $dienstkontrollblatt_id > 0, "Kein Dienstkontrollblatt Eintrag" );
      $changes .= ", abrechnung_dienstkontrollblatt_id = 0 ";
      break;
    case STATUS_ABRECHNET . "," . STATUS_ARCHIVIERT:
      // TODO: tests:
      //   - bezahlt?
      //   - basarreste?
      break;
    default:
      error(__LINE__,__FILE__, "Ungültiger Statuswechsel");
      return false;
  }
  $sql = "UPDATE gesamtbestellungen SET $changes WHERE id = $bestell_id";
  $result = doSql($sql, LEVEL_KEY, "Konnte status der Bestellung nicht ändern..");
  if( $result ) {
    if( $do_verteilmengen_zuweisen )
      verteilmengenZuweisen( $bestell_id );
  }
  return $result;
}

function sql_bestellungen($state = FALSE, $use_Date = FALSE, $id = FALSE){
  $where = '';
  $add_and = 'WHERE';
  if($use_Date!==FALSE){
    $where .= " $add_and (NOW() BETWEEN bestellstart AND bestellende)";
    $add_and = 'AND';
  }
  if( $state ) {
    $add_or = '';
    $where .= " $add_and ( ";
    if(!is_array($state)){
      $where .= "rechnungsstatus = $state";
    } else {
      foreach($state as $st){
        $where .= " $add_or (rechnungsstatus = $st)";
        $add_or = 'OR';
      }
    }
    $where .= ')';
    $add_and = 'AND';
  }
  if($id!==FALSE){
    $where.= " $add_and (id =$id)";
    $add_and = 'AND';
  }
  return doSql( "SELECT * FROM gesamtbestellungen $where ORDER BY bestellende DESC,name" );
}

/* function select_gesamtbestellungen_schuldverhaeltnis():
 *  liefert gesamtbestellungen, fuer die bereits ein verbindlicher vertrag besteht
 *  (ab STATUS_LIEFERANT)
 */
function select_gesamtbestellungen_schuldverhaeltnis() {
  return "
    SELECT * FROM gesamtbestellungen
    WHERE rechnungsstatus >= " . STATUS_LIEFERANT;
}

/**
 *  Gesamtbestellung einfügen
 */
function sql_insert_bestellung($name, $startzeit, $endzeit, $lieferung, $lieferanten_id ){
  nur_fuer_dienst_IV();
  return sql_insert( 'gesamtbestellungen', array(
    'name' => $name, 'bestellstart' => $startzeit, 'bestellende' => $endzeit
  , 'lieferung' => $lieferung, 'lieferanten_id' => $lieferanten_id
  , 'rechnungsstatus' => STATUS_BESTELLEN
  ) );
}

function sql_update_bestellung($name, $startzeit, $endzeit, $lieferung, $bestell_id ){
  nur_fuer_dienst_IV();
  return sql_update( 'gesamtbestellungen', $bestell_id, array(
    'name' => $name, 'bestellstart' => $startzeit, 'bestellende' => $endzeit, 'lieferung' => $lieferung
  ) );
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

function sql_delete_bestellvorschlag( $produkt_id, $bestell_id ) {
  need( getState( $bestell_id ) == STATUS_BESTELLEN );
  doSql( "
    DELETE bestellzuordnung.*
    FROM bestellzuordnung
    INNER JOIN gruppenbestellungen
      ON gruppenbestellungen.id = gruppenbestellung_id
    WHERE produkt_id = $produkt_id AND gesamtbestellung_id = $bestell_id
  " );
  doSql( "
    DELETE FROM bestellvorschlaege
    WHERE produkt_id = $produkt_id AND gesamtbestellung_id = $bestell_id
  " );
}

function sql_bestellvorschlag_daten($bestell_id, $produkt_id){
  return sql_select_single_row( "
      SELECT *
               , produktpreise.id as preis_id
               , produkte.name as produkt_name
               , produktgruppen.name as produktgruppen_name
               , gesamtbestellungen.name as name
      FROM gesamtbestellungen
      INNER JOIN bestellvorschlaege
              ON bestellvorschlaege.gesamtbestellung_id=gesamtbestellungen.id
      INNER JOIN produkte
              ON produkte.id=bestellvorschlaege.produkt_id
      INNER JOIN produktpreise
              ON produktpreise.id=bestellvorschlaege.produktpreise_id
      INNER JOIN produktgruppen
              ON produktgruppen.id=produkte.produktgruppen_id
      WHERE     gesamtbestellungen.id='$bestell_id'
            AND bestellvorschlaege.produkt_id='$produkt_id'
  " );
}

function references_gesamtbestellung( $bestell_id ) {
  return sql_select_single_field( " SELECT (
     ( SELECT count(*) FROM bestellvorschlaege WHERE gesamtbestellung_id = $bestell_id )
   + ( SELECT count(*) FROM gruppenbestellungen WHERE gesamtbestellung_id = $bestell_id ) 
  ) as count
  " , 'count'
  );
}

function sql_bestellpreis($bestell_id, $produkt_id){
	$row = sql_bestellvorschlag_daten($bestell_id, $produkt_id);
	return $row['preis_id'];
}

function sql_create_gruppenbestellung( $gruppe, $bestell_id ){
  return sql_insert( 'gruppenbestellungen'
  , array( 'bestellguppen_id' => $gruppe , 'gesamtbestellung_id' => $bestell_id )
  , array(  /* falls schon existiert: -kein fehler -nix updaten -id zurueckgeben */  )
  );
}



////////////////////////////////////
//
// funktionen fuer bestellmengen und verteil/liefermengen
//
////////////////////////////////////

function sql_bestellmengen( $bestell_id, $produkt_id, $art = -1, $orderby = 'bestellzuordnung.zeitpunkt' ) {
  $query = "
    SELECT  *, bestellzuordnung.id as bestellzuordnung_id
    FROM gruppenbestellungen
    INNER JOIN bestellzuordnung
       ON (bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id)
    WHERE gruppenbestellungen.gesamtbestellung_id = $bestell_id 
      AND bestellzuordnung.produkt_id = $produkt_id
  ";
  if( $art >= 0 ){
    $query = $query." AND bestellzuordnung.art=$art";
  } else {
    $query = $query." AND bestellzuordnung.art < 2";
  }
  if( $orderby ) {
    $query = $query." ORDER BY $orderby ";
  }
  return doSql($query, LEVEL_ALL, "Konnte Bestellmengen nich aus DB laden..");
}

function gruppengesamtmenge( $bestell_id, $produkt_id, $gruppen_id, $art ) {
  return sql_select_single_field( "
    SELECT IFNULL( SUM( menge ), 0 ) as summe
    FROM gruppenbestellungen
    INNER JOIN bestellzuordnung
       ON ( bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id)
    WHERE gruppenbestellungen.gesamtbestellung_id = $bestell_id 
      AND bestellzuordnung.produkt_id = $produkt_id
      AND gruppenbestellungen.bestellguppen_id = $gruppen_id
      AND bestellzuordnung.art = $art
  ", 'summe'
  );
}

function select_bestellprodukte( $bestell_id, $gruppen_id = 0, $produkt_id = 0, $orderby = '' ) {
  $basar_id = sql_basar_id();
  $muell_id = sql_muell_id();
  $state = getState( $bestell_id );

  // echo "select_bestellprodukte: $gruppen_id, $produkt_id, $empty <br>";
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
    ifnull( sum(bestellzuordnung.menge * IF(bestellzuordnung.art=2,1,0)
                                       * IF( gruppenbestellungen.bestellguppen_id=$muell_id, 0, 1) ), 0.0 )
  ";
  $muellmenge_expr = "
    ifnull( sum(bestellzuordnung.menge * IF(bestellzuordnung.art=2,1,0)
                                       * IF( gruppenbestellungen.bestellguppen_id=$muell_id, 1 , 0) ), 0.0 )
  ";

  if( $orderby == '' )
    $orderby = "menge_ist_null, produktgruppen_id, produkte.name";

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
        $firstorder_expr = " ( $verteilmenge_expr + $muellmenge_expr ) ";
      else
        $firstorder_expr = "liefermenge";
      break;
  }

  // echo "<br>select_bestellprodukte: $having</br>";
  return "SELECT
      produkte.name as produkt_name
    , produktgruppen.name as produktgruppen_name
    , produkte.id as produkt_id
    , produkte.notiz as notiz
    , bestellvorschlaege.liefermenge as liefermenge
    , bestellvorschlaege.gesamtbestellung_id as gesamtbestellung_id
    , produktpreise.liefereinheit as liefereinheit
    , produktpreise.verteileinheit as verteileinheit
    , produktpreise.gebindegroesse as gebindegroesse
    , produktpreise.preis as preis
    , produktpreise.id as preis_id
    , produktpreise.pfand as pfand
    , produktpreise.mwst as mwst
    , produkte.artikelnummer as artikelnummer
    , produktpreise.bestellnummer as bestellnummer
    , ( produktpreise.preis - produktpreise.pfand ) as bruttopreis
    , ( produktpreise.preis - produktpreise.pfand ) / ( 1.0 + produktpreise.mwst / 100.0 ) as nettopreis
    , $gesamtbestellmenge_expr as gesamtbestellmenge
    , $basarbestellmenge_expr  as basarbestellmenge
    , $toleranzbestellmenge_expr as toleranzbestellmenge
    , $verteilmenge_expr as verteilmenge
    , $muellmenge_expr as muellmenge
    , IF( $firstorder_expr > 0, 0, 1 ) as menge_ist_null
  FROM bestellvorschlaege
  INNER JOIN produkte
    ON (produkte.id=bestellvorschlaege.produkt_id)
  INNER JOIN produktpreise
    ON (produktpreise.id=bestellvorschlaege.produktpreise_id)
  INNER JOIN produktgruppen
    ON (produktgruppen.id=produkte.produktgruppen_id)
  LEFT JOIN gruppenbestellungen
    ON (gruppenbestellungen.gesamtbestellung_id=$bestell_id)
  LEFT JOIN bestellzuordnung
    ON (bestellzuordnung.produkt_id=bestellvorschlaege.produkt_id
        AND bestellzuordnung.gruppenbestellung_id=gruppenbestellungen.id)
  WHERE bestellvorschlaege.gesamtbestellung_id=$bestell_id
  "
   . ( $gruppen_id ? " and gruppenbestellungen.bestellguppen_id=$gruppen_id " : "" )
   . ( $produkt_id ? " and produkte.id=$produkt_id " : "" )
  . "
  GROUP BY bestellvorschlaege.produkt_id
  ORDER BY $orderby ";
}

function sql_bestellprodukte( $bestell_id, $gruppen_id = 0, $produkt_id = 0, $orderby = '' ) {
  return doSql( select_bestellprodukte( $bestell_id, $gruppen_id, $produkt_id, $orderby ) );
}

// zuteilungen_berechnen():
// wo benoetigt, ist sql_bestellprodukte() schon aufgerufen; zwecks effizienz uebergeben wir der funktion
// eine Ergebniszeile, um den komplexen query in sql_bestellprodukte() nicht wiederholen zu muessen:
//
function zuteilungen_berechnen( $mengen  /* a row from sql_bestellprodukte */ ) {
  $produkt_id = $mengen['produkt_id'];
  $bestell_id = $mengen['gesamtbestellung_id'];
  $gebindegroesse = $mengen['gebindegroesse'];
  $toleranzbestellmenge = $mengen['toleranzbestellmenge'] + $mengen['basarbestellmenge'];
  $gesamtbestellmenge = $mengen['gesamtbestellmenge'];
  $festbestellmenge = $gesamtbestellmenge - $toleranzbestellmenge;

  $gebinde = (int)( $festbestellmenge / $gebindegroesse );
  if( $gebinde * $gebindegroesse < $festbestellmenge )
    if( ($gebinde+1) * $gebindegroesse <= $gesamtbestellmenge )
      ++$gebinde;
  $bestellmenge = $gebinde * $gebindegroesse;

  if( $bestellmenge < 1 )
    return array( 'bestellmenge' => 0, 'gebinde' => 0, 'festzuteilungen' => array(), 'toleranzzuteilungen' => array() );

  $restmenge = $bestellmenge;

  // erste zuteilungsrunde: festbestellungen in bestellreihenfolge erfuellen, dabei berechnete
  // negativ-toleranz abziehen:
  //
  $festbestellungen = sql_bestellmengen( $bestell_id, $produkt_id, 0 );
  $festzuteilungen = array();
  $offen = array();
  while( ( $restmenge > 0 ) and ( $row = mysql_fetch_array( $festbestellungen ) ) ) {
    $gruppe = $row['bestellguppen_id'];
    $menge = $row['menge'];
    if( isset( $offen[$gruppe] ) ) {
      $offen[$gruppe] += $menge;
    } else {
      $offen[$gruppe] = $menge;
      $festzuteilungen[$gruppe] = 0;
    }

    // negativ-toleranz ausrechnen und zurueckbehalten (maximal ein halbes gebinde):
    //
    $t_min = floor( ( $menge - $gebindegroesse / 2 ) / 2 );
    if( $t_min < 0 )
      $t_min = 0;
    if( $t_min > $gebindegroesse / 2 )
      $t_min = floor( $gebindegroesse / 2 );
    $menge -= $t_min;

    if( $menge > $restmenge )
      $menge = $restmenge;

    $festzuteilungen[$gruppe] += $menge;
    $restmenge -= $menge;
    $offen[$gruppe] -= $menge;
  }

  // zweite zuteilungsrunde: ebenfalls in bestellreihenfolge noch offene festbestellungen erfuellen:
  //
  mysql_data_seek( $festbestellungen, 0 );
  while( ( $restmenge > 0 ) and ( $row = mysql_fetch_array( $festbestellungen ) ) ) {
    $gruppe = $row['bestellguppen_id'];
    $menge = min( $row['menge'], $offen[$gruppe], $restmenge );
    $festzuteilungen[$gruppe] += $menge;
    $restmenge -= $menge;
    $offen[$gruppe] -= $menge;
  }

  // dritte zuteilungsrunde: mit positiv-toleranzen auffuellen:
  //
  $toleranzzuteilungen = array();
  if( $toleranzbestellmenge > 0 ) {
    $toleranzbestellungen = sql_bestellmengen( $bestell_id, $produkt_id, 1, '-menge' );
    $quote = ( 1.0 * $restmenge ) / $toleranzbestellmenge;
    need( $quote <= 1 );
    while( ( $restmenge > 0 ) and ( $row = mysql_fetch_array( $toleranzbestellungen ) ) ) {
      $gruppe = $row['bestellguppen_id'];
      $menge = (int) ceil( $quote * $row['menge'] );
      if( $menge > $restmenge )
        $menge = $restmenge;
      if( isset( $toleranzzuteilungen[$gruppe] ) ) // sollte nicht sein: nur _eine_ toleranzbestellung je gruppe!
        $toleranzzuteilungen[$gruppe] += $menge;
      else
        $toleranzzuteilungen[$gruppe] = $menge;
      $restmenge -= $menge;
    }
  }

  // jetzt sollte nix mehr da sein:  :-)
  //
  need( $restmenge == 0, "Fehler beim Verteilen: Rest: $restmenge Rest bei Produkt {$mengen['produkt_name']}" );

  return array( 'bestellmenge' => $bestellmenge, 'gebinde' => $gebinde, 'festzuteilungen' => $festzuteilungen, 'toleranzzuteilungen' => $toleranzzuteilungen );
}


function select_liefermenge( $bestell_id, $produkt_id ) {
  return "( SELECT bestellvorschlaege.liefermenge
    FROM bestellvorschlaege
    WHERE bestellvorschlaege.gesamtbestellung_id = $bestell_id
      AND bestellvorschlaege.produkt_id = $produkt_id
  )";
}

function sql_liefermenge( $bestell_id, $produkt_id ) {
  return sql_select_single_field( "SELECT ".select_liefermenge( $bestell_id, $produkt_id )." AS liefermenge", 'liefermenge' );
}

function select_verteilmenge( $bestell_id, $produkt_id, $gruppen_id = 0 ) {
  $muell_id = sql_muell_id();
  $basar_id = sql_basar_id();
  if( $gruppen_id ) {
    $more_where = " AND (gruppenbestellungen.bestellguppen_id = $gruppen_id )";
  } else {
    $more_where = " AND (gruppenbestellungen.bestellguppen_id != $muell_id) AND (gruppenbestellungen.bestellguppen_id != $basar_id)";
  }
  $muell_id = sql_muell_id();
  $basar_id = sql_basar_id();
  return "( SELECT IFNULL( sum( bestellzuordnung.menge ), 0.0 ) as verteilmenge
    FROM bestellzuordnung
    JOIN gruppenbestellungen
      ON gruppenbestellungen.id = bestellzuordnung.gruppenbestellung_id
    WHERE ( bestellzuordnung.art = 2 ) AND ( bestellzuordnung.produkt_id = $produkt_id )
          AND ( gruppenbestellungen.gesamtbestellung_id = $bestell_id )
         $more_where
  ) ";
}

function select_basarmenge( $bestell_id, $produkt_id ) {
  return "( SELECT ("
           . select_liefermenge( $bestell_id, $produkt_id ).
      " - " .select_verteilmenge( $bestell_id, $produkt_id ).
      " - " .select_verteilmenge( $bestell_id, $produkt_id, sql_muell_id() ).
    ") AS basarmenge )";
}


function sql_verteilmenge( $bestell_id, $produkt_id, $gruppen_id = 0 ) {
  return sql_select_single_field( "SELECT ".select_verteilmenge( $bestell_id, $produkt_id, $gruppen_id )." AS verteilmenge", 'verteilmenge' );
}

function sql_basarmenge( $bestell_id, $produkt_id ) {
  return sql_select_single_field( "SELECT ".select_basarmenge( $bestell_id, $produkt_id )." AS basarmenge", 'basarmenge' );
}

function sql_muellmenge( $bestell_id, $produkt_id ) {
  return sql_verteilmenge( $bestell_id, $produkt_id, sql_muell_id() );
}



/**
 *  sql_basar:
 *  produkte im basar (differenz aus liefer- und verteilmengen) berechnen:
 */
function sql_basar( $bestell_id = 0, $order='produktname' ) {
  switch( $order ) {
    case 'datum':
      $order_by = 'lieferung';
      break;
    case 'bestellung':
      $order_by = 'bestellung_name';
      break;
    default:
    case 'produktname':
      $order_by = 'produkt_name';
      break;
  }
  return doSql( select_basar( $bestell_id ) . " ORDER BY $order_by" );
}

/**
 *
 */
function select_basar( $bestell_id = 0 ) {
  $where = '';
  if( $bestell_id )
    $where = "WHERE gesamtbestellungen.id = $bestell_id";
  return "
    SELECT produkte.name as produkt_name
         , gesamtbestellungen.name as bestellung_name
         , gesamtbestellungen.lieferung as lieferung
         , gesamtbestellungen.id as gesamtbestellung_id
         , produktpreise.preis as endpreis
         , produktpreise.preis - produktpreise.pfand as bruttopreis
         , ( produktpreise.preis - produktpreise.pfand ) / ( 1.0 + produktpreise.mwst / 100.0 ) as nettopreis
         , produktpreise.verteileinheit
         , bestellvorschlaege.produkt_id
         , bestellvorschlaege.produktpreise_id
         , bestellvorschlaege.liefermenge
         , bestellvorschlaege.bestellmenge
         , (" .select_basarmenge( 'gesamtbestellungen.id', 'produkte.id' ). ") AS basar
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") AS gesamtbestellungen
    JOIN bestellvorschlaege ON ( bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id )
    JOIN produkte ON produkte.id = bestellvorschlaege.produkt_id
    JOIN produktpreise ON ( bestellvorschlaege.produktpreise_id = produktpreise.id )
    $where
    HAVING (basar <> 0)
  " ;
}

function basar_wert_brutto( $bestell_id = 0 ) {
  return sql_select_single_field(
    " SELECT IFNULL(sum( basar.basar * basar.bruttopreis ), 0.0 ) as wert
      FROM ( " .select_basar( $bestell_id ). " ) as basar "
  , 'wert'
  );
}

function muell_wert_brutto( $bestell_id = 0 ) {
  return sql_select_single_field(
    " SELECT IFNULL( sum( bestellprodukte.bruttopreis * bestellprodukte.muellmenge ), 0.0 ) as muell
      FROM ( " .select_bestellprodukte( $bestell_id ). " ) AS bestellprodukte "
  , 'muell'
  );
}

function verteilung_wert_brutto( $bestell_id = 0 ) {
  return sql_select_single_field(
    " SELECT IFNULL( sum( bestellprodukte.bruttopreis * bestellprodukte.verteilmenge ), 0.0 ) as wert
      FROM ( " .select_bestellprodukte( $bestell_id ). " ) AS bestellprodukte "
  , 'wert'
  );
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
		$sql = "UPDATE bestellvorschlaege
            set bestellmenge = NULL, liefermenge = NULL
            where gesamtbestellung_id = ".$bestell_id;
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
function verteilmengenZuweisen($bestell_id){
  $basar_id = sql_basar_id();

  need( getState($bestell_id)==STATUS_LIEFERANT , 'verteilmengenZuweisen: falscher Status der Bestellung' );

  $produkte = sql_bestellprodukte( $bestell_id );
  while( $produkt = mysql_fetch_array( $produkte ) ) {
    $produkt_id = $produkt['produkt_id'];
    // echo "<pre>".var_export( $produkt ) ."</pre>";
    $zuteilungen = zuteilungen_berechnen( $produkt );
    sql_update( 'bestellvorschlaege', array( 'gesamtbestellung_id' => $bestell_id, 'produkt_id' => $produkt_id ), array(
      'bestellmenge' => $zuteilungen['bestellmenge']
    , 'liefermenge' => $zuteilungen['bestellmenge']
    ) );
    // echo "<pre>". $produkt['produkt_name'] . "</pre>";
    // echo "<pre>". var_export( $zuteilungen ). "</pre>";
    // need(0);
    $festzuteilungen = $zuteilungen['festzuteilungen'];
    $toleranzzuteilungen = $zuteilungen['toleranzzuteilungen'];
    foreach( $festzuteilungen as $gruppen_id => $menge ) {
      if( $gruppen_id == $basar_id )
        continue;
      if( isset( $toleranzzuteilungen[$gruppen_id] ) ) {
        $menge += $toleranzzuteilungen[$gruppen_id];
        unset( $toleranzzuteilungen[$gruppen_id] );
      }
      $gruppenbestellung_id = sql_create_gruppenbestellung( $gruppen_id, $bestell_id );
      sql_insert( 'bestellzuordnung', array(
        'gruppenbestellung_id' => $gruppenbestellung_id, 'produkt_id' => $produkt_id
      , 'art' => 2, 'menge' => $menge
      ) );
    }
    // need( is_array( $toleranzzuteilungen ), "<pre>".var_export( $toleranzzuteilungen )."</pre>" );
    foreach( $toleranzzuteilungen as $gruppen_id => $menge ) {
      $gruppenbestellung_id = sql_create_gruppenbestellung( $gruppen_id, $bestell_id );
      sql_insert( 'bestellzuordnung', array(
        'gruppenbestellung_id' => $gruppenbestellung_id, 'produkt_id' => $produkt_id
      , 'art' => 2, 'menge' => $menge
      ) );
    }
  }
}

function changeLieferpreis_sql($preis_id, $produkt_id, $bestell_id){
  return sql_update( 'bestellvorschlaege'
  , array( 'produkt_id' => $produkt_id, 'gesamtbestellung_id' => $bestell_id )
  , array( 'produktpreise_id' => $preis_id )
  );
}

function changeLiefermengen_sql($menge, $produkt_id, $bestell_id){
  nur_fuer_dienst(1,3,4);
  return sql_update( 'bestellvorschlaege'
  , array( 'produkt_id' => $produkt_id, 'gesamtbestellung_id' => $bestell_id )
  , array( 'liefermenge' => $menge )
  );
}

function nichtGeliefert( $bestell_id, $produkt_id ) {
  doSql( "UPDATE bestellzuordnung
    INNER JOIN gruppenbestellungen
       ON gruppenbestellung_id = gruppenbestellungen.id
    SET menge =0
    WHERE art=2
      AND produkt_id = $produkt_id
      AND gesamtbestellung_id = $bestell_id
  ", LEVEL_IMPORTANT, "Konnte Verteilmengen nicht in DB ändern..."
  );
  doSql( "UPDATE bestellvorschlaege
    SET liefermenge = 0
    WHERE produkt_id = $produkt_id
      AND gesamtbestellung_id = $bestell_id
  ", LEVEL_IMPORTANT, "Konnte Liefermengen nicht in DB ändern..."
  );
}

function change_bestellmengen( $gruppen_id, $bestell_id, $produkt_id, $festmenge = -1, $toleranzmenge = -1 ) {
  $gruppenbestellung_id = sql_create_gruppenbestellung( $gruppen_id, $bestell_id );
  if( $festmenge >= 0 ) {
    $festmenge_alt = sql_select_single_field(
      "SELECT IFNULL( SUM( menge ), 0 ) AS festmenge FROM bestellzuordnung
       WHERE produkt_id = $produkt_id AND gruppenbestellung_id = $gruppenbestellung_id AND art=0"
    , 'festmenge'
    );
    if( $festmenge > $festmenge_alt ) {
      // Erhoehung der festmenge: zusaetzliche Bestellung am Ende der Schlange:
      sql_insert( 'bestellzuordnung', array(
        'produkt_id' => $produkt_id, 'gruppenbestellung_id' => $gruppenbestellung_id
      , 'menge' => $festmenge - $festmenge_alt, 'art' => 0
      ) );
    } elseif( $festmenge < $festmenge_alt ) {
      // bei Ruecktritt von vorheriger Bestellung: neue Bestellung stellt sich _hinten_ in die Reihe
      // (um Nachteile fuer andere Besteller zu minimieren):
      doSql( " DELETE FROM bestellzuordnung
               WHERE art=0 AND produkt_id = $produkt_id AND gruppenbestellung_id = $gruppenbestellung_id" );
      if( $festmenge > 0 ) {
        sql_insert( 'bestellzuordnung', array(
          'produkt_id' => $produkt_id, 'gruppenbestellung_id' => $gruppenbestellung_id
        , 'menge' => $festmenge, 'art' => 0
        ) );
      }
    } // else: ( $ festmenge == $festmenge_alt ): nix zu tun...
  }

  if( $toleranzmenge >= 0 ) {
    $toleranzmenge_alt = sql_select_single_field(
      "SELECT IFNULL( SUM( menge ), 0 ) AS toleranzmenge FROM bestellzuordnung
       WHERE produkt_id = $produkt_id AND gruppenbestellung_id = $gruppenbestellung_id AND art=1"
    , 'toleranzmenge'
    );
    if( $toleranzmenge_alt != $toleranzmenge ) {
      // toleranzmenge: zeitliche Reihenfolge ist hier (fast) egal, wir schreiben einfach neu:
      //
      doSql( " DELETE FROM bestellzuordnung
               WHERE art=1 AND produkt_id = $produkt_id AND gruppenbestellung_id = $gruppenbestellung_id" );
      if( $toleranzmenge > 0 ) {
        sql_insert( 'bestellzuordnung', array(
          'produkt_id' => $produkt_id, 'gruppenbestellung_id' => $gruppenbestellung_id
        , 'menge' => $toleranzmenge, 'art' => 1
        ) );
      }
    }
  }
}

function changeVerteilmengen_sql($menge, $gruppen_id, $produkt_id, $bestell_id){
  $gruppenbestellung_id = sql_create_gruppenbestellung( $gruppen_id, $bestell_id );
  doSql( " DELETE FROM bestellzuordnung
           WHERE art=2 AND produkt_id=$produkt_id AND gruppenbestellung_id = $gruppenbestellung_id" );
  return sql_insert( 'bestellzuordnung', array(
    'produkt_id' => $produkt_id
  , 'menge' => $menge
  , 'gruppenbestellung_id' => $gruppenbestellung_id
  , 'art' => 2
  ) );
}

function sql_basar2group($gruppe, $produkt, $bestell_id, $menge){
  $gruppenbestellung_id = sql_create_gruppenbestellung( $gruppe, $bestell_id );
  $sql = " INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art)
     VALUES ('$produkt','$gruppenbestellung_id','$menge', 2)
     ON DUPLICATE KEY UPDATE menge = menge + $menge
   ";
  return doSql($sql, LEVEL_IMPORTANT, "Konnte Basarkauf nicht eintragen..");
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
   return sql_insert( 'bestellzuordnung', array(
     'produkt_id' => $produkt_id
   , 'gruppenbestellung_id' => $gruppenbestellung_id
   , 'menge' => $bestellmenge
   , 'art' => 1
   ) );
}



////////////////////////////////////
//
// funktionen fuer gruppen-, lieferanten-, und bankkonto: transaktionen
//
// "soll" und "haben" sind immer (wo nicht anders angegeben) aus sicht der FC
//
////////////////////////////////////


// TODO: transaktionsart: zur Klassifikation der Gruppe-13-Transaktionen benutzen!
//
function sql_gruppen_transaktion(
  $transaktionsart, $gruppen_id, $summe,
  $notiz ="",
  $kontobewegungs_datum = 0, $lieferanten_id = 0, $konterbuchung_id = 0
) {
  global $dienstkontrollblatt_id, $hat_dienst_IV, $mysqlheute;

  need( $gruppen_id or $lieferanten_id );
  $kontobewegungs_datum or $kontobewegungs_datum = $mysqlheute;

  return sql_insert( 'gruppen_transaktion', array(
    'type' => $transaktionsart
  , 'gruppen_id' => $gruppen_id
  , 'lieferanten_id' => $lieferanten_id
  /* , 'eingabe_zeit' => 'NOW()'  klappt so nicht, macht die DB aber sowieso automatisch! */
  , 'summe' => $summe
  , 'kontobewegungs_datum' => $kontobewegungs_datum
  , 'dienstkontrollblatt_id' => $dienstkontrollblatt_id
  , 'notiz' => $notiz
  , 'konterbuchung_id' => $konterbuchung_id
  ) );
}

function sql_gruppen_umlage( $betrag, $valuta, $notiz ) {
  $gruppen = sql_aktive_bestellgruppen();
  while( $gruppe = mysql_fetch_array( $gruppen ) ) {
    if( $gruppe['mitgliederzahl'] > 0 ) {
      sql_doppelte_transaktion(
        array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_UMLAGE )
      , array( 'konto_id' => -1, 'gruppen_id' => $gruppe['id'], 'transaktionsart' => TRANSAKTION_TYP_UMLAGE )
      , $betrag * $gruppe['mitgliederzahl']
      , $valuta
      , "$notiz"
      );
    }
  }
}

function sql_bank_transaktion(
  $konto_id, $auszug_jahr, $auszug_nr
, $haben, $valuta
, $dienstkontrollblatt_id, $notiz
, $konterbuchung_id
) {
  global $mysqlheute;

  need( $konto_id and $auszug_jahr and $auszug_nr );
  need( $dienstkontrollblatt_id and $notiz );

  return sql_insert( 'bankkonto', array(
    'konto_id' => $konto_id
  , 'kontoauszug_jahr' => $auszug_jahr
  , 'kontoauszug_nr' => $auszug_nr
  , 'betrag' => $haben
  , 'buchungsdatum' => $mysqlheute
  , 'valuta' => $valuta
  , 'dienstkontrollblatt_id' => $dienstkontrollblatt_id
  , 'kommentar' => $notiz
  , 'konterbuchung_id' => $konterbuchung_id
  ) );
}

function sql_link_transaction( $soll_id, $haben_id ) {
  if( $soll_id > 0 )
    sql_update( 'bankkonto', $soll_id, array( 'konterbuchung_id' => $haben_id ) );
  else
    sql_update( 'gruppen_transaktion', -$soll_id, array( 'konterbuchung_id' => $haben_id ) );

  if( $haben_id > 0 )
    sql_update( 'bankkonto', $haben_id, array( 'konterbuchung_id' => $soll_id ) );
  else
    sql_update( 'gruppen_transaktion', -$haben_id, array( 'konterbuchung_id' => $soll_id ) );
}

/*
 * konto_id == -1 bedeutet gruppen_transaktion, sonst bankkonto
 */
function sql_doppelte_transaktion( $soll, $haben, $betrag, $valuta, $notiz, $spende = false ) {
  global $dienstkontrollblatt_id, $login_gruppen_id;

  if( $spende ) {
    need( $betrag > 0, "Bitte nur positive Spenden!" );
    $soll['konto_id'] = -1;
    $soll['gruppen_id'] = sql_muell_id();
    $soll['transaktionsart'] = TRANSAKTION_TYP_SPENDE;
    $haben['konto_id'] = -1;
    $haben['transaktionsart'] = TRANSAKTION_TYP_SPENDE;
    need( $haben['gruppen_id'] == $login_gruppen_id );
  } else {
    nur_fuer_dienst(4,5);
    need( $dienstkontrollblatt_id, 'Kein Dienstkontrollblatt Eintrag!' );
  }
  need( $notiz, 'Bitte Notiz angeben!' );
  need( isset( $soll['konto_id'] ) and isset( $haben['konto_id'] ) );


  if( $soll['konto_id'] == -1 ) {
    $soll_id = -1 * sql_gruppen_transaktion(
      adefault( $soll, 'transaktionsart', 0 ), adefault( $soll, 'gruppen_id', 0 ), $betrag
    , $notiz, $valuta, adefault( $soll, 'lieferanten_id', 0 )
    );
  } else {
    $soll_id = sql_bank_transaktion(
      $soll['konto_id'], adefault( $soll, 'auszug_jahr', '' ), adefault( $soll, 'auszug_nr', '' )
    , -$betrag, $valuta, $dienstkontrollblatt_id, $notiz, 0
    );
  }

  if( $haben['konto_id'] == -1 ) {
    $haben_id = -1 * sql_gruppen_transaktion(
      adefault( $haben, 'transaktionsart', 0 ), adefault( $haben, 'gruppen_id', 0 ), -$betrag
    , $notiz, $valuta, adefault( $haben, 'lieferanten_id', 0 )
    );
  } else {
    $haben_id = sql_bank_transaktion(
      $haben['konto_id'], adefault( $haben, 'auszug_jahr', '' ), adefault( $haben, 'auszug_nr', '' )
    , $betrag, $valuta, $dienstkontrollblatt_id, $notiz, 0
    );
  }

  sql_link_transaction( $soll_id, $haben_id );
  return;
}



/*
 * buchung_gruppe_bank: wertet formular zu eingabe eine einzahlung einer
 * gruppe auf ein bankkonto aus.
 */
function buchung_gruppe_bank() {
  global $betrag, $gruppen_id, $notiz, $day, $month, $year, $auszug_jahr, $auszug_nr, $konto_id;
  global $specialgroups;
  $problems = false;
  // echo "buchung_gruppe_bank: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $gruppen_id or need_http_var( 'gruppen_id', 'u' );
  $gruppen_name = sql_gruppenname( $gruppen_id );
  if( ! $notiz ) {
    if( $betrag < 0 ) {
      need_http_var( 'notiz', 'H' );
    } else {
      get_http_var( 'notiz', 'H', "Einzahlung Gruppe $gruppen_name" );
    }
  }
  $day or need_http_var( 'day', 'u' );
  $month or need_http_var( 'month', 'u' );
  $year or need_http_var( 'year', 'u' );
  $konto_id or need_http_var( 'konto_id', 'u' );
  $auszug_jahr or need_http_var( 'auszug_jahr', 'u' );
  $auszug_nr or need_http_var( 'auszug_nr', 'u' );
  if( ! $notiz ) {
    ?> <div class='warn'>Bitte Notiz eingeben!</div> <?
    $problems = true;
  }
  if( ! $konto_id ) {
    ?> <div class='warn'>Bitte Konto wählen!</div> <?
    $problems = true;
  }
  if( ! $gruppen_id ) {
    ?> <div class='warn'>Bitte Gruppe wählen!</div> <?
    $problems = true;
  }
  need( ! in_array( $gruppen_id, $specialgroups ) );
  need( sql_gruppenname( $gruppen_id ) );

  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , $betrag
    , "$year-$month-$day"
    , "$notiz"
    );
  }
}

function buchung_bank_sonderausgabe() {
  global $betrag, $notiz, $day, $month, $year, $auszug_jahr, $auszug_nr, $konto_id;
  $problems = false;
  // echo "buchung_sonderausgabe: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $notiz or need_http_var( 'notiz', 'H' );
  $day or need_http_var( 'day', 'u' );
  $month or need_http_var( 'month', 'u' );
  $year or need_http_var( 'year', 'u' );
  $konto_id or need_http_var( 'konto_id', 'u' );
  $auszug_jahr or need_http_var( 'auszug_jahr', 'u' );
  $auszug_nr or need_http_var( 'auszug_nr', 'u' );
  if( ! $notiz ) {
    ?> <div class='warn'>Bitte Notiz eingeben!</div> <?
    $problems = true;
  }
  if( ! $konto_id ) {
    ?> <div class='warn'>Bitte Konto wählen!</div> <?
    $problems = true;
  }

  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_SONDERAUSGABEN )
    , array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , $betrag
    , "$year-$month-$day"
    , "$notiz"
    );
  }
}

function buchung_gruppe_sonderausgabe() {
  global $betrag, $notiz, $day, $month, $year, $gruppen_id, $specialgroups;
  $problems = false;
  // echo "buchung_sonderausgabe: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $notiz or need_http_var( 'notiz', 'H' );
  $day or need_http_var( 'day', 'u' );
  $month or need_http_var( 'month', 'u' );
  $year or need_http_var( 'year', 'u' );
  $gruppen_id or need_http_var( 'gruppen_id', 'U' );
  if( ! $notiz ) {
    ?> <div class='warn'>Bitte Notiz eingeben!</div> <?
    $problems = true;
  }
  if( ! $gruppen_id ) {
    ?> <div class='warn'>Bitte Gruppe wählen!</div> <?
    $problems = true;
  }
  need( ! in_array( $gruppen_id, $specialgroups ) );
  need( sql_gruppenname( $gruppen_id ) );

  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_SONDERAUSGABEN )
    , $betrag
    , "$year-$month-$day"
    , "$notiz"
    );
  }
}

function buchung_lieferant_bank() {
  global $betrag, $lieferanten_id, $notiz, $day, $month, $year, $auszug_jahr, $auszug_nr, $konto_id;
  $betrag or need_http_var( 'betrag', 'f' );
  $lieferanten_id or need_http_var( 'lieferanten_id', 'U' );
  $day or need_http_var( 'day', 'U' );
  $month or need_http_var( 'month', 'U' );
  $year or need_http_var( 'year', 'U' );
  $notiz or need_http_var( 'notiz', 'H' );
  $konto_id or need_http_var( 'konto_id', 'U' );
  $auszug_jahr or need_http_var( 'auszug_jahr', 'U' );
  $auszug_nr or need_http_var( 'auszug_nr', 'U' );
  $notiz or get_http_var( 'notiz', 'H' );
  get_http_var( 'notiz', 'H' );
  sql_doppelte_transaktion(
    array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
  , array( 'konto_id' => -1, 'lieferanten_id' => $lieferanten_id )
  , $betrag
  , "$year-$month-$day"
  , "$notiz"
  );
}

function buchung_gruppe_lieferant() {
  global $betrag, $lieferanten_id, $gruppen_id, $notiz, $day, $month, $year;
  $betrag or need_http_var( 'betrag', 'f' );
  $lieferanten_id or need_http_var( 'lieferanten_id', 'U' );
  $gruppen_id or need_http_var( 'gruppen_id', 'U' );
  $notiz or need_http_var( 'notiz', 'H' );
  $day or need_http_var( 'day', 'U' );
  $month or need_http_var( 'month', 'U' );
  $year or need_http_var( 'year', 'U' );
  sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
  , array( 'konto_id' => -1, 'lieferanten_id' => $lieferanten_id )
  , $betrag
  , "$year-$month-$day"
  , "$notiz"
  );
}

function buchung_gruppe_gruppe() {
  global $betrag, $gruppen_id, $nach_gruppen_id, $notiz, $day, $month, $year;
  // echo "buchung_gruppe_gruppe: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $gruppen_id or need_http_var( 'gruppen_id', 'U' );
  $nach_gruppen_id or need_http_var( 'nach_gruppen_id', 'U' );
  $notiz or need_http_var( 'notiz', 'H' );
  $day or need_http_var( 'day', 'U' );
  $month or need_http_var( 'month', 'U' );
  $year or need_http_var( 'year', 'U' );
  sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => $nach_gruppen_id )
  , array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
  , $betrag
  , "$year-$month-$day"
  , "$notiz"
  );
}

function buchung_bank_bank() {
  global $betrag, $konto_id, $auszug_jahr, $auszug_nr
       , $nach_konto_id
       , $nach_auszug_jahr, $nach_auszug_nr
       , $notiz, $day, $month, $year;
  // echo "buchung_bank_bank: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $konto_id or need_http_var( 'konto_id', 'U' );
  $auszug_jahr or need_http_var( 'auszug_jahr', 'U' );
  $auszug_nr or need_http_var( 'auszug_nr', 'U' );
  $nach_konto_id or need_http_var( 'nach_konto_id', 'U' );
  $nach_auszug_jahr or need_http_var( 'nach_auszug_jahr', 'U' );
  $nach_auszug_nr or need_http_var( 'nach_auszug_nr', 'U' );
  $notiz or need_http_var( 'notiz', 'H' );
  $day or need_http_var( 'day', 'U' );
  $month or need_http_var( 'month', 'U' );
  $year or need_http_var( 'year', 'U' );
  sql_doppelte_transaktion(
    array( 'konto_id' => $konto_id, 'auszug_jahr' => $auszug_jahr, 'auszug_nr' => $auszug_nr )
  , array( 'konto_id' => $nach_konto_id, 'auszug_jahr' => $nach_auszug_jahr, 'auszug_nr' => $nach_auszug_nr )
  , $betrag
  , "$year-$month-$day"
  , "$notiz"
  );
}


/**
 *
 */
function sql_finish_transaction( $soll_id , $konto_id , $receipt_nr , $receipt_year, $valuta, $notiz ){
  global $dienstkontrollblatt_id;
  fail_if_readonly();
  nur_fuer_dienst_IV();

  $row = sql_select_single_row( "SELECT * FROM gruppen_transaktion WHERE id=$soll_id" );

  $haben_id = sql_bank_transaktion(
    $konto_id, $receipt_year, $receipt_nr
  , $row['summe'], $valuta
  , $dienstkontrollblatt_id, $notiz, 0
  );

  sql_link_transaction( -$soll_id, $haben_id );

  return sql_update( 'gruppen_transaktion', $soll_id, array(
    'dienstkontrollblatt_id' => $dienstkontrollblatt_id
  ) );
}


function sql_get_group_transactions( $gruppen_id, $lieferanten_id, $from_date = NULL, $to_date = NULL ) {
  $filter = "";
  $and = "WHERE";
  if( $gruppen_id ) {
    $filter .= " $and ( gruppen_transaktion.gruppen_id = $gruppen_id )";
    $and = "AND";
  }
  if( $lieferanten_id ) {
    $filter .= " $and ( gruppen_transaktion.lieferanten_id = $lieferanten_id )";
    $and = "AND";
  }
  if( $from_date ) {
    $filter .= " $and ( kontobewegungs_datum >= '$from_date' )";
    $and = "AND";
  }
  if( $to_date ) {
    $filter .= " $and ( kontobewegungs_datum <= '$to_date' )";
    $and = "AND";
  }
  $sql = "
    SELECT gruppen_transaktion.id, type, summe, kontobewegungs_datum
         , konterbuchung_id, gruppen_transaktion.notiz
         , dienstkontrollblatt_id
         , DATE_FORMAT(gruppen_transaktion.eingabe_zeit,'%d.%m.%Y') AS date
         , DATE_FORMAT(gruppen_transaktion.kontobewegungs_datum,'%d.%m.%Y') AS valuta_trad
         , DATE_FORMAT(gruppen_transaktion.kontobewegungs_datum,'%Y%m%d') AS valuta_kan
         , dienstkontrollblatt.name as dienst_name
    FROM gruppen_transaktion
    LEFT JOIN dienstkontrollblatt ON dienstkontrollblatt.id = dienstkontrollblatt_id
    $filter
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
           , bankkonto.kommentar as kommentar
           , bankkonto.valuta as valuta
           , bankkonto.buchungsdatum as buchungsdatum
           , bankkonto.konterbuchung_id as konterbuchung_id
           , bankkonten.name as kontoname
           , bankkonten.id as konto_id
           , dienstkontrollblatt.name as dienst_name
      FROM bankkonto
      JOIN bankkonten ON bankkonten.id = bankkonto.konto_id
      LEFT JOIN dienstkontrollblatt ON dienstkontrollblatt.id = bankkonto.dienstkontrollblatt_id
      WHERE bankkonto.id = $id
    ";
  } else {
    $sql = "
      SELECT bankkonto.kontoauszug_jahr
           , bankkonto.kontoauszug_nr
           , (-summe) as haben
           , gruppen_transaktion.notiz as kommentar
           , gruppen_transaktion.type as transaktionstyp
           , gruppen_transaktion.kontobewegungs_datum as valuta
           , gruppen_transaktion.eingabe_zeit as buchungsdatum
           , gruppen_transaktion.konterbuchung_id as konterbuchung_id
           , bankkonten.name as kontoname
           , gruppen_transaktion.gruppen_id as gruppen_id
           , gruppen_transaktion.lieferanten_id as lieferanten_id
           , dienstkontrollblatt.name as dienst_name
      FROM gruppen_transaktion
      LEFT JOIN bankkonto
             ON bankkonto.id = gruppen_transaktion.konterbuchung_id
      LEFT JOIN bankkonten
             ON bankkonten.id = bankkonto.konto_id
      LEFT JOIN dienstkontrollblatt ON dienstkontrollblatt.id = gruppen_transaktion.dienstkontrollblatt_id
      WHERE gruppen_transaktion.id = ".(-$id)."
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

function sql_bankkonto_saldo( $konto_id, $auszug_jahr = 0, $auszug_nr = FALSE ) {
  $where = "WHERE (konto_id=$konto_id)";
  if( $auszug_jahr ) {
    if( $auszug_nr !== FALSE ) {
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
  return sql_select_single_field( "
    SELECT IFNULL(sum( betrag ),0.0) as saldo
    FROM bankkonto
    $where
  " , 'saldo'
  );
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
    , DATE_FORMAT(valuta,'%d.%m.%Y') as valuta_trad
    , DATE_FORMAT(buchungsdatum,'%d.%m.%Y') as buchungsdatum_trad
    , dienstkontrollblatt.name as dienst_name
    FROM bankkonto
    JOIN bankkonten ON bankkonten.id=konto_id
    LEFT JOIN dienstkontrollblatt ON dienstkontrollblatt.id = dienstkontrollblatt_id
    $where
    $groupby
    ORDER BY konto_id, kontoauszug_jahr, kontoauszug_nr
  " );
}


////////////////////////////////////
//
//  Pfandbewegungen buchen
//
////////////////////////////////////

define( 'PFAND_OPT_GRUPPEN_INAKTIV', 1 );
define( 'PFAND_OPT_ALLE_BESTELLUNGEN', 2 );

// pfandzuordnung_{lieferant,gruppe}:
// schreibe _gesamtmenge_ fuer eine (bestellung,verpackung) oder (bestellung,gruppe),
// _ersetzt_ fruehere zuordnungen (nicht additiv!)
//
function sql_pfandzuordnung_lieferant( $bestell_id, $verpackung_id, $anzahl_voll, $anzahl_leer ) {
  if( $anzahl_voll > 0 or $anzahl_leer > 0 ) {
    sql_insert( 'lieferantenpfand' , array(
        'verpackung_id' => $verpackung_id
      , 'bestell_id' => $bestell_id
      , 'anzahl_voll' => $anzahl_voll
      , 'anzahl_leer' => $anzahl_leer
      )
    , true
    );
  } else {
    doSql( "DELETE FROM lieferantenpfand WHERE bestell_id=$bestell_id AND verpackung_id=$verpackung_id" ); 
  }
}

function sql_pfandzuordnung_gruppe( $bestell_id, $gruppen_id, $anzahl_leer ) {
  if( $anzahl_leer > 0 ) {
    // pfandrueckgabe ist jetzt an eine gesamtbestellung gebunden, und wir brauchen eine gruppenbestellung:
    sql_create_gruppenbestellung( $gruppen_id, $bestell_id );
    return sql_insert( 'gruppenpfand', array(
        'gruppen_id' => $gruppen_id
      , 'bestell_id' => $bestell_id
      , 'anzahl_leer' => $anzahl_leer
      , 'pfand_wert' => 0.16
      )
    , true
    );
  } else {
    return doSql( "DELETE FROM gruppenpfand  WHERE bestell_id=$bestell_id AND gruppen_id=$gruppen_id" ); 
  }
}

////////////////////////////////////////////
//
// funktionen fuer gruppen-, lieferantenkonto: abfrage kontostaende/kontobewegungen
//
////////////////////////////////////////////

define( 'TRANSAKTION_TYP_UNDEFINIERT', 0 );      // noch nicht zugeordnet
define( 'TRANSAKTION_TYP_ANFANGSGUTHABEN', 1 );  // anfangsguthaben gruppen _und_ lieferanten
define( 'TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN', 2 ); // Ausgleich/Umlage Differenz Anfangsguthaben
define( 'TRANSAKTION_TYP_SPENDE', 3 );           // freiwillige Spende
define( 'TRANSAKTION_TYP_SONDERAUSGABEN', 4 );   // Mitgliedsbeitrag Haus der Natur, Kontofuehrung, ...
define( 'TRANSAKTION_TYP_UMLAGE', 5 );           // Verlustumlage auf alle Mitglieder
define( 'TRANSAKTION_TYP_SOCKEL', 6 );           // geparkte Sockelbetraege
define( 'TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE', 7 ); // Umlage Bestellverluste (auch: ein paar ganz alte Basarabrechnungen)
define( 'TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN', 8 ); // Umlage Sonderausgaben
define( 'TRANSAKTION_TYP_UMBUCHUNG_SPENDE', 9 );   // umbuchung von spenden nach TRANSAKTION_TYP_AUSGLEICH_*
define( 'TRANSAKTION_TYP_UMBUCHUNG_UMLAGE', 10 );  // umbuchung von umlagen nach TRANSAKTION_TYP_AUSGLEICH_*
define( 'TRANSAKTION_TYP_STORNO', 98 );          // Buchungen, die sich gegenseitig neutralisieren
// define( 'TRANSAKTION_TYP_SONSTIGES', 99 ); // nicht benutzt

function transaktion_typ_string( $typ ) {
  switch( $typ ) {
    case TRANSAKTION_TYP_UNDEFINIERT:
      return 'unklassifiziert';
    case TRANSAKTION_TYP_ANFANGSGUTHABEN:
      return 'Anfangsguthaben';
    case TRANSAKTION_TYP_SPENDE:
      return 'Spende';
    case TRANSAKTION_TYP_UMBUCHUNG_SPENDE:
      return 'Umbuchung von Spenden';
    case TRANSAKTION_TYP_SONDERAUSGABEN:
      return 'Sonderausgabe';
    case TRANSAKTION_TYP_UMLAGE:
      return 'Verlustumlage auf Mitglieder';
    case TRANSAKTION_TYP_UMBUCHUNG_UMLAGE:
      return 'Umbuchung von Umlagen';
    case TRANSAKTION_TYP_SOCKEL:
      return 'Sockeleinlage';
    case TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE:
      return 'Ausgleich für Bestellverluste';
    case TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN:
      return 'Ausgleich für Sonderausgaben';
    case TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN:
      return 'Ausgleich für Differenz Anfangsguthaben';
    case TRANSAKTION_TYP_STORNO:
      return 'Storno';
    case TRANSAKTION_TYP_SONSTIGES:
      return 'Sonstiges';
  }
  return "FEHLER: undefinierter Typ: $typ";
}


// optionen fuer kontoabfragen:
//
// betraege werden immer als 'soll' der fc, also schuld der fc
// (an gruppen, lieferanten oder bank) zurueckgegeben (ggf. also negativ)
//
define( 'OPTION_WAREN_NETTO_SOLL', 1 );         /* waren ohne pfand */
define( 'OPTION_WAREN_BRUTTO_SOLL', 2 );
define( 'OPTION_ENDPREIS_SOLL', 3 );            /* waren brutto inclusive pfand (nur gruppenseitig sinnvoll) */
define( 'OPTION_PFAND_VOLL_BRUTTO_SOLL', 4 );   /* schuld aus kauf voller pfandverpackungen */
define( 'OPTION_PFAND_VOLL_NETTO_SOLL', 5 );
define( 'OPTION_PFAND_VOLL_ANZAHL', 6 );
define( 'OPTION_PFAND_LEER_BRUTTO_SOLL', 7 );   /* schuld aus rueckgabe leerer pfandverpackungen */
define( 'OPTION_PFAND_LEER_NETTO_SOLL', 8 ); 
define( 'OPTION_PFAND_LEER_ANZAHL', 9 );
define( 'OPTION_EXTRA_BRUTTO_SOLL', 10 );   /* sonstiges: Rabatte, Versandkosten, ... */


/* select_bestellungen_soll_gruppen:
 *   liefert als skalarer subquery schuld der FC an gruppen aus bestellungen, und zugehoeriger
 *   pfandbewegungen (auch rueckgabe der betreffenden woche!)
 *   - $using ist array von tabellen, die aus dem uebergeordneten query benutzt werden sollen;
 *     auswirkungen haben: 'gesamtbestellungen', 'bestellgruppen'
 *   - $art ist eine der optionen oben; SOLL immer aus sicht der FC
*/
function select_bestellungen_soll_gruppen( $art, $using = array() ) {
  switch( $art ) {
    case OPTION_ENDPREIS_SOLL:
      $expr = "( -1.0 * bestellzuordnung.menge * produktpreise.preis)";
      $query = 'waren';
      break;
    case OPTION_WAREN_BRUTTO_SOLL:
      $expr = "( bestellzuordnung.menge * ( produktpreise.pfand - produktpreise.preis ) )";
      $query = 'waren';
      break;
    case OPTION_WAREN_NETTO_SOLL:
      $expr = "( bestellzuordnung.menge * ( produktpreise.pfand - produktpreise.preis ) / ( 1.0 + produktpreise.mwst / 100.0 ) )";
      $query = 'waren';
      break;
    case OPTION_PFAND_VOLL_BRUTTO_SOLL:
      $expr = "( -1.0 * bestellzuordnung.menge * produktpreise.pfand )";
      $query = 'waren';
      break;
    case OPTION_PFAND_LEER_BRUTTO_SOLL:
      $expr = "( gruppenpfand.anzahl_leer * gruppenpfand.pfand_wert )";
      $query = 'pfand';
      break;
    case OPTION_PFAND_LEER_ANZAHL:
      $expr = "( gruppenpfand.anzahl_leer )";
      $query = 'pfand';
      break;
    default:
      error(__LINE__,__FILE__, "select_bestellungen_soll_gruppen: bitte Funktionsaufruf anpassen!", debug_backtrace());
  }
  switch( $query ) {
    case 'waren':
      return "
        SELECT IFNULL( sum( $expr ), 0.0 )
        FROM bestellzuordnung
        JOIN gruppenbestellungen
          ON gruppenbestellungen.id = bestellzuordnung.gruppenbestellung_id
        JOIN bestellvorschlaege
          ON (bestellvorschlaege.produkt_id = bestellzuordnung.produkt_id)
             AND ( bestellvorschlaege.gesamtbestellung_id = gruppenbestellungen.gesamtbestellung_id )
        JOIN produktpreise
          ON produktpreise.id = bestellvorschlaege.produktpreise_id
      " . need_joins( $using, array(
          'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                   ON gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id'
        ) ) . "
        WHERE (bestellzuordnung.art=2) " . use_filters( $using, array(
          'bestellgruppen' => 'gruppenbestellungen.bestellguppen_id = bestellgruppen.id'
        , 'gesamtbestellungen' => 'gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id'
        ) );
    case 'pfand':
      return "
        SELECT IFNULL( sum( $expr ), 0.0 )
        FROM gruppenpfand
        " . need_joins( $using, array(
            'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                     ON gesamtbestellungen.id = gruppenpfand.bestell_id'
          ) ) . "
        WHERE 1 " . use_filters( $using, array(
          'bestellgruppen' => 'gruppenpfand.gruppen_id = bestellgruppen.id'
        , 'gesamtbestellungen' => 'gruppenpfand.bestell_id = gesamtbestellungen.id'
        ) );
  }
}

/* select_bestellungen_soll_lieferanten:
 *   liefert als skalarer subquery forderung von lieferanten aus bestellungen
 *   $using ist array von tabellen, die aus dem uebergeordneten query benutzt werden sollen;
 *   auswirkung haben: 'gesamtbestellungen', 'lieferanten'
*/
function select_bestellungen_soll_lieferanten( $art, $using = array() ) {
  switch( $art ) {
    case OPTION_WAREN_BRUTTO_SOLL:
      $expr = "( bestellvorschlaege.liefermenge * ( produktpreise.preis - produktpreise.pfand ) )";
      $query = 'waren';
      break;
    case OPTION_WAREN_NETTO_SOLL:
      $expr = "( bestellvorschlaege.liefermenge * ( (produktpreise.preis - produktpreise.pfand ) / ( 1.0 + produktpreise.mwst / 100.0 ) ) )";
      $query = 'waren';
      break;
    case OPTION_PFAND_VOLL_NETTO_SOLL:
      $expr = "( lieferantenpfand.anzahl_voll * pfandverpackungen.wert )";
      $query = 'pfand';
      break;
    case OPTION_PFAND_VOLL_BRUTTO_SOLL:
      $expr = "( lieferantenpfand.anzahl_voll * pfandverpackungen.wert * ( 1.0 + pfandverpackungen.mwst / 100.0 ) )";
      $query = 'pfand';
      break;
    case OPTION_PFAND_LEER_NETTO_SOLL:
      $expr = "( -1.0 * lieferantenpfand.anzahl_leer * pfandverpackungen.wert )";
      $query = 'pfand';
      break;
    case OPTION_PFAND_LEER_BRUTTO_SOLL:
      $expr = "( -1.0 * lieferantenpfand.anzahl_leer * pfandverpackungen.wert * ( 1.0 + pfandverpackungen.mwst / 100.0 ) )";
      $query = 'pfand';
      break;
    case OPTION_PFAND_VOLL_ANZAHL:
      $expr = "( lieferantenpfand.anzahl_voll )";
      $query = 'pfand';
      break;
    case OPTION_PFAND_LEER_ANZAHL:
      $expr = "( lieferantenpfand.anzahl_leer )";
      $query = 'pfand';
      break;
    case OPTION_EXTRA_BRUTTO_SOLL:
      $query = 'extra';
      break;
    default:
      error(__LINE__,__FILE__, "select_bestellungen_soll_lieferanten: bitte Funktionsaufruf anpassen!", debug_backtrace());
  }
  switch( $query ) {
    case 'waren':
      return "
        SELECT IFNULL( sum( $expr ), 0.0 )
          FROM bestellvorschlaege
          JOIN produktpreise
            ON produktpreise.id = bestellvorschlaege.produktpreise_id
      " . need_joins( $using, array(
          'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                   ON gesamtbestellungen.id = bestellvorschlaege.gesamtbestellung_id'
      ) ) . "
        WHERE true " . use_filters( $using, array(
          'lieferanten' => 'lieferanten.id = gesamtbestellungen.lieferanten_id'
        , 'gesamtbestellungen' => 'bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id'
        ) );
    case 'pfand':
      return "
        SELECT IFNULL( sum( $expr ), 0.0 )
        FROM lieferantenpfand
        " . need_joins( $using, array(
            'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                     ON gesamtbestellungen.id = lieferantenpfand.bestell_id'
          , 'pfandverpackungen' => ' pfandverpackungen ON pfandverpackungen.id = lieferantenpfand.verpackung_id '
          ) ) . "
        WHERE 1 " . use_filters( $using, array(
          'lieferanten' => 'pfandverpackungen.lieferanten_id = lieferanten.id'
        , 'gesamtbestellungen' => 'lieferantenpfand.bestell_id = gesamtbestellungen.id'
        , 'pfandverpackungen' => 'lieferantenpfand.verpackung_id = pfandverpackungen.id'
        ) );
    case 'extra':
      return "
        SELECT IFNULL( sum( bla.extra_soll ), 0.0 )
        FROM gesamtbestellungen as bla
        WHERE 1 " .use_filters( $using, array(
          'lieferanten' => 'lieferanten.id = bla.lieferanten_id'
        , 'gesamtbestellungen' => 'bla.id = gesamtbestellungen.id'
        ) );
  }
}

/*  select_transaktionen_haben_gruppen:
 *   liefert als skalarer subquery schuld an gruppen aus gruppen_transaktion
 *   aus $using werden verwendet: 'bestellgruppen'
 */
function select_transaktionen_soll_gruppen( $using = array() ) {
  return "
    SELECT IFNULL( sum( summe ), 0.0 )
      FROM gruppen_transaktion
     WHERE ( gruppen_transaktion.gruppen_id > 0 ) " . use_filters( $using, array(
        'bestellgruppen' => 'bestellgruppen.id = gruppen_transaktion.gruppen_id'
  ) );
}

/*  select_transaktionen_soll_lieferanten:
 *   liefert als skalarer subquery schuld an lieferanten aus gruppen_transaktion
 *   aus $using werden verwendet: 'lieferanten'
 */
function select_transaktionen_soll_lieferanten( $using = array() ) {
  return "
    SELECT IFNULL( sum( summe ), 0.0 )
      FROM gruppen_transaktion
     WHERE ( gruppen_transaktion.lieferanten_id > 0 ) " . use_filters( $using, array(
       'lieferanten' => 'gruppen_transaktion.lieferanten_id = lieferanten.id'
  ) );
}

function select_waren_soll_gruppen( $using = array() ) {
  return select_bestellungen_soll_gruppen( OPTION_WAREN_BRUTTO_SOLL, $using );
}

function select_waren_soll_lieferanten( $using = array() ) {
  return select_bestellungen_soll_lieferanten( OPTION_WAREN_BRUTTO_SOLL, $using );
}

function select_pfand_soll_gruppen( $using = array() ) {
  return " SELECT (
      (" .select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_BRUTTO_SOLL, $using ). ")
    + (" .select_bestellungen_soll_gruppen( OPTION_PFAND_VOLL_BRUTTO_SOLL, $using ). ")
    ) ";
}

function select_pfand_soll_lieferanten( $using = array() ) {
  return " SELECT (
      (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_BRUTTO_SOLL, $using ). ")
    + (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_BRUTTO_SOLL, $using ). ")
    ) ";
}

function select_extra_soll_lieferanten( $using = array() ) {
  return select_bestellungen_soll_lieferanten( OPTION_EXTRA_BRUTTO_SOLL, $using );
}

function select_soll_lieferanten( $using = array() ) {
  return " SELECT (
      (" .select_waren_soll_lieferanten( $using ). ")
    + (" .select_pfand_soll_lieferanten( $using ). ")
    + (" .select_transaktionen_soll_lieferanten( $using ). ")
    + (" .select_extra_soll_lieferanten( $using ). ")
    ) ";
}

function select_soll_gruppen( $using = array() ) {
  return " SELECT (
      (" .select_waren_soll_gruppen( $using ). ")
    + (" .select_pfand_soll_gruppen( $using ). ")
    + (" .select_transaktionen_soll_gruppen( $using ). ")
  ) ";
}



function sql_gruppenpfand( $lieferanten_id = 0, $bestell_id = 0, $group_by = 'bestellgruppen.id' ) {
  $on = '';
  if( $lieferanten_id ) {
    $on = " ON gesamtbestellungen.lieferanten_id = $lieferanten_id";
  }
  if( $bestell_id ) {
    $on .= " AND gesamtbestellungen.id = $bestell_id";
  }
  return doSql( "
    SELECT
      bestellgruppen.id as gruppen_id
    , bestellgruppen.aktiv as aktiv
    , bestellgruppen.name as gruppen_name
    , bestellgruppen.id % 1000 as gruppen_nummer
    , sum( (".select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_ANZAHL, array( 'gesamtbestellungen', 'bestellgruppen' ) ).") ) AS pfand_leer_anzahl
    , sum( (".select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_BRUTTO_SOLL, array( 'gesamtbestellungen', 'bestellgruppen' ) ).") ) AS pfand_leer_brutto_soll
    , sum( (".select_bestellungen_soll_gruppen( OPTION_PFAND_VOLL_BRUTTO_SOLL, array( 'gesamtbestellungen', 'bestellgruppen' ) ).") ) AS pfand_voll_brutto_soll
    FROM bestellgruppen
    JOIN gesamtbestellungen
      $on
    LEFT JOIN gruppenpfand
      ON gruppenpfand.bestell_id = gesamtbestellungen.id
         AND gruppenpfand.gruppen_id = bestellgruppen.id
    GROUP BY $group_by
    ORDER BY bestellgruppen.aktiv, bestellgruppen.id
  " );
}

function sql_lieferantenpfand( $lieferanten_id, $bestell_id = 0, $group_by = 'pfandverpackungen.id' ) {
  $more_on = '';
  if( $bestell_id ) {
    $more_on = "AND gesamtbestellungen.id = $bestell_id";
  }
  return doSql( "
    SELECT
      pfandverpackungen.id as verpackung_id
    , pfandverpackungen.name as name
    , pfandverpackungen.wert as wert
    , pfandverpackungen.mwst as mwst
    , pfandverpackungen.sort_id as sort_id
    , lieferantenpfand.id as zuordnung_id
    , sum( (".select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_ANZAHL, array( 'gesamtbestellungen', 'pfandverpackungen', 'lieferanten' ) )." ) ) as pfand_leer_anzahl
    , sum( (".select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_NETTO_SOLL, array( 'gesamtbestellungen', 'pfandverpackungen', 'lieferanten' ) )." ) ) as pfand_leer_netto_soll
    , sum( (".select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_BRUTTO_SOLL, array( 'gesamtbestellungen', 'pfandverpackungen', 'lieferanten' ) )." ) ) as pfand_leer_brutto_soll
    , sum( (".select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_ANZAHL, array( 'gesamtbestellungen', 'pfandverpackungen', 'lieferanten' ) )." ) ) as pfand_voll_anzahl
    , sum( (".select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_NETTO_SOLL, array( 'gesamtbestellungen', 'pfandverpackungen', 'lieferanten' ) )." ) ) as pfand_voll_netto_soll
    , sum( (".select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_BRUTTO_SOLL, array( 'gesamtbestellungen', 'pfandverpackungen', 'lieferanten' ) )." ) ) as pfand_voll_brutto_soll
    FROM pfandverpackungen
    JOIN lieferanten
      ON lieferanten.id = pfandverpackungen.lieferanten_id
         AND lieferanten.id = $lieferanten_id
    JOIN gesamtbestellungen
      ON gesamtbestellungen.lieferanten_id = pfandverpackungen.lieferanten_id
         $more_on
    LEFT JOIN lieferantenpfand
      ON lieferantenpfand.verpackung_id = pfandverpackungen.id
      AND lieferantenpfand.bestell_id = gesamtbestellungen.id
    GROUP BY $group_by
    ORDER BY sort_id
  " );
}

function sql_verbindlichkeiten_lieferanten() {
  return doSql( "
    SELECT lieferanten.id as lieferanten_id
         , lieferanten.name as name
         , ( ".select_soll_lieferanten('lieferanten')." ) as soll
    FROM lieferanten
    HAVING (soll <> 0)
  " );
}

function forderungen_gruppen_summe() {
  return sql_select_single_field( "
    SELECT ifnull( -sum( table_soll.soll ), 0.0 ) as forderungen
    FROM (
      SELECT (" .select_soll_gruppen('bestellgruppen'). ") AS soll
      FROM (" .select_aktive_bestellgruppen(). ") AS bestellgruppen
      HAVING ( soll < 0 )
    ) AS table_soll
  ", 'forderungen' );
}

function verbindlichkeiten_gruppen_summe() {
  return sql_select_single_field( "
    SELECT ifnull( sum( table_soll.soll ), 0.0 ) as verbindlichkeiten
    FROM (
      SELECT (" .select_soll_gruppen('bestellgruppen'). ") AS soll
      FROM (" .select_aktive_bestellgruppen(). ") AS bestellgruppen
      HAVING ( soll > 0 )
    ) AS table_soll
  ", 'verbindlichkeiten' );
}

function sql_bestellungen_soll_gruppe( $gruppen_id, $bestell_id = 0 ) {
  $more_where = '';
  if( $bestell_id ) {
    need( getState( $bestell_id ) >= STATUS_LIEFERANT );
    $more_where = "AND ( gesamtbestellungen.id = $bestell_id )";
  }
  $query = "
    SELECT gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.name
         , DATE_FORMAT(gesamtbestellungen.lieferung,'%d.%m.%Y') as lieferdatum_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%d.%m.%Y') as valuta_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%Y%m%d') as valuta_kan
         , (" .select_bestellungen_soll_gruppen( OPTION_WAREN_NETTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as waren_netto_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_WAREN_BRUTTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as waren_brutto_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_PFAND_VOLL_BRUTTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as pfand_voll_brutto_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_BRUTTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as pfand_leer_brutto_soll
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") as gesamtbestellungen
    INNER JOIN gruppenbestellungen
      ON ( gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id )
    INNER JOIN bestellgruppen
      ON bestellgruppen.id = gruppenbestellungen.bestellguppen_id
    WHERE ( gruppenbestellungen.bestellguppen_id = $gruppen_id ) $more_where
    ORDER BY valuta_kan DESC;
  ";
  return doSql($query, LEVEL_ALL, "sql_bestellungen_soll_gruppe() fehlgeschlagen: ");
}


function sql_bestellungen_soll_lieferant( $lieferanten_id, $bestell_id = 0 ) {
  $where = '';
  $having = 'HAVING ( waren_netto_soll <> 0 ) or ( pfand_voll_brutto_soll <> 0 ) or ( pfand_leer_brutto_soll <> 0 )';
  if( $bestell_id ) {
    need( getState( $bestell_id ) >= STATUS_LIEFERANT );
    $where = "WHERE gesamtbestellungen.id = $bestell_id";
    $having = '';
  }
  $query = "
    SELECT gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.name
         , DATE_FORMAT(gesamtbestellungen.lieferung,'%d.%m.%Y') as lieferdatum_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%d.%m.%Y') as valuta_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%Y%m%d') as valuta_kan
         , (" .select_bestellungen_soll_lieferanten( OPTION_WAREN_NETTO_SOLL, array('lieferanten','gesamtbestellungen') ). ") as waren_netto_soll
         , (" .select_bestellungen_soll_lieferanten( OPTION_WAREN_BRUTTO_SOLL, array('lieferanten','gesamtbestellungen') ). ") as waren_brutto_soll
         , (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_NETTO_SOLL, array('lieferanten','gesamtbestellungen') ). ") as pfand_voll_netto_soll
         , (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_NETTO_SOLL, array('lieferanten','gesamtbestellungen') ). ") as pfand_leer_netto_soll
         , (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_BRUTTO_SOLL, array('lieferanten','gesamtbestellungen') ). ") as pfand_voll_brutto_soll
         , (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_BRUTTO_SOLL, array('lieferanten','gesamtbestellungen') ). ") as pfand_leer_brutto_soll
         , (" .select_bestellungen_soll_lieferanten( OPTION_EXTRA_BRUTTO_SOLL, array('lieferanten','gesamtbestellungen') ). ") as extra_brutto_soll
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") as gesamtbestellungen
    JOIN lieferanten
      ON lieferanten.id = $lieferanten_id
    $where
    $having
    ORDER BY valuta_kan DESC;
  ";
  return doSql( $query, LEVEL_ALL, "sql_bestellungen_soll_lieferant fehlgeschlagen: " );
}

function sql_bestellung_soll_lieferant( $bestell_id ) {
  $result = sql_bestellungen_soll_lieferant( getProduzentBestellID( $bestell_id ), $bestell_id );
  need( mysql_num_rows( $result ) == 1 );
  return mysql_fetch_array( $result );
}


function sql_bestellung_rechnungssumme( $bestell_id ) {
  return sql_select_single_field( "
    SELECT (
          (" .select_bestellungen_soll_lieferanten( OPTION_WAREN_BRUTTO_SOLL, 'gesamtbestellungen' ). ")
        + (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_BRUTTO_SOLL, 'gesamtbestellungen' ). ")
        + (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_BRUTTO_SOLL, 'gesamtbestellungen' ). ")
    )   + extra_soll
    AS summe
    FROM gesamtbestellungen
    WHERE gesamtbestellungen.id = $bestell_id
  ", 'summe'
  );
}

function sql_bestellung_pfandsumme( $bestell_id ) {
  return sql_select_single_field( "
    SELECT (
      (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_LEER_BRUTTO_SOLL, array('gesamtbestellungen') ). ")
    + (" .select_bestellungen_soll_lieferanten( OPTION_PFAND_VOLL_BRUTTO_SOLL, array('gesamtbestellungen') ). ")
    ) AS pfand
    FROM gesamtbestellungen
    WHERE gesamtbestellungen.id = $bestell_id
  ", 'pfand'
  );
}


function kontostand( $gruppen_id ) {
	//FIXME: zu langsam auf Gruppenview wenn Dienst5
  if( $gruppen_id == sql_basar_id() )
    return 100.0;
  $row = sql_select_single_row( "
    SELECT (".select_soll_gruppen('bestellgruppen').") as soll
    FROM bestellgruppen
    WHERE bestellgruppen.id = $gruppen_id
  " );
  return $row['soll'];
}

function pfandkontostand( $gruppen_id = 0 ) {
  $where = '';
  if( $gruppen_id )
    $where = "WHERE bestellgruppen.id = $gruppen_id";
  return sql_select_single_field( "
    SELECT IFNULL( sum((".select_pfand_soll_gruppen('bestellgruppen').")), 0.0 ) as pfand_soll
    FROM bestellgruppen
    $where
  ", 'pfand_soll'
  );
}

function sockel_gruppen_summe() {
  global $sockelbetrag;
  $row = sql_select_single_row( "
    SELECT sum( $sockelbetrag * bestellgruppen.mitgliederzahl ) as soll
    FROM (".select_aktive_bestellgruppen().") AS bestellgruppen 
  " );
  return $row['soll'];
}

function lieferantenkontostand( $lieferanten_id ) {
  $row = sql_select_single_row( "
    SELECT (".select_soll_lieferanten('lieferanten').") as soll
    FROM lieferanten
    WHERE lieferanten.id = $lieferanten_id
  " );
  return $row['soll'];
}

function lieferantenpfandkontostand( $lieferanten_id = 0 ) {
  $where = '';
  if( $lieferanten_id )
    $where = "WHERE lieferanten.id = $lieferanten_id";
  return sql_select_single_field( "
    SELECT IFNULL( sum((" .select_pfand_soll_lieferanten('lieferanten')." )), 0.0 ) as pfand_soll
    FROM lieferanten
    $where
  ", 'pfand_soll'
  );
}

function select_ungebuchte_einzahlungen( $gruppen_id = 0 ) {
  return "
    SELECT *
      , DATE_FORMAT(gruppen_transaktion.kontobewegungs_datum,'%d.%m.%Y') AS valuta_trad
      , DATE_FORMAT(gruppen_transaktion.eingabe_zeit,'%d.%m.%Y') AS eingabedatum_trad
    FROM gruppen_transaktion
    WHERE (konterbuchung_id = 0)
      and ( gruppen_id " . ( $gruppen_id ? "=$gruppen_id" : ">0" ) . ")
  ";
}

function sql_ungebuchte_einzahlungen( $gruppen_id = 0 ) {
  return doSql( select_ungebuchte_einzahlungen( $gruppen_id ) );
}


//
// verluste und spenden
//

function select_verluste( $type, $not = false ) {
  $muell_id = sql_muell_id();
  if( is_array( $type ) ) {
    $filter = ' type in (';
    $komma = '';
    foreach( $type as $v ) {
      $filter .= "$komma $v";
      $komma = ',';
    }
    $filter .= " )";
  } else {
    $filter = " type = $type ";
  }
  if( $not )
    $filter = " not ( $filter ) ";
  return "
    SELECT id
         , summe as soll
         , kontobewegungs_datum as valuta
         , notiz
         , konterbuchung_id
    FROM gruppen_transaktion
    WHERE gruppen_transaktion.gruppen_id = $muell_id AND $filter
    ORDER BY type, kontobewegungs_datum
  ";
}

function sql_verluste( $type ) {
  return doSql( select_verluste( $type ) );
}

function sql_verluste_summe( $type ) {
  return sql_select_single_field( "
    RETURN sum( summe ) as soll
    FROM ( " .select_verluste( $type ). " ) as verluste
  ", 'soll'
  );
}

function sql_spenden( $orderby = 'valuta' ) {
  return doSql( "
    SELECT spenden.id as id
         , spenden.summe as haben
         , spenden.kontobewegungs_datum as valuta
         , spenden.notiz as notiz
         , bestellgruppen.name as name
    FROM ( " .select_verluste( TRANSAKTION_TYP_SPENDE ). " ) as spenden
    JOIN gruppen_transaktion
      ON  gruppen_transaktion.id = -spenden.konterbuchung_id
    JOIN bestellgruppen
      ON bestellgruppen.id = gruppen_transaktion.gruppen_id
    ORDER BY $orderby
  " );
}


/////////////////////////////////////////////
//
// produkte und produktpreise
//
/////////////////////////////////////////////

function references_produktpreise( $preis_id ) {
  return sql_select_single_field(
    "SELECT count(*) as count FROM bestellvorschlaege WHERE produktpreise_id=$preis_id"
  , "count"
  );
}

/**
 *
 */
function sql_produktpreise2( $produkt_id, $zeitpunkt = false ){
  if( $zeitpunkt ) {
    $zeitfilter = " AND (zeitende >= '$zeitpunkt' OR ISNULL(zeitende))
                    AND (zeitstart <= '$zeitpunkt' OR ISNULL(zeitstart))";
  } else {
    $zeitfilter = "";
  }
  $query = "
    SELECT produktpreise.*
         , date(produktpreise.zeitstart) as datum_start
         , day(produktpreise.zeitstart) as tag_start
         , month(produktpreise.zeitstart) as monat_start
         , year(produktpreise.zeitstart) as jahr_start
         , date(produktpreise.zeitende) as datum_ende
         , produkte.notiz
    FROM produktpreise 
    JOIN produkte ON produkte.id = produktpreise.produkt_id
    WHERE produkt_id= $produkt_id $zeitfilter
    ORDER BY zeitstart, IFNULL(zeitende,'9999-12-31'), id";
  //  ORDER BY IFNULL(zeitende,'9999-12-31'), id";
  return doSql($query, LEVEL_ALL, "Konnte Produktpreise nich aus DB laden..");
}

/* sql_aktueller_produktpreis:
 *  liefert aktuellsten preis zu $produkt_id,
 *  oder false falls es keinen gueltigen preis gibt:
 */
function sql_aktueller_produktpreis( $produkt_id, $zeitpunkt = "NOW()" ) {
  $result = sql_produktpreise2( $produkt_id, $zeitpunkt );
  $rows = mysql_num_rows( $result );
  if( $rows < 1 )
    return false;
  mysql_data_seek( $result, $rows - 1 );
  return mysql_fetch_array( $result );
}

/* sql_aktueller_produktpreis_id:
 *  liefert id des aktuellsten preises zu $produkt_id,
 *  oder 0 falls es NOW() keinen gueltigen preis gibt:
 */
function sql_aktueller_produktpreis_id( $produkt_id, $zeitpunkt = "NOW()" ) {
  $row = sql_aktueller_produktpreis( $produkt_id, $zeitpunkt );
  return $row ? $row['id'] : 0;
}

function sql_expire_produktpreise($produkt_id, $zeitende = false ) {
  global $mysqljetzt;
  if( $zeitende )
    $zeitende = mysql_real_escape_string( $zeitende );
  else
    $zeitende = "$mysqljetzt";
  $query = "
    UPDATE produktpreise
    SET zeitende='$zeitende'
    WHERE ( produkt_id = '$produkt_id' )
          AND ( ISNULL(zeitende) OR ( zeitende > '$zeitende' ) )
  ";
  return doSql( $query, LEVEL_IMPORTANT, "sql_expire_produktpreise() fehlgeschlagen: " );
}

// produktpreise_konsistenztest:
//  - alle zeitintervalle bis auf das letzte muessen abgeschlossen sein
//  - intervalle duerfen nicht ueberlappen
//  - warnen, wenn kein aktuell gueltiger preis vorhanden
// rueckgabe: true, falls keine probleme, sonst false
//
function produktpreise_konsistenztest( $produkt_id, $editable = false, $mod_id = false ) {
  global $mysqljetzt;
  need( $produkt_id );
  $rv = true;
  $produktpreise = sql_produktpreise2( $produkt_id );
  $pr0 = FALSE;
  while( $pr1 = mysql_fetch_array($produktpreise) ) {
    if( $pr0 ) {
      $monat = $pr1['monat_start'];
      $jahr = $pr1['jahr_start'];
      $tag = $pr1['tag_start'];
      $show_button = false;
      if( $pr0['zeitende'] == '' ) {
        echo "<div class='warn'>FEHLER: Preisintervall {$pr0['id']} nicht aktuell aber nicht abgeschlossen.</div>";
        $show_button = true;
        $rv = false;
      } else if( $pr0['zeitende'] > $pr1['zeitstart'] ) {
        echo "<div class='warn'>FEHLER: Ueberlapp in Preishistorie: {$pr0['id']} und {$pr1['id']}.</div>";
        $show_button = true;
        $rv = false;
      }
      if( $editable && $show_button )
        echo action_button( "Eintrag {$pr0['id']} zum $jahr-$monat-$tag enden lassen"
          , "Eintrag {$pr0['id']} zum $jahr-$monat-$tag enden lassen"
          , array(
              'action' => 'zeitende_setzen'
            , 'day' => "$tag", 'month' => "$monat", 'year' => "$jahr"
            , 'vortag' => '1'
            , 'preis_id' => $pr0['id']
            )
        , $mod_id, 'warn'
        );
    }
    $pr0 = $pr1;
  }
  if( ! $pr0 ) {
    ?> <div class='alert'>HINWEIS: kein Preiseintrag fuer diesen Artikel vorhanden!</div> <?
  } else if ( $pr0['zeitende'] != '' ) {
    if ( $pr0['zeitende'] < $mysqljetzt ) {
      ?> <div class='alert'>HINWEIS: kein aktuell g&uuml;ltiger Preiseintrag fuer diesen Artikel vorhanden!</div> <?
    } else {
      ?> <div class='alert'>HINWEIS: aktueller Preis l&auml;uft aus!</div> <?
    }
  }
  return $rv;
}


/**
 *  Erzeugt einen Produktpreiseintrag
 */
function sql_insert_produktpreis (
  $produkt_id, $preis, $start, $bestellnummer, $gebindegroesse
, $mwst, $pfand, $liefereinheit, $verteileinheit
) {
  $aktueller_preis = sql_aktueller_produktpreis( $produkt_id, $start );
  if( $aktueller_preis ) {
    sql_update( 'produktpreise'
    , $aktueller_preis['id']
    , array( 'zeitende' => "date_add( date('$start'), interval -1 second )" )
    , false
    );
  }
  // sql_expire_produktpreise( $produkt_id, $start );

  return sql_insert( 'produktpreise', array(
    'produkt_id' => $produkt_id
  , 'preis' => $preis
  , 'zeitstart' => $start
  , 'bestellnummer' => $bestellnummer
  , 'gebindegroesse' => $gebindegroesse
  , 'mwst' => $mwst
  , 'pfand' => $pfand
  , 'liefereinheit' => $liefereinheit
  , 'verteileinheit' => $verteileinheit
  ) );
}


function action_form_produktpreis() {
  global $name, $verteilmult, $verteileinheit, $liefermult, $liefereinheit
       , $gebindegroesse, $mwst, $pfand, $preis, $bestellnummer
       , $day, $month, $year, $notiz, $produkt_id;

  need_http_var('produkt_id','u');

  get_http_var('name','H','');  // notwendig, sollte aber moeglichst nicht geaendert werden!
  need_http_var('verteilmult','f');
  need_http_var('verteileinheit','w');
  need_http_var('liefermult','u');
  need_http_var('liefereinheit','w');
  need_http_var('gebindegroesse','u');
  need_http_var('mwst','f');
  need_http_var('pfand','f');
  need_http_var('preis','f');
  get_http_var('bestellnummer','H','');
  need_http_var('day','u');
  need_http_var('month','u');
  need_http_var('year','u');
  get_http_var('notiz','H','');

  $produkt = sql_produkt_details( $produkt_id );

  if( "$name" and ( "$name" != $produkt['name'] ) ) {
    sql_update( 'produkte', $produkt_id, array( 'name' => $name ) );
  }
  if( "$notiz" != $produkt['notiz'] ) {
    sql_update( 'produkte', $produkt_id, array( 'notiz' => $notiz ) );
  }

  sql_insert_produktpreis(
    $produkt_id, $preis, "$year-$month-$day", $bestellnummer, $gebindegroesse, $mwst, $pfand
  , "$liefermult $liefereinheit", "$verteilmult $verteileinheit"
  );
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
$masseinheiten = array( 'g', 'ml', 'ST', 'KI', 'PA', 'GL', 'BE', 'DO', 'BD', 'BT', 'KT', 'FL', 'EI', 'KA', 'SC' );

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

function references_produktgruppe( $produktgruppen_id ) {
  return sql_select_single_field(
    "SELECT count(*) as count FROM produkte WHERE produktgruppen_id = $produktgruppen_id"
  , 'count'
  );
}


function optionen_produktgruppen( $selected = 0 ) {
  $produktgruppen = sql_produktgruppen();
  $output = "";
  while( $pg = mysql_fetch_array($produktgruppen) ) {
    echo "pg name: {$pg['name']}<br>";
    $id = $pg['id'];
    $output .= "<option value='$id'";
    if( $selected == $id ) {
      $output .= " selected";
      $selected = -1;
    }
    $output .= ">{$pg['name']}</option>";
  }
  if( $selected >=0 ) {
    $output = "<option value='0' selected>(bitte Produktgruppe wählen)</option>" . $output;
  }
  return $output;
}



/**
 *  Produktinformationen abfragen
 */
function getProdukt($produkt_id){
   $sql = "SELECT * FROM produkte WHERE id = ".$produkt_id;
    $result = doSql($sql, LEVEL_ALL, "Konnte Produkte nich aus DB laden..");
    return mysql_fetch_array($result);
}

function references_produkt( $produkt_id ) {
  return sql_select_single_field( " SELECT (
     ( SELECT count(*) FROM bestellvorschlaege WHERE produkt_id=$produkt_id )
   + ( SELECT count(*) FROM bestellzuordnung WHERE produkt_id=$produkt_id )
  ) as count
  ", 'count'
  );
}

function sql_produkt_details( $produkt_id, $preis_id = 0, $zeitpunkt = false ) {
  $produkt_row = sql_select_single_row( "
    SELECT produkte.id
         , produkte.artikelnummer
         , produkte.name
         , produkte.lieferanten_id
         , produkte.notiz
         , produktgruppen.id as produktgruppen_id
         , produktgruppen.name as produktgruppen_name
    FROM produkte
    LEFT JOIN produktgruppen ON produktgruppen.id = produkte.produktgruppen_id
    WHERE produkte.id=$produkt_id
  " );
  $produkt_row['lieferanten_name'] = lieferant_name( $produkt_row['lieferanten_id'] );
  if( $preis_id ) {
    $preis_row = sql_select_single_row( "SELECT * FROM produktpreise WHERE id=$preis_id" );
  } else {
    $preis_row = sql_aktueller_produktpreis( $produkt_id, $zeitpunkt );
  }
  if( $preis_row ) {
    preisdatenSetzen( & $preis_row );
    //
    // definierten Satz von Werten umkopieren:
    $produkt_row['preis_id'] = $preis_row['id'];
    //
    // V-Mult V-einheit: Vielfache davon bestellen die Gruppen:
    $produkt_row['kan_verteileinheit'] = $preis_row['kan_verteileinheit'];
    $produkt_row['kan_verteilmult'] = $preis_row['kan_verteilmult'];
    //
    // L-Mult L-einheit: Vielfache davon nennen wir in der Bestellung beim Lieferanten:
    $produkt_row['kan_liefereinheit'] = $preis_row['kan_liefereinheit'];
    $produkt_row['kan_liefermult'] = $preis_row['kan_liefermult'];
    //
    // Gebindegroesse: wieviele V-Einheiten muessen jeweils bestellt werden:
    $produkt_row['gebindegroesse'] = $preis_row['gebindegroesse'];
    //
    // Preise pro V-Mult * V-Einheit:
    $produkt_row['nettopreis'] = $preis_row['nettopreis'];
    $produkt_row['bruttopreis'] = $preis_row['bruttopreis'];  // mit MWSt.
    $produkt_row['endpreis'] = $preis_row['endpreis'];        // mit MWSt. und Pfand
    //
    // Preiseinheit: Menge fuer die der Katalogpreis des Lieferanten angegeben ist:
    //               (enthaelt masszahl und einheit, nur zur Ausgabe gedacht!)
    // mengenfaktor: faktor zwischen V-Mult V-Einheit und Preis-Einheit
    //               (zur Umrechnung (Konsumenten-)Nettopreis -> (Lieferanten-)Katalogpreis)
    $produkt_row['preiseinheit'] = $preis_row['preiseinheit'];
    $produkt_row['mengenfaktor'] = $preis_row['mengenfaktor'];
    $produkt_row['nettolieferpreis'] = $preis_row['nettopreis'] * $preis_row['mengenfaktor'];
    //
    $produkt_row['pfand'] = $preis_row['pfand'];  // in Euro
    $produkt_row['mwst'] = $preis_row['mwst'];    // in Prozent
    //
    $produkt_row['bestellnummer'] = $preis_row['bestellnummer'];
    //
    $produkt_row['zeitstart'] = $preis_row['zeitstart'];
    $produkt_row['zeitende'] = $preis_row['zeitende'];
  } else {
    // flag: kein gueltiger preis:
    $produkt_row['zeitstart'] = false;
  }
  return $produkt_row;
}

function sql_delete_produkt( $produkt_id ) {
  $count = references_produkt( $produkt_id );
  need( $count == 0, 'Produkteintrag nicht löschbar, da in Bestellungen oder -vorlagen benutzt!' );
  doSql( "DELETE FROM produktpreise WHERE produkt_id=$produkt_id" );
  doSql( "DELETE FROM produkte WHERE id=$produkt_id" );
}

function sql_delete_produktpreis( $preis_id ) {
  need( references_produktpreise( $preis_id ) == 0 , 'Preiseintrag nicht löschbar, da er benutzt wird!' );
  doSql( "DELETE FROM produktpreise WHERE id=$preis_id" );
}

/**
 *  Produktinformationen updaten
 */
function sql_update_produkt ($id, $name, $produktgruppen_id, $einheit, $notiz){
  return sql_update( 'produkte', $id, array(
    'name' => "$name"
  , 'produktgruppen_id' => $produktgruppen_id
  , 'einheit' => "$einheit"
  , 'notiz' => "$notiz"
  ) );
}

/**
 * Alle Produkte von einem Lieferanten, auch mit ungültigem Preis
 */
function sql_produkte_von_lieferant_ids( $lieferanten_id ) {
  $sql = "
    SELECT produkte.id as id
    FROM produkte
    LEFT JOIN produktgruppen ON produktgruppen.id = produkte.produktgruppen_id
    WHERE lieferanten_id = '$lieferanten_id'
    ORDER BY produktgruppen.name, produkte.name
  ";
  return doSql($sql, LEVEL_ALL, "Konnte Produkte nicht aus DB laden..");
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
// Lieferantenkatalog
//
////////////////////////////////////


function sql_anzahl_katalogeintraege( $lieferanten_id ) {
  return sql_select_single_field( "SELECT count(*) as anzahl FROM lieferantenkatalog WHERE lieferanten_id = $lieferanten_id", 'anzahl' );
}


////////////////////////////////////
//
// HTML-funktionen:
//
////////////////////////////////////

function checkvalue( $val, $typ){
	  $pattern = '';
	  switch( substr( $typ, 0, 1 ) ) {
	    case 'H':
        // FIXME: 'H' zum default machen?
        if( get_magic_quotes_gpc() )
          $val = stripslashes( $val );
	      $val = htmlspecialchars( $val );
	      break;
	    case 'M':
	      $val = mysql_real_escape_string( $val );
	      break;
      case 'R':
        break;
	    case 'U':
	      $val = trim($val);
	      $pattern = '/^\d*[1-9]\d*$/';
	      break;
	    case 'u':
		    //FIXME: zahl sollte als zahl zurückgegeben 
		    //werden, zur Zeit String
	      $val = trim($val);
        // eventuellen nachkommateil (und sonstigen Muell) abschneiden:
        $val = preg_replace( '/[^\d].*$/', '', $val );
	      $pattern = '/^\d+$/';
	      break;
	    case 'd':
	      $val = trim($val);
        // eventuellen nachkommateil abschneiden:
        $val = preg_replace( '/[.].*$/', '', $val );
	      $pattern = '/^-{0,1}\d+$/';
	      break;
	    case 'f':
	      $val = str_replace( ',', '.' , trim($val) );
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
        return FALSE;
	  }
	  if( $pattern ) {
	    if( ! preg_match( $pattern, $val ) ) {
	      return FALSE;
	    }
	  }
      return $val;

}

// get_http_var:
// - name: wenn name auf [] endet, wird ein array erwartet (aus <input name='bla[]'>)
// - typ: definierte $typ argumente:
//   d : ganze Zahl
//   u (default wenn name auf _id endet): nicht-negative ganze Zahl
//   U positive ganze Zahl (also echt groesser als NULL)
//   M (sonst default): Wert beliebig, wird aber durch mysql_real_escape_string fuer MySQL verdaulich gemacht
//   H : wendet htmlspecialchars an (erlaubt sichere und korrekte ausgabe in HTML)
//   R : raw: keine Einschraenkung, keine Umwandlung
//   A : automatisch (default; momentan: trick um ..._id-Variablen zu testen)
//   f : Festkommazahl
//   w : bezeichner: alphanumerisch und _
//   /.../: regex pattern. Wert wird ausserdem ge-trim()-t
// - default:
//   - wenn array erwartet wird, kann der default ein array sein.
//   - wird kein array erwartet, aber default is ein array, so wird $default[$name] versucht
//
// per POST uebergebene variable werden nur beruecksichtigt, wenn zugleich eine
// unverbrauchte transaktionsnummer 'postform_id' uebergeben wird (als Sicherung
// gegen mehrfache Absendung desselben Formulars per "Reload" Knopfs des Browsers)
/**
 *
 */
function get_http_var( $name, $typ = 'A', $default = NULL, $is_self_field = false ) {
  global $HTTP_GET_VARS, $HTTP_POST_VARS, $self_fields;
  global $postform_id;

  if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    if( ! isset( $postform_id ) ) {
      if( isset( $HTTP_POST_VARS['postform_id'] ) ) {
        $postform_id = $HTTP_POST_VARS['postform_id'];
        $used = sql_select_single_field( "SELECT used FROM transactions WHERE id=$postform_id", 'used', true );
        if( $used ) {
          // formular wurde mehr als einmal abgeschickt: POST-daten verwerfen:
          $HTTP_POST_VARS = array();
          echo "<div class='warn'>Warnung: mehrfach abgeschicktes Formular detektiert! (wurde nicht ausgewertet)</div>";
        } else {
          // id ist noch unverbraucht: jetzt entwerten:
          sql_update( 'transactions', $postform_id, array( 'used' => 1 ) );
          // echo "<div class='ok'>postform_id entwertet: $postform_id</div>";
        }
      } else {
        // TODO: warnung ausgeben: formular hatte keine Transaktionsnummer!
      }
    }
  } else {
    $HTTP_POST_VARS = array();
  }

  if( substr( $name, -2 ) == '[]' ) {
    $want_array = true;
    $name = substr( $name, 0, strlen($name)-2 );
  } else {
    $want_array = false;
  }
  if( isset( $HTTP_GET_VARS[$name] ) ) {
    $arry = $HTTP_GET_VARS[$name];
  } elseif( isset( $HTTP_POST_VARS[$name] ) ) {
    $arry = $HTTP_POST_VARS[$name];
  } else {
    if( isset( $default ) ) {
      if( is_array( $default ) ) {
        if( $want_array ) {
          $GLOBALS[$name] = $default;
          //FIXME self_fields for arrays?
        } else if( isset( $default[$name] ) ) {
          // erlaube initialisierung z.B. aus MySQL-'$row':
          $GLOBALS[$name] = $default[$name];
          if( $is_self_field ) {
            $self_fields[$name] = $default[$name];
          }
        } else {
          unset( $GLOBALS[$name] );
          return FALSE;
        }
      } else {
        $GLOBALS[$name] = $default;
        if( $is_self_field ) {
          $self_fields[$name] = $default;
        }
      }
      return TRUE;
    } else {
      unset( $GLOBALS[$name] );
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

  if(is_array($arry)){
    if( ! $want_array ) {
      unset( $GLOBALS[$name] );
      return FALSE;
    }
    foreach($arry as $key => $val){
      $new = checkvalue($val, $typ);
      if($new===FALSE){
        unset( $GLOBALS[$name] );
	      return FALSE;
      } else {
	      $arry[$key]=$new;
      }
    }
	  //FIXME self_fields for arrays?
	  $GLOBALS[$name] = $arry;
  } else {
      $new = checkvalue($arry, $typ);
      if($new===FALSE){
        unset( $GLOBALS[$name] );
        return FALSE;
      } else {
        $GLOBALS[$name] = $new;
        if( $is_self_field ) {
          $self_fields[$name] = $new;
        }
      }
  }
  return TRUE;
}

/**
 *
 */
function need_http_var( $name, $typ = 'A', $is_self_field = false ) {
  need( get_http_var( $name, $typ, NULL, $is_self_field ), "variable $name nicht uebergeben" );
  return TRUE;
}
/**
 *
 */
function reload_immediately( $url ) {
  global $print_on_exit;
  echo "
    <form action='$url' name='reload_now_form' method='post'></form>
    <script type='text/javascript'>document.forms['reload_now_form'].submit();</script>
    $print_on_exit;
  ";
  exit();
}
/**
 *
 */
function update_database($version){
	switch($version){
	case 0:

		$sql="INSERT INTO `nahrungskette`.`leitvariable` (
			`name` ,
			`value` ,
			`local` ,
			`comment`
			)
			VALUES (
				'database_version',
				'1', '0',
			       	'Versionskontrolle für Datenbank. Erlaubt automatisches Anpassen der Datenbank beim start.'
			);
               ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Leitvariable database_version nicht einfügen");
	case 1:
		$sql = "
INSERT INTO `nahrungskette`.`leitvariable` (
`name` , `value` , `local` , `comment`
) VALUES (
'basar_id', '99', '0', 'Gruppen-ID der besonderen Basar-Gruppe'
) ON DUPLICATE KEY UPDATE value = '99';

			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql = "
INSERT INTO `nahrungskette`.`leitvariable` (
`name` , `value` , `local` , `comment`
) VALUES (
'muell_id', '13', '0', 'Gruppen-ID der besonderen Muell-Gruppe'
)ON DUPLICATE KEY UPDATE value = '13';

			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql = "

CREATE TABLE `bankkonto` (
 `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
 `kontoauszug_jahr` SMALLINT NOT NULL, 
 `kontoauszug_nr` SMALLINT NOT NULL, 
 `eingabedatum` DATE NOT NULL, 
 `gruppen_id` INT NOT NULL,
 `lieferanten_id` INT NOT NULL,
 `dienstkontrollblatt_id` INT NOT NULL,
 `betrag` DECIMAL(10,2) NOT NULL,
 `konto_id` smallint(4) NOT NULL,
 `kommentar` TEXT NOT NULL,
 `konterbuchung_id` INT NOT NULL,
  KEY `secondary` (`konto_id`, `kontoauszug_jahr`,`kontoauszug_nr`)
 )
 ENGINE = myisam DEFAULT CHARACTER SET utf8 COMMENT = 'Bankkontotransaktionen';

			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql = "
ALTER TABLE `gruppen_transaktion` ADD `konterbuchung_id` INT NOT NULL DEFAULT '0';
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql = "
ALTER TABLE `gruppen_transaktion` ADD `lieferanten_id` INT NOT NULL DEFAULT '0';
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql = "

CREATE TABLE `bankkonten` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`name` TEXT NOT NULL ,
`kontonr` TEXT NOT NULL ,
`blz` TEXT NOT NULL
) ENGINE = MYISAM ;

			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql="UPDATE leitvariable
			set value =  2
			WHERE name = 'database_version' ;
               ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Datenbank-Version nicht hochsetzen");
	case 2:
		$sql = "
			DROP TABLE IF EXISTS gruppenmitglieder;
                       ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht löschen");
		//Zusätzliche Statusspalte in gruppenmitgliedern
		//
		$sql = "
			CREATE TABLE `gruppenmitglieder` (
			 `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
			 `gruppen_id` INT NOT NULL,
			 `name` TEXT NOT NULL, 
			 `vorname` TEXT NOT NULL, 
			 `telefon` TEXT NOT NULL, 
			 `email` TEXT NOT NULL, 
			 `diensteinteilung` ENUM( '1/2', '3', '4', '5', 'freigestellt' ) NOT NULL DEFAULT 'freigestellt',
			 `rotationsplanposition` INT NOT NULL
			 )
			 ENGINE = myisam DEFAULT CHARACTER SET utf8 COMMENT = 'Mitglieder einer Foodcoopgruppe';
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		//Zusätzliche Statusspalte in gruppenmitgliedern
		//
		$sql = " ALTER TABLE `gruppenmitglieder` ADD `status` ENUM( 'aktiv', 'geloescht' ) NOT NULL DEFAULT 'aktiv'  ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Status-Feld in Tabelle gruppenmitglieder nicht anlegen");
		$sql = " ALTER TABLE `Dienste` ADD `gruppenmitglieder_id` INT(11) NOT NULL DEFAULT 0  ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Mitglieder_id-Feld in Tabelle dienste nicht anlegen");

		$sql = " INSERT INTO gruppenmitglieder 
			(gruppen_id, name, telefon, email, diensteinteilung, rotationsplanposition)
			SELECT id, ansprechpartner, telefon, email, diensteinteilung, rotationsplanposition 
			FROM bestellgruppen;
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht mit Werten fuellen");

		$sql = " UPDATE Dienste inner join gruppenmitglieder on (GruppenID = gruppen_id) SET gruppenmitglieder_id = gruppenmitglieder.id  ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder_id in Tabelle Dienste nicht anpassen");

		
		
	        $sql = " INSERT INTO gruppenmitglieder( gruppen_id, diensteinteilung )
			SELECT gruppen_id, diensteinteilung
			FROM (

			SELECT gruppen_id, mitgliederzahl, bestellgruppen.diensteinteilung
			FROM `gruppenmitglieder`
			INNER JOIN bestellgruppen ON ( gruppen_id = bestellgruppen.id )
			GROUP BY gruppen_id, bestellgruppen.diensteinteilung
			HAVING count( gruppenmitglieder.telefon ) < mitgliederzahl
			) AS bla
			";
	        while(mysql_affected_rows() > 0){
		    doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht mit leeren Werten fuellen");
		}
		$sql = " ALTER TABLE `bestellgruppen`
			  DROP `ansprechpartner`,
			  DROP `telefon`,
			  DROP `email`,
			  DROP `diensteinteilung`,
			  DROP `mitgliederzahl`,
			  DROP `rotationsplanposition`;
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql=" ALTER TABLE `Dienste` DROP `GruppenID`  ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Spalte GruppenID  nicht aus Tabelle Dienste löschen");
		$sql="UPDATE leitvariable
			set value = 3 
			WHERE name = 'database_version' ;
               ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Datenbank-Version nicht hochsetzen");
  case 3:
    $sql = " ALTER TABLE gruppen_transaktion
      DROP kontoauszugs_nr,
      DROP kontoauszugs_jahr,
      ADD KEY `tertiary` (`lieferanten_id`,`kontobewegungs_datum`)
    ";
    doSql($sql, LEVEL_IMPORTANT, "Update Tabelle gruppen_transaktion fehlgeschlagen");
    $sql = " ALTER TABLE bankkonto
      DROP gruppen_id,
      DROP lieferanten_id
    ";
    doSql($sql, LEVEL_IMPORTANT, "Update Tabelle bankkonto fehlgeschlagen");
    $sql="UPDATE leitvariable set value = 4 WHERE name = 'database_version'; ";
    doSql($sql, LEVEL_IMPORTANT, "Konnte Datenbank-Version nicht auf 4 hochsetzen");
  case 4:
    $sql = " ALTER TABLE gesamtbestellungen
      ADD `rechnungssumme` decimal(10,2) NOT NULL default '0.00' COMMENT 'wahre Rechnungssumme (kann wegen Pfand von berechneter abweichen!)',
      ADD `lieferanten_id` int(11) NOT NULL,
      ADD `rechnungsnummer` text NOT NULL COMMENT 'Rechnungsnummer des Lieferanten',
      ADD `abrechnung_dienstkontrollblatt_id` int(11) NOT NULL default 0
    ";
    doSql($sql, LEVEL_IMPORTANT, "Update Tabelle gesamtbestellungen fehlgeschlagen");
    $sql = " ALTER TABLE bankkonten
      ADD `letzter_auszug_jahr` SMALLINT NOT NULL default 0,
      ADD `letzter_auszug_nr` SMALLINT NOT NULL default 0
    ";
    doSql($sql, LEVEL_IMPORTANT, "Update Tabelle bankkonten fehlgeschlagen");
    $sql="UPDATE leitvariable set value = 5 WHERE name = 'database_version'; ";
    doSql($sql, LEVEL_IMPORTANT, "Konnte Datenbank-Version nicht auf 5 hochsetzen");
  case 5:
    $sql="
        DROP TABLE IF EXISTS `transactions`;
    ";
    doSql($sql, LEVEL_IMPORTANT, "Loeschen Tabelle transactions fehlgeschlagen");
    $sql="
         CREATE TABLE `transactions` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `used` tinyint(1) NOT NULL default '0'
        ) ENGINE = MYISAM ;
    ";
    doSql($sql, LEVEL_IMPORTANT, "Anlegen Tabelle transactions fehlgeschlagen");
    $sql="UPDATE leitvariable set value = 6 WHERE name = 'database_version'; ";
    doSql($sql, LEVEL_IMPORTANT, "Konnte Datenbank-Version nicht auf 5 hochsetzen");
  
	case 6:
    $sql = "
       CREATE TABLE `lieferantenkatalog` (
         `id` int(11) NOT NULL auto_increment,
         `lieferanten_id` int(11) NOT NULL,
         `name` text NOT NULL,
         `artikelnummer` bigint(20) NOT NULL,
         `bestellnummer` text NOT NULL,
         `liefereinheit` text NOT NULL,
         `gebinde` text NOT NULL,
         `mwst` decimal(4,2) NOT NULL,
         `pfand` decimal(6,2) NOT NULL,
         `verband` text NOT NULL,
         `herkunft` text NOT NULL,
         `preis` decimal(8,2) NOT NULL,
         `katalogdatum` text NOT NULL,
         PRIMARY KEY  (`id`),
         UNIQUE KEY `secondary` (`lieferanten_id`,`artikelnummer`)
       ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle lieferantenkatalog nicht anlegen");
		
    $sql = "
       CREATE TABLE `pfandverpackungen` (
         `id` int(11) NOT NULL auto_increment,
         `lieferanten_id` int(11) NOT NULL,
         `name` text NOT NULL,
         `wert` decimal(8,2) NOT NULL,
         `mwst` decimal(6,2) NOT NULL,
         PRIMARY KEY  (`id`)
       ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle pfandverpackungen nicht anlegen");
		
    $sql = "
      CREATE TABLE `pfandzuordnung` (
        `id` int(11) NOT NULL auto_increment,
        `verpackung_id` int(11) NOT NULL,
        `bestell_id` int(11) NOT NULL,
        `anzahl_kauf` int(11) NOT NULL default '0',
        `anzahl_rueckgabe` int(11) NOT NULL default '0',
        PRIMARY KEY  (`id`),
        UNIQUE KEY `secondary` (`bestell_id`,`verpackung_id`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle pfandzuordnung nicht anlegen");
		
    $sql="UPDATE leitvariable
			set value =  7
			WHERE name = 'database_version' ;
    ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Datenbank-Version nicht hochsetzen");
	       
/*
	case n:
		$sql = "
			";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Tabelle gruppenmitglieder nicht anlegen");
		$sql="UPDATE leitvariable
			set value =  n+1
			WHERE name = 'database_version' ;
               ";
		doSql($sql, LEVEL_IMPORTANT, "Konnte Datenbank-Version nicht hochsetzen");
	       
 */
	}
}

function wikiLink( $topic, $text, $head = false ) {
  global $foodsoftdir;
  echo "
    <a class='wikilink' " . ( $head ? "id='wikilink_head' " : "" ) . "
      title='zur Wiki-Seite $topic'
      href=\"javascript:neuesfenster('$foodsoftdir/../wiki/doku.php?id=$topic','wiki');\"
    >$text</a>
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
  $exclude[] = 'postform_id';
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
// in jedem Formular wird automatisch eine Transaktionsnummer postform_id eingefuegt.
// 
function self_post( $exclude = array() ) {
  global $self_fields, $new_post_id;

  // bei bedarf neue nummer ziehen, aber nur einmal pro script:
  //
  if( ! isset( $self_fields['postform_id'] ) ) {
    $self_fields['postform_id'] = sql_insert( 'transactions', array( 'used' => 0 ) );
  }

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
// /**
//  *
//  */
// function from_basar(){
//    return "((`verteilmengen` join `bestellvorschlaege` on(((`verteilmengen`.`bestell_id` = `bestellvorschlaege`.`gesamtbestellung_id`) and (`bestellvorschlaege`.`produkt_id` = `verteilmengen`.`produkt_id`)))) join `produkte` on((`verteilmengen`.`produkt_id` = `produkte`.`id`)))";
// }
//
// function sql_liefermenge($bestell_id,$produkt_id){
//   $row = sql_select_single_row( "
//     SELECT liefermenge FROM bestellvorschlaege
//     WHERE (gesamtbestellung_id='$bestell_id') and (produkt_id='$produkt_id')
//   " );
//   return $row['liefermenge'];
// }
//
// function select_bestellungen_pfand( $using = array() ) {
//   return "
//     SELECT IFNULL( sum( bestellzuordnung.menge * produktpreise.pfand ), 0.0 )
//     FROM gruppenbestellungen
//   " . need_joins( $using, array(
//       'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
//                                ON gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id'
//     ) ) . "
//     JOIN bestellzuordnung
//       ON gruppenbestellungen.id = bestellzuordnung.gruppenbestellung_id
//     JOIN bestellvorschlaege
//       ON (bestellvorschlaege.produkt_id = bestellzuordnung.produkt_id)
//          AND ( bestellvorschlaege.gesamtbestellung_id = gruppenbestellungen.gesamtbestellung_id )
//     JOIN produktpreise
//       ON produktpreise.id = bestellvorschlaege.produktpreise_id
//     WHERE (bestellzuordnung.art=2) " . use_filters( $using, array(
//       'bestellgruppen' => 'gruppenbestellungen.bestellguppen_id = bestellgruppen.id'
//     , 'lieferanten' => 'gesamtbestellungen.lieferanten_id = lieferanten.id'
//     , 'gesamtbestellungen' => 'gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id'
//     ) );
// }
// function select_transaktionen_pfand( $using = array() ) {
//   return "
//     SELECT IFNULL( sum( summe * IF( type=1, 1, 0 ) ), 0.0 )
//       FROM gruppen_transaktion
//      WHERE ( gruppen_transaktion.gruppen_id > 0 ) " . use_filters( $using, array(
//         'bestellgruppen' => 'bestellgruppen.id = gruppen_transaktion.gruppen_id'
//       , 'lieferanten' => 'lieferanten.id = gruppen_transaktion.lieferanten_id'
//   ) );
// }
// function writeVerteilmengen_sql($gruppenMengeInGebinde, $gruppenbestellung_id, $produkt_id){
// 	if($gruppenMengeInGebinde > 0){
// 		$query = "INSERT INTO  bestellzuordnung (menge, produkt_id, gruppenbestellung_id, art) 
// 			  VALUES (".$gruppenMengeInGebinde.
// 			 ", ".$produkt_id.
// 			 ", ".$gruppenbestellung_id.", 2);";
//                 doSql($query, LEVEL_IMPORTANT, "Konnte Verteilmengen nicht in DB schreiben...");
// 	}
// }



?>
