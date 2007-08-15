<?PHP

  require_once('code/config.php');
  require_once("$foodsoftpath/code/err_functions.php");
  require_once("$foodsoftpath/code/zuordnen.php");
  require_once("$foodsoftpath/code/login.php");

  need_http_var('bestell_id');
  need_http_var('produkt_id');

  get_http_var('order_by');
  $order_by != '' or $order_by = 'bestellguppen_id';

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

  preisdatenSetzen( & $vorschlag );

  echo "
    <h1>Verteilung {$vorschlag['produkt_name']} aus Bestellung {$vorschlag['name']}</h1>
    <table class='numbers'>
      <tr class='summe'>
        <td colspan='3' style='text-align:right;'>Liefermenge:</td>
        <td class='mult'>" . $vorschlag['liefermenge'] * $vorschlag['kan_verteilmult'] . "</td>
        <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
      </tr>
      <tr>
        <th>Gruppe</th>
        <th colspan='2'>bestellt (Toleranz)</th>
        <th colspan='2'>zugeteilt</th>
      </tr>
  ";

  $verteilt = 0;
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
      "SELECT *
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
      echo "
        <td class='mult'>" . $zuteilung['menge'] * $vorschlag['kan_verteilmult'] . "</td>
        <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
      ";
      $verteilt += $zuteilung['menge'];
    } else {
      echo "
        <td colspan='2' class='warn'>
        FEHLER: $rows Zuteilungen:
      ";
      while( $zuteilung = mysql_fetch_array($zuteilungen) ) {
        echo "
          <br>" . $zuteilung['menge'] * $vorschlag['kan_verteilmult'] 
          . "{$vorschlag['kan_verteileinheit']}
        ";
      }
      echo "</td>";
    }
    echo "</tr>";
  }

  $basar = $vorschlag['liefermenge'] - $verteilt;
  
  echo "
    <tr class='summe'>
      <td><a href='/foodsoft/basar.php'>Basar:</a></td>
      <td class='mult'>" . $basar_festmenge * $vorschlag['kan_verteilmult']
        . " (" . $basar_toleranzmenge * $vorschlag['kan_verteilmult']  . ")</td>
      <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
      <td class='mult'>" . $basar * $vorschlag['kan_verteilmult'] . "</td>
      <td class='unit'>{$vorschlag['kan_verteileinheit']}</td>
    </tr>
  ";

  echo "$print_on_exit";
?>
