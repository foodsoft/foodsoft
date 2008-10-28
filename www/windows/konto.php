<?php
//
// konto.php: Bankkonto-Verwaltung
//

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );

setWindowSubtitle( 'Kontoverwaltung' );
setWikiHelpTopic( 'foodsoft:kontoverwaltung' );

?> <h1>Kontoverwaltung</h1> <?

//////////////////////
// konto auswaehlen:
//
$konten = sql_konten();
if( ! $konten ) {
  div_msg( 'warn', 'Keine Konten definiert!', 'index' );
  return;
}

if( count($konten) == 1 ) {
  $row = current( $konten );
  $konto_id = $row['id'];
} else {
  $konto_id = 0;
}
get_http_var( 'konto_id', 'u', $konto_id, true );

?> <h4>Konten der Foodcoop:</h4> <?

open_table('list');
  open_th('','','Name');
  open_th('','','BLZ');
  open_th('','','Konto-Nr');
  open_th('','','Saldo');
  open_th('','','Online-Banking');
  open_th('','','Kommentar');

foreach( $konten as $row ) {
  if( $row['id'] != $konto_id ) {
    open_tr( '', 'onclick="' .fc_link( 'self', "konto_id={$row['id']},context=js" ).';"' );
  } else {
    open_tr( 'active' );
  }
    open_td( 'bold', '', $row['name'] );
    open_td( '', '', $row['blz'] );
    open_td( '', '', $row['kontonr'] );
    open_td( 'number', '', price_view( sql_bankkonto_saldo( $row['id'] ) ) );
    if( ( $url = $row['url'] ) ) {
      open_td( '', '',"<a href=\"javascript:window.open('$url','onlinebanking').focus();\">$url</a></td>" );
    } else {
      open_td( '', '', '-' );
    }
    open_td( '', '', $row['kommentar'] );
}
close_table();;
open_div('medskip', '', '' );

if( ! $konto_id )
  return;


//////////////////////
// auszug auswaehlen:
//

get_http_var( 'auszug', '/^\d+-\d+$/', 0, true );
if( $auszug ) {
  sscanf( $auszug, "%u-%u", & $auszug_jahr, & $auszug_nr );
  // $self_fields['auszug_jahr'] = $auszug_jahr;
  // $self_fields['auszug_nr'] = $auszug_nr;
} else {
  get_http_var( 'auszug_jahr', 'u', 0 ) or $auszug_jahr = 0;
  get_http_var( 'auszug_nr', 'u', 0 ) or $auszug_nr = 0;
  $self_fields['auszug'] = "$auszug_jahr-$auszug_nr";
}

$ungebuchte_einzahlungen = sql_ungebuchte_einzahlungen();

open_table('layout');
  open_td('', "colspan='2'", '<h3>Erfasste Auszüge:</h3' );
  if( $editable and $ungebuchte_einzahlungen )
    open_td('', "colspan='2'", '<h3>Ungebuchte Einzahlungen:</h3>' );

  open_tr();
    open_td();
      open_select( 'auszug', true );
        $selected = false;
        $options = '';
        foreach( sql_kontoauszug( $konto_id ) as $auszug ) {
          $jahr = $auszug['kontoauszug_jahr'];
          $nr = $auszug['kontoauszug_nr'];

          $posten = count( sql_kontoauszug( $konto_id, $jahr, $nr ) );
          $saldo = sql_bankkonto_saldo( $konto_id, $auszug['kontoauszug_jahr'], $auszug['kontoauszug_nr'] );

          // $detailurl = self_url( array( 'auszug_jahr', 'auszug_nr' ) ) . "&auszug_nr=$nr&auszug_jahr=$jahr";

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

    open_td();

      open_fieldset('', '','Neuen Auszug anlegen', 'off' );
        open_form( '', '', 'action=neuer_auszug' );
          open_div('oneline');
            echo "<label>Jahr:</label> " . string_view( date('Y'), 4, 'neuer_auszug_jahr' );
            echo " / <label>Nr:</label>" . string_view( '', 2, 'neuer_auszug_nr' );
            submission_button();
          close_div();
        close_form();
      close_fieldset();

    open_td();
      if( $editable and $ungebuchte_einzahlungen ) {
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
                  foreach( sql_gruppen_members($trans['gruppen_id']) as $pers )
                    open_li( '', '', $pers["vorname"]." ".$pers["name"] );
                close_ul();
              open_td( 'number', '', price_view( $trans['summe'] ) );
              open_td();
                if( $editable and $auszug_jahr and $auszug_nr ) {
                  open_form( '', '', array( 'action' => 'confirm_payment', 'transaction_id' => $trans['id'] ) );
                    echo "<label>Valuta:</label> ". date_view( '', 'valuta' );
                      open_span( 'quad', "title='Best&auml;tigen: diese Gutschrift ist auf Auszug $auszug_jahr / $auszug_nr verbucht'" );
                        submission_button( 'Best&auml;tigen' );
                      close_span();
                  close_form();
                }
                echo "<hr>";
                if( $editable ) {
                  echo fc_action( 'title=diese ungebuchte Gutschrift stornieren,class=drop'
                                , "action=cancel_payment,transaction_id={$trans['id']}" );
                }
          }
        close_table();
      }

close_table();


get_http_var( 'action', 'w', false );
$editable or $action = '';

switch( $action ) { // aktionen die keinen auszug brauchen
  case 'cancel_payment':
    need_http_var( 'transaction_id', 'u' );
    doSql( "DELETE FROM gruppen_transaktion WHERE id=$transaction_id" );
    reload_immediately( fc_link( '', 'context=action' ) );
    break;
    need_http_var( 'transaction_id', 'u' );
  case 'neuer_auszug':
    need_http_var( 'neuer_auszug_nr', 'u' );
    need_http_var( 'neuer_auszug_jahr', 'u' );
    $auszug_nr = $neuer_auszug_nr;
    $auszug_jahr = $neuer_auszug_jahr;
    break;
}

if( ! $auszug_jahr or ! $auszug_nr )
  return;

$kontoname = sql_kontoname($konto_id);
echo "<h3>$kontoname - Auszug $auszug_jahr / $auszug_nr</h3>";

switch( $action ) { // aktionen, die einen auszug brauchen
  case 'zahlung_gruppe':
    action_buchung_gruppe_bank();
    break;
  case 'zahlung_lieferant':
    action_buchung_lieferant_bank();
    break;
  case 'ueberweisung_konto_konto':
    action_buchung_bank_bank();
    break;
  case 'ueberweisung_sonderausgabe':
    buchung_bank_sonderausgabe();
    break;
  case 'confirm_payment':
    need_http_var( 'transaction_id', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'day', 'u' );
    sql_finish_transaction( $transaction_id, $konto_id, $auszug_nr, $auszug_jahr, "$year-$month-$day", 'gebuchte Einzahlung' );
    reload_immediately( self_url() );
    break;
}

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
    open_td( 'number', '', $row['buchungsdatum_trad']."<div style='small'>{$row['dienst_name']}</div>" );
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
open_table();

?>
