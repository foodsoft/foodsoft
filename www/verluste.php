<?php
//
// verluste.php:
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
get_http_var( 'optionen', 'u', 0, true );

$muell_id = sql_muell_id();



$verluste_summe = 0.0;

$option_flag = 1;


?>
<h1>Verlustaufstellung: Achtung, noch unvollstaendig!</h1>

<h2>Differenzen aus Bestellungen:</h2>
  <table width='98%' class='numbers'>
    <tr>
      <th>Bestellung</th>
      <th>Schwund/Müll</th>
      <th colspan='2'>Sonstiges</th>
      <th>Verlust</th>
    </tr>
    <tr>
      <td colspan='5' style='text-align:left;'>
  <?
  if( $optionen & $option_flag ) {
    echo fc_alink( 'self', array( 'img' => 'img/close_black_trans.gif', 'text' => ''
                                , 'optionen' => $optionen ^ $option_flag ) );
    ?> Details: <?
  } else {
    echo fc_alink( 'self', array( 'img' => 'img/open_black_trans.gif', 'text' => 'Details...'
                                           , 'optionen' => $optionen ^ $option_flag ) );
  }
  ?>
      </td>
    </tr>
  <?
  
  $result = doSql( "
    SELECT gesamtbestellungen.*
    , (" .select_bestellungen_soll_gruppen( OPTION_ENDPREIS_SOLL, array( 'gesamtbestellungen', 'bestellgruppen' ) ). ") as muell_soll
    FROM gesamtbestellungen
    JOIN bestellgruppen ON bestellgruppen.id = $muell_id
    HAVING ( extra_soll <> 0 ) OR ( muell_soll <> 0)
    ORDER BY gesamtbestellungen.lieferung
  " );
  
  $muell_soll_summe = 0;
  $extra_soll_summe = 0;
  $soll_summe = 0;
  
  while( $row = mysql_fetch_array( $result ) ) {
    $muell_soll = - $row['muell_soll'];
    $extra_soll = $row['extra_soll'];
    $soll = $muell_soll + $extra_soll;
  
    if( $optionen & $option_flag ) {
      ?>
        <tr>
          <td><? echo fc_alink( 'abrechnung', array( 'bestell_id' => $row['id'], 'text' => $row['name'] ) ); ?></td>
          <td class='number'><? printf( "%.2lf", $muell_soll ); ?></td>
          <td><? echo $row['extra_text']; ?></td>
          <td class='number'><? printf( "%.2lf", $extra_soll ); ?></td>
          <td class='number'><? printf( "%.2lf", $soll ); ?></td>
        </tr>
      <?
    }
    $muell_soll_summe += $muell_soll;
    $extra_soll_summe += $extra_soll;
    $soll_summe += $soll;
  }
  $verluste_summe += $soll_summe;
  ?>
    <tr class='summe'>
      <td>Summe:</td>
      <td class='number'><? printf( "%.2lf", $muell_soll_summe ); ?></td>
      <td>&nbsp;</td>
      <td class='number'><? printf( "%.2lf", $extra_soll_summe ); ?></td>
      <td class='number'><? printf( "%.2lf", $soll_summe ); ?></td>
    </tr>
  </table>
  <?
  $option_flag <<= 1;


function verlust_transaktionen( $typ, $title ) {
  global $option_flag, $optionen, $verluste_summe;
  echo "<a name='label$option_flag'></a>$title";

  $result = sql_verluste( $typ );
  ?>
  <table width='98%' class='numbers'>
    <tr>
      <th>Id</th>
      <th>Valuta</th>
      <th>Notiz</th>
      <th>Betrag</th>
    </tr>
    <tr>
      <td colspan='4' style='text-align:left;'>
  <?
  if( $optionen & $option_flag ) {
    echo fc_alink( 'self', array( 'img' => 'img/close_black_trans.gif', 'text' => ''
                                , 'anchor' => "label$option_flag"
                                , 'optionen' => $optionen ^ $option_flag ) );
    ?> Details: <?
  } else {
    echo fc_alink( 'self', array( 'img' => 'img/open_black_trans.gif', 'text' => 'Details...'
                                , 'anchor' => "label$option_flag"
                                , 'optionen' => $optionen ^ $option_flag ) );
  }
  ?>
      </td>
    </tr>
  <?

  $soll_summe = 0.0;

  while( $row = mysql_fetch_array( $result ) ) {
    $soll = $row['soll'];
    if( $optionen & $option_flag ) {
      ?>
        <tr>
          <td><? echo fc_alink( 'edit_buchung', "transaktion_id={$row['id']},img=,text={$row['id']}" ); ?></td>
          <td><? echo $row['valuta']; ?></td>
          <td><? echo $row['notiz']; ?></td>
          <td class='number'><? printf( "%.2lf", -$soll ); ?></td>
        </tr>
      <?
    }
    $soll_summe += $soll;
  }
  ?>
    <tr class='summe'>
      <td colspan='3' style='text-align:right;'>Summe:</td>
      <td class='number'><? printf( "%.2lf", $soll_summe ); ?></td>
    </tr>
  </table>
  <?
  $verluste_summe += $soll_summe;
  $option_flag <<= 1;
}


verlust_transaktionen( TRANSAKTION_TYP_SONDERAUSGABEN, "<h2 style='margin-top:2em;'>Sonderausgaben</h2>" );


?> <h2 style='margin-top:2em;'>Altlasten:</h2> <?


verlust_transaktionen( TRANSAKTION_TYP_UNDEFINIERT, '<h4>Nicht klassifiziert</h4>' );
verlust_transaktionen( TRANSAKTION_TYP_ANFANGSGUTHABEN, '<h4>Anfangsguthaben</h4>' );
verlust_transaktionen( TRANSAKTION_TYP_BASAR, '<h4>Basar</h4>' );
verlust_transaktionen( TRANSAKTION_TYP_VERLUST, '<h4>Schwund/M&uuml;ll</h4>' );
verlust_transaktionen( TRANSAKTION_TYP_SONSTIGES, '<h4>Sonstiges</h4>' );

