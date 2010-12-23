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
      //
      // 130002 @300 @ 0,5l Vollmilch im Milchbeutel 3,7% Brodowin @ BW @REG @DD @6@ST @0.54@7@4022894000054@
      // 130609 @609 @ S Haselnuss Joghurt 150g                    @ SB   @DE   @DB @ 10    @ BE  @0.42        @7      @4008471506812@
      // Art.Nr.@Bestell-Nr.@Milch                                 @Herst.@Herk.@IK @ Inhalt@Einh.@Netto-Preis @MwSt. %@EAN-Code@
      //
      if( preg_match( '&^Art.Nr. *@+Bestell-Nr. *@+Milch *@+Inhalt *@Einh. *@+Land *@+IK *@+Verband *@+ *Netto-Preis *@+/Einh. *@empf. VK@+MwSt. % *@+EAN-Code *@+&' , $line ) ) {
        $tag = "Fr";
        $splitat = '@+';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', 'verband', 'netto', '', '', 'mwst', '' );
        // $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', 'verband', 'netto', 'mwst', '' );
        $pattern = '/^[\d\s]+@+[\d\s]+@/';
      }
      if( preg_match( '&^Preisliste:\s+Mopro&', $line ) ) {
        $tag='Fr';
        $splitat = '@+';
        $fields = array( 'anummer', 'bnummer', 'name', '', 'herkunft', 'verband', 'gebinde', 'einheit', 'netto', 'mwst', '', '' );
        $pattern = '/^[\d\s]+@+[\d\s]+@/';
      }

      if( preg_match( "&^Art.Nr.@Bestell-Nr.@ZITRUS-FR\xdcCHTE *@Inhalt *@Einh. *@Herk. *@HKL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@&", $line ) ) {
        $tag='OG';
        $splitat = '@';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', '', 'verband', 'netto', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }
      if( preg_match( '&^Art.Nr.@Bestell-Nr.@Zitrus-Fr.*chte *@Inhalt *@Einh. *@Herk. *@HKL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@&', $line ) ) {
        /// lyzing line: Art.Nr.@Bestell-Nr.@Zitrus-Früchte  @Inhalt  @Einh.  @Herk.  @HKL@IK@Verband@Netto-Preis    @/Einh.@MwSt.%@Bemerkung@
        $tag='OG';
        $splitat = '@';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', '', 'verband', 'netto', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }

      if( preg_match( '&^Preisliste\s+Drogeriewaren&', $line ) ) {
        // 705022  @ 45 01 2  @  Babyflasche 2x125ml @  1 SET @ MLL @DE @ @ 5.46[$ 407] @ @ J @ 19 @4031075402211@
        $tag='drog';
        $splitat = '@';
        $fields = array( 'anummer', 'bnummer', 'name',  'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        // $fields = array( 'anummer', 'bnummer', 'name', '', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }

      if( preg_match( '&^Preisliste\s+Trockensortiment&', $line ) ) {
        // Artikelnr.@Bestellnr.@ Beschreibung@VPE@Liefera@Land@IK@Netto-Preis@@@MwSt.%@EAN- Code@
        // ab 2010: 402912 @ 34 69 @Granatapfel, pur 0,75Ltr@ @6 FL @VOE @DE @C% @4.97[$ 407]@@J@19@4015533015762@
        // ...und nun: 129210 @ 26 4  @Volvic Wasser PET 1,5Ltr@  6 FL @VOL @FR @## @0.83[$ 407]@@J@19@3057640108433@
        $tag = 'Tr';
        $splitat = '@';
        $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        // $fields = array( 'anummer', 'bnummer', 'name', '', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
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

//
// format midgard.bnn:
//
// 30503  ;X ;20060410 ; ; ; ;Naturata Dinkel Bandnudeln  ; ; ; ;Na ; ;D ;DEM  ; ;     ;1000;13 ;43 ;  ;1 ; 10 x 500g; 10 ; 500g  ; 1 ; ; ; ; ;      ; ; ; ;1 ; ;      ; ; 1,86  ;J;;;;;;;;;;;;;;;;;;;;;;T;;;;;;;;
// 25540  ;A ;20090901 ; ; ; ;Schoko-Müsli               ; ; ; ;Rg ; ;D ;kbA  ; ;     ;900 ;9  ;51 ;  ;1 ; 6 x 750g ;  6 ; 750g  ; 1 ; ; ; ; ;4,73  ; ; ; ;1 ; ; 4,49 ; ; 3,07  ;J;;;;;;;;;;;;;;;;;;;;;;T;;;;;;;;
// 353555 ;A ;20090402 ; ; ; ;Kichererbsen, gekocht       ; ; ; ;LS ; ;I ;kbA  ; ;     ;300 ;3  ;35 ;  ;1 ; 6 x 340g ;  6 ; 340g  ; 1 ; ; ; ; ;3,348 ; ; ; ;1 ; ; 2,45 ; ; 1,67  ;J;;;;;;;;;;;;;;;;;;;;;;T;;;;;;;;
// 424902 ;A ;20090403 ; ; ; ;Bio-Hirse aus dem Spreewald ; ; ; ;SH ; ;D ;kbA  ; ;     ;    ;   ;57 ;  ;1 ; 6 x 1 kg ;  6 ; 1 kg  ; 1 ; ; ; ; ;6     ; ; ; ;1 ; ;      ; ; 2,4   ;J;;;;;;;;;;;;;;;;;;;;;;T;;;;;1kg;1;;
// 424903 ;A ;20090402 ; ; ; ;Bio-Hirse aus dem Spreewald ; ; ; ;SH ; ;D ;kbA  ; ;     ;    ;   ;57 ;  ;1 ; 1 x 25kg ;  1 ; 25kg  ; 1 ; ; ; ; ;25    ; ; ; ;1 ; ;      ; ; 46,42 ;J;;;;;;;;;;;;;;;;;;;;;;T;;;;;;;;
// 121580 ;A ;20090512 ; ; ; ;Ziegengouda jung ca.4kg     ; ; ; ;Au ; ;NL;BL   ; ;     ;200 ;2  ;3  ;  ;1 ; 1 x kg   ;  1 ; kg    ; 1 ; ; ; ; ;4     ; ; ; ;1 ; ;      ; ; 13,92 ;J;;;;;;;;;;;;;;;;;;;;;;F;;;;;;;;
// 323660 ;A ;20090902 ; ; ; ;Zwiebelschmelz              ; ; ; ;ZG ; ;D ;kbA  ; ;     ;700 ;7  ;29 ;  ;1 ; 6 x 150g ;  6 ; 150g  ; 1 ; ; ; ; ;1,9   ; ; ; ;1 ; ; 2,89 ; ; 1,89  ;N;;;;;;;;;;;;;;;;;;;;;;T;;;;;;;;
// 101152 ;A ;20090402 ; ; ; ;BGL Vollmilch Flasche 3,8%  ; ; ; ;Pi ; ;D ;DEM  ; ;     ;200 ;2  ;10 ;  ;1 ; 6 x 1 Ltr;  6 ; 1 Ltr ; 1 ; ; ; ; ;10,01 ; ; ; ;1 ; ; 1,55 ; ; 1,17  ;J;;;;;;;;;;;;;;;;;;;;;;F;;;;;;;;
//
// vermutliche semantik:
//
//  0 anummer
//        1?
//           2datum    
//                     4 5 ean?
//                           6 name
//                                                        7 8 9 ?
//                                                             10 hersteller
//                                                                  11?
//                                                                     12 land
//                                                                       13 verband
//                                                                             14 ...                20  ?
//                                                                                                       21 geb*einh
//                                                                                                                  23 gebinde
//                                                                                                                        23 ; Leinheit
//                                                                                                                               24..28?
//                                                                                                                                          29 bruttogewicht
//                                                                                                                                                30..32 ?
//                                                                                                                                                     33: mwst: 1=7, 2=19?
//                                                                                                                                                       34 ?
//                                                                                                                                                              35 evp
//                                                                                                                                                               36 ?
//                                                                                                                                                                 37 nettopreis

function upload_midgard() {
  global $katalogkw, $lieferanten_id;

  $klines = file( $_FILES['katalog']['tmp_name'] );
  $tag = 'Tr'; // Bode: nur ein Katalog, entspricht "Trocken" bei Terra
  $pattern = '/^\d+;[AX];/';
  $splitat = ';';

  $n = 0;
  $success = 0;
  foreach ( $klines as $line ) {
    if( $n++ > 9999 )
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

    // var_dump( $splitline );
    
    $bnummer = $splitline[0];
    $bnummer = mysql_real_escape_string( preg_replace( '/\s/', '', $bnummer ) );
    $anummer = $bnummer;

    $name = mysql_real_escape_string( $splitline[6] );
    $herkunft = mysql_real_escape_string( $splitline[12] );
    $verband = mysql_real_escape_string( $splitline[13] );

    $gebinde = $splitline[22];
    $gebinde = preg_replace( '/,/', '.', trim( $gebinde ) );
    $gebinde = sprintf( '%d', $gebinde );

    $einheit = $splitline[23];
    $einheit = preg_replace( '/,/', '.', trim( $einheit ) );

    switch( trim( $splitline[33] ) ) {
      case '1':
        $mwst = "7.00";
        break;
      case '2':
        $mwst = "19.00";
        break;
      default:
        break;
    }

    $netto = $splitline[37];
    $netto = sprintf( "%.2lf", preg_replace( '/,/', '.', trim( $netto ) ) );

    if( ( $netto < 0.01 ) || ( $mwst < 0 ) || !  kanonische_einheit( $einheit, &$e, &$m, false ) ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: $line" );
      continue;
    }
    $einheit = "$m $e";

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $einheit, $gebinde, $mwst, $pfand, $verband, $herkunft, $netto, 'midgard'
    );
    $success++;
  }

  logger( "Midgard-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
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
  case 'midgard':
    upload_midgard();
    break;
  case 'keins':
  default:
    error( "kann Katalog von {$lieferant['name']} nicht parsen" );
}


?>
