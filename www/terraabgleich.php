<?php

// terraabgleich.php (name ist historisch: nuetzlich auch fuer andere lieferanten!)
//
// sucht in produktliste und preishistorie nach inkonsistenzen,
// und nach unterschieden zum Terra-katalog,
// macht ggf. verbesserungsvorschlaege und erlaubt aenderungen

  // Konfigurationsdatei einlesen
	require_once('code/config.php');
	
	// Funktionen zur Fehlerbehandlung laden
	require_once('code/err_functions.php');
	
	// Verbindung zur MySQL-Datenbank herstellen
	require_once('code/connect_MySQL.php');
	
  // Verbindung zur MySQL-Datenbank herstellen
	require_once('code/zuordnen.php');
	
  require_once('code/login.php');
  //nur_fuer_dienst(4,5);
  
	// egal ob get oder post verwendet wird...
	$HTTP_GET_VARS = array_merge($HTTP_GET_VARS, $HTTP_POST_VARS);

  // ggf. die area Variable einlesen, die festlegt in welchem Bereich man sich befindet
  if (isset($HTTP_GET_VARS['area'])) $area = $HTTP_GET_VARS['area'];


  $mysqljetzt = date('Y') . '-' . date('m') . '-' . date('d') . ' ' . date('H') . ':' . date('i') . ':' . date('s');
  // echo "Hallo, Welt! in MySQL ist es jetzt: $mysqljetzt <br>";

  if (isset($HTTP_GET_VARS['produktid'])) {
    $produktid = $HTTP_GET_VARS['produktid'];
    $detail = TRUE;
  } else {
    $detail = FALSE;
  }

  $title = 'Produktdaten';
  $title = 'Produktdaten';
  if( $detail )
    $subtitle = $subtitle . " - Detailanzeige";
  require_once('windows/head.php');

  if( $detail ) {

    // eventuell uebergebene SQL-befehle befehl0, befehl1, ... abarbeiten:
    //
    $n=0;
    while( $b = $HTTP_GET_VARS[ 'befehl' . $n ] ) {
      // echo 'b: ' . $b . '<br>';
      $befehl = base64_decode( $b );
      // $befehl = "UPDATE produktpreise SET zeitende='2007-05-16 11:22:33' WHERE id=4707";
      ( $kommentar = $HTTP_GET_VARS[ 'kommentar' . $n ] ) || $kommentar = 'SQL-Befehl: ' . $befehl;
      ( $doit = $HTTP_GET_VARS[ 'doit' . $n ] ) || $doit = TRUE;
      if( $doit ) {
        // printf( ":%s:\n", "$kommentar");
        if( mysql_query( $befehl ) ) {
          // echo '<span class="ok"> OK </span><br>';
        } else {
          // echo ' <span class="warn"> FEHLGESCHLAGEN </span><br>';
        }
      }
      $n++;
    }
  
    // eventuell neuen preiseintrag vornehmen:
    //
  
    if( $HTTP_GET_VARS['neuerpreiseintrag'] ) {
      ( $newfcmult = $HTTP_GET_VARS['newfcmult'] ) || error( __LINE__, __FILE__, "newfcmult nicht gesetzt!" );
      ( $newfceinheit = $HTTP_GET_VARS['newfceinheit'] ) || error( __LINE__, __FILE__, "newfceinheit nicht gesetzt!" );
      ( $newliefermult = $HTTP_GET_VARS['newliefermult'] ) || error( __LINE__, __FILE__, "newliefermult nicht gesetzt!" );
      ( $newliefereinheit = $HTTP_GET_VARS['newliefereinheit'] ) || error( __LINE__, __FILE__, "newliefereinheit nicht gesetzt!" );
      ( $newfcgebindegroesse = $HTTP_GET_VARS['newfcgebindegroesse'] ) || error( __LINE__, __FILE__, "newfcgebindegroesse nicht gesetzt!" );
      ( $newfcpreis = $HTTP_GET_VARS['newfcpreis'] ) || error( __LINE__, __FILE__, "newfcpreis nicht gesetzt!" );
      ( $newfcname = $HTTP_GET_VARS['newfcname'] ) || error( __LINE__, __FILE__, "newfcname nicht gesetzt!" );
      if( ! ( $newfcnotiz = $HTTP_GET_VARS['newfcnotiz'] ) )
        $newfcnotiz = '';
      need_http_var('newfcmwst');
      need_http_var('newfcpfand');
      need_http_var('newfcbnummer');
      ( $newfczeitstart = $HTTP_GET_VARS['newfczeitstart'] ) || error( __LINE__, __FILE__, "newfczeitstart nicht gesetzt!" );
    
      ( $terraprodukt = mysql_query( "SELECT * FROM produkte WHERE id=$produktid" ) )
        || error ( __LINE__, __FILE__, "Suche nach Produkt fehlgeschlagen" );
    
      ( $terrapreise = mysql_query( "SELECT * FROM produktpreise WHERE produkt_id=$produktid ORDER BY zeitstart" ) )
        || error ( __LINE__, __FILE__, "Suche nach Produktpreisen fehlgeschlagen" );
      
      if( mysql_query( "UPDATE produkte SET einheit='$newfcmult $newfceinheit' WHERE id=$produktid" ) ) {
        // echo "<div class='ok'>neue Einheit: $newfcmult $newfceinheit</div>";
      } else {
        echo "<div class='ok'>FEHLGESCHLAGEN: neue Einheit: $newfcmult $newfceinheit</div>";
      }
      if( mysql_query( "UPDATE produkte SET name='$newfcname' WHERE id=$produktid" ) ) {
        // echo "<div class='ok'>neue Bezeichnung: $newfcname</div>";
      } else {
        echo "<div class='ok'>FEHLGESCHLAGEN: neue Bezeichnung: $newfcname</div>";
      }
      if( mysql_query( "UPDATE produkte SET notiz='$newfcnotiz' WHERE id=$produktid" ) ) {
        // echo "<div class='ok'>neue Notiz: $newfcnotiz</div>";
      } else {
        echo "<div class='ok'>FEHLGESCHLAGEN: neue Notiz: $newfcnotiz</div>";
      }
    
      $pr0 = false;
      while( $pr1 = mysql_fetch_array($terrapreise) ) {
        $pr0 = $pr1;
      }
      if( $pr0 ) {
        if( mysql_query( "UPDATE produktpreise SET zeitende='$newfczeitstart' WHERE id=" . $pr0['id'] ) ) {
          // echo "<div class='ok'>letzter Preiseintrag ausgelaufen ab: $newfczeitstart</div>";
        } else {
          echo "<div class='ok'>FEHLGESCHLAGEN: konnte letzten Preiseintrag nicht abschliessen</div>";
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
            , liefereinheit,
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
        echo "<div class='ok'>neuer Preiseintrag FEHLGESCHLAGEN</div>";
      }
    }

    // eventuell neue Artikelnummer setzen:
    //
    
    if( ( $anummer = $HTTP_GET_VARS['anummer'] ) ) {
      // echo 'Update:<br>';
      // echo 'produktid: ' . $produktid . '<br>';
      // echo 'neue Artikelnummer: ' . $anummer . '<br>';
      if ( mysql_query( 'UPDATE produkte SET artikelnummer=' . $anummer . ' WHERE id=' . $produktid ) ) {
        // echo "OK!<br>";
      } else {
        // echo "fehlgeschlagen!<br>";
      }
    }
  }

  if( $detail ) {
    $result = mysql_query( "SELECT * FROM produkte WHERE id='$produktid'" )
      or error ( __LINE__, __FILE__, "Suche nach Produkt fehlgeschlagen" );
    $row = mysql_fetch_array($result)
      or error ( __LINE__, __FILE__, "Produkt nicht gefunden" );
    $lieferanten_id = $row['lieferanten_id'];
  } else {
    need_http_var( 'lieferanten_id' );
    get_http_var( 'order_by' );
    if( $order_by )
      $order_by = ' ORDER BY ' . $order_by;
  }
    

  $result = mysql_query( "SELECT * FROM lieferanten WHERE id='$lieferanten_id'" )
    or error ( __LINE__, __FILE__, "Suche nach Lieferant fehlgeschlagen" );

  $row = mysql_fetch_array($result)
    or error ( __LINE__, __FILE__, "Lieferant nicht gefunden" );

  $is_terra = ( $row['name'] == 'Terra' );

  $filter = 'lieferanten_id=' . $lieferanten_id;
  if( $detail ) {
    $filter = $filter . ' AND id=' . $produktid;
  }
  // echo 'filter: ' . $filter;
  ( $produkte = mysql_query( 'SELECT * FROM produkte WHERE ' . $filter . $order_by ) )
    || error ( __LINE__, __FILE__, "Suche nach Produkten fehlgeschlagen" );
  
  // echo "<br>connecting... ";
  $ldaphandle = ldap_connect( $ldapuri );
  // echo " result is: " . $ldaphandle  . " <br>";

  // echo "<br>setting protocol version 3...";
  $rv = ldap_set_option( $ldaphandle, LDAP_OPT_PROTOCOL_VERSION, 3 );
  // echo " result is: " . $rv  . " <br>";

  // echo "<br>binding to server...";
  $rv = ldap_bind( $ldaphandle );
  // echo " result is: " . $rv  . " <br>";

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
  function mysql_repair_link( $befehl, $kommentar, $domid = '' ) {
    global $produktid;
    echo '<div class="warn" style="padding-left:2em;">';
    echo "  <form method='post' action='terraabgleich.php?produktid=$produktid'>";
    echo "    <input type='hidden' name='produktid' value='$produktid'></input>";
    echo '    <input type="hidden" name="befehl0" value="' . base64_encode( $befehl ) . '"></input>';
    echo '    <input type="submit" name="submit" value="' . $kommentar . '"';
    if( $domid != '' ) {
      echo "    onclick=\"document.getElementById('$domid').className='modified';\"";
    }
    echo '    ></input>';
    echo '  </form>';
    echo '</div>';
  }


  // do_artikel
  // wird aus der hauptschleife aufgerufen, um einen artikel aus der Produktliste anzuzeigen
  //
  function do_artikel() {
    global $outerrow, $ldaphandle, $ldapbase, $artikel, $detail, $mysqljetzt, $is_terra;

    echo "\n<tr id='row$outerrow'>";
    $anummer = $artikel['artikelnummer'];
    $name = $artikel['name'];
    $produktid = $artikel['id'];
    $fcnotiz = $artikel['notiz'];
    $fceinheit = $artikel['einheit'];


    echo '<th class="outer" style="vertical-align:top;">';
    if( ! $detail ) {
      echo "<a class='blocklink' href='terraabgleich.php?produktid=$produktid' target='_new' title='Details...'";
      echo "onclick=\"document.getElementById('row$outerrow').className='modified';\">";
    }
    echo "$anummer<br>id:&nbsp;$produktid";
    if( ! $detail ) {
      echo '</a>';
    }
    echo '</th><td class="outer" style="padding-bottom:1ex;">';


    //
    // produktpreise abfragen und (ggf.) anzeigen:
    //
    ( $terrapreise = mysql_query(
      'SELECT * FROM produktpreise WHERE produkt_id=' . $produktid . ' ORDER BY produkt_id,zeitstart' ) )
      || error ( __LINE__, __FILE__, "Suche nach Produktpreisen fehlgeschlagen" );

    if( $detail ) {
      echo '
        <div class="untertabelle">
          <img id="preishistorie_knopf" class="button" src="img/close_black_trans.gif"
            onclick="preishistorie_toggle();" title="Ausblenden">
          </img>
          Preis-Historie:
        </div>
        <div id="preishistorie">
          <table width="100%">
            <tr>
              <th>id</th>
              <th>B-Nr</th>
              <th>von</th>
              <th>bis</th>
              <th title="Liefer-Einheit: fuers Bestellen beim Lieferanten">L-Einheit</th>
              <th>MWSt</th>
              <th title="Pfand je V-Einheit">Pfand</th>
              <th title="Endpreis je V-Einheit">Preis</th>
            </tr>
      ';
      while( $pr1 = mysql_fetch_array($terrapreise) ) {
        echo "
          <tr>
            <td>{$pr1['id']}</td>
            <td>{$pr1['bestellnummer']}</td>
            <td>{$pr1['zeitstart']}</td>
            <td>{$pr1['zeitende']}</td>
            <td>{$pr1['liefereinheit']}</td>
            <td>{$pr1['mwst']}</td>
            <td>{$pr1['pfand']}</td>
            <td>{$pr1['preis']}</td>
          </tr>
        ";
      }
     if( mysql_num_rows( $terrapreise ) > 0 ) {
        mysql_data_seek( $terrapreise, 0 );
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
    while( $pr1 = mysql_fetch_array($terrapreise) ) {
      if( $pr0 ) {
        if ( $pr0['zeitende'] == '' ) {
          echo '<div class="warn">FEHLER: Preisintervall ' . $pr0['id'] . ' nicht aktuell aber nicht abgeschlossen.</div>';
          $detail && mysql_repair_link(
            'UPDATE produktpreise SET zeitende="' . $pr1['zeitstart'] . '" WHERE id=' . $pr0['id'] 
          , 'Zeitende in ' . $pr0['id'] . ' auf ' . $pr1['zeitstart'] . ' setzen'
          , 'row' . $outerrow
          );
        } else if ( $pr0['zeitende'] > $pr1['zeitstart'] ) {
          echo '<div class="warn">FEHLER: Ueberlapp in Preishistorie: ' . $pr0['id'] . ' und ' . $pr1['id'] . '.</div>';
          $detail && mysql_repair_link(
            'UPDATE produktpreise SET zeitende="' . $pr1['zeitstart'] . '" WHERE id=' . $pr0['id'] 
          , 'Zeitende in ' . $pr0['id'] . ' auf ' . $pr1['zeitstart'] . ' setzen'
          , 'row' . $outerrow
          );
        }
      }
      $pr0 = $pr1;
    }
    if( ! $pr0 ) {
      echo '<div class="warn">WARNUNG: kein Preiseintrag fuer diesen Artikel vorhanden!</div><br>';
    } else if ( $pr0['zeitende'] != '' ) {
      if ( $pr0['zeitende'] < mysqljetzt ) {
        echo '<div class="warn">WARNUNG: kein aktuell gueltiger Preiseintrag fuer diesen Artikel vorhanden!</div><br>';
        // echo '&nbsp; letzter eintrag: ab: '. $pr0['zeitstart'] . ' bis: ' . $pr0['zeitende'] . ' preis: ' . $pr0['preis'] . '<br>';
      } else {
        echo '<div class="warn">WARNUNG: aktueller Preis laeuft aus!</div><br>';
        // echo '&nbsp; letzter eintrag: ab: '. $pr0['zeitstart'] . ' bis: ' . $pr0['zeitende'] . ' preis: ' . $pr0['preis'] . '<br>';
        $prgueltig = $pr0;  // kann man noch zulassen...
      }
    } else {
      $prgueltig = $pr0;
    }
    if( $prgueltig ) {
      if( $prgueltig['liefereinheit'] == '' ) {
        echo "<div class='warn'>FEHLER: keine gueltige Liefereinheit</div>";
        $detail && mysql_repair_link(
          "UPDATE produktpreise SET liefereinheit='$fceinheit' WHERE id={$prgueltig['id']}" 
        , "L-Einheit in {$prgueltig['id']} auf $fceinheit setzen"
        , "row$outerrow"
        );
      }
      if( $prgueltig['verteileinheit'] != "fceinheit" ) {
        echo "<div class='warn'>FEHLER: V-Einheit in Preishistorie anders als in Produktdatenbank</div>";
        $detail && mysql_repair_link(
          "UPDATE produktpreise SET verteileinheit='$fceinheit' WHERE id={$prgueltig['id']}" 
        , "V-Einheit in {$prgueltig['id']} auf $fceinheit setzen"
        , "row$outerrow"
        );
      }
    }
    $fcgebindegroesse = NULL;
    $fcpreis = NULL;
    $fcpfand = NULL;
    $fcbnummer = NULL;
    $fcliefereinheit = NULL;
    $fcmwst = NULL;
    if( $prgueltig ) {
      $fcgebindegroesse = $prgueltig['gebindegroesse'];
      $fcpreis = $prgueltig['preis'];
      $fcpfand = $prgueltig['pfand'];
      $fcbnummer = $prgueltig['bestellnummer'];
      $fcliefereinheit = $prgueltig['liefereinheit'];
      $fcmwst = $prgueltig['mwst'];
    }

    kanonische_einheit( $fceinheit, &$can_fceinheit, &$can_fcmult );

    kanonische_einheit( $fcliefereinheit, &$can_liefereinheit, &$can_liefermult );


    //
    // Artikeldaten aus foodsoft-Datenbank anzeigen:
    //

    echo '
      <div class="untertabelle" id="foodsoftdatenbank">Foodsoft-Datenbank:</div>
      <table width="100%">
        <tr>
          <th>B-Nr.</th>
          <th>Name</th>
          <th title="Liefer-Einheit: fuers Bestellen beim Lieferanten">L-Einheit</th>
          <th title="Verteil-Einheit: fuers Bestellen und Verteilen bei uns">V-Einheit</th>
          <th title="V-Einheiten pro Gebinde">Gebinde</th>
          <th title="MWSt in Prozent">MWSt</th>
          <th title="Pfand je V-Einheit">Pfand</th>
          <th title="Endpreis je V-Einheit">Preis</th>
        </tr>
        <tr>
    ';
 
    if( $prgueltig ) {
      echo "<td>$fcbnummer</td>";
    } else {
      echo '<td><div class="warn" style="text-align:center;">keine</div></td>';
    }

    echo "<td>$name</td>";
    echo "<td>$can_liefermult $can_liefereinheit</td>";
    echo "<td>$can_fcmult $can_fceinheit</td>";
    if( $prgueltig ) {
      echo "<td>$fcgebindegroesse</td>";
      echo "<td>$fcmwst</td>";
      echo "<td>$fcpfand</td>";
      echo "<td>$fcpreis</td>";
    } else {
      echo '<td><div class="warn" style="text-align:center;">-</div></td>';
      echo '<td><div class="warn" style="text-align:center;">-</div></td>';
      echo '<td><div class="warn" style="text-align:center;">-</div></td>';
      echo '<td><div class="warn" style="text-align:center;">-</div></td>';
    }

    echo '</tr></table>';

    // flag: neuen preiseintrag vorschlagen (falls gar keiner oder fehlerhaft):
    //
    $neednewprice = FALSE;

    // flag: suche nach artikelnummer vorschlagen (falls kein Treffer bei Katalogsuche):
    //
    $neednewarticlenumber = FALSE;

    //
    // Artikeldaten aus Katalog suchen und ggf anzeigen:
    //
    if( $is_terra ) {

      $brutto = NULL;
      $mwst = NULL;
      $terragebindegroesse = NULL;
      $terrabnummer = NULL;
      $can_terraeinheit = NULL;
      $can_terramult = NULL;
    
      $katalogergebnis = ldap_search( $ldaphandle, $ldapbase, "(&(objectclass=terraartikel)(terraartikelnummer=$anummer))" );
      $katalogeintraege = ldap_get_entries( $ldaphandle, $katalogergebnis );
  
      $anummer_form = "
        <table>
          <tr>
            <td>
              neue Artikel-Nr. setzen:
            </td>
            <td><form action='terraabgleich.php?produktid=$produktid' method='post'><input type='text' size='20' name='anummer' value='$anummer'></input>&nbsp;<input type='submit' name='Submit' value='OK'></input>
              </form>
            </td>
          </tr>
          <tr>
            <td>
              ...oder: Katalogsuche nach:
            </td>
            <td><form action='artikelsuche.php' method='post'><input name='terracn' value='$name' size='40'></input>&nbsp;<input type='submit' name='submit' value='Los!'
                 onclick='document.getElementById(\"row$outerrow\").className=\"modified\";'></input>
                <input type='hidden' name='produktid' value='$produktid'></input>
                <input type='hidden' name='produktname' value='$name'></input>
              </form>
            </td>
          </tr>
        </table>
      ";

      if( ( ! $katalogeintraege ) || ( $katalogeintraege['count'] < 1 ) ) {
  
        echo '<div class="warn">Katalogsuche: Artikelnummer nicht gefunden!</div>';
        if( $detail ) {
          echo "
            <div id='anummer_form' class='small_form'>
              <form>
                <fieldset>
                  <legend>
                    Artikelnummer aendern:
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
                    Artikelnummer aendern:
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
              onclick='anummer_on();' >Artikelnummer aendern...</span>";
        }
        echo "
          </div>

          <table width='100%'>
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
  
        echo '<tr>';
        echo "  <td>" . $katalogeintraege[0]["terrabestellnummer"][0] . "</td>";
        echo "  <td>" . $katalogeintraege[0]["cn"][0] . "</td>";
        echo "  <td>" . $terraeinheit . "</td>";
        echo "  <td>" . $terragebindegroesse . "</td>";
        echo "  <td>" . $katalogeintraege[0]["terraherkunft"][0] . "</td>";
        echo "  <td>" . $katalogeintraege[0]["terraverband"][0] . "</td>";
        echo "  <td>" . $netto . "</td>";
        echo "  <td>" . $mwst . "</td>";
        echo "  <td>" . $brutto . "</td>";
        echo "</tr>";
        echo "</table>";
        
        kanonische_einheit( $terraeinheit, &$can_terraeinheit, &$can_terramult );
        
        $neednewprice = FALSE;
        $newfceinheit = FALSE;
        $newfcmult = FALSE;
        $newfcgebindegroesse = FALSE;
        $newfcpreis = FALSE;
        $newfcbnummer = FALSE;
        $newliefereinheit = FALSE;
        $newliefermult = FALSE;
        $newfcmwst = FALSE;
        
        if( $prgueltig ) {
          // echo "<br>Foodsoft: Einheit: $can_fcmult * $can_fceinheit Gebinde: $fcgebindegroesse";
          // echo "<br>Terra: Einheit: $can_terramult * $can_terraeinheit Gebinde: $terragebindegroesse";
  
          if( ( $can_terraeinheit != $can_liefereinheit ) || ( $can_terramult * $terragebindegroesse != $can_liefermult) ) {
            $neednewprice = TRUE;
            echo "<div class='warn'>Problem: L-Einheit stimmt nicht:
                        <p class='li'>Terra: <kbd>$terragebindegroesse * $can_terramult $can_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>$can_liefermult $can_liefereinheit</kbd></p></div>";
            // vorschlag fuer aenderung:
            $newliefermult = $can_terramult * $terragebindegroesse;
            $newliefereinheit = $can_terraeinheit;
          }
          if( $can_liefereinheit != $can_fceinheit ) {
            if( $can_liefereinheit == 'KI' && $can_terraeinheit='ST' ) {
              // spezialfall: KIste mit vielen STueck inhalt ist ok!
            } else {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Einheit inkompatibel:
                          <p class='li'>Lieferant: <kbd>$can_liefereinheit</kbd></p>
                          <p class='li'>Verteilung: <kbd>$can_fceinheit</kbd></p></div>";
              if( $newliefereinheit ) {
                $newfcmult = $newliefermult;
                $newfceinheit = $newliefereinheit;
              } else {
                $newfcmult = $can_liefermult;
                $newfceinheit = $can_liefereinheit;
              }
              if( $fcgebindegroesse > 1 ) {
                $newfcmult = $newfcmult / $fcgebindegroesse;
              }
            }
          } else {
            if( abs( $can_terramult * $terragebindegroesse - $can_fcmult * $fcgebindegroesse ) > 0.01 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Gebindegroessen stimmen nicht: 
                        <p class='li'>Terra: <kbd>$terragebindegroesse * $can_terramult $can_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>$fcgebindegroesse * $can_fcmult $can_fceinheit</kbd></p></div>";
              $newfcgebindegroesse = $terragebindegroesse * $can_terramult / $can_fcmult;
            }
            if( abs( ($fcpreis - $fcpfand) * $can_terramult / $can_fcmult - $brutto ) > 0.01 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Preise stimmen nicht (beide Brutto ohne Pfand):
                        <p class='li'>Terra: <kbd>$brutto je $terramult $can_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>"
                          . ($fcpreis-$fcpfand) * $terramult / $can_fcmult
                          . " je $terramult $can_terraeinheit </kbd></p></div>";
              $newfcpreis = $brutto / $can_terramult * $can_fcmult + $fcpfand;
            }
            if( abs( $fcmwst - $mwst ) > 0.005 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: MWSt-Satz stimmt nicht:
                        <p class='li'>Terra: <kbd>$mwst</kbd></p>
                        <p class='li'>Foodsoft: <kbd>$fcmwst</kbd></p></div>";
              $newfcmwst = $mwst;
            }
          }
          if( $terrabnummer != $fcbnummer ) {
            $neednewprice = TRUE;
            echo "<div class='warn'>Problem: Bestellnummern stimmen nicht:
                      <p class='li'>Terra: <kbd>$terrabnummer</kbd></p>
                      <p class='li'>Foodsoft: <kbd>$fcbnummer</kbd></p></div>";
            $newfcbnummer = $terrabnummer;
          }
          // echo "<br>Verteil: new: Einheit: $newfcmult * $newfceinheit Gebinde: $newfcgebindegroesse";
          // echo "<br>Liefer: new: Einheit: $newliefermult * $newliefereinheit";
        } else {
          $neednewprice = TRUE;
          $newliefermult = $can_terramult;
          $newliefereinheit = $can_terraeinheit;
          $newfcpreis = $brutto / $can_terramult * $can_fcmult + $fcpfand;
          $newfcmwst = $mwst;
          $newfcbnummer = $terrabnummer;
          $newfcgebindegroesse = $terragebindegroesse;
        }

      }

    } // if( $is_terra ) { ... katalogvergleich ... }

    if( $detail ) {

      //
      // vorlage fuer neuen preiseintrag berechnen:
      //

      if( ! $newfcgebindegroesse ) {
        $newfcgebindegroesse = $fcgebindegroesse;
      }

      if( ! $newfceinheit ) {
        $newfceinheit = $can_fceinheit;
        $newfcmult = $can_fcmult;
      }
      if( ! $newliefereinheit ) {
        $newliefereinheit = $can_fceinheit;
        $newliefermult = $can_fcmult;
        if( $is_terra ) {
          $newliefermult *= $newfcgebindegroesse;
        }
      }


      if( ! $newfcmwst ) {
        $newfcmwst = $fcmwst;
      }

      if( $fcpfand ) {
        $newfcpfand = $fcpfand;
      } else {
        $newfcpfand = 0.00;
      }

      if( ! $newfcpreis ) {
        $newfcpreis = $fcpreis;
      }

      $newfcnotiz = $fcnotiz;

      if( ! $newfcbnummer ) {
        $newfcbnummer = $fcbnummer;
      }

      if( $neednewprice ) {
        echo "
          <div style='padding:1ex;' id='preiseintrag_form' class='small_form'>
            <form method='post' action='terraabgleich.php?produktid=$produktid'>
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
            <form method='post' action='terraabgleich.php?produktid=$produktid'>
            <fieldset>
              <legend>
                <img class='button' title='Ausblenden' src='img/close_black_trans.gif'
                 onclick='preiseintrag_off();'></img> Neuer Preiseintrag:</legend>
        ";
      }

      echo "
            <table>
              <tr>
                <td>Name:</td>
                <td><input type='text' size='42' name='newfcname' value='$name'
                 title='Produktbezeichnung; bei abgepackten Sachen bitte auch die Menge angeben!'></input>
                  &nbsp; Notiz: <input type='text' size='42' name='newfcnotiz' value='$fcnotiz'></input>
                </td>
              </tr>
              <tr>
                <td>Verteil-Einheit:</td>
                <td>
                  <input type='text' size='4' name='newfcmult' value='$newfcmult'
                   title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'></input>
                  <input type='text' size='2' name='newfceinheit' value='$newfceinheit'
                   title='Einheit, z.B. g, Be, Gl, Bd. Bei Terra moeglichst Vorschlag uebernehmen!'></input>
                  &nbsp; Gebinde:
                    <input type='text' size='4' name='newfcgebindegroesse' value='$newfcgebindegroesse'
                     title='Gebindegroesse in ganzen Vielfachen der V-Einheit'></input>
                </td>
              </tr>
              <tr>
                <td>Liefer-Einheit:</td>
                <td>
                  <input type='text' size='4' name='newliefermult' value='$newliefermult'
                   title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'></input>
                  <input type='text' size='2' name='newliefereinheit' value='$newliefereinheit'
                   title='Einheit, z.B. g, Be, Gl, Bd. Bei Terra moeglichst Vorschlag uebernehmen!'></input>
                  &nbsp; Bestell-Nr: <input type='text' size='8' name='newfcbnummer' value='$newfcbnummer'
                   title='Bestellnummer (die, die sich bei Terra staendig aendert!)'></input>
                </td>
              </tr>
              <tr>
                <td>MWSt:</td>
                <td>
                  <input type='text' size='4' name='newfcmwst' value='$newfcmwst'
                   title='MWSt-Satz in Prozent'></input>
                  &nbsp; Pfand: <input type='text' size='4' name='newfcpfand' value='$newfcpfand'
                   title='Pfand pro V-Einheit, bei uns immer 0.00 oder 0.16'></input>
                  &nbsp; Endpreis:
                    <input title='Preis incl. MWSt und Pfand' type='text' size='8' name='newfcpreis' value='$newfcpreis'></input>
                  &nbsp; ab: <input type='text' size='18' name='newfczeitstart' value='$mysqljetzt'></input>
                  &nbsp; <input type='submit' name='submit' value='OK'
                          onclick=\"document.getElementById('row$outerrow').className='modified';\";
                  ></input>
                </td>
              </tr>
            </table>
          </fieldset>
          <input type='hidden' name='neuerpreiseintrag' value='1'>
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
  
</script>




</html>
