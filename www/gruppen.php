<h1>Gruppenverwaltung...</h1>

<?PHP
  
assert( $angemeldet ) or exit();
$problems="";
$msg="";


get_http_var( 'optionen', 'u', 0, true );

// ggf. Aktionen durchführen (z.B. Gruppe löschen...)
get_http_var('action','w','');
$readonly and $action = '';

  ?>
    <form action='<? echo self_url(); ?>' name='reload_form' method='post'>
      <? echo self_post(); ?>
      <input type='hidden' name='gruppen_id' value=''>
      <input type='hidden' name='action' value=''>
    </form>
    <div style='padding-bottom:2em;'>
    <table class='menu' style='padding-bottom:2em;'>
      <tr>
        <th>Optionen</th>
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
      <? if( $dienst == 4 or $dienst == 5 ) { ?>
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
      <? } ?>
    </table>
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

// Hier ändern. Code in views verschieben, details in editGroup verschieben
   $show_member_details=FALSE;

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
      echo"
            <a class='png' style='padding:0pt 1ex 0pt 1ex;'
              href=\"javascript:neuesfenster('index.php?window=gruppen_mitglieder&gruppen_id=$id','gruppenmitglieder');\">
             <img src='img/b_browse.png' border='0' title='Personen' alt='Personen'/>
            </a>
      ";
      if( ! $readonly ) {
        if( ( $dienst == 4 ) || ( $dienst == 5 ) ) {
          echo "
            <a class='png' style='padding:0pt 1ex 0pt 1ex;'
              href=\"javascript:neuesfenster('index.php?window=showGroupTransaktions&gruppen_id={$row['id']}','kontoblatt');\">
             <img src='img/chart.png' border='0' title='Kontotransaktionen' alt='Kontotransaktionen'/>
            </a>
          ";
        } elseif( $login_gruppen_id == $id ) {
          ?>
            <a class='png' style='padding:0pt 1ex 0pt 1ex;'  href='index.php?area=meinkonto'>
             <img src='img/chart.png' border='0' title='Mein Konto' alt='Mein Konto'/>
            </a>
          <?
        }
        if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $id ) ) {
          ?>
       <!-- nicht mehr da, da Formular veraltet ToDo: Gruppennamen hier editierbar machen
            <a class='png' style='padding:0pt 1ex 0pt 1ex;'
            href="javascript:window.open('index.php?window=editGroup&gruppen_id=<?// echo $row['id']; ?>','insertGroup','width=390,height=420,left=200,top=100').focus();">
            <img src='img/b_edit.png' border='0' alt='Gruppendaten Ã¤ndern' title='Gruppendaten Ã¤ndern'/></a>
        -->
          <?
        }
        // loeschen nur wenn
        // - kontostand 0
        // - mitgliederzahl 0 (wegen rueckbuchung sockelbetrag!)
        if(    ( $dienst == 5 )
            && ( abs($kontostand) < 0.005 )
            && ( $row['mitgliederzahl'] == 0 )
            && ( ! in_array( $id, $specialgroups ) )
        ) {
          ?>
            <a class='png' href="javascript:if(confirm('Soll die Gruppe wirklich GELÃ–SCHT werden?')){
              document.forms['reload_form'].action.value='delete';
              document.forms['reload_form'].gruppen_id.value='<? echo $row['id']; ?>';
              document.forms['reload_form'].submit();}">
            <img src='img/b_drop.png' border='0' alt='Gruppe lÃ¶schen' title='Gruppe lÃ¶schen'/></a>
          <?
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
	<?  membertable_view(sql_gruppen_members($id)); ?>
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

