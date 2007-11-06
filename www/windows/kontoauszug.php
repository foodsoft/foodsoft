<?PHP

assert($angemeldet) or exit();
 
setWindowSubtitle( 'Kontoauszug' );
setWikiHelpTopic( 'foodsoft:kontoauszug' );

need_http_var( 'konto_id', 'u', true );
need_http_var( 'auszug_jahr', 'u', true );
need_http_var( 'auszug_nr', 'u', true );

get_http_var( 'action', 'w', false );
switch( $action ) {
  case 'zahlung_gruppe':
    buchung_gruppe_bank();
    break;
  case 'zahlung_lieferant':
    buchung_lieferant_bank();
    break;
//   case 'zahlung_gruppe_lieferant':
//     buchung_gruppe_lieferant();
//     break;
//   case 'umbuchung_gruppegruppe':
//     buchung_gruppe_gruppe();
//     break;
}

if( $action ) {
  reload_immediately( self_url() );
}

$auszug = sql_kontoauszug( $konto_id, $auszug_jahr, $auszug_nr );

$startsaldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr-1 );
$saldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr );

$kontoname = sql_kontoname($konto_id);

echo "<h1>Kontoauszug: $kontoname - $auszug_jahr / $auszug_nr</h1>";

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
                 document.getElementById('gruppelieferant_form').style.display='none';
                 document.getElementById('gruppegruppe_form').style.display='none';"
      ><b>Einzahlung / Auszahlung Gruppe</b>
      </li>

      <li title='Überweisung an oder Lastschrift von Lieferant'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('gruppe_form').style.display='none';
                 document.getElementById('lieferant_form').style.display='block';
                 document.getElementById('gruppelieferant_form').style.display='none';
                 document.getElementById('gruppegruppe_form').style.display='none';"
      ><b>Überweisung / Lastschrift Lieferant</b>
      </li>

      <!--
      <li title='Direkte Überweisung einer Gruppe an einen Lieferanten'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('gruppe_form').style.display='none';
                 document.getElementById('lieferant_form').style.display='none';
                 document.getElementById('gruppelieferant_form').style.display='block';
                 document.getElementById('gruppegruppe_form').style.display='none';"
      ><b>Zahlung Gruppe -> Lieferant</b>
      </li>

      <li title='überweisung von einer Gruppe an eine andere Gruppe'>
      <input type='radio' name='transaktionsart'
        onclick="document.getElementById('gruppe_form').style.display='none';
                 document.getElementById('lieferant_form').style.display='none';
                 document.getElementById('gruppelieferant_form').style.display='none';
                 document.getElementById('gruppegruppe_form').style.display='block';"
      ><b>Umbuchung Gruppe -> Gruppe</b>
      </li>
      -->

    </ul>

    <div id='gruppe_form' style='display:none;'>
      <? formular_buchung_gruppe_bank( 0, $konto_id, $auszug_jahr, $auszug_nr ); ?>
    </div>

    <div id='lieferant_form' style='display:none;'>
      <? formular_buchung_lieferant_bank( 0, $konto_id, $auszug_jahr, $auszug_nr ); ?>
    </div>

    <div id='gruppelieferant_form' style='display:none;'>
      <? formular_buchung_gruppe_lieferant( 0, 0 ); ?>
    </div>

    <div id='gruppegruppe_form' style='display:none;'>
      <? formular_buchung_gruppe_gruppe( 0, 0 ); ?>
    </div>

  </fieldset>

<?

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
  echo "
    <tr>
      <td class='number'>$n</td>
      <td class='number'>{$row['valuta']}</td>
      <td>
  ";
  $gid = $row['gruppen_id'];
  $lid = $row['lieferanten_id'];
  $kommentar = $row['kommentar'];
  $konterbuchung_id = $row['konterbuchung_id'];
  if( $gid ) {
    printf( "<p>Überweisung Gruppe %d (%s)</p>" , $gid % 1000, sql_gruppenname( $gid ) );
  }
  if( $lid ) {
    printf( "<p>Überweisung Lieferant %s</p>" , lieferant_name( $lid ) );
  }
  if( $kommentar ) {
    echo "<p>$kommentar</p>";
  }
  if( $konterbuchung_id ) {
    $konterbuchung = sql_get_transaction( $konterbuchung_id );
    if( $konterbuchung_id > 0 ) {
      $k_konto_id = $konterbuchung['konto_id'];
      $k_auszug_jahr = $konterbuchung['auszug_jahr'];
      $k_auszug_nr = $konterbuchung['auszug_nr'];
      echo "
        <p>Gegenbuchung:
        <a href='index.php?window=kontoauszug&konto_id=$k_konto_id&auszus_jahr=$k_kontoauszug_jahr&$k_kontoauszug_nr'
        >{$konterbuchung['kontoname']}, Auszug $k_auszug_jahr / $k_auszug_nr</a></p>
      ";
    } else {
      $gruppen_id = $konterbuchung['gruppen_id'];
      $gruppen_name = sql_gruppenname( $gruppen_id );
      $lieferanten_id=$konterbuchung['lieferanten_id'];
      if( $gruppen_id ) {
        echo "
          <p><a href=\"javascript:neuesfenster('index.php?window=showGroupTransaktions&gruppen_id=$gruppen_id');\"
          >Gruppenkonto $gruppen_name</a></p>
        ";
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

?> </table> <?


?>


