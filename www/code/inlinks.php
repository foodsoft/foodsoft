<?php

// inlinks.php
// functions and definitions for internal hyperlinks, in particular: window properties


// global defaults for windows
// (these are really constants, but php doesn not support array-valued constants)
//
// this file may be include from inside a function (from doku-wiki!), so we need `global':
//
global $large_window_options, $small_window_options;
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

// pseudo-parameters: when generating links and forms with the functions below,
// these parameters will never be transmitted via GET or POST; rather, they determine
// how the link itself will look and behave:
//
$pseudo_parameters = array( 'img', 'title', 'text', 'class', 'confirm', 'form', 'anchor', 'url', 'context' );

//
// internal functions (not supposed to be called by consumers):
//

// fc_window_defaults:
// define default values and default options for windows
//
function fc_window_defaults( $name ) {
  global $readonly, $dienst, $large_window_options, $small_window_options;
  $parameters = array();
  $options = $large_window_options;
  // echo "fc_window_defaults: $name<br>";
  switch( strtolower( $name ) ) {
    //
    // self: Anzeige im selben Fenster, per self_url():
    //
    case 'self':
      $parameters['window'] = $GLOBALS['window'];
      $parameters['window_id'] = $GLOBALS['window_id'];
      break;
    //
    // Anzeige im Hauptfenster (aus dem Hauptmenue) oder in "grossem" Fenster moeglich:
    //
    case 'index':
      $parameters['window'] = 'menu';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Beenden';
      $parameters['title'] = 'zur&uuml;ck zum Hauptmen&uuml;';
      $options = $large_window_options;
      break;
    case 'meinkonto':
      $parameters['window'] = 'gruppenkonto';
      $parameters['window_id'] = 'main';
      $parameters['meinkonto'] = '1';
      $parameters['text'] = 'mein Konto';
      $parameters['title'] = 'zum Kontoblatt der Gruppe...';
      $parameters['class'] = 'cash';
      $options = $large_window_options;
      break;
    case 'bestellungen_overview':
      $parameters['window'] = 'bestellschein';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'alle Bestellungen';
      $parameters['title'] = 'Tabelle aller Bestellungen...';
      $parameters['class'] = 'browse';
      $options = $large_window_options;
      break;
    case 'bestellen':
      $parameters['window'] = 'bestellen';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Bestellen';
      $parameters['title'] = 'zum Bestellformular...';
      $options = $large_window_options;
      break;
    case 'bilanz':
      $parameters['window'] = 'bilanz';
      $parameters['window_id'] = 'bilanz';
      $parameters['text'] = 'Bilanz';
      $parameters['class'] = 'chart';
      $parameters['title'] = 'zur &Uuml;bersicht &uuml;ber die Finanzen der Foodcoop...';
      $options = $large_window_options;
      break;
    case 'produkte':
      $parameters['window'] = 'produkte';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Produkte';
      $parameters['class'] = 'browse';
      $parameters['title'] = 'zur Produktdatenbank der Foodsoft...';
      $options = $large_window_options;
      break;
    case 'gruppen':
      $parameters['window'] = 'gruppen';
      $parameters['window_id'] = 'gruppen';
      $parameters['text'] = 'Gruppen';
      $parameters['class'] = 'browse';
      $parameters['title'] = 'zur Liste der Bestellgruppen...';
      $options = $large_window_options;
      break;
    case 'lieferanten':
      $parameters['window'] = 'lieferanten';
      $parameters['window_id'] = 'lieferanten';
      $parameters['text'] = 'Lieferanten';
      $parameters['class'] = 'browse';
      $parameters['title'] = 'zur Liste aller Lieferanten...';
      $options = $large_window_options;
      break;
    case 'basar':
      $parameters['window'] = 'basar';
      $parameters['window_id'] = 'basar';
      $parameters['text'] = "Basar";
      $parameters['title'] = "zur Basar&uuml;bersicht...";
      $parameters['class'] = 'browse';
      $options = array_merge( $large_window_options, array( 'width' => 1200 ) );
      break;
    case 'dienstkontrollblatt':
      $parameters['window'] = 'dienstkontrollblatt';
      $parameters['window_id'] = 'dienstkontrollblatt';
      $parameters['text'] = 'Dienstkontrollblatt';
      $parameters['title'] = 'zur Anzeige des Dienstkontrollblatts...';
      $parameters['class'] = 'browse';
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
      $parameters['class'] = 'browse';
      $options = $large_window_options;
      break;
    //
    // "grosse" Fenster:
    //
    case 'abrechnung':
      $parameters['window'] = 'abrechnung';
      $parameters['window_id'] = 'abrechnung';
      $parameters['text'] = 'Abrechnung';
      $parameters['title'] = 'zur &Uuml;bersichtsseite Abrechnung...';
      $parameters['class'] = 'record';
      $options = array_merge( $large_window_options, array( 'width' => 860, 'height' => 720 ) );
      break;
    case 'bestellschein':
    case 'lieferschein':
      $parameters['window'] = 'bestellschein';
      $parameters['window_id'] = 'bestellschein';
      $parameters['class'] = 'browse';
      $options = $large_window_options;
      break;
    case 'gruppenkonto':
      $parameters['window'] = 'gruppenkonto';
      $parameters['window_id'] = 'gruppenkonto';
      $parameters['text'] = 'Gruppenkonto';
      $parameters['title'] = 'zum Kontoblatt der Gruppe...';
      $parameters['class'] = 'cash';
      $options = array_merge( $large_window_options, array( 'width' => '1000' ) );
      break;
    case 'gruppenmitglieder':
      $parameters['window'] = 'gruppenmitglieder';
      $parameters['window_id'] = 'gruppenmitglieder';
      $parameters['text'] = 'Mitglieder';
      $parameters['title'] = 'zur Liste der Gruppenmitglieder...';
      $parameters['class'] = 'people';
      $options = array_merge( $large_window_options, array( 'width' => '800', 'height' => '600' ) );
      break;
    case 'gruppenpfand':
      $parameters['window'] = 'gruppenpfand';
      $parameters['window_id'] = 'gruppenpfand';
      $parameters['text'] = 'Gruppenpfand';
      $parameters['title'] = 'Fantkram';
      $parameters['class'] = 'fant';
      $options = $large_window_options;
      break;
    case 'katalog':
    case 'artikelsuche':
      $parameters['window'] = 'artikelsuche';
      $parameters['window_id'] = 'katalog';
      $parameters['text'] = 'Lieferantenkatalog';
      $parameters['title'] = 'zum Katalog des Lieferanten...';
      $parameters['class'] = 'browse';
      $options = array_merge( $large_window_options, array( 'width' => 1100, 'height' => 820 ) );
      break;
    case 'katalog_upload':
      $parameters['window'] = 'terrakatalog_upload';
      $parameters['window_id'] = 'katalog_upload';
      $parameters['text'] = 'Katalog einlesen';
      $parameters['title'] = 'zum Einlesen eines neuen Katalogs in die Foodsoft-Datenbank...';
      $options = array_merge( $large_window_options, array( 'width' => 1100, 'height' => 820 ) );
      break;
    case 'konto':
    case 'kontoauszug':
      $parameters['window'] = 'konto';
      $parameters['window_id'] = 'kontoauszug';
      $parameters['title'] = 'zum Kontoauszug...';
      $parameters['title'] = 'Kontoauszug';
      $parameters['class'] = 'cash';
      $options = $large_window_options;
      break;
    case 'lieferantenkonto':
      $parameters['window'] = 'lieferantenkonto';
      $parameters['window_id'] = 'lieferantenkonto';
      $parameters['text'] = 'Lieferantenkonto';
      $parameters['title'] = 'zum Lieferantenkonto...';
      $parameters['class'] = 'cash';
      $options = array_merge( $large_window_options, array( 'width' => '1200' ) );
      break;
    case 'pfandzettel':
    case 'pfandverpackungen':
      $parameters['window'] = 'pfandverpackungen';
      $parameters['window_id'] = 'pfandzettel';
      $parameters['text'] = 'Pfandzettel';
      $parameters['title'] = 'Fantkram...';
      $parameters['class'] = 'fant';
      $options = $large_window_options;
      break;
    case 'produktpreise':
    case 'produktdetails':
      $parameters['window'] = 'produktpreise';
      $parameters['window_id'] = 'produktpreise';
      $parameters['text'] = 'Produktpreise';
      $parameters['title'] = 'Preise und andere Produktdetails...';
      $parameters['class'] = 'browse';
      $options = $large_window_options;
      break;
    case 'produktverteilung':
    case 'verteilliste':
      if( $dienst > 0 ) {
        $parameters['window'] = 'produktverteilung';
        $parameters['window_id'] = 'verteilliste';
        $parameters['class'] = 'browse';
        $parameters['text'] = 'Produktverteilung';
        $parameters['title'] = 'Details zur Verteilung des Produkts...';
        $options = array_merge(
          $large_window_options, array( 'toolbar' => 'no', 'location' => 'no', 'width' => '840', 'menubar' => 'no','height' => '640' ) );
      } else {
        $parameters = NULL;
      }
      break;
    case 'verluste':
      $parameters['window'] = 'verluste';
      $parameters['window_id'] = 'verluste';
      $parameters['text'] = 'Verlustaufstellung';
      $parameters['class'] = 'browse';
      $parameters['title'] = 'zur &Uuml;bersicht &uuml;ber die Verluste der Foodcoop...';
        $options = array_merge(
          $large_window_options, array( 'toolbar' => 'no', 'location' => 'no', 'width' => '800', 'menubar' => 'no','height' => '1000' ) );
      break;
    case 'verlust_details':
      $parameters['window'] = 'verluste';
      $parameters['window_id'] = 'verlust_details';
      $parameters['text'] = 'Details...';
      $parameters['title'] = 'Zur Liste aller Einzelposten...';
      $parameters['class'] = 'browse';
      $options = array_merge(
        $large_window_options, array( 'toolbar' => 'no', 'location' => 'no', 'width' => '800', 'menubar' => 'no','height' => '1000' ) );
      break;
    //
    // "kleine" Fenster:
    //
    case 'editBestellung':
    case 'edit_bestellung':
      $parameters['window'] = 'editBestellung';
      $parameters['window_id'] = 'edit_bestellung';
      $parameters['title'] = 'zu den Stammdaten der Bestellung...';
      $parameters['class'] = ( ( $dienst == 4 and ! $readonly ) ? 'edit' : 'record' );
      $options = array_merge( $small_window_options, array( 'width' => '480' ) );
      break;
    case 'editBuchung':
    case 'edit_buchung':
      $parameters['window'] = 'editBuchung';
      $parameters['window_id'] = 'edit_buchung';
      $parameters['title'] = 'zu den Details der Buchung...';
      $parameters['class'] = ( ( $dienst == 4 and ! $readonly ) ? 'edit' : 'record' );
      $options = array_merge( $small_window_options, array( 'width' => '600', 'height' => '600' ) );
      break;
    // case 'edit_group':  //  im moment nicht benutzt
    case 'editLieferant':
    case 'edit_lieferant':
      $parameters['window'] = 'editLieferant';
      $parameters['window_id'] = 'edit_lieferant';
      $parameters['title'] = 'zu den Stammdaten des Lieferanten...';
      $parameters['class'] = ( ( $dienst == 4 and ! $readonly ) ? 'edit' : 'record' );
      $options = array_merge( $small_window_options, array( 'width' => '680', 'height' => 500 ) );
      break;
    case 'editProdukt':
    case 'edit_produkt':
      $parameters['window'] = 'editProdukt';
      $parameters['window_id'] = 'edit_produkt';
      $parameters['title'] = 'zu den Stammdaten des Produkts...';
      $parameters['class'] = ( ( $dienst == 4 and ! $readonly ) ? 'edit' : 'record' );
      $options = array_merge( $small_window_options, array( 'width' => '560', 'height' => 380 ) );
      break;
    case 'editVerpackung':
    case 'edit_verpackung':
      $parameters['window'] = 'editVerpackung';
      $parameters['window_id'] = 'edit_verpackung';
      $parameters['title'] = 'zu den Stammdaten der Pfandverpackung...';
      $parameters['class'] = ( ( $dienst == 4 and ! $readonly ) ? 'edit' : 'record' );
      $options = array_merge( $small_window_options, array( 'width' => '500' ) );
      break;
    case 'insertBestellung':
    case 'insert_bestellung':
      $parameters['window'] = 'insertBestellung';
      $parameters['window_id'] = 'insert_bestellung';
      $parameters['text'] = 'neue Bestellvorlage anlegen...';
      $parameters['title'] = 'neue Bestellvorlage anlegen...';
      $parameters['class'] = 'button';
      $options = array_merge( $small_window_options, array( 'width' => '460' ) );
      break;
    case 'insertProduktgruppe':
    case 'produktgruppen':
      $parameters['window'] = 'insertProduktgruppe';
      $parameters['window_id'] = 'produktgruppen';
      $parameters['title'] = 'Produktgruppen verwalten...';
      $parameters['text'] = 'Produktgruppen';
      $parameters['class'] = 'browse';
      $options = array_merge( $small_window_options, array( 'width' => '420', 'height' => 600, 'scrollbars' => 'yes' ) );
      break;
    default:
      error( "undefiniertes Fenster: $name " );
  }
  if( $parameters )
    return array( 'parameters' => $parameters, 'options' => $options );
  else
    return NULL;
}

// self_url:
// generate url to reload this page, with QUERY_STRING passing all
// variables from $self_fields, skipping those in $exclude:
// 
function self_url( $exclude = array() ) {
  global $self_fields;

  $output = 'index.php?';
  if( ! $exclude ) {
    $exclude = array();
  } elseif( is_string( $exclude ) ) {
    $exclude = array( $exclude );
  }
  foreach( $self_fields as $key => $value ) {
    if( ! in_array( $key, $exclude ) )
      $output = $output . "&$key=$value";
  }
  return $output;
}


// self_post:
// in jedem Formular wird automatisch eine eindeutige TAN, postform_id, einfgefuegt
//
function self_post( $exclude = array() ) {
  return "<input type='hidden' name='postform_id' value='".postform_id()."'>";
}

// parameters_explode:
// wandelt string "k1=v1,k2=k2,..." nach array( k1 => v1, k2 => v2, ...)
//
function parameters_explode( $s ) {
  $r = array();
  $pairs = explode( ',', $s );
  foreach( $pairs as $pair ) {
    $v = explode( '=', $pair );
    if( $v[0] == '' )
      continue;
    $r[$v[0]] = ( isset($v[1]) ? $v[1] : '' );
  }
  return $r;
}

// fc_url: generates internal URLs; arguments:
// - $parameters: GET-variables to pass in url: either array of key=>value pairs, or "k1=v1,k2=v2..." string
// - $options: window-options to pass to javascript:open_window(); same format as $parameters
// - $context: where this url is to be used; one of the following:
//    'href': ordinary hyperlink <a href=...>: may return javascript:... or plain url
//    'action': for <form action=...>. will never return javascript (as that won't work with POSTed variables)
//    'handler': for "onClick=..."-like handlers: always returns javascript, but no 'javascript:'-prefix
// some parameters have a special meaning (will not be used as GET variables):
//  - 'window': window-name, see fc_window_defaults(): determines target window and defaults for parameters and options
//     special case: 'self' means: 
//        - same script in same window and
//  - 'img', 'text', 'title', 'class': pseudo-parameters used in the higher-level functions below; will not be passed in url.
//  - 'anchor': generate #-anchor in url
//  - 'form': name of form to be submitted. allows submission of form into a different window. the action of the form
//     will be replaced by the generated url. Only meaningful with $context 'href' or 'handler'.
//  - 'button_id': only meaningful together with 'form': if a POST parameter 'button_id' is already present in the given
//     form, its value will be updated before submssion. this allows to identify which button was pressed.
//
function fc_url( $parameters, $options, $context ) {
  global $pseudo_parameters, $form_id;

  $window = $parameters['window'];
  $window_id = $parameters['window_id'];

  $url = 'index.php?';
  $form = '';
  $anchor = '';
  $button_id = '';
  $confirm = '';
  foreach( $parameters as $key => $value ) {
    switch( $key ) {
      case 'anchor':
        $anchor = "#$value";
        continue 2;
      case 'confirm':
        $confirm = "if( confirm( '$value' ) ) ";
        continue 2;
      case 'button_id':
        $button_id = $value;
        continue 2;
      case 'form':
        $form = ( $value ? $value : "form_$form_id" );
        continue 2;
      case 'url':
        $url = $value;
        continue 2;
      default:
        if( in_array( $key, $pseudo_parameters ) )
          continue 2;
    }
    if( $value === NULL )
      continue;
    $url .= "&$key=$value";
  }
  $url .= $anchor;

  $option_string = '';
  $komma = '';
  foreach( $options as $key => $value ) {
    $option_string .= "$komma$key=$value";
    $komma = ',';
  }

  if( ( $window_id == 'main' ) or ( $window_id == 'top' ) )
    $js_window_name = '_top';
  else
    $js_window_name = $window_id;

  $prefix = '';
  $reload = '';
  switch( $context ) {
    case 'action':
      return $url;
    case 'href':
      $prefix = 'javascript:';
    case 'handler':
      if( $form ) {
        if( $window_id != $GLOBALS['window_id'] )
          $reload = "self.location.href='" .self_url(). "';"; // force reload to issue new iTAN
        return "$prefix $confirm submit_form( '$form', '$url', '$js_window_name', '$option_string', '$button_id' ); $reload";
      } else {
        if( $window_id != $GLOBALS['window_id'] ) {
          return "$prefix $confirm window.open( '$url', '$js_window_name', '$option_string' ).focus();";
        } else {
          return "$prefix $confirm self.location.href='$url';";
        }
      }
    default:
      error( "undefinierter context: $context" );
  }
}

// alink: compose from parts and return an <a href=...> hyperlink
//
function alink( $url, $class = '', $text = '', $title = '', $img = false ) {
  $alt = '';
  if( $title ) {
    $alt = "alt='$title'";
    $title = "title='$title'";
  }
  $l = "<a class='$class' $title href=\"$url\">"; // NOTE: $url may contain '-quotes (but no "-quotes)!
  if( $img ) {
    $l .= "<img src='$img' class='icon' $alt $title />";
    if( $text )
      $l .= ' ';
  }
  if( $text )
    $l .= "$text";
  return $l . '</a>';
}


//////////////////////////////////////////////
//
// consumer-callable functions follow below:
//

// fc_link: create internal link:
//   $window: the name of the view; determines the script, the target window, and defaults
//   $parameters: GET parameters to be passed in url: either "k1=v1&k2=v2" string, or
//                array of 'name' => 'value' pairs
//   $options: window options to be passed in javascript:window_open()
// pseudo-parameters title, text, img determine the look of the link and will not be passed
//
function fc_link( $window = '', $parameters = array(), $options = array() ) {
  global $self_fields;

  if( ! $window )
    $window = 'self';
  if( is_string( $parameters ) )
    $parameters = parameters_explode( $parameters );
  if( is_string( $options ) )
    $options = parameters_explode( $options );
  if( $window == 'self' )
    $parameters = array_merge( $self_fields, $parameters );

  $window_defaults = fc_window_defaults( $window );
  $parameters = array_merge( $window_defaults['parameters'], $parameters );
  $options = array_merge( $window_defaults['options'], $options );

  $context = adefault( $parameters, 'context', 'href' );
  $url = fc_url( $parameters, $options, $context );

  switch( $context ) {
    case 'handler':
    case 'action':
      return $url;
    case 'href':
      $title = adefault( $parameters, 'title', '' );
      $text = adefault( $parameters, 'text', '' );
      $img = adefault( $parameters, 'img', '' );
      $class = adefault( $parameters, 'class', 'href' );
      return alink( $url, $class, $text, $title, $img );
  }
}

function fc_alink( $window = '', $parameters = array(), $options = array() ) {  // temporary kludge...
  return fc_link( $window, $parameters, $options );
}

// fc_action: generates simple form and one submit button.
// $get_parameters: determine the url as in fc_alink / fc_url. In particular, 'window'
//                  allows to call an arbitrary script in a different window.
// $post_parameter: additional parameters to be POSTed in hidden input fields.
//
function fc_action( $get_parameters = array(), $post_parameters = array(), $options = array() ) {
  global $print_on_exit, $pseudo_parameters;

  if( is_string( $get_parameters ) )
    $get_parameters = parameters_explode( $get_parameters );
  if( is_string( $post_parameters ) )
    $post_parameters = parameters_explode( $post_parameters );

  $form_id = new_html_id();

  $window = adefault( $get_parameters, 'window', 'self' );
  $window_defaults = fc_window_defaults( $window );
  $get_parameters = array_merge( $window_defaults['parameters'], $get_parameters );

  $title = adefault( $get_parameters, 'title', '' );
  $text = adefault( $get_parameters, 'text', '' );
  $class = adefault( $get_parameters, 'class', 'button' );
  $confirm = adefault( $get_parameters, 'confirm', '' );
  $img = adefault( $get_parameters, 'img', '' );

  $form = "<form style='display:inline;' method='post' id='form_$form_id' name='form_$form_id' action=''>";
  $form .= self_post();
  foreach( $post_parameters as $name => $value ) {
    $form .= "<input type='hidden' name='$name' value='$value'>";
  }
  $form .= "</form>";
  // we may be inside another form, but forms cannot be nested; so we append this form at the end:
  $print_on_exit[] = $form;

  // $get_parameters['context'] = 'href';
  $get_parameters['form'] = "form_$form_id";

  return fc_link( $window, $get_parameters, $options );
}

function fc_openwindow( $window, $parameters = array(), $options = array() ) {
  $parameters['window'] = $window;
  open_javscript( fc_url( $parameters, $options, 'handler' ) );
}

function reload_immediately( $url ) {
  open_form( '', "name='reload_now_form'", array( 'url' => $url ) ); close_form();
  open_javascript( "document.forms['reload_now_form'].submit();" );
  exit();
}
?>
