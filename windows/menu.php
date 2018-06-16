<?php

setWikiHelpTopic( 'foodsoft:' );

function process_bullentinboard_content($bulltin, $up_or_down) {
    global $bulletinboard;
    error_log($bulletinboard);

    $b = preg_split( '/\n/m', $bulltin . "\n\n\n\n\n\n\n" );
    $bulltin = '';
    $nl = '';
    for( $i = 0; $i <= 7; ++$i ) {
      $bulltin .= ( $nl . rtrim( preg_replace( '/\r/', '', $b[$i] ) ) );
      $nl = "\n";
    }

    $bulltemp = json_decode($bulletinboard);
    if(gettype($bulltemp) != "array")
        $bulltemp = array();

    if($up_or_down == "up")
        $bulltemp[0] = $bulltin;
    else
        $bulltemp[1] = $bulltin;

    $bulletinboard = json_encode($bulltemp);
    sql_update( 'leitvariable', array( 'name'=> 'bulletinboard' ), array( 'value' => $bulletinboard ) );
}

get_http_var( 'action', 'w', '' );
if( $readonly )
  $action = '';
$ro_tag = 'readonly';
switch( $action ) {
  case 'edit':
    $ro_tag = '';
    break;
  case 'save_up':
    need_http_var( 'bulletinboard_up', 'H' );
    process_bullentinboard_content($bulletinboard_up, "up");
    break;
  case 'save_down':
    need_http_var( 'bulletinboard_down', 'H' );
    process_bullentinboard_content($bulletinboard_down, "down");
    break;
}

$decoded_bulletinboard = json_decode($bulletinboard);
if(gettype($decoded_bulletinboard) == "array") {
    $bulletinboard_up = $decoded_bulletinboard[0];
    $bulletinboard_down = $decoded_bulletinboard[1];
} else
{
    $bulletinboard_up = $bulletinboard;
    $bulletinboard_down = "";
}

open_table( 'layout hfill' );
  open_td( '', "rowspan='3'" );
    bigskip();
    open_table( 'menu' );
      foreach(possible_areas() as $menu_area){
        areas_in_menu($menu_area);
      }
    close_table();
  open_td( 'qquad bottom' );   // schwarzes Brett
    if( $action == 'edit' ) {
      open_form( '', 'action=save_up' );
        open_div( 'board' );
          ?><textarea id='news' wrap='hard' name='bulletinboard_up' class='board' cols='38' rows='8'><?php echo $bulletinboard_up; ?></textarea><?php
          open_div( 'chalk' );
            submission_button();
          close_div();
        close_div();
      close_form();
      open_javascript( "document.getElementById('news').focus();" );
    } else {
      $form_id = open_form( '', 'action=edit' );
        open_div( 'board' );
          ?><textarea class='board' name='news' readonly cols='38' rows='8'><?php echo $bulletinboard_up; ?></textarea><?php
          open_div( 'chalk' );
            ?><a href='#' onclick="document.forms.form_<?php echo $form_id; ?>.submit();"
                title='Tafel beschreiben...'><img src='img/chalk_trans.gif' alt='Kreide'></a><?php
          close_div();
        close_div();
      close_form();
    }


  /* social column */
  if( $member_showcase_count ) {
    open_td( '', 'rowspan=3');
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

  open_td( 'qquad bottom' );   // schwarzes Brett 2
    if( $action == 'edit' ) {
      open_form( '', 'action=save_down' );
        open_div( 'board' );
          ?><textarea id='news' wrap='hard' name='bulletinboard_down' class='board' cols='38' rows='8'><?php echo $bulletinboard_down; ?></textarea><?php
          open_div( 'chalk' );
            submission_button();
          close_div();
        close_div();
      close_form();
      open_javascript( "document.getElementById('news').focus();" );
    } else {
      $form_id = open_form( '', 'action=edit' );
        open_div( 'board' );
          ?><textarea class='board' name='news' readonly cols='38' rows='8'><?php echo $bulletinboard_down; ?></textarea><?php
          open_div( 'chalk' );
            ?><a href='#' onclick="document.forms.form_<?php echo $form_id; ?>.submit();"
                title='Tafel beschreiben...'><img src='img/chalk_trans.gif' alt='Kreide'></a><?php
          close_div();
        close_div();
      close_form();
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
