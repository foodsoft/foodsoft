
<?PHP
  
assert( $angemeldet ) or exit();

need_http_var('gruppen_id','u');

	  $edit_names = FALSE;
	  $edit_dienst_einteilung=FALSE;


echo "<h1>Gruppenmitglieder f&uuml;r Gruppe $gruppen_id </h1> ";
// ggf. Aktionen durchführen (z.B. Gruppe löschen...)
get_http_var('action','w');
if( $action == 'delete' ) {
}

  // Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können
  ?>
    <form action='<? echo self_url(); ?>' name='reload_form' method='post'>
      <? echo self_post(); ?>
      <input type='hidden' name='gruppen_id' value=''>
      <input type='hidden' name='action' value=''>
    </form>
    <table class='menu'>
  <?
global $login_gruppen_id ;

//FIXME: remove trim when http_get_var correctly returns integer
if( trim($login_gruppen_id) == $gruppen_id){
	  $edit_names = TRUE;
}
  if( $hat_dienst_V and ! $readonly ) {
	  $edit_names = TRUE;
	  $edit_dienst_einteilung=TRUE;
    ?>
      <tr>
        <td>
          <input type='button' value='Neue Gruppe' class='bigbutton'
          onClick="window.open('index.php?window=insertGroup','insertGroup','width=390,height=420,left=200,top=100').focus();"></td>
        <td valign='middle' class='smallfont'>Eine neue Bestellgruppe hinzufÃ¼gen...</td>
      </tr>
    <?
  }


  ?>
    </table>

    <br><br>

	<?  membertable_view(sql_gruppen_members($gruppen_id), $edit_names , $edit_dienst_einteilung); ?>

</table>

