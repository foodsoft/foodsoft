<?php

// terraabgleich.php (name ist historisch: nuetzlich auch fuer andere lieferanten!)
//
// sucht in produktliste und preishistorie nach inkonsistenzen,
// und nach unterschieden zum Terra-katalog,
// macht ggf. verbesserungsvorschlaege und erlaubt aenderungen
//
// ausgabe wird durch folgende variable bestimmt:
// - produkt_id: fuer detailanzeige ein produkt
// - bestell_id: erlaubt auswahl preiseintrag fuer diese bestellung (nur mit produktid)
// - lieferanten_id: anzeige aller produkte eines lieferanten

assert( $angemeldet ) or exit();

$editable = ( ! $readonly and ( $dienst == 4 ) );

$detail = get_http_var('produkt_id','u',0,true);
get_http_var('bestell_id','u',0,true);

$subtitle = 'Produktddetails';
if( $detail ) {
  if( $bestell_id )
    $subtitle = $subtitle . " - Auswahl Preiseintrag";
  else
    $subtitle = $subtitle . " - Detailanzeige";
}
setWindowSubtitle( $subtitle );
setWikiHelpTopic( "foodsoft:datenbankabgleich" );

if( $detail ) {
  $produkt = sql_produkt_details( $produkt_id );
  $lieferanten_id = $produkt['lieferanten_id'];
  $produkt_name = $produkt['name'];
} else {
  need_http_var( 'lieferanten_id','u',true );
}
$lieferanten_name = lieferanten_name( $lieferanten_id );
$is_terra = ( $lieferanten_name == 'Terra' );

get_http_var( 'action','w','' );
$editable or $action = '';

if( $detail ) {
  $produkt_ids = array( $produkt_id );

  if( $action == 'zeitende_setzen' ) {
    need_http_var('preis_id','u');
    need_http_var('zeitende','H');
    sql_update( 'produktpreise', $preis_id, array( 'zeitende' => $zeitende ) );
  }
  if( $action == 'artikelnummer_setzen' ) {
    need_http_var( 'anummer', 'H' );
    sql_update( 'produkte', $produkt_id, array( 'artikelnummer' => $anummer ) );
  }
  if( $action == 'neuer_preiseintrag' ) {
    action_form_preiseintrag();
  }

  if( $bestell_id ) {
    if( $action == 'preiseintrag_waehlen' ) {
      need_http_var( 'preis_id','u' );
      doSql ( "UPDATE bestellvorschlaege
         SET produktpreise_id='$preis_id'
         WHERE gesamtbestellung_id='$bestell_id' AND produkt_id='$produkt_id'
      ", LEVEL_IMPORTANT, "Auswahl Preiseintrag fehlgeschlagen: " );
    }
  }
} else {
  $result = sql_produkte_von_lieferant_ids( $lieferanten_id ) {
  $produkt_ids = mysql2array( $result, 'id', 'id' );
}

?>
  <table width="100%">
    <colgroup> <col width="7%"> <col> </colgroup>
    <tr> <th class="outer">A-Nr.</th>
         <th class="outer">Artikeldaten <? echo $produkt_name; ?></th> </tr>
    <?
      foreach( $produkt_ids as $produkt_id ) {
        do_artikel( $produkt_id, $detail );
      }
    ?>
  </table>
<?


// do_artikel
// wird aus der hauptschleife aufgerufen, um einen artikel aus der Produktliste anzuzeigen
//
function do_artikel( $produkt_id, $detail ) {
  static $outerrow;
  global $mysqljetzt, $is_terra, $bestell_id;

  isset( $outerrow ) ? ++$outerrow : $outerrow = 0;
  echo "\n<tr id='row$outerrow'>";

  $artikel = sql_produkt_details( $produkt_id );

  // flag: neuen preiseintrag vorschlagen (falls gar keiner oder fehlerhaft):
  //
  $neednewprice = FALSE;

  // flag: suche nach artikelnummer vorschlagen (falls kein Treffer bei Katalogsuche):
  //
  $neednewarticlenumber = FALSE;

  // werte fuer neuen preiseintrag initialisieren:
  //
  unset( $newfc );
  $newfc['verteileinheit'] = FALSE;
  $newfc['liefereinheit'] = FALSE;
  $newfc['gebindegroesse'] = FALSE;
  $newfc['preis'] = FALSE;
  $newfc['bnummer'] = FALSE;
  $newfc['mwst'] = FALSE;
  $newfc['pfand'] = FALSE;

  ?> <th class='outer' style='vertical-align:top;'> <?
  if( $detail ) {
    echo "$anummer<br>id:&nbsp;$produkt_id";
  } else {
    echo "<a class='blocklink'
      href=\"javascript:neuesfenster('" . self_url() . "&produkt_id=$produkt_id','produktdetails')\"
      title='Details...'
      onclick=\"document.getElementById('row$outerrow').className='modified';\"
      >$anummer<br>id:&nbsp;$produkt_id</a>
    ";
  }
  ?> </th><td class="outer" style="padding-bottom:1ex;"> <?

  ////////////////////////
  // Preishistorie: anzeigen, im Detail-Modus, sonst nur Test auf Konsistenz:
  //

  if( $detail ) {
    preishistorie_view( $produkt_id, $bestell_id, $editable );

  $preishistorie_konsistenztest( $produkt_id, $editable, "row$outerrow" );

  $prgueltig = false;
  if( $artikel['zeitstart'] ) {
    $prgueltig = true;
    if( ! $artikel['kan_liefereinheit'] ) {
      ?> <div class='warn'>FEHLER: keine gueltige Liefereinheit</div> <?
      $neednewprice = TRUE;
    }
    // FIXME: hier mehr tests machen!
  }

  ///////////////////////////
  // Artikeldaten aus foodsoft-Datenbank anzeigen:
  //

  ?>
    <div class='untertabelle' id='foodsoftdatenbank'>Foodsoft-Datenbank:</div>
    <table width='100%' class='numbers'>
      <tr>
        <th>Name</th>
        <th>B-Nr.</th>
        <th title='Liefer-Einheit: fuers Bestellen beim Lieferanten'>L-Einheit</th>
        <th title='Nettopreis beim Lieferanten'>L-Preis</th>
        <th title='Verteil-Einheit: fuers Bestellen und Verteilen bei uns'>V-Einheit</th>
        <th title='V-Einheiten pro Gebinde'>Gebinde</th>
        <th title='MWSt in Prozent'>MWSt</th>
        <th title='Pfand je V-Einheit'>Pfand</th>
        <th title='Endpreis je V-Einheit'>V-Preis</th>
      </tr>
      <tr>
       <td><? echo $artikel['name']; ?></td>
  <?

  if( $prgueltig ) {
  } else {
    ?> <td><div class="warn" style="text-align:center;">keine</div></td> <?
  }

  if( $prgueltig ) {
    ?>
      <td class='number'><? echo $artikel['bestellnummer']; ?></td>
      <td class='number'><? echo "{$artikel['kan_liefermult']} {$artikel['kan_liefereinheit']}"; ?></td>
      <td class='number'><? printf( "%8.2lf / %s", $artikel['nettolieferpreis'], $artikel['preiseinheit'] ); ?></td>
      <td class='number'><? echo "{$artikel['kan_fcmult']} {$artikel['kan_fceinheit']}"; ?></td>
      <td class='number'><? echo $artikel['gebindegroesse']; ?></td>
      <td class='number'><? echo $artikel['mwst']; ?></td>
      <td class='number'><? printf( "%.2lf", $artikel['pfand'] ); ?></td>
      <td class='number'><?
        printf( "%8.2lf / %s", $artikel['preis'], $artikel['kan_verteilmult', $artikel['kan_verteileinheit'] ); ?>
      </td>
    <?
  } else {
    ?> <td colspan='8'><div class='warn' style='text-align:center;'> - - - </div></td> <?
  }
  ?> </tr></table> <?

  /////////////////////////////
  // Artikeldaten im Katalog suchen und ggf. anzeigen:
  //
  if( ( $katalogeintraege = katalogsuche( $artikel ) ) ) {
    $katalogtreffer = $katalogeintraege['count'];
    if( ! $katalogtreffer ) {
      ?> <div class='warn'>Katalogsuche: Artikelnummer nicht gefunden!</div> <?
      if( $detail )
        formular_artikelnummer( $produkt_id, false, true );
      $katalog_datum = false;
    } else {
      $katalog_datum = $katalogeintraege[0]["terradatum"][0];
      $katalog_bnummer = $katalogeintraege[0]["terrabestellnummer"][0];
      $katalog_name = $katalogeintraege[0]["cn"][0];
      $katalog_einheit = $katalogeintraege[0]["terraeinheit"][0];
      $katalog_gebindegroesse = $katalogeintraege[0]["terragebindegroesse"][0];
      $katalog_herkunft =  $katalogeintraege[0]["terraherkunft"][0];
      $katalog_verband = $katalogeintraege[0]["terraverband"][0];
      $katalog_netto = $katalogeintraege[0]["terranettopreisincents"][0] / 100.0;
      $katalog_mwst = $katalogeintraege[0]["terramwst"][0];
      $katalog_brutto = $netto * (1 + $mwst / 100.0 );
      ?>
        <div class='untertabelle'>
          Artikelnummer gefunden in Katalog <? echo $katalog_datum; ?>
        </div>
        <table width='100%' class='numbers'>
          <tr>
            <th>B-Nr.</th>
            <th>Bezeichnung</th>
            <th>Einheit</th>
            <th>Gebinde</th>
            <th>Land</th>
            <th>Verband</th>
            <th>Netto</th>
            <th>MWSt</th>
            <th>Brutto</th>
          </tr>
          <tr>
            <td><? echo $katalog_bnummer; ?></td>
            <td><? echo $katalog_name; ?></td>
            <td><? echo $katalog_einheit; ?></td>
            <td><? echo $katalog_gebindegroesse; ?></td>
            <td><? echo $katalog_herkunft; ?></td>
            <td><? echo $katalog_verband; ?></td>
            <td><? echo $katalog_netto; ?></td>
            <td><? echo $katalog_netto; ?></td>
            <td><? echo $katalog_mwst; ?></td>
            <td><? echo $katalog_brutto; ?></td>
          </tr>
        </table>
      <?

      if( $detail )
        formular_artikelnummer( $produkt_id, true, false );

      kanonische_einheit( $katalog_einheit, &$kan_katalogeinheit, &$kan_katalogmult );

      ////////////////////////////////
      // aktuellsten preiseintrag mit Katalogeintrag vergleichen:
      //
      if( $prgueltig ) {
        // echo "<br>Foodsoft: Einheit: $kan_fcmult * $kan_fceinheit Gebinde: $fcgebindegroesse";
        // echo "<br>Terra: Einheit: $kan_terramult * $kan_terraeinheit Gebinde: $terragebindegroesse";

        $newfc['liefereinheit'] = $terragebindegroesse * $kan_terramult . " $kan_terraeinheit";
        if( $newfc['liefereinheit'] != "$kan_liefermult $kan_liefereinheit" ) {
          $neednewprice = TRUE;
          echo "<div class='warn'>Problem: L-Einheit stimmt nicht:
                 <p class='li'>Terra: <kbd>" . $terragebindegroesse * $kan_terramult . " $kan_terraeinheit</kbd></p>
                 <p class='li'>Foodsoft: <kbd>$kan_liefermult $kan_liefereinheit</kbd></p></div>";
        }

        $newfc['mwst'] = $mwst;
        if( abs( $fcmwst - $mwst ) > 0.005 ) {
          $neednewprice = TRUE;
          echo "<div class='warn'>Problem: MWSt-Satz stimmt nicht:
                    <p class='li'>Terra: <kbd>$mwst</kbd></p>
                    <p class='li'>Foodsoft: <kbd>$fcmwst</kbd></p></div>";
        }

        if( $kan_terraeinheit == 'KI' and $kan_fceinheit == 'ST' ) {
          // spezialfall: KIste mit vielen STueck inhalt ist ok!
          $newfc['verteileinheit'] = "$kan_fcmult ST";
          $newfc['gebindegroesse'] = ( ( $fcgebindegroesse > 0.001 ) ? $fcgebindegroesse : 1 );
          $newfc['preis'] = $brutto * $terragebindegroesse / $newfc['gebindegroesse'] + $fcpfand;
        } else {
          if( $kan_terraeinheit != $kan_fceinheit ) {
            $neednewprice = TRUE;
            $newfc['gebindegroesse'] = ( ( $fcgebindegroesse > 0.001 ) ? $fcgebindegroesse : 1 );
            echo "<div class='warn'>Problem: Einheit inkompatibel:
                    <p class='li'>Lieferant: <kbd>$kan_terraeinheit</kbd></p>
                    <p class='li'>Verteilung: <kbd>$kan_fceinheit</kbd></p></div>";
            $newfc['verteileinheit']
              = $terragebindegroesse * $kan_terramult / $newfc['gebindegroesse'] . " $kan_terraeinheit";
            $newfc['preis'] = $brutto * $terragebindegroesse / $newfc['gebindegroesse'] + $fcpfand;
          } else {
            $newfc['verteileinheit'] = "$kan_fcmult $kan_fceinheit";
            $newfc['gebindegroesse'] = $terragebindegroesse * $kan_terramult / $kan_fcmult;
            $newfc['preis'] = $brutto / $kan_terramult * $kan_fcmult + $fcpfand;
            if( abs( $kan_terramult * $terragebindegroesse - $kan_fcmult * $fcgebindegroesse ) > 0.001 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Gebindegroessen stimmen nicht: 
                        <p class='li'>Terra: <kbd>$terragebindegroesse * $kan_terramult $kan_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>$fcgebindegroesse * $kan_fcmult $kan_fceinheit</kbd></p></div>";
            }
            if( abs( ($fcpreis - $fcpfand) * $kan_terramult / $kan_fcmult - $brutto ) > 0.01 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Preise stimmen nicht (beide Brutto ohne Pfand):
                        <p class='li'>Terra: <kbd>$brutto je $kan_terramult $kan_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>"
                          . ($fcpreis-$fcpfand) * $kan_terramult / $kan_fcmult
                          . " je $kan_terramult $kan_terraeinheit </kbd></p></div>";
            }
          }
        }

        $newfc['bnummer'] = $terrabnummer;
        if( $terrabnummer != $fcbnummer ) {
          $neednewprice = TRUE;
          echo "<div class='warn'>Problem: Bestellnummern stimmen nicht:
                    <p class='li'>Terra: <kbd>$terrabnummer</kbd></p>
                    <p class='li'>Foodsoft: <kbd>$fcbnummer</kbd></p></div>";
        }

        // echo "<br>Verteil: new: Einheit: $newfcmult * $newfceinheit Gebinde: $newfcgebindegroesse";
        // echo "<br>Liefer: new: Einheit: $newliefermult * $newliefereinheit";
      } else {
        $neednewprice = TRUE;
        $newfc['liefereinheit'] = $kan_terramult * $terragebindegroesse . " $kan_terraeinheit";
        $newfc['verteileinheit'] = $kan_terramult . " $kan_terraeinheit";
        $newfc['gebindegroesse'] = $terragebindegroesse;
        $newfc['mwst'] = $mwst;
        $newfc['pfand'] = $fcpfand;
        $newfc['bnummer'] = $terrabnummer;
        $newfc['preis'] = $brutto / $terragebindegroesse + $fcpfand;
      }

    }

  } //  ... katalogsuche ...

  if( $detail ) {

    /////////////////////////
    // vorlage fuer neuen preiseintrag berechnen:
    //

    if( ! $newfc['gebindegroesse'] ) {
      $newfc['gebindegroesse'] = ( ( $fcgebindegroesse > 1 ) ? $fcgebindegroesse : 1 );
    }

    if( ! $newfc['verteileinheit'] ) {
      $newfc['verteileinheit'] =
        ( ( $kan_fcmult > 0.0001 ) ? $kan_fcmult : 1 )
        . ( $kan_fceinheit ? " $kan_fceinheit" : ' ST' );
    }

    if( ! $newfc['liefereinheit'] ) {
      if( $kan_liefereinheit and ( $kan_liefermult > 0.0001 ) )
        $newfc['liefereinheit'] = "$kan_liefermult $kan_liefereinheit";
      else
        $newfc['liefereinheit'] = $newfc['verteileinheit'];
    }

    if( ! $newfc['mwst'] ) {
      $newfc['mwst'] = ( $fcmwst ? $fcmwst : 7.0 );
    }

    if( ! $newfc['pfand'] ) {
       $newfc['pfand'] = ( $fcpfand ? $fcpfand : 0.00 );
    }

    if( ! $newfc['preis'] ) {
      $newfc['preis'] = ( $fcpreis ? $fcpreis : 0.00 );
    }

    if( ! $newfc['bnummer'] ) {
      $newfc['bnummer'] = $fcbnummer;
    }

    // echo "newverteileinheit: {$newfc['verteileinheit']}";
    // echo "newliefereinheit: {$newfc['liefereinheit']}";

    // restliche felder automatisch berechnen:
    //
    preisdatenSetzen( & $newfc );

    if( $neednewprice ) {
      echo "
        <div style='padding:1ex;' id='preiseintrag_form' class='small_form'>
          <form name='Preisform' method='post' action='" . self_url() . "'>" . self_post() . "
          <fieldset>
            <legend>Vorschlag neuer Preiseintrag:</legend>
      ";
    } else {
      echo "
        <div class='untertabelle'>
          <div id='preiseintrag_an_knopf'>
            <span class='button'
              onclick='preiseintrag_on();' >Neuer Preiseintrag...</span>
          </div>
        </div>
        <div style='display:none;' id='preiseintrag_form' class='small_form'>
          <form name='Preisform' method='post' action='" . self_url() . "'>" . self_post() . "
          <fieldset>
            <legend>
              <img class='button' title='Ausblenden' src='img/close_black_trans.gif'
               onclick='preiseintrag_off();'></img> Neuer Preiseintrag:</legend>
      ";
    }

    echo "
      <table id='preisform'>
        <tr>
          <td><label>Name:</label></td>
          <td><input type='text' size='42' name='newfcname' value='$name'
           title='Produktbezeichnung; bei abgepackten Sachen bitte auch die Menge angeben!'>
            <label>Notiz:</label> <input type='text' size='42' name='newnotiz' value='$notiz'
           title='Notiz: zum Beispiel aktuelle Herkunft, Verband oder Lieferant'>
          </td>
        </tr>
        <tr>
          <td><label>Bestell-Nr:</label></td>
          <td>
            <input type='text' size='8' name='newfcbnummer' value='{$newfc['bnummer']}'
              title='Bestellnummer (die, die sich bei Terra st&auml;ndig &auml;ndert!)'>

            <label>MWSt:</label>
              <input type='text' size='4' name='newfcmwst' id='newfcmwst' value='${newfc['mwst']}'
                 title='MWSt-Satz in Prozent'
                 onchange='preisberechnung_rueckwaerts();'
                 >

            <label>Pfand:</label> <input type='text' size='4' name='newfcpfand' id='newfcpfand' value='{$newfc['pfand']}'
              title='Pfand pro V-Einheit, bei uns immer 0.00 oder 0.16'
              onchange='preisberechnung_rueckwaerts();'
              >
          </td>
        </tr>
          <td><label>Verteil-Einheit:</label></td>
          <td>
            <input type='text' size='4' name='newfcmult' id='newfcmult' value='${newfc['kan_verteilmult']}'
             title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
             onchange='preisberechnung_fcmult();'
             >
            <select size='1' name='newfceinheit' id='newfceinheit'
             onchange='preisberechnung_default();'
            >
    " . optionen_einheiten( $newfc['kan_verteileinheit'] ) . "
            </select>
            <label>Endpreis:</label>
              <input title='Preis incl. MWSt und Pfand' type='text' size='8' id='newfcpreis' name='newfcpreis'
              value='${newfc['preis']}' onchange='preisberechnung_vorwaerts();'
              >
              / <span id='newfcendpreiseinheit'>{$newfc['kan_verteilmult']}
                  {$newfc['kan_verteileinheit']}</span>

            <label>Gebinde:</label>
              <input type='text' size='4' name='newfcgebindegroesse' id='newfcgebindegroesse' value='${newfc['gebindegroesse']}'
               title='Gebindegroesse in ganzen Vielfachen der V-Einheit'
               onchange='preisberechnung_gebinde();'
               >
              * <span id='newfcgebindeeinheit']>{$newfc['kan_verteilmult']}
                  {$newfc['kan_verteileinheit']}</span>
          </td>
        </tr>
        <tr>
          <td><label>Liefer-Einheit:</label></td>
          <td>
            <input type='text' size='4' name='newliefermult' id='newliefermult' value='${newfc['kan_liefermult']}'
             title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
             onchange='preisberechnung_default();'
             >
            <select size='1' name='newliefereinheit' id='newliefereinheit'
             onchange='preisberechnung_default();'
            >
    " . optionen_einheiten( $newfc['kan_liefereinheit'] ) . "
            </select>

               <label>Lieferpreis:</label>
                  <input title='Nettopreis' type='text' size='8' id='newfclieferpreis' name='newfclieferpreis'
                  value='${newfc['lieferpreis']}'
                  onchange='preisberechnung_rueckwaerts();'
                  >
                  / <span id='newfcpreiseinheit'>{$newfc['preiseinheit']}</span>
              </td>
            </tr>
            <tr>
              <td><label>ab:</label></td>
                <td><input type='text' size='18' name='newfczeitstart' value='$mysqljetzt'>


                <label>&nbsp;</label>
                <input type='submit' name='submit' value='OK'
                        onclick=\"document.getElementById('row$outerrow').className='modified';\";
                        title='Neuen Preiseintrag vornehmen (und letzten ggf. abschliessen)'
                >

                <label>&nbsp;</label>
                <label>Dynamische Neuberechnung:</label>
                <input name='dynamischberechnen' type='checkbox' value='yes'
                title='Dynamische Berechnung anderer Felder bei &Auml;nderung eines Eintrags' checked>

              </td>
            </tr>
          </table>
        </fieldset>
        <input type='hidden' name='action' value='neuer_preiseintrag'>
        </form>
      </div>
    ";

  }
  ?> </td></tr> <?

} // function do_artikel

?>

<script type="text/javascript">
  function preiseintrag_on() {
    document.getElementById("preiseintrag_an_knopf").style.display = "none";
    document.getElementById("preiseintrag_form").style.display = "block";
  }
  function preiseintrag_off() {
    document.getElementById("preiseintrag_an_knopf").style.display = "inline";
    document.getElementById("preiseintrag_form").style.display = "none";
  }

  var mwst, pfand, verteilmult, verteileinheit, preis, gebindegroesse,
    liefermult, liefereinheit, lieferpreis, preiseinheit, mengenfaktor;

  // vorwaerts: lieferpreis berechnen
  //
  var vorwaerts = 0;

  function preiseinheit_setzen() {
    if( liefereinheit != verteileinheit ) {
      mengenfaktor = gebindegroesse;
      preiseinheit = liefereinheit + ' (' + gebindegroesse * verteilmult + ' ' + verteileinheit + ')';
      if( liefermult != '1' )
        preiseinheit = liefermult + ' ' + preiseinheit;
    } else {
      switch( liefereinheit ) {
        case 'g':
          preiseinheit = 'kg';
          mengenfaktor = 1000 / verteilmult;
          break;
        case 'ml':
          preiseinheit = 'L';
          mengenfaktor = 1000 / verteilmult;
          break;
        default:
          preiseinheit = liefereinheit;
          mengenfaktor = 1.0 / verteilmult;
          break;
      }
    }
  }

  function preiseintrag_auslesen() {
    mwst = parseFloat( document.Preisform.newfcmwst.value );
    pfand = parseFloat( document.Preisform.newfcpfand.value );
    verteilmult = parseInt( document.Preisform.newfcmult.value );
    verteileinheit = document.Preisform.newfceinheit.value;
    preis = parseFloat( document.Preisform.newfcpreis.value );
    gebindegroesse = parseInt( document.Preisform.newfcgebindegroesse.value );
    liefermult = parseInt( document.Preisform.newliefermult.value );
    liefereinheit = document.Preisform.newliefereinheit.value;
    lieferpreis = parseFloat( document.Preisform.newfclieferpreis.value );
    preiseinheit_setzen();
  }

  preiseintrag_auslesen();

  function preiseintrag_update() {
    document.Preisform.newfcmwst.value = mwst;
    document.Preisform.newfcmwst.pfand = pfand;
    document.Preisform.newfcmult.value = verteilmult;
    document.Preisform.newfceinheit.value = verteileinheit;
    document.Preisform.newfcpreis.value = preis;
    document.Preisform.newfcgebindegroesse.value = gebindegroesse;
    document.Preisform.newliefermult.value = liefermult;
    document.Preisform.newliefereinheit.value = liefereinheit;
    document.Preisform.newfclieferpreis.value = lieferpreis;
    document.getElementById("newfcendpreiseinheit").firstChild.nodeValue = verteilmult + ' ' + verteileinheit;
    document.getElementById("newfcgebindeeinheit").firstChild.nodeValue = verteilmult + ' ' + verteileinheit;
    document.getElementById("newfcpreiseinheit").firstChild.nodeValue = preiseinheit;
  }

  function preisberechnung_vorwaerts() {
    vorwaerts = 1;
    preiseintrag_auslesen();
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      lieferpreis = 
        parseInt( 0.499 + 100 * ( preis - pfand ) / ( 1.0 + mwst / 100.0 ) * mengenfaktor ) / 100.0;
    }
    preiseintrag_update();
  }

  function preisberechnung_rueckwaerts() {
    vorwaerts = 0;
    preiseintrag_auslesen();
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      preis = 
        parseInt( 0.499 + 10000 * ( lieferpreis * ( 1.0 + mwst / 100.0 ) / mengenfaktor + pfand ) ) / 10000.0;
    }
    preiseintrag_update();
  }

  function preisberechnung_default() {
    if( vorwaerts )
      preisberechnung_vorwaerts();
    else
      preisberechnung_rueckwaerts();
  }
  function preisberechnung_fcmult() {
    alt = verteilmult;
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      verteilmult = parseInt( document.Preisform.newfcmult.value );
      if( verteilmult < 1 )
        verteilmult = 1;
      if( (verteilmult > 0) && (alt > 0) ) {
        gebindegroesse = parseInt( 0.499  + gebindegroesse * alt / verteilmult);
        if( gebindegroesse < 1 )
          gebindegroesse = 1;
        document.Preisform.newfcgebindegroesse.value = gebindegroesse;
      }
    }
    preisberechnung_default();
  }
  function preisberechnung_gebinde() {
    alt = gebindegroesse;
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      gebindegroesse = parseInt( document.Preisform.newfcgebindegroesse.value );
      if( gebindegroesse < 1 )
        gebindegroesse = 1;
      // if( (gebindegroesse > 0) && (alt > 0) ) {
      //  verteilmult = parseInt( 0.499 + verteilmult * alt / gebindegroesse );
      //  document.Preisform.newfcmult.value = verteilmult;
      // }
    }
    preisberechnung_default();
  }

</script>

