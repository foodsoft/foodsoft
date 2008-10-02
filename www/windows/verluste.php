<?php
//
// verluste.php: detailansichten und uebersichtstabelle
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
// get_http_var( 'optionen', 'u', 0, true );

get_http_var( 'detail', 'w', 0, true );

$muell_id = sql_muell_id();


function verlust_bestellungen( $detail = false ) {
  global $muell_id;
  if( $detail ) {
    ?> <h2>Differenzen aus Bestellungen:</h2> <?
    open_table( 'list', "width='98%'" );
      open_th('','','Bestellung');
      open_th('','','Schwund/Müll');
      open_th('','','Sonstiges');
      open_th('','','Haben FC');
  }

  $result = doSql( "
    SELECT gesamtbestellungen.*
    , (" .select_bestellungen_soll_gruppen( OPTION_ENDPREIS_SOLL, array( 'gesamtbestellungen', 'bestellgruppen' ) ). ") as muell_soll
    FROM gesamtbestellungen
    JOIN bestellgruppen ON bestellgruppen.id = $muell_id
    HAVING ( extra_soll <> 0 ) OR ( muell_soll <> 0)
    ORDER BY gesamtbestellungen.lieferung
  " );

  $muell_soll_summe = 0;
  $extra_soll_summe = 0;
  $soll_summe = 0;

  while( $row = mysql_fetch_array( $result ) ) {
    $muell_soll = - $row['muell_soll'];  // soll _aus_sicht_von_gruppe_13_! (also der FC-Gemeinschaft!)
    $extra_soll = $row['extra_soll'];
    $soll = $muell_soll + $extra_soll;
    $bestell_id = $row['id'];

    if( $detail ) {
      open_tr();
        open_td( '', '', fc_alink( 'abrechnung', array( 'bestell_id' => $bestell_id, 'text' => $row['name'] ) ) );
        open_td( 'number', '', fc_alink( 'lieferschein'
                    , "img=,bestell_id=$bestell_id,gruppen_id=$muell_id,text=". sprintf( "%.2lf", - $muell_soll ) ) );
        open_td( '', '',  $row['extra_text'] );
        open_td( 'number', '', sprintf( "%.2lf", - $extra_soll ) );
        open_td( 'number', '', sprintf( "%.2lf", - $soll ) );
    }
    $muell_soll_summe += $muell_soll;
    $extra_soll_summe += $extra_soll;
    $soll_summe += $soll;
  }
  if( $detail ) {
    open_tr('summe');
      open_td('','','Summe:');
      open_td( 'number', '', sprintf( "%.2lf", - $muell_soll_summe ) );
      open_td();
      open_td( 'number', '', sprintf( "%.2lf", - $extra_soll_summe ) );
      open_td( 'number', '', sprintf( "%.2lf", - $soll_summe ) );
    close_table();
  }

  return $soll_summe;
}


function verlust_transaktionen( $typ, $detail = false ) {
  global $option_flag, $optionen, $verluste_summe;
  if( $detail ) {
    echo "<h4>". transaktion_typ_string( $typ ) ."</h4>";
    open_table( 'list', "width='98%'" );
      open_th('','','Id');
      open_th('','','Valuta');
      open_th('','','Notiz');
      open_th('oneline','','Haben FC');
  }

  $result = sql_verluste( $typ );
  $soll_summe = 0.0;
  while( $row = mysql_fetch_array( $result ) ) {
    $soll = - $row['soll'];  // $soll aus sicht von gruppe 13
    if( $detail ) {
      open_tr();
        open_td( '', '', alink( 'edit_buchung', "transaktion_id={$row['id']},img=,text={$row['id']}" ) );
        open_td( '', '', $row['valuta'] );
        open_td( '', '', $row['notiz'] );
        open_td( 'number', '', sprintf( "%.2lf", - $soll ) );
    }
    $soll_summe += $soll;
  }
  if( $detail ) {
    open_tr('summe');
      open_td( 'right', "colspan='3'", 'Summe:' );
      open_td( 'number', '', sprintf( "%.2lf", - $soll_summe ) );
    close_table();
  }
  return $soll_summe;
}

get_http_var( 'action', 'w', '' );
$editable or $action = '';
switch( $action ) {
  case 'umbuchung_verlust':
    need_http_var( 'von_typ', 'U' );
    need_http_var( 'nach_typ', 'U' );
    need_http_var( 'betrag', 'f' );
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
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
    , "$year-$month-$day"
    , "$notiz"
    );
    break;
  case 'umlage':
    need_http_var( 'betrag', 'f' );
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'notiz', 'H' );
    if( ! $notiz ) {
      div_msg( 'warn', 'Bitte Notiz eingeben!' );
      break;
    }
    sql_gruppen_umlage( $betrag, "$year-$month-$day", $notiz );
    break;
}

if( $detail ) {
  if( $detail == 'bestellungen' ) {
    verlust_bestellungen( true );
  } else if ( $detail == 'undefiniert' ) {
    verlust_transaktionen( TRANSAKTION_TYP_UNDEFINIERT, true );
  } else {
    verlust_transaktionen( $detail, true );
  }
  return;
}

$verluste_summe = 0.0;
$ausgleich_summe = 0.0;

?>
<h1>Verlustaufstellung --- &Uuml;bersicht</h1>

<? if( $editable ) { ?>

<div id='transactions_button' style='padding-bottom:1em;'>
  <span class='button'
    onclick="document.getElementById('transactions_menu').style.display='block';
             document.getElementById('transactions_button').style.display='none';"
    >Transaktionen...
  </span>
</div>

<fieldset class='small_form' id='transactions_menu' style='display:none;margin-bottom:2em;'>
  <legend>
    <img src='img/close_black_trans.gif' class='button' title='Schliessen' alt='Schliessen'
    onclick="document.getElementById('transactions_button').style.display='block';
             document.getElementById('transactions_menu').style.display='none';">
    Transaktionen
  </legend>

  Art der Transaktion:
  <ul style='list-style:none;'>
    <li style='padding-left:1em;' title='Umbuchung von Spenden oder Umlagen zur Schuldentilgung'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('umbuchung_form').style.display='block';
                 document.getElementById('umlage_form').style.display='none';"
      ><b>Umbuchung Verlustausgleich</b>
    </li>
    <li style='padding-left:1em;' title='Umlage von allen(!) aktiven Gruppenmitgliedern erheben'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('umbuchung_form').style.display='none';
                 document.getElementById('umlage_form').style.display='block';"
      ><b>Umlage erheben</b>
    </li>
  </ul>

  <div id='umbuchung_form' style='padding-bottom:1em;display:none;margin-bottom:2em;'>
    <fieldset class='small_form'>
      <legend>Umbuchung Verlustausgleich </legend>
      <? formular_umbuchung_verlust(); ?>
    </fieldset>
  </div>

  <div id='umlage_form' style='padding-bottom:1em;display:none;margin-bottom:2em;'>
    <fieldset class='small_form'>
      <legend>Verlustumlage auf Gruppenmitglieder</legend>
      <? formular_gruppen_umlage(); ?>
    </fieldset>
  </div>
</fieldset>

<?
}

//
// verluste --- uebersichtstabelle
//
open_table('list');
  open_th('','',       'Typ');
  open_th('oneline','','Haben FC');
  open_th('','',       'Ausgleichsbuchungen');
  open_th('','',       'Stand');

open_tr();
  open_td( '', '', "Altlasten (Anfangsguthaben):" );

  $soll = verlust_transaktionen( TRANSAKTION_TYP_ANFANGSGUTHABEN );
  $verluste_summe += $soll;

  $ausgleich = verlust_transaktionen( TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN );
  $ausgleich_summe += $ausgleich;

  open_td( 'number','', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_ANFANGSGUTHABEN, 'img' => '', 'text' => sprintf( "%.2lf", -$soll ) ) ) );
  open_td( 'number','', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN, 'img' => '', 'text' => sprintf( "%.2lf", -$ausgleich ) ) ) );
  open_td( 'number', '', sprintf( "%.2lf", - $soll - $ausgleich ) );


open_tr();
  open_td( '','', 'Verluste aus Bestellungen:' );

  $soll = verlust_bestellungen( false );
  $verluste_summe += $soll;

  $ausgleich = verlust_transaktionen( TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE );
  $ausgleich_summe += $ausgleich;

  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => 'bestellungen', 'img' => '' , 'text' => sprintf( "%.2lf", -$soll ) ) ) );
  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE, 'img' => '', 'text' => sprintf( "%.2lf", -$ausgleich ) ) ) );
  open_td( 'number', '', sprintf( "%.2lf", - $soll - $ausgleich ) );


open_tr();
  open_td( '','', 'Sonderausgaben:' );

  $soll = verlust_transaktionen( TRANSAKTION_TYP_SONDERAUSGABEN );
  $verluste_summe += $soll;

  $ausgleich = verlust_transaktionen( TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN );
  $ausgleich_summe += $ausgleich;

  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_SONDERAUSGABEN, 'img' => '' , 'text' => sprintf( "%.2lf", -$soll ) ) ) );
  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN, 'img' => '' , 'text' => sprintf( "%.2lf", -$ausgleich ) ) ) );
  open_td( 'number', '', sprintf( "%.2lf", - $soll - $ausgleich ) );


open_tr( 'summe' );
  open_td( '', '', 'Zwischensumme:' );
    open_td( 'number', '', sprintf( "%.2lf", - $verluste_summe ) );
    open_td( 'number', '', sprintf( "%.2lf", - $ausgleich_summe ) );
    open_td( 'number', '', sprintf( "%.2lf", - $ausgleich_summe - $verluste_summe ) );


open_tr();
  open_th( '', "colspan='4' style='padding-top:1em;'", 'Einnahmen:' );

open_tr();
  open_td( '', '', 'Spenden:' );

  $soll = verlust_transaktionen( TRANSAKTION_TYP_SPENDE );
  $verluste_summe += $soll;

  $ausgleich = verlust_transaktionen( TRANSAKTION_TYP_UMBUCHUNG_SPENDE );
  $ausgleich_summe += $ausgleich;

  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_SPENDE, 'img' => '' , 'text' => sprintf( "%.2lf", -$soll ) ) ) );
  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_UMBUCHUNG_SPENDE, 'img' => '' , 'text' => sprintf( "%.2lf", -$ausgleich ) ) ) );
  open_td( 'number', '', sprintf( "%.2lf", - $soll - $ausgleich ) );


open_tr();
  open_td( '', '', 'Umlagen:' );

  $soll = verlust_transaktionen( TRANSAKTION_TYP_UMLAGE );
  $verluste_summe += $soll;

  $ausgleich = verlust_transaktionen( TRANSAKTION_TYP_UMBUCHUNG_UMLAGE );
  $ausgleich_summe += $ausgleich;

  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_UMLAGE, 'img' => '' , 'text' => sprintf( "%.2lf", -$soll ) ) ) );
  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => TRANSAKTION_TYP_UMBUCHUNG_UMLAGE, 'img' => '' , 'text' => sprintf( "%.2lf", -$ausgleich ) ) ) );
  open_td( 'number', '', sprintf( "%.2lf", - $soll - $ausgleich ) );


open_tr();
  open_th( '', "colspan='4' style='padding-top:1em;'", 'Sonstiges:' );

open_tr();
  open_td( '', '', 'nicht klassifiziert:' );

  $soll = verlust_transaktionen( TRANSAKTION_TYP_UNDEFINIERT );
  $verluste_summe += $soll;

  open_td( 'number', '', fc_alink( 'verlust_details'
    , array( 'detail' => 'undefiniert', 'img' => '' , 'text' => sprintf( "%.2lf", -$soll ) ) ) );
  open_td();
  open_td();

open_tr('summe');
  open_td( '', '', 'Summe:' );
  open_td( 'number', '', sprintf( "%.2lf", - $verluste_summe ) );
  open_td( 'number', '', sprintf( "%.2lf", - $ausgleich_summe ) );
  open_td( 'number', '', sprintf( "%.2lf", - $ausgleich_summe - $verluste_summe ) );


open_tr();
  open_th( '', "colspan='4' style='padding-top:1em;'", 'Weitere "Muell"-Buchungen (keine Verluste):' );

open_tr();
  open_td( '', "colspan='3'", 'Stornos (sollten zusammen Betrag 0 ergeben):' );
  open_td( 'number', '', fc_alink( 'verlust_details', array( 'detail' => TRANSAKTION_TYP_STORNO
        , 'img' => '', 'text' => sprintf( "%.2lf", -verlust_transaktionen( TRANSAKTION_TYP_STORNO ) ) ) ) );

open_tr();
  open_td( '', "colspan='3'", 'geparkte" Sockeleinlagen:' );
  open_td( 'number', '', fc_alink( 'verlust_details', array( 'detail' => TRANSAKTION_TYP_SOCKEL
        , 'img' => '', 'text' => sprintf( "%.2lf", -verlust_transaktionen( TRANSAKTION_TYP_SOCKEL ) ) ) ) );

close_table();

?>
