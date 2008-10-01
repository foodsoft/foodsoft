<?PHP
//
// low-level error handling and logging
// (these functions must not depend on a working database connection)
//

function log_error($string,$stack) {
  global $logfile;
  if( isset($logfile) and $logfile ) {
    $fp = fopen( $logfile,"a" );
    // Fehler rausschreiben und dabei Leerzeichen maskieren (" " => %20)
    fputs( $fp, $line." ".$file.": ".str_replace(" ", "_", $string)." ".str_replace(" ", "_", $error)." ".var_export($stack, TRUE)."\n" );
    fclose($fp);
  }
}

function error( $string ) {
  $stack = debug_backtrace();
  // log_error($string,$stack);
  ?> <div class='warn'>Fehler: <? echo htmlspecialchars( $string ); ?>
     <br>
     <pre><? echo htmlspecialchars( var_export($stack) ); ?>
     </pre>
     </div>
   <?
  // if ($error_report_adress != "") mail($error_report_adress,$test_title." - Error mail!!",$fehler);
  die($string);
}

function need( $exp, $comment = "Fataler Fehler" ) {
  global $print_on_exit;
  if( ! $exp ) {
    ?> <div class='warn'><? echo htmlspecialchars( "$comment: $exp" ); ?> <a href='<? echo self_url(); ?>'>weiter...</a></div> <?
    echo "$print_on_exit";
    die();
  }
  return true;
}

function fail_if_readonly() {
  global $readonly, $print_on_exit;
  if( $readonly ) {
    ?> <div class='warn'>Datenbank ist schreibgesch&uuml;tzt - Operation nicht m&ouml;glich!</div> <?
    echo $print_on_exit;
    die();
  }
  return true;
}

?>
