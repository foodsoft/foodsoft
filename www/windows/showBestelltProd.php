<?PHP

assert( $angemeldet );   // aufruf jetzt per index.php?window=showBestelltProd

setWindowSubtitle( "Produktverteilung" );
setWikiHelpTopic( "foodsoft:Produktverteilung" );  // TODO: das ganze Skript umbenennen?

need_http_var('bestell_id', 'u', true);
need_http_var('produkt_id', 'u', true);

get_http_var('action','w','');

$editAmounts = ( $hat_dienst_IV and ! $readonly );
$editAmounts or $action = '';

// TODO: wird bisher von nirgendwo ausgeloest, wird das gebraucht?
// if( $action == 'zuteilung_loeschen' ) {
//   need_http_var( 'zuteilung_id' );
//   sql_delete_bestellzuordnung($zuteilungs_id);
// }

// daten zum bestellvorschlag ermitteln:
//
$vorschlag = sql_bestellvorschlag_daten($bestell_id,$produkt_id);
preisdatenSetzen( & $vorschlag );

$basar_id = sql_basar_id();
$basar_festmenge = 0;
$basar_toleranzmenge = 0;

$gruppen = sql_beteiligte_bestellgruppen($bestell_id, $produkt_id);

?>
  <table class='liste' style='margin-bottom:2em;'>
    <tr>
      <th>Bestellung:</th>
      <td><a
         href="javascript:neuesfenster('index.php?window=bestellschein&bestell_id=<? echo $bestell_id; ?>','bestellschein')"
             title='zum Lieferschein...'><? echo $vorschlag['name']; ?></a>
      </td>
    </tr>
    <tr>
      <th>Produkt:</th>
      <td>
        <a href="javascript:neuesfenster('index.php?window=terraabgleich&produkt_id=<? echo $produkt_id; ?>','produktdetails');"
          title='zu den Produktdetails...' ><? echo $vorschlag['produkt_name']; ?></a>
      </td>
    </tr>
  </table>
<?

if( $editAmounts ) {
  echo "<form action='" . self_url() . "' method='post'>" . self_post();
}

?>
  <table class='numbers'>
    <tr class='summe'>
      <td colspan='3' style='text-align:right;'>Liefermenge:</td>
      <td class='mult'><? echo $vorschlag['liefermenge'] * $vorschlag['kan_verteilmult']; ?></td>
      <td class='unit'><? echo $vorschlag['kan_verteileinheit']; ?></td>
      <td class='mult'><? echo $vorschlag['preis_rund']; ?></td>
      <td class='unit'>/ <? echo "{$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}"; ?></td>
      <td class='number'><? printf( "%.2lf", $vorschlag['preis'] * $vorschlag['liefermenge'] ); ?></td>
    </tr>
<?

distribution_tabellenkopf( 'Gruppe' );

$verteilt = 0;
while( $gruppe = mysql_fetch_array($gruppen) ) {
  $gruppen_id = $gruppe['id'];

  // bestellte mengen ermitteln:
  // TODO: mit sql_bestellmengen zusammenfassen
  // DONE: sql_bestellprodukte liefert alle infos
  //   $bestellungen = mysql_query(
  //     "SELECT SUM( menge * IF(art=0,1,0) ) as festmenge
  //           , SUM( menge * IF(art=1,1,0) ) as toleranzmenge
  //       FROM bestellzuordnung
  //       INNER JOIN gruppenbestellungen
  //               ON gruppenbestellungen.id=bestellzuordnung.gruppenbestellung_id
  //       WHERE     gruppenbestellungen.gesamtbestellung_id='$bestell_id'
  //             AND gruppenbestellungen.bestellguppen_id='$gruppen_id'
  //             AND bestellzuordnung.produkt_id='$produkt_id'
  //             AND (art=0 OR art=1)
  //       GROUP BY gruppenbestellungen.bestellguppen_id,bestellzuordnung.produkt_id
  //     "
  //   ) or error ( __LINE__, __FILE__,
  //     "Suche nach bestellungen fehlgeschlagen: " . mysql_error() );

  $bestellungen = sql_bestellprodukte( $bestell_id, $gruppen_id, $produkt_id );

  switch( $rows = mysql_num_rows($bestellungen) ) {
    case 0:
      $festmenge = 0;
      $toleranzmenge = 0;
      $menge = 0;
      break;
    case 1:
      $bestellung = mysql_fetch_array( $bestellungen );
      //
      // basar kommt extra ganz zum schluss; wir merken uns ggf. die bestellten mengen:
      //
      if( $gruppen_id == $basar_id ) {
        // sonderfall: bei basar ist die toleranz in 'basarbestellmenge':
        $basar_festmenge = $bestellung['gesamtbestellmenge'] - $bestellung['basarbestellmenge'];
        $basar_toleranzmenge = $bestellung['basarbestellmenge'];
        continue 2;  // 'switch' ist in php auch eine Schleife!
      }
      $toleranzmenge = $bestellung['toleranzbestellmenge'];
      $festmenge = $bestellung['gesamtbestellmenge'] - $toleranzmenge;
      $menge = $bestellung['verteilmenge'];
      break;
    default:
      error ( __LINE__, __FILE__, 'FEHLER: $rows bestellungen gefunden' );
  }

  echo "
    <tr>
      <td>${gruppe['name']}</td>
      <td class='mult'>" . $festmenge * $vorschlag['kan_verteilmult']
        . " (" . $toleranzmenge * $vorschlag['kan_verteilmult']  . ")</td>
      <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
  ";

  // zugeteilte mengen ermitteln:
  // TODO: mit sql_bestellmengen zusammen. Wieso  brauchen wir count?
  // DONE: sql_bestellprodukte liefert schon alles. count brauchen wir wohl nicht :-)
  //   $zuteilungen = mysql_query(
  //     "SELECT sum(menge) as menge, count(*) as anzahl
  //       FROM bestellzuordnung
  //       INNER JOIN gruppenbestellungen
  //                  ON gruppenbestellungen.id=bestellzuordnung.gruppenbestellung_id
  //       INNER JOIN bestellgruppen
  //                  ON bestellgruppen.id=gruppenbestellungen.bestellguppen_id
  //       WHERE     gruppenbestellungen.gesamtbestellung_id='$bestell_id'
  //             AND gruppenbestellungen.bestellguppen_id='$gruppen_id'
  //             AND bestellzuordnung.produkt_id='$produkt_id'
  //             AND art=2
  //       GROUP BY gruppenbestellungen.gesamtbestellung_id,gruppenbestellungen.bestellguppen_id
  //     "
  //   ) or error ( __LINE__, __FILE__,
  //     "Suche nach Zuteilungen fehlgeschlagen: " . mysql_error() );

  if( $editAmounts && ( $action == 'zuteilungen_aendern' ) ) {
    fail_if_readonly();
    if( get_http_var("zuteilung_$gruppen_id",'f') ) {
      $verteil_form = ${"zuteilung_$gruppen_id"} / $vorschlag['kan_verteilmult'];
      if( $verteil_form != $menge ) {
        changeVerteilmengen_sql( $verteil_form, $gruppen_id, $produkt_id, $bestell_id );
        $menge = $verteil_form;
      }
    }
  }
  ?> <td class='number' style='padding:1px 1ex 1px 1em;'> <?
  if( $editAmounts ) {
    ?>
      <input name='zuteilung_<? echo $gruppen_id; ?>' type='text' size='5'
        style='text-align:right;'
        value='<? echo $menge * $vorschlag['kan_verteilmult']; ?>'
        onfocus="document.getElementById('form_submit').style.display='';"
        onchange="document.getElementById('form_submit').style.display='';"
      >
    <?
  } else {
    echo $menge * $vorschlag['kan_verteilmult'];
  }
  ?>
    </td>
    <td class='unit'><? echo $vorschlag['kan_verteileinheit']; ?></td>
    <td class='mult' style='padding-left:1em;'><? echo $vorschlag['preis_rund']; ?></td>
    <td class='unit'>/ <? echo "{$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}"; ?></td>
    <td class='number'><? printf( "%.2lf", $vorschlag['preis'] * $menge ); ?></td>
  </tr>
  <?
  $verteilt += $menge;
}

$basar = $vorschlag['liefermenge'] - $verteilt;

?>
  <tr class='summe'>
    <td><a href="javascript:neuesfenster('index.php?window=basar','basar');"
      title='Basar anzeigen...'>Basar:</a></td>
    <td class='mult'><? echo $basar_festmenge * $vorschlag['kan_verteilmult']; ?>
       (<? echo $basar_toleranzmenge * $vorschlag['kan_verteilmult']; ?>)</td>
    <td class='unit'><? echo $vorschlag['kan_verteileinheit'] ?></td>
    <td class='mult'><? echo $basar * $vorschlag['kan_verteilmult']; ?></td>
    <td class='unit'><? echo $vorschlag['kan_verteileinheit']; ?></td>
    <td class='mult'><? echo $vorschlag['preis_rund']; ?></td>
    <td class='unit'>/ <? echo "{$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}"; ?></td>
    <td class='number'><? printf( "%.2lf", $vorschlag['preis'] * $basar ); ?></td>
  </tr>
<?

if( $editAmounts ) {
  ?>
    <tr style='display:none;' id='form_submit'>
      <td colspan='3'>
        <input type='submit' name='submit' value='Änderungen speichern'>
      </td>
      <td colspan='5'>
        <input type='reset' name='reset' value='Änderungen rückgängig machen'
        onclick="document.getElementById('form_submit').style.display='none';"
        >
      </td>
    </tr>
  <?
}

?> </table> <?

if( $editAmounts ) {
  ?> <input type='hidden' name='action' value='zuteilungen_aendern'> </form> <?
}

