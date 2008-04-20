<?PHP

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );

$editable = true;

get_http_var( 'bestell_id', 'u', 0, true );
if( $bestell_id ) {
  $bestellung_name = bestellung_name( $bestell_id );
  $lieferanten_id = getProduzentBestellID( $bestell_id );
  $lieferant_name = lieferant_name( $lieferanten_id );
} else {
  $bestellung_name = '';
  get_http_var( 'lieferanten_id', 'u', false, true );
}


/////////////////////////////
//
//  auswahl lieferanten:
//
/////////////////////////////
?> <table width='100%' class='layout'><tr> <?

if( $bestell_id ) {
  ?> <h2>Pfandabrechnung: Bestellung <? echo "$bestellung_name ({$lieferant_name})"; ?></h2> <?
} else {
  ?>
    <h2>Pfandverpackungen</h2>
    <td style='text-align:left;padding:1ex 1em 2em 3em;'>
    <table style="width:600px;" class="liste">
      <tr>
        <th>Lieferanten</th>
        <th>Produkte</th>
      </tr>
  <?
  $lieferanten = sql_lieferanten();
  while( $row = mysql_fetch_array($lieferanten) ) {
    if( $row['id'] != $lieferanten_id ) {
      echo "<tr><td><a class='tabelle' href='" . self_url('lieferanten_id') . "&lieferanten_id={$row['id']}'>{$row['name']}</a>";
    } else {
      echo "<tr class='active'><td>{$row['name']}";
    }
    ?>  </td><td> <? echo $row['anzahl_pfandverpackungen']; ?> </td>
      </tr>
    <?
  }
  ?>
        </table>
      </td>
    </tr>
    </table>
  <?
}

// ab hier muss ein Lieferant ausgewaehlt sein, sonst Ende:
//
if( ! $lieferanten_id )
  return;

$lieferant_name = lieferant_name( $lieferanten_id );

?>
<table width='100%' class='layout'>
  <tr>
    <td>
      <table class='menu'>
        <? if( $editable ) { ?>
          <tr>
            <td><input type='button' value='Neue Verpackung eintragen' class='bigbutton' onClick="window.open('index.php?window=editVerpackung&lieferanten_id=<? echo $lieferanten_id; ?>','editProdukt','width=500,height=500,left=100,top=100').focus()"></td>
          </tr>
        <? } ?>
        <!--
        <tr>
          <td><input type='button' value='Seite aktualisieren' class='bigbutton' onClick="document.forms['reload_form'].submit();"></td>
        </tr>
        -->
      </table>
    </td>
  </tr>
</table>
<?



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
      sql_pfandzuordnung( $bestell_id, $id, ${"anzahl_kauf$id"}, ${"anzahl_rueckgabe$id"} );
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
      <th>Wert (Netto)</th>
      <th>MWSt</th>
      <? if( $bestell_id ) { ?>
        <th title'Wieviele wurden in der Rechnung zu <? echo $bestellung_name; ?> in Rechnung gestellt?'>Anzahl geliefert</th>
        <th title'Wieviele wurden in der Rechnung zu <? echo $bestellung_name; ?> gutgeschrieben?'>Anzahl gutgeschrieben</th>
      <? } ?>
      <th>Aktionen</th>
    </tr>
<?

while( $row = mysql_fetch_array( $verpackungen ) ) {
  $verpackung_id = $row['verpackung_id'];
  ?>
    <tr>
      <td><? echo $row['name']; ?></td>
      <td class='number'><? printf( "%.2lf", $row['wert'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['mwst'] ); ?></td>
      <? if( $bestell_id ) { ?>
        <td class='number'>
          <input type=text' size='6' name='anzahl_kauf<? echo $verpackung_id; ?>' value='<? printf( "%d", $row['anzahl_kauf'] ); ?>'>
        </td>
        <td class='number'>
          <input type=text' size='6' name='anzahl_rueckgabe<? echo $verpackung_id; ?>' value='<? printf( "%d", $row['anzahl_rueckgabe'] ); ?>'>
        </td>
      <? } ?>
      <td>
        <a class='png' href="javascript:f=window.open('index.php?window=editVerpackung&verpackung_id=<? echo $verpackung_id; ?>','editProdukt','width=500,height=450,left=200,top=100');f.focus();"><img src='img/b_edit.png'
           border='0' alt='Stammdaten ändern' title='Stammdaten ändern'/></a>
      </td>
    </tr>
  <?
}

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


