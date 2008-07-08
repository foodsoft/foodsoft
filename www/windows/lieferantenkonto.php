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

$editable = ( ! $readonly and ( $dienst == 4 ) );
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

if( $editable ) {
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

        <div id='zahlunggruppe_form' style='display:none;'>
          <? formular_buchung_gruppe_lieferant( 0, $lieferanten_id ); ?>
        </div>

     </fieldset>

  <?
}

  $kontostand = lieferantenkontostand($lieferanten_id);
  $pfandkontostand = lieferantenpfandkontostand($lieferanten_id);

  $cols = 9;
  ?>
  <table class="numbers">
    <tr>
      <th>Typ</th>
      <th>Valuta</th>
      <th>Buchung</th>
      <th>Informationen</th>
      <th>Pfand</th>
      <th>Pfandkonto</th>
      <th>Waren</th>
      <th>Sonstiges</th>
      <th>Buchung</th>
      <th>Kontostand</th>
    </tr>
    <tr class='summe'>
      <td colspan='5' style='text-align:right;'>Kontostand:</td>
      <td class='number'>
        <? printf( "%.2lf", $pfandkontostand ); ?>
      </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td class='number'>
        <? printf( "%.2lf", $kontostand ); ?>
      </td>
    </tr>
  <?

  $konto_result = sql_get_group_transactions( 0, $lieferanten_id );
  $vert_result = sql_bestellungen_soll_lieferant( $lieferanten_id );

  $summe = $kontostand;
  $pfandsumme = $pfandkontostand;
  $konto_row = mysql_fetch_array($konto_result);
  $vert_row = mysql_fetch_array($vert_result);
  while( $konto_row or $vert_row ) {
    if( compare_date($konto_row, $vert_row) == 1 ) {
      //Eintrag in Konto ist Älter -> Verteil ausgeben
      $bestell_id = $vert_row['gesamtbestellung_id'];
//              . "&spalten=" . ( PR_COL_NAME | PR_COL_BESTELLMENGE | PR_COL_LPREIS
//                                | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME );
      $pfand_leer_soll = $vert_row['pfand_leer_brutto_soll'];
      $pfand_voll_soll = $vert_row['pfand_voll_brutto_soll'];
      $pfand_soll = $pfand_leer_soll + $pfand_voll_soll;
      $waren_soll = $vert_row['waren_brutto_soll'];
      $extra_soll = $vert_row['extra_brutto_soll'];
      $soll = $pfand_soll + $waren_soll + $extra_soll;
      ?>
      <tr>
        <td style='vertical-align:top;font-weight:bold;'>Bestellung</td>
        <td><? echo $vert_row['valuta_trad']; ?></td>
        <td><? echo $vert_row['lieferdatum_trad']; ?></td>
        <td>Bestellung:
          <? echo fc_alink( 'lieferschein', array(
             'img' => false, 'bestell_id' => $bestell_id, 'text' => $vert_row['name']
             , 'spalten' => ( PR_COL_NAME | PR_COL_BESTELLMENGE | PR_COL_LPREIS | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME )
          ) ); ?>
        </td>
        <td class='number'>
          <? printf("%.2lf", $pfand_soll ); ?>
        </td>
        <td class='number'>
          <? printf("%.2lf", $pfandsumme ); ?>
        </td>
        <td class='number'>
          <? printf("%.2lf", $waren_soll ); ?>
        </td>
        <td class='number'>
          <? printf("%.2lf", $extra_soll ); ?>
        </td>
        <td class='number'>
          <div style='font-weight:bold;'><? printf("%.2lf", $soll ); ?></div>
        </td>
        <td class='number'>
          <? printf("%.2lf", $summe ); ?>
        </td>
      </tr>
      <?
      $summe -= $soll;
      $pfandsumme -= $pfand_soll;
      $vert_row = mysql_fetch_array($vert_result);
    } else {
      ?>
      <tr>
        <td valign='top' style='font-weight:bold;'>
          <?
          if( $konto_row['konterbuchung_id'] >= 0 ) {
            echo 'Zahlung';
          } else {
            echo 'Verrechnung';
          }
          ?>
        </td>
        <td><? echo $konto_row['valuta_trad']; ?></td>
        <td><? echo $konto_row['date']; ?></td>
        <td>
          <div>
            <? echo fc_alink( 'edit_buchung', array(
              'img' => false, 'transaktion_id' => $konto_row['id'], 'text' => $konto_row['notiz'] ) );
            ?>
          </div>
          <div>
            <?
            $k_id = $konto_row['konterbuchung_id'];
            $k_row = sql_get_transaction( $k_id );
            if( $k_id > 0 ) { // bankueberweisung oder lastschrift
              $konto_id = $k_row['konto_id'];
              $auszug_nr = $k_row['kontoauszug_nr'];
              $auszug_jahr = $k_row['kontoauszug_jahr'];
              echo "Auszug: " . fc_alink( 'kontoauszug', array(
                'konto_id' => $konto_id, 'auszug_jahr' => $auszug_jahr, 'auszug_nr' => $auszug_nr
              , 'img' => false, 'text' => "$auszug_jahr / $auszug_nr ({$k_row['kontoname']})" ) );
            } else {  // zahlung durch gruppe
              $gruppen_id = $k_row['gruppen_id'];
              $gruppen_name = sql_gruppenname($gruppen_id);
              echo "Zahlung durch Gruppe: " . fc_alink( 'gruppenkonto', array(
                'img' => false, 'gruppen_id' => $gruppen_id, 'text' => $gruppen_name ) );
            }
            ?>
          </div>
        </td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td class='number'>
          <div style='font-weight:bold;'>
            <? printf("%.2lf" , $konto_row['summe']); ?>
          </div>
        </td>
        <td class='number'>
          <? printf( "%.2lf", $summe ); ?>
        </td>
      </tr>
      <?
      $summe -= $konto_row['summe'];
      $konto_row = mysql_fetch_array($konto_result);
    }
  }

  ?>
    <tr class='summe'>
      <td colspan='5' style='text-align:right;'>Startsaldo:</td>
      <td class='number'>
        <? printf( "%8.2lf", $pfandsumme ); ?>
      </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td class='number'>
        <? printf( "%8.2lf", $summe ); ?>
      </td>
    </tr>
   </table>

