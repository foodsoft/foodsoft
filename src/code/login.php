<?php
//
// login.php
//
// anmeldescript:
//  - prüft, ob benutzer schon angemeldet (per cookie)
//  - verarbeitet neuanmeldungen
//  - per "login=logout" wird ein logout (löschen des cookie) erzwungen
//  - falls nicht angemeldet: anmeldeformular wird ausgegeben
//  - falls $from_dokuwiki==true wird index.php?window=wiki aufgerufen
//
// bei erfolgreicher anmeldung werden global gesetzt:
//  - $angemeldet == TRUE
//  - $login_gruppen_id
//  - $login_gruppen_name
//  - $session_id
//  - $login_dienst (0, 1, 3, 4 oder 5)
//  - $login_dienstname (lesbare Dienstbezeichnung)
// falls $login_dienst > 0 außerdem:
//  - $coopie_name
//  - $dienstkontrollblatt_id

define('DIENST_KEIN',                    0);
define('DIENST_VERTEILEN',               1);
define('DIENST_AUCH_VERTEILEN',          2);
define('DIENST_ANNEHMEN',                3);
define('DIENST_VORBEREITEN_ABRECHNEN',   4);
define('DIENST_MITGLIEDER_DIENSTE',      5);

$dienstinfos = [
  DIENST_KEIN => [
    'label'       => '(kein Dienst)',
    'description' => 'Bestellen, Gruppenkonto einsehen',
    'wiki'        => null,
    'comment'     =>  'This is the default (no special task)'
  ],
  DIENST_VERTEILEN => [
    'label'       => 'Verteilen',
    'description' => 'Verteilung Terra',
    'wiki'        => 'fc:dienst_verteilen_1_2',
    'comment'     => 'This is the former `Dienst 1`'
  ],
  DIENST_AUCH_VERTEILEN => [  // does not occur in the wild
    'label'       => '(auch Verteilen)',
    'description' => '(auch: Verteilung Terra)',
    'wiki'        => 'fc:dienst_verteilen_1_2',
    'comment'     => 'This is the former `Dienst 2`'
  ],
  DIENST_ANNEHMEN => [
    'label'       => 'Annehmen',
    'description' => 'Pfand, Annahme Terra, Verteilung Landbrot',
    'wiki'        => 'fc:dienst_annehmen_3',
    'comment'     => 'This is the former `Dienst 3`'
  ],
  DIENST_VORBEREITEN_ABRECHNEN => [
    'label'       => 'Vorbereiten/Abrechnen',
    'description' => 'Katalogpflege, Bestellformulare, Abrechnung',
    'wiki'        => 'fc:dienst_vorbereiten_abrechnen_4',
    'comment'     => 'This is the former `Dienst 4`'
  ],
  DIENST_MITGLIEDER_DIENSTE => [
    'label'       => 'Mitglieder/Dienste/Konto',
    'description' => 'Mitgliederverwaltung, Dienstplanerstellung, Buchhaltung/Kontoführung',
    'wiki'        => 'fc:dienst_mitglieder_dienstplan_5',
    'comment'     => 'This is the former `Dienst 5`'
  ],
];

function init_login() {
  global
    $angemeldet,
    $coopie_name,
    $dienstkontrollblatt_id,
    $login_dienst,
    $login_dienstname,
    $login_gruppen_id,
    $login_gruppen_name,
    $reconfirmation_muted,
    $session_id,
    $theme;

  $angemeldet=FALSE;
  $session_id = 0;
  $login_gruppen_id = FALSE;
  $login_gruppen_name = FALSE;
  $login_dienst = 0;
  $login_dienstname = FALSE;
  $dienstkontrollblatt_id = FALSE;
  $coopie_name= FALSE;
  $reconfirmation_muted = FALSE;
  $theme = 'normal';
}

function logout() {
  init_login();
  if (!headers_sent() ) {
    unset( $_COOKIE['foodsoftkeks'] );
    setcookie( 'foodsoftkeks', '0', 0, '/', '', TRUE );
  }
}

init_login();
$errors = [];
$messages = [];

$telefon ='';
$name ='';
$notiz ='';

// pruefen, ob schon eingeloggt:
//
if( isset( $_COOKIE['foodsoftkeks'] ) && ( strlen( $_COOKIE['foodsoftkeks'] ) > 1 ) ) {
  sscanf( $_COOKIE['foodsoftkeks'], "%u_%s", $session_id, $cookie );
  $row = sql_select_single_row( "SELECT *, TIMESTAMPDIFF(MINUTE, muteReconfirmation_timestamp, NOW()) AS muteReconfirmation_elapsed FROM sessions WHERE id=$session_id", true );
  if( ! $row ) {
    $errors[] = "nicht angemeldet";
  } elseif( $cookie != $row['cookie'] ) {
    $errors[] = "(im Keks) nicht angemeldet";
  } else {
    // anmeldung ist gültig:
    $login_gruppen_id = $row['login_gruppen_id'];
    $login_dienst = $row['dienst'];
    $login_dienstname = $dienstinfos[$login_dienst]['label'];
    $dienstkontrollblatt_id = $row['dienstkontrollblatt_id'];
    $login_gruppen_name = sql_gruppenname( $login_gruppen_id );
    if (! is_null($row['muteReconfirmation_elapsed']) && $row['muteReconfirmation_elapsed'] < 60 )
        $reconfirmation_muted = TRUE;
  }
  if( ! array_key_exists( $login_dienst, $dienstinfos ) )
    $errors[] = "(intern) ungültiger dienst";
  if( $login_dienst > 0 ) {
    if( $dienstkontrollblatt_id > 0 ) {
      ( $row =  current( sql_dienstkontrollblatt( $dienstkontrollblatt_id ) ) )
        or $errors[] = "Dienstkontrollblatt-Eintrag nicht gefunden";
      $coopie_name = $row['name'];
    } else {
      $errors[] = "(intern) ungültige dienstkontrollblatt_id";
    }
  }
  if( ! $errors ) {  // login ok, weitermachen...
    $angemeldet = TRUE;
  } else {  // irgendwas war falsch... zurück auf los:
    logout();
  }
}

// prüfen, ob neue login daten übergeben werden:
//
get_http_var( 'login', 'w', '' );
switch( $login ) {
  case 'login':
    get_http_var( 'login_gruppen_id', 'u' )
      or $errors[] = "keine Gruppe ausgewählt";
    get_http_var( 'passwort','R' )
      or $errors[] = "kein Passwort angegeben";
    get_http_var( 'dienst', 'u' )
      or $errors[] = "kein Dienst ausgewählt";

    if( ! array_key_exists( $dienst, $dienstinfos ) ) {
      $errors[] = "kein gültiger Dienst angegeben";
    }

    if( $dienst != 0 ) {
      get_http_var( 'coopie_name', 'H', '' );
      if( ! $coopie_name || ( strlen( $coopie_name ) < 2 ) ) {
        $errors[] = "kein Name angegeben";
      }
      get_http_var( 'telefon', 'H', '' );
      get_http_var( 'notiz', 'H', '' );
    }

    if( ! $errors ) {
      if( $gruppe = check_password( $login_gruppen_id, $passwort ) ) {
        $login_gruppen_name = $gruppe['name'];
      } else {
        $errors[] = "Passwort leider falsch";
      }
    }

    if ( ( ! $errors ) && ( $dienst > 0 ) ) {
      $login_dienst = $dienst;
      $login_dienstname = $dienstinfos[$login_dienst]['label'];
      $dienstkontrollblatt_id = dienstkontrollblatt_eintrag(
        false, $login_gruppen_id, $login_dienst, $coopie_name, $telefon, $notiz
      );
    } else {
      $dienstkontrollblatt_id = 0;
    }

    if( ! $errors ) {
      // alles ok: neue session erzeugen:
      $cookie = random_hex_string( 5 );
      $session_id = sql_insert(
        'sessions',
        [
          'cookie'                 => $cookie,
          'login_gruppen_id'       => $login_gruppen_id,
          'dienst'                 => $login_dienst,
          'dienstkontrollblatt_id' => $dienstkontrollblatt_id
        ]
      );
      $keks = $session_id.'_'.$cookie;
      need( setcookie( 'foodsoftkeks', $keks, 0, '/', '', TRUE ), "setcookie() fehlgeschlagen" );
      $angemeldet = TRUE;
      logger( "successful login. client: {$_SERVER['HTTP_USER_AGENT']}" );
    }
    break;
  case 'logout':
    $messages[] = "Abgemeldet!";
  case 'silentlogout':
    // ggf. noch  dienstkontrollblatt-Eintrag aktualisieren:
    if( $login_dienst > 0 and $dienstkontrollblatt_id > 0 ) {
      need_http_var('coopie_name','H');
      need_http_var('telefon','H');
      need_http_var('notiz','H');
      dienstkontrollblatt_eintrag(
        $dienstkontrollblatt_id, $login_gruppen_id, $login_dienst, $coopie_name, $telefon, $notiz
      );
    }
    logout();
    break;
}

if( $angemeldet ) {
  if ($login_dienst > 0) {
    $theme = 'dienst';
  }
  return;
}

// ab hier: benutzer ist nicht eingeloggt; wir setzen alles zurück und zeigen das anmeldeformular:

logout();  // nicht korrekt angemeldet: alles zurücksetzen...
require_once("head.php");
setWikiHelpTopic( ':' );

open_div( 'kommentar', '', $motd );

open_javascript();
?>
function pick_login_dropdown() {
  var source = $('login_gruppen_id');
  var text = $('login_gruppen_id_text');

  text.value = source.value % 1000;
}

function pick_login_text() {
  var source = $('login_gruppen_id_text');
  var dropdown = $('login_gruppen_id');

  var options = dropdown.options;
  var group_id = 0;
  for (var i = 0; i < options.length; ++i) {
    if (options.item(i).value % 1000 == source.value) {
      group_id = options.item(i).value;
      break;
    }
  }
  dropdown.value = group_id;
}
<?php
close_javascript();

// we need $foodsoftdir in form action to allow login from DokuWiki:
//
open_form( "url=$foodsoftdir/index.php", 'login=login' );
  open_fieldset( 'small_form', "style='padding:2em;width:max-content;'", 'Anmelden' );
    foreach( $errors as $error ) {
      echo "<div class='warn'>FEHLER: $error</div>";
    }
    foreach( $messages as $msg ) {
      echo "<div class='ok'>$msg</div>";
    }

    open_div( 'newfield', '', "
      <p>
        <label class='login'> ". ( $FC_acronym == 'LS' ? 'Kunde:' : 'Gruppe:' ) ."</label>
        <input type='text' size='12' name='login_gruppen_id_text' id='login_gruppen_id_text' value=''
          onkeyup='pick_login_text();'>
        <select size='1' name='login_gruppen_id' id='login_gruppen_id'
          onchange='pick_login_dropdown();'>
        ". optionen_gruppen() ."
        </select>
      </p>
      <p>
        <label class='login'>Passwort:</label>
        <input type='password' size='12' name='passwort' value=''>
      </p>
        <div class='kommentar' id='nodienstform'>
          Um zu bestellen oder dein Gruppenkonto einzusehen,<br>
          brauchst Du hier keinen Dienst auszuwählen.
        </div>
      <p>
        <label class='login'>Dienst:</label>
        <select size='1' name='dienst' id='dienst'
          onchange='set_dienstform();'>
        ". optionen_dienste() . "
        </select>
      </p>
    " );
    open_div( '', "id='dienstform' style='display:" . ( $login_dienst ? 'block' : 'none' ) .";'" );
      open_div( 'kommentar', '', "
        Wenn Du Dich " . ( $FC_acronym == 'LS' ? 'für eine Aktion' : 'als diensthabend' ) . " anmeldest,
        kannst Du zusätzliche Funktionen der Foodsoft nutzen." ."<br>" .
        "Außerdem wirst Du automatisch ins " . ( $FC_acronym == 'LS' ? 'Kontrollblatt' : 'Dienstkontrollblatt' ) .
        " eingetragen:" );
      open_fieldset( 'small_form', '', ( $FC_acronym == 'LS' ? 'Kontrollblatt' : 'Dienstkontrollblatt' ) );
        open_div( 'newfield' );
          ?>
             <label>Dein Name:</label>
             <input type='text' size='20' name='coopie_name' value='<?php echo $coopie_name; ?>'>
             <label style='padding-left:4em;'>Telefon:</label>
             <input type='text' size='20' name='telefon' value='<?php $telefon; ?>'> <?php
        close_div();
        open_div( 'newfield' );
          ?> <label>Notiz fürs Dienstkontrollblatt:</label>
             <br>
             <textarea cols='80' rows='3' name='notiz'><?php echo $notiz; ?></textarea> <?php
        close_div();
      close_fieldset();
    close_div();
    open_div( 'newfield right' );
      submission_button('OK');
    close_div();
  close_fieldset();
  $login_form_id = "form_$form_id";
close_form();

open_javascript( "
  function set_dienstform() {
    const selected = \$('dienst').value;
    const display = selected != 0
      ? { dienst: 'block', nodienst: 'none'  }
      : { dienst: 'none' , nodienst: 'block' };
    \$('dienstform').style.display = display.dienst;
    \$('nodienstform').style.display = display.nodienst;
    const theme = selected != 0
      ? 'dienst'
      : 'normal';
    document.documentElement.setAttribute('data-theme', theme);
  }
  \$('$login_form_id').onsubmit = pick_login_text;
  document.observe('dom:loaded', pick_login_text);
" );

function optionen_dienste() {
  global $dienstinfos, $login_dienst;
  $optionen = "";
  $dienste_ordered = [
    DIENST_KEIN,
    DIENST_VORBEREITEN_ABRECHNEN,
    DIENST_ANNEHMEN,
    DIENST_VERTEILEN,
    DIENST_MITGLIEDER_DIENSTE
  ];
  foreach($dienste_ordered as $dienst) {
    $selected = $login_dienst == $dienst ? 'selected' : '';
    $label = $dienstinfos[$dienst]['label'];
    $description = $dienstinfos[$dienst]['description'];
    $optionen .= "<option value='$dienst' title='$description' $selected>$label</option>";
  }
  return $optionen;
}

function nur_fuer_dienst(...$dienste) {
  global $login_dienst;
  if ( in_array($login_dienst, $dienste) ) {
    return TRUE;
  }
  div_msg( 'warn', 'Keine Berechtigung' );
  exit();
}

function hat_dienst(...$dienste) {
  global $login_dienst;
  return in_array($login_dienst, $dienste);
}

exit();

?>
