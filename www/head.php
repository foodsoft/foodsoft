<html>
<head>
   <title>FC Potsdam  - Foodsoft</title>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-15" >
	<link rel="stylesheet" type="text/css" media="screen" href="css/foodsoft.css" />
   <link rel="stylesheet" type="text/css" media="print" href="css/print.css" />
<!--    für die popups 
   <script src="js/tooltip.js" type="text/javascript" language="javascript"></script>-->

	 <script src="js/foodsoft.js" type="text/javascript" language="javascript"></script>	 
</head>
<body onload="jsinit();">
<table class="head" width="100%">
  <tr>
    <td id="logo">
     	<a href="index.php"><span>FC</span> Nahrungskette</a> <span style="color:white; font-size:45%; letter-spacing: -1px; ">... Foodsoft</span>
    </td>
    <td style="padding-top:1em;">
      <?php
        global $angemeldet, $login_gruppen_name, $coopie_name, $dienst;
        if( $angemeldet ) {
          if( $dienst > 0 ) {
            echo "Hallo $coopie_name ($login_gruppen_name) vom Dienst $dienst!";
          } else {
            echo "Hallo Gruppe $login_gruppen_name!";
          }
        }
        echo '</td><td style="text-align:right;padding-top:1em;">';
        if( $angemeldet ) {
          echo "<a class='button' href='index.php?action=logout'>Abmelden</a>";
        } else {
          echo "(nicht angemeldet)";
        }
      ?>
    </td>
   </tr>
 </table>
  
  <ul id="menu">
 <li><a href="index.php?area=meinkonto" class="first">Mein Konto</a>
<!--      <ul>
      <li><a href="main.php?area=ModulStart&subarea=News">News</a></li>
      <li><a href="main.php?area=ModulStart&subarea=Termine">Termine</a></li>
      <li><a href="main.php?area=ModulStart&subarea=OwnEntries">Selbst eingetragene News/Termine bearbeiten</a></li>    
	</ul>  -->
	</li>
	<li><a href="index.php?area=bestellen" class="first">Bestellen</a>
	</li>
  <li><a href="index.php?area=bestellt<?php if( $angemeldet && ( $dienst == 0 ) ) echo "&gruppen_id=$login_gruppen_id"; ?>" class="first">Bestellungen ansehen</a>
	</li>
  <li><a href="index.php?area=produkte" class="first">Produktdatenbank</a>
  </li> 
	<li><a href="index.php?area=gruppen" class="first">Gruppenverwaltung</a>
  </li>
	  <li><a href="index.php?area=lieferanten" class="first">LieferantInnen</a>
  </li>
  <li><a href="../../wiki/" class="first">Wiki</a>
  </li>
</ul>
<div id="content">
