<?php
//
// login.php
//
// anmeldeformular:
//  - verarbeitet neuanmeldungen
//  - andernfalls: prueft, ob benutzer schon angemeldet (per cookie)
//  - falls auch nicht: anmeldeformular ausgeben
//  - per "action=logout" wird ein logout (loeschen des cookie) erzwungen
//  - falls $from_dokuwiki==true wird index.php?area=wiki aufgerufen
//
// bei erfolgreicher anmeldung werden gesetzt:
//  - $angemeldet == TRUE
//  - $login_gruppen_id
//  - $login_gruppen_name
//  - $dienst (0, 1, 3, 4 oder 5)
// falls $dienst > 0 ausserdem:
//  - $coopie_name
//  - $dienstkontrollblatt_id

  global $angemeldet,
         $login_gruppen_id,
         $login_gruppen_name,
         $dienst,
         $coopie_name,
         $dienstkontrollblatt_id,
         $foodsoftpath,
         $foodsoftdir,
         $action;

  function init_login() {
    global $angemeldet, $login_gruppen_id, $login_gruppen_name, $dienst, $dienstkontrollblatt_id;
    $angemeldet=FALSE;
    $login_gruppen_id = FALSE;
    $login_gruppen_name = FALSE;
    $dienst = 0;
    $dienstkontrollblatt_id = FALSE;
  }
  function logout() {
    init_login();
    unset( $_COOKIE['foodsoftkeks'] );
    setcookie( 'foodsoftkeks', '0', 0, '/' );
  }

  require_once( "$foodsoftpath/code/err_functions.php" );
  require_once( "$foodsoftpath/code/zuordnen.php" );
  require_once( "$foodsoftpath/code/views.php" );
  
  init_login();

  $telefon ='';
  $name ='';
  $notiz ='';
  $problems = '';

  // pruefen, ob neue login daten uebergeben werden:
  //
  get_http_var( 'action', 'w', '' );

  if( $action == 'login' ) {
    get_http_var( 'login_gruppen_id', 'u' )
      or $problems = $problems . "<div class='warn'>FEHLER: keine Gruppe ausgewaehlt</div>";
    get_http_var( 'passwort' )
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
      get_http_var( 'coopie_name', 'M', '' );
      // if( ! $coopie_name || ( strlen( $coopie_name ) < 2 ) ) {
      //  $problems = $problems . "<div class='warn'>FEHLER: kein Name angegeben</div>";
      // }
      get_http_var( 'telefon', 'M', '' );
      get_http_var( 'notiz', 'M', '' );
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
      // echo "<div class='ok'>neue dienstkontrollblatt_id: $dienstkontrollblatt_id</div>";
    } else {
      $dienstkontrollblatt_id = 0;
    }

    // ggf. passwort aendern:
    //
    get_http_var( 'pwneu1', '' );
    get_http_var( 'pwneu2', '' );
    if( isset($pwneu1) && ( strlen( $pwneu1 ) >= 1 ) ) {
      if( strlen( $pwneu1 ) < 2 ) {
        $problems = $problems . "<div class='warn'>FEHLER: neues Passwort zu kurz</div>";
      }
      if( $pwneu1 != $pwneu2 ) {
        $problems = $problems . "<div class='warn'>FEHLER: Eingaben fuer neues PW stimmen nicht ueberein</div>";
      }
      if( ! $problems ) {
        setcookie( 'foodsoftkeks', '0', 0, '/' );
        set_password( $login_gruppen_id, $pwneu1 )
          or error(__LINE__,__FILE__,"Konnte das Gruppenpasswort nicht setzen: ",mysql_error());
        include( 'head.php' );
        echo "
          <div class='ok'>
           Passwort erfolgreich geaendert!  &nbsp; <a href='$foodsoftdir/index.php'>weiter...</a>
          </div>
          $print_on_exit
        ";
        exit();
      }
    }

    if( ! $problems ) {
      $klarkeks = "$login_gruppen_id-$dienst-$dienstkontrollblatt_id-$passwort";
      $keks = base64_encode( "$klarkeks" );
      setcookie( 'foodsoftkeks', $keks, 0, '/' ) 
        or error( __LINE__, __FILE__, "setcookie() fehlgeschlagen" );
      $angemeldet = TRUE;
      set_privileges();
      return;
    }

  // falls kein neues login:
  // pruefen, ob schon eingeloggt:
  //
  } elseif( isset( $_COOKIE['foodsoftkeks'] ) && ( strlen( $_COOKIE['foodsoftkeks'] ) > 1 ) ) {

    // echo "keks: {$_COOKIE['foodsoftkeks']} ";

    $keks = base64_decode( $_COOKIE['foodsoftkeks'] );
    sscanf( $keks, "%u-%u-%u-%s", &$login_gruppen_id, &$dienst, &$dienstkontrollblatt_id, &$passwort );

    if( ! ( $login_gruppen_id > 0 ) ) {
      $problems = $problems .  "<div class='warn'>fehler im keks: ungueltige login_gruppen_id</div>";
    } else {
      // echo "<!-- from cookie: :$login_gruppen_id,$passwort: -->";
      $bestellgruppen_row = check_password( $login_gruppen_id, $passwort );
      if( $bestellgruppen_row ) {
        $login_gruppen_name = $bestellgruppen_row['name'];
      } else {
        $problems = $problems .  "<div class='warn'>fehler im keks: ungueltiges passwort</div>";
      }
    }
    switch( $dienst ) {
      case 0:
      case 1:
      case 3:
      case 4:
      case 5:
        break;
      default:
        $problems = $problems .  "<div class='warn'>fehler im keks: ungueltiger dienst</div>";
    }
    if( $dienst > 0 ) {
      if( $dienstkontrollblatt_id > 0 ) {
        $result = dienstkontrollblatt_select( $dienstkontrollblatt_id );
        $row = mysql_fetch_array($result)
          or $problems = $problems .  "<div class='warn'>Dienstkontrollblatt-Eintrag nicht gefunden</div>";
        $coopie_name = $row['name'];
      } else {
        $problems = $problems .  "<div class='warn'>fehler im keks $keks: ungueltige dienstkontrollblatt_id: $dienstkontrollblatt_id</div>";
      }
    }

    if( ! $problems ) {  // login ok, weitermachen...

      if( ( $action == 'logout' ) ) {
        // ggf. noch  dienstkontrollblatt-Eintrag aktualisieren:
        if( $dienst > 0 and $dienstkontrollblatt_id > 0 ) {
          get_http_var('coopie_name','M','');
          get_http_var('telefon','M','');
          get_http_var('notiz','M','');
          dienstkontrollblatt_eintrag(
            $dienstkontrollblatt_id, $login_gruppen_id, $dienst, $coopie_name, $telefon, $notiz 
          );
        }
        logout();
        $problems = "<div class='ok'>Abgemeldet!</div>";
      } else {
        $angemeldet = TRUE;
        set_privileges();
        return;
      }
    } else {  // fehlerhafter keks, besser loeschen:
      logout();
    }
  }

  // ab hier: benutzer ist noch nicht eingeloggt, und hat keine gueltigen
  // login daten uebergeben. Wir setzen alles zurueck und zeigen das
  // anmeldeformular:

  logout();  // nicht korrekt angemeldet: alles zuruecksetzen...

  set_privileges(); // im moment: keine...
  require_once("$foodsoftpath/head.php");

  get_http_var( 'area', 'w' );
  if( isset( $from_dokuwiki ) && $from_dokuwiki or ( $area == 'wiki' ) ) {
    $form_action="$foodsoftdir/index.php?area=wiki";
  } else {
    $form_action="$foodsoftdir/index.php";
  }
  echo "
    <form action='$form_action' method='post' class='small_form'>
      <input type='hidden' name='action' value='login'>
      <fieldset class='small_form' style='padding:2em;'>
        <legend>
          Anmelden
        </legend>
  ";
  if( "$problems" ) echo "$problems";
  echo "
       <div class='kommentar'>
         In Zukunft braucht Ihr Euch nur noch einmal pro Sitzung bei der Foodsoft anmelden.
         <br>
         <br>
         Und: die gleiche Anmeldung gilt jetzt auch fuers Wiki!
         <br>
       </div>
       <div class='newfield'>
         <label>Gruppe:</label>
         <select size='1' name='login_gruppen_id'>
  ";
  echo optionen_gruppen();
  echo "
         </select>
         <label style='padding-left:4em;'>Passwort:</label>
         <input type='password' size='8' name='passwort' value=''>
         <a id='pwneu_knopf' style='margin-left:2em;font-size:10pt;'
          onclick='pwneu_on();'>Passwort &auml;ndern...</a>
       </div>
       <div class='newfield' style='display:none;' id='pwneu_form'>
         <fieldset class='small_form'>
         <legend>
         <img src='img/close_black_trans.gif' style='padding:0pt;margin:0pt;' class='button'
          onclick='pwneu_off()' title='Ausblenden...' alt='Ausblenden...'>
          Passwort &auml;ndern
          </legend>
         <label style='padding-left:2em;'>neues Passwort:</label>
         <input type='password' size='8' name='pwneu1' value=''>
         <label style='padding-left:4em;'>nochmal das neue Passwort:</label>
         <input type='password' size='8' name='pwneu2' value=''>
         </fieldset>
       </div>
       <div class='newfield'>
         <label>Ich mache gerade...</label>
       </div>
       <table>
         <tr>
           <td>
             <input class='checkbox' type='radio' name='dienst' value='0'
             onclick='dienstform_off();' ";
  if (!$dienst) echo ' checked';
  echo ">
             <label>keinen Dienst</label>
           </td>
           <td>
             <input class='checkbox' type='radio' name='dienst' value='1'
             onclick='dienstform_on();' ";
  if ($dienst==1) echo ' checked';
  echo ">
             <label title='Verteiler'>Dienst I/II</label>
           </td>
           <td>
             <input class='checkbox' type='radio' name='dienst' value='3'
             onclick='dienstform_on();' ";
  if ($dienst==3) echo ' checked';
  echo ">
             <label title='Kellerdienst'>Dienst III</label>
           </td>
           <td>
             <input class='checkbox' type='radio' name='dienst' value='4'
             onclick='dienstform_on();' ";
  if ($dienst==4) echo ' checked';
  echo ">
             <label title='Abrechnung'>Dienst IV</label>
           </td>
           <td>
             <input class='checkbox' type='radio' name='dienst' value='5'
             onclick='dienstform_on();' ";
  if ($dienst==5) echo ' checked';
  echo ">
             <label title='Mitgliederverwaltung'>Dienst V</label>
           </td>
         </tr>
       </table>
       <div id='dienstform' style='display:";
       echo $dienst ? 'block' : 'none';
  echo ";'>
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
             <input type='text' size='20' name='coopie_name' value='$coopie_name'>
             <label style='padding-left:4em;'>Telefon:</label>
             <input type='text' size='20' name='telefon' value='$telefon'>
           </div>
           <div class='newfield'>
             <label>Notiz fuers Dienstkontrollblatt:</label>
             <br>
             <textarea cols='80' rows='3' name='notiz'>$notiz</textarea>
           </div>
         </fieldset>
       </div>
       ";
  echo "
       <div class='newfield'>
         <input type='submit' name='submit' value='OK'>
       </div>
     </fieldset>
   </form>
  ";

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
    

  echo "
    <script type='text/javascript'>
      function dienstform_on() {
        document.getElementById('dienstform').style.display = 'block';
        document.getElementById('quiz').style.display = 'none';
      }
      function dienstform_off() {
        document.getElementById('dienstform').style.display = 'none';
        document.getElementById('quiz').style.display = 'block';
      }
      function pwneu_on() {
        document.getElementById('pwneu_knopf').style.display = 'none';
        document.getElementById('pwneu_form').style.display = 'block';
      }
      function pwneu_off() {
        document.getElementById('pwneu_knopf').style.display = 'inline';
        document.getElementById('pwneu_form').style.display = 'none';
      }
    </script>
    $print_on_exit
  ";

exit();

?>
