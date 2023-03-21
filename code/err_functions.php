<?PHP
//
// low-level error handling and logging
//

function is_ajax() {
  foreach( headers_list() as $header ) {
    if( $header === 'Content-Type: application/json' )
      return true;
  }
  return false;
}

function error( $string ) {
  if( is_ajax() ) {
    error_ajax( $string );
    return;
  }
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

function error_ajax( $reason, $http_error_code = 500 ) {
  static $in_error = false;
  if( $in_error )
    die();
  $in_error = true;
  $stack = debug_backtrace();
  if( $stack[0]['function'] === 'error' )
    array_shift( $stack );
  $response = [
    'success' => false
  , 'reason' => $reason
  , 'stack' => var_export( $stack, true )
  , 'itan' => $_POST['itan']
  , 'next_itan' => get_itan()
  ];
  http_response_code($http_error_code);
  echo json_encode( $response );
  logger( "error: $reason ({$stack[0]['file']}:{$stack[0]['line']})" );
  die();
}

function need( $exp, $comment = "Problem" ) {
  if( is_ajax() ) {
    need_ajax( $exp, $comment );
    return;
  }
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

function need_ajax( $exp, $comment = "Problem", $http_error_code = 500 ) {
  static $in_need = false;
  if( ! $exp ) {
    if( $in_need )
      die();
    $in_need = true;
    $stack = debug_backtrace();
    if( $stack[0]['function'] === 'need' )
      array_shift( $stack );
    $response = [
      'success' => false
    , 'reason' => 'need'
    , 'comment' => $comment
    , 'stack' => var_export( $stack, true )
    , 'itan' => $_POST['itan']
    , 'next_itan' => get_itan()
    ];
    http_response_code($http_error_code);
    echo json_encode( $response );
    logger( "need_ajax failed ({$stack[0]['file']}:{$stack[0]['line']})" );
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
