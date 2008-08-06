<?PHP
error_reporting('E_NONE'); 

assert( $angemeldet ) or exit();  // aufruf sollte nur noch per index.php?area=bestellen erfolgen

setWikiHelpTopic( "foodsoft:bestellen" );

if( $hat_dienst_IV ) {
  // auch dienst_IV bestellt nur im STATUS_BESTELLEN (kann man ja zuruecksetzen!):
  // $status[] = STATUS_LIEFERANT;
  $useDate = FALSE;
  $gruppen_id = sql_basar_id();                 // dienst IV bestellt fuer basar...
  $kontostand = 100.0;
  echo "<h1>Bestellen f&uuml;r den Basar</h1>";
} else {
  // Neu: alle duerfen weiter bestellen, solange STATUS_BESTELLEN besteht:
  $useDate = FALSE;
  $gruppen_id = $login_gruppen_id;  // ...alle anderen fuer sich selbst!
  $kontostand = kontostand( $gruppen_id );
  echo "<h1>Bestellen f&uuml;r Gruppe $login_gruppen_name</h1>";
}

get_http_var('bestell_id','u',false,true );
if( $bestell_id ) {
  if( getState( $bestell_id ) != STATUS_BESTELLEN )
    $bestell_id = NULL;
}

$laufende_bestellungen = sql_bestellungen( STATUS_BESTELLEN, $useDate );
if (mysql_num_rows($laufende_bestellungen) < 1) {
  ?>
    <div class='warn'>
      Zur Zeit laufen leider keine Bestellungen!
      <a href='index.php'>Zurück...</a>
    </div>
  <?
  return;
}

// tabelle fuer infos und auswahl bestellungen:
//
?> <table width='100%' class='layout'><tr> <?

if( $bestell_id ) {
  $gesamtbestellung = sql_bestellung( $bestell_id );
  ?> <td style='text-align:left;padding-bottom:1em;'> <?
  bestellung_overview( $gesamtbestellung, TRUE, $gruppen_id );
  ?> </td> <?
}

?>
  <td style='text-align:left;padding:1ex 1em 2em 3em;'>
  <h4> Zur Zeit laufende Bestellungen: </h4>
  <table style="width:600px;" class="liste">
    <tr>
      <th>Name</th>
      <th>Lieferant</th>
      <th>Beginn</th>
      <th>Ende</th>
      <th>Produkte</th>
    </tr>
<?
while( $row = mysql_fetch_array($laufende_bestellungen) ) {
  $id = $row['id'];
  //jetzt die anzahl der produkte bestimmen ...
  $num = sql_select_single_field(
    "SELECT COUNT(*) as num FROM bestellvorschlaege WHERE gesamtbestellung_id=$id", 'num'
  );
  if( $id != $bestell_id ) {
    ?>
      <tr>
        <td><? echo fc_alink( 'bestellen', array( 'bestell_id' => $id, 'text' => $row['name'] ) ); ?></td>
    <?
  } else {
    ?>
      <tr class='active'>
        <td style='font-weight:bold;'><? echo $row['name']; ?></td>
    <?
  }
  ?>
    <td><? echo lieferant_name($row['lieferanten_id']); ?></td>
    <td><? echo $row['bestellstart']; ?></td>
    <td <? if( $row['bestellende'] < $mysqljetzt ) echo "style='font-weight:bold;'"; ?>
       ><? echo $row['bestellende']; ?></td>
    <td><? echo $num; ?></td>
    </tr>
  <?
}
?>
      </table>
    </td>
  </tr>
  </table>
<?

if( ! $bestell_id )
  return;

///////////////////////////////////////////
// ab hier: eigentliches bestellformular:
//

$lieferanten_id = getProduzentBestellID( $bestell_id );

get_http_var( 'action', 'w', '' );

switch( $action ) {
  case 'produkt_hinzufuegen':
    need_http_var( 'produkt_id', 'U' );
    sql_insert_bestellvorschlaege( $produkt_id, $bestell_id );
    break;
  case 'bestellen':
    $produkte = sql_bestellprodukte( $bestell_id, 0, 0 );
    $gesamtpreis = 0;
    $bestellungen = array();
    while( $produkt = mysql_fetch_array( $produkte ) ) {
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
    break;
  case 'delete':
    need_http_var( 'produkt_id', 'U' );
    sql_delete_bestellvorschlag( $produkt_id, $bestell_id );
    break;
}


$produkte = sql_bestellprodukte( $bestell_id, 0, 0, 'produktgruppen_name,produkt_name' );
// ^ brauchen wir gleich im java-script!
$anzahl_produkte = mysql_num_rows( $produkte );
$gesamtpreis = 0.0;

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
      document.getElementById('gv_'+produkt).className = 'mult_highlight';
    } else {
      document.getElementById('gv_'+produkt).firstChild.nodeValue = '0';
      if( festmenge + toleranzmenge > 0 ) {
        document.getElementById('gv_'+produkt).className = 'mult_crit';
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
        document.getElementById('k_'+produkt).className = 'mult_highlight';
        document.getElementById('m_'+produkt).className = 'mult_highlight';
      } else {
        document.getElementById('k_'+produkt).className = 'mult_crit';
        document.getElementById('m_'+produkt).className = 'mult_crit';
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

    reminder = document.getElementById('reminder');
    reminder.style.display = 'inline';

    id = document.getElementById('hinzufuegen');
    while( id.firstChild ) {
      id.removeChild( id.firstChild );
    }
    id.appendChild( document.createTextNode( 'Vor dem Hinzufügen: bitte erst Änderungen speichern!' ) );
    id.style.backgroundColor = '#ffffa0';

    if( gesamtpreis > kontostand ) {
      konto_rest.style.color = '#c00000';
      document.getElementById('submit').className = 'bigbutton_warn';
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
      document.forms['bestellform'].submit();
    }
  }
</script>

<? if( ! $readonly ) { ?>
  <div style='position:fixed;top:20px;left:20px;padding:2ex;z-index:999;display:none;' id='reminder' class='alert'>
    <table class='inner'>
      <tr>
        <td>
          <img class='button' src='img/close_black_trans.gif' onClick='document.getElementById("reminder").style.display = "none";'>
        </td>
        <td style='text-align:center;' colspan='2'>Änderungen sind noch nicht gespeichert!</td>
      </tr>
      <tr><td style='padding:0.5ex;' colspan='3'></td></tr>
      <tr>
        <td class='alert' style='padding-left:1em;' colspan='2'>Gesamtpreis:</td>
        <td class='alert' id='gesamtpreis1' style='text-align:right;padding-right:1em;'>-</td>
      </tr>
      <tr>
        <td class='alert' style='padding-left:1em;' colspan='2'>noch verf&uuml;gbar:</td>
        <td class='alert' id='konto_rest' style='text-align:right;padding-right:1em;'><? printf( '%.2lf', $kontostand ); ?></td>
      </tr>
      <tr><td style='padding:0.5ex;' colspan='3'></td></tr>
      <tr>
        <td></td>
        <td style='text-align:center;'>
          <input id='submit' type='button' class='bigbutton' value='Bestellung speichern' onClick='bestellung_submit();'>
        </td>
        <td style='text-align:center;'>
          <? echo fc_button( 'self', 'text=Abbrechen' ); ?>
        </td>
      </tr>
    </table>
  </div>
<? } ?>

<form name="bestellform" action="<? echo self_url(); ?>" method="post">
  <? echo self_post(); ?>
  <input type="hidden" name="action" value='bestellen'>
  <table class='numbers' width='100%' style="margin:40px 0 0 0;">
    <tr>
      <th>Produktgruppe</th>
      <th>Bezeichnung</th>
      <th colspan='4'>Gebinde</th>
      <th colspan='2' title='Einzelpreis (mit Pfand und MWSt')>Preis</th>
      <th colspan='2'>Menge</th>
      <th>fest</th>
      <th>Toleranz</th>
      <th title='voraussichtliche Kosten (mit Pfand und MWSt)'>Kosten</th>
      <? if( $dienst == 4 ) { ?>
        <th>Aktionen</th>
      <? } ?>
    </tr>
<?

$produktgruppe_alt = '';
$rowspan = 1;
$pg_id = 0;
$js = '';
while( $produkt = mysql_fetch_array( $produkte ) ) {
  preisdatenSetzen( $produkt );
  $produkt_id = $produkt['produkt_id'];
  $n = $produkt_id;

  $gebindegroesse = $produkt['gebindegroesse'];
  $preis = $produkt['preis'];

  $festmenge = gruppengesamtmenge( $bestell_id, $produkt_id, $gruppen_id, 0 );
  $toleranzmenge = gruppengesamtmenge( $bestell_id, $produkt_id, $gruppen_id, 1 );

  $toleranzmenge_gesamt = $produkt['toleranzbestellmenge'] + $produkt['basarbestellmenge'];
  $toleranzmenge_andere = $toleranzmenge_gesamt - $toleranzmenge;

  $festmenge_gesamt = $produkt['gesamtbestellmenge'] - $toleranzmenge_gesamt;
  $festmenge_andere = $festmenge_gesamt - $festmenge;

  $zuteilungen = zuteilungen_berechnen( $produkt );
  $zuteilung_fest = adefault( $zuteilungen['festzuteilungen'], $gruppen_id, 0 );
  $zuteilung_toleranz = adefault( $zuteilungen['toleranzzuteilungen'], $gruppen_id, 0 );

  $kosten = $preis * ( $festmenge + $toleranzmenge );
  $gesamtpreis += $kosten;

  $js .= sprintf( "init_produkt( %u, %u, %.2lf, %u, %u, %u, %u, %u, %u );\n"
  , $n, $gebindegroesse , $preis
  , $festmenge, $toleranzmenge
  , $festmenge_andere, $toleranzmenge_andere
  , $zuteilung_fest, $zuteilung_toleranz
  );
  ?> <tr> <?
  $produktgruppe = $produkt['produktgruppen_name'];
  if( $produktgruppe != $produktgruppe_alt ) {
    if( $pg_id )
      $js .= sprintf( "document.getElementById('pg_%u').rowSpan = %u;\n", $pg_id, $rowspan );
    ++$pg_id;
    ?>
      <td id='pg_<? echo $pg_id; ?>' rowspan='1'>
        <? echo $produktgruppe; ?>
      </td>
    <?
    $rowspan = 1;
    $produktgruppe_alt = $produktgruppe;
  } else {
    ++$rowspan;
  }

  ?>
    <input type='hidden' name='fest_<? echo $n; ?>' id='fest_<? echo $n; ?>' value='<? echo $festmenge; ?>'>
    <input type='hidden' name='toleranz_<? echo $n; ?>'  id='toleranz_<? echo $n; ?>' value='<? echo $toleranzmenge; ?>'>
    <td><? printf( "<div class='oneline'>%s</div><div class='oneline_small'>%s</div>", $produkt['produkt_name'], $produkt['notiz'] ); ?></td>
    <td
      <? if( $zuteilungen[gebinde] > 0 ) { ?>
        class='mult_highlight'
      <? } else { ?>
        <? if( $festmenge_gesamt + $toleranzmenge_gesamt > 0 ) { ?>
         class='mult_crit'
        <? } else { ?>
         class='mult'
        <? } ?>
      <? } ?>
         style='width:2ex;border-right:none;' id='gv_<? echo $n; ?>'><? printf( "%s", $zuteilungen[gebinde] ); ?>
    </td>
    <td style='width:1ex;border-right:none;border-left:none;'>*</td>
    <td class='mult' style='border-left:none;width:6ex;'><? printf( "(%s *", $gebindegroesse ); ?></td>
    <td class='unit'><? printf( "%s %s)", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ); ?></td>
      <?
        $class = 'mult';
        if( $dienst == 4 ) {
          $aktueller_preis_id = sql_aktueller_produktpreis_id( $n, $gesamtbestellung['lieferung'] );
          if( $aktueller_preis_id != $produkt['preis_id'] )
            echo "<td class='mult_outdated' title='Preis nicht aktuell!'>";
          else
            echo "<td class='mult'>";
        } else {
          echo "<td class='mult'>";
        }
        $s = sprintf( "%.2lf", $preis );
        echo fc_alink( 'produktpreise', "produkt_id=$n,bestell_id=$bestell_id,img=,text=$s" );
      ?>
    </td>
    <td class='unit'><? printf( "/ %s %s", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ); ?></td>
    <td
      <? if( $festmenge + $toleranzmenge > 0 ) { ?>
        <? if( $zuteilungen[gebinde] > 0 ) { ?>
          class='mult_highlight' 
        <? } else { ?>
          class='mult_crit'
        <? } ?>
      <? } else { ?>
          class='mult'
      <? } ?>
          id='m_<? echo $n; ?>'><? printf( "%u", $festmenge + $toleranzmenge ); ?>
    </td>
    <td class='unit'><? printf( "* %s %s", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ); ?></td>
    <td style='text-align:center;'>
      <div class='oneline'>
        <span style='color:#00e000;font-weight:bold;' id='fz_<? echo $n; ?>'><? echo $zuteilung_fest; ?></span>
        +
        <span style='color:#e80000;font-weight:bold;' id='fr_<? echo $n; ?>'><? echo $festmenge - $zuteilung_fest; ?></span>
        /
        <span style='color:#000000;' id='fg_<? echo $n; ?>'><? echo $festmenge_gesamt; ?></span>
      </div>
      <? if( ! $readonly ) { ?>
        <div class='oneline'>
        <? if( $gebindegroesse > 1 ) { ?>
          <input type='button' value='<<' onclick='fest_minusminus(<? echo $n; ?>);' >
        <? } ?>
          <input type='button' value='<' onclick='fest_minus(<? echo $n; ?>);' >
          <span style='width:4em;'>&nbsp;</span>
          <input type='button' value='>' onclick='fest_plus(<? echo $n; ?>);' >
        <? if( $gebindegroesse > 1 ) { ?>
          <input type='button' value='>>' onclick='fest_plusplus(<? echo $n; ?>);' >
        <? } ?>
        </div>
      <? } ?>
    </td>
    <td style='text-align:center;'>
      <? if( $gebindegroesse > 1 ) { ?>
        <div class='oneline'>
          <span style='color:#00e000;font-weight:bold;' id='tz_<? echo $n; ?>'><? echo $zuteilung_toleranz; ?></span>
          +
          <span style='color:#e80000;font-weight:bold;width:2ex;' id='tr_<? echo $n; ?>'><? echo $toleranzmenge - $zuteilung_toleranz; ?></span>
          /
          <span style='color:#000000;width:4ex;' id='tg_<? echo $n; ?>'><? echo $toleranzmenge_gesamt; ?></span>
        </div>
        <? if( ! $readonly ) { ?>
          <div class='oneline'>
          <input type='button' value='<' onclick='toleranz_minus(<? echo $n; ?>);' >
          <span style='width:2em;'>&nbsp;</span>
          <!-- <input type='button' value='G' onclick='toleranz_auffuellen(<? echo $n; ?>);' > -->
          <span style='width:2em;'>&nbsp;</span>
          <input type='button' value='>' onclick='toleranz_plus(<? echo $n; ?>);' >
          </div>
        <? } ?>
      <? } else { ?>
        <div class='oneline' style='text-align:center;'> - </div>
      <? } ?>
    </td>
    <td
      <? if( $festmenge + $toleranzmenge > 0 ) { ?>
        <? if( $zuteilungen[gebinde] > 0 ) { ?>
          class='mult_highlight'
        <? } else { ?>
          class='mult_crit'
        <? } ?>
      <? } else { ?>
          class='mult'
      <? } ?>
          id='k_<? echo $n; ?>'><? printf( "%.2lf", $kosten ); ?>
    </td>
    <? if( $dienst == 4 ) { ?>
      <td>
        <?
          echo fc_alink( 'edit_produkt', "produkt_id=$produkt_id" );
          echo fc_action( array( 'action' => 'delete', 'produkt_id' => $produkt_id, 'img' => 'img/b_drop.png', 'text' => ''
                                 , 'title' => 'Bestellvorschlag löschen', 'confirm' => 'Bestellvorschlag wirklich löschen?' ) );
        ?>
      </td>
    <? } ?>
  </tr>
  <?
}
if( $rowspan > 1 )
  $js .= sprintf( "document.getElementById('pg_%u').rowSpan = %u;\n", $pg_id, $rowspan );

if( $js ) {
  ?>
    <script type='text/javascript'>
      <? echo $js; ?>
    </script>
  <?
}

?>
  <tr class='summe'>
    <td colspan='12'>Gesamtpreis:</td>
    <td class='number' id='gesamtpreis2'><? printf( "%.2lf", $gesamtpreis ); ?></td>
    <? if( $dienst == 4 ) { ?>
      <td></td>
    <? } ?>
  </tr>
  </table>
  </form>

<? if( ! $readonly ) { ?>
  <h3> Zus&auml;tzlich Produkt in Bestellvorlage aufnehmen </h3>
  <div id='hinzufuegen' style='height:3em;vertical-align:middle;'>
    <form method='post' action='<? echo self_url(); ?>'>
    <input type='hidden' name='action' value='produkt_hinzufuegen'>
    <? echo self_post(); ?>
      <? select_products_not_in_list($bestell_id); ?>
      <input type="submit" value="Produkt hinzuf&uuml;gen">
    </form>
  </div>
  <?
    $anzahl_eintraege = sql_anzahl_katalogeintraege( $lieferanten_id );
    if( $anzahl_eintraege > 0 ) {
      ?>
        <div class='kommentar'>
          Ist Dein gewünschter Artikel nicht in der Auswahlliste? 
          Im <? echo fc_alink( 'katalog', "lieferanten_id=$lieferanten_id,text=Lieferantenkatalog,img=" ); ?>
          findest Du <? echo $anzahl_eintraege; ?> Artikel; bitte wende Dich an die Leute vom Dienst 4, wenn
          Du einen davon in die Bestellvorlaege aufnehmen lassen möchtest!
        </div>
      <?
    }
  ?>
<? } ?>

