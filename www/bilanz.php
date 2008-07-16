<?php
//
// bilanz.php
//

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:Bilanz' );

?> <h1>Bilanz </h1> <?

$gruppen_einzahlungen_ungebucht = sql_select_single_field( "
    SELECT IFNULL( sum( einzahlungen.summe ), 0.0 ) as summe
    FROM ( ".select_ungebuchte_einzahlungen()." ) as einzahlungen
  ", 'summe'
);

$erster_posten = 1;
function rubrik( $name ) {
  global $erster_posten;
  echo "<tr class='rubrik'><th colspan='2'>$name</th></tr>";
  $erster_posten = 1;
}
function posten( $name, $wert ) {
  global $erster_posten, $seitensumme;
  $rounded = sprintf( "%.2lf", $wert );
  $class = ( $rounded < 0 ? 'rednumber' : 'number' );
  printf( "
    <tr class='%s'>
      <td>%s:</td>
      <td class='$class'>%s</td>
    </tr>
    "
  , $erster_posten ? 'ersterposten' : 'posten'
  , $name, $rounded
  );
  $erster_posten = 0;
  $seitensumme += $wert;
}

?>
  <table width='100%'>
    <colgroup>
      <col width='*'><col width='*'>
    </colgroup>
    <tr><th> Aktiva </th><th> Passiva </th></tr>
    <tr>
      <td>

      <table class='inner' width='100%'>
<?

// aktiva:
//

$seitensumme = 0;

rubrik( "Bankguthaben" );
  $kontosalden = sql_bankkonto_salden();
  while( $konto = mysql_fetch_array( $kontosalden ) ) {
    posten(
      fc_alink( 'kontoauszug', array( 'konto_id' => $konto['konto_id'], 'img' => false, 'text' => "Konto {$konto['kontoname']}" ) )
    , $konto['saldo']
    );
  }

  posten( fc_alink( 'gruppen', "img=,optionen=".GRUPPEN_OPT_UNGEBUCHT.",text=Ungebuchte Einzahlungen" ), $gruppen_einzahlungen_ungebucht );

rubrik( "Umlaufvermögen" );
  posten( fc_alink( 'basar', "img=,text=Warenbestand Basar" ), basar_wert_brutto() );
  posten( fc_alink( 'pfandzettel', "img=,text=Bestand Pfandverpackungen" ), lieferantenpfandkontostand() );

rubrik( "Forderungen" );
  posten( fc_alink( 'gruppen', "img=,optionen=".GRUPPEN_OPT_SCHULDEN.",text=Forderungen an Gruppen" ), forderungen_gruppen_summe() );


$aktiva = $seitensumme;


// passiva:
//

?>
    </table>
    </td><td>

    <table class='inner' width='100%'>
<?

$seitensumme = 0;


rubrik( "Einlagen der Gruppen" );
  posten( "Sockeleinlagen", sockel_gruppen_summe() );
  posten( fc_alink( 'gruppen', "img=,optionen=".GRUPPEN_OPT_GUTHABEN.",text=Kontoguthaben" ), verbindlichkeiten_gruppen_summe() );
  posten( fc_alink( 'gruppenpfand', "img=,optionen=".PFAND_OPT_GRUPPEN_INAKTIV.",text=Pfandverpackungen" ), -pfandkontostand() );

$verbindlichkeiten = sql_verbindlichkeiten_lieferanten();
rubrik( "Verbindlichkeiten" );
  while( $vkeit = mysql_fetch_array( $verbindlichkeiten ) ) {
    posten( fc_alink( 'lieferantenkonto', array( 'img' => false, 'lieferanten_id' => $vkeit['lieferanten_id'], 'text' => $vkeit['name'] ) )
    , $vkeit['soll']
    );
  }


$passiva = $seitensumme;

$bilanzverlust = $aktiva - $passiva;
$passiva += $bilanzverlust;

rubrik( "Bilanzausgleich" );
  posten( fc_alink( 'verluste', "text=". ( ( $bilanzverlust > 0 ) ? "Bilanzüberschuss" : "Bilanzverlust" ) )
  , $bilanzverlust
  );

?>
      </table>
      </td>
    </tr>

    <tr class='summe'>
      <td class='number'><? printf( "%.2lf", $aktiva ); ?></td>
      <td class='number'><? printf( "%.2lf", $passiva ); ?></td>
    </tr>

</table>


