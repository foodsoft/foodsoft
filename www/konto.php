<?php
//
// konto.php: Bankkonto-Verwaltung
//

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );

setWindowSubtitle( 'Kontoverwaltung' );
setWikiHelpTopic( 'foodsoft:kontoverwaltung' );

?> <h1>Kontoverwaltung</h1> <?

//////////////////////
// konto auswaehlen:
//
$konten = sql_konten();
if( mysql_num_rows($konten) < 1 ) {
  ?>
    <div class='warn'>
      Keine Konten definiert!
      <a href='index.php'>Zurück...</a>
    </div>
  <?
  return;
}
if( mysql_num_rows($konten) == 1 ) {
  $row = mysql_fetch_array($konten);
  $konto_id = $row['id'];
  mysql_data_seek( $konten, 0 );
} else {
  $konto_id = 0;
}
get_http_var( 'konto_id', 'u', $konto_id, true );

?>
  <h4>Konten der Foodcoop:</h4>
  <div style='padding-bottom:2em;'>
  <table style='padding-bottom:2em;' class='liste'>
    <tr>
      <th>Name</th>
      <th>BLZ</th>
      <th>Konto-Nr</th>
      <th>Online-Banking</th>
      <th>Kommentar</th>
    </tr>
<?
while( $row = mysql_fetch_array($konten) ) {
  if( $row['id'] != $konto_id ) {
    echo "
      <tr onclick=\"window.location.href='" . self_url('konto_id') . "&konto_id={$row['id']}';\">
        <td><a class='tabelle' href='" . self_url('konto_id') . "&konto_id={$row['id']}'>{$row['name']}</a></td>
    ";
  } else {
    echo "<tr class='active'><td style='font-weight:bold;'>{$row['name']}</td>";
  }
  echo "
      <td class='number'>{$row['blz']}</td>
      <td class='number'>{$row['kontonr']}</td>
  ";
  if( ( $url = $row['url'] ) ) {
    echo "<td><a href=\"javascript:neuesfenster('$url','onlinebanking');\">$url</a></td>";
  } else {
    echo "<td> - </td>";
  }
  echo "
      <td>{$row['kommentar']}</td>
    </tr>
  ";
}
?> </table></div> <?

if( ! $konto_id )
  return;


//////////////////////
// auszug auswaehlen:
//

get_http_var( 'auszug', '/\d+-\d+/', 0 );
if( $auszug ) {
  list( $auszug_jahr, $auszug_nr ) = explode( '-', $auszug );
  $self_fields['auszug_jahr'] = $auszug_jahr;
  $self_fields['auszug_nr'] = $auszug_nr;
} else {
  get_http_var( 'auszug_jahr', 'u', 0, true );
  get_http_var( 'auszug_nr', 'u', 0, true );
}

$auszuege = sql_kontoauszug( $konto_id );

?>
  <table>
  <tr><td>
    <h3>Erfasste Auszüge:</h3>
  </td></tr>
  <tr><td>
      <select id='select_auszug'
       onchange='select_auszug("<? echo self_url( array( 'auszug_jahr', 'auszug_nr' ) ); ?>");'>
<?

$selected = false;
$options = '';
while( $auszug = mysql_fetch_array( $auszuege ) ) {
  $jahr = $auszug['kontoauszug_jahr'];
  $nr = $auszug['kontoauszug_nr'];

  $posten = mysql_num_rows( sql_kontoauszug( $konto_id, $jahr, $nr ) );
  $saldo = sql_bankkonto_saldo( $konto_id, $auszug['kontoauszug_jahr'], $auszug['kontoauszug_nr'] );

  // $detailurl = self_url( array( 'auszug_jahr', 'auszug_nr' ) ) . "&auszug_nr=$nr&auszug_jahr=$jahr";

  $options .= "<option value='$jahr-$nr'";
  if( $jahr == $auszug_jahr and $nr == $auszug_nr ) {
    $options .= " selected";
    $selected = true;
  }
  $options .= ">$jahr / $nr ($posten Posten, Saldo: $saldo)</option>";
}
if( ! $selected ) {
  $options = "<option value='0' selected>(Bitte Auszug wählen)</option>" . $options;
}
echo $options;
?> </select>
 </td><td>

  <div id='neuer_auszug_button' style='padding-bottom:1em;'>
    <span class='button'
      onclick="document.getElementById('neuer_auszug_menu').style.display='block';
               document.getElementById('neuer_auszug_button').style.display='none';"
    >Neuen Auszug anlegen...</span>
  </div>

  <div id='neuer_auszug_menu' style='display:none;margin-bottom:2em;white-space:nowrap;'>
    <form method='post' action='<? echo self_url( array('auszug_jahr','auszug_nr') ); ?>'>
      <? echo self_post( array('auszug_jahr','auszug_nr') ); ?>
      <!-- <fieldset class='small_form'>
      <legend> -->
        <img src='img/close_black_trans.gif' class='button' title='Schliessen' alt='Schliessen'
        onclick="document.getElementById('neuer_auszug_button').style.display='block';
                 document.getElementById('neuer_auszug_menu').style.display='none';">
        Neuen Auszug anlegen:
      <!-- </legend> -->
      <label>Jahr:</label>
      <input id='input_auszug_jahr' type='text' size='4' name='auszug_jahr' value='<? echo date('Y'); ?>'>
      /
      <label>Nr:</label>
      <input id='input_auszug_nr' type='text' size='2' name='auszug_nr' value=''>
      &nbsp;
      <input type='submit' value='OK'>
    </form>
  </div>

  </td>
  </tr>
  </table>

  <!-- <table class='liste'>
    <tr class='legende'>
      <th>Jahr</th>
      <th>Nr</th>
      <th>Anzahl Posten</th>
      <th>Saldo</th>
    </tr>
  -->
<?


if( ! $auszug_jahr or ! $auszug_nr )
  return;

$auszug = sql_kontoauszug( $konto_id, $auszug_jahr, $auszug_nr );

$startsaldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr-1 );
$saldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr );

$kontoname = sql_kontoname($konto_id);
echo "<h3>$kontoname - Auszug $auszug_jahr / $auszug_nr</h3>";

if( $editable ) {
  get_http_var( 'action', 'w', false );
  switch( $action ) {
    case 'zahlung_gruppe':
      buchung_gruppe_bank();
      break;
    case 'zahlung_lieferant':
      buchung_lieferant_bank();
      break;
  }

  ?>
  <div id='transactions_button' style='padding-bottom:1em;'>
  <span class='button'
    onclick="document.getElementById('transactions_menu').style.display='block';
             document.getElementById('transactions_button').style.display='none';"
    >Transaktion eintragen...</span>
  </div>

  <fieldset class='small_form' id='transactions_menu' style='display:none;margin-bottom:2em;'>
    <legend>
      <img src='img/close_black_trans.gif' class='button' title='Schliessen' alt='Schliessen'
      onclick="document.getElementById('transactions_button').style.display='block';
               document.getElementById('transactions_menu').style.display='none';">
      Transaktionen
    </legend>

    Art der Transaktion:

    <ul style='list-style:none;'>
      <li title='Einzahlung von oder Auszahlung an Gruppe'>
        <input type='radio' name='transaktionsart'
          onclick="document.getElementById('gruppe_form').style.display='block';
                   document.getElementById('lieferant_form').style.display='none';
                   document.getElementById('konto_form').style.display='none';"
        ><b>Einzahlung / Auszahlung Gruppe</b>
      </li>

      <li title='Überweisung an oder Lastschrift von Lieferant'>
        <input type='radio' name='transaktionsart'
          onclick="document.getElementById('gruppe_form').style.display='none';
                   document.getElementById('lieferant_form').style.display='block';
                   document.getElementById('konto_form').style.display='none';"
        ><b>Überweisung / Lastschrift Lieferant</b>
      </li>

      <li title='Überweisung von diesem auf ein anderes Bankkonto der FC'>
        <input type='radio' name='transaktionsart'
          onclick="document.getElementById('gruppe_form').style.display='none';
                   document.getElementById('lieferant_form').style.display='none';
                   document.getElementById('konto_form').style.display='block';"
        ><b>Überweisung auf ein anderes Konto der FC</b>
      </li>

    </ul>

    <div id='gruppe_form' style='display:none;'>
      <? formular_buchung_gruppe_bank( 0, $konto_id, $auszug_jahr, $auszug_nr ); ?>
    </div>

    <div id='lieferant_form' style='display:none;'>
      <? formular_buchung_lieferant_bank( 0, $konto_id, $auszug_jahr, $auszug_nr ); ?>
    </div>

    <div id='konto_form' style='display:none;'>
      <? formular_buchung_bank_bank( $konto_id, $auszug_jahr, $auszug_nr ); ?>
    </div>

  </fieldset>
  <?
}


?>

  <table class='liste'>
    <tr class='legende'>
      <th>Nr</th>
      <th>Valuta</th>
      <th>Text</th>
      <th>Betrag</th>
    </tr>
<?

printf( "
    <tr class='summe'>
      <td colspan='3' style='text-align:right;'>Startsaldo:</td>
      <td class='number'>%.2lf</td>
    </tr>
  "
, $startsaldo
);

$n=0;
while( $row = mysql_fetch_array( $auszug ) ) {
  $n++;
  $kommentar = $row['kommentar'];
  $konterbuchung_id = $row['konterbuchung_id'];
  echo "
    <tr>
      <td class='number'>$n</td>
      <td class='number'>{$row['valuta']}</td>
      <td>$kommentar<br>
  ";
  if( $konterbuchung_id ) {
    $konterbuchung = sql_get_transaction( $konterbuchung_id );
    if( $konterbuchung_id > 0 ) {
      $k_konto_id = $konterbuchung['konto_id'];
      $k_auszug_jahr = $konterbuchung['auszug_jahr'];
      $k_auszug_nr = $konterbuchung['auszug_nr'];
      echo "
        <p>Gegenbuchung:
        <a href='index.php?window=konto&konto_id=$k_konto_id&auszus_jahr=$k_kontoauszug_jahr&$k_kontoauszug_nr'
        >{$konterbuchung['kontoname']}, Auszug $k_auszug_jahr / $k_auszug_nr</a></p>
      ";
    } else {
      $gruppen_id = $konterbuchung['gruppen_id'];
      $lieferanten_id=$konterbuchung['lieferanten_id'];
      if( $gruppen_id ) {
        $gruppen_name = sql_gruppenname( $gruppen_id );
        echo "
          <p>Überweisung Gruppe
          <a href=\"javascript:neuesfenster('index.php?window=showGroupTransaktions&gruppen_id=$gruppen_id','kontoblatt');\"
          >$gruppen_name</a></p>
        ";
      } else if ( $lieferanten_id ) {
        $lieferanten_name = lieferant_name( $lieferanten_id );
        echo "
          <p>Überweisung/Lastschrift Lieferant
          <a href=\"javascript:neuesfenster('index.php?window=lieferantenkonto&lieferanten_id=$lieferanten_id','lieferantenkonto');\"
          >$lieferanten_name</a></p>
        ";
      } else {
        ?> <div class='warn'>unültige Buchung</div> <?
      }
    }
  } else {
    echo "<div class='warn'>einfache Buchung</div>";
  }
  printf( "<td class='number' style='vertical-align:bottom;'>%.2lf</td>", $row['betrag'] );
  echo "</tr>";
}

printf( "
    <tr class='summe'>
      <td colspan='3' style='text-align:right;'>Saldo:</td>
      <td class='number'>%.2lf</td>
    </tr>
  "
, $saldo
);

?>

</table>

