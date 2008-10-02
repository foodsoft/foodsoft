<?php

// terraabgleich.php (name ist historisch: nuetzlich auch fuer andere lieferanten!)
//
// - sucht in produktliste und preishistorie nach inkonsistenzen,
// - vergleicht mit Katalog (momentan nur Terra)
// - macht ggf. verbesserungsvorschlaege und erlaubt neueintrag von preisen
//
// anzeige wird durch folgende variable bestimmt:
// - produkt_id: fuer detailanzeige ein produkt (sonst ganze liste eines lieferanten)
// - lieferanten_id: liste alle produkte des lieferanten (verpflichtend, wenn keine produkt_id)
// - bestell_id: erlaubt auswahl preiseintrag fuer diese bestellung (nur mit produkt_id)

assert( $angemeldet ) or exit();

$editable = ( ! $readonly and ( $dienst == 4 ) );

need_http_var('produkt_id','u',true);
get_http_var('bestell_id','u',0,true);

$produkt = sql_produkt_details( $produkt_id );
$lieferanten_id = $produkt['lieferanten_id'];
$produkt_name = $produkt['name'];
$lieferanten_name = lieferant_name( $lieferanten_id );
$is_terra = ( $lieferanten_name == 'Terra' );

if( $bestell_id )
  $subtitle = "Produktdetails $produkt_name - Auswahl Preiseintrag";
else
  $subtitle = "Produktdetails: $produkt_name von $lieferanten_name";

setWindowSubtitle( $subtitle );
setWikiHelpTopic( "foodsoft:datenbankabgleich" );

get_http_var( 'action','w','' );
$editable or $action = '';

if( $action == 'zeitende_setzen' ) {
  need_http_var('preis_id','u');
  need_http_var('day','u');
  need_http_var('month','u');
  need_http_var('year','u');
  get_http_var('vortag','u',0);
  if( $vortag ) {
    $zeitende = "date_add( '$year-$month-$day', interval -1 second )";
  } else {
    $zeitende = "'$year-$month-$day 23:59:59'";
  }
  sql_update( 'produktpreise', $preis_id, array( 'zeitende' => "$zeitende" ), false );
}
if( $action == 'artikelnummer_setzen' ) {
  if( get_http_var( 'button_id', 'H' ) )  // aufruf aus katalogsuche per click auf A-nummer
    $anummer = $button_id;
  else
    need_http_var( 'anummer', 'H' );
  sql_update( 'produkte', $produkt_id, array( 'artikelnummer' => $anummer ) );
}
if( $action == 'neuer_preiseintrag' ) {
  action_form_produktpreis();
}
if( $action == 'delete_price' ) {
  need_http_var('preis_id','u');
  sql_delete_produktpreis( $preis_id );
}

if( $bestell_id ) {
  if( $action == 'preiseintrag_waehlen' ) {
    need_http_var( 'preis_id','u' );
    need( getState( $bestell_id ) < STATUS_ABGERECHNET, "Änderung nicht möglich: Bestellung ist bereits abgerechnet!" );
    doSql ( "UPDATE bestellvorschlaege
       SET produktpreise_id='$preis_id'
       WHERE gesamtbestellung_id='$bestell_id' AND produkt_id='$produkt_id'
    ", LEVEL_IMPORTANT, "Auswahl Preiseintrag fehlgeschlagen: " );
  }
}


// flag: neuen preiseintrag vorschlagen (falls gar keiner oder fehlerhaft):
//
$neednewprice = FALSE;

// flag: suche nach artikelnummer vorschlagen (falls kein Treffer bei Katalogsuche):
//
$neednewarticlenumber = FALSE;

// felder fuer neuen preiseintrag initialisieren:
//
$preiseintrag_neu = array();
$preiseintrag_neu['verteileinheit'] = FALSE;
$preiseintrag_neu['liefereinheit'] = FALSE;
$preiseintrag_neu['gebindegroesse'] = FALSE;
$preiseintrag_neu['preis'] = FALSE;
$preiseintrag_neu['bestellnummer'] = FALSE;
$preiseintrag_neu['mwst'] = FALSE;
$preiseintrag_neu['pfand'] = FALSE;
$preiseintrag_neu['notiz'] = FALSE;

?>
<form action='<? echo self_url(); ?>' name='reload_form' method='post'>
  <? echo self_post(); ?>
  <input type='hidden' name='action' value=''>
  <input type='hidden' name='preis_id' value='0'>
</form>

<fieldset class='big_form'>
  <legend>
    <?
      echo "Produkt: $produkt_name von $lieferanten_name";
      if( $produkt['artikelnummer'] )
        echo " (Artikelnummer: {$produkt['artikelnummer']})";
    ?>
  </legend>
<?

////////////////////////
// Preishistorie: im Detail-Modus anzeigen, sonst nur Test auf Konsistenz:
//

preishistorie_view( $produkt_id, $bestell_id, $editable );



///////////////////////////
// Artikeldaten aus foodsoft-Datenbank anzeigen:
//


// neu laden (falls durch $action geaendert):
//
$produkt = sql_produkt_details( $produkt_id );

$prgueltig = false;
if( $produkt['zeitstart'] )
  $prgueltig = true;
$lieferanten_id = $produkt['lieferanten_id'];
$produkt_name = $produkt['name'];

?>
  <fieldset class='big_form'>
  <legend>Foodsoft-Datenbank:</legend>
  <table width='100%' class='list'>
    <tr>
      <th>Name</th>
      <th>B-Nr.</th>
      <th title='Liefer-Einheit: fuers Bestellen beim Lieferanten'>L-Einheit</th>
      <th title='Nettopreis beim Lieferanten'>L-Preis</th>
      <th title='Verteil-Einheit: fuers Bestellen und Verteilen bei uns'>V-Einheit</th>
      <th title='V-Einheiten pro Gebinde'>Gebinde</th>
      <th title='MWSt in Prozent'>MWSt</th>
      <th title='Pfand je V-Einheit'>Pfand</th>
      <th title='Endpreis je V-Einheit'>V-Preis</th>
    </tr>
    <tr>
      <td><table class='inner' width='100%'>
         <tr>
           <td>
             <div class='oneline'><? printf( "%s", $produkt['name'] ); ?></div>
           </td>
           <td rowspan='2' style='width:5em;text-align:center;'>
             <? echo fc_alink( 'edit_produkt', "produkt_id=$produkt_id" ); ?>
           </td>
         </tr>
         <tr>
           <td>
             <div class='oneline small'><? printf( "%s", $produkt['notiz'] ); ?></div>
           </td>
         </tr>
      </table></td>
<?

if( $prgueltig ) {
  ?>
    <td class='number'><? echo $produkt['bestellnummer']; ?></td>
    <td class='number'><? echo "{$produkt['kan_liefermult']} {$produkt['kan_liefereinheit']}"; ?></td>
    <td class='number'><? printf( "%8.2lf / %s", $produkt['nettolieferpreis'], $produkt['preiseinheit'] ); ?></td>
    <td class='number'><? echo "{$produkt['kan_verteilmult']} {$produkt['kan_verteileinheit']}"; ?></td>
    <td class='number'><? echo $produkt['gebindegroesse']; ?></td>
    <td class='number'><? echo $produkt['mwst']; ?></td>
    <td class='number'><? printf( "%.2lf", $produkt['pfand'] ); ?></td>
    <td class='number'><?
      printf( "%8.2lf / %s %s", $produkt['endpreis'], $produkt['kan_verteilmult'], $produkt['kan_verteileinheit'] ); ?>
    </td>
  <?

} else {
  ?> <td colspan='9'><div class='warn' style='text-align:center;'> - - - </div></td> <?
}
?></tr></table><?

if( $prgueltig ) {
  if( ! $produkt['kan_liefereinheit'] ) {
    ?> <div class='warn'>FEHLER: keine gültige Liefereinheit:</div> <?
    $neednewprice = TRUE;
  }
  // FIXME: hier mehr tests machen!
}

?></fieldset> <?

/////////////////////////////
// Artikeldaten im Katalog suchen und ggf. anzeigen:
//

$result = katalogabgleich( $produkt_id, $editable, true, & $preiseintrag_neu );
switch( $result ) {
  case 0:
    // alles ok!
    break;
  case 1:
    // Abweichung zum Katalog: schlage neuen Preiseintrag vor:
    $neednewprice = true;
    break;
  case 2:
    // Kein Treffer bei Katalogsuche: schlage Artikel(nummer)wahl vor:
    $neednewarticlenumber = true;
    break;
  default:
  case 3:
    // Katalogsuche fehlgeschlagen: das ist normal bei allen ausser Terra:
    break;
}


/////////////////////////
// vorlage fuer neuen preiseintrag berechnen (soweit noch nicht aus Katalog gesetzt):
//

if( ! $preiseintrag_neu['gebindegroesse'] ) {
  if( $prgueltig and $produkt['gebindegroesse'] > 1 )
    $preiseintrag_neu['gebindegroesse'] = $produkt['gebindegroesse'];
  else
    $preiseintrag_neu['gebindegroesse'] = 1;
}

if( ! $preiseintrag_neu['verteileinheit'] ) {
  if( $prgueltig )
    $preiseintrag_neu['verteileinheit'] =
      ( ( $produkt['kan_verteilmult'] > 0.0001 ) ? $produkt['kan_verteilmult'] : 1 )
      . ( $produkt['kan_verteileinheit'] ? " {$produkt['kan_verteileinheit']} " : ' ST' );
  else
    $preiseintrag_neu['verteileinheit'] = '1 ST';
}

if( ! $preiseintrag_neu['liefereinheit'] ) {
  if( $prgueltig and $produkt['kan_liefereinheit'] and ( $produkt['kan_liefermult'] > 0.0001 ) )
    $preiseintrag_neu['liefereinheit'] = "{$produkt['kan_liefermult']} {$produkt['kan_liefereinheit']}";
  else
    $preiseintrag_neu['liefereinheit'] = $preiseintrag_neu['verteileinheit'];
}

if( ! $preiseintrag_neu['mwst'] ) {
  if( $prgueltig and $produkt['mwst'] )
    $preiseintrag_neu['mwst'] = $produkt['mwst'];
  else
    $preiseintrag_neu['mwst'] = '7.00';
}

if( ! $preiseintrag_neu['pfand'] ) {
  if( $prgueltig and $produkt['pfand'] )
    $preiseintrag_neu['pfand'] = $produkt['pfand'];
  else
    $preiseintrag_neu['pfand'] = '0.00';
}

if( ! $preiseintrag_neu['preis'] ) {
  if( $prgueltig and $produkt['endpreis'] )
    $preiseintrag_neu['preis'] = $produkt['endpreis'];
  else
    $preiseintrag_neu['preis'] = '0.00';
}

if( ! $preiseintrag_neu['bestellnummer'] ) {
  if( $prgueltig and $produkt['bestellnummer'] )
    $preiseintrag_neu['bestellnummer'] = $produkt['bestellnummer'];
  else
    $preiseintrag_neu['bestellnummer'] = '';
}

if( ! $preiseintrag_neu['notiz'] ) {
  if( $prgueltig and $produkt['notiz'] )
    $preiseintrag_neu['notiz'] = $produkt['notiz'];
  else
    $preiseintrag_neu['notiz'] = '';
}

// echo "newverteileinheit: {$preiseintrag_neu['verteileinheit']}";
// echo "newliefereinheit: {$preiseintrag_neu['liefereinheit']}";

// restliche felder automatisch berechnen:
//
preisdatenSetzen( & $preiseintrag_neu );

if( $neednewprice ) {
  ?>
    <div style='padding:1ex;' id='preiseintrag_form' class='small_form'>
      <form name='Preisform' method='post' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <fieldset>
        <legend>Vorschlag neuer Preiseintrag:</legend>
  <?
} else {
  ?>
    <div class='untertabelle'>
      <div id='preiseintrag_an_knopf'>
        <span class='button'
          onclick='preiseintrag_on();' >Neuer Preiseintrag...</span>
      </div>
    </div>
    <div style='display:none;padding:0ex;' id='preiseintrag_form' class='small_form'>
      <form name='Preisform' method='post' action='<? echo self_url(); ?>'>
      <? echo self_post(); ?>
      <fieldset>
        <legend>
          <img class='button' title='Ausblenden' src='img/close_black_trans.gif'
           onclick='preiseintrag_off();'></img> Neuer Preiseintrag:</legend>
  <?
}

?>
  <input type='hidden' name='action' value='neuer_preiseintrag'>
  <table id='preisform'>
    <tr>
      <td style='padding:1ex 0ex 1ex 0ex;'><label>Produkt:</label></td>
      <td><kbd> <?  echo "{$produkt['name']} von {$produkt['lieferanten_name']}"; ?> </kbd></td>
    </tr>
    <tr>
      <td><label>Notiz:</label>
      <td><input type='text' size='42' name='notiz' value='<? echo $preiseintrag_neu['notiz']; ?>'
           title='Notiz: zum Beispiel aktuelle Herkunft, Verband oder Lieferant'>
      </td>
    </tr>
    <tr>
      <td><label>Bestell-Nr:</label></td>
      <td>
        <input type='text' size='8' name='bestellnummer'
         value='<? echo $preiseintrag_neu['bestellnummer']; ?>'
         title='Bestellnummer (die, die sich bei Terra st&auml;ndig &auml;ndert!)'>

        <label>MWSt:</label>
        <input type='text' size='4' name='mwst' id='newfcmwst'
         value='<? echo $preiseintrag_neu['mwst']; ?>'
         title='MWSt-Satz in Prozent'
         onchange='preisberechnung_rueckwaerts();'>

        <label>Pfand:</label>
        <input type='text' size='4' name='pfand' id='newfcpfand'
         value='<? echo $preiseintrag_neu['pfand']; ?>'
         title='Pfand pro V-Einheit, bei uns immer 0.00 oder 0.16'
         onchange='preisberechnung_rueckwaerts();'>
      </td>
    </tr>
      <td><label>Verteil-Einheit:</label></td>
      <td>
        <input type='text' size='4' name='verteilmult' id='newfcmult'
         value='<? echo $preiseintrag_neu['kan_verteilmult']; ?>'
         title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
         onchange='preisberechnung_fcmult();'>
        <select size='1' name='verteileinheit' id='newfceinheit'
          onchange='preisberechnung_default();'>
          <? echo optionen_einheiten( $preiseintrag_neu['kan_verteileinheit'] ); ?>
        </select>
        <label>Endpreis:</label>
        <input title='Preis incl. MWSt und Pfand' type='text' size='8' id='newfcpreis' name='preis'
         value='<? echo $preiseintrag_neu['preis']; ?>'
         onchange='preisberechnung_vorwaerts();'>
        / <span id='newfcendpreiseinheit'>
            <? echo $preiseintrag_neu['kan_verteilmult']; ?>
            <? echo $preiseintrag_neu['kan_verteileinheit']; ?>
           </span>

        <label>Gebinde:</label>
        <input type='text' size='4' name='gebindegroesse' id='newfcgebindegroesse'
         value='<? echo $preiseintrag_neu['gebindegroesse']; ?>'
         title='Gebindegroesse in ganzen Vielfachen der V-Einheit'
         onchange='preisberechnung_gebinde();'>
        * <span id='newfcgebindeeinheit']>
            <? echo $preiseintrag_neu['kan_verteilmult']; ?>
            <? echo $preiseintrag_neu['kan_verteileinheit']; ?>
          </span>
      </td>
    </tr>
    <tr>
      <td><label>Liefer-Einheit:</label></td>
      <td>
        <input type='text' size='4' name='liefermult' id='newliefermult'
         value='<? echo $preiseintrag_neu['kan_liefermult']; ?>'
         title='Vielfache der Einheit: meist 1, ausser bei g, z.B. 1000 fuer 1kg'
         onchange='preisberechnung_default();'>
        <select size='1' name='liefereinheit' id='newliefereinheit'
          onchange='preisberechnung_default();'>
          <? echo optionen_einheiten( $preiseintrag_neu['kan_liefereinheit'] ); ?>
        </select>

        <label>Lieferpreis:</label>
          <input title='Nettopreis' type='text' size='8' id='newfclieferpreis' name='lieferpreis'
           value='<? echo $preiseintrag_neu['lieferpreis']; ?>'
           onchange='preisberechnung_rueckwaerts();'>
          / <span id='newfcpreiseinheit'><? echo $preiseintrag_neu['preiseinheit']; ?></span>
      </td>
    </tr>
    <tr>
      <td><label>ab:</label></td>
      <td>
        <? date_selector( 'day', date('d'), 'month', date('m'), 'year', date('Y') ); ?>
        <label>&nbsp;</label>
        <input type='submit' name='submit' value='OK'
         onclick=\"document.getElementById('row$outerrow').className='modified';\";
         title='Neuen Preiseintrag vornehmen (und letzten ggf. abschliessen)'>

        <label>&nbsp;</label>
        <label>Dynamische Neuberechnung:</label>
        <input name='dynamischberechnen' type='checkbox' value='yes'
         title='Dynamische Berechnung anderer Felder bei Änderung eines Eintrags' checked>

      </td>
    </tr>
  </table>
  </fieldset></form></div>
</fieldset>

<script type="text/javascript">
  function preiseintrag_on() {
    document.getElementById("preiseintrag_an_knopf").style.display = "none";
    document.getElementById("preiseintrag_form").style.display = "block";
  }
  function preiseintrag_off() {
    document.getElementById("preiseintrag_an_knopf").style.display = "inline";
    document.getElementById("preiseintrag_form").style.display = "none";
  }

  var mwst, pfand, verteilmult, verteileinheit, preis, gebindegroesse,
    liefermult, liefereinheit, lieferpreis, preiseinheit, mengenfaktor;

  // vorwaerts: lieferpreis berechnen
  //
  var vorwaerts = 0;

  function preiseinheit_setzen() {
    if( liefereinheit != verteileinheit ) {
      mengenfaktor = gebindegroesse;
      preiseinheit = liefereinheit + ' (' + gebindegroesse * verteilmult + ' ' + verteileinheit + ')';
      if( liefermult != '1' )
        preiseinheit = liefermult + ' ' + preiseinheit;
    } else {
      switch( liefereinheit ) {
        case 'g':
          preiseinheit = 'kg';
          mengenfaktor = 1000 / verteilmult;
          break;
        case 'ml':
          preiseinheit = 'L';
          mengenfaktor = 1000 / verteilmult;
          break;
        default:
          preiseinheit = liefereinheit;
          mengenfaktor = 1.0 / verteilmult;
          break;
      }
    }
  }

  function preiseintrag_auslesen() {
    mwst = parseFloat( document.Preisform.newfcmwst.value );
    pfand = parseFloat( document.Preisform.newfcpfand.value );
    verteilmult = parseInt( document.Preisform.newfcmult.value );
    verteileinheit = document.Preisform.newfceinheit.value;
    preis = parseFloat( document.Preisform.newfcpreis.value );
    gebindegroesse = parseInt( document.Preisform.newfcgebindegroesse.value );
    liefermult = parseInt( document.Preisform.newliefermult.value );
    liefereinheit = document.Preisform.newliefereinheit.value;
    lieferpreis = parseFloat( document.Preisform.newfclieferpreis.value );
    preiseinheit_setzen();
  }

  preiseintrag_auslesen();

  function preiseintrag_update() {
    document.Preisform.newfcmwst.value = mwst;
    document.Preisform.newfcmwst.pfand = pfand;
    document.Preisform.newfcmult.value = verteilmult;
    document.Preisform.newfceinheit.value = verteileinheit;
    document.Preisform.newfcpreis.value = preis;
    document.Preisform.newfcgebindegroesse.value = gebindegroesse;
    document.Preisform.newliefermult.value = liefermult;
    document.Preisform.newliefereinheit.value = liefereinheit;
    document.Preisform.newfclieferpreis.value = lieferpreis;
    document.getElementById("newfcendpreiseinheit").firstChild.nodeValue = verteilmult + ' ' + verteileinheit;
    document.getElementById("newfcgebindeeinheit").firstChild.nodeValue = verteilmult + ' ' + verteileinheit;
    document.getElementById("newfcpreiseinheit").firstChild.nodeValue = preiseinheit;
  }

  function preisberechnung_vorwaerts() {
    vorwaerts = 1;
    preiseintrag_auslesen();
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      lieferpreis = 
        parseInt( 0.499 + 100 * ( preis - pfand ) / ( 1.0 + mwst / 100.0 ) * mengenfaktor ) / 100.0;
    }
    preiseintrag_update();
  }

  function preisberechnung_rueckwaerts() {
    vorwaerts = 0;
    preiseintrag_auslesen();
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      preis = 
        parseInt( 0.499 + 10000 * ( lieferpreis * ( 1.0 + mwst / 100.0 ) / mengenfaktor + pfand ) ) / 10000.0;
    }
    preiseintrag_update();
  }

  function preisberechnung_default() {
    if( vorwaerts )
      preisberechnung_vorwaerts();
    else
      preisberechnung_rueckwaerts();
  }
  function preisberechnung_fcmult() {
    alt = verteilmult;
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      verteilmult = parseInt( document.Preisform.newfcmult.value );
      if( verteilmult < 1 )
        verteilmult = 1;
      if( (verteilmult > 0) && (alt > 0) ) {
        gebindegroesse = parseInt( 0.499  + gebindegroesse * alt / verteilmult);
        if( gebindegroesse < 1 )
          gebindegroesse = 1;
        document.Preisform.newfcgebindegroesse.value = gebindegroesse;
      }
    }
    preisberechnung_default();
  }
  function preisberechnung_gebinde() {
    alt = gebindegroesse;
    berechnen = document.Preisform.dynamischberechnen.checked;
    if( berechnen ) {
      gebindegroesse = parseInt( document.Preisform.newfcgebindegroesse.value );
      if( gebindegroesse < 1 )
        gebindegroesse = 1;
      // if( (gebindegroesse > 0) && (alt > 0) ) {
      //  verteilmult = parseInt( 0.499 + verteilmult * alt / gebindegroesse );
      //  document.Preisform.newfcmult.value = verteilmult;
      // }
    }
    preisberechnung_default();
  }

</script>

