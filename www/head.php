<?php
  global $angemeldet, $login_gruppen_name, $coopie_name, $dienst
       , $readonly, $kopf_schon_ausgegeben, $print_on_exit;
  if( ! $kopf_schon_ausgegeben ) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
      <html>
      <head>
        <title>FC Potsdam  - Foodsoft</title>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
        <link rel='stylesheet' type='text/css' media='screen' href='/foodsoft/css/foodsoft.css'>
        <link rel='stylesheet' type='text/css' media='print' href='/foodsoft/css/print.css'>
        <!--  fuer die popups:  -->
        <script src='/foodsoft/js/foodsoft.js' type='text/javascript' language='javascript'></script>	 
      </head>
      <body onload='jsinit();'
    ";
    if( $readonly ) {
      echo " class='ro'";
    }
    echo "<table class='head' width='100%'";
    if( $readonly ) {
      echo " id='ro'";
    }
    echo "
      <tr>
      <td id='logo'>
        <a href='index.php'><span>FC</span>Nahrungskette</a> <span style='color:white; font-size:45%; letter-spacing: -1px;'>... Foodsoft</span>
      </td>
      <td style='padding-top:1em;'>
    ";
    if( $angemeldet ) {
      if( $dienst > 0 ) {
        echo "Hallo $coopie_name ($login_gruppen_name) vom Dienst $dienst!";
      } else {
        echo "Hallo Gruppe $login_gruppen_name!";
      }
    }
    echo '</td><td style="text-align:right;padding-top:1em;">';
    if( $angemeldet ) {
      if( $dienst > 0 ) {
        // fuer dienste: noch dienstkontrollblatteintrag aktualisieren:
        echo "<a class='button' href='index.php?area=dienstkontrollblatt&action=abmelden'>Abmelden</a>";
      } else {
        echo "<a class='button' href='index.php?action=logout'>Abmelden</a>";
      }
    } else {
      echo "(nicht angemeldet)";
    }
    echo "
        </td>
      </tr>
     </table>
  
     <ul id='menu' style='margin-bottom:1em;'>";
      foreach(possible_areas() as $menu_area){
        areas_in_head($menu_area);
      }
     echo "
     </ul>
<div id='payload'
    ";
    if($readonly) {
      echo " class='ro'>";
    } else {
      echo " class=''>";
    }
    $print_on_exit='</div></body></html>';

    $kopf_schon_ausgegeben = true;
  }
?>
