<?php
// head.php
//
// kopf fuer kleine popup-Fenster
// - $title (<title>) und $subtitle (im Fenster) werden angezeigt
// - ein "Close" Knopf wird automatisch erzeugt

global $angemeldet, $login_gruppen_name, $coopie_name
     , $dienst, $title, $subtitle, $wikitopic, $onload_str, $readonly
     , $foodsoftpath, $area;

if( ! $title ) $title = "FC Nahrungskette - Foodsoft";
if( ! $subtitle ) $subtitle = "FC Nahrungskette - Foodsoft";
$img = "$foodsoftdir/img/close_black_trans.gif";

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
  <title id='title'><? echo $title; ?></title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='<? echo $foodsoftdir; ?>/css/foodsoft.css'>
  <script type='text/javascript' src='<? echo $foodsoftdir; ?>/js/foodsoft.js' language='javascript'></script>	 
<?
close_tag( 'head' );

open_tag( 'body' );

open_div( $headclass, "id='header' style='padding:0.5ex 1em 0.5ex 1ex;margin:0pt 0pt 1em 0pt;'" );
  open_table( $headclass, "width='100%'" );
    open_tr();
      open_td();
        ?> <img src='img/close_black_trans.gif' class='button' alt='Schlie&szlig;en' title='Schlie&szlig;en'
             width='15' onClick='if(opener) opener.focus(); window.close();'> <?
      open_td( '', "id='subtitle'" );
        echo $subtitle;
      open_td( '', "style='text-align:right;'" );
        wikiLink( ( $area ? "foodsoft:$area" : 'start' ) , "Hilfe-Wiki...", true );
    open_tr();
      open_td();
      open_td( '', "style='font-size:11pt;'" );
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
  close_table();
close_div();

open_div( $payloadclass, "id='payload'" );

?>
