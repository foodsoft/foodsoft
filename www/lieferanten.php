<h1>Lieferanten&uuml;bersicht...</h1>

<?PHP

assert( $angemeldet ) or exit();
$problems = '';
$editable = ( ! $readonly and ( $dienst == 4 ) );
 
	 // ggf. Aktionen durchführen (z.B. Lieferant löschen...)
get_http_var('action','w','');
if( $action == 'delete' ) {
  fail_if_readonly();
  nur_fuer_dienst(4,5);
  need_http_var('lieferanten_id','u');

  if( abs( lieferantenkontostand( $lieferanten_id )) > 0.005 ) {
    $problems .= "<div class='warn'>Lieferantenkonto nicht ausgeglichen, loeschen nicht moeglich!</div>";
  }

  if( ! $problems ) {
    doSql(
      "DELETE FROM lieferanten WHERE id=$lieferanten_id"
    , LEVEL_IMPORTANT, "Loeschen des Lieferanten fehlgeschlagen"
    );
  }
}

	 
  ?> <table class='menu' style='margin-bottom:2em;'> <?

  if( ! $readonly ) {
    ?>
				   <tr>
		          <td><input type='button' value='Neuen Lieferanten' class='bigbutton' onClick="window.open('index.php?window=editLieferant','lieferant','width=510,height=500,left=200,top=100').focus();"></td>
				      <td valign=middle'>Einen neuen Lieferanten hinzuf&uuml;gen...</td>
					 </tr>
    <?
  }
  ?>
     <tr>
        <td><input type='button' value='Reload' class='bigbutton' onClick="self.location.href='<? echo self_url(); ?>';"></td>
        <td valign=middle'>diese Seite aktualisieren...</td>
     </tr><tr>
        <td><input type='button' value='Beenden' class='bigbutton' onClick="self.location.href='index.php'"></td>
        <td valign=middle'>diesen Bereich verlassen...</td>
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
						 <th>Kontostand</th
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
    $kontostand = lieferantenkontostand( $row['id'] );
    ?>
      </td>
      <td class='number'>
      <? printf( "%.2lf", $kontostand ); ?>
      </td>
      <td style='white-space:nowrap;'>
    <?
    echo "
      <a class='png' style='padding:0pt 1ex 0pt 1ex;'
        href=\"javascript:neuesfenster('index.php?window=lieferantenkonto&lieferanten_id={$row['id']}','lieferantenkonto');\">
       <img src='img/chart.png' border='0' title='Finanzielles' alt='Finanzielles'/></a>
      <a class='png' style='padding:0pt 1ex 0pt 1ex;'
        href=\"javascript:neuesfenster('index.php?window=pfandverpackungen&lieferanten_id={$row['id']}','pfandzettel');\">
       <img src='img/fant.gif' border='0' title='Fantkram' alt='Fantkram'/></a>
    ";
    if( ( ! $readonly ) and ( $dienst == 4 or $dienst == 5 ) ) {
      echo "
        <a class='png' style='padding:0pt 1ex 0pt 1ex;'
          href=\"javascript:window.open('index.php?window=editLieferant&lieferanten_id={$row['id']}','lieferant','width=510,height=500,left=200,top=100').focus()\">
          <img src='img/b_edit.png' border='0' alt='Lieferanten edieren' title='Lieferanten editieren' />
        </a>
      ";
      if( abs($kontostand) < 0.005 ) {
        echo "
          <a class='png' style='padding:0pt 1ex 0pt 1ex;' href=\"javascript:deleteLieferant({$row['id']});\">
            <img src='img/b_drop.png' border='0' alt='Lieferanten l&ouml;schen' title='Lieferanten l&ouml;schen' />
          </a>
        ";
      }
    } else {
      echo "
        <a class='png' style='padding:0pt 1ex 0pt 1ex;'
          href=\"javascript:window.open('index.php?window=editLieferant&ro=1&lieferanten_id={$row['id']}','lieferant','width=510,height=500,left=200,top=100').focus()\">
          <img src='img/birne_rot.png' border='0' alt='Details zum Lieferanten' title='Details zum Lieferanten' />
        </a>
      ";
    }
    ?> </td></tr> <?
  }

?>

</table>
</body>
</html>

