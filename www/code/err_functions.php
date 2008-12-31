<?PHP
//
// low-level error handling and logging
//

global $in_error;
$in_error = false;

function error( $string ) {
  global $in_error;
  $stack = debug_backtrace();
  ?> <div class='warn'>Fehler: <? echo htmlspecialchars( $string ); ?>
     <br>
     <pre><? echo htmlspecialchars( var_export($stack) ); ?>
     </pre>
     </div>
   <?
  if( ! $in_error ) { // avoid infinite recursion (e.g. if there is no database connection)
    $in_error = true;
    logger( "error: $string [$stack]" );
  }
  die();
}

function need( $exp, $comment = "Fataler Fehler" ) {
  global $in_error;
  if( ! $exp ) {
    open_div( 'warn', htmlspecialchars( "$comment: $exp" ) . fc_link( 'img=,text=weiter...' ) );
    if( ! $in_error ) {
      $in_error = true;
      logger( "assertion failed: $exp" );
    }
    die();
  }
  return true;
}

function fail_if_readonly() {
  global $readonly;
  if( $readonly ) {
    open_div( 'warn', 'Datenbank ist schreibgesch&uuml;tzt - Operation nicht m&ouml;glich!' );
    die();
  }
  return true;
}

?>
