<?PHP

assert($angemeldet) or exit();
 
 //Vergleicht das Datum der beiden mysql-records
 //gibt +1 zur¸ck, wenn Datum in $konto ‰lter ist
 //gibt 0 zur¸ck, wenn Daten gleich sind
 //gibt -1 zur¸ck, wen Datum in $veteil ‰lter ist
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

get_http_var( 'meinkonto', 'u', 0, true );
$muell_id = sql_muell_id();

if( $meinkonto ) {
  setWikiHelpTopic( 'foodsoft:MeinKonto' );
  $gruppen_id = $login_gruppen_id;
  $self_fields['gruppen_id'] = $gruppen_id;
  $gruppen_name = sql_gruppenname( $gruppen_id );
  ?> <h1>Mein Konto: Kontoausz&uuml;ge von Gruppe <? echo $gruppen_name; ?></h1> <?

  if( ! $readonly ) {
    ?>
    <div id='transaction_button' style='padding-bottom:1em;'>
    <span class='button'
      onclick="document.getElementById('transaction_form').style.display='block';
               document.getElementById('transaction_button').style.display='none';"
      title="Hier k√∂nnt Ihr eintragen, dass Ihr Geld √ºberwiesen habt, und dann gleich damit bestellen!"
      >√úberweisung eintragen...</span>
    </div>

    <div id='transaction_form' style='display:none;padding-bottom:1em;'>
      <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <fieldset>
      <legend>
        <img src='img/close_black_trans.gif' class='button'
        onclick="document.getElementById('transaction_button').style.display='block';
                 document.getElementById('transaction_form').style.display='none';">
        √úberweisung eintragen
      </legend>
      Ich habe heute <input type="text" size="12" name="amount"/>
      Euro <input type="submit" value="√ºberwiesen"/>
      </fieldset>
      </form>
    </div>
    <?

    if( get_http_var( 'amount', 'f' ) ) {
      sql_gruppen_transaktion( 0, $login_gruppen_id, $amount, "Einzahlung" );
    }
  }

} else {
  nur_fuer_dienst(4,5);
  setWikiHelpTopic( 'foodsoft:kontoblatt' );
  get_http_var( 'gruppen_id', 'u', 0, true );
  ?>
  <h1>Kontoblatt</h1>
  <div id='option_menu'></div>
  <?

  option_menu_row( "<th colspan='2'>Anzeigeoptionen</th>" );

  option_menu_row(
    " <td>Gruppe:</td>
      <td><select id='select_group' onchange=\"select_group('"
      . self_url( 'gruppen_id' ) . "');\">
    " . optionen_gruppen( false, false, $gruppen_id, ( $gruppen_id ? false : "(bitte Gruppe w√§hlen)"), false , sql_muell_id() ) . "
      </select></td>"
  );
  if( ! $gruppen_id )
    return;
  $gruppen_name = sql_gruppenname( $gruppen_id );

  if( ! $readonly ) {
    get_http_var( 'action', 'w', '' );
    switch( $action ) {
      case 'finish_transaction':
        need_http_var( 'trans_nr', 'u' );
        need_http_var( 'auszug_jahr', 'u' );
        need_http_var( 'auszug_nr', 'u' );
        need_http_var( 'konto_id', 'u' );
        need_http_var( 'year', 'u' );
        need_http_var( 'month', 'u' );
        need_http_var( 'day', 'u' );
        sql_finish_transaction( $trans_nr, $konto_id, $auszug_nr, $auszug_jahr, "$year-$month-$day", 'gebuchte Einzahlung' );
        break;
      case 'zahlung_gruppe':
        buchung_gruppe_bank();
        break;
      case 'zahlung_gruppe_lieferant':
        buchung_gruppe_lieferant();
        break;
      case 'umbuchung_gruppe_gruppe':
        buchung_gruppe_gruppe();
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
        <span style='padding-left:1em;' title='Einzahlung auf oder Auszahlung von Bankkonto der Foodcoop'>
        <input type='radio' name='transaktionsart'
          onclick="document.getElementById('einzahlung_form').style.display='block';
                   document.getElementById('gruppegruppe_form').style.display='none';
                   document.getElementById('gruppelieferant_form').style.display='none';"
        ><b>Einzahlung</b>
        </span>
  
        <span style='padding-left:1em;' title='√ºberweisung auf ein anderes Gruppenkonto'>
        <input type='radio' name='transaktionsart'
          onclick="document.getElementById('einzahlung_form').style.display='none';
                   document.getElementById('gruppegruppe_form').style.display='block';
                   document.getElementById('gruppelieferant_form').style.display='none';"
        ><b>Transfer an andere Gruppe</b>
        </span>
  
        <span style='padding-left:1em;' title='√ºberweisung von Gruppe an Lieferant'>
        <input type='radio' name='transaktionsart'
          onclick="document.getElementById('einzahlung_form').style.display='none';
                   document.getElementById('gruppegruppe_form').style.display='none';
                   document.getElementById('gruppelieferant_form').style.display='block';"
        ><b>√úberweisung von Gruppe an Lieferant</b>
        </span>
  
        <div id='einzahlung_form' style='display:none;'>
          <? formular_buchung_gruppe_bank( $gruppen_id ); ?>
        </div>
  
        <div id='gruppegruppe_form' style='display:none;'>
          <? formular_buchung_gruppe_gruppe( $gruppen_id, 0 ); ?>
        </div>
  
        <div id='gruppelieferant_form' style='display:none;'>
          <? formular_buchung_gruppe_lieferant( $gruppen_id, 0 ); ?>
        </div>
  
      </fieldset>
  
    <?
  }
}

$kontostand = kontostand($gruppen_id);
$pfandkontostand = pfandkontostand($gruppen_id);

// aktuelle Gruppendaten laden
$bestellgruppen_row = sql_gruppendaten( $gruppen_id );
	
	// wieviele Kontenbewegungen werden ab wo angezeigt...
	if (isset($HTTP_GET_VARS['start_pos'])) $start_pos = $HTTP_GET_VARS['start_pos']; else $start_pos = 0;
	//Funktioniert erstmal mit der Mischung aus Automatischer Berechung und manuellen Eintr‰gen nicht
  //FIXME: vielleicht ggf. start/enddatum waehlbar machen? oder immer ganze jahre?
	$size          = 2000;
	 
	
  $cols = 9;
  ?>
  <table class="numbers">
    <tr>
      <th>Typ</th>
      <th>Valuta</th>
      <th>Buchung</th>
      <th>Informationen</th>
      <th>Pfand Kauf</th>
      <th style='vertical-align:bottom;'>R√ºckgabe</th>
      <th>Pfandkonto</th>
      <th>Waren</th>
      <th>Buchung</th>
      <th>Kontostand</th>
    </tr>
    <tr class='summe'>
      <td colspan='6' style='text-align:right;'>Kontostand:</td>
      <td class='number'>
        <? printf( "%8.2lf", $pfandkontostand ); ?>
      </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td class='number'>
        <? printf( "%8.2lf", $kontostand ); ?>
      </td>
    </tr>
  <?
  $konto_result = sql_get_group_transactions( $gruppen_id, 0 );
  $num_rows = mysql_num_rows($result);

  $vert_result = sql_bestellungen_soll_gruppe($gruppen_id);
  $summe = $kontostand;
  $pfandsumme = $pfandkontostand;
  $konto_row = mysql_fetch_array($konto_result);
  $vert_row = mysql_fetch_array($vert_result);
  while( $vert_row or $konto_row ) {
    //Mische Eintr‰ge aus Kontobewegungen und Verteilzuordnung zusammen
    if( compare_date($konto_row, $vert_row)==1 ){
      //Eintrag in Konto ist ƒlter -> Verteil ausgeben
      $details_link = fc_alink( 'lieferschein', array(
        'img' => false, 'text' => $vert_row['name'], 'title' => 'zum Lieferschein...'
      , 'bestell_id' => $vert_row['gesamtbestellung_id']
      , 'gruppen_id' => $gruppen_id
      , 'spalten' => ( PR_COL_NAME | PR_COL_BESTELLMENGE | PR_COL_VPREIS | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME )
      ) );
      $pfand_leer_soll = $vert_row['pfand_leer_brutto_soll'];
      $pfand_voll_soll = $vert_row['pfand_voll_brutto_soll'];
      $pfand_soll = $pfand_leer_soll + $pfand_voll_soll;
      $waren_soll = $vert_row['waren_brutto_soll'];
      $soll = $pfand_soll + $waren_soll;
      $have_pfand = false;
      ?>
      <tr>
        <td valign='top'><b>Bestellung</b></td>
        <td><? echo $vert_row['valuta_trad']; ?></td>
        <td><? echo $vert_row['lieferdatum_trad']; ?></td>
        <td>Bestellung: <? echo $details_link; ?></td>
        <td class='number'>
          <?
            if( abs( $pfand_voll_soll ) > 0.005 ) {
              printf( "%.2lf", $pfand_voll_soll );
              $have_pfand = true;
            }
          ?>
        </td>
        <td class='number'>
          <?
            if( abs( $pfand_leer_soll ) > 0.005 ) {
              printf( "%.2lf", $pfand_leer_soll );
              $have_pfand = true;
            }
          ?>
        </td>
        <td class='number'>
          <?
            if( $have_pfand )
              printf( "%.2lf", $pfandsumme );
          ?>
        </td>
        <td class='number'>
          <? printf( "%.2lf", $waren_soll ); ?>
        </td>
        <td class='number'>
          <div style='font-weight:bold;'><? printf( "%.2lf", $soll ); ?></div>
        </td>
        <td class='number'>
          <? printf( "%.2lf", $summe ); ?>
        </td>
      </tr>
      <?
      $summe -= $soll;
      $pfandsumme -= $pfand_soll;
      $vert_row = mysql_fetch_array($vert_result);
    } else {
      $k_id = $konto_row['konterbuchung_id'];
      ?>
      <tr>
        <td valign='top' style='font-weight:bold;'>
          <?
          if( $konto_row['konterbuchung_id'] >= 0 ) {
            $text = ( $konto_row['summe'] > 0 ? 'Einzahlung' : 'Auszahlung' );
          } else {
            $text = 'Verrechnung';
          }
          if( $k_id ) {
            echo fc_alink( 'edit_buchung', "transaktion_id={$konto_row['id']},text=$text,img=" );
          } else {
            echo $text;
          }
          ?>
        </td>
        <td><? echo $konto_row['valuta_trad']; ?></td>
        <td>
          <? echo $konto_row['date']; ?>
          <div style='font-size:1;'><? echo $konto_row['dienst_name']; ?></div>
        </td>
        <td>
          <div><? echo $konto_row['notiz']; ?></div>
          <div class='oneline'>
            <?
              $k_id = $konto_row['konterbuchung_id'];
              if( $k_id > 0 ) { // bank-transaktion
                // echo "k_id: $k_id";
                ?> Auszug: <?
                $bank_row = sql_get_transaction( $k_id );
                $konto_id = $bank_row['konto_id'];
                $auszug_nr = $bank_row['kontoauszug_nr'];
                $auszug_jahr = $bank_row['kontoauszug_jahr'];
                echo fc_alink( 'kontoauszug', array(
                  'img' => false, 'text' => "$auszug_jahr / $auszug_nr ({$bank_row['kontoname']})"
                , 'title' => 'zum Kontoauszug...'
                , 'konto_id' => $konto_id, 'auszug_jahr' => $auszug_jahr, 'auszug_nr' => $auszug_nr
                ) );
              } else if( $k_id == 0 ) { // bank-transaktion, noch unvollstaendig!
                if( $meinkonto ) {
                  ?> <div class='alert'>noch nich verbucht</div> <?
                } else {
                  ?>
                    <form action='<? echo self_url(); ?>' method="post">
                      <? echo self_post(); ?>
                      <input type="hidden" name="action" value="finish_transaction">
                      <input type="hidden" name="trans_nr" value="<?PHP echo $konto_row['id'] ?>">
                    <select name='konto_id' size='1'>
                      <? echo optionen_konten(); ?>
                      </select>
                    <br>
                      Jahr: <?  number_selector( 'auszug_jahr', 2004, 2011, date('Y') ,"%04d"); ?>
                      / Nr: <input type="text" size='6' name='auszug_nr' />
                    <br>
                      <label>Valuta:</label>
                      <? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?>
                      &nbsp;
                      <input type="submit" value="Best√§tigen ">
                    </form>
                  <?
                }
              } else { // gruppen-gruppen oder gruppen-lieferanten-transaktion:
                // echo "k_id: $k_id";
                $k_row = sql_get_transaction( $k_id );
                $k_gruppen_id = $k_row['gruppen_id'];
                $k_lieferanten_id = $k_row['lieferanten_id'];
                if( $k_gruppen_id > 0 ) {
                  if( $k_gruppen_id == $muell_id ) {
                    if( $meinkonto ) {
                      ?> Verrechnung mit FC-Verrechnungskonto (Gruppe <? echo $muell_id; ?>) <?
                    } else {
                      ?>
                        Verrechnung mit
                        <a href='<? echo self_url('gruppen_id')."&gruppen_id=$muell_id"; ?>'
                        >FC-Konto FC-Verrechnungskonto (Gruppe <? echo $muell_id; ?>) </a>
                      <?
                    }
                  } else {
                    printf( "√úberweisung %s %sGruppe %s%s</td>"
                    , ( $konto_row['summe'] > 0 ? 'von' : 'an' )
                    , ( $meinkonto ? '' : "<a href='" .self_url('gruppen_id'). "&gruppen_id=$k_gruppen_id'>" )
                    , sql_gruppenname( $k_gruppen_id )
                    , ( $meinkonto ? '' : "</a>" )
                    );
                  }
                } else if ( $k_lieferanten_id > 0 ) {
                  ?> √úberweisung an Lieferant <?
                  echo fc_alink( 'lieferantenkonto', array(
                    'img' => false, 'lieferanten_id' => $k_lieferanten_id, 'text' => lieferant_name( $k_lieferanten_id ) ) );
                } else {
                  ?> <div class='warn'>Keine g√ºltige Transaktion</div> <?
                }
              }
              ?>
          </div>
        </td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td class='number'>
          <div style='font-weight:bold'><? printf("%.2lf",$konto_row['summe']); ?></div>
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
      <td colspan='6' style='text-align:right;'>Startsaldo:</td>
      <td class='number'>
        <? printf( "%8.2lf", $pfandsumme ); ?>
      </td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td class='number'>
        <? printf( "%8.2lf", $summe ); ?>
      </td>
    </tr>
  </table>

	 <form name="skip" action="showGroupTransaktions.php">
	    <input type="hidden" name="gruppen_id" value="<?PHP echo $gruppen_id; ?>">
			<input type="hidden" name="gruppen_pwd" value="<?PHP echo $gruppen_pwd; ?>">
			<input type="hidden" name="start_pos" value="<?PHP echo $start_pos; ?>">
			<?PHP 
			   $downButtonScript = "";
			   if ($start_pos > 0 && $start_pos > $size)
				    $downButtonScript="document.forms['skip'].start_pos.value=".($start_pos-$size).";";
				 else if ($start_pos > 0)
				    $downButtonScript="document.forms['skip'].start_pos.value=0;";
						
				 if ($downButtonScript != "")
				    echo "<input type=button value='<' onClick=\"".$downButtonScript." ;document.forms['skip'].submit();\">";
						

			   $upButtonScript = "";
			   if ($num_rows == $size)
				    $upButtonScript="document.forms['skip'].start_pos.value=".($start_pos+$size).";";

				 if ($upButtonScript != "") echo "<input type=button value='>' onClick=\"".$upButtonScript.";document.forms['skip'].submit()\"";
			?>
	 </form>

 
