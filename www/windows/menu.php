<h1>Start ....</h1>  
<?

open_table( 'layout hfill' );
  open_td();  // hauptmenu
    open_table( 'menu' );
      foreach(possible_areas() as $menu_area){
        areas_in_menu($menu_area);
      }
    close_table();
  open_td( 'qquad bottom' );   // schwarzes Brett, Schnellauswahl laufende Bestellungen

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

close_table();
?>
