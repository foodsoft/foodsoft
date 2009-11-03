<?php

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:katalog_upload' );
setWindowSubtitle( 'Katalog einlesen' );

nur_fuer_dienst(4);

need_http_var( 'lieferanten_id', 'U' );
$lieferant = sql_lieferant( $lieferanten_id );

need_http_var( 'katalogkw', 'w' );

// echo '<br>files: ' . var_export($_FILES);
// echo '<br>tmpfile: ' . $_FILES['katalog']['tmp_name'];
// echo '<br>katalogkw: ' . $katalogkw . '<br>';

open_div( '', '', "Katalog einlesen: Lieferant: {$lieferant['name']} / g&uuml;ltig: $katalogkw" );


function katalog_update(
  $lieferant_id, $tag, $katalogkw
, $anummer, $bnummer, $name, $einheit, $gebinde, $mwst, $pfand, $verband, $herkunft, $netto, $katalogformat
) {

  open_div( 'ok' );
    open_div( 'ok qquad', '', "erfasst: $anummer, $bnummer, $name, $einheit, $gebinde, $mwst, $pfand, $verband, $herkunft, $netto, $katalogformat" );
  close_div();

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
    , katalogformat
    , gueltig
    ) VALUES (
      '$lieferant_id'
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
    , '$katalogkw'
    , '$tag'
    , '$katalogformat'
    , 1
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
    , katalogdatum='$katalogkw'
    , katalogtyp='$tag'
    , katalogformat='$katalogformat'
    , gueltig=1
  " );
}


function upload_terra() {
  global $katalogkw, $lieferanten_id;

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['katalog']['tmp_name'], $klines );

  $tag = false;

  $n = 0;
  $success = 0;
  foreach ( $klines as $line ) {
    if( $n++ > 99999 )
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
      if( preg_match( '&^Art.Nr.@Bestell-Nr.@Zitrus-Früchte *@Inhalt *@Einh. *@Herk. *@HKL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@&', $line ) ) {
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
        open_div( 'ok', '', "Katalog: detektiertes Format: $tag" );
        // echo "pattern: $pattern<br>";
      }
      continue;
    }

    if( ! preg_match( $pattern, $line ) ) {
      open_div( 'alert', '', "Zeile nicht ausgewertet: $line" );
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
      $einheit =  preg_replace( '/^[[:digit:]. ]*([[:alpha:]]*)$/', '${1}', $vpe );
    } else {
      $gebinde = sprintf( "%.2lf", preg_replace( '/,/', '.', $gebinde ) );
    }

    if( $netto < 0.01 or $mwst < 0.01 ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: $line" );
      continue;
    }

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $einheit, $gebinde, $mwst, $pfand, $verband, $herkunft, $netto, 'terra'
    );
    $success++;
  }

  logger( "Terra-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
  open_div( 'ok', '', 'finis.' );
}


function upload_rapunzel() {
  global $katalogkw, $lieferanten_id;

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['katalog']['tmp_name'], $klines );
  $tag = 'Tr'; // Rapunzel: nur ein Katalog, entspricht "Trocken" bei Terra
  $pattern = '/^[^@]*@\d+@/';
  $splitat = '@';

  $n = 0;
  $success = 0;
  foreach ( $klines as $line ) {
    if( $n++ > 99999 )
      break;

    if( ! preg_match( $pattern, $line ) ) {
      open_div( 'alert', '', "Zeile nicht ausgewertet: $line" );
      continue;
    }

    $anummer = "";
    $bnummer = "";
    $name = "";
    $einheit = "";
    $gebinde = "";
    $mwst = "-1";
    $pfand = "0.00";
    $verband = "";
    $herkunft = "";
    $netto = "0.00";

    $splitline = split( $splitat, $line );

    $bnummer = $splitline[1]; // if $pattern matches, this is purely numerical
    $anummer = $bnummer;
    $name = mysql_real_escape_string( $splitline[2] );
    $verband = mysql_real_escape_string( $splitline[5] );
    $herkunft = mysql_real_escape_string( $splitline[6] );

    $gebinde = $splitline[9];

    sscanf( $splitline[9], '%d x %s', & $gebinde, & $einheit );
    $einheit = preg_replace( '/,/', '.', $einheit );

    sscanf( $splitline[11], '%f ', & $netto );

    if( preg_match( '&^[\d\s]*$&', $einheit ) )
      $einheit = "$einheit ST";

    if( $netto < 0.01 or !  kanonische_einheit( $einheit, &$e, &$m, false ) ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: $line" );
      continue;
    }
    $einheit = "$m $e";

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $einheit, $gebinde, $mwst, $pfand, $verband, $herkunft, $netto, 'rapunzel'
    );
    $success++;
  }

  logger( "Rapunzel-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
  open_div( 'ok', '', 'finis.' );
}


function upload_bode() {
  global $katalogkw, $lieferanten_id;

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['katalog']['tmp_name'], $klines );
  $tag = 'Tr'; // Bode: nur ein Katalog, entspricht "Trocken" bei Terra
  $pattern = '/^@\d+\s+@/';
  $splitat = '@';

  $n = 0;
  $success = 0;
  foreach ( $klines as $line ) {
    if( $n++ > 99999 )
      break;

    if( ! preg_match( $pattern, $line ) ) {
      open_div( 'alert', '', "Zeile nicht ausgewertet: $line" );
      continue;
    }

    $anummer = "";
    $bnummer = "";
    $name = "";
    $einheit = "";
    $gebinde = "";
    $mwst = "-1";
    $pfand = "0.00";
    $verband = "";
    $herkunft = "";
    $netto = "0.00";

    $splitline = split( $splitat, $line );
    $bnummer = $splitline[1];
    $bnummer = mysql_real_escape_string( preg_replace( '/\s/', '', $bnummer ) );
    $anummer = $bnummer;

    $name = mysql_real_escape_string( $splitline[2] );
    $verband = mysql_real_escape_string( $splitline[3] );
    $gebinde = $splitline[5];
    $gebinde = preg_replace( '/,/', '.', $gebinde );

    if( preg_match( '&^\s*\d+\s*/&', $gebinde ) ) {
      sscanf( $gebinde, '%u/%s', &$gebinde, &$einheit );
    } else if( preg_match( '&^\s*\d+\s*x&', $gebinde ) ) {
      sscanf( $gebinde, '%ux%s', &$gebinde, &$einheit );
    } else {
      $einheit = $gebinde;
      $gebinde = 1;
    }
    $netto = $splitline[9];
    $netto = sprintf( "%.2lf", preg_replace( '/,/', '.', $netto * 1.0 / $gebinde ) );

    if( preg_match( '&^[\d\s]*$&', $einheit ) )
      $einheit = "$einheit ST";

    if( $netto < 0.01 or !  kanonische_einheit( $einheit, &$e, &$m, false ) ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: $line" );
      continue;
    }
    $einheit = "$m $e";

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $einheit, $gebinde, $mwst, $pfand, $verband, $herkunft, $netto, 'bode'
    );
    $success++;
  }

  logger( "Bode-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
  open_div( 'ok', '', 'finis.' );
}


switch( $lieferant['katalogformat'] ) {
  case 'terra':
    upload_terra();
    break;
  case 'bode':
    upload_bode();
    break;
  case 'rapunzel':
    upload_rapunzel();
    break;
  case 'keins':
  default:
    error( "kann Katalog von {$lieferant['name']} nicht parsen" );
}


?>
