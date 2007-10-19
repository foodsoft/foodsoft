<?php
  //
  // bilanz.php
  //

  error_reporting(E_ALL);

  if( ! $angemeldet ) {
    exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
  } 

  ?> <h1>Bilanz</h1> <?

  isset($basar_id) or $basar_id = 99;
  isset($muell_id) or $muell_id = 13;

  // aktiva berechnen:
  //

  if( ! isset( $inventur_datum ) )
    $inventur_datum = "(keine)";
  if( ! isset( $inventur_pfandwert ) )
    $inventur_pfandwert = 0.0;

  if( ! isset( $kontostand_datum ) )
    $kontostand_datum = "(keine)";
  if( ! isset( $kontostand_wert ) )
    $kontostand_wert = 0.0;

  $basar_wert = 0.0;
  $basar = sql_basar();

  
  while( $row = mysql_fetch_array( $basar ) ) {
    // print_r( $row );
    $basar_wert += $row['basar'] * $row['preis'];
  }

  $basar_2 = kontostand( $basar_id );

  $abschreibung = kontostand( $muell_id );

  $gruppen_guthaben = 0.0;
  $gruppen_forderungen = 0.0;
  
  // passiva berechnen:
  //

  $gruppen = sql_gruppen();
  while( $gruppe = mysql_fetch_array($gruppen) ) {
    $w = kontostand( $gruppe['id'] );
    if( $w > 0 )
      $gruppen_guthaben += $w;
    else
      $gruppen_forderungen -= $w;
  }

  $verbindlichkeiten = doSql( "
    SELECT lieferanten.id as id
         , lieferanten.name as name
         , sum( bestellvorschlaege.liefermenge * produktpreise.preis ) as schuld
    FROM gesamtbestellungen
    INNER JOIN bestellvorschlaege
      ON bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id
    INNER JOIN produkte
      ON produkte.id = bestellvorschlaege.produkt_id
    INNER JOIN produktpreise
      ON produktpreise.id = bestellvorschlaege.produktpreise_id
    INNER JOIN lieferanten
      ON lieferanten.id = produkte.lieferanten_id
    WHERE gesamtbestellungen.state = 'Verteilt' and isnull(gesamtbestellungen.bezahlung) 
    GROUP BY lieferanten.id
    HAVING schuld <> 0;
  ", LEVEL_IMPORTANT, "fehler: " );


  $aktiva = 0;
  $passiva = 0;

  echo "
    <table width='100%'>
      <colgroup>
        <col width='*'>
        <col width='*'>
      </colgroup>
      <tr>
        <th> Aktiva </th>
        <th> Passiva </th>
      </tr>
      <tr>
        <td>

        <table class='inner' width='100%'>
          <tr>
            <th>Bankguthaben:</th>
          </tr>
          <tr>
            <td class='number'>" . sprintf( "%8.2lf", $kontostand_wert ) . "</td>
          </tr>
    ";
    $aktiva += $kontostand_wert;

    printf( "
          <tr>
            <th>Warenbestand Basar:</th>
          </tr>
          <tr>
            <td class='number'>%.2lf</td>
          </tr>
      "
    , $basar_wert
    );
    $aktiva += $basar_wert;

    printf( "

          <tr>
            <th>Forderungen an Gruppen:</th>
          </tr>
          <tr>
            <td class='number'>%.2lf</td>
          </tr>
        </table>
      "
    , $gruppen_forderungen
    );
    
    $aktiva += $gruppen_forderungen;

    echo "
        </td><td>

        <table class='inner' width='100%'>
    ";

    printf( "
          <tr>
            <th>Einlagen der Gruppen:</th>
          </tr>
          <tr>
            <td class='number'>%.2lf</td>
          </tr>
      "
    , $gruppen_guthaben
    );
    $passiva += $gruppen_guthaben;

    echo "

          <tr>
            <th>Verbindlichkeiten:</th>
          </tr>
  ";
  while( $vkeit = mysql_fetch_array( $verbindlichkeiten ) ) {
    printf( "
      <tr>
        <td>%s:</td>
      </tr>
      <tr>
        <td class='number'>%.2lf</td>
      </tr>
      "
    , $vkeit['name']
    , $vkeit['schuld']
    );
    $passiva += $vkeit['schuld'];
  }

  echo "
        </table>
        </td>
      </tr>
  ";

  printf ("
      <tr class='summe'>
        <td class='number'>%.2lf</td>
        <td class='number'>%.2lf</td>
      </tr>
    "
  , $aktiva
  , $passiva
  );

  echo "</table>";

?>

