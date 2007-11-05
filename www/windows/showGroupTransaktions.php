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

$meinkonto = ( $area == 'meinkonto' );

if( $meinkonto ) {
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
      >Ãœberweisung eintragen...</span>
    </div>

    <div id='transaction_form' style='display:none;padding-bottom:1em;'>
      <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <fieldset>
      <legend>
        <img src='img/close_black_trans.gif' class='button'
        onclick="document.getElementById('transaction_button').style.display='block';
                 document.getElementById('transaction_form').style.display='none';">
        Ãœberweisung eintragen
      </legend>
      Ich habe heute <input type="text" size="12" name="amount"/>
      Euro <input type="submit" value="Ã¼berwiesen"/>
      </fieldset>
      </form>
    </div>
    <?

    if( get_http_var( 'amount', 'f' ) ) {
      sql_gruppen_transaktion( 0, $login_gruppen_id, $amount, NULL, NULL, "Einzahlung" );
    }
  }

} else {
  nur_fuer_dienst(4,5);
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
    " . optionen_gruppen( false, false, $gruppen_id, ( $gruppen_id ? false : "(bitte Gruppe wÃ¤hlen)"), false , $specialgroups ) . "
      </select></td>"
  );
  if( ! $gruppen_id )
    return;
  $gruppen_name = sql_gruppenname( $gruppen_id );

  get_http_var( 'action', 'w', '' );

  if( get_http_var( 'trans_nr', 'u' ) ) {
    need_http_var( 'auszug_jahr', 'u' );
    need_http_var( 'auszug_nr', 'u' );
    need_http_var( 'konto_id', 'u' );
    sql_finish_transaction( $trans_nr, $konto_id, $auszug_nr, $auszug_jahr, 'gebuchte Einzahlung' );
  }

  if( $action == 'zahlung_gruppe' ) {
    need_http_var( 'betrag', 'f' );
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'auszug_jahr', 'u' );
    need_http_var( 'auszug_nr', 'u' );
    need_http_var( 'konto_id', 'u' );
    sql_doppelte_transaktion(
      array(
        'konto_id' => -1, 'gruppen_id' => $gruppen_id
      , 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , array(
        'konto_id' => $konto_id, 'gruppen_id' => $gruppen_id
      , 'auszug_nr' => "$auszug_nr", 'auszug_jahr' => "$auszug_jahr" )
    , $betrag
    , "$year-$month-$day"
    , "Einzahlung"
    );
  }

  if( $action == 'umbuchung_gruppe_gruppe' ) {
    need_http_var( 'betrag', 'f' );
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'notiz', 'M' );
    need_http_var( 'nach_gruppen_id', 'u' );
    $nach_gruppen_name = sql_gruppenname( $nach_gruppen_id );
    sql_doppelte_transaktion(
      array( 'konto_id' => -1 , 'gruppen_id' => $gruppen_id )
    , array( 'konto_id' => -1 , 'gruppen_id' => $nach_gruppen_id )
    , $betrag
    , "$year-$month-$day"
    , "Transfer von $gruppen_name an $nach_gruppen_name: $notiz"
    );
  }

  if( $action == 'zahlung_gruppe_lieferant' ) {
    need_http_var( 'betrag', 'f' );
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'notiz', 'M' );
    need_http_var( 'lieferanten_id', 'u' );
  }


  
//   if( get_http_var( 'summe_sonstiges', 'f' ) ) {
//     need_http_var( 'day', 'u' );
//     need_http_var( 'month', 'u' );
//     need_http_var( 'year', 'u' );
//     need_http_var( 'notiz', 'M' );
//     sqlGroupTransaction( '2', $gruppen_id, $summe_sonstiges, NULL, NULL,  $notiz, "$year-$month-$day" );
//     // TODO: Transaktionart?
//   }

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

      <span style='padding-left:1em;' title='Ã¼berweisung auf ein anderes Gruppenkonto'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('einzahlung_form').style.display='none';
                 document.getElementById('gruppegruppe_form').style.display='block';
                 document.getElementById('gruppelieferant_form').style.display='none';"
      ><b>Transfer an andere Gruppe</b>
      </span>

      <span style='padding-left:1em;' title='Ã¼berweisung von Gruppe an Lieferant'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('einzahlung_form').style.display='none';
                 document.getElementById('gruppegruppe_form').style.display='none';
                 document.getElementById('gruppelieferant_form').style.display='block';"
      ><b>Ãœberweisung von Gruppe an Lieferant</b>
      </span>

      <div id='einzahlung_form' style='display:none;'>
        <? echo formular_buchung_gruppe_bank( $gruppen_id ); ?>
      </div>

      <div id='gruppegruppe_form' style='display:none;'>
        <? echo formular_buchung_gruppe_gruppe( $gruppen_id, 0 ); ?>
      </div>

      <div id='gruppelieferant_form' style='display:none;'>
        <? formular_buchung_gruppe_lieferant( $gruppen_id, 0 ); ?>
      </div>

    </fieldset>

  <?
}

  $kontostand = kontostand($gruppen_id);

  // aktuelle Gruppendaten laden
	$result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte Gruppendaten nicht lesen.",mysql_error());
	$bestellgruppen_row = mysql_fetch_array($result);
	
	// wieviele Kontenbewegungen werden ab wo angezeigt...
	if (isset($HTTP_GET_VARS['start_pos'])) $start_pos = $HTTP_GET_VARS['start_pos']; else $start_pos = 0;
	//Funktioniert erstmal mit der Mischung aus Automatischer Berechung und manuellen Einträgen nicht
	$size          = 2000;
	 
	$type2str[0] = "Einzahlung";
	$type2str[1] = "Ãœberweisung";
	$type2str[2] = "Sonstiges";
	
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

			   // $result = mysql_query("SELECT id, type, summe, kontobewegungs_datum, kontoauszugs_nr, kontoauszugs_jahr, notiz, DATE_FORMAT(eingabe_zeit,'%d.%m.%Y  <br> <font size=1>(%T)</font>') as date FROM gruppen_transaktion WHERE gruppen_id=".mysql_escape_string($gruppen_id)." ORDER BY  eingabe_zeit DESC LIMIT ".mysql_escape_string($start_pos).", ".mysql_escape_string($size).";") or error(__LINE__,__FILE__,"Konnte Gruppentransaktionsdaten nicht lesen.",mysql_error());
			   $result = sql_get_group_transactions( $gruppen_id );
         $num_rows = mysql_num_rows($result);

         $vert_result = sql_bestellungen_soll_gruppe($gruppen_id);
         $summe = $kontostand;
				 $no_more_vert = false;
				 $no_more_konto=false;
				 $konto_row = mysql_fetch_array($result);
				 $vert_row = mysql_fetch_array($vert_result);
				 //Gehe zum ersten Eintrag in Bestellzuordnung, der nach dem Eintrag in Konto liegt
				 //while(compare_date($konto_row, $vert_row)==+1){
				 	//$vert_row = mysql_fetch_array($vert_result);
				 //}
				 while (!($no_more_vert && $no_more_konto)) {
				    //Mische Einträge aus Kontobewegungen und Verteilzuordnung zusammen
            if(compare_date($konto_row, $vert_row)==1 && !$no_more_vert){
				    		//Eintrag in Konto ist Älter -> Verteil ausgeben
					    echo "<tr>\n";
					    echo "   <td valign='top'><b>Bestell Abrechnung</b></td>\n";
					    echo "   <td>".$vert_row['valuta_trad']."</td>\n";
					    echo "   <td> </td>\n";
					    echo "   <td>Bestellung: ".$vert_row['name']." </td>";
					    echo "   <td class='mult'> <b> ".sprintf("%.2lf", -$vert_row['soll'])."</b></td>";
              $details_url = "index.php?window=bestellschein"
              . "&gruppen_id=$gruppen_id"
              . "&bestell_id={$vert_row['gesamtbestellung_id']}"
              . "&spalten=" . ( PR_COL_NAME | PR_COL_BESTELLMENGE | PR_COL_VPREIS
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
              $summe += $vert_row['soll'];
				 	    $vert_row = mysql_fetch_array($vert_result);
					    if(!$vert_row){
					    	$no_more_vert = true;
					    }

            } else {
              echo "
                <tr>
                  <td valign='top'><b>{$type2str[$konto_row['type']]}</b></td>
                  <td>{$konto_row['valuta_trad']}</td>
                  <td>{$konto_row['date']}</td>
              ";
              if ($konto_row['type'] == 0) {
                ?>
                  <td>
                    <table style='font-size:10pt' class='inner'>
                      <tr><td>Auszug:</td><td>
                <?
                if( $konto_row['konterbuchung_id'] > 0 ) {
                  $bank_row = sql_get_transaction( $konto_row['konterbuchung_id'] );
                  $konto_id = $bank_row['konto_id'];
                  $auszug_nr = $bank_row['kontoauszug_nr'];
                  $auszug_jahr = $bank_row['kontoauszug_jahr'];
                  echo "<a href=\"javascript:neuesfenster(
                      'index.php?window=kontoauszug&konto_id=$konto_id&auszug_jahr=$auszug_jahr&auszug_nr=$auszug_nr'
                      ,'kontoauszug'
                    );\">$auszug_jahr / $auszug_nr ({$bank_row['kontoname']})</a>
                  ";
                } else {
                  if( $meinkonto ) {
                    ?> <div class='warn'>noch nich verbucht</div> <?
                  } else {
                    ?>
                      <form action='<? echo self_url(); ?>' method="post">
                        <? echo self_post(); ?>
                        <input type="hidden" name="trans_nr" value="<?PHP echo $konto_row['id'] ?>">
                        <select name='konto_id' size='1'>
                        <? echo optionen_konten(); ?>
                        </select>
                        <br>
                        Jahr: <?  number_selector( 'auszug_jahr', 2004, 2011, date('Y') ,"%04d"); ?>
                        / Nr: <input type="text" size='6' name='auszug_nr' />
                        <input type="submit" value="BestÃ¤tigen ">
                      </form>
                    <?
                  }
                }
                ?> </td></tr> </table> </td> <?
							} else if ($konto_row['type'] == 1) {
							   echo "<td>[noch nicht unterstützt]</td>";
		    } else {
							   echo "<td>".$konto_row['notiz']."</td>";
							}
							
              ?>
							  <td class='mult'>
                  <b><? printf("%.2lf",$konto_row['summe']); ?></b></td>
                  <td class='unit'>&nbsp;</td>
					      <td class='number'><? printf( "%8.2lf", $summe ); ?></td>
							</tr>
              <?
              $summe -= $konto_row['summe'];
				 	    $konto_row = mysql_fetch_array($result);
					    if(!$konto_row){
					    	$no_more_konto = true;
					    }

				 	}
				 }
			?>
      <tr class='summe'>
        <td colspan='<? echo $cols-1; ?>' style='text-align:right;'>Startsaldo:</td>
        <td class='number'><? printf( "%8.2lf", $summe ); ?></td>
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

 
