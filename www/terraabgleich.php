


<?php

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
  echo 'Hallo, Welt! in MySQL ist es jetzt: ' . $mysqljetzt . '<br>';

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
      echo 'b: ' . $b . '<br>';
      $befehl = base64_decode( $b );
      // $befehl = "UPDATE produktpreise SET zeitende='2007-05-16 11:22:33' WHERE id=4707";
      ( $kommentar = $HTTP_GET_VARS[ 'kommentar' . $n ] ) || $kommentar = 'SQL-Befehl: ' . $befehl;
      ( $doit = $HTTP_GET_VARS[ 'doit' . $n ] ) || $doit = TRUE;
      if( $doit ) {
        printf( ":%s:\n", "$kommentar");
        if( mysql_query( $befehl ) ) {
          echo '<span class="ok"> OK </span><br>';
        } else {
          echo ' <span class="warn"> FEHLGESCHLAGEN </span><br>';
        }
      }
      $n++;
    }
  
    // eventuell neuen preiseintrag abarbeiten:
    //
  
    if( $HTTP_GET_VARS['neuerpreiseintrag'] ) {
      ( $newfcmult = $HTTP_GET_VARS['newfcmult'] ) || error( __LINE__, __FILE__, "newfcmult nicht gesetzt!" );
      ( $newfceinheit = $HTTP_GET_VARS['newfceinheit'] ) || error( __LINE__, __FILE__, "newfceinheit nicht gesetzt!" );
      ( $newfcgebindegroesse = $HTTP_GET_VARS['newfcgebindegroesse'] ) || error( __LINE__, __FILE__, "newfcgebindegroesse nicht gesetzt!" );
      ( $newfcpreis = $HTTP_GET_VARS['newfcpreis'] ) || error( __LINE__, __FILE__, "newfcpreis nicht gesetzt!" );
      ( $newfcname = $HTTP_GET_VARS['newfcname'] ) || error( __LINE__, __FILE__, "newfcname nicht gesetzt!" );
      ( $newfcbnummer = $HTTP_GET_VARS['newfcbnummer'] ) || error( __LINE__, __FILE__, "newfcbnummer nicht gesetzt!" );
      ( $newfczeitstart = $HTTP_GET_VARS['newfczeitstart'] ) || error( __LINE__, __FILE__, "newfczeitstart nicht gesetzt!" );
    
      ( $terraprodukt = mysql_query( "SELECT * FROM produkte WHERE id=$produktid" ) )
        || error ( __LINE__, __FILE__, "Suche nach Produkt fehlgeschlagen" );
    
      ( $terrapreise = mysql_query( "SELECT * FROM produktpreise WHERE produkt_id=$produktid ORDER BY zeitstart" ) )
        || error ( __LINE__, __FILE__, "Suche nach Produktpreisen fehlgeschlagen" );
      
      if( mysql_query( "UPDATE produkte SET einheit='$newfcmult $newfceinheit' WHERE id=$produktid" ) ) {
        echo "<div class='ok'>neue Einheit: $newfcmult $newfceinheit</div>";
      } else {
        echo "<div class='ok'>FEHLGESCHLAGEN: neue Einheit: $newfcmult $newfceinheit</div>";
      }
      if( mysql_query( "UPDATE produkte SET name='$newfcname' WHERE id=$produktid" ) ) {
        echo "<div class='ok'>neue Bezeichnung: $newfcname</div>";
      } else {
        echo "<div class='ok'>FEHLGESCHLAGEN: neue Bezeichnung: $newfcname</div>";
      }
    
      $pr0 = TRUE;
      while( $pr1 = mysql_fetch_array($terrapreise) ) {
        $pr0 = $pr1;
      }
      if( $pr0 ) {
        if( mysql_query( "UPDATE produktpreise SET zeitende='$newfczeitstart' WHERE id=" . $pr0['id'] ) ) {
          echo "<div class='ok'>letzter Preiseintrag ausgelaufen ab: $newfczeitstart</div>";
        } else {
          echo "<div class='ok'>FEHLGESCHLAGEN: konnte letzten Preiseintrag nicht abschliessen</div>";
        }
      }
      if( mysql_query( "INSERT INTO produktpreise (produkt_id, preis, zeitstart, zeitende, bestellnummer, gebindegroesse) VALUES ($produktid,'$newfcpreis','$newfczeitstart', NULL, '$newfcbnummer', '$newfcgebindegroesse')" ) ) {
        echo "<div class='ok'>neuer Preiseintrag gespreichert</div>";
      } else {
        echo "<div class='ok'>neuer Preiseintrag FEHLGESCHLAGEN</div>";
      }
    }

  }

  ( $result = mysql_query( 'SELECT id FROM lieferanten WHERE name="Terra" ' ) )
    || error ( __LINE__, __FILE__, "Suche nach Lieferant Terra fehlgeschlagen" );

  ( $row = mysql_fetch_array($result) )
    || error ( __LINE__, __FILE__, "Lieferant Terra nicht gefunden" );

  $terraid = $row['id'];
  echo 'Terra ID: ' . $terraid . '<br>';
  $is_terra = TRUE;

  $filter = 'lieferanten_id=' . $terraid;
  if( $detail ) {
    $filter = $filter . ' AND id=' . $produktid;
  }
  echo 'filter: ' . $filter;
  ( $terraprodukte = mysql_query( 'SELECT * FROM produkte WHERE ' . $filter ) )
    || error ( __LINE__, __FILE__, "Suche nach Terraprodukten fehlgeschlagen" );
  echo 'Produkte: ' . mysql_num_rows( $terraprodukte ) . '<br>';
  
  echo "<br>connecting... ";
  $ldaphandle = ldap_connect( $ldapuri );
  echo " result is: " . $ldaphandle  . " <br>";

  echo "<br>setting protocol version 3...";
  $rv = ldap_set_option( $ldaphandle, LDAP_OPT_PROTOCOL_VERSION, 3 );
  echo " result is: " . $rv  . " <br>";

  echo "<br>binding to server...";
  $rv = ldap_bind( $ldaphandle );
  echo " result is: " . $rv  . " <br>";

  echo '
    <table width="100%">
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


  function mysql_repair_link( $befehl, $kommentar, $domid = '' ) {
    global $produktid;
    echo '<div class="warn" style="padding-left:2em;">';
    echo '  <form method="post" action="terraabgleich.php?produktid=$produktid">';
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
  
  function do_artikel() {
    global $outerrow, $ldaphandle, $ldapbase, $artikel, $detail, $mysqljetzt, $is_terra;

    echo "\n";
    echo '<tr id="row' . $outerrow . '">';
    $anummer = $artikel['artikelnummer'];
    $name = $artikel['name'];
    $produktid = $artikel['id'];
    $fceinheit = $artikel['einheit'];


    echo '<th class="outer" style="vertical-align:top;">';
    if( ! $detail ) {
      echo '<a class="blocklink" href="terraabgleich.php?produktid=' . $produktid . '" target="_new" ';
      echo "onclick=\"document.getElementById('row$outerrow').className='modified';\"";
    }
    echo $anummer . '<br>id:&nbsp;' . $produktid;
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
        <table width="100%" id="preishistorie">
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
      echo '</table>';
    }

    // produktpreise: test auf konsistenz:
    //  - alle intervalle bis auf das letzte muessen abgeschlossen sein
    //  - intervalle duerfen nicht ueberlappen
    //  - warnen, wenn kein aktuell gueltiger preis vorhanden
    //
    $pr0 = FALSE;
    $prgueltig = FALSE;
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
    // "kanonische" masseinheit und masszahl rausfinden, fuer katalogvergleich:
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
      case 'be':
        $can_fceinheit = 'BE';
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
        echo "<div class='warn'>Foodsoft Einheit unbekannt: $can_fceinheit </div>";
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


    //
    // Artikeldaten aus Katalog suchen und ggf anzeigen:
    //

    if( $is_terra ) {
    
      $filter = '(&(objectclass=terraartikel)(artikelnummer=' . $anummer . '))';
      // echo 'filter: ' . $filter;
      $katalogergebnis = ldap_search( $ldaphandle, $ldapbase, '(&(objectclass=terraartikel)(terraartikelnummer=' . $anummer . '))' );
      $katalogeintraege = ldap_get_entries( $ldaphandle, $katalogergebnis );
  
      if( ( ! $katalogeintraege ) || ( $katalogeintraege['count'] < 1 ) ) {
  
        echo '<div class="warn">Katalogsuche: Artikelnummer nicht gefunden!</div>';
        if( $detail ) {
          ?>
            <div class="warn">
              <form action="artikelsuche.php" method="post" target="_new">
                <input type="hidden" name="produktid" value="<?php echo $produktid; ?>"></input>
                <input type="hidden" name="produktname" value="<?php echo $name; ?>"></input>
                Katalogsuche nach Name:
                <input name="terracn" value="<?php echo $name; ?>" size="40"></input>
                <input type="submit" name="submit" value="Los!"
                 onclick="document.getElementById('row<?php echo $outerrow; ?>').className='modified';"></input>
              </form>
            </div>
    
          <?php
      ///// document.getElementById(theid).className="modified";
        }
  
      } else {
  
        ?>
  
          <div class="untertabelle">Artikelnummer gefunden in Katalog <?php echo $katalogeintraege[0]["terradatum"][0]; ?>:</div>
  
          <table width="100%">
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
  
        <?php
  
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
        
        switch( strtolower( $terraeinheit ) ) {
          case 'kg':
            $can_terraeinheit = 'g';
            $terramult = 1000;
            // $can_terragebindegroesse = 1000 * $terragebindegroesse;
            break;
          case 'st':
            $can_terraeinheit = 'ST';
            $terramult = 1;
            // $can_terragebindegroesse = $terragebindegroesse;
            break;
          case 'lt':
            $can_terraeinheit = 'L';
            $terramult = 1;
            // $can_terragebindegroesse = $terragebindegroesse;
            break;
          case 'be':
            $can_terraeinheit = 'BE';
            $terramult = 1;
            break;
          case 'gl':
            $can_terraeinheit = 'GL';
            $terramult = 1;
            break;
          default:
            $can_terraeinheit = strtolower($terraeinheit);
            $terramult = 1;
            // $can_terragebindegroesse = $terragebindegroesse;
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
            if( abs( $fcpreis * $terramult / $fcmult - $brutto ) > 0.01 ) {
              $neednewprice = TRUE;
              echo "<div class='warn'>Problem: Preise stimmen nicht:
                        <p class='li'>Terra: <kbd>$brutto je $terramult $can_terraeinheit</kbd></p>
                        <p class='li'>Foodsoft: <kbd>" . $fcpreis * $terramult / $fcmult . " je $terramult $can_terraeinheit </kbd></p></div>";
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

      } // if( $is_terra ) { ... katalogvergleich ... )

      if( $detail ) {
        if( $neednewprice ) {
          if( ! $newfceinheit )
            $newfceinheit = $can_terraeinheit;
          if( ! $newfcmult )
            $newfcmult = $terramult;
          $newfceinheit = strtoupper($newfceinheit);
          $newfcgebindegroesse = $terragebindegroesse * $terramult / $newfcmult;
          $newfcpreis = $brutto * $newfcmult / $terramult;
          $newfcbnummer = $terrabnummer;
          echo "
            <form method='post' action='terraabgleich.php?produktid=$produktid'>
              <input type='hidden' name='neuerpreiseintrag' value='1'>
              <div style='padding:1ex;' class='ok'>Vorschlag neuer Preiseintrag:
                <input type='text' size='40' name='newfcname' value='$name'></input>
                <br>
                Einheit <input type='text' size='4' name='newfcmult' value='$newfcmult'></input>
                <input type='text' size='2' name='newfceinheit' value='$newfceinheit'></input>
                &nbsp; Gebinde: <input type='text' size='6' name='newfcgebindegroesse' value='$newfcgebindegroesse'></input>
                &nbsp; Preis: <input type='text' size='8' name='newfcpreis' value='$newfcpreis'></input>
                &nbsp; B-Nr: <input type='text' size='8' name='newfcbnummer' value='$newfcbnummer'></input>
                &nbsp; ab: <input type='text' size='12' name='newfczeitstart' value='$mysqljetzt'></input>
                &nbsp; <input type='submit' name='submit' value='OK'
                        onclick=\"document.getElementById('row$outerrow').className='modified';\";
                ></input>
              </div>
            </form>
          ";
          }
        }
      
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
  
//   function jsinit() {
//     document.getElementById("presentationdetails").style.display = "$presentationdetailsstyle";
//     document.getElementById("posterdetails").style.display = "$posterdetailsstyle";
//     document.getElementById("talkdetails").style.display = "$talkdetailsstyle";
//     document.getElementById("applyreduction1").style.display = "$applyreductionstyle";
//     document.getElementById("applyreduction2").style.display = "$applyreductionstyle";
//     // document.getElementById("regularfee").style.display = "$regularfeestyle";
//     // document.getElementById("studentfee").style.display = "$studentfeestyle";
//     // document.getElementById("reducedfee").style.display = "$reducedfeestyle";
//   }
//   function clickPoster() {
//     document.getElementById("presentationdetails").style.display = "block";
//     document.getElementById("posterdetails").style.display = "block";
//     document.getElementById("talkdetails").style.display = "none";
//   }
//   function clickTalk() {
//     document.getElementById("presentationdetails").style.display = "block";
//     document.getElementById("posterdetails").style.display = "none";
//     document.getElementById("talkdetails").style.display = "block";
//   }
//   function clickNoPresentation() {
//     document.getElementById("presentationdetails").style.display = "none";
//     document.getElementById("posterdetails").style.display = "none";
//     document.getElementById("talkdetails").style.display = "none";
//   }
//   function clickStudent() {
//     isstudent = !isstudent;
//     if ( isstudent ) {
//       document.getElementById("applyreduction1").style.display = "block";
//       document.getElementById("applyreduction2").style.display = "block";
//     } else {
//       document.getElementById("applyreduction1").style.display = "none";
//       document.getElementById("applyreduction2").style.display = "none";
//     }
//     // document.getElementById("studentfee").style.display = "block";
//     // document.getElementById("regularfee").style.display = "none";
//   }
//   // function clickNoStudent() {
//   //  document.getElementById("NoApplyreduction").click();
//   //  document.getElementById("applyreduction1").style.display = "none";
//   //  document.getElementById("applyreduction2").style.display = "none";
//     // document.getElementById("studentfee").style.display = "none";
//     // document.getElementById("regularfee").style.display = "block";
//   // }
//   function clickApplyreduction() {
//     // document.getElementById("reducedfee").style.display = "block";
//   }
//   function clickNoApplyreduction() {
//     // document.getElementById("reducedfee").style.display = "none";
//   }
</script>




</html>
