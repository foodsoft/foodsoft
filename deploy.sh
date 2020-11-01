#!/bin/sh
#
# this file is generated - do not modify!
#

export LANG=C
BRANCH=`git branch | sed -e '/^[^*]/d' -e 's/^\* \(.*\)/\1/'`
COMMIT=`git rev-parse --short HEAD`
COMMIT_FULL=`git rev-parse HEAD`
DIRTY=""
git status | grep -qF 'working directory clean' || DIRTY='-dirty'
echo "<a href='http://github.com/foodsoft/foodsoft/commits/$COMMIT_FULL'>$BRANCH-$COMMIT$DIRTY</a>" >src/version.txt

chmod 644 src/code/common.php
chmod 644 src/code/config.php
chmod 644 src/code/err_functions.php
chmod 644 src/code/forms.php
chmod 644 src/code/html.php
chmod 644 src/code/inlinks.php
chmod 644 src/code/katalogsuche.php
chmod 644 src/code/login.php
chmod 644 src/code/views.php
chmod 644 src/code/zuordnen.php
chmod 644 src/css/foodsoft.css
chmod 644 src/css/modified.gif
chmod 644 src/css/readonly.gif
chmod 644 src/dump.php
chmod 644 src/fcck.php
chmod 644 src/foodsoft.class.php
chmod 644 src/head.php
chmod 644 src/img/arrow.down.blue.png
chmod 644 src/img/arrow.up.blue.png
chmod 644 src/img/b_browse.png
chmod 644 src/img/b_drop.png
chmod 644 src/img/b_edit.png
chmod 644 src/img/birne_rot.png
chmod 644 src/img/card.png
chmod 644 src/img/chalk_trans.gif
chmod 644 src/img/chart.png
chmod 644 src/img/close_black.gif
chmod 644 src/img/close_black_hover.gif
chmod 644 src/img/close_black_trans.gif
chmod 644 src/img/euro.png
chmod 644 src/img/fant.gif
chmod 644 src/img/gluehbirne_15x16.png
chmod 644 src/img/green.png
chmod 644 src/img/magic_wand.png
chmod 644 src/img/minus.png
chmod 644 src/img/open_black_trans.gif
chmod 644 src/img/people.png
chmod 644 src/img/plus.png
chmod 644 src/img/print_black.gif
chmod 644 src/img/question.png
chmod 644 src/img/question_small.png
chmod 644 src/img/reload_black.gif
chmod 644 src/index.php
chmod 644 src/js/Acronyms.js
chmod 644 src/js/foodsoft.js
chmod 644 src/js/lib/prototype.js
chmod 644 src/js/tooltip.js
chmod 644 src/leitvariable.php
chmod 644 src/phpinfo.php
chmod 644 src/setup.php
chmod 644 src/structure.php
chmod 644 src/templates/antixls.modif
chmod 644 src/templates/bestellschein.tex
chmod 644 src/templates/prettytables.tex
chmod 644 src/windows/abrechnung.php
chmod 644 src/windows/abschluss.php
chmod 644 src/windows/artikelsuche.php
chmod 644 src/windows/basar.php
chmod 644 src/windows/bestellen.php
chmod 644 src/windows/bestellen.php.neu
chmod 644 src/windows/bestellfax.php
chmod 644 src/windows/bestellschein.php
chmod 644 src/windows/bestellungen.php
chmod 644 src/windows/bilanz.php
chmod 644 src/windows/catalogue_acronyms.php
chmod 644 src/windows/dienstkontrollblatt.php
chmod 644 src/windows/dienstplan.php
chmod 644 src/windows/editBestellung.php
chmod 644 src/windows/editBuchung.php
chmod 644 src/windows/editKonto.php
chmod 644 src/windows/editLieferant.php
chmod 644 src/windows/editProdukt.php
chmod 644 src/windows/editProduktgruppe.php
chmod 644 src/windows/editVerpackung.php
chmod 644 src/windows/gesamtlieferschein.php
chmod 644 src/windows/gruppen.php
chmod 644 src/windows/gruppenkonto.php
chmod 644 src/windows/gruppenmitglieder.php
chmod 644 src/windows/gruppenpfand.php
chmod 644 src/windows/head.php
chmod 644 src/windows/katalog_upload.php
chmod 644 src/windows/konto.php
chmod 644 src/windows/lieferanten.php
chmod 644 src/windows/lieferantenkonto.php
chmod 644 src/windows/menu.php
chmod 644 src/windows/pfandverpackungen.php
chmod 644 src/windows/produkte.php
chmod 644 src/windows/produktpreise.php
chmod 644 src/windows/produktverteilung.php
chmod 644 src/windows/updownload.php
chmod 644 src/windows/verluste.php
chmod 700 .git
