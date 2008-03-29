
<?php

assert( $angemeldet ) or exit();

$lieferanten_id = sql_select_single_field( "SELECT id FROM lieferanten WHERE name='Terra'", 'id' );
need_http_var( 'terrakw', 'w' );

  echo 'Hallo, Welt!';
  echo '<br>files: ' . $_FILES;
  echo '<br>lieferant: ' . $lieferanten_id;
  echo '<br>tmpfile: ' . $_FILES['terrakatalog']['tmp_name'];
  echo '<br>terrakw: ' . $terrakw . '<br>';

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['terrakatalog']['tmp_name'], $klines );

  $tag = false;

  $n=1;
  foreach ( $klines as $line ) {
    if( $n++ > 100 )
      break;

    if( ! $tag ) {
      echo "analyzing line: $line<br>";
      // Art.Nr.@@Bestell-Nr.@@Milch@@@@@@Inhalt@Einh.@Land@@IK@Verband@@Netto-Preis @@/Einh.@empf. VK@@MwSt. %@@EAN-Code@@@
      if( preg_match( '&^Art.Nr. *@@Bestell-Nr.@@Milch *@@@@@@Inhalt *@Einh. *@Land *@@IK *@Verband *@@ *Netto-Preis *@@/Einh. *@empf. VK@@MwSt. % *@@EAN-Code *@@@&' , $line ) ) {
        $tag = "Fr";
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', 'verband', 'netto', '', 'mwst', '' );
        $pattern = '/^[[:digit:] ]+@@[[:digit:] ]+@/';
        echo "detected format: $tag<br>";
      }
      if( preg_match( '&^Art.Nr.@Bestell-Nr.@ZITRUS-FRÜCHTE *@Inhalt *@Einh. *@Herk. *@HKL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@&', $line ) ) {
        $tag='OG';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', '', 'verband', 'netto', '', 'mwst', '' );
        $pattern = '/^[[:digit:] ]+@[[:digit:] ]+@/';
        echo "detected format: $tag<br>";
      }
      continue;
    }

    if( ! preg_match( $pattern, $line ) ) {
      echo "<div class='warn'>cannot parse line: $line</div>";
      continue;
    }

    $anummer = "";
    $bnummer = "";
    $name = "";
    $einheit = "";
    $gebinde = "";
    $mwst = "7.00";
    $pfand = "0.00";
    $verband = "";
    $herkunft = "";
    $netto = "0.00";

    $splitline = split( '@+', $line );
    $i=0;
    foreach( $splitline as $field ) {
      if( isset( $fields[$i] ) and $fields[$i] ) {
        ${$fields[$i]} = mysql_real_escape_string($field);
      }
      $i++;
    }
    // drop trailing garbage in $netto:
    $netto = sprintf( "%.2lf", $netto );
    $mwst = sprintf( "%.2lf", $mwst );
    $name = mysql_real_escape_string( $name );

    if( $netto < 0.01 or $mwst < 0.01 ) {
      echo "<div class='warn'>error parsing line: $line</div>";
      continue;
    }

    echo "<div class='ok'>parsed: $anummer, $bnummer, $name, $einheit, $gebinde, $mwst, $pfand, $verband, $herkunft, $netto</div>";
    doSql( "
      INSERT INTO lieferantenkatalog (
        lieferanten_id
      , artikelnummer
      , bestellnummer
      , name
      , liefereinheit
      , gebinde
      , mwst
      , pfand
      , verband
      , herkunft
      , preis
      , katalogdatum
      ) VALUES (
        '$lieferanten_id'
      , '$anummer'
      , '$bnummer'
      , '$name'
      , '$einheit'
      , '$gebinde'
      , '$mwst'
      , '$pfand'
      , '$verband'
      , '$herkunft'
      , '$netto'
      , '$terrakw'
      ) ON DUPLICATE KEY UPDATE
        name='$name'
      , liefereinheit='$einheit'
      , gebinde='$gebinde'
      , mwst='$mwst'
      , pfand='$pfand'
      , verband='$verband'
      , herkunft='$herkunft'
      , preis='$netto'
      , katalogdatum='$terrakw'
    " );

  }
    
  echo '<br>finis.<br>';

?>
