//  das  javascript der foodsoft  
// copyright Fc Schinke09 2006 



function checkAll( form_id ) {
  var o = document.forms[ 'form_'+form_id ].elements;
  if (o) {
    for (i=0; i<o.length; i++) {
      if (o[i].type == 'checkbox')
        o[i].checked = 1;
    }
  }	
  on_change( form_id );
  // if( s = document.getElementById('checkall_'+form_id) )
  //   s.className = 'button inactive';
  // if( s = document.getElementById('uncheckall_'+form_id) )
  //   s.className = 'button';
}

function uncheckAll( form_id ) {
  var o = document.forms[ 'form_'+form_id ].elements;
  if (o){
    for (i=0; i<o.length; i++) {
      if (o[i].type == 'checkbox')
        o[i].checked = 0;
    }
  }	
  on_change( form_id );
  // if( s = document.getElementById('uncheckall_'+form_id) )
  //   s.className = 'button inactive';
  // if( s = document.getElementById('checkall_'+form_id) )
  //   s.className = 'button';
}

// neuesfenster: neues (grosses) Fenster oeffnen (fuer wiki)
//
function neuesfenster(url,name) {
  f=window.open(url,name,"dependent=yes,toolbar=yes,menubar=yes,location=yes,resizable=yes,scrollbars=yes");
  f.focus();
}

function drop_col(self,spalten) {
  i = document.getElementById('select_drop_cols').selectedIndex;
  s = document.getElementById('select_drop_cols').options[i].value;
  window.location.href = self + '&spalten=' + ( spalten - parseInt(s) );
}
function insert_col(self,spalten) {
  i = document.getElementById('select_insert_cols').selectedIndex;
  s = document.getElementById('select_insert_cols').options[i].value;
  window.location.href = self + '&spalten=' + ( spalten + parseInt(s) );
}

function closeCurrentWindow() {
  // this function is a workaround for the spurious " 'window.close()' is not a function" -bug
  // (occurring in some uses of onClick='window.close();'; strangely, the following works:):
  window.close();
}

function on_change( id ) {
  if( id ) {
    if( s = document.getElementById( 'submit_button_'+id ) )
      s.className = 'button';
    if( s = document.getElementById( 'reset_button_'+id ) )
      s.className = 'button';
    if( s = document.getElementById( 'floating_submit_button_'+id ) )
      s.style.display = 'inline';
  }
}

function on_reset( id ) {
  if( id ) {
    if( s = document.getElementById( 'submit_button_'+id ) )
      s.className = 'button inactive';
    if( s = document.getElementById( 'reset_button_'+id ) )
      s.className = 'button inactive';
    if( s = document.getElementById( 'floating_submit_button_'+id ) )
      s.style.display = 'none';
  }
}

function submit_form( form_id ) {
  f = document.getElementById( 'form_'+form_id );
  // calling f.submit() explicitely will not trigger the onsubmit() handler, so we call it explicitely:
  if( f.onsubmit )
    f.onsubmit();
  f.submit();
}

function post_action( action, message ) {
  f = document.forms['update_form'];
  f.action.value = action;
  f.message.value = message;
  if( f.onsubmit )
    f.onsubmit();
  f.submit();
}

function set_footbar( percentage ) {
  var main = document.getElementById( 'main' );
  var footbar = document.getElementById( 'footbar' );
  main.style.height=(100-percentage)+"%";
  footbar.style.height=percentage+"%";
  if (percentage)
  {
    footbar.style.display="block";
  }
  else
  {
    footbar.style.display="none";
  }
}

function set_class( node, className, enabled ) {
  if (enabled) {
    if (node.className.match(RegExp('\\b'+className+'\\b')))
      return;
    node.className += ' ' + className;
    return;
  }
  // removal
  node.className = node.className.replace(RegExp(' *\\b'+className+'\\b *'), ' ');
}

// experimenteller code - funktioniert noch nicht richtig...
// 
// var child_windows = new Array();
// var child_counter = 0;
// 
// function window_open( url, name, options, focus ) {
//   var w, i;
//   w = window.open( url, name, options );
//   if( focus )
//     w.focus();
//   for( i = 0; i < child_counter; i++ ) {
//     if( child_windows[i].name == name )
//       return w;
//   }
//   child_windows[ child_counter++ ] = w;
//   return w;
// }
//   
// 
// function notify_down() {
//   var m;
//   m = document.forms['update_form'].message.value;
//   for( i = 0; i < child_counter; i++ ) {
//     child_windows[i].document.forms['update_form'].message.value = m;
//     if( confirm( 'down to: ' + i + ' ' + child_windows[i].name ) )
//       child_windows[i].notify_down();
//   }
// }
// 
// function notify_up() {
//   var m;
//   m = document.forms['update_form'].message.value;
//   if( opener && ( opener != window ) && opener.document.forms ) {
//     opener.document.forms['update_form'].message.value = m;
//     if( confirm( 'weitermachen: ' + opener.name ) )
//       opener.notify_up();
//   } else {
//     alert( 'top reached: passing message down...' );
//     notify_down();
//   }
// }
