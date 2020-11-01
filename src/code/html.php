<?php

global $open_tags      /* keep track of open tags */
     , $print_on_exit  /* print this just before </body> */
     , $js_on_exit     /* javascript code to insert just before </body> */
     , $html_id        /* draw-a-number-box to generate unique ids */
     , $form_id        /* id of the currently open form (if any) */
     , $input_event_handlers  /* insert into <input> and similar inside a form */
     , $html_hints     /* online hints to display for fields */
     , $table_level      /* nesting level for tables */
     , $table_row_number /* stack of table row counters */
;
$open_tags = array();
$print_on_exit = array();
$js_on_exit = array();
$html_hints = array();
$html_id = 0;
$input_event_handlers = '';
$form_id = '';

global $td_title, $tr_title;  /* can be used to set title for next <td> or <tr> */
$td_title = '';
$tr_title = '';

// set flags to activate workarounds for known browser bugs:
//
$browser = $_SERVER['HTTP_USER_AGENT'];
global $activate_mozilla_kludges, $activate_safari_kludges, $activate_exploder_kludges, $activate_konqueror_kludges;
$activate_safari_kludges = 0;
$activate_mozilla_kludges = 0;
$activate_exploder_kludges = 0;
$activate_konqueror_kludges = 0;
if( preg_match ( '/safari/i', $browser ) ) {  // safari sends "Mozilla...safari"!
  $activate_safari_kludges = 1;
} else if( preg_match ( '/konqueror/i', $browser ) ) {  // dito: konqueror
  $activate_konqueror_kludges = 1;
} else if( preg_match ( '/^mozilla/i', $browser ) ) {  // plain mozilla(?)
  $activate_mozilla_kludges = 1;
} else if( preg_match ( '/^msie/i', $browser ) ) {
  $activate_exploder_kludges = 1;
}

// new_html_id(): increment and return next unique id:
//
function new_html_id() {
  global $html_id;
  return ++$html_id;
}

// open_tag(), close_tag(): open and close html tag. wrong nesting will cause an error
//
function open_tag( $tag, $class = '', $attr = '', $payload = null ) {
  global $open_tags;
  if( $class )
    $class = "class='$class'";
  echo "<$tag $class $attr>\n";
  $n = count( $open_tags );
  $open_tags[$n+1] = $tag;
  if (!is_null($payload)) {
    echo $payload;
    close_tag($tag);
  }
}

function close_tag( $tag ) {
  global $open_tags, $hidden_input;
  $n = count( $open_tags );
  switch( $tag ) {
    case 'form':
      echo $hidden_input;
      $hidden_input = '';
      break;
  }
  if( $open_tags[$n] == $tag ) {
    echo "</$tag>";
    unset( $open_tags[$n--] );
  } else {
    error( "unmatched close_tag(got:$tag / expected:{$open_tags[$n]})" );
  }
}

function open_div( $class = '', $attr = '', $payload = false ) {
  open_tag( 'div', $class, $attr );
  if( $payload !== false ) {
    echo $payload;
    close_div();
  }
}

function close_div() {
  close_tag( 'div' );
}

function open_span( $class = '', $attr = '', $payload = false ) {
  open_tag( 'span', $class, $attr );
  if( $payload !== false ) {
    echo $payload;
    close_span();
  }
}

function close_span() {
  close_tag( 'span' );
}

// open/close_table(), open/close_td/th/tr():
//   these functions will take care of correct nesting, so explicit call of close_td
//   will rarely be needed
//
function open_table( $class = '', $attr = '' ) {
  global $table_level, $table_row_number;
  $table_row_number[ ++$table_level ] = 1;
  open_tag( 'table', $class, $attr );
}

function close_table() {
  global $table_level, $open_tags;
  $table_level--;
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
  global $open_tags, $tr_title, $table_level, $table_row_number;
  $class .= ( ( $table_row_number[ $table_level ]++ % 2 ) ? ' odd' : ' even' );
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'td':
    case 'th':
      close_tag( $open_tags[$n] );
    case 'tr':
      close_tag( 'tr' );
    case 'table':
      open_tag( 'tr', $class, $attr . $tr_title );
      break;
    default:
      error( 'unexpected open_tr' );
  }
  $tr_title = '';
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

function open_tdh( $tag, $class= '', $attr = '', $payload = false ) {
  global $open_tags, $td_title;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'td':
    case 'th':
      close_tag( $open_tags[$n] );
    case 'tr':
      open_tag( $tag, $class . $td_title, $attr );
      break;
    case 'table':
      open_tr();
      open_tag( $tag, $class, $attr . $td_title );
      break;
    default:
      error( "unexpected open_td: innermost open tag: {$open_tags[$n]}" );
  }
  $td_title = '';
  if( $payload !== false ) {
    echo $payload;
    close_td();  // will output either </td> or </th>, whichever is needed!
  }
}

function open_td( $class= '', $attr = '', $payload = false ) {
  open_tdh( 'td', $class, $attr, $payload );
}
function open_th( $class= '', $attr = '', $payload = false ) {
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

function tr_title( $title ) {
  global $tr_title;
  $tr_title = " title='$title' ";
}
function td_title( $title ) {
  global $td_title;
  $td_title = " title='$title' ";
}

function open_ul( $class = '', $attr = '' ) {
  open_tag( 'ul', $class, $attr );
}

function close_ul() {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'li':
      close_tag( 'li' );
    case 'ul':
      close_tag( 'ul' );
      break;
    default:
      error( 'unmatched close_ul' );
  }
}

function open_li( $class = '', $attr = '', $payload = false ) {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'li':
      close_tag( 'li' );
    case 'ul':
      open_tag( 'li', $class, $attr );
      break;
    default:
      error( 'unexpected open_li' );
  }
  if( $payload !== false ) {
    echo $payload;
    close_li();
  }
}

function close_li() {
  global $open_tags;
  $n = count( $open_tags );
  switch( $open_tags[$n] ) {
    case 'li':
      close_tag( 'li' );
      break;
    case 'ul':
      break;  // already closed, never mind...
    default:
      error( 'unmatched close_li' );
  }
}

// open_form(): open a <form method='post'>
//   $get_parameters: determine the form action: target script and query string
//   (target script is window=$window; default is 'self')
//   $post_parameters: will be posted via <input type='hidden'>
// - hidden input fields will be collected and printed just before </form>
//   (so function hidden_input() (see below) can be called at any point)
// - $get/post_parameters can be arrays or strings (see parameters_explode() in inlinks.php!)
//
function open_form( $get_parameters = array(), $post_parameters = array() ) {
  global $form_id, $input_event_handlers, $hidden_input, $self_fields;

  if( is_string( $get_parameters ) )
    $get_parameters = parameters_explode( $get_parameters );
  if( is_string( $post_parameters ) )
    $post_parameters = parameters_explode( $post_parameters );

  $form_id = new_html_id();
  if( isset( $get_parameters['name'] ) ) {
    $name = $get_parameters['name'];
    unset( $get_parameters['name'] );
  } else {
    $name = "form_$form_id";
  }
  // set handler to display SUBMIT and RESET buttons after changes:
  $input_event_handlers = " onChange='on_change($form_id);' ";

  $attr = adefault( $get_parameters, 'attr', '' );

  $window = adefault( $get_parameters, 'window', 'self' );
  $get_parameters['context'] = 'form';
  $action = fc_link( $window, $get_parameters );

  echo
  open_tag( 'form', '', "method='post' $action name='$name' id='form_$form_id' $attr" );
  $hidden_input = '';
  $post_parameters['itan'] = get_itan();
  foreach( $post_parameters as $key => $val )
    hidden_input( $key, $val );
  return $form_id;
}

// hidden_input(): 
// - register parameter $name, value $val to be inserted as a hidden input field
//   just before </form> 
// - thus, this function can be called anywhere in the html structure, not just
//   where <input> is allowed
// - $attr can be used to set e.g. an id='foo' to modify the value from javascript
//
function hidden_input( $name, $val = false, $attr = '' ) {
  global $hidden_input;
  if( $val === false ) {
    global $$name;
    $val = $$name;
  }
  if( $val !== NULL )
    $hidden_input .= "<input type='hidden' name='$name' value='$val' $attr>\n";
}

function close_form() {
  global $input_event_handlers, $form_id;
  $input_event_handlers = '';
  $form_id = '';
  // insert an invisible submit button: this allows to submit this form by pressing ENTER:
  open_span( 'nodisplay', '', "<input type='submit'>" );
  close_tag( 'form' );
  echo "\n";
}

// open_fieldset():
//   $toggle: allow user to display / hide the fieldset; $toggle == 'on' or 'off' determines initial state
//
function open_fieldset( $class = '', $attr = '', $legend = '', $toggle = false ) {
  if( $toggle ) {
    if( $toggle == 'on' ) {
      $buttondisplay = 'none';
      $fieldsetdisplay = 'block';
    } else {
      $buttondisplay = 'inline';
      $fieldsetdisplay = 'none';
    }
    $id = new_html_id();
    open_span( '', "$attr id='button_$id' style='display:$buttondisplay;'" );
      echo "<a class='button' href='javascript:;' onclick=\"document.getElementById('fieldset_$id').style.display='block';
                            document.getElementById('button_$id').style.display='none';\"
            >$legend...</a>";
    close_span();

    open_fieldset( $class, "$attr style='display:$fieldsetdisplay;' id='fieldset_$id'" );
    echo "<legend><img src='img/close_black_trans.gif' alt='Schließen'
            onclick=\"document.getElementById('button_$id').style.display='inline';
                     document.getElementById('fieldset_$id').style.display='none';\">
          $legend</legend>";
  } else {
    open_tag( 'fieldset', $class, $attr );
    if( $legend )
      echo "<legend>$legend</legend>";
  }
}

function close_fieldset() {
  close_tag( 'fieldset' );
}

function open_javascript( $js = '' ) {
  echo "\n";
  open_tag( 'script', '', "type='text/javascript'" );
  echo "\n";
  if( $js ) {
    echo $js ."\n";
    close_javascript();
  }
}

function close_javascript() {
  close_tag('script');
}

function floating_submission_button() {
  global $form_id;

  open_span( 'alert floatingbuttons', "id='floating_submit_button_$form_id'" );
    open_table('layout');
      open_td('alert left');
        ?> <a class='close' title='Schließen' href='javascript:true;'
          onclick='document.getElementById("floating_submit_button_<?php echo $form_id; ?>").style.display = "none"; return false;'></a> <?php
      open_td('alert center quad', '', "&Auml;nderungen sind noch nicht gespeichert!" );
    open_tr();
      open_td( 'alert center oneline smallskip', "colspan='2'" );
        reset_button();
        submission_button();
    close_table();
  close_tag('span');
}

function html_button( $text, $js = '', $attrib = '' ) {
  echo "<a href='javascript:$js' class='button' $attrib title='$text'>$text</a>";
}

function submission_button( $text = '', $active = true, $confirm = '' ) {
  global $form_id;
  $text or $text = 'Speichern';
  $class = ( $active ? 'button' : 'button inactive' );
  // open_span( 'qquad', '', "<a href='javascript:return true;' class='$class' id='submit_button_$form_id' title='$text' onClick=\"submit_form( $form_id );\">$text</a>" );
  if( $confirm )
    $confirm = "if( confirm( '$confirm' ) ) ";
  open_span( 'qquad', '', "<a href=\"javascript:$confirm submit_form( $form_id );\" class='$class' id='submit_button_$form_id' title='$text' >$text</a>" );
}

function reset_button( $text = 'Zur&uuml;cksetzen' ) {
  global $form_id;
  open_span( 'qquad', '', "<a class='button inactive' href='javascript:true;' id='reset_button_$form_id' title='Änderungen zurücknehmen'
                              onClick=\"var form = $('form_$form_id'); form.reset(); form.fire('form:afterReset'); on_reset($form_id); return false;\">$text</a>" );
}

function check_all_button( $text = 'Alle ausw&auml;hlen', $title = '' ) {
  global $form_id;
  $title or $title = $text;
  echo "<a class='button' title='$text' onClick='checkAll($form_id);'>$text</a>";
}
function uncheck_all_button( $text = 'Alle abw&auml;hlen', $title = '' ) {
  global $form_id;
  $title or $title = $text;
  echo "<a class='button' title='$text' onClick='uncheckAll($form_id);'>$text</a>";
}

function close_button( $text = 'Schließen' ) {
  echo "<a class='button' onclick='if(opener) opener.focus(); closeCurrentWindow();'>$text</a>";
}

// open_select(): create <select> element
// $attr supports some magic values:
//  - 'autoreload': on change, reload current window with the new value of $fieldname in the URL
//  - 'autopost': on change, submit the update_form (inserted at end of every page), posting the
//    $fieldname as hidden parameter 'action' and the selected option value as parameters 'message'.
//
function open_select( $fieldname, $attr = '' ) {
  global $input_event_handlers;
  switch( $attr ) {
    case 'autoreload':
      $id = new_html_id();
      $url = fc_link( 'self', array( 'XXX' => 'X', 'context' => 'action', $fieldname => NULL ) );
      $attr = "id='select_$id' onchange=\"
        i = document.getElementById('select_$id').selectedIndex;
        s = document.getElementById('select_$id').options[i].value;
        self.location.href = '$url'.replace( /XXX=X/, '&$fieldname='+s );
      \" ";
      break;
    case 'autopost':
      $id = new_html_id();
      $attr = "id='select_$id' onchange=\"
        i = document.getElementById('select_$id').selectedIndex;
        s = document.getElementById('select_$id').options[i].value;
        post_action( '$fieldname', s );
      \" ";
      break;
  }
  open_tag( 'select', '', "$attr $input_event_handlers name='$fieldname'" );
}

function close_select() {
  close_tag( 'select' );
}

// option_checkbox(): create <input type='checkbox'> element
// when clicked, the current window will be reloaded, with $flag toggled in variable $fieldname in the URL
//
function option_checkbox( $fieldname, $flag, $text, $title = false ) {
  global $$fieldname;
  echo '<input type="checkbox" class="checkbox" onclick="'
         . fc_link('', array( $fieldname => ( $$fieldname ^ $flag ), 'context' => 'js' ) ) .'" ';
  if( $title ) echo " title='$title' ";
  if( $$fieldname & $flag ) echo " checked ";
  echo ">$text";
}

// option_radio(): similar to option_checkbox, but generate a radio button:
// on click, reload current window with all $flags_on set and all $flags_off unset
// in variable $fieldname in the URL
//
function option_radio( $fieldname, $flags_on, $flags_off, $text, $title = false ) {
  global $$fieldname;
  $all_flags = $flags_on | $flags_off;
  $groupname = "{$fieldname}_{$all_flags}";
  echo "<input type='radio' class='radiooption' name='$groupname' onclick=\""
        . fc_link('', array( 'context' => 'js' , $fieldname => ( ( $$fieldname | $flags_on ) & ~ $flags_off ) ) ) .'"';
  if( ( $$fieldname & $all_flags ) == $flags_on ) echo " checked ";
  echo ">$text";
}

// alternatives_radio(): create list of radio buttons to toggle on and of html elements
// (typically: fieldsets, each containing a small form)
// $items is an array:
//  - every key is the id of the element to toggle
//  - every value is either a button label, or a pair of label and title for the button
//
function alternatives_radio( $items ) {
  $id = new_html_id();
  open_ul('plain');
  $keys = array_keys( $items );
  foreach( $items as $item => $value ) {
    open_li();
    $title = '';
    if( is_array( $value ) ) {
      $text = current($value);
      $title = "title='".next($value)."'";
    } else {
      $text = $value;
    }
    echo "<input type='radio' class='radiooption' name='radio_$id' $title onclick=\"";
    foreach( $keys as $key )
      echo "document.getElementById('$key').style.display='". ( $key == $item ? 'block' : 'none' ) ."'; ";
    echo "\">$text";
  }
  close_ul();
}

function close_all_tags() {
  global $open_tags, $print_on_exit, $js_on_exit, $html_hints;
  while( $n = count( $open_tags ) ) {
    if( $open_tags[$n] == 'body' ) {
      foreach( $print_on_exit as $p )
        echo "\n" . $p;
      if( $js_on_exit ) {
        open_javascript();
        foreach( $js_on_exit as $js )
          echo "\n" . $js;
        echo "\n";
        close_javascript();
      }
    }
    close_tag( $open_tags[$n] );
  }
}

// close all open html tags even in case of early error exit:
//
register_shutdown_function( 'close_all_tags' );

function div_msg( $class, $msg, $backlink = false ) {
  echo "<div class='$class'>$msg " . ( $backlink ? fc_link( $backlink, 'text=zur&uuml;ck...' ) : '' ) ."</div>";
}

function open_hints() {
  global $html_hints;
  $n = count( $html_hints );
  $html_hints[++$n] = new_html_id();
}
function close_hints( $class = 'kommentar', $initial = '' ) {
  global  $html_hints;
  $n = count( $html_hints );
  $id = $html_hints[$n];
  open_div( $class, "id='hints_$id'", $initial );
  unset( $html_hints[$n--] );
}

function html_hint( $hint ) {
  global $html_hints;
  $n = count( $html_hints );
  $id = $html_hints[$n];
  return " onmouseover=\" document.getElementById('hints_$id').firstChild.nodeValue = '$hint'; \" "
        . " onmouseout=\" document.getElementById('hints_$id').firstChild.nodeValue = ' '; \" ";
}


// the following are kludges to replace the missing <spacer> (equivalent of \kern) element:
//
function smallskip() {
  open_div('smallskip', '', '' );
}
function medskip() {
  open_div('medskip', '', '' );
}
function bigskip() {
  open_div('bigskip', '', '' );
}
function quad() {
  open_span('quad', '', '' );
}
function qquad() {
  open_span('qquad', '', '' );
}


// option_menu_row():
// create row in a small dummy table;
// at the end of the document, javascript code will be inserted to move this row into
// a table with id='option_menu_table'
// $payload must contain one or more complete columns (ie <td>...</td> elements)
//
function open_option_menu_row( $payload = false ) {
  global $option_menu_counter;
  $option_menu_counter = new_html_id();
  open_table();
  open_tr( '', "id='option_entry_$option_menu_counter'" );
  if( $payload ) {
    echo $payload;
    close_option_menu_row();
  }
}

function close_option_menu_row() {
  global $option_menu_counter, $js_on_exit;
  close_table();
  $js_on_exit[] = move_html( 'option_entry_' . $option_menu_counter, 'option_menu_table' );
}

/**
 * Generate event handler attributes for handling changes and capturing ENTER key
 * 
 * @author Tilman Vogel
 * 
 * @param[in]   handler
 *              JS code to execute on field change or ENTER
 * @param[in]   capture_enter
 *              whether to capture ENTER key press
 * @returns     corresponding "onchange" and "onkeypress" attributes
 */
function textfield_on_change_handler( $handler, $capture_enter = true ) {
  $result = " onchange='$handler'";
  if ($capture_enter) {
    $result .= " onkeypress='handleTextFieldKeyPress(event, function() { $handler });'";
  }
  return $result;
}

/**
 * Send a PHP array as a JavaScript object via JSON
 * 
 * @author Tilman Vogel
 * 
 * @param[in]   name
 *              name of the JavaScript variable, must contain "var " if desired
 * @param[in]   value
 *              the PHP array to send
 * @returns     the correspoding JS code
 */
function toJavaScript( $name, $value ) {
  return "$name = ".json_encode($value).";";
}

?>
