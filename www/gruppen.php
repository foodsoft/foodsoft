<h1>Gruppenverwaltung...</h1>

<?PHP
  
  require_once("code/login.php");
  require_once("code/zuordnen.php");

  // ggf. Aktionen durchführen (z.B. Gruppe löschen...)
  get_http_var('action');
  if( $action == 'delete' ) {
    fail_if_readonly();
    nur_fuer_dienst(4,5);
    need_http_var('gruppen_id');

    ( $result = mysql_query( "SELECT * FROM bestellgruppen WHERE id='$gruppen_id'" ) )
      or error(__LINE__,__FILE__,"Bestellgruppe nicht gefunden.",mysql_error());
    ( $row = mysql_fetch_array( $result ) )
      or error(__LINE__,__FILE__,"Bestellgruppe nicht gefunden.",mysql_error());

    $kontostand = kontostand( $row['id'] );
    if( abs($kontostand) > 0.005 ) {
      echo "
        <div class='warn'>Kontostand: $kontostand ist nicht null: L&ouml;schen nicht m&ouml;glich!</div>
      ";
    } elseif( $row['mitgliederzahl'] != 0 ) {
      echo "
        <div class='warn'>Mitgliederzahl ist nicht null: L&ouml;schen nicht m&ouml;glich (Sockelbetrag!)</div>
        <div class='warn'>(bitte erst auf null setzen, um Sockelbetrag zu verbuchen!)</div>
      ";
    } else {
      if( ! mysql_query(
        "UPDATE bestellgruppen SET aktiv=0 WHERE id=".mysql_escape_string($HTTP_GET_VARS['gruppen_id'])
      ) ) echo "<div class='warn'>Konnte Bestellgruppe nicht l&ouml;schen: " . mysql_error() . "</div>";
    }
  }

  echo "
    <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können -->
    <form action='index.php' name='reload_form' method='post'>
      <input type='hidden' name='area' value='gruppen'>
      <input type='hidden' name='action' value='normal'>
      <input type='hidden' name='gruppen_id' value=''>
    </form>
    <table class='menu'>
  "; 

  if( $hat_dienst_IV || $hat_dienst_V and ! $readonly ) {
    echo "
      <tr>
        <td>
          <input type='button' value='Neue Gruppe' class='bigbutton' onClick=\"window.open('windows/insertGroup.php','insertGroup','width=390,height=360,left=200,top=100').focus()\"></td>
        <td valign='middle' class='smallfont'>Eine neue Bestellgruppe hinzufügen...</td>
      </tr>
    ";
  }

  echo "
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
  ";

  $result = mysql_query("SELECT * FROM bestellgruppen WHERE aktiv=1 ORDER BY (id%1000)")
    or error(__LINE__,__FILE__,"Konnte Bestellgruppen nicht lesen.",mysql_error());
  while ($row = mysql_fetch_array($result)) {
    $kontostand = sprintf( '%10.2lf', kontostand($row['id']) );
    $nr = $row['id'] % 1000;
    echo "
      <tr>
        <td>$nr</td>
        <td>{$row['name']}</td>
        <td>{$row['ansprechpartner']}</td>
        <td>{$row['email']}</td>
        <td>{$row['telefon']}</td>
        <td align='right'>$kontostand</td>
        <td>{$row['mitgliederzahl']}</td>
        <td>{$row['diensteinteilung']}</td>
        <td>
    ";
    if( ! $readonly ) {
      if( ( $dienst == 4 ) || ( $dienst == 5 ) ) {
        echo "
          <a class='png' style='padding:0pt 1ex 0pt 1ex;'
            href=\"javascript:window.open('windows/groupTransaktionMenu.php?gruppen_id={$row['id']}','groupTransaktion','width=500,height=300,left=200,top=100').focus()\">
           <img src='img/b_browse.png' border='0' titel='Kontotransaktionen' alt='Kontotransaktionen'/>
          </a>
        ";
      } elseif( $login_gruppen_id == $row['id'] ) {
        echo "
          <a class='png' style='padding:0pt 1ex 0pt 1ex;'  href='index.php?area=meinkonto'>
           <img src='img/b_browse.png' border='0' titel='Mein Konto' alt='Mein Konto'/>
          </a>
        ";
      }
      if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $row['id'] ) ) {
        echo "<a class='png' style='padding:0pt 1ex 0pt 1ex;'  href=\"javascript:window.open('windows/editGroup.php?gruppen_id={$row['id']}','insertGroup','width=390,height=420,left=200,top=100').focus()\">
          <img src='img/b_edit.png' border='0' alt='Gruppendaten ändern' titel='Gruppendaten ändern'/></a>
        ";
      }
      // loeschen nur wenn
      // - kontostand 0
      // - mitgliederzahl 0 (wegen rueckbuchung sockelbetrag!)
      if( ( $dienst == 5 ) && ( abs($kontostand) < 0.005 ) && ( $row['mitgliederzahl'] == 0 ) ) {
        echo "<a class='png' href=\"javascript:deleteGroup({$row['id']});\">
          <img src='img/b_drop.png' border='0' alt='Gruppe löschen' titel='Gruppe löschen'/></a>
        ";
      }
    }
    echo " </td> </tr>";
  }
?>

</table>
</body>
</html>

