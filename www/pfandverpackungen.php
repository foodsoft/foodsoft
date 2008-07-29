<?PHP

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );

get_http_var( 'bestell_id', 'u', 0, true );
if( $bestell_id ) {
  $bestellung_name = bestellung_name( $bestell_id );
  $lieferanten_id = getProduzentBestellID( $bestell_id );
  $lieferant_name = lieferant_name( $lieferanten_id );
} else {
  $bestellung_name = '';
  get_http_var( 'lieferanten_id', 'u', 0, true );
}

get_http_var( 'optionen', 'u', 0, true );


/////////////////////////////
//
//  auswahl lieferanten:
//
/////////////////////////////
?>

<table width='100%' class='layout'><tr>
<td>
  <table class='menu'>
    <tr>
      <th style='text-align:center;'><h4>Optionen</h4></th>
    </tr>
    <? if( $lieferanten_id) { ?>
      <tr>
        <td><? echo fc_button( 'edit_verpackung', "text=Neue Verpackung eintragen,lieferanten_id=$lieferanten_id" ); ?></td>
      </tr>
      <tr>
        <td><? echo fc_button( 'self', "text=Seite aktualisieren" ); ?></td>
      </tr>
      <tr>
        <td><? echo fc_button( 'index', "text=Beenden" ); ?></td>
      </tr>
    <? } ?>
  </table>
</td>

<td>
<? if( $bestell_id ) { ?>
  <h3>Pfandabrechnung: Bestellung <? echo "$bestellung_name ({$lieferant_name})"; ?></h3>
<? } else { ?>
   <td style='text-align:left;padding:1ex 1em 2em 3em;'>
   <h3>Pfandverpackungen</h3>
     <? auswahl_lieferant( $lieferanten_id ); ?>
   </td>
<? } ?>
</td>

</tr>
</table>
<?

// ab hier muss ein Lieferant ausgewaehlt sein, sonst Ende:
//
if( ! $lieferanten_id )
  return;

$lieferant_name = lieferant_name( $lieferanten_id );



/////////////////////////////
//
// aktionen verarbeiten:
//
/////////////////////////////

get_http_var('action','w','');
$editable or $action = '';

if( $bestell_id and ( $action == 'save' ) ) {
  $verpackungen = sql_lieferantenpfand( $lieferanten_id, $bestell_id );
  while( $row = mysql_fetch_array($verpackungen)) {
    $id = $row['verpackung_id'];
    if( get_http_var( "anzahl_voll$id", 'u' ) and get_http_var( "anzahl_leer$id", 'u' ) ) {
      sql_pfandzuordnung_lieferant( $bestell_id, $id, ${"anzahl_voll$id"}, ${"anzahl_leer$id"} );
    }
  }
}
if( $action == 'moveup' ) {
  need_http_var( 'verpackung_id', 'u' );
  $verpackungen = sql_lieferantenpfand( $lieferanten_id );
  $prev = false;
  while( $row = mysql_fetch_array( $verpackungen ) ) {
    if( $row['verpackung_id'] == $verpackung_id ) {
      if( ! $prev )
        break;
      // echo "prev: {$prev['verpackung_id']}/{$prev['sort_id']}, row: {$row['verpackung_id']}/{$row['sort_id']}<br>";
      $h = $prev['sort_id'];
      sql_update( 'pfandverpackungen', $prev['verpackung_id'], array( 'sort_id' => $row['sort_id'] ) );
      sql_update( 'pfandverpackungen', $row['verpackung_id'], array( 'sort_id' => $h ) );
      // erzwinge neue index-reihenfolge schon beim naechsten SELECT in diesem script:
      // doSql( 'FLUSH TABLES' );
      break;
    }
    $prev = $row;
  }
}
if( $action == 'movedown' ) {
  need_http_var( 'verpackung_id', 'u' );
  $verpackungen = sql_lieferantenpfand( $lieferanten_id );
  while( $row = mysql_fetch_array( $verpackungen ) ) {
    if( $row['verpackung_id'] == $verpackung_id ) {
      $next = mysql_fetch_array( $verpackungen );
      if( ! $next )
        break;
      // echo "next: {$next['id']}/{$next['sort_id']}, row: {$row['id']}/{$row['sort_id']}<br>";
      $h = $row['sort_id'];
      sql_update( 'pfandverpackungen', $row['verpackung_id'], array( 'sort_id' => $next['sort_id'] ) );
      sql_update( 'pfandverpackungen', $next['verpackung_id'], array( 'sort_id' => $h ) );
      doSql( 'FLUSH TABLES' );
      break;
    }
  }
}

//   if( $action == 'delete' and $editable ) {
//     need_http_var('pfandverpackung_id','u');
//     sql_delete_pfandverpackung( $pfandverpackung_id );
//   }


/////////////////////////////
//
// Pfandzettel anzeigen:
//
/////////////////////////////


$verpackungen = sql_lieferantenpfand( $lieferanten_id, $bestell_id );

if( $bestell_id ) {
  ?>
  <form method='post' action='<? echo self_url(); ?>'>
  <? echo self_post(); ?>
  <input type='hidden' name='action' value='save'>
  <?
}

?>
  <table class='numbers'>
    <tr>
      <th>Bezeichnung <? echo $lieferanten_id; ?></th>
      <th>Einzelwert</th>
      <th>MWSt</th>
      <th class='number'>geliefert</th>
      <th class='number'>Netto</th>
      <th class='number'>Brutto</th>
      <th class='number'>gutgeschrieben</th>
      <th class='number'>Netto</th>
      <th class='number'>Brutto</th>
      <th class='number'>Bestand</th>
      <th class='number'>Netto</th>
      <th class='number'>Brutto</th>
      <th>Aktionen</th>
    </tr>
<?

$summe_voll_anzahl = 0;
$summe_voll_netto = 0;
$summe_voll_brutto = 0;
$summe_leer_anzahl = 0;
$summe_leer_netto = 0;
$summe_leer_brutto = 0;
while( $row = mysql_fetch_array( $verpackungen ) ) {
  $verpackung_id = $row['verpackung_id'];
  ?>
    <tr>
      <td><? echo $row['name']; ?></td>
      <td class='number'><? printf( "%.2lf", $row['wert'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['mwst'] ); ?></td>
      <td class='mult'>
        <? if( $editable and $bestell_id ) { ?>
          <input style='text-align:right;' type=text' size='3' name='anzahl_voll<? echo $verpackung_id; ?>' value='<? printf( "%d", $row['pfand_voll_anzahl'] ); ?>'
               onchange="document.getElementById('reminder').style.display = 'inline';">
        <? } else { ?>
          <? echo $row['pfand_voll_anzahl']; ?>
        <? } ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_voll_netto_soll'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_voll_brutto_soll'] ); ?>
      </td>
      <td class='number'>
        <? if( $editable and $bestell_id ) { ?>
          <input style='text-align:right;' type=text' size='3' name='anzahl_leer<? echo $verpackung_id; ?>' value='<? printf( "%d", $row['pfand_leer_anzahl'] ); ?>'
               onchange="document.getElementById('reminder').style.display = 'inline';">
        <? } else { ?>
          <? echo $row['pfand_leer_anzahl']; ?>
        <? } ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_leer_netto_soll'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_leer_brutto_soll'] ); ?>
      </td>
      <td class='number'>
        <? echo ( $row['pfand_voll_anzahl'] - $row['pfand_leer_anzahl'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf" , $row['pfand_voll_netto_soll'] + $row['pfand_leer_netto_soll'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf" , $row['pfand_voll_brutto_soll'] + $row['pfand_leer_brutto_soll'] ); ?>
      </td>
      <td>
        <?
          if( $editable ) {
            echo fc_alink( 'edit_verpackung', "verpackung_id=$verpackung_id" );
            echo fc_alink( 'self', "action=moveup,verpackung_id=$verpackung_id,text=,img=img/arrow.up.blue.png,title=Eintrag nach oben schieben" );
            echo fc_alink( 'self', "action=movedown,verpackung_id=$verpackung_id,text=,img=img/arrow.down.blue.png,title=Eintrag nach unten schieben" );
          }
        ?>
      </td>
    </tr>
  <?
  $summe_voll_anzahl += $row['pfand_voll_anzahl'];
  $summe_voll_netto += $row['pfand_voll_netto_soll'];
  $summe_voll_brutto += $row['pfand_voll_brutto_soll'];
  $summe_leer_anzahl += $row['pfand_leer_anzahl'];
  $summe_leer_netto += $row['pfand_leer_netto_soll'];
  $summe_leer_brutto += $row['pfand_leer_brutto_soll'];
}
if( $bestell_id ) {
  $verpackungen = sql_lieferantenpfand( $lieferanten_id, $bestell_id, 'mwst' );
  while( $row = mysql_fetch_array( $verpackungen ) ) {
    ?>
      <tr class='summe'>
        <td colspan='2'>Zwischensumme:</td>
        <td class='number'><? echo $row['mwst']; ?></td>
        <td class='number'><? printf( "%u", $row['pfand_voll_anzahl'] ); ?></td>
        <td class='number'><? printf( "%.2lf", $row['pfand_voll_netto_soll'] ); ?></td>
        <td class='number'><? printf( "%.2lf", $row['pfand_voll_brutto_soll'] ); ?></td>
        <td class='number'><? printf( "%u", $row['pfand_leer_anzahl'] ); ?></td>
        <td class='number'><? printf( "%.2lf", $row['pfand_leer_netto_soll'] ); ?></td>
        <td class='number'><? printf( "%.2lf", $row['pfand_leer_brutto_soll'] ); ?></td>
        <td class='number'><? printf( "%u", $row['pfand_voll_anzahl'] - $row['pfand_leer_anzahl'] ); ?></td>
        <td class='number'><? printf( "%.2lf", $row['pfand_voll_netto_soll'] + $row['pfand_leer_netto_soll'] ); ?></td>
        <td class='number'><? printf( "%.2lf", $row['pfand_voll_brutto_soll'] + $row['pfand_leer_brutto_soll'] ); ?></td>
        <td>&nbsp;</td>
      </tr>
    <?
  }
}

?>
  <tr class='summe'>
    <td colspan='3'>Summe:</td>
    <td class='number'>
      <? printf( "%u", $summe_voll_anzahl ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_voll_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_voll_brutto ); ?>
    </td>
    <td class='number'>
      <? printf( "%u", $summe_leer_anzahl ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_leer_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_leer_brutto ); ?>
    </td>
    <td class='number'>
      <? printf( "%u", $summe_voll_anzahl - $summe_leer_anzahl ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_voll_netto + $summe_leer_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_voll_brutto + $summe_leer_brutto ); ?>
    </td>
    <td>&nbsp;</td>
  </tr>
<?

?> </table> <?

if( $bestell_id ) {
  floating_submission_button( 'reminder' );
  ?> </form> <?
}



