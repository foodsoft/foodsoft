<?php

////////////////////////////////////////
//
// functions to output one row of a form
//
// - the line will usually contain two columns: one for the label, one for the input field
// - if a $fieldname is alread part of $self_fields (ie, defining part of the current view), the value
//   will just be printed and cannot be modified (only applies to types that can be in $self_fields)
// - the last (second) column will not be closed; so e.g. a submission_button() can be appended
//
////////////////////////////////////////

function form_row_konto( $label = 'Konto:', $fieldname = 'konto_id', $initial = 0 ) {
  open_tr();
    open_td( 'label', '', $label );
    if( ( $konto_id = self_field( $fieldname ) ) === NULL )
      $konto_id = $initial;
    else
      $fieldname = false;
    open_td( 'kbd' ); echo konto_view( $konto_id, $fieldname );
}

function form_row_kontoauszug( $label = 'Auszug:', $fieldname = 'auszug', $initial_jahr = NULL, $initial_nr = 0 ) {
  open_tr();
    open_td( 'label', '', $label );
    $auszug_jahr = self_field( $fieldname.'_jahr' );
    $auszug_nr = self_field( $fieldname.'_nr' );
    if( $auszug_jahr !== NULL and $auszug_nr !== NULL )
      $fieldname = false;
    if( $auszug_jahr === NULL )
      $auszug_jahr = ( $initial_jahr !== NULL ) ? $initial_jahr : date('Y');
    if( $auszug_nr === NULL )
      $auszug_nr = $initial_nr;
    open_td( 'kbd oneline' ); echo kontoauszug_view( 0, $auszug_jahr, $auszug_nr, $fieldname );
}

function form_row_gruppe( $label = 'Gruppe:', $fieldname = 'gruppen_id', $initial = 0 ) {
  open_tr();
    open_td('label', '', $label );
    if( ( $gruppen_id = self_field( $fieldname ) ) === NULL )
      $gruppen_id = $initial;
    else
      $fieldname = false;
    open_td( 'kbd' ); echo gruppe_view( $gruppen_id, $fieldname );
}

function form_row_lieferant( $label = 'Lieferant:', $fieldname = 'lieferanten_id', $initial = 0 ) {
  open_tr();
    open_td('label', '', $label );
    if( ( $lieferant_id = self_field( $fieldname ) ) === NULL )
      $lieferant_id = $initial;
    else
      $fieldname = false;
    open_td( 'kbd' ); echo lieferant_view( $lieferant_id, $fieldname );
}

function form_row_date( $label, $fieldname, $initial = 0 ) {
  $year = self_field( $fieldname.'_year' );
  $month = self_field( $fieldname.'_month' );
  $day = self_field( $fieldname.'_day' );
  if( ($year !== NULL) and ($day !== NULL) and ($month !== NULL) ) {
    $date = "$year-$month-$day";
    $fieldname = false;
  } else {
    $date = $initial;
  }
  open_tr();
    open_td( 'label', '', $label );
    open_td( 'kbd oneline' ); echo date_view( $date, $fieldname );
}

function form_row_date_time( $label, $fieldname, $initial = 0 ) {
  $year = self_field( $fieldname.'_year' );
  $month = self_field( $fieldname.'_month' );
  $day = self_field( $fieldname.'_day' );
  $hour = self_field( $fieldname.'_hour' );
  $minute = self_field( $fieldname.'_minute' );
  if( ($year !== NULL) and ($day !== NULL) and ($month !== NULL) and ($hour !== NULL) and ($minute !== NULL) ) {
    $datetime = "$year-$month-$day $hour:$minute";
    $fieldname = false;
  } else {
    $datetime = $initial;
  }
  open_tr();
    open_td( 'label', '', $label );
    open_td( 'kbd' ); echo date_time_view( $datetime, $fieldname );
}

function form_row_betrag( $label = 'Betrag:' , $fieldname = 'betrag', $initial = 0.0 ) {
  open_tr();
    open_td( 'label', '', $label );
    open_td( 'kbd' ); echo price_view( $initial, $fieldname );
}

function form_row_text( $label = 'Notiz:', $fieldname = 'notiz', $size = 60, $initial = '' ) {
  open_tr();
    open_td( 'label', '', $label );
    open_td( 'kbd' ); echo string_view( $initial, $size, $fieldname );
}


//////////////////////////////////////////////////////////////////
//
// functions to output complete forms, usually followed
// by a handler function to deal with the POSTed data
//
//////////////////////////////////////////////////////////////////

function form_finish_transaction( $transaction_id ) {
  global $input_event_handlers;
  open_form( '', "action=finish_transaction,transaction_id=$transaction_id" );
    open_table('layout');
      form_row_konto();
      form_row_kontoauszug();
      form_row_date( 'Valuta:', 'valuta' );
      open_tr();
        open_td( 'right', "colspan='2'" );
        echo "Best&auml;tigen: <input type='checkbox' name='confirm' value='yes' $input_event_handlers>";
        qquad();
        submission_button( 'OK' );
    close_table();
  close_form();
}

function action_finish_transaction() {
  global $transaction_id, $konto_id, $auszug_jahr, $auszug_nr, $valuta_day, $valuta_month, $valuta_year, $confirm;
  global $dienstkontrollblatt_id;
  need_http_var( 'transaction_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  get_http_var( 'confirm', 'w', 'no' );

  if( $confirm != 'yes' )
    return;

  fail_if_readonly();
  nur_fuer_dienst(4);

  $soll_id = -$transaction_id;
  $soll_transaction = sql_get_transaction( $soll_id );

  $haben_id = sql_bank_transaktion(
    $konto_id, $auszug_jahr, $auszug_nr
  , $soll_transaction['soll'], "$valuta_year-$valuta_month-$valuta_day"
  , $dienstkontrollblatt_id, $soll_transaction['kommentar'], 0
  );

  sql_link_transaction( $soll_id, $haben_id );

  return sql_update( 'gruppen_transaktion', $transaction_id, array(
    'dienstkontrollblatt_id' => $dienstkontrollblatt_id
  ) );
}

function formular_buchung_gruppe_bank( $notiz_initial = 'Einzahlung' ) {
  open_form( '', 'action=buchung_gruppe_bank' );
    open_fieldset( 'small_form', '', 'Einzahlung / Auszahlung Gruppe' );
      open_table('layout');
        form_row_gruppe();
        form_row_konto();
        form_row_kontoauszug();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'Haben Konto: positiv bei Einzahlung, negativ bei Auszahlung' );
        form_row_betrag( 'Haben Konto:' );
        form_row_text( 'Notiz:', 'notiz', 60, $notiz_initial );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_gruppe_bank() {
  global $gruppen_id, $konto_id, $auszug_nr, $auszug_jahr, $valuta_day, $valuta_month, $valuta_year, $betrag, $notiz;
  global $specialgroups;
  $problems = false;

  need_http_var( 'betrag', 'f' );
  need_http_var( 'gruppen_id', 'U' );
  $gruppen_name = sql_gruppenname( $gruppen_id );
  if( $betrag < 0 ) {
    need_http_var( 'notiz', 'H' );
  } else {
    get_http_var( 'notiz', 'H', "Einzahlung Gruppe $gruppen_name" );
  }
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  need( ! in_array( $gruppen_id, $specialgroups ) );
  need( $gruppen_name );

  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_buchung_lieferant_bank( $notiz_initial = 'Abbuchung Lieferant' ) {
  open_form( '', 'action=buchung_lieferant_bank' );
    open_fieldset( '', '', 'Überweisung / Lastschrift Lieferant' );
      open_table('layout');
        form_row_lieferant();
        form_row_konto();
        form_row_kontoauszug();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'Betrag: positiv bei Einzahlung, negativ bei Auszahlung/Lastschrift' );
        form_row_betrag( 'Haben Konto:' );
        form_row_text( 'Notiz:', 'notiz', 60, $notiz_initial );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_lieferant_bank() {
  global $lieferanten_id, $konto_id, $auszug_jahr, $auszug_nr, $betrag, $notiz, $valuta_day, $valuta_month, $valuta_year;
  $problems = false;

  need_http_var( 'betrag', 'f' );
  need_http_var( 'lieferanten_id', 'U' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  need_http_var( 'notiz', 'H' );
  sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'lieferanten_id' => $lieferanten_id )
  , array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
  , $betrag
  , "$valuta_year-$valuta_month-$valuta_day"
  , "$notiz"
  );
}

function formular_buchung_gruppe_lieferant( $notiz_initial = 'Zahlung an Lieferant' ) {
  open_form( '', 'action=buchung_gruppe_lieferant' );
    open_fieldset( 'small_form', '', 'Zahlung von Gruppe an Lieferant' );
      open_table('layout');
        form_row_gruppe();
        form_row_lieferant();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'Haben Lieferant: positiv: Zahlung an Lieferant / negativ: Zahlung an Gruppe' );
        form_row_betrag( 'Haben Lieferant:' );
        form_row_text( 'Notiz:', 'notiz', 60, $notiz_initial );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_gruppe_lieferant() {
  global $betrag, $lieferanten_id, $gruppen_id, $notiz, $valuta_day, $valuta_month, $valuta_year;
  need_http_var( 'betrag', 'f' );
  need_http_var( 'lieferanten_id', 'U' );
  need_http_var( 'gruppen_id', 'U' );
  need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
  , array( 'konto_id' => -1, 'lieferanten_id' => $lieferanten_id )
  , $betrag
  , "$valuta_year-$valuta_month-$valuta_day"
  , "$notiz"
  );
}

function formular_buchung_gruppe_gruppe( $notiz_initial = 'Umbuchung' ) {
  open_form( '', 'action=buchung_gruppe_gruppe' );
    open_fieldset( '', '', 'Umbuchung von Gruppe an Gruppe' );
      open_table('layout');
        form_row_gruppe( 'von Gruppe:', 'gruppen_id' );
        form_row_gruppe( 'an Gruppe:', 'nach_gruppen_id' );
        form_row_date( 'Valuta:', 'valuta' );
        form_row_betrag( 'Betrag:' );
        form_row_text( 'Notiz:', 'notiz', 60, $notiz_initial );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_gruppe_gruppe() {
  global $betrag, $gruppen_id, $nach_gruppen_id, $notiz, $valuta_day, $valuta_month, $valuta_year;
  logger( "buchung_gruppe_gruppe: $betrag, $gruppen_id -> $nach_gruppen_id" );
  need_http_var( 'betrag', 'f' );
  need_http_var( 'gruppen_id', 'U' );
  need_http_var( 'nach_gruppen_id', 'U' );
  $notiz or need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need( sql_gruppe_aktiv( $gruppen_id ) );
  need( sql_gruppe_aktiv( $nach_gruppen_id ) );
  sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => $nach_gruppen_id )
  , array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
  , $betrag
  , "$valuta_year-$valuta_month-$valuta_day"
  , "$notiz"
  );
}

function formular_buchung_bank_bank( $notiz_initial = 'Überweisung' ) {
  open_form( '', 'action=buchung_bank_bank' );
    open_fieldset( '', '', 'Überweisung von Konto zu Konto' );
      open_table('layout');
        form_row_konto( 'von Konto:', 'konto_id' );
        form_row_kontoauszug( "<div class='right'>Auszug:</div>", 'auszug' );
        form_row_konto( 'an Konto:', 'nach_konto_id' );
        form_row_kontoauszug( "<div class='right'>Auszug:</div>", 'nach_auszug' );
        form_row_date( 'Valuta:', 'valuta' );
        form_row_betrag( 'Betrag' );
        form_row_text( 'Notiz:', 'notiz', 60, $notiz_initial );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_bank_bank() {
  global $betrag, $konto_id, $auszug_jahr, $auszug_nr
       , $nach_konto_id , $nach_auszug_jahr, $nach_auszug_nr
       , $notiz, $valuta_day, $valuta_month, $valuta_year;
  need_http_var( 'betrag', 'f' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  need_http_var( 'nach_konto_id', 'U' );
  need_http_var( 'nach_auszug_jahr', 'U' );
  need_http_var( 'nach_auszug_nr', 'U' );
  need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  sql_doppelte_transaktion(
    array( 'konto_id' => $konto_id, 'auszug_jahr' => $auszug_jahr, 'auszug_nr' => $auszug_nr )
  , array( 'konto_id' => $nach_konto_id, 'auszug_jahr' => $nach_auszug_jahr, 'auszug_nr' => $nach_auszug_nr )
  , $betrag
  , "$valuta_year-$valuta_month-$valuta_day"
  , "$notiz"
  );
}

function formular_buchung_bank_sonderausgabe() {
  open_form( '', 'action=buchung_bank_sonderausgabe' );
    open_fieldset( '', '', 'Überweisung Sonderausgabe' );
      open_table('layout');
        form_row_konto( 'von Konto:', 'konto_id' );
        form_row_kontoauszug( "<div class='right'>Auszug:</div>", 'auszug' );
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'positiv: Gewinn der FC / negativ: Verlust der FC' );
        form_row_betrag( 'Haben FC:' );
        form_row_text( 'Notiz:', 'notiz', 60 );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_bank_sonderausgabe() {
  global $betrag, $notiz, $valuta_day, $valuta_month, $valuta_year, $auszug_jahr, $auszug_nr, $konto_id;
  $problems = false;
  // echo "buchung_sonderausgabe: 1";
  need_http_var( 'betrag', 'f' );
  need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_SONDERAUSGABEN )
    , array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_buchung_gruppe_sonderausgabe() {
  open_form( '', 'action=buchung_gruppe_sonderausgabe' );
    open_fieldset( '', '', 'Sonderausgabe durch eine Gruppe' );
      open_table('layout');
        form_row_gruppe();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'positiv: Gewinn der FC / negativ: Verlust der FC' );
        form_row_betrag( 'Haben FC:' );
        form_row_text( 'Notiz:', 'notiz', 60 );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_gruppe_sonderausgabe() {
  global $betrag, $notiz, $valuta_day, $valuta_month, $valuta_year, $gruppen_id, $specialgroups;
  $problems = false;
  // echo "buchung_sonderausgabe: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $notiz or need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  $gruppen_id or need_http_var( 'gruppen_id', 'U' );
  if( ! $notiz ) {
    div_msg( 'warn', 'Bitte Notiz eingeben!' );
    $problems = true;
  }
  need( sql_gruppe_aktiv( $gruppen_id ) );
  need( sql_gruppenname( $gruppen_id ) );

  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_SONDERAUSGABEN )
    , array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_buchung_gruppe_anfangsguthaben() {
  open_form( '', 'action=buchung_gruppe_anfangsguthaben' );
    open_fieldset( '', '', 'Anfangsguthaben einer Gruppe eintragen' );
      open_table('layout');
        open_td( 'kommentar', "colspan='2'" )
          ?>
            Diese Funktion sollte normalerweise
            <em>nur bei Umstellung einer Foodcoop auf die Foodsoft</em> zur Erfassung der
            <em>schon vorhandenen Guthaben schon vorhandener Gruppen</em>
            benutzt werden, <em>nicht</em> im normalen Betrieb!
          <?php
        form_row_gruppe();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'positiv: Guthaben der Gruppe / negativ: Schulden der Gruppe' );
        form_row_betrag( 'Haben Gruppe:' );
        form_row_text( 'Notiz:', 'notiz', 60, 'Anfangsguthaben bei Umstellung auf die Foodsoft' );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_gruppe_anfangsguthaben() {
  global $betrag, $notiz, $valuta_day, $valuta_month, $valuta_year, $gruppen_id, $specialgroups;
  $problems = false;
  // echo "buchung_sonderausgabe: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $notiz or need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  $gruppen_id or need_http_var( 'gruppen_id', 'U' );
  if( ! $notiz ) {
    div_msg( 'warn', 'Bitte Notiz eingeben!' );
    $problems = true;
  }
  need( sql_gruppe_aktiv( $gruppen_id ) );
  need( sql_gruppenname( $gruppen_id ) );

  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_ANFANGSGUTHABEN )
    , $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_buchung_lieferant_anfangsguthaben() {
  open_form( '', 'action=buchung_lieferant_anfangsguthaben' );
    open_fieldset( '', '', 'Anfangsguthaben eines Lieferanten eintragen' );
      open_table('layout');
        open_td( 'kommentar', "colspan='2'" )
          ?>
            Diese Funktion sollte normalerweise
            <em>nur bei Umstellung einer Foodcoop auf die Foodsoft</em> zur Erfassung
            noch offener Rechnungen (Forderungen von Lieferanten an die FC)
            benutzt werden, <em>nicht</em> im laufenden Betrieb!
          <?php
        form_row_lieferant();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'positiv: offene Forderung des Lieferanten an die FC / negativ: Forderung der FC an Lieferant' );
        form_row_betrag( 'Haben Lieferant:' );
        form_row_text( 'Notiz:', 'notiz', 60, 'offene Rechnungen bei Umstellung auf die Foodsoft' );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_lieferant_anfangsguthaben() {
  global $betrag, $notiz, $valuta_day, $valuta_month, $valuta_year, $lieferanten_id, $specialgroups;
  $problems = false;
  // echo "buchung_sonderausgabe: 1";
  $betrag or need_http_var( 'betrag', 'f' );
  $notiz or need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need_http_var( 'lieferanten_id', 'U' );
  if( ! $notiz ) {
    div_msg( 'warn', 'Bitte Notiz eingeben!' );
    $problems = true;
  }

  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => -1, 'lieferanten_id' => $lieferanten_id )
    , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_ANFANGSGUTHABEN )
    , $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_buchung_bank_anfangsguthaben() {
  open_form( '', 'action=buchung_bank_anfangsguthaben' );
    open_fieldset( '', '', 'Anfangskontostand eintragen' );
      open_table('layout');
        open_td( 'kommentar', "colspan='2'" )
          ?>
            Diese Funktion sollte normalerweise
            <em>nur bei Umstellung einer Foodcoop auf die Foodsoft</em> zur Erfassung
            <em>des Anfangskontostands bei Umstellung</em>
            benutzt werden, <em>nicht</em> im laufenden Betrieb!
          <?php
        form_row_konto( 'von Konto:', 'konto_id' );
        form_row_kontoauszug( "<div class='right'>Auszug:</div>", 'auszug' );
        form_row_date( 'Valuta:', 'valuta' );
        form_row_betrag( 'Kontostand:' );
        form_row_text( 'Notiz:', 'notiz', 60, 'Anfangskontostand bei Umstellung auf die Foodsoft' );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_buchung_bank_anfangsguthaben() {
  global $betrag, $notiz, $valuta_day, $valuta_month, $valuta_year, $auszug_jahr, $auszug_nr, $konto_id;
  $problems = false;
  // echo "buchung_sonderausgabe: 1";
  need_http_var( 'betrag', 'f' );
  need_http_var( 'notiz', 'H' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_ANFANGSGUTHABEN )
    , - $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_umbuchung_verlust( $typ = 0 ) {
  open_form( '', "action=umbuchung_verlust" );
    open_fieldset( '', '', 'Umbuchung Verlustausgleich' );
      open_table('layout');
          open_td( 'label', '', 'von:' );
          open_td( 'kbd' );
            if( $typ ) { 
              need( in_array( $typ, array( TRANSAKTION_TYP_SPENDE, TRANSAKTION_TYP_UMLAGE ) ) );
              echo transaktion_typ_string( $typ );
              hidden_input( 'von_typ', $typ );
            } else {
              open_select( 'von_typ' );
                ?> <option value=''>(bitte Quelle w&auml;hlen)</option> <?php
                foreach( array( TRANSAKTION_TYP_SPENDE , TRANSAKTION_TYP_UMLAGE ) as $t ) {
                   ?> <option value='<?php echo $t; ?>'><?php echo transaktion_typ_string($t); ?></option> <?php
                 }
              close_select();
            }
        open_tr();
          open_td( 'label', '', 'nach:' );
          open_td( 'kbd' );
            open_select( 'nach_typ' );
              ?> <option value=''>(bitte Ziel w&auml;hlen)</option> <?php
              foreach( array( TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN
                            , TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN
                            , TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE ) as $t ) {
                ?> <option value='<?php echo $t; ?>'><?php echo transaktion_typ_string($t); ?></option> <?php
              }
            close_select();
        form_row_date( 'Valuta:', 'valuta' );
        form_row_betrag( 'Betrag:' );
        form_row_text( 'Notiz:', 'notiz', 60 );
          quad();
          submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_umbuchung_verlust() {
  global $von_typ, $nach_typ, $valuta_day, $valuta_month, $valuta_year, $betrag, $notiz;

  need_http_var( 'von_typ', 'U' );
  need_http_var( 'nach_typ', 'U' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need_http_var( 'betrag', 'f' );
  need_http_var( 'notiz', 'H' );
  need( in_array( $von_typ, array( TRANSAKTION_TYP_SPENDE, TRANSAKTION_TYP_UMLAGE ) ) );
  need( in_array( $nach_typ, array( TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN
                                  , TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN
                                  , TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE ) ) );
  switch( $von_typ ) {
    case TRANSAKTION_TYP_SPENDE:
      $von_typ = TRANSAKTION_TYP_UMBUCHUNG_SPENDE;
      break;
    case TRANSAKTION_TYP_UMLAGE:
      $von_typ = TRANSAKTION_TYP_UMBUCHUNG_UMLAGE;
  }
  if( ! $notiz ) {
    div_msg( 'warn', 'Bitte Notiz eingeben!' );
    return false;
  }
  sql_doppelte_transaktion(
    array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => $nach_typ )
  , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => $von_typ )
  , $betrag
  , "$valuta_year-$valuta_month-$valuta_day"
  , "$notiz"
  );
}

function formular_gruppen_umlage() {
  open_form( '', 'action=gruppen_umlage' );
    open_fieldset( '', '', 'Verlustumlage auf Gruppenmitglieder' );
      open_table( 'layout' );
          open_td( '', "colspan='2'", "Von <span class='bold italic'>allen aktiven Bestellgruppen</span> eine Umlage" );
        form_row_betrag( 'in Höhe von' );
          echo " EUR je Gruppenmitglied erheben";
        form_row_date( 'Valuta:', 'valuta' );
        form_row_text( 'Notiz:', 'notiz', 60 );
          quad();
          submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function action_gruppen_umlage() {
  global $valuta_day, $valuta_month, $valuta_year, $betrag, $notiz;

  $problems = false;
  need_http_var( 'betrag', 'f' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );
  need_http_var( 'notiz', 'H' );
  if( ! $notiz ) {
    div_msg( 'warn', 'Bitte Notiz eingeben!' );
    $problems = true;
  }
  // echo "action_gruppen_umlage:";
  if( ! $problems ) {
    foreach( sql_gruppen( array( 'aktiv' => 'true' ) ) as $gruppe ) {
      if( $gruppe['mitgliederzahl'] > 0 ) {
        sql_doppelte_transaktion(
          array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_UMLAGE )
        , array( 'konto_id' => -1, 'gruppen_id' => $gruppe['id'], 'transaktionsart' => TRANSAKTION_TYP_UMLAGE )
        , $betrag * $gruppe['mitgliederzahl']
        , "$valuta_year-$valuta_month-$valuta_day"
        , "$notiz"
        );
      }
    }
  }
}

function mod_onclick( $id ) {
  return $id ? " onclick=\"document.getElementById('$id').className='modified';\" " : '';
}

function formular_artikelnummer( $produkt_id, $toggle = false, $bestell_id = 0 ) {
  $produkt = sql_produkt( $produkt_id );
  $anummer = $produkt['artikelnummer'];
  $lieferanten_id = $produkt['lieferanten_id'];

  open_fieldset( 'small_form', '', "Artikelnummer ($anummer) &auml;ndern", $toggle );
    open_table( 'layout' );
        open_td( '', '' );
          open_form( '', 'action=artikelnummer_setzen' );
            ?> neue Artikel-Nr. setzen: <?php
            echo string_view( $anummer, 20, 'anummer' );
            quad(); submission_button( 'Speichern', true );
          close_form();
      open_tr();
        open_td();
          open_form( "window=artikelsuche,produkt_id=$produkt_id,lieferanten_id=$lieferanten_id,bestell_id=$bestell_id", 'action=search' );
            ?>...oder: Katalogsuche nach: <?php
            echo string_view( $produkt['name'], 40, 'name' );
            quad(); submission_button( 'Los!' );
          close_form();
    close_table();
  close_fieldset();
}


// formular_produktpreis:
//   $vorschlag: may contain new values to suggest for form fields
//
function formular_produktpreis( $produkt_id, $vorschlag = array() ) {
  global $mwst_default;

  $preis_id = sql_aktueller_produktpreis_id( $produkt_id );
  $produkt = sql_produkt( array( 'produkt_id' => $produkt_id, 'preis_id' => $preis_id ) );

  // besetze $vorschlag mit Werten fuer Formularfelder; benutze nacheinander
  //  - existierende Werte in $vorschlag (typischerweise: automatisch aus lieferantenkatalog entnommen)
  //  - existierende Werte aus $produkt
  //  - vernuenftigen Default

  if( ! isset( $vorschlag['gebindegroesse'] ) )
    $vorschlag['gebindegroesse'] = $preis_id ? $produkt['gebindegroesse'] : 1;

  if( ! isset( $vorschlag['verteileinheit'] ) )
    if( $preis_id )
      $vorschlag['verteileinheit'] =
        ( ( $produkt['kan_verteilmult'] > 0.0001 ) ? $produkt['kan_verteilmult'] : 1 )
        . ( $produkt['kan_verteileinheit'] ? " {$produkt['kan_verteileinheit']} " : ' ST' );
    else
      $vorschlag['verteileinheit'] = '1 ST';

  if( ! isset( $vorschlag['liefereinheit'] ) )
    $vorschlag['liefereinheit'] = $preis_id ? "{$produkt['kan_liefermult']} {$produkt['kan_liefereinheit']}"
                                           : $vorschlag['verteileinheit'];
  if( ! isset( $vorschlag['lv_faktor'] ) )
    $vorschlag['lv_faktor'] = 1;

  if( ! isset( $vorschlag['mwst'] ) )
    $vorschlag['mwst'] = $preis_id ? $produkt['mwst'] : $mwst_default;

  if( ! isset( $vorschlag['pfand'] ) )
    $vorschlag['pfand'] = $preis_id ? $produkt['pfand'] : '0.00';

  if( ! isset( $vorschlag['lieferpreis'] ) )
    $vorschlag['lieferpreis'] = $preis_id ? $produkt['nettolieferpreis'] : '0.00';

  if( ! isset( $vorschlag['bestellnummer'] ) )
    $vorschlag['bestellnummer'] = $preis_id ? $produkt['bestellnummer'] : '';

  if( ! isset( $vorschlag['notiz'] ) )
    $vorschlag['notiz'] = $produkt['notiz'];  // braucht _keinen_ gueltigen preiseintrag!

  // restliche felder automatisch berechnen:
  //
  $vorschlag = preisdatenSetzen( $vorschlag );

  $form_id = open_form( '', 'action=neuer_preiseintrag' );

    open_table('layout');
      form_row_text( 'Produkt:', false, 1, "{$produkt['name']} von {$produkt['lieferant_name']}" );

      tr_title( 'Notiz: zum Beispiel aktuelle Herkunft, Verband oder Lieferant' );
      form_row_text( 'Notiz:', 'notiz', 42, $vorschlag['notiz'] );

      form_row_text( 'Bestell-Nr:', 'bestellnummer', 8, $vorschlag['bestellnummer'] );
        ?>
        <label class='qquad'>MWSt:</label>
           <input type='text' size='4' class='number' name='mwst' id='newmwst'
            value='<?php echo $vorschlag['mwst']; ?>' title='Mehrwertsteuer-Satz in Prozent'
            onchange='preisberechnung_vorwaerts();'>

        <label class='qquad'>Pfand:</label>
           <input type='text' class='number' size='4' name='pfand' id='newpfand'
            value='<?php printf( "%.2lf", $vorschlag['pfand'] ); ?>'
            title='Pfand pro V-Einheit, bei uns immer 0.00 oder 0.16'
            onchange='preisberechnung_vorwaerts();'>
        <?php

      open_tr();  // lieferpreis und liefereinheit

        open_td( 'label', "title='Katalogpreis (Netto, ohne Pfand) des Lieferanten'", 'L-Preis:' );
        open_td();
        ?>
           <span onmouseover="help('L-Preis: Netto: der Einzelpreis aus dem Katalog des Lieferanten (ohne MWSt, ohne Pfand)');"
                 onmouseout="help(' ');" >
           <input title='Nettopreis' class='number' type='text' size='8' id='newlieferpreis' name='lieferpreis'
             value='<?php printf( "%.2lf", $vorschlag['nettolieferpreis'] ); ?>'
             onchange='preisberechnung_vorwaerts();'>
           </span>
        <span style='padding:1ex;'>/</span>
        Liefer-Einheit:
           <span onmouseover="help('Liefer-Einheit: die Menge, für die der Einzelpreis aus dem Katalog gilt');"
                 onmouseout="help(' ');" >
           <input type='text' size='4' class='number' name='liefermult' id='newliefermult'
             value='<?php echo $vorschlag['kan_liefermult']; ?>'
             title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
             onchange='preisberechnung_vorwaerts();'>
           <select size='1' name='liefereinheit' id='newliefereinheit'
             onchange='preisberechnung_vorwaerts();'>
               <?php echo optionen_einheiten( $vorschlag['kan_liefereinheit'] ); ?>
           </select>
           </span>
         <?php

      open_tr();  // vpreis und verteileinheit

        open_td( 'label', "title='Endverbraucher-Preis (Brutto, mit Pfand)'", 'V-Preis:' );
        open_td();
        ?>
           <span onmouseover="help('Verbraucher-Preis: der Preis für die Gruppen (mit MWSt und Pfand) je Verteileinheit');"
                 onmouseout="help(' ');" >
           <input title='Preis incl. MWSt und Pfand' class='number' type='text' size='8' id='newvpreis' name='vpreis'
             value='<?php printf( '%.4lf', $vorschlag['vpreis'] ); ?>'
             onchange='preisberechnung_rueckwaerts();'>
           </span>
        <span style='padding:1ex;'>/</span>
           Verteil-Einheit:
           <span onmouseover="help('Verteileinheit: Vielfache davon können die Gruppen bestellen - wählt hier eine sinnvolle Größe, etwa 1 ST bei abgepackten Sachen, 500g bei Gemüse, 100g bei Käse');"
                 onmouseout="help(' ');" >
           <input type='text' size='4' class='number' name='verteilmult' id='newverteilmult'
             value='<?php echo $vorschlag['kan_verteilmult']; ?>'
             title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
             onchange='preisberechnung_vorwaerts();'>
           <select size='1' name='verteileinheit' id='newverteileinheit'
             onchange='preisberechnung_vorwaerts();'>
               <?php echo optionen_einheiten( $vorschlag['kan_verteileinheit'] ); ?>
           </select>
           </span>
        <?php

      open_tr(); // gebinde
           open_td( 'label', '', 'Gebindegr&ouml;&szlig;e:' );
           open_td();
           ?>
           <span onmouseover="help('Gebindegroesse: wieviel von diesem Produkt muessen wir auf einmal bestellen --- muss ein Vielfaches fer V-Einheit sein!');"
                 onmouseout="help(' ');" >
           <input type='text' size='4' class='number' name='gebindegroesse' id='newgebindegroesse'
             value='<?php echo mult2string( $vorschlag['gebindegroesse'] / $vorschlag['lv_faktor'] ) ; ?>'
             onchange='preisberechnung_vorwaerts();'>
           * <span id='gebindegroesse_liefereinheit']>
               <?php echo $vorschlag['kan_liefermult']; ?>
               <?php echo $vorschlag['kan_liefereinheit']; ?>
             </span>
           </span>
         <?php

        if( $vorschlag['kan_verteileinheit'] != $vorschlag['kan_liefereinheit'] )
          $display = 'inline';
        else
          $display = 'none';
        open_span( '', "style='padding-left:3em;display:$display;' id='umrechnung_einheiten'" );
          ?>
           <span onmouseover="help('Umrechnung: hier müsst ihr der Software helfen, die Liefereinheit in die Verteileinheit umzurechnen!');"
                 onmouseout="help(' ');" >
          Umrechnung der Einheiten:
            <span id='umrechnung_liefereinheit'><?php echo "{$vorschlag['kan_liefermult']} {$vorschlag['kan_liefereinheit']}"; ?></span>
            =
            <input type='text' size='6' class='number' name='lv_faktor' id='newlv_faktor' value='<?php echo mult2string( $vorschlag['lv_faktor'] ); ?>'
             onchange='preisberechnung_vorwaerts();'>
            *  <span id='umrechnung_verteileinheit'><?php echo "{$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}"; ?></span>
           </span>
          <?php
        close_span();

      open_tr();
        open_td( 'label', '', 'gültig ab:' );
        open_td();
          date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') );
          qquad();
          submission_button( 'OK' );
      if( false ){
            qquad();
            ?> <label>Dynamische Neuberechnung:</label>
               <input name='dynamischberechnen' type='checkbox' value='yes'
                 title='Dynamische Berechnung anderer Felder bei Änderung eines Eintrags' checked>
            <?php
       }
    close_table();
    open_div( 'kommentar bottom', "id='preisform_hinweise' style='height:1em;padding:1em;'", ' ' );
  close_form();

  ?>
  <script type="text/javascript">

    var mwst, pfand, verteilmult, verteileinheit, preis, gebindegroesse_in_liefereinheiten,
      liefermult, liefereinheit, lieferpreis, lv_faktor;

    var preisform = '<?php echo "form_$form_id"; ?>';

    // vorwaerts: lieferpreis berechnen
    //
    var vorwaerts = 0;

    function preiseintrag_auslesen() {
      mwst = parseFloat( document.forms[preisform].newmwst.value );
      pfand = parseFloat( document.forms[preisform].newpfand.value );
      verteilmult = parseFloat( document.forms[preisform].newverteilmult.value );
      verteileinheit = document.forms[preisform].newverteileinheit.value;
      liefermult = parseFloat( document.forms[preisform].newliefermult.value );
      liefereinheit = document.forms[preisform].newliefereinheit.value;
      vpreis = parseFloat( document.forms[preisform].newvpreis.value );
      lieferpreis = parseFloat( document.forms[preisform].newlieferpreis.value );
      gebindegroesse_in_liefereinheiten = parseFloat( document.forms[preisform].newgebindegroesse.value );
      lv_faktor = parseFloat( document.forms[preisform].newlv_faktor.value );
      if( liefermult < 0.01 )
        liefermult = 0.01;
      if( verteilmult < 0.01 )
        verteilmult = 0.01;
      if( liefereinheit == verteileinheit )
        lv_faktor = liefermult / verteilmult;
      // init lv_faktor; kludge alert - we really need Q-numbers here!
      if( lv_faktor < 0.001 )
        lv_faktor = 1;
    }

    preiseintrag_auslesen();

    function preiseintrag_update() {
      document.forms[preisform].newmwst.value = mwst;
      document.forms[preisform].newpfand.value = pfand;
      document.forms[preisform].newverteilmult.value = verteilmult;
      document.forms[preisform].newverteileinheit.value = verteileinheit;
      document.forms[preisform].newvpreis.value = vpreis;
      document.forms[preisform].newgebindegroesse.value = gebindegroesse_in_liefereinheiten;
      document.forms[preisform].newliefermult.value = liefermult;
      document.forms[preisform].newliefereinheit.value = liefereinheit;
      document.forms[preisform].newlieferpreis.value = lieferpreis;
      document.forms[preisform].newlv_faktor.value = ( lv_faktor.toPrecision(3) );
      document.getElementById("gebindegroesse_liefereinheit").firstChild.nodeValue = liefermult + ' ' + liefereinheit;
      document.getElementById("umrechnung_liefereinheit").firstChild.nodeValue = liefermult + ' ' + liefereinheit;
      document.getElementById("umrechnung_verteileinheit").firstChild.nodeValue = verteilmult + ' ' + verteileinheit;
      if( liefereinheit == verteileinheit )
        document.getElementById("umrechnung_einheiten").style.display = 'none';
      else
        document.getElementById("umrechnung_einheiten").style.display = 'inline';
    }

    function preisberechnung_rueckwaerts() {
      vorwaerts = 0;
      preiseintrag_auslesen();
      berechnen = true; // document.forms[preisform].dynamischberechnen.checked;
      if( berechnen ) {
        lieferpreis = 
          parseInt( 0.499 + 100 * ( vpreis - pfand ) / ( 1.0 + mwst / 100.0 ) * lv_faktor ) / 100.0;
      }
      preiseintrag_update();
    }

    function preisberechnung_vorwaerts() {
      vorwaerts = 1;
      preiseintrag_auslesen();
      berechnen = true; // document.forms[preisform].dynamischberechnen.checked;
      if( berechnen ) {
        vpreis = 
          parseInt( 0.499 + 10000 * ( lieferpreis * ( 1.0 + mwst / 100.0 ) / lv_faktor + pfand ) ) / 10000.0;
      }
      preiseintrag_update();
    }

    function preisberechnung_default() {
      if( vorwaerts )
        preisberechnung_vorwaerts();
      else
        preisberechnung_rueckwaerts();
    }

    function help(s) {
      document.getElementById('preisform_hinweise').firstChild.nodeValue = s;
    }

  </script>
  <?php
}

function action_form_produktpreis() {
  global $name, $verteilmult, $verteileinheit, $liefermult, $liefereinheit
       , $gebindegroesse, $mwst, $pfand, $lieferpreis, $bestellnummer, $lv_faktor
       , $day, $month, $year, $notiz, $produkt_id;
       
  $unit_pattern = '/^[a-zA-ZÄäÖöÜüß]+$/';

  need_http_var('produkt_id','u');

  // get_http_var('name','H','');  // notwendig, sollte aber moeglichst nicht geaendert werden!
  need_http_var('verteilmult','f');
  $verteilmult = mult2string( $verteilmult ); // ...maximal 3 nachkommastellen, und nur wenn noetig!
  need_http_var('verteileinheit',$unit_pattern);
  need_http_var('liefermult','f');
  $liefermult = mult2string( $liefermult );
  need_http_var('liefereinheit',$unit_pattern);

  need_http_var('gebindegroesse','f'); // in liefereinheiten!
  need_http_var('mwst','f');
  need_http_var('pfand','f');
  need_http_var('lieferpreis','f');
  need_http_var('lv_faktor','f');
  need_http_var('bestellnummer','H','');
  need_http_var('day','u');
  need_http_var('month','u');
  need_http_var('year','u');
  need_http_var('notiz','H');

  $gebindegroesse *= $lv_faktor;
  // kludge alert: rundungsfehler korrigieren (gebindegroesse muss ganzzahlig und >= 1 sein!)
  // (eigentlich brauchen wir Q-arithnetik fuer den lv_faktor)
  $gebindegroesse = floor( $gebindegroesse + 0.02 );

  $produkt = sql_produkt( $produkt_id );

  // if( "$name" and ( "$name" != $produkt['name'] ) ) {
  //  sql_update( 'produkte', $produkt_id, array( 'name' => $name ) );
  // }
  if( "$notiz" != $produkt['notiz'] ) {
    sql_update( 'produkte', $produkt_id, array( 'notiz' => $notiz ) );
  }

  sql_insert_produktpreis(
    $produkt_id, $lieferpreis, "$year-$month-$day", $bestellnummer, $gebindegroesse, $mwst, $pfand
  , "$liefermult $liefereinheit", "$verteilmult $verteileinheit", $lv_faktor
  );
}

// fieldset_edit_transaction: ediert eine transaktion der beiden transaktionen einer buchung.
// $tag: 1 oder 2:
//  - felder die in beiden transactions identisch sein muessen, werden nur bei $tag == 1 angezeigt
//  - $tag wird an feldnamen angehaengt, um beide transaktionen unterscheiden zu koennen.
//
function fieldset_edit_transaction( $id, $tag, $editable ) {
  global $selectable_types;

  $muell_id = sql_muell_id();
  $t = sql_get_transaction( $id );

  $haben = $t['haben'];
  $soll = -$haben;

  hidden_input( "id_$tag", $id );

  if( $tag == 1 ) {
    open_tr();
      open_td('label', '', 'Buchung:' );
      open_td('kbd' );
        open_div('kbd', '', $t['buchungsdatum'] );
        open_div('kbd small', '', $t['dienst_name'] );
    form_row_date( 'Valuta:', $editable ? 'valuta' : false, $t['valuta'] );
    form_row_text( 'Notiz:', $editable ? 'notiz' : false, 42, $t['kommentar'] );
  }

  open_tr();
    open_td( 'smallskip' );
  if( $id > 0 ) {  // bank-transaktion
    open_tr();
      open_th( 'smallskip', "colspan='2'", "Bank-Transaktion <span class='small'>$id</span>" );
    form_row_konto( 'Konto:', false, $t['konto_id'] );   // TODO: make this editable?
    form_row_kontoauszug( 'Kontoauszug:', $editable ? "auszug_$tag" : false, $t['kontoauszug_jahr'], $t['kontoauszug_nr'] ); 
    tr_title( 'Haben FC: positiv, falls zu unseren Gunsten (wie auf Kontoauszug der Bank)' );
    form_row_betrag( 'Haben FC:', ( $editable and $tag == 1 ) ? 'haben' : false, $haben );

  } else {  // lieferant / gruppe / muell-transaktion
    $id = -$id;
    $gruppen_id = $t['gruppen_id'];
    $lieferanten_id = $t['lieferanten_id'];

    if( $lieferanten_id > 0 ) {
      open_tr();
        open_th( 'smallskip', "colspan='2'", "Lieferanten-Transaktion <span class='small'>$id</span>" );
      form_row_lieferant( 'Lieferant:', false, $t['lieferanten_id'] );  // TODO: make this editable?
      if( $haben > 0 ) {
        tr_title( 'Haben FC: positiv, falls wir unsere Schulden beim Lieferanten verringern' );
        form_row_betrag( 'Haben FC:', ( $editable and $tag == 1 ) ? 'haben' : false, $haben );
      } else {
        tr_title( 'Soll FC: positiv, falls wir unsere Schulden beim Lieferanten vergroessern' );
        form_row_betrag( 'Soll FC:', ( $editable and $tag == 1 ) ? 'soll' : false, $soll );
      }

    } else if( $gruppen_id == $muell_id ) {
      open_tr();
        open_th( 'smallskip', "colspan='2'", "Interne Verrechnung <span class='small'>$id</span>" );
      open_tr();
        open_td( 'label', '', 'Typ:' );
        open_td( 'kbd' );
          $typ = $t['transaktionstyp'];
          $options = '';
          $selected = false;
          foreach( $selectable_types as $tt ) {
            $options .= "<option value='".$tt."'";
            if( $tt == $typ ) {
              $options .= " selected";
              $selected = true;
            }
            $options .= ">" . transaktion_typ_string($tt) . "</option>";
          }
          if( ! $selected ) {
            $options = "<option value=''>(bitte Typ wählen)</option>$options";
          }
          if( $editable and ( $selected or ( $typ == TRANSAKTION_TYP_UNDEFINIERT ) ) ) {
            open_select( "typ_$tag" );
              echo $options;
            close_select();
          } else {
            echo transaktion_typ_string( $typ );
          }

          if( $soll > 0 ) {
            tr_title( 'Soll FC: positiv, falls wir GEWINN gemacht haben (SIC! siehe Hilfe im Wiki!)' );
            form_row_betrag( 'Soll FC:', ( $editable and $tag == 1 ) ? 'soll' : false, $soll );
          } else {
            tr_title( 'Haben FC: positiv, falls wir VERLUST gemacht haben (SIC! siehe Hilfe im Wiki!)' );
            form_row_betrag( 'Haben FC:', ( $editable and $tag == 1 ) ? 'haben' : false, $haben );
          }

    } else {  // regulaere (nicht-13) gruppen-transaktion
      open_tr();
        open_th( 'smallskip', "colspan='2'", "Gruppen-Transaktion <span class='small'>$id</span>" );
      form_row_gruppe( 'Gruppe:', false, $t['gruppen_id'] );  // TODO: make this editable?
      tr_title( 'Soll FC: positiv, wenn wir der Gruppe jetzt mehr Geld schulden' );
      form_row_betrag( 'Soll FC:', ( $editable and $tag == 1 ) ? 'soll' : false, $soll );
    }
  }
}

?>
