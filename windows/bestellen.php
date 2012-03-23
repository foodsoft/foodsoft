<?PHP
error_reporting('E_ALL'); 

assert( $angemeldet ) or exit();

setWikiHelpTopic( "foodsoft:bestellen" );

if( hat_dienst(4) ) {
  $gruppen_id = $basar_id;
  $kontostand = 250.0;
  $festgelegt = 0.0;
  echo "<h1>Bestellen f&uuml;r den Basar</h1>";
} else {
  $gruppen_id = $login_gruppen_id;  // ...alle anderen fuer sich selbst!
  $kontostand = kontostand( $gruppen_id );
  // $festgelegt = gruppenkontostand_festgelegt( $gruppen_id );
  echo "<h1>Bestellen f&uuml;r Gruppe $login_gruppen_name</h1>";
}

get_http_var('bestell_id','u',false,true );
if( $bestell_id ) {
  if( sql_bestellung_status( $bestell_id ) != STATUS_BESTELLEN )
    $bestell_id = 0;
}

$laufende_bestellungen = sql_bestellungen( 'rechnungsstatus = ' . STATUS_BESTELLEN );
if( count( $laufende_bestellungen ) < 1) {
  div_msg( 'warn', "Zur Zeit laufen leider keine Bestellungen! <a href='index.php'>Zurück...</a>" );
  return;
}

// tabelle fuer infos und auswahl bestellungen:
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

get_http_var( 'action', 'w', '' );
if( $readonly )
  $action = '';
switch( $action ) {
  case 'produkt_hinzufuegen':
    need_http_var( 'produkt_id', 'U' );
    sql_insert_bestellvorschlag( $produkt_id, $bestell_id );
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
      $vormerken = ( ${"vm_$n"} == 'yes' ? true : false );
      $bestellungen[$n] = array( 'fest' => $fest, 'toleranz' => $toleranz, 'vormerken' => $vormerken );
      $gesamtpreis += $produkt['endpreis'] * ( $fest + $toleranz );
    }
    if( $gesamtpreis > 0.005 ) {
      need( $gesamtpreis <= $kontostand, "Konto &uuml;berzogen!" );
    }
    foreach( $bestellungen as $produkt_id => $m ) {
      change_bestellmengen( $gruppen_id, $bestell_id, $produkt_id, $m['fest'], $m['toleranz'], $m['vormerken'] );
    }
    logger( "Bestellung speichern: $bestell_id" );
    $js_on_exit[] = "alert( 'Bestellung wurde eingetragen!' );";
    break;
  case 'delete':
    need_http_var( 'produkt_id', 'U' );
    sql_delete_bestellvorschlag( $produkt_id, $bestell_id );
    break;
  case 'update_prices':
    // preiseintrage automatisch aktualisieren: bisher nur fuer bestellnummern:
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
      $js_on_exit[] = "alert( 'Die Preiseintraege von $n Produkten wurden aktualisiert.' );";
    } else {
      $js_on_exit[] = "alert( 'Fuer kein Produkt konnte der Preis automatisch aktualisiert werden --- bitte manuell pruefen!' );";
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
    echo "Warnung: bei <span id='preise_falsch'>?</span> Produkten scheinen die Preise falsch --- bitte pruefen!";
  close_div();
  smallskip();
}

// $festgelegt = gruppenkontostand_festgelegt( $gruppen_id );

if( ! $readonly ) {
  $bestellform_id = open_form( '', 'action=bestellen' );

  ?>
  <script type="text/javascript">
    var anzahl_produkte = <?php echo count( $produkte ); ?>;
    var kontostand = <?php printf( "%.2lf", $kontostand ); ?>;
    var gesamtpreis = 0.00;
    var aufschlag = <?php printf( "%.2lf", $gesamtbestellung['aufschlag'] ); ?>;
    var toleranz_default_faktor = <?php printf( "%.3lf", 0.001 + $toleranz_default / 100.0 ); ?>;
    var gebindegroesse     = new Array();
    var preis              = new Array();
    var kosten             = new Array();
    var fest_alt           = new Array();   // festbestellmenge der gruppe bisher
    var fest               = new Array();   // festbestellmenge der gruppe aktuell
    var fest_andere        = new Array();   // festbestellmenge anderer gruppen
    var zuteilung_fest_alt = new Array();
    var toleranz_alt       = new Array();
    var toleranz           = new Array();
    var toleranz_andere    = new Array();
    var verteilmult        = new Array();

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

    function zuteilung_berechnen( produkt, init ) {
      var festmenge, toleranzmenge, gebinde, bestellmenge, restmenge, zuteilung_fest, t_min;
      var menge, quote, zuteilung_toleranz, kosten_neu, reminder, konto_rest, kontostand_neu;
      var id;

      // bestellmenge berechnen: wieviel kann insgesamt bestellt werden:
      //
      festmenge = fest_andere[produkt] + fest[produkt];
      toleranzmenge = toleranz_andere[produkt] + toleranz[produkt];

      // volle fest bestellte gebinde:
      //
      gebinde = Math.floor( festmenge / gebindegroesse[produkt] );

      // falls angebrochenes gebinde: wenn moeglich, mit toleranz auffuellen:
      //
      if( gebinde * gebindegroesse[produkt] < festmenge )
        if( (gebinde+1) * gebindegroesse[produkt] <= festmenge + toleranzmenge )
          gebinde++;
      bestellmenge = gebinde * gebindegroesse[produkt];

      restmenge = bestellmenge;
      zuteilung_fest = 0;
      if( fest[produkt] >= fest_alt[produkt] ) {

        // falls festmenge hoeher oder gleichgeblieben:
        // gruppe kriegt mindestens das, was schon vorher zugeteilt worden waere:
        //
        menge = Math.min( zuteilung_fest_alt[produkt], restmenge );
        zuteilung_fest += menge;
        restmenge -= menge;

        // ...dann werden, soweit moeglich, die anderen festbestellungen erfuellt:
        //
        menge = Math.min( fest_andere[produkt], restmenge );
        restmenge -= menge;

        // ...dann wird die zuteilung der gruppe, soweit moeglich, aufgestockt:
        //
        menge = Math.min( fest[produkt] - zuteilung_fest, restmenge );
        zuteilung_fest += menge; restmenge -= menge;

      } else {

        // festmenge wurde reduziert:
        // erstmal werden die anderen gruppen beruecksichtigt...
        //
        menge = Math.min( fest_andere[produkt], restmenge );
        restmenge -= menge;

        // ...und erst dann die gruppe, die reduziert hat:
        //
        menge = Math.min( fest[produkt], restmenge );
        zuteilung_fest += menge; restmenge -= menge;

      }

      // falls noch toleranz beruecksichtigt wird: moeglichst gleichmaessig nach quote verteilen:
      //
      if( restmenge > 0 ) {
        quote = restmenge / ( toleranz_andere[produkt] + toleranz[produkt] );
        menge = Math.min( Math.ceil( toleranz[produkt] * quote ), restmenge );
        zuteilung_toleranz = menge;
      } else {
        zuteilung_toleranz = 0;
      }

      // anzeige gesamt aktualisieren:
      //
      if( festmenge )
        s = festmenge * verteilmult[produkt];
      else
        s = '0';
      if( toleranzmenge > 0 )
        s = s + ' ... ' + (festmenge + toleranzmenge) * verteilmult[produkt];
      document.getElementById('gv_'+produkt).firstChild.nodeValue = s;

      if( gebinde > 0 ) {
        document.getElementById('g_'+produkt).className = 'highlight';
        document.getElementById('gg_'+produkt).firstChild.nodeValue = gebinde;
      } else {
        document.getElementById('gg_'+produkt).firstChild.nodeValue = '0';
        if( festmenge + toleranzmenge > 0 ) {
          document.getElementById('g_'+produkt).className = 'crit';
        } else {
          document.getElementById('g_'+produkt).className = '';
        }
      }
      gwidth = document.getElementById('g_'+produkt).offsetWidth;
      toleranzmax = Math.min( gebindegroesse[produkt] - 1, toleranzmenge );
      if( gwidth > 40 ) {
        nw = Math.floor( gwidth * ( ( festmenge + toleranzmax ) / gebindegroesse[produkt] - gebinde ) );
        document.getElementById('gi_'+produkt).style.width = ( nw + 'px' );
        document.getElementById('gi_'+produkt).style.marginRight = ((-nw) + 'px');
        document.getElementById('g_'+produkt).style.offsetWidth = gwidth;
      }

      // anzeige gruppe aktualisieren:
      //
      s = fest[produkt] * verteilmult[produkt];
      var toleranzNode = document.getElementById('t_'+produkt);
      
      // also show when tolerance changed for marking change by color
      if( toleranz[produkt] > 0 || toleranz_alt[produkt] != toleranz[produkt] ) {
        s = s + ' ... ';
        toleranzNode.firstChild.nodeValue = ( fest[produkt] + toleranz[produkt] ) * verteilmult[produkt];
      } else {
        toleranzNode.firstChild.nodeValue = ' ';
      }
      
      var festNode = document.getElementById('f_'+produkt);
      festNode.firstChild.nodeValue = s;
      
      // highlight changes
      if (!init) {
        set_class(festNode, 'changed', fest[produkt] != fest_alt[produkt]);
        set_class(
            toleranzNode, 
            'changed', 
            fest[produkt] + toleranz[produkt] != fest_alt[produkt] + toleranz_alt[produkt]);
      }

      // update order form fields
      document.getElementById('fest_'+produkt).value = fest[produkt];
      document.getElementById('toleranz_'+produkt).value = toleranz[produkt];

      <?php if( ! hat_dienst(4) ) { ?>
      zuteilung = zuteilung_fest + zuteilung_toleranz;
      if( zuteilung > 0 ) {
        document.getElementById('z_'+produkt).firstChild.nodeValue = zuteilung * verteilmult[produkt];
        document.getElementById('zt_'+produkt).className = 'center highlight';
      } else {
        document.getElementById('z_'+produkt).firstChild.nodeValue = '0';
        if( fest[produkt] + toleranz[produkt] > 0 ) {
          document.getElementById('zt_'+produkt).className = 'center crit';
        } else {
          document.getElementById('zt_'+produkt).className = 'center';
        }
      }
      <?php } ?>

      // kosten und neuen kontostand berechnen und anzeigen:
      //
      kosten_neu = preis[produkt] * ( fest[produkt] + toleranz[produkt] );
      gesamtpreis += ( kosten_neu - kosten[produkt] );
      kosten[produkt] = kosten_neu;
      if( ( fest[produkt] + toleranz[produkt] ) > 0 ) {
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
      kontostand_neu = ( kontostand - gesamtpreis ).toFixed(2);
      konto_rest = document.getElementById('konto_rest');
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

      if( ! init ) {
        reminder_on();
      }

      return true;
    }

    function reminder_on() {
      var reminder = document.getElementById('floating_submit_button_<?php echo $bestellform_id; ?>');
      var footbar = document.getElementById('footbar');
      while (footbar.firstChild) {
        footbar.removeChild(footbar.firstChild);
      }
      reminder.style.display = "inline";
      footbar.appendChild(reminder);
      
      set_footbar(true);
      
      id = document.getElementById('hinzufuegen');
      while( id.firstChild ) {
        id.removeChild( id.firstChild );
      }
      id.appendChild( document.createTextNode( 'Produkt hinzufuegen: bitte vorher erst Änderungen speichern!' ) );
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
      var gebinde;
      gebinde = Math.floor( fest[produkt] / gebindegroesse[produkt] );
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
      var gebinde;
      gebinde = Math.ceil( fest[produkt] / gebindegroesse[produkt] ) - 1;
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
      gebinde = Math.floor( fest[produkt] / gebindegroesse[produkt] );
      if( fest[produkt] - gebinde * gebindegroesse[produkt] > 0 ) {
        toleranz[produkt] = (gebinde+1) * gebindegroesse[produkt] - fest[produkt];
      } else {
        toleranz[produkt] = 0;
      }
      zuteilung_berechnen( produkt, false );
    }
    function bestellung_submit( produkt ) {
      if( gesamtpreis > kontostand ) {
        alert( 'Kontostand nicht ausreichend!' );
      } else {
        document.forms['form_<?php echo $bestellform_id; ?>'].submit();
      }
    }
  </script>
  <?php

  open_div( 'alert nodisplay tight', "id='floating_submit_button_$bestellform_id' style='width:100%'" );
    open_div( 'table', 'style="width:100%;"' );
      open_div( 'tr' );
        open_div( 'alert left td', '', fc_link( 'self', array( 'class' => 'close', 'url' => "javascript:set_footbar(0);") ) );
        open_div( 'alert left td', '', "&Auml;nderungen sind noch nicht gespeichert!" );
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
    open_th( '', "title='maximale (bei voller Zuteilung) Kosten f&uuml;r deine Gruppe'", 'Kosten' );
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
    open_th( '', "colspan='1' title='insgesamt gefuellte Gebinde'", 'volle Gebinde' );
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

  open_td();
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
          // kein Katalog erfasst: Abgleich nicht moeglich!
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
        echo fc_link( 'produktdetails', array( 'produkt_id' => $n, 'bestell_id' => $bestell_id
                                          , 'text' => sprintf( '%.2lf', $preis ), 'class' => 'href' ) );
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
      echo fc_action( array( 'class' => 'drop', 'text' => '', 'title' => 'Bestellvorschlag löschen'
                           , 'confirm' => 'Bestellvorschlag wirklich löschen?' )
                    , array( 'action' => 'delete', 'produkt_id' => $produkt_id ) );
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
    open_fieldset( 'small_form', '', 'Zus&auml;tzlich Produkt in Bestellvorlage aufnehmen', 'off' );
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
              submission_button( 'Produkt hinzuf&uuml;gen', true
                , "Produkt zur Bestellvorlage hinzufuegen: bist du ueberzeugt, dass das Gebinde noch voll werden wird, "
                  ."und dass du dich nicht lieber an der Bestellung eines schon teilweise gefuellten Gebindes beteiligen moechtest?"
              );
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
  
  $unlisted_products = sql_produkte( array(
      (hat_dienst( 4 ) ? 'price_on_date_or_null' : 'price_on_date') 
          => $gesamtbestellung['lieferung']
    , 'not_in_order' => $gesamtbestellung['id']
    , 'lieferanten_id' => $lieferanten_id  ));
    
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
    $json['link'] = fc_link('produktdetails', array( 
          'produkt_id' => $p['produkt_id']
        , 'text' => 'Produktdetails'
        , 'class' => 'button noleftmargin'));
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
