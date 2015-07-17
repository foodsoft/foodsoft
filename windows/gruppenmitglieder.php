<?PHP
  
assert( $angemeldet ) or exit();
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

setWikiHelpTopic( 'foodsoft:gruppenmitglieder' );
setWindowSubtitle( 'Gruppenmitglieder' );

need_http_var('gruppen_id','u', 1);
$gruppe = sql_gruppe( $gruppen_id );

$edit_names = FALSE;
$edit_dienst_einteilung = FALSE;
$edit_pwd = FALSE;
if( ( $login_gruppen_id == $gruppen_id ) and ! $readonly ) {
  $edit_names = TRUE;
  $edit_pwd = TRUE;
}
if( hat_dienst(5) and ! $readonly ) {
  $edit_names = TRUE;
  $edit_dienst_einteilung=TRUE;
  $edit_pwd = TRUE;
}

$pwmsg = '';
$avatar_msg = '';

// ggf. Aktionen durchführen (z.B. Gruppe löschen...)
get_http_var('action','w','');
switch( $action ) {
  case 'new_pwd':
    need( $edit_pwd, "keine Berechtigung zur Passwortaenderung!" );
    need_http_var('newPass', 'R');
    need_http_var('newPass2', 'R');
    if($newPass!=$newPass2){
      $pwmsg = "<div class='warn' style='padding:1em;'>Eingaben nicht identisch! (Gruppenpasswort wurde nicht geändert)</div>";
    } else if( strlen( $newPass ) < 4 ) {
      $pwmsg = "<div class='warn' style='padding:1em;'>Passwort zu kurz! (Gruppenpasswort wurde nicht geändert)</div>";
    } else {
      set_password( $gruppen_id, $newPass );
      $pwmsg = "<div class='ok' style='padding:1em;'>Das Gruppenpasswort wurde neu gesetzt</div>";
    }
    break;
  case 'edit':
    need( $edit_names, "keine Berechtigung!" );
    foreach( sql_gruppe_mitglieder( $gruppen_id ) as $row ) {
      $id = $row['gruppenmitglieder_id'];
      get_http_var( "vorname_$id", 'H', $row['vorname'] );
      get_http_var( "name_$id", 'H', $row['name'] );
      get_http_var( "email_$id", 'H', $row['email'] );
      get_http_var( "telefon_$id", 'H', $row['telefon'] );
      get_http_var( "slogan_$id", 'H', $row['slogan'] );
      get_http_var( "url_$id", 'H', $row['url'] );
      get_http_var( "notiz_$id", 'H', $row['notiz'] );
      get_http_var( "avatar_delete_$id", 'u', 0 );
      if( $edit_dienst_einteilung ) {
        get_http_var( "dienst_$id", 'H', $row['diensteinteilung'] );
      } else {
        ${"dienst_$id"} = $row['diensteinteilung'];
      }
      sql_update( 'gruppenmitglieder', $id, array(
        'name' => ${"name_$id"}
      , 'vorname' => ${"vorname_$id"}
      , 'email' => ${"email_$id"}
      , 'telefon' => ${"telefon_$id"}
      , 'diensteinteilung' => ${"dienst_$id"}
      , 'slogan' => ${"slogan_$id"}
      , 'url' => ${"url_$id"}
      , 'notiz' => ${"notiz_$id"}
      ) );

      if( ${"avatar_delete_$id"} ) {
        sql_update( 'gruppenmitglieder', $id, array( 'photo_url' => '' ) );
        $row['photo_url'] = '';
      }

      while( isset($_FILES["avatar_$id"]) ) {
        $avatar_upload = $_FILES["avatar_$id"];
        if( ! $avatar_upload['size'] ) {
          break;
        }
        if( $avatar_upload['error'] ) {
          $avatar_msg .= "<div class='warn' style='padding:1em;'>Hochladen der Bilddatei fehlgeschlagen!</div>";
          break;
        }
        if( filesize( $avatar_upload['tmp_name'] ) > 0x20000 ) {
          $avatar_msg .= "<div class='warn' style='padding:1em;'>Bilddatei zu gross (Limit: 128kB)!</div>";
          break;
        }
        $data = base64_encode( file_get_contents( $avatar_upload['tmp_name'] ) );
        if( ( $avatar_upload['type'] == 'image/jpeg' ) && ! strncmp( $data, '/9j/4', 5 ) ) {
          $mimetype = 'image/jpeg';
        } else if( ( $avatar_upload['type'] == 'image/png' ) && ! strncmp( $data, 'iVBOR', 5 ) ) {
          $mimetype = 'image/png';
        } else {
          $avatar_msg .= "<div class='warn' style='padding:1em;'>Bilddatei: Dateityp nicht unterstützt (bitte nur JPEG oder PNG!)(</div>";
          break;
        }
       $imagesize = getimagesize( $avatar_upload['tmp_name'] );
        if( ! $imagesize ) {
          $avatar_msg .= "<div class='warn' style='padding:1em;'>Kann Bildgröße nicht bestimmen</div>";
          break;
        }
        if( $mimetype === 'image/png' ) {
          if( $imagesize[0] > 128 || $imagesize[1] > 192  ) {
            $avatar_msg .= "<div class='warn' style='padding:1em;'>"
              . "Bild zu groß ({$imagesize[0]} x {$imagesize[1]} Pixel, max. 128 x 192 Pixel)!"
              . "</div>";
            break;
          }
        }
        sql_update( 'gruppenmitglieder', $id, array( 'photo_url' => "data:$mimetype;base64," . $data ) );

        break;
      }
    }
    if( hat_dienst(5) ) {
      get_http_var( 'gruppenname', 'H', $gruppe['name'] );
      get_http_var( 'notiz_gruppe', 'H', $gruppe['notiz_gruppe'] );
      sql_update( 'bestellgruppen', $gruppen_id, array( 'name' => $gruppenname, 'notiz_gruppe' => $notiz_gruppe ) );
    }
    break;
  case 'delete':
    fail_if_readonly();
    nur_fuer_dienst(5);
    need_http_var('person_id','u');
    sql_delete_group_member($person_id, $gruppen_id);
    break;
  case 'insert':
    fail_if_readonly();
    nur_fuer_dienst(5);
    need_http_var('newVorname', 'H');
    need_http_var('newName', 'H');
    need_http_var('newMail', 'H');
    need_http_var('newTelefon', 'H');
    need_http_var('dienst_', 'H');
    sql_insert_group_member($gruppen_id, $newVorname, $newName, $newMail, $newTelefon, $dienst_ );
    break;
}

$gruppenname = sql_gruppenname( $gruppen_id );

open_fieldset( 'small_form', '', 'Gruppe '.$gruppe['name'].' ('.$gruppe['gruppennummer'].')' );

medskip();

echo $avatar_msg;

memberform_view( $gruppen_id, $edit_names, $edit_dienst_einteilung );

medskip();
if( hat_dienst(5) and ! $readonly ) {
  open_fieldset( 'small_form', '', 'Neues Gruppenmitglied eintragen', 'off' );
    open_form( '', 'action=insert' );
      open_table('layout');
        form_row_text( 'Vorname:', 'newVorname', 16 );
        form_row_text( 'Name:', 'newName', 20 );
        form_row_text( 'Email:', 'newMail', 24 );
        form_row_text( 'Telefon:', 'newTelefon', 20 );
        open_tr(); open_td( 'label', '', 'Diensteinteilung:'); open_td( 'kbd', '', dienst_selector('') ); 
        open_tr(); open_td( 'right', "colspan='2'" ); submission_button();
      close_table();
    close_form();
  close_fieldset();
}
medskip();

echo $pwmsg;
if( $edit_pwd ) {
  open_fieldset( 'small_form medskip', '', 'Passwort aendern', 'off' );
    open_form( '', 'action=new_pwd' );
      open_table('layout');
        open_tr(); open_td( 'label', '', 'Passwort:');
                   open_td( 'kbd', '', "<input type='password' size='24' name='newPass'>" );
        open_tr(); open_td( 'label', '', 'nochmal das Passwort:');
                   open_td( 'kbd', '', "<input type='password' size='24' name='newPass2'>" );
        open_tr(); open_td( 'right', "colspan='2'" ); submission_button();
      close_table();
    close_form();
  close_fieldset();
}
medskip();

if( hat_dienst(4,5) or ( $gruppen_id == $login_gruppen_id ) )
  open_div( 'smallskip right', '', fc_link( 'gruppenkonto', "gruppen_id=$gruppen_id,text=Gruppenkonto..." ) );
close_fieldset();

?>
