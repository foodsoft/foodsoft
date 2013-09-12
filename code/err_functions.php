<?PHP
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
    logger( "error: $string" );
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
    logger( "assertion failed: $exp" );
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

?>
