<?

global $open_tags, $print_on_exit;
$open_tags = array();
$print_on_exit = array();

function open_tag( $tag, $class = '', $attr = '' ) {
  global $open_tags;
  echo "<$tag class='$class' $attr>\n";
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

function open_div( $class = '', $attr = '' ) {
  open_tag( 'div', $class, $attr );
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
    default:
      error( 'unmatched close_td' );
  }
}

function close_th() {
  close_td();
}

function open_form( $name = '', $class = '', $action = '' ) {
  if( ! $action ) {
    $action = self_url();
    $hidden = self_post();
  } else {
    $hidden = '';
  }
  if( $name )
    $name = "name='$name'";
  open_tag( 'form', $class, "$name method='post' action='$action'" );
  echo $hidden;
}

function close_form() {
  close_tag( 'form' );
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
