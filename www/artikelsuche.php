
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


	//head einfügen
		include ('head.php');

    echo 'Hallo, Welt!';

    $filter = '';
    if (isset($HTTP_GET_VARS['terrabnummer'])) {
      $terrabnummer = $HTTP_GET_VARS['terrabnummer'];
      if ( $terrabnummer > 0 )
        $filter = $filter . '(terrabestellnummer='.$terrabnummer.')';
    }
    if (isset($HTTP_GET_VARS['terraanummer'])) {
      $terraanummer = $HTTP_GET_VARS['terraanummer'];
      if ( $terraanummer > 0 )
        $filter = $filter . '(terraartikelnummer='.$terraanummer.')';
    }
    if (isset($HTTP_GET_VARS['terracn'])) {
      $terracn = $HTTP_GET_VARS['terracn'];
      if ( $terracn )
        $filter = $filter . '(cn=*'.$terracn.'*)';
    }
    if (isset($HTTP_GET_VARS['terraminpreis'])) {
      $terraminpreis = $HTTP_GET_VARS['terraminpreis'];
      if ( $terraminpreis > 0 )
        $filter = $filter . '(terranettopreisincents>='.$terraminpreis.')';
    }
    if (isset($HTTP_GET_VARS['terramaxpreis'])) {
      $terramaxpreis = $HTTP_GET_VARS['terramaxpreis'];
      if ( $terramaxpreis > 0 )
        $filter = $filter . '(terranettopreisincents<='.$terramaxpreis.')';
    }
?>
    <form action="artikelsuche.php" method="post">
      <table>
      <tr>
        <th colspan="2">
          Artikelsuche
        </th>
      <tr>
      <tr>
        <td>
          Bestellnummer:
        </td><td>
          <input type="text" name="terrabnummer" value="<?PHP echo $terrabnummer; ?>" size=10>
          &nbsp;
          Artikelnummer:
          <input type="text" name="terraanummer" value="<?PHP echo $terraanummer; ?>" size=10>
        </td
      </tr>
      <tr>
        <td>
          Bezeichnung:
        </td><td>
          <input type="text" name="terracn" value="<?PHP echo $terracn; ?>" size=60>
          Jokerzeichen * ist erlaubt!
        </td
      </tr>
      <tr>
        <td>
          Preis (netto in Cent):
        </td><td>
          &nbsp; von: <input type="text" name="terraminpreis" value="<?PHP echo $terraminpreis; ?>" size=10>
          &nbsp; bis: <input type="text" name="terramaxpreis" value="<?PHP echo $terramaxpreis; ?>" size=10>
        </td
      </tr>
      <tr>
        <td>
          &nbsp;
        </td><td>
          <input type="submit" value="Suche starten">
        </td
      </tr>
      </table>
   </form>

<?php

    if ( $filter != '' ) {
      $filter = '(&(objectclass=terraartikel)' . $filter . ')';
      echo '<br>filter: ' . $filter . '<br>';

      echo "<br>connecting... ";
      $ldaphandle = ldap_connect( $ldapuri );
      echo " result is: " . $ldaphandle  . " <br>";

      echo "<br>setting protocol version 3...";
      $rv = ldap_set_option( $ldaphandle, LDAP_OPT_PROTOCOL_VERSION, 3 );
      echo " result is: " . $rv  . " <br>";

      echo "<br>binding to server...";
      $rv = ldap_bind( $ldaphandle );
      echo " result is: " . $rv  . " <br>";

      echo "<br>searching...";
      $results = ldap_search( $ldaphandle , $ldapbase , $filter );
      echo " result is: " . $results  . " <br>";

      $entries = ldap_get_entries( $ldaphandle, $results );
      echo " hit count:  " . $entries["count"]  . " <br>";
      $count = $entries["count"];
      $max = $count;
      if( $max > 100 ) $max = 100;

      echo "<h2> " . $count . " Treffer (" . $max . " werden angezeigt)</h2>";
      ?>
        <table>
          <tr>
            <th>A-Nr.</th>
            <th>B-Nr.</th>
            <th>Bezeichnung</th>
            <th>Einheit</th>
            <th>Gebinde</th>
            <th>Land</th>
            <th>Verband</th>
            <th>Netto</th>
            <th>MWSt</th>
            <th>Brutto</th>
            <th>Katalog</th>
          </tr>
      <?php

      for( $i=0; $i < $max; $i++ ) {
        echo "<tr>";
        echo "  <td>" . $entries[$i]["terraartikelnummer"][0] . "</td>";
        echo "  <td>" . $entries[$i]["terrabestellnummer"][0] . "</td>";
        echo "  <td>" . $entries[$i]["cn"][0] . "</td>";
        echo "  <td>" . $entries[$i]["terraeinheit"][0] . "</td>";
        echo "  <td>" . $entries[$i]["terragebindegroesse"][0] . "</td>";
        echo "  <td>" . $entries[$i]["terraherkunft"][0] . "</td>";
        echo "  <td>" . $entries[$i]["terraverband"][0] . "</td>";
        $netto = $entries[$i]["terranettopreisincents"][0] / 100.0;
        $mwst = $entries[$i]["terramwst"][0];
        $brutto = $netto * (1 + $mwst / 100.0 );
        echo "  <td>" . $netto . "</td>";
        echo "  <td>" . $mwst . "</td>";
        echo "  <td>" . $brutto . "</td>";
        echo "  <td>" . $entries[$i]["terradatum"][0] . "</td>";
        echo "</tr>";
      }

      echo "</table>";
    }
?>

</body>
</html>
