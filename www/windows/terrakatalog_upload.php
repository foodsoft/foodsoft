
<?php

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:katalog_upload' );
setWindowSubtitle( 'Katalog einlesen' );

nur_fuer_dienst(4);

$lieferanten_id = sql_select_single_field( "SELECT id FROM lieferanten WHERE name like 'Terra%'", 'id' );
need_http_var( 'terrakw', 'w' );

  // echo '<br>files: ' . var_export($_FILES);
  echo '<br>lieferant: ' . $lieferanten_id;
  echo '<br>tmpfile: ' . $_FILES['terrakatalog']['tmp_name'];
  echo '<br>terrakw: ' . $terrakw . '<br>';

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['terrakatalog']['tmp_name'], $klines );

  $tag = false;

  $n=1;
  foreach ( $klines as $line ) {
    if( $n++ > 9999930 )
      break;

    if( ! $tag ) {
      echo "analyzing line: $line<br>";
      // Art.Nr.@@Bestell-Nr.@@Milch@@@@@@Inhalt@Einh.@Land@@IK@Verband@@Netto-Preis @@/Einh.@empf. VK@@MwSt. %@@EAN-Code@@@
      if( preg_match( '&^Art.Nr. *@@Bestell-Nr.@@Milch *@@@@@@Inhalt *@Einh. *@Land *@@IK *@Verband *@@ *Netto-Preis *@@/Einh. *@empf. VK@@MwSt. % *@@EAN-Code *@@@&' , $line ) ) {
        $tag = "Fr";
        $splitat = '@+';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', 'verband', 'netto', '', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@@[\d\s]+@/';
      }
      // Art.Nr.@Bestell-Nr.@Milch@Inhalt@Einh.@Land@IK@Verband@Netto-Preis @/Einh.@empf. VK@MwSt. %@EAN-Code@
      if( preg_match( '&^Art.Nr. *@+Bestell-Nr. *@+Milch *@+Inhalt *@Einh. *@+Land *@+IK *@+Verband *@+ *Netto-Preis *@+/Einh. *@empf. VK@+MwSt. % *@+EAN-Code *@+&' , $line ) ) {
        $tag = "Fr";
        $splitat = '@+';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', 'verband', 'netto', '', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@+[\d\s]+@/';
      }
      if( preg_match( '&^Preisliste:\s+Mopro&', $line ) ) {
        $tag='Fr';
        $splitat = '@+';
        $fields = array( 'anummer', 'bnummer', 'name', '', 'herkunft', 'verband', 'gebinde', 'einheit', 'netto', 'mwst', '', '' );
        $pattern = '/^[\d\s]+@@[\d\s]+@/';
      }

      if( preg_match( '&^Art.Nr.@Bestell-Nr.@ZITRUS-FRÜCHTE *@Inhalt *@Einh. *@Herk. *@HKL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@&', $line ) ) {
        $tag='OG';
        $splitat = '@';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', '', 'verband', 'netto', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }

      if( preg_match( '&^Preisliste\s+Drogeriewaren&', $line ) ) {
        $tag='drog';
        $splitat = '@';
        $fields = array( 'anummer', 'bnummer', 'name', '', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }

      if( preg_match( '&^Preisliste\s+Trockensortiment&', $line ) ) {
        // Artikelnr.@Bestellnr.@ Beschreibung@VPE@Liefera@Land@IK@Netto-Preis@@@MwSt.%@EAN- Code@
        $tag = 'Tr';
        $splitat = '@';
        $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }
      if( $tag ) {
        echo "detected format: $tag<br>";
        echo "pattern: $pattern<br>";
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
    $vpe = "";

    $splitline = split( $splitat, $line );
    $i=0;
    foreach( $splitline as $field ) {
      if( isset( $fields[$i] ) and $fields[$i] ) {
        ${$fields[$i]} = mysql_real_escape_string($field);
      }
      $i++;
    }
    // drop trailing garbage in $netto:
    $netto = sprintf( "%.2lf", preg_replace( '/,/', '.', $netto ) );
    $mwst = sprintf( "%.2lf", preg_replace( '/,/', '.', $mwst ) );
    $pfand = sprintf( "%.2lf", preg_replace( '/,/', '.', $pfand ) );
    $name = mysql_real_escape_string( $name );

    // drop spurious whitespace from numbers:
    $anummer = preg_replace( '/\s/', '', $anummer );
    $bnummer = preg_replace( '/\s/', '', $bnummer );

    if( $vpe ) {
      $gebinde = sprintf( "%.2lf", preg_replace( '/,/', '.', $vpe ) );
      $einheit =  preg_replace( '/^[[:digit:].]* ([[:alpha:]]*)$/', '${1}', $vpe );
    } else {
      $gebinde = sprintf( "%.2lf", preg_replace( '/,/', '.', $gebinde ) );
    }

    if( $netto < 0.01 or $mwst < 0.01 ) {
      echo "<div class='warn'>error parsing line: $line</div>";
      continue;
    }

    echo "<div class='ok'>line: $line</div>";
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
      , katalogtyp
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
      , '$tag'
      ) ON DUPLICATE KEY UPDATE
        bestellnummer='$bnummer'
      , name='$name'
      , liefereinheit='$einheit'
      , gebinde='$gebinde'
      , mwst='$mwst'
      , pfand='$pfand'
      , verband='$verband'
      , herkunft='$herkunft'
      , preis='$netto'
      , katalogdatum='$terrakw'
      , katalogtyp='$tag'
    " );

  }

  echo '<br>finis.<br>';

?>
