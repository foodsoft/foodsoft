<?php
// head.php
//
// kopf fuer kleine popup-Fenster
// - $title (<title>) und $subtitle (im Fenster) werden angezeigt
// - ein "Close" Knopf wird automatisch erzeugt

  global $angemeldet, $login_gruppen_name, $coopie_name
       , $dienst, $title, $subtitle, $wikitopic, $onload_str, $readonly
       , $kopf_schon_ausgegeben, $print_on_exit
       , $foodsoftpath;

  if( ! $kopf_schon_ausgegeben ) {
    if( ! $title ) $title = "FC Nahrungskette - Foodsoft";
    if( ! $subtitle ) $subtitle = "FC Nahrungskette - Foodsoft";
    $img = "$foodsoftdir/img/close_black_trans.gif";
  
    if( $readonly ) {
      $ro='ro';
    } else {
      $ro='';
    }
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
      <html>
      <head>
        <title>$title</title>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
        <link rel='stylesheet' type='text/css' href='$foodsoftdir/css/foodsoft.css'>
        <script type='text/javascript' src='$foodsoftdir/js/foodsoft.js' language='javascript'></script>	 
      </head>
      <body onload='$onload_str' class='$ro'>
      <div style='padding:0.5ex 1em 0.5ex 1ex;margin:0pt 0pt 1em 0pt;' class='head$ro'>
      <table width='100%' class='head$ro'>
      <tr>
        <td style='padding-right:0.5ex;' class='head$ro'>
          <img src='$img' class='button' title='Schlie&szlig;en' onClick='if(opener) opener.focus(); window.close();'></img>
        </td>
        <td>Foodsoft: $subtitle</td>
        <td>
    ";
    if( $wikitopic ) {
      wikiLink( $wikitopic, "Hilfe-Wiki...", true );
    }
    echo "
        </td>
      </tr>
      <tr>
      <td>&nbsp;</td>
      <td style='font-size:11pt;'>
    ";
    if( $angemeldet ) {
      if( $dienst > 0 ) {
        echo "$coopie_name ($login_gruppen_name) / Dienst $dienst";
      } else {
        echo "angemeldet: $login_gruppen_name";
      }
    }
    if( $readonly ) {
      echo "<span style='padding-left:3em;'>schreibgeschuetzt!</span>";
    }
    echo "
      </td></tr>
      </table></div><div class='payload$ro'>
    ";
    $print_on_exit='</div></body></html>';
    $kopf_schon_ausgegeben = true;
  }
?>
