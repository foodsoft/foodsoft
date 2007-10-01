<?php

assert( $angemeldet ) or exit();

setWindowSubtitle( "Artikelsuche im Terra-Katalog" );
setWikiHelpTopic( "foodsoft:katalogsuche" );

$filter = '';

get_http_var( 'terrabnummer', 'w', '' );
$terrabnummer and $filter = $filter . '(terrabestellnummer='.$terrabnummer.')';

get_http_var( 'terraanummer', 'w', '' );
$terraanummer and $filter = $filter . '(terraartikelnummer='.$terraanummer.')';

get_http_var( 'terracn', 'M', '' );
$terracn and $filter = $filter . '(cn=*'.$terracn.'*)';

get_http_var( 'terraminpreis', 'f', '' );
$terraminpreis and $filter = $filter . '(terranettopreisincents>='.$terraminpreis.')';

get_http_var( 'terramaxpreis', 'f', '' );
$terramaxpreis and $filter = $filter . '(terranettopreisincents<='.$terramaxpreis.')';

get_http_var( 'terrakatalog', 'M', '' );
$terrakatalog and $filter = $filter . '(terradatum=*.'.$terrakatalog.')';

// produktid: wenn gesetzt, erlaube update der artikelnummer!
if( get_http_var( 'produktid', 'u', NULL, true ) ) {
  need_http_var( 'produktname', 'M', true );
}

echo "
  <form method='post' class='small_form' action='" . self_url() . "'>" . self_post() . "
    <fieldset class='small_form'>
    <legend>
  ";
  if( $produktid ) {
    echo "Katalogsuche nach Artikelnummer fuer <i>$produktname</i>:";
  } else {
    echo "Artikelsuche";
  }
  echo "
    </legend>
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

  if( ( ! $produktid ) && ( $hat_dienst_IV or $hat_dienst_V ) ) {
    echo "
      <br>
      <form class='small_form' action='index.php?window=terrakatalog_upload' method='post' enctype='multipart/form-data'>
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

  if( $produktid > 0 ) {
    ?> <b>Zur Uebernahme in die Produktdatenbank bitte auf Artikelnummer klicken!</b> <?
  }

  if ( $filter != '' ) {
    $filter = '(&(objectclass=terraartikel)' . $filter . ')';
    // echo '<br>filter: ' . $filter . '<br>';

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

    echo "
      <h2>$count Treffer ($max werden angezeigt)</h2>
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
    ";

    if ( $produktid > 0 ) {
      echo "
        <form action='index.php?window=terraabgleich&produktid=$produktid' method='post'>
        <input type='hidden' name='action' value='artikelnummer_setzen'>
      ";
    }

    for( $i=0; $i < $max; $i++ ) {
      echo "<tr>";
      echo "  <td>";
      if ( $produktid > 0 ) {
        echo "<input type='submit' name='anummer' value='{$entries[$i]['terraartikelnummer'][0]}'>";
      } else {
        echo "{$entries[$i]['terraartikelnummer'][0]}";
      }
      $netto = $entries[$i]["terranettopreisincents"][0] / 100.0;
      $mwst = $entries[$i]["terramwst"][0];
      $brutto = $netto * (1 + $mwst / 100.0 );
      echo "
        </td>
        <td>{$entries[$i]['terrabestellnummer'][0]}</td>
        <td>{$entries[$i]['cn'][0]}</td>
        <td>{$entries[$i]['terraeinheit'][0]}</td>
        <td>{$entries[$i]['terragebindegroesse'][0]}</td>
        <td>{$entries[$i]['terraherkunft'][0]}</td>
        <td>{$entries[$i]['terraverband'][0]}</td>
        <td>$netto</td>
        <td>$mwst</td>
        <td>$brutto</td>
        <td>{$entries[$i]['terradatum'][0]}</td>
        </tr>
      ";
    }

    if ( $produktid > 0 ) {
      ?> </form> <?
    }

    ?> </table> <?
  }

?>

