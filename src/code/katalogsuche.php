<?php

// katalogsuche: sucht im lieferantenkatalog nach $produkt (soweit fuer den Lieferanten implementiert)
// 
// !!! dieses Skript ist nur fuer den _internen_ katalogabgleich aufgrund der Artikelnummer zustaendig!
// !!! fuer die manuelle Suche ist windows/artikelsuche.php da!
//
// $produkt ist entweder eine produkt_id, oder das Ergebnis von sql_produkt().
// moegliche rueckgabewerte:
//   1: kein Katalog vom Lieferanten vorhanden (ist kein Fehler)
//   2: keine Artikelnummer - Suche nicht moeglich
//   0 / NULL: Suche ohne (eindeutigen) Treffer
//   array(): enthaelt gefundenen Katalogeintrag
//
function katalogsuche( $produkt ) {
  if( is_numeric( $produkt ) ) {
    $produkt = sql_produkt( $produkt );
  }

  $where = "WHERE ( lieferanten_id = {$produkt['lieferanten_id']} ) ";

  if( ! sql_lieferant_katalogeintraege( $produkt['lieferanten_id'] ) ) {
    return 1;
  }
  if( ( $artikelnummer = adefault( $produkt, 'artikelnummer', 0 ) ) )
    $where .= " AND ( artikelnummer = '$artikelnummer' ) ";
  // elseif( ( $bestellnummer = adefault( $produkt, 'bestellnummer', 0 ) ) )
  //  $where .= " AND bestellnummer='$bestellnummer' ";
  else
    return 2;

  return sql_select_single_row( "SELECT * FROM lieferantenkatalog $where " , true );
}


// katalogabgleich
//
// rueckgabe:
//  0: ok
//  1: Katalogeintrag weicht ab (oder kein Preiseintrag in der Foodsoft-Datenbank)
//  2: Katalogsuche fehlgeschlagen
//  3: kein Katalog dieses Lieferanten erfasst
//  4: Abweichung nur bei Bestellnummer (Terra.....)
//
function katalogabgleich(
  $produkt_id
, $display_level = 0  // 0: garnix, 1: abweichungen, 2: voller katalogeintrag
, $editable = false
, & $preiseintrag_neu = array() // aus Katalogeintrag Vorschlag fuer Preiseintrag generieren
) {
  global $mwst_default;

  $preis_id = sql_aktueller_produktpreis_id( $produkt_id );
  $artikel = sql_produkt( array( 'produkt_id' => $produkt_id, 'preis_id' => $preis_id ) );
  $neednewprice = false;
  $neednewbestellnummer = false;

  $katalogeintrag = katalogsuche( $artikel );
  if( $katalogeintrag == 1 ) {
    if( $display_level >= 1 ) {
      div_msg( 'alert', 'Katalogsuche: kein Katalog dieses Lieferanten erfasst!' );
    }
    return 3;
  } else if( $katalogeintrag == 2 ) {
    if( $display_level >= 1 ) {
      div_msg( 'warn', 'Katalogsuche: Artikelnummer des Produktes fehlt --- Suche nicht moeglich!' );
    }
    return 2;
  } else if( ! $katalogeintrag ) {
    if( $display_level >= 1 ) {
      div_msg( 'warn', 'Katalogsuche fehlgeschlagen oder ohne Treffer' );
    }
    return 2;
  }

  $katalog_datum = $katalogeintrag["katalogdatum"];
  $katalog_typ = $katalogeintrag["katalogtyp"];
  $katalog_artikelnummer = $katalogeintrag["artikelnummer"];
  $katalog_bestellnummer = $katalogeintrag["bestellnummer"];
  $katalog_name = $katalogeintrag["name"];
  $katalog_bemerkung = $katalogeintrag["bemerkung"];
  $katalog_einheit = str_replace( ',', '.' , $katalogeintrag["liefereinheit"] );
  $katalog_gebindegroesse = str_replace( ',', '.' , $katalogeintrag["gebinde"] );
  $katalog_herkunft =  $katalogeintrag["herkunft"];
  $katalog_verband = $katalogeintrag["verband"];
  $katalog_hersteller = $katalogeintrag["hersteller"];
  $katalog_ean = $katalogeintrag["ean_einzeln"];
  $katalog_netto = $katalogeintrag["preis"];
  $katalog_id = $katalogeintrag["id"];

  $katalog_katalogname = sql_katalogname( $katalog_id );

  $kgueltig = $katalogeintrag['gueltig'];

  if( ! ( list( $kan_liefermult, $kan_liefereinheit ) = kanonische_einheit( $katalog_einheit, false ) ) ) {
    div_msg( 'warn', "Katalogsuche: unbekannte Einheit: $katalog_einheit" );
    return 2;
  }

  $have_mwst = false; // ist die mwst im katalog gelisted? (terra ja, andere nicht!)
  switch( $katalogeintrag['katalogformat'] ) {
    case 'terra_xls':
      $have_mwst = true;

      // setze:
      //   - $liefereinheit: auf diese bezieht sich der preis $katalog_netto,
      //     ebenso der wie der 'lieferpreis' in tabelle 'produktpreise'
      //   - $liefergebinde in vielfachen der $liefereinheit
      //     (achtung: 'gebindegroesse' in tabelle 'produktpreise' ist in v-einheiten!)
      switch( $kan_liefereinheit ) {
        case 'g':
          $liefereinheit = '1000 g';
          $liefergebinde = $katalog_gebindegroesse * $kan_liefermult / 1000.0;
          $kan_liefermult = 1000.0;
          break;
        case 'ml':
          $liefereinheit = '1000 ml';
          $liefergebinde = $katalog_gebindegroesse * $kan_liefermult / 1000.0;
          $kan_liefermult = 1000.0;
          break;
        default:
          $liefereinheit = "1 $kan_liefereinheit";
          $liefergebinde = $katalog_gebindegroesse * $kan_liefermult;
          $kan_liefermult = 1;
          break;
      }

      // default fuer v-einheit (nur relevant, falls gar keine oder inkompatible gesetzt)
      switch( $kan_liefereinheit ) {
        case 'KI':
        case 'PA':
        case 'GB':
          $verteileinheit_default = '1 ST';
          $lv_faktor_default = 1; // TODO: aus katalogtext parsen?
          break;
        case 'g':
          $verteileinheit_default = '500 g';
          $lv_faktor_default = 2;
          break;
        case 'ml':
          $verteileinheit_default = '1000 ml';
          $lv_faktor_default = 1;
          break;
        default:
          $verteileinheit_default = $liefereinheit;
          $lv_faktor_default = 1;
          break;
      }

      break;

    case 'bode':

      $liefergebinde = $katalog_gebindegroesse;
      $liefereinheit = "$kan_liefermult $kan_liefereinheit";  // oder = $katalog_einheit ???
      $verteileinheit_default = $liefereinheit;
      $lv_faktor_default = 1;

      break;

    case 'rapunzel':

      $liefergebinde = $katalog_gebindegroesse;
      $liefereinheit = "$kan_liefermult $kan_liefereinheit";
      $verteileinheit_default = $liefereinheit;
      $lv_faktor_default = 1;

      break;

    case 'midgard':
    case 'grell':
    case 'bnn':
      $have_mwst = true;

      $liefergebinde = $katalog_gebindegroesse;
      $liefereinheit = "$kan_liefermult $kan_liefereinheit";
      $verteileinheit_default = $liefereinheit;
      $lv_faktor_default = 1;

      break;

    default:
    case 'keins':
      if( $display_level >= 1 ) {
        open_div( 'warn', '', "unbekanntes oder undefiniertes Katalogformat [{$katalogeintrag['katalogformat']}] --- Katalogabgleich nicht moeglich" );
      }
      return 2;
  }

  if( $have_mwst) {
    $katalog_mwst = $katalogeintrag["mwst"];
    $katalog_brutto = $katalog_netto * (1 + $katalog_mwst / 100.0 );
  } else {
    $katalog_mwst = 'n/a';
    $katalog_brutto = 'n/a';
  }

  if( $display_level >= 2 ) {
    if( $kgueltig ) {
      $class = 'ok';
      $checkedyes = 'checked';
      if( $editable )
        $checkedno = 'onclick="'. fc_action( 'update,context=js', "action=katalog_ungueltig,message=$katalog_id" ) . ';"';
      else
        $checkedno = 'disabled';
    } else {
      $class = 'alert';
      if( $editable )
        $checkedyes = 'onclick="'. fc_action( 'update,context=js', "action=katalog_gueltig,message=$katalog_id" ) . ';"';
      else
        $checkedyes = 'disabled';
      $checkedno = 'checked';
    }
    open_fieldset( 'big_form', '', "Lieferantenkatalog: Artikel gefunden in Katalog $katalog_katalogname" );
      open_table( 'list hfill' );
          open_th( '', "title='Artikelnummer'", 'A-Nr.' );
          open_th( '', "title='Bestellnummer'", 'B-Nr.' );
          open_th( '', '', 'Bezeichnung' );
          open_th( '', '', 'Einheit' );
          open_th( '', '', 'Gebinde' );
          open_th( '', '', 'Land' );
          open_th( '', '', 'Verband' );
          open_th( '', '', 'Hersteller' );
          open_th( '', "title='European Article Number'", 'EAN (einzeln)');
          open_th( '', '', 'L-Preis' );
          open_th( '', '', 'MWSt' );
          open_th( '', '', 'Brutto' );
          open_th( '', "title='hier koennt ihr fehlerhafte oder ungueltige Katalogeintraege markieren'", 'gilt noch' );
        open_tr();
          open_td( '', '', $katalog_artikelnummer );
          open_td( '', '', $katalog_bestellnummer );
          open_td();
            open_div('', '', $katalog_name);
            if ($katalog_bemerkung)
              open_div('small', '', $katalog_bemerkung);
          open_td( '', '', $katalog_einheit );
          open_td( '', '', $katalog_gebindegroesse );
          open_td( '', '', $katalog_herkunft );
          open_td( '', '', $katalog_verband );
          open_td( '', '', $katalog_hersteller );
          open_td( '', '', ean_view($katalog_ean).ean_links($katalog_ean));
          open_td( '', '', $katalog_netto );
          open_td( '', '', $katalog_mwst );
          open_td( '', '', $katalog_brutto );
          open_td( "$class", "rowspan='2'" );
            open_div( '', '', "<input type='radio' class='radiooption' name='kgueltig' $checkedyes> ja" );
            open_div( '', '', "<input type='radio' class='radiooption' name='kgueltig' $checkedno> nein" );
        open_tr();
          open_td( 'left small top', "colspan='3'", 'Interpretation der Foodsoft:' );
          open_td( 'center small top', "colspan='2'", "1 Gebinde = $liefergebinde * ($liefereinheit)" );
          open_td( '', "colspan='4'", '');
          open_td( 'center small top', "colspan='3'", "Preis gilt pro $liefereinheit" );
      close_table();
    close_fieldset();
  }

  ////////////////////////////////
  // aktuellsten preiseintrag mit Katalogeintrag vergleichen,
  // Vorlage fuer neuen preiseintrag mit Katalogdaten vorbesetzen:
  // - die L-felder werden immer aus dem katalog uebernommen, 
  // - die V-felder wenn moeglich aus dem letzten Preiseintrag
  //

  $preiseintrag_neu['katalog_id'] = $katalog_id;
  $preiseintrag_neu['katalogname'] = $katalog_katalogname;
  $preiseintrag_neu['liefereinheit'] = $liefereinheit;
  $preiseintrag_neu['lieferpreis'] = $katalog_netto;
  $preiseintrag_neu['bestellnummer'] = $katalog_bestellnummer;
  if( $have_mwst ) {
    $preiseintrag_neu['mwst'] = $katalog_mwst;
  }

  if( $preis_id ) {
    $problems = array();

    // liefereinheit und mwst sollten mit katalog uebereinstimmen:
    //
    if( $preiseintrag_neu['liefereinheit'] != $artikel['liefereinheit'] ) {
      $problems[] = "Problem: L-Einheit stimmt nicht:
        <p class='li'>Katalog: <kbd>{$preiseintrag_neu['liefereinheit']}</kbd></p>
        <p class='li'>Foodsoft: <kbd>{$artikel['liefereinheit']}</kbd></p>
        <div class='small'>die L-Einheit sollte dem Einzelpreis im Katalog zugrundeliegen</div>
      ";
    }
    if( $have_mwst ) {
      $preiseintrag_neu['mwst'] = $katalog_mwst;
      if( abs( $preiseintrag_neu['mwst'] - $artikel['mwst'] ) > 0.005 ) {
        $problems[] = "Problem: MWSt-Satz stimmt nicht:
          <p class='li'>Katalog: <kbd>{$preiseintrag_neu['mwst']}</kbd></p>
          <p class='li'>Foodsoft: <kbd>{$artikel['mwst']}</kbd></p>
        ";
      }
    }

    $preiseintrag_neu['verteileinheit'] =
      ( $artikel['verteileinheit'] ? $artikel['verteileinheit'] : $verteileinheit_default );

    switch( $kan_liefereinheit ) {
      case 'KI':
      case 'PA':
      case 'GB':
      case 'VPE':
        // verteileinheit darf von liefereinheit abweichen:
        break;
      default:
        if( $artikel['kan_verteileinheit'] === 'VPE' ) {
          break;
        }
        if( $kan_liefereinheit !== $artikel['kan_verteileinheit'] ) {
          $preiseintrag_neu['verteileinheit'] = $verteileinheit_default;
          $problems[] = "Warnung: Einheiten inkompatibel:
                  <p class='li'>Katalog: <kbd>$kan_liefereinheit</kbd></p>
                  <p class='li'>Verteilung: <kbd>{$artikel['kan_verteileinheit']}</kbd></p>
                  <div class='small'>die Einheiten sollten in der Regel Vielfache voneinander sein - Ausnahmen auf eigene Gefahr!</div>
          ";
        }
    }

    list( $kan_verteilmult_neu, $kan_verteileinheit_neu ) = kanonische_einheit( $preiseintrag_neu['verteileinheit'] );

    if( $kan_liefereinheit !== $kan_verteileinheit_neu ) {
      // keine automatische umrechnung moeglich; wir uebernehmen die alten werte, die
      // manuell geprueft und bei bedarf korrigiert werden muessen:
      if( $artikel['lv_faktor'] > 0.001 ) {
        $preiseintrag_neu['lv_faktor'] = $artikel['lv_faktor'];
        if( $liefergebinde > 0 ) { // terra listet manchmal gebindegroesse 0
          $preiseintrag_neu['gebindegroesse'] = $liefergebinde * $preiseintrag_neu['lv_faktor'];
          if( abs( $preiseintrag_neu['gebindegroesse'] - $artikel['gebindegroesse'] ) > 0.001 ) {
            $problems[] = "Problem: Gebindegroessen oder Umrechnung Liefer/Verteileinheit stimmen nicht:
              <p class='li'>Katalog: <kbd>$liefergebinde * $liefereinheit</kbd></p>
              <p class='li'>Foodsoft: <kbd>{$artikel['gebindegroesse']} * {$artikel['verteileinheit']}</kbd></p>
              <div class='small'>Bitte manuell pr&uuml;en und neuen Preiseintrag erfassen!</div>
            ";
          }
        }
      } else {
        if( $artikel['gebindegroesse'] > 0.001 ) {
          $preiseintrag_neu['lv_faktor'] = $liefergebinde / $artikel['gebindegroesse'];
        } else {
          $preiseintrag_neu['lv_faktor'] = 1.0;
        }
        $problems[] = "Problem: Umrechnungsfaktor von Liefer und Verteileinheit noch nicht erfasst.
            <div class='small'>Bitte manuell pr&uuml;en und neuen Preiseintrag erfassen!</div>
        ";
      }

    } else {

      // einheiten sind kompatibel: wir setzen die vorlage auf aus katalog berechnete werte und vergleichen
      // anschliessend mit dem ist-zustand:

      $preiseintrag_neu['lv_faktor'] = $kan_liefermult / $kan_verteilmult_neu;
      if( $liefergebinde > 0 ) { // terra listet manchmal gebindegroesse 0
        $preiseintrag_neu['gebindegroesse'] = $liefergebinde * $preiseintrag_neu['lv_faktor'];
        if( abs( $preiseintrag_neu['gebindegroesse'] - $artikel['gebindegroesse'] ) > 0.001 ) {
          $problems[] = "Problem: Gebindegroessen stimmen nicht:
            <p class='li'>Katalog: <kbd>$liefergebinde * $liefereinheit</kbd></p>
            <p class='li'>Foodsoft: <kbd>{$artikel['gebindegroesse']} * {$artikel['verteileinheit']}</kbd></p>
          ";
        }
      }
    }

    if( abs( $preiseintrag_neu['lieferpreis'] - $artikel['nettolieferpreis'] ) > 0.005 ) {
      $p = "Problem: Preise stimmen nicht (beide Netto ohne Pfand):
        <p class='li'>Katalog: <kbd>$katalog_netto / $liefereinheit</kbd></p>
        <p class='li'>Foodsoft: <kbd>". price_view($artikel['nettopreis'])." / {$artikel['verteileinheit']} ";
      if( $liefereinheit != $artikel['verteileinheit'] ) {
        $p .= " = {$artikel['nettolieferpreis']} / $liefereinheit";
      }
      $p .= "</kbd></p>";
      $problems[] = $p;
    }

    if( $problems ) {
      $neednewprice = true;
    }

    if( $katalog_bestellnummer != $artikel['bestellnummer'] ) {
      $neednewbestellnummer = true;
      $problems[] = "Problem: Bestellnummern stimmen nicht:
        <p class='li'>Katalog: <kbd>$katalog_bestellnummer</kbd></p>
        <p class='li'>Foodsoft: <kbd>{$artikel['bestellnummer']}</kbd></p>
      ";
    }

    if( ( ! $problems ) and ( ! $kgueltig ) ) {
      $problems[] = "Katalogeintrag ist als ungueltig markiert, stimmt aber mit aktuellem Preiseintrag ueberein --- bitte manuell pruefen!";
      $neednewprice = true;
    }

    if( $problems && ( $display_level >= 1 ) ) {
      if( $kgueltig ) {
        open_div( 'warn' );
      } else {
        open_div( 'alert' );
        smallskip();
        echo "Katalogeintrag ist als ungueltig markiert --- bitte pruefen:";
        medskip();
      }
      foreach( $problems as $p ) {
        open_div( '', '', $p );
        smallskip();
      }
      close_div();
    }

  } else {
    // kein aktuell gueltiger Preiseintrag: wir erzeugen defaults aus Katalogdaten...
    $preiseintrag_neu['verteileinheit'] = $verteileinheit_default;
    $preiseintrag_neu['lv_faktor'] = $lv_faktor_default;
    $preiseintrag_neu['gebindegroesse'] = $liefergebinde * $lv_faktor_default;
    // ...und empfehlen einen neuen Preiseintrag:
    $neednewprice = TRUE;
  }

  if( $neednewprice ) {
    // inkonsistenz: sollte manuell ueberprueft werden:
    return 1;
  }
  if( $neednewbestellnummer ) {
    if( $kgueltig ) {
      // automatisches update sollte unproblematisch sein:
      return 4;
    } else {
      return 1;
    }
  }
  return 0; // keine probleme
}

// update_preis:
//   aktuellen preiseintrag aus katalog automatisch erzeugen
//   (zur zeit: nur falsche bestellnummern werden automatisch korrigiert!)
// rueckgabe:
//  -1 : preis ist aktuell, kein neueintrag notwendig
//   0 : automatische aktualisierung nicht moeglich oder fehlgeschlagen
//  >0 : preis wurde aktualisiert, rueckgabe ist produktpreise.id
//
function update_preis( $produkt_id ) {
  global $mysqlheute;
  $preiseintrag_neu = array();
  $r = katalogabgleich( $produkt_id, 0, 0, $preiseintrag_neu );
  switch( $r ) {
    case 0:
      return -1;
    case 1:
    case 2:
    case 3:
      return 0;
    case 4:
      if( ! isset( $preiseintrag_neu['pfand'] ) || ! isset( $preiseintrag_neu['mwst'] ) ) {
        // pfand und mwst, wenn nicht im katalog, aus letztem aktuellen preiseintrag uebernehmen:
        $preis_id = sql_aktueller_produktpreis( $produkt_id );
        if( $preis_id ) {
          $p_alt = sql_produkt( array( 'preis_id' => $preis_id['id'] ) );
          if( ! isset( $preiseintrag_neu['pfand'] ) )
            $preiseintrag_neu['pfand'] = $p_alt['pfand'];
          if( ! isset( $preiseintrag_neu['mwst'] ) )
            $preiseintrag_neu['mwst'] = $p_alt['mwst'];
        }
      }
      foreach( array( 'lieferpreis', 'bestellnummer', 'gebindegroesse', 'mwst', 'pfand'
                    , 'liefereinheit', 'verteileinheit', 'lv_faktor' ) as $key ) {
        if( ! isset( $preiseintrag_neu[ $key ] ) ) {
          continue 2;
        }
      }
      return sql_insert_produktpreis(
        $produkt_id, $preiseintrag_neu['lieferpreis'], $mysqlheute
      , $preiseintrag_neu['bestellnummer'], $preiseintrag_neu['gebindegroesse']
      , $preiseintrag_neu['mwst'], $preiseintrag_neu['pfand']
      , $preiseintrag_neu['liefereinheit'], $preiseintrag_neu['verteileinheit']
      , $preiseintrag_neu['lv_faktor']
      );
  }
}

?>
