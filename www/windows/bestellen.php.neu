<?PHP
error_reporting('E_ALL'); 

assert( $angemeldet ) or exit();

setWikiHelpTopic( "foodsoft:bestellen" );

if( hat_dienst(4) ) {
  // auch dienst IV bestellt nur im STATUS_BESTELLEN (kann man ja zuruecksetzen!):
  // $status[] = STATUS_LIEFERANT;
  $gruppen_id = $basar_id;
  $kontostand = 100.0;
  echo "<h1>Bestellen f&uuml;r den Basar</h1>";
} else {
  // Neu: alle duerfen weiter bestellen, solange STATUS_BESTELLEN besteht:
  $gruppen_id = $login_gruppen_id;  // ...alle anderen fuer sich selbst!
  $kontostand = kontostand( $gruppen_id );
  echo "<h1>Bestellen f&uuml;r Gruppe $login_gruppen_name</h1>";
}

get_http_var('bestell_id','u',false,true );
if( $bestell_id ) {
  if( sql_bestellung_status( $bestell_id ) != STATUS_BESTELLEN )
    $bestell_id = 0;
}

$laufende_bestellungen = sql_bestellungen( STATUS_BESTELLEN );
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
    bestellung_overview( $gesamtbestellung, TRUE, $gruppen_id );
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

$lieferanten_id = sql_bestellung_lieferant_id( $bestell_id );

get_http_var( 'action', 'w', '' );
switch( $action ) {
  case 'produkt_hinzufuegen':
    need_http_var( 'produkt_id', 'U' );
    sql_insert_bestellvorschlag( $produkt_id, $bestell_id );
    break;
  case 'bestellen':
    $gesamtpreis = 0;
    $bestellungen = array();
    foreach( sql_bestellung_produkte( $bestell_id, 0, 0 ) as $produkt ) {
      $n = $produkt['produkt_id'];
      get_http_var( "fest_$n", 'u', 0 );
      $fest = ${"fest_$n"};
      get_http_var( "toleranz_$n", 'u', 0 );
      $toleranz = ${"toleranz_$n"};
      $bestellungen[$n] = array( 'fest' => $fest, 'toleranz' => $toleranz );
      $gesamtpreis += $produkt['preis'] * ( $fest + $toleranz );
    }
    need( $gesamtpreis <= $kontostand, "Konto &uuml;berzogen!" );
    foreach( $bestellungen as $produkt_id => $m ) {
      change_bestellmengen( $gruppen_id, $bestell_id, $produkt_id, $m['fest'], $m['toleranz'] );
    }
    logger( "Bestellung speichern: $bestell_id" );
    open_javascript( "alert( 'Bestellung wurde eingetragen!' );" );
    break;
  case 'delete':
    need_http_var( 'produkt_id', 'U' );
    sql_delete_bestellvorschlag( $produkt_id, $bestell_id );
    break;
}

$produkte = sql_bestellung_produkte( $bestell_id, 0, 0, 'produktgruppen_name,produkt_name' );
$anzahl_produkte = count( $produkte );
// ^ brauchen wir gleich im java-script!
$gesamtpreis = 0.0;

if( ! $readonly ) {
  $bestellform_id = open_form( '', 'action=bestellen' );

  ?>
  <script type="text/javascript">
    var anzahl_produkte = <? echo $anzahl_produkte; ?>;
    var kontostand = <? printf( "%.2lf", $kontostand ); ?>;
    var gesamtpreis = 0.00;
    var gebindegroesse     = new Array();
    var preis              = new Array();
    var kosten             = new Array();
    var fest_alt           = new Array();   // festbestellmenge der gruppe bisher
    var fest               = new Array();   // festbestellmenge der gruppe aktuell
    var fest_andere        = new Array();   // festbestellmenge anderer gruppen
    var zuteilung_fest_alt = new Array();
    var toleranz           = new Array();
    var toleranz_andere    = new Array();

    function init_produkt( produkt, _gebindegroesse, _preis, _fest, _toleranz, _fest_andere, _toleranz_andere, zuteilung_fest, zuteilung_toleranz ) {
      gebindegroesse[produkt] = _gebindegroesse;
      preis[produkt] = _preis;
      fest_alt[produkt] = _fest;
      fest[produkt] = fest_alt[produkt];
      fest_andere[produkt] = _fest_andere;
      zuteilung_fest_alt[produkt] = zuteilung_fest;
      toleranz[produkt] = _toleranz;
      toleranz_andere[produkt] = _toleranz_andere;
      kosten[produkt] = _preis * ( _fest + _toleranz );
      gesamtpreis += kosten[produkt];
    }

    function zuteilung_berechnen( produkt ) {
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

      // falls noch toleranz beruechsichtigt wird: moeglichst gleichmaessig nach quote verteilen:
      //
      if( restmenge > 0 ) {
        quote = restmenge / ( toleranz_andere[produkt] + toleranz[produkt] );
        menge = Math.min( Math.ceil( toleranz[produkt] * quote ), restmenge );
        zuteilung_toleranz = menge;
      } else {
        zuteilung_toleranz = 0;
      }
  
      // anzeige aktualisieren:
      //
      document.getElementById('fz_'+produkt).firstChild.nodeValue = zuteilung_fest;
      document.getElementById('fr_'+produkt).firstChild.nodeValue = fest[produkt] - zuteilung_fest;
      document.getElementById('fg_'+produkt).firstChild.nodeValue = fest[produkt] + fest_andere[produkt];
      if( gebindegroesse[produkt] > 1 ) {
        document.getElementById('tz_'+produkt).firstChild.nodeValue = zuteilung_toleranz;
        document.getElementById('tr_'+produkt).firstChild.nodeValue = toleranz[produkt] - zuteilung_toleranz;
        document.getElementById('tg_'+produkt).firstChild.nodeValue = toleranz[produkt] + toleranz_andere[produkt];
      }
  
      if( gebinde > 0 ) {
        document.getElementById('gv_'+produkt).firstChild.nodeValue = gebinde;
        document.getElementById('gv_'+produkt).className = 'mult highlight';
      } else {
        document.getElementById('gv_'+produkt).firstChild.nodeValue = '0';
        if( festmenge + toleranzmenge > 0 ) {
          document.getElementById('gv_'+produkt).className = 'mult crit';
        } else {
          document.getElementById('gv_'+produkt).className = 'mult';
        }
      }
  
      // formularfelder aktualisieren:
      //
      document.getElementById('fest_'+produkt).value = fest[produkt];
      document.getElementById('toleranz_'+produkt).value = toleranz[produkt];
  
      // kosten und neuen kontostand berechnen und anzeigen:
      kosten_neu = preis[produkt] * ( fest[produkt] + toleranz[produkt] );
      gesamtpreis += ( kosten_neu - kosten[produkt] );
      kosten[produkt] = kosten_neu;
      if( ( fest[produkt] + toleranz[produkt] ) > 0 ) {
        document.getElementById('k_'+produkt).firstChild.nodeValue = kosten_neu.toFixed(2);
        document.getElementById('m_'+produkt).firstChild.nodeValue = ( fest[produkt] + toleranz[produkt] );
        if( gebinde > 0 ) {
          document.getElementById('k_'+produkt).className = 'mult highlight';
          document.getElementById('m_'+produkt).className = 'mult highlight';
        } else {
          document.getElementById('k_'+produkt).className = 'mult crit';
          document.getElementById('m_'+produkt).className = 'mult crit';
        }
      } else {
        document.getElementById('k_'+produkt).firstChild.nodeValue = '0.00'
        document.getElementById('k_'+produkt).className = 'mult';
        document.getElementById('m_'+produkt).firstChild.nodeValue = '0';
        document.getElementById('m_'+produkt).className = 'mult';
      }
  
      document.getElementById('gesamtpreis1').firstChild.nodeValue = gesamtpreis.toFixed(2);
      document.getElementById('gesamtpreis2').firstChild.nodeValue = gesamtpreis.toFixed(2);
      kontostand_neu = ( kontostand - gesamtpreis ).toFixed(2);
      konto_rest = document.getElementById('konto_rest');
      konto_rest.firstChild.nodeValue = kontostand_neu;

      reminder = document.getElementById('floating_submit_button_<? echo $bestellform_id; ?>');
      reminder.style.display = 'inline';

      id = document.getElementById('hinzufuegen');
      while( id.firstChild ) {
        id.removeChild( id.firstChild );
      }
      id.appendChild( document.createTextNode( 'Vor dem Hinzufügen: bitte erst Änderungen speichern!' ) );
      id.style.backgroundColor = '#ffffa0';

      if( gesamtpreis > kontostand ) {
        konto_rest.style.color = '#c00000';
        document.getElementById('submit').className = 'bigbutton warn';
        document.getElementById('submit').value = 'Konto überzogen';
      } else {
        konto_rest.style.color = '#000000';
        document.getElementById('submit').style.color = '#000000;'
        document.getElementById('submit').className = 'bigbutton';
        document.getElementById('submit').value = 'Bestellung Speichern';
      }

      return true;
    }

    function fest_plus( produkt ) {
      fest[produkt]++;
      zuteilung_berechnen( produkt );
    }
    function fest_plusplus( produkt ) {
      var gebinde;
      gebinde = Math.floor( fest[produkt] / gebindegroesse[produkt] );
      fest[produkt] = (gebinde+1) * gebindegroesse[produkt];
      zuteilung_berechnen( produkt );
    }
    function fest_minus( produkt ) {
      if( fest[produkt] > 0 ) {
        fest[produkt]--;
        zuteilung_berechnen( produkt );
      }
    }
    function fest_minusminus( produkt ) {
      var gebinde;
      gebinde = Math.ceil( fest[produkt] / gebindegroesse[produkt] ) - 1;
      if( gebinde > 0 ) {
        fest[produkt] = gebinde * gebindegroesse[produkt];
        zuteilung_berechnen( produkt );
      } else {
        fest[produkt] = 0;
        zuteilung_berechnen( produkt );
      }
    }
    function toleranz_plus( produkt ) {
      if( toleranz[produkt] < gebindegroesse[produkt]-1 ) {
        toleranz[produkt]++;
        zuteilung_berechnen( produkt );
      }
    }
    function toleranz_minus( produkt ) {
      if( toleranz[produkt] > 0 ) {
        toleranz[produkt]--;
        zuteilung_berechnen( produkt );
      }
    }
    function toleranz_auffuellen( produkt ) {
      gebinde = Math.floor( fest[produkt] / gebindegroesse[produkt] );
      if( fest[produkt] - gebinde * gebindegroesse[produkt] > 0 ) {
        toleranz[produkt] = (gebinde+1) * gebindegroesse[produkt] - fest[produkt];
      } else {
        toleranz[produkt] = 0;
      }
      zuteilung_berechnen( produkt );
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
        open_td('center alert', '', "<a class='bigbutton' href='javascript:bestellung_submit();'>Speichern</a>" );
        open_td('center alert', '', fc_link( 'self', 'bestell_id=0,class=bigbutton,text=Abbrechen' ) );
    close_table();
  close_div();

}

open_table( 'list hfill' );  // bestelltabelle
  open_th( '', '', 'Produktgruppe' );
  open_th( '', '', 'Bezeichnung' );
  open_th( '', "colspan='4' title='Anzahl voller Gebinde und Gebindegröße'", 'Gebinde' );
  open_th( '', "colspan='2' title='Einzelpreis (mit Pfand und MWSt)'", 'Preis' );
  open_th( '', "colspan='2' title='von Eurer Gruppe maximal bestellte Menge'", 'Menge' );
  open_th( '', "title='Fest-Bestellmenge: wieviel Ihr wirklich haben wollt'", 'fest' );
  open_th( '', "title='Toleranz-Menge: wieviel Ihr auch mehr nehmen würdet'", 'Toleranz' );
  open_th( '', "title='voraussichtliche maximale Kosten f&uuml;r eure Gruppe (mit Pfand und MWSt)'", 'Kosten' );
  if( $dienst == 4 )
    open_th( '', '', 'Aktionen' );

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
  $preis = $produkt['preis'];
  $lv_faktor = $produkt['lv_faktor'];

  $festmenge = sql_bestellung_produkt_gruppe_menge( $bestell_id, $produkt_id, $gruppen_id, 0 );
  $toleranzmenge = sql_bestellung_produkt_gruppe_menge( $bestell_id, $produkt_id, $gruppen_id, 1 );

  $toleranzmenge_gesamt = $produkt['toleranzbestellmenge'] + $produkt['basarbestellmenge'];
  $toleranzmenge_andere = $toleranzmenge_gesamt - $toleranzmenge;

  $festmenge_gesamt = $produkt['gesamtbestellmenge'] - $toleranzmenge_gesamt;
  $festmenge_andere = $festmenge_gesamt - $festmenge;

  $zuteilungen = zuteilungen_berechnen( $produkt );
  $zuteilung_fest = adefault( $zuteilungen['festzuteilungen'], $gruppen_id, 0 );
  $zuteilung_toleranz = adefault( $zuteilungen['toleranzzuteilungen'], $gruppen_id, 0 );

  $kosten = $preis * ( $festmenge + $toleranzmenge );
  $gesamtpreis += $kosten;

  $aktueller_preis_id = sql_aktueller_produktpreis_id( $n, $gesamtbestellung['lieferung'] );

  $js .= sprintf( "init_produkt( %u, %u, %.2lf, %u, %u, %u, %u, %u, %u );\n"
  , $n, $gebindegroesse , $preis
  , $festmenge, $toleranzmenge
  , $festmenge_andere, $toleranzmenge_andere
  , $zuteilung_fest, $zuteilung_toleranz
  );
  $produktgruppe = $produkt['produktgruppen_id'];
  if( $produktgruppe != $produktgruppe_alt ) {
    if( $activate_mozilla_kludges ) {
      // mozilla can't handle rowspan in complex tables on first pass (grid lines get lost),
      // so we set rowspan=1 first and modify later :-/
      open_td( '', "rowspan='1' id='pg_$produktgruppe'", $produkt['produktgruppen_name'] );
      $js .= "document.getElementById('pg_$produktgruppe').rowSpan = {$produktgruppen_zahl[$produktgruppe]}; ";
    } else {
      // other browsers get it right the first time, as it should be:
      open_td( '', "rowSpan='{$produktgruppen_zahl[$produktgruppe]}'", $produkt['produktgruppen_name'] );
    }
    $produktgruppe_alt = $produktgruppe;
    // $js .= "document.getElementById('pg_$produktgruppe').rowSpan = 1; ";
  } else {
    // open_td( '', '', ' ' );
  }

  hidden_input( "fest_$n", "$festmenge", "id='fest_$n'" );
  hidden_input( "toleranz_$n", "$toleranzmenge", "id='toleranz_$n'" );

  open_td();
    open_div('oneline', '', $produkt['produkt_name']);
    open_div('oneline small', '', $produkt['notiz']);

  open_td( 'mult ' . ( ( $zuteilungen[gebinde] > 0 )  ?  'highlight'
                          : ( ( $festmenge_gesamt + $toleranzmenge_gesamt > 0 ) ? 'crit' : '' ) )
          , "style='width:2ex;border-right:none;' id='gv_$n'"
          , $zuteilungen[gebinde]
  );

  open_td( '', "style='width:1ex;border-right:none;border-left:none;padding-left:0pt;padding-right:0pt;'", '*' );
  open_td( 'mult', "style='border-left:none;width:5ex;'", $gebindegroesse );
  open_td( 'unit', '', "{$produkt['kan_verteilmult']} {$produkt['kan_verteileinheit']}" );

  if( ( $dienst == 4 ) and ( $aktueller_preis_id != $produkt['preis_id'] ) ) {
    open_td( 'mult outdated', "title='Preis nicht aktuell!'" );
  } else {
    open_td( 'mult' );
  }
    echo fc_link( 'produktdetails', array( 'produkt_id' => $n, 'bestell_id' => $bestell_id
                                      , 'text' => sprintf( '%.2lf', $preis ), 'class' => 'href' ) );
    if( $lv_faktor != 1 )
      open_div( 'small right', '', price_view( $preis * $produkt['lv_faktor'] ) );

  open_td( 'unit' );
    printf( "/ %s %s", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] );
    if( $lv_faktor != 1 )
      open_div( 'small left', '', "/ {$produkt['liefereinheit']}" );

  $tag = '';
  if( $festmenge + $toleranzmenge > 0 )
    $tag = ( ( $zuteilungen[gebinde] > 0 ) ? 'highlight' : 'crit' );
  open_td( "mult $tag", "id='m_$n'", sprintf( "%u", $festmenge + $toleranzmenge ) );
  open_td( 'unit', '', sprintf( "* %s %s", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ) );

  open_td('center');  // festmengenwahl
    open_div('oneline', '', "
      <span style='color:#00e000;font-weight:bold;' id='fz_$n'>$zuteilung_fest</span>
      +
      <span style='color:#e80000;font-weight:bold;' id='fr_$n'>".($festmenge - $zuteilung_fest)."</span>
      /
      <span style='color:#000000;' id='fg_$n'>$festmenge_gesamt</span>
    " );
    if( ! $readonly ) {
      open_div('oneline');
        // if( $gebindegroesse > 1 )
        //  echo "<input type='button' value='<<' onclick='fest_minusminus($n);' >";
        ?> <input type='button' value='<' onclick='fest_minus(<? echo $n; ?>);' >
            <span style='width:4em;'>&nbsp;</span>
            <input type='button' value='>' onclick='fest_plus(<? echo $n; ?>);' > <?
        // if( $gebindegroesse > 1 )
        //  echo "<input type='button' value='>>' onclick='fest_plusplus($n);' >";
      close_div();
    }

  open_td('center'); // toleranzwahl
    if( $gebindegroesse > 1 ) {
      open_div( 'oneline', '', "
        <span style='color:#00e000;font-weight:bold;' id='tz_$n'>$zuteilung_toleranz</span>
        +
        <span style='color:#e80000;font-weight:bold;' id='tr_$n'>".($toleranzmenge - $zuteilung_toleranz)."</span>
        /
        <span style='color:#000000;' id='tg_$n'>$toleranzmenge_gesamt</span>
      " );
      if( ! $readonly ) {
        open_div('oneline');
          ?> <input type='button' value='<' onclick='toleranz_minus(<? echo $n; ?>);' >
             <span style='width:2em;'>&nbsp;</span>
             <!-- <input type='button' value='G' onclick='toleranz_auffuellen(<? echo $n; ?>);' > -->
             <span style='width:2em;'>&nbsp;</span>
             <input type='button' value='>' onclick='toleranz_plus(<? echo $n; ?>);' > <?
        close_div();
      }
    } else {
      ?> - <?
    }


  open_td( "mult $tag", "id='k_$n'", sprintf( '%.2lf', $kosten ) );

  if( $dienst == 4 ) {
    open_td();
      echo fc_link( 'edit_produkt', "produkt_id=$produkt_id" );
      echo fc_action( array( 'class' => 'drop', 'text' => '', 'title' => 'Bestellvorschlag löschen'
                           , 'confirm' => 'Bestellvorschlag wirklich löschen?')
                    , array( 'action' => 'delete', 'produkt_id' => $produkt_id ) );
  }
}


open_tr('summe');
  open_td( '', "colspan='12'", 'Gesamtpreis:' );
  open_td( 'number', "id='gesamtpreis2'", sprintf( '%.2lf', $gesamtpreis ) );

  if( $dienst == 4 ) open_td();
close_table();

if( $js )
  open_javascript( $js );

if( ! $readonly ) {
  close_form();

  open_div( 'middle', "id='hinzufuegen'" );
    open_fieldset( 'small_form', '', 'Zus&auml;tzlich Produkt in Bestellvorlage aufnehmen', 'off' );
      open_form( '', 'action=produkt_hinzufuegen' );
        div_msg( 'warn medskip', "
          Achtung: diese Funktion fügt ein weiteres Produkt in die Liste oben ein, damit du oder
          andere es diese Woche bestellen <em><span style='text-decoration:underline;'>können</span></em>.
        " );
        div_msg( 'warn smallskip', "
          Eine <span style='text-decoration:underline;'><em>Bestellung</em></span> ist damit nicht
          verbunden, das geht <em><span style='text-decoration:underline;'>nur<span></em>
          mit den Knöpfen <span class='button'>&lt;</span> und <span class='button'>&gt;</span> oben im Formular!
        " );
        medskip();
        select_products_not_in_list($bestell_id);
        submission_button( 'Produkt hinzuf&uuml;gen' );
        $anzahl_eintraege = sql_anzahl_katalogeintraege( $lieferanten_id );
        if( $anzahl_eintraege > 0 ) {
          div_msg( 'kommentar', "
            Ist Dein gewünschter Artikel nicht in der Auswahlliste? 
            Im ". fc_link( 'katalog', "lieferanten_id=$lieferanten_id,text=Lieferantenkatalog,class=href" ) ."
            findest Du $anzahl_eintraege Artikel; bitte wende Dich an die Leute vom Dienst 4, wenn
            Du einen davon in die Bestellvorlage aufnehmen lassen möchtest!
          " );
        }
      close_form();
    close_fieldset();
  close_div();

}

?>
