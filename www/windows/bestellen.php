<?PHP
error_reporting('E_ALL'); 

assert( $angemeldet ) or exit();

setWikiHelpTopic( "foodsoft:bestellen" );

if( hat_dienst(4) ) {
  $gruppen_id = $basar_id;
  $kontostand = 100.0;
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

open_td( 'qquad smallskip floatright' );
  ?> <h4> Zur Zeit laufende Bestellungen: </h4> <?
  auswahl_bestellung( $bestell_id );

close_table();
medskip();

if( ! $bestell_id )
  return;

///////////////////////////////////////////
// ab hier: eigentliches bestellformular:
//

$lieferanten_id = $gesamtbestellung['lieferanten_id'];

get_http_var( 'action', 'w', '' );
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
    need( $gesamtpreis <= $kontostand, "Konto &uuml;berzogen!" );
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
}

$produkte = sql_bestellung_produkte( $bestell_id, 0, 0, 'produktgruppen_name,produkt_name' );
$gesamtpreis = 0.0;

// $festgelegt = gruppenkontostand_festgelegt( $gruppen_id );

if( ! $readonly ) {
  $bestellform_id = open_form( '', 'action=bestellen' );

  ?>
  <script type="text/javascript">
    var anzahl_produkte = <? echo count( $produkte ); ?>;
    var kontostand = <? printf( "%.2lf", $kontostand ); ?>;
    var gesamtpreis = 0.00;
    var aufschlag = <? printf( "%.2lf", $gesamtbestellung['aufschlag'] ); ?>;
    var gebindegroesse     = new Array();
    var preis              = new Array();
    var kosten             = new Array();
    var fest_alt           = new Array();   // festbestellmenge der gruppe bisher
    var fest               = new Array();   // festbestellmenge der gruppe aktuell
    var fest_andere        = new Array();   // festbestellmenge anderer gruppen
    var zuteilung_fest_alt = new Array();
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
      toleranz[produkt] = _toleranz;
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
        document.getElementById('g_'+produkt).className = 'mult highlight';
        document.getElementById('gg_'+produkt).firstChild.nodeValue = gebinde;
      } else {
        document.getElementById('gg_'+produkt).firstChild.nodeValue = '0';
        if( festmenge + toleranzmenge > 0 ) {
          document.getElementById('g_'+produkt).className = 'mult crit';
        } else {
          document.getElementById('g_'+produkt).className = 'mult';
        }
      }

      // anzeige gruppe aktualisieren:
      //
      s = fest[produkt] * verteilmult[produkt];
      if( toleranz[produkt] > 0 ) {
        s = s + ' ... ';
        document.getElementById('t_'+produkt).firstChild.nodeValue = ( fest[produkt] + toleranz[produkt] ) * verteilmult[produkt];
      } else {
        document.getElementById('t_'+produkt).firstChild.nodeValue = ' ';
      }
      document.getElementById('f_'+produkt).firstChild.nodeValue = s;

      document.getElementById('fest_'+produkt).value = fest[produkt];
      document.getElementById('toleranz_'+produkt).value = toleranz[produkt];

      <? if( ! hat_dienst(4) ) { ?>
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
      <? } ?>

      // kosten und neuen kontostand berechnen und anzeigen:
      //
      kosten_neu = preis[produkt] * ( fest[produkt] + toleranz[produkt] );
      gesamtpreis += ( kosten_neu - kosten[produkt] );
      kosten[produkt] = kosten_neu;
      if( ( fest[produkt] + toleranz[produkt] ) > 0 ) {
        document.getElementById('k_'+produkt).firstChild.nodeValue = kosten_neu.toFixed(2);
        // document.getElementById('m_'+produkt).firstChild.nodeValue = ( fest[produkt] + toleranz[produkt] );
        if( gebinde > 0 ) {
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

      if( gesamtpreis > kontostand ) {
        konto_rest.style.color = '#c00000';
        document.getElementById('submit').className = 'bigbutton warn';
        document.getElementById('submit').firstChild.nodeValue = 'Konto überzogen';
      } else {
        konto_rest.style.color = '#000000';
        document.getElementById('submit').style.color = '#000000';
        document.getElementById('submit').className = 'bigbutton';
        document.getElementById('submit').firstChild.nodeValue = 'Bestellung Speichern';
      }

      if( ! init ) {
        reminder_on();
      }

      return true;
    }

    function reminder_on() {
      reminder = document.getElementById('floating_submit_button_<? echo $bestellform_id; ?>');
      reminder.style.display = 'inline';

      id = document.getElementById('hinzufuegen');
      while( id.firstChild ) {
        id.removeChild( id.firstChild );
      }
      id.appendChild( document.createTextNode( 'Produkt hinzufuegen: bitte vorher erst Änderungen speichern!' ) );
      id.style.backgroundColor = '#ffffa0';
      id.className = 'inactive';
    }

    function fest_plus( produkt ) {
      fest[produkt]++;
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
        document.forms['form_<? echo $bestellform_id; ?>'].submit();
      }
    }
  </script>
  <?

  open_div( 'alert floatingbuttons', "id='floating_submit_button_$bestellform_id'" );
    open_table('layout');
        open_td('alert left');
          fc_link( 'self', array( 'class' => 'close'
           , 'url' => "javascript:document.getElementById('floating_submit_button_$bestellform_id').style.display = 'none';" 
          ) );
        open_td('alert center', "colspan='2'", "&Auml;nderungen sind noch nicht gespeichert!" );
      open_tr();
        open_td('alert smallskip');
      open_tr();
        open_td('alert', "colspan='2'", 'Gesamtpreis:' );
        open_td('alert right', "id='gesamtpreis1'", '-' );
      open_tr();
        open_td('alert', "colspan='2'", 'noch verf&uuml;gbar:' );
        open_td('alert right', "id='konto_rest'", sprintf( '%.2lf', $kontostand ) );
      open_tr();
        open_td('alert smallskip');
      open_tr();
        open_td('alert');
        open_td('center alert', '', "<a class='bigbutton' id='submit' href='javascript:bestellung_submit();'>Speichern</a>" );
        open_td('center alert', '', fc_link( 'self', 'bestell_id=0,class=bigbutton,text=Abbrechen' ) );
    close_table();
  close_div();

}

open_table( 'list hfill', "style='width:100%;'" );  // bestelltabelle
  ?> <!-- colgroup scheint bei firefox nicht die spur einer wirkung zu haben...
    <colgroup>
      <col width='2*'>
      <col width='3*'>
      <col width='1*'>
      <col width='3*'>
      <col width='3*'>
      <col width='3*'>
      <col width='1*'>
      <? if( hat_dienst(4) ) echo "<col width='1*'>"; ?>
    </colgroup>
    -->
  <?
  open_tr( 'groupofrows_top' );
    open_th( '', '', 'Produktgruppe' );
    open_th( '', '', 'Bezeichnung' );
    open_th( '', "colspan='1' title='Einzelpreis (mit Pfand, MWSt und ggf. Aufschlag)'", 'Preis' );
    open_th( '', "colspan='2' title='Bestellmenge deiner Gruppe'", 'deine Bestellmenge' );
    open_th( '', "title='maximale (bei voller Zuteilung) Kosten f&uuml;r deine Gruppe'", 'Kosten' );
    open_th( '', "title='Falls Produkt nicht kommt: automatisch vormerken für kommende Wochen'", "vormerken" );
    open_th( '', "colspan='1' title='Bestellungen aller Gruppen'", 'Gesamtbestellmenge' );
    if( hat_dienst(4) )
      open_th( '', '', 'Aktionen' );
    else
      open_th( '', "colspan='1' title='Zuteilung (nach aktuellem Stand) an deine Gruppe'", 'Zuteilung' );
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
    open_th( 'small', '', '(maximal)' );
    open_th( '', '', '' );
    open_th( '', "colspan='1' title='insgesamt gefuellte Gebinde'", 'volle Gebinde' );
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
  $preis = $produkt['endpreis'] + $produkt['nettopreis'] * $gesamtbestellung['aufschlag'] / 100.0;
  $lv_faktor = $produkt['lv_faktor'];

  $festmenge = sql_bestellung_produkt_gruppe_menge( $bestell_id, $produkt_id, $gruppen_id, BESTELLZUORDNUNG_ART_FESTBESTELLUNG );
  $toleranzmenge = sql_bestellung_produkt_gruppe_menge( $bestell_id, $produkt_id, $gruppen_id, BESTELLZUORDNUNG_ART_TOLERANZBESTELLUNG );
  // $vormerkung = sql_bestellung_produkt_gruppe_menge( $bestell_id, $produkt_id, $gruppen_id, BESTELLZUORDNUNG_ART_VORMERKUNG );

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
    open_div('oneline', '', $produkt['produkt_name']);
    open_div('oneline small', '', $produkt['notiz']);

  // preis:
  open_td('top center');
    open_table('layout');
      open_tr();
        if( hat_dienst(4) && ( sql_aktueller_produktpreis_id( $n, $gesamtbestellung['lieferung'] ) != $produkt['preis_id'] ) ) {
          open_td( 'mult outdated', "title='Preis nicht aktuell!'" );
        } else {
          open_td( 'mult' );
        }
        echo fc_link( 'produktdetails', array( 'produkt_id' => $n, 'bestell_id' => $bestell_id
                                          , 'text' => sprintf( '%.2lf', $preis ), 'class' => 'href' ) );
        open_td( 'unit', '', "/ {$produkt['verteileinheit']}" );

      open_tr();
      if( $lv_faktor != 1 ) {
        open_td( 'mult small', '', price_view( $preis * $produkt['lv_faktor'] ) );
        open_td( 'unit small', '', "/ {$produkt['liefereinheit']}" );
      } else {
        open_td( 'mult small', "colspan='2'", ' ' );
      }
    close_table('layout');

  // festmenge
  open_td( "center mult", "colspan='1' id='tf_$n' " );
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
        ?> <span class='flatbutton' onclick='fest_minus(<? echo $n; ?>);' >-</span>
            <span class='quad'>&nbsp;</span>
            <span class='flatbutton' onclick='fest_plus(<? echo $n; ?>);' >+</span> <?
        // if( $gebindegroesse > 1 )
        //  echo "<input type='button' value='++' onclick='fest_plusplus($n);' >";
        qquad();
      close_div();
    }

  // toleranzmenge
  open_td( "center unit", "colspan='1' id='tt_$n' " ); // toleranzwahl
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
          ?> <span class='flatbutton' onclick='toleranz_minus(<? echo $n; ?>);' >-</span>
             <span class='quad'>&nbsp;</span>
             <!-- <input type='button' value='G' onclick='toleranz_auffuellen(<? echo $n; ?>);' > -->
             <span class='flatbutton' onclick='toleranz_plus(<? echo $n; ?>);' >+</span> <?
        close_div();
      }
    } else {
      ?> - <?
    }


  open_td( "mult", "id='k_$n'", sprintf( '%.2lf', $kosten ) );

  open_td( 'center bottom' );
    $checked = ( $vormerkung > 0 ? 'checked' : '' );
    echo "<input type='checkbox' onclick='reminder_on();' name='vm_$n' value='yes' $checked>";
  close_td();

  // bestellungen aller gruppen:
  //
  // open_div( '', '', "f: $festmenge_gesamt; t: $toleranzmenge_gesamt" );
  open_td( "top center", "id='g_$n' " );
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
    open_td( 'center', "id='zt_$n'" );
      open_div( 'oneline center' );
        open_span( '', "id='z_$n'", '&nbsp;' ); // IE _braucht_ hier ein space!
        open_span( '', '', $produkt['kan_verteileinheit_anzeige'] );
      close_div();
    close_td();
  }
}


open_tr('summe');
  open_td( '', "colspan='5'", 'Gesamtpreis:' );
  open_td( 'number', "id='gesamtpreis2'", sprintf( '%.2lf', $gesamtpreis ) );
  open_td( '', "colspan='3'", '' );

close_table();

if( ! $readonly ) {
  close_form();

  open_div( 'middle', "id='hinzufuegen'" );
    open_fieldset( 'small_form', '', 'Zus&auml;tzlich Produkt in Bestellvorlage aufnehmen', 'off' );
      open_form( '', 'action=produkt_hinzufuegen' );
        select_products_not_in_list($bestell_id);
        submission_button( 'Produkt hinzuf&uuml;gen' );
        $anzahl_eintraege = sql_anzahl_katalogeintraege( $lieferanten_id );
        if( $anzahl_eintraege > 0 ) {
          div_msg( 'kommentar', "
            Ist ein gewünschter Artikel nicht in der Auswahlliste? 
            Im ". fc_link( 'katalog', "lieferanten_id=$lieferanten_id,text=Lieferantenkatalog,class=href" ) ."
            findest du $anzahl_eintraege Artikel; bitte wende dich an die Leute vom Dienst 4, wenn
            du einen davon in die Bestellvorlage aufnehmen lassen möchtest!
          " );
        }
      close_form();
    close_fieldset();
  close_div();

}

?>
