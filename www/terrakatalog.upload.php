
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

	//head einfügen
		include ('head.php');

    echo 'Hallo, Welt!';
    echo '<br>files: ' . $_FILES;
    echo '<br>kat: ' . $_FILES['terrakatalog'];
    echo '<br>tmp: ' . $_FILES['terrakatalog']['tmp_name'];

    if (isset($HTTP_GET_VARS['terrakw']))
      $terrakw = $HTTP_GET_VARS['terrakw'];

    if (isset($HTTP_GET_VARS['terrakatalog']))
      $terrakatalog = $HTTP_GET_VARS['terrakatalog'];

    echo '<br>terrakw: ' . $terrakw . '<br>';
    echo '<br>terrakatalog: ' . $terrakatalog . '<br>';
    // system( 'cat ' . $_FILES['terrakatalog']['tmp_name'] );
    system( './terra2ldap.sh ' . $terrakw . ' ' . $_FILES['terrakatalog']['tmp_name'] );
    echo '<br>finis.<br>';

?>

</body>
</html>
