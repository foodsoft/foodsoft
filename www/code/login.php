<?php
//
// login.php
//
// anmeldescript:
//  - prueft, ob benutzer schon angemeldet (per cookie)
//  - verarbeitet neuanmeldungen
//  - per "login=logout" wird ein logout (loeschen des cookie) erzwungen
//  - falls nicht angemeldet: anmeldeformular wird ausgegeben
//  - falls $from_dokuwiki==true wird index.php?window=wiki aufgerufen
//
// bei erfolgreicher anmeldung werden global gesetzt:
//  - $angemeldet == TRUE
//  - $login_gruppen_id
//  - $login_gruppen_name
//  - $session_id
//  - $dienst (0, 1, 3, 4 oder 5)
// falls $dienst > 0 ausserdem:
//  - $coopie_name
//  - $dienstkontrollblatt_id

function init_login() {
  global $angemeldet, $session_id, $login_gruppen_id, $login_gruppen_name
       , $dienst, $dienstkontrollblatt_id, $coopie_name;
  $angemeldet=FALSE;
  $session_id = 0;
  $login_gruppen_id = FALSE;
  $login_gruppen_name = FALSE;
  $dienst = 0;
  $dienstkontrollblatt_id = FALSE;
  $coopie_name= FALSE;
}

function logout() {
  init_login();
  unset( $_COOKIE['foodsoftkeks'] );
  setcookie( 'foodsoftkeks', '0', 0, '/' );
}

init_login();
$problems = '';

$telefon ='';
$name ='';
$notiz ='';

// pruefen, ob schon eingeloggt:
//
if( isset( $_COOKIE['foodsoftkeks'] ) && ( strlen( $_COOKIE['foodsoftkeks'] ) > 1 ) ) {
  sscanf( $_COOKIE['foodsoftkeks'], "%u_%s", &$session_id, &$cookie );
  $row = sql_select_single_row( "SELECT * FROM sessions WHERE id=$session_id", true );
  if( ! $row ) {
    $problems .= "<div class='warn'>nicht angemeldet</div>";
  } elseif( $cookie != $row['cookie'] ) {
    $problems .= "<div class='warn'>Fehler im Keks: nicht angemeldet</div>";
  } else {
    // anmeldung ist gueltig:
    $login_gruppen_id = $row['login_gruppen_id'];
    $dienst = $row['dienst'];
    $dienstkontrollblatt_id = $row['dienstkontrollblatt_id'];
    $login_gruppen_name = sql_gruppenname( $login_gruppen_id );
  }
  if( ! in_array( $dienst, array( 0, 1, 3, 4, 5 ) ) )
    $problems = $problems .  "<div class='warn'>interner fehler: ungueltiger dienst</div>";
  if( $dienst > 0 ) {
    if( $dienstkontrollblatt_id > 0 ) {
      ( $row =  current( sql_dienstkontrollblatt( $dienstkontrollblatt_id ) ) )
        or $problems = $problems .  "<div class='warn'>Dienstkontrollblatt-Eintrag nicht gefunden</div>";
      $coopie_name = $row['name'];
    } else {
      $problems = $problems .  "<div class='warn'>interner fehler: ungueltige dienstkontrollblatt_id</div>";
    }
  }
  if( ! $problems ) {  // login ok, weitermachen...
    $angemeldet = TRUE;
  } else {  // irgendwas war falsch... zurueck auf los:
    logout();
  }
}

// pruefen, ob neue login daten uebergeben werden:
//
get_http_var( 'login', 'w', '' );
switch( $login ) {
  case 'login': 
    get_http_var( 'login_gruppen_id', 'u' )
      or $problems .= "<div class='warn'>FEHLER: keine Gruppe ausgewaehlt</div>";
    get_http_var( 'passwort','R' )
      or $problems .= "<div class='warn'>FEHLER: kein Passwort angegeben</div>";
    get_http_var( 'dienst', 'u' )
      or $problems .= "<div class='warn'>FEHLER: kein Dienst ausgewaehlt</div>";

    if( ! in_array( $dienst, array( 0, 1, 3, 4, 5 ) ) ) {
      $problems .= "<div class='warn'>FEHLER: kein gueltiger Dienst angegeben</div>";
    }

    if( $dienst != 0 ) {
      get_http_var( 'coopie_name', 'H', '' );
      if( ! $coopie_name || ( strlen( $coopie_name ) < 2 ) ) {
        $problems = $problems . "<div class='warn'>FEHLER: kein Name angegeben</div>";
      }
      get_http_var( 'telefon', 'H', '' );
      get_http_var( 'notiz', 'H', '' );
    }

    if( ! $problems ) {
      if( $gruppe = check_password( $login_gruppen_id, $passwort ) ) {
        $login_gruppen_name = $gruppe['name'];
      } else {
        $problems .= "<div class='warn'>FEHLER: Passwort leider falsch</div>";
      }
    }

    if ( ( ! $problems ) && ( $dienst > 0 ) ) {
      $dienstkontrollblatt_id = dienstkontrollblatt_eintrag(
        false, $login_gruppen_id, $dienst, $coopie_name, $telefon, $notiz 
      );
    } else {
      $dienstkontrollblatt_id = 0;
    }

    if( ! $problems ) {
      // alles ok: neue session erzeugen:
      $cookie = random_hex_string( 5 );
      $session_id = sql_insert( 'sessions', array( 
        'cookie' => $cookie
      , 'login_gruppen_id' => $login_gruppen_id
      , 'dienst' => $dienst
      , 'dienstkontrollblatt_id' => $dienstkontrollblatt_id
      ) );
      $keks = $session_id.'_'.$cookie;
      need( setcookie( 'foodsoftkeks', $keks, 0, '/' ), "setcookie() fehlgeschlagen" );
      $angemeldet = TRUE;
      logger( 'successful login' );
    }
    break;
  case 'logout':
    $problems .= "<div class='ok'>Abgemeldet!</div>";
  case 'silentlogout':
    // ggf. noch  dienstkontrollblatt-Eintrag aktualisieren:
    if( $dienst > 0 and $dienstkontrollblatt_id > 0 ) {
      get_http_var('coopie_name','H','');
      get_http_var('telefon','H','');
      get_http_var('notiz','H','');
      dienstkontrollblatt_eintrag(
        $dienstkontrollblatt_id, $login_gruppen_id, $dienst, $coopie_name, $telefon, $notiz 
      );
    }
    logout();
    break;
}

if( $angemeldet )
  return;

// ab hier: benutzer ist nicht eingeloggt; wir setzen alles zurueck und zeigen das anmeldeformular:

logout();  // nicht korrekt angemeldet: alles zuruecksetzen...
require_once("head.php");
setWikiHelpTopic( ':' );

if( isset( $from_dokuwiki ) && $from_dokuwiki ) {
  $form_action="$foodsoftdir/index.php?window=wiki";
} else {
  $form_action="$foodsoftdir/index.php";
}

open_div( 'kommentar', '', $motd );
open_form( "url=$form_action", 'login=login' );
  open_fieldset( 'small_form', "style='padding:2em;width:800px;'", 'Anmelden' );
    if( "$problems" )
      echo "$problems";
    open_div( 'kommentar', "style='padding:1em;'", 'Anmeldung für die Foodsoft und fürs Doku-Wiki der Foodsoft:' );
    open_div( 'newfield', '', "
      <label>Gruppe:</label>
      <select size='1' name='login_gruppen_id'>
        ". optionen_gruppen() ."
      </select>
      <label style='padding-left:4em;'>Passwort:</label>
        <input type='password' size='8' name='passwort' value=''>
    " );
    open_div( 'newfield', '', 'Ich mache gerade...' );
    open_table();
        open_td();
          ?> <input class='checkbox' type='radio' name='dienst' value='0'
              onclick='dienstform_off();'
              <? if (!$dienst) echo ' checked'; ?> >
              <label>keinen Dienst</label> <?
        open_td();
          ?> <input class='checkbox' type='radio' name='dienst' value='1'
              onclick='dienstform_on();'
              <? if ($dienst==1) echo ' checked'; ?> >
              <label title='Verteiler'>Dienst I/II</label> <?
        open_td();
          ?> <input class='checkbox' type='radio' name='dienst' value='3'
               onclick='dienstform_on();'
               <? if ($dienst==3) echo ' checked'; ?> >
               <label title='Kellerdienst'>Dienst III</label> <?
        open_td();
          ?> <input class='checkbox' type='radio' name='dienst' value='4'
              onclick='dienstform_on();'
              <? if ($dienst==4) echo ' checked'; ?> >
              <label title='Abrechnung'>Dienst IV</label> <?
        open_td();
          ?> <input class='checkbox' type='radio' name='dienst' value='5'
              onclick='dienstform_on();'
              <? if ($dienst==5) echo ' checked'; ?> >
              <label title='Mitgliederverwaltung'>Dienst V</label> <?
    close_table();
    open_div( 'kommentar', "id='nodienstform' style='display:" . ( $dienst ? 'none' : 'block' ) .";'" );
      ?> Wenn du nur bestellen oder dein Gruppenkonto einsehen möchtest, brauchst Du hier keinen Dienst auszuwählen. <?
    close_div();
    open_div( '', "id='dienstform' style='display:" . ( $dienst ? 'block' : 'none' ) .";'" );
      open_div( 'kommentar', '', "
        Wenn Du Dich für einen Dienst anmeldest, kannst Du zusätzliche
        Funktionen der Foodsoft nutzen; außerdem wirst Du 
        automatisch ins Dienstkontrollblatt eingetragen:
      " );
      open_fieldset( 'small_form', '', 'Dienstkontrollblatt' );
        open_div( 'newfield' );
          ?> <label>Dein Name:</label>
             <input type='text' size='20' name='coopie_name' value='<? echo $coopie_name; ?>'>
             <label style='padding-left:4em;'>Telefon:</label>
             <input type='text' size='20' name='telefon' value='<? $telefon; ?>'> <?
        close_div();
        open_div( 'newfield' );
          ?> <label>Notiz fuers Dienstkontrollblatt:</label>
             <br>
             <textarea cols='80' rows='3' name='notiz'><? echo $notiz; ?></textarea> <?
        close_div();
      close_fieldset();
    close_div();
    open_div( 'newfield right' );
      submission_button('OK');
    close_div();
  close_fieldset();
close_form();

open_javascript( "
  function dienstform_on() {
    document.getElementById('dienstform').style.display = 'block';
    document.getElementById('nodienstform').style.display = 'none';
  }
  function dienstform_off() {
    document.getElementById('dienstform').style.display = 'none';
    document.getElementById('nodienstform').style.display = 'block';
  }
" );

function nur_fuer_dienst() {
  global $dienst;
  for( $i = 0; $i < func_num_args(); $i++ ) {
    if( $dienst == func_get_arg($i) )
      return TRUE;
  }
  div_msg( 'warn', 'Keine Berechtigung' );
  exit();
}
function hat_dienst() {
  global $dienst;
  for( $i = 0; $i < func_num_args(); $i++ ) {
    if( $dienst == func_get_arg($i) )
      return true;
  }
  return false;
}

exit();

?>
