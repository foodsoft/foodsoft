<?php

// inlinks.php
// functions and definitions for internal hyperlinks, in particular: window properties


// global defaults for windows
// (these are really constants, but php doesn not support array-valued constants)
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
// define optional and mandatory parameters and default options for windows
//
function fc_window( $name ) {
  global $dienst, $login_gruppen_id, $large_window_options, $small_window_options, $self_fields;
  $parameters = array();
  $options = $large_window_options;
  switch( strtolower( $name ) ) {
    //
    // self: Anzeige im selben Fenster, per self_url():
    //
    case 'self':
      $parameters = $self_fields;
      $parameters['text'] = 'Reload';
      break;
    //
    // Anzeige im Hauptfenster (aus dem Hauptmenue) oder in "grossem" Fenster moeglich:
    //
    case 'index':
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Beenden';
      $options = $large_window_options;
      break;
    case 'meinkonto':
      $parameters['window'] = 'showGroupTransaktions';
      $parameters['window_id'] = 'gruppenkonto';
      $parameters['meinkonto'] = '1';
      $parameters['text'] = 'mein Konto';
      $parameters['title'] = 'zur Kontoanzeige der Gruppe';
      $parameters['img'] = 'img/euro.png';
      $options = $large_window_options;
      break;
    case 'bestellungen_overview':
      $parameters['window'] = 'bestellschein';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'alle Bestellungen';
      $parameters['title'] = 'Tabelle aller Bestellungen';
      $options = $large_window_options;
      break;
    case 'bestellen':
      $parameters['window'] = 'bestellen';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Bestellen';
      $parameters['title'] = 'zum Bestellformular';
      $parameters['bestell_id'] = NULL;
      $options = $large_window_options;
      break;
    case 'bilanz':
      $parameters['window'] = 'bilanz';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Bilanz';
      $parameters['img'] = 'img/chart.png';
      $parameters['title'] = 'zur &Uuml;bersicht &uuml;ber die Finanzen der Foodcoop';
      $options = $large_window_options;
      break;
    case 'produkte':
      $parameters['window'] = 'produkte';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Produkte';
      $parameters['title'] = 'zur Produktdatenbank der Foodsoft';
      $parameters['optionen'] = NULL;
      $options = $large_window_options;
      break;
    case 'gruppen':
      $parameters['window'] = 'gruppen';
      $parameters['window_id'] = 'gruppen';
      $parameters['optionen'] = NULL;
      $parameters['text'] = 'Gruppen';
      $parameters['title'] = 'zur Tabelle der Bestellgruppen';
      $options = $large_window_options;
      break;
    case 'lieferanten':
      $parameters['window'] = 'lieferanten';
      $parameters['window_id'] = 'lieferanten';
      $parameters['text'] = 'Lieferanten';
      $parameters['title'] = 'zur Tabelle aller Lieferanten';
      $options = $large_window_options;
      break;
    case 'basar':
      $parameters['window'] = 'basar';
      $parameters['window_id'] = 'basar';
      $parameters['bestell_id'] = NULL;
      $parameters['orderby'] = NULL;
      $parameters['text'] = "Basar";
      $parameters['title'] = "zur Basar&uuml;bersicht";
      $options = $large_window_options;
      break;
    case 'dienstkontrollblatt':
      $parameters['window'] = 'dienstkontrollblatt';
      $parameters['window_id'] = 'dienstkontrollblatt';
      $parameters['text'] = 'Dienstkontrollblatt';
      $parameters['title'] = 'zur Anzeige des Dienstkontrollblatts...';
      $parameters['id_to'] = NULL;
      $parameters['id_from'] = NULL;
      $options = $large_window_options;
      break;
    case 'updownload':
      $parameters['window'] = 'updownload';
      $parameters['window_id'] = 'updownload';
      $parameters['text'] = 'Up/Download';
      $parameters['title'] = 'zum Upload / Download der Datenbank...';
      $options = $large_window_options;
      break;
    case 'dienstplan':
      $parameters['window'] = 'dienstplan';
      $parameters['window_id'] = 'dienstplan';
      $parameters['text'] = 'Dienstplan';
      $parameters['title'] = 'zum Dienstplan...';
      $options = $large_window_options;
      break;
    //
    // "grosse" Fenster:
    //
    case 'abrechnung':
      $parameters['window'] = 'abrechnung';
      $parameters['window_id'] = 'abrechnung';
      $parameters['bestell_id'] = false;
      $parameters['text'] = 'Abrechnung';
      $parameters['title'] = '&Uuml;bersichtsseite Abrechnung';
      $options = array_merge( $large_window_options, array( 'width' => 860, 'height' => 720 ) );
      break;
    case 'bestellschein':
    case 'lieferschein':
      $parameters['window'] = 'bestellschein';
      $parameters['window_id'] = 'bestellschein';
      $parameters['img'] = 'img/b_chart.png';
      $parameters['bestell_id'] = false;
      $parameters['gruppen_id'] = NULL;
      $parameters['spalten'] = NULL;
      $options = $large_window_options;
      break;
    case 'gruppenkonto':
      $parameters['window'] = 'showGroupTransaktions';
      $parameters['window_id'] = 'kontoblatt';
      $parameters['gruppen_id'] = false;
      $parameters['img'] = 'img/euro.png';
      $options = array_merge( $large_window_options, array( 'width' => '1000' ) );
      break;
    case 'gruppenmitglieder':
      $parameters['window'] = 'gruppen_mitglieder';
      $parameters['window_id'] = 'gruppenmitglieder';
      $parameters['gruppen_id'] = false;
      $parameters['title'] = 'Gruppenmitglieder';
      $options = array_merge( $large_window_options, array( 'width' => '900' ) );
      break;
    case 'gruppenpfand':
      $parameters['window'] = 'gruppenpfand';
      $parameters['window_id'] = 'pfandzettel';
      $parameters['bestell_id'] = NULL;
      $parameters['title'] = 'Fantkram';
      $parameters['img'] = 'img/fant.gif';
      $options = $large_window_options;
      break;
    case 'kontoauszug':
      $parameters['window'] = 'konto';
      $parameters['window_id'] = 'kontoauszug';
      $parameters['konto_id'] = NULL;
      $parameters['auszug_nr'] = NULL;
      $parameters['auszug_jahr'] = NULL;
      $parameters['img'] = 'img/euro.png';
      $options = $large_window_options;
      break;
    case 'lieferantenkonto':
      $parameters['window'] = 'lieferantenkonto';
      $parameters['window_id'] = 'kontoblatt';
      $parameters['img'] = 'img/euro.png';
      $parameters['text'] = 'Lieferantenkonto';
      $parameters['title'] = 'zum Lieferantenkonto...';
      $parameters['lieferanten_id'] = false;
      $options = array_merge( $large_window_options, array( 'width' => '1000' ) );
      break;
    case 'pfandzettel':
      $parameters['window'] = 'pfandverpackungen';
      $parameters['window_id'] = 'pfandzettel';
      $parameters['title'] = 'Fantkram';
      $parameters['img'] = 'img/fant.gif';
      $parameters['lieferanten_id'] = NULL;
      $parameters['bestell_id'] = NULL;
      $options = $large_window_options;
      break;
    case 'produktpreise':
    case 'produktdetails':
      $parameters['window'] = 'terraabgleich';
      $parameters['window_id'] = 'produktdetails';
      $parameters['title'] = 'Preise und andere Produktdetails...';
      $parameters['text'] = ' Produktdetails';
      $parameters['bestell_id'] = NULL;;
      $parameters['produkt_id'] = false;
      $options = $large_window_options;
      break;
    case 'produktverteilung':
      $parameters['window'] = 'showBestelltProd';
      $parameters['window_id'] = 'produktverteilung';
      $parameters['img'] = 'img/b_browse.png';
      $parameters['title'] = 'Details zur Verteilung des Produkts...';
      $parameters['bestell_id'] = false;
      $parameters['produkt_id'] = false;
      $options = array_merge( $large_window_options, array( 'width' => '680', 'height' => '600' ) );
      break;
    case 'verluste':
      $parameters['window'] = 'verluste';
      $parameters['window_id'] = 'verluste';
      $options = $large_window_options;
      break;
    case 'verteilliste':
      $parameters['window'] = 'verteilung';
      $parameters['window_id'] = 'verteilliste';
      $parameters['bestell_id'] = false;
      $parameters['text'] = 'Verteilliste';
      $parameters['title'] = 'zur Verteilliste...';
      $options = $large_window_options;
      break;
    //
    // "kleine" Fenster:
    //
    case 'edit_bestellung':
      $parameters['window'] = 'editBestellung';
      $parameters['window_id'] = 'edit_bestellung';
      $parameters['bestell_id'] = false;
      $options = array_merge( $small_window_options, array( 'width' => '460' ) );
      break;
    case 'edit_lieferant':
      $parameters['window'] = 'editLieferant';
      $parameters['window_id'] = 'edit_lieferant';
      $parameters['lieferanten_id'] = NULL;
      $parameters['ro'] = NULL;
      $parameters['title'] = 'zu den Stammdaten des Lieferanten';
      $parameters['img'] = 'img/b_edit.png';
      $options = array_merge( $small_window_options, array( 'width' => '510', 'height' => 500 ) );
      break;
    default:
      error( __LINE__, __FILE__, "undefiniertes Fenster: $name ", debug_backtrace());
  }
  return array( 'parameters' => $parameters, 'options' => $options );
}

// parameters_explode:
// wandelt string "k1=v1,k2=k2,..." nach array( k1 => v1, k2 => v2, ...)
//
function parameters_explode( $s ) {
  $r = array();
  $pairs = explode( ',', $s );
  foreach( $pairs as $pair ) {
    $v = explode( '=', $pair );
    $r[$v[0]] = $v[1];
  }
  return $r;
}

function fc_url( $name, $parameters = array(), $options = array(), $scheme = 'javascript:' ) {
  global $foodsoftdir;

  if( is_string( $parameters ) )
    $parameters = parameters_explode( $parameters );

  $window = fc_window( $name );
  $parameters = array_merge( $window['parameters'], $parameters );
  $window_id = $parameters['window_id'];

  $url = "$foodsoftdir/index.php";
  $and = '?';
  foreach( $parameters as $key => $value ) {
    switch( $key ) {
      case 'img':
      case 'text':
      case 'title':
        continue 2; //  php counts switch as a loop!
    }
    if( $value === NULL )
      continue;
    if( $value === false )
      error( __LINE__, __FILE__, "parameter $key nicht uebergeben", debug_backtrace() );
    $url .= "$and$key=$value";
    $and = '&';
  }
  switch( $window_id ) {
    case 'self':
      return "javascript:self.location.href='$url';";
    case 'top':
    case 'main':
      $window_id = '_top';
    default:
      if( is_string( $options ) )
        $options = parameters_explode( $options );
      $options = array_merge( $window['options'], $options );
      $option_string = '';
      $komma = '';
      foreach( $options as $key => $value ) {
        $option_string .= "$komma$key=$value";
        $komma = ',';
      }
      return "{$scheme}window.open('$url','$window_id','$option_string').focus();";
  }
}

function alink( $url, $text = false, $title = false, $img = false ) {
  if( $title ) {
    $alt = "alt='$title'";
    $title = "title='$title'";
  } else {
    $alt = '';
    $title = '';
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

function buttonlink( $url, $text, $title = false ) {
  if( $title ) {
    $title = "title='$title'";
  } else {
    $title = '';
  }
  return "<input type='button' value='$text' class='bigbutton' $title onclick=\"$url\">";
}


function fc_alink( $name, $parameters = array(), $options = array() ) {
  $window = fc_window( $name );
  if( is_string( $parameters ) )
    $parameters = parameters_explode( $parameters );
  $url = fc_url( $name, $parameters, $options );
  $title = adefault( $window['parameters'], 'title', '' );
  $title = adefault( $parameters, 'title', $title );
  $text = adefault( $window['parameters'], 'text', '' );
  $text = adefault( $parameters, 'text', $text );
  $img = adefault( $window['parameters'], 'img', '' );
  $img = adefault( $parameters, 'img', $img );
  return alink( $url, $text, $title, $img );
}

function fc_button( $name, $parameters = array(), $options = array() ) {
  $window = fc_window( $name );
  if( is_string( $parameters ) )
    $parameters = parameters_explode( $parameters );
  $url = fc_url( $name, $parameters, $options );
  $title = adefault( $window['parameters'], 'title', '' );
  $title = adefault( $parameters, 'title', $title );
  $text = adefault( $window['parameters'], 'text', '' );
  $text = adefault( $parameters, 'text', $text );
  return buttonlink( $url, $text, $title );
}

function fc_openwindow( $name, $parameters = array(), $options = array() ) {
  $url = fc_url( $name, $parameters, $options, '' );
  return "
    <script type='text/javascript'> 
      $url
    </script>
  ";
}

?>

