<?php

setWikiHelpTopic( 'foodsoft:' );

get_http_var( 'action', 'w', '' );
if( $readonly )
  $action = '';
$ro_tag = 'readonly';
switch( $action ) {
  case 'edit':
    $ro_tag = '';
    break;
  case 'save':
    need_http_var( 'bulletinboard', 'H' );
    $b = preg_split( '/\n/m', $bulletinboard . "\n\n\n\n\n\n\n" );
    $bulletinboard = '';
    $nl = '';
    for( $i = 0; $i <= 7; ++$i ) {
      $bulletinboard .= ( $nl . rtrim( preg_replace( '/\r/', '', $b[$i] ) ) );
      $nl = "\n";
    }
    sql_update( 'leitvariable', array( 'name'=> 'bulletinboard' ), array( 'value' => $bulletinboard ) );
    break;
}

open_table( 'layout hfill' );
  open_td( '', "rowspan='2'" );
    bigskip();
    open_table( 'menu' );
      foreach(possible_areas() as $menu_area){
        areas_in_menu($menu_area);
      }
    close_table();
  open_td( 'qquad bottom' );   // schwarzes Brett, Schnellauswahl laufende Bestellungen
    if( $action == 'edit' ) {
      open_form( '', 'action=save' );
        open_div( 'board' );
          ?><textarea id='news' wrap='hard' name='bulletinboard' class='board' cols='38' rows='8'><?php echo $bulletinboard; ?></textarea><?php
          open_div( 'chalk' );
            submission_button();
          close_div();
        close_div();
      close_form();
      open_javascript( "document.getElementById('news').focus();" );
    } else {
      $form_id = open_form( '', 'action=edit' );
        open_div( 'board' );
          ?><textarea class='board' name='news' readonly cols='38' rows='8'><?php echo $bulletinboard; ?></textarea><?php
          open_div( 'chalk' );
            ?><a href='#' onclick="document.forms.form_<?php echo $form_id; ?>.submit();"
                title='Tafel beschreiben...'><img src='img/chalk_trans.gif' alt='Kreide'></a><?php
          close_div();
        close_div();
      close_form();
    }

  /* social column */
  if( $member_showcase_count ) {
    open_td( '', 'rowspan=2');
      bigskip();
      $random_group_members = sql_gruppenmitglieder('true', 'rand()');

      $pick_count = 0;

      open_table( 'menu' );
        open_tr();
          open_td( 'center' );
            echo $member_showcase_title;
      foreach ($random_group_members as $member) {
        $member['avatar_url'] = get_avatar_url($member);
        if (!$member['avatar_url'])
          continue;
        open_tr();
          open_td();
            avatar_view($member);
        if (++$pick_count >= $member_showcase_count)
          break;
      }
      close_table();
  }


  open_tr();
  open_td();

    if( hat_dienst(0) ) {
      open_div( 'bigskip bold', '', 'Euer Gruppenkontostand: ' . fc_link( 'meinkonto', array(
        'text' => price_view( kontostand( $login_gruppen_id ) ) . " Euro"
      , 'class' => 'href', 'gruppen_id' => $login_gruppen_id
      ) ) );
    }

    open_div( 'bigskip' );
      ?> <h4> Laufende Bestellungen: </h4> <?php
      auswahl_bestellung();
    close_div();

    open_div( 'bigskip' );
      dienst_liste( $login_gruppen_id );
    close_div();

    if( false ) {
      open_div( 'bigskip' );
        ?> <h4> Letzte Dienste: </h4> <?php
        foreach( sql_dienste( " ( gruppen_id = $login_gruppen_id ) and ( lieferdatum < $mysqlheute ) " ) as $row ) {
          if($row['dienstkontrollblatt_id']!="NULL"){
            dienst_view3($row);
          }
        }
      close_div();
    }
close_table();
?>
