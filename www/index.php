<?php

require_once('code/common.php');

$window = 'menu';     // preliminary settings for login script
$window_id = 'main';
require_once( 'code/login.php' );
if( ! $angemeldet ) {
  div_msg( 'warn', "Bitte erst <a href='/foodsoft/index.php'>Anmelden...</a>" );
  exit();
}

if( get_http_var( 'download','w' ) ) {  // Spezialfall: Datei-Download (.pdf, ...): ohne HTTP-header!
  $window = $download;
  $self_fields['download'] = $window;
  include( "$download.php" );
  exit();
}

get_http_var( 'window', 'w', 'menu', true );     // eigentlich: name des skriptes
get_http_var( 'window_id', 'w', 'main', true );  // ID des browserfensters
switch( $window_id ) {
  case 'main':   // anzeige im hauptfenster des browsers
    include('head.php');
    include('dienst_info.php');
    switch( $window ) {
      case "wiki":
        reload_immediately( "$foodsoftdir/../wiki/doku.php?do=show" );
        break;
      case "bestellen":
        if ( !( $dienst == 4 ) and ( mysql_num_rows(sql_get_dienst_group($login_gruppen_id ,"Vorgeschlagen"))>0 ) ) {
         //darf nur bestellen, wenn Dienste akzeptiert
         ?> <h2> Vor dem Bestellen bitte Dienstvorschl&auml;ge akzeptieren </h2> <?
         include('windows/dienstplan.php');
         break;
        }
      default:
        if( is_readable( "windows/$window.php" ) ) {
          include( "windows/$window.php" );
        } else {
          div_msg( 'warn', "Ung&uuml;ltiger Bereich: $window" );
          include('windows/menu.php');
        }
    }
    open_table( 'footer', "width='100%'" );
      open_td( '', '', "aktueller Server: <kbd>$foodsoftserver</kbd>" );
      open_td( 'right' );
        echo $mysqljetzt;
        if( $readonly ) {
          echo "<span style='font-weight:bold;color:440000;'> --- !!! Datenbank ist schreibgeschuetzt !!!</span>";
        }
    close_table();
    break;
  default:   // anzeige in einem unterfenster
    require_once( 'windows/head.php' );
    if( is_readable( "windows/$window.php" ) ) {
      include( "windows/$window.php" );
    } else {
      div_msg( 'warn', "Ung&uuml;ltiger Bereich: $window" );
    }
    break;
}

// force new iTAN (this form must still be submittable after any other):
//
postform_id( true );
open_form( '', "name=update_form", "message=" );
  echo self_post();

?>
