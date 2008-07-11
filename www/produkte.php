<h1>Produktdatenbank ....</h1>

<?PHP
  assert( $angemeldet ) or exit();

  get_http_var( 'lieferanten_id', 'u', false, true );
  define( 'OPTION_KATALOGABGLEICH', 1 );
  define( 'OPTION_PREISKONSISTENZTEST', 2 );
  get_http_var( 'optionen', 'u', OPTION_PREISKONSISTENZTEST, true );

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
          <td><? echo fc_button( 'edit_produkt', "lieferanten_id=$lieferanten_id,text=Neues Produkt" ); ?></td>
        </tr>
        <!-- momentan ausser Betrieb (Einheiten nicht korrekt implementiert!)
          <tr>
          <td><input type='button' value='Alle Produkte bearbeiten' class='bigbutton' onClick="document.forms['reload_form'].action.value = 'edit_all'; document.forms['reload_form'].submit();"></td>
        </tr>
        -->
      <?
    }
    ?>
      <tr>
        <td><? echo fc_button( 'self', "text=Seite aktualisieren" ); ?></td>
      </tr><tr>
        <td><? echo fc_button( 'katalog', "text=Katalogsuche" ); ?></td>
      </tr><tr>
        <td><? echo fc_button( 'index' ); ?></td>
      </tr>
      <tr>
        <td>
          <input type='checkbox'
            <? if( $optionen & OPTION_PREISKONSISTENZTEST ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ OPTION_PREISKONSISTENZTEST);
            ?>';"
            title='Soll die Preishistorie aller Eintr√§ge auf Inkonsistenzen gepr√ºft werden?'
          > Preiskonsistenztest
        </td>
      </tr>
      <tr>
        <td>
          <input type='checkbox'
            <? if( $optionen & OPTION_KATALOGABGLEICH ) echo " checked"; ?>
            onclick="window.location.href='<?
              echo self_url('optionen'), "&optionen=", ($optionen ^ OPTION_KATALOGABGLEICH);
            ?>';"
            title='Sollen alle Eintr√§ge mit dem Lieferantenkatalog verglichen werden?'
          > Abgleich mit Lieferantenkatalog
        </td>
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
    if( $row['id'] != $lieferanten_id ) {
      echo "<tr><td><a class='tabelle' href='" . self_url('lieferanten_id') . "&lieferanten_id={$row['id']}'>{$row['name']}</a>";
    } else {
      echo "<tr class='active'><td>{$row['name']}";
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


  /////////////////////////////
  //
  // aktionen verarbeiten:
  //
  /////////////////////////////

  get_http_var('action','w','');

  $edit_all = false;
  //   if( $action == "edit_all" and $editable ) { 
  //     $edit_all = true;
  //   }

  if( $action == 'delete' and $editable ) {
    need_http_var('produkt_id','u');
    sql_delete_produkt( $produkt_id );
  }

  //   if( ($action == "change_all") and $editable ) {
  //     get_http_vars( 'prodIds[]', 'u', array() );
  //     foreach( $prodIds as $pid ) {
  //       need_http_var( "name_$pid", 'H' );
  //       need_http_var( "prodgroup_$pid", 'u' );
  //       need_http_var( "einheit_$pid", 'H' );
  //       need_http_var( "notiz_$pid", 'H' );
  //       need_http_var( "preis_$pid", 'f' );
  //       need_http_var( "bestellnummer_$pid", 'H' );
  //       need_http_var( "gebindegroesse_$pid", 'H' );
  //       sql_update_produkt( $pid, ${"name_$pid"}, ${"prodgroup_$pid"}, ${"einheit_$pid"}, ${"notiz_$pid"} );
  // 
  //       $preis = ${"preis_$pid"};
  // 
  //       //aber erst wird gepr¸ft, ob es aktuelle preise f¸r das produkt gibt
  //       $preis_row = sql_aktueller_produktpreis( $pid, false );
  //       if( $preis_row ) {
  //         if( $preis_row['preis'] == $preis
  //             and $preis_row['bestellnummer'] == ${"bestellnummer_$pid"}
  //             and $preis_row['gebindegroesse'] == ${"gebindegroesse_$pid"}
  //             and $preis_row['einheit'] == ${"einheit_$pid"}
  //           ) {
  //           continue;  // keine aenderungen noetig
  //         }
  //       }
  //       sql_insert_produktpreis( $pid,
  //         $preis, $mysqljetzt, "NULL", ${"bestellnummer_$pid"}, ${"gebindegroesse_$pid"}
  //       );
  //     }
  //   }


  /////////////////////////////
  //
  // Produkttabelle anzeigen:
  //
  /////////////////////////////

  $lieferant_name = lieferant_name($lieferanten_id);
  $produkte = sql_produkte_von_lieferant_ids($lieferanten_id);

  if ($edit_all) {
    ?>
      <form action="<? echo self_url(); ?>" name="editAllForm" method="POST">
      <? echo self_post(); ?>
      <input type="hidden" name="action" value="change_all">
    <?
  } else {
    ?>
      <form action="<? echo fc_url( 'insert_bestellung', '', '', 'form:' ); ?>" method="post"  name="newBestellungForm">
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
              ?>

          </h3></th>
        </tr><tr>
          <th> </th>
          <th title='generische Produktbezeichnung'>Bezeichnung</th>
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
          <th>Bezeichnung</th>
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
    $produkt = sql_produkt_details( $id, 0, $mysqljetzt );
    $references = references_produkt( $id );

    if (!$edit_all) { 
      ?>
        <tr class='groupofrows_top'>
      <? if( $produkt['zeitstart'] ) { ?>
          <td valign="top"><input type="checkbox" name="bestelliste[]" value="<? echo $id; ?>"></td>
      <?  } else { ?>
          <td valign='top'> - </td>
      <?  } ?>
          <td valign="top"><b><? echo $produkt['name']; ?></b></td>
          <td valign="top"><? echo $produkt['produktgruppen_name']; ?></td>
      <? if( $produkt['zeitstart'] ) { ?>
          <td style="vertical-align:top;width:25ex;"><? echo $produkt['notiz']; ?></td>
          <td class='number'><?
            printf(
              "%d * (%s %s)"
            , $produkt['gebindegroesse'], $produkt['kan_verteilmult'], $produkt['kan_verteileinheit']
            );
          ?></td>
          <td class='mult'><?  printf( "%.2lf", $produkt['nettolieferpreis'] ); ?></td>
          <td class='unit'><?  printf( "/ %s", $produkt['preiseinheit'] ); ?></td>
          <td class='mult'><?  printf( "%.2lf", $produkt['endpreis'] ); ?></td>
          <td class='unit'><?
            printf( "/ %s %s"
            , $produkt['kan_verteilmult'], $produkt['kan_verteileinheit']
            );
          ?></td>
      <?  } else { ?>
        <td colspan='6' style='text-align:center'>(kein aktueller Preiseintrag)</td>
      <? } ?>
          <td valign='top' style='white-space:nowrap;'>
          <?
          if( $editable ) {
            echo fc_alink( 'edit_produkt', "produkt_id=$id" );
            echo fc_alink( 'produktpreise', "produkt_id=$id" );
            if( $references == 0 ) {
              echo fc_action( array( 'action' => 'delete', 'img' => 'img/b_drop.png', 'produkt_id' => $id
              , 'confirm' => 'Soll das Produkt wirklich GEL&Ouml;SCHT werden?'
              ) );
            } 
          }
          ?>
          </td>
        </tr>
        <tr class='groupofrows_bottom'>
          <td colspan='1'></td>
          <td colspan='9'>
            <?
              if( $optionen & OPTION_PREISKONSISTENZTEST )
                produktpreise_konsistenztest( $id );
              if( $optionen & OPTION_KATALOGABGLEICH )
                katalogabgleich( $id );
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
        <th colspan="9">
        <input type="submit" value="√Ñnderungen speichern"> &nbsp;| <a href="#" class="tabelle">nach oben</a>
      <?
    } else {
      ?>
        <th colspan="10">
          <? echo fc_button( 'insert_bestellung', 'text=Neue Bestellung,form=newBestellungForm' ); ?>
          <input type='button' class='bigbutton' onclick="javascript:checkAll('newBestellungForm','',true)" value='alle Produkte ausw√§hlen' />
      <?
    }
    ?> </th> </tr> <?
  }
  ?>

    </form>
 </table>

