<?php
  // Konfigurationsdatei einlesen
	require_once('code/config.php');
	
	// Funktionen zur Fehlerbehandlung laden
  require_once('code/views.php');
  require_once('code/zuordnen.php');
	require_once('code/err_functions.php');
	
  require_once('code/login.php');
	
	// egal ob get oder post verwendet wird...
	$HTTP_GET_VARS = array_merge($HTTP_GET_VARS, $HTTP_POST_VARS);

  get_http_var( 'area' );

  if($area == 'bestellt_faxansicht'){
  	include("bestellt_faxansicht.php");
	exit();
  }


		//head einfügen
	get_http_var( 'nohead' );
  if( ! $nohead ) include ( "$foodsoftpath/head.php" );

  if( ! $angemeldet ) {
    echo "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div></body></html>";
    exit();
  }
  global $login_gruppen_id;

  include('dienst_info.php');
	 
	    // Wenn kein Bereich gewählt wurde, dann Auswahlmenü präsentieren
	    if (!isset($area)) {
			   include('menu.php');
	    } else {
		    switch($area){
		    case "bestellen":
			   //darf nur bestellen, wenn Dienste akzeptiert
			   if (mysql_num_rows(sql_get_dienst_group($login_gruppen_id ,"Vorgeschlagen"))>0){
			       echo "<h2> Vor dem Bestellen bitte Dienstvorschläge akzeptieren </h2>";
			       include('dienstplan.php');
			   } else {
			       include('bestellen.php');		
			   }
			    break;
		    case "lieferschein":
			    //Fast gleich
			    include('bestellschein.php');
			    break;
		    case "wiki":
				echo "
				  <form action='/wiki/doku.php' name='gotowiki_form' method='get'>
				    <input type='hidden' name='do' value='show'>
				  </form>
				  <script type='text/javascript'>document.forms['gotowiki_form'].submit();</script>
				";
			    break;
		    default:
			    include($area.".php");
		    }
	    }
				 
				 

  echo "
    <table width='100%' class='footer'>
      <tr>
        <td style='padding-left:1em;text-align:left;'>aktueller Server: <kbd>$foodsoftserver</kbd></td>
        <td style='padding-right:1em;text-align:right;'>
        $mysqljetzt
  ";
  if( $readonly ) {
    echo "<span style='font-weight:bold;color:440000;'> --- !!! Datenbank ist schreibgeschuetzt !!!</span>";
  }
  echo "
      </td>
    </tr>
    </table>
    $print_on_exit
  ";
?>
