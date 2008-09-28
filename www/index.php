<?php

require_once('code/common.php');

$window = 'menu';
$window_id = 'main';

require_once( 'code/login.php' );
if( ! $angemeldet ) {
  ?> <div class='warn'>Bitte erst <a href='/foodsoft/index.php'>Anmelden...</a></div></body></html> <?
  exit();
}

if( get_http_var( 'download','w' ) ) {  // Spezialfall: Datei-Download (.pdf, ...): ohne HTTP-header!
  $window = $download;
  $self_fields['download'] = $window;
  include( "$download.php" );
  exit();
}

get_http_var( 'window', 'w', 'menu', true );     // eigentlich: name des skriptes
get_http_var( 'window_id', 'w', 'main', true );  // ID des browserfensters
switch( $window_id ) {
  case 'main':   // anzeige im hauptfenster des browsers
    include('head.php');
    include('dienst_info.php');
    switch( $window ) {
      case "bestellen":
        // if ( !( $dienst == 4 ) and ( mysql_num_rows(sql_get_dienst_group($login_gruppen_id ,"Vorgeschlagen"))>0 ) ) {
        //  //darf nur bestellen, wenn Dienste akzeptiert
        //  ?><!-- <h2> Vor dem Bestellen bitte Dienstvorschl&auml;ge akzeptieren </h2> --> <?
        //  include('dienstplan.php');
        // } else {
          include('bestellen.php');
        // }
        break;
      case "lieferschein":
      case "bestellungen_overview":
        //Fast gleich
        include('bestellschein.php');
        break;
      case "wiki":
        reload_immediately( "$foodsoftdir/../wiki/doku.php?do=show" );
        break;
      default:
        if( is_readable( "$window.php" ) ) {
          include( "$window.php" );
        } elseif( is_readable( "windows/$window.php" ) ) {
          include( "windows/$window.php" );
        } else {
          ?> <div class='warn'>Ung&uuml;ltiger Bereich: <? echo($window); ?></div> <?
          include('menu.php');
        }
    }
    ?>
      <table width='100%' class='footer'>
        <tr>
          <td style='padding-left:1em;text-align:left;'>aktueller Server: <kbd><? echo $foodsoftserver; ?></kbd></td>
          <td style='padding-right:1em;text-align:right;'>
          <? echo $mysqljetzt; ?>
            <?  if( $readonly ) { ?>
              <span style='font-weight:bold;color:440000;'> --- !!! Datenbank ist schreibgeschuetzt !!!</span>";
            <? } ?>
          </td>
        </tr>
      </table>
    <?
    break;
  default:   // anzeige in einem unterfenster
    require_once( 'windows/head.php' );
    if( is_readable( "windows/$window.php" ) )
      include( "windows/$window.php" );
    else
      include( "$window.php" );
    break;
}

?>
<form name='update_form' method='post' action='<? echo self_url(); ?>'>
  <? echo self_post(); ?>
  <input type='hidden' name='message' value=''>
</form>
<?
echo $print_on_exit;
exit();

