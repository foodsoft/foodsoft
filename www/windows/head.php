<?php
// head.php
//
// kopf fuer kleine popup-Fenster
// - $title (<title>) und $subtitle (im Fenster) werden angezeigt
// - ein "Close" Knopf wird automatisch erzeugt

  global $angemeldet, $login_gruppen_name, $coopie_name, $dienst, $title, $subtitle, $onload_str;
  if( ! $title ) $title = "FC Nahrungskette - Foodsoft";
  if( ! $subtitle ) $subtitle = "FC Nahrungskette - Foodsoft";
  if( is_file( '../img/close_black_trans.gif' ) )
    $img = '../img/close_black_trans.gif';
  else
    $img = 'img/close_black_trans.gif';

  echo "
    <html>
    <head>
      <title>$title</title>
      <meta http-equiv='Content-Type' content='text/html; charset=ISO-8859-15' >
      <link rel='stylesheet' type='text/css' media='screen' href='/foodsoft/css/foodsoft.css' />
      <link rel='stylesheet' type='text/css' media='print' href=/foodsoft/css/print.css' />
      <!--  für die popups:  -->
      <script src='/foodsoft/js/foodsoft.js' type='text/javascript' language='javascript'></script>	 
    </head>
    <body onload='$onload_str'>
    <div class='head' style='padding:0.5ex 1em 0.5ex 1ex;margin:0pt 0pt 1em 0pt;'>
    <table width='100%'>
    <tr>
      <td style='padding-right:0.5ex;'>
        <img src='$img' class='button' title='Schlie&szlig;en' onClick='opener.focus(); window.close();'></img>
      </td>
      <td>Foodsoft: $subtitle</td>
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
  echo "</td></tr></table></div>";
?>

