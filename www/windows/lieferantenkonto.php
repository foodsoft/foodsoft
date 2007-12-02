<?PHP

 assert($angemeldet) or exit();
 
 //Vergleicht das Datum der beiden mysql-records
 //gibt +1 zurück, wenn Datum in $konto älter ist
 //gibt 0 zurück, wenn Daten gleich sind
 //gibt -1 zurück, wen Datum in $veteil älter ist
 function compare_date($konto, $verteil){
	//Kein weiterer Eintrag in Konto
 	if(!$konto) return 1;
	if(!$verteil) return -1;
 	$konto_date = $konto['valuta_kan'];
	$verteil_date = $verteil['valuta_kan'];
  if( $konto_date < $verteil_date )
    return 1;
  if( $konto_date > $verteil_date )
    return -1;
  return 0;
 }

nur_fuer_dienst(4,5);
get_http_var( 'lieferanten_id', 'u', 0, true );

?>
 <h1>Lieferantenkonto</h1>
 <div id='option_menu'></div>
<?

option_menu_row( "<th colspan='2'>Anzeigeoptionen</th>" );

option_menu_row(
  " <td>Lieferant:</td>
    <td><select id='select_lieferant' onchange=\"select_lieferant('"
    . self_url( 'lieferanten_id' ) . "');\">
  " . optionen_lieferanten( $lieferanten_id ) . "
    </select></td>"
);
if( ! $lieferanten_id )
  return;

$lieferanten_name = lieferant_name( $lieferanten_id );

get_http_var( 'action', 'w', '' );
switch( $action ) {
  case 'zahlung_lieferant':
    buchung_lieferant_bank();
    break;
 case 'zahlung_gruppe_lieferant':
   buchung_gruppe_lieferant();
   break;
}


?>
  <div id='transactions_button' style='padding-bottom:1em;'>
  <span class='button'
    onclick="document.getElementById('transactions_menu').style.display='block';
             document.getElementById('transactions_button').style.display='none';"
    >Transaktionen...</span>
  </div>

  <fieldset class='small_form' id='transactions_menu' style='display:none;margin-bottom:2em;'>
    <legend>
      <img src='img/close_black_trans.gif' class='button' title='Schliessen' alt='Schliessen'
      onclick="document.getElementById('transactions_button').style.display='block';
               document.getElementById('transactions_menu').style.display='none';">
      Transaktionen
    </legend>
  
    Art der Transaktion:
      <span style='padding-left:1em;' title='Ãœberweisung oder Abbuchung von Bankkonto der Foodcoop'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('zahlungbank_form').style.display='block';
                 document.getElementById('zahlunggruppe_form').style.display='none';"
      ><b>Ãœberweisung/Lastschrift</b>
      </span>

      <span style='padding-left:1em;' title='Direktzahlung von Gruppe an Lieferant'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('zahlungbank_form').style.display='none';
                 document.getElementById('zahlunggruppe_form').style.display='block';"
      ><b>Direktzahlung durch Gruppe</b>
      </span>

      <div id='zahlungbank_form' style='display:none;'>
        <? formular_buchung_lieferant_bank( $lieferanten_id ); ?>
      </div>

      <div id='zahlungruppe_form' style='display:none;'>
        <? formular_buchung_gruppe_lieferant( 0, $lieferanten_id ); ?>
      </div>

    </fieldset>

  <?
}

  $kontostand = lieferantenkontostand($lieferanten_id);

  $cols = 7;
  ?>
	 <table class="numbers">
	    <tr>
			   <th>Typ</th>
				 <th>Valuta</th>
				 <th>Buchung</th>
				 <th>Informationen</th>
				 <th colspan='2'>Betrag</th>
				 <th>Summe</th>
			</tr>
      <tr class='summe'>
        <td colspan='<? echo $cols-1; ?>' style='text-align:right;'>Kontostand:</td>
        <td class='number'><? printf( "%8.2lf", $kontostand ); ?></td>
      </tr>
			<?PHP

		  $result = sql_get_lieferant_transactions( $lieferanten_id );
      $num_rows = mysql_num_rows($result);

      $vert_result = sql_bestellungen_haben_lieferant($lieferanten_id);
      $summe = $kontostand;
      $konto_row = mysql_fetch_array($result);
      $vert_row = mysql_fetch_array($vert_result);
      while( $konto_row or $vert_row ) {
        if( compare_date($konto_row, $vert_row) == 1 ) {
          //Eintrag in Konto ist Älter -> Verteil ausgeben
          echo "<tr>\n";
          echo "   <td valign='top'><b>Bestellung</b></td>\n";
          echo "   <td>".$vert_row['valuta_trad']."</td>\n";
          echo "   <td> </td>\n";
          echo "   <td>".$vert_row['name']."</td>";
          echo "   <td class='mult'> <b> ".sprintf("%.2lf", $vert_row['haben'])."</b></td>";
          $details_url = "index.php?window=bestellschein&gruppen_id=0"
              . "&bestell_id={$vert_row['gesamtbestellung_id']}"
              . "&spalten=" . ( PR_COL_NAME | PR_COL_BESTELLMENGE | PR_COL_LPREIS
                                | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME );
          ?>
            <td class='unit'>
              <a class='png' style='padding:0pt 1ex 0pt 1ex;'
                href="javascript:neuesfenster('<? echo $details_url; ?>','bestellschein');">
                  <img src='img/b_browse.png' border='0' title='Details zur Bestellung' alt='Details zur Bestellung'/>
              </a>
            </td>
            <td class='number'><? printf( "%8.2lf", $summe ); ?></td>
            </tr>
          <?
          $summe -= $vert_row['haben'];
          $vert_row = mysql_fetch_array($vert_result);
        } else {
          ?>
            <tr>
              <td valign='top'><b>Zahlung</b></td>
              <td><? echo $konto_row['valuta_trad']; ?></td>
              <td><? echo $konto_row['date']; ?></td>
              <td> <? echo $konto_row['notiz']; ?>
                <br>
          <?
            $k_id = $konto_row['konterbuchung_id'];
            $k_row = sql_get_transaction( $k_id );
            if( $k_id > 0 ) { // bankueberweisung oder lastschrift
              $konto_id = $k_row['konto_id'];
              $auszug_nr = $k_row['kontoauszug_nr'];
              $auszug_jahr = $k_row['kontoauszug_jahr'];
              echo "Auszug: <a href=\"javascript:neuesfenster(
                 'index.php?window=kontoauszug&konto_id=$konto_id&auszug_jahr=$auszug_jahr&auszug_nr=$auszug_nr'
                ,'kontoauszug'
                );\">$auszug_jahr / $auszug_nr ({$bank_row['kontoname']})</a>
              ";
            } else {  // zahlung durch gruppe
              $gruppen_id = $k_row['gruppen_id'];
              $gruppen_name = sql_gruppenname($gruppen_id);
              echo "Zahlung durch Gruppe: <a href=\"javascript:neuesfenster(
              'index.php?window=showGroupTransaktions&gruppen_id=$gruppen_id'
              , 'kontoblatt'
              );\">$gruppen_name</a>
              ";
            }
          ?>
            </td>
            <td class='mult'><b><? printf("%.2lf" , -$konto_row['summe']); ?></b></td>
            <td class='unit'>&nbsp;</td>
            <td class='number'><? printf( "%8.2lf", $summe ); ?></td>
            </tr>
          <?
          $summe += $konto_row['summe'];
          $konto_row = mysql_fetch_array($result);
        }
      }
      ?>
        <tr class='summe'>
          <td colspan='<? echo $cols-1; ?>' style='text-align:right;'>Startsaldo:</td>
          <td class='number'><? printf( "%8.2lf", $summe ); ?></td>
        </tr>
      <?
   </table>

