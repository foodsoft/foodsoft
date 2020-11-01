<?php

////////////////////////////////////
//
// debugging und fehlerbehandlung:
//
////////////////////////////////////

global $from_dokuwiki;
$from_dokuwiki or   // dokuwiki hat viele, viele "undefined variable"s !!!
  error_reporting(E_ALL); // alle Fehler anzeigen

define('LEVEL_NEVER', 5);
define('LEVEL_ALL', 4);
define('LEVEL_MOST', 3);
define('LEVEL_IMPORTANT', 2); // all UPDATE and INSERT statements should have level important
define('LEVEL_KEY', 1);
define('LEVEL_NONE', 0);

// LEVEL_CURRENT: alle sql-aufrufe bis zu diesem level werden angezeigt:
$_SESSION['LEVEL_CURRENT'] = LEVEL_NONE;

function sql_selects( $table, $prefix = false ) {
  global $tables;
  $cols = $tables[$table]['cols'];
  $selects = array();
  foreach( $cols as $name => $type ) {
    if( $name == 'id' ) {
      if( isstring( $prefix ) )
        $selects[] = "$table.id as {$prefix}id";
      else
        $selects[] = "$table.id as $table_id";
    } else {
      if( isstring( $prefix ) )
        $selects[] = "$table.$name as $prefix$name";
      else if( $prefix )
        $selects[] = "$table.$name as $table_$name";
      else
        $selects[] = "$table.$name as $name";
    }
  }
  return $selects;
}

function doSql( $sql, $debug_level = LEVEL_IMPORTANT, $error_text = "Datenbankfehler: " ) {
  global $db_handle;
  if($debug_level <= $_SESSION['LEVEL_CURRENT']) {
    open_div( 'alert', '', htmlspecialchars( $sql, ENT_QUOTES, 'UTF-8' ) );
  }
  $result = mysqli_query($db_handle, $sql);
  if( ! $result ) {
    error( $error_text. "\n  query: $sql\n  MySQL-error: " . mysqli_error($db_handle) );
  }
  return $result;
}

// turn $key and $cond into a boolean sql expression, using some heuristics.
//
function cond2filter( $key, $cond ) {
  if( $cond === NULL )
    return ' true ';
  if( is_numeric( $key ) ) {   // assume $cond is a complete boolean expression
    return " $cond ";
  } else {
    if( is_array( $cond ) ) {
      $f = "$key IN ";
      $komma = '(';
      foreach( $cond as $c ) {
        $f .= "$komma '$c'";
        $komma = ',';
      }
      return $f . ') ';
    } else if( strchr( $cond, ' ' ) ) {  // assume $cond contains an operator
      return " $key $cond ";
    } else {                      // assume we need a '=':
      return " $key = $cond ";
    }
  }
}

function get_sql_query( $op, $table, $selects = '*', $joins = '', $filters = false, $orderby = false, $groupby = false ) {
  if( is_string( $selects ) ) {
    $select_string = $selects;
  } else {
    $select_string = '';
    $komma = '';
    foreach( $selects as $s ) {
      $select_string .= "$komma $s";
      $komma = ',';
    }
  }
  if( is_string( $joins ) ) {
    $join_string = $joins;
  } else {
    $join_string = need_joins( array(), $joins );
  }
  $query = "$op $select_string FROM $table $join_string";
  if( $filters ) {
    if( is_string( $filters ) ) {
      $query .= " WHERE ( $filters ) ";
    } else {
      $and = 'WHERE';
      foreach( $filters as $key => $cond ) {
        $query .= " $and (". cond2filter( $key, $cond ) .") ";
        $and = 'AND';
      }
    }
  }
  if( $groupby ) {
    $query .= " GROUP BY $groupby ";
  }
  if( $orderby ) {
    $query .= " ORDER BY $orderby ";
  }
  return $query;
}

function select_query( $table, $selects = '*', $joins = '', $filters = false, $orderby = false ) {
  return get_sql_query( 'SELECT', $table, $selects, $joins, $filters, $orderby );
}

// function delete_query( $table, $what = '*', $joins = '', $filters = false ) {
//   return get_sql_query( 'DELETE', $table, $what, $joins, $filters, $orderby );
// }


function sql_select_single_row( $sql, $allownull = false, $result_type = MYSQLI_ASSOC ) {
  $result = doSql( $sql );
  $rows = mysqli_num_rows($result);
  // echo "<br>$sql<br>rows: $rows<br>";
  if( $rows == 0 ) {
    if( is_array( $allownull ) )
      return $allownull;
    if( $allownull )
      return NULL;
  }
  need( $rows > 0, "Kein Treffer bei Datenbanksuche: $sql" );
  need( $rows == 1, "Ergebnis der Datenbanksuche $sql nicht eindeutig ($rows)" );
  return mysqli_fetch_array($result, $result_type);
}

function sql_select_single_field( $sql, $field, $allownull = false ) {
  $row = sql_select_single_row( $sql, $allownull );
  if( ! $row )
    return NULL;
  if( isset( $row[$field] ) )
    return $row[$field];
  need( $allownull );
  return NULL;
}

function sql_count( $table, $where ) {
  return sql_select_single_field(
    "SELECT count(*) as count FROM $table WHERE $where"
  , 'count'
  );
}

function sql_update( $table, $where, $values, $escape_and_quote = true ) {
  global $db_handle;
  
  switch( $table ) {
    case 'leitvariable':
    case 'transactions':
    case 'logbook':
    case 'sessions':
      break;
    default:
      fail_if_readonly();
  }
  $sql = "UPDATE $table SET";
  $komma='';
  foreach( $values as $key => $val ) {
    if( $escape_and_quote )
      $val = "'" . mysqli_real_escape_string($db_handle, $val) . "'";
    $sql .= "$komma $key=$val";
    $komma=',';
  }
  if( is_array( $where ) ) {
    $and = 'WHERE';
    foreach( $where as $field => $val ) {
      if( $escape_and_quote )
        $val = "'" . mysqli_real_escape_string($db_handle, $val) . "'";
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
  global $db_handle;
  
  switch( $table ) {
    case 'leitvariable':
    case 'transactions':
    case 'logbook':
    case 'sessions':
      break;
    default:
      fail_if_readonly();
  }
  $komma='';
  $update_komma='';
  $cols = '';
  $vals = '';
  $update = '';
  foreach( $values as $key => $val ) {
    $cols .= "$komma $key";
    if( $escape_and_quote )
      $val = "'" . mysqli_real_escape_string($db_handle, $val) . "'";
    $vals .= "$komma $val";
    if( is_array( $update_cols ) ) {
      if( isset( $update_cols[$key] ) ) {
        if( $update_cols[$key] ) {
          $val = $update_cols[$key];
          if( $escape_and_quote )
            $val = "'" . mysqli_real_escape_string($db_handle, $val) . "'";
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
  if( doSql( $sql, LEVEL_IMPORTANT, "Einfügen in Tabelle $table fehlgeschlagen: "  ))
    return mysqli_insert_id($db_handle);
  else
    return FALSE;
}

function logger( $notiz ) {
  global $session_id;
  return sql_insert( 'logbook', array( 'notiz' => $notiz, 'session_id' => $session_id ) );
}

function adefault( $array, $index, $default ) {
  if( isset( $array[$index] ) )
    return $array[$index];
  else
    return $default;
}

function mysql2array( $result, $key = false, $val = false, $result_type = MYSQLI_ASSOC ) {
  if( is_array( $result ) )  // temporary kludge: make me idempotent
    return $result;
  $r = array();
  $n = 1;
  while( $row = mysqli_fetch_array( $result, $result_type ) ) {
    if( $key ) {
      need( isset( $row[$key] ) );
      need( isset( $row[$val] ) );
      $r[$row[$key]] = $row[$val];
    } else {
      $row['nr'] = $n++;
      $r[] = $row;
    }
  }
  return $r;
}


/*
 * need_joins: fuer skalare subqueries wie in "SELECT x , ( SELECT ... ) as y, z":
 *  erzeugt aus $rules JOIN-anweisungen fuer benoetigte tabellen; in $using koennen
 *  tabellen uebergeben werden, die bereits verfuegbar sind
 */
function need_joins_array( $using, $rules ) {
  $joins = array();
  is_array( $using ) or $using = array( $using );
  foreach( $rules as $table => $rule ) {
    if( ! in_array( $table, $using ) ) {
      if( strstr( $rule, ' ON ' ) ) {
        $joins[] = $rule;
      } else {
        $joins[$table] = $rule;
      }
    }
  }
  return $joins;
}
function need_joins( $using, $rules ) {
  $joins = '';
  $joins_array = need_joins_array( $using, $rules );
  foreach( $joins_array as $table => $rule ) {
    if( is_numeric( $table ) ) {
      if ( strstr( $rule, 'JOIN ') ) {
        $joins .= " $rule ";
      } else {
        $joins .= " JOIN $rule ";
      }
    } else {
      $joins .= " JOIN $table ON $rule ";
    }
  }
  return $joins;
}

/*
 * use_filters: fuer skalare subqueries wie in "SELECT x , ( SELECT ... ) as y, z":
 *  erzeugt optionale filterausdruecke, die bereits verfuegbare tabellen benutzen
 */
function use_filters_array( $using, $rules ) {
  $filters = array();
  is_array( $using ) or $using = array( $using );
  foreach( $rules as $table => $f ) {
    if( in_array( $table, $using ) ) {
      $filters[] = $f;
    }
  }
  return $filters;
}
function use_filters( $using, $rules ) {
  $filters = '';
  $filters_array = use_filters_array( $using, $rules );
  foreach( $filters_array as $f ) {
    $filters .= " AND ( $f ) ";
  }
  return $filters;
}


////////////////////////////////////
//
// dienstplan-funktionen:
//
////////////////////////////////////

$_SESSION['DIENSTEINTEILUNG'] =  array('1/2', '3', '4', '5', 'freigestellt');

function select_dienste( $filter = 'true' ) {
  return "SELECT
           dienste.*
         , if( gruppenmitglieder.gruppen_id, gruppenmitglieder.gruppen_id, dienste.gruppen_id ) as gruppen_id
         , gruppenmitglieder.name
         , gruppenmitglieder.vorname
         , gruppenmitglieder.telefon
         , gruppenmitglieder.email
         , gruppenmitglieder.diensteinteilung
         , gruppenmitglieder.aktiv
         , ( if( not dienste.geleistet and ( adddate( curdate(), 14 ) >= dienste.lieferdatum ), 1, 0 ) ) as soon
         , if( lieferdatum <= CURDATE(), 1, 0 ) as over
         , ( if( lieferdatum < ( SELECT max(lieferdatum) FROM dienste WHERE lieferdatum < CURDATE() ), 1, 0 ) ) as historic
         , if( lieferdatum > adddate( curdate(), -32 ), 1, 0 ) as editable
    FROM dienste
    LEFT JOIN gruppenmitglieder
      ON (gruppenmitglieder_id = gruppenmitglieder.id)
    WHERE ( $filter )
  ";
}

function sql_dienste( $filter = 'true', $orderby = 'lieferdatum ASC, dienst' ) {
  return mysql2array( doSql(
    select_dienste( $filter ) . " ORDER BY $orderby "
  , LEVEL_ALL, "error while reading from dienste"
  ) );
}

function sql_dienst( $dienst_id ) {
  return sql_select_single_row( select_dienste( "dienste.id = $dienst_id" ) );
}

/** Gibt das Datum für den letzten
 *  Dienst im Dienstplan zurück.
 */
function get_latest_dienst( $add_days = 0 ) {
  return sql_select_single_field( "
    SELECT ifnull( adddate( max(lieferdatum), $add_days ), curdate() ) AS datum FROM dienste
  " , 'datum'
  );
}

function sql_dienste_tauschmoeglichkeiten( $dienst_id ) {
  $dienst = sql_dienst( $dienst_id );
  $filter = "
        ( dienste.dienst = '{$dienst['dienst']}' )
    AND ( dienste.id != $dienst_id )
    AND ( NOT dienste.geleistet )
    AND ( dienste.lieferdatum >= curdate() )
    AND ( dienste.lieferdatum != '{$dienst['lieferdatum']}' )
  ";
  $r = sql_dienste( $filter . " AND ( dienste.status in ( 'Offen', 'Vorgeschlagen' ) ) " );
  return $r;
}


/**
 *  Dienst Akzeptieren (oder auch bestaetigen)
 */ 
function sql_dienst_akzeptieren( $dienst_id, $abgesprochen = false, $status_neu = 'Akzeptiert' ) {
  global $login_gruppen_id;

  $dienst = sql_dienst( $dienst_id );
  need( ! $dienst['geleistet'], 'Dienst bereits geleistet!' );
  switch( $dienst['status'] ) {
    case 'Akzeptiert':
    case 'Bestaetigt':
      if( $dienst['gruppen_id'] )
        if( $login_gruppen_id != $dienst['gruppen_id'] )
          need( $abgesprochen, "Dienst schon {$dienst['status']}: Uebernahme nur nach Absprache" );
    case 'Vorgeschlagen':
    case 'Offen':
      break;
    default:
      error( "sql_dienst_akzeptieren(): falscher Status!" );
  }
  sql_update( 'dienste', $dienst_id, array( 'status' => $status_neu ) );
  if( $dienst['gruppen_id'] != $login_gruppen_id ) {
    $mitglieder = sql_gruppe_mitglieder( $login_gruppen_id );
    if( count( $mitglieder ) == 1 ) {
      $mitglied = current( $mitglieder );
      $m_id = $mitglied['gruppenmitglieder_id'];
    } else {
      $m_id = 0;
    }
    sql_update( 'dienste', $dienst_id, array(
      'gruppen_id' => $login_gruppen_id, 'gruppenmitglieder_id' => $m_id
    ) );
  }
}

/**
 *  Dienst ablehnen, nachdem die Gruppe ihn schon akzeptiert hat (wird 'Offen')
 */
function sql_dienst_wird_offen( $dienst_id ) {
  global $login_gruppen_id;
  $dienst = sql_dienst( $dienst_id );
  need( ! $dienst['geleistet'], 'Dienst bereits geleistet!' );
  need( hat_dienst(5) || ( $dienst['gruppen_id'] == $login_gruppen_id ) );
  sql_update( 'dienste', $dienst_id, array(
    'status' => 'Offen', 'gruppen_id' => 0, 'gruppenmitglieder_id' => 0
  ) );
}

/**
 *  Dienst ablehnen und alternative suchen
 */
function sql_dienst_abtauschen( $dienst_id, $tausch_id ) {
  global $login_gruppen_id;

  $dienst = sql_dienst( $dienst_id );
  need( ! $dienst['geleistet'], 'Dienst bereits geleistet!' );
  need( $dienst["status"] == "Vorgeschlagen", "falscher Status ".$dienst['status'] );
  need( $dienst["gruppen_id"] == $login_gruppen_id, "falsche gruppen_id" );

  $ausweichdienste = sql_dienste_tauschmoeglichkeiten( $dienst_id );
  foreach( $ausweichdienste as $h ) {
    if( $h['id'] == $tausch_id ) {
      sql_dienst_akzeptieren( $h['id'] );
      return sql_update( 'dienste', $dienst_id, array(
        'gruppen_id' => $h['gruppen_id']
      , 'gruppenmitglieder_id' => $h['gruppenmitglieder_id']
      , 'status' => $h['status']
      ) );
    }
  }
  error( "Diensttausch fehlgeschlagen: kein gueltiger Ausweichdienst gewaehlt" );
}

/**
 * Person, die den Dienst ausführt verändern
 */
function sql_dienst_person_aendern( $dienst_id, $person_id ) {
  global $login_gruppen_id;
  $dienst = sql_dienst( $dienst_id );
  $person = sql_gruppenmitglied( $person_id );
  need( $person['aktiv'] );
  need( ! $dienst['geleistet'] );
  if( ! hat_dienst(5) ) {
    need( $login_gruppen_id == $person['gruppen_id'] );
    if( $dienst['gruppen_id'] )
      need( $login_gruppen_id == $dienst['gruppen_id'] );
  }
  sql_update( 'dienste', $dienst_id, array(
    'gruppen_id' => $person['gruppen_id']
  , 'gruppenmitglieder_id' => $person_id
  ) );
  if( $person['gruppen_id'] != $dienst['gruppen_id'] ) {
    sql_update( 'dienste', $dienst_id, array( 'status' => 'Vorgeschlagen' ) );
  }
}

function sql_dienst_gruppe_aendern( $dienst_id, $gruppen_id ) {
  global $login_gruppen_id;
  $dienst = sql_dienst( $dienst_id );
  if( $gruppen_id == $dienst['gruppen_id'] )
    return;
  $gruppe = sql_gruppe( $gruppen_id );
  need( $gruppe['aktiv'] );
  need( ! $dienst['geleistet'] );
  nur_fuer_dienst(5);
  if( $gruppe['mitgliederzahl'] == 1 ) {
    $mitglied = current( sql_gruppe_mitglieder( $gruppen_id ) );
    $m_id = $mitglied['gruppenmitglieder_id'];
  } else {
    $m_id = 0;
  }
  sql_update( 'dienste', $dienst_id, array(
    'gruppen_id' => $gruppen_id
  , 'gruppenmitglieder_id' => $m_id
  , 'status' => 'Vorgeschlagen'
  ) );
}

/**
 * Gibt es an einem Datum Dienste, die noch offen, vorgeschlagen oder akzeptiert sind
 * (so dass nicht sicher ist, ob der Dienst geleistet wird)
 */
function sql_dienste_nicht_bestaetigt( $datum ) {
  return sql_dienste( " ( lieferdatum = '$datum' ) and not status = 'Bestaetigt' " );
}

/** Fuegt einen neuen Dienst in die Diensttabelle
 * - mit mitglied_id: dienst wird 'Vorgeschlagen'
 * - ohne mitglied_id: dienst wird 'Offen'
 */
function sql_create_dienst( $datum, $dienst, $mitglied_id = 0 ) {
  nur_fuer_dienst(5);
  if( $mitglied_id ) {
    $mitglied = sql_gruppenmitglied( $mitglied_id );
    need( $mitglied['aktiv'] );
    sql_insert( 'dienste', array(
      'gruppenmitglieder_id' => $mitglied_id
    , 'gruppen_id' => $mitglied['gruppen_id'] // falls mitglied ausscheidet: dienst bleibt bei gruppe!
    , 'dienst' => $dienst
    , 'lieferdatum' => $datum
    , 'status' => 'Vorgeschlagen'
    ) );
  } else {
    sql_insert( 'dienste', array(
      'gruppenmitglieder_id' => 0
    , 'gruppen_id' => 0
    , 'dienst' => $dienst
    , 'lieferdatum' => $datum
    , 'status' => 'Offen'
    ) );
  }
}

function sql_delete_dienst( $dienst_id ) {
  nur_fuer_dienst(5);
  return doSql(
    "DELETE FROM dienste WHERE id=$dienst_id"
  , LEVEL_IMPORTANT, "Dienst loeschen fehlgeschlagen"
  );
}

function sql_dienst_mute_reconfirmation( $session_id ) {
    sql_update( 'sessions' 
      , $session_id
      , array( 
          'muteReconfirmation_timestamp' => 'NOW()' )
      , false );
}

////////////////////////////////////
//
// rotationsplan-funktionen:
//
////////////////////////////////////

function sql_rotationsplan_mitglied( $position ) {
  return sql_select_single_field( "
    SELECT id FROM gruppenmitglieder WHERE rotationsplanposition = $position
  ", 'id'
  );
}

function sql_rotationsplan_dienst( $position ) {
  return sql_select_single_field( "
    SELECT diensteinteilung FROM gruppenmitglieder WHERE rotationsplanposition = $position
  ", 'diensteinteilung'
  );
}

function sql_rotationsplanposition( $mitglied_id ) {
  return sql_select_single_field( "
    SELECT rotationsplanposition FROM gruppenmitglieder WHERE id = $mitglied_id
  ", 'rotationsplanposition'
  );
}

/** Queries the rotation plan for a given task.
 */
function sql_rotationsplan( $dienst ) {
  return sql_gruppenmitglieder( "gruppenmitglieder.aktiv AND ( diensteinteilung = '$dienst' ) ", "rotationsplanposition ASC" );
}

/**
 *  Gibt die nächste Position für einen Dienst aus dem Rotationsplan zurück
 */
function sql_rotationsplan_next( $current, $dienst = false ) {
  if( ! $dienst ) {
    need( $current );
    $dienst = sql_rotationsplan_dienst( $current );
  }
  $morefilter = ( $current ? "rotationsplanposition > $current" : "true" );
  $next = sql_select_single_field( "
    SELECT min(rotationsplanposition) AS mynext FROM gruppenmitglieder
    WHERE aktiv AND diensteinteilung = '$dienst' AND $morefilter
  ", 'mynext', true
  );
  if( $next )
    return $next;
  need( $current, "Kein Eintrag im Rotationsplan fuer Dienst $dienst" );
  return sql_rotationsplan_next( 0, $dienst );
}

function sql_rotationsplan_prev( $current, $dienst = false ) {
  if( ! $dienst ) {
    need( $current );
    $dienst = sql_rotationsplan_dienst( $current );
  }
  $morefilter = ( $current ? "rotationsplanposition < $current" : "true" );
  $prev = sql_select_single_field( "
    SELECT max(rotationsplanposition) AS myprev FROM gruppenmitglieder
    WHERE aktiv AND diensteinteilung = '$dienst' AND $morefilter
  ", 'myprev', true
  );
  if( $prev )
    return $prev;
  need( $current, "Kein Eintrag im Rotationsplan fuer Dienst $dienst" );
  return sql_rotationsplan_prev( 0, $dienst );
}


/**
 *  This function allows to rotate the
 *  rotation system. This is used after
 *  assigning new tasks. The rotation is
 *  performed in a way that the group with
 *  the latest assignment will the the last
 *  in the rotation system.
 */
function sql_rotate_rotationsplan( $latest_position ) {
  nur_fuer_dienst(5);
  $dienst = sql_rotationsplan_dienst( $latest_position );
  $after = mysql2array( doSql( "
    SELECT id, rotationsplanposition FROM gruppenmitglieder
    WHERE diensteinteilung = '$dienst' AND aktiv AND rotationsplanposition > $latest_position
    ORDER BY rotationsplanposition ASC
  " ) );
  if( ! $after )
    return;
  $before = mysql2array( doSql( "
    SELECT id, rotationsplanposition FROM gruppenmitglieder
    WHERE diensteinteilung = '$dienst' AND aktiv AND rotationsplanposition <= $latest_position
    ORDER BY rotationsplanposition ASC
  " ) );
  need( $before, "Fehler im Rotationsplan: Eintrag $latest_position nicht gefunden" );

  $old = array_merge( $before, $after );
  $new = array_merge( $after, $before );

  // rotationsplanposition ist UNIQUE KEY; daher erstmal alles aus dem Weg raeumen:
  foreach( $new as $row ) {
    sql_update( 'gruppenmitglieder', $row['id'],
                 array( 'rotationsplanposition' => - $row['rotationsplanposition'] ) );
  }

  $oldrow = current( $old );
  foreach( $new as $newrow ) {
    sql_update( 'gruppenmitglieder', $newrow['id'],
                 array( 'rotationsplanposition' => $oldrow['rotationsplanposition'] ) );
    $oldrow = next( $old );
  }
}

/**
 *  This function allows to move a person up or down within the rotation system
 */
function sql_change_rotationsplan( $mitglied_id, $dienst, $move_down ) {
  nur_fuer_dienst(5);
  $position = sql_rotationsplanposition( $mitglied_id );
  $newpos = ( $move_down ? sql_rotationsplan_next( $position ) : sql_rotationsplan_prev( $position ) );
  if( $position == $newpos )
    return;

  $newmitglied_id = sql_rotationsplan_mitglied( $newpos );
  sql_update( 'gruppenmitglieder', $mitglied_id, array( 'rotationsplanposition' => -1 ) );
  sql_update( 'gruppenmitglieder', $newmitglied_id, array( 'rotationsplanposition' => $position ) );
  sql_update( 'gruppenmitglieder', $mitglied_id, array( 'rotationsplanposition' => $newpos ) );
}

/**
 *  Erzeugt Dienste für einen Zeitraum
 */
function create_dienste( $start, $spacing, $zahl, $personenzahlen ) {
  foreach( $personenzahlen as $dienstname => $personen ) {
    $positionen[$dienstname] = 0;
  }
  for( $n = 1; $n <= $zahl; $n++ ) {
    foreach( $personenzahlen as $dienstname => $personen ) {
      for( $i=1; $i <= $personen; $i++ ) {
        $plan_position = sql_rotationsplan_next( $positionen[$dienstname], $dienstname );
        sql_create_dienst( $start, $dienstname, sql_rotationsplan_mitglied( $plan_position ) );
        $positionen[$dienstname] = $plan_position;
      }
    }
    $start = sql_select_single_field( "SELECT adddate( '$start', $spacing ) AS date", 'date' );
  }
  //Wenn ein Dienst erzeugt wurde, rotationsplan umstellen:
  foreach( $positionen as $plan_position ) {
    if( $plan_position) {
      sql_rotate_rotationsplan( $plan_position);
    }
  }
}


//////////////////////////////
//
// Funktionen fuer Hauptmenue
// (todo: nach views verschieben?)
//
//////////////////////////////


/**
 * Returns an array of functions (i.e. forms) a
 * group is allowed to access based on the task
 * they are performing
 */
function possible_areas(){
  global $exportDB;

  $areas = array();

   $areas[] = array("area" => "bestellen",
     "hint" => "Hier können ihr euch an den laufenden Bestellung beteiligen",
     "title" => "Bestellen");

   if( hat_dienst(0) ) {
    $areas[] = array("area" => "meinkonto", 
             "hint"  => "Hier könnt ihr euer Gruppenkonto einsehen", 
       "title" => "Mein Konto" );
   }
     $areas[] = array("area" => "gruppen",
     "hint" => "Hier kann man die Bestellgruppen und deren Konten verwalten...",
     "title" => "Gruppen");

   $areas[] = array("area" => "bestellungen",
     "hint" => "Übersicht aller Bestellungen (laufende und abgeschlossene)",
     "title" => "Alle Bestellungen");

   if( hat_dienst(3,4) ) {
     $areas[] = array("area" => "basar",
     "hint" => "Produkte im Basar an Gruppen verteilen",
     "title" => "Basar");
   } else {
     $areas[] = array("area" => "basar",
     "hint" => "Waren im Basar auflisten",
     "title" => "Basar");
   }

   $areas[] = array("area" => "bilanz",
     "hint" => "Finanzen der FC: Überblick und Verwaltung",
     "title" => "Bilanz");

   if( hat_dienst(4) ){
     $areas[] = array("area" => "produkte",
     "hint" => "Neue Produkte eingeben ... Preise verwalten ... Bestellung online stellen","title" => "Produkte");	 
     $areas[] = array("area" => "konto",
     "hint" => "Hier könnt ihr die Bankkonten verwalten...",
     "title" => "Konten");
     $areas[] = array("area" => "lieferanten",
     "hint" => "Hier kann man die LieferantInnen verwalten...",
     "title" => "LieferantInnen");
   } else {
     $areas[] = array("area" => "produkte",
     "hint" => "Produktdatenbank und Kataloge einsehen","title" => "Produkte");	 
     $areas[] = array("area" => "konto",
     "hint" => "Hier könnt ihr die Kontoauszüge der Bankkonten einsehen...",
     "title" => "Konten");
     $areas[] = array("area" => "lieferanten",
     "hint" => "Hier könnt ihr die LieferantInnen einsehen...",
     "title" => "LieferantInnen");
  }

  $areas[] = array("area" => "dienstkontrollblatt",
    "hint" => "Hier kann man das Dienstkontrollblatt einsehen...",
    "title" => "Dienstkontrollblatt");

  if( ( hat_dienst(4) && $exportDB ) ) {
    $areas[] = array("area" => "updownload",
    "hint" => "Hier kann die Datenbank runtergeladen werden...",
    "title" => "Download");
  }

   $areas[] = array("area" => "dienstplan", 
     "hint"  => "Eigene Dienste anschauen, Dienste übernehmen, ...", 
     "title" => "Dienstplan" );

   return $areas;
}


//////////////////////////////
//
// Passwort-Funktionen:
//
//////////////////////////////

$urandom_handle = false;
function random_hex_string( $bytes ) {
  global $urandom_handle;
  if( ! $urandom_handle )
    need( $urandom_handle = fopen( '/dev/urandom', 'r' ), 'konnte /dev/urandom nicht oeffnen' );
  $s = '';
  while( $bytes > 0 ) {
    $c = fgetc( $urandom_handle );
    need( $c !== false, 'Lesefehler von /dev/urandom' );
    $s .= sprintf( '%02x', ord($c) );
    $bytes--;
  }
  return $s;
}

function check_password( $gruppen_id, $gruppen_pwd ) {
  global $specialgroups;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    if( in_array( $gruppen_id, $specialgroups ) )
      return false;
    $row = sql_gruppe( $gruppen_id );
    if( ! $row['aktiv'] )
      return false;
    if( $row['passwort'] == crypt($gruppen_pwd,$row['salt']) )
      return $row;
  }
  return false;
}

function set_password( $gruppen_id, $gruppen_pwd ) {
  global $login_gruppen_id;
  if ( $gruppen_pwd != '' && $gruppen_id != '' ) {
    ( $gruppen_id == $login_gruppen_id ) or nur_fuer_dienst(5);
    $salt = random_hex_string( 4 );
    return sql_update( 'bestellgruppen', $gruppen_id, array(
      'salt' => $salt
    , 'passwort' => crypt( $gruppen_pwd, $salt )  // TODO: this is not yet very good...
    ) );
  }
}

////////////////////////////////////
//
// dienstkontrollblatt-Funktionen:
//
////////////////////////////////////

function dienstkontrollblatt_eintrag( $dienstkontrollblatt_id, $gruppen_id, $dienst, $name, $telefon, $notiz, $datum = '', $zeit = '' ) {
  global $db_handle;
  $notiz = mysqli_real_escape_string($db_handle, $notiz);
  $telefon = mysqli_real_escape_string($db_handle, $telefon);
  $name = mysqli_real_escape_string($db_handle, $name);
  if( $dienstkontrollblatt_id ) {
    doSql( "
      UPDATE dienstkontrollblatt SET
        name = " . ( $name ? "'$name'" : "name" ) . "
      , telefon = " . ( $telefon ? "'$telefon'" : "telefon" ) . "
      , notiz = " . ( $notiz ? "IF( notiz = '$notiz', notiz, CONCAT( notiz, ' --- $notiz' ) )" : "notiz" ) . "
      WHERE id='$dienstkontrollblatt_id'
    ", LEVEL_ALL, "Eintrag im Dienstkontrollblatt fehlgeschlagen: "
    );
    return $dienstkontrollblatt_id;
  } else {
    doSql( "
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
    ", LEVEL_ALL, "Eintrag im Dienstkontrollblatt fehlgeschlagen: "
    );
    return mysqli_insert_id($db_handle);
    //  WARNING: ^ does not always work (see http://bugs.mysql.com/bug.php?id=27033)
    //  (fixed in mysql-5.0.45)
  }
}

function sql_dienstkontrollblatt( $from_id = 0, $to_id = 0, $gruppen_id = 0, $dienst = 0 ) {
  $to_id or $to_id = $from_id;
  $where = '';
  $and = 'WHERE';
  if( $from_id ) {
    $where .= "$and (dienstkontrollblatt.id >= $from_id) AND (dienstkontrollblatt.id <= $to_id)";
    $and = 'AND';
  }
  if( $gruppen_id ) {
    $where .= "$and ( gruppen_id = $gruppen_id) ";
    $and = 'AND';
  }
  if( $dienst ) {
    $where = "$and ( dienst = '$dienst') ";
    $and = 'AND';
  }
  return mysql2array( doSql( "
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
  ", LEVEL_IMPORTANT, "Suche in dienstkontrollblatt fehlgeschlagen: "
  ) );
}

function sql_dienstkontrollblatt_name( $id ) {
  return sql_select_single_field( "SELECT name FROM dienstkontrollblatt WHERE id=$id", 'name' );
}


////////////////////////////////////
//
// bestellgruppen-funktionen:
//
////////////////////////////////////

// gruppen_id der basar und der bad-bank gruppe sind in den leitvariablen definiert:
//
function sql_basar_id() {
  global $basar_id;
  need( $basar_id, "Spezielle Basar-Gruppe nicht gesetzt (in tabelle leitvariablen!)" );
  return $basar_id;
}
function sql_muell_id() {
  global $muell_id;
  need( $muell_id, "Spezielle Muell-Gruppe nicht gesetzt (in tabelle leitvariablen!)" );
  return $muell_id;
}

function select_gruppenmitglieder() {
  return "
    SELECT bestellgruppen.name as gruppenname
         , bestellgruppen.id as gruppen_id
         , bestellgruppen.id % 1000 as gruppennummer
         , gruppenmitglieder.id as gruppenmitglieder_id
         , gruppenmitglieder.vorname as vorname
         , gruppenmitglieder.name as name
         , gruppenmitglieder.telefon as telefon
         , gruppenmitglieder.email as email
         , gruppenmitglieder.diensteinteilung as diensteinteilung
         , gruppenmitglieder.rotationsplanposition as rotationsplanposition
         , gruppenmitglieder.aktiv as aktiv
         , gruppenmitglieder.sockeleinlage as sockeleinlage
         , gruppenmitglieder.slogan as slogan
         , gruppenmitglieder.url as url
         , gruppenmitglieder.photo_url as photo_url
         , gruppenmitglieder.notiz as notiz
    FROM gruppenmitglieder
    JOIN bestellgruppen ON bestellgruppen.id = gruppenmitglieder.gruppen_id
  ";
}

function sql_gruppenmitglied( $gruppenmitglieder_id, $allow_null = false ) {
  return sql_select_single_row(
    select_gruppenmitglieder() . " WHERE gruppenmitglieder.id = $gruppenmitglieder_id "
  , $allow_null
  );
}

function sql_gruppenmitglieder( $filter = 'true', $orderby = 'gruppennummer' ) {
  return mysql2array( doSql( select_gruppenmitglieder() . " WHERE ( $filter ) ORDER BY $orderby " ) );
}

function sql_gruppe_mitglieder( $gruppen_id, $filter = 'gruppenmitglieder.aktiv' ) { 
  return sql_gruppenmitglieder( "(bestellgruppen.id = $gruppen_id) and ($filter) " );
}

function query_gruppen( $op, $keys = array(), $using = array(), $orderby = false ) {
  $selects = array();
  $filters = array();
  $joins = array();

  $selects[] = 'bestellgruppen.name as name';
  $selects[] = 'bestellgruppen.id as id';
  $selects[] = 'bestellgruppen.aktiv as aktiv';
  $selects[] = 'bestellgruppen.passwort as passwort';
  $selects[] = 'bestellgruppen.salt as salt';
  $selects[] = 'bestellgruppen.sockeleinlage as sockeleinlage_gruppe';
  $selects[] = 'bestellgruppen.notiz_gruppe as notiz_gruppe';
  $selects[] = '
    ( SELECT count(*) FROM gruppenmitglieder
        WHERE gruppenmitglieder.aktiv
          AND gruppenmitglieder.gruppen_id = bestellgruppen.id
      ) as mitgliederzahl
  ';
  $selects[] = "
    ( SELECT count(*) FROM gruppenmitglieder
        WHERE gruppenmitglieder.aktiv
          AND gruppenmitglieder.gruppen_id = bestellgruppen.id
          AND gruppenmitglieder.photo_url != ''
    ) as avatars_count
  ";
  $selects[] = 'bestellgruppen.id % 1000 as gruppennummer';

  foreach( $keys as $key => $cond ) {
    switch( $key ) {
      case 'id':
      case 'gruppen_id':
        $filters['bestellgruppen.id'] = $cond;
        break;
      case 'gruppennummer':
        need( is_numeric( $cond ) );
        $filters[] = "(bestellgruppen.id % 1000) = $cond";
        break;
      case 'aktiv':
        $filters['bestellgruppen.aktiv'] = $cond;
        break;
      case 'bestell_id':
        $joins['gruppenbestellungen'] = 'gruppenbestellungen.bestellgruppen_id = bestellgruppen.id';
        $filters['gruppenbestellungen.gesamtbestellung_id'] = $cond;
        $selects[] = 'gruppenbestellungen.id as gruppenbestellung_id';
        break;
      case 'produkt_id':
        $joins['gruppenbestellungen'] = 'gruppenbestellungen.bestellgruppen_id = bestellgruppen.id';
        $joins['bestellzuordnung'] = 'bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id';
        $filters['bestellzuordnung.produkt_id'] = $cond;
        // $selects[] = 'bestellzuordnung.menge as menge';
        // $selects[] = 'bestellzuordnung.art as art';
        break;
      case 'where':
        $filters = $cond;
        break;
      default:
          error( "undefined key: $key" );
    }
  }
  switch( $op ) {
    case 'SELECT':
      break;
    case 'COUNT':
      $op = 'SELECT';
      $selects = 'COUNT(*) as anzahl';
      break;
    default:
      error( "undefined op: $op" );
  }
  return get_sql_query( $op, 'bestellgruppen', $selects, $joins, $filters, $orderby, 'bestellgruppen.id' );
}
function select_gruppen( $keys = array(), $using = array(), $orderby = false ) {
  return query_gruppen( 'SELECT', $keys, $using, $orderby );
}

function sql_gruppen( $keys = array(), $orderby = 'NOT(aktiv), gruppennummer' ) {
  return mysql2array( doSql( select_gruppen( $keys, array(), $orderby ) ) );
}

function sql_gruppe( $gruppen_id, $allow_null = false ) {
  return sql_select_single_row( select_gruppen( array( 'gruppen_id' => $gruppen_id ) ), $allow_null );
}

function sql_gruppenname( $gruppen_id ) {
  return sql_select_single_field( select_gruppen( array( 'gruppen_id' => $gruppen_id ) ), 'name' );
}

function sql_gruppennummer( $gruppen_id ) {
  return $gruppen_id % 1000;
}

function sql_gruppe_aktiv( $gruppen_id ) {
  return sql_select_single_field( select_gruppen( array( 'gruppen_id' => $gruppen_id ) ), 'aktiv' );
}


// sql_gruppe_letztes_login(), sql_gruppe_letzte_bestellung():
// 2 Funktionen zum Ermitteln von Karteileichen:
//
function sql_gruppe_letztes_login( $gruppen_id ) {
  global $login_gruppen_id;
  need( hat_dienst(4,5) or ( $gruppen_id == $login_gruppen_id ) );
  $result = doSql( "
    SELECT sessions.id, logbook.time_stamp
    FROM sessions
    JOIN logbook
      ON logbook.session_id = sessions.id
    WHERE sessions.login_gruppen_id = $gruppen_id
    ORDER BY time_stamp DESC
  " );
  return mysqli_fetch_array( $result );
}
function sql_gruppe_letzte_bestellung( $gruppen_id ) {
  global $login_gruppen_id;
  need( hat_dienst(4,5) or ( $gruppen_id == $login_gruppen_id ) );
  $result = doSql( "
    SELECT gesamtbestellungen.id, gesamtbestellungen.lieferung as lieferdatum
    FROM gruppenbestellungen
    JOIN gesamtbestellungen
      ON gesamtbestellungen.id = gruppenbestellungen.gesamtbestellung_id
    WHERE gruppenbestellungen.bestellgruppen_id = $gruppen_id
    ORDER BY lieferdatum DESC
  " );
  return mysqli_fetch_array( $result );
}

function sql_gruppe_offene_bestellungen( $gruppen_id ) {
  need( hat_dienst(4,5) );
  return mysql2array( doSql( "
    SELECT gesamtbestellungen.name as name
         , gesamtbestellungen.rechnungsstatus as rechnungsstatus
    FROM gesamtbestellungen
    INNER JOIN gruppenbestellungen
      ON gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id
    WHERE ( gesamtbestellungen.rechnungsstatus < ".STATUS_ABGERECHNET." )
      AND ( gruppenbestellungen.bestellgruppen_id = $gruppen_id )
    ORDER BY gesamtbestellungen.lieferung
  " ) );
}

function optionen_gruppen(
  $selected = 0
, $keys = array( 'aktiv' => 'true' )
, $option_0 = false
) {
  $output='';
  if( $option_0 ) {
    $output = "<option value='0'";
    if( $selected == 0 ) {
      $output = $output . " selected";
      $selected = -1;
    }
    $output = $output . ">$option_0</option>";
  }
  foreach( sql_gruppen( $keys ) as $gruppe ) {
    $id = $gruppe['id'];
    $output = "$output
      <option value='$id'";
    if( $selected == $id ) {
      $output = $output . " selected";
      $selected = -1;
    }
    if( $id == sql_muell_id() )
      $gruppe['name'] = "== BadBank ==";
    if( $id == sql_basar_id() )
      $gruppe['name'] = "== Basar ==";
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
function check_new_group_nr( $newNummer, & $problems ){
  global $specialgroups;

  if( ( ! ( $newNummer > 0 ) ) || ( $newNummer > 98 ) ) {
    $problems .= "<div class='warn'>Ung&uuml;ltige Gruppennummer!</div>";
    return false;
  }
  if( in_array( $newNummer, $specialgroups ) ) {
    $problems .= "<div class='warn'>Ung&uuml;ltige Gruppennummer (reserviert fuer Basar oder Muell)</div>";
    return false;
  }
  $id = $newNummer;
  $result = sql_gruppen( array( 'gruppennummer' => $newNummer ) );
  foreach( $result as $row ) {
    if( $row['aktiv'] ) {
      $problems .= "<div class='warn'>Aktive Gruppe der Nummer $newNummer existiert bereits!</div>";
      return false;
    }
    if( $row['id'] >= $id ) {
      $id += 1000;
    }
  }
  return $id;
}

/**
 * Entfernt Gruppenmitglied und verringert den
 * Sockelbetrag entsprechend
 */
function sql_delete_group_member( $gruppenmitglieder_id ) {
  global $problems, $msg, $mysqlheute;

  need( hat_dienst(5), "Nur Dienst 5 darf Personen löschen");

  $daten = sql_gruppenmitglied( $gruppenmitglieder_id );
  need( $daten['aktiv'], "Mitglied nicht aktiv" );
  $gruppen_id = $daten['gruppen_id'];

  sql_update( 'gruppenmitglieder', $gruppenmitglieder_id, array(
    'aktiv' => 0
  , 'diensteinteilung' => 'freigestellt'
  , 'sockeleinlage' => 0.0
  , 'photo_url' => ''
  ) );

  logger( "Gruppenmitglied $gruppenmitglieder_id ({$daten['vorname']}) aus Gruppe {$daten['gruppennummer']} geloescht" );

  // sockelbetrag fuer mitglied rueckerstatten:
  $muell_id = sql_muell_id();
  if( $daten['sockeleinlage'] > 0 ) {
    if( sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => -1, 'gruppen_id' => $muell_id, 'transaktionsart' => TRANSAKTION_TYP_SOCKEL )
    , $daten['sockeleinlage']
    , $mysqlheute
    , "Erstattung Sockeleinlage fuer ausgetretenes Mitglied " . $daten['vorname']
    ) ) {
      $msg = $msg . "<div class='ok'>Aenderung Sockeleinlage ausgetretenes Mitglied: {$daten['sockeleinlage']} Euro wurden erstattet.</div>";
    } else {
      $problems = $problems . "<div class='warn'>Verbuchen Aenderung Sockeleinlage fehlgeschlagen: " . mysqli_error($db_handle) . "</div>";
    }
  }

  // falls letztes mitglied der gruppe ausgetreten: sockelbetrag der Gruppe rueckerstatten:
  $gruppendaten = sql_gruppe( $gruppen_id );
  if( ( $gruppendaten['mitgliederzahl'] == 0 ) and ( $gruppendaten['sockeleinlage_gruppe'] > 0 ) ) {
    if( sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => -1, 'gruppen_id' => $muell_id, 'transaktionsart' => TRANSAKTION_TYP_SOCKEL )
    , $gruppendaten['sockeleinlage_gruppe']
    , $mysqlheute
    , "Erstattung Sockeleinlage Gruppe " . $gruppendaten['name']
    ) ) {
      $msg = $msg . "<div class='ok'>Aenderung Sockeleinlage Gruppe: {$gruppendaten['sockeleinlage']} Euro wurden erstattet.</div>";
      sql_update( 'bestellgruppen', $gruppen_id, array( 'sockeleinlage' => 0.0 ) );
    } else {
      $problems = $problems . "<div class='warn'>Verbuchen Aenderung Sockeleinlage fehlgeschlagen: " . mysqli_error($db_handle) . "</div>";
    }
  }

  // bevorstehende dienste abklaeren:
  if( $gruppendaten['mitgliederzahl'] > 0 ) {
    $bevorstehende_dienste= sql_dienste( "( lieferdatum >= $mysqlheute ) and ( gruppenmitglieder_id = $gruppenmitglieder_id )" );
    if( $gruppendaten['mitgliederzahl'] == 1 ) {
      // bei nur noch einem Mitglied ist klar, wer die Dienste abkriegt:
      $mitglied = current( sql_gruppe_mitglieder( $gruppen_id ) );
      $m_id = $mitglied['gruppenmitglieder_id'];
    } else {
      // dienst bleibt bei gruppe, aber Mitglied muss noch abgesprochen werden:
      $m_id = 0;
    }
    foreach( $bevorstehende_dienste as $dienst ) {
      sql_update( 'dienste', $dienst['id'], array(
        'gruppenmitglieder_id' => $m_id
      , 'status' => 'Vorgeschlagen'
      ) );
    }
  } else {
    // gruppe jetzt ganz inaktiv, also: alle dienste werden offen:
    $bevorstehende_dienste = sql_dienste( "( lieferdatum >= $mysqlheute ) and ( dienste.gruppen_id = $gruppen_id ) and not geleistet" );
    foreach( $bevorstehende_dienste as $dienst ) {
      sql_dienst_wird_offen( $dienst['id'] );
    }
  }
}

/**
 * Legt neues Gruppenmitglied an und erhöht den Sockelbetrag entsprechend
 * Argumente:
 * Vorname, Name, Mail, Telefon und Diensteinteilung des Neumitgliedes
 */
function sql_insert_group_member($gruppen_id, $newVorname, $newName, $newMail, $newTelefon, $newDiensteinteilung){
  global $problems, $msg, $sockelbetrag_mitglied, $sockelbetrag_gruppe, $muell_id, $mysqlheute;
  need( isset( $sockelbetrag_mitglied ), "leitvariable sockelbetrag_mitglied nicht gesetzt!" );
  need( isset( $sockelbetrag_gruppe ), "leitvariable sockelbetrag_gruppe nicht gesetzt!" );

  $muell_id = sql_muell_id();
  $id = sql_insert( 'gruppenmitglieder', array(
    'vorname' => $newVorname
  , 'name' => $newName
  , 'gruppen_id' => $gruppen_id
  , 'email' => $newMail
  , 'telefon' => $newTelefon
  , 'diensteinteilung' => $newDiensteinteilung
  , 'sockeleinlage' => $sockelbetrag_mitglied
  , 'aktiv' => 1
  ) );
  sql_update( 'gruppenmitglieder', $id, array( 'rotationsplanposition' => $id ) );
  logger( "neues Gruppenmitglied $id ($newVorname) in Gruppe $gruppen_id angelegt" );

  $gruppendaten = sql_gruppe( $gruppen_id );

  // sockelbetrag fuer mitglied verbuchen:
  if( $sockelbetrag_mitglied > 0 ) {
    if( sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => $muell_id, 'transaktionsart' => TRANSAKTION_TYP_SOCKEL )
    , array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , $sockelbetrag_mitglied
    , $mysqlheute
    , "Sockeleinlage fuer neues Mitglied $newVorname"
    ) ) {
      $msg = $msg . "<div class='ok'>Aenderung Sockelbetrag neues Mitglied: $sockelbetrag_mitglied Euro wurden verbucht.</div>";
    } else {
      $problems .= "<div class='warn'>Verbuchen Sockelbetrag fehlgeschlagen: " . mysqli_error($db_handle) . "</div>";
    }
  }
  // falls erstes mitglied der gruppe: sockelbetrag fuer ganze gruppe verbuchen:
  if( $sockelbetrag_gruppe > 0 ) {
    if( $gruppendaten['mitgliederzahl'] == 1 ) {
      if( sql_doppelte_transaktion(
        array( 'konto_id' => -1, 'gruppen_id' => $muell_id, 'transaktionsart' => TRANSAKTION_TYP_SOCKEL )
      , array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
      , $sockelbetrag_gruppe
      , $mysqlheute
      , "Sockeleinlage fuer Gruppe " . $gruppendaten['name']
      ) ) {
        $msg = $msg . "<div class='ok'>Aenderung Sockeleinlage Gruppe: $sockelbetrag_gruppe Euro wurden verbucht.</div>";
        sql_update( 'bestellgruppen', $gruppen_id, array( 'sockeleinlage' => $sockelbetrag_gruppe ) );
      } else {
        $problems .= "<div class='warn'>Verbuchen Sockeleinlage fehlgeschlagen: " . mysqli_error($db_handle) . "</div>";
      }
    }
  }

  if( $gruppendaten['mitgliederzahl'] == 1 ) {
    $bevorstehende_dienste= sql_dienste( "( lieferdatum >= $mysqlheute ) and ( dienste.gruppen_id = $gruppen_id )" );
    foreach( $bevorstehende_dienste as $dienst ) {
      sql_update( 'dienste', $dienst['dienst_id'], array(
        'gruppenmitglieder_id' => $id
      , 'status' => 'Vorgeschlagen'
      ) );
    }
  }
}

/**
 * Legt neue Gruppe an
 * macht aber erst ein paar checks
 *
 * Bitte zuvor $problems und $msg initialisieren
 *
 * Wenn etwas nicht stimmt, ist $problems gesetzt und
 * die Funktion gibt false zurüc; bei Erfolg die neue $gruppen_id
 *
 * $msg könnte auch Hinweise enthalten
 */
function sql_insert_group($newNumber, $newName, $pwd) {
  global $problems, $msg;

  $new_id = check_new_group_nr( $newNumber, $problems ) ;

  if ($newName == "")
    $problems = $problems . "<div class='warn'>Die neue Bestellgruppe muss einen Name haben!</div>";

  if( $new_id > 0 and ! $problems ) {
    logger( "neue Gruppe $new_id ($newName) angelegt" );
    $id = sql_insert( 'bestellgruppen', array(
      'id' => $new_id
    , 'aktiv' => 1
    , 'sockeleinlage' => 0.0  // wird erst bei Eintrag erstes Mitglied verbucht
    , 'name' => $newName
    ) );
    if( $id !== FALSE ) { // bestellgruppen hat kein AUTO_INCREMENT: mysqli_insert_id() == 0 bei Erfolg!
      set_password( $new_id, $pwd );
      return $new_id;
    } else {
      return FALSE;
    }
  } else {
    return FALSE;
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
  return mysql2array( doSql( select_lieferanten( $id ) ) );
}

function sql_lieferant( $id ) {
  return sql_select_single_row( select_lieferanten( $id ) );
}

function sql_lieferant_name($id){
  return sql_select_single_field( select_lieferanten( $id ) , 'name' );
}

function optionen_lieferanten( $selected = false, $option_0 = false ) {
  $output = "";
  if( $option_0 ) {
    $output = "<option value='0'";
    if( $selected == 0 ) {
      $output .= " selected";
      $selected = -1;
    }
    $output .= ">$option_0</option>";
  }
  foreach( sql_lieferanten() as $lieferant ) {
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

function sql_references_lieferant( $lieferanten_id ) {
  return sql_count( 'gesamtbestellungen', "lieferanten_id=$lieferanten_id" );
}

function sql_delete_lieferant( $lieferanten_id ) {
  $name = sql_lieferant_name( $lieferanten_id );
  need( sql_references_lieferant( $lieferanten_id ) == 0, 'Bestellungen vorhanden: Lieferant $name kann nicht gelöpscht werden!' );
  need( abs( lieferantenkontostand( $lieferanten_id )) < 0.01
    , 'Lieferantenkonto nicht ausgeglichen: Lieferant $name kann nicht gelöpscht werden!' );
  doSql(
    "DELETE FROM lieferanten WHERE id=$lieferanten_id"
  , LEVEL_IMPORTANT, "Loeschen des Lieferanten fehlgeschlagen"
  );
  logger( "Lieferant $lieferanten_id geloescht" );
}

function sql_lieferant_offene_bestellungen( $lieferanten_id ) {
  return mysql2array( doSql( "
    SELECT gesamtbestellungen.name
      FROM gesamtbestellungen
     WHERE ( lieferanten.id = $lieferanten_id )
       AND ( rechnungsstatus < ".STATUS_ABGERECHNET." )
  " ) );
}

function sql_lieferant_katalogeintraege( $lieferanten_id ) {
  $lieferant = sql_lieferant( $lieferanten_id );
  $katalogformat = $lieferant['katalogformat'];
  if( ( $katalogformat == 'keins' ) || ( $katalogformat == '' ) ) {
    return 0;
  }
  return sql_count( 'lieferantenkatalog'
                  , "(lieferanten_id = $lieferanten_id) and (katalogformat = '$katalogformat')" );
}


////////////////////////////////////
//
// funktionen fuer produkte und produktgruppen
//
////////////////////////////////////


function query_produkte( $op, $keys = array(), $using = array(), $orderby = false ) {
  $have_price = false;

  $selects = array();
  $filters = array();
  $joins = need_joins_array( $using, array(
    'produktgruppen' => 'produktgruppen.id = produkte.produktgruppen_id'
  , 'lieferanten' => 'lieferanten.id = produkte.lieferanten_id'
  ) );

  $selects[] = 'produkte.id as produkt_id';
  $selects[] = 'produkte.artikelnummer';
  $selects[] = 'produkte.name as name';
  $selects[] = 'produkte.lieferanten_id';
  $selects[] = 'produkte.produktgruppen_id';
  $selects[] = 'produkte.notiz';
  $selects[] = 'produkte.dauerbrenner';
  $selects[] = 'produktgruppen.name as produktgruppen_name';
  $selects[] = 'produktgruppen.id as produktgruppen_id';
  $selects[] = 'lieferanten.name as lieferant_name';

  foreach( $keys as $key => $cond ) {
    switch( $key ) {
      case 'id':
      case 'produkt_id':
        $filters['produkte.id'] = $cond;
        break;
      case 'anummer':
      case 'artikelnummer':
        $filters['produkte.artikelnummer'] = $cond;
        break;
      case 'lieferant_id':
      case 'lieferanten_id':
        $filters['lieferanten.id'] = $cond;
        break;
      case 'bestell_id':
      case 'gesamtbestellung_id':
        if( $cond ) {
          $joins['bestellvorschlaege'] = 'bestellvorschlaege.produkt_id = produkte.id';
          $joins['gesamtbestellungen'] = 'gesamtbestellungen.id = bestellvorschlaege.gesamtbestellung_id';
          $joins['produktpreise']
            = '(produktpreise.id = bestellvorschlaege.produktpreise_id) and (produktpreise.produkt_id = produkte.id)';
          $have_price = true;
          $filters['gesamtbestellungen.id'] = $cond;
          $selects[] = 'bestellvorschlaege.liefermenge';
          $selects[] = 'bestellvorschlaege.produktpreise_id';
          $selects[] = 'gesamtbestellungen.aufschlag_prozent';
          $selects[] = 'gesamtbestellungen.name as gesamtbestellung_name';
        }
        break;
      case 'preis_id':
        if( $cond ) {
          $joins['produktpreise'] = 'produktpreise.produkt_id = produkte.id';
          $filters['produktpreise.id'] = $cond;
          $have_price = true;
        }
        break;
      case 'price_on_date_or_null':
        if ($cond) {
          $price_select = select_current_productprice_id('produkte.id', $cond);
          $joins[] = "LEFT OUTER JOIN produktpreise ON "
              . 'produktpreise.produkt_id = produkte.id '
              . "AND produktpreise.id = ($price_select)";
          $have_price = true;
        }
        break;
      case 'price_on_date':
        if ($cond) {
          $price_select = select_current_productprice_id('produkte.id', $cond);
          $joins['produktpreise'] = 'produktpreise.produkt_id = produkte.id '
              . "AND produktpreise.id = ($price_select)";
          $have_price = true;
        }
        break;
      case 'not_in_order':
        if ($cond) {
          $order_products_select = "SELECT produkt_id FROM bestellvorschlaege WHERE gesamtbestellung_id = '$cond'";
          $filters['produkte.id'] = "!= ALL ($order_products_select)";
        }
        break;
      default:
          error( "undefined key: $key" );
    }
  }
  if( $have_price ) {
    $selects[] = 'produktpreise.liefereinheit';
    $selects[] = 'produktpreise.verteileinheit';
    $selects[] = 'produktpreise.lv_faktor';
    $selects[] = 'produktpreise.mwst';
    $selects[] = 'produktpreise.pfand';
    $selects[] = 'produktpreise.gebindegroesse';
    $selects[] = 'produktpreise.bestellnummer';
    $selects[] = 'produktpreise.zeitstart';
    $selects[] = 'produktpreise.zeitende';
    $selects[] = 'produktpreise.lieferpreis';
    $selects[] = 'produktpreise.id as preis_id';
  }
  if( $using ) {
    is_array( $using ) or ( $using = array( $using ) );
    foreach( $using as $table ) {
      switch( $table ) {
        case 'gesamtbestellungen':
          $joins['bestellvorschlaege'] = 'bestellvorschlaege.produkt_id = produkte.id';
          $filters[] = 'bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id';
          break;
        case 'lieferanten':
          $filters[] = 'produkte.lieferanten_id = lieferanten.id';
          break;
        default:
          error( "Sorry, I have no use for table $table" );
      }
    }
  }
  switch( $op ) {
    case 'SELECT':
      break;
    case 'COUNT':
      $op = 'SELECT';
      $selects = 'COUNT(*) as anzahl';
      break;
    default:
      error( "undefined op: $op" );
  }
  return get_sql_query( $op, 'produkte', $selects, $joins, $filters, $orderby );
}

function select_produkte( $keys = array(), $using = array(), $orderby = false ) {
  return query_produkte( 'SELECT', $keys, $using, $orderby );
}

function select_produkte_anzahl( $keys = array(), $using = array() ) {
  return query_produkte( 'COUNT', $keys, $using );
}

function sql_produkte( $keys = array(), $orderby = 'produktgruppen.name, produktgruppen.id, produkte.name' ) {
  $r = mysql2array( doSql( select_produkte( $keys, array(), $orderby ) ) );
  foreach( $r as & $p ) {
    if( isset( $p['preis_id'] ) ) {
      $p = preisdatenSetzen( $p );
    }
  }
  return $r;
}
function sql_produkt( $keys = array(), $allow_null = false ) {
  if( is_numeric( $keys ) )
    $keys = array( 'produkt_id' => $keys );
  $p = sql_select_single_row( select_produkte( $keys ), $allow_null );
  if( $p and isset( $p['preis_id'] ) ) {
    $p = preisdatenSetzen( $p );
  }
  // foreach( $p as $k => $v ) {
  //  open_div( '', '', "$k: [$v]" );
  //}
  return $p;
}

function sql_produkte_anzahl( $keys = array() ) {
  return sql_select_single_field( select_produkte_anzahl( $keys ), 'anzahl' );
}

function references_produkt( $produkt_id ) {
  return sql_count( 'bestellvorschlaege', "produkt_id=$produkt_id" )
       + sql_count( 'bestellzuordnung', "produkt_id=$produkt_id" );
}

function sql_delete_produkt( $produkt_id ) {
  $count = references_produkt( $produkt_id );
  need( $count == 0, 'Produkteintrag nicht löschbar, da in Bestellungen oder -vorlagen benutzt!' );
  doSql( "DELETE FROM produktpreise WHERE produkt_id=$produkt_id" );
  doSql( "DELETE FROM produkte WHERE id=$produkt_id" );
}


function sql_produktgruppen(){
  return mysql2array( doSql( "SELECT * FROM produktgruppen ORDER BY name"
  , LEVEL_ALL, "Konnte Produktgruppen nicht aus DB laden.." ) );
}

function references_produktgruppe( $produktgruppen_id ) {
  return sql_count( 'produkte', "produktgruppen_id = $produktgruppen_id" );
}

function optionen_produktgruppen( $selected = 0 ) {
  $output = "";
  foreach( sql_produktgruppen() as $pg ) {
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

function sql_produktgruppen_name( $id ) {
  return sql_select_single_field( "SELECT name FROM produktgruppen WHERE id=$id" , 'name' );
}

////////////////////////////////////
//
// funktionen fuer gesamtbestellung, bestellvorschlaege und gruppenbestellungen:
//
////////////////////////////////////

$wochentage = array( 'invalid', 'Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag' );

define('STATUS_BESTELLEN', 10 );
define('STATUS_LIEFERANT', 20 );
define('STATUS_VERTEILT', 30 );
define('STATUS_ABGERECHNET', 40 );
define('STATUS_ABGESCHLOSSEN', 45 );
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
    case STATUS_ABGESCHLOSSEN:
      return 'abgeschlossen';
    case STATUS_ARCHIVIERT:
      return 'archiviert';
  }
  return "FEHLER: undefinierter Status: $state";
}

function sql_abrechnung_set( $abrechnung_id ) {
  $result = doSql( "SELECT id FROM gesamtbestellungen WHERE abrechnung_id = $abrechnung_id" );
  $r = array();
  while( $row = mysqli_fetch_array( $result ) ) {
    $r[] = $row['id'];
  }
  return $r;
}

function sql_bestellung_status($bestell_id){
  return sql_select_single_field( "SELECT rechnungsstatus FROM gesamtbestellungen WHERE id=$bestell_id", 'rechnungsstatus' );
}

function sql_bestellung_name($bestell_id){
  return sql_select_single_field( "SELECT name FROM gesamtbestellungen WHERE id=$bestell_id", 'name' );
}

function sql_bestellung_lieferant_id($bestell_id){
  if( is_array( $bestell_id ) )           // usually: result of sql_abrechnung_set()
    $bestell_id = current( $bestell_id );
  return sql_select_single_field( "SELECT lieferanten_id FROM gesamtbestellungen WHERE id=$bestell_id", 'lieferanten_id' );
}

/**
 *  sql_change_bestellung_status:
 *   - fuehrt erlaubte Statusaenderungen einer Bestellung aus
 *   - ggf. werden Nebenwirkungen, wie verteilmengenZuweisen, ausgeloest
 */
function sql_change_bestellung_status( $bestell_id, $state ) {
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
      // $changes .= ", lieferung=NOW()";   // TODO: eingabe erlauben?
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
    case STATUS_ABGERECHNET . "," . STATUS_ARCHIVIERT:
      // TODO: tests:
      //   - bezahlt?
      //   - basarreste?
      break;
    default:
      error( "Ungültiger Statuswechsel" );
      return false;
  }
  logger( "statuswechsel Bestellung $bestell_id: $current, $state" );
  $sql = "UPDATE gesamtbestellungen SET $changes WHERE id = $bestell_id";
  $result = doSql($sql, LEVEL_KEY, "Konnte status der Bestellung nicht ändern..");
  if( $result ) {
    if( $do_verteilmengen_zuweisen ) {
      verteilmengenZuweisen( $bestell_id );
      // befriedigender waere, vormerkungen erst bei lieferung zu loeschen - das kann aber
      // eventuell _nach_ erstellung der naechsten bestellvorlage sein; wir muessen also
      // schon hier loeschen:
      vormerkungenLoeschen( $bestell_id );
    }
  }
  return $result;
}

function sql_bestellungen( $filter = 'true', $orderby = 'rechnungsstatus, abrechnung_id, bestellende DESC, name' ) {
  return mysql2array( doSql( "
    SELECT gesamtbestellungen.*
         , dayofweek( lieferung ) as lieferdatum_dayofweek
         , DATE_FORMAT( lieferung, '%d.%m.%Y') AS lieferdatum_trad
         , lieferanten.name as lieferantenname FROM gesamtbestellungen
    JOIN lieferanten on lieferanten.id = gesamtbestellungen.lieferanten_id
    WHERE $filter ORDER BY $orderby
  " ) );
}

function sql_bestellung( $bestell_id ) {
  $r = sql_bestellungen( "gesamtbestellungen.id = $bestell_id" );
  need( count($r) == 1 );
  return current($r);
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

function select_gesamtbestellungen_unverbindlich() {
  return "
    SELECT * FROM gesamtbestellungen
    WHERE rechnungsstatus < " . STATUS_LIEFERANT;
}

/**
 *  Gesamtbestellung einfügen
 */
function sql_insert_bestellung( $name, $startzeit, $endzeit, $lieferung, $lieferanten_id, $aufschlag_prozent ) {
  nur_fuer_dienst(4);
  $id = sql_insert( 'gesamtbestellungen', array(
    'name' => $name, 'bestellstart' => $startzeit, 'bestellende' => $endzeit
  , 'lieferung' => $lieferung, 'lieferanten_id' => $lieferanten_id
  , 'aufschlag_prozent' => $aufschlag_prozent
  , 'rechnungsstatus' => STATUS_BESTELLEN
  ) );
  sql_update( 'gesamtbestellungen', $id, array( 'abrechnung_id' => $id ) );
  return $id;
}

function sql_update_bestellung( $name, $startzeit, $endzeit, $lieferung, $bestell_id, $aufschlag_prozent ) {
  nur_fuer_dienst(4);
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET, "Aenderung nicht moeglich: Bestellung ist bereits abgerechnet!" );
  return sql_update( 'gesamtbestellungen', $bestell_id, array(
    'name' => $name, 'bestellstart' => $startzeit, 'bestellende' => $endzeit, 'lieferung' => $lieferung
  , 'aufschlag_prozent' => $aufschlag_prozent
  ) );
}

/**
 *  Bestellvorschlag einfuegen
 */
function sql_insert_bestellvorschlag( $produkt_id , $gesamtbestellung_id, $preis_id = 0, $gruppen_id = 0 ) {
  fail_if_readonly();
  need( sql_bestellung_status( $gesamtbestellung_id ) < STATUS_ABGERECHNET, "Änderung nicht moeglich: Bestellung ist bereits abgerechnet!" );

  // finde NOW() aktuellen preis:
  if( ! $preis_id )
    $preis_id = sql_aktueller_produktpreis_id( $produkt_id );

  // kludge alert: finde erstmal irgendeinen preis...
  if( ! $preis_id )
    if( hat_dienst(4) )
      $preis_id = sql_aktueller_produktpreis_id( $produkt_id, false );

  if( ! $preis_id ) {
    error( "Eintrag Bestellvorschlag fehlgeschlagen: kein Preiseintrag gefunden!" );
    return false;
  }
  return doSql( "
    INSERT INTO bestellvorschlaege
      (produkt_id, gesamtbestellung_id, produktpreise_id, liefermenge )
    VALUES ($produkt_id, $gesamtbestellung_id, $preis_id, 0 )
    ON DUPLICATE KEY UPDATE produktpreise_id = $preis_id
  ", LEVEL_IMPORTANT, "Konnte Bestellvorschlag nicht aufnehmen."
  );
}

function sql_delete_bestellvorschlag( $produkt_id, $bestell_id ) {
  need( sql_bestellung_status( $bestell_id ) == STATUS_BESTELLEN, "Loeschen von Bestellvorschlaegen nur in der Bestellzeit!" );
  sql_delete_bestellzuordnungen( array( 'produkt_id' => $produkt_id, 'bestell_id' => $bestell_id ) );
  doSql( "
    DELETE FROM bestellvorschlaege
    WHERE produkt_id = $produkt_id AND gesamtbestellung_id = $bestell_id
  " );
}

function sql_references_gesamtbestellung( $bestell_id ) {
  return sql_select_single_field( " SELECT (
     ( SELECT count(*) FROM bestellvorschlaege WHERE gesamtbestellung_id = $bestell_id )
   + ( SELECT count(*) FROM gruppenbestellungen WHERE gesamtbestellung_id = $bestell_id ) 
  ) as count
  " , 'count'
  );
}


function sql_insert_gruppenbestellung( $gruppe, $bestell_id ){
  need( sql_gruppe_aktiv( $gruppe ) or ($gruppe == sql_muell_id()) or ($gruppe == sql_basar_id())
      , "sql_insert_gruppenbestellung: keine aktive Bestellgruppe angegeben!" );
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGESCHLOSSEN, "Aenderung nicht mehr moeglich: Bestellung ist abgeschlossen!" );
  return sql_insert( 'gruppenbestellungen'
  , array( 'bestellgruppen_id' => $gruppe , 'gesamtbestellung_id' => $bestell_id )
  , array(  /* falls schon existiert: -kein fehler -nix updaten -id zurueckgeben */  )
  );
}


////////////////////////////////////
//
// funktionen fuer bestellmengen und verteil/liefermengen
//
////////////////////////////////////

// werte fuer feld `art' in bestellzuordnung:
//
define( 'BESTELLZUORDNUNG_ART_VORMERKUNG_FEST', 10 );
define( 'BESTELLZUORDNUNG_ART_VORMERKUNG_TOLERANZ', 11 );
define( 'BESTELLZUORDNUNG_ART_FESTBESTELLUNG', 20 );
define( 'BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG', 21 );
define( 'BESTELLZUORDNUNG_ART_ZUTEILUNG', 30 );

define( 'BESTELLZUORDNUNG_ART_VORMERKUNGEN', 'BETWEEN 10 AND 19' );
define( 'BESTELLZUORDNUNG_ART_BESTELLUNGEN', 'BETWEEN 20 AND 29' );
define( 'BESTELLZUORDNUNG_ART_ZUTEILUNGEN', 'BETWEEN 30 AND 39' );

define( 'BESTELLZUORDNUNG_ART_ANY', 'BETWEEN 1 AND 99' );

// todo: basarzuteilungen unterscheiden:
// define( 'BESTELLZUORDNUNG_ART_ZUTEILUNG_BASAR', 31 );


function query_bestellzuordnungen( $op, $keys = array(), $using = array(), $orderby = false ) {
  $selects = array();
  $filters = array();
  $joins = need_joins_array( $using, array(
    'gruppenbestellungen' => 'bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id'
  ) );

  $selects[] = 'bestellzuordnung.produkt_id';
  $selects[] = 'bestellzuordnung.menge';
  $selects[] = 'bestellzuordnung.art';
  $selects[] = 'bestellzuordnung.zeitpunkt';
  $selects[] = 'bestellzuordnung.id AS bestellzuordnung_id';
  $selects[] = 'gruppenbestellungen.bestellgruppen_id';
  $selects[] = 'gruppenbestellungen.gesamtbestellung_id';

  foreach( $keys as $key => $cond ) {
    switch( $key ) {
      case 'gruppen_id':
        $filters['gruppenbestellungen.bestellgruppen_id'] = $cond;
        break;
      case 'gesamtbestellung_id':
      case 'bestell_id':
        $filters['gruppenbestellungen.gesamtbestellung_id'] = $cond;
        break;
      case 'produkt_id':
        $filters['bestellzuordnung.produkt_id'] = $cond;
        break;
      case 'gruppenbestellung_id':
        $filters['gruppenbestellungen.id'] = $cond;
        break;
      case 'art':
        $filters['bestellzuordnung.art'] = $cond;
        break;
      case 'lieferanten_id':
      case 'lieferant_id':
        $joins['gesamtbestellungen'] = 'gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id';
        $filters['gesamtbestellungen.lieferanten_id'] = $cond;
        break;
      default:
        error( "undefined key: $key" );
    }
  }
  if( $using ) {
    is_array( $using ) or ( $using = array( $using ) );
    foreach( $using as $table ) {
      switch( $table ) {
        case 'produkte':
          $filters[] = 'bestellzuordnung.produkt_id = produkte.id';
          break;
        case 'gruppenbestellungen':
          $filters[] = 'bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id';
          break;
        default:
          error( "Sorry, I have no use for table $table" );
      }
    }
  }
  switch( $op ) {
    case 'SELECT':
      if( $using ) {
        // in a scalar subquery, the only field that makes sense is `menge':
        $selects = 'bestellzuordnung.menge';
      }
      break;
    case 'DELETE':
      $selects = 'bestellzuordnung.*'; // override selects from above!
      break;
    case 'SUM':
      $op = 'SELECT';
      $selects = 'IFNULL( SUM( bestellzuordnung.menge ), 0 ) AS menge';
      break;
    default:
      error( "undefined op: $op" );
  }
  return get_sql_query( $op, 'bestellzuordnung', $selects, $joins, $filters, $orderby );
}

function select_bestellzuordnungen( $keys = array(), $using = array(), $orderby = 'bestellzuordnung.zeitpunkt' ) {
  return query_bestellzuordnungen( 'SELECT', $keys, $using, $orderby );
}

function sql_bestellzuordnungen( $keys = array(), $orderby = 'bestellzuordnung.zeitpunkt' ) {
  return mysql2array( doSql( select_bestellzuordnungen( $keys, array(), $orderby ) ) );
}

function sql_delete_bestellzuordnungen( $keys = array() ) {
  return doSql( query_bestellzuordnungen( 'DELETE', $keys ) );
}


function select_bestellzuordnung_menge( $keys = array(), $using = array() ) {
  return query_bestellzuordnungen( 'SUM', $keys, $using );
}

function sql_bestellzuordnung_menge( $keys = array() ) {
  return sql_select_single_field( select_bestellzuordnung_menge( $keys ), 'menge' );
}


// select_bestellung_produkte():
// liefert fuer ein oder alle produkte einer bestellung (group by produkt):
// - produktdaten und preise (preisdatenSetzen() sollte zusaetzlich aufgerufen werden)
// - gesamtbestellmenge
// - festbestellmenge (gruppen _und_ basar)
// - toleranzbestellmenge: alle toleranzebestellungen _ohne_ basar
// - basarbestellmenge: _toleranzbestellungen_ des basars
// - verteilmenge _ohne_ muellgruppe (nicht sinnvoll fuer basar: liefert nicht den basarbestand!)
// - muellmenge: zuteilung an muell-gruppe
// $gruppen_id = 0: summe aller gruppen
// $gruppen_id != 0: nur fuer diese gruppe (muell*, basar* sind dann nicht sinnvoll)
//
function select_bestellung_produkte( $bestell_id, $produkt_id = 0, $gruppen_id = 0, $orderby = '' ) {
  $basar_id = sql_basar_id();
  $muell_id = sql_muell_id();

  // if( is_array( $bestell_id ) ) {
  //  $state = sql_bestellung_status( $bestell_id[0] );
  //  $bestell_id_filter = ' gesamtbestellungen.id IN ';
  //  $komma = '(';
  //  foreach( $bestell_id as $b_id ) {
  //    $bestell_id_filter .= "$komma $b_id";
  //    $komma = ',';
  //  }
  //  $bestell_id_filter .= ')';
  //  $bestell_id_filter = ' gesamtbestellungen.id IN ( 11, 20 ) ';
  // } else {
    $state = sql_bestellung_status( $bestell_id );
    $bestell_id_filter = " gesamtbestellungen.id = $bestell_id";
  // }

  // zur information, vor allem im "vorlaeufigen Bestellschein", auch Bestellmengen berechnen:
  $gesamtbestellmenge_expr = "ifnull( sum( IF( (bestellzuordnung.art ".BESTELLZUORDNUNG_ART_BESTELLUNGEN."), bestellzuordnung.menge, 0 ) ), 0 )";
  $festbestellmenge_expr = "ifnull( sum( IF( (bestellzuordnung.art = ".BESTELLZUORDNUNG_ART_FESTBESTELLUNG."), bestellzuordnung.menge, 0 ) ), 0 )";

  // basarbestellmenge: _eigentliche_ basarbestellungen sind TOLERANZBESTELLUNG,
  // basar mit FESTBESTELLUNG zaehlt wie gewoehnliche festmenge!
  $basarbestellmenge_expr = "
    ifnull( sum( IF( (bestellzuordnung.art = ".BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG.") and (gruppenbestellungen.bestellgruppen_id = $basar_id)
                      , bestellzuordnung.menge, 0 ) ), 0 )
  ";
  $toleranzbestellmenge_expr = "
    ifnull( sum( IF( (bestellzuordnung.art = ".BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG.") and (gruppenbestellungen.bestellgruppen_id != $basar_id)
                      , bestellzuordnung.menge, 0 ) ), 0 )
  ";
  if( $gruppen_id != $basar_id ) {
    $verteilmenge_expr = "
     ifnull( sum( IF( (bestellzuordnung.art ".BESTELLZUORDNUNG_ART_ZUTEILUNGEN.") and (gruppenbestellungen.bestellgruppen_id != $muell_id)
                      , bestellzuordnung.menge, 0 ) ), 0 )
    ";
    $muellmenge_expr = "
     ifnull( sum( IF( (bestellzuordnung.art ".BESTELLZUORDNUNG_ART_ZUTEILUNGEN.") and (gruppenbestellungen.bestellgruppen_id = $muell_id)
                      , bestellzuordnung.menge, 0 ) ), 0 )
    ";
  } else {
    // funktioniert nicht fuer basar (als Warnungen: Werte nicht benutzen!
    $verteilmenge_expr = 999999;
    $muellmenge_expr = 999999;
  }

  if( $orderby == '' )
    $orderby = "menge_ist_null, produktgruppen.name, produkte.name";

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
        switch( $gruppen_id ) {
          case $basar_id:
            $firstorder_expr = "liefermenge";
            break;
          case $muell_id:
            $firstorder_expr = $muellmenge_expr;
            break;
          default:
            $firstorder_expr = $verteilmenge_expr;
            break;
        }
      else
        $firstorder_expr = "liefermenge";
      break;
  }

  return "SELECT
      produkte.name as produkt_name
    , produktgruppen.name as produktgruppen_name
    , produktgruppen.id as produktgruppen_id
    , produkte.id as produkt_id
    , produkte.notiz as notiz
    , bestellvorschlaege.liefermenge  as liefermenge
    , bestellvorschlaege.gesamtbestellung_id as gesamtbestellung_id
    , gesamtbestellungen.aufschlag_prozent as aufschlag_prozent
    , produktpreise.liefereinheit as liefereinheit
    , produktpreise.verteileinheit as verteileinheit
    , produktpreise.lv_faktor as lv_faktor
    , produktpreise.gebindegroesse as gebindegroesse
    , produktpreise.lieferpreis as lieferpreis
    , produktpreise.id as preis_id
    , produktpreise.pfand as pfand
    , produktpreise.mwst as mwst
    , produkte.artikelnummer as artikelnummer
    , produktpreise.bestellnummer as bestellnummer
    , ( $gesamtbestellmenge_expr ) as gesamtbestellmenge
    , ( $festbestellmenge_expr ) as festbestellmenge
    , ( $basarbestellmenge_expr ) as basarbestellmenge
    , ( $toleranzbestellmenge_expr ) as toleranzbestellmenge
    , ( $verteilmenge_expr ) as verteilmenge
    , ( $muellmenge_expr ) as muellmenge
    , IF( abs($firstorder_expr) > 0, 0, 1 ) as menge_ist_null
    FROM bestellvorschlaege
    INNER JOIN produkte
      ON (produkte.id=bestellvorschlaege.produkt_id)
    INNER JOIN produktpreise
      ON (produktpreise.id=bestellvorschlaege.produktpreise_id)
    INNER JOIN produktgruppen
      ON (produktgruppen.id=produkte.produktgruppen_id)
    INNER JOIN gesamtbestellungen
      ON (gesamtbestellungen.id = bestellvorschlaege.gesamtbestellung_id)
    LEFT JOIN gruppenbestellungen
      ON (gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id)
    LEFT JOIN bestellzuordnung
      ON (bestellzuordnung.produkt_id=bestellvorschlaege.produkt_id
         AND bestellzuordnung.gruppenbestellung_id=gruppenbestellungen.id)
    WHERE ( $bestell_id_filter )
    " . ( $gruppen_id ? " and gruppenbestellungen.bestellgruppen_id=$gruppen_id " : "" )
      . ( $produkt_id ? " and produkte.id=$produkt_id " : "" )
    . "
    GROUP BY produkte.id
    ORDER BY $orderby
  ";
}

function sql_bestellung_produkte( $bestell_id, $produkt_id = 0, $gruppen_id = 0, $orderby = '' ) {
  $result = doSql( select_bestellung_produkte( $bestell_id, $produkt_id, $gruppen_id, $orderby ), LEVEL_KEY );
  $r = mysql2array( $result );
  foreach( $r as $key => $val )
    $r[ $key ] = preisdatenSetzen( $val );
  return $r;
}




/*  preisdaten setzen:
 *  berechnet und setzt einige weitere nuetzliche eintraege einer 'produktpreise'-Zeile:
 *   - kan_verteileinheit, kan_verteilmult, kan_liefereinheit, kan_liefermult:
 *     kanonische einheiten (masszahl abgespalten, einheit wie in global $masseinheiten)
 *   - liefereinheit_anzeige, verteileinheit_anzeige:
 *     alternative darstellung fuer bildschirmanzeige (kg und L statt g und ml bei grossen masszahlen)
 *   - kan_{liefer,verteil}einheit_anzeige, kan_{liefer,verteil}mult_anzeige:
 *     dito, zerlegt in masszahl und einheit
 *   - nettolieferpreis, bruttolieferpreis: preise pro L-Einheit
 *   - nettopreis, bruttopreis: preise pro V-Einheit
 *   - endpreis:  bruttopreis plus pfand
 *   - lv_faktor (wird berechnet wenn moeglich, sonst aus datenbank entnommen)
 *   - preisaufschlag: aufschlag pro V-Einheit (berechnet als prozentsatz vom nettolieferpreis)
 */
function preisdatenSetzen( $pr /* a row from produktpreise */ ) {

  // kanonische masseinheiten setzen (gross/kleinschreibung, 1 space zwischenraum, kg -> g, ...)
  //
  list( $m, $e ) = kanonische_einheit( $pr['verteileinheit'] );
  $pr['kan_verteilmult'] = $m;
  $pr['kan_verteileinheit'] = $e;
  $pr['verteileinheit'] = "$m $e";
  // fuer anzeige ggf groessere einheiten waehlen:
  switch( $e ) {
    case 'g':
      if( $m >= 1000 and ( $m % 100 == 0 ) ) {
        $e = 'kg';
        $m /= 1000.0;
      }
      break;
    case 'ml':
      if( $m >= 1000 and ( $m % 100 == 0 ) ) {
        $e = 'l';
        $m /= 1000.0;
      }
      break;
    default:
  }
  $pr['verteileinheit_anzeige'] = mult2string( $m ) . " $e";
  $pr['kan_verteileinheit_anzeige'] = $e;
  $pr['kan_verteilmult_anzeige'] = $m;

  list( $m, $e ) = kanonische_einheit( $pr['liefereinheit'] );
  $pr['kan_liefermult'] = $m;
  $pr['kan_liefereinheit'] = $e;
  $pr['liefereinheit'] = "$m $e";
  switch( $e ) {
    case 'g':
      if( $m >= 1000 and ( $m % 100 == 0 ) ) {
        $e = 'kg';
        $m /= 1000.0;
      }
      break;
    case 'ml':
      if( $m >= 1000 and ( $m % 100 == 0 ) ) {
        $e = 'l';
        $m /= 1000.0;
      }
      break;
    default:
  }
  $pr['liefereinheit_anzeige'] = mult2string( $m ) ." $e";
  $pr['kan_liefereinheit_anzeige'] = $e;
  $pr['kan_liefermult_anzeige'] = $m;

  if( $pr['kan_liefereinheit'] == $pr['kan_verteileinheit'] ) {
    $pr['lv_faktor'] = $pr['kan_liefermult'] / $pr['kan_verteilmult'];
  } else {
    need( $pr['lv_faktor'] > 0, "L-V-Faktor unbekannt: kann nicht zwischen verteileinheit und Liefereinheit umrechnen" );
  }

  $pr['nettolieferpreis'] = $pr['lieferpreis'];
  $pr['bruttolieferpreis'] = $pr['lieferpreis'] * ( 1.0 + $pr['mwst'] / 100.0 );

  // Preise je V-Einheit:
  $pr['nettopreis'] = $pr['nettolieferpreis'] / $pr['lv_faktor'];
  $pr['bruttopreis'] = $pr['bruttolieferpreis'] / $pr['lv_faktor'];
  $pr['vpreis'] = $pr['bruttopreis'] + $pr['pfand'];
  if( isset( $pr['aufschlag_prozent'] ) ) { // needs JOIN gesamtbestellungen
    $pr['lieferpreisaufschlag'] = $pr['nettolieferpreis'] * $pr['aufschlag_prozent'] / 100.0;
    $pr['preisaufschlag'] = $pr['lieferpreisaufschlag'] / $pr['lv_faktor'];
    $pr['endpreis'] = $pr['vpreis'] + $pr['preisaufschlag'];
  }

  return $pr;
}

// zuteilungen_berechnen():
// wo benoetigt, ist sql_bestellung_produkte() schon aufgerufen; zwecks effizienz uebergeben wir der funktion
// eine Ergebniszeile, um den komplexen query in sql_bestellung_produkte() nicht wiederholen zu muessen:
//
function zuteilungen_berechnen( $mengen /* one row from sql_bestellung_produkte */ ) {
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
  $festbestellungen = sql_bestellzuordnungen( array( 'art' => BESTELLZUORDNUNG_ART_FESTBESTELLUNG, 'bestell_id' => $bestell_id, 'produkt_id' => $produkt_id ) );
  $festzuteilungen = array();
  $offen = array();
  foreach( $festbestellungen as $row ) {
    if( $restmenge <= 0 )
      break; // nix mehr da...
    $gruppe = $row['bestellgruppen_id'];
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
  foreach( $festbestellungen as $row ) {
    if( $restmenge <= 0 )
      break;
    $gruppe = $row['bestellgruppen_id'];
    $menge = min( $row['menge'], $offen[$gruppe], $restmenge );
    $festzuteilungen[$gruppe] += $menge;
    $restmenge -= $menge;
    $offen[$gruppe] -= $menge;
  }

  // dritte zuteilungsrunde: mit positiv-toleranzen auffuellen:
  //
  $toleranzzuteilungen = array();
  if( $toleranzbestellmenge > 0 ) {
    $toleranzbestellungen = sql_bestellzuordnungen( array( 'art' => BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG, 'bestell_id' => $bestell_id, 'produkt_id' => $produkt_id ), '-menge' );
    $quote = ( 1.0 * $restmenge ) / $toleranzbestellmenge;
    need( $quote <= 1 );
    foreach( $toleranzbestellungen as $row ) {
      if( $restmenge <= 0 )
        break;
      $gruppe = $row['bestellgruppen_id'];
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
  need( $restmenge == 0, "Fehler beim Verteilen: Rest: $restmenge bei Produkt {$mengen['produkt_name']}" );

  return array( 'bestellmenge' => $bestellmenge, 'gebinde' => $gebinde, 'festzuteilungen' => $festzuteilungen, 'toleranzzuteilungen' => $toleranzzuteilungen );
}


function select_liefermenge( $bestell_id, $produkt_id ) {
  return select_query( 'bestellvorschlaege', 'liefermenge', '', array( "gesamtbestellung_id = $bestell_id", "produkt_id = $produkt_id" ) );
}

function select_verteilmenge( $bestell_id, $produkt_id, $gruppen_id = 0 ) {
  $keys = array( 'art' => BESTELLZUORDNUNG_ART_ZUTEILUNGEN, 'bestell_id' => $bestell_id, 'produkt_id' => $produkt_id );
  if( $gruppen_id ) {
    $keys['gruppen_id'] = $gruppen_id;
  } else {
    $keys['gruppen_id'] = ( '!= ' . sql_muell_id() );
  }
  return select_bestellzuordnung_menge( $keys );
}

function select_muellmenge( $bestell_id, $produkt_id ) {
  return select_verteilmenge( $bestell_id, $produkt_id, sql_muell_id() );
}

function select_basarmenge( $bestell_id, $produkt_id ) {
  return "( SELECT (
               (". select_liefermenge( $bestell_id, $produkt_id ). ")
             - (" .select_verteilmenge( $bestell_id, $produkt_id ). ")
             - (" .select_muellmenge( $bestell_id, $produkt_id ). ")
         ) AS menge )";
}


function sql_liefermenge( $bestell_id, $produkt_id ) {
  return sql_select_single_field( select_liefermenge( $bestell_id, $produkt_id ), 'menge' );
}

function sql_verteilmenge( $bestell_id, $produkt_id, $gruppen_id = 0 ) {
  return sql_select_single_field( select_verteilmenge( $bestell_id, $produkt_id, $gruppen_id ), 'menge' );
}

function sql_muellmenge( $bestell_id, $produkt_id ) {
  return sql_select_single_field( select_muellmenge( $bestell_id, $produkt_id ), 'menge' );
}

function sql_basarmenge( $bestell_id, $produkt_id ) {
  return sql_select_single_field( select_basarmenge( $bestell_id, $produkt_id ), 'menge' );
}



/**
 * select_basar:
 * produkte im basar (differenz aus liefer- und verteilmengen) berechnen:
 */
function select_basar( $bestell_id = 0 ) {
  if( $bestell_id ) {
    $where = "WHERE gesamtbestellungen.id = $bestell_id";
  } else {
    $where = "WHERE gesamtbestellungen.rechnungsstatus < ".STATUS_ABGERECHNET; // todo: change to 'ABGESCHLOSSEN'
  }
  return "
    SELECT produkte.name as produkt_name
         , gesamtbestellungen.name as bestellung_name
         , gesamtbestellungen.lieferung as lieferung
         , gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.aufschlag_prozent as aufschlag_prozent
         , produktpreise.lieferpreis
         , produktpreise.mwst
         , produktpreise.pfand
         , produktpreise.lv_faktor
         , produktpreise.verteileinheit
         , produktpreise.liefereinheit
         , bestellvorschlaege.produkt_id
         , bestellvorschlaege.produktpreise_id
         , bestellvorschlaege.liefermenge
         , (" .select_basarmenge( 'gesamtbestellungen.id', 'produkte.id' ). ") AS basarmenge
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") AS gesamtbestellungen
    JOIN bestellvorschlaege ON ( bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id )
    JOIN produkte ON produkte.id = bestellvorschlaege.produkt_id
    JOIN produktpreise ON ( bestellvorschlaege.produktpreise_id = produktpreise.id )
    $where
    HAVING ( basarmenge <> 0 )
  " ;
}

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
  $basar = mysql2array( doSql( select_basar( $bestell_id ) . " ORDER BY $order_by" ) );
  foreach( $basar as $key => $r ) {
    $basar[ $key ] = preisdatenSetzen( $r );
  }
  return $basar;
}

function basar_wert_brutto( $bestell_id = 0 ) {
  $basar = sql_basar( $bestell_id );
  $wert = 0.0;
  foreach( $basar as $r ) {
    $wert += ( $r['basarmenge'] * $r['bruttopreis'] );
  }
  return $wert;
}


// // in der bilanz: wert der basarwaren entspricht dem, was wir den gruppen beim verkauf abziehen
// // (inclusive pfand, aufschlag, und mwst (solange wir letztere nicht abfuehren muessen))
///// keine gute idee: _pfand_ gehoert da nicht rein!
// //
// function basar_wert_bilanz( $bestell_id = 0 ) {
//   $basar = sql_basar( $bestell_id );
//   $wert = 0.0;
//   foreach( $basar as $r ) {
//     $wert += ( $r['basarmenge'] * ( $r['endpreis'] + $r['preisaufschlag'] ) );
//   }
//   return $wert;
// }



/**
 * verteilmengenLoeschen: bei statuswechsel LIEFERANT -> BESTELLEN:
 */
function verteilmengenLoeschen( $bestell_id ) {
  need( sql_bestellung_status( $bestell_id ) < STATUS_VERTEILT,
        "Bestellung schon verteilt: verteilmengen_loeschen() nicht mehr moeglich!" );
  nur_fuer_dienst(1,3,4);

  sql_delete_bestellzuordnungen( array( 'art' => BESTELLZUORDNUNG_ART_ZUTEILUNGEN, 'bestell_id' => $bestell_id ) );
  sql_update( 'bestellvorschlaege', array( 'gesamtbestellung_id' => $bestell_id ), array( 'liefermenge' => 0 ) );
}


function verteilmengenZuweisen( $bestell_id ) {
  $basar_id = sql_basar_id();

  need( sql_bestellung_status($bestell_id)==STATUS_LIEFERANT , 'verteilmengenZuweisen: falscher Status der Bestellung' );

  foreach( sql_bestellung_produkte( $bestell_id ) as $produkt ) {
    $produkt_id = $produkt['produkt_id'];
    $zuteilungen = zuteilungen_berechnen( $produkt );
    sql_update( 'bestellvorschlaege', array( 'gesamtbestellung_id' => $bestell_id, 'produkt_id' => $produkt_id )
               , array( 'liefermenge' => $zuteilungen['bestellmenge'] )
    );
    $festzuteilungen = $zuteilungen['festzuteilungen'];
    $toleranzzuteilungen = $zuteilungen['toleranzzuteilungen'];
    $zuteilungen = array();
    foreach( $festzuteilungen as $gruppen_id => $menge ) {
      if( isset( $zuteilungen[$gruppen_id] ) )
        $zuteilungen[$gruppen_id] += $menge;
      else
        $zuteilungen[$gruppen_id] = $menge;
    }
    foreach( $toleranzzuteilungen as $gruppen_id => $menge ) {
      if( isset( $zuteilungen[$gruppen_id] ) )
        $zuteilungen[$gruppen_id] += $menge;
      else
        $zuteilungen[$gruppen_id] = $menge;
    }
    foreach( $zuteilungen as $gruppen_id => $menge ) {
      if( $gruppen_id == $basar_id )
        continue;
      if( $menge <= 0 )
        continue;
      $gruppenbestellung_id = sql_insert_gruppenbestellung( $gruppen_id, $bestell_id );
      sql_insert( 'bestellzuordnung', array(
               'produkt_id' => $produkt_id, 'gruppenbestellung_id' => $gruppenbestellung_id
             , 'art' => BESTELLZUORDNUNG_ART_ZUTEILUNG, 'menge' => $menge
      ) );
    }
  }
}

function vormerkungenLoeschen( $bestell_id ) {
  global $js_on_exit;
  $vormerkungen_teilerfuellt = 0;
  $vormerkungen_unerfuellt = 0;
  $vormerkungen_erfuellt = 0;
  $lieferant_id = sql_bestellung_lieferant_id( $bestell_id );
  foreach( sql_bestellung_produkte( $bestell_id ) as $produkt ) {
    $produkt_id = $produkt['produkt_id'];
    foreach( sql_gruppen( array( 'bestell_id' => $bestell_id, 'produkt_id' => $produkt_id ) ) as $gruppe ) {
      $gruppen_id = $gruppe['id'];
      $vormerkung_fest = sql_bestellzuordnung_menge( array(
        'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_FEST
      , 'gruppen_id' => $gruppen_id, 'produkt_id' => $produkt_id
      ) );
      $vormerkung_toleranz = sql_bestellzuordnung_menge( array(
        'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_TOLERANZ
      , 'gruppen_id' => $gruppen_id, 'produkt_id' => $produkt_id
      ) );
      sql_delete_bestellzuordnungen( array(
        'art' => BESTELLZUORDNUNG_ART_VORMERKUNGEN
      , 'gruppen_id' => $gruppen_id, 'produkt_id' => $produkt_id
      ) );
      if( $vormerkung_fest + $vormerkung_toleranz <= 0 )
        continue;
      $gruppenbestellung_id = sql_insert_gruppenbestellung( $gruppen_id, $bestell_id );
      $keys = array( 'produkt_id' => $produkt_id, 'gruppenbestellung_id' => $gruppenbestellung_id );
      $zuteilung = sql_bestellzuordnung_menge( $keys + array( 'art' => BESTELLZUORDNUNG_ART_ZUTEILUNGEN ) );
      if( $zuteilung < $vormerkung_fest ) {
        // nicht vollstaendig erfuellt: neue vormerkung eintragen:
        sql_insert( 'bestellzuordnung', $keys + array(
          'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_FEST, 'menge' => ( $vormerkung_fest - $zuteilung )
        ) );
        sql_insert( 'bestellzuordnung', $keys + array(
          'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_TOLERANZ, 'menge' => $vormerkung_toleranz
        ) );
        if( $zuteilung > 0.001 ) {
          $vormerkungen_teilerfuellt++;
        } else {
          $vormerkungen_unerfuellt++;
        }
      } else {
        $vormerkungen_erfuellt++;
      }
    }
  }
  if( $vormerkungen_teilerfuellt + $vormerkungen_unerfuellt + $vormerkungen_erfuellt ) {
    $js_on_exit[] = " alert( ' Durch diese Bestellung werden $vormerkungen_erfuellt Vormerkungen erfuellt und geloescht; '
      + ' $vormerkungen_teilerfuellt wurden teilweise erfuellt und reduziert;'
      + ' $vormerkungen_unerfuellt unerfuellte Vormerkungen fuer Produkte dieser Bestellvorlage bleiben unveraendert.'
    ); ";
  }
}

function sql_change_liefermenge( $bestell_id, $produkt_id, $menge ) {
  nur_fuer_dienst(1,3,4);
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET, "Aenderung nicht moeglich: Bestellung ist bereits abgerechnet!" );
  return sql_update( 'bestellvorschlaege'
  , array( 'produkt_id' => $produkt_id, 'gesamtbestellung_id' => $bestell_id )
  , array( 'liefermenge' => $menge )
  );
}

function nichtGeliefert( $bestell_id, $produkt_id ) {
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET, "Aenderung nicht moeglich: Bestellung ist bereits abgerechnet!" );
  sql_delete_bestellzuordnungen( array( 'art' => BESTELLZUORDNUNG_ART_ZUTEILUNGEN, 'bestell_id' => $bestell_id, 'produkt_id' => $produkt_id ) );
  sql_change_liefermenge( $bestell_id, $produkt_id, 0 );
}

function change_bestellmengen( $gruppen_id, $bestell_id, $produkt_id, $festmenge = -1, $toleranzmenge = -1, $vormerken = false ) {
  need( sql_bestellung_status( $bestell_id ) == STATUS_BESTELLEN, "Bestellen bei dieser Bestellung nicht mehr moeglich" );
  $gruppenbestellung_id = sql_insert_gruppenbestellung( $gruppen_id, $bestell_id );

  $keys = array( 'produkt_id' => $produkt_id, 'gruppenbestellung_id' => $gruppenbestellung_id );

  if( $festmenge >= 0 ) {
    sql_delete_bestellzuordnungen( array(
      'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_FEST
    , 'gruppen_id' => $gruppen_id
    , 'produkt_id' => $produkt_id
    ) );
    $festmenge_alt = sql_bestellzuordnung_menge( $keys + array( 'art' => BESTELLZUORDNUNG_ART_FESTBESTELLUNG ) );
    if( $festmenge > $festmenge_alt ) {
      // Erhoehung der festmenge: zusaetzliche Bestellung am Ende der Schlange:
      sql_insert( 'bestellzuordnung', $keys + array(
        'menge' => $festmenge - $festmenge_alt, 'art' => BESTELLZUORDNUNG_ART_FESTBESTELLUNG
      ) );
    } elseif( $festmenge < $festmenge_alt ) {
      // bei Ruecktritt von vorheriger Bestellung: neue Bestellung stellt sich _hinten_ in die Reihe
      // (um Nachteile fuer andere Besteller zu minimieren):
      sql_delete_bestellzuordnungen( $keys + array( 'art' => BESTELLZUORDNUNG_ART_FESTBESTELLUNG ) );
      if( $festmenge > 0 ) {
        sql_insert( 'bestellzuordnung', $keys + array(
          'menge' => $festmenge, 'art' => BESTELLZUORDNUNG_ART_FESTBESTELLUNG
        ) );
      }
    } // else: ( $festmenge == $festmenge_alt ): nix zu tun...
    if( $vormerken and ( $festmenge > 0 ) ) {
      sql_insert( 'bestellzuordnung', $keys + array(
        'menge' => $festmenge, 'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_FEST
      ) );
    }
  }

  if( $toleranzmenge >= 0 ) {
    sql_delete_bestellzuordnungen( array(
      'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_TOLERANZ
    , 'gruppen_id' => $gruppen_id
    , 'produkt_id' => $produkt_id
    ) );
    $toleranzmenge_alt = sql_bestellzuordnung_menge( $keys + array( 'art' => BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG ) );
    if( $toleranzmenge_alt != $toleranzmenge ) {
      // toleranzmenge: zeitliche Reihenfolge ist hier (fast) egal, wir schreiben einfach neu:
      //
      sql_delete_bestellzuordnungen( $keys + array( 'art' => BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG ) );
      if( $toleranzmenge > 0 ) {
        sql_insert( 'bestellzuordnung', $keys + array(
          'menge' => $toleranzmenge, 'art' => BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG
        ) );
      }
    }
    if( $vormerken and ( $toleranzmenge > 0 ) ) {
      sql_insert( 'bestellzuordnung', $keys + array(
        'menge' => $toleranzmenge, 'art' => BESTELLZUORDNUNG_ART_VORMERKUNG_TOLERANZ
      ) );
    }
  }
}

function sql_change_verteilmenge( $bestell_id, $produkt_id, $gruppen_id, $menge ) {
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET, "Aenderung nicht mehr moeglich: Bestellung ist abgerechnet!" );

  $gruppenbestellung_id = sql_insert_gruppenbestellung( $gruppen_id, $bestell_id );
  sql_delete_bestellzuordnungen( array( 'art' => BESTELLZUORDNUNG_ART_ZUTEILUNG
                                       , 'gruppenbestellung_id' => $gruppenbestellung_id, 'produkt_id' => $produkt_id ) );
  return sql_insert( 'bestellzuordnung', array(
    'produkt_id' => $produkt_id
  , 'menge' => $menge
  , 'gruppenbestellung_id' => $gruppenbestellung_id
  , 'art' => BESTELLZUORDNUNG_ART_ZUTEILUNG
  ) );
}

function sql_basar2group( $gruppen_id, $produkt_id, $bestell_id, $menge ) {
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGESCHLOSSEN, "Aenderung nicht mehr moeglich: Bestellung ist abgeschlossen!" );
  $gruppenbestellung_id = sql_insert_gruppenbestellung( $gruppen_id, $bestell_id );
  return doSql(
    " INSERT INTO bestellzuordnung (produkt_id, gruppenbestellung_id, menge, art)
      VALUES ( '$produkt_id', '$gruppenbestellung_id','$menge', ".BESTELLZUORDNUNG_ART_ZUTEILUNG." )
      ON DUPLICATE KEY UPDATE menge = menge + $menge "
  , LEVEL_IMPORTANT, "Konnte Basarkauf nicht eintragen"
  );
}


////////////////////////////////////
//
// funktionen fuer gruppen-, lieferanten-, und bankkonto: transaktionen
//
// "soll" und "haben" sind immer (wo nicht anders angegeben) aus sicht der FC
//
////////////////////////////////////


// transaktionsart: hat nur bedeutung zur klassifikation der BadBank-Buchungen (s.u. TRANSAKTION_TYP_*)
//
function sql_gruppen_transaktion(
  $transaktionsart, $gruppen_id, $summe,
  $notiz ="",
  $valuta = 0, $lieferanten_id = 0, $konterbuchung_id = 0
) {
  global $dienstkontrollblatt_id, $mysqlheute;

  need( $gruppen_id or $lieferanten_id );
  $valuta or $valuta = $mysqlheute;

  return sql_insert( 'gruppen_transaktion', array(
    'type' => $transaktionsart
  , 'gruppen_id' => $gruppen_id
  , 'lieferanten_id' => $lieferanten_id
  /* , 'eingabe_zeit' => 'NOW()'  klappt so nicht, macht die DB aber sowieso automatisch! */
  , 'summe' => $summe
  , 'valuta' => $valuta
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
  logger( "sql_link_transaction: $soll_id, $haben_id" );
  if( $soll_id > 0 )
    sql_update( 'bankkonto', $soll_id, array( 'konterbuchung_id' => $haben_id ) );
  else
    sql_update( 'gruppen_transaktion', -$soll_id, array( 'konterbuchung_id' => $haben_id ) );

  if( $haben_id > 0 )
    sql_update( 'bankkonto', $haben_id, array( 'konterbuchung_id' => $soll_id ) );
  else
    sql_update( 'gruppen_transaktion', -$haben_id, array( 'konterbuchung_id' => $soll_id ) );

  return true;
}

/*
 * sql_doppelte_transaktion: fuehrt eine doppelte buchung (also eine soll, eine haben buchung) aus.
 * $soll, $haben: arrays, geben konten an. zwingend ist element 'konto_id':
 *   konto_id == -1 bedeutet gruppen/lieferanten-transaktion, sonst bankkonto
 * flag $spende: einzige transaktion, die von nicht-diensten ausgefuehrt werden kann
 */
function sql_doppelte_transaktion( $soll, $haben, $betrag, $valuta, $notiz, $spende = false ) {
  global $dienstkontrollblatt_id, $login_gruppen_id;

  // open_div( 'ok', '', "doppelte_transaktion: $soll, $haben" );
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
  logger( "sql_doppelte_transaktion: $soll_id, $haben_id" );

  return sql_link_transaction( $soll_id, $haben_id );
}

function sql_transactions( $gruppen_id, $lieferanten_id, $from_date = NULL, $to_date = NULL ) {
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
    $filter .= " $and ( valuta >= '$from_date' )";
    $and = "AND";
  }
  if( $to_date ) {
    $filter .= " $and ( valuta <= '$to_date' )";
    $and = "AND";
  }
  $sql = "
    SELECT gruppen_transaktion.id, type, summe, valuta
         , konterbuchung_id, gruppen_transaktion.notiz
         , dienstkontrollblatt_id
         , DATE_FORMAT(gruppen_transaktion.eingabe_zeit,'%d.%m.%Y') AS date
         , DATE_FORMAT(gruppen_transaktion.valuta,'%d.%m.%Y') AS valuta_trad
         , DATE_FORMAT(gruppen_transaktion.valuta,'%Y%m%d') AS valuta_kan
         , dienstkontrollblatt.name as dienst_name
    FROM gruppen_transaktion
    LEFT JOIN dienstkontrollblatt ON dienstkontrollblatt.id = dienstkontrollblatt_id
    $filter
    ORDER BY valuta_kan DESC
  ";
  return mysql2array( doSql( $sql, LEVEL_IMPORTANT, "Konnte Gruppentransaktionen nicht lesen ") );
}

function sql_get_transaction( $id ) {
  if( $id > 0 ) {
    $sql = "
      SELECT kontoauszug_jahr, kontoauszug_nr
           , betrag as haben, -betrag as soll
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
           , (-summe) as haben, summe as soll
           , gruppen_transaktion.notiz as kommentar
           , gruppen_transaktion.type as transaktionstyp
           , gruppen_transaktion.valuta as valuta
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
  return mysql2array( doSql( "SELECT * FROM bankkonten ORDER BY name" ) );
}

function sql_kontodaten( $konto_id ) {
  return sql_select_single_row( "SELECT * FROM bankkonten WHERE id='$konto_id'" );
}
function sql_kontoname($konto_id){
  $row = sql_kontodaten( $konto_id );
  return $row['name'];
}

function optionen_konten( $selected = 0 ) {
  $output = "";
  foreach( sql_konten() as $konto ) {
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

function sql_kontoauszug( $konto_id, $auszug_jahr = 0, $auszug_nr = 0 ) {
  $filter = " WHERE ( konto_id = $konto_id ) ";
  $groupby = "GROUP BY konto_id, kontoauszug_jahr, kontoauszug_nr";
  if( $auszug_jahr ) {
    $filter .= " AND ( kontoauszug_jahr = $auszug_jahr ) ";
    if( $auszug_nr ) {
      $filter .= " AND ( kontoauszug_nr = $auszug_nr ) ";
      $groupby = "";
    }
  }
  return mysql2array( doSql( "
    SELECT *
    , bankkonto.id as id
    , bankkonto.kommentar as kommentar
    , DATE_FORMAT(valuta,'%d.%m.%Y') as valuta_trad
    , DATE_FORMAT(buchungsdatum,'%d.%m.%Y') as buchungsdatum_trad
    , dienstkontrollblatt.name as dienst_name
    FROM bankkonto
    JOIN bankkonten ON bankkonten.id=konto_id
    LEFT JOIN dienstkontrollblatt ON dienstkontrollblatt.id = dienstkontrollblatt_id
    $filter
    $groupby
    ORDER BY konto_id, kontoauszug_jahr, kontoauszug_nr
  " ) );
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
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET, "Pfandzuordnung nicht mehr moeglich: Bestellung ist abgerechnet!" );
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
  need( sql_bestellung_status( $bestell_id ) < STATUS_ABGERECHNET, "Pfandzuordnung nicht mehr moeglich: Bestellung ist abgerechnet!" );
  if( $anzahl_leer > 0 ) {
    // pfandrueckgabe ist jetzt an eine gesamtbestellung gebunden, und wir brauchen eine gruppenbestellung:
    sql_insert_gruppenbestellung( $gruppen_id, $bestell_id );
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

// TRANSAKTION_TYP_xxx: dienen zur Klassifikation der BadBank-Buchungen,
// die *SALDO*-typen auch fuer gruppen/lieferanten/bank:
//
define( 'TRANSAKTION_TYP_UNDEFINIERT', 0 );      // noch nicht zugeordnet
define( 'TRANSAKTION_TYP_ANFANGSGUTHABEN', 1 );  // anfangsguthaben: gruppen, lieferanten und bank
define( 'TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN', 2 ); // Ausgleich/Umlage Differenz Anfangsguthaben
define( 'TRANSAKTION_TYP_SPENDE', 3 );           // freiwillige Spende
define( 'TRANSAKTION_TYP_SONDERAUSGABEN', 4 );   // Mitgliedsbeitrag Haus der Natur, Kontofuehrung, ...
define( 'TRANSAKTION_TYP_UMLAGE', 5 );           // Verlustumlage auf alle Mitglieder
define( 'TRANSAKTION_TYP_SOCKEL', 6 );           // geparkte Sockelbetraege
define( 'TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE', 7 ); // Umlage Bestellverluste (auch: ein paar ganz alte Basarabrechnungen)
define( 'TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN', 8 ); // Umlage Sonderausgaben
define( 'TRANSAKTION_TYP_UMBUCHUNG_SPENDE', 9 );   // umbuchung von spenden nach TRANSAKTION_TYP_AUSGLEICH_*
define( 'TRANSAKTION_TYP_UMBUCHUNG_UMLAGE', 10 );  // umbuchung von umlagen nach TRANSAKTION_TYP_AUSGLEICH_*

define( 'TRANSAKTION_TYP_SALDO', 11 );             // saldo nach jahresabschluss
define( 'TRANSAKTION_TYP_PFANDSALDO', 12 );        // pfandsaldo nach jahresabschluss

// die folgenden sind historisch und sollten nicht erzeugt werden (aber teils noch in der db vorhanden):
define( 'TRANSAKTION_TYP_STORNO', 98 );          // Buchungen, die sich gegenseitig neutralisieren
// define( 'TRANSAKTION_TYP_SONSTIGES', 99 ); // ... nicht mehr vorhanden! :-)


$selectable_types = array(
  TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE
, TRANSAKTION_TYP_ANFANGSGUTHABEN
, TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN
, TRANSAKTION_TYP_SPENDE
, TRANSAKTION_TYP_SONDERAUSGABEN
, TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN
/// , TRANSAKTION_TYP_VERLUST   // bestellverluste gehen extra!
, TRANSAKTION_TYP_UMLAGE
);

function transaktion_typ_string( $typ ) {
  switch( $typ ) {
    case TRANSAKTION_TYP_UNDEFINIERT:
      return 'unklassifiziert (sollte nicht mehr vorkommen!)';
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
    case TRANSAKTION_TYP_SALDO:
      return 'Saldo nach Abschluss';
    case TRANSAKTION_TYP_PFANDSALDO:
      return 'Pfandsaldo nach Abschluss';
    case TRANSAKTION_TYP_STORNO:
      return 'Storno';
    case TRANSAKTION_TYP_SONSTIGES:
      return 'Sonstiges (sollte nicht mehr vorkommen!)';
  }
  return "FEHLER: undefinierter Typ: $typ";
}


// optionen fuer kontoabfragen:
//
// betraege werden immer als 'soll' der fc, also schuld der fc
// (an gruppen, lieferanten oder bank) zurueckgegeben (ggf. also negativ)
//
define( 'OPTION_WAREN_NETTO_SOLL', 1 );       /* waren ohne pfand */
define( 'OPTION_WAREN_BRUTTO_SOLL', 2 );      /* mit mwst, ohne pfand */
define( 'OPTION_AUFSCHLAG_SOLL', 3 );         /* Aufschlag zur Kostendeckung der FC */
define( 'OPTION_VPREIS_SOLL', 4 );          /* waren brutto inclusive pfand, aber _ohne_ aufschlag (nur gruppenseitig sinnvoll) */
define( 'OPTION_PFAND_VOLL_BRUTTO_SOLL', 14 );   /* schuld aus kauf voller pfandverpackungen */
define( 'OPTION_PFAND_VOLL_NETTO_SOLL', 15 );
define( 'OPTION_PFAND_VOLL_ANZAHL', 16 );
define( 'OPTION_PFAND_LEER_BRUTTO_SOLL', 17 );   /* schuld aus rueckgabe leerer pfandverpackungen */
define( 'OPTION_PFAND_LEER_NETTO_SOLL', 18 ); 
define( 'OPTION_PFAND_LEER_ANZAHL', 19 );
define( 'OPTION_EXTRA_BRUTTO_SOLL', 20 );   /* sonstiges: Rabatte, Versandkosten, ... (nur lieferantenseitig sinnvoll) */



/* select_bestellungen_soll_gruppen:
 *   liefert als skalarer subquery schuld der FC an gruppen aus bestellungen, und zugehoeriger
 *   pfandbewegungen (auch rueckgabe der betreffenden woche!)
 *   - $using ist array von tabellen, die aus dem uebergeordneten query benutzt werden sollen;
 *     auswirkungen haben: 'gesamtbestellungen', 'bestellgruppen'
 *   - $art ist eine der optionen oben; SOLL immer aus sicht der FC
*/
function select_bestellungen_soll_gruppen( $art, $using = array() ) {
  switch( $art ) {
    case OPTION_VPREIS_SOLL:
      $expr = "( -1.0 * bestellzuordnung.menge *
                   ( produktpreise.pfand + produktpreise.lieferpreis / produktpreise.lv_faktor
                                           * ( 1.0 + produktpreise.mwst / 100.0 ) ) )";
      $query = 'waren';
      break;
    case OPTION_AUFSCHLAG_SOLL:
      $expr = "( -1.0 * bestellzuordnung.menge * ( produktpreise.lieferpreis / produktpreise.lv_faktor )
                 * ( gesamtbestellungen.aufschlag_prozent / 100.0 ) )";
      $query = 'waren';
      break;
    case OPTION_WAREN_BRUTTO_SOLL:
      $expr = "( -1.0 * bestellzuordnung.menge * ( produktpreise.lieferpreis / produktpreise.lv_faktor * ( 1.0 + produktpreise.mwst / 100.0 ) ) )";
      $query = 'waren';
      break;
    case OPTION_WAREN_NETTO_SOLL:
      $expr = "( -1.0 * bestellzuordnung.menge * ( produktpreise.lieferpreis / produktpreise.lv_faktor ) )";
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
      error( "select_bestellungen_soll_gruppen: bitte Funktionsaufruf anpassen!" );
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
        WHERE (bestellzuordnung.art=".BESTELLZUORDNUNG_ART_ZUTEILUNG.") " . use_filters( $using, array(
          'bestellgruppen' => 'gruppenbestellungen.bestellgruppen_id = bestellgruppen.id'
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
 *   auswirkung haben: 'gesamtbestellungen', 'lieferanten', 'pfandverpackungen'
*/
function select_bestellungen_soll_lieferanten( $art, $using = array() ) {
  switch( $art ) {
    case OPTION_WAREN_BRUTTO_SOLL:
      $expr = "( bestellvorschlaege.liefermenge / produktpreise.lv_faktor * produktpreise.lieferpreis * ( 1.0 + produktpreise.mwst / 100.0 ) )";
      $query = 'waren';
      break;
    case OPTION_WAREN_NETTO_SOLL:
      $expr = "( bestellvorschlaege.liefermenge / produktpreise.lv_faktor * produktpreise.lieferpreis )";
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
      error( "select_bestellungen_soll_lieferanten: bitte Funktionsaufruf anpassen!" );
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
          , 'pfandverpackungen' => ' pfandverpackungen.id = lieferantenpfand.verpackung_id '
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

/*  select_transaktionen_soll_gruppen:
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

function select_pfand_soll_gruppen( $using = array() ) {
  return " SELECT (
      (" .select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_BRUTTO_SOLL, $using ). ")
    + (" .select_bestellungen_soll_gruppen( OPTION_PFAND_VOLL_BRUTTO_SOLL, $using ). ")
    ) ";
}

function select_aufschlag_soll_gruppen( $using = array() ) {
  return select_bestellungen_soll_gruppen( OPTION_AUFSCHLAG_SOLL, $using );
}

function select_waren_soll_lieferanten( $using = array() ) {
  return select_bestellungen_soll_lieferanten( OPTION_WAREN_BRUTTO_SOLL, $using );
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
    + (" .select_extra_soll_lieferanten( $using ). ")
    + (" .select_transaktionen_soll_lieferanten( $using ). ")
    ) ";
}

function select_soll_gruppen( $using = array() ) {
  return " SELECT (
      (" .select_waren_soll_gruppen( $using ). ")
    + (" .select_pfand_soll_gruppen( $using ). ")
    + (" .select_aufschlag_soll_gruppen( $using ). ")
    + (" .select_transaktionen_soll_gruppen( $using ). ")
  ) ";
}

function sql_verteilt_brutto_soll( $bestell_id = 0, $gruppen_id = 0 ) {
  $muell_id = sql_muell_id();
  $cond_bestellungen = ( $bestell_id ? "( gesamtbestellungen.id = $bestell_id )" : "TRUE" );
  $cond_gruppen = ( $gruppen_id ? "( bestellgruppen.id = $gruppen_id )" : "( bestellgruppen.id != $muell_id )" );
  return sql_select_single_field(
    " SELECT sum(
        (" .select_bestellungen_soll_gruppen( OPTION_WAREN_BRUTTO_SOLL, array( 'gesamtbestellungen', 'bestellgruppen' ) ). ")
      ) as soll
      FROM gesamtbestellungen
      INNER JOIN bestellgruppen
      WHERE $cond_bestellungen AND $cond_gruppen
    ", 'soll'
  );
}

function sql_muell_brutto_soll( $bestell_id = 0 ) {
  return sql_verteilt_brutto_soll( $bestell_id, sql_muell_id() );
}

function sql_aufschlag_soll( $bestell_id = 0, $gruppen_id = 0 ) {
  $muell_id = sql_muell_id();
  $cond_bestellungen = ( $bestell_id ? "( gesamtbestellungen.id = $bestell_id )" : "TRUE" );
  $cond_gruppen = ( $gruppen_id ? "( bestellgruppen.id = $gruppen_id )" : "( bestellgruppen.id != $muell_id )" );
  return sql_select_single_field(
    " SELECT sum(
        (" .select_aufschlag_soll_gruppen( array( 'gesamtbestellungen', 'bestellgruppen' ) ). ")
      ) as aufschlag
      FROM gesamtbestellungen
      INNER JOIN bestellgruppen
      WHERE $cond_bestellungen AND $cond_gruppen
    ", 'aufschlag'
  );
}

function sql_gruppenpfand( $lieferanten_id = 0, $bestell_id = 0, $group_by = 'bestellgruppen.id' ) {
  $on = '';
  if( $lieferanten_id ) {
    $on = " ON gesamtbestellungen.lieferanten_id = $lieferanten_id";
  }
  if( $bestell_id ) {
    $on .= " AND gesamtbestellungen.id = $bestell_id";
  }
  return mysql2array( doSql( "
    SELECT
      bestellgruppen.id as gruppen_id
    , bestellgruppen.aktiv as aktiv
    , bestellgruppen.name as gruppen_name
    , bestellgruppen.id % 1000 as gruppennummer
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
    ORDER BY bestellgruppen.aktiv, gruppennummer
  " ) );
}

function sql_lieferantenpfand( $lieferanten_id, $bestell_id = 0, $group_by = 'pfandverpackungen.id' ) {
  $more_on = '';
  if( $bestell_id ) {
    $more_on = "AND gesamtbestellungen.id = $bestell_id";
  }
  return mysql2array( doSql( "
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
  " ) );
}

// sql_verbindlichkeiten_lieferanten:
// liefert verbindlichkeiten (positiv) _und_ forderungen (negativ) --- anders als bei gruppen!
// (letztere kommen da aber ja nur sehr selten vor, anders als forderungen an gruppen...)
//
function sql_verbindlichkeiten_lieferanten() {
  return mysql2array( doSql( "
    SELECT lieferanten.id as lieferanten_id
         , lieferanten.name as name
         , ( ".select_soll_lieferanten('lieferanten')." ) as soll
    FROM lieferanten
    HAVING (abs(soll) > 0.005)
  " ) );
}

function forderungen_gruppen_summe() {
  return sql_select_single_field( "
    SELECT ifnull( -sum( table_soll.soll ), 0.0 ) as forderungen
    FROM (
      SELECT (" .select_soll_gruppen('bestellgruppen'). ") AS soll
      FROM (" .select_gruppen( array( 'aktiv' => 'true' ) ). ") AS bestellgruppen
      HAVING ( soll < 0 )
    ) AS table_soll
  ", 'forderungen' );
}

function verbindlichkeiten_gruppen_summe() {
  return sql_select_single_field( "
    SELECT ifnull( sum( table_soll.soll ), 0.0 ) as verbindlichkeiten
    FROM (
      SELECT (" .select_soll_gruppen('bestellgruppen'). ") AS soll
      FROM (" .select_gruppen( array( 'aktiv' => 'true' ) ). ") AS bestellgruppen
      HAVING ( soll > 0 )
    ) AS table_soll
  ", 'verbindlichkeiten' );
}

function sql_bestellungen_soll_gruppe( $gruppen_id, $bestell_id = 0 ) {
  $more_where = '';
  if( $bestell_id ) {
    need( sql_bestellung_status( $bestell_id ) >= STATUS_LIEFERANT );
    $more_where = "AND ( gesamtbestellungen.id = $bestell_id )";
  }
  $query = "
    SELECT gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.name
         , DATE_FORMAT(gesamtbestellungen.lieferung,'%d.%m.%Y') as lieferdatum_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%d.%m.%Y') as valuta_trad
         , DATE_FORMAT(gesamtbestellungen.bestellende,'%Y%m%d') as valuta_kan
         , (" .select_bestellungen_soll_gruppen( OPTION_AUFSCHLAG_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as aufschlag_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_WAREN_NETTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as waren_netto_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_WAREN_BRUTTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as waren_brutto_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_PFAND_VOLL_BRUTTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as pfand_voll_brutto_soll
         , (" .select_bestellungen_soll_gruppen( OPTION_PFAND_LEER_BRUTTO_SOLL, array('bestellgruppen','gesamtbestellungen') ). ") as pfand_leer_brutto_soll
    FROM (" .select_gesamtbestellungen_schuldverhaeltnis(). ") as gesamtbestellungen
    INNER JOIN gruppenbestellungen
      ON ( gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id )
    INNER JOIN bestellgruppen
      ON bestellgruppen.id = gruppenbestellungen.bestellgruppen_id
    WHERE ( gruppenbestellungen.bestellgruppen_id = $gruppen_id ) $more_where
    ORDER BY valuta_kan DESC;
  ";
  return mysql2array( doSql($query, LEVEL_ALL, "sql_bestellungen_soll_gruppe() fehlgeschlagen: ") );
}


function sql_bestellungen_soll_lieferant( $lieferanten_id, $bestell_id = NULL ) {
  $where = '';
  $having = ( $bestell_id ? '' : 'HAVING ( waren_netto_soll <> 0 ) or ( pfand_voll_brutto_soll <> 0 ) or ( pfand_leer_brutto_soll <> 0 )' );
  $query = "
    SELECT gesamtbestellungen.id as gesamtbestellung_id
         , gesamtbestellungen.name
         , gesamtbestellungen.rechnungsnummer
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
    WHERE " .cond2filter( 'gesamtbestellungen.id', $bestell_id ) ."
    $having
    ORDER BY valuta_kan DESC;
  ";
  return mysql2array( doSql( $query, LEVEL_ALL, "sql_bestellungen_soll_lieferant fehlgeschlagen: " ) );
}

function sql_bestellung_soll_lieferant( $bestell_id ) {
  $result = sql_bestellungen_soll_lieferant( sql_bestellung_lieferant_id( $bestell_id ), $bestell_id );
  need( count( $result ) == 1 );
  return current( $result );
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
  global $specialgroups;
  need( ! in_array( $gruppen_id, $specialgroups ), "kontostand fuer Basar und BadBank nicht definiert" );
  $row = sql_select_single_row( "
    SELECT (".select_soll_gruppen('bestellgruppen').") as soll
    FROM bestellgruppen
    WHERE bestellgruppen.id = $gruppen_id
  " );
  return $row['soll'];
}

// funktioniert noch nicht!
//
// function gruppenkontostand_festgelegt( $gruppen_id ) {
//   return sql_select_single_field( "
//     SELECT
//       ( SELECT (".select_bestellungen_soll_gruppen( OPTION_WAREN_ENDPREIS_SOLL, array('gesamtbestellungen','bestellgruppen') ). ")
//         FROM (".select_gesamtbestellungen_unverbindlich().") AS gesamtbestellungen
//       ) AS soll
//     FROM bestellgruppen
//     WHERE bestellgruppen.id = $gruppen_id
//   ", 'soll'
//   );
// }

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

function sockeleinlagen( $gruppen_id = 0 ) {
  $where = '';
  if( $gruppen_id )
    $where = "WHERE bestellgruppen.id = $gruppen_id";
  return sql_select_single_field( "
    SELECT
      sum( bestellgruppen.sockeleinlage_gruppe
          + ( SELECT sum( gruppenmitglieder.sockeleinlage )
              FROM gruppenmitglieder
              WHERE gruppenmitglieder.aktiv
                AND gruppenmitglieder.gruppen_id = bestellgruppen.id
            )
      ) as soll
    FROM (".select_gruppen( array( 'aktiv' => 'true' ) ).") AS bestellgruppen 
    $where
  ", 'soll' 
  );
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
      , DATE_FORMAT(gruppen_transaktion.valuta,'%d.%m.%Y') AS valuta_trad
      , DATE_FORMAT(gruppen_transaktion.eingabe_zeit,'%d.%m.%Y') AS eingabedatum_trad
    FROM gruppen_transaktion
    WHERE (konterbuchung_id = 0)
      and ( gruppen_id " . ( $gruppen_id ? "=$gruppen_id" : ">0" ) . ")
  ";
}

function sql_ungebuchte_einzahlungen( $gruppen_id = 0 ) {
  return mysql2array( doSql( select_ungebuchte_einzahlungen( $gruppen_id ) ) );
}

function sql_ungebuchte_einzahlungen_summe( $gruppen_id = 0 ) {
  return sql_select_single_field( "
      SELECT IFNULL( sum( einzahlungen.summe ), 0.0 ) as summe
      FROM ( ".select_ungebuchte_einzahlungen( $gruppen_id )." ) as einzahlungen
    ", 'summe'
  );
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
         , valuta as valuta
         , notiz
         , konterbuchung_id
    FROM gruppen_transaktion
    WHERE gruppen_transaktion.gruppen_id = $muell_id AND $filter
    ORDER BY type, valuta
  ";
}

function sql_verluste( $type ) {
  return mysql2array( doSql( select_verluste( $type ) ) );
}

function sql_verluste_summe( $type ) {
  return sql_select_single_field( "
    RETURN sum( summe ) as soll
    FROM ( " .select_verluste( $type ). " ) as verluste
  ", 'soll'
  );
}


/////////////////////////////////////////////
//
// produktpreise
//
/////////////////////////////////////////////


// wichtige felder in tabelle produktpreise:
//
// einheiten: bestehen aus masszahl (optional, default ist 1, bis 3 nachkommastellen werden unterstuetzt) und
// der eigentlichen einheit. wir unterscheiden 2 einheiten:
// - verteileinheit (V-Einheit) (unsere historisch erste und ehemals wichtigste einheit):
//     - gruppen bestellen vielfache davon: jeweils eine pro klick im Bestellformular
//     - produktmengen werden immer als vielfache davon gespeichert
// - liefereinheit (L-Einheit)
//     - preise werden pro liefereinheit gespeichert (spalte 'lieferpreis')
//     - zweckmaessige einheit, die das bestellen beim lieferanten und den rechnungsabgleich erleichtern soll:
//       * _immer_ die einheit, auf die sich der "einzelpreis" des lieferanten bezieht
//       * oft (etwa beim bauern) auch die einheit, in der der wir bestellen, etwa "1 kg"
//   zu beiden Einheiten berechnet kanonische_einheit() eine kanonische darstellung:
//     * gleich wie einheit, ausser: kg in g und l in ml umgerechnet, gross/kleinschreibung vereinheitlicht, und
//     * masszahl immer abgetrennt
//   verteileinheit und liefereinheit muessen gleiche kanonische einheit haben, ausser:
//     * GB, KI, VPE oder PA als liefereinheit: verteileinheit ist dann beliebig
//     * VPE als verteileinheit ist mit beliebiger liefereinheit kombinierbar
//  - lv_faktor:
//     umrechnungsfaktor L-Einheit / V-Einheit
//     wenn verteileinheit und liefereinheit verschiedene kanonische enheiten haben (nur bei GB, KI, PA, VPE als
//     L-Einheit oder VPE als V-Einheit erlaubt) muss dieser faktor manuell erfasst werden.
// - gebindegroesse:
//     gebindegroesse, vielfache der V-Einheit. Muss immer eine ganze Zahl sein!
// - lieferpreis:
//   der nettopreis (ohne pfand, ohne mehrwertsteuer) je liefereinheit
// - pfand:
//   pfand (brutto), das den gruppen je V-Einheit in rechnung gestellt wird
//   (hat im Moment nichts mit dem vom Lieferanten berechneten Pfand zu tun!)
// - mwst:
//   mehrwertsteuersatz in prozent (meist 7.00 oder 19.00).
//
//  beispiele:
//   lieferant/produkt   V-Einheit  L-Einheit  lv-faktor           gebindegroesse
//  -----------------------------------------------------------------------------------------
//   Terra/kaese           100 g      1 kg    (10 (automatisch))    20 (= 2kg)
//   Terra/Milch             1 FL     1 FL     (1 (automatisch))     6 (= 6FL)
//   Terra/Roggen         2500 g      1 kg     (0.4 (automatisch))   3 (= 3*2.5kg)
//   Terra/Olivenoel      3000 ml     1 L      (1 (automatisch))     1 (= 3L)
//   Terra/Knoblauchzopf   0.1 ST     1 ST    (10 (automatisch))    10 (= 1ST)
//   Terra/Blumenkohl        1 ST     1 KI      8 (manuell)          8 (= 8ST = 1KI)
//   B&L/Partybroetchen      1 ST    30 ST     30 (manuell)         30 (= 30ST ("Wagenrad"))
//   B&L/Torte               1 ST    12 ST      6 (manuell)          6 (= 6ST ("halbe Torte"))
//   Bauer/Kartoffeln      500 g      1 kg     (2 (automatisch)     25 (= 12.5kg ("1/4 Zentner"))
//   Bode/Schokoriegel       1 VPE   45 g      (1 (manuell)         30 (= 30*45g = 30VPE)


function references_produktpreis( $preis_id ) {
  return sql_count( 'bestellvorschlaege', "produktpreise_id=$preis_id" );
}

function sql_produktpreise( $produkt_id, $zeitpunkt = false, $reverse = false ){
  if( $zeitpunkt === true )
    $zeitpunkt = $GLOBALS['mysqljetzt'];
  if( $zeitpunkt ) {
    $zeitfilter = " AND (zeitende >= '$zeitpunkt' OR ISNULL(zeitende))
                    AND (zeitstart <= '$zeitpunkt' OR ISNULL(zeitstart))";
  } else {
    $zeitfilter = "";
  }
  $order = $reverse ? "DESC" : "ASC";
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
    ORDER BY zeitstart $order, IFNULL(zeitende,'9999-12-31') $order, id $order";
  //  ORDER BY IFNULL(zeitende,'9999-12-31'), id";
  $result = mysql2array( doSql($query, LEVEL_ALL, "Konnte Produktpreise nich aus DB laden..") );
  foreach( $result as $key => $r ) {
    $result[ $key ] = preisdatenSetzen( $r );
  }
  return $result;
}

/* sql_aktueller_produktpreis:
 *  liefert aktuellsten preis zu $produkt_id,
 *  oder false falls es keinen gueltigen preis gibt:
 */
function sql_aktueller_produktpreis( $produkt_id, $zeitpunkt = true ) {
  return end( sql_produktpreise( $produkt_id, $zeitpunkt ) );
}

/* sql_aktueller_produktpreis_id:
 *  liefert id des aktuellsten preises zu $produkt_id,
 *  oder 0 falls es NOW() keinen gueltigen preis gibt:
 */
function sql_aktueller_produktpreis_id( $produkt_id, $zeitpunkt = true ) {
  $row = sql_aktueller_produktpreis( $produkt_id, $zeitpunkt );
  return $row ? $row['id'] : 0;
}

function select_current_productprice_id( $product_id, $timestamp = true ) {
  if( $timestamp === true )
    $timestamp = $GLOBALS['mysqljetzt'];
  if ($timestamp) {
    $zeitfilter = "AND (zeitende >= '$timestamp' OR ISNULL(zeitende)) "
        . "AND (zeitstart <= '$timestamp' OR ISNULL(zeitstart))";
  } else {
    $zeitfilter = '';
  }

  return "SELECT id FROM produktpreise "
      . "WHERE produkt_id = $product_id $zeitfilter "
      . "ORDER BY zeitstart DESC, IFNULL(zeitende,'9999-12-31') DESC, id DESC "
      . "LIMIT 1";
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
  $pr0 = FALSE;
  foreach( sql_produktpreise( $produkt_id ) as $pr1 ) {
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
        div_msg( 'warn', fc_action(  array( 'text' => "Eintrag {$pr0['id']} zum $jahr-$monat-$tag enden lassen"
                                          , 'title' => "Eintrag {$pr0['id']} zum $jahr-$monat-$tag enden lassen" )
                                   , array(  'action' => 'zeitende_setzen', 'vortag' => '1', 'preis_id' => $pr0['id']
                                          , 'day' => "$tag", 'month' => "$monat", 'year' => "$jahr" ) ) );
    }
    $pr0 = $pr1;
  }
  if( ! $pr0 ) {
    div_msg( 'alert', 'HINWEIS: kein Preiseintrag fuer diesen Artikel vorhanden!' );
  } else if ( $pr0['zeitende'] != '' ) {
    if ( $pr0['zeitende'] < $mysqljetzt ) {
        div_msg( 'alert', 'HINWEIS: kein aktuell g&uuml;ltiger Preiseintrag fuer diesen Artikel vorhanden!' );
    } else {
        div_msg( 'alert', 'HINWEIS: aktueller Preis l&auml;uft aus!' );
    }
  }
  return $rv;
}


/**
 *  Erzeugt einen Produktpreiseintrag
 */
function sql_insert_produktpreis (
  $produkt_id, $lieferpreis, $start, $bestellnummer, $gebindegroesse
, $mwst, $pfand, $liefereinheit, $verteileinheit, $lv_faktor
) {
  need( $lieferpreis > 0, "kein gueltiger Lieferpreis" );
  need( $gebindegroesse >= 1, "keine gueltige Gebindegroesse" );
  need( $mwst >= 0, "kein gueltiger Mehrwertsteuersatz" );
  need( $pfand >= 0, "kein gueltiges Pfand" );
  need( list( $lm, $le ) = kanonische_einheit( $liefereinheit, false ), "keine gueltige L-Einheit" );
  need( list( $vm, $ve ) = kanonische_einheit( $verteileinheit, false ), "keine gueltige V-Einheit" );
  need( $lm >= 0.001, "keine gueltige Masszahl bei L-Einheit" );
  need( $vm >= 0.001, "keine gueltige Masszahl bei V-Einheit" );
  if( $le == $ve ) {
    $lv_faktor = $lm / $vm;
  } else {
    switch( $le ) {
      case 'GB':
      case 'PA':
      case 'KI':
      case 'KO':
      case 'VPE':
        break;
      default:
        if( $ve === 'VPE' ) {
          break;
        }
        error( "L-Einheit und V-Einheit nicht kompatibel" );
    }
  }
  need( $lv_faktor >= 0.001, "kein gueltiger Umrechnungsfaktor L-Einheit / V-Einheit" );

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
  , 'lieferpreis' => $lieferpreis
  , 'zeitstart' => $start
  , 'bestellnummer' => $bestellnummer
  , 'gebindegroesse' => $gebindegroesse
  , 'mwst' => $mwst
  , 'pfand' => $pfand
  , 'liefereinheit' => $liefereinheit
  , 'verteileinheit' => $verteileinheit
  , 'lv_faktor' => $lv_faktor
  ) );
}


global $masseinheiten;
$masseinheiten = array( 'g', 'ml', 'ST', 'GB', 'KI', 'PA', 'GL', 'BE', 'DO', 'BD', 'BT', 'KT', 'FL', 'EI', 'KA', 'SC', 'NE', 'EA', 'TA', 'TÜ', 'TÖ', 'SET', 'BTL', 'TU', 'KO', 'SCH', 'BOX', 'BX', 'VPE' );

// kanonische_einheit: zerlegt $einheit in kanonische einheit und masszahl:
// 
function kanonische_einheit( $einheit, $die_on_error = true ) {
  global $masseinheiten;
  $kan_einheit = NULL;
  $kan_mult = NULL;
  sscanf( $einheit, "%f", $kan_mult );
  if( $kan_mult ) {
    // masszahl vorhanden, also abspalten:
    sscanf( $einheit, "%f%s", $kan_mult, $einheit );
  } else {
    // keine masszahl, also eine einheit:
    $kan_mult = 1;
  }
  $einheit = str_replace( ' ', '', strtolower($einheit) );
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
    case 'ltr':
    case 'l.':    // midgardism...
    case 'ltr.':  // midgardism...
      $kan_einheit = 'ml';
      $kan_mult *= 1000;
      break;
    case 'ml':
      $kan_einheit = 'ml';
      break;
    default:
      //
      // der rest sind zaehleinheiten (STueck und aequivalent)
      //
      foreach( $masseinheiten as $e ) {
        if( $einheit === strtolower($e) ) {
          $kan_einheit = $e;
          break 2;
        }
      }
      if( $die_on_error )
        error( "Einheit unbekannt: $einheit" );
  }
  return ( $kan_mult && $kan_einheit ) ? array( $kan_mult, $kan_einheit ) : NULL;
}

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

function mult2string( $mult ) {
  $mult = preg_replace( '/0*$/', '', sprintf( '%.3lf', $mult ) );
  return preg_replace( '/\.$/', '', $mult );
}


function sql_delete_produktpreis( $preis_id ) {
  need( references_produktpreis( $preis_id ) == 0 , 'Preiseintrag nicht löschbar, da er benutzt wird!' );
  doSql( "DELETE FROM produktpreise WHERE id=$preis_id" );
}


////////////////////////////////////
//
// Lieferantenkatalog
//
////////////////////////////////////

function sql_katalogeintrag( $katalog_id, $allow_null = false ) {
  return sql_select_single_row( "SELECT * FROM lieferantenkatalog WHERE id=$katalog_id", $allow_null );
}
function sql_katalogname( $katalog_id, $allow_null = false ) {
  $k = sql_katalogeintrag( $katalog_id, true );
  if( ! $k )
    return '';
  switch( $k['katalogformat'] ) {
    case 'terra_xls':
    default:
      return $k['katalogtyp'] . '/' . $k['katalogdatum'];
  }
}

function sql_catalogue_acronym( $context, $acronym ) {
  return mysql2array( doSql( 
            "SELECT * from `catalogue_acronyms` "
          . "WHERE `context`='$context' AND `acronym`='$acronym'") );
}

////////////////////////////////////
//
// HTML-funktionen:
//
////////////////////////////////////

// variablen die in URL (method='GET' oder in href-url) auftreten duerfen,
// mit typen:
//
$foodsoft_get_vars = array(
  'abrechnung_id' => 'u'
, 'action' => 'w'
, 'area' => 'w'
, 'auszug' => '/\d+-\d+/'
, 'auszug_jahr' => 'u'
, 'auszug_nr' => 'u'
, 'auszus_jahr' => 'u'
, 'bestell_id' => 'u'
, 'buchung_id' => 'd' /* kann auch negativ sein */
, 'confirmed' => 'w'
, 'detail' => 'w'
, 'download' => 'w'
, 'faxspalten' => 'u'
, 'gruppen_id' => 'u'
, 'id' => 'u'
, 'id_to' => 'u'
, 'login' => 'w'
, 'katalogdatum' => 'w'
, 'katalogtyp' => 'w'
, 'konto_id' => 'u'
, 'lieferanten_id' => 'u'
, 'meinkonto' => 'u'
, 'optionen' => 'u'
, 'options' => 'u'
, 'orderby' => 'w'
, 'plan_dienst' => '/^[0-9\/]+$/'
, 'prev_id' => 'u'
, 'produkt_id' => 'u'
, 'ro' => 'u'
, 'spalten' => 'u'
, 'state' => 'u'
, 'transaktion_id' => 'u'
, 'verpackung_id' => 'u'
, 'window' => 'W'
, 'window_id' => 'w'
);

$http_input_sanitized = false;
function sanitize_http_input() {
  global $from_dokuwiki
       , $foodsoft_get_vars, $http_input_sanitized, $session_id;

  if( ! $from_dokuwiki ) {
    foreach( $_GET as $key => $val ) {
      need( isset( $foodsoft_get_vars[$key] ), "unerwartete Variable $key in URL uebergeben" );
      need( checkvalue( $val, $foodsoft_get_vars[$key] ) !== false , "unerwarteter Wert fuer Variable $key in URL" );
    }
    if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
      need( isset( $_POST['itan'] ), 'foodsoft: fehlerhaftes Formular uebergeben' );
      sscanf( $_POST['itan'], "%u_%s", $t_id, $itan );
      need( $t_id, 'fehlerhaftes Formular uebergeben' );
      $row = sql_select_single_row( "SELECT * FROM transactions WHERE id=$t_id", true );
      need( $row, 'fehlerhaftes Formular uebergeben' );
      if( $row['used'] ) {
        // formular wurde mehr als einmal abgeschickt: POST-daten verwerfen:
        $_POST = array();
        echo "<div class='warn'>Warnung: mehrfach abgeschicktes Formular detektiert! (wurde nicht ausgewertet)</div>";
      } else {
        need( $row['itan'] == $itan, 'ungueltige iTAN uebergeben' );
        // echo "session_id: $session_id, from db: {$row['session_id']} <br>";
        need( $row['session_id'] == $session_id, 'ungueltige session_id' );
        // id ist noch unverbraucht: jetzt entwerten:
        sql_update( 'transactions', $t_id, array( 'used' => 1 ) );
      }
    } else {
      $_POST = array();
    }
    $http_input_sanitized = true;
  }
}


function checkvalue( $val, $typ){
    $pattern = '';
    $format = '';
    switch( substr( $typ, 0, 1 ) ) {
      case 'H':
        if( get_magic_quotes_gpc() )
          $val = stripslashes( $val );
        $val = htmlspecialchars( $val, ENT_QUOTES, 'UTF-8' );
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
        $format = '%f';
        $pattern = '/^[-\d.]+$/';
        break;
      case 'w':
        $val = trim($val);
        $pattern = '/^[a-zA-Z0-9_]*$/';
        break;
      case 'W':
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
    if( $format ) {
      sscanf( $val, $format, $val );
    }
  return $val;
}

// get_http_var:
// - name: wenn name auf [] endet, wird ein array erwartet (aus <input name='bla[]'>)
// - typ: definierte $typ argumente:
//   d : ganze Zahl
//   u : nicht-negative ganze Zahl
//   U : positive ganze Zahl (echt groesser als 0)
//   H : wendet htmlspecialchars an (erlaubt sichere und korrekte ausgabe in HTML)
//   R : raw: keine Einschraenkung, keine Umwandlung
//   f : Festkommazahl
//   w : bezeichner: alphanumerisch und _; leerstring zugelassen
//   W : bezeichner: alphanumerisch und _, mindestens ein zeichen
//   /.../: regex pattern. Wert wird ausserdem ge-trim()-t
// - default:
//   - wenn array erwartet wird, kann der default ein array sein.
//   - wird kein array erwartet, aber default is ein array, so wird $default[$name] versucht
//
// per POST uebergebene variable werden nur beruecksichtigt, wenn zugleich eine
// unverbrauchte transaktionsnummer 'itan' uebergeben wird (als Sicherung
// gegen mehrfache Absendung desselben Formulars per "Reload" Knopfs des Browsers)
//
function get_http_var( $name, $typ, $default = NULL, $is_self_field = false ) {
  global $self_fields, $self_post_fields;
  global $http_input_sanitized;

  if( ! $http_input_sanitized )
    sanitize_http_input();

  // echo "get_http_var: $is_self_field";
  if( substr( $name, -2 ) == '[]' ) {
    $want_array = true;
    $name = substr( $name, 0, strlen($name)-2 );
  } else {
    $want_array = false;
  }
  if( isset( $_GET[$name] ) ) {
    $arry = $_GET[$name];
  } elseif( isset( $_POST[$name] ) ) {
    $arry = $_POST[$name];
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
            if( $is_self_field === 'POST' )
              $self_post_fields[$name] = $default[$name];
            else
              $self_fields[$name] = $default[$name];
          }
        } else {
          unset( $GLOBALS[$name] );
          return FALSE;
        }
      } else {
        $GLOBALS[$name] = $default;
        if( $is_self_field ) {
          if( $is_self_field === 'POST' )
            $self_post_fields[$name] = $default;
          else
            $self_fields[$name] = $default;
        }
      }
      return TRUE;
    } else {
      unset( $GLOBALS[$name] );
      return FALSE;
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
        // error( 'unerwarteter Wert fuer Variable $name' );
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
        // error( 'unerwarteter Wert fuer Variable $name' );
        unset( $GLOBALS[$name] );
        return FALSE;
      } else {
        $GLOBALS[$name] = $new;
        if( $is_self_field ) {
          if( $is_self_field === 'POST' )
            $self_post_fields[$name] = $new;
          else
            $self_fields[$name] = $new;
        }
      }
  }
  return TRUE;
}

/**
 *
 */
function need_http_var( $name, $typ, $is_self_field = false ) {
  need( get_http_var( $name, $typ, NULL, $is_self_field ), "variable $name nicht uebergeben" );
  return TRUE;
}

function self_field( $name, $default = NULL ) {
  global $self_fields;
  if( isset( $self_fields[$name] ) )
    return $self_fields[$name];
  else
    return $default;
}

/**
 *
 */
function update_database( $version ) {
  switch( $version ) {
    case 8:
      logger( 'starting update_database: from version 8' );
       doSql( "ALTER TABLE Dienste ADD `dienstkontrollblatt_id` INT NULL DEFAULT NULL "
       , "update datenbank von version 8 auf 9 fehlgeschlagen"
       );
       sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 9 ) );
      logger( 'update_database: update to version 9 successful' );
    case 9:
      logger( 'starting update_database: from version 9' );

      doSql( "ALTER TABLE `produktpreise` ADD column `lv_faktor` int(11) default '0'" );
      doSql( "ALTER TABLE `produktpreise` MODIFY column `liefereinheit` varchar(10) default '1 ST'" );
      doSql( "ALTER TABLE `produktpreise` MODIFY column `verteileinheit` varchar(10) default '1 ST'" );
      doSql( "ALTER TABLE `produktpreise` MODIFY column `gebindegroesse` int(11) default 1" );
      doSql( "ALTER TABLE `lieferantenkatalog` MODIFY column `bestellnummer` bigint(20) " );
      doSql( "ALTER TABLE `lieferantenkatalog` MODIFY column `liefereinheit` varchar(20) " );
      doSql( "ALTER TABLE `lieferantenkatalog` MODIFY column `gebinde` int(11) " );

      $preise = mysql2array( doSql( "SELECT * FROM produktpreise" ) );
      foreach( $preise as $p ) {
        $id = $p['id'];
        $liefereinheit = $p['liefereinheit'];
        $verteileinheit = $p['verteileinheit'];
        $gebindegroesse = $p['gebindegroesse'];
        if( ! $gebindegroesse )
          $gebindegroesse = 1;
        switch( $liefereinheit ) {
          case '1 KI':
          case '1 PA':
            $lv_faktor = $gebindegroesse;
            break;
          default:
            if( ! ( list( $lm, $le ) = kanonische_einheit( $liefereinheit, false ) ) ) {
              $le = 'EA';
              $lm = 1;
              $liefereinheit = "$lm $le";
            }
            if( ! ( list( $vm, $ve ) = kanonische_einheit( $verteileinheit, false ) ) ) {
              $ve = $le;
              $vm = $lm;
              $verteileinheit = "$vm $ve";
            }
            if( $ve != $le ) {
              $ve = $le;
              $vm = $lm;
              $verteileinheit = "$vm $ve";
            }
            $lv_faktor = ( 1.0 * $gebindegroesse ) * $vm / $lm;
            if( $le == 'g' )
              $le = 'KG';
            if( $le == 'ml' )
              $le = 'L';
            $liefereinheit = "1 $le";
            break;
        }
        sql_update( 'produktpreise', $id, array( 'lv_faktor' => $lv_faktor
                                               , 'liefereinheit' => $liefereinheit, 'verteileinheit' => $verteileinheit ) );
      }

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 10 ) );
      logger( 'update_database: update to version 10 successful' );

  case 10:
      logger( 'starting update_database: from version 10' );

      // preise ab jetzt pro L-einheit speichern:
      doSql( "ALTER TABLE `produktpreise` ADD column `lieferpreis` decimal(12,4) default '0.0'" );
      doSql( "ALTER TABLE `produktpreise` MODIFY column `lv_faktor` decimal(12,6) default '1.0'" );

      // gebindegroesse bleibt erstmal pro v-einheit (wie auch das pfand!)
      // doSql( "ALTER TABLE `produktpreise` MODIFY column `gebindegroesse` decimal(9,3) default '1.0'"
      // , "update datenbank von version 10 auf 11 fehlgeschlagen: failed: modify column gebindegroesse"
      // );
      $preise = mysql2array( doSql( "SELECT * FROM produktpreise" ) );
      foreach( $preise as $p ) {
        $id = $p['id'];
        $preis = $p['preis'];
        $p = preisdatenSetzen( $p );
        $lv_faktor = $p['lv_faktor'];
        $lieferpreis = ( $preis - $p['pfand'] ) * $lv_faktor / ( 1.0 + $p['mwst'] / 100.0 );
        /// $gebindegroesse = mult2string( $p['gebindegroesse'] / $p['lv_faktor'] );
        sql_update( 'produktpreise', $id, array( 'lieferpreis' => $lieferpreis
                                               , 'lv_faktor' => $lv_faktor
                                            /* , 'gebindegroesse' => $gebindegroesse */
                                               ) );
      }
      doSql( "ALTER TABLE `produktpreise` DROP column `preis` " );
      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 11 ) );
      logger( 'update_database: update to version 11 successful' );

  case 11:
      logger( 'starting update_database: from version 11' );

      doSql( "ALTER TABLE `gruppenbestellungen` CHANGE column `bestellguppen_id` `bestellgruppen_id` int(11)" );

      doSql( "ALTER TABLE `Dienste` CHANGE column `ID` `id` int(11) NOT NULL auto_increment
                                  , CHANGE column `Dienst` `dienst` enum('1/2','3','4','5','freigestellt')
                                  , CHANGE column `Lieferdatum` `lieferdatum` date
                                  , CHANGE column `Status` `status` enum('Vorgeschlagen','Akzeptiert','Bestaetigt','Offen')
                                  , ADD column `geleistet` tinyint(1) not null default 0
                                  , CHANGE column `Bemerkung` `bemerkung` text
                                  , RENAME to `dienste`
      " );

      doSql( "ALTER TABLE `bestellgruppen` ADD column `sockeleinlage` decimal(8,2) NOT NULL default '0.00'" );
      doSql( "ALTER TABLE `gruppenmitglieder` ADD column `sockeleinlage` decimal(8,2) NOT NULL default '0.00'" );

      $sockelbetrag = sql_select_single_field( "SELECT value FROM leitvariable WHERE name = 'sockelbetrag'", 'value' );
      echo "sockelbetrag: $sockelbetrag";
      doSql( "UPDATE `gruppenmitglieder` SET sockeleinlage = $sockelbetrag WHERE status = 'aktiv' " );
      doSql( "ALTER TABLE `leitvariable` MODIFY column `name` varchar(30) NOT NULL default '' " );
      sql_insert( 'leitvariable', array( 'name' => 'sockelbetrag_mitglied', 'value' => $sockelbetrag ) );
      sql_insert( 'leitvariable', array( 'name' => 'sockelbetrag_gruppe', 'value' => 0.0 ) );
      doSql( "DELETE FROM leitvariable WHERE name = 'sockelbetrag'" );

      doSql( "ALTER TABLE `gesamtbestellungen` ADD column `aufschlag_prozent` decimal(4,2) NOT NULL default '0.00'" );
      sql_insert( 'leitvariable', array( 'name' => 'aufschlag_default', 'value' => 0.0 ) );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 12 ) );
      logger( 'update_database: update to version 12 successful' );

  case 12:
      logger( 'starting update_database: from version 12' );

      doSql( "ALTER TABLE `bestellvorschlaege` MODIFY COLUMN `liefermenge` decimal(10,3) not null default 0
                                             , DROP COLUMN `bestellmenge` " );

      doSql( "ALTER TABLE `bestellzuordnung` ADD INDEX `undnocheiner` (`art`,`gruppenbestellung_id`)" );

      doSql( "ALTER TABLE `dienste` DROP INDEX `GruppenID`
                                  , ADD INDEX `mitglied` (`gruppenmitglieder_id`)
                                  , ADD COLUMN `gruppen_id` int(11) not null default 0
                                  , MODIFY COLUMN `dienstkontrollblatt_id` int(11) not null default 0" );

      doSql( "ALTER TABLE `gesamtbestellungen` DROP COLUMN `state`" );
      doSql( "ALTER TABLE `gesamtbestellungen` ADD INDEX `rechnungsstatus` (`rechnungsstatus`)" );

      doSql( "ALTER TABLE `gruppen_transaktion` CHANGE COLUMN `kontobewegungs_datum` `valuta` date not null default '0000-00-00' " );

      doSql( "ALTER TABLE `gruppenbestellungen` ADD INDEX `gruppe` (`bestellgruppen_id`)
                                              , MODIFY COLUMN `bestellgruppen_id` int(11) not null default 0
      " );

      doSql( "UPDATE `gruppenmitglieder` SET rotationsplanposition = id WHERE true" );
      // hope we don't need this ^ !
      doSql( "ALTER TABLE `gruppenmitglieder` ADD INDEX `gruppe` (`gruppen_id`)
                        , ADD COLUMN `aktiv` tinyint(1) not null default 0
                        , MODIFY COLUMN `gruppen_id` int(11) not null default 0
                        , ADD UNIQUE KEY `rotationsplan` (`rotationsplanposition`)
      " );
      doSql( "UPDATE `gruppenmitglieder` SET aktiv=1 WHERE status='aktiv' " );
      doSql( "ALTER TABLE `gruppenmitglieder` DROP COLUMN `status` " );

      doSql( "ALTER TABLE `gruppenpfand` ADD INDEX `gruppe` (`gruppen_id`)
                        , MODIFY COLUMN `bestell_id` int(11) not null default 0
                        , MODIFY COLUMN `gruppen_id` int(11) not null default 0
                        , MODIFY COLUMN `pfand_wert` decimal(6,2) not null default 0.0
                        , MODIFY COLUMN `anzahl_leer` int(11) not null default 0
      " );

      doSql( "DROP TABLE IF EXISTS `kategoriezuordnung` " );
      doSql( "DROP TABLE IF EXISTS `produktkategorien` " );

      doSql( "ALTER TABLE `lieferantenkatalog` MODIFY COLUMN `lieferanten_id` int(11) not null default 0
                                             , MODIFY COLUMN `mwst` decimal(4,2) not null default 0.0
                                             , MODIFY COLUMN `pfand` decimal(6,2) not null default 0.0
                                             , MODIFY COLUMN `preis` decimal(8,2) not null default 0.0
      " );

      doSql( "ALTER TABLE `lieferantenpfand` MODIFY COLUMN `verpackung_id` int(11) not null default 0
                                           , MODIFY COLUMN `bestell_id` int(11) not null default 0
      " );

      doSql( "ALTER TABLE `logbook` MODIFY COLUMN `session_id` int(11) not null default 0" );

      doSql( "ALTER TABLE `pfandverpackungen` DROP INDEX `sort_id`
                                            , ADD INDEX `sort_id` (`lieferanten_id`,`sort_id`)
                                            , MODIFY COLUMN `lieferanten_id` int(11) not null default 0
                                            , MODIFY COLUMN `wert` decimal(8,2) not null default 0.0
                                            , MODIFY COLUMN `mwst` decimal(6,2) not null default 0.0
                                            , MODIFY COLUMN `sort_id` int(11) not null default 0
      " );

      doSql( "ALTER TABLE `produkte` DROP COLUMN `einheit`" );

      doSql( "ALTER TABLE `produktpreise` MODIFY COLUMN `lieferpreis` decimal(12,4) not null default 0.0
                                   , MODIFY COLUMN `verteileinheit` varchar(10) not null default '1 ST'
                                   , MODIFY COLUMN `liefereinheit` varchar(10) not null default '1 ST'
                                   , MODIFY COLUMN `gebindegroesse` int(11) not null default 1
                                   , MODIFY COLUMN `lv_faktor` decimal(12,6) not null default 1.0
      " );

      doSql( "ALTER TABLE `transactions` MODIFY COLUMN `session_id` int(11) not null default 0" );
      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 13 ) );

      logger( 'update_database: update to version 13 successful' );
  case 13:
      logger( 'starting update_database: from version 13' );

      doSql( "update `bestellzuordnung` set art=20 where art=0" );
      doSql( "update `bestellzuordnung` set art=21 where art=1" );
      doSql( "update `bestellzuordnung` set art=30 where art=2" );
      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 14 ) );

      logger( 'update_database: update to version 14 successful' );
  case 14:
      logger( 'starting update_database: from version 14' );

      doSql( "ALTER TABLE `lieferantenkatalog` ADD COLUMN `katalogformat` varchar(20) not null default '' " );
      doSql( "ALTER TABLE `lieferanten` ADD COLUMN `katalogformat` varchar(20) not null default '' " );
      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 15 ) );

      logger( 'update_database: update to version 15 successful' );

  case 15:
      logger( 'starting update_database: from version 15' );

      doSql( "ALTER TABLE `lieferantenkatalog` ADD COLUMN `gueltig` tinyint(1) not null default 1 " );
      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 16 ) );

      logger( 'update_database: update to version 16 successful' );

  case 16:
      logger( 'starting update_database: from version 16' );

      doSql( "ALTER TABLE `gesamtbestellungen` ADD COLUMN `abrechnung_id` int(11) not null default 0 " );
      doSql( "update `gesamtbestellungen` set abrechnung_id=id where true" );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 17 ) );
      logger( 'update_database: update to version 17 successful' );

  case 17:
      logger( 'starting update_database: from version 17' );

      doSql( "ALTER TABLE `produkte` ADD COLUMN `dauerbrenner` tinyint(1) not null default 0 " );
      doSql( "ALTER TABLE `sessions` ADD COLUMN `session_timestamp` timestamp not null default CURRENT_TIMESTAMP " );
      doSql( "ALTER TABLE `bestellvorschlaege` ADD COLUMN `vorschlag_gruppen_id` int(11) not null default 0 " );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 18 ) );
      logger( 'update_database: update to version 18 successful' );

  case 18:
      logger( 'starting update_database: from version 18' );

      doSql( "ALTER TABLE `lieferantenkatalog` MODIFY COLUMN `gebinde` decimal(8,3) not null default 1.0 " );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 19 ) );
      logger( 'update_database: update to version 19 successful' );

  case 19:
      logger( 'starting update_database: from version 19' );

      sql_update( 'lieferanten', array( 'katalogformat' => 'terra' ), array( 'katalogformat' => 'terra_xls' ) );

      sql_insert( 'leitvariable', array(
          'name' => 'toleranz_default'
        , 'value' => '0.00'
        , 'comment' => 'automatischer Toleranzzuschlag in Prozent bei Bestellungen (kann im Einzelfall manuell runtergesetzt werden)'
        )
      );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 20 ) );
      logger( 'update_database: update to version 20 successful' );

  case 20:
      logger( 'starting update_database: from version 20' );

      sql_update( 'lieferantenkatalog', array( 'katalogformat' => 'terra' ), array( 'katalogformat' => 'terra_xls' ) );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 21 ) );
      logger( 'update_database: update to version 21 successful' );
      
  case 21:
      logger( 'starting update_database: from version 21' );

      doSql( "ALTER TABLE `lieferantenkatalog` ADD COLUMN `hersteller` text not null default '' " );
      doSql( "ALTER TABLE `lieferantenkatalog` ADD COLUMN `bemerkung` text not null default '' " );
      doSql( "ALTER TABLE `lieferantenkatalog` ADD COLUMN `ean_einzeln` varchar(15) not null default '' " );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 22 ) );
      logger( 'update_database: update to version 22 successful' );
  
  case 22:
      logger( 'starting update_database: from version 22' );

      doSql( "ALTER TABLE `sessions` ADD COLUMN `muteReconfirmation_timestamp` timestamp null default null" );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 23 ) );
      logger( 'update_database: update to version 23 successful' );
      
 case 23:
      logger( 'starting update_database: from version 23' );
      
      doSql( "CREATE TABLE `catalogue_acronyms` ("
              . "  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY"
              . ", `context` VARCHAR(10) NOT NULL"
              . ", `acronym` VARCHAR(10) NOT NULL"
              . ", `definition` TEXT NOT NULL"
              . ", `comment` TEXT NOT NULL"
              . ", `url` TEXT NOT NULL"
              . ", UNIQUE INDEX `secondary` (`context`, `acronym`)"
              . " ) ");

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 24 ) );
      logger( 'update_database: update to version 24 successful' );
   
 case 24:
      logger( 'starting update_database: from version 24' );

      doSql( "ALTER TABLE `lieferanten`
                CHANGE COLUMN `adresse` `strasse` text not null
              , ADD COLUMN `ort` text not null
              , ADD COLUMN `anrede` text not null
              , ADD COLUMN `grussformel` text not null
              , ADD COLUMN `fc_name` text not null
              , ADD COLUMN `fc_strasse` text not null
              , ADD COLUMN `fc_ort` text not null
      " );

      // doSql( "UPDATE `lieferanten` set fc_name='FoodCoop $foodcoop_name'" ); // not yet available!

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 25 ) );
      logger( 'update_database: update to version 25 successful' );

 case 25:
      logger( 'starting update_database: from version 25' );

      doSql( "ALTER TABLE `gruppenmitglieder`
                ADD COLUMN `slogan` text not null 
              , ADD COLUMN `url` text not null
              , ADD COLUMN `photo_url` mediumtext not null
      " );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 26 ) );
      logger( 'update_database: update to version 26 successful' );

case 26:
      logger( 'starting update_database: from version 26' );

      sql_insert( 'leitvariable', array(
        'name' => 'member_showcase_count'
      , 'value' => '3'
      , 'comment' => 'Anzahl an Mitgliedern, die auf der Startseite angezeigt werden (neben Schwarzem Brett)'
      ) );
      sql_insert( 'leitvariable', array(
        'name' => 'member_showcase_title'
      , 'value' => '<b>Ein paar von uns</b>'
      , 'comment' => 'Titel über Mitgliedern, die auf der Startseite angezeigt werden (neben Schwarzem Brett)'  
      ) );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 27 ) );
      logger( 'update_database: update to version 27 successful' );

  case 27:
      logger( 'starting update_database: from version 27' );

      doSql( "ALTER TABLE `lieferanten` ADD COLUMN `bestellfaxspalten` int(11) not null default 534541" );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 28 ) );
      logger( 'update_database: update to version 28 successful' );
      
  case 28:
      logger( 'starting update_database: from version 28' );
      sql_insert( 'leitvariable', array(
        'name' => 'exportDB'
      , 'value' => '0'
      , 'comment' => 'Flag: export des Datenbankinhalts erlauben'  
      ) );
      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 29 ) );
      logger( 'update_database: update to version 29 successful' );
      
  case 29:
      logger( 'starting update_database: from version 29' );

      doSql( "ALTER TABLE `gruppenmitglieder` ADD COLUMN `notiz` text not null " );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 30 ) );
      logger( 'update_database: update to version 30 successful' );

  case 30:
      logger( 'starting update_database: from version 30' );

      doSql( "ALTER TABLE `bestellgruppen` ADD COLUMN `notiz_gruppe` text not null " );

      sql_update( 'leitvariable', array( 'name' => 'database_version' ), array( 'value' => 31 ) );
      logger( 'update_database: update to version 31 successful' );


	}
}

function wikiLink( $topic, $text, $head = false ) {
  global $foodsoftdir;
  if( ( $wikibase = getenv('wikibase') ) ) {
    echo "
      <a class='wikilink' " . ( $head ? "id='wikilink_head' " : "" ) . "
        title='zur Wiki-Seite $topic'
        href=\"javascript:neuesfenster('$wikibase/doku.php?id=$topic','wiki');\"
      >$text</a>
    ";
  }
}

function setWikiHelpTopic( $topic ) {
  global $foodsoftdir, $js_on_exit;
  if( ( $wikibase = getenv('wikibase') ) ) {
    // head may not have been read (yet), so we postpone this:
    $js_on_exit[] = "document.getElementById('wikilink_head').href
          = \"javascript:neuesfenster('$wikibase/doku.php?id=$topic','wiki');\" ";
    $js_on_exit[] = "document.getElementById('wikilink_head').title = \"zur Wiki-Seite $topic\" ";
  }
}

// auf <title> (fensterrahmen) kann offenbar nicht mehr zugegriffen werden(?), wir
// koennen daher nur noch den subtitle (im fenster) setzen:
//
function setWindowSubtitle( $subtitle ) {
  open_javascript( replace_html( 'subtitle', "Foodsoft: $subtitle" ) );
}

global $itan;
$itan = false;

function set_itan() {
  global $itan, $session_id;
  $tan = random_hex_string(5);
  $id = sql_insert( 'transactions' , array(
    'used' => 0
  , 'session_id' => $session_id
  , 'itan' => $tan
  ) );
  $itan = $id.'_'.$tan;
}

function get_itan( $force_new = false ) {
  global $itan;
  if( $force_new or ! $itan )
    set_itan();
  return $itan;
}

function optionen( $values, $selected ) {
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
    $output .= "<option value='$value'";
    if( $value == $selected )
      $output .= " selected";
    if( $title )
      $output .= " title='$title'";
    $output .= ">$text</option>";
  }
  return $output;
}


////////////////////////////////////
//
// PDF-export
//
////////////////////////////////////

function get_tmp_working_dir( $base = '/tmp' ) {
  for( $retries = 0; $retries < 10; $retries++ ) {
    $fqpath = $base . '/foodsoft.' . random_hex_string( 8 );
    if( mkdir( $fqpath, 0700 ) )
      return $fqpath;
  }
  return false;
}

function tex2pdf( $tex ) {
  $tex = preg_replace( '/@@macros_prettytables@@/', file_get_contents( 'templates/prettytables.tex' ), $tex );
  $cwd = getcwd();
  need( $tmpdir = get_tmp_working_dir() );
  need( chdir( $tmpdir ) );
  file_put_contents( 'tex2pdf.tex', $tex );
  exec( 'pdflatex tex2pdf.tex', /* & */ $output, /* & */ $rv );
  if( ! $rv ) {
    $pdf = file_get_contents( 'tex2pdf.pdf' );
    // open_div( 'ok', '', 'ok: '.  implode( ' ', $output ) );
  } else {
    open_div( 'warn', '', 'error: '. file_get_contents( 'tex2pdf.log' ) );
    $pdf = false;
  }
  @ unlink( 'tex2pdf.tex' );
  @ unlink( 'tex2pdf.aux' );
  @ unlink( 'tex2pdf.log' );
  @ unlink( 'tex2pdf.pdf' );
  chdir( $cwd );
  rmdir( $tmpdir );

  return $pdf;
}

function tex_encode( $s ) {
  $maps = array(
    '/\\\\/' => '\\backslash'
  , '/\\&quot;/' => "''"
  , '/\\&#039;/' => "'"
  , '/([$%_#~])/' => '\\\\$1'
  , '/\\&amp;/' => '\\&'
  , '/\\&lt;/' => '$<$'
  , '/\\&gt;/' => '$>$'
  , '/[}]/' => '$\}$'
  , '/[{]/' => '$\{$'
  , '/ä/' => '{\"a}'
  , '/Ä/' => '{\"A}'
  , '/ö/' => '{\"o}'
  , '/Ö/' => '{\"O}'
  , '/ü/' => '{\"u}'
  , '/Ü/' => '{\"U}'
  , '/ß/' => '{\ss}'
  , '/\\\\backslash/' => '\\$\\backslash{}\\$'
  );
  foreach( $maps as $pattern => $to ) {
    $s = preg_replace( $pattern, $to, $s );
  }
  $len = strlen( $s );
  $i = 0;
  $out = '';
  while( $i < $len ) {
    $c = $s[ $i ];
    $n = ord( $c );
    $bytes = 1;
    if( $n < 128 ) {
      // skip most control characters:
      if( $n < 32 ) {
        switch( $n ) {
          case  9: // tab
            $out .= ' ';
            break;
          case 10: // lf 
            $out .= '\\newline{}';
            break;
          case 13: // cr
            break;
          default:
            break;
        }
      } else {
        $out .= $c;
      }
    } else {
      // skip remaining utf-8 characters:
      if( $n > 247 ) continue;
      elseif( $n > 239 ) $bytes = 4;
      elseif( $n > 223 ) $bytes = 3;
      elseif( $n > 191 ) $bytes = 2;
      else continue;
    }
    $i += $bytes;
  }
  return $out;
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
// social
//
////////////////////////////////////

function get_avatar_url( $member_row ) {

  return $member_row['photo_url'];

//   $d = '404';
//   $email = $member_row['email'];
//   /*
//   if ($member_row['slogan'] || $member_row['url']) {
//     $d = 'identicon';
//     if (!$email) {
//       $d = 'mm';
//       $email = true;
//     }
//   }
//   */
//   if (!$email)
//     return false;
//   return checked_gravatar_url($email, 128, $d);

}

?>
