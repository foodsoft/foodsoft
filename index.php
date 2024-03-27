<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

$window = 'menu';     // preliminary settings for login script, or very early errors
$window_id = 'main';
require_once('code/common.php');

require_once( 'code/login.php' );
if( ! $angemeldet ) {
  div_msg( 'warn', "Bitte erst <a href='/foodsoft/index.php'>Anmelden...</a>" );
  exit();
}

if( get_http_var( 'download','W' ) ) {  // Spezialfall: Datei-Download (.pdf, ...): ohne HTTP-header!
  $window = $download;
  $self_fields['download'] = $window;
  include( "windows/$download.php" );
  exit();
}

get_http_var( 'window', 'w', 'menu', true );         // eigentlich: name des skriptes
get_http_var( 'window_id', 'w', 'main', true );   // ID des browserfensters

switch( $window_id ) {
  case 'main':   // anzeige im hauptfenster des browsers
    include('head.php');
    switch( $window ) {
      case "wiki":
        reload_immediately( "$foodsoftdir/../wiki/doku.php?do=show" );
        break;
      case 'menu':
      case "bestellen":
        // if( hat_dienst(0) )
          if( dienst_liste( $login_gruppen_id, 'bestaetigen lassen' ) )
            break;
      default:
        if( is_readable( "windows/$window.php" ) ) {
          include( "windows/$window.php" );
        } else {
          div_msg( 'warn', "Ung&uuml;ltiger Bereich: $window" );
          include('windows/menu.php');
        }
    }
    open_table( 'footer', "width='100%'" );
      open_td( '', '', "aktueller Server: <kbd>" .gethostname(). "</kbd>" );
      $version = "unknown";
      if (file_exists("version.txt")) {
        $version = file_get_contents("version.txt");
      }
      open_td( '', '', "Version: <kbd>$version</kbd>");
      open_td( 'right' );
        echo $mysqljetzt;
        if( $readonly ) {
          echo "<span style='font-weight:bold;color:440000;'> --- !!! Datenbank ist schreibgeschuetzt !!!</span>";
        }
    close_table();
    close_div(); // payload
    open_div('layout', 'id="footbar" style="display: none;"');
    close_div(); // layout: footbar

    $js_on_exit[] = "document.observe('dom:loaded', window.updateWindowHeight );";
    $js_on_exit[] = "Event.observe(window, 'resize', window.updateWindowHeight );";
    $js_on_exit[] = "window.scroller.register(document);";

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
get_itan( true );
open_form( 'name=update_form', 'action=nop,message=' );
// never POST on reload / backward / forward:
$js_on_exit[] = 'if ( window.history.replaceState ) window.history.replaceState( null, null, window.location.href );';
?>
