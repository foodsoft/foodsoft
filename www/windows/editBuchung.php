<?

assert( $angemeldet ) or exit();

setWindowSubtitle( 'Buchung edieren' );
setWikiHelpTopic( 'foodsoft:buchung_edieren' );

nur_fuer_dienst_IV();
  // fail_if_readonly();
$editable = ( $dienst == 4 and ! $readonly );

$msg = '';
$problems = '';

if( get_http_var( 'transaktion_id', 'u', NULL, true ) )
  $buchung_id = -$transaktion_id;
else
  need_http_var( 'buchung_id','u', true );

$buchung = sql_get_transaction( $buchung_id ); 
$k_id = $buchung['konterbuchung_id'];
if( ( $k_id > 0 ) and ( $buchung_id < 0 ) ) {
  $h = $buchung_id;
  $buchung_id = $k_id;
  $k_id = $h;
}

get_http_var( 'action', 'w', '' );
$editable or $action = '';
switch( $action ) {
  case 'update':
    need_http_var( 'soll', 'f' );
    need_http_var( 'notiz', 'H' );
    need_http_var( 'day', 'U' );
    need_http_var( 'month', 'U' );
    need_http_var( 'year', 'U' );
    if( $buchung_id > 0 ) {
      need_http_var( "auszug_jahr_1", 'U' );
      need_http_var( "auszug_nr_1", 'U' );
    } else {
      need_http_var( 'typ_1', 'u' );
    }
    if( $k_id > 0 ) {
      need_http_var( "auszug_jahr_2", 'U' );
      need_http_var( "auszug_nr_2", 'U' );
    } else {
      need_http_var( 'typ_2', 'u' );
    }

    break;
}



function show_transaction( $id, $show_common ) {
  global $editable;
  $t = sql_get_transaction( $id );
  $v = preg_split( '/[- ]/',$t['valuta'] );

  if( $show_common ) {
    ?>
      <tr>
        <td><label>Buchung:</label></td><td><kbd><? echo $t['buchungsdatum']; ?></kbd></td>
      </tr>
      <tr>
        <td><label>Valuta:</label></td>
        <td>
          <? if( $editable ) { ?>
            <? date_selector( 'day', $v[2], 'month', $v[1], 'year', $v[0] ); ?>
          <? } else { ?>
            <kbd><? echo $t['valuta']; ?></kbd>
          <? } ?>
        </td>
      </tr>
      <tr class='lastline'>
        <td><label>Notiz:</label></td>
        <td>
          <? if( $editable ) { ?>
            <input name='notiz' type='text' size='40' value='<? echo $t['kommentar']; ?>'>
          <? } else { ?>
            <kbd><? echo $t['kommentar']; ?></kbd>
          <? } ?>
        </td>
      </tr>
    <?
  }

  if( $id > 0 ) {
    ?>
      <tr class='newfield'>
        <th colspan='2'>Bank-Transaktion (<? echo $id; ?>)</th>
      </tr>
      <tr>
        <td><label>Konto:</label></td><td><? echo $t['kontoname']; ?></td>
      </tr>
      <tr>
        <td><label>Auszug:</label></td>
        <td class='number'>
        <? if( $editable )  { ?>
          <input name='auszug_jahr_<? echo $id; ?>' type='text' size='4' value='<? echo $t['kontoauszug_jahr']; ?>'>
            /
          <input name='auszug_nr_<? echo $id; ?>' type='text' size='2' value='<? echo $t['kontoauszug_nr']; ?>'>
        <? } else { ?>
          <kbd><? echo "{$t['kontoauszug_nr']} / {$t['kontoauszug_jahr']}"; ?></kbd>
        <? } ?>
        </td>
      </tr>
      <tr class='lastline'>
        <td><label>Soll FC:</label></td>
        <td class='number'>
          <? if( $editable and $show_common ) { ?>
            <input name='soll' type='text' size='6' value='<? printf( "%.2lf", -$t['haben'] ); ?>'>
        <? } else { ?>
          <kbd><? printf( "%.2lf", -$t['haben'] ); ?></kbd>
        <? } ?>
        </td>
      </tr>
    <?
  } else {
    $id = -$id;
    $gruppen_id = $t['gruppen_id'];
    $lieferanten_id = $t['lieferanten_id'];
    if( $lieferanten_id > 0 ) {
      ?>
        <tr class='newfield'>
          <th colspan='2'>Lieferanten-Transaktion (<? echo $id; ?>)</th>
        </tr>
        <tr>
          <td><label>Lieferant:</label></td><td><kbd><? printf( "%s", lieferant_name( $lieferanten_id ) ); ?></kbd></td>
        </tr>
      <?
    } else if( $gruppen_id == sql_muell_id() ) {
      ?>
        <tr class='newfield'>
          <th colspan='2'>Interne Verrechnung (<? echo $id; ?>)</th>
        </tr>
        <tr>
          <td><label>Typ:</label></td>
          <td>
            <?
              $typ = $t['transaktionstyp'];
              $options = '';
              $selected = false;
              foreach( array( TRANSAKTION_TYP_ANFANGSGUTHABEN, TRANSAKTION_TYP_SPENDE, TRANSAKTION_TYP_SONDERAUSGABEN, TRANSAKTION_TYP_VERLUST
                              , TRANSAKTION_TYP_SONSTIGES ) as $tt ) {
                $options .= "<option value='".$tt."'";
                if( $tt == $typ ) {
                  echo " selected";
                  $selected = true;
                }
                $options .= ">" . transaktion_typ_string($tt) . "</option>";
              }
              if( ! $selected ) {
                $options = "<option value=''>(bitte Typ wählen)</option>$options";
              }
              if( $selected or ( $typ == TRANSAKTION_TYP_UNDEFINIERT ) ) {
                ?> <select name='typ'> <?
                echo $options
                ?> </select> <?
              } else {
                echo "<kbd>" .transaktion_typ_string($typ)."</kbd>";
              }
            ?>
          </td>
        </tr>
      <?
    } else {
      ?>
        <tr class='newfield'>
          <th colspan='2'>Gruppen-Transaktion (<? echo $id; ?>)</th>
        </tr>
        <tr>
          <td><label>Gruppe:</label></td><td><kbd><? printf( "%s (%s)", sql_gruppenname( $gruppen_id ), $gruppen_id ); ?></kbd></td>
        </tr>
      <?
    }
    ?>
      <tr class='lastline'>
        <td><label>Soll FC:</label></td><td><kbd><? printf( " %.2lf", -$t['haben'] ); ?></kbd></td>
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
    <table style="width:350px;" class='form'>
      <? show_transaction( $buchung_id, true ); ?>
      <tr><td colspan='2'></td></tr>
      <? show_transaction( $k_id, false ); ?>
      <tr class='newfield'>
        <td colspan='2' class='text-align:right;'>
          <input type='submit' value='&Auml;ndern'>
        </td>
      </tr>
      <tr>
      </tr>
    </table>
  </fieldset>
</form>


