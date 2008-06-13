<?php
//
// bilanz.php
//

assert( $angemeldet ) or exit();

?> <h1>Bilanz <!-- - <blink>Achtung, in Arbeit: Werte stimmen nicht!</blink> --></h1> <?

// aktiva berechnen:
//

if( ! isset( $inventur_datum ) )
  $inventur_datum = "(keine)";
if( ! isset( $inventur_pfandwert ) )
  $inventur_pfandwert = 0.0;


$row = sql_select_single_row( "
  SELECT sum( summe ) as summe
  FROM gruppen_transaktion
  WHERE (gruppen_id>0) and (konterbuchung_id=0)
" );
$gruppen_einzahlungen_ungebucht = $row['summe'];


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
  global $erster_posten, $seitensumme;
  $wert += 0.00499;
  $class = ( $wert < 0 ? 'rednumber' : 'number' );
  printf( "
    <tr class='%s'>
      <td>%s:</td>
      <td class='$class'>%.2lf</td>
    </tr>
    "
  , $erster_posten ? 'ersterposten' : 'posten'
  , $name, $wert
  );
  $erster_posten = 0;
  $seitensumme += $wert;
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


$seitensumme = 0;

rubrik( "Bankguthaben" );
  $kontosalden = sql_bankkonto_salden();
  while( $konto = mysql_fetch_array( $kontosalden ) ) {
    posten( "
      <a href=\"javascript:neuesfenster('index.php?window=konto&konto_id={$konto['konto_id']}','konto');\"
      >Konto {$konto['kontoname']}</a>"
    , $konto['saldo']
    );
  }

  posten( "<a href=\"javascript:neuesfenster('index.php?window=gruppen&optionen=" . GRUPPEN_OPT_UNGEBUCHT . "','gruppen');\">Ungebuchte Einzahlungen</a>", $gruppen_einzahlungen_ungebucht );

rubrik( "Umlaufvermögen" );
  posten( "<a href=\"javascript:neuesfenster('index.php?window=basar','basar');\">Warenbestand Basar</a>", basar_wert_brutto() );
  posten( "<a href=\"javascript:neuesfenster('index.php?window=pfandverpackungen','pfandzettel');\">Bestand Pfandverpackungen</a>", lieferantenpfandkontostand() );

rubrik( "Forderungen" );
  posten( "<a href=\"javascript:neuesfenster('index.php?window=gruppen&optionen=" . GRUPPEN_OPT_SCHULDEN . "','gruppen');\">Forderungen an Gruppen</a>", forderungen_gruppen_summe() );


$aktiva = $seitensumme;


//
// ab hier passiva:
//
echo "
    </table>
    </td><td>

    <table class='inner' width='100%'>
";

$seitensumme = 0;


rubrik( "Einlagen der Gruppen" );
  posten( "Sockeleinlagen", sockel_gruppen_summe() );
  posten( "<a href=\"javascript:neuesfenster('index.php?window=gruppen&optionen=" . GRUPPEN_OPT_GUTHABEN . "','gruppen');\">Kontoguthaben</a>", verbindlichkeiten_gruppen_summe() );
  posten( "<a href=\"javascript:neuesfenster('index.php?window=gruppenpfand&optionen=" . PFAND_OPT_GRUPPEN_INAKTIV . "','gruppenpfand');\">Pfandverpackungen</a>", -pfandkontostand() );

$verbindlichkeiten = sql_verbindlichkeiten_lieferanten();
rubrik( "Verbindlichkeiten" );
  while( $vkeit = mysql_fetch_array( $verbindlichkeiten ) ) {
    posten( "
      <a href=\"javascript:neuesfenster('index.php?window=lieferantenkonto&lieferanten_id={$vkeit['lieferanten_id']}','lieferantenkonto');\"
      >{$vkeit['name']}</a>"
    , $vkeit['soll']
    );
  }


$passiva = $seitensumme;

$bilanzverlust = $aktiva - $passiva;
$passiva += $bilanzverlust;

rubrik( "Bilanzausgleich" );
  posten( ( $bilanzverlust > 0 ) ? "Bilanzüberschuss" : "Bilanzverlust", $bilanzverlust );

echo "
      </table>
      </td>
    </tr>
";

printf( "
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

