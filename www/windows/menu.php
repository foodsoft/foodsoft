<?

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
    // $bulletinboard = $b[0].$b[1].$b[2].$b[3].$b[4].$b[5].$b[6];
    // var_export( $b );
    // var_export( $bulletinboard );
    sql_update( 'leitvariable', array( 'name'=> 'bulletinboard' ), array( 'value' => $bulletinboard ) );
    break;
}

open_table( 'layout hfill' );
  open_td();
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
          ?><textarea id='news' wrap='hard' name='bulletinboard' cols='38' rows='8'><? echo $bulletinboard; ?></textarea><?
          open_div( 'chalk' );
            submission_button();
          close_div();
        close_div();
        open_javascript( "document.getElementById('news').focus();" );
      close_form();
    } else {
      $form_id = open_form( '', 'action=edit' );
        open_div( 'board' );
          // open_div();
            ?><textarea name='news' readonly cols='38' rows='8'><? echo $bulletinboard; ?></textarea><?
            open_div( 'chalk' );
              ?><a href='#' onclick="document.forms.form_<? echo $form_id; ?>.submit();"
                  title='Tafel beschreiben...'><img src='img/chalk_trans.gif'></a><?
            close_div();
          // close_div();
        close_div();
      close_form();
    }
  open_div( '', "style='clear:left;'", ' ' ); // erzwingt neue Zeile

    if( hat_dienst(0) ) {
      open_div( 'bigskip bold', '', 'Euer Gruppenkontostand: ' . fc_link( 'meinkonto', array(
        'text' => price_view( kontostand( $login_gruppen_id ) ) . " Euro"
      , 'class' => 'href', 'gruppen_id' => $login_gruppen_id
      ) ) );
    }
    open_div( 'bigskip' );
      ?> <h4> Laufende Bestellungen: </h4> <?
      auswahl_bestellung();
    close_div();

    open_div( 'bigskip' );
      ?> <h4> Eure nächsten <a href=index.php?window=dienstplan>Dienste</a>: </h4> <?
      $date = strftime("%Y-%m-%d");
      $dienste =  sql_get_dienste($date, $login_gruppen_id);
      while($row = mysql_fetch_array($dienste)){
	       dienst_view2($row);
      }
    close_div();
      /*
    open_div( 'bigskip' );
      ?> <h4> Letzte Dienste: </h4> <?
      $dienste =  sql_get_dienste($date, $login_gruppen_id, FALSE, TRUE);
       while($row = mysql_fetch_array($dienste)){
	       if($row['dienstkontrollblatt_id']!="NULL"){
	          dienst_view3($row);
	       }


       }
    close_div();
       */
close_table();
?>
