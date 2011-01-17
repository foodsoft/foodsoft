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

function handleTextFieldKeyPress(event, onEnter) {
    if (event.keyCode != Event.KEY_RETURN) {
      return;
    }
    event.stop(); // no submit
    onEnter();
    event.findElement().select();
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


var MagicCalculator = Class.create(
{
  initialize: function(orderId, productId, distMult, endPrice) 
  {
    this.mOrderId = orderId;
    this.mProductId = productId;
    this.mDistMult = distMult;
    this.mEndPrice = endPrice;
    this.mGroupFields = new Array();
    this.mGroupValues = new Array();
    this.mResultGroupValues = new Array();
    this.mTrashField = '';
    this.mTrashValue = 0;
    this.mBazaarField = '';
    this.mBazaarValue = 0;
    this.mBazaarTarget = 0;
    this.mTotal = 0;
    this.mUiEnabled = false;
    
  },
  addGroupField: function(id) 
  {
    this.mGroupFields.push(id);
  },
  setTrashField: function(id)
  {
    this.mTrashField = id;
  },
  setBazaarField: function(id)
  {
    this.mBazaarField = id;
  },
  fetchValues: function()
  {
    this.mTotal = parseInt($('liefermenge_' + this.mOrderId + '_' + this.mProductId).value, 10);
    this.mGroupValues.length = this.mGroupFields.length;
    for (var i = 0; i < this.mGroupFields.length; ++i)
    {
      this.mGroupValues[i] = parseInt($('menge_' + this.mGroupFields[i]).value, 10);
    }
    this.mResultGroupValues = this.mGroupValues;
    this.mTrashValue = parseInt($('menge_' + this.mTrashField).value, 10);
    this.mBazaarValue = parseInt($('menge_' + this.mBazaarField).textContent, 10);
    this.mBazaarTarget = parseInt($('magic_' + this.mBazaarField).value, 10);
  },
  recalcCurrentBazaar: function() {
    this.mBazaarValue = this.mTotal;
    for (var i = 0; i < this.mGroupValues.length; ++i)
    {
      this.mBazaarValue -= this.mGroupValues[i];
    }
    this.mBazaarValue -= this.mTrashValue;
  },
  publishCurrentBazaar: function() {
    $('menge_' + this.mBazaarField).textContent = this.mBazaarValue;
  },
  calculate: function()
  {
    if (isNaN(this.mBazaarTarget)) {
      return;
    }
    var groupsSum = 0;
    this.mGroupValues.each(function(x) { groupsSum += x });
    var groupsTarget = this.mTotal - this.mBazaarTarget - this.mTrashValue;
    var ratio = groupsTarget / groupsSum;
    groupsSum = 0;
    var groupValuesExact = this.mGroupValues.collect(function(x) {
      return x * ratio;
    });
    this.mResultGroupValues = groupValuesExact.collect(function(x) { 
      var newX = Math.round(x); 
      groupsSum += newX;
      return newX; 
    });
    
    this.mBazaarValue = this.mTotal - this.mTrashValue - groupsSum;
    
    // rounding fix-up: make array with same length initialized to zero
    var roundingDistribution = this.mGroupValues.collect(function(x) { return 0; });
    while (this.mBazaarValue != this.mBazaarTarget) {
      // bazaar rest from rounding
      // direction +1: need to distribute more to groups
      var direction = (this.mBazaarValue - this.mBazaarTarget > 0) ? 1 : -1;
      var minBadness;
      var iMinBadness = 0;
      for (var i = 0; i < this.mGroupValues.length; ++i) {
        if (this.mGroupValues[i] == 0) {
          // do not involve new groups
          continue;
        }
        var badness = Math.abs((this.mResultGroupValues[i] + roundingDistribution[i] + direction - groupValuesExact[i]) / this.mResultGroupValues[i]);
        if (i == 0) {
          minBadness = badness;
          continue;
        }
        if (badness < minBadness) {
          iMinBadness = i;
          minBadness = badness;
        }
      }
      roundingDistribution[iMinBadness] += direction;
      this.mBazaarValue -= direction;
    }
    
    for (var i = 0; i < this.mGroupValues.length; ++i) {
      this.mResultGroupValues[i] += roundingDistribution[i];
    }
  },
  setUi: function(enabled) {
    $('magic_' + this.mOrderId + '_' + this.mProductId + '_style').sheet.cssRules[0].style.display = enabled ? '' : 'none';
    this.mUiEnabled = enabled;
  },
  displayResult: function() {
    for (var i = 0; i < this.mGroupFields.length; ++i) {
      $('magic_' + this.mGroupFields[i]).textContent = this.mResultGroupValues[i];
    }
    $('magic_' + this.mTrashField).textContent = this.mTrashValue;
  },
  applyResult: function() {
    this.setUi(false);
    for (var i = 0; i < this.mGroupFields.length; ++i) {
      $('menge_' + this.mGroupFields[i]).value = this.mResultGroupValues[i];
    }
    this.handleChangedDistribution();
  },
  initUi: function() {
    this.fetchValues();
    this.recalcCurrentBazaar();
    this.publishCurrentBazaar();
    this.mBazaarTarget = this.mBazaarValue;
    $('magic_' + this.mBazaarField).value = this.mBazaarTarget;
    this.calculate();
    this.displayResult();
    this.setUi(true);
  },
  updateUi: function() {
    this.fetchValues();
    this.calculate();
    this.displayResult();
  },
  calcPrice: function(amount) {
    return this.mEndPrice * amount / this.mDistMult;
  },
  formatPrice: function(price) {
    return price.toFixed(2);
  },
  recalcAndShowPrices: function() {
    $('preis_' + this.mOrderId + '_' + this.mProductId).textContent = this.formatPrice(this.calcPrice(this.mTotal));
    for (var i = 0; i < this.mGroupFields.length; ++i) {
      $('preis_' + this.mGroupFields[i]).textContent = this.formatPrice(this.calcPrice(this.mGroupValues[i]));
    }
    $('preis_' + this.mTrashField).textContent = this.formatPrice(this.calcPrice(this.mTrashValue));
    $('preis_' + this.mBazaarField).textContent = this.formatPrice(this.calcPrice(this.mBazaarValue));
  },
  handleChangedDistribution: function() {
    this.fetchValues();
    this.recalcCurrentBazaar();
    this.publishCurrentBazaar();
    this.recalcAndShowPrices();
    if (this.mUiEnabled) {
      this.calculate();
      this.displayResult();
    }
  },
});

