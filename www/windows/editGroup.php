<?PHP

  assert( $angemeldet ) or exit();
  need( isset( $sockelbetrag ) );  // sollte in leitvariablen definiert sein!

  setWindowSubtitle( 'Bestellgruppe edieren' );
  setWikiHelpTopic( 'foodsoft:bestellgruppe_edieren' );

  need_http_var( 'gruppen_id','u', true );

  if( $gruppen_id != $login_gruppen_id )
    nur_fuer_dienst(4,5);   // nur dienst 4 und 5 ediert fremde gruppen

  $msg = '';
  $pwmsg = '';
  $problems = '';

  $row = sql_gruppendaten( $gruppen_id );

  if( get_http_var('newName') ) {
    get_http_var('newAnsprechpartner');
    get_http_var('newMail');
    get_http_var('newTelefon');
    get_http_var('newMitgliederzahl');
    get_http_var('newDiensteinteilung');
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
    if( $dienst != 4 and $dienst != 5 ){
      $newMitgliederzahl = $row['mitgliederzahl'];
      $newDiensteinteilung = $row['diensteinteilung'];
    }else {
      if ( ! (
                   ( ( $newMitgliederzahl == '0' ) || ( $newMitgliederzahl >= 1 ) )
                && ( $newMitgliederzahl < 100 ) ) )
        $problems = $problems . "<div class='warn'>Keine g&uuml;ltige Mitgliederzahl angegeben!</div>";
    }

    // Wenn keine Fehler, dann ändern...
    if( ! $problems ) {
      if( ! mysql_query(
        "UPDATE bestellgruppen
         SET name='".mysql_escape_string($newName)."'
           , ansprechpartner='".mysql_escape_string($newAnsprechpartner)."'
           , email='".mysql_escape_string($newMail)."'
           , telefon='".mysql_escape_string($newTelefon)."'
           , mitgliederzahl='".mysql_escape_string($newMitgliederzahl)."'
           , diensteinteilung='".mysql_escape_string($newDiensteinteilung)."'
         WHERE id=".mysql_escape_string($gruppen_id)
      ) ) {
        $problems = $problems . "<div class='warn'>&Auml;dern der Gruppe fehlgeschlagen:"
                                 .  mysql_error() . "</div>";
      } else {
        $msg = $msg . "<div class='ok'>&Auml;nderungen gespeichert </div>";
      }

      if( ( ! $problems ) && ( $newMitgliederzahl != $row['mitgliederzahl'] ) ) {
        if( $buchesockelbetrag ) {
          $sockeldiff = $sockelbetrag * ($row['mitgliederzahl'] - $newMitgliederzahl);
          if( sql_gruppen_transaktion(
              2
            , $gruppen_id
            , $sockeldiff
            , "NULL"
            , "NULL"
            , "Korrektur Sockelbetrag bei Ã„nderung Mitgliederzahl {$row['mitgliederzahl']} -> $newMitgliederzahl"
            , "NOW()"
          ) ) {
            $msg = $msg . "<div class='ok'>Aenderung Sockelbetrag: $sockeldiff Euro wurden verbucht.</div>";
          } else {
            $problems = $problems . "<div class='warn'>Verbuchen Aenderung Sockelbetrag fehlgeschlagen: "
                                       . mysql_error() . "</div>";
          }
        } else {
          $msg = $msg . "<div class='warn'>Sockelbetrag: Ã„nderung $sockeldiff wurde <b>nicht</b> verbucht!</div>";
        }
      }
    }
  }

	//ggf. Aktionen durchführen
  elseif ( get_http_var('action') ) {
			// neues Passwort anlegen...
			if ($action == "new_pwd") {
			   $pwd = strval(rand(1000,9999));
         set_password( $gruppen_id, $pwd );
				 $pwmsg = $pwmsg .  "<div class='ok' style='padding:1em;'>Das neu angelegte Gruppenpasswort ist: <b>$pwd</b></div>";
			}
  }

  // gruppendaten (ggf nochmal neu!) laden:
  //
  $row = sql_gruppendaten( $gruppen_id );

  ?>
	  <form action='<? echo self_url(); ?>' method='post' class='small_form'>
      <? echo self_post(); ?>
      <fieldset style='width:350px;' class='small_form'>
       <legend>Stammdaten Gruppe <? echo $gruppen_id % 1000; ?></legend>
       <? echo $msg; echo $problems; ?>
  		 <table>
			   <tr>
				   <td><label>Gruppenname:</label></td>
					 <td><input type='input' size='24' name='newName' value='<? echo $row['name']; ?>'></td>
				 </tr>
			   <tr>
				    <td><label>AnsprechpartnerIn:</label></td>
						<td><input type='input' size='24' name='newAnsprechpartner' value='<? echo $row['ansprechpartner']; ?>'></td>
				 </tr>				 
			   <tr>
				    <td><label>Email-Adresse:</label></td>
						<td><input type='input' size='24' name='newMail' value='<? echo $row['email']; ?>'></td>
				 </tr>				 
			   <tr>
				    <td><label>Telefonnummer:</label></td>
						<td><input type='input' size='24' name='newTelefon' value='<? echo $row['telefon']; ?>'></td>
				 </tr>
  <?
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
				 
			   <tr>
				    <td><label>Diensteinteilung:</b></td>
						<td style='white-space:nowrap;'>
              <select name='newDiensteinteilung'>";
    foreach($_SESSION['DIENSTEINTEILUNG'] as $dienst){
	    $select_str="";
	    if($dienst == $row['diensteinteilung']) $select_str="selected";
	       echo "<option value='".$dienst."' ".$select_str.">".$dienst."</option>\n";  
    }
    ?>
	         
	      </select>
            </td>
				 </tr>				 
    <?
  }
  ?>
				 <tr>
				    <td colspan='2' align='center'><input type='submit' value='&Auml;ndern'></td>
				 </tr>
			 </table>
      </fieldset>
	   </form>
  <?
	 
  if( $hat_dienst_V ) {
    ?>
      <form action='<? echo self_url(); ?>' name='optionen' class='small_form' method='post'>
      <? echo self_post(); ?>
			 <input type='hidden' name='action' value=''>
       <fieldset style='width:350px;' class='small_form'>
	  	   <legend>Optionen</legend>
         <? echo $pwmsg; ?>
         <table style='width:350px;' class='menu'>
			     <tr>
			        <td><input type='button' value='neues Passwort'
                onClick="document.forms['optionen'].action.value='new_pwd';
                document.forms['optionen'].submit();">
              </td>
					    <td class='smalfont'>Gruppenpasswort zur&uuml;cksetzen...</td>
			     </tr>
	        </table>
       </fieldset>
      </form>
    <?
  }
?>

