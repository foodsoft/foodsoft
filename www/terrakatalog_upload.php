
<?php

assert( $angemeldet ) or exit();

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
