<?PHP
   
  assert( $angemeldet ) or exit();
  need( isset( $sockelbetrag ) );  // sollte in leitvariablen definiert sein!
   
  setWindowSubtitle( 'Neue Bestellgruppe eintragen' );
  setWikiHelpTopic( 'foodsoft:bestellgruppe_eintragen' );

  nur_fuer_dienst(5);
   
  $msg = '';
  $problems = '';
  $done = FALSE;
  
  // ggf. die neue Gruppe hinzufügen

  if( get_http_var('newName') ) {
    get_http_var('newNummer');
    get_http_var('newAnsprechpartner');
    get_http_var('newMail');
    get_http_var('newTelefon');
    get_http_var('newMitgliederzahl');
        
    if( ( ! ( $newNummer > 0 ) ) || ( $newNummer > 98 ) ) {
      $problems = $problems . "<div class='warn'>Ung&uuml;ltige Gruppennummer!</div>";
    }
  
    // suche $id = $newNummer + n * 1000
    // dabei pruefen, ob noch aktive gruppe derselben nummer existiert:
    $id = $newNummer;
    while( true ) {
      $result = mysql_query( "SELECT * FROM bestellgruppen WHERE id='$id'" );
      if( ! $result ) {
        $problems = $problems . "<div class='warn'>Suche in bestellgruppen fehlgeschlagen: "
                    . mysql_error() . "</div>";
        break;
      }
      $row = mysql_fetch_array( $result );
      if( ! $row )
        break;
      if( $row['aktiv'] > '0' )
        $problems = $problems . "<div class='warn'>Aktive Gruppe der Nummer $newNummer existiert bereits!</div>";
      $id = $id + 1000;
    }

    if ($newName == "")
      $problems = $problems . "<div class='warn'>Die neue Bestellgruppe mu&szlig; einen Name haben!</div>";
    if ( ! ( $newMitgliederzahl >= 1 ) )
      $problems = $problems . "<div class='warn'>Keine g&uuml;ltige Mitgliederzahl angegeben!</div>";

    // bis auf weiteres: Gruppenname beginnt mit Gruppennummer:
    //
    sscanf( $newName, "%d %s", &$n, &$s );
    if( ( ! $s ) || ( $n != $newNummer ) ) {
      $newName = "$newNummer $newName";
      $msg = $msg . "<div class='warn'>Gruppennummer wurde in Namen eingef&uuml;gt</div>";
    }

    // Wenn keine Fehler, dann einfügen...
    if( ! $problems ) {

      // vorläufiges Passwort für die Bestellgruppe erzeugen...
      $pwd = strval(rand(1010,9999));

      if( ! mysql_query(
        "INSERT INTO bestellgruppen 
         (id, aktiv, name, ansprechpartner, email, telefon, mitgliederzahl, passwort)
         VALUES ( $id
                  , 1 
                  , '".mysql_escape_string($newName)."'
                  , '".mysql_escape_string($newAnsprechpartner)."'
                  , '".mysql_escape_string($newMail)."'
                  , '".mysql_escape_string($newTelefon)."'
                  , '".mysql_escape_string($newMitgliederzahl)."'
                  , '".crypt($pwd,$crypt_salt)."')"
      ) ) {
        $problems = $problems . "<div class='warn'>Eintragen der Gruppe fehlgeschlagen:"
                                 .  mysql_error() . "</div>";
      } else {
        $msg = $msg . "
          <div class='ok'>Gruppe erfolgreich angelegt</div>
          <div class='ok'>Vorl&auml;ufiges Passwort: <b>$pwd</b> (bitte notieren!)</div>
        ";
        $done = TRUE;
      }
  
      if( ! $problems ) {
        // gruppe ist angelegt: jetzt sockelbetrag verbuchen!
        $sockelbetrag = - $sockelbetrag * $newMitgliederzahl;
        if( sql_gruppen_transaktion(
            2
          , $id
          , $sockelbetrag
          , "NULL"
          , "NULL"
          , "Sockelbetrag neue Gruppe $newNummer"
          , "NOW()"
        ) ) {
          $msg = $msg . "<div class='ok'>Sockelbetrag $sockelbetrag Euro wurde verbucht.</div>";
        } else {
          $problems = $problems . "<div class='warn'>Verbuchen des Sockelbetrags fehlgeschlagen: "
                                     . mysql_error() . "</div>";
        }
      }

    }
  }
 
  ?>
    <form action='<? echo self_url(); ?>' method='post' class='small_form'>
    <? echo self_post(); ?>
      <fieldset style='width:350px;' class='small_form'>
      <legend>neue Bestellgruppe</legend>
        <? echo $problems; echo $msg; ?>
        <table>
          <tr>
             <td><label>Gruppennummer:</label></td>
             <td>
               <input type='input' size='3' name='newNummer' value='<? echo $newNummer; ?>'>
             </td>
          </tr>
          <tr>
             <td><label>Gruppenname:</label></td>
             <td>
               <input type='input' size='24' name='newName' value='<? echo $newName; ?>'>
             </td>
          </tr>
          <tr>
             <td><label>AnsprechpartnerIn:</label></td>
             <td>
               <input type='input' size='24' name='newAnsprechpartner' value='<? echo $newAnsprechpartner; ?>'>
             </td>
          </tr>
          <tr>
             <td><label>Email-Adresse:</label></td>
             <td>
               <input type='input' size='24' name='newMail' value='<? echo $newMail; ?>'>
             </td>
          </tr>
          <tr>
             <td><label>Telefonnummer:</label></td>
             <td>
               <input type='input' size='24' name='newTelefon' value='<? echo $newTelefon; ?>'>
             </td>
          </tr>
          <tr>
             <td><label>Mitgliederzahl:</label></td>
             <td>
               <input type='input' size='2' value='<? echo $newMitgliederzahl; ?>' name='newMitgliederzahl'>
             </td>
          </tr>
          <tr>
             <td colspan='2' align='center'>
  <?
  if( ! $done ) {
    ?> <input type='submit' value='Einf&uuml;gen'> <?
  } else {
    ?> <input value='OK' type='button' onClick='if(opener) opener.focus();window.close();'> <?
  }
  ?>
             </td>
          </tr>
        </table>
      </fieldset>
    </form>

