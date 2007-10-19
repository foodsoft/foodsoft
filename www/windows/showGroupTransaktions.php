<?PHP

 assert($angemeldet);
 
 //Vergleicht das Datum der beiden mysql-records
 //gibt +1 zurück, wenn Datum in $konto älter ist
 //gibt 0 zurück, wenn Daten gleich sind
 //gibt -1 zurück, wen Datum in $veteil älter ist
 function compare_date($konto, $verteil){
 	//Kein weiterer Eintrag in Konto
 	if(!$konto) return 1;
	if(!$verteil) return -1;
 	$konto_date = $konto['date'];
	$verteil_date = $verteil['datum'];
	// Zeit abschneiden
	$temp = explode("<", $konto_date);
	//echo "konto-datum ".$temp[0];
	$k = explode(".", $temp[0]);
	$temp = explode("<", $verteil_date);
	//echo "verteil-datum ".$temp[0];
	$v = explode(".", $temp[0]);
	//Jahr vegleichen
	if($k[2]<$v[2]){
		return 1;
	} else if($k[2]>$v[2]){
		return -1;
	} else {
		//Monat vergleichen
		if($k[1]<$v[1]){
			return 1;
		} else if($k[1]>$v[1]){
			return -1;
		} else {
			//Tag vergleichen
			if($k[0]<$v[0]){
				return 1;
			} else if($k[0]>$v[0]){
				return -1;
			} else {
				return 0;
			}
		}
	}
 }

$meinkonto = ( $area == 'meinkonto' );

if( $meinkonto ) {
  $gruppen_id = $login_gruppen_id;
  $self_fields['gruppen_id'] = $gruppen_id;
  $gruppen_name = sql_gruppenname( $gruppen_id );
  ?>
    <h1>Mein Konto: Kontoausz&uuml;ge von Gruppe <? echo $gruppen_name; ?></h1>
    <div id='option_menu'></div>

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
    sqlGroupTransaction( 0, $login_gruppen_id, $amount );
  }

} else {
  nur_fuer_dienst(4,5);
  get_http_var( 'gruppen_id', 'u', false, true );
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

  if( get_http_var( 'trans_nr', 'u' ) ) {
    need_http_var( 'auszug_jahr', 'u' );
    need_http_var( 'auszug_nr', 'u' );
    sqlUpdateTransaction( $trans_nr, $auszug_nr, $auszug_jahr );
  }

  if( get_http_var( 'summe_einzahlung', 'f' ) ) {
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'auszug_jahr', 'u' );
    need_http_var( 'auszug_nr', 'u' );
    sqlGroupTransaction( '0', $gruppen_id , $summe_einzahlung , $auszug_nr , $auszug_jahr
      , 'Einzahlung' , "$year-$month-$day" );
  }

  if( get_http_var( 'summe_transfer', 'f' ) ) {
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'notiz', 'M' );
    need_http_var( 'to_group_id', 'u' );
    $to_group_name = sql_gruppenname( $to_group_id );
    sqlGroupTransaction( '2', $gruppen_id, -$summe_transfer
      , NULL, NULL, "Transfer an $to_group_name: $notiz", "$year-$month-$day" );
    sqlGroupTransaction( '2', $to_group_id, $summe_transfer
      , '',  "Transfer von $gruppen_name: $notiz", "$year-$month-$day" );
  }

  if( get_http_var( 'summe_sonstiges', 'f' ) ) {
    need_http_var( 'day', 'u' );
    need_http_var( 'month', 'u' );
    need_http_var( 'year', 'u' );
    need_http_var( 'notiz', 'M' );
    sqlGroupTransaction( '2', $gruppen_id, $summe_sonstiges, NULL, NULL,  $notiz, "$year-$month-$day" );
    // TODO: Transaktionart?
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
      <span style='padding-left:1em;' title='Einzahlung der Gruppe auf das Bankkonto der Foodcoop'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('einzahlung_form').style.display='block';
                 document.getElementById('transfer_form').style.display='none';
                 document.getElementById('sonstige_form').style.display='none';"
      ><b>Einzahlung</b>
      </span>

      <span style='padding-left:1em;' title='Ã¼berweisung auf ein anderes Gruppenkonto'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('einzahlung_form').style.display='none';
                 document.getElementById('transfer_form').style.display='block';
                 document.getElementById('sonstige_form').style.display='none';"
      ><b>Transfer an andere Gruppe</b>
      </span>

      <span style='padding-left:1em;' title='Sonstige Transaktionen'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('einzahlung_form').style.display='none';
                 document.getElementById('transfer_form').style.display='none';
                 document.getElementById('sonstige_form').style.display='block';"
      ><b>sonstige Transaktion</b>
      </span>

      <div id='einzahlung_form' style='display:none;'>
        <form method='post' class='small_form' action='<? echo self_url(); ?>'>
          <? echo self_post(); ?>
          <fieldset>
            <legend>
              Einzahlung
            </legend>
            <table>
              <tr>
                <td>Kontoeingang:</td>
                <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
              </tr><tr>
                <td>Kontoauszug Jahr:</td>
                <td><? number_selector( 'auszug_jahr', 2004, 2011, date('Y') ,"%04d"); ?>
                / Nr: <input type="text" size="6" name="auszug_nr"></td>
              </tr><tr>
                <td>Summe:</td>
                <td>
                  <input type="text" name="summe_einzahlung" value="">
                  <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
                </td>
              </tr>
            </table>
          </fieldset>
        </form>
      </div>

      <div id='transfer_form' style='display:none;'>
        <form method='post' class='small_form' action='<? echo self_url(); ?>'>
          <? echo self_post(); ?>
          <fieldset>
            <legend>
              Transfer von Gruppe <? echo $gruppen_name; ?> an andere Gruppe
            </legend>
            <table>
              <tr>
                <td>Datum:</td>
                <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
              </tr><tr>
                <td>Notiz:</td>
                <td><input type="text" size="60" name="notiz"></td>
              </tr></tr>
                <td>an Gruppe:</td>
                <td>
                  <select name='to_group_id' size='1'>
                    <? echo optionen_gruppen( false, false, false, "(bitte Gruppe wÃ¤hlen)" ); ?>
                  </select>
                </td>
              </tr><tr>
                <td>Summe:</td>
                <td>
                  <input type="text" name="summe_transfer" value="">
                  <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
                </td>
              </tr>
            </table>
          </fieldset>
        </form>
      </div>

      <div id='sonstige_form' style='display:none;'>
        <form method='post' class='small_form' action='<? echo self_url(); ?>'>
          <? echo self_post(); ?>
          <fieldset>
            <legend>
              Sonstige Transaktion
            </legend>
            <table>
              <tr>
                <td>Datum:</td>
                <td><? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?></td>
              </tr><tr>
                <td>Notiz:</td>
                <td><input type="text" size="60" name="notiz"></td>
              </tr><tr>
                <td>Summe:</td>
                <td>
                  <input type="text" name="summe_sonstiges" value="">
                  <input style='margin-left:2em;' type='submit' name='Ok' value='Ok'>
                </td>
              </tr>
            </table>
          </fieldset>
        </form>
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
	$type2str[1] = "Bestellung";
	$type2str[2] = "Sonstiges";
	


//    if( ( $gesamtbestellung_id = $HTTP_GET_VARS['gesamtbestellung_id'] ) ) {
//      echo "details fuer bestellung: $gesamtbestellung_id";
//      echo "<div class='warn'>noch in arbeit!</div>";
//      exit(12);
//    }
   $cols = 6;
   ?>
	 <table class="numbers">
	    <tr>
			   <th>Typ</th>
				 <th>Eingabezeit</th>
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

         $vert_result = sql_gesamtpreise($gruppen_id);
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
					    //echo "   <td>".$vert_row['datum']."</td>\n";
					    echo "   <td></td>\n";
					    echo "   <td>Bestellung: ".$vert_row['name']." </td>";
					    echo "   <td class='mult'> <b> ".sprintf("%.2lf", -$vert_row['gesamtpreis'])."</b></td>";
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
              $summe += $vert_row['gesamtpreis'];
				 	    $vert_row = mysql_fetch_array($vert_result);
					    if(!$vert_row){
					    	$no_more_vert = true;
					    }

			            } else {

					    echo "<tr>\n";
							echo "   <td valign='top'><b>".$type2str[$konto_row['type']]."</b></td>\n";
							echo "   <td>".$konto_row['date']."</td>\n";
							
							if ($konto_row['type'] == 0) {
                     ?>
                          <td> <table style='font-size:10pt' class='inner'>
                           <tr><td>Einzahldatum:</td>
                           <td><?echo $konto_row['kontobewegungs_datum']?></td>
                           </tr>
                           <tr><td>Auszug:</td><td>
                             <?
                        if($meinkonto or $konto_row['kontoauszugs_nr']>0){
			   echo "{$konto_row['kontoauszugs_jahr']} / {$konto_row['kontoauszugs_nr']}";
                        } else {
			   ?>
						<form action='<? echo self_url(); ?>' method="post">
              <? echo self_post(); ?>
							   <input type="hidden" name="trans_nr" value="<?PHP echo $konto_row['id'] ?>">
                 Jahr: <?  number_selector( 'auszug_jahr', 2004, 2011, date('Y') ,"%04d"); ?>
                 / Nr: <input type="text" size='6' name='auszug_nr' />
							   <input type="submit" value="BestÃ¤tigen ">
						   </form>
			   <?
                        }
                        ?></td></tr>
                           </table> </td>
                     <?
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

 
