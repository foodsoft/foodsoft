<?php

// terraabgleich.php (name ist historisch: nuetzlich auch fuer andere lieferanten!)
//
// - sucht in produktliste und preishistorie nach inkonsistenzen,
// - vergleicht mit Katalog (momentan nur Terra)
// - macht ggf. verbesserungsvorschlaege und erlaubt neueintrag von preisen
//
// anzeige wird durch folgende variable bestimmt:
// - produkt_id: fuer detailanzeige ein produkt (sonst ganze liste eines lieferanten)
// - lieferanten_id: liste alle produkte des lieferanten (verpflichtend, wenn keine produkt_id)
// - bestell_id: erlaubt auswahl preiseintrag fuer diese bestellung (nur mit produkt_id)

assert( $angemeldet ) or exit();

$editable = ( ! $readonly and ( $dienst == 4 ) );

get_http_var('produkt_id','u',0,true);
$detail = $produkt_id;
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
$lieferanten_name = lieferant_name( $lieferanten_id );
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
    action_form_produktpreis();
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
  $result = sql_produkte_von_lieferant_ids( $lieferanten_id );
  $produkt_ids = mysql2array( $result, 'id', 'id' );
}

?>
  <table width="100%">
    <colgroup> <col width="7%"> <col> </colgroup>
    <tr> <th class="outer">A-Nr.</th>
         <th class="outer">Artikeldaten
         <?
           if( $detail )
             echo "$produkt_name von $lieferanten_name";
         ?>
         </th> </tr>
    <?
      foreach( $produkt_ids as $produkt_id ) {
        do_artikel( $produkt_id, $detail, $editable );
      }
    ?>
  </table>
<?


// do_artikel
// wird aus der hauptschleife aufgerufen, um einen artikel aus der Produktliste anzuzeigen
//
function do_artikel( $produkt_id, $detail, $editable ) {
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

  // felder fuer neuen preiseintrag initialisieren:
  //
  $preiseintrag_neu = array();
  $preiseintrag_neu['verteileinheit'] = FALSE;
  $preiseintrag_neu['liefereinheit'] = FALSE;
  $preiseintrag_neu['gebindegroesse'] = FALSE;
  $preiseintrag_neu['preis'] = FALSE;
  $preiseintrag_neu['bestellnummer'] = FALSE;
  $preiseintrag_neu['mwst'] = FALSE;
  $preiseintrag_neu['pfand'] = FALSE;
  $preiseintrag_neu['notiz'] = FALSE;

  ?> <th class='outer' style='vertical-align:top;'> <?
  if( $detail ) {
    echo "{$artikel['artikelnummer']}<br>id:&nbsp;$produkt_id";
  } else {
    echo "<a class='blocklink'
      href=\"javascript:neuesfenster('" . self_url() . "&produkt_id=$produkt_id','produktdetails')\"
      title='Details...'
      onclick=\"document.getElementById('row$outerrow').className='modified';\"
      >{$artikel['artikelnummer']}<br>id:&nbsp;$produkt_id</a>
    ";
  }
  ?> </th><td class="outer" style="padding-bottom:1ex;"> <?

  ////////////////////////
  // Preishistorie: im Detail-Modus anzeigen, sonst nur Test auf Konsistenz:
  //

  if( $detail )
    preishistorie_view( $produkt_id, $bestell_id, $editable );

  produktpreise_konsistenztest( $produkt_id, $editable, "row$outerrow" );

  $prgueltig = false;

  if( $artikel['zeitstart'] ) {
    $prgueltig = true;
    if( ! $artikel['kan_liefereinheit'] ) {
      ?> <div class='warn'>FEHLER: keine gültige Liefereinheit:
      .<? echo $artikel['kan_liefereinheit']; ?>.
      .<? echo "{$artikel['preis_id']}, {$artikel['zeitstart']}"; ?>.
      </div> <?
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
      <td class='number'><? echo "{$artikel['kan_verteilmult']} {$artikel['kan_verteileinheit']}"; ?></td>
      <td class='number'><? echo $artikel['gebindegroesse']; ?></td>
      <td class='number'><? echo $artikel['mwst']; ?></td>
      <td class='number'><? printf( "%.2lf", $artikel['pfand'] ); ?></td>
      <td class='number'><?
        printf( "%8.2lf / %s %s", $artikel['endpreis'], $artikel['kan_verteilmult'], $artikel['kan_verteileinheit'] ); ?>
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
      $katalog_bestellnummer = $katalogeintraege[0]["terrabestellnummer"][0];
      $katalog_name = $katalogeintraege[0]["cn"][0];
      $katalog_einheit = $katalogeintraege[0]["terraeinheit"][0];
      $katalog_gebindegroesse = $katalogeintraege[0]["terragebindegroesse"][0];
      $katalog_herkunft =  $katalogeintraege[0]["terraherkunft"][0];
      $katalog_verband = $katalogeintraege[0]["terraverband"][0];
      $katalog_netto = $katalogeintraege[0]["terranettopreisincents"][0] / 100.0;
      $katalog_mwst = $katalogeintraege[0]["terramwst"][0];
      $katalog_brutto = $katalog_netto * (1 + $katalog_mwst / 100.0 );
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
            <td><? echo $katalog_bestellnummer; ?></td>
            <td><? echo $katalog_name; ?></td>
            <td><? echo $katalog_einheit; ?></td>
            <td><? echo $katalog_gebindegroesse; ?></td>
            <td><? echo $katalog_herkunft; ?></td>
            <td><? echo $katalog_verband; ?></td>
            <td><? echo $katalog_netto; ?></td>
            <td><? echo $katalog_mwst; ?></td>
            <td><? echo $katalog_brutto; ?></td>
          </tr>
        </table>
      <?

      kanonische_einheit( $katalog_einheit, &$kan_katalogeinheit, &$kan_katalogmult );

      ////////////////////////////////
      // aktuellsten preiseintrag mit Katalogeintrag vergleichen,
      // Vorlage fuer neuen preiseintrag mit Katalogdaten vorbesetzen:
      //
      if( $prgueltig ) {
        // echo "<br>Foodsoft: Einheit: $kan_fcmult * $kan_fceinheit Gebinde: $fcgebindegroesse";
        // echo "<br>Terra: Einheit: $kan_terramult * $kan_terraeinheit Gebinde: $terragebindegroesse";

        // liefereinheit und mwst sollten mit katalog uebereinstimmen:
        //
        $preiseintrag_neu['liefereinheit'] = $katalog_gebindegroesse * $kan_katalogmult . " $kan_katalogeinheit";
        $preiseintrag_neu['mwst'] = $katalog_mwst;
        if( $preiseintrag_neu['liefereinheit'] != "{$artikel['kan_liefermult']} {$artikel['kan_liefereinheit']}" ) {
          $neednewprice = TRUE;
          echo "<div class='warn'>Problem: L-Einheit stimmt nicht:
                 <p class='li'>Katalog: <kbd>" . $katalog_gebindegroesse * $kan_katalogmult . " $kan_katalogeinheit</kbd></p>
                 <p class='li'>Foodsoft: <kbd>{$artikel['kan_liefermult']} {$artikel['kan_liefereinheit']}</kbd></p></div>";
        }
        if( abs( $artikel['mwst'] - $katalog_mwst ) > 0.005 ) {
          $neednewprice = TRUE;
          echo "<div class='warn'>Problem: MWSt-Satz stimmt nicht:
                    <p class='li'>Katalog: <kbd>$katalog_mwst</kbd></p>
                    <p class='li'>Foodsoft: <kbd>{$artikel['mwst']}</kbd></p></div>";
        }

        // verteileinheit: kann von liefereinheit abweichen:
        if( $kan_katalogeinheit == 'KI' and $artikel['kan_verteileinheit'] == 'ST' ) {
          // spezialfall: KIste mit vielen STueck inhalt ist ok!
          // hier muss die STueckzahl pro KIste als gebindegroesse manuell erfasst werden:
          //
          $preiseintrag_neu['verteileinheit'] = "{$artikel['kan_verteilmult']} ST";
          $preiseintrag_neu['gebindegroesse']
            = ( ( $artikel['gebindegroesse'] > 0.001 ) ? $artikel['gebindegroesse'] : 1 );
          $preiseintrag_neu['preis']
            = $katalog_brutto * $katalog_gebindegroesse / $preiseintrag_neu['gebindegroesse'] + $artikel['pfand'];
        } else {

          // andernfalls: verteileinheit solllte gleich liefereinheit sein, bis auf einen vorfaktor 

          if( $kan_katalogeinheit != $artikel['kan_verteileinheit'] ) {
            // wir schlagen neue verteileinheit vor, und berechnen den vorfaktor aus den gebindegroessen:
            $neednewprice = TRUE;
            $preiseintrag_neu['gebindegroesse']
              = ( ( $artikel['gebindegroesse'] > 0.001 ) ? $artikel['gebindegroesse'] : 1 );
            $preiseintrag_neu['verteileinheit']
              = $katalog_gebindegroesse * $kan_katalogmult / $artikel['gebindegroesse'] . " $kan_katalogeinheit";
            $preiseintrag_neu['preis'] = $katalog_brutto * $katalog_gebindegroesse / $preiseintrag_neu['gebindegroesse']
                                         + $artikel['pfand'];
            echo "<div class='warn'>Problem: Einheit inkompatibel:
                    <p class='li'>Katalog: <kbd>$kan_katalogeinheit</kbd></p>
                    <p class='li'>Verteilung: <kbd>{$artikel['kan_verteileinheit']}</kbd></p></div>";
          } else {
            // einheiten ok; wir setzen aber trotzdem die Vorlage auf die kanonischen werte,
            // und berechnen die gebindegroesse (in V-einheiten):

            $preiseintrag_neu['verteileinheit']
              = "{$artikel['kan_verteilmult']} {$artikel['kan_verteileinheit']}";
            $preiseintrag_neu['gebindegroesse']
              = $katalog_gebindegroesse * $kan_katalogmult / $artikel['kan_verteilmult'];
            $preiseintrag_neu['preis']
              = $katalog_brutto / $kan_katalogmult * $artikel['kan_verteilmult'] + $artikel['pfand'];

            if( abs( $preiseintrag_neu['gebindegroesse'] - $artikel['gebindegroesse'] ) > 0.001 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Gebindegroessen stimmen nicht: 
                        <p class='li'>Katalog: <kbd>$katalog_gebindegroesse * $kan_katalogmult $kan_katalogeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>{$artikel['gebindegroesse']}
                            * {$artikel['kan_verteilmult']} {$artikel['kan_verteileinheit']}</kbd></p></div>";
            }
            if( abs( $artikel['bruttopreis'] * $kan_katalogmult / $artikel['kan_verteilmult']
                      - $katalog_brutto ) > 0.005 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Preise stimmen nicht (beide Brutto ohne Pfand):
                        <p class='li'>Katalog: <kbd>$katalog_brutto / $kan_katalogmult $kan_katalogeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>"
                          . ( $artikel['endpreis'] - $artikel['pfand'] ) * $kan_katalogmult / $artikel['kan_verteilmult']
                          . " / $kan_katalogmult $kan_katalogeinheit</kbd></p></div>";
            }
          }
        }

        $preiseintrag_neu['bestellnummer'] = $katalog_bestellnummer;
        if( $katalog_bestellnummer != $artikel['bestellnummer'] ) {
          $neednewprice = TRUE;
          echo "<div class='warn'>Problem: Bestellnummern stimmen nicht:
                    <p class='li'>Katalog: <kbd>$katalog_bestellnummer</kbd></p>
                    <p class='li'>Foodsoft: <kbd>{$artikel['bestellnummer']}</kbd></p></div>";
        }


        // echo "<br>Verteil: new: Einheit: $newfcmult * $newfceinheit Gebinde: $newfcgebindegroesse";
        // echo "<br>Liefer: new: Einheit: $newliefermult * $newliefereinheit";
      } else {
        // kein aktuell gueltiger Preiseintrag: wir erzeugen Vorlage aus Katalogdaten:
        $neednewprice = TRUE;
        $preiseintrag_neu['liefereinheit']
          = $kan_katalogmult * $katalog_gebindegroesse . " $kan_katalogeinheit";
        $preiseintrag_neu['verteileinheit'] = $kan_katalogmult . " $kan_katalogeinheit";
        $preiseintrag_neu['gebindegroesse'] = $katalog_gebindegroesse;
        $preiseintrag_neu['mwst'] = $katalog_mwst;
        $preiseintrag_neu['bestellnummer'] = $katalog_bestellnummer;
        $preiseintrag_neu['preis'] = $katalog_brutto;
      }

      if( $detail ) {
        ?> <div style='padding:1ex;'> <?
        formular_artikelnummer( $produkt_id, true, false );
        ?> </div> <?
      }
    }

  } //  ... katalogsuche ...


  if( $detail ) {

    /////////////////////////
    // vorlage fuer neuen preiseintrag berechnen (soweit noch nicht aus Katalog gesetzt):
    //

    if( ! $preiseintrag_neu['gebindegroesse'] ) {
      if( $prgueltig and $artikel['gebindegroesse'] > 1 )
        $preiseintrag_neu['gebindegroesse'] = $artikel['gebindegroesse'];
      else
        $preiseintrag_neu['gebindegroesse'] = 1;
    }

    if( ! $preiseintrag_neu['verteileinheit'] ) {
      if( $prgueltig )
        $preiseintrag_neu['verteileinheit'] =
          ( ( $artikel['kan_verteilmult'] > 0.0001 ) ? $artikel['kan_verteilmult'] : 1 )
          . ( $artikel['kan_verteileinheit'] ? " {$artikel['kan_verteileinheit']} " : ' ST' );
      else
        $preiseintrag_neu['verteileinheit'] = '1 ST';
    }

    if( ! $preiseintrag_neu['liefereinheit'] ) {
      if( $prgueltig and $artikel['kan_liefereinheit'] and ( $artikel['kan_liefermult'] > 0.0001 ) )
        $preiseintrag_neu['liefereinheit'] = "{$artikel['kan_liefermult']} {$artikel['kan_liefereinheit']}";
      else
        $preiseintrag_neu['liefereinheit'] = $preiseintrag_neu['verteileinheit'];
    }

    if( ! $preiseintrag_neu['mwst'] ) {
      if( $prgueltig and $artikel['mwst'] )
        $preiseintrag_neu['mwst'] = $artikel['mwst'];
      else
        $preiseintrag_neu['mwst'] = '7.00';
    }

    if( ! $preiseintrag_neu['pfand'] ) {
      if( $prgueltig and $artikel['pfand'] )
        $preiseintrag_neu['pfand'] = $artikel['pfand'];
      else
        $preiseintrag_neu['pfand'] = '0.00';
    }

    if( ! $preiseintrag_neu['preis'] ) {
      if( $prgueltig and $artikel['endpreis'] )
        $preiseintrag_neu['preis'] = $artikel['endpreis'];
      else
        $preiseintrag_neu['preis'] = '0.00';
    }

    if( ! $preiseintrag_neu['bestellnummer'] ) {
      if( $prgueltig and $artikel['bestellnummer'] )
        $preiseintrag_neu['bestellnummer'] = $artikel['bestellnummer'];
      else
        $preiseintrag_neu['bestellnummer'] = '';
    }

    if( ! $preiseintrag_neu['notiz'] ) {
      if( $prgueltig and $artikel['notiz'] )
        $preiseintrag_neu['notiz'] = $artikel['notiz'];
      else
        $preiseintrag_neu['notiz'] = '';
    }

    // echo "newverteileinheit: {$preiseintrag_neu['verteileinheit']}";
    // echo "newliefereinheit: {$preiseintrag_neu['liefereinheit']}";

    // restliche felder automatisch berechnen:
    //
    preisdatenSetzen( & $preiseintrag_neu );

    if( $neednewprice ) {
      ?>
        <div style='padding:1ex;' id='preiseintrag_form' class='small_form'>
          <form name='Preisform' method='post' action='<? echo self_url(); ?>'>
          <? echo self_post(); ?>
          <fieldset>
            <legend>Vorschlag neuer Preiseintrag:</legend>
      <?
    } else {
      ?>
        <div class='untertabelle'>
          <div id='preiseintrag_an_knopf'>
            <span class='button'
              onclick='preiseintrag_on();' >Neuer Preiseintrag...</span>
          </div>
        </div>
        <div style='display:none;' id='preiseintrag_form' class='small_form'>
          <form name='Preisform' method='post' action='<? echo self_url(); ?>'>
          <? echo self_post(); ?>
          <fieldset>
            <legend>
              <img class='button' title='Ausblenden' src='img/close_black_trans.gif'
               onclick='preiseintrag_off();'></img> Neuer Preiseintrag:</legend>
      <?
    }

    ?>
      <input type='hidden' name='action' value='neuer_preiseintrag'>
      <table id='preisform'>
        <tr>
          <td style='padding:1ex 0ex 1ex 0ex;'><label>Produkt:</label></td>
          <td><kbd> <?  echo "{$artikel['name']} von {$artikel['lieferanten_name']}"; ?> </kbd></td>
        </tr>
        <tr>
          <td><label>Notiz:</label>
          <td><input type='text' size='42' name='notiz' value='<? echo $preiseintrag_neu['notiz']; ?>'
               title='Notiz: zum Beispiel aktuelle Herkunft, Verband oder Lieferant'>
          </td>
        </tr>
        <tr>
          <td><label>Bestell-Nr:</label></td>
          <td>
            <input type='text' size='8' name='bestellnummer'
             value='<? echo $preiseintrag_neu['bestellnummer']; ?>'
             title='Bestellnummer (die, die sich bei Terra st&auml;ndig &auml;ndert!)'>

            <label>MWSt:</label>
            <input type='text' size='4' name='mwst' id='newfcmwst'
             value='<? echo $preiseintrag_neu['mwst']; ?>'
             title='MWSt-Satz in Prozent'
             onchange='preisberechnung_rueckwaerts();'>

            <label>Pfand:</label>
            <input type='text' size='4' name='pfand' id='newfcpfand'
             value='<? echo $preiseintrag_neu['pfand']; ?>'
             title='Pfand pro V-Einheit, bei uns immer 0.00 oder 0.16'
             onchange='preisberechnung_rueckwaerts();'>
          </td>
        </tr>
          <td><label>Verteil-Einheit:</label></td>
          <td>
            <input type='text' size='4' name='verteilmult' id='newfcmult'
             value='<? echo $preiseintrag_neu['kan_verteilmult']; ?>'
             title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
             onchange='preisberechnung_fcmult();'>
            <select size='1' name='verteileinheit' id='newfceinheit'
              onchange='preisberechnung_default();'>
              <? echo optionen_einheiten( $preiseintrag_neu['kan_verteileinheit'] ); ?>
            </select>
            <label>Endpreis:</label>
            <input title='Preis incl. MWSt und Pfand' type='text' size='8' id='newfcpreis' name='preis'
             value='<? echo $preiseintrag_neu['preis']; ?>'
             onchange='preisberechnung_vorwaerts();'>
            / <span id='newfcendpreiseinheit'>
                <? echo $preiseintrag_neu['kan_verteilmult']; ?>
                <? echo $preiseintrag_neu['kan_verteileinheit']; ?>
               </span>

            <label>Gebinde:</label>
            <input type='text' size='4' name='gebindegroesse' id='newfcgebindegroesse'
             value='<? echo $preiseintrag_neu['gebindegroesse']; ?>'
             title='Gebindegroesse in ganzen Vielfachen der V-Einheit'
             onchange='preisberechnung_gebinde();'>
            * <span id='newfcgebindeeinheit']>
                <? echo $preiseintrag_neu['kan_verteilmult']; ?>
                <? echo $preiseintrag_neu['kan_verteileinheit']; ?>
              </span>
          </td>
        </tr>
        <tr>
          <td><label>Liefer-Einheit:</label></td>
          <td>
            <input type='text' size='4' name='liefermult' id='newliefermult'
             value='<? echo $preiseintrag_neu['kan_liefermult']; ?>'
             title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
             onchange='preisberechnung_default();'>
            <select size='1' name='liefereinheit' id='newliefereinheit'
              onchange='preisberechnung_default();'>
              <? echo optionen_einheiten( $preiseintrag_neu['kan_liefereinheit'] ); ?>
            </select>

            <label>Lieferpreis:</label>
              <input title='Nettopreis' type='text' size='8' id='newfclieferpreis' name='lieferpreis'
               value='<? echo $preiseintrag_neu['lieferpreis']; ?>'
               onchange='preisberechnung_rueckwaerts();'>
              / <span id='newfcpreiseinheit'><? echo $preiseintrag_neu['preiseinheit']; ?></span>
          </td>
        </tr>
        <tr>
          <td><label>ab:</label></td>
          <td>
            <? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?>
            <label>&nbsp;</label>
            <input type='submit' name='submit' value='OK'
             onclick=\"document.getElementById('row$outerrow').className='modified';\";
             title='Neuen Preiseintrag vornehmen (und letzten ggf. abschliessen)'>
  
            <label>&nbsp;</label>
            <label>Dynamische Neuberechnung:</label>
            <input name='dynamischberechnen' type='checkbox' value='yes'
             title='Dynamische Berechnung anderer Felder bei Änderung eines Eintrags' checked>
  
          </td>
        </tr>
      </table>
      </fieldset></form></div>
    <?

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

