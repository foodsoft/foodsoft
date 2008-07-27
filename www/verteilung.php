<?php

// verteilung.php
//
// um die bestellungen nach produkten sortiert zu sehen ....

//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();
need_http_var('bestell_id','u',true);
get_http_var('produkt_id','u',0, true);

$status = getState( $bestell_id );

$ro_tag = '';
if( $status != STATUS_VERTEILT ) {
  $ro_tag = 'readonly';
}

nur_fuer_dienst(1,3,4,5);

setWikiHelpTopic( "foodsoft:verteilung" );

if( $produkt_id ) {
  ?> <h1>Produktverteilung</h1> <?
} else {
  ?> <h1>Verteilliste</h1> <?
}
bestellung_overview( sql_bestellung( $bestell_id ) );

?> <div style='padding-top:2em;'> <?

if( ! $ro_tag ) {
  ?> <form action="<? echo self_url(); ?>" method="post"><?
  echo self_post();
}

distribution_tabellenkopf(); 

$produkte = sql_bestellprodukte( $bestell_id, 0, $produkt_id );

while( $produkt = mysql_fetch_array( $produkte ) ) {
  if( ( $produkt['liefermenge'] < 0.5 ) and ( $produkt['verteilmenge'] < 0.5 ) )
    continue;
  $produkt_id = $produkt['produkt_id'];

  distribution_produktdaten( $bestell_id, $produkt_id );
  distribution_view( $bestell_id, $produkt_id, ! $ro_tag );
  ?> <tr> <td colspan='6'>&nbsp;</td></tr> <?
}

if( 0 ) {
  ?>
  <tr style='border:none'>
    <td colspan='6' style='border:none;'>
      <input type='submit' value=' speichern '>
      <input type='reset' value=' &Auml;nderungen zur&uuml;cknehmen'>
    </td>
  </tr>
  <?
}

?> </table> <?

if( ! $ro_tag ) {
  floating_submission_button( 'reminder' );
  ?> </form> <?
  echo self_post();
}

?>
</div>

