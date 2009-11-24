<?php

// dienstkontrollblatt.php
//
assert( $angemeldet ) or exit();

get_http_var('action','w','');

if( ( $action == 'abmelden' ) && ( $login_dienst >= 0 ) )  {
  $row = false;
  if( $login_dienst > 0 and $dienstkontrollblatt_id > 0 )
    $row = current( sql_dienstkontrollblatt( $dienstkontrollblatt_id ) );
  if( $row ) {
    open_form( '', 'login=logout' );
      open_fieldset( 'small_form', '', 'Abmeldung im Dienstkontrollblatt' );
        open_table('layout');
            open_td('label','','Dein Name:');
            open_td('kbd');
              echo string_view( $coopie_name, 20, 'coopie_name' );
              open_span('qquad', '', 'Telefon: ');
              open_span('kbd', '', string_view( $telefon, '20', 'telefon' ) );
          open_tr();
            open_td('label', "colspan='2'", 'Notiz fuers Dienstkontrollblatt:' );
              open_div('kbd');
                echo "<textarea cols='80' rows='3' name='notiz'>$notiz</textarea>";
              close_div();
          open_tr();
            open_td('right', "colspan='2'" );
              submission_button('Abmelden...');
        close_table();
      close_fieldset();
    close_form();
  } else {
    // kein eintrag zum aktualisieren, also gleich ausloggen:
    //
    reload_immediately( 'index.php?login=logout' );
  }
  return;
}

$result = mysql_query( "SELECT id FROM dienstkontrollblatt ORDER BY id DESC LIMIT 5" );
$row = mysql_fetch_array( $result );
if( ! $row )
  error( "konnte dienstkontrollblatt nicht lesen" );
$id_max = $row['id'];
get_http_var( 'id_to', 'u', $id_max, true );
$id_from = $id_to - 10;
if( $id_from < 1 )
  $id_from = 1;

$result = sql_dienstkontrollblatt( $id_from, $id_to );

echo "<h1>Dienstkontrollblatt</h1>";
  open_table('list');
      open_th( '', '', 'Nr.' );
      open_th( '', '', 'Datum' );
      open_th( '', '', 'Zeit' );
      open_th( '', '', 'Dienst' );
      open_th( '', '', 'Gruppe' );
      open_th( '', '', 'Name' );
      open_th( '', '', 'Telefon' );
      open_th( '', '', 'Notiz' );

if( $id_from > 1 ) {
  $n = ( $id_from > 10 ) ? $id_from : 10;
  open_tr();
    open_th('', "colspan='8'", fc_link( '', "class=button,id_to=$n,text= &lt; &lt; &lt;  Bl&auml;ttern &lt; &lt; &lt; " ) );
}
foreach( $result as $row ) {
  open_tr();
    open_td('','', fc_link( 'self', array( 'title' => 'Zentrieren', 'id_to' => $row['id'] + 5, 'text' => $row['id'] ) ) );
    open_td('','', $row['datum'] );
    open_td('','', $row['zeit'] );
    open_td('','', $row['dienst'] );
    open_td('','', $row['gruppen_name'] );
    open_td('','', $row['name'] );
    open_td('','', $row['telefon'] );
    open_td('','', $row['notiz'] );
}
if( $id_to < $id_max ) {
  $n = $id_to + 10;
  if( $n > $id_max )
    $n = $id_max;
  open_tr();
    open_th('',"colspan='8'", fc_link( '', "class=button,id_to=$n,text= &gt; &gt; &gt;  Bl&auml;ttern &gt; &gt; &gt; " ) );
}
close_table();

?>
