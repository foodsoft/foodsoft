<?php
  //
  // konto.php: Bankkonto-Verwaltung
  //

  if( ! $angemeldet ) {
    exit( "<div class='warn'>Bitte erst <a href='index.php'>Anmelden...</a></div>");
  } 

  setWindowSubtitle( 'Kontoverwaltung' );
  setWikiHelpTopic( 'foodsoft:kontoverwaltung' );

  ?> <h1>Kontoverwaltung</h1> <?

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

  $auszuege = sql_kontoauszug( $konto_id );

  ?>
    <h3>Auszüge:</h3>
    
    <table class='liste'>
      <tr class='legende'>
        <th>Jahr</th>
        <th>Nr</th>
        <th>Anzahl Posten</th>
        <th>Saldo</th>
      </tr>
  <?
  
  while( $auszug = mysql_fetch_array( $auszuege ) ) {
    $jahr = $auszug['kontoauszug_jahr'];
    $nr = $auszug['kontoauszug_nr'];

    $posten = mysql_num_rows( sql_kontoauszug( $konto_id, $jahr, $nr ) );
    $saldo = sql_bankkonto_saldo( $konto_id, $auszug['kontoauszug_jahr'], $auszug['kontoauszug_nr'] );

    $detailurl="javascript:neuesfenster('index.php?window=kontoauszug&konto_id=$konto_id&auszug_jahr=$jahr&auszug_nr=$nr','kontoauszug');";
    echo "
      <tr onclick=\"$detailurl\">
        <td>$jahr</td>
        <td class='number'><a href='$detailurl'>$nr</a></td>
        <td class='number'>$posten</td>
        <td class='number'>$saldo</td>
      </tr>
    ";
  }
  ?> </table> <?

?>

