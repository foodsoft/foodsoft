<?php

// terraabgleich.php (name ist historisch: nuetzlich auch fuer andere lieferanten!)
//
// sucht in produktliste und preishistorie nach inkonsistenzen,
// und nach unterschieden zum Terra-katalog,
// macht ggf. verbesserungsvorschlaege und erlaubt aenderungen
//
// ausgabe wird durch folgende variable bestimmt:
// - produktid: fuer detailanzeige ein produkt
// - bestell_id: erlaubt auswahl preiseintrag fuer diese bestellung (nur mit produktid)
// - lieferanten_id: anzeige aller produkte eines lieferanten

  require_once('code/config.php');
  require_once('code/err_functions.php');
  require_once('code/zuordnen.php');
  require_once('code/login.php');

  //nur_fuer_dienst(4,5);
  
  $self = 'terraabgleich.php?';
  $self_fields = '';

  $detail = get_http_var('produktid');
  if( $detail ) {
    // $self: um diese seite nochmal aufzurufen:
    $self = "$self&produktid=$produktid";
    // fuer POST-formulare: auch als hidden-eingabefeld:
    $self_fields = "<input type='hidden' name='produktid' value='$produktid'>";
  }

  $bestell_id = false;
  if( get_http_var('bestell_id') ) {
    $self = "$self&bestell_id=$bestell_id";
    $self_fields = $self_fields . "<input type='hidden' name='bestell_id' value='$bestell_id'>";
  }

  $title = 'Produktdaten';
  $subtitle = 'Produktdaten';
  $wikitopic = "foodsoft:datenbankabgleich";
  if( $detail ) {
    if( $bestell_id )
      $subtitle = $subtitle . " - Auswahl Preiseintrag";
    else
      $subtitle = $subtitle . " - Detailanzeige";
  }
  require_once('windows/head.php');

  if( $detail ) {
    $result = mysql_query( "SELECT * FROM produkte WHERE id='$produktid'" )
      or error ( __LINE__, __FILE__, "Suche nach Produkt fehlgeschlagen" );
    $row = mysql_fetch_array($result)
      or error ( __LINE__, __FILE__, "Produkt nicht gefunden" );
    $lieferanten_id = $row['lieferanten_id'];
  } else {
    need_http_var( 'lieferanten_id' );
  }
  $self = "$self&lieferanten_id=$lieferanten_id";
  $self_fields = $self_fields . "<input type='hidden' name='lieferanten_id' value='$lieferanten_id'>";


  if( $detail ) {

    get_http_var( 'action' );

    if( $action == 'zeitende_setzen' ) {
      fail_if_readonly();
      nur_fuer_dienst_IV();
      need_http_var('preis_id');
      need_http_var('zeitende');
      $result = mysql_query( "UPDATE  produktpreise SET zeitende='$zeitende' WHERE id='$preis_id'" )
        or error( __LINE__, __FILE__, "Setzen Zeitende in Preishistorie fehlgeschlagen" );
    }

    if( $bestell_id ) {

      if( $action == 'preiseintrag_waehlen' ) {
        fail_if_readonly();
        nur_fuer_dienst_IV();
        need_http_var( 'preis_id' );
        $result = mysql_query(
          "UPDATE bestellvorschlaege
           SET produktpreise_id='$preis_id'
           WHERE gesamtbestellung_id='$bestell_id' AND produkt_id='$produktid' " )
          or error ( __LINE__, __FILE__, "Setzen des neuen Preiseintrags fehlgeschlagen" );
      }

      $result = mysql_query(
        "SELECT * FROM bestellvorschlaege
         WHERE gesamtbestellung_id=$bestell_id AND produkt_id=$produktid"
      ) or error ( __LINE__, __FILE__, "Suche nach Bestellvorschlag fehlgeschlagen" );
      $row = mysql_fetch_array( $result )
        or error ( __LINE__, __FILE__, "Bestellvorschlag nicht gefunden" );
      $preisid_in_bestellvorschlag = $row['produktpreise_id'];

      $result = mysql_query( "SELECT * FROM gesamtbestellungen WHERE id=$bestell_id" )
        or error ( __LINE__, __FILE__, "Suche nach Gesamtbestellung fehlgeschlagen" );
      $row = mysql_fetch_array( $result )
        or error ( __LINE__, __FILE__, "Gesamtbestellung nicht gefunden" );
      $bestellung_name = $row['name'];

    }

    // eventuell neuen preiseintrag vornehmen:
    //
  
    if( $action == 'neuer_preiseintrag' ) {
      fail_if_readonly();
      nur_fuer_dienst_IV();

      need_http_var('newfcmult');
      need_http_var('newfceinheit');
      need_http_var('newfcgebindegroesse');
      need_http_var('newfcmwst');
      need_http_var('newfcpfand');
      need_http_var('newfcpreis');
      need_http_var('newfcname');
      need_http_var('newfcbnummer');
      need_http_var('newfczeitstart');
      need_http_var('newliefermult');
      need_http_var('newliefereinheit');
      get_http_var('newnotiz') or $newnotiz = '';

      ( $terraprodukt = mysql_query( "SELECT * FROM produkte WHERE id=$produktid" ) )
        || error ( __LINE__, __FILE__, "Suche nach Produkt fehlgeschlagen" );

      ( $terrapreise = mysql_query( "SELECT * FROM produktpreise WHERE produkt_id=$produktid ORDER BY zeitstart" ) )
        || error ( __LINE__, __FILE__, "Suche nach Produktpreisen fehlgeschlagen" );

      if( mysql_query( "UPDATE produkte SET einheit='$newfcmult $newfceinheit' WHERE id=$produktid" ) ) {
        // echo "<div class='ok'>neue Einheit: $newfcmult $newfceinheit</div>";
      } else {
        echo "<div class='warn'>FEHLGESCHLAGEN: neue Einheit: $newfcmult $newfceinheit</div>";
      }
      if( mysql_query( "UPDATE produkte SET name='$newfcname' WHERE id=$produktid" ) ) {
        // echo "<div class='ok'>neue Bezeichnung: $newfcname</div>";
      } else {
        echo "<div class='warn'>FEHLGESCHLAGEN: neue Bezeichnung: $newfcname</div>";
      }
      if( mysql_query( "UPDATE produkte SET notiz='$newnotiz' WHERE id=$produktid" ) ) {
        // echo "<div class='ok'>neue Notiz: $newnotiz</div>";
      } else {
        echo "<div class='warn'>FEHLGESCHLAGEN: neue Notiz: $newnotiz</div>";
      }
    
      $pr0 = false;
      while( $pr1 = mysql_fetch_array($terrapreise) ) {
        $pr0 = $pr1;
      }
      if( $pr0 ) {
        if( ( ! $pr0['zeitende'] ) or ( $pr0['zeitende'] > $newfczeitstart ) ) {
          if( mysql_query( "UPDATE produktpreise SET zeitende='$newfczeitstart' WHERE id=" . $pr0['id'] ) ) {
            // echo "<div class='ok'>letzter Preiseintrag ausgelaufen ab: $newfczeitstart</div>";
          } else {
            echo "<div class='warn'>FEHLGESCHLAGEN: konnte letzten Preiseintrag nicht abschliessen</div>";
          }
        }
      }
      if( mysql_query( "
            INSERT INTO produktpreise
            ( produkt_id
            , preis
            , zeitstart
            , zeitende
            , bestellnummer
            , gebindegroesse
            , mwst
            , pfand
            , liefereinheit
            , verteileinheit )
            VALUES (
              $produktid
            , '$newfcpreis'
            , '$newfczeitstart'
            , NULL
            , '$newfcbnummer'
            , '$newfcgebindegroesse'
            , '$newfcmwst'
            , '$newfcpfand'
            , '$newliefermult $newliefereinheit'
            , '$newfcmult $newfceinheit'
            )"
          )
       ) {
        // echo "<div class='ok'>neuer Preiseintrag gespreichert</div>";
      } else {
        echo "<div class='warn'>neuer Preiseintrag FEHLGESCHLAGEN: " . mysql_error() . "</div>";
      }
    }

    // eventuell neue Artikelnummer setzen:
    //
    if( $action == 'artikelnummer_setzen' ) {
      fail_if_readonly();
      nur_fuer_dienst_IV();
      need_http_var( 'anummer' );
      if( ! mysql_query( "UPDATE produkte SET artikelnummer='$anummer' WHERE id='$produktid' " ) )
        echo "<div class='warn'>Setzen der neuen Artikelnummer FEHLGESCHLAGEN</div>";
    }

  }

  get_http_var( 'order_by' ) or $order_by = 'name';

  $result = mysql_query( "SELECT * FROM lieferanten WHERE id='$lieferanten_id'" )
    or error ( __LINE__, __FILE__, "Suche nach Lieferant fehlgeschlagen" );
  $row = mysql_fetch_array($result)
    or error ( __LINE__, __FILE__, "Lieferant nicht gefunden" );
  $is_terra = ( $row['name'] == 'Terra' );

  $filter = "lieferanten_id='$lieferanten_id'";
  if( $detail ) {
    $filter = "$filter AND id='$produktid'";
  }
  // echo 'filter: ' . $filter;
  $produkte = mysql_query( "SELECT * FROM produkte WHERE $filter ORDER BY $order_by" )
    or error ( __LINE__, __FILE__, "Suche nach Produkten fehlgeschlagen" );
  
  if( $is_terra and $ldapuri != '' ) {
    // echo "<br>connecting... ";
    $ldaphandle = ldap_connect( $ldapuri );
    // echo " result is: " . $ldaphandle  . " <br>";
  
    // echo "<br>setting protocol version 3...";
    $rv = ldap_set_option( $ldaphandle, LDAP_OPT_PROTOCOL_VERSION, 3 );
    // echo " result is: " . $rv  . " <br>";
  
    // echo "<br>binding to server...";
    $rv = ldap_bind( $ldaphandle );
    // echo " result is: " . $rv  . " <br>";
  } else {
    $ldaphandle = false;
  }

  echo '
    <table width="100%">
      <colgroup>
        <col width="7%">
        <col>
      </colgroup>
      <tr>
        <th class="outer">A-Nr.</th>
        <th class="outer">Artikeldaten</th>
      </tr>
  ';

  $outerrow=0;
  while ( ++$outerrow < 9999 && ( $artikel = mysql_fetch_array( $produkte ) ) ) {
    do_artikel();
  }
  echo '</table>';


  // mysql_repair_link:
  // erzeugt kleines formular, alle felder "hidden", nur submit-knopf sichtbar,
  // das dieses Skript neu aufruft und dabei einen beliebigen SQL-befehl uebergibt
  //
  function mysql_repair_link( $fields, $kommentar, $domid = '' ) {
    global $produktid, $self, $self_fields;
    echo "
      <div class='warn' style='padding:0.1ex 1em 0.1ex 2em;'>
        <form method='post' action='$self'>
          $self_fields
          $fields
          <input type='submit' name='submit' value='$kommentar'
    ";
    if( $domid != '' ) {
      echo "    onclick=\"document.getElementById('$domid').className='modified';\"";
    }
    echo "
        ></form>
       </div>
    ";
  }

  // do_artikel
  // wird aus der hauptschleife aufgerufen, um einen artikel aus der Produktliste anzuzeigen
  //
  function do_artikel() {
    global $outerrow, $ldaphandle, $ldapbase, $artikel, $detail, $mysqljetzt, $is_terra
         , $bestell_id, $bestellung_name, $preisid_in_bestellvorschlag, $self, $self_fields;

    echo "\n<tr id='row$outerrow'>";
    $anummer = $artikel['artikelnummer'];
    $name = $artikel['name'];
    $produktid = $artikel['id'];
    $notiz = $artikel['notiz'];

    // flag: neuen preiseintrag vorschlagen (falls gar keiner oder fehlerhaft):
    //
    $neednewprice = FALSE;

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

    // flag: suche nach artikelnummer vorschlagen (falls kein Treffer bei Katalogsuche):
    //
    $neednewarticlenumber = FALSE;

    echo "<th class='outer' style='vertical-align:top;'>";
    if( $detail ) {
      echo "$anummer<br>id:&nbsp;$produktid";
    } else {
      echo "
        <a class='blocklink'
        href=\"javascript:neuesfenster('$self&produktid=$produktid','produktdetails')\"
        title='Details...'
        onclick=\"document.getElementById('row$outerrow').className='modified';\"
        >$anummer<br>id:&nbsp;$produktid</a>
      ";
    }
    echo '</th><td class="outer" style="padding-bottom:1ex;">';


    //
    // produktpreise abfragen und (ggf.) anzeigen:
    //
    $produktpreise = mysql_query(
      "SELECT * FROM produktpreise WHERE produkt_id='$produktid' ORDER BY produkt_id,zeitstart"
    ) or error ( __LINE__, __FILE__, "Suche nach Produktpreisen fehlgeschlagen" );

    if( $detail ) {
      echo "
        <div class='untertabelle'>
          <img id='preishistorie_knopf' class='button' src='img/close_black_trans.gif'
            onclick='preishistorie_toggle();' title='Ausblenden'>
          </img>
      ";
      if( $bestell_id ) {
        echo "Preiseintrag w&auml;hlen f&uuml;r Bestellung $bestellung_name:";
      } else {
        echo "Preis-Historie:";
      }
      echo "
        </div>
        <div id='preishistorie'>
          <table width='100%' class='numbers'>
            <tr>
              <th>id</th>
              <th>B-Nr</th>
              <th>von</th>
              <th>bis</th>
              <th title='Liefer-Einheit: f&uuml;rs Bestellen beim Lieferanten' colspan='2'>L-Einheit</th>
              <th title='Nettopreis beim Lieferanten' colspan='2'>L-Preis</th>
              <th title='Verteil-Einheit: f&uuml;rs Bestellen und Verteilen bei uns' colspan='2'>V-Einheit</th>
              <th title='Gebindegr&ouml;&szlig;e in V-Einheiten'>Gebinde</th>
              <th>MWSt</th>
              <th title='Pfand je V-Einheit'>Pfand</th>
              <th title='Endpreis je V-Einheit' colspan='2'>V-Preis</th>
      ";
      if( $bestell_id ) {
        echo "<th title='Preiseintrag f&uuml;r Bestellung $bestellung_name'>Aktiv</th>";
      }
      echo "</tr>";
      while( $pr1 = mysql_fetch_array($produktpreise) ) {
        preisdatenSetzen( &$pr1 );
        echo "
          <tr>
            <td>{$pr1['id']}</td>
            <td>{$pr1['bestellnummer']}</td>
            <td>{$pr1['zeitstart']}</td>
            <td>
        ";
        if( $pr1['zeitende'] ) {
          echo "{$pr1['zeitende']}";
        } else {
          echo "
            <form method='post' action='$self'>
              $self_fields
              <input type='hidden' name='action' value='zeitende_setzen'>
              <input type='hidden' name='zeitende' value='$mysqljetzt'>
              <input type='hidden' name='preis_id' value='{$pr1['id']}'>
              <input type='submit' name='submit' value='Abschliessen'
                onclick='document.getElementById(\"row$outerrow\").className=\"modified\";'
                title='Preisintervall abschliessen (z.B. falls Artikel nicht lieferbar!)'>
            </form>
          ";
        }
        echo "
            </td>
            <td class='mult'>{$pr1['kan_liefermult']}</td>
            <td class='unit'>{$pr1['kan_liefereinheit']}</td>
            <td class='mult'>{$pr1['lieferpreis']}</td>
            <td class='unit'>/ {$pr1['preiseinheit']}</td>
            <td class='mult'>{$pr1['kan_verteilmult']}</td>
            <td class='unit'>{$pr1['kan_verteileinheit']}</td>
            <td class='number'>{$pr1['gebindegroesse']}</td>
            <td class='number'>{$pr1['mwst']}</td>
            <td class='number'>{$pr1['pfand']}</td>
            <td class='mult'>{$pr1['preis_rund']}</td>
            <td class='unit'> / {$pr1['kan_verteilmult']} {$pr1['kan_verteileinheit']}</td>
        ";
        if( $bestell_id ) {
          echo "<td>";
          if( $pr1['id'] == $preisid_in_bestellvorschlag ) {
            echo "<input type='submit' name='aktiv' value='aktiv' class='buttondown'
              style='width:5em;'
              title='gilt momentan f&uuml;r Abrechnung der Bestellung $bestellung_name'
              >"; // <b>aktiv</b></span>";
          } else {
            echo "
              <form action='$self' method='post'>
                $self_fields
                <input type='hidden' name='action' value='preiseintrag_waehlen'>
                <input type='hidden' name='preis_id' value='{$pr1['id']}'>
                <input type='submit' name='setzen' value='setzen' class='buttonup'
                  style='width:5em;'
                  title='diesen Preiseintrag f&uuml;r Bestellung $bestellung_name w&auml;hlen'
                >
              </form>
            ";
          }
          echo "</td>";
        }
        echo "</tr>";
      }
      if( mysql_num_rows( $produktpreise ) > 0 ) {
        mysql_data_seek( $produktpreise, 0 );
      }
      echo '</table></div>';
    }

    // produktpreise: test auf konsistenz:
    //  - alle intervalle bis auf das letzte muessen abgeschlossen sein
    //  - intervalle duerfen nicht ueberlappen
    //  - warnen, wenn kein aktuell gueltiger preis vorhanden
    //
    $pr0 = FALSE;
    $prgueltig = FALSE; // flag: wir haben einen akzeptablen preiseintrag fuer diesen artikel
    while( $pr1 = mysql_fetch_array($produktpreise) ) {
      if( $pr0 ) {
        if ( $pr0['zeitende'] == '' ) {
          echo "<div class='warn'>FEHLER: Preisintervall {$pr0['id']} nicht aktuell aber nicht abgeschlossen.</div>";
          $detail && mysql_repair_link(
            "<input type='hidden' name='action' value='zeitende_setzen'>
             <input type='hidden' name='zeitende' value='{$pr1['zeitstart']}'>
             <input type='hidden' name='preis_id' value='{$pr0['id']}'>"
          , "Zeitende in {$pr0['id']} auf {$pr1['zeitstart']} setzen"
          , "row$outerrow"
          );
        } else if ( $pr0['zeitende'] > $pr1['zeitstart'] ) {
          echo "<div class='warn'>FEHLER: Ueberlapp in Preishistorie: {$pr0['id']} und {$pr1['id']}.</div>";
          $detail && mysql_repair_link(
            "<input type='hidden' name='action' value='zeitende_setzen'>
             <input type='hidden' name='zeitende' value='{$pr1['zeitstart']}'>
             <input type='hidden' name='preis_id' value='{$pr0['id']}'>"
          , "Zeitende in {$pr0['id']} auf {$pr1['zeitstart']} setzen"
          , "row$outerrow"
          );
        }
      }
      $pr0 = $pr1;
    }
    if( ! $pr0 ) {
      echo "<div class='warn'>WARNUNG: kein Preiseintrag fuer diesen Artikel vorhanden!</div>";
    } else if ( $pr0['zeitende'] != '' ) {
      if ( $pr0['zeitende'] < $mysqljetzt ) {
        echo "<div class='warn'>WARNUNG: kein aktuell g&uuml;ltiger Preiseintrag fuer diesen Artikel vorhanden!</div>";
        // echo '&nbsp; letzter eintrag: ab: '. $pr0['zeitstart'] . ' bis: ' . $pr0['zeitende'] . ' preis: ' . $pr0['preis'] . '<br>';
      } else {
        echo "<div class='warn'>WARNUNG: aktueller Preis l&auml;uft aus!</div>";
        // echo '&nbsp; letzter eintrag: ab: '. $pr0['zeitstart'] . ' bis: ' . $pr0['zeitende'] . ' preis: ' . $pr0['preis'] . '<br>';
        $prgueltig = $pr0;  // kann man noch zulassen...
      }
    } else {
      $prgueltig = $pr0;
    }

    if( $prgueltig ) {
      preisdatenSetzen( &$prgueltig );
      $fcbnummer = $prgueltig['bestellnummer'];
      $fcgebindegroesse = $prgueltig['gebindegroesse'];
      $fcpreis = $prgueltig['preis'];
      $fcpfand = $prgueltig['pfand'];
      $fcmwst = $prgueltig['mwst'];
      $fclieferpreis = $prgueltig['lieferpreis'];
      $fcpreiseinheit = $prgueltig['preiseinheit'];
      $fcmengenfaktor = $prgueltig['mengenfaktor'];
      $kan_fcmult = $prgueltig['kan_verteilmult'];
      $kan_fceinheit = $prgueltig['kan_verteileinheit'];
      $kan_liefermult = $prgueltig['kan_liefermult'];
      $kan_liefereinheit = $prgueltig['kan_liefereinheit'];
      if( ! $kan_liefereinheit ) {
        echo "<div class='warn'>FEHLER: keine gueltige Liefereinheit</div>";
        $neednewprice = TRUE;
      }
    } else {
      $fcbnummer = NULL;
      $fcgebindegroesse = NULL;
      $fcpreis = NULL;
      $fcpfand = NULL;
      $fcmwst = NULL;
      $fclieferpreis = NULL;
      $fcpreiseinheit = NULL;
      $fcmengenfaktor = NULL;
      $kan_fcmult = NULL;
      $kan_fceinheit = NULL;
      $kan_liefermult = NULL;
      $kan_liefereinheit = NULL;
    }


    //
    // Artikeldaten aus foodsoft-Datenbank anzeigen:
    //

    echo "
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
    ";
 
    if( $prgueltig ) {
      echo "<td>$fcbnummer</td>";
    } else {
      echo '<td><div class="warn" style="text-align:center;">keine</div></td>';
    }

    echo "<td>$name</td>";
    if( $prgueltig ) {
      echo "
        <td class='number'>$kan_liefermult $kan_liefereinheit</td>
        <td class='number'>$fclieferpreis / $fcpreiseinheit</td>
        <td class='number'>$kan_fcmult $kan_fceinheit</td>
        <td class='number'>$fcgebindegroesse</td>
        <td class='number'>$fcmwst</td>
        <td class='number'>$fcpfand</td>
        <td class='number'>$fcpreis / $kan_fcmult $kan_fceinheit</td>
      ";
    } else {
      echo "
        <td><div class='warn' style='text-align:center;'>-</div></td>
        <td><div class='warn' style='text-align:center;'>-</div></td>
        <td><div class='warn' style='text-align:center;'>-</div></td>
        <td><div class='warn' style='text-align:center;'>-</div></td>
        <td><div class='warn' style='text-align:center;'>-</div></td>
        <td><div class='warn' style='text-align:center;'>-</div></td>
        <td><div class='warn' style='text-align:center;'>-</div></td>
      ";
    }
    echo '</tr></table>';


    //
    // Artikeldaten aus Katalog suchen und ggf anzeigen:
    //
    if( $is_terra and $ldaphandle ) {

      $brutto = NULL;
      $mwst = NULL;
      $terragebindegroesse = NULL;
      $terrabnummer = NULL;
      $kan_terraeinheit = NULL;
      $kan_terramult = NULL;
    
      $katalogergebnis = ldap_search( $ldaphandle, $ldapbase, "(&(objectclass=terraartikel)(terraartikelnummer=$anummer))" );
      $katalogeintraege = ldap_get_entries( $ldaphandle, $katalogergebnis );
  
      $anummer_form = "
        <table>
          <tr>
            <td>
              neue Artikel-Nr. setzen:
            </td>
            <td>
              <form method='post' action='$self'>
                $self_fields
                <input type='hidden' name='action' value='artikelnummer_setzen'>
                <input type='text' size='20' name='anummer' value='$anummer'>&nbsp;
                <input type='submit' name='Submit' value='OK'
                 onclick='document.getElementById(\"row$outerrow\").className=\"modified\";'
                >
              </form>
            </td>
          </tr>
          <tr>
            <td>
              ...oder: Katalogsuche nach:
            </td>
            <td><form action='artikelsuche.php' method='post'>
                <input name='terracn' value='$name' size='40'>&nbsp;<input type='submit' name='submit' value='Los!'
                 onclick='document.getElementById(\"row$outerrow\").className=\"modified\";'
                 >
                <input type='hidden' name='produktid' value='$produktid'>
                <input type='hidden' name='produktname' value='$name'>
              </form>
            </td>
          </tr>
        </table>
      ";

      if( ( ! $katalogeintraege ) || ( $katalogeintraege['count'] < 1 ) ) {
  
        echo "<div class='warn'>Katalogsuche: Artikelnummer nicht gefunden!</div>";
        if( $detail ) {
          echo "
            <div id='anummer_form' class='small_form'>
              <form>
                <fieldset>
                  <legend>
                    Artikelnummer &auml;ndern:
                  </legend>
                  $anummer_form
                </fieldset>
              </form>
            </div>
          ";
        }
  
      } else {
  
        if( $detail ) {
          echo "
            <div style='display:none;' id='anummer_form' class='small_form'>
              <form>
                <fieldset>
                  <legend>
                    <img class='button' src='img/close_black_trans.gif' title='Ausblenden' onclick='anummer_off();'></img>
                    Artikelnummer &auml;ndern:
                  </legend>
                  $anummer_form
                </fieldset>
              </form>
            </div>
          ";
        }

        echo "
          <div class='untertabelle'>
            Artikelnummer gefunden in Katalog {$katalogeintraege[0]['terradatum'][0]}:";
        if( $detail ) {
          echo "<span class='button' id='anummer_an_knopf'
              onclick='anummer_on();' >Artikelnummer &auml;ndern...</span>";
        }
        echo "
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
        ";
  
        $terraeinheit = $katalogeintraege[0]["terraeinheit"][0];
        $netto = $katalogeintraege[0]["terranettopreisincents"][0] / 100.0;
        $mwst = $katalogeintraege[0]["terramwst"][0];
        $brutto = $netto * (1 + $mwst / 100.0 );
        $terragebindegroesse = $katalogeintraege[0]["terragebindegroesse"][0];
        $terrabnummer = $katalogeintraege[0]["terrabestellnummer"][0];
  
        echo "
            <tr>
              <td>{$katalogeintraege[0]['terrabestellnummer'][0]}</td>
              <td>{$katalogeintraege[0]['cn'][0]}</td>
              <td>$terraeinheit</td>
              <td>$terragebindegroesse</td>
              <td>{$katalogeintraege[0]['terraherkunft'][0]}</td>
              <td>{$katalogeintraege[0]['terraverband'][0]}</td>
              <td>$netto</td>
              <td>$mwst</td>
              <td>$brutto</td>
            </tr>
          </table>
        ";

        kanonische_einheit( $terraeinheit, &$kan_terraeinheit, &$kan_terramult );

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
        $newfc['gebindegroesse'] = ( ( $fcgebindegroesse > 0.001 ) ? $fcgebindegroesse : 1 );
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
            <form name='Preisform' method='post' action='$self'>
            $self_fields
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
            <form name='Preisform' method='post' action='$self'>
            $self_fields
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
      ";
      optionen_einheiten( $newfc['kan_verteileinheit'] );
      echo "
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
      ";
      optionen_einheiten( $newfc['kan_liefereinheit'] );
      echo "
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
    echo '</td></tr>';

  } // function do_artikel

?>

</body>

<script type="text/javascript">
  preishistorie = 1;
  function preishistorie_toggle() {
    preishistorie = ! preishistorie;
    if( preishistorie ) {
      document.getElementById("preishistorie").style.display = "block";
      document.getElementById("preishistorie_knopf").src = "img/close_black_trans.gif";
      document.getElementById("preishistorie_knopf").title = "Ausblenden";
    } else {
      document.getElementById("preishistorie").style.display = "none";
      document.getElementById("preishistorie_knopf").src = "img/open_black_trans.gif";
      document.getElementById("preishistorie_knopf").title = "Einblenden";
    }
  }
  function anummer_on() {
    document.getElementById("anummer_an_knopf").style.display = "none";
    document.getElementById("anummer_form").style.display = "block";
  }
  function anummer_off() {
    document.getElementById("anummer_an_knopf").style.display = "inline";
    document.getElementById("anummer_form").style.display = "none";
  }
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
      if( (verteilmult > 0) && (alt > 0) ) {
        gebindegroesse = parseInt( 0.499  + gebindegroesse * alt / verteilmult);
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
      if( (gebindegroesse > 0) && (alt > 0) ) {
        verteilmult = parseInt( 0.499 + verteilmult * alt / gebindegroesse );
        document.Preisform.newfcmult.value = verteilmult;
      }
    }
    preisberechnung_default();
  }

</script>




</html>
