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


function upload_terra() {
  global $db_handle, $katalogkw, $lieferanten_id;

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['katalog']['tmp_name'], $klines );

  $tag = false;
  $fields = false;
  $pattern = false;
  $splitat = false;
  $splitline = false;

  $n = 0;
  $success = 0;
  foreach ( $klines as $line ) {
    if( $n++ > 99999 )
      break;

    // neu ab 2011kw09:
    //  - komische Waehrungscodes, und
    //  - jetzt nicht nur spaces, sondern auch punkte und kommata in den B-nummern:
    //  - Kataloge tr, Fr und drog (und andere?) koennen zusaetzliche Spalte "empf.VK" vor der MWSt enthalten
    //  - und: komma statt punkt in der vpe (= gebindegroesse mit einheit)
    // nicht ausgewertet: 12923117@27 7, 00@Adelholzener Classic PET  1Ltr@12,00 FL@AHZ@DE@##@0.67[$ €407]@@J@@19@4005906002079@
    // nicht ausgewertet: 759582@54 .2 42@basis sensitiv Zahncreme 75ml@1,00 ST@LAV@DE@@1.16@@J@1.99@19@4021457470334@
    //
    $line = preg_replace( '/\[\$ €407\]/', '', $line );

    if( $splitat )
      $splitline = preg_split( $splitat, $line );

    if( ! $tag || ! $fields || ! $splitat || ! $pattern ) {
      echo "analyzing line: $line<br>";
      // Art.Nr.@@Bestell-Nr.@@Milch@@@@@@Inhalt@Einh.@Land@@IK@Verband@@Netto-Preis @@/Einh.@empf. VK@@MwSt. %@@EAN-Code@@@

      // Fr: ganz alter stil:
      //
      if( preg_match( '&^Preisliste:\s+Mopro&', $line ) ) {
        $tag='Fr';
        $splitat = '/@+/';
        $fields = array( 'anummer', 'bnummer', 'name', '', 'herkunft', 'verband', 'gebinde', 'einheit', 'netto', 'mwst', '', '' );
        $pattern = '/^[\d\s]+@+[\d\s]+@/';
      }

      // Fr: aktueller (2011) stil: gibt es mit und ohne empf.VK-spalte, wir warten also auf den Tabellenkopf, um $fields zu setzen:
      //
      if( preg_match( '&^Preisliste\s+Frischesortiment&', $line ) ) {
        $tag='Fr';
        $splitat = '/@/';
        // Artikelnr.               @Bestellnr.@ Beschreibung@VPE@Lieferant @Land      @IK        @Netto-Preis@@@MwSt %@EAN- Code@
        // $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'lieferant', 'herkunft', 'verband', 'netto', '', '', 'mwst', '', '' );
        $pattern = '/^[\d\s]+@+[\d\s]+@/';
      }
      if( $splitline && ( $tag == 'Fr' ) && ! $fields ) {
        if( preg_match( '/mwst/i', $splitline[10] ) ) {          // ohne 'empf.VK'-Spalte
          $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'lieferant', 'herkunft', 'verband', 'netto', '', '', 'mwst', '', '' );
        } else if( preg_match( '/mwst/i', $splitline[11] ) ) {   // mit 'empf.VK'-Spalte
          $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'lieferant', 'herkunft', 'verband', 'netto', '', '', '', 'mwst', '', '' );
        }
      }

      if( preg_match( "&^Art.Nr.@Bestell-Nr.@ZITRUS-FR\xdcCHTE *@Inhalt *@Einh. *@Herk. *@HKL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@&", $line ) ) {
        $tag='OG';
        $splitat = '/@/';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', '', 'verband', 'netto', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }
      if( preg_match( '&^Art.Nr.@Bestell-Nr.@Zitrus-Fr.*chte *@Inhalt *@Einh. *@Herk. *@H?KL@IK@Verband@ *Netto-Preis *@/Einh.@MwSt.%@Bemerkung@&', $line ) ) {
        /// lyzing line: Art.Nr.@Bestell-Nr.@Zitrus-Früchte  @Inhalt  @Einh.  @Herk.  @HKL@IK@Verband@Netto-Preis    @/Einh.@MwSt.%@Bemerkung@
        $tag='OG';
        $splitat = '/@/';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', '', 'verband', 'netto', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }
      if( preg_match( "#^Art\\.Nr\\.@Bestell-Nr\\.@Obst & Gemüse@Inhalt@Einh\\.@Herk.@HKL@IK@Zertifizierung@@Netto-Preis *@/Einh\\.@MwSt\\.%@Bemerkung@#", $line ) ) {
        /// Art.Nr.@Bestell-Nr.@Obst & Gemüse@Inhalt@Einh.@Herk.@HKL@IK@Zertifizierung@@Netto-Preis @/Einh.@MwSt.%@Bemerkung@@
        $tag='OG';
        $splitat = '/@/';
        $fields = array( 'anummer', 'bnummer', 'name', 'gebinde', 'einheit', 'herkunft', '', '', 'verband', '',  'netto', '', 'mwst', '' );
        $pattern = '/^[\d\s]+@[\d\s]+@/';
      }

      if( preg_match( '&^Preisliste\s+Drogeriewaren&', $line ) ) {
        // 705022  @ 45 01 2  @  Babyflasche 2x125ml @  1 SET @ MLL @ DE @ @ 5.46[$ 407] @ @ J @ 19 @4031075402211@
        // 759582  @ 54 .2 42 @  basis sensitiv Zahncreme 75ml @ 1,00 ST @LAV @DE@@1.16@@J@1.99@19@4021457470334@

        $tag='drog';
        $splitat = '/@/';
        $pattern = '/^[\d\s]+@[\d\s,.]+@/';
      }
      if( $splitline && ( $tag == 'drog' ) ) {
        if( preg_match( '/mwst/i', $splitline[10] ) ) {          // ohne 'empf.VK'-Spalte
          $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        } else if( preg_match( '/mwst/i', $splitline[11] ) ) {   // mit 'empf.VK'-Spalte
          $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', '', 'mwst' );
        }
      }

      if( preg_match( '&^Preisliste\s+Trockensortiment&', $line ) ) {
        // Artikelnr.@Bestellnr.@ Beschreibung@VPE@Liefera@Land@IK@Netto-Preis@@@MwSt.%@EAN- Code@
        // ab 2010: 402912 @ 34 69 @Granatapfel, pur 0,75Ltr@ @6 FL @VOE @DE @C% @4.97[$ 407]@@J@19@4015533015762@
        // ...und nun: 129210 @ 26 4   @Volvic Wasser PET 1,5Ltr    @  6 FL @ VOL @FR @## @0.83[$ 407]@@J@19@3057640108433@
        //             402202 @3. 32 5,@Heimische Apfelschorle 1Ltr@6,00 FL @ VOE @DE @DD @1.02       @@J@@19@4015533018053@
        $tag = 'Tr';
        $splitat = '/@/';
        // $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst', 'mwst2' );
        // $fields = array( 'anummer', 'bnummer', 'name', '', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        $pattern = '/^[\d\s]+@[\d\s,.]+@/';
      }
      if( $splitline && ( $tag == 'Tr' ) ) {
        if( preg_match( '/mwst/i', $splitline[10] ) ) {          // ohne 'empf.VK'-Spalte
          $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', 'mwst' );
        } else if( preg_match( '/mwst/i', $splitline[11] ) ) {   // mit 'empf.VK'-Spalte
          $fields = array( 'anummer', 'bnummer', 'name', 'vpe', 'verband', 'herkunft', '', 'netto', '', '', '', 'mwst' );
        }
      }

      if( $tag && $fields && $splitat && $pattern ) {
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
    $bemerkung = "";
    $einheit = "";
    $gebinde = "";
    $mwst = "7.00";
    $pfand = "0.00";
    $hersteller = "";
    $verband = "";
    $herkunft = "";
    $netto = "0.00";
    $vpe = "";
    $ean_einzeln = "";

    $i=0;
    foreach( $splitline as $field ) {
      if( isset( $fields[$i] ) and $fields[$i] ) {
        ${$fields[$i]} = mysqli_real_escape_string($db_handle, $field);
      }
      $i++;
    }
    // drop trailing garbage in $netto:
    $netto = sprintf( "%.2lf", preg_replace( '/,/', '.', $netto ) );
    $mwst = sprintf( "%.2lf", preg_replace( '/,/', '.',  $mwst ) );
    $pfand = sprintf( "%.2lf", preg_replace( '/,/', '.', $pfand ) );
    $name = mysqli_real_escape_string( $db_handle, $name );

    // drop spurious whitespace from numbers:
    $anummer = preg_replace( '/\s/', '', $anummer );
    $bnummer = preg_replace( '/[\s.,]/', '', $bnummer );

    if( $vpe ) {
      $gebinde = sprintf( "%.2lf", preg_replace( '/,/', '.', $vpe ) );
      $einheit =  preg_replace( '/^[[:digit:]., ]*([[:alpha:]]*)$/', '${1}', $vpe );
    } else {
      $gebinde = sprintf( "%.2lf", preg_replace( '/,/', '.', $gebinde ) );
    }

    if( $netto < 0.01 or $mwst < 0.01 ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: $line" );
      continue;
    }

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $bemerkung, $einheit, $gebinde, $mwst, $pfand, $hersteller, $verband, $herkunft, $netto, $ean_einzeln, 'terra_xls'
    );
    $success++;
  }

  logger( "Terra-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
  open_div( 'ok', '', 'finis.' );
}


function upload_rapunzel() {
  global $db_handle, $katalogkw, $lieferanten_id;

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['katalog']['tmp_name'], $klines );
  $tag = 'Tr'; // Rapunzel: nur ein Katalog, entspricht "Trocken" bei Terra
  $pattern = '/^[^@]*@\d+@/';
  $splitat = '/@/';

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
    $bemerkung = "";
    $einheit = "";
    $gebinde = "";
    $mwst = "-1";
    $pfand = "0.00";
    $hersteller = "";
    $verband = "";
    $herkunft = "";
    $netto = "0.00";
    $ean_einzeln = "";

    $splitline = preg_split( $splitat, $line );

    $bnummer = $splitline[1]; // if $pattern matches, this is purely numerical
    $anummer = $bnummer;
    $name = mysqli_real_escape_string( $db_handle, $splitline[2] );
    $verband = mysqli_real_escape_string( $db_handle, $splitline[5] );
    $herkunft = mysqli_real_escape_string( $db_handle, $splitline[6] );

    $gebinde = $splitline[9];

    sscanf( $splitline[9], '%d x %s', $gebinde, $einheit );
    $einheit = preg_replace( '/,/', '.', $einheit );

    sscanf( $splitline[11], '%f ', $netto );

    if( preg_match( '&^[\d\s]*$&', $einheit ) )
      $einheit = "$einheit ST";

    if( $netto < 0.01 or ! ( list( $m, $e ) = kanonische_einheit( $einheit, false ) ) ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: $line" );
      continue;
    }
    $einheit = "$m $e";

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $bemerkung, $einheit, $gebinde, $mwst, $pfand, $hersteller, $verband, $herkunft, $netto, $ean_einzeln, 'rapunzel'
    );
    $success++;
  }

  logger( "Rapunzel-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
  open_div( 'ok', '', 'finis.' );
}


function upload_bode() {
  global $db_handle, $katalogkw, $lieferanten_id;

  exec( './antixls.modif -c 2>/dev/null ' . $_FILES['katalog']['tmp_name'], $klines );
  $tag = 'Tr'; // Bode: nur ein Katalog, entspricht "Trocken" bei Terra
  $pattern = '/^@\d+\s*@/';
  $splitat = '/@/';

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
    $bemerkung = "";
    $einheit = "";
    $gebinde = "";
    $mwst = "-1";
    $pfand = "0.00";
    $hersteller = "";
    $verband = "";
    $herkunft = "";
    $netto = "0.00";
    $ean_einzeln = "";

    $splitline = preg_split( $splitat, $line );
    $bnummer = $splitline[1];
    $bnummer = mysqli_real_escape_string( $db_handle, preg_replace( '/\s/', '', $bnummer ) );
    $anummer = $bnummer;

    $name = mysqli_real_escape_string( $db_handle, $splitline[2] );
    $verband = mysqli_real_escape_string( $db_handle, $splitline[3] );
    $gebinde = $splitline[5];
    $gebinde = preg_replace( '/,/', '.', $gebinde );

    if( preg_match( '&^\s*\d+\s*/&', $gebinde ) ) {
      sscanf( $gebinde, '%u/%s', $gebinde, $einheit );
    } else if( preg_match( '&^\s*\d+\s*x&', $gebinde ) ) {
      sscanf( $gebinde, '%ux%s', $gebinde, $einheit );
    } else {
      $einheit = $gebinde;
      $gebinde = 1;
    }
    $netto = $splitline[9];
    $netto = sprintf( "%.2lf", preg_replace( '/,/', '.', $netto * 1.0 / $gebinde ) );

    if( preg_match( '&^[\d\s]*$&', $einheit ) )
      $einheit = "$einheit ST";

    if( $netto < 0.01 or ! ( list( $m, $e ) = kanonische_einheit( $einheit, false ) ) ) {
      open_div( 'warn', '', "Fehler bei Auswertung der Zeile: $line" );
      continue;
    }
    $einheit = "$m $e";

    katalog_update( $lieferanten_id, $tag, $katalogkw
    , $anummer, $bnummer, $name, $bemerkung, $einheit, $gebinde, $mwst, $pfand, $hersteller, $verband, $herkunft, $netto, $ean_einzeln, 'bode'
    );
    $success++;
  }

  logger( "Bode-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
  open_div( 'ok', '', 'finis.' );
}


//
//  0 anummer
//       1?
//         2 datum
//                  3?
//                       4 ean?
//                                     5 ?
//                                      6 name
//                                                             7 bemerkung
//                                                               8 9 ?
//                                                                   10 hersteller
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

// upload_bnn: fuer midgard, grell, und vielleicht noch andere
// $katalogformat: koennte immer 'bnn' sein, aber wir lassen das parametrisierbar, falls es sich mal aendert,
// und um mit der existierenden datenbank kompatibel zu bleiben:
//
function upload_bnn( $katalogformat ) {
  global $db_handle, $katalogkw, $lieferanten_id;

  $klines = file( $_FILES['katalog']['tmp_name'] );

  $fuehrungssatz = $klines[0];
  unset( $klines[0] );

  need( preg_match( '/^BNN;3;/', $fuehrungssatz ), 'kein oder falsches BNN format' );

  $tag = 'Tr'; // Bode, Grell: nur ein Katalog, entspricht "Trocken" bei Terra

  if( preg_match( '/;"Terra Naturkost /', $fuehrungssatz ) ) {
    // Terra: unterscheidet 4 Kataloge:
    if( preg_match( '/;"[^"]*(Obst|O&G)/', $fuehrungssatz ) )
      $tag = 'OG';
    else if( preg_match( '/;"(Naturdrog|Drog)/', $fuehrungssatz ) )
      $tag = 'drog';
    else if( preg_match( '/;"Trocken/', $fuehrungssatz ) )
      $tag = 'Tr';
    else if( preg_match( '/;"Frisch/', $fuehrungssatz ) )
      $tag = 'Fr';
    else
      error( 'Terra: Katalogformat nicht erkannt' );
    open_div( 'ok', '', "Terra: detektierter Teilkatalog: $tag" );
  }

  $pattern = '/^\d+;[ANWRXV];/';
  $splitat = '/;/';

  $n = 0;
  $success = 0;
  foreach ( $klines as $line ) {
    if( $n++ > 9999 )
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
        $mwst = "5.00";
        break;
      case '2':
        $mwst = "16.00";
        break;
      default:
        break;
    }

    $netto = $splitline[37];
    $netto = sprintf( "%.2lf", preg_replace( '/,/', '.', trim( $netto ) ) );

    if( ( $netto < 0.01 ) || ( $mwst < 0 ) || ! ( list( $m, $e ) = kanonische_einheit( $einheit, false ) ) ) {
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

  logger( "$katalogformat-Katalog erfasst: $tag / $katalogkw: erfolgreich geparst: $success Zeilen von $n" );
  open_div( 'ok', '', 'finis.' );
}



switch( $lieferant['katalogformat'] ) {
  case 'terra_xls':
    upload_terra();
    break;
  case 'bode':
    upload_bode();
    break;
  case 'rapunzel':
    upload_rapunzel();
    break;
  case 'midgard':
  case 'grell':
  case 'bnn':
    upload_bnn( $lieferant['katalogformat'] );
    break;
  case 'keins':
  default:
    error( "kann Katalog von {$lieferant['name']} nicht parsen" );
}


?>
