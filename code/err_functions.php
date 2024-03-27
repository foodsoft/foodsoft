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

//
// low-level error handling and logging
//

function error( $string ) {
  static $in_error = false;
  if( ! $in_error ) { // avoid infinite recursion (e.g. if there is no database connection)
    $in_error = true;
    $stack = debug_backtrace();
    open_div( 'warn' );
      smallskip();
      open_fieldset( '', '', "Fehler", 'off' );
        echo "<pre><br>[" .htmlspecialchars($string)."]<br>". htmlspecialchars( var_export( $stack, true ) ) . "</pre>";
      close_fieldset();
      open_span( 'qquad', '', fc_link( 'self', 'img=,text=weiter...' ) );
      bigskip();
    close_div();
    logger( "error: $string ({$stack[0]['file']}:{$stack[0]['line']})" );
  }
  die();
}

function need( $exp, $comment = "Problem" ) {
  static $in_need = false;
  if( ! $exp ) {
    if( $in_need )
      die();
    $in_need = true;
    $stack = debug_backtrace();
    open_div( 'warn' );
      smallskip();
      open_fieldset( '', '', htmlspecialchars( "$comment" ), 'off' );
        echo "<pre>". htmlspecialchars( var_export( $stack, true ) ) . "</pre>";
      close_fieldset();
      open_span( 'qquad', '', fc_link( 'self', 'img=,text=weiter...' ) );
      bigskip();
    close_div();
    logger( "need failed ({$stack[0]['file']}:{$stack[0]['line']})" );
    die();
  }
  return true;
}

function fail_if_readonly() {
  global $readonly;
  if( isset( $readonly ) and $readonly ) {
    open_div( 'warn', '', 'Datenbank ist schreibgesch&uuml;tzt - Operation nicht m&ouml;glich!' );
    die();
  }
  return true;
}

set_exception_handler(function ($e) {
  error( 'Unerwarteter Ausnahmefall: '.$e->getMessage() );
});

?>
