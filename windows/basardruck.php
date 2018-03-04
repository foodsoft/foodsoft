<?php
open_table('list');
    open_tr();
        open_th('', '', 'Artikel');
        open_th('', 'colspan=2', 'Endpreis');
        open_th('', 'colspan=2', 'Foodsoft');
        open_th('', '', 'Dienst 1/2');
        open_th('', '', 'Differenzursache');
        open_th('', '', 'Ende Dienst 3');
    close_tr();
        foreach(sql_basar(0, 'bestellung') as $basar_item)
        {
            list( $kan_verteilmult, $kan_verteileinheit ) = kanonische_einheit( $basar_item['verteileinheit'] );
            $menge = $basar_item['basarmenge'];

            // wir zeigen den Endpreis: vpreis + aufschlag:
            $preis = $basar_item['vpreis'] + $basar_item['preisaufschlag'];

            // umrechnen, z.B. Brokkoli von: x * (500g) nach (x * 500) g:
            $menge *= $kan_verteilmult;
            open_tr();
            open_td('', '', $basar_item['produkt_name']);
            open_td('mult', '', sprintf("%.2lf", $preis));
            open_td('unit', '', ' / ' . $basar_item['verteileinheit']);
            open_td('mult', '', $menge);
            open_td('unit', '', $kan_verteileinheit);
            open_td('', '', '');
            open_td('', '', '');
            open_td('', '', '');
            close_tr();
        }
close_table();
?>
