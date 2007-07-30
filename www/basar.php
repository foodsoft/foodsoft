<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
	require_once('code/config.php');
	
	require_once("$foodsoftpath/code/err_functions.php");
	require_once("$foodsoftpath/code/zuordnen.php");
  require_once("$foodsoftpath/code/login.php" );
  if( $angemeldet )
     $pwd_ok = true;

  require_once ("$foodsoftpath/head.php");


// Übergebene Variablen einlesen...
  get_http_var('bestell_id');
  get_http_var('allGroupsArray');
  get_http_var('sortierfolge');
  get_http_var('nichtGeliefert');

  // glassrueckgabe bearbeiten:
  //
  get_http_var('menge_glas');
  get_http_var('gruppe');
  if( $menge_glas > 0 and $gruppe > 0 ) {
    sql_groupGlass( $gruppe, $menge_glas );
  }

  echo "
    <h1>Basar</h1>
      <br>
      <br>
         <form action='basar.php' method='post'>
         <table style='width: 600px;' class='numbers'>
    <tr>
      <td colspan=3> Gruppe: 
        <select name='gruppe'>
  ";
  optionen_gruppen();
  echo "
        </select>
      </td>
    </tr>
    <tr class='legende'>
      <th>Produkt</th>
      <th colspan='2'>Menge im Basar</th>
      <th>Menge</th>
    </tr>
  ";

  //Den Basar erstellen
  $result1 = sql_basar();

  while  ($basar_row = mysql_fetch_array($result1)) {
     kanonische_einheit( $basar_row['verteileinheit'], & $kan_verteileinheit, & $kan_verteilmult );
     $fieldName = "menge_".$basar_row['produkt_id']_$basar_row['gesamtbestellung_id'];
     $menge=$basar_row['basar'];
     if( get_http_var($fieldName) ) {
       if( ${$fieldName} != 0 && $gruppe > 0 ) {
         $gruppen_menge = ${$fieldName} / $kan_verteilmult;
         $menge -= $gruppen_menge;
         sql_basar2group($gruppe, $basar_row['produkt_id'], $basar_row['gesamtbestellung_id'], $gruppen_menge);
         if($menge==0) continue;
       }
     }
     // umrechnen, z.B. Brokkoli von: x * (500g) nach (x * 500) g:
     $menge *= $kan_verteilmult;
     echo "
       <tr>
         <td>{$basar_row['name']}</td>
         <td class='mult'><b>$menge</b></td>
         <td class='unit'>$kan_verteileinheit</td>
         <td><input name='{$fieldName}' type='text' size='3' /> $kan_verteileinheit</td>
       </tr>
     ";
  }

   echo "
     <tr style='border:none'>
       <td colspan='4' style='border:none'></td>
     </tr>
     <tr>
      <td colspan='4' >
        Glasr&uuml;ckgabe zu 16 Cent (Anzahl eintragen):	<input name='menge_glas' type='text' size='3' />
      </td>
     </tr>

  <tr style='border:none'>
     <td colspan='4' style='border:none'></td>
  </tr>
   
   <tr style='border:none'>
  <td colspan='4' style='border:none'>
     <input type='hidden' name='bestell_id' value='$bestell_id'>
     <input type='submit' value=' Neu laden / Basareintrag &uuml;bertragen '>
     <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen'>
  </td>
   </tr>
   </table>                   
   </form>

   <form action='index.php' method='post'>
     <input type='hidden' name='bestellungs_id' value='$bestell_id'>
     <input type='hidden' name='area' value='bestellt'>			
     <input type='submit' value='Zur&uuml;ck '>
   </form>

   $print_on_exit
 ";
?>
