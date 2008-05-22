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
  echo "<br>";
  $i = 1;
  foreach( $args as $a ) {
    echo "$tag: $i:";
    print_r( $a );
    echo "<br>";
    $i++;
  }
}


function doSql($sql, $debug_level = LEVEL_IMPORTANT, $error_text = "Datenbankfehler: " ){
	if($debug_level <= $_SESSION['LEVEL_CURRENT']) echo "<p>".$sql."</p>";
	$result = mysql_query($sql) or
	error(__LINE__,__FILE__,$error_text."(".$sql.")",mysql_error(), debug_backtrace());
	return $result;
}

function sql_select_single_row( $sql, $allownull = false ) {
  $result = doSql( $sql );
  $rows = mysql_num_rows($result);
  // echo "<br>$sql<br>rows: $rows<br>";
  if( $allownull and ( $rows == 0 ) )
    return NULL;
  need( $rows > 0, "Kein Treffer bei Datenbanksuche: $sql" );
  need( $rows == 1, "Ergebnis der Datenbanksuche $sql nicht eindeutig" );
  return mysql_fetch_array($result);
}

function sql_select_single_field( $sql, $field, $allownull = false ) {
  $row = sql_select_single_row( $sql, $allownull );
  if( $allownull and ! $row )
    return NULL;
  need( isset( $row[$field] ), "Feld $field nicht gesetzt" );
  return $row[$field];
}

function sql_update( $table, $where, $values, $escape_and_quote = true ) {
  $table == 'leitvariable' or $table == 'transactions' or fail_if_readonly();
  $sql = "UPDATE $table SET";
  $komma='';
  foreach( $values as $key => $val ) {
    $escape_and_quote and $val = "'" . mysql_real_escape_string($val) . "'";
    $sql .= "$komma $key=$val";
    $komma=',';
  }
  if( is_array( $where ) ) {
    $and = 'WHERE';
    foreach( $where as $field => $val ) {
      $sql .= " $and ($field='$val') ";
      $and = 'AND';
    }
  } else {
    $sql .= " WHERE id=$where";
  }
  // echo "<br>sql_update: $sql<br>";
  if( doSql( $sql, LEVEL_IMPORTANT, "Update von Tabelle $table fehlgeschlagen: " ) )
    return $where;
  else
    return FALSE;
}

function sql_insert( $table, $values, $update_cols = false ) {
  // debug_args( func_get_args(), 'sql_insert' );
  $table == 'leitvariable' or $table == 'transactions' or fail_if_readonly();
  $komma='';
  $update_komma='';
  $cols = '';
  $vals = '';
  $update = '';
  foreach( $values as $key => $val ) {
    $cols .= "$komma $key";
    $vals .= "$komma '" . mysql_real_escape_string($val) . "'";
    if( $update_cols or is_array($update_cols) ) {
      if( is_array( $update_cols ) ) {
        if( isset( $update_cols[$key] ) ) {
          $update .= "$update_komma $key='" . mysql_real_escape_string(
            $update_cols[$key] ? $update_cols[$key] : $val
          ) . "'";
          $update_komma=',';
        }
      } else {
        $update .= "$update_komma $key='" . mysql_real_escape_string($val) . "'";
        $update_komma=',';
      }
    }
    $komma=',';
  }
  $sql = "INSERT INTO $table ( $cols ) VALUES ( $vals )";
  if( $update_cols or is_array( $update_cols ) ) {
    $sql .= " ON DUPLICATE KEY UPDATE $update $update_komma id = LAST_INSERT_ID(id) ";
  }
  // echo "<br>sql_insert: $sql<br>";
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
        <? echo $comment; ?>
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


// define('STATUS_BESTELLEN', "bestellen");
// define('STATUS_LIEFERANT', "beimLieferanten");
// define('STATUS_VERTEILT', "Verteilt");
// define('STATUS_ARCHIVIERT', "archiviert");

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

// doSql( "UPDATE gesamtbestellungen set rechnungsstatus='10' where state='Bestellen'" );
// doSql( "UPDATE gesamtbestellungen set rechnungsstatus='20' where state='beimLieferanten'" );
// doSql( "UPDATE gesamtbestellungen set rechnungsstatus='30' where state='Verteilt'" );
// doSql( "UPDATE gesamtbestellungen set rechnungsstatus='40' where state='Abgerechnet'" );
// doSql( "UPDATE gesamtbestellungen set rechnungsstatus='50' where state='archiviert'" );


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
} else {
	$areas[] = array("area" => "index.php?area=produkte",
	"hint" => "Produktdatenbank und Kataloge einsehen","title" => "Produktdatenbank");	 
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
  need( $basar_id );
  return $basar_id;
}

function sql_muell_id(){
  global $muell_id;
  need( $muell_id );
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
    SELECT bestellgruppen.*
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
 *
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
  foreach( $additionalgroups as $id ) {
    $output = "$output
      <option value='$id'";
        if( $selected == $id ) {
          $output = $output . " selected";
          $selected = -1;
        }
        $output = $output . ">" . sql_gruppenname( $id ) . "</option>";
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
	global $problems, $msg, $sockelbetrag, $muell_id, $mysqlheute;
  need( isset( $sockelbetrag ) );  // sollte in leitvariablen definiert sein!
  need( $muell_id );
             $sql = "UPDATE gruppenmitglieder set status = 'geloescht', diensteinteilung = 'freigestellt', rotationsplanposition = 0  WHERE id=".mysql_escape_string($person_id);
   	     doSql($sql, LEVEL_IMPORTANT, "Konnte Person nicht l&ouml;schen");

          //Den Sockelbetrag ändern
  if( sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
  , array( 'konto_id' => -1, 'gruppen_id' => $muell_id )
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
  need( isset( $sockelbetrag ) );  // sollte in leitvariablen definiert sein!
  need( $muell_id );
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
    array( 'konto_id' => -1, 'gruppen_id' => $muell_id )
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

// optionen fuer kontoabfragen:
//
// betraege werden immer als 'soll' der fc, also schuld der fc
// (an gruppen, lieferanten oder bank) zurueckgegeben (ggf. also negativ)
//
define( 'OPTION_NETTO_SOLL', 1 );
define( 'OPTION_BRUTTO_SOLL', 2 );
define( 'OPTION_ENDPREIS_SOLL', 3 );
define( 'OPTION_PFAND_VOLL_SOLL', 4 );   /* schuld aus kauf voller pfandverpackungen */
define( 'OPTION_PFAND_LEER_SOLL', 5 );   /* schuld aus rueckgabe leerer pfandverpackungen */


////////////////////////////////////
//
// lieferanten-funktionen:
//
////////////////////////////////////

function sql_lieferanten( $id = false ) {
  $where = ( $id ? "WHERE id=$id" : "" );
  return doSql( "
    SELECT *
    , ( SELECT count(*) FROM produkte WHERE produkte.lieferanten_id = lieferanten.id ) as anzahl_produkte
    , ( SELECT count(*) FROM pfandverpackungen WHERE pfandverpackungen.lieferanten_id = lieferanten.id ) as anzahl_pfandverpackungen
    FROM lieferanten $where"
    , LEVEL_ALL, "Suche nach Lieferanten fehlgeschlagen: "
  );
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

function sql_bestellung( $bestell_id ) {
  return sql_select_single_row( "SELECT * FROM gesamtbestellungen WHERE id=$bestell_id" );
}

function getState($bestell_id){
  return sql_select_single_field( "SELECT status FROM gesamtbestellungen WHERE id=$bestell_id", 'status' );
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
  global $mysqljetzt;

  $bestellung = sql_bestellung( $bestell_id );

  $current = $bestellung['status'];
  if( $current == $state )
    return true;

  fail_if_readonly();
  nur_fuer_dienst(1,3,4);

  $do_verteilmengen_zuweisen = false;
  $changes = "state = '$state'";
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
  if($state!==FALSE){
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
  return sql_insert( 'gruppenbestellungen'
  , array( 'bestellguppen_id' => $gruppe , 'gesamtbestellung_id' => $bestell_id )
  , array(  /* falls schon existiert: -kein fehler -nix updaten -id zurueckgeben */  )
  );
}


////////////////////////////////////
//
//  Pfandbewegungen buchen
//
////////////////////////////////////

function sql_pfandverpackungen( $lieferanten_id, $bestell_id = 0, $group_by = "pfandverpackungen.id" ) {
  $more_on = '';
  if( $bestell_id )
    $more_on = " AND lieferantenpfand.bestell_id = $bestell_id ";
  return doSql( "
    SELECT *
      , pfandverpackungen.id as verpackung_id
      , lieferantenpfand.id as zuordnung_id
      , sum( lieferantenpfand.anzahl_kauf ) as anzahl_kauf
      , sum( lieferantenpfand.anzahl_rueckgabe ) as anzahl_rueckgabe
      , sum( lieferantenpfand.anzahl_kauf * pfandverpackungen.wert ) as pfand_soll_netto
      , sum( lieferantenpfand.anzahl_kauf * pfandverpackungen.wert * ( 1 + pfandverpackungen.mwst / 100.0 ) ) as pfand_soll_brutto
      , sum( lieferantenpfand.anzahl_rueckgabe * pfandverpackungen.wert ) as pfand_haben_netto
      , sum( lieferantenpfand.anzahl_rueckgabe * pfandverpackungen.wert * ( 1 + pfandverpackungen.mwst / 100.0 ) ) as pfand_haben_brutto
    FROM pfandverpackungen
    LEFT JOIN lieferantenpfand
      ON lieferantenpfand.verpackung_id = pfandverpackungen.id
      $more_on
    WHERE lieferanten_id=$lieferanten_id
    GROUP BY $group_by
    ORDER BY sort_id
  " );
}

// pfandzuordnung_{lieferant,gruppe}:
// schreibe _gesamtmenge_ fuer eine (bestellung,verpackung) oder (bestellung,gruppe),
// _ersetzt_ fruehere zuordnungen (nicht additiv!)
//
function sql_pfandzuordnung_lieferant( $bestell_id, $verpackung_id, $kauf, $rueckgabe ) {
  if( $kauf > 0 or $rueckgabe > 0 ) {
    sql_insert( 'lieferantenpfand' , array(
        'verpackung_id' => $verpackung_id
      , 'bestell_id' => $bestell_id
      , 'anzahl_kauf' => $kauf
      , 'anzahl_rueckgabe' => $rueckgabe
      )
    , true
    );
  } else {
    doSql( "DELETE FROM lieferantenpfand WHERE bestell_id=$bestell_id AND verpackung_id=$verpackung_id" ); 
  }
}

function sql_pfandzuordnung_gruppe( $bestell_id, $gruppen_id, $anzahl_rueckgabe ) {
  if( $anzahl_rueckgabe > 0 ) {
    return sql_insert( 'gruppenpfand', array(
        'gruppen_id' => $gruppen_id
      , 'bestell_id' => $bestell_id
      , 'anzahl_rueckgabe' => $anzahl_rueckgabe
      , 'pfand_wert' => 0.16
      )
    , true
    );
  } else {
    return doSql( "DELETE FROM gruppenpfand  WHERE bestell_id=$bestell_id AND gruppen_id=$gruppen_id" ); 
  }
}


////////////////////////////////////
//
// funktionen fuer bestellmengen und verteil/liefermengen
//
////////////////////////////////////


function sql_bestellmengen($bestell_id, $produkt_id, $art, $gruppen_id=false,$sortByDate=true){
	$query = "
    SELECT  *, bestellzuordnung.id as bestellzuordnung_id
    FROM gruppenbestellungen
    INNER JOIN bestellzuordnung
       ON (bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id)
    WHERE gruppenbestellungen.gesamtbestellung_id = $bestell_id 
      AND bestellzuordnung.produkt_id = $produkt_id
  ";
  if($gruppen_id!==false){
    $query = $query." AND gruppenbestellungen.bestellguppen_id = $gruppen_id";
  }
  if($art!==false){
    $query = $query." AND bestellzuordnung.art=".$art;
  }
  if($sortByDate){
    $query = $query." ORDER BY bestellzuordnung.zeitpunkt;";
  }else{
    $query = $query." ORDER BY gruppenbestellung_id, art;";
  }
  return doSql($query, LEVEL_ALL, "Konnte Bestellmengen nich aus DB laden..");
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
        AND bestellzuordnung.gruppenbestellung_id=gruppenbestellungen.id)
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

  $gruppen = sql_beteiligte_bestellgruppen($bestell_id);
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

function changeVerteilmengen_sql($menge, $gruppen_id, $produkt_id, $bestell_id){
  $gruppenbestellung_id = sql_create_gruppenbestellung( $gruppe, $bestell_id );
  doSql( "DELETE * FROM bestellzuordnung
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
 *  sql_basar:
 *  produkte im basar (differenz aus liefer- und verteilmengen) berechnen:
 */
function sql_basar($bestell_id=0,$order='produktname'){
  $where = ( $bestell_id ? "WHERE gesamtbestellungen.id = $bestell_id" : "" );
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
  $sql = select_basar() . "$where ORDER BY $order_by";
  return doSql($sql, LEVEL_ALL, "Konnte Basardaten nicht aus DB laden..");
}

/**
 *
 */
function select_basar() {
  $subselect_verteilmenge = "
    SELECT IFNULL(sum(menge),0.0) as verteilmenge
    FROM bestellzuordnung
    JOIN gruppenbestellungen
      ON bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id
    WHERE (art = 2)
      AND (produkte.id = bestellzuordnung.produkt_id)
      AND (gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id)
  ";
  return "
    SELECT produkte.name as produkt_name
         , gesamtbestellungen.name as bestellung_name
         , gesamtbestellungen.lieferung as lieferung
        , produktpreise.preis
         , produktpreise.verteileinheit
         , bestellvorschlaege.produkt_id
         , bestellvorschlaege.gesamtbestellung_id
         , bestellvorschlaege.produktpreise_id
         , bestellvorschlaege.liefermenge
         , bestellvorschlaege.bestellmenge
         , ( bestellvorschlaege.liefermenge - ( $subselect_verteilmenge ) ) as basar
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") as gesamtbestellungen
    JOIN bestellvorschlaege ON ( bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id )
    JOIN produkte ON produkte.id = bestellvorschlaege.produkt_id
    JOIN produktpreise ON ( bestellvorschlaege.produktpreise_id = produktpreise.id )
    HAVING (basar <> 0)
  " ;
}

function basar_wert_summe() {
  return sql_select_single_field(
    " SELECT IFNULL(sum( basar.basar * basar.preis ), 0.0 ) as wert
      FROM ( " .select_basar(). " ) as basar "
  , 'wert'
  );
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


/**
 * transaktionsart: 0 : normal
 *                  1 : pfand
 */
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
function sql_doppelte_transaktion( $soll, $haben, $betrag, $valuta, $notiz ) {
  global $dienstkontrollblatt_id;

  nur_fuer_dienst(4,5);
  need( $dienstkontrollblatt_id, 'Kein Dienstkontrollblatt Eintrag!' );
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
 * buchung_gruppe_bank: wertet formular zu einhabe eine einzahlung einer
 * gruppe auf ein bankkonto aus.
 */
function buchung_gruppe_bank() {
  global $betrag, $gruppen_id, $notiz, $day, $month, $year, $auszug_jahr, $auszug_nr, $konto_id;
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
  // debug_args( func_get_args(), 'sql_get_transaction' );
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
      FROM bankkonto
      JOIN bankkonten ON bankkonten.id = bankkonto.konto_id
      WHERE bankkonto.id = $id
    ";
  } else {
    $sql = "
      SELECT bankkonto.kontoauszug_jahr
           , bankkonto.kontoauszug_nr
           , (-summe) as haben
           , gruppen_transaktion.notiz as kommentar
           , gruppen_transaktion.kontobewegungs_datum as valuta
           , gruppen_transaktion.eingabe_zeit as buchungsdatum
           , gruppen_transaktion.konterbuchung_id as konterbuchung_id
           , bankkonten.name as kontoname
           , gruppen_transaktion.gruppen_id as gruppen_id
           , gruppen_transaktion.lieferanten_id as lieferanten_id
      FROM gruppen_transaktion
      LEFT JOIN bankkonto
             ON bankkonto.id = gruppen_transaktion.konterbuchung_id
      LEFT JOIN bankkonten
             ON bankkonten.id = bankkonto.konto_id
      WHERE gruppen_transaktion.id = ".(-$id)."
    ";
  }
  // echo "sql_get_transaction: $sql";
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
  // echo "sql_bankkonto_saldo: [$where]<br>";
  $row = sql_select_single_row( "
    SELECT IFNULL(sum( betrag ),0.0) as saldo
    FROM bankkonto
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


////////////////////////////////////////////
//
// funktionen fuer gruppen-, lieferantenkonto: abfrage kontostaende/kontobewegungen
//
////////////////////////////////////////////

/* select_bestellungen_soll_gruppen:
 *   liefert als skalarer subquery schuld der FC an gruppen aus bestellungen, und zugehoeriger
 *   pfandbewegungen (auch rueckgabe der betreffenden woche!)
 *   - $using ist array von tabellen, die aus dem uebergeordneten query benutzt werden sollen;
 *     erlaubte werte: 'gesamtbestellungen', 'bestellgruppen'
 *   - $art ist eine der optionen oben; SOLL immer aus sicht der FC
*/
function select_bestellungen_soll_gruppen( $art, $using = array() ) {
  switch( $art ) {
    case OPTION_ENDPREIS_SOLL:
      $expr = "(produktpreise.preis)";
      $query = 'waren';
      break;
    case OPTION_BRUTTO_SOLL:
      $expr = "(produktpreise.preis - produktpreise.pfand)";
      $query = 'waren';
      break;
    case OPTION_NETTO_SOLL:
      $expr = "( (produktpreise.preis - produktpreise.pfand) / ( 1.0 + produktpreise.mwst / 100.0 ) )";
      $query = 'waren';
      break;
    case OPTION_PFAND_VOLL_SOLL:
      $expr = "(produktpreise.pfand)";
      $query = 'waren';
      break;
    case OPTION_PFAND_LEER_SOLL:
      $expr = "(gruppenpfand.anzahl_rueckgabe * gruppenpfand.pfand_wert)";
      $query = 'pfand';
      break;
    default:
      error(__LINE__,__FILE__, "select_bestellungen_soll_gruppen: bitte Funktionsaufruf anpassen!", debug_backtrace());
  }
  switch( $query ) {
    case 'waren':
      return "
        SELECT -1.0 * IFNULL( sum( bestellzuordnung.menge * $expr ), 0.0 )
        FROM gruppenbestellungen
      " . need_joins( $using, array(
          'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                   ON gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id'
        ) ) . "
        JOIN bestellzuordnung
          ON gruppenbestellungen.id = bestellzuordnung.gruppenbestellung_id
        JOIN bestellvorschlaege
          ON (bestellvorschlaege.produkt_id = bestellzuordnung.produkt_id)
             AND ( bestellvorschlaege.gesamtbestellung_id = gruppenbestellungen.gesamtbestellung_id )
        JOIN produktpreise
          ON produktpreise.id = bestellvorschlaege.produktpreise_id
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
        WHERE 1 . use_filters( $using, array(
          'bestellgruppen' => 'gruppenpfand.gruppen_id = bestellgruppen.id'
        , 'gesamtbestellungen' => 'gruppenpfand.bestell_id = gesamtbestellungen.id'
        ) );
  }
}

/* select_bestellungen_soll_lieferanten:
 *   liefert als skalarer subquery forderung von lieferanten aus bestellungen
 *   $using ist array von tabellen, die aus dem uebergeordneten query benutzt werden sollen;
 *   erlaubte werte: 'gesamtbestellungen', 'lieferanten'
*/
function select_bestellungen_soll_lieferanten( $art, $using = array() ) {
  switch( $art ) {
    case OPTION_ENDPREIS_SOLL:
      $expr = "(produktpreise.preis)";
      $query = 'waren;
      break;
    case OPTION_BRUTTO_SOLL:
      $expr = "(produktpreise.preis - produktpreise.pfand)";
      $query = 'waren;
      break;
    case OPTION_NETTO_SOLL:
      $expr = "( (produktpreise.preis - produktpreise.pfand) / ( 1.0 + produktpreise.mwst / 100.0 ) )";
      $query = 'waren;
      break;
    case OPTION_PFAND_VOLL_SOLL:
      $expr = "( lieferantenpfand.anzahl_voll * pfandverpackungen.pfand_wert )";
      $query = 'pfand';
      break;
    
      return "
        SELECT -1.0 * IFNULL( sum( lieferantenpfand.anzahl_voll * pfandverpackungen.pfand_wert ), 0.0 )
        FROM lieferantenpfand
        " . need_joins( $using, array(
            'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                     ON gesamtbestellungen.id = lieferantenpfand.bestell_id'
          ) ) . "
        JOIN pfandverpackungen
          ON pfandverpackungen.id = lieferantenpfand.verpackung_id
        WHERE 1 . use_filters( $using, array(
          'lieferanten' => 'lieferantenpfand.lieferanten_id = lieferanten.id'
        , 'gesamtbestellungen' => 'gruppenpfand.bestell_id = gesamtbestellungen.id'
        ) );
    case OPTION_PFAND_LEER_SOLL:
      return "
        SELECT -1.0 * IFNULL( sum( lieferantenpfand.anzahl_leer * pfandverpackungen.pfand_wert ), 0.0 )
        FROM lieferantenpfand
        " . need_joins( $using, array(
            'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                     ON gesamtbestellungen.id = lieferantenpfand.bestell_id'
          ) ) . "
        JOIN pfandverpackungen
          ON pfandverpackungen.id = lieferantenpfand.verpackung_id
        WHERE 1 . use_filters( $using, array(
          'lieferanten' => 'lieferantenpfand.lieferanten_id = lieferanten.id'
        , 'gesamtbestellungen' => 'gruppenpfand.bestell_id = gesamtbestellungen.id'
        ) );
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
          JOIN produkte
            ON produkte.id = bestellvorschlaege.produkt_id
      " . need_joins( $using, array(
          'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                   ON gesamtbestellungen.id = bestellvorschlaege.gesamtbestellung_id'
      ) ) . "
        WHERE true " . use_filters( $using, array(
          'lieferanten' => 'lieferanten.id = produkte.lieferanten_id'
        , 'gesamtbestellungen' => 'bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id'
        ) );
    case 'pfand':
      return "
        SELECT -1.0 * IFNULL( sum( $expr ), 0.0 )
        FROM lieferantenpfand
        " . need_joins( $using, array(
            'gesamtbestellungen' => '(' .select_gesamtbestellungen_schuldverhaeltnis(). ') as gesamtbestellungen
                                     ON gesamtbestellungen.id = lieferantenpfand.bestell_id'
          ) ) . "
        JOIN pfandverpackungen
          ON pfandverpackungen.id = lieferantenpfand.verpackung_id
        WHERE 1 . use_filters( $using, array(
          'lieferanten' => 'lieferantenpfand.lieferanten_id = lieferanten.id'
        , 'gesamtbestellungen' => 'gruppenpfand.bestell_id = gesamtbestellungen.id'
        ) );
  }
}

function sql_gruppenpfand( $lieferanten_id, $bestell_id = 0, $group_by = "bestellgruppen.id" ) {
  if( $bestell_id ) {
    $filter = "gesamtbestellungen.id = $bestell_id";
  } else {
    $filter = "gesamtbestellungen.lieferanten_id = $lieferanten_id";
  }
  return doSql( "
    SELECT
      bestellgruppen.id as gruppen_id
    , bestellgruppen.aktiv as aktiv
    , bestellgruppen.name as gruppen_name
    , bestellgruppen.id % 1000 as gruppen_nummer
    , sum( (".select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_SOLL, array( 'gesamtbestellungen', 'bestellgruppen', 'gruppenpfand' ) ).") ) AS pfand_leer
    , sum( (".select_bestellungen_soll_gruppen( OPTION_PFAND_VOLL_SOLL, array( 'gesamtbestellungen', 'bestellgruppen', 'gruppenpfand' ) ).") ) AS pfand_voll
    , sum( gruppenpfand.anzahl_rueckgabe ) as anzahl_leer
    FROM bestellgruppen
    JOIN gesamtbestellungen
    LEFT JOIN gruppenpfand
      ON gruppenpfand.bestell_id = gesamtbestellungen.id
         AND gruppenpfand.gruppen_id = bestellgruppen.id
    WHERE $filter
    GROUP BY $group_by
    ORDER BY bestellgruppen.aktiv, bestellgruppen.id
  " );
}



/*  select_transaktionen_haben_gruppen:
 *   liefert als skalarer subquery forderung von gruppen aus gruppen_transaktion
 *   aus $using werden verwendet: 'bestellgruppen'
 */
function select_transaktionen_haben_gruppen( $using = array() ) {
  return "
    SELECT IFNULL( sum( summe ), 0.0 )
      FROM gruppen_transaktion
     WHERE ( gruppen_transaktion.gruppen_id > 0 ) " . use_filters( $using, array(
        'bestellgruppen' => 'bestellgruppen.id = gruppen_transaktion.gruppen_id'
  ) );
}

function select_transaktionen_pfand( $using = array() ) {
  return "
    SELECT IFNULL( sum( summe * IF( type=1, 1, 0 ) ), 0.0 )
      FROM gruppen_transaktion
     WHERE ( gruppen_transaktion.gruppen_id > 0 ) " . use_filters( $using, array(
        'bestellgruppen' => 'bestellgruppen.id = gruppen_transaktion.gruppen_id'
      , 'lieferanten' => 'lieferanten.id = gruppen_transaktion.lieferanten_id'
  ) );
}

/*  select_transaktionen_soll_lieferanten:
 *   liefert als skalarer subquery schuld von lieferanten aus gruppen_transaktion
 *   aus $using werden verwendet: 'lieferanten'
 */
function select_transaktionen_soll_lieferanten( $using = array() ) {
  return "
    SELECT IFNULL( sum( -summe ), 0.0 )
      FROM gruppen_transaktion
     WHERE ( gruppen_transaktion.lieferanten_id > 0 ) " . use_filters( $using, array(
       'lieferanten' => 'gruppen_transaktion.lieferanten_id = lieferanten.id'
  ) );
}

function select_haben_lieferanten( $using = array() ) {
  return "
    SELECT ( (" .select_bestellungen_soll_lieferanten($using). ")
            - (" .select_transaktionen_soll_lieferanten($using). ") ) as haben
  ";
}

function select_kontostand_gruppen( $using = array() ) {
  return "
    SELECT ( (".select_transaktionen_haben_gruppen($using).")
           + (".select_bestellungen_soll_gruppen(OPTION_ENDPREIS_SOLL,$using).") ) as haben
  ";
}

function select_pfandkontostand_gruppen( $using = array() ) {
  return "
    SELECT ( (".select_transaktionen_pfand($using).")
           - (".select_bestellungen_pfand($using).") ) as pfand
  ";
}

function select_pfandkontostand_lieferanten( $using = array() ) {
  return "
    SELECT ( (" .select_bestellungen_pfand($using). ")
            - (" .select_transaktionen_pfand($using). ") ) as haben
  ";
}

function sql_verbindlichkeiten_lieferanten() {
  return doSql( "
    SELECT lieferanten.id as lieferanten_id
         , lieferanten.name as name
         , ( ".select_haben_lieferanten('lieferanten')." ) as soll
    FROM lieferanten
    HAVING (soll <> 0)
  " );
}

function forderungen_gruppen_summe() {
  $row = sql_select_single_row( "
    SELECT ifnull( sum( table_soll_gruppe.soll_gruppe ), 0.0 ) as soll
    FROM (
      SELECT ( -(" .select_kontostand_gruppen('bestellgruppen'). ") ) AS soll_gruppe
      FROM (" .select_aktive_bestellgruppen(). ") AS bestellgruppen
      HAVING (soll_gruppe > 0)
    ) AS table_soll_gruppe
  " );
  return $row['soll'];
}

function guthaben_gruppen_summe() {
  $row = sql_select_single_row( "
    SELECT ifnull( sum( table_haben_gruppe.haben_gruppe ), 0.0 ) as haben
    FROM (
      SELECT (" .select_kontostand_gruppen('bestellgruppen'). ") AS haben_gruppe
      FROM (" .select_aktive_bestellgruppen(). ") AS bestellgruppen
      HAVING (haben_gruppe > 0)
    ) AS table_haben_gruppe
  " );
  return $row['haben'];
}

function sql_bestellungen_soll_gruppe( $gruppen_id ) {
  $query = "
    SELECT gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.name
         , DATE_FORMAT(gesamtbestellungen.lieferung,'%d.%m.%Y') as lieferdatum_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%d.%m.%Y') as valuta_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%Y%m%d') as valuta_kan
         , (" .select_bestellungen_soll_gruppen( OPTION_ENDPREIS_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as soll
         , (" .select_bestellungen_soll_gruppen( OPTION_PFAND_VOLL_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as pfand_voll_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as pfand_leer_soll
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") as gesamtbestellungen
    INNER JOIN gruppenbestellungen
      ON ( gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id )
    INNER JOIN bestellgruppen
      ON bestellgruppen.id = gruppenbestellungen.bestellguppen_id
    WHERE ( gruppenbestellungen.bestellguppen_id = $gruppen_id )
    ORDER BY valuta_kan DESC;
  ";
  $result = doSql($query, LEVEL_ALL, "Konnte Gesamtpreise nicht aus DB laden..");
  return $result;
}

function sql_bestellungen_haben_lieferant( $lieferanten_id ) {
  $query = "
    SELECT gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.name
         , DATE_FORMAT(gesamtbestellungen.lieferung,'%d.%m.%Y') as lieferdatum_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%d.%m.%Y') as valuta_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%Y%m%d') as valuta_kan
         , (" .select_bestellungen_soll_lieferanten( array('lieferanten','gesamtbestellungen') ). ") as soll
         , (" .select_bestellungen_pfand( array('lieferanten','gesamtbestellungen') ). ") as pfand
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") as gesamtbestellungen
    JOIN lieferanten
      ON lieferanten.id = $lieferanten_id
    HAVING haben <> 0
    ORDER BY valuta_kan DESC;
  ";
  return doSql( $query, LEVEL_ALL, "Suche nach Lieferantenforderungen fehlgeschlagen: " );
}

function sql_bestellung_rechnungssumme( $bestell_id ) {
  return sql_select_single_field( "
    SELECT (" .select_bestellungen_haben_lieferanten( array('gesamtbestellungen') ). ") as summe
    FROM gesamtbestellungen
    WHERE gesamtbestellungen.id = $bestell_id
  ", 'summe'
  );
}

function sql_bestellung_pfandsumme( $bestell_id ) {
  return sql_select_single_field( "
    SELECT (" .select_bestellungen_pfand( array('gesamtbestellungen') ). ") as pfand
    FROM gesamtbestellungen
    WHERE gesamtbestellungen.id = $bestell_id
  ", 'pfand'
  );
}


function kontostand($gruppen_id){
	//FIXME: zu langsam auf Gruppenview wenn Dienst5
  $row = sql_select_single_row( "
    SELECT (".select_kontostand_gruppen('bestellgruppen').") as haben
    FROM bestellgruppen
    WHERE bestellgruppen.id = $gruppen_id
  " );
  return $row['haben'];
}

function pfandkontostand($gruppen_id) {
  $row = sql_select_single_row( "
    SELECT (".select_pfandkontostand_gruppen('bestellgruppen').") as pfand
    FROM bestellgruppen
    WHERE bestellgruppen.id = $gruppen_id
  " );
  return $row['pfand'];
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
    SELECT (".select_haben_lieferanten('lieferanten').") as haben
    FROM lieferanten
    WHERE lieferanten.id = $lieferanten_id
  " );
  return $row['haben'];
}

function lieferantenpfandkontostand( $lieferanten_id ) {
  $row = sql_select_single_row( "
    SELECT (".select_pfandkontostand_lieferanten('lieferanten').") as pfand
    FROM lieferanten
    WHERE lieferanten.id = $lieferanten_id
  " );
  return $row['pfand'];
}

function sql_ungebuchte_einzahlungen( $gruppen_id = 0 ) {
  return doSql( "
    SELECT *
      , DATE_FORMAT(gruppen_transaktion.kontobewegungs_datum,'%d.%m.%Y') AS valuta_trad
      , DATE_FORMAT(gruppen_transaktion.eingabe_zeit,'%d.%m.%Y') AS eingabedatum_trad
    FROM gruppen_transaktion
    WHERE (konterbuchung_id = 0)
      and ( gruppen_id " . ( $gruppen_id ? "=$gruppen_id" : ">0" ) . ")
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
$masseinheiten = array( 'g', 'ml', 'ST', 'KI', 'PA', 'GL', 'BE', 'DO', 'BD', 'BT', 'KT', 'FL', 'EI', 'KA' );

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
  $row = sql_select_single_row( " SELECT (
     ( SELECT count(*) FROM bestellvorschlaege WHERE produkt_id=$produkt_id )
   + ( SELECT count(*) FROM bestellzuordnung WHERE produkt_id=$produkt_id )
  ) as count
  " );
  return $row['count'];
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



?>
