<?php
// foodsoft: Order system for Food-Coops
// Copyright (C) 2024  Tilman Vogel <tilman.vogel@web.de>

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.
?>
<h1>Lieferanten&uuml;bersicht...</h1>

<?php

assert( $angemeldet ) or exit();

setWikiHelpTopic( 'foodsoft:lieferanten' );

$editable = ( hat_dienst(4,5) and ! $readonly );

// ggf. Aktionen durchführen (z.B. Lieferant löschen...)
get_http_var('action','w','');
$editable or $action = '';

if( $action == 'delete' ) {
  nur_fuer_dienst(4,5);
  need_http_var('lieferanten_id','u');
  sql_delete_lieferant( $lieferanten_id );
}

open_table( 'menu', "style='margin-bottom:2em;'" );
  if( $editable ) {
      open_td( '', '', fc_link( 'edit_lieferant', "class=bigbutton,text=Neuer Lieferant" ) );
      open_td( '', '', 'einen neuen Lieferanten hinzuf&uuml;gen...' );
  }
  open_tr();
     open_td( '', '', fc_link( 'self', "class=bigbutton,text=Aktualisieren" ) );
     open_td( '', '', 'diese Seite neu laden...' );
  open_tr();
     open_td( '', '', fc_link( 'index', "class=bigbutton,text=Beenden" ) );
     open_td( '', '', 'diesen Bereich verlassen...' );
close_table();

open_table('list');
  open_th('','','Name');
  open_th('','','Telefon');
  open_th('','','Fax');
  open_th('','','Mail');
  open_th('','','Webadresse');
  open_th('','','Kontostand');
  open_th('','','Optionen');

foreach( sql_lieferanten() as $row ) {
  $lieferanten_id=$row['id'];
  $kontostand = lieferantenkontostand( $row['id'] );
  open_tr();
    open_td('','', fc_link( 'edit_lieferant', [ "lieferanten_id" => $lieferanten_id, "class" => "record", "ro" => "1", "text" => $row['name'] ] ) );
    open_td('','', $row['telefon'] );
    open_td('','', $row['fax'] );
    open_td('','', $row['mail'] );
    if( $row['url'] )
      open_td('','',"<a href='{$row['url']}' title='zur Webseite des Lieferanten' target='_new'>{$row['url']}</a>" );
    else
      open_td('','','-');
    open_td('number','', price_view( $kontostand ) );
    open_td('oneline','');
      echo fc_link( 'lieferantenkonto', "lieferanten_id=$lieferanten_id,text=" );
      echo fc_link( 'pfandzettel', "lieferanten_id=$lieferanten_id,text=" );
      if( $editable ) {
        echo fc_link( 'edit_lieferant', "lieferanten_id=$lieferanten_id" );
        if( ( sql_references_lieferant($lieferanten_id) == 0 ) and ( abs($kontostand) < 0.005 ) ) {
          echo fc_action( array( 'class' => 'drop', 'title' => 'Lieferanten l&ouml;schen'
                               , 'confirm' => 'Soll der Lieferant wirklich GEL&Ouml;SCHT werden?' )
                        , "action=delete,lieferanten_id=$lieferanten_id" );
        }
      } else {
        echo fc_link( 'edit_lieferant', "lieferanten_id=$lieferanten_id,ro=1,class=details" );
      }
}
close_table();

?>
