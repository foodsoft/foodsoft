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
      $parameters['text'] = 'Neu Laden';
      break;
    //
    // Anzeige im Hauptfenster (aus dem Hauptmenue) oder in "grossem" Fenster moeglich:
    //
    case 'index':
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Beenden';
      $parameters['title'] = 'zur&uuml;ck zum Hauptmen&uuml;';
      $options = $large_window_options;
      break;
    case 'meinkonto':
      $parameters['window'] = 'showGroupTransaktions';
      $parameters['window_id'] = 'gruppenkonto';
      $parameters['meinkonto'] = '1';
      $parameters['text'] = 'mein Konto';
      $parameters['title'] = 'zum Kontoblatt der Gruppe...';
      $parameters['img'] = 'img/euro.png';
      $options = $large_window_options;
      break;
    case 'bestellungen_overview':
      $parameters['window'] = 'bestellschein';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'alle Bestellungen';
      $parameters['title'] = 'Tabelle aller Bestellungen...';
      $parameters['img'] = 'img/b_browse.png';
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
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Bilanz';
      $parameters['img'] = 'img/chart.png';
      $parameters['title'] = 'zur &Uuml;bersicht &uuml;ber die Finanzen der Foodcoop...';
      $options = $large_window_options;
      break;
    case 'produkte':
      $parameters['window'] = 'produkte';
      $parameters['window_id'] = 'main';
      $parameters['text'] = 'Produkte';
      $parameters['title'] = 'zur Produktdatenbank der Foodsoft...';
      $options = $large_window_options;
      break;
    case 'gruppen':
      $parameters['window'] = 'gruppen';
      $parameters['window_id'] = 'gruppen';
      $parameters['text'] = 'Gruppen';
      $parameters['title'] = 'zur Liste der Bestellgruppen...';
      $options = $large_window_options;
      break;
    case 'lieferanten':
      $parameters['window'] = 'lieferanten';
      $parameters['window_id'] = 'lieferanten';
      $parameters['text'] = 'Lieferanten';
      $parameters['title'] = 'zur Liste aller Lieferanten...';
      $options = $large_window_options;
      break;
    case 'basar':
      $parameters['window'] = 'basar';
      $parameters['window_id'] = 'basar';
      $parameters['text'] = "Basar";
      $parameters['title'] = "zur Basar&uuml;bersicht...";
      $options = array_merge( $large_window_options, array( 'width' => 1200 ) );
      break;
    case 'dienstkontrollblatt':
      $parameters['window'] = 'dienstkontrollblatt';
      $parameters['window_id'] = 'dienstkontrollblatt';
      $parameters['text'] = 'Dienstkontrollblatt';
      $parameters['title'] = 'zur Anzeige des Dienstkontrollblatts...';
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
      $parameters['text'] = 'Abrechnung';
      $parameters['title'] = 'zur &Uuml;bersichtsseite Abrechnung...';
      $options = array_merge( $large_window_options, array( 'width' => 860, 'height' => 720 ) );
      break;
    case 'bestellschein':
    case 'lieferschein':
      $parameters['window'] = 'bestellschein';
      $parameters['window_id'] = 'bestellschein';
      $parameters['img'] = 'img/b_chart.png';
      $options = $large_window_options;
      break;
    case 'gruppenkonto':
      $parameters['window'] = 'showGroupTransaktions';
      $parameters['window_id'] = 'kontoblatt';
      $parameters['img'] = 'img/euro.png';
      $parameters['title'] = 'zum Kontoblatt der Gruppe...';
      $options = array_merge( $large_window_options, array( 'width' => '1000' ) );
      break;
    case 'gruppenmitglieder':
      $parameters['window'] = 'gruppen_mitglieder';
      $parameters['window_id'] = 'gruppenmitglieder';
      $parameters['title'] = 'zur Liste der Gruppenmitglieder...';
      $options = array_merge( $large_window_options, array( 'width' => '900' ) );
      break;
    case 'gruppenpfand':
      $parameters['window'] = 'gruppenpfand';
      $parameters['window_id'] = 'pfandzettel';
      $parameters['title'] = 'Fantkram';
      $parameters['img'] = 'img/fant.gif';
      $options = $large_window_options;
      break;
    case 'katalog':
    case 'artikelsuche':
      $parameters['window'] = 'artikelsuche';
      $parameters['window_id'] = 'katalog';
      $parameters['text'] = 'Lieferantenkatalog';
      $parameters['title'] = 'zum Katalog des Lieferanten...';
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
      $parameters['img'] = 'img/euro.png';
      $parameters['title'] = 'zum Kontoauszug...';
      $options = $large_window_options;
      break;
    case 'lieferantenkonto':
      $parameters['window'] = 'lieferantenkonto';
      $parameters['window_id'] = 'lieferantenkonto';
      $parameters['img'] = 'img/euro.png';
      $parameters['text'] = 'Lieferantenkonto';
      $parameters['title'] = 'zum Lieferantenkonto...';
      $options = array_merge( $large_window_options, array( 'width' => '1200' ) );
      break;
    case 'pfandzettel':
      $parameters['window'] = 'pfandverpackungen';
      $parameters['window_id'] = 'pfandzettel';
      $parameters['title'] = 'Fantkram...';
      $parameters['img'] = 'img/fant.gif';
      $options = $large_window_options;
      break;
    case 'produktpreise':
    case 'produktdetails':
      $parameters['window'] = 'terraabgleich';
      $parameters['window_id'] = 'produktdetails';
      $parameters['title'] = 'Preise und andere Produktdetails...';
      // $parameters['text'] = 'Produktdetails';
      $parameters['img'] = 'img/b_browse.png';
      $options = $large_window_options;
      break;
    case 'produktverteilung':
      $parameters['window'] = 'verteilung';
      $parameters['window_id'] = 'verteilliste';
      $parameters['img'] = 'img/b_browse.png';
      $parameters['title'] = 'Details zur Verteilung des Produkts...';
      $options = array_merge(
        $large_window_options, array( 'toolbar' => 'no', 'location' => 'no', 'width' => '840', 'menubar' => 'no','height' => '640' ) );
      break;
    case 'verluste':
      $parameters['window'] = 'verluste';
      $parameters['window_id'] = 'verluste';
      $parameters['text'] = 'Verlustaufstellung';
      $parameters['title'] = 'zur &Uuml;bersicht &uuml;ber die Verluste der Foodcoop...';
      $options = $large_window_options;
      break;
    case 'verteilliste':
      $parameters['window'] = 'verteilung';
      $parameters['window_id'] = 'verteilliste';
      $parameters['text'] = 'Verteilliste';
      $parameters['title'] = 'zur Verteilliste...';
      $options = array_merge( $large_window_options, array( 'width' => '840' ) );
      break;
    //
    // "kleine" Fenster:
    //
    case 'edit_bestellung':
      $parameters['window'] = 'editBestellung';
      $parameters['window_id'] = 'edit_bestellung';
      $parameters['title'] = 'zu den Stammdaten der Bestellung...';
      $parameters['img'] = 'img/b_edit.png';
      $options = array_merge( $small_window_options, array( 'width' => '460' ) );
      break;
    case 'edit_buchung':
      $parameters['window'] = 'editBuchung';
      $parameters['window_id'] = 'edit_buchung';
      $parameters['img'] = 'img/b_edit.png';
      $options = array_merge( $small_window_options, array( 'width' => '600', 'height' => '600' ) );
      break;
    // case 'edit_group':  //  im moment nicht benutzt
    case 'edit_lieferant':
      $parameters['window'] = 'editLieferant';
      $parameters['window_id'] = 'edit_lieferant';
      $parameters['title'] = 'zu den Stammdaten des Lieferanten...';
      $parameters['img'] = 'img/b_edit.png';
      $options = array_merge( $small_window_options, array( 'width' => '640', 'height' => 500 ) );
      break;
    case 'edit_produkt':
      $parameters['window'] = 'editProdukt';
      $parameters['window_id'] = 'edit_produkt';
      $parameters['title'] = 'zu den Stammdaten des Produkts...';
      $parameters['img'] = 'img/b_edit.png';
      $options = array_merge( $small_window_options, array( 'width' => '550', 'height' => 380 ) );
      break;
    case 'edit_verpackung':
      $parameters['window'] = 'editVerpackung';
      $parameters['window_id'] = 'edit_verpackung';
      $parameters['title'] = 'zu den Stammdaten der Pfandverpackung...';
      $parameters['img'] = 'img/b_edit.png';
      $options = array_merge( $small_window_options, array( 'width' => '500' ) );
      break;
    // case 'editProduktpreis': // nicht benutzt
    case 'insert_bestellung':
      $parameters['window'] = 'insertBestellung';
      $parameters['window_id'] = 'insert_bestellung';
      $parameters['title'] = 'neue Bestellvorlage anlegen...';
      $options = array_merge( $small_window_options, array( 'width' => '460' ) );
      break;
    case 'insert_produktgruppe':
      $parameters['window'] = 'insertProduktgruppe';
      $parameters['window_id'] = 'insert_produktgruppe';
      $parameters['title'] = 'neue Produktgruppe anlegen...';
      $options = array_merge( $small_window_options, array( 'width' => '460' ) );
      break;
    case 'insert_produktkategorie':
      $parameters['window'] = 'insertProduktkategorie';
      $parameters['window_id'] = 'insert_produktkategorie';
      $parameters['title'] = 'neue Produktkategorie anlegen...';
      $options = array_merge( $small_window_options, array( 'width' => '460' ) );
      break;
    // case 'insertGroup': // nicht benutzt
    //
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

function alink( $url, $text = '', $title = '', $img = false ) {
  $alt = '';
  if( $title ) {
    $alt = "alt='$title'";
    $title = "title='$title'";
  }
  $l = "<a href=\"$url\" $title>";
  if( $img ) {
    $l .= "<img src='$img' class='png' $alt $title />";
    $img = ' ';
  }
  if( $text )
    $l .= "$img$text";
  return $l . '</a>';
}

function buttonlink( $url, $text, $title = '' ) {
  if( $title )
    $title = "title='$title'";
  return "<input type='button' value='$text' class='bigbutton' $title onclick=\"$url\">";
}

function action_button( $label, $title, $fields, $mod_id = false, $class = '' ) {
  $s = "<div class='<? echo $class; ?>' style='white-space:nowrap;padding:0.1ex 1ex 0.1ex 1ex;'>
      <form style='margin:0ex;padding:0ex;' method='post' action='" . self_url() . "'>" . self_post();
  foreach( $fields as $name => $value )
     $s .= "<input type='hidden' name='$name' value='$value'>";
  $s .= "<input style='padding:0ex;margin:0ex;' type='submit' name='submit' value='$label'";
  if( $mod_id )
    $s .= " onclick=\"document.getElementById('$mod_id').className='modified';\"";
  if( $title )
    $s .= " title='$title'";
  $s  .= "></form></div>";
  return $s;
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

$action_form_id = 0;
function fc_action( $parameters = array() ) {
  global $action_form_id;
  $action_form_id++;
  $class = '';
  $title = '';
  $text = '';
  $img = '';
  $confirm = '';
  $l = "<form style='display:inline;' method='post' id='action_form_$action_form_id' action='" .self_url(). "'>" . self_post();
  foreach( $parameters as $name => $value ) {
    switch( $name ) {
      case 'title':
      case 'text':
      case 'img':
      case 'class':
      case 'confirm':
        $$name = $value;
        continue 2;
      default:
        $l .= "<input type='hidden' name='$name' value='$value'>";
    }
  }
  $alt = '';
  if( $title ) {
    $alt = "alt='$title'";
    $title = "title='$title'";
  }
  $l .= "<a class='$class' href='#' $title onclick=\"";
  if( $confirm ) {
    $l .= "if( confirm( '$confirm' ) ) ";
  }
  $l .= "document.forms['action_form_$action_form_id'].submit();\" ";
  $l .= '>';
  if( $img ) {
    $l .= "<img src='$img' class='png' $alt $title />";
    $img = ' ';
  }
  if( $text )
    $l .= "$img$text";
  $l .= "</a></form>";
  return $l;
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
