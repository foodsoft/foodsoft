<?

assert( $angemeldet ) or exit();

setWindowSubtitle( 'Buchung edieren' );
setWikiHelpTopic( 'foodsoft:buchung_edieren' );

nur_fuer_dienst_IV();
  // fail_if_readonly();
$editable = ( $dienst == 4 and ! $readonly );
$ro_tag = ( $editable ? '' : 'readonly' );

$msg = '';
$problems = '';

if( get_http_var( 'transaktion_id', 'u', NULL, true ) )
  $buchung_id = -$transaktion_id;
else
  need_http_var( 'buchung_id','u', true );

$buchung = sql_get_transaction( $buchung_id ); 
$k_id = $buchung['konterbuchung_id'];



function show_transaction( $id ) {
  $t = sql_get_transaction( $id );
  if( $id > 0 ) {
    ?>
      <tr>
        <th colspan='2'>Bank-Transaktion</th>
      </tr>
      <tr>
        <td><label>Konto:</label></td><td><? echo $t['kontoname']; ?></td>
      </tr>
      <tr>
        <td><label>Auszug:</label></td><td><? echo "{$t['kontoauszug_nr']} / {$t['kontoauszug_jahr']}"; ?></td>
      </tr>
      <tr>
        <td><label>Buchung:</label></td><td><? echo $t['buchungsdatum']; ?></td>
      </tr>
      <tr>
        <td><label>Valuta:</label></td><td><? echo $t['valuta']; ?></td>
      </tr>
      <tr>
        <td><label>Notiz:</label></td><td><? echo $t['kommentar']; ?></td>
      </tr>
      <tr>
        <td><label>Soll FC:</label></td><td><? printf( "%.2lf", -$t['haben'] ); ?></td>
      </tr>
    <?
  } else {
    $gruppen_id = $t['gruppen_id'];
    $lieferanten_id = $t['lieferanten_id'];
    if( $lieferanten_id > 0 ) {
      ?>
        <tr>
          <th colspan='2'>Lieferanten-Transaktion</th>
        </tr>
        <tr>
          <td><label>Lieferant:</label></td><td><? printf( "%s", lieferant_name( $lieferanten_id ) ); ?></td>
        </tr>
      <?
    } else {
      ?>
        <tr>
          <th colspan='2'>Gruppen-Transaktion</th>
        </tr>
        <tr>
          <td><label>Gruppe:</label></td><td><? printf( "%s (%s)", sql_gruppenname( $gruppen_id ), $gruppen_id ); ?></td>
        </tr>
      <?
    }
      ?>
      <tr>
        <td><label>Buchung:</label></td><td><? echo $t['buchungsdatum']; ?></td>
      </tr>
      <tr>
        <td><label>Valuta:</label></td><td><? echo $t['valuta']; ?></td>
      </tr>
      <tr>
        <td><label>Notiz:</label></td><td><? echo $t['kommentar']; ?></td>
      </tr>
      <tr>
        <td><label>Soll FC:</label></td><td><? printf( "%.2lf", -$t['haben'] ); ?></td>
      </tr>
    <?
  }
}


?>
<form action='<? echo self_url(); ?>' method='post' class='small_form'>
  <? echo self_post(); ?>
  <input type='hidden' name='action' value='update'>
  <fieldset style='width:450px;' class='small_form'>
    <legend>Buchung:</legend>
    <? echo $msg; echo $problems; ?>
    <table style="width:350px;">
      <? show_transaction( $buchung_id ); ?>
      <tr><td colspan='2'></td></tr>
      <? show_transaction( $k_id ); ?>
      <tr>
        <td colspan='2' align='center'><input type='submit' value='&Auml;ndern'></td>
      </tr>
    </table>
  </fieldset>
</form>


