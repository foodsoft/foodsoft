<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.


assert($angemeldet) or exit();
$editable = ! $readonly;

get_http_var( 'meinkonto', 'u', 0, true );
get_http_var( 'optionen', 'u', 0, true );
get_http_var( 'gruppen_id', 'u', 0, true );
if( ( ! hat_dienst(4,5) ) and ( $gruppen_id == $login_gruppen_id ) ) {
  $meinkonto = 1;
}

if( $meinkonto ) {
  setWikiHelpTopic( 'foodsoft:MeinKonto' );

  ?>
    <script type="text/javascript" src='<?php echo $foodsoftdir; ?>/js/lib/qrcode.min.js'></script>
  <?php

  $gruppen_id = $login_gruppen_id;
  $self_fields['gruppen_id'] = $gruppen_id;
  $gruppen_name = sql_gruppenname( $gruppen_id );
  ?> <h1>Mein Konto: Kontoausz&uuml;ge von Gruppe <?php echo $gruppen_name; ?></h1> <?php

  if( ! $readonly ) {

    open_fieldset( 'small_form', '', 'Überweisung eintragen', 'off' );
      open_form( '', "action=einzahlung" );
        open_table('layout');
          form_row_betrag( 'Ich habe heute ' ); echo ' Euro fuer unsere Gruppe '. gruppe_view( $login_gruppen_id ); submission_button( 'überwiesen' );
        close_table();
      close_form();
    close_fieldset();
    medskip();

    open_fieldset( 'small_form', '', 'Überweisung per QR-Code', 'off' );
      open_table('layout');
        $amount_id = new_html_id();
        $qr_code_id = new_html_id();
        global $input_event_handlers;
        $input_event_handlers = "onkeyup='makeQrCode(\"$amount_id\", \"$qr_code_id\")'";
        form_row_betrag( 'Ich möchte heute ', id: $amount_id ); echo ' Euro fuer unsere Gruppe '. gruppe_view( $login_gruppen_id ). ' überweisen!';
        $input_event_handlers = '';
        open_tr();
          open_td( '', "colspan='2'" );
            open_div( 'nodisplay', "id='$qr_code_id.div'");
              echo 'Klar, kein Problem! Einfach den QR-Code mit der Banking-App scannen:';
              medskip();
              open_div( '', "id='$qr_code_id'", '');
              medskip();
              open_form( '', "action=einzahlung" );
                hidden_input( 'betrag', '', "id='$amount_id.form'" );
                echo 'Überweisung ist gemacht? Dann:'; submission_button( 'Hier klicken!' );
              close_form();
            close_div();
      close_table();
  close_fieldset();
  medskip();

    open_div('alert', "style='padding:1ex 0ex 1ex 0ex;'" );
      open_fieldset( 'small_form', '', 'Spende an die Foodcoop', 'off' );
        open_form( '', "action=spende" );
          open_table('layout');
            form_row_gruppe( 'Unsere Gruppe', false, $gruppen_id );
            form_row_betrag( 'spendet der Foodcoop' ); echo " Euro!";
            form_row_text( 'Anmerkungen:', 'notiz', 60, 'Spende zum Schuldenabbau' );
            qquad();
            submission_button( 'Speichern' );
          close_table();
        close_form();
      close_fieldset();
    close_div();
    medskip();

    get_http_var( 'action', 'w', '' );
    $editable or $action = '';
    switch( $action ) {
      case 'einzahlung':
        need_http_var( 'betrag', 'f' );
        sql_gruppen_transaktion( 0, $login_gruppen_id, $betrag, "Einzahlung" );
        break;
      case 'spende':
        need_http_var( 'betrag', 'f' );
        if( $betrag <= 0 )
          break;
        get_http_var( 'notiz', 'H', 'Spende' );
        sql_doppelte_transaktion(
          array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id(), 'transaktionsart' => TRANSAKTION_TYP_SPENDE )
        , array( 'konto_id' => -1, 'gruppen_id' => $gruppen_id, 'transaktionsart' => TRANSAKTION_TYP_SPENDE )
        , $betrag
        , $mysqlheute
        , $notiz
        , true
        );
        open_javascript( "alert( 'Spende ist eingegangen, vielen Dank!' );" );
        break;
    }
  }

} else { // kontoblatt-anzeige fuer dienste
  nur_fuer_dienst(4,5);
  setWikiHelpTopic( 'foodsoft:kontoblatt' );
  ?> <h1>Kontoblatt</h1> <?php

  if( ! $readonly ) {
    get_http_var( 'action', 'w', '' );
    switch( $action ) {
      case 'finish_transaction':
        action_finish_transaction();
        break;
      case 'buchung_gruppe_sonderausgabe':
        action_buchung_gruppe_sonderausgabe();
        break;
      case 'buchung_gruppe_bank':
        action_buchung_gruppe_bank();
        break;
      case 'buchung_gruppe_lieferant':
        action_buchung_gruppe_lieferant();
        break;
      case 'buchung_gruppe_gruppe':
        action_buchung_gruppe_gruppe();
        break;
      case 'buchung_gruppe_anfangsguthaben':
        action_buchung_gruppe_anfangsguthaben();
        break;
    }
  }

  if( ! $readonly ) {
    open_fieldset( 'small_form', '', 'Transaktionen', 'off' );

      ?> <h4>Art der Transaktion:</h4> <?php

      alternatives_radio( array(
        'gruppe_bank_form' => array( 'Einzahlung oder Auszahlung'
                                   , 'Einzahlung auf oder Auszahlung von Bankkonto der Foodcoop' )
      , 'gruppe_gruppe_form' => array( 'Transfer an andere Gruppe'
                                   , 'überweisung auf ein anderes Gruppenkonto' )
      , 'gruppe_lieferant_form' => array( 'Zahlung von Gruppe an Lieferant'
                                   , 'überweisung von Gruppe an Lieferant' )
      , 'sonderausgabe_gruppe_form' => array( 'Sonderausgabe durch Gruppe'
                                        , 'Sonderausgabe durch Gruppe (z.B. Geschenkkauf)' )
      , 'anfangsguthaben_gruppe_form' => array( 'Erfassung Anfangsguthaben Gruppe'
                                        , 'Anfangsguthaben (bei Umstellung auf Foodsoft) erfassen' )
      ) );

      open_div( 'nodisplay', "id='gruppe_bank_form'" );
        formular_buchung_gruppe_bank();
      close_div();

      open_div( 'nodisplay', "id='gruppe_gruppe_form'" );
        formular_buchung_gruppe_gruppe();
      close_div();

      open_div( 'nodisplay', "id='gruppe_lieferant_form'" );
        formular_buchung_gruppe_lieferant();
      close_div();

      open_div( 'nodisplay', "id='sonderausgabe_gruppe_form'" );
        formular_buchung_gruppe_sonderausgabe();
      close_div();

      open_div( 'nodisplay', "id='anfangsguthaben_gruppe_form'" );
        formular_buchung_gruppe_anfangsguthaben();
      close_div();

    close_fieldset();
    medskip();
  }
}

open_table( 'menu' );
    open_th( '', 'colspan="2"', 'Optionen' );
  open_tr();
    open_td( '', 'colspan="2"');
      option_checkbox( 'optionen', GRUPPENKONTO_OPT_BASAR, 'Basarkäufe zeigen');
if (! $meinkonto) {
  open_tr();
    open_td('', '', 'Gruppe:' );
    open_td();
      open_select( 'gruppen_id', 'autoreload' );
        echo optionen_gruppen(
            $gruppen_id
          , [ 'aktiv' => 'true' ]
          , $optionen & GRUPPENKONTO_OPT_BASAR ? 'Alle' : false);
      close_select();
}
close_table();
medskip();

if(! $optionen & GRUPPENKONTO_OPT_BASAR && ! $gruppen_id )
  return;

if ($optionen & GRUPPENKONTO_OPT_BASAR)
  basarbuchungen_view($gruppen_id);
else
  gruppenkonto_view($gruppen_id, $meinkonto);

if ( $meinkonto ) {
  $konten = sql_konten();
  $kontonr = '';
  $kontoinhaber = '';
  if (count($konten) > 0) {
    $kontonr = preg_replace('/\s+/', '', $konten[0]['kontonr']);
    $kontoinhaber = $konten[0]['name'];
  }

  open_javascript( toJavaScript( 'const foodcoop_name', $foodcoop_name ) );
  open_javascript( toJavaScript( 'const kontonr', $kontonr ) );
  open_javascript( toJavaScript( 'const kontoinhaber', $kontoinhaber ) );
  open_javascript( toJavascript( 'const gruppen_name', $gruppen_name ) );
  open_javascript( toJavascript( 'const gruppen_nr', sql_gruppennummer( $gruppen_id ) ) );

  open_javascript(<<<'JS'
var qrCode = null;
function makeQrCode( amountId, qrCodeId ) {
  const text = `BCD
002
1
SCT

${kontoinhaber}
${kontonr}
EUR${Number($(amountId).value).toFixed(2)}
DEPT

Einzahlung Gruppe ${gruppen_nr} ${gruppen_name}
`;

  if (!qrCode) {
    qrCode = new QRCode( qrCodeId, {
      text,
      width: 256,
      height: 256,
      colorDark : "#000000",
      colorLight : "#ffffff",
      correctLevel : QRCode.CorrectLevel.M,
      useUtf8Bom: false
    });
    $(`${qrCodeId}.div`).style.display = 'block';
  }
  else {
    qrCode.clear();
    qrCode.makeCode( text );
  }

  $(`${amountId}.form`).value = $(amountId).value;
}
JS);
}
benchmarkTimestamp(__LINE__);
showBenchmark();
?>
