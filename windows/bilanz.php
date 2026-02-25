<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

//
// bilanz.php
//

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:Bilanz' );

?> <h1>Bilanz </h1> <?php
benchmarkTimestamp(__LINE__);

$gruppen_einzahlungen_ungebucht = sql_ungebuchte_einzahlungen_summe();

$erster_posten = 1;
function rubrik( $name ) {
  global $erster_posten;
  open_tr( 'rubrik' );
    open_th( '', "colspan='2'", "<div>$name</div>" );
  $erster_posten = 1;
}
function posten( $name, $wert ) {
  global $erster_posten, $seitensumme;
  $rounded = sprintf( "%.2lf", $wert );
  open_tr( $erster_posten ? 'ersterposten' : 'posten' );
    open_td( '', '', $name );
    open_td( ( $rounded < 0 ? 'rednumber' : 'number' ), '', $rounded );
  $erster_posten = 0;
  $seitensumme += $wert;
}

benchmarkTimestamp(__LINE__);
$forderungen_verblindlichkeiten_gruppen = forderungen_verbindlichkeiten_gruppen_summe();
benchmarkTimestamp(__LINE__);

open_table( 'layout hfill' );
  ?> <colgroup><col width='*'><col width='*'></colgroup> <?php
  open_th( '', "style='padding:4px;'", 'Aktiva' ); open_th( '', "style='padding:4px;'", 'Passiva' );
  open_tr();

    //////////////////
    //  aktiva:
    //
    open_td();
      smallskip();
      open_table( 'inner hfill' );
        $seitensumme = 0;
        benchmarkTimestamp(__LINE__);
        rubrik( "Bankguthaben" );
          $kontosalden = sql_bankkonto_salden();
        benchmarkTimestamp(__LINE__);
          while( $konto = mysqli_fetch_array( $kontosalden ) ) {
            posten(
              fc_link( 'kontoauszug', array( 'konto_id' => $konto['konto_id'], 'class' => 'href', 'text' => "Konto {$konto['kontoname']}" ) )
            , $konto['saldo']
            );
          }
          posten( fc_link( 'gruppen', "class=href,optionen=".GRUPPEN_OPT_UNGEBUCHT.",text=Ungebuchte Einzahlungen" ), $gruppen_einzahlungen_ungebucht );

        rubrik( "Umlaufvermögen" );
          benchmarkTimestamp(__LINE__);
          posten( fc_link( 'basar', "class=href,text=Warenbestand Basar" ), basar_wert_brutto() );
          benchmarkTimestamp(__LINE__);
          posten( fc_link( 'pfandzettel', "class=href,text=Bestand Pfandverpackungen" ), lieferantenpfandkontostand() );
          benchmarkTimestamp(__LINE__); // SLOW 0.59

        rubrik( "Forderungen" );
          benchmarkTimestamp(__LINE__);
          posten( fc_link( 'gruppen', "class=href,optionen=".GRUPPEN_OPT_SCHULDEN.",text=Forderungen an Gruppen" ), $forderungen_verblindlichkeiten_gruppen['forderungen'] );
          benchmarkTimestamp(__LINE__); // SLOW 2.24

        $aktiva = $seitensumme;

      close_table();
      medskip();

    //////////////////
    //  passiva:
    //
    open_td();
      smallskip();
      open_table( 'inner hfill' );
        $seitensumme = 0;

        rubrik( "Einlagen der Gruppen" );
          benchmarkTimestamp(__LINE__);
          posten( fc_link( 'verlust_details', array( 'detail' => TRANSAKTION_TYP_SOCKEL, 'text' => "Sockeleinlagen", 'class' => 'href' ) ), sockeleinlagen() );
          benchmarkTimestamp(__LINE__);
          posten( fc_link( 'gruppen', "class=href,optionen=".GRUPPEN_OPT_GUTHABEN.",text=Kontoguthaben" ), $forderungen_verblindlichkeiten_gruppen['verbindlichkeiten'] );
          benchmarkTimestamp(__LINE__); // SLOW 2.3
          posten( fc_link( 'gruppenpfand', "class=href,optionen=".PFAND_OPT_GRUPPEN_INAKTIV.",text=Pfandverpackungen" ), -pfandkontostand() );
          benchmarkTimestamp(__LINE__); // SLOW 0.74

        rubrik( "Verbindlichkeiten" );
          benchmarkTimestamp(__LINE__);
          foreach( sql_verbindlichkeiten_lieferanten() as $vkeit ) {
            posten( fc_link( 'lieferantenkonto', array( 'class' => 'href', 'lieferanten_id' => $vkeit['lieferanten_id'], 'text' => $vkeit['name'] ) )
            , $vkeit['soll']
            );
          }
        benchmarkTimestamp(__LINE__);

        $passiva = $seitensumme;

        $bilanzverlust = $aktiva - $passiva;
        $passiva += $bilanzverlust;

        rubrik( "Bilanzausgleich" );
          benchmarkTimestamp(__LINE__);
          posten( fc_link( 'verluste', "class=href,text=". ( ( $bilanzverlust > 0 ) ? "Bilanzüberschuss" : "Bilanzverlust" ) )
          , $bilanzverlust
          );
          benchmarkTimestamp(__LINE__);

      close_table();
      medskip();

  open_tr( 'summe posten' );
    open_th( '', '', price_view( $aktiva ) );
    open_th( '', '', price_view( $passiva ) );

close_table();
benchmarkTimestamp();
showBenchmark();

?>
