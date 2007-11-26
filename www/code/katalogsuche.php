<?php

$ldap_handle = false;

function init_ldap_handle() {
  global $ldap_handle, $ldapuri;

  if( ! $ldap_handle ) {
    if( ! $ldapuri )
      return false;

   $h = ldap_connect( $ldapuri );
   if( ! ldap_set_option( $h, LDAP_OPT_PROTOCOL_VERSION, 3 ) ) {
     return false;
   }
   if( ! ldap_bind( $h ) ) {
     return false;
   }
    $ldap_handle = $h;
  }
  return $ldap_handle;
}

// katalogsuche: sucht im lieferantenkatalog nach $produkt.
// bisher nur fuer Terra.
// $produkt ist entweder eine produkt_id, oder das Ergebnis von sql_produkt_details().
//
function katalogsuche( $produkt ) {
  global $ldap_handle, $ldapbase;
  if( is_numeric( $produkt ) ) {
    $produkt = sql_produkt_details( $produkt );
  }
  
  $lieferant_name = lieferant_name( $produkt['lieferanten_id'] );
  switch( $lieferant_name ) {
    case 'Terra' :
      if( ! ( $artikelnummer = $produkt['artikelnummer'] ) )
        return false;
      if( ! $ldap_handle ) {
        init_ldap_handle();
      }
      if( $ldap_handle ) {
        $katalogergebnis = ldap_search( $ldap_handle, $ldapbase
        , "(&(objectclass=terraartikel)(terraartikelnummer=$artikelnummer))"
        );
        $katalogeintraege = ldap_get_entries( $ldap_handle, $katalogergebnis );
        return $katalogeintraege;
      }
    break;
    default:
      return false;
  }
  return false;
}


// katalogvergleich
//
// rueckgabe:
//  0: ok
//  1: Katalogeintrag weicht ab
//  2: Katalogsuche ohne Treffer
//  3: Katalogsuche fehlgeschlagen
//
function katalogabgleich(
  $produkt_id
, $editable = false
, $detail = false
, & $preiseintrag_neu = array()
) {
  $artikel = sql_produkt_details( $produkt_id );
  $prgueltig = $artikel['zeitstart'];
  $neednewprice = false;

  $katalogeintraege = katalogsuche( $artikel );
  if( ! $katalogeintraege )
    return 3;

  $katalogtreffer = $katalogeintraege['count'];
  if( ! $katalogtreffer ) {
    ?> <div class='warn'>Katalogsuche: Artikelnummer nicht gefunden!</div> <?
    if( $detail and $editable )
      formular_artikelnummer( $produkt_id, false, true );
    return 2;
  }
  
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
  if( $detail ) {
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
  }

  kanonische_einheit( $katalog_einheit, &$kan_katalogeinheit, &$kan_katalogmult );

  ////////////////////////////////
  // aktuellsten preiseintrag mit Katalogeintrag vergleichen,
  // Vorlage fuer neuen preiseintrag mit Katalogdaten vorbesetzen:
  //
  if( $prgueltig ) {

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
    return ( $neednewprice ? 1 : 0 );

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

    return 1;
  }
}

?>
