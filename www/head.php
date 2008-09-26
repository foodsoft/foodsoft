<?php
  global $angemeldet, $login_gruppen_name, $coopie_name, $dienst
       , $readonly, $kopf_schon_ausgegeben, $print_on_exit, $foodsoftdir;

if( isset( $kopf_schon_ausgegeben ) && $kopf_schon_ausgegeben )
  return;

if( $readonly ) {
  $headclass='headro';
  $payloadclass='payloadro';
} else {
  $headclass='head';
  $payloadclass='payload';
}

?><!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
<head>
  <title>FC Potsdam  - Foodsoft</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='<? echo $foodsoftdir; ?>/css/foodsoft.css'>
  <script type='text/javascript' src='<? echo $foodsoftdir; ?>/js/foodsoft.js' language='javascript'></script>	 
</head>
<body>
<div id='header' class='<? echo $headclass; ?>' style='padding:0pt;margin:0pt;'>
<table width='100%'>
  <tr>
    <td><a class='logo' href='index.php'><span class='logoinvers'>FC</span><span class='logo'>Nahrungskette... Foodsoft</span></a></td>
    <td style='padding-top:1em;'>
      <?
        if( $angemeldet ) {
          if( $dienst > 0 ) {
            echo "Hallo $coopie_name ($login_gruppen_name) vom Dienst $dienst!";
          } else {
            echo "Hallo Gruppe $login_gruppen_name!";
          }
        }
      ?>
    </td>
    <td style='text-align:right;padding-top:1em;'>
      <?
        if( $angemeldet ) {
          if( $dienst > 0 ) {
            // fuer dienste: noch dienstkontrollblatteintrag aktualisieren:
            echo "<a class='button' href='index.php?window=dienstkontrollblatt&action=abmelden'>Abmelden</a>";
          } else {
            echo "<a class='button' href='index.php?login=logout'>Abmelden</a>";
          }
        } else {
          echo "(nicht angemeldet)";
        }
      ?>
    </td>
  </tr>
  <tr>
    <td colspan='3' style='text-align:right;'>
      <ul id='menu' style='margin-bottom:0.5ex;'>
      <?
        foreach(possible_areas() as $menu_area){
          areas_in_head($menu_area);
        }
      ?>
        <li><? wikiLink( isset($window) ? "foodsoft:$window" : "", "Hilfe-Wiki", true ); ?></li>
      </ul>
    </td>
  </tr>
</table>
</div>
<div id='payload' class='<? echo $payloadclass; ?>'>
<?
$print_on_exit = "</div></body></html>";
$kopf_schon_ausgegeben = true;
?>

