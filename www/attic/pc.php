<?php
  //
  // pc.php
  //
  // debugger fuer tabelle gruppen_transaktion
  //

error_reporting(E_ALL);
exit(1); // momentan ausser betrieb

assert($angemeldet) or exit();

// need( $dienstkontrollblatt_id, "Dienstkontrollblatt-ID nicht gesetzt" );

$bestell_id = 7;
$result = doSql( "
  SELECT * FROM bestellvorschlaege WHERE gesamtbestellung_id = $bestell_id
" );


while( $vorschlag = mysql_fetch_array( $result ) ) {
  echo "<div style='padding-top:2em;'>
    vorschlag: produkt_id: {$vorschlag['produkt_id']}:
    preis_id: {$vorschlag['produktpreise_id']}:
    liefermenge: {$vorschlag['liefermenge']}
    </div>
  ";
  $produkt = sql_select_single_row( "SELECT * FROM produkte WHERE id=${vorschlag['produkt_id']}", true );
  $preis = sql_select_single_row( "SELECT * FROM produktpreise WHERE id=${vorschlag['produktpreise_id']}", true );
  preisdatenSetzen( & $preis );  
  echo "<div style='padding-top:1ex;'>
    Name: {$produkt['name']}:
    preis: {$preis['preis']},
    liefermenge: {$vorschlag['liefermenge']} {$preis['verteileinheit']}
    </div>
  ";
}

return;




# echo 1;
# $result = doSql(
#   "SELECT bestell_id, gruppen_id FROM gruppenpfand"
# );
# 
# while( $row = mysql_fetch_array( $result ) ) {
#   echo $row['gruppen_id'], $row['bestell_id'];
#   sql_create_gruppenbestellung( $row['gruppen_id'], $row['bestell_id'] );
# }
#   
# return;
# 
# $result = doSql(
#   "SELECT *
#   , sum( summe ) as summe
#   , (
#       SELECT max(id)
#       FROM gesamtbestellungen
#       WHERE gesamtbestellungen.lieferanten_id = 1
#         AND gesamtbestellungen.lieferung < gruppen_transaktion.kontobewegungs_datum
#     ) as bestell_id
#   FROM gruppen_transaktion
#   WHERE type=1
#         AND gruppen_id > 0
#         AND gruppen_id != 13
#   GROUP BY bestell_id, gruppen_id
#   ORDER BY bestell_id, gruppen_id
#   "
# );
# 
# 
# 
# $result = doSql(
#   "SELECT
#     *
#   , sum( summe ) as summe
#   , (
#       SELECT max(id)
#       FROM gesamtbestellungen
#       WHERE gesamtbestellungen.lieferanten_id = 1
#         AND gesamtbestellungen.lieferung < gruppen_transaktion.kontobewegungs_datum
#     ) as bestell_id
#   FROM gruppen_transaktion
#   WHERE type=1
#         AND gruppen_id > 0
#         AND gruppen_id != 13
#   GROUP BY bestell_id, gruppen_id
#   ORDER BY bestell_id, gruppen_id
#   "
# );
# 
# ?>
#   <table class='numbers'>
# 
# <?
# while( $row = mysql_fetch_array( $result ) ) {
#   echo "
#     <tr>
#       <td>
#         {$row['gruppen_id']}
#       </td>
#       <td>
#         {$row['summe']}
#       </td>
#       <td>
#         {$row['bestell_id']}
#       </td>
#       <td>
#         {$row['kontobewegungs_datum']}
#       </td>
#   ";
#   $anzahl = $row['summe'] / 0.16;
#   sql_insert( 'gruppenpfand', array(
#     'gruppen_id' => $row['gruppen_id']
#   , 'bestell_id' => $row['bestell_id']
#   , 'pfand_wert' => 0.16
#   , 'anzahl_leer' => $anzahl
#   ) );
# 
#   echo "
#       <td>
#         $anzahl
#       </td>
#     </tr>
#   ";
# 
# }
# 
# ?>
#   <table>
# 
# <?
# 
