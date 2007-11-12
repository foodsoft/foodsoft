<h1>Produktdatenbank ....</h1>

<?PHP
  assert( $angemeldet ) or exit();

  get_http_var( 'lieferanten_id', 'u', false, true );

  $editable = ( ! $readonly and ( $dienst == 4 ) );



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

  // ab hier muss ein Lieferant ausgewaehlt sein, sonst Ende:
  //
  if( ! $lieferanten_id )
    return;

  $lieferant_name = lieferant_name($lieferanten_id);
  $produkte = sql_produkte_von_lieferant_ids($lieferanten_id);


  /////////////////////////////
  //
  // aktionen verarbeiten:
  //
  /////////////////////////////

  get_http_var('action','w','');

  $edit_all = false;
  if( $action == "edit_all" and $editable ) { 
    $edit_all = true;
  }

  if( ($action == "change_all") and $editable ) {
    get_http_vars( 'prodIds[]', 'u', array() );
    foreach( $prodIds as $pid ) {
      need_http_var( "name_$pid", 'H' );
      need_http_var( "prodgroup_$pid", 'u' );
      need_http_var( "einheit_$pid", 'H' );
      need_http_var( "notiz_$pid", 'H' );
      need_http_var( "preis_$pid", 'f' );
      need_http_var( "bestellnummer_$pid", 'H' );
      need_http_var( "gebindegroesse_$pid", 'H' );
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
  
  ?>
    <!-- Hier eine reload-Form die dazu dient, dieses Fenster von einem anderen aus reloaden zu kˆnnen -->
    <form action='<? echo self_url(); ?>' name='reload_form' method='post'>
      <? echo self_post(); ?>
    </form>
  <?

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

  if (!$edit_all) {   // f¸r die normalansicht
    ?>
      <table class='numbers'>
        <tr>
          <th colspan="10"><h3>Produkt√ºbersicht von
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
          <th> </th>
          <th title='generische Produktbezeichnung'>Name</th>
          <th>Produktgruppe</th>
          <th title='aktuelle Details zum Produkt'>Notiz</th>
          <th>Gebindegroesse</th>
          <!-- <th>Kategorien</th> -->
          <th colspan='2' title='Lieferanten-Preis (ohne Pfand, ohne MWSt)'>L-Nettopreis</th>
          <th colspan='2' title='Verbraucher-Preis mit Pfand und MWSt'>V-Endpreis</th>
          <th>Optionen</th>
        </tr>
    <?
  } else {  //f¸r die alle ¸berarbeiten ansicht
    ?>
      <table class='numbers'>
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

  while( $row = mysql_fetch_array($produkte) ) {
    $id = $row['id'];
    $produkt = sql_produkt_details( $id );

    if (!$edit_all) { 
      ?>
        <tr>
          <td valign="top"><input type="checkbox" name="bestelliste[]" value="<? echo $id; ?>"></td>
          <td valign="top"><b><? echo $produkt['name']; ?></b></td>
          <td valign="top"><? echo $produkt['produktgruppen_name']; ?></td>
          <td valign="top"><? echo $produkt['notiz']; ?></td>
      <? if( $produkt['zeitstart'] ) { ?>
          <td class='number'><?
            printf(
              "%d * (%s %s)"
            , $produkt['gebindegroesse'], $produkt['kan_verteilmult'], $produkt['kan_verteileinheit']
            );
          ?></td>
          <td class='mult'><?  printf( "%.2lf", $produkt['nettopreis'] ); ?></td>
          <td class='unit'><?  printf( "/ %s", $produkt['preiseinheit'] ); ?></td>
          <td class='mult'><?  printf( "%.2lf", $produkt['endpreis'] ); ?></td>
          <td class='unit'><?
            printf( "/ %s %s"
            , $produkt['kan_verteilmult'], $produkt['kan_verteileinheit']
            );
          ?></td>
      <?  } else { ?>
        <td colspan='5' style='text-align:center'>(kein aktueller Preiseintrag)</td>
      <? } ?>
          <td valign='top'>
          <? if( $editable ) { ?>
            <a class='png' href="javascript:neuesfenster('index.php?window=terraabgleich&produktid=<? echo $row['id'] ?>','produktdetails');"><img src='img/euro.png' border='0' alt='Preise' titel='Preise'></a>
            <a class='png' href="javascript:f=window.open('index.php?window=editProdukt&produkt_id=<? echo $row['id'] ?>','editProdukt','width=400,height=450,left=200,top=100'); f.focus();"><img src='img/b_edit.png' border='0' alt='Produktdaten √§ndern'  titel='Produktdaten √§ndern'/></a>
            <!-- Produkte nicht loeschen, da dynamische Abrechnung Daten benˆtigt:
              <a class='png' href=\"javascript:deleteProdukt({$row['id']})\"><img src='img/b_drop.png' border='0' alt='Gruppe lˆschen' titel='Gruppe lˆschen'/></a>
            -->
          <? } ?>
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

<?
// "produktkategorien" im moment unbenutzt:
//          $kat_result = mysql_query("SELECT produktkategorien.name FROM produktkategorien, kategoriezuordnung WHERE kategoriezuordnung.produkt_id = ".mysql_escape_string($row['id'])." AND kategoriezuordnung.kategorien_id = produktkategorien.id;") or error(__LINE__,__FILE__,"Konnte Kategorien nich aus DB laden..",mysql_error());
//          $kategorien_str = "";
//          while ($kat_row = mysql_fetch_array($kat_result)) {
//            $kategorien_str .= $kat_row['name'];
//          }
//          echo $kategorien_str ? $kategorien_str : "-";
?>

