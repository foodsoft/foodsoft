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
define( 'OPTION_GRUPPENPFAND', 1 );
define( 'OPTION_GRUPPEN_INAKTIV', 2 );

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
    <tr>
      <td>
        <input type='checkbox'
          <? if( $optionen & OPTION_GRUPPENPFAND ) echo " checked"; ?>
          onclick="window.location.href='<?
            echo self_url('optionen'), "&optionen=", ($optionen ^ OPTION_GRUPPENPFAND);
          ?>';"
          title='Soll die Übersicht über berechnetes und gutgeschriebenes Pfand aller Gruppen angezeigt werden?'
        > Übersicht Gruppenpfand
      </td>
    </tr>
    <? if( $optionen & OPTION_GRUPPENPFAND ) { ?>
      <tr>
        <td>
          <input style='margin-left:2em;' type='checkbox'
            <? if( $optionen & OPTION_GRUPPEN_INAKTIV ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ OPTION_GRUPPEN_INAKTIV );
            ?>';"
            title='Auch inaktive Gruppen in Pfandübersicht aufnehmen?'
          > auch inaktive Gruppen?
        </td>
      </tr>
    <? } ?>
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
      sql_pfandzuordnung( $bestell_id, $id, ${"anzahl_kauf$id"}, ${"anzahl_rueckgabe$id"} );
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

$summe_kauf_netto = 0;
$summe_kauf_brutto = 0;
$summe_rueckgabe_netto = 0;
$summe_rueckgabe_brutto = 0;
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
        <? printf( "%.2lf", $row['kauf_netto'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['kauf_brutto'] ); ?>
      </td>
      <td class='number'>
        <? if( $editable and $bestell_id ) { ?>
          <input type=text' size='6' name='anzahl_rueckgabe<? echo $verpackung_id; ?>' value='<? printf( "%d", $row['anzahl_rueckgabe'] ); ?>'>
        <? } else { ?>
          <? echo $row['anzahl_rueckgabe']; ?>
        <? } ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['rueckgabe_netto'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf", $row['rueckgabe_brutto'] ); ?>
      </td>
      <td class='number'>
        <? echo ( $row['anzahl_kauf'] - $row['anzahl_rueckgabe'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf" , $row['kauf_netto'] - $row['rueckgabe_netto'] ); ?>
      </td>
      <td class='number'>
        <? printf( "%.2lf" , $row['kauf_brutto'] - $row['rueckgabe_brutto'] ); ?>
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
  $summe_kauf_netto += $row['kauf_netto'];
  $summe_rueckgabe_netto += $row['rueckgabe_netto'];
  $summe_kauf_brutto += $row['kauf_brutto'];
  $summe_rueckgabe_brutto += $row['rueckgabe_brutto'];
}

?>
  <tr class='summe'>
    <td colspan='4'>Summe:</td>
    <td class='number'>
      <? printf( "%.2lf", $summe_kauf_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_kauf_brutto ); ?>
    </td>
    <td>&nbsp;</td>
    <td class='number'>
      <? printf( "%.2lf", $summe_rueckgabe_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_rueckgabe_brutto ); ?>
    </td>
    <td>&nbsp;</td>
    <td class='number'>
      <? printf( "%.2lf", $summe_kauf_netto - $summe_rueckgabe_netto ); ?>
    </td>
    <td class='number'>
      <? printf( "%.2lf", $summe_kauf_brutto - $summe_rueckgabe_brutto ); ?>
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

if( ! ( $optionen & OPTION_GRUPPENPFAND ) )
  return;

?> <h3>Pfandübersicht Bestellgruppen</h3> <?

if( $bestell_id ) {
  $where = "gesamtbestellungen.id=$bestell_id";
  ?>
    <h4>
    Achtung: Spalte 'Pfand gutgeschrieben' entählt <em>alle</em> Gutschriften,
    Spalte 'Pfand berechnet' nur Pfand aus Bestellung <? echo $bestellung_name; ?>!
    </h4>
  <?
} else {
  $where = "gesamtbestellungen.lieferanten_id=$lieferanten_id";
  ?>
    <h4>
    Achtung: Spalte 'Pfand gutgeschrieben' entählt <em>alle</em> Gutschriften,
    Spalte 'Pfand berechnet' nur Pfand aus Bestellungen bei <? echo $lieferant_name; ?>!
    </h4>
  <?
}

$query = "
  SELECT
    bestellgruppen.id as gruppen_id
  , bestellgruppen.id % 1000 as gruppen_nummer
  , bestellgruppen.aktiv as aktiv
  , bestellgruppen.name as gruppen_name
  , sum( (".select_bestellungen_pfand( array( 'gesamtbestellungen', 'bestellgruppen' ) ).") ) AS pfand_haben
  , (".select_transaktionen_pfand( array( 'bestellgruppen' ) ).") AS pfand_soll
  FROM bestellgruppen
  JOIN gesamtbestellungen
  WHERE $where
  GROUP BY bestellgruppen.id
  ORDER BY bestellgruppen.aktiv, bestellgruppen.id
";
$gruppen = doSql( $query );

?>
<table class='numbers'>
  <tr>
    <th>Gruppe</th>
    <th>Nr (Id)</th>
    <th>aktiv</th>
    <th>Pfand berechnet</th>
    <th>Pfand gutgeschrieben</th>
    <th>Bestand</th>
  </tr>
<?
$summe_pfand_haben = 0;
$summe_pfand_soll = 0;
$muell_row = false;
$basar_row = false;
while( $row = mysql_fetch_array( $gruppen ) ) {
  if( $row['gruppen_id'] == $muell_id ) {
    $muell_row = $row;
    continue;
  }
  if( $row['gruppen_id'] == $basar_id ) {
    $basar_row = $row;
    continue;
  }
  if( ! ( $row['aktiv'] or ( $optionen & OPTION_GRUPPEN_INAKTIV ) ) )
    continue;
  ?>
    <tr>
      <td><? echo $row['gruppen_name']; ?></td>
      <td><? echo "{$row['gruppen_nummer']} ({$row['gruppen_id']})"; ?></td> 
      <td><? echo $row['aktiv']; ?></td> 
      <td class='number'><? printf( "%.2lf", $row['pfand_haben'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['pfand_soll'] ); ?></td>
      <td class='number'><? printf( "%.2lf", $row['pfand_soll'] - $row['pfand_haben'] ); ?></td>
    </tr>
  <?
  $summe_pfand_haben += $row['pfand_haben'];
  $summe_pfand_soll += $row['pfand_soll'];
}
?>
  <tr class='summe'>
    <td colspan='3'>Summe:</td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_haben ); ?></td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_soll ); ?></td>
    <td class='number'><? printf( "%.2lf", $summe_pfand_soll - $summe_pfand_haben ); ?></td>
  </tr>
<?
if( $basar_row ) {
  ?>
  <tr class='summe'>
    <td colspan='3'>Basar:</td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_haben'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_soll'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $basar_row['pfand_soll'] - $basar_row['pfand_haben'] ); ?></td>
  </tr>
  <?
}
if( $muell_row ) {
  ?>
  <tr class='summe'>
    <td colspan='3'>internes Verrechnungskonto:</td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_haben'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_soll'] ); ?></td>
    <td class='number'><? printf( "%.2lf", $muell_row['pfand_soll'] - $muell_row['pfand_haben'] ); ?></td>
  </tr>
  <?
}
?>
</table>

