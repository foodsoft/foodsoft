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
      <td><h4>Optionen</h4></td>
    </tr>
    <? if( $lieferanten_id) { ?>
      <tr>
        <td><input type='button' value='Neue Verpackung eintragen' class='bigbutton' onClick="window.open('index.php?window=editVerpackung&lieferanten_id=<? echo $lieferanten_id; ?>','editProdukt','width=500,height=500,left=100,top=100').focus()"></td>
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
     <?  auswahl_lieferant( $lieferanten_id ); ?>
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
  $verpackungen = sql_pfandverpackungen( $lieferanten_id, $bestell_id );
  while( $row = mysql_fetch_array($verpackungen)) {
    $id = $row['verpackung_id'];
    if( get_http_var( "anzahl_kauf$id", 'u' ) and get_http_var( "anzahl_rueckgabe$id", 'u' ) ) {
      sql_pfandzuordnung_lieferant( $bestell_id, $id, ${"anzahl_kauf$id"}, ${"anzahl_rueckgabe$id"} );
    }
  }
}
if( $action == 'moveup' ) {
  need_http_var( 'verpackung_id', 'u' );
  $verpackungen = sql_pfandverpackungen( $lieferanten_id );
  $prev = false;
  while( $row = mysql_fetch_array( $verpackungen ) ) {
    if( $row['verpackung_id'] == $verpackung_id ) {
      if( ! $prev )
        break;
      // echo "prev: {$prev['id']}/{$prev['sort_id']}, row: {$row['id']}/{$row['sort_id']}<br>";
      $h = $prev['sort_id'];
      sql_update( 'pfandverpackungen', $prev['id'], array( 'sort_id' => $row['sort_id'] ) );
      sql_update( 'pfandverpackungen', $row['id'], array( 'sort_id' => $h ) );
      // erzwinge neue index-reihenfolge schon beim naechsten SELECT in diesem script:
      doSql( 'FLUSH TABLES' );
      break;
    }
    $prev = $row;
  }
}
if( $action == 'movedown' ) {
  need_http_var( 'verpackung_id', 'u' );
  $verpackungen = sql_pfandverpackungen( $lieferanten_id );
  while( $row = mysql_fetch_array( $verpackungen ) ) {
    if( $row['verpackung_id'] == $verpackung_id ) {
      $next = mysql_fetch_array( $verpackungen );
      if( ! $next )
        break;
      // echo "next: {$next['id']}/{$next['sort_id']}, row: {$row['id']}/{$row['sort_id']}<br>";
      $h = $row['sort_id'];
      sql_update( 'pfandverpackungen', $row['id'], array( 'sort_id' => $next['sort_id'] ) );
      sql_update( 'pfandverpackungen', $next['id'], array( 'sort_id' => $h ) );
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



$verpackungen = sql_pfandverpackungen( $lieferanten_id, $bestell_id );

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
      <th>Bezeichnung</th>
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

$summe_soll_netto = 0;
$summe_soll_brutto = 0;
$summe_haben_netto = 0;
$summe_haben_brutto = 0;
while( $row = mysql_fetch_array( $verpackungen ) ) {
  $verpackung_id = $row['verpackung_id'];
  ?>
    <tr>
      <td><? echo $row['name']; ?></td>
      <td class='number'><? printf( "%.2lf", $row['wert'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['mwst'] ); ?></td>
      <td class='mult'>
        <? if( $editable and $bestell_id ) { ?>
          <input type=text' size='6' name='anzahl_kauf<? echo $verpackung_id; ?>' value='<? printf( "%d", $row['anzahl_kauf'] ); ?>'>
        <? } else { ?>
          <? echo $row['anzahl_kauf']; ?>
        <? } ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_soll_netto'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_soll_brutto'] ); ?>
      </td>
      <td class='number'>
        <? if( $editable and $bestell_id ) { ?>
          <input type=text' size='6' name='anzahl_rueckgabe<? echo $verpackung_id; ?>' value='<? printf( "%d", $row['anzahl_rueckgabe'] ); ?>'>
        <? } else { ?>
          <? echo $row['anzahl_rueckgabe']; ?>
        <? } ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_haben_netto'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['pfand_haben_brutto'] ); ?>
      </td>
      <td class='number'>
        <? echo ( $row['anzahl_kauf'] - $row['anzahl_rueckgabe'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf" , $row['pfand_soll_netto'] - $row['pfand_haben_netto'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf" , $row['pfand_soll_brutto'] - $row['pfand_haben_brutto'] ); ?>
      </td>
      <td>
        <a class='png' href="javascript:f=window.open('index.php?window=editVerpackung&verpackung_id=<? echo $verpackung_id; ?>','editProdukt','width=500,height=450,left=200,top=100');f.focus();"><img src='img/b_edit.png'
           border='0' alt='Stammdaten ändern' title='Stammdaten ändern'/></a>
           &nbsp;
        <? if( $editable ) { ?>
          <a class='png' href='<? echo self_url() . "&action=moveup&verpackung_id=$verpackung_id"; ?>'>
            <img style='border:none;' src='img/arrow.up.blue.png' title='Eintrag nach oben schieben'></a>
          <a class='png' href='<? echo self_url() . "&action=movedown&verpackung_id=$verpackung_id"; ?>'>
            <img style='border:none;' src='img/arrow.down.blue.png' title='Eintrag nach unten schieben'></a>
        <? } ?>
      </td>
    </tr>
  <?
  $summe_soll_netto += $row['pfand_soll_netto'];
  $summe_haben_netto += $row['pfand_haben_netto'];
  $summe_soll_brutto += $row['pfand_soll_brutto'];
  $summe_haben_brutto += $row['pfand_haben_brutto'];
}

?>
  <tr class='summe'>
    <td colspan='4'>Summe:</td>
    <td class='number'>
      <? printf( "%.2lf", $summe_soll_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_soll_brutto ); ?>
    </td>
    <td>&nbsp;</td>
    <td class='number'>
      <? printf( "%.2lf", $summe_haben_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_haben_brutto ); ?>
    </td>
    <td>&nbsp;</td>
    <td class='number'>
      <? printf( "%.2lf", $summe_soll_netto - $summe_haben_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_soll_brutto - $summe_haben_brutto ); ?>
    </td>
    <td>&nbsp;</td>
  </tr>
<?

if( $bestell_id ) {
  ?>
    <tr>
      <td colspan='6'>
        <input type='submit' class='button' value='Speichern'>
      </td>
    </tr>
  </table>
  </form>
  <?
} else {
  ?> </table> <?
}


