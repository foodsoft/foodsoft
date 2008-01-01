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

$meinkonto = ( $area == 'meinkonto' );

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
    " . optionen_gruppen( false, false, $gruppen_id, ( $gruppen_id ? false : "(bitte Gruppe w√§hlen)"), false , $specialgroups ) . "
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
	$result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Gruppendaten nicht lesen.",mysql_error());
	$bestellgruppen_row = mysql_fetch_array($result);
	
	// wieviele Kontenbewegungen werden ab wo angezeigt...
	if (isset($HTTP_GET_VARS['start_pos'])) $start_pos = $HTTP_GET_VARS['start_pos']; else $start_pos = 0;
	//Funktioniert erstmal mit der Mischung aus Automatischer Berechung und manuellen Eintr‰gen nicht
  //FIXME: vielleicht ggf. start/enddatum waehlbar machen? oder immer ganze jahre?
	$size          = 2000;
	 
	
   $cols = 8;
   ?>
	 <table class="numbers">
	    <tr>
			   <th>Typ</th>
				 <th>Valuta</th>
				 <th>Buchung</th>
				 <th>Informationen</th>
				 <th colspan='2'>Betrag (Pfand)</th>
				 <th colspan='2'>Summe</th>
			</tr>
      <tr class='summe'>
        <td colspan='<? echo $cols-2; ?>' style='text-align:right;'>Kontostand:</td>
        <td class='mult'><? printf( "%8.2lf", $kontostand ); ?></td>
        <td class='unit'><? printf( "(%8.2lf)", $pfandkontostand ); ?></td>
      </tr>
			<?PHP

			   // $result = mysql_query("SELECT id, type, summe, kontobewegungs_datum, kontoauszugs_nr, kontoauszugs_jahr, notiz, DATE_FORMAT(eingabe_zeit,'%d.%m.%Y  <br> <font size=1>(%T)</font>') as date FROM gruppen_transaktion WHERE gruppen_id=".mysql_escape_string($gruppen_id)." ORDER BY  eingabe_zeit DESC LIMIT ".mysql_escape_string($start_pos).", ".mysql_escape_string($size).";") or error(__LINE__,__FILE__,"Konnte Gruppentransaktionsdaten nicht lesen.",mysql_error());
			   $result = sql_get_group_transactions( $gruppen_id );
         $num_rows = mysql_num_rows($result);

         $vert_result = sql_bestellungen_soll_gruppe($gruppen_id);
         $summe = $kontostand;
         $pfandsumme = $pfandkontostand;
				 $no_more_vert = false;
				 $no_more_konto=false;
				 $konto_row = mysql_fetch_array($result);
				 $vert_row = mysql_fetch_array($vert_result);
				 //Gehe zum ersten Eintrag in Bestellzuordnung, der nach dem Eintrag in Konto liegt
				 //while(compare_date($konto_row, $vert_row)==+1){
				 	//$vert_row = mysql_fetch_array($vert_result);
				 //}
				 while (!($no_more_vert && $no_more_konto)) {
				    //Mische Eintr‰ge aus Kontobewegungen und Verteilzuordnung zusammen
            if(compare_date($konto_row, $vert_row)==1 && !$no_more_vert){
				    		//Eintrag in Konto ist ƒlter -> Verteil ausgeben
              $details_url = "index.php?window=bestellschein"
              . "&gruppen_id=$gruppen_id"
              . "&bestell_id={$vert_row['gesamtbestellung_id']}"
              . "&spalten=" . ( PR_COL_NAME | PR_COL_BESTELLMENGE | PR_COL_VPREIS
                                | PR_COL_LIEFERMENGE | PR_COL_ENDSUMME );
              $pfand = -$vert_row['pfand'];
              ?>
					      <tr>
					      <td valign='top'><b>Bestellung</b></td>
					      <td><? echo $vert_row['valuta_trad']; ?></td>
					      <td><? echo $vert_row['lieferdatum_trad']; ?></td>
					      <td>Bestellung: <a
                  href="javascript:neuesfenster('<? echo $details_url; ?>','bestellschein');"
                  ><? echo $vert_row['name']; ?></a></td>
					      <td class='mult'><b><? printf("%.2lf", -$vert_row['soll']); ?></b></td>
					      <td class='unit'> <?
                  if( abs($pfand) >= 0.005 )
                    printf("(%.2lf)", $pfand); ?></td>
                <!-- <td class='unit'>
                 <a class='png' style='padding:0pt 1ex 0pt 1ex;'
                   href="javascript:neuesfenster('<? echo $details_url; ?>','bestellschein');">
                  <img src='img/b_browse.png' border='0' title='Details zur Bestellung' alt='Details zur Bestellung'/>
                 </a>
                  </td>
                 -->
                  <td class='mult'><? printf( "%8.2lf", $summe ); ?></td>
                  <td class='unit'><? printf( "(%8.2lf)", $pfandsumme ); ?></td>
                </tr>
              <?
              $summe += $vert_row['soll'];
              $pfandsumme -= $pfand;
				 	    $vert_row = mysql_fetch_array($vert_result);
					    if(!$vert_row){
					    	$no_more_vert = true;
					    }

            } else {
              // eintrag aus gruppen_transaktion anzeigen:
              //
              ?> <tr>
                  <td valign='top'><b>
              <?
              if( $konto_row['konterbuchung_id'] >= 0 ) {
                echo $konto_row['summe'] > 0 ? 'Einzahlung' : 'Auszahlung';
              } else {
                echo "Verrechnung";
              }
              ?> </td>
                 <td><? echo $konto_row['valuta_trad']; ?></td>
                 <td><div><? echo $konto_row['date']; ?></div>
                     <div style='font-size:1;'><? echo $konto_row['dienst_name']; ?></div></td>
                  <td><? echo $konto_row['notiz']; ?><br>
              <?
              $k_id = $konto_row['konterbuchung_id'];
              if( $k_id > 0 ) { // bank-transaktion
                // echo "k_id: $k_id";
                ?> Auszug: <?
                $bank_row = sql_get_transaction( $k_id );
                $konto_id = $bank_row['konto_id'];
                $auszug_nr = $bank_row['kontoauszug_nr'];
                $auszug_jahr = $bank_row['kontoauszug_jahr'];
                echo "<a href=\"javascript:neuesfenster(
                      'index.php?window=konto&konto_id=$konto_id&auszug_jahr=$auszug_jahr&auszug_nr=$auszug_nr'
                      ,'konto'
                    );\">$auszug_jahr / $auszug_nr ({$bank_row['kontoname']})</a>
                  ";
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
                    ?> 
                      √úberweisung an Lieferant
                      <a href="javascript:neuesfenster(
                          'index.php?window=lieferantenkonto&lieferanten_id=<? echo $k_lieferanten_id; ?>'
                        , 'lieferantenkonto')"
                      ><? echo lieferant_name( $k_lieferanten_id ); ?></a>
                    <?
                } else {
                  ?> <div class='warn'>Keine g√ºltige Transaktion</div> <?
                }
              }
              $pfand = $konto_row['pfand'];
              ?>
                </td>
                <td class='mult'>
                  <b><? printf("%.2lf",$konto_row['summe']); ?></b></td>
					        <td class='unit'> <?
                  if( abs($pfand) > 0.005 )
                    printf("(%.2lf)", $pfand); ?></td>
                  <td class='mult'><? printf( "%.2lf", $summe ); ?></td>
					      <td class='unit'><? printf("(%.2lf)", $pfandsumme); ?></td>
                </tr>
              <?
              $summe -= $konto_row['summe'];
              $pfandsumme -= $pfand;
				 	    $konto_row = mysql_fetch_array($result);
					    if(!$konto_row){
					    	$no_more_konto = true;
					    }

				 	}
				 }
			?>
      <tr class='summe'>
        <td colspan='<? echo $cols-2; ?>' style='text-align:right;'>Startsaldo:</td>
        <td class='mult'><? printf( "%8.2lf", $summe ); ?></td>
        <td class='unit'><? printf( "(%8.2lf)", $pfandsumme ); ?></td>
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

 
