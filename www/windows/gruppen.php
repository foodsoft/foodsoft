<h1>Gruppenverwaltung...</h1>
<?PHP

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:gruppen' );

$problems="";
$msg="";

get_http_var( 'optionen', 'u', 0, true );
$show_member_details= $optionen & GRUPPEN_OPT_DETAIL;

if( hat_dienst(4,5) ) {
  open_table('menu');
      open_th('', '', 'Optionen' );
    open_tr();
      open_td();
        option_checkbox( 'optionen', GRUPPEN_OPT_DETAIL, 'Details f&uuml;r Gruppenmitglieder anzeigen' );
    open_tr();
      open_td();
        option_checkbox( 'optionen', GRUPPEN_OPT_INAKTIV, 'inaktive Gruppen zeigen'
            , 'Auch inaktive/gelÃ¶schte Gruppen anzeigen?' );
    open_tr();
      open_td();
        option_checkbox( 'optionen', GRUPPEN_OPT_UNGEBUCHT, 'nur ungebuchte Einzahlungen'
            , 'Nur Gruppen mit ungebuchten Einzahlungen anzeigen?' );
    open_tr();
      open_td();
        option_radio( 'optionen', 0, GRUPPEN_OPT_SCHULDEN | GRUPPEN_OPT_GUTHABEN, 'alle' );
        quad(); option_radio( 'optionen', GRUPPEN_OPT_SCHULDEN, GRUPPEN_OPT_GUTHABEN, 'Gruppen mit Schulden' );
        quad(); option_radio( 'optionen', GRUPPEN_OPT_GUTHABEN, GRUPPEN_OPT_SCHULDEN, 'Gruppen mit Guthaben' );
  close_table();
  bigskip();
}

if( ! $readonly and hat_dienst(5) ) {
  open_fieldset( 'small_form', '', 'Neue Gruppe anlegen', 'off' );
    open_form( '', 'action=insert' );
      open_table();
        open_tr(); open_td( 'label', '', 'Nr:' ); open_td( 'kbd', '', string_view( '', 4, 'newNumber' ) );
        open_tr(); open_td( 'label', '', 'Name:' ); open_td( 'kbd' );
          echo string_view( '', 20, 'newName' );
          submission_button();
      close_table();
    close_form();
  close_fieldset();
}

// ggf. Aktionen durchführen (z.B. Gruppe löschen...)
get_http_var('action','w','');
$readonly and $action = '';
switch( $action ) {
  case 'delete':
    nur_fuer_dienst(5);
    need_http_var('gruppen_id','u');

    $row = sql_gruppendaten( $gruppen_id );
    $kontostand = kontostand( $gruppen_id );
    if( abs($kontostand) > 0.005 ) {
      div_msg( 'warn', "Kontostand ($kontostand EUR) ist nicht null: L&ouml;schen nicht m&ouml;glich!" );
    } elseif( $row['mitgliederzahl'] != 0 ) {
      div_msg( 'warn', "Mitgliederzahl ist nicht null: L&ouml;schen nicht m&ouml;glich!" );
      div_msg( 'warn', "(bitte erst Mitglieder l&ouml;schen, um Sockelbetrag zu verbuchen)" );
    } else {
      sql_update( 'bestellgruppen', $gruppen_id, array( 'aktiv' => 0 ) );
    }
    break;
  case 'insert':
    nur_fuer_dienst(5);
    need_http_var('newNumber', 'u');
    need_http_var('newName','H');
    // vorläufiges Passwort für die Bestellgruppe erzeugen...
    $pwd = strval(rand(1010,9999));

    $gruppen_id = sql_insert_group( $newNumber, $newName, $pwd );
    if( $gruppen_id ) {
      echo fc_openwindow( 'gruppenmitglieder', "gruppen_id=$gruppen_id" );
      $msg .= "
        <div class='ok'>Gruppe erfolgreich angelegt</div>
        <div class='ok'>Vorl&auml;ufiges Passwort: <b>$pwd</b> (bitte notieren!)</div>
      ";
    } else {
      $msg .= "<div class='warn'>Eintrag fehlgeschlagen!</div>";
    }
    break;
  case 'cancel_payment':
    need_http_var( 'transaction_id', 'U' );
    // echo "id: $gruppen_id, trans: $transaction_id <br>";
    $trans = sql_get_transaction( -$transaction_id );
    if( $trans['gruppen_id'] != $login_gruppen_id )
      nur_fuer_dienst(4,5);
    need( $trans['konterbuchung_id'] == 0, 'bereits verbucht, kann nicht mehr gel&ouml;scht werden!' );
    doSql( "DELETE FROM gruppen_transaktion WHERE id=$transaction_id" );
    break;
}

echo "$problems $msg";

medskip();

open_table('list');
  open_th( '','','Nr' );
  open_th( '','','Gruppenname' );
  open_th( '','','Kontostand' );
  open_th( '','','Mitgliederzahl' );
  open_th( '','','Aktionen' );

  $summe = 0;
  $mitglieder_summe = 0;
  $gruppen = ( $optionen & GRUPPEN_OPT_INAKTIV ? sql_bestellgruppen() : sql_aktive_bestellgruppen() );
  foreach( $gruppen as $row ) {
    $id = $row['id'];
    if( in_array( $id, $specialgroups ) )
      continue;
    if( hat_dienst(4,5) || ( $login_gruppen_id == $id ) ) {
      $kontostand = sprintf( '%10.2lf', kontostand($row['id']) );
      if( $optionen & GRUPPEN_OPT_SCHULDEN )
        if( $kontostand >= 0 )
          continue;
      if( $optionen & GRUPPEN_OPT_GUTHABEN )
        if( $kontostand <= 0 )
          continue;
      $offene_einzahlungen = sql_ungebuchte_einzahlungen( $id );
      if( $optionen & GRUPPEN_OPT_UNGEBUCHT )
        if( count($offene_einzahlungen) < 1 )
          continue;
      $summe += $kontostand;
    }
    $nr = $row['gruppennummer'];
    $mitglieder_summe += $row['mitgliederzahl'];

    open_tr();
      open_td( '', '', $nr );
      open_td( '', '', $row['name'] );
      open_td( 'number' );
      if( hat_dienst(4,5) || ( $login_gruppen_id == $id ) )
        echo price_view( $kontostand );
      open_td( 'number', '', $row['mitgliederzahl'] );
      open_td();

      if( $row['aktiv'] > 0 ) {
        echo fc_link( 'gruppenmitglieder', "gruppen_id=$id,title=Mitglieder,text=" );
        if( hat_dienst(4,5) ) {
          echo fc_link( 'gruppenkonto', "gruppen_id=$id,title=Kontoblatt,text=" );
        } elseif( $login_gruppen_id == $id ) {
          echo fc_link( 'gruppenkonto', "gruppen_id=$id,title=Kontoblatt,meinkonto=1,text=" );
        }
        if( hat_dienst(4,5) || ( $login_gruppen_id == $id ) ) {
          if( $offene_einzahlungen ) {
            open_table('list');
                open_th( '', "colspan='3'", 'ungebuchte Einzahlungen:' );
              foreach( $offene_einzahlungen as $trans ) {
                open_tr();
                  open_td( 'left', '', $trans['eingabedatum_trad'] );
                  open_td( 'number', '', price_view( $trans['summe'] ) );
                  open_td( '', '', fc_action( array( 'class' => 'drop', 'title' => 'L&ouml;schen?', 'confirm' => 'Gutschrift wirklich lÃ¶schen?' )
                                            , array( 'action' => 'cancel_payment', 'transaction_id' => $trans['id'] ) ) );
              }
            close_table();
          }
        }
        // loeschen nur wenn
        // - kontostand 0
        // - mitgliederzahl 0 (wegen rueckbuchung sockelbetrag!)
        if(    hat_dienst(5)
            && ( abs($kontostand) < 0.005 )
            && ( $row['mitgliederzahl'] == 0 )
            && ( ! in_array( $id, $specialgroups ) )
        ) {
          echo fc_action( array( 'class' => 'drop', 'title' => 'Gruppe l&ouml;schen?', 'text' => ''
                               , 'confirm' => 'Soll die Gruppe wirklich GEL&Ouml;SCHT werden?' )
                        , array( 'action' => 'delete', 'gruppen_id' => $row['id'] ) );
        }
      } else {
        ?>(inaktiv)<?
      }

    if($show_member_details) {
      open_tr();
        open_td();
        open_td('', "colspan='4'" );
          membertable_view( $id, FALSE,FALSE, FALSE);
    }
  }

  if( hat_dienst(4,5) ) {
    open_tr('summe');
      open_td('right', "colspan='2'", 'Summe:' );
      open_td('number', '', price_view( $summe ) );
      open_td('number', '', $mitglieder_summe );
      open_td();
  }

close_table();

?>
