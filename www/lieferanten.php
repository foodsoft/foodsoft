<h1>Lieferanten&uuml;bersicht...</h1>

<?PHP
  require_once("code/login.php");
  require_once("code/zuordnen.php");
  $problems = '';
   
	 // ggf. Aktionen durchführen (z.B. Lieferant löschen...)
  get_http_var('action');
  if( $action == 'delete' ) {
    fail_if_readonly();
    nur_fuer_dienst(4,5);
    need_http_var('lieferanten_id');
    
			 mysql_query( "DELETE FROM lieferanten WHERE id=".mysql_escape_string($lieferanten_id) )
         or $problems = $problems . "<div class='warn'>Konnte Lieferanten nicht l&ouml;schen: "
                                  . mysql_error() ."</div>";
	}

	 
  echo "	
	     <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können -->
			 <form action='index.php' name='reload_form' method='post'>
			    <input type='hidden' name='area' value='lieferanten'>
					<input type='hidden' name='action' value='normal'>
					<input type='hidden' name='lieferanten_id'>
			 </form>
	
				<table class=menu'>
  ";
  if( ! $readonly ) {
    echo "
				   <tr>
		          <td><input type='button' value='Neuen Lieferanten' class='bigbutton' onClick=\"window.open('windows/insertLieferant.php','lieferant','width=510,height=500,left=200,top=100').focus()\"></td>
				      <td valign=middle' class='smalfont'>Einen neuen Lieferanten hinzuf&uuml;gen...</td>
					 </tr>
    ";
  }
  echo "
           <tr>
		          <td><input type='button' value='Reload' class='bigbutton' onClick=\"document.forms['reload_form'].submit();\"></td>
				      <td valign=middle' class='smalfont'>diese Seite aktualisieren...</td>
					 </tr><tr>
		          <td><input type='button' value='Beenden' class='bigbutton' onClick=\"self.location.href='index.php'\"></td>
				      <td valign=middle' class='smalfont'>diesen Bereich verlassen...</td>
					 </tr>
				</table>
				
				<br><br>
  ";
	$result = mysql_query("SELECT * FROM lieferanten ORDER BY name")
    or $problems = $problems . "<div class='warn'>Konnte LieferantInnen nich aus DB laden: "
                  . mysql_error() . '</div>';
  echo "
        $problems
				<table class='liste'>
	        <tr>
						 <th>Name</th>
						 <th>Telefon</th
						 <th>Fax</th
						 <th>Mail</th
						 <th>Webadresse</th
						 <th>Optionen</th
					</tr>					 
	";
	while ($row = mysql_fetch_array($result)) {
    echo "
	        <tr>
						 <td><b>{$row['name']}</b></td>
						 <td>{$row['telefon']}</td>
						 <td>{$row['fax']}</td>
						 <td>{$row['mail']}</td>
						 <td>
    ";
    if( $row['url'] )
      echo "<a href='{$row['url']}' target='_new'>{$row['url']}</a>";
    else
      echo "-";
    echo "   </td>
						 <td>
						 <a class='png' style='padding:0pt 1ex 0pt 1ex;'
               href=\"javascript:window.open('windows/detailsLieferant.php?lieferanten_id={$row['id']}','lieferant','width=510,height=500,left=200,top=100').focus()\">
               <img src='img/birne_rot.png' border='0' alt='Details zum Lieferanten' titel='Details zum Lieferanten' />
             </a>
    ";
    if( ! $readonly ) {
      if( $dienst == 4 or $dienst == 5 ) {
        echo "
          <a class='png' style='padding:0pt 1ex 0pt 1ex;'
            href=\"javascript:window.open('windows/editLieferant.php?lieferanten_id={$row['id']}','lieferant','width=510,height=500,left=200,top=100').focus()\">
            <img src='img/b_edit.png' border='0' alt='Lieferanten editieren' titel='Lieferanten editieren' />
          </a>
          <a class='png' style='padding:0pt 1ex 0pt 1ex;' href=\"javascript:deleteLieferant({$row['id']});\">
            <img src='img/b_drop.png' border='0' alt='Lieferanten l&ouml;schen' titel='Lieferanten l&ouml;schen' />
          </a>
        ";
      }
    }
    echo "</td></tr>";
  }

?>

</table>
</body>
</html>

