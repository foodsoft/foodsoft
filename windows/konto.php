<?php
//
// konto.php: Bankkonto-Verwaltung
//

assert( $angemeldet ) or exit();
$editable = ( hat_dienst(4) and ! $readonly );

get_http_var( 'ajax', 'u', false );

if( $ajax )
  header( 'Content-Type: application/json' );

if( ! $ajax ) {
  setWikiHelpTopic( 'foodsoft:kontoverwaltung' );

  ?>
  <script type="text/javascript" src='<?php echo $foodsoftdir; ?>/js/lib/papaparse.min.js'></script>
  <h1>Bankkonten</h1><?php
}

$konten = sql_konten();

if( count($konten) == 1 ) {
  $row = current( $konten );
  $konto_id = $row['id'];
} else {
  $konto_id = 0;
}
get_http_var( 'konto_id', 'u', $konto_id, true );

get_http_var( 'auszug', '/^\d+-\d+$/', 0, false );  // kompakt-format (aus <select> unten!)
if( $auszug ) {
  sscanf( $auszug, "%u-%u", $auszug_jahr, $auszug_nr );
  $self_fields['auszug_jahr'] = $auszug_jahr;
  $self_fields['auszug_nr'] = $auszug_nr;
} else {
  get_http_var( 'auszug_jahr', 'u', 0, true ) or $auszug_jahr = 0;
  get_http_var( 'auszug_nr', 'u', 0, true ) or $auszug_nr = 0;
}

if( ! $ajax ) {
  //////////////////////
  // hauptmenue und auswahl konto:
  //
  open_table( 'layout hfill' );
    open_td('left');
      open_table('menu');
        if( $editable ) {
          open_tr();
            open_td( '', '', fc_link( 'edit_konto'
                      , "class=bigbutton,title=Neues Bankkonto eintragen,text=Neues Konto" ) );
        }
        open_tr();
          open_td( '', '', fc_link( 'self', "class=bigbutton,text=Seite aktualisieren" ) );
        open_tr();
          open_td( '', '', fc_link( 'index', "class=bigbutton" ) );
      close_table();

    open_td( 'floatright' );
      auswahl_konto( $konto_id );
  close_table();

  bigskip();
} // ! ajax

if( ! $konto_id )
  return;

if( $ajax ) {
  $ajax_response = [
    'success' => true
  ,  'itan' => $_POST['itan']
  , 'next_itan' => get_itan()
  ];
}

get_http_var( 'action', 'w', false );
$editable or $action = '';
global $db_handle;
switch( $action ) { // aktionen die keinen auszug brauchen
  case 'cancel_payment':
    need_http_var( 'transaction_id', 'u' );
    doSql( "DELETE FROM gruppen_transaktion WHERE id=$transaction_id" );
    if( $ajax )
      $ajax_response += [ 'rows' => mysqli_affected_rows( $db_handle ) ];
    else
      reload_immediately( fc_link( '', 'context=action' ) );
    break;
  case 'buchung_gruppe_bank':
    action_buchung_gruppe_bank();
    break;
  case 'buchung_lieferant_bank':
    action_buchung_lieferant_bank();
    break;
  case 'buchung_bank_bank':
    action_buchung_bank_bank();
    break;
  case 'buchung_bank_sonderausgabe':
    action_buchung_bank_sonderausgabe();
    break;
  case 'buchung_bank_anfangsguthaben':
    action_buchung_bank_anfangsguthaben();
    break;
  case 'finish_transaction':
    action_finish_transaction();
    break;
  case 'regeln_speichern':
    action_save_booking_rules();
    break;
  default:
    if( $action === 'nop' )
      $action = false;
    need( ! $action, "Unbekannte Aktion '$action'!" );
}

if( $ajax ) {
  echo json_encode( $ajax_response );
  exit( 0 );
}

$kontodaten = sql_kontodaten( $konto_id );

//////////////////////
// auszug auswaehlen:
//

open_table('layout hfill' );

  open_td();
    ?> <h3>Kontouszüge von Konto <?php echo sql_kontoname($konto_id); ?>:</h3> <?php

    open_select( 'auszug', 'autoreload' );
      $selected = false;
      $options = '';
      foreach( sql_kontoauszug( $konto_id ) as $auszug ) {
        $jahr = $auszug['kontoauszug_jahr'];
        $nr = $auszug['kontoauszug_nr'];

        $posten = count( sql_kontoauszug( $konto_id, $jahr, $nr ) );
        $saldo = sql_bankkonto_saldo( $konto_id, $auszug['kontoauszug_jahr'], $auszug['kontoauszug_nr'] );

        $options .= "<option value='$jahr-$nr'";
        if( $jahr == $auszug_jahr and $nr == $auszug_nr ) {
          $options .= " selected";
          $selected = true;
        }
        $options .= ">$jahr / $nr ($posten Posten, Saldo: $saldo)</option>";
      }
      if( ! $selected ) {
        $options = "<option value='0' selected>(Bitte Auszug wählen)</option>" . $options;
      }
      echo $options;
    close_select();
    smallskip();

    if( $editable ) {
      open_fieldset( 'small_form', '','Neuen Auszug anlegen', 'off' );
        open_form( array( 'auszug_jahr' => NULL, 'auszug_nr' => NULL ) /* don't pass these automatically */ );
          open_div('oneline');
            echo "<label>Jahr:</label> " . string_view( date('Y'), 4, 'auszug_jahr' );
            echo " / <label>Nr:</label>" . string_view( '', 2, 'auszug_nr' );
            submission_button();
          close_div();
        close_form();
      close_fieldset();
    }

  if( ! $auszug_jahr or ! $auszug_nr ) {
    close_table();
    return;
  }

  bigskip();
  close_tr();
  open_td();

    ////////////////////////////////
    // anzeige eines kontoauszugs:
    //

    $kontoname = sql_kontoname($konto_id);
    echo "<h3>$kontoname - Auszug $auszug_jahr / $auszug_nr</h3>";


    if( $editable ) {
      open_div('', 'id="transaction_fieldset"');
        open_fieldset( 'small_form', '', 'Transaktion eintragen', 'off' );
          alternatives_radio( array(
            'import_form' => "Kontoauszug importieren"
          , 'gruppe_bank_form' => "Einzahlung / Auszahlung Gruppe"
          , 'lieferant_bank_form' => "Überweisung / Lastschrift Lieferant"
          , 'bank_bank_form' => "Überweisung auf ein anderes Konto der FC"
          , 'sonderausgabe_bank_form' => "Überweisung/Abbuchung Sonderausgabe"
          , 'anfangsguthaben_bank_form' => "Anfangskontostand erfassen"
          ) );

          open_div( 'nodisplay', "id='import_form'" );
            open_fieldset( 'small_form', '', 'Kontoauszug importieren' );
              open_table('layout');
                open_tr();
                  open_td( 'label', '', 'Datei (CSV):' );
                  open_td( 'kbd' ); echo '<input id="auszug_input" type="file" name="auszug" accept=".csv" '
                    . 'onchange="$(\'transaction_fieldset\').style.display=\'none\'; importAccountSheetPreview();">';
              close_table();
            close_fieldset();
          close_div();

          open_div( 'nodisplay', "id='gruppe_bank_form'" );
            formular_buchung_gruppe_bank();
          close_div();

          open_div( 'nodisplay', "id='lieferant_bank_form'" );
            formular_buchung_lieferant_bank();
          close_div();

          open_div( 'nodisplay', "id='bank_bank_form'" );
            formular_buchung_bank_bank();
          close_div();

          open_div( 'nodisplay', "id='sonderausgabe_bank_form'" );
            formular_buchung_bank_sonderausgabe();
          close_div();

          open_div( 'nodisplay', "id='anfangsguthaben_bank_form'" );
            formular_buchung_bank_anfangsguthaben();
          close_div();

        close_fieldset();
        medskip();
      close_div();
    }

    $startsaldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr-1 );
    $saldo = sql_bankkonto_saldo( $konto_id, $auszug_jahr, $auszug_nr );

    open_table('list', 'id="kontoauszug"');
        open_th('','','Posten');
        open_th('','','Valuta');
        open_th('','','Buchung');
        open_th('','','Kommentar');
        open_th('','','Betrag');
        open_th('','','Aktionen');
      open_tr('summe');
        open_td( 'right', "colspan='4'", 'Startsaldo:');
        open_td( 'number', '', price_view( $startsaldo ) );
        open_td();

    $n=0;
    foreach( sql_kontoauszug( $konto_id, $auszug_jahr, $auszug_nr ) as $row ) {
      $n++;
      $kommentar = $row['kommentar'];
      $konterbuchung_id = $row['konterbuchung_id'];

      open_tr();
        open_td( 'number', '', $n );
        open_td( 'number', '', $row['valuta_trad'] );
        open_td( 'number', '', $row['buchungsdatum_trad']."<div class='small'>{$row['dienst_name']}</div>" );
        open_td();
          echo $kommentar;

          if( $konterbuchung_id ) {
            $konterbuchung = sql_get_transaction( $konterbuchung_id );
            if( $konterbuchung_id > 0 ) {
              $k_konto_id = $konterbuchung['konto_id'];
              $k_auszug_jahr = $konterbuchung['kontoauszug_jahr'];
              $k_auszug_nr = $konterbuchung['kontoauszug_nr'];
              open_div( '', '', 'Gegenbuchung: ' . fc_link( 'kontoauszug', array(
                  'konto_id' => $k_konto_id, 'auszug_jahr' => $k_auszug_jahr, 'auszug_nr' => $k_auszug_nr
                , 'text' => "{$konterbuchung['kontoname']}, Auszug $k_auszug_jahr / $k_auszug_nr", 'class' => 'href'
              ) ) );
            } else {
              $gruppen_id = $konterbuchung['gruppen_id'];
              $lieferanten_id=$konterbuchung['lieferanten_id'];
              if( $gruppen_id ) {
                if( $gruppen_id == sql_muell_id() ) {
                  $typ = $konterbuchung['transaktionstyp'];
                  div_msg( '', fc_link( 'verlust_details', array(
                              'detail' => $typ, 'class' => 'href', 'text' => transaktion_typ_string( $typ ) ) ) );
                } else {
                  $gruppen_name = sql_gruppenname( $gruppen_id );
                  div_msg( '', 'Überweisung Gruppe '. fc_link( 'gruppenkonto', array(
                            'gruppen_id' => $gruppen_id , 'class' => 'href', 'text' => $gruppen_name ) ) );
                }
              } elseif ( $lieferanten_id ) {
                $lieferanten_name = sql_lieferant_name( $lieferanten_id );
                div_msg( '', 'Überweisung/Lastschrift Lieferant ' . fc_link( 'lieferantenkonto', array(
                            'lieferanten_id' => $lieferanten_id , 'class' => 'href', 'text' => $lieferanten_name ) ) );
              } else {
                div_msg( 'warn', 'fehlerhafte Buchung' );
              }
            }
          } else {
            div_msg( 'warn', 'unvollständige oder fehlerhafte Buchung' );
          }
        open_td( 'number bottom', '', price_view( $row['betrag'] ) );
        open_td( '', 'bottom', fc_link( 'edit_buchung', "buchung_id={$row['id']}" ) );
    }

      open_tr('summe');
        open_td( 'right', "colspan='4'", 'Saldo:');
        open_td( 'number', '', price_view( $saldo ) );
        open_td();
    close_table();

    open_div('', 'id="import" style="display:none"');
      open_table('list');
          open_th('', 'colspan="100%"', 'Import-Vorschau');
        open_tr();
          open_th('', '', 'Posten');
          open_th('', '', 'Valuta');
          open_th('', '', 'Kontoinhaber');
          open_th('', '', 'Verwendungszweck');
          open_th('', '', 'Betrag');
          open_th('', '', 'Verbuchung');
        close_tr();
        open_tag('tbody', '', 'id="import_body"', '');
        open_tr();
          open_td('', 'colspan="5"', '');
          open_td('center');
            html_button( 'Verbuchen', 'importAccountSheetExecute();', 'id="execImportButton"');
          close_td();
      close_table();
    close_div();

    open_div('', 'id="import_log" style="display:none"', '');

    open_div('bigskip', 'id="remaining_unbooked" style="display:none"');
      open_table('list');
          open_th('', 'colspan="100%"', 'Nicht bestätigte Vormerkungen');
        open_tr();
          open_th('', '', 'Datum');
          open_th('', '', 'Gruppe');
          open_th('', '', 'Betrag');
          open_th('', '', 'Verwerfen?');
        close_tr();
        open_tag('tbody', '', 'id="remaining_unbooked_body"', '');
        open_tr();
          open_td('', 'colspan="3"', '');
          open_td('center');
            html_button( 'Verwerfen', 'cancelPayments();', 'id="cancelPaymentsButton"');
          close_td();
      close_table();
    close_div();

    open_div( '', 'style="display:none" id="show_account_button"', fc_link( 'self', "class=bigbutton,text=Buchungen anzeigen" ) );

    $ungebuchte_einzahlungen = sql_ungebuchte_einzahlungen();
    if( $editable and $ungebuchte_einzahlungen ) {
      open_td( 'floatright' );
        ?> <h4>ungebuchte Einzahlungen:</h4> <?php

        // open_div( 'kommentar left', '', 'Hier könnt ihr den Geldeingang von Einzahlungen, die von den Gruppen selbst eingetragen wurden,
        //                      bestätigen, oder die Einzahlung stornieren:' );

        smallskip();
        open_table('list');
          open_th('','','Datum');
          open_th('','','Gruppe');
          open_th('','','Betrag');
          open_th('','','Aktionen');

          foreach( $ungebuchte_einzahlungen as $trans ) {
            open_tr('', "id='unbooked-{$trans['id']}'");
              open_td('','', $trans['eingabedatum_trad'] );
              open_td();
                echo gruppe_view( $trans['gruppen_id'] );
                open_ul();
                  foreach( sql_gruppe_mitglieder( $trans['gruppen_id'] ) as $pers )
                    open_li( '', '', $pers["vorname"]." ".$pers["name"] );
                close_ul();
              open_td( 'number', '', price_view( $trans['summe'] ) );
              open_td();
                if( $editable ) {
                  form_finish_transaction( $trans['id'] );
                  echo "<hr>";
                  open_div( 'right', '', fc_action( array( 'title' => 'diese ungebuchte Gutschrift stornieren', 'text' => 'löschen'
                                                        , 'class' => 'button drop', 'confirm' => 'Gutschrift wirklich löschen?' )
                                                  , "action=cancel_payment,transaction_id={$trans['id']}" ) );
                }
          }
        close_table();
  }
close_table();
bigskip();

open_javascript( toJavaScript( 'var konto', [
  'id' => $konto_id
, 'nr' => str_replace( ' ', '', $kontodaten['kontonr'])
, 'regeln' => $kontodaten['buchungsregeln']
] ) );

$ajax_url = preg_replace( '/&amp;/', '&', fc_link('self', ['download' => 'konto', 'context' => 'action'] ) );
open_javascript( toJavaScript( 'var ajax', [ 'url' => $ajax_url, 'itan' => get_itan() ] ) );
open_javascript( toJavaScript( 'var auszug', [ 'jahr' => (int)$auszug_jahr, 'nr' => (int)$auszug_nr ] ) );
open_javascript( toJavaScript( 'var gruppen', array_map(
  fn ($gruppe) => [
    'id' => $gruppe['id']
  , 'nr' => $gruppe['gruppennummer']
  , 'name' => html_entity_decode( $gruppe['name'], ENT_QUOTES, 'UTF-8' )
  , 'regeln' => $gruppe['buchungsregeln'] ]
, sql_gruppen( ['aktiv' => true, 'buchungsregeln' => true] ) ) ) );
open_javascript( toJavaScript( 'var lieferantys', array_map(
  fn( $lieferanty ) => [
    'id' => $lieferanty['id']
  , 'name' => html_entity_decode( $lieferanty['name'], ENT_QUOTES, 'UTF-8' )
  , 'regeln' => $lieferanty['buchungsregeln'] ]
, sql_lieferanten() ) ) );
open_javascript( toJavaScript( 'var ungebuchteEinzahlungen',
  array_map(fn ($einzahlung) => [
    'id' => $einzahlung['id'],
    'gruppen_id' => $einzahlung['gruppen_id'],
    'valuta' => $einzahlung['valuta'],
    'betrag' => $einzahlung['summe']
  ], $ungebuchte_einzahlungen ) ) );

open_javascript(<<<'JS'

const lieferantyIndex = $H();
lieferantys.each( l => {
  l.regeln = l.regeln === '' ? null : JSON.parse(l.regeln);
  lieferantyIndex[l.id] = l;
 });

const gruppenIndex = $H();
gruppen.each( g => {
  g.regeln = g.regeln === '' ? null : JSON.parse(g.regeln);
  gruppenIndex[g.id] = g;
 });

konto.regeln = konto.regeln === '' ? null : JSON.parse(konto.regeln);

ungebuchteEinzahlungen.each( x => { x.valuta = new Date(x.valuta); x.betrag = Number(x.betrag); } );

const ungebuchteEinzahlungenIndex = $H();
ungebuchteEinzahlungen.each(
  x => (ungebuchteEinzahlungenIndex[x.gruppen_id] ?? (ungebuchteEinzahlungenIndex[x.gruppen_id] = []))
    .push(x) );

const Tags = {
  Orig: 'orig',
  SourceAccount: 'sourceAccount',
  OtherName: 'otherName',
  OtherAccount: 'otherAccount',
  Valuta: 'valuta',
  Type: 'type',
  Id: 'id',
  Note: 'note',
  OrigNote: 'origNote',
  Amount: 'amount',
  MatchIndex: 'matchIndex',
  GroupTransaction: 'groupTransaction',
};

const Types = {
  Gruppe: 'Gruppe',
  Lieferanty: 'Lieferanty',
  Sonderausgabe: 'Sonderausgabe',
};

const Parsers = {
  GermanNumber: 'GermanNumber',
  GermanDate: 'GermanDate',
};

const ParserFunctions = {
  GermanNumber: function(s) {
    return Number(s.replaceAll('.','').replace(',','.'));
  },
  GermanDate: function(s) {
    const [ day, month, year ] = s.split('.');
    return new Date(year, month-1, day);
  }
}

const fixedClassifier = [
  {
    orig: Tags.Orig
  },
];

// suitable for GLS-CSV-Export
const defaultAccountClassifier = [
  {
    column: 1,
    header: '^IBAN Auftrag',
    tag: Tags.SourceAccount,
  },
  {
    column: 5,
    header: '^Valuta',
    parse: Parsers.GermanDate,
    tag: Tags.Valuta,
  },
  {
    column: 6,
    header: '^Name Zahlungsbeteiligter$',
    tag: Tags.OtherName,
  },
  {
    column: 7,
    header: '^IBAN Zahlungsbeteiligter$',
    tag: Tags.OtherAccount,
  },
  {
    column: 10,
    header: '^Verwendungszweck$',
    tag: Tags.OrigNote,
  },
  {
    column: 11,
    header: '^Betrag$',
    parse: Parsers.GermanNumber,
    tag: Tags.Amount,
  },
  {
    column: 9,
    header: '^Buchungstext$',
    match: '^Abschluss$',
    store: [
      [Tags.Type, Types.Sonderausgabe],
      [Tags.Note, `Kontoführung {nr}/{jahr}`]
    ],
  },
];

const classifier = [];

function prepare(classifier, data) {
  if (!data.length)
    return [];
  const header = data[0];
  let result = [...classifier]
  result.forEach(c => {
    if (c.column !== undefined) {
      if (typeof c.column !== 'number')
        throw new Error('column: Benötige Ganzzahl als Argument!', { cause: c });
      if (c.column < 0 || c.column >= header.length) {
        throw new Error(`column: Spalte ${c.column} außerhalb [0, ${header.length - 1}]!`, { cause: c });
      }
    }
    if (c.header !== undefined) {
      if (typeof c.header !== 'string')
        throw new Error('header: Benötige Zeichenkette mit Regex als Argument!', { cause: c });
      if (c.column !== undefined) {
        if (!header[c.column]?.match(c.header)) {
          throw new Error(`column: Spalte ${c.column} passt nicht zu Regex "${c.header}"!`, { cause: c });
          return;
        }
      } else {
        header.each((h, i) => {
          if (h.match(c.header)) {
            c.column = i;
            throw $break;
          }
        });
        if (c.column === undefined)
          throw new Error(`header: Keine Spalte passt zu Regex "${c.header}"!`, { cause: c });
      }
    }
  });
  return result;
}

function classify(classifier, data, lineIndex, classified) {
  if (data.length <= lineIndex)
    return null;
  const header = data[0];
  const line = data[lineIndex];
  if (classified[lineIndex - 1] === undefined)
    classified[lineIndex - 1] = {};
  const result = classified[lineIndex - 1];
  classifier.each((c, i) => {
    if (c.orig !== undefined) {
      result[c.orig] = line;
      return;
    }
    if (result.exception)
      return;
    let value;
    if (c.column !== undefined)
      value = line[c.column];
    else if (c.tagged !== undefined)
      value = result[c.tagged];
    else
      return;
    if (c.match !== undefined) {
      if (typeof c.match !== 'string')
        throw new Error('match: Benötige Zeichenkette mit Regex als Argument!', { cause: c });
      if (!value.match(c.match))
        return;
      result[Tags.MatchIndex] = i;
    }
    if (c.store !== undefined) {
      c.store.each(s => {
        const [ tag, value ] = s;
        if (typeof tag !== 'string')
          throw new Error('store: Benötige Zeichenkette als Tag!', { cause: c });
        result[tag] = value;
      });
    }
    if (c.parse !== undefined) {
      if (typeof c.parse !== 'string')
          throw new Error('parse: Benötige Zeichenkette als Argument!', { cause: c });
      if (typeof ParserFunctions[c.parse] !== 'function')
          throw new Error(`parse: Unbekannte Funktion "${c.parse}"!`, { cause: c });
      value = ParserFunctions[c.parse](value);
    }
    if (c.tag !== undefined) {
      if (typeof c.tag !== 'string')
          throw new Error('tag: Benötige Zeichenkette als Argument!', { cause: c });
      result[c.tag] = value;
    }
  });
}

function makeId() {
  return `cs-${makeId.id++}`;
}

makeId.id = 1;

const classifierUis = [];

function makeClassificationUi(idx) {
  const unclassified = classified[idx];
  if (classifierUis[idx] === undefined) {
    classifierUis[idx] = [
      'radioGroupId'
    , 'groupTransactionId'
    , 'supplierTransactionId'
    , 'specialTransactionId'
    , 'confirmIbanId'
    , 'detailsId'].reduce((a, x) => (a[x] = makeId(), a), {});
  }

  const {
    radioGroupId
  , groupTransactionId
  , supplierTransactionId
  , specialTransactionId
  , confirmIbanId
  , detailsId } = classifierUis[idx];
  return `\
<span class="oneline">\
<input type="radio" name="${radioGroupId}" id="${groupTransactionId}" value="${Types.Gruppe}" \
 oninput="setClassificationType(${idx}, '${groupTransactionId}');">\
<label for="${groupTransactionId}">${Types.Gruppe}</label>\
</span> \
<span class="oneline">\
<input type="radio" name="${radioGroupId}" id="${supplierTransactionId}" value="${Types.Lieferanty}" \
 oninput="setClassificationType(${idx}, '${supplierTransactionId}');">\
<label for="${supplierTransactionId}">${Types.Lieferanty}</label>\
</span> \
<span class="oneline">\
<input type="radio" name="${radioGroupId}" id="${specialTransactionId}" value="${Types.Sonderausgabe}" \
 oninput="setClassificationType(${idx}, '${specialTransactionId}');">\
<label for="${specialTransactionId}">${Types.Sonderausgabe}</label>\
</span>\
<div id="${detailsId}"></div>\
<div>` + (unclassified.exception
? `<a href='javascript:resetClassificationException(${idx})' \
 class='drop' title='Änderung abbrechen und Regeln wieder anwenden'></a>`
: `<a href='javascript:makeIbanClassifier("${unclassified.otherAccount}", ${idx})' \
 class='button' id='${confirmIbanId}' style='display:none'>IBAN-Regel erstellen</a>`)
+ '</div>';
}

function setClassifierAction(idx, classifier, type, idOrNote) {
  Object.assign(classifier, {
    source: {
      type: type === Types.Sonderausgabe ? 'Konto' : type,
      ...(type !== Types.Sonderausgabe && { id: idOrNote }),
      index: idx,
    },
    store: [
      [ Tags.Type, type ]
    , [ type === Types.Sonderausgabe ? Tags.Note : Tags.Id, idOrNote ]
    ]
  });
  return classifier;
}

function error(title, detail) {
  console.log(`Fehler: ${title}`, detail);
  window.alert(`Fehler: ${title}`);
}

async function ajaxCall(parameters) {
  return await new Promise((resolve, reject) => {
    new Ajax.Request( ajax.url, {
        parameters: {
          ajax: 1,
          itan: ajax.itan,
          ...parameters
        },
        onSuccess: function(response) {
          let json = response.responseJSON;
          ajax.itan = json.next_itan;
          if( !ajax.itan ) {
            reject(new Error('Keine neue ITAN erhalten!', { cause: json }));;
            return;
          }
          if( !json.success ) {
            reject(new Error('Unerwarteter Fehler!', { cause: json }));;
            return;
          }
          resolve(response);
        },
        onFailure: function(response) {
          reject(ajaxError(response));
        }
      } ); // ajax
  });
}

function ajaxError(response) {
  let json = response.responseJSON;
  ajax.itan = response.responseJSON.next_itan;

  let message = json.reason;
  if (json.comment !== undefined)
    message += ` (${json.comment})`;
  if( !ajax.itan )
    message += '; keine neue ITAN erhalten';
  return new Error(message, { cause: json });
}

function storeRules(type, id, rules) {
  ajaxCall({
    action: 'regeln_speichern',
    type: type,
    id: id,
    rules: JSON.stringify(rules),
  }).then((r) => {
    console.log('storeRules:', r.statusText);
  }).catch((e) => {
    error(e.message, e.cause);
  });
}

function makeIbanClassifier(iban, idx) {
  const classification = classified[idx];
  if (classification?.type == null) {
    window.alert('Unerwartet: Keine Zuordnung gewählt!');
    return;
  }
  const newClassifier = {
    tagged: Tags.OtherAccount,
    match: `^${iban}$`,
  };

  if (classification.type === Types.Gruppe) {
    const gruppe = gruppenIndex[classification.id];
    (gruppe.regeln ?? (gruppe.regeln = [])).push({...newClassifier});
    storeRules('gruppe', classification.id, gruppe.regeln);
    setClassifierAction(gruppe.regeln.length - 1, newClassifier, classification.type, classification.id);
  } else if (classification.type === Types.Lieferanty) {
    const lieferanty = lieferantyIndex[classification.id];
    (lieferanty.regeln ?? (lieferanty.regeln = [])).push({...newClassifier});
    storeRules('lieferanty', classification.id, lieferanty.regeln);
    setClassifierAction(lieferanty.regeln.length - 1, newClassifier, classification.type, classification.id);
  } else if (classification.type === Types.Sonderausgabe) {
    setClassifierAction(konto.regeln?.length, newClassifier, classification.type, classification.note);
    const persistentClassifier = {...newClassifier};
    delete persistentClassifier.source;
    (konto.regeln ?? (konto.regeln = [])).push(persistentClassifier);
    storeRules('konto', konto.id, konto.regeln);
  }
  classifier.push(newClassifier);
  importAccountSheetPreview(false);
}

function dropClassifier(index) {
  const [ droppedClassifier ] = classifier.splice(index, 1);
  if (droppedClassifier.source.type === 'Konto') {
    konto.regeln.splice(droppedClassifier.source.index, 1);
    storeRules('konto', konto.id, konto.regeln);
  } else if (droppedClassifier.source.type === Types.Gruppe) {
    const gruppe = gruppenIndex[droppedClassifier.source.id];
    gruppe.regeln.splice(droppedClassifier.source.index, 1);
    storeRules('gruppe', droppedClassifier.source.id, gruppe.regeln);
  } else if (droppedClassifier.source.type === Types.Lieferanty) {
    const lieferanty = lieferantyIndex[droppedClassifier.source.id];
    lieferanty.regeln.splice(droppedClassifier.source.index, 1);
    storeRules('lieferanty', droppedClassifier.source.id, lieferanty.regeln);
  }
  classified.each((c, i) => { if (c.matchIndex === index) delete c.matchIndex; });
  importAccountSheetPreview(false);
}

function makeClassificationException(index) {
  const classification = classified[index];
  classification.exception = true;
  delete classification.matchIndex;
  importAccountSheetPreview(false);
}

function resetClassificationException(index) {
  const classification = classified[index];
  delete classification.exception;
  importAccountSheetPreview(false);
}

function addOption(select, value, text) {
  const option = document.createElement('option');
  option.value = value;
  option.textContent = text;
  select.append(option);
  return option;
}

function setCurrentClassification(idx) {
  const classification = classified[idx];
  const ui = classifierUis[idx];
  if (!classification || !ui)
    return;
  if (classification.type === undefined)
    return;
  if (classification.type === Types.Gruppe) {
    $(ui.groupTransactionId).click();
    const select = $(ui.groupSelectId);
    select.value = classification.id ?? '';
    select.dispatchEvent(new Event('input'));
    return;
  }
  if (classification.type === Types.Lieferanty) {
    $(ui.supplierTransactionId).click();
    const select = $(ui.supplierSelectId);
    select.value = classification.id ?? '';
    select.dispatchEvent(new Event('input'));
    return;
  }
  if (classification.type === Types.Sonderausgabe) {
    $(ui.specialTransactionId).click();
    const input = $(ui.specialNoteId);
    input.value = classification.note;
    input.dispatchEvent(new Event('input'));
    return;
  }
  error(`Unbekannter Typ "${classification.type}"`, classification);
}

function formatNote(classified, notePattern) {
  return notePattern
    ?.replaceAll('{kti}', classified.otherName)
    ?.replaceAll('{vwz}', classified.origNote)
    ?.replaceAll('{nr}', auszug.nr.toString().padStart(2, '0'))
    ?.replaceAll('{jahr}', auszug.jahr)
    ?? classified.origNote;
}

const dayMs = 1000*60*60*24;

function getMatchingUnbooked(classification) {
  const result = (ungebuchteEinzahlungenIndex[classification.id] ?? [])
        .filter(
          // suche bis 2 Tage nach Einzahlung
          x => x.valuta.getTime() <= classification.valuta.getTime() + 2 * dayMs
            && x.betrag === classification.amount );
  return result;
}

function takeMatchingUnbooked(classification) {
  const unbooked = ungebuchteEinzahlungenIndex[classification.id] ?? [];
  const index = unbooked.findIndex(
    // suche bis 2 Tage nach Einzahlung
    x => x.valuta.getTime() <= classification.valuta.getTime() + 2 * dayMs
      && x.betrag === classification.amount );
  if (index < 0)
    return null;
  const result = unbooked.splice(index, 1)[0] ?? null;
  if (result) {
    const origIndex = ungebuchteEinzahlungen.findIndex(x => x.id === result.id);
    if (origIndex >= 0)
      ungebuchteEinzahlungen.splice(origIndex, 1);
  }
  return result;
}

function matchingUnbookedText(unbooked) {
  return unbooked.length <= 0
    ? 'KEINE passende Vormerkung'
    : unbooked.length === 1
    ? 'Eine passende Vormerkung'
    : `MEHRERE (${unbooked.length}) passende Vormerkungen`;
}

function setClassificationType(idx, buttonId) {
  const ui = classifierUis[idx];
  const chosenType = $(buttonId).value;
  const classification = classified[idx];
  const bookingCell = $(`booking-${idx}`);
  if (chosenType !== classification.type) {
    delete classification.type;
    delete classification.id;
    delete classification.note;
    bookingCell.classList.add('alert');
  }
  if (chosenType === Types.Gruppe) {
    const select = document.createElement('select');
    select.id = ui.groupSelectId ?? makeId();
    ui.groupSelectId = select.id;
    select.on('input', (e) => {
      if (e.target.value === '') {
        delete classification[Tags.Type];
        delete classification[Tags.Id];
        bookingCell.classList.add('alert');
      } else {
        classification[Tags.Type] = Types.Gruppe;
        classification[Tags.Id] = e.target.value;
        bookingCell.classList.remove('alert');
      }
      const matchingUnbooked = getMatchingUnbooked(classification);
      $(ui.groupFoundMatchId).textContent = matchingUnbookedText(matchingUnbooked);
      if (!classification.exception)
        $(ui.confirmIbanId).style.display = 'block';
    });
    addOption(select, '', 'Bitte wählen');
    gruppen.each(group => {
      addOption(select, group.id, `${group.name} (${group.nr})`)
    });
    const foundMatch = document.createElement('div');
    foundMatch.id = ui.groupFoundMatchId ?? makeId();
    ui.groupFoundMatchId = foundMatch.id;
    $(ui.detailsId).replaceChildren(select, foundMatch);
  } else if (chosenType === Types.Lieferanty) {
    const select = document.createElement('select');
    select.id = ui.supplierSelectId ?? makeId();
    ui.supplierSelectId = select.id;
    select.on('input', (e) => {
      if (e.target.value === '') {
        delete classification[Tags.Type];
        delete classification[Tags.Id];
        bookingCell.classList.add('alert');
      } else {
        classification[Tags.Type] = Types.Lieferanty;
        classification[Tags.Id] = e.target.value;
        bookingCell.classList.remove('alert');
      }
      if (!classification.exception)
        $(ui.confirmIbanId).style.display = 'block';
    });
    addOption(select, '', 'Bitte wählen');
    lieferantys.each(lieferanty => {
      addOption(select, lieferanty.id, lieferanty.name);
    });
    $(ui.detailsId).replaceChildren(select);
  } else if (chosenType === Types.Sonderausgabe) {
    bookingCell.classList.remove('alert');
    const input = document.createElement('input');
    input.id = ui.specialNoteId ?? makeId();
    ui.specialNoteId = input.id;
    input.type = 'text';
    input.placeholder = 'Zweck';
    input.title = 'Variablen: Kontoinhaber {kti}, Verwendungszweck {vwz}, Auszug-Nr {nr}, Auszug-Jahr {jahr}';
    input.value = '{kti}: {vwz}';
    input.on('input', (e) => {
      classification[Tags.Type] = Types.Sonderausgabe;
      classification[Tags.Note] = e.target.value;
      $(ui.specialNoteOutputId).textContent = formatNote(classification, e.target.value);
      if (!classification.exception)
        $(ui.confirmIbanId).style.display = 'block';
    });
    const output = document.createElement('div');
    output.id = ui.specialNoteOutputId ?? makeId();
    ui.specialNoteOutputId = output.id;
    $(ui.detailsId).replaceChildren(input, output);
    input.dispatchEvent(new Event('input'));
  } else {
    $(ui.detailsId).replaceChildren();
  }
}

let classified = [];
let classifiedReversed = false;

function buildClassifier() {
  classifier.splice(0, Infinity, ...fixedClassifier);
  if (!konto.regeln?.length) {
    konto.regeln = defaultAccountClassifier;
    storeRules('konto', konto.id, konto.regeln);
  }
  classifier.push(...konto.regeln.map((r, i) => Object.assign({...r}, {
    source: { type: 'Konto', index: i }
  })));
  gruppen.filter(g => g.regeln?.length).each(g => {
    classifier.push(...g.regeln.map((r, i) => setClassifierAction(i, {...r}, Types.Gruppe, g.id)));
  });
  lieferantys.filter(l => l.regeln?.length).each(l => {
    classifier.push(...l.regeln.map((r, i) => setClassifierAction(i, {...r}, Types.Lieferanty, l.id)));
  });
}

function formatDate(date) {
  return date.toLocaleDateString(undefined, {dateStyle: 'medium'});
}

function formatAmount(amount) {
  return amount.toFixed(2);
}

function formatGruppe(gruppe) {
  return `${gruppe.name}\xa0(${gruppe.nr})`;
}

function importAccountSheetPreview(reset = true) {
  let files = $('auszug_input').files;
  if (!files.length) {
    alert('Keine Datei gewählt!');
    return;
  }

  if (reset)
    classified.length = 0;
  Papa.parse(files[0], {
    skipEmptyLines: true,
    complete: function(results) {
      buildClassifier();
      try {
        let prepared = prepare(classifier, results.data);

        if (classifiedReversed) {
          classified.reverse();
          classifiedReversed = false;
        }

        for (let i = 1; i < results.data.length; ++i)
        {
          classify(prepared, results.data, i, classified);
        }
      }
      catch (e) {
        error(e.message, e.cause);
        $('kontoauszug').style.display = 'block';
        $('import').style.display = 'none';
        return;
      }

      $('kontoauszug').style.display = 'none';
      $('import').style.display = 'block';
      const importBody = $('import_body');
      importBody.replaceChildren();
      classifiedReversed = classified.at(0)?.valuta > classified.at(-1)?.valuta;
      if (classifiedReversed)
        classified.reverse();

      let hasError = false;
      classified.each((c, i) => {
        if (c.sourceAccount !== konto.nr) {
          alert(`Importierte Daten sind für anderes Konto: ${c.sourceAccount}`);
          $('kontoauszug').style.display = 'block';
          $('import').style.display = 'none';
          throw $break;
        }
        const row = importBody.insertRow();
        row.insertCell().textContent = i + 1;
        row.insertCell().textContent = formatDate(c.valuta);
        row.insertCell().textContent = c.otherName;
        row.insertCell().textContent = c.origNote;

        const amount = row.insertCell();
        amount.textContent = formatAmount(c.amount);
        amount.classList.add('price','number');
        const booking = row.insertCell();
        booking.id = `booking-${i}`;

        if (c.matchIndex !== undefined) {
          const edit_button = `<a class='edit' title='Manuell ändern' href='javascript:makeClassificationException(${i})'></a>`;
          const drop_button = `<a class='drop' title='Regel entfernen' href='javascript:dropClassifier(${c.matchIndex})'></a>`;
          let remark;
          if (c.type === Types.Gruppe) {
            const gruppe = gruppenIndex[c.id];
            const matchingUnbooked = getMatchingUnbooked(c);
            booking.textContent = `${gruppe.name}\xa0(${gruppe.nr})`;
            remark = matchingUnbookedText(matchingUnbooked);
          } else if (c.type === Types.Lieferanty) {
            const lieferanty = lieferantyIndex[c.id];
            booking.textContent = lieferanty.name;
          } else if (c.type === Types.Sonderausgabe) {
            booking.textContent = `Sonderausgabe: ${formatNote(c, c.note)}`;
          } else {
            booking.classList.add('warn');
            booking.textContent = `Fehlerhafter Buchungstyp: ${c.type}`;
            hasError = true;
          }
          booking.insert(edit_button);
          booking.insert(drop_button);
          if (remark !== undefined)
            booking.insert(`<div>${ remark }</div>`);
        } else {
          booking.innerHTML = makeClassificationUi(i);
          booking.classList.add('alert');
          setCurrentClassification(i);
        }
      });
      if (hasError)
        $('execImportButton').classList.add('disabled');
      else
        $('execImportButton').classList.remove('disabled');
    }
  });
}

function importAccountSheetExecute() {
  if (classified.length === 0) {
    alert('Keine Buchungen importiert!');
    return;
  }

  const unclassifiedRows = classified.reduce((a, c, i) => {
    if (c.type === undefined)
      a.push(i + 1);
      return a;
  }, []);
  if (unclassifiedRows.length) {
    const niceList = unclassifiedRows.join(', ').replace(/, ([^,]*)$/, ' und $1');
    const sind = unclassifiedRows.length > 1 ? 'sind' : 'ist';
    const werden = unclassifiedRows.length > 1 ? 'werden' : 'wird';
    if (!confirm(`Posten ${niceList} ${sind} nicht zugeordnet und ${werden} nicht verbucht. `
      + 'Trotzdem Verbuchung durchführen?'))
      return;
  }

  $('execImportButton').classList.add('disabled');

  const importLog = $('import_log');
  importLog.innerHTML = '<h3 class="bigskip">Import-Log</h3>';
  importLog.style.display = 'block';

  function book(index) {
    if (index >= classified.length) {
      importLog.insert(`<div class="ok">Finis.</div>`);
      offerCancelUnconfirmed();
      return;
    }

    const c = classified[index];
    const valuta = formatDate(c.valuta);
    const amount = formatAmount(c.amount);
    const note = formatNote(c, c.note);
    if (c.type === Types.Sonderausgabe) {
      ajaxCall({
          action: 'buchung_bank_sonderausgabe',
          valuta_day: c.valuta.getDate(),
          valuta_month: c.valuta.getMonth() + 1,
          valuta_year: c.valuta.getFullYear(),
          betrag: c.amount.toFixed(2),
          notiz: note,
        }).then((response) => {
          importLog.insert(`<div class="ok">Sonderausgabe: ${index+1}, ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          book(index + 1);
        }).catch((e) => {
          importLog.insert(`<div class="warn">Fehler: ${e.message}, ${index+1}: ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          error(e.message, e.cause);
        });
      return;
    }
    if (c.type === Types.Lieferanty) {
      ajaxCall({
          action: 'buchung_lieferant_bank',
          valuta_day: c.valuta.getDate(),
          valuta_month: c.valuta.getMonth() + 1,
          valuta_year: c.valuta.getFullYear(),
          lieferanten_id: c.id,
          betrag: c.amount.toFixed(2),
          notiz: note,
        }).then((response) => {
          importLog.insert(`<div class="ok">${lieferantyIndex[c.id].name}: ${index+1}, ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          book(index + 1);
        }).catch((e) => {
          importLog.insert(`<div class="warn">Fehler: ${e.message}, ${index+1}: ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          error(e.message, e.cause);
        });
      return;
    }
    if (c.type === Types.Gruppe) {
      const gruppe = gruppenIndex[c.id];
      const gruppeText = `Gruppe ${gruppe.name} (${gruppe.nr})`;
      const matchingUnbooked = takeMatchingUnbooked(c);
      if (matchingUnbooked) {
        ajaxCall({
          action: 'finish_transaction',
          valuta_day: c.valuta.getDate(),
          valuta_month: c.valuta.getMonth() + 1,
          valuta_year: c.valuta.getFullYear(),
          transaction_id: matchingUnbooked.id,
          confirm: 'yes'
        }).then((response) => {
          importLog.insert(`<div class="ok">Einzahlung bestätigt ${gruppeText}: ${index+1}, ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          $(`unbooked-${matchingUnbooked.id}`).remove();
          book(index + 1);
        }).catch((e) => {
          importLog.insert(`<div class="warn">Fehler: ${e.message}, ${index+1}: ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          error(e.message, e.cause);
        });
        return;
      }
      ajaxCall({
          action: 'buchung_gruppe_bank',
          valuta_day: c.valuta.getDate(),
          valuta_month: c.valuta.getMonth() + 1,
          valuta_year: c.valuta.getFullYear(),
          gruppen_id: c.id,
          betrag: c.amount.toFixed(2),
          ...(c.amount < 0 && { notiz: note }),
        }).then((response) => {
          importLog.insert(`<div class="ok">Neue ${c.amount >= 0 ? 'Einzahlung' : 'Auszahlung'} ${gruppeText}: ${index+1}, ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          book(index + 1);
        }).catch((e) => {
          importLog.insert(`<div class="warn">Fehler: ${e.message}, ${index+1}: ${valuta}, ${c.otherName}, ${amount}, ${note}</div>`);
          error(e.message, e.cause);
        });
      return;
    }
    importLog.insert(`<div class="alert">Nicht verbucht: ${index+1}: ${valuta}, ${c.otherName}, ${amount}, ${c.origNote}</div>`);
    setTimeout(() => book(index + 1), 50);
  }
  book(0);
}

function offerCancelUnconfirmed() {
  if (classified.length === 0) {
    $('show_account_button').style.display='block';
    return;
  }
  const tbody = $('remaining_unbooked_body');
  const lastValuta = classified.at(-1).valuta;
  tbody.replaceChildren();
  ungebuchteEinzahlungen
  .filter(
    x => x.valuta < lastValuta - 2 * dayMs)
  .each(x => {
    const row = tbody.insertRow();
    const gruppe = formatGruppe(gruppenIndex[x.gruppen_id]);
    const valuta = formatDate(x.valuta);
    const betrag = formatAmount(x.betrag);
    row.dataset.transaction = JSON.stringify({
      id: x.id,
      gruppe: gruppe,
      valuta: valuta,
      betrag: betrag,
    });
    row.insertCell().textContent = valuta;
    row.insertCell().textContent = gruppe;
    row.insertCell().textContent = betrag;
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.id = `cancel-${x.id}`;
    checkbox.checked = true;
    const checkboxCell = row.insertCell();
    checkboxCell.className = 'center';
    checkboxCell.insert(checkbox);
  });
  if (tbody.rows.length) {
    $('remaining_unbooked').style.display='block';
    $('cancelPaymentsButton').classList.remove('disabled');
  }
}

function cancelPayments() {
  $('cancelPaymentsButton').classList.add('disabled');
  const tbody = $('remaining_unbooked_body');
  const importLog = $('import_log');

  let toCancel = Array.from(tbody.rows)
  .map(x => JSON.parse(x.dataset.transaction))
  .filter(x => $(`cancel-${x.id}`).checked);

  function cancelNext() {
    if (toCancel.length === 0) {
      importLog.insert(`<div class="ok">Finis.</div>`);
      $('show_account_button').style.display='block';
      return;
    }
    const transaction = toCancel.shift();
    ajaxCall({
      action: 'cancel_payment',
      transaction_id: transaction.id,
    }).then((response) => {
      importLog.insert(`<div class="ok">Einzahlung storniert: ${transaction.gruppe}: ${transaction.valuta}, ${transaction.betrag}</div>`);
      $(`unbooked-${transaction.id}`).remove();
      cancelNext();
    }).catch((e) => {
      importLog.insert(`<div class="warn">Fehler: ${e.message}, ${transaction.gruppe}: ${transaction.valuta}, ${transaction.betrag}</div>`);
      error(e.message, e.cause);
    });
  }

  $('remaining_unbooked').style.display='none';

  if (toCancel.length === 0) {
    return;
  }
  importLog.insert(`<div class="ok">Storniere Einzahlungen:</div>`)
  cancelNext();
}

JS);

?>
