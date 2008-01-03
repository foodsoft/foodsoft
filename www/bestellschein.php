<?php
//
// bestellschein.php:
// - wenn bestell_id (oder bestellungs_id...) uebergeben:
//   detailanzeige, abhaengig vom status der bestellung
// - wenn keine bestell_id uebergeben:
//   auswahlliste aller bestellungen zeigen
//   (ggf. mit filter "status")
//

error_reporting(E_ALL);
//$_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

assert( $angemeldet ) or exit();

if( get_http_var( 'bestellungs_id', 'u' ) ) {
  $bestell_id = $bestellungs_id;
  $self_fields['bestell_id'] = $bestell_id;
} else {
  get_http_var( 'bestell_id', 'u', false, true );
}

get_http_var( 'action', 'w', '' );
echo "<!-- action: $action -->";
switch( $action ) {
  case 'changeState':
    fail_if_readonly();
    nur_fuer_dienst(1,3,4);
    need_http_var( 'change_id', 'u' );
    need_http_var( 'change_to', 'w' );
    echo "<!-- going to call changeState: $change_id, $change_to -->";
    if( changeState( $change_id, $change_to ) ) {
      if( ! $bestell_id ) {  // falls nicht bereits in detailanzeige:
        switch( $change_to ) {
          case STATUS_LIEFERANT:   // bestellschein oder ...
          case STATUS_VERTEILT:    // ... lieferschein anzeigen:
            echo "
              <script type='text/javascript'>
                neuesfenster('index.php?window=bestellschein&bestell_id=$change_id','bestellschein');
              </script>
            ";
          break;
        }
      }
    }
    break;

  case 'insert':
    fail_if_readonly();
    nur_fuer_dienst(1,3,4);
    need_http_var( 'produkt_id', 'u' );
    need_http_var( 'menge', 'f' );
    if( $bestell_id && ( $menge > 0 ) ) {
      zusaetzlicheBestellung( $produkt_id, $bestell_id, $menge );
    }

  default:
    break;
}

if( ! $bestell_id ) {
  // auswahl praesentieren, abhaengig von $state oder $area:
  if( ! get_http_var( 'state', 'w', false, true ) ) {
    switch( $area ) {
      case 'lieferschein':
        $state = STATUS_VERTEILT;
        break;
      case 'bestellschein':
        $state = array( STATUS_BESTELLEN, STATUS_LIEFERANT );
        break;
      default:
        $state = FALSE;
    }
    $self_fields['state'] = $state;
  }
  $result = sql_bestellungen( $state );
  select_bestellung_view($result, 'Liste der Bestellungen', $hat_dienst_IV, $dienst > 0 );
  return;
}

get_http_var( 'gruppen_id', 'u', 0, true );

if( $gruppen_id and ! in_array( $gruppen_id, $specialgroups ) ) {
  if( $gruppen_id != $login_gruppen_id )
    nur_fuer_dienst(4);
  $gruppen_name = sql_gruppenname($gruppen_id);
}
$state = getState($bestell_id);

$default_spalten = PR_COL_NAME | PR_COL_LPREIS | PR_COL_VPREIS | PR_COL_MWST | PR_COL_PFAND;
switch($state){    // anzeigedetails abhaengig vom Status auswaehlen
  case STATUS_BESTELLEN:
    $editable = FALSE;
    if( $gruppen_id ) {
      $default_spalten |= ( PR_COL_BESTELLMENGE | PR_COL_ENDSUMME );
    } else {
      $default_spalten
        |= ( PR_COL_BESTELLMENGE | PR_COL_BESTELLGEBINDE | PR_COL_NETTOSUMME | PR_COL_BRUTTOSUMME
             | PR_ROWS_NICHTGEFUELLT );
    }
    $title="Bestellschein (vorläufig)";
    break;
  case STATUS_LIEFERANT:
    $editable= FALSE;
    if( $gruppen_id ) {
      $default_spalten |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME );
    } else {
      $default_spalten
        |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_LIEFERGEBINDE
             | PR_COL_NETTOSUMME | PR_COL_BRUTTOSUMME | PR_ROWS_NICHTGEFUELLT );
    }
    $title="Bestellschein";
    // $selectButtons = array("zeigen" => "bestellschein", "pdf" => "bestellt_faxansicht" );
    break;
  case STATUS_VERTEILT:
    if( $gruppen_id ) {
      $editable= FALSE;
      $default_spalten |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME );
    } else {
      // ggf. liefermengen aendern lassen:
      $editable = (!$readonly) && ( $hat_dienst_I or $hat_dienst_III or $hat_dienst_IV );
      $default_spalten
        |= ( PR_COL_BESTELLMENGE | PR_COL_LIEFERMENGE | PR_COL_LIEFERGEBINDE
             | PR_COL_NETTOSUMME | PR_COL_BRUTTOSUMME | PR_ROWS_NICHTGEFUELLT );
    }
    $title="Lieferschein";
    break;
  default: 
    ?> <div class='warn'>Keine Detailanzeige verfügbar</div> <?
    return;
}

get_http_var( 'spalten', 'w', $default_spalten, true );

  // liefermengen aktualisieren:
//  Hier werden die vom Formular übergebenen Werte ausgewertet
//  FIXME in obiges switch-statement integrieren
  //
  if( $editable and $state == STATUS_VERTEILT ) {
    $produkte = sql_bestellprodukte($bestell_id);
    while  ($produkte_row = mysql_fetch_array($produkte)) {
      $produkt_id =$produkte_row['produkt_id'];
      if( get_http_var( 'liefermenge'.$produkt_id ) ) {
        preisdatenSetzen( & $produkte_row );
        $mengenfaktor = $produkte_row['mengenfaktor'];
        $liefermenge = $produkte_row['liefermenge'] / $mengenfaktor;
        if( abs( ${"liefermenge$produkt_id"} - $liefermenge ) > 0.001 ) {
          $liefermenge = ${"liefermenge$produkt_id"};
          changeLiefermengen_sql( $liefermenge * $mengenfaktor, $produkt_id, $bestell_id );
        }
      }

    }
    // Als nicht geliefert markierte Produkte löschen
    if(get_http_var( 'nichtGeliefert')){
    	foreach($nichtGeliefert as $p_id){
    		nichtGeliefert($bestell_id, $p_id);
    	}
    }
  }

  //infos zur gesamtbestellung auslesen 
  $bestellung = sql_bestellung($bestell_id);

	echo "<h1>$title</h1>";

  ?>
    <table width='100%' class='layout'>
      <tr>
        <td style='text-align:left;padding-bottom:1em;'>
         <?
         bestellung_overview($bestellung,$gruppen_id,$gruppen_id);
         ?>
        </td>
        <td style='text-align:right;padding-bottom:1em;' id='option_menu'>
        </td>
      </tr>
    </table>
  <?

  option_menu_row( "<th colspan='2'>Anzeigeoptionen</th>" );

  option_menu_row(
    " <td>Gruppenansicht:</td>
      <td><select id='select_group' onchange=\"select_group('"
      . self_url( 'gruppen_id' ) . "');\">
    " . optionen_gruppen(
        $bestell_id
       , false
       , $gruppen_id
       , "Alle (Gesamtbestellung)"
      , ( $hat_dienst_IV ? false : $login_gruppen_id )
      , $specialgroups
      ) . " </select></td>"
  );
  $abschluss_option = ""; // ggf. erst als letzte Option ausgeben (s.u.)!

  if( $state == STATUS_VERTEILT
      and $dienst == 4
      and $dienstkontrollblatt_id > 0
      and ! $readonly
      and ! $bestellung['abrechnung_dienstkontrollblatt_id']
  ) {

    $abschluss_option = "
      <td>Rechnungsabschluss:</td>
      <td class='number'><form method='post' action='" . self_url() . "'>
         <input type='hidden' name='action' value='abschluss1'>
         <input type='submit' class='button' name='Abschliessen' value='jetzt durchführen'
         title='Wenn alles abgeglichen ist, könnt ihr jetzt diese Bestellung abschliessen!'
         >
      </form></td>
    ";
    switch( $action ) {
      case 'abschluss1':
        ?>
        <form method='post' action='<? echo self_url(); ?>'>
          <input type='hidden' name='action' value='abschluss2'>
          <fieldset class='small_form'>
            <legend>
              <img src='img/close_black_trans.gif' class='button' title='Schliessen' alt='Schliessen'
                onclick="window.location.href='<? echo self_url(); ?>';">
              Bestellung abschliessen: Schritt 1...
            </legend>
            <table>
              <tr>
                <td><label>Liefermengen sind abgeglichen:</label></td>
                <td><input type='checkbox' name='abschluss_liefermengen' value='yes'></td>
              </tr>
              <tr>
                <td><label>Verteilmengen sind abgeglichen:</label></td>
                <td><input type='checkbox' name='abschluss_verteilmengen' value='yes'></td>
              </tr>
              <tr>
                <td><label>Produktpreise sind abgeglichen:</label></td>
                <td><input type='checkbox' name='abschluss_preise' value='yes'></td>
              </tr>
              <tr>
                <td><label>Basarzuteilungen sind erfasst:</label></td>
                <td><input type='checkbox' name='abschluss_basar' value='yes'></td>
              </tr>
              <tr>
                <td><label>Rechnungsnummer des Lieferanten:</label></td>
                <td><input type='text' size='20' name='abschluss_rechnungsnummer' value=''
                   title='Rechnungsnummer der Lieferantenrechnung zu dieser Bestellung'>
                </td>
              </tr>
              <tr>
                <td><label>Pfandgutschrift des Lieferanten:</label></td>
                <td><input type='text' size='8' name='abschluss_pfandgutschrift'
                   title='Gutschrift fuer Pfandrueckgabe in Lieferantenrechnung zu dieser Bestellung'
                     value='<? printf( "%.2lf", sql_bestellung_pfandsumme( $bestell_id )   ); ?>'>
                </td>
              </tr>
              <tr>
                <td><label>Rechnungssumme des Lieferanten:</label></td>
                <td><input type='text' size='8' name='abschluss_rechnungssumme'
                   title='Endsumme der Lieferantenrechung (inclusive aller Pfandgutschriften)'
                     value='<? printf( "%.2lf", sql_bestellung_rechnungssumme( $bestell_id ) ); ?>'>
                    <td><input type='submit' name='OK' value='OK'></td>
                </td>
              </tr>
            </table>
          </fieldset>
        </form>
        <?
        $abschluss_option = '';
        break;
      case 'abschluss2':
        get_http_var( 'abschluss_liefermengen', 'w', 'no' );
        get_http_var( 'abschluss_verteilmengen', 'w', 'no' );
        get_http_var( 'abschluss_preise', 'w', 'no' );
        get_http_var( 'abschluss_basar', 'w', 'no' );
        need_http_var( 'abschluss_rechnungssumme', 'f' );
        need_http_var( 'abschluss_rechnungsnummer', 'H' );
        need_http_var( 'abschluss_pfandgutschrift', 'f' );
        $rechnungssumme = sql_bestellung_rechnungssumme( $bestell_id );
        $fehl = sprintf( "%.2lf", $abschluss_rechnungssumme - $abschluss_pfandgutschrift - $rechnungssumme );
        if( $abschluss_liefermengen == 'yes'
            and $abschluss_verteilmengen == 'yes'
            and $abschluss_preise == 'yes'
            and $abschluss_basar == 'yes' ) {
          ?>
          <form method='post' action='<? echo self_url(); ?>'>
            <input type='hidden' name='action' value='abschluss3'>
            <input type='hidden' name='abschluss_rechnungsnummer' value='<? echo $abschluss_rechnungsnummer; ?>'>
            <input type='hidden' name='abschluss_rechnungssumme' value='<? echo $abschluss_rechnungssumme; ?>'>
            <input type='hidden' name='abschluss_pfandgutschrift' value='<? echo $abschluss_pfandgutschrift; ?>'>
            <input type='hidden' name='abschluss_fehl' value='<? echo $fehl; ?>'>
            <fieldset class='small_form'>
              <legend>
                <img src='img/close_black_trans.gif' class='button' title='Schliessen' alt='Schliessen'
                  onclick="window.location.href='<? echo self_url(); ?>';">
                Bestellung abschliessen: Schritt 2...
              </legend>
              <table>
                <tr>
                  <td>Gruppen in Rechnung gestellter Betrag:</td>
                  <td class='number'><? printf( '%.2lf', $rechnungssumme ); ?></td>
                </tr>
                <tr>
                  <td>Vom Lieferanten berechnet (ohne Pfandgutschrift):</td>
                  <td class='number'><? printf( '%.2lf', $abschluss_rechnungssumme - $abschluss_pfandgutschrift ); ?></td>
                </tr>
                <? if( $fehl > 0 ) { ?>
                  <tr>
                    <td>Somit als Verlust der FC zu verbuchender Fehlbetrag:</td>
                    <td class='number' style='font-weight:bold'><? printf( '%.2lf', $fehl ); ?></td>
                  </tr>
                  <tr>
                    <td>Kommentar:</td>
                    <td><kbd>Verlust: <? $bestellung['name']; ?>: <input type='text' size='40' name='kommentar' value=''></kbd></td>
                  </tr>
                <? } else if( $fehl < 0 ) { ?>
                  <tr>
                    <td>Somit als Verlustrücklage der FC zu verbuchen:</td>
                    <td class='number' style='font-weight:bold;'><? printf( '%.2lf', -$fehl ); ?></td>
                  </tr>
                  <tr>
                    <td>Kommentar:</td>
                    <td><kbd>Ruecklage: <? echo $bestellung['name']; ?>: <input type='text' size='40' name='abschluss_kommentar' value=''></kbd></td>
                  </tr>
                <? } else { ?>
                  <tr>
                    <td colspan='2'>(Klasse: Punktlandung!)</td>
                  </tr>
                <? } ?>
                <tr>
                  <td>&nbsp;</td>
                  <td class='number'><input type='submit' name='OK' value='Abschluss bestätigen'></td>
                </tr>
              </table>
            </fieldset>
          </form>
          <?
          $abschluss_option = '';
        }
        break;
      case 'abschluss3':
        get_http_var( 'abschluss_kommentar', 'H', '' );
        get_http_var( 'abschluss_rechnungsnummer', 'H', '' );
        need_http_var( 'abschluss_rechnungssumme', 'f' );
        need_http_var( 'abschluss_fehl', 'f' );
        need_http_var( 'abschluss_pfandgutschrift', 'f' );
        /* echo "Abschluss:
          nr: $abschluss_rechnungsnummer <br>
          summe: $abschluss_rechnungssumme <br>
          pfand: $abschluss_rechnungssumme <br>
          fehl: $abschluss_pfandgutschrift <br>
          kommentar: $abschluss_kommentar <br>
        "; */
        sql_update( 'gesamtbestellungen', $bestell_id, array(
          'rechnungsnummer' => $abschluss_rechnungsnummer
        , 'rechnungssumme' => $abschluss_rechnungssumme
        , 'abrechnung_dienstkontrollblatt_id' => $dienstkontrollblatt_id
        ) );
        if( $abschluss_fehl > 0.005 ) {
          sql_doppelte_transaktion(
            array( 'konto_id' => -1, 'lieferanten_id' => $bestellung['lieferanten_id'] )
          , array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id() )
          , $abschluss_fehl
          , $bestellung['lieferung']
          , "Verlust: {$bestellung['name']}: " . $kommentar
          );
        } else if ( $abschluss_fehl < -0.005 ) {
          sql_doppelte_transaktion(
            array( 'konto_id' => -1, 'gruppen_id' => sql_muell_id() )
          , array( 'konto_id' => -1, 'lieferanten_id' => $bestellung['lieferanten_id'] )
          , -$abschluss_fehl
          , $bestellung['lieferung']
          , "Ruecklage: {$bestellung['name']}: " . $abschluss_kommentar
          );
        }
        if( abs($abschluss_pfandgutschrift) > 0.005 ) {
          sql_lieferant_glass(
            $bestellung['lieferanten_id'], $abschluss_pfandgutschrift, $bestellung['lieferung']
          );
        }
        $abschluss_option = '';
        break;
      default:
      break;
    }
  }

  products_overview(
    $bestell_id,
    $editable,   // Liefermengen edieren zulassen?
    $editable,   // Preise edieren zulassen?
    $spalten,    // welche Tabellenspalten anzeigen
    $gruppen_id, // Gruppenansichte (0: alle)
    true,        // angezeigte Spalten auswaehlen lassen
    true,        // Gruppenansicht auswaehlen lassen
    true         // Option: Anzeige nichtgelieferte zulassen
  );

  switch( $state ) {
    case STATUS_LIEFERANT:
    case STATUS_VERTEILT:
      if( ! $readonly and ! $gruppen_id and ( $dienst == 1 || $dienst == 3 || $dienst == 4 ) ) {
        echo "
          <h3>Zusätzliches Produkt eintragen (wirkt wie Basar<b>bestellung</b>):</h3>
          <form method='post' action='" . self_url() . "'> " . self_post() . "
            <input type='hidden' name='action' value='insert'>
        ";
        select_products_not_in_list($bestell_id);
        echo "
          <label>Menge:</label>
          <input type='text' size='6' style='text-align:right;' name='menge' value='0'>
          <input type='submit' value='Produkt hinzufügen'>
          </form>
        ";
      }
      break;
    default:
      break;
  }

  if( $abschluss_option ) {
    option_menu_row( $abschluss_option );
  }

?>

