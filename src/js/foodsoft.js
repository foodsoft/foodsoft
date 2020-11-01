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

function set_footbar( enabled ) {
  var footbar = document.getElementById( 'footbar' );
  if (enabled)
  {
    footbar.style.display="block";
  }
  else
  {
    footbar.style.display="none";
  }
  updateWindowHeight();
}

function updateWindowHeight() {
  var spaceForScrollbar = 16;
  var overlap = 0.05;
  var footbar = $('footbar');
  var footbarHeight = footbar.offsetHeight;
  var windowHeight = document.viewport.getHeight();
  
  scroller.setPageHeight((1-overlap) * (windowHeight - footbarHeight - spaceForScrollbar));
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
    event.findElement().select();
    onEnter(true);
}

function installTextFieldChangeHandler(element, handler, captureEnter) {
  captureEnter = typeof captureEnter === 'undefined' ? true : captureEnter;
  element.on('change', function() {handler(false);} );
  if (captureEnter)
    element.on(
        'keypress', 
        function(event) {handleTextFieldKeyPress(event, handler);});
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


var Scroller = Class.create({
  initialize: function() {
    this.mPageHeight = window.innerHeight;
    this.mKeyState = Scroller.UP;
    this.mKeyCode = 0;
    // generate closures
    var self = this;
    this.mKeyPressHandler = function(event) {self.handleKey(event, Scroller.PRESS);};
    this.mKeyDownHandler = function(event) {self.handleKey(event, Scroller.DOWN);};
    this.mKeyUpHandler = function(event) {self.handleKey(event, Scroller.UP);};
  },
  setPageHeight: function(pageHeight) {
    this.mPageHeight = pageHeight;
  },
  scrollPage: function(direction) {
    window.scrollBy(0, direction * this.mPageHeight);
  },
  handleKey: function(event, what) {
    // capture only page up / down
    if (event.keyCode !== Event.KEY_PAGEUP 
        && event.keyCode !== Event.KEY_PAGEDOWN
        || event.altKey || event.ctrlKey || event.shiftKey) {
      return;
    }
    
    // check target, only want top-level scrolls
    if (this.isInNestedScrollview(event.target)) {
      return;
    }

    event.stop();
    
    if (this.mKeyCode === event.keyCode 
        && this.mKeyState === Scroller.DOWN
        && what === Scroller.PRESS) {
      // discard first press after down:
      // firefox fires: "down, press, up" on single press, "down, press, press, ... , up" on auto-repeat
      // webkit fires: "down" on single press, "down, down, ..." on auto-repeat
      this.mKeyState = what;
      return;
    }
    
    this.mKeyState = what;
    this.mKeyCode = event.keyCode;
    
    if (what === Scroller.UP) {
      return;
    }

    this.scrollPage(event.keyCode === Event.KEY_PAGEUP ? -1 : 1);
  },
  register: function(element) {
    if (element === null) {
      element = document;
    }
    Event.observe(element, 'keypress', this.mKeyPressHandler);
    Event.observe(element, 'keydown', this.mKeyDownHandler);
    Event.observe(element, 'keyup', this.mKeyUpHandler);
  },
  unregister: function(element) {
    if (element === null) {
      element = document;
    }
    Event.stopObserving(element, 'keypress', this.mKeyPressHandler);
    Event.stopObserving(element, 'keydown', this.mKeyDownHandler);
    Event.stopObserving(element, 'keyup', this.mKeyUpHandler);
  },
  isInNestedScrollview: function(node) {
    switch(node) {
      case null:
      case document.documentElement: // firefox
      case document.body: // webkit
        // top-level element: not nested
        return false;
    }
    if (node.nodeType === Node.ELEMENT_NODE) {
      if (node.nodeName === "FORM") { // have bogus sizes on IE
        return this.isInNestedScrollview(node.parentNode);
      }
      if (node.scrollHeight > node.offsetHeight) {
        return true;
      }
    }
    return this.isInNestedScrollview(node.parentNode);
  }
});

Scroller.UP = 0;
Scroller.DOWN = 1;
Scroller.PRESS = 2;

window.scroller = new Scroller();

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
    this.mNotInteger = false;
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
  parseValue: function(string) {
    var resultInt = parseInt(string, 10);
    var resultFloat = parseFloat(string);
    if (isNaN(resultInt) || resultInt !== resultFloat || string.indexOf('.') >= 0) {
      this.mNotInteger = true;
      return Math.round(resultFloat*1000)/1000;
    }
    return resultInt;
  }, 
  fetchValues: function()
  {
    this.mNotInteger = false;
    this.mTotal = this.parseValue($('liefermenge_' + this.mOrderId + '_' + this.mProductId).value);
    this.mGroupValues.length = this.mGroupFields.length;
    for (var i = 0; i < this.mGroupFields.length; ++i)
    {
      this.mGroupValues[i] = this.parseValue($('menge_' + this.mGroupFields[i]).value);
    }
    this.mResultGroupValues = this.mGroupValues;
    this.mTrashValue = this.parseValue($('menge_' + this.mTrashField).value);
    this.mBazaarValue = parseFloat($('menge_' + this.mBazaarField).textContent);
    this.mBazaarTarget = this.parseValue($('magic_' + this.mBazaarField).value);
  },
  recalcCurrentBazaar: function() {
    this.mBazaarValue = this.mTotal;
    for (var i = 0; i < this.mGroupValues.length; ++i)
    {
      this.mBazaarValue -= this.mGroupValues[i];
    }
    this.mBazaarValue -= this.mTrashValue;
  },
  formatNumber: function(number, precision) {
    var string = number.toFixed(precision);
    return string.replace(/\.?0+$/, '');
  },
  publishCurrentBazaar: function() {
    $('menge_' + this.mBazaarField).textContent = this.formatNumber(this.mBazaarValue, 3);
  },
  calculate: function()
  {
    if (isNaN(this.mBazaarTarget)) {
      return;
    }
    
    var fixPointFactor = (this.mNotInteger) ? 1000 : 1;
    
    var groupsSum = 0;
    this.mGroupValues.each(function(x) {groupsSum += x});
    var groupsTarget = this.mTotal - this.mBazaarTarget - this.mTrashValue;
    var ratio = groupsTarget / groupsSum;
    groupsSum = 0;
    var groupValuesExact = this.mGroupValues.collect(function(x) {
      return x * ratio;
    });
    this.mResultGroupValues = groupValuesExact.collect(function(x) { 
      var newX = Math.round(x*fixPointFactor) / fixPointFactor; 
      groupsSum += newX;
      return newX; 
    });
    
    
    // in case of decimals, do the rounding on 1e-3, scale up, do it in integer, then scale down
    this.mBazaarValue = Math.round((this.mTotal - this.mTrashValue - groupsSum) * fixPointFactor);
    this.mBazaarTarget = Math.round(this.mBazaarTarget * fixPointFactor);
    // rounding fix-up: make array with same length initialized to zero
    var roundingDistribution = this.mGroupValues.collect(function(x) {return 0;});
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
        var badness = Math.abs(
            (this.mResultGroupValues[i] + (roundingDistribution[i] + direction)/fixPointFactor - groupValuesExact[i]) 
                / groupValuesExact[i]);
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
      this.mResultGroupValues[i] += roundingDistribution[i] / fixPointFactor;
    }
    this.mBazaarTarget /= fixPointFactor;
    this.mBazaarValue /= fixPointFactor;
  },
  setUi: function(enabled) {
    $('magic_' + this.mOrderId + '_' + this.mProductId + '_style').sheet.cssRules[0].style.display = enabled ? '' : 'none';
    this.mUiEnabled = enabled;
  },
  displayResult: function() {
    for (var i = 0; i < this.mGroupFields.length; ++i) {
      $('magic_' + this.mGroupFields[i]).textContent = this.formatNumber(this.mResultGroupValues[i], 3);
    }
    $('magic_' + this.mTrashField).textContent = this.formatNumber(this.mTrashValue, 3);
  },
  applyResult: function() {
    this.setUi(false);
    for (var i = 0; i < this.mGroupFields.length; ++i) {
      $('menge_' + this.mGroupFields[i]).value = this.formatNumber(this.mResultGroupValues[i], 3);
    }
    this.handleChangedDistribution();
  },
  initUi: function() {
    this.fetchValues();
    this.recalcCurrentBazaar();
    this.publishCurrentBazaar();
    this.mBazaarTarget = this.mBazaarValue;
    $('magic_' + this.mBazaarField).value = this.formatNumber(this.mBazaarTarget, 3);
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
  }
});

function bound(min, x, max) {
  x = x < min ? min : x;
  x = x > max ? max : x;
  return x;
}

var SearchableSelect = Class.create({
  initialize: function(selectElement, searchInput) {
    var self = this;
    this.mSelectElement = selectElement;
    this.mSearchInput = searchInput;
    this.mListEntries = [];
    this.mVisibleEntries = [];
    this.mCaseSensitive = false;
    
    installTextFieldChangeHandler(
        this.mSearchInput, 
        function() {self.filterList();});
    this.mSelectElement.on('change', function() {self.emitSelection();});
  },
  updateText: function(entry) {
    entry.data.setOption(entry.option);
    entry.option.memo = entry.data;
    if (this.mCaseSensitive)
      entry.searchText = entry.option.text;
    else
      entry.searchText = entry.option.text.toLowerCase();
  },
  setEntries: function(entries) {
    var self = this;
    this.mListEntries = entries.collect(function(entry) {
      return { 
        data: entry,
        option: document.createElement('option')
      };
    });
    this.mListEntries.each(function(entry) {
      self.updateText(entry);
    });
    this.mVisibleEntries = this.mListEntries.clone();
    this.updateSelectElement();
  },
  appendEntry: function(entry) {
    var newListEntry = {
      data: entry,
      option: document.createElement('option')
    };
    this.mListEntries.push(newListEntry);
    this.updateText(newListEntry);
    this.filterList();
  },
  remove: function(entry) {
    var listEntry = this.mListEntries.detect(function(it) {
      return it.data === entry;
    });
    this.mListEntries = this.mListEntries.without(listEntry);
    this.mVisibleEntries = this.mVisibleEntries.without(listEntry);
    var i = this.mSelectElement.selectedIndex;
    this.updateSelectElement();
    i = bound(0, i, this.mSelectElement.options.length - 1);
    this.selectIndex(i);
  },
  selectIndex: function(index) {
    if (index === this.mSelectElement.selectedIndex)
      return;
    this.mSelectElement.selectedIndex = index;
    this.emitSelection();
  },
  select: function(entry) {
    var self = this;
    var found = false;
    this.mVisibleEntries.each(function (listEntry, index) {
      if (listEntry.data === entry) {
        self.selectIndex(index);
        found = true;
        throw $break;
      }
    });
    if (!found) {
      // remove filter
      if (this.mSearchInput.value !== '') {
        this.mSearchInput.value = '';
        this.filterList();
        this.select(entry);
      }
      /*
      // force append to visible list
      this.mListEntries.each(function (listEntry) {
        if (listEntry.data === entry) {
          self.mVisibleEntries.push(listEntry);
          self.updateSelectElement();
          self.select(entry);
          found = true;
          throw $break;
        }
      });
      */
    }
  },
  moveSelection: function(delta) {
    var oldIndex = this.mSelectElement.selectedIndex;
    var newIndex = bound(
        0,
        oldIndex + delta,
        this.mSelectElement.options.length - 1);
    if (newIndex !== oldIndex) {
      this.mSelectElement.selectedIndex = newIndex;
      this.emitSelection();
      return true;
    }
    return false;
  },
  updateEntry: function(entry) {
    var listEntry = this.mListEntries.detect(function (x) {
      return x.data === entry;
    })
    this.updateText(listEntry);
  },
  filterList: function() {
    var searchText = this.mSearchInput.value;
    if (!this.mCaseSensitive)
      searchText = searchText.toLowerCase();
    this.mVisibleEntries = this.mListEntries.findAll(function(entry) {
      return ! (searchText.length && entry.searchText.indexOf(searchText) < 0);
    });
    this.updateSelectElement();
  },
  updateSelectElement: function() {
    var self = this;
    var currentMemo = this.currentMemo();
    this.mSelectElement.innerHTML = '';
    this.mVisibleEntries.each(function(entry) {
      self.mSelectElement.appendChild(entry.option);
      if (currentMemo === entry.option.memo) {
        self.mSelectElement.selectedIndex 
            = self.mSelectElement.options.length - 1;
        self.emitSelection();
      }
    });
  },
  currentMemo: function() {
    var selectedIndex = this.mSelectElement.selectedIndex;
    return selectedIndex < 0 
        ? null 
        : this.mSelectElement.options[selectedIndex].memo;
  },
  emitSelection: function() {
    this.mSelectElement.fire('option:selected', this.currentMemo());
  }
});

function disableAutocomplete(element) {
  element.setAttribute('autocomplete', 'off');
}
