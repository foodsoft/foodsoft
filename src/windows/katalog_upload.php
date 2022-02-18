<?php

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:katalog_upload' );
setWindowSubtitle( 'Katalog einlesen' );

nur_fuer_dienst(4);

need_http_var( 'lieferanten_id', 'U' );
$lieferant = sql_lieferant( $lieferanten_id );

need_http_var( 'katalogkw', 'w' );

open_div( '', '', "Katalog einlesen: Lieferant: {$lieferant['name']} / gültig: $katalogkw" );

if (isset($_FILES['katalog']['error'])) {
  $phpFileUploadErrors = array(
      0 => 'There is no error, the file uploaded with success',
      1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
      2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
      3 => 'The uploaded file was only partially uploaded',
      4 => 'No file was uploaded',
      6 => 'Missing a temporary folder',
      7 => 'Failed to write file to disk.',
      8 => 'A PHP extension stopped the file upload.',
  );

  $code = $_FILES['katalog']['error'];
  $message = $phpFileUploadErrors[$code];

  if ( $code === 0 ) {
    echo "{$message}\n";
  } else {
    error ("Fehler beim Upload: $message (Code $code)");
  }
}

function katalog_update(
  $lieferant_id, $tag, $katalogkw
, $anummer, $bnummer, $name, $bemerkung, $einheit, $gebinde, $mwst, $pfand
, $hersteller, $verband, $herkunft
, $netto
, $ean_einzeln
, $katalogformat
) {

  open_div( 'ok' );
    open_div( 'ok qquad', '', 
            "erfasst: $anummer, $bnummer, $name, $bemerkung, $einheit, "
            . "$gebinde, $mwst, $pfand, $hersteller, $verband, $herkunft, "
            . "$netto, $ean_einzeln, $katalogformat" );
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
    , hersteller
    , bemerkung
    , ean_einzeln
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
    , '$hersteller'
    , '$bemerkung'
    , '$ean_einzeln'
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
    , hersteller='$hersteller'
    , bemerkung='$bemerkung'
    , ean_einzeln='$ean_einzeln'
  " );
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
// format grell.bnn:
//
//  36807;A;20101018;0000;4019736002475;;Brot-Salat 'Gutsherren'          ;;;--;ZWE;;DE;C%;;;0701;;36;;;6 x 200 g;6,00;200 g;1;N;;;;0,20;;;;1;;2,69;;1,63;J;J;0,00;0,00;;;0,00;0,00;;;0,00;0,00;;;0,00;0,00;;;0,00;0,00;;;T;;;;;kg;5,000000;;
//
//  01266;A;20100318;0000;4009233002948;;TK Steinofen Pizzies Salami (2er);(Unsere Natur);St.Pz.Salami2er;--;WGP;;DE;C%;;;1031;;1;;;10 x 2x 150 g;10,00;2x 150 g;1;N;;;;0,30;;;;1;;3,79;;2,45;N;J;0,00;0,00;;;0,00;0,00;;;0,00;0,00;;;0,00;0,00;;;0,00;0,00;;;F;;;;;kg;3,333000;; 
//
// vermutliche semantik:
//
//  0 anummer
//       1?
//         2 datum
//                  3?
//                       4 ean?
//                                     5 ?
//                                      6 name
//                                                             7 8 9 ?
//                                                                  10 hersteller
//                                                                    11?
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

// upload_bnn: für midgard, grell, und vielleicht noch andere
// $katalogformat: könnte immer 'bnn' sein, aber wir lassen das parametrisierbar, falls es sich mal ändert,
// und um mit der existierenden datenbank kompatibel zu bleiben:
//
function upload_bnn( $katalogformat ) {
  global
    $db_handle,
    $katalog_mwst_reduziert,
    $katalog_mwst_standard,
    $katalogkw,
    $lieferanten_id;

  $klines = file( $_FILES['katalog']['tmp_name'] );

  $fuehrungssatz = $klines[0];
  $fuehrungssatz = iconv( 'CP850', 'UTF-8', $fuehrungssatz );
  unset( $klines[0] );

  need( preg_match( '/^BNN;3;/', $fuehrungssatz ), 'kein oder falsches BNN format' );

  $tag = 'Tr'; // Bode, Grell: nur ein Katalog, entspricht "Trocken" bei Terra

  if( preg_match( '/;"?Terra Naturkost /', $fuehrungssatz ) ) {
    // Terra: tell apart several types of catalogues
    if( preg_match( '/;[^;]*(Obst|O&G)/', $fuehrungssatz ) )
      $tag = 'OG';
    else if( preg_match( '/;"?(Naturdrog|Drog)/', $fuehrungssatz ) )
      $tag = 'drog';
    else if( preg_match( '/;"?Trocken/', $fuehrungssatz ) )
      $tag = 'Tr';
    else if( preg_match( '/;"?Frisch/', $fuehrungssatz ) )
      $tag = 'Fr';
    else if( preg_match( '/;"?Gastronomie/', $fuehrungssatz) )
      $tag = 'Gastro';
    else
      error( 'Terra: Katalogformat nicht erkannt' );
    open_div( 'ok', '', "Terra: detektierter Teilkatalog: $tag" );
  }

  $pattern = '/^\d+;[ANWRXV];/';
  $splitat = '/;/';

  $lineCount = 0;
  $success = 0;
  foreach ( $klines as $line ) {
    if( $lineCount++ > 9999 )
      break;
    $line = iconv( "CP850", "UTF-8", $line );

    if( preg_match('/^;;99/', $line) ) {
      open_div( 'ok', '', "Ende-Marke: $line" );
      break;
    }

    if( ! preg_match( $pattern, $line ) ) {
      open_div( 'alert', '', "Zeile nicht ausgewertet: $line" );
      continue;
    }

    $splitline_quoted = preg_split( $splitat, $line );

    // remove quoting and fix erroneously split strings:
    //
    $splitline = array();
    $n = 0;
    while( isset( $splitline_quoted[$n] ) ) {
      $field = $splitline_quoted[$n];
      if( substr( $field, 0, 1 ) !== '"' ) {
        $splitline[] = $field;
        $n++;
        continue;
      }
      while( substr( $field, -1, 1 ) !== '"' ) {
        $n++;
        if( ! isset( $splitline_quoted[$n] ) ) {
          open_div( 'warn', '', 'unmatched open quote' );
          break;
        }
        $field .=  ';' . $splitline_quoted[$n];
      }
      $splitline[] = substr( $field, 1, strlen( $field ) - 2 );
      $n++;
    }

    switch( $splitline[1] ) {
      case 'X':
      case 'V':
        open_div( 'alert', '', "Artikel nicht lieferbar - wird nicht erfasst: $line" );
        continue 2; // "switch" counts as a loop in php!
    }

    $anummer = "";
    $bnummer = "";
    $name = "";
    $bemerkung = "";
    $handelsklasse = "";
    $einheit = "";
    $gebinde = "";
    $mwst = "-1";
    $pfand = "0.00";
    $verband = "";
    $herkunft = "";
    $netto = "0.00";
    $hersteller = "";
    $ean_einzeln = "";

    $bnummer = $splitline[0];
    $bnummer = mysqli_real_escape_string( $db_handle, preg_replace( '/\s/', '', $bnummer ) );
    $anummer = $bnummer;

    $name = mysqli_real_escape_string( $db_handle, $splitline[6] );
    $bemerkung = mysqli_real_escape_string( $db_handle, $splitline[7] );
    $handelsklasse = mysqli_real_escape_string( $db_handle, $splitline[9] );
    $herkunft = mysqli_real_escape_string( $db_handle, $splitline[12] );
    $verband = mysqli_real_escape_string( $db_handle, $splitline[13] );
    $hersteller = mysqli_real_escape_string( $db_handle, $splitline[10] );
    $ean_einzeln = mysqli_real_escape_string( $db_handle, $splitline[4] );
    
    if ( $handelsklasse )
    {
        $handelsklasse = "HK $handelsklasse";
        if ( $bemerkung )
            $bemerkung = "$handelsklasse; $bemerkung";
        else
            $bemerkung = $handelsklasse;
    }
    
    $gebinde = $splitline[22];
    $gebinde = preg_replace( '/,/', '.', trim( $gebinde ) );
    $gebinde = sprintf( '%.2f', $gebinde );

    $einheit = $splitline[23];
    $einheit = preg_replace( '/[(].*$/', '', $einheit ); // geklammerte anmerkungen wegschmeissen
    $einheit = preg_replace( '/,/', '.', trim( $einheit ) );

    // bnn: gelegentlich einheiten wie: 3 x 100g:
    if( preg_match( '/\d *x *\d/', $einheit ) ) {
      $extra_mult = sprintf( '%d', $einheit );
      $einheit = preg_replace( '/^.*\d *x *(\d.*)$/', '${1}', $einheit ); 
    } else {
      $extra_mult = 1;
    }

    switch( trim( $splitline[33] ) ) {
      case '1':
        $mwst = $katalog_mwst_reduziert;
        break;
      case '2':
        $mwst = $katalog_mwst_standard;
        break;
      default:
        break;
    }

    $netto = $splitline[37];
    $netto = sprintf( "%.2lf", preg_replace( '/,/', '.', trim( $netto ) ) );

    /* Allow for products with price/VAT 0, e.g. deposit form pad */
    if( ( $netto < 0 ) || ( $mwst < 0 ) || ! ( list( $m, $e ) = kanonische_einheit( $einheit, false ) ) ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: [einheit:$einheit,netto:$netto,mwst:$mwst] $line " );
      continue;
    }
    $m *= $extra_mult;
    $einheit = "$m $e";

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $bemerkung, $einheit, $gebinde, $mwst, $pfand, $hersteller, $verband, $herkunft, $netto, $ean_einzeln, $katalogformat
    );
    $success++;
  }

  logger( "$katalogformat-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $lineCount" );
  open_div( 'ok', '', 'finis.' );
}


switch( $lieferant['katalogformat'] ) {
  case 'midgard':
  case 'grell':
  case 'bnn':
    upload_bnn( $lieferant['katalogformat'] );
    break;
  case 'keins':
  default:
    error( "kann Katalog von {$lieferant['name']} nicht parsen" );
}
