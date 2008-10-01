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

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n";
open_tag( 'html' );
open_tag( 'head' );
?>
  <title>FC <? echo $foodcoop_name; ?>  - Foodsoft</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='<? echo $foodsoftdir; ?>/css/foodsoft.css'>
  <script type='text/javascript' src='<? echo $foodsoftdir; ?>/js/foodsoft.js' language='javascript'></script>	 
<?
close_tag( 'head' );
open_tag( 'body' );

open_div( $headclass, "id='header'" );
  open_table( '', "width='100%'" );
    open_td();
      ?><a class='logo' href='index.php'>
          <span class='logoinvers'>FC</span><span class='logo'><? echo $foodcoop_name; ?>... Foodsoft</span></a> <?
    open_td( '', "style='padding-top:1em;'" );
      if( $angemeldet ) {
        if( $dienst > 0 ) {
          echo "Hallo $coopie_name ($login_gruppen_name) vom Dienst $dienst!";
        } else {
          echo "Hallo Gruppe $login_gruppen_name!";
        }
      }
    open_td( '', "style='text-align:right;padding-top:1em;'" );
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
  open_tr();
    open_td( '', "colspan='3' style='text-align:right;'" );
      open_tag( 'ul', '', "id='menu' style='margin-bottom:0.5ex;'" );
        foreach(possible_areas() as $menu_area){
          areas_in_head($menu_area);
        }
        ?><li><? wikiLink( isset($window) ? "foodsoft:$window" : "", "Hilfe-Wiki", true ); ?></li><?
      close_tag( 'ul' );
  close_table();
close_div();

open_div( $payloadclass, "id='payload'" );

?>
