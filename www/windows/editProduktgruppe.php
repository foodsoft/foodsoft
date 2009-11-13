<?PHP
assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:produktgruppen' );
setWindowSubtitle( 'Produktgruppen' );

$editable = ( hat_dienst(4) and ! $readonly );

get_http_var( 'action', 'w', '' );
$editable or $action = '';
switch( $action ) {
  case 'insert':
    need_http_var( 'neue_produktgruppe', 'H' );
    sql_insert( 'produktgruppen', array( 'name' => $neue_produktgruppe ) );
    break;
  case 'delete':
    need_http_var( 'produktgruppen_id', 'U' );
    need( references_produktgruppe( $produktgruppen_id ) == 0, "Loeschen nicht moeglich: Produktgruppe wird benutzt!" );
    doSql( "DELETE FROM produktgruppen WHERE id=$produktgruppen_id" );
    break;
}

open_fieldset( 'small_form', '', 'Neue Produktgruppe', 'off' );
  open_form( '', 'action=insert' );
    open_table('layout');
      form_row_text( 'Name:', 'neue_produktgruppe', 20 );
      qquad(); submission_button( 'Speichern', true );
    close_table();
  close_form();
close_fieldset();
medskip();

open_fieldset( 'small_form', '', 'Produktgruppen' );
  open_table('list');
      open_th('','', 'Produktgruppen' );
      open_th('','', 'Aktionen' );

    foreach( sql_produktgruppen() as $row ) {
      open_tr();
        open_td( '', '', $row['name'] );
        open_td();
          if( $editable && ( references_produktgruppe( $row['id'] ) == 0 ) )
              echo fc_action( 'class=drop,text=,title=Produktgruppe l&ouml;schen?'
                            , "action=delete,produktgruppen_id={$row['id']}" );
    }
  close_table();
close_fieldset();

?>
