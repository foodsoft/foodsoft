<?PHP

  require_once('code/config.php');
  require_once('code/err_functions.php');
  require_once('code/connect_MySQL.php');
  require_once('code/login.php');
  need_http_var('gruppen_id');

  // $onload_str = "";       // befehlsstring der beim laden ausgeführt wird...

  if( $gruppen_id != $login_gruppen_id )
    nur_fuer_dienst(4,5);   // nur dienst 4 und 5 ediert fremde gruppen

  $msg = '';
  $pwmsg = '';
  $problems = '';

  $result = mysql_query( "SELECT * FROM bestellgruppen WHERE id=$gruppen_id" );
  if( ! ( $row = mysql_fetch_array( $result ) ) )
    $problems = $problems . "<div class='warn'>Konnte Gruppendaten nicht laden</div>";

  if( get_http_var('newName') ) {
    get_http_var('newAnsprechpartner');
    get_http_var('newMail');
    get_http_var('newTelefon');
    get_http_var('newMitgliederzahl');
    get_http_var('buchesockelbetrag');

    if ($newName == "")
      $problems = $problems . "<div class='warn'>Die neue Bestellgruppe mu&szlig; einen Name haben!</div>";

    // bis auf weiteres: Gruppenname beginnt mit Gruppennummer:
    //
    sscanf( $newName, "%d %s", &$n, &$s );
    if( ( ! $s ) || ( $n != $gruppen_id % 1000 ) ) {
      $msg = $msg . "<div class='warn'>Gruppenname sollte mit Gruppennummer beginnen!</div>";
    }

    // nur dienst 4 und 5 buchen sockelbetraege:
    //
    if( $dienst != 4 and $dienst != 5 )
      $newMitgliederzahl = $row['mitgliederzahl'];
    else
      if ( ! (
                   ( ( $newMitgliederzahl == '0' ) || ( $newMitgliederzahl >= 1 ) )
                && ( $newMitgliederzahl < 100 ) ) )
        $problems = $problems . "<div class='warn'>Keine g&uuml;ltige Mitgliederzahl angegeben!</div>";

    // Wenn keine Fehler, dann ändern...
    if( ! $problems ) {
      if( ! mysql_query(
        "UPDATE bestellgruppen
         SET name='".mysql_escape_string($newName)."'
           , ansprechpartner='".mysql_escape_string($newAnsprechpartner)."'
           , email='".mysql_escape_string($newMail)."'
           , telefon='".mysql_escape_string($newTelefon)."'
           , mitgliederzahl='".mysql_escape_string($newMitgliederzahl)."'
         WHERE id=".mysql_escape_string($gruppen_id)
      ) ) {
        $problems = $problems . "<div class='warn'>&Auml;dern der Gruppe fehlgeschlagen:"
                                 .  mysql_error() . "</div>";
      } else {
        $msg = $msg . "<div class='ok'>&Auml;nderungen gespeichert</div>";
      }

      if( ( ! $problems ) && ( $newMitgliederzahl != $row['mitgliederzahl'] ) ) {
        if( $buchesockelbetrag ) {
          $sockeldiff = 6.0 * ($row['mitgliederzahl'] - $newMitgliederzahl);
          if( ! mysql_query(
            " INSERT INTO gruppen_transaktion (
                type
              , gruppen_id
              , eingabe_zeit
              , summe
              , kontoauszugs_nr
              , notiz
              , kontobewegungs_datum
              , dienstkontrollblatt_id
            ) VALUES (
              2
            , $gruppen_id
            , NOW()
            , $sockeldiff
            , ''
            , 'Korrektur Sockelbetrag bei Aenderung Mitgliederzahl {$row['mitgliederzahl']} -> $newMitgliederzahl'
            , ''
            , $dienstkontrollblatt_id
            ) " 
          ) ) {
            $problems = $problems . "<div class='warn'>Verbuchen Aenderung Sockelbetrag fehlgeschlagen: "
                                       . mysql_error() . "</div>";
          } else {
            $msg = $msg . "<div class='ok'>Aenderung Sockelbetrag: $sockeldiff Euro wurden verbucht.</div>";
          }
        } else {
          $msg = $msg . "<div class='warn'>Sockelbetrag: Aenderung $sockeldiff wurde <b>nicht</b> verbucht!</div>";
        }
      }
    }
  }

	//ggf. Aktionen durchführen
  elseif ( get_http_var('action') ) {
			// neues Passwort anlegen...
			if ($action == "new_pwd") {
			   $pwd = strval(rand(1000,9999));
				 mysql_query("UPDATE bestellgruppen SET passwort='".mysql_escape_string(crypt($pwd,35464))."' WHERE id=".mysql_escape_string($gruppen_id)) or error(__LINE__,__FILE__,"Konnte das Gruppenpasswort nicht zurücksetzen.",mysql_error());
				 $pwmsg = $pwmsg .  "<div class='ok' style='padding:1em;'>Das neu angelegte Gruppenpasswort ist: <b>$pwd</b></div>";
			}
  }

  $result = mysql_query( "SELECT * FROM bestellgruppen WHERE id=$gruppen_id" );
  if( ! ( $row = mysql_fetch_array( $result ) ) )
    $problems = $problems . "<div class='warn'>Konnte Gruppendaten nicht laden</div>";

  $title = "Bestellgruppe edieren";
  $subtitle = "Bestellgruppe " . $gruppen_id % 1000 . " edieren";
  require_once('head.php');

  echo "
	  <form action='editGroup.php' method='post' class='small_form'>
      <input type='hidden' name='gruppen_id' value='$gruppen_id'>
      <fieldset style='width:350px;' class='small_form'>
       <legend>Stammdaten Gruppe " . $gruppen_id % 1000 . " </legend>
       $msg
       $problems
  		 <table>
			   <tr>
				   <td><label>Gruppenname:</label></td>
					 <td><input type='input' size='24' name='newName' value='{$row['name']}'></td>
				 </tr>
			   <tr>
				    <td><label>AnsprechpartnerIn:</label></td>
						<td><input type='input' size='24' name='newAnsprechpartner' value='{$row['ansprechpartner']}'></td>
				 </tr>				 
			   <tr>
				    <td><label>Email-Adresse:</label></td>
						<td><input type='input' size='24' name='newMail' value='{$row['email']}'></td>
				 </tr>				 
			   <tr>
				    <td><label>Telefonnummer:</label></td>
						<td><input type='input' size='24' name='newTelefon' value='{$row['telefon']}'></td>
				 </tr>
  ";
  if( $hat_dienst_IV or $hat_dienst_V ) {
    echo "
			   <tr>
				    <td><label>Mitgliederzahl:</b></td>
						<td style='white-space:nowrap;'>
              <input type='input' size='2' name='newMitgliederzahl' value='{$row['mitgliederzahl']}'
                onfocus=\"document.getElementById('checksockelbuchen').style.display='inline';\">
             <span style='display:none;' id='checksockelbuchen'>
               <label style='padding-left:2ex;'>Sockelbetrag buchen:</label>
               <input style='margin:0pt;padding:0pt;' type='checkbox' name='buchesockelbetrag' value='1' checked></input>
             </span>
            </td>
				 </tr>				 
    ";
  }
  echo "
				 <tr>
				    <td colspan='2' align='center'><input type='submit' value='&Auml;ndern'></td>
				 </tr>
			 </table>
      </fieldset>
	   </form>
  ";
	 
  if( $hat_dienst_IV or $hat_dienst_V ) {
    echo "
	   <form action='editGroup.php' name='optionen' class='small_form'>
			 <input type='hidden' name='gruppen_id' value='$gruppen_id'>	 
			 <input type='hidden' name='action'>
       <fieldset style='width:350px;' class='small_form'>
	  	   <legend>Optionen</legend>
         $pwmsg
         <table style='width:350px;' class='menu'>
			     <tr>
			        <td><input type='button' value='neues Passwort' onClick=\"document.forms['optionen'].action.value='new_pwd'; document.forms['optionen'].submit();\"></td>
					    <td class='smalfont'>Gruppenpasswort zur&uuml;cksetzen...</td>
			     </tr>
	        </table>
       </fieldset>
	  </form>
    ";
  }
?>

</body>
</html>
