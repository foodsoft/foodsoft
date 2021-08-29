<?PHP
error_reporting('E_ALL'); 

assert( $angemeldet ) or exit();

setWikiHelpTopic( "foodsoft:bestellen" );

get_http_var( 'basarmodus', 'd', 0, true);
get_http_var( 'bestell_id','u',false,true );
get_http_var( 'vertical_scroll', 'w', '' );

// variable muss evtl. an anderen Stellen mitübergeben werden, wie dem Link auf "abbrechen" im floating submit Button

if( hat_dienst(4) && $basarmodus ) {
  $gruppen_id = $basar_id; // Im Basarmodus wird für den Basar bestellt...
  $kontostand = 250.0;
  $festgelegt = 0.0;
  $heading = "Bestellen für den Basar";
} else {
  $gruppen_id = $login_gruppen_id;  // ...ansonsten für sich selbst!
  $kontostand = kontostand( $gruppen_id );
  // $festgelegt = gruppenkontostand_festgelegt( $gruppen_id );
  $heading = "Bestellen für Gruppe $login_gruppen_name";
}

if ( hat_dienst(4) ) { // add button to toggle basar/group order
  $basarToggleUrl = fc_link(
    'self',
    [
      'bestell_id' => $bestell_id ?: 0,
      'basarmodus' => -($basarmodus-1), // 0 => 1, 1 => 0
      'context' => 'js',
      ]
    );
    $orderFor = $basarmodus
      ? "Gruppe $login_gruppen_name"
      : "den Basar";
    $basarToggleButton = "<button id='basarToggleButton' onClick=\"$basarToggleUrl\" style='display:inline'>Für $orderFor bestellen</button>";
    $heading .= "&nbsp;$basarToggleButton";
  }
  
  echo "<h1>$heading</h1>";
  
  if( $bestell_id ) {
    if( sql_bestellung_status( $bestell_id ) != STATUS_BESTELLEN )
      $bestell_id = 0;
  }

$laufende_bestellungen = sql_bestellungen( 'rechnungsstatus = ' . STATUS_BESTELLEN );
if( count( $laufende_bestellungen ) < 1) {
  div_msg( 'warn', "Zur Zeit laufen leider keine Bestellungen! <a href='index.php'>Zurück...</a>" );
  return;
}

// tabelle für infos und auswahl bestellungen:
//
open_table( 'layout hfill' );

if( $bestell_id ) {
  $gesamtbestellung = sql_bestellung( $bestell_id );
  open_td( 'left' );
    bestellung_overview( $bestell_id, $gruppen_id );
}

open_td( 'qquad smallskip floatright', "id='auswahl_bestellung'" );
  ?> <h4> Zur Zeit laufende Bestellungen: </h4> <?php
  auswahl_bestellung( $bestell_id );

close_table();
medskip();

if( ! $bestell_id )
  return;

///////////////////////////////////////////
// ab hier: eigentliches bestellformular:
//

$lieferanten_id = $gesamtbestellung['lieferanten_id'];
$lieferant = sql_lieferant( $lieferanten_id );

$scroll_to_product = null;

get_http_var( 'action', 'w', '' );
if( $readonly )
  $action = '';
switch( $action ) {
  case 'produkt_hinzufuegen':
    need_http_var( 'produkt_id', 'U' );
    sql_insert_bestellvorschlag( $produkt_id, $bestell_id );
    $scroll_to_product = $produkt_id;
    $js_on_exit[] = "scrollToMarkedProduct();";
    break;
  case 'bestellen':
    $gesamtpreis = 0;
    $bestellungen = array();
    foreach( sql_bestellung_produkte( $bestell_id ) as $produkt ) {
      $n = $produkt['produkt_id'];
      get_http_var( "fest_$n", 'u', 0 );
      $fest = ${"fest_$n"};
      get_http_var( "toleranz_$n", 'u', 0 );
      $toleranz = ${"toleranz_$n"};
      get_http_var( "vm_$n", 'w', 'no' );
      $vormerken = ( ${"vm_$n"} === 'yes' );
      $bestellungen[$n] = array( 'fest' => $fest, 'toleranz' => $toleranz, 'vormerken' => $vormerken );
      $gesamtpreis += $produkt['endpreis'] * ( $fest + $toleranz );
    }
    if( $gesamtpreis > 0.005 ) {
      need( $gesamtpreis <= $kontostand, "Konto überzogen!" );
    }
    foreach( $bestellungen as $produkt_id => $m ) {
      change_bestellmengen( $gruppen_id, $bestell_id, $produkt_id, $m['fest'], $m['toleranz'], $m['vormerken'] );
    }
    logger( "Bestellung speichern: $bestell_id" );
    $js_on_exit[] = "if ( verticalScroll ) window.scrollTo(0, verticalScroll);";
    $js_on_exit[] = "showInSnackbar('Bestellung wurde eingetragen!')";
    break;
  case 'delete':
    need_http_var( 'produkt_id', 'U' );
    sql_delete_bestellvorschlag( $produkt_id, $bestell_id );
    break;
  case 'update_prices':
    // preiseinträge automatisch aktualisieren: bisher nur für bestellnummern:
    $n = 0;
    foreach( sql_bestellung_produkte( $bestell_id ) as $p ) {
      $id = update_preis( $p['produkt_id'] );
      if( $id > 0 ) {
        sql_update( 'bestellvorschlaege'
        , array( 'gesamtbestellung_id' => $bestell_id, 'produkt_id' => $p['produkt_id'] )
        , array( 'produktpreise_id' => $id )
        );
        $n++;
      }
    }
    if( $n ) {
      $js_on_exit[] = "alert( 'Die Preiseinträge von $n Produkten wurden aktualisiert.' );";
    } else {
      $js_on_exit[] = "alert( 'Für kein Produkt konnte der Preis automatisch aktualisiert werden --- bitte manuell prüfen!' );";
    }
    break;
}

$produkte = sql_bestellung_produkte( $bestell_id, 0, 0, 'produktgruppen_name,produkt_name' );
$gesamtpreis = 0.0;


if( hat_dienst( 4 ) ) {
  $bestellnummern_falsch = array();
  $preise_falsch = array();
  if( $gesamtbestellung['lieferung'] < $mysqlheute ) {
    open_div( 'warn', '', 'Lieferdatum liegt in der Vergangenheit --- bitte '
        .fc_link( 'editBestellung', "bestell_id=$bestell_id,text=hier korrigieren!" )
       );
  }
  open_div( 'nodisplay', "id='bestellnummern_warnung'" );
    echo "Warnung: bei <span id='bestellnummern_falsch'>?</span> Produkten scheinen die Bestellnummern falsch ";
    echo fc_action( 'update,class=button,text=alle aktualisieren', 'action=update_prices' );
  close_div();
  open_div( 'nodisplay', "id='preise_warnung'" );
    echo "Warnung: bei <span id='preise_falsch'>?</span> Produkten scheinen die Preise falsch --- bitte prüfen!";
  close_div();
  smallskip();
}

// $festgelegt = gruppenkontostand_festgelegt( $gruppen_id );

if( ! $readonly ) {
  $bestellform_id = open_form( '', 'action=bestellen' );

  ?>
  <script>
    const anzahl_produkte = <?php echo count( $produkte ); ?>;
    let kontostand = <?php printf( "%.2lf", $kontostand ); ?>;
    let gesamtpreis = 0.00;
    const aufschlag = <?php printf( "%.2lf", $gesamtbestellung['aufschlag'] ); ?>;
    const toleranz_default_faktor = <?php printf( "%.3lf", 0.001 + $toleranz_default / 100.0 ); ?>;
    let gebindegroesse     = [];
    let preis              = [];
    let kosten             = [];
    let fest_alt           = [];   // festbestellmenge der gruppe bisher
    let fest               = [];   // festbestellmenge der gruppe aktuell
    let fest_andere        = [];   // festbestellmenge anderer gruppen
    let zuteilung_fest_alt = [];
    let toleranz_alt       = [];
    let toleranz           = [];
    let toleranz_andere    = [];
    let verteilmult        = [];

    const verticalScroll = <?php print ( isset($vertical_scroll) ? (int)$vertical_scroll : 0 ); ?>;

    function init_produkt( produkt, _gebindegroesse, _preis, _fest, _toleranz, _fest_andere, _toleranz_andere, zuteilung_fest, zuteilung_toleranz, _verteilmult ) {
      gebindegroesse[produkt] = _gebindegroesse;
      preis[produkt] = _preis;
      fest_alt[produkt] = _fest;
      fest[produkt] = fest_alt[produkt];
      fest_andere[produkt] = _fest_andere;
      zuteilung_fest_alt[produkt] = zuteilung_fest;
      toleranz_alt[produkt] = _toleranz;
      toleranz[produkt] = toleranz_alt[produkt];
      toleranz_andere[produkt] = _toleranz_andere;
      kosten[produkt] = _preis * ( _fest + _toleranz );
      verteilmult[produkt] = _verteilmult;
      gesamtpreis += kosten[produkt];
      zuteilung_berechnen( produkt, true );
    }

    /**
     * Calculate the amount of the product that is assigned to the ordering group
     *
     * @param {number} produkt - product id
     * @param {boolean} init - true for initial rendering,
     *   false means that a change in the data triggered the call
     */
    function zuteilung_berechnen( produkt, init ) {

      // assign shorthand names for improved readability and performance
      const gebindemenge = gebindegroesse[produkt];
      const _fest = fest[produkt];
      const _fest_alt = fest_alt[produkt];
      const _fest_andere = fest_andere[produkt];
      const _toleranz = toleranz[produkt];
      const _toleranz_alt = toleranz_alt[produkt];
      const _toleranz_andere = toleranz_andere[produkt];
      const _verteilmult = verteilmult[produkt];
      const _zuteilung_fest_alt = zuteilung_fest_alt[produkt];

      // bestellmenge berechnen: wieviel kann insgesamt bestellt werden?
      //
      const festmenge = _fest_andere + _fest;
      const toleranzmenge = _toleranz_andere + _toleranz;

      // anzahl der zu bestellenden gebinde berechnen
      // volle fest bestellte gebinde:
      //
      let gebinde = Math.floor( festmenge / gebindemenge );

      // falls angebrochenes gebinde: wenn möglich, mit toleranz auffüllen:
      //
      let bestellmenge = gebinde * gebindemenge;
      if( bestellmenge < festmenge )
        if( bestellmenge + gebindemenge <= festmenge + toleranzmenge ) {
          gebinde++;
          bestellmenge += gebindemenge;
        }

      let zuteilung_fest = 0;
      let zuteilung_toleranz = 0;
      
      let restmenge = bestellmenge;
      if( _fest >= _fest_alt ) {

        // falls festmenge höher oder gleichgeblieben:
        // gruppe kriegt mindestens das, was schon vorher zugeteilt worden wäre:
        //
        let menge = Math.min( _zuteilung_fest_alt, restmenge );
        zuteilung_fest += menge;
        restmenge -= menge;

        // ...dann werden, soweit möglich, die anderen festbestellungen erfüllt:
        //
        menge = Math.min( _fest_andere, restmenge );
        restmenge -= menge;

        // ...dann wird die zuteilung der gruppe, soweit möglich, aufgestockt:
        //
        menge = Math.min( _fest - zuteilung_fest, restmenge );
        zuteilung_fest += menge;
        restmenge -= menge;

      } else {

        // festmenge wurde reduziert:
        // erstmal werden die anderen gruppen berücksichtigt...
        //
        let menge = Math.min( _fest_andere, restmenge );
        restmenge -= menge;

        // ...und erst dann die gruppe, die reduziert hat:
        //
        menge = Math.min( _fest, restmenge );
        zuteilung_fest += menge;
        restmenge -= menge;

      }

      let _toleranz_gesamt = _toleranz_andere + _toleranz;

      if( restmenge > 0 && _toleranz_gesamt > 0 ) {
        // falls noch toleranz berücksichtigt wird:
        // möglichst gleichmäßig nach quote verteilen
        const quote = restmenge / ( _toleranz_gesamt );
        zuteilung_toleranz = Math.min( Math.ceil( _toleranz * quote ), restmenge );
      }

      // anzeige gesamt aktualisieren (Gesamtmengen, Spalte "Gesamtbestellung")
      let anzeige_gesamt;
      anzeige_gesamt = (festmenge * _verteilmult).toString();
      if( toleranzmenge > 0 )
        anzeige_gesamt += ' ... ' + ((festmenge + toleranzmenge) * _verteilmult).toString();
      document.getElementById('gv_'+produkt).firstChild.nodeValue = anzeige_gesamt;

      if( gebinde > 0 ) {
        document.getElementById('g_'+produkt).className = 'highlight';
        document.getElementById('gg_'+produkt).firstChild.nodeValue = gebinde.toString();
      } else {
        document.getElementById('gg_'+produkt).firstChild.nodeValue = '0';
        if( festmenge + toleranzmenge > 0 ) {
          document.getElementById('g_'+produkt).className = 'crit';
        } else {
          document.getElementById('g_'+produkt).className = '';
        }
      }
      const gwidth = document.getElementById('g_'+produkt).offsetWidth;
      const toleranzmax = Math.min( gebindemenge - 1, toleranzmenge );
      if( gwidth > 40 ) {
        const nw = Math.floor( gwidth * ( ( festmenge + toleranzmax ) / gebindemenge - gebinde ) ).toString();
        document.getElementById('gi_'+produkt).style.width = ( nw + 'px' );
        document.getElementById('gi_'+produkt).style.marginRight = ((-nw) + 'px');
        document.getElementById('g_'+produkt).style.offsetWidth = gwidth;
      }

      // anzeige für gruppe aktualisieren (effektive Zuteilung, Spalte "Zuteilung")
      let anzeige_gruppe;
      anzeige_gruppe = _fest * _verteilmult;
      const toleranzNode = document.getElementById('t_'+produkt);
      
      // also show when tolerance changed for marking change by color
      if( _toleranz > 0 || _toleranz_alt !== _toleranz ) {
        anzeige_gruppe += ' ... ';
        toleranzNode.firstChild.nodeValue = (( _fest + _toleranz ) * _verteilmult).toString();
      } else {
        toleranzNode.firstChild.nodeValue = ' ';
      }
      
      const festNode = document.getElementById('f_'+produkt);
      festNode.firstChild.nodeValue = anzeige_gruppe;
      
      // highlight changes
      if (!init) {
        set_class(festNode, 'changed', _fest !== _fest_alt);
        set_class(
            toleranzNode, 
            'changed', 
            _fest + _toleranz !== _fest_alt + _toleranz_alt
        );
      }

      // update order form fields
      document.getElementById('fest_'+produkt).value = _fest;
      document.getElementById('toleranz_'+produkt).value = _toleranz;

      <?php if( ! hat_dienst(4) ) { ?>
      const zuteilung = zuteilung_fest + zuteilung_toleranz;
      if( zuteilung > 0 ) {
        document.getElementById('z_'+produkt).firstChild.nodeValue = (zuteilung * _verteilmult).toString();
        document.getElementById('zt_'+produkt).className = 'center highlight';
      } else {
        document.getElementById('z_'+produkt).firstChild.nodeValue = '0';
        if( _fest + _toleranz > 0 ) {
          document.getElementById('zt_'+produkt).className = 'center crit';
        } else {
          document.getElementById('zt_'+produkt).className = 'center';
        }
      }
      <?php } ?>

      // kosten und neuen kontostand berechnen und anzeigen
      const kosten_neu = preis[produkt] * ( _fest + _toleranz );
      gesamtpreis += ( kosten_neu - kosten[produkt] );
      kosten[produkt] = kosten_neu;
      let tag;
      if( ( _fest + _toleranz ) > 0 ) {
        document.getElementById('k_'+produkt).firstChild.nodeValue = kosten_neu.toFixed(2);
        // document.getElementById('m_'+produkt).firstChild.nodeValue = ( fest[produkt] + toleranz[produkt] );
        if( <?php printf( hat_dienst(4) ? "gebinde" : "zuteilung" ); ?> > 0 ) {
          tag = 'highlight';
        } else {
          tag = 'crit';
        }
      } else {
        document.getElementById('k_'+produkt).firstChild.nodeValue = ' ';
        tag = '';
      }
      document.getElementById('tf_'+produkt).className = 'center mult ' + tag; // festmenge
      document.getElementById('tt_'+produkt).className = 'center unit ' + tag; // toleranzmenge
      document.getElementById('k_'+produkt).className = 'mult ' + tag;         // kosten

      document.getElementById('gesamtpreis1').firstChild.nodeValue = gesamtpreis.toFixed(2);
      document.getElementById('gesamtpreis2').firstChild.nodeValue = gesamtpreis.toFixed(2);
      const kontostand_neu = ( kontostand - gesamtpreis ).toFixed(2);
      const konto_rest = document.getElementById('konto_rest');
      konto_rest.firstChild.nodeValue = kontostand_neu;

      if( ( gesamtpreis > 0.005 ) && ( gesamtpreis > kontostand ) ) {
        konto_rest.style.color = '#c00000';
        document.getElementById('submit').className = 'button warn';
        document.getElementById('submit').firstChild.nodeValue = 'Konto überzogen';
      } else {
        konto_rest.style.color = '#000000';
        document.getElementById('submit').style.color = '#000000';
        document.getElementById('submit').className = 'button';
        document.getElementById('submit').firstChild.nodeValue = 'Bestellung Speichern';
      }

      if( !init ) {
        reminder_on();
      }

      return true;
    }

    function disable_basar_toggle() {
      const basarToggleButton = document.getElementById('basarToggleButton');
      basarToggleButton.disabled = true;
    }

    function reminder_on() {
      const reminder = document.getElementById('floating_submit_button_<?php echo $bestellform_id; ?>');
      const footbar = document.getElementById('footbar');
      while (footbar.firstChild) {
        footbar.removeChild(footbar.firstChild);
      }
      reminder.style.display = "inline";
      footbar.appendChild(reminder);
      
      set_footbar(true);
      disable_basar_toggle();
      
      const id = document.getElementById('hinzufuegen');
      while( id.firstChild ) {
        id.removeChild( id.firstChild );
      }
      id.appendChild( document.createTextNode( 'Produkt hinzufügen: bitte vorher erst Änderungen speichern!' ) );
      id.style.backgroundColor = '#ffffa0';
      id.className = 'inactive';

      document.getElementById('auswahl_bestellung').style.visibility = 'hidden';
    }

    function fest_plus( produkt ) {
      fest[produkt]++;
      if( toleranz[produkt] < gebindegroesse[produkt] - 1 ) {
        if( ( toleranz[produkt] + 1 ) <= fest[produkt] * toleranz_default_faktor ) {
          toleranz[produkt]++;
        }
      }
      zuteilung_berechnen( produkt, false );
    }

    function fest_plusplus( produkt ) {
      const gebinde = Math.floor( fest[produkt] / gebindegroesse[produkt] );
      fest[produkt] = (gebinde+1) * gebindegroesse[produkt];
      zuteilung_berechnen( produkt, false );
    }

    function fest_minus( produkt ) {
      if( fest[produkt] > 0 ) {
        fest[produkt]--;
        zuteilung_berechnen( produkt, false );
      }
    }

    function fest_minusminus( produkt ) {
      const gebinde = Math.ceil( fest[produkt] / gebindegroesse[produkt] ) - 1;
      if( gebinde > 0 ) {
        fest[produkt] = gebinde * gebindegroesse[produkt];
        zuteilung_berechnen( produkt, false );
      } else {
        fest[produkt] = 0;
        zuteilung_berechnen( produkt, false );
      }
    }

    function toleranz_plus( produkt ) {
      if( toleranz[produkt] < gebindegroesse[produkt]-1 ) {
        toleranz[produkt]++;
        zuteilung_berechnen( produkt, false );
      }
    }

    function toleranz_minus( produkt ) {
      if( toleranz[produkt] > 0 ) {
        toleranz[produkt]--;
        zuteilung_berechnen( produkt, false );
      }
    }

    function toleranz_auffuellen( produkt ) {
      const gebinde = Math.floor( fest[produkt] / gebindegroesse[produkt] );
      if( fest[produkt] - gebinde * gebindegroesse[produkt] > 0 ) {
        toleranz[produkt] = (gebinde+1) * gebindegroesse[produkt] - fest[produkt];
      } else {
        toleranz[produkt] = 0;
      }
      zuteilung_berechnen( produkt, false );
    }

    function bestellung_submit() {
      if( gesamtpreis > kontostand ) {
        alert( 'Kontostand nicht ausreichend!' );
      } else {
        let orderForm = document.forms['form_<?php echo $bestellform_id; ?>'];
        /* append hidden field holding the scroll position to the form */
        let scrollInput = document.createElement('input');
        scrollInput.name = 'vertical_scroll';
        scrollInput.type = 'hidden';
        scrollInput.value = `${document.scrollingElement.scrollTop}`;
        orderForm.appendChild(scrollInput);
        orderForm.submit();
      }
    }

    function showInSnackbar ( messageText ) {
        let snackBar = document.querySelector("#snackbar");
        snackBar.innerText = messageText;
        snackBar.className += " show";
        setTimeout(function() {
            snackBar.className = snackBar.className.replace("show", "");
        }, 8000);
    }
    
    function scrollToMarkedProduct() {
      const markedProduct = document.querySelector('#scroll-me-into-view');
      markedProduct.scrollIntoView({behavior: 'auto', block: 'center'});
      markedProduct.classList.add('background-flash');
    }
  </script>
  <?php

  /* snackbar for one-off messages */
  open_div( '', 'id="snackbar"');
  close_div();
  /* floating_submit_button */
  open_div( 'alert nodisplay tight', "id='floating_submit_button_$bestellform_id' style='width:100%'" );
    open_div( 'table', 'style="width:100%;"' );
      open_div( 'tr' );
        open_div( 'alert left td', '', fc_link( 'self', array( 'class' => 'close', 'url' => "javascript:set_footbar(0);") ) );
        open_div( 'alert left td', '', "Änderungen sind noch nicht gespeichert!" );
        open_div( 'alert center td');
          echo "Gesamtpreis: ";
          open_div( '', 'id="gesamtpreis1" style="display:inline"', '-' );
          echo ", übrig: ";
          open_div( '', 'id="konto_rest" style="display:inline"', sprintf( '%.2lf', $kontostand ) );
        close_div();
        open_div( 'alert right td' );
          echo "<a class='button' id='submit' href='javascript:bestellung_submit();'>Speichern</a>";
          echo " ", fc_link( 'self', 'class=button,text=Abbrechen' );
        close_div();
      close_div(); // tr
    close_div(); // table
  close_div(); // submit div
}

open_table( 'list hfill' );  // bestelltabelle
  ?> <!-- colgroup scheint bei firefox nicht die spur einer wirkung zu haben...
    <colgroup>
      <col width='2*'>
      <col width='3*'>
      <col width='1*'>
      <col width='3*'>
      <col width='3*'>
      <col width='3*'>
      <col width='1*'>
      <?php if( hat_dienst(4) ) echo "<col width='1*'>"; ?>
    </colgroup>
    -->
  <?php
  open_tr( 'groupofrows_top' );
    open_th( '', '', 'Produktgruppe' );
    open_th( '', '', 'Bezeichnung' );
    open_th( '', "colspan='1' title='Einzelpreis (mit Pfand, MWSt und ggf. Aufschlag)'", 'Preis' );
    open_th( '', "colspan='3'" );
      open_div();
      open_span( 'floatleft', "title='Bestellmenge deiner Gruppe'", 'deine Bestellung' );
      open_span( 'quad floatright', "title='Falls Produkt nicht kommt: automatisch vormerken für nächste Bestellung?'", "vormerken" );
      close_div();
    open_th( '', "title='maximale (bei voller Zuteilung) Kosten für deine Gruppe'", 'Kosten' );
    open_th( '', "colspan='1' title='Bestellungen aller Gruppen'", 'Gesamtbestellung' );
    if( hat_dienst(4) )
      open_th( '', '', 'Aktionen' );
    else
      open_th( 'tight', "colspan='1' title='Zuteilung (nach aktuellem Stand) an deine Gruppe'", 'Zuteilung' );
  open_tr( 'groupofrows_bottom' );
    open_th( '', '', '' );
    open_th( 'small', '', '' );
    if( $gesamtbestellung['aufschlag'] > 0 ) {
      open_th( 'small', "colspan='1'", '(mit Aufschlag)' );
    } else {
      open_th( 'small', "colspan='1'", '' );
    }
    open_th( '', "colspan='1' title='Fest-Bestellmenge: wieviel du wirklich haben willst'", 'fest' );
    open_th( '', "colspan='1' title='Toleranz-Menge: wieviel du auch mehr nehmen würdest'", 'Toleranz' );
    open_th( '', '', '' );
    open_th( 'small tight', '', '(maximal)' );
    open_th( '', "colspan='1' title='insgesamt gefüllte Gebinde'", 'volle Gebinde' );
    if( hat_dienst(4) )
      open_th( 'small tight', '', '' );
    else
      open_th( 'small tight', '', '(aktuell)' );

$produktgruppen_zahl = array();
foreach( $produkte as $produkt ) {
  $id = $produkt['produktgruppen_id'];
  $produktgruppen_zahl[$id] = adefault( $produktgruppen_zahl, $id, 0 ) + 1;
}
$produktgruppe_alt = -1;

foreach( $produkte as $produkt ) {
  open_tr();

  $produkt_id = $produkt['produkt_id'];
  $n = $produkt_id;

  $gebindegroesse = $produkt['gebindegroesse'];
  $preis = $produkt['endpreis'];
  $lv_faktor = $produkt['lv_faktor'];

  $keys = array( 'bestell_id' => $bestell_id, 'produkt_id' => $produkt_id, 'gruppen_id' => $gruppen_id );
  $festmenge = sql_bestellzuordnung_menge( $keys + array( 'art' => BESTELLZUORDNUNG_ART_FESTBESTELLUNG ) );
  $toleranzmenge = sql_bestellzuordnung_menge( $keys + array( 'art' => BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG ) );
  $vormerkung = sql_bestellzuordnung_menge( $keys + array( 'art' => BESTELLZUORDNUNG_ART_VORMERKUNGEN ) );

  $toleranzmenge_gesamt = $produkt['toleranzbestellmenge'] + $produkt['basarbestellmenge'];
  $toleranzmenge_andere = $toleranzmenge_gesamt - $toleranzmenge;

  $festmenge_gesamt = $produkt['gesamtbestellmenge'] - $toleranzmenge_gesamt;
  $festmenge_andere = $festmenge_gesamt - $festmenge;

  $zuteilungen = zuteilungen_berechnen( $produkt );
  $zuteilung_fest = adefault( $zuteilungen['festzuteilungen'], $gruppen_id, 0 );
  $zuteilung_toleranz = adefault( $zuteilungen['toleranzzuteilungen'], $gruppen_id, 0 );

  $verteilmult = $produkt['kan_verteilmult_anzeige'];

  $kosten = $preis * ( $festmenge + $toleranzmenge );
  $gesamtpreis += $kosten;
 
  $js_on_exit[] = sprintf( "init_produkt( %u, %u, %.2lf, %u, %u, %u, %u, %u, %u, %.3lf );\n"
  , $n, $gebindegroesse , $preis
  , $festmenge, $toleranzmenge
  , $festmenge_andere, $toleranzmenge_andere
  , $zuteilung_fest, $zuteilung_toleranz
  , $verteilmult
  );
  $produktgruppe = $produkt['produktgruppen_id'];
  
  $katalogeintrag = katalogsuche($produkt_id);
  
  if( $produktgruppe != $produktgruppe_alt ) {
    if( 0 * $activate_mozilla_kludges ) {
      // mozilla can't handle rowspan in complex tables on first pass (grid lines get lost),
      // so we set rowspan=1 first and modify later :-/
      open_td( '', "rowspan='1' id='pg_$produktgruppe'", $produkt['produktgruppen_name'] );
      $js_on_exit[] = "document.getElementById('pg_$produktgruppe').rowSpan = {$produktgruppen_zahl[$produktgruppe]}; ";
    } else {
      // other browsers get it right the first time, as it should be:
      open_td( '', "rowSpan='{$produktgruppen_zahl[$produktgruppe]}'", $produkt['produktgruppen_name'] );
    }
    $produktgruppe_alt = $produktgruppe;
  }

  hidden_input( "fest_$n", "$festmenge", "id='fest_$n'" );
  hidden_input( "toleranz_$n", "$toleranzmenge", "id='toleranz_$n'" );

    if ( $produkt_id === $scroll_to_product ) {
        open_td('', 'id="scroll-me-into-view"');
    } else {
        open_td();
    }
    open_span('oneline', '', $produkt['produkt_name']);
    open_span('small floatright', 'title="Quelle: Lieferantenkatalog"', catalogue_product_details($katalogeintrag) );
    open_div('small', '', $produkt['notiz']);
    
  // preis:
  $class = '';
  $title = '';
  if( hat_dienst(4) ) {
    if( sql_aktueller_produktpreis_id( $n, $gesamtbestellung['lieferung'] ) != $produkt['preis_id'] ) {
      $preise_falsch[] = $n;
      $class .= 'outdated';
      $title = 'Preis nicht aktuell!';
    } else {
      $katalogdaten = array();
      switch( katalogabgleich( $produkt_id, 0, 0, $katalogdaten ) ) {
        case 0:
          $class .= 'ok';
          $title = 'Preis aktuell und konsistent mit Lieferantenkatalog '. $katalogdaten['katalogname'];
          break;
        case 3:
          // kein Katalog erfasst: Abgleich nicht möglich!
          break;
        case 4:
          $bestellnummern_falsch[] = $n;
          $class .= 'alert';
          $title = 'Bestellnummer anders als in Lieferantenkatalog ' . $katalogdaten['katalogname'];
          break;
        case 1:
        case 2:
        default:
          $preise_falsch[] = $n;
          $class .= 'warn';
          $title = 'Abweichung oder kein Treffer bei Katalogabgleich!';
          break;
      }
    }
  }
  open_td( "top center tight $class", "title='$title'" );
    open_table( "layout $class" );
      open_tr();
        open_td( "mult $class" );
        echo fc_link(
          'produktdetails',
          [
            'text'       => sprintf( '%.2lf', $preis ),
            'produkt_id' => $n,
            'bestell_id' => $bestell_id,
            'class'      => 'href',
          ]
        );
        open_td( "unit $class", '', "/ {$produkt['verteileinheit']}" );

      open_tr();
      if( $lv_faktor != 1 ) {
        open_td( "mult small $class", '', price_view( $preis * $produkt['lv_faktor'] ) );
        open_td( "unit small $class", '', "/ {$produkt['liefereinheit']}" );
      } else {
        open_td( "mult small $class", "colspan='2'", ' ' );
      }
    close_table();

  // festmenge
  open_td( "center mult noright", "colspan='1' id='tf_$n' " );
    open_div( 'oneline right' );
      open_span( '', "id='f_$n'" );
        echo mult2string( $festmenge * $produkt['kan_verteilmult'] );
        if( $toleranzmenge > 0 )
          echo " ...";
      close_span();
    close_div();

    if( ! $readonly ) {
      open_div('oneline center smallskip');
        // if( $gebindegroesse > 1 )
        //  echo "<input type='button' value='--' onclick='fest_minusminus($n);' >";
        ?> <span onclick='fest_minus(<?php echo $n; ?>);' ><img alt='-' src='img/minus.png'></span>
            <span class='quad'>&nbsp;</span>
            <span onclick='fest_plus(<?php echo $n; ?>);' ><img alt='+' src='img/plus.png'></span> <?php
        // if( $gebindegroesse > 1 )
        //  echo "<input type='button' value='++' onclick='fest_plusplus($n);' >";
        qquad();
      close_div();
    }

  // toleranzmenge
  open_td( "center unit noleft noright", "colspan='1' id='tt_$n' " ); // toleranzwahl
    open_div( 'oneline left' );
      open_span( '', "id='t_$n'" );
        if( $toleranzmenge > 0 )
          echo mult2string( ( $festmenge + $toleranzmenge ) * $produkt['kan_verteilmult'] );
        else
          echo '&nbsp;';
      close_span();
      echo " {$produkt['kan_verteileinheit_anzeige']}";
    close_div();
    if( $gebindegroesse > 1 ) {
      if( ! $readonly ) {
        open_div('oneline center smallskip');
          qquad();
          ?> <span onclick='toleranz_minus(<?php echo $n; ?>);' ><img alt='-' src='img/minus.png'></span>
             <span class='quad'>&nbsp;</span>
             <!-- <input type='button' value='G' onclick='toleranz_auffuellen(<?php echo $n; ?>);' > -->
             <span onclick='toleranz_plus(<?php echo $n; ?>);' ><img alt='+' src='img/plus.png'></span> <?php
        close_div();
      }
    } else {
      ?> &nbsp; <?php
    }

  open_td( 'center bottom noleft' );
    $checked = ( $vormerkung > 0 ? 'checked' : '' );
    echo "<input type='checkbox' onclick='reminder_on();' name='vm_$n' value='yes' $checked>";
  close_td();

  open_td( "mult", "id='k_$n'", sprintf( '%.2lf', $kosten ) );


  // bestellungen aller gruppen:
  //
  // open_div( '', '', "f: $festmenge_gesamt; t: $toleranzmenge_gesamt" );
  open_td( "top left tight ", "id='g_$n' style='margin:0pt; padding:0pt;'" );
    open_div( 'left', "style='margin-bottom:-30px; margin-right:0px; margin-left:0px; padding:0px; top:0px; left:0px'" );
      echo "<img src='img/green.png' alt='progressbar' id='gi_$n' style='width:0px;height:30px;margin:0px;padding:0px;' >";
    close_div();
    open_div( 'oneline center' );
      // v-menge:
      open_span( 'mult', "id='gv_$n'" );
        echo mult2string( $verteilmult * $festmenge_gesamt );
        if( $toleranzmenge_gesamt > 0 ) {
          echo ' ... ' . mult2string( $verteilmult * ( $festmenge_gesamt + $toleranzmenge_gesamt ) );
        }
      close_span();
      open_span( 'unit', '', $produkt['kan_verteileinheit_anzeige'] );
    close_div();
    open_div( 'oneline center' );
       // gebinde:
        open_span( 'mult', "id='gg_$n'", sprintf( '%u', $zuteilungen[gebinde] ) );
        open_span( 'unit', '', "* (" . $produkt['gebindegroesse'] * $produkt['kan_verteilmult_anzeige'] . " {$produkt['kan_verteileinheit_anzeige']})" );
    close_div();

  if( hat_dienst(4) ) {
    open_td();
      echo fc_link( 'edit_produkt', "produkt_id=$produkt_id" );
      echo fc_action(
        [
          'text'       => '',
          'title'      => 'Bestellvorschlag löschen',
          'confirm'    => 'Bestellvorschlag wirklich löschen?',
          'class'      => 'drop', 
        ],
        [
          'action'     => 'delete',
          'produkt_id' => $produkt_id,
        ]
      );
    close_td();
  } else {
    open_td( '', "id='zt_$n'" );
      open_div( 'oneline center' );
        open_span( '', "id='z_$n'", '&nbsp;' ); // IE _braucht_ hier ein space!
        open_span( '', '', $produkt['kan_verteileinheit_anzeige'] );
      close_div();
    close_td();
  }
}


open_tr('summe');
  open_td( '', "colspan='6'", 'Gesamtpreis:' );
  open_td( 'number', "id='gesamtpreis2'", sprintf( '%.2lf', $gesamtpreis ) );
  open_td( '', "colspan='2'", '' );

close_table();

if( ! $readonly ) {
  close_form();

  if( hat_dienst( 4 ) ) {
    if( $bestellnummern_falsch ) {
      $js_on_exit[] = "document.getElementById('bestellnummern_falsch').firstChild.nodeValue
                        = ".count( $bestellnummern_falsch ).";";
      $js_on_exit[] = "document.getElementById('bestellnummern_warnung').className = 'alert';";
    }
    if( $preise_falsch ) {
      $js_on_exit[] = "document.getElementById('preise_falsch').firstChild.nodeValue
                        = ".count( $preise_falsch )." ;";
      $js_on_exit[] = "document.getElementById('preise_warnung').className = 'alert';";
    }
  }
  smallskip();
  open_div( 'middle', "id='hinzufuegen' style='display:block;'" );  
    open_fieldset( 'small_form', '', 'Zusätzlich Produkt in Bestellvorlage aufnehmen', 'off' );
      open_form( '', 'action=produkt_hinzufuegen');
        open_table('small_form');
          open_tr();
            open_td('', '', 'Suche:');
            open_td('', 'colspan=2', string_view('', 20, 'search', 'id=search', true, 'hfill'));
          open_tr();
            open_td();
              open_div('', '', 'Produkt:');
            open_td('', 'colspan=2');
              open_select('produkt_id', 'size=8 id="productSelect" class="hfill"');
              close_select();
          open_tr();
            open_td('', '', 'Produktgruppe:');
            open_td('', 'id="productGroup"', '');
          open_tr();
            open_td('', '', '');
            open_td('', 'id="productLink"', '');
            open_td('right');
              submission_button( 'Produkt hinzufügen', true );
        close_table();
      close_form();
    
      open_div();
        $anzahl_eintraege = sql_lieferant_katalogeintraege( $lieferanten_id );
        if( $anzahl_eintraege > 0 ) {
          div_msg( 'kommentar', "
            Ist ein gewünschter Artikel nicht in der Auswahlliste? 
            Im ". fc_link( 'katalog', "lieferanten_id=$lieferanten_id,text=Lieferantenkatalog,class=href" ) ."
            findest du $anzahl_eintraege Artikel; bitte wende dich an die Leute vom Dienst 4, wenn
            du einen davon in die Bestellvorlage aufnehmen lassen möchtest!
          " );
        }
      close_div();
    close_fieldset();
  close_div();
  
  $price_key = hat_dienst( 4 ) ? 'price_on_date_or_null' : 'price_on_date';
  $unlisted_products = sql_produkte([
        $price_key       => $gesamtbestellung['lieferung'],
        'not_in_order'   => $gesamtbestellung['id'],
        'lieferanten_id' => $lieferanten_id
      ]);
    
  foreach ($unlisted_products as $p) {
    $json = array();
    $json['id'] = $p['produkt_id'];
    $json['name'] = $p['name'];
    $price = $p['vpreis'];
    if (!is_null($price))
      $price = price_view($price);
    $json['price'] = $price;
    $json['unit'] = $p['verteileinheit_anzeige'];
    $json['group'] = $p['produktgruppen_name'];
    $json['link'] = fc_link(
      'produktdetails',
      [
        'produkt_id' => $p['produkt_id'],
        'text'       => 'Produktdetails',
        'class'      => 'button noleftmargin'
      ]
    );
    $json_list[] = $json;
  }
  
  
  open_javascript();
    echo toJavaScript('var unlistedProducts', $json_list);
  ?>
  var UnlistedProduct = Class.create({
    initialize: function(other) {
      this.id = other.id;
      this.name = other.name;
      this.price = other.price;
      this.unit = other.unit;
      this.group = other.group;
      this.link = other.link;
    },
    setOption: function(option) {
      option.value = this.id;
      option.innerHTML = this.name;
      option.innerHTML += ' (';
      if (this.price === null) {
        option.innerHTML += 'kein aktueller Preiseintrag';
      } else {
        option.innerHTML += 'V-Preis: ' + this.price + ' / ' + this.unit;
      }
      option.innerHTML += ')';
    }
  });
             
  var searchableSelect = new SearchableSelect($('productSelect'), $('search'));
  var productGroupCell = $('productGroup');
  var productLinkCell = $('productLink');
  
  unlistedProducts = unlistedProducts.collect(function(product) {
    return new UnlistedProduct(product);
  });
  
  function showDetails(unlistedProduct) {
    productGroupCell.innerHTML = unlistedProduct.group;
    productLinkCell.innerHTML = unlistedProduct.link;
  }
  
  searchableSelect.setEntries(unlistedProducts);
  
  $('productSelect').on('option:selected', function(event) { showDetails(event.memo); } );
  
  <?php
  close_javascript();

}

?>
