<?php
//error_reporting(E_ALL); // alle Fehler anzeigen
	require_once('code/config.php');
	
	require_once("$foodsoftpath/code/err_functions.php");
	require_once("$foodsoftpath/code/zuordnen.php");
  require_once("$foodsoftpath/code/login.php" );

  require_once ("$foodsoftpath/head.php");


// ‹bergebene Variablen einlesen...
  get_http_var('bestell_id');
  get_http_var('allGroupsArray');
  get_http_var('sortierfolge');
  get_http_var('nichtGeliefert');

  // glassrueckgabe bearbeiten:
  //
  get_http_var('menge_glas');
  get_http_var('gruppe');
  if( $menge_glas > 0 and $gruppe > 0 ) {
    fail_if_readonly();
    nur_fuer_dienst(4);
    sql_groupGlass( $gruppe, $menge_glas );
  }

?>
    <h1>Basar</h1>

      <p>
<?	wikiLink("foodsoft:basarbewegungen_eintragen", "Wiki..."); ?>
      </p>
         <form action='basar.php' method='post'>
         <table style='width: 600px;' class='numbers'>
    <tr>
      <td colspan='5'> Gruppe: 
        <select name='gruppe'>
        <option value='' selected>(Gruppe w&auml;hlen)</option>
<?
  echo optionen_gruppen();
?>
        </select>
      </td>
    </tr>
    <tr class='legende'>
      <th>Produkt</th>
      <th>aus Bestellung</th>
      <th colspan='2'>Preis</th>
      <th colspan='3'>Menge im Basar</th>
      <th>Zuteilung</th>
    </tr>
<?

  //Den Basar erstellen
  $result1 = sql_basar();

  $letzte_produkt_id = -1;
  while  ($basar_row = mysql_fetch_array($result1)) {
     kanonische_einheit( $basar_row['verteileinheit'], & $kan_verteileinheit, & $kan_verteilmult );
     $fieldName = "menge_{$basar_row['produkt_id']}_{$basar_row['gesamtbestellung_id']}";
     $menge=$basar_row['basar'];
     if( get_http_var($fieldName) ) {
       if( ${$fieldName} != 0 && $gruppe > 0 ) {
         fail_if_readonly();
         nur_fuer_dienst(4);
         $gruppen_menge = ${$fieldName} / $kan_verteilmult;
         $menge -= $gruppen_menge;
         sql_basar2group($gruppe, $basar_row['produkt_id'], $basar_row['gesamtbestellung_id'], $gruppen_menge);
         if($menge==0) continue;
       }
     }
     // umrechnen, z.B. Brokkoli von: x * (500g) nach (x * 500) g:
     $menge *= $kan_verteilmult;
     if( $letzte_produkt_id == $basar_row['produkt_id'] ) {
       $name = '';
     } else {
       $name = $basar_row['name'];
       $letzte_produkt_id = $basar_row['produkt_id'];
     }
     echo "
       <tr>
         <td><b>$name</b></td>
         <td><a
           href=\"javascript:neuesfenster('/foodsoft/index.php?area=lieferschein&bestellungs_id={$basar_row['gesamtbestellung_id']}','lieferschein')\"
             title='zum Lieferschein...'>{$basar_row['bestellung_name']}</a></td>
         <td class='mult'>" . sprintf( "%8.2lf", $basar_row['preis'] ) . "</td>
         <td class='unit'>/ $kan_verteilmult $kan_verteileinheit</td>
         <td class='mult'><b>$menge</b></td>
         <td class='unit' style='border-right-style:none;'>$kan_verteileinheit</td>
         <td style='border-left-style:none;'><a 
            href=\"javascript:neuesfenster('/foodsoft/windows/showBestelltProd.php?bestell_id={$basar_row['gesamtbestellung_id']}&produkt_id={$basar_row['produkt_id']}','produktverteilung');\"
            ><img src='img/b_browse.png' border='0' title='Details zur Verteilung' alt='Details zur Verteilung'
            ></a></td>
         <td class='unit'><input name='{$fieldName}' type='text' size='5' /> $kan_verteileinheit</td>
       </tr>
     ";
  }

   echo "
     <tr style='border:none'>
       <td colspan='5' style='border:none'></td>
     </tr>
     <tr>
      <td colspan='5' >
        Glasr&uuml;ckgabe zu 16 Cent (Anzahl eintragen):	<input name='menge_glas' type='text' size='3' />
      </td>
     </tr>

  <tr style='border:none'>
     <td colspan='5' style='border:none'></td>
  </tr>
   
   <tr style='border:none'>
  <td colspan='5' style='border:none'>
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

 ";
?>

