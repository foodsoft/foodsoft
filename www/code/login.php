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

// Bei aufruf aus dem wiki sind wir in einer function, wir brauchen daher 
// die global-deklarationen:
//
  global $angemeldet,
         $login_gruppen_id,
         $login_gruppen_name,
         $dienst,
         $coopie_name,
         $dienstkontrollblatt_id,
         $foodsoftpath,
         $foodsoftdir,
         $action,
         $session_id,
         $motd;

  function init_login() {
    global $angemeldet, $login_gruppen_id, $login_gruppen_name
         , $session_id, $dienst, $dienstkontrollblatt_id;
    // echo "init_login called";
    $angemeldet=FALSE;
    $login_gruppen_id = FALSE;
    $login_gruppen_name = FALSE;
    $dienst = 0;
    $dienstkontrollblatt_id = FALSE;
    $coopie_name= FALSE;
    $session_id = 0;
    set_privileges();
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
    switch( $dienst ) {
      case 0:
      case 1:
      case 3:
      case 4:
      case 5:
        break;
      default:
        $problems = $problems .  "<div class='warn'>interner fehler: ungueltiger dienst</div>";
    }
    if( $dienst > 0 ) {
      if( $dienstkontrollblatt_id > 0 ) {
        $result = dienstkontrollblatt_select( $dienstkontrollblatt_id );
        $row = mysql_fetch_array($result)
          or $problems = $problems .  "<div class='warn'>Dienstkontrollblatt-Eintrag nicht gefunden</div>";
        $coopie_name = $row['name'];
      } else {
        $problems = $problems .  "<div class='warn'>interner fehler: ungueltige dienstkontrollblatt_id</div>";
      }
    }

    if( ! $problems ) {  // login ok, weitermachen...
      $angemeldet = TRUE;
      set_privileges();
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
      or $problems = $problems . "<div class='warn'>FEHLER: keine Gruppe ausgewaehlt</div>";
    get_http_var( 'passwort','R' )
      or $problems = $problems . "<div class='warn'>FEHLER: kein Passwort angegeben</div>";
    get_http_var( 'dienst', 'u' )
      or $problems = $problems . "<div class='warn'>FEHLER: kein Dienst ausgewaehlt</div>";

    switch( $dienst ) {
      case 0:
      case 1:
      case 3:
      case 4:
      case 5:
        break;
      default:
        $problems = $problems . "<div class='warn'>FEHLER: kein gueltiger Dienst angegeben</div>";
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
        $problems = $problems . "<div class='warn'>FEHLER: Passwort leider falsch</div>";
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
      set_privileges();
      logger( 'successful login' );
    }
    break;
  case 'logout':
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
    $problems .= "<div class='ok'>Abgemeldet!</div>";
    break;
  }

  if( $angemeldet )
    return;

  // ab hier: benutzer ist nicht eingeloggt; wir setzen alles zurueck und zeigen das anmeldeformular:

  logout();  // nicht korrekt angemeldet: alles zuruecksetzen...
  // setWikiHelpTopic( ':' );
  require_once("head.php");
  setWikiHelpTopic( ':' );

  if( isset( $from_dokuwiki ) && $from_dokuwiki ) {
    $form_action="$foodsoftdir/index.php?window=wiki";
  } else {
    $form_action="$foodsoftdir/index.php";
  }

if( isset( $motd ) ) {
  ?> <div class='kommentar'> echo $motd </div> <?
}

?>
<form action='<? echo $form_action; ?>' method='post' class='small_form'>
  <? echo self_post(); ?>
  <input type='hidden' name='login' value='login'>
    <fieldset class='small_form' style='padding:2em;'>
      <legend>Anmelden</legend>
    <? if( "$problems" ) echo "$problems"; ?>
    <div class='kommentar' style='padding:1em;'>
      Anmeldung für die Foodsoft und fürs Doku-Wiki der Foodsoft:
    </div>
    <div class='newfield'>
      <label>Gruppe:</label>
      <select size='1' name='login_gruppen_id'>
        <? echo optionen_gruppen(); ?>
      </select>
      <label style='padding-left:4em;'>Passwort:</label>
        <input type='password' size='8' name='passwort' value=''>
    </div>
    <div class='newfield'>
      <label>Ich mache gerade...</label>
    </div>
    <table>
      <tr>
        <td>
          <input class='checkbox' type='radio' name='dienst' value='0'
            onclick='dienstform_off();'
            <? if (!$dienst) echo ' checked'; ?> >
          <label>keinen Dienst</label>
        </td>
        <td>
          <input class='checkbox' type='radio' name='dienst' value='1'
            onclick='dienstform_on();'
            <? if ($dienst==1) echo ' checked'; ?> >
          <label title='Verteiler'>Dienst I/II</label>
        </td>
        <td>
          <input class='checkbox' type='radio' name='dienst' value='3'
            onclick='dienstform_on();'
            <? if ($dienst==3) echo ' checked'; ?> >
          <label title='Kellerdienst'>Dienst III</label>
        </td>
        <td>
          <input class='checkbox' type='radio' name='dienst' value='4'
            onclick='dienstform_on();'
            <? if ($dienst==4) echo ' checked'; ?> >
          <label title='Abrechnung'>Dienst IV</label>
        </td>
        <td>
          <input class='checkbox' type='radio' name='dienst' value='5'
            onclick='dienstform_on();'
            <? if ($dienst==5) echo ' checked'; ?> >
          <label title='Mitgliederverwaltung'>Dienst V</label>
        </td>
      </tr>
    </table>
    <div id='dienstform' style='display:<? echo $dienst ? 'block' : 'none';  ?>;'>
      <div class='kommentar'>
           Wenn Du Dich fuer einen Dienst anmeldest, kannst Du zusaetzliche
           Funktionen der Foodsoft nutzen; ausserdem wirst Du 
           automatisch ins Dienstkontrollblatt eingetragen:
      </div>
         <fieldset class='small_form'>
           <legend>
             Dienstkontrollblatt
           </legend>
           <div class='newfield'>
             <label>Dein Name:</label>
             <input type='text' size='20' name='coopie_name' value='<? echo $coopie_name; ?>'>
             <label style='padding-left:4em;'>Telefon:</label>
             <input type='text' size='20' name='telefon' value='<? $telefon; ?>'>
           </div>
           <div class='newfield'>
             <label>Notiz fuers Dienstkontrollblatt:</label>
             <br>
             <textarea cols='80' rows='3' name='notiz'><? echo $notiz; ?></textarea>
           </div>
         </fieldset>
       </div>
       <div class='newfield'>
         <input type='submit' name='submit' value='OK'>
       </div>
     </fieldset>
   </form>
   <script type='text/javascript'>
     function dienstform_on() {
       document.getElementById('dienstform').style.display = 'block';
     }
     function dienstform_off() {
       document.getElementById('dienstform').style.display = 'none';
     }
   </script>
<?

  function set_privileges() {
    global $dienst, $hat_dienst_I, $hat_dienst_III, $hat_dienst_IV, $hat_dienst_V;
    $hat_dienst_I = FALSE;
    $hat_dienst_III = FALSE;
    $hat_dienst_IV = FALSE;
    $hat_dienst_V = FALSE;
    switch( $dienst ) {
      case 1:
        $hat_dienst_I = TRUE;
        break;
      case 3:
        $hat_dienst_III = TRUE;
        break;
      case 4:
        $hat_dienst_IV = TRUE;
        break;
      case 5:
        $hat_dienst_V = TRUE;
        break;
      default:
        break;
    }
  }
  function nur_fuer_dienst() {
    global $dienst, $foodsoftpath, $print_on_exit;
    global $dienst, $foodsoftpath;
    for( $i = 0; $i < func_num_args(); $i++ ) {
      if( $dienst == func_get_arg($i) )
        return TRUE;
    }
    require_once( $foodsoftpath."/head.php" );
    echo "<div class='warn'>Keine Berechtigung</div> $print_on_exit";
    exit();
  }
  function nur_fuer_dienst_I() {
    global $hat_dienst_I, $foodsoftpath;
    if( ! $hat_dienst_I ) {
      require_once( "$foodsoftpath/head.php" );
      echo "<div class='warn'>Nur fuer Dienst I</div> $print_on_exit";
      exit();
    }
  }
  function nur_fuer_dienst_III() {
    global $hat_dienst_III, $foodsoftpath;
    if( ! $hat_dienst_III ) {
      require_once( "$foodsoftpath/head.php" );
      echo "<div class='warn'>Nur fuer Dienst II</div> $print_on_exit";
      exit();
    }
  }
  function nur_fuer_dienst_IV() {
    global $hat_dienst_IV, $foodsoftpath;
    if( ! $hat_dienst_IV ) {
      require_once( "$foodsoftpath/head.php" );
      echo "<div class='warn'>Nur fuer Dienst IV</div> $print_on_exit";
      exit();
    }
  }
  function nur_fuer_dienst_V() {
    global $hat_dienst_V, $foodsoftpath;
    if( ! $hat_dienst_V ) {
      require_once( "$foodsoftpath/head.php" );
      echo "<div class='warn'>Nur fuer Dienst V</div> $print_on_exit";
      exit();
    }
  }

echo $print_on_exit;

exit();

?>
