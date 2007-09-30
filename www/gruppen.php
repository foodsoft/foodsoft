<h1>Gruppenverwaltung...</h1>

<?PHP
  
assert( $angemeldet ) or exit();

// ggf. Aktionen durchf�hren (z.B. Gruppe l�schen...)
get_http_var('action','w');
if( $action == 'delete' ) {
  fail_if_readonly();
  nur_fuer_dienst(5);
  need_http_var('gruppen_id','u');

  $row = sql_gruppendaten( $gruppen_id );

  $kontostand = kontostand( $row['id'] );
  if( abs($kontostand) > 0.005 ) {
    ?>
      <div class='warn'>Kontostand (<? echo $kontostand; ?> EUR) ist nicht null: L&ouml;schen nicht m&ouml;glich!</div>
    <?
  } elseif( $row['mitgliederzahl'] != 0 ) {
    ?>
      <div class='warn'>Mitgliederzahl ist nicht null: L&ouml;schen nicht m&ouml;glich (Sockelbetrag!)</div>
      <div class='warn'>(bitte erst auf null setzen, um Sockelbetrag zu verbuchen!)</div>
    <?
  } else {
    if( ! mysql_query(
      "UPDATE bestellgruppen SET aktiv=0 WHERE id='$gruppen_id'"
    ) ) echo "<div class='warn'>Konnte Bestellgruppe nicht l&ouml;schen: " . mysql_error() . "</div>";
  }
}

  // Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu k�nnen
  ?>
    <form action='<? echo self_url(); ?>' name='reload_form' method='post'>
      <? echo self_post(); ?>
      <input type='hidden' name='gruppen_id' value=''>
      <input type='hidden' name='action' value=''>
    </form>
    <table class='menu'>
  <?

  if( $hat_dienst_V and ! $readonly ) {
    ?>
      <tr>
        <td>
          <input type='button' value='Neue Gruppe' class='bigbutton'
          onClick="window.open('index.php?window=insertGroup','insertGroup','width=390,height=420,left=200,top=100').focus();"></td>
        <td valign='middle' class='smallfont'>Eine neue Bestellgruppe hinzufügen...</td>
      </tr>
    <?
  }

  ?>
    </table>

    <br><br>

    <table class='liste'>
      <tr>
         <th>Nr</th>
         <th>Gruppenname</th>
         <th>AnsprechpartnerIn</th>
         <th>Mail</th>
         <th>Telefon</th>
         <th>Kontostand</th>
         <th>Mitgliederzahl</th>
         <th>Diensteinteilung</th>
         <th>Optionen</th>
      </tr>
  <?

  $result = sql_gruppen();
  while ($row = mysql_fetch_array($result)) {
      if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $row['id'] ) ) {
    $kontostand = sprintf( '%10.2lf', kontostand($row['id']) );
      }
    $nr = $row['id'] % 1000;
    echo "
      <tr>
        <td>$nr</td>
        <td>{$row['name']}</td>
        <td>{$row['ansprechpartner']}</td>
        <td>{$row['email']}</td>
        <td>{$row['telefon']}</td>
	";
      if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $row['id'] ) ) {
        echo "<td align='right'>$kontostand</td>";
	} else {
		
        echo "<td></td>";
	}
    echo"
        <td>{$row['mitgliederzahl']}</td>
        <td>{$row['diensteinteilung']}</td>
        <td>
    ";
    if( ! $readonly ) {
      if( ( $dienst == 4 ) || ( $dienst == 5 ) ) {
        echo "
          <a class='png' style='padding:0pt 1ex 0pt 1ex;'
            href=\"javascript:neuesfenster('index.php?window=showGroupTransaktions&gruppen_id={$row['id']}','kontoblatt');\">
           <img src='img/b_browse.png' border='0' title='Kontotransaktionen' alt='Kontotransaktionen'/>
          </a>
        ";
      } elseif( $login_gruppen_id == $row['id'] ) {
        ?>
          <a class='png' style='padding:0pt 1ex 0pt 1ex;'  href='index.php?area=meinkonto'>
           <img src='img/b_browse.png' border='0' title='Mein Konto' alt='Mein Konto'/>
          </a>
        <?
      }
      if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $row['id'] ) ) {
        ?>
          <a class='png' style='padding:0pt 1ex 0pt 1ex;'
          href="javascript:window.open('index.php?window=editGroup&gruppen_id=<? echo $row['id']; ?>','insertGroup','width=390,height=420,left=200,top=100').focus();">
          <img src='img/b_edit.png' border='0' alt='Gruppendaten ändern' title='Gruppendaten ändern'/></a>
        <?
      }
      // loeschen nur wenn
      // - kontostand 0
      // - mitgliederzahl 0 (wegen rueckbuchung sockelbetrag!)
      if( ( $dienst == 5 ) && ( abs($kontostand) < 0.005 ) && ( $row['mitgliederzahl'] == 0 ) ) {
        ?>
          <a class='png' href="javascript:if(confirm('Soll die Gruppe wirklich GELÖSCHT werden?')){
            document.forms['reload_form'].action.value='delete';
            document.forms['reload_form'].gruppen_id.value='<? echo $row['id']; ?>';
            document.forms['reload_form'].submit();}">
          <img src='img/b_drop.png' border='0' alt='Gruppe löschen' title='Gruppe löschen'/></a>
        <?
      }
    }
    ?> </td> </tr> <?
  }
?>

</table>

