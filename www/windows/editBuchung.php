<?

assert( $angemeldet ) or exit();

setWindowSubtitle( 'Buchung edieren' );
setWikiHelpTopic( 'foodsoft:buchung_edieren' );

// nur_fuer_dienst_IV();
// fail_if_readonly();
$editable = ( $dienst == 4 and ! $readonly );

$msg = '';
$problems = '';

$muell_id = sql_muell_id();

$selectable_types = array(
  TRANSAKTION_TYP_ANFANGSGUTHABEN
, TRANSAKTION_TYP_SPENDE
, TRANSAKTION_TYP_SONDERAUSGABEN
/// , TRANSAKTION_TYP_VERLUST
, TRANSAKTION_TYP_UMLAGE
, TRANSAKTION_TYP_AUSGLEICH_ANFANGSGUTHABEN
, TRANSAKTION_TYP_AUSGLEICH_SONDERAUSGABEN
, TRANSAKTION_TYP_AUSGLEICH_BESTELLVERLUSTE
);

if( get_http_var( 'transaktion_id', 'u', NULL, true ) )
  $buchung_id = -$transaktion_id;
else
  need_http_var( 'buchung_id','u', true );

$buchung = sql_get_transaction( $buchung_id ); 
$k_id = $buchung['konterbuchung_id'];

// waehle eine (hoffentlich) leicht verstaendliche / kanonische reihenfolge der beiden Buchungen:
//
if( $buchung_id < 0 ) {
  if( $k_id > 0 ) { //  wenn eine bank-transaktion dabei: diese zuerst anzeigen!
    $h = $buchung_id;
    $buchung_id = $k_id;
    $k_id = $h;
  } else {  // beides sind gruppen-transaktionen
    // geparkte sockel-einlage moeglichst als zweites anzeigen:
    if( ( $buchung['gruppen_id'] == $muell_id ) and ( $buchung['transaktionstyp'] == TRANSAKTION_TYP_SOCKEL ) ) {
      $h = $buchung_id;
      $buchung_id = $k_id;
      $k_id = $h;
    }
  }
}
$self_fields['buchung_id'] = $buchung_id;   // moeglicherweise getauscht

get_http_var( 'action', 'w', '' );
$editable or $action = '';
switch( $action ) {
  case 'update':
    need_http_var( 'id_1', 'd' ); need( $id_1 == $buchung_id );
    need_http_var( 'id_2', 'd' ); need( $id_2 == $k_id );
    need( $dienstkontrollblatt_id );
    $b1 = sql_get_transaction( $id_1 );
    $b2 = sql_get_transaction( $id_2 );
    if( get_http_var( 'haben', 'f' ) ) {
      $soll = - $haben;
    } else {
      need_http_var( 'soll', 'f' );
    }
    need_http_var( 'notiz', 'H' );
    need_http_var( 'vday', 'U' );
    need_http_var( 'vmonth', 'U' );
    need_http_var( 'vyear', 'U' );
    $mod_1 = array( 'dienstkontrollblatt_id' => $dienstkontrollblatt_id );
    $mod_2 = array( 'dienstkontrollblatt_id' => $dienstkontrollblatt_id );
    if( $id_1 > 0 ) {
      need_http_var( "auszug_jahr_1", 'U' );
      need_http_var( "auszug_nr_1", 'U' );
      $mod_1['kommentar'] = $notiz;
      $mod_1['valuta'] = "$vyear-$vmonth-$vday";
      $mod_1['kontoauszug_jahr'] = $auszug_jahr_1;
      $mod_1['kontoauszug_nr'] = $auszug_nr_1;
      $mod_1['betrag'] = - $soll;
    } else {
      $mod_1['notiz'] = $notiz;
      $mod_1['kontobewegungs_datum'] = "$vyear-$vmonth-$vday";
      $mod_1['summe'] = $soll;
      if( $b1['gruppen_id'] == $muell_id ) {
        if( in_array( $b1['transaktionstyp'], $selectable_types ) or ( $b1['transaktionstyp'] == TRANSAKTION_TYP_UNDEFINIERT ) ) {
          need_http_var( 'typ_1', 'U' );
          need( in_array( $typ_1, $selectable_types ) );
          $mod_1['type'] = $typ_1;
        }
      }
    }
    if( $id_2 > 0 ) {
      need_http_var( "auszug_jahr_2", 'U' );
      need_http_var( "auszug_nr_2", 'U' );
      $mod_2['kommentar'] = $notiz;
      $mod_2['valuta'] = "$vyear-$vmonth-$vday";
      $mod_2['kontoauszug_jahr'] = $auszug_jahr_2;
      $mod_2['kontoauszug_nr'] = $auszug_nr_2;
      $mod_2['betrag'] = $soll;
    } else {
      $mod_2['notiz'] = $notiz;
      $mod_2['kontobewegungs_datum'] = "$vyear-$vmonth-$vday";
      $mod_2['summe'] = - $soll;
      if( $b2['gruppen_id'] == $muell_id ) {
        if( in_array( $b2['transaktionstyp'], $selectable_types ) or ( $b2['transaktionstyp'] == TRANSAKTION_TYP_UNDEFINIERT ) ) {
          need_http_var( 'typ_2', 'U' );
          need( in_array( $typ_2, $selectable_types ) );
          $mod_2['type'] = $typ_2;
        }
      }
    }
    if( $id_1 > 0 ) {
      sql_update( 'bankkonto', $id_1, $mod_1 );
    } else {
      sql_update( 'gruppen_transaktion', -$id_1, $mod_1 );
    }
    if( $id_2 > 0 ) {
      sql_update( 'bankkonto', $id_2, $mod_2 );
    } else {
      sql_update( 'gruppen_transaktion', -$id_2, $mod_2 );
    }
    break;
}



function show_transaction( $id, $tag ) {
  global $editable, $selectable_types, $muell_id;

  $t = sql_get_transaction( $id );
  $v = preg_split( '/[- ]/',$t['valuta'] );

  $haben = $t['haben'];
  $soll = -$haben;

  echo "<input type='hidden' name='id_$tag' value='$id'>";

  if( $tag == 1 ) {
    ?>
      <tr>
        <td><label>Buchung:</label></td>
        <td>
          <div><kbd><? echo $t['buchungsdatum']; ?></kbd></div>
          <div class='small'><? echo $t['dienst_name']; ?></div>
        </td>
      </tr>
      <tr>
        <td><label>Valuta:</label></td>
        <td>
          <? if( $editable ) { ?>
            <? date_selector( 'vday', $v[2], 'vmonth', $v[1], 'vyear', $v[0] ); ?>
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
        <th colspan='2'>Bank-Transaktion <span class='small'><? echo $id; ?></span></th>
      </tr>
      <tr>
        <td><label>Konto:</label></td><td><? echo $t['kontoname']; ?></td>
      </tr>
      <tr>
        <td><label>Auszug:</label></td>
        <td class='number'>
        <? if( $editable )  { ?>
          <input name='auszug_jahr_<? echo $tag; ?>' type='text' size='4' value='<? echo $t['kontoauszug_jahr']; ?>'>
            /
          <input name='auszug_nr_<? echo $tag; ?>' type='text' size='2' value='<? echo $t['kontoauszug_nr']; ?>'>
        <? } else { ?>
          <kbd><? echo "{$t['kontoauszug_nr']} / {$t['kontoauszug_jahr']}"; ?></kbd>
        <? } ?>
        </td>
      </tr>
      <tr class='lastline'>
        <td class='oneline'><label title='Haben FC: positiv, falls zu unseren Gunsten (wie auf Kontoauszug der Bank)'>Haben FC:</label></td>
        <td class='number'>
          <? if( $editable and ( $tag == 1 ) ) { ?>
            <input name='haben' type='text' size='6' value='<? printf( "%.2lf", $haben ); ?>'>
        <? } else { ?>
          <kbd><? printf( "%.2lf", $t['haben'] ); ?></kbd>
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
          <th colspan='2'>Lieferanten-Transaktion <span class='small'><? echo $id; ?></span></th>
        </tr>
        <tr>
          <td><label>Lieferant:</label></td><td><kbd><? printf( "%s", lieferant_name( $lieferanten_id ) ); ?></kbd></td>
        </tr>
        <tr class='lastline'>
          <? if( $haben > 0 ) { ?>
            <td class='oneline'><label title='Haben FC: positiv, falls wir unsere Schulden beim Lieferanten verringern'>Haben FC:</label></td>
            <td class='number'>
              <? if( $editable and ( $tag == 1 ) ) { ?>
                <input name='haben' type='text' size='6' value='<? printf( "%.2lf", $haben ); ?>'>
            <? } else { ?>
              <kbd><? printf( "%.2lf", $haben ); ?></kbd>
            <? } ?>
            </td>
          <? } else { ?>
            <td class='oneline'><label title='Soll FC: positiv, falls wir unsere Schulden beim Lieferanten vergroessern'>Soll FC:</label></td>
            <td class='number'>
              <? if( $editable and ( $tag == 1 ) ) { ?>
                <input name='soll' type='text' size='6' value='<? printf( "%.2lf", $soll ); ?>'>
            <? } else { ?>
              <kbd><? printf( "%.2lf", $soll ); ?></kbd>
            <? } ?>
            </td>
          <? } ?>
        </tr>
      <?
    } else if( $gruppen_id == $muell_id ) {
      ?>
        <tr class='newfield'>
          <th colspan='2'>Interne Verrechnung <span class='small'><? echo $id; ?></span></th>
        </tr>
        <tr>
          <td><label>Typ:</label></td>
          <td>
            <?
              $typ = $t['transaktionstyp'];
              if( $editable )  {
                $options = '';
                $selected = false;
                foreach( $selectable_types as $tt ) {
                  $options .= "<option value='".$tt."'";
                  if( $tt == $typ ) {
                    $options .= " selected";
                    $selected = true;
                  }
                  $options .= ">" . transaktion_typ_string($tt) . "</option>";
                }
                if( ! $selected ) {
                  $options = "<option value=''>(bitte Typ wählen)</option>$options";
                }
                if( $selected or ( $typ == TRANSAKTION_TYP_UNDEFINIERT ) ) {
                  ?> <select name='typ_<? echo $tag; ?>'> <?
                  echo $options
                  ?> </select> <?
                } else {
                  echo "<kbd>" .transaktion_typ_string($typ)."</kbd>";
                }
              } else {
                echo "<kbd>" . transaktion_typ_string( $typ ) . "</kbd>";
              }
            ?>
          </td>
        </tr>
        <tr class='lastline'>
          <? if( $haben > 0 ) { ?>
            <td class='oneline'><label title='Soll FC: positiv, falls wir Verlust gemacht haben'>Soll FC:</label></td>
            <td class='number'>
              <? if( $editable and ( $tag == 1 ) ) { ?>
                <input name='haben' type='text' size='6' value='<? printf( "%.2lf", $haben ); ?>'>
            <? } else { ?>
              <kbd><? printf( "%.2lf", $haben ); ?></kbd>
            <? } ?>
            </td>
          <? } else { ?>
            <td class='oneline'><label title='Haben FC: positiv, falls wir Gewinn gemacht haben'>Haben FC:</label></td>
            <td class='number'>
              <? if( $editable and ( $tag == 1 ) ) { ?>
                <input name='soll' type='text' size='6' value='<? printf( "%.2lf", $soll ); ?>'>
            <? } else { ?>
              <kbd><? printf( "%.2lf", $soll ); ?></kbd>
            <? } ?>
            </td>
          <? } ?>
        </tr>
      <?
    } else {
      ?>
        <tr class='newfield'>
          <th colspan='2'>Gruppen-Transaktion <span class='small'><? echo $id; ?></span></th>
        </tr>
        <tr>
          <td><label>Gruppe:</label></td><td><kbd><? printf( "%s (%s)", sql_gruppenname( $gruppen_id ), $gruppen_id ); ?></kbd></td>
        </tr>
        <tr class='lastline'>
          <td class='oneline'><label title='Haben Gruppe: positiv, wenn die Gruppe jetzt mehr Geld auf dem Gruppenkonto hat'>Haben Gruppe:</label></td>
          <td class='number'>
            <? if( $editable and ( $tag == 1 ) ) { ?>
              <input name='soll' type='text' size='6' value='<? printf( "%.2lf", -$t['haben'] ); ?>'>
          <? } else { ?>
            <kbd><? printf( "%.2lf", -$t['haben'] ); ?></kbd>
          <? } ?>
          </td>
        </tr>
      <?
    }
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
      <? show_transaction( $buchung_id, 1 ); ?>
      <tr><td colspan='2'></td></tr>
      <? show_transaction( $k_id, 2 ); ?>
      <tr class='newfield'>
        <td colspan='2' style='text-align:right;'>
          <? if( $editable ) { ?>
            <input type='submit' value='&Auml;ndern'>
          <? } ?>
          <input value='Schließen' type='button' onClick='if(opener) opener.focus(); closeCurrentWindow();'>
        </td>
      </tr>
      <tr>
      </tr>
    </table>
  </fieldset>
</form>


