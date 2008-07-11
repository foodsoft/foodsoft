<h1>Gruppenverwaltung...</h1>
<?PHP
  
assert( $angemeldet ) or exit();
$problems="";
$msg="";


get_http_var( 'optionen', 'u', 0, true );
$show_member_details= $optionen & GRUPPEN_OPT_DETAIL;
	

// ggf. Aktionen durchführen (z.B. Gruppe löschen...)
get_http_var('action','w','');
$readonly and $action = '';

?> <div style='padding-bottom:2em;'> <?
  if( $dienst == 4 or $dienst == 5 ) {
    ?>
      <table class='menu' style='padding-bottom:2em;'>
      <tr>
        <th>Optionen</th>
      </tr>
      <tr>
        <td>
          <input type='checkbox'
            <? if( $optionen & GRUPPEN_OPT_DETAIL ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ GRUPPEN_OPT_DETAIL );
            ?>';"
            title='Details f&uuml;r Gruppenmitglieder anzeigen'
          >Details f&uuml;r Gruppenmitglieder anzeigen
        </td>
      </tr>
      <tr>
        <td>
          <input type='checkbox'
            <? if( $optionen & GRUPPEN_OPT_INAKTIV ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ GRUPPEN_OPT_INAKTIV);
            ?>';"
            title='Auch inaktive/gelÃ¶schte Gruppen anzeigen?'
          > inaktive Gruppen zeigen
        </td>
      </tr>
      <tr>
        <td>
          <input type='checkbox'
            <? if( $optionen & GRUPPEN_OPT_UNGEBUCHT ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ GRUPPEN_OPT_UNGEBUCHT );
            ?>';"
            title='Nur Gruppen mit ungebuchten Einzahlungen anzeigen?'
          > nur ungebuchte Einzahlungen
        </td>
      </tr>
      <tr>
        <td>
          <span class='radiooption'>
            <input type='radio' name='schuldoderguthaben'
            <? if( ( $optionen & (GRUPPEN_OPT_SCHULDEN | GRUPPEN_OPT_GUTHABEN) ) == 0 ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen & ~(GRUPPEN_OPT_SCHULDEN | GRUPPEN_OPT_GUTHABEN) );
            ?>';"
            > alle
          </span>
          <span class='radiooption'>
            <input type='radio' name='schuldoderguthaben'
            <? if( $optionen & GRUPPEN_OPT_SCHULDEN ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", (($optionen | GRUPPEN_OPT_SCHULDEN) & ~ GRUPPEN_OPT_GUTHABEN);
            ?>';"
            > Gruppen mit Schulden
          </span>
          <span class='radiooption'>
            <input type='radio' name='schuldoderguthaben'
            <? if( $optionen & GRUPPEN_OPT_GUTHABEN ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", (($optionen | GRUPPEN_OPT_GUTHABEN) & ~GRUPPEN_OPT_SCHULDEN);
            ?>';"
            > Gruppen mit Guthaben
          </span>
        </td>
      </tr>
      </table>
    <? } ?>
    </div>

  <?

  if( $hat_dienst_V and ! $readonly ) {
    ?>
    <div id='transaction_button' style='padding-bottom:1em;'>
    <span class='button'
      onclick="document.getElementById('transaction_form').style.display='block';
               document.getElementById('transaction_button').style.display='none';"
      >Neue Gruppe...</span>
    </div>

    <div id='transaction_form' style='display:none;'>
      <form method='post' class='small_form' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <input type='hidden' name='action' value='insert'>
      <fieldset class='small_form'>
      <legend>
        <img src='img/close_black_trans.gif' class='button'
        onclick="document.getElementById('transaction_form').style.display='none';
                 document.getElementById('transaction_button').style.display='block';">
	Neue Gruppe
      </legend>
      Nr: <input type="text" size="4" name="newNumber" />
      Name: <input type="text" size="12" name="newName" />
      <input type="submit" value="Anlegen" />
      </fieldset>
      </form>
    </div>

    <?
	
    if( $action == 'delete' ) {
      nur_fuer_dienst(5);
      need_http_var('gruppen_id','u');
    
      $row = sql_gruppendaten( $gruppen_id );
    
      $kontostand = kontostand( $row['id'] );
      if( abs($kontostand) > 0.005 ) {
        ?>
          <div class='warn'>Kontostand (<? echo $kontostand; ?> EUR) ist nicht null: L&ouml;schen nicht m&ouml;glich!</div>
        <?
      } elseif( $row['mitgliederzahl'] != 0 ) {
        ?>
          <div class='warn'>Mitgliederzahl ist nicht null: L&ouml;schen nicht m&ouml;glich (Sockelbetrag!)</div>
          <div class='warn'>(bitte erst auf null setzen, um Sockelbetrag zu verbuchen!)</div>
        <?
      } else {
        sql_update( 'bestellgruppen', $gruppen_id, array( 'aktiv' => 0 ) );
      }
   }
    if( $action == 'insert' ) {
	    need_http_var('newNumber', 'u');
		  need_http_var('newName','H');
		      // vorläufiges Passwort für die Bestellgruppe erzeugen...
		      $pwd = strval(rand(1010,9999));

		      if(sql_insert_group($newNumber, $newName, $pwd)){
			//ToDo Forward to corresponding 
			      //gruppen_mitglieder
			$msg = $msg . "
			  <div class='ok'>Gruppe erfolgreich angelegt</div>
			  <div class='ok'>Vorl&auml;ufiges Passwort: <b>$pwd</b> (bitte notieren!)</div>
			";
		      }
	  }
  }

if( $action == 'cancel_payment' ) {
  need_http_var( 'transaction_id', 'u' );
  // echo "id: $gruppen_id, trans: $transaction_id <br>";
  $trans = sql_get_transaction( -$transaction_id );
  if( $trans['gruppen_id'] != $login_gruppen_id )
    nur_fuer_dienst(4,5);
  doSql( "DELETE FROM gruppen_transaktion WHERE id=$transaction_id" );
}


// Hier ändern. Code in views verschieben, details in editGroup verschieben
   //$show_member_details=TRUE;

  echo $problems; echo $msg; 

  ?>

 
 
    <br><br>

    <table class='liste'>
      <tr>
         <th>Nr</th>
         <th>Gruppenname</th>
	 <!--
         <th>AnsprechpartnerIn</th>
         <th>Mail</th>
         <th>Telefon</th>
         -->
         <th>Kontostand</th>
         <th>Mitgliederzahl</th>
	 <!--
         <th>Diensteinteilung</th>
         -->
         <th>Optionen</th>
      </tr>
  <?

  $summe = 0;
  $mitglieder_summe = 0;
  $result = ( $optionen & GRUPPEN_OPT_INAKTIV ? sql_bestellgruppen() : sql_aktive_bestellgruppen() );
  while ($row = mysql_fetch_array($result)) {
    $id = $row['id'];
    if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $id ) ) {
      $kontostand = sprintf( '%10.2lf', kontostand($row['id']) );
      if( $optionen & GRUPPEN_OPT_SCHULDEN )
        if( $kontostand >= 0 )
          continue;
      if( $optionen & GRUPPEN_OPT_GUTHABEN )
        if( $kontostand <= 0 )
          continue;
      $offene_einzahlungen = sql_ungebuchte_einzahlungen( $id );
      if( $optionen & GRUPPEN_OPT_UNGEBUCHT )
        if( mysql_num_rows($offene_einzahlungen) < 1 )
          continue;
      $summe += $kontostand;
    }
    $nr = $row['gruppennummer'];
    echo "
      <tr>
        <td>$nr</td>
        <td>{$row['name']}</td>
	";
      if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $id ) ) {
        echo "<td align='right'>$kontostand</td>";
	} else {
		
        echo "<td></td>";
	}
    echo"
      <td class='number'>{$row['mitgliederzahl']}</td>
      <td>
    ";
    $mitglieder_summe += $row['mitgliederzahl'];
    if( $row['aktiv'] > 0 ) {
      echo fc_alink( 'gruppenmitglieder', "gruppen_id=$id,title=Personen,img=img/b_browse.png" );
      if( ! $readonly ) {
        if( ( $dienst == 4 ) || ( $dienst == 5 ) ) {
          echo fc_alink( 'gruppenkonto', "gruppen_id=$id,title=Kontoblatt,img=img/euro.png" );
        } elseif( $login_gruppen_id == $id ) {
          echo fc_alink( 'gruppenkonto', "gruppen_id=$id,title=Kontoblatt,img=img/euro.png,meinkonto=1" );
        }
        if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $id ) ) {
          if( mysql_num_rows($offene_einzahlungen) > 0 ) {
            ?>
              <table>
                <tr>
                  <th colspan='3'>ungebuchte Einzahlungen:</th>
                </tr>
              <? while( $trans = mysql_fetch_array( $offene_einzahlungen ) ) { ?>
                <tr>
                  <td><? echo $trans['eingabedatum_trad']; ?></td>
                  <td><? printf( "%.2lf", $trans['summe'] ); ?></td>
                  <td>
                    <? echo fc_action( array( 'action' => 'cancel_payment', 'transaction_id' => $trans['id']
                       , 'img' => 'img/b_drop.png', 'title' => 'L&ouml;schen?'
                       ) );
                    ?>
                  </td>
                </tr>
              <? } ?>
              </table>
            <?
          }
        }
        // loeschen nur wenn
        // - kontostand 0
        // - mitgliederzahl 0 (wegen rueckbuchung sockelbetrag!)
        if(    ( $dienst == 5 )
            && ( abs($kontostand) < 0.005 )
            && ( $row['mitgliederzahl'] == 0 )
            && ( ! in_array( $id, $specialgroups ) )
        ) {
          echo fc_action( array( 'action' => 'delete', 'gruppen_id' => $row['id']
          , 'img' => 'img/b_drop.png', 'title' => 'Gruppe l&ouml;schen?'
          , 'confirm' => 'Soll die Gruppe wirklich GEL&Ouml;SCHT werden?'
          ) );
        }
      }
    } else {
      ?>(inaktiv)<?
    }
    ?> </td> </tr> <?

    if($show_member_details){
?>
	<tr>
          <td/>
          <td colspan="4">
	<?  membertable_view(sql_gruppen_members($id), FALSE,FALSE, FALSE); ?>
         <td/>
<?
    }
  }

  if( $dienst == 4 or $dienst == 5 ) {
    ?>
      <tr class='summe'>
        <td colspan='2' style='text-align:right;'>Summe:</td>
        <td class='number'><? printf( "%.2lf", $summe ); ?></td>
        <td class='number'><? echo $mitglieder_summe; ?></td>
        <td colspan='1'>&nbsp;</td>
      </tr>
    <?
  }
?>

</table>

