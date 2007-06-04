
<script type="text/javascript">
  function setelementmodified(theid) {
    document.getElementById(theid).className="modified";
  }
</script>

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

  $ldapuri = 'ldap://fcnahrungskette.qipc.org:21';
  $ldapbase = 'ou=terra,ou=fcnahrungskette,o=uni-potsdam,c=de';

  include ('head.php');

  $mysqljetzt = date('Y') . '-' . date('m') . '-' . date('d') . ' ' . date('H') . ':' . date('i') . ':' . date('s');
  echo 'Hallo, Welt! in MySQL ist es jetzt: ' . $mysqljetzt . '<br>';

  ( $result = mysql_query( 'SELECT id FROM lieferanten WHERE name="Terra" ' ) )
    || error ( __LINE__, __FILE__, "Suche nach Lieferant Terra fehlgeschlagen" );

  ( $row = mysql_fetch_array($result) )
    || error ( __LINE__, __FILE__, "Lieferant Terra nicht gefunden" );
  $terraid = $row['id'];
  echo 'Terra ID: ' . $terraid . '<br>';

  $filter = 'lieferanten_id=' . $terraid;
  if (isset($HTTP_GET_VARS['produktid'])) {
    $produktid = $HTTP_GET_VARS['produktid'];
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

  ?>

    <table width="100%">
      <tr>
        <th class="outer">A-Nr.</th>
        <th class="outer">Artikeldaten</th>
      </tr>
  <?php

  $n=0;
  while ( $n++ < 9 && ( $artikel = mysql_fetch_array( $terraprodukte ) ) ) {
    echo "\n";
    echo '<tr id="row' . $n . '">';
    $anummer = $artikel['artikelnummer'];
    $name = $artikel['name'];
    $produktid = $artikel['id'];

    echo '<th class="outer" style="vertical-align:top;">' . $anummer . '</th>';
    echo '<td class="outer" style="padding-bottom:1ex;">';

    // produktpreise abfragen und test auf konsistenz:
    //  - alle intervalle bis auf das letzte muessen abgeschlossen sein
    //  - intervalle duerfen nicht ueberlappen
    //  - warnen, wenn kein aktuell gueltiger preis vorhanden

    ( $terrapreise = mysql_query(
      'SELECT * FROM produktpreise WHERE produkt_id=' . $produktid . ' ORDER BY produkt_id,zeitstart' ) )
      || error ( __LINE__, __FILE__, "Suche nach Produktpreisen fehlgeschlagen" );

    $pr0 = FALSE;
    $prgueltig = FALSE;
    while( $pr1 = mysql_fetch_array($terrapreise) ) {
      // echo 'preiseintrag: ab ' . $pr1['zeitstart'] . ': ' . $pr1['preis'] . '<br>';
      if( $pr0 ) {
        if ( $pr0['zeitende'] == '' ) {
          echo '<span class="warn">FEHLER: Preisintervall nicht aktuell aber nicht abgeschlossen:</span><br>';
          echo 'ab: ' . $pr0['zeitstart'] . ' preis: ' . $pr0['preis'] . '<br>';
        } else if ( $pr0['zeitende'] > $pr1['zeitstart'] ) {
          echo '<span class="warn">FEHLER: Ueberlapp in Preishistorie:</span><br>';
          echo '&nbsp; eintrag: ab: '. $pr0['zeitstart'] . ' bis: ' . $pr0['zeitende'] . ' preis: ' . $pr0['preis'] . '<br>';
          echo '&nbsp; eintrag: ab: '. $pr1['zeitstart'] . ' bis: ' . $pr1['zeitende'] . ' preis: ' . $pr1['preis'] . '<br>';
        }
      }

      $pr0 = $pr1;
    }
    if( ! $pr0 ) {
      echo '<span class="warn">WARNUNG: kein Preiseintrag fuer diesen Artikel vorhanden!</span><br>';
    } else if ( $pr0['zeitende'] != '' ) {
      if ( $pr0['zeitende'] < mysqljetzt ) {
        echo '<span class="warn">WARNUNG: kein aktuell gueltiger Preiseintrag fuer diesen Artikel vorhanden!</span><br>';
        echo '&nbsp; letzter eintrag: ab: '. $pr0['zeitstart'] . ' bis: ' . $pr0['zeitende'] . ' preis: ' . $pr0['preis'] . '<br>';
      } else {
        echo '<span class="warn">WARNUNG: aktueller Preis laeuft aus:</span><br>';
        echo '&nbsp; letzter eintrag: ab: '. $pr0['zeitstart'] . ' bis: ' . $pr0['zeitende'] . ' preis: ' . $pr0['preis'] . '<br>';
        $prgueltig = $pr0;  // kann man noch zulassen...
      }
    } else {
      $prgueltig = $pr0;
    }

    ?>
      <div>Foodsoft-Datenbank:</div>
      <table width="100%">
        <tr>
          <th>B-Nr.</th>
          <th>Name</th>
          <th>Einheit</th>
          <th>Gebinde</th>
          <th>Preis</th>
        </tr>
    <?php
 
    echo '<tr>';

    if( $prgueltig ) {
      echo '<td>' . $prgueltig['bestellnummer'] . ' </td>';
    } else {
      echo '<td><div class="warn" style="text-align:center;">keine</div></td>';
    }
    echo '<td>' . $artikel['name'] . ' </td>' ;
    echo '<td>' . $artikel['einheit'] . ' </td>' ;
    if( $prgueltig ) {
      echo '<td>' . $prgueltig['gebindegroesse'] . ' </td>';
      echo '<td>' . $prgueltig['preis'] . ' </td>';
    } else {
      echo '<td><div class="warn" style="text-align:center;">keine</div></td>';
      echo '<td><div class="warn" style="text-align:center;">keiner</div></td>';
    }
      
    echo '';
    echo '</tr></table>';

    $filter = '(&(objectclass=terraartikel)(artikelnummer=' . $anummer . '))';
    // echo 'filter: ' . $filter;
    $katalogergebnis = ldap_search( $ldaphandle, $ldapbase, '(&(objectclass=terraartikel)(terraartikelnummer=' . $anummer . '))' );
    $katalogeintraege = ldap_get_entries( $ldaphandle, $katalogergebnis );

    if ( $katalogeintraege['count'] < 1 ) {
      ?>

        <div class="warn">Katalogsuche: Artikelnummer nicht gefunden!</div>
        <form action="artikelsuche.php" method="post" target="_new">
          <input type="hidden" name="produktid" value="<?php echo $produktid; ?>"></input>
          <input type="hidden" name="produktname" value="<?php echo $name; ?>"></input>
          Katalogsuche nach Name:
          <input name="terracn" value="<?php echo $name; ?>" size="40"></input>
          <input type="submit" name="submit" value="Los!"
           onclick="setelementmodified('row<?php echo $n; ?>');"></input>
        </form>

      <?php

    } else {

      ?>

        <div class="ok">Artikelnummer gefunden in Katalog <?php echo $katalogeintraege[0]["terradatum"][0]; ?>:</div>

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

      echo '<tr>';
      echo "  <td>" . $katalogeintraege[0]["terrabestellnummer"][0] . "</td>";
      echo "  <td>" . $katalogeintraege[0]["cn"][0] . "</td>";
      echo "  <td>" . $katalogeintraege[0]["terraeinheit"][0] . "</td>";
      echo "  <td>" . $katalogeintraege[0]["terragebindegroesse"][0] . "</td>";
      echo "  <td>" . $katalogeintraege[0]["terraherkunft"][0] . "</td>";
      echo "  <td>" . $katalogeintraege[0]["terraverband"][0] . "</td>";
      $netto = $katalogeintraege[0]["terranettopreisincents"][0] / 100.0;
      $mwst = $katalogeintraege[0]["terramwst"][0];
      $brutto = $netto * (1 + $mwst / 100.0 );
      echo "  <td>" . $netto . "</td>";
      echo "  <td>" . $mwst . "</td>";
      echo "  <td>" . $brutto . "</td>";
      echo "</tr>";
      
      echo "</table>";
    }
    
    echo '</td></tr>';
  }

  echo '</table>';
  
?>

</body>
</html>
