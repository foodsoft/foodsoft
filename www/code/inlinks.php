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
  $options = $large_window_options;
  switch( strtolower( $name ) ) {
    case 'lieferschein':
    case 'bestellschein':
      $parameters['bestell_id'] = false;
      $parameters['gruppen_id'] = ( $dienst > 0 ? "0" : "$login_gruppen_id" );
      $options = $large_window_options;
      $window_id = 'bestellschein';
      break;
    case 'abrechnung':
      $parameters['bestell_id'] = false;
      $parameters['text'] = 'Abrechnung';
      $parameters['title'] = '&Uuml;bersichtsseite Abrechnung';
      $options = $large_window_options;
      $window_id = 'abrechnung';
      break;
    case 'verteilliste':
      $parameters['bestell_id'] = false;
      $options = $large_window_options;
      $window_id = 'verteilliste';
      break;
    case 'lieferantenkonto':
      $parameters['lieferanten_id'] = false;
      $parameters['img'] = 'img/b_chart.png';
      $parameters['title'] = 'Lieferantenkonto';
      $options = array_merge( $large_window_options, array( 'width' => '640' ) );
      $window_id = 'kontoblatt';
      break;
    case 'gruppenkonto':
      $parameters['gruppen_id'] = false;
      $options = array_merge( $large_window_options, array( 'width' => '640' ) );
      $window_id = 'kontoblatt';
      break;
    case 'pfandzettel':
      $parameters['lieferanten_id'] = false;
      $parameters['bestell_id'] = NULL;
      $options = $large_window_options;
      $window_id = 'kontoblatt';
      break;
    case 'edit_bestellung':
      $parameters['bestell_id'] = false;
      $options = array_merge( $small_window_options, array( 'width' => '460' ) );
      $window_id = 'edit_bestellung';
      break;
    case 'edit_lieferant':
      $parameters['lieferanten_id'] = NULL;
      $parameters['ro'] = NULL;
      $parameters['title'] = 'zu den Stammdaten des Lieferanten';
      $parameters['img'] = 'img/b_edit.png';
      $options = array_merge( $small_window_options, array( 'width' => '510', 'height' => 500 ) );
      $window_id = 'edit_lieferant';
      break;
    case 'produktverteilung':
      $parameters['bestell_id'] = false;
      $parameters['produkt_id'] = false;
      $options = array_merge( $large_window_options, array( 'width' => '600' ) );
      $window_id = 'produktverteilung';
      break;
    default:
      error( __LINE__, __FILE__, "undefiniertes Fenster: $name ", debug_backtrace());
  }
  return array( 'parameters' => $parameters, 'options' => $options, 'window_id' => $window_id );
}
 
function p_explode( $s ) {
  $r = array();
  $pairs = explode( ',', $s );
  foreach( $pairs as $pair ) {
    $v = explode( '=', $pair );
    $r[$v[0]] = $v[1];
  }
  return $r;
}

function fc_url( $name, $parameters = array(), $options = array() ) {
  global $foodsoftdir;

  $window = fc_window( $name );

  if( is_string( $parameters ) )
    $parameters = p_explode( $parameters );

  if( is_string( $options ) )
    $options = p_explode( $options );
  $options = array_merge( $window['options'], $options );

  $url = "$foodsoftdir/index.php?window=$name";
  foreach( $window['parameters'] as $key => $value ) {
    if( isset( $parameters[$key] ) )
      $value = $parameters[$key];
    elseif( $value === false )
      error( __LINE__, __FILE__, "parameter $key nicht uebergeben", debug_backtrace());
    if( $value === NULL )
      continue;
    $url .= "&$key=$value";
  }
  $option_string = '';
  $komma = '';
  foreach( $options as $key => $value ) {
    $option_string .= "$komma$key=$value";
    $komma = ',';
  }
  return "javascript:window.open('$url','{$window['window_id']}','$option_string').focus();";
}

function alink( $url, $text = false, $title = false, $img = false ) {
  if( $title ) {
    $title = "title='$title'";
    $alt = "alt='$title'";
  } else {
    $title = '';
    $alt = '';
  }
  $l = "<a href=\"$url\" $title";
  if( $img )
    $l .= " class='png' style='padding:0pt 1ex 0pt 1ex;'><img src='$img' border='0' $alt $title /";
  $l .= ">";
  if( $text )
    $l .= "$text";
  $l .= "</a>";
  return $l;
}

function fc_alink( $name, $parameters = array(), $options = array() ) {
  $window = fc_window( $name );
  if( is_string( $parameters ) )
    $parameters = p_explode( $parameters );
  $url = fc_url( $name, $parameters, $options );
  $parameters = array_merge( $window['parameters'], $parameters );
  $title = ( isset( $parameters['title'] ) ? $parameters['title'] : '' );
  $text = ( isset( $parameters['text'] ) ? $parameters['text'] : '' );
  $img = ( isset( $parameters['img'] ) ? $parameters['img'] : '' );
  return alink( $url, $text, $title, $img );
}


// function alink_bestellschein( $parameters, $name = false, $gruppen_id = 0, $img = 'img/b_browse.png' ) {
//   need( isset( $parameters['bestell_id'] ) );
//   switch( getState( $parameters['bestell_id'] ) ) {
//     case STATUS_BESTELLEN:
//       $title = 'zum vorl&auml;ufigen Bestellschein';
//       $name or $name = 'Bestellschein';
//       break;
//     case STATUS_LIEFERANT:
//       $title = 'zum Bestellschein';
//       $name or $name = 'Bestellschein';
//       break;
//     default:
//       $title = 'zum Lieferschein';
//       $name or $name = 'Lieferschein';
//       break;
//   }
//   return alink( fc_url( 'bestellschein', $parameters ), $name, $title, $img );
// }
// 
// function alink_abrechnung( $parameters ) {
//   return alink( fc_url( 'abrechnung', $parameters ), $name, "zur &Uuml;bersichtsseite Abrechnung", $img );
// }
// 
// function alink_verteilliste( $parameters, $name = 'Verteilliste', $img = false ) {
//   return alink( fc_url( 'verteilliste', $parameters ), $name, "zur Verteilliste", $img );
// }
// 
// 
// function alink_edit_bestellung( $parameters, $name = '', $img = 'img/b_edit.png' ) {
//   return alink( fc_url( 'edit_bestellung', $parameters), $name, 'Stammdaten der Bestellung edieren', $img );
// }
// function alink_edit_lieferant( $parameters = array(), $name = '', $img = 'img/b_edit.png' ) {
//   return alink( fc_url( 'edit_lieferant', $parameters ), $name, 'Formular Stammdaten des Lieferanten', $img );
// }
// 
// 
// function alink_produktverteilung( $parameters, $name = 'Produktverteilung', $img = 'img/chart.png' ) {
//   return alink( fc_url( 'produktverteilung', $parameters ), $name, 'Details zur Verteilung des Produkts', $img );
// }
// 
// function alink_lieferantenkonto( $parameters, $name = 'Lieferantenkonto', $img = 'img/chart.png' ) {
//   return alink( fc_url( 'lieferantenkonto', $parameters ), $name, 'Kontoübersicht des Lieferanten', $img );
// }
// function alink_gruppenkonto( $parameters, $name = 'Gruppenkonto', $img = 'img/chart.png' ) {
//   return alink( fc_url( 'gruppenkonto', $parameters ), $name, 'Kontoblatt der Gruppe', $img );
// }
// 
// function alink_pfandzettel( $parameters, $name = 'Pfandzettel', $img = 'img/fant.gif' ) {
//   return alink( fc_url( 'pfandzettel', $parameters ), $name, 'Fantkram', $img );
// }

?>
