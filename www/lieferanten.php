<h1>Lieferanten&uuml;bersicht...</h1>

<?PHP
  assert( $angemeldet ) or exit();
  $problems = '';
   
	 // ggf. Aktionen durchführen (z.B. Lieferant löschen...)
  get_http_var('action','w','');
  if( $action == 'delete' ) {
    fail_if_readonly();
    nur_fuer_dienst(4,5);
    need_http_var('lieferanten_id','u');
    
			 mysql_query( "DELETE FROM lieferanten WHERE id=".mysql_escape_string($lieferanten_id) )
         or $problems = $problems . "<div class='warn'>Konnte Lieferanten nicht l&ouml;schen: "
                                  . mysql_error() ."</div>";
	}

	 
  ?> <table class='menu' style='margin-bottom:2em;'> <?

  if( ! $readonly ) {
    ?>
				   <tr>
		          <td><input type='button' value='Neuen Lieferanten' class='bigbutton' onClick="window.open('index.php?window=editLieferant','lieferant','width=510,height=500,left=200,top=100').focus();"></td>
				      <td valign=middle' class='smalfont'>Einen neuen Lieferanten hinzuf&uuml;gen...</td>
					 </tr>
    <?
  }
  ?>
     <tr>
        <td><input type='button' value='Reload' class='bigbutton' onClick="self.location.href='<? echo self_url(); ?>';"></td>
        <td valign=middle' class='smalfont'>diese Seite aktualisieren...</td>
     </tr><tr>
        <td><input type='button' value='Beenden' class='bigbutton' onClick="self.location.href='index.php'"></td>
        <td valign=middle' class='smalfont'>diesen Bereich verlassen...</td>
     </tr>
  </table>
  
  <?
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
               href=\"javascript:window.open('index.php?window=editLieferant&ro=1&lieferanten_id={$row['id']}','lieferant','width=510,height=500,left=200,top=100').focus()\">
               <img src='img/birne_rot.png' border='0' alt='Details zum Lieferanten' title='Details zum Lieferanten' />
             </a>
    ";
    if( ! $readonly ) {
      if( $dienst == 4 or $dienst == 5 ) {
        echo "
          <a class='png' style='padding:0pt 1ex 0pt 1ex;'
            href=\"javascript:window.open('index.php?window=editLieferant&lieferanten_id={$row['id']}','lieferant','width=510,height=500,left=200,top=100').focus()\">
            <img src='img/b_edit.png' border='0' alt='Lieferanten edieren' title='Lieferanten editieren' />
          </a>
          <a class='png' style='padding:0pt 1ex 0pt 1ex;' href=\"javascript:deleteLieferant({$row['id']});\">
            <img src='img/b_drop.png' border='0' alt='Lieferanten l&ouml;schen' title='Lieferanten l&ouml;schen' />
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

