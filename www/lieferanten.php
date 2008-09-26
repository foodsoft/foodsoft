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
      <td><? echo fc_button( 'edit_lieferant', "text=Neuer Lieferant" ); ?></td>
      <td valign=middle'>einen neuen Lieferanten hinzuf&uuml;gen...</td>
    </tr>
  <? } ?>
  <tr>
     <td><? echo fc_button( 'self', "text=Aktualisieren" ); ?></td>
     <td valign=middle'>diese Seite neu laden...</td>
  </tr><tr>
     <td><? echo fc_button( 'index', "text=Beenden" ); ?></td>
     <td valign=middle'>diesen Bereich verlassen...</td>
  </tr>
</table>

<table class='list'>
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
    echo fc_alink( 'lieferantenkonto', "lieferanten_id=$lieferanten_id,text=" );
    echo fc_alink( 'pfandzettel', "lieferanten_id=$lieferanten_id" );
    if( $editable ) {
      echo fc_alink( 'edit_lieferant', "lieferanten_id=$lieferanten_id" );
      if( ( references_lieferant($lieferanten_id) == 0 ) and ( abs($kontostand) < 0.005 ) ) {
        echo fc_action( array(
          'img' => 'img/b_drop.png', 'title' => 'Lieferanten l&ouml;schen'
        , 'confirm' => 'Soll der Lieferant wirklich GEL&Ouml;SCHT werden?'
        , 'action' => 'delete', 'lieferanten_id' => $lieferanten_id
        ) );
      }
    } else {
      echo fc_alink( 'edit_lieferant', "lieferanten_id=$lieferanten_id,ro=1,img=img/birne_rot.png" );
    }
    ?> </td></tr> <?
  }

?>

</table>

