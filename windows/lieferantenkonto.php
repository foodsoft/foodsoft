<?PHP

assert($angemeldet) or exit();
 
setWikiHelpTopic( 'foodsoft:lieferantenkonto' );
setWindowSubtitle( 'Lieferantenkonto' );

$editable = ( hat_dienst(4) and ! $readonly );
get_http_var( 'lieferanten_id', 'u', 0, true );

?> <h1>Lieferantenkonto</h1> <?php

open_table( 'menu' );
    open_th( '', "colspan='2'", 'Optionen' );
  open_tr();
    open_td('', '', 'Lieferant:' );
    open_td();
      open_select( 'lieferanten_id', 'autoreload' );
        echo optionen_lieferanten( $lieferanten_id );
      close_select();
close_table();

if( ! $lieferanten_id )
  return;

$lieferanten_name = sql_lieferant_name( $lieferanten_id );

if( $editable ) {
  get_http_var( 'action', 'w', '' );
  switch( $action ) {
    case 'buchung_lieferant_bank':
      action_buchung_lieferant_bank();
      break;
    case 'buchung_gruppe_lieferant':
      action_buchung_gruppe_lieferant();
      break;
    case 'buchung_lieferant_anfangsguthaben':
      action_buchung_lieferant_anfangsguthaben();
      break;
  }

  medskip();
  open_fieldset( 'small_form', '', 'Transaktionen', 'off' );
    alternatives_radio( array(
      'lieferant_bank_form' => 'Überweisung oder Abbuchung von Bankkonto der Foodcoop'
    , 'gruppe_lieferant_form' => 'Direktzahlung von Gruppe an Lieferant'
    , 'lieferant_anfangsguthaben_form' => 'Erfassung Anfangsguthaben Lieferant'
    ) );

    open_div( 'nodisplay', "id='lieferant_bank_form'" );
      formular_buchung_lieferant_bank();
    close_div();

    open_div( 'nodisplay', "id='gruppe_lieferant_form'" );
      formular_buchung_gruppe_lieferant();
    close_div();

    open_div( 'nodisplay', "id='lieferant_anfangsguthaben_form'" );
      formular_buchung_lieferant_anfangsguthaben();
    close_div();

  close_fieldset();
}

$kontostand = lieferantenkontostand($lieferanten_id);
$pfandkontostand = lieferantenpfandkontostand($lieferanten_id);

medskip();
$cols = 9;
open_table('list');
    open_th( '', '', 'Typ' );
    open_th( '', '', 'Valuta' );
    open_th( '', '', 'Buchung' );
    open_th( '', '', 'Informationen' );
    open_th( '', '', 'Pfand' );
    open_th( '', '', 'Pfandkonto' );
    open_th( '', '', 'Waren' );
    open_th( '', '', 'Sonstiges' );
    open_th( '', '', 'Buchung' );
    open_th( '', '', 'Kontostand' );

  open_tr( 'summe' );
    open_td( 'right', "colspan='5'", 'Kontostand:' );
    open_td( 'number', '', price_view( $pfandkontostand ) );
    open_td();
    open_td();
    open_td();
    open_td( 'number', '', price_view( $kontostand ) );

  $konto_result = sql_transactions( 0, $lieferanten_id );
  $vert_result = sql_bestellungen_soll_lieferant( $lieferanten_id );

  $summe = $kontostand;
  $pfandsumme = $pfandkontostand;
  $konto_row = current($konto_result);
  $vert_row = current($vert_result);
  while( $konto_row or $vert_row ) {
    open_tr();
    if( ( $vert_row ? $vert_row['valuta_kan'] : '0' ) > ( $konto_row ? $konto_row['valuta_kan'] : '0' ) ) {
      //Eintrag in Konto ist Älter -> Verteil ausgeben
      $bestell_id = $vert_row['gesamtbestellung_id'];
      $pfand_leer_soll = $vert_row['pfand_leer_brutto_soll'];
      $pfand_voll_soll = $vert_row['pfand_voll_brutto_soll'];
      $pfand_soll = $pfand_leer_soll + $pfand_voll_soll;
      $waren_soll = $vert_row['waren_brutto_soll'];
      $extra_soll = $vert_row['extra_brutto_soll'];
      $soll = $pfand_soll + $waren_soll + $extra_soll;

      open_td('bold', '', 'Bestellung' );
      open_td('', '', $vert_row['valuta_trad'] );
      open_td('', '', $vert_row['lieferdatum_trad'] );
      open_td(); 
        echo 'Bestellung: ' . fc_link( 'lieferschein', array(
           'bestell_id' => $bestell_id, 'text' => $vert_row['name'], 'class' => 'href'
         , 'spalten' => ( PR_COL_NAME | PR_COL_BESTELLMENGE | PR_COL_LPREIS | PR_COL_LIEFERMENGE | PR_COL_NETTOSUMME | PR_COL_ENDSUMME )
        ) );
        open_span( 'small', '', $vert_row['rechnungsnummer'] );
      open_td( 'number', '', price_view( $pfand_soll ) );
      open_td( 'number', '', price_view( $pfandsumme ) );
      open_td( 'number', '', price_view( $waren_soll ) );
      open_td( 'number', '', price_view( $extra_soll ) );
      open_td( 'number bold', '', price_view( $soll ) );
      open_td( 'number', '', price_view( $summe ) );

      $summe -= $soll;
      $pfandsumme -= $pfand_soll;
      $vert_row = next($vert_result);

    } else {

      $text = ( ( $konto_row['konterbuchung_id'] >= 0 ) ? 'Zahlung' : 'Verrechnung' );
      open_td( 'bold', '', fc_link( 'edit_buchung', "transaktion_id={$konto_row['id']},text=$text,class=href" ) );
      open_td('', '', $konto_row['valuta_trad'] );
      open_td('', '', $konto_row['date'] );
      open_td();
        open_div( '', '', fc_link( 'edit_buchung', array(
          'transaktion_id' => $konto_row['id'], 'text' => $konto_row['notiz'], 'class' => 'href' ) ) );
        open_div();
          buchung_kurzinfo( $konto_row['konterbuchung_id'] );
        close_div();
      open_td();
      open_td();
      open_td();
      open_td();
      open_td( 'number bold', '', price_view( $konto_row['summe'] ) );
      open_td( 'number', '', price_view( $summe ) );

      $summe -= $konto_row['summe'];
      $konto_row = next($konto_result);
    }
  }

  open_tr( 'summe' );
    open_td( 'right', "colspan='5'", 'Startsaldo:' );
    open_td( 'number', '',  price_view( $pfandsumme ) );
    open_td();
    open_td();
    open_td();
    open_td( 'number', '', price_view( $summe ) );

close_table();

?>
