<h1>Lieferanten&uuml;bersicht...</h1>

<?PHP

assert( $angemeldet ) or exit();
$editable = ( ! $readonly and ( $dienst == 4 ) );
 
// ggf. Aktionen durchführen (z.B. Lieferant löschen...)
get_http_var('action','w','');
$editable or $action = '';

if( $action == 'delete' ) {
  nur_fuer_dienst(4,5);
  need_http_var('lieferanten_id','u');
  delete_lieferant( $lieferanten_id );
}
$result = sql_lieferanten();

?>
<table class='menu' style='margin-bottom:2em;'>
  <? if( $editable ) { ?>
    <tr>
      <td><input type='button' value='Neuer Lieferant' class='bigbutton'
           onClick="<? echo fc_url( 'edit_lieferant' ); ?>">
         </td>
      <td valign=middle'>Einen neuen Lieferanten hinzuf&uuml;gen...</td>
    </tr>
  <? } ?>
  <tr>
     <td><input type='button' value='Reload' class='bigbutton' onClick="self.location.href='<? echo self_url(); ?>';"></td>
     <td valign=middle'>diese Seite aktualisieren...</td>
  </tr><tr>
     <td><input type='button' value='Beenden' class='bigbutton' onClick="self.location.href='index.php'"></td>
     <td valign=middle'>diesen Bereich verlassen...</td>
  </tr>
</table>

<table class='liste'>
  <tr>
    <th>Name</th>
    <th>Telefon</th>
    <th>Fax</th>
    <th>Mail</th>
    <th>Webadresse</th>
    <th>Kontostand</th>
    <th>Optionen</th>
  </tr>

<?
while ($row = mysql_fetch_array($result)) {
  $lieferanten_id=$row['id'];
  $kontostand = lieferantenkontostand( $row['id'] );
  ?>
    <tr>
      <td><b><? echo $row['name']; ?></b></td>
      <td><? echo $row['telefon']; ?></td>
      <td><? echo $row['fax']; ?></td>
      <td><? echo $row['mail']; ?></td>
      <td>
        <?
        if( $row['url'] )
          echo "<a href='{$row['url']}' title='zur Webseite des Lieferanten' target='_new'>{$row['url']}</a>";
        else
          echo "-";
        ?>
      </td>
      <td class='number'><? printf( "%.2lf", $kontostand ); ?></td>
      <td style='white-space:nowrap;'>
    <?
    echo fc_alink( 'lieferantenkonto', array( 'lieferanten_id' => $lieferanten_id ) );
    echo fc_alink( 'pfandzettel', "lieferanten_id=$lieferanten_id" );
    if( $editable ) {
      echo fc_alink( 'edit_lieferant', array( 'lieferanten_id' => $lieferanten_id ) );
      if( abs($kontostand) < 0.005 ) {
        echo "
          <a class='png' style='padding:0pt 1ex 0pt 1ex;' href=\"javascript:deleteLieferant({$row['id']});\">
            <img src='img/b_drop.png' border='0' alt='Lieferanten l&ouml;schen' title='Lieferanten l&ouml;schen' />
          </a>
        ";
      }
    } else {
      echo fc_alink( 'edit_lieferant', array( 'lieferanten_id' => $lieferanten_id, 'ro' => '1', 'img' => 'img/birne_rot.png' ) );
    }
    ?> </td></tr> <?
  }

?>

</table>
</body>
</html>

