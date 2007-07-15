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
         $dienstkontrollblatt_id;

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

  init_login();

  $telefon='';
  $name='';
  $notiz='';
  $problems = '';

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
      $result = mysql_query(
        "SELECT * FROM bestellgruppen
         WHERE (id=".mysql_escape_string($login_gruppen_id).") and (aktiv=1)"
      ) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
	    $bestellgruppen_row = mysql_fetch_array($result);
			if( $bestellgruppen_row['passwort'] != crypt($passwort,35464) ) {
        $problems = $problems . "<div class='warn'>FEHLER: Passwort leider falsch</div>";
      }
      $login_gruppen_name = $bestellgruppen_row['name'];
			
    }

    // $msg = "login:<br>";

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
        // Eintrag existiert schon: ggf. aktualisieren:
        if( $notiz != '' ) {
          mysql_query(
            "UPDATE dienstkontrollblatt SET notiz='" . mysql_escape_string( $row['notiz'] . $notiz )
            . "' WHERE id={$row['id']}"
          );
        }
        if( $telefon != '' ) {
          mysql_query(
            "UPDATE dienstkontrollblatt SET telefon='$telefon' WHERE id={$row['id']}"
          );
        }
        if( $coopie_name != '' ) {
          mysql_query(
            "UPDATE dienstkontrollblatt SET name='" . mysql_escape_string($coopie_name)
            . "' WHERE id={$row['id']}"
          );
        }
        $dienstkontrollblatt_id = $row['id'];
//         $msg = $msg
//           . "update:<br>coopie_name: $coopie_name<br>"
//           . "<br>telefon: $telefon<br>"
//           . "<br>notiz: $telefon<br>";
      } else {
        $q = "INSERT INTO dienstkontrollblatt
          (zeit,dienst,gruppen_id,name,telefon,notiz)
          VALUES ('$mysqljetzt',$dienst,$login_gruppen_id,'$coopie_name','$telefon','$notiz')";
        mysql_query( $q ) or error( __LINE__, __FILE__, "eintrag im dienstkontrollblatt fehlgeschlagen" );
        $result = mysql_query(
          "SELECT id FROM dienstkontrollblatt
           WHERE zeit='$mysqljetzt' and dienst=$dienst and gruppen_id=$login_gruppen_id"
        );
        if( ! $result )
          error( __LINE__, __FILE__, "eintrag im dienstkontrollblatt fehlgeschlagen" );
        if( ! ( $row = mysql_fetch_array( $result ) ) )
          error( __LINE__, __FILE__, "eintrag im dienstkontrollblatt fehlgeschlagen" );
        $dienstkontrollblatt_id = $row['id'];
//         $msg = $msg
//           . "neueintrag: $q<br>id: {$row['id']} <br> coopie_name: $coopie_name / {$row['name']}<br>"
//           . " telefon: $telefon / {$row['telefon']}<br>"
//           . " notiz: $notiz / {$row['notiz']}<br>";
      }
    } else {
      $dienstkontrollblatt_id = 0;

//       if( ! $problems ) {
//         get_http_var( 'quiz_name' ) && get_http_var( 'quiz_datum' )
//           && ( $quiz_name ) && ( $quiz_datum )
//           or $problems = $problems . "<div class='warn'>Bitte das Quiz ausf&uuml;llen!</div>";
//       }
//       if( ! $problems ) {
//         mysql_query(
//           "INSERT INTO dienstquiz
//             (gruppen_id,name,datum,eingabezeit)
//             VALUES ( $login_gruppen_id, '$quiz_name', '$quiz_datum', '$mysqljetzt' ) " );
//       }
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
      // require_once('head.php');
      // echo "<div class='ok'>msg: $msg </div>";
      // echo "keks: $klarkeks, $keks";
      $angemeldet = TRUE;
      set_privileges();
      return;
    }

  } elseif( isset( $_COOKIE['foodsoftkeks'] ) && ( strlen( $_COOKIE['foodsoftkeks'] ) > 1 ) ) {

    // echo "keks: {$_COOKIE['foodsoftkeks']} ";

    $keks = base64_decode( $_COOKIE['foodsoftkeks'] );
    sscanf( $keks, "%u-%u-%u-%s", &$login_gruppen_id, &$dienst, &$dienstkontrollblatt_id, &$passwort );

    if( ( ! $login_gruppen_id ) or ( $login_gruppen_id < 1 ) ) {
      $problems = $problems .  "fehler im keks: ungueltige login_gruppen_id";
    } else {
      $result = mysql_query(
        "SELECT * FROM bestellgruppen
         WHERE (id=" . mysql_escape_string($login_gruppen_id) . ") and (aktiv=1)"
      ) or error(__LINE__,__FILE__,"Konnte Bestellgruppendaten nich aus DB laden..",mysql_error());
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

      if( ( $action == 'logout' ) ) {
        if( $dienst > 0 ) {
          if( get_http_var('coopie_name') && get_http_var('telefon') && get_http_var('notiz') ) {
            // ggf. noch  dienstkontrollblatt-Eintrag aktualisieren:
            mysql_query(
              " UPDATE dienstkontrollblatt
                SET name='" . mysql_escape_string( $coopie_name ) . "' "
              . ",  telefon='" . mysql_escape_string( $telefon ) . "' "
              . ",  notiz='" . mysql_escape_string( $notiz ) . "' "
              . "WHERE id=$dienstkontrollblatt_id"
            ) or error( __LINE__, __FILE__, "Dienstkontrollblatt-Eintrag fehlgeschlagen", mysql_error() );
            $problems = "<div class='ok'>Abgemeldet!</div>";
          } else {
            $problems = "<div class='warn'>Dienstkontrollblatt-Austrag fehlgeschlagen!</div>"
             . get_http_var('coopie_name')
             . " name: $coopie_name, "
             . get_http_var('telefon')
             . " telefon: $telefon, "
             . get_http_var('notiz')
             . " notiz: $notiz "
            ;
          }
        }
        logout();
      } else {
        $angemeldet = TRUE;
        set_privileges();
        return;
      }
    } else {  // fehlerhafter keks, besser loeschen:
      logout();
    }
  }

  logout();  // nicht korrekt angemeldet: alles zuruecksetzen...

  //
  // nutzer ist noch nicht angemeldet, also: formular ausgeben:
  //
  set_privileges(); // im moment: keine...
  require_once('head.php');

  get_http_var( 'area' );
  if( $from_dokuwiki || ( $area == 'wiki' ) ) {
    $form_action='/foodsoft/index.php?area=wiki';
  } else {
    $form_action='index.php';
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
           <option value='' selected>(bitte Gruppe waehlen)</option>
  ";
  ( $gruppen = mysql_query( "SELECT * FROM bestellgruppen WHERE (aktiv=1) and (name like '% %') ORDER by (id%1000)" ) )
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
         <a id='pwneu_knopf' style='margin-left:2em;font-size:10pt;'
          onclick='pwneu_on();'>Passwort &auml;ndern...</a>
       </div>
       <div class='newfield' style='display:none;' id='pwneu_form'>
         <fieldset class='small_form'>
         <legend>
         <img src='img/close_black_trans.gif' style='padding:0pt;margin:0pt;' class='button'
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
             <input type='text' size='20' name='coopie_name' value='$coopie_name'></input>
             <label style='padding-left:4em;'>Telefon:</label>
             <input type='text' size='20' name='telefon' value='$telefon'></input>
           </div>
           <div class='newfield'>
             <label>Notiz fuers Dienstkontrollblatt:</label>
             <br>
             <textarea cols='80' rows='3' name='notiz'>$notiz</textarea>
           </div>
         </fieldset>
       </div>
       ";
//        <div id='quiz' style='display:";
//        echo $dienst ? 'none' : 'block';
// echo ";'>
//          <fieldset class='small_form' style='padding:1em'>
//            <legend>
//              Quiz
//            </legend>
//            <div class='kommentar'>
//              Aus aktuellem Anlass: ein kleines Quiz:
//            </div>
//            <div class='newfield'>
//              Den n&auml;chsten Dienst meiner Gruppe macht...
//            </div>
//            <div class='newfield'>
//              <label>Name:</label>
//              <input type='text' size='20' name='quiz_name' value='$quiz_name'></input>
//              <label style='padding-left:4em;'>am:</label>
//              <input type='text' size='20' name='quiz_datum' value='$quiz_datum'></input>
//            </div>
//            <div class='kommentar' style='padding-top:2em;'>
//              Keine Ahnung? Kein Problem! Hier geht's zum
//              <a href='http://nahrungskette.fcschinke09.de/wiki/doku.php?id=start&do=login' target='_new'>Dienstplan</a>!
//            </div>
//          </fieldset>
//        </div>
  echo "
       <div class='newfield'>
         <input type='submit' name='submit' value='OK'></input>
       </div>
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
    global $dienst;
    for( $i = 0; $i < func_num_args(); $i++ ) {
      if( $dienst == func_get_arg($i) )
        return TRUE;
    }
    require_once( 'head.php' );
    echo "<div class='warn'>Keine Berechtigung</div></body></html>";
    exit();
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
    </html>
  ";

exit();

?>

