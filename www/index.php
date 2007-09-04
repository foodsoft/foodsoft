<?php
  // Konfigurationsdatei einlesen
	require_once('code/config.php');
	
  require_once('code/views.php');
  require_once('code/zuordnen.php');
	// Funktionen zur Fehlerbehandlung laden
	require_once('code/err_functions.php');
	
  require_once('code/login.php');
  if( ! $angemeldet ) {
    echo "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div></body></html>";
    exit();
  }
	
	// egal ob get oder post verwendet wird...
	$HTTP_GET_VARS = array_merge($HTTP_GET_VARS, $HTTP_POST_VARS);

  global $self_fields;
  $self_fields = array();

  if( get_http_var( 'download','w' ) ) {  // Datei-Download (.pdf, ...): ohne Kopf
    $area = $download;
    $self_fields['download'] = $area;
    include( "$download.php" );
    exit();
  }
  if( get_http_var( 'window','w' ) ) {    // window: anzeige in Unterfenster (kleiner Kopf)
    $area = $window;
    $self_fields['window'] = $area;
    require_once( 'windows/head.php' );
    if( is_readable( "windows/$window.php" ) )
      include( "windows/$window.php" );
    else
      include( "$window.php" );
    echo "$print_on_exit";
    exit();
  } else {

    get_http_var( 'area','w' );             // area: anzeige im Hauptfenster (normaler Kopf)

    if($area == 'bestellt_faxansicht'){     // TODO: Aufruf per index.php?download=...
    	include("bestellt_faxansicht.php");
  	exit();
    }


    include ( "head.php" );
    include('dienst_info.php');

    global $login_gruppen_id;

	    // Wenn kein Bereich gewählt wurde, dann Auswahlmenü präsentieren
	    if (!isset($area)) {
			   include('menu.php');
	    } else {
        $self_fields['area'] = $area;
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
        case "bestellungen_overview":
			    //Fast gleich
			    include('bestellschein.php');
			    break;
		    case "wiki":
          reload_immediately( '$foodsoftdir/../wiki/doku.php?do=show' );
			    break;
		    default:
			    if(file_exists($area.".php")){
			        include($area.".php");
			    } else {
                              ?>
				      <div class='warn'>Ung&uuml;ltiger Bereich: <?echo($area)?></div></body></html>
			      <?
			        include('menu.php');
			    }
		    }
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
