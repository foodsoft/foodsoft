<?php
  //
  // bilanz.php
  //

  error_reporting(E_ALL);

  if( ! $angemeldet ) {
    exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
  } 

  ?> <h1>Bilanz</h1> <?

  // aktiva berechnen:
  //

  if( ! isset( $inventur_datum ) )
    $inventur_datum = "(keine)";
  if( ! isset( $inventur_pfandwert ) )
    $inventur_pfandwert = 0.0;

  $kontosalden = sql_saldo();

  $basar_wert = 0.0;
  $basar = sql_basar();

  while( $row = mysql_fetch_array( $basar ) ) {
    // print_r( $row );
    $basar_wert += $row['basar'] * $row['preis'];
  }

  $basar_2 = kontostand( $basar_id );

  $abschreibung = kontostand( $muell_id );

  $result = doSQL( "
    SELECT sum( summe ) as summe
    FROM gruppen_transaktion
    WHERE (type=0) and (kontoauszugs_nr<=0)
  " );
  $row = mysql_fetch_array( $result );
  $gruppen_einzahlungen_ungebucht = $row['summe'];
  
  // passiva berechnen:
  //

  $gruppen_guthaben = 0.0;
  $gruppen_forderungen = 0.0;
  $gruppen_sockel = 0.0;
  $gruppen = sql_gruppen();
  while( $gruppe = mysql_fetch_array($gruppen) ) {
    $w = kontostand( $gruppe['id'] );
    $gruppen_sockel += $sockelbetrag * $gruppe['mitgliederzahl'];
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
  " );


  $aktiva = 0;
  $passiva = 0;


  $erster_posten = 1;
  function rubrik( $name ) {
    global $erster_posten;
    echo "
      <tr class='rubrik'>
        <th colspan='2'>$name</th>
      </tr>
    ";
    $erster_posten = 1;
  }
  function posten( $name, $wert ) {
    global $erster_posten;
    printf( "
      <tr class='%s'>
        <td>%s:</td>
        <td class='number'>%.2lf</td>
      </tr>
      "
    , $erster_posten ? 'ersterposten' : 'posten'
    , $name, $wert
    );
    $erster_posten = 0;
  }

  echo "
    <table width='100%'>
      <colgroup>
        <col width='*'><col width='*'>
      </colgroup>
      <tr><th> Aktiva </th><th> Passiva </th></tr>
      <tr>
        <td>

        <table class='inner' width='100%'>
  ";

  rubrik( "Bankguthaben" );
  while( $konto = mysql_fetch_array( $kontosalden ) ) {
    posten( "
      <a href=\"javascript:neuesfenster('index.php?window=konto&konto_id={$konto['konto_id']}','konto');\"
      >Konto {$konto['name']}</a>"
    , $konto['saldo']
    );
    $aktiva += $konto['saldo'];
  }
  posten( "Ungebuchte Einzahlungen", $gruppen_einzahlungen_ungebucht );
  $aktiva += $gruppen_einzahlungen_ungebucht;

  rubrik( "Umlaufvermögen" );
  posten( "Warenbestand Basar", $basar_wert );
  posten( "Bestand Pfandverpackungen", $inventur_pfandwert );
  $aktiva += ( $basar_wert + $inventur_pfandwert );

  rubrik( "Forderungen" );
  posten( "Forderungen an Gruppen", $gruppen_forderungen );
  $aktiva += $gruppen_forderungen;

  //
  // ab hier passiva:
  //
  echo "
      </table>
      </td><td>

      <table class='inner' width='100%'>
  ";

  rubrik( "Einlagen der Gruppen" );
  posten( "Sockeleinlagen", $gruppen_sockel );
  posten( "Kontoguthaben", $gruppen_guthaben );
  $passiva += ( $gruppen_guthaben + $gruppen_sockel );

  rubrik( "Verbindlichkeiten" );
  while( $vkeit = mysql_fetch_array( $verbindlichkeiten ) ) {
    posten( $vkeit['name'], $vkeit['schuld'] );
    $passiva += $vkeit['schuld'];
  }

  $bilanzverlust = $aktiva - $passiva;
  $passiva += $bilanzverlust;

  rubrik( "Bilanzausgleich" );
  posten( ( $bilanzverlust > 0 ) ? "Bilanzüberschuss" : "Bilanzverlust", $bilanzverlust );

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

