<?php
  global $angemeldet, $login_gruppen_name, $coopie_name, $login_dienst
       , $readonly, $foodsoftdir;

$headclass='head';
$payloadclass='';
if( $readonly ) {
  $headclass='headro';
  $payloadclass='ro';
}

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">\n";
open_tag( 'html' );
open_tag( 'head' );
?>
  <title>Food Coop <?php echo $foodcoop_name; ?>  - Foodsoft</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='<?php echo $foodsoftdir; ?>/css/foodsoft.css'>
  <script type='text/javascript' src='<?php echo $foodsoftdir; ?>/js/lib/prototype.js' language='javascript'></script>        
  <script type='text/javascript' src='<?php echo $foodsoftdir; ?>/js/foodsoft.js' language='javascript'></script>	 
<?php
close_tag( 'head' );
open_tag( 'body' );

open_div( 'layout_master', 'id="layout_master"' );
open_div( 'layout', 'id="main" style="height: 100%"' );
open_div( $headclass, "id='header'" );
  open_table( '', "width='100%'" );
    open_td();
      ?><a class='logo' href='index.php'>
          <span class='logoinvers'>FC</span><span class='logo'><?php echo $foodcoop_name; ?>... Foodsoft</span></a> <?php
    open_td( '', "style='padding-top:1em;'" );
      if( $angemeldet ) {
        if( $login_dienst > 0 ) {
          echo "Hallo $coopie_name ($login_gruppen_name) vom Dienst $login_dienst!";
        } else {
          echo "Hallo Gruppe $login_gruppen_name!";
        }
      }
    open_td( '', "style='text-align:right;padding-top:1em;'" );
      if( $angemeldet ) {
        if( $login_dienst > 0 ) {
          // fuer dienste: noch dienstkontrollblatteintrag aktualisieren:
          echo fc_action( 'window=dienstkontrollblatt,class=button,text=Abmelden,img=', 'action=abmelden' );
          // "<a class='button' href='index.php?window=dienstkontrollblatt&action=abmelden'>Abmelden</a>";
        } else {
          echo fc_action( 'class=button,text=Abmelden,img=', 'login=logout' );
          // echo "<a class='button' href='index.php?login=logout'>Abmelden</a>";
        }
      } else {
        echo "(nicht angemeldet)";
      }
  open_tr();
    open_td( '', "colspan='3' style='text-align:right;'" );
      open_ul( '', "id='menu' style='margin-bottom:0.5ex;'" );
        foreach( possible_areas() as $menu_area ) {
          areas_in_head($menu_area);
        }
        open_li(); wikiLink( isset($window) ? "foodsoft:$window" : "", "Hilfe-Wiki der Food Coop...", true );
      close_ul();
  close_table();
close_div();

open_div( $payloadclass, "id='payload'" );

?>
