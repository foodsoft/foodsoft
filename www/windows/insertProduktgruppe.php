<?PHP
assert( $angemeldet ) or exit();

$editable = ( ! $readonly and ( $dienst == 4 ) );

get_http_var( 'action', 'w', '' );
$editable or $action = '';
switch( $action ) {
  case 'insert':
    need_http_var( 'neue_produktgruppe', 'H' );
    sql_insert( 'produktgruppen', array( 'name' => $neue_produktgruppe ) );
    break;
  case 'delete':
    need_http_var( 'produktgruppen_id', 'u' );
    doSql( 'DELETE * FROM produktgruppen WHERE id=$produktgruppen_id' );
    break;
}

open_fieldset( 'small_form', '', '', 'Neue Produktgruppe', 'off' );
  open_form( '', '', '', 'action=insert' );
    open_table('layout');
      form_row_text( 'Name:', 'neue_produktgruppe', 20 );
      submission_button( 'Speichern', true );
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
          if( references_produktgruppe( $row['id'] ) == 0 )
              echo fc_action( 'class=drop,text=,title=Produktgruppe l&ouml;schen?'
                            , "action=delete,produktgruppen_id={$row['id']}" );
    }
  close_table();
close_fieldset();

?>
