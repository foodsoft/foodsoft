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
      need_http_var(' produktgruppen_id', 'u' );
      doSql( 'DELETE * FROM produktgruppen WHERE id=$produktgruppen_id' );
      break;
  }

$produktgruppen = sql_produktgruppen();

?>

<fieldset class='small_form'>
<legend>Produktgruppen</legend>

<table class='list'>
  <tr>
    <th>Produktgruppen</th>
    <th>Aktionen</th>
  </tr>

<? while( $row = mysql_fetch_array( $produktgruppen ) ) { ?>
  <tr>
    <td><? echo $row['name']; ?></td>
    <td><? if( references_produktgruppe( $row['id'] ) == 0 )
              echo fc_action( array( 'action' => 'delete', 'produktgruppen_id' => $row['id'], 'img' => 'img/b_drop.png', 'text' => ''
                                     , 'title' => 'Produktgruppe l&ouml;schen?' ) ); ?></td>
  </tr>
<? } ?>

</table>

<h4>Neue Produktgruppe:</h4>

<form action="<? echo self_url(); ?>" method='post'>
  <? echo self_post(); ?>
  <input type='hidden' name='action' value='insert'>
  <table class="menu">
    <tr>
      <td><label>Name:</label></td>
      <td><input type="text" size="20" name="neue_produktgruppe"></td>
    </tr>
    <tr>
      <td colspan='2' style='text-align:right;'><input type="submit" value="Einf&uuml;gen"></td>
    </tr>
  </table>
</form>

</fieldset>

