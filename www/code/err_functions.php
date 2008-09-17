<?PHP
//
// low-level error handling and logging
// (these functions must not depend on a working database connection)
//

function log_error($line,$file,$string,$error="",$stack) {
  global $logfile;
  if( isset($logfile) and $logfile ) {
    $fp = fopen( $logfile,"a" );
    // Fehler rausschreiben und dabei Leerzeichen maskieren (" " => %20)
    fputs( $fp, $line." ".$file.": ".str_replace(" ", "_", $string)." ".str_replace(" ", "_", $error)." ".var_export($stack, TRUE)."\n" );
    fclose($fp);
  }
}

function error( $line, $file, $string, $error="", $stack="" ){
  // global $test_title;

  log_error($line,$file,$string,$error,$stack);

  $fehler = "<div class='warn'><b>Fehler in Zeile ".$line." in ".$file."</b> ";
  $fehler .= "<br>" . htmlspecialchars($string) . "<br>";
  if($error) $fehler .= "<b>Error:</b> ". $error;
  if($stack) $fehler .= "<br><b>Stack:</b><br><pre><code>".htmlspecialchars(var_export($stack, TRUE))."</code></pre>";
  $fehler .= "</div>";

  // if ($error_report_adress != "") mail($error_report_adress,$test_title." - Error mail!!",$fehler);

  die($fehler);
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
