<?php
//
// bilanz.php
//

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:Bilanz' );

?> <h1>Bilanz </h1> <?php

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

        rubrik( "Bankguthaben" );
          $kontosalden = sql_bankkonto_salden();
          while( $konto = mysql_fetch_array( $kontosalden ) ) {
            posten(
              fc_link( 'kontoauszug', array( 'konto_id' => $konto['konto_id'], 'class' => 'href', 'text' => "Konto {$konto['kontoname']}" ) )
            , $konto['saldo']
            );
          }
          posten( fc_link( 'gruppen', "class=href,optionen=".GRUPPEN_OPT_UNGEBUCHT.",text=Ungebuchte Einzahlungen" ), $gruppen_einzahlungen_ungebucht );

        rubrik( "Umlaufvermögen" );
          posten( fc_link( 'basar', "class=href,text=Warenbestand Basar" ), basar_wert_brutto() );
          posten( fc_link( 'pfandzettel', "class=href,text=Bestand Pfandverpackungen" ), lieferantenpfandkontostand() );

        rubrik( "Forderungen" );
          posten( fc_link( 'gruppen', "class=href,optionen=".GRUPPEN_OPT_SCHULDEN.",text=Forderungen an Gruppen" ), forderungen_gruppen_summe() );

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
          posten( fc_link( 'verlust_details', array( 'detail' => TRANSAKTION_TYP_SOCKEL, 'text' => "Sockeleinlagen", 'class' => 'href' ) ), sockeleinlagen() );
          posten( fc_link( 'gruppen', "class=href,optionen=".GRUPPEN_OPT_GUTHABEN.",text=Kontoguthaben" ), verbindlichkeiten_gruppen_summe() );
          posten( fc_link( 'gruppenpfand', "class=href,optionen=".PFAND_OPT_GRUPPEN_INAKTIV.",text=Pfandverpackungen" ), -pfandkontostand() );

        rubrik( "Verbindlichkeiten" );
          foreach( sql_verbindlichkeiten_lieferanten() as $vkeit ) {
            posten( fc_link( 'lieferantenkonto', array( 'class' => 'href', 'lieferanten_id' => $vkeit['lieferanten_id'], 'text' => $vkeit['name'] ) )
            , $vkeit['soll']
            );
          }

        $passiva = $seitensumme;

        $bilanzverlust = $aktiva - $passiva;
        $passiva += $bilanzverlust;

        rubrik( "Bilanzausgleich" );
          posten( fc_link( 'verluste', "class=href,text=". ( ( $bilanzverlust > 0 ) ? "Bilanzüberschuss" : "Bilanzverlust" ) )
          , $bilanzverlust
          );

      close_table();
      medskip();

  open_tr( 'summe posten' );
    open_th( '', '', price_view( $aktiva ) );
    open_th( '', '', price_view( $passiva ) );

close_table();

?>
