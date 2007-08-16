<?PHP

  require_once('code/config.php');
  require_once("$foodsoftpath/code/err_functions.php");
  require_once("$foodsoftpath/code/zuordnen.php");
  require_once("$foodsoftpath/code/login.php");

  need_http_var('bestell_id');
  need_http_var('produkt_id');

  $self = "/foodsoft/windows/showBestelltProd.php?bestell_id=$bestell_id&produkt_id=$produkt_id";
  $self_fields = "
    <input type='hidden' name='bestell_id' value='$bestell_id'>
    <input type='hidden' name='produkt_id' value='$produkt_id'>
  ";

  get_http_var('order_by');
  $order_by != '' or $order_by = 'bestellguppen_id';

  get_http_var('action');
  if( $action == 'zuteilung_loeschen' ) {
    need_http_var( 'zuteilung_id' );
    mysql_query( "DELETE FROM bestellzuordnung WHERE id='$zuteilung_id'" )
      or error( __LINE__, __FILE__, "L&ouml;schen fehlgeschlagen: " . mysql_error() );
  }

  // daten zum bestellvorschlag ermitteln:
  //
  $result = mysql_query(
    " SELECT * , produktpreise.id as preis_id
               , produkte.name as produkt_name
               , gesamtbestellungen.name as name
      FROM gesamtbestellungen
      INNER JOIN bestellvorschlaege
              ON bestellvorschlaege.gesamtbestellung_id=gesamtbestellungen.id
      INNER JOIN produkte
              ON produkte.id=bestellvorschlaege.produkt_id
      INNER JOIN produktpreise
              ON produktpreise.id=bestellvorschlaege.produktpreise_id
      WHERE     gesamtbestellungen.id='$bestell_id'
            AND bestellvorschlaege.produkt_id='$produkt_id'
    "
  ) or error( __LINE__, __FILE__,
    "Suche in gesamtbestellungen,bestellvorschlaege fehlgeschlagen: " . mysql_error() );

  $vorschlag = mysql_fetch_array($result)  
    or error( __LINE__, __FILE__,
      "gesamtbestellung/bestellvorschlag nicht gefunden " );

  preisdatenSetzen( & $vorschlag );

  $title = "Verteilung: {$vorschlag['name']}";
  $subtitle = "Produkt: {$vorschlag['produkt_name']}";
  require_once("$foodsoftpath/windows/head.php");

  $basar_id = sql_basar_id();
  $basar_festmenge = 0;
  $basar_toleranzmenge = 0;

  // alle an dieser bestellung dieses produktes beteiligten gruppen ermitteln:
  //
  $gruppen = mysql_query(
    " SELECT gruppenbestellungen.bestellguppen_id as id
           , bestellgruppen.name as name
      FROM bestellzuordnung
      INNER JOIN gruppenbestellungen
              ON gruppenbestellungen.id=bestellzuordnung.gruppenbestellung_id
      INNER JOIN bestellgruppen
              ON bestellgruppen.id=gruppenbestellungen.bestellguppen_id
      WHERE     gruppenbestellungen.gesamtbestellung_id='$bestell_id'
            AND bestellzuordnung.produkt_id='$produkt_id'
      GROUP BY bestellgruppen.id
      ORDER BY ( bestellgruppen.id % 1000 )
    "
  ) or error( __LINE__, __FILE__,
    "Suche nach beteiligten Gruppen fehlgeschlagen: " . mysql_error() );


  echo "
    <h1>Produktverteilung</h1>
    <table class='liste' style='margin-bottom:2em;'>
      <tr>
        <th>Bestellung:</th>
        <td><a
           href=\"javascript:neuesfenster('/foodsoft/index.php?area=lieferschein&bestellungs_id=$bestell_id','lieferschein')\"
             title='zum Lieferschein...'>{$vorschlag['name']}</a>
        </td>
      </tr>
      <tr>
        <th>Produkt:</th>
        <td>
          <a href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produktid=$produkt_id','produktdetails');\"
            title='zu den Produktdetails...' >{$vorschlag['produkt_name']}</a>
        </td>
      </tr>
    </table>
        
    <form action='$self' method='$post'>
    $self_fields
    <table class='numbers'>
  ";
  echo "
      <tr class='summe'>
        <td colspan='3' style='text-align:right;'>Liefermenge:</td>
        <td class='mult'>" . $vorschlag['liefermenge'] * $vorschlag['kan_verteilmult'] . "</td>
        <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
        <td class='mult'>{$vorschlag['preis_rund']}</td>
        <td class='unit'>/ {$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}</td>
        <td class='number'>". sprintf( "%.2lf", $vorschlag['preis'] * $vorschlag['liefermenge'] ) . "</td>
      </tr>
  ";
  distribution_tabellenkopf( 'Gruppe' );

  $verteilt = 0;
  $problems = false;
  while( $gruppe = mysql_fetch_array($gruppen) ) {
    $gruppen_id = $gruppe['id'];

    // bestellte mengen ermitteln:
    //
    $bestellungen = mysql_query(
      "SELECT SUM( menge * IF(art=0,1,0) ) as festmenge
            , SUM( menge * IF(art=1,1,0) ) as toleranzmenge
        FROM bestellzuordnung
        INNER JOIN gruppenbestellungen
                ON gruppenbestellungen.id=bestellzuordnung.gruppenbestellung_id
        WHERE     gruppenbestellungen.gesamtbestellung_id='$bestell_id'
              AND gruppenbestellungen.bestellguppen_id='$gruppen_id'
              AND bestellzuordnung.produkt_id='$produkt_id'
              AND (art=0 OR art=1)
        GROUP BY gruppenbestellungen.bestellguppen_id,bestellzuordnung.produkt_id
      "
    ) or error ( __LINE__, __FILE__,
      "Suche nach bestellungen fehlgeschlagen: " . mysql_error() );

    $bestellung = mysql_fetch_array( $bestellungen );
    if( $bestellung ) {
      $festmenge = $bestellung['festmenge'];
      $toleranzmenge = $bestellung['toleranzmenge'];
    } else {
      $festmenge = 0;
      $toleranzmenge = 0;
    }

    // basar kommt extra ganz zum schluss; wir merken uns ggf. die bestellten mengen:
    //
    if( $gruppen_id == $basar_id ) {
      $basar_festmenge = $festmenge;
      $basar_toleranzmenge = $toleranzmenge;
      continue;
    }

    echo "
      <tr>
        <td>${gruppe['name']}</td>
        <td class='mult'>" . $festmenge * $vorschlag['kan_verteilmult']
          . " (" . $toleranzmenge * $vorschlag['kan_verteilmult']  . ")</td>
        <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
    ";

    // zugeteilte mengen ermitteln:
    //
    $zuteilungen = mysql_query(
      "SELECT *, bestellzuordnung.id as zuteilung_id
        FROM bestellzuordnung
        INNER JOIN gruppenbestellungen
                   ON gruppenbestellungen.id=bestellzuordnung.gruppenbestellung_id
        INNER JOIN bestellgruppen
                   ON bestellgruppen.id=gruppenbestellungen.bestellguppen_id
        WHERE     gruppenbestellungen.gesamtbestellung_id='$bestell_id'
              AND gruppenbestellungen.bestellguppen_id='$gruppen_id'
              AND bestellzuordnung.produkt_id='$produkt_id'
              AND art=2
      "
    ) or error ( __LINE__, __FILE__,
      "Suche nach Zuteilungen fehlgeschlagen: " . mysql_error() );

    $rows = mysql_num_rows($zuteilungen);
    if( $rows == 0 ) {
      echo "<td colspan='2'>(keine Zuteilungen)</td>";
    } else if( $rows == 1 ) {
      $zuteilung = mysql_fetch_array($zuteilungen);
      preisdatenSetzen( & $zuteilung );

      if( $action == 'zuteilungen_aendern' ) {
        need_http_var("zuteilung_$gruppen_id");
        $verteil_form = ${"zuteilung_$gruppen_id"} / $vorschlag['kan_verteilmult'];
        if( $verteil_form != $zuteilung['menge'] ) {
          changeVerteilmengen_sql( $verteil_form, $gruppen_id, $produkt_id, $bestell_id );
          $zuteilung['menge'] = $verteil_form;
        }
      }

      echo "
        <td class='number' style='padding:1px 1ex 1px 1em;'>
          <input name='zuteilung_$gruppen_id' type='text' size='5'
            style='text-align:right;'
            value='" . $zuteilung['menge'] * $vorschlag['kan_verteilmult'] . "'></td>
        <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
        <td class='mult' style='padding-left:1em;'>{$vorschlag['preis_rund']}</td>
        <td class='unit'>/ {$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}</td>
        <td class='number'>". sprintf("%.2lf", $vorschlag['preis'] * $zuteilung['menge']) . "</td>
      ";
      $verteilt += $zuteilung['menge'];
    } else {
      $problems = true;
      echo "
        <td colspan='2'>
        <div class='warn' style='margin:1ex;'>FEHLER: $rows Zuteilungen:</div>
        <table class='liste' width='90%'>
      ";
      while( $zuteilung = mysql_fetch_array($zuteilungen) ) {
        echo "
          <tr>
            <td class='unit'>" . $zuteilung['menge'] * $vorschlag['kan_verteilmult'] 
                  . "{$vorschlag['kan_verteileinheit']}
            </td>
            <td class='unit'>
              <form action='$self' method='post'>
                $self_fields
                <input type='hidden' name='action' value='zuteilung_loeschen'>
                <input type='hidden' name='zuteilung_id' value='{$zuteilung['zuteilung_id']}'>
                <input type='submit' name='submit'
                  value='{$zuteilung['zuteilung_id']} l&ouml;schen'>
              </form>
            </td>
          </tr>
        ";
      }
      echo "</table></td>";
    }
    echo "</tr>";
  }

  $basar = $vorschlag['liefermenge'] - $verteilt;
  
  echo "
    <tr class='summe'>
      <td><a href=\"javascript:neuesfenster('/foodsoft/basar.php','basar');\"
        title='Basar anzeigen...'>Basar:</a></td>
      <td class='mult'>" . $basar_festmenge * $vorschlag['kan_verteilmult']
        . " (" . $basar_toleranzmenge * $vorschlag['kan_verteilmult']  . ")</td>
      <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
  ";
  if( ! $problems ) {
    echo "
      <td class='mult'>" . $basar * $vorschlag['kan_verteilmult'] . "</td>
      <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
      <td class='mult'>{$vorschlag['preis_rund']}</td>
      <td class='unit'>/ {$vorschlag['kan_verteilmult']} {$vorschlag['kan_verteileinheit']}</td>
      <td class='number'>" . sprintf( "%.2lf", $vorschlag['preis'] * $basar ) . "</td>
    ";
  } else {
    echo "
      <td colspan='5' style='text-align:center;'><div class='warn'>(FEHLER!)</div></td>
    ";
  }

  echo "
    </tr>
  ";

  if( ! $problems ) {
    echo "
      <tr>
        <td colspan='8'>
          <input type='submit' name='submit' value='Verteilmengen &auml;ndern'>
        </td>
      </tr>
      <input type='hidden' name='action' value='zuteilungen_aendern'>
    ";
  }
  
  echo "
    </table>
    </form>
    $print_on_exit";
   
?>

