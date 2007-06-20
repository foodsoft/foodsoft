<h1>Gruppenverwaltung...</h1>

<?PHP
  require_once("code/login.php");
  require_once("code/zuordnen.php");

  // ggf. Aktionen durchführen (z.B. Gruppe löschen...)
  get_http_var('action');
  if( $action == 'delete' ) {
    //nur_fuer_dienst(4,5);
    need_http_var('gruppen_id');

    ( $result = mysql_query( "SELECT * FROM bestellgruppen WHERE id='$gruppen_id'" ) )
      or error(__LINE__,__FILE__,"Bestellgruppe nicht gefunden.",mysql_error());
    ( $row = mysql_fetch_array( $result ) )
      or error(__LINE__,__FILE__,"Bestellgruppe nicht gefunden.",mysql_error());

    $kontostand = kontostand( $row['id'] );
    if( abs($kontostand) > 0.005 ) {
      echo "
        <div class='warn'>Kontostand: $kontostand ist nicht null: Loeschen nicht moeglich!</div>
      ";
    } else {
      mysql_query( "DELETE FROM bestellgruppen WHERE id=".mysql_escape_string($HTTP_GET_VARS['gruppen_id']))
        or error(__LINE__,__FILE__,"Konnte Bestellgruppe nicht löschen.",mysql_error());
    }
  }

  ?>

  <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu können -->
    <form action="index.php" name="reload_form">
      <input type="hidden" name="area" value="gruppen">
      <input type="hidden" name="action" value="normal">
      <input type="hidden" name="gruppen_id" value="">
    </form>
    <table class="menu">
      <tr>
        <td><input type="button" value="Neue Gruppe" class="bigbutton" onClick="window.open('windows/insertGroup.php?gruppen_pwd=<?PHP echo $gruppen_pwd; ?>','insertGroup','width=350,height=320,left=200,top=100').focus()"></td>
        <td valign="middle" class="smalfont">Eine neue Bestellgruppe hinzufügen...</td>
           </tr><tr>
              <td><input type="button" value="Reload" class="bigbutton" onClick="document.forms['reload_form'].submit();"></td>
              <td valign="middle" class="smalfont">diese Seite aktualisieren...</td>
           </tr><tr>
              <td><input type="button" value="Beenden" class="bigbutton" onClick="self.location.href='index.php'"></td>
              <td valign="middle" class="smalfont">diesen Bereich verlassen...</td>
           </tr>
        </table>

      <br><br>

        <table class="liste">
          <tr>
             <th>Gruppenname</th>
             <th>AnsprechpartnerIn</th>
             <th>Mail</th>
             <th>Telefon</th>
             <th>Kontostand</th>
             <th>Mitgliederzahl</th>
             <th>Optionen</th>
          </tr>
  <?PHP

  $result = mysql_query("SELECT * FROM bestellgruppen ORDER BY name")
    or error(__LINE__,__FILE__,"Konnte Bestellgruppen nicht lesen.",mysql_error());
  while ($row = mysql_fetch_array($result)) {
    $kontostand = kontostand($row['id']);
    echo "
      <tr>
        <td>{$row['name']}</td>
        <td>{$row['ansprechpartner']}</td>
        <td>{$row['email']}</td>
        <td>{$row['telefon']}</td>
        <td align='right'>$kontostand</td>
         <td>{$row['mitgliederzahl']}</td>
        <td>
    ";
    if( ( $dienst == 4 ) || ( $dienst == 5 ) ) {
      echo "
        <a class='png' href=\"javascript:window.open('windows/groupTransaktionMenu.php?gruppen_id={$row['id']}','groupTransaktion','width=500,height=300,left=200,top=100').focus()\">
         <img src='img/b_browse.png' border='0' titel='Kontotransaktionen' alt='Kontotransaktionen'/>
        </a>
      ";
    } elseif( $login_gruppen_id == $row['id'] ) {
      echo "
        <a class='png' href='index.php?area=meinkonto'>
         <img src='img/b_browse.png' border='0' titel='Mein Konto' alt='Mein Konto'/>
        </a>
      ";
    }
    if( ( $dienst == 4 ) || ( $dienst == 5 ) || ( $login_gruppen_id == $row['id'] ) ) {
      echo "<a class='png' href=\"javascript:window.open('windows/editGroup.php?gruppen_id={$row['id']}','insertGroup','width=350,height=340,left=200,top=100').focus()\">
        <img src='img/b_edit.png' border='0' alt='Gruppendaten ändern' titel='Gruppendaten ändern'/></a>
      ";
    }
    if( ( $dienst == 5 ) && ( abs($kontostand) < 0.005 ) ) {
      echo "<a class='png' href=\"javascript:deleteGroup({$row['id']});\">
        <img src='img/b_drop.png' border='0' alt='Gruppe löschen' titel='Gruppe löschen'/></a>
      ";
    }
    echo " </td> </tr>";
  }
?>

</table>
</body>
</html>

