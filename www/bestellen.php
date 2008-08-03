<?PHP
  error_reporting('E_NONE'); 

  assert( $angemeldet ) or exit();  // aufruf sollte nur noch per index.php?area=bestellen erfolgen
	
  setWikiHelpTopic( "foodsoft:bestellen" );

  // $HTTP_GET_VARS = array_merge( $HTTP_GET_VARS, $HTTP_POST_VARS );
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


  // ab hier: eigentliches bestellformular:

  get_http_var( 'action', 'w', '' );

  switch( $action ) {
    case 'produkt_hinzufuegen':
      need_http_var( 'produkt_id', 'U' );
      sql_insert_bestellvorschlaege( $produkt_id, $bestell_id );
      break;
  }

  $gesamt_preis = 0;
  $max_gesamt_preis = 0;

  $produkte = sql_bestellprodukte( $bestell_id );
  $anzahl_produkte = mysql_num_rows( $produkte );

?>
<script type="text/javascript">
  var anzahl_produkte = <? echo $anzahl_produkte; ?>;
  var kontostand = <? printf( "%.2lf", $kontostand ); ?>;
  var gesamtkosten = 0.00;
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
    gesamtkosten += kosten[produkt];
  }

  function zuteilung_berechnen( produkt ) {
    var festmenge, toleranzmenge, gebinde, bestellmenge, restmenge, zuteilung_fest;
    var menge, quote, zuteilung_toleranz, kosten_neu;

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
      menge = zuteilung_fest_alt[produkt];
      if( menge > restmenge )
        menge = restmenge;
      zuteilung_fest += menge; restmenge -= menge;

      // ...dann werden, soweit moeglich, die anderen festbestellungen erfuellt:
      //
      menge = fest_andere[produkt];
      if( menge > restmenge )
        menge = restmenge;
      restmenge -= menge;

      // ...dann wird die zuteilung der gruppe, soweit moeglich, aufgestockt:
      //
      menge = fest[produkt] - zuteilung_fest;
      if( menge > restmenge )
        menge = restmenge;
      zuteilung_fest += menge; restmenge -= menge;

    } else {

      // festmenge wurde reduziert:
      // erstmal werden die anderen gruppen beruecksichtigt...
      //
      menge = zuteilung_fest_andere[produkt];
      if( menge > restmenge )
        menge = restmenge;
      restmenge -= menge;

      // ...und erst dann die gruppe, die reduziert hat:
      //
      menge = fest[produkt];
      if( menge > restmenge )
        menge = restmenge;
      zuteilung_fest += menge; restmenge -= menge;

    }

    // falls noch toleranz beruechsichtigt wird: moeglichst gleichmaessig nach quote verteilen:
    //
    if( restmenge > 0 ) {
      quote = restmenge / ( toleranz_andere[produkt] + toleranz[produkt] );
      menge = Math.ceil( toleranz[produkt] * quote );
      if( menge > restmenge )
        menge = restmenge;
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

    document.getElementById('bm_'+produkt).firstChild.nodeValue = bestellmenge;

    // formularfelder aktualisieren:
    document.getElementById('fest_'+produkt).value = fest[produkt];
    document.getElementById('toleranz_'+produkt).value = toleranz[produkt];

    // kosten und neuen kontostand berechnen und anzeigen:
    kosten_neu = preis * ( fest[produkt] + toleranz[produkt] );
    gesamtkosten += ( kosten_neu - kosten[produkt] );
    kosten[produkt] = kosten_neu;
    document.getElementById('k_'+produkt).firstChild.nodeValue = kosten_neu;
    document.getElementById('gesamtkosten').firstChild.nodeValue = gesamtkosten;
    document.getElementById('konto_rest').firstChild.nodeValue = kontostand - gesamtkosten;

    document.getElementById('reminder').style.display = 'inline';
    return true;
  }

  function fest_plus( produkt ) {
    fest[produkt]++;
    zuteilung_berechnen( produkt );
  }
  function fest_minus( produkt ) {
    if( fest[produkt] > 0 ) {
      fest[produkt]--;
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
-->
</script>


<?

if( ! $readonly ) {
  ?>
  <div style='position:fixed;top:20px;left:20px;padding:0ex;z-index:999;' class='alert'>
    <div style='margin:0pt;display:none;padding:1ex;' id='reminder'>
      <table class='alert'>
        <tr>
          <td class='alert'>
            <img class='button' src='img/close_black_trans.gif' onClick='document.getElementById("reminder").style.display = "none";'>
          </td>
          <td style='text-align:center' class='alert'> Änderungen sind noch nicht gespeichert! </td>
        </tr>
        <tr>
          <td colspan='2' style='text-align:center;' class='alert'>
            <input type='button' class='bigbutton' value='Bestellung speichern' onClick='bestellungAktualisieren();'>
            <input type="button" class="bigbutton" value="Abbrechen" onClick="bestellungBeenden();">
          </td>
        </tr>
        <tr>
          <td>Gesamtpreis:</td><td class='number' id='gesamtkosten'>-</td>
        </tr>
        <tr>
          <td>noch verf&uuml;gbar:</td><td class='number' id='konto_rest'><? printf( '%.2lf', $kontostand ); ?></td>
        </tr>
      </table>
    </div>
  </div>
  <?
}
?>


<form name="bestellForm" action="<? echo self_url(); ?>" method="post">
  <? echo self_post(); ?>
    <input type="hidden" name="action" value='bestellen'>
    <table class='numbers' width='100%' style="margin:40px 0 0 0;">
      <tr>
        <th>Bezeichnung</th>
        <th>Produktgruppe</th>
        <th colspan='2'>Gebindegroesse</th>
        <th colspan='2' title='Einzelpreis (mit Pfand und MWSt')>Preis</th>
        <th colspan='2' title='voraussichtliche Bestellmenge aller Gruppen'>Gesamtbestellmenge</th>
        <th>Menge fest</th>
        <th>Toleranz</th>
        <th title='voraussichtliche Kosten (mit Pfand und MWSt)'>Kosten</th>
      </tr>
<?

$produkte = sql_bestellprodukte( $bestell_id );

$gesamtpreis = 0.0;
$n = 0;
while( $produkt = mysql_fetch_array( $produkte ) ) {
  ++$n;
  preisdatenSetzen( $produkt );
  $produkt_id = $produkt['produkt_id'];
  // echo "$bestell_id, $gruppen_id, $produkt_id<br>";
  $bestellmengen_gruppe = sql_select_single_row(
    select_bestellprodukte( $bestell_id, $gruppen_id, $produkt_id )
  , array( 'toleranzbestellmenge' => 0, 'basarbestellmenge' => 0, 'gesamtbestellmenge' => 0 )
  );

  $gebindegroesse = $produkt['gebindegroesse'];
  $preis = $produkt['preis'];

  $toleranzmenge = $bestellmengen_gruppe['toleranzbestellmenge'] + $bestellmengen_gruppe['basarbestellmenge'];
  $festmenge = $bestellmengen_gruppe['gesamtbestellmenge'] - $toleranzmenge;

  $toleranzmenge_gesamt = $produkt['toleranzbestellmenge'] + $produkt['basarbestellmenge'];
  $toleranzmenge_andere = $toleranzmenge_gesamt - $toleranzmenge;

  $festmenge_gesamt = $produkt['gesamtbestellmenge'] - $toleranzmenge_gesamt;
  $festmenge_andere = $festmenge_gesamt - $festmenge;

  $zuteilungen = zuteilungen_berechnen( $bestell_id, $produkt_id );
  $zuteilung_fest = adefault( $zuteilungen['festzuteilungen'], $gruppen_id, 0 );
  $zuteilung_toleranz = adefault( $zuteilungen['toleranzzuteilungen'], $gruppen_id, 0 );

  $kosten = $preis * ( $festmenge + $toleranzmenge );
  $gesamtpreis += $kosten;

  printf( "
    <script type='text/javascript'>
      init_produkt( %u, %u, %.2lf, %u, %u, %u, %u, %u, %u );
    </script>
  "
  , $n, $gebindegroesse , $preis
  , $festmenge, $toleranzmenge
  , $festmenge_andere, $toleranzmenge_andere
  , $zuteilung_fest, $zuteilung_toleranz
  );
  ?>
    <tr>
      <td><? printf( "<div class='oneline'>%s</div><div class='oneline_small'>%s</div>", $produkt['produkt_name'], $produkt['notiz'] ); ?></td>
      <td><? echo $produkt['produktgruppen_name']; ?></td>
      <td class='mult'><? printf( "%s *", $gebindegroesse ); ?></td>
      <td class='unit'><? printf( "%s %s", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ); ?></td>
      <td class='mult'><? printf( "%.2lf", $preis ); ?></td>
      <td class='unit'><? printf( "/ %s %s", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ); ?></td>
      <td class='mult' id='bm_<? echo $n; ?>'><? printf( "%u", $zuteilungen['bestellmenge'] ); ?></td>
      <td class='unit'><? printf( "%s %s", $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ); ?></td>
      <td class='number'>
        <div class='oneline'>
          <span style='color:#00e000;' id='fz_<? echo $n; ?>'><? echo $zuteilung_fest; ?></span>
          +
          <span style='color:#e80000;' id='fr_<? echo $n; ?>'><? echo $festmenge - $zuteilung_fest; ?></span>
          /
          <span style='color:#000000;' id='fg_<? echo $n; ?>'><? echo $festmenge_gesamt; ?></span>
        </div>
        <? if( ! $readonly ) { ?>
          <div class='oneline'>
            <input type='button' value='<' onclick='fest_minus(<? echo $n; ?>);' >
            <input type='button' value='>' onclick='fest_plus(<? echo $n; ?>);' >
          </div>
        <? } ?>
      </td>
      <td class='number'>
        <? if( $gebindegroesse > 1 ) { ?>
          <div class='oneline'>
            <span style='color:#00e000;' id='tz_<? echo $n; ?>'><? echo $zuteilung_toleranz; ?></span>
            +
            <span style='color:#e80000;' id='tr_<? echo $n; ?>'><? echo $toleranzmenge - $zuteilung_toleranz; ?></span>
            /
            <span style='color:#000000;' id='tg_<? echo $n; ?>'><? echo $toleranzmenge_gesamt; ?></span>
          </div>
          <? if( ! $readonly ) { ?>
            <div class='oneline'>
            <input type='button' value='<' onclick='toleranz_minus(<? echo $n; ?>);' >
            <input type='button' value='>' onclick='toleranz_plus(<? echo $n; ?>);' >
            </div>
          <? } ?>
        <? } else { ?>
          <div class='oneline' style='text-align:center;'>
          -
          </div>
        <? } ?>
      </td>
      <td class='number' id='k_<? echo $n; ?>'>
        <? printf( "%.2lf", $kosten ); ?>
        <input type='hidden' name='fest_<? echo $n; ?>' id='fest_<? echo $n; ?>' value='<? echo $festmenge; ?>'>
        <input type='hidden' name='toleranz_<? echo $n; ?>'  id='toleranz_<? echo $n; ?>' value='<? echo $toleranzmenge; ?>'>
      </td>
    </tr>
  <?
}

?>
  <tr class='summe'>
    <td colspan='10'>Gesamtpreis:</td>
    <td class='number' id='summe'><? printf( "%.2lf", $gesamtpreis ); ?></td>
  </tr>
  </table>
  </form>
<?

