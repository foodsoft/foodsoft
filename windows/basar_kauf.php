<?php
error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();  // aufruf nur per index.php?window=basar...
nur_fuer_dienst(0);

global $foodsoftdir;

get_http_var( 'action','w','' );

if( $action === 'basarzuteilung' ) {
  header( 'Content-Type: application/json' );

  need_ajax_http_var( "produkt", 'U' );
  need_ajax_http_var( "bestellung", 'U' );
  need_ajax( sql_bestellung_status( $bestellung ) < STATUS_ABGERECHNET, 'Bestellung schon abgerechnet!', 400 );
  need_ajax_http_var( "menge", "f" );
  $pr = sql_produkt( array( 'bestell_id' => $bestellung, 'produkt_id' => $produkt ) );
  $gruppen_menge = $menge / $pr['kan_verteilmult'];
  need_ajax( $gruppen_menge > 0, 'Menge muss positiv sein!', 400 );
  sql_basar2group( $login_gruppen_id, $produkt, $bestellung, $gruppen_menge );

  $response = [
    'gruppen_id' => $login_gruppen_id
  , 'produkt_id' => $produkt
  , 'bestell_id' => $bestellung
  , 'menge' => $gruppen_menge * $pr['kan_verteilmult']
  , 'success' => true
  , 'itan' => $_POST['itan']
  , 'next_itan' => get_itan()
  ];

  echo json_encode($response);
  exit(0);
}
if( $action === 'inventur' ) {
  header( 'Content-Type: application/json' );

  need_ajax_http_var( "produkt", 'U' );
  need_ajax_http_var( "bestellung", 'U' );
  need_ajax( sql_bestellung_status( $bestellung ) < STATUS_ABGERECHNET, 'Bestellung schon abgerechnet!', 400 );
  need_ajax_http_var( "menge", "f" );
  $pr = sql_produkt( array( 'bestell_id' => $bestellung, 'produkt_id' => $produkt ) );
  $rest_menge = $menge / $pr['kan_verteilmult'];
  need_ajax( $rest_menge >= 0, 'Menge darf nicht negativ sein!', 400 );
  sql_basarinventur( $login_gruppen_id, $produkt, $bestellung, $rest_menge );

  $response = [
    'gruppen_id' => $login_gruppen_id
  , 'produkt_id' => $produkt
  , 'bestell_id' => $bestellung
  , 'menge' => $rest_menge * $pr['kan_verteilmult']
  , 'success' => true
  , 'itan' => $_POST['itan']
  , 'next_itan' => get_itan()
  ];

  echo json_encode($response);
  exit(0);
}

if( $action === 'nop' )
  $action = '';

need( $action === '', "Unbekannte Aktion $action !" );

?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"></link>
<script type="text/javascript" src='<?php echo $foodsoftdir; ?>/js/lib/quagga.min.js'></script>
<?php

$ajax_url = preg_replace( '/&amp;/', '&', fc_link('self', ['download' => 'basar_kauf', 'context' => 'action'] ) );
open_javascript( toJavaScript( 'var ajax', [ 'url' => $ajax_url, 'itan' => get_itan() ] ) );

$verfuegbar_nach_ean = [];

foreach( sql_basar() as $produkt ) {
  $ean = $produkt['ean_einzeln'];
  if( !$ean )
    continue;
  $produkt_daten = [
    'produkt_name' => html_entity_decode( $produkt['produkt_name'], ENT_QUOTES, 'UTF-8' )
  , 'basarmenge' => $produkt['basarmenge']
  , 'verteileinheit' => $produkt['kan_verteileinheit']
  , 'verteilmult' => $produkt['kan_verteilmult']
  , 'endpreis' => $produkt['endpreis']
  , 'bestell_id' => $produkt['gesamtbestellung_id']
  , 'bestellung' => $produkt['bestellung_name']
  , 'lieferdatum' => $produkt['lieferung']
  , 'produkt_id' => $produkt['produkt_id']
  ];
  $verfuegbar_nach_ean[$ean][] = $produkt_daten or $verfuegbar_nach_ean[$ean] = [ $produkt_daten ];
}
open_javascript( toJavaScript( 'var availableByEan', $verfuegbar_nach_ean ) );

$prevent_submit = textfield_on_change_handler('');
open_div( '', 'id="top"', '' );
open_div( 'tab', 'id="scan-product"' );
  open_div( 'scanner', 'id="scanner-viewport"' );
    open_tag( 'video', '', '', '' );
  close_div();
close_div();
open_div( 'tab', 'id="pick-delivery"' );
  open_tag( 'h1', '', 'id="pick-delivery-produkt_name"', '');
  open_tag( 'p', '', '', 'Bitte Lieferung wählen:');
  open_div( '', 'id="delivery-list"', '');
close_div();
open_form();
hidden_input( 'ean', '', 'id="ean"' );
hidden_input( 'produkt_id', '', 'id="produkt_id"' );
hidden_input( 'bestell_index', '', 'id="bestell_index"' );
hidden_input( 'bestell_id', '', 'id="bestell_id"' );
hidden_input( 'verteilmult', '1', 'id="verteilmult"' );
hidden_input( 'basarmenge', '', 'id="basarmenge"' );
hidden_input( 'endpreis', '', 'id="endpreis"' );
open_div( 'tab', 'id="enter-amount"' );
  open_tag( 'h1', '', 'id="produkt_name"', '' );
  open_tag( 'p', '', '', 'Bitte Menge wählen:');
  open_table( 'list', 'width="100%"' );
    open_tr();
      open_td( '', '', 'Menge:');
      open_td( 'mult', '', mult_view( 1, "kaufmenge", true, true, $prevent_submit ) );
      open_td( 'unit', 'id="verteileinheit" width=1' );
    open_tr();
      open_td( '', '', 'Preis:');
      open_td( 'number', 'id="preis" colspan=2', '' );
  close_table();
  open_div( 'not_touch_only medskip', 'style="text-align:center"');
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_cancel" style="background-color:darkorange;"'
            , 'barcode_scanner' );
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_minus" style="background-color:darkred;"'
            , 'remove' );
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_plus" style="background-color:darkgreen;"'
            , 'add' );
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_buy" style="background-color:rgb(15,33,139); color:rgb(255,255,0);"'
            , 'euro' );
  close_div();
close_div();
close_form();
open_div( 'tab', 'id="check-remaining"' );
  open_tag( 'h1', '', 'id="check-remaining-produkt_name"', '');
  open_tag( 'p', '', '', 'Wieviel ist noch übrig?');
  open_table( 'list', 'width="100%"' );
    open_tr();
      open_td( '', '', 'Rest:');
      open_td( 'mult', '', mult_view( 1, "restmenge", true, true, $prevent_submit ) );
      open_td( 'unit', 'id="check-remaining-verteileinheit" width=1' );
  close_table();
  open_div( 'not_touch_only medskip', 'style="text-align:center"');
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_check-remaining_skip" style="background-color:darkorange;"'
            , 'skip_next' );
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_check-remaining_minus" style="background-color:darkred;"'
            , 'remove' );
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_check-remaining_plus" style="background-color:darkgreen;"'
            , 'add' );
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_check-remaining_confirm" style="background-color:rgb(15,33,139);"'
            , 'verified' );
  close_div();
close_div();
open_div( 'tab', 'id="error"' );
  open_tag( 'h1', '', 'id="error_title"', 'Fehler' );
  open_div( 'error_icon', '', '');
  open_div( '', 'id="error_description"', '' );
  open_div( 'not_touch_only medskip', 'style="text-align:center"');
    open_div( 'touch_button material-symbols-rounded'
            , 'id="button_error_reset" style="background-color:darkorange;"'
            , 'barcode_scanner' );
  close_div();
  open_div( '', 'id="error_details"', '' );
close_div();
open_div( 'tab', 'id="success"' );
  open_tag( 'h1', '', '', 'Kauf eingetragen!' );
  open_div( 'success_icon', '', '');
close_div();

open_tag ( 'hr', '', '', '' );

open_table ( 'list', 'id="bonliste"' );
close_table();

open_javascript(<<<'EOD'

function getCookies() {
  return Object.fromEntries(document.cookie.split(/; */).map(function(c) {
    var index = c.indexOf("=");     // Find the index of the first equal sign
    var key   = c.slice(0, index);  // Everything upto the index is the key
    var value = c.slice(index + 1); // Everything after the index is the value

    // Return the key and value
    return [ decodeURIComponent(key), decodeURIComponent(value) ];
  }));
}

function setCookie(key, value) {
  document.cookie = encodeURIComponent(key)+"="+encodeURIComponent(value)+"; SameSite=Strict"
}

function datediff(first, second) {
  return Math.round((second - first) / (1000 * 60 * 60 * 24));
}

var dom_top;
var dom_kaufmenge;
var dom_verteilmult;
var dom_basarmenge;
var dom_restmenge;
var dom_bonliste;
var tab_ids;
var basarkaufbon;

function evaluateDom() {
  dom_top = $('top');
  dom_kaufmenge = $('kaufmenge');
  dom_verteilmult = $('verteilmult');
  dom_basarmenge = $('basarmenge');
  dom_restmenge = $('restmenge');
  dom_bonliste = $('bonliste');

  tab_ids = $$('div.tab').map( tab => tab.id );
}

function tab(id) {
  tab_ids.forEach(candidate =>  {
    candidate == id ? $(candidate).show() : $(candidate).hide();
  });

  dom_top.scrollIntoView();
  window.scrollTo( 0, 0 );
}

var bonTemplate = new Template(`
  <tr>
    <td>#{datum}</td>
    <td>#{menge}</td>
    <td>#{produkt}</td>
    <td>#{preis}</td>
  </tr>
`);

function displayBon(bon) {
  bon = { ...bon };
  bon.datum = bon.datum.toLocaleDateString(undefined, {day:'2-digit', month:'2-digit', year:'2-digit'});
  dom_bonliste.insert(bonTemplate.evaluate(bon));
}

function initBasarkaufbon() {
  // read from cookie and make proper dates
  basarkaufbon = JSON.parse(getCookies().basarkaufbon ?? '[]');
  basarkaufbon.forEach( bon => {
    bon.datum = new Date(bon.datum);
  } );

  // expire old entries
  var heute = new Date();
  basarkaufbon = basarkaufbon.filter( bon => {
    return datediff(bon.datum, heute) <= 28;
  });
  setCookie('basarkaufbon', JSON.stringify(basarkaufbon));

  basarkaufbon.forEach(displayBon);
}

function error( title, description = '' ) {
  $('error_title').update( title );
  $('error_description').update( description );
  $('error_details').update();
  tab( 'error' );
}

function ajax_error( response ) {
  let json = response.responseJSON;
  ajax.itan = response.responseJSON.next_itan;

  $('error_title').update( response.statusText );

  let description = $('error_description');
  description.update( json.comment );
  let details = $('error_details');
  details.update();
  if( json.stack )
    details.update( `
      <fieldset>
        <legend>Stack Trace</legend>
        <pre>${json.stack}</pre>
      </fieldset>
    ` );

  tab( 'error' );
}

function resumeScanning() {
  Quagga.start();
  tab('scan-product');
}

function pickDelivery( ean, index, found ) {
  $('ean').value = ean;
  $('produkt_name').textContent = found.produkt_name;
  $('bestell_index').value = index;
  $('bestell_id').value = found.bestell_id;
  $('produkt_id').value = found.produkt_id;
  dom_verteilmult.value = found.verteilmult;
  dom_basarmenge.value = found.basarmenge;
  $('endpreis').value = found.endpreis;
  $('verteileinheit').textContent = found.verteileinheit;
  dom_kaufmenge.value = found.verteilmult;
  dom_kaufmenge.min = 0;
  dom_kaufmenge.max = found.basarmenge * found.verteilmult;
  dom_kaufmenge.fire('kaufmenge:change');
  tab('enter-amount');
}

function buySuccess(json) {
  let verteilmult = dom_verteilmult.value;
  let restmenge =
    availableByEan[$('ean').value][parseInt($('bestell_index').value)].basarmenge -= dom_kaufmenge.value / verteilmult;
  let produktname = $('produkt_name').textContent;
  let verteileinheit = $('verteileinheit').textContent;

  let bon = {
    datum: new Date()
  , produkt: produktname
  , menge: dom_kaufmenge.value + ' ' + verteileinheit
  , preis: $('preis').textContent
  };

  displayBon(bon);
  basarkaufbon.push(bon);
  setCookie('basarkaufbon', JSON.stringify(basarkaufbon));

  $('check-remaining-produkt_name').textContent = produktname;
  dom_restmenge.value = restmenge * verteilmult;
  $('check-remaining-verteileinheit').textContent = verteileinheit;

  tab( 'check-remaining' );
}

function checkRemainingSuccess(json) {
  let verteilmult = dom_verteilmult.value;
  availableByEan[$('ean').value][parseInt($('bestell_index').value)].basarmenge = dom_restmenge.value / verteilmult;

  tab( 'success' );
  window.setTimeout(resumeScanning, 1500);
}

var CodeScanner = {
  init: function() {
    var self = this;

    Quagga.init(this.state, function(err) {
      if (err) {
          return self.handleError(err);
      }
      //Quagga.registerResultCollector(resultCollector);
      //App.attachListeners();
      self.checkCapabilities();
      Quagga.start();
    });
  },
  handleError: function(err) {
    console.log(err);
  },
  checkCapabilities: function() {
    var track = Quagga.CameraAccess.getActiveTrack();
    var capabilities = {};
    if (typeof track.getCapabilities === 'function') {
        capabilities = track.getCapabilities();
    }
    // window.alert( 'capabilities: '+ JSON.stringify(capabilities) );
    // this.applySettingsVisibility('zoom', capabilities.zoom);
    // this.applySettingsVisibility('torch', capabilities.torch);
    track.applyConstraints( {advanced:[{focusMode: 'continuous'}]} );
    track.applyConstraints( {advanced:[{zoom: 4}]} );
  },

  state: {
    inputStream: {
      type : "LiveStream",
      constraints: {
        width: {min: 600},
        height: {min: 600},
        facingMode: "environment",
        aspectRatio: {min: 0.5, max: 1}
      },
    },
    locator: {
      patchSize: "medium",
      halfSample: true
    },
    numOfWorkers: 2,
    frequency: 10,
    decoder: {
      readers : [{
        format: "ean_reader",
        config: {}
      }]
    },
    locate: true
  },
};

function handleQuaggaDetected( result ) {
  var code = result.codeResult.code;
  var found = availableByEan[code];
  if( ! found ) {
    console.log(code + " NOT FOUND");
    return;
  }
  Quagga.pause();

  if ( found.length == 1 ) {
    pickDelivery(code, 0, found[0]);
    return;
  }
  $('pick-delivery-produkt_name').textContent = found[0].produkt_name;
  var deliveryPicker = $('delivery-list');
  var deliveryList = new Element('table', { 'class': 'layout', 'width': '100%' });

  var deliveryTemplate = new Template(`
    <tr>
      <td style="vertical-align:middle">
        <div class="touch_button material-symbols-rounded"
             style="background-color:darkgreen;"
             onclick='pickDelivery("#{code}", #{index}, #{json})'>
          add_shopping_cart
        </div>
      </td>
      <td style="vertical-align:middle">
        <table class="list" width="100%">
          <tr>
            <td colspan=2>#{bestellung}</td>
          </tr>
          <tr>
            <td>Lieferdatum:</td>
            <td>#{lieferdatum}</td>
          </tr>
          <tr>
            <td>Menge:</td>
            <td>#{basarmenge}</td>
          </tr>
        </table>
      </td>
    </tr>
  `);

  found.forEach((candidate, index) => {
    var templateData = { code: code, index: index, ...candidate, 'json': JSON.stringify( candidate ) };
    templateData.basarmenge = candidate.basarmenge * candidate.verteilmult + ' ' + candidate.verteileinheit;
    deliveryList.insert(deliveryTemplate.evaluate(templateData));
  });
  deliveryPicker.update(deliveryList);

  tab('pick-delivery');
}

function onDomReady() {
  evaluateDom();
  initBasarkaufbon();

  tab('scan-product');

  $('button_cancel').observe('click', resumeScanning);
  $('button_error_reset').observe('click', resumeScanning);

  $('button_minus').observe('click', () => {
    if (parseFloat(dom_kaufmenge.value) > parseFloat(dom_verteilmult.value))
      dom_kaufmenge.value -= dom_verteilmult.value;
      if (parseFloat(dom_kaufmenge.value) < parseFloat(dom_verteilmult.value))
        dom_kaufmenge.value = dom_verteilmult.value;
      dom_kaufmenge.fire('kaufmenge:change');
    });

  $('button_plus').observe('click', () => {
    dom_kaufmenge.value = parseFloat(dom_kaufmenge.value) + parseFloat(dom_verteilmult.value);
    if (dom_kaufmenge.value > dom_basarmenge.value * dom_verteilmult.value)
      dom_kaufmenge.value = dom_basarmenge.value * dom_verteilmult.value;
    dom_kaufmenge.fire('kaufmenge:change');
  });

  $('button_buy').observe( 'click', () => {
    new Ajax.Request( ajax.url, {
      parameters: {
        action: 'basarzuteilung',
        produkt: $('produkt_id').value,
        bestellung: $('bestell_id').value,
        menge: dom_kaufmenge.value,
        itan: ajax.itan
      },
      onSuccess: function(response) {
        let json = response.responseJSON;
        ajax.itan = json.next_itan;
        if( !ajax.itan ) {
          error( 'Keine neue ITAN erhalten!', JSON.stringify(json) );
          return;
        }
        if( !json.success ) {
          error( 'Unerwarteter Fehler!', JSON.stringify(json) );
          return;
        }
        buySuccess(json);
      },
      onFailure: function(response) {
        ajax_error( response );
      }
    } ); // ajax
  } );

  dom_kaufmenge.observe('change', () => {
    dom_kaufmenge.fire('kaufmenge:change');
  });

  dom_kaufmenge.observe('kaufmenge:change', () => {
    $('preis').textContent = (dom_kaufmenge.value / dom_verteilmult.value * $('endpreis').value).toFixed(2);
    $('button_minus').toggleClassName('disabled', parseFloat(dom_kaufmenge.value) <= parseFloat(dom_verteilmult.value) );
    $('button_plus').toggleClassName('disabled', parseFloat(dom_kaufmenge.value) >= dom_basarmenge.value * dom_verteilmult.value);
  });

  $('button_check-remaining_skip').observe( 'click', () => {
    tab( 'success' );
    window.setTimeout(resumeScanning, 1500);
  } );

  dom_restmenge.observe('change', () => {
    dom_restmenge.fire('restmenge:change');
  });

  dom_restmenge.observe('restmenge:change', () => {
    $('button_minus').toggleClassName('disabled', parseFloat(dom_restmenge.value) <= 0 );
  });

  $('button_check-remaining_minus').observe( 'click', () => {
    if (parseFloat(dom_restmenge.value) > 0)
      dom_restmenge.value -= dom_verteilmult.value;
    if (parseFloat(dom_restmenge.value) < 0)
      dom_restmenge.value = 0;
      dom_restmenge.fire('restmenge:change');
  } );

  $('button_check-remaining_plus').observe('click', () => {
    dom_restmenge.value = parseFloat(dom_restmenge.value) + parseFloat(dom_verteilmult.value);
    dom_restmenge.fire('restmenge:change');
  });

  $('button_check-remaining_confirm').observe( 'click', () => {
    new Ajax.Request( ajax.url, {
      parameters: {
        action: 'inventur',
        produkt: $('produkt_id').value,
        bestellung: $('bestell_id').value,
        menge: dom_restmenge.value,
        itan: ajax.itan
      },
      onSuccess: function(response) {
        let json = response.responseJSON;
        ajax.itan = json.next_itan;
        if( !ajax.itan ) {
          error( 'Keine neue ITAN erhalten!', JSON.stringify(json) );
          return;
        }
        if( !json.success ) {
          error( 'Unerwarteter Fehler!', JSON.stringify(json) );
          return;
        }
        checkRemainingSuccess(json);
      },
      onFailure: function(response) {
        ajax_error( response );
      }
    } ); // ajax
  } );

  var target = $('scanner-viewport');
  CodeScanner.state.inputStream.target = target;
  CodeScanner.init();

  Quagga.onProcessed(function(result) {
    var drawingCtx = Quagga.canvas.ctx.overlay,
        drawingCanvas = Quagga.canvas.dom.overlay;

    if (result) {
      drawingCtx.clearRect(0, 0, parseInt(drawingCanvas.getAttribute("width")), parseInt(drawingCanvas.getAttribute("height")));
      if (result.boxes) {
          // window.alert('drawingCanvas: ' + drawingCanvas.getAttribute("width") + ' x ' + drawingCanvas.getAttribute("height"));
          result.boxes.filter(function (box) {
              return box !== result.box;
          }).forEach(function (box) {
              Quagga.ImageDebug.drawPath(box, {x: 0, y: 1}, drawingCtx, {color: "orange", lineWidth: 2});
          });
      }

      if (result.box) {
          Quagga.ImageDebug.drawPath(result.box, {x: 0, y: 1}, drawingCtx, {color: "green", lineWidth: 5});
      }

      if (result.codeResult && result.codeResult.code) {
          Quagga.ImageDebug.drawPath(result.line, {x: 'x', y: 'y'}, drawingCtx, {color: 'red', lineWidth: 10});
      }
    }
  } )

  Quagga.onDetected(handleQuaggaDetected);
}

if (document.readyState === 'loading') {  // Loading hasn't finished yet
  document.observe('DOMContentLoaded', onDomReady);
} else {  // `DOMContentLoaded` has already fired
  onDomReady();
}
EOD);
?>
