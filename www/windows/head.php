<html>
<head>
   <title>FC Potsdam  - Foodsoft</title>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-15" >
	<link rel='stylesheet' type='text/css' media='screen' href='/foodsoft/css/foodsoft.css' />
  <link rel='stylesheet' type='text/css' media='print' href=/foodsoft/css/print.css' />
  <!--  für die popups:  -->
	 <script src="/foodsoft/js/foodsoft.js" type="text/javascript" language="javascript"></script>	 
</head>
<body onload="jsinit();">
<div class="head">
  FC Nahrungskette - Foodsoft -
  <?php
    global $angemeldet, $login_gruppen_name, $coopie_name, $dienst;
    if( $angemeldet ) {
      if( $dienst > 0 ) {
        echo "$coopie_name ($login_gruppen_name) / Dienst $dienst";
      } else {
        echo "angemeldet: $login_gruppen_name";
      }
    }
  ?>
</div>
  
