<?php

// terraabgleich.php
//
// sucht in produktliste und preishistorie nach inkonsistenzen,
// und nach unterschieden zum Terra-katalog,
// macht ggf. verbesserungsvorschlaege und erlaubt aenderungen

  // Konfigurationsdatei einlesen
	include('code/config.php');
	
	// Funktionen zur Fehlerbehandlung laden
	include('code/err_functions.php');
	
	// Verbindung zur MySQL-Datenbank herstellen
	include('code/connect_MySQL.php');
	
	// egal ob get oder post verwendet wird...
	$HTTP_GET_VARS = array_merge($HTTP_GET_VARS, $HTTP_POST_VARS);

  // ggf. die area Variable einlesen, die festlegt in welchem Bereich man sich befindet
  if (isset($HTTP_GET_VARS['area'])) $area = $HTTP_GET_VARS['area'];

  include ('head.php');

  $mysqljetzt = date('Y') . '-' . date('m') . '-' . date('d') . ' ' . date('H') . ':' . date('i') . ':' . date('s');
  // echo "Hallo, Welt! in MySQL ist es jetzt: $mysqljetzt <br>";

  if (isset($HTTP_GET_VARS['produktid'])) {
    $produktid = $HTTP_GET_VARS['produktid'];
    $detail = TRUE;
  } else {
    $detail = FALSE;
  }

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
      ( $newfcgebindegroesse = $HTTP_GET_VARS['newfcgebindegroesse'] ) || error( __LINE__, __FILE__, "newfcgebindegroesse nicht gesetzt!" );
      ( $newfcpreis = $HTTP_GET_VARS['newfcpreis'] ) || error( __LINE__, __FILE__, "newfcpreis nicht gesetzt!" );
      ( $newfcname = $HTTP_GET_VARS['newfcname'] ) || error( __LINE__, __FILE__, "newfcname nicht gesetzt!" );
      if( ! ( $newfcnotiz = $HTTP_GET_VARS['newfcnotiz'] ) )
        $newfcnotiz = '';
      ( $newfcpfand = $HTTP_GET_VARS['newfcpfand'] ) || error( __LINE__, __FILE__, "newfcpfand nicht gesetzt!" );
      ( $newfcbnummer = $HTTP_GET_VARS['newfcbnummer'] ) || error( __LINE__, __FILE__, "newfcbnummer nicht gesetzt!" );
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
    
      $pr0 = TRUE;
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
            (produkt_id, preis, zeitstart, zeitende, bestellnummer, gebindegroesse, pfand)
            VALUES ($produktid,'$newfcpreis','$newfczeitstart', NULL, '$newfcbnummer', '$newfcgebindegroesse', '$newfcpfand')"
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

  ( $result = mysql_query( 'SELECT id FROM lieferanten WHERE name="Terra" ' ) )
    || error ( __LINE__, __FILE__, "Suche nach Lieferant Terra fehlgeschlagen" );

  ( $row = mysql_fetch_array($result) )
    || error ( __LINE__, __FILE__, "Lieferant Terra nicht gefunden" );

  $terraid = $row['id'];
  // echo 'Terra ID: ' . $terraid . '<br>';
  $is_terra = TRUE;

  $filter = 'lieferanten_id=' . $terraid;
  if( $detail ) {
    $filter = $filter . ' AND id=' . $produktid;
  }
  // echo 'filter: ' . $filter;
  ( $terraprodukte = mysql_query( 'SELECT * FROM produkte WHERE ' . $filter ) )
    || error ( __LINE__, __FILE__, "Suche nach Terraprodukten fehlgeschlagen" );
  // echo 'Produkte: ' . mysql_num_rows( $terraprodukte ) . '<br>';
  
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
  while ( ++$outerrow < 9999 && ( $artikel = mysql_fetch_array( $terraprodukte ) ) ) {
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

    echo "\n";
    echo '<tr id="row' . $outerrow . '">';
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
    echo '</th>';
    echo '<td class="outer" style="padding-bottom:1ex;">';


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
              <th>Preis</th>
              <th>Pfand</th>
            </tr>
      ';
      while( $pr1 = mysql_fetch_array($terrapreise) ) {
        echo '<tr>';
        echo '  <td>' . $pr1['id'] . '</td>';
        echo '  <td>' . $pr1['bestellnummer'] . '</td>';
        echo '  <td>' . $pr1['zeitstart'] . '</td>';
        echo '  <td>' . $pr1['zeitende'] . '</td>';
        echo '  <td> ' . $pr1['preis'] . '</td>';
        echo '  <td> ' . $pr1['pfand'] . '</td>';
        echo '</tr>';
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
    $fcgebindegroesse = NULL;
    $fcpreis = NULL;
    $fcpfand = NULL;
    $fcbnummer = NULL;
    if( $prgueltig ) {
      $fcgebindegroesse = $prgueltig['gebindegroesse'];
      $fcpreis = $prgueltig['preis'];
      $fcpfand = $prgueltig['pfand'];
      $fcbnummer = $prgueltig['bestellnummer'];
    }

    //
    // "kanonische" maszeinheit und maszzahl rausfinden, fuer katalogvergleich:
    //

    $can_fceinheit = NULL;
    $fcmult = NULL;
    sscanf( $fceinheit, "%f", &$fcmult );
    if( $fcmult ) {
      sscanf( $fceinheit, "%f%s", &$fcmult, &$fceinheit );
    } else {
      $fcmult = 1;
    }
    $fceinheit = substr( str_replace( ' ', '', strtolower($fceinheit) ), 0, 2);
    switch( $fceinheit ) {
      case 'kg':
        $can_fceinheit = 'g';
        $fcmult = 1000;
        break;
      case 'g':
      case 'gr':
        $can_fceinheit = 'g';
        break;
      case 'gl':
        $can_fceinheit = 'GL';
        break;
      case 'fl':
        $can_fceinheit = 'FL';
        break;
      case 'be':
        $can_fceinheit = 'BE';
        break;
      case 'bd':
        $can_fceinheit = 'BD';
        break;
      case 'l':
      case 'lt':
      case 'li':
        $can_fceinheit = 'L';
        break;
      case 'ea':
      case 'st':
      case '':
        $can_fceinheit = 'ST';
        break;
      default:
        $can_fceinheit = strtolower($fceinheit);
        echo "<div class='warn'>Foodsoft-Einheit unbekannt: $can_fceinheit </div>";
        break;
    }


    //
    // Artikeldaten aus foodsoft-Datenbank anzeigen:
    //

    echo '
      <div class="untertabelle" id="foodsoftdatenbank">Foodsoft-Datenbank:</div>
      <table width="100%">
        <tr>
          <th>B-Nr.</th>
          <th>Name</th>
          <th>Einheit</th>
          <th>Gebinde</th>
          <th>Preis</th>
          <th>Pfand</th>
        </tr>
        <tr>
    ';
 
    if( $prgueltig ) {
      echo "<td>$fcbnummer</td>";
    } else {
      echo '<td><div class="warn" style="text-align:center;">keine</div></td>';
    }

    echo "<td>$name</td>";
    echo "<td>$fcmult $can_fceinheit</td>";
    if( $prgueltig ) {
      echo "<td>$fcgebindegroesse</td>";
      echo "<td>$fcpreis</td>";
      echo "<td>$fcpfand</td>";
    } else {
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
      $terragebindegroesse = NULL;
      $terrabnummer = NULL;
      $can_terraeinheit = NULL;
    
      $filter = '(&(objectclass=terraartikel)(artikelnummer=' . $anummer . '))';
      // echo 'filter: ' . $filter;
      $katalogergebnis = ldap_search( $ldaphandle, $ldapbase, '(&(objectclass=terraartikel)(terraartikelnummer=' . $anummer . '))' );
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
        
        $terramult = 1;
        switch( strtolower( $terraeinheit ) ) {
          case 'kg':
            $can_terraeinheit = 'g';
            $terramult = 1000;
            break;
          case 'st':
            $can_terraeinheit = 'ST';
            break;
          case 'lt':
            $can_terraeinheit = 'L';
            break;
          case 'fl':
            $can_terraeinheit = 'FL';
            break;
          case 'gl':
            $can_terraeinheit = 'GL';
            break;
          case 'be':
            $can_terraeinheit = 'BE';
            break;
          case 'bd':
            $can_terraeinheit = 'BD';
            break;
          default:
            $can_terraeinheit = strtolower($terraeinheit);
            echo '<div class="warn">Terraeinheit unbekannt: ' . $can_terraeinheit . '</div>';
            break;
        }
        
        $neednewprice = FALSE;
        $newfceinheit = FALSE;
        $newfcmult = FALSE;
        $newfcgebindegroesse = FALSE;
        $newfcpreis = FALSE;
        $newfcbnummer = FALSE;
        
        if( $prgueltig ) {
          // echo "<br>Foodsoft: Einheit: $fcmult * $can_fceinheit Gebinde: $fcgebindegroesse";
          // echo "<br>Terra: Einheit: $terramult * $can_terraeinheit Gebinde: $terragebindegroesse";
  
          if( $can_terraeinheit != $can_fceinheit ) {
            $neednewprice = TRUE;
            echo "<div class='warn'>Problem: Einheiten stimmen nicht:
                        <p class='li'>Terra: <kbd>$can_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>$can_fceinheit</kbd></p></div>";
          } else {
            $newfceinheit = $can_fceinheit;
            $newfcmult = $fcmult;
            if( abs( $terramult * $terragebindegroesse - $fcmult * $fcgebindegroesse ) > 0.01 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Gebindegroessen stimmen nicht: 
                        <p class='li'>Terra: <kbd>$terragebindegroesse * $terramult $can_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>$fcgebindegroesse * $fcmult $can_fceinheit</kbd></p></div>";
            }
            if( abs( ($fcpreis - $fcpfand) * $terramult / $fcmult - $brutto ) > 0.01 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Preise stimmen nicht (beide Brutto ohne Pfand):
                        <p class='li'>Terra: <kbd>$brutto je $terramult $can_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>"
                          . ($fcpreis-$fcpfand) * $terramult / $fcmult
                          . " je $terramult $can_terraeinheit </kbd></p></div>";
            }
          }
          if( $terrabnummer != $fcbnummer ) {
            $neednewprice = TRUE;
            echo "<div class='warn'>Problem: Bestellnummern stimmen nicht:
                      <p class='li'>Terra: <kbd>$terrabnummer</kbd></p>
                      <p class='li'>Foodsoft: <kbd>$fcbnummer</kbd></p></div>";
          }
        } else {
          $neednewprice = TRUE;
        }

      }

    } // if( $is_terra ) { ... katalogvergleich ... }

    if( $detail ) {
    
      //
      // vorlage fuer neuen preiseintrag berechnen:
      //
    
      if( ! $newfceinheit )
        if( $is_terra && $can_terraeinheit )
          $newfceinheit = $can_terraeinheit;
        else
          $newfceinheit = $fceinheit;
          
      if( ! $newfcmult )
        if( $is_terra && $can_terraeinheit )
          $newfcmult = $terramult;
        else
          $newfcmult = 1;

      if( $is_terrra && $terragebindegroesse )
        $newfcgebindegroesse = $terragebindegroesse * $terramult / $newfcmult;
      else
        $newfcgebindegroesse = $fcgebindegroesse;
        
      if( $fcpfand ) {
        $newfcpfand = $fcpfand;
      } else {
        $newfcpfand = 0.00;
      }
      $newfcpreis = $brutto * $newfcmult / $terramult + $newfcpfand;
      $newfcnotiz = $fcnotiz;
      if( $is_terra && $terrabnummer )
        $newfcbnummer = $terrabnummer;

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
                <td><input type='text' size='40' name='newfcname' value='$name'></input>
                  &nbsp; Notiz: <input type='text' size='40' name='newfcnotiz' value='$fcnotiz'></input>
                </td>
              </tr>
              <tr>
                <td>Einheit:</td>
                <td>
                  <input type='text' size='4' name='newfcmult' value='$newfcmult'></input>
                  <input type='text' size='2' name='newfceinheit' value='$newfceinheit'></input>
                  &nbsp; Gebinde: <input type='text' size='6' name='newfcgebindegroesse' value='$newfcgebindegroesse'></input>
                  &nbsp; B-Nr: <input type='text' size='8' name='newfcbnummer' value='$newfcbnummer'></input>
                  &nbsp; Pfand: <input type='text' size='6' name='newfcpfand' value='$newfcpfand'></input>
                  &nbsp; Endpreis:
                    <input title='Preis incl. MWSt und Pfand' type='text' size='8' name='newfcpreis' value='$newfcpreis'></input>
                  &nbsp; ab: <input type='text' size='12' name='newfczeitstart' value='$mysqljetzt'></input>
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
