<?PHP
//
// low-level error handling and logging
//

function error( $string ) {
  static $in_error = false;
  if( ! $in_error ) { // avoid infinite recursion (e.g. if there is no database connection)
    $in_error = true;
    $stack = debug_backtrace();
    ?> <div class='warn'>Fehler: <? echo htmlspecialchars( $string ); ?>
       <br>
       <pre><? echo htmlspecialchars( var_export($stack) ); ?>
       </pre>
       </div>
     <?
    logger( "error: $string [$stack]" );
  }
  die();
}

function need( $exp, $comment = "Fataler Fehler" ) {
  static $in_need = false;
  if( ! $exp ) {
    if( $in_need )
      die();
    $in_need = true;
    open_div( 'warn', '', htmlspecialchars( "$comment: $exp" ) . fc_link( 'self', 'img=,text=weiter...' ) );
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
