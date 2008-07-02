<?php

// inlinks.php
// functions and definitions for internal hyperlinks, in particular: window properties


// global defaults for windows
// (these are really constants, but php doesn not support arrays-valued constants)
//
$large_window_options = array(
    'dependent' => 'yes'
  , 'toolbar' => 'yes'
  , 'menubar' => 'yes'
  , 'location' => 'yes'
  , 'scrollbars' => 'yes'
  , 'resizable' => 'yes'
);

$small_window_options = array(
    'dependent' => 'yes'
  , 'toolbar' => 'no'
  , 'menubar' => 'no'
  , 'location' => 'no'
  , 'scrollbars' => 'no'
  , 'resizable' => 'yes'
  , 'width' => '420'
  , 'height' => '460'
  , 'left' => '80'
  , 'top' => '80'
);


// fc_window:
// define optional and mandatory parameters and default options for sub-windows
//
function fc_window( $name ) {
  global $dienst, $login_gruppen_id, $large_window_options, $small_window_options;
  $parameters = array();
  switch( strtolower( $name ) ) {
    case 'lieferschein':
    case 'bestellschein':
      $parameters['bestell_id'] = false;
      $parameters['gruppen_id'] = ( $dienst > 0 ? "0" : "$login_gruppen_id" );
      $options = $large_window_options;
      $window_id = 'bestellschein';
      break;
    case 'editBestellung':
      $parameters['bestell_id'] = false;
      $options = array_merge( SMALL_WINDOW_OPTIONS, array( 'width' => '460' ) );
      $options = $small_window_options;
      $window_id = 'editBestellung';
      break;
    default:
      error( __LINE__, __FILE__, "undefiniertes Fenster: $name ", debug_backtrace());
  }
  return array( 'parameters' => $parameters, 'options' => $options, 'window_id' => $window_id );
}

function fc_url( $name, $parameters = array(), $options = array() ) {
  global $foodsoftdir;
  $window = fc_window( $name );
  $url = "$foodsoftdir/index.php?window=$name&";
  $and = '?';
  foreach( $window['parameters'] as $key => $value ) {
    if( isset( $parameters[$key] ) )
      $value = $parameters[$key];
    elseif( $value === false )
      error( __LINE__, __FILE__, "parameter $key nicht uebergeben", debug_backtrace());
    if( $value === NULL )
      continue;
    $url .= "$and$key=$value";
    $and = '&';
  }
  $options = array_merge( $window['options'], $options );
  $option_string = '';
  $komma = '';
  foreach( $options as $key => $value ) {
    $option_string .= "$komma$key=$value";
    $komma = ',';
  }
  return "javascript:window.open('$url','{$window['window_id']}','$option_string').focus();";
}


function alink( $url, $name = false, $title = false, $img = false ) {
  if( $title ) {
    $title = "title='$title'";
    $alt = "alt='$title'";
  } else {
    $title = '';
    $alt = '';
  }
  $l = "<a href=\"$url\" $title";
  if( $img )
    $l .= " class='png' style='padding:0pt 1ex 0pt 1ex;'";
  $l .= ">";
  if( $img )
    $l .=  "<img src='$img' border='0' $alt $title />";
  if( $name )
    $l .= "$name";
  $l .= "</a>";
  return $l;
}

function bestellschein_url( $bestell_id ) {
  return fc_url( 'bestellschein', array( 'bestell_id' => $bestell_id ) );
}

function alink_bestellschein( $bestell_id, $name = false, $gruppen_id = 0, $img = 'img/b_browse.png' ) {
  switch( getState( $bestell_id ) ) {
    case STATUS_BESTELLEN:
      $title = 'zum vorl&auml;ufigen Bestellschein';
      $name or $name = 'Bestellschein';
      break;
    case STATUS_LIEFERANT:
      $title = 'zum Bestellschein';
      $name or $name = 'Bestellschein';
      break;
    default:
      $title = 'zum Lieferschein';
      $name or $name = 'Lieferschein';
      break;
  }
  return alink( bestellschein_url( $bestell_id ), $name, $title, $img );
}

function abrechnung_url( $bestell_id ) {
  global $foodsoftdir;
  return "javascript:neuesfenster('"
         . "$foodsoftdir/index.php?window=abrechnung"
         . "&bestell_id=$bestell_id"
         . "','abrechnung' );";
}

function verteilung_url( $bestell_id ) {
  global $foodsoftdir;
  return "javascript:neuesfenster('"
         . "$foodsoftdir/index.php?window=verteilung"
         . "&bestell_id=$bestell_id"
         . "','verteilliste' );";
}


function link_editBestellung( $bestell_id, $img = 'img/b_edit.png' ) {
  return alink(
    "javascript:window.open('index.php?window=editBestellung&bestell_id=$bestell_id','editBestellung','width=460,height=420,left=100,top=100').focus();"
  , ' Bestellung edieren...'
  , 'Stammdaten der Bestellung &auml;ndern'
  , $img
  );
}

function link_abrechnung( $bestell_id, $name = '&Uuml;bersichtsseite Abrechnung', $img = false ) {
  return alink(
    "javascript:neuesfenster('index.php?window=abrechnung&bestell_id=$bestell_id','abrechnung');"
  , $name
  , 'Zur &Uuml;bersichtsseite Abrechnung'
  , $img
  );
}


function link_produktverteilung( $bestell_id, $produkt_id, $name = 'Produktverteilung', $img = false ) {
  return alink(
    "javascript:neuesfenster('index.php?window=showBestelltProd&bestell_id=$bestell_id&produkt_id=$produkt_id','produktverteilung');"
  , $name
  , "Details zur Verteilung"
  , $img
  );
}

function link_lieferantenkonto( $lieferanten_id, $name = 'Lieferantenkonto', $img = 'img/chart.png' ) {
  return alink(
    "javascript:neuesfenster('index.php?window=lieferantenkonto&lieferanten_id=$lieferanten_id','lieferantenkonto');"
  , $name, 'Kontoübersicht des Lieferanten', $img
  );
}

function link_pfandzettel( $lieferanten_id, $name = 'Pfandzettel', $img = 'img/fant.gif' ) {
  return alink(
    "javascript:neuesfenster('index.php?window=pfandverpackungen&lieferanten_id=$lieferanten_id','pfandzettel');"
  , $name, 'Fantkram', $img
  );
}

?>
