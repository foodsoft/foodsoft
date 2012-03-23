<?php
//error_reporting(E_ALL); // alle Fehler anzeigen

assert( $angemeldet ) or exit();  // aufruf nur per index.php?window=basar...

//get_http_var( 'orderby', 'w' , 'artikelname', true );
//get_http_var( 'bestell_id', 'u' , 0, true );

$editable = ( hat_dienst(4) and ! $readonly );

get_http_var( 'action','w','' );
$editable or $action = '';
/*
if( $action == 'basarzuteilung' ) {
  need_http_var('fieldcount','u' );
  need_http_var('gruppen_id','U', false );
  if( $gruppen_id != sql_muell_id() ) {
    need( sql_gruppe_aktiv( $gruppen_id ) , "Keine aktive Bestellgruppe ausgewaehlt!" );
  }

  for( $i = 0; $i < $fieldcount; $i++ ) {
    if( ! get_http_var( "produkt$i", 'U' ) )
      continue;
    need_http_var( "bestellung$i", 'U' );
    $b_id = ${"bestellung$i"};
    if( sql_bestellung_status( $b_id ) >= STATUS_ABGERECHNET )
      continue;
    if( get_http_var( "menge$i", "f" ) ) {
      $pr = sql_produkt( array( 'bestell_id' => $b_id, 'produkt_id' => ${"produkt$i"} ) );
      $gruppen_menge = ${"menge$i"} / $pr['kan_verteilmult'];
      if( $gruppen_menge > 0 or ( $gruppen_id == $muell_id ) )
        sql_basar2group( $gruppen_id, ${"produkt$i"}, ${"bestellung$i"}, $gruppen_menge );
    }
  }
}
*/
?> <h1>Katalog-Akronyme</h1> <?php

if ($action == 'update') {
  need_http_var('changes', 'R');
  $decodedChanges = json_decode($changes, true);
  if (is_null($decodedChanges)) {
    error("Cannot decode JSON $changes");
  }
  foreach ($decodedChanges as $change) {
    $values = array(
         'context' => htmlspecialchars( $change['context'], ENT_QUOTES, 'UTF-8' )
       , 'acronym' => htmlspecialchars( $change['acronym'], ENT_QUOTES, 'UTF-8' )
       , 'definition' => htmlspecialchars( $change['definition'], ENT_QUOTES, 'UTF-8' )
       , 'comment' => htmlspecialchars( $change['comment'], ENT_QUOTES, 'UTF-8' )
       , 'url' => htmlspecialchars( $change['url'] , ENT_QUOTES, 'UTF-8' ) ); 
        
    if (preg_match('/^new-(\d+)$/', $change['id'], $matches))  {
      sql_insert('catalogue_acronyms', $values);
    }
    elseif (preg_match('/^delete-(\d+)$/', $change['id'], $matches)) {
      doSql("DELETE FROM `catalogue_acronyms` WHERE id={$matches[1]}"
      , LEVEL_IMPORTANT, "Could not delete catalogue acronym");
    }
    elseif (preg_match('/^(\d+)$/', $change['id'], $matches)) {
      sql_update('catalogue_acronyms', $matches[1], $values);
    }
    else
    {
      error("Could not process change request with id {$change['id']}");
    }
  }
}

catalogue_acronym_view( $editable );

?>
