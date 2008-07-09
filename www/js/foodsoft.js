
//  das  javascript der foodsoft  
// copyright Fc Schinke09 2006 


function deleteProdukt(produktID)
{
    if (confirm('Soll das Produkt wirklich GELÖSCHT werden?')) { 
      document.forms['reload_form'].action.value="delete";
      document.forms['reload_form'].produkt_id.value=produktID;
      document.forms['reload_form'].submit();
   }
}	
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
  // this function is a workaround for the " 'window.close()' is not a function" -bug
  // (occurring in some uses of onClick='window.close();'):
  window.close();
}

