<?PHP
  
assert( $angemeldet ) or exit();
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

need_http_var('gruppen_id','u', 1);

          //predefine all edit modes as false
$edit_names = FALSE;
$edit_dienst_einteilung=FALSE;
$edit_pwd = FALSE;
if( ( $login_gruppen_id == $gruppen_id ) and ! $readonly ) {
  $edit_names = TRUE;
  $edit_pwd = TRUE;
}
if( $hat_dienst_V and ! $readonly ) {
  $edit_names = TRUE;
  $edit_dienst_einteilung=TRUE;
  $edit_pwd = TRUE;
}

?> <h1>Gruppenmitglieder f&uuml;r Gruppe <? echo sql_gruppenname($gruppen_id)." (".sql_gruppennummer($gruppen_id).")"; ?></h1><?

$pwmsg = '';

// ggf. Aktionen durchführen (z.B. Gruppe löschen...)
get_http_var('action','w','');
switch( $action ) {
  case 'new_pwd':
    need( $edit_pwd, "keine Berechtigung zur Passwortaenderung!" );
    need_http_var('newPass', 'R');
    need_http_var('newPass2', 'R');
    if($newPass!=$newPass2){
      $pwmsg =  "<div class='warn' style='padding:1em;'>Eingaben nicht identisch! (Gruppenpasswort wurde nicht geändert)</div>";
    } else if( strlen( $newPass ) < 4 ) {
      $pwmsg =  "<div class='warn' style='padding:1em;'>Passwort zu kurz! (Gruppenpasswort wurde nicht geändert)</div>";
    } else {
      set_password( $gruppen_id, $newPass );
      $pwmsg =  "<div class='ok' style='padding:1em;'>Das Gruppenpasswort wurde neu gesetzt</div>";
    }
    break;
  case 'edit':
    need( $edit_names, "keine Berechtigung!" );
    $rows = sql_gruppen_members( $gruppen_id );
    while( $row = mysql_fetch_array($rows) ) {
      $id = $row['id'];
      get_http_var( "vorname_$id", 'H', $row['vorname'] );
      get_http_var( "name_$id", 'H', $row['name'] );
      get_http_var( "email_$id", 'H', $row['email'] );
      get_http_var( "telefon_$id", 'H', $row['telefon'] );
      if( $edit_dienst_einteilung ) {
        get_http_var( "dienst_$id", 'H', $row['diensteinteilung'] );
      } else {
        ${"dienst_$id"} = $row['diensteinteilung'];
      }
      sql_update_gruppen_member( $id, ${"name_$id"}, ${"vorname_$id"}, ${"email_$id"}, ${"telefon_$id"}, ${"dienst_$id"} );
    }
    break;
  case 'delete':
    fail_if_readonly();
    nur_fuer_dienst(5);
    need_http_var('person_id','u');
    sql_delete_group_member($person_id, $gruppen_id);
    break;
  case 'add':
    fail_if_readonly();
    nur_fuer_dienst(5);
    need_http_var('newVorname', 'H');
    need_http_var('newName', 'H');
    need_http_var('newMail', 'H');
    need_http_var('newTelefon', 'H');
    need_http_var('newDienst', 'H');
    sql_insert_group_member($gruppen_id, $newVorname, $newName, $newMail, $newTelefon, $newDienst[0]);
    break;
}


if( $hat_dienst_V and ! $readonly ) {
  echo switchable_form( 'newmember', "Neues Gruppenmitglied eintragen", false, "
    <input type='hidden' name='action' value='add'>
    <table>
      <tr><td>Vorname:</td><td><input type='text' size='12' name='newVorname'/></td></tr>
      <tr><td>Name:</td><td> <input type='text' size='12' name='newName'/></td></tr>
      <tr><td>Mail:</td><td> <input type='text' size='12' name='newMail'/></td></tr>
      <tr><td>Telefon:</td><td> <input type='text' size='12' name='newTelefon'/></td></tr>
      <tr><td>Diensteinteilung:</td><td>'.dienst_selector('').'</td></tr>
      <tr><td></td><td style='text-align:right'><input type='submit' value='Anlegen'/></td></tr>
    </table>
  " );
}

if( $edit_pwd ) {
  echo switchable_form( 'password', 'Passwort aendern', $pwmsg, "
    <input type='hidden' name='action' value='new_pwd'>
    $pwmsg
    <table class='menu'>
      <tr> <td>Passwort:</td><td><input type='password' size='24' name='newPass'></td></tr>
      <tr> <td>nochmal das Passwort:</td><td><input type='password' size='24' name='newPass2'></td></tr>
      <tr><td></td><td style='text-align:right;'><input type='submit' value='Passwort &auml;ndern'></td></tr>
    </table>
  " );
}

membertable_view( $gruppen_id, $edit_names , $edit_dienst_einteilung);

