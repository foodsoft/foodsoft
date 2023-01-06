<?php
  global
    $angemeldet,
    $coopie_name,
    $foodsoftdir,
    $login_dienst,
    $login_dienstname,
    $login_gruppen_name,
    $readonly,
    $theme;

$headclass='head';
$payloadclass='';
if( $readonly ) {
  $headclass='headro';
  $payloadclass='ro';
}

$FC_acronym = adefault( $_SERVER, 'FC_acronym', '' );

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
open_tag( 'html' );
open_tag( 'head' );
?>
  <title>Food Coop <?php echo $foodcoop_name; ?>  - Foodsoft</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel='stylesheet' type='text/css' href='<?php echo $foodsoftbase; ?>/css/foodsoft.css'>
  <script src="<?php echo $foodsoftdir; ?>/js/lib/prototype.js"></script>
  <script src="<?php echo $foodsoftdir; ?>/js/foodsoft.js"></script>
  <script>setTheme('<?php echo $theme; ?>');</script>
<?php
close_tag( 'head' );
open_tag( 'body' );

open_div( $headclass, "id='header'" );
  open_table( '', "style='padding: 5pt'" );
    open_td( '', 'style="padding: 0px 5px"', "
          <a class='logo' href='index.php'>
            <span class='logoinvers'>$FC_acronym</span>
            <span class='logo'>$foodcoop_name</span>
            <span class='logo' style='font-weight:normal;font-size:medium'>Foodsoft</span>
          </a>
    " );
    open_td( '', "style='padding-top:1em;'" );
      if( $angemeldet ) {
        if( $login_dienst > 0 ) {
          echo "Hallo Dienst \"$login_dienstname\" | $coopie_name von $login_gruppen_name";
        } else {
          echo "Hallo Gruppe $login_gruppen_name";
        }
      }
    open_td( '', "style='text-align:right;padding-top:1em;'" );
      if( $angemeldet ) {
        if( $login_dienst > 0 ) {
          // für dienste: noch dienstkontrollblatteintrag aktualisieren:
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
      open_ul( '' , "id='menu' style='margin-bottom:0.5ex;'" );
        if( $angemeldet || ( $FC_acronym != 'LS' ) ) {
          foreach( possible_areas() as $menu_area )
            areas_in_head($menu_area);
        }
        open_li(); wikiLink( isset($window) ? "foodsoft:$window" : "", "zum Hilfe-Wiki...", true );
      close_ul();
  close_table();
close_div(); // header

open_div( $payloadclass, "id='payload'" );

?>
