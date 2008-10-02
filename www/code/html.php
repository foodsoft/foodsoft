<?

global $open_tags, $print_on_exit;
$open_tags = array();
$print_on_exit = array();

function open_tag( $tag, $class = '', $attr = '' ) {
  global $open_tags;
  if( $class )
    $class = "class='$class'";
  echo "<$tag $class $attr>\n";
  $n = count( $open_tags );
  $open_tags[$n+1] = $tag;
}

function close_tag( $tag ) {
  global $open_tags;
  $n = count( $open_tags );
  if( $open_tags[$n] == $tag ) {
    echo "</$tag>";
    unset( $open_tags[$n--] );
  } else {
    error( "unmatched close_tag($tag)" );
  }
}

function open_div( $class = '', $attr = '', $payload = '' ) {
  open_tag( 'div', $class, $attr );
  if( $payload ) {
    echo $payload;
    close_div();
  }
}

function close_div() {
  close_tag( 'div' );
}

function open_table( $class = '', $attr = '' ) {
  open_tag( 'table', $class, $attr );
}

function close_table() {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'td':
    case 'th':
      close_tag( $open_tags[$n] );
    case 'tr':
      close_tag( 'tr' );
    case 'table':
      close_tag( 'table' );
      break;
    default:
      error( 'unmatched close_table' );
  }
}

function open_tr( $class = '', $attr = '' ) {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'td':
    case 'th':
      close_tag( $open_tags[$n] );
    case 'tr':
      close_tag( 'tr' );
    case 'table':
      open_tag( 'tr', $class, $attr );
      break;
    default:
      error( 'unexpected open_tr' );
  }
}

function close_tr() {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'td':
    case 'th':
      close_tag( $open_tags[$n] );
    case 'tr':
      close_tag( 'tr' );
      break;
    case 'table':
      break;  // already closed, never mind...
    default:
      error( 'unmatched close_tr' );
  }
}

function open_tdh( $tag, $class= '', $attr = '', $payload = '' ) {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'td':
    case 'th':
      close_tag( $open_tags[$n] );
    case 'tr':
      open_tag( $tag, $class, $attr );
      break;
    case 'table':
      open_tr();
      open_tag( $tag, $class, $attr );
      break;
    default:
      error( 'unexpected open_td' );
  }
  if( $payload ) {
    echo $payload;
    close_td();
  }
}

function open_td( $class= '', $attr = '', $payload = '' ) {
  open_tdh( 'td', $class, $attr, $payload );
}
function open_th( $class= '', $attr = '', $payload = '' ) {
  open_tdh( 'th', $class, $attr, $payload );
}

function close_td() {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'td':
    case 'th':
      close_tag( $open_tags[$n] );
      break;
    case 'tr':
    case 'table':
      break; // already closed, never mind...
    default:
      error( 'unmatched close_td' );
  }
}

function close_th() {
  close_td();
}

function html_in_tr() {
  global $open_tags;
  $n = count( $open_tags );
  return ( $open_tags[$n] == 'tr' );
}

function open_form( $class = '', $attr = '', $action = '', $hide = array() ) {
  global $self_fields;
  if( ! $action ) {
    $action = self_url();
    $hidden = self_post();
  } else {
    $hidden = self_post(true);
  }
  open_tag( 'form', $class, "method='post' action='$action' $attr" );
  echo $hidden;
  foreach( $hide as $key => $val ) {
    echo "<input type='hidden' name='$key' value='$val'>";
  }
}

function close_form() {
  global $onchange_handler;;
  $onchange_handler = '';
  close_tag( 'form' );
}

function open_fieldset( $class = '', $attr = '', $legend = '' ) {
  open_tag( 'fieldset', $class, $attr );
  if( $legend )
    echo "<legend>$legend</legend>";
}

function close_fieldset() {
  close_tag( 'fieldset' );
}

global $onchange_handler;;
$onchange_handler = '';

function floating_submission_button() {
  global $onchange_handler;;
  $onchange_handler = "onchange='document.getElementById(\"floatingbuttons\").style.display = \"inline\";'";

  open_tag( 'span', 'alert', "id='floatingbuttons'" );
    open_table();
      open_td('alert left');
        ?> <img class='button' src='img/close_black_trans.gif' onClick='document.getElementById("floatingbuttons").style.display = "none";'> <?
      open_td('alert center', '', "&Auml;nderungen sind noch nicht gespeichert!" );
    open_tr();
      open_td( 'alert center', "colspan='2'" );
        ?> <input type='submit' class='bigbutton' value='Speichern'>
           <input type='reset' class="bigbutton" value='Zur&uuml;cksetzen'
            onClick='document.getElementById("floatingbuttons").style.display = "none";'> <?
    close_table();
  close_tag('span');
}

function close_all_tags() {
  global $open_tags, $print_on_exit;
  while( $n = count( $open_tags ) ) {
    if( $open_tags[$n] == 'body' ) {
      foreach( $print_on_exit as $p )
        echo $p;
    }
    close_tag( $open_tags[$n] );
  }
}

register_shutdown_function( 'close_all_tags' );

function div_msg( $class, $msg, $backlink = false ) {
  echo "<div class='$class'>$msg " . ( $backlink ? fc_alink( $backlink, 'text=zur&uuml;ck...' ) : '' ) ."</div>";
}

?>
