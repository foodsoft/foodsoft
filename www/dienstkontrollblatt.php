<?php

// dienstkontrollblatt.php
//
	assert( $angemeldet ) or exit();

  get_http_var('action','w','');

  if( ( $action == 'abmelden' ) && ( $dienst >= 0 ) )  {

    if( $dienst > 0 and $dienstkontrollblatt_id > 0 ) {
      $result = dienstkontrollblatt_select( $dienstkontrollblatt_id );
      $row = mysql_fetch_array( $result );
  
      echo "
        <form class='small_form' action='index.php?action=logout' method='post'>
          <fieldset>
            <legend>
              Abmeldung im Dienstkontrollblatt
            </legend>
            <div class='newfield'>
              <label>Dein Name:</label>
              <input type='text' size='20' name='coopie_name' value='{$row['name']}'></input>
              <label style='padding-left:4em;'>Telefon:</label>
              <input type='text' size='20' name='telefon' value='{$row['telefon']}'></input>
            </div>
            <div class='newfield'>
              <label>Notiz fuers Dienstkontrollblatt (zum Beispiel: wo die Ordner liegen...):</label>
              <br>
              <textarea cols='80' rows='4' name='notiz'>{$row['notiz']}</textarea>
            </div>
            <div class='newfield'>
              <input type='submit' name='submit' value='Abmelden...'></input>
            </div>
          </fieldset>
        </form>
      ";
    } else {

      // kein eintrag zum aktualisieren, also gleich ausloggen:
      //
      reload_immediately( 'index.php?action=logout' );
    }

  } else {

    $result = mysql_query( "SELECT id FROM dienstkontrollblatt ORDER BY id DESC LIMIT 5" );
    $row = mysql_fetch_array( $result );
    if( ! $row )
      error( __LINE__, __FILE__, "konnte dienstkontrollblatt nicht lesen" );
    $id_max = $row['id'];
    get_http_var( 'id_to', 'u', $id_max, true );
    $id_from = $id_to - 10;
    if( $id_from < 1 )
      $id_from = 1;
    // get_http_var('id_from', 'u', $id_to - 10, true );
  
    $result = dienstkontrollblatt_select( $id_from, $id_to );

    ?>
      <h1>Dienstkontrollblatt</h1>
      <table class='liste'>
        <tr>
          <th> Nr. </th>
          <th> Datum </th>
          <th> Zeit </th>
          <th> Dienst </th>
          <th> Gruppe </th>
          <th> Name </th>
          <th> Telefon </th>
          <th> Notiz </th>
        </tr>
    <?

    if( $id_from > 1 ) {
      $n = ( $id_from > 10 ) ? $id_from : 10;
      ?>
        <tr>
          <td colspan='7'>
            <? echo fc_button( 'self', "id_to=$n,text= &lt; &lt; &lt;  Bl&auml;ttern &lt; &lt; &lt; " ); ?>
          </td>
        </tr>
      <?
    }
    while( $row = mysql_fetch_array( $result ) ) {
      ?>
        <tr>
          <td>
            <?  echo fc_alink( 'self', array( 'title' => 'Zentrieren', 'id_to' => $row['id'] + 5, 'text' => $row['id'] ) ); ?>
          </td>
          <td><? echo $row['datum']; ?></td>
          <td><? echo $row['zeit']; ?></td>
          <td><? echo $row['dienst']; ?></td>
          <td><? echo $row['gruppen_name']; ?></td>
          <td><? echo $row['name']; ?></td>
          <td><? echo $row['telefon']; ?></td>
          <td><? echo $row['notiz']; ?></td>
        </tr>
      <?
    }
    if( $id_to < $id_max ) {
      $n = $id_to + 10;
      if( $n > $id_max )
        $n = $id_max;
      ?>
        <tr>
          <td colspan='7'>
            <? echo fc_button( 'self', "id_to=$n,text= &gt; &gt; &gt;  Bl&auml;ttern &gt; &gt; &gt; " ); ?>
          </td>
        </tr>
      <?
    }
    echo "</table>";
  }

?>

