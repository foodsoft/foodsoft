<?php
//
// konto.php: Bankkonto-Verwaltung
//

assert( $angemeldet ) or exit();
$editable = ( hat_dienst(4) and ! $readonly );

setWikiHelpTopic( 'foodsoft:kontoverwaltung' );

?> <h1>Bankkonten</h1> <?php

$konten = sql_konten();

if( count($konten) == 1 ) {
  $row = current( $konten );
  $konto_id = $row['id'];
} else {
  $konto_id = 0;
}
get_http_var( 'konto_id', 'u', $konto_id, true );

get_http_var( 'auszug', '/^\d+-\d+$/', 0, false );  // kompakt-format (aus <select> unten!)
if( $auszug ) {
  sscanf( $auszug, "%u-%u", $auszug_jahr, $auszug_nr );
  $self_fields['auszug_jahr'] = $auszug_jahr;
  $self_fields['auszug_nr'] = $auszug_nr;
} else {
  get_http_var( 'auszug_jahr', 'u', 0, true ) or $auszug_jahr = 0;
  get_http_var( 'auszug_nr', 'u', 0, true ) or $auszug_nr = 0;
}

//////////////////////
// hauptmenue und auswahl konto:
//
open_table( 'layout hfill' );
  open_td('left');
    open_table('menu');
      if( $editable ) {
        open_tr();
          open_td( '', '', fc_link( 'edit_konto'
                     , "class=bigbutton,title=Neues Bankkonto eintragen,text=Neues Konto" ) );
      }
      open_tr();
        open_td( '', '', fc_link( 'self', "class=bigbutton,text=Seite aktualisieren" ) );
      open_tr();
        open_td( '', '', fc_link( 'index', "class=bigbutton" ) );
    close_table();

  open_td( 'floatright' );
    auswahl_konto( $konto_id );
close_table();

bigskip();

if( ! $konto_id )
  return;

get_http_var( 'action', 'w', false );
$editable or $action = '';
switch( $action ) { // aktionen die keinen auszug brauchen
  case 'cancel_payment':
    need_http_var( 'transaction_id', 'u' );
    doSql( "DELETE FROM gruppen_transaktion WHERE id=$transaction_id" );
    reload_immediately( fc_link( '', 'context=action' ) );
    break;
  case 'buchung_gruppe_bank':
    action_buchung_gruppe_bank();
    break;
  case 'buchung_lieferant_bank':
    action_buchung_lieferant_bank();
    break;
  case 'buchung_bank_bank':
    action_buchung_bank_bank();
    break;
  case 'buchung_bank_sonderausgabe':
    action_buchung_bank_sonderausgabe();
    break;
  case 'buchung_bank_anfangsguthaben':
    action_buchung_bank_anfangsguthaben();
    break;
  case 'finish_transaction':
    action_finish_transaction();
    break;
}


//////////////////////
// auszug auswaehlen:
//

open_table('layout hfill' );

  open_td();
    ?> <h3>Kontouszüge von Konto <?php echo sql_kontoname($konto_id); ?>:</h3> <?php

    open_select( 'auszug', 'autoreload' );
      $selected = false;
      $options = '';
      foreach( sql_kontoauszug( $konto_id ) as $auszug ) {
        $jahr = $auszug['kontoauszug_jahr'];
        $nr = $auszug['kontoauszug_nr'];

        $posten = count( sql_kontoauszug( $konto_id, $jahr, $nr ) );
        $saldo = sql_bankkonto_saldo( $konto_id, $auszug['kontoauszug_jahr'], $auszug['kontoauszug_nr'] );

        $options .= "<option value='$jahr-$nr'";
        if( $jahr == $auszug_jahr and $nr == $auszug_nr ) {
          $options .= " selected";
          $selected = true;
        }
        $options .= ">$jahr / $nr ($posten Posten, Saldo: $saldo)</option>";
      }
      if( ! $selected ) {
        $options = "<option value='0' selected>(Bitte Auszug wählen)</option>" . $options;
      }
      echo $options;
    close_select();
    smallskip();

    if( $editable ) {
      open_fieldset( 'small_form', '','Neuen Auszug anlegen', 'off' );
        open_form( array( 'auszug_jahr' => NULL, 'auszug_nr' => NULL ) /* don't pass these automatically */ );
          open_div('oneline');
            echo "<label>Jahr:</label> " . string_view( date('Y'), 4, 'auszug_jahr' );
            echo " / <label>Nr:</label>" . string_view( '', 2, 'auszug_nr' );
            submission_button();
          close_div();
        close_form();
      close_fieldset();
    }

  $ungebuchte_einzahlungen = sql_ungebuchte_einzahlungen();
  if( $editable and $ungebuchte_einzahlungen and $auszug_jahr and $auszug_nr ) {
    open_td( 'floatright' );
      ?> <h4>ungebuchte Einzahlungen:</h4> <?php

      // open_div( 'kommentar left', '', 'Hier könnt ihr den Geldeingang von Einzahlungen, die von den Gruppen selbst eingetragen wurden,
      //                      bestätigen, oder die Einzahlung stornieren:' );

      smallskip();
      open_table('list');
        open_th('','','Datum');
        open_th('','','Gruppe');
        open_th('','','Betrag');
        open_th('','','Aktionen');

        foreach( $ungebuchte_einzahlungen as $trans ) {
          open_tr();
            open_td('','', $trans['eingabedatum_trad'] );
            open_td();
              echo gruppe_view( $trans['gruppen_id'] );
              open_ul();
                foreach( sql_gruppe_mitglieder( $trans['gruppen_id'] ) as $pers )
                  open_li( '', '', $pers["vorname"]." ".$pers["name"] );
              close_ul();
            open_td( 'number', '', price_view( $trans['summe'] ) );
            open_td();
              if( $editable ) {
                form_finish_transaction( $trans['id'] );
                echo "<hr>";
                open_div( 'right', '', fc_action( array( 'title' => 'diese ungebuchte Gutschrift stornieren', 'text' => 'löschen'
                                                       , 'class' => 'button drop', 'confirm' => 'Gutschrift wirklich löschen?' )
                                                 , "action=cancel_payment,transaction_id={$trans['id']}" ) );
              }
        }
      close_table();
  }

close_table();

bigskip();


if( ! $auszug_jahr or ! $auszug_nr )
  return;

////////////////////////////////
// anzeige eines kontoauszugs:
//

$kontoname = sql_kontoname($konto_id);
echo "<h3>$kontoname - Auszug $auszug_jahr / $auszug_nr</h3>";


if( $editable ) {
  open_fieldset( 'small_form', '', 'Transaktion eintragen', 'off' );
    alternatives_radio( array(
      'gruppe_bank_form' => "Einzahlung / Auszahlung Gruppe"
    , 'lieferant_bank_form' => "Überweisung / Lastschrift Lieferant"
    , 'bank_bank_form' => "Überweisung auf ein anderes Konto der FC"
    , 'sonderausgabe_bank_form' => "Überweisung/Abbuchung Sonderausgabe"
    , 'anfangsguthaben_bank_form' => "Anfangskontostand erfassen"
    ) );

    open_div( 'nodisplay', "id='gruppe_bank_form'" );
      formular_buchung_gruppe_bank();
    close_div();

    open_div( 'nodisplay', "id='lieferant_bank_form'" );
      formular_buchung_lieferant_bank();
    close_div();

    open_div( 'nodisplay', "id='bank_bank_form'" );
      formular_buchung_bank_bank();
    close_div();

    open_div( 'nodisplay', "id='sonderausgabe_bank_form'" );
      formular_buchung_bank_sonderausgabe();
    close_div();

    open_div( 'nodisplay', "id='anfangsguthaben_bank_form'" );
      formular_buchung_bank_anfangsguthaben();
    close_div();

  close_fieldset();
  medskip();
}

$startsaldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr-1 );
$saldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr );

open_table('list');
    open_th('','','Posten');
    open_th('','','Valuta');
    open_th('','','Buchung');
    open_th('','','Kommentar');
    open_th('','','Betrag');
    open_th('','','Aktionen');
  open_tr('summe');
    open_td( 'right', "colspan='4'", 'Startsaldo:');
    open_td( 'number', '', price_view( $startsaldo ) );
    open_td();

$n=0;
foreach( sql_kontoauszug( $konto_id, $auszug_jahr, $auszug_nr ) as $row ) {
  $n++;
  $kommentar = $row['kommentar'];
  $konterbuchung_id = $row['konterbuchung_id'];

  open_tr();
    open_td( 'number', '', $n );
    open_td( 'number', '', $row['valuta_trad'] );
    open_td( 'number', '', $row['buchungsdatum_trad']."<div class='small'>{$row['dienst_name']}</div>" );
    open_td();
      echo $kommentar;

      if( $konterbuchung_id ) {
        $konterbuchung = sql_get_transaction( $konterbuchung_id );
        if( $konterbuchung_id > 0 ) {
          $k_konto_id = $konterbuchung['konto_id'];
          $k_auszug_jahr = $konterbuchung['kontoauszug_jahr'];
          $k_auszug_nr = $konterbuchung['kontoauszug_nr'];
          open_div( '', '', 'Gegenbuchung: ' . fc_link( 'kontoauszug', array(
              'konto_id' => $k_konto_id, 'auszug_jahr' => $k_auszug_jahr, 'auszug_nr' => $k_auszug_nr
            , 'text' => "{$konterbuchung['kontoname']}, Auszug $k_auszug_jahr / $k_auszug_nr", 'class' => 'href'
          ) ) );
        } else {
          $gruppen_id = $konterbuchung['gruppen_id'];
          $lieferanten_id=$konterbuchung['lieferanten_id'];
          if( $gruppen_id ) {
            if( $gruppen_id == sql_muell_id() ) {
              $typ = $konterbuchung['transaktionstyp'];
              div_msg( '', fc_link( 'verlust_details', array(
                           'detail' => $typ, 'class' => 'href', 'text' => transaktion_typ_string( $typ ) ) ) );
            } else {
              $gruppen_name = sql_gruppenname( $gruppen_id );
              div_msg( '', 'Überweisung Gruppe '. fc_link( 'gruppenkonto', array(
                         'gruppen_id' => $gruppen_id , 'class' => 'href', 'text' => $gruppen_name ) ) );
            }
          } elseif ( $lieferanten_id ) {
            $lieferanten_name = sql_lieferant_name( $lieferanten_id );
            div_msg( '', 'Überweisung/Lastschrift Lieferant ' . fc_link( 'lieferantenkonto', array(
                         'lieferanten_id' => $lieferanten_id , 'class' => 'href', 'text' => $lieferanten_name ) ) );
          } else {
            div_msg( 'warn', 'fehlerhafte Buchung' );
          }
        }
      } else {
        div_msg( 'warn', 'unvollständige oder fehlerhafte Buchung' );
      }
    open_td( 'number bottom', '', price_view( $row['betrag'] ) );
    open_td( '', 'bottom', fc_link( 'edit_buchung', "buchung_id={$row['id']}" ) );
}

  open_tr('summe');
    open_td( 'right', "colspan='4'", 'Saldo:');
    open_td( 'number', '', price_view( $saldo ) );
    open_td();
close_table();

?>
