<?PHP

assert( $angemeldet );   // aufruf jetzt per index.php?window=showBestelltProd

setWindowSubtitle( "Produktverteilung" );
setWikiHelpTopic( "foodsoft:Produktverteilung" );  // TODO: das ganze Skript umbenennen?

need_http_var('bestell_id', 'u', true);
need_http_var('produkt_id', 'u', true);

$status = getState( $bestell_id );

get_http_var('action','w','');

$editAmounts = ( $hat_dienst_IV and ( ! $readonly ) );
$ro_tag = '';
if( $status != STATUS_VERTEILT ) {
  $editAmounts = false;
  $ro_tag="readonly";
}
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
$muell_id = sql_muell_id();
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
      <th>Status:</th>
      <td>
        <?
          echo rechnung_status_string( $status );
          if( $status == STATUS_ABGERECHNET )
            abrechnung_kurzinfo( $bestell_id );
        ?>
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

$muellmenge = 0;
$verteilt = 0;
while( $gruppe = mysql_fetch_array($gruppen) ) {
  $gruppen_id = $gruppe['id'];

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
      if( $gruppen_id == $muell_id ) {
        // sonderfall: muell
        $muellmenge = $bestellung['muellmenge'];
        continue 2;
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
      <td>${gruppe['name']} (${gruppe['gruppennummer']})</td>
      <td class='mult'>" . $festmenge * $vorschlag['kan_verteilmult']
        . " (" . $toleranzmenge * $vorschlag['kan_verteilmult']  . ")</td>
      <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
  ";

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
      <input $ro_tag name='zuteilung_<? echo $gruppen_id; ?>' type='text' size='5'
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

?>
  <tr class='summe'>
    <td>Müll:</td>
    <td> </td>
    <td> </td>
    <td class='mult'><? printf( '%d', $muellmenge * $vorschlag['kan_verteilmult'] ); ?></td>
    <td class='unit'><? echo $vorschlag['kan_verteileinheit']; ?></td>
    <td class='mult'><? echo $vorschlag['preis_rund']; ?></td>
    <td class='unit'>/ <? echo "{$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}"; ?></td>
    <td class='number'><? printf( "%.2lf", $vorschlag['preis'] * $muellmenge ); ?></td>
  <tr>
<?

$basar = $vorschlag['liefermenge'] - $verteilt - $muellmenge;

?>
  <tr class='summe'>
    <td><a href="javascript:neuesfenster('index.php?window=basar','basar');"
      title='Basar anzeigen...'>Basar:</a></td>
    <td class='mult'><? printf( '%d', $basar_festmenge * $vorschlag['kan_verteilmult'] ); ?>
       (<? printf( '%d', $basar_toleranzmenge * $vorschlag['kan_verteilmult'] ); ?>)</td>
    <td class='unit'><? echo $vorschlag['kan_verteileinheit'] ?></td>
    <td class='mult'><? printf( '%d', $basar * $vorschlag['kan_verteilmult'] ); ?></td>
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

