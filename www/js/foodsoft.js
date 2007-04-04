
//  das  javascript der foodsoft  
// copyright Fc Schinke09 2006 

function deleteGroup(groupID)
			{
 	       if (confirm('Soll die Gruppe wirklich GELÖSCHT werden?')) { 
				    document.forms['reload_form'].action.value="delete";
						document.forms['reload_form'].gruppen_id.value=groupID;
						document.forms['reload_form'].submit();
				 }
			}
			
	    function deleteLieferant(lieferantID)
			{
 	       if (confirm('Soll der Lieferant wirklich GELÖSCHT werden?')) { 
				    document.forms['reload_form'].action.value="delete";
						document.forms['reload_form'].lieferanten_id.value=lieferantID;
						document.forms['reload_form'].submit();
				 }
			}			
			
	    function deleteProdukt(produktID)
			{
 	       if (confirm('Soll das Produkt wirklich GELÖSCHT werden?')) { 
				    document.forms['reload_form'].action.value="delete";
						document.forms['reload_form'].produkt_id.value=produktID;
						document.forms['reload_form'].submit();
				 }
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