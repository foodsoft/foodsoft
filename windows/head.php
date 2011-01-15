<?php
// head.php
//
// kopf fuer kleine popup-Fenster
// - $title (<title>) und $subtitle (im Fenster) werden angezeigt
// - ein "Close" Knopf wird automatisch erzeugt

global $angemeldet, $login_gruppen_name, $coopie_name
     , $login_dienst, $title, $subtitle, $wikitopic, $readonly
     , $area, $foodcoop_name;

if( ! $title ) $title = "FC $foodcoop_name - Foodsoft";
if( ! $subtitle ) $subtitle = "FC $foodcoop_name - Foodsoft";

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
  <title id='title'><?php echo $title; ?></title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='<?php echo $foodsoftdir; ?>/css/foodsoft.css'>
  <script type='text/javascript' src='<?php echo $foodsoftdir; ?>/js/lib/prototype.js' language='javascript'></script>
  <script type='text/javascript' src='<?php echo $foodsoftdir; ?>/js/foodsoft.js' language='javascript'></script>        
<?php
close_tag( 'head' );

open_tag( 'body' );

open_div( $headclass, "id='header' style='padding:0.5ex 1em 0.5ex 1ex;margin:0pt 0pt 1em 0pt;'" );
  open_table( $headclass, "width='100%'" );
    open_tr();
      open_td( 'oneline', 'style="width:80px;"'
                   , "<a class='close' title='Schließen' href='javascript:if(opener)opener.focus();window.close();'>
                      </a><a class='print' title='Ausdrucken' href='javascript:window.print();'>
                      </a><a class='reload' id='reload_button' title='Neu Laden' href='javascript:document.forms.update_form.submit();'>
                      </a>" );
      open_td( 'quad', "id='subtitle' ", $subtitle );
      open_td( '', "style='text-align:right;'" );
        wikiLink( ( $area ? "foodsoft:$area" : 'start' ) , "Hilfe-Wiki...", true );
    open_tr();
      open_td();
      open_td( '', "style='font-size:11pt;'" );
        if( $angemeldet ) {
          if( $login_dienst > 0 ) {
            echo "$coopie_name ($login_gruppen_name) / Dienst $login_dienst";
          } else {
            echo "angemeldet: $login_gruppen_name";
          }
        }
        if( $readonly )
          open_span( 'qquad', '', 'schreibgesch&uuml;tzt!' );
      open_td();
  close_table();
close_div();

open_div( $payloadclass, "id='payload'" );

?>
