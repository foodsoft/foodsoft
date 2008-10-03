
//  das  javascript der foodsoft  
// copyright Fc Schinke09 2006 


function deleteProduktpreis(id)
{
   document.forms['reload_form'].action.value="delete_price";
   document.forms['reload_form'].preis_id.value=id;
   document.forms['reload_form'].submit();
}	

function checkAll(thisForm,elmnt,tf,x) {
  var o = document.forms[thisForm].elements
  if (o){
    for (i=0; i<o.length; i++){
      if (elmnt != ''){
        if ((o[i].type == 'checkbox')&&(o[i].name.indexOf(elmnt+"") != -1)){
          o[i].checked = tf
        }
      }
      else {
        if (o[i].type == 'checkbox'){
          o[i].checked = tf
        }
      }			
    }
  }	
  for (var j = 0; j < document.links.length; j++){
    if ((document.links[j].href.indexOf(thisForm) != -1) && (document.links[j].href.indexOf('checkAll') != -1)){
      if (tf == true){
        document.links[j].href = "javascript:checkAll('"+thisForm+"','"+elmnt+"',false)";
        //document.links[j].innerText = "- all";
      }
      else {
        document.links[j].href = "javascript:checkAll('"+thisForm+"','"+elmnt+"',true)";
        //document.links[j].innerText = "+ all";
      }
    }
  }
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
function select_group(self) {
  i = document.getElementById('select_group').selectedIndex;
  s = document.getElementById('select_group').options[i].value;
  window.location.href = self + '&gruppen_id=' + s;
}
function select_lieferant(self) {
  i = document.getElementById('select_lieferant').selectedIndex;
  s = document.getElementById('select_lieferant').options[i].value;
  window.location.href = self + '&lieferanten_id=' + s;
}
function select_auszug(self) {
  i = document.getElementById('select_auszug').selectedIndex;
  s = document.getElementById('select_auszug').options[i].value;
  window.location.href = self + '&auszug=' + s;
}

function closeCurrentWindow() {
  // this function is a workaround for the spurious " 'window.close()' is not a function" -bug
  // (occurring in some uses of onClick='window.close();'; strangely, the following works:):
  window.close();
}

// submit_form: erlaubt POST an script in anderem fenster:
//  - $form ist name eines <form> im aktuellen skript, action="neues skript"
//  - $window ist ein fenstename
//  - $button_id ist eine optionale POST variable, um den gedrueckten submit-knopf zu identifizieren
function submit_form( form, window_id, optionen, button_id ) {
  window.open( '', window_id, optionen ).focus();
  document.forms[form].target = window_id;
  if( button_id != '' )
    if( document.forms[form].button_id )
      document.forms[form].button_id.value = button_id;
  if( document.forms[form] )
    document.forms[form].submit();
  else
    alert( 'no such form: ' + form + ' button_id: ' + button_id );
}


function on_change( $id ) {
  if( $id ) {
    if( s = document.getElementById( 'submit_button_'+$id ) )
      s.className = 'button';
    if( s = document.getElementById( 'floating_submit_button_'+$id ) )
      s.style.display = 'inline';
  }
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

