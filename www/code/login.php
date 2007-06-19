<?php
//
// login.php
//
// anmeldeformular:
//  - verarbeitet neuanmeldungen
//  - andernfalls: prueft, ob benutzer schon angemeldet (per cookie)
//  - falls auch nicht: anmeldeformular ausgeben
//  - per "action=logout" wird ein logout (loeschen des cookie) erzwungen
//
// bei erfolgreicher anmeldung werden gesetzt:
//  - $angemeldet == TRUE
//  - $login_gruppen_id
//  - $dienst (0, 1, 3, 4 oder 5)
// falls $dienst > 0 ausserdem:
//  - $coopie_name
//  - $dienstkontrollblatt_id

  $mysqlheute = date('Y') . '-' . date('m') . '-' . date('d');
  $mysqljetzt = $mysqlheute . ' ' . date('H') . ':' . date('i') . ':' . date('s');

  function get_http_var( $name ) {
    global $$name, $HTTP_GET_VARS, $HTTP_POST_VARS;
    if( isset( $HTTP_GET_VARS[$name] ) ) {
      $$name = $HTTP_GET_VARS[$name];
      return TRUE;
    } elseif( isset( $HTTP_POST_VARS[$name] ) ) {
      $$name = $HTTP_POST_VARS[$name];
      return TRUE;
    } else {
      unset( $$name );
      return FALSE;
    }
  }
  function need_http_var( $name ) {
    global $$name, $HTTP_GET_VARS, $HTTP_POST_VARS;
    if( isset( $HTTP_GET_VARS[$name] ) ) {
      $$name = $HTTP_GET_VARS[$name];
    } elseif( isset( $HTTP_POST_VARS[$name] ) ) {
      $$name = $HTTP_POST_VARS[$name];
    } else {
      error( __FILE__, __LINE__, "variable $name nicht uebergeben" );
      exit();
    }
  }

  $problems = '';
  $angemeldet=FALSE;
  $dienst=0;
  $login_gruppen_id=FALSE;
  $name='';
  $notiz='';

  // pruefen, ob neue login daten uebergeben werden:
  //
  get_http_var( 'action' );
  if( $action == 'login' ) {
    get_http_var( 'login_gruppen_id' );
    if( ( ! isset( $login_gruppen_id ) ) || ( strlen( $login_gruppen_id ) < 1 ) ) {
      $problems = $problems . "<div class='warn'>FEHLER: keine Gruppe ausgewaehlt</div>";
    }
    get_http_var( 'passwort' )
      or $problems = $problems . "<div class='warn'>FEHLER: kein Passwort angegeben</div>";
    get_http_var( 'dienst' ) or
       $problems = $problems . "<div class='warn'>FEHLER: kein Dienst ausgewaehlt</div>";

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
      get_http_var( 'coopie_name' );
      // if( ! $coopie_name || ( strlen( $coopie_name ) < 2 ) ) {
      //  $problems = $problems . "<div class='warn'>FEHLER: kein Name angegeben</div>";
      // }
      get_http_var( 'telefon' );
      get_http_var( 'notiz' );
    }

    if( ! $problems ) {
      //
      // passwort pruefen:
      //
      $result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($login_gruppen_id)) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
	    $bestellgruppen_row = mysql_fetch_array($result);
			if( $bestellgruppen_row['passwort'] != crypt($passwort,35464) ) {
        $problems = $problems . "<div class='warn'>FEHLER: Passwort leider falsch</div>";
      }
      $login_gruppen_name = $bestellgruppen_row['name'];
			
    }

    if ( ( ! $problems ) && ( $dienst != 0 ) ) {
      //
      // eintrag im dienstkontrollblatt:
      //
      $result = mysql_query(
        "SELECT * FROM dienstkontrollblatt WHERE zeit like '$mysqlheute %' ORDER BY id"
      );
      $row = FALSE;
      while( ( $r1 = mysql_fetch_array($result) ) ) {
        $row = $r1;
      }
      if( $row && ( $row['dienst'] == $dienst ) && ( $row['gruppen_id'] == $login_gruppen_id ) ) {
        // Eintrag existiert schon: ggf. notiz anfuegen:
        if( $notiz != '' ) {
          mysql_query(
            "UPDATE dienstkontrollblatt SET notiz='{$row['notiz']} $notiz' WHERE id={$row['id']}"
          );
        }
        $dienstkontrollblatt_id = $row['id'];
      } else {
        mysql_query(
          "INSERT INTO dienstkontrollblatt
          (zeit,dienst,gruppen_id,name,telefon,notiz)
          VALUES ('$mysqljetzt',$dienst,$login_gruppen_id,'$coopie_name','$telefon','$notiz')"
        ) or error( __LINE__, __FILE__, "eintrag im dienstkontrollblatt fehlgeschlagen" );
        $result = mysql_query(
          "SELECT id FROM dienstkontrollblatt
           WHERE zeit='$mysqljetzt' and dienst=$dienst and gruppen_id=$login_gruppen_id"
        );
        if( ! $result )
          error( __LINE__, __FILE__, "eintrag im dienstkontrollblatt fehlgeschlagen" );
        if( ! ( $row = mysql_fetch_array( $result ) ) )
          error( __LINE__, __FILE__, "eintrag im dienstkontrollblatt fehlgeschlagen" );
        $dienstkontrollblatt_id = $row['id'];
      }
    } else {
      $dienstkontrollblatt_id = 0;
    }

    // ggf. passwort aendern:
    //
    get_http_var( 'pwneu1' );
    get_http_var( 'pwneu2' );
    if( isset($pwneu1) && ( strlen( $pwneu1 ) >= 1 ) ) {
      if( strlen( $pwneu1 ) < 2 ) {
        $problems = $problems . "<div class='warn'>FEHLER: neues Passwort zu kurz</div>";
      }
      if( $pwneu1 != $pwneu2 ) {
        $problems = $problems . "<div class='warn'>FEHLER: Eingaben fuer neues PW stimmen nicht ueberein</div>";
      }
      if( ! $problems ) {
        setcookie( 'foodsoftkeks', '0', 0, '/' );
        mysql_query( 
          "UPDATE bestellgruppen SET passwort='"
           . mysql_escape_string(crypt($pwneu1,35464))
           . "' WHERE id=$login_gruppen_id"
        ) or error(__LINE__,__FILE__,"Konnte das Gruppenpasswort nicht setzen.",mysql_error());
        include( 'head.php' );
        echo "
          <div class='ok'>
           Passwort erfolgreich geaendert!  &nbsp; <a href='index.php'>weiter...</a>
          </div>
          </body>
          </html>
        ";
        exit();
      }
    }

    if( ! $problems ) {
      $klarkeks = "$login_gruppen_id-$dienst-$dienstkontrollblatt_id-$passwort";
      $keks = base64_encode( "$klarkeks" );
      setcookie( 'foodsoftkeks', $keks, 0, '/' ) 
        or error( __LINE__, __FILE__, "setcookie() fehlgeschlagen" );
      // include ('head.php');
      // echo "keks: $klarkeks, $keks";
      $angemeldet = TRUE;
      set_privileges();
      return;
    }

  } elseif( isset( $_COOKIE['foodsoftkeks'] ) && ( strlen( $_COOKIE['foodsoftkeks'] ) > 1 ) ) {

    // echo "keks: {$_COOKIE['foodsoftkeks']} ";
    if( ( $action == 'logout' ) ) {
      unset( $_COOKIE['foodsoftkeks'] );
      setcookie( 'foodsoftkeks', '0', 0, '/' );
    } else {

      $keks = base64_decode( $_COOKIE['foodsoftkeks'] );
      sscanf( $keks, "%u-%u-%u-%s", &$login_gruppen_id, &$dienst, &$dienstkontrollblatt_id, &$passwort );
  
      if( ( ! $login_gruppen_id ) or ( $login_gruppen_id < 1 ) ) {
        $problems = $problems .  "fehler im keks: ungueltige login_gruppen_id";
      } else {
        $result = mysql_query("SELECT * FROM bestellgruppen WHERE id=".mysql_escape_string($login_gruppen_id))
          or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
        $bestellgruppen_row = mysql_fetch_array($result);
        if( $bestellgruppen_row['passwort'] != crypt($passwort,35464) ) {
          $problems = $problems .  "fehler im keks: ungueltiges passwort";
        } else {
          $login_gruppen_name = $bestellgruppen_row['name'];
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
          $problems = $problems .  "fehler im keks: ungueltiger dienst";
      }
      if( $dienst > 0 ) {
        $result = mysql_query( "SELECT * FROM dienstkontrollblatt WHERE id='$dienstkontrollblatt_id'" )
          or $problems = $problems . "fehler im keks: ungueltige dienstkontrollblatt_id";
        $row = mysql_fetch_array($result);
        $coopie_name = $row['name'];
      }
  
      if( ! $problems ) {  // login ok, weitermachen...
        $angemeldet = TRUE;
        set_privileges();
        return;
      } else {  // fehlerhafter keks, besser loeschen:
        setcookie( 'foodsoftkeks', '0', 0, '/' );
      }
    }
  }


  //
  // nutzer ist noch nicht angemeldet, also: formular ausgeben:
  //
  set_privileges(); // im moment: keine...
  require_once('head.php');
  echo "
    <form action='index.php' method='post' class='small_form'>
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
         (das funktioniert aber noch nicht richtig: Ihr werdet also trotzdem noch zwischendurch
         immer mal nach einem passwort gefragt werden, bitte nicht wundern!)
         <br>
         (aber: Gruppenpasswoerter aendern geht jetzt schon!)
       </div>
       <div class='newfield'>
         <label>Gruppe:</label>
         <select size='1' name='login_gruppen_id'>
           <option value='' selected>(bitte Gruppe waehlen)</option>
  ";
  ( $gruppen = mysql_query( "SELECT * FROM bestellgruppen WHERE name like '% %'" ) )
    or error( __LINE__, __FILE__, "konne Bestellgruppen nicht aus Datenbank lesen!" );
  while( $gruppe = mysql_fetch_array( $gruppen ) ) {
    echo "<option value='{$gruppe['id']}'";
    if( $login_gruppen_id == $gruppe['id'] )
      echo " selected";
    echo ">{$gruppe['name']}</option>";
  }
  echo "
         </select>
         <label style='padding-left:4em;'>Passwort:</label>
         <input type='password' size='8' name='passwort' value=''></input>
         <span class='button' id='pwneu_knopf' style='padding-left:2em;'
          onclick='pwneu_on();'>Passwort aendern...</span>
       </div>
       <div class='newfield' style='display:none;' id='pwneu_form'>
         <fieldset class='small_form'>
         <legend>
         <img src='img/close_black_trans.gif' class='button'
          onclick='pwneu_off()' title='Ausblenden...'></img>
          Passwort aendern
          </legend>
         <label style='padding-left:2em;'>neues Passwort:</label>
         <input type='password' size='8' name='pwneu1' value=''></input>
         <label style='padding-left:4em;'>nochmal das neue Passwort:</label>
         <input type='password' size='8' name='pwneu2' value=''></input>
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
             <input class='text' size='20' name='coopie_name' value='$name'></input>
             <label style='padding-left:4em;'>Telefon:</label>
             <input class='text' size='20' name='telefon' value='$name'></input>
           </div>
           <div class='newfield'>
             <label>Notiz fuers Dienstkontrollblatt:</label>
             <br>
             <textarea cols='80' rows='4' name='notiz'>$notiz</textarea>
           </div>
         </fieldset>
       </div>
       <div class='newfield'>
         <input type='submit' name='submit' value='OK'></input>
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
  function nur_fuer_dienst_I() {
    global $hat_dienst_I;
    if( ! $hat_dienst_I ) {
      require_once( 'head.php' );
      echo "<div class='warn'>Nur fuer Dienst I</div></body></html>";
      exit();
    }
  }
  function nur_fuer_dienst_III() {
    global $hat_dienst_III;
    if( ! $hat_dienst_III ) {
      require_once( 'head.php' );
      echo "<div class='warn'>Nur fuer Dienst II</div></body></html>";
      exit();
    }
  }
  function nur_fuer_dienst_IV() {
    global $hat_dienst_IV;
    if( ! $hat_dienst_IV ) {
      require_once( 'head.php' );
      echo "<div class='warn'>Nur fuer Dienst IV</div></body></html>";
      exit();
    }
  }
  function nur_fuer_dienst_V() {
    global $hat_dienst_V;
    if( ! $hat_dienst_V ) {
      require_once( 'head.php' );
      echo "<div class='warn'>Nur fuer Dienst V</div></body></html>";
      exit();
    }
  }

  echo "
    </body>
    <script type='text/javascript'>
      function dienstform_on() {
        document.getElementById('dienstform').style.display = 'block';
      }
      function dienstform_off() {
        document.getElementById('dienstform').style.display = 'none';
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
    </html>
  ";

exit();

?>

