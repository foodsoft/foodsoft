<?php

//error_reporting(E_ALL);
// $_SESSION['LEVEL_CURRENT'] = LEVEL_IMPORTANT;

get_http_var( 'plan_dienst', '/^[0-9\/]+$/', '1/2', true ); // fuer anzeige rotationsplan
get_http_var( 'options', 'u', 0, true );

define( 'OPTION_SHOW_HISTORY', 1 );

$editable = ! $readonly;

get_http_var( 'action', 'w', '' );
$editable or $action = false;

if( $action ) {
  $parts = explode( '_', $action );
  $action = $parts[0];
  if( count( $parts ) > 1 ) {
    $id = sprintf( '%u', $parts[1] );
  } else {
    $id = 0;
  }
}

get_http_var("startdatum_day",'U',0);
get_http_var("startdatum_month",'U',0);
get_http_var("startdatum_year",'U',0);
get_http_var("dienstinterval","U",7) or $dienstinterval = 7;
get_http_var("dienstanzahl","U",8) or $dienstanzahl = 8;
get_http_var("personen_1","u",2) or $personen_1 = 2;
get_http_var("personen_3","u",1) or $personen_3 = 1;
get_http_var("personen_4","u",2) or $personen_4 = 2;

if( $startdatum_day && $startdatum_month && $startdatum_year ) {
  $startdatum = "$startdatum_year-$startdatum_month-$startdatum_day";
} else {
  $startdatum = false;
}

switch( $action ) {
  case 'diensteErstellen':
    need( $startdatum );
    need( $dienstinterval );
    need( $dienstanzahl );
    need( $personen_1 >= 0 );
    need( $personen_3 >= 0 );
    need( $personen_4 >= 0 );
    $personenzahlen = array( '1/2' => $personen_1, '3' => $personen_3, '4' => $personen_4 );
    create_dienste( $startdatum, $dienstinterval, $dienstanzahl, $personenzahlen );
    break;
  case 'dienstLoeschen':
    need_http_var( 'message', 'U' );
    sql_delete_dienst( $message );
    break;
  case 'diensteTagLoeschen':
    need_http_var( 'message', 'U' );
    $dienst = sql_dienst( $message );
    $dienste = sql_dienste( "lieferdatum = '{$dienst['lieferdatum']}'" );
    foreach( $dienste as $d )
      sql_delete_dienst( $d['id'] );
    break;
  case 'dienstEinfuegen':
    need_http_var( 'message', '/^[0-9-]+_[0-9\/]+$/' );
    $temp = explode( '_', $message );
    sql_create_dienst( $temp[0], $temp[1] );
    break;
  case 'moveUp':
    need_http_var( 'message', 'U' );
    sql_change_rotationsplan( $message, $plan_dienst, false );
    break;
  case 'moveDown':
    need_http_var( 'message', 'U' );
    sql_change_rotationsplan( $message, $plan_dienst, true );
    break;
  case 'uebernehmen':
    need( $id );
    get_http_var( 'message', 'u', 0 ) or $message = 0;
    $abgesprochen = $message;
    $dienst = sql_dienst( $id );
    if( $dienst["status"]=="Offen" || $abgesprochen ) {
      sql_dienst_akzeptieren( $id, $abgesprochen );
    } else {
      open_div( 'warn' );
      ?> Dies müsste mit der andern Gruppe abgesprochen sein oder die Gruppe ist nach mehreren
         Versuchen (Telefon und Email) nicht erreichbar 
      <?php
      echo fc_action( 'class=button,text=Klar', sprintf( 'action=uebernehmen_%u,message=1', $id ) );
      close_div();
      smallskip();
    }
    break;
  // case 'wirdOffen':
  //  need( $id );
  //  sql_dienst_wird_offen( $id );
  //  break;
  case 'abtauschen':
    need( $id );
    $dienst = sql_dienst( $id );
    need( ! $dienst['soon'] );
    get_http_var( 'tausch_id', 'U', false );
    if( ! $tausch_id ){
      $tauschmoeglichkeiten = sql_dienste_tauschmoeglichkeiten( $id );
      if( ! $tauschmoeglichkeiten ) {
        sql_dienst_wird_offen( $id );
        open_div( 'warn', '', 'Keine Tauschmöglichkeit: Dienst ist jetzt offen!' );
      } else {
        open_div( 'warn' );
          open_form( '', sprintf( 'action=abtauschen_%u', $id ) );
            open_div( 'warn' );
              echo 'Bitte Ausweichdatum auswählen: ';
              open_select( 'tausch_id' );
                echo "<option value=''>(bitte auswaehlen)</option>";
                foreach( $tauschmoeglichkeiten as $t ) {
                  echo "<option value={$t['id']}>{$t['lieferdatum']}</option>";
                }
              close_select();
              submission_button( 'Dieses Datum geht' );
            close_div();
          close_form();
        close_div();
      }
    } else {
      sql_dienst_abtauschen( $id, $tausch_id );
    }
    break;
  case 'akzeptieren':
    need( $id );
    sql_dienst_akzeptieren( $id );
    break;
  case 'bestaetigen':
    need( $id , "Fehler: id: $id" );
    sql_dienst_akzeptieren( $id, 0, 'Bestaetigt' );
    break;
  case "gruppeAendern":
    need_http_var( 'message', 'u' );
    need( $id );  // hier: eine dienste.id!
    $gruppe_neu_id = $message;
    sql_dienst_gruppe_aendern( $id, $gruppe_neu_id );
    break;
  case "personAendern":
    need_http_var( 'message', 'u' );
    need( $id );  // hier: eine dienste.id!
    $person_neu_id = $message;
    sql_dienst_person_aendern( $id, $person_neu_id );
    break;
}


if( hat_dienst(5) ) {
  open_div( '', 'id=Zusatz' );

    ?> <h1>Dienste erstellen</h1> <?php

    $startdatum = get_latest_dienst( $dienstinterval );
    open_form( '', 'action=diensteErstellen' );
      open_table( 'smallskip' );
        open_tr();
          open_td( '', '', "Verteile Dienste fuer " . int_view( $dienstanzahl, 'dienstanzahl', 2 ) . " Liefertage," );
        open_tr();
          open_td( 'qquad', '', "im Abstand von je " . int_view( $dienstinterval, 'dienstinterval', 2 ) . " Tagen," );
        open_tr();
          open_td( 'qquad', '', "beginnend mit dem " . date_view( $startdatum, 'startdatum' ) );
        open_tr();
          open_td( 'qquad' );
          smallskip();
          echo "Eingeteilt werden für Dienst...";
        open_tr();
          open_td( 'qquad' );
            open_span( 'qquad', '', "1/2: " . int_view( $personen_1, "personen_1", 1 ) );
            open_span( 'qquad', '', "3: " . int_view( $personen_3, "personen_3", 1 ) );
            open_span( 'qquad', '', "4: " . int_view( $personen_4, "personen_4", 1 ) . " Personen" );
        open_tr();
          open_td( 'right', '' );
            smallskip();
            submission_button( 'Dienste Erstellen' );
      close_table();
    close_form();
    smallskip();

    ?> <h1>Rotationsplan</h1> <?php

    ?> Rotationsplan für <?php
     open_select( 'plan_dienst', 'autoreload' );
       foreach( array( '1/2', '3', '4' ) as $dienst ) {
         $selected = ( $plan_dienst == $dienst ? 'selected' : '' );
         echo "<option value='$dienst' $selected>Dienst $dienst</option>";
       }
     close_select();
    ?> bearbeiten: <?php

    open_table( 'smallskip' );
      foreach( sql_rotationsplan( $plan_dienst ) as $mitglied ) {
        $id = $mitglied['gruppenmitglieder_id'];
        open_tr( 'smallskip' );
          open_th( '', '', $mitglied['nr'] );
          open_td( 'quad', '', fc_link( 'gruppenmitglieder', array(
              'class' => 'href', 'gruppen_id' => $mitglied['gruppen_id']
            , 'text' => "Gruppe {$mitglied['gruppennummer']}: {$mitglied['vorname']}"
          ) ) );
          open_td( '', '', fc_action( 'update,text=UP', sprintf( "action=moveUp,message=%u", $id ) ) );
          open_td( '', '', fc_action( 'update,text=DOWN', sprintf( "action=moveDown,message=%u", $id ) ) );
        close_tr();
      }
    close_table();

  close_div();
}


?> <h1>Dienstliste</h1> <?php

open_div( 'kommentar' );
  // open_span( '', '',
  //  "Zum Abtauschen von Diensten: Beide Gruppen klicken auf <code>kann doch nicht</code>
  //   und übernehmen anschliessend den von der andern Gruppe entstandenen offen Dienst." );
  open_span( '', '', wikiLink("foodsoft:dienstplan", "Mehr Infos im Wiki..." ) );
close_div();

medskip();
open_table( 'menu', "id='option_menu_table'" );
  open_th( '', "colspan='2'", 'Anzeigeoptionen' );
  open_td();
    option_checkbox( 'options', OPTION_SHOW_HISTORY, " historische Dienste anzeigen" );
close_table();
medskip();

$dienstnamen = array( '1/2', '3', '4' );

open_table( 'list' );
  open_th( '', '', 'Datum' );
  open_th( '', '', 'Dienst 1/2' );
  open_th( '', '', 'Dienst 3' );
  open_th( '', '', 'Dienst 4' );

  $dienste = sql_dienste();

  $currentDate = "initial";
  $dienst = current( $dienste );
  while( $dienst ) {
    if( $dienst['historic'] && ! ( $options & OPTION_SHOW_HISTORY ) ) {
      $dienst = next( $dienste );
      continue;
    }
    if( $dienst["lieferdatum"] != $currentDate ) {
      $currentDate = $dienst["lieferdatum"];
      open_tr();
      open_th( 'top' );
        open_div( '', '', $currentDate );
        if( hat_dienst(5) && ! $readonly ) {
          open_div( 'bigskip center', ''
            , fc_action( "update,title=Dienste fuer ganzen Liefertag loeschen,class=drop,text=,confirm=Alle Dienste dieses Tages wirklich loeschen?"
                       , "action=diensteTagLoeschen,message={$dienst['id']}" )
          );
        }
    }
    foreach( $dienstnamen as $d ) {
      open_td( 'top' );
        open_table( 'inner layout hfill tight' );
          while( $dienst and ( $dienst['dienst'] == $d ) and ( $dienst['lieferdatum'] == $currentDate ) ) {
            open_tr();
            open_td();
              // echo "{$dienst['id']} , {$dienst['soon']}";
              dienstplan_eintrag_view( $dienst['id'] );
              smallskip();
            $dienst = next( $dienste );
          }
          if( hat_dienst(5) && ! $readonly && ! $dienst['historic'] ) {
            open_tr();
            open_td( 'smallskip center', '',
              fc_action( "update,title=Dienst hinzufuegen,class=button,text= + "
                       , "action=dienstEinfuegen,message={$currentDate}_$d" ) );
          }
        close_table();
    }
  }
close_table();

?>
