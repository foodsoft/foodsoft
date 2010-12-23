<?php

assert( $angemeldet ) or exit();

setWindowSubtitle( 'Buchung edieren' );
setWikiHelpTopic( 'foodsoft:buchung_edieren' );

$editable = ( hat_dienst(4) and ! $readonly );

$msg = '';
$problems = '';

$muell_id = sql_muell_id();

if( get_http_var( 'transaktion_id', 'U', NULL, true ) )
  $buchung_id = -$transaktion_id;
else
  need_http_var( 'buchung_id','U', true );

$buchung = sql_get_transaction( $buchung_id ); 
$k_id = $buchung['konterbuchung_id'];
if( ! $k_id ) {
  div_msg( 'kommentar', "
    Buchung ist nicht vollstaendig oder fehlerhaft.
    Falls es sich um eine noch unbestätigte Einzahlung handelt: bitte erst bestätigen!
  " );
  return;
}

$k_buchung = sql_get_transaction( $k_id ); 

// waehle eine (hoffentlich) leicht verstaendliche / kanonische reihenfolge der beiden Buchungen:
//
if( $buchung_id < 0 ) {
  if( $k_id > 0 ) { //  wenn eine bank-transaktion dabei: diese zuerst anzeigen!
    $h = $buchung_id;
    $buchung_id = $k_id;
    $k_id = $h;
  } else {  // beides sind gruppen-transaktionen
    // gruppe-13 buchungen moeglichst als zweites anzeigen:
    if( ( $buchung['gruppen_id'] == $muell_id ) and ($k_buchung['gruppen_id'] != $muell_id ) ) {
      $h = $buchung_id;
      $buchung_id = $k_id;
      $k_id = $h;
    }
  }
}
$self_fields['buchung_id'] = $buchung_id;   // moeglicherweise getauscht

get_http_var( 'action', 'w', '' );
$editable or $action = '';
switch( $action ) {
  case 'update':
    // we just _update_ the fields that have been submitted; missing fields will be left untouched:
    need_http_var( 'id_1', 'd' ); need( $id_1 == $buchung_id );
    need_http_var( 'id_2', 'd' ); need( $id_2 == $k_id );
    need( $dienstkontrollblatt_id );
    $b1 = sql_get_transaction( $id_1 );
    $b2 = sql_get_transaction( $id_2 );
    if( get_http_var( 'haben', 'f' ) ) {
      $soll = - $haben;
    } else {
      need_http_var( 'soll', 'f' );
    }
    need_http_var( 'notiz', 'H' );
    need_http_var( 'valuta_day', 'U' );
    need_http_var( 'valuta_month', 'U' );
    need_http_var( 'valuta_year', 'U' );
    $mod_1 = array( 'dienstkontrollblatt_id' => $dienstkontrollblatt_id );
    $mod_2 = array( 'dienstkontrollblatt_id' => $dienstkontrollblatt_id );
    if( $id_1 > 0 ) {
      need_http_var( "auszug_1_jahr", 'U' );
      need_http_var( "auszug_1_nr", 'U' );
      $mod_1['kommentar'] = $notiz;
      $mod_1['valuta'] = "$valuta_year-$valuta_month-$valuta_day";
      $mod_1['kontoauszug_jahr'] = $auszug_1_jahr;
      $mod_1['kontoauszug_nr'] = $auszug_1_nr;
      $mod_1['betrag'] = - $soll;
    } else {
      $mod_1['notiz'] = $notiz;
      $mod_1['valuta'] = "$valuta_year-$valuta_month-$valuta_day";
      $mod_1['summe'] = $soll;
      if( $b1['gruppen_id'] == $muell_id ) {
        if( in_array( $b1['transaktionstyp'], $selectable_types ) or ( $b1['transaktionstyp'] == TRANSAKTION_TYP_UNDEFINIERT ) ) {
          need_http_var( 'typ_1', 'U' );
          need( in_array( $typ_1, $selectable_types ) );
          $mod_1['type'] = $typ_1;
        }
      }
    }
    if( $id_2 > 0 ) {
      need_http_var( "auszug_2_jahr", 'U' );
      need_http_var( "auszug_2_nr", 'U' );
      $mod_2['kommentar'] = $notiz;
      $mod_2['valuta'] = "$valuta_year-$valuta_month-$valuta_day";
      $mod_2['kontoauszug_jahr'] = $auszug_2_jahr;
      $mod_2['kontoauszug_nr'] = $auszug_2_nr;
      $mod_2['betrag'] = $soll;
    } else {
      $mod_2['notiz'] = $notiz;
      $mod_2['valuta'] = "$valuta_year-$valuta_month-$valuta_day";
      $mod_2['summe'] = - $soll;
      if( $b2['gruppen_id'] == $muell_id ) {
        if( in_array( $b2['transaktionstyp'], $selectable_types ) or ( $b2['transaktionstyp'] == TRANSAKTION_TYP_UNDEFINIERT ) ) {
          need_http_var( 'typ_2', 'U' );
          need( in_array( $typ_2, $selectable_types ) );
          $mod_2['type'] = $typ_2;
        }
      }
    }
    logger( "editBuchung: update: $id_1 und $id_2" );
    if( $id_1 > 0 ) {
      sql_update( 'bankkonto', $id_1, $mod_1 );
    } else {
      sql_update( 'gruppen_transaktion', -$id_1, $mod_1 );
    }
    if( $id_2 > 0 ) {
      sql_update( 'bankkonto', $id_2, $mod_2 );
    } else {
      sql_update( 'gruppen_transaktion', -$id_2, $mod_2 );
    }
    break;
}


if( $editable )
  open_form( '', 'action=update' );

open_fieldset( 'small_form', '', 'Buchung:' );
  echo $msg; echo $problems;
  open_table( 'layout hfill' );
    fieldset_edit_transaction( $buchung_id, 1, $editable );
    open_tr();
      open_td( 'smallskip', "colspan='2'" );
    fieldset_edit_transaction( $k_id, 2, $editable );
    open_tr('newfield');
      open_td('right', "colspan='2'" );
        $editable ? submission_button() : close_button();
  close_table();
close_fieldset();

if( $editable )
  close_form();

?>
