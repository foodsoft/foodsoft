<?php

	require_once('code/config.php');
	require_once('code/err_functions.php');
  require_once('code/login.php');
 
  get_http_var('area');

	//head einfügen
  $title="Artikelsuche im Terra-Katalog";
  $subtitle="Artikelsuche im Terra-Katalog";
	require_once ('windows/head.php');

  $filter = '';
  get_http_var( 'terrabnummer' ) or $terrabnummer='';
  $terrabnummer and $filter = $filter . '(terrabestellnummer='.$terrabnummer.')';

  get_http_var( 'terraanummer' ) or $terraanummer='';
  $terraanummer and $filter = $filter . '(terraartikelnummer='.$terraanummer.')';

  get_http_var( 'terracn' ) or $terracn='';
  $terracn and $filter = $filter . '(cn=*'.$terracn.'*)';

  get_http_var( 'terraminpreis' ) or $terraminpreis='';
  $terraminpreis and $filter = $filter . '(terranettopreisincents>='.$terraminpreis.')';

  get_http_var( 'terramaxpreis' ) or $terramaxpreis='';
  $terramaxpreis and $filter = $filter . '(terranettopreisincents<='.$terramaxpreis.')';

  get_http_var( 'terrakatalog' ) or $terrakatalog='';
  $terrakatalog and $filter = $filter . '(terradatum=*.'.$terrakatalog.')';

  // produktid: wenn gesetzt, erlaube update der artikelnummer!
  get_http_var( 'produktid' ) and get_http_var( 'produktname' ) or $produktid = -1;

  echo "
    <form action='artikelsuche.php' method='post' class='small_form'>
     <fieldset class='small_form'>
     <legend>
  ";
  if( $produktid >= 0 ) {
    echo "Katalogsuche nach Artikelnummer fuer <i>$produktname</i>:";
  } else {
    echo "Artikelsuche";
  }
  echo "</legend>";
  if( $produktid >= 0 ) {
    echo "<input type='hidden' name='produktid' value='$produktid'>
          <input type='hidden' name='produktname' value='$produktname'>";
  }
  echo "
     <table>
     <tr>
       <td>
         Bestellnummer:
       </td><td>
         <input type='text' name='terrabnummer' value='$terrabnummer' size='10'>
         &nbsp;
         Artikelnummer:
         <input type='text' name='terraanummer' value='$terraanummer' size='10'>
         &nbsp;
         Katalog:
         <select name='terrakatalog' size='1'>
  ";
  $kataloge = array( '', 'OG', 'Fr', 'Tr' );
  foreach ( $kataloge as $option ) {
    echo "<option value='$option'";
    if ( $terrakatalog == $option )
      echo ' selected';
    echo ">$option</option>";
  }
  echo "
           </select>
         </td
       </tr>
       <tr>
         <td>
           Bezeichnung:
         </td><td>
           <input type='text' name='terracn' value='$terracn' size='60'>
           Jokerzeichen * ist erlaubt!
         </td>
       </tr>
       <tr>
         <td>
           Preis (netto in Cent):
         </td><td>
           &nbsp; von: <input type='text' name='terraminpreis' value='$terraminpreis' size='10'>
           &nbsp; bis: <input type='text' name='terramaxpreis' value='$terramaxpreis' size='10'>
         </td>
       </tr>
       <tr>
         <td>
           &nbsp;
         </td><td>
           <input type='submit' value='Suche starten'>
         </td
       </tr>
       </table>
    </fieldset>
    </form>
  ";
  
  if( ( $produktid < 0 ) && ( $hat_dienst_IV or $hat_dienst_V ) ) {
    echo "
      <br>
      <form class='small_form' action='terrakatalog.upload.php' method='post' enctype='multipart/form-data'>
         <fieldset class='small_form'>
           <legend>
           Neuen Katalog einlesen:
           </legend>
         <tr>
        <table>
        <tr>
          <td>
          Datei (Format: .xls): <input type='file' name='terrakatalog'></input>
          </td>
        <td>
         &nbsp; gueltig ab (Format: JJJJkwWW): <input type='text' name='terrakw' size='8'></input>
        </td>
          <td>
            <input type='submit' value='start'>
          </td
        </tr>
        </table>
        </fieldset>
      </form>
    ";
  }

  if( $produktid >= 0 ) {
    echo '<b>Zur Uebernahme in die Produktdatenbank bitte auf Artikelnummer klicken!</b>';
  }

  if ( $filter != '' ) {
    $filter = '(&(objectclass=terraartikel)' . $filter . ')';
    echo '<br>filter: ' . $filter . '<br>';

    //echo "<br>connecting... ";
    $ldaphandle = ldap_connect( $ldapuri );
    //echo " result is: " . $ldaphandle  . " <br>";

    //echo "<br>setting protocol version 3...";
    $rv = ldap_set_option( $ldaphandle, LDAP_OPT_PROTOCOL_VERSION, 3 );
    //echo " result is: " . $rv  . " <br>";

    //echo "<br>binding to server...";
    $rv = ldap_bind( $ldaphandle );
    //echo " result is: " . $rv  . " <br>";

    //echo "<br>searching...";
    $results = ldap_search( $ldaphandle , $ldapbase , $filter );
    //echo " result is: " . $results  . " <br>";

    $entries = ldap_get_entries( $ldaphandle, $results );
    //echo " hit count:  " . $entries["count"]  . " <br>";
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

    if ( $produktid >= 0 ) {
      echo "<form action='terraabgleich.php?produktid=$produktid' method='post'>";
    }

    for( $i=0; $i < $max; $i++ ) {
      echo "<tr>";
      echo "  <td>";
      if ( $produktid >= 0 ) {
        echo '<input type="submit" name="anummer" value="' . $entries[$i]["terraartikelnummer"][0] . '"></input>';
      } else {
        echo $entries[$i]["terraartikelnummer"][0];
      }
      echo "</td>";
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

    if ( $produktid >= 0 ) {
      echo '</form>';
    }

    echo "</table>";
  }

  echo "$print_on_exit";
?>

