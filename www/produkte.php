<h1>Produktdatenbank ....</h1>

<?PHP
  assert( $angemeldet ) or exit();

/* wie das skript funktioniert:

   1. variablen einlesen, passwort pr¸fen
   2. ¸ber die action variable wird gepr¸ft, welcher button gedr¸ckt wurde
      2.1. action = delete -> produkt wird gelˆscht
      2.2. action = edit_all ->  die alle bearbeiten seite wird angezeigt
      2.3. action = change_all -> die preise werden ggf. aktualisiert .. 

*/
  get_http_var( 'lieferanten_id', 'u', false, true );

  $editable = ( ! $readonly and ( $dienst == 4 ) );
  
  get_http_var('action','w','');

  $edit_all = false;
  if( $action == "edit_all" and $editable ) { 
    $edit_all = true;
  }


  /////////////////////////////
  //
  // tabelle fuer hauptmenue und auswahl lieferanten:
  //
  /////////////////////////////
  ?> <table width='100%' class='layout'><tr> <?

  if( $lieferanten_id ) {
    ?> <td> <table class='menu'> <?
    if( $editable ) {
      ?>
        <tr>
          <td><input type='button' value='Neues Produkt eintragen' class='bigbutton' onClick="window.open('index.php?window=insertProdukt','insertProdukt','width=450,height=500,left=100,top=100').focus()"></td>
        </tr><tr>
          <td><input type='button' value='Alle Produkte bearbeiten' class='bigbutton' onClick="document.forms['reload_form'].action.value = 'edit_all'; document.forms['reload_form'].submit();"></td>
        </tr>
      <?
    }
    ?>
      <tr>
        <td><input type='button' value='Seite aktualisieren' class='bigbutton' onClick="document.forms['reload_form'].submit();"></td>
      </tr><tr>
        <td><input type='button' value='Beenden' class='bigbutton' onClick="self.location.href='index.php';"></td>
      </tr>
      </table>
    <?
  }

  ?>
    <td style='text-align:left;padding:1ex 1em 2em 3em;'>
    <table style="width:600px;" class="liste">
      <tr>
        <th>Lieferanten</th>
        <th>Produkte</th>
      </tr>
  <?
  $lieferanten = sql_lieferanten();
  while( $row = mysql_fetch_array($lieferanten) ) {
    ?> <tr><td style='font-weight:bold;'> <?
    if( $row['id'] != $lieferanten_id ) {
      echo "<a class='tabelle' href='" . self_url('lieferanten_id') . "&lieferanten_id={$row['id']}'>{$row['name']}</a>";
    } else {
      echo $row['name'];
    }
    ?>  </td><td> <? echo $row['anzahl_produkte']; ?> </td>
      </tr>
    <?
  }
  ?>
        </table>
      </td>
    </tr>
    </table>
  <?

  if( ! $lieferanten_id )
    return;

  $lieferant_name = lieferant_name($lieferanten_id);
  $produkte = getProdukteVonLieferant($lieferanten_id);


  /////////////////////////////
  //
  // aktionen verarbeiten:
  //
  /////////////////////////////

  if( ($action == "change_all") and $editable ) {
    get_http_vars( 'prodIds[]', 'u', array() );
    foreach( $prodIds as $pid ) {
      need_http_var( "name_$pid", 'H' );
      need_http_var( "prodgroup_$pid", 'u' );
      need_http_var( "einheit_$pid", 'H' );
      need_http_var( "notiz_$pid", 'H' );
      need_http_var( "preis_$pid", 'f' );
      need_http_var( "bestellnummer_$pid", 'f' );
      need_http_var( "gebindegroesse_$pid", 'f' );
      sql_update_produkt( $pid, ${"name_$pid"}, ${"prodgroup_$pid"}, ${"einheit_$pid"}, ${"notiz_$pid"} );

      $preis = ${"preis_$pid"};

      //aber erst wird gepr¸ft, ob es aktuelle preise f¸r das produkt gibt
      $preis_row = sql_aktueller_produktpreis( $pid, false );
      if( $preis_row ) {
        if( $preis_row['preis'] == $preis
            and $preis_row['bestellnummer'] == ${"bestellnummer_$pid"}
            and $preis_row['gebindegroesse'] == ${"gebindegroesse_$pid"}
            and $preis_row['einheit'] == ${"einheit_$pid"}
          ) {
          continue;  // keine aenderungen noetig
        }
      }
      sql_insert_produktpreis( $pid,
        $preis, $mysqljetzt, "NULL", ${"bestellnummer_$pid"}, ${"gebindegroesse_$pid"}
      );
    }
  }


  /////////////////////////////
  //
  // Produkttabelle anzeigen:
  //
  /////////////////////////////
  
  echo "
    <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu kˆnnen -->
    <form action='index.php' name='reload_form'>
       <input type='hidden' name='area' value='produkte'>
         <input type='hidden' name='lieferanten_id' value='$lieferanten_id'>
         <input type='hidden' name='action' value='normal'>
         <input type='hidden' name='produkt_id'>
    </form>
  ";

  if ($edit_all) {
    ?>
       <form action="<? echo self_url(); ?>" name="editAllForm" method="POST">
       <? echo self_post(); ?>
       <input type="hidden" name="action" value="change_all">
    <?
  } else {
    ?>
      <form action="index.php?window=insertBestellung" method="post" target="insertBestellung" name="newBestellungForm">
      <input type="hidden" name="lieferanten_id" value="<? echo $lieferanten_id; ?>">
    <?
  }


  if (!$edit_all) {  					// f¸r die normalansicht
    ?>
      <table class="liste">
        <tr>
          <th colspan="8"><h3>Produkt√ºbersicht von
              <?php
                echo $lieferant_name;
                if ( $lieferant_name == "Terra" ) {
                 ?> <a class="button" href="javascript:neuesfenster('index.php?window=artikelsuche','artikelsuche');">Katalogsuche</a> <?
                }
                 // if( $hat_dienst_IV ) {
                   ?> <a class="button" href="javascript:neuesfenster('index.php?window=terraabgleich&lieferanten_id=<? echo $lieferanten_id; ?>','terraabgleich;');">Datenbankabgleich</a> <?
                 // }
              ?>

          </h3></th>
        </tr><tr>
          <th></th>
          <th>Name</th>
          <th>Produktgruppe</th>
          <th>Einheit</th>
          <th>Notiz</th>
          <th>Kategorien</th>
          <th>Preis</th>
          <th>Optionen</th>
        </tr>
    <?
  } else {  //f¸r die alle ¸berarbeiten ansicht
    ?>
      <table>
        <tr>
          <th colspan="7"><h3>Produkt√ºbersicht von <?php echo $lieferant_name?></h3></th>
        </tr><tr>
          <th>Name</th>
          <th>Produktgruppe</th>
          <th>Einheit</th>
          <th>Notiz</th>
          <th>Gebindegr</th>
          <th>Preis</th>
          <th>Bestellnr</th>
        </tr>	 
    <?
  }

  // jetzt werden die produkte aus der datenbank gelesen ....


  if ($edit_all) { // hier im falle der "alle ¸berarbeiten" ansicht
	
      $result = sql_produktgruppen();
            while ($row = mysql_fetch_array($result)) 
                $prodgroup_id2name[$row['id']] = $row['name'];

          $result = sql_lieferanten(); 
            while ($row = mysql_fetch_array($result)) 
                $lieferanten_id2name[$row['id']] = $row['name'];

  } else {         // hier f¸r die standardansicht
       
//                             $sql = "SELECT produkte.*, produkte.id as prodId, lieferanten.name as lname, produktgruppen.name as pname
//                                     FROM produkte,lieferanten,produktgruppen
//                                     WHERE lieferanten.id ='$lieferanten_id'
//                                     AND produkte.produktgruppen_id = produktgruppen.id
//                                     AND produkte.lieferanten_id = lieferanten.id
//                                     ORDER BY produkte.lieferanten_id, produkte.produktgruppen_id, produkte.name";
//                         $result = mysql_query($sql) or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());
//
//                          } else {
//
//                    $result = mysql_query("SELECT produkte.*, produkte.id as prodId, lieferanten.name as lname, produktgruppen.name as pname
//                                                                  FROM produkte,lieferanten,produktgruppen
//                                                                                     WHERE produkte.lieferanten_id = lieferanten.id
//                                                                                       AND produkte.produktgruppen_id = produktgruppen.id
//                                                                                       ORDER BY produkte.lieferanten_id, produkte.produktgruppen_id, produkte.name") or error(__LINE__,__FILE__,"Konnte Produkte nich aus DB laden..",mysql_error());
  }

  while( $row = mysql_fetch_array($produkte) ) {
    if (!$edit_all) { 
      ?>
        <tr>
          <td valign="top"><input type="checkbox" name="bestelliste[]" value="<? echo $row['id']; ?>"></td>
          <td valign="top"><b><? echo $row['name']; ?></b></td>
          <td valign="top"><? echo $row['produktgruppen_name']; ?></td>
          <td valign="top"><? echo $row['einheit']; ?></td>
          <td valign="top" align="middle"><?PHP echo $row['notiz']; ?></td>
          <td valign="top" align="middle">
      <?
      $kat_result = mysql_query("SELECT produktkategorien.name FROM produktkategorien, kategoriezuordnung WHERE kategoriezuordnung.produkt_id = ".mysql_escape_string($row['id'])." AND kategoriezuordnung.kategorien_id = produktkategorien.id;") or error(__LINE__,__FILE__,"Konnte Kategorien nich aus DB laden..",mysql_error());
      $kategorien_str = "";
      while ($kat_row = mysql_fetch_array($kat_result)) {
        $kategorien_str .= $kat_row['name'];
      }
      echo $kategorien_str ? $kategorien_str : "-";
      ?>
        </td>
        <td valign='top' align='middle'>
      <?
      $preis_row = sql_aktueller_produktpreis($row['id']);
      echo $preis_row['preis'];
      ?>
        </td>
        <td valign='top'>
      <?
      if( $editable ) {
        ?>
          <a class='png' href="javascript:neuesfenster('index.php?window=terraabgleich&produktid=<? echo $row['id'] ?>','produktdetails');"><img src='img/euro.png' border='0' alt='Preise' titel='Preise'></a>
          <a class='png' href="javascript:f=window.open('index.php?window=editProdukt&produkt_id=<? echo $row['id'] ?>','editProdukt','width=400,height=450,left=200,top=100'); f.focus();"><img src='img/b_edit.png' border='0' alt='Produktdaten √§ndern'  titel='Produktdaten √§ndern'/></a>
          <!-- Produkte nicht loeschen, da dynamische Abrechnung Daten benˆtigt
            <a class='png' href=\"javascript:deleteProdukt({$row['id']})\"><img src='img/b_drop.png' border='0' alt='Gruppe lˆschen' titel='Gruppe lˆschen'/></a>
          -->
        <?
      }
      ?>
        </td>
        </tr>
      <?
    } else { //  alle bearbeiten ansicht ...
      ?>
        <tr>
          <td valign="top"><input type="text" name="name_<? echo $row['id']; ?>" value="<? echo $row['name']; ?>"></td>
          <td valign="top">
            <input type='hidden' name='prodIds[]' value='<? echo $row['id']; ?>'>
            <select name='prodgroup_<? echo $row['id']; ?>'>
            <?
              foreach( $prodgroup_id2name as $key => $value ) {
                if ($key == $row['produktgruppen_id']) $sel_str = "selected"; else $sel_str = "";
                echo "<option value='".$key."' ".$sel_str.">".$value."</option>\n";
              }
            ?>
            </select>
          </td>
          <td valign="top">
            <select name='lieferant_<? echo $row['id']; ?>'>
            <?
              foreach( $lieferanten_id2name as $key => $value ) {
                if ($key == $row['lieferanten_id']) $sel_str = "selected"; else $sel_str = "";
                echo "<option value='".$key."' ".$sel_str.">".$value."</option>\n";
              }
            ?>
            </select>
          </td>
          <td valign="top"><input type="text" size="10" name="einheit_<? echo $row['id']; ?>" value="<? echo $row['einheit']; ?>"></td>
          <td valign="top" align="middle"><input type="text" name="notiz_<? echo $row['id']; ?>" value="<? echo $row['notiz']; ?>"></td>
          <?
            $preis_row = sql_aktueller_produktpreis($row['id']);
            if( $preis_row ) {
              echo "<td valign='top' align='middle'><input type='text' size='10' name='gebindegroesse_".$row['id']."' value='".$preis_row['gebindegroesse']."'></td><td valign='top' align='middle'><input type='text' size='10' name='preis_".$row['id']."' value='".$preis_row['preis']."'></td><td valign='top' align='middle'><input type='text' size='10' name='bestellnummer_".$row['id']."' value='".$preis_row['bestellnummer']."'></td>";
            } else {
              echo "<td>(kein aktueller Preiseintrag)</td>";
            }
          ?>
        </tr>
      <?
    }
  }

  if( $editable ) {
    ?> <tr> <?
    if ($edit_all) {
      ?>
        <th colspan="8">
        <input type="submit" value="√Ñnderungen speichern"> &nbsp;| <a href="#" class="tabelle">nach oben</a>
      <?
    } else {
      ?>
        <th colspan="9">
          <input type="button" value="neue Bestellung" onClick="window.open('','insertBestellung','width=400,height=450,left=200,top=100').focus() ; document.forms['newBestellungForm'].submit();">
          &nbsp;| <a href="javascript:checkAll('newBestellungForm','',true)" class="tabelle">alle Produkte ausw√§hlen</a>
          &nbsp;| <a href="#" class="tabelle">nach oben</a>
      <?
    }
    ?> </th> </tr> <?
  }
  ?>

    </form>
 </table>

