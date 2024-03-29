<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

  global $angemeldet, $login_gruppen_name, $coopie_name, $login_dienst
       , $readonly, $foodsoftdir;

$headclass='head';
$payloadclass='';
if( $readonly ) {
  $headclass='headro';
  $payloadclass='ro';
}

$FC_acronym = adefault( $_SERVER, 'FC_acronym', 'FC' );

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">\n";
open_tag( 'html' );
open_tag( 'head' );
?>
  <title>Food Coop <?php echo $foodcoop_name; ?>  - Foodsoft</title>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' >
  <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Permanent+Marker">
  <link rel='stylesheet' type='text/css' href='<?php echo $foodsoftbase; ?>/css/foodsoft.css'>
  <link rel='icon' type='image/vnd.microsoft.icon' href='<?php echo $foodsoftbase; ?>/img/favicon.ico'>
  <script type='text/javascript' src='<?php echo $foodsoftdir; ?>/js/lib/prototype.js' language='javascript'></script>
  <script type='text/javascript' src='<?php echo $foodsoftdir; ?>/js/foodsoft-2.js' language='javascript'></script>
<?php
close_tag( 'head' );
open_tag( 'body' );

open_div( $headclass, "id='header'" );
  open_table( '', "width='100%'" );
    open_td( '', '', "
          <a class='logo' href='index.php'>
          <span class='logoinvers'>$FC_acronym</span><span class='logo'>$foodcoop_name... Foodsoft</span></a>
    " );
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
