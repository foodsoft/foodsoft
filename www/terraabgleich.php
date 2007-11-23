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

    $gesamtbestellung = sql_bestellung( $bestell_id );
    $bestellung_name = $gesamtbestellung['name'];
  }
} else {
  $result = sql_produkte_von_lieferant_ids( $lieferanten_id ) {
  $produkt_ids = mysql2array( $result, 'id', 'id' );
}

?>
  <table width="100%">
    <colgroup> <col width="7%"> <col> </colgroup>
    <tr>
      <th class="outer">A-Nr.</th> <th class="outer">Artikeldaten</th>
    </tr>
    <?
      $outerrow = 0;
      foreach( $produkt_ids as $produkt_id ) {
        $outerrow++;
        do_artikel( $produkt_id, $detail );
      }
    ?>
  </table>
<?


// preishistorie:
//  - kann preishistorie anzeigen
//  - kann preisauswahl fuer eine bestellung erlauben
//
function preishistorie_view( $produkt_id, $bestell_id = 0, $editable = false ) {
  need( $produkt_id );
  if( $bestell_id ) {
    $bestellvorschlag = sql_bestellvorschlag_daten( $bestell_id, $produkt_id );
    $preisid_in_bestellvorschlag = $bestellvorschlag['preis_id'];
  }

  ?>
    <script type="text/javascript">
      preishistorie_status = 1;
      function preishistorie_toggle() {
        preishistorie_status = ! preishistorie_status;
        if( preishistorie_status ) {
          document.getElementById("preishistorie").style.display = "block";
          document.getElementById("preishistorie_knopf").src = "img/close_black_trans.gif";
          document.getElementById("preishistorie_knopf").title = "Ausblenden";
        } else {
          document.getElementById("preishistorie").style.display = "none";
          document.getElementById("preishistorie_knopf").src = "img/open_black_trans.gif";
          document.getElementById("preishistorie_knopf").title = "Einblenden";
        }
      }
    </script>
    <div class='untertabelle'>
      <img id='preishistorie_knopf' class='button' src='img/close_black_trans.gif'
        onclick='preishistorie_toggle();' title='Ausblenden'>
  <?
  if( $bestell_id ) {
    ?> Preiseintrag wählen für Bestellung <?
    echo "$bestellung_name:";
  } else {
    ?> Preis-Historie: <?
  }
  ?>
    </div>
    <div id='preishistorie'>
      <table width='100%' class='numbers'>
        <tr>
          <th title='Interne eindeutige ID-Nummer des Preiseintrags'>id</th>
          <th title='Bestellnummer'>B-Nr</th>
          <th title='Preiseintrag gültig ab'>von</th>
          <th title='Preiseintrag gültig bis'>bis</th>
          <th title='Liefer-Einheit: fürs Bestellen beim Lieferanten' colspan='2'>L-Einheit</th>
          <th title='Nettopreis beim Lieferanten' colspan='2'>L-Preis</th>
          <th title='Verteil-Einheit: f&uuml;rs Bestellen und Verteilen bei uns' colspan='2'>V-Einheit</th>
          <th title='Gebindegröße in V-Einheiten'>Gebinde</th>
          <th>MWSt</th>
          <th title='Pfand je V-Einheit'>Pfand</th>
          <th title='Endpreis je V-Einheit' colspan='2'>V-Preis</th>
  <?
  if( $bestell_id )
    echo "<th title='Preiseintrag für Bestellung $bestellung_name'>Aktiv</th>";
  ?> </tr> <?

  $produktpreise = sql_produktpreise2( $produkt_id );
  while( $pr1 = mysql_fetch_array($produktpreise) ) {
    preisdatenSetzen( &$pr1 );
    ?>
      <tr>
        <td><? echo $pr1['id']; ?></td>
        <td><? echo $pr1['bestellnummer']; ?></td>
        <td><? echo $pr1['zeitstart']; ?></td>
        <td>
    <?
    if( $pr1['zeitende'] ) {
      echo "{$pr1['zeitende']}";
    } else {
      if( $editable )
        action_button( "Abschließzen"
        , "Preisintervall abschließen (z.B. falls Artikel nicht lieferbar)"
        , array( 'action' => 'zeitende_setzen', 'preis_id' => $pr1['id'], 'zeitende' => $mysqljetzt, 'preis_id' => $pr1['id'] )
        , "row$outerrow"
        );
      else
        echo " - ";
    }
    ?>
        </td>
        <td class='mult'><? echo $pr1['kan_liefermult']; ?></td>
        <td class='unit'><? echo $pr1['kan_liefereinheit']; ?></td>
        <td class='mult'><? printf( "%8.2lf", $pr1['lieferpreis'] ); ?></td>
        <td class='unit'>/ <? echo $pr1['preiseinheit']; ?></td>
        <td class='mult'><? echo $pr1['kan_verteilmult']; ?></td>
        <td class='unit'><? echo $pr1['kan_verteileinheit']; ?></td>
        <td class='number'><? echo $pr1['gebindegroesse']; ?></td>
        <td class='number'><? echo $pr1['mwst']; ?></td>
        <td class='number'><? echo $pr1['pfand']; ?></td>
        <td class='mult'><? printf( "%8.2lf", $pr1['preis'] ); ?></td>
        <td class='unit'><? / echo "{$pr1['kan_verteilmult']} {$pr1['kan_verteileinheit']}"; ?></td>
    <?
    if( $bestell_id ) {
      ?> <td> <?
      if( $pr1['id'] == $preisid_in_bestellvorschlag ) {
        ?>
          <input type='submit' name='aktiv' value='aktiv' class='buttondown'
          style='width:5em;'
          title='gilt momentan f&uuml;r Abrechnung der Bestellung <? echo $bestellung_name; ?>'>
        <?
      } else {
        if( $editable ) {
          action_button( "setzen", "
          , "diesen Preiseintrag für Bestellung $bestellung_name auswählen"
          , array( 'action' => 'preiseintrag_waehlen', 'preis_id' => $pr1['id'] )
          );
        } else {
          echo " - ";
        }
      }
      ?> </td> <?
    }
    ?> </tr> <?
  }
  ?> </table></div> <?
}

// produktpreise: test auf konsistenz:
//  - alle intervalle bis auf das letzte muessen abgeschlossen sein
//  - intervalle duerfen nicht ueberlappen
//  - warnen, wenn kein aktuell gueltiger preis vorhanden
//
function produktpreise_konsistenztest( $produkt_id, $editable, $mod_id = false ) {
  need( $produkt_id );
  $produktpreise = sql_produktpreise2( $produkt_id );
  $pr0 = FALSE;
  $prgueltig = FALSE; // aktuell gueltiger preiseintrag, oder FALSE
  while( $pr1 = mysql_fetch_array($produktpreise) ) {
    if( $pr0 ) {
      if( $pr0['zeitende'] == '' ) {
        echo "<div class='warn'>FEHLER: Preisintervall {$pr0['id']} nicht aktuell aber nicht abgeschlossen.</div>";
        $editable && action_button(
          "Zeitende in {$pr0['id']} auf {$pr1['zeitstart']} setzen"
          , array( 'action' => 'zeitende_setzen', 'zeitende' => $pr1['zeitstart'], 'preis_id' => $pr0['id'] )
        , $mod_id
        );
      } else if( $pr0['zeitende'] > $pr1['zeitstart'] ) {
        echo "<div class='warn'>FEHLER: Ueberlapp in Preishistorie: {$pr0['id']} und {$pr1['id']}.</div>";
        $editable && action_button(
          "Zeitende in {$pr0['id']} auf {$pr1['zeitstart']} setzen"
          , array( 'action' => 'zeitende_setzen', 'zeitende' => $pr1['zeitstart'], 'preis_id' => $pr0['id'] )
        , $mod_id
        );
      }
    }
    $pr0 = $pr1;
  }
  if( ! $pr0 ) {
    ?> <div class='warn'>WARNUNG: kein Preiseintrag fuer diesen Artikel vorhanden!</div> <?
  } else if ( $pr0['zeitende'] != '' ) {
    if ( $pr0['zeitende'] < $mysqljetzt ) {
      ?> <div class='warn'>WARNUNG: kein aktuell g&uuml;ltiger Preiseintrag fuer diesen Artikel vorhanden!</div> <?
    } else {
      ?> <div class='warn'>WARNUNG: aktueller Preis l&auml;uft aus!</div> <?
      $prgueltig = $pr0;  // kann man noch zulassen...
    }
  } else {
    $prgueltig = $pr0;
  }
  return $prgueltig;
}


// do_artikel
// wird aus der hauptschleife aufgerufen, um einen artikel aus der Produktliste anzuzeigen
//
function do_artikel( $produkt_id, $detail ) {
  static $outerrow;
  global $mysqljetzt, $is_terra
       , $bestell_id, $bestellung_name, $preisid_in_bestellvorschlag;

  isset( $outerrow ) or $outerrow = 0;
  ++$outerrow;
  echo "\n<tr id='row$outerrow'>";

  $artikel = sql_produkt_details( $produkt_id );

  $anummer = $artikel['artikelnummer'];
  $name = $artikel['name'];
  $notiz = $artikel['notiz'];

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
    echo "
      <a class='blocklink'
      href=\"javascript:neuesfenster('" . self_url()
        . "&produkt_id=$produkt_id','produktdetails')\"
      title='Details...'
      onclick=\"document.getElementById('row$outerrow').className='modified';\"
      >$anummer<br>id:&nbsp;$produkt_id</a>
    ";
  }
  ?> </th><td class="outer" style="padding-bottom:1ex;"> <?

  ////////////////////////
  // Preishistorie:
  //

  if( $detail ) {
    preishistorie_view( $produkt_id, $bestell_id, $editable );

  $preishistorie_konsistenztest( $produkt_id, $editable, "row$outerrow" );

  if( $artikel['zeitstart'] ) {
    $prgueltig = true;
    if( ! $artikel['kan_liefereinheit'] ) {
      ?> <div class='warn'>FEHLER: keine gueltige Liefereinheit</div> <?
      $neednewprice = TRUE;
    }
  } else {
    $prgueltig = false;
  }

//   if( $prgueltig ) {
//     $fc_produkt = sql_produkt_details( $produkt_id, $prgueltig['id'] );
//     preisdatenSetzen( &$prgueltig );
//     $fc_bnummer = $prgueltig['bestellnummer'];
//     $fc_gebindegroesse = $prgueltig['gebindegroesse'];
//     $fc_preis = $prgueltig['preis'];
//     $fc_pfand = $prgueltig['pfand'];
//     $fc_mwst = $prgueltig['mwst'];
//     $fc_lieferpreis = $prgueltig['lieferpreis'];
//     $fc_preiseinheit = $prgueltig['preiseinheit'];
//     $fc_mengenfaktor = $prgueltig['mengenfaktor'];
//     $fc_kan_fcmult = $prgueltig['kan_verteilmult'];
//     $kan_fceinheit = $prgueltig['kan_verteileinheit'];
//     $kan_liefermult = $prgueltig['kan_liefermult'];
//     $kan_liefereinheit = $prgueltig['kan_liefereinheit'];
//     if( ! $kan_liefereinheit ) {
//       ?> <div class='warn'>FEHLER: keine gueltige Liefereinheit</div> <?
//       $neednewprice = TRUE;
//     }
//   } else {
//     $fc_produkt = sql_produkt_details( $produkt_id );
//     $fc_produkt = false;
//     $fcbnummer = NULL;
//     $fcgebindegroesse = NULL;
//     $fcpreis = NULL;
//     $fcpfand = NULL;
//     $fcmwst = NULL;
//     $fclieferpreis = NULL;
//     $fcpreiseinheit = NULL;
//     $fcmengenfaktor = NULL;
//     $kan_fcmult = NULL;
//     $kan_fceinheit = NULL;
//     $kan_liefermult = NULL;
//     $kan_liefereinheit = NULL;
//   }


  //
  // Artikeldaten aus foodsoft-Datenbank anzeigen:
  //

  ?>
    <div class='untertabelle' id='foodsoftdatenbank'>Foodsoft-Datenbank:</div>
    <table width='100%' class='numbers'>
      <tr>
        <th>B-Nr.</th>
        <th>Name</th>
        <th title='Liefer-Einheit: fuers Bestellen beim Lieferanten'>L-Einheit</th>
        <th title='Nettopreis beim Lieferanten'>L-Preis</th>
        <th title='Verteil-Einheit: fuers Bestellen und Verteilen bei uns'>V-Einheit</th>
        <th title='V-Einheiten pro Gebinde'>Gebinde</th>
        <th title='MWSt in Prozent'>MWSt</th>
        <th title='Pfand je V-Einheit'>Pfand</th>
        <th title='Endpreis je V-Einheit'>V-Preis</th>
      </tr>
      <tr>
  <?

  if( $prgueltig ) {
    echo "<td>$artikel['bestellnummer']</td>";
  } else {
    ?> <td><div class="warn" style="text-align:center;">keine</div></td> <?
  }

  echo "<td>{$artikel['name']}</td>";
  if( $prgueltig ) {
    ?>
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
    ?> <td colspan='7'><div class='warn' style='text-align:center;'> - - - </div></td> <?
  }
  ?> </tr></table> <?

  // Artikeldaten aus Katalog suchen und ggf. anzeigen:
  //
  if( ( $katalogeintraege = katalogsuche( $artikel ) ) ) {
    $katalogtreffer = $katalogeintraege['count'];

    $brutto = NULL;
    $mwst = NULL;
    $terragebindegroesse = NULL;
    $terrabnummer = NULL;
    $kan_terraeinheit = NULL;
    $kan_terramult = NULL;

    if( ! $katalogtreffer ) {

      ?> <div class='warn'>Katalogsuche: Artikelnummer nicht gefunden!</div> <?
      if( $detail )
        formular_artikelnummer( $produkt_id, false, true );

    } else {

      ?>
        <div class='untertabelle'>
          Artikelnummer gefunden in Katalog <? echo $katalogeintraege[0]['terradatum'][0]; ?>
      <?

      if( $detail ) {
        formular_artikelnummer( $produkt_id, true, false );

      ?>
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
      <?

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

      kanonische_einheit( $katalog_einheit, &$kan_katalogeinheit, &$kan_katalogmult );

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

  } // if( $is_terra ) { ... katalogvergleich ... }

  if( $detail ) {

    //
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

