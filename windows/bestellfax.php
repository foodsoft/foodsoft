<?php
//
// bestellfax.php
//

error_reporting(E_ALL);
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

assert( $angemeldet ) or exit();

need_http_var( 'bestell_id', 'U', true );

$bestellung = sql_bestellung( $bestell_id );
$status = $bestellung['rechnungsstatus'];
$lieferdatum_trad = "{$wochentage[ $bestellung['lieferdatum_dayofweek'] ]}, {$bestellung['lieferdatum_trad']}";

$lieferant = sql_lieferant( $bestellung['lieferanten_id'] );

get_http_var( 'lieferant_name', 'H', $lieferant['name'] );
get_http_var( 'lieferant_strasse', 'H', $lieferant['strasse'] );
get_http_var( 'lieferant_ort', 'H', $lieferant['ort'] );
get_http_var( 'lieferant_fax', 'H', $lieferant['fax'] );
get_http_var( 'lieferant_email', 'H', $lieferant['mail'] );

get_http_var( 'lieferant_anrede', 'H', $lieferant['anrede'] );
if( ! ( $lieferant_anrede = trim( $lieferant_anrede ) ) )
  $lieferant_anrede = 'Sehr geehrte Damen und Herren,';
get_http_var( 'lieferant_grussformel', 'H', $lieferant['grussformel'] );
if( ! ( $lieferant_grussformel = trim( $lieferant_grussformel ) ) )
  $lieferant_grussformel = 'Mit freundlichen Grüßen,';

get_http_var( 'fc_name', 'H', $lieferant['fc_name'] );
if( ! ( $fc_name = trim( $fc_name ) ) )
  $fc_name = $foodcoop_name;
get_http_var( 'fc_strasse', 'H', $lieferant['fc_strasse'] );
get_http_var( 'fc_ort', 'H', $lieferant['fc_ort'] );
get_http_var( 'fc_kundennummer', 'H', $lieferant['kundennummer'] );

get_http_var( 'besteller_name', 'H', $coopie_name );

get_http_var( 'action', 'w', '' );
$readonly and $action = '';

get_http_var( 'spalten', 'u', $lieferant['bestellfaxspalten'], true );
$spalten &= PR_FAXOPTIONS;

$gruppen_id = 0;

// echo "action: [$action]";
switch( $action ) {
  case 'faxansicht_save':
    sql_update( 'lieferanten', $lieferant['id'], array(
      'strasse' => $lieferant_strasse
    , 'ort' => $lieferant_ort
    , 'fax' => $lieferant_fax
    , 'mail' => $lieferant_email
    , 'anrede' => $lieferant_anrede
    , 'grussformel' => $lieferant_grussformel
    , 'fc_name' => $fc_name
    , 'fc_strasse' => $fc_strasse
    , 'fc_ort' => $fc_ort
    , 'kundennummer' => $fc_kundennummer
    , 'bestellfaxspalten' => $spalten
    ) );
    $lieferant = sql_lieferant( $bestellung['lieferanten_id'] );

    break;

  default:
    break;
}

get_http_var( 'export', 'w', '' );
if( $export == 'bestellschein' ) {
  fc_openwindow( 'self', 'window_id=pdf,download=bestellfax' );
}

if( isset( $download ) && ( $download == 'bestellfax' ) ) {
  $fc_kundennummer = trim( $fc_kundennummer );

  $tex = file_get_contents( 'templates/bestellschein.tex' );
  foreach( array( 'lieferant_name', 'lieferant_strasse', 'lieferant_ort', 'lieferant_fax'
                , 'lieferant_email' , 'lieferant_anrede', 'lieferant_grussformel'
                , 'fc_kundennummer' , 'fc_name', 'fc_strasse', 'fc_ort'
                , 'besteller_name', 'lieferdatum_trad'
  ) as $field ) {
    $tex = preg_replace( "/@@$field@@/", tex_encode( $GLOBALS[ $field ] ) , $tex );
  }
  $tex = preg_replace( '/@@tabelle@@/', bestellfax_tex( $bestell_id, $spalten ), $tex );
  // file_put_contents( '/tmp/b.tex', $tex );
  if( ( $pdf = tex2pdf( $tex ) ) ) {
    $downloadname = 'Bestellschein.pdf';
    // header("Content-Type: text/plain");
    // echo $tex;
    header( 'Content-Type: application/pdf' );
    header( "Content-Disposition: filename=$downloadname" );
    echo $pdf;
    return;
  } else {
    header( 'Content-Type: text/html' );
    open_div( 'warn', '', 'Konvertierung nach PDF fehlgeschlagen' );
  }
}


open_table( 'layout hfill' );
  open_td( 'left' );
    bestellung_overview( $bestell_id, $gruppen_id );
  open_td( 'right qquad floatright' );
    open_table( 'menu', "id='option_menu_table'" );
      open_th( '', "colspan='2'", 'Anzeigeoptionen' );
    close_table();
close_table();



echo "<h1>Bestellschein - Faxansicht</h1>";
$editable = false;

if( $lieferant['katalogformat'] == 'bnn' ) {
  // die b-nummern sind eigentlich a-nummern (in zukunft besser gar nicht erfassen?):
  if( $spalten & PR_COL_BNUMMER ) {
    if( $spalten & PR_COL_BNUMMER ) {
      $spalten = $self_fields['spalten'] = ( ( $spalten | PR_COL_ANUMMER ) & ~ PR_COL_BNUMMER );
      $msg = 'WARNUNG: Katalogformat BNN kennt keine Bestellnummern - Spaltenauswahl wurde korrigiert!';
      open_div( 'warn medskip', '', $msg );
      bigskip();
      $js_on_exit[] = "alert('$msg');";
    }
  }
}

$faxform_id = open_form( '', 'action=faxansicht_save,export=' );

  open_table();
    open_tr();
      open_th( 'medskip', '', 'Lieferanschrift: ' );
      open_th( 'quad medskip', '', 'Name:' );
      open_td( 'quad medskip', '', string_view( $fc_name, 40, 'fc_name' ) );
    open_tr();
      open_th();
      open_th( 'quad medskip', '', 'Kundennummer:' );
      open_td( 'quad medskip', '', string_view( $fc_kundennummer, 40, 'fc_kundennummer' ) );
    open_tr();
      open_th();
      open_th( 'quad smallskip', '', 'Strasse:' );
      open_td( 'quad smallskip', '', string_view( $fc_strasse, 40, 'fc_strasse' ) );
    open_tr();
      open_th();
      open_th( 'quad smallskip', '', 'Ort:' );
      open_td( 'quad smallskip', '', string_view( $fc_ort, 40, 'fc_ort' ) );
    open_tr();
      open_th( 'bigskip', '', 'Lieferant:' );
      open_th( 'quad bigskip', '', 'Name:' );
      open_td( 'quad bigskip', '', string_view( $lieferant_name, 40, 'lieferant_name' ) );
    open_tr( '' );
      open_th();
      open_th( 'quad smallskip', '', 'Strasse:' );
      open_td( 'quad smallskip', '', string_view( $lieferant_strasse, 40, 'lieferant_strasse' ) );
    open_tr( '' );
      open_th();
      open_th( 'quad smallskip', '', 'Ort:' );
      open_td( 'quad smallskip', '', string_view( $lieferant_ort, 40, 'lieferant_ort' ) );
    open_tr( '' );
      open_th();
      open_th( 'quad smallskip', '', 'Fax:' );
      open_td( 'quad smallskip', '', string_view( $lieferant_fax, 40, 'lieferant_fax' ) );
    open_tr( '' );
      open_th();
      open_th( 'quad smallskip', '', 'email:' );
      open_td( 'quad smallskip', '', string_view( $lieferant_email, 40, 'lieferant_email' ) );
    open_tr();
      open_th();
      open_th( 'bigskip', '', 'Anrede:' );
      open_td( 'quad bigskip', "colspan='1'", string_view( $lieferant_anrede, 40, 'lieferant_anrede' ) );

    open_tr();
      open_th( 'smallskip' );
      open_th( 'smallskip' );
      open_td( 'quad smallskip', '', "zur Lieferung am $lieferdatum_trad bestellen wir:" );

  close_table();


  bigskip();
  bestellschein_view(
    $bestell_id 
  , false    // Mengen...
  , false    // ... und Preise hier _nicht_ edieren lassen
  , $spalten | PR_FAXANSICHT
  , 0        // Gruppenansicht: alle
  , true     // angezeigte Spalten auswaehlen lassen
  , false    // nichtgelieferte nicht anzeigen
  );

  bigskip();
  open_table();
    open_tr();
      open_th( 'medskip', '', 'Grußformel:' );
      open_td( 'medskip qquad', '', string_view( $lieferant_grussformel, 40, 'lieferant_grussformel' ) );
    open_tr();
      open_th( 'bigskip', '', 'Name Besteller:' );
      open_td( 'bigskip qquad', '', string_view( $besteller_name ) );
  close_table();

close_form();

open_div( 'right medskip' );
  $confirm = ( (int)$lieferant['bestellfaxspalten'] !== (int)$spalten ) ? "if( confirm( 'Spaltenauswahl f&uuml;r diesen Lieferanten wurde ge&auml;ndert - sind sie sicher?' ) ) " : '';
  open_span( 'qquad button', "onclick=\" $confirm { f=document.forms.form_$faxform_id;f.elements.export.value='bestellschein'; f.submit(); } \"", 'PDF erzeugen' );
  open_span( 'qquad button', "onclick=\" $confirm document.forms.form_$faxform_id.submit(); \"", 'Speichern' );
close_div()    ;

open_option_menu_row();
  open_td( '', "colspan='2'", fc_link( 'bestellschein', "class=qquad href,bestell_id=$bestell_id,text=zur Normalansicht..." ) );
close_option_menu_row();

?>
