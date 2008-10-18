<?

// functions to output one row of a form
//
// if a $fieldname is alread part of $self_fields (ie, part of the current view), the value
// will just be printed and cannot be modified (only applies to types that can be in $self_fields).
// the line will not be closed; so e.g. a submission_button() can be appended to the last row
//

function form_row_konto( $label = 'Konto:', $fieldname = 'konto_id', $initial = 0 ) {
  open_tr();
    open_td( 'label', '', $label );
    if( ( $konto_id = self_field( $fieldname ) ) === NULL )
      $konto_id = $initial;
    else
      $fieldname = false;
    open_td( 'kbd' ); echo konto_view( $konto_id, $fieldname );
}

function form_row_kontoauszug( $label = 'Kontoauszug:', $fieldname = 'auszug', $initial_jahr = 0, $initial_nr = 0 ) {
  open_tr();
    open_td( 'label', '', $label );
    $auszug_jahr = self_field( $fieldname.'_jahr' );
    $auszug_nr = self_field( $fieldname.'_nr' );
    if( $auszug_jahr !== NULL and $auszug_nr !== NULL )
      $fieldname = false;
    if( $auszug_jahr === NULL )
      $auszug_jahr = $initial_jahr;
    if( $auszug_nr === NULL )
      $auszug_nr = $initial_nr;
    open_td( 'kbd' ); echo kontoauszug_view( 0, $auszug_jahr, $auszug_nr, $fieldname );
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
  $year = self_field( $fieldname.'_jahr' );
  $month = self_field( $fieldname.'_monat' );
  $day = self_field( $fieldname.'_tag' );
  if( ($year !== NULL) and ($day !== NULL) and ($month !== NULL) ) {
    $date = "$year-$month-$day";
    $fieldname = false;
  } else {
    $date = $initial;
  }
  open_tr();
    open_td( 'label', '', $label );
    open_td( 'kbd' ); echo date_view( $date, $fieldname );
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



function form_finish_transaction( $transaction_id, $konto_id = 0, $auszug_jahr = 0, $auszug_nr = 0 ) {
  $trans = sql_get_transaction( $transaction_id );
  open_form('', '', '', "action=finish_transaction,transaction_id=$transaction_id" );
    open_table('layout');
      $konto_id or form_row_konto();
      ( $auszug_jahr and $auszug_nr ) or form_row_auszug();
      form_row_date( 'Valuta:', 'valuta' );
      submission_button( 'Best&auml;tigen' );
    close_table();
  close_form();
}

function action_finish_transaction() {
  global $transaction_id, $konto_id, $auszug_jahr, $auszug_nr, $valuta_day, $valuta_month, $valuta_year;
  global $dienstkontrollblatt_id;
  need_http_var( 'transaction_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'valuta_day', 'U' );
  need_http_var( 'valuta_month', 'U' );
  need_http_var( 'valuta_year', 'U' );

  fail_if_readonly();
  nur_fuer_dienst(4);

  $soll_id = -$transaction_id;
  $soll_transaction = sql_get_transaction( $soll_id );

  $haben_id = sql_bank_transaktion(
    $konto_id, $auszug_jahr, $auszug_nr
  , $soll_transaction['soll'], "$valuta_year-$valuta_month-$valuta_day"
  , $dienstkontrollblatt_id, $notiz, 0
  );

  sql_link_transaction( $soll_id, $haben_id );

  return sql_update( 'gruppen_transaktion', $transaction_id, array(
    'dienstkontrollblatt_id' => $dienstkontrollblatt_id
  ) );
}




function formular_buchung_gruppe_bank( $notiz_initial = 'Einzahlung' ) {
  open_form( 'small_form', '', '', 'action=buchung_gruppe_bank' );
    open_fieldset( 'small_form', '', 'Einzahlung / Auszahlung Gruppe' );
      open_table('layout');
        form_row_gruppe();
        form_row_konto();
        form_row_kontoauszug();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'Betrag: positiv bei Einzahlung, negativ bei Auszahlung' );
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
  need( sql_gruppenname( $gruppen_id ) );

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
  open_form( 'small_form', '', '', 'action=buchung_lieferant_bank' );
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
    array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
  , array( 'konto_id' => -1, 'lieferanten_id' => $lieferanten_id )
  , $betrag
  , "$valuta_year-$valuta_month-$valuta_day"
  , "$notiz"
  );
}

function formular_buchung_gruppe_lieferant( $notiz_initial = 'Zahlung an Lieferant' ) {
  open_form( 'small_form', '', '', 'action=buchung_gruppe_lieferant' );
    open_fieldset( 'small_form', '', 'Zahlung von Gruppe an Lieferant' );
      open_table('layout');
        form_row_gruppe();
        form_row_lieferant();
        form_row_date( 'Valuta:', 'valuta' );
        tr_title( 'Betrag: positiv: Zahlung an Lieferant / negativ: Zahlung an Gruppe' );
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
  open_form( 'small_form', '', '', 'action=buchung_gruppe_gruppe' );
    open_fieldset( '', '', 'Umbuchung von Gruppe an Gruppe' );
      open_table('layout');
        form_row_gruppe( 'von Gruppe:', 'gruppen_id' );
        form_row_gruppe( 'an Gruppe:', 'nach_gruppen_id' );
        form_row_date( 'Valuta:', 'valuta' );
        form_row_betrag( 'Haben Lieferant:' );
        form_row_text( 'Notiz:', 'notiz', 60, $notiz_initial );
        quad();
        submission_button( 'OK' );
      close_table();
    close_fieldset();
  close_form();
}

function buchung_gruppe_gruppe() {
  global $betrag, $gruppen_id, $nach_gruppen_id, $notiz, $valuta_day, $valuta_month, $valuta_year;
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
  , "$year-$month-$day"
  , "$notiz"
  );
}

function formular_buchung_bank_bank( $notiz_initial = 'Überweisung' ) {
  open_form( 'small_form', '', '', 'action=buchung_gruppe_gruppe' );
    open_fieldset( '', '', 'Überweisung von Konto zu Konto' );
      open_table('layout');
        form_row_konto( 'von Konto:', 'konto_id' );
        form_row_kontoauszug( "<div class='right'>Auszug:</div>", 'auszug' );
        form_row_konto( 'an Konto:', 'nach_konto_id' );
        form_row_kontoauszug( "<div class='right'>Auszug:</div>", 'nach_auszug' );
        form_row_date( 'Valuta:', 'valuta' );
        form_row_betrag( 'Haben Lieferant:' );
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
  open_form( 'small_form', '', '', 'action=buchung_bank_sonderausgabe' );
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
  need_http_var( 'day', 'U' );
  need_http_var( 'month', 'U' );
  need_http_var( 'year', 'U' );
  need_http_var( 'konto_id', 'U' );
  need_http_var( 'auszug_jahr', 'U' );
  need_http_var( 'auszug_nr', 'U' );
  if( ! $problems ) {
    sql_doppelte_transaktion(
      array( 'konto_id' => $konto_id, 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_SONDERAUSGABEN )
    , $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_buchung_gruppe_sonderausgabe() {
  open_form( 'small_form', '', '', 'action=buchung_gruppe_sonderausgabe' );
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
      array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_SONDERAUSGABEN )
    , $betrag
    , "$valuta_year-$valuta_month-$valuta_day"
    , "$notiz"
    );
  }
}

function formular_umbuchung_verlust( $typ = 0 ) {
  open_form( 'small_form', '', '', "action=umbuchung_verlust,typ=$typ" );
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
                ?> <option value=''>(bitte Quelle w&auml;hlen)</option> <?
                foreach( array( TRANSAKTION_TYP_SPENDE , TRANSAKTION_TYP_UMLAGE ) as $t ) {
                   ?> <option value='<? echo $t; ?>'><? echo transaktion_typ_string($t); ?></option> <?
                 }
              close_select();
            }
        open_tr();
          open_td( 'label', '', 'nach:' );    
          open_td( 'kbd' );
            open_select( 'nach_typ' );
              ?> <option value=''>(bitte Ziel w&auml;hlen)</option> <?
              foreach( array( TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN
                            , TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN
                            , TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE ) as $t ) {
                ?> <option value='<? echo $t; ?>'><? echo transaktion_typ_string($t); ?></option> <?
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
    break;
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
  open_form( 'small_form', '', '', 'action=buchung_umlage' );
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
  if( ! $problems ) {
    foreach( sql_aktive_bestellgruppen() as $gruppe ) {
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

function formular_artikelnummer( $produkt_id, $toggle = false, $mod_id = false ) {
  $produkt = sql_produkt_details( $produkt_id );
  $anummer = $produkt['artikelnummer'];
  $lieferanten_id = $produkt['lieferanten_id'];

  open_fieldset( 'small_form', '', "Artikelnummer ($anummer) &auml;ndern", $toggle );
    open_table( 'layout' );
        open_td( '', '', 'neue Artikel-Nr. setzen:' );
          open_form( 'small_form', '', '', 'action=artikelnummer_setzen' );
            echo string_view( $anummer, 20, 'anummer' );
            submission_button();
          close_form();
      open_tr();
        open_td( '', '', '...oder: Katalogsuche nach:' );
          open_form('small_form','','', "produkt_id=$produkt_id,lieferanten_id=$lieferanten_id");
            echo string_view( $produkt['name'], 40, 'name' );
            echo fc_link( 'artikelsuche', 'text=Los!,form,class=button' );
          close_form();
    close_table();
  close_fieldset();
}

?>
