<?php
//
// abrechnung.php:
//

assert( $angemeldet ) or exit();
$editable = ( $hat_dienst_IV and ! $readonly );
need_http_var( 'bestell_id', 'u', true );

$state = getState($bestell_id);
$bestellung_name = bestellung_name( $bestell_id );
$lieferant_id = getProduzentBestellID( $bestell_id );
$lieferant_name = lieferant_name( $lieferant_id );

need( $state >= STATUS_VERTEILT, "Bestellung ist noch nicht verteilt!" );
need( $state < STATUS_ARCHIVIERT, "Bestellung ist bereits archiviert!" );


get_http_var( 'action', 'w', '' );
$editable or $action = '';

$query_by_produkt = "
  SELECT gesamtbestellungen.id as bestell_id
       , bestellvorschlaege.produkt_id as produkt_id
       , bestellvorschlaege.liefermente as liefermenge
       , produkte.name as produkt_name
       , sum( 
  FROM gesamtbestellungen
  INNER JOIN bestellvorschlaege
     ON bestellvorschlaege.gesamtbestellung_id = gesamtbestellungen.id
  INNER JOIN produkte
     ON produkte.id = bestellvorschlaege.produkt_id
  INNER JOIN produktgruppen
     ON produktgruppen.id=produkte.produktgruppen_id
  INNER JOIN produktpreise
     ON produktpreise.id = bestellvorschlaege.produktpreise_id
  INNER JOIN gruppenbestellungen
     ON gruppenbestellungen.gesamtbestellung_id = gesamtbestellungen.id
  INNER JOIN bestellzuordnung
     ON bestellzuordnung.produkt_id = bestellvorschlaege.produkt_id
        AND bestellzuordnung.gruppenbestellung_id = gruppenbestellungen.id
        AND bestellzuordnung.art = 2
  WHERE gesamtbestellungen.id = $bestell_id 
  GROUP BY bestellvorschlaege.produkt_id
  ORDER BY produktgruppen.id, produkte.name
";


/////////////////////////////
//
// aktionen verarbeiten:
//
/////////////////////////////

// ...


?>
<h2>Abrechung: Bestellung <? echo "$bestellung_name ($lieferant_name)"; ?></h2>

<form method='post' action='<? echo self_url(); ?>'>
<?echo self_post(); ?>
  <input type='hidden' name='action' value='abschluss'>

  <table>
    <tr>
      <th>Abrechnungsschritt</th>
      <th>Details</th>
      <th>Aktionen</th>
      <th>erledigt?</th>
    </tr>
    <tr>
      <th colspan='4'>Lieferant <? echo $lieferant_name; ?>: </th>
    </tr>
    <tr>
      <td>
        Liefermengen und -preise abgleichen:
      </td>
      <td>
        (kommt noch)
      </td>
        <a href="javascript:neuesfenster('index.php?window=bestellschein&bestell_id=$bestell_id','bestellschein');"
        >zum Lieferschein...</a>
      <td>
      <td>
        ok: <input type='checkbox' name='lieferschein_ok' value='yes'>
      </td>
    </tr>
    <tr>
      <td>
        Pfandabrechnung Lieferant:
        <div class='small'>(falls zutreffend, etwa bei Terra!)</div>
      </td>
      <td>
        (kommt noch)
      </td>
        <a href="javascript:neuesfenster('index.php?window=pfandverpackungen&bestell_id=$bestell_id','pfandzettel');"
        >zum Pfandzettel...</a>
      <td>
      <td>
        ok: <input type='checkbox' name='pfandzettel_ok' value='yes'>
      </td>
    </tr>
    <tr>
      <th colspan='4'>Bestellgruppen: </th>
    </tr>
    <tr>
      <td>
        Verteilmengen erfassen und abgleichen
        <div class='small'>(sofern noch nicht geschehen!)</div>
      </td>
      <td>
        (kommt noch)
      </td>
        <a href="javascript:neuesfenster('index.php?window=verteilung&bestell_id=$bestell_id','verteilliste');"
        >zur Verteilliste...</a>
      <td>
      <td>
        ok: <input type='checkbox' name='veteilung_ok' value='yes'>
      </td>
    </tr>
    <tr>
      <td>
        Basarkäufe eintragen:
      </td>
      <td>
        (kommt noch)
      </td>
        <a href="javascript:neuesfenster('index.php?window=basar','basar');"
        >zum Basar...</a>
      <td>
      <td>
        ok: <input type='checkbox' name='basar_ok' value='yes'>
      </td>
    </tr>
    <tr>
      <td>
        Pfandabrechnung Bestellgruppen:
        <div class='small'>(nur bei Terra!)</div>
      </td>
      <td>
        (kommt noch)
      </td>
        <a href="javascript:neuesfenster('index.php?window=gruppenpfand&bestell_id=$bestell_id','gruppenpfand');"
        >Pfandabrechnung Bestellgruppen...</a>
      <td>
      <td>
        ok: <input type='checkbox' name='gruppenpfand_ok' value='yes'>
      </td>
    </tr>
  </table>

</form>



