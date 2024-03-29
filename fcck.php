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


// fcck.php: konsistenzcheck fuer Datenbanken, entfernt verwaiste Eintraege, etc...
//
// Timo, 2008

exit(1);  // ...funktioniert noch nicht!

assert( $angemeldet ) or exit();  // aufruf sollte nur noch per index.php?area=bestellen erfolgen

global $db_handle;

<h1>Test Gesamtbestellungen:</h1>



  echo "
    <script type='text/javascript'>
      function neuesfenster(url,name) {
        f=window.open(url,name);
        f.focus();
      }
    </script>
  ";
  $self = 'bb.php?';
  $self_fields = '';

  echo "<h2><a href='$self'>Bestellungen</a></h2>";

  $bestellungen = doSql( "SELECT * FROM gesamtbestellungen ORDER by id" );
  $last_id = -1;
  while( $row = mysqli_fetch_array( $bestellungen ) ) {
    $n1 = sql_select_single_field( "SELECT count(*) as count FROM bestellvorschlaege WHERE gesamtbestellung_id = $id", 'count' );
    $n2 = sql_select_single_field( "SELECT count(*) as count FROM gruppenbestellungen WHERE gesamtbestellung_id = $id", 'count' );
    if(





  $bestell_id = false;
  if( get_http_var('bestell_id') ) {
    $self = "$self&bestell_id=$bestell_id";
    $self_fields = $self_fields . "<input type='hidden' name='bestell_id' value='$bestell_id'>";
  } else {
    $bestellungen = mysqli_query( $db_handle, "SELECT * FROM gesamtbestellungen ORDER BY bestellende DESC,name" )
      or error ( __LINE__, __FILE__, "Suche in gesamtbestellungen fehlgeschlagen" );
    echo "
      <table>
        <tr>
          <th>Id</th>
          <th>Name</th>
          <th>Status</th>
          <th>von</th>
          <th>bis</th>
          <th>Ausgang</th>
          <th>Lieferung</th>
          <th>Bezahlung</th>
        </tr>
    ";
    while( $bestellung = mysqli_fetch_array( $bestellungen ) ) {
      echo "
        <tr>
          <td><a href='$self&bestell_id={$bestellung['id']}'>{$bestellung['id']}</a></td>
          <td><a href='$self&bestell_id={$bestellung['id']}'>{$bestellung['name']}</a></td>
          <td>{$bestellung['state']}</td>
          <td>{$bestellung['bestellstart']}</td>
          <td>{$bestellung['bestellende']}</td>
          <td>{$bestellung['ausgang']}</td>
          <td>{$bestellung['lieferung']}</td>
          <td>{$bestellung['bezahlung']}</td>
        </tr>
      ";
    }
    echo "</table><hr>";
    exit( $print_on_exit );
  }

  $bestellungen = mysqli_query( $db_handle,
    "SELECT * FROM gesamtbestellungen WHERE id='$bestell_id' ORDER BY bestellende DESC,name"
  ) or error ( __LINE__, __FILE__, "Suche nach Bestellung" );
  $bestellung = mysqli_fetch_array( $bestellungen )
    or error ( __LINE__, __FILE__, "Bestellung nicht gefunden" );

  echo "<h2>Bestellung: <a href='$self'>{$bestellung['name']} ($bestell_id)</a></h2>";

  $produkt_id = false;
  if( get_http_var('produkt_id') ) {
    $self = "$self&produkt_id=$produkt_id";
    $self_fields = $self_fields . "<input type='hidden' name='produkt_id' value='$produkt_id'>";
  } else {
    $vorschlaege = mysqli_query( $db_handle,
      "SELECT * FROM bestellvorschlaege WHERE gesamtbestellung_id='$bestell_id' ORDER BY produkt_id"
    ) or error ( __LINE__, __FILE__, "Suche in bestellvorschlaegen fehlgeschlagen" );
    echo "
      <table>
        <tr>
          <th>Id</th>
          <th>Name</th>
          <th>Preis-Id</th>
          <th>Bestellmenge</th>
          <th>Liefermenge</th>
        </tr>
    ";
    while( $vorschlag = mysqli_fetch_array( $vorschlaege ) ) {
      $produkte = mysqli_query( $db_handle,
        "SELECT * FROM produkte WHERE id='{$vorschlag['produkt_id']}'"
      ) or error ( __LINE__, __FILE__, "Suche nach Produkt fehlgeschlagen" );
      if( ! ( $produkt = mysqli_fetch_array( $produkte ) ) ) {
        echo "<div class='warn'>Produkt '{$produkt_id}' nicht gefunden</div>";
      }
      echo "
        <tr>
          <td><a href='$self&produkt_id={$produkt['id']}'>{$produkt['id']}</a></td>
          <td><a href='$self&produkt_id={$produkt['id']}'>{$produkt['name']}</a></td>
          <td>
          <a href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produkt_id={$produkt['id']}&bestell_id=$bestell_id','foodsoftdetail');\"
          >{$vorschlag['produktpreise_id']}</a></td>
          <td>{$vorschlag['bestellmenge']}</td>
          <td>{$vorschlag['liefermenge']}</td>
        </tr>
      ";
    }
    echo "</table><hr>";
    exit( $print_on_exit );
  }

  $vorschlaege = mysqli_query( $db_handle,
    "SELECT * FROM bestellvorschlaege
     WHERE gesamtbestellung_id='$bestell_id' AND produkt_id='$produkt_id' "
  ) or error ( __LINE__, __FILE__, "Suche in bestellvorschlaegen fehlgeschlagen" );
  $vorschlag = mysqli_fetch_array( $vorschlaege )
    or error ( __LINE__, __FILE__, "Bestellvorschlag nicht gefunden" );

  $produkte = mysqli_query( $db_handle,
    "SELECT * FROM produkte WHERE id='{$vorschlag['produkt_id']}'"
  ) or error ( __LINE__, __FILE__, "Suche nach Produkt fehlgeschlagen" );
  $produkt = mysqli_fetch_array( $produkte )
    or error ( __LINE__, __FILE__, "Produkt '{$vorschlag['produkt_id']}' nicht gefunden" );

  echo "<h2>Bestellvorschlag: <a href='$self'>{$produkt['name']} ({$vorschlag['produkt_id']})</a></h2>";

  $gruppen_id = false;
  if( get_http_var('gruppen_id') ) {
    $self = "$self&gruppen_id=$gruppen_id";
    $self_fields = $self_fields . "<input type='hidden' name='gruppen_id' value='$gruppen_id'>";
  } else {
    $order_by != '' or $order_by='art,bestellguppen_id';
    $zuordnungen = mysqli_query( $db_handle,
      "SELECT *
        FROM bestellzuordnung
        INNER JOIN gruppenbestellungen
                   ON gruppenbestellungen.id=bestellzuordnung.gruppenbestellung_id
        INNER JOIN produktpreise
        INNER JOIN bestellgruppen
                   ON bestellgruppen.id=gruppenbestellungen.bestellguppen_id
        WHERE     gruppenbestellungen.gesamtbestellung_id='$bestell_id'
              AND bestellzuordnung.produkt_id='$produkt_id'
              AND produktpreise.id='{$vorschlag['produktpreise_id']}'
              AND produktpreise.produkt_id='$produkt_id'
        ORDER BY $order_by
      "
    ) or error ( __LINE__, __FILE__,
      "Suche in bestellzuordnung,gruppenbestellungen fehlgeschlagen: " . mysqli_error( $db_handle ) );
    echo "
      <table class='list'>
        <tr>
          <th><a href='$self&order_by=bestellguppen_id,art'>Gruppe</th>
          <th><a href='$self&order_by=zeitpunkt,art,bestellguppen_id'>Zeit</th>
          <th><a href='$self&order_by=art,bestellguppen_id'>Art</th>
          <th colspan='2'>Menge</th>
          <th>Einzelpreis</th>
          <th>Gesamtpreis</th>
          <th>Gruppenbestellung</th>
        </tr>
    ";
    while( $zuordnung = mysqli_fetch_array( $zuordnungen ) ) {
      $zuordnung = preisdatenSetzen( $zuordnung );
      echo "
        <tr>
          <td>{$zuordnung['bestellguppen_id']} ({$zuordnung['name']})</td>
          <td>{$zuordnung['zeitpunkt']}</td>
          <td>{$zuordnung['art']}</td>
          <td class='mult'>" . $zuordnung['menge'] * $zuordnung['kan_verteilmult'] . "</td>
          <td class='unit'>{$zuordnung['kan_verteileinheit']}</td>
          <td class='number'>
          <a href=\"javascript:neuesfenster('/foodsoft/terraabgleich.php?produkt_id=$produkt_id&bestell_id=$bestell_id','foodsoftdetail');\"
          >{$zuordnung['preis']}</a></td>
          <td class='number'>" . $zuordnung['preis'] * $zuordnung['menge'] . "</td>
          <td class='number'>" . $zuordnung['gruppenbestellung_id'] . "</td>
        </tr>
      ";
    }
    echo "</table><hr>";
    exit( $print_on_exit );
  }

?>


